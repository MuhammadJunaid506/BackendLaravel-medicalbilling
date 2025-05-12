<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Provider;
use App\Models\ProviderCompanyMap;
use Mail;
use App\Mail\BAFSubmitted;
use App\Mail\commonEmail;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\AssignProvider;
use App\Models\AssignCredentialingTaks;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\BAF;
use App\Http\Controllers\UserCommonFunc;
use App\Http\Controllers\StatsController as EnrollmentStats;
use App\Http\Controllers\Api\LicenseController;
use App\Models\PracticeLocation;
use  App\Models\IdentifierTypes;
use App\Models\Portal;
use App\Models\InsuranceCoverage;
use DB;
use Illuminate\Pagination\Paginator as customPagination;

class ProviderController extends Controller
{
    use ApiResponseHandler, Utility;

    private $providerCols2 = [
        "providers.*",
        "companies.company_name",
        "companies.company_country",
        "companies.company_logo",
        "contracts.contract_token",
        "discoverydocuments.dd_token"
    ];
    private $providerCols3 = [
        "providers.*",
        "companies.company_name",
        "companies.company_country",
        "companies.company_logo"
    ];
    private $providerManagementCols = [
        "user_baf_practiseinfo.id", "user_baf_practiseinfo.user_id", "user_baf_practiseinfo.provider_type", "user_baf_contactinfo.contact_person_name",
        "user_baf_contactinfo.contact_person_email", "user_baf_contactinfo.contact_person_phone", "user_baf_contactinfo.state", "users.deleted",
        "user_baf_practiseinfo.provider_name", "user_baf_practiseinfo.legal_business_name", "user_role_map.role_id as role_id"
    ];
    private $tbl = "user_ddpracticelocationinfo";
    private $tblU = "users";
    private $key = "";
    public function __construct()
    {
        $this->key = env("AES_KEY");
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {

            // $this->printR($request->all(),true);
            if ($request->has("operational_manager") && $request->operational_manager == true) {

                $user = UserProfile::where("id", "=", $request->id)->first(["user_id"]);

                $userId = $user->user_id;

                // $providers = AssignProvider::select($this->providerCols)


                // ->leftJoin("providers","providers.id","=","assign_providers.provider_id")

                // ->leftJoin("providers_companies_map","providers_companies_map.provider_id","=","providers.id")

                // ->leftJoin("companies","companies.id","=","providers_companies_map.company_id")

                // ->where("assign_providers.operational_m_id","=",$userId)

                // ->get();

                $providers = AssignProvider::select("assign_providers.provider_id")


                    //->leftJoin("providers","providers.id","=","assign_credentialingtask.provider_id")

                    // ->leftJoin("providers_companies_map","providers_companies_map.provider_id","=","providers.id")

                    // ->leftJoin("companies","companies.id","=","providers_companies_map.company_id")

                    ->where("assign_providers.operational_m_id", "=", $userId)

                    //->groupBy("assign_credentialingtask.provider_id")

                    ->get();

                if (count($providers)) {

                    $providers = $this->stdToArray($providers);

                    $providersIds = array_column($providers, "provider_id");

                    $providers = Provider::select($this->providerCols)

                        ->join("providers_companies_map", "providers_companies_map.provider_id", "=", "providers.id")

                        ->leftJoin("companies", "companies.id", "=", "providers_companies_map.company_id")

                        ->whereIn("providers.id", $providersIds)

                        ->get();
                }

                return $this->successResponse(["providers" => $providers], "Success");
            } else if ($request->has("team_lead") && $request->team_lead == true) {

                $user = UserProfile::where("id", "=", $request->id)->first(["user_id"]);

                $userId = $user->user_id;

                // $providers = AssignCredentialingTaks::select($this->providerCols)


                // ->leftJoin("providers","providers.id","=","assign_credentialingtask.provider_id")

                // ->leftJoin("providers_companies_map","providers_companies_map.provider_id","=","providers.id")

                // ->leftJoin("companies","companies.id","=","providers_companies_map.company_id")

                // ->where("assign_credentialingtask.user_id","=",$userId)

                // ->get();

                $providers = AssignCredentialingTaks::select("assign_credentialingtask.provider_id")


                    //->leftJoin("providers","providers.id","=","assign_credentialingtask.provider_id")

                    // ->leftJoin("providers_companies_map","providers_companies_map.provider_id","=","providers.id")

                    // ->leftJoin("companies","companies.id","=","providers_companies_map.company_id")

                    ->where("assign_credentialingtask.user_id", "=", $userId)

                    ->groupBy("assign_credentialingtask.provider_id")

                    ->get();

                if (count($providers)) {

                    $providers = $this->stdToArray($providers);

                    $providersIds = array_column($providers, "provider_id");

                    $providers = Provider::select($this->providerCols)

                        ->join("providers_companies_map", "providers_companies_map.provider_id", "=", "providers.id")

                        ->leftJoin("companies", "companies.id", "=", "providers_companies_map.company_id")

                        ->whereIn("providers.id", $providersIds)

                        ->get();
                }

                return $this->successResponse(["providers" => $providers], "Success");
            } else if ($request->has("team_member") && $request->team_member == true) {

                $user = UserProfile::where("id", "=", $request->id)->first(["user_id"]);

                $userId = $user->user_id;


                $providers = AssignCredentialingTaks::select("assign_credentialingtask.provider_id")


                    //->leftJoin("providers","providers.id","=","assign_credentialingtask.provider_id")

                    // ->leftJoin("providers_companies_map","providers_companies_map.provider_id","=","providers.id")

                    // ->leftJoin("companies","companies.id","=","providers_companies_map.company_id")

                    ->where("assign_credentialingtask.user_id", "=", $userId)

                    ->groupBy("assign_credentialingtask.provider_id")

                    ->get();

                if (count($providers)) {

                    $providers = $this->stdToArray($providers);

                    $providersIds = array_column($providers, "provider_id");

                    $providers = Provider::select($this->providerCols)

                        ->join("providers_companies_map", "providers_companies_map.provider_id", "=", "providers.id")

                        ->leftJoin("companies", "companies.id", "=", "providers_companies_map.company_id")

                        ->whereIn("providers.id", $providersIds)

                        ->get();
                }
                return $this->successResponse(["providers" => $providers], "Success");
            } else {
                $searchCol = $request->col;

                $searchVal = $request->val;

                $whichSection = $request->which_section;
                if ($whichSection == "provider") {
                    // $this->printR($this->providerManagementCols,true);
                    $providers = User::select($this->providerManagementCols)
                        ->join("user_role_map", function ($join) {
                            $join->on("user_role_map.user_id", "=", "users.id")
                                ->whereIn("role_id", [2, 3]);
                        })
                        ->join("user_baf_contactinfo", "user_baf_contactinfo.user_id", "=", "users.id")

                        ->join("user_baf_businessinfo", "user_baf_businessinfo.user_id", "=", "users.id")

                        ->join("user_baf_practiseinfo", "user_baf_practiseinfo.user_id", "=", "users.id");

                    //->where("users.deleted","=",0);

                    if ($searchVal != "") {
                        $providers = $providers->whereDate('user_baf_practiseinfo.created_at', "=", $searchVal)

                            ->orWhere('user_baf_contactinfo.contact_person_name', 'LIKE', '%' . $searchVal . '%')

                            ->orWhere('user_baf_contactinfo.contact_person_email', 'LIKE', '%' . $searchVal . '%')

                            ->orWhere('user_baf_contactinfo.contact_person_phone', 'LIKE', '%' . $searchVal . '%')

                            ->orWhere('user_baf_practiseinfo.provider_type', '=', $searchVal)

                            ->orWhere('user_baf_businessinfo.seeking_service', 'LIKE', '%' . $searchVal . '%')

                            ->orWhere('user_baf_contactinfo.city', 'LIKE', '%' . $searchVal . '%')

                            ->orWhere('user_baf_contactinfo.state', 'LIKE', '%' . $searchVal . '%')

                            ->orWhere('user_baf_practiseinfo.legal_business_name', 'LIKE', '%' . $searchVal . '%');
                    }

                    $providers = $providers->orderBy("user_baf_practiseinfo.id", "DESC")
                        ->paginate($this->cmperPage);
                    $providersArr = [];
                    foreach ($providers as $provider) {
                        $providersArr[] = ["legal_business_name" => $provider->legal_business_name, "provider_name" => $provider->provider_name, "id" => $provider->id, "user_id" => $provider->user_id, "provider_type" => $provider->provider_type, "contact_person_name" => $provider->contact_person_name, "contact_person_email" => $provider->contact_person_email, "contact_person_phone" => $provider->contact_person_phone, "state" => $provider->state, "deleted" => $provider->deleted, "role_id" => $provider->role_id];
                    }

                    return $this->successResponse(["providers" => $providersArr, "pagination" => $providers], "Success");
                } else if ($whichSection == "directory") {
                    return $this->fetchDirectoryUsers($request);
                }
            }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }

        // $this->printR($providers,true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {


            if ($request->has("section3") && $request->section3  == "contactInfo") {
                $email = $request->contact_person_email;
                $name = $request->contact_person_name;
                $phone = $request->contact_person_phone;
                $password = Str::random(6);

                $userExist = User::where("email", "=", $email)

                    ->count();

                if ($userExist) {
                    return $this->warningResponse(["email" => $email], "Provider already found against this email " . $email, 302);
                } else {
                    $addUser = [
                        "first_name" => $name,
                        "email" => $email,
                        "password" => Hash::make($password),
                        "phone" => $phone,
                        "city" => $request->city,
                        "state" => $request->state,
                        "zip_code" => $request->zip_code,
                        "created_at" => $this->timeStamp()
                    ];

                    $user = User::create($addUser);

                    $user->createToken($name . " Token")->plainTextToken;

                    $userId = $user->id;
                    $practiceInfo = [

                        "user_id" => $userId,

                        "provider_type" => $request->provider_type,

                        "provider_name" => $request->provider_name,

                        "legal_business_name" => $request->legal_business_name,

                        "doing_business_as" => $request->doing_business_as,

                        "number_of_individual_provider" => $request->number_of_individual_provider,

                        // "updated_at" =>$request->updated_at,
                        // "created_at" =>$request->created_at,
                        // "id" =>$request->id,
                    ];

                    $this->addData("user_baf_practiseinfo", $practiceInfo, 0);



                    $businessInfo = [


                        "user_id" => $userId,

                        "business_type" => $request->business_type,

                        "begining_date" => $request->begining_date,

                        "number_of_physical_location" => (int) $request->number_of_physical_location + 1,

                        "avg_patient_day" => $request->avg_patient_day,

                        "practise_managemnt_software" => $request->practise_managemnt_software,

                        "use_pms" => $request->use_pms,

                        "electronic_health_record_software" => $request->electronic_health_record_software,

                        "use_ehr" => $request->use_ehr,

                        "seeking_service" => $request->seeking_service


                        // "updated_at" =>$request->updated_at,
                        // "created_at" =>$request->created_at,
                        // "id" =>$request->id,

                    ];

                    $this->addData("user_baf_businessinfo", $businessInfo, 0);
                    $contactInfo = [

                        "user_id" => $userId,

                        "address" => $request->address,

                        "address_line_one" => $request->address_line_one,

                        "city" => $request->city,

                        "state" => $request->state,

                        "zip_code" => $request->zip_code,

                        "contact_person_name" => $request->contact_person_name,

                        "contact_person_email" => $request->contact_person_email,

                        "contact_person_designation" => $request->contact_person_designation,

                        "contact_person_phone" => $request->contact_person_phone,

                        "has_physical_location" => $request->has_physical_location,

                        "comments" => $request->comments,


                        // "updated_at" =>$request->updated_at,
                        // "created_at" =>$request->created_at,
                        // "id" =>$request->id,
                    ];
                    $this->addData("user_baf_contactinfo", $contactInfo, 0);

                    $roleId = 3;
                    if ($request->provider_type == "solo")
                        $roleId = 2;

                    $role = Role::where("id", "=", $roleId)->first(["id"]);

                    $roleId = $role->id;
                    $roleData = [
                        "user_id" => $userId,
                        "role_id" => $roleId
                    ];

                    $comapny_map = [
                        "user_id" => $userId,
                        "company_id" => 1,
                    ];



                    $this->addData("user_role_map", $roleData, 0);

                    $this->addData("user_company_map", $comapny_map, 0);

                    $companyId                      = $request->has("company_id") ? $request->company_id : 0;

                    $providerType                   = $request->has("provider_type") ? $request->provider_type : "NULL";

                    $legalBusinessName              = $request->has("legal_business_name") ? $request->legal_business_name : "NULL";

                    $businessAs                     = $request->has("business_as") ? $request->business_as : "NULL";

                    $numOfProvider                  = $request->has("num_of_provider") && $request->get("num_of_provider") != "" ? $request->num_of_provider : 0;

                    $businessType                   = $request->has("business_type") ? $request->business_type : "NULL";

                    $numOfBusinessLocations         = $request->has("num_of_physical_locations") && $request->get("num_of_physical_locations") != "" ? $request->num_of_physical_locations : 0;

                    $avgPateintsDay                 = $request->has("avg_pateints_day") &&  $request->get("avg_pateints_day") != ""  ? $request->avg_pateints_day : 0;

                    $seekingService                 = $request->has("seeking_service") ? $request->seeking_service : "NULL";

                    $practiceManageSoftwareName     = $request->has("practice_manage_software_name") ? $request->practice_manage_software_name : "NULL";

                    $usePMS                         = $request->has("use_pms") && $request->get("use_pms") != "" ? $request->use_pms : 0;

                    $electronicHealthRecordSoftware = $request->has("electronic_health_record_software") ? $request->electronic_health_record_software : "NULL";

                    $useEHR                         = $request->has("use_ehr") && $request->get("use_ehr") != "" ? $request->use_ehr : 0;

                    $address                        = $request->has("address") ? $request->address : "NULL";

                    $addressLineOne                    = $request->has("address_line_one") ? $request->address_line_one : "NULL";

                    // $addressLineTwo	                = $request->has("address_line_two") ? $request->address_line_two : "NULL";

                    $contactPersonName                = $request->has("contact_person_name") ? $request->contact_person_name : "NULL";

                    $contactPersonDesignation        = $request->has("contact_person_designation") ? $request->contact_person_designation : "NULL";

                    $contactPersonEmail                = $request->has("contact_person_email") ? $request->contact_person_email : "NULL";

                    $contactPersonPhone                = $request->has("contact_person_phone") ? $request->contact_person_phone : "NULL";

                    $city                            = $request->has("city") ? $request->city : "NULL";

                    $state                            = $request->has("state") ? $request->state : "NULL";

                    $zipCode                        = $request->has("zip_code") ? $request->zip_code : "NULL";

                    $comments                        = $request->has("comments") ? $request->comments : "NULL";

                    $providerName                   = $request->has("provider_name") ? $request->provider_name : "NULL";

                    $beginingDate                   = $request->has("begining_date") ? $request->begining_date : "NULL";

                    $hasPhysicalLocation            = $request->has("has_physical_location") && $request->get("has_physical_location") != "" ? $request->has_physical_location : 0;

                    // $message                        = $request->has("message") ? $request->message : "NULL";

                    $addProviderData = [
                        "provider_type"                         => $providerType,
                        "provider_name"                         => $providerName,
                        "legal_business_name"                   => $legalBusinessName,
                        "business_as"                           => $businessAs,
                        "num_of_provider"                       => $numOfProvider,
                        "business_type"                         => $businessType,
                        "num_of_physical_locations"             => $numOfBusinessLocations,
                        "avg_pateints_day"                      => $avgPateintsDay,
                        "seeking_service"                       => $seekingService,
                        "practice_manage_software_name"         => $practiceManageSoftwareName,
                        "use_pms"                               => $usePMS,
                        "electronic_health_record_software"     => $electronicHealthRecordSoftware,
                        "use_ehr"                               => $useEHR,
                        "address"                               => $address,
                        "address_line_one"                      => $addressLineOne,
                        "contact_person_name"                   => $contactPersonName,
                        "contact_person_designation"            => $contactPersonDesignation,
                        "contact_person_email"                  => $contactPersonEmail,
                        "contact_person_phone"                  => $contactPersonPhone,
                        "city"                                  => $city,
                        "state"                                 => $state,
                        "zip_code"                              => $zipCode,
                        "comments"                              => $comments,
                        "begining_date"                         => $beginingDate,
                        "has_physical_location"                 => $hasPhysicalLocation,
                        "created_at"                            => date("Y-m-d H:i:s")
                    ];
                    try {
                        //Mail::queue(new BAFSubmitted($addProviderData));//send email user
                        Mail::to($addProviderData["contact_person_email"])
                            ->send(new BAFSubmitted($addProviderData));
                        $isSentEmail = 1;
                        $this->addNotification($userId, "new_provider", NULL, "Request for " . $seekingService . " recieved", "Practice request"); //add notification
                    } catch (\Throwable $exception) {
                        $isSentEmail = 0;
                        $msg = $exception->getMessage();
                    }
                    return $this->successResponse(["id" => $userId, 'is_email_sent' => 0, 'msg' => ""], "Provider added successfully.");
                }
            }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {

        $attachments = new UserCommonFunc();
        $stats      = new EnrollmentStats();
        $license    = new LicenseController();
        $insuranceCoverage = "";
        $educationProfessional = null;
        $educationPostGraduate = null;
        $hospitalPrivillagesData = null;
        $specialitySpecialityInfoCnt = [];
        $selectedFacilities = [];
        $professionalSpecialtyInfo = [];
        $userRole = null;
        $locationsInfo = [];
        $encKey = $this->key;
        // try
        {

            if (!$request->has("filter") && !$request->has("profile_id")) {

                // exit("In first iff");
                if ($request->has("provider_type") && ($request->provider_type == "group" || $request->provider_type == "solo")) {


                    $provider = BAF::select([
                        "user_baf_practiseinfo.id", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.user_id", "user_baf_practiseinfo.provider_type", "user_baf_practiseinfo.provider_name",
                        'user_baf_practiseinfo.legal_business_name', 'user_baf_practiseinfo.doing_business_as', 'user_baf_practiseinfo.number_of_individual_provider', 'user_baf_practiseinfo.doing_business_as', "user_baf_contactinfo.address",
                        "user_baf_contactinfo.address_line_one", "user_baf_contactinfo.city", "user_baf_contactinfo.state", "user_baf_contactinfo.zip_code", "user_baf_contactinfo.contact_person_name",
                        "user_baf_contactinfo.contact_person_email", "user_baf_contactinfo.contact_person_designation", "user_baf_contactinfo.contact_person_phone", "user_baf_contactinfo.comments",
                        "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type",
                        "user_baf_businessinfo.business_type", "user_baf_businessinfo.begining_date", "user_baf_businessinfo.number_of_physical_location", "user_baf_businessinfo.avg_patient_day",
                        "user_baf_businessinfo.practise_managemnt_software", "user_baf_businessinfo.use_pms", "user_baf_businessinfo.electronic_health_record_software", "user_baf_businessinfo.use_ehr",
                        "user_baf_businessinfo.seeking_service",  "user_baf_businessinfo.seeking_service", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.updated_at", "users.deleted",
                        "user_dd_businessinformation.facility_npi as NPI", "user_dd_businessinformation.facility_tax_id", "user_dd_businessinformation.fax", "user_dd_businessinformation.group_specialty",
                        "user_baf_contactinfo.has_physical_location"

                    ])

                        ->leftJoin("user_baf_contactinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_contactinfo.user_id")

                        ->leftJoin("user_baf_businessinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_businessinfo.user_id")

                        ->leftJoin("users", "user_baf_practiseinfo.user_id", "=", "users.id")

                        ->leftJoin("user_dd_businessinformation", "user_dd_businessinformation.user_id", "=", "users.id")

                        ->where("user_baf_practiseinfo.user_id", "=", $id)

                        ->first();
                } elseif ($request->provider_type == "location_user") {


                    // echo $request->provider_type;
                    // exit("in iff");
                    $provider = $this->fetchLocationUser($id, $request->provider_type);

                } elseif ($request->provider_type == "Practice") {
                    $provider = $this->fetchLocationUser($id, $request->provider_type);
                } else {
                    // echo "this is the solo provider";
                    // exit;
                    $provider = $this->fetchMemberProfile($id);
                }
                $practiceCols = [
                    "id", "user_id", "user_parent_id", DB::raw("AES_DECRYPT(primary_correspondence_address,'$encKey') as primary_correspondence_address"),
                    DB::raw("AES_DECRYPT(phone,'$encKey') as phone"), DB::raw("AES_DECRYPT(fax,'$encKey') as fax"), DB::raw("AES_DECRYPT(email,'$encKey') as email"),
                    "office_manager_name", DB::raw("AES_DECRYPT(doing_buisness_as,'$encKey') as doing_buisness_as"), DB::raw("AES_DECRYPT(npi,'$encKey') as npi"),
                    DB::raw("AES_DECRYPT(practise_address,'$encKey') as practise_address"),
                    DB::raw("AES_DECRYPT(practise_address1,'$encKey') as practise_address1"),
                    DB::raw("AES_DECRYPT(practise_phone,'$encKey') as practise_phone"),
                    DB::raw("AES_DECRYPT(practise_fax,'$encKey') as practise_fax"),
                    DB::raw("AES_DECRYPT(practise_email,'$encKey') as practise_email"),
                    DB::raw("AES_DECRYPT(practice_name,'$encKey') as practice_name"),
                    DB::raw("AES_DECRYPT(tax_id,'$encKey') as tax_id"), "monday_from", "tuesday_from", "wednesday_from", "thursday_from", "friday_from", "saturday_from", "sunday_from",
                    "monday_to", "tuesday_to", "wednesday_to", "thursday_to", "friday_to", "saturday_to", "sunday_to", "location_summary"
                ];
                $practiceLocation = PracticeLocation::where("user_id", "=", $id)

                    ->orWhere("user_parent_id", "=", $id)

                    ->first($practiceCols);
                // $this->printR($practiceLocation,true);
                $request->merge(["entity" => "provider_id", "entity_id" => $id, "user_id" => $id]);
                $allAttachments = json_decode($attachments->fetchAttachments($request)->getContent(), true);
                $allStats = json_decode($stats->enrollmentStats($request)->getContent(), true);
                $userRole = $this->userRole($id);
                $allStatsArr = $allStats["data"];
                $allAttachmentsCount = $allAttachments["data"]["document_count"];
                $documentStats = ["document_count" => $allAttachmentsCount, "mssing_count" => "0", "expiring_count" => "0", "expired_count" => "0", "active_count" => "0"];
                $exclusionStats = ["exclusion_count" => "5", "notverified_count" => "0", "scheduled_count" => "0", "potential_occurancefound_count" => "0", "verified_count" => "0"];
                $verificationStats = ["verifications_count" => "10", "scheduled_count" => "0", "inprogress_count" => "0", "verified_count" => "0", "not_verified_count" => 0];
                $userComponts       = $this->fetchUserComponent($request->route_id, $request->role_id);
                // $this->printR($userComponts,true);
                // $this->printR($allStats,true);
                return $this->successResponse([
                    "provider" => $provider, "documents" => $documentStats, "exclusions" =>
                    $exclusionStats, "verifications" => $verificationStats, "dashboard" => $allStatsArr,
                    "practice_location" => $practiceLocation, "user_role" => $userRole,
                    "allowed_components" => $userComponts
                ], "Success");
            } else if ($request->has("profile_id")) {

                $type = $request->type;
                // $provider = BAF::select([
                //     "user_baf_practiseinfo.id", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.user_id", "user_baf_practiseinfo.provider_type", "user_baf_practiseinfo.provider_name",
                //     'user_baf_practiseinfo.legal_business_name', 'user_baf_practiseinfo.doing_business_as', 'user_baf_practiseinfo.number_of_individual_provider', "user_baf_contactinfo.address",
                //     "user_baf_contactinfo.address_line_one", "user_baf_contactinfo.city", "user_baf_contactinfo.state", "user_baf_contactinfo.zip_code", "user_baf_contactinfo.contact_person_name",
                //     "user_baf_contactinfo.contact_person_email", "user_baf_contactinfo.contact_person_designation", "user_baf_contactinfo.contact_person_phone", "user_baf_contactinfo.comments",
                //     "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type",
                //     "user_baf_businessinfo.business_type", "user_baf_businessinfo.begining_date", "user_baf_businessinfo.number_of_physical_location", "user_baf_businessinfo.avg_patient_day",
                //     "user_baf_businessinfo.practise_managemnt_software", "user_baf_businessinfo.use_pms", "user_baf_businessinfo.electronic_health_record_software", "user_baf_businessinfo.use_ehr",
                //     "user_baf_businessinfo.seeking_service",  "user_baf_businessinfo.seeking_service", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.updated_at", "users.deleted",
                //     "user_dd_businessinformation.facility_npi as NPI", "user_dd_businessinformation.facility_tax_id", "w9form.social_security_number", "user_dd_businessinformation.fax",
                //     "user_dd_businessinformation.group_specialty", "user_dd_businessinformation.federal_tax_classification"
                // ])

                //     ->leftJoin("user_baf_contactinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_contactinfo.user_id")

                //     ->leftJoin("user_baf_businessinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_businessinfo.user_id")

                //     ->leftJoin("users", "user_baf_practiseinfo.user_id", "=", "users.id")

                //     ->leftJoin("user_dd_businessinformation", "user_dd_businessinformation.user_id", "=", "users.id")

                //     //->leftJoin("user_ddpracticelocationinfo","user_ddpracticelocationinfo.user_id","=","users.id")

                //     ->leftJoin("w9form", "users.id", "=", "w9form.user_id")

                //     ->where("user_baf_practiseinfo.user_id", "=", $request->profile_id)

                //     ->first();

                // if (is_object($provider)) {
                //     $type = $provider->provider_type;
                //     try {
                //         $breakName = explode(" ", $provider->practice_name);
                //         $makeShortName = "";
                //         if (is_array($breakName)) {
                //             foreach ($breakName as $key => $name) {
                //                 // if(count($breakName) - 1 == $key)
                //                 $makeShortName .= substr($name, 0, 1);
                //                 // else
                //                 //     $makeShortName.= substr($name,0,1).'_';
                //             }
                //         }
                //         $provider->short_name = $makeShortName;
                //     } catch (\Exception $e) {
                //         $provider->short_name = "";
                //     }

                // }
                // // $providerMember = "";
                $selfData = "";
                $parentId = "";
                $locationUserId = $request->profile_id;
                $provider = null;
                //if (!is_object($provider))
                // {


                // $selfData =  $this->fetchData("user_dd_individualproviderinfo",["user_id" => $request->profile_id],1);
                // $selfData = $this->fetchIndividualProvider($request->profile_id);
                // if (!is_object($selfData)) {
                //     // exit(1);
                //     $locationUser = $this->fetchData("user_ddpracticelocationinfo", ["user_id" => $request->profile_id], 1);
                //     $parentId = $locationUser->user_id;
                //     //$type = "location_user";
                //     $selfData = "";
                // } else
                if ($type == "member" || $type == "owner") {

                    $provider = $selfData =  $this->fetchIndividualProvider($request->profile_id);

                    $educationProfessional = $this->fetchEducation($request->profile_id, "professional");
                    $educationPostGraduate = $this->fetchEducation($request->profile_id, "post_graduate");
                    $hospitalPrivillagesData = $this->fetchData("hospital_affiliations", ["user_id" => $request->profile_id], 1, []);
                    if (is_object($hospitalPrivillagesData) && $this->isValidDate($hospitalPrivillagesData->start_date))
                        $hospitalPrivillagesData->start_date = date("Y-m-d", strtotime($hospitalPrivillagesData->start_date));

                    // $allEduData = $this->fetchEducation($request->profile_id,"all");
                    $specialitySpecialityInfoTotl = count($educationPostGraduate);
                    if ($specialitySpecialityInfoTotl > 0) {
                        for ($i = 1; $i <= $specialitySpecialityInfoTotl; $i++) {
                            array_push($specialitySpecialityInfoCnt, $i);
                        }
                        $allEduDataArr = $this->stdToArray($educationPostGraduate);
                        $faciltyIds = array_column($allEduDataArr, "facility_id");
                        $selectedFacilities = $this->fetchSpecificFacilties($faciltyIds);
                    }
                    $professionalSpecialtyInfo = $this->fetchData("specialty_information", ["user_id" => $request->profile_id], 0, []);
                    if (count($professionalSpecialtyInfo) > 0) {
                        foreach ($professionalSpecialtyInfo as $professionalSpecialty) {
                            if ($this->isValidDate($professionalSpecialty->expiration_date))
                                $professionalSpecialty->expiration_date = date("Y-m-d", strtotime($professionalSpecialty->expiration_date));
                            if ($this->isValidDate($professionalSpecialty->certification_date))
                                $professionalSpecialty->certification_date = date("Y-m-d", strtotime($professionalSpecialty->certification_date));
                            if ($this->isValidDate($professionalSpecialty->recertification_date))
                                $professionalSpecialty->recertification_date = date("Y-m-d", strtotime($professionalSpecialty->recertification_date));
                        }
                    }

                    // $provider = BAF::select([
                    //     "user_baf_practiseinfo.id", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.user_id", "user_baf_practiseinfo.provider_type", "user_baf_practiseinfo.provider_name",
                    //     'user_baf_practiseinfo.legal_business_name', 'user_baf_practiseinfo.doing_business_as', 'user_baf_practiseinfo.number_of_individual_provider', "user_baf_contactinfo.address",
                    //     "user_baf_contactinfo.address_line_one", "user_baf_contactinfo.city", "user_baf_contactinfo.state", "user_baf_contactinfo.zip_code", "user_baf_contactinfo.contact_person_name",
                    //     "user_baf_contactinfo.contact_person_email", "user_baf_contactinfo.contact_person_designation", "user_baf_contactinfo.contact_person_phone", "user_baf_contactinfo.comments",
                    //     "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type",
                    //     "user_baf_businessinfo.business_type", "user_baf_businessinfo.begining_date", "user_baf_businessinfo.number_of_physical_location", "user_baf_businessinfo.avg_patient_day",
                    //     "user_baf_businessinfo.practise_managemnt_software", "user_baf_businessinfo.use_pms", "user_baf_businessinfo.electronic_health_record_software", "user_baf_businessinfo.use_ehr",
                    //     "user_baf_businessinfo.seeking_service",  "user_baf_businessinfo.seeking_service", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.updated_at", "users.deleted",
                    //     "user_dd_businessinformation.facility_npi as NPI", "user_dd_businessinformation.facility_tax_id", "w9form.social_security_number", "user_dd_businessinformation.fax", "user_dd_businessinformation.group_specialty",
                    //     "user_dd_businessinformation.federal_tax_classification"
                    // ])

                    //     ->leftJoin("user_baf_contactinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_contactinfo.user_id")

                    //     ->leftJoin("user_baf_businessinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_businessinfo.user_id")

                    //     ->leftJoin("users", "user_baf_practiseinfo.user_id", "=", "users.id")

                    //     ->leftJoin("user_dd_businessinformation", "user_dd_businessinformation.user_id", "=", "users.id")

                    //     //->leftJoin("user_ddpracticelocationinfo","user_ddpracticelocationinfo.user_id","=","users.id")

                    //     ->leftJoin("w9form", "users.id", "=", "w9form.user_id")

                    //     ->where("user_baf_practiseinfo.user_id", "=", $selfData->parent_user_id)

                    //     ->first();
                    // if($this->isValidDate($selfData->dob))
                    // try {
                    //     $selfData->dob = date("Y-m-d", strtotime($selfData->dob));
                    // } catch (\Exception $e) {
                    // }
                    //$type = "solo";
                }
                // }
                // echo $locationUserId;
                // exit;

                // if ($type == "location_user") {
                //     $request->merge(["user_id" => $parentId]);
                //     $userId = $parentId;
                //     $locationsInfo      = $this->fetchParentAfflietedLocations($userId);
                //     $locationAddress    = $this->fetchLocationAddress($request->profile_id);
                //     $provider           = $this->fetchLocationUser($request->profile_id,"location_user");
                //     try {
                //         $breakName = explode(" ", $provider->practice_name);
                //         $makeShortName = "";
                //         if (is_array($breakName)) {
                //             foreach ($breakName as $key => $name) {
                //                 // if(count($breakName) - 1 == $key)
                //                 $makeShortName .= substr($name, 0, 1);
                //                 // else
                //                 //     $makeShortName.= substr($name,0,1).'_';
                //             }
                //         }
                //         $provider->short_name = $makeShortName;
                //     } catch (\Exception $e) {
                //         $provider->short_name = "";
                //     }
                // } else

                if ($type == "location_user") {

                    $request->merge(["user_id" => $request->profile_id]);
                    $userId = $request->profile_id;
                    $locationsInfo      = $this->fetchParentAfflietedLocations($userId);
                    $locationAddress    = $this->fetchLocationAddress($userId);
                    $provider           = $this->fetchLocationUser($userId, "location_user");
                    try {
                        $breakName = explode(" ", $provider->practice_name);
                        $makeShortName = "";
                        if (is_array($breakName)) {
                            foreach ($breakName as $key => $name) {
                                // if(count($breakName) - 1 == $key)
                                $makeShortName .= substr($name, 0, 1);
                                // else
                                //     $makeShortName.= substr($name,0,1).'_';
                            }
                        }
                        $provider->short_name = $makeShortName;
                    } catch (\Exception $e) {
                        $provider->short_name = "";
                    }
                } else {
                    if ($type == "group") {
                        $request->merge(["user_id" => $request->profile_id]);
                        $userId = $request->profile_id;
                        //$locationsInfo      = $this->fetchParentAfflietedLocations($userId);
                        $locationAddress    = $this->fetchLocationAddress($userId);

                        $provider           = $this->fetchLocationUser($userId, "Practice");

                        try {
                            $breakName = explode(" ", $provider->practice_name);
                            $makeShortName = "";
                            if (is_array($breakName)) {
                                foreach ($breakName as $key => $name) {
                                    // if(count($breakName) - 1 == $key)
                                    $makeShortName .= substr($name, 0, 1);
                                    // else
                                    //     $makeShortName.= substr($name,0,1).'_';
                                }
                            }
                            $provider->short_name = $makeShortName;
                        } catch (\Exception $e) {
                            $provider->short_name = "";
                        }
                    } else {

                        $request->merge(["user_id" => $request->profile_id]);

                        $userId = $request->profile_id;
                        // if($type == "owner")
                        //     $locationsInfo      = $this->ownerAfflietedLocations($userId);
                        // else
                        //     $locationsInfo      = $this->fetchAfflietedLocations($userId);

                        $locationAddress    = $this->fetchLocationAddress($userId);
                    }
                }


                $licenses = json_decode($license->index($request)->getContent(), true);
                $licenses = $licenses["data"]["specific_licenses"];
                $licenseData = [];
                if (count($licenses)) {
                    foreach ($licenses as $license) {
                        try {
                            $license['issue_date'] = date("Y-m-d", strtotime($license['issue_date']));
                        } catch (\Exception $e) {
                        }
                        try {
                            $license['exp_date'] = date("Y-m-d", strtotime($license['exp_date']));
                        } catch (\Exception $e) {
                        }
                        array_push($licenseData, $license);
                    }
                }
                $identifiers = [];
                // if ($type == "group") {

                //     $portalObj = new Portal();
                //     $identifiers = $portalObj->fetchUserIdentifiers($userId);
                //     if (count($identifiers)) {
                //         foreach ($identifiers as $identifier) {
                //             if ($this->isValidDate($identifier->effective_date))
                //                 $identifier->effective_date = date("Y-m-d", strtotime($identifier->effective_date));
                //         }
                //     }
                // } elseif ($type == "solo") {
                //     $portalObj = new Portal();
                //     $identifiers = $portalObj->fetchUserIdentifiers($userId);
                //     if (count($identifiers)) {
                //         foreach ($identifiers as $identifier) {
                //             if ($this->isValidDate($identifier->effective_date))
                //                 $identifier->effective_date = date("Y-m-d", strtotime($identifier->effective_date));
                //         }
                //     }
                // }
                // else
                {
                    $portalObj = new Portal();
                    $identifiers = $portalObj->fetchUserIdentifiers($userId);
                    if (count($identifiers)) {
                        foreach ($identifiers as $identifier) {
                            if ($this->isValidDate($identifier->effective_date))
                                $identifier->effective_date = date("Y-m-d", strtotime($identifier->effective_date));
                        }
                    }
                }
                $where  = [];
                if ($type == "solo" || $type == "owner")
                    $where = ["type" => "solo"];
                else
                    $where = ["type" => "group"];

                $facilityData       = $this->fetchData("facilities", $where);

                $bankingInfo        = $this->bankingInformation($userId);

                $ownedLocations     = $this->fetchOwnedLocations($userId);

                $crpAddress         = $this->fetchCorrespondenceAddress($userId);


                if ($type == "location_user") {
                    // exit("in idd");
                    $affiliatedContacts = $this->fetchAfflietedContacts($userId, $type, $locationUserId);
                    // $this->printR($affiliatedContacts,true);
                } elseif ($type == "group") {
                    $affiliatedContacts =  $this->fetchPracticeAfflietedContacts($userId);
                } else
                    $affiliatedContacts = $this->fetchAfflietedContacts($userId);

                // exit("here");
                $opertionSchedule   = $this->fetchData($this->tbl, ["user_id" => $userId], 1, ["monday_from", "tuesday_from", "wednesday_from", "thursday_from", "friday_from", "saturday_from", "sunday_from", "monday_to", "tuesday_to", "wednesday_to", "thursday_to", "friday_to", "saturday_to", "sunday_to"]);
                $ownerShipInfo      = $this->fetchOwnerInfo($userId);
                $citizenships       = $this->fetchData("citizenships", "");
                $citizenshipsRes = [];
                foreach ($citizenships as $citizenship) {
                    $citizenshipsRes[] = ["value" => $citizenship->id, "label" => $citizenship->name];
                }

                $insuranceCoverageObj = new InsuranceCoverage();
                $insuranceCoverage = $insuranceCoverageObj->fetchInsuranceCoverage($userId);

                $filterFaciltyData = [];
                foreach ($facilityData as $faclity) {
                    array_push($filterFaciltyData, ["value" => $faclity->facility, "label" => $faclity->facility]);
                }
                // $this->printR($identifiers,true);
                $resData = [
                    "provider" => $provider, "self_data" => $selfData,
                    "specific_licenses" => $licenseData, "facilities" => $filterFaciltyData,
                    "banking_info" => $bankingInfo, "locations_info" => $locationsInfo,
                    "identifiers" => $identifiers, "opertion_schedule" => $opertionSchedule,
                    "ownership_info" => $ownerShipInfo,
                    "affiliated_contacts" => $affiliatedContacts,
                    "insurance_coverage" => $insuranceCoverage,
                    "education_professional" => $educationProfessional,
                    "education_postgraduate" => $educationPostGraduate,
                    "hospital_privillages" => $hospitalPrivillagesData,
                    "speciality_info_count" => $specialitySpecialityInfoCnt,
                    "selected_facilities" => $selectedFacilities,
                    "professional_specialty_info" => $professionalSpecialtyInfo,
                    "citizenships" => $citizenshipsRes,
                    "owned_locations"   => $ownedLocations,
                    "correspondence_address" => $crpAddress,
                    "location_address" => $locationAddress

                ];
                // $this->printR($resData,true);
                return $this->successResponse($resData, "Success");
            } else {
                return $this->filterProviderData($request);
            }
        }
        // catch (\Throwable $exception) {
        //     //throw $th;
        //     return $this->errorResponse([],$exception->getMessage(),500);
        // }
    }
    /**
     * get the filter data
     *
     * @return \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    private function filterProviderData(Request $request)
    {

        try {



            $searchCol = $request->search_col;

            $searchVal = $request->search_val;
            // if(!$request->has("search_area"))
            {

                $providers = User::select($this->providerManagementCols)
                    ->join("user_role_map", function ($join) {
                        $join->on("user_role_map.user_id", "=", "users.id")
                            ->whereIn("role_id", [2, 3]);
                    })
                    ->join("user_baf_contactinfo", "user_baf_contactinfo.user_id", "=", "users.id")

                    ->join("user_baf_businessinfo", "user_baf_businessinfo.user_id", "=", "users.id")

                    ->join("user_baf_practiseinfo", "user_baf_practiseinfo.user_id", "=", "users.id");

                //->where("users.deleted","=",0);

                if ($searchVal != "" && strlen($searchVal) > 0) {
                    $providers = $providers->whereDate('user_baf_practiseinfo.created_at', "=", $searchVal)

                        ->orWhere('user_baf_contactinfo.contact_person_name', 'LIKE', '%' . $searchVal . '%')

                        ->orWhere('user_baf_contactinfo.contact_person_email', 'LIKE', '%' . $searchVal . '%')

                        ->orWhere('user_baf_contactinfo.contact_person_phone', 'LIKE', '%' . $searchVal . '%')

                        ->orWhere('user_baf_practiseinfo.provider_type', '=', $searchVal)

                        ->orWhere('user_baf_businessinfo.seeking_service', 'LIKE', '%' . $searchVal . '%')

                        ->orWhere('user_baf_contactinfo.city', 'LIKE', '%' . $searchVal . '%')

                        ->orWhere('user_baf_contactinfo.state', 'LIKE', '%' . $searchVal . '%')

                        ->orWhere('user_baf_practiseinfo.legal_business_name', 'LIKE', '%' . $searchVal . '%');
                }

                // $providers2 = $providers;
                $providers = $providers->orderBy("user_baf_practiseinfo.id", "DESC")
                    ->paginate($this->cmperPage);

                $providersArr = [];
                foreach ($providers as $provider) {
                    $providersArr[] = ["legal_business_name" => $provider->legal_business_name, "provider_name" => $provider->provider_name, "id" => $provider->id, "user_id" => $provider->user_id, "provider_type" => $provider->provider_type, "contact_person_name" => $provider->contact_person_name, "contact_person_email" => $provider->contact_person_email, "contact_person_phone" => $provider->contact_person_phone, "state" => $provider->state, "deleted" => $provider->deleted, "role_id" => $provider->role_id];
                }
                // $sql = $providers2->orderBy("user_baf_practiseinfo.id","DESC")->toSql();
                return $this->successResponse(["providers" => $providersArr, "pagination" => $providers], "Success");
            }
            // else {

            //     $providers = BAF::select(["user_baf_practiseinfo.id","user_baf_practiseinfo.user_id","user_baf_practiseinfo.provider_type","user_baf_contactinfo.contact_person_name",
            //     "user_baf_contactinfo.contact_person_email","user_baf_contactinfo.contact_person_phone","user_baf_contactinfo.state","users.deleted"
            //     ])

            //     ->leftJoin("user_baf_contactinfo","user_baf_practiseinfo.user_id","=","user_baf_contactinfo.user_id")

            //     ->leftJoin("user_baf_businessinfo","user_baf_practiseinfo.user_id","=","user_baf_businessinfo.user_id")

            //     ->leftJoin("users","user_baf_practiseinfo.user_id","=","users.id")

            //     ->where("users.deleted","=","0");

            //     if(($request->has("directory_status") && $request->directory_status !="All") && ($request->has("directory_group") && $request->directory_group !="All")) {
            //         $status = $request->directory_status == "Active" ? 0 : 1;
            //         $type = $request->directory_group == "Individual" ? "solo" : "group";
            //         $providers = $providers->where('users.deleted',"=", $status)->where("user_baf_practiseinfo.provider_type","=",$type);
            //     }
            //     elseif($request->has("directory_status") && $request->directory_status !="All") {
            //         $status = $request->directory_status == "Active" ? 0 : 1;
            //         $providers = $providers->where('users.deleted',"=", $status);
            //     }
            //     elseif($request->has("directory_group") && $request->directory_group !="All") {
            //         $type = $request->directory_group == "Individual" ? "solo" : "group";
            //         $providers = $providers->where("user_baf_practiseinfo.provider_type","=",$type);
            //     }
            //     elseif($request->directory_status =="All" && $request->directory_group !="All") {
            //         // $status = $request->directory_status == "Onboard" ? 0 : 1;
            //         $type = $request->directory_group == "Individual" ? "solo" : "group";
            //         $providers = $providers->where("user_baf_practiseinfo.provider_type","=",$type);
            //     }
            //     elseif($request->directory_status !="All" && $request->directory_group =="All") {
            //         $status = $request->directory_status == "Active" ? 0 : 1;
            //         // $type = $request->directory_group == "Individual" ? "solo" : "group";
            //         $providers = $providers->where('users.deleted',"=", $status);
            //     }
            //     $providers = $providers->orderBy("user_baf_practiseinfo.id","DESC")
            //     ->paginate($this->cmperPage);

            //     $providersArr = [];
            //     foreach($providers as $provider) {
            //         $providersArr[] = ["id"=>$provider->id,"user_id" => $provider->user_id,"provider_type" => $provider->provider_type,"contact_person_name" => $provider->contact_person_name,"contact_person_email" => $provider->contact_person_email,"contact_person_phone" => $provider->contact_person_phone,"state" => $provider->state ,"deleted" => $provider->deleted];
            //     }

            //     return $this->successResponse(["providers" => $providersArr,"pagination" => $providers],"Success");
            // }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
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

        try {

            $updateData = $request->all();
            if ($updateData["step"] == 1) {
                $table = "user_baf_practiseinfo";
                $data = [
                    "doing_business_as"     => $updateData["business_as"],
                    "legal_business_name"   =>  $updateData["legal_business_name"],
                    "number_of_individual_provider"       => $updateData["num_of_provider"],
                    "provider_name"         =>  $updateData["provider_name"],
                    "provider_type"         => $updateData["provider_type"]
                ];
                $isUpdate = $this->updateData($table, ["user_id" => $id], $data);
            } elseif ($updateData["step"] == 2) {
                if ($updateData["business_type"] == "startup") {
                    $noOfPyhLoc = $updateData["num_of_physical_locations"];
                    $data = [
                        "begining_date"     => $updateData["begining_date"],
                        "business_type"   =>  $updateData["business_type"],
                        "seeking_service"       => $updateData["seeking_service"],
                        "number_of_physical_location"     => $noOfPyhLoc
                    ];

                    $this->updateData("user_baf_contactinfo", ["user_id" => $id], ["has_physical_location" => $updateData["has_physical_location"]]);
                    $isUpdate = $this->updateData("user_baf_businessinfo", ["user_id" => $id], $data);
                } elseif ($updateData["business_type"] == "established") {
                    $noOfPyhLoc = $updateData["num_of_physical_locations"];
                    $noOfPyhLoc = $updateData["num_of_physical_locations"];
                    if ($updateData["has_physical_location"] == "no") {
                        $data_["number_of_physical_location"] = 1;
                        $this->updateData("user_baf_businessinfo", ["user_id" => $id], $data_);
                    } else {
                        $data_["number_of_physical_location"] = $noOfPyhLoc;
                        $this->updateData("user_baf_businessinfo", ["user_id" => $id], $data_);
                    }
                    $this->updateData("user_baf_contactinfo", ["user_id" => $id], ["has_physical_location" => $updateData["has_physical_location"]]);

                    $data = [
                        "number_of_physical_location"     => $noOfPyhLoc,
                        "avg_patient_day"   =>  $updateData["avg_pateints_day"],
                        "seeking_service"       => $updateData["seeking_service"],
                        "use_ehr"       => $updateData["use_ehr"],
                        "use_pms"       => $updateData["use_pms"],
                        "business_type"   =>  $updateData["business_type"],
                        "practise_managemnt_software"       => $updateData["practice_manage_software_name"],
                        "electronic_health_record_software"       => $updateData["electronic_health_record_software"],
                    ];
                    $isUpdate = $this->updateData("user_baf_businessinfo", ["user_id" => $id], $data);
                }
            } else if ($updateData["step"] == 3) {
                $data = [
                    "has_physical_location"     => $updateData["has_physical_location"],
                    "address"                   =>  $updateData["address"],
                    "address_line_one"          => $updateData["address_line_one"],
                    "state"                     => $updateData["state"],
                    "city"                      => $updateData["city"],
                    "zip_code"                  => $updateData["zip_code"],
                    "contact_person_name"       =>  $updateData["contact_person_name"],
                    "contact_person_email"       => $updateData["contact_person_email"],
                    "contact_person_designation" => $updateData["contact_person_designation"],
                    "contact_person_phone"       => $updateData["contact_person_phone"],
                    "comments"                   => $updateData["comments"],
                ];

                $isUpdate = $this->updateData("user_baf_contactinfo", ["user_id" => $id], $data);
            }
            // $isUpdate = Provider::find($id)->update($updateData);

            return $this->successResponse(['id' => $id, 'is_update' => $isUpdate], "Success");
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
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
     * fetch the provider user
     *
     * @param $userId
     * @return $result
     */
    public function fetchProviderUser($userId)
    {

        $provider = BAF::select([
            "user_baf_practiseinfo.id", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.user_id", "user_baf_practiseinfo.provider_type", "user_baf_practiseinfo.provider_name",
            'user_baf_practiseinfo.legal_business_name', 'user_baf_practiseinfo.doing_business_as', 'user_baf_practiseinfo.number_of_individual_provider', "user_baf_contactinfo.address",
            "user_baf_contactinfo.address_line_one", "user_baf_contactinfo.city", "user_baf_contactinfo.state", "user_baf_contactinfo.zip_code", "user_baf_contactinfo.contact_person_name",
            "user_baf_contactinfo.contact_person_email", "user_baf_contactinfo.contact_person_designation", "user_baf_contactinfo.contact_person_phone", "user_baf_contactinfo.comments",
            "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type",
            "user_baf_businessinfo.business_type", "user_baf_businessinfo.begining_date", "user_baf_businessinfo.number_of_physical_location", "user_baf_businessinfo.avg_patient_day",
            "user_baf_businessinfo.practise_managemnt_software", "user_baf_businessinfo.use_pms", "user_baf_businessinfo.electronic_health_record_software", "user_baf_businessinfo.use_ehr",
            "user_baf_businessinfo.seeking_service",  "user_baf_businessinfo.seeking_service", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.updated_at", "users.deleted",
            "user_baf_contactinfo.has_physical_location"
        ])

            ->leftJoin("user_baf_contactinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_contactinfo.user_id")

            ->leftJoin("user_baf_businessinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_businessinfo.user_id")

            ->leftJoin("users", "user_baf_practiseinfo.user_id", "=", "users.id")

            ->where("user_baf_practiseinfo.user_id", "=", $userId)

            ->first();
        return $provider;
    }
    /**
     * fetch directory users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchDirectoryUsersOld(Request $request)
    {
        // exit("hi");
        $whichType = "";
        if ($request->has("filter") && $request->filter == true && ($request->status != "All" && $request->status != "") && ($request->col == "type" &&  $request->val == "")) {

            $status = $request->status == "Active" ? "0" : "1";
            $users = User::select($this->usersCols)
                ->where("deleted", "=", $request->status)
                ->paginate($this->cmperPage);
            // $allRec = User::select(["id","email","deleted"])
            // ->where("deleted","=",$request->val)
            // ->count();
        } elseif ($request->has("filter") && $request->filter == true && $request->col == "type") {
            // exit("in this iff");
            $whichType = $request->val == "Practice" ? "Group" : $request->val;
            if ($request->val == "Practice") {
                if ($request->has("status") && $request->status != "" && $request->status != "All") {
                    $status = $request->status == "Active" ? "0" : "1";
                    $userIds = BAF::select("user_id")
                        ->get();

                    $userIdsArr = $this->stdToArray($userIds);
                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        ->where("deleted", "=", $status)
                        ->paginate($this->cmperPage);
                } else {

                    $userIds = BAF::select("user_id")
                        ->get();

                    $userIdsArr = $this->stdToArray($userIds);
                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        ->paginate($this->cmperPage);
                }

                // $allRec = User::select(["id","email","deleted"])
                // ->whereIn("id",$ids)
                // ->count();
            }
            if ($request->val == "Individual") {
                if ($request->has("status") && $request->status != "" && $request->status != "All") {
                    $status = $request->status == "Active" ? "0" : "1";
                    $sql = "SELECT user_id FROM `cm_user_dd_individualproviderinfo` GROUP BY user_id;";
                    $idsI = $this->rawQuery($sql);

                    // $idsI = $idsI[0];
                    $userIdsArr = $this->stdToArray($idsI);

                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        ->where("deleted", "=", $status)
                        ->paginate($this->cmperPage);
                } else {
                    $sql = "SELECT user_id FROM `cm_user_dd_individualproviderinfo` GROUP BY user_id;";
                    $idsI = $this->rawQuery($sql);

                    // $idsI = $idsI[0];
                    $userIdsArr = $this->stdToArray($idsI);

                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // ->count();
                }
            }
            if ($request->val == "All" || $request->val == "") {

                if ($request->has("status") && $request->status != "" && $request->status != "All") {
                    $status = $request->status == "Active" ? "0" : "1";
                    // $sql = "SELECT user_id FROM `cm_user_dd_individualproviderinfo` GROUP BY user_id;";
                    // $idsI = $this->rawQuery($sql);

                    // // $idsI = $idsI[0];
                    // $userIdsArr = $this->stdToArray($idsI);

                    // $ids   = array_column($userIdsArr,"user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        // ->whereIn("id",$ids)
                        ->where("deleted", "=", $status)
                        ->paginate($this->cmperPage);
                } else {
                    // $sql = "SELECT user_id FROM `cm_user_dd_individualproviderinfo` GROUP BY user_id;";
                    // $idsI = $this->rawQuery($sql);

                    // // $idsI = $idsI[0];
                    // $userIdsArr = $this->stdToArray($idsI);

                    // $ids   = array_column($userIdsArr,"user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        // ->whereIn("id",$ids)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // ->count();
                }
            }
        } elseif ($request->has("filter") && $request->filter == true && $request->col == "smart_search" && $request->val != "") {
            $searchKeyWord = $request->val;
            $indvidualFilterData = [];
            $bpFilterData = [];
            $ciFilterData = [];
            $sql = "SELECT user_id FROM `cm_users` WHERE  email LIKE '%" . $searchKeyWord . "%' OR first_name LIKE '%" . $searchKeyWord . "%' OR last_name LIKE '%" . $searchKeyWord . "%' OR phone LIKE '%" . $searchKeyWord . "%' OR state_of_birth LIKE '%" . $searchKeyWord . "%' GROUP BY user_id";
            $idsI = $this->rawQuery($sql);
            if (count($idsI)) {
                $userIdsArr = $this->stdToArray($idsI);
                $indvidualFilterData   = array_column($userIdsArr, "user_id");
                // $this->printR($indvidualFilterData,true);
            }
            $sql2 = "SELECT user_id FROM `cm_user_baf_practiseinfo` WHERE  legal_business_name LIKE '%" . $searchKeyWord . "%'";
            $idsbp = $this->rawQuery($sql2);
            if (count($idsbp)) {
                $userIdsArr_ = $this->stdToArray($idsbp);
                $bpFilterData   = array_column($userIdsArr_, "user_id");
                // $this->printR($bpFilterData,true);
            }
            $sql3 = "SELECT user_id FROM `cm_user_baf_contactinfo` WHERE  state LIKE '%" . $searchKeyWord . "%' OR contact_person_name LIKE '%" . $searchKeyWord . "%' OR contact_person_email LIKE '%" . $searchKeyWord . "%' OR contact_person_phone LIKE '%" . $searchKeyWord . "%'";
            $idsci = $this->rawQuery($sql3);
            if (count($idsci)) {
                $userIdsArr_ = $this->stdToArray($idsci);
                $ciFilterData   = array_column($userIdsArr_, "user_id");
                // $this->printR($ciFilterData,true);
            }

            $finalFilterData = array_merge($indvidualFilterData, $bpFilterData, $ciFilterData);
            // $this->printR($finalFilterData,true);
            // $idsI = $idsI[0];

            $users = User::select($this->usersCols)
                ->whereIn("id", $finalFilterData)
                ->paginate($this->cmperPage);
            // $this->printR($users,true);
            // $allRec = User::select(["id","email","deleted"])
            // ->whereIn("id",$finalFilterData)
            // ->count();

        } else {
            // exit("in else");
            // $this->printR($request->all());
            if ($request->has("page") && ($request->has("status") && $request->status != "" && $request->status != "All") && ($request->has("type") &&  $request->type != "" && $request->type != "All")) {
                // exit("Here in iff 3");
                $status = $request->status == "Active" ? 0 : 1;

                $whichType = $request->type == "Practice" ? "Group" :  $request->type;
                if ($request->type == "Practice") {
                    $userIds = BAF::select("user_id")
                        ->get();

                    $userIdsArr = $this->stdToArray($userIds);
                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        ->where("deleted", "=", $status)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // ->where("deleted","=",$status)
                    // ->count();
                }
                if ($request->type == "Individual") {
                    $sql = "SELECT user_id FROM `cm_user_dd_individualproviderinfo` GROUP BY user_id;";
                    $idsI = $this->rawQuery($sql);

                    // $idsI = $idsI[0];
                    $userIdsArr = $this->stdToArray($idsI);

                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        ->where("deleted", "=", $status)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // ->where("deleted","=",$status)
                    // ->count();
                }
            } elseif ($request->has("page") && ($request->has("status") && $request->status != "" && $request->status != "All") && ($request->has("type") &&  $request->type == "")) {
                // exit("Here in iff 2");
                $status = $request->status == "Active" ? 0 : 1;
                // $whichType = $request->type;

                $userIds = BAF::select("user_id")
                    ->get();

                $userIdsArr = $this->stdToArray($userIds);
                $ids   = array_column($userIdsArr, "user_id");
                // $this->printR($ids,true);
                $users = User::select($this->usersCols)
                    ->whereIn("id", $ids)
                    ->where("deleted", "=", $status)
                    ->paginate($this->cmperPage);
                // $allRec = User::select(["id","email","deleted"])
                // ->whereIn("id",$ids)
                // ->where("deleted","=",$status)
                // ->count();

                // $users = User::select(["id","email","deleted"])
                // ->where("deleted","=",$status)
                // ->paginate(20);
            } elseif ($request->has("page") && ($request->has("status") && $request->status != "" && $request->status == "All") && ($request->has("type") &&  $request->type == "")) {
                // exit("Here in iff 2");
                // $status = $request->status == "Active" ? 0 : 1;
                // $whichType = $request->type;

                // $userIds = BAF::select("user_id")
                // ->get();

                // $userIdsArr = $this->stdToArray($userIds);
                // $ids   = array_column($userIdsArr,"user_id");
                // $this->printR($ids,true);
                $users = User::select($this->usersCols)
                    // ->whereIn("id",$ids)
                    // ->where("deleted","=",$status)
                    ->paginate($this->cmperPage);
                // $allRec = User::select(["id","email","deleted"])
                // ->whereIn("id",$ids)
                // ->where("deleted","=",$status)
                // ->count();

                // $users = User::select(["id","email","deleted"])
                // ->where("deleted","=",$status)
                // ->paginate(20);
            } elseif ($request->has("page") && ($request->has("status") && $request->status != "" && $request->status != "All") && ($request->has("type") &&  $request->type == "All")) {
                // exit("Here in iff 2");
                $status = $request->status == "Active" ? 0 : 1;
                // $whichType = $request->type;

                $userIds = BAF::select("user_id")
                    ->get();

                $userIdsArr = $this->stdToArray($userIds);
                $ids   = array_column($userIdsArr, "user_id");
                // $this->printR($ids,true);
                $users = User::select($this->usersCols)
                    ->whereIn("id", $ids)
                    ->where("deleted", "=", $status)
                    ->paginate($this->cmperPage);
                // $allRec = User::select(["id","email","deleted"])
                // ->whereIn("id",$ids)
                // ->where("deleted","=",$status)
                // ->count();

                // $users = User::select(["id","email","deleted"])
                // ->where("deleted","=",$status)
                // ->paginate(20);
            } elseif ($request->has("page") && ($request->has("status") && $request->status == "") && ($request->has("type") &&  $request->type != "" && $request->type != "All")) {
                // exit("Here in iff 1");
                // $status = $request->status == "Onboard" ? 0 : 1;
                $whichType = $request->type == "Practice" ? "Group" :  $request->type;
                if ($request->type == "Practice") {
                    $userIds = BAF::select("user_id")
                        ->get();

                    $userIdsArr = $this->stdToArray($userIds);
                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        // ->where("deleted","=",$status)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // // ->where("deleted","=",$status)
                    // ->count();
                }
                if ($request->type == "Individual") {
                    $sql = "SELECT user_id FROM `cm_user_dd_individualproviderinfo` GROUP BY user_id;";
                    $idsI = $this->rawQuery($sql);

                    // $idsI = $idsI[0];
                    $userIdsArr = $this->stdToArray($idsI);

                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        // ->where("deleted","=",$status)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // // ->where("deleted","=",$status)
                    // ->count();
                }
                // $users = User::select(["id","email","deleted"])
                // ->where("deleted","=",$status)
                // ->paginate(20);
            } elseif ($request->has("page") && ($request->has("status") && $request->status == "All") && ($request->has("type") &&  $request->type != "" && $request->type != "All")) {
                // exit("Here in iff 1");
                // $status = $request->status == "Onboard" ? 0 : 1;
                $whichType = $request->type == "Practice" ? "Group" :  $request->type;
                if ($request->type == "Practice") {
                    $userIds = BAF::select("user_id")
                        ->get();

                    $userIdsArr = $this->stdToArray($userIds);
                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        // ->where("deleted","=",$status)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // // ->where("deleted","=",$status)
                    // ->count();
                }
                if ($request->type == "Individual") {
                    $sql = "SELECT user_id FROM `cm_user_dd_individualproviderinfo` GROUP BY user_id;";
                    $idsI = $this->rawQuery($sql);

                    // $idsI = $idsI[0];
                    $userIdsArr = $this->stdToArray($idsI);

                    $ids   = array_column($userIdsArr, "user_id");
                    // $this->printR($ids,true);
                    $users = User::select($this->usersCols)
                        ->whereIn("id", $ids)
                        // ->where("deleted","=",$status)
                        ->paginate($this->cmperPage);
                    // $allRec = User::select(["id","email","deleted"])
                    // ->whereIn("id",$ids)
                    // // ->where("deleted","=",$status)
                    // ->count();
                }
                // $users = User::select(["id","email","deleted"])
                // ->where("deleted","=",$status)
                // ->paginate(20);
            } elseif ($request->has("page") && $request->status == "All" && $request->type == "All") {
                // exit("Here in iff");
                $whichType = "";
                $users = User::select($this->usersCols)
                    //->where("deleted","=",$request->val)
                    ->paginate($this->cmperPage);
                // $allRec = User::select(["id","email","deleted"])

                // ->count();
            } else {

                $users = User::select($this->usersCols)
                    ->where("deleted", "=", 0)
                    ->paginate($this->cmperPage);
            }
        }




        $directoryData = [];
        $innerData = [];
        // echo $whichType ;
        // exit;
        if (count($users)) {
            foreach ($users as $user) {

                // // print_r($user);
                // exit;
                if ($whichType == "Group") {


                    $childUser = $this->fetchData("user_dd_individualproviderinfo", ["user_id" => $user->id], 1);
                    // print_r($childUser);
                    // exit;
                    if (is_object($childUser)) {
                        // $directoryData[] = ["id"=>$childUser->id,"provider_type" => "solo","contact_person_name" => $childUser->first_name." ".$childUser->last_name,"contact_person_email" => $childUser->email,"contact_person_phone" => $childUser->phone,"state" => $childUser->state_of_birth ,"deleted" => $user->deleted];
                        // $innerData[$childUser->id] = $childUser;
                    } else {
                        $contactInfo = $this->fetchData("user_baf_contactinfo", ["user_id" => $user->id], 1, ["state", "contact_person_phone"]);
                        $businessInfo = $this->fetchData("user_baf_practiseinfo", ["user_id" => $user->id], 1, ["legal_business_name"]);
                        // $this->printR($contactInfo);
                        $lbn = is_object($businessInfo) ? $businessInfo->legal_business_name : "";
                        $cp = is_object($contactInfo) ? $contactInfo->contact_person_phone : "";
                        $st = is_object($contactInfo) ? $contactInfo->state : "";
                        $initials = "";
                        try {
                            if ($lbn != "") {
                                $words = explode(" ", $lbn);
                                foreach ($words as $w) {
                                    $initials .= $w['0'];
                                }
                            }
                        } catch (\Throwable $exception) {
                        }
                        $directoryData[] = ["initials" => $initials, "id" => $user->id, "user_id" => $user->id, "provider_type" => "group", "legal_business_name" => $lbn, "contact_person_email" => $user->email, "contact_person_phone" => $cp, "state" => $st, "deleted" => $user->deleted];
                        // $innerData[$childUser->id] = $childUser;
                    }
                } elseif ($whichType == "Individual") {


                    $childUser = $this->fetchData("user_dd_individualproviderinfo", ["user_id" => $user->id], 1);
                    // print_r($childUser);
                    // exit;
                    if (is_object($childUser)) {
                        $in1 = substr($user->first_name, 0, 1);
                        $in2 = substr($user->last_name, 0, 1);
                        $initials = $in1 . $in2;
                        $directoryData[] = ["initials" => $initials, "id" => $user->id, "user_id" => $childUser->user_id, "provider_type" => "solo", "contact_person_name" => $childUser->first_name . " " . $childUser->last_name, "contact_person_email" => $childUser->email, "contact_person_phone" => $childUser->phone, "state" => $childUser->state_of_birth, "deleted" => $user->deleted];
                        $innerData[$childUser->id] = $childUser;
                    } else {
                        // $contactInfo =$this->fetchData("user_baf_contactinfo",["user_id" => $user->id],1,["state","contact_person_phone"]);
                        // $businessInfo =$this->fetchData("user_baf_practiseinfo",["user_id" => $user->id],1,["legal_business_name"]);
                        // // $this->printR($contactInfo);
                        // $lbn = is_object($businessInfo) ? $businessInfo->legal_business_name : "";
                        // $cp = is_object($contactInfo) ? $contactInfo->contact_person_phone : "";
                        // $st = is_object($contactInfo) ? $contactInfo->state : "";
                        // $directoryData[] = ["id"=>$user->id,"provider_type" => "group","legal_business_name" => $lbn,"contact_person_email" => $user->email,"contact_person_phone" => $cp,"state" => $st,"deleted" => $user->deleted];
                        // // $innerData[$childUser->id] = $childUser;
                    }
                } elseif ($whichType == "" || $whichType == "All") {
                    $childUser = $this->fetchData("user_dd_individualproviderinfo", ["user_id" => $user->id], 1);
                    // print_r($childUser);
                    // exit;
                    if (is_object($childUser)) {
                        $in1 = substr($childUser->first_name, 0, 1);
                        $in2 = substr($childUser->last_name, 0, 1);
                        $initials = $in1 . $in2;
                        $directoryData[] = ["initials" => $initials, "user_id" => $childUser->user_id, "id" => $childUser->id, "provider_type" => "solo", "contact_person_name" => $childUser->first_name . " " . $childUser->last_name, "contact_person_email" => $childUser->email, "contact_person_phone" => $childUser->phone, "state" => $childUser->state_of_birth, "deleted" => $user->deleted];
                        $innerData[$childUser->id] = $childUser;
                    } else {
                        $contactInfo = $this->fetchData("user_baf_contactinfo", ["user_id" => $user->id], 1, ["state", "contact_person_phone"]);
                        $businessInfo = $this->fetchData("user_baf_practiseinfo", ["user_id" => $user->id], 1, ["legal_business_name"]);
                        // $this->printR($contactInfo);
                        $lbn = is_object($businessInfo) ? $businessInfo->legal_business_name : "";
                        $cp = is_object($contactInfo) ? $contactInfo->contact_person_phone : "";
                        $st = is_object($contactInfo) ? $contactInfo->state : "";
                        $initials = "";
                        try {
                            if ($lbn != "") {
                                $words = explode(" ", $lbn);
                                foreach ($words as $w) {
                                    $initials .= $w['0'];
                                }
                            }
                        } catch (\Throwable $exception) {
                        }
                        $directoryData[] = ["initials" => $initials, "id" => $user->id, "user_id" => $user->id, "provider_type" => "group", "legal_business_name" => $lbn, "contact_person_email" => $user->email, "contact_person_phone" => $cp, "state" => $st, "deleted" => $user->deleted];
                        // $innerData[$childUser->id] = $childUser;
                    }
                }
            }
        }
        return $this->successResponse(["providers" => $directoryData, "childs_details" => $innerData, "pagination" => $users], "Success");
        // $this->printR($users);
    }
    /**
     * fetch directory users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchDirectoryUsers(Request $request)
    {
        $tbl = $this->tblU;

        $tbl2 = $this->tbl;

        $key = $this->key;        

        $perPage = $this->cmperPage;
        $page = $request->has("page") ? $request->page : 1;
        $sessionUserId = $request->session_id;



        //exit("in else");
        $smartSearchSql = "";
        $filterSql = "";
        $roleIds = "";

        if ($request->has("filter") && $request->filter == true && $request->col == "smart_search" && $request->val != "") {
            $searchKeyWord = $request->val;
            $smartSearchSql = "WHERE (T.modified_date_of_birth LIKE '%" . $searchKeyWord . "%' OR T.fax_id LIKE '%" . $searchKeyWord . "%' OR T.npi LIKE '%" . $searchKeyWord . "%' OR T.taxid LIKE '%" . $searchKeyWord . "%' OR T.name LIKE '%" . $searchKeyWord . "%' OR T.phone LIKE '%" . $searchKeyWord . "%' OR T.address LIKE '%" . $searchKeyWord . "%' OR T.practice_name LIKE '%" . $searchKeyWord . "%' OR T.doing_business_as LIKE '%" . $searchKeyWord . "%' OR T.email LIKE '%" . $searchKeyWord . "%')";
            if ($request->status != "All" && $request->provider_type == "All") {
                $status = $request->status == "Active" ? 0 : 1;
                $smartSearchSql .= " AND T.deleted = '$status'";
            } elseif ($request->status != "All" && $request->provider_type != "All") {
                $status = $request->status == "Active" ? 0 : 1;
                $smartSearchSql .= "AND T.deleted = '$status'";
                // $roleIds = $request->provider_type == "Practice" ? "9" :  "4,10";
                if ($request->provider_type == "Practice") {
                    $roleIds = "9";
                } elseif ($request->provider_type == "Facility") {
                    $roleIds = "3";
                } else {
                    $roleIds = "4,10";
                }
            } elseif ($request->status == "All" && $request->provider_type != "All") {
                // $roleIds = $request->provider_type == "Practice" ? "9" :  "4,10";
                if ($request->provider_type == "Practice") {
                    $roleIds = "9";
                } elseif ($request->provider_type == "Facility") {
                    $roleIds = "3";
                } else {
                    $roleIds = "4,10";
                }
            }
        }
        if (!$request->has("page")) {
            if ($request->col == "type" && ($request->val != "" && $request->val != "All") && ($request->status != "All" && $request->status != "")) {
                //echo "1";
                $status = $request->status == "Active" ? 0 : 1;
                //$roleIds = $request->val == "Practice" ? "9" :  "4,10";
                if ($request->val == "Practice") {
                    $roleIds = "9";
                } elseif ($request->val == "Facility") {
                    $roleIds = "3";
                } else {
                    $roleIds = "4,10";
                }
                $filterSql = "WHERE T.deleted = '$status'";
            } elseif ($request->col == "type" && ($request->val == "" && $request->val != "All") && $request->status != "All") {
                // echo "2";
                $status = $request->status == "Active" ? 0 : 1;
                //$roleIds = $request->type == "Practice" ? "9" :  "4,10";
                $filterSql = "WHERE T.deleted = '$status'";
            } else if ($request->col == "type" && ($request->val != "" && $request->val != "All") && $request->status == "All") {
                //echo "3";
                //$status = $request->status == "Active" ? 0 : 1;
                // $roleIds = $request->val == "Practice" ? "9" :  "4,10";
                //$filterSql = "WHERE T.deleted = '$status'";
                if ($request->val == "Practice") {
                    $roleIds = "9";
                } elseif ($request->val == "Facility") {
                    $roleIds = "3";
                } else {
                    $roleIds = "4,10";
                }
            } else if ($request->col == "type" && ($request->val != "" && $request->val == "All") && $request->status != "All") {
                //echo "4";
                $status = $request->status == "Active" ? 0 : 1;
                //$roleIds = $request->val == "Practice" ? "9" :  "4,10";
                $filterSql = "WHERE T.deleted = '$status'";
            } else if ($request->col == "type" && ($request->val != "" && $request->val != "All") && $request->status == "") {
                //echo "5";
                //exit("in iff");
                // $status = $request->status == "Active" ? 0 : 1;
                // $roleIds = $request->val == "Practice" ? "9" :  "4,10";
                //$filterSql = "WHERE T.deleted = '$status'";
                if ($request->val == "Practice") {
                    $roleIds = "9";
                } elseif ($request->val == "Facility") {
                    $roleIds = "3";
                } else {
                    $roleIds = "4,10";
                }
            }
        }
        if ($request->has("page")) {
            //exit("has page");
            if ($request->status != "All" && $request->type == "All") {
                $status = $request->status == "Active" ? 0 : 1;
                $filterSql = "WHERE T.deleted = '$status'";
            } elseif ($request->status != "All" && $request->type != "All") {

                $status = $request->status == "Active" ? 0 : 1;
                $filterSql = "WHERE T.deleted = '$status'";
                //$roleIds = $request->type == "Practice" ? "9" :  "4,10";
                if ($request->type == "Practice") {
                    $roleIds = "9";
                } elseif ($request->type == "Facility") {
                    $roleIds = "3";
                } else {
                    $roleIds = "4,10";
                }
            } elseif ($request->status == "All" && $request->type != "All") {
                //$roleIds = $request->type == "Practice" ? "9" :  "4,10";
                if ($request->type == "Practice") {
                    $roleIds = "9";
                } elseif ($request->type == "Facility") {
                    $roleIds = "3";
                } else {
                    $roleIds = "4,10";
                }
            }
        }
       
        // echo $roleIds;
        // exit;
        if (strpos($roleIds, "9") !== false) {


            $sql = "SELECT *
            FROM   (SELECT u.id,
                           u.gender,
                           urm.role_id,
                           state_of_birth,
                           deleted,
                           AES_DECRYPT(u.email, '$key') AS email,
                           CASE
                             WHEN urm.role_id = 9 THEN
                             (SELECT pi.practice_name
                              FROM
                             `cm_$tbl2` pli
                             INNER JOIN `cm_user_baf_practiseinfo` pi
                                     ON pi.user_id = pli.user_parent_id
                                                        WHERE
                             pi.user_id = u.id limit 0,1)
                             ELSE (SELECT AES_DECRYPT(practice_name,'$key')
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS name,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT  AES_DECRYPT(npi, '$key') AS npi
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(npi, '$key') AS npi
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS npi,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(tax_id, '$key') AS tax_id
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(tax_id, '$key') AS tax_id
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS taxid,
                           '-' AS date_of_birth,
                           '-' AS modified_date_of_birth,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(fax, '$key') AS fax
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(fax, '$key') AS fax
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS fax_id,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(phone, '$key') AS phone
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(phone, '$key') AS phone
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS phone,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT
                             AES_DECRYPT(primary_correspondence_address,'$key')
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(primary_correspondence_address,'$key')
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS address,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(practice_name,'$key')
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS practice_name,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(doing_buisness_as,'$key')
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(doing_buisness_as,'$key')
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS doing_business_as
                    FROM   `cm_emp_location_map` elm
                           INNER JOIN `cm_$tbl` u
                                   ON u.id = elm.location_user_id
                           INNER JOIN cm_user_role_map urm
                                   ON urm.user_id = u.id
                                      AND urm.role_id IN ( '9' )
                    WHERE  elm.emp_id = '$sessionUserId') AS T $smartSearchSql $filterSql";
        } elseif (strpos($roleIds, "3") !== false) {

            $sql = "SELECT *
            FROM   (SELECT u.id,
                            u.gender,
                           urm.role_id,
                           state_of_birth,
                           deleted,
                           AES_DECRYPT(u.email, '$key') AS email,
                           CASE
                             WHEN urm.role_id = 9 THEN
                             (SELECT pi.practice_name
                              FROM
                             `cm_$tbl2` pli
                             INNER JOIN `cm_user_baf_practiseinfo` pi
                                     ON pi.user_id = pli.user_parent_id
                                                        WHERE
                             pi.user_id = u.id limit 0,1)
                             ELSE (SELECT AES_DECRYPT(practice_name,'$key')
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS name,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT  AES_DECRYPT(npi, '$key') AS npi
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(npi, '$key') AS npi
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS npi,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT  AES_DECRYPT(tax_id, '$key') AS tax_id
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(tax_id, '$key') AS tax_id
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS taxid,
                           '-' AS date_of_birth,
                           '-' AS modified_date_of_birth,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(fax, '$key') AS fax
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(fax, '$key') AS fax
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS fax_id,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(phone, '$key') AS phone
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(phone, '$key') AS phone
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS phone,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT
                             AES_DECRYPT(primary_correspondence_address,'$key')
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(primary_correspondence_address,'$key')
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS address,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(practice_name,'$key')
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS practice_name,
                           CASE
                             WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                                        FROM
                             `cm_$tbl2`
                                                        WHERE
                             user_id = user_parent_id
                             AND user_parent_id = u.id)
                             ELSE (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                   FROM   `cm_$tbl2`
                                   WHERE  user_id = u.id
                                   GROUP  BY user_id)
                           end AS doing_business_as
                    FROM   `cm_emp_location_map` elm
                           INNER JOIN `cm_$tbl` u
                                   ON u.id = elm.location_user_id
                           INNER JOIN cm_user_role_map urm
                                   ON urm.user_id = u.id
                                      AND urm.role_id IN ( '3' )
                    WHERE  elm.emp_id = '$sessionUserId') AS T $smartSearchSql $filterSql";
        } elseif (strpos($roleIds, "4,10") !== false) {


            $sql = "SELECT *
            FROM   (SELECT u.id,
                           u.gender,
                           urm.role_id,
                           state_of_birth,
                           deleted,
                           AES_DECRYPT(u.email, '$key') AS email,
                           ( Concat(u.first_name, ' ', u.last_name) ) AS name,
                           AES_DECRYPT(u.facility_npi,'$key')         AS npi,
                           '-'                                        AS taxid,
                           AES_DECRYPT(u.dob, '$key')                 AS date_of_birth,
                           Concat(Substring(AES_DECRYPT(u.dob, '$key'), 6, 2), '/', Substring(AES_DECRYPT(u.dob, '$key'), 9, 2),
                           '/',
                           Substring(AES_DECRYPT(u.dob, '$key'), 1, 4))                      AS modified_date_of_birth,
                           AES_DECRYPT(u.fax, '$key')                 AS fax_id,
                           AES_DECRYPT(u.phone, '$key')               AS phone,
                           AES_DECRYPT(u.address_line_one,'$key')     AS address,
                           '-'                                        AS
                           practice_name,
                           '-'                                        AS
                           doing_business_as
                    FROM   `cm_emp_location_map` elm
                           INNER JOIN `cm_individualprovider_location_map` ilm
                                   ON ilm.location_user_id = elm.location_user_id
                           INNER JOIN `cm_$tbl` u
                                   ON u.id = ilm.user_id
                           INNER JOIN cm_user_role_map urm
                                   ON urm.user_id = u.id
                                      AND urm.role_id IN( 4, 10 )
                    WHERE  elm.emp_id = '$sessionUserId') AS T $smartSearchSql $filterSql GROUP BY T.id";
        } else {
            
            $sql = "SELECT *
            FROM   ((SELECT *
                     FROM   (SELECT u.id,
                                    u.gender,
                                    urm.role_id,
                                    state_of_birth,
                                    deleted,
                                    AES_DECRYPT(u.email, '$key') AS email,
                                    CASE
                                      WHEN urm.role_id = 9 THEN
                                      (SELECT pi.practice_name
                                       FROM
                                      `cm_$tbl2` pli
                                      INNER JOIN `cm_user_baf_practiseinfo` pi
                                              ON pi.user_id = pli.user_parent_id
                                                                 WHERE
                                      pi.user_id = u.id limit 0,1
                                      )
                                      ELSE (SELECT   AES_DECRYPT(practice_name, '$key') AS practice_name
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS name,
                                    CASE
                                      WHEN urm.role_id = 9 THEN (SELECT AES_DECRYPT(npi, '$key') AS npi
                                                                 FROM
                                      `cm_$tbl2`
                                                                 WHERE
                                      user_id = user_parent_id
                                      AND user_parent_id = u.id)
                                      ELSE (SELECT AES_DECRYPT(npi, '$key') AS npi
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS npi,
                                    CASE
                                      WHEN urm.role_id = 3 THEN (SELECT AES_DECRYPT(tax_id, '$key') AS tax_id
                                                                 FROM
                                      `cm_$tbl2`
                                                                 WHERE
                                      user_id = user_parent_id
                                      AND user_parent_id = u.id)
                                      ELSE (SELECT AES_DECRYPT(tax_id, '$key') AS tax_id
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS taxid,
                                    '-' AS date_of_birth,
                                    '-' AS modified_date_of_birth,
                                    CASE
                                      WHEN urm.role_id = 9 THEN (SELECT AES_DECRYPT(fax, '$key') AS fax
                                                                 FROM
                                      `cm_$tbl2`
                                                                 WHERE
                                      user_id = user_parent_id
                                      AND user_parent_id = u.id)
                                      ELSE (SELECT AES_DECRYPT(fax, '$key') AS fax
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS fax_id,
                                    CASE
                                      WHEN urm.role_id = 9 THEN (SELECT AES_DECRYPT(phone, '$key') AS phone
                                                                 FROM
                                      `cm_$tbl2`
                                                                 WHERE
                                      user_id = user_parent_id
                                      AND user_parent_id = u.id)
                                      ELSE (SELECT AES_DECRYPT(phone, '$key') AS phone
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS phone,
                                    CASE
                                      WHEN urm.role_id = 9 THEN (SELECT
                                      AES_DECRYPT(primary_correspondence_address, '$key') AS primary_correspondence_address
                                                                 FROM
                                      `cm_$tbl2`
                                                                 WHERE
                                      user_id = user_parent_id
                                      AND user_parent_id = u.id)
                                      ELSE (SELECT  AES_DECRYPT(primary_correspondence_address, '$key') AS primary_correspondence_address
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS address,
                                    CASE
                                      WHEN urm.role_id = 9 THEN (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                                                 FROM
                                      `cm_$tbl2`
                                                                 WHERE
                                      user_id = user_parent_id
                                      AND user_parent_id = u.id)
                                      ELSE (SELECT AES_DECRYPT(practice_name, '$key') AS practice_name
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS practice_name,
                                    CASE
                                      WHEN urm.role_id = 9 THEN (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                                                 FROM
                                      `cm_$tbl2`
                                                                 WHERE
                                      user_id = user_parent_id
                                      AND user_parent_id = u.id)
                                      ELSE (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                            FROM   `cm_$tbl2`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)
                                    end AS doing_business_as
                             FROM   `cm_emp_location_map` elm
                                    INNER JOIN `cm_users` u
                                            ON u.id = elm.location_user_id
                                    INNER JOIN cm_user_role_map urm
                                            ON urm.user_id = u.id
                                               AND urm.role_id IN ( '9', '3' )
                             WHERE  elm.emp_id = '$sessionUserId') AS T $smartSearchSql $filterSql)
                    UNION
                    (SELECT *
                     FROM   (SELECT u.id,
                                    u.gender,
                                    urm.role_id,
                                    state_of_birth,
                                    deleted,
                                    AES_DECRYPT(u.email, '$key') as email,
                                    ( Concat(u.first_name, ' ', u.last_name) ) AS name,
                                    AES_DECRYPT(u.facility_npi,'$key')         AS npi,
                                    '-'                                        AS taxid,
                                    AES_DECRYPT(u.dob, '$key')                 AS date_of_birth,
                                    Concat(Substring(AES_DECRYPT(u.dob, '$key'), 6, 2), '/', Substring(AES_DECRYPT(u.dob, '$key'), 9, 2),
                                    '/',
                                    Substring(AES_DECRYPT(u.dob, '$key'), 1, 4))                      AS modified_date_of_birth,
                                    AES_DECRYPT(u.fax, '$key')                 AS fax_id,
                                    AES_DECRYPT(u.phone, '$key')               AS phone,
                                    AES_DECRYPT(u.address_line_one, '$key')    AS address,
                                    '-'                                        AS practice_name,
                                    '-'                                        AS doing_business_as
                             FROM   `cm_emp_location_map` elm
                                    INNER JOIN `cm_individualprovider_location_map` ilm
                                            ON ilm.location_user_id = elm.location_user_id
                                    INNER JOIN `cm_$tbl` u
                                            ON u.id = ilm.user_id
                                    INNER JOIN cm_user_role_map urm
                                            ON urm.user_id = u.id
                                               AND urm.role_id IN( 4, 10 )
                             WHERE  elm.emp_id = '$sessionUserId') AS T $smartSearchSql $filterSql
                     GROUP  BY T.id)) AS H
            ORDER  BY H.id ";
        }

        // echo $sql;
        // exit;
        $users = DB::select($sql);

        $totalRec = count($users);

        $numOfPage = ceil($totalRec / $perPage);

        if ($page > $numOfPage) {
            //exit("In iff ");
            $offset = $page;
            $users = [];
            $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);
        } else {

            $offset = $page - 1;

            $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

            $users = [];
            $newOffest = $perPage * $offset;
            if ($offset <= $pagination["last_page"]) {
                //exit("else");
                $sql .= " LIMIT $perPage OFFSET $newOffest";

                $users = DB::select($sql);
            }
        }


        $directoryData = [];
        $innerData = [];
        // $this->printR($users,true);
        if (count($users)) {
            foreach ($users as $user) {
                $words = explode(" ", $user->name);
                $initials = "";
                try {
                    if (count($words) > 0) {
                        foreach ($words as $word) {
                            $initials .= $word['0'];
                        }
                        $initials = str_replace("(", "", $initials);
                    }
                } catch (\Exception $e) {
                }
                $whichType = "solo";
                if ($user->role_id == 3) {
                    $whichType = "location_user";
                }
                if ($user->role_id == 9) {
                    $whichType = "Practice";
                }

                $directoryData[] = [
                    "legal_business_name" => $user->name, "initials" => $initials, "id" => $user->id, "user_id" => $user->id,
                    "provider_type" => $whichType, "contact_person_name" => $user->name, "contact_person_email" => $user->email,
                    "contact_person_phone" => $user->phone, "state" => $user->state_of_birth, "deleted" => $user->deleted, "role_id" => $user->role_id, "npi" => $user->npi,
                    "gender" => $user->gender
                ];
                $innerData[$user->id] = $user;
            }
        }
        return $this->successResponse(["providers" => $directoryData, "childs_details" => $innerData, "pagination" => $pagination], "Success");
    }
    /**
     * fetch solo provider details
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $details
     */
    private function fetchSoloUser($userId)
    {
        $childUser = $this->fetchData("user_dd_individualproviderinfo", ["user_id" => $userId], 1);
        $details = [];
        if (is_object($childUser)) {
            $details = [
                "id" => $childUser->id, "user_id" => $childUser->user_id, "provider_type" => "solo", "contact_person_name" => $childUser->first_name . " " . $childUser->last_name,
                "contact_person_email" => $childUser->email, "contact_person_phone" => $childUser->phone, "state" => $childUser->state_of_birth, "facility_tax_id" => null,
                "NPI" => $childUser->facility_npi, "city" => $childUser->citizenship, "address" => null
            ];
        }
        return $details;
    }
    /**
     * fetch the facility providers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function fetchFacilityProviders(Request $request)
    {

        $facilityId = $request->facility_id;

        $role = $this->userRole($facilityId);
        $type = "solo";
        if ($role->id == 9) {
            $type = "location_user";
        }
        if ($role->id == 3) {
            $type = "Practice";
        }
        $search = $request->has('search') ? $request->search : "";
        // $this->printR($role,true);
        if ($type == "location_user") {
            $affiliatedContacts = $this->fetchAfflietedContacts($facilityId, '', 0, $search);
        } elseif ($type == "group") {
            $affiliatedContacts =  $this->fetchPracticeAfflietedContacts($facilityId, $search);
        } else
            $affiliatedContacts = $this->fetchAfflietedContacts($facilityId, '', 0, $search);

        return $this->successResponse(["providers" => $affiliatedContacts], "success");
    }
    /**
     * fetch the provider basic information
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function fetchFacilityBasicInformation(Request $request)
    {
        $facilityId = $request->facility_id;

        $role = $this->userRole($facilityId);

        $type = "solo";

        $where = ["type" => "solo"];
        if ($role->id == 9) {
            $type = "location_user";
            $where = ["type" => "group"];
        }
        if ($role->id == 3) {
            $type = "Practice";
            $where = ["type" => "group"];
        }

        $provider           = $this->fetchLocationUser($facilityId, $type);
        try {
            $breakName = explode(" ", $provider->practice_name);
            $makeShortName = "";
            if (is_array($breakName)) {
                foreach ($breakName as $key => $name) {
                    // if(count($breakName) - 1 == $key)
                    $makeShortName .= substr($name, 0, 1);
                    // else
                    //     $makeShortName.= substr($name,0,1).'_';
                }
            }
            $provider->short_name = $makeShortName;
        } catch (\Exception $e) {
            $provider->short_name = "";
        }
        $facilityData       = $this->fetchData("facilities", $where);
        $filterFaciltyData = [];
        foreach ($facilityData as $faclity) {
            array_push($filterFaciltyData, ["value" => $faclity->facility, "label" => $faclity->facility]);
        }
        $opertionSchedule   = $this->fetchData("user_ddpracticelocationinfo", ["user_id" => $facilityId], 1, ["monday_from", "tuesday_from", "wednesday_from", "thursday_from", "friday_from", "saturday_from", "sunday_from", "monday_to", "tuesday_to", "wednesday_to", "thursday_to", "friday_to", "saturday_to", "sunday_to"]);

        // $locationAddress    = $this->fetchLocationAddress($facilityId);

        return $this->successResponse([
            "provider" => $provider, 'opertion_schedule' => $opertionSchedule,
            'specialties' => $filterFaciltyData
        ], "success");
    }
    function testSmtp()
    {
        $isSentEmail = 0;
        $msg = "";
        try {
            Mail::to("test_eca@yopmail.com")
                ->send(new commonEmail([]));
            $isSentEmail = 1;
        } catch (\Throwable $exception) {
            $isSentEmail = 0;
            $msg = $exception->getMessage();
        }
        return $this->successResponse(['is_email_sent' => $isSentEmail, 'msg' => $msg], "Provider added successfully.");
    }
    /**
     * add test encrypt data
     *
     */
    function addTestEncryptData()
    {
        $data = "Manzoor Ali";
        $encryptionKey = env("APP_KEY"); // Replace with your encryption key

        // Encrypt the data
        $encryptedData = DB::raw("AES_ENCRYPT('$data', '$encryptionKey')");
        //dd($encryptedData);
        // Insert the encrypted data
        echo DB::table('encrypted_data')->insertGetId(['data' => $encryptedData]);
    }
    /**
     * fetch encrypted data
     */
    function fetchEncryptedData()
    {
        $encryptionKey = env("APP_KEY");
        $data = DB::table('encrypted_data')
            ->select(['id', DB::raw("AES_DECRYPT(data, '$encryptionKey') AS decrypted_data")])
            //->whereRaw("AES_DECRYPT(data, '$encryptionKey') LIKE '%$searchPattern%'")
            ->get();

        //dd($data);
        $this->printR($data, true);
    }
    /**
     * search the data with keyword
     */
    function searchEncryptionData(Request $request)
    {
        $keyword =  $request->keyword;
        $encryptionKey = env("APP_KEY");
        $data = DB::table('encrypted_data')
            ->select(['id', DB::raw("AES_DECRYPT(data, '$encryptionKey') AS decrypted_data")])
            ->whereRaw("AES_DECRYPT(data, '$encryptionKey') LIKE '%$keyword%'")
            ->get();

        //dd($data);
        $this->printR($data, true);
    }
    /**
     * function encrypt profile data
     *
     */
    function encryptProfile()
    {

        $key = env("AES_KEY");

        $allData = User::select("id", "dob", "ssn", "visa_number", "email", "phone", "work_phone", "address_line_one", "address_line_two", "fax")

            ->get();
        $encryptData = [];
        //$encryptedData = DB::raw("AES_ENCRYPT('$data', '$encryptionKey')");

        foreach ($allData as $user) {
            if (isset($user->dob))
                $encryptData["dob"] = DB::raw("AES_ENCRYPT('$user->dob', '$key')");
            if (isset($user->ssn))
                $encryptData["ssn"] = DB::raw("AES_ENCRYPT('$user->ssn', '$key')");
            if (isset($user->visa_number))
                $encryptData["visa_number"] = DB::raw("AES_ENCRYPT('$user->visa_number', '$key')");
            if (isset($user->email))
                $encryptData["email"] = DB::raw("AES_ENCRYPT('$user->email', '$key')");
            if (isset($user->phone))
                $encryptData["phone"] = DB::raw("AES_ENCRYPT('$user->phone', '$key')");
            if (isset($user->work_phone))
                $encryptData["work_phone"] = DB::raw("AES_ENCRYPT('$user->work_phone', '$key')");
            if (isset($user->address_line_one))
                $encryptData["address_line_one"] = DB::raw("AES_ENCRYPT('$user->address_line_one', '$key')");
            if (isset($user->address_line_two))
                $encryptData["address_line_two"] = DB::raw("AES_ENCRYPT('$user->address_line_two', '$key')");
            if (isset($user->fax))
                $encryptData["fax"] = DB::raw("AES_ENCRYPT('$user->fax', '$key')");

            User::where("id", "=", $user->id)->update($encryptData);

            $encryptData = []; //reset array
            // $this->printR($encryptData,true);
        }
        echo "done with encryption of profile information";
        // $this->printR($allData,true);
        //return $new_key;
    }
    /**
     * encrypt practice profile information
     */
    function encryptPracticeProfile()
    {
        $key = env("AES_KEY");

        // $practiceData = DB::table("user_ddpracticelocationinfo")

        //     ->select(
        //         "id",
        //         "email",
        //         "doing_buisness_as",
        //         "npi",
        //         "tax_id",
        //         "fax",
        //         "phone",
        //         "practise_address",
        //         "practise_address1",
        //         "practise_phone",
        //         "practise_fax",
        //         "practise_email",
        //         "primary_correspondence_address",
        //         "practice_name"
        //     )

        //     ->get();
        $practiceData = DB::table("user_ddpracticelocationinfo")

            // ->select(
            //     "id",
            //     "email",
            //     "doing_buisness_as",
            //     "npi",
            //     "tax_id",
            //     "fax",
            //     "phone",
            //     "practise_address",
            //     "practise_address1",
            //     "practise_phone",
            //     "practise_fax",
            //     "practise_email",
            //     "primary_correspondence_address",
            //     "practice_name"
            // )

            ->get();

        // $this->printR($practiceData,true);
        $encryptPracticeData = [];
        foreach ($practiceData as $eachData) {
            $encryptPracticeData = $this->stdToArray($eachData);
            //dd($eachData);
            if (isset($eachData->email))
                $encryptPracticeData["email"] = DB::raw("AES_ENCRYPT('$eachData->email', '$key')");
            if (isset($eachData->doing_buisness_as))
                $encryptPracticeData["doing_buisness_as"] = DB::raw("AES_ENCRYPT('$eachData->doing_buisness_as', '$key')");
            if (isset($eachData->npi))
                $encryptPracticeData["npi"] = DB::raw("AES_ENCRYPT('$eachData->npi', '$key')");
            if (isset($eachData->tax_id))
                $encryptPracticeData["tax_id"] = DB::raw("AES_ENCRYPT('$eachData->tax_id', '$key')");
            if (isset($eachData->fax))
                $encryptPracticeData["fax"] = DB::raw("AES_ENCRYPT('$eachData->fax', '$key')");
            if (isset($eachData->phone))
                $encryptPracticeData["phone"] = DB::raw("AES_ENCRYPT('$eachData->phone', '$key')");
            if (isset($eachData->practise_address))
                $encryptPracticeData["practise_address"] = DB::raw("AES_ENCRYPT('$eachData->practise_address', '$key')");
            if (isset($eachData->practise_address1))
                $encryptPracticeData["practise_address1"] = DB::raw("AES_ENCRYPT('$eachData->practise_address1', '$key')");
            if (isset($eachData->practise_phone))
                $encryptPracticeData["practise_phone"] = DB::raw("AES_ENCRYPT('$eachData->practise_phone', '$key')");
            if (isset($eachData->practise_fax))
                $encryptPracticeData["practise_fax"] = DB::raw("AES_ENCRYPT('$eachData->practise_fax', '$key')");
            if (isset($eachData->practise_email))
                $encryptPracticeData["practise_email"] = DB::raw("AES_ENCRYPT('$eachData->practise_email', '$key')");
            if (isset($eachData->primary_correspondence_address))
                $encryptPracticeData["primary_correspondence_address"] = DB::raw("AES_ENCRYPT('$eachData->primary_correspondence_address', '$key')");
            if (isset($eachData->practice_name))
                $encryptPracticeData["practice_name"] = DB::raw("AES_ENCRYPT('$eachData->practice_name', '$key')");

            DB::table("user_ddpracticelocationinfo")

                ->where("id", "=", $eachData->id)

                ->update($encryptPracticeData);

            $encryptPracticeData = []; //reset array

            // $this->printR($encryptPracticeData,true);
        }
        echo "Practice profile updated successfully";
    }
    /**
     * encrypt banking information
     *
     */
    function encryptBankingInformation()
    {
        $key = env("AES_KEY");

        $bankData = DB::table("user_ddbankinginfo")

            ->select('id', 'routing_number', 'account_number', 'account_name')

            ->get();

        if (count($bankData)) {
            $encryptBankData = [];
            foreach ($bankData as $bank) {
                $encryptBankData = $this->stdToArray($bank);
                if (isset($bank->routing_number))
                    $encryptBankData["routing_number"] = DB::raw("AES_ENCRYPT('$bank->routing_number', '$key')");
                if (isset($bank->account_number))
                    $encryptBankData["account_number"] = DB::raw("AES_ENCRYPT('$bank->account_number', '$key')");
                if (isset($bank->account_name))
                    $encryptBankData["account_name"] = DB::raw("AES_ENCRYPT('$bank->account_name', '$key')");

                DB::table("user_ddbankinginfo")

                    ->where("id", "=", $bank->id)

                    ->update($encryptBankData);

                $encryptBankData = []; //reset array
            }
        }
        echo "Bank information updated successfully";
    }
    /**
     * encypt business information
     */
    function encryptBussinesInformation()
    {
        $key = env("AES_KEY");

        $businessInfoData = DB::table("user_dd_businessinformation")

            //->select('id','facility_npi', 'legal_business_name', 'primary_correspondence_address', 'phone', 'fax', 'email', 'facility_tax_id')

            ->get();
        if (count($businessInfoData)) {
            $encryptBIData = [];
            foreach ($businessInfoData as $businessInfo) {
                $encryptBIData = $this->stdToArray($businessInfo);
                if (isset($businessInfo->facility_npi))
                    $encryptBIData["facility_npi"] = DB::raw("AES_ENCRYPT('$businessInfo->facility_npi', '$key')");
                if (isset($businessInfo->legal_business_name))
                    $encryptBIData["legal_business_name"] = DB::raw("AES_ENCRYPT('$businessInfo->legal_business_name', '$key')");
                if (isset($businessInfo->primary_correspondence_address))
                    $encryptBIData["primary_correspondence_address"] = DB::raw("AES_ENCRYPT('$businessInfo->primary_correspondence_address', '$key')");
                if (isset($businessInfo->phone))
                    $encryptBIData["phone"] = DB::raw("AES_ENCRYPT('$businessInfo->phone', '$key')");
                if (isset($businessInfo->fax))
                    $encryptBIData["fax"] = DB::raw("AES_ENCRYPT('$businessInfo->fax', '$key')");
                if (isset($businessInfo->email))
                    $encryptBIData["email"] = DB::raw("AES_ENCRYPT('$businessInfo->email', '$key')");
                if (isset($businessInfo->facility_tax_id))
                    $encryptBIData["facility_tax_id"] = DB::raw("AES_ENCRYPT('$businessInfo->facility_tax_id', '$key')");

                DB::table("user_dd_businessinformation")

                ->where("id","=",$businessInfo->id)

                ->update($encryptBIData);

                $encryptBIData = [];
            }
        }
        echo "Bussines information updated successfully";
        //facility_npi,legal_business_name,primary_correspondence_address,phone,fax,email,facility_tax_id
    }
    /**
     * add new practice in the system
     *
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function addPractice(Request $request)
    {
        $key = env("AES_KEY");
        $request->validate([
            "legal_business_name" => "required",
            "email" => "required",
        ]);
        $email = $request->email;
        if(!$request->has('skip_user_creation')) {
            $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                ->count();
            
            if ($user > 0)
                return $this->warningResponse(["email" => $email], "Practice already found against this email " . $email, 302);

            $password = "Qwerty123#";
            $addUser = [
                "email" => DB::raw("AES_ENCRYPT('$request->email', '$key')"),
                "password" => Hash::make($password),
                "created_at" => $this->timeStamp()
            ];

            $user = User::create($addUser);

            $user->createToken("Practice  Token ")->plainTextToken;
        }
        else {
            $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                ->first(["id"]);
            
        }

        $userId = $user->id;
        $practiceInfo = [

            "user_id" => $userId,

            "provider_type" => isset($request->provider_type) ? $request->provider_type : NULL,

            "provider_name" => isset($request->provider_name) ? $request->provider : NULL,

            "legal_business_name" => isset($request->legal_business_name) ? $request->legal_business_name : NULL,

            "doing_business_as" => isset($request->doing_business_as) ? $request->doing_business : NULL,

            "number_of_individual_provider" => isset($request->number_of_individual_provider) ? $request->number_of_individual_provider : NULL,
        ];

        $this->addData("user_baf_practiseinfo", $practiceInfo, 0);

        $businessInfo = [


            "user_id" => $userId,

            "business_type" => isset($request->business_type) ? $request->business_type : NULL,

            "begining_date" => isset($request->begining_date) ? $request->begining_date : NULL,

            "number_of_physical_location" => isset($request->number_of_physical_location) ? $request->number_of_physical_location : NULL,

            "avg_patient_day" => isset($request->avg_patient_day) ? $request->avg_patient_day :  NULL,

            "practise_managemnt_software" => isset($request->practise_managemnt_software) ? $request->practise_managemnt_software : NULL,

            "use_pms" => isset($request->use_pms) ? $request->use_pms : NULL,

            "electronic_health_record_software" => isset($request->electronic_health_record_software) ? $request->electronic_health_record_software : NULL,

            "use_ehr" => isset($request->use_ehr) ? $request->use_ehr : NULL,

            "seeking_service" => isset($request->seeking_service) ? $request->seeking_service : NULL

        ];

        $this->addData("user_baf_businessinfo", $businessInfo, 0);
        $contactInfo = [

            "user_id" => $userId,

            "address" => isset($request->address) ? $request->address : NULL,

            "address_line_one" => isset($request->address_line_one) ? $request->address_line_one : NULL,

            "city" => isset($request->city) ? $request->city : NULL,

            "state" => isset($request->state) ? $request->state : NULL,

            "zip_code" => isset($request->zip_code) ? $request->zip_code : NULL,

            "contact_person_name" => isset($request->contact_person_name) ? $request->contact_person_name : NULL,

            "contact_person_email" => isset($request->contact_person_email) ? $request->contact_person_email : NULL,

            "contact_person_designation" => isset($request->contact_person_designation) ? $request->contact_person_designation : NULL,

            "contact_person_phone" => isset($request->contact_person_phone) ? $request->contact_person_phone : NULL,

            "has_physical_location" => isset($request->has_physical_location) ? $request->has_physical_location : NULL,

            "comments" => isset($request->comments) ? $request->comments : NULL
        ];
        $this->addData("user_baf_contactinfo", $contactInfo, 0);

        $role = Role::where("id", "=", 3)->first(["id"]);

        $roleId = $role->id;
        $roleData = [
            "user_id" => $userId,
            "role_id" => $roleId
        ];

        $comapny_map = [
            "user_id" => $userId,
            "company_id" => 1,
        ];



        $this->addData("user_role_map", $roleData, 0);

        $this->addData("user_company_map", $comapny_map, 0);

        echo "practice added successfully with id :".$userId;
    }
    /**
     * add new Facility in the system
     *
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function addFacility(Request $request) {
        $key = env("AES_KEY");
        $request->validate([
            "legal_business_name" => "required",
            "email" => "required",
            "practice_id" => "required",
            "practice_name" => "required"
        ]);
        $email = $request->email;
        
        if(!$request->has('skip_user_creation')) {
          
            $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                ->count();
            
            if ($user > 0)
                return $this->warningResponse(["email" => $email], "Facility already found against this email " . $email, 302);

            $password = "Qwerty123#";
            $addUser = [
                "email" => DB::raw("AES_ENCRYPT('$request->email', '$key')"),
                "password" => Hash::make($password),
                "created_at" => $this->timeStamp()
            ];

            $user = User::create($addUser);

            $user->createToken("Practice  Token ")->plainTextToken;
        }
        else {
            $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")
            ->first(["id"]);
        }

        $userId = $user->id;

        $addFacility = [
            "user_parent_id" => $request->practice_id,
            "user_id" => $userId,
        ];
        if($request->has("practice_name"))
            $addFacility["practice_name"] = DB::raw("AES_ENCRYPT('$request->practice_name', '$key')");
        
        if($request->has("legal_business_name"))
            $addFacility["doing_buisness_as"] = DB::raw("AES_ENCRYPT('$request->legal_business_name', '$key')");
        if($request->has("npi"))
            $addFacility["npi"] = DB::raw("AES_ENCRYPT('$request->npi', '$key')");
        if($request->has("tax_id"))
            $addFacility["tax_id"] = DB::raw("AES_ENCRYPT('$request->tax_id', '$key')");
        if($request->has("primary_correspondence_address"))
            $addFacility["primary_correspondence_address"] = DB::raw("AES_ENCRYPT('$request->primary_correspondence_address', '$key')");


        $role = Role::where("id", "=", 9)->first(["id"]);

        $roleId = $role->id;
        $roleData = [
            "user_id" => $userId,
            "role_id" => $roleId
        ];

        $comapny_map = [
            "user_id" => $userId,
            "company_id" => 1,
        ];



        $this->addData("user_role_map", $roleData, 0);

        $this->addData("user_company_map", $comapny_map, 0);

        $this->addData("user_ddpracticelocationinfo", $addFacility, 0);

        echo "facility added successfully with id :".$userId;
        
    }
    /**
     * add new Facility in the system
     *
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function assignToUser(Request $request) {
        $key = env("AES_KEY");
        $request->validate([
            "email" => "required",
            "location_id" => "required"
        ]);
        $email = $request->email;

        $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")
        
        ->first(["id"]);
        
        $userId = $user->id;
        
        $locationId = $request->location_id;
        
        $where = [
            ["emp_id", "=",$userId],
            ["location_user_id", "=",$locationId]
        ];

        $alreadyAssigned = $this->fetchData("emp_location_map",$where,1,["id"]);
        $message = "Person already assigned";
        if(!is_object($alreadyAssigned)) {
            $message = "Person assigned";
            $this->addData("emp_location_map", ["emp_id" => $userId,"location_user_id" => $locationId], 0);
        }

        echo $message;
    }
    /**
     * revoke user from the system
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function revokeUser(Request $request) {
        $key = env("AES_KEY");
        $request->validate([
            "email" => "required"
        ]);
        $email = $request->email;
        $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")
        
        ->update(["deleted" => 1,"password" => NULL]);

        echo "User has been revoked successfully";
    }

}
