<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ARBackup;
use App\Models\ArStatus;
use App\Models\ARReports;
use DB;
use Carbon\Carbon;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\AccountReceivable;
class ARReportsController extends Controller
{
    use ApiResponseHandler, Utility;
    private $tbl = "user_ddpracticelocationinfo";
    /**
     * AR Trend report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function arTrendReport(Request $request)
    {
        $request->validate([
            "facilities_filter" => "required|json:Please provide a valid JSON string"
        ]);

        $facilityIdsArr = json_decode($request->facilities_filter, true);

        $practicesArr = json_decode($request->practices_filter, true);

        $dateRangeFilter = json_decode($request->date_range_filter, true);

        $startDate = $dateRangeFilter['startDate'];
        $startDate = date('Y-m-d', strtotime($startDate));

        $endDate = $dateRangeFilter['endDate'];
        $endDate = date('Y-m-d', strtotime($endDate));

        $report = [];
        $baseRowReport = [];
        $arBackupObj = new ARBackup();

        foreach($practicesArr as $practice) {
            $practiceId = $practice['value'];

            foreach ($facilityIdsArr as $facilityId) {

                if (strpos($facilityId, ".") !== false) {

                    $backupData = $arBackupObj->arTrendReport($practiceId,$facilityId, $startDate, $endDate);

                    $baseData = $arBackupObj->arBaseRow($practiceId,$facilityId, $startDate, $endDate);

                    if (count($backupData) > 0) {
                        $rowData = [];

                        foreach ($backupData as $backup) {
                            if (count($report)) {

                                $sameDate = $this->chkDate($report, $backup->report_date);

                                if ($sameDate['index'] === -1) {
                                    $rowData = [
                                        "date"            => $backup->report_date,
                                        "claims"          => [$facilityId => $backup->total_claims],
                                        "amounts"         => [$facilityId => round($backup->total_amount,2)],
                                        "totalClaims"     => $backup->total_claims,
                                        "totalAmount"     => round($backup->total_amount,2),
                                        "percentage"      => 0,
                                    ];

                                    array_push($report, $rowData);
                                } else {
                                    $recIndx                                 = $sameDate['index'];
                                    $tempData                                = $report[$recIndx];
                                    $tempData["claims"][$facilityId]         = $backup->total_claims;
                                    $tempData["amounts"][$facilityId]        = round($backup->total_amount,2);
                                    $tempData["totalClaims"]                += $backup->total_claims;
                                    $tempData["totalAmount"]                += round($backup->total_amount,2);
                                    $report[$recIndx]                        = $tempData;
                                }
                            } else {

                                $rowData["date"]            = $backup->report_date;
                                $rowData["claims"]          = [$facilityId => $backup->total_claims];
                                $rowData["amounts"]         = [$facilityId => round($backup->total_amount,2)];
                                $rowData["totalClaims"]     = $backup->total_claims;
                                $rowData["totalAmount"]     = round($backup->total_amount,2);
                                $rowData["percentage"]      = 0;

                                array_push($report, $rowData);
                            }
                        }
                    }


                    if (count($baseData) > 0) {
                        $rowData = [];
                        foreach ($baseData as $backup) {
                            if (count($baseRowReport)) {
                                $sameDate = $this->chkDate($baseRowReport, $backup->report_date);

                                if ($sameDate['index'] === -1) {
                                    $rowData = [
                                        "date"           => $backup->report_date,
                                        "claims"         => [$facilityId => $backup->total_claims],
                                        "amounts"        => [$facilityId => round($backup->total_amount,2)],
                                        "totalClaims"    => $backup->total_claims,
                                        "totalAmount"    => round($backup->total_amount,2),
                                        "percentage"     => 0,
                                    ];

                                    array_push($baseRowReport, $rowData);
                                } else {
                                    $recIndx                                    = $sameDate['index'];
                                    $tempData                                   = $baseRowReport[$recIndx];
                                    $tempData["claims"][$facilityId]            = $backup->total_claims;
                                    $tempData["amounts"][$facilityId]           = round($backup->total_amount,2);
                                    $tempData["totalClaims"]                   += $backup->total_claims;
                                    $tempData["totalAmount"]                   += round($backup->total_amount,2);
                                    $baseRowReport[$recIndx]                    = $tempData;
                                }
                            } else {

                                $rowData["date"]            = $backup->report_date;
                                $rowData["claims"]          = [$facilityId => $backup->total_claims];
                                $rowData["amounts"]         = [$facilityId => round($backup->total_amount,2)];
                                $rowData["totalClaims"]     = $backup->total_claims;
                                $rowData["totalAmount"]     = round($backup->total_amount,2);
                                $rowData["percentage"]      = 0;

                                array_push($baseRowReport, $rowData);
                            }
                        }
                    }
                }
                else {
                    $facilityIdStr = $facilityId;

                    $facilityId = $this->removeDecimalFromString($facilityId);

                    $backupData = $arBackupObj->arTrendReport($practiceId,$facilityId, $startDate, $endDate);

                    $baseData = $arBackupObj->arBaseRow($practiceId,$facilityId, $startDate, $endDate);

                    if (count($backupData) > 0) {
                        $rowData = [];

                        foreach ($backupData as $backup) {
                            if (count($report)) {

                                $sameDate = $this->chkDate($report, $backup->report_date);

                                if ($sameDate['index'] === -1) {
                                    $rowData = [
                                        "date"            => $backup->report_date,
                                        "claims"          => [$facilityIdStr => $backup->total_claims],
                                        "amounts"         => [$facilityIdStr => round($backup->total_amount,2)],
                                        "totalClaims"     => $backup->total_claims,
                                        "totalAmount"     => round($backup->total_amount,2),
                                        "percentage"      => 0,
                                    ];

                                    array_push($report, $rowData);
                                } else {
                                    $recIndx                                 = $sameDate['index'];
                                    $tempData                                = $report[$recIndx];
                                    $tempData["claims"][$facilityIdStr]         = $backup->total_claims;
                                    $tempData["amounts"][$facilityIdStr]        = round($backup->total_amount,2);
                                    $tempData["totalClaims"]                += $backup->total_claims;
                                    $tempData["totalAmount"]                += round($backup->total_amount,2);
                                    $report[$recIndx]                        = $tempData;
                                }
                            } else {

                                $rowData["date"]            = $backup->report_date;
                                $rowData["claims"]          = [$facilityIdStr => $backup->total_claims];
                                $rowData["amounts"]         = [$facilityIdStr => round($backup->total_amount,2)];
                                $rowData["totalClaims"]     = $backup->total_claims;
                                $rowData["totalAmount"]     = round($backup->total_amount,2);
                                $rowData["percentage"]      = 0;

                                array_push($report, $rowData);
                            }
                        }
                    }

                    if (count($baseData) > 0) {
                        $rowData = [];
                        foreach ($baseData as $backup) {
                            if (count($baseRowReport)) {
                                $sameDate = $this->chkDate($baseRowReport, $backup->report_date);

                                if ($sameDate['index'] === -1) {
                                    $rowData = [
                                        "date"           => $backup->report_date,
                                        "claims"         => [$facilityIdStr => $backup->total_claims],
                                        "amounts"        => [$facilityIdStr => round($backup->total_amount,2)],
                                        "totalClaims"    => $backup->total_claims,
                                        "totalAmount"    => round($backup->total_amount,2),
                                        "percentage"     => 0,
                                    ];

                                    array_push($baseRowReport, $rowData);
                                } else {
                                    $recIndx                                    = $sameDate['index'];
                                    $tempData                                   = $baseRowReport[$recIndx];
                                    $tempData["claims"][$facilityIdStr]            = $backup->total_claims;
                                    $tempData["amounts"][$facilityIdStr]           = round($backup->total_amount,2);
                                    $tempData["totalClaims"]                   += $backup->total_claims;
                                    $tempData["totalAmount"]                   += round($backup->total_amount,2);
                                    $baseRowReport[$recIndx]                    = $tempData;
                                }
                            } else {

                                $rowData["date"]            = $backup->report_date;
                                $rowData["claims"]          = [$facilityIdStr => $backup->total_claims];
                                $rowData["amounts"]         = [$facilityIdStr => round($backup->total_amount,2)];
                                $rowData["totalClaims"]     = $backup->total_claims;
                                $rowData["totalAmount"]     = round($backup->total_amount,2);
                                $rowData["percentage"]      = 0;

                                array_push($baseRowReport, $rowData);
                            }
                        }
                    }
                }
            }
        }
        $arBackupObj = NULL;
        if (count($report)) // sorting the date wise report data
            $report = collect($report)->sortBy('date')->values()->all();

        return $this->successResponse(["report" => $report, 'base_row' => $baseRowReport], "success");
    }
    /**
     * check if date exist into data
     *
     * @param $data
     * @param $date
     */
    private function chkDate($data, $date)
    {
        $res = ["index" => -1, "same_date" => false];
        foreach ($data as $key => $eachData) {
            $date_ = date("Y-m-d", strtotime($eachData['date']));
            $date__ = date("Y-m-d", strtotime($date));
            $date1 = Carbon::parse($date_);
            $date2 = Carbon::parse($date__);
            if ($date1->eq($date2)) {
                $res  = ["index" => $key, "same_date" => true];
            }
        }
        return $res;
    }
    /**
     * ar distribution by reason report
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function arDistributionByReason(Request $request)
    {

        $request->validate([
            "practices_filter"  => "required",
            "facilities_filter" => "required",
            "status_filter"     => "required",
            "date_range_filter" => "required"
        ]);
        $ARReportsObj = new ARReports();
        // $arStatusAll = ArStatus::get();
        //$this->printR($arStatusAll,true);
        $practiceFilterArr = json_decode($request->practices_filter, true);

        $facilitiesFilterArr = json_decode($request->facilities_filter, true);

        $statusFilterArr = json_decode($request->status_filter, true);

        $dateRangeFilter = json_decode($request->date_range_filter, true);

        $remarksFilter = json_decode($request->remarks_filter, true);

        // $this->printR($facilitiesFilterArr,true);

        $reportJSON = [];
        $finalRow = ["total_claims" => [], "total_billed_amounts" => [], "total_paid_amounts" => [], "claims_grand_total" => 0, "billed_grand_total" => 0, "paid_grand_total" => 0];
        if (count($practiceFilterArr)) {
            $grandTotalClaims = 0;
            $grandTotalPaidAmount = 0;
            $grandTotalBilledAmount = 0;
            foreach ($practiceFilterArr as $indx => $practice) {
                $practiceId = $practice['value'];
                foreach ($facilitiesFilterArr as $facilityId) {
                    $whereParent = [
                        ["user_parent_id", "=", $practiceId],
                        ["user_id", "=", $facilityId]
                    ];
                    $facility  = $this->fetchData($this->tbl, $whereParent, 1);

                    if (is_object($facility) && strpos($facilityId, ".") === false) {
                        $totalClaims = 0;
                        $totalPaidAmount = 0;
                        $totalBilledAmount = 0;
                        //$reportJSON[$indx] = ["facility_id" => $facilityId,"facility_name"  => $facility->practice_name,'data' => []];
                        foreach ($statusFilterArr as $statusId) {
                            $arStatus       = $this->fetchData("revenue_cycle_status", ['id' => $statusId], 1);
                            $reportData = $ARReportsObj->arDistributionByReason($practiceId, $facilityId, $statusId, $dateRangeFilter["startDate"], $dateRangeFilter["endDate"], 0);
                            if (count($reportData)) {
                                $rowData = [];
                                foreach ($reportData as $eachreportData) {
                                    if (count($reportJSON)) {
                                        $sameStatus = $this->chkStatus($reportJSON, $eachreportData->status);
                                        if ($sameStatus['index'] == '-1') {
                                            $totalClaims += is_null($eachreportData->total_claims) ? 0 : $eachreportData->total_claims;
                                            $totalBilledAmount += is_null($eachreportData->total_billed_amount) ? 0 : round($eachreportData->total_billed_amount,2);
                                            $totalPaidAmount += is_null($eachreportData->total_paid_amount) ? 0 : round($eachreportData->total_paid_amount,2);
                                            // $grandTotalClaims += $totalClaims;
                                            // $grandTotalAmount += $totalAmount;
                                            $rowData["status_name"]             = $eachreportData->status;
                                            $rowData["status_id"]               = $statusId;
                                            $rowData["claims"]                  = [$facilityId => $eachreportData->total_claims];
                                            $rowData["billed_amounts"]          = [$facilityId => round($eachreportData->total_billed_amount,2)];
                                            $rowData["paid_amounts"]            = [$facilityId => round($eachreportData->total_paid_amount,2)];
                                            $rowData["totalClaims"]             = $eachreportData->total_claims;
                                            $rowData["totalBilledAmount"]       = round($eachreportData->total_billed_amount,2);
                                            $rowData["totalPaidAmount"]         = round($eachreportData->total_paid_amount,2);

                                            array_push($reportJSON, $rowData);
                                        } else {
                                            $totalBilledAmount += is_null($eachreportData->total_billed_amount) ? 0 : round($eachreportData->total_billed_amount,2);
                                            $totalPaidAmount += is_null($eachreportData->total_paid_amount) ? 0 : round($eachreportData->total_paid_amount,2);
                                            $totalClaims += is_null($eachreportData->total_claims) ? 0 : $eachreportData->total_claims;
                                            // $grandTotalClaims += $totalClaims;
                                            // $grandTotalAmount += $totalAmount;
                                            $recIndx                                    = $sameStatus['index'];
                                            $tempData                                   = $reportJSON[$recIndx];
                                            $tempData["claims"][$facilityId]            = $eachreportData->total_claims;
                                            $tempData["billed_amounts"][$facilityId]    = round($eachreportData->total_billed_amount,2);
                                            $tempData["paid_amounts"][$facilityId]      = round($eachreportData->total_paid_amount,2);
                                            $tempData["totalClaims"]                    += $eachreportData->total_claims;
                                            $tempData["totalBilledAmount"]              += round($eachreportData->total_billed_amount,2);
                                            $tempData["totalPaidAmount"]                += round($eachreportData->total_paid_amount,2);
                                            $reportJSON[$recIndx]                       = $tempData;
                                        }
                                    } else {
                                        $totalBilledAmount += is_null($eachreportData->total_billed_amount) ? 0 : round($eachreportData->total_billed_amount,2);
                                        $totalPaidAmount += is_null($eachreportData->total_paid_amount) ? 0 : round($eachreportData->total_paid_amount,2);
                                        $totalClaims += is_null($eachreportData->total_claims) ? 0 : $eachreportData->total_claims;
                                        // $grandTotalClaims += $totalClaims;
                                        // $grandTotalAmount += $totalAmount;
                                        $rowData["status_name"]             = $eachreportData->status;
                                        $rowData["status_id"]               = $statusId;
                                        $rowData["claims"]                  = [$facilityId => $eachreportData->total_claims];
                                        $rowData["billed_amounts"]          = [$facilityId => round($eachreportData->total_billed_amount,2)];
                                        $rowData["paid_amounts"]            = [$facilityId => round($eachreportData->total_paid_amount,2)];
                                        $rowData["totalClaims"]             = $eachreportData->total_claims;
                                        $rowData["totalBilledAmount"]       = round($eachreportData->total_billed_amount,2);
                                        $rowData["totalPaidAmount"]         = round($eachreportData->total_paid_amount,2);
                                        //$this->printR($rowData,true);
                                        array_push($reportJSON, $rowData);
                                    }
                                }
                            } else {
                                $sameStatus = $this->chkStatus($reportJSON, $arStatus->status);
                                if ($sameStatus['index'] == '-1') {
                                    $rowData["status_name"]         = $arStatus->status;
                                    $rowData["status_id"]           = $statusId;
                                    $rowData["claims"]              = [$facilityId => null];
                                    $rowData["billed_amounts"]      = [$facilityId => null];
                                    $rowData["paid_amounts"]        = [$facilityId => null];
                                    $rowData["totalClaims"]         = 0;
                                    $rowData["totalBilledAmount"]   = 0;
                                    $rowData["totalPaidAmount"]     = 0;
                                    //$this->printR($rowData,true);
                                    array_push($reportJSON, $rowData);
                                } else {
                                    $recIndx                            = $sameStatus['index'];
                                    $tempData                           = $reportJSON[$recIndx];
                                    $tempData["claims"][$facilityId]    = null;
                                    $tempData["billed_amounts"][$facilityId]   = null;
                                    $tempData["paid_amounts"][$facilityId]   = null;
                                    $tempData["totalClaims"]            += 0;
                                    $tempData["totalBilledAmount"]      += 0;
                                    $tempData["totalPaidAmount"]        += 0;
                                    $reportJSON[$recIndx]               = $tempData;
                                }
                            }
                        }
                        $finalRow["total_claims"][$facilityId] = $totalClaims;
                        $finalRow["total_billed_amounts"][$facilityId] = $totalBilledAmount;
                        $finalRow["total_paid_amounts"][$facilityId] = $totalPaidAmount;
                        $grandTotalClaims += $totalClaims;
                        $grandTotalBilledAmount += $totalBilledAmount;
                        $grandTotalPaidAmount += $totalPaidAmount;
                    } else {
                        $facilityIdStr = $facilityId;
                        $shelterId = "";
                        $shelter = 0;
                        // $facilityId = $this->removeDecimalFromString($facilityId);
                        //bellow code for segregation of facility and shelter from string
                        if(strpos($facilityId, ".") !== false) {
                           $shelter = 1;
                            $facilityId = explode(".",$facilityIdStr)[0];
                            $shelterId = explode(".",$facilityIdStr)[1];
                        }

                        //$shelter  = $this->chkShelter($practiceId, $facilityId);

                        if ($shelter > 0) {

                            $totalClaims = 0;
                            $totalPaidAmount = 0;
                            $totalBilledAmount = 0;
                            //$reportJSON[$indx] = ["facility_id" => $facilityId,"facility_name"  => $facility->practice_name,'data' => []];
                            foreach ($statusFilterArr as $statusId) {
                                $arStatus       = $this->fetchData("revenue_cycle_status", ['id' => $statusId], 1);
                                $reportData = $ARReportsObj->arDistributionByReason($practiceId, $facilityId, $statusId, $dateRangeFilter["startDate"], $dateRangeFilter["endDate"], 1,$shelterId);
                                if (count($reportData)) {
                                    $rowData = [];
                                    foreach ($reportData as $eachreportData) {
                                        if (count($reportJSON)) {
                                            $sameStatus = $this->chkStatus($reportJSON, $eachreportData->status);
                                            if ($sameStatus['index'] == '-1') {
                                                $totalClaims += is_null($eachreportData->total_claims) ? 0 : $eachreportData->total_claims;
                                                $totalBilledAmount += is_null($eachreportData->total_billed_amount) ? 0 : round($eachreportData->total_billed_amount,2);
                                                $totalPaidAmount += is_null($eachreportData->total_paid_amount) ? 0 : round($eachreportData->total_paid_amount,2);
                                                // $grandTotalClaims += $totalClaims;
                                                // $grandTotalAmount += $totalAmount;
                                                $rowData["status_name"]             = $eachreportData->status;
                                                $rowData["status_id"]               = $statusId;
                                                $rowData["claims"]                  = [$facilityIdStr => $eachreportData->total_claims];
                                                $rowData["billed_amounts"]          = [$facilityIdStr => round($eachreportData->total_billed_amount,2)];
                                                $rowData["paid_amounts"]            = [$facilityIdStr => round($eachreportData->total_paid_amount,2)];
                                                $rowData["totalClaims"]             = $eachreportData->total_claims;
                                                $rowData["totalBilledAmount"]       = round($eachreportData->total_billed_amount,2);
                                                $rowData["totalPaidAmount"]         = round($eachreportData->total_paid_amount,2);

                                                array_push($reportJSON, $rowData);
                                            } else {
                                                $totalBilledAmount += is_null($eachreportData->total_billed_amount) ? 0 : round($eachreportData->total_billed_amount,2);
                                                $totalPaidAmount += is_null($eachreportData->total_paid_amount) ? 0 : round($eachreportData->total_paid_amount,2);
                                                $totalClaims += is_null($eachreportData->total_claims) ? 0 : $eachreportData->total_claims;
                                                // $grandTotalClaims += $totalClaims;
                                                // $grandTotalAmount += $totalAmount;
                                                $recIndx                                    = $sameStatus['index'];
                                                $tempData                                   = $reportJSON[$recIndx];
                                                $tempData["claims"][$facilityIdStr]            = $eachreportData->total_claims;
                                                $tempData["billed_amounts"][$facilityIdStr]    = round($eachreportData->total_billed_amount,2);
                                                $tempData["paid_amounts"][$facilityIdStr]      = round($eachreportData->total_paid_amount,2);
                                                $tempData["totalClaims"]                    += $eachreportData->total_claims;
                                                $tempData["totalBilledAmount"]              += round($eachreportData->total_billed_amount,2);
                                                $tempData["totalPaidAmount"]                += round($eachreportData->total_paid_amount,2);
                                                $reportJSON[$recIndx]                       = $tempData;
                                            }
                                        } else {
                                            $totalBilledAmount += is_null($eachreportData->total_billed_amount) ? 0 : round($eachreportData->total_billed_amount,2);
                                            $totalPaidAmount += is_null($eachreportData->total_paid_amount) ? 0 : round($eachreportData->total_paid_amount,2);
                                            $totalClaims += is_null($eachreportData->total_claims) ? 0 : $eachreportData->total_claims;
                                            // $grandTotalClaims += $totalClaims;
                                            // $grandTotalAmount += $totalAmount;
                                            $rowData["status_name"]             = $eachreportData->status;
                                            $rowData["status_id"]               = $statusId;
                                            $rowData["claims"]                  = [$facilityIdStr => $eachreportData->total_claims];
                                            $rowData["billed_amounts"]          = [$facilityIdStr => round($eachreportData->total_billed_amount,2)];
                                            $rowData["paid_amounts"]            = [$facilityIdStr => round($eachreportData->total_paid_amount,2)];
                                            $rowData["totalClaims"]             = $eachreportData->total_claims;
                                            $rowData["totalBilledAmount"]       = round($eachreportData->total_billed_amount,2);
                                            $rowData["totalPaidAmount"]         = round($eachreportData->total_paid_amount,2);
                                            //$this->printR($rowData,true);
                                            array_push($reportJSON, $rowData);
                                        }
                                    }
                                } else {
                                    $sameStatus = $this->chkStatus($reportJSON, $arStatus->status);
                                    if ($sameStatus['index'] == '-1') {
                                        $rowData["status_name"]         = $arStatus->status;
                                        $rowData["status_id"]           = $statusId;
                                        $rowData["claims"]              = [$facilityIdStr => null];
                                        $rowData["billed_amounts"]      = [$facilityIdStr => null];
                                        $rowData["paid_amounts"]        = [$facilityIdStr => null];
                                        $rowData["totalClaims"]         = 0;
                                        $rowData["totalBilledAmount"]   = 0;
                                        $rowData["totalPaidAmount"]     = 0;
                                        //$this->printR($rowData,true);
                                        array_push($reportJSON, $rowData);
                                    } else {
                                        $recIndx                            = $sameStatus['index'];
                                        $tempData                           = $reportJSON[$recIndx];
                                        $tempData["claims"][$facilityIdStr]    = null;
                                        $tempData["billed_amounts"][$facilityIdStr]   = null;
                                        $tempData["paid_amounts"][$facilityIdStr]   = null;
                                        $tempData["totalClaims"]            += 0;
                                        $tempData["totalBilledAmount"]      += 0;
                                        $tempData["totalPaidAmount"]        += 0;
                                        $reportJSON[$recIndx]               = $tempData;
                                    }
                                }
                            }
                            $finalRow["total_claims"][$facilityIdStr] = $totalClaims;
                            $finalRow["total_billed_amounts"][$facilityIdStr] = $totalBilledAmount;
                            $finalRow["total_paid_amounts"][$facilityIdStr] = $totalPaidAmount;
                            $grandTotalClaims += $totalClaims;
                            $grandTotalBilledAmount += $totalBilledAmount;
                            $grandTotalPaidAmount += $totalPaidAmount;
                        }
                    }
                }
            }
            $finalRow["claims_grand_total"] = $grandTotalClaims;
            $finalRow["billed_grand_total"] = $grandTotalBilledAmount;
            $finalRow["paid_grand_total"] = $grandTotalPaidAmount;
        }
        $ARReportsObj = NULL;
        // $this->printR($reportJSON,true);
        return $this->successResponse(["report" => $reportJSON, 'total_row' => $finalRow], "success");
    }
    /**
     * check if status exist into data
     *
     * @param $data
     * @param $date
     */
    private function chkStatus($data, $status)
    {
        $res = ["index" => -1, "same_status" => false];
        foreach ($data as $key => $eachData) {

            if ($eachData['status_name'] == $status) {
                $res  = ["index" => $key, "same_status" => true];
            }
        }
        return $res;
    }

