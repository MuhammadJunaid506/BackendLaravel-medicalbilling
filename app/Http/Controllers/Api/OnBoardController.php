<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\EditImage;
use App\Models\User;
use Mail;
use DB;
use App\Models\ActiveInActiveLogs;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Mail\FacilityFormLinkEmail;
use App\Mail\FacilityFormOTPCode;
use App\Mail\ProviderFormLinkEmail;
use App\Mail\ProviderFormOTPCode;
use App\Models\EmpLocationMap;
use App\Models\License;
use App\Models\PracticeServiceTypeDropdown;
use App\Models\ProviderLocationMap;
use App\Models\StatesCities;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\Credentialing;

class OnBoardController extends Controller
{
    use ApiResponseHandler, Utility, EditImage;
    private $key = "";


    public function __construct()
    {
        $this->key = env("AES_KEY");
    }
    /**
     *check the facility name uniqueness
     *
     * @param string $name
     */
    public function isFacilityNameUnique($name)
    {
        $key = $this->key;
        $data = DB::table('user_ddpracticelocationinfo')
            ->whereRaw("AES_DECRYPT(practice_name, '$key') = '$name'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the facility email uniqueness
     *
     * @param string $email
     */
    public function isFacilityEmailUnique($email)
    {
        $key = $this->key;
        $data = DB::table('users')
            ->whereRaw("AES_DECRYPT(email, '$key') = '$email'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the facility npi uniqueness
     *
     * @param string $npi
     */
    public function isFacilityNPIUnique($npi)
    {
        $key = $this->key;
        $data = DB::table('user_ddpracticelocationinfo')
            ->whereRaw("AES_DECRYPT(npi, '$key') = '$npi'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
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
    /**
     *check the practice npi uniqueness
     *
     * @param string $npi
     */
    public function isPracticeNPIUnique($npi)
    {
        $key = $this->key;

        $data = DB::table('user_dd_businessinformation')
            ->whereRaw("AES_DECRYPT(facility_npi, '$key') = '$npi'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }

    /**
     *check the practice email uniqueness
     *
     * @param string $name
     */
    public function isPracticeEmailUnique($email)
    {
        $key = $this->key;

        $data = DB::table('users')

            ->whereRaw("AES_DECRYPT(email, '$key') = '$email'")

            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the provider name uniqueness
     *
     * @param string $name
     */

    public function isProviderNameUnique($name)
    {
        $data = DB::table('users')
            ->whereRaw("concat(first_name,' ', last_name) LIKE '%$name%'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the provider name uniqueness
     *
     * @param string $name
     */

    public function isProviderFirstNameUnique($fname)
    {
        $data = DB::table('users')
            ->where("first_name", "=", $fname)
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the provider name uniqueness
     *
     * @param string $name
     */

    public function isProviderlastNameUnique($lname)
    {
        $data = DB::table('users')
            ->where("last_name", "=", $lname)
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the provider name uniqueness
     *
     * @param string $name
     */

    public function isProviderDOBUnique($dob)
    {
        $key = $this->key;
        $dob = $this->formatDate($dob);
        $data = DB::table('users')
            ->whereRaw("AES_DECRYPT(dob, '$key') = '$dob'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the provider email uniqueness
     *
     * @param string $email
     */
    public function isProviderEmailUnique($email)
    {
        $key = $this->key;
        $data = DB::table('users')
            ->whereRaw("AES_DECRYPT(cm_users.email, '$key') = '$email'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     *check the provider npi uniqueness
     *
     * @param string $name
     */
    public function isProviderNPIUnique($npi)
    {
        $key = $this->key;
        $data = DB::table('users')
            ->whereRaw("AES_DECRYPT(cm_users.facility_npi, '$key') = '$npi'")
            ->count();

        //dd($data,is_object($data));
        if ($data > 0)
            return false;
        else
            return true;
    }
    /**
     * check provider is unique in database
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function chkUniqueProvider(Request $request)
    {
        $request->validate([
            "first_name"    => "required",
            "last_name"     => "required",
            "email"         => "required",
            "date_of_birth" => "required"
        ]);
        $key = $this->key;
        $email          = $request->email;
        $npi            = $request->facility_npi;
        $firstName      = $request->first_name;
        $lastName       = $request->last_name;
        $dateOfBirth    = $request->date_of_birth;

        $isEmailUnique      = $this->isProviderEmailUnique($email);
        $isNPIUnique        = $this->isProviderNPIUnique($npi);
        $dob                = $this->formatDate($dateOfBirth);
        $isDOBUnique        = $this->isProviderDOBUnique($dob);
        $isFirstNameUnique  = $this->isProviderFirstNameUnique($firstName);
        $isLastNameUnique   = $this->isProviderlastNameUnique($lastName);

        if ($request->has("facility_npi") && ($isEmailUnique && $isNPIUnique && $isDOBUnique && $isFirstNameUnique && $isLastNameUnique)) {

            $user = DB::table('users')
                ->whereRaw("AES_DECRYPT(cm_users.facility_npi, '$key') = '$npi'")
                ->whereRaw("AES_DECRYPT(cm_users.email, '$key') = '$email'")
                ->whereRaw("AES_DECRYPT(dob, '$key') = '$dob'")
                ->where("first_name", "=", $firstName)
                ->where("last_name", "=", $lastName)
                ->first(["id"]);

            return $this->successResponse(['is_unique' => true, 'user' => $user], "success");
        }
        if (!$request->has("facility_npi") && ($isEmailUnique  && $isDOBUnique && $isFirstNameUnique && $isLastNameUnique)) {
            $user = DB::table('users')
                //->whereRaw("AES_DECRYPT(cm_users.facility_npi, '$key') = '$npi'")
                ->whereRaw("AES_DECRYPT(cm_users.email, '$key') = '$email'")
                ->whereRaw("AES_DECRYPT(dob, '$key') = '$dob'")
                ->where("first_name", "=", $firstName)
                ->where("last_name", "=", $lastName)
                ->first(["id"]);
            return $this->successResponse(['is_unique' => true, 'user' => $user], "success");
        } else {
            $user = DB::table('users');
            if ($request->has("facility_npi"))
                $user = $user->whereRaw("AES_DECRYPT(cm_users.facility_npi, '$key') = '$npi'");

            $user = $user->whereRaw("AES_DECRYPT(cm_users.email, '$key') = '$email'")
                ->whereRaw("AES_DECRYPT(dob, '$key') = '$dob'")
                ->where("first_name", "=", $firstName)
                ->where("last_name", "=", $lastName)
                ->first(["id"]);
            return $this->successResponse(['is_unique' => false, 'user' => $user], "success");
        }
    }
    /**
     * link providers with facility
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function facilityLinkProvider(Request $request)
    {
        $request->validate([
            "providers" => "required",
            "facility_id" => "required"
        ]);

        $facilityId = $request->facility_id;

        $linkProviders = $request->has('providers') ? json_decode($request->providers, true) : [];

        $unlinkProviders = $request->has('unlinkproviders') ? json_decode($request->unlinkproviders, true) : [];

        $linkedStatus = $this->linkProviders($facilityId, $linkProviders);

        $unLinkedStatus = $this->unlinkProviders($facilityId, $unlinkProviders);

        return $this->successResponse(['is_linked' => $linkedStatus, 'unlinkeds_tatus' => $unLinkedStatus], "success");
    }
    /**
     * link the providers with location
     *
     * @param  $providers
     */
    private function linkProviders($facilityId, $providers)
    {
        $linkedStatus = false;
        if (count($providers) > 0) {
            $linkedStatus = true;
            foreach ($providers as $provider) {
                $whereLinkedLocation = [
                    ["user_id", "=", $provider],
                    ["location_user_id", "=", $facilityId]
                ];

                $alreadyInLoc = $this->fetchData("individualprovider_location_map", $whereLinkedLocation, 1, []);
                if (is_object($alreadyInLoc))
                    $this->updateData("individualprovider_location_map", $whereLinkedLocation, ["user_id" => $provider, "location_user_id" => $facilityId]);
                else
                    $this->addData("individualprovider_location_map",  ["user_id" => $provider, "location_user_id" => $facilityId], 0);
            }
        }
        return $linkedStatus;
    }
    /**
     * un link providers from the location
     *
     * @param $facilityId
     * @param $unlinkProviders
     */
    private function unlinkProviders($facilityId, $unlinkProviders)
    {
        $unlinkedStatus = false;
        if (count($unlinkProviders) > 0) {
            $unlinkedStatus = true;
            foreach ($unlinkProviders as $provider) {
                $whereLinkedLocation = [
                    ["user_id", "=", $provider],
                    ["location_user_id", "=", $facilityId]
                ];

                $this->deleteData("individualprovider_location_map", $whereLinkedLocation);
            }
        }
        return $unlinkedStatus;
    }
    /**
     * add the more facility against the practice
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addMoreFacility(Request $request)
    {
        $request->validate([
            "practice_id" => "required",
            "facility_name" => "required",
        ]);
        $practiceId = $request->practice_id;

        $sessionUserId = $request->session_userid;

        $facilityName = $request->facility_name;

        $providers = $request->has('providers') ? json_decode($request->providers, true) : [];

        $facility = $this->createFacility($practiceId, $sessionUserId, $facilityName);
        if (isset($facility["facility_id"]) && count($providers) > 0) {
            $this->linkProviders($facility["facility_id"], $providers);
        }
        return $this->successResponse($facility, "success");
    }
    /**
     * add the more facility against the practice
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addMoreProvider(Request $request)
    {
        $request->validate([
            "facility_id"   => "required",
            "first_name"    => "required",
            "last_name"     => "required",
            "email"         => "required",
            "date_of_birth" => "required"
        ]);
        $facilities = json_decode($request->facility_id, true);

        $sessionUserId = $request->session_userid;

        $firstName = $request->first_name;

        $lastName = $request->last_name;

        $email = $request->email;

        $dob = $request->date_of_birth;

        $npiNumber = $request->has("npi_number") ? $request->npi_number : null;

        $providerExist = json_decode($this->chkUniqueProvider($request)->getContent(), true);
        $addProvider = [];
        // $this->printR($providerExist["data"],true);
        if (
            isset($providerExist["data"])
            && (isset($providerExist["data"]['is_unique'])
                && !$providerExist["data"]['is_unique'])
            && (isset($providerExist["data"]['user'])
                && is_array($providerExist["data"]['user']))
        ) {
            $providerId = $providerExist["data"]['user']['id'];
            foreach ($facilities as $facility) {
                $whereProviderMap = [
                    ["user_id", "=", $providerId],
                    ["location_user_id", "=", $facility]
                ];
                $providerMap = $this->fetchData("individualprovider_location_map", $whereProviderMap, 1, []);
                if (!is_object($providerMap))
                    $addProvider[] = $this->addData("individualprovider_location_map", ["user_id" => $providerId, "location_user_id" => $facility]);
            }
        } else {

            // $this->printR($facilities,true);
            foreach ($facilities as $facility) {
                $addProvider[] = $this->createProvider($facility, $facility, $sessionUserId, $firstName, NULL, $npiNumber, $lastName, $email, $dob);
            }
        }
        return $this->successResponse($addProvider, "success");
    }
    /**
     * delete the facility from the database
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteFacility($practiceId, $facilityId, Request $request)
    {
        $whereFacilityLinked = [
            ["location_user_id", "=", $facilityId],
        ];
        $this->deleteData("emp_location_map", $whereFacilityLinked);

        $compMapDel = [
            ['user_id', '=', $facilityId],
            ['company_id', '=', 1]
        ];
        $this->deleteData("user_company_map", $compMapDel);

        $delRole = [
            ["user_id", "=", $facilityId],
            ["role_id", "=", 3]
        ];
        $this->deleteData("user_role_map", $delRole);

        $delFacility = [
            ['user_id', '=', $facilityId],
            ['user_parent_id', '=', $practiceId]
        ];
        $this->deleteData("user_ddpracticelocationinfo", $delFacility);

        $delFacilityProfile = [
            ['id', '=', $facilityId]
        ];
        $this->deleteData("users", $delFacilityProfile);

        return $this->successResponse(["is_delete" => true], "success");
    }
    /**
     * delete the provider from the database
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteProvider($practiceId, $facilityId, $providerId, Request $request)
    {
        $whereLinkedProvider = [
            ["user_id", "=", $providerId],
            ["parent_user_id", "=", $practiceId]
        ];


        $this->deleteData("user_dd_individualproviderinfo", $whereLinkedProvider);


        $compMap = [
            ['user_id', '=', $providerId],
            ['company_id', '=', 1]
        ];

        $this->deleteData("user_company_map", $compMap);

        $whereRoleMap = [
            ["user_id", "=", $providerId],
            ["role_id", "=", 10]
        ];

        $this->deleteData("user_role_map",  $whereRoleMap);

        $whereLocationMap = [
            ["user_id", "=", $providerId],
            ["location_user_id", "=", $facilityId]
        ];

        $this->deleteData("individualprovider_location_map", $whereLocationMap);

        $whereProviderLinked = [
            ["location_user_id", "=", $providerId],
        ];

        $this->deleteData("emp_location_map", $whereProviderLinked);

        $delProviderProfile = [
            ['id', '=', $providerId]
        ];

        $this->deleteData("users", $delProviderProfile);

        return $this->successResponse(["is_delete" => true], "success");
    }
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchOnBoardingPracticeData(Request $request)
    {
        $key = $this->key;

        $request->validate([
            "practice_id" => "required"
        ]);

        $practiceId = $request->practice_id;

        $practice = DB::table("user_baf_practiseinfo")

            ->select(
                "user_baf_practiseinfo.practice_name",
                "user_baf_practiseinfo.practice_name",
                "user_baf_practiseinfo.user_id as practice_id",
                "users.is_complete",
                "users.profile_complete_percentage",
                "users.status"
            )
            ->join("users", "users.id", "=", "user_baf_practiseinfo.user_id")
            ->where("user_baf_practiseinfo.user_id", "=", $practiceId)

            ->first();

        $facilities = DB::table("user_ddpracticelocationinfo")

            ->select(
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') as facility_name"),
                "user_ddpracticelocationinfo.user_id as facility_id",
                "users.is_complete",
                "users.profile_complete_percentage",
                "users.status"
            )

            ->join("users", "users.id", "=", "user_ddpracticelocationinfo.user_id")

            ->where("user_ddpracticelocationinfo.user_parent_id", "=", $practiceId)

            ->get();
        // $this->printR($facilities,true);
        $providerForm = [];
        if ($facilities->count() > 0) {

            $facilitiesArr = $this->stdToArray($facilities, true);

            $facilityIds = array_column($facilitiesArr, "facility_id");
            // $this->printR($facilityIds,true);
            $providerForm = DB::table("individualprovider_location_map as ilp")

                ->select(
                    "ilp.user_id as provider_id",
                    "users.is_complete",
                    "users.profile_complete_percentage",
                    DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as provider_name"),
                    "users.status"
                )

                ->join("users", "users.id", "=", "ilp.user_id")

                ->whereIn("ilp.location_user_id", $facilityIds)
                ->groupBy("ilp.user_id")
                ->get();
            // $this->printR($providerForm,true);
        }
        $practiceResponse = [
            "practice_form" => $practice,
            "facility_form" => $facilities,
            "provider_form" => $providerForm
        ];

        return $this->successResponse($practiceResponse, "success");
    }
    /**
     * fetch each on-board of each section
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchOnBoardingEachFormData(Request $request)
    {

        $request->validate([
            "section" => "required",
            "user_id" => "required"
        ]);

        $section = $request->section;
        $userId = $request->user_id;
        if ($section == "practice") {
        }
    }
    /**
     * create the new practice
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createPractice(Request $request)
    {
        $request->validate([
            "practice_name" => "required"
        ]);
        $key = $this->key;
        $practiceName = $request->practice_name;

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

        $this->addData("user_baf_practiseinfo", $practiceInfo);
        $msg = $practiceName . " created.";
        if (strlen($msg)) {
            // $this->addDirectoryLogs($practiceId, $sessionUserId, $msg, "Practice");
            DB::table("practice_logs")->insertGetId([
                "practice_id" => $practiceId, "session_userid" => $sessionUserId, "section" => "Practice Profile",
                "action" => "Add",
                "practice_profile_logs" => DB::raw("AES_ENCRYPT('" .    $msg     . "', '$key')"),
                'created_at' => $this->timeStamp()
            ]);
        }

        // $facility = $this->createFacility($practiceId, $sessionUserId, NULL, $practiceName);

        // $provider = $this->createProvider($practiceId, $facility["facility_id"], $sessionUserId, NULL, $practiceName);

        $practiceResponse = [
            "practice_form" => ["practice_id" => $practiceId, "name" => $practiceName],
            "facility_form" => [],
            "provider_form" => []
        ];

        return $this->successResponse($practiceResponse, "success");
    }
    /**
     * create a new facility relation
     *
     * @param $practiceId
     * @param $sessionUserId
     */
    private function createFacility($practiceId, $sessionUserId, $facilityName = NULL, $practiceName = NULL)
    {
        $key = $this->key;
        $facilityName = !is_null($facilityName) ? $facilityName : $practiceName . " Facility 1";

        $facilityName = strtoupper($facilityName);

        $email = strtolower($facilityName) . "@eclinicassist.com";
        $addFacility = [
            'first_name' =>  NULL,
            "last_name" =>   NULL,
            "email"     =>  DB::raw("AES_ENCRYPT('" .    $email     . "', '$key')"),
            "password"  => NULL,
            'is_complete' => false,
            'profile_complete_percentage' => 0,
            'status' => "Active",
            'created_at' => $this->timeStamp()
        ];

        $user = User::create($addFacility); //create the facility profile

        $user->createToken($facilityName . " Token")->plainTextToken; //create the facility token

        $facilityId = $user->id;
        $whereFacilityLinked = [
            ["emp_id", "=", $sessionUserId],
            ["location_user_id", "=", $facilityId],
        ];
        $facilityLinked = $this->fetchData("emp_location_map", $whereFacilityLinked, 1, []);
        if (!is_object($facilityLinked)) {
            $this->addData("emp_location_map", ["emp_id" => $sessionUserId, "location_user_id" => $facilityId]);
        }
        $compMap = [
            'user_id' => $facilityId,
            'company_id' => 1
        ];
        $this->addData("user_company_map", $compMap);

        $this->addData("user_role_map",  ["user_id" => $facilityId, "role_id" => 3, "role_preference" => 1], 0); //assign the role the new facility
        $facility = [
            'user_id' => $facilityId,
            'user_parent_id' => $practiceId,
            'practice_name' => DB::raw("AES_ENCRYPT('" .   $facilityName     . "', '$key')")

        ];
        $this->addData("user_ddpracticelocationinfo", $facility);
        $facilityRelation = ["practice_id" => $practiceId, "facility_id" => $facilityId, "name" => $facilityName];
        $msg = $facilityName . " created.";
        if (strlen($msg)) {
            // $this->addDirectoryLogs($facilityId, $sessionUserId, $msg, "Facility");
            DB::table("facility_logs")->insertGetId([
                "facility_id" => $facilityId, "session_userid" => $sessionUserId, "section" => "Facility Profile",
                "action" => "Created",
                "log" => DB::raw("AES_ENCRYPT('" .    $msg     . "', '$key')"),
                'created_at' => $this->timeStamp()
            ]);
        }

        return $facilityRelation;
    }
    /**
     * create the povider relation
     *
     * @param string $practiceId
     * @param string $facilityId
     */
    private function createProvider($practiceId, $facilityId, $sessionUserId, $providerName = null, $practiceName = NULL, $npiNumber = NULL, $lastName = NULL, $email = NULL, $dob = NULL)
    {
        $companyId = 1;
        if (!is_null($dob))
            $dob = $this->formatDate($dob);

        $key = $this->key;
        $providerName = is_null($providerName) ? $practiceName . " Provider 1" : $providerName;
        $providerName = strtoupper($providerName);
        $lastName     = strtoupper($lastName);
        //$fullName = $providerName." ".$lastName;
        $userExist = NULL;
        if (!is_null($npiNumber))
            $userExist = User::whereRaw("AES_DECRYPT(facility_npi, '$key') = '$npiNumber'")->first(["first_name", "id"]);
        else {
            if (!is_null($providerName) && !is_null($lastName) && !is_null($dob) && !is_null($email)) {
                $userExist = User::where("first_name", "=", $providerName)
                    ->where("last_name", "=", $lastName)
                    ->whereRaw("AES_DECRYPT(dob, '$key') = '$dob'")
                    ->whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                    ->first(["first_name", "id"]);
            }
        }

        if (!is_object($userExist)) {
            $email = is_null($providerName) ? strtolower($providerName) . "@eclinicassist.com" : $email;
            $addProvider = [
                'first_name' =>  $providerName,
                "last_name" =>   $lastName,
                "email"     =>  DB::raw("AES_ENCRYPT('" .   $email     . "', '$key')"),
                "dob"     =>  DB::raw("AES_ENCRYPT('" .   $dob     . "', '$key')"),
                "password"  => NULL,
                'is_complete' => false,
                "status" => "Active",
                'profile_complete_percentage' => 0,
                'created_at' => $this->timeStamp()
            ];
            if (!is_null($npiNumber)) {
                $addProvider["facility_npi"] = DB::raw("AES_ENCRYPT('" .   $npiNumber     . "', '$key')");
            }
            // $this->printR($addProvider,true);
            $user = User::create($addProvider); //create the facility profile

            $user->createToken($providerName . " Token")->plainTextToken; //create the facility token
            $providerId = $user->id;
        } else {
            $providerId = $userExist->id;
            $providerName = $userExist->first_name;
        }



        $whereLinkedProvider = [
            ["user_id", "=", $providerId],
            ["parent_user_id", "=", $practiceId]
        ];

        $indvPRelation  = [];
        $indvPRelation["user_id"]                 = $providerId;
        $indvPRelation["parent_user_id"]          = $practiceId;


        $hasRec = $this->fetchData("user_dd_individualproviderinfo", $whereLinkedProvider, 1, []);

        if (is_object($hasRec))
            $this->updateData("user_dd_individualproviderinfo", $whereLinkedProvider, $indvPRelation);
        else
            $this->addData("user_dd_individualproviderinfo",  $indvPRelation, 0);

        $compMap = [
            'user_id' => $providerId,
            'company_id' => $companyId
        ];
        $whereCompanyMap = [
            ["user_id", "=", $providerId],
            ["company_id", "=", $companyId]
        ];
        $companyMap = $this->fetchData("user_company_map", $whereCompanyMap, 1, []);
        if (!is_object($companyMap))
            $this->addData("user_company_map", $compMap);

        $whereRoleMap = [
            ["user_id", "=", $providerId],
            ["role_id", "=", 10]
        ];
        $roleMap = $this->fetchData("user_role_map", $whereRoleMap, 1, []);
        if (!is_object($roleMap))
            $this->addData("user_role_map",  ["user_id" => $providerId, "role_id" => 10, "role_preference" => 1], 0); //assign the role the new provier

        $whereProviderMap = [
            ["user_id", "=", $providerId],
            ["location_user_id", "=", $facilityId]
        ];
        $providerMap = $this->fetchData("individualprovider_location_map", $whereProviderMap, 1, []);
        if (!is_object($providerMap))
            $this->addData("individualprovider_location_map", ["user_id" => $providerId, "location_user_id" => $facilityId]);


        $providerRelation = ["practice_id" => $practiceId, "facility_id" => $facilityId, "provider_id" => $providerId, "name" => $providerName];
        $msg = $providerName . " created.";
        if (strlen($msg)) {
            // $this->addDirectoryLogs($providerId, $sessionUserId, $msg, "Provider");
            DB::table("provider_logs")->insertGetId([
                "provider_id" => $providerId, "session_userid" => $sessionUserId, "section" => "Provider Profile",
                "action" => "Add",
                "log" => DB::raw("AES_ENCRYPT('" .    $msg     . "', '$key')"),
                'created_at' => $this->timeStamp()
            ]);
        }

        return $providerRelation;
    }
    /**
     * update the practice data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePractice($practiceId, Request $request)
    {
        $key = $this->key;

        // $clientReqData = $request->all();

        // $this->printR($clientReqData, true);
        $profileCompletePercentage  = $request->profile_complete_percentage;
        $isComplete  = $request->is_complete;
        $status  = $request->status;
        $logMsg = "";

        User::where("id", "=", $practiceId)
            ->update([
                "profile_complete_percentage" => $profileCompletePercentage,
                "is_complete" => $isComplete, "updated_at" => $this->timeStamp(),
                "status" => $status
            ]);

        $npiNumber                  = $request->npi_number;
        $practiceName               = $request->practice_name;
        $doingBusinessAs            = $request->doing_business_as;
        $taxId                      = $request->tax_id;
        $businessEstablishedDate    = $request->business_established_date;
        $taxonomy                   = $request->taxonomy;
        $ownershipStatus            = $request->ownership_status;
        $ownershipClassificationStatus = $request->ownership_classification_status;
        $numberOfOwners             = $request->number_of_owners;
        $totalOwnershipPercentage   = $request->total_ownership_percentage;

        $practiceName = strtoupper($practiceName);
        $doingBusinessAs = strtoupper($doingBusinessAs);

        $servicePlan = $request->service_plan;
        $updateServicePlan = [];

        $updateServicePlan["practice_id"]           = $practiceId;
        $updateServicePlan["service_type"]          = isset($servicePlan["service_type"])       ? $servicePlan["service_type"] : null;
        $updateServicePlan["onboarding_fee"]        = isset($servicePlan["onboarding_fee"])     ? $servicePlan["onboarding_fee"] : null;
        $updateServicePlan["monthly_flat_fee"]      = isset($servicePlan["monthly_flat_fee"])   ? $servicePlan["monthly_flat_fee"] : null;
        $updateServicePlan["billing_percentage"]    = isset($servicePlan["billing_percentage"]) ? $servicePlan["billing_percentage"] : null;
        $updateServicePlan["effective_date"]        = isset($servicePlan["effective_date"])     ? $this->formatDate($servicePlan["effective_date"]) : null;
        $updateServicePlan["agreement_tenure"]      = isset($servicePlan["agreement_tenure"])   ? $servicePlan["agreement_tenure"] : null;
        $updateServicePlan["expiration_date"]       = isset($servicePlan["expiration_date"])    ? $this->formatDate($servicePlan["expiration_date"]) : null;
        $updateServicePlan["plan_auto_renew"]       = isset($servicePlan["plan_auto_renew"])    ? $servicePlan["plan_auto_renew"]  : 0;


        $servicePlanExist =  DB::table("practice_service_plan")->where("practice_id", "=", $practiceId)->first();
        if (is_object($servicePlanExist)) {
            $updateServicePlan["updated_at"] = $this->timeStamp();
            DB::table("practice_service_plan")->where("practice_id", "=", $practiceId)->update($updateServicePlan);
        } else {
            $updateServicePlan["created_at"] = $this->timeStamp();
            DB::table("practice_service_plan")->insertGetId($updateServicePlan);
        }

        $pInfo = DB::table("user_baf_practiseinfo")->where("user_id", "=", $practiceId)
            ->first();
        $updateData = [];
        $updateData["practice_name"]        = $practiceName;
        $updateData["doing_business_as"]    = $doingBusinessAs;
        $updateData["user_id"]              = $practiceId;
        // $this->printR($pInfo,true);
        if (is_object($pInfo)) {
            if ($pInfo->practice_name != $practiceName && !is_null($pInfo->practice_name)) {
                $logMsg .= " Practice name changed from <b>" . $pInfo->practice_name . "</b> to <b>" . $practiceName . "</b> <br>";
            }
            if (is_null($pInfo->practice_name) && !is_null($practiceName) && !empty($practiceName)) {
                $logMsg .= " Practice name assigned to <b>" . $practiceName . "</b> <br>";
            }

            if ($pInfo->doing_business_as != $doingBusinessAs && !is_null($pInfo->doing_business_as)) {
                $logMsg .= " Doing business as changed from <b>" . $pInfo->doing_business_as . "</b> to <b>" . $doingBusinessAs . "</b> <br>";
            }
            if (is_null($pInfo->doing_business_as) && !is_null($doingBusinessAs) && !empty($doingBusinessAs)) {
                $logMsg .= " Doing business as assigned to <b>" . $doingBusinessAs . "</b> <br>";
            }

            $updateData["updated_at"] = $this->timeStamp();
            DB::table("user_baf_practiseinfo")->where("user_id", "=", $practiceId)->update($updateData);
        } else {
            $updateData["created_at"] = $this->timeStamp();
            if (isset($practiceName) && !empty($practiceName))
                $logMsg .= " Practice name assigned to <b>" . $practiceName . "</b> <br>";
            if (isset($doingBusinessAs) && !empty($doingBusinessAs))
                $logMsg .= " Doing business as assigned to <b>" . $doingBusinessAs . "</b> <br>";

            DB::table("user_baf_practiseinfo")->insertGetId($updateData);
        }


        $bInfo = DB::table("user_dd_businessinformation")->where("user_id", "=", $practiceId)
            ->select(
                DB::raw("AES_DECRYPT(facility_npi, '$key') as facility_npi"),
                DB::raw("AES_DECRYPT(facility_tax_id, '$key') as facility_tax_id"),
                "business_established_date",
                "ownership_status",
                "ownership_classification_status",
                "number_of_owners",
                "total_ownership_percentage",
                "taxonomy_code",
                "taxonomy_group",
                "taxonomy_desc",
                "taxonomy_state",
                "taxonomy_license",
                "taxonomy_primary"
            )
            ->first();

        $bInfoData = [];
        $bInfoData["user_id"]                   = $practiceId;
        $bInfoData["facility_npi"]              = isset($npiNumber) ? DB::raw("AES_ENCRYPT('" .    $npiNumber     . "', '$key')") : null;
        $bInfoData["facility_tax_id"]           = isset($taxId) ? DB::raw("AES_ENCRYPT('" .    $taxId     . "', '$key')") : null;
        $bInfoData["business_established_date"] = isset($businessEstablishedDate) ? $businessEstablishedDate : null;
        $bInfoData["ownership_status"]          = isset($ownershipStatus) ? $ownershipStatus : null;
        $bInfoData["ownership_classification_status"] = isset($ownershipClassificationStatus) ? $ownershipClassificationStatus : null;
        $bInfoData["number_of_owners"]          = isset($numberOfOwners) ? $numberOfOwners : null;
        $bInfoData["total_ownership_percentage"] = isset($totalOwnershipPercentage) ? $totalOwnershipPercentage : null;
        $bInfoData["taxonomy_code"]             = isset($taxonomy["code"]) ? $taxonomy["code"] : null;
        $bInfoData["taxonomy_group"]            = isset($taxonomy["taxonomy_group"]) ? $taxonomy["taxonomy_group"] : null;
        $bInfoData["taxonomy_desc"]             = isset($taxonomy["desc"]) ? $taxonomy["desc"] : null;
        $bInfoData["taxonomy_state"]            = isset($taxonomy["state"]) ? $taxonomy["state"] : null;
        $bInfoData["taxonomy_license"]          = isset($taxonomy["license"]) ? $taxonomy["license"] : null;
        $bInfoData["taxonomy_primary"]          = isset($taxonomy["primary"]) ? $taxonomy["primary"] : null;
        // $this->printR($bInfoData,true);
        if (is_object($bInfo)) {
            if ($bInfo->facility_npi != $npiNumber && !is_null($bInfo->facility_npi)) {
                $logMsg .= " NPI number changed from <b>" . $bInfo->facility_npi . "</b> to <b>" . $npiNumber . "</b> <br>";
            }
            if (is_null($bInfo->facility_npi) && !is_null($npiNumber) && !empty($npiNumber)) {
                $logMsg .= " NPI number assigned to <b>" . $npiNumber . "</b> <br>";
            }

            if ($bInfo->facility_tax_id != $taxId && !is_null($bInfo->facility_tax_id)) {
                $logMsg .= " Tax ID number changed from <b>" . $bInfo->facility_tax_id . "</b> to <b>" . $taxId . "</b> <br>";
            }
            if (is_null($bInfo->facility_tax_id) && isset($taxId)) {
                $logMsg .= " Tax ID number assigned to <b>" . $taxId . "</b> <br>";
            }

            if ($bInfo->business_established_date != $businessEstablishedDate && !is_null($bInfo->business_established_date)) {
                $logMsg .= " Business established date changed from <b>" . $bInfo->business_established_date . "</b> to <b>" . $businessEstablishedDate . "</b> <br>";
            }
            if (is_null($bInfo->business_established_date) && isset($businessEstablishedDate)) {
                $logMsg .= " Business established date assigned to <b>" . $businessEstablishedDate . "</b> <br>";
            }

            if ($bInfo->ownership_status != $ownershipStatus && !is_null($bInfo->ownership_status)) {
                $logMsg .= " Ownership status changed from <b>" . $bInfo->ownership_status . "</b> to <b>" . $ownershipStatus . "</b> <br>";
            }
            if (is_null($bInfo->ownership_status) && !is_null($ownershipStatus) && !empty($ownershipStatus)) {
                $logMsg .= " Ownership status assigned to <b>" . $ownershipStatus . "</b> <br>";
            }

            if ($bInfo->ownership_classification_status != $ownershipClassificationStatus && !is_null($bInfo->ownership_classification_status)) {
                $logMsg .= " Ownership classification status changed from <b>" . $bInfo->ownership_classification_status . "</b> to <b>" . $ownershipClassificationStatus . "</b> <br>";
            }
            if (is_null($bInfo->ownership_classification_status) && !is_null($ownershipClassificationStatus) && !empty($ownershipClassificationStatus)) {
                $logMsg .= " Ownership classification status assigned to <b>" . $ownershipClassificationStatus . "</b> <br>";
            }

            if ($bInfo->number_of_owners != $numberOfOwners && !is_null($bInfo->number_of_owners)) {
                $logMsg .= " Number of owners changed from <b>" . $bInfo->number_of_owners . "</b> to <b>" . $numberOfOwners . "</b> <br>";
            }
            if (is_null($bInfo->number_of_owners) && !is_null($numberOfOwners) && !empty($numberOfOwners)) {
                $logMsg .= " Number of owners assigned to <b>" . $numberOfOwners . "</b> <br>";
            }
            if ($bInfo->total_ownership_percentage != $totalOwnershipPercentage && !is_null($bInfo->total_ownership_percentage)) {
                $logMsg .= " Total ownership percentage changed from <b>" . $bInfo->total_ownership_percentage . "</b> to <b>" . $totalOwnershipPercentage . "</b> <br>";
            }
            if (is_null($bInfo->total_ownership_percentage) && !is_null($totalOwnershipPercentage) && !empty($totalOwnershipPercentage)) {
                $logMsg .= " Total ownership percentage assigned to <b>" . $totalOwnershipPercentage . "</b> <br>";
            }
            if ($bInfo->taxonomy_code != $taxonomy["code"] && !is_null($bInfo->taxonomy_code)) {
                $logMsg .= " Taxonomy code changed from <b>" . $bInfo->taxonomy_code . "</b> to <b>" . $taxonomy["code"] . "</b> <br>";
            }
            if (is_null($bInfo->taxonomy_code) && !is_null($taxonomy["code"]) && !empty($taxonomy["code"])) {
                $logMsg .= " Taxonomy code assigned to <b>" . $taxonomy["code"] . "</b> <br>";
            }
            if ($bInfo->taxonomy_group != $taxonomy["taxonomy_group"] && !is_null($bInfo->taxonomy_group)) {
                $logMsg .= " Taxonomy group changed from <b>" . $bInfo->taxonomy_group . "</b> to <b>" . $taxonomy["taxonomy_group"] . "</b> <br>";
            }
            if (is_null($bInfo->taxonomy_group) && !is_null($taxonomy["taxonomy_group"]) && !empty($taxonomy["taxonomy_group"])) {
                $logMsg .= " Taxonomy group assigned to <b>" . $taxonomy["taxonomy_group"] . "</b> <br>";
            }
            if ($bInfo->taxonomy_desc != $taxonomy["desc"] && !is_null($bInfo->taxonomy_desc)) {
                $logMsg .= " Taxonomy description changed from <b>" . $bInfo->taxonomy_desc . "</b> to <b>" . $taxonomy["desc"] . "</b> <br>";
            }
            if (is_null($bInfo->taxonomy_desc) && !is_null($taxonomy["desc"]) && !empty($taxonomy["desc"])) {
                $logMsg .= " Taxonomy description assigned to <b>" . $taxonomy["desc"] . "</b> <br>";
            }
            if ($bInfo->taxonomy_state != $taxonomy["state"] && !is_null($bInfo->taxonomy_state)) {
                $logMsg .= " Taxonomy state changed from <b>" . $bInfo->taxonomy_state . "</b> to <b>" . $taxonomy["state"] . "</b> <br>";
            }
            if (is_null($bInfo->taxonomy_state) && !is_null($taxonomy["state"]) && !empty($taxonomy["state"])) {
                $logMsg .= " Taxonomy state assigned to <b>" . $taxonomy["state"] . "</b> <br>";
            }
            if ($bInfo->taxonomy_license != $taxonomy["license"] && !is_null($bInfo->taxonomy_license)) {
                $logMsg .= " Taxonomy license changed from <b>" . $bInfo->taxonomy_license . "</b> to <b>" . $taxonomy["license"] . "</b> <br>";
            }
            if (is_null($bInfo->taxonomy_license) && !is_null($taxonomy["license"]) && !empty($taxonomy["license"])) {
                $logMsg .= " Taxonomy license assigned to <b>" . $taxonomy["license"] . "</b> <br>";
            }
            if ($bInfo->taxonomy_primary != $taxonomy["primary"] && !is_null($bInfo->taxonomy_primary)) {
                $logMsg .= " Taxonomy primary changed from <b>" . $bInfo->taxonomy_primary . "</b> to <b>" . $taxonomy["primary"] . "</b> <br>";
            }
            if (is_null($bInfo->taxonomy_primary) && !is_null($taxonomy["primary"]) && !empty($taxonomy["primary"])) {
                $logMsg .= " Taxonomy primary assigned to <b>" . $taxonomy["primary"] . "</b> <br>";
            }
            $bInfoData["updated_at"]          = $this->timeStamp();
            DB::table("user_dd_businessinformation")->where("user_id", "=", $practiceId)->update($bInfoData);
        } else {
            $bInfoData["created_at"]          = $this->timeStamp();
            if (isset($npiNumber) && !empty($npiNumber))
                $logMsg .= " NPI number assigned to <b>" . $npiNumber . "</b> <br>";
            if (isset($taxId) && !empty($taxId))
                $logMsg .= " Tax ID number assigned to <b>" . $taxId . "</b> <br>";
            if (isset($businessEstablishedDate) && !empty($businessEstablishedDate))
                $logMsg .= " Business established date assigned to <b>" . $businessEstablishedDate . "</b> <br>";
            if (isset($ownershipStatus) && !empty($ownershipStatus))
                $logMsg .= " Ownership status assigned to <b>" . $ownershipStatus . "</b> <br>";
            if (isset($ownershipClassificationStatus) && !empty($ownershipClassificationStatus))
                $logMsg .= " Ownership classification status assigned to <b>" . $ownershipClassificationStatus . "</b> <br>";
            if (isset($numberOfOwners) && !empty($numberOfOwners))
                $logMsg .= " Number of owners assigned to <b>" . $numberOfOwners . "</b> <br>";
            if (isset($totalOwnershipPercentage) && !empty($totalOwnershipPercentage))
                $logMsg .= " Total ownership percentage assigned to <b>" . $totalOwnershipPercentage . "</b> <br>";
            if (isset($taxonomy["code"]) && !empty($taxonomy["code"]))
                $logMsg .= " Taxonomy code assigned to <b>" . $taxonomy["code"] . "</b> <br>";
            if (isset($taxonomy["taxonomy_group"]) && !empty($taxonomy["taxonomy_group"]))
                $logMsg .= " Taxonomy group assigned to <b>" . $taxonomy["taxonomy_group"] . "</b> <br>";
            if (isset($taxonomy["desc"]) && !empty($taxonomy["desc"]))
                $logMsg .= " Taxonomy description assigned to <b>" . $taxonomy["desc"] . "</b> <br>";
            if (isset($taxonomy["state"]) && !empty($taxonomy["state"]))
                $logMsg .= " Taxonomy state assigned to <b>" . $taxonomy["state"] . "</b> <br>";
            if (isset($taxonomy["license"]) && !empty($taxonomy["license"]))
                $logMsg .= " Taxonomy license assigned to <b>" . $taxonomy["license"] . "</b> <br>";
            if (isset($taxonomy["primary"]) && !empty($taxonomy["primary"]))
                $logMsg .= " Taxonomy primary assigned to <b>" . $taxonomy["primary"] . "</b> <br>";


            DB::table("user_dd_businessinformation")->insertGetId($bInfoData);
        }

        $contactInformation         = $request->contact_information;
        $contactUpdateData = [];
        $contactUpdateData["user_id"]                       = $practiceId;
        $contactUpdateData["contact_person_name"]           = isset($contactInformation["name"]) ? $contactInformation["name"] : null;
        $contactUpdateData["contact_person_email"]          = isset($contactInformation["email"]) ? $contactInformation["email"] : null;
        $contactUpdateData["contact_person_phone"]          = isset($contactInformation["phone_number"]) ? $this->sanitizePhoneNumber($contactInformation["phone_number"]) : null;
        $contactUpdateData["contact_person_designation"]    = isset($contactInformation["title"]) ? $contactInformation["title"] : null;
        $contactUpdateData["contact_person_fax"]            = isset($contactInformation["fax_number"]) ? $contactInformation["fax_number"] : null;
        $contactInfo = DB::table("user_baf_contactinfo")->where("user_id", "=", $practiceId)->first();
        if (is_object($contactInfo)) {
            if ($contactInfo->contact_person_name != $contactUpdateData["contact_person_name"] && !is_null($contactInfo->contact_person_name)) {
                $logMsg .= " Contact person name changed from <b>" . $contactInfo->contact_person_name . "</b> to <b>" . $contactUpdateData["contact_person_name"] . "</b> <br>";
            }
            if (is_null($contactInfo->contact_person_name) && !is_null($contactUpdateData["contact_person_name"]) && !empty($contactUpdateData["contact_person_name"])) {
                $logMsg .= " Contact person name assigned to <b>" . $contactUpdateData["contact_person_name"] . "</b> <br>";
            }
            if ($contactInfo->contact_person_email != $contactUpdateData["contact_person_email"] && !is_null($contactInfo->contact_person_email)) {
                $logMsg .= " Contact person email changed from <b>" . $contactInfo->contact_person_email . "</b> to <b>" . $contactUpdateData["contact_person_email"] . "</b> <br>";
            }
            if (is_null($contactInfo->contact_person_email) && !is_null($contactUpdateData["contact_person_email"]) && !empty($contactUpdateData["contact_person_email"])) {
                $logMsg .= " Contact person email assigned to <b>" . $contactUpdateData["contact_person_email"] . "</b> <br>";
            }
            if ($contactInfo->contact_person_phone != $contactUpdateData["contact_person_phone"] && !is_null($contactInfo->contact_person_phone)) {
            }
            if (is_null($contactInfo->contact_person_phone) && !is_null($contactUpdateData["contact_person_phone"]) && !empty($contactUpdateData["contact_person_phone"])) {
                $logMsg .= " Contact person phone assigned to <b>" . $contactUpdateData["contact_person_phone"] . "</b> <br>";
            }
            if ($contactInfo->contact_person_designation != $contactUpdateData["contact_person_designation"] && !is_null($contactInfo->contact_person_designation)) {
                $logMsg .= " Contact person designation changed from <b>" . $contactInfo->contact_person_designation . "</b> to <b>" . $contactUpdateData["contact_person_designation"] . "</b> <br>";
            }
            if (is_null($contactInfo->contact_person_designation) && !is_null($contactUpdateData["contact_person_designation"]) && !empty($contactUpdateData["contact_person_designation"])) {
                $logMsg .= " Contact person designation assigned to <b>" . $contactUpdateData["contact_person_designation"] . "</b> <br>";
            }
            if ($contactInfo->contact_person_fax != $contactUpdateData["contact_person_fax"] && !is_null($contactInfo->contact_person_fax)) {
                $logMsg .= " Contact person fax changed from <b>" . $contactInfo->contact_person_fax . "</b> to <b>" . $contactUpdateData["contact_person_fax"] . "</b> <br>";
            }
            if (is_null($contactInfo->contact_person_fax) && !is_null($contactUpdateData["contact_person_fax"]) && !empty($contactUpdateData["contact_person_fax"])) {
                $logMsg .= " Contact person fax assigned to <b>" . $contactUpdateData["contact_person_fax"] . "</b> <br>";
            }

            $contactUpdateData["updated_at"]            = $this->timeStamp();
            DB::table("user_baf_contactinfo")->where("user_id", "=", $practiceId)->update($contactUpdateData);
        } else {
            if (isset($contactUpdateData["contact_person_name"]) && !empty($contactUpdateData["contact_person_name"]))
                $logMsg .= " Contact person name assigned to <b>" . $contactUpdateData["contact_person_name"] . "</b> <br>";
            if (isset($contactUpdateData["contact_person_email"]) && !empty($contactUpdateData["contact_person_email"]))
                $logMsg .= " Contact person email assigned to <b>" . $contactUpdateData["contact_person_email"] . "</b> <br>";
            if (isset($contactUpdateData["contact_person_phone"]) && !empty($contactUpdateData["contact_person_phone"]))
                $logMsg .= " Contact person phone assigned to <b>" . $contactUpdateData["contact_person_phone"] . "</b> <br>";
            if (isset($contactUpdateData["contact_person_designation"]) && !empty($contactUpdateData["contact_person_designation"]))
                $logMsg .= " Contact person designation assigned to <b>" . $contactUpdateData["contact_person_designation"] . "</b> <br>";
            if (isset($contactUpdateData["contact_person_fax"]) && !empty($contactUpdateData["contact_person_fax"]))
                $logMsg .= " Contact person fax assigned to <b>" . $contactUpdateData["contact_person_fax"] . "</b> <br>";

            $contactUpdateData["created_at"]            = $this->timeStamp();
            DB::table("user_baf_contactinfo")->insertGetId($contactUpdateData);
        }
        $contactInfo = DB::table("user_baf_contactinfo")->where("user_id", "=", $practiceId)->first();

        $mailingAddress             = $request->mailing_address;
        $mailingUpdateData = [];
        $mailingUpdateData["user_id"]                           = $practiceId;
        $mailingUpdateData["mailing_address_zip_five"]          = isset($mailingAddress["zip_five"]) ? $mailingAddress["zip_five"] : null;
        $mailingUpdateData["mailing_address_zip_four"]          = isset($mailingAddress["zip_four"]) ? $mailingAddress["zip_four"] : null;
        $mailingUpdateData["mailing_address_street_address"]    = isset($mailingAddress["street_address"]) ? $mailingAddress["street_address"] : null;
        $mailingUpdateData["mailing_address_country"]           = isset($mailingAddress["country"]) ? $mailingAddress["country"] : null;
        $mailingUpdateData["mailing_address_city"]              = isset($mailingAddress["city"]) ? $mailingAddress["city"] : null;
        $mailingUpdateData["mailing_address_state"]             = isset($mailingAddress["state"]) ? $mailingAddress["state"] : null;
        $mailingUpdateData["mailing_address_state_code"]        = isset($mailingAddress["state_code"]) ? $mailingAddress["state_code"] : null;
        $mailingUpdateData["mailing_address_county"]            = isset($mailingAddress["county"]) ? $mailingAddress["county"] : null;
        $mailingUpdateData["mailing_address_phone_number"]      = isset($mailingAddress["phone_number"]) ? $this->sanitizePhoneNumber($mailingAddress["phone_number"]) : null;
        $mailingUpdateData["mailing_address_fax_number"]        = isset($mailingAddress["fax_number"]) ? $mailingAddress["fax_number"] : null;
        // $this->printR($mailingUpdateData,true);

        if (is_object($contactInfo)) {
            if ($contactInfo->mailing_address_zip_five != $mailingUpdateData["mailing_address_zip_five"] && !is_null($contactInfo->mailing_address_zip_five)) {
                $logMsg .= " Mailing address zip five changed from <b>" . $contactInfo->mailing_address_zip_five . "</b> to <b>" . $mailingUpdateData["mailing_address_zip_five"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_zip_five) && !is_null($mailingUpdateData["mailing_address_zip_five"]) && !empty($mailingUpdateData["mailing_address_zip_five"])) {
                $logMsg .= " Mailing address zip five assigned to <b>" . $mailingUpdateData["mailing_address_zip_five"] . "</b> <br>";
            }
            // if ($contactInfo->mailing_address_zip_four != $mailingUpdateData["mailing_address_zip_four"] && !is_null($contactInfo->mailing_address_zip_four)) {
            //     $logMsg .= " Mailing address zip four changed from <b>" . $contactInfo->mailing_address_zip_four . "</b> to <b>" . $mailingUpdateData["mailing_address_zip_four"] . "</b> <br>";
            // }
            // if (is_null($contactInfo->mailing_address_zip_four) && !is_null($mailingUpdateData["mailing_address_zip_four"]) && !empty($mailingUpdateData["mailing_address_zip_four"])) {
            //     $logMsg .= " Mailing address zip four assigned to <b>" . $mailingUpdateData["mailing_address_zip_four"] . "</b> <br>";
            // }
            if ($contactInfo->mailing_address_street_address != $mailingUpdateData["mailing_address_street_address"] && !is_null($contactInfo->mailing_address_street_address)) {
                $logMsg .= " Mailing address street address changed from <b>" . $contactInfo->mailing_address_street_address . "</b> to <b>" . $mailingUpdateData["mailing_address_street_address"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_street_address) && !is_null($mailingUpdateData["mailing_address_street_address"]) && !empty($mailingUpdateData["mailing_address_street_address"])) {
                $logMsg .= " Mailing address street address assigned to <b>" . $mailingUpdateData["mailing_address_street_address"] . "</b> <br>";
            }
            if ($contactInfo->mailing_address_country != $mailingUpdateData["mailing_address_country"] && !is_null($contactInfo->mailing_address_country)) {
                $logMsg .= " Mailing address country changed from <b>" . $contactInfo->mailing_address_country . "</b> to <b>" . $mailingUpdateData["mailing_address_country"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_country) && !is_null($mailingUpdateData["mailing_address_country"]) && !empty($mailingUpdateData["mailing_address_country"])) {
                $logMsg .= " Mailing address country assigned to <b>" . $mailingUpdateData["mailing_address_country"] . "</b> <br>";
            }
            if ($contactInfo->mailing_address_city != $mailingUpdateData["mailing_address_city"] && !is_null($contactInfo->mailing_address_city)) {
                $logMsg .= " Mailing address city changed from <b>" . $contactInfo->mailing_address_city . "</b> to <b>" . $mailingUpdateData["mailing_address_city"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_city) && !is_null($mailingUpdateData["mailing_address_city"]) && !empty($mailingUpdateData["mailing_address_city"])) {
                $logMsg .= " Mailing address city assigned to <b>" . $mailingUpdateData["mailing_address_city"] . "</b> <br>";
            }
            if ($contactInfo->mailing_address_state != $mailingUpdateData["mailing_address_state"] && !is_null($contactInfo->mailing_address_state)) {
                $logMsg .= " Mailing address state changed from <b>" . $contactInfo->mailing_address_state . "</b> to <b>" . $mailingUpdateData["mailing_address_state"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_state) && !is_null($mailingUpdateData["mailing_address_state"]) && !empty($mailingUpdateData["mailing_address_state"])) {
                $logMsg .= " Mailing address state assigned to <b>" . $mailingUpdateData["mailing_address_state"] . "</b> <br>";
            }
            if ($contactInfo->mailing_address_state_code != $mailingUpdateData["mailing_address_state_code"] && !is_null($contactInfo->mailing_address_state_code)) {
                $logMsg .= " Mailing address state code changed from <b>" . $contactInfo->mailing_address_state_code . "</b> to <b>" . $mailingUpdateData["mailing_address_state_code"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_state_code) && !is_null($mailingUpdateData["mailing_address_state_code"]) && !empty($mailingUpdateData["mailing_address_state_code"])) {
                $logMsg .= " Mailing address state code assigned to <b>" . $mailingUpdateData["mailing_address_state_code"] . "</b> <br>";
            }
            if ($contactInfo->mailing_address_county != $mailingUpdateData["mailing_address_county"] && !is_null($contactInfo->mailing_address_county)) {
                $logMsg .= " Mailing address county changed from <b>" . $contactInfo->mailing_address_county . "</b> to <b>" . $mailingUpdateData["mailing_address_county"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_county) && !is_null($mailingUpdateData["mailing_address_county"]) && !empty($mailingUpdateData["mailing_address_county"])) {
                $logMsg .= " Mailing address county assigned to <b>" . $mailingUpdateData["mailing_address_county"] . "</b> <br>";
            }
            if ($contactInfo->mailing_address_phone_number != $mailingUpdateData["mailing_address_phone_number"] && !is_null($contactInfo->mailing_address_phone_number)) {
                $logMsg .= " Mailing address phone number changed from <b>" . $contactInfo->mailing_address_phone_number . "</b> to <b>" . $mailingUpdateData["mailing_address_phone_number"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_phone_number) && !is_null($mailingUpdateData["mailing_address_phone_number"]) && !empty($mailingUpdateData["mailing_address_phone_number"])) {
                $logMsg .= " Mailing address phone number assigned to <b>" . $mailingUpdateData["mailing_address_phone_number"] . "</b> <br>";
            }
            if ($contactInfo->mailing_address_fax_number != $mailingUpdateData["mailing_address_fax_number"] && !is_null($contactInfo->mailing_address_fax_number)) {
                $logMsg .= " Mailing address fax number changed from <b>" . $contactInfo->mailing_address_fax_number . "</b> to <b>" . $mailingUpdateData["mailing_address_fax_number"] . "</b> <br>";
            }
            if (is_null($contactInfo->mailing_address_fax_number) && !is_null($mailingUpdateData["mailing_address_fax_number"]) && !empty($mailingUpdateData["mailing_address_fax_number"])) {
                $logMsg .= " Mailing address fax number assigned to <b>" . $mailingUpdateData["mailing_address_fax_number"] . "</b> <br>";
            }

            $mailingUpdateData["updated_at"] = $this->timeStamp();
            DB::table("user_baf_contactinfo")->where("user_id", "=", $practiceId)->update($mailingUpdateData);
        } else {
            if (isset($mailingUpdateData["mailing_address_zip_five"]) && !empty($mailingUpdateData["mailing_address_zip_five"]))
                $logMsg .= " Mailing address zip five assigned to <b>" . $mailingUpdateData["mailing_address_zip_five"] . "</b> <br>";
            // if (isset($mailingUpdateData["mailing_address_zip_four"]) && !empty($mailingUpdateData["mailing_address_zip_four"]))
            //     $logMsg .= " Mailing address zip four assigned to <b>" . $mailingUpdateData["mailing_address_zip_four"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_street_address"]) && !empty($mailingUpdateData["mailing_address_street_address"]))
                $logMsg .= " Mailing address street address assigned to <b>" . $mailingUpdateData["mailing_address_street_address"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_country"]) && !empty($mailingUpdateData["mailing_address_country"]))
                $logMsg .= " Mailing address country assigned to <b>" . $mailingUpdateData["mailing_address_country"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_city"]) && !empty($mailingUpdateData["mailing_address_city"]))
                $logMsg .= " Mailing address city assigned to <b>" . $mailingUpdateData["mailing_address_city"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_state"]) && !empty($mailingUpdateData["mailing_address_state"]))
                $logMsg .= " Mailing address state assigned to <b>" . $mailingUpdateData["mailing_address_state"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_state_code"]) && !empty($mailingUpdateData["mailing_address_state_code"]))
                $logMsg .= " Mailing address state code assigned to <b>" . $mailingUpdateData["mailing_address_state_code"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_county"]) && !empty($mailingUpdateData["mailing_address_county"]))
                $logMsg .= " Mailing address county assigned to <b>" . $mailingUpdateData["mailing_address_county"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_phone_number"]) && !empty($mailingUpdateData["mailing_address_phone_number"]))
                $logMsg .= " Mailing address phone number assigned to <b>" . $mailingUpdateData["mailing_address_phone_number"] . "</b>  <br>";
            if (isset($mailingUpdateData["mailing_address_fax_number"]) && !empty($mailingUpdateData["mailing_address_fax_number"]))
                $logMsg .= " Mailing address fax number assigned to <b>" . $mailingUpdateData["mailing_address_fax_number"] . "</b>  <br>";

            $mailingUpdateData["created_at"] = $this->timeStamp();
            DB::table("user_baf_contactinfo")->insertGetId($mailingUpdateData);
        }

        $contactInfo = DB::table("user_baf_contactinfo")->where("user_id", "=", $practiceId)->first();
        // $this->printR($mailingUpdateData,true);
        $primaryFacilityAddress     = $request->primary_facility_address;
        // $this->printR($primaryFacilityAddress,true);
        $primaryUpdateData = [];
        $primaryUpdateData["user_id"]                           = $practiceId;
        $primaryUpdateData["zip_five"]          = isset($primaryFacilityAddress["zip_five"]) ? $primaryFacilityAddress["zip_five"] : null;
        $primaryUpdateData["zip_four"]          = isset($primaryFacilityAddress["zip_four"]) ? $primaryFacilityAddress["zip_four"] : null;
        $primaryUpdateData["street_address"]    = isset($primaryFacilityAddress["street_address"]) ? $primaryFacilityAddress["street_address"] : null;
        $primaryUpdateData["country"]           = isset($primaryFacilityAddress["country"]) ? $primaryFacilityAddress["country"] : null;
        $primaryUpdateData["city"]              = isset($primaryFacilityAddress["city"]) ? $primaryFacilityAddress["city"] : null;
        $primaryUpdateData["state"]             = isset($primaryFacilityAddress["state"]) ? $primaryFacilityAddress["state"] : null;
        $primaryUpdateData["state_code"]        = isset($primaryFacilityAddress["state_code"]) ? $primaryFacilityAddress["state_code"] : null;
        $primaryUpdateData["county"]            = isset($primaryFacilityAddress["county"]) ? $primaryFacilityAddress["county"] : null;
        $primaryUpdateData["phone"]             = isset($primaryFacilityAddress["phone_number"]) ? $this->sanitizePhoneNumber($primaryFacilityAddress["phone_number"]) : null;
        $primaryUpdateData["fax"]               = isset($primaryFacilityAddress["fax_number"]) ?  $primaryFacilityAddress["fax_number"] : null;
        $primaryUpdateData["is_primary"]        = isset($primaryFacilityAddress["is_Checked"]) && ($primaryFacilityAddress["is_Checked"] == "true" || $primaryFacilityAddress["is_Checked"] == 1) ?  1 : 0;

        if (is_object($contactInfo)) {
            if ($contactInfo->zip_five != $primaryUpdateData["zip_five"] && !is_null($contactInfo->zip_five)) {
                $logMsg .= " Primary facility address zip five changed from <b>" . $contactInfo->zip_five . "</b> to <b>" . $primaryUpdateData["zip_five"] . "</b> <br>";
            }
            if (is_null($contactInfo->zip_five) && !is_null($primaryUpdateData["zip_five"]) && !empty($primaryUpdateData["zip_five"])) {
                $logMsg .= " Primary facility address zip five assigned to <b>" . $primaryUpdateData["zip_five"] . "</b> <br>";
            }
            // if ($contactInfo->zip_four != $primaryUpdateData["zip_four"] && !is_null($contactInfo->zip_four)) {
            //     $logMsg .= " Primary facility address zip four changed from <b>" . $contactInfo->zip_four . "</b> to <b>" . $primaryUpdateData["zip_four"] . "</b> <br>";
            // }
            // if (is_null($contactInfo->zip_four) && !is_null($primaryUpdateData["zip_four"]) && !empty($primaryUpdateData["zip_four"])) {
            //     $logMsg .= " Primary facility address zip four assigned to <b>" . $primaryUpdateData["zip_four"] . "</b> <br>";
            // }
            if ($contactInfo->street_address != $primaryUpdateData["street_address"] && !is_null($contactInfo->street_address)) {
                $logMsg .= " Primary facility address street address changed from <b>" . $contactInfo->street_address . "</b> to <b>" . $primaryUpdateData["street_address"] . "</b> <br>";
            }
            if (is_null($contactInfo->street_address) && !is_null($primaryUpdateData["street_address"]) && !empty($primaryUpdateData["street_address"])) {
                $logMsg .= " Primary facility address street address assigned to <b>" . $primaryUpdateData["street_address"] . "</b> <br>";
            }
            if ($contactInfo->country != $primaryUpdateData["country"] && !is_null($contactInfo->country)) {
                $logMsg .= " Primary facility address country changed from <b>" . $contactInfo->country . "</b> to <b>" . $primaryUpdateData["country"] . "</b> <br>";
            }
            if (is_null($contactInfo->country) && !is_null($primaryUpdateData["country"]) && !empty($primaryUpdateData["country"])) {
                $logMsg .= " Primary facility address country assigned to <b>" . $primaryUpdateData["country"] . "</b> <br>";
            }
            if ($contactInfo->city != $primaryUpdateData["city"] && !is_null($contactInfo->city)) {
                $logMsg .= " Primary facility address city changed from <b>" . $contactInfo->city . "</b> to <b>" . $primaryUpdateData["city"] . "</b> <br>";
            }
            if (is_null($contactInfo->city) && !is_null($primaryUpdateData["city"]) && !empty($primaryUpdateData["city"])) {
                $logMsg .= " Primary facility address city assigned to <b>" . $primaryUpdateData["city"] . "</b> <br>";
            }
            if ($contactInfo->state != $primaryUpdateData["state"] && !is_null($contactInfo->state)) {
                $logMsg .= " Primary facility address state changed from <b>" . $contactInfo->state . "</b> to <b>" . $primaryUpdateData["state"] . "</b> <br>";
            }
            if (is_null($contactInfo->state) && !is_null($primaryUpdateData["state"]) && !empty($primaryUpdateData["state"])) {
                $logMsg .= " Primary facility address state assigned to <b>" . $primaryUpdateData["state"] . "</b> <br>";
            }
            if ($contactInfo->state_code != $primaryUpdateData["state_code"] && !is_null($contactInfo->state_code)) {
                $logMsg .= " Primary facility address state code changed from <b>" . $contactInfo->state_code . "</b> to <b>" . $primaryUpdateData["state_code"] . "</b> <br>";
            }
            if (is_null($contactInfo->state_code) && !is_null($primaryUpdateData["state_code"]) && !empty($primaryUpdateData["state_code"])) {
                $logMsg .= " Primary facility address state code assigned to <b>" . $primaryUpdateData["state_code"] . "</b> <br>";
            }
            if ($contactInfo->county != $primaryUpdateData["county"] && !is_null($contactInfo->county)) {
                $logMsg .= " Primary facility address county changed from <b>" . $contactInfo->county . "</b> to <b>" . $primaryUpdateData["county"] . "</b> <br>";
            }
            if (is_null($contactInfo->county) && !is_null($primaryUpdateData["county"]) && !empty($primaryUpdateData["county"])) {
                $logMsg .= " Primary facility address county assigned to <b>" . $primaryUpdateData["state_code"] . "</b> <br>";
            }
            if ($contactInfo->phone != $primaryUpdateData["phone"] && !is_null($contactInfo->phone)) {
                $logMsg .= " Primary facility address phone number changed from <b>" . $contactInfo->phone . "</b> to <b>" . $primaryUpdateData["phone"] . "</b> <br>";
            }
            if (is_null($contactInfo->phone) && !is_null($primaryUpdateData["phone"]) && !empty($primaryUpdateData["phone"])) {
                $logMsg .= " Primary facility address phone number assigned to <b>" . $primaryUpdateData["phone"] . "</b> <br>";
            }
            if ($contactInfo->fax != $primaryUpdateData["fax"] && !is_null($contactInfo->fax)) {
                $logMsg .= " Primary facility address fax number changed from <b>" . $contactInfo->fax . "</b> to <b>" . $primaryUpdateData["fax"] . "</b> <br>";
            }
            if (is_null($contactInfo->fax) && !is_null($primaryUpdateData["fax"]) && !empty($primaryUpdateData["fax"])) {
                $logMsg .= " Primary facility address fax number assigned to <b>" . $primaryUpdateData["fax"] . "</b> <br>";
            }
            $primaryUpdateData["updated_at"]        = $this->timeStamp();
            DB::table("user_baf_contactinfo")->where("user_id", "=", $practiceId)->update($primaryUpdateData);
        } else {
            if (isset($primaryUpdateData["zip_five"]) && !empty($primaryUpdateData["zip_five"]))
                $logMsg .= " Primary facility address zip five assigned to <b>" . $primaryUpdateData["zip_five"] . "</b> <br>";
            // if (isset($primaryUpdateData["zip_four"]) && !empty($primaryUpdateData["zip_four"]))
            //     $logMsg .= " Primary facility address zip four assigned to <b>" . $primaryUpdateData["zip_four"] . "</b> <br>";
            if (isset($primaryUpdateData["street_address"]) && !empty($primaryUpdateData["street_address"]))
                $logMsg .= " Primary facility address street address assigned to <b>" . $primaryUpdateData["street_address"] . "</b> <br>";
            if (isset($primaryUpdateData["country"]) && !empty($primaryUpdateData["country"]))
                $logMsg .= " Primary facility address country assigned to <b>" . $primaryUpdateData["country"] . "</b> <br>";
            if (isset($primaryUpdateData["city"]) && !empty($primaryUpdateData["city"]))
                $logMsg .= " Primary facility address city assigned to <b>" . $primaryUpdateData["city"] . "</b> <br>";
            if (isset($primaryUpdateData["state"]) && !empty($primaryUpdateData["state"]))
                $logMsg .= " Primary facility address state assigned to <b>" . $primaryUpdateData["state"] . "</b> <br>";
            if (isset($primaryUpdateData["state_code"]) && !empty($primaryUpdateData["state_code"]))
                $logMsg .= " Primary facility address state code assigned to <b>" . $primaryUpdateData["state_code"] . "</b> <br>";
            if (isset($primaryUpdateData["county"]) && !empty($primaryUpdateData["county"]))
                $logMsg .= " Primary facility address county assigned to <b>" . $primaryUpdateData["county"] . "</b> <br>";
            if (isset($primaryUpdateData["phone"]) && !empty($primaryUpdateData["phone"]))
                $logMsg .= " Primary facility address phone number assigned to <b>" . $primaryUpdateData["phone"] . "</b> <br>";
            if (isset($primaryUpdateData["fax"]) && !empty($primaryUpdateData["fax"]))
                $logMsg .= " Primary facility address fax number assigned to <b>" . $primaryUpdateData["fax"] . "</b> <br>";

            $primaryUpdateData["created_at"]        = $this->timeStamp();
            DB::table("user_baf_contactinfo")->insertGetId($primaryUpdateData);
        }
        // exit("Done");
        $ownershipInformation       = $request->ownership_information;
        if (count($ownershipInformation)) {
            foreach ($ownershipInformation as $ownership) {
                // $this->printR($ownership,true);
                $ownershipArr = [];
                if (isset($ownership['name_of_owner']) || isset($ownership['email']) || isset($ownership['ssn_number'])) {
                    $ownershipArr['name']              = strtoupper($ownership['name_of_owner']);
                    $ownershipArr['email']             = isset($ownership["address"]['email']) ? DB::raw("AES_ENCRYPT('" .    $ownership["address"]['email']     . "', '$key')") : NULL;
                    $ownershipArr['pob']               =  isset($ownership['place_of_birth']) ? $ownership['place_of_birth'] : NULL;
                    $ownershipArr['dob']               =  isset($ownership['date_of_birth']) ? DB::raw("AES_ENCRYPT('" .    $ownership['date_of_birth']     . "', '$key')") : NULL;
                    $ownershipArr['ssn']               =  isset($ownership['ssn_number']) ? DB::raw("AES_ENCRYPT('" .    $ownership['ssn_number']     . "', '$key')") : NULL;
                    $ownershipArr['address']            =  isset($ownership["address"]['street_address']) ? DB::raw("AES_ENCRYPT('" .    $ownership["address"]['street_address']     . "', '$key')") : NULL;
                    $ownershipArr['phone']             =  isset($ownership["address"]['phone_number']) ? DB::raw("AES_ENCRYPT('" .    $this->sanitizePhoneNumber($ownership["address"]['phone_number'])     . "', '$key')") : NULL;
                    $ownershipArr['fax']               =  isset($ownership["address"]['fax_number']) ? DB::raw("AES_ENCRYPT('" .    $ownership["address"]['fax_number']     . "', '$key')") : NULL;
                    $ownershipArr['city']              = isset($ownership["address"]["city"]) ? $ownership["address"]["city"] : NULL;
                    $ownershipArr['state']             = isset($ownership["address"]["state"]) ? $ownership["address"]["state"] : NULL;
                    $ownershipArr['zip_five']          = isset($ownership["address"]["zip_five"]) ? $ownership["address"]["zip_five"] : NULL;
                    $ownershipArr['zip_four']          = isset($ownership["address"]["zip_four"]) ? $ownership["address"]["zip_four"] : NULL;
                    $ownershipArr['country']           = isset($ownership["address"]["country"]) ? $ownership["address"]["country"] : NULL;
                    $ownershipArr['county']            = isset($ownership["address"]["county"]) ? $ownership["address"]["county"] : NULL;
                    //$ownershipArr['state_code']        = isset($ownership["address"]["state_code"]) ? $ownership["address"]["state_code"] : NULL;

                    $email = $ownership["address"]['email'];
                    $selCols = [
                        "id", "parent_user_id", "is_partnership",
                        "name", "num_of_owners", "effective_date", "ownership_percentage",
                        DB::raw("AES_DECRYPT(email, '$key') as email"),
                        "pob", 'zip_five', 'zip_four',
                        DB::raw("AES_DECRYPT(dob, '$key') as dob"),
                        DB::raw("AES_DECRYPT(ssn, '$key') as ssn"),
                        DB::raw("AES_DECRYPT(address,'$key') as address"),
                        DB::raw("AES_DECRYPT(phone, '$key') as phone"),
                        DB::raw("AES_DECRYPT(fax, '$key') as fax"),
                        "city", "state", "country", "county"
                    ];

                    if (isset($ownership['owner_id'])) {

                        $whereOI = [
                            ["id", "=", $ownership['owner_id']]
                        ];
                        $owner = DB::table("user_ddownerinfo")
                            ->select($selCols)
                            ->where($whereOI)
                            ->first();
                        // $owner = $this->fetchData("user_ddownerinfo", $whereOI, 1, $selCols);
                        // $owner = User::where("id", "=", $ownership['owner_id'])->first($selCols);
                    } else {
                        if (isset($ownership['ssn_number'])) {
                            $ssn = $ownership['ssn_number'];

                            // $owner = User::whereRaw("AES_DECRYPT(ss, '$key') = '$ssn'")
                            //     ->first($selCols);
                            $owner = DB::table("user_ddownerinfo")
                                ->whereRaw("AES_DECRYPT(ss, '$key') = '$ssn'")
                                ->select($selCols)
                                ->first();
                        } else {
                            // $owner = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                            //     ->first($selCols);
                            $owner = DB::tabel("user_ddownerinfo")
                                ->whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                                ->select($selCols)
                                ->first();
                        }
                    }

                    // $this->printR($owner,true);
                    if (!is_object($owner)) {
                        if (isset($ownershipArr['name']) && strlen($ownershipArr['name']) > 1)
                            $logMsg .= "Owner name " . $ownershipArr['name'] . " added <br>";
                        if (isset($ownership["address"]['email']) && strlen($ownership["address"]['email']) > 1)
                            $logMsg .= "Owner email" . $ownership["address"]['email'] . " added <br>";
                        if (isset($ownership["address"]['email']) && strlen($ownership["address"]['email']) > 1)
                            $logMsg .= "Owner email" . $ownership["address"]['email'] . " added <br>";
                        if (isset($ownershipArr['pob']) && strlen($ownershipArr['pob']) > 1)
                            $logMsg .= "Owner country of birth " . $ownershipArr['pob'] . " added <br>";
                        if (isset($ownership['date_of_birth']) && strlen($ownership['date_of_birth']) > 1)
                            $logMsg .= "Owner dob " .  $ownership['date_of_birth'] . " added <br>";
                        if (isset($ownership['ssn_number']) && strlen($ownership['ssn_number']) > 1)
                            $logMsg .= "Owner ssn " . $ownership['ssn_number'] . " added <br>";
                        if (isset($ownership["address"]['street_address']) && strlen($ownership["address"]['street_address']) > 1)
                            $logMsg .= "Owner address " . $ownership["address"]['street_address'] . " added <br>";
                        if (isset($ownership["address"]['phone_number']) && strlen($ownership["address"]['phone_number']) > 1)
                            $logMsg .= "Owner phone " . $ownership["address"]['phone_number'] . " added <br>";
                        if (isset($ownership["address"]['fax_number']) && strlen($ownership["address"]['fax_number']) > 1)
                            $logMsg .= "Owner fax " . $ownership["address"]['fax_number'] . " added <br>";
                        if (isset($ownershipArr['city']) && strlen($ownershipArr['city']) > 1)
                            $logMsg .= "Owner city " . $ownershipArr['city'] . " added <br>";
                        if (isset($ownershipArr['state']) && strlen($ownershipArr['state']) > 1)
                            $logMsg .= "Owner state " . $ownershipArr['state'] . " added <br>";
                        if (isset($ownershipArr['zip_five']) && strlen($ownershipArr['zip_five']) > 1)
                            $logMsg .= "Owner zip five " . $ownershipArr['zip_five'] . " added <br>";
                        // if (isset($ownershipArr['zip_four']) && strlen($ownershipArr['zip_four']) > 1)
                        //     $logMsg .= "Owner zip four " . $ownershipArr['zip_four'] . " added <br>";
                        if (isset($ownershipArr['country']) && strlen($ownershipArr['country']) > 1)
                            $logMsg .= "Owner country " . $ownershipArr['country'] . " added <br>";
                        if (isset($ownershipArr['county']) && strlen($ownershipArr['county']) > 1)
                            $logMsg .= "Owner county " . $ownershipArr['county'] . " added <br>";
                        // if (isset($ownershipArr['state_code']) && strlen($ownershipArr['state_code']) > 1)
                        //     $logMsg .= "Owner state code " . $ownershipArr['state_code'] . " added <br>";

                        // $ownershipArr["created_at"] = $this->timeStamp();
                        // $whereOI = [
                        //     ["user_id", "=", $ownerId],
                        //     ["parent_user_id", "=", $practiceId],
                        // ];
                        // $hasRec = $this->fetchData("user_ddownerinfo", $whereOI, 1, []);
                        //at last add owner to ddownerinfo
                        $ownershipArr['user_id']               = 0;
                        $ownershipArr['parent_user_id']        = $practiceId;
                        $ownershipArr['ownership_percentage']  = $ownership['ownership_percentage'];
                        $ownershipArr["num_of_owners"]         = $numberOfOwners;
                        $ownershipArr["effective_date"]        = $ownership["date_of_ownership"];

                        if (isset($ownerInfo['ownership_percentage']) && !empty($ownerInfo['ownership_percentage']))
                            $logMsg .= "Ownership percentage " . $ownerInfo['ownership_percentage'] . " added <br>";
                        if (isset($ownerInfo['effective_date']) && !empty($ownerInfo['effective_date']))
                            $logMsg .= "Owner effective date " . $ownerInfo['effective_date'] . " added <br>";
                        if (isset($numberOfOwners) && !empty($numberOfOwners))
                            $logMsg .= "Number Of owner  " . $numberOfOwners . " added <br>";
                        //update invidual profile data
                        if (is_object($owner)) {
                            $ownerInfo["updated_at"]         = $this->timeStamp();
                            // $this->updateData("user_ddownerinfo", $whereOI, $ownerInfo);
                            DB::table("user_ddownerinfo")
                                ->where("id", "=", $owner->id)
                                ->update($ownershipArr);

                            $ownerMap = DB::table("owners_map")
                                ->where("owner_id", "=", $owner->id)
                                ->where("practice_id", "=", $practiceId)
                                ->count();

                            if ($ownerMap > 0) {
                                DB::table("owners_map")
                                    ->where("owner_id", "=", $owner->id)
                                    ->where("practice_id", "=", $practiceId)
                                    ->update([
                                        "percentage" => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            } else {
                                DB::table("owners_map")
                                    ->insertGetId([
                                        "owner_id"          => $owner->id,
                                        "practice_id"       => $practiceId,
                                        "percentage"        => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            }

                            $ownerMap = DB::table("owners_map")
                                ->where("owner_id", "=", $owner->id)
                                ->where("practice_id", "=", $practiceId)
                                ->count();

                            if ($ownerMap > 0) {
                                DB::table("owners_map")
                                    ->where("owner_id", "=", $owner->id)
                                    ->where("practice_id", "=", $practiceId)
                                    ->update([
                                        "percentage" => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            } else {
                                DB::table("owners_map")
                                    ->insertGetId([
                                        "owner_id"          => $owner->id,
                                        "practice_id"       => $practiceId,
                                        "percentage"        => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            }
                        } else {
                            $ownerInfo["created_at"]         = $this->timeStamp();
                            // $this->addData("user_ddownerinfo",  $ownerInfo, 0);
                            $ownerId = DB::table("user_ddownerinfo")->insertGetId($ownershipArr);

                            $ownerMap = DB::table("owners_map")
                                ->where("owner_id", "=", $ownerId)
                                ->where("practice_id", "=", $practiceId)
                                ->count();

                            if ($ownerMap > 0) {
                                DB::table("owners_map")->where("owner_id", "=", $ownerId)
                                    ->where("practice_id", "=", $practiceId)
                                    ->update([
                                        "percentage" => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            } else {
                                DB::table("owners_map")
                                    ->insertGetId([
                                        "owner_id"          => $ownerId,
                                        "practice_id"       => $practiceId,
                                        "percentage"        => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            }
                        }
                    } else {
                        // $ownerId = $owner->id;

                        if (isset($ownershipArr['name']) && (!is_null($owner->name) && $owner->name != $ownershipArr['name']))
                            $logMsg .= "Owner name changed from " . $owner->name . " to " . $ownershipArr['name'] . " <br>";
                        if (is_null($owner->name) && !is_null($ownershipArr['name']) && !empty($ownershipArr['name']))
                            $logMsg .= "Owner name " . $ownershipArr['name'] . " added <br>";

                        if (isset($ownership["address"]['email']) && (!is_null($owner->email) && $owner->email != $ownership["address"]['email']))
                            $logMsg .= "Owner email changed " . $owner->email . " to " . $ownership["address"]['email'] . "  <br>";
                        if (is_null($owner->email) && !is_null($ownership["address"]['email']) && !empty($ownership["address"]['email']))
                            $logMsg .= "Owner email" . $ownership["address"]['email'] . " added <br>";

                        if (isset($ownershipArr['pob']) && (!is_null($owner->pob) && $owner->pob != $ownershipArr['pob']))
                            $logMsg .= "Owner country of birth changed from " . $owner->country_of_birth . " to " . $ownershipArr['pob'] . " <br>";
                        if (is_null($owner->pob) && !is_null($ownershipArr['pob']) && !empty($ownershipArr['pob']))
                            $logMsg .= "Owner country of birth " . $ownershipArr['pob'] . " added <br>";

                        if (isset($ownership['date_of_birth']) && (!is_null($owner->dob) && $owner->dob != $ownership['date_of_birth']))
                            $logMsg .= "Owner dob changed from " . $owner->dob . " to " . $ownership['date_of_birth'] . " <br>";
                        if (is_null($owner->dob) && !is_null($ownership['date_of_birth']) && !empty($ownership['date_of_birth']))
                            $logMsg .= "Owner dob " . $ownership['date_of_birth'] . " added <br>";
                        if (isset($ownership['date_of_birth']) && (!is_null($owner->dob) && $owner->dob != $ownership['date_of_birth']))
                            $logMsg .= "Owner dob changed from " . $owner->dob . " to " . $ownership['date_of_birth'] . " <br>";
                        if (is_null($owner->dob) && !is_null($ownership['date_of_birth']) && !empty($ownership['date_of_birth']))
                            $logMsg .= "Owner dob " . $ownership['date_of_birth'] . " added <br>";

                        if (isset($ownership['ssn_number']) && (!is_null($owner->ssn) && $owner->ssn != $ownership['ssn_number']))
                            $logMsg .= "Owner ssn changed from " . $owner->ssn . " to " . $ownership['ssn_number'] . " added <br>";
                        if (is_null($owner->ssn) && !is_null($ownership['ssn_number']) && !empty($ownership['ssn_number']))
                            $logMsg .= "Owner ssn " . $ownership['ssn_number'] . " added <br>";

                        if (isset($ownership["address"]['street_address']) && (!is_null($owner->address) && $owner->address != $ownership["address"]['street_address']))
                            $logMsg .= "Owner address changed from " . $owner->address . " to " . $ownership["address"]['street_address'] . " <br>";
                        if (is_null($owner->address) && !is_null($ownership["address"]['street_address']) && !empty($ownership["address"]['street_address']))
                            $logMsg .= "Owner address " . $ownership["address"]['street_address'] . " added <br>";

                        if (isset($ownership["address"]['phone_number']) && (!is_null($owner->phone) && $owner->phone != $ownership["address"]['phone_number']))
                            $logMsg .= "Owner phone changed from " . $owner->phone . " to " . $ownership["address"]['phone_number'] . "  <br>";
                        if (is_null($owner->phone) && !is_null($ownership["address"]['phone_number']) && !empty($ownership["address"]['phone_number']))
                            $logMsg .= "Owner phone " . $ownership["address"]['phone_number'] . " added <br>";

                        if (isset($ownership["address"]['fax_number']) && (!is_null($owner->fax) && $owner->fax != $ownership["address"]['fax_number']))
                            $logMsg .= "Owner fax changed from " . $owner->fax . " to " . $ownership["address"]['fax_number'] . "  <br>";
                        if (is_null($owner->fax) && !is_null($ownership["address"]['fax_number']) && !empty($ownership["address"]['fax_number']))
                            $logMsg .= "Owner fax " . $ownership["address"]['fax_number'] . " added <br>";

                        if (isset($ownershipArr['city']) && (!is_null($owner->city) && $owner->city != $ownershipArr['city']))
                            $logMsg .= "Owner city changed from " . $owner->city . " to " . $ownershipArr['city'] . "  <br>";
                        if (is_null($owner->city) && !is_null($ownershipArr['city']) && !empty($ownershipArr['city']))
                            $logMsg .= "Owner city " . $ownershipArr['city'] . " added <br>";

                        if (isset($ownershipArr['state']) && (!is_null($owner->state) && $owner->state != $ownershipArr['state']))
                            $logMsg .= "Owner state changed from " . $owner->state . " to " . $ownershipArr['state'] . " <br>";
                        if (is_null($owner->state) && !is_null($ownershipArr['state']) && !empty($ownershipArr['state']))
                            $logMsg .= "Owner state " . $ownershipArr['state'] . " added <br>";

                        if (isset($ownershipArr['zip_five']) && (!is_null($owner->zip_five) && $owner->zip_five != $ownershipArr['zip_five']))
                            $logMsg .= "Owner zip five changed from " . $owner->zip_five . " to " . $ownershipArr['zip_five'] . " added <br>";
                        if (is_null($owner->zip_five) && !is_null($ownershipArr['zip_five']) && !empty($ownershipArr['zip_five']))
                            $logMsg .= "Owner zip five " . $ownershipArr['zip_five'] . " added <br>";

                        // if (isset($ownershipArr['zip_four']) && (!is_null($owner->zip_four) && $owner->zip_four != $ownershipArr['zip_four']))
                        //     $logMsg .= "Owner zip four changed from " . $owner->zip_five . " to " . $ownershipArr['zip_four'] . " <br>";
                        // if (is_null($owner->zip_four) && !is_null($ownershipArr['zip_four']) && !empty($ownershipArr['zip_four']))
                        //     $logMsg .= "Owner zip four " . $ownershipArr['zip_four'] . " added <br>";

                        if (isset($ownershipArr['country']) && (!is_null($owner->country) && $owner->country != $ownershipArr['country']))
                            $logMsg .= "Owner country changed from " . $owner->country . " to " . $ownershipArr['country'] . "  <br>";
                        if (is_null($owner->country) && !is_null($ownershipArr['country']) && !empty($ownershipArr['country']))
                            $logMsg .= "Owner country " . $ownershipArr['country'] . " added <br>";

                        if (isset($ownershipArr['county']) && (!is_null($owner->county) && $owner->county != $ownershipArr['county']))
                            $logMsg .= "Owner county changed from " . $owner->county . " to " . $ownershipArr['county'] . "  <br>";
                        if (is_null($owner->county) && !is_null($ownershipArr['county']) && !empty($ownershipArr['county']))
                            $logMsg .= "Owner county " . $ownershipArr['county'] . " added <br>";

                        // if (isset($ownershipArr['state_code']) && (!is_null($owner->state_code) && $owner->state_code != $ownershipArr['state_code']))
                        //     $logMsg .= "Owner state code " . $owner->state_code . " to " . $ownershipArr['state_code'] . " <br>";
                        // if (is_null($owner->state_code) && !is_null($ownershipArr['state_code']) && !empty($ownershipArr['state_code']))
                        //     $logMsg .= "Owner state code " . $ownershipArr['state_code'] . " added <br>";

                        // User::where("id", "=", $ownerId)->update($ownershipArr);

                        // $whereOI = [
                        //     ["user_id", "=", $ownerId],
                        //     ["parent_user_id", "=", $practiceId],
                        // ];
                        // $hasRec = $this->fetchData("user_ddownerinfo", $whereOI, 1, []);
                        //at last add owner to ddownerinfo
                        $profileFoundId = 0;
                        if (isset($ownership['ssn_number'])) {

                            $profileFound = DB::table('users')
                                ->whereRaw("AES_DECRYPT(ssn, '$key') = '" . $ownership['ssn_number'] . "' ")
                                ->first(["id"]);
                            if (is_object($profileFound)) {
                                $profileFoundId =  $profileFound->id;
                            }
                        }

                        $ownershipArr['user_id']               = $profileFoundId;
                        $ownershipArr['parent_user_id']        = $practiceId;
                        $ownershipArr['ownership_percentage']  = $ownership['ownership_percentage'];
                        $ownershipArr["num_of_owners"]         = $numberOfOwners;
                        $ownershipArr["effective_date"]        = $ownership["date_of_ownership"];

                        if (isset($ownershipArr['ownership_percentage']) && !empty($ownershipArr['ownership_percentage']) && (!is_null($owner->ownership_percentage) && $owner->ownership_percentage != $ownershipArr['ownership_percentage']))
                            $logMsg .= "Ownership percentage change from " . $owner->ownership_percentage . " to " . $ownershipArr['ownership_percentage'] . "  <br>";
                        if ((isset($ownershipArr['ownership_percentage']) || !empty($ownershipArr['ownership_percentage'])) && is_null($owner->ownership_percentage))
                            $logMsg .= "Ownership percentage " . $ownershipArr['ownership_percentage'] . "  added <br>";

                        if ((isset($ownershipArr['effective_date']) || !empty($ownershipArr['effective_date'])) && (!is_null($owner->effective_date) && $owner->effective_date != $ownershipArr['effective_date']))
                            $logMsg .= "Effective date change from " . $owner->effective_date . " to " . $ownershipArr['effective_date'] . "  <br>";
                        if ((isset($ownershipArr['effective_date']) || !empty($ownershipArr['effective_date'])) && is_null($owner->effective_date))
                            $logMsg .= "Effective date " . $ownershipArr['effective_date'] . " added <br>";

                        if ((isset($ownershipArr['num_of_owners']) || !empty($ownershipArr['num_of_owners'])) && (!is_null($owner->num_of_owners) && $owner->num_of_owners != $ownershipArr['num_of_owners']))
                            $logMsg .= "No of owner change from " . $owner->num_of_owners . " to " . $ownershipArr['num_of_owners'] . "  <br>";
                        if ((isset($ownerInfo['num_of_owners']) || !empty($ownerInfo['num_of_owners'])) && is_null($owner->num_of_owners))
                            $logMsg .= "No of owner  " . $ownershipArr['num_of_owners'] . " added <br>";
                        // if (isset($numberOfOwners) && !empty($numberOfOwners))
                        //     $logMsg .= "Number Of owner  " . $numberOfOwners . " added <br>";

                        //update invidual profile data
                        if (is_object($owner)) {

                            $ownerInfo["updated_at"]         = $this->timeStamp();
                            // $this->updateData("user_ddownerinfo", $whereOI, $ownerInfo);
                            DB::table("user_ddownerinfo")
                                ->where("id", "=", $owner->id)
                                ->update($ownershipArr);

                            $ownerMap = DB::table("owners_map")
                                ->where("owner_id", "=", $owner->id)
                                ->where("practice_id", "=", $practiceId)
                                ->count();

                            if ($ownerMap > 0) {
                                DB::table("owners_map")
                                    ->where("owner_id", "=", $owner->id)
                                    ->where("practice_id", "=", $practiceId)
                                    ->update([
                                        "percentage" => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            } else {
                                DB::table("owners_map")
                                    ->insertGetId([
                                        "owner_id"          => $owner->id,
                                        "practice_id"       => $practiceId,
                                        "percentage"        => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            }
                        } else {
                            $ownerInfo["created_at"]         = $this->timeStamp();
                            // $this->addData("user_ddownerinfo",  $ownerInfo, 0);
                            $ownerId = DB::table("user_ddownerinfo")->insertGetId($ownershipArr);

                            $ownerMap = DB::table("owners_map")
                                ->where("owner_id", "=", $ownerId)
                                ->where("practice_id", "=", $practiceId)
                                ->count();

                            if ($ownerMap > 0) {
                                DB::table("owners_map")
                                    ->where("owner_id", "=", $ownerId)
                                    ->where("practice_id", "=", $practiceId)
                                    ->update([
                                        "percentage" => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            } else {
                                DB::table("owners_map")
                                    ->insertGetId([
                                        "owner_id"          => $ownerId,
                                        "practice_id"       => $practiceId,
                                        "percentage"        => $ownership['ownership_percentage'],
                                        "date_of_ownership" => $ownership['date_of_ownership']
                                    ]);
                            }
                        }
                    }
                }
            }
        }
        $bankingInformation         = $request->banking_information;
        $bankInfo = DB::table("user_ddbankinginfo")->where("user_id", "=", $practiceId)
            ->select(
                DB::raw("AES_DECRYPT(account_name, '$key') as account_name"),
                "bank_name",
                DB::raw("AES_DECRYPT(routing_number, '$key') as routing_number"),
                DB::raw("AES_DECRYPT(account_number, '$key') as account_number"),
                "bank_phone",
                "bank_contact_person",
                DB::raw("AES_DECRYPT(email, '$key') as email")
            )
            ->first();
        $bankUpdate = [];
        $bankUpdate["user_id"] = $practiceId;

        $bankUpdate["bank_name"] = isset($bankingInformation["name_of_bank"]) ? $bankingInformation["name_of_bank"] : NULL;
        $bankUpdate["account_name"] = isset($bankingInformation['bank_account_title']) ? DB::raw("AES_ENCRYPT('" .    $bankingInformation['bank_account_title']     . "', '$key')") : NULL;
        $bankUpdate["routing_number"] = isset($bankingInformation['financial_institution_routing_number']) ? DB::raw("AES_ENCRYPT('" .    $bankingInformation['financial_institution_routing_number']     . "', '$key')") : NULL;
        $bankUpdate["account_number"] = isset($bankingInformation['bank_account_number']) ? DB::raw("AES_ENCRYPT('" .    $bankingInformation['bank_account_number']     . "', '$key')") : NULL;
        $bankUpdate["bank_phone"] = isset($bankingInformation["bank_contact_person_phone_number"]) ? $this->sanitizePhoneNumber($bankingInformation["bank_contact_person_phone_number"]) : NULL;
        $bankUpdate["bank_contact_person"] = isset($bankingInformation["bank_contact_person_name"]) ? $bankingInformation["bank_contact_person_name"] : NULL;
        $bankUpdate["email"] = isset($bankingInformation['bank_contact_person_email']) ? DB::raw("AES_ENCRYPT('" .    $bankingInformation['bank_contact_person_email']     . "', '$key')") : NULL;

        // $this->printR($bankUpdate,true);
        if (is_object($bankInfo)) {
            if ($bankInfo->bank_name != $bankingInformation["name_of_bank"] && !is_null($bankInfo->bank_name)) {
                $logMsg .= " Bank name changed from <b>" . $bankInfo->bank_name . "</b> to <b>" . $bankingInformation["name_of_bank"] . "</b> <br>";
            }
            if (is_null($bankInfo->bank_name) && !is_null($bankingInformation["name_of_bank"]) && !empty($bankingInformation["name_of_bank"])) {
                $logMsg .= " Bank name assigned to <b>" . $bankingInformation["name_of_bank"] . "</b> <br>";
            }
            if ($bankInfo->account_name != $bankingInformation["bank_account_title"] && !is_null($bankInfo->account_name)) {
                $logMsg .= " Account name changed from <b>" . $bankInfo->account_name . "</b> to <b>" . $bankingInformation["bank_account_title"] . "</b> <br>";
            }
            if (is_null($bankInfo->account_name) && !is_null($bankingInformation["bank_account_title"]) && !empty($bankingInformation["bank_account_title"])) {
                $logMsg .= " Account name assigned to <b>" . $bankingInformation["bank_account_title"] . "</b> <br>";
            }
            if ($bankInfo->routing_number != $bankingInformation["financial_institution_routing_number"] && !is_null($bankInfo->routing_number)) {
                $logMsg .= " Routing number changed from <b>" . $bankInfo->routing_number . "</b> to <b>" . $bankingInformation["financial_institution_routing_number"] . "</b> <br>";
            }
            if (is_null($bankInfo->routing_number) && !is_null($bankingInformation["financial_institution_routing_number"]) && !empty($bankingInformation["financial_institution_routing_number"])) {
                $logMsg .= " Routing number assigned to <b>" . $bankingInformation["financial_institution_routing_number"] . "</b> <br>";
            }
            if ($bankInfo->account_number != $bankingInformation["bank_account_number"] && !is_null($bankInfo->account_number)) {
                $logMsg .= " Account number changed from <b>" . $bankInfo->account_number . "</b> to <b>" . $bankingInformation["bank_account_number"] . "</b> <br>";
            }
            if (is_null($bankInfo->account_number) && !is_null($bankingInformation["bank_account_number"]) && !empty($bankingInformation["bank_account_number"])) {
                $logMsg .= " Account number assigned to <b>" . $bankingInformation["bank_account_number"] . "</b> <br>";
            }
            if ($bankInfo->bank_phone != $bankingInformation["bank_contact_person_phone_number"] && !is_null($bankInfo->bank_phone)) {
                $logMsg .= " Bank phone changed from <b>" . $bankInfo->bank_phone . "</b> to <b>" . $bankingInformation["bank_contact_person_phone_number"] . "</b> <br>";
            }
            if (is_null($bankInfo->bank_phone) && !is_null($bankingInformation["bank_contact_person_phone_number"]) && !empty($bankingInformation["bank_contact_person_phone_number"])) {
                $logMsg .= " Bank phone assigned to <b>" . $bankingInformation["bank_contact_person_phone_number"] . "</b> <br>";
            }
            if ($bankInfo->bank_contact_person != $bankingInformation["bank_contact_person_name"] && !is_null($bankInfo->bank_contact_person)) {
                $logMsg .= " Bank contact person changed from <b>" . $bankInfo->bank_contact_person . "</b> to <b>" . $bankingInformation["bank_contact_person_name"] . "</b> <br>";
            }
            if (is_null($bankInfo->bank_contact_person) && !is_null($bankingInformation["bank_contact_person_name"]) && !empty($bankingInformation["bank_contact_person_name"])) {
                $logMsg .= " Bank contact person assigned to <b>" . $bankingInformation["bank_contact_person_name"] . "</b> <br>";
            }
            if ($bankInfo->email != $bankingInformation["bank_contact_person_email"] && !is_null($bankInfo->email)) {
                $logMsg .= " Bank email changed from <b>" . $bankInfo->email . "</b> to <b>" . $bankingInformation["bank_contact_person_email"] . "</b> <br>";
            }
            if (is_null($bankInfo->email) && !is_null($bankingInformation["bank_contact_person_email"]) && !empty($bankingInformation["bank_contact_person_email"])) {
                $logMsg .= " Bank email assigned to <b>" . $bankingInformation["bank_contact_person_email"] . "</b> <br>";
            }
            $bankUpdate["updated_at"] = $this->timeStamp();
            DB::table("user_ddbankinginfo")->where("user_id", "=", $practiceId)->update($bankUpdate);
        } else {
            // $this->printR($bankUpdate,true);
            if (isset($bankingInformation["name_of_bank"]) && !empty($bankingInformation["name_of_bank"])) {
                $logMsg .= " Bank name assigned to <b>" . $bankingInformation["name_of_bank"] . "</b> <br>";
            }
            if (isset($bankingInformation["bank_account_title"]) && !empty($bankingInformation["bank_account_title"])) {
                $logMsg .= " Account name assigned to <b>" . $bankingInformation["bank_account_title"] . "</b> <br>";
            }
            if (isset($bankingInformation["financial_institution_routing_number"]) && !empty($bankingInformation["financial_institution_routing_number"])) {
                $logMsg .= " Routing number assigned to <b>" . $bankingInformation["financial_institution_routing_number"] . "</b> <br>";
            }
            if (isset($bankingInformation["bank_account_number"]) && !empty($bankingInformation["bank_account_number"])) {
                $logMsg .= " Account number assigned to <b>" . $bankingInformation["bank_account_number"] . "</b> <br>";
            }
            if (isset($bankingInformation["bank_contact_person_phone_number"]) && !empty($bankingInformation["bank_contact_person_phone_number"])) {
                $logMsg .= " Bank phone assigned to <b>" . $bankingInformation["bank_contact_person_phone_number"] . "</b> <br>";
            }
            if (isset($bankingInformation["bank_contact_person"]) && !empty($bankingInformation["bank_contact_person"])) {
                $logMsg .= " Bank contact person assigned to <b>" . $bankingInformation["bank_contact_person"] . "</b> <br>";
            }
            if (isset($bankingInformation["bank_contact_person_email"]) && !empty($bankingInformation["bank_contact_person_email"])) {
                $logMsg .= " Bank email assigned to <b>" . $bankingInformation["bank_contact_person_email"] . "</b> <br>";
            }
            $bankUpdate["created_at"] = $this->timeStamp();
            DB::table("user_ddbankinginfo")->insertGetId($bankUpdate);
        }
        $sessionUserId = $this->getSessionUserId($request);
        if (strlen($logMsg)) {
            // $this->addDirectoryLogs($practiceId, $sessionUserId, $logMsg, "Practice");
            DB::table("practice_logs")->insertGetId([
                "practice_id" => $practiceId, "session_userid" => $sessionUserId, "section" => "Practice Profile",
                "action" => "Update",
                "practice_profile_logs" => DB::raw("AES_ENCRYPT('" .    $logMsg     . "', '$key')"),
                'created_at' => $this->timeStamp()
            ]);
        }
        return $this->successResponse(["is_update" => true], "Data updated successfully");
    }
    /**
     * update facility data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateFacility($facilityId, Request $request)
    {
        $key = $this->key;

        // $clientReqData = $request->all();
        // $this->printR($clientReqData, true);
        $logMsg = "";
        $profileCompletePercentage  = $request->profile_complete_percentage;
        $isComplete  = $request->is_complete;
        $status  = $request->status;
        $facilityName               = $request->facility_name;
        $facilityNpiNumber          = $request->facility_npi_number;
        $newFacilityAddress         = $request->new_facility_address;
        $facilityContactInformation = $request->facility_contact_information;

        $facilityTimings            = $request->facility_timings;
        $mondayTimings              = isset($facilityTimings["monday"]) ? $facilityTimings["monday"] : null;
        $tuesdayTimings             = isset($facilityTimings["tuesday"]) ? $facilityTimings["tuesday"] : null;
        $wednesdayTimings           = isset($facilityTimings["wednesday"]) ? $facilityTimings["wednesday"] : null;
        $thursdayTimings            = isset($facilityTimings["thursday"]) ? $facilityTimings["thursday"] : null;
        $fridayTimings              = isset($facilityTimings["friday"]) ? $facilityTimings["friday"] : null;
        $saturdayTimings            = isset($facilityTimings["saturday"]) ? $facilityTimings["saturday"] : null;
        $sundayTimings              = isset($facilityTimings["sunday"]) ? $facilityTimings["sunday"] : null;

        $facilityName = strtoupper($facilityName);

        $profileEmail = isset($facilityContactInformation["email"]) ? DB::raw("AES_ENCRYPT('" .    $facilityContactInformation["email"]     . "', '$key')") : null;
        // $this->printR($facilityTimings,true);
        User::where("id", "=", $facilityId)
            ->update([
                "profile_complete_percentage" => $profileCompletePercentage, "is_complete" => $isComplete,
                "updated_at" => $this->timeStamp(), "status" => $status,
                "email" => $profileEmail
            ]);

        $cols = [
            DB::raw("AES_DECRYPT(npi, '$key') as npi"),
            DB::raw("AES_DECRYPT(practice_name, '$key') as practice_name"),
            DB::raw("AES_DECRYPT(contact_phone, '$key') as contact_phone"),
            DB::raw("AES_DECRYPT(contact_fax, '$key') as contact_fax"),
            DB::raw("AES_DECRYPT(contact_email, '$key') as contact_email"),
            "contact_name", "contact_title", "zip_five", "zip_four",
            DB::raw("AES_DECRYPT(practise_address, '$key') as practise_address"),
            "country", "county", "city", "state",
            DB::raw("AES_DECRYPT(phone, '$key') as phone"),
            "state_code", DB::raw("AES_DECRYPT(fax, '$key') as fax"),
            DB::raw("AES_DECRYPT(email, '$key') as email"),
            "monday_from", "tuesday_from", "wednesday_from", "thursday_from",
            "friday_from", "saturday_from", "sunday_from",
            "monday_to", "tuesday_to", "wednesday_to", "thursday_to",
            "friday_to", "saturday_to", "sunday_to", "monday_is_closed", "tuesday_is_closed",
            "wednesday_is_closed", "thursday_is_closed", "friday_is_closed", "saturday_is_closed", "sunday_is_closed"

        ];

        $facilityInfo = DB::table("user_ddpracticelocationinfo")->where("user_id", "=", $facilityId)
            ->first($cols);

        $facilityInfoData = [];
        $facilityInfoData["user_id"]                   = $facilityId;
        $facilityInfoData["npi"]                       = isset($facilityNpiNumber) ? DB::raw("AES_ENCRYPT('" .    $facilityNpiNumber     . "', '$key')") : null;
        $facilityInfoData["practice_name"]             = isset($facilityName) ? DB::raw("AES_ENCRYPT('" .    $facilityName     . "', '$key')") : null;

        $facilityInfoData["contact_phone"]             = isset($facilityContactInformation["phone_number"]) ? DB::raw("AES_ENCRYPT('" .    $this->sanitizePhoneNumber($facilityContactInformation["phone_number"])     . "', '$key')") : null;
        $facilityInfoData["contact_fax"]               = isset($facilityContactInformation["fax_number"]) ? DB::raw("AES_ENCRYPT('" .    $facilityContactInformation["fax_number"]     . "', '$key')") : null;
        $facilityInfoData["contact_email"]             = isset($facilityContactInformation["email"]) ? DB::raw("AES_ENCRYPT('" .    $facilityContactInformation["email"]     . "', '$key')") : null;
        $facilityInfoData["contact_name"]              = isset($facilityContactInformation["name"]) ?  $facilityContactInformation["name"] : null;
        $facilityInfoData["contact_title"]             = isset($facilityContactInformation["title"]) ? $facilityContactInformation["title"] : null;

        $facilityInfoData["zip_five"]                  = isset($newFacilityAddress["zip_five"]) ? $newFacilityAddress["zip_five"] : null;
        $facilityInfoData["zip_four"]                  = isset($newFacilityAddress["zip_four"]) ? $newFacilityAddress["zip_four"] : null;
        $facilityInfoData["practise_address"]          = isset($newFacilityAddress["street_address"]) ? DB::raw("AES_ENCRYPT('" .    $newFacilityAddress["street_address"]     . "', '$key')") : null;
        $facilityInfoData["country"]                   = isset($newFacilityAddress["country"]) ? $newFacilityAddress["country"] : null;
        $facilityInfoData["county"]                    = isset($newFacilityAddress["county"]) ? $newFacilityAddress["county"] : null;
        $facilityInfoData["city"]                      = isset($newFacilityAddress["city"]) ? $newFacilityAddress["city"] : null;
        $facilityInfoData["state"]                     = isset($newFacilityAddress["state"]) ? $newFacilityAddress["state"] : null;
        $facilityInfoData["phone"]                     = isset($newFacilityAddress["phone_number"]) ? DB::raw("AES_ENCRYPT('" .    $this->sanitizePhoneNumber($newFacilityAddress["phone_number"])     . "', '$key')") : null;
        $facilityInfoData["state_code"]                = isset($newFacilityAddress["state_code"]) ? $newFacilityAddress["state_code"] : null;
        $facilityInfoData["fax"]                       = isset($newFacilityAddress["fax_number"]) ? DB::raw("AES_ENCRYPT('" .    $newFacilityAddress["fax_number"]     . "', '$key')") : null;
        $facilityInfoData["email"]                     = isset($newFacilityAddress["email"]) ? DB::raw("AES_ENCRYPT('" .    $newFacilityAddress["email"]     . "', '$key')") : null;


        $facilityInfoData["monday_from"]               = isset($mondayTimings["start_time"]) ? $mondayTimings["start_time"] : null;
        $facilityInfoData["tuesday_from"]              = isset($tuesdayTimings["start_time"]) ? $tuesdayTimings["start_time"] : null;
        $facilityInfoData["wednesday_from"]            = isset($wednesdayTimings["start_time"]) ? $wednesdayTimings["start_time"] : null;
        $facilityInfoData["thursday_from"]             = isset($thursdayTimings["start_time"]) ? $thursdayTimings["start_time"] : null;
        $facilityInfoData["friday_from"]               = isset($fridayTimings["start_time"]) ? $fridayTimings["start_time"] : null;
        $facilityInfoData["saturday_from"]             = isset($saturdayTimings["start_time"]) ? $saturdayTimings["start_time"] : null;
        $facilityInfoData["sunday_from"]               = isset($sundayTimings["start_time"]) ? $sundayTimings["start_time"] : null;

        $facilityInfoData["monday_to"]               = isset($mondayTimings["end_time"]) ? $mondayTimings["end_time"] : null;
        $facilityInfoData["tuesday_to"]              = isset($tuesdayTimings["end_time"]) ? $tuesdayTimings["end_time"] : null;
        $facilityInfoData["wednesday_to"]            = isset($wednesdayTimings["end_time"]) ? $wednesdayTimings["end_time"] : null;
        $facilityInfoData["thursday_to"]             = isset($thursdayTimings["end_time"]) ? $thursdayTimings["end_time"] : null;
        $facilityInfoData["friday_to"]               = isset($fridayTimings["end_time"]) ? $fridayTimings["end_time"] : null;
        $facilityInfoData["saturday_to"]             = isset($saturdayTimings["end_time"]) ? $saturdayTimings["end_time"] : null;
        $facilityInfoData["sunday_to"]               = isset($sundayTimings["end_time"]) ? $sundayTimings["end_time"] : null;

        $facilityInfoData["monday_is_closed"] = 0;
        if (isset($mondayTimings["is_closed"])) {
            if ($mondayTimings["is_closed"] == "true")
                $facilityInfoData["monday_is_closed"] =   1;
            else
                $facilityInfoData["monday_is_closed"] =   0;
        }

        $facilityInfoData["tuesday_is_closed"]  =  0;
        if (isset($tuesdayTimings["is_closed"])) {
            if ($tuesdayTimings["is_closed"] == "true")
                $facilityInfoData["tuesday_is_closed"] =   1;
            else
                $facilityInfoData["tuesday_is_closed"] =   0;
        }

        $facilityInfoData["wednesday_is_closed"] =  0;
        if (isset($wednesdayTimings["is_closed"])) {
            if ($wednesdayTimings["is_closed"] == "true")
                $facilityInfoData["wednesday_is_closed"] =   1;
            else
                $facilityInfoData["wednesday_is_closed"] =   0;
        }

        $facilityInfoData["thursday_is_closed"] =  0;
        if (isset($thursdayTimings["is_closed"])) {
            if ($thursdayTimings["is_closed"] == "true")
                $facilityInfoData["thursday_is_closed"] =   1;
            else
                $facilityInfoData["thursday_is_closed"] =   0;
        }

        $facilityInfoData["friday_is_closed"] =  0;
        if (isset($fridayTimings["is_closed"])) {
            if ($fridayTimings["is_closed"] == "true")
                $facilityInfoData["friday_is_closed"] =   1;
            else
                $facilityInfoData["friday_is_closed"] =   0;
        }

        $facilityInfoData["saturday_is_closed"] = 0;
        if (isset($saturdayTimings["is_closed"])) {
            if ($saturdayTimings["is_closed"] == "true")
                $facilityInfoData["saturday_is_closed"] =   1;
            else
                $facilityInfoData["saturday_is_closed"] =   0;
        }

        $facilityInfoData["sunday_is_closed"]  = 0;
        if (isset($sundayTimings["is_closed"])) {
            if ($sundayTimings["is_closed"] == "true")
                $facilityInfoData["sunday_is_closed"] =   1;
            else
                $facilityInfoData["sunday_is_closed"] =   0;
        }
        if (is_object($facilityInfo)) {

            if ((is_object($facilityInfo) && isset($facilityInfo->npi)) && $facilityInfo->npi != $facilityNpiNumber) {
                $logMsg .= "Facility npi changed from " . $facilityInfo->npi . " to " . $facilityNpiNumber . "<br>";
            }
            if (is_null($facilityInfo->npi) && isset($facilityNpiNumber)) {
                $logMsg .= "Facility npi assigned to " . $facilityNpiNumber . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->practice_name != $facilityName) {
                $logMsg .= "Facility name changed from " . $facilityInfo->practice_name . " to " . $facilityName . "<br>";
            }
            if (is_null($facilityInfo->practice_name)  && isset($facilityName)) {
                $logMsg .= "Facility name assigned to " . $facilityName . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->contact_phone != $facilityContactInformation["phone_number"]) {
                $logMsg .= "Facility contact phone changed from " . $facilityInfo->contact_phone . " to " . $facilityContactInformation["phone_number"] . "<br>";
            }
            if (is_null($facilityInfo->contact_phone) && isset($facilityContactInformation["phone_number"])) {
                $logMsg .= "Facility contact phone assigned to " . $facilityContactInformation["phone_number"] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->contact_fax !=  $facilityContactInformation["fax_number"]) {
                $logMsg .= "Facility contact fax changed from " . $facilityInfo->contact_fax . " to " .  $facilityContactInformation["fax_number"] . "<br>";
            }
            if (is_null($facilityInfo->contact_fax) && isset($facilityContactInformation["fax_number"])) {
                $logMsg .= "Facility contact fax assigned to " .  $facilityContactInformation["fax_number"] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->contact_email !=  $facilityContactInformation["email"]) {
                $logMsg .= "Facility contact email changed from " . $facilityInfo->contact_email . " to " .  $facilityContactInformation["email"] . "<br>";
            }
            if (is_null($facilityInfo->contact_email) && isset($facilityContactInformation["email"])) {
                $logMsg .= "Facility contact email assigned to " .  $facilityContactInformation["email"] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->contact_name != $facilityInfoData['contact_name']) {
                $logMsg .= "Facility contact name changed from " . $facilityInfo->contact_name . " to " . $facilityInfoData['contact_name'] . "<br>";
            }
            if (is_null($facilityInfo->contact_name) && isset($facilityInfoData['contact_name'])) {
                $logMsg .= "Facility contact name assigned to " . $facilityInfoData['contact_name'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->contact_title != $facilityInfoData['contact_title']) {
                $logMsg .= "Facility contact title changed from " . $facilityInfo->contact_title . " to " . $facilityInfoData['contact_title'] . "<br>";
            }
            if (is_null($facilityInfo->contact_title) && isset($facilityInfoData['contact_title'])) {
                $logMsg .= "Facility contact title assigned to " . $facilityInfoData['contact_title'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->zip_five != $facilityInfoData['zip_five']) {
                $logMsg .= "Facility zip five changed from " . $facilityInfo->zip_five . " to " . $facilityInfoData['zip_five'] . "<br>";
            }
            if (is_null($facilityInfo->zip_five) && isset($facilityInfoData['zip_five'])) {
                $logMsg .= "Facility zip five assigned to " . $facilityInfoData['zip_five'] . "<br>";
            }

            // if (isset($facilityInfoData['zip_four']) && (is_object($facilityInfo) && $facilityInfo->zip_four != $facilityInfoData['zip_four'])) {
            //     $logMsg .= "Facility zip four changed from " . $facilityInfo->zip_four . " to " . $facilityInfoData['zip_four'] . "<br>";
            // }
            // if ((is_null($facilityInfo->zip_four) || empty($facilityInfo->zip_four)) && isset($facilityInfoData['zip_four'])) {
            //     $logMsg .= "Facility zip four assigned to " . $facilityInfoData['zip_four'] . "<br>";
            // }

            if (is_object($facilityInfo) && $facilityInfo->practise_address != $newFacilityAddress["street_address"]) {
                $logMsg .= "Facility  address changed from " . $facilityInfo->practise_address . " to " . $newFacilityAddress["street_address"] . "<br>";
            }
            if (is_null($facilityInfo->practise_address)  && isset($newFacilityAddress["street_address"])) {
                $logMsg .= "Facility address assigned to " . $newFacilityAddress["street_address"] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->country != $facilityInfoData['country']) {
                $logMsg .= "Facility  country changed from " . $facilityInfo->country . " to " . $facilityInfoData['country'] . "<br>";
            }
            if (is_null($facilityInfo->country) && isset($facilityInfoData['country'])) {
                $logMsg .= "Facility country assigned to " . $facilityInfoData['country'] . "<br>";
            }


            if (is_object($facilityInfo) && $facilityInfo->county != $facilityInfoData['county']) {
                $logMsg .= "Facility  county changed from " . $facilityInfo->county . " to " . $facilityInfoData['county'] . "<br>";
            }
            if (is_null($facilityInfo->county) && isset($facilityInfoData['county'])) {
                $logMsg .= "Facility county assigned to " . $facilityInfoData['county'] . "<br>";
            }

            // if (isset($facilityInfoData['county']) && (is_object($facilityInfo) && $facilityInfo->county != $facilityInfoData['county'])) {
            //     $logMsg .= "Facility  county changed from " . $facilityInfo->county . " to " . $facilityInfoData['county'];
            // } else {
            //     $logMsg .= "Facility county assigned to " . $facilityInfoData['county'];
            // }

            if (is_object($facilityInfo) && $facilityInfo->city != $facilityInfoData['city']) {
                $logMsg .= "Facility  city changed from " . $facilityInfo->city . " to " . $facilityInfoData['city'] . "<br>";
            }
            if (is_null($facilityInfo->city) && isset($facilityInfoData['city'])) {
                $logMsg .= "Facility city assigned to " . $facilityInfoData['city'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->state != $facilityInfoData['state']) {
                $logMsg .= "Facility  state changed from " . $facilityInfo->state . " to " . $facilityInfoData['state'] . "<br>";
            }
            if (is_null($facilityInfo->state) && isset($facilityInfoData['state'])) {
                $logMsg .= "Facility state assigned to " . $facilityInfoData['state'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->phone != $newFacilityAddress["phone_number"]) {
                $logMsg .= "Facility  phone changed from " . $facilityInfo->phone . " to " . $newFacilityAddress["phone_number"] . "<br>";
            }
            if (is_null($facilityInfo->phone) && isset($newFacilityAddress["phone_number"])) {
                $logMsg .= "Facility phone assigned to " . $newFacilityAddress["phone_number"] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->state_code != $facilityInfoData['state_code']) {
                $logMsg .= "Facility  state code changed from " . $facilityInfo->state_code . " to " . $facilityInfoData['state_code'] . "<br>";
            }
            if (is_null($facilityInfo->state_code) && isset($facilityInfoData['state_code'])) {
                $logMsg .= "Facility state code assigned to " . $facilityInfoData['state_code'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->fax != $newFacilityAddress["fax_number"]) {
                $logMsg .= "Facility  fax changed from " . $facilityInfo->fax . " to " . $newFacilityAddress["fax_number"] . "<br>";
            }
            if (is_null($facilityInfo->fax) && isset($newFacilityAddress["fax_number"])) {
                $logMsg .= "Facility fax assigned to " . $newFacilityAddress["fax_number"] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->email != $newFacilityAddress["email"]) {
                $logMsg .= "Facility  email changed from " . $facilityInfo->email . " to " . $newFacilityAddress["email"] . "<br>";
            }
            if (is_null($facilityInfo->email) && isset($newFacilityAddress["email"])) {
                $logMsg .= "Facility email assigned to " . $newFacilityAddress["email"] . "<br>";
            }

            // if (isset($facilityInfoData['email']) && (is_object($facilityInfo) && $facilityInfo->email != $facilityInfoData['email'])) {
            //     $logMsg .= "Facility  email changed from " . $facilityInfo->email . " to " . $facilityInfoData['email'];
            // }
            // if(is_null($facilityInfo->email) && !is_null($facilityInfoData['email']) && !empty($facilityInfoData['email'])) {
            //     $logMsg .= "Facility email assigned to " . $facilityInfoData['email'];
            // }

            if (is_object($facilityInfo) && $facilityInfo->monday_from != $facilityInfoData['monday_from']) {
                $logMsg .= "Facility  monday from changed from " . $facilityInfo->monday_from . " to " . $facilityInfoData['monday_from'] . "<br>";
            }
            if (is_null($facilityInfo->monday_from) && isset($facilityInfoData['monday_from'])) {
                $logMsg .= "Facility monday from assigned to " . $facilityInfoData['monday_from'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->tuesday_from != $facilityInfoData['tuesday_from']) {
                $logMsg .= "Facility  tuesday from changed from " . $facilityInfo->tuesday_from . " to " . $facilityInfoData['tuesday_from'] . "<br>";
            }
            if (is_null($facilityInfo->tuesday_from) && isset($facilityInfoData['tuesday_from'])) {
                $logMsg .= "Facility tuesday from assigned to " . $facilityInfoData['tuesday_from'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->wednesday_from != $facilityInfoData['wednesday_from']) {
                $logMsg .= "Facility  wednesday from changed from " . $facilityInfo->wednesday_from . " to " . $facilityInfoData['wednesday_from'] . "<br>";
            }
            if (is_null($facilityInfo->wednesday_from) && isset($facilityInfoData['wednesday_from'])) {
                $logMsg .= "Facility wednesday from assigned to " . $facilityInfoData['wednesday_from'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->thursday_from != $facilityInfoData['thursday_from']) {
                $logMsg .= "Facility  thursday from changed from " . $facilityInfo->thursday_from . " to " . $facilityInfoData['thursday_from'] . "<br>";
            }
            if (is_null($facilityInfo->thursday_from) && isset($facilityInfoData['thursday_from'])) {
                $logMsg .= "Facility thursday from assigned to " . $facilityInfoData['thursday_from'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->friday_from != $facilityInfoData['friday_from']) {
                $logMsg .= "Facility  friday from changed from " . $facilityInfo->friday_from . " to " . $facilityInfoData['friday_from'] . "<br>";
            }
            if (is_null($facilityInfo->friday_from) && isset($facilityInfoData['friday_from'])) {
                $logMsg .= "Facility friday from assigned to " . $facilityInfoData['friday_from'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->saturday_from != $facilityInfoData['saturday_from']) {
                $logMsg .= "Facility  saturday from changed from " . $facilityInfo->saturday_from . " to " . $facilityInfoData['saturday_from'] . "<br>";
            }
            if (is_null($facilityInfo->saturday_from) && isset($facilityInfoData['saturday_from'])) {
                $logMsg .= "Facility saturday from assigned to " . $facilityInfoData['saturday_from'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->sunday_from != $facilityInfoData['sunday_from']) {
                $logMsg .= "Facility  sunday from changed from " . $facilityInfo->sunday_from . " to " . $facilityInfoData['sunday_from'] . "<br>";
            }
            if (is_null($facilityInfo->sunday_from) && isset($facilityInfoData['sunday_from'])) {
                $logMsg .= "Facility sunday from assigned to " . $facilityInfoData['sunday_from'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->monday_to != $facilityInfoData['monday_to']) {
                $logMsg .= "Facility  monday to changed from " . $facilityInfo->monday_to . " to " . $facilityInfoData['monday_to'] . "<br>";
            }
            if (is_null($facilityInfo->monday_to) && isset($facilityInfoData['monday_to'])) {
                $logMsg .= "Facility monday to assigned to " . $facilityInfoData['monday_to'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->tuesday_to != $facilityInfoData['tuesday_to']) {
                $logMsg .= "Facility  tuesday to changed from " . $facilityInfo->tuesday_to . " to " . $facilityInfoData['tuesday_to'] . "<br>";
            }
            if (is_null($facilityInfo->tuesday_to) && isset($facilityInfoData['tuesday_to'])) {
                $logMsg .= "Facility tuesday to assigned to " . $facilityInfoData['tuesday_to'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->wednesday_to != $facilityInfoData['wednesday_to']) {
                $logMsg .= "Facility  wednesday to changed from " . $facilityInfo->wednesday_to . " to " . $facilityInfoData['wednesday_to'] . "<br>";
            }
            if (is_null($facilityInfo->wednesday_to) && isset($facilityInfoData['wednesday_to'])) {
                $logMsg .= "Facility wednesday to assigned to " . $facilityInfoData['wednesday_to'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->thursday_to != $facilityInfoData['thursday_to']) {
                $logMsg .= "Facility  thursday to changed from " . $facilityInfo->thursday_to . " to " . $facilityInfoData['thursday_to'] . "<br>";
            }
            if (is_null($facilityInfo->thursday_to) && isset($facilityInfoData['thursday_to'])) {
                $logMsg .= "Facility thursday to assigned to " . $facilityInfoData['thursday_to'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->friday_to != $facilityInfoData['friday_to']) {
                $logMsg .= "Facility  friday to changed from " . $facilityInfo->friday_to . " to " . $facilityInfoData['friday_to'] . "<br>";
            }
            if (is_null($facilityInfo->friday_to) && isset($facilityInfoData['friday_to'])) {
                $logMsg .= "Facility friday to assigned to " . $facilityInfoData['friday_to'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->saturday_to != $facilityInfoData['saturday_to']) {
                $logMsg .= "Facility  saturday to changed from " . $facilityInfo->saturday_to . " to " . $facilityInfoData['saturday_to'] . "<br>";
            }
            if (is_null($facilityInfo->saturday_to) && isset($facilityInfoData['saturday_to'])) {
                $logMsg .= "Facility saturday to assigned to " . $facilityInfoData['saturday_to'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->sunday_to != $facilityInfoData['sunday_to']) {
                $logMsg .= "Facility  sunday to changed from " . $facilityInfo->sunday_to . " to " . $facilityInfoData['sunday_to'] . "<br>";
            }
            if (is_null($facilityInfo->sunday_to) && isset($facilityInfoData['sunday_to'])) {
                $logMsg .= "Facility sunday to assigned to " . $facilityInfoData['sunday_to'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->monday_is_closed != $facilityInfoData['monday_is_closed']) {
                $logMsg .= "Facility  monday closed changed from " . $facilityInfo->monday_is_closed . " to " . $facilityInfoData['monday_is_closed'] . "<br>";
            }
            if (is_null($facilityInfo->monday_is_closed) && isset($facilityInfoData['monday_is_closed'])) {
                $logMsg .= "Facility  monday closed  assigned to " . $facilityInfoData['monday_is_closed'] . "<br>";
            }


            if (is_object($facilityInfo) && $facilityInfo->tuesday_is_closed != $facilityInfoData['tuesday_is_closed']) {
                $logMsg .= "Facility  tuesday closed changed from " . $facilityInfo->tuesday_is_closed . " to " . $facilityInfoData['tuesday_is_closed'] . "<br>";
            }
            if (is_null($facilityInfo->tuesday_is_closed) && isset($facilityInfoData['tuesday_is_closed'])) {
                $logMsg .= "Facility  tuesday closed  assigned to " . $facilityInfoData['tuesday_is_closed'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->wednesday_is_closed != $facilityInfoData['wednesday_is_closed']) {
                $logMsg .= "Facility  wednesday closed changed from " . $facilityInfo->wednesday_is_closed . " to " . $facilityInfoData['wednesday_is_closed'] . "<br>";
            }
            if (is_null($facilityInfo->wednesday_is_closed) && isset($facilityInfoData['wednesday_is_closed'])) {
                $logMsg .= "Facility  wednesday closed  assigned to " . $facilityInfoData['wednesday_is_closed'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->thursday_is_closed != $facilityInfoData['thursday_is_closed']) {
                $logMsg .= "Facility  thursday closed changed from " . $facilityInfo->thursday_is_closed . " to " . $facilityInfoData['thursday_is_closed'] . "<br>";
            }
            if (is_null($facilityInfo->thursday_is_closed) && isset($facilityInfoData['thursday_is_closed'])) {
                $logMsg .= "Facility  thursday closed  assigned to " . $facilityInfoData['thursday_is_closed'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->friday_is_closed != $facilityInfoData['friday_is_closed']) {
                $logMsg .= "Facility  friday closed changed from " . $facilityInfo->friday_is_closed . " to " . $facilityInfoData['friday_is_closed'] . "<br>";
            }
            if (is_null($facilityInfo->friday_is_closed) && isset($facilityInfoData['friday_is_closed'])) {
                $logMsg .= "Facility  friday closed  assigned to " . $facilityInfoData['friday_is_closed'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->saturday_is_closed != $facilityInfoData['saturday_is_closed']) {
                $logMsg .= "Facility  saturday closed changed from " . $facilityInfo->saturday_is_closed . " to " . $facilityInfoData['saturday_is_closed'] . "<br>";
            }
            if (is_null($facilityInfo->saturday_is_closed) && isset($facilityInfoData['saturday_is_closed'])) {
                $logMsg .= "Facility  saturday closed  assigned to " . $facilityInfoData['saturday_is_closed'] . "<br>";
            }

            if (is_object($facilityInfo) && $facilityInfo->sunday_is_closed != $facilityInfoData['sunday_is_closed']) {
                $logMsg .= "Facility  sunday closed changed from " . $facilityInfo->sunday_is_closed . " to " . $facilityInfoData['sunday_is_closed'] . "<br>";
            }
            if (is_null($facilityInfo->sunday_is_closed) && isset($facilityInfoData['sunday_is_closed'])) {
                $logMsg .= "Facility  sunday closed  assigned to " . $facilityInfoData['sunday_is_closed'] . "<br>";
            }


            // $this->printR($facilityInfoData,true);
            $facilityInfoData["updated_at"] = $this->timeStamp();
            DB::table("user_ddpracticelocationinfo")->where("user_id", "=", $facilityId)
                ->update($facilityInfoData);
        } else {
            $facilityInfoData["created_at"] = $this->timeStamp();
            DB::table("user_ddpracticelocationinfo")
                ->insertGetId($facilityInfoData);
        }

        $sessionUserId = $this->getSessionUserId($request);
        if (strlen($logMsg)) {
            // $this->addDirectoryLogs($facilityId, $sessionUserId, $logMsg, "Facility");
            DB::table("facility_logs")->insertGetId([
                "facility_id" => $facilityId, "session_userid" => $sessionUserId, "section" => "Facility Profile",
                "action" => "Update",
                "log" => DB::raw("AES_ENCRYPT('" .    $logMsg     . "', '$key')"),
                'created_at' => $this->timeStamp()
            ]);
        }
        return $this->successResponse(["is_update" => true], "Data updated successfully");
    }
    /**
     * add the addedum
     *
     * @param $firstName
     * @param $lastName
     * @param $email
     * @param $dateTime
     */
    private function addAddedum($firstName, $lastName, $email, $dateTime)
    {

        return DB::table("addendum")
            ->insertGetId([
                "first_name" => $firstName,
                "last_name" => $lastName,
                "email" => $email,
                "date_time" => $dateTime,
                "created_at" => $this->timeStamp()
            ]);
    }
    /**
     * update facility data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProvider($providerId, Request $request)
    {
        $key = $this->key;
        // $clientReqData = $request->all();
        // $this->printR($clientReqData, true);
        $logMsg = "";
        $profileCompletePercentage = $request->profile_complete_percentage;
        $isComplete = $request->is_complete;
        $status = $request->status;
        User::where("id", "=", $providerId)
            ->update([
                "profile_complete_percentage" => $profileCompletePercentage, "is_complete" => $isComplete,
                "updated_at" => $this->timeStamp(), "status" => $status
            ]);

        $selCols = [

            "first_name", "last_name", DB::raw("AES_DECRYPT(dob,'$key') as dob"),
            DB::raw("AES_DECRYPT(ssn,'$key') as ssn"), "citizenship_id", "gender",
            "supervisor_physician", "place_of_birth", DB::raw("AES_DECRYPT(phone,'$key') as phone"),
            DB::raw("AES_DECRYPT(work_phone,'$key') as work_phone"),
            DB::raw("AES_DECRYPT(email,'$key') as email"),
            DB::raw("AES_DECRYPT(address_line_one,'$key') as address_line_one"),
            "zip_five", "zip_four", "country", "county", "city", "state", "state_code",
            "primary_speciality", "secondary_speciality", "professional_group_id", "professional_type_id",
            DB::raw("AES_DECRYPT(facility_npi,'$key') as facility_npi"), "middle_name", "name_credentials"

        ];
        $supervisionOption = [
            "1" => "Yes",
            "2" => "No"
        ];
        // $genders = [
        //     "1" => "Male",
        //     "2" => "Female",
        //     "3" => "Others"
        // ];
        $user = User::where("id", "=", $providerId)
            ->select($selCols)
            ->first();

        $basicInformation           = $request->basic_information;
        $updateProvider = [];

        $updateProvider["facility_npi"]     = isset($basicInformation["provider_npi_number"]) ? DB::raw("AES_ENCRYPT('" .    $basicInformation["provider_npi_number"]     . "', '$key')") : null;
        $updateProvider["first_name"]       = isset($basicInformation["provider_first_name"]) ? strtoupper($basicInformation["provider_first_name"]) : null;
        $updateProvider["last_name"]        = isset($basicInformation["provider_last_name"]) ? strtoupper($basicInformation["provider_last_name"]) : null;
        $updateProvider["dob"]              = isset($basicInformation["date_of_birth"]) ? DB::raw("AES_ENCRYPT('" .    $basicInformation["date_of_birth"]     . "', '$key')") : null;
        $updateProvider["ssn"]              = isset($basicInformation["ssn_number"]) ? DB::raw("AES_ENCRYPT('" .    $basicInformation["ssn_number"]     . "', '$key')") : null;
        $updateProvider["citizenship_id"]   = isset($basicInformation["citizenship_id"]) ? $basicInformation["citizenship_id"]  : null;
        $updateProvider["gender"]           = isset($basicInformation["gender"]) ? $basicInformation["gender"]  : null;
        $updateProvider["supervisor_physician"] = isset($basicInformation["supervisor_physician"]) ? $basicInformation["supervisor_physician"]  : null;
        $updateProvider["place_of_birth"]       = isset($basicInformation["place_of_birth"]) ? $basicInformation["place_of_birth"]  : null;
        $updateProvider["middle_name"]          = isset($basicInformation["provider_middle_name"]) ? $basicInformation["provider_middle_name"]  : null;
        $updateProvider["name_credentials"]     = isset($basicInformation["provider_name_credentials"]) ? $basicInformation["provider_name_credentials"]  : null;


        $contactInformation         = $request->contact_information;

        $updateProvider["phone"]              = isset($contactInformation["phone_number_home"]) ? DB::raw("AES_ENCRYPT('" .    $this->sanitizePhoneNumber($contactInformation["phone_number_home"])     . "', '$key')") : null;
        $updateProvider["work_phone"]         = isset($contactInformation["phone_number_work"]) ? DB::raw("AES_ENCRYPT('" .    $this->sanitizePhoneNumber($contactInformation["phone_number_work"])     . "', '$key')") : null;
        $updateProvider["email"]              = isset($contactInformation["email"]) ? DB::raw("AES_ENCRYPT('" .    $contactInformation["email"]     . "', '$key')") : null;

        if (isset($basicInformation["provider_first_name"]) && isset($basicInformation["provider_last_name"]) && isset($contactInformation["email"])) {
            //$this->addAddedum($basicInformation["provider_first_name"],$basicInformation["provider_last_name"],$contactInformation["email"],$request->timestamp);
        }

        $providerAddress            = $request->provider_address;

        $updateProvider["address_line_one"] = isset($providerAddress["street_address"]) ? DB::raw("AES_ENCRYPT('" .    $providerAddress["street_address"]     . "', '$key')") : null;
        $updateProvider["zip_five"]         = isset($providerAddress["zip_five"]) ? $providerAddress["zip_five"]  : null;
        $updateProvider["zip_four"]         = isset($providerAddress["zip_four"]) ? $providerAddress["zip_four"]   : null;
        $updateProvider["country"]          = isset($providerAddress["country"]) ? $providerAddress["country"]   : null;
        $updateProvider["county"]           = isset($providerAddress["county"]) ? $providerAddress["county"]   : null;
        $updateProvider["city"]             = isset($providerAddress["city"]) ? $providerAddress["city"]   : null;
        $updateProvider["state"]            = isset($providerAddress["state"]) ? $providerAddress["state"]   : null;
        $updateProvider["state_code"]       = isset($providerAddress["state_code"]) ? $providerAddress["state_code"]   : null;

        $spcialities                = $request->spcialities;
        $updateProvider["primary_speciality"]       = isset($spcialities["primary_specialty"]) ? $spcialities["primary_specialty"]   : null;
        $updateProvider["secondary_speciality"]     = isset($spcialities["secondary_specialty"]) ? $spcialities["secondary_specialty"]   : null;
        $updateProvider["professional_group_id"]    = isset($spcialities["professional_group"]) ? $spcialities["professional_group"]   : null;
        $updateProvider["professional_type_id"]     = isset($spcialities["professional_type"]) ? $spcialities["professional_type"]   : null;
        $updateProvider["updated_at"]   = $this->timeStamp();

        if (!is_null($user->first_name) && $updateProvider["first_name"] != $user->first_name)
            $logMsg .= "Provider first name changed from " . $user->first_name . " to " . $updateProvider["first_name"] . " <br>";
        if (is_null($user->first_name) && (!is_null($updateProvider["first_name"]) && !empty($updateProvider["first_name"])))
            $logMsg .= "Provider first name assigned to  " . $updateProvider["first_name"] . " <br>";

        if (!is_null($user->last_name) && $updateProvider["last_name"] != $user->last_name)
            $logMsg .= "Provider last name changed from " . $user->last_name . " to " . $updateProvider["last_name"] . " <br>";
        if (is_null($user->last_name) && (!is_null($updateProvider["last_name"]) && !empty($updateProvider["last_name"])))
            $logMsg .= "Provider last name assigned to  " . $updateProvider["last_name"] . " <br>";

        if (!is_null($user->name_credentials) && $updateProvider["name_credentials"] != $user->name_credentials)
            $logMsg .= "Provider  name credentail changed from " . $user->name_credentials . " to " . $updateProvider["name_credentials"] . " <br>";
        if (is_null($user->name_credentials) && (!is_null($updateProvider["name_credentials"]) && !empty($updateProvider["name_credentials"])))
            $logMsg .= "Provider name credentail assigned to  " . $updateProvider["name_credentials"] . " <br>";

        if (!is_null($user->middle_name) && $updateProvider["middle_name"] != $user->middle_name)
            $logMsg .= "Provider middle name changed from " . $user->middle_name . " to " . $updateProvider["middle_name"] . " <br>";
        if (is_null($user->middle_name) && (!is_null($updateProvider["middle_name"]) && !empty($updateProvider["middle_name"])))
            $logMsg .= "Provider middle name assigned to  " . $updateProvider["middle_name"] . " <br>";


        if (!is_null($user->facility_npi) && $basicInformation["provider_npi_number"] != $user->facility_npi)
            $logMsg .= "Provider  npi  changed from " . $user->facility_npi . " to " . $basicInformation["provider_npi_number"] . " <br>";
        if (is_null($user->facility_npi) && (!is_null($basicInformation["provider_npi_number"]) && !empty($basicInformation["provider_npi_number"])))
            $logMsg .= "Provider   npi assigned to  " . $basicInformation["provider_npi_number"] . " <br>";

        if (!is_null($user->dob) && $basicInformation["date_of_birth"] != $user->dob)
            $logMsg .= "Provider  dob  changed from " . $user->dob . " to " . $basicInformation["date_of_birth"] . " <br>";
        if (is_null($user->dob) && (!is_null($basicInformation["date_of_birth"]) && !empty($basicInformation["date_of_birth"])))
            $logMsg .= "Provider   dob assigned to  " . $basicInformation["date_of_birth"] . " <br>";

        if (!is_null($user->ssn) && $basicInformation["ssn_number"] != $user->ssn)
            $logMsg .= "Provider  ssn  changed from " . $user->ssn . " to " . $basicInformation["ssn_number"] . " <br>";
        if (is_null($user->ssn) && (!is_null($basicInformation["ssn_number"]) && !empty($basicInformation["ssn_number"])))
            $logMsg .= "Provider   ssn assigned to  " . $basicInformation["ssn_number"] . " <br>";

        $spOld = isset($supervisionOption[$user->supervisor_physician]) ? $supervisionOption[$user->supervisor_physician] : "";
        $spNew = isset($supervisionOption[$updateProvider["supervisor_physician"]]) ? $supervisionOption[$updateProvider["supervisor_physician"]] : "";

        if (!is_null($user->supervisor_physician) && $updateProvider["supervisor_physician"] != $user->supervisor_physician)
            $logMsg .= "Provider  supervisor physician  changed from " . $spOld . " to " . $spNew . " <br>";
        if (is_null($user->supervisor_physician) && (!is_null($updateProvider["supervisor_physician"]) && !empty($updateProvider["supervisor_physician"])))
            $logMsg .= "Provider   supervisor physician assigned to  " . $spNew . " <br>";

        if (!is_null($user->place_of_birth) && $updateProvider["place_of_birth"] != $user->place_of_birth)
            $logMsg .= "Provider  place of birth  changed from " . $user->place_of_birth . " to " . $updateProvider["place_of_birth"] . " <br>";
        if (is_null($user->place_of_birth) && (!is_null($updateProvider["place_of_birth"])) && !empty($updateProvider["place_of_birth"]))
            $logMsg .= "Provider   place of birth assigned to  " . $updateProvider["place_of_birth"] . " <br>";

        if (!is_null($user->phone) && $contactInformation["phone_number_home"] != $user->phone)
            $logMsg .= "Provider  phone  changed from " . $user->phone . " to " . $contactInformation["phone_number_home"] . " <br>";
        if (is_null($user->phone) && isset($contactInformation["phone_number_home"]))
            $logMsg .= "Provider   phone assigned to  " . $contactInformation["phone_number_home"] . " <br>";

        if (!is_null($user->work_phone) && $contactInformation["phone_number_work"] != $user->work_phone)
            $logMsg .= "Provider  work phone  changed from " . $user->work_phone . " to " . $contactInformation["phone_number_work"] . " <br>";
        if (is_null($user->work_phone) && isset($contactInformation["phone_number_work"]))
            $logMsg .= "Provider   work phone assigned to  " . $contactInformation["phone_number_work"] . " <br>";

        if (!is_null($user->email) && $contactInformation["email"] != $user->email)
            $logMsg .= "Provider  email  changed from " . $user->email . " to " . $contactInformation["email"] . " <br>";
        if (is_null($user->email) && isset($contactInformation["email"]))
            $logMsg .= "Provider   email assigned to  " . $contactInformation["email"];

        $oldGender = isset($user->gender) ? $user->gender : "";
        $newGender = isset($updateProvider["gender"]) ? $updateProvider["gender"] : "";
        if ($updateProvider["gender"] != $user->gender)
            $logMsg .= "Provider  gender  changed from " . $oldGender . " to " . $newGender . " <br>";
        else
            $logMsg .= "Provider   gender assigned to  " . $newGender . " <br>";

        if (!is_null($user->address_line_one) && $providerAddress["street_address"] != $user->address_line_one)
            $logMsg .= "Provider  address  changed from " . $user->address_line_one . " to " . $providerAddress["street_address"] . " <br>";
        if (is_null($user->address_line_one) && isset($contactInformation["street_address"]))
            $logMsg .= "Provider   address assigned to  " . $providerAddress["street_address"] . " <br>";

        if ($updateProvider["zip_five"] != $user->zip_five)
            $logMsg .= "Provider  zip five  changed from " . $user->zip_five . " to " . $updateProvider["zip_five"] . " <br>";
        if (is_null($user->zip_five) && isset($updateProvider["zip_five"]))
            $logMsg .= "Provider   zip five assigned to  " . $updateProvider["zip_five"] . " <br>";

        if ($updateProvider["citizenship_id"] != $user->citizenship_id) {
            $citizenshipNew = DB::table('citizenships')->where('id', $updateProvider["citizenship_id"])->first(["name"]);
            $citizenshipOld = DB::table('citizenships')->where('id', $user->citizenship_id)->first(["name"]);
            $citizenshipNew = isset($citizenshipNew->name) ? $citizenshipNew->name : "";
            $citizenshipOld = isset($citizenshipOld->name) ? $citizenshipOld->name : "";
            $logMsg .= "Provider  citizenship  changed from " . $citizenshipOld . " to " . $citizenshipNew . " <br>";
        } else {
            $citizenshipNew = DB::table('citizenships')->where('id', $updateProvider["citizenship_id"])->first(["name"]);
            $citizenshipNew = isset($citizenshipNew->name) ? $citizenshipNew->name : "";
            $logMsg .= "Provider   citizenship assigned to  " . $citizenshipNew . " <br>";
        }

        if (!is_null($user->country) && $updateProvider["country"] != $user->country)
            $logMsg .= "Provider  country  changed from " . $user->country . " to " . $updateProvider["country"] . " <br>";
        if (is_null($user->country) && isset($updateProvider["country"]))
            $logMsg .= "Provider   country assigned to  " . $updateProvider["country"] . " <br>";

        if (!is_null($user->county) && $updateProvider["county"] != $user->county)
            $logMsg .= "Provider  county  changed from " . $user->county . " to " . $updateProvider["county"] . " <br>";
        if (is_null($user->county) && isset($updateProvider["county"]))
            $logMsg .= "Provider   county assigned to  " . $updateProvider["county"] . " <br>";

        if (!is_null($user->city) && $updateProvider["city"] != $user->city)
            $logMsg .= "Provider  city  changed from " . $user->city . " to " . $updateProvider["city"] . " <br>";
        if (is_null($user->city) && isset($updateProvider["city"]))
            $logMsg .= "Provider   city assigned to  " . $updateProvider["city"] . " <br>";

        if (!is_null($user->state) && $updateProvider["state"] != $user->state)
            $logMsg .= "Provider  state  changed from " . $user->state . " to " . $updateProvider["state"] . " <br>";
        if (is_null($user->city) && !isset($updateProvider["state"]))
            $logMsg .= "Provider   state assigned to  " . $updateProvider["state"] . " <br>";

        if (!is_null($user->state_code) && $updateProvider["state_code"] != $user->state_code)
            $logMsg .= "Provider  state code  changed from " . $user->state_code . " to " . $updateProvider["state_code"] . " <br>";
        if (is_null($user->state_code) && !is_null($updateProvider["state_code"]))
            $logMsg .= "Provider   state code assigned to  " . $updateProvider["state_code"] . " <br>";

        if ($updateProvider["primary_speciality"] != $user->primary_speciality) {
            $logMsg .= "Provider primary speciality  changed from " . $user->primary_speciality . " to " . $updateProvider["primary_speciality"] . " <br>";
        } else
            $logMsg .= "Provider   primary speciality assigned to  " . $updateProvider["primary_speciality"] . " <br>";


        if ($updateProvider["secondary_speciality"] != $user->secondary_speciality) {
            $logMsg .= "Provider secondary speciality  changed from " . $user->secondary_speciality . " to " . $updateProvider["secondary_speciality"] . " <br>";
        } else
            $logMsg .= "Provider secondary speciality assigned to  " . $updateProvider["secondary_speciality"] . " <br>";


        if ($user->professional_group_id != $updateProvider["professional_group_id"]) {
            $spcialityNew = DB::table("professional_groups")
                ->select("name")
                // ->where("type","=","solo")
                ->where("id", "=", $updateProvider["professional_group_id"])
                ->first();
            $spcialityNew = isset($spcialityNew->name) ? $spcialityNew->name : "";

            $spcialityOld = DB::table("professional_groups")
                ->select("name")
                // ->where("type","=","solo")
                ->where("id", "=", $user->professional_group_id)
                ->first();
            $spcialityOld = isset($spcialityOld->name) ? $spcialityOld->name : "";
            $logMsg .= "Provider professional group  changed from " . $spcialityOld . " to " . $spcialityNew . " <br>";
        } else {
            $spcialityNew = DB::table("professional_groups")
                ->select("name")
                // ->where("type","=","solo")
                ->where("id", "=", $updateProvider["professional_group_id"])
                ->first();
            $spcialityNew = isset($spcialityNew->name) ? $spcialityNew->name : "";
            $logMsg .= "Provider professional group  assigned to " . $spcialityNew . " <br>";
        }
        $updateProvider["professional_type_id"]     = isset($spcialities["professional_type"]) ? $spcialities["professional_type"]   : null;

        if ($user->professional_type_id != $updateProvider["professional_type_id"]) {
            $spcialityNew = DB::table("professional_types")
                ->select("name")
                // ->where("type","=","solo")
                ->where("id", "=", $updateProvider["professional_type_id"])
                ->first();
            $spcialityNew = isset($spcialityNew->name) ? $spcialityNew->name : "";

            $spcialityOld = DB::table("professional_types")
                ->select("name")
                // ->where("type","=","solo")
                ->where("id", "=", $user->professional_type_id)
                ->first();
            $spcialityOld = isset($spcialityOld->name) ? $spcialityOld->name : "";
            $logMsg .= "Provider professional type  changed from " . $spcialityOld . " to " . $spcialityNew . " <br>";
        } else {
            $spcialityNew = DB::table("professional_types")
                ->select("name")
                // ->where("type","=","solo")
                ->where("id", "=", $updateProvider["professional_type_id"])
                ->first();
            $spcialityNew = isset($spcialityNew->name) ? $spcialityNew->name : "";
            $logMsg .= "Provider professional type  assigned to " . $spcialityNew . " <br>";
        }

        User::where("id", "=", $providerId)
            ->update($updateProvider);


        $driverLicense              = $request->driver_license;
        $hasDriverLicense = DB::table("user_licenses")->where("user_id", "=", $providerId)
            ->where("type_id", "=", 23)
            ->first();

        $driverLicenseData = [];

        $driverLicenseData["user_id"]       = $providerId;
        $driverLicenseData["license_no"]    = isset($driverLicense["license_number"]) ? $providerId . "_" . $driverLicense["license_number"] : $driverLicense["license_number"];
        $driverLicenseData["issue_date"]    = $driverLicense["issue_date"];
        $driverLicenseData["exp_date"]      = $driverLicense["expiration_date"];
        $driverLicenseData["issuing_state"] = isset($driverLicense["state"]) ? $driverLicense["state"] : null;
        $driverLicenseData["type_id"]       = 23;

        if (is_object($hasDriverLicense)) {
            if (!is_null($hasDriverLicense->license_no) && $hasDriverLicense->license_no != $driverLicenseData["license_no"]) {
                if (Str::contains($hasDriverLicense->license_no, "_")) {
                    $hasDriverLicense->license_no = explode("_", $hasDriverLicense->license_no)[1];
                }
                $addLicense = $driverLicenseData["license_no"];
                if (Str::contains($driverLicenseData["license_no"], "_")) {
                    $addLicense = explode("_", $driverLicenseData["license_no"])[1];
                }
                $logMsg .= "Provider drivers license license no changed from  " . $hasDriverLicense->license_no . " to " . $addLicense . " <br>";
            }
            if (is_null($hasDriverLicense->license_no) && isset($driverLicenseData["license_no"])) {
                $addLicense = $driverLicenseData["license_no"];
                if (Str::contains($driverLicenseData["license_no"], "_")) {
                    $addLicense = explode("_", $driverLicenseData["license_no"])[1];
                }
                $logMsg .= "Provider drivers license license no assigned to  " . $addLicense . " <br>";
            }


            if (!is_null($hasDriverLicense->issue_date) && $hasDriverLicense->issue_date != $driverLicenseData["issue_date"])
                $logMsg .= "Provider drivers license issue date changed from  " . $hasDriverLicense->issue_date . " to " . $driverLicenseData["issue_date"] . " <br>";
            if (is_null($hasDriverLicense->issue_date) && isset($driverLicenseData["issue_date"]))
                $logMsg .= "Provider drivers license issue date assigned to  " . $driverLicenseData["issue_date"] . " <br>";

            if (!is_null($hasDriverLicense->exp_date) && $hasDriverLicense->exp_date != $driverLicenseData["exp_date"])
                $logMsg .= "Provider drivers license expiration date changed from  " . $hasDriverLicense->exp_date . " to " . $driverLicenseData["exp_date"] . " <br>";
            if (is_null($hasDriverLicense->exp_date) && isset($driverLicenseData["exp_date"]))
                $logMsg .= "Provider drivers license expiration date assigned to  " . $driverLicenseData["exp_date"] . " <br>";

            if (!is_null($hasDriverLicense->issuing_state) && $hasDriverLicense->issuing_state != $driverLicenseData["issuing_state"])
                $logMsg .= "Provider drivers license issuing state changed from  " . $hasDriverLicense->issuing_state . " to " . $driverLicenseData["issuing_state"] . " <br>";
            if (is_null($hasDriverLicense->issuing_state) && isset($driverLicenseData["issuing_state"]))
                $logMsg .= "Provider drivers license issuing state assigned to  " . $driverLicenseData["issuing_state"] . " <br>";

            $driverLicenseData["updated_at"] = $this->timeStamp();
            DB::table("user_licenses")->where("user_id", "=", $providerId)
                ->where("type_id", "=", 23)
                ->update($driverLicenseData);
        } else {
            if (!empty($driverLicenseData["license_no"])) {
                $addLicense = $driverLicenseData["license_no"];
                if (Str::contains($driverLicenseData["license_no"], "_")) {
                    $addLicense = explode("_", $driverLicenseData["license_no"])[1];
                }
                $logMsg .= "Provider drivers license license no assigned to  " . $addLicense . " <br>";
            }
            if (!empty($driverLicenseData["issue_date"]))
                $logMsg .= "Provider drivers license issue date assigned to  " . $driverLicenseData["issue_date"] . " <br>";
            if (!empty($driverLicenseData["exp_date"]))
                $logMsg .= "Provider drivers license expiration date assigned to  " . $driverLicenseData["exp_date"] . " <br>";
            if (!empty($driverLicenseData["issuing_state"]))
                $logMsg .= "Provider drivers license issuing state assigned to  " . $driverLicenseData["issuing_state"] . " <br>";

            $driverLicenseData["created_at"] = $this->timeStamp();
            DB::table("user_licenses")
                ->insertGetId($driverLicenseData);
        }

        $stateLicense               = $request->state_license;

        $hasStateLicense = DB::table("user_licenses")->where("user_id", "=", $providerId)
            ->where("type_id", "=", 33)
            ->first();

        $stateLicenseData = [];

        $stateLicenseData["user_id"]       = $providerId;
        $stateLicenseData["license_no"]    = isset($stateLicense["license_number"]) ? $providerId . "_" . $stateLicense["license_number"] : $stateLicense["license_number"];
        $stateLicenseData["issue_date"]    = $stateLicense["issue_date"];
        $stateLicenseData["exp_date"]      = $stateLicense["expiration_date"];
        $stateLicenseData["issuing_state"] = isset($stateLicense["state"]) ? $stateLicense["state"] : null;
        $stateLicenseData["type_id"]       = 33;

        if (is_object($hasStateLicense)) {
            if (!is_null($hasStateLicense->license_no) && $hasStateLicense->license_no != $stateLicenseData["license_no"]) {
                if (Str::contains($hasStateLicense->license_no, "_")) {
                    $hasStateLicense->license_no = explode("_", $hasStateLicense->license_no)[1];
                }
                $addLicense = $stateLicenseData["license_no"];
                if (Str::contains($stateLicenseData["license_no"], "_")) {
                    $addLicense = explode("_", $stateLicenseData["license_no"])[1];
                }
                $logMsg .= "Provider state license license no changed from  " . $hasStateLicense->license_no . " to " . $addLicense . " <br>";
            }
            if (is_null($hasStateLicense->license_no) && isset($stateLicenseData["license_no"])) {
                $addLicense = $stateLicenseData["license_no"];
                if (Str::contains($stateLicenseData["license_no"], "_")) {
                    $addLicense = explode("_", $stateLicenseData["license_no"])[1];
                }
                $logMsg .= "Provider state license license no assigned to  " . $addLicense . " <br>";
            }


            if (!is_null($hasStateLicense->issue_date) && $hasStateLicense->issue_date != $stateLicenseData["issue_date"])
                $logMsg .= "Provider state license issue date changed from  " . $hasStateLicense->issue_date . " to " . $stateLicenseData["issue_date"] . " <br>";
            if (is_null($hasStateLicense->issue_date) && isset($stateLicenseData["issue_date"]))
                $logMsg .= "Provider state license issue date assigned to  " . $stateLicenseData["issue_date"] . " <br>";

            if (!is_null($hasStateLicense->exp_date) && $hasStateLicense->exp_date != $stateLicenseData["exp_date"])
                $logMsg .= "Provider state license expiration date changed from  " . $hasStateLicense->exp_date . " to " . $stateLicenseData["exp_date"] . " <br>";
            if (is_null($hasStateLicense->exp_date) && isset($stateLicenseData["exp_date"]))
                $logMsg .= "Provider state license expiration date assigned to  " . $stateLicenseData["exp_date"] . " <br>";

            if (!is_null($hasStateLicense->issuing_state) && $hasStateLicense->issuing_state != $stateLicenseData["issuing_state"])
                $logMsg .= "Provider state license issuing state changed from  " . $hasStateLicense->issuing_state . " to " . $stateLicenseData["issuing_state"] . " <br>";
            if (is_null($hasStateLicense->issuing_state) && isset($stateLicenseData["issuing_state"]))
                $logMsg .= "Provider state license issuing state assigned to  " . $stateLicenseData["issuing_state"] . " <br>";

            $stateLicenseData["updated_at"] = $this->timeStamp();
            DB::table("user_licenses")->where("user_id", "=", $providerId)
                ->where("type_id", "=", 33)
                ->update($stateLicenseData);
        } else {
            if (!empty($stateLicenseData["license_no"])) {
                $addLicense = $stateLicenseData["license_no"];
                if (Str::contains($stateLicenseData["license_no"], "_")) {
                    $addLicense = explode("_", $stateLicenseData["license_no"])[1];
                }
                $logMsg .= "Provider state license license no assigned to  " . $addLicense . " <br>";
            }
            if (!empty($stateLicenseData["issue_date"]))
                $logMsg .= "Provider state license issue date assigned to  " . $stateLicenseData["issue_date"] . " <br>";
            if (!empty($stateLicenseData["exp_date"]))
                $logMsg .= "Provider state license expiration date assigned to  " . $stateLicenseData["exp_date"] . " <br>";
            if (!empty($stateLicenseData["issuing_state"]))
                $logMsg .= "Provider state license issuing state assigned to  " . $stateLicenseData["issuing_state"] . " <br>";

            $stateLicenseData["created_at"] = $this->timeStamp();
            DB::table("user_licenses")
                ->insertGetId($stateLicenseData);
        }

        $DEAInformation             = $request->DEA_information;
        $hasDEALicense = DB::table("user_licenses")->where("user_id", "=", $providerId)
            ->where("type_id", "=", 36)
            ->first();

        $deaLicenseData = [];

        $deaLicenseData["user_id"]       = $providerId;
        $deaLicenseData["license_no"]    = isset($DEAInformation["DEA_number"]) ? $providerId . "_" . $DEAInformation["DEA_number"] : $DEAInformation["DEA_number"];
        $deaLicenseData["issue_date"]    = $DEAInformation["issue_date"];
        $deaLicenseData["exp_date"]      = $DEAInformation["expiration_date"];
        $deaLicenseData["issuing_state"] = isset($DEAInformation["state"]) ? $DEAInformation["state"] : null;
        $deaLicenseData["type_id"]       = 36;

        if (is_object($hasDEALicense)) {
            if (!is_null($hasDEALicense->license_no) && $hasDEALicense->license_no != $deaLicenseData["license_no"]) {
                $newLicense = $deaLicenseData["license_no"];
                if (Str::contains($hasDEALicense->license_no, "_")) {
                    $hasDEALicense->license_no = explode("_", $hasDEALicense->license_no)[1];
                }
                if (Str::contains($newLicense, "_")) {
                    $newLicense = explode("_", $newLicense)[1];
                }
                $logMsg .= "Provider dea license license no changed from  " . $hasDEALicense->license_no . " to " . $newLicense . " <br>";
            }
            if (is_null($hasDEALicense->license_no) && isset($deaLicenseData["license_no"])) {
                $addLicense = $deaLicenseData["license_no"];
                if (Str::contains($deaLicenseData["license_no"], "_")) {
                    $addLicense = explode("_", $deaLicenseData["license_no"])[1];
                }
                $logMsg .= "Provider dea license license no assigned to  " . $addLicense . " <br>";
            }


            if (!is_null($hasDEALicense->issue_date) && $hasDEALicense->issue_date != $deaLicenseData["issue_date"])
                $logMsg .= "Provider dea license issue date changed from  " . $hasDEALicense->issue_date . " to " . $deaLicenseData["issue_date"] . " <br>";
            if ((is_null($hasDEALicense->issue_date)) && isset($deaLicenseData["issue_date"]))
                $logMsg .= "Provider dea license issue date assigned to  " . $deaLicenseData["issue_date"] . " <br>";

            if (!is_null($hasDEALicense->exp_date) && $hasDEALicense->exp_date != $deaLicenseData["exp_date"])
                $logMsg .= "Provider dea license expiration date changed from  " . $hasDEALicense->exp_date . " to " . $deaLicenseData["exp_date"] . " <br>";
            if (is_null($hasDEALicense->exp_date) && isset($deaLicenseData["exp_date"]))
                $logMsg .= "Provider dea license expiration date assigned to  " . $deaLicenseData["exp_date"] . " <br>";

            if (!is_null($hasDEALicense->issuing_state) && $hasDEALicense->issuing_state != $deaLicenseData["issuing_state"])
                $logMsg .= "Provider dea license issuing state changed from  " . $hasDEALicense->issuing_state . " to " . $deaLicenseData["issuing_state"] . " <br>";
            if (is_null($hasDEALicense->issuing_state) && isset($deaLicenseData["issuing_state"]))
                $logMsg .= "Provider dea license issuing state assigned to  " . $deaLicenseData["issuing_state"] . " <br>";

            $deaLicenseData["updated_at"] = $this->timeStamp();
            DB::table("user_licenses")->where("user_id", "=", $providerId)
                ->where("type_id", "=", 36)
                ->update($deaLicenseData);
        } else {
            if (!empty($deaLicenseData["license_no"])) {
                $newLicense = $deaLicenseData["license_no"];
                if (Str::contains($newLicense, "_")) {
                    $newLicense = explode("_", $newLicense)[1];
                }
                $logMsg .= "Provider dea license license no assigned to  " . $newLicense . " <br>";
            }
            if (!empty($deaLicenseData["issue_date"]))
                $logMsg .= "Provider dea license issue date assigned to  " . $deaLicenseData["issue_date"] . " <br>";
            if (!empty($deaLicenseData["exp_date"]))
                $logMsg .= "Provider dea license expiration date assigned to  " . $deaLicenseData["exp_date"] . " <br>";
            if (!empty($deaLicenseData["issuing_state"]))
                $logMsg .= "Provider dea license issuing state assigned to  " . $deaLicenseData["issuing_state"] . " <br>";

            $deaLicenseData["created_at"] = $this->timeStamp();
            DB::table("user_licenses")
                ->insertGetId($deaLicenseData);
        }

        $CAQHInformation            = $request->CAQH_information;
        $portalType = $this->fetchData("portal_types", ["name" => "CAQH"], 1, []);

        $caqhPortal = DB::table("portals")->select("user_name as username", "password", "type_id", "identifier")
            ->where("type_id", "=", $portalType->id ?? 0)
            ->where("user_id", "=", $providerId ?? 0)
            ->first();

        $CAQHData = [];

        $CAQHData["user_id"] = $providerId;
        $CAQHData["user_name"] = $CAQHInformation["username"];
        $CAQHData["password"] = isset($CAQHInformation["password"]) ? encrypt($CAQHInformation["password"]) : NULL;
        $CAQHData["identifier"] = $CAQHInformation["CAQH_id"];

        $CAQHData["type_id"] = $portalType->id ?? 0;
        if (is_object($caqhPortal)) {
            if (isset($caqhPortal->password))
                $caqhPortal->password = decrypt($caqhPortal->password);

            if (!is_null($caqhPortal->username) && $caqhPortal->username != $CAQHInformation["username"])
                $logMsg .= "Provider portal caqh user name changed from  " . $caqhPortal->username . " to " . $CAQHInformation["username"] . " <br>";
            if (is_null($caqhPortal->username) && isset($CAQHInformation["username"]))
                $logMsg .= "Provider portal caqh user name assigned to  " . $CAQHInformation["username"] . " <br>";

            if (!is_null($caqhPortal->password) && $caqhPortal->password != $CAQHInformation["password"])
                $logMsg .= "Provider portal caqh password changed from  " . $caqhPortal->password . " to " . $CAQHInformation["username"] . " <br>";
            if (is_null($caqhPortal->password) && isset($CAQHInformation["password"]))
                $logMsg .= "Provider portal caqh password assigned to  " . $CAQHInformation["password"] . " <br>";

            if (is_null($caqhPortal->identifier) && $caqhPortal->identifier != $CAQHInformation["CAQH_id"])
                $logMsg .= "Provider portal caqh identifier changed from  " . $caqhPortal->identifier . " to " . $CAQHInformation["CAQH_id"] . " <br>";
            if (is_null($caqhPortal->identifier) && isset($CAQHInformation["CAQH_id"]))
                $logMsg .= "Provider portal caqh identifier assigned to  " . $CAQHInformation["CAQH_id"] . " <br>";

            $CAQHData["updated_at"] = $this->timeStamp();
            DB::table("portals")->where("type_id", "=", $portalType->id ?? 0)
                ->where("user_id", "=", $providerId)
                ->update($CAQHData);
        } else {
            if (!empty($CAQHInformation["username"]))
                $logMsg .= "Provider portal caqh user name assigned to  " . $CAQHInformation["username"] . " <br>";
            if (!empty($CAQHInformation["password"]))
                $logMsg .= "Provider portal caqh password assigned to  " . $CAQHInformation["password"] . " <br>";
            if (!empty($CAQHInformation["CAQH_id"]))
                $logMsg .= "Provider portal caqh identifier assigned to  " . $CAQHInformation["CAQH_id"] . " <br>";

            $CAQHData["created_at"] = $this->timeStamp();
            DB::table("portals")
                ->insertGetId($CAQHData);
        }
        $NPPESPecosAccess           = $request->NPPES_pecos_access;
        $portalType = $this->fetchData("portal_types", ["name" => "NPPES"], 1, []);

        $nppesPortal = DB::table("portals")->select("user_name as username", "password", "type_id")
            ->where("type_id", "=", $portalType->id)
            ->where("user_id", "=", $providerId)
            ->first();

        $NPPESData = [];

        $NPPESData["user_id"] = $providerId;
        $NPPESData["user_name"] = $NPPESPecosAccess["username"];
        $NPPESData["password"] = isset($NPPESPecosAccess["password"]) ? encrypt($NPPESPecosAccess["password"]) : NULL;
        $NPPESData["type_id"] = $portalType->id;
        if (is_object($nppesPortal)) {
            if (isset($nppesPortal->password))
                $nppesPortal->password = decrypt($nppesPortal->password);

            if (!is_null($nppesPortal->username) && $nppesPortal->username != $NPPESPecosAccess["username"])
                $logMsg .= "Provider portal nppes user name changed from  " . $nppesPortal->user_name . " to " . $NPPESPecosAccess["username"] . " <br>";
            if (is_null($nppesPortal->username) && isset($NPPESPecosAccess["username"]))
                $logMsg .= "Provider portal nppes user name assigned to  " . $NPPESPecosAccess["username"] . " <br>";

            if (!is_null($nppesPortal->password) && $nppesPortal->password != $NPPESPecosAccess["password"])
                $logMsg .= "Provider portal nppes password changed from  " . $nppesPortal->password . " to " . $NPPESPecosAccess["username"] . " <br>";
            if (is_null($nppesPortal->password) && isset($NPPESPecosAccess["password"]))
                $logMsg .= "Provider portal nppes password assigned to  " . $NPPESPecosAccess["password"] . " <br>";


            $NPPESData["updated_at"] = $this->timeStamp();
            DB::table("portals")->where("type_id", "=", $portalType->id)
                ->where("user_id", "=", $providerId)
                ->update($NPPESData);
        } else {
            if (!empty($NPPESPecosAccess["username"]))
                $logMsg .= "Provider portal nppes user name assigned to  " . $NPPESPecosAccess["username"] . " <br>";
            if (!empty($NPPESPecosAccess["password"]))
                $logMsg .= "Provider portal nppes password assigned to  " . $NPPESPecosAccess["password"] . " <br>";

            $NPPESData["created_at"] = $this->timeStamp();
            DB::table("portals")
                ->insertGetId($NPPESData);
        }
        if (strlen($logMsg) > 0) {

            $sessionUserId = $this->getSessionUserId($request);
            // $this->addDirectoryLogs($providerId, $sessionUserId, $logMsg, "Provider");
            DB::table("provider_logs")->insertGetId([
                "provider_id" => $providerId, "session_userid" => $sessionUserId, "section" => "Provider Profile",
                "action" => "Update",
                "log" => DB::raw("AES_ENCRYPT('" .    $logMsg     . "', '$key')"),
                'created_at' => $this->timeStamp()
            ]);
        }
        return $this->successResponse(["is_update" => true, 'user' => $user, "log_msg" => $logMsg], "Data updated successfully");
    }
    /**
     *add the new practice
     *
     * @param $practice
     */
    private function addNewPractice($practice)
    {
        $key = $this->key;
        $isValidPractice        = true;
        $isValidPracticeEmail   = true;
        $isValidPracticeNPI     = true;

        $practiceName   = $practice['practice_name'];
        $practiceEmail  = $practice['contact_information']['email'];
        $practiceNpi    = $practice['npi_number'];
        $createPractice = [
            "email" => isset($practiceEmail) ? DB::raw("AES_ENCRYPT('" .    $practiceEmail     . "', '$key')") : NULL,
            "password" => NULL,
            'is_complete' => $practice['is_complete'],
            'profile_complete_percentage' => $practice['profile_complete_percentage'],
            "created_at" => $this->timeStamp()
        ];
        //$this->printR($createPractice,true);
        $errors = [];
        if (isset($practiceName) && strlen($practiceName) > 0) {
            $isValidPractice = $this->isPracticeNameUnique($practiceName);
            $error =  "The practice name $practiceName is already taken. Please choose another name";
            if ($isValidPractice == false)
                array_push($errors, $error);
        }
        if (isset($practiceEmail) && strlen($practiceEmail) > 0) {
            $isValidPracticeEmail = $this->isPracticeEmailUnique($practiceEmail);
            $error =  "The practice email $isValidPracticeEmail is already taken. Please choose another email";
            if ($isValidPracticeEmail == false)
                array_push($errors, $error);
        }
        if (isset($practiceNpi) && strlen($practiceNpi) > 0) {
            $isValidPracticeNPI = $this->isPracticeNPIUnique($practiceNpi);
            $error =  "The practice npi $practiceNpi is already taken. Please choose another npi";
            if ($isValidPracticeNPI == false)
                array_push($errors, $error);
        }
        if (count($errors) > 0) {
            return  ["is_error" => true, "id" => 0, "errors" => $errors];
        } else {


            $user = User::create($createPractice); //create the practice profile

            $user->createToken($practiceName . " Token")->plainTextToken; //create the practice token

            $practiceId = $user->id;

            $compMap = [
                'user_id' => $practiceId,
                'company_id' => 1
            ];
            $this->addData("user_company_map", $compMap);

            $this->addData("user_role_map",  ["user_id" => $practiceId, "role_id" => 9, "role_preference" => 1], 0); //assign the role the new practice

            $this->addPracticeBusinessRelatedData($practiceId, $practice); //add the practice business related data
            $this->addPracticeBankInfo($practiceId, $practice); //add the practice bank info
            $this->addPracticeBusinessInfo($practiceId, $practice); //add the practice bank info
            $this->addPracticeOwner($practiceId, $practice); //add the practice owner
            //$this->addPrimaryPracticeFacility($practiceId, $practice); //create the facility

            return  ["is_error" => false, "id" => $practiceId, "errors" => $errors];
        }
    }

    /**
     * fetch the profile percentage
     *
     * @param $userId
     */
    private function getProfilePercentage($userId)
    {
        return DB::table("users")
            ->where("id", "=", $userId)
            ->first(["profile_complete_percentage", "is_complete", "status", "profile_image", "image_settings"]);
    }
    /**
     * fetch the profile image
     *
     * @param $userId
     */
    private function getProfileImage($userId, $table)
    {
        return DB::table($table)
            ->where("user_id", "=", $userId)
            ->first(["profile_image", "image_settings"]);
    }
    /**
     * fetch the provider uploaded files
     *
     * @param $userId
     */
    private function providerFiles($userId)
    {
        $files = DB::table("provider_attachments")
            ->select(["file_name", "doc_name"])
            ->where("provider_id", "=", $userId)
            ->orderBy("id", "desc")
            ->groupBy("doc_name")
            ->get();
        $filesRes = [];
        if (count($files)) {
            foreach ($files as $file)
                $filesRes[$file->doc_name] = $file->file_name;
        }
        return $filesRes;
    }
    /**
     * get the provider form for pre filled
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function providerAttachments(Request $request)
    {

        $request->validate([
            "provider_id" => "required",
        ]);

        $providerId = $request->provider_id;

        $attachments = DB::table("provider_attachments")
            ->select("provider_attachments.*", "users.first_name", "users.last_name")
            ->join("users", "users.id", "=", "provider_attachments.created_by")
            ->where("provider_id", "=", $providerId)

            ->orderBy("id", "desc")

            ->get();

        return $this->successResponse(["attachments" => $attachments], "success");
    }
    /**
     * get the provider form for pre filled
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function facilityAttachments(Request $request)
    {

        $request->validate([
            "facility_id" => "required",
        ]);

        $facilityId = $request->facility_id;

        $attachments = DB::table("facility_attachments")

            ->select("facility_attachments.*", "users.first_name", "users.last_name")

            ->join("users", "users.id", "=", "facility_attachments.created_by")

            ->where("facility_id", "=", $facilityId)

            ->orderBy("id", "desc")

            ->get();

        return $this->successResponse(["attachments" => $attachments], "success");
    }
    /**
     * fetch the owner data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchOwnerData(Request $request)
    {

        $request->validate([
            "owner_id" => "required",
            "practice_id" => "required"
        ]);

        $ownerId = $request->owner_id;
        $practiceId = $request->practice_id;
        $key = $this->key;
        $owner = DB::table("user_ddownerinfo")
            ->select(
                "user_ddownerinfo.name as name_of_owner",
                "owners_map.percentage as ownership_percentage",
                "user_ddownerinfo.num_of_owners",
                "user_ddownerinfo.pob as place_of_birth",
                DB::raw("DATE_FORMAT(AES_DECRYPT(cm_user_ddownerinfo.dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.dob,'$key') as date_of_birth"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"),
                "user_ddownerinfo.city",
                "user_ddownerinfo.state",
                "user_ddownerinfo.zip_five",
                "user_ddownerinfo.zip_four",
                "user_ddownerinfo.country",
                "user_ddownerinfo.county",
                DB::raw("cm_owners_map.date_of_ownership as date_of_ownership"),
                DB::raw("DATE_FORMAT(cm_owners_map.date_of_ownership,'%m/%d/%Y') as format_date_of_ownership"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.address,'$key') as street_address"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.phone,'$key') as phone_number"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.fax,'$key') as fax_number"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.email,'$key') as email"),
                "user_ddownerinfo.id",
                "user_ddownerinfo.profile_complete_percentage",
                "user_ddownerinfo.profile_image",
                "user_ddownerinfo.image_settings"
            )
            ->join("owners_map", "owners_map.owner_id", "=", "user_ddownerinfo.id")
            ->where("owners_map.owner_id", $ownerId)
            ->where("owners_map.practice_id", $practiceId)
            ->first();

        $ownerResData = [];
        if (is_object($owner)) {
            $ownerResData["owner_id"] = $owner->id;
            $ownerResData["practice_id"] = $practiceId;
            $ownerResData["name_of_owner"] = $owner->name_of_owner;
            $ownerResData["ownership_percentage"] = $owner->ownership_percentage;
            $ownerResData["ssn_number"] = $owner->ssn_number;
            $ownerResData["date_of_birth"] = $owner->date_of_birth;
            $ownerResData["place_of_birth"] = $owner->place_of_birth;
            $ownerResData["date_of_ownership"] = $owner->date_of_ownership;
            $ownerResData["profile_complete_percentage"] = $owner->profile_complete_percentage;
            $ownerResData["profile_image_url"] = "eCA/profile/" . $owner->profile_image;
            $ownerResData["profile_image"] = $owner->profile_image;
            $ownerResData["image_settings"] = $owner->image_settings;
            $ownerResData["address"] = [
                "zip_five" => $owner->zip_five,
                "zip_four" => $owner->zip_four,
                "street_address" => $owner->street_address,
                "country" => $owner->country,
                "city" => $owner->city,
                "state" => $owner->state,
                "county" => $owner->county,
                "phone_number" => $owner->phone_number,
                "fax_number" => $owner->fax_number,
                "email" => $owner->email,

            ];
        } else
            $ownerResData = NULL;

        return $this->successResponse(["owner" => $ownerResData], "success");
    }
    /**
     * update owner information
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateOwnerData($ownerId, Request $request)
    {
        $key = $this->key;
        $practiceId = $request->practice_id;

        $owner = DB::table("user_ddownerinfo")
            ->select(
                "user_ddownerinfo.name as name_of_owner",
                "owners_map.percentage as ownership_percentage",
                "user_ddownerinfo.num_of_owners",
                "user_ddownerinfo.pob as place_of_birth",
                DB::raw("DATE_FORMAT(AES_DECRYPT(cm_user_ddownerinfo.dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.dob,'$key') as date_of_birth"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"),
                "user_ddownerinfo.city",
                "user_ddownerinfo.state",
                "user_ddownerinfo.zip_five",
                "user_ddownerinfo.zip_four",
                "user_ddownerinfo.country",
                "user_ddownerinfo.county",
                DB::raw("cm_owners_map.date_of_ownership as date_of_ownership"),
                DB::raw("DATE_FORMAT(cm_owners_map.date_of_ownership,'%m/%d/%Y') as format_date_of_ownership"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.address,'$key') as street_address"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.phone,'$key') as phone_number"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.fax,'$key') as fax_number"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.email,'$key') as email"),
                "user_ddownerinfo.id",
                "user_ddownerinfo.profile_complete_percentage"
            )
            ->join("owners_map", "owners_map.owner_id", "=", "user_ddownerinfo.id")
            ->where("owners_map.owner_id", $ownerId)
            ->where("owners_map.practice_id", $practiceId)
            ->first();

        $ownerName = $request->name_of_owner;

        $ownerPerc = $request->ownership_percentage;

        $ownerSSN = $request->ssn_number;

        $dob = $request->date_of_birth;

        $pob = $request->place_of_birth;

        $doOwner = $request->date_of_ownership;

        $noOfOwners = $request->num_of_owners;
        // $this->printR($request->address,true);
        $address    = isset($request->address) ? $request->address : NULL;

        $ownerName = strtoupper($ownerName);

        $effectiveDate = isset($request->date_of_ownership) ? $request->date_of_ownership : NULL;

        $ownerData = [];
        $ownerMapData = [];
        $ownerData["profile_complete_percentage"]  = $request->profile_complete_percentage;
        $ownerData["name"]                   = isset($ownerName) ? $ownerName : NULL;
        $ownerMapData["percentage"]         = isset($ownerPerc) ? $ownerPerc : NULL;
        $ownerData["ssn"]                   = isset($ownerSSN) ? DB::raw("AES_ENCRYPT('" .    $ownerSSN     . "', '$key')") : NULL;
        $ownerData["dob"]                   = isset($dob) ? DB::raw("AES_ENCRYPT('" .    $dob     . "', '$key')") : NULL;
        $ownerData["pob"]                   = isset($pob) ? $pob : NULL;

        $addressStreet = isset($address['street_address']) ? $address['street_address'] : NULL;
        $phone = isset($address['phone_number']) ? $address['phone_number'] : NULL;
        $fax = isset($address['fax_number']) ? $address['fax_number'] : NULL;
        $email = isset($address['email']) ? $address['email'] : NULL;
        //$ownerData["date_of_ownership"]      = isset($doOwner) ? $doOwner : NULL;
        $ownerData["num_of_owners"]          = isset($noOfOwners) ? $noOfOwners : NULL;
        $ownerData["city"]                   = isset($address['city']) ? $address['city'] : NULL;
        $ownerData["state"]                  = isset($address['state']) ? $address['state'] : NULL;
        $ownerData["zip_five"]               = isset($address['zip_five']) ? $address['zip_five'] : NULL;
        $ownerData["zip_four"]               = isset($address['zip_four']) ? $address['zip_four'] : NULL;
        $ownerData["country"]                = isset($address['country']) ? $address['country']  : NULL;
        $ownerData["county"]                 = isset($address['county']) ? $address['county'] : NULL;
        $ownerMapData["date_of_ownership"]   = isset($effectiveDate) ? $effectiveDate : NULL;
        $ownerData["address"]                = isset($address['street_address']) ? DB::raw("AES_ENCRYPT('" .     $addressStreet    . "', '$key')") : NULL;
        $ownerData["phone"]                  = isset($address['phone_number']) ? DB::raw("AES_ENCRYPT('" .   $phone      . "', '$key')") : NULL;
        $ownerData["fax"]                    = isset($address['fax_number']) ? DB::raw("AES_ENCRYPT('" .   $fax      . "', '$key')") : NULL;
        $ownerData["email"]                  = isset($address['email']) ? DB::raw("AES_ENCRYPT('" .   $email      . "', '$key')") : NULL;

        $logMsg = "";
        if ($owner->name_of_owner != $ownerName && !is_null($owner->name_of_owner)) {
            $logMsg .= " Owner name changed from <b>" . $owner->name_of_owner . "</b> to <b>" . $ownerName . "</b> <br>";
        }
        if (is_null($owner->name_of_owner) && !is_null($ownerName) && !empty($ownerName)) {
            $logMsg .= " Owner name assigned to <b>" . $ownerName . "</b> <br>";
        }

        if ($owner->ownership_percentage != $ownerPerc && !is_null($owner->ownership_percentage)) {
            $logMsg .= " Owner percentage changed from <b>" . $owner->ownership_percentage . "</b> to <b>" . $ownerPerc . "</b> <br>";
        }
        if (is_null($owner->ownership_percentage) && !is_null($ownerPerc) && !empty($ownerPerc)) {
            $logMsg .= " Owner percentage assigned to <b>" . $ownerPerc . "</b> <br>";
        }

        if ($owner->ssn_number != $ownerSSN && !is_null($owner->ssn_number)) {
            $logMsg .= " Owner ssn changed from <b>" . $owner->ssn_number . "</b> to <b>" . $ownerSSN . "</b> <br>";
        }
        if (is_null($owner->ssn_number) && !is_null($ownerSSN) && !empty($ownerSSN)) {
            $logMsg .= " Owner ssn assigned to <b>" . $ownerSSN . "</b> <br>";
        }

        if ($owner->date_of_birth != $dob && !is_null($owner->date_of_birth)) {
            $logMsg .= " Owner date of birth changed from <b>" . $owner->date_of_birth . "</b> to <b>" . $dob . "</b> <br>";
        }
        if (is_null($owner->date_of_birth) && !is_null($dob) && !empty($dob)) {
            $logMsg .= " Owner date of birth assigned to <b>" . $dob . "</b> <br>";
        }

        if ($owner->place_of_birth != $pob && !is_null($owner->place_of_birth)) {
            $logMsg .= " Owner date of birth changed from <b>" . $owner->place_of_birth . "</b> to <b>" . $pob . "</b> <br>";
        }
        if (is_null($owner->place_of_birth) && !is_null($dob) && !empty($dob)) {
            $logMsg .= " Owner date of birth assigned to <b>" . $pob . "</b> <br>";
        }

        if ($owner->date_of_ownership != $doOwner && !is_null($owner->date_of_ownership)) {
            $logMsg .= " Owner date of ownership changed from <b>" . $owner->date_of_ownership . "</b> to <b>" . $doOwner . "</b> <br>";
        }
        if (is_null($owner->date_of_ownership) && !is_null($doOwner) && !empty($doOwner)) {
            $logMsg .= " Owner date of ownership assigned to <b>" . $doOwner . "</b> <br>";
        }

        if ($owner->num_of_owners != $noOfOwners && !is_null($owner->num_of_owners)) {
            $logMsg .= " Owner no of owners changed from <b>" . $owner->num_of_owners . "</b> to <b>" . $noOfOwners . "</b> <br>";
        }
        if (is_null($owner->num_of_owners) && !is_null($noOfOwners) && !empty($noOfOwners)) {
            $logMsg .= " Owner no of owners assigned to <b>" . $noOfOwners . "</b> <br>";
        }

        if ($owner->city != $address['city'] && !is_null($owner->city)) {
            $logMsg .= " Owner city changed from <b>" . $owner->city . "</b> to <b>" . $address['city'] . "</b> <br>";
        }
        if (is_null($owner->city) && !is_null($address['city']) && !empty($address['city'])) {
            $logMsg .= " Owner city assigned to <b>" . $address['city'] . "</b> <br>";
        }

        if ($owner->state != $address['state'] && !is_null($owner->state)) {
            $logMsg .= " Owner state changed from <b>" . $owner->state . "</b> to <b>" . $address['state'] . "</b> <br>";
        }
        if (is_null($owner->state) && !is_null($address['state']) && !empty($address['state'])) {
            $logMsg .= " Owner state assigned to <b>" . $address['state'] . "</b> <br>";
        }

        if ($owner->zip_five != $address['zip_five'] && !is_null($owner->zip_five)) {
            $logMsg .= " Owner zip five changed from <b>" . $owner->zip_five . "</b> to <b>" . $address['zip_five'] . "</b> <br>";
        }
        if (is_null($owner->zip_five) && !is_null($address['zip_five']) && !empty($address['zip_five'])) {
            $logMsg .= " Owner zip five assigned to <b>" . $address['zip_five'] . "</b> <br>";
        }

        // if ($owner->zip_four != $address['zip_four'] && !is_null($owner->zip_four)) {
        //     $logMsg .= " Owner zip four changed from <b>" . $owner->zip_four . "</b> to <b>" . $address['zip_four'] . "</b> <br>";
        // }
        // if (is_null($owner->zip_four) && !is_null($address['zip_four']) && !empty($address['zip_four'])) {
        //     $logMsg .= " Owner zip four assigned to <b>" . $address['zip_four'] . "</b> <br>";
        // }

        if ($owner->country != $address['country'] && !is_null($owner->country)) {
            $logMsg .= " Owner country changed from <b>" . $owner->country . "</b> to <b>" . $address['country'] . "</b> <br>";
        }
        if (is_null($owner->country) && !is_null($address['country']) && !empty($address['country'])) {
            $logMsg .= " Owner country assigned to <b>" . $address['country'] . "</b> <br>";
        }

        if ($owner->county != $address['county'] && !is_null($owner->county)) {
            $logMsg .= " Owner county changed from <b>" . $owner->county . "</b> to <b>" . $address['county'] . "</b> <br>";
        }
        if (is_null($owner->county) && !is_null($address['county']) && !empty($address['county'])) {
            $logMsg .= " Owner country assigned to <b>" . $address['county'] . "</b> <br>";
        }

        if ($owner->date_of_ownership != $effectiveDate && !is_null($owner->date_of_ownership)) {
            $logMsg .= " Owner effective date changed from <b>" . $owner->date_of_ownership . "</b> to <b>" . $effectiveDate . "</b> <br>";
        }
        if (is_null($owner->date_of_ownership) && !is_null($effectiveDate) && !empty($effectiveDate)) {
            $logMsg .= " Owner effective date assigned to <b>" . $effectiveDate . "</b> <br>";
        }
        //address
        if ($owner->street_address != $address['street_address'] && !is_null($owner->street_address)) {
            $logMsg .= " Owner address changed from <b>" . $owner->street_address . "</b> to <b>" . $address['street_address'] . "</b> <br>";
        }
        if (is_null($owner->street_address) && !is_null($address['street_address']) && !empty($address['street_address'])) {
            $logMsg .= " Owner zip four assigned to <b>" . $address['street_address'] . "</b> <br>";
        }

        if ($owner->phone_number != $address['phone_number'] && !is_null($owner->phone_number)) {
            $logMsg .= " Owner phone changed from <b>" . $owner->phone_number . "</b> to <b>" . $address['phone_number'] . "</b> <br>";
        }
        if (is_null($owner->phone_number) && !is_null($address['phone_number']) && !empty($address['phone_number'])) {
            $logMsg .= " Owner zip four assigned to <b>" . $address['phone_number'] . "</b> <br>";
        }

        if ($owner->fax_number != $address['fax_number'] && !is_null($owner->fax_number)) {
            $logMsg .= " Owner phone changed from <b>" . $owner->fax_number . "</b> to <b>" . $address['fax_number'] . "</b> <br>";
        }
        if (is_null($owner->fax_number) && !is_null($address['fax_number']) && !empty($address['fax_number'])) {
            $logMsg .= " Owner zip four assigned to <b>" . $address['fax_number'] . "</b> <br>";
        }

        if ($owner->email != $address['email'] && !is_null($owner->email)) {
            $logMsg .= " Owner email changed from <b>" . $owner->email . "</b> to <b>" . $address['email'] . "</b> <br>";
        }
        if (is_null($owner->email) && !is_null($address['email']) && !empty($address['email'])) {
            $logMsg .= " Owner email assigned to <b>" . $address['email'] . "</b> <br>";
        }

        // echo $logMsg;
        // exit;
        $isUpdate = DB::table("user_ddownerinfo")

            ->where("id", $ownerId)

            ->update($ownerData);

        DB::table("owners_map")

            ->where("owner_id", $ownerId)

            ->where("practice_id", $practiceId)

            ->update($ownerMapData);

        $sessionUserId = $this->getSessionUserId($request);
        if (strlen($logMsg))
            $this->addDirectoryLogs($ownerId, $sessionUserId, $logMsg, "Owner");

        return $this->successResponse(['is_update' => true], "success");
    }
    /**
     * get the provider form for pre filled
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchProviderForm(Request $request)
    {

        $request->validate([
            "provider_id" => "required"
        ]);


        $providerId = $request->provider_id;

        $providerBasicInfo  = $this->fetchProviderBasicInformation($providerId);
        $contactInfo        = $this->fetchProviderContactInfo($providerId);
        $address            = $this->fetchProviderAddress($providerId);
        $specialties        = $this->fetchProviderSpecialities($providerId);
        $driverLicense      = $this->fetchProviderDriverLicense($providerId);
        $stateLicense       = $this->fetchProviderStateLicense($providerId);
        $deaInfo            = $this->fetchDEALicense($providerId);
        $CAQHPortal         = $this->fetchCAQHInfo($providerId);
        $NPPESPortal        = $this->fetchNPPESInfo($providerId);
        $facilities         = $this->providerFacilities($providerId);
        $practices          = $this->providerPractices($facilities);
        $files              = $this->providerFiles($providerId);

        $providerInfo = [
            "provider_id" => $providerId,
            "profile_complete_percentage" => is_object($this->getProfilePercentage($providerId)) ? $this->getProfilePercentage($providerId)->profile_complete_percentage  : null,
            "is_complete" => is_object($this->getProfilePercentage($providerId)) ? $this->getProfilePercentage($providerId)->is_complete  : null,
            "status" => is_object($this->getProfilePercentage($providerId)) ? $this->getProfilePercentage($providerId)->status  : null,
            "basic_information"     => $providerBasicInfo,
            "contact_information"   => $contactInfo,
            "provider_address"      => $address,
            "spcialities"           => $specialties,
            "driver_license"        => $driverLicense,
            "state_license"         => $stateLicense,
            "DEA_information"       => $deaInfo,
            "CAQH_information"      => $CAQHPortal,
            "NPPES_pecos_access"    => $NPPESPortal,
            "facilities"            => $facilities,
            "practices"             => $practices,
            "files"                 => $files
        ];
        return $this->successResponse($providerInfo, "success");
    }

    /**
     * fetch provider practices
     *
     * @param string $provider
     * @return array
     */
    private function providerPractices($facilities)
    {
        // $this->printR($facilities,true);
        $practices = [];
        if (count($facilities)) {
            $facilities = $this->stdToArray($facilities);
            $practiceIds = array_column($facilities, "practice_id");
            $practices = DB::table("user_baf_practiseinfo")
                ->select("practice_name", "user_id as practice_id")
                ->whereIn("user_id", $practiceIds)
                ->get();
        }
        return $practices;
    }
    /**
     * fetch provider facilities
     *
     * @param string $provider
     * @return array
     */
    private function providerFacilities($provider)
    {
        $key = $this->key;
        $facilities = DB::table("individualprovider_location_map")

            ->select(
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name,'$key') as facility_name"),
                "individualprovider_location_map.location_user_id as facility_id",
                "user_ddpracticelocationinfo.user_parent_id as practice_id"
            )

            ->join("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "individualprovider_location_map.location_user_id")

            ->where("individualprovider_location_map.user_id", "=", $provider)

            ->whereRaw('cm_user_ddpracticelocationinfo.practice_name IS NOT NULL')

            ->get();
        if (count($facilities)) {
            foreach ($facilities as $facility) {
                $facility->approval_percentage = $this->fetchProviderCredsInfo($facility->facility_id,$provider);
            }
        }
        return $facilities;
    }
    /**
     * fetch provider credentialing %
     * 
     * @param $providerId
     * @param $facilityId
     */
    private function fetchProviderCredsInfo($facilityId,$providerId)
    {
        $approvalPercentage = Credentialing::select('user_id', 'user_parent_id')
        ->selectRaw(
            '(COUNT(CASE WHEN credentialing_status_id = ? THEN 1 END) / 
            NULLIF(COUNT(id) - COUNT(CASE WHEN credentialing_status_id = ? THEN 1 END), 0) * 100) AS approval_percentage',
            [3, 8]
        )
        ->where('user_id', $providerId)
        ->where('user_parent_id', $facilityId)
        ->groupBy('user_id', 'user_parent_id')
        ->first();
        return is_object($approvalPercentage) ? round($approvalPercentage->approval_percentage,2) : null;
    
    }
    /**
     * fetch the provider basic information
     *
     * @param $providerId
     */
    private function fetchProviderBasicInformation($providerId)
    {
        $key = $this->key;
        $provider = DB::table("users")
            ->select(
                DB::raw("AES_DECRYPT(facility_npi,'$key') as provider_npi_number"),
                "first_name as provider_first_name",
                "last_name as provider_last_name",
                DB::raw("AES_DECRYPT(dob,'$key') as date_of_birth"),
                DB::raw("DATE_FORMAT(AES_DECRYPT(dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                "place_of_birth",
                DB::raw("AES_DECRYPT(ssn,'$key') as ssn_number"),
                "citizenship_id",
                "gender",
                "profile_image",
                "supervisor_physician",
                "image_settings",
                "middle_name as provider_middle_name",
                "name_credentials as provider_name_credentials",

            )
            ->where("id", "=", $providerId)
            ->first();
        if (is_object($provider))
            $provider->profile_image_url = "eCA/profile/" . $provider->profile_image;

        return $provider;
    }
    /**
     * fetch the provider basic information
     *
     * @param $providerId
     */
    private function fetchProviderContactInfo($providerId)
    {
        $key = $this->key;
        $provider = DB::table("users")
            ->select(
                DB::raw("AES_DECRYPT(phone,'$key') as phone_number_home"),
                DB::raw("AES_DECRYPT(work_phone,'$key') as phone_number_work"),
                DB::raw("AES_DECRYPT(email,'$key') as email")
            )
            ->where("id", "=", $providerId)
            ->first();
        return $provider;
    }
    /**
     * fetch the provider basic information
     *
     * @param $providerId
     */
    private function fetchProviderAddress($providerId)
    {
        $key = $this->key;
        $provider = DB::table("users")
            ->select(
                "zip_five",
                "zip_four",
                DB::raw("AES_DECRYPT(address_line_one,'$key') as street_address"),
                "country",
                "county",
                "city",
                "state",
                "state_code"
            )
            ->where("id", "=", $providerId)
            ->first();
        return $provider;
    }
    /**
     * fetch the provider basic information
     *
     * @param $providerId
     */
    private function fetchProviderSpecialities($providerId)
    {
        $provider = DB::table("users")
            ->select(
                "primary_speciality as primary_specialty",
                "secondary_speciality as secondary_specialty",
                "professional_group_id as professional_group",
                "professional_type_id as professional_type"
            )
            ->where("id", "=", $providerId)
            ->first();
        return $provider;
    }
    /**
     * fetch the provider driver license information
     *
     * @param $providerId
     */
    private function fetchProviderDriverLicense($providerId)
    {

        $provider = DB::table("user_licenses")
            ->select(
                "license_no as license_number",
                "issue_date as issue_date",
                "exp_date as expiration_date",
                "issuing_state as state"
            )
            ->where("user_id", "=", $providerId)
            ->where("type_id", "=", 23)
            ->first();
        if (is_object($provider)) {
            if (!is_null($provider->license_number) && Str::contains($provider->license_number, "_")) {
                $licenseParts = explode("_", $provider->license_number);
                if (is_array($licenseParts))
                    $provider->license_number = $licenseParts[1];
            }
            if (!is_null($provider->issue_date))
                $provider->issue_date_format = date('m/d/Y', strtotime($provider->issue_date));
            if (!is_null($provider->expiration_date))
                $provider->expiration_date_format = date('m/d/Y', strtotime($provider->expiration_date));
        }
        return $provider;
    }
    /**
     * fetch the provider driver state licese
     *
     * @param $providerId
     */
    private function fetchProviderStateLicense($providerId)
    {

        $provider = DB::table("user_licenses")
            ->select(
                "license_no as license_number",
                "issue_date as issue_date",
                "exp_date as expiration_date",
                "issuing_state as state"
            )
            ->where("user_id", "=", $providerId)
            ->where("type_id", "=", 33)
            ->first();

        if (is_object($provider)) {
            if (!is_null($provider->license_number) && Str::contains($provider->license_number, "_")) {
                $licenseParts = explode("_", $provider->license_number);
                if (is_array($licenseParts))
                    $provider->license_number = $licenseParts[1];
            }
            if (!is_null($provider->issue_date))
                $provider->issue_date_format = date('m/d/Y', strtotime($provider->issue_date));
            if (!is_null($provider->expiration_date))
                $provider->expiration_date_format = date('m/d/Y', strtotime($provider->expiration_date));
        }
        return $provider;
    }
    /**
     * fetch DEA portal
     *
     * @param $providerId
     */
    private function fetchDEALicense($providerId)
    {

        $provider = DB::table("user_licenses")
            ->select(
                "license_no as DEA_number",
                "issue_date as issue_date",
                "exp_date as expiration_date",
                "issuing_state as state"
            )
            ->where("user_id", "=", $providerId)
            ->where("type_id", "=", 36)
            ->first();

        if (is_object($provider)) {
            if (!is_null($provider->DEA_number) && Str::contains($provider->DEA_number, "_")) {
                $licenseParts = explode("_", $provider->DEA_number);
                if (is_array($licenseParts))
                    $provider->DEA_number = $licenseParts[1];
            }
            if (!is_null($provider->issue_date))
                $provider->issue_date_format = date('m/d/Y', strtotime($provider->issue_date));
            if (!is_null($provider->expiration_date))
                $provider->expiration_date_format = date('m/d/Y', strtotime($provider->expiration_date));
        }
        return $provider;
    }
    /**
     * fetch the CAQH information
     *
     * @param $providerId
     */
    private function fetchCAQHInfo($providerId)
    {

        $portalType = $this->fetchData("portal_types", ["name" => "CAQH"], 1, []);
        $nppesPortal = null;
        if (is_object($portalType)) {
            $nppesPortal = DB::table("portals")->select("user_name as username", "password", "identifier as CAQH_id")
                ->where("type_id", "=", $portalType->id)
                ->where("user_id", "=", $providerId)
                ->first();
            if (is_object($nppesPortal) && isset($nppesPortal->password) && strlen($nppesPortal->password) > 5)
                $nppesPortal->password = decrypt($nppesPortal->password);
        }

        return $nppesPortal;
    }
    /**
     * fetch the CAQH information
     *
     * @param $providerId
     */
    private function fetchNPPESInfo($providerId)
    {

        $portalType = $this->fetchData("portal_types", ["name" => "NPPES"], 1, []);

        $nppesPortal = DB::table("portals")->select("user_name as username", "password")
            ->where("type_id", "=", isset($portalType->id) ? $portalType->id : 0)
            ->where("user_id", "=", $providerId)
            ->first();
        if (is_object($nppesPortal) && isset($nppesPortal->password) && strlen($nppesPortal->password) > 5)
            $nppesPortal->password = decrypt($nppesPortal->password);
        return $nppesPortal;
    }
    /**
     * fetch the provider uploaded files
     *
     * @param $userId
     */
    private function facilityFiles($userId)
    {
        $files = DB::table("facility_attachments")
            ->select(["file_name", "doc_name"])
            ->where("facility_id", "=", $userId)
            ->orderBy("id", "desc")
            ->groupBy("doc_name")
            ->get();
        $filesRes = [];
        if (count($files)) {
            foreach ($files as $file)
                $filesRes[$file->doc_name] = $file->file_name;
        }
        return $filesRes;
    }
    /**
     * get the facility form for pre filled
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchFacilityForm(Request $request)
    {
        $request->validate([
            "facility_id" => "required"
        ]);
        $key = $this->key;
        $facilityId = $request->facility_id;

        $facility = DB::table("user_ddpracticelocationinfo")
            ->select(
                DB::raw("AES_DECRYPT(npi,'$key') as facility_npi_number"),
                DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"),
            )
            ->where("user_id", "=", $facilityId)
            ->first();
        $facilityContactInfo    = $this->facilityContactInformation($facilityId);
        $facilityAddress        = $this->facilityAddress($facilityId);
        $facilityTimings        = $this->facilityTimings($facilityId);
        $providers              = $this->fetchProviders($facilityId);
        $files                  = $this->facilityFiles($facilityId);
        $facilityUsers          = $this->facilityUsers($facilityId);
       

        $tbl = "user_ddpracticelocationinfo";
        $facilityForm = [
            "facility_id" => $facilityId,
            "profile_complete_percentage" => is_object($this->getProfilePercentage($facilityId)) ? $this->getProfilePercentage($facilityId)->profile_complete_percentage  : null,
            "is_complete" => is_object($this->getProfilePercentage($facilityId)) ? $this->getProfilePercentage($facilityId)->is_complete  : null,
            "status" => is_object($this->getProfilePercentage($facilityId)) ? $this->getProfilePercentage($facilityId)->status  : null,
            "image_settings" => is_object($this->getProfileImage($facilityId, $tbl)) ? $this->getProfileImage($facilityId, $tbl)->image_settings  : null,
            "profile_image" => is_object($this->getProfileImage($facilityId, $tbl)) ? $this->getProfileImage($facilityId, $tbl)->profile_image  : null,
            "profile_image_url" => is_object($this->getProfileImage($facilityId, $tbl)) ? "eCA/profile/" . $this->getProfileImage($facilityId, $tbl)->profile_image  : null,
            "facility_name" => is_object($facility) ? $facility->facility_name : null,
            "facility_npi_number" => is_object($facility) ? $facility->facility_npi_number : null,
            "facility_contact_information" => $facilityContactInfo,
            "new_facility_address" => $facilityAddress,
            "facility_timings" => [
                "monday"        => ["start_time" => is_object($facilityTimings) ? $facilityTimings->monday_from : null, "end_time" => is_object($facilityTimings) ? $facilityTimings->monday_to : null, "is_closed" =>  is_object($facilityTimings) ? $facilityTimings->monday_is_closed : 0],
                "tuesday"       => ["start_time" => is_object($facilityTimings) ? $facilityTimings->tuesday_from : null, "end_time" => is_object($facilityTimings) ? $facilityTimings->tuesday_to : null, "is_closed" =>  is_object($facilityTimings) ? $facilityTimings->tuesday_is_closed : 0],
                "wednesday"     => ["start_time" => is_object($facilityTimings) ? $facilityTimings->wednesday_from : null, "end_time" => is_object($facilityTimings) ? $facilityTimings->wednesday_to : null, "is_closed" =>  is_object($facilityTimings) ? $facilityTimings->wednesday_is_closed : 0],
                "thursday"      => ["start_time" => is_object($facilityTimings) ? $facilityTimings->thursday_from : null, "end_time" => is_object($facilityTimings) ? $facilityTimings->thursday_to : null, "is_closed" =>  is_object($facilityTimings) ? $facilityTimings->thursday_is_closed : 0],
                "friday"        => ["start_time" => is_object($facilityTimings) ? $facilityTimings->friday_from : null, "end_time" => is_object($facilityTimings) ? $facilityTimings->friday_to : null, "is_closed" =>  is_object($facilityTimings) ? $facilityTimings->friday_is_closed : 0],
                "saturday"      => ["start_time" => is_object($facilityTimings) ? $facilityTimings->saturday_from : null, "end_time" => is_object($facilityTimings) ? $facilityTimings->saturday_to : null, "is_closed" =>  is_object($facilityTimings) ? $facilityTimings->saturday_is_closed : 0],
                "sunday"        => ["start_time" => is_object($facilityTimings) ? $facilityTimings->sunday_from : null, "end_time" => is_object($facilityTimings) ? $facilityTimings->sunday_to : null, "is_closed" =>  is_object($facilityTimings) ? $facilityTimings->sunday_is_closed : 0]
            ],
            "providers" => $providers,
            "files" => $files,
            'facility_users' => $facilityUsers
        ];
        return $this->successResponse($facilityForm, "success");
    }


    /**
     * fetch the facility's User
     *
     * @param  $facilityId
     * @return array
     */
    function facilityUsers($facilityId)
    {
        $users = DB::table('emp_location_map')->select('users.id as user_id', DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as user_name"))
            ->where('location_user_id', $facilityId)
            ->join("users", "users.id", "=", "emp_location_map.emp_id")
            ->get();
        return   $users;
    }


    /**
     * fetch the facility's providers
     *
     * @param  $facilityId
     * @return array
     */
    function fetchProviders($facilityId)
    {

        $providers = DB::table("individualprovider_location_map")

            ->select(
                "individualprovider_location_map.user_id as provider_id",
                "users.is_complete",
                "users.profile_complete_percentage",
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as provider_name"),
                "users.status"
            )

            ->join("users", "users.id", "=", "individualprovider_location_map.user_id")

            ->where("individualprovider_location_map.location_user_id", '=', $facilityId)

            ->get();
        if(count($providers) > 0) {
            foreach ($providers as $provider) {
                $provider->approval_percentage = $this->fetchProviderCredsInfo($facilityId,$provider->provider_id);
            }
        }
        return $providers;
    }
    /**
     * facility  contact information
     *
     * @param string $facilityId
     */
    private function facilityContactInformation($facilityId)
    {
        $key = $this->key;
        return DB::table("user_ddpracticelocationinfo")
            ->select(
                DB::raw("AES_DECRYPT(contact_phone,'$key') as phone_number"),
                DB::raw("AES_DECRYPT(contact_fax,'$key') as fax_number"),
                DB::raw("AES_DECRYPT(contact_email,'$key') as email"),
                "contact_name as name",
                "contact_title as title"
            )
            ->where("user_id", "=", $facilityId)
            ->first();
    }
    /**
     * facility  address
     *
     * @param string $facilityId
     */
    private function facilityAddress($facilityId)
    {
        $key = $this->key;
        return DB::table("user_ddpracticelocationinfo")
            ->select(
                "zip_five",
                "zip_four",
                DB::raw("AES_DECRYPT(practise_address,'$key') as street_address"),
                "country",
                "city",
                "state",
                "state_code",
                DB::raw("AES_DECRYPT(phone,'$key') as phone_number"),
                DB::raw("AES_DECRYPT(fax,'$key') as fax_number"),
                DB::raw("AES_DECRYPT(email,'$key') as email"),
                "county"
            )
            ->where("user_id", "=", $facilityId)
            ->first();
    }
    /**
     * facility  address
     *
     * @param string $facilityId
     */
    private function facilityTimings($facilityId)
    {
        return DB::table("user_ddpracticelocationinfo")
            ->select(
                "monday_from",
                "tuesday_from",
                "wednesday_from",
                "thursday_from",
                "friday_from",
                "saturday_from",
                "sunday_from",
                "monday_to",
                "tuesday_to",
                "wednesday_to",
                "thursday_to",
                "friday_to",
                "saturday_to",
                "sunday_to",
                "monday_is_closed",
                "tuesday_is_closed",
                "wednesday_is_closed",
                "thursday_is_closed",
                "friday_is_closed",
                "saturday_is_closed",
                "sunday_is_closed"
            )
            ->where("user_id", "=", $facilityId)
            ->first();
    }
    /**
     * get the practice form for pre filled
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchPracticeForm(Request $request)
    {

        $request->validate([
            "practice_id" => "required"
        ]);

        $practiceId = $request->practice_id;

        $practiceInfo       = $this->fetchPracticeBasicInfo($practiceId);

        $practiceAddress    = $this->fetchAddressContactInfo($practiceId);

        $practiceOwnerInfo  = $this->fetchOwnershipInformation($practiceId);

        $practiceTaxonomy   = $this->fetchTaxonomyInfo($practiceId);

        $bankInfo           = $this->fetchBankInfo($practiceId);

        $servicePlan        = $this->fetchServicePlan($practiceId);

        $tbl = "user_baf_practiseinfo";
        $practiceForm = [
            "practice_id" => $practiceId,
            "profile_complete_percentage" => is_object($this->getProfilePercentage($practiceId)) ? $this->getProfilePercentage($practiceId)->profile_complete_percentage  : null,
            "is_complete"               => is_object($this->getProfilePercentage($practiceId)) ? $this->getProfilePercentage($practiceId)->is_complete  : null,
            "status"                    => is_object($this->getProfilePercentage($practiceId)) ? $this->getProfilePercentage($practiceId)->status  : null,
            "image_settings"            => is_object($this->getProfileImage($practiceId, $tbl)) ? $this->getProfileImage($practiceId, $tbl)->image_settings  : null,
            "profile_image"             => is_object($this->getProfileImage($practiceId, $tbl)) ? $this->getProfileImage($practiceId, $tbl)->profile_image  : null,
            "profile_image_url"         => is_object($this->getProfileImage($practiceId, $tbl)) ? "eCA/profile/" . $this->getProfileImage($practiceId, $tbl)->profile_image  : null,
            "npi_number"                => is_object($practiceInfo["basic_info"]) ? $practiceInfo["basic_info"]->npi_number                 : null,
            "practice_name"             => is_object($practiceInfo["basic_info"]) ? $practiceInfo["basic_info"]->practice_name              : null,
            "doing_business_as"         => is_object($practiceInfo["basic_info"]) ? $practiceInfo["basic_info"]->doing_business_as          : null,
            "tax_id"                    => is_object($practiceInfo["basic_info"]) ? $practiceInfo["basic_info"]->tax_id                     : null,
            "business_established_date" => is_object($practiceInfo["basic_info"]) ? $practiceInfo["basic_info"]->business_established_date  : null,
            "contact_information"       => $practiceInfo["contact_info"],
            "mailing_address"           => $practiceAddress["mailing"],
            "primary_facility_address"  => $practiceAddress["primary"],
            "ownership_status"          => is_object($practiceOwnerInfo["other_info"]) ? $practiceOwnerInfo["other_info"]->ownership_status           : null,
            "ownership_classification_status"          => is_object($practiceOwnerInfo["other_info"]) ? $practiceOwnerInfo["other_info"]->ownership_classification_status           : null,
            "number_of_owners"          => is_object($practiceOwnerInfo["other_info"]) ? $practiceOwnerInfo["other_info"]->number_of_owners           : null,
            "total_ownership_percentage" => is_object($practiceOwnerInfo["other_info"]) ? $practiceOwnerInfo["other_info"]->total_ownership_percentage : null,
            "ownership_information"     => $practiceOwnerInfo["owners"],
            "banking_information"       => $bankInfo,
            "taxonomy"                  => $practiceTaxonomy,
            "service_plan"              => $servicePlan,
        ];

        return $this->successResponse($practiceForm, "success");
    }
    /**
     * fetch the practice service plan
     *
     * @param $practiceId
     */
    private function fetchServicePlan($practiceId)
    {
        $servicePlan = DB::table("practice_service_plan")
            ->where("practice_id", "=", $practiceId)
            ->first();
        if (is_object($servicePlan)) {
            $effectiveDate  = $servicePlan->effective_date;
            $effectiveDate  = Carbon::parse($effectiveDate);
            $tenure         =  $servicePlan->agreement_tenure;
            $servicePlan->expiration_date = null;
            if(isset($tenure) && isset($effectiveDate)) {
                if (Str::contains($tenure, '-')) {
                   
                    $tenureType     = explode('-', $tenure)[1];
                    $tenureInDigit  = explode('-', $tenure)[0];
                   
                    $tenureInMonths = $tenureType == 'Month' ? $tenureInDigit : $tenureInDigit * 12; // Convert years to months if needed
                    
                    $expirationDate = $effectiveDate->copy()->addMonths($tenureInMonths);
                    $servicePlan->expiration_date = $expirationDate->format("m/d/Y");
                   
                  }
            }
            $effectiveDate_ = Carbon::parse($servicePlan->effective_date);
            // Calculate the difference in days between the effective date and today
            $daysPassed = $effectiveDate_->diffInDays(Carbon::now());

            // Format the result into a human-readable format
            $humanReadableFormat = $this->formatDaysPassed($daysPassed);

            $servicePlan->plan_days_passed = $humanReadableFormat;
        }
        return $servicePlan;
    }
    /**
     * function for format days
     *
     * @param string $days
     * @return string
     */
    private function formatDaysPassed($days)
    {
        if ($days == 1) {
            return "1 day has passed";
        } elseif ($days > 1) {
            return "$days days have passed";
        } else {
            return "Today is the effective date";
        }
    }
    /**
     * fetch the practice basic information
     *
     * @param $practiceId
     */
    private function fetchPracticeBasicInfo($practiceId)
    {
        $key = $this->key;
        $practiceBasicInfo = DB::table("user_baf_practiseinfo")
            ->select(
                DB::raw("AES_DECRYPT(cm_user_dd_businessinformation.facility_npi,'$key') as npi_number"),
                "user_baf_practiseinfo.practice_name",
                "user_baf_practiseinfo.doing_business_as",
                DB::raw("AES_DECRYPT(cm_user_dd_businessinformation.facility_tax_id,'$key') as tax_id"),
                "user_dd_businessinformation.business_established_date as business_established_date",
                DB::raw("DATE_FORMAT(cm_user_dd_businessinformation.business_established_date,'%m/%d/%Y') as formated_business_established_date")
            )
            ->Leftjoin("user_baf_contactinfo", "user_baf_contactinfo.user_id", "=", "user_baf_practiseinfo.user_id")
            ->Leftjoin("user_dd_businessinformation", "user_dd_businessinformation.user_id", "=", "user_baf_contactinfo.user_id")
            ->where("user_baf_practiseinfo.user_id", "=", $practiceId)
            ->first();

        $practiceContactInfo = DB::table("user_baf_contactinfo")
            ->select(
                "user_baf_contactinfo.contact_person_name as name",
                "user_baf_contactinfo.contact_person_designation as title",
                "user_baf_contactinfo.contact_person_email as email",
                "user_baf_contactinfo.contact_person_phone as phone_number",
                "user_baf_contactinfo.contact_person_fax as fax_number"

            )
            ->where("user_baf_contactinfo.user_id", "=", $practiceId)
            ->first();

        // $this->printR($practiceContactInfo,true);
        return ["basic_info" => $practiceBasicInfo, "contact_info" => $practiceContactInfo];
    }
    /**
     * fetch the address and contact information
     *
     * @param $practiceId
     */
    private function fetchAddressContactInfo($practiceId)
    {

        $mailingAddress = DB::table("user_baf_contactinfo")
            ->select(
                "mailing_address_zip_five as zip_five",
                "mailing_address_zip_four as zip_four",
                "mailing_address_country as country",
                "mailing_address_state as state",
                "mailing_address_city as city",
                "mailing_address_county as county",
                "mailing_address_street_address as street_address",
                "mailing_address_phone_number as phone_number",
                "mailing_address_fax_number as fax_number",
                "mailing_address_state_code as state_code"
            )
            ->where("user_id", "=", $practiceId)
            ->first();

        $primaryAddress = DB::table("user_baf_contactinfo")
            ->select(
                "street_address",
                "zip_five",
                "zip_four",
                "country",
                "state",
                "city",
                "county",
                "fax",
                "phone",
                "state_code",
                "phone as phone_number",
                "fax as fax_number",
                "is_primary as is_Checked"
            )
            ->where("user_id", "=", $practiceId)
            ->first();
        return ["primary" => $primaryAddress, "mailing" => $mailingAddress];
    }
    /**
     * fetch the practice ownership information
     *
     * @param $practiceId
     */
    private function fetchOwnershipInformation($practiceId)
    {
        $key = $this->key;

        $ownerOtherInfo = DB::table("user_dd_businessinformation")
            ->select("number_of_owners", "total_ownership_percentage", "ownership_status", "ownership_classification_status")
            ->where("user_id", "=", $practiceId)
            ->first();

        $owners = DB::table("user_ddownerinfo")
            ->select(
                "user_ddownerinfo.name as name_of_owner",
                "owners_map.percentage as ownership_percentage",
                "user_ddownerinfo.num_of_owners",
                "user_ddownerinfo.pob as place_of_birth",
                DB::raw("DATE_FORMAT(AES_DECRYPT(cm_user_ddownerinfo.dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.dob,'$key') as date_of_birth"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"),
                "user_ddownerinfo.city",
                "user_ddownerinfo.state",
                "user_ddownerinfo.zip_five",
                "user_ddownerinfo.zip_four",
                "user_ddownerinfo.country",
                "user_ddownerinfo.county",
                DB::raw("cm_owners_map.date_of_ownership as date_of_ownership"),
                DB::raw("DATE_FORMAT(cm_owners_map.date_of_ownership,'%m/%d/%Y') as format_date_of_ownership"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.address,'$key') as street_address"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.phone,'$key') as phone_number"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.fax,'$key') as fax_number"),
                DB::raw("AES_DECRYPT(cm_user_ddownerinfo.email,'$key') as email"),
                "user_ddownerinfo.id"
            )
            ->leftJoin("owners_map", "owners_map.owner_id", "user_ddownerinfo.id")
            ->where("owners_map.practice_id", "=", $practiceId)
            ->get();

        return ["owners" => $owners, "other_info" => $ownerOtherInfo];
    }

    /**
     * fetch the bank information
     *
     * @param $practiceId
     */
    private function fetchBankInfo($practiceId)
    {
        $key = $this->key;
        return DB::table("user_ddbankinginfo")
            ->select(
                DB::raw("AES_DECRYPT(cm_user_ddbankinginfo.account_name,'$key') as bank_account_title"),
                "user_ddbankinginfo.bank_name as name_of_bank",
                DB::raw("AES_DECRYPT(cm_user_ddbankinginfo.routing_number,'$key') as financial_institution_routing_number"),
                DB::raw("AES_DECRYPT(cm_user_ddbankinginfo.account_number,'$key') as bank_account_number"),
                "user_ddbankinginfo.bank_phone as bank_contact_person_phone_number",
                "user_ddbankinginfo.bank_contact_person as bank_contact_person_name",
                DB::raw("AES_DECRYPT(cm_user_ddbankinginfo.email,'$key') as bank_contact_person_email")
            )
            ->where("user_ddbankinginfo.user_id", "=", $practiceId)
            ->first();
    }
    /**
     * fetch the practice taxonomy information
     *
     * @param $practiceId
     */
    private function fetchTaxonomyInfo($practiceId)
    {
        return DB::table("user_dd_businessinformation")
            ->select("taxonomy_code", "taxonomy_group", "taxonomy_desc", "taxonomy_state", "taxonomy_license", "taxonomy_primary")
            ->where("user_id", "=", $practiceId)
            ->first();
    }
    /**
     * add the BAF related data of the practice
     *
     * @param $practiceId
     * @param $practice
     */
    private function addPracticeBusinessRelatedData($practiceId, $practice)
    {

        $bafBusinessInfo["user_id"]                     = $practiceId;
        $bafBusinessInfo["business_type"]               = NULL;
        $bafBusinessInfo["begining_date"]               =  $practice['business_established_date'];
        $bafBusinessInfo["number_of_physical_location"] = NULL;
        $bafBusinessInfo["seeking_service"]             = NULL;

        $wherePractice = [
            ["user_id", "=", $practiceId],
        ];
        $hasRec = $this->fetchData("user_baf_businessinfo", $wherePractice, 1, []);
        //update practice business info
        if (is_object($hasRec)) {
            $this->updateData("user_baf_businessinfo", $wherePractice, $bafBusinessInfo);
        } else {
            $this->addData("user_baf_businessinfo",  $bafBusinessInfo, 0);
        }

        //adding business info of practice

        $bafContactInfo["user_id"]          = $practiceId;
        $bafContactInfo["address"]          = $practice['primary_facility_address']['street_address'];
        $bafContactInfo["address_line_one"] = NULL;
        $bafContactInfo["city"]             = $practice['primary_facility_address']['city'];
        $bafContactInfo["state"]            = $practice['primary_facility_address']['state'];
        $bafContactInfo["zip_code"]         = $practice['primary_facility_address']['zip_five'];

        $bafContactInfo["contact_person_name"]          = $practice['contact_information']['name'];
        $bafContactInfo["has_physical_location"]        = NULL;
        $bafContactInfo["contact_person_email"]         = $practice['contact_information']['email'];
        $bafContactInfo["contact_person_phone"]         = $practice['contact_information']['phone_number'];
        $bafContactInfo["contact_person_designation"]   = $practice['contact_information']['title'];

        $bafContactInfo['zip_five']         = $practice['primary_facility_address']['zip_five'];
        $bafContactInfo['zip_four']         = $practice['primary_facility_address']['zip_four'];
        $bafContactInfo['street_address']   = $practice['primary_facility_address']['street_address'];
        $bafContactInfo['country']          = $practice['primary_facility_address']['country'];
        $bafContactInfo['state_code']       = $practice['primary_facility_address']['state_code'];
        $bafContactInfo['county']           = $practice['primary_facility_address']['county'];


        //adding mailing address info to contact info
        $bafContactInfo['mailing_address_zip_five']         = $practice['mailing_address']['zip_five'];
        $bafContactInfo['mailing_address_zip_four']         = $practice['mailing_address']['zip_four'];
        $bafContactInfo['mailing_address_street_address']   = $practice['mailing_address']['street_address'];
        $bafContactInfo['mailing_address_country']          = $practice['mailing_address']['country'];
        $bafContactInfo['mailing_address_city']             = $practice['mailing_address']['city'];
        $bafContactInfo['mailing_address_state']            = $practice['mailing_address']['state'];
        $bafContactInfo['mailing_address_state_code']       = $practice['mailing_address']['state_code'];
        $bafContactInfo['mailing_address_county']           = $practice['mailing_address']['county'];
        $bafContactInfo['mailing_address_phone_number']     = $practice['mailing_address']['phone_number'];
        $bafContactInfo['mailing_address_fax_number']       = $practice['mailing_address']['fax_number'];



        $wherePracticeCI = [
            ["user_id", "=", $practiceId],
        ];
        $hasRec = $this->fetchData("user_baf_contactinfo", $wherePracticeCI, 1, []);
        //update practice business info
        if (is_object($hasRec)) {
            $this->updateData("user_baf_contactinfo", $wherePracticeCI, $bafContactInfo);
        } else {
            $this->addData("user_baf_contactinfo",  $bafContactInfo, 0);
        }




        //adding practice info
        $bafPracticeInfo["user_id"]                        = $practiceId;
        $bafPracticeInfo['provider_type']                  = "group";
        $bafPracticeInfo['provider_name']                  = $practice['practice_name'];
        $bafPracticeInfo['legal_business_name']            = $practice['practice_name'];
        $bafPracticeInfo['doing_business_as']              = $practice['doing_business_as'];
        $bafPracticeInfo['number_of_individual_provider']  = 0;



        $wherePracticeI = [
            ["user_id", "=", $practiceId],
        ];
        $hasRec = $this->fetchData("user_baf_practiseinfo", $wherePracticeI, 1, []);
        //update practice business info
        if (is_object($hasRec)) {
            $this->updateData("user_baf_practiseinfo", $wherePracticeI, $bafPracticeInfo);
        } else {
            $this->addData("user_baf_practiseinfo",  $bafPracticeInfo, 0);
        }
    }
    /**
     * add practice bank information
     *
     * @param  $practiceId
     * @param  $practice
     */
    private function addPracticeBankInfo($practiceId, $practice)
    {
        $key = $this->key;
        //add Bank Info
        $bankInfo['user_id']        = $practiceId;
        $bankInfo['bank_name']      = $practice['banking_information']['name_of_bank'];
        $bankInfo['account_name']   = DB::raw("AES_ENCRYPT('" .    $practice['banking_information']['bank_account_title']     . "', '$key')");
        $bankInfo['routing_number'] = DB::raw("AES_ENCRYPT('" .    $practice['banking_information']['financial_institution_routing_number']     . "', '$key')");
        $bankInfo['account_number'] = DB::raw("AES_ENCRYPT('" .   $practice['banking_information']['bank_account_number']     . "', '$key')");

        $bankInfo['bank_phone']          = $practice['banking_information']['bank_contact_person_phone_number'];
        $bankInfo['bank_contact_person'] = $practice['banking_information']['bank_contact_person_name'];

        $whereBank = [
            ["user_id", "=", $practiceId],

        ];
        $hasRec = $this->fetchData("user_ddbankinginfo", $whereBank, 1, []);
        //update practice business info
        if (is_object($hasRec)) {
            $this->updateData("user_ddbankinginfo", $whereBank, $bankInfo);
        } else {
            $this->addData("user_ddbankinginfo",  $bankInfo, 0);
        }
    }
    /**
     * add the practice business information
     *
     * @param $practiceId
     * @param $practice
     */
    private function addPracticeBusinessInfo($practiceId, $practice)
    {
        //add Practice Business Info
        $key = $this->key;
        $practiceBusinessInfo['user_id']                       = $practiceId;
        $practiceBusinessInfo['facility_npi']                  = DB::raw("AES_ENCRYPT('" .    $practice['npi_number']     . "', '$key')");
        $practiceBusinessInfo['legal_business_name']           = DB::raw("AES_ENCRYPT('" .   $practice['doing_business_as']     . "', '$key')");
        $practiceBusinessInfo['primary_correspondence_address'] = NULL;
        $practiceBusinessInfo['phone']                         = DB::raw("AES_ENCRYPT('" .   $practice['primary_facility_address']['phone_number']     . "', '$key')");
        $practiceBusinessInfo['fax']                           = DB::raw("AES_ENCRYPT('" .   $practice['primary_facility_address']['fax_number']     . "', '$key')");
        $practiceBusinessInfo['federal_tax_classification']    = $practice['ownership_status'];
        $practiceBusinessInfo['email']                         = DB::raw("AES_ENCRYPT('" .   $practice['contact_information']['email']     . "', '$key')");
        $practiceBusinessInfo['group_specialty']               = NULL;
        $practiceBusinessInfo['facility_tax_id']               = DB::raw("AES_ENCRYPT('" .   $practice['tax_id']     . "', '$key')");
        $practiceBusinessInfo['business_established_date']     = $practice['business_established_date'];
        $practiceBusinessInfo['ownership_status']              = $practice['ownership_status'];

        $whereBI = [
            ["user_id", "=", $practiceId],
        ];

        $hasRec = $this->fetchData("user_dd_businessinformation", $whereBI, 1, []);
        //update practice business info
        if (is_object($hasRec)) {
            $this->updateData("user_dd_businessinformation", $whereBI, $practiceBusinessInfo);
        } else {
            $this->addData("user_dd_businessinformation",  $practiceBusinessInfo, 0);
        }
    }
    /**
     * add the practice owner information
     *
     * @param $practiceId
     * @param $practice
     */
    private function addPracticeOwner($practiceId, $practice)
    {
        //add Owners of this practice
        $key = $this->key;
        if (count($practice['ownership_information']) > 0) {
            foreach ($practice['ownership_information'] as $ownershipInfoData) {

                if (isset($ownershipInfoData['name_of_owner'])) {
                    $owner['first_name'] = $ownershipInfoData['name_of_owner'];
                    $owner['last_name'] = NULL;
                    $owner['email'] = isset($ownershipInfoData['address']['email']) ? DB::raw("AES_ENCRYPT('" .    $ownershipInfoData['address']['email']     . "', '$key')") : NULL;
                    $owner['password'] = NULL;
                    $email = $ownershipInfoData['address']['email'];

                    $owner = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")->first(["id"]);
                    if (!is_object($owner)) {
                        $user = User::create($owner); //create the user profile

                        $user->createToken($ownershipInfoData['name_of_owner'] . " Token")->plainTextToken; //create the user token

                        $ownerId = $user->id;

                        $compMap = [
                            'user_id' => $ownerId,
                            'company_id' => 1
                        ];

                        $this->addData("user_company_map", $compMap);

                        $this->addData("user_role_map",  ["user_id" => $ownerId, "role_id" => 0, "role_preference" => 1], 0); //assign the role the new user
                    } else
                        $ownerId = $owner->id;



                    $insData = [];

                    $insData["country_of_birth"]            = $ownershipInfoData['place_of_birth'] == "null" ? NULL : $ownershipInfoData['place_of_birth'];
                    $insData["ssn"]                         = $ownershipInfoData['ssn_number'] == "null" ? NULL : DB::raw("AES_ENCRYPT('" .    $ownershipInfoData['ssn_number']     . "', '$key')");
                    $insData["dob"]                         = $ownershipInfoData['date_of_birth'] == "null" ? NULL : DB::raw("AES_ENCRYPT('" . $ownershipInfoData['date_of_birth'] . "','$key')");
                    $insData['city']                        = $ownershipInfoData['address']['city'] == "null" ? NULL : $ownershipInfoData['address']['city'];
                    $insData['state']                       = $ownershipInfoData['address']['state'] == "null" ? NULL : $ownershipInfoData['address']['state'];
                    $insData['provider_zip_five']           = $ownershipInfoData['address']['zip_five'] == "null" ? NULL : $ownershipInfoData['address']['zip_five'];
                    $insData['provider_zip_five']           = $ownershipInfoData['address']['zip_five'] == "null" ? NULL : $ownershipInfoData['address']['zip_five'];

                    $whereOwner = [
                        ["id", "=", $ownerId],
                    ];
                    $hasRec = $this->fetchData("users", $whereOwner, 1, []);
                    //update invidual profile data
                    if (is_object($hasRec)) {
                        $this->updateData("users", $whereOwner, $insData);
                    }

                    //at last add owner to ddownerinfo
                    $ownerInfo['user_id']               = $ownerId;
                    $ownerInfo['parent_user_id']        = $practiceId;
                    $ownerInfo['ownership_percentage']  = $ownershipInfoData['ownership_percentage'];
                    $ownerInfo["num_of_owners"]         = $practice['number_of_owners'];

                    $ownerInfo["title"]             = NULL;
                    $ownerInfo["zip_five"]          = $ownershipInfoData['address']['zip_five'];
                    $ownerInfo["zip_four"]          = $ownershipInfoData['address']['zip_four'];
                    $ownerInfo["street_address"]    = $ownershipInfoData['address']['street_address'];
                    $ownerInfo["country"]           = $ownershipInfoData['address']['country'];
                    $ownerInfo["state_code"]        = $ownershipInfoData['address']['state_code'];
                    $ownerInfo["county"]            = $ownershipInfoData['address']['county'];

                    $whereOI = [
                        ["user_id", "=", $ownerId],
                        ["parent_user_id", "=", $practiceId],
                    ];

                    $hasRec = $this->fetchData("user_ddownerinfo", $whereOI, 1, []);
                    //update invidual profile data
                    if (is_object($hasRec)) {
                        $this->updateData("user_ddownerinfo", $whereOI, $ownerInfo);
                    } else {
                        $this->addData("user_ddownerinfo",  $ownerInfo, 0);
                    }
                }
            }
        }
    }
    /**
     * create the  practice first location
     *
     * @param $practiceId
     * @param $practice
     */
    private function addPrimaryPracticeFacility($practiceId, $practice)
    {
        $key = $this->key;
        $userddPracticeInformation['user_id']           = $practiceId;
        $userddPracticeInformation['user_parent_id']    = $practiceId;
        $userddPracticeInformation['tax_id']            = DB::raw("AES_ENCRYPT('" .    $practice['tax_id']     . "', '$key')");
        $userddPracticeInformation['fax']               = DB::raw("AES_ENCRYPT('" .    $practice['primary_facility_address']['fax_number']     . "', '$key')");
        $userddPracticeInformation['doing_buisness_as'] = DB::raw("AES_ENCRYPT('" .    $practice['doing_business_as']     . "', '$key')");
        $userddPracticeInformation['email']             = DB::raw("AES_ENCRYPT('" .    $practice['contact_information']['email']     . "', '$key')");
        $userddPracticeInformation['phone']             = DB::raw("AES_ENCRYPT('" .    $practice['primary_facility_address']['phone_number']     . "', '$key')");
        $userddPracticeInformation['npi']               = DB::raw("AES_ENCRYPT('" .   $practice['npi_number']     . "', '$key')");
        $userddPracticeInformation['practise_address']  = NULL;
        $userddPracticeInformation['practise_address1'] = NULL;
        $userddPracticeInformation['practise_phone']    = DB::raw("AES_ENCRYPT('" .   $practice['primary_facility_address']['phone_number']     . "', '$key')");
        $userddPracticeInformation['practise_fax']      = DB::raw("AES_ENCRYPT('" .   $practice['primary_facility_address']['fax_number']     . "', '$key')");
        $userddPracticeInformation['practise_email']    = DB::raw("AES_ENCRYPT('" .   $practice['contact_information']['email']     . "', '$key')");
        $userddPracticeInformation['practice_name']     = DB::raw("AES_ENCRYPT('" .   $practice['practice_name']     . "', '$key')");
        $userddPracticeInformation['city']              = $practice['primary_facility_address']['city'];
        $userddPracticeInformation['state']             = $practice['primary_facility_address']['state'];

        $wherePL = [
            ["user_id", "=", $practiceId],
            ["user_parent_id", "=", $practiceId],
        ];
        $roleData = [
            "user_id" => $practiceId,
            "role_id" => 3
        ];
        $wherePLRole = [
            ["user_id", "=", $practiceId],
            ["role_id", "=", 3],
        ];
        $hasRec_ = $this->fetchData("user_role_map", $wherePLRole, 1, []);
        if (!is_object($hasRec_))
            $this->addData("user_role_map", $roleData, 0);

        $hasRec = $this->fetchData("user_ddpracticelocationinfo", $wherePL, 1, []);
        //update practice business info
        if (is_object($hasRec)) {
            $this->updateData("user_ddpracticelocationinfo", $wherePL, $userddPracticeInformation);
        } else {
            $this->addData("user_ddpracticelocationinfo",  $userddPracticeInformation, 0);
        }
    }
    /**
     *add the new facility
     *
     * @param $facility
     */
    private function addFacilityAgainstPractice($facility, $practiceId)
    {
        //$this->printR($facility,true);
        $key = env("AES_KEY");
        $isValidFacility        = true;
        $isValidFacilityEmail   = true;
        $isValidFacilityNPI     = true;

        $facilityName   = $facility['facility_name'];
        $facilityEmail  = $facility['new_facility_address']['email'];
        $facilityNpi    = $facility['facility_npi_number'];

        $errors = [];
        if (isset($facilityName) && strlen($facilityName) > 0) {
            $isValidFacility = $this->isFacilityNameUnique($facilityName);
            $error =  "The facility name $facilityName is already taken. Please choose another facility name";
            if ($isValidFacility == false)
                array_push($errors, $error);
        }
        if (isset($facilityEmail) && strlen($facilityEmail) > 0) {
            $isValidFacilityEmail = $this->isFacilityEmailUnique($facilityEmail);
            $error =  "The facility email $facilityName is already taken. Please choose another facility email";
            if ($isValidFacilityEmail == false)
                array_push($errors, $error);
        }
        if (isset($facilityNpi) && strlen($facilityNpi) > 0) {
            $isValidFacilityNPI = $this->isFacilityNPIUnique($facilityNpi);
            $error =  "The facility npi $facilityNpi is already taken. Please choose another facility npi";
            if ($isValidFacilityNPI == false)
                array_push($errors, $error);
        }

        if (count($errors) > 0) {
            // return $this->warningResponse($errors, "Validation error", 409);
            // exit;
            return ["is_error" => true, "id" => 0, "errors" => $errors];
        } else {

            $addFacility = [
                'first_name' =>  NULL,
                "last_name" =>   NULL,
                "email"     =>  isset($facilityEmail) ? DB::raw("AES_ENCRYPT('" .    $facilityEmail     . "', '$key')") : NULL,
                "password"  => NULL,
                'is_complete' => $facility['is_complete'],
                'profile_complete_percentage' => $facility['profile_complete_percentage'],
                'created_at' => $this->timeStamp()
            ];

            $user = User::create($addFacility); //create the facility profile

            $user->createToken($facilityName . " Token")->plainTextToken; //create the facility token

            $facilityId = $user->id;

            $compMap = [
                'user_id' => $facilityId,
                'company_id' => 1
            ];
            $this->addData("user_company_map", $compMap);

            $this->addData("user_role_map",  ["user_id" => $facilityId, "role_id" => 3, "role_preference" => 1], 0); //assign the role the new facility


            $practiceLInformation['user_id']                = $facilityId;
            $practiceLInformation['user_parent_id']         = $practiceId;
            //$practiceLInformation['doing_buisness_as']      = $facility['facility_name'];
            $practiceLInformation['zip_five']               = $facility['new_facility_address']['zip_five'];
            $practiceLInformation['zip_four']               = $facility['new_facility_address']['zip_four'];
            if (isset($facilityNpi))
                $practiceLInformation['npi']                    = DB::raw("AES_ENCRYPT('" .    $facilityNpi     . "', '$key')");
            if (isset($facility['new_facility_address']['street_address']))
                $practiceLInformation['practise_address']       = DB::raw("AES_ENCRYPT('" .    $facility['new_facility_address']['street_address']     . "', '$key')");
            if (isset($facilityName))
                $practiceLInformation['practice_name']          = DB::raw("AES_ENCRYPT('" .    $facilityName     . "', '$key')");
            if (isset($facility['new_facility_address']['phone_number'])) {
                $practiceLInformation['phone']                  = DB::raw("AES_ENCRYPT('" .    $facility['new_facility_address']['phone_number']     . "', '$key')");
                $practiceLInformation['practise_phone']         = DB::raw("AES_ENCRYPT('" .    $facility['new_facility_address']['phone_number']     . "', '$key')");
            }
            if (isset($facility['new_facility_address']['fax_number'])) {
                $practiceLInformation['practise_fax']           = DB::raw("AES_ENCRYPT('" .   $facility['new_facility_address']['fax_number']     . "', '$key')");
                $practiceLInformation['fax']                    = DB::raw("AES_ENCRYPT('" .   $facility['new_facility_address']['fax_number']     . "', '$key')");
            }
            if (isset($facility['new_facility_address']['email']))
                $practiceLInformation['practise_email']         = DB::raw("AES_ENCRYPT('" .   $facility['new_facility_address']['email']     . "', '$key')");

            $practiceLInformation['city']                   = $facility['new_facility_address']['city'];
            $practiceLInformation['state']                  = $facility['new_facility_address']['state'];

            $practiceLInformation['country']                = $facility['new_facility_address']['country'];
            $practiceLInformation['state_code']             = $facility['new_facility_address']['state_code'];
            $practiceLInformation['county']                 = $facility['new_facility_address']['county'];

            $practiceLInformation['contact_name']   = $facility['facility_contact_information']['name'];
            $practiceLInformation['contact_title']  = $facility['facility_contact_information']['title'];
            if (isset($facility['facility_contact_information']['phone_number']))
                $practiceLInformation['contact_phone']  = DB::raw("AES_ENCRYPT('" .   $facility['facility_contact_information']['phone_number']     . "', '$key')");
            if (isset($facility['facility_contact_information']['fax_number']))
                $practiceLInformation['contact_fax']    = DB::raw("AES_ENCRYPT('" .   $facility['facility_contact_information']['fax_number']     . "', '$key')");
            if (isset($facility['facility_contact_information']['email']))
                $practiceLInformation['contact_email']  = DB::raw("AES_ENCRYPT('" .   $facility['facility_contact_information']['email']     . "', '$key')");

            //$this->printR($practiceLInformation, true);

            $whereFacility = [
                ["user_id", "=", $practiceId],
                ["user_parent_id", "=", $facilityId],
            ];
            $hasRec = $this->fetchData("user_ddpracticelocationinfo", $whereFacility, 1, []);
            //update practice location info
            if (is_object($hasRec)) {
                $this->updateData("user_ddpracticelocationinfo", $whereFacility, $practiceLInformation);
            } else {
                $this->addData("user_ddpracticelocationinfo",  $practiceLInformation, 0);
            }

            return  ["is_error" => false, "id" => $facilityId, "errors" => $errors];
        }
    }
    /**
     *add the new provider
     *
     * @param $provider
     */
    private function addProviderAgainstFacility($provider, $facilityId)
    {
        $key = $this->key;

        //$this->printR($provider,true);
        $isValidProvider        = true;
        $isValidProviderEmail   = true;
        $isValidProviderNPI     = true;

        $providerName   = isset($provider['basic_information']['provider_first_name']) ? $provider['basic_information']['provider_first_name'] : NULL;
        $providerEmail  = isset($provider['basic_information']['email']) ? $provider['basic_information']['email'] : NULL;
        $providerNpi    = isset($provider['basic_information']['provider_npi_number']) ? $provider['basic_information']['provider_npi_number'] : NULL;

        $errors = [];
        if (isset($providerName) && strlen($providerName) > 0) {
            $isValidProvider = $this->isProviderNameUnique($providerName);
            $error =  "The provider name  $providerName is already taken. Please choose another provider name";
            if ($isValidProvider == false)
                array_push($errors, $error);
        }
        if (isset($providerEmail) && strlen($providerEmail) > 0) {
            $isValidProviderEmail = $this->isProviderEmailUnique($providerEmail);
            $error =  "The provider email  $providerEmail is already taken. Please choose another provider email";
            if ($isValidProviderEmail == false)
                array_push($errors, $error);
        }
        if (isset($providerNpi) && strlen($providerNpi) > 0) {
            $isValidProviderNPI = $this->isProviderNPIUnique($providerNpi);
            $error =  "The provider npi  $providerEmail is already taken. Please choose another provider npi";
            if ($isValidProviderNPI == false)
                array_push($errors, $error);
        }

        if (count($errors) > 0) {
            //return $this->warningResponse($errors, "Validation error", 409);
            return  ["is_error" => true, "id" => 0, "errors" => $errors];
        } else {

            $insData = [];
            if (isset($provider['basic_information']['provider_npi_number']))
                $insData["facility_npi"]                = DB::raw("AES_ENCRYPT('" .   $provider['basic_information']['provider_npi_number']     . "', '$key')");

            $insData["primary_speciality"]          = isset($provider['spcialities']['primary_specialty']) ? $provider['spcialities']['primary_specialty'] : NULL;
            $insData["secondary_speciality"]        = isset($provider['spcialities']['secondary_specialty']) ? $provider['spcialities']['secondary_specialty'] : NULL;
            if (isset($provider['contact_information']['phone_number_home']))
                $insData["phone"]                       = DB::raw("AES_ENCRYPT('" .   $provider['contact_information']['phone_number_home']     . "', '$key')");

            $insData["country_of_birth"]            = isset($provider['basic_information']['place_of_birth']) ? $provider['basic_information']['place_of_birth'] : NULL;
            $insData["citizenship_id"]              = isset($provider['basic_information']['citizenship']) ? $provider['basic_information']['citizenship'] : NULL;

            $insData["supervisor_physician"]        = isset($provider['basic_information']['supervisor_physician']) ? $provider['basic_information']['supervisor_physician'] : NULL;
            $insData["gender"]                      = isset($provider['basic_information']['gender']) ? $provider['basic_information']['gender'] : NULL;
            if (isset($provider['basic_information']['ssn_number']))
                $insData["ssn"]                         = DB::raw("AES_ENCRYPT('" .   $provider['basic_information']['ssn_number']     . "', '$key')");
            if (isset($provider['basic_information']['date_of_birth']))
                $insData["dob"]                         = DB::raw("AES_ENCRYPT('" .   $provider['basic_information']['date_of_birth']     . "', '$key')");

            if ($provider['spcialities']['professional_group'] != null)
                $insData["professional_group_id"]       = $provider['spcialities']['professional_group'];
            if ($provider['spcialities']['professional_type'] != null)
                $insData["professional_type_id"]        = $provider['spcialities']['professional_type'];


            $insData['is_complete']                    = $provider['is_complete'];
            $insData['profile_complete_percentage']    = $provider['profile_complete_percentage'];

            $insData["provider_zip_five"]                         = isset($provider['provider_address']['zip_five']) ? $provider['provider_address']['zip_five'] : NULL;
            $insData["provider_zip_four"]                         = isset($provider['provider_address']['zip_four']) ? $provider['provider_address']['zip_four'] : NULL;
            $insData["provider_country"]                          = isset($provider['provider_address']['country']) ? $provider['provider_address']['country'] : NULL;
            $insData["provider_city"]                             = isset($provider['provider_address']['city']) ? $provider['provider_address']['city'] : NULL;
            $insData["provider_state"]                            = isset($provider['provider_address']['state']) ? $provider['provider_address']['state'] : NULL;
            $insData["provider_state_code"]                       = isset($provider['provider_address']['state_code']) ? $provider['provider_address']['state_code'] : NULL;
            $insData["provider_county"]                           = isset($provider['provider_address']['county']) ? $provider['provider_address']['county'] : NULL;
            $insData["address_line_one"]                          = isset($provider['provider_address']['street_address']) ? DB::raw("AES_ENCRYPT('" .   $provider['provider_address']['street_address']     . "', '$key')") : NULL;

            /*$addProvider = [
                'first_name' =>  isset($provider['basic_information']['provider_first_name']) ? $provider['basic_information']['provider_first_name'] : NULL,
                "last_name" =>   isset($provider['basic_information']['provider_last_name']) ? $provider['basic_information']['provider_last_name'] : NULL,
                "email" =>      isset($provider['contact_information']['email']) ? DB::raw("AES_ENCRYPT('" .   $provider['contact_information']['email']     . "', '$key')") : NULL
            ];*/

            $insData["first_name"] = isset($provider['basic_information']['provider_first_name']) ? $provider['basic_information']['provider_first_name'] : NULL;
            $insData["last_name"]  = isset($provider['basic_information']['provider_last_name']) ? $provider['basic_information']['provider_last_name'] : NULL;
            $insData["email"]      = isset($provider['contact_information']['email']) ? DB::raw("AES_ENCRYPT('" .   $provider['contact_information']['email']     . "', '$key')") : NULL;
            $user = User::create($insData); //create the facility profile

            $user->createToken($providerName . " Token")->plainTextToken; //create the facility token

            $providerId = $user->id;

            /*$whereProvider = [
                ["id", "=", $providerId],
            ];
            $hasRec = $this->fetchData("users", $whereProvider, 1, []);
            //update invidual profile data
            if (is_object($hasRec)) {
                $this->updateData("users", $whereProvider, $insData);
            }*/




            $whereLinkedProvider = [
                ["user_id", "=", $providerId],
                ["parent_user_id", "=", $facilityId]
            ];

            $indvPRelation  = [];
            $indvPRelation["user_id"]                 = $providerId;
            $indvPRelation["parent_user_id"]          = $facilityId;


            $hasRec = $this->fetchData("user_dd_individualproviderinfo", $whereLinkedProvider, 1, []);

            if (is_object($hasRec)) {

                $this->updateData("user_dd_individualproviderinfo", $whereLinkedProvider, $indvPRelation);
            } else {

                $this->addData("user_dd_individualproviderinfo",  $indvPRelation, 0);
            }
            $compMap = [
                'user_id' => $providerId,
                'company_id' => 1
            ];
            $this->addData("user_company_map", $compMap);

            $this->addData("user_role_map",  ["user_id" => $providerId, "role_id" => 10, "role_preference" => 1], 0); //assign the role the new provier

            $this->addData("individualprovider_location_map", ["user_id" => $providerId, "location_user_id" => $facilityId]);


            $this->addProviderLicenseInfo($provider, $providerId); //add the license information

            $this->addProviderPortals($provider, $providerId); //add the portals information

            return  ["is_error" => false, "id" => $providerId, "errors" => $errors];
        }
    }
    /**
     * add provder license information
     *
     * @param  $provider
     * @param $providerId
     */
    private function addProviderLicenseInfo($provider, $providerId)
    {
        //add the driver license information
        if ($provider['driver_license'] != null) {

            $driverLicenseType = $provider['driver_license']['license_number'];
            $whereDriverLicense = [
                ["user_id", "=", $providerId],
                ["driver_license_no", "=", $driverLicenseType]
            ];

            $hasDiverLicense = $this->fetchData("user_licenses", $whereDriverLicense, 1, []);

            if (is_object($hasDiverLicense)) {
                $diverLicense = [];
                $diverLicense["user_id"]            = $providerId;
                $diverLicense["driver_license_no"]  = $provider['driver_license']['license_number'];
                $diverLicense["driver_issue_date"]  = $provider['driver_license']['issue_date'];
                $diverLicense["driver_exp_date"]    = $provider['driver_license']['expiration_date'];
                $diverLicense["driver_state"]       = $provider['driver_license']['state'];

                $this->updateData("user_licenses", $whereDriverLicense, $diverLicense);
            } else {
                $diverLicense = [];
                $diverLicense["user_id"]            = $providerId;
                $diverLicense["driver_license_no"]  = $provider['driver_license']['license_number'];
                $diverLicense["type_id"]            = 23;
                $diverLicense["is_for"]             = "Provider";
                $diverLicense["driver_issue_date"]  = $provider['driver_license']['issue_date'];
                $diverLicense["driver_exp_date"]    = $provider['driver_license']['expiration_date'];
                $diverLicense["driver_state"]       = $provider['driver_license']['state'];

                $this->addData("user_licenses", $diverLicense, 0);
            }
        }

        //state license
        if ($provider['state_license'] != null) {

            $stateLicenseType = $provider['state_license']['license_number'];
            $whereStateLicense = [
                ["user_id", "=", $providerId],
                ["state_license_no", "=", $stateLicenseType]
            ];

            $hasStateLicense = $this->fetchData("user_licenses", $whereStateLicense, 1, []);

            if (is_object($hasStateLicense)) {
                $stateLicense = [];
                $stateLicense["user_id"]            = $providerId;
                $stateLicense["state_license_no"]   = $provider['state_license']['license_number'];
                $stateLicense["state_issue_date"]   = $provider['state_license']['issue_date'];
                $stateLicense["state_exp_date"]     = $provider['state_license']['expiration_date'];
                $stateLicense["state_state"]        = $provider['state_license']['state'];

                $this->updateData("user_licenses", $whereStateLicense, $stateLicense);
            } else {

                $stateLicense = [];
                $stateLicense["user_id"]            = $providerId;
                $stateLicense["state_license_no"]   = $provider['state_license']['license_number'];
                $stateLicense["state_issue_date"]   = $provider['state_license']['issue_date'];
                $stateLicense["state_exp_date"]     = $provider['state_license']['expiration_date'];
                $stateLicense["state_state"]        = $provider['state_license']['state'];
                $stateLicense["type_id"]            = 33;
                $stateLicense["is_for"]             = "Provider";

                $this->addData("user_licenses", $stateLicense, 0);
            }
        }
        //DEA license information
        if ($provider['DEA_information']['DEA_number'] != null) {
            // exit("Here i am");
            $deaLicenseType = $provider['DEA_information']['DEA_number'];
            $whereDeaLicense = [
                ["user_id", "=", $providerId],
                ["dea_no", "=", $deaLicenseType]
            ];
            $hasDeaLicense = $this->fetchData("user_licenses", $whereDeaLicense, 1, []);
            if (is_object($hasDeaLicense)) {

                $deaLicense = [];
                $deaLicense["user_id"]          = $providerId;
                $deaLicense["dea_no"]           = $provider['DEA_information']['DEA_number'];
                $deaLicense["dea_issue_date"]   = $provider['DEA_information']['issue_date'];
                $deaLicense["dea_exp_date"]     = $provider['DEA_information']['expiration_date'];
                $deaLicense["dea_state"]        = $provider['DEA_information']['state'];

                $this->updateData("user_licenses", $whereDeaLicense, $deaLicense);
            } else {

                $deaLicense = [];
                $deaLicense["user_id"]          = $providerId;
                $deaLicense["dea_no"]           = $provider['DEA_information']['DEA_number'];
                $deaLicense["dea_issue_date"]   = $provider['DEA_information']['issue_date'];
                $deaLicense["dea_exp_date"]     = $provider['DEA_information']['expiration_date'];
                $deaLicense["dea_state"]        = $provider['DEA_information']['state'];
                $deaLicense["type_id"]          = 36;
                $deaLicense["is_for"]           = "Provider";

                $this->addData("user_licenses", $deaLicense, 0);
            }
        }
    }
    /**
     * add the provider portal information
     *
     * @param  $provider
     *  @param $providerId
     */
    private function addProviderPortals($provider, $providerId)
    {
        //portal types
        // $portalTypeName = $provider['NPPES_pecos_access']['username'];
        $hasPortalType = $this->fetchData("portal_types", ["name" => "CAQH"], 1, []);
        // $portalType = [];
        // $portalType['name'] = $portalTypeName;
        // //$portalType = $hasPortalType;

        // if (!is_object($hasPortalType) && $portalType['name'] != '') {
        //     $hasPortalType = $this->addData("portal_types", $portalType);
        // }



        //portals add CAQH or update
        $portalData = [];
        $portalData["user_id"]      = $providerId;
        $portalData["user_name"]    = $provider['CAQH_information']['username'];
        $portalData["password"]     = encrypt($provider['CAQH_information']['password']);
        $portalData["type_id"]      = isset($hasPortalType->id)   ? $hasPortalType->id : null;



        $hasPortal = $this->fetchData("portals", ["user_id" => $providerId], 1, []);

        if (is_object($hasPortal)) {

            $this->updateData("portals", ["user_id" => $providerId], $portalData);
        } else {

            $this->addData("portals", $portalData);
        }

        $hasPortalType = $this->fetchData("portal_types", ["name" => "NPPES"], 1, []);
        //portals add CAQH or update
        $portalData = [];
        $portalData["user_id"]      = $providerId;
        $portalData["user_name"]    = $provider['NPPES_pecos_access']['username'];
        $portalData["password"]     = encrypt($provider['NPPES_pecos_access']['password']);
        $portalData["type_id"]      = isset($hasPortalType->id)   ? $hasPortalType->id : null;
    }
    /**
     * add onboard data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addOnboardForm(Request $request)
    {
        $practice = $request->get('practice_form');
        $facility = $request->get("facility_form");
        $provider = $request->get('provider_form');
        // $this->printR($facility,true);
        $sessionUserId = $request->get('session_userid');
        $facilities = $facility["facilities"];
        // $this->printR($facilities,true);
        foreach ($facilities as $facility) {
            $this->printR($provider, true);
        }
        $practiceRes = $this->addNewPractice($practice);

        if (!$practiceRes['is_error']) {
            $practiceId = $practiceRes['id'];
            $wherePracticeLinked = [
                ["emp_id", "=", $sessionUserId],
                ["location_user_id", "=", $practiceId],
            ];
            $practiceLinked = $this->fetchData("emp_location_map", $wherePracticeLinked, 1, []);
            if (!is_object($practiceLinked)) {
                $this->addData("emp_location_map", ["emp_id" => $sessionUserId, "location_user_id" => $practiceId]);
            }
            $facilities = $facility["facilities"];
            $this->printR($facilities, true);
            $facilityRes = $this->addFacilityAgainstPractice($facility, $practiceId);

            if (!$facilityRes['is_error']) {
                $facilityId = $facilityRes['id'];
                $whereFacilityLinked = [
                    ["emp_id", "=", $sessionUserId],
                    ["location_user_id", "=", $facilityId],
                ];

                $facilityLinked = $this->fetchData("emp_location_map", $whereFacilityLinked, 1, []);
                if (!is_object($facilityLinked)) {
                    $this->addData("emp_location_map", ["emp_id" => $sessionUserId, "location_user_id" => $facilityId]);
                }

                $providerRes = $this->addProviderAgainstFacility($provider, $facilityId);
                $providerId = $providerRes['id'];

                return $this->successResponse(["is_added" => true, "practice_id" => $practiceId, "facility_id" => $facilityId, "provider_id" => $providerId], "success");
            } else {
                return $this->warningResponse($facilityRes["errors"], "Invalid Data", 422);
            }
        } else {
            return $this->warningResponse($practiceRes["errors"], "Invalid Data", 422);
        }
    }


    /**
     * Check the unique attribute of practice , facility and provider information
     *
     * @author Faheem Ahmed
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function chkUniqueAttributes(Request $request)
    {
        $key = $this->key;
        $validator = Validator::make($request->all(), [
            'npi_number' => 'required_without_all:practice_name,email',
            'practice_name' => 'required_without_all:npi_number,email',
            'email' => 'required_without_all:practice_name,npi_number',
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors();
            // Handle validation failure
            return $this->warningResponse($errors, "Validation errors", 422);
        } else {
            $validationErrors = [];
            if ($request->has("npi_number")) {
                $npiNumber = $request->npi_number;
                if (isset($npiNumber)) {
                    $practiceNPI = $npiNumber;
                    $isValid = DB::table("user_dd_businessinformation")->whereRaw("AES_DECRYPT(facility_npi, '$key') = '$practiceNPI'")
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["npi_number"] = "NPI Number already exists";
                }
            }
            if ($request->has("practice_name")) {
                $practiceName = $request->practice_name;
                if (isset($practiceName)) {
                    $isValid = DB::table("user_baf_practiseinfo")->whereRaw("legal_business_name = '$practiceName'")
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["practice_name"] = "Practice Name already exists";
                }
            }
            if ($request->has("email")) {
                $email = $request->email;
                if (isset($email)) {
                    $isValid = DB::table("users")->whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["email"] = "Email already exists";
                }
            }

            return $this->successResponse(["validation" => $validationErrors], "success");
        }
    }
    /**
     * validate the Faciltiy
     *
     * @author Faheem Ahmed
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function validateFacility(Request $request)
    {
        $key = $this->key;
        $validator = Validator::make($request->all(), [
            'npi_number' => 'required_without_all:practice_name,email',
            'practice_name' => 'required_without_all:npi_number,email',
            'email' => 'required_without_all:practice_name,npi_number',
        ]);
        $validationErrors = [];
        if ($validator->fails()) {

            $errors = $validator->errors();
            // Handle validation failure
            return $this->warningResponse($errors, "Validation errors", 422);
        } else {
            if ($request->has("facility_npi_number")) {

                $facilityNPI    = $request->facility_npi_number;
                if (isset($facilityNPI)) {
                    $isValid = DB::table("user_ddpracticelocationinfo")->whereRaw("AES_DECRYPT(npi, '$key') = '$facilityNPI'")
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["facility_npi_number"] = "NPI Number already exists";
                }
            }
            if ($request->has("facility_name")) {

                $facilityName   = $request->facility_name;
                if (isset($facilityName)) {
                    $isValid = DB::table("user_ddpracticelocationinfo")->whereRaw("AES_DECRYPT(practice_name, '$key') = '$facilityName'")
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["facility_name"] = "Facility name  already exists";
                }
            }
            if ($request->has("email")) {

                $email          = $request->email;
                if (isset($email)) {
                    $isValid = DB::table("users")->whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                        ->count();

                    if ($isValid > 0)
                        $validationErrors["email"] = "Email already exists";
                }
            }
        }
        return $this->successResponse(["validation" => $validationErrors], "success");
    }
    /**
     * validate the provider
     *
     * @author Faheem Ahmed
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function validateProvider(Request $request)
    {
        $key = $this->key;
        $validator = Validator::make($request->all(), [
            'npi_number' => 'required_without_all:provider_name,email',
            'provider_name' => 'required_without_all:npi_number,email',
            'email' => 'required_without_all:provider_name,npi_number',
        ]);
        $validationErrors = [];
        if ($validator->fails()) {

            $errors = $validator->errors();
            // Handle validation failure
            return $this->warningResponse($errors, "Validation errors", 422);
        } else {
            if ($request->has("npi_number")) {
                $npiNumber = $request->npi_number;
                if (isset($npiNumber)) {
                    $npi = $npiNumber;
                    $isValid = DB::table("users")->whereRaw("AES_DECRYPT(facility_npi, '$key') = '$npi'")
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["npi_number"] = "Email already exists";
                }
            }
            if ($request->has("provider_name")) {
                $providerName = $request->provider_name;
                if (isset($providerName)) {
                    $isValid = DB::table("users")->where("first_name", "=", $providerName)
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["provider_name"] = "First Name already exists";
                }
            }
            if ($request->has("email")) {
                $email = $request->email;
                if (isset($email)) {
                    $isValid = DB::table("users")->whereRaw("AES_DECRYPT(email, '$key') = '$email'")
                        ->count();
                    if ($isValid > 0)
                        $validationErrors["email"] = "Email already exists";
                }
            }
        }
        return $this->successResponse(["validation" => $validationErrors], "success");
    }
    /**
     * Fetch the directory data
     *
     * @author Faheem Ahmed
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function fetchDirectoryData(Request $request)
    {
        $tbl = $this->tblU;

        $tbl2 = $this->tbl;

        $tbl3 = "user_dd_businessinformation";

        $key = $this->key;

        $perPage = $this->cmperPage;
        $page = $request->has("page") ? $request->page : 1;
        $sessionUserId = $request->session_id;

        // dd($request->all());

        //exit("in else");
        $smartSearchSql = "";
        $filterSql = "";
        $roleIds = "3,9,4,10";
        $types = [];
        $whereType = "";
        $status = [];
        if ($request->has("smart_search") && $request->smart_search != "") {
            $searchKeyWord = $request->smart_search;
            $phoneNumber = $this->sanitizePhoneNumber($request->smart_search);
            if (is_numeric($phoneNumber))
                $searchKeyWord = $phoneNumber;
            else
                $searchKeyWord = $searchKeyWord;

            $smartSearchSql = "WHERE (T.zip LIKE '%" . $searchKeyWord . "%' OR T.modified_date_of_birth LIKE '%" . $searchKeyWord . "%' OR T.fax_id LIKE '%" . $searchKeyWord . "%' OR T.npi LIKE '%" . $searchKeyWord . "%' OR T.taxid LIKE '%" . $searchKeyWord . "%' OR T.name LIKE '%" . $searchKeyWord . "%' OR T.phone LIKE '%" . $searchKeyWord . "%' OR T.address LIKE '%" . $searchKeyWord . "%' OR T.practice_name LIKE '%" . $searchKeyWord . "%' OR T.doing_business_as LIKE '%" . $searchKeyWord . "%' OR T.email LIKE '%" . $searchKeyWord . "%')";
        }

        if ($request->has("type") && $request->type != "" && strlen($request->type)) {
            $types = json_decode($request->type, true);
            // $typesStr = implode(', ', $types);

            $whereType = " WHERE H.type IN(";
            foreach ($types as $type) {
                $whereType .= "'$type',";
            }
            $whereType = rtrim($whereType, ',');
            $whereType .= ")";
        }

        if ($request->has("status") && $request->status != "" && strlen($request->status)) {
            $status = json_decode($request->status, true);
        }
        
        if (in_array("onboard", $status) && in_array("offboard", $status) && in_array("complete", $status) && in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 0 OR T.deleted = 1 OR T.is_complete = 1 OR T.is_complete = 0)";
            } else {
                $filterSql = " WHERE (T.deleted = 0 OR T.deleted = 1 OR T.is_complete = 1 OR T.is_complete = 0)";
            }
        } else if (in_array("onboard", $status)  && in_array("complete", $status) && in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 0  OR T.is_complete = 1 OR T.is_complete = 0)";
            } else {
                $filterSql = " WHERE (T.deleted = 0  OR T.is_complete = 1 OR T.is_complete = 0)";
            }
        } else if (in_array("offboard", $status) && in_array("complete", $status) && in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 1 OR T.is_complete = 1 OR T.is_complete = 0)";
            } else {
                $filterSql = " WHERE (T.deleted = 1 OR T.is_complete = 1 OR T.is_complete = 0)";
            }
        } else if (in_array("offboard", $status)  && in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 1 OR T.is_complete = 0)";
            } else {
                $filterSql = " WHERE (T.deleted = 1 OR T.is_complete = 0)";
            }
        } else if (in_array("offboard", $status) && in_array("complete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 1 OR T.is_complete = 1 )";
            } else {
                $filterSql = " WHERE (T.deleted = 1 OR T.is_complete = 1)";
            }
        } else if (in_array("offboard", $status)  && in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 1 OR T.is_complete = 0)";
            } else {
                $filterSql = " WHERE (T.deleted = 1 OR T.is_complete = 0)";
            }
        } else if (in_array("onboard", $status) && in_array("complete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 0 OR T.is_complete = 1 )";
            } else {
                $filterSql = " WHERE (T.deleted = 0 OR T.is_complete = 1)";
            }
        } else if (in_array("onboard", $status) && in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.deleted = 0 OR T.is_complete = 0 )";
            } else {
                $filterSql = " WHERE (T.deleted = 0 OR T.is_complete = 0)";
            }
        } else if (in_array("complete", $status) && in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND (T.is_complete =  0 OR T.is_complete = 1)";
            } else {
                $filterSql = " WHERE (T.is_complete =  0 OR T.is_complete = 1)";
            }
        } else if (in_array("onboard", $status) && in_array("offboard", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND T.deleted IN(0,1)";
            } else {
                $filterSql = " WHERE T.deleted IN(0,1)";
            }
        } else if (in_array("complete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND T.is_complete = 1";
            } else {
                $filterSql = " WHERE T.is_complete = 1";
            }
        } else if (in_array("incomplete", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND T.is_complete = 0";
            } else {
                $filterSql = " WHERE T.is_complete = 0";
            }
        } else if (in_array("offboard", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND T.deleted = 1";
            } else {
                $filterSql = " WHERE T.deleted = 1";
            }
        } else if (in_array("onboard", $status)) {
            if (strlen($smartSearchSql) > 0) {
                $filterSql = " AND T.deleted = 0";
            } else {
                $filterSql = " WHERE T.deleted = 0";
            }
        }



        $envirement = app()->environment('local');

        $userRole = $this->getUserRole($sessionUserId);

        $roleId = is_object($userRole) ? $userRole->role_id : null;
        
        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

        $practices = DB::table("user_ddpracticelocationinfo" . ' as pli')

        ->join("user_baf_practiseinfo" . ' as p', function ($join) {
            $join->on('p.user_id', '=', 'pli.user_parent_id');
        })
        ->join("users" . ' as u', function ($join) {
            $join->on('p.user_id', '=', 'u.id');
        })
       
        
        ->whereIn('pli.user_id', $facilities)

        ->groupBy('pli.user_parent_id')

        ->pluck('pli.user_parent_id')

        ->toArray();

        $practices1 = DB::table("user_baf_practiseinfo as p")
        ->join("users" . ' as u', function ($join) {
            $join->on('p.user_id', '=', 'u.id');
        })
       
        
        ->whereIn('p.user_id', $facilities)

        ->groupBy('p.user_id')

        ->pluck('p.user_id')

        ->toArray();

        
        $practices = array_merge($practices, $practices1);
        
        $practices = array_unique($practices);
        
        
        $practices = count($practices) > 0 ? $practices : [0];
        
        $practicesStr = implode(', ', $practices);
        
        $practiceUnion = "SELECT u.id,u.is_complete,u.profile_complete_percentage,u.profile_image,
                                
                                'practice' AS type,
                                        u.gender,
                                        u.updated_at,
                                        '9' as role_id,
                                        state_of_birth,
                                        deleted,
                                         (SELECT zip_code
                                                FROM   `cm_user_baf_contactinfo`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id) AS zip,
                                           
                                        (SELECT   contact_person_email
                                            FROM   `cm_user_baf_contactinfo`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id)  AS
                                        email,
                                        
                                           
                                        (SELECT   practice_name
                                            FROM   `cm_user_baf_practiseinfo`
                                            WHERE  user_id = u.id
                                            GROUP  BY user_id) AS name,
                                        (SELECT AES_DECRYPT(facility_npi, '$key') AS npi
                                                FROM   `cm_$tbl3`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id)
                                        AS npi,
                                        (SELECT AES_DECRYPT(facility_tax_id, '$key') AS tax_id
                                                FROM   `cm_$tbl3`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id)
                                         AS taxid,
                                        '-' AS date_of_birth,
                                        '-' AS modified_date_of_birth,
                                        (SELECT contact_person_fax AS fax
                                                FROM   `cm_user_baf_contactinfo`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id)
                                         AS fax_id,
                                        (SELECT contact_person_phone AS phone
                                                FROM   `cm_user_baf_contactinfo`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id)
                                         AS phone,
                                        (SELECT  street_address AS primary_correspondence_address
                                                FROM   `cm_user_baf_contactinfo`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id)
                                         AS address,
                                         (SELECT practice_name
                                                FROM   `cm_user_baf_practiseinfo`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id)
                                         AS practice_name,
                                        (SELECT doing_business_as AS doing_buisness_as
                                                FROM   `cm_user_baf_practiseinfo`
                                                WHERE  user_id = u.id
                                                GROUP  BY user_id)
                                         AS doing_business_as
                                    FROM   `cm_user_baf_practiseinfo` bpi
                                        INNER JOIN `cm_users` u
                                                ON u.id = bpi.user_id
                                      
                                    WHERE  bpi.user_id IN($practicesStr)";
       
        $facilitiesStr = implode(', ', $facilities);
        $facilitiesUnion = "SELECT u.id,u.is_complete,u.profile_complete_percentage,u.profile_image,
                            'facility' as type,
                            u.gender,
                            u.updated_at,
                            '3' as role_id,
                            state_of_birth,
                            deleted,
                            pli.zip_five as zip,
                        (SELECT   AES_DECRYPT(contact_email, '$key') AS ct_email
                                    FROM   `cm_$tbl2`
                                    WHERE  user_id = u.id
                                    GROUP  BY user_id)
                            AS email,
                            (SELECT   AES_DECRYPT(practice_name, '$key') AS practice_name
                                    FROM   `cm_$tbl2`
                                    WHERE  user_id = u.id
                                    GROUP  BY user_id)
                            AS name,
                            (SELECT AES_DECRYPT(npi, '$key') AS npi
                                                            FROM
                                `cm_$tbl2`
                                                            WHERE
                                user_id =  u.id)
                            AS npi,
                            (SELECT AES_DECRYPT(tax_id, '$key') AS tax_id
                                                            FROM
                                `cm_$tbl2`
                                                            WHERE
                                user_id  = u.id)
                            AS taxid,
                            '-' AS date_of_birth,
                            '-' AS modified_date_of_birth,
                            (SELECT AES_DECRYPT(fax, '$key') AS fax
                                                            FROM
                                `cm_$tbl2`
                                                            WHERE
                                user_id  = u.id)
                            AS fax_id,
                            (SELECT AES_DECRYPT(phone, '$key') AS phone
                                                            FROM
                                `cm_$tbl2`
                                                            WHERE
                                user_id  = u.id)
                            AS phone,
                            (SELECT
                                AES_DECRYPT(primary_correspondence_address, '$key') AS primary_correspondence_address
                                                            FROM
                                `cm_$tbl2`
                                                            WHERE
                                user_id = user_parent_id
                                AND user_parent_id = u.id)
                            AS address,
                            (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                                            FROM
                                `cm_$tbl2`
                                                            WHERE
                                user_id = user_parent_id
                                AND user_parent_id = u.id)
                            AS practice_name,
                            (SELECT AES_DECRYPT(doing_buisness_as, '$key') AS doing_buisness_as
                                                            FROM
                                `cm_$tbl2`
                                                            WHERE
                                user_id = user_parent_id
                                AND user_parent_id = u.id)
                            AS doing_business_as
                        FROM   `cm_user_ddpracticelocationinfo` pli
                            INNER JOIN `cm_users` u
                                    ON u.id = pli.user_id
                        
                            
                        WHERE  pli.user_id IN($facilitiesStr)";
                       
        if (($sessionUserId == "36229" || $sessionUserId == "36230" || $sessionUserId == "36228") || $envirement == "true" || ($roleId == 1 || $roleId == 11 || $roleId == 37)) {
           
            $sql = "SELECT *
                FROM   ((SELECT *
                            FROM   ($practiceUnion) AS T $smartSearchSql $filterSql)
                            UNION
                            (SELECT *
                            FROM   ($facilitiesUnion) AS T $smartSearchSql $filterSql)
                        UNION
                        (SELECT *
                            FROM   (SELECT u.id,u.is_complete,u.profile_complete_percentage,u.profile_image,
                                    'provider' AS type,
                                    u.gender,
                                    u.updated_at,
                                    '10' AS role_id,
                                    state_of_birth,
                                    deleted,
                                    u.zip_five as zip,
                                    AES_DECRYPT(u.email, '$key') as email,
                                    ( CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) )  AS name,
                                    AES_DECRYPT(u.facility_npi,'$key')             AS npi,
                                    '-'                                        AS taxid,
                                    AES_DECRYPT(u.dob, '$key')                 AS date_of_birth,
                                    CONCAT(Substring(AES_DECRYPT(u.dob, '$key'), 6, 2), '/', Substring(AES_DECRYPT(u.dob, '$key'), 9, 2),
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
                                      
                                    WHERE  elm.emp_id = '$sessionUserId') AS T $smartSearchSql $filterSql
                            GROUP  BY T.id)
                        UNION(
                            SELECT * FROM(
                                SELECT oi.id,'0' AS is_complete,'0' AS profile_complete_percentage,NULL AS profile_image,'Owner' AS TYPE,
                                '-' AS gender,
                                oi.updated_at, '0' AS role_id ,
                                oi.pob AS state_of_birth,'0' AS deleted,
                                oi.zip_five as zip,
                                AES_DECRYPT(oi.email, '$key') AS email,
                                oi.name
                                ,'-' AS npi,
                                '-' AS taxid,
                                AES_DECRYPT(oi.dob, '$key')                 AS date_of_birth,
                                AES_DECRYPT(oi.dob, '$key')                 AS modified_date_of_birth,
                                AES_DECRYPT(oi.fax, '$key')                 AS fax_id,
                                AES_DECRYPT(oi.phone, '$key')               AS phone,
                                AES_DECRYPT(oi.address, '$key')    AS address,
                                '-'                                        AS practice_name,
                                '-'                                        AS doing_business_as
                                FROM `cm_user_ddownerinfo` oi
                                INNER JOIN `cm_users` u ON u.id = oi.parent_user_id
                                INNER JOIN `cm_user_ddpracticelocationinfo` pli ON pli.user_parent_id = oi.parent_user_id
                                INNER JOIN `cm_emp_location_map` elm ON elm.location_user_id = pli.user_id
                            WHERE oi.name IS NOT NULL  AND oi.user_id = 0 AND elm.emp_id = '$sessionUserId' GROUP BY oi.id
                            ) AS T $smartSearchSql $filterSql
                        )
                        UNION(
                            SELECT * FROM(
                                SELECT id,
                                '0'    AS is_complete,
                                profile_complete_percentage,
                                NULL   AS profile_image,
                                'lead' AS type,
                                '-'    AS gender,
                                updated_at,
                                '-0'                              AS role_id ,
                                state                             AS state_of_birth,
                                '0'                               AS deleted,
                                zip,
                                AES_DECRYPT(email, '$key')        AS email,
                                AES_DECRYPT(company_name, '$key') AS name,
                                '-' AS npi,
                                '-' AS taxid,
                                '-' AS date_of_birth,
                                '-' AS modified_date_of_birth,
                                '-' AS fax_id,
                                AES_DECRYPT(phone, '$key')   AS phone,
                                '-' AS address,
                                '-' AS practice_name,
                                '-' AS doing_business_as
                            FROM   `cm_leads`
                            ) AS T $smartSearchSql $filterSql
                        )
                        UNION(
                            SELECT * FROM(
                                SELECT id,
                                '0'    AS is_complete,
                                profile_complete_percentage,
                                NULL   AS profile_image,
                                'contacts' AS type,
                                '-'    AS gender,
                                updated_at,
                                '-0'                              AS role_id ,
                                state                             AS state_of_birth,
                                CASE
                                    WHEN is_active = 1 THEN 0
                                    ELSE 1
                                END AS deleted,
                                zip,
                                AES_DECRYPT(email, '$key')        AS email,
                                CASE
                                    WHEN organization_name IS NOT NULL THEN organization_name
                                    ELSE CONCAT(first_name,' ',last_name)
                                END AS name,
                                '-' AS npi,
                                '-' AS taxid,
                                '-' AS date_of_birth,
                                '-' AS modified_date_of_birth,
                                '-' AS fax_id,
                                AES_DECRYPT(phone, '$key')   AS phone,
                                '-' AS address,
                                '-' AS practice_name,
                                '-' AS doing_business_as
                            FROM   `cm_buisness_contacts` WHERE linked_profileid IS NULL
                            ) AS T $smartSearchSql $filterSql
                        )
                        ) AS H $whereType
                ORDER  BY H.updated_at DESC";
               
        } else {
            $sql = "SELECT *
                FROM   ((SELECT *
                            FROM   ($practiceUnion) AS T $smartSearchSql $filterSql)
                            UNION
                            (SELECT *
                            FROM   ($facilitiesUnion) AS T $smartSearchSql $filterSql)
                        UNION
                        (SELECT *
                            FROM   (SELECT u.id,u.is_complete,u.profile_complete_percentage,u.profile_image,
                                    'provider' AS type,
                                    u.gender,
                                    u.updated_at,
                                    '10' AS role_id,
                                    state_of_birth,
                                    deleted,
                                    u.zip_five as zip,
                                    AES_DECRYPT(u.email, '$key') as email,
                                    ( CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) )  AS name,
                                    AES_DECRYPT(u.facility_npi,'$key')             AS npi,
                                    '-'                                        AS taxid,
                                    AES_DECRYPT(u.dob, '$key')                 AS date_of_birth,
                                    CONCAT(Substring(AES_DECRYPT(u.dob, '$key'), 6, 2), '/', Substring(AES_DECRYPT(u.dob, '$key'), 9, 2),
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
                                      
                                    WHERE  elm.emp_id = '$sessionUserId') AS T $smartSearchSql $filterSql
                            GROUP  BY T.id)
                        UNION(
                            SELECT * FROM(
                                SELECT oi.id,'0' AS is_complete,'0' AS profile_complete_percentage,NULL AS profile_image,'Owner' AS TYPE,
                                '-' AS gender,
                                oi.updated_at, '0' AS role_id ,
                                oi.pob AS state_of_birth,'0' AS deleted,
                                oi.zip_five as zip,
                                AES_DECRYPT(oi.email, '$key') AS email,
                                oi.name
                                ,'-' AS npi,
                                '-' AS taxid,
                                AES_DECRYPT(oi.dob, '$key')                 AS date_of_birth,
                                AES_DECRYPT(oi.dob, '$key')                 AS modified_date_of_birth,
                                AES_DECRYPT(oi.fax, '$key')                 AS fax_id,
                                AES_DECRYPT(oi.phone, '$key')               AS phone,
                                AES_DECRYPT(oi.address, '$key')    AS address,
                                '-'                                        AS practice_name,
                                '-'                                        AS doing_business_as
                                FROM `cm_user_ddownerinfo` oi
                                INNER JOIN `cm_users` u ON u.id = oi.parent_user_id
                                INNER JOIN `cm_user_ddpracticelocationinfo` pli ON pli.user_parent_id = oi.parent_user_id
                                INNER JOIN `cm_emp_location_map` elm ON elm.location_user_id = pli.user_id
                            WHERE oi.name IS NOT NULL AND oi.user_id = 0 AND elm.emp_id = '$sessionUserId' GROUP BY oi.id
                            ) AS T $smartSearchSql $filterSql
                        )) AS H $whereType
                ORDER  BY H.updated_at DESC";
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

        //$this->printR($users,true);
        return $this->successResponse(["users" => $users, "pagination" => $pagination], "success");
    }
    /**
     * get the on-board dropdown list
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function getOnBoardDropdownList(Request $request)
    {
        $profesionalGroups = $this->fetchProfessionalGroups();
        $profesionalTypesDD = [];
        $profesionalGroupsDD = [];
        if (count($profesionalGroups)) {
            foreach ($profesionalGroups as $group) {
                array_push($profesionalGroupsDD, ["value" => $group->id, "label" => $group->name]);
                $types = $this->fetchProfessionalTypes($group->id);
                if (count($types)) {
                    foreach ($types as $type) {
                        $profesionalTypesDD[$group->id][] = ["value" => $type->id, "label" => $type->name];
                    }
                }
            }
        }
        // $citizenships       = $this->fetchData("citizenships", "");
        // foreach ($citizenships as $citizenship) {
        //     $citizenshipsRes[] = ["value" => $citizenship->id, "label" => $citizenship->name];
        // }

        $citizenships = DB::table('citizenships')

            ->select('id as value', 'name as label')

            ->orderBy('name', 'asc')

            ->get();

        $facilityGroupData = $this->fetchData("facilities", ["type" => "group"]);
        $filterFaciltyGroupData = [];
        foreach ($facilityGroupData as $faclityg) {
            //$value = explode(":",$faclityg->facility)[0];
            array_push($filterFaciltyGroupData, ["value" => $faclityg->facility, "label" => $faclityg->facility]);
        }
        $providerTexonomy = $this->fetchData("facilities", ["type" => "solo"]);
        $filterTexonomyData = [];
        foreach ($providerTexonomy as $texonomy) {
            array_push($filterTexonomyData, ["value" => $texonomy->facility, "label" => $texonomy->facility]);
        }

        $facilityTexonomy = $this->fetchData("facilities", ["type" => "group"]);
        $filterFacilityTexonomyData = [];
        foreach ($facilityTexonomy as $texonomy) {
            array_push($filterFacilityTexonomyData, ["value" => $texonomy->facility, "label" => $texonomy->facility]);
        }

        $statesCitiesData = StatesCities::select("state as value", "state as label")->distinct()
            ->orderBy("state", "asc")
            ->get();

        $stateAndCities = [];

        foreach ($statesCitiesData as $state) {
            $stateAndCities[$state->value] =  StatesCities::where("state", "=", $state->value)->select("city")->distinct()->get();
        }

        $countries = DB::table('countries')

            ->select('name as value', 'name as label')

            ->orderBy('name', 'asc')

            ->get();

        $response = [

            "profesional_groups"        => $profesionalGroupsDD,
            "profesional_types"         => $profesionalTypesDD,
            "citizenships"              => $citizenships,
            "facilty_group"             => $filterFaciltyGroupData,
            "facilities"                => $filterTexonomyData,
            'provider_taxonomy'         => $filterTexonomyData,
            'facility_taxonomy'         => $filterFacilityTexonomyData,
            'states'                    => $statesCitiesData,
            'state_and_cities'          => $stateAndCities,
            'countries'                 => $countries,
            "provider_specialties"      => $providerTexonomy

        ];
        return $this->successResponse($response, "success");
    }
    /**
     * get the practice id against user id
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPracticeId(Request $request)
    {

        $request->validate([
            "user_id" => "required",
            "type" => "required"
        ]);

        $userId = $request->user_id;

        $type = $request->type;

        if ($type == "provider") {
            $practice = DB::table('user_dd_individualproviderinfo')
                ->select("user_parent_id as practice_id")
                ->join("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "user_dd_individualproviderinfo.parent_user_id")
                ->where("user_dd_individualproviderinfo.user_id", "=", $userId)
                ->first();
        }
        if ($type == "facility") {
            $practice = DB::table('user_ddpracticelocationinfo')
                ->select("user_parent_id as practice_id")
                ->where("user_id", "=", $userId)
                ->first();
        }
        return $this->successResponse($practice, "success");
    }
    /**
     * get the practice facilities and providers against practice id
     *
     *
     * @author Faheem Mahar
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function getPracticeFacilityAndProvider(Request $request)
    {

        $request->validate([
            "practice_id" => "required"
        ]);

        $key = $this->key;
        $practiceId = $request->practice_id;
        $practice = DB::table('user_baf_practiseinfo as pi')
            ->select(DB::raw('IFNULL(cm_pi.practice_name,cm_pi.doing_business_as) AS practice_name'), "pi.user_id as practice_id", "users.profile_complete_percentage", "users.status", "users.is_complete")
            ->join('users', 'users.id', 'pi.user_id')
            ->where("pi.user_id", "=", $practiceId)
            ->first();


        $facilities = DB::table('user_ddpracticelocationinfo as pli')
            ->select(DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name"), "pli.user_id as facility_id", "users.profile_complete_percentage", "users.status", "users.is_complete")
            ->join('users', 'users.id', 'pli.user_id')
            ->where("user_parent_id", "=", $practiceId)
            ->get();

        $providers = [];

        if ($facilities->count() > 0) {
            foreach ($facilities as $facility) {
                $providersRes = DB::table('individualprovider_location_map as iplp')
                    ->select(DB::raw("CONCAT(COALESCE(cm_users.first_name,''),' ',COALESCE(cm_users.last_name,'')) as provider_name"), "users.id as provider_id", "users.profile_complete_percentage", "users.status", "users.is_complete", 'iplp.for_credentialing as facility_status')
                    ->join('users', 'users.id', 'iplp.user_id')
                    ->where("iplp.location_user_id", "=", $facility->facility_id)
                    ->get();
                $providers[$facility->facility_id] = $providersRes;
            }
        }

        $response = [
            "practice" => $practice,
            "facilities" => $facilities,
            "providers" => $providers
        ];
        return $this->successResponse($response, "success");
    }
    /**
     * active and inactive directory user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function activeInactiveDirectoryPracitce(Request $request)
    {

        $request->validate([
            "pratice_data" => "required",
        ]);

        $sessionUser            = $this->getSessionUserId($request);

        $sessionUserId          = $sessionUser;

        $practiceData    = json_decode($request->pratice_data, true);

        $practice        = $practiceData['practice'];
        $practiceId      = $practice['practice_id'];
        $reason = "";
        $from = "0000-00-00";
        $to       = "0000-00-00";
        $table1 = "user_ddpracticelocationinfo";
        $table2 = "users";
        $table3 = "individualprovider_location_map";
        $ActiveInActiveLogsObj = new ActiveInActiveLogs();
        if (isset($practice['reason']) && isset($practice['status'])) {

            // echo $practiceId;
            // exit;
            $status          = $practice['status'] == 'Active' ? 1 : 0;

            if ($status == 1)
                $from =  $practice['date'];
            else
                $to = $practice['date'];

            $reason          = $practice['reason'];

            $this->updateData($table1, ["user_parent_id" => $practiceId], ["for_credentialing" => $status]);

            $this->updateData($table2, ["id" => $practiceId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => $practice['status']]);
            // echo $table2.":Here:".$practiceId;
            // exit;
            // echo $table2.":Here:".$practiceId;
            // exit;
            $ActiveInActiveLogsObj->managePracticeActiveInActiveLogs($practiceId, $status, $from, $to, $sessionUserId, $reason);
        }


        //$status = 1;
        //$statusStr = "Active";
        $facilities = $practiceData["facilities"];
        $providers = $practiceData["providers"];
        if (count($facilities)) {
            foreach ($facilities as $facility) {

                $facilityId = $facility['facility_id'];
                $status     = $facility['status'] == 'Active' ? 1 : 0;
                if ($practiceId != $facilityId) {
                    if (isset($facility['reason']) && isset($facility['status'])) {
                        $from = "0000-00-00";
                        $to = "0000-00-00";
                        if ($status == 1)
                            $from = $facility['date'];
                        else
                            $to = $facility['date'];

                        $this->updateData($table3, ["location_user_id" => $facilityId], ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);
                        $this->updateData($table2, ["id" => $facilityId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => $facility['status']]);
                        $ActiveInActiveLogsObj->manageLocationActiveInactivityLog($practiceId, $facilityId, $status, $from, $to, $sessionUserId, $facility['reason']);
                    } elseif (!isset($facility['reason']) && isset($facility['status'])) {
                        $this->updateData($table3, ["location_user_id" => $facilityId], ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);
                        $this->updateData($table2, ["id" => $facilityId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => $facility['status']]);
                        $ActiveInActiveLogsObj->manageLocationActiveInactivityLog($practiceId, $facilityId, $status, $from, $to, $sessionUserId, $reason);
                    }
                }

                $facilityProviders = $providers[$facilityId];


                if (count($facilityProviders) > 0) {
                    foreach ($facilityProviders as $provider) {
                        // exit("In else");
                        $providerId = $provider["provider_id"];
                        $whereProvider = [
                            ["location_user_id", "=", $facilityId],
                            ["user_id", "=", $providerId],
                        ];
                        $status     = $provider['facility_status'] == '1' ? 1 : 0;
                        $this->updateData($table3, $whereProvider, ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);
                        if (isset($provider['reason']) && isset($provider['status'])) {
                            // $this->printR($provider,true);
                            $from = "0000-00-00";
                            $to = "0000-00-00";
                            if ($status == 1)
                                $from = $provider['date'];
                            else
                                $to = $provider['date'];

                            // $inOtherLocation = $this->fetchData($table3, ["user_id" => $providerId, 'for_credentialing' => 1], 0, ["user_id"]);
                            // if (count($inOtherLocation) == 0 && $status == 0) {
                            //     $ActiveInActiveLogsObj->manageProviderActivityLogs($providerId, $status, $from, $to, $sessionUserId, $provider['reason']);
                            //     $this->updateData($table2, ["id" => $providerId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => $provider["status"]]);
                            // } else
                            {

                                $ActiveInActiveLogsObj->manageProviderActivityLogs($providerId, $status, $from, $to, $sessionUserId, $reason);

                                $findProvider = [
                                    ["id", "=", $providerId]
                                ];
                                if ($status == 0) {
                                    $inOtherLocation = $this->fetchData($table3, ["user_id" => $providerId, 'for_credentialing' => 1], 0, ["user_id"]);
                                    if (count($inOtherLocation) == 0) {
                                        $deleted = $status == 1 ? 0 : 1;
                                        $updateProvider = ["deleted" => $deleted, 'status' => "Inactive", 'updated_at' => $this->timeStamp()];
                                        $this->updateData($table2, $findProvider, $updateProvider);
                                    }
                                }
                                if ($status == 1) {
                                    $deleted = $status == 1 ? 0 : 1;
                                    $updateProvider = ["deleted" => $deleted, 'status' => "Active", 'updated_at' => $this->timeStamp()];
                                    $this->updateData($table2, $findProvider, $updateProvider);
                                }
                            }
                        } elseif (!isset($provider['reason']) && isset($provider['status'])) {
                            // $from = "0000-00-00";
                            // $to = "0000-00-00";
                            // if ($status == 1)
                            //     $from = $provider['date'];
                            // else
                            //     $to = $provider['date'];

                            // $inOtherLocation = $this->fetchData($table3, ["user_id" => $providerId, 'for_credentialing' => 1], 0, ["user_id"]);
                            // if (count($inOtherLocation) == 0 && $status == 0) {

                            //     $ActiveInActiveLogsObj->manageProviderActivityLogs($providerId, $status, $from, $to, $sessionUserId, $reason);
                            //     $this->updateData($table2, ["id" => $providerId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => $provider["status"]]);
                            // } else
                            {
                                // echo $providerId;
                                // echo PHP_EOL;
                                // echo $status;
                                // exit;
                                $ActiveInActiveLogsObj->manageProviderActivityLogs($providerId, $status, $from, $to, $sessionUserId, $reason);
                                if ($status == 0) {
                                    $inOtherLocation = $this->fetchData($table3, ["user_id" => $providerId, 'for_credentialing' => 1], 0, ["user_id"]);
                                    if (count($inOtherLocation) == 0) {
                                        $this->updateData($table2, ["id" => $providerId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => "Inactive"]);
                                    }
                                }
                                if ($status == 1) {
                                    $this->updateData($table2, ["id" => $providerId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => "Active"]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $ActiveInActiveLogsObj = NULL;
        return $this->successResponse(["is_update" => true], "Update successfully");
    }
    /**
     * fetch the facility providers
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    function fetchFacilityProviders(Request $request)
    {

        $request->validate([
            "facility_id" => "required"
        ]);

        $facilityId = $request->facility_id;


        $providers = DB::table('individualprovider_location_map as iplp')
            ->select(DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) as provider_name"), "users.id as provider_id", "users.profile_complete_percentage", "users.status", "users.is_complete")
            ->join('users', 'users.id', 'iplp.user_id')
            ->where("iplp.location_user_id", "=", $facilityId)
            ->get();

        $response = [
            "providers" => $providers
        ];
        return $this->successResponse($response, "success");
    }
    /**
     * active and inactive directory facility user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function activeInactiveDirectoryFacility(Request $request)
    {
        $request->validate([
            "facility_data" => "required",
        ]);

        $sessionUser            = $this->getSessionUserId($request);

        $sessionUserId          = $sessionUser;

        $facilityData    = json_decode($request->facility_data, true);
        $practiceId      = $facilityData["practice_id"];
        $facilityId      = $facilityData["facility_id"];
        $status          = $facilityData['status'];
        $affiliationDate = $facilityData['affiliation_date'];
        $reason          = $facilityData['reason'];
        $dateConst       = "0000-00-00";

        $ActiveInActiveLogsObj = new ActiveInActiveLogs();

        $table1 = "user_ddpracticelocationinfo";
        $table2 = "users";
        $table3 = "individualprovider_location_map";
        $statusStr = "Active";
        if ($status != "Active") {
            $status = 0;
            $this->updateData($table1, ["user_id" => $facilityId], ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);
            $this->updateData($table2, ["id" => $facilityId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => "Inactive"]);
            $this->updateData($table3, ["location_user_id" => $facilityId], ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);
            $userInManyLocation = $this->fetchData($table3, ["location_user_id" => $facilityId], 0, ["user_id"]);
            if (count($userInManyLocation)) { //this code for in active the providers with practice and location
                foreach ($userInManyLocation as $provider) {
                    if ($status == 0) {
                        $inOtherLocation = $this->fetchData($table3, ["user_id" => $provider->user_id, 'for_credentialing' => 1, 'updated_at' => $this->timeStamp()], 0, ["user_id"]);
                        if (count($inOtherLocation) == 0) {
                            $this->updateData($table2, ["id" => $provider->user_id], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => "Inactive"]);
                        }
                    } else {
                        $this->updateData($table2, ["id" => $provider->user_id], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => "Inactive"]);
                    }
                }
            }
            $ActiveInActiveLogsObj->manageLocationActiveInactivityLog($practiceId, $facilityId, $status, $dateConst, $affiliationDate, $sessionUserId, $reason);
        } else {
            $status = 1;
            $statusStr = "Active";
            $providers = $facilityData["providers"];
            $this->updateData($table3, ["location_user_id" => $facilityId], ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);
            $this->updateData($table2, ["id" => $facilityId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => $statusStr]);
            if (count($providers) > 0) {
                foreach ($providers as $provider) {
                    $this->updateData($table2, ["id" => $provider["provider_id"]], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), 'status' => $provider["status"]]);
                }
            }
            $ActiveInActiveLogsObj->manageLocationActiveInactivityLog($practiceId, $facilityId, $status, $affiliationDate, $dateConst, $sessionUserId, $reason);
        }
        $ActiveInActiveLogsObj = NULL;
        return $this->successResponse(["is_updated" => true], "success");
    }
    /**
     * active and inactive directory provider user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function activeInactiveDirectoryProvider(Request $request)
    {

        $request->validate([
            "provider_id" => "required",
        ]);
        $sessionUser            = $this->getSessionUserId($request);

        $sessionUserId          = $sessionUser;

        $status          = $request->status;
        $affiliationDate = $request->affiliation_date;
        $reason          = $request->reason;
        $dateConst       = "0000-00-00";

        $ActiveInActiveLogsObj = new ActiveInActiveLogs();

        $providerId =   $request->provider_id;

        $table1 = "users";

        $table2 = "individualprovider_location_map";

        if ($status != "Active") {
            $status = 0;
            $this->updateData($table1, ["id" => $providerId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), "status" => "Inactive"]);
            $this->updateData($table2, ["user_id" => $providerId], ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);

            $ActiveInActiveLogsObj = new ActiveInActiveLogs();
            $ActiveInActiveLogsObj->manageProviderActivityLogs($providerId, $status, $dateConst, $affiliationDate, $sessionUserId, $reason);
        } else {
            $status = 1;
            $this->updateData($table1, ["id" => $providerId], ["deleted" => $status == 1 ? 0 : 1, 'updated_at' => $this->timeStamp(), "status" => "Active"]);
            $this->updateData($table2, ["user_id" => $providerId], ["for_credentialing" => $status, 'updated_at' => $this->timeStamp()]);

            $ActiveInActiveLogsObj = new ActiveInActiveLogs();
            $ActiveInActiveLogsObj->manageProviderActivityLogs($providerId, $status, $affiliationDate, $dateConst, $sessionUserId, $reason);
        }
        $ActiveInActiveLogsObj = NULL;
        return $this->successResponse(["is_updated" => true], "success");
    }
    /**
     * share the provider form via email
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function shareProviderForm(Request $request)
    {
        $request->validate([
            "email" => "required",
            "provider_id" => "required"
        ]);

        $token          = Str::random(32);
        $providerId     = $request->provider_id;
        $email          = $request->email;
        $sessionUserId  = $request->session_user_id;
        $table          = "shared_provider_form";
        $findProvider   = ["provider_id" => $providerId];
        $updateProvider = ["token" => $token, "sent_by" => $sessionUserId, "provider_id" => $providerId];
        $provider = $this->fetchData($table, $findProvider, 1);

        $frontEndBaseUrl = $request->frontend_url;

        if (!is_object($provider))
            $this->addData($table, $updateProvider);
        else
            $this->updateData($table, $findProvider, $updateProvider);

        $provider = $this->fetchData($table, $findProvider, 1);

        $providerToken = $provider->token;

        $url = $frontEndBaseUrl . "provider/form/" . $providerToken . "/2";

        $whereProvider = [
            ["id", "=", $providerId]
        ];

        $providerProfile = $this->fetchData("users", $whereProvider, 1, ["first_name", "last_name"]);
        $providerName = null;
        $inLocations = 0;
        $practiceName = "";
        if (is_object($providerProfile)) {

            $providerName = $providerProfile->first_name . " " . $providerProfile->last_name;

            $mappedLoc = $this->fetchData("individualprovider_location_map", [
                ["user_id", "=", $providerId]
            ], 0, ["id"]);
            // echo $providerId;
            // exit;
            $mappedPractice = $this->fetchData("user_dd_individualproviderinfo", [
                ["user_id", "=", $providerId]
            ], 1, ["parent_user_id"]);

            $practiceId = $mappedPractice->parent_user_id;

            $practice = $this->fetchData("user_baf_practiseinfo", [
                ["user_id", "=", $practiceId]
            ], 1, ["practice_name", "doing_business_as"]);
            $practiceName = null;
            if (is_object($practice)) {
                $practiceName = is_null($practice->practice_name) ? $practice->doing_business_as : $practice->practice_name;
            }
            $inLocations = count($mappedLoc);
        }

        $isSent = 0;
        $msg = "";
        try {
            $isSent = 1;
            Mail::to($email)
                ->send(new ProviderFormLinkEmail(['link' => $url, 'provider_name' => $providerName, 'locations' => $inLocations, 'practice_name' => $practiceName]));
        } catch (\Throwable $exception) {
            $isSent = 0;
            $msg = $exception->getMessage();
        }
        if ($isSent == 0)
            return $this->warningResponse(["msg" => $msg], "email error", 502);
        else {
            $this->updateData($table, ["provider_id" => $providerId], [
                "sent_by"       => $sessionUserId,
                "provider_id"   => $providerId,
                "is_link_sent"  => $isSent,
                "sent_time"     => $this->timeStamp(),
                "email"         => $email
            ]);
            $key = $this->key;
            $shareId = DB::table($table)
                ->where("provider_id", "=", $providerId)
                ->select("id")
                ->first();

            $log = "Shared to " . $email;
            $logs = [];
            $logs["log"]            = DB::raw("AES_ENCRYPT('" .    $log     . "', '$key')");
            $logs["share_id"]       = $shareId->id;
            $logs["session_userid"] = $sessionUserId;
            $logs["created_at"]     = $this->timeStamp();
            //add the log for share provider
            DB::table('provider_formshare_logs')->insertGetId($logs);

            return $this->successResponse(["is_email_sent" => true], "success");
        }
    }
    /**
     * check the provider link is valid or not
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function checkProviderLink(Request $request)
    {
        $request->validate([
            "token" => "required"
        ]);
        $token = $request->token;
        $table = "shared_provider_form";
        $findProvider = ["token" => $token];
        $provider = $this->fetchData($table, $findProvider, 1);
        if (is_object($provider)) {
            if ($provider->is_link_active == 0)
                return $this->warningResponse(["message" => "link is in active"], "error", 401);
            else {
                $this->updateData($table, ["provider_id" => $provider->provider_id], [
                    "is_viewed" => 1, "viewed_time" => $this->timeStamp()
                ]);
                return $this->successResponse(["is_valid" => true, 'provider_id' => $provider->provider_id], "success");
            }
        } else
            return $this->warningResponse(["message" => "record not found"], "error", 404);
    }
    /**
     * send the otp to provider
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function sendProviderOTP(Request $request)
    {
        $request->validate([
            "token" => "required"
        ]);
        $key = $this->key;
        $token  = $request->token;
        $user = DB::table("shared_provider_form as spf")
            ->select(
                "spf.provider_id",
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as provider_name"),
                "spf.email"
            )
            ->join("users", "users.id", "=", "spf.provider_id")
            ->where("spf.token", $token)
            ->first();
        if (is_object($user)) {
            $otp          = Str::random(4);
            try {
                $isSent = 1;
                Mail::to($user->email)
                    ->send(new ProviderFormOTPCode(['otp_code' => $otp, 'name' => $user->provider_name]));
            } catch (\Throwable $exception) {
                $isSent = 0;
                $msg = $exception->getMessage();
            }
            if ($isSent == 0)
                return $this->warningResponse(["msg" => $msg], "email error", 502);
            else {
                $this->updateData("shared_provider_form", ["provider_id" => $user->provider_id], [
                    "otp_code" => $otp
                ]);
                return $this->successResponse(["is_email_sent" => true], "success");
            }
        } else {
            return $this->warningResponse(["message" => "record not found"], "error", 404);
        }
    }
    /**
     * verify otp token
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @param \Illuminate\Http\Response
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            "token" => "required",
            "otp_code" => "required"
        ]);
        $key = $this->key;
        $token  = $request->token;
        $otpCode  = $request->otp_code;

        $user = DB::table("shared_provider_form as spf")
            ->select(
                "spf.provider_id",
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as provider_name"),
                DB::raw("AES_DECRYPT(email, '$key') as email")
            )
            ->join("users", "users.id", "=", "spf.provider_id")
            ->where("spf.token", $token)
            ->where("spf.otp_code", $otpCode)
            ->first();
        if (is_object($user))
            return $this->successResponse(["is_verified" => true], "success");
        else
            return $this->warningResponse(["message" => "Invalid otp given"], "error", 401);
    }
    /**
     * share the facility form via email
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function shareFacilityForm(Request $request)
    {
        $request->validate([
            "email"         => "required",
            "facility_id"   => "required",
            "first_name"    => "required",
            "last_name"     => "required",
        ]);
        $key = $this->key;
        $token          = Str::random(32);
        $facilityId     = $request->facility_id;
        $email          = $request->email;
        $sessionUserId  = $request->session_user_id;
        $firstName      = $request->first_name;
        $lastName       = $request->last_name;
        $email          = $request->email;
        $table          = "shared_facility_form";
        $findFacility   = ["facility_id" => $facilityId];
        $encEmail =  DB::raw("AES_ENCRYPT('" .    $email     . "', '$key')");
       
       
        $updateFacility = ["token" => $token, "sent_by" => $sessionUserId, "facility_id" => $facilityId, "first_name" => $firstName, "last_name" => $lastName, "email" => $encEmail];
        $facility       = $this->fetchData($table, $findFacility, 1);
        
        $frontEndBaseUrl = $request->frontend_url;

        if (!is_object($facility))
            $this->addData($table, $updateFacility);
        else
            $this->updateData($table, $findFacility, $updateFacility);
        
        $facility = $this->fetchData($table, $findFacility, 1);

        $facilityToken = $facility->token;

        $url = $frontEndBaseUrl . "facility/form/" . $facilityToken;

        $whereFacility = [
            ["user_id", "=", $facilityId]
        ];
        $whereSession = [
            ["id", "=", $sessionUserId]
        ];
        $sessionUser = $this->fetchData("users", $whereSession, 1, ["first_name", "last_name"]);

        $sessionUserName = is_object($sessionUser) ? $sessionUser->first_name . " " . $sessionUser->last_name : "";
        $facilityProfile = $this->fetchData("user_ddpracticelocationinfo", $whereFacility, 1, [DB::raw("AES_DECRYPT(practice_name, '$key') as practice_name"), 'user_parent_id']);

        $practiceName = "";
        if (is_object($facilityProfile)) {

            $practiceId = $facilityProfile->user_parent_id;

            $practice = $this->fetchData("user_baf_practiseinfo", [
                ["user_id", "=", $practiceId]
            ], 1, ["practice_name", "doing_business_as"]);
            $practiceName = is_null($practice->practice_name) ? $practice->doing_business_as : $practice->practice_name;
        }
        $subject = "IMPORTANT: New Location Information Required for " . $practiceName;
        $isSent = 0;
        $msg = "";
        try {
            $isSent = 1;
            Mail::to($email)
                ->send(new FacilityFormLinkEmail([
                    'link' => $url,
                    'first_name' => $firstName, 'last_name' => $lastName, 'subject' => $subject, 'practice_name' => $practiceName,
                    'session_username' => $sessionUserName
                ]));
        } catch (\Throwable $exception) {
            $isSent = 0;
            $msg = $exception->getMessage();
        }
        if ($isSent == 0)
            return $this->warningResponse(["msg" => $msg], "email error", 502);
        else {
            $this->updateData($table, ["facility_id" => $facilityId], [
                "sent_by" => $sessionUserId,
                "facility_id" => $facilityId, "is_link_sent" => $isSent, "sent_time" => $this->timeStamp()
            ]);

            $logs = [];

            $shareId = DB::table($table)
                ->select("id")
                ->where("facility_id", "=", $facilityId)
                ->first();

            $name = $firstName . " " . $lastName;

            $log = "Shared to name " . $name . " <br/> Shared to email " . $email;

            $logs["share_id"]       = $shareId->id;
            $logs["session_userid"] = $sessionUserId;
            $logs["log"]           = DB::raw("AES_ENCRYPT('" .    $log     . "', '$key')");
            //add the logs
            DB::table("facility_formshare_logs")

                ->insertGetId($logs);

            return $this->successResponse(["is_email_sent" => true], "success");
        }
    }
    /**
     * check the facility link is valid or not
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function checkFacilityLink(Request $request)
    {
        $request->validate([
            "token" => "required"
        ]);
        $token = $request->token;
        $table = "shared_facility_form";
        $findFacility = ["token" => $token];
        $facility = $this->fetchData($table, $findFacility, 1);
        if (is_object($facility)) {
            if ($facility->is_link_active == 0)
                return $this->warningResponse(["message" => "link is in active"], "error", 401);
            else {
                $this->updateData($table, ["facility_id" => $facility->facility_id], [
                    "is_viewed" => 1, "viewed_time" => $this->timeStamp()
                ]);
                return $this->successResponse(["is_valid" => true, 'facility_id' => $facility->facility_id], "success");
            }
        } else
            return $this->warningResponse(["message" => "record not found"], "error", 404);
    }
    /**
     * send the otp to facility
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function sendFacilityOTP(Request $request)
    {
        $request->validate([
            "token" => "required"
        ]);
        $key = $this->key;
        $token  = $request->token;
        $user = DB::table("shared_facility_form as sff")
            ->select(
                "sff.facility_id",
                DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') as facility_name"),
                DB::raw("AES_DECRYPT(cm_users.email, '$key') as email"),
                DB::raw("AES_DECRYPT(cm_sff.email, '$key') as f_email")
            )
            ->join("user_ddpracticelocationinfo as pli", "pli.user_id", "=", "sff.facility_id")
            ->join("users", "users.id", "=", "pli.user_id")
            ->where("sff.token", $token)
            ->first();
        if (is_object($user)) {
            $otp          = Str::random(4);
            try {
                $isSent = 1;
                Mail::to($user->f_email)
                    ->send(new FacilityFormOTPCode(['otp_code' => $otp, 'name' => $user->facility_name]));
            } catch (\Throwable $exception) {
                $isSent = 0;
                $msg = $exception->getMessage();
            }
            if ($isSent == 0)
                return $this->warningResponse(["msg" => $msg], "email error", 502);
            else {
                $this->updateData("shared_facility_form", ["facility_id" => $user->facility_id], [
                    "otp_code" => $otp
                ]);
                return $this->successResponse(["is_email_sent" => true], "success");
            }
        } else {
            return $this->warningResponse(["message" => "record not found"], "error", 404);
        }
    }
    /**
     * verify otp token
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @param \Illuminate\Http\Response
     */
    public function verifyFacilityOtp(Request $request)
    {
        $request->validate([
            "token" => "required",
            "otp_code" => "required"
        ]);
        $key = $this->key;
        $token  = $request->token;
        $otpCode  = $request->otp_code;

        $user = DB::table("shared_facility_form as sff")
            ->select(
                "sff.facility_id",
                DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') as facility_name"),
                DB::raw("AES_DECRYPT(cm_users.email, '$key') as email"),
                DB::raw("AES_DECRYPT(cm_sff.email, '$key') as f_email")
            )
            ->join("user_ddpracticelocationinfo as pli", "pli.user_id", "=", "sff.facility_id")
            ->join("users", "users.id", "=", "pli.user_id")
            ->where("sff.token", $token)
            ->where("sff.otp_code", $otpCode)
            ->first();
        if (is_object($user))
            return $this->successResponse(["is_verified" => true], "success");
        else
            return $this->warningResponse(["message" => "Invalid otp given"], "error", 401);
    }
    /**
     * upload the provider documents
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function uploadProviderDocuments(Request $request)
    {

        $request->validate([
            'DEA_document' => 'required_without_all:state_license,driver_license,resume_cv,highest_degree,board_certification,general_liability_insurance,professional_liability_insurance', // At least one file should be filled
            'state_license' => 'required_without_all:DEA_document,driver_license,resume_cv,highest_degree,board_certification,general_liability_insurance,professional_liability_insurance',
            'driver_license' => 'required_without_all:DEA_document,state_license,resume_cv,highest_degree,board_certification,general_liability_insurance,professional_liability_insurance',
            'resume_cv' => 'required_without_all:DEA_document,state_license,driver_license,highest_degree,board_certification,general_liability_insurance,professional_liability_insurance',
            'highest_degree' => 'required_without_all:DEA_document,state_license,driver_license,resume_cv,board_certification,general_liability_insurance,professional_liability_insurance',
            'board_certification' => 'required_without_all:DEA_document,state_license,driver_license,resume_cv,highest_degree,general_liability_insurance,professional_liability_insurance',
            'general_liability_insurance' => 'required_without_all:DEA_document,state_license,driver_license,resume_cv,highest_degree,board_certification,professional_liability_insurance',
            'professional_liability_insurance' => 'required_without_all:DEA_document,state_license,driver_license,resume_cv,highest_degree,board_certification,general_liability_insurance',
            "provider_id" => "required",
            "created_by" => "required"
        ]);

        $providerId = $request->provider_id;
        $createdBy = $request->created_by;
        $isUpload = false;
        $filesUploadedCount = 0;
        $filesUploadedRes = [];
        if ($request->hasFile("DEA_document")) {
            // Get the file from the request
            $file = $request->file('DEA_document');
            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 36;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "DEA_document";

                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("state_license")) {
            // Get the file from the request
            $file = $request->file('state_license');
            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 82;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "state_license";
                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("resume_cv")) {
            // Get the file from the request
            $file = $request->file('resume_cv');
            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 25;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "resume_cv";
                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("driver_license")) {
            // Get the file from the request
            $file = $request->file('driver_license');
            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 23;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "driver_license";
                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("board_certification")) {
            // Get the file from the request
            $file = $request->file('board_certification');
            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 35;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "board_certification";

                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("highest_degree")) {
            // Get the file from the request
            $file = $request->file('highest_degree');
            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 31;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "highest_degree";
                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("general_liability_insurance")) {
            // exit("In iff");
            // Get the file from the request
            $file = $request->file('general_liability_insurance');

            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);

            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 41;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["insurance_type"]  = 'general';
                $addData["doc_name"]        = "general_liability_insurance";

                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("professional_liability_insurance")) {
            // Get the file from the request
            $file = $request->file('professional_liability_insurance');
            $destFolder = "providersEnc/attachments/" . $providerId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 41;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["provider_id"]     = $providerId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["insurance_type"]  = 'professional';
                $addData["doc_name"]        = "professional_liability_insurance";
                $this->addData("provider_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        return $this->successResponse(["is_upload" => $isUpload, 'total_uploaded' => $filesUploadedCount, 'files_uploaded' => $filesUploadedRes], "success");
    }
    /**
     * upload the facility documents
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function uploadFacilityDocuments(Request $request)
    {
        $request->validate([
            'W9_form' => 'required_without_all:general_liability_insurance,professional_liability_insurance,CLIA_certificate,organizational_chart,copy_of_licenses', // At least one file should be filled
            'general_liability_insurance' => 'required_without_all:W9_form,professional_liability_insurance,CLIA_certificate,organizational_chart,copy_of_licenses',
            'professional_liability_insurance' => 'required_without_all:W9_form,general_liability_insurance,CLIA_certificate,organizational_chart,copy_of_licenses',
            'CLIA_certificate' => 'required_without_all:W9_form,general_liability_insurance,professional_liability_insurance,organizational_chart,copy_of_licenses',
            'organizational_chart' => 'required_without_all:W9_form,general_liability_insurance,professional_liability_insurance,CLIA_certificate,copy_of_licenses',
            'copy_of_licenses' => 'required_without_all:W9_form,general_liability_insurance,professional_liability_insurance,CLIA_certificate,organizational_chart',
            "facility_id" => "required",
            "created_by" => "required"
        ]);
        $facilityId = $request->facility_id;
        $createdBy = $request->created_by;
        $isUpload = false;
        $filesUploadedCount = 0;
        $filesUploadedRes = [];
        if ($request->hasFile("W9_form")) {
            // Get the file from the request
            $file = $request->file('W9_form');
            $destFolder = "facilityEnc/attachments/" . $facilityId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 60;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["facility_id"]     = $facilityId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "W9_form";

                $this->addData("facility_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("general_liability_insurance")) {
            // Get the file from the request
            $file = $request->file('general_liability_insurance');
            $destFolder = "facilityEnc/attachments/" . $facilityId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 41;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["facility_id"]     = $facilityId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "general_liability_insurance";
                $addData["insurance_type"]  = 'general';
                $this->addData("facility_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("professional_liability_insurance")) {
            // Get the file from the request
            $file = $request->file('professional_liability_insurance');
            $destFolder = "facilityEnc/attachments/" . $facilityId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 41;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["facility_id"]     = $facilityId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "professional_liability_insurance";
                $addData["insurance_type"]  = 'professional';
                $this->addData("facility_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("CLIA_certificate")) {
            // Get the file from the request
            $file = $request->file('CLIA_certificate');
            $destFolder = "facilityEnc/attachments/" . $facilityId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 77;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["facility_id"]     = $facilityId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "CLIA_certificate";
                $this->addData("facility_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        if ($request->hasFile("copy_of_licenses")) {
            // Get the file from the request
            $file = $request->file('copy_of_licenses');
            $destFolder = "facilityEnc/attachments/" . $facilityId;

            $fileRes = $this->encryptAndUpload($request, $file, $destFolder);
            if (isset($fileRes["file_name"])) {
                array_push($filesUploadedRes, $fileRes);
                $typeId = 82;
                $fileName = $fileRes["file_name"];
                $addData = [];

                $addData["facility_id"]     = $facilityId;
                $addData["type_id"]         = $typeId;
                $addData["file_name"]       = $fileName;
                $addData["path"]            = $destFolder;
                $addData["created_by"]      = $createdBy;
                $addData["created_at"]      = $this->timeStamp();
                $addData["doc_name"]        = "copy_of_licenses";
                $this->addData("facility_attachments", $addData, 0);
                $isUpload = true;
                $filesUploadedCount++;
            }
        }
        return $this->successResponse(["is_upload" => $isUpload, 'total_uploaded' => $filesUploadedCount, 'files_uploaded' => $filesUploadedRes], "success");
    }
    /**
     * approve the provider uploaded dea
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function approveProviderDeaAttachments(Request $request)
    {

        $request->validate([
            "type_id"           => "required",
            "license_no"        => "required",
            "state"             => "required",
            "issue_date"        => "required",
            "exp_date"          => "required",
            "provider_id"       => "required",
            "created_by"        => "required",
            "file"              => "required",
        ]);

        $file = $request->input('file');
        $fileArr = json_decode($file, true);
        $sourcePath = $fileArr['path'];
        $fileName = $fileArr['file_name'];
        $tempRecId = $fileArr['id'];

        $sourcePath = $sourcePath . '/' . $fileName;
        $userId = $request->provider_id;
        $typeId = $request->type_id;
        $LicenseNo = $userId . "_" . $request->license_no;
        $status = $request->has("status") ? $request->status : 0;
        $addLicenseData = [];

        $licenseVersion = License::where("user_id", "=", $userId)
            ->where("type_id", "=", $typeId)
            ->where("license_no", "=", $LicenseNo)
            ->orderBy('id', 'DESC')
            ->first();

        $documentLicenseVersion = is_object($licenseVersion) ? (int)$licenseVersion->document_version + 1 : 1;

        $addLicenseData["user_id"]      = $userId;
        $addLicenseData["license_no"]   = $LicenseNo;
        $addLicenseData["issue_date"]   = $request->issue_date;
        $addLicenseData["exp_date"]     = $request->exp_date;
        $addLicenseData["issuing_state"] = $request->state;
        $addLicenseData["type_id"]      = $request->type_id;
        $addLicenseData["created_by"]   = $request->created_by;
        $addLicenseData["notify_before_exp"]   = $request->has('remind_before_days') ? $request->remind_before_days : 30;
        $addLicenseData["status"]       = $status;
        $addLicenseData["currently_practicing"]       = $request->currently_practicing;
        $addLicenseData["note"]       = $request->notes;
        $addLicenseData["created_at"]   = $this->timeStamp();
        $addLicenseData["document_version"]       = $documentLicenseVersion;

        $destFolder = "providersEnc/licenses/" . $userId . '/' . $fileName;

        // Move the file
        Storage::disk('ftp')->move($sourcePath, $destFolder);

        $id = License::insertGetId($addLicenseData);


        $addFileData = [
            "entities"     => "license_id",
            "entity_id"     => $id,
            "field_key"     => "license_file",
            "field_value"   => $fileName,
            "note" => $request->notes
        ];

        $this->addData("attachments", $addFileData, 0);

        $whereProviderTempRec = [
            ["id", "=", $tempRecId]
        ];

        $this->deleteData("provider_attachments", $whereProviderTempRec);

        return $this->successResponse(["is_approved" => true], "success");
    }
    /**
     * approve the provider uploaded cv
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function approveProviderCvAttachments(Request $request)
    {

        $request->validate([
            "type_id"           => "required",
            "provider_id"       => "required",
            "created_by"        => "required",
            "file"              => "required",
            "type"              => "required",
        ]);
        $addLicenseData = [];
        $userId = $request->provider_id;

        $file = $request->input('file');
        $fileArr = json_decode($file, true);

        $sourcePath = $fileArr['path'];
        $fileName = $fileArr['file_name'];
        $tempRecId = $fileArr['id'];
        $sourcePath = $sourcePath . '/' . $fileName;
        //$userName = $this->getUserNameById($userId);

        $reqName = $userId . "_CV";

        $typeId = $request->type_id;

        $type = $userId . "_Curriculum Vitae";


        $destFolder = "providersEnc/licenses/" . $userId . '/' . $fileName;;

        $license = License::select('document_version')

            ->where('user_id', '=', $userId)

            ->where('type_id', '=', $typeId)

            ->where('name', '=', $reqName)

            ->orderBy("id", "DESC")

            ->first();

        $version = is_object($license) ? (int)$license->document_version + 1 : 1;

        $addLicenseData["user_id"]      = $userId;
        $addLicenseData["note"]         = $request->notes;
        $addLicenseData["license_no"]   = $type;
        $addLicenseData["name"]         = $reqName;
        $addLicenseData["issue_date"]   = NULL;
        $addLicenseData["exp_date"]     = NULL;
        $addLicenseData["issuing_state"] = NULL;
        $addLicenseData["type_id"]      = $typeId;
        $addLicenseData["created_by"]   = $request->created_by;
        $addLicenseData["notify_before_exp"]   = 0;
        $addLicenseData["status"]       = 0;
        $addLicenseData["currently_practicing"]       = 0;
        $addLicenseData["created_at"]   = $this->timeStamp();
        $addLicenseData["document_version"]       = $version;

        // Move the file
        Storage::disk('ftp')->move($sourcePath, $destFolder);

        $id = License::insertGetId($addLicenseData);

        $addFileData = [
            "entities"     => "license_id",
            "entity_id"     => $id,
            "field_key"     => "license_file",
            "field_value"   => $fileName,
            "note" => $request->notes
        ];

        $this->addData("attachments", $addFileData, 0);

        $whereProviderTempRec = [
            ["id", "=", $tempRecId]
        ];

        $this->deleteData("provider_attachments", $whereProviderTempRec);

        return $this->successResponse(["is_approved" => true], "success");
    }
    /**
     * delete the provider attachments
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function deleteProviderAttachment(Request $request, $id)
    {

        $whereProviderTempRec = [
            ["id", "=", $id]
        ];

        $this->updateData("provider_attachments", $whereProviderTempRec, ["is_delete" => 1]);

        return $this->successResponse(["is_delete" => true], "success");
    }
    /**
     * delete the facility attachments
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function deleteFacilityAttachment(Request $request, $id)
    {

        $whereProviderTempRec = [
            ["id", "=", $id]
        ];

        $this->updateData("facility_attachments", $whereProviderTempRec, ["is_delete" => 1]);

        return $this->successResponse(["is_delete" => true], "success");
    }
    /**
     * delete the practice owner
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function deletePracticeOwner(Request $request, $id)
    {
        $where = [
            ["id", "=", $id]
        ];
        $this->deleteData("user_ddownerinfo", $where);
        return $this->successResponse(["is_delete" => true], "success");
    }
    /**
     * get the name of the countries
     *
     *
     */
    function addCountries()
    {
        $url = "https://restcountries.com/v3.1/all";
        $response = Http::withHeaders([
            "Accept" => 'application/json'
        ])
            ->get($url);
        // Check if the request was successful
        if ($response->successful()) {
            // Get the response body as JSON
            $data = $response->json();
            $dataArr = $this->stdToArray($data);
            // Use the data as needed
            //dd($data);
            // $this->printR($dataArr,true);
            if (count($dataArr) > 0) {
                foreach ($dataArr as $country) {
                    $countryName = $country["name"]["common"];
                    $addData = [];
                    $addData["name"] = $countryName;
                    $addData["created_at"] = $this->timeStamp();
                    // $this->printR($addData,true);
                    // $this->addData("citizenships", $addData, 0);
                }
                echo "Done";
            }
        } else {
            // Handle the error
            $errorMessage = $response->status() . ' - ' . $response->body();
            dd($errorMessage);
        }
    }
    /**
     * make the facility names uppercase
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function makeFacilityNamesUpperCase(Request $request)
    {
        $key = $this->key;
        // DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') as facility_name")
        $facilities = DB::table('user_ddpracticelocationinfo')
            ->select('id', DB::raw("AES_DECRYPT(practice_name, '$key') as facility_name"))
            ->get();
        // $this->printR($facilities, true);
        if ($facilities->count()) {
            foreach ($facilities as $facility) {
                $facilityName = $facility->facility_name;
                $facilityName = strtoupper($facilityName);
                $where = [
                    ["id", "=", $facility->id]
                ];
                $updateData = [
                    "practice_name" => DB::raw("AES_ENCRYPT('" .    $facilityName     . "', '$key')")
                ];
                DB::table('user_ddpracticelocationinfo')
                    ->where($where)
                    ->update($updateData);
                // $this->updateData("user_ddpracticelocationinfo", $where, $updateData);
            }
        }
        echo "Done........!" . $facilities->count();
    }
    /**
     * check the owner already exists
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function chkOwnerExists(Request $request)
    {

        $request->validate([
            "owner_name" => "required",
            "owner_dob"  => "required"
        ]);

        $key = $this->key;

        $ssn = $request->owner_ssn;

        $practiceId = $request->practice_id;

        $ownerName = $request->owner_name;

        $ownerDob = $request->owner_dob;

        if ($request->has('owner_ssn')) {

            $owner = DB::table('user_ddownerinfo')
                ->select(
                    "user_ddownerinfo.name as name_of_owner",
                    "user_ddownerinfo.ownership_percentage",
                    "user_ddownerinfo.num_of_owners",
                    "user_ddownerinfo.pob as place_of_birth",
                    DB::raw("DATE_FORMAT(AES_DECRYPT(cm_user_ddownerinfo.dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.dob,'$key') as date_of_birth"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"),
                    "user_ddownerinfo.city",
                    "user_ddownerinfo.state",
                    "user_ddownerinfo.zip_five",
                    "user_ddownerinfo.zip_four",
                    "user_ddownerinfo.country",
                    "user_ddownerinfo.county",
                    DB::raw("cm_user_ddownerinfo.effective_date as date_of_ownership"),
                    DB::raw("DATE_FORMAT(cm_user_ddownerinfo.effective_date,'%m/%d/%Y') as format_date_of_ownership"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.address,'$key') as street_address"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.phone,'$key') as phone_number"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.fax,'$key') as fax_number"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.email,'$key') as email"),
                    "user_ddownerinfo.id",
                    "user_ddownerinfo.profile_complete_percentage"
                )
                ->whereRaw("AES_DECRYPT(ssn, '$key') = '$ssn'")

                ->first();
            // $this->printR($owner,true);
            if (is_object($owner)) {
                $profileFound = DB::table('users')
                    ->whereRaw("AES_DECRYPT(ssn, '$key') = '$ssn'")
                    ->first(["id"]);
                $ownerId = $owner->id;
                if (is_object($profileFound)) {
                    DB::table('user_ddownerinfo')
                        ->where("id", "=", $ownerId)
                        ->update(["user_id" => $profileFound->id]);
                }
                if (
                    DB::table('owners_map')->where('owner_id', '=', $ownerId)
                    ->where('practice_id', '=', $practiceId)
                    ->count() == 0
                ) {
                    DB::table('owners_map')
                        ->insertGetId([
                            "owner_id" => $ownerId,
                            "practice_id" => $practiceId
                        ]);
                }
            } else {
                $ownerId = DB::table('user_ddownerinfo')
                    ->insertGetId([
                        "name" => $ownerName,
                        "dob" => DB::raw("AES_ENCRYPT('" .    $ownerDob     . "', '$key')"),
                        "ssn" => DB::raw("AES_ENCRYPT('" .    $ssn     . "', '$key')")
                    ]);
                $profileFound = DB::table('users')
                    ->whereRaw("AES_DECRYPT(ssn, '$key') = '$ssn'")
                    ->first(["id"]);
                // $ownerId = $owner->id;
                if (is_object($profileFound)) {
                    DB::table('user_ddownerinfo')
                        ->where("id", "=", $ownerId)
                        ->update(["user_id" => $profileFound->id]);
                }
                // DB::table('owners_map')
                // ->insertGetId([
                //     "owner_id" => $ownerId,
                //     "practice_id" => $practiceId
                // ]);
                if (
                    DB::table('owners_map')->where('owner_id', '=', $ownerId)
                    ->where('practice_id', '=', $practiceId)
                    ->count() == 0
                ) {
                    DB::table('owners_map')
                        ->insertGetId([
                            "owner_id" => $ownerId,
                            "practice_id" => $practiceId
                        ]);
                }
                $owner = DB::table('user_ddownerinfo')
                    ->select(
                        "user_ddownerinfo.name as name_of_owner",
                        "user_ddownerinfo.ownership_percentage",
                        "user_ddownerinfo.num_of_owners",
                        "user_ddownerinfo.pob as place_of_birth",
                        DB::raw("DATE_FORMAT(AES_DECRYPT(cm_user_ddownerinfo.dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.dob,'$key') as date_of_birth"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"),
                        "user_ddownerinfo.city",
                        "user_ddownerinfo.state",
                        "user_ddownerinfo.zip_five",
                        "user_ddownerinfo.zip_four",
                        "user_ddownerinfo.country",
                        "user_ddownerinfo.county",
                        DB::raw("cm_user_ddownerinfo.effective_date as date_of_ownership"),
                        DB::raw("DATE_FORMAT(cm_user_ddownerinfo.effective_date,'%m/%d/%Y') as format_date_of_ownership"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.address,'$key') as street_address"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.phone,'$key') as phone_number"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.fax,'$key') as fax_number"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.email,'$key') as email"),
                        "user_ddownerinfo.id",
                        "user_ddownerinfo.profile_complete_percentage"
                    )
                    ->where('id', '=', $ownerId)

                    ->first();
            }
        }
        if (!$request->has('owner_ssn') && ($request->has('owner_name') && $request->has('owner_dob'))) {
            $owner = DB::table('user_ddownerinfo')
                ->select(
                    "user_ddownerinfo.name as name_of_owner",
                    "user_ddownerinfo.ownership_percentage",
                    "user_ddownerinfo.num_of_owners",
                    "user_ddownerinfo.pob as place_of_birth",
                    DB::raw("DATE_FORMAT(AES_DECRYPT(cm_user_ddownerinfo.dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.dob,'$key') as date_of_birth"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"),
                    "user_ddownerinfo.city",
                    "user_ddownerinfo.state",
                    "user_ddownerinfo.zip_five",
                    "user_ddownerinfo.zip_four",
                    "user_ddownerinfo.country",
                    "user_ddownerinfo.county",
                    DB::raw("cm_user_ddownerinfo.effective_date as date_of_ownership"),
                    DB::raw("DATE_FORMAT(cm_user_ddownerinfo.effective_date,'%m/%d/%Y') as format_date_of_ownership"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.address,'$key') as street_address"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.phone,'$key') as phone_number"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.fax,'$key') as fax_number"),
                    DB::raw("AES_DECRYPT(cm_user_ddownerinfo.email,'$key') as email"),
                    "user_ddownerinfo.id",
                    "user_ddownerinfo.profile_complete_percentage"
                )
                ->whereRaw("AES_DECRYPT(dob, '$key') = '$ownerDob'")
                ->where("name", "=", $ownerName)
                ->first();

            if (is_object($owner)) {

                $profileFound = DB::table('users')
                    ->whereRaw("CONCAT(first_name,' ',last_name = '$ownerName')")
                    ->first(["id"]);
                $ownerId = $owner->id;
                if (is_object($profileFound)) {
                    DB::table('user_ddownerinfo')
                        ->where("id", "=", $ownerId)
                        ->update(["user_id" => $profileFound->id]);
                }

                // $ownerId = $owner->id;
                // DB::table('owners_map')
                // ->insertGetId([
                //     "owner_id" => $ownerId,
                //     "practice_id" => $practiceId
                // ]);
                if (
                    DB::table('owners_map')->where('owner_id', '=', $ownerId)
                    ->where('practice_id', '=', $practiceId)
                    ->count() == 0
                ) {
                    DB::table('owners_map')
                        ->insertGetId([
                            "owner_id" => $ownerId,
                            "practice_id" => $practiceId
                        ]);
                }
            } else {
                $ownerId = DB::table('user_ddownerinfo')
                    ->insertGetId([
                        "name" => $ownerName,
                        "dob" => DB::raw("AES_ENCRYPT('" .    $ownerDob     . "', '$key')"),
                        //"ssn" => DB::raw("AES_ENCRYPT('" .    $ssn     . "', '$key')")
                    ]);
                $profileFound = DB::table('users')
                    ->whereRaw("CONCAT(first_name,' ',last_name = '$ownerName')")
                    ->first(["id"]);
                // $ownerId = $owner->id;
                if (is_object($profileFound)) {
                    DB::table('user_ddownerinfo')
                        ->where("id", "=", $ownerId)
                        ->update(["user_id" => $profileFound->id]);
                }
                // DB::table('owners_map')
                // ->insertGetId([
                //     "owner_id" => $ownerId,
                //     "practice_id" => $practiceId
                // ]);
                if (
                    DB::table('owners_map')->where('owner_id', '=', $ownerId)
                    ->where('practice_id', '=', $practiceId)
                    ->count() == 0
                ) {
                    DB::table('owners_map')
                        ->insertGetId([
                            "owner_id" => $ownerId,
                            "practice_id" => $practiceId
                        ]);
                }
                $owner = DB::table('user_ddownerinfo')
                    ->select(
                        "user_ddownerinfo.name as name_of_owner",
                        "user_ddownerinfo.ownership_percentage",
                        "user_ddownerinfo.num_of_owners",
                        "user_ddownerinfo.pob as place_of_birth",
                        DB::raw("DATE_FORMAT(AES_DECRYPT(cm_user_ddownerinfo.dob,'$key'),'%m/%d/%Y') as format_date_of_birth"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.dob,'$key') as date_of_birth"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"),
                        "user_ddownerinfo.city",
                        "user_ddownerinfo.state",
                        "user_ddownerinfo.zip_five",
                        "user_ddownerinfo.zip_four",
                        "user_ddownerinfo.country",
                        "user_ddownerinfo.county",
                        DB::raw("cm_user_ddownerinfo.effective_date as date_of_ownership"),
                        DB::raw("DATE_FORMAT(cm_user_ddownerinfo.effective_date,'%m/%d/%Y') as format_date_of_ownership"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.address,'$key') as street_address"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.phone,'$key') as phone_number"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.fax,'$key') as fax_number"),
                        DB::raw("AES_DECRYPT(cm_user_ddownerinfo.email,'$key') as email"),
                        "user_ddownerinfo.id",
                        "user_ddownerinfo.profile_complete_percentage"
                    )
                    ->where('id', '=', $ownerId)

                    ->first();
            }
        }
        return $this->successResponse(["owner" => $owner], "success");
    }
    /**
     * fetch the owner affliation
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchOwnerAffiliations(Request $request)
    {
        $request->validate([
            'owner_id' => 'required'
        ]); //

        $ownerId = $request->input('owner_id');

        $ownerAffliations = DB::table('owners_map')

            ->select("user_ddownerinfo.name as owner_name", "user_baf_practiseinfo.practice_name", "owners_map.practice_id", "owners_map.owner_id", "owners_map.percentage")

            ->join('user_ddownerinfo', 'user_ddownerinfo.id', '=', 'owners_map.owner_id')

            ->join('user_baf_practiseinfo', 'user_baf_practiseinfo.user_id', '=', 'owners_map.practice_id')

            ->where('owners_map.owner_id', '=', $ownerId)

            ->get();

        return $this->successResponse(["owner_affliations" => $ownerAffliations], "success");
    }
    /**
     * move the owner information from current table to owners map
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function moveOwnerInformation(Request $request)
    {

        $key = $this->key;

        $owners = DB::table('user_ddownerinfo')

            ->select(DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"), 'id', 'ownership_percentage', 'effective_date', 'parent_user_id')
            ->whereRaw('name IS NOT NULL')
            ->get();
        // $this->printR($owners,true);

        if (count($owners) > 0) {
            foreach ($owners as $owner) {
                $isSameOwner = false;
                $sameOwnerId = null;
                $percentage = $owner->ownership_percentage;
                $dateOfOwnership = $owner->effective_date;
                $ownerId = $owner->id;
                $practiceId = $owner->parent_user_id;

                $ssn = $owner->ssn_number;

                $ownerMap = DB::table('owners_map')

                    ->get();

                if (count($ownerMap) > 0) {
                    foreach ($ownerMap as $ownerEachMap) {
                        $mapOwnerId = $ownerEachMap->owner_id;
                        $owners_ = DB::table('user_ddownerinfo')

                            ->select(DB::raw("AES_DECRYPT(cm_user_ddownerinfo.ssn,'$key') as ssn_number"), 'id', 'ownership_percentage', 'effective_date', 'parent_user_id')
                            ->where('id', '=', $mapOwnerId)
                            ->first();
                        if ($owners_->ssn_number == $ssn) {
                            $isSameOwner = true;
                            $sameOwnerId = $mapOwnerId;
                            break;
                        }
                    }
                    if ($isSameOwner == true && $sameOwnerId) {
                        DB::table('owners_map')
                            ->insertGetId([
                                "owner_id" => $sameOwnerId, "practice_id" => $practiceId,
                                "percentage" => $percentage, "date_of_ownership" => $dateOfOwnership,
                                "created_at" => $this->timeStamp()
                            ]);
                    } else {
                        DB::table('owners_map')
                            ->insertGetId([
                                "owner_id" => $ownerId, "practice_id" => $practiceId,
                                "percentage" => $percentage, "date_of_ownership" => $dateOfOwnership,
                                "created_at" => $this->timeStamp()
                            ]);
                    }
                } else {
                    DB::table('owners_map')
                        ->insertGetId([
                            "owner_id" => $ownerId, "practice_id" => $practiceId,
                            "percentage" => $percentage, "date_of_ownership" => $dateOfOwnership,
                            "created_at" => $this->timeStamp()
                        ]);
                }
            }
        }

        return $this->successResponse(["res" => "done"], "success");
    }
    /**
     * fetch the facility info
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function facilityInfo(Request $request)
    {
        $request->validate([
            "facility_ids" => "required",
        ]);

        $key = $this->key;
        $facilityIds = json_decode($request->facility_ids, true);

        $facilities = DB::table("user_ddpracticelocationinfo")
            ->select(
                DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"),
                DB::raw("AES_DECRYPT(phone,'$key') as phone_number"),
                DB::raw("AES_DECRYPT(fax,'$key') as fax_number"),
                DB::raw("AES_DECRYPT(email,'$key') as email"),
                'zip_five',
                'zip_four',
                DB::raw("AES_DECRYPT(practise_address,'$key') as street_address"),
                'country',
                'city',
                'state_code',
                'state',
                'county',
                'contact_name',
                'contact_title',
                DB::raw("AES_DECRYPT(contact_phone,'$key') as contact_phone"),
                DB::raw("AES_DECRYPT(contact_fax,'$key') as contact_fax"),
                DB::raw("AES_DECRYPT(contact_email,'$key') as contact_email"),

            )
            ->whereIn("user_id", $facilityIds)
            ->get();

        $facilitiesRes = [];
        if (count($facilities)) {
            foreach ($facilities as $facility) {
                $facilityRes = [
                    "facility_name"         => $facility->facility_name,
                    "new_facility_address"  => [
                        "zip_five"          => $facility->zip_five,
                        "zip_four"          => $facility->zip_four,
                        "street_address"    => $facility->street_address,
                        "country"           => $facility->country,
                        "city"              => $facility->city,
                        "state"             => $facility->state,
                        "state_code"        => $facility->state_code,
                        "county"            => $facility->county,
                        "phone_number"      => $facility->phone_number,
                        "fax_number"        => $facility->fax_number,
                        "email"             => $facility->email,
                    ],
                    "facility_contact_information" => [
                        "name"          => $facility->contact_name,
                        "title"         => $facility->contact_title,
                        "phone_number"  => $facility->contact_phone,
                        "fax_number"    => $facility->contact_fax,
                        "email"         => $facility->contact_email,
                    ]
                ];

                array_push($facilitiesRes, $facilityRes);
            }
        }
        return $this->successResponse(["facilities" => $facilitiesRes], "success");
    }
    /**
     * fetch the provider info
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function providerInfo(Request $request)
    {

        $request->validate([
            "provider_ids" => "required",
        ]);

        $key = $this->key;

        $providerIds = json_decode($request->provider_ids, true);
        // $this->printR($providerIds,true);
        $providers = DB::table("users")
            ->select(
                'users.first_name as provider_first_name',
                'users.last_name as provider_last_name',
                'users.place_of_birth',
                'users.gender',
                'users.supervisor_physician',
                DB::raw("AES_DECRYPT(cm_users.facility_npi,'$key') as provider_npi_number"),
                DB::raw("AES_DECRYPT(cm_users.dob,'$key') as date_of_birth"),
                DB::raw("AES_DECRYPT(cm_users.ssn,'$key') as ssn_number"),
                'citizenships.name as citizenship'
            )
            ->join('citizenships', 'citizenships.id', '=', 'users.citizenship_id', 'left')
            ->whereIn("users.id", $providerIds)
            ->get();
        $providerRes = [];
        if (count($providers)) {
            foreach ($providers as $provider) {
                $providerNew = ["basic_information" => $provider];
                array_push($providerRes, $providerNew);
            }
        }
        return $this->successResponse(["providers" => $providerRes], "success");
    }
    /**
     * add the log of practice
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addPracticeLog(Request $request)
    {

        $request->validate([
            "practice_id"       => "required",
            "user_id"           => "required",
            "section"           => "required",
            "log"               => "required",
            "action"            => "required"
        ]);

        $practiceId     = $request->practice_id;
        $sessionUserId  = $request->user_id;
        $section        = $request->section;

        $loiBody        = $request->loi_body;

        $loiFooter        = $request->loi_footer;

        $log            = $request->log;

        // $log         =   json_decode($log,true);

        // $log            = json_encode($log);
        $action         = $request->action;

        $key            = $this->key;
        $addLog = [
            "practice_id"       => $practiceId,
            "session_userid"    => $sessionUserId,
            "section"           => $section,
            "log"               => DB::raw("AES_ENCRYPT('" .    $log     . "', '$key')"),
            "loi_body"          => DB::raw("AES_ENCRYPT('" .    $loiBody     . "', '$key')"),
            "loi_footer"        => DB::raw("AES_ENCRYPT('" .    $loiFooter     . "', '$key')"),
            "created_at"        => $this->timeStamp(),
            "action"            => $action
        ];

        $logRes = DB::table("practice_logs")

            ->insertGetId($addLog);

        return $this->successResponse(["log" => $logRes], "success");
    }
    /**
     * fetch the practice log
     *
     * @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function fetchPracticeLOILog(Request $request)
    {

        $request->validate([
            "practice_id" => "required",
        ]);

        $practiceId = $request->practice_id;

        $key        = $this->key;

        $logs = DB::table("practice_logs")

            ->select(
                DB::raw("AES_DECRYPT(cm_practice_logs.log,'$key') as logs"),
                DB::raw("AES_DECRYPT(cm_practice_logs.loi_body,'$key') as loi_body"),
                DB::raw("AES_DECRYPT(cm_practice_logs.loi_footer,'$key') as loi_footer"),
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as session_user"),
                "practice_logs.section",
                "practice_logs.action",
                "practice_logs.created_at",
                DB::raw("AES_DECRYPT(cm_practice_logs.practice_profile_logs,'$key') as practice_profile_logs"),

            )

            ->join("users", "users.id", "=", "practice_logs.session_userid")

            ->where("practice_logs.practice_id", "=", $practiceId)

            ->whereNull("practice_logs.practice_profile_logs")

            ->orderBy("practice_logs.created_at", "DESC")

            ->paginate($this->cmperPage);

        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                //$log->logs = json_decode(json_encode($log->logs),true);
                // $this->printR($log->logs,true);
                $log->human_readable = $this->humanReadableTimeDifference($log->created_at);
                $log->am_pm         = $this->fetchAMPMFromTS($log->created_at);
                $log->am_pm         = $this->fetchAMPMFromTS($log->created_at);
            }
        }
        return $this->successResponse(["result" => $logs], "success");
    }
    /**
     * fetch the practice log
     *
     * @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function fetchPracticeProfileLog(Request $request)
    {

        $request->validate([
            "practice_id" => "required",
        ]);

        $practiceId = $request->practice_id;

        $key        = $this->key;

        $logs = DB::table("practice_logs")

            ->select(
                DB::raw("AES_DECRYPT(cm_practice_logs.log,'$key') as logs"),
                DB::raw("AES_DECRYPT(cm_practice_logs.loi_body,'$key') as loi_body"),
                DB::raw("AES_DECRYPT(cm_practice_logs.loi_footer,'$key') as loi_footer"),
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as session_user"),
                "practice_logs.section",
                "practice_logs.action",
                "practice_logs.created_at",
                DB::raw("AES_DECRYPT(cm_practice_logs.practice_profile_logs,'$key') as practice_profile_logs"),

            )

            ->join("users", "users.id", "=", "practice_logs.session_userid")

            ->where("practice_logs.practice_id", "=", $practiceId)

            ->whereNull("practice_logs.loi_body")

            ->orderBy("practice_logs.created_at", "DESC")

            ->paginate($this->cmperPage);

        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                //$log->logs = json_decode(json_encode($log->logs),true);
                // $this->printR($log->logs,true);
                $log->human_readable = $this->humanReadableTimeDifference($log->created_at);
                $log->am_pm         = $this->fetchAMPMFromTS($log->created_at);
            }
        }
        return $this->successResponse(["result" => $logs], "success");
    }
    /**
     * fetch the facility log
     *
     * @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function fetchFacilityProfileLog(Request $request)
    {

        $request->validate([
            "facility_id" => "required",
        ]);

        $facilityId = $request->facility_id;

        $key        = $this->key;

        $logs = DB::table("facility_logs")

            ->select(
                DB::raw("AES_DECRYPT(cm_facility_logs.log,'$key') as logs"),
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as session_user"),
                "facility_logs.section",
                "facility_logs.action",
                "facility_logs.created_at"

            )

            ->join("users", "users.id", "=", "facility_logs.session_userid")

            ->where("facility_logs.facility_id", "=", $facilityId)

            ->orderBy("facility_logs.created_at", "DESC")

            ->paginate($this->cmperPage);

        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                //$log->logs = json_decode(json_encode($log->logs),true);
                // $this->printR($log->logs,true);
                $log->human_readable = $this->humanReadableTimeDifference($log->created_at);
                $log->am_pm         = $this->fetchAMPMFromTS($log->created_at);
            }
        }
        return $this->successResponse(["result" => $logs], "success");
    }
    /**
     * fetch the provider log
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchProviderProfileLog(Request $request)
    {

        $request->validate([
            "provider_id" => "required",
        ]);

        $providerId = $request->provider_id;

        $key        = $this->key;

        $logs = DB::table("provider_logs")

            ->select(
                DB::raw("AES_DECRYPT(cm_provider_logs.log,'$key') as logs"),
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as session_user"),
                "provider_logs.section",
                "provider_logs.action",
                "provider_logs.created_at"

            )

            ->join("users", "users.id", "=", "provider_logs.session_userid")

            ->where("provider_logs.provider_id", "=", $providerId)

            ->orderBy("provider_logs.created_at", "DESC")

            ->paginate($this->cmperPage);

        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                //$log->logs = json_decode(json_encode($log->logs),true);
                // $this->printR($log->logs,true);
                $log->human_readable = $this->humanReadableTimeDifference($log->created_at);
                $log->am_pm         = $this->fetchAMPMFromTS($log->created_at);
            }
        }
        return $this->successResponse(["result" => $logs], "success");
    }
    /**
     * fetch the provider share form logs
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchProviderSharedFormLogs(Request $request)
    {

        $request->validate([
            "provider_id" => "required"
        ]);

        $providerId = $request->provider_id;

        $key = $this->key;

        $logs = DB::table('provider_formshare_logs')

            ->select(
                DB::raw("AES_DECRYPT(cm_provider_formshare_logs.log,'$key') as logs"),
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as session_user"),
                DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ', COALESCE(cm_u.last_name,'')) as provider_name"),
                "provider_formshare_logs.created_at"
            )
            ->join("users", "users.id", "=", "provider_formshare_logs.session_userid")

            ->join("shared_provider_form", "shared_provider_form.id", "=", "provider_formshare_logs.share_id")

            ->join("users as u", "u.id", "=", "shared_provider_form.provider_id")

            ->where("shared_provider_form.provider_id", "=", $providerId)

            ->orderBy("provider_formshare_logs.created_at", "DESC")

            ->paginate($this->cmperPage);

        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                //$log->logs = json_decode(json_encode($log->logs),true);
                // $this->printR($log->logs,true);
                $log->human_readable = $this->humanReadableTimeDifference($log->created_at);
                $log->am_pm         = $this->fetchAMPMFromTS($log->created_at);
            }
        }
        return $this->successResponse(["result" => $logs], "success");
    }
    /**
     * fetch the facility share form logs
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchFacilitySharedFormLogs(Request $request)
    {

        $request->validate([
            "facility_id" => "required"
        ]);

        $key = $this->key;

        $facilityId = $request->facility_id;

        $logs = DB::table('facility_formshare_logs')

            ->select(
                DB::raw("AES_DECRYPT(cm_facility_formshare_logs.log,'$key') as logs"),
                DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as session_user"),
                DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name"),
                "facility_formshare_logs.created_at"
            )
            ->join("users", "users.id", "=", "facility_formshare_logs.session_userid")

            ->join("shared_facility_form", "shared_facility_form.id", "=", "facility_formshare_logs.share_id")

            ->join("user_ddpracticelocationinfo as pli", "pli.user_id", "=", "shared_facility_form.facility_id")

            ->where("shared_facility_form.facility_id", "=", $facilityId)

            ->orderBy("facility_formshare_logs.created_at", "DESC")

            ->paginate($this->cmperPage);

        if ($logs->count() > 0) {
            foreach ($logs as $log) {
                //$log->logs = json_decode(json_encode($log->logs),true);
                // $this->printR($log->logs,true);
                $log->human_readable = $this->humanReadableTimeDifference($log->created_at);
                $log->am_pm         = $this->fetchAMPMFromTS($log->created_at);
            }
        }
        return $this->successResponse(["result" => $logs], "success");
    }
    /**
     * upload the directory profile image
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadDirectoryProfileImage(Request $request)
    {
        $request->validate([
            'user_id'   => "required"
        ]);
        $dateUpdate = [];
        $userId = $request->user_id;
        $type = $request->type;
        if ($request->hasFile("image")) {
            $image = $request->file("image");


            $imageSettings = $request->has('image_settings') ? $request->image_settings : null;

            $imageName = $userId . "_" . $type . "_eca_profile" . '.' . $image->getClientOriginalExtension();
            if ($type == "practice")
                $this->updateData("user_baf_practiseinfo", ["user_id" => $userId], ["profile_image" => $imageName, 'image_settings' => $imageSettings]);
            elseif ($type == "facility")
                $this->updateData("user_ddpracticelocationinfo", ["user_id" => $userId], ["profile_image" => $imageName, 'image_settings' => $imageSettings]);
            else
                $this->updateData("users", ["id" => $userId], ["profile_image" => $imageName, 'image_settings' => $imageSettings]);

            $this->uploadMyFile($imageName, $image, "eCA/profile");
            $dateUpdate["is_updated"] = false;
            $dateUpdate["image_name"] = $imageName;
        } else {
            $imageSettings = $request->has('image_settings') ? $request->image_settings : null;
            if (isset($imageSettings)) {
                if ($type == "practice")
                    $this->updateData("user_baf_practiseinfo", ["user_id" => $userId], ['image_settings' => $imageSettings]);
                elseif ($type == "facility")
                    $this->updateData("user_ddpracticelocationinfo", ["user_id" => $userId], ['image_settings' => $imageSettings]);
                else
                    $this->updateData("users", ["id" => $userId], ['image_settings' => $imageSettings]);

                $dateUpdate["is_updated"] = true;
            }
        }

        return $this->successResponse($dateUpdate, "success");
    }
    /**
     * upload the owner profile image
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadOwnerProfileImage(Request $request)
    {

        $request->validate([
            'owner_id'   => "required"
        ]);

        $dateUpdate = [];

        $ownerId = $request->owner_id;

        if ($request->hasFile("image")) {

            $image = $request->file("image");

            $imageName = $ownerId . "_eca_owner_profile" . '.' . $image->getClientOriginalExtension();

            $this->updateData("user_ddownerinfo", ["id" => $ownerId], ["profile_image" => $imageName]);

            $this->uploadMyFile($imageName, $image, "eCA/profile");

            $dateUpdate["is_updated"] = false;

            $dateUpdate["image_name"] = $imageName;
        } else {

            $imageSettings = $request->has('image_settings') ? $request->image_settings : null;

            if (isset($imageSettings)) {

                $this->updateData("user_ddownerinfo", ["id" => $ownerId], ['image_settings' => $imageSettings]);

                $dateUpdate["is_updated"] = true;
            }
        }

        return $this->successResponse($dateUpdate, "success");
    }
    /**
     * fetch practice users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchPracticeUsers(Request $request)
    {

        $request->validate([
            "practice_id" => "required"
        ]);

        $key = $this->key;
        $practiceId = $request->practice_id;
        $perPage = $request->has("per_page") ? $request->per_page : $this->cmperPage;

        $facilities = DB::table("user_ddpracticelocationinfo")
            ->select(
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') as facility_name"),
                "user_ddpracticelocationinfo.user_id as facility_id",
                "users.is_complete",
                "users.profile_complete_percentage",
                "users.status"
            )
            ->join("users", "users.id", "=", "user_ddpracticelocationinfo.user_id")
            ->where("user_ddpracticelocationinfo.user_parent_id", "=", $practiceId)
            ->get();

        $facilityArray = $facilities->toArray();
        $allFacilityId = array_column($facilityArray, 'facility_id');

        $providerUser = ProviderLocationMap::select("u.id", DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ',COALESCE(cm_u.last_name,'')) AS name"), DB::raw(" 'provider_user' AS user_type"), DB::raw(' IF(select_all_facility = 1, "true", "false") AS selectAllFacilities'))
            ->join('users AS u', 'u.id', '=', 'user_id')
            ->whereIn('location_user_id', $allFacilityId)
            ->groupBy('user_id');
        if ($request->has('search')) {
            $providerUser = $providerUser->where(DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ',COALESCE(cm_u.last_name,''))"), 'like', '%' . $request->search . '%');
        }


        $sysUsers = DB::table('emp_location_map AS elp')
            ->join('users AS u', 'u.id', '=', 'elp.emp_id')
            ->select("u.id", DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ',COALESCE(cm_u.last_name,'')) AS name"), DB::raw(" 'system_user' AS user_type"), DB::raw(' IF(select_all_facility = 1, "true", "false") AS selectAllFacilities'))
            ->where('elp.location_user_id', '!=', 0)
            ->where('u.deleted', 0)
            // ->whereIn('location_user_id', $allFacilityId)
            ->groupBy('elp.emp_id');
        if ($request->has('search')) {
            $sysUsers = $sysUsers->where(DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ',COALESCE(cm_u.last_name,''))"), 'like', '%' . $request->search . '%');
        }



        $mergedUser = $providerUser->union($sysUsers)->orderBy('user_type')->orderBy('name');
        $UsersIds = array_column($mergedUser->get()->toArray(), 'id');

        $allUsersMerged = $mergedUser->get()->toarray();
        $allUsers = $mergedUser->paginate($perPage);

        $providerUserFacility = DB::table("individualprovider_location_map")
            ->whereIn('location_user_id', $allFacilityId)
            ->whereIn('user_id', $UsersIds)
            ->get();

        $systemUserFacility = DB::table("emp_location_map")
            ->whereIn('location_user_id', $allFacilityId)
            ->whereIn('emp_id', $UsersIds)
            ->get();

        $providerUserFacilityArr = [];
        foreach ($providerUserFacility as $providerUserFacilities) {
            $providerUserFacilityArr[$providerUserFacilities->location_user_id][] = $providerUserFacilities->user_id;
        }

        $sytemUserFacilityArr = [];
        foreach ($systemUserFacility as $systemUserFacilities) {
            $sytemUserFacilityArr[$systemUserFacilities->location_user_id][] = $systemUserFacilities->emp_id;
        }
        $counterProviderUser = 0;
        $countersystemUser = 0;
        if ($allUsers->count() > 0) {
            foreach ($allUsers as $user) {
                $userId = $user->id;
                $eachUserFacility = [];
                $selectAllFacilities = true;
                if ($user->user_type == "provider_user") {
                    $counterProviderUser = $counterProviderUser + 1;
                    foreach ($facilities as $facility) {
                        $facilityId = $facility->facility_id;
                        $status = false;
                        if (isset($providerUserFacilityArr[$facility->facility_id])) {
                            $searchUser = array_search($userId, $providerUserFacilityArr[$facility->facility_id]);
                            if ($searchUser !== null && $searchUser !== false) {
                                $status = true;
                            }
                            if ($status == false) {
                                $selectAllFacilities = false;
                            }
                        }
                        $facility = ["facility_name" => $facility->facility_name, "facility_id" => $facilityId, "active" => $status];
                        array_push($eachUserFacility, $facility);
                    }
                }
                if ($user->user_type == "system_user") {
                    $countersystemUser = $countersystemUser + 1;
                    foreach ($facilities as $facility) {
                        $facilityId = $facility->facility_id;
                        $status = false;
                        if (isset($sytemUserFacilityArr[$facility->facility_id])) {
                            $searchUser = array_search($userId, $sytemUserFacilityArr[$facility->facility_id]);
                            if ($searchUser !== null && $searchUser !== false) {
                                $status = true;
                            }
                            if ($status == false) {
                                $selectAllFacilities = false;
                            }
                        }
                        $facility = ["facility_name" => $facility->facility_name, "facility_id" => $facilityId, "active" => $status];
                        array_push($eachUserFacility, $facility);
                    }
                }
                $user->selectAllFacilities = $user['selectAllFacilities'] == 'true' ? true : false;
                $user->facilities = $eachUserFacility;
            }
        }

        $counterProviderUser = 0;
        $countersystemUser = 0;
        foreach ($allUsersMerged as $allUsersMerge) {
            if ($allUsersMerge['user_type'] == 'provider_user') {
                $counterProviderUser = $counterProviderUser + 1;
            } else {
                $countersystemUser = $countersystemUser + 1;
            }
        }
        $allUsersWithExtraData = $allUsers->toArray(); // Convert the paginated collection to an array
        $allUsersWithExtraData['total_no_of_system_users'] = $countersystemUser;
        $allUsersWithExtraData['total_no_of_provider_users'] = $counterProviderUser;

        return $this->successResponse($allUsersWithExtraData, "success");
    }
    /**
     * Update practice users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePracticeUser(Request $request)
    {

        // dd($request->all());
        foreach ($request->users as $user) {
            foreach ($user['facilities'] as $facility) {
                if ($user['user_type'] == "system_user") {
                    if ($facility['active']) {
                        EmpLocationMap::firstOrCreate(['emp_id' => $user['id'], 'location_user_id' => $facility['facility_id']]);
                    } else {
                        EmpLocationMap::where('emp_id', $user['id'])->where('location_user_id', $facility['facility_id'])->delete();
                    }
                } else if ($user['user_type'] == "provider_user") {
                    if ($facility['active']) {
                        ProviderLocationMap::firstOrCreate(['user_id' => $user['id'], 'location_user_id' => $facility['facility_id']]);
                    } else {
                        ProviderLocationMap::where('user_id', $user['id'])->where('location_user_id', $facility['facility_id'])->delete();
                    }
                }
            }
            $userData = User::find($user['id']);
            $userData->select_all_facility = $user['selectAllFacilities'];
            $userData->save();
            // ->update(["select_all_facility" => 1]);
        }

        return $this->successResponse(['is_update' => true], "success");
    }

    /**
     * store Practice ServiceType Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storePracticeServiceTypeDropdowns(Request $request)
    {
        $request->validate([
            'name' => 'required',
            "session_userid" => "required"
        ]);
        PracticeServiceTypeDropdown::create([
            'name' => $request->name,
            'created_by' => $request->session_userid
        ]);
        return $this->successResponse([], "success");
    }

    /**
     * update Practice ServiceType Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function updatePracticeServiceTypeDropdowns($id, Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $serviceType = PracticeServiceTypeDropdown::find($id);
        if ($serviceType == null) {
            return $this->errorResponse([], "No Service Type found", 404);
        }
        $serviceType->name = $request->name;
        $serviceType->save();
        return $this->successResponse([], "success");
    }

    /**
     * Get getPractice Service Type Dropdowns
     *
     * @return \Illuminate\Http\Response
     */
    public function getPracticeServiceTypeDropdowns(Request $request)
    {

        $defaulPracticeServiceTypes[] = ['id' => 0, 'name' => 'Add New'];
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $serviceType = PracticeServiceTypeDropdown::select('id', 'name')->orderby('created_at', 'DESC');
        if ($request->has('search')) {
            $serviceType = $serviceType->where('name', 'like', '%' . $request->search . '%');;
        }
        $serviceType = $serviceType->paginate($perPage);
        $practiceServiceTypes = [...$defaulPracticeServiceTypes, ...$serviceType];
        return $this->successResponse(['service_type' => $practiceServiceTypes], "success");
    }

    /**
     * Delete Practice ServiceType Dropdown
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function deletePracticeServiceTypeDropdowns($id)
    {

        $serviceType = PracticeServiceTypeDropdown::find($id);
        if ($serviceType == null) {
            return $this->errorResponse([], "No Service Type found", 404);
        }
        $serviceType->delete();
        return $this->successResponse([], "success");
    }
}
