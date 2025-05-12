<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Attachments;
use Carbon\Carbon;
use App\Models\LeadUserActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class LeadController extends Controller
{
    use Utility,ApiResponseHandler;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sessionId = $request->session_id;
        //
        $key = $this->key;
        $leads = DB::table("leads")->select([
            DB::raw("AES_DECRYPT(cm_leads.email,'$key') as email"),
            DB::raw("AES_DECRYPT(cm_leads.phone,'$key') as phone"),
            DB::raw("AES_DECRYPT(cm_leads.company_name,'$key') as company_name"),
            "leads.first_name","leads.last_name","leads.url","leads.address",
            "leads.city","leads.state","leads.zip","leads.country","leads.comment",
            DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ', COALESCE(cm_u.last_name,''))  AS user_name"),
            "leads.lead_type", 'ls.status as status_name','leads.referral','leads.id','leads.profile_complete_percentage',
            'leads.is_manual',"leads.last_followup","leads.status_id","leads.speciality","leads.created_at",
            "lsa.is_msg_seen","leads.is_converted"
        ])
        ->leftJoin("lead_sessionuser_activity as lsa",function($leftJoin) use($sessionId){
            $leftJoin->on("lsa.lead_id","=","leads.id")
            ->where("session_userid",$sessionId);
        })

        ->join("users as u","u.id","=","leads.created_by",'left')

        ->join('lead_status as ls','ls.id','=','leads.status_id','left');

        if($request->has('smart_search')) {
            $search__ = $request->get('smart_search'); 
            $search_ = strtolower($request->get('smart_search'));
            $search = strtoupper($request->get('smart_search'));
            $cleanSeacch =  $this->sanitizePhoneNumber($search__);

            $whereRaw = "(AES_DECRYPT(cm_leads.company_name,'$key') LIKE '%$search%' OR AES_DECRYPT(cm_leads.email,'$key') LIKE '%$search_%'";
            if($cleanSeacch)
                $whereRaw .="OR AES_DECRYPT(cm_leads.phone,'$key') LIKE '%$cleanSeacch%'";
            
            $whereRaw .="OR cm_leads.referral LIKE '%$search%' OR cm_leads.speciality LIKE '%$search__%')";
            
            $leads = $leads->whereRaw($whereRaw);

        }
        if($request->has('status')) {
            $status = json_decode($request->get('status'),true);
            $leads = $leads->whereIn('leads.status_id', $status);
        }
        // echo $leads->toSql();
        // exit;
        $perPage = $request->has("per_page") ? $request->get("per_page") : $this->cmperPage;
      
        $leads = $leads->orderBy('leads.last_followup', 'desc')

        ->paginate($perPage);

        if(count($leads) > 0) {
            
            foreach ($leads as $lead) {

                $createdAt = $lead->created_at;
               
                // Parse the created_at date
                $createdDate = Carbon::parse($createdAt);

                // Get the current date
                $currentDate = Carbon::now();
                
                // Calculate the difference in days
                $daysPassed = $createdDate->diffInDays($currentDate);
               
                $lead->lead_age = $daysPassed;
                if(!isset($lead->is_msg_seen))
                    $lead->is_msg_seen = 0;
            }
        }
        $paginationData = [
            'last_page'     => $leads->lastPage(),
            'per_page'      => $leads->perPage(),
            'total'         => $leads->total(),
            'to'            => $leads->currentPage() * $leads->perPage(),
            'from'          => ($leads->currentPage() - 1) * $leads->perPage() + 1,
            'current_page'  => $leads->currentPage(),
        ];

        $leadStatus = $this->fetchData("lead_status",["active_status" => 1]);

        $leadTypesDD = $this->fetchData("leadtypes_dropdowns",["status" => 1]);

        $leadRefBDD  = $this->fetchData("referredby_dropdowns");

        return $this->successResponse([
            "leads" => $leads,"pagination" => $paginationData,
            'status' => $leadStatus, "lead_types"  => $leadTypesDD,
            "lead_ref_by"   => $leadRefBDD
        ],"success");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
            "company_name"  => "required",
            "session_userid"=> "required"
        ]);
        $key = $this->key;
        $sessionUserId = $request->session_userid;
        $params = $request->all();
        $recordExist = DB::table("leads")
        ->whereRaw("AES_DECRYPT(company_name, '$key') = '" . strtoupper($params["company_name"]) . "'")
        ->count();
        if($recordExist) {
            return $this->errorResponse([],"Company name already exist", 409);
        }
        // cm_leads
        $addData = [
                "company_name"  => DB::raw("AES_ENCRYPT('" .    strtoupper($params["company_name"])     . "', '$key')"),
                "created_by"    => $sessionUserId,
                "created_at"    => $this->timeStamp(),
                "updated_at"    => $this->timeStamp(),
                "status_id"     => "1"
        ];

        $id = DB::table("leads")->insertGetId($addData);
        $log = "Lead created";
        $addLog = [
            "lead_id"           => $id,
            "session_userid"    => $sessionUserId,
            "lead_status_id"    => 1,
            "details"           => DB::raw("AES_ENCRYPT('" .    $log     . "', '$key')"),
            "correspondence_type" => NULL,
            "created_at"        => $this->timeStamp()
        ];
        DB::table("lead_logs")->insertGetId($addLog);

        return $this->successResponse(["id" => $id],"success");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $request->validate(["lead_id" => "required"]);

        $id = $request->lead_id;

        $key = $this->key;
        $leads = DB::table("leads")->select([
            DB::raw("AES_DECRYPT(email,'$key') as email"),
            DB::raw("AES_DECRYPT(phone,'$key') as phone"),
            DB::raw("AES_DECRYPT(company_name,'$key') as company_name"),
            "first_name","last_name","url","address","city","state","zip","country","comment",
            "lead_type","profile_complete_percentage",'id',"county","referral","profile_image",
            "image_settings","speciality","software_type","software_name","average_monthly_collection",
            "no_of_providers","no_of_locations","percentage","flat_fee"
        ])

        //->join("users as u","u.id","=","leads.created_by","left")

        ->where("id","=",$id)

        ->first();
        if(is_object($leads)) {
            $leads->profile_image_url = "eCA/profile/".$leads->profile_image;
        }

        $leadTypesDD =  DB::table("leadtypes_dropdowns")

        ->select("id","name")

        ->orderByDesc("id")->get();//$this->fetchData("leadtypes_dropdowns");

        $leadRefBDD  = DB::table("referredby_dropdowns")

        ->select("id","name")

        ->orderByDesc("id")->get();//$this->fetchData("referredby_dropdowns");
        $leadTypes = [
                ["id" => 0,"name" => "Add New"]
        ];
        if(count($leadTypesDD)) {
            foreach($leadTypesDD as $leadType) {
                $leadTypes[] = [
                    "id" => $leadType->id,
                    "name" => $leadType->name
                ];
            }
        }
        $leadRefBy = [
            ["id" => 0,"name" => "Add New"]
        ];
        if(count($leadRefBDD)) {
            foreach($leadRefBDD as $leadRef) {
                $leadRefBy[] = [
                    "id" => $leadRef->id,
                    "name" => $leadRef->name
                ];
            }
        }
        $prepLead = [];
        if(is_object($leads)) {
            $prepLead["id"]                             = $leads->id;
            $prepLead["profile_complete_percentage"]    = $leads->profile_complete_percentage;
            $prepLead["image_settings"]                 = $leads->image_settings;
            $prepLead["profile_image"]                  = $leads->profile_image;
            $prepLead["profile_image_url"]              = $leads->profile_image_url;
            $prepLead["first_name"]                     = $leads->first_name;
            $prepLead["last_name"]                      = $leads->last_name;
            $prepLead["company_name"]                   = $leads->company_name;
            $prepLead["comment"]                        = $leads->comment;
            $prepLead["speciality"]                     = $leads->speciality;
            $prepLead["lead_type"]                      = $leads->lead_type;
            $prepLead["url"]                            = $leads->url;
            $prepLead["address"]                        = [
                "zip"               => $leads->zip,
                "street_address"    => $leads->address,
                "country"           => $leads->country,
                "state"             => $leads->state,
                "city"              => $leads->city,
                "state"             => $leads->state,
                "phone_number"      => $leads->phone,
                "email"             => $leads->email,
                "county"            => $leads->county,
                "referral"          => $leads->referral
            ];
            $prepLead["pricing_tool"] = [
                "software_type"             => $leads->software_type,
                "software_name"             => $leads->software_name,
                "average_monthly_collection"=> $leads->average_monthly_collection,
                "no_of_providers"           => $leads->no_of_providers,
                "no_of_locations"           => $leads->no_of_locations,
                "percentage"                => $leads->percentage,
                "flat_fee"                  => $leads->flat_fee
            ];

        }
        return $this->successResponse([
            "lead"          => $prepLead,
            "lead_types"    => $leadTypes,
            "lead_ref_by"   => $leadRefBy
        ],"success");
    }
    /**
     * fetch the speciality dropdown for practice
     * 
     */
    public function specialityDropdown(Request $request)
    {
       
        $facilityGroupData = $this->fetchData("facilities", ["type" => "group"]);
        $filterFaciltyGroupData = [];
        foreach ($facilityGroupData as $faclityg) {
            $value = explode(":",$faclityg->facility)[1];
            array_push($filterFaciltyGroupData, ["value" => $value, "label" => $value]);
        }

        return $this->successResponse(["speciality" => $filterFaciltyGroupData],"success");
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $key = $this->key;
        $updateData = [];
        if($request->profile_complete_percentage) {
            $updateData["profile_complete_percentage"] = $request->profile_complete_percentage;
        }
        if($request->first_name) {
            $updateData["first_name"] = $request->first_name;
        }
        if($request->speciality) {
            $updateData["speciality"] = $request->speciality;
        }
        if($request->last_name) {
            $updateData["last_name"] = $request->last_name;
        }
        if($request->company_name) {
            $updateData["company_name"] = DB::raw("AES_ENCRYPT('" .    strtoupper($request->company_name)     . "', '$key')");
        }
        if($request->comment) {
            $updateData["comment"] = $request->comment;
        }
        if($request->url) {
            $updateData["url"] = $request->url;
        }
        if($request->lead_type) {
            $updateData["lead_type"] = $request->lead_type;
        }

        $address = $request->address;

        if($address) {
            $updateData["city"]     = $address["city"];
            $updateData["state"]    = $address["state"];
            $updateData["zip"]      = $address["zip"];
            $updateData["country"]  = $address["country"];
            $updateData["address"]  = $address["street_address"];
            $updateData["county"]   = $address["county"];
            $updateData["phone"]    = DB::raw("AES_ENCRYPT('" .    $this->sanitizePhoneNumber($address["phone_number"])     . "', '$key')");
            $updateData["email"]    = DB::raw("AES_ENCRYPT('" .    strtolower($address["email"])     . "', '$key')");
            $updateData["referral"] = $address["referral"];
        }
        $pricingTool = $request->pricing_tool;
        if($pricingTool) {
            $updateData["software_type"]                = $pricingTool["software_type"];
            $updateData["software_name"]                = $pricingTool["software_name"];
            $updateData["average_monthly_collection"]   = $pricingTool["average_monthly_collection"];
            $updateData["no_of_providers"]              = $pricingTool["no_of_providers"];
            $updateData["no_of_locations"]              = $pricingTool["no_of_locations"];
            $updateData["percentage"]                   = $pricingTool["percentage"];
            $updateData["flat_fee"]                     = $pricingTool["flat_fee"];
        }
        $updateData["updated_at"] = $this->timeStamp();
        DB::table("leads")->where("id", "=", $id)->update($updateData);

        return $this->successResponse(["update" => true],"success");

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Upload Attachemnt against Lead
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
    */
    public function uploadLeadAttachment(Request $request){

        $request->validate([
            "lead_id" => "required",
            "file" => "required|file",
        ]);
        $leadLog = new LeadLogsController();
        $uploadFile = $leadLog->uploadLeadFile($request,$request->lead_id);
        if($uploadFile){
            return $this->successResponse([],"attachment add successfully");
        }
        return $this->errorResponse([],'something Went wrong', 500);
    }
     /**
     * upload the directory profile image
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadLeadProfileImage(Request $request) {
        $request->validate([
            'lead_id'   => "required"
        ]);
        $dateUpdate = [];
        $leadId = $request->lead_id;
        if( $request->hasFile("image")) {
            $image = $request->file("image");


            $imageSettings = $request->has('image_settings') ? $request->image_settings : null;

            $imageName = $leadId."_eca_profile".'.'.$image->getClientOriginalExtension();

            $this->updateData("leads",["id" => $leadId],["profile_image" => $imageName,'image_settings' => $imageSettings]);

            $this->uploadMyFile($imageName,$image,"eCA/profile");
            $dateUpdate["is_updated"] = false;
            $dateUpdate["image_name"] = $imageName;
        }
        else {
            $imageSettings = $request->has('image_settings') ? $request->image_settings : null;
            if(isset($imageSettings)) {
                $this->updateData("leads",["id" => $leadId],['image_settings' => $imageSettings]);
                $dateUpdate["is_updated"] = true;
            }
        }

        return $this->successResponse($dateUpdate, "success");
    }
    /**
     * Record the users activity against the lead
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reacordLeadActivity(Request $request) {
        
        $request->validate([
            "lead_id" => "required",
            "session_userid" => "required"
        ]);
        
        $isMsgSeen = 1;
        
        $activity = [
            "lead_id"           => $request->lead_id,
            "session_userid"    => $request->session_userid,
            "is_msg_seen"       => $isMsgSeen,
            "created_at" => $this->timeStamp()
        ];
        $exists = LeadUserActivity::where('lead_id', $request->lead_id)
        ->where("session_userid",$request->session_userid)
        ->exists();
        if(!$exists)
            $id = LeadUserActivity::insertGetId($activity);
        else {
            $id = LeadUserActivity::where('lead_id', $request->lead_id)
            ->where("session_userid",$request->session_userid)
            ->update(["is_msg_seen" => $isMsgSeen,"updated_at" => $this->timeStamp()]);
        }


        return $this->successResponse(["activity_id" => $id], "success");
    }
    /**
     * proccess won lead
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function proccessWonLead($leadId,Request $request) {
        
        $key = $this->key;
        $request->validate([
            "session_userid" => "required",
        ]);

        $sessionUserId = $request->session_userid;

        $lead = DB::table("leads")->select([
            DB::raw("AES_DECRYPT(cm_leads.email,'$key') as email"),
            DB::raw("AES_DECRYPT(cm_leads.phone,'$key') as phone"),
            DB::raw("AES_DECRYPT(cm_leads.company_name,'$key') as company_name"),
            "leads.first_name","leads.last_name","leads.url","leads.address",
            "leads.city","leads.state","leads.zip","leads.country","leads.comment",
            "leads.lead_type", 'leads.referral','leads.id','leads.profile_complete_percentage',
            'leads.is_manual',"leads.last_followup","leads.status_id","leads.speciality","leads.created_at"
        ])
        
        ->where("id",$leadId)

        ->first();

        if(is_object($lead)) {
            
            $practiceName   = $lead->company_name;

            $key = $this->key;
            
            $isPracticeUnique = $this->isPracticeNameUnique($practiceName);

            if (!$isPracticeUnique)
                return $this->warningResponse(["message" => "Valid practice name required"], "Validation error", 409);

            $sessionUserId = $request->get('session_userid');
            $email = strtolower($practiceName) . "@eclinicassist.com";
            $createPractice = [
                "email" =>  DB::raw("AES_ENCRYPT('" .    $email     . "', '$key')"),
                "password" => NULL,
                'is_complete' => false,
                'profile_complete_percentage' => 0,
                "created_at" => $this->timeStamp()
            ];
            $user = User::create($createPractice); //create the practice profile

            $user->createToken($practiceName . " Token")->plainTextToken; //create the practice token

            $practiceId = $user->id;

            $compMap = [
                'user_id' => $practiceId,
                'company_id' => 1
            ];
            $this->addData("user_company_map", $compMap);

            $this->addData("user_role_map",  ["user_id" => $practiceId, "role_id" => 9, "role_preference" => 1], 0); //assign the role the new practice
            $wherePracticeLinked = [
                ["emp_id", "=", $sessionUserId],
                ["location_user_id", "=", $practiceId],
            ];
            $practiceLinked = $this->fetchData("emp_location_map", $wherePracticeLinked, 1, []);
            if (!is_object($practiceLinked)) {
                $this->addData("emp_location_map", ["emp_id" => $sessionUserId, "location_user_id" => $practiceId]);
            }

            $practiceName = strtoupper($practiceName);
            $practiceInfo = [
                'user_id' => $practiceId,
                'practice_name' => $practiceName,
                'provider_type' => "group"
            ];
            $logMsg = "";
            $this->addData("user_baf_practiseinfo", $practiceInfo);
            $logMsg = "<b>".$practiceName . "</b> created <br>";
            

            $phone          = $lead->phone;
            
            $firstName      = $lead->first_name;
            $lastName       = $lead->last_name;
            $email          = $lead->email;

            $contactUpdateData = [];
            $contactUpdateData["user_id"]                       = $practiceId;
            $contactUpdateData["contact_person_name"]           = isset($firstName) ? $firstName . " ".$lastName            : null;
            $contactUpdateData["contact_person_email"]          = isset($email)     ? $email                                : null;
            $contactUpdateData["contact_person_phone"]          = isset($phone)     ? $this->sanitizePhoneNumber($phone)    : null;
            $contactUpdateData["created_at"]                    = $this->timeStamp();

            if (isset($contactUpdateData["contact_person_name"]) && !empty($contactUpdateData["contact_person_name"]))
                $logMsg .= " Contact person name assigned to <b>" . $contactUpdateData["contact_person_name"] . "</b> <br>";
            if (isset($contactUpdateData["contact_person_email"]) && !empty($contactUpdateData["contact_person_email"]))
                $logMsg .= " Contact person email assigned to <b>" . $contactUpdateData["contact_person_email"] . "</b> <br>";
            if (isset($contactUpdateData["contact_person_phone"]) && !empty($contactUpdateData["contact_person_phone"]))
                $logMsg .= " Contact person phone assigned to <b>" . $contactUpdateData["contact_person_phone"] . "</b> <br>";
            
            DB::table("user_baf_contactinfo")->insertGetId($contactUpdateData);
            
            $address        = $lead->address;
            $city           = $lead->city;
            $state          = $lead->state;
            $zip            = $lead->zip;
            $country        = $lead->country;

            $mailingUpdateData = [];
            $mailingUpdateData["user_id"]                           = $practiceId;
            $mailingUpdateData["mailing_address_zip_five"]          = isset($zip)       ? $zip      : null;
            $mailingUpdateData["mailing_address_street_address"]    = isset($address)   ? $address  : null;
            $mailingUpdateData["mailing_address_country"]           = isset($country)   ? $country  : null;
            $mailingUpdateData["mailing_address_city"]              = isset($city)      ? $city     : null;
            $mailingUpdateData["mailing_address_state"]             = isset($state)     ? $state    : null;
            

            $mailingUpdateData["created_at"] = $this->timeStamp();
           
            if (isset($mailingUpdateData["mailing_address_zip_five"]) && !empty($mailingUpdateData["mailing_address_zip_five"]))
                $logMsg .= " Mailing address zip five assigned to <b>" . $mailingUpdateData["mailing_address_zip_five"] . "</b> <br>";
            if (isset($mailingUpdateData["mailing_address_street_address"]) && !empty($mailingUpdateData["mailing_address_street_address"]))
                $logMsg .= " Mailing address street address assigned to <b>" . $mailingUpdateData["mailing_address_street_address"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_country"]) && !empty($mailingUpdateData["mailing_address_country"]))
                $logMsg .= " Mailing address country assigned to <b>" . $mailingUpdateData["mailing_address_country"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_city"]) && !empty($mailingUpdateData["mailing_address_city"]))
                $logMsg .= " Mailing address city assigned to <b>" . $mailingUpdateData["mailing_address_city"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_state"]) && !empty($mailingUpdateData["mailing_address_state"]))
                $logMsg .= " Mailing address state assigned to <b>" . $mailingUpdateData["mailing_address_state"] . "</b>  <br>";
            
            DB::table("user_baf_contactinfo")->insertGetId($mailingUpdateData);

            if (strlen($logMsg)) {
                DB::table("practice_logs")->insertGetId([
                    "practice_id" => $practiceId, "session_userid" => $sessionUserId, "section" => "Practice Profile",
                    "action" => "Update",
                    "practice_profile_logs" => DB::raw("AES_ENCRYPT('" .    $logMsg     . "', '$key')"),
                    'created_at' => $this->timeStamp()
                ]);
            }
            
            DB::table("leads")->where("id", $leadId)->update(["is_converted" => 1]);

            return $this->successResponse(["practice_id" => $practiceId], "success");
        }
        else {
            return $this->warningResponse(["message" => "Lead not found"], "Validation error", 404);
        }

    }
     /**
     *check the practice name uniqueness
     *
     * @param string $name
     */
    public function isPracticeNameUnique($name)
    {
        $key = $this->key;

        $data = DB::table('user_baf_practiseinfo')

            //->whereRaw("AES_DECRYPT(doing_buisness_as, '$key') = '$name'")
            ->where("practice_name", "=", $name)

            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    
}