    /**
     * ar distribution by user
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @param  \Illuminate\Http\Response
     */
    public function arDistributionByUser(Request $request)
    {
        set_time_limit(0);
        $employees = $request->employees;

        $employees = json_decode($employees,true);

        $dateRangeFilter = $request->date_range_filter;
        $arReportObj = new ARReports();
        $dateRangeFilterArr = json_decode($dateRangeFilter,true);

        // $this->printR($dateRangeFilterArr,true);
        $resJSON = [];
        $resJSON = [
            'totalRow' => [
            "employee" => "TOTAL",
            "total" => [
                "open_claims"   => 0,
                "worked_on"     => 0,
                "claims_closed" => 0,
                "claims_paid"   => 0,
                "amount_paid"   => 0
            ],
            "record" => [],
            ],
            "report" => []
        ];
        if(count($employees)) {
            $grandOpenClaims = 0;
            $grandWorkedOn = 0;
            $grandClaimsClosed = 0;
            $grandClaimsPaid = 0;
            $grandAmountPaid = 0;

            foreach($employees as $key=>$employee) {
                $openClaims = 0;
                $workedOn = 0;
                $claimsClosed = 0;
                $claimsPaid = 0;
                $amountPaid = 0;
                $userName = $this->getUserNameById($employee);
                $empData = [
                    "employee" => $userName
                ];
                foreach($dateRangeFilterArr as $eachDate) {

                    $filterDate = explode('-',$eachDate)[0];
                    // echo $employee;
                    // echo "---";
                    $dbDate = date('Y-m-d',strtotime($filterDate));
                    $userDisributionData = $arReportObj->arDistributionByUser($dbDate,$employee);

                    if(count($userDisributionData) > 0) {

                        $rowObj = $userDisributionData[0];
    
                        $openClaims     +=$rowObj->open_claims;
                        $workedOn       +=$rowObj->worked_on;
                        $claimsClosed   +=$rowObj->claims_closed;
                        $claimsPaid     +=$rowObj->claims_paid;
                        $amountPaid     +=round($rowObj->amount_paid,2);
    
                        if(isset($resJSON['totalRow']["record"][$eachDate])) {
    
                            $resJSON['totalRow']["record"][$eachDate]['open_claims']    +=$rowObj->open_claims;
                            $resJSON['totalRow']["record"][$eachDate]['worked_on']      +=$rowObj->worked_on;
                            $resJSON['totalRow']["record"][$eachDate]['claims_closed']  +=$rowObj->claims_closed;
                            $resJSON['totalRow']["record"][$eachDate]['claims_paid']    +=$rowObj->claims_paid;
                            $resJSON['totalRow']["record"][$eachDate]['amount_paid']    +=round($rowObj->amount_paid,2);
                            //$this->printR($resJSON['totalRow']["record"][$eachDate],true);
                        }
                        else {
    
                            $resJSON['totalRow']["record"][$eachDate] = [
                                "open_claims"   => $rowObj->open_claims,
                                "worked_on"     => $rowObj->worked_on,
                                "claims_closed" => $rowObj->claims_closed,
                                "claims_paid"   => $rowObj->claims_paid,
                                "amount_paid"   => round($rowObj->amount_paid,2)
                            ];
    
                        }
                        $empData["record"][$eachDate] = $rowObj;
                    }

                }
                $empData["total"] = [
                    "open_claims"   => $openClaims,
                    "worked_on"     => $workedOn,
                    "claims_closed" => $claimsClosed,
                    "claims_paid"   => $claimsPaid,
                    "amount_paid"   => $amountPaid
                ];

                $grandOpenClaims    += $openClaims;
                $grandWorkedOn      += $workedOn;
                $grandClaimsClosed  += $claimsClosed;
                $grandClaimsPaid    += $claimsPaid;
                $grandAmountPaid    += $amountPaid;

                array_push($resJSON['report'], $empData);
            }
        }
        $resJSON['totalRow']["total"]["open_claims"]    = $grandOpenClaims;
        $resJSON['totalRow']["total"]["worked_on"]      = $grandWorkedOn;
        $resJSON['totalRow']["total"]["claims_closed"]  = $grandClaimsClosed;
        $resJSON['totalRow']["total"]["claims_paid"]    = $grandClaimsPaid;
        $resJSON['totalRow']["total"]["amount_paid"]    = $grandAmountPaid;
        $arReportObj = NULL;
        return $this->successResponse($resJSON, "success");

    }
    /**
     *ar distribution by payer
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @param  \Illuminate\Http\Response
     */
    public function arDistributionByPayer(Request $request) {
        set_time_limit(0);
        $arReportObj = new ARReports();

        $practices  = json_decode($request->practices,true);

        $facilities = json_decode($request->facilities,true);

        $payers     = json_decode($request->payers,true);

        $dateRangeFilter     = json_decode($request->date_range_filter,true);

        $startDate = date('Y-m-d',strtotime($dateRangeFilter['startDate']));

        $endDate = date('Y-m-d',strtotime($dateRangeFilter['endDate']));

        $filterFacility = [];
        if(count($facilities)) {
            foreach($facilities as $facilityId) {
                $facilityId = $this->removeDecimalFromString($facilityId);
                array_push($filterFacility,$facilityId);
            }
        }
        // $this->printR($filterFacility,true);
        $reportJSON = [];
        // $totalRow = ["row" => []];
        // $grandTotal = [];
        // $this->printR($payers,true);
        $totalRow = [];
        if(count($payers)) {
            foreach($payers as $payerId) {
                //echo $payerId;
                //$this->printR($practices,true);

                $topThreeStatus = $arReportObj->payerTopThree(implode(",",$practices),implode(",",$filterFacility),$payerId,$startDate,$endDate);

                $claims = $arReportObj->fetchPayerClaims(implode(",",$practices),implode(",",$filterFacility),$payerId,$startDate,$endDate);

                $totalRow['column'][$payerId] = ["cliams" => $claims[0],"statuses" => $topThreeStatus];
                // $topThreeByPayer[$payerId] = [];
                foreach($practices as $practiceId) {
                    foreach($facilities as $facilityId) {
                        $whereParent = [
                            ["user_parent_id", "=", $practiceId],
                            ["user_id", "=", $facilityId]
                        ];
                        $facility  = $this->fetchData($this->tbl, $whereParent, 1);

                        if(is_object($facility) && strpos($facilityId, ".") === false) {
                            $topThree = $arReportObj->fetchDistributionByPayerTopThree($practiceId,$facilityId,$payerId,$startDate,$endDate,0);
                            $claims = $arReportObj->fetchDistributionByPayerClaims($practiceId,$facilityId,$payerId,$startDate,$endDate,0);
                            // $topThreeArr = $this->stdToArray($topThree);
                            // $topThreeByPayer[$payerId] = array_merge($topThreeByPayer[$payerId], $topThreeArr);
                            $claimsCnt = $claims[0]->claims;
                            $reportJSON[$payerId][$practiceId][$facilityId] = [
                                "claims" => $claimsCnt,
                                "top_three_statuses" => $topThree
                            ];

                            // if(isset($totalRow['row'][$practiceId][$facilityId]["claims"])) {
                            //     $totalRow['row'][$practiceId][$facilityId]["claims"] += $claimsCnt;
                            // }
                            // else {
                            //     $totalRow['row'][$practiceId][$facilityId]["claims"] = $claimsCnt;
                            // }
                            // if(isset($grandTotal["claims"]))
                            //     $grandTotal["claims"] += $claimsCnt;
                            // else
                            //     $grandTotal["claims"] = $claimsCnt;

                            // if(count($topThree)) {

                            //     foreach($topThree as $topStatus) {
                            //         if(isset($totalRow['row'][$practiceId][$facilityId]["statuses"])) {
                            //             $res = $this->chkReportStatus($totalRow['row'][$practiceId][$facilityId]["statuses"],$topStatus->status);
                            //             if($res['same_status'] == true) {
                            //                 $idx = $res['index'];
                            //                 $tempData =  $totalRow['row'][$practiceId][$facilityId]["statuses"][$idx];
                            //                 $tempData['count'] +=$topStatus->count;
                            //                 $totalRow['row'][$practiceId][$facilityId]["statuses"][$idx] = $tempData;
                            //             }
                            //             else
                            //                 $totalRow['row'][$practiceId][$facilityId]["statuses"][] = ["status" => $topStatus->status,"count" => $topStatus->count];
                            //         }
                            //         else {
                            //             $totalRow['row'][$practiceId][$facilityId]["statuses"][] = ["status" => $topStatus->status,"count" => $topStatus->count];
                            //         }
                            //         if(isset($grandTotal["statuses"])) {
                            //             $grandTotal["claims"] += $claimsCnt;
                            //             $res = $this->chkReportStatus($grandTotal["statuses"],$topStatus->status);
                            //             if($res['same_status'] == true) {
                            //                 $idx = $res['index'];
                            //                 $tempData_ = $grandTotal["statuses"][$idx];
                            //                 $tempData_['count'] +=$topStatus->count;
                            //                 $grandTotal["statuses"][$idx] = $tempData_;
                            //             }
                            //             else
                            //                 $grandTotal["statuses"] = ["status" => $topStatus->status,"count" => $topStatus->count];
                            //         }
                            //         else
                            //             $grandTotal["statuses"] = ["status" => $topStatus->status,"count" => $topStatus->count];

                            //     }
                            // }
                            // else {
                            //     $totalRow['row'][$practiceId][$facilityId]["statuses"] = [];
                            // }
                            // $this->printR($claims,true);
                        }
                        else {
                            $facilityIdStr =  $facilityId;
                            $facilityId = $this->removeDecimalFromString($facilityId);
                            $shelter  = $this->chkShelter($practiceId, $facilityId);
                            if($shelter > 0) {
                                $topThree = $arReportObj->fetchDistributionByPayerTopThree($practiceId,$facilityId,$payerId,$startDate,$endDate,1);
                                $claims = $arReportObj->fetchDistributionByPayerClaims($practiceId,$facilityId,$payerId,$startDate,$endDate,1);
                                // $topThreeArr = $this->stdToArray($topThree);
                                // $topThreeByPayer[$payerId] = array_merge($topThreeByPayer[$payerId], $topThreeArr);
                                $claimsCnt = $claims[0]->claims;
                                $reportJSON[$payerId][$practiceId][$facilityIdStr] = [
                                    "claims" => $claimsCnt,
                                    "top_three_statuses" => $topThree
                                ];
                            }
                        }
                    }

                }
                // // Sort the top three statuses by count
                // usort($topThreeByPayer[$payerId], function($a, $b) {
                //     return $b['count'] - $a['count'];
                // });
                // // Take only the top three statuses
                // $topThreeByPayer[$payerId] = array_slice($topThreeByPayer[$payerId], 0, 3);
            }
        }
        $sortedData = collect($reportJSON)
        ->sortByDesc(function ($practice) {
            return collect($practice)->flatten(1)->max('claims');
        })
        ->all();

        $payerKeys = array_keys($sortedData);
        // $this->printR($payerKeys,true);
        $reOderArrRes =[];
        $reOrderTotalRow = [];
        if(count($payerKeys)) {
            foreach($payerKeys as $lkey=>$key) {
                // $this->printR($sortedData[$key],true);
                $reOderArrRes[$lkey."_".$key] = $sortedData[$key];
                $reOrderTotalRow["column"][$lkey."_".$key]  = $totalRow["column"][$key];
            }
        }
        // $this->printR($topThreeByPayer,true);
        $arReportObj = NULL;
        return $this->successResponse(['report' => $reOderArrRes,'totalRow' => $reOrderTotalRow], "success");
    }
    /**
     * check if status exist into data
     *
     * @param $data
     * @param $date
     */
    private function chkReportStatus($data, $status)
    {
        $res = ["index" => -1, "same_status" => false];
        foreach ($data as $key => $eachData) {

            if ($eachData['status'] == $status) {
                $res  = ["index" => $key, "same_status" => true];
            }
        }
        return $res;
    }
    /** 
     * generate the collection expected revenue report
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @param  \Illuminate\Http\Response
     */
    public function generateCollectionExpectedRevenueReport(Request $request) {
        
        $request->validate([
            'dos' => 'required',
        ]);
        $dos = json_decode($request->dos,true);
        $cols = [
            "Practice Name"         => "practice_name",
            "Total Claims"          => "claims",
            "Claims Processed"      => "claims_processed",
            "Collection"            => "processed_claims_collection",
            "Claims Pending"        => "claims_pending",
            "Estimated Collections" => "expected_collection"
        ];
        $token          = $request->bearerToken();
        $sessionUser    = $this->fetchUserIdAgainstToken($token);
        $sessionUserId  = is_object($sessionUser) ? $sessionUser->session_userid : 0;
        $practices      = $this->activePractices($sessionUserId);
        $practiceIdsArr = $this->stdToArray($practices);
        $practiceIds    = array_column($practiceIdsArr, "facility_id");
        $startDate = $dos["start_date"];//"2024-05-01";
        $endDate   = $dos["end_date"];//"2024-05-31";
        
        $startDate = date('Y-m-d', strtotime($startDate));

        $endDate = date('Y-m-d', strtotime($endDate));
       
        // $results = DB::table('account_receivable as ar')
        // ->select(
        //     "ubp.practice_name",
        //     "ar.practice_id",
        //     DB::raw('COUNT(cm_ar.id) AS claims'),
        //     DB::raw("(SELECT COUNT(id) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND STATUS IN (5, 2, 6) AND dos BETWEEN '$startDate' AND '$endDate') AS claims_processed"),
        //     DB::raw("(SELECT SUM(paid_amount) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND STATUS IN (5, 2, 6) AND dos BETWEEN '$startDate' AND '$endDate') AS processed_claims_collection"),
        //     DB::raw("(SELECT COUNT(id) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND STATUS NOT IN (5, 2, 6) AND dos BETWEEN '$startDate' AND '$endDate') AS claims_pending"),
        // )
        // ->join("user_baf_practiseinfo as ubp","ubp.user_id","=","ar.practice_id")
        // ->whereIn("ar.practice_id",$practiceIds)
        // ->where('ar.dos', '>=', $startDate)
        // ->where('ar.dos', '<=', $endDate)
        // ->where('ar.is_delete', 0)
        // //->whereNotNull("practice_name")
        // ->groupBy('ar.practice_id')
        // ->orderBy('practice_name')
        // ->get();
        $processedClaims = DB::table('account_receivable as ar1')
        ->select(
            'ar1.practice_id',
            DB::raw('COUNT(cm_ar1.id) as claims_processed'),
            DB::raw('SUM(cm_ar1.paid_amount) as processed_claims_collection')
        )
        ->whereIn('ar1.status', [5, 2, 6])
        ->whereBetween('ar1.dos', [$startDate, $endDate])
        ->groupBy('ar1.practice_id');

        $pendingClaims = DB::table('account_receivable as ar2')
        ->select(
            'ar2.practice_id',
            DB::raw('COUNT(cm_ar2.id) as claims_pending')
        )
        ->whereNotIn('ar2.status', [5, 2, 6])
        ->whereBetween('ar2.dos', [$startDate, $endDate])
        ->groupBy('ar2.practice_id');

        $results = DB::table('account_receivable as ar')
        ->select(
            "ubp.practice_name",
            "ar.practice_id",
            DB::raw('COUNT(cm_ar.id) AS claims'),
            DB::raw('IFNULL(cm_proc.claims_processed, 0) AS claims_processed'),
            DB::raw('IFNULL(cm_proc.processed_claims_collection, 0) AS processed_claims_collection'),
            DB::raw('IFNULL(cm_pend.claims_pending, 0) AS claims_pending')
        )
        ->join('user_baf_practiseinfo as ubp', 'ubp.user_id', '=', 'ar.practice_id')
        ->leftJoinSub($processedClaims, 'proc', function($join) {
            $join->on('proc.practice_id', '=', 'ar.practice_id');
        })
        ->leftJoinSub($pendingClaims, 'pend', function($join) {
            $join->on('pend.practice_id', '=', 'ar.practice_id');
        })
        ->where("ubp.practice_name","NOT LIKE","%test%")
        ->whereBetween('ar.dos', [$startDate, $endDate])
        ->whereIn("ar.practice_id",$practiceIds)
        ->where('ar.is_delete', 0)
        ->groupBy('ar.practice_id')
        ->orderBy('ubp.practice_name')
        ->get();


        $overallClaims = 0;
        $overallProcessedClaims = 0;
        $overallPendingClaims = 0;
        $overallProcessedClaimsCollection = 0;
        $overallExpectedCollection = 0;

        if($results->count() > 0) {
            foreach($results as $result) {
                
              
                $overallClaims             += $result->claims;
                
                $overallProcessedClaims   += $result->claims_processed;
                
                $overallPendingClaims     += $result->claims_pending;
                
                $result->processed_claims_collection = round($result->processed_claims_collection,2);
                
                $overallProcessedClaimsCollection +=$result->processed_claims_collection;
                
                $result->expected_collection = 0;
                if($result->claims_processed > 0) {
                    $perClaimAvg = $result->processed_claims_collection / $result->claims_processed;
                    $result->expected_collection = round($perClaimAvg * $result->claims_pending,2);
                    $overallExpectedCollection +=$result->expected_collection;
                    
                }

            }
        }
       
        if($results->count() > 0) {
            $resultArr  = $this->stdToArray($results);
            $resultArr  = collect($resultArr);
            $sortBy     = $request->has("sort_by") ? $cols[$request->sort_by]:"practice_name";
            $sortOrder  =  $request->has("sort_order") ? $request->sort_order :"asc";
            
            if ($sortOrder === 'desc') 
                $results = $resultArr->sortByDesc($sortBy)->values()->toArray();
            else 
                $results = $resultArr->sortBy($sortBy)->values()->toArray();
        }
        return $this->successResponse([
            'report'                                => $results,
            "overall_claims"                        => $overallClaims,
            "overall_processed_claims"              => $overallProcessedClaims,
            "overall_pending_claims"                => $overallPendingClaims,
            "overall_processed_claims_collection"   => $overallProcessedClaimsCollection,
            "overall_expected_collection"           => $overallExpectedCollection
        ], "success");
    }
    /** 
     * fetch the  collection expected revenue report by payer 
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @param  \Illuminate\Http\Response
     */
    public function fetchCollectionExpectedRevenueReportByPayer(Request $request) {
        $request->validate([
            'dos'           => 'required',
            'practice_id'   => 'required',
        ]);
        $practiceId = $request->practice_id;
        $dos = json_decode($request->dos,true);
        $cols = [
            "Payer Name"         => "payer_name",
            "Total Claims"          => "claims",
            "Claims Processed"      => "claims_processed",
            "Collection"            => "processed_claims_collection",
            "Claims Pending"        => "claims_pending",
            "Estimated Collections" => "expected_collection"
        ];
        // $this->printR($dos, true);
        $startDate = $dos["start_date"];
        $endDate   = $dos["end_date"];
        $startDate = date('Y-m-d', strtotime($startDate));

        $endDate = date('Y-m-d', strtotime($endDate));

        $results = DB::table('account_receivable as ar')
        ->select(
            "payers.payer_name",
            "ar.payer_id",
            DB::raw('COUNT(cm_ar.id) AS claims'),
            DB::raw("(SELECT COUNT(id) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND payer_id=cm_ar.payer_id AND STATUS IN (5, 2, 6) AND dos BETWEEN '$startDate' AND '$endDate') AS claims_processed"),
            DB::raw("(SELECT SUM(paid_amount) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND payer_id=cm_ar.payer_id AND STATUS IN (5, 2, 6) AND dos BETWEEN '$startDate' AND '$endDate') AS processed_claims_collection"),
            DB::raw("(SELECT COUNT(id) FROM cm_account_receivable WHERE practice_id = cm_ar.practice_id AND payer_id=cm_ar.payer_id AND STATUS NOT IN (5, 2, 6) AND dos BETWEEN '$startDate' AND '$endDate') AS claims_pending"),
        )
        ->join("payers","payers.id","=","ar.payer_id")
        // ->where('ar.dos', '>=', $startDate)
        // ->where('ar.dos', '<=', $endDate)
        ->whereBetween('ar.dos', [$startDate, $endDate])
        ->where('ar.is_delete', 0)
        ->where('ar.practice_id', $practiceId)
        ->groupBy('ar.payer_id')
        ->orderBy('payers.payer_name')
        ->get();
        $overallClaims = 0;
        $overallProcessedClaims = 0;
        $overallPendingClaims = 0;
        $overallProcessedClaimsCollection = 0;
        $overallExpectedCollection = 0;
        if($results->count() > 0) {
            foreach($results as $result) {
                
                $overallClaims             += $result->claims;
                
                $overallProcessedClaims   += $result->claims_processed;
                
                $overallPendingClaims     += $result->claims_pending;

                $result->processed_claims_collection = round($result->processed_claims_collection,2);
                $overallProcessedClaimsCollection +=$result->processed_claims_collection;
                $result->expected_collection = 0;
                if($result->claims_processed > 0) {
                    $perClaimAvg = $result->processed_claims_collection / $result->claims_processed;
                    $result->expected_collection = round($perClaimAvg * $result->claims_pending,2);
                    $overallExpectedCollection +=$result->expected_collection;
                }

            }
        }

        if($results->count() > 0) {
            $resultArr  = $this->stdToArray($results);
            $resultArr  = collect($resultArr);
            $sortBy     = $request->has("sort_by") ? $cols[$request->sort_by]:"payer_name";
            $sortOrder  =  $request->has("sort_order") ? $request->sort_order :"asc";
            if ($sortOrder === 'desc') 
                $results = $resultArr->sortByDesc($sortBy)->values()->toArray();
            else 
                $results = $resultArr->sortBy($sortBy)->values()->toArray();
        }

        return $this->successResponse([
            'payer_report'                          => $results,
            "overall_claims"                        => $overallClaims,
            "overall_processed_claims"              => $overallProcessedClaims,
            "overall_pending_claims"                => $overallPendingClaims,
            "overall_processed_claims_collection"   => $overallProcessedClaimsCollection,
            "overall_expected_collection"           => $overallExpectedCollection
        ], "success");
    }
}
