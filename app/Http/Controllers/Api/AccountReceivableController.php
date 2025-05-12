<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountReceivable;
use App\Models\Payer;
use App\Models\Credentialing;
use Carbon\Carbon;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use Illuminate\Support\Facades\DB;
use App\Models\ARUserColumn;
use App\Models\ARLogs;
use App\Models\ArStatus;
use App\Models\Shelters;
use Illuminate\Support\Str;
use DateTime;

class AccountReceivableController extends Controller
{
    use ApiResponseHandler, Utility;
    private $tbl = "user_ddpracticelocationinfo";
    private $tblU = "users";
    private $key = "";
    private $sessionUserId = null;
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

        $sessionUser = $this->getSessionUserId($request);

        $sessionUserId = $sessionUser;


        //try
        {
            $isArchived = 0;
            $totallCount = 0;
            $userCols = [];

            $page   = $request->has('page') ? $request->get('page') : 1;

            $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;

            $offset  = $page - 1;

            $newOffset  = $perPage * $offset;
            $search     = $request->search;

            $practiceFilter     = $request->practice_filter;
            $facilityFilter     = $request->facility_filter;
            $payerFilter        = $request->payers_filter;
            $statusFilter       = $request->status_filter;
            $assignedToFilter   = $request->assigned_to_filter;
            $dateRangeFilter    = $request->date_range_filter;
            $columnFilter       = $request->column_filter;
            $buntyFilter        = $request->bunty_filter;
            $remarksFilter      = $request->remarks_filter;

            $tbl    = $this->tbl;

            $tblU   = $this->tblU;

            $key    = $this->key;

            if ($request->has('sort_by') && $request->sort_by == "practice_name") {
                $sortByCol = "ubp.practice_name";
            } elseif ($request->has('sort_by') && $request->sort_by == "facility_name") {
                $sortByCol = "pli.practice_name";
            } elseif ($request->has('sort_by') && $request->sort_by == "payer_name") {
                $sortByCol = "payers.payer_name";
            } elseif ($request->has('sort_by') && $request->sort_by == "entered_date") {
                $sortByCol = "account_receivable.created_at";
            } elseif ($request->has('sort_by') && $request->sort_by == "status_name") {
                $sortByCol = "revenue_cycle_status.status";
            } elseif ($request->has('sort_by') && $request->sort_by == "remarks") {
                $sortByCol = "revenue_cycle_remarks.remarks";
            } elseif ($request->has('sort_by') && $request->sort_by == "assigned_to_name") {
                $sortByCol = "cu.first_name";
            } elseif ($request->has('sort_by') && $request->sort_by == "aging_days") {
                $sortByCol = "aging_days";
            } else {
                $sortByCol      = $request->has('sort_by') && $request->sort_by != "" ? "account_receivable." . $request->sort_by : "account_receivable.id";
            }
            //$sortByCol = explode('.', $sortByCol)[1];
            $sortByOrder      = $request->has('sort_order') && $request->sort_order != "false" ? $request->sort_order : "desc";

            $hasStatusFilter = false;

            $ARUserColumnObj = new ARUserColumn();

            $userId = $request->has('session_user_id') ? $request->get('session_user_id') : $request->user->id;

            $arModel = new AccountReceivable();
            $practices = $arModel->activePractices($sessionUserId);
            // now get ids from the practicies...
            $practiceIds = [];
            foreach ($practices as $practice) {
                $practiceIds[] = $practice->facility_id;
            }

            // now get facilities by practice ids and user session id in a loop on practice ids...
            $facilities = [];
            foreach ($practiceIds as $practiceId) {
                $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
            }

            $sessionFacilityIds = [];
            foreach ($facilities as $facility) {
                foreach ($facility as $f) {
                    $sessionFacilityIds[] = $f->facility_id;
                }
            }
            $sessionFacilityIds = array_unique($sessionFacilityIds);

            $cols = [
                "account_receivable.claim_no",
                //DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as, '$key') as practice_name"),
                "ubp.doing_business_as as practice_name",
                "cu.first_name",
                DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name"), "payers.payer_name",
                DB::raw("CONCAT(cm_cu.first_name, ' ',cm_cu.last_name) as assigned_to_name"),
                DB::raw("CONCAT(cm_cu_.first_name, ' ',cm_cu_.last_name) as created_by_name"),
                "shelters.name as shelter_name",
                "revenue_cycle_status.status as status_name", "account_receivable.dob", "account_receivable.dos", "account_receivable.billed_amount",
                "account_receivable.paid_amount", DB::raw("DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y') AS entered_date"), "revenue_cycle_remarks.remarks", "account_receivable.id",
                "account_receivable.patient_name", "account_receivable.created_at",
                "payers.timely_filling_limit",
                DB::raw("DATEDIFF(CURDATE(), cm_account_receivable.dos) as aging_days"),
                DB::raw('(CASE WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) > cm_payers.timely_filling_limit
                THEN "Expired"
                WHEN (DATEDIFF(CURDATE(), cm_account_receivable.dos) > 60
                     && DATEDIFF(CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit)
                THEN "Expiring Soon"
                WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60
                	THEN "Under 60\'s"
                ELSE "None"
                END) AS aging_status'),
                "account_receivable.practice_id",
                "account_receivable.facility_id",
                "account_receivable.payer_id",
                "account_receivable.assigned_to",
                "account_receivable.status",
                "account_receivable.eob_number",
                "account_receivable.eob_date",
                "account_receivable.amount",
                "account_receivable.closed_remarks",
                DB::raw("DATE_FORMAT(cm_account_receivable.last_followup_date,'%m/%d/%Y') AS last_followup_date"),
                DB::raw("DATE_FORMAT(cm_account_receivable.next_follow_up,'%m/%d/%Y') AS next_follow_up"),
                "account_receivable.remarks as remarks_id",
                "account_receivable.status as status_id",
                "account_receivable.assigned_to as assigned_to_id",
                "account_receivable.is_delete",
                DB::raw("(SELECT COUNT(arlog.id) FROM `cm_ar_logs` arlog
                INNER JOIN `cm_attachments` atch
                on atch.entity_id = arlog.id AND atch.entities ='ar_log'
                WHERE arlog.ar_id = cm_account_receivable.id) as attachment_flag"),
                "account_receivable.shelter_id",
                "account_receivable.no_fault"
            ];
            $frontEndCols = [
                "claim_no"      => "account_receivable.claim_no",
                //"practice_name" => DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as, '$key') as practice_name"),
                "ubp.doing_business_as as practice_name",
                "facility_name" => DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name"),
                "payer_name"    => "payers.payer_name",
                "patient_name"  => "account_receivable.patient_name",
                "dob"           => "account_receivable.dob",
                "dos"           => "account_receivable.dos",
                "billed_amount" => "account_receivable.billed_amount",
                "paid_amount"   => "account_receivable.paid_amount",
                "entered_date"  => DB::raw("DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y') AS entered_date"),
                "remarks"       => "revenue_cycle_remarks.remarks",
                "assigned_to_name" => DB::raw("CONCAT(cm_cu.first_name, ' ',cm_cu.last_name) as assigned_to_name"),
                "status_name" => "revenue_cycle_status.status as status_name",
                "last_followup_date" =>   DB::raw("DATE_FORMAT(cm_account_receivable.last_followup_date,'%m/%d/%Y') AS last_followup_date"),
                "next_followup_date" => DB::raw("DATE_FORMAT(cm_account_receivable.next_follow_up,'%m/%d/%Y') AS next_follow_up"),
                "aging_days" =>  DB::raw("DATEDIFF(CURDATE(), cm_account_receivable.dos) as aging_days"),
                "account_receivable.no_fault"

            ];

            $sqlCols = [
                "account_receivable.created_at", "account_receivable.id",

                "account_receivable.practice_id",
                "account_receivable.facility_id",
                "account_receivable.payer_id",
                "account_receivable.eob_number",
                "account_receivable.eob_date",
                "account_receivable.amount",
                "account_receivable.closed_remarks",
                DB::raw('(CASE WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) > cm_payers.timely_filling_limit
                THEN "Expired"
                WHEN (DATEDIFF(CURDATE(), cm_account_receivable.dos) > 60
                     && DATEDIFF(CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit)
                THEN "Expiring Soon"
                WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60
                	THEN "Under 60\'s"
                ELSE "None"
                END) AS aging_status'),
                "account_receivable.remarks as remarks_id",
                "account_receivable.status as status_id",
                "account_receivable.assigned_to as assigned_to_id",
                "cu.first_name",
                "account_receivable.is_delete",
                DB::raw("(SELECT COUNT(arlog.id) FROM `cm_ar_logs` arlog
                INNER JOIN `cm_attachments` atch
                on atch.entity_id = arlog.id AND atch.entities ='ar_log'
                WHERE arlog.ar_id = cm_account_receivable.id) as attachment_flag"),
                DB::raw("DATE_FORMAT(cm_account_receivable.next_follow_up,'%m/%d/%Y') AS next_follow_up"),
                "shelters.name as shelter_name",
                "account_receivable.shelter_id",
                "account_receivable.no_fault"

            ];
            $sessionUserCols = [];
            if (strlen($columnFilter) > 2) {
                $columnFilterArr = json_decode($columnFilter, true);
                foreach ($columnFilterArr as $eachColumn) {
                    array_push($sessionUserCols, ['user_id' => $userId, "column_name" => $eachColumn, "created_at" => $this->timeStamp()]);
                }
                $ARUserColumnObj->addOrUpdateUserColumn($userId, $sessionUserCols);
            }

            $sessionUserCols = ARUserColumn::where('user_id', '=', $userId)

                ->select('column_name')

                ->get();

            if (count($sessionUserCols)) { //session user cols
                foreach ($sessionUserCols as $column) {
                    if (isset($frontEndCols[$column->column_name]) && $column->column_name != "next_followup_date") {
                        array_push($sqlCols, $frontEndCols[$column->column_name]);
                        array_push($userCols, $column->column_name);
                    }
                }
            } else
                $sqlCols = $cols;

            //main query object
            $fatchAccountReceivable = AccountReceivable::select(
                $sqlCols
            )
                // ->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
                //     $join->on('elm.location_user_id', '=', 'account_receivable.facility_id')
                //         ->where('elm.emp_id', '=', $sessionUserId);
                // })
                // ->leftJoin($tbl . ' as pli', 'pli.user_id', '=', 'elm.location_user_id')
                ->leftJoin($tbl . ' as pli', 'pli.user_id', '=', 'account_receivable.facility_id')
                //->leftJoin($tbl, $tbl . '.user_id', '=', 'account_receivable.practice_id')
                // ->leftJoin("user_baf_practiseinfo as ubp", 'ubp.user_id', '=', 'pli.user_parent_id')
                ->leftJoin("user_baf_practiseinfo as ubp", 'ubp.user_id', '=', 'account_receivable.practice_id')
                //->leftJoin($tbl . ' as pli', 'pli.user_id', '=', 'account_receivable.facility_id')

                ->leftJoin('shelter_facility_map', function ($join) {
                    $join->on([
                        ['shelter_facility_map.shelter_id', '=', 'account_receivable.shelter_id'],
                        ["shelter_facility_map.facility_id", "=", 'account_receivable.facility_id']
                    ]);
                })

                ->leftJoin('shelters', 'shelters.id', '=', 'shelter_facility_map.shelter_id')

                ->leftJoin('payers', 'payers.id', '=', 'account_receivable.payer_id')

                ->leftJoin($tblU . ' as cu', 'cu.id', '=', 'account_receivable.assigned_to')

                ->leftJoin($tblU . ' as cu_', 'cu_.id', '=', 'account_receivable.created_by')

                ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')

                ->leftJoin('revenue_cycle_remarks', 'revenue_cycle_remarks.id', '=', 'account_receivable.remarks')
                ->whereIn('practice_id',$practiceIds);

            // ->join($tblU . " as u_practice", function ($join) {
            //     $join->on('u_practice.id', '=', 'account_receivable.practice_id')
            //         ->where('u_practice.deleted', '=', 0);
            // })
            // ->join($tblU . " as u_facility", function ($join) use ($isArchived) {
            //     $join->on('u_facility.id', '=', 'account_receivable.facility_id')
            //         ->where('u_facility.deleted', '=', $isArchived);
            // });



            // $fatchAccountReceivable1 = AccountReceivable::select("account_receivable.id")

            //     ->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            //         $join->on('elm.location_user_id', '=', 'account_receivable.facility_id')
            //             ->where('elm.emp_id', '=', $sessionUserId);
            //     })
            //     ->leftJoin($tbl . ' as pli', 'pli.user_id', '=', 'elm.location_user_id')
            //     //->leftJoin($tbl, $tbl . '.user_id', '=', 'account_receivable.practice_id')
            //     ->leftJoin("user_baf_practiseinfo as ubp", 'ubp.user_id', '=', 'pli.user_parent_id')

            //     ->leftJoin('shelter_facility_map', function ($join) {
            //         $join->on([
            //             ['shelter_facility_map.shelter_id', '=', 'account_receivable.shelter_id'],
            //             ["shelter_facility_map.facility_id", "=", 'account_receivable.facility_id']
            //         ]);
            //     })

            //     ->leftJoin('shelters', 'shelters.id', '=', 'shelter_facility_map.shelter_id')

            //     ->leftJoin('payers', 'payers.id', '=', 'account_receivable.payer_id')

            //     ->leftJoin($tblU . ' as cu', 'cu.id', '=', 'account_receivable.assigned_to')

            //     ->leftJoin($tblU . ' as cu_', 'cu_.id', '=', 'account_receivable.created_by')

            //     ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')

            //     ->leftJoin('revenue_cycle_remarks', 'revenue_cycle_remarks.id', '=', 'account_receivable.remarks')

            //     ->join($tblU . " as u_practice", function ($join) {
            //         $join->on('u_practice.id', '=', 'account_receivable.practice_id')
            //             ->where('u_practice.deleted', '=', 0);
            //     })
            //     ->join($tblU . " as u_facility", function ($join) use ($isArchived) {
            //         $join->on('u_facility.id', '=', 'account_receivable.facility_id')
            //             ->where('u_facility.deleted', '=', $isArchived);
            //     });


            $sortedArray = [];
            // $this->printR($sqlCols,true);
            if (
                $request->search != '' ||
                strlen($facilityFilter) > 2 ||
                strlen($practiceFilter) > 2 ||
                strlen($payerFilter) > 2 ||
                strlen($statusFilter) > 2 ||
                strlen($assignedToFilter) > 2 ||
                strlen($dateRangeFilter) > 2 ||
                strlen($buntyFilter) > 2 ||
                strlen($remarksFilter) > 2
            ) {

                $andClause = false;




                if (strlen($practiceFilter) > 2) {
                    // exit('in practice filter');
                    $andClause = true;
                    $practiceFilter = json_decode($practiceFilter, true);

                    $practiceFilter = array_column($practiceFilter, "value");

                    $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.practice_id', $practiceFilter);
                    // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereIn('account_receivable.practice_id', $practiceFilter);
                }
                if (strlen($facilityFilter) > 2) {
                    $andClause = true;
                    $facilityFilter = json_decode($facilityFilter, true);

                    $facilityFilter = array_column($facilityFilter, "value");

                    $facilityFilter = $this->removeDecimalValues($facilityFilter);
                    $facilityIds = $facilityFilter['facility'];
                    $shelterIds = $facilityFilter['shelter'];
                    if (count($facilityIds) > 0 && count($shelterIds) == 0) {
                        $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.facility_id', $facilityIds);
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereIn('account_receivable.facility_id', $facilityIds);
                    }
                    if (count($shelterIds) > 0 && count($facilityIds) == 0) {
                        $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.shelter_id', $shelterIds);
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereIn('account_receivable.shelter_id', $shelterIds);
                    }
                    if (count($shelterIds) > 0 && count($facilityIds) > 0) {
                        $shelterIdsStr = implode(',', $shelterIds);
                        $facilityIdsStr = implode(',', $facilityIds);
                        $fatchAccountReceivable = $fatchAccountReceivable->whereRaw("(cm_account_receivable.shelter_id IN($shelterIdsStr) OR cm_account_receivable.facility_id IN($facilityIdsStr))");
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw("(cm_account_receivable.shelter_id IN($shelterIdsStr) OR cm_account_receivable.facility_id IN($facilityIdsStr))");
                        //$fatchAccountReceivable1 = $fatchAccountReceivable1->whereIn('account_receivable.shelter_id', $shelterIds);
                    }
                }
                if (strlen($payerFilter) > 2) {
                    $andClause = true;
                    $payerFilter = json_decode($payerFilter, true);

                    // $payerFilter = array_column($payerFilter, "value");

                    // $this->printR($payerFilter,true);

                    $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.payer_id', $payerFilter);
                    // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereIn('account_receivable.payer_id', $payerFilter);
                }
                if (strlen($statusFilter) > 2) {

                    $hasStatusFilter = true;
                    $andClause = true;
                    $statusFilter = json_decode($statusFilter, true);

                    $statusFilter = array_column($statusFilter, "id");

                    $statusFilterStr = implode(",", $statusFilter);

                    $rawFilterSql = 'cm_account_receivable.status IN (SELECT status_id FROM cm_revenue_cycle_remarks_map GROUP BY status_id) AND cm_account_receivable.status IN(' . $statusFilterStr . ')';

                    $fatchAccountReceivable = $fatchAccountReceivable->whereRaw($rawFilterSql);

                    // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw($rawFilterSql);

                    // $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.status', $statusFilter);
                }



                if (strlen($dateRangeFilter) > 2) {

                    $andClause = true;
                    $rangeFilter = json_decode($dateRangeFilter, true);
                    // echo $rangeFilter["startDate"];
                    // echo $rangeFilter["endDate"];
                    $startDate = $this->isoDate($rangeFilter["startDate"]);
                    $endDate = $this->isoDate($rangeFilter["endDate"]);
                    // $this->printR($rangeFilter,true);
                    // $startDate = $rangeFilter['startDate'];

                    // $endDate = $rangeFilter['endDate'];

                    if ($rangeFilter['column'] == "dos") {
                        // $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                        // $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                        // $startDate = $this->dateFormat($rangeFilter["startDate"]);
                        // $endDate = $this->dateFormat($rangeFilter["endDate"]);

                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.dos', $startDate);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereDate('account_receivable.dos', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.dos', [$startDate, $endDate]);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereBetween('account_receivable.dos', [$startDate, $endDate]);
                        }
                    }

                    if ($rangeFilter['column'] == "dob") {
                        // $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                        // $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.dob', $startDate);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereDate('account_receivable.dob', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.dob', [$startDate, $endDate]);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereBetween('account_receivable.dob', [$startDate, $endDate]);
                        }
                    }
                    if ($rangeFilter['column'] == "last_followup_date") {
                        // $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                        // $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                        // $startDate = $this->dateFormat($rangeFilter["startDate"]);
                        // $endDate = $this->dateFormat($rangeFilter["endDate"]);
                        // echo $startDate . ' ' . $endDate;
                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.last_followup_date', $startDate);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereDate('account_receivable.last_followup_date', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.last_followup_date', [$startDate, $endDate]);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereBetween('account_receivable.last_followup_date', [$startDate, $endDate]);
                        }
                        //         echo $fatchAccountReceivable->count();
                        // exit;
                    }
                    if ($rangeFilter['column'] == "entered_date") {
                        // $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                        // $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.created_at', $startDate);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereDate('account_receivable.created_at', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.created_at', [$startDate, $endDate]);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereBetween('account_receivable.created_at', [$startDate, $endDate]);
                        }
                    }
                    if ($rangeFilter['column'] == "next_followup_date") {
                        // $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                        // $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.next_follow_up', $startDate);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereDate('account_receivable.next_follow_up', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.next_follow_up', [$startDate, $endDate]);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereBetween('account_receivable.next_follow_up', [$startDate, $endDate]);
                        }
                    }
                    if ($rangeFilter['column'] == "paid_date") {
                        //exit("here");
                        $hasStatusFilter = true;
                        // $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                        // $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.paid_date', $startDate);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereDate('account_receivable.paid_date', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.paid_date', [$startDate, $endDate]);
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereBetween('account_receivable.paid_date', [$startDate, $endDate]);
                        }
                    }
                }
                if (strlen($assignedToFilter) > 2) {
                    $andClause = true;
                    $assignedToFilter = json_decode($assignedToFilter, true);

                    $assignedToFilter = array_column($assignedToFilter, "value");
                    //$this->printR($assignedToFilter,true);
                    if (in_array("Unassigned", $assignedToFilter)) {
                        $key = array_search("Unassigned", $assignedToFilter);
                        unset($assignedToFilter[$key]);
                        if (count($assignedToFilter) == 0) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereNull('account_receivable.assigned_to');
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereNull('account_receivable.assigned_to');
                        } else {
                            $idsStr = implode(',', $assignedToFilter);
                            $fatchAccountReceivable = $fatchAccountReceivable->whereRaw("(cm_account_receivable.assigned_to IS NULL OR cm_account_receivable.assigned_to IN($idsStr))");
                            // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw("(cm_account_receivable.assigned_to IS NULL OR cm_account_receivable.assigned_to IN($idsStr))");
                        }
                    } else {
                        $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.assigned_to', $assignedToFilter);
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereIn('account_receivable.assigned_to', $assignedToFilter);
                    }
                }
                if (strlen($buntyFilter) > 2) {
                    $buntyFilterArr = json_decode($buntyFilter, true);
                    $andClause = true;
                    $rawQuery = "(";
                    if (in_array('1', $buntyFilterArr)) {
                        $rawQuery .= "DATEDIFF(CURDATE(), cm_account_receivable.dos) > cm_payers.timely_filling_limit ";
                        if (count($buntyFilterArr) > 1)
                            $rawQuery .= " OR ";

                        // $fatchAccountReceivable = $fatchAccountReceivable->whereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) > cm_payers.timely_filling_limit');
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) > cm_payers.timely_filling_limit');
                    }
                    if (in_array('2', $buntyFilterArr)) {
                        $rawQuery .= "DATEDIFF(CURDATE(), cm_account_receivable.dos) >= 61
                        && DATEDIFF(CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit ";
                        if (in_array('3', $buntyFilterArr))
                            $rawQuery .= " OR ";
                        // elseif (count($buntyFilterArr) == 2)
                        //     $rawQuery.=" OR ";

                        // $fatchAccountReceivable = $fatchAccountReceivable->whereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) >= (cm_payers.timely_filling_limit-30)+1
                        // && DATEDIFF(CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit');
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) >= (cm_payers.timely_filling_limit-30)+1
                        // && DATEDIFF(CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit');
                    }
                    if (in_array('3', $buntyFilterArr)) {
                        $rawQuery .= "DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60";
                        // $fatchAccountReceivable = $fatchAccountReceivable->whereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60');
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60');
                    }

                    $rawQuery .= " )";
                    // echo $rawQuery;
                    // exit;

                    $fatchAccountReceivable = $fatchAccountReceivable->whereRaw($rawQuery);
                    // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw($rawQuery);
                }
                if (strlen($remarksFilter) > 2) {
                    $andClause = true;
                    $remarksFilter = json_decode($remarksFilter, true);

                    $remarksFilter = array_column($remarksFilter, "id");

                    $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.remarks', $remarksFilter);
                    // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereIn('account_receivable.remarks', $remarksFilter);
                }

                if ($search != '') {
                    if (is_numeric($search)) {
                        $fatchAccountReceivable = $fatchAccountReceivable->where('account_receivable.claim_no', "LIKE", "%" . $search . "%");
                    } else {

                        $searchDate = date('Y-m-d', strtotime($search));
                        $whereRaw = "(cm_account_receivable.patient_name LIKE '%$search%'
                        OR  cm_ubp.doing_business_as  LIKE '%$search%' OR
                        cm_payers.payer_name LIKE '%$search%' OR
                        cm_revenue_cycle_status.status LIKE '%$search%' OR CONCAT(cm_cu.first_name, ' ',cm_cu.last_name) LIKE '%$search%'

                        OR AES_DECRYPT(cm_pli.practice_name,'$key') LIKE '%$search%'  OR cm_account_receivable.billed_amount LIKE '%$search%'
                        OR cm_account_receivable.paid_amount LIKE '%$search%'
                        OR cm_shelters.name LIKE  '%$search%'
                        OR cm_revenue_cycle_remarks.remarks LIKE  '%$search%'
                        OR DATEDIFF(CURDATE(), cm_account_receivable.dos) LIKE '%$search%'
                        ";
                        if ($searchDate != "1970-01-01") {
                            $whereRaw .= "
                            OR cm_account_receivable.dob LIKE '$searchDate%' OR  cm_account_receivable.dos LIKE '$searchDate%'
                            OR cm_account_receivable.last_followup_date LIKE '$searchDate%'
                            OR cm_account_receivable.next_follow_up LIKE '$searchDate%'
                            OR cm_account_receivable.created_at LIKE '$searchDate%'";
                        }
                        $whereRaw .= " )";
                        $fatchAccountReceivable = $fatchAccountReceivable->whereRaw($whereRaw);
                        // $fatchAccountReceivable1 = $fatchAccountReceivable1->whereRaw($whereRaw);
                    }
                }
                if ($hasStatusFilter == false) { //when there is not status filter then show ,considered as completed = 0
                    $fatchAccountReceivable = $fatchAccountReceivable->where('revenue_cycle_status.considered_as_completed', '=', 0);
                    // $fatchAccountReceivable1 = $fatchAccountReceivable1->where('revenue_cycle_status.considered_as_completed', '=', 0);
                }




                // $this->printR($statusFilter,true);
                $hasDeletedStatus = 0;
                if (is_array($statusFilter))
                    $hasDeletedStatus = $this->chkStatusDeleted($statusFilter);

                // echo $hasDeletedStatus;exit;
                if ($hasDeletedStatus == 0) { //if deleted status not found
                    $fatchAccountReceivable = $fatchAccountReceivable->where('account_receivable.is_delete', '=', 0);
                    // ->whereIn('account_receivable.practice_id', $practiceIds)
                    // ->whereIn('account_receivable.facility_id', $sessionFacilityIds);
                    // $fatchAccountReceivable1 = $fatchAccountReceivable1->where('account_receivable.is_delete', '=', 0);
                }
                // if (gettype($statusFilter) === "string" && Str::contains($statusFilter, '[]')) {

                //     $fatchAccountReceivable = $fatchAccountReceivable->where('account_receivable.is_delete', '=', 0);
                //     $fatchAccountReceivable1 = $fatchAccountReceivable1->where('account_receivable.is_delete', '=', 0);
                // }
                // echo $fatchAccountReceivable->toSql();
                // exit;

                // echo json_encode($practiceIds);
                // echo json_encode($sessionFacilityIds);
                // // exit;

                // echo $startDate . ' ' . $endDate;

                foreach ($fatchAccountReceivable as $accountReceivable) {
                    $accountReceivable->practice = UserBafPractiseinfo::find($accountReceivable->practice_id);

                    $accountReceivable->shelter = ShelterFacilityMap::where([
                        ['shelter_id', '=', $accountReceivable->shelter_id],
                        ["facility_id", "=", $accountReceivable->facility_id]
                    ])->first();

                    $accountReceivable->payer = Payers::find($accountReceivable->payer_id);
                    $accountReceivable->assignedToUser = User::find($accountReceivable->assigned_to);
                    $accountReceivable->createdByUser = User::find($accountReceivable->created_by);
                    $accountReceivable->status = RevenueCycleStatus::find($accountReceivable->status);
                    $accountReceivable->remarks = RevenueCycleRemarks::find($accountReceivable->remarks);
                }

                $totallCount =  $fatchAccountReceivable->count();
                // $this->printR($fatchAccountReceivable->get(), true);
                // echo $totallCount;
                // exit;
                if (Str::contains($sortByCol, "paid_amount") || Str::contains($sortByCol, "billed_amount")) {
                    // echo $sortByOrder;
                    // exit("Eixit");
                    $sortByCol = "cm_" . $sortByCol;

                    $sortedArray = $fatchAccountReceivable

                        ->orderByRaw("CAST($sortByCol AS DECIMAL(10,2)) $sortByOrder")

                        ->limit($perPage)

                        ->offset($newOffset)

                        ->get();
                } else {


                    $sortedArray = $fatchAccountReceivable

                        ->orderBy($sortByCol, $sortByOrder)

                        ->limit($perPage)

                        ->offset($newOffset)

                        ->get();
                }
            } else {
                // dd($practiceIds);
                $fatchAccountReceivable =   $fatchAccountReceivable
                    // ->whereIn('practice_id',$practiceIds)
                    ->where('account_receivable.is_delete', '=', 0)

                    ->where('revenue_cycle_status.considered_as_completed', '=', 0);

                // $fatchAccountReceivable1 =   $fatchAccountReceivable1

                //     ->where('account_receivable.is_delete', '=', 0)

                //     ->where('revenue_cycle_status.considered_as_completed', '=', 0);

                // $totallCount = $fatchAccountReceivable1->count();
                $totallCount =  $fatchAccountReceivable->count();

                if (Str::contains($sortByCol, "paid_amount") || Str::contains($sortByCol, "billed_amount")) {

                    $sortByCol = "cm_" . $sortByCol;

                    $sortedArray = $fatchAccountReceivable

                        ->orderByRaw("CAST($sortByCol AS DECIMAL(10,2)) $sortByOrder")

                        ->limit($perPage)

                        ->offset($newOffset)

                        ->get();
                } else {

                    $sortedArray = $fatchAccountReceivable
                        ->orderBy($sortByCol, $sortByOrder)

                        ->limit($perPage)

                        ->offset($newOffset)

                        ->get();
                }
            }
            return $this->successResponse(["account_receivable" => $sortedArray, 'users_columns' => $userCols, 'total_count' => $totallCount, 'currency_symbol' => '$'], "Success", 200);
        }
        // catch (\Throwable $exception) {

        //     return $this->errorResponse([],$exception->getMessage(),500);
        // }
    }

    /**
     * fetch the AR footer data counts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function arFooterData(Request $request)
    {

        $sessionUser = $this->getSessionUserId($request);

        $sessionUserId = $sessionUser;


        //try
        {
            $isArchived = 0;

            $search     = $request->search;

            $practiceFilter     = $request->practice_filter;
            $facilityFilter     = $request->facility_filter;
            $payerFilter        = $request->payers_filter;
            $statusFilter       = $request->status_filter;
            $assignedToFilter   = $request->assigned_to_filter;
            $dateRangeFilter    = $request->date_range_filter;
            $buntyFilter        = $request->bunty_filter;
            $remarksFilter      = $request->remarks_filter;

            $tbl    = $this->tbl;

            $tblU   = $this->tblU;

            $key    = $this->key;



            $hasStatusFilter = false;

            $arModel = new AccountReceivable();
            $practices = $arModel->activePractices($sessionUserId);
            // now get ids from the practicies...
            $practiceIds = [];
            foreach ($practices as $practice) {
                $practiceIds[] = $practice->facility_id;
            }

            // now get facilities by practice ids and user session id in a loop on practice ids...
            $facilities = [];
            foreach ($practiceIds as $practiceId) {
                $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
            }

            $sessionFacilityIds = [];
            foreach ($facilities as $facility) {
                foreach ($facility as $f) {
                    $sessionFacilityIds[] = $f->facility_id;
                }
            }
            $sessionFacilityIds = array_unique($sessionFacilityIds);

            $cols = [
                DB::raw("SUM(cm_account_receivable.billed_amount) AS total_billed_amount"),
                DB::raw("SUM(cm_account_receivable.paid_amount) AS total_paid_amount"),
                DB::raw("SUM(cm_account_receivable.balance_amount) AS total_balance_amount")

            ];

            $result = [];
            //main query object
            $fatchAccountReceivable = AccountReceivable::select(
                $cols
            )
                ->leftJoin($tbl . ' as pli', 'pli.user_id', '=', 'account_receivable.facility_id')
                ->leftJoin("user_baf_practiseinfo as ubp", 'ubp.user_id', '=', 'account_receivable.practice_id')
                ->leftJoin('shelter_facility_map', function ($join) {
                    $join->on([
                        ['shelter_facility_map.shelter_id', '=', 'account_receivable.shelter_id'],
                        ["shelter_facility_map.facility_id", "=", 'account_receivable.facility_id']
                    ]);
                })

                ->leftJoin('shelters', 'shelters.id', '=', 'shelter_facility_map.shelter_id')

                ->leftJoin('payers', 'payers.id', '=', 'account_receivable.payer_id')

                ->leftJoin($tblU . ' as cu', 'cu.id', '=', 'account_receivable.assigned_to')

                ->leftJoin($tblU . ' as cu_', 'cu_.id', '=', 'account_receivable.created_by')

                ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')

                ->leftJoin('revenue_cycle_remarks', 'revenue_cycle_remarks.id', '=', 'account_receivable.remarks');





            if (
                $request->search != ''          ||
                strlen($facilityFilter) > 2     ||
                strlen($practiceFilter) > 2     ||
                strlen($payerFilter) > 2        ||
                strlen($statusFilter) > 2       ||
                strlen($assignedToFilter) > 2   ||
                strlen($dateRangeFilter) > 2    ||
                strlen($buntyFilter) > 2        ||
                strlen($remarksFilter) > 2
            ) {


                if (strlen($practiceFilter) > 2) {

                    $practiceFilter = json_decode($practiceFilter, true);

                    $practiceFilter = array_column($practiceFilter, "value");

                    $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.practice_id', $practiceFilter);
                }
                if (strlen($facilityFilter) > 2) {

                    $facilityFilter = json_decode($facilityFilter, true);

                    $facilityFilter = array_column($facilityFilter, "value");

                    $facilityFilter = $this->removeDecimalValues($facilityFilter);
                    $facilityIds = $facilityFilter['facility'];
                    $shelterIds = $facilityFilter['shelter'];
                    if (count($facilityIds) > 0 && count($shelterIds) == 0) {
                        $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.facility_id', $facilityIds);
                    }
                    if (count($shelterIds) > 0 && count($facilityIds) == 0) {
                        $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.shelter_id', $shelterIds);
                    }
                    if (count($shelterIds) > 0 && count($facilityIds) > 0) {
                        $shelterIdsStr = implode(',', $shelterIds);
                        $facilityIdsStr = implode(',', $facilityIds);
                        $fatchAccountReceivable = $fatchAccountReceivable->whereRaw("(cm_account_receivable.shelter_id IN($shelterIdsStr) OR cm_account_receivable.facility_id IN($facilityIdsStr))");
                    }
                }
                if (strlen($payerFilter) > 2) {
                    $payerFilter = json_decode($payerFilter, true);

                    $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.payer_id', $payerFilter);
                }
                if (strlen($statusFilter) > 2) {

                    $hasStatusFilter = true;

                    $statusFilter = json_decode($statusFilter, true);

                    $statusFilter = array_column($statusFilter, "id");

                    $statusFilterStr = implode(",", $statusFilter);

                    $rawFilterSql = 'cm_account_receivable.status IN (SELECT status_id FROM cm_revenue_cycle_remarks_map GROUP BY status_id) AND cm_account_receivable.status IN(' . $statusFilterStr . ')';

                    $fatchAccountReceivable = $fatchAccountReceivable->whereRaw($rawFilterSql);
                }



                if (strlen($dateRangeFilter) > 2) {

                    $rangeFilter = json_decode($dateRangeFilter, true);
                    $startDate = $this->isoDate($rangeFilter["startDate"]);
                    $endDate = $this->isoDate($rangeFilter["endDate"]);

                    if ($rangeFilter['column'] == "dos") {

                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.dos', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.dos', [$startDate, $endDate]);
                        }
                    }

                    if ($rangeFilter['column'] == "dob") {
                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.dob', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.dob', [$startDate, $endDate]);
                        }
                    }
                    if ($rangeFilter['column'] == "last_followup_date") {

                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.last_followup_date', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.last_followup_date', [$startDate, $endDate]);
                        }
                    }
                    if ($rangeFilter['column'] == "entered_date") {

                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.created_at', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.created_at', [$startDate, $endDate]);
                        }
                    }
                    if ($rangeFilter['column'] == "next_followup_date") {

                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.next_follow_up', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.next_follow_up', [$startDate, $endDate]);
                        }
                    }
                    if ($rangeFilter['column'] == "paid_date") {

                        $hasStatusFilter = true;
                        if ($startDate == $endDate) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereDate('account_receivable.paid_date', $startDate);
                        } else {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereBetween('account_receivable.paid_date', [$startDate, $endDate]);
                        }
                    }
                }
                if (strlen($assignedToFilter) > 2) {

                    $assignedToFilter = json_decode($assignedToFilter, true);

                    $assignedToFilter = array_column($assignedToFilter, "value");
                    //$this->printR($assignedToFilter,true);
                    if (in_array("Unassigned", $assignedToFilter)) {
                        $key = array_search("Unassigned", $assignedToFilter);
                        unset($assignedToFilter[$key]);
                        if (count($assignedToFilter) == 0) {
                            $fatchAccountReceivable = $fatchAccountReceivable->whereNull('account_receivable.assigned_to');
                        } else {
                            $idsStr = implode(',', $assignedToFilter);
                            $fatchAccountReceivable = $fatchAccountReceivable->whereRaw("(cm_account_receivable.assigned_to IS NULL OR cm_account_receivable.assigned_to IN($idsStr))");
                        }
                    } else {
                        $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.assigned_to', $assignedToFilter);
                    }
                }
                if (strlen($buntyFilter) > 2) {
                    $buntyFilterArr = json_decode($buntyFilter, true);
                    $rawQuery = "(";
                    if (in_array('1', $buntyFilterArr)) {
                        $rawQuery .= "DATEDIFF(CURDATE(), cm_account_receivable.dos) > cm_payers.timely_filling_limit ";
                        if (count($buntyFilterArr) > 1)
                            $rawQuery .= " OR ";
                    }
                    if (in_array('2', $buntyFilterArr)) {
                        $rawQuery .= "DATEDIFF(CURDATE(), cm_account_receivable.dos) >= 61
                        && DATEDIFF(CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit ";
                        if (in_array('3', $buntyFilterArr))
                            $rawQuery .= " OR ";
                    }
                    if (in_array('3', $buntyFilterArr)) {
                        $rawQuery .= "DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60";
                    }

                    $rawQuery .= " )";

                    $fatchAccountReceivable = $fatchAccountReceivable->whereRaw($rawQuery);
                }
                if (strlen($remarksFilter) > 2) {
                    $remarksFilter = json_decode($remarksFilter, true);

                    $remarksFilter = array_column($remarksFilter, "id");

                    $fatchAccountReceivable = $fatchAccountReceivable->whereIn('account_receivable.remarks', $remarksFilter);
                }

                if ($search != '') {
                    if (is_numeric($search)) {
                        $fatchAccountReceivable = $fatchAccountReceivable->where('account_receivable.claim_no', "LIKE", "%" . $search . "%");
                    } else {

                        $searchDate = date('Y-m-d', strtotime($search));
                        $whereRaw = "(cm_account_receivable.patient_name LIKE '%$search%'
                        OR  cm_ubp.doing_business_as  LIKE '%$search%' OR
                        cm_payers.payer_name LIKE '%$search%' OR
                        cm_revenue_cycle_status.status LIKE '%$search%' OR CONCAT(cm_cu.first_name, ' ',cm_cu.last_name) LIKE '%$search%'

                        OR AES_DECRYPT(cm_pli.practice_name,'$key') LIKE '%$search%'  OR cm_account_receivable.billed_amount LIKE '%$search%'
                        OR cm_account_receivable.paid_amount LIKE '%$search%'
                        OR cm_shelters.name LIKE  '%$search%'
                        OR cm_revenue_cycle_remarks.remarks LIKE  '%$search%'
                        OR DATEDIFF(CURDATE(), cm_account_receivable.dos) LIKE '%$search%'
                        ";
                        if ($searchDate != "1970-01-01") {
                            $whereRaw .= "
                            OR cm_account_receivable.dob LIKE '$searchDate%' OR  cm_account_receivable.dos LIKE '$searchDate%'
                            OR cm_account_receivable.last_followup_date LIKE '$searchDate%'
                            OR cm_account_receivable.next_follow_up LIKE '$searchDate%'
                            OR cm_account_receivable.created_at LIKE '$searchDate%'";
                        }
                        $whereRaw .= " )";
                        $fatchAccountReceivable = $fatchAccountReceivable->whereRaw($whereRaw);
                    }
                }
                if ($hasStatusFilter == false) { //when there is not status filter then show ,considered as completed = 0
                    $fatchAccountReceivable = $fatchAccountReceivable->where('revenue_cycle_status.considered_as_completed', '=', 0);
                }




                // $this->printR($statusFilter,true);
                $hasDeletedStatus = 0;
                if (is_array($statusFilter))
                    $hasDeletedStatus = $this->chkStatusDeleted($statusFilter);

                // echo $hasDeletedStatus;exit;
                if ($hasDeletedStatus == 0) { //if deleted status not found
                    $fatchAccountReceivable = $fatchAccountReceivable->where('account_receivable.is_delete', '=', 0);
                }

                $result =  $fatchAccountReceivable->get();
            } else {

                $fatchAccountReceivable =   $fatchAccountReceivable

                    ->where('account_receivable.is_delete', '=', 0)

                    ->where('revenue_cycle_status.considered_as_completed', '=', 0);

                $result =  $fatchAccountReceivable->get();
            }
            return $this->successResponse($result, "success", 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        set_time_limit(0);
        $validationErrors = [];

        $newRecId = 0;
        $practiceCount = 0;
        $facilityCount = 0;
        $payerIdCount = 0;
        $calimsCount = 0;
        $statusCount = 0;
        $remarksCount = 0;
        $dobCount = 0;
        $dosCount = 0;

        $tbl = $this->tbl;
        $tblU = $this->tblU;
        $key = $this->key;

        $accountReceivable =  new AccountReceivable();

        if ($request->has('is_bluck') && $request->is_bluck) {
            $arBluckData = json_decode($request->ar_bluck_data, true);
            $createdBy = $request->created_by;
            $request->merge(["session_userid" => $createdBy]);
            // $this->printR($arBluckData,true);
            // echo $arBluckData;
            // exit;
            if (count($arBluckData)) {
                foreach ($arBluckData as $arData) {
                    // $this->printR($arData,true);
                    if (
                        isset($arData['claim_no']) &&
                        isset($arData['practice_name']) &&
                        isset($arData['facility_name']) &&
                        isset($arData['payer_name']) &&
                        isset($arData['billed_amount']) &&
                        isset($arData['paid_amount']) &&
                        isset($arData['dob']) &&
                        isset($arData['dos'])
                    ) {


                        $claimsNo       = $arData['claim_no'];
                        $practiceName   = $arData['practice_name'];
                        $facilityName   = $arData['facility_name'];
                        $payerName      = $arData['payer_name'];
                        $billedAmount   = $arData['billed_amount'];
                        $paidAmount     = $arData['paid_amount'];
                        $dob            = $arData['dob'];
                        $dos            = $arData['dos'];

                        $payerValid     = $this->fetchData("payers", ['payer_name' => $payerName], 1);

                        // $practiceValid  = $this->fetchData($tbl, [DB::raw("AES_DECRYPT(doing_buisness_as,'$key')") => $practiceName], 1);

                        // $facilityValid  = $this->fetchData($tbl, [DB::raw("AES_DECRYPT(practice_name,'$key')") => $facilityName], 1);

                        $practiceValid = $accountReceivable->chkPracticeName($practiceName);

                        $facilityValid = $accountReceivable->chkFaciltyName($facilityName);

                        $facilityId_ = NULL;
                        $shelterId_ = NULL;
                        if (!is_object($facilityValid)) {

                            $facilityValid = Shelters::chkShelterAgainstName($facilityName);
                            if (is_object($facilityValid)) {
                                $facilityId_ = $facilityValid->facility_id;
                                $shelterId_ = $facilityValid->user_id;
                            }
                        } else {
                            $facilityId_ = $facilityValid->user_id;
                            $shelterId_ = NULL;
                        }
                        $arStatus       = $this->fetchData("revenue_cycle_status", ['status' => $arData['status_name']], 1);

                        $arRemarks      = $this->fetchData("revenue_cycle_remarks", ['remarks' => $arData["remarks"]], 1);

                        $claimNoValid   = AccountReceivable::where('claim_no', '=', $claimsNo)

                            ->count();


                        $dosValid       = $this->checkDateFormat($dos);

                        $dobValid       = $this->checkDateFormat($dob);

                        $paidAmountValid    = strpos($paidAmount, "$");

                        $billedAmountValid  = strpos($billedAmount, "$");

                        if ($paidAmountValid === false) {

                            if (strpos($arData["paid_amount"], '$') === false && (float)$arData["paid_amount"] > 0) {
                                $arData["paid_amount"] = $arData["paid_amount"];
                            }
                        }
                        if ($paidAmountValid == true) {
                            //exit("Here22");
                            if (strpos($arData["paid_amount"], '$') !== false && (float)$arData["paid_amount"] <= 0) {
                                $arData["paid_amount"] = "";
                            } else {
                                $arData["paid_amount"] = "";
                            }
                        }
                        if ($paidAmountValid === 0) {
                            //exit("Here55");
                            if (strpos($arData["paid_amount"], '$') !== false && (float)$arData["paid_amount"] <= 0) {
                                $arData["paid_amount"] = "";
                            } else {
                                $arData["paid_amount"] = "";
                            }
                        }

                        if ($billedAmountValid === false) {

                            if (strpos($arData["billed_amount"], '$') === false && (float)$arData["billed_amount"] > 0) {
                                $arData["billed_amount"] = $arData["billed_amount"];
                            }
                        }
                        if ($billedAmountValid == true) {
                            //exit("Here");
                            if (strpos($arData["billed_amount"], '$') !== false && (float)$arData["paid_amount"] <= 0) {
                                $arData["billed_amount"] = "";
                            } else {
                                $arData["billed_amount"] = "";
                            }
                        }
                        if ($billedAmountValid === 0) {
                            //exit("Here");
                            if (strpos($arData["billed_amount"], '$') !== false && (float)$arData["paid_amount"] <= 0) {
                                $arData["billed_amount"] = "";
                            } else {
                                $arData["billed_amount"] = "";
                            }
                        }
                        $dosWithinDateRange = true;
                        if ($dosValid == true) {

                            $given_date = date('Y-m-d', strtotime($arData["dos"]));
                            $current_date = new \DateTime();

                            // create a DateTime object from the given date
                            $given_date_obj = new \DateTime($given_date);

                            // compare the two dates using the compare() method
                            if ($given_date_obj->format('Y-m-d') < $current_date->format('Y-m-d')) {
                                //echo "Given date is less than current date";
                                $dosWithinDateRange = true;
                            } else {
                                //echo "Given date is greater than or equal to current date";
                                $dosWithinDateRange = false;
                            }
                        }
                        // $this->printR($payerValid,true);
                        if (
                            is_object($payerValid)      &&
                            is_object($practiceValid)   &&
                            is_object($facilityValid)   &&
                            $claimNoValid == 0          &&
                            $dosValid == true           &&
                            $dobValid == true           &&
                            is_object($arRemarks)       &&
                            is_object($arStatus)        &&
                            $dosWithinDateRange == true &&
                            is_object($arStatus)

                        ) {
                            $addArData = [
                                "claim_no" => $claimsNo,
                                "patient_name" => $arData["patient_name"],
                                "dob" => !is_null($arData["dob"]) && $arData["dob"] != "" ? $arData["dob"] : "",
                                "dos" =>  !is_null($arData["dos"]) && $arData["dos"] != "" ? $arData["dos"] : "",
                                "practice_id" => $practiceValid->user_id,
                                "facility_id" => $facilityId_,
                                "shelter_id" => $shelterId_,
                                "payer_id" => $payerValid->id,
                                "billed_amount" => strpos($arData["billed_amount"], "$") !== false  ? str_replace("$", "", $arData["billed_amount"]) : $arData["billed_amount"],
                                "paid_amount" => strpos($arData["paid_amount"], "$") !== false  ? str_replace("$", "", $arData["paid_amount"]) : $arData["paid_amount"],
                                "status" => is_object($arStatus) ? $arStatus->id : 0,
                                "remarks" => is_object($arRemarks) ? $arRemarks->id : 0,
                                "created_by" => $createdBy,
                                "created_at" =>  $this->timeStamp(),
                                "balance_amount" => $arData["billed_amount"]

                            ];
                            $newRecId = AccountReceivable::insertGetId($addArData);
                            if (is_object($arStatus) && $arStatus->status == "ON HOLD") {
                                $claim = AccountReceivable::where("id", "=", $newRecId)
                                    ->first()
                                    ->toArray();
                                $this->syncUpdates($claim, $request);
                            }
                            /**
                             * record the AR logs very first titme
                             */
                            $logData = "Claim entered";

                            $statusId = is_object($arStatus) ? $arStatus->id : 0;

                            ARLogs::insertGetId([
                                'user_id' => $createdBy, 'ar_id' => $newRecId, 'ar_status_id' => $statusId,
                                'details' => $logData, 'is_system' => 1, 'created_at' => $this->timeStamp()
                            ]);
                        } else {
                            if (!is_object($practiceValid)) {
                                $practiceCount += 1;
                            }
                            if (!is_object($facilityValid)) {
                                $facilityCount += 1;
                            }
                            if (!is_object($payerValid)) {
                                $payerIdCount += 1;
                            }
                            if ($claimNoValid > 0) {
                                $calimsCount += 1;
                            }

                            if (!is_object($arRemarks)) {
                                $remarksCount += 1;
                            }
                            if (!is_object($arStatus)) {
                                $statusCount += 1;
                            }



                            $arErrData['payer_name']      =  is_object($payerValid) ? 0 : "Payer name is not valid";
                            $arErrData['practice_name']   =  is_object($practiceValid) ? 0 : "Practice name is not valid";
                            $arErrData['facility_name']   =  is_object($facilityValid) ? 0 : "Facility name is not valid";
                            $arErrData['status_name']     =  is_object($arStatus) ? 0 : "Status name is not valid";
                            $arErrData['claim_no']        =  $claimNoValid == 0 ? 0 : "Claim number already exist";

                            $arData['practice_id']     = is_object($practiceValid) ? $practiceValid->user_id : 0;
                            $arData['facility_id']     = is_object($facilityValid) ? $facilityValid->user_id : 0;
                            $arData['payer_id']        = is_object($payerValid) ? $payerValid->id : 0;
                            $arData['status_id']       = is_object($arStatus) ? $arStatus->id : 0;
                            $arData['remarks_id']      = is_object($arRemarks) ? $arRemarks->id : 0;

                            if ($dosValid == true && $dosWithinDateRange == true) {
                                $arErrData['dos'] = 0;
                                // $dosCount++;
                            } elseif ($dosValid == true && $dosWithinDateRange == false) {
                                $arErrData['dos'] = "Dos must be less then current date";
                                // $dosCount++;
                            } elseif ($dosValid == false && $dosWithinDateRange == true) {
                                $arErrData['dos'] = "Dos has not valid date format";
                                // $dosCount++;
                            } elseif ($dosValid == false && $dosWithinDateRange == false) {
                                $arErrData['dos'] = "Dos has not valid date format";
                                // $dosCount++;
                            }

                            if (!$dobValid)
                                $dobCount++;
                            if (!$dosValid)
                                $dosCount++;

                            //$arErrData['dos']             =  $dosValid == true ? 0 :  "Dob has not valid date format";

                            $arErrData['dob']             =  $dobValid == true ? 0 :  "Dob has not valid date format";
                            $arErrData['remarks']         =  is_object($arRemarks) ? 0 : "Remarks is not valid";
                            // $arErrData['billed_amount']   =  $paidAmountValid !== false ? 0 : "Billed amount should be in $";
                            // $arErrData['paid_amount']     =  $billedAmountValid !== false ? 0 : "Paid amount should be in $";
                            $arData['invalid_fields']     =  $arErrData;

                            array_push($validationErrors, $arData);
                        }
                    } else {
                        $arErrData['claim_no']        =   "Claim number is required";
                        $arData['invalid_fields']     =  $arErrData;
                        array_push($validationErrors, $arData);
                    }
                }
                $summary = [
                    'invalid_practice' => $practiceCount, 'invalid_facility' => $facilityCount,
                    'invalid_payer' => $payerIdCount, 'invalid_claims' => $calimsCount,
                    'invalid_dob' => $dobCount, 'invalid_dos' => $dosCount, 'invalid_status' => $statusCount, 'invalid_remark' =>  $remarksCount
                ];
                return $this->successResponse(["id" => $newRecId, 'error_data' => $validationErrors, 'error_summary' => $summary], "success");
            }
        } else {

            $claimsNo = $request->claim_no;

            $hasDuplicateClaim = AccountReceivable::where('claim_no', '=', $claimsNo)

                ->count();



            if ($hasDuplicateClaim > 0) {

                $error = [
                    "claim_no_error" => true,
                    'message' => "Given claim no is already exist"
                ];
                array_push($validationErrors, $error);
            }




            $paidAmount = $request->paid_amount;

            $billedAmount = $request->billed_amount;

            if ($paidAmount > 0) {
                $paidAmount = $paidAmount;
            }
            if ($billedAmount > 0) {
                $billedAmount = $billedAmount;
            }


            if (count($validationErrors) > 0) {
                return $this->successResponse(["id" => $newRecId, 'error_data' => $validationErrors], "success");
            } else {



                $payerId = $request->payer_id;

                $practiceId = $request->practice_id;
                $facilityId = $request->facility_id;
                $shelterId = NULL;
                if (Str::contains($facilityId, ".")) {
                    $facilityIdArr = explode(".", $facilityId);
                    $facilityId = $facilityIdArr[0];
                    $shelterId = $facilityIdArr[1];
                }
                // $facilityId = $this->removeDecimalFromString($request->facility_id);
                // $shelterId  = NULL;
                // if ($this->chkShelter($practiceId, $facilityId) == 1) {
                //     $shelterId  = $facilityId;
                //     // $facilityId = NULL;
                // }

                $statusId = $request->status_id;

                $arData = [
                    "claim_no"             => $claimsNo,
                    "patient_name"         => $request->patient_name,
                    "dob"                  => !is_null($request->dob) && $request->dob != ""  ? $request->dob : "",
                    "dos"                  => !is_null($request->dos) && $request->dos != "" ? $request->dos : "",
                    "practice_id"          => $practiceId,
                    "facility_id"          => $facilityId,
                    "shelter_id"           => $shelterId,
                    "payer_id"             => $payerId,
                    "billed_amount"        => $billedAmount,
                    "paid_amount"          => $paidAmount,
                    "status"               => $statusId,
                    "remarks"              => $request->remarks,
                    "assigned_to"          => $request->has('assigned_to') ? $request->assigned_to : 0,
                    "created_by"           => $request->created_by,
                    "created_at"           => $this->timestamp(),
                    "balance_amount"        => $billedAmount
                ];

                $newRecId = AccountReceivable::insertGetId($arData);
                $arStatus = $this->getARStatusName($statusId);
                if (is_object($arStatus) && $arStatus->status == "ON HOLD") {
                    $claim = AccountReceivable::where("id", "=", $newRecId)
                        ->first()
                        ->toArray();
                    $this->syncUpdates($claim, $request);
                }
                /**
                 * record the AR logs very first titme
                 */
                $logData = "Claim entered";

                ARLogs::insertGetId([
                    'user_id' => $request->created_by, 'ar_id' => $newRecId, 'ar_status_id' => $statusId, 'details' => $logData,
                    'is_system' => 1, 'created_at' => $this->timeStamp()
                ]);
            }
            return $this->successResponse(["id" => $newRecId, 'error_data' => $validationErrors], "success");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $key = $this->key;
        $tbl = $this->tbl;
        $tblU = $this->tblU;

        $cols = [
            "account_receivable.claim_no", DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as,'$key') as practice_name"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name"), "payers.payer_name", DB::raw("CONCAT(cm_cu.first_name, ' ',cm_cu.last_name) as assigned_to_name"),
            DB::raw("CONCAT(cm_cu_.first_name, ' ',cm_cu_.last_name) as created_by_name"),
            "revenue_cycle_status.status as status_name", "account_receivable.dob", "account_receivable.dos", "account_receivable.billed_amount",
            "account_receivable.paid_amount", DB::raw("DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y') AS entered_date"), "revenue_cycle_remarks.remarks", "account_receivable.id",
            "account_receivable.patient_name", "account_receivable.created_at",
            "payers.timely_filling_limit",
            DB::raw("DATEDIFF(CURDATE(), cm_account_receivable.dos) as aging_days"),
            DB::raw('(CASE WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) > cm_payers.timely_filling_limit
            THEN "Expired"
            WHEN (DATEDIFF(CURDATE(), cm_account_receivable.dos) > 60
                 && DATEDIFF(CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit)
            THEN "Expiring Soon"
            ELSE "None"
            END) AS aging_status'),
            "account_receivable.practice_id",
            "account_receivable.facility_id",
            "account_receivable.payer_id",
            "account_receivable.assigned_to",
            "account_receivable.status",
            "account_receivable.eob_number",
            "account_receivable.eob_date",
            "account_receivable.amount",
            "account_receivable.closed_remarks",
            DB::raw("DATE_FORMAT(cm_account_receivable.last_followup_date,'%m/%d/%Y') AS last_followup_date"),
            "account_receivable.remarks as remarks_id",
            "account_receivable.status as status_id",
            "account_receivable.assigned_to as assigned_to_id",
            "account_receivable.is_delete"
        ];

        $arData = AccountReceivable::select($cols)

            ->leftJoin($tbl, $tbl . '.user_id', '=', 'account_receivable.practice_id')

            ->leftJoin($tbl . ' as pli', 'pli.user_id', '=', 'account_receivable.facility_id')

            ->leftJoin('payers', 'payers.id', '=', 'account_receivable.payer_id')

            ->leftJoin($tblU . ' as cu', 'cu.id', '=', 'account_receivable.assigned_to')

            ->leftJoin($tblU . ' as cu_', 'cu_.id', '=', 'account_receivable.created_by')

            ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')

            ->leftJoin('revenue_cycle_remarks', 'revenue_cycle_remarks.id', '=', 'account_receivable.remarks')

            // ->where('account_receivable.is_delete', '=', 0)

            // ->where('ar_status.considered_as_completed', '=', 0)

            ->where('account_receivable.id', '=', $id)

            ->first();


        return $this->successResponse($arData, "success");
    }
    /**
     * syn updates to billing against updating claim
     *
     * @param $arId
     * @param $statusId
     * @param $arRemarkId
     * @return boolean
     */
    private function syncStatusToBilling($arId,$statusId,$arRemarkId,$remark)
    {
        $claim = AccountReceivable::select("claim_no")
        
        ->where("id",$arId)
        
        ->first();

        if(is_object($claim)) {
            $claimNo = $claim->claim_no;
            $this->updateData("billing", ["claim_no" => $claimNo], ["status_id" => $statusId,"remark_id" => $arRemarkId,"remarks" => $remark]);
        }
    }
    /**
     * syn updates to billing against updating claim
     *
     * @param $updateData
     * @return boolean
     */
    private function syncUpdates($updateData, $clientReq)
    {
        $isSynced = false;
        if ($updateData["claim_no"]) {
            $claimNo    = $updateData["claim_no"];
            $statusId   = $updateData["status"];
            $claimsDataExist = $this->billingDataExists($claimNo);
            $arStatus = $this->getARStatusName($statusId);
            // $arRemarks = $this->fetchARRemarks($updateData["remarks"]);
            // $this->printR($arStatus,true);
            if ($claimsDataExist > 0) {
                $isSynced = true;
                if (isset($arStatus->status) 
                    && ($arStatus->status == "ON HOLD" || $arStatus->status == "BILLED" || $arStatus->status == "DENIED")
                ) { //when claims changed on hold then update it on to billing
                    $billingStatus = $this->getBillingStatusId($arStatus->status);
                    $synData = ["status_id" => isset($billingStatus->id) ? $billingStatus->id : NULL];
                    $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                    if (isset($updateData["remarks"])) {
                        // exit("In remarks");
                        $remarks = $updateData["remarks"];

                        $billingRemarks = $this->fetchBillingRemarks($remarks);

                        if (is_object($billingRemarks)) {
                            $synData = ["remarks" => $billingRemarks->remarks, "remark_id" => $billingRemarks->id];
                            // $this->printR($synData,true);
                            $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                        }
                    }
                }
                // $this->printR($arStatus,true);
                if (isset($updateData["facility_id"])) {
                    if (Str::contains($updateData["facility_id"], ".")) {
                        $shelterId = explode(".", $updateData["facility_id"])[1];
                        $facilityId = explode(".", $updateData["facility_id"])[0];
                        $synData = ["facility_id" => $facilityId, "shelter_id" => $shelterId];
                        $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                    } else {
                        $synData = ["facility_id" => $updateData["facility_id"]];
                        $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                    }
                }
                if (isset($updateData["practice_id"])) {
                    $synData = ["practice_id" => $updateData["practice_id"]];
                    $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                }
                if (isset($updateData["payer_id"])) {
                    $synData = ["payer_id" => $updateData["payer_id"]];
                    $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                }
                if (isset($updateData["billed_amount"])) {
                    $synData = ["bill_amount" => $updateData["billed_amount"]];
                    $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                }
                if (isset($updateData["patient_name"])) {
                    $synData = ["patient_name" => $updateData["patient_name"]];
                    $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                }
                if (isset($updateData["dos"])) {
                    // echo $updateData["dos"];
                    // exit("I am here");
                    $dos = $this->isoDate($updateData["dos"]); //date("Y-m-d", strtotime($updateData["dos"]));

                    $synData = ["dos" => $dos];
                    $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                }
                if (isset($updateData["dob"])) {
                    $dob = $this->isoDate($updateData["dob"]); //date("Y-m-d", strtotime($updateData["dob"]));
                    $synData = ["dob" => $dob];
                    $this->updateData("billing", ["claim_no" => $claimNo], $synData);
                }
            } else {
                if (
                    isset($arStatus->status) && 
                    ($arStatus->status == "ON HOLD" || $arStatus->status == "BILLED" || $arStatus->status == "DENIED")
                ) { //when claims changed on hold and not in billing then add in billing
                    $billingStatus = $this->getBillingStatusId($arStatus->status);
                    if (Str::contains($updateData["facility_id"], ".")) {
                        $shelterId = explode(".", $updateData["facility_id"])[1];
                        $facilityId = explode(".", $updateData["facility_id"])[0];
                    } else {
                        $facilityId = $updateData["facility_id"];
                        $shelterId = 0;
                    }
                    $remarks = $updateData["remarks"];
                    $billingRemarks = $this->fetchBillingRemarks($remarks);
                    $addNewClaims = [
                        "claim_no"      => $updateData["claim_no"],
                        "practice_id"   => $updateData["practice_id"],
                        "facility_id"   => $facilityId,
                        "payer_id"      => $updateData["payer_id"],
                        "shelter_id"    => $shelterId,
                        "dos"           => $this->isoDate($updateData["dos"]),
                        "dob"           => $this->isoDate($updateData["dob"]),
                        "patient_name"  => $updateData["patient_name"],
                        "bill_amount"   => $updateData["billed_amount"],
                        "status_id"     => isset($billingStatus->id) ? $billingStatus->id : NULL,
                        "created_by"    => $clientReq->input("session_userid"),
                        "created_at"    => $this->timeStamp()

                    ];
                    if (isset($updateData["remarks"])) {
                        $remarks = $updateData["remarks"];

                        $billingRemarks = $this->fetchBillingRemarks($remarks);
                        if (is_object($billingRemarks)) {
                            $addNewClaims["remarks"]    = $billingRemarks->remarks;
                            $addNewClaims["remark_id"] = $billingRemarks->id;
                        }
                    }
                    $this->addData("billing", $addNewClaims); //create the new claim into billing
                }
            }
        }
        return $isSynced;
    }
    /**
     * sync old ar on hold record to billing
     *
     *
     */
    public function syncOldOnHoldClaimsToBilling()
    {

        $onHoldClaims = AccountReceivable::where("status", "=", "1")

            ->get();
        // $this->printR($onHoldClaims,true);
        $billingStatus = $this->getBillingStatusId("ON HOLD");
        $copiedData = [];
        if (count($onHoldClaims) > 0) {
            foreach ($onHoldClaims as $claim) {
                $claimsDataExist = $this->billingDataExists($claim->claim_no);
                $claimArr = $this->stdToArray($claim);
                // $this->printR($claimArr,true);

                if ($claimsDataExist == 0) {
                    $addNewClaims = [
                        "claim_no"     => $claimArr["claim_no"],
                        "practice_id"  => $claimArr["practice_id"],
                        "facility_id"   => $claimArr["facility_id"],
                        "payer_id"      => $claimArr["payer_id"],
                        "shelter_id"    => $claimArr["shelter_id"],
                        "dos"           => $claimArr["dos"],
                        "dob"           => $claimArr["dob"],
                        "patient_name"  => $claimArr["patient_name"],
                        "bill_amount"   => $claimArr["billed_amount"],
                        "status_id"     => isset($billingStatus->id) ? $billingStatus->id : NULL,
                        "created_by"    => $claimArr["created_by"],
                        "created_at"    => $claimArr["created_at"]

                    ];
                    array_push($copiedData, $addNewClaims);
                    $this->addData("billing", $addNewClaims); //create the new claim into billing
                }
            }
        }
        echo "Task Done." . PHP_EOL;
        $this->printR($copiedData, true);
    }
    /**
     * update balance when user change
     *
     * @param $id
     * @param $updateData
     */
    public function updateBalance($id, $updateData)
    {
        $isBalanceUpdated = false;
        $aR = AccountReceivable::where("id", $id)->first(["billed_amount", "balance_amount"]);
        if ($aR->billed_amount != $updateData["billed_amount"]) {
            $newInitialBalance  =  $aR->balance_amount + ($updateData["billed_amount"] - $aR->billed_amount);
            //$aR->save();
            AccountReceivable::where("id", $id)->update([
                "balance_amount"     => $newInitialBalance,

            ]);
            $isBalanceUpdated = true;
        }

        return $isBalanceUpdated;
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
        $tbl = $this->tbl;
        $tblU = $this->tblU;
        $key = $this->key;
        //try
        {

            $logsData = "";
            $updateData = $request->all();

            $isBalanceUpdated = $this->updateBalance($id, $updateData); //update the balance when billed amount changed

            $isSynced = $this->syncUpdates($updateData, $request); //this code will sync updates with billing

            $sessionUserId = $request->session_userid;
            $isUpdate = 0;
            unset($updateData['session_userid']);
            // $this->printR($updateData,true);
            foreach ($updateData as $col => $data) {

                $eachColData = AccountReceivable::where("id", $id)->first([$col, 'shelter_id']);
                $eachColDataArr = $this->stdToArray($eachColData);
                if ($col == "facility_id") {
                    // $col = $this->removeDecimalFromString($col);
                    if (Str::contains($data, ".")) { //shelter exist
                        $facilityId = explode(".", $data)[0];
                        $oldVal = $eachColDataArr['shelter_id'];
                        $newVal = $this->removeDecimalFromString($data);

                        if ($oldVal != $newVal) {
                            $facilityOld = $this->fetchData($tbl, ['user_id' => $eachColDataArr[$col]], 1, [DB::raw("AES_DECRYPT(practice_name,'$key') as practice_name")]);
                            $shelterOld = $this->fetchData("shelters", ['id' => $eachColDataArr['shelter_id']], 1);
                            $shelterNew = $this->fetchData("shelters", ['id' => $newVal], 1);
                            if (is_object($facilityOld) && !is_object($shelterOld)) {
                                $logsData .= "Facility  change from " . $facilityOld->practice_name . " to " . $shelterNew->name . " <br/>";
                            }
                            if (!is_object($facilityOld) && is_object($shelterOld)) {
                                $logsData .= "Shelter  change from " . $shelterOld->name . " to " . $shelterNew->name . " <br/>";
                            }
                            $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $facilityId, "shelter_id" => $newVal, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                        }
                    } else {
                        $oldVal = $eachColDataArr[$col];
                        $newVal = $data;
                        if ($oldVal != $newVal) {
                            $facilityOld = $this->fetchData($tbl, ['user_id' => $eachColDataArr[$col]], 1, [DB::raw("AES_DECRYPT(practice_name,'$key') as practice_name")]);
                            $shelterOld = $this->fetchData("shelters", ['id' => $eachColDataArr['shelter_id']], 1);
                            $facilityNew = $this->fetchData($tbl, ['user_id' => $newVal], 1, [DB::raw("AES_DECRYPT(practice_name,'$key') as practice_name")]);
                            if (is_object($shelterOld) && !is_object($facilityOld)) {
                                $logsData .= "Shelter  change from " . $shelterOld->name . " to " . $facilityNew->practice_name . " <br/>";
                            }
                            if (!is_object($shelterOld) && is_object($facilityNew)) {
                                $logsData .= "Facility  change from " . $facilityOld->practice_name . " to " . $facilityNew->practice_name . " <br/>";
                            }
                            $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $newVal, "shelter_id" => NULL, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                        }
                    }
                } elseif ($col == "practice_id") {
                    $oldVal = $eachColDataArr[$col];
                    $newVal = $data;
                    if ($oldVal != $newVal) {
                        $practiceOld = $this->fetchData($tbl, ['user_id' => $oldVal], 1, [DB::raw("AES_DECRYPT(doing_buisness_as,'$key') as doing_buisness_as")]);
                        $practiceNew = $this->fetchData($tbl, ['user_id' => $newVal], 1, [DB::raw("AES_DECRYPT(doing_buisness_as,'$key') as doing_buisness_as")]);
                        $logsData .= "Practice change from " . $practiceOld->doing_buisness_as . " to " . $practiceNew->doing_buisness_as . " <br/>";
                        $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $data, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                    }
                } elseif ($col == "payer_id") {

                    $oldVal = $eachColDataArr[$col];
                    $newVal = $data;
                    if ($oldVal != $newVal) {
                        $payerOld     = $this->fetchData("payers", ['id' => $oldVal], 1);
                        $payerNew     = $this->fetchData("payers", ['id' => $newVal], 1);
                        $payerName    = is_object($payerOld) ? $payerOld->payer_name : "";
                        $logsData .= "Payer  change from " . $payerName . "  to " . $payerNew->payer_name . " <br/>";
                        $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $data, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                    }
                } elseif ($col == "status") {
                    $oldVal = $eachColDataArr[$col];
                    $newVal = $data;
                    if ($oldVal != $newVal) {
                        $arStatusOld       = $this->fetchData("revenue_cycle_status", ['id' => $oldVal], 1);
                        $arStatusNew       = $this->fetchData("revenue_cycle_status", ['id' => $newVal], 1);
                        $statusStr = is_object($arStatusOld) ? $arStatusOld->status : "";
                        $logsData .= "Status  change from " . $statusStr . " to " . $arStatusNew->status . " <br/>";

                        $paidStatusCount = ArStatus::where('id', "=", $data)

                            ->where('considered_as_completed', '=', 1)

                            ->count();

                        if ($paidStatusCount == 1)
                            $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $data, 'last_followup_date' => date('Y-m-d'), 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId, "paid_date" => date('Y-m-d')]);
                        else
                            $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $data, 'last_followup_date' => date('Y-m-d'), 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                    }
                } elseif ($col == "remarks") {
                    $oldVal = $eachColDataArr[$col];
                    $newVal = $data;
                    if ($oldVal != $newVal) {

                        $arRemarksOld  = $this->fetchData("revenue_cycle_remarks", ['id' => $oldVal], 1);
                        $arRemarksNew  = $this->fetchData("revenue_cycle_remarks", ['id' => $newVal], 1);
                        // $this->printR($arRemarksOld,true);
                        $oldRemarksStr = is_object($arRemarksOld) ? $arRemarksOld->remarks : "";
                        $newRemarksStr = is_object($arRemarksNew) ? $arRemarksNew->remarks : "";
                        $logsData .= "Remarks  change from " . $oldRemarksStr . " to " . $newRemarksStr . " <br/>";
                        // $this->printR([$col => $data],true);

                        $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $data, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                    }
                } elseif ($col == "assigned_to") {
                    $oldVal = $eachColDataArr[$col];
                    $newVal = $data;
                    if ($oldVal != $newVal) {
                        $assignToOld  = $this->getUserNameById($oldVal);
                        $assignToNew = $this->getUserNameById($newVal);
                        if ($assignToOld == "Unknown")
                            $logsData .= "Assigned to   " . $assignToNew . " <br/>";
                        else
                            $logsData .= "Assignee   change from " . $assignToOld . " to " . $assignToNew . " <br/>";

                        $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $newVal, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                    }
                } else {
                    if ($col != "id" && $col != "created_by") { //skip selected columns

                        if ($col == "dob" || $col == "dos") { //format the date
                            $oldVal = $eachColDataArr[$col];

                            $newVal = $this->isoDate($data); //date("Y-m-d", strtotime($data));
                            if ($oldVal != $newVal) {
                                $readStateCol = $this->dbColumnReadable($col);
                                if (is_null($oldVal))
                                    $logsData .= $readStateCol . "  assigned to " . $newVal . " <br/>";
                                else
                                    $logsData .= $readStateCol . "  changed from " . $oldVal . " to " . $newVal . " <br/>";

                                $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $newVal, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                            }
                        } elseif (($data > 0 || $data == "") && ($col == "billed_amount" || $col == "paid_amount")) { //validate the amount for $
                            $readStateCol = $this->dbColumnReadable($col);
                            $oldVal = $eachColDataArr[$col];

                            $oldVal_ = $eachColDataArr[$col];
                            if (is_float($data))
                                $newdata = $data;
                            else
                                $newdata = (float)$data;

                            $data_ = $data;
                            if (strpos($data, '$') === false) {
                                $data = '$  ' . number_format($newdata, 2, '.', ',');
                                $data_ = $data_;
                            }


                            if ($oldVal_ != $data_) {

                                $oldVal = str_replace('$', '', $oldVal);
                                if (is_float($oldVal))
                                    $oldVal_ = $oldVal;
                                else
                                    $oldVal_ = (float)$oldVal;

                                try {
                                    if (is_float($oldVal_))
                                        $oldVal = "$  " . number_format($oldVal_, 2, '.', ',');
                                    else
                                        $oldVal = "";
                                } catch (\Exception) {
                                }

                                if (is_null($oldVal) || $oldVal == "")
                                    $logsData .= $readStateCol . "  assigned to "  . $data . " <br/>";
                                if (is_null($data) || $data == "")
                                    $logsData .= $readStateCol . "  assigned to "  . $data . " <br/>";
                                if ((!is_null($oldVal) && $oldVal != "") && (!is_null($data) && $data != ""))
                                    $logsData .= $readStateCol . "  change from " . $oldVal . " to " . $data . " <br/>";


                                $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $data_, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                            }
                        } else {
                            $oldVal = $eachColDataArr[$col];
                            $newVal = $data;
                            if ($oldVal != $newVal) {
                                $readStateCol = $this->dbColumnReadable($col);
                                if (is_null($oldVal))
                                    $logsData .= $readStateCol . "  assigned to "  . $newVal . " <br/>";
                                else
                                    $logsData .= $readStateCol . "  change from " . $oldVal . " to " . $newVal . " <br/>";

                                $isUpdate  = AccountReceivable::where("id", $id)->update([$col => $data, 'updated_at' => $this->timeStamp(), 'updated_by' => $sessionUserId]);
                            }
                        }
                    }
                }
            }

            //add the updatation logs into the database
            if (strlen($logsData) > 0) {
                $recData = AccountReceivable::where("id", $id)->first("status");
                ARLogs::insertGetId([
                    'user_id' => $request->session_userid, 'ar_id' => $id,
                    'ar_status_id' => $recData->status, 'details' => $logsData, 'is_system' => 1,
                    'created_at' => $this->timeStamp()
                ]);
            }
            return $this->successResponse(["is_update" => $isUpdate, 'is_sync_update' => $isSynced], "success", 200);
        }

        // catch (\Throwable $exception) {

        //     return $this->errorResponse([],$exception->getMessage(),500);
        // }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $summary = [];
        $deletedRecords = 0;
        $isDelete = false;
        $deletedStatusId = $this->deleteStatusId();
        $deletedRemark = $this->deleteStatusRemarkId($deletedStatusId->id);
        try {
            if (!$request->has('is_bluck')) {


                $isDelete  = AccountReceivable::where("id", $id)->update(['is_delete' => 1, 'status' => $deletedStatusId->id, 'remarks' => $deletedRemark->remark_id]);
                if ($isDelete) {
                    $logsData = "Claim deleted";
                    ARLogs::insertGetId([
                        'user_id' => $request->session_userid, 'ar_id' => $id,
                        'ar_status_id' => $deletedStatusId->id, 'details' => $logsData, 'is_system' => 1,
                        'created_at' => $this->timeStamp()
                    ]);
                }
            } else {
                $ids = json_decode($request->get('ids'), true);


                // $this->printR($ids,true);
                $isDelete  = AccountReceivable::whereIn("id", $ids)->update(['is_delete' => 1, 'status' => $deletedStatusId->id, 'remarks' => $deletedRemark->remark_id]);
                if ($isDelete) {
                    foreach ($ids as $id) {
                        $logsData = "Claim deleted";
                        ARLogs::insertGetId([
                            'user_id' => $request->session_userid, 'ar_id' => $id,
                            'ar_status_id' => $deletedStatusId->id, 'details' => $logsData, 'is_system' => 1,
                            'created_at' => $this->timeStamp()
                        ]);
                    }
                }
            }

            return $this->successResponse(["is_delete" => $isDelete, 'error_summary' => $summary, 'deleted_records' => $deletedRecords], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the account recieveable status
     */
    function getAccountRecieveableStatus(Request $request)
    {


        // $arrStatus = $this->fetchDataWithOrder("ar_status", "", [], "ASC", "status");
        $exclude = $request->has('exclude') ? json_decode($request->get('exclude'), true) :  [];
        $arrStatus = ArStatus::orderBy("status", "ASC");
        if (count($exclude)) {
            $arrStatus = $arrStatus->WhereNotIn("status", $exclude);
        }

        $arrStatus = $arrStatus->get();

        $remarksArr = [];
        $filterStatusArr = [];
        if (count($arrStatus)) {
            foreach ($arrStatus as $key => $value) {

                $remarks = DB::table("revenue_cycle_remarks_map")

                    ->select("revenue_cycle_remarks.*")

                    ->leftJoin('revenue_cycle_remarks', 'revenue_cycle_remarks.id', '=', 'revenue_cycle_remarks_map.remark_id')

                    ->where('revenue_cycle_remarks_map.status_id', '=', $value->id)

                    ->get();

                if (count($remarks) && $value->is_special == 0) {
                    $remarksArr[$value->id] = $remarks;
                    array_push($filterStatusArr, $value);
                }
                if ($value->is_special == 1) {
                    array_push($filterStatusArr, $value);
                }
            }
        }
        return $this->successResponse(["status" => $filterStatusArr, 'remarks' => $remarksArr], "success", 200);
    }
    /**
     * fetch the account recieveable status
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */

    function getPracticeFacilityPayersDD(Request $request)
    {
        $sessionUser = $this->getSessionUserId($request);

        $sessionUserId = $sessionUser;
        // $credentialing   = new Credentialing();
        $arModel = new AccountReceivable();
        // $arrStatus = $this->fetchDataWithOrder("ar_status", "", [], "ASC", "status");
        $arrStatus = ArStatus::WhereNotIn("status", ["DELETED", "ARCHIVED"])

            ->orderBy("status", "ASC")

            ->get();

        $arRemarks = $this->fetchDataWithOrder("revenue_cycle_remarks", "", [], "ASC", "remarks");

        $payers      = Payer::orderBy("payer_name", "ASC")->get();

        $activePractices = $arModel->activePractices($sessionUserId);

        $activePractices =  $this->stdToArray($activePractices);
        // $this->printR($activePractices,true);
        $facilityData = [];
        $facilityIdsArr = [];
        foreach ($activePractices as $activePractice) {
            // $this->printR($activePractice,true);
            $practiceId = $activePractice['facility_id'];
            $practiceName = $activePractice['doing_buisness_as'];
            $sheltersData = [];

            $locations = $arModel->getFacilities($practiceId, $sessionUserId);

            $locations =  $this->stdToArray($locations);

            $facilityIds = array_column($locations, "facility_id");

            $hasShelter = Shelters::hasShelters($facilityIds);

            if ($hasShelter) {
                foreach ($locations as $location) {
                    // $locationArr = $this->stdToArray($location);
                    array_push($sheltersData, $location);
                    array_push($facilityIdsArr, $practiceId . "." . $location['facility_id']);
                    $shelters = Shelters::facilityShelters($location['facility_id']);
                    if (count($shelters)) {
                        foreach ($shelters as $shelter) {
                            $shelterArr = $this->stdToArray($shelter);
                            // $this->printR($shelterArr,true);
                            array_push($facilityIdsArr, $shelterArr['facility_id']);
                            array_push($sheltersData, $shelterArr);
                        }
                    }
                }
                $facilityData[$practiceId] = $sheltersData;
            } else {
                $locations = $arModel->getFacilities($practiceId, $sessionUserId);
                $facilityData[$practiceId] = $locations;
                foreach ($facilityIds as $id) {
                    array_push($facilityIdsArr, $practiceId . "." . $id);
                }
            }
            // $this->printR($facilityIds,true);
            //$this->printR($locations,true);

        }
        // $this->printR($facilityIdsArr,true);
        $payerFilterByFacility = [];
        if (count($facilityIdsArr)) {
            foreach ($facilityIdsArr as $facilityId) {
                if (strpos($facilityId, ".") === false) {
                    $payersFilter = $arModel->fetchARPayer($facilityId);
                    $payerFilterByFacility[$facilityId] = $payersFilter;
                } else {
                    $facilityIdStr =  $facilityId;
                    $facilityId = $this->removeDecimalFromString($facilityId);
                    $payersFilter = $arModel->fetchARPayer($facilityId);
                    $payerFilterByFacility[$facilityIdStr] = $payersFilter;
                }
            }
        }


        $ids = array_column($activePractices, "facility_id");

        // // $this->printR($ids,true);
        $locations = $arModel->getFacilities($ids, $sessionUserId);

        //$this->printR($locations,true);
        $payerArr = [];
        foreach ($payers as $payer) {
            array_push($payerArr, ["label" => $payer->payer_name, "value" => $payer->id]);
        }
        $activeUsers = $this->sysActiveUsers([12, 14, 15, 13]);
        $sysUsers = $this->sysAllUsers([12, 14, 15, 13]);
        $sysUsersArr = $this->stdToArray($sysUsers);
        // $this->printR($sysUsersArr,true);
        $newArray = [
            "value" => "Unassigned",
            "label" => "Unassigned"
        ];

        array_unshift($sysUsersArr, $newArray);
        // $credentialing = NULL;
        $arModel = NULL;
        return $this->successResponse([
            "status" => $arrStatus, 'payers' =>  $payerArr,
            'practices' => $activePractices, 'facilities' => $locations,
            'remarks' => $arRemarks, 'all_facilities' => $facilityData,
            'payer_by_facility' => $payerFilterByFacility,
            'active_users' => $activeUsers,
            'all_users' => $sysUsersArr
        ], "success", 200);
    }

    /**
     * update the status and assign to data
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    function updateBluckSelection(Request $request)
    {

        $isUpdate = 0;
        $updatedBy = $request->session_userid;
        if ($request->has('assigned_to') && $request->assigned_to != "") {
            //echo "hi1";
            $assignedTo = $request->get('assigned_to');

            $status = $request->update_status;

            $remarks = $request->remarks;

            $ids = json_decode($request->ids, true);
            /**
             * bellow code for recod the logs activity for AR
             *
             */
            if (count($ids)) {
                foreach ($ids as $id) {
                    
                    $arAssignTo = AccountReceivable::where('account_receivable.id', '=', $id)

                        ->select(
                            'revenue_cycle_status.status as from_status',
                            DB::raw('(SELECT `status`  FROM cm_revenue_cycle_status WHERE id="' . $status . '") AS to_status'),
                            DB::raw('(SELECT `remarks`  FROM cm_revenue_cycle_remarks WHERE id="' . $remarks . '") AS to_remarks', 'revenue_cycle_remarks.remarks as from_remarks')
                        )

                        ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')

                        ->leftJoin('revenue_cycle_remarks', 'revenue_cycle_remarks.id', '=', 'account_receivable.remarks')

                        ->first();

                    $assignedToName = $this->getUserNameById($assignedTo);

                    $logData = "Assignee changed from " . $arAssignTo->assigned_to_name . " to " . $assignedToName;
                    if ($remarks) {
                        $logData .= "Remarks changed from " . $arAssignTo->from_remarks . " to " . $arAssignTo->to_remarks;
                    }
                    //when selected to onhold then sync it to the billing
                    if($status == 9)
                        $this->syncStatusToBilling($id,$status,$remarks,$arAssignTo->to_remarks);

                    ARLogs::insertGetId([
                        'user_id' => $request->session_userid, 'ar_id' => $id, 'ar_status_id' => $arAssignTo->status,
                        'details' => $logData, 'is_system' => 1, 'created_at' => $this->timeStamp()
                    ]);
                }
            }
            $updateData = ['assigned_to' => $assignedTo, 'updated_at' => $this->timeStamp(), 'updated_by' => $updatedBy];
            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }
            //$this->printR($updateData);
            $isUpdate = AccountReceivable::whereIn("id", $ids)->update($updateData);
        }
        if ($request->has('update_status') && $request->update_status != "") {
            //echo "hi12";

            $status = $request->update_status;

            $remarks = $request->remarks;

            $ids = json_decode($request->ids, true);
            /**
             * bellow code for recod the logs activity for AR
             */
            if (count($ids)) {
                foreach ($ids as $id) {

                    $arStatus = AccountReceivable::where('account_receivable.id', '=', $id)

                        ->select(
                            'revenue_cycle_status.status as from_status',
                            DB::raw('(SELECT `status`  FROM cm_revenue_cycle_status WHERE id="' . $status . '") AS to_status'),
                            DB::raw('(SELECT `remarks`  FROM cm_revenue_cycle_remarks WHERE id="' . $remarks . '") AS to_remarks', 'revenue_cycle_remarks.remarks as from_remarks')
                        )

                        ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')

                        ->leftJoin('revenue_cycle_remarks', 'revenue_cycle_remarks.id', '=', 'account_receivable.remarks')

                        ->first();

                    $logData = "Status changed from " . $arStatus->from_status . " to " . $arStatus->to_status . "<br>";
                    if ($remarks) {
                        $logData .= "Remarks changed from " . $arStatus->from_remarks . " to " . $arStatus->to_remarks;
                    }
                    //when selected to onhold then sync it to the billing
                    if($status == 9)
                        $this->syncStatusToBilling($id,$status,$remarks,$arStatus->to_remarks);

                    ARLogs::insertGetId([
                        'user_id' => $request->session_userid, 'ar_id' => $id, 'ar_status_id' => $status,
                        'details' => $logData, 'is_system' => 1, 'created_at' => $this->timeStamp()
                    ]);
                }
            }
            if($arStatus->to_status == "") {

            }
            $updateData = ['status' => $status, 'last_followup_date' => date('Y-m-d'), 'updated_at' => $this->timeStamp(), 'updated_by' => $updatedBy];
            if ($remarks) {
                $updateData['remarks'] = $remarks;
            }
            // $this->printR($updateData);
            $isUpdate = AccountReceivable::whereIn("id", $ids)->update($updateData);
        }
        return $this->successResponse(["is_update" => $isUpdate], 'success');
    }
    /**
     * update the bluck EOB data
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    public function updateBluckEOBData(Request $request)
    {
        $bluckEOBData = $request->eob_data;
        $updateData = [];
        if (strlen($bluckEOBData)) {
            $bluckEOBDataArr = json_decode($bluckEOBData, true);
            // $this->printR($bluckEOBDataArr,true);
            foreach ($bluckEOBDataArr as $eobData) {
                // $request->merge([
                //     "eob_number"        => $eobData['eob_number'],
                //     "eob_date"          => $eobData['eob_date'],
                //     "amount"            => $eobData['eob_amount'],
                //     "closed_remarks"    => $eobData['eob_remarks'],
                //     "updated_by"        => $request->session_userid,
                //     "status_id"         => $request->status_id,
                //     "attachment"        => $request->get($eobData['claim_no']),//$eobData['eob_attachment'],
                //     "record_id"         => $eobData['id']
                // ]);
                // $this->updateEOBRelatedData($request);
                $eobNumber      = $eobData['eob_number'];
                $eobDate        = $eobData['eob_date'];
                $amount         = $eobData['eob_amount'];
                $closedRemarks  = $eobData['eob_remarks'];
                $updatedBy      = $request->session_userid;
                $status         = $request->status_id;
                $recordId       = $eobData['id'];
                $hasFile        = $request->hasFile($eobData['claim_no']);
                $file           = $request->file($eobData['claim_no']);
                $note           = $request->has('note') ? $request->note : "";

                $updateData     = $this->updateEOBData($request, $hasFile, $amount, $recordId, $status, $updatedBy, $file, $eobNumber, $eobDate, $closedRemarks, $note);
            }
            return $this->successResponse($updateData, 'success');
        } else
            return $this->successResponse(["is_update" => false], 'success');
    }
    /**
     * update the eobi related data
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    public function updateEOBRelatedData(Request $request)
    {

        $eobNumber      = $request->eob_number;
        $eobDate        = $request->eob_date;
        $amount         = $request->amount;
        $closedRemarks  = $request->closed_remarks;
        $updatedBy      = $request->updated_by;
        $status         = $request->status_id;
        $recordId       = $request->record_id;
        $hasFile        = $request->hasFile('attachment');
        $request->merge(["file" => $request->file('attachment')]);
        $file           = $request->file('attachment');
        $note           = $request->has('note') ? $request->note : "";

        $updateData     = $this->updateEOBData($request, $hasFile, $amount, $recordId, $status, $updatedBy, $file, $eobNumber, $eobDate, $closedRemarks, $note);

        return $this->successResponse($updateData, 'success');
    }
    /**
     * update each ar eob data
     */
    private function updateEOBData($request, $hasFile, $amount, $recordId, $status, $updatedBy, $file, $eobNumber, $eobDate, $closedRemarks, $note)
    {

        $logData = "";
        $reqAmount = "";
        $isUpdate = 0;
        $isFile = 0;
        $fileName = "";
        $sizeUnit = "";
        if (Str::contains($amount, "$")) {
            $amount = str_replace('$', '', $amount);
            $reqAmount      = $amount;
        } else {
            $reqAmount      = $amount;
        }

        $claimData = AccountReceivable::where('id', $recordId)

            ->first();

        $statusOld = $claimData->status;
        if ($status != $statusOld) {
            $isUpdate = 1;
            $arStatusOld       = $this->fetchData("revenue_cycle_status", ['id' => $statusOld], 1);
            $arStatusNew       = $this->fetchData("revenue_cycle_status", ['id' => $status], 1);
            $logData .= "Status changed from " . $arStatusOld->status . "  to " . $arStatusNew->status . " <br/>";
            AccountReceivable::where('id', $recordId)
                ->update(['status' => $status, 'updated_by' => $updatedBy, 'updated_at' => $this->timeStamp()]);
        }
        if ($hasFile) {

            // Get the file size in bytes
            $size = $file->getSize();
            $isFile = 1;
            $sizeUnit = $this->fileSizeUnits($size);

            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

            //$this->uploadMyFile($fileName, $file, "arlogs/" . $recordId);

            $destFolder = "arlogsEnc/" . $recordId;

            $fileRes = $this->encryptEachUpload($request, $file, $destFolder);
        }
        if ($eobNumber != "") {
            $oldEOBNumber = $claimData->eob_number;
            if ($eobNumber != $oldEOBNumber) {
                $isUpdate = 1;
                if (is_null($oldEOBNumber))
                    $logData .= "Check number  Assigned to " . $eobNumber . "<br/>";
                else
                    $logData .= "Check number changed from " . $oldEOBNumber . " to " . $eobNumber . "<br/>";

                AccountReceivable::where('id', $recordId)
                    ->update(['eob_number' => $eobNumber, 'updated_by' => $updatedBy, 'updated_at' => $this->timeStamp()]);
            }
        }
        if ($eobDate != "") {
            $oldEOBDate = $claimData->eob_date;
            $eobDate = date("m/d/Y", strtotime($eobDate));
            if ($eobDate !== $oldEOBDate) {
                $isUpdate = 1;
                if (is_null($oldEOBDate))
                    $logData .= "Check date  Assigned to " . $eobDate . "<br/>";
                else
                    $logData .= "Check date changed from " . $oldEOBDate . " to " . $eobDate . "<br/>";

                AccountReceivable::where('id', $recordId)
                    ->update(['eob_date' => $eobDate, 'updated_by' => $updatedBy, 'updated_at' => $this->timeStamp()]);
            }
        }
        if ($amount != "") {
            // $this->printR($claimData,true);
            $oldEOBAmount = $claimData->amount;
            $oldPaidAmount = $claimData->paid_amount;
            // echo $oldEOBAmount;
            // echo "---";
            // echo $amount;
            // exit;


            if ($oldEOBAmount != $amount) {

                if (is_float($oldEOBAmount))
                    $oldEOBAmount = "$  " . number_format($oldEOBAmount, 2, '.', ',');
                else {
                    $oldEOBAmount = (float)$oldEOBAmount;
                    $oldEOBAmount = "$  " . number_format($oldEOBAmount, 2, '.', ',');
                }

                if (is_float($amount))
                    $amount = "$  " . number_format($amount, 2, '.', ',');
                else {
                    $amount = floatval($amount);
                    $amount = "$  " . number_format($amount, 2, '.', ',');
                }

                $isUpdate = 1;
                if (is_null($oldEOBAmount))
                    $logData .= "Check amount Assigned to "  . $amount . "<br/>";
                else
                    $logData .= "Check amount changed from " . $oldEOBAmount . " to " . $amount . "<br/>";


                AccountReceivable::where('id', $recordId)
                    ->update(['amount' => $reqAmount, 'updated_by' => $updatedBy, 'updated_at' => $this->timeStamp(), "paid_amount" => $reqAmount]);
            } else {

                if (is_float($oldEOBAmount))
                    $oldEOBAmount = "$  " . number_format($oldEOBAmount, 2, '.', ',');
                else {
                    $oldEOBAmount = (float)$oldEOBAmount;
                    $oldEOBAmount = "$  " . number_format($oldEOBAmount, 2, '.', ',');
                }

                if (is_float($amount))
                    $amount = "$  " . number_format($amount, 2, '.', ',');
                else {
                    $amount = floatval($amount);
                    $amount = "$  " . number_format($amount, 2, '.', ',');
                }

                $isUpdate = 1;
                if (is_null($oldEOBAmount))
                    $logData .= "Check amount Assigned to "  . $amount . "<br/>";
                else
                    $logData .= "Check amount changed from " . $oldEOBAmount . " to " . $amount . "<br/>";


                AccountReceivable::where('id', $recordId)
                    ->update(['amount' => $reqAmount, 'updated_by' => $updatedBy, 'updated_at' => $this->timeStamp(), "paid_amount" => $reqAmount]);
            }

            if ($oldPaidAmount != $reqAmount) {

                if (is_float($oldPaidAmount))
                    $oldPaidAmount = "$  " . number_format($oldPaidAmount, 2, '.', ',');
                else {
                    $oldPaidAmount = floatval($oldPaidAmount);
                    $oldPaidAmount = "$  " . number_format($oldPaidAmount, 2, '.', ',');
                }

                if (is_float($reqAmount))
                    $amount = "$  " . number_format($reqAmount, 2, '.', ',');
                else {
                    $amount = floatval($reqAmount);
                    $amount = "$  " . number_format($amount, 2, '.', ',');
                }

                $logData .= "Paid amount changed from " . $oldPaidAmount . " to " . $amount . "<br/>";
            } else {
                if (is_float($oldPaidAmount))
                    $oldPaidAmount = "$  " . number_format($oldPaidAmount, 2, '.', ',');
                else {
                    $oldPaidAmount = floatval($oldPaidAmount);
                    $oldPaidAmount = "$  " . number_format($oldPaidAmount, 2, '.', ',');
                }

                if (is_float($reqAmount))
                    $amount = "$  " . number_format($reqAmount, 2, '.', ',');
                else {
                    $amount = floatval($reqAmount);
                    $amount = "$  " . number_format($amount, 2, '.', ',');
                }

                $logData .= "Paid amount changed from " . $oldPaidAmount . " to " . $amount . "<br/>";
            }
        }
        if ($closedRemarks != "") {
            $closedRemarksOld = $claimData->closed_remarks;
            if ($closedRemarksOld != $closedRemarks) {
                $isUpdate = 1;
                if (is_null($closedRemarksOld))
                    $logData .= "Check closed remarks Assigned to "  . $closedRemarks . "<br/>";
                else
                    $logData .= "Check closed remarks changed from " . $closedRemarksOld . " to " . $closedRemarks . "<br/>";

                AccountReceivable::where('id', $recordId)
                    ->update(['closed_remarks' => $closedRemarks, 'updated_by' => $updatedBy, 'updated_at' => $this->timeStamp()]);
            }
        }
        /**
         * record the ar logs
         */
        if ($logData != "" && $isUpdate) {
            $id = ARLogs::insertGetId([
                'user_id' => $updatedBy, 'ar_id' => $recordId, 'ar_status_id' => $status,
                'details' => $logData, 'is_system' => 1, 'created_at' => $this->timeStamp()
            ]);
            if ($isFile) {
                if (isset($fileRes["file_name"])) {
                    $addFileData = [
                        "entities"     => "ar_log",
                        "entity_id"     => $id,
                        "field_key"     => "AR log file",
                        "field_value"   => $fileRes["file_name"],
                        "created_by" => $updatedBy,
                        "note" => !is_null($note) ? $note : NULL,
                        "file_size" => $sizeUnit,
                        "created_at" => $this->timeStamp()

                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }
        }
        return ["is_update" => $isUpdate]; //$this->successResponse(["is_update" => $isUpdate], 'success');
    }

    /**
     * AR dashboard stats
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    function arDashboardStats(Request $request)
    {
        set_time_limit(0);

        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;

        $sessionUser            = $this->getSessionUserId($request);

        $sessionUserId          = $sessionUser;

        $this->sessionUserId    = $sessionUserId;

        if (
            strlen($facilityFilter) > 2     ||
            strlen($practiceFilter) > 2     ||
            strlen($payerFilter) > 2        ||
            strlen($statusFilter) > 2       ||
            strlen($assignedToFilter) > 2   ||
            strlen($dateRangeFilter) > 2    ||
            strlen($buntyFilter) > 2        ||
            strlen($remarksFilter) > 2      ||
            strlen($search) > 0
        ) {
            $practiceIds = [];
            $practiceIdsStr = "";

            $facilityIds = [];
            $facilityIdsStr = "";

            $payerIds = [];
            $payerIdsStr = "";

            $statusIds = [];
            $statusIdsStr = "";

            $assignToIds = [];
            $assignToIdsStr = "";

            $remarksIds = [];
            $remarksIdsStr = "";

            $rangeFilter = [];
            $buntyFilterArr = [];
            $shelterIdsStr = "";
            $hasUnAssigned = false;
            if (strlen($practiceFilter) > 2) {
                $practiceFilter = json_decode($practiceFilter, true);

                $practiceIds = array_column($practiceFilter, "value");

                $practiceIdsStr = implode(",", $practiceIds);
                //$this->printR($practiceFilter,true);
            }
            if (strlen($facilityFilter) > 2) {
                $facilityFilter = json_decode($facilityFilter, true);

                $facilityIds = array_column($facilityFilter, "value");

                $facilityIdsArr = $this->removeDecimalValues($facilityIds);

                // $this->printR($facilityIds,true);

                $facilityIds = $facilityIdsArr["facility"];

                $facilityIdsStr = implode(",", $facilityIds);

                $shelterIdsStr = implode(",", $facilityIdsArr["shelter"]);
                //$this->printR($practiceFilter,true);
            }
            if (strlen($payerFilter) > 2) {

                $payerFilter = json_decode($payerFilter, true);

                // $payerIds = array_column($payerFilter, "value");

                $payerIdsStr = implode(",", $payerFilter);

                //$this->printR($practiceFilter,true);
            }
            if (strlen($statusFilter) > 2) {

                $statusFilter = json_decode($statusFilter, true);

                $statusIds = array_column($statusFilter, "id");

                $statusIdsStr = implode(",", $statusIds);
            }
            if (strlen($assignedToFilter) > 2) {
                $assignedToFilter = json_decode($assignedToFilter, true);
                $assignToIds = array_column($assignedToFilter, "value");
                if (in_array("Unassigned", $assignToIds)) {
                    $hasUnAssigned =  true;
                    $key = array_search("Unassigned", $assignToIds);
                    unset($assignToIds[$key]);
                }
                // echo "Faheem:".$hasUnAssigned;
                // exit;
                $assignToIdsStr = implode(",", $assignToIds);
                // $this->printR($assignToIds,true);
            }
            if (strlen($dateRangeFilter) > 2) {
                $rangeFilter = json_decode($dateRangeFilter, true);
            }
            if (strlen($buntyFilter) > 2) {
                $buntyFilterArr = json_decode($buntyFilter, true);
            }
            if (strlen($remarksFilter) > 2) {
                $remarksFilter = json_decode($remarksFilter, true);

                $remarksIds = array_column($remarksFilter, "id");

                $remarksIdsStr = implode(",", $remarksIds);
                //$this->printR($practiceFilter,true);
            }

            $agingStats = $this->getAgingRangesStatsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);

            $practiceStats = $this->getPracticeStatsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);

            $assignedToUsersStats =  $this->getAssignedUserStatsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);

            $timelyStats = $this->getTimelyReportsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);

            $statusWiseSummary = $this->getStatusWiseSummaryFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);
        } else {

            $agingStats = $this->getAgingRangesStats();

            $practiceStats = $this->getPracticeStats();

            $assignedToUsersStats = $this->getAssignedUserStats();

            $timelyStats = $this->getTimelyReports();

            $statusWiseSummary = $this->getStatusWiseSummary();
        }
        $arr = [
            'aging_stats' => $agingStats,
            'practice_stats' => $practiceStats,
            'assigned_users_stats' => $assignedToUsersStats,
            'timely_stats' => $timelyStats,
            'currency_symbol' => '$',
            'status_wise_summary' => $statusWiseSummary
        ];

        return $this->successResponse($arr, 'success');
    }
    /**
     * Status wise summary
     *
     */
    private function getStatusWiseSummaryFilter($practiceIds, $payerIds, $statusIds, $assignTo, $rangeFilter, $buntyFilter, $remarks, $facilityIds, $search, $shelterIdsStr, $hasUnAssigned)
    {
        $key = $this->key;
        $sessionUserId = $this->sessionUserId;

        $isArchived = 0;
        $arModel = new AccountReceivable();
        $practices = $arModel->activePractices($sessionUserId);

        $sessionPracticeIds = [];
        foreach ($practices as $practice) {
            $sessionPracticeIds[] = $practice->facility_id;
        }

        $facilities = [];
        foreach ($sessionPracticeIds as $practiceId) {
            $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
        }

        $sessionFacilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $sessionFacilityIds[] = $f->facility_id;
            }
        }

        $sessionFacilityIds = array_unique($sessionFacilityIds);

        $sessionPracticeIds = implode(",", $sessionPracticeIds);
        $sessionFacilityIds = implode(", ", $sessionFacilityIds);

        $sql = "SELECT
            COUNT(T.claim_no) as claims,
            SUM(T.billed_amount) as amount,
            T.status,
            T.status_name
        FROM
          ( select
                `cm_account_receivable`.`claim_no`,
                `cm_account_receivable`.`dob`,
                `cm_account_receivable`.`dos`,
                `cm_account_receivable`.`billed_amount`,
                `cm_account_receivable`.`paid_amount`,
                `cm_revenue_cycle_status`.`status` as `status_name`,
                DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y'	) AS entered_date,
                `cm_account_receivable`.`created_at`,
                DATEDIFF(CURDATE(),	cm_account_receivable.dos) as aging_days,
                (CASE
                    WHEN DATEDIFF(CURDATE(),	cm_account_receivable.dos) > cm_payers.timely_filling_limit THEN 'Expired'
                    WHEN (DATEDIFF(CURDATE(),	cm_account_receivable.dos) > 60 && DATEDIFF(CURDATE(),cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60  THEN 'Under 60s'
                    ELSE 'None'
                END ) AS aging_status,
                `cm_account_receivable`.`practice_id`,
                `cm_account_receivable`.`facility_id`,
                `cm_account_receivable`.`shelter_id`,
                `cm_account_receivable`.`payer_id`,
                `cm_account_receivable`.`status`,
                `cm_account_receivable`.`assigned_to`,
                `cm_account_receivable`.`last_followup_date`,
                `cm_account_receivable`.`remarks` as `remarks_id`,
                `cm_account_receivable`.`status` as `status_id`,
                `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
                `cm_account_receivable`.`next_follow_up`,
                `cm_account_receivable`.`paid_date`
            from
                `cm_account_receivable`
                left join `cm_payers` on `cm_payers`.`id`=`cm_account_receivable`.`payer_id`
                left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id`=`cm_account_receivable`.`status`
            WHERE
                `cm_account_receivable`.`is_delete` = 0
                AND `cm_account_receivable`.`practice_id` IN ($sessionPracticeIds)
		        AND `cm_account_receivable`.`facility_id` IN ($sessionFacilityIds)

          ) AS T  WHERE T.status NOT IN(25) ";

        if (strlen($practiceIds)) {
            $sql .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            $sql .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            $sql .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            $sql .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        }
        if (strlen($payerIds)) {
            $sql .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {

            $sql .= " AND T.status_id IN($statusIds)";
        }

        if (strlen($assignTo) > 0 && $hasUnAssigned ==  true) {
            $sql .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL )";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned ==  true) {
            $sql .= " AND T.assigned_to_id IS NULL";
        }
        if (strlen($assignTo) > 0 && $hasUnAssigned ==  false) {
            $sql .= " AND T.assigned_to_id IN($assignTo) ";
        }


        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql .= " AND T.dos = '$startDate'";
                else
                    $sql .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql .= " AND T.dob = '$startDate'";
                else
                    $sql .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql .= " AND T.last_followup_date = '$startDate'";
                else
                    $sql .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql .= " AND DATE(T.created_at) = '$startDate'";
                else
                    $sql .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql .= " AND T.next_follow_up = '$startDate'";
                else
                    $sql .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql .= " AND T.paid_date = '$startDate'";
                else
                    $sql .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
            }
        }
        if (count($buntyFilter)) {
            $btq = "(";
            if (in_array('1', $buntyFilter)) {
                $btq .= "  T.aging_status ='Expired'";
                if (count($buntyFilter) > 1)
                    $btq .= " OR ";
            }
            if (in_array('2', $buntyFilter)) {
                $btq .= " T.aging_status ='Expiring Soon'";
                if (in_array('3', $buntyFilter))
                    $btq .= " OR ";
            }
            if (in_array('3', $buntyFilter)) {
                $btq .= "  T.aging_status = 'Under 60s'";
            }
            $btq .= ")";
            $sql .= "AND $btq";
        }
        if (strlen($remarks)) {
            $sql .= " AND T.remarks_id IN($remarks)";
        }
        // if (strlen($search)) {
        //     $searchDate = date('Y-m-d', strtotime($search));
        //     $sql .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //     OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //     OR T.entered_date LIKE '%$search%' OR T.remarks LIKE '%$search%'
        //     OR T.aging_days LIKE '%$search%' OR T.payer_name LIKE '%$search%'
        //     OR T.assigned_to_name LIKE '%$search%'
        //     OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //     OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //     OR T.shelter_name LIKE '%$search%'
        //     OR T.status_name LIKE '%$search%'
        //     ";

        //     if ($searchDate != "1970-01-01") {

        //         $sql .= "  OR T.dob LIKE '$searchDate%'
        //         OR T.dos LIKE '$searchDate%'
        //         OR T.last_followup_date LIKE '$searchDate%'
        //         OR T.next_follow_up LIKE '$searchDate%'
        //         OR T.created_at LIKE '$searchDate%'";
        //     }
        //     $sql .= " )";
        // }
        $sql .= " GROUP BY T.status_name ORDER BY claims DESC";
        return $this->rawQuery($sql);
    }
    /**
     * get the exclude practices and facility in active
     */
    private function getExcludePracticesFacilities($isAnd = 1)
    {
        if ($isAnd) {
            return "and ( `cm_account_receivable`.`practice_id` IN(SELECT u.id FROM `cm_users`
            u INNER JOIN cm_user_role_map urm ON urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' ) and
            `cm_account_receivable`.`facility_id` IN(SELECT u.id FROM `cm_users` u INNER JOIN cm_user_role_map urm ON
            urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' ))";
        } else {
            return "and `cm_account_receivable`.`practice_id` IN(SELECT u.id FROM `cm_users`
            u INNER JOIN cm_user_role_map urm ON urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' ) and
            `cm_account_receivable`.`facility_id` IN(SELECT u.id FROM `cm_users` u INNER JOIN cm_user_role_map urm ON
            urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' )";
        }
    }
    /**
     * Status wise summary
     *
     */
    private function getStatusWiseSummary()
    {
        $key = $this->key;
        $tbl = "cm_" . $this->tbl;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;

        $isArchived = 0;
        $arModel = new AccountReceivable();

        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $practiceIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];

        $result = AccountReceivable::selectRaw('
            COUNT(cm_account_receivable.claim_no) as claims,
            SUM(cm_account_receivable.balance_amount) as amount,
            SUM(cm_account_receivable.paid_amount) as paid_amount,
            payer_name
        ')->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')
            ->leftJoin('payers', 'payers.id', '=', 'account_receivable.payer_id')
            ->where('account_receivable.is_delete', 0)
            ->where('revenue_cycle_status.status', '!=', 'Deleted')
            ->whereIn('revenue_cycle_status.id', [10, 1, 8, 4, 7, 9, 3])
            ->whereIn('account_receivable.practice_id', $practiceIds)
            ->whereIn('account_receivable.facility_id', $sessionFacilityIds)
            ->groupBy('payer_id')
            ->orderByDesc('claims')
            ->get();
        return $result;
    }
    /**
     * create the aging ranges stats
     */
    private function getAgingRangesStatsFilter($practiceIds, $payerIds, $statusIds, $assignTo, $rangeFilter, $buntyFilter, $remarks, $facilityIds, $search, $shelterIdsStr, $hasUnAssigned)
    {
        $key = $this->key;
        $tbl = "cm_" . $this->tbl;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;
        set_time_limit(0);

        $isArchived = 0;
        $arModel = new AccountReceivable();
        $practices = $arModel->activePractices($sessionUserId);
        // now get ids from the practicies...
        $sessionPracticeIds = [];
        foreach ($practices as $practice) {
            $sessionPracticeIds[] = $practice->facility_id;
        }

        // now get facilities by practice ids and user session id in a loop on practice ids...
        $facilities = [];
        foreach ($sessionPracticeIds as $practiceId) {
            $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
        }

        $sessionFacilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $sessionFacilityIds[] = $f->facility_id;
            }
        }

        $sessionFacilityIds = array_unique($sessionFacilityIds);

        $sessionPracticeIds = implode(",", $sessionPracticeIds);
        $sessionFacilityIds = implode(", ", $sessionFacilityIds);

        //     if (strlen($statusIds))
        //         $andCluas = "";
        //     else
        //         $andCluas = " AND cm_revenue_cycle_status.considered_as_completed = 0";

        //     $sql1 = "SELECT
        //     COUNT(T.claim_no) as claims,
        //     SUM(T.paid_amount) as amount,
        //     '0-30' as aging_range
        //   FROM
        //     (
        //       select
        //         `cm_account_receivable`.`claim_no`,
        //         ubp.doing_business_as as practice_name,
        //         `cm_cu`.`first_name`,
        //         AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name,
        //         `cm_payers`.`payer_name`,
        //         CONCAT(
        //           cm_cu.first_name, '
        //   ', cm_cu.last_name
        //         ) as assigned_to_name,
        //         CONCAT(
        //           cm_cu_.first_name, ' ', cm_cu_.last_name
        //         ) as created_by_name,
        //         `cm_revenue_cycle_status`.`status` as `status_name`,
        //         `cm_account_receivable`.`dob`,
        //         `cm_account_receivable`.`dos`,
        //         `cm_account_receivable`.`billed_amount`,
        //         `cm_account_receivable`.`paid_amount`,
        //         DATE_FORMAT(
        //           cm_account_receivable.created_at,
        //           '%m/%d/%Y'
        //         ) AS entered_date,
        //         `cm_revenue_cycle_remarks`.`remarks`,
        //         `cm_account_receivable`.`id`,
        //         `cm_account_receivable`.`patient_name`,
        //         `cm_account_receivable`.`created_at`,
        //         `cm_payers`.`timely_filling_limit`,
        //         DATEDIFF(
        //           CURDATE(),
        //           cm_account_receivable.dos
        //         ) as aging_days,
        //         (
        //           CASE WHEN DATEDIFF(
        //             CURDATE(),
        //             cm_account_receivable.dos
        //           ) > cm_payers.timely_filling_limit THEN 'Expired' WHEN (
        //             DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) > 60 && DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) <= cm_payers.timely_filling_limit
        //           )
        //             THEN 'Expiring Soon'
        //             WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60
        //             THEN 'Under 60s'
        //             ELSE 'None' END
        //         ) AS aging_status,
        //         `cm_account_receivable`.`practice_id`,
        //         `cm_account_receivable`.`facility_id`,
        //         `cm_account_receivable`.`shelter_id`,
        //         `cm_account_receivable`.`payer_id`,
        //         `cm_account_receivable`.`assigned_to`,
        //         `cm_account_receivable`.`status`,
        //         `cm_account_receivable`.`eob_number`,
        //         `cm_account_receivable`.`eob_date`,
        //         `cm_account_receivable`.`amount`,
        //         `cm_account_receivable`.`closed_remarks`,
        //         cm_account_receivable.last_followup_date,
        //         `cm_account_receivable`.`remarks` as `remarks_id`,
        //         `cm_account_receivable`.`status` as `status_id`,
        //         `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
        //         `cm_account_receivable`.`next_follow_up`,
        //         `cm_account_receivable`.`paid_date`,
        //         `cm_shelters`.`name` as `shelter_name`
        //       from
        //         `cm_account_receivable`
        //         -- left join `$tbl` on `$tbl`.`user_id` = `cm_account_receivable`.`practice_id`
        //         -- left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `cm_account_receivable`.`facility_id`
        //         inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '$sessionUserId'
        //         left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //         left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //         left join `cm_shelter_facility_map` on (`cm_shelter_facility_map`.`shelter_id`=`cm_account_receivable`.`shelter_id` and
        //                                                 `cm_shelter_facility_map`.`facility_id`=`cm_account_receivable`.`facility_id`)
        //         left join `cm_shelters` on `cm_shelters`.`id`=`cm_shelter_facility_map`.`shelter_id`
        //         left join `cm_payers` on `cm_payers`.`id` = `cm_account_receivable`.`payer_id`
        //         left join `$tblU` as `cm_cu` on `cm_cu`.`id` = `cm_account_receivable`.`assigned_to`
        //         left join `$tblU` as `cm_cu_` on `cm_cu_`.`id` = `cm_account_receivable`.`created_by`
        //         left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //         left join `cm_revenue_cycle_remarks` on `cm_revenue_cycle_remarks`.`id` = `cm_account_receivable`.`remarks`
        //         inner join `$tblU` AS `cm_u_practice` ON `cm_u_practice`.`id`= `cm_account_receivable`.`practice_id` AND `cm_u_practice`.`deleted` = 0
        //         inner join `$tblU` AS `cm_u_facility` ON `cm_u_facility`.`id` = `cm_account_receivable`.`facility_id` AND `cm_u_facility`.`deleted` = 0

        //       WHERE
        //         `cm_account_receivable`.`is_delete` = 0 $andCluas

        //     ) AS T
        //     WHERE T.aging_days >= 0 AND T.aging_days <= 30
        //   ";

        //     if (strlen($practiceIds)) {
        //         $sql1 .= " AND T.practice_id IN($practiceIds)";
        //     }
        //     if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
        //         $sql1 .= " AND T.facility_id IN($facilityIds)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
        //         $sql1 .= " AND T.shelter_id IN($shelterIdsStr)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds)) {
        //         $sql1 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        //     }
        //     if (strlen($payerIds)) {
        //         $sql1 .= " AND T.payer_id IN($payerIds)";
        //     }
        //     if (strlen($statusIds)) {
        //         $sql1 .= " AND T.status_id IN($statusIds)";
        //     }

        //     if (strlen($assignTo) && $hasUnAssigned == true) {
        //         $sql1 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL ) ";
        //     }
        //     if (strlen($assignTo) && $hasUnAssigned == false) {
        //         $sql1 .= " AND T.assigned_to_id IN($assignTo)  ";
        //     }
        //     if (strlen($assignTo) == 0 && $hasUnAssigned == true) {
        //         $sql1 .= " AND T.assigned_to_id IS NULL ";
        //     }

        //     if (count($rangeFilter)) {
        //         if ($rangeFilter['column'] == "dos") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql1 .= " AND T.dos  = '$startDate'";
        //             else
        //                 $sql1 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "dob") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql1 .= " AND T.dob  = '$startDate'";
        //             else
        //                 $sql1 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "last_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql1 .= " AND T.last_followup_date  = '$startDate'";
        //             else
        //                 $sql1 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "entered_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql1 .= " AND DATE(T.created_at)  = '$startDate'";
        //             else
        //                 $sql1 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "next_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql1 .= " AND T.next_follow_up  = '$startDate'";
        //             else
        //                 $sql1 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "paid_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql1 .= " AND T.paid_date  = '$startDate'";
        //             else
        //                 $sql1 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //     }
        //     if (count($buntyFilter)) {
        //         $btq = "(";
        //         if (in_array('1', $buntyFilter)) {
        //             $btq .= "  T.aging_status ='Expired'";
        //             if (count($buntyFilter) > 1)
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('2', $buntyFilter)) {
        //             $btq .= " T.aging_status ='Expiring Soon'";
        //             if (in_array('3', $buntyFilter))
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('3', $buntyFilter)) {
        //             $btq .= "  T.aging_status = 'Under 60s'";
        //         }
        //         $btq .= ")";
        //         $sql1 .= "AND $btq";
        //     }
        //     if (strlen($remarks)) {
        //         $sql1 .= " AND T.remarks_id IN($remarks)";
        //     }

        //     if (strlen($search)) {
        //         $searchDate = date('Y-m-d', strtotime($search));
        //         $sql1 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //             OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //             OR T.remarks LIKE '%$search%'
        //             OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //             OR T.assigned_to_name LIKE '%$search%'
        //             OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //             OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //             OR T.shelter_name LIKE '%$search%'
        //             OR T.status_name LIKE '%$search%'
        //             ";
        //         if ($searchDate != "1970-01-01") {
        //             $sql1 .= " OR T.dob LIKE '$searchDate%'
        //             OR T.dos LIKE '$searchDate%'
        //             OR T.last_followup_date LIKE '$searchDate%'
        //             OR T.next_follow_up LIKE '$searchDate%'
        //             OR T.created_at LIKE '$searchDate%'";
        //         }
        //         $sql1 .= " )";
        //     }

        //     $sql2 = "SELECT
        //     COUNT(T.claim_no) as claims,
        //     SUM(T.paid_amount) as amount,
        //     '31-60' as aging_range
        //   FROM
        //     (
        //       select
        //         `cm_account_receivable`.`claim_no`,
        //         -- AES_DECRYPT($tbl.doing_buisness_as,'$key') as practice_name,
        //         ubp.doing_business_as as practice_name,
        //         `cm_cu`.`first_name`,
        //         AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name,
        //         `cm_payers`.`payer_name`,
        //         CONCAT(
        //           cm_cu.first_name, '
        //   ', cm_cu.last_name
        //         ) as assigned_to_name,
        //         CONCAT(
        //           cm_cu_.first_name, ' ', cm_cu_.last_name
        //         ) as created_by_name,
        //         `cm_revenue_cycle_status`.`status` as `status_name`,
        //         `cm_account_receivable`.`dob`,
        //         `cm_account_receivable`.`dos`,
        //         `cm_account_receivable`.`billed_amount`,
        //         `cm_account_receivable`.`paid_amount`,
        //         DATE_FORMAT(
        //           cm_account_receivable.created_at,
        //           '%m/%d/%Y'
        //         ) AS entered_date,
        //         `cm_revenue_cycle_remarks`.`remarks`,
        //         `cm_account_receivable`.`id`,
        //         `cm_account_receivable`.`patient_name`,
        //         `cm_account_receivable`.`created_at`,
        //         `cm_payers`.`timely_filling_limit`,
        //         DATEDIFF(
        //           CURDATE(),
        //           cm_account_receivable.dos
        //         ) as aging_days,
        //         (
        //           CASE WHEN DATEDIFF(
        //             CURDATE(),
        //             cm_account_receivable.dos
        //           ) > cm_payers.timely_filling_limit THEN 'Expired' WHEN (
        //             DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) > 60  && DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) <= cm_payers.timely_filling_limit
        //           ) THEN 'Expiring Soon'
        //           WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60
        //             THEN 'Under 60s'
        //           ELSE 'None' END
        //         ) AS aging_status,
        //         `cm_account_receivable`.`practice_id`,
        //         `cm_account_receivable`.`facility_id`,
        //         `cm_account_receivable`.`shelter_id`,
        //         `cm_account_receivable`.`payer_id`,
        //         `cm_account_receivable`.`assigned_to`,
        //         `cm_account_receivable`.`status`,
        //         `cm_account_receivable`.`eob_number`,
        //         `cm_account_receivable`.`eob_date`,
        //         `cm_account_receivable`.`amount`,
        //         `cm_account_receivable`.`closed_remarks`,
        //         cm_account_receivable.last_followup_date,
        //         `cm_account_receivable`.`remarks` as `remarks_id`,
        //         `cm_account_receivable`.`status` as `status_id`,
        //         `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
        //         `cm_account_receivable`.`next_follow_up`,
        //         `cm_account_receivable`.`paid_date`,
        //         `cm_shelters`.`name` as `shelter_name`

        //       from
        //         `cm_account_receivable`
        //         -- left join `$tbl` on `$tbl`.`user_id` = `cm_account_receivable`.`practice_id`
        //         -- left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `cm_account_receivable`.`facility_id`
        //         inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '$sessionUserId'
        //         left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //         left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //         left join `cm_shelter_facility_map` on (`cm_shelter_facility_map`.`shelter_id`=`cm_account_receivable`.`shelter_id` and
        //                                                 `cm_shelter_facility_map`.`facility_id`=`cm_account_receivable`.`facility_id`)
        //         left join `cm_shelters` on `cm_shelters`.`id`=`cm_shelter_facility_map`.`shelter_id`
        //         left join `cm_payers` on `cm_payers`.`id` = `cm_account_receivable`.`payer_id`
        //         left join `$tblU` as `cm_cu` on `cm_cu`.`id` = `cm_account_receivable`.`assigned_to`
        //         left join `$tblU` as `cm_cu_` on `cm_cu_`.`id` = `cm_account_receivable`.`created_by`
        //         left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //         left join `cm_revenue_cycle_remarks` on `cm_revenue_cycle_remarks`.`id` = `cm_account_receivable`.`remarks`
        //         inner join `$tblU` AS `cm_u_practice` ON `cm_u_practice`.`id`= `cm_account_receivable`.`practice_id` AND `cm_u_practice`.`deleted` = 0
        //         inner join `$tblU` AS `cm_u_facility` ON `cm_u_facility`.`id` = `cm_account_receivable`.`facility_id` AND `cm_u_facility`.`deleted` = 0

        //       WHERE
        //         `cm_account_receivable`.`is_delete` = 0 $andCluas

        //     ) AS T
        //     WHERE T.aging_days >= 31 AND T.aging_days <= 60
        //   ";

        //     if (strlen($practiceIds)) {
        //         $sql2 .= " AND T.practice_id IN($practiceIds)";
        //     }
        //     if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
        //         $sql2 .= " AND T.facility_id IN($facilityIds)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
        //         $sql2 .= " AND T.shelter_id IN($shelterIdsStr)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds)) {
        //         $sql2 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        //     }
        //     if (strlen($payerIds)) {
        //         $sql2 .= " AND T.payer_id IN($payerIds)";
        //     }
        //     if (strlen($statusIds)) {
        //         $sql2 .= " AND T.status_id IN($statusIds)";
        //     }

        //     if (strlen($assignTo) && $hasUnAssigned == true) {
        //         $sql2 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL ) ";
        //     }
        //     if (strlen($assignTo) && $hasUnAssigned == false) {
        //         $sql2 .= " AND T.assigned_to_id IN($assignTo)  ";
        //     }
        //     if (strlen($assignTo) == 0 && $hasUnAssigned == true) {
        //         $sql2 .= " AND T.assigned_to_id IS NULL ";
        //     }

        //     if (count($rangeFilter)) {
        //         if ($rangeFilter['column'] == "dos") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql2 .= " AND T.dos  = '$startDate'";
        //             else
        //                 $sql2 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "dob") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql2 .= " AND T.dob  = '$startDate'";
        //             else
        //                 $sql2 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "last_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql2 .= " AND T.last_followup_date  = '$startDate'";
        //             else
        //                 $sql2 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "entered_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql2 .= " AND DATE(T.created_at)  = '$startDate'";
        //             else
        //                 $sql2 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "next_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql2 .= " AND T.next_follow_up  = '$startDate'";
        //             else
        //                 $sql2 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "paid_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql2 .= " AND T.paid_date  = '$startDate'";
        //             else
        //                 $sql2 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //     }
        //     if (count($buntyFilter)) {
        //         $btq = "(";
        //         if (in_array('1', $buntyFilter)) {
        //             $btq .= "  T.aging_status ='Expired'";
        //             if (count($buntyFilter) > 1)
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('2', $buntyFilter)) {
        //             $btq .= " T.aging_status ='Expiring Soon'";
        //             if (in_array('3', $buntyFilter))
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('3', $buntyFilter)) {
        //             $btq .= "  T.aging_status = 'Under 60s'";
        //         }
        //         $btq .= ")";
        //         $sql2 .= "AND $btq";
        //     }
        //     if (strlen($remarks)) {
        //         $sql2 .= " AND T.remarks_id IN($remarks)";
        //     }
        //     if (strlen($search)) {
        //         $searchDate = date('Y-m-d', strtotime($search));
        //         $sql2 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //         OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //         OR T.remarks LIKE '%$search%'
        //         OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //         OR T.assigned_to_name LIKE '%$search%'
        //         OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //         OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //         OR T.shelter_name LIKE '%$search%'
        //         OR T.status_name LIKE '%$search%'
        //         ";
        //         if ($searchDate != "1970-01-01") {
        //             $sql2 .= " OR T.dob LIKE '$searchDate%'
        //             OR T.dos LIKE '$searchDate%'
        //             OR T.last_followup_date LIKE '$searchDate%'
        //             OR T.next_follow_up LIKE '$searchDate%'
        //             OR T.created_at LIKE '$searchDate%'";
        //         }
        //         $sql2 .= " )";
        //     }


        //     $sql3 = "SELECT
        //     COUNT(T.claim_no) as claims,
        //     SUM(T.paid_amount) as amount,
        //     '61-90' as aging_range
        //   FROM
        //     (
        //       select
        //         `cm_account_receivable`.`claim_no`,
        //         -- AES_DECRYPT($tbl.doing_buisness_as,'$key') as practice_name,
        //         ubp.doing_business_as as practice_name,
        //         `cm_cu`.`first_name`,
        //         AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name,
        //         `cm_payers`.`payer_name`,
        //         CONCAT(
        //           cm_cu.first_name, '
        //   ', cm_cu.last_name
        //         ) as assigned_to_name,
        //         CONCAT(
        //           cm_cu_.first_name, ' ', cm_cu_.last_name
        //         ) as created_by_name,
        //         `cm_revenue_cycle_status`.`status` as `status_name`,
        //         `cm_account_receivable`.`dob`,
        //         `cm_account_receivable`.`dos`,
        //         `cm_account_receivable`.`billed_amount`,
        //         `cm_account_receivable`.`paid_amount`,
        //         DATE_FORMAT(
        //           cm_account_receivable.created_at,
        //           '%m/%d/%Y'
        //         ) AS entered_date,
        //         `cm_revenue_cycle_remarks`.`remarks`,
        //         `cm_account_receivable`.`id`,
        //         `cm_account_receivable`.`patient_name`,
        //         `cm_account_receivable`.`created_at`,
        //         `cm_payers`.`timely_filling_limit`,
        //         DATEDIFF(
        //           CURDATE(),
        //          cm_account_receivable.dos
        //         ) as aging_days,
        //         (
        //           CASE WHEN DATEDIFF(
        //             CURDATE(),
        //             cm_account_receivable.dos
        //           ) > cm_payers.timely_filling_limit THEN 'Expired' WHEN (
        //             DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) > 60 && DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) <= cm_payers.timely_filling_limit
        //           ) THEN 'Expiring Soon'
        //           WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60
        //             THEN 'Under 60s'
        //           ELSE 'None' END
        //         ) AS aging_status,
        //         `cm_account_receivable`.`practice_id`,
        //         `cm_account_receivable`.`facility_id`,
        //         `cm_account_receivable`.`shelter_id`,
        //         `cm_account_receivable`.`payer_id`,
        //         `cm_account_receivable`.`assigned_to`,
        //         `cm_account_receivable`.`status`,
        //         `cm_account_receivable`.`eob_number`,
        //         `cm_account_receivable`.`eob_date`,
        //         `cm_account_receivable`.`amount`,
        //         `cm_account_receivable`.`closed_remarks`,

        //         cm_account_receivable.last_followup_date,

        //         `cm_account_receivable`.`remarks` as `remarks_id`,
        //         `cm_account_receivable`.`status` as `status_id`,
        //         `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
        //         `cm_account_receivable`.`next_follow_up`,
        //         `cm_account_receivable`.`paid_date`,
        //         `cm_shelters`.`name` as `shelter_name`
        //       from
        //         `cm_account_receivable`
        //         -- left join `$tbl` on `$tbl`.`user_id` = `cm_account_receivable`.`practice_id`
        //         -- left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `cm_account_receivable`.`facility_id`
        //         inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '$sessionUserId'
        //         left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //         left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //         left join `cm_shelter_facility_map` on (`cm_shelter_facility_map`.`shelter_id`=`cm_account_receivable`.`shelter_id` and
        //                                                 `cm_shelter_facility_map`.`facility_id`=`cm_account_receivable`.`facility_id`)
        //         left join `cm_shelters` on `cm_shelters`.`id`=`cm_shelter_facility_map`.`shelter_id`
        //         left join `cm_payers` on `cm_payers`.`id` = `cm_account_receivable`.`payer_id`
        //         left join `$tblU` as `cm_cu` on `cm_cu`.`id` = `cm_account_receivable`.`assigned_to`
        //         left join `$tblU` as `cm_cu_` on `cm_cu_`.`id` = `cm_account_receivable`.`created_by`
        //         left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //         left join `cm_revenue_cycle_remarks` on `cm_revenue_cycle_remarks`.`id` = `cm_account_receivable`.`remarks`
        //         inner join `$tblU` AS `cm_u_practice` ON `cm_u_practice`.`id`= `cm_account_receivable`.`practice_id` AND `cm_u_practice`.`deleted` = 0
        //         inner join `$tblU` AS `cm_u_facility` ON `cm_u_facility`.`id` = `cm_account_receivable`.`facility_id` AND `cm_u_facility`.`deleted` = 0

        //       WHERE
        //         `cm_account_receivable`.`is_delete` = 0 $andCluas

        //     ) AS T
        //     WHERE T.aging_days >= 61 AND T.aging_days <= 90
        //   ";

        //     if (strlen($practiceIds)) {
        //         $sql3 .= " AND T.practice_id IN($practiceIds)";
        //     }
        //     if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
        //         $sql3 .= " AND T.facility_id IN($facilityIds)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
        //         $sql3 .= " AND T.shelter_id IN($shelterIdsStr)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds)) {
        //         $sql3 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        //     }
        //     if (strlen($payerIds)) {
        //         $sql3 .= " AND T.payer_id IN($payerIds)";
        //     }
        //     if (strlen($statusIds)) {
        //         $sql3 .= " AND T.status_id IN($statusIds)";
        //     }

        //     if (strlen($assignTo) && $hasUnAssigned == true) {
        //         $sql3 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL ) ";
        //     }
        //     if (strlen($assignTo) && $hasUnAssigned == false) {
        //         $sql3 .= " AND T.assigned_to_id IN($assignTo)  ";
        //     }
        //     if (strlen($assignTo) == 0 && $hasUnAssigned == true) {
        //         $sql3 .= " AND T.assigned_to_id IS NULL ";
        //     }

        //     if (count($rangeFilter)) {
        //         if ($rangeFilter['column'] == "dos") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql3 .= " AND T.dos  = '$startDate'";
        //             else
        //                 $sql3 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "dob") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql3 .= " AND T.dob  = '$startDate'";
        //             else
        //                 $sql3 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "last_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql3 .= " AND T.last_followup_date  = '$startDate'";
        //             else
        //                 $sql3 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "entered_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql3 .= " AND DATE(T.created_at)  = '$startDate'";
        //             else
        //                 $sql3 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "next_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql3 .= " AND T.next_follow_up  = '$startDate'";
        //             else
        //                 $sql3 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "paid_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql3 .= " AND T.paid_date  = '$startDate'";
        //             else
        //                 $sql3 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //     }
        //     if (count($buntyFilter)) {
        //         $btq = "(";
        //         if (in_array('1', $buntyFilter)) {
        //             $btq .= "  T.aging_status ='Expired'";
        //             if (count($buntyFilter) > 1)
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('2', $buntyFilter)) {
        //             $btq .= " T.aging_status ='Expiring Soon'";
        //             if (in_array('3', $buntyFilter))
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('3', $buntyFilter)) {
        //             $btq .= "  T.aging_status = 'Under 60s'";
        //         }
        //         $btq .= ")";
        //         $sql3 .= "AND $btq";
        //     }
        //     if (strlen($remarks)) {
        //         $sql3 .= " AND T.remarks_id IN($remarks)";
        //     }
        //     if (strlen($search)) {
        //         $searchDate = date('Y-m-d', strtotime($search));
        //         $sql3 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //         OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //         OR T.remarks LIKE '%$search%'
        //         OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //         OR T.assigned_to_name LIKE '%$search%'
        //         OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //         OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //         OR T.shelter_name LIKE '%$search%'
        //         OR T.status_name LIKE '%$search%'
        //         ";

        //         if ($searchDate != "1970-01-01") {
        //             $sql3 .= "  OR T.dob LIKE '$searchDate%'
        //             OR T.dos LIKE '$searchDate%'
        //             OR T.last_followup_date LIKE '$searchDate%'
        //             OR T.next_follow_up LIKE '$searchDate%'
        //             OR T.created_at LIKE '$searchDate%'";
        //         }
        //         $sql3 .= " )";
        //     }

        //     $sql4 = "SELECT
        //     COUNT(T.claim_no) as claims,
        //     SUM(T.paid_amount) as amount,
        //     '91-365' as aging_range
        //   FROM
        //     (
        //       select
        //         `cm_account_receivable`.`claim_no`,
        //         -- AES_DECRYPT($tbl.doing_buisness_as,'$key') as practice_name,
        //         ubp.doing_business_as as practice_name,
        //         `cm_cu`.`first_name`,
        //         AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name,
        //         `cm_payers`.`payer_name`,
        //         CONCAT(
        //           cm_cu.first_name, '
        //   ', cm_cu.last_name
        //         ) as assigned_to_name,
        //         CONCAT(
        //           cm_cu_.first_name, ' ', cm_cu_.last_name
        //         ) as created_by_name,
        //         `cm_revenue_cycle_status`.`status` as `status_name`,
        //         `cm_account_receivable`.`dob`,
        //         `cm_account_receivable`.`dos`,
        //         `cm_account_receivable`.`billed_amount`,
        //         `cm_account_receivable`.`paid_amount`,
        //         DATE_FORMAT(
        //           cm_account_receivable.created_at,
        //           '%m/%d/%Y'
        //         ) AS entered_date,
        //         `cm_revenue_cycle_remarks`.`remarks`,
        //         `cm_account_receivable`.`id`,
        //         `cm_account_receivable`.`patient_name`,
        //         `cm_account_receivable`.`created_at`,
        //         `cm_payers`.`timely_filling_limit`,
        //         DATEDIFF(
        //           CURDATE(),
        //           cm_account_receivable.dos
        //         ) as aging_days,
        //         (
        //           CASE WHEN DATEDIFF(
        //             CURDATE(),
        //             cm_account_receivable.dos
        //           ) > cm_payers.timely_filling_limit THEN 'Expired' WHEN (
        //             DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) > 60 && DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) <= cm_payers.timely_filling_limit
        //           ) THEN 'Expiring Soon'
        //           WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60
        //             THEN 'Under 60s'
        //           ELSE 'None' END
        //         ) AS aging_status,
        //         `cm_account_receivable`.`practice_id`,
        //         `cm_account_receivable`.`facility_id`,
        //         `cm_account_receivable`.`shelter_id`,
        //         `cm_account_receivable`.`payer_id`,
        //         `cm_account_receivable`.`assigned_to`,
        //         `cm_account_receivable`.`status`,
        //         `cm_account_receivable`.`eob_number`,
        //         `cm_account_receivable`.`eob_date`,
        //         `cm_account_receivable`.`amount`,
        //         `cm_account_receivable`.`closed_remarks`,
        //         cm_account_receivable.last_followup_date,
        //         `cm_account_receivable`.`remarks` as `remarks_id`,
        //         `cm_account_receivable`.`status` as `status_id`,
        //         `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
        //         `cm_account_receivable`.`next_follow_up`,
        //         `cm_account_receivable`.`paid_date`,
        //         `cm_shelters`.`name` as `shelter_name`
        //       from
        //         `cm_account_receivable`
        //         -- left join `$tbl` on `$tbl`.`user_id` = `cm_account_receivable`.`practice_id`
        //         -- left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `cm_account_receivable`.`facility_id`
        //         inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '$sessionUserId'
        //         left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //         left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //         left join `cm_shelter_facility_map` on (`cm_shelter_facility_map`.`shelter_id`=`cm_account_receivable`.`shelter_id` and
        //                                                 `cm_shelter_facility_map`.`facility_id`=`cm_account_receivable`.`facility_id`)
        //         left join `cm_shelters` on `cm_shelters`.`id`=`cm_shelter_facility_map`.`shelter_id`
        //         left join `cm_payers` on `cm_payers`.`id` = `cm_account_receivable`.`payer_id`
        //         left join `$tblU` as `cm_cu` on `cm_cu`.`id` = `cm_account_receivable`.`assigned_to`
        //         left join `$tblU` as `cm_cu_` on `cm_cu_`.`id` = `cm_account_receivable`.`created_by`
        //         left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //         left join `cm_revenue_cycle_remarks` on `cm_revenue_cycle_remarks`.`id` = `cm_account_receivable`.`remarks`
        //         inner join `$tblU` AS `cm_u_practice` ON `cm_u_practice`.`id`= `cm_account_receivable`.`practice_id` AND `cm_u_practice`.`deleted` = 0
        //         inner join `$tblU` AS `cm_u_facility` ON `cm_u_facility`.`id` = `cm_account_receivable`.`facility_id` AND `cm_u_facility`.`deleted` = 0

        //       WHERE
        //         `cm_account_receivable`.`is_delete` = 0 $andCluas

        //     ) AS T
        //     WHERE T.aging_days >= 91 AND T.aging_days <= 365
        //   ";

        //     if (strlen($practiceIds)) {
        //         $sql4 .= " AND T.practice_id IN($practiceIds)";
        //     }
        //     if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
        //         $sql4 .= " AND T.facility_id IN($facilityIds)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
        //         $sql4 .= " AND T.shelter_id IN($shelterIdsStr)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds)) {
        //         $sql4 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        //     }
        //     if (strlen($payerIds)) {
        //         $sql4 .= " AND T.payer_id IN($payerIds)";
        //     }
        //     if (strlen($statusIds)) {
        //         $sql4 .= " AND T.status_id IN($statusIds)";
        //     }

        //     if (strlen($assignTo) && $hasUnAssigned == true) {
        //         $sql4 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL ) ";
        //     }
        //     if (strlen($assignTo) && $hasUnAssigned == false) {
        //         $sql4 .= " AND T.assigned_to_id IN($assignTo)  ";
        //     }
        //     if (strlen($assignTo) == 0 && $hasUnAssigned == true) {
        //         $sql4 .= " AND T.assigned_to_id IS NULL ";
        //     }

        //     if (count($rangeFilter)) {
        //         if ($rangeFilter['column'] == "dos") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql4 .= " AND T.dos  = '$startDate'";
        //             else
        //                 $sql4 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "dob") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql4 .= " AND T.dob  = '$startDate'";
        //             else
        //                 $sql4 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "last_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql4 .= " AND T.last_followup_date  = '$startDate'";
        //             else
        //                 $sql4 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "entered_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql4 .= " AND DATE(T.created_at)  = '$startDate'";
        //             else
        //                 $sql4 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "next_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql4 .= " AND T.next_follow_up  = '$startDate'";
        //             else
        //                 $sql4 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "paid_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql4 .= " AND T.paid_date  = '$startDate'";
        //             else
        //                 $sql4 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //     }
        //     if (count($buntyFilter)) {
        //         $btq = "(";
        //         if (in_array('1', $buntyFilter)) {
        //             $btq .= "  T.aging_status ='Expired'";
        //             if (count($buntyFilter) > 1)
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('2', $buntyFilter)) {
        //             $btq .= " T.aging_status ='Expiring Soon'";
        //             if (in_array('3', $buntyFilter))
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('3', $buntyFilter)) {
        //             $btq .= "  T.aging_status = 'Under 60s'";
        //         }
        //         $btq .= ")";
        //         $sql4 .= "AND $btq";
        //     }
        //     if (strlen($remarks)) {
        //         $sql4 .= " AND T.remarks_id IN($remarks)";
        //     }
        //     if (strlen($search)) {
        //         $searchDate = date('Y-m-d', strtotime($search));
        //         $sql4 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //         OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //         OR T.remarks LIKE '%$search%'
        //         OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //         OR T.assigned_to_name LIKE '%$search%'
        //         OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //         OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //         OR T.shelter_name LIKE '%$search%'
        //         OR T.status_name LIKE '%$search%'
        //         ";
        //         if ($searchDate != "1970-01-01") {
        //             $sql4 .= " OR T.dob LIKE '$searchDate%'
        //             OR T.dos LIKE '$searchDate%'
        //             OR T.last_followup_date LIKE '$searchDate%'
        //             OR T.next_follow_up LIKE '$searchDate%'
        //             OR T.created_at LIKE '$searchDate%'";
        //         }
        //         $sql4 .= " )";
        //     }

        //     $sql5 = "SELECT
        //     COUNT(T.claim_no) as claims,
        //     SUM(T.paid_amount) as amount,
        //     '365+' as aging_range
        //   FROM
        //     (
        //       select
        //         `cm_account_receivable`.`claim_no`,
        //         -- AES_DECRYPT($tbl.doing_buisness_as,'$key') as `practice_name`,
        //         ubp.doing_business_as as practice_name,
        //         `cm_cu`.`first_name`,
        //         AES_DECRYPT(cm_pli.practice_name,'$key') as `facility_name`,
        //         `cm_payers`.`payer_name`,
        //         CONCAT(
        //           cm_cu.first_name, '
        //   ', cm_cu.last_name
        //         ) as assigned_to_name,
        //         CONCAT(
        //           cm_cu_.first_name, ' ', cm_cu_.last_name
        //         ) as created_by_name,
        //         `cm_revenue_cycle_status`.`status` as `status_name`,
        //         `cm_account_receivable`.`dob`,
        //         `cm_account_receivable`.`dos`,
        //         `cm_account_receivable`.`billed_amount`,
        //         `cm_account_receivable`.`paid_amount`,
        //         DATE_FORMAT(
        //           cm_account_receivable.created_at,
        //           '%m/%d/%Y'
        //         ) AS entered_date,
        //         `cm_revenue_cycle_remarks`.`remarks`,
        //         `cm_account_receivable`.`id`,
        //         `cm_account_receivable`.`patient_name`,
        //         `cm_account_receivable`.`created_at`,
        //         `cm_payers`.`timely_filling_limit`,
        //         DATEDIFF(
        //           CURDATE(),
        //          cm_account_receivable.dos
        //         ) as aging_days,
        //         (
        //           CASE WHEN DATEDIFF(
        //             CURDATE(),
        //             cm_account_receivable.dos
        //           ) > cm_payers.timely_filling_limit THEN 'Expired' WHEN (
        //             DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) > 60 && DATEDIFF(
        //               CURDATE(),
        //               cm_account_receivable.dos
        //             ) <= cm_payers.timely_filling_limit
        //           ) THEN 'Expiring Soon'
        //           WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60
        //             THEN 'Under 60s'
        //           ELSE 'None' END
        //         ) AS aging_status,
        //         `cm_account_receivable`.`practice_id`,
        //         `cm_account_receivable`.`facility_id`,
        //         `cm_account_receivable`.`shelter_id`,
        //         `cm_account_receivable`.`payer_id`,
        //         `cm_account_receivable`.`assigned_to`,
        //         `cm_account_receivable`.`status`,
        //         `cm_account_receivable`.`eob_number`,
        //         `cm_account_receivable`.`eob_date`,
        //         `cm_account_receivable`.`amount`,
        //         `cm_account_receivable`.`closed_remarks`,
        //         cm_account_receivable.last_followup_date,
        //         `cm_account_receivable`.`remarks` as `remarks_id`,
        //         `cm_account_receivable`.`status` as `status_id`,
        //         `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
        //         `cm_account_receivable`.`next_follow_up`,
        //         `cm_account_receivable`.`paid_date`,
        //         `cm_shelters`.`name` as `shelter_name`
        //       from
        //         `cm_account_receivable`
        //         -- left join `$tbl` on `$tbl`.`user_id` = `cm_account_receivable`.`practice_id`
        //         -- left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `cm_account_receivable`.`facility_id`
        //         inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '$sessionUserId'
        //         left join `$tbl` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //         left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //         left join `cm_shelter_facility_map` on (`cm_shelter_facility_map`.`shelter_id`=`cm_account_receivable`.`shelter_id` and
        //                                                 `cm_shelter_facility_map`.`facility_id`=`cm_account_receivable`.`facility_id`)
        //         left join `cm_shelters` on `cm_shelters`.`id`=`cm_shelter_facility_map`.`shelter_id`
        //         left join `cm_payers` on `cm_payers`.`id` = `cm_account_receivable`.`payer_id`
        //         left join `$tblU` as `cm_cu` on `cm_cu`.`id` = `cm_account_receivable`.`assigned_to`
        //         left join `$tblU` as `cm_cu_` on `cm_cu_`.`id` = `cm_account_receivable`.`created_by`
        //         left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //         left join `cm_revenue_cycle_remarks` on `cm_revenue_cycle_remarks`.`id` = `cm_account_receivable`.`remarks`
        //         inner join `$tblU` AS `cm_u_practice` ON `cm_u_practice`.`id`= `cm_account_receivable`.`practice_id` AND `cm_u_practice`.`deleted` = 0
        //         inner join `$tblU` AS `cm_u_facility` ON `cm_u_facility`.`id` = `cm_account_receivable`.`facility_id` AND `cm_u_facility`.`deleted` = 0

        //       WHERE
        //         `cm_account_receivable`.`is_delete` = 0 $andCluas
        //     ) AS T
        //     WHERE T.aging_days >= 365
        //   ";

        //     if (strlen($practiceIds)) {
        //         $sql5 .= " AND T.practice_id IN($practiceIds)";
        //     }
        //     if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
        //         $sql5 .= " AND T.facility_id IN($facilityIds)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
        //         $sql5 .= " AND T.shelter_id IN($shelterIdsStr)";
        //     }
        //     if (strlen($shelterIdsStr) && strlen($facilityIds)) {
        //         $sql5 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        //     }
        //     if (strlen($payerIds)) {
        //         $sql5 .= " AND T.payer_id IN($payerIds)";
        //     }
        //     if (strlen($statusIds)) {
        //         $sql5 .= " AND T.status_id IN($statusIds)";
        //     }

        //     if (strlen($assignTo) && $hasUnAssigned == true) {
        //         $sql5 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL ) ";
        //     }
        //     if (strlen($assignTo) && $hasUnAssigned == false) {
        //         $sql5 .= " AND T.assigned_to_id IN($assignTo)  ";
        //     }
        //     if (strlen($assignTo) == 0 && $hasUnAssigned == true) {
        //         $sql5 .= " AND T.assigned_to_id IS NULL ";
        //     }

        //     if (count($rangeFilter)) {
        //         if ($rangeFilter['column'] == "dos") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql5 .= " AND T.dos  = '$startDate'";
        //             else
        //                 $sql5 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "dob") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

        //             $sql5 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "last_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql5 .= " AND T.last_followup_date  = '$startDate'";
        //             else
        //                 $sql5 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "entered_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql5 .= " AND DATE(T.created_at)  = '$startDate'";
        //             else
        //                 $sql5 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "next_followup_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql5 .= " AND T.next_follow_up  = '$startDate'";
        //             else
        //                 $sql5 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
        //         }
        //         if ($rangeFilter['column'] == "paid_date") {
        //             $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

        //             $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
        //             if ($startDate == $endDate)
        //                 $sql5 .= " AND T.paid_date  = '$startDate'";
        //             else
        //                 $sql5 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
        //         }
        //     }
        //     if (count($buntyFilter)) {
        //         $btq = "(";
        //         if (in_array('1', $buntyFilter)) {
        //             $btq .= "  T.aging_status ='Expired'";
        //             if (count($buntyFilter) > 1)
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('2', $buntyFilter)) {
        //             $btq .= " T.aging_status ='Expiring Soon'";
        //             if (in_array('3', $buntyFilter))
        //                 $btq .= " OR ";
        //         }
        //         if (in_array('3', $buntyFilter)) {
        //             $btq .= "  T.aging_status = 'Under 60s'";
        //         }
        //         $btq .= ")";
        //         $sql5 .= "AND $btq";
        //     }
        //     if (strlen($remarks)) {
        //         $sql5 .= " AND T.remarks_id IN($remarks)";
        //     }
        //     if (strlen($search)) {

        //         $searchDate = date('Y-m-d', strtotime($search));

        //         $sql5 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%' OR T.dob LIKE '%$searchDate%'
        //         OR T.dos LIKE '%$searchDate%' OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //         OR T.remarks LIKE '%$search%'
        //         OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //         OR T.assigned_to_name LIKE '%$search%'
        //         OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //         OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //         OR T.shelter_name LIKE '%$search%'
        //         OR T.status_name LIKE '%$search%'
        //         ";

        //         if ($searchDate != "1970-01-01") {
        //             $sql5 .= " OR T.dob LIKE '$searchDate%'
        //             OR T.dos LIKE '$searchDate%'
        //             OR T.last_followup_date LIKE '$searchDate%'
        //             OR T.next_follow_up LIKE '$searchDate%'
        //             OR T.created_at LIKE '$searchDate%'";
        //         }
        //         $sql5 .= " )";
        //     }

        //     $sql = "(" . $sql1 . ")";
        //     $sql .= "UNION ALL";
        //     $sql .= "(" . $sql2 . ")";
        //     $sql .= "UNION ALL";
        //     $sql .= "(" . $sql3 . ")";
        //     $sql .= "UNION ALL";
        //     $sql .= "(" . $sql4 . ")";
        //     $sql .= "UNION ALL";
        //     $sql .= "(" . $sql5 . ")";

        // echo $sql;
        // exit;
        // return $this->rawQuery($sql);
        $newSql = "
        select

            COUNT(T.claim_no) AS claims,
            SUM(T.paid_amount) AS amount,
            aging_range
        FROM (
            SELECT
                cm_account_receivable.claim_no,
                cm_account_receivable.paid_amount,
                DATEDIFF(CURDATE(), cm_account_receivable.dos) AS aging_days,
                CASE
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 0 AND 30 THEN '0-30'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 31 AND 60 THEN '31-60'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 61 AND 90 THEN '61-90'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 91 AND 365 THEN '91-365'
                    ELSE '365+'
                END AS aging_range,
                `cm_revenue_cycle_status`.`status` AS `status_name`,
                `cm_account_receivable`.`dob`,
                `cm_account_receivable`.`dos`,
                `cm_account_receivable`.`last_followup_date`,
                `cm_account_receivable`.`next_follow_up`,
                `cm_account_receivable`.`paid_date`,
                DATE_FORMAT( cm_account_receivable.created_at, '%m/%d/%Y' ) AS entered_date,
                (
                CASE
                    WHEN DATEDIFF( CURDATE(), cm_account_receivable.dos ) > cm_payers.timely_filling_limit THEN 'Expired'
                    WHEN (DATEDIFF(CURDATE(),cm_account_receivable.dos) > 60 && DATEDIFF(CURDATE(),cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
                    WHEN DATEDIFF(CURDATE(),cm_account_receivable.dos) <= 60 THEN 'Under 60s'
                    ELSE 'None'
                END ) AS aging_status,
                `cm_account_receivable`.`practice_id`,
                `cm_account_receivable`.`facility_id`,
                `cm_account_receivable`.`shelter_id`,
                `cm_account_receivable`.`payer_id`,
                `cm_account_receivable`.`assigned_to`,
                `cm_account_receivable`.`remarks` AS `remarks_id`,
                `cm_account_receivable`.`status` AS `status_id`,
                `cm_account_receivable`.`assigned_to` AS `assigned_to_id`
            FROM
                cm_account_receivable
            LEFT JOIN cm_revenue_cycle_status ON cm_revenue_cycle_status.id = cm_account_receivable.status
            left join `cm_payers` on `cm_payers`.`id`=`cm_account_receivable`.`payer_id`
            WHERE
                cm_account_receivable.is_delete = 0
                AND `cm_account_receivable`.`practice_id` IN ($sessionPracticeIds)
                AND `cm_account_receivable`.`facility_id` IN ($sessionFacilityIds)
        ) AS T
        WHERE  1=1
            ";


        if (strlen($practiceIds)) {
            $newSql .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            $newSql .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            $newSql .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            $newSql .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        }
        if (strlen($payerIds)) {
            $newSql .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {
            $newSql .= " AND T.status_id IN($statusIds)";
        }

        if (strlen($assignTo) && $hasUnAssigned == true) {
            $newSql .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL ) ";
        }
        if (strlen($assignTo) && $hasUnAssigned == false) {
            $newSql .= " AND T.assigned_to_id IN($assignTo)  ";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned == true) {
            $newSql .= " AND T.assigned_to_id IS NULL ";
        }

        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.dos  = '$startDate'";
                else
                    $newSql .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

                $newSql .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.last_followup_date  = '$startDate'";
                else
                    $newSql .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND DATE(T.created_at)  = '$startDate'";
                else
                    $newSql .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.next_follow_up  = '$startDate'";
                else
                    $newSql .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.paid_date  = '$startDate'";
                else
                    $newSql .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
            }
        }
        if (count($buntyFilter)) {
            $btq = "(";
            if (in_array('1', $buntyFilter)) {
                $btq .= "  T.aging_status ='Expired'";
                if (count($buntyFilter) > 1)
                    $btq .= " OR ";
            }
            if (in_array('2', $buntyFilter)) {
                $btq .= " T.aging_status ='Expiring Soon'";
                if (in_array('3', $buntyFilter))
                    $btq .= " OR ";
            }
            if (in_array('3', $buntyFilter)) {
                $btq .= "  T.aging_status = 'Under 60s'";
            }
            $btq .= ")";
            $newSql .= "AND $btq";
        }
        if (strlen($remarks)) {
            $newSql .= " AND T.remarks_id IN($remarks)";
        }

        $newSql .= " GROUP BY aging_range";
        // echo $newSql;
        // exit;
        $result = DB::select($newSql);

        return $result;
    }
    /**
     * Exclude De-Active Facility and practices
     */
    private function excludeInActiveFacilitiesAndPractices($withShelter = 0)
    {
        if ($withShelter == 0) {
            $excludeDeactive = " and (`T`.`practice_id` IN(SELECT u.id FROM `cm_users`
            u INNER JOIN cm_user_role_map urm ON urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' ) and
            `T`.`facility_id` IN(SELECT u.id FROM `cm_users` u INNER JOIN cm_user_role_map urm ON
            urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' ) )";
            return $excludeDeactive;
        } else {
            $excludeDeactive = " and `T`.`practice_id` IN(SELECT u.id FROM `cm_users`
            u INNER JOIN cm_user_role_map urm ON urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0')";
            return $excludeDeactive;
        }
    }
    /**
     * Exclude De-Active Facility and practices
     */
    private function excludeInActiveFacilitiesAndPracticesWithoutAnd()
    {
        $excludeDeactive = " `T`.`practice_id` IN(SELECT u.id FROM `cm_users`
        u INNER JOIN cm_user_role_map urm ON urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' ) and
        `T`.`facility_id` IN(SELECT u.id FROM `cm_users` u INNER JOIN cm_user_role_map urm ON
        urm.user_id=u.id AND urm.role_id='9' WHERE u.deleted='0' ) ";
        return $excludeDeactive;
    }
    /**
     * create the aging ranges stats
     */
    private function getAgingRangesStats()
    {
        $key = $this->key;
        $tbl = "cm_" . $this->tbl;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;

        // $result = DB::table(DB::raw('(SELECT
        //         cm_account_receivable.claim_no,
        //         cm_account_receivable.paid_amount,
        //         DATEDIFF(CURDATE(), cm_account_receivable.dos) AS aging_days,
        //         CASE
        //             WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 0 AND 30 THEN "0-30"
        //             WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 31 AND 60 THEN "31-60"
        //             WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 61 AND 90 THEN "61-90"
        //             WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 91 AND 365 THEN "91-365"
        //             ELSE "365+"
        //         END AS aging_range
        //     FROM
        //         cm_account_receivable
        //     LEFT JOIN cm_revenue_cycle_status ON cm_revenue_cycle_status.id = cm_account_receivable.status
        //     WHERE
        //         cm_account_receivable.is_delete = 0
        //         AND cm_revenue_cycle_status.considered_as_completed = 0) AS T'))
        //     ->groupBy('aging_range')
        //     ->selectRaw('aging_range, COUNT(claim_no) AS claims, SUM(paid_amount) AS amount')
        //     ->get();

        $result = AccountReceivable::select(
            DB::raw('COUNT(claim_no) as claims'),
            DB::raw('SUM(balance_amount) AS amount'),
            DB::raw('SUM(paid_amount) AS paid_amount'),
            DB::raw('
                CASE
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 0 AND 30 THEN "0-30"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 31 AND 60 THEN "31-60"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 61 AND 90 THEN "61-90"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 91 AND 365 THEN "91-365"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 366 AND 10000 THEN "365+"
                END AS aging_range
            '),
        )->whereHas('revenuecyclestatus', function ($query) {
            return $query->where('considered_as_completed', 0)->whereIn('id', [10, 1, 8, 4, 7, 9, 3]);
        })->where('is_delete', 0)
        ->groupBy('aging_range')
        //->orderBy('aging_range')
        ->get();
        // Create a Collection from the array
        $agingStats = collect($result);

        // Order the collection by 'aging_range'
        $sortedAgingStats = $agingStats->sort(function ($a, $b) {
            // Extract the first number of the range and compare them
            $aStart = intval(explode('-', preg_replace('/\D/', '-', $a['aging_range']))[0]);
            $bStart = intval(explode('-', preg_replace('/\D/', '-', $b['aging_range']))[0]);
        
            return $aStart <=> $bStart;
        });
        return $sortedAgingStats->values()->all();
    }
    /**
     * AR practice related stats
     *
     */
    private function getPracticeStatsFilter($practiceIds, $payerIds, $statusIds, $assignTo, $rangeFilter, $buntyFilter, $remarks, $facilityIds, $search, $shelterIdsStr, $hasUnAssigned)
    {
        $key = $this->key;
        $tbl = "cm_" . $this->tbl;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;
        set_time_limit(0);

        $isArchived = 0;
        $arModel = new AccountReceivable();
        $practices = $arModel->activePractices($sessionUserId);
        // now get ids from the practicies...
        $sessionPracticeIds = [];
        foreach ($practices as $practice) {
            $sessionPracticeIds[] = $practice->facility_id;
        }

        // now get facilities by practice ids and user session id in a loop on practice ids...
        $facilities = [];
        foreach ($sessionPracticeIds as $practiceId) {
            $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
        }

        $sessionFacilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $sessionFacilityIds[] = $f->facility_id;
            }
        }

        $sessionFacilityIds = array_unique($sessionFacilityIds);

        $sessionPracticeIds = implode(",", $sessionPracticeIds);
        $sessionFacilityIds = implode(", ", $sessionFacilityIds);

        if (strlen($statusIds)) {
            $andCluas = "";
        } else {
            $andCluas = " AND cm_revenue_cycle_status.considered_as_completed = 0";
        }

        $newSql = "
        SELECT
            COUNT(T.claim_no) as total,
            NULL as practice_name,
            practice_id,
            SUM(T.billed_amount) as amount
        FROM
            (select
                `cm_account_receivable`.`claim_no`,
                `cm_account_receivable`.`dob`,
                `cm_account_receivable`.`dos`,
                `cm_account_receivable`.`billed_amount`,
                DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y') AS entered_date,
                `cm_account_receivable`.`created_at`,
                DATEDIFF(CURDATE(),	STR_TO_DATE(cm_account_receivable.dos, '%m/%d/%Y')) as aging_days,
                (CASE
                    WHEN DATEDIFF(CURDATE(),cm_account_receivable.dos) > cm_payers.timely_filling_limit THEN 'Expired'
                    WHEN (DATEDIFF(	CURDATE(),	cm_account_receivable.dos) > 60 && DATEDIFF(CURDATE(),cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60 THEN 'Under 60s'
                    ELSE 'None' END
                ) AS aging_status,
                `cm_account_receivable`.`practice_id`,
                `cm_account_receivable`.`facility_id`,
                `cm_account_receivable`.`shelter_id`,
                `cm_account_receivable`.`payer_id`,
                `cm_account_receivable`.`assigned_to`,
                `cm_account_receivable`.`last_followup_date`,
                `cm_account_receivable`.`remarks` as `remarks_id`,
                `cm_account_receivable`.`status` as `status_id`,
                `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
                `cm_account_receivable`.`next_follow_up`,
                `cm_account_receivable`.`paid_date`
            from
                `cm_account_receivable`
                left join `cm_payers` on `cm_payers`.`id`=`cm_account_receivable`.`payer_id`
                WHERE
                `cm_account_receivable`.`is_delete`= 0
                AND `cm_account_receivable`.`practice_id` IN ($sessionPracticeIds)
                AND `cm_account_receivable`.`facility_id` IN ($sessionFacilityIds)
            ) AS T
            WHERE 1 = 1

        ";

        if (strlen($practiceIds)) {
            $newSql .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            $newSql .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            $newSql .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            $newSql .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        }
        if (strlen($payerIds)) {
            $newSql .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {
            $newSql .= " AND T.status_id IN($statusIds)";
        }

        if (strlen($assignTo) && $hasUnAssigned == true) {
            $newSql .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL ) ";
        }
        if (strlen($assignTo) && $hasUnAssigned == false) {
            $newSql .= " AND T.assigned_to_id IN($assignTo)  ";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned == true) {
            $newSql .= " AND T.assigned_to_id IS NULL ";
        }

        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.dos  = '$startDate'";
                else
                    $newSql .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

                $newSql .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.last_followup_date  = '$startDate'";
                else
                    $newSql .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND DATE(T.created_at)  = '$startDate'";
                else
                    $newSql .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.next_follow_up  = '$startDate'";
                else
                    $newSql .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $newSql .= " AND T.paid_date  = '$startDate'";
                else
                    $newSql .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
            }
        }
        if (count($buntyFilter)) {
            $btq = "(";
            if (in_array('1', $buntyFilter)) {
                $btq .= "  T.aging_status ='Expired'";
                if (count($buntyFilter) > 1)
                    $btq .= " OR ";
            }
            if (in_array('2', $buntyFilter)) {
                $btq .= " T.aging_status ='Expiring Soon'";
                if (in_array('3', $buntyFilter))
                    $btq .= " OR ";
            }
            if (in_array('3', $buntyFilter)) {
                $btq .= "  T.aging_status = 'Under 60s'";
            }
            $btq .= ")";
            $newSql .= "AND $btq";
        }
        if (strlen($remarks)) {
            $newSql .= " AND T.remarks_id IN($remarks)";
        }

        $newSql = $newSql .= " GROUP BY T.practice_id ";

        $results = DB::select($newSql);

        foreach ($results as $result) {
            $result->practice_name = $arModel->getPracticeNamesById($result->practice_id);
        }
        $results = collect($results)->sortBy('practice_name')->values()->all();

        return $results;
    }
    /**
     * AR practice related stats
     *
     */
    private function getPracticeStats()
    {
        $key = $this->key;
        $tbl = "cm_" . $this->tbl;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;
        $isArchived = 0;
        $arModel = new AccountReceivable();
        $practices = $arModel->activePractices($sessionUserId);
        // now get ids from the practicies...
        $practiceIds = [];
        foreach ($practices as $practice) {
            $practiceIds[] = $practice->facility_id;
        }

        // now get facilities by practice ids and user session id in a loop on practice ids...
        $facilities = [];
        foreach ($practiceIds as $practiceId) {
            $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
        }

        $sessionFacilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $sessionFacilityIds[] = $f->facility_id;
            }
        }

        $sessionFacilityIds = array_unique($sessionFacilityIds);

        $result = AccountReceivable::select([
            DB::raw('COUNT(cm_account_receivable.claim_no) as total'),
            'ubp.doing_business_as as practice_name',
            DB::raw('SUM(cm_account_receivable.balance_amount) as amount'),
            DB::raw('SUM(cm_account_receivable.paid_amount) AS paid_amount'),
        ])
            ->leftJoin('user_baf_practiseinfo as ubp', 'ubp.user_id', '=', 'account_receivable.practice_id')
            ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')
            ->where('account_receivable.is_delete', 0)
            ->where('revenue_cycle_status.considered_as_completed', 0)
            ->whereIn('account_receivable.practice_id', $practiceIds)
            ->whereIn('account_receivable.facility_id', $sessionFacilityIds)
            ->groupBy('ubp.doing_business_as')
            ->whereIn('account_receivable.status', [10, 1, 8, 4, 7, 9, 3])
            ->get();

        return $result;
    }
    /**
     * AR assigned user related stats filter
     *
     */
    private function getAssignedUserStatsFilter($practiceIds, $payerIds, $statusIds, $assignTo, $rangeFilter, $buntyFilter, $remarks, $facilityIds, $search, $shelterIdsStr, $hasUnAssigned)
    {
        $key = $this->key;
        $tbl = "cm_" . $this->tbl;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;
        set_time_limit(0);

        $isArchived = 0;
        $arModel = new AccountReceivable();
        $practices = $arModel->activePractices($sessionUserId);
        // now get ids from the practicies...
        $sessionPracticeIds = [];
        foreach ($practices as $practice) {
            $sessionPracticeIds[] = $practice->facility_id;
        }

        // now get facilities by practice ids and user session id in a loop on practice ids...
        $facilities = [];
        foreach ($sessionPracticeIds as $practiceId) {
            $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
        }

        $sessionFacilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $sessionFacilityIds[] = $f->facility_id;
            }
        }

        $sessionFacilityIds = array_unique($sessionFacilityIds);
        $sessionPracticeIds = implode(',', $sessionPracticeIds);
        $sessionFacilityIds = implode(', ', $sessionFacilityIds);
        // if (strlen($statusIds))
        //     $andCluas = "";
        // else
        //     $andCluas = "";

        $sql = "SELECT
        if(T.assigned_to_name IS NULL, 'Unassigned', T.assigned_to_name) as assinged_to_me,
        -- (SELECT COUNT(claim_no) FROM `cm_account_receivable` WHERE assigned_to = T.assigned_to) as total,
        'NULL' as total,
        SUM(CASE WHEN T.status_name = 'Paid' THEN 1 ELSE 0 END) AS paid_claims,
        SUM(CASE WHEN T.status_name = 'BALANCE DUE PATIENT' THEN 1 ELSE 0 END) AS balance_due_patient_claims,
        SUM(T.paid_amount) as amount,
        id as user_id
      FROM
        (
          select
            `cm_account_receivable`.`claim_no`,
            `cm_cu`.`id`,
            -- -- AES_DECRYPT(`$tbl`.`doing_buisness_as`,'$key') as `practice_name`,
            -- ubp.doing_business_as as practice_name,
            -- `cm_cu`.`first_name`,
            -- AES_DECRYPT(`cm_pli`.`practice_name`,'$key') as `facility_name`,
            -- `cm_payers`.`payer_name`,
            CONCAT(cm_cu.first_name, '  ', cm_cu.last_name) as assigned_to_name,
            -- CONCAT( cm_cu_.first_name, ' ', cm_cu_.last_name   ) as created_by_name,
            `cm_revenue_cycle_status`.`status` as `status_name`,
            `cm_account_receivable`.`dob`,
            `cm_account_receivable`.`dos`,
            -- `cm_account_receivable`.`billed_amount`,
            `cm_account_receivable`.`paid_amount`,
            DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y' ) AS entered_date,
            -- `cm_revenue_cycle_remarks`.`remarks`,
            -- `cm_account_receivable`.`id`,
            -- `cm_account_receivable`.`patient_name`,
            `cm_account_receivable`.`created_at`,
            -- `cm_payers`.`timely_filling_limit`,
            -- DATEDIFF(CURDATE(), STR_TO_DATE(cm_account_receivable.dos, '%m/%d/%Y')) as aging_days,
            (CASE WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos ) > cm_payers.timely_filling_limit THEN 'Expired'
            WHEN ( DATEDIFF( CURDATE(), cm_account_receivable.dos ) > 60 && DATEDIFF( CURDATE(), cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
            WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60 THEN 'Under 60s' ELSE 'None' END) AS aging_status,
            `cm_account_receivable`.`practice_id`,
            `cm_account_receivable`.`facility_id`,
            `cm_account_receivable`.`shelter_id`,
            `cm_account_receivable`.`payer_id`,
            `cm_account_receivable`.`assigned_to`,
            -- `cm_account_receivable`.`status`,
            -- `cm_account_receivable`.`eob_number`,
            -- `cm_account_receivable`.`eob_date`,
            -- `cm_account_receivable`.`amount`,
            -- `cm_account_receivable`.`closed_remarks`,
            cm_account_receivable.last_followup_date,
            `cm_account_receivable`.`remarks` as `remarks_id`,
            `cm_account_receivable`.`status` as `status_id`,
            `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
            `cm_account_receivable`.`next_follow_up`,
            `cm_account_receivable`.`paid_date`
            -- `cm_shelters`.`name` as `shelter_name`
          from
            `cm_account_receivable`

            left join `cm_payers` on `cm_payers`.`id` = `cm_account_receivable`.`payer_id`
            left join `$tblU` as `cm_cu` on `cm_cu`.`id` = `cm_account_receivable`.`assigned_to`

            left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`


          WHERE
            `cm_account_receivable`.`is_delete` = 0
            AND (`cm_revenue_cycle_status`.`status` = 'Paid' OR `cm_revenue_cycle_status`.`status` = 'BALANCE DUE PATIENT')
            AND `cm_account_receivable`.`practice_id` IN($sessionPracticeIds)
            AND `cm_account_receivable`.`facility_id` IN($sessionFacilityIds)
        ) AS T WHERE
        ";
        $filter = "";
        if (strlen($practiceIds)) {
            if ($filter == "")
                $filter .= "  T.practice_id IN($practiceIds)";
            else
                $filter .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            if ($filter == "")
                $filter .= "   T.facility_id IN($facilityIds)";
            else
                $filter .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            if ($filter == "")
                $filter .= "  T.shelter_id IN($shelterIdsStr)";
            else
                $filter .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            if ($filter == "")
                $filter .= " T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds)";
            else
                $filter .= " AND T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds)";
        }
        if (strlen($payerIds)) {
            if ($filter == "")
                $filter .= "   T.payer_id IN($payerIds)";
            else
                $filter .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {
            if ($filter == "")
                $filter .= "  T.status_id IN($statusIds)";
            else
                $filter .= " AND T.status_id IN($statusIds)";
        }
        // if (strlen($assignTo)) {
        //     if ($filter == "")
        //         $filter .= "   T.assigned_to_id IN($assignTo)";
        //     else
        //         $filter .= " AND T.assigned_to_id IN($assignTo)";
        // }
        if (strlen($assignTo) > 0 && $hasUnAssigned ==  true) {
            if ($filter == "")
                $filter .= "  (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL )";
            else
                $filter .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL )";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned ==  true) {
            if ($filter == "")
                $filter .= "  T.assigned_to_id IS NULL ";
            else
                $filter .= " AND  T.assigned_to_id IS NULL ";
        }
        if (strlen($assignTo) > 0 && $hasUnAssigned ==  false) {
            if ($filter == "")
                $filter .= "  T.assigned_to_id IN($assignTo) ";
            else
                $filter .= " AND T.assigned_to_id IN($assignTo) ";
        }

        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($filter == "") {
                    if ($startDate == $endDate)
                        $filter .= "   T.dos =  '$startDate'";
                    else
                        $filter .= "   T.dos BETWEEN '$startDate' AND '$endDate'";
                } else {
                    if ($startDate == $endDate)
                        $filter .= " AND T.dos =  '$startDate'";
                    else
                        $filter .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
                }
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));


                if ($filter == "") {
                    if ($startDate == $endDate)
                        $filter .= "   T.dob =  '$startDate'";
                    else
                        $filter .= "   T.dob BETWEEN '$startDate' AND '$endDate'";
                } else {
                    if ($startDate == $endDate)
                        $filter .= " AND T.dob =  '$startDate'";
                    else
                        $filter .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
                }
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

                if ($filter == "")
                    $filter .= "   T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
                else
                    $filter .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

                if ($filter == "") {
                    if ($startDate == $endDate)
                        $filter .= "   DATE(T.created_at) =  '$startDate'";
                    else
                        $filter .= "   T.created_at BETWEEN '$startDate' AND '$endDate'";
                } else {
                    if ($startDate == $endDate)
                        $filter .= " AND DATE(T.created_at) =  '$startDate'";
                    else
                        $filter .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
                }
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

                if ($filter == "") {
                    if ($startDate == $endDate)
                        $filter .= "   T.next_follow_up =  '$startDate'";
                    else
                        $filter .= "   T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
                } else {
                    if ($startDate == $endDate)
                        $filter .= " AND T.next_follow_up =  '$startDate'";
                    else
                        $filter .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
                }
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));

                if ($filter == "") {
                    if ($startDate == $endDate)
                        $filter .= "   T.paid_date =  '$startDate'";
                    else
                        $filter .= "   T.paid_date BETWEEN '$startDate' AND '$endDate'";
                } else {
                    if ($startDate == $endDate)
                        $filter .= " AND T.paid_date =  '$startDate'";
                    else
                        $filter .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
                }
            }
        }
        if (count($buntyFilter)) {
            $andOperator = "";
            $bfsq = "(";
            if (in_array('1', $buntyFilter)) {

                if ($filter == "") {
                    $bfsq .= "  T.aging_status ='Expired'";
                    if (count($buntyFilter) > 1)
                        $bfsq .= " OR";
                } else {
                    $andOperator = " AND ";
                    $bfsq .= "  T.aging_status ='Expired'";
                    if (count($buntyFilter) > 1)
                        $bfsq .= " OR";
                }
            }
            if (in_array('2', $buntyFilter)) {

                if ($filter == "") {
                    $bfsq .= "  T.aging_status ='Expiring Soon'";
                    if (in_array('3', $buntyFilter))
                        $bfsq .= " OR";
                } else {
                    $andOperator = " AND ";
                    $bfsq .= "  T.aging_status ='Expiring Soon'";
                    if (in_array('3', $buntyFilter))
                        $bfsq .= " OR";
                }
            }
            if (in_array('3', $buntyFilter)) {

                if ($filter == "")
                    $bfsq .= "  T.aging_status ='Under 60s'";
                else {
                    $bfsq .= "  T.aging_status ='Under 60s'";
                    $andOperator = " AND ";
                }
            }
            $bfsq .= ")";
            $filter .= $andOperator . $bfsq;
        }
        if (strlen($remarks)) {

            if ($filter == "")
                $filter .= "   T.remarks_id IN($remarks)";
            else
                $filter .= " AND T.remarks_id IN($remarks)";
        }
        // if (strlen($search)) {
        //     $searchDate = date('Y-m-d', strtotime($search));
        //     if ($filter == "") {
        //         $filter .= "   (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //         OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //         OR T.remarks LIKE '%$search%'
        //         OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //         OR T.assigned_to_name LIKE '%$search%'
        //         OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //         OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //         OR T.shelter_name LIKE '%$search%'
        //         OR T.status_name LIKE '%$search%'
        //        ";
        //         if ($searchDate != "1970-01-01") {
        //             $filter .= " OR T.dob LIKE '$searchDate%'
        //             OR T.dos LIKE '$searchDate%'
        //             OR T.last_followup_date LIKE '$searchDate%'
        //             OR T.next_follow_up LIKE '$searchDate%'
        //             OR T.created_at LIKE '$searchDate%'
        //         ";
        //         }
        //         $filter .= " )";
        //     } else {
        //         $filter .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //             OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //             OR T.remarks LIKE '%$search%'
        //             OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //             OR T.assigned_to_name LIKE '%$search%'
        //             OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //             OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //             OR T.shelter_name LIKE '%$search%'
        //         ";
        //         if ($searchDate != "1970-01-01") {
        //             $filter .= " OR T.dob LIKE '$searchDate%'
        //             OR T.dos LIKE '$searchDate%'
        //             OR T.last_followup_date LIKE '$searchDate%'
        //             OR T.next_follow_up LIKE '$searchDate%'
        //             OR T.created_at LIKE '$searchDate%'
        //         ";
        //         }
        //         $filter .= " )";
        //     }
        // }
        $sql = $sql . $filter . " GROUP BY T.assigned_to_name ORDER BY T.assigned_to_name";

        $results = DB::select($sql);
        foreach ($results as $result) {
            if ($result->user_id) {
                $result->total = $arModel->getClaimCountByUserId($result->user_id);
            }
        }

        return $results;
        // echo json_encode($result);
        // exit;



        // echo $sql;
        // exit;
        // return $this->rawQuery($sql);
    }
    /**
     * AR assigned user related stats
     *
     */
    private function getAssignedUserStats()
    {

        $sessionUserId = $this->sessionUserId;

        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $practiceIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];
        $currentMonth = date('m');
        $results = AccountReceivable::selectRaw('
                COUNT(cm_account_receivable.claim_no) as total,
                COALESCE(CONCAT(cm_users.first_name, " ", cm_users.last_name), "Unassigned") as assinged_to_me,
                SUM(cm_account_receivable.paid_amount) as amount,
                cm_account_receivable.assigned_to,
                SUM(CASE WHEN cm_revenue_cycle_status.status = "Paid" THEN 1 ELSE 0 END) AS paid_claims,
                SUM(CASE WHEN cm_revenue_cycle_status.status = "BALANCE DUE PATIENT" THEN 1 ELSE 0 END) AS balance_due_patient_claims
            ')
            ->leftJoin('users', 'users.id', '=', 'account_receivable.assigned_to')
            ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')
            ->where('account_receivable.is_delete', 0)
            ->where('revenue_cycle_status.status', '!=', 'Deleted')
            ->whereIn('account_receivable.practice_id', $practiceIds)
            ->whereIn('account_receivable.facility_id', $sessionFacilityIds)
            ->groupBy('assinged_to_me')
            ->whereMonth('account_receivable.created_at', $currentMonth)
            ->get();

        return $results;
    }
    /**
     * AR timely reports filter
     *
     */
    private function getTimelyReportsFilter($practiceIds, $payerIds, $statusIds, $assignTo, $rangeFilter, $buntyFilter, $remarks, $facilityIds, $search, $shelterIdsStr, $hasUnAssigned)
    {
        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;
        set_time_limit(0);

        $isArchived = 0;
        $arModel = new AccountReceivable();
        $practices = $arModel->activePractices($sessionUserId);
        // now get ids from the practicies...
        $sessionPracticeIds = [];
        foreach ($practices as $practice) {
            $sessionPracticeIds[] = $practice->facility_id;
        }

        // now get facilities by practice ids and user session id in a loop on practice ids...
        $facilities = [];
        foreach ($sessionPracticeIds as $practiceId) {
            $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
        }

        $sessionFacilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $sessionFacilityIds[] = $f->facility_id;
            }
        }

        $sessionFacilityIds = array_unique($sessionFacilityIds);

        $sessionPracticeIds = implode(",", $sessionPracticeIds);
        $sessionFacilityIds = implode(", ", $sessionFacilityIds);

        if (strlen($statusIds))
            $andCluas = "";
        else
            $andCluas = "";

        $sql1 = "SELECT
                'Today' as entity,
                COUNT(T.claim_no) as claims,
                SUM(T.paid_amount) as collections,
                (SUM(T.paid_amount)/ COUNT(T.claim_no)) as average
            FROM
                (
                select
                    CURDATE() as curdate,
                    `cm_account_receivable`.`claim_no`,
                    `cm_account_receivable`.`dob`,
                    `cm_account_receivable`.`dos`,
                    `cm_account_receivable`.`billed_amount`,
                    `cm_account_receivable`.`paid_amount`,
                    DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y'	) AS entered_date,
                    `cm_account_receivable`.`created_at`,
                    DATEDIFF(CURDATE(),	cm_account_receivable.dos) as aging_days,
                    (CASE
                    WHEN DATEDIFF(CURDATE(),	cm_account_receivable.dos) > cm_payers.timely_filling_limit THEN 'Expired'
                    WHEN (DATEDIFF(CURDATE(),	cm_account_receivable.dos) > 60 && DATEDIFF(CURDATE(),cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60  THEN 'Under 60s'
                    ELSE 'None'
                    END ) AS aging_status,
                    `cm_account_receivable`.`practice_id`,
                    `cm_account_receivable`.`facility_id`,
                    `cm_account_receivable`.`shelter_id`,
                    `cm_account_receivable`.`payer_id`,
                    `cm_account_receivable`.`assigned_to`,
                    `cm_account_receivable`.`last_followup_date`,
                    `cm_account_receivable`.`remarks` as `remarks_id`,
                    `cm_account_receivable`.`status` as `status_id`,
                    `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
                    `cm_account_receivable`.`next_follow_up`,
                    `cm_account_receivable`.`paid_date`
                from
                `cm_account_receivable`
                left join `cm_payers` on `cm_payers`.`id`=`cm_account_receivable`.`payer_id`
                left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id`=`cm_account_receivable`.`status`

                WHERE
                    `cm_account_receivable`.`is_delete` = 0 $andCluas
                    AND `cm_account_receivable`.`practice_id` IN ($sessionPracticeIds)
                    AND `cm_account_receivable`.`facility_id` IN ($sessionFacilityIds)
                    AND `cm_revenue_cycle_status`.`status` = 'Paid'
                ) AS T
            WHERE
                T.last_followup_date BETWEEN T.curdate
                AND T.curdate
            ";

        if (strlen($practiceIds)) {
            $sql1 .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            $sql1 .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            $sql1 .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            $sql1 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        }
        if (strlen($payerIds)) {
            $sql1 .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {
            $sql1 .= " AND T.status_id IN($statusIds)";
        }

        if (strlen($assignTo) > 0 && $hasUnAssigned ==  true) {
            $sql1 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL )";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned ==  true) {
            $sql1 .= " AND  T.assigned_to_id IS NULL ";
        }
        if (strlen($assignTo) > 0 && $hasUnAssigned ==  false) {
            $sql1 .= " AND T.assigned_to_id IN($assignTo) ";
        }

        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql1 .= " AND T.dos = '$startDate'";
                else
                    $sql1 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql1 .= " AND T.dob = '$startDate'";
                else
                    $sql1 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql1 .= " AND T.last_followup_date = '$startDate'";
                else
                    $sql1 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql1 .= " AND DATE(T.created_at) = '$startDate'";
                else
                    $sql1 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql1 .= " AND T.next_follow_up = '$startDate'";
                else
                    $sql1 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql1 .= " AND T.paid_date = '$startDate'";
                else
                    $sql1 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
            }
        }
        if (count($buntyFilter)) {
            $btq = "(";
            if (in_array('1', $buntyFilter)) {
                $btq .= "  T.aging_status ='Expired'";
                if (count($buntyFilter) > 1)
                    $btq .= " OR ";
            }
            if (in_array('2', $buntyFilter)) {
                $btq .= " T.aging_status ='Expiring Soon'";
                if (in_array('3', $buntyFilter))
                    $btq .= " OR ";
            }
            if (in_array('3', $buntyFilter)) {
                $btq .= "  T.aging_status = 'Under 60s'";
            }
            $btq .= ")";
            $sql1 .= "AND $btq";
        }
        if (strlen($remarks)) {
            $sql1 .= " AND T.remarks_id IN($remarks)";
        }
        // if (strlen($search)) {
        //     $searchDate = date('Y-m-d', strtotime($search));

        //     $sql1 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //     OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //     OR T.remarks LIKE '%$search%'
        //     OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //     OR T.assigned_to_name LIKE '%$search%'
        //     OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //     OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //     OR T.shelter_name LIKE '%$search%'
        //     OR T.status_name LIKE '%$search%'
        //     ";
        //     if ($searchDate == "1970-01-01") {
        //         $sql1 .= "  OR T.dob LIKE '$searchDate%'
        //         OR T.dos LIKE '$searchDate%'
        //         OR T.last_followup_date LIKE '$searchDate%'
        //         OR T.next_follow_up LIKE '$searchDate%'
        //         OR T.created_at LIKE '$searchDate%'
        //         ";
        //     }
        //     $sql1 .= " )";
        // }




        $sql2 = "SELECT
            'WTD' as entity,
            COUNT(T.claim_no) as claims,
            SUM(T.paid_amount) as collections,
            (
                SUM(T.paid_amount)/ COUNT(T.claim_no)
            ) as average
            FROM
            (
                select
                    SUBDATE( CURDATE(), WEEKDAY( CURDATE())) as from_date,
                    CURDATE() as to_date,
                    `cm_account_receivable`.`claim_no`,
                    `cm_account_receivable`.`dob`,
                    `cm_account_receivable`.`dos`,
                    `cm_account_receivable`.`billed_amount`,
                    `cm_account_receivable`.`paid_amount`,
                    DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y'	) AS entered_date,
                    `cm_account_receivable`.`created_at`,
                    DATEDIFF(CURDATE(),	cm_account_receivable.dos) as aging_days,
                    (CASE
                    WHEN DATEDIFF(CURDATE(),	cm_account_receivable.dos) > cm_payers.timely_filling_limit THEN 'Expired'
                    WHEN (DATEDIFF(CURDATE(),	cm_account_receivable.dos) > 60 && DATEDIFF(CURDATE(),cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60  THEN 'Under 60s'
                    ELSE 'None'
                    END ) AS aging_status,
                    `cm_account_receivable`.`practice_id`,
                    `cm_account_receivable`.`facility_id`,
                    `cm_account_receivable`.`shelter_id`,
                    `cm_account_receivable`.`payer_id`,
                    `cm_account_receivable`.`assigned_to`,
                    `cm_account_receivable`.`last_followup_date`,
                    `cm_account_receivable`.`remarks` as `remarks_id`,
                    `cm_account_receivable`.`status` as `status_id`,
                    `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
                    `cm_account_receivable`.`next_follow_up`,
                    `cm_account_receivable`.`paid_date`
                from
                `cm_account_receivable`
                left join `cm_payers` on `cm_payers`.`id`=`cm_account_receivable`.`payer_id`
                left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id`=`cm_account_receivable`.`status`

                WHERE
                    `cm_account_receivable`.`is_delete` = 0 $andCluas
                    AND `cm_account_receivable`.`practice_id` IN ($sessionPracticeIds)
                    AND `cm_account_receivable`.`facility_id` IN ($sessionFacilityIds)
                    AND `cm_revenue_cycle_status`.`status` = 'Paid'
            ) AS T
            WHERE
            T.last_followup_date BETWEEN T.from_date
            AND T.to_date
            ";
        if (strlen($practiceIds)) {
            $sql2 .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            $sql2 .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            $sql2 .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            $sql2 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        }
        if (strlen($payerIds)) {
            $sql2 .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {
            $sql2 .= " AND T.status_id IN($statusIds)";
        }


        if (strlen($assignTo) > 0 && $hasUnAssigned ==  true) {
            $sql2 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL )";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned ==  true) {
            $sql2 .= " AND  T.assigned_to_id IS NULL ";
        }
        if (strlen($assignTo) > 0 && $hasUnAssigned ==  false) {
            $sql2 .= " AND T.assigned_to_id IN($assignTo) ";
        }

        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql2 .= " AND T.dos = '$startDate'";
                else
                    $sql2 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql2 .= " AND T.dob = '$startDate'";
                else
                    $sql2 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql2 .= " AND T.last_followup_date = '$startDate'";
                else
                    $sql2 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql2 .= " AND T.next_follow_up = '$startDate'";
                else
                    $sql2 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql2 .= " AND DATE(T.created_at) = '$startDate'";
                else
                    $sql2 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql2 .= " AND T.paid_date = '$startDate'";
                else
                    $sql2 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
            }
        }
        if (count($buntyFilter)) {
            $btq = "(";
            if (in_array('1', $buntyFilter)) {
                $btq .= "  T.aging_status ='Expired'";
                if (count($buntyFilter) > 1)
                    $btq .= " OR ";
            }
            if (in_array('2', $buntyFilter)) {
                $btq .= " T.aging_status ='Expiring Soon'";
                if (in_array('3', $buntyFilter))
                    $btq .= " OR ";
            }
            if (in_array('3', $buntyFilter)) {
                $btq .= "  T.aging_status = 'Under 60s'";
            }
            $btq .= ")";
            $sql2 .= "AND $btq";
        }
        if (strlen($remarks)) {
            $sql2 .= " AND T.remarks_id IN($remarks)";
        }
        // if (strlen($search)) {
        //     $searchDate = date('Y-m-d', strtotime($search));
        //     $sql2 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //         OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //         OR T.remarks LIKE '%$search%'
        //         OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //         OR T.assigned_to_name LIKE '%$search%'
        //         OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //         OR  AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //         OR T.shelter_name LIKE '%$search%'
        //         OR T.status_name LIKE '%$search%'
        //     ";
        //     if ($searchDate != "1970-01-01") {
        //         $sql2 .= " OR T.dob LIKE '$searchDate%'
        //         OR T.dos LIKE '$searchDate%'
        //         OR T.last_followup_date LIKE '$searchDate%'
        //         OR T.next_follow_up LIKE '$searchDate%'
        //         OR T.created_at LIKE '$searchDate%'
        //         ";
        //     }
        //     $sql2 .= " )";
        // }

        $sql3 = "SELECT
            'MTD' as entity,
            COUNT(T.claim_no) as claims,
            SUM(T.paid_amount) as collections,
            (
            SUM(T.paid_amount)/ COUNT(T.claim_no)
            ) as average
        FROM
            (
            select
                SUBDATE(CURDATE(), DAYOFMONTH(CURDATE())) as from_date,
                CURDATE() as to_date,
                `cm_account_receivable`.`claim_no`,
                    `cm_account_receivable`.`dob`,
                    `cm_account_receivable`.`dos`,
                    `cm_account_receivable`.`billed_amount`,
                    `cm_account_receivable`.`paid_amount`,
                    DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y'	) AS entered_date,
                    `cm_account_receivable`.`created_at`,
                    DATEDIFF(CURDATE(),	cm_account_receivable.dos) as aging_days,
                    (CASE
                    WHEN DATEDIFF(CURDATE(),	cm_account_receivable.dos) > cm_payers.timely_filling_limit THEN 'Expired'
                    WHEN (DATEDIFF(CURDATE(),	cm_account_receivable.dos) > 60 && DATEDIFF(CURDATE(),cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60  THEN 'Under 60s'
                    ELSE 'None'
                    END ) AS aging_status,
                    `cm_account_receivable`.`practice_id`,
                    `cm_account_receivable`.`facility_id`,
                    `cm_account_receivable`.`shelter_id`,
                    `cm_account_receivable`.`payer_id`,
                    `cm_account_receivable`.`assigned_to`,
                    `cm_account_receivable`.`last_followup_date`,
                    `cm_account_receivable`.`remarks` as `remarks_id`,
                    `cm_account_receivable`.`status` as `status_id`,
                    `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
                    `cm_account_receivable`.`next_follow_up`,
                    `cm_account_receivable`.`paid_date`
                from
                `cm_account_receivable`
                left join `cm_payers` on `cm_payers`.`id`=`cm_account_receivable`.`payer_id`
                left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id`=`cm_account_receivable`.`status`

                WHERE
                    `cm_account_receivable`.`is_delete` = 0 $andCluas
                    AND `cm_account_receivable`.`practice_id` IN ($sessionPracticeIds)
                    AND `cm_account_receivable`.`facility_id` IN ($sessionFacilityIds)
                    AND `cm_revenue_cycle_status`.`status` = 'Paid'
            ) AS T
        WHERE
            T.last_followup_date BETWEEN T.from_date
            AND T.to_date
        ";
        if (strlen($practiceIds)) {
            $sql3 .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            $sql3 .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            $sql3 .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            $sql3 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        }
        if (strlen($payerIds)) {
            $sql3 .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {
            $sql3 .= " AND T.status_id IN($statusIds)";
        }

        if (strlen($assignTo) > 0 && $hasUnAssigned ==  true) {
            $sql3 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL )";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned ==  true) {
            $sql3 .= " AND  T.assigned_to_id IS NULL ";
        }
        if (strlen($assignTo) > 0 && $hasUnAssigned ==  false) {
            $sql3 .= " AND T.assigned_to_id IN($assignTo) ";
        }

        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql3 .= " AND T.dos = '$startDate'";
                else
                    $sql3 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql3 .= " AND T.dob = '$startDate'";
                else
                    $sql3 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql3 .= " AND T.last_followup_date = '$startDate'";
                else
                    $sql3 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql3 .= " AND DATE(T.created_at) = '$startDate'";
                else
                    $sql3 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql3 .= " AND T.next_follow_up = '$startDate'";
                else
                    $sql3 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql3 .= " AND T.paid_date = '$startDate'";
                else
                    $sql3 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
            }
        }
        if (count($buntyFilter)) {
            $btq = "(";
            if (in_array('1', $buntyFilter)) {
                $btq .= "  T.aging_status ='Expired'";
                if (count($buntyFilter) > 1)
                    $btq .= " OR ";
            }
            if (in_array('2', $buntyFilter)) {
                $btq .= " T.aging_status ='Expiring Soon'";
                if (in_array('3', $buntyFilter))
                    $btq .= " OR ";
            }
            if (in_array('3', $buntyFilter)) {
                $btq .= "  T.aging_status = 'Under 60s'";
            }
            $btq .= ")";
            $sql3 .= "AND $btq";
        }
        if (strlen($remarks)) {
            $sql3 .= " AND T.remarks_id IN($remarks)";
        }
        // if (strlen($search)) {
        //     $searchDate = date('Y-m-d', strtotime($search));

        //     $sql3 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //     OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //     OR T.remarks LIKE '%$search%'
        //     OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //     OR T.assigned_to_name LIKE '%$search%'
        //     OR AES_DECRYPT(T.practice_name ,'$key') LIKE '%$search%'
        //     OR AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //     OR T.shelter_name LIKE '%$search%'
        //     OR T.status_name LIKE '%$search%'
        //     ";

        //     if ($searchDate != "1970-01-01") {
        //         $sql3 .= " OR T.dob LIKE '$searchDate%'
        //         OR T.dos LIKE '$searchDate%'
        //         OR T.last_followup_date LIKE '$searchDate%'
        //         OR T.next_follow_up LIKE '$searchDate%'
        //         OR T.created_at LIKE '$searchDate%'";
        //     }
        //     $sql3 .= " )";
        // }

        $sql4 = "SELECT
        'YTD' as entity,
        COUNT(T.claim_no) as claims,
        SUM(T.paid_amount) as collections,
        (
            SUM(T.paid_amount)/ COUNT(T.claim_no)
        ) as average
        FROM
        (
            select
                SUBDATE( CURDATE(), DAYOFYEAR( CURDATE())) as from_date,
                CURDATE() as to_date,
                `cm_account_receivable`.`claim_no`,
                `cm_account_receivable`.`dob`,
                `cm_account_receivable`.`dos`,
                `cm_account_receivable`.`billed_amount`,
                `cm_account_receivable`.`paid_amount`,
                DATE_FORMAT(cm_account_receivable.created_at,'%m/%d/%Y'	) AS entered_date,
                `cm_account_receivable`.`created_at`,
                DATEDIFF(CURDATE(),	cm_account_receivable.dos) as aging_days,
                (CASE
                WHEN DATEDIFF(CURDATE(),	cm_account_receivable.dos) > cm_payers.timely_filling_limit THEN 'Expired'
                WHEN (DATEDIFF(CURDATE(),	cm_account_receivable.dos) > 60 && DATEDIFF(CURDATE(),cm_account_receivable.dos) <= cm_payers.timely_filling_limit ) THEN 'Expiring Soon'
                WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60  THEN 'Under 60s'
                ELSE 'None'
                END ) AS aging_status,
                `cm_account_receivable`.`practice_id`,
                `cm_account_receivable`.`facility_id`,
                `cm_account_receivable`.`shelter_id`,
                `cm_account_receivable`.`payer_id`,
                `cm_account_receivable`.`assigned_to`,
                `cm_account_receivable`.`last_followup_date`,
                `cm_account_receivable`.`remarks` as `remarks_id`,
                `cm_account_receivable`.`status` as `status_id`,
                `cm_account_receivable`.`assigned_to` as `assigned_to_id`,
                `cm_account_receivable`.`next_follow_up`,
                `cm_account_receivable`.`paid_date`
            from
            `cm_account_receivable`
            left join `cm_payers` on `cm_payers`.`id`=`cm_account_receivable`.`payer_id`
            left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id`=`cm_account_receivable`.`status`

            WHERE
                `cm_account_receivable`.`is_delete` = 0 $andCluas
                AND `cm_account_receivable`.`practice_id` IN ($sessionPracticeIds)
                AND `cm_account_receivable`.`facility_id` IN ($sessionFacilityIds)
                AND `cm_revenue_cycle_status`.`status` = 'Paid'
        ) AS T
        WHERE
        T.last_followup_date BETWEEN T.from_date
        AND T.to_date
        ";
        if (strlen($practiceIds)) {
            $sql4 .= " AND T.practice_id IN($practiceIds)";
        }
        if (strlen($facilityIds) && strlen($shelterIdsStr) == 0) {
            $sql4 .= " AND T.facility_id IN($facilityIds)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds) == 0) {
            $sql4 .= " AND T.shelter_id IN($shelterIdsStr)";
        }
        if (strlen($shelterIdsStr) && strlen($facilityIds)) {
            $sql4 .= " AND (T.shelter_id IN($shelterIdsStr) OR T.facility_id IN($facilityIds))";
        }
        if (strlen($payerIds)) {
            $sql4 .= " AND T.payer_id IN($payerIds)";
        }
        if (strlen($statusIds)) {
            $sql4 .= " AND T.status_id IN($statusIds)";
        }

        if (strlen($assignTo) > 0 && $hasUnAssigned ==  true) {
            $sql4 .= " AND (T.assigned_to_id IN($assignTo) OR T.assigned_to_id IS NULL )";
        }
        if (strlen($assignTo) == 0 && $hasUnAssigned ==  true) {
            $sql4 .= " AND  T.assigned_to_id IS NULL ";
        }
        if (strlen($assignTo) > 0 && $hasUnAssigned ==  false) {
            $sql4 .= " AND T.assigned_to_id IN($assignTo) ";
        }

        if (count($rangeFilter)) {
            if ($rangeFilter['column'] == "dos") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql4 .= " AND T.dos = '$startDate'";
                else
                    $sql4 .= " AND T.dos BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "dob") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql4 .= " AND T.dob = '$startDate'";
                else
                    $sql4 .= " AND T.dob BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "last_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql4 .= " AND T.last_followup_date = '$startDate'";
                else
                    $sql4 .= " AND T.last_followup_date BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "entered_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql4 .= " AND DATE(T.created_at) = '$startDate'";
                else
                    $sql4 .= " AND T.created_at BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "next_followup_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql4 .= " AND T.next_follow_up = '$startDate'";
                else
                    $sql4 .= " AND T.next_follow_up BETWEEN '$startDate' AND '$endDate'";
            }
            if ($rangeFilter['column'] == "paid_date") {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));

                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate)
                    $sql4 .= " AND T.paid_date = '$startDate'";
                else
                    $sql4 .= " AND T.paid_date BETWEEN '$startDate' AND '$endDate'";
            }
        }
        if (count($buntyFilter)) {
            $btq = "(";
            if (in_array('1', $buntyFilter)) {
                $btq .= "  T.aging_status ='Expired'";
                if (count($buntyFilter) > 1)
                    $btq .= " OR ";
            }
            if (in_array('2', $buntyFilter)) {
                $btq .= " T.aging_status ='Expiring Soon'";
                if (in_array('3', $buntyFilter))
                    $btq .= " OR ";
            }
            if (in_array('3', $buntyFilter)) {
                $btq .= "  T.aging_status = 'Under 60s'";
            }
            $btq .= ")";
            $sql4 .= "AND $btq";
        }
        if (strlen($remarks)) {
            $sql4 .= " AND T.remarks_id IN($remarks)";
        }
        // if (strlen($search)) {
        //     $searchDate = date('Y-m-d', strtotime($search));
        //     $sql4 .= " AND (T.`claim_no` LIKE '%$search%' OR T.`patient_name` LIKE '%$search%'
        //     OR T.billed_amount LIKE '%$search%' OR T.paid_amount LIKE '%$search%'
        //     OR T.remarks LIKE '%$search%'
        //     OR T.aging_days LIKE '%$search%'  OR T.payer_name LIKE '%$search%'
        //     OR T.assigned_to_name LIKE '%$search%'
        //     OR AES_DECRYPT(T.practice_name,'$key') LIKE '%$search%'
        //     OR AES_DECRYPT(T.facility_name,'$key') LIKE '%$search%'
        //     OR T.shelter_name LIKE '%$search%'
        //     OR T.status_name LIKE '%$search%'
        //     ";

        //     if ($searchDate != "1970-01-01") {
        //         $sql4 .= "  OR T.dob LIKE '$searchDate%'
        //         OR T.dos LIKE '$searchDate%'
        //         OR T.last_followup_date LIKE '$searchDate%'
        //         OR T.next_follow_up LIKE '$searchDate%'
        //         OR T.created_at LIKE '$searchDate%'";
        //     }
        //     $sql4 .= " )";
        // }

        $sql = "(" . $sql1 . ")";
        $sql .= "UNION ALL";
        $sql .= "(" . $sql2 . ")";
        $sql .= "UNION ALL";
        $sql .= "(" . $sql3 . ")";
        $sql .= "UNION ALL";
        $sql .= "(" . $sql4 . ")";

        return $this->rawQuery($sql);
    }
    /**
     * AR timely reports
     *
     */
    private function getTimelyReports()
    {
        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;
        $sessionUserId = $this->sessionUserId;

        // $isArchived = 0;
        // $arModel = new AccountReceivable();
        // $practices = $arModel->activePractices($sessionUserId);
        // $practiceIds = [];
        // foreach ($practices as $practice) {
        //     $practiceIds[] = $practice->facility_id;
        // }
        // $facilities = [];
        // foreach ($practiceIds as $practiceId) {
        //     $facilities[] = $arModel->getSpecificFacilities($practiceId, $sessionUserId, $isArchived);
        // }
        // $sessionFacilityIds = [];
        // foreach ($facilities as $facility) {
        //     foreach ($facility as $f) {
        //         $sessionFacilityIds[] = $f->facility_id;
        //     }
        // }
        // $sessionFacilityIds = array_unique($sessionFacilityIds);
        // $practiceIds = implode(',', $practiceIds);
        // $sessionFacilityIds = implode(', ', $sessionFacilityIds);


        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $practiceIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];
        $result1 = AccountReceivable::select(
            DB::raw("'Today' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('revenue_cycle_status.status', '!=', 'Deleted')
            ->where('revenue_cycle_status.status', 'Paid')
            ->whereBetween('account_receivable.last_followup_date', ['CURDATE()', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);

        $result2 = AccountReceivable::select(
            DB::raw("'WTD' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('account_receivable.is_delete', 0)
            ->whereBetween('account_receivable.last_followup_date', ['SUBDATE(CURDATE(), WEEKDAY(	CURDATE()))', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);

        $result3 = AccountReceivable::select(
            DB::raw("'MTD' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('account_receivable.is_delete', 0)
            ->whereBetween('account_receivable.last_followup_date', ['SUBDATE(CURDATE(), DAYOFMONTH( CURDATE()))', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);

        $result4 = AccountReceivable::select(
            DB::raw("'YTD' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('account_receivable.is_delete', 0)
            ->whereBetween('account_receivable.last_followup_date', ['SUBDATE(CURDATE(), DAYOFYEAR(CURDATE()))', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);



        $result = $result1->union($result2)->union($result3)->union($result4)->get();
        // dd($result);

        return $result;


        // $newSql = "
        //     select
        //     'Today' as entity,
        //     COUNT(`cm_account_receivable`.`claim_no`) as claims,
        //     SUM(`cm_account_receivable`.`paid_amount`) as collections,
        //     (SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average
        //     from
        //     `cm_account_receivable`
        //     left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //     inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '36436'
        //     left join `cm_user_ddpracticelocationinfo` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //     left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //     WHERE
        //     `cm_account_receivable`.`is_delete` = 0
        //     AND `cm_revenue_cycle_status`.`status` != 'Deleted'
        //     AND `cm_revenue_cycle_status`.`status` = 'Paid'
        //     AND `cm_account_receivable`.last_followup_date BETWEEN CURDATE() AND CURDATE()
        //     AND `cm_account_receivable`.`practice_id` in ($practiceIds)
        //     AND `cm_account_receivable`.`facility_id` in ($sessionFacilityIds)

        //     UNION ALL

        //     select
        //     'WTD' as entity,
        //     COUNT(`cm_account_receivable`.`claim_no`) as claims,
        //     SUM(`cm_account_receivable`.`paid_amount`) as collections,
        //     (SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average
        //     from
        //     `cm_account_receivable`
        //     inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '36436'
        //     left join `cm_user_ddpracticelocationinfo` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //     left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //     left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`

        //     WHERE
        //     `cm_account_receivable`.`is_delete` = 0
        //     AND `cm_revenue_cycle_status`.`status` != 'Deleted'
        //     AND `cm_revenue_cycle_status`.`status` = 'Paid'
        //     AND `cm_account_receivable`.last_followup_date BETWEEN SUBDATE(CURDATE(), WEEKDAY(	CURDATE())) AND CURDATE()
        //     AND `cm_account_receivable`.`practice_id` in ($practiceIds)
        //     AND `cm_account_receivable`.`facility_id` in ($sessionFacilityIds)

        //     UNION ALL

        //     select
        //     'MTD' as entity,
        //     COUNT(`cm_account_receivable`.`claim_no`) as claims,
        //     SUM(`cm_account_receivable`.`paid_amount`) as collections,
        //     (SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average
        //     from
        //     `cm_account_receivable`
        //     inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '36436'
        //     left join `cm_user_ddpracticelocationinfo` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //     left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //     left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //     WHERE
        //     `cm_account_receivable`.`is_delete` = 0
        //     AND `cm_revenue_cycle_status`.`status` != 'Deleted'
        //     AND `cm_revenue_cycle_status`.`status` = 'Paid'
        //     AND `cm_account_receivable`.last_followup_date BETWEEN SUBDATE(CURDATE(), DAYOFMONTH( CURDATE())) AND CURDATE()
        //     AND `cm_account_receivable`.`practice_id` in ($practiceIds)
        //     AND `cm_account_receivable`.`facility_id` in ($sessionFacilityIds)

        //     UNION ALL

        //     select
        //     'YTD' as entity,
        //     COUNT(`cm_account_receivable`.`claim_no`) as claims,
        //     SUM(`cm_account_receivable`.`paid_amount`) as collections,
        //     (SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average
        //     from
        //     `cm_account_receivable`
        //     inner join `cm_emp_location_map` AS `elm` ON `elm`.`location_user_id`= `cm_account_receivable`.`facility_id` AND elm.emp_id = '36436'
        //     left join `cm_user_ddpracticelocationinfo` as `cm_pli` on `cm_pli`.`user_id` = `elm`.`location_user_id`
        //     left join `cm_user_baf_practiseinfo` as `ubp` on `ubp`.`user_id` = cm_pli.user_parent_id
        //     left join `cm_revenue_cycle_status` on `cm_revenue_cycle_status`.`id` = `cm_account_receivable`.`status`
        //     WHERE
        //     `cm_account_receivable`.`is_delete` = 0
        //     AND `cm_revenue_cycle_status`.`status` != 'Deleted'
        //     AND `cm_revenue_cycle_status`.`status` = 'Paid'
        //     AND `cm_account_receivable`.last_followup_date BETWEEN SUBDATE(CURDATE(), DAYOFYEAR(CURDATE())) AND CURDATE()
        //     AND `cm_account_receivable`.`practice_id` in ($practiceIds)
        //     AND `cm_account_receivable`.`facility_id` in ($sessionFacilityIds)";

        // return $this->rawQuery($newSql);

        // Use $results as needed

    }

    /**
     * AR dashboard Aging Stats
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    public function agingStats(Request $request)
    {
        set_time_limit(0);

        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;

        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;
        
        if (strlen($facilityFilter) > 2 || strlen($practiceFilter) > 2 
        || strlen($payerFilter) > 2 || strlen($statusFilter) > 2 
        || strlen($assignedToFilter) > 2 || strlen($dateRangeFilter) > 2 
        || strlen($buntyFilter) > 2 || strlen($remarksFilter) > 2 
        || strlen($search) > 0) {

            // $agingStats = $this->getAgingRangesStatsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);
            $agingStats = $this->agingStatusFilterData($request);
          
        } else {
            $agingStats = $this->getAgingRangesStats();
            
        }
    
        $allAmounts = array_column($agingStats, 'amount');
        $allCliams = array_column($agingStats, 'claims');
        $allPaidAmount = array_column($agingStats, 'paid_amount');
        

        $sumAmounts = array_sum($allAmounts);
        $sumClaims = array_sum($allCliams);
        $sumPaidAmount = array_sum($allPaidAmount);

        $arr = [
            'currency_symbol' => '$',
            'total_amount' => $sumAmounts,
            'total_claims' => $sumClaims,
            'total_paid_amount' => $sumPaidAmount,
            'aging_stats' => $agingStats,
        ];

        return $this->successResponse($arr, 'success');
    }

    /**
     * AR dashboard Practice Stats
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    public function practiceStats(Request $request)
    {
        set_time_limit(0);

        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;


        if (strlen($facilityFilter) > 2 || strlen($practiceFilter) > 2 || strlen($payerFilter) > 2 || strlen($statusFilter) > 2 || strlen($assignedToFilter) > 2 || strlen($dateRangeFilter) > 2 || strlen($buntyFilter) > 2 || strlen($remarksFilter) > 2 || strlen($search) > 0) {

            // $practiceStats = $this->getPracticeStatsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);
            $practiceStats = $this->practiceStatsFilterData($request);
        } else {
            $practiceStats = $this->getPracticeStats();
        }

        $allAmounts = array_Column($practiceStats->toArray(), 'amount');
        $allClaims = array_Column($practiceStats->toArray(), 'total');
        $allPaidAmount = array_Column($practiceStats->toArray(), 'paid_amount');

        $totalClaims = array_sum($allClaims);
        $totalAmount = array_sum($allAmounts);
        $sumPaidAmount = array_sum($allPaidAmount);

        $arr = [
            'currency_symbol' => '$',
            'total_amount' => $totalAmount,
            'total_claims' => $totalClaims,
            'total_paid_amount' => $sumPaidAmount,
            'practice_stats' => $practiceStats,
        ];

        return $this->successResponse($arr, 'success');
    }

    /**
     * AR dashboard Assigned User Stats
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    public function assignedUserStats(Request $request)
    {
        set_time_limit(0);

        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        if (strlen($facilityFilter) > 2 || strlen($practiceFilter) > 2 || strlen($payerFilter) > 2 || strlen($statusFilter) > 2 || strlen($assignedToFilter) > 2 || strlen($dateRangeFilter) > 2 || strlen($buntyFilter) > 2 || strlen($remarksFilter) > 2 || strlen($search) > 0) {

            // $assignedToUsersStats =  $this->getAssignedUserStatsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);
            $assignedToUsersStats =  $this->assingedUserStatsFilterData($request);
        } else {
            $assignedToUsersStats = $this->getAssignedUserStats();
        }
        $convertedArray = $assignedToUsersStats->toArray();

        $allClaims = array_column($convertedArray, 'total');
        $totalClaims = array_sum($allClaims);

        $allPaidClaims = array_column($convertedArray, 'paid_claims');
        $totalPaidClaims = array_sum($allPaidClaims);

        $allBDP = array_column($convertedArray, 'balance_due_patient_claims');
        $totalallBDP = array_sum($allBDP);


        $allAmount = array_column($convertedArray, 'amount');
        $totalAmount = array_sum($allAmount);

        $arr = [
            'currency_symbol' => '$',
            'total_claims' => $totalClaims,
            'total_paid_claims' => $totalPaidClaims,
            'total_all_bdp' => $totalallBDP,
            'total_amount' => $totalAmount,
            'assigned_users_stats' => $assignedToUsersStats,
        ];

        return $this->successResponse($arr, 'success');
    }

    /**
     * AR dashboard Timely Stats
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    public function timelyStats(Request $request)
    {
        set_time_limit(0);

        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        if (strlen($facilityFilter) > 2 || strlen($practiceFilter) > 2 || strlen($payerFilter) > 2 || strlen($statusFilter) > 2 || strlen($assignedToFilter) > 2 || strlen($dateRangeFilter) > 2 || strlen($buntyFilter) > 2 || strlen($remarksFilter) > 2 || strlen($search) > 0) {

            $timelyStats = $this->TimelyReportsFilterData($request);
            // $timelyStats = $this->getTimelyReportsFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);
        } else {
            $timelyStats = $this->getTimelyReports();
        }
        $convertedArray = $timelyStats->toArray();

        $all_claims = array_column($convertedArray, 'claims');
        $totalClaims = array_sum($all_claims);

        $all_collections = array_column($convertedArray, 'collections');
        $totalCollections = array_sum($all_collections);

        $all_average = array_column($convertedArray, 'average');
        $total_average = array_sum($all_average);

        $arr = [
            'currency_symbol' => '$',
            'total_claims' => $totalClaims,
            'total_collection' => $totalCollections,
            'total_average' => $total_average,
            'timely_stats' => $timelyStats,
        ];

        return $this->successResponse($arr, 'success');
    }

    /**
     * AR dashboard Status Wise Summary
     *
     * @param \Illuminate\Http\Request
     * @param \Illuminate\Http\Response
     */
    public function statusWiseSummary(Request $request)
    {
        set_time_limit(0);
        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        if (strlen($facilityFilter) > 2 || strlen($practiceFilter) > 2 || strlen($payerFilter) > 2 || strlen($statusFilter) > 2 || strlen($assignedToFilter) > 2 || strlen($dateRangeFilter) > 2 || strlen($buntyFilter) > 2 || strlen($remarksFilter) > 2 || strlen($search) > 0) {

            // $statusWiseSummary = $this->getStatusWiseSummaryFilter($practiceIdsStr, $payerIdsStr, $statusIdsStr, $assignToIdsStr, $rangeFilter, $buntyFilterArr, $remarksIdsStr, $facilityIdsStr, $search, $shelterIdsStr, $hasUnAssigned);
            $statusWiseSummary = $this->statusWiseSummaryFilterData($request);
        } else {
            $statusWiseSummary = $this->getStatusWiseSummary();
        }

        $convertedArray = $statusWiseSummary->toArray();

        $allClaims = array_column($convertedArray, 'claims');
        $total_allClaims = array_sum($allClaims);

        $allamount = array_column($convertedArray, 'amount');
        $total_allamount = array_sum($allamount);

        $alPaidAmount = array_column($convertedArray, 'paid_amount');
        $totalPaidAmount = array_sum($alPaidAmount);

        $arr = [
            'currency_symbol' => '$',
            'all_amount' => $total_allamount,
            'all_claims' => $total_allClaims,
            'total_paid_amount' => $totalPaidAmount,
            'status_wise_summary' => $statusWiseSummary
        ];

        return $this->successResponse($arr, 'success');
    }


    //Filters Data
    public function agingStatusFilterData(Request $request)
    {

        $isArchived = 0;
        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $sessionPracticeIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];


        $practiceIds = [];
        $facilityIds = [];
        $payerIds = [];
        $statusIds = [];
        $assignToIds = [];
        $remarksIds = [];
        $rangeFilter = [];
        $hasUnAssigned = false;
        $shelterIds = [];

        if (strlen($practiceFilter) > 2) {
            $practiceFilter = json_decode($practiceFilter, true);
            $practiceIds = array_column($practiceFilter, "value");
        }
        if (strlen($facilityFilter) > 2) {
            $facilityFilter = json_decode($facilityFilter, true);
            $facilityIds = array_column($facilityFilter, "value");
            $facilityIdsArr = $this->removeDecimalValues($facilityIds);
            $facilityIds = $facilityIdsArr["facility"];
            $shelterIds = $facilityIdsArr["shelter"];
        }

        if (strlen($payerFilter) > 2) {
            $payerIds = json_decode($payerFilter, true);
            $payerFilter = json_decode($payerFilter, true);
        }

        if (strlen($statusFilter) > 2) {
            $statusFilter = json_decode($statusFilter, true);
            $statusIds = array_column($statusFilter, "id");
        }

        if (strlen($assignedToFilter) > 2) {
            $assignedToFilter = json_decode($assignedToFilter, true);
            $assignToIds = array_column($assignedToFilter, "value");
            if (in_array("Unassigned", $assignToIds)) {
                $hasUnAssigned =  true;
                $key = array_search("Unassigned", $assignToIds);
                unset($assignToIds[$key]);
            }
        }

        if (strlen($dateRangeFilter) > 2) {
            $rangeFilter = json_decode($dateRangeFilter, true);
        }
        $buntyFilterArr = [];
        if (strlen($buntyFilter) > 2) {
            $buntyFilterArr = json_decode($buntyFilter, true);
        }
        if (strlen($remarksFilter) > 2) {
            $remarksFilter = json_decode($remarksFilter, true);
            $remarksIds = array_column($remarksFilter, "id");
        }


        $result = AccountReceivable::select(
            DB::raw('COUNT(claim_no) as claims'),
            DB::raw('SUM(balance_amount) AS amount'),
            DB::raw('SUM(paid_amount) AS paid_amount'),
            DB::raw('
                CASE
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 0 AND 30 THEN "0-30"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 31 AND 60 THEN "31-60"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 61 AND 90 THEN "61-90"
                    WHEN DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 91 AND 365 THEN "91-365"
                    ELSE "365+"
                END AS aging_range
            ')
        )->whereIn('status', [10, 1, 8, 4, 7, 9, 3])
        ->where('is_delete', 0)
        ->groupBy('aging_range');

        //--Practice Filters
        if (count($practiceIds) > 0) {
            $result = $result->whereIn('practice_id', $practiceIds);
        } else {
            $result = $result->whereIn('practice_id', $sessionPracticeIds);
        }


        //--Facility & Shelter Filters
        if (count($facilityIds) > 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $facilityIds);
        }
        if (count($facilityIds) == 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $sessionFacilityIds);
        }
        if (count($shelterIds) > 0 && count($facilityIds) == 0) {
            $result = $result->whereIn('shelter_id', $shelterIds);
        }
        if (count($facilityIds) > 0 && count($shelterIds) > 0) {
            $result =  $result->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
        }

        //--Payer Filters
        if (count($payerIds) > 0) {
            $result = $result->whereIn('payer_id', $payerIds);
        }

        //--Status Filters
        if (count($statusIds) > 0) {
            $result = $result->whereIn('status', $statusIds);
        }

        //--Assinge To Filters
        if (count($assignToIds) > 0 && $hasUnAssigned == true) {
            $result =  $result->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to', $assignToIds)
                    ->orWhereIn('assigned_to', null);
            });
        }
        if (count($assignToIds) > 0 && $hasUnAssigned == false) {
            $result = $result->whereIn('assigned_to', $assignToIds);
        }
        if (count($assignToIds) == 0 && $hasUnAssigned == true) {
            $result = $result->where('assigned_to', null);
        }

        //--Range Filters
        if (count($rangeFilter)) {
            if (in_array($rangeFilter['column'], ['dos', 'paid_date', 'next_followup_date', 'entered_date', 'last_followup_date', 'dob'])) {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate) {
                    $result =  $result->where($rangeFilter['column'], '=', $startDate);
                } else {
                    $result = $result->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                }
            }
        }

        //--Remark Filters
        if (count($remarksIds) > 0) {
            $result =  $result->whereIn('remarks', $remarksIds);
        }
        if (count($buntyFilterArr) > 0) {
            if (in_array('1', $buntyFilterArr) || in_array('2', $buntyFilterArr) || in_array('3', $buntyFilterArr)) {
                $result = $result->where(function($query) use ($buntyFilterArr) {
                    if (in_array('3', $buntyFilterArr)) {
                        $query->orWhereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) <= 60');
                    }
                    if (in_array('2', $buntyFilterArr)) {
                        $query->orWhereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 61 AND 90');
                    }
                    if (in_array('1', $buntyFilterArr)) {
                        $query->orWhereRaw('DATEDIFF(CURDATE(), cm_account_receivable.dos) BETWEEN 91 AND 10000');
                    }
                });
            }
        }
       $result = $result->get();
       // Create a Collection from the array
       $agingStats = collect($result);

       // Order the collection by 'aging_range'
       $sortedAgingStats = $agingStats->sort(function ($a, $b) {
           // Extract the first number of the range and compare them
           $aStart = intval(explode('-', preg_replace('/\D/', '-', $a['aging_range']))[0]);
           $bStart = intval(explode('-', preg_replace('/\D/', '-', $b['aging_range']))[0]);
       
           return $aStart <=> $bStart;
       });
       return $sortedAgingStats->values()->all();
    }

    public function practiceStatsFilterData(Request $request)
    {


        $isArchived = 0;
        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        $arModel = new AccountReceivable();

        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $sessionPracticeIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];


        $practiceIds = [];
        $facilityIds = [];
        $payerIds = [];
        $statusIds = [];
        $assignToIds = [];
        $remarksIds = [];
        $rangeFilter = [];
        $hasUnAssigned = false;
        $shelterIds = [];

        if (strlen($practiceFilter) > 2) {
            $practiceFilter = json_decode($practiceFilter, true);
            $practiceIds = array_column($practiceFilter, "value");
        }
        if (strlen($facilityFilter) > 2) {
            $facilityFilter = json_decode($facilityFilter, true);
            $facilityIds = array_column($facilityFilter, "value");
            $facilityIdsArr = $this->removeDecimalValues($facilityIds);
            $facilityIds = $facilityIdsArr["facility"];
            $shelterIds = $facilityIdsArr["shelter"];
        }

        if (strlen($payerFilter) > 2) {
            $payerIds = json_decode($payerFilter, true);
            $payerFilter = json_decode($payerFilter, true);
        }

        if (strlen($statusFilter) > 2) {
            $statusFilter = json_decode($statusFilter, true);
            $statusIds = array_column($statusFilter, "id");
        }

        if (strlen($assignedToFilter) > 2) {
            $assignedToFilter = json_decode($assignedToFilter, true);
            $assignToIds = array_column($assignedToFilter, "value");
            if (in_array("Unassigned", $assignToIds)) {
                $hasUnAssigned =  true;
                $key = array_search("Unassigned", $assignToIds);
                unset($assignToIds[$key]);
            }
        }

        if (strlen($dateRangeFilter) > 2) {
            $rangeFilter = json_decode($dateRangeFilter, true);
        }
        $buntyFilterArr = [];
        if (strlen($buntyFilter) > 2) {
            $buntyFilterArr = json_decode($buntyFilter, true);
        }
        if (strlen($remarksFilter) > 2) {
            $remarksFilter = json_decode($remarksFilter, true);
            $remarksIds = array_column($remarksFilter, "id");
        }


        $result = AccountReceivable::select(
            DB::raw('COUNT(claim_no) as total'),
            DB::raw('NULL as practice_name'),
            DB::raw('practice_id'),
            DB::raw('SUM(balance_amount) as amount'),
            DB::raw('SUM(paid_amount) AS paid_amount'),
        )
            ->whereIn('status', [10, 1, 8, 4, 7, 9, 3])
            ->where('is_delete', 0)->groupBy('practice_id');

        //--Practice Filters
        if (count($practiceIds) > 0) {
            $result = $result->whereIn('practice_id', $practiceIds);
        } else {
            $result = $result->whereIn('practice_id', $sessionPracticeIds);
        }


        //--Facility & Shelter Filters
        if (count($facilityIds) > 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $facilityIds);
        }
        if (count($facilityIds) == 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $sessionFacilityIds);
        }
        if (count($shelterIds) > 0 && count($facilityIds) == 0) {
            $result = $result->whereIn('shelter_id', $shelterIds);
        }
        if (count($facilityIds) > 0 && count($shelterIds) > 0) {
            $result =  $result->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
        }

        //--Payer Filters
        if (count($payerIds) > 0) {
            $result = $result->whereIn('payer_id', $payerIds);
        }

        //--Status Filters
        if (count($statusIds) > 0) {
            $result = $result->whereIn('status', $statusIds);
        }

        //--Assinge To Filters
        if (count($assignToIds) > 0 && $hasUnAssigned == true) {
            $result =  $result->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to', $assignToIds)
                    ->orWhereIn('assigned_to', null);
            });
        }
        if (count($assignToIds) > 0 && $hasUnAssigned == false) {
            $result = $result->whereIn('assigned_to', $assignToIds);
        }
        if (count($assignToIds) == 0 && $hasUnAssigned == true) {
            $result = $result->where('assigned_to', null);
        }

        //--Range Filters
        if (count($rangeFilter)) {
            if (in_array($rangeFilter['column'], ['dos', 'paid_date', 'next_followup_date', 'entered_date', 'last_followup_date', 'dob'])) {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate) {
                    $result =  $result->where($rangeFilter['column'], '=', $startDate);
                } else {
                    $result = $result->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                }
            }
        }

        //--Remark Filters
        if (count($remarksIds) > 0) {
            $result =  $result->whereIn('remarks', $remarksIds);
        }
        $result  = $result->get();

        //--Expires Filters
        if (count($buntyFilterArr)) {
            $buntyFiltervalues = [null, 'Expired', 'Expiring Soon', 'Under 60s'];
            $buntyFilterArr = array_flip($buntyFilterArr);
            $intersectVals = array_intersect_key($buntyFiltervalues, $buntyFilterArr);
            $intersectVals = array_values($intersectVals);

            $result = $result->filter(function ($item) use ($intersectVals) {
                return in_array($item->aging_status, $intersectVals);
            });
        }

        foreach ($result as $results) {
            $results->practice_name = $arModel->getPracticeNamesById($results->practice_id);
        }
        $result = collect($result)->sortBy('practice_name')->values()->all();
        return collect($result);
    }

    public function assingedUserStatsFilterData(Request $request)
    {


        $isArchived = 0;
        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        $arModel = new AccountReceivable();

        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $sessionPracticeIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];


        $practiceIds = [];
        $facilityIds = [];
        $payerIds = [];
        $statusIds = [];
        $assignToIds = [];
        $remarksIds = [];
        $rangeFilter = [];
        $hasUnAssigned = false;
        $shelterIds = [];

        if (strlen($practiceFilter) > 2) {
            $practiceFilter = json_decode($practiceFilter, true);
            $practiceIds = array_column($practiceFilter, "value");
        }
        if (strlen($facilityFilter) > 2) {
            $facilityFilter = json_decode($facilityFilter, true);
            $facilityIds = array_column($facilityFilter, "value");
            $facilityIdsArr = $this->removeDecimalValues($facilityIds);
            $facilityIds = $facilityIdsArr["facility"];
            $shelterIds = $facilityIdsArr["shelter"];
        }

        if (strlen($payerFilter) > 2) {
            $payerIds = json_decode($payerFilter, true);
            $payerFilter = json_decode($payerFilter, true);
        }

        if (strlen($statusFilter) > 2) {
            $statusFilter = json_decode($statusFilter, true);
            $statusIds = array_column($statusFilter, "id");
        }

        if (strlen($assignedToFilter) > 2) {
            $assignedToFilter = json_decode($assignedToFilter, true);
            $assignToIds = array_column($assignedToFilter, "value");
            if (in_array("Unassigned", $assignToIds)) {
                $hasUnAssigned =  true;
                $key = array_search("Unassigned", $assignToIds);
                unset($assignToIds[$key]);
            }
        }

        if (strlen($dateRangeFilter) > 2) {
            $rangeFilter = json_decode($dateRangeFilter, true);
        }
        $buntyFilterArr = [];
        if (strlen($buntyFilter) > 2) {
            $buntyFilterArr = json_decode($buntyFilter, true);
        }
        if (strlen($remarksFilter) > 2) {
            $remarksFilter = json_decode($remarksFilter, true);
            $remarksIds = array_column($remarksFilter, "id");
        }


        $currentMonth = date('m');
        $result = AccountReceivable::select(
            DB::raw("if(CONCAT(cm_asg.first_name, '  ', cm_asg.last_name)  IS NULL, 'Unassigned', CONCAT(cm_asg.first_name, '  ', cm_asg.last_name) ) as assinged_to_me"),
            DB::raw("NULL as total"),
            DB::raw("SUM(CASE WHEN cm_cu.status = 'Paid' THEN 1 ELSE 0 END) AS paid_claims"),
            DB::raw("SUM(CASE WHEN cm_cu.status = 'BALANCE DUE PATIENT' THEN 1 ELSE 0 END) AS balance_due_patient_claims"),
            DB::raw("SUM(paid_amount) as amount"),
            DB::raw("cm_asg.id as user_id"),
        )
            ->leftjoin('payers', 'payers.id', 'payer_id')
            ->leftjoin('users as asg', 'asg.id', 'assigned_to')
            ->leftjoin('revenue_cycle_status as cu', 'cu.id', 'account_receivable.status')
            ->where('is_delete', 0)
            ->where(function ($query) {
                $query->where('cu.status', 'Paid')
                    ->orWhere('cu.status', 'BALANCE DUE PATIENT');
            })
            ->whereMonth('account_receivable.created_at', $currentMonth)
            ->groupBy(DB::raw("CONCAT(cm_asg.first_name, '  ', cm_asg.last_name)"))->orderby(DB::raw("CONCAT(cm_asg.first_name, '  ', cm_asg.last_name)"));



        //--Practice Filters
        if (count($practiceIds) > 0) {
            $result = $result->whereIn('practice_id', $practiceIds);
        } else {
            $result = $result->whereIn('practice_id', $sessionPracticeIds);
        }


        //--Facility & Shelter Filters
        if (count($facilityIds) > 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $facilityIds);
        }
        if (count($facilityIds) == 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $sessionFacilityIds);
        }
        if (count($shelterIds) > 0 && count($facilityIds) == 0) {
            $result = $result->whereIn('shelter_id', $shelterIds);
        }
        if (count($facilityIds) > 0 && count($shelterIds) > 0) {
            $result =  $result->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
        }

        //--Payer Filters
        if (count($payerIds) > 0) {
            $result = $result->whereIn('payer_id', $payerIds);
        }

        //--Status Filters
        if (count($statusIds) > 0) {
            $result = $result->whereIn('cu.status', $statusIds);
        }

        //--Assinge To Filters
        if (count($assignToIds) > 0 && $hasUnAssigned == true) {
            $result =  $result->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to', $assignToIds)
                    ->orWhereIn('assigned_to', null);
            });
        }
        if (count($assignToIds) > 0 && $hasUnAssigned == false) {
            $result = $result->whereIn('assigned_to', $assignToIds);
        }
        if (count($assignToIds) == 0 && $hasUnAssigned == true) {
            $result = $result->where('assigned_to', null);
        }

        //--Range Filters
        if (count($rangeFilter)) {
            if (in_array($rangeFilter['column'], ['dos', 'paid_date', 'next_followup_date', 'entered_date', 'last_followup_date', 'dob'])) {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate) {
                    $result =  $result->where($rangeFilter['column'], '=', $startDate);
                } else {
                    $result = $result->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                }
            }
        }

        //--Remark Filters
        if (count($remarksIds) > 0) {
            $result =  $result->whereIn('remarks', $remarksIds);
        }
        $result  = $result->get();

        //--Expires Filters
        if (count($buntyFilterArr)) {
            $buntyFiltervalues = [null, 'Expired', 'Expiring Soon', 'Under 60s'];
            $buntyFilterArr = array_flip($buntyFilterArr);
            $intersectVals = array_intersect_key($buntyFiltervalues, $buntyFilterArr);
            $intersectVals = array_values($intersectVals);

            $result = $result->filter(function ($item) use ($intersectVals) {
                return in_array($item->aging_status, $intersectVals);
            });
        }

        foreach ($result as $results) {
            if ($results->user_id) {
                $results->total = $arModel->getClaimCountByUserId($results->user_id);
            }
        }

        return $result;
    }

    public function TimelyReportsFilterData(Request $request)
    {

        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $sessionPracticeIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];


        $practiceIds = [];
        $facilityIds = [];
        $payerIds = [];
        $statusIds = [];
        $assignToIds = [];
        $remarksIds = [];
        $rangeFilter = [];
        $hasUnAssigned = false;
        $shelterIds = [];

        if (strlen($practiceFilter) > 2) {
            $practiceFilter = json_decode($practiceFilter, true);
            $practiceIds = array_column($practiceFilter, "value");
        }
        if (strlen($facilityFilter) > 2) {
            $facilityFilter = json_decode($facilityFilter, true);
            $facilityIds = array_column($facilityFilter, "value");
            $facilityIdsArr = $this->removeDecimalValues($facilityIds);
            $facilityIds = $facilityIdsArr["facility"];
            $shelterIds = $facilityIdsArr["shelter"];
        }

        if (strlen($payerFilter) > 2) {
            $payerIds = json_decode($payerFilter, true);
            $payerFilter = json_decode($payerFilter, true);
        }

        if (strlen($statusFilter) > 2) {
            $statusFilter = json_decode($statusFilter, true);
            $statusIds = array_column($statusFilter, "id");
        }

        if (strlen($assignedToFilter) > 2) {
            $assignedToFilter = json_decode($assignedToFilter, true);
            $assignToIds = array_column($assignedToFilter, "value");
            if (in_array("Unassigned", $assignToIds)) {
                $hasUnAssigned =  true;
                $key = array_search("Unassigned", $assignToIds);
                unset($assignToIds[$key]);
            }
        }

        if (strlen($dateRangeFilter) > 2) {
            $rangeFilter = json_decode($dateRangeFilter, true);
        }
        $buntyFilterArr = [];
        if (strlen($buntyFilter) > 2) {
            $buntyFilterArr = json_decode($buntyFilter, true);
        }
        if (strlen($remarksFilter) > 2) {
            $remarksFilter = json_decode($remarksFilter, true);
            $remarksIds = array_column($remarksFilter, "id");
        }


        $result1 = AccountReceivable::select(
            DB::raw("'Today' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('revenue_cycle_status.status', '!=', 'Deleted')
            ->where('revenue_cycle_status.status', 'Paid')
            ->whereBetween('account_receivable.last_followup_date', ['CURDATE()', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);

        $result2 = AccountReceivable::select(
            DB::raw("'WTD' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('account_receivable.is_delete', 0)
            ->whereBetween('account_receivable.last_followup_date', ['SUBDATE(CURDATE(), WEEKDAY(	CURDATE()))', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);

        $result3 = AccountReceivable::select(
            DB::raw("'MTD' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('account_receivable.is_delete', 0)
            ->whereBetween('account_receivable.last_followup_date', ['SUBDATE(CURDATE(), DAYOFMONTH( CURDATE()))', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);

        $result4 = AccountReceivable::select(
            DB::raw("'YTD' as entity"),
            DB::raw("COUNT(`cm_account_receivable`.`claim_no`) as claims"),
            DB::raw("SUM(`cm_account_receivable`.`paid_amount`) as collections"),
            DB::raw("(SUM(`cm_account_receivable`.`paid_amount`)/ COUNT(`cm_account_receivable`.`claim_no`)) as average"),
        )
            ->join("emp_location_map as elm", function ($join) {
                $join->on('location_user_id', '=', 'account_receivable.facility_id')->where('emp_id', 36436);
            })
            ->leftjoin("revenue_cycle_status", "revenue_cycle_status.id", "revenue_cycle_status.status")
            ->leftjoin("user_ddpracticelocationinfo as cm_pli", "cm_pli.user_id", "elm.location_user_id")
            ->leftjoin("user_baf_practiseinfo as ubp", "ubp.user_id", "cm_pli.user_parent_id")
            ->where('account_receivable.is_delete', 0)
            ->whereBetween('account_receivable.last_followup_date', ['SUBDATE(CURDATE(), DAYOFYEAR(CURDATE()))', 'CURDATE()'])
            ->whereIn('practice_id', $practiceIds)
            ->whereIn('facility_id', $sessionFacilityIds);


        //--Practice Filters
        if (count($practiceIds) > 0) {
            $result1 = $result1->whereIn('practice_id', $practiceIds);
            $result2 = $result2->whereIn('practice_id', $practiceIds);
            $result3 = $result3->whereIn('practice_id', $practiceIds);
            $result4 = $result4->whereIn('practice_id', $practiceIds);
        } else {
            $result1 = $result1->whereIn('practice_id', $sessionPracticeIds);
            $result2 = $result2->whereIn('practice_id', $sessionPracticeIds);
            $result3 = $result3->whereIn('practice_id', $sessionPracticeIds);
            $result4 = $result4->whereIn('practice_id', $sessionPracticeIds);
        }


        //--Facility & Shelter Filters
        if (count($facilityIds) > 0 && count($shelterIds) == 0) {
            $result1 = $result1->whereIn('facility_id', $facilityIds);
            $result2 = $result2->whereIn('facility_id', $facilityIds);
            $result3 = $result3->whereIn('facility_id', $facilityIds);
            $result4 = $result4->whereIn('facility_id', $facilityIds);
        }
        if (count($facilityIds) == 0 && count($shelterIds) == 0) {
            $result1 = $result1->whereIn('facility_id', $sessionFacilityIds);
            $result2 = $result2->whereIn('facility_id', $sessionFacilityIds);
            $result3 = $result3->whereIn('facility_id', $sessionFacilityIds);
            $result4 = $result4->whereIn('facility_id', $sessionFacilityIds);
        }
        if (count($shelterIds) > 0 && count($facilityIds) == 0) {
            $result1 = $result1->whereIn('shelter_id', $shelterIds);
            $result2 = $result2->whereIn('shelter_id', $shelterIds);
            $result3 = $result3->whereIn('shelter_id', $shelterIds);
            $result4 = $result4->whereIn('shelter_id', $shelterIds);
        }
        if (count($facilityIds) > 0 && count($shelterIds) > 0) {
            $result1 =  $result1->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
            $result2 =  $result2->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
            $result3 =  $result3->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
            $result4 =  $result4->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
        }

        //--Payer Filters
        if (count($payerIds) > 0) {
            $result1 = $result1->whereIn('payer_id', $payerIds);
            $result2 = $result2->whereIn('payer_id', $payerIds);
            $result3 = $result3->whereIn('payer_id', $payerIds);
            $result4 = $result4->whereIn('payer_id', $payerIds);
        }

        //--Status Filters
        if (count($statusIds) > 0) {
            $result1 = $result1->whereIn('account_receivable.status', $statusIds);
            $result2 = $result2->whereIn('account_receivable.status', $statusIds);
            $result3 = $result3->whereIn('account_receivable.status', $statusIds);
            $result4 = $result4->whereIn('account_receivable.status', $statusIds);
        }

        //--Assinge To Filters
        if (count($assignToIds) > 0 && $hasUnAssigned == true) {
            $result1 =  $result1->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to_id', $assignToIds)
                    ->orWhereIn('assigned_to_id', null);
            });
            $result2 =  $result2->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to_id', $assignToIds)
                    ->orWhereIn('assigned_to_id', null);
            });
            $result3 =  $result3->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to_id', $assignToIds)
                    ->orWhereIn('assigned_to_id', null);
            });
            $result4 =  $result4->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to_id', $assignToIds)
                    ->orWhereIn('assigned_to_id', null);
            });
        }
        if (count($assignToIds) > 0 && $hasUnAssigned == false) {
            $result1 = $result1->whereIn('assigned_to', $assignToIds);
            $result2 = $result2->whereIn('assigned_to', $assignToIds);
            $result3 = $result3->whereIn('assigned_to', $assignToIds);
            $result4 = $result4->whereIn('assigned_to', $assignToIds);
        }
        if (count($assignToIds) == 0 && $hasUnAssigned == true) {
            $result1 = $result1->where('assigned_to', null);
            $result2 = $result2->where('assigned_to', null);
            $result3 = $result3->where('assigned_to', null);
            $result4 = $result4->where('assigned_to', null);
        }

        //--Range Filters
        if (count($rangeFilter)) {
            if (in_array($rangeFilter['column'], ['dos', 'paid_date', 'next_followup_date', 'entered_date', 'last_followup_date', 'dob'])) {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate) {
                    $result1 =  $result1->where($rangeFilter['column'], '=', $startDate);
                    $result2 =  $result2->where($rangeFilter['column'], '=', $startDate);
                    $result3 =  $result3->where($rangeFilter['column'], '=', $startDate);
                    $result4 =  $result4->where($rangeFilter['column'], '=', $startDate);
                } else {
                    $result1 = $result1->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                    $result2 = $result2->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                    $result3 = $result3->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                    $result4 = $result4->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                }
            }
        }


        //--Remark Filters
        if (count($remarksIds) > 0) {
            $result1 =  $result1->whereIn('remarks', $remarksIds);
            $result2 =  $result2->whereIn('remarks', $remarksIds);
            $result3 =  $result3->whereIn('remarks', $remarksIds);
            $result4 =  $result4->whereIn('remarks', $remarksIds);
        }

        $result  = $result1->union($result2)->union($result3)->union($result4)->get();


        //--Expires Filters
        if (count($buntyFilterArr)) {
            $buntyFiltervalues = [null, 'Expired', 'Expiring Soon', 'Under 60s'];
            $buntyFilterArr = array_flip($buntyFilterArr);
            $intersectVals = array_intersect_key($buntyFiltervalues, $buntyFilterArr);
            $intersectVals = array_values($intersectVals);

            $result = $result->filter(function ($item) use ($intersectVals) {
                return in_array($item->aging_status, $intersectVals);
            });
        }

        return $result;
    }

    public function statusWiseSummaryFilterData(Request $request)
    {



        $practiceFilter     = $request->practice_filter;
        $facilityFilter     = $request->facility_filter;
        $payerFilter        = $request->payers_filter;
        $statusFilter       = $request->status_filter;
        $assignedToFilter   = $request->assigned_to_filter;
        $dateRangeFilter    = $request->date_range_filter;
        $buntyFilter        = $request->bunty_filter;
        $remarksFilter      = $request->remarks_filter;
        $search             = $request->search;
        $sessionUser            = $this->getSessionUserId($request);
        $sessionUserId          = $sessionUser;
        $this->sessionUserId    = $sessionUserId;

        $credentiling = new Credentialing();
        $facilityAndPractice = $credentiling->fetchActiveFacilitiesAndPracticeOfUser($sessionUserId);
        $sessionPracticeIds = $facilityAndPractice['practices'];
        $sessionFacilityIds = $facilityAndPractice['facility'];


        $practiceIds = [];
        $facilityIds = [];
        $payerIds = [];
        $statusIds = [];
        $assignToIds = [];
        $remarksIds = [];
        $rangeFilter = [];
        $hasUnAssigned = false;
        $shelterIds = [];

        if (strlen($practiceFilter) > 2) {
            $practiceFilter = json_decode($practiceFilter, true);
            $practiceIds = array_column($practiceFilter, "value");
        }
        if (strlen($facilityFilter) > 2) {
            $facilityFilter = json_decode($facilityFilter, true);
            $facilityIds = array_column($facilityFilter, "value");
            $facilityIdsArr = $this->removeDecimalValues($facilityIds);
            $facilityIds = $facilityIdsArr["facility"];
            $shelterIds = $facilityIdsArr["shelter"];
        }

        if (strlen($payerFilter) > 2) {
            $payerIds = json_decode($payerFilter, true);
            $payerFilter = json_decode($payerFilter, true);
        }

        if (strlen($statusFilter) > 2) {
            $statusFilter = json_decode($statusFilter, true);
            $statusIds = array_column($statusFilter, "id");
        }

        if (strlen($assignedToFilter) > 2) {
            $assignedToFilter = json_decode($assignedToFilter, true);
            $assignToIds = array_column($assignedToFilter, "value");
            if (in_array("Unassigned", $assignToIds)) {
                $hasUnAssigned =  true;
                $key = array_search("Unassigned", $assignToIds);
                unset($assignToIds[$key]);
            }
        }

        if (strlen($dateRangeFilter) > 2) {
            $rangeFilter = json_decode($dateRangeFilter, true);
        }
        $buntyFilterArr = [];
        if (strlen($buntyFilter) > 2) {
            $buntyFilterArr = json_decode($buntyFilter, true);
        }
        if (strlen($remarksFilter) > 2) {
            $remarksFilter = json_decode($remarksFilter, true);
            $remarksIds = array_column($remarksFilter, "id");
        }

        $result = AccountReceivable::selectRaw('
            COUNT(cm_account_receivable.claim_no) as claims,
            SUM(cm_account_receivable.balance_amount) as amount,
            SUM(cm_account_receivable.paid_amount) as paid_amount,
            payer_name
        ')
            ->leftJoin('payers', 'payers.id', '=', 'account_receivable.payer_id')
            ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'account_receivable.status')
            ->where('account_receivable.is_delete', 0)
            ->where('account_receivable.status', '!=', 25)
            ->whereIn('account_receivable.practice_id', $practiceIds)
            ->whereIn('account_receivable.facility_id', $sessionFacilityIds)
            ->whereIn('revenue_cycle_status.id', [10, 1, 8, 4, 7, 9, 3])
            ->groupBy('payers.id')
            ->orderByDesc('claims');


        //--Practice Filters
        if (count($practiceIds) > 0) {
            $result = $result->whereIn('practice_id', $practiceIds);
        } else {
            $result = $result->whereIn('practice_id', $sessionPracticeIds);
        }


        //--Facility & Shelter Filters
        if (count($facilityIds) > 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $facilityIds);
        }
        if (count($facilityIds) == 0 && count($shelterIds) == 0) {
            $result = $result->whereIn('facility_id', $sessionFacilityIds);
        }
        if (count($shelterIds) > 0 && count($facilityIds) == 0) {
            $result = $result->whereIn('shelter_id', $shelterIds);
        }
        if (count($facilityIds) > 0 && count($shelterIds) > 0) {
            $result =  $result->where(function ($query) use ($shelterIds, $facilityIds) {
                $query->whereIn('shelter_id', $shelterIds)
                    ->orWhereIn('facility_id', $facilityIds);
            });
        }

        //--Payer Filters
        if (count($payerIds) > 0) {
            $result = $result->whereIn('payer_id', $payerIds);
        }

        //--Status Filters
        if (count($statusIds) > 0) {
            $result = $result->whereIn('account_receivable.status', $statusIds);
        }

        //--Assinge To Filters
        if (count($assignToIds) > 0 && $hasUnAssigned == true) {
            $result =  $result->where(function ($query) use ($assignToIds) {
                $query->whereIn('assigned_to', $assignToIds)
                    ->orWhereIn('assigned_to', null);
            });
        }
        if (count($assignToIds) > 0 && $hasUnAssigned == false) {
            $result = $result->whereIn('assigned_to', $assignToIds);
        }
        if (count($assignToIds) == 0 && $hasUnAssigned == true) {
            $result = $result->where('assigned_to', null);
        }

        //--Range Filters
        if (count($rangeFilter)) {
            if (in_array($rangeFilter['column'], ['dos', 'paid_date', 'next_followup_date', 'entered_date', 'last_followup_date', 'dob'])) {
                $startDate = date('Y-m-d', strtotime($rangeFilter["startDate"]));
                $endDate = date('Y-m-d', strtotime($rangeFilter["endDate"]));
                if ($startDate == $endDate) {
                    $result =  $result->where($rangeFilter['column'], '=', $startDate);
                } else {
                    $result = $result->whereBetween($rangeFilter['column'], [$startDate, $endDate]);
                }
            }
        }

        //--Remark Filters
        if (count($remarksIds) > 0) {
            $result =  $result->whereIn('remarks', $remarksIds);
        }
        $result  = $result->get();

        //--Expires Filters
        if (count($buntyFilterArr)) {
            $buntyFiltervalues = [null, 'Expired', 'Expiring Soon', 'Under 60s'];
            $buntyFilterArr = array_flip($buntyFilterArr);
            $intersectVals = array_intersect_key($buntyFiltervalues, $buntyFilterArr);
            $intersectVals = array_values($intersectVals);

            $result = $result->filter(function ($item) use ($intersectVals) {
                return in_array($item->aging_status, $intersectVals);
            });
        }

        return $result;
    }
}
