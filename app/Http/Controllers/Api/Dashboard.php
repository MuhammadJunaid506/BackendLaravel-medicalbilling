<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentsController;
use Illuminate\Http\Request;
use App\Models\Dashboard as dashboadModel;
use App\Http\Traits\Utility;
use App\Http\Traits\ApiResponseHandler;
use App\Models\LicenseTypes;
use Carbon\Carbon;
use App\Models\Payer;

use Illuminate\Support\Facades\DB;
use Termwind\Components\Raw;

class Dashboard extends Controller
{
    use Utility, ApiResponseHandler;
    /**
     * credentialing dashboard stats
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function credentailingStatistics(Request $request)
    {

        $facilityId = $request->has("facility_id") ? $request->get('facility_id') : 0;
        $dashboadModelObj = new dashboadModel();
        $sessionUserId = $this->getSessionUserId($request);
        $dashboad = $dashboadModelObj->dashboadStates($facilityId, $sessionUserId);
        $reArrageData = [
            ["status" => "New/Not Initiated", "tasks" => "0"],
            ["status" => "Initiated", "tasks" => "0"],
            ["status" => "In Process", "tasks" => "0"],
            ["status" => "Approved", "tasks" => "0"],
            ["status" => "On Hold", "tasks" => "0"],
            ["status" => "Rejected", "tasks" => "0"],
            ["status" => "Revalidation", "tasks" => "0"],
            ["status" => "Not Eligible", "tasks" => "0"]

        ];
        //$this->printR($dashboad, true);
        if (count($dashboad)) {
            foreach ($dashboad as $dash) {
                if ($dash->status == "ALL") {
                    $reArrageData[0] = ["status" => "ALL", "tasks" => $dash->tasks];
                } else if ($dash->status == "New/Not Initiated") {
                    $reArrageData[1] = ["status" => "New/Not Initiated", "tasks" => $dash->tasks];
                } else if ($dash->status == "Initiated") {
                    $reArrageData[2] = ["status" => "Initiated", "tasks" => $dash->tasks];
                } else if ($dash->status == "In Process") {
                    $reArrageData[3] = ["status" => "In Process", "tasks" => $dash->tasks];
                } else if ($dash->status == "Approved") {
                    $reArrageData[4] = ["status" => "Approved", "tasks" => $dash->tasks];
                } else if ($dash->status == "On Hold") {
                    $reArrageData[5] = ["status" => "On Hold", "tasks" => $dash->tasks];
                } else if ($dash->status == "Rejected") {
                    $reArrageData[6] = ["status" => "Rejected", "tasks" => $dash->tasks];
                } else if ($dash->status == "Revalidation") {
                    $reArrageData[7] = ["status" => "Revalidation", "tasks" => $dash->tasks];
                } else if ($dash->status == "Not Eligible") {
                    $reArrageData[8] = ["status" => "Not Eligible", "tasks" => $dash->tasks];
                }
            }
        }
        $filterDD = $dashboadModelObj->filterDD();

        $practices = $dashboadModelObj->activePractices($sessionUserId);
        // $this->printR($filterDD,true);
        $dashboadModelObj = NULL;  //Release memory
        return $this->successResponse(["dashboad" => $reArrageData, "filter_dd" => $filterDD, "practices" => $practices], "success");
    }
    /**
     * billing dashboard stats
     *
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    function billingStatistics(Request $request)
    {


        $sessionUserId = $this->getSessionUserId($request);

        // Get the current date
        $currentDate = Carbon::now();

        $endDate = $currentDate->endOfMonth();

        $endDate = $endDate->format('Y-m-d');

        $currentDate_ = Carbon::now();

        $startDate  = $currentDate_->subYear()->subMonths(11)->startOfMonth(); // Start of the current month, 11 months ago

        $startDate = $startDate->format('Y-m-d');

        // echo $startDate;
        // echo "---";
        // echo $endDate;
        // exit;

        $facilityId = $request->has("facility_id") ? $request->get('facility_id') : 0;

        $dashboadModelObj = new dashboadModel();

        $billingStats = $dashboadModelObj->billingStatistics($facilityId, $startDate, $endDate, $sessionUserId);

        $dashboadModelObj = NULL;

        // $billingStatsJSON = [["Timeline", "Claims"]];

        // if (count($billingStats)) {
        //     foreach ($billingStats as $billing) {
        //         $month = [$billing->short_month_name . " " . $billing->year, $billing->total_claims];
        //         array_push($billingStatsJSON, $month);
        //     }
        // }

        $currentYear = date("Y");
        $monthsBack = 11;

        $timelineData = array();
        // Generate data for the previous year
        $prevYear = (int)$currentYear - 1;
        $prevYear = "$prevYear";
        for ($i = $monthsBack; $i >= 0; $i--) {
            $year = $prevYear;
            $month = date('M', strtotime("-$i months"));
            $timelineData[$year][$month] = 0; // Initialize with 0 claims
        }

        // Generate data for the current year
        for ($i = $monthsBack; $i >= 0; $i--) {
            $year = $currentYear;
            $month = date('M', strtotime("-$i months"));
            $timelineData[$year][$month] = 0; // Initialize with 0 claims
        }



        //set fetched data from database against each year's each moneth
        if (count($billingStats)) {
            foreach ($billingStats as $item) {
                $month = $item->short_month_name;
                $year = $item->year;
                $claims = $item->total_claims;

                if ($year == $currentYear)
                    $timelineData[$year][$month] = $claims;
                else {
                    if ($prevYear != $year)
                        $timelineData[$prevYear][$month] = $claims;
                    if ($prevYear == $year)
                        $timelineData[$prevYear][$month] = $claims;
                }
            }
        }

        // Generate the final JSON structure
        $resultJson = array();

        $timelineHeader = array('Timeline', $prevYear, $currentYear); //header the graph

        array_push($resultJson, $timelineHeader);

        //add data against each month
        $monthWiseDataArr = [];
        $years = array_keys($timelineData);
        foreach ($years as $year) {
            $monthWiseData = $timelineData[$year];
            foreach ($monthWiseData as $key => $value) {
                $monthWiseDataArr[$key][] = $value;
            }
        }
        //create the each monts data as front end consume it
        $months = array_keys($monthWiseDataArr);
        foreach ($months as $month) {
            $monthArr = [];
            $monthData = $monthWiseDataArr[$month];
            array_push($monthArr, $month);
            foreach ($monthData as $eachMontData) {
                array_push($monthArr, $eachMontData);
            }
            array_push($resultJson, $monthArr);
        }

        return $this->successResponse($resultJson, "success");
    }
    /**
     * posting dashboard statistics
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    function postingStatistics(Request $request)
    {
        $dashboadModelObj = new dashboadModel();
        // Get the current date
        $currentDate = Carbon::now();
        // Calculate the end date as today (current date)
        $endDate = $currentDate->endOfDay();

        $endDateFormatted = $endDate->format('Y-m-d');
        // Calculate the start date for the recent 3 years
        $startDate = $currentDate->subYears(2)->startOfYear();


        // Format the dates if needed
        $startDateFormatted = $startDate->format('Y-m-d');

        $practiceId = $request->has("practice_id") ? $request->get('practice_id') : 0;

        $postingTimeFrame = $dashboadModelObj->postingGraphTimeFrame($practiceId, $startDateFormatted, $endDateFormatted);

        // Process the data and segregate it by payer and year
        $stackedData = [];
        // data lables
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $datasets = [];

        // $this->printR($postingTimeFrame,true);
        // $postingCollection = [];
        if (count($postingTimeFrame)) {
            foreach ($postingTimeFrame as $timeFrame) {
                $data = $dashboadModelObj->postingGraphPayers($practiceId, $timeFrame->period);
                // $this->printR($data,true);
                $data = $this->stdToArray($data);
                foreach ($data as $item) {
                    $period = $item['period'];
                    $payerName = $item['payer_name'];
                    $collection = $item['collection'];
                    // echo $period;
                    // exit;
                    // Extract year and month from the period
                    list($month, $year) = explode('-', $period);

                    if (!isset($stackedData[$year][$payerName])) {
                        $stackedData[$year][$payerName] = array_fill(1, 12, 0); // Initialize an array of 12 zeros
                    }

                    $stackedData[$year][$payerName][$month] = is_null($collection) ? 0 : $collection;
                }
                //$this->printR($data,true);
            }
            if (count($stackedData)) {
                foreach ($stackedData as $year => $payers) {
                    // $colors = ['red', 'blue', 'yellow']; // You can add more colors if needed
                    // $stackIndex = 0;

                    foreach ($payers as $payerName => $claimsData) {
                        $datasets[] = [
                            'label' => $payerName,
                            'data' => array_values($claimsData),
                            'backgroundColor' => $this->fetchPayerColor($payerName),
                            'stack' => "stack {$year}",
                        ];

                        // $stackIndex = ($stackIndex + 1) % count($colors);
                    }
                }
            }
        }
        $finalJson = [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
        return $this->successResponse($finalJson, "success");
        //$this->printR($stackedData,true);
    }
    /**
     * Payer averages dashboard
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    function facilityPayerAverages(Request $request)
    {
        $sessionUserId = $this->getSessionUserId($request);

        $dashboadModelObj = new dashboadModel();
        $endDate = Carbon::now()->subDays(15);
        $startDate = $endDate->copy()->subDays(185)->format("Y-m-d");
        $endDate = Carbon::parse($endDate)->format("Y-m-d");

        $facilityId = $request->has('facility_id') ? $request->facility_id : 0;

        $avgsResult = $dashboadModelObj->facilitypayerAvgs(0, $startDate, $endDate, $sessionUserId);

        $avgs = array();

        if ($avgsResult->count() > 0) {
            foreach ($avgsResult as $payer) {
                $avgs[$payer->facility_id][$payer->payer_name] = $payer->average_ar;
            }
        }
        $finalJson = [];
        if ($avgsResult->count() > 0) {
            $fromDate   = Carbon::now()->subDays(45)->format("Y-m-d");
            $toDate     = Carbon::now()->subDays(15)->format("Y-m-d");
            $avgsResult = $dashboadModelObj->facilitypayerAvgs($facilityId, $fromDate, $toDate, $sessionUserId);
            foreach ($avgsResult as $payer) {

                if (is_object($payer)) {
                    if ($avgs[$payer->facility_id][$payer->payer_name] > 0) {
                        $payer->percentage = round(($payer->average_ar / $avgs[$payer->facility_id][$payer->payer_name]) * 100);
                        if ($payer->percentage < 100 && $payer->total_claims >= 3) {
                            $payer->danger_rate = 100 - $payer->percentage;
                            if ($payer->danger_rate > 10)
                                array_push($finalJson, $payer);
                        }
                    }
                }
            }
        }
        $sortedArray = array();
        if (count($finalJson)) {
            $finalJsonArr = $this->stdToArray($finalJson);
            $collection = collect($finalJsonArr);

            // $sortedCollection = $collection->unique('');
            $sortedArray = $collection->sortBy(function ($item) {
                return [-$item['total_claims'], -$item['danger_rate']];
            })->values()->all();/**/
        }
        return $this->successResponse($sortedArray, "success");
    }
    /**
     * directory docs dashboards
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    function directoryDocsDashboards(Request $request)
    {
        $request->validate([
            "session_userid" => "required",
        ]);

        $role1 = 3;
        $role2 = [4, 10];
        $empId = 36235;

        $docsObj = new DocumentsController();

        $query1 = DB::table('emp_location_map')
            ->select('user_role_map.role_id', 'user_role_map.user_id')
            ->join('users', 'users.id', '=', 'emp_location_map.location_user_id')
            ->join('user_role_map', function ($join) use ($role1) {
                $join->on('user_role_map.user_id', '=', 'users.id')
                    ->where('user_role_map.role_id', '=', $role1);
            })
            ->where('emp_location_map.emp_id', $empId);

        $query2 = DB::table('emp_location_map')
            ->select('user_role_map.role_id', 'user_role_map.user_id')
            ->join('individualprovider_location_map', 'individualprovider_location_map.location_user_id', '=', 'emp_location_map.location_user_id')
            ->join('users', 'users.id', '=', 'individualprovider_location_map.user_id')
            ->join('user_role_map', function ($join) use ($role2) {
                $join->on('user_role_map.user_id', '=', 'users.id')
                    ->whereIn('user_role_map.role_id', $role2);
            })
            ->where('emp_location_map.emp_id', $empId);


        $results = $query1->union($query2)->get();

        $rolesArr = ['3' => "Practice", "9" => "Practice", "4" => "Provider", "10" => "Provider"]; //role name


        $parentLicenseType = licensetypes::where('parent_type_id', '=', 0)

            ->orderBy('sort_by', 'ASC')

            ->get();

        $expiringSoonDocsCnt = 0;
        $missingDocsCnt = 0;
        $expiredDocsCnt = 0;
        if ($results->count() > 0) {
            foreach ($results  as $row) {
                $userId = $row->user_id;
                $roleId = $row->role_id;
                $isFor = $rolesArr[$roleId];
                foreach ($parentLicenseType as $license) {

                    $childLicenseTypes = licensetypes::where('parent_type_id', '=', $license->id)

                        ->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

                        ->select("id", "parent_type_id", "name", 'is_mandatory')

                        ->orderBy('sort_by', 'ASC')

                        ->get();

                    if (count($childLicenseTypes)) {
                        //$this->printR($childLicenseTypes,true);
                        foreach ($childLicenseTypes as $eachChild) {
                            $isMandatory = $eachChild->is_mandatory;

                            $expiredDocs = $docsObj->licenseTypesExpired($userId, $eachChild->id);
                            if (count($expiredDocs)) {
                                $expiredDocsCnt += count($expiredDocs);
                            }
                            $expiringSoon = $docsObj->expiringSoonDocs($userId, $eachChild->id);
                            if (count($expiringSoon)) {
                                $expiringSoonDocsCnt += count($expiringSoon);
                            }
                            if ($isMandatory == 1) {
                                $missingDocs = $docsObj->missingDocs($userId, $eachChild->id);
                                if (count($missingDocs) == 0) {
                                    $missingDocsCnt += count($missingDocs);
                                }
                            }
                        }
                    }
                }
            }
        }
        $directoryDashboard = [
            "expiring_soon" =>  $expiringSoonDocsCnt,
            "missing_docs" => $missingDocsCnt,
            "expired_docs" => $expiredDocsCnt
        ];
        return $this->successResponse($directoryDashboard, "success");
    }
    /**
     * directory practice dashboards
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function directoryPracticeDashboard(Request $request)
    {
        $request->validate([
            "session_userid" => "required",
        ]);

        $sessionUserId = $request->session_userid;

        $practices = $this->allPracticesDashboard($sessionUserId);

        return $this->successResponse($practices, "success");
    }
    /**
     * directory facility dashboards
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function directoryFacilitiesDashboards(Request $request)
    {

        $request->validate([
            "session_userid" => "required",
        ]);

        $sessionUserId = $request->session_userid;

        $practices = $this->allPracticesDashboard($sessionUserId);

        if (count($practices)) {
            // $this->printR($practices,true);
            foreach ($practices as $practice) {
                $practiceId = $practice->practice_id;
                $facilities = $this->fetchFacilities($practiceId, $sessionUserId);
                // $this->printR($facilities,true);
                // $facilityData[$practiceId] = $facilities;
                $practice->facilities = $facilities;
            }
        }
        // $this->printR($facilityData,true);
        return $this->successResponse($practices, "success");
    }
    /**
     * fetch the facility
     *
     * @param $parentId
     * @param $sessionUserId
     * @return array
     */
    private function fetchFacilities($parentId, $sessionUserId)
    {
        $appKey = env("AES_KEY");

        $locations = DB::table('user_ddpracticelocationinfo as pli')

            ->select(
                [
                    DB::raw("AES_DECRYPT(cm_pli.practice_name,'$appKey') as facility_name"),
                    "pli.user_id as facility_id", "u_facility.profile_complete_percentage",
                    DB::raw("
                    CASE
                        WHEN cm_u_facility.deleted = '0'
                            THEN 'Active'
                    ELSE 'InActive'
                    END AS status")
                ]
            );

        $locations = $locations->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            $join->on('elm.location_user_id', '=', 'pli.user_id')
                ->where('elm.emp_id', '=', $sessionUserId);
        });
        $locations->join("users as u_facility", function ($join) {
            $join->on('u_facility.id', '=', 'pli.user_id');
        });
        if (is_array($parentId))
            $locations = $locations->whereIn("pli.user_parent_id", $parentId);
        else
            $locations = $locations->where("pli.user_parent_id", "=", $parentId);



        return $locations->get();
    }
    /**
     * directory provider dashboards
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function directoryProviderDashboards(Request $request)
    {
        $request->validate([
            "session_userid" => "required",
        ]);

        $sessionUserId = $request->session_userid;

        $facilities = DB::table("emp_location_map as elm")

            ->where('elm.emp_id', '=', $sessionUserId)

            ->pluck('elm.location_user_id')

            ->toArray();

        $sessionProviders = DB::table("individualprovider_location_map as iplp")

            ->select(
                DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as provider_name"),
                DB::raw("
                    CASE
                        WHEN cm_users.deleted = '0'
                            THEN 'Active'
                    ELSE 'InActive'
                    END AS status"),
                "users.id as provider_id",
                "users.profile_complete_percentage",
                "primary_speciality",
                "secondary_speciality",
                DB::raw("(select name FROM cm_professional_groups WHERE id = cm_users.professional_group_id LIMIT 0,1) as professional_group"),
                DB::raw("(select name FROM cm_professional_types WHERE id = cm_users.professional_type_id LIMIT 0,1) as professional_type"),
                "users.gender"


            )

            ->join("users", "users.id", "=", "iplp.user_id")

            ->whereIn("iplp.location_user_id", $facilities)
            
            ->groupBy("users.id")

            ->get();
        if ($sessionProviders->count() > 0) {
            foreach ($sessionProviders as &$provider) {
                $provider->specialties = [
                    'primary_specialty' => $provider->primary_speciality,
                    'secondary_specialty' => $provider->secondary_speciality,
                    'professional_group' => $provider->professional_group,
                    'professional_type' => $provider->professional_type,
                ];
                unset($provider->primary_speciality);  // Remove unnecessary columns after creating object
                unset($provider->secondary_speciality);
                unset($provider->professional_group);
                unset($provider->professional_type);
            }
        }
        // $this->printR($sessionProviders,true);
        return $this->successResponse($sessionProviders, "success");
    }
    /**
     * directory provider dashboards
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function directoryLeadsDashboards(Request $request)
    {
        $request->validate([
            "session_userid" => "required",
        ]);

        $sessionUserId = $request->session_userid;

        $userRole = $this->getUserRole($sessionUserId);

        $roleId = is_object($userRole) ? $userRole->role_id : null;

        $envirement = app()->environment('local');
        $leads = [];
        $key = env("AES_KEY");
        if ((
                $sessionUserId == "36229" ||
                $sessionUserId == "36230" ||
                $sessionUserId == "36228") ||
            $envirement == "true" ||
            ($roleId == 1 || $roleId == 11)
        ) {

            $leads = DB::table("leads")

                ->select(

                    DB::raw("AES_DECRYPT(cm_leads.company_name, '$key') AS company_name"),
                    "leads.id AS lead_id",
                    "leads.profile_complete_percentage",
                    "lead_status.status",
                    "leads.status_id"
                )

                ->leftJoin("lead_status", "lead_status.id", "=", "leads.status_id")

                ->get();
        }
        return $this->successResponse($leads, "success");
    }
    /**
     * directory events dashboard
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function directoryEventsDashboard(Request $request)
    {
        $request->validate([
            "session_userid" => "required",
        ]);

        $sessionUserId = $request->session_userid;


        // Get the current date
        $today = Carbon::today();

        $month = $today->format('m'); // Current month

        $day = $today->format('d');   // Current day

        $key = env("AES_KEY");

        $facilities = DB::table("emp_location_map as elm")

            ->where('elm.emp_id', '=', $sessionUserId)

            ->pluck('elm.location_user_id')

            ->toArray();

        $sessionProvidersBirthdayToday = DB::table("individualprovider_location_map as iplp")
            ->select(
                DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as provider_name"),
                DB::raw("AES_DECRYPT(cm_users.dob,'$key') as date_of_birth")
            )

            ->join("users", "users.id", "=", "iplp.user_id")

            ->whereIn("iplp.location_user_id", $facilities)

            ->whereRaw("MONTH(STR_TO_DATE(AES_DECRYPT(cm_users.dob, ?), '%Y-%m-%d')) = ?", [$key, $month])

            ->whereRaw("DAY(STR_TO_DATE(AES_DECRYPT(cm_users.dob, ?), '%Y-%m-%d')) = ?", [$key, $day])

            ->groupBy("users.id")

            ->get();

        $thirtyDaysAgo = $today->copy()->addDays(30);
        $todayMonthDay = $today->format('m-d');
        $thirtyDayMonthDay = $thirtyDaysAgo->format('m-d');
        $sessionProvidersBirthdayUpcomming = DB::table("individualprovider_location_map as iplp")
            ->select(
                DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as provider_name"),
                DB::raw("AES_DECRYPT(cm_users.dob,'$key') as date_of_birth")
            )
            ->join("users", "users.id", "=", "iplp.user_id")
            ->whereIn("iplp.location_user_id", $facilities)
            ->whereRaw("DATE_FORMAT(AES_DECRYPT(cm_users.dob, '$key'), '%m-%d') != ?", [$todayMonthDay])
            ->where(function ($query) use ($thirtyDayMonthDay, $todayMonthDay, $key) {
                $query->whereRaw("DATE_FORMAT(AES_DECRYPT(cm_users.dob, '$key'), '%m-%d') >= ?", [$todayMonthDay])
                    ->whereRaw("DATE_FORMAT(AES_DECRYPT(cm_users.dob, '$key'), '%m-%d') <= ?", [$thirtyDayMonthDay]);
            })
            ->orderBy(DB::raw("DATE_FORMAT(AES_DECRYPT(cm_users.dob, '$key'), '%m-%d')",'ASC'))
            ->groupBy("users.id")
            ->get();

        $sessionuserPractices = $this->activePractices($sessionUserId);
        $sessionuserPracticesArr = $this->stdToArray($sessionuserPractices);
        $sessionuserPracticesIds = array_column($sessionuserPracticesArr, "facility_id");
        // $this->printR($sessionuserPracticesIds,true);
        //cm_user_dd_businessinformation
        $sessionPracticeAnniversaryToday = DB::table("user_dd_businessinformation")

            ->select("user_baf_practiseinfo.practice_name", "user_dd_businessinformation.business_established_date as anniversary_date",DB::raw("'0' as anniversary_type"))

            ->join("user_baf_practiseinfo", "user_baf_practiseinfo.user_id", "=", "user_dd_businessinformation.user_id")

            ->whereIn("user_dd_businessinformation.user_id", $sessionuserPracticesIds)

            ->whereMonth('user_dd_businessinformation.business_established_date', $today->month)

            ->whereDay('user_dd_businessinformation.business_established_date', '=', $today->day);

            $sessionPracticeAnniversaryToday_ = DB::table("practice_service_plan")

            ->select("user_baf_practiseinfo.practice_name", "practice_service_plan.effective_date as anniversary_date",DB::raw("'1' as anniversary_type"))

            ->join("user_baf_practiseinfo", "user_baf_practiseinfo.user_id", "=", "practice_service_plan.practice_id")

            ->whereIn("practice_service_plan.practice_id", $sessionuserPracticesIds)

            ->whereMonth('practice_service_plan.effective_date', $today->month)

            ->whereDay('practice_service_plan.effective_date', '=', $today->day);

        $sessionPracticeAnniversaryToday = $sessionPracticeAnniversaryToday->union($sessionPracticeAnniversaryToday_)->get();

        $sessionPracticeAnniversaryUpcomming = DB::table("user_dd_businessinformation")

            ->select("user_baf_practiseinfo.practice_name", "user_dd_businessinformation.business_established_date as anniversary_date",DB::raw("'0' as anniversary_type"))

            ->join("user_baf_practiseinfo", "user_baf_practiseinfo.user_id", "=", "user_dd_businessinformation.user_id")

            ->whereIn("user_dd_businessinformation.user_id", $sessionuserPracticesIds)

            ->whereRaw("DATE_FORMAT(cm_user_dd_businessinformation.business_established_date, '%m-%d') != ?", [$todayMonthDay])

            ->where(function ($query) use ($thirtyDayMonthDay, $todayMonthDay) {
                $query->whereRaw("DATE_FORMAT(cm_user_dd_businessinformation.business_established_date, '%m-%d') >= ?", [$todayMonthDay])
                    ->whereRaw("DATE_FORMAT(cm_user_dd_businessinformation.business_established_date, '%m-%d') <= ?", [$thirtyDayMonthDay]);
            });
            //->get();

        $sessionPracticeAnniversaryUpcomming_ = DB::table("practice_service_plan")

            ->select("user_baf_practiseinfo.practice_name", "practice_service_plan.effective_date as anniversary_date",DB::raw("'1' as anniversary_type"))

            ->join("user_baf_practiseinfo", "user_baf_practiseinfo.user_id", "=", "practice_service_plan.practice_id")

            ->whereIn("practice_service_plan.practice_id", $sessionuserPracticesIds)

            ->whereRaw("DATE_FORMAT(cm_practice_service_plan.effective_date, '%m-%d') != ?", [$todayMonthDay])

            ->where(function ($query) use ($thirtyDayMonthDay, $todayMonthDay) {
                $query->whereRaw("DATE_FORMAT(cm_practice_service_plan.effective_date, '%m-%d') >= ?", [$todayMonthDay])
                    ->whereRaw("DATE_FORMAT(cm_practice_service_plan.effective_date, '%m-%d') <= ?", [$thirtyDayMonthDay]);
            });

            $sessionPracticeAnniversaryUpcomming = $sessionPracticeAnniversaryUpcomming->union($sessionPracticeAnniversaryUpcomming_)->get();

        $result = [
            "provider_today_birthdays"          =>  $sessionProvidersBirthdayToday,
            "provider_upcomming_birthdays"      =>  $sessionProvidersBirthdayUpcomming,
            "practice_today_anniversary"        =>  $sessionPracticeAnniversaryToday,
            "practice_upcomming_anniversary"    =>  $sessionPracticeAnniversaryUpcomming,
        ];
        return $this->successResponse($result, "success");
    }
    /**
     * fetch documents validity dashboard
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function validityDashboard(Request $request) {
       
        $request->validate([
        "session_userid" => "required",
       ]);
       $docsObj = new DocumentsController();
       $sessionUserId = $request->session_userid;

       $facilities = DB::table("emp_location_map as elm")

       ->where('elm.emp_id', '=', $sessionUserId)

       ->pluck('elm.location_user_id')

       ->toArray();
       //$request->merge(["user_ids" => $facilities]);
       //$this
        $facilityAllExpired = $docsObj->allLicenseTypesExpired($facilities,"practice");
        //    echo $facilityAllExpired . PHP_EOL;
        $facilityAllExpSoon = $docsObj->expiringSoonDocsAll($facilities,"practice");
        //    echo $facilityAllExpSoon .PHP_EOL;
        $facilityAllMissing = $docsObj->allMissingDocuments($facilities,"practice");
        //    echo $facilityAllMissing.PHP_EOL;
       
        $sessionProviders = DB::table("individualprovider_location_map as iplp")


            ->join("users", "users.id", "=", "iplp.user_id")

            ->whereIn("iplp.location_user_id", $facilities)

            ->pluck("iplp.user_id")
            
            ->toArray();
        
        $providerAllExpired = $docsObj->allLicenseTypesExpired($sessionProviders,"provider");
            
        $providerAllExpSoon = $docsObj->expiringSoonDocsAll($sessionProviders,"provider");

        $providerAllMissing = $docsObj->allMissingDocuments($sessionProviders,"provider");

        $prepData = [
            "all_expired_docs"      => ($providerAllExpired+$facilityAllExpired),
            "all_expiringsoon_docs" => ($providerAllExpSoon+$facilityAllExpSoon),
            "all_missing_docs"      => ($providerAllMissing+$facilityAllMissing),
            
            "facility_all_expired"  => $facilityAllExpired,
            "facility_all_expSoon"  => $facilityAllExpSoon,
            "facility_all_missing"  => $facilityAllMissing,
            
            "provider_all_expired"  => $providerAllExpired,
            "provider_all_expSoon"  => $providerAllExpSoon,
            "provider_all_missing"  => $providerAllMissing,

        ];

        return $this->successResponse($prepData, "success");
    }
   
    /**
     * fetch the provider expired docs
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchProviderExpiredDocs(Request $request) {
        
        $request->validate([
            "session_userid" => "required"
        ]);
        
        $docsObj = new DocumentsController();

        $sessionUserId = $request->session_userid;

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

        $sessionProviders = DB::table("individualprovider_location_map as iplp")


        ->join("users", "users.id", "=", "iplp.user_id")

        ->whereIn("iplp.location_user_id", $facilities)

        ->pluck("iplp.user_id")
        
        ->toArray();

        $expiredDocs = $docsObj->allProviderLicenseTypesExpiredDetails($sessionProviders,"provider");
        
        return $this->successResponse($expiredDocs, "success");

    }
    /**
     * fetch the facility expired docs
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchFacilityExpiredDocs(Request $request) {
        
        $request->validate([
            "session_userid" => "required"
        ]);
        
        $docsObj = new DocumentsController();

        $sessionUserId = $request->session_userid;

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

        $expiredDocs = $docsObj->allFacilityLicenseTypesExpiredDetails($facilities,"practice");
        
        return $this->successResponse($expiredDocs, "success");

    }
    /**
     * fetch the facility expiring soon docs
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchProviderExpiringSoonDocs(Request $request) {
        $request->validate([
            "session_userid" => "required"
        ]);
        
        $docsObj = new DocumentsController();

        $sessionUserId = $request->session_userid;

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

        $sessionProviders = DB::table("individualprovider_location_map as iplp")


        ->join("users", "users.id", "=", "iplp.user_id")

        ->whereIn("iplp.location_user_id", $facilities)

        ->pluck("iplp.user_id")
        
        ->toArray();

        $expiringSoonDocs = $docsObj->providerExpiringSoonDetail($sessionProviders,"provider");

        return $this->successResponse($expiringSoonDocs, "success");
    }
    /**
     * fetch the facility expiring soon docs
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchFacilityExpiringSoonDocs(Request $request) {
       
        $request->validate([
            "session_userid" => "required"
        ]);
        
        $docsObj = new DocumentsController();

        $sessionUserId = $request->session_userid;

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

       

        $expiringSoonDocs = $docsObj->facilityExpiringSoonDetail($facilities,"practice");

        return $this->successResponse($expiringSoonDocs, "success");
    }
    /**
     * fetch the facility missing docs
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchFacilityMissingDocs(Request $request) {
       
        $request->validate([
            "session_userid" => "required"
        ]);
        
        $docsObj = new DocumentsController();

        $sessionUserId = $request->session_userid;

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

       

        $missingDocs = $docsObj->facilityMissingDocuments($facilities,"practice");

        return $this->successResponse($missingDocs, "success");
    }
    /**
     * fetch the facility missing docs
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchProviderMissingDocs(Request $request) {
       
        $request->validate([
            "session_userid" => "required"
        ]);
        
        $docsObj = new DocumentsController();

        $sessionUserId = $request->session_userid;

        $facilities = DB::table("emp_location_map as elm")

        ->where('elm.emp_id', '=', $sessionUserId)

        ->pluck('elm.location_user_id')

        ->toArray();

        $sessionProviders = DB::table("individualprovider_location_map as iplp")


        ->join("users", "users.id", "=", "iplp.user_id")

        ->whereIn("iplp.location_user_id", $facilities)

        ->pluck("iplp.user_id")
        
        ->toArray();

       

        $missingDocs = $docsObj->providerMissingDocuments($sessionProviders,"provider");

        return $this->successResponse($missingDocs, "success");
    }
}
