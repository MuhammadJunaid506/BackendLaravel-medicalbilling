<?php

namespace App\Http\Traits;

use DB;
use Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ProviderCompanyMap;
use App\Models\Role;
use App\Mail\ProviderCredentials;
use App\Models\Provider;
use App\Models\License;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

trait Utility
{

    public $providerCols = [
        "providers.*",
        "companies.company_name",
        "companies.company_country",
        "companies.company_logo"
    ];
    public $cmperPage = 20;
    public $perPersonCharges = 99;
    public $usersCols = ["users.id", "users.email", "users.deleted", "users.first_name", "users.last_name", "users.phone", "users.state_of_birth"];
    private $tbl ="user_ddpracticelocationinfo";
    private $tbl2 = "user_dd_businessinformation";
    private $key = "";
    private $tblU = "users";
    public function __construct() {
        $this->key = env("AES_KEY");
    }
    /**
     * convert std to array
     */
    function stdToArray($data)
    {
        return json_decode(json_encode($data), true);
    }
    /**
     * dyanmic server link handle
     */
    function resetPasswordLink()
    {
        if ($_SERVER['SERVER_NAME'] == "127.0.0.1")
            return "http://localhost:3000/new/password";
        else
            return "http://app.eclinicassist.com/new/password";
    }
    /**
     * server dynamic react app url
     */
    function baseURL()
    {
        if ($_SERVER['SERVER_NAME'] == "127.0.0.1")
            return "http://localhost:3000";
        else
            return "http://app.eclinicassist.com";
    }
    /**
     * get the API URL
     */
    function getServerURL()
    {
        if ($_SERVER['SERVER_NAME'] == "127.0.0.1")
            return "http://127.0.0.1";
        else
            return "http://api.eclinicassist.com";
    }
    /**
     * get user data against the email
     *
     * @author Faheem Mahar
     * @param $email
     */
    function userData($email)
    {
        $key = $this->key;
        return DB::table("users")->whereRaw("AES_DECRYPT(email, '$key') = '$email'")->first(DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as name"));
    }

    /**
     * record the logs of each user
     *
     * @author Faheem Mahar <email@email.com>
     * @param $logsData
     */
    function addSystemLogs($logsData)
    {
        return  DB::table("sys_logs")->insertGetId($logsData);
    }
    /**
     * timestamp
     */
    function timeStamp()
    {
        return date("Y-m-d H:i:s");
    }
    /**
     * issue date
     */
    function issueDate()
    {
        return date("Y-m-d");
    }
    /**
     * due date
     */
    function dueDate()
    {
        return  date('Y-m-d', strtotime('+3 day', time()));
    }
    /**
     * generate the invoice number
     */
    function generateInvoiceNumber($invoiceId)
    {

        if (!$invoiceId) return 'CM0001';
        else {
            $string = preg_replace("/[^0-9\.]/", '', $invoiceId);

            return 'CM' . sprintf('%04d', $string + 1);
        }
    }
    /**
     * add the payment failure logs
     */
    function addPaymentLogs($payementData)
    {
        return  DB::table("payment_logs")->insertGetId($payementData);
    }
    /**
     * add the notification into sytem
     *
     * @param $providerId
     * @param $notifyType
     */
    function addNotification($providerId, $notifyType, $detail = NULL, $msg, $heading, $taskId = 0, $logId = 0)
    {
        // $hasRec = DB::table("notifications")->where("provider_id", "=", $providerId)
        //     ->where("notify_type", "=", $notifyType)
        //     ->count();

        // if ($hasRec == 0)
        return DB::table("notifications")->insertGetId([
            "provider_id" => $providerId,
            "notify_type" => $notifyType, "details" => $detail,
            "message" => $msg, "heading" => $heading, "task_id" => $taskId, "log_id" => $logId,
            "created_at" => date("Y-m-d H:i:s")
        ]); //add the notifications
    }
    /**
     * add the data into specific table
     *
     * @param $table
     * @param $data
     * @param $isMultiRows
     */
    function addData($table, $data, $isMultiRows = 0)
    {
        $tablObj = DB::table($table);
        if ($isMultiRows)
            return  $tablObj->insert($data);
        else
            return  $tablObj->insertGetId($data);
    }
    /**
     * delete the data into specific table
     *
     * @param $table
     * @param $data
     * @param $isMultiRows
     */
    function deleteData($table, $where)
    {
        return DB::table($table)->where($where)->delete();
    }
    /**
     * select the data from specific table
     *
     * @param $table
     * @param $where
     * @param $isFirst
     * @param $cols
     */
    function fetchData($table, $where = "", $isFirst = 0, $cols = [])
    {
        $tablObj = DB::table($table);
        if (count($cols))
            $tablObj = $tablObj->select($cols);

        if ($where != "")
            $tablObj = $tablObj->where($where);
        if ($isFirst == 1)
            return $tablObj->first();
        else
            return $tablObj->get();
    }
    /**
     * select the data with order by
     *
     * @param $table
     * @param $where
     * @param $orderType
     * @param $orderColumn
     * @param $cols
     */
    function fetchDataWithOrder($table, $where = "",  $cols = [], $orderType, $orderColumn)
    {
        $tablObj = DB::table($table);
        if (count($cols))
            $tablObj = $tablObj->select($cols);

        if ($where != "")
            $tablObj = $tablObj->where($where);

        return $tablObj

            ->orderBy($orderColumn, $orderType)

            ->get();
    }
    /**
     * select the data from specific table
     *
     * @param $table
     * @param $where
     * @param $isFirst
     * @param $cols
     */
    function fetchAfflietedContacts($parentId, $type = "", $locationId = 0, $search = "")
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;
        $tblU = "cm_".$this->tblU;

        $sql = "SELECT ilm.location_user_id, ilm.user_id, AES_DECRYPT(pli.practice_name,'$key') as practice_name, u.first_name, u.last_name , AES_DECRYPT(u.facility_npi,'$key') as facility_npi,ilm.for_credentialing,ilm.id
        FROM `cm_individualprovider_location_map` ilm
        INNER JOIN `$tbl` pli
        ON pli.user_id = ilm.location_user_id
        INNER JOIN `$tblU` u
        ON u.id = ilm.user_id
        WHERE ilm.location_user_id = '$parentId'";
        if ($search != '') {
            $sql .= " AND (CONCAT(u.first_name, ' ',u.last_name) LIKE '%$search%' OR AES_DECRYPT(u.facility_npi,'$key') LIKE '%$search%') ";
        }
        $sql .= "ORDER BY u.supervisor_physician DESC, ilm.for_credentialing DESC";

        return $this->rawQuery($sql);

    }
    /**
     * fetch practice afflieted contacts
     *
     * @param $practiceId
     *
     */
    function fetchPracticeAfflietedContacts($practiceId, $search = "")
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;
        $tblU = "cm_".$this->tblU;

