<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\BuisnessContact;
use Illuminate\Support\Facades\DB;

class BuisnessContactsController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * add the buisness contact
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function addBuisnessContact(Request $request)
    {
        $key = $this->key;
        $request->validate([
            "buisness_id"       => "required",
            "session_userid"    => "required",
            "email"             => "required_without:phone",
            'phone'             => 'required_without:email',
            "type"              => "required",
        ]);

        $buisnessId = $request->buisness_id;

        $createdBy  = $request->session_userid;

        $type       = $request->type;

        $category    = $request->category;

        $orgName     = $request->has("organization_name") ? $request->organization_name : null;
        $firstName   = $request->has("first_name") ? $request->first_name : null;
        $lastName    = $request->has("last_name") ? $request->last_name : null;

        $email      = $request->email;

        $profileCompletePercentage = $request->has("profile_complete_percentage") ? $request->profile_complete_percentage : 0;
        $email      = $request->email;

        $profileCompletePercentage = $request->has("profile_complete_percentage") ? $request->profile_complete_percentage : 0;
        $linkedProfileId = $request->get("linked_profileid");
        if($request->has("linked_profileid") && isset($linkedProfileId) && $linkedProfileId !="null" && ($type == "provider" || $type == "practice")) {
            
            if(BuisnessContact::where("linked_profileid",$linkedProfileId)->count() > 0) {
                $contactId = BuisnessContact::where("linked_profileid",$linkedProfileId)->first(["id"]);
                $contactExist = DB::table("buisnees_contact_map")->where("contact_id", $contactId->id)->where("buisness_id", $buisnessId)->count();
                if($contactExist == 0) {
                    DB::table("buisnees_contact_map")
                    ->insertGetId([
                        "contact_id" => $contactId->id,"buisness_id" => $buisnessId,
                        "created_at" => $this->timeStamp(),"updated_at" => $this->timeStamp()
                    ]);
                }

                return $this->successResponse(["create" => $contactId->id], "success");
            } else {
                $addData = [
                    "email"             => DB::raw("AES_ENCRYPT('" .    $request->email     . "', '$key')"),
                    "type"              => $type,
                    "organization_name" => $orgName,
                    "first_name"        => $firstName,
                    "last_name"         => $lastName,
                    "created_by"        => $createdBy,
                    "profile_complete_percentage" => $profileCompletePercentage,
                    "created_at"        => $this->timeStamp(),
                    "updated_at"        => $this->timeStamp(),
                    "linked_profileid"  => $linkedProfileId,
                    "category"          => $category
                ];
                //$this->printR($addData,true);
                $addBuisnessContact = BuisnessContact::insertGetId($addData);
                /**
                 * map the contact
                 */
                DB::table("buisnees_contact_map")
                ->insertGetId([
                    "contact_id" => $addBuisnessContact,"buisness_id" => $buisnessId,
                    "created_at" => $this->timeStamp(),"updated_at" => $this->timeStamp()
                ]);

                return $this->successResponse(["create" => $addBuisnessContact], "success");
            }
        }
        elseif($request->has("linked_profileid") && isset($linkedProfileId) && $linkedProfileId !="null" && $type == "contacts") {
            
            if(BuisnessContact::where("id",$linkedProfileId)->count() > 0) {
                $contactId = BuisnessContact::where("id",$linkedProfileId)->first(["id"]);
                $contactExist = DB::table("buisnees_contact_map")->where("contact_id", $contactId->id)->where("buisness_id", $buisnessId)->count();
                if($contactExist == 0) {
                   
                    DB::table("buisnees_contact_map")
                    ->insertGetId([
                        "contact_id" => $contactId->id,"buisness_id" => $buisnessId,
                        "created_at" => $this->timeStamp(),"updated_at" => $this->timeStamp()
                    ]);
                }

                return $this->successResponse(["create" => $contactId->id], "success");
            }
            else {
                $addData = [
                    "email"             => DB::raw("AES_ENCRYPT('" .    $request->email     . "', '$key')"),
                    "type"              => $type,
                    "organization_name" => $orgName,
                    "first_name"        => $firstName,
                    "last_name"         => $lastName,
                    "created_by"        => $createdBy,
                    "profile_complete_percentage" => $profileCompletePercentage,
                    "created_at"        => $this->timeStamp(),
                    "updated_at"        => $this->timeStamp(),
                    "linked_profileid"  => NULL,
                    "category"          => $category
                ];
                //$this->printR($addData,true);
                $addBuisnessContact = BuisnessContact::insertGetId($addData);
                /**
                 * map the contact
                 */
                DB::table("buisnees_contact_map")
                ->insertGetId([
                    "contact_id" => $addBuisnessContact,"buisness_id" => $buisnessId,
                    "created_at" => $this->timeStamp(),"updated_at" => $this->timeStamp()
                ]);

                return $this->successResponse(["create" => $addBuisnessContact], "success");
            }
        } else {
            $recordExist = null;
            if (is_null($orgName)) {
                $fullName = $request->first_name . " " . $request->last_name;
           
                $recordExist = BuisnessContact::select("first_name", "last_name", DB::raw("AES_DECRYPT(email, '$key') as email"), "organization_name", DB::raw("AES_DECRYPT(phone, '$key') as phone"));
                // ->OrwhereRaw("CONCAT(first_name,' ',last_name) = '$fullName'");
                if($request->has('phone') && $request->phone != null){
                    $recordExist = $recordExist->OrWhereRaw("AES_DECRYPT(phone, '$key') = '$request->phone'");
                }
                if($request->has('email') && $request->email != null){
                    $recordExist = $recordExist->OrWhereRaw("AES_DECRYPT(email, '$key') = '$email'");
                }
                $recordExist = $recordExist->first();
            } else {
                $recordExist = BuisnessContact::select("first_name", "last_name", DB::raw("AES_DECRYPT(email, '$key') as email"), "organization_name",DB::raw("AES_DECRYPT(phone, '$key') as phone"));
                // ->whereRaw("organization_name = '$orgName'")

                if($request->has('phone') && $request->phone != null){
                    $recordExist = $recordExist->OrWhereRaw("AES_DECRYPT(phone, '$key') = '$request->phone'");
                }
                if($request->has('email') && $request->email != null){
                    $recordExist = $recordExist->OrWhereRaw("AES_DECRYPT(email, '$key') = '$email'");
                }
                $recordExist = $recordExist->first();
            }

            // dd($request->all(),$orgName,$recordExist->toArray(),$key);

            if (!is_object($recordExist)) {
                $addData = [
                    "first_name"        => $request->first_name,
                    "last_name"         => $request->last_name,
                    "email"             => $request->has('email') && $request->email != null ? DB::raw("AES_ENCRYPT('" .    $request->email     . "', '$key')") : null,
                    "phone"             => $request->has('phone') && $request->phone != null ? DB::raw("AES_ENCRYPT('" .    $this->sanitizePhoneNumber($request->phone)     . "', '$key')") : null,
                    "fax_number"        => DB::raw("AES_ENCRYPT('" .    $request->fax_number     . "', '$key')"),
                    "title"             => $request->title,
                    "type"              => $type,
                    "organization_name" => $request->organization_name,
                    "created_by"        => $createdBy,
                    "profile_complete_percentage" => $profileCompletePercentage,
                    "created_at"        => $this->timeStamp(),
                    "updated_at"        => $this->timeStamp(),
                    "category"          => $category
                ];
                //$this->printR($addData,true);
                $addBuisnessContact = BuisnessContact::insertGetId($addData);
                /**
                 * map the contact
                 */
                DB::table("buisnees_contact_map")
                    ->insertGetId([
                        "contact_id" => $addBuisnessContact, "buisness_id" => $buisnessId,
                        "created_at" => $this->timeStamp(), "updated_at" => $this->timeStamp()
                    ]);

                return $this->successResponse(["create" => $addBuisnessContact], "success");
            } else {
                $orgName = $recordExist->organization_name;

                if (is_null($orgName) && (!is_null($recordExist->first_name) || !is_null($recordExist->last_name))) {
                    //exit("here");
                    if ($recordExist->first_name == $request->first_name) {
                        return $this->warningResponse(["create" => 0], "contact first name already exist", 422);
                    }
                    if ($recordExist->last_name == $request->last_name) {
                        return $this->warningResponse(["create" => 0], "contact last name already exist", 422);
                    }
                    if ($recordExist->email == $request->email) {
                        return $this->warningResponse(["create" => 0], "contact email already exist", 422);
                    }
                    if ($recordExist->phone == $request->phone) {
                        return $this->warningResponse(["create" => 0], "contact phone already exist", 422);
                    }
                } else {
                    if ($recordExist->organization_name == $request->organization_name) {
                        return $this->warningResponse(["create" => 0], "contact orgnization name already exist", 422);
                    }
                    if ($recordExist->email == $request->email) {
                        return $this->warningResponse(["create" => 0], "contact email already exist", 422);
                    }
                }
            }
        }
    }
    /**
     * get the buisness contact
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function getBuisnessContact(Request $request)
    {
        $key = $this->key;
        $request->validate([
            "buisness_id"   => "required"
        ]);
        $buisnessId = $request->buisness_id;

        $buisnessContact = BuisnessContact::select(
            "buisness_contacts.id",
            "buisness_contacts.first_name",
            "buisness_contacts.last_name",
            DB::raw("AES_DECRYPT(cm_buisness_contacts.email, '$key') as email"),
            DB::raw("AES_DECRYPT(cm_buisness_contacts.phone, '$key') as phone"),
            DB::raw("AES_DECRYPT(cm_buisness_contacts.fax_number, '$key') as fax_number"),
            "buisness_contacts.title",
            "buisness_contacts.type",
            "buisness_contacts.organization_name",
            "buisness_contacts.zip",
            DB::raw("AES_DECRYPT(cm_buisness_contacts.street_address, '$key') as street_address"),
            "buisness_contacts.country",
            "buisness_contacts.city",
            "buisness_contacts.state",
            "buisness_contacts.county",
            "buisness_contacts.is_active",
            "buisness_contacts.linked_profileid",
            "buisness_contacts.category",
            "buisness_contacts.profile_image",
            "buisness_contacts.image_settings"

        )
            ->join('buisnees_contact_map', 'buisnees_contact_map.contact_id', "buisness_contacts.id")
            ->where('buisnees_contact_map.buisness_id', $buisnessId)
            ->orderBy("buisness_contacts.created_at", "DESC")
            ->get();
        $contactsData = [];
        if ($buisnessContact->count() > 0) {
            foreach ($buisnessContact as $contact) {
                $profile_image_url = "eCA/profile/" . $contact->profile_image;

                $contactArr["id"]                   = $contact->id;
                $contactArr["first_name"]           = $contact->first_name;
                $contactArr["last_name"]            = $contact->last_name;
                $contactArr["title"]                = $contact->title;
                $contactArr["type"]                 = $contact->type;
                $contactArr["organization_name"]    = $contact->organization_name;
                $contactArr["is_active"]            = $contact->is_active;
                $contactArr["category"]             = $contact->category;
                $contactArr["linked_profileid"]     = $contact->linked_profileid;
                $contactArr["profile_image_url"]    = $profile_image_url;
                $contactArr["profile_image"]        = $contact->profile_image;
                $contactArr["image_settings"]        = $contact->image_settings;


                $contactArr["address"]              = [
                    "zip"               => $contact->zip,
                    "street_address"    => $contact->street_address,
                    "country"           => $contact->country,
                    "city"              => $contact->city,
                    "state"             => $contact->state,
                    "county"            => $contact->county,
                    "phone_number"      => $contact->phone,
                    "fax_number"        => $contact->fax_number,
                    "email"             => $contact->email
                ];
                array_push($contactsData, $contactArr);
            }
        }
        return $this->successResponse(["data" => $contactsData], "success");
    }
    /**
     * update the contacts information
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function updateBuisnessContact(Request $request, $id)
    {

        $inputData = $request->all();

        $key = $this->key;

        $updatedBy = $request->has("session_userid") ? $request->session_userid : null;

        $address    = $request->has("address")          ? $request->address         : null;

        $firstName  =  isset($inputData["first_name"])  ? $inputData["first_name"]  : null;

        $isActive  =  isset($inputData["is_active"])  ? $inputData["is_active"]     : 0;

        $lastName   =  isset($inputData["last_name"])   ? $inputData["last_name"]   : null;

        $title      =  $inputData["title"];

        $orgName    =  isset($inputData["organization_name"]) ? $inputData["organization_name"] : null;

        $profileCompletePercentage = isset($inputData["profile_complete_percentage"]) ? $inputData["profile_complete_percentage"] : 0;

        $updateData = ["first_name" => $firstName, "last_name" => $lastName, "title" => $title, "organization_name" => $orgName, "is_active" => $isActive];

        $email          = $address["email"];
        $phone          = $this->sanitizePhoneNumber($address["phone_number"]);
        $faxNumber      = $address["fax_number"];
        $streetAddress  = $address["street_address"];

        if (isset($address["email"])) {
            $updateData["email"] = DB::raw("AES_ENCRYPT('$email', '$key')");
        }
        if (isset($address["phone_number"])) {
            $updateData["phone"] = DB::raw("AES_ENCRYPT('$phone', '$key')");
        }
        if (isset($address["fax_number"])) {
            $updateData["fax_number"] = DB::raw("AES_ENCRYPT('$faxNumber', '$key')");
        }
        if (isset($address["street_address"])) {
            $updateData["street_address"] = DB::raw("AES_ENCRYPT('$streetAddress', '$key')");
        }

        $updateData["updated_by"]   = $updatedBy;

        $updateData["zip"]          = $address["zip"];

        $updateData["country"]      = $address["country"];

        $updateData["city"]         = $address["city"];

        $updateData["state"]        = $address["state"];

        $updateData["county"]       = $address["county"];

        $updateData["profile_complete_percentage"]       = $profileCompletePercentage;

        $updateData["updated_at"] = $this->timeStamp();

        $isUpdate = BuisnessContact::where('id', $id)

            ->update($updateData);

        return $this->successResponse(["is_update" => $isUpdate], "success");
    }
    /**
     * delete buisness contact
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function deleteBuisnessContact(Request $request, $id)
    {
        //is_deleted
        $isUpdate = BuisnessContact::where('id', $id)

            ->update(["is_active" => 0]);

        return $this->successResponse(["is_deleted" => $isUpdate], "success");
    }
    /**
     * search the contact with the specified criteria practice name
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function searchPracticeContact(Request $request)
    {

        $request->validate([
            "keyword" => "required",
        ]);

        $keyword = $request->keyword;
        
        $key = $this->key;

        $practiceData = DB::table("user_baf_practiseinfo")

        ->select("user_baf_practiseinfo.practice_name as user_name","user_baf_practiseinfo.user_id","user_baf_practiseinfo.practice_name","user_baf_contactinfo.contact_person_email as email",DB::raw(" 'practice' as type"))
        
        ->join("user_baf_contactinfo","user_baf_contactinfo.user_id","=","user_baf_practiseinfo.user_id")

        ->where("user_baf_practiseinfo.practice_name","LIKE","%".$keyword."%");

        $practiceData1 = DB::table("buisness_contacts")

        ->select("organization_name as user_name","buisness_contacts.id as user_id","organization_name as practice_name",DB::raw("AES_DECRYPT(email, '$key') as email"),DB::raw(" 'contacts' as type"))
        
        ->where("category","=","organization")

        ->whereRaw("(organization_name LIKE '%$keyword%' OR AES_DECRYPT(email, '$key') LIKE '%$keyword%')");
        //->get();
        $practiceData = $practiceData->union($practiceData1)
       
        ->get();
        
        $jsonArray = $this->stdToArray($practiceData);

        $uniqueUsernames = collect($jsonArray)->unique('user_name')->values()->all();
        return $this->successResponse(["data" => $uniqueUsernames], "success");

    }
    /**
     * search the contact with the specified criteria first name
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function searchProviderContact(Request $request)
    {

        $request->validate([
            "keyword" => "required",
        ]);

        $key = $this->key;

        $keyword = $request->keyword;

        $providerData = DB::table("users")

        ->select(DB::raw("CONCAT(first_name,' ',last_name) as user_name"),"users.id as user_id","users.first_name","users.last_name",DB::raw("AES_DECRYPT(email, '$key') as email"),DB::raw(" 'provider' as type"))
        
        
        ->whereRaw("CONCAT(first_name,' ',last_name) LIKE '%$keyword%'")

        ->orWhereRaw("AES_DECRYPT(email, '$key') LIKE '%$keyword%'");

        $providerData1 = DB::table("buisness_contacts")

        ->select(DB::raw("CONCAT(first_name,' ',last_name) as user_name"),"buisness_contacts.id as user_id","buisness_contacts.first_name","buisness_contacts.last_name",DB::raw("AES_DECRYPT(email, '$key') as email"),DB::raw(" 'contacts' as type"))
        
        ->where("category","=","person")

        ->whereRaw("(CONCAT(first_name,' ',last_name) LIKE '%$keyword%' OR AES_DECRYPT(email, '$key') LIKE '%$keyword%')");

        //->orWhereRaw("AES_DECRYPT(email, '$key') LIKE '%$keyword%'");

        //->get();
        $providerData = $providerData->union($providerData1)
       
        ->get();
        
        $jsonArray = $this->stdToArray($providerData);

        $uniqueUsernames = collect($jsonArray)->unique('user_name')->values()->all();
        
        return $this->successResponse(["data" => $uniqueUsernames], "success");

    }
    /**
     * fetch the buisness contact details
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function getBuisnessContactDetails(Request $request)
    {
        $key = $this->key;
        $request->validate([
            "contact_id"   => "required"
        ]);
        $contactId = $request->contact_id;


        $buisnessContact = BuisnessContact::select(
            "buisness_contacts.id",
            "buisness_contacts.first_name",
            "buisness_contacts.last_name",
            DB::raw("AES_DECRYPT(cm_buisness_contacts.email, '$key') as email"),
            DB::raw("AES_DECRYPT(cm_buisness_contacts.phone, '$key') as phone"),
            DB::raw("AES_DECRYPT(cm_buisness_contacts.fax_number, '$key') as fax_number"),
            "buisness_contacts.title",
            "buisness_contacts.type",
            "buisness_contacts.organization_name",
            "buisness_contacts.zip",
            DB::raw("AES_DECRYPT(cm_buisness_contacts.street_address, '$key') as street_address"),
            "buisness_contacts.country",
            "buisness_contacts.city",
            "buisness_contacts.state",
            "buisness_contacts.county",
            "buisness_contacts.is_active",
            "buisness_contacts.linked_profileid",
            "buisness_contacts.category",
            "buisness_contacts.profile_image",
            "buisness_contacts.image_settings"

        )
            ->join('buisnees_contact_map', 'buisnees_contact_map.contact_id', "buisness_contacts.id")
            ->where('buisness_contacts.id', $contactId)
            ->get();
        $contactsData = [];
        if ($buisnessContact->count() > 0) {
            foreach ($buisnessContact as $contact) {
                $profile_image_url = "eCA/profile/" . $contact->profile_image;
                $contactsData["id"]                   = $contact->id;
                $contactsData["first_name"]           = $contact->first_name;
                $contactsData["last_name"]            = $contact->last_name;
                $contactsData["title"]                = $contact->title;
                $contactsData["type"]                 = $contact->type;
                $contactsData["organization_name"]    = $contact->organization_name;
                $contactsData["is_active"]            = $contact->is_active;
                $contactsData["category"]             = $contact->category;
                $contactsData["linked_profileid"]     = $contact->linked_profileid;
                $contactsData["profile_image_url"]      = $profile_image_url;
                $contactsData["profile_image"]          = $contact->profile_image;
                $contactsData["image_settings"]        = $contact->image_settings;
                $contactsData["address"]              = [
                    "zip"               => $contact->zip,
                    "street_address"    => $contact->street_address,
                    "country"           => $contact->country,
                    "city"              => $contact->city,
                    "state"             => $contact->state,
                    "county"            => $contact->county,
                    "phone_number"      => $contact->phone,
                    "fax_number"        => $contact->fax_number,
                    "email"             => $contact->email
                ];
            }
        }
        return $this->successResponse(["contact" => $contactsData], "success");
    }

    /**
     * upload the directory profile image
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadContactProfileImage(Request $request)
    {
        $request->validate([
            'contact_id'   => "required"
        ]);
        $dateUpdate = [];
        $contactId = $request->contact_id;
        if ($request->hasFile("image")) {
            $image = $request->file("image");


            $imageSettings = $request->has('image_settings') ? $request->image_settings : null;

            $imageName = $contactId . "_eca_profile" . '.' . $image->getClientOriginalExtension();

            $this->updateData("buisness_contacts", ["id" => $contactId], ["profile_image" => $imageName, 'image_settings' => $imageSettings]);

            $this->uploadMyFile($imageName, $image, "eCA/profile");
            $dateUpdate["is_updated"] = false;
            $dateUpdate["image_name"] = $imageName;
        } else {
            $imageSettings = $request->has('image_settings') ? $request->image_settings : null;
            if (isset($imageSettings)) {
                $this->updateData("buisness_contacts", ["id" => $contactId], ['image_settings' => $imageSettings]);
                $dateUpdate["is_updated"] = true;
            }
        }

        return $this->successResponse($dateUpdate, "success");
    }
    /**
     * fetch the contact affliations
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchContactAffiliations(Request $request) {

        $request->validate([
            'contact_id'   => "required",
            'type'         =>'required'
        ]);

        $contactId = $request->contact_id;

        $type = $request->type;
        $key = $this->key;
        $affiliations = DB::table('buisness_contacts')
            ->select("buisnees_contact_map.buisness_id",DB::raw("CASE
            WHEN cm_leads.company_name IS NOT NULL THEN AES_DECRYPT(cm_leads.company_name,'$key')
            ELSE AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name,'$key')
            END AS buisness_name"),DB::raw("CASE
            WHEN cm_leads.company_name IS NOT NULL THEN 'lead'
            ELSE 'facility'
            END AS affiliation_type"))
            ->join('buisnees_contact_map', 'buisnees_contact_map.contact_id',"=", 'buisness_contacts.id','left')
            ->join('leads', 'leads.id',"=", 'buisnees_contact_map.buisness_id','left')
            ->join('user_ddpracticelocationinfo', 'user_ddpracticelocationinfo.user_id',"=", 'buisnees_contact_map.buisness_id','left')
            ->where('buisnees_contact_map.contact_id', $contactId)
            ->where('buisness_contacts.type', $type)
            ->get();

        return $this->successResponse($affiliations, "success");
    }
}
