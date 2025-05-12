<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\UserAccountActivityLog;
use App\Http\Traits\Utility;
use App\Models\Credentialing;
use App\Models\CredentialingActivityLog;
use App\Models\HospitalAffliation;
use App\Models\Education;
use App\Models\InsuranceCoverage;
use App\Models\License;
use App\Models\LicenseTypes;
use App\Models\SessionLog;
use DB;

class ReportController extends Controller
{
    use ApiResponseHandler, Utility, UserAccountActivityLog;
    /**
     * fetch the report of credentialing status
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    public function credentialingFacilityStatusReport(Request $request)
    {
       
        set_time_limit(0);
        $reportObj = new Report();
        $credentialingObj = new Credentialing();
        $practiceId = $request->practice_id;
        $facilityId = $request->facility_id;
        $providerId = $request->provider_id;
        $isActive = $request->is_active;

        // echo $facilityId;
        // echo '<br>';
        // echo $providerId;
        // exit;
        $credsPayers = [];
        $statsArr = [];
        $facilities = [];
        $reportJSON = [];
        $facilityArr = [];

        $newNotInitiatedCnt = 0;
        $initiatedCnt = 0;
        $inProcessCnt = 0;
        $approvedCnt = 0;
        $onHoldCnt = 0;
        $rejectedCnt = 0;
        $revalidationCnt = 0;
        $notEligibleCnt = 0;
        $notEnrolled = 0;

        if ($facilityId != "undefined" && $request->has('is_all') && $request->is_all == 1) {
            $facilityIdArr = json_decode($facilityId, true);
            $providerIdArr = json_decode($providerId, true);
            unset($providerIdArr[0]);

            $providerIds = array_column($providerIdArr, "value");

            $providerIdsStr = implode(",", $providerIds);

            $facilityIds = array_column($facilityIdArr, "value");

            $facilityIdsStr = implode(",", $facilityIds);


            // echo $facilityIdsStr;
            // exit;

            $credsPayers = $isActive == 1 ? $reportObj->fetchCredentialingPayer($isActive, $facilityIdsStr, 1, $practiceId) :  $reportObj->inActivePayers($isActive, $facilityIdsStr, 1, $practiceId);
            // $this->printR($credsPayers,true);
            foreach ($facilityIdArr as $eachFacility) {
                // $this->printR($eachFacility,true);

                $facilityId = $eachFacility['value'];
                array_push($facilityArr, ['facility_id' => $facilityId, 'practice_name' => $eachFacility['label']]);

                $providers = $credentialingObj->fetchReportProviders($facilityId, $isActive, $providerIdsStr);
                $reportJSON[$facilityId]['providers'] = $providers;

                if (count($credsPayers) > 0) {
                    foreach ($providers as $index => $provider) {
                        foreach ($credsPayers as $credPayer) {
                            $staus = $reportObj->getCredentialingProviderStatus($facilityId, $provider->id, $credPayer->id);
                            if (!is_object($staus)) {
                                $notEnrolled++;
                            } else {
                                if ($staus->credentialing_status == "New/Not Initiated")
                                    $newNotInitiatedCnt++;
                                elseif ($staus->credentialing_status == "Initiated")
                                    $initiatedCnt++;
                                elseif ($staus->credentialing_status == "In Process")
                                    $inProcessCnt++;
                                elseif ($staus->credentialing_status == "Approved")
                                    $approvedCnt++;
                                elseif ($staus->credentialing_status == "On Hold")
                                    $onHoldCnt++;
                                elseif ($staus->credentialing_status == "Rejected")
                                    $rejectedCnt++;
                                elseif ($staus->credentialing_status == "Revalidation")
                                    $revalidationCnt++;
                                elseif ($staus->credentialing_status == "Not Eligible")
                                    $notEligibleCnt++;
                            }
                            // $this->printR($staus,true);
                            $reportJSON[$facilityId]['provider_stats'][$provider->id][$credPayer->id] = $staus;
                        }
                    }
                }
            }
            // $this->printR($reportJSON,true);
            // $isActive = $request->is_active;
            // $credsPayers = [];

            //$facilities = $isActive == 1 ? $credentialingObj->fetchCredentialingUsers($practiceId,$isActive,"") : $credentialingObj->fetchCredentialingUsersInActive($practiceId,$isActive,"");
            // // $this->printR($facilities,true);

            // $statsArr = [];
            if (count($credsPayers) > 0) {
                foreach ($facilityIdArr as $eachFacility) {
                    foreach ($credsPayers as $credSts) {
                        // $this->printR($credSts,true);
                        $staus = $reportObj->getCredentialingStatus($eachFacility['value'], $credSts->id);
                        if (!is_object($staus)) {
                            $notEnrolled++;
                        } else {
                            if ($staus->credentialing_status == "New/Not Initiated")
                                $newNotInitiatedCnt++;
                            elseif ($staus->credentialing_status == "Initiated")
                                $initiatedCnt++;
                            elseif ($staus->credentialing_status == "In Process")
                                $inProcessCnt++;
                            elseif ($staus->credentialing_status == "Approved")
                                $approvedCnt++;
                            elseif ($staus->credentialing_status == "On Hold")
                                $onHoldCnt++;
                            elseif ($staus->credentialing_status == "Rejected")
                                $rejectedCnt++;
                            elseif ($staus->credentialing_status == "Revalidation")
                                $revalidationCnt++;
                            elseif ($staus->credentialing_status == "Not Eligible")
                                $notEligibleCnt++;
                        }
                        //$this->printR($staus,true);
                        $statsArr[$eachFacility['value']][$credSts->id] = $staus;
                    }
                }
            }
        } else {
            $facilityIdArr = json_decode($facilityId, true);
            // $this->printR($facilityIdArr,true);
            $providerIdArr = json_decode($providerId, true);
            // $this->printR($providerIdArr[0]['value'],true);
            if ($providerIdArr[0]['value'] == 0)
                unset($providerIdArr[0]);

            $providerIds = array_column($providerIdArr, "value");

            $providerIdsStr = implode(",", $providerIds);

            $facilityIds = array_column($facilityIdArr, "value");
            // $this->printR($providerIdArr,true);
            $facilityIdsStr = implode(",", $facilityIds);
            $credsPayers = $isActive == 1 ? $reportObj->fetchCredentialingPayer($isActive, $facilityIdsStr, 1, $practiceId) :  $reportObj->inActivePayers($isActive, $facilityIdsStr, 1, $practiceId);
            // $this->printR($credsPayers,true);
            foreach ($facilityIdArr as $eachFacility) {
                // $this->printR($eachFacility,true);

                $facilityId = $eachFacility['value'];
                array_push($facilityArr, ['facility_id' => $facilityId, 'practice_name' => $eachFacility['label']]);

                $providers = $credentialingObj->fetchReportProviders($facilityId, $isActive, $providerIdsStr);
                $reportJSON[$facilityId]['providers'] = $providers;

                if (count($credsPayers) > 0) {
                    foreach ($providers as $index => $provider) {
                        foreach ($credsPayers as $credPayer) {
                            $staus = $reportObj->getCredentialingProviderStatus($facilityId, $provider->id, $credPayer->id);
                            if (!is_object($staus)) {
                                $notEnrolled++;
                            } else {
                                if ($staus->credentialing_status == "New/Not Initiated")
                                    $newNotInitiatedCnt++;
                                elseif ($staus->credentialing_status == "Initiated")
                                    $initiatedCnt++;
                                elseif ($staus->credentialing_status == "In Process")
                                    $inProcessCnt++;
                                elseif ($staus->credentialing_status == "Approved")
                                    $approvedCnt++;
                                elseif ($staus->credentialing_status == "On Hold")
                                    $onHoldCnt++;
                                elseif ($staus->credentialing_status == "Rejected")
                                    $rejectedCnt++;
                                elseif ($staus->credentialing_status == "Revalidation")
                                    $revalidationCnt++;
                                elseif ($staus->credentialing_status == "Not Eligible")
                                    $notEligibleCnt++;
                            }
                            // $this->printR($staus,true);
                            $reportJSON[$facilityId]['provider_stats'][$provider->id][$credPayer->id] = $staus;
                        }
                    }
                }
            }
            if (count($credsPayers) > 0) {
                foreach ($facilityIdArr as $eachFacility) {
                    foreach ($credsPayers as $credSts) {
                        // $this->printR($credSts,true);
                        $staus = $reportObj->getCredentialingStatus($eachFacility['value'], $credSts->id);
                        if (!is_object($staus)) {
                            $notEnrolled++;
                        } else {
                            if ($staus->credentialing_status == "New/Not Initiated")
                                $newNotInitiatedCnt++;
                            elseif ($staus->credentialing_status == "Initiated")
                                $initiatedCnt++;
                            elseif ($staus->credentialing_status == "In Process")
                                $inProcessCnt++;
                            elseif ($staus->credentialing_status == "Approved")
                                $approvedCnt++;
                            elseif ($staus->credentialing_status == "On Hold")
                                $onHoldCnt++;
                            elseif ($staus->credentialing_status == "Rejected")
                                $rejectedCnt++;
                            elseif ($staus->credentialing_status == "Revalidation")
                                $revalidationCnt++;
                            elseif ($staus->credentialing_status == "Not Eligible")
                                $notEligibleCnt++;
                        }
                        //$this->printR($staus,true);
                        $statsArr[$eachFacility['value']][$credSts->id] = $staus;
                    }
                }
            }
        }

        $reportObj = NULL;
        $credentialingObj = NULL;
        $enrollmentsCountByStatus = [
            'No Enrollment'     => $notEnrolled,
            'New/Not Initiated' => $newNotInitiatedCnt,
            'Initiated'         => $initiatedCnt,
            'In Process'        => $inProcessCnt,
            'Approved'          => $approvedCnt,
            'On Hold'           => $onHoldCnt,
            'Rejected'          => $rejectedCnt,
            'Revalidation'      => $revalidationCnt,
            'Not Eligible'      => $notEligibleCnt
        ];
        return $this->successResponse([
            "creds_payers" => $credsPayers, 'creds_status' => $statsArr,
            'facilities' => $facilityArr, 'provider_report' => $reportJSON, "enrollments_countby_status" => $enrollmentsCountByStatus
        ], "success");
    }
    /**
     * get credentialing status report of a provider
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function credentialingProviderStatusReport(Request $request)
    {
        set_time_limit(0);
        $reportObj = new Report();
        $isActive = $request->is_active; //isActive
        $credentialingObj = new Credentialing();
        $statsArr = [];
        if ($request->has('facility_id') && $request->has('provider_ids')) {

            $facilityId = $request->facility_id;
            $practiceId = $request->practice_id;
            $providerIds = $request->provider_ids;
            $facility = json_decode($request->facility, true);
            $providerIds = json_decode($providerIds, true);
            try {
                $providerIds = array_column($providerIds, "value");
            } catch (\Exception) {
                $providerIds = 0;
            }
            $facilityArr = [];
            array_push($facilityArr, ['facility_id' => $facilityId, 'practice_name' => $facility[0]['label']]);
            $providerIdsStr = implode(",", $providerIds);
            $providers = $credentialingObj->fetchReportProviders($facilityId, $isActive, $providerIdsStr);
            // $this->printR($providers,true);
            $credsPayers = $isActive == 1 ? $reportObj->fetchCredentialingPayer($isActive, $facilityId, 1, $practiceId) :  $reportObj->inActivePayers($isActive, $facilityId, 1, $practiceId);
            $statsProviderArr = [];
            if (count($credsPayers) > 0) {
                foreach ($providers as $index => $provider) {
                    foreach ($credsPayers as $credPayer) {
                        $staus = $reportObj->getCredentialingProviderStatus($facilityId, $provider->id, $credPayer->id);
                        // $this->printR($staus,true);
                        $statsProviderArr[$facilityId][$index . "-" . $provider->id][$credPayer->id] = $staus;
                    }
                }
            }
            if (count($credsPayers) > 0) {

                foreach ($credsPayers as $credPayer) {
                    $staus =   $staus = $reportObj->getCredentialingStatus($facilityId, $credPayer->id);
                    // $this->printR($staus,true);
                    $statsArr[$facilityId][$credPayer->id] = $staus;
                }
            }
            $reportObj = NULL;
            $credentialingObj = NULL;
            return $this->successResponse(["creds_payers" => $credsPayers, 'creds_status' => $statsArr, 'providers' => $providers, 'creds_stats_provider' => $statsProviderArr, 'facilities' => $facilityArr], "success");
            // $this->printR($providerIds,true);
        } else {
            $facilityId = $request->facility_id;
            $practiceId = $request->practice_id;
            $facility = json_decode($request->facility, true);
            $providers = $credentialingObj->fetchReportProviders($facilityId, $isActive);
            $credsPayers = $isActive == 1 ? $reportObj->fetchCredentialingPayer($isActive, $facilityId, 1, $practiceId) :  $reportObj->inActivePayers($isActive, $facilityId, 1, $practiceId); //$reportObj->fetchCredentialingPayer($isActive,$facilityId,0,1);
            $statsProviderArr = [];
            $facilityArr = [];
            array_push($facilityArr, ['facility_id' => $facilityId, 'practice_name' => $facility[0]['label']]);
            // $this->printR($providers,true);
            if (count($credsPayers) > 0) {
                foreach ($providers as $index => $provider) {
                    foreach ($credsPayers as $credPayer) {
                        $staus = $reportObj->getCredentialingProviderStatus($facilityId, $provider->id, $credPayer->id);
                        // $this->printR($staus,true);
                        $statsProviderArr[$facilityId][$index . "-" . $provider->id][$credPayer->id] = $staus;
                    }
                }
            }
            if (count($credsPayers) > 0) {

                foreach ($credsPayers as $credPayer) {
                    $staus =   $staus = $reportObj->getCredentialingStatus($facilityId, $credPayer->id);
                    // $this->printR($staus,true);
                    $statsArr[$facilityId][$credPayer->id] = $staus;
                }
            }
            $reportObj = NULL;
            $credentialingObj = NULL;
            return $this->successResponse(["creds_payers" => $credsPayers, 'creds_status' => $statsArr, 'providers' => $providers, 'creds_stats_provider' => $statsProviderArr, 'facilities' => $facilityArr], "success");
        }
    }
    /**
     * fetch the active inactive report
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function fetchActiveInActiveReport(Request $request)
    {
        set_time_limit(0);
        $reportObj = new Report();

        $activeInActiveReport = $reportObj->activeInActiveReports();

        // $this->printR($activeInActiveReport,true);
        $reportObj = NULL;
        return $this->successResponse(["active_inactive_report" => $activeInActiveReport['result'], 'pagination' => $activeInActiveReport['pagination']], 'success');
    }
    /**
     * fetch the comprehensive report of credentialing
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    function fetchComprehensiveSpecificReport(Request $request)
    {
        set_time_limit(0);
        $credentialingActivityLogObj = new CredentialingActivityLog();
        $reportObj = new Report();
        $facilityId = $request->facility_id;
        $isActive = $request->is_active;
        $entities = [];
        if ($request->has('entities'))
            $entities = json_decode($request->entities, true);

        $comprehensiveProviderJSON = [];
        $comprehensiveFacilityJSON = [];
        $timeLineProviderJSON = [];
        $timeLineFacilityJSON = [];
        if ($request->has('facility_id') && !$request->has('provider_id')) {
            //if($request->has('status'))
            {
                $status = $request->get('status');
                $statusIds = json_decode($status, true);

                $payers = $request->get('payers');
                $payersIds = json_decode($payers, true);

                try {
                    if (isset($statusIds['value'])) {
                        $statusIds = $statusIds['value'];
                        if (is_array($statusIds)) {
                            try {
                                $statusIds = array_column($statusIds, "value");
                                $statusIds = implode(",", $statusIds);
                            } catch (\Exception $e) {
                                $statusIds = '';
                            }
                        } else
                            $statusIds = '';
                    } else {
                        $statusIds = array_column($statusIds, "value");
                        $statusIds = implode(",", $statusIds);
                    }
                } catch (\Exception $e) {
                    $statusIds = '';
                }

                try {
                    if (isset($payersIds['value'])) {
                        $payersIds = $payersIds['value'];
                        if (is_array($payersIds)) {
                            try {
                                $payersIds = array_column($payersIds, "value");
                                $payersIds = implode(",", $payersIds);
                            } catch (\Exception $e) {
                                $payersIds = '';
                            }
                        } else
                            $payersIds = '';
                    } else {
                        $payersIds = array_column($payersIds, "value");
                        $payersIds = implode(",", $payersIds);
                    }
                } catch (\Exception $e) {
                    $payersIds = '';
                }

                $cphnsReport = $reportObj->comprehensiveReport($facilityId, 0, 1, $statusIds, $entities, $payersIds);
                if (count($cphnsReport)) {
                    foreach ($cphnsReport as $eachReport) {
                        $timeLineFacilityJSON[$facilityId][$eachReport->credentialing_task_id] = $credentialingActivityLogObj->taskAVG($eachReport->payer_id, $eachReport->credentialing_task_id);
                    }
                }
                $comprehensiveFacilityJSON[$facilityId]['comprehensive'] = $cphnsReport;
                //$comprehensiveFacilityJSON[$facilityId]['licenses'] = $this->getReportLicense($facilityId,["3"]);
                return $this->successResponse([
                    'facility_comprehensive_report' => $comprehensiveFacilityJSON,
                    'facility_time_line' => $timeLineFacilityJSON
                ], 'success');
            }
        }
        if ($request->has('provider_id') && $request->has('facility_id')) {
            $status = $request->get('status');
            $statusIds = json_decode($status, true);
            $payers = $request->get('payers');
            $payersIds = json_decode($payers, true);
            try {
                if (isset($statusIds['value'])) {
                    $statusIds = $statusIds['value'];
                    if (is_array($statusIds)) {
                        try {
                            $statusIds = array_column($statusIds, "value");
                            $statusIds = implode(",", $statusIds);
                        } catch (\Exception $e) {
                            $statusIds = '';
                        }
                    } else
                        $statusIds = '';
                } else {
                    $statusIds = array_column($statusIds, "value");
                    $statusIds = implode(",", $statusIds);
                }
            } catch (\Exception $e) {
                $statusIds = '';
            }

            try {
                if (isset($payersIds['value'])) {
                    $payersIds = $payersIds['value'];
                    if (is_array($payersIds)) {
                        try {
                            $payersIds = array_column($payersIds, "value");
                            $payersIds = implode(",", $payersIds);
                        } catch (\Exception $e) {
                            $payersIds = '';
                        }
                    } else
                        $payersIds = '';
                } else {
                    if (is_array($payersIds)) {
                        $payersIds = array_column($payersIds, "value");
                        $payersIds = implode(",", $payersIds);
                    } else
                        $payersIds = "";
                }
            } catch (\Exception $e) {
                $payersIds = '';
            }

            $cphnsReport = $reportObj->comprehensiveReport($facilityId, $request->provider_id, 0, $statusIds, $entities, $payersIds);
            if (count($cphnsReport)) {
                foreach ($cphnsReport as $eachReport) {
                    $timeLineProviderJSON[$eachReport->credentialing_task_id] = $credentialingActivityLogObj->taskAVG($eachReport->payer_id, $eachReport->credentialing_task_id);
                }
            }
            $comprehensiveProviderJSON['comprehensive'] = $cphnsReport;
            //$comprehensiveProviderJSON['licenses'] = $this->getReportLicense($request->provider_id,["1","2","4"]);
            return $this->successResponse([
                'provider_comprehensive_report' => $comprehensiveProviderJSON,
                'provider_time_line' => $timeLineProviderJSON
            ], 'success');
        }
    }


    /**
     * fetch the License report of credentialing
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    function fetchLicenseValdityReport(Request $request)
    {

        set_time_limit(0);


        $facilityId = $request->facility_id;

        $providerId = $request->provider_id;

        // $isActive = $request->is_active;

        $validityFilter = $request->validity_filter;
        $licenseData = [];
        // $facilityIdsArr = json_decode($facilityIds,true);
        // $providerIdsArr = json_decode($providerIds,true);

        // s$this->printR($facilityIdsArr['value'][0]['value'],true);

        $validityFilterArr = json_decode($validityFilter, true);
        $validtyFilters = [];
        if (is_array($validityFilterArr['value']) && count($validityFilterArr['value'])) {
            $validtyFilters = $validityFilterArr['value'];
            $validtyFilters = array_column($validtyFilters, "value");
        }
        // $this->printR($validtyFilters,true);
        //$facilityId = $facilityIdsArr['value'][0]['value'];
        // if(count($providerIdsArr)) {
        //     foreach($providerIdsArr as $providerId) {
        // $licenseData['provider'] = $this->getReportLicense($providerId,["1","2","4"],$validtyFilters);
        //     }
        // }



        if ($request->has('is_provider'))
            $licenseData['provider'] = $this->getReportLicense($providerId, ["1", "2", "4"], $validtyFilters, 'provider');

        if ($request->has('is_facility'))
            $licenseData['facility'] = $this->getReportLicense($facilityId, ["3"], $validtyFilters, 'facility');
        return $this->successResponse($licenseData, "success");
    }


    /**
     * load comprehensive basic data
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    function fetchComprehensiveReport(Request $request)
    {

        $credentialingObj = new Credentialing();

        $facilityId = $request->facility_id;

        $isActive = $request->is_active;

        $providers = $credentialingObj->fetchReportProviders($facilityId, $isActive);

        $credentialingObj = NULL;

        return $this->successResponse(['providers' => $providers, 'provider_comprehensive_report' => [], 'facility_comprehensive_report' => [], 'provider_time_line' => [], 'facility_time_line' => []], 'success');
    }
    /**
     * get the report license
     *
     * @param $userId
     * @param $whereIn
     */
    private function getReportLicense($userId, $whereIn, $moreFilter = [], $type = '')
    {
        $allLicenses = array();
        $expiredLicenses = array();
        $soonExpiredLicenses = array();
        $validLicenses = array();
        $missingLicenses = array();

        if ($type == "facility") {
            // $this->printR($moreFilter,true);
            if (count($moreFilter) == 0) {
                $allLicenses = License::select(
                    "user_licenses.id",
                    "user_licenses.license_no",
                    "user_licenses.issuing_state",
                    DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
                    DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
                    DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
                    "license_types.name as type",
                    DB::raw("CASE
                    WHEN cm_user_licenses.exp_date < CURDATE() THEN 'Expired'
                    WHEN cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'Expiring Soon'
                    WHEN cm_user_licenses.exp_date > DATE_ADD(NOW(), INTERVAL 60 DAY) THEN 'Valid'
                    END AS validity")
                    // DB::raw("IF(cm_user_licenses.exp_date < CURDATE(),'1','0') AS is_expired"),
                    // DB::raw("IF(cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY),'1','0') AS is_expiring_soon"),
                    // DB::raw("IF(cm_user_licenses.exp_date > DATE_ADD(NOW(), INTERVAL 60 DAY),'1','0') AS is_valid")
                )

                    ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

                    ->join("license_types", function ($join) use ($whereIn) {
                        $join->on("license_types.id", "=", "user_licenses.type_id")
                            ->where("license_types.is_for_report", "=", 1);
                            // ->whereIn("user_licenses.type_id", $whereIn);
                    })

                    // ->join(DB::raw("(SELECT license_no, MAX(document_version) AS max_version FROM cm_user_licenses WHERE user_id = $userId GROUP BY license_no) AS cm_t2"), function ($join) {
                    //     $join->on('user_licenses.license_no', '=', 't2.license_no')
                    //          ->on('user_licenses.document_version', '=', 't2.max_version');
                    // })

                    ->where("user_licenses.is_current_version", "=", 1)

                    ->where("user_licenses.is_delete", "=", 0)

                    ->where("user_licenses.user_id", "=", $userId)

                    ->orderBy("user_licenses.id", "DESC");
                    // ->toSql();
                    // ->get();
                    $missingLicenseDocuments = LicenseTypes::select(
                        DB::raw("'-' as id"),
                        DB::raw("'-' as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("'-' as issue_date"),
                        DB::raw("'-' as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw("cm_license_types.name as type"),
                        DB::raw("'Document Missing' as validity"),
                    )
                    ->leftJoin('user_licenses', function($join) {
                        $join->on("user_licenses.type_id", "=", "license_types.id")
                            ->where("user_licenses.is_delete", "=", 0);
                    })
                    ->leftJoin('users', function($join) use ($userId) {
                        $join->on('users.id', '=', 'user_licenses.user_id')
                            ->where("users.id", "=", $userId);
                    })
                    ->where('license_types.is_mandatory', "=", 1)
                    ->where("license_types.is_for_report", "=", 1)
                    ->whereIn("license_types.is_for", ["Practice", "Both"])
                    ->whereNotIn("license_types.id", function ($query) use($userId) {
                        $query->select('type_id')
                            ->from('user_licenses')
                            ->where('user_id', "=", $userId);
                    })
                    ->groupBy("type");

                    $missingInsuaranceDocuments = LicenseTypes::select(
                        DB::raw("'-' as id"),
                        DB::raw("'-' as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("'-' as issue_date"),
                        DB::raw("'-' as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw("cm_license_types.name as type"),
                        DB::raw("'Document Missing' as validity"),
                    )
                    ->leftJoin('insurance_coverage', function($join) use($userId) {
                        $join->on("insurance_coverage.type_id", "=", "license_types.id")
                        ->whereNotIn('license_types.id', function($query) use($userId) {

                            $query->select('type_id')
                            ->from('insurance_coverage')
                            ->where('user_id', "=", $userId);

                        });

                    })
                    ->leftJoin('users', function($join) use ($userId) {
                        $join->on('users.id', '=', 'insurance_coverage.user_id')
                            ->where("users.id", "=", $userId);
                    })
                    ->where('license_types.is_mandatory', "=", 1)
                    ->where("license_types.is_for_report", "=", 1)
                    ->whereIn("license_types.is_for", ["Practice", "Both"])

                    ->groupBy("type");


                    $insuranceCoverage = InsuranceCoverage::select(
                        "insurance_coverage.id",
                        DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
                        DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('
                        CASE
                        WHEN type = "professional" THEN "Professional Liability Insurance"
                        ELSE "General Liability Insurance"
                        END as type'),
                        DB::raw("
                        CASE
                            WHEN expiration_date < CURDATE() THEN 'Expired'
                            WHEN expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'Expiring Soon'
                            WHEN expiration_date > DATE_ADD(NOW(), INTERVAL 60 DAY) THEN 'Valid'
                            ELSE '-'
                        END AS validity"))
                        ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
                        ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
                        ->where("insurance_coverage.user_id", $userId)
                        ->where("insurance_coverage.is_current_version", 1)
                        ->where("license_types.is_for_report", 1);

                    $hospitalAffiliations = HospitalAffliation::select(
                        "hospital_affiliations.id",
                        "admitting_previleges as license_no",
                        DB::raw('NULL as issuing_state'),
                        DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
                        DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('"hospital_affiliations" as type'),
                        DB::raw("
                        CASE
                        WHEN (end_date < CURDATE()) THEN 'Expired'
                        WHEN (end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)) THEN 'Expiring Soon'
                        WHEN (end_date> DATE_ADD(NOW(), INTERVAL 60 DAY))	THEN 'Valid'
                        ELSE '-'
                        END AS validity
                        ")
                        )
                        ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
                        ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
                        ->where("hospital_affiliations.user_id", $userId)
                        ->where("hospital_affiliations.is_current_version", 1)
                        ->where("license_types.is_for_report", 1);
                        // ->get();

                    $educationDocuments = Education::select(
                        "education.id",
                        DB::raw("'Degree' as license_no"),
                        "issuing_institute as issuing_state",
                        DB::raw("null as issue_date"),
                        DB::raw("null as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        "education_type as type",
                        DB::raw("'-' as validity")
                    )
                    ->leftJoin("users", "users.id", "=", "education.user_id")
                    ->leftJoin("license_types", "education.type_id", "=", "license_types.id")
                    ->where("education.user_id", $userId)
                    ->where("education.is_current_version", 1)
                    ->where("license_types.is_for_report", 1);
                    // ->get();


                    $allLicenses = $allLicenses->union($missingInsuaranceDocuments)->union($missingLicenseDocuments)->union($insuranceCoverage)->union($hospitalAffiliations)->union($educationDocuments)->get();

                    // echo $allLicenses;
                    // exit;
                $allLicenses = count($allLicenses) > 0 ? $this->stdToArray($allLicenses) : [];
            }
            if (in_array("all", $moreFilter)) {
                $allLicenses = License::select(
                    "user_licenses.id",
                    "user_licenses.license_no",
                    "user_licenses.issuing_state",
                    DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
                    DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
                    DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
                    "license_types.name as type",
                    DB::raw("CASE
                    WHEN cm_user_licenses.exp_date < CURDATE() THEN 'Expired'
                    WHEN cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'Expiring Soon'
                    WHEN cm_user_licenses.exp_date > DATE_ADD(NOW(), INTERVAL 60 DAY) THEN 'Valid'
                    END AS validity")
                    // DB::raw("IF(cm_user_licenses.exp_date < CURDATE(),'1','0') AS is_expired"),
                    // DB::raw("IF(cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY),'1','0') AS is_expiring_soon"),
                    // DB::raw("IF(cm_user_licenses.exp_date > DATE_ADD(NOW(), INTERVAL 60 DAY),'1','0') AS is_valid")
                )


                    ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

                    ->join("license_types", function ($join) use ($whereIn) {
                        $join->on("license_types.id", "=", "user_licenses.type_id")
                            // ->whereIn("user_licenses.type_id", $whereIn);
                            ->where("license_types.is_for_report", "=", 1);
                    })

                    // ->join(DB::raw("(SELECT license_no, MAX(document_version) AS max_version FROM cm_user_licenses WHERE user_id = $userId GROUP BY license_no) AS cm_t2"), function ($join) {
                    //     $join->on('user_licenses.license_no', '=', 't2.license_no')
                    //          ->on('user_licenses.document_version', '=', 't2.max_version');
                    // })

                    ->where("user_licenses.is_delete", "=", 0)

                    ->where("user_licenses.is_current_version", "=", 1)

                    ->where("user_licenses.user_id", "=", $userId)

                    ->orderBy("user_licenses.id", "DESC");

                    // ->get();

                    $missingLicenseDocuments = LicenseTypes::select(
                        DB::raw("'-' as id"),
                        DB::raw("'-' as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("'-' as issue_date"),
                        DB::raw("'-' as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw("cm_license_types.name as type"),
                        DB::raw("'Document Missing' as validity"),
                    )
                    ->leftJoin('user_licenses', function($join) {
                        $join->on("user_licenses.type_id", "=", "license_types.id")
                            ->where("user_licenses.is_delete", "=", 0);
                    })
                    ->leftJoin('users', function($join) use ($userId) {
                        $join->on('users.id', '=', 'user_licenses.user_id')
                            ->where("users.id", "=", $userId);
                    })
                    ->where('license_types.is_mandatory', "=", 1)
                    ->where("license_types.is_for_report", "=", 1)
                    ->whereIn("license_types.is_for", ["Practice", "Both"])
                    ->whereNotIn("license_types.id", function ($query) use($userId) {
                        $query->select('type_id')
                            ->from('user_licenses')
                            ->where('user_id', "=", $userId);
                    })
                    ->groupBy("type");

                    $missingInsuaranceDocuments = LicenseTypes::select(
                        DB::raw("'-' as id"),
                        DB::raw("'-' as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("'-' as issue_date"),
                        DB::raw("'-' as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw("cm_license_types.name as type"),
                        DB::raw("'Document Missing' as validity"),
                    )
                    ->leftJoin('insurance_coverage', function($join) use($userId) {
                        $join->on("insurance_coverage.type_id", "=", "license_types.id")
                        ->whereNotIn('license_types.id', function($query) use($userId) {

                            $query->select('type_id')
                            ->from('insurance_coverage')
                            ->where('user_id', "=", $userId);

                        });

                    })
                    ->leftJoin('users', function($join) use ($userId) {
                        $join->on('users.id', '=', 'insurance_coverage.user_id')
                            ->where("users.id", "=", $userId);
                    })
                    ->where('license_types.is_mandatory', "=", 1)
                    ->where("license_types.is_for_report", "=", 1)
                    ->whereIn("license_types.is_for", ["Practice", "Both"])

                    ->groupBy("type");


                    $insuranceCoverage = InsuranceCoverage::select(
                        "insurance_coverage.id",
                        DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
                        DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('
                        CASE
                        WHEN type = "professional" THEN "Professional Liability Insurance"
                        ELSE "General Liability Insurance"
                        END as type'),
                        DB::raw("
                        CASE
                            WHEN exp_date < CURDATE() THEN 'Expired'
                            WHEN exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN 'Expiring Soon'
                            WHEN exp_date > DATE_ADD(NOW(), INTERVAL 60 DAY) THEN 'Valid'
                            ELSE '-'
                        END AS validity"))
                        ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
                        ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
                        ->where("insurance_coverage.user_id", $userId)
                        ->where("insurance_coverage.is_current_version", 1)
                        ->where("license_types.is_for_report", 1);
                   
                    $hospitalAffiliations = HospitalAffliation::select(
                        "hospital_affiliations.id",
                        "admitting_previleges as license_no",
                        DB::raw('NULL as issuing_state'),
                        DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
                        DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('"hospital_affiliations" as type'),
                        DB::raw("
                        CASE
                        WHEN (end_date < CURDATE()) THEN 'Expired'
                        WHEN (end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)) THEN 'Expiring Soon'
                        WHEN (end_date> DATE_ADD(NOW(), INTERVAL 60 DAY))	THEN 'Valid'
                        ELSE '-'
                        END AS validity
                        ")
                        )
                        ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
                        ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
                        ->where("hospital_affiliations.user_id", $userId)
                        ->where("hospital_affiliations.is_current_version", 1)
                        ->where("license_types.is_for_report", 1);
                        // ->get();

                    $educationDocuments = Education::select(
                        "education.id",
                        DB::raw('"degree" as license_no'),
                        "issuing_institute as issuing_state",
                        DB::raw("null as issue_date"),
                        DB::raw("null as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        "education_type as type",
                        DB::raw("'-' as validity")
                    )
                    ->leftJoin("users", "users.id", "=", "education.user_id")
                    ->leftJoin("license_types", "education.type_id", "=", "license_types.id")
                    ->where("education.user_id", $userId)
                    ->where("education.is_current_version", 1)
                    ->where("license_types.is_for_report", 1);
                    // ->get();

                    
                    $allLicenses = $allLicenses->union($missingInsuaranceDocuments)->union($missingLicenseDocuments)->union($insuranceCoverage)->union($hospitalAffiliations)->union($educationDocuments)->get();

                $allLicenses = count($allLicenses) > 0 ? $this->stdToArray($allLicenses) : [];
            }
            if (in_array("expired", $moreFilter)) {

                $expiredLicenses = License::select(
                    "user_licenses.id",
                    "user_licenses.license_no",
                    "user_licenses.issuing_state",
                    DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
                    DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
                    DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
                    "license_types.name as type",
                    DB::raw("'Expired' as validity")
                )

                    ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

                    ->join("license_types", function ($join) use ($whereIn) {
                        $join->on("license_types.id", "=", "user_licenses.type_id")
                            // ->whereIn("user_licenses.type_id", $whereIn);
                            ->where("license_types.is_for_report", "=", 1);
                    })

                    // ->join(DB::raw("(SELECT license_no, MAX(document_version) AS max_version FROM cm_user_licenses WHERE user_id = $userId GROUP BY license_no) AS cm_t2"), function ($join) {
                    //     $join->on('user_licenses.license_no', '=', 't2.license_no')
                    //          ->on('user_licenses.document_version', '=', 't2.max_version');
                    // })

                    ->where("user_licenses.is_delete", "=", 0)

                    ->where("user_licenses.is_current_version", "=", 1)

                    ->where("user_licenses.user_id", "=", $userId)

                    ->whereRaw("cm_user_licenses.exp_date < CURDATE()")

                    ->orderBy("user_licenses.id", "DESC");

                    // ->get();

                    $insuranceCoverage = InsuranceCoverage::select(
                        "insurance_coverage.id",
                        DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
                        DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('
                        CASE
                        WHEN type = "professional" THEN "Professional Liability Insurance"
                        ELSE "General Liability Insurance"
                        END as type'),
                        DB::raw("'Expired' as validity"))
                        ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
                        ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
                        ->where("insurance_coverage.user_id", $userId)
                        ->where("insurance_coverage.is_current_version", 1)
                        ->where("license_types.is_for_report", 1)
                        ->whereRaw("expiration_date < CURDATE()");

                    $hospitalAffiliations = HospitalAffliation::select(
                        "hospital_affiliations.id",
                        "admitting_previleges as license_no",
                        DB::raw('NULL as issuing_state'),
                        DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
                        DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('"hospital_affiliations" as type'),
                        DB::raw("'Expired' as validity")
                        )
                        ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
                        ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
                        ->where("hospital_affiliations.user_id", $userId)
                        ->where("hospital_affiliations.is_current_version", 1)
                        ->where("license_types.is_for_report", 1)
                        ->whereRaw("end_date < CURDATE()");

                        $expiredLicenses = $expiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();
                $expiredLicenses = count($expiredLicenses) > 0 ? $this->stdToArray($expiredLicenses) : [];
            }
            if (in_array("expiring soon", $moreFilter)) {
                $soonExpiredLicenses = License::select(
                    "user_licenses.id",
                    "user_licenses.license_no",
                    "user_licenses.issuing_state",
                    DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
                    DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
                    DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
                    "license_types.name as type",
                    DB::raw("'Expiring Soon' as validity")
                )

                    ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

                    ->join("license_types", function ($join) use ($whereIn) {
                        $join->on("license_types.id", "=", "user_licenses.type_id")
                            // ->whereIn("user_licenses.type_id", $whereIn);
                            ->where("license_types.is_for_report", "=", 1);
                    })

                    // ->join(DB::raw("(SELECT license_no, MAX(document_version) AS max_version FROM cm_user_licenses WHERE user_id = $userId GROUP BY license_no) AS cm_t2"), function ($join) {
                    //     $join->on('user_licenses.license_no', '=', 't2.license_no')
                    //          ->on('user_licenses.document_version', '=', 't2.max_version');
                    // })
                    ->where("user_licenses.is_delete", "=", 0)

                    ->where("user_licenses.is_current_version", "=", 1)

                    ->where("user_licenses.user_id", "=", $userId)


                    ->whereRaw("cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)")

                    ->orderBy("user_licenses.id", "DESC");

                    // ->get();
                    $insuranceCoverage = InsuranceCoverage::select(
                        "insurance_coverage.id",
                        DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
                        DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('cm_license_types.name as type'),
                        DB::raw("'Expiring Soon' as validity"))
                        ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
                        ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
                        ->where("insurance_coverage.user_id", $userId)
                        ->where("insurance_coverage.is_current_version", 1)
                        ->where("license_types.is_for_report", 1)
                        ->whereRaw("expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)");

                    $hospitalAffiliations = HospitalAffliation::select(
                        "hospital_affiliations.id",
                        "admitting_previleges as license_no",
                        DB::raw('NULL as issuing_state'),
                        DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
                        DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('"hospital_affiliations" as type'),
                        DB::raw("'Expiring Soon' as validity")
                        )
                        ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
                        ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
                        ->where("hospital_affiliations.user_id", $userId)
                        ->where("hospital_affiliations.is_current_version", 1)
                        ->where("license_types.is_for_report", 1)
                        ->whereRaw("end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)");

                    $soonExpiredLicenses = $soonExpiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();

                $soonExpiredLicenses = count($soonExpiredLicenses) > 0 ? $this->stdToArray($soonExpiredLicenses) : [];
            }
            if (in_array("valid", $moreFilter)) {
                $validLicenses = License::select(
                    "user_licenses.id",
                    "user_licenses.license_no",
                    "user_licenses.issuing_state",
                    DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
                    DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
                    DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
                    "license_types.name as type",
                    DB::raw("'Valid' as validity")
                )

                    ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

                    ->join("license_types", function ($join) use ($whereIn) {
                        $join->on("license_types.id", "=", "user_licenses.type_id")
                            // ->whereIn("user_licenses.type_id", $whereIn);
                            ->where("license_types.is_for_report", "=", 1);
                    })

                    // ->join(DB::raw("(SELECT license_no, MAX(document_version) AS max_version FROM cm_user_licenses WHERE user_id = $userId GROUP BY license_no) AS cm_t2"), function ($join) {
                    //     $join->on('user_licenses.license_no', '=', 't2.license_no')
                    //          ->on('user_licenses.document_version', '=', 't2.max_version');
                    // })
                    ->where("user_licenses.is_delete", "=", 0)

                    ->where("user_licenses.is_current_version", "=", 1)

                    ->where("user_licenses.user_id", "=", $userId)

                    ->whereRaw("cm_user_licenses.exp_date > DATE_ADD(NOW(), INTERVAL 60 DAY)")

                    ->orderBy("user_licenses.id", "DESC");

                    // ->get();

                    $insuranceCoverage = InsuranceCoverage::select(
                        "insurance_coverage.id",
                        DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
                        DB::raw("'-' as issuing_state"),
                        DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
                        DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('cm_license_types.name as type'),
                        DB::raw("'Valid' as validity"))
                        ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
                        ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
                        ->where("insurance_coverage.user_id", $userId)
                        ->where("insurance_coverage.is_current_version", 1)
                        ->where("license_types.is_for_report", 1)
                        ->whereRaw("expiration_date > DATE_ADD(NOW(), INTERVAL 60 DAY)");

                    $hospitalAffiliations = HospitalAffliation::select(
                        "hospital_affiliations.id",
                        "admitting_previleges as license_no",
                        DB::raw('NULL as issuing_state'),
                        DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
                        DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
                        DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                        DB::raw('"hospital_affiliations" as type'),
                        DB::raw("'Valid' as validity")
                        )
                        ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
                        ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
                        ->where("hospital_affiliations.user_id", $userId)
                        ->where("hospital_affiliations.is_current_version", 1)
                        ->where("license_types.is_for_report", 1)
                        ->whereRaw("end_date > DATE_ADD(NOW(), INTERVAL 60 DAY)");

                        $validLicenses = $validLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();


                $validLicenses = count($validLicenses) > 0 ? $this->stdToArray($validLicenses) : [];
            }

            if(in_array("missing document", $moreFilter)) {

                $missingLicenseDocuments = LicenseTypes::select(
                    DB::raw("'-' as id"),
                    DB::raw("'-' as license_no"),
                    DB::raw("'-' as issuing_state"),
                    DB::raw("'-' as issue_date"),
                    DB::raw("'-' as exp_date"),
                    DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                    DB::raw("cm_license_types.name as type"),
                    DB::raw("'Document Missing' as validity"),
                )
                ->leftJoin('user_licenses', function($join) {
                    $join->on("user_licenses.type_id", "=", "license_types.id")
                        ->where("user_licenses.is_delete", "=", 0);
                })
                ->leftJoin('users', function($join) use ($userId) {
                    $join->on('users.id', '=', 'user_licenses.user_id')
                        ->where("users.id", "=", $userId);
                })
                ->where('license_types.is_mandatory', "=", 1)
                ->where("license_types.is_for_report", "=", 1)
                ->whereIn("license_types.is_for", ["Practice", "Both"])
                ->whereNotIn("license_types.id", function ($query) use($userId) {
                    $query->select('type_id')
                        ->from('user_licenses')
                        ->where('user_id', "=", $userId);
                })
                ->groupBy("type");

                $missingInsuaranceDocuments = LicenseTypes::select(
                    DB::raw("'-' as id"),
                    DB::raw("'-' as license_no"),
                    DB::raw("'-' as issuing_state"),
                    DB::raw("'-' as issue_date"),
                    DB::raw("'-' as exp_date"),
                    DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                    DB::raw("cm_license_types.name as type"),
                    DB::raw("'Document Missing' as validity"),
                )
                ->leftJoin('insurance_coverage', function($join) use($userId) {
                    $join->on("insurance_coverage.type_id", "=", "license_types.id")
                    ->whereNotIn('license_types.id', function($query) use($userId) {

                        $query->select('type_id')
                        ->from('insurance_coverage')
                        ->where('user_id', "=", $userId);

                    });

                })
                ->leftJoin('users', function($join) use ($userId) {
                    $join->on('users.id', '=', 'insurance_coverage.user_id')
                        ->where("users.id", "=", $userId);
                })
                ->where('license_types.is_mandatory', "=", 1)
                ->where("license_types.is_for_report", "=", 1)
                ->whereIn("license_types.is_for", ["Practice", "Both"])

                ->groupBy("type");


                $missingLicenses = $missingLicenseDocuments->union($missingInsuaranceDocuments)->get()->toArray();
            }
        } else {
            if (count($moreFilter) == 0) {

                $sql = "(SELECT '-' as license_no, name as type, 'Document Missing' as validity, '-' as exp_date, '-' as issue_date, '-' as
                issuing_state,
                (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                FROM `cm_license_types` lt
                WHERE lt.is_mandatory = '1'
                AND lt.is_for_report = 1
                AND lt.is_for = 'Provider'
                AND lt.id NOT IN(30,31,26,32,41)
                AND id NOT IN(SELECT type_id
                FROM cm_user_licenses
                WHERE user_id = '$userId' AND is_delete = 0))
                UNION ALL
                
                (SELECT '-' as license_no, name as type, 'Document Missing' as validity, '-' as exp_date, '-' as issue_date, '-' as
                issuing_state,
                (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                FROM `cm_license_types` lt
                WHERE lt.is_mandatory = '1'
                AND lt.is_for_report = 1
                AND lt.is_for = 'Provider'
                AND lt.id = '41'
                AND id NOT IN(SELECT type_id
                FROM cm_insurance_coverage
                WHERE user_id = '$userId'))

                UNION ALL

                    (SELECT REPLACE(ul.license_no,'" . $userId . "_', '') as license_no ,
                lt.name as type,
                CASE WHEN (ul.exp_date < CURDATE()) THEN 'Expired' WHEN (ul.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL
                    60 DAY)) THEN 'Expiring Soon' WHEN (ul.exp_date> DATE_ADD(NOW(), INTERVAL 60 DAY))
                    THEN 'Valid'
                    ELSE '-'
                    END AS validity,
                    DATE_FORMAT(ul.exp_date, '%m/%d/%Y') as exp_date,
                    DATE_FORMAT(ul.issue_date, '%m/%d/%Y') as issue_date,
                    ul.issuing_state,
                    (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                    FROM cm_user_licenses ul
                    INNER JOIN cm_license_types lt
                    ON lt.id = ul.type_id
                    WHERE ul.user_id = '$userId'
                    AND lt.is_for_report = 1
                    AND ul.is_delete = 0
                    AND ul.is_current_version = 1
                    -- AND ul.document_version = (SELECT max(document_version) FROM cm_user_licenses WHERE type_id = ul.type_id AND user_id = ul.user_id)
                    ORDER BY ul.exp_date)

                    UNION ALL

                    (SELECT REPLACE(policy_number,'" . $userId . "_', '') as license_no,
                    CASE 
                    WHEN ul.type = 'professional' THEN 'Professional Liability Insurance'
                    ELSE 'General Liability Insurance'
                    END AS type,
                    CASE WHEN (ul.expiration_date < CURDATE()) THEN 'Expired' WHEN (ul.expiration_date BETWEEN CURDATE() AND
                        DATE_ADD(CURDATE(), INTERVAL 60 DAY)) THEN 'Expiring Soon' WHEN (ul.expiration_date> DATE_ADD(NOW(), INTERVAL 60
                        DAY))
                        THEN 'Valid'
                        ELSE '-'
                        END AS validity,
                        DATE_FORMAT(ul.expiration_date, '%m/%d/%Y') as exp_date,
                        DATE_FORMAT(ul.effective_date, '%m/%d/%Y') as issue_date,
                        '-' as issuing_state,
                        (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                        FROM cm_insurance_coverage ul
                        INNER JOIN cm_license_types lt
                        ON lt.id = ul.type_id
                        WHERE ul.user_id = '$userId' and lt.is_for_report = 1
                        AND ul.is_current_version = 1
                        
                    ORDER BY ul.expiration_date)
                    UNION ALL

                    SELECT
                    degree as license_no,
                    'Degree' as type,
                    '-' as validity,
                    '-' as exp_date,
                    '-' as issue_date,
                    issuing_institute as issuing_state,
                    concat(first_name, ' ', last_name) as name
                    FROM cm_education
                    left join cm_users on cm_education.user_id = cm_users.id
                    left join cm_license_types on cm_education.type_id = cm_license_types.id
                    where user_id = '$userId' and is_current_version = 1 and is_for_report = 1

                    UNION ALL

                    SELECT
                    admitting_previleges as license_no,
                    'hospital_affiliations' as type,
                        CASE
                        WHEN (end_date < CURDATE()) THEN 'Expired'
                        WHEN (end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)) THEN 'Expiring Soon'
                        WHEN (end_date> DATE_ADD(NOW(), INTERVAL 60 DAY))	THEN 'Valid'
                        ELSE '-'
                        END AS validity,
                    end_date as exp_date,
                    start_date as issue_date,
                    null as issuing_state,
                    concat(first_name, ' ', last_name) as name
                    FROM cm_hospital_affiliations
                    left join cm_users on cm_hospital_affiliations.user_id = cm_users.id
                    left join cm_license_types on cm_hospital_affiliations.type_id = cm_license_types.id
                    where user_id = '$userId' and is_current_version = 1 and is_for_report;

                    ";

                    


                $allLicenses = $this->rawQuery($sql);

                $allLicenses = count($allLicenses) > 0 ? $this->stdToArray($allLicenses) : [];
            }
            if (in_array("all", $moreFilter)) {


                $sql = "(SELECT '-' as license_no, name as type, 'Document Missing' as validity, '-' as exp_date, '-' as issue_date, '-' as
            issuing_state,
            (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
            FROM `cm_license_types` lt
            WHERE lt.is_mandatory = '1'
            AND lt.is_for_report = 1
            AND lt.is_for = 'Provider'
            AND lt.id NOT IN(30,31,26,32,41)
            AND id NOT IN(SELECT type_id
            FROM cm_user_licenses
            WHERE user_id = '$userId' AND is_delete = 0))

            UNION ALL

            (SELECT '-' as license_no, name as type, 'Document Missing' as validity, '-' as exp_date, '-' as issue_date, '-' as
            issuing_state,
            (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
            FROM `cm_license_types` lt
            WHERE lt.is_mandatory = '1'
            AND lt.is_for_report = 1
            AND lt.is_for = 'Provider'
            AND lt.id = '41'
            AND id NOT IN(SELECT type_id
            FROM cm_insurance_coverage
            WHERE user_id = '$userId'))

            UNION ALL

                (SELECT REPLACE(ul.license_no,'" . $userId . "_', '') as license_no ,
            lt.name as type,
            CASE WHEN (ul.exp_date < CURDATE()) THEN 'Expired' WHEN (ul.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL
                60 DAY)) THEN 'Expiring Soon' WHEN (ul.exp_date> DATE_ADD(NOW(), INTERVAL 60 DAY))
                THEN 'Valid'
                ELSE '-'
                END AS validity,
                DATE_FORMAT(ul.exp_date, '%m/%d/%Y') as exp_date,
                DATE_FORMAT(ul.issue_date, '%m/%d/%Y') as issue_date,
                ul.issuing_state,
                (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                FROM cm_user_licenses ul
                INNER JOIN cm_license_types lt
                ON lt.id = ul.type_id
                WHERE ul.user_id = '$userId' AND ul.is_delete = 0
                AND lt.is_for_report = 1
                AND ul.is_current_version = 1
                -- AND ul.document_version = (SELECT max(document_version) FROM cm_user_licenses WHERE type_id = ul.type_id AND user_id = ul.user_id)
                ORDER BY ul.exp_date)

                UNION ALL

                (SELECT REPLACE(policy_number,'" . $userId . "_', '') as license_no,
                CASE 
                    WHEN ul.type = 'professional' THEN 'Professional Liability Insurance'
                    ELSE 'General Liability Insurance'
                    END AS type,
                CASE WHEN (ul.expiration_date < CURDATE()) THEN 'Expired' WHEN (ul.expiration_date BETWEEN CURDATE() AND
                    DATE_ADD(CURDATE(), INTERVAL 60 DAY)) THEN 'Expiring Soon' WHEN (ul.expiration_date> DATE_ADD(NOW(), INTERVAL 60
                    DAY))
                    THEN 'Valid'
                    ELSE '-'
                    END AS validity,
                    DATE_FORMAT(ul.expiration_date, '%m/%d/%Y') as exp_date,
                    DATE_FORMAT(ul.effective_date, '%m/%d/%Y') as issue_date,
                    '-' as issuing_state,
                    (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                    FROM cm_insurance_coverage ul
                    INNER JOIN cm_license_types lt
                    ON lt.id = ul.type_id
                    WHERE ul.user_id = '$userId' AND ul.is_delete = 0 AND lt.is_for_report = 1 AND ul.is_current_version = 1
                    -- AND ul.document_version = (SELECT max(document_version) FROM cm_insurance_coverage WHERE type_id = ul.type_id AND user_id = ul.user_id)
                ORDER BY ul.expiration_date)";
                $allLicenses = $this->rawQuery($sql);

                $allLicenses = count($allLicenses) > 0 ? $this->stdToArray($allLicenses) : [];
            }
            if (in_array("expired", $moreFilter)) {


                $sql = "SELECT REPLACE(ul.license_no,'" . $userId . "_', '') as license_no ,
                lt.name as type,
                'Expired'  AS validity,
                DATE_FORMAT(ul.exp_date, '%m/%d/%Y') as exp_date,
                DATE_FORMAT(ul.issue_date, '%m/%d/%Y') as issue_date,
                ul.issuing_state,
                (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                FROM cm_user_licenses ul
                INNER JOIN cm_license_types lt
                ON lt.id = ul.type_id
                WHERE ul.user_id = '$userId' AND lt.is_for_report = 1 AND ul.is_delete = 0 AND exp_date < CURDATE() AND ul.is_current_version = 1
                -- AND ul.document_version = (SELECT max(document_version) FROM cm_user_licenses WHERE type_id = ul.type_id AND user_id = ul.user_id)
                ORDER BY ul.exp_date";

                $expiredLicenses = $this->rawQuery($sql);

                $expiredLicenses = count($expiredLicenses) > 0 ? $this->stdToArray($expiredLicenses) : [];
            }
            if (in_array("expiring soon", $moreFilter)) {

                $sql = "(SELECT REPLACE(ul.license_no,'" . $userId . "_', '') as license_no ,
                lt.name as type,
                CASE
                WHEN (ul.exp_date < CURDATE()) THEN 'Expired'
                WHEN (ul.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL
                    60 DAY)) THEN 'Expiring Soon'
                    WHEN (ul.exp_date> DATE_ADD(NOW(), INTERVAL 60 DAY))
                    THEN 'Valid'
                    ELSE '-'
                    END AS validity,
                    DATE_FORMAT(ul.exp_date, '%m/%d/%Y') as exp_date,
                    DATE_FORMAT(ul.issue_date, '%m/%d/%Y') as issue_date,
                    ul.issuing_state,
                    (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                    FROM cm_user_licenses ul
                    INNER JOIN cm_license_types lt
                    ON lt.id = ul.type_id
                    WHERE ul.user_id = '$userId' AND lt.is_for_report = 1 AND ul.is_delete = 0 AND ul.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                    AND ul.is_current_version = 1
                    -- AND ul.document_version = (SELECT max(document_version) FROM cm_user_licenses WHERE type_id = ul.type_id AND user_id = ul.user_id)
                    ORDER BY ul.exp_date)

                    UNION ALL

                    (SELECT REPLACE(policy_number,'" . $userId . "_', '') as license_no,
                    CASE 
                    WHEN ul.type = 'professional' THEN 'Professional Liability Insurance'
                    ELSE 'General Liability Insurance'
                    END AS type,
                    CASE WHEN (ul.expiration_date < CURDATE()) THEN 'Expired'
                    WHEN (ul.expiration_date BETWEEN CURDATE() AND
                        DATE_ADD(CURDATE(), INTERVAL 60 DAY)) THEN 'Expiring Soon' WHEN (ul.expiration_date> DATE_ADD(NOW(), INTERVAL 60
                        DAY))
                        THEN 'Valid'
                        ELSE '-'
                        END AS validity,
                        DATE_FORMAT(ul.expiration_date, '%m/%d/%Y') as exp_date,
                        DATE_FORMAT(ul.effective_date, '%m/%d/%Y') as issue_date,
                        '-' as issuing_state,
                        (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                        FROM cm_insurance_coverage ul
                        INNER JOIN cm_license_types lt
                        ON lt.id = ul.type_id
                        WHERE ul.user_id = '$userId' AND lt.is_for_report = 1 AND ul.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                        AND ul.is_current_version = 1
                        -- AND ul.document_version = (SELECT max(document_version) FROM cm_insurance_coverage WHERE type_id = ul.type_id AND user_id = ul.user_id)
                    ORDER BY ul.expiration_date)";

                $soonExpiredLicenses = $this->rawQuery($sql);

                $soonExpiredLicenses = count($soonExpiredLicenses) > 0 ? $this->stdToArray($soonExpiredLicenses) : [];
            }
            if (in_array("valid", $moreFilter)) {

                $sql = "(SELECT REPLACE(ul.license_no,'" . $userId . "_', '') as license_no ,
                lt.name as type,
                CASE
                WHEN (ul.exp_date < CURDATE()) THEN 'Expired'
                WHEN (ul.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL
                    60 DAY)) THEN 'Expiring Soon'
                    WHEN (ul.exp_date> DATE_ADD(NOW(), INTERVAL 60 DAY))
                    THEN 'Valid'
                    ELSE '-'
                    END AS validity,
                    DATE_FORMAT(ul.exp_date, '%m/%d/%Y') as exp_date,
                    DATE_FORMAT(ul.issue_date, '%m/%d/%Y') as issue_date,
                    ul.issuing_state,
                    (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                    FROM cm_user_licenses ul
                    INNER JOIN cm_license_types lt
                    ON lt.id = ul.type_id
                    WHERE ul.user_id = '$userId' AND lt.is_for_report = 1 AND ul.is_delete = 0 AND ul.exp_date > DATE_ADD(NOW(), INTERVAL 60 DAY)
                    -- AND ul.document_version = (SELECT max(document_version) FROM cm_user_licenses WHERE type_id = ul.type_id AND user_id = ul.user_id)
                    ORDER BY ul.exp_date)

                    UNION ALL

                    (SELECT REPLACE(policy_number,'" . $userId . "_', '') as license_no,
                    CASE 
                    WHEN ul.type = 'professional' THEN 'Professional Liability Insurance'
                    ELSE 'General Liability Insurance'
                    END AS type,
                    CASE WHEN (ul.expiration_date < CURDATE()) THEN 'Expired'
                    WHEN (ul.expiration_date BETWEEN CURDATE() AND
                        DATE_ADD(CURDATE(), INTERVAL 60 DAY)) THEN 'Expiring Soon' WHEN (ul.expiration_date> DATE_ADD(NOW(), INTERVAL 60
                        DAY))
                        THEN 'Valid'
                        ELSE '-'
                        END AS validity,
                        DATE_FORMAT(ul.expiration_date, '%m/%d/%Y') as exp_date,
                        DATE_FORMAT(ul.effective_date, '%m/%d/%Y') as issue_date,
                        '-' as issuing_state,
                        (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                        FROM cm_insurance_coverage ul
                        INNER JOIN cm_license_types lt
                        ON lt.id = ul.type_id
                        WHERE ul.user_id = '$userId' AND lt.is_for_report = 1 AND ul.expiration_date > DATE_ADD(NOW(), INTERVAL 60 DAY)
                        AND ul.is_current_version = 1
                        -- AND ul.document_version = (SELECT max(document_version) FROM cm_insurance_coverage WHERE type_id = ul.type_id AND user_id = ul.user_id)
                    ORDER BY ul.expiration_date)";

                $validLicenses = $this->rawQuery($sql);

                $validLicenses = count($validLicenses) > 0 ? $this->stdToArray($validLicenses) : [];
            }
            if (in_array("missing document", $moreFilter)) {

                $sql = "
                (SELECT '-' as license_no, name as type, 'Document Missing' as validity, '-' as exp_date, '-' as issue_date, '-' as issuing_state,
                (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                FROM `cm_license_types` lt
                WHERE lt.is_mandatory = '1'
                AND lt.is_for_report = 1
                AND lt.is_for = 'Provider'
				AND lt.id NOT IN(30,31,26,32,41)
                AND id NOT IN(SELECT type_id
                              FROM cm_user_licenses
                              WHERE user_id = '$userId'))
				UNION ALL
                (SELECT '-' as license_no, name as type, 'Document Missing' as validity, '-' as exp_date, '-' as issue_date, '-' as issuing_state,
                (SELECT CONCAT(cm_users.first_name,' ',cm_users.last_name) FROM cm_users WHERE id = '$userId') as name
                FROM `cm_license_types` lt
                WHERE lt.is_mandatory = '1'
                AND lt.is_for_report = 1
                AND lt.is_for = 'Provider'
				AND lt.id = '41'
                AND id NOT IN(SELECT type_id
                              FROM cm_insurance_coverage
                              WHERE user_id = '$userId'))
                ";

                $missingLicenses = $this->rawQuery($sql);

                $missingLicenses = count($missingLicenses) > 0 ? $this->stdToArray($missingLicenses) : [];
            }
        }


        return array_merge($allLicenses, $expiredLicenses, $soonExpiredLicenses, $validLicenses, $missingLicenses);
    }
    /**
     * fetch the system session report
     *
     * @param \Illuminate\Http\Request $request
     *  @param \Illuminate\Http\Response
     */
    function getAppSessionReport(Request $request)
    {

        set_time_limit(0);

        $page = $request->has('page') ? $request->get('page') : 1;

        $offset = $page - 1;

        $newOffset = $this->cmperPage * $offset;

        $perPage = $request->has('per_page') ? $request->get('per_page') : $this->cmperPage;

        $sessionData = SessionLog::select(
            DB::raw('DATE_FORMAT(cm_user_accountactivity.session_buid_at, "%r") as signin_time'),
            DB::raw('DATE_FORMAT(cm_user_accountactivity.session_expired_at, "%r") as signout_time'),
            DB::raw('DATE_FORMAT(cm_user_accountactivity.session_expired_at, "%m/%d/%Y") as signout_date'),
            DB::raw('DATE_FORMAT(cm_user_accountactivity.session_buid_at, "%m/%d/%Y") as signin_date'),
            "user_accountactivity.ip",
            "user_accountactivity.iso_code",
            "user_accountactivity.country",
            "user_accountactivity.city",
            "user_accountactivity.state",
            "user_accountactivity.state_name",
            "user_accountactivity.postal_code",
            "user_accountactivity.lat",
            "user_accountactivity.lon",
            "user_accountactivity.timezone",
            "user_accountactivity.continent",
            "user_accountactivity.device",
            "user_accountactivity.os",
            "user_accountactivity.browser",
            "user_accountactivity.os_version",
            "user_accountactivity.browser_version",
            "user_accountactivity.robot",
            DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS user_name")
        )

            ->join('users', 'users.id', 'user_accountactivity.user_id')

            ->orderBy('user_accountactivity.id', 'ASC')

            ->join('users', 'users.id', 'user_accountactivity.user_id')

            ->orderBy('user_accountactivity.id', 'ASC')

            ->orderBy('user_accountactivity.user_id', 'ASC')

            ->offset($newOffset)

            ->limit($perPage)

            ->get();

        return $this->successResponse(['session_data' => $sessionData], 'success');
    }
    /**
     * fetch the system usage report
     *
     * @param \Illuminate\Http\Request $request
     *  @param \Illuminate\Http\Response
     */
    function getAppUsageReport(Request $request)
    {

        set_time_limit(0);

        $page = $request->has('page') ? $request->get('page') : 1;

        $perPage = $request->has('per_page') ? $request->get('per_page') : $this->cmperPage;

        $appLogsData = $this->fetchAppActivityData($page, $perPage);
        $appLogsData = $this->fetchAppActivityData($page, $perPage);

        return $this->successResponse(['usage_data' => $appLogsData], 'success');
    }
    
    /**
     * payer average comparison report
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    function payerAverageComparisonReport(Request $request)
    {
        $request->validate([
            "dos_filter" => "required",
            "practice_ids" => "required"
        ]);

        $key = env("AES_KEY");

        $dosFilter = json_decode($request->dos_filter, true);

        $filterStartDate = $this->formatDate($dosFilter["startDate"]);
        $filterStartDate = date('Y-m-d',strtotime($filterStartDate));

        $filterEndDate = $this->formatDate($dosFilter["endDate"]);
        $filterEndDate = date('Y-m-d',strtotime($filterEndDate));


        $subQuery = "SELECT
        COUNT(ar.claim_no) AS total_claims,
        SUM(ar.paid_amount) AS total_paid,
        (SUM(ar.paid_amount) / COUNT(ar.claim_no)) AS average_ar,
        cm_payers.payer_name,
        ar.payer_id,
        ar.facility_id,
        MONTH(ar.dos) AS MONTH
    FROM
        cm_account_receivable AS ar
        INNER JOIN cm_payers ON cm_payers.id = ar.payer_id
    WHERE
        ar.status IN (5,6,8,2)
        AND ar.is_delete = 0
        AND ar.dos BETWEEN '$filterStartDate' AND '$filterEndDate'";
        if ($request->has("facility_ids")) {
            $facilityIds = json_decode($request->facility_ids, true);
            $facilityIdsStr = implode(",", $facilityIds);
            $subQuery .= " AND ar.facility_id IN ($facilityIdsStr)";
        }

        if ($request->has("practice_ids")) {
            $practiceIds = json_decode($request->practice_ids, true);
            $practiceIdsStr = implode(",", $practiceIds);
            $subQuery .= " AND ar.practice_id IN($practiceIdsStr)";
        }


        $subQuery .= " GROUP BY MONTH(ar.dos),ar.payer_id,ar.facility_id";

        $result = DB::table(DB::raw("($subQuery) AS subquery"))
            ->select([
                'total_claims',
                DB::raw('ROUND(total_paid, 2) AS total_paid'),
                DB::raw('ROUND(average_ar, 2) AS average_ar'),
                'payer_name',
                'payer_id',
                'MONTH',
                'facility_id',
                DB::raw("(SELECT AES_DECRYPT(practice_name,'$key') FROM cm_user_ddpracticelocationinfo WHERE user_id = facility_id) AS facility_name"),
                DB::raw('0 AS percentage')
            ])
            ->orderByDesc('total_claims')
            ->get();

            

        // $this->printR($result,true);
        // Initialize the final result array
        $finalResultArray = array();
        $payerOverAllAvg = array();
        $months = array();
        $avgs = array();
        $eachPayerAvg = [];
        //each payer month count along with total number of avg
        if ($result->count() > 0) {
            foreach ($result as $payer) {
                if (isset($months[$payer->payer_name]))
                    $months[$payer->payer_name] += 1;
                else
                    $months[$payer->payer_name] = 1;


                if (isset($avgs[$payer->payer_name]))
                    $avgs[$payer->payer_name] +=  $payer->average_ar;
                else
                    $avgs[$payer->payer_name] = $payer->average_ar;
            }
        }
        //each payer avg
        if (count($avgs)) {
            foreach ($avgs as $key => $value) {
                $eachPayerAvg[$key] = $value / $months[$key];
            }
        }

        if ($result->count() > 0) {
            foreach ($result as $payer) {
                $monthName = date('F', mktime(0, 0, 0, $payer->MONTH, 1));

                if ($eachPayerAvg[$payer->payer_name] > 0) {
                    $payer->percentage = round(($payer->average_ar / $eachPayerAvg[$payer->payer_name]) * 100);

                    $payer->percentage_color = "";
                    if ($payer->percentage > 100)
                        $payer->percentage_color = "green";
                    elseif ($payer->percentage < 100)
                        $payer->percentage_color = "red";
                } else
                    $payer->percentage_color = "";


                $payer->average_ar = "$ " . $payer->average_ar;
                $finalResultArray[$payer->payer_name][$monthName][$payer->facility_id] = $payer;
            }
        }


        return $this->successResponse(['report' => $finalResultArray], 'success');
    }
    /**
     * payer avg report for the provider
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    public function payerAverageComparisonProviderReport(Request $request)
    {

        $request->validate([
            "dos_filter" => "required",
            "practice_ids" => "required"
        ]);

        $key = env("AES_KEY");

        $dosFilter = json_decode($request->dos_filter, true);

        $filterStartDate = $this->formatDate($dosFilter["startDate"]);

        $filterEndDate = $this->formatDate($dosFilter["endDate"]);


        $subQuery = "SELECT
        COUNT(ar.claim_no) AS total_claims,
        SUM(ar.paid_amount) AS total_paid,
        (SUM(ar.paid_amount) / COUNT(ar.claim_no)) AS average_ar,
        cm_payers.payer_name,
        ar.payer_id,
        ar.facility_id,
        billing.billing_provider_id AS provider_id,
        MONTH(ar.dos) AS MONTH
    FROM
        cm_account_receivable AS ar
        INNER JOIN cm_payers ON cm_payers.id = ar.payer_id
        INNER JOIN cm_billing AS billing ON billing.claim_no = ar.claim_no
    WHERE
        ar.status IN (5,6,8,2)
        AND ar.is_delete = 0
        AND ar.dos BETWEEN '$filterStartDate' AND '$filterEndDate'";
        if ($request->has("facility_ids")) {
            $facilityIds = json_decode($request->facility_ids, true);
            $facilityIdsStr = implode(",", $facilityIds);
            $subQuery .= " AND ar.facility_id IN ($facilityIdsStr)";
        }
        if ($request->has("provider_ids")) {
            $providerIds = json_decode($request->provider_ids, true);
            $providerIdsStr = implode(",", $providerIds);
            $subQuery .= " AND billing.billing_provider_id IN ($providerIdsStr)";
        }

        if ($request->has("practice_ids")) {
            $practiceIds = json_decode($request->practice_ids, true);
            $practiceIdsStr = implode(",", $practiceIds);
            $subQuery .= " AND ar.practice_id IN($practiceIdsStr)";
        }


        $subQuery .= " GROUP BY MONTH(ar.dos),ar.payer_id,ar.facility_id,billing.billing_provider_id";

        $result = DB::table(DB::raw("($subQuery) AS subquery"))
            ->select([
                'total_claims',
                DB::raw('ROUND(total_paid, 2) AS total_paid'),
                DB::raw('ROUND(average_ar, 2) AS average_ar'),
                'payer_name',
                'payer_id',
                'MONTH',
                'facility_id',
                'provider_id',
                DB::raw("(SELECT AES_DECRYPT(practice_name,'$key') FROM cm_user_ddpracticelocationinfo WHERE user_id = facility_id) AS facility_name"),
                DB::raw("(SELECT UPPER(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) FROM cm_users WHERE id = provider_id) AS provider_name"),
                DB::raw('0 AS percentage')
            ])
            ->orderByDesc('total_claims')
            ->get();

        // $this->printR($result,true);
        // Initialize the final result array
        $finalResultArray = array();
        $months = array();
        $avgs = array();
        $eachPayerAvg = [];
        //each payer month count along with total number of avg
        if ($result->count() > 0) {
            foreach ($result as $payer) {
                if (isset($months[$payer->payer_name]))
                    $months[$payer->payer_name] += 1;
                else
                    $months[$payer->payer_name] = 1;


                if (isset($avgs[$payer->payer_name]))
                    $avgs[$payer->payer_name] +=  $payer->average_ar;
                else
                    $avgs[$payer->payer_name] = $payer->average_ar;
            }
        }
        //each payer avg
        if (count($avgs)) {
            foreach ($avgs as $key => $value) {
                $eachPayerAvg[$key] = $value / $months[$key];
            }
        }

        if ($result->count() > 0) {
            foreach ($result as $payer) {
                $monthName = date('F', mktime(0, 0, 0, $payer->MONTH, 1));

                if ($eachPayerAvg[$payer->payer_name] > 0) {
                    $payer->percentage = round(($payer->average_ar / $eachPayerAvg[$payer->payer_name]) * 100);

                    $payer->percentage_color = "";
                    if ($payer->percentage > 100)
                        $payer->percentage_color = "green";
                    elseif ($payer->percentage < 100)
                        $payer->percentage_color = "red";
                } else
                    $payer->percentage_color = "";


                $payer->average_ar = "$ " . $payer->average_ar;
                $finalResultArray[$payer->payer_name][$monthName][$payer->facility_id][$payer->provider_name] = $payer;
            }
        }


        return $this->successResponse(['report' => $finalResultArray], 'success');
    }
}