        $sql = "SELECT plm.parent_user_id as location_user_id, plm.user_id, AES_DECRYPT(pli.practice_name,'$key') as practice_name, u.first_name,u.last_name, AES_DECRYPT(u.facility_npi,'$key') AS facility_npi
        FROM `cm_user_dd_individualproviderinfo` plm
        INNER JOIN `$tbl` pli
        ON pli.user_id = plm.parent_user_id
        INNER JOIN `$tblU` u
        ON u.id = plm.user_id
        WHERE plm.parent_user_id = '$practiceId'";
        if ($search != '') {
            $sql .= " AND (CONCAT(u.first_name, ' ',u.last_name) LIKE '%$search%' OR AES_DECRYPT(u.facility_npi,'$key') LIKE '%$search%') ";
        }
        $sql .= "ORDER BY u.supervisor_physician DESC";
        return $this->rawQuery($sql);
    }
    /**
     * fetch the ownership user
     *
     * @param $userId
     */
    function fetchOwnerShipInfo($userId)
    {

        return DB::table("user_ddownerinfo")

            ->where("user_id", "=", $userId)

            ->count();
    }
    /**
     * fetch the member provider profile
     *
     * @param $userId
     */
    function fetchMemberProfile($userId)
    {
        $key = $this->key;

        $tbl = $this->tblU;

        $tbl2 = $this->tbl;

        $role = DB::table("user_role_map")

            ->select("user_role_map.role_id")

            ->join("roles", "roles.id", "=", "user_role_map.role_id")

            ->where("user_role_map.user_id", "=", $userId)

            ->first();
        if (is_object($role) && $role->role_id != 9) {

            $provider = DB::table("user_dd_individualproviderinfo")

                ->select(
                    "$tbl.first_name",
                    "$tbl.last_name",
                    DB::raw("AES_DECRYPT(cm_$tbl.email,'$key') AS email"),
                    "$tbl.gender",
                    DB::raw("AES_DECRYPT(cm_$tbl.phone,'$key') AS phone"),
                    "$tbl.cnic",
                    "$tbl.emp_number",
                    "$tbl.cnic",
                    DB::raw("AES_DECRYPT(cm_$tbl.facility_npi,'$key') AS facility_npi"),
                    "$tbl.primary_speciality",
                    "$tbl.secondary_speciality",
                    DB::raw("AES_DECRYPT(cm_$tbl.dob,'$key') AS dob"),
                    "$tbl.state_of_birth",
                    "$tbl.country_of_birth",
                    DB::raw("AES_DECRYPT(cm_$tbl.address_line_one,'$key') AS address_line_one"),
                    DB::raw("AES_DECRYPT(cm_$tbl.address_line_two,'$key') AS address_line_two"),
                    DB::raw("AES_DECRYPT(cm_$tbl.ssn,'$key') AS ssn"),
                    "$tbl.city",
                    "$tbl.state",
                    "$tbl.zip_code",
                    DB::raw("AES_DECRYPT(cm_$tbl.work_phone,'$key') AS work_phone"),
                    DB::raw("AES_DECRYPT(cm_$tbl.fax,'$key') AS fax"),
                    DB::raw("AES_DECRYPT(cm_$tbl.visa_number,'$key') AS visa_number"),
                    "$tbl.eligible_to_work",
                    "$tbl.place_of_birth",
                    "$tbl.status",
                    "$tbl.hospital_privileges",
                    "$tbl.deleted",
                    "user_dd_individualproviderinfo.*",
                    "portals.identifier as cahq_number",
                    "role_short_name",
                    "professional_types.name as professional_type"
                )

                ->join($tbl, $tbl.".id", "=", "user_dd_individualproviderinfo.user_id")

                ->join("user_role_map", "user_role_map.user_id", "=", $tbl.".id")

                ->join("roles", "roles.id", "=", "user_role_map.role_id")

                ->leftJoin("portals", "portals.user_id", "=", "user_dd_individualproviderinfo.user_id")

                ->leftJoin("professional_types", "professional_types.id", "=", $tbl.".professional_type_id")

                ->where("user_dd_individualproviderinfo.user_id", "=", $userId)

                // ->orderBy("supervisor_physician","DESC")

                ->first();

            if (is_object($provider)) {

                $noOflocas = DB::table("individualprovider_location_map")

                    ->where("user_id", "=", $userId)

                    // ->orderBy("supervisor_physician","DESC")

                    ->count();

                $stateLicense = DB::table("user_licenses")

                    ->select("user_licenses.license_no")

                    ->where("user_id", "=", $userId)

                    ->where("type_id", "=", 1)

                    ->orderBy("id", "DESC")

                    ->first();

                $driverLicense = DB::table("user_licenses")

                    ->select("user_licenses.license_no")

                    ->where("user_id", "=", $userId)

                    ->where("type_id", "=", 2)

                    ->orderBy("id", "DESC")

                    ->first();

                $deaLicense = DB::table("user_licenses")

                    ->select("user_licenses.license_no")

                    ->where("user_id", "=", $userId)

                    ->where("type_id", "=", 4)

                    ->orderBy("id", "DESC")

                    ->first();


                $roleName = "member";
                if (isset($provider->role_short_name))
                    $roleName = $provider->role_short_name;

                $provider->provider_type = $this->fetchOwnerShipInfo($userId) > 0  ? "owner" : $roleName;
                $provider->state_license_no = is_object($stateLicense) ? $stateLicense->license_no : "";
                $provider->driver_license_no = is_object($driverLicense) ? $driverLicense->license_no : "";
                $provider->dea_license_no = is_object($deaLicense) ? $deaLicense->license_no : "";
                $provider->number_of_affiliated_locations = $noOflocas;
                if ($this->isValidDate($provider->dob))
                    $provider->dob = date("m/d/Y", strtotime($provider->dob));
            }

            return $provider;
        } else {
            $provider = DB::table($tbl2)

                ->select(
                    "$tbl.first_name",
                    "$tbl.last_name",
                    DB::raw("AES_DECRYPT(cm_$tbl.email,'$key') AS email"),
                    "$tbl.gender",
                    DB::raw("AES_DECRYPT(cm_$tbl.phone,'$key') AS phone"),
                    "$tbl.cnic",
                    "$tbl.emp_number",
                    DB::raw("AES_DECRYPT(cm_$tbl.facility_npi,'$key') AS facility_npi"),
                    "$tbl.primary_speciality",
                    "$tbl.secondary_speciality",
                    DB::raw("AES_DECRYPT(cm_$tbl.dob,'$key') AS dob"),
                    "$tbl.state_of_birth",
                    "$tbl.country_of_birth",
                    DB::raw("AES_DECRYPT(cm_$tbl.address_line_one,'$key') AS address_line_one"),
                    DB::raw("AES_DECRYPT(cm_$tbl.address_line_two,'$key') AS address_line_two"),
                    DB::raw("AES_DECRYPT(cm_$tbl.ssn,'$key') AS ssn"),
                    "$tbl.city",
                    "$tbl.state",
                    "$tbl.zip_code",
                    DB::raw("AES_DECRYPT(cm_$tbl.work_phone,'$key') AS work_phone"),
                    DB::raw("AES_DECRYPT(cm_$tbl.fax,'$key') AS fax"),
                    DB::raw("AES_DECRYPT(cm_$tbl.visa_number,'$key') AS visa_number"),
                    "$tbl.eligible_to_work",
                    "$tbl.place_of_birth",
                    "$tbl.status",
                    "$tbl.hospital_privileges",
                    "$tbl.deleted",
                    "portals.identifier as cahq_number",
                    "role_short_name",
                    "professional_types.name as professional_type"
                )

                ->join($tbl, $tbl.".id", "=", $tbl2.".user_id")

                ->join("user_role_map", "user_role_map.user_id", "=", "users.id")

                ->join("roles", "roles.id", "=", "user_role_map.role_id")

                ->leftJoin("portals", "portals.user_id", "=", $tbl2.".user_id")

                ->join("professional_types", "professional_types.id", "=", $tbl.".professional_type_id")

                ->where($tbl2.".user_id", "=", $userId)

                // ->orderBy("supervisor_physician","DESC")

                ->first();

            if (is_object($provider)) {

                $noOflocas = DB::table("individualprovider_location_map")

                    ->where("user_id", "=", $userId)

                    // ->orderBy("supervisor_physician","DESC")

                    ->count();

                $stateLicense = DB::table("user_licenses")

                    ->select("user_licenses.license_no")

                    ->where("user_id", "=", $userId)

                    ->where("type_id", "=", 1)

                    ->orderBy("id", "DESC")

                    ->first();

                $driverLicense = DB::table("user_licenses")

                    ->select("user_licenses.license_no")

                    ->where("user_id", "=", $userId)

                    ->where("type_id", "=", 2)

                    ->orderBy("id", "DESC")

                    ->first();

                $deaLicense = DB::table("user_licenses")

                    ->select("user_licenses.license_no")

                    ->where("user_id", "=", $userId)

                    ->where("type_id", "=", 4)

                    ->orderBy("id", "DESC")

                    ->first();



                $provider->provider_type = $this->fetchOwnerShipInfo($userId) > 0  ? "owner" : $provider->role_short_name;
                $provider->state_license_no = is_object($stateLicense) ? $stateLicense->license_no : "";
                $provider->driver_license_no = is_object($driverLicense) ? $driverLicense->license_no : "";
                $provider->dea_license_no = is_object($deaLicense) ? $deaLicense->license_no : "";
                $provider->number_of_affiliated_locations = $noOflocas;
            }

            return $provider;
        }
    }
    /**
     * fetch the user routes
     *
     * @param $roleId
     */
    function fetchUserRoutes()
    {
        // if ($roleId == 1) {
        //     return DB::table("route_role_map")

        //         ->select("routes.*")

        //         ->join("routes", function ($join) {
        //             $join->on("routes.id", "=", "route_role_map.route_id")
        //                 ->where("routes.parent_route_id", "=", 0)
        //                 ->whereNotNull('routes.routes');
        //         })

        //         ->where("route_role_map.role_id", "=", $roleId)
        //         //->orderBy("routes.sort_by","ASC")
        //         ->get();
        // } else
        // {
        //     return DB::table("route_role_map")

        //         ->select("routes.*", "route_role_map.role_id")

        //         ->join("routes", function ($join) {
        //             $join->on("routes.id", "=", "route_role_map.route_id")
        //                 ->whereNotNull('routes.routes');
        //         })
        //         ->where("routes.parent_route_id", "=", 0)
        //         ->whereIn("route_role_map.role_id", $roleId)
        //         ->groupBy("route_role_map.route_id")
        //         //->orderBy("routes.sort_by","ASC")
        //         ->get();
        // }

        return DB::table("routes")
            ->select("routes.*")
            ->where("parent_route_id", "=", 0)
            ->where("parent_navigation_id", "=", 0)
            ->whereNotNull("routes")
            ->groupBy("routes.id")
            ->get();
    }

    /**
     * This method will return the inner routes of the user with respect to the assigend navigation.
     * @param $navigation navigation objects
     * @return $innerRoutes inner routes of the user
     */

    function fetchUserInnerRoutes($routes, $navigation){
        $innerRoutes = [];
       
        foreach($routes as $eachRoute){
            $innerRoutes[$eachRoute->id] = DB::table("routes")
                                            ->select("routes.*")
                                            ->where("parent_route_id", $eachRoute->id)
                                            ->get();    
        }        

        return $innerRoutes;
    }


    /**
     * fetch user ids
     *
     * @param $roleId
     * @param $routeId
     */
    function fetchUserComponent($routeId, $roleId)
    {
        $sql = "SELECT r.*
        FROM `cm_route_role_map` rrm
        INNER JOIN cm_routes r
        ON r.id = rrm.route_id
        AND r.is_navigation = '0'
        AND (r.id = '$routeId' OR r.parent_route_id = '$routeId')
        WHERE rrm.role_id = '$roleId'";

        $userComponent = $this->rawQuery($sql);

        $arrangeComponent = [];
        if (count($userComponent)) {
            foreach ($userComponent as $eachComponent) {
                $arrangeComponent[$eachComponent->component] = 1;
            }
        }
        return $arrangeComponent;
    }
    /**
     * fetch user roles
     *
     * @param $userId
     */
    function fetchUserRoles($userId)
    {

        return DB::table("user_role_map")

            ->select("role_id")

            ->where("user_id", $userId)

            ->get();
    }
    /**
     * fetch navigation of the app
     *
     * @param $roleId
     */
    function fetchNavigation($roleId)
    {   
        $sql = "SELECT r.*
        FROM cm_route_role_map rrm
        INNER JOIN cm_routes r
        ON r.id = rrm.route_id AND r.is_navigation = '1' AND r.parent_navigation_id = '0'
        WHERE rrm.role_id IN ($roleId)
        GROUP BY rrm.route_id
        ORDER BY r.sort_by";

        $navigation = $this->rawQuery($sql);

        return $navigation;
    }

    /**
     * fetch User navigation
     * 
     * @param $userId
     * @return $navigation
     */
    function fetchUserNavigation($userId) {

       
        $navigation = DB::table('routes')
            ->whereIn('id', function ($query) use ($userId) {
                $query->select('parent_navigation_id')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('route_id')
                            ->from('user_facility_privileges')
                            ->where('user_id', $userId)
                            ->where('view', 1)
                            ->groupBy('route_id');
                    });
            })
            ->union(function ($query) use ($userId) {
                $query->select('*')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('route_id')
                            ->from('user_facility_privileges')
                            ->where('user_id', $userId)
                            ->where('view', 1);
                    })
                    ->where('parent_navigation_id', 0)
                    ->where('is_navigation', 1);
            })
            ->union(function ($query) use ($userId) {
                $query->select('*')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('parent_navigation_id')
                            ->from('routes')
                            ->whereIn('id', function ($query) use ($userId) {
                                $query->select('route_id')
                                    ->from('role_privileges')
                                    ->whereNotIn('route_id', function ($query) use ($userId) {
                                        $query->select('route_id')
                                            ->from('user_facility_privileges')
                                            ->where('user_id', $userId);
                                    })
                                    ->whereIn('role_id', function ($query) use ($userId) {
                                        $query->select('role_id')
                                            ->from('user_role_map')
                                            ->where('user_id', $userId);
                                    })
                                    ->where('view', 1);
                            })
                            ->where('parent_navigation_id', '<>', 0);
                    });
            })
            ->union(function ($query) use ($userId) {
                $query->select('*')
                    ->from('routes')
                    ->whereIn('id', function ($query) use ($userId) {
                        $query->select('route_id')
                            ->from('role_privileges')
                            ->whereNotIn('route_id', function ($query) use ($userId) {
                                $query->select('route_id')
                                    ->from('user_facility_privileges')
                                    ->where('user_id', $userId);
                            })
                            ->whereIn('role_id', function ($query) use ($userId) {
                                $query->select('role_id')
                                    ->from('user_role_map')
                                    ->where('user_id', $userId);
                            })
                            ->where('view', 1);
                    })
                    ->where('parent_navigation_id', 0)
                    ->where('is_navigation', 1);
            })
            ->orderBy('sort_by', 'asc')
            ->get();


        return $navigation;
        
    }   

    /**
     * fetch inner navigation
     *
     * @param $roleId
     * @param $navId
     */
    function fetchInnerNavigation($roleId, $navId)
    {
        $sql = "SELECT r.*
        FROM cm_route_role_map rrm
        INNER JOIN cm_routes r
        ON r.id = rrm.route_id AND r.is_navigation = '1' AND r.parent_navigation_id = '$navId'
        WHERE rrm.role_id IN ($roleId)
        GROUP BY rrm.route_id
        ORDER BY r.sort_by";

        $innerNavigation = $this->rawQuery($sql);

        return $innerNavigation;
    }

    /**
     * fetch user inner navigation
     * 
     * @param $userId
     * @param $navId
     * @return $userInnerNavigation
     */
    function fetchUserInnerNavigation($userId, $navId) {
        // $userInnerNavigation = DB::table('routes as r')
        // ->whereIn('r.id', function ($query) use ($userId) {
        //     $query->select('route_id')
        //         ->from('user_facility_privileges')
        //         ->where('user_id', $userId)
        //         ->where('view', 1)
        //         ->groupBy('route_id');
        // })
        // ->where('r.is_navigation', 1)
        // ->where('r.parent_navigation_id', $navId)
        // ->orderBy('r.sort_by')
        // ->get();

        $routeIds = DB::table('routes')
            ->whereIn('id', function ($query) use($userId) {
                $query->select('route_id')
                    ->from('user_facility_privileges')
                    ->where('user_id', $userId)
                    ->where('view', 1)
                    ->groupBy('route_id');
            })
            ->where('is_navigation', 1)
            ->where('parent_navigation_id', $navId);

        $roleIds = DB::table('routes')
            ->whereIn('id', function ($query) use($userId) {
                $query->select('route_id')
                    ->from('role_privileges')
                    ->whereIn('role_id', function ($subQuery) use ($userId) {
                        $subQuery->select('role_id')
                            ->from('user_role_map')
                            ->where('user_id', $userId);
                    })
                    ->where('view', 1);
            })
            ->where('is_navigation', 1)
            ->where('parent_navigation_id', $navId);

        $userInnerNavigation = $routeIds->union($roleIds)->orderBy('sort_by')->get();

        return $userInnerNavigation;
    }


    /**
     * fetch the parent route
     *
     * @param $parentId
     */
    function fetchParentRoute($parentId)
    {
        return DB::table("routes")
            ->where("id", "=", $parentId)
            ->whereNotNull('routes')
            ->first();
    }
    /**
     * fetch the child routes
     *
     * @param $parentId
     */
    function fetchInnerRoutes($parentId, $roleId)
    {
        return DB::table("routes")
            ->select("routes.*")
            ->join("route_role_map", function ($join) use ($roleId) {
                $join->on("route_role_map.route_id", "=", "routes.id")
                    ->whereIn("route_role_map.role_id", $roleId);
            })
            ->where("routes.parent_route_id", "=", $parentId)
            ->groupBy("route_role_map.route_id")
            //->whereNotNull('routes')
            ->get();
    }
    /**
     * fetch the child routes
     *
     * @param $parentId
     */
    function fetchSpecificRoutes($id, $parentId, $roleId)
    {
        return DB::table("routes")
            ->select("routes.*")
            ->join("route_role_map", function ($join) use ($roleId) {
                $join->on("route_role_map.route_id", "=", "routes.id")
                    ->whereIn("route_role_map.role_id", $roleId);
            })
            ->where("routes.id", "=", $id)
            ->where("routes.parent_route_id", "=", $parentId)
            ->groupBy("route_role_map.route_id")
            ->get();
    }
    /**
     * ISO  date format
     */
    function isoDate($date)
    {
        return $date;
        // return $this->convertToDateYmd($date);
    }
    /**
     * fomat the date in iso format
     * 
     * @param string $date
     * @return string $date
     */
    function formatDate($dateString) {
        
        $format1        = '/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/(\d{4})$/'; // d/m/Y
        $format2        = '/^(\d{4})\-(0[1-9]|1[0-2])\-(0[1-9]|[1-2][0-9]|3[0-1])$/'; // Y-m-d
        $firstFormat    = preg_match($format1, $dateString);
        $secondFormat   = preg_match($format2, $dateString);
        if($firstFormat) {
            $dateObject = \DateTime::createFromFormat('m/d/Y', $dateString);
            return $dateObject->format('Y-m-d');
        }
        elseif($secondFormat) {
            return $dateString;
        }
        else {
            return $dateString;
        }

    }
    /**
     * fetch the education data of the user
     *  @param $userId
     * @return result
     */
    function fetchEducation($userId, $type = "")
    {
        if ($type == "professional") {
            $eduP = DB::table("education")
                ->where("user_id", "=", $userId)
                ->where("education_type", "=", "professional")
                ->first();
            if (is_object($eduP)) {
                if ($this->isValidDate($eduP->attendance_date_from))
                    $eduP->attendance_date_from =  date("Y-m-d", strtotime($eduP->attendance_date_from));
                if ($this->isValidDate($eduP->attendance_date_to))
                    $eduP->attendance_date_to =  date("Y-m-d", strtotime($eduP->attendance_date_to));
            }
            return $eduP;
        } elseif ($type == "post_graduate") {
            $eduPG = DB::table("education")
                ->select("education.*", "facilities.facility")
                ->leftJoin("facilities", "facilities.id", "=", "education.facility_id")
                ->where("user_id", "=", $userId)
                ->where("education_type", "=", "post_graduate")
                ->get();

            if (count($eduPG)) {
                foreach ($eduPG as $eduPGG) {
                    if ($this->isValidDate($eduPGG->attendance_date_from))
                        $eduPGG->attendance_date_from =  date("Y-m-d", strtotime($eduPGG->attendance_date_from));
                    if ($this->isValidDate($eduPGG->attendance_date_to))
                        $eduPGG->attendance_date_to =  date("Y-m-d", strtotime($eduPGG->attendance_date_to));
                }
            }
            return $eduPG;
        } elseif ($type == "all") {
            $eduPG = DB::table("education")
                ->select("education.*")
                //->leftJoin("facilities","facilities.id","=","education.facility_id")
                ->where("user_id", "=", $userId)
                //->where("education_type","=","post_graduate")
                ->get();


            return $eduPG;
        }
    }
    /**
     * fetch afflieted locations
     * @param $userId
     */
    function fetchAfflietedLocations($userId)
    {
        $key = $this->key;
        $tbl = $this->tbl;

        return DB::table("individualprovider_location_map")
            ->select(
                DB::raw("AES_DECRYPT(cm_$tbl.practice_name,'$key') AS practice_name"),
                "$tbl.office_manager_name",
                DB::raw("AES_DECRYPT(cm_$tbl.npi,'$key') AS npi"),
                "$tbl.user_id",
                "$tbl.for_credentialing",
                "$tbl.id"
            )
            ->join($tbl, $tbl.".user_id", "=", "individualprovider_location_map.location_user_id")
            //->where("user_id","=",$userId)
            ->where("individualprovider_location_map.user_id", "=", $userId)
            ->get();
    }
    /**
     * owner afflieted locations
     *
     * @param $userId
     */
    function ownerAfflietedLocations($userId)
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;
        // $tblU = "cm_".$this->tblU;

        $sql = "(SELECT T.practice_name, T.office_manager_name, T.npi, T.user_id, if(T.affiliation = 1, 'Provider, owner', 'Provider') as affiliation, T.for_credentialing, T.id
                FROM
                (SELECT AES_DECRYPT(pli.practice_name,'$key') as practice_name, pli.office_manager_name, AES_DECRYPT(pli.npi,'$key') as npi, pli.user_id,
                (SELECT 1 FROM cm_user_ddownerinfo WHERE parent_user_id = pli.user_id AND user_id = $userId) as affiliation,
                pli.for_credentialing, pli.id
                FROM `cm_individualprovider_location_map` plm
                INNER JOIN `$tbl` pli
                ON pli.user_id = plm.location_user_id
                WHERE plm.user_id = $userId) AS T)
                UNION ALL
                (SELECT AES_DECRYPT(pli.practice_name,'$key') as practice_name, pli.office_manager_name, AES_DECRYPT(pli.npi,'$key') as npi, pli.user_id, 'owner' as affiliation, '-' as for_credentialing, NULL as id
                        FROM `$tbl` pli
                        WHERE pli.user_parent_id IN (SELECT parent_user_id
                                                    FROM `cm_user_ddownerinfo`
                                                    WHERE user_id = $userId)
                        AND pli.user_id NOT IN(SELECT pli.user_id
                                                FROM `cm_individualprovider_location_map` plm
                                                INNER JOIN `$tbl` pli
                                                ON pli.user_id = plm.location_user_id
                                                WHERE plm.user_id = $userId)
                        ORDER BY pli.user_id, pli.index_id)
        ";
        return $this->rawQuery($sql);
    }
    /**
     * owner afflieted locations
     *
     * @param $userId
     */
    function ownerAfflietedLocation($userId, $locationId)
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;

        $sql = "SELECT M.practice_name, M.office_manager_name, M.npi, M.user_id, M.affiliation, M.for_credentialing, M.id
        FROM (
        (SELECT T.practice_name, T.office_manager_name, T.npi, T.user_id, if(T.affiliation = $userId, 'Provider, owner', 'Provider') as affiliation, T.for_credentialing, T.id
                        FROM
                        (SELECT AES_DECRYPT(pli.practice_name,'$key') as practice_name , pli.office_manager_name, AES_DECRYPT(pli.npi,'$key') as npi, pli.user_id,
                        (SELECT 1 FROM cm_user_ddownerinfo WHERE parent_user_id = pli.user_id AND user_id = $userId) as affiliation,
                        pli.for_credentialing, pli.id
                        FROM `cm_individualprovider_location_map` plm
                        INNER JOIN `$tbl` pli
                        ON pli.user_id = plm.location_user_id
                        WHERE plm.user_id = 1) AS T)
                        UNION ALL
                        (SELECT AES_DECRYPT(pli.practice_name,'$key') as practice_name, pli.office_manager_name, AES_DECRYPT(pli.npi,'$key') as npi, pli.user_id, 'owner' as affiliation, '-' as for_credentialing, NULL as id
                                FROM `$tbl` pli
                                WHERE pli.user_parent_id IN (SELECT parent_user_id
                                                            FROM `cm_user_ddownerinfo`
                                                            WHERE user_id = $userId)
                                AND pli.user_id NOT IN(SELECT pli.user_id
                                                        FROM `cm_individualprovider_location_map` plm
                                                        INNER JOIN `$tbl` pli
                                                        ON pli.user_id = plm.location_user_id
                                                        WHERE plm.user_id = $userId)
                                ORDER BY pli.user_id, pli.index_id)
        ) AS M
        WHERE M.user_id = $locationId
        ";
        return $this->rawQuery($sql);
    }
    /**
     * fetch location user afflieted location
     *
     * @param $parentId
     */
    function fetchParentAfflietedLocations($parentId)
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;

        $sql = "SELECT AES_DECRYPT(practice_name,'$key') as practice_name, office_manager_name, AES_DECRYPT(npi,'$key') as npi, user_id,for_credentialing,id
        FROM `$tbl`
        WHERE user_parent_id = (SELECT user_parent_id FROM `$tbl` WHERE `$tbl`.`user_id` = $parentId)
        ORDER BY for_credentialing DESC";

        return $this->rawQuery($sql);
    }
    /**
     * fetch practce info
     *
     * @param $practiceId
     */
    function fetchPracticeDetailInfo($practiceId)
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;
        $sql = "SELECT AES_DECRYPT(practice_name,'$key') as practice_name, office_manager_name, AES_DECRYPT(npi,'$key') as npi, user_id,for_credentialing,id
        FROM `$tbl`
        WHERE user_id = $practiceId ORDER BY for_credentialing DESC";

        return $this->rawQuery($sql);
    }
    /**
     * get the user role
     *
     * @param $userId
     * @param return
     */
    function userRole($userId)
    {
        return DB::table("user_role_map")
            ->select("roles.role_name", "roles.role_short_name", "roles.id")
            ->leftJoin("roles", "roles.id", "user_role_map.role_id")
            ->where("user_role_map.user_id", "=", $userId)
            ->first();
    }
    /**
     * fetch the selected the facilities
     *
     * @param $facilitIds
     */
    function fetchSpecificFacilties($facilitIds)
    {
        return DB::table("facilities")->whereIn("id", $facilitIds)->get();
    }
    /**
     * update the data from specific table
     *
     * @param $table
     * @param $where
     * @param $data
     */
    function updateData($table, $where = "", $data)
    {
        return DB::table($table)->where($where)->update($data);
    }
    /**
     * add the provider logs data into system
     *
     * @param $logData
     */
    function addProviderLogs($table, $logData)
    {
        return DB::table($table)->insertGetId($logData);
    }
    /**
     * run raw sql query
     */
    function rawQuery($sql)
    {
        return DB::select($sql);
    }
    /**
     * add member user against the group provider
     *
     *
     */
    function addMemberUser($email, $firstName, $lastName)
    {
        $providerId = 0;
        $password = Str::random(6);

        $fullName = $firstName . " " . $lastName;

        $addUser = [
            "name" => $fullName,
            "email" => $email,
            "password" => Hash::make($password)
        ];

        $user = User::create($addUser);
        $user->createToken($fullName . " Token")->plainTextToken;
        $userId = $user->id;

        $adminId = 0;

        $providerMapData = ProviderCompanyMap::where("provider_id", $providerId)->first(["company_id"]);

        $companyId = $providerMapData->company_id;

        //$companyId = $request->company_id;

        $role = Role::where("role_name", "=", "Provider Member")->first(["id"]);

        $roleId = $role->id;

        $gender   = "";

        $contactNumber = "";

        $employeeNumber = "";

        $cnic = "";

        $addUserProfile = [
            "admin_id"          => $adminId,
            'user_id'           => $userId,
            "company_id"        => $companyId,
            "role_id"           => $roleId,
            "first_name"        => $firstName,
            "last_name"         => $lastName,
            "gender"            => $gender,
            "contact_number"    => $contactNumber,
            "employee_number"   => $employeeNumber,
            "cnic"              => $cnic,
            "picture"           => "",
            "created_at"        => date("Y-m-d H:i:s")
        ];
        $emailData = ["login_email" => $email, "password" => $password, "name" => $fullName, "provider_type" => $providerType, "legal_business_name" => $legalBName];
        try {

            Mail::to($email)

                ->send(new ProviderCredentials($emailData));
        } catch (\Throwable $exception) {

            $isSentEmail = 0;

            $msg = $exception->getMessage();
        }
    }
    /**
     * upload the file on storage
     *
     * @param $file
     * @param $mainFolder
     * @param $subFolder
     */
    function uploadMyFile($fileName = "", $fileContent, $folder)
    {
        set_time_limit(0);
        if ($fileName != "") {
            //Upload File to external server
            $directoryExist = Storage::disk('ftp')->exists($folder);
            
            if (!$directoryExist) {
                Storage::disk('ftp')->makeDirectory($folder, 0775, true);
            }
            $destination = $folder . "/" . $fileName;
            $isUploaded = Storage::disk('ftp')->put($destination, fopen($fileContent, 'r+'));
            return ["file_name" => $fileName, "is_uploaded" => $isUploaded];
        }
    }
    /**
     * upload the file on storage
     *
     * @param $file
     * @param $mainFolder
     * @param $subFolder
     */
    function moveMyFile($path, $file, $content)
    {
        set_time_limit(0);
        if ($path != "") {
            //Upload File to external server
            $directoryExist = Storage::disk('ftp')->exists($path);
            if (!$directoryExist) {
                Storage::disk('ftp')->makeDirectory($path, 0775, true);
            }
            $destination = $path . "/" . $file;
            $isUploaded = Storage::disk('ftp')->put($destination, $content);
            return ["file_name" => $path, "is_moved" => $isUploaded];
        }
    }
    /**
     * delete upload file from storage
     *
     * @param $file
     */
    function deleteFile($file)
    {
        return Storage::disk('ftp')->delete($file);
    }
    /**
     * encrypt uploading file
     *
     * @param $request
     * @return array
     */
    function encryptUpload($requestData, $disFolder)
    {
        $serviceReq = env("ENC_APP_URL") . "/api/file/encrypt/credentail";
        // Get the bearer token
        $token = $requestData->bearerToken();

        //$body = $request->all();
        //dd($body);
        if ($requestData->hasFile('file') && ($requestData->file('file') && $requestData->file('file')->getSize()))
            $file = $requestData->file('file');
        if ($requestData->hasFile('attachment') && ($requestData->file('attachment') && $requestData->file('attachment')->getSize()))
            $file = $requestData->file('attachment');
        if ($requestData->hasFile('image') && ($requestData->file('image') && $requestData->file('image')->getSize()))
            $file = $requestData->file('image');
        if ($requestData->hasFile('pdf') && ($requestData->file('pdf') && $requestData->file('pdf')->getSize()))
            $file = $requestData->file('pdf');

        $contents = $file->get();
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            "Accept" => 'application/json'
        ])
            ->attach("file", $contents, $file->getClientOriginalName())
            ->post($serviceReq, [
                'destination_path' => $disFolder
            ]);
        return $response->json();
    }
    /**
     * encrypt uploading file
     *
     * @param $request
     * @return array
     */
    function uploadWithEncryption($token,$contents, $disFolder,$fileName)
    {
        $serviceReq = env("ENC_APP_URL") . "/api/file/encrypt/credentail";
        // // Get the bearer token
        // $token = $requestData->bearerToken();

        // //$body = $request->all();
        // //dd($body);
        // if ($requestData->hasFile('file') && ($requestData->file('file') && $requestData->file('file')->getSize()))
        //     $file = $requestData->file('file');
        // if ($requestData->hasFile('attachment') && ($requestData->file('attachment') && $requestData->file('attachment')->getSize()))
        //     $file = $requestData->file('attachment');
        // if ($requestData->hasFile('image') && ($requestData->file('image') && $requestData->file('image')->getSize()))
        //     $file = $requestData->file('image');
        // if ($requestData->hasFile('pdf') && ($requestData->file('pdf') && $requestData->file('pdf')->getSize()))
        //     $file = $requestData->file('pdf');

        // $contents = $file->get();
        // $file->getClientOriginalName()
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            "Accept" => 'application/json'
        ])
            ->attach("file", $contents, $fileName)
            ->post($serviceReq, [
                'destination_path' => $disFolder
            ]);
        return $response->json();
    }
    
    /**
     * encrypt uploading file
     *
     * @param $request
     * @return array
     */
    function uploadW9File($file,$fileName)
    {
        $directoryExist = Storage::disk('ftp')->exists("w9/images");
            
        if (!$directoryExist) {
            Storage::disk('ftp')->makeDirectory("w9/images", 0775, true);
        }
        $destination = "w9/images" . "/" . $fileName;

        $uploadedFile = Storage::disk('ftp')->put($destination,  $file);
        
        if ($uploadedFile) {
            return  true;
        } else {
            return  false;
        }
    }
     /**
     * get uploading file
     *
     * @param $request
     * @return array
     */
    function delW9File($fileName)
    {
       
        $destination = "w9/images" . "/" . $fileName;

        return Storage::disk('ftp')->delete($destination);
        
       
    }
    /**
     * encrypt bluck uploading file
     *
     * @param $request 
     * @param $file
     * @param $destPath
     * @return array
     */
    function encryptEachUpload($requestData,$file,$destPath) {
        
        $serviceReq = env("ENC_APP_URL") . "/api/file/encrypt/credentail";
        // Get the bearer token
        $token = $requestData->bearerToken();

        $contents = $file->get();
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            "Accept" => 'application/json'
        ])
            ->attach("file", $contents, $file->getClientOriginalName())
            ->post($serviceReq, [
                'destination_path' => $destPath
            ]);
        return $response->json();
    }
     /**
     * encrypt bluck uploading file
     *
     * @param $request 
     * @param $file
     * @param $destPath
     * @return array
     */
    function encryptAndUpload($requestData,$file,$destPath) {
        
        $serviceReq = env("ENC_APP_URL") . "/api/encrypt/file";
        // Get the bearer token
        //$token = $requestData->bearerToken();

        $contents = $file->get();
        $response = Http::withHeaders([
            "Accept" => 'application/json'
        ])
            ->attach("file", $contents, $file->getClientOriginalName())
            ->post($serviceReq, [
                'destination_path' => $destPath
            ]);
        return $response->json();
    }
    /**
     * validate the date
     */
    function isValidDate($date)
    {
        /* correct format = "2012-09-15 11:23:32" or "2012-09-15"*/
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])( (0[0-9]|[1-2][0-4]):(0[0-9]|[1-5][0-9]):(0[0-9]|[1-5][0-9]))?$/", $date)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * get the professional groups
     *
     */
    function fetchProfessionalGroups()
    {
        return DB::table("professionalgroup_professionaltype_map as map")
            ->select("map.group_id as id", "pg.name")
            ->join("professional_groups as pg", "pg.id", "=", "map.group_id")
            ->groupBy("id", "name")
            ->orderBy("id", "ASC")
            ->get();
    }
    /**
     * get the professional types
     *
     */
    function fetchProfessionalTypes($groupId)
    {
        return DB::table("professionalgroup_professionaltype_map as map")
            ->select("map.type_id as id", "pt.name")
            ->join("professional_types as pt", "pt.id", "=", "map.type_id")
            ->where("map.group_id", "=", $groupId)
            ->orderBy("id", "ASC")
            ->get();
    }
    /**
     * fetch the invidual provider data
     *
     * @param $parentId
     */
    function fetchIndividualProviders($parentId)
    {
        return DB::table("user_dd_individualproviderinfo")
            ->join("users", "users.id", "=", "user_dd_individualproviderinfo.user_id")
            ->select(
                "user_dd_individualproviderinfo.*",
                "users.*",
                "professional_groups.name as professional_group_name",
                "professional_types.name as professional_type_name",
                "citizenships.name as citizen_name"
            )
            ->leftJoin("professional_groups", "professional_groups.id", "=", "users.professional_group_id")
            ->leftJoin("professional_types", "professional_types.id", "=", "users.professional_type_id")
            ->leftJoin("citizenships", "citizenships.id", "=", "users.citizenship_id")
            // ->leftJoin("user_licenses",function($join) {
            //     $join->on("user_licenses.user_id","=","users.id")
            //     ->whereIn("user_licenses.type_id",[4,1,2]);
            // })
            ->where("user_dd_individualproviderinfo.parent_user_id", "=", $parentId)
            ->get();
    }
    /**
     * fetch user licenses data
     *
     * @param $userId
     */
    function fetchLicensesData($userId, $typeId)
    {
        $license = DB::table("user_licenses")
            ->where("user_licenses.type_id", "=", $typeId)
            ->where("user_id", "=", $userId)
            ->first();
        if (is_object($license)) {
            if ($this->isValidDate($license->issue_date))
                $license->issue_date = date("Y-m-d", strtotime($license->issue_date));
            if ($this->isValidDate($license->exp_date))
                $license->exp_date = date("Y-m-d", strtotime($license->exp_date));
        }
        return $license;
    }
    /**
     * fetch the correspondence  address
     */
    function fetchCorrespondenceAddress($userId)
    {
        $key = $this->key;
        $tbl = $this->tbl;

        return  DB::table($tbl)
            ->select("user_baf_contactinfo.*")
            ->join("user_baf_contactinfo", "user_baf_contactinfo.user_id", "=", $tbl.".user_parent_id")
            ->where($tbl.".user_id", "=", $userId)
            ->first();
    }
    /**
     * fetch the locations  address
     */
    function fetchLocationAddress($userId)
    {
        $key = $this->key;
        $tbl = $this->tbl;
        return  DB::table($tbl)

            ->select(
            DB::raw("AES_DECRYPT(cm_$tbl.primary_correspondence_address,'$key') AS primary_correspondence_address"),
            DB::raw("AES_DECRYPT(cm_$tbl.phone,'$key') AS phone"),
            DB::raw("AES_DECRYPT(cm_$tbl.fax,'$key') AS fax"),
            DB::raw("AES_DECRYPT(cm_$tbl.email,'$key') AS email"),
            DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as,'$key') AS doing_buisness_as"),
            DB::raw("AES_DECRYPT(cm_$tbl.npi,'$key') AS npi"),
            DB::raw("AES_DECRYPT(cm_$tbl.practise_address,'$key') AS practise_address"),
            DB::raw("AES_DECRYPT(cm_$tbl.practise_address1,'$key') AS practise_address1"),
            DB::raw("AES_DECRYPT(cm_$tbl.practise_phone,'$key') AS practise_phone"),
            DB::raw("AES_DECRYPT(cm_$tbl.practise_fax,'$key') AS practise_fax"),
            DB::raw("AES_DECRYPT(cm_$tbl.practise_email,'$key') AS practise_email"),
            DB::raw("AES_DECRYPT(cm_$tbl.practice_name,'$key') AS practice_name"),
            DB::raw("AES_DECRYPT(cm_$tbl.tax_id,'$key') AS tax_id"),
            DB::raw("AES_DECRYPT(cm_$tbl.contact_phone,'$key') AS contact_phone"),
            DB::raw("AES_DECRYPT(cm_$tbl.contact_fax,'$key') AS contact_fax"),
            DB::raw("AES_DECRYPT(cm_$tbl.contact_email,'$key') AS contact_email")
            )

            ->where($tbl.".user_id", "=", $userId)
            ->first();
    }
    /**
     * fetch ownerInfo
     *
     * @param $usersId
     */
    function fetchOwnerInfo($userId)
    {
        $key = env("AES_KEY");

        $tbl ="users";
        return DB::table("user_ddownerinfo")
            ->select("user_ddownerinfo.*", "users.first_name as linked_user", "users.first_name", "users.last_name", DB::raw("AES_DECRYPT(cm_$tbl.ssn,'$key') as ssn"),DB::raw("AES_DECRYPT(cm_$tbl.dob,'$key') as dob"), DB::raw("AES_DECRYPT(cm_$tbl.email,'$key') as email"), "users.state_of_birth", "users.country_of_birth")
            ->leftJoin($tbl, "users.id", "=", "user_ddownerinfo.user_id")
            //->where("user_id","=",$userId)
            ->where("parent_user_id", "=", $userId)
            ->get();
    }
    /**
     * fetch the invidual provider data
     *
     * @param $userId
     */
    function fetchIndividualProvider($userId)
    {
        $key = env("AES_KEY");

        $tbl ="users";

        $provider = DB::table("user_dd_individualproviderinfo")
            ->select(
                $tbl.".first_name",
                $tbl.".last_name",
                DB::raw("AES_DECRYPT(cm_$tbl.email,'$key') AS email"),
                "$tbl.gender",
                DB::raw("AES_DECRYPT(cm_$tbl.phone,'$key') AS phone"),
                "user_dd_individualproviderinfo.parent_user_id",
                "$tbl.cnic",
                "$tbl.emp_number",
                "$tbl.profile_image",
                "$tbl.deleted",
                DB::raw("AES_DECRYPT(cm_$tbl.facility_npi,'$key') AS facility_npi"),
                "$tbl.primary_speciality",
                "$tbl.secondary_speciality",
                DB::raw("DATE_FORMAT(AES_DECRYPT(cm_$tbl.dob,'$key'),'%m/%d/%Y') AS format_dob"),
                DB::raw("AES_DECRYPT(cm_$tbl.dob,'$key') AS dob"),
                "$tbl.state_of_birth",
                "$tbl.country_of_birth",
                "$tbl.supervisor_physician",
                DB::raw("AES_DECRYPT(cm_$tbl.address_line_one,'$key') AS address_line_one"),
                DB::raw("AES_DECRYPT(cm_$tbl.address_line_two,'$key') AS address_line_two"),
                DB::raw("AES_DECRYPT(cm_$tbl.ssn,'$key') AS ssn"),
                "$tbl.city",
                "$tbl.state",
                "$tbl.zip_code",
                DB::raw("AES_DECRYPT(cm_$tbl.work_phone,'$key') AS work_phone"),
                DB::raw("AES_DECRYPT(cm_$tbl.fax,'$key') AS fax"),
                DB::raw("AES_DECRYPT(cm_$tbl.visa_number,'$key') AS visa_number"),
                "$tbl.eligible_to_work",
                "$tbl.place_of_birth",
                "$tbl.status",
                "$tbl.hospital_privileges",
                "professional_groups.name as professional_group_name",
                "professional_types.name as professional_type_name",
                "citizenships.name as citizen_name"
            )
            ->join($tbl, $tbl.".id", "=", "user_dd_individualproviderinfo.user_id")
            ->leftJoin("professional_groups", "professional_groups.id", "=", $tbl.".professional_group_id")
            ->leftJoin("professional_types", "professional_types.id", "=", $tbl.".professional_type_id")
            ->leftJoin("citizenships", "citizenships.id", "=", $tbl.".citizenship_id")
            ->where("user_dd_individualproviderinfo.user_id", "=", $userId)
            ->first();
        if (is_object($provider))
            $provider->password = "";

        return $provider;
    }
    /**
     * delete the owner user
     *
     * @param $index
     * @param $userParent
     */
    function deleteOwnerShipUser($index, $userParent)
    {
        return DB::table("user_ddownerinfo")
            ->whereRaw("index_id=$index AND (user_id = '$userParent' OR parent_user_id = $userParent)")
            ->delete();
    }
    /**
     * fetch location user profile
     */
    function fetchLocationUser($userId, $type = "")
    {
        $key = $this->key;

        $tbl = $this->tbl;
        $tbl2 = $this->tbl2;
        $tblU = $this->tblU;
        $locationUser = DB::table($tbl)
            ->select(
                "$tbl.user_id",
                DB::raw("AES_DECRYPT(cm_$tbl.practise_address,'$key') as address_line_one"),
                DB::raw("AES_DECRYPT(cm_$tbl.practise_address1,'$key') as address_line_two"),
                DB::raw("AES_DECRYPT(cm_$tbl.email,'$key') as email"),
                DB::raw("AES_DECRYPT(cm_$tbl.phone,'$key') as phone"),
                DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as,'$key') as doing_buisness_as"),
                "$tbl.office_manager_name",
                "$tbl.city",
                "$tbl.state",
                "$tbl.zip_code",
                DB::raw("AES_DECRYPT(cm_$tbl.tax_id,'$key') AS tax_id"),
                DB::raw("AES_DECRYPT(cm_$tbl.npi,'$key') AS npi"),
                DB::raw("AES_DECRYPT(cm_$tbl.fax,'$key') AS fax"),
                DB::raw("(SELECT seeking_service FROM `cm_user_baf_businessinfo` WHERE `cm_$tbl`.`user_parent_id` = `cm_user_baf_businessinfo`.`user_id`) as seeking_service"),
                DB::raw("(SELECT COUNT(id) FROM `cm_$tbl` WHERE user_parent_id = (SELECT user_parent_id FROM `cm_$tbl` WHERE `cm_$tbl`.`user_id` = $userId) ) as number_of_physical_location"),
                DB::raw("(SELECT legal_business_name FROM `cm_user_baf_practiseinfo` WHERE `cm_$tbl`.`user_parent_id` = `cm_user_baf_practiseinfo`.`user_id`) as legal_business_name"),
                DB::raw("(SELECT COUNT(user_id) FROM
            `cm_individualprovider_location_map` WHERE `cm_individualprovider_location_map`.`location_user_id` = $userId AND `cm_individualprovider_location_map`.`for_credentialing` = 1) as
            number_of_individual_provider"),
                "$tbl2.group_specialty",
                "$tbl.is_primary",
                "$tbl.user_parent_id",
                DB::raw("AES_DECRYPT(cm_$tbl2.fax,'$key') as b_fax"),
                DB::raw("AES_DECRYPT(cm_$tbl2.facility_tax_id,'$key') as facility_tax_id"),
                DB::raw("(SELECT contact_person_email FROM `cm_user_baf_contactinfo` WHERE `cm_$tbl`.`user_parent_id` = `cm_user_baf_contactinfo`.`user_id`) as contact_person_email"),
                DB::raw("(SELECT contact_person_phone FROM `cm_user_baf_contactinfo` WHERE `cm_$tbl`.`user_parent_id` = `cm_user_baf_contactinfo`.`user_id`) as contact_person_phone"),
                "$tbl2.business_established_date",
                "$tbl2.federal_tax_classification",
                DB::raw("AES_DECRYPT(cm_$tbl.practice_name,'$key') AS practice_name"),
                DB::raw("(SELECT CONCAT(u.first_name, ' ', u.last_name)
            FROM cm_user_ddownerinfo oi
            INNER JOIN cm_$tblU u
            ON u.id = oi.user_id
            WHERE oi.parent_user_id  = (SELECT pli.user_parent_id
                                        FROM `cm_$tbl` pli
                                        WHERE pli.user_id = '$userId')
            LIMIT 0,1) as owner"),
                DB::raw("IF(is_primary = 1,
            (SELECT CONCAT(u.first_name, ' ', u.last_name)
             FROM `cm_user_dd_individualproviderinfo` ipi
             INNER JOIN `cm_users` u
             ON u.id = ipi.user_id AND u.supervisor_physician = '1'
             WHERE ipi.parent_user_id = '$userId'
             ORDER BY u.id
             LIMIT 0,1),
                (SELECT CONCAT(u.first_name, ' ', u.last_name)
             FROM `cm_individualprovider_location_map` plm
             INNER JOIN `cm_users` u
             ON u.id = plm.user_id AND u.supervisor_physician = '1'
             WHERE plm.location_user_id = '$userId' AND plm.for_credentialing = '1'
             ORDER BY u.id
             LIMIT 0,1)) as supervisor_physician"),
                DB::raw("(SELECT deleted from `cm_users` WHERE cm_users.id = '$userId' LIMIT 0,1) as deleted")

            )
            ->leftJoin($tbl2, "$tbl2.user_id", "$tbl.user_parent_id")

            ->where("$tbl.user_id", "=", $userId)
            ->first();
        // exit($type);
        // $this->printR($locationUser,true);
        if (is_object($locationUser) && $type == "location_user") {

            $role = DB::table("roles")->where("id", "=", 9)->first(["role_name"]);
            $locationUser->provider_type = "location_user";
            $locationUser->role_name = is_object($role) ? $role->role_name : "";

            // $this->printR($locationUser,true);
        }
        if (is_object($locationUser) && $type == "Practice") {
            $role = DB::table("roles")->where("id", "=", 3)->first(["role_name"]);
            $locationUser->provider_type = "group";
            $locationUser->role_name = is_object($role) ? $role->role_name : "";
        }
        return $locationUser;
    }
    /**
     * get the user banking information
     *
     *
     * @param $userId
     */
    function bankingInformation($userId) {

        $key = env("AES_KEY");

        $tbl = "user_ddbankinginfo";

        return DB::table($tbl)

        ->select(
            DB::raw("AES_DECRYPT(cm_$tbl.account_name,'$key') AS account_name"),
            DB::raw("AES_DECRYPT(cm_$tbl.routing_number,'$key') AS routing_number"),
            DB::raw("AES_DECRYPT(cm_$tbl.account_number,'$key') AS account_number"),
            "bank_name","bank_address","bank_address2","state","city","zipcode","bank_phone",
            "bank_contact_person"
            )

        ->where("user_id","=",$userId)

        ->first();
    }
    /**
     * update the provider data
     *
     * @param $ids
     * @param $updateData
     */
    function updateProviders($ids, $updateData)
    {
        return DB::table("user_dd_individualproviderinfo")
            //->where("parent_user_id","=",$pranetId)
            ->whereIn("id", $ids)
            ->update($updateData);
    }
    /**
     * add or update the location connected users
     *
     * @param $connectedUsers
     * @param $locationUser
     */
    function addOrUpdateLocationUser($connectedUsers, $locationUser)
    {
        $hasCount = DB::table("individualprovider_location_map")

            ->where("location_user_id", "=", $locationUser)

            ->count();

        // $indLocMap = DB::table("individualprovider_location_map")

        // ->where("location_user_id","=",$locationUser)

        // ->get();

        if ($hasCount) {

            DB::table("individualprovider_location_map")

                ->where("location_user_id", "=", $locationUser)

                ->delete();

            $userLocationMap = [];
            foreach ($connectedUsers as $user) {
                array_push($userLocationMap, ["user_id" => $user, "location_user_id" => $locationUser, 'for_credentialing' => 1]);
            }
            return DB::table("individualprovider_location_map")->insert($userLocationMap);
        } else {
            $userLocationMap = [];
            foreach ($connectedUsers as $user) {
                array_push($userLocationMap, ["user_id" => $user, "location_user_id" => $locationUser, 'for_credentialing' => 1]);
            }
            return DB::table("individualprovider_location_map")->insert($userLocationMap);
        }
    }
    /**
     * add user to location
     *
     *
     * @param $newProviderId
     * @param $locationUserId
     */
    function addUserToLocation($newProviderId, $locationUserId)
    {

        $userExist = DB::table("individualprovider_location_map")

            ->where("location_user_id", "=", $locationUserId)

            ->where("user_id", "=", $newProviderId)

            ->count();
        if ($userExist == 0) {
            return DB::table("individualprovider_location_map")->insertGetId(
                [
                    "user_id" => $newProviderId,
                    "location_user_id" => $locationUserId,
                    "created_at" => $this->timeStamp(),
                ]
            );
        }
    }
    /**
     * connect user with role
     *
     * @param $userId
     *
     */
    function connectUserWithRole($userId, $roleId)
    {

        $hasRole = DB::table("user_role_map")

            ->where("user_id", "=", $userId)

            ->where("role_id", "=", $roleId)

            ->count();

        if ($hasRole == 0) {
            DB::table("user_role_map")
                ->insertGetId(["user_id" => $userId, "role_id" => $roleId]);
        }
    }
    /**
     * fetch the owned locations
     *
     * @param $userId
     */
    function fetchOwnedLocations($userId)
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;
        $sql = "SELECT pli.user_id, pli.index_id,AES_DECRYPT(pli.primary_correspondence_address,'$key') as primary_correspondence_address,pli.office_manager_name,AES_DECRYPT(pli.npi,'$key') as npi,AES_DECRYPT(pli.practice_name,'$key') as practice_name
        FROM `$tbl` pli
        WHERE pli.user_parent_id IN (SELECT parent_user_id
                                    FROM `cm_user_ddownerinfo`
                                    WHERE user_id = '$userId')
        ORDER BY pli.user_id, pli.index_id;";
        return $this->rawQuery($sql);
    }
    /**
     * make the custom pagination for view
     *
     *
     */
    function makePagination($page, $perPage, $offset, $totalRec)
    {

        try {
            try {
                $numOfPage = $perPage > 0 ? ceil($totalRec / $perPage) : 0;
            } catch (\Exception $e) {
                $numOfPage = 0;
            }
            if ($offset <= $numOfPage) {
                $to = $totalRec > 10 ? $page * $perPage : $totalRec;
                if ($to > $totalRec) {
                    $to = $totalRec;
                }
                $from = ($offset * $perPage) + 1;
                if ($from > $totalRec) {
                    $diff = $from - $totalRec;
                    $from = ($totalRec - $diff) + 2;
                }
            } else {
                $to = null;
                $from = null;
            }
            return ["last_page" => $numOfPage, "per_page" => $perPage, "total" => $totalRec, "to" => $to, "from" => $from, "current_page" => $page];
        } catch (\Exception $e) {
            return ["last_page" => 0, "per_page" => 0, "total" => 0, "to" => 0, "from" => 0, "current_page" => 0];
        }
    }
    /**
     * make the sql query filter string
     *
     *
     * @param $filter
     * @param $columns
     */
    function sqlFilterString($filter, $columns)
    {
        $breakLater = explode(" ", $filter);
        $filterString = "";
        foreach ($columns as $key => $singleFilter) {

            if (count($breakLater) > 1) {

                $filterString .= "( ";
                foreach ($breakLater as $key2 => $eachLater) {
                    if ($key2 + 1 != count($breakLater))
                        $filterString .= $singleFilter . " LIKE '$eachLater%' OR ";
                    if ($key2 + 1 == count($breakLater))
                        $filterString .= $singleFilter . " LIKE '$eachLater%'";
                }
                if ($key + 1 != count($columns))
                    $filterString .= " ) OR ";
                if ($key + 1 == count($columns))
                    $filterString .= " )";
            } else {
                if ($key + 1 != count($columns))
                    $filterString .= $singleFilter . " LIKE '$filter%' OR ";
                if ($key + 1 == count($columns))
                    $filterString .= $singleFilter . " LIKE '$filter%' ";
            }
        }

        return $filterString;
    }
    function sqlAndFilterString($filter, $columns)
    {
        $breakLater = explode(" ", $filter);
        $filterString = "";
        foreach ($columns as $key => $singleFilter) {

            if (count($breakLater) > 1) {

                $filterString .= "( ";
                foreach ($breakLater as $key2 => $eachLater) {
                    if ($key2 + 1 != count($breakLater))
                        $filterString .= $singleFilter . " LIKE '%$eachLater%' OR ";
                    if ($key2 + 1 == count($breakLater))
                        $filterString .= $singleFilter . " LIKE '%$eachLater%'";
                }
                if ($key + 1 != count($columns))
                    $filterString .= " ) AND ";
                if ($key + 1 == count($columns))
                    $filterString .= " )";
            } else {
                if ($key + 1 != count($columns))
                    $filterString .= $singleFilter . " LIKE '%$filter%' OR ";
                if ($key + 1 == count($columns))
                    $filterString .= $singleFilter . " LIKE '%$filter%' ";
            }
        }

        return $filterString;
    }
    /**
     * get the user agains the token
     *
     *
     * @param $token
     */
    function fetchUserAgainstToken($token)
    {
        $tbl = "users";

        $key = env("AES_KEY");

        return DB::table("personal_access_tokens")->where("token", $token)

            ->select($tbl.".id", $tbl.".first_name", $tbl.".last_name",DB::raw("AES_DECRYPT(cm_".$tbl.".email, '$key') AS email"),
            $tbl.".gender", $tbl.".cnic",DB::raw("AES_DECRYPT(cm_".$tbl.".phone, '$key') AS phone"),
            "user_company_map.company_id", "user_role_map.role_id", "attachments.field_value as profile_image")

            ->leftJoin($tbl, $tbl.".id", "=", "personal_access_tokens.tokenable_id")

            ->leftJoin("user_company_map", "user_company_map.user_id", "=", $tbl.".id")

            ->leftJoin("user_role_map", "user_role_map.user_id", "=", $tbl.".id")

            ->leftJoin("attachments", function ($join) use($tbl) {
                $join->on("attachments.entity_id", "=", $tbl.".id")
                    ->where("attachments.entities", "=", "user_id");
            })

            ->first();
    }
    /**
     * fetch the user id against the token
     * 
     */
    function fetchUserIdAgainstToken($token) {
        return DB::table("personal_access_tokens")->where("token", $token)
        ->select("tokenable_id as session_userid")
        ->orderBy("id", "DESC")
        ->first();
    }
    /**
     * fetch the companies
     */
    function fetchCompanies()
    {
        return  DB::table("companies")
            ->get();
    }
    /**
     * fetch the roles
     */
    function fetchRoles()
    {
        return  DB::table("roles")
            ->get();
    }
    /**
     * manage the login of the app
     *
     *
     * @param $email
     */
    function doLogin($email)
    {

        $user =  DB::table("users")

            ->select(
                "users.first_name",
                "users.last_name",
                "users.email",
                "users.id",
                "roles.role_name as role",
                "roles.role_short_name as role_name",
                "personal_access_tokens.token",
                "user_baf_practiseinfo.legal_business_name",
                "roles.id as role_id"
            )

            ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

            ->leftJoin("user_role_map", "user_role_map.user_id", "=", "users.id")

            ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

            ->leftJoin("personal_access_tokens", "personal_access_tokens.tokenable_id", "users.id")

            ->leftJoin("user_baf_practiseinfo", "user_baf_practiseinfo.user_id", "users.id")

            ->where("users.email", "=", $email)

            ->first();

        return $user;
    }
    /**
     * fetch the cohart user against the user
     *
     * @param $userId
     */
    function sysUsers($roleId = [6, 7, 12, 13])
    {
        //echo "working:".$userId;
        // return DB::table("user_role_map")

        //     ->select("users.id", DB::raw("CONCAT(cm_users.first_name,' ', cm_users.last_name) as full_name"), "roles.role_name")

        //     ->join("users", "users.id", "=", "user_role_map.user_id")

        //     ->leftJoin("user_company_map", "user_company_map.user_id", "=", "user_role_map.id")

        //     ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

        //     ->whereIn("user_role_map.role_id", $roleId)

        //     ->get();

        $queryResult = DB::table('emp_location_map AS elp')
        ->join('users AS u', 'u.id', '=', 'elp.emp_id')
        ->select('u.id', DB::raw("concat(cm_u.first_name, ' ', cm_u.last_name) as full_name"))
        ->where('elp.location_user_id', '!=', 0)
        ->where('u.deleted', 0)
        ->groupBy('elp.emp_id')
        ->get();
        return $queryResult;
    }
    /**
     * fetch the creds users
     *
     * @param $userId
     */
    function credsUsers($userId = 0)
    {
        //echo "working:".$userId;
        return DB::table("user_role_map")

            ->select("users.id", DB::raw("CONCAT(cm_users.first_name,' ', cm_users.last_name) as full_name"), "roles.role_name")

            ->join("users", "users.id", "=", "user_role_map.user_id")

            ->leftJoin("user_company_map", "user_company_map.user_id", "=", "user_role_map.id")

            ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

            ->whereIn("user_role_map.role_id", [6, 7])

            ->get();
    }
    /**
     * assign the task to users
     *
     * @param $userId
     */
    function assignTask($taskId)
    {
        $users = $this->credsUsers();

        foreach ($users as $user) {
            // $alreadyExist =  DB::table("assignments")->where("entities","=","credentialingtask_id")
            // ->where("entity_id","=",$taskId)
            // ->where("user_id","=",$user->id)
            // ->count();
            // if($alreadyExist == 0)
            {
                DB::table("assignments")->insertGetId(
                    [
                        "entities" => "credentialingtask_id",
                        "entity_id" => $taskId,
                        "user_id" => $user->id
                    ]
                );
            }
        }
    }
    /**
     * check valid json
     *
     * @param $string
     */
    function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    /**
     * fetch the all active locations
     *
     *
     */
    function fetchAllActiveLocations()
    {
        $key = $this->key;
        $tbl = $this->tbl;
        return DB::table($tbl." as pli")
            ->select(DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as doing_buisness_as"), "pli.user_id as facility_id")
            ->join("users as u", function ($join) {
                $join->on("pli.user_id", "=", "u.id")
                    ->where("u.deleted", "=", 0);
            })
            ->get();
    }
    /**
     * update the afflieted contacts of the location
     *
     *
     */
    function updateAffliatedContacts($locationUserId, $status)
    {
        return DB::table("individualprovider_location_map")->where("location_user_id", "=", $locationUserId)->update(["for_credentialing" => $status]);
    }
    /**
     * check if array contains specific value
     *
     *
     * @param $array
     * @param $string
     */
    function arrayContains($array, $string, $col)
    {
        foreach ($array as $key => $value) {
            if ($value[$col] == $string) {
                return true;
            }
        }
        return false;
    }
    /**
     * fetch the facilities of the Practice
     *
     * @param $practiceId
     */
    function getFacilities($practiceId)
    {
        $key = $this->key;
        $tbl = "cm_".$this->tbl;
        $sql = "SELECT T.facility_id, T.facility_name, if(T.is_expandable > 0, '1', '0') is_expandable
        FROM(
        SELECT user_id as facility_id, AES_DECRYPT(practice_name,'$key') as facility_name,
        (SELECT COUNT(user_id) FROM cm_individualprovider_location_map WHERE location_user_id = $practiceId GROUP BY location_user_id) as is_expandable
        FROM $tbl
        WHERE user_parent_id = $practiceId
        ) AS T";
        return $this->rawQuery($sql);
    }
    /**
     * licenses type of specific users
     *
     * @param $userId
     */
    function fetchLicenseTypes($userId)
    {

        // return DB::table("user_licenses as ul")

        // ->select('ul.type_id', 'ul.user_id', 'lt.name')

        // ->join("license_types as lt","lt.id","=","ul.type_id")

        // ->where('ul.user_id','=',$userId)

        // ->groupBy('ul.type_id')

        // ->get();

        return DB::table("license_types")

            ->select('id as type_id', DB::raw('' . $userId . ' as user_id'), 'name')

            ->orderBy('id')

            ->get();
    }
    /**
     * get the license type expiration summary
     *
     *
     * @param $userId
     * @param $type
     */
    function getLicenseTypeExpiration($userId, $type, $licenseType = "")
    {
        /*
        $groupBy = "T.license_no";
        $subQuery = "(SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$type' AND ul.user_id = '$userId' AND ul.license_no = cm_user_licenses.license_no AND cm_user_licenses.is_delete=0)";
        if (is_object($licenseType)) {
            if ($licenseType->versioning_type == "number") {
                $subQuery = "(SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$type' AND ul.user_id = '$userId' AND ul.license_no = cm_user_licenses.license_no AND cm_user_licenses.is_delete=0)";
                $groupBy = "T.license_no";
            }
            if ($licenseType->versioning_type == "name") {
                $subQuery = "(SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$type' AND ul.user_id = '$userId' AND ul.name = cm_user_licenses.name AND cm_user_licenses.is_delete=0)";
                $groupBy = "T.name";
            }
        }
        $sql = "SELECT T.expired,T.close_to_expiring,T.notify_before_exp,
        IF(DATEDIFF(T.diff_days,CURDATE()) <= T.notify_before_exp,'1','0') AS has_warning,
        IF(T.notify_before_exp > 0 ,'1','0') AS has_reminders,
        T.id,
        T.exp_date,
        T.license_no,
        T.name
        FROM (SELECT
        id,
        IF(exp_date < CURDATE(),'1','0') AS expired ,
        IF(exp_date > CURDATE() AND notify_before_exp >=1 AND DATE_SUB(exp_date, INTERVAL notify_before_exp DAY) < CURDATE(),'1','0') AS close_to_expiring,
        DATE_SUB(exp_date, INTERVAL notify_before_exp DAY) as diff_days,
        exp_date,
        notify_before_exp,
        license_no,
        name
        FROM `cm_user_licenses` WHERE cm_user_licenses.document_version = $subQuery) as T GROUP BY $groupBy";
        
        return $this->rawQuery($sql);
        /* */
        $groupBy = "T.license_no";
        if (is_object($licenseType)) {
            if ($licenseType->versioning_type == "number") {
                $groupBy = "T.license_no";
            }
            if ($licenseType->versioning_type == "name") {
                $groupBy = "T.name";
            }
        }
        $result = DB::table('user_licenses')
        ->select(
            'T.expired',
            'T.close_to_expiring',
            'T.notify_before_exp',
            DB::raw("IF(DATEDIFF(cm_T.diff_days, CURDATE()) <= cm_T.notify_before_exp, '1', '0') AS has_warning"),
            DB::raw("IF(cm_T.notify_before_exp > 0, '1', '0') AS has_reminders"),
            'T.id',
            'T.exp_date',
            'T.license_no',
            'T.name',
            'T.diff_days'
        )
        ->from(function ($query) use($userId,$type) {
            $query->select(
                'ul.id',
                DB::raw("IF(cm_ul.exp_date < CURDATE(), '1', '0') AS expired"),
                DB::raw("IF(cm_ul.exp_date > CURDATE() AND cm_ul.notify_before_exp >= 1 AND DATE_SUB(cm_ul.exp_date, INTERVAL cm_ul.notify_before_exp DAY) < CURDATE(), '1', '0') AS close_to_expiring"),
                DB::raw("DATE_SUB(cm_ul.exp_date, INTERVAL cm_ul.notify_before_exp DAY) AS diff_days"),
                'ul.exp_date',
                'ul.notify_before_exp',
                'ul.license_no',
                'ul.name'
            )
            ->from('user_licenses as ul')
            // ->where('ul.document_version', function ($subquery) use($userId,$type) {
            //     $subquery->select(DB::raw('MAX(document_version)'))
            //         ->from('user_licenses')
            //         ->where('type_id', $type)
            //         ->where('user_id', $userId)
            //         ->whereColumn('ul.license_no', 'user_licenses.license_no')
            //         ->where('user_licenses.is_delete', 0)
            //         ->where('user_licenses.is_current_version', 0);
            // })
            ->where('ul.user_id','=',$userId)
            ->where('ul.type_id','=',$type)
            ->where('ul.is_delete', 0)
            ->where('ul.is_current_version', 1);
        }, 'T')
        ->groupBy($groupBy)
        ->get();
        // dd($result);
        return $result;
    }
    /**
     * get the license type expiration summary
     *
     *
     * @param $userId
     * @param $type
     */
    function getUserDocumentsStats($userId)
    {
        
        // $groupBy = "T.license_no";
        // if (is_object($licenseType)) {
        //     if ($licenseType->versioning_type == "number") {
        //         $groupBy = "T.license_no";
        //     }
        //     if ($licenseType->versioning_type == "name") {
        //         $groupBy = "T.name";
        //     }
        // }
        $result = DB::table('user_licenses')
        ->select(
            'T.expired',
            'T.close_to_expiring',
            'T.notify_before_exp',
            DB::raw("IF(DATEDIFF(cm_T.diff_days, CURDATE()) <= cm_T.notify_before_exp, '1', '0') AS has_warning"),
            DB::raw("IF(cm_T.notify_before_exp > 0, '1', '0') AS has_reminders"),
            'T.id',
            'T.exp_date',
            'T.license_no',
            'T.name',
            'T.diff_days',
            DB::raw("(SELECT name FROM `cm_license_types` WHERE id = cm_T.type_id LIMIT 0,1) as license_name")
        )
        ->from(function ($query) use($userId) {
            $query->select(
                'ul.id',
                DB::raw("IF(cm_ul.exp_date < CURDATE(), '1', '0') AS expired"),
                DB::raw("IF(cm_ul.exp_date > CURDATE() AND cm_ul.notify_before_exp >= 1 AND DATE_SUB(cm_ul.exp_date, INTERVAL cm_ul.notify_before_exp DAY) < CURDATE(), '1', '0') AS close_to_expiring"),
                DB::raw("DATE_SUB(cm_ul.exp_date, INTERVAL cm_ul.notify_before_exp DAY) AS diff_days"),
                'ul.exp_date',
                'ul.notify_before_exp',
                'ul.license_no',
                'ul.name',
                'ul.type_id'
            )
            ->from('user_licenses as ul')
            
            ->where('ul.user_id','=',$userId)
            //->where('ul.type_id','=',$type)
            ->where('ul.is_delete', 0)
            ->where('ul.is_current_version', 1);
        }, 'T')
        // ->groupBy($groupBy)
        ->get();
        // dd($result);
        return $result;
    }
    /**
     * get the license type expiration summary
     *
     *
     * @param $userId
     * @param $type
     */
    function getMiscellaneousExpiration($userId)
    {
        $sql = "SELECT T.expired,T.close_to_expiring,
        '0' AS has_warning,
        '0' AS has_reminders,
        T.id,
        T.field_value,
        T.field_key
        FROM (SELECT
        id,
        '0' AS expired ,
        '0' AS close_to_expiring,
        '0' AS diff_days,
        field_value,
        field_key
        FROM `cm_attachments` WHERE entity_id = $userId AND entities = 'provider_id' AND visibility = 1) as T";

        return $this->rawQuery($sql);
    }
    /**
     * fetch the insurance coverage expiration
     *
     * @param $userId
     */
    function getInsuranceCoverageExpiration($userId)
    {
        $sql = "SELECT T.expired,T.close_to_expiring,T.notify_before_exp,
        IF(DATEDIFF(T.diff_days,CURDATE()) <= T.notify_before_exp,'1','0') AS has_warning,
        IF(T.notify_before_exp > 0 ,'1','0') AS has_reminders,
        T.id,
        T.exp_date,
        T.policy_number
        FROM (SELECT
        id,
        IF(expiration_date < CURDATE(),'1','0') AS expired ,
        IF(expiration_date > CURDATE() AND notify_before_exp >=1 AND DATE_SUB(expiration_date, INTERVAL notify_before_exp DAY) < CURDATE(),'1','0') AS close_to_expiring,
        DATE_SUB(expiration_date, INTERVAL notify_before_exp DAY) as diff_days,
        expiration_date as exp_date,
        notify_before_exp,
        policy_number
        FROM `cm_insurance_coverage` WHERE cm_insurance_coverage.id = (SELECT MAX(id) FROM `cm_insurance_coverage`as ic WHERE ic.user_id = '$userId' AND ic.policy_number = cm_insurance_coverage.policy_number)) as T GROUP BY T.policy_number";

        return $this->rawQuery($sql);
    }
    /**
     * make the log msg form given data
     *
     * @param $newData
     * @param $oldData
     */
    function makeTheLogMsg($sessionUserName, $newData, $oldData)
    {

        $logData = "";
        if (count($newData)  > 0 && !is_null($newData)) {

            foreach ($newData  as $key => $val) {
                // echo $key;
                // exit;
                if (isset($oldData[$key]) && $oldData[$key] != $val && ($key != "created_at" && $key != "updated_at")) {
                    $logData .= "<b>" . $sessionUserName . "</b> change colum <strong>" . $key . "</strong> <b>" . $oldData[$key] . "</b> changed to  <b>" . $val . "</b> <br/>";
                }
            }
        } else {
            foreach ($newData  as $key => $val) {
                if ($key != "created_at" && $key != "updated_at")
                    $logData .= "<b>" . $sessionUserName . "</b> Added colum <strong>" . $key . "</strong>  with value  <b>" . $val . "</b> <br/>";
            }
        }
        return $logData;
    }
    /**
     * add new data log
     * @param $sessionUserName
     */
    function addNewDataLogMsg($sessionUserName, $entityName = "")
    {
        return "<b>" . $sessionUserName . "</b> has added " . $entityName . " record";
    }
    /**
     * data data log
     * @param $sessionUserName
     */
    function delDataLogMsg($sessionUserName, $entityName)
    {
        return "<b>" . $sessionUserName . "</b> has deleted " . $entityName . " record";
    }
    /**
     * fetch the session user name and id
     *
     * @param $request
     * @param $sessionUserId
     */
    function getSessionUserName($request, $sessionUserId = 0)
    {
        $sessionUserName = 'System';
        // if(isset($request->user)) {
        //     $sessionUserId = $request->user->id;
        //     $sessionUserName = $request->user->first_name . ' ' . $request->user->last_name;
        // }
        // else
        {

            $user = $this->fetchData("users", ['id' => $sessionUserId], 1, ['first_name', 'last_name']);

            $sessionUserName = is_object($user) ? $user->first_name . ' ' . $user->last_name : "Unknown";
        }
        return $sessionUserName;
    }
    /**
     * fetch the session user name and id
     *
     * @param $request
     * @param $sessionUserId
     */
    function getSessionUserId($request)
    {
        // $sessionUserId = 0;
        // if(isset($request->user)) {
        //     $sessionUserId = $request->user->id;
        // }
        // return $sessionUserId;
        // $accessToken = $this->getAccessTokenFromRequest($request);
        $token = $request->bearerToken();
        $userToken = DB::table("personal_access_tokens")

            ->select('tokenable_id')

            ->where('token', $token)
            
            ->orderBy('id', 'desc')

            ->first();

        if (is_object($userToken))
            return $userToken->tokenable_id;
        else
            return 0;
    }
    /**
     * @param Request $request
     * @return $accessToken
     */
    function getAccessTokenFromRequest($request)
    {

        $bearerToken = $request->header('Authorization');
        $token = 0;
        if (strpos($bearerToken, 'Bearer ') === 0) {
            $token = str_replace('Bearer ', '', $bearerToken);
        }
        return $token;
    }
    /**
     * get the user role by user id
     *
     * @param $userId
     */
    function getUserRole($userId)
    {

        return DB::table("user_role_map")

            ->select('role_id')

            ->where('user_id', $userId)

            ->first();
    }
    /**
     * user has multiple roles
     *
     * @param $userId
     */
    function userHasMultiRoles($userId)
    {

        return DB::table("user_role_map")

            ->select('role_id')

            ->where('user_id', $userId)

            ->get();
    }
    /**
     * get user name by user id
     */
    function getUserNameById($userId)
    {

        $user = $this->fetchData("users", ['id' => $userId], 1, ['first_name', 'last_name']);

        $userName = is_object($user) ? $user->first_name . ' ' . $user->last_name : "Unknown";

        return $userName;
    }
    /**
     * remove the white spaces from string
     *
     * @param $string
     */
    function removeWhiteSpaces($string)
    {
        return preg_replace('/\s+/', '_', $string);
    }
    /**
     * fetch the Education releted count for licenses
     *
     * @param $userId
     * @param $type
     */
    function eduTypesCount($userId, $type)
    {
        return DB::table('education')
            ->where('user_id', '=', $userId)
            ->where('is_delete', '=', 0)
            ->where('education_type', '=', $type)
            ->count();
    }
    /**
     * fetch the Education releted count for hospital affliation
     *
     * @param $userId
     */
    function hospitalAffliationCount($userId)
    {
        return DB::table('hospital_affiliations')
            ->where('user_id', '=', $userId)
            ->where('is_delete', '=', 0)
            ->count();
    }
    /**
     * fetch the Education releted count for hospital affliation
     *
     * @param $userId
     */
    function malPracticeCoverageCount($userId)
    {
        return DB::table('insurance_coverage')
            ->where('user_id', '=', $userId)
            ->where('is_delete', '=', 0)
            ->where('for_category', '=', 'malpractice_coverage')
            ->groupBy('policy_number')
            ->get();
    }
    /**
     * fetch the Education releted count for hospital affliation
     *
     * @param $userId
     */
    function sheltersCount($userId)
    {
        return DB::table('shelter_facility_map')
            ->join("shelters", "shelters.id", "=", "shelter_facility_map.shelter_id")
            ->where('shelter_facility_map.facility_id', '=', $userId)
            ->count();
    }
    /**
     * fetch the sharing document sub categories
     *
     * @param $typeId
     * @param $userId
     */
    function licenseSubTypes($userId, $parentTypeId, $isFor)
    {
        // $sql = "SELECT T.id, T.name, T.sort_by
        // FROM (
        //     (select lt.id, lt.name, sort_by
        //     from `cm_license_types` lt
        //     where lt.parent_type_id = $parentTypeId
        //     AND lt.is_for IN('$isFor','Both')
        //     AND exists (SELECT 1 FROM `cm_user_licenses` WHERE user_id = '$userId' AND type_id = lt.id)
        //     order by lt.sort_by)
        // UNION ALL
        //     (select lt.id, lt.name, lt.sort_by
        //     from `cm_license_types` lt
        //     where lt.parent_type_id = $parentTypeId
        //     AND lt.is_for IN('$isFor','Both')
        //     AND exists (SELECT 1 FROM `cm_education` WHERE user_id = '$userId' AND type_id = lt.id)
        //     order by lt.sort_by)
        // UNION ALL
        //     (select lt.id, lt.name, lt.sort_by
        //     from `cm_license_types` lt
        //     where lt.parent_type_id = $parentTypeId
        //     AND lt.is_for IN('$isFor','Both')
        //     AND exists (SELECT 1 FROM `cm_hospital_affiliations` WHERE user_id = '$userId' AND type_id = lt.id)
        //     order by lt.sort_by)
        // UNION ALL
        //     (select lt.id, lt.name, lt.sort_by
        //     from `cm_license_types` lt
        //     where lt.parent_type_id = $parentTypeId
        //     AND lt.is_for IN('$isFor','Both')
        //     AND exists (SELECT 1 FROM `cm_insurance_coverage` WHERE user_id = '$userId' AND type_id = lt.id)
        //     order by lt.sort_by)
        // ) AS T
        // ORDER BY T.sort_by";
        $sql = "
        SELECT T.id, T.name, T.sort_by
        FROM (
            (select lt.id, lt.name, sort_by
            from `cm_license_types` lt
            where lt.parent_type_id = $parentTypeId
            AND lt.is_for IN('$isFor','Both')
            AND exists (SELECT 1 FROM `cm_user_licenses` ul
					    INNER JOIN `cm_attachments` a
            			ON a.entities = 'license_id' AND a.entity_id = ul.id
						WHERE ul.user_id = '$userId' AND ul.type_id = lt.id)
            order by lt.sort_by)
        UNION ALL
            (select lt.id, lt.name, lt.sort_by
            from `cm_license_types` lt
            where lt.parent_type_id = $parentTypeId
            AND lt.is_for IN('$isFor','Both')
            AND exists (SELECT 1 FROM `cm_education` e
            			INNER JOIN `cm_attachments` a
            			ON a.entities = 'education' AND a.entity_id = e.id
                        WHERE e.user_id = '$userId' AND e.type_id = lt.id)
            order by lt.sort_by)
       UNION ALL
            (select lt.id, lt.name, lt.sort_by
            from `cm_license_types` lt
            where lt.parent_type_id = $parentTypeId
            AND lt.is_for IN('$isFor','Both')
            AND exists (SELECT 1 FROM `cm_hospital_affiliations` h
						INNER JOIN `cm_attachments` a
            			ON a.entities = 'hospital' AND a.entity_id = h.id
						WHERE h.user_id = '$userId' AND h.type_id = lt.id)
            order by lt.sort_by)
        UNION ALL
            (select lt.id, lt.name, lt.sort_by
            from `cm_license_types` lt
            where lt.parent_type_id = $parentTypeId
            AND lt.is_for IN('$isFor','Both')
            AND exists (SELECT 1 FROM `cm_insurance_coverage` ic
						INNER JOIN `cm_attachments` a
            			ON a.entities = 'coverage' AND a.entity_id = ic.id
						WHERE ic.user_id = '$userId' AND ic.type_id = lt.id)
            order by lt.sort_by)
        ) AS T
        ORDER BY T.sort_by
        ";
        return $this->rawQuery($sql);
    }
    /**
     * fetch license type of attachments
     *
     * @param $userId
     * @param $typeId
     */
    function licenseSubTypesAttachments($userId, $typeId)
    {
        $sql = "SELECT T.id, T.directory, T.tble, T.filename, T.created_at
        FROM(
            (select ul.id, 'licenses' as directory, 'licenses' as tble, a.field_value as filename, ul.created_at
            from `cm_user_licenses` ul
            LEFT JOIN `cm_attachments` a
            ON a.entities = 'license_id' AND a.entity_id = ul.id
            where ul.document_version = (SELECT MAX(document_version)
                        FROM `cm_user_licenses`
                        WHERE type_id = '$typeId' AND user_id = '$userId' AND license_no = ul.license_no)
            group by ul.`license_no`
            order by ul.`created_at`)
        UNION ALL
            (SELECT e.id, 'licenses' as directory, 'education' as tble, a.field_value as filename, e.created_at
            FROM `cm_education` e
            LEFT JOIN `cm_attachments` a
            ON a.entities = 'education' AND a.entity_id = e.id
            WHERE e.type_id = '$typeId'
            AND e.user_id = '$userId'
            ORDER BY e.created_at)
        UNION ALL
            (SELECT h.id, 'licenses' as directory, 'hospital_affiliations' as tble, a.field_value as filename, h.created_at
            FROM `cm_hospital_affiliations` h
            LEFT JOIN `cm_attachments` a
            ON a.entities = 'hospital' AND a.entity_id = h.id
            WHERE h.type_id = '$typeId'
            AND h.user_id = '$userId'
            ORDER BY h.created_at)
        UNION ALL
            (SELECT i.id, 'coverage' as directory, 'cm_insurance_coverage' as tble, a.field_value as filename, i.created_at
            FROM `cm_insurance_coverage` i
            LEFT JOIN `cm_attachments` a
            ON a.entities = 'coverage' AND a.entity_id = i.id
            WHERE i.type_id = '$typeId'
            AND i.user_id = '$userId'
            ORDER BY i.created_at)
        ) AS T
        ORDER BY T.created_at;
        ";
        return $this->rawQuery($sql);
    }
    /**
     * check the date format
     *
     * @param
     */
    function checkDateFormat($dateString, $format = 'm/d/Y')
    {

        $dateStr =  date('m/d/Y', strtotime($dateString));

        $date = \DateTime::createFromFormat($format, $dateString);

        if ($date && $date->format($format) == $dateStr)
            return true;
        else {
            $dateStr =  date('Y-m-d', strtotime($dateString));

            $date = \DateTime::createFromFormat($format, $dateString);

            if ($date && $date->format($format) == $dateStr)
                return true;
            else
                return false;
        }
            
    }
    /**
     * get the file size units
     *
     * @param $fileSize
     */
    public function fileSizeUnits($fileSize)
    {
        if ($fileSize < 1024) {
            return "$fileSize bytes";
        } elseif ($fileSize < 1048576) {
            $size_kb = round($fileSize / 1024);
            return "$size_kb KB";
        } else {
            $size_mb = round($fileSize / 1048576, 1);
            return "$size_mb MB";
        }
    }
    /**
     * time difference for human readable
     *
     * @param $timeStamp
     */
    function humanReadableTimeDifference($timeStamp)
    {
        return Carbon::parse($timeStamp)->diffForHumans(['parts' => 2]);
    }
    /**
     * time difference for human readable
     *
     * @param $timeStamp
     */
    function fetchAMPMFromTS($timestamp)
    {
       // Convert the timestamp to a Carbon instance
        $carbonInstance = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp);

        // Get the AM/PM information
        $amOrPm = $carbonInstance->format('A');

        return $amOrPm;

    }
    /**
     * make the db column readable
     *
     * @param $string
     */
    function dbColumnReadable($string)
    {
        $string = str_replace('_', ' ', $string); // Replace underscores with spaces
        //$string = ucwords($string); // Capitalize the first letter of each word
        $capitalizedString = strtoupper($string[0]) . substr($string, 1);

        return $capitalizedString;
    }
    /**
     * remove first _ from string
     *
     * @param $str
     */
    function removeUnderScore($str)
    {
        try {
            $explodAr = explode("_", $str);
            $newStr = "";
            foreach ($explodAr as $key => $eachPart) {
                if ($key > 0) {
                    if ($newStr == "")
                        $newStr = $eachPart;
                    else
                        $newStr .= "_" . $eachPart;
                }
            }
            return $newStr;
        } catch (\Exception $e) {
            return NULL;
        }
    }
    /**
     * re arrange the numeric array , remove the decimal values
     *
     * @param array $array
     */
    function removeDecimalValues($originalArray)
    {

        // New array to store values after decimal
        $facility = [];
        $shelter = [];

        // Loop over each element in the original array
        foreach ($originalArray as $value) {
            // Split the number into two parts based on the decimal point
            $parts = explode(".", $value);

            // If there is a second part, extract it and convert it to an integer
            if (isset($parts[1])) {
                $decimalValue = intval($parts[1]);
                // Add the decimal value to the new array
                $shelter[] = $decimalValue;
            } else {
                $facility[] = $value;
            }
        }
        return ["facility" => $facility, "shelter" => $shelter];
    }
    /**
     * remove the dot value from the string
     *
     * @param string $string
     */
    function removeDecimalFromString($string)
    {

        if (strpos($string, ".") !== false) {
            $parts = explode(".", $string);
            return $parts[1];
        } else
            return  $string;
    }
    /**
     * check for shelter
     *
     * @param $facilityId
     */
    function chkShelter($facilityId, $shelterId)
    {
        return DB::table('shelter_facility_map')
            ->where('facility_id', '=', $facilityId)
            ->where('shelter_id', '=', $shelterId)
            ->count();
    }
    /**
     * get the user access token
     *
     * @param $userId
     */
    function getUserAccessToken($userId)
    {
        return  DB::table('personal_access_tokens')
            ->where('tokenable_id', '=', $userId)
            ->first(["token"]);
    }
    /**
     * generate the hexa code color
     */
    function generateHexaColor()
    {
        // Generate a random number between 0 and 16777215.
        $rand_num = rand(0, 16777215);

        // Convert the random number to a hexadecimal value.
        $hex_color = dechex($rand_num);

        // Pad the hexadecimal value with zeros to the left so that it is 6 characters long.
        $hex_color = str_pad($hex_color, 6, "0", STR_PAD_LEFT);

        // Return the hexadecimal color.
        return "#" . $hex_color;
    }
    /**
     * fetch payer color
     *
     * @param $payerName
     */
    function fetchPayerColor($payerName)
    {
        $color = DB::table('payers')
            ->where('payer_name', '=', $payerName)
            ->first(['color']);
        return isset($color->color) ? $color->color : "black";
    }
    /**
     * billing data exist against the claim
     * @param $cliamNo
     */
    function billingDataExists($cliamNo)
    {

        return DB::table('billing')

            ->where("claim_no", "=", $cliamNo)

            ->count();
    }
    /**
     * get status name
     *
     * @param $statusId
     * @return Object
     */
    function getARStatusName($statusId)
    {

        return DB::table('revenue_cycle_status')

            ->where("id", "=", $statusId)

            ->first(['status']);
    }
    /**
     * get status name
     *
     * @param $statusName
     * @return Object
     */
    function getBillingStatusId($statusName)
    {

        return DB::table('revenue_cycle_status')

            ->where("status", "=", $statusName)

            ->first(['id']);
    }
    /**
     * fetch ar remarks with name
     *
     * @param $remarks
     * @return $result
     */
    function fetchARRemarks($remarks)
    {
        return DB::table('ar_remarks')

            ->where("id", "=", $remarks)

            ->first(['remarks']);
    }
    /**
     * fetch ar remarks with name
     *
     * @param $remarks
     * @return $result
     */
    function fetchBillingRemarks($remarks)
    {
        return DB::table('revenue_cycle_remarks')

            ->where("id", "=", $remarks)

            ->first(['remarks', 'id']);
    }
    /**
     * check status id is for deleted,archived
     *
     * @param $statusId
     */
    function chkStatusDeleted($statusArr)
    {

        return DB::table("revenue_cycle_status")

            ->select("id")

            ->whereIn("id", $statusArr)

            ->where("status", "=", "DELETED")

            ->count();
    }
    /**
     * check status id is for deleted,archived
     *
     * @param $statusId
     */
    function deleteStatusId()
    {

        return DB::table("revenue_cycle_status")

            ->select("id")

            //->whereIn("id",$statusArr)

            ->where("status", "=", "DELETED")

            ->first();
    }
    /**
     * check status remark
     *
     * @param $statusId
     */
    function deleteStatusRemarkId($statusId)
    {

        return DB::table("revenue_cycle_remarks_map")

            ->select("remark_id")

            //->whereIn("id",$statusArr)

            ->where("status_id", "=", $statusId)

            ->first();
    }
    /**
     * fetch system active users
     *
     * @param $userId
     */
    function sysActiveUsers($roleId = [6, 7, 12, 13])
    {
        //echo "working:".$userId;
        // return DB::table("user_role_map")

        //     ->select("users.id as value", DB::raw("CONCAT(cm_users.first_name,' ', cm_users.last_name) as label"))

        //     ->join("users", "users.id", "=", "user_role_map.user_id")

        //     ->leftJoin("user_company_map", "user_company_map.user_id", "=", "user_role_map.id")

        //     ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

        //     ->whereIn("user_role_map.role_id", $roleId)

        //     ->where("users.deleted", "=", 0)

        //     ->groupBy("users.id")

        //     ->get();

        $queryResult = DB::table('emp_location_map AS elp')
            ->join('users AS u', 'u.id', '=', 'elp.emp_id')
            ->select('u.id as value', DB::raw("concat(cm_u.first_name, ' ', cm_u.last_name) as label"))
            ->where('elp.location_user_id', '!=', 0)
            ->where('u.deleted', 0)
            ->groupBy('elp.emp_id')
            ->get();
        return $queryResult;
    }
    /**
     * fetch system active users
     *
     * @param $userId
     */
    function sysAllUsers($roleId = [6, 7, 12, 13])
    {
        //echo "working:".$userId;
        // return DB::table("user_role_map")

        //     ->select("users.id as value", DB::raw("CONCAT(cm_users.first_name,' ', cm_users.last_name) as label"))

        //     ->join("users", "users.id", "=", "user_role_map.user_id")

        //     ->leftJoin("user_company_map", "user_company_map.user_id", "=", "user_role_map.id")

        //     ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

        //     ->whereIn("user_role_map.role_id", $roleId)

        //     ->where("users.deleted", "=", 0)

        //     ->groupBy("users.id")

        //     ->get();

        $queryResult = DB::table('emp_location_map AS elp')
            ->join('users AS u', 'u.id', '=', 'elp.emp_id')
            ->select('u.id as value', DB::raw("concat(cm_u.first_name, ' ', cm_u.last_name) as label"))
            ->where('elp.location_user_id', '!=', 0)
            ->where('u.deleted', 0)
            ->groupBy('elp.emp_id')
            ->get();
        return $queryResult;
    }
    /**
     * Add the direcoty logs
     * 
     * @param $effectingUserId
     * @param $updatingUserId
     * @param $details
     */
    function addDirectoryLogs($effectingUserId, $updatingUserId, $details,$type) {
        
        $key =  $this->key;
        
        $addLog = [];
        
        $addLog['user_id']          = $effectingUserId;
        
        $addLog['session_userid']   = $updatingUserId;
        
        $addLog['details']          = DB::raw("AES_ENCRYPT('" .    $details     . "', '$key')");
        
        $addLog['type']             = $type;

        return DB::table('directory_logs')->insertGetId($addLog);
    }
    /**
     * check provider fall in any location
     * 
     * 
     * @param $providerId
     * @return  integer
     */
    function chkProfileFallInLocation($providerId) {
        return DB::table('individualprovider_location_map')
        ->where("user_id","=",$providerId)
        ->count();
    }

    function dateFormat($date) {
        $parts = explode('/',  $date);
        $formattedDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        return $formattedDate;
    }
    /**
     * set up smtp settings dynamically
     * 
     * 
     * @param $companyId
     */
    function setSMTPSettings($companyId) {
        
        $key = $this->key;

        $settings = DB::table('smtp_settings')
        
        ->where("company_id", "=", $companyId)
        
        ->first(["host","port","username","username","encryption",
        DB::raw("AES_DECRYPT(password,'$key') AS password"),'mail_from_address','mail_from_name']);
        //    dd($settings);
       
        if(is_object($settings) > 0) {

            $smtpConfig = $this->stdToArray($settings);
            
            // Set SMTP configuration dynamically
            // Config::set('mail.from.address', $smtpConfig['mail_from_address']);
            // Config::set('mail.from.name',  $smtpConfig['mail_from_name']);
            // Config::set('mail.host', $smtpConfig['host']);
            // Config::set('mail.port', $smtpConfig['port']);
            // Config::set('mail.username', $smtpConfig['username']);
            // Config::set('mail.password', $smtpConfig['password']);
            // Config::set('mail.encryption', $smtpConfig['encryption']);
            
            putenv("MAIL_DRIVER=mailgun");
            if(isset($smtpConfig['encryption']))
                putenv("MAIL_ENCRYPTION=".$smtpConfig['encryption']);
            if(isset($smtpConfig['mail_from_address']))
                putenv("MAIL_FROM_ADDRESS=".$smtpConfig['mail_from_address']);
            if(isset($smtpConfig['mail_from_name']))
                putenv("MAIL_FROM_NAME=".$smtpConfig['mail_from_name']);
            if(isset($smtpConfig['host']))
                putenv("MAIL_HOST=".$smtpConfig['host']);
            if(isset($smtpConfig['port']))
                putenv("MAIL_PORT=".$smtpConfig['port']);
            if(isset($smtpConfig['username']))
                putenv("MAIL_USERNAME=".$smtpConfig['username']);
            if(isset($smtpConfig['password']))
                putenv("MAIL_PASSWORD=".$smtpConfig['password']);
            

          

        }
    }
    /**
     * sanitize the phone number
     *
     * @param $phoneNumber
     * @return string
     */
    function sanitizePhoneNumber($phoneNumber) {
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }
    /**
     * active practice of the system
     * 
     * @param $sessionUserId
     * @return array
     */
    public function activePractices($sessionUserId) {
        $tbl        = "user_baf_practiseinfo";
        $tblU       = "users";
        $appTblL    = "user_ddpracticelocationinfo";

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

    // $this->printR($facilities,true);

    $practices = DB::table($appTblL . ' as pli')

        ->select('pli.user_parent_id as facility_id', DB::raw('IFNULL(cm_p.practice_name, cm_p.doing_business_as) AS doing_buisness_as'))

        ->join($tbl . ' as p', function ($join) {
            $join->on('p.user_id', '=', 'pli.user_parent_id');
        })
        ->join($tblU . ' as u', function ($join) {
            $join->on('p.user_id', '=', 'u.id')
                ->where('u.deleted', '=', '0');
        })


        ->whereIn('pli.user_id', $facilities)

        ->groupBy('pli.user_parent_id')

        ->orderBy('p.doing_business_as')

        ->get();

        return $practices;
    }
    /**
     * fetch the Facility Of ECA
     *
     * @param $practiceId
     */
    function getPracticeFacilities($parentId, $sessionUserId)
    {
        $tbl = $this->tbl;
       
        $appKey = $this->key;

        $locations = DB::table($tbl . ' as pli')

            ->select([DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$appKey') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_pli.practice_name,'$appKey') as practice_name"), "pli.user_id as facility_id"]);

        $locations = $locations->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            $join->on('elm.location_user_id', '=', 'pli.user_id')
                ->where('elm.emp_id', '=', $sessionUserId);
        });
        if (is_array($parentId))
            $locations = $locations->whereIn("pli.user_parent_id", $parentId);
        else
            $locations = $locations->where("pli.user_parent_id", "=", $parentId);



        return $locations->orderByRaw("practice_name ASC")

            ->get();
    }
    /**
     * active practice of the system
     * 
     * @param $sessionUserId
     * @return array
     */
    public function allPracticesDashboard($sessionUserId) {
        $tbl        = "user_baf_practiseinfo";
        $tblU       = "users";
        $appTblL    = "user_ddpracticelocationinfo";

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

    // $this->printR($facilities,true);

    $practices = DB::table($appTblL . ' as pli')

        ->select(
            'pli.user_parent_id as practice_id', 
            DB::raw('IFNULL(cm_p.practice_name, cm_p.doing_business_as) AS practice_name'),
            "u.profile_complete_percentage",DB::raw("
            CASE
                WHEN cm_u.deleted = '0' 
                    THEN 'Active'
            ELSE 'InActive'
            END AS status"),
            "dbi.taxonomy_code as taxonomy_label"
        )

        ->join($tbl . ' as p', function ($join) {
            $join->on('p.user_id', '=', 'pli.user_parent_id');
        })
        ->join($tblU . ' as u', function ($join) {
            $join->on('p.user_id', '=', 'u.id');
                //->where('u.deleted', '=', '1');
        })
        ->leftJoin('user_dd_businessinformation as dbi', function ($join) {
            $join->on('dbi.user_id', '=', 'pli.user_parent_id');
        })
        
        ->whereIn('pli.user_id', $facilities)

        ->groupBy('pli.user_parent_id')

        ->orderBy('p.doing_business_as')

        ->get();

        return $practices;
    }
}
