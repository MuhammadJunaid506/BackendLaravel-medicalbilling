<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Credentialing;
use App\Http\Traits\{ApiResponseHandler, Utility};
use App\Models\{Billing, CredentialingActivityLog, CredentialingLogs, CredentialingStatus, EmpLocationMap,Payer,UserCommonFunc,PracticeLocation};
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Service\Treasury\CreditReversalService;
use App\Models\Report;

class CredentialingDashboardController extends Controller
{
    use ApiResponseHandler, Utility;

    /**
     * Get All Credentialing Dashboard Data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboard(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = EmpLocationMap::select("location_user_id as facility_id")
            ->where('emp_id', $sessionUserId)
            ->get()->pluck('facility_id')->toArray();

        //followsUpToday
        $followsUpToday = Credentialing::with(['credentialinglogs'])
            ->whereHas('credentialinglogs', function ($query) {
                $query->whereDate('next_follow_up', Carbon::now());
            })->whereIn('user_parent_id', $allFacilities)->orderby('id', 'DESC')->count();

        //expiredFollowup
        $expiredFollowup = Credentialing::with(['credentialinglogs'])
            ->whereDoesntHave('credentialinglogs', function ($query) {
                $query->whereDate('next_follow_up', Carbon::now())->orwhereDate('next_follow_up', '>', Carbon::now());
            })
            ->orWhereDoesntHave('credentialinglogs')
            ->whereIn('user_parent_id', $allFacilities)
            ->orderby('id', 'DESC')->count();

        //expired_enrollments
        $payerAvgConsumeDay = Credentialing::WhereNotIn('credentialing_status_id', [3, 6, 8])->whereIn('user_parent_id', $allFacilities)->orderby('id', 'DESC')->get();
        $allPayers = Credentialing::select('payer_id')->whereIn('user_parent_id', $allFacilities)->groupby('payer_id')->get()->toArray();
        foreach ($allPayers as $key => $payer) {
            $payerAvgDay = $this->taskAVG($payer['payer_id']);
            $averageDays = 0;
            if (!empty($payerAvgDay)) {
                $averageDays = (int) $payerAvgDay[0]->average_days;
            }
            $allPayers[$key]['avg_days'] = $averageDays;
        }
        $allPayerIds = array_column($allPayers, 'payer_id');
        $allPayerAvergaeDays = array_column($allPayers, 'avg_days');
        $combinePayerAverageDay = array_combine($allPayerIds, $allPayerAvergaeDays);

        $expiredEnrollments = 0;
        foreach ($payerAvgConsumeDay as $key => $value) {
            $payerAvgConsumedDays = $combinePayerAverageDay[$value->payer_id] ?? 0;
            $todayDate = date('Y-m-d');
            $enrollmentConsumedDays = floor((strtotime($todayDate) - strtotime(date('Y-m-d', strtotime($value->created_at)))) / (60 * 60 * 24));
            $expiredEnrollments += $payerAvgConsumedDays > $enrollmentConsumedDays ? 1 : 0;
        }

        //approvalPercentageQuery
        $approvalPercentageQuery = DB::table("credentialing_tasks")
            ->select(
                DB::raw("(SELECT COUNT(*) FROM `cm_credentialing_tasks` WHERE credentialing_status_id = 3) AS approval"),
                DB::raw("(SELECT COUNT(*) FROM `cm_credentialing_tasks` WHERE credentialing_status_id = 6) AS revalidation")
            )->whereIn('user_parent_id', $allFacilities)
            ->first();
        $totalEnrollment = Credentialing::whereIn('user_parent_id', $allFacilities)->count();
        $totalApproval = $approvalPercentageQuery->approval ?? 0;
        $totalRevalidaton = $approvalPercentageQuery->revalidation ?? 0;

        $approvalPercentadd = $totalApproval + $totalRevalidaton;
        $approvalPercentage = $approvalPercentadd  > 0 ? ($approvalPercentadd / $totalEnrollment) * 100 : 0;


        //approval_enrollment_today
        $approvedTodayEnrollemnt = Credentialing::with(['credentialinglogs'])
            ->whereHas('credentialinglogs', function ($query) {
                $query->where('credentialing_status_id', 3)->whereDate('created_at', Carbon::now());
            })->whereIn('user_parent_id', $allFacilities)->orderby('id', 'DESC')->count();

        //incident_report_enrollments_count
        $incidentReportEnrollmentsCount = DB::table('billing')->select(
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS facility_name"),
            DB::raw('(SELECT CONCAT(first_name, " ", last_name) FROM cm_users WHERE id = cm_billing.render_provider_id) AS provider'),
            'payer.payer_name',
            DB::raw('SUM(CASE WHEN cm_billing.render_provider_id IS NOT NULL THEN 1 ELSE 0 END) AS patient_seen'),
            DB::raw('SUM(CASE WHEN cm_billing.billing_provider_id IS NULL THEN 1 ELSE 0 END) AS patient_seen_facility'),
            DB::raw('SUM(CASE WHEN cm_billing.render_provider_id = cm_billing.billing_provider_id THEN 1 ELSE 0 END) AS self_billed'),
            DB::raw('SUM(CASE WHEN cm_billing.render_provider_id != cm_billing.billing_provider_id THEN 1 ELSE 0 END) AS incident_to'),
            DB::raw('(SELECT credentialing_status FROM cm_credentialing_status WHERE id = cm_ct.credentialing_status_id) AS enrollment_status'),
            DB::raw("
                    CASE
                        WHEN cm_ct.credentialing_status_id = '3'
                            THEN (SELECT created_at FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_ct.id  ORDER BY id DESC LIMIT 0,1)
                    ELSE NULL
                    END AS approved_task_date"),
            'ct.created_at AS enrollment_date',
            'ct.effective_date',
            'ct.revalidation_date',
            'billing.render_provider_id',
            'billing.facility_id',
            'billing.payer_id',
            'ct.credentialing_status_id as enrollment_status_id',
            'billing.dos',
        )
            ->join('user_ddpracticelocationinfo as pli', 'pli.user_id', '=', 'billing.facility_id')
            ->join('payers as payer', 'payer.id', '=', 'billing.payer_id')
            ->join('credentialing_tasks as ct', function ($join) {
                $join->on([
                    ['ct.user_id', '=', 'billing.render_provider_id'],
                    ['ct.user_parent_id', '=', 'billing.facility_id'],
                    ['ct.payer_id', '=', 'billing.payer_id'],
                ]);
            })
            ->whereIn('billing.status_id', [10, 3])
            ->whereIn('billing.facility_id', $allFacilities)
            ->groupBy('billing.facility_id', 'billing.payer_id', 'billing.render_provider_id')
            ->havingRaw('SUM(CASE WHEN cm_billing.render_provider_id != cm_billing.billing_provider_id THEN 1 ELSE 0 END) > 0')
            ->count();


        return $this->successResponse([
            'follow_up_today' => $followsUpToday,
            'expired_follow_up' => $expiredFollowup,
            'expired_enrollments' => $expiredEnrollments,
            'approval_percentage' => $approvalPercentage,
            'approval_enrollment_today' => $approvedTodayEnrollemnt,
            'incident_report_enrollments_count' => $incidentReportEnrollmentsCount,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Today Followups
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardTodayFollowups(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);

            $followsUpToday = Credentialing::select(
                'id',
                'user_id',
                'payer_id',
                'user_parent_id',
                'credentialing_status_id',
           
            )
            ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
            ->whereNotIn('credentialing_status_id', [3,8])
            ->where('payer_id', '!=', null)
            ->whereDate(DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END 
            "),Carbon::now())
            ->orderby('id', 'DESC')
            ->count();
           
        return $this->successResponse([
            'follow_up_today' => $followsUpToday,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Today Followups Detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardTodayFollowupsDetail(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $key = env('AES_KEY');

        $allFacilities = $this->activeFacilities($sessionUserId);
        $todayDate = Carbon::now()->toDateString();
        // $followsUpToday = Credentialing::
        //     select('credentialing_tasks.id', 'credentialing_tasks.user_id', 'credentialing_tasks.payer_id', 'credentialing_tasks.user_parent_id', 'credentialing_tasks.credentialing_status_id',
        //         DB::raw("
        //             CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
        //             ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
        //             END as next_follow_up
        //         "),
        //         DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
        //     )
        //     ->with([
        //         'credentialingpayer:id,payer_name',
        //         'credentialingfacility' => function ($query) use ($key) {
        //             $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"),);
        //         },
        //         'credentialingprovider' => function ($query) {
        //             $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
        //         }, 
        //         'credentialingstatus',
        //     ])
        // ->whereDate(DB::raw("
        //     CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
        //     ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
        //     END 
        // "),Carbon::now())
        // ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        // ->whereNotIn('credentialing_tasks.credentialing_status_id', [3,8])
        // ->where('payer_id', '!=', null)
        // ->get();

        $credentialing = Credentialing::select(
           'credentialing_tasks.id',
           'credentialing_tasks.user_id',
           'credentialing_tasks.payer_id',
           'credentialing_tasks.user_parent_id',
           'credentialing_tasks.credentialing_status_id',
           DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
           DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
           DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
            DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up
            "),
        )
        ->with([
            'credentialingpayer:id,payer_name',
            'credentialingstatus',
        ])
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')
        ->Join('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                ->where('pli.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->WhereNotIn('credentialing_status_id', [3,8])
        ->whereDate(DB::raw("
            CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
            ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
            END 
        "),Carbon::now())
         ->where('payer_id', '!=', null);

        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id',
            'credentialing_tasks.user_id',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.user_parent_id',
            'credentialing_tasks.credentialing_status_id',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
            DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up
            "),
        )
        ->with([
            'credentialingpayer:id,payer_name',
            'credentialingstatus',
        ])
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
        ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
        })
        ->Join('individualprovider_location_map as plm', function ($join) {
            $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', '<>', 0)
        ->whereDate(DB::raw("
            CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
            ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
            END 
        "),Carbon::now())
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->WhereNotIn('credentialing_status_id', [3,8])
        ->where('payer_id', '!=', null);

        $followsUpToday = $credentialing->union($credentialingFacility)->get();

        $followsUpTodayDetail = [];
        foreach ($followsUpToday as $key => $value) {
            $followsUpTodayDetail[] = [
                'id' => $value->id,
                'payer' => $value->credentialingpayer->payer_name ?? null,
                'facility' => $value->practice ?? null,
                'provider' => $value->provider ?? null,
                'status' => $value->credentialingstatus->credentialing_status ?? null,
                'last_followup_date' => $value->last_follow_up_date,
                'next_follow_up' => $value->next_follow_up,
            ];
        }

        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $followsUpTodayDetail = $this->paginateArray($followsUpTodayDetail, $perPage);

        return $this->successResponse([
            'follow_up_today' => $followsUpTodayDetail,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Expired Enrollments
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardExpiredEnrollments(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);


        $credentialing = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.created_at'
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')
        ->Join('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                ->where('pli.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->WhereNotIn('credentialing_status_id', [3,8,6])
        ->where('pr.id', '!=', null);

        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.created_at'
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
        ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
        })
        ->Join('individualprovider_location_map as plm', function ($join) {
            $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', '<>', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->WhereNotIn('credentialing_status_id', [3,8,6])
        ->where('pr.id', '!=', null);

        $payerAvgConsumeDay = $credentialing->union($credentialingFacility)->get();

        $allPayers = Credentialing::select('payer_id')
            ->where('payer_id', '!=', null)
            ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
            ->groupby('payer_id')->pluck('payer_id')->toArray();

        $allPayerAverages = $this->taskAVG($allPayers);
        $allPayerIds = array_column($allPayerAverages, 'payer_id');
        $allPayerAvergaeDays = array_column($allPayerAverages, 'average_days');
        $combinePayerAverageDay = array_combine($allPayerIds, $allPayerAvergaeDays);


        $expiredEnrollments = 0;
        foreach ($payerAvgConsumeDay as $key => $value) {
            $payerAvgConsumedDays = $combinePayerAverageDay[$value->payer_id] ?? 0;
            $todayDate = date('Y-m-d');
            $enrollmentConsumedDays = floor((strtotime($todayDate) - strtotime(date('Y-m-d', strtotime($value->created_at)))) / (60 * 60 * 24));
            $expiredEnrollments +=  $enrollmentConsumedDays > $payerAvgConsumedDays ? 1 : 0;
        }
        return $this->successResponse([
            'expired_enrollments' => $expiredEnrollments,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Expired Enrollments Detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardExpiredEnrollmentsDetail(Request $request)
    {
        $key = env('AES_KEY');
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);


        $credentialing = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.created_at',
            'pr.payer_name AS payer',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
            'cs.credentialing_status AS credential_status',
            DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up_date
            "),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')
        ->Join('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                ->where('pli.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->WhereNotIn('credentialing_status_id', [3,8,6])
        ->where('pr.id', '!=', null);

        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.created_at',
            'pr.payer_name AS payer',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
            'cs.credentialing_status AS credential_status',
            DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up_date
            "),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
        ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
        })
        ->Join('individualprovider_location_map as plm', function ($join) {
            $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', '<>', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->WhereNotIn('credentialing_status_id', [3,8,6])
        ->where('pr.id', '!=', null);

        $payerAvgConsumeDay = $credentialing->union($credentialingFacility)->get();

        $allPayers = Credentialing::select('payer_id')
            ->where('payer_id', '!=', null)
            ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
            ->groupby('payer_id')
            ->pluck('payer_id')->toArray();

        $allPayerAverages = $this->taskAVG($allPayers);
        $allPayerIds = array_column($allPayerAverages, 'payer_id');
        $allPayerAvergaeDays = array_column($allPayerAverages, 'average_days');
        $combinePayerAverageDay = array_combine($allPayerIds, $allPayerAvergaeDays);

        $expiredEnrollments = [];
        foreach ($payerAvgConsumeDay as $key => $value) {
            $payerAvgConsumedDays = $combinePayerAverageDay[$value->payer_id] ?? 0;
            $todayDate = date('Y-m-d');
            $enrollmentConsumedDays = floor((strtotime($todayDate) - strtotime(date('Y-m-d', strtotime($value->created_at)))) / (60 * 60 * 24));
            if ($enrollmentConsumedDays > $payerAvgConsumedDays) {
                $expiredEnrollments[] = [
                    'id' => $value->creds_taskid,
                    'payer' => $value->payer ?? null,
                    'facility' => $value->practice ?? null,
                    'provider' => $value->provider ?? null,
                    'status' => $value->credential_status ?? null,
                    'last_followup_date' => $value->last_follow_up_date ?? null,
                    'next_follow_up' => $value->next_follow_up_date ?? null,
                    'task_consume_days' => $enrollmentConsumedDays,
                ];
            }
        }

        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $expiredEnrollments = $this->paginateArray($expiredEnrollments, $perPage);


        return $this->successResponse([
            'expired_enrollments' => $expiredEnrollments
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Expired Followup
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardExpiredFollowup(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);
            
        $credentialingObj = new Credentialing();
        // $oldestDate = date('Y-m-d', 0);
        $oldestDate = '1900-01-01';
        $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
        $rangeFilter=['status'=>'next_follow_up_date',"startDate"=>$oldestDate,"endDate"=>$yesterdayDate,"is_range_filter"=>true];
        $statusFilter=[0,1,2,4,5,6,7];
        $credentialingTasks =  $credentialingObj->fetchCredentialingOrmAllIds($sessionUserId, null, $statusFilter, [], [], [], null, null, null, $rangeFilter, null, null);
        $expiredFollowup = count($credentialingTasks);

        
        return $this->successResponse([
            'expired_follow_up' => $expiredFollowup,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Expired Followup Detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardExpiredFollowupDetail(Request $request)
    {
        $key = env('AES_KEY');
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);


        $credentialingObj = new Credentialing();
        $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
        $oldestDate = date('Y-m-d', 0);
        $rangeFilter=['status'=>'next_follow_up_date',"startDate"=>$oldestDate,"endDate"=>$yesterdayDate,"is_range_filter"=>true];
        $statusFilter=[0,1,2,4,5,6,7];
        $credentialingTasks =  $credentialingObj->fetchCredentialingOrmAllIds($sessionUserId, null, $statusFilter, [], [], [], null, null, null, $rangeFilter, null, null);
        $allTaskIds = array_column($credentialingTasks,'creds_taskid');

        // $expiredFollowup = Credentialing::select('id', 'user_id', 'payer_id', 'user_parent_id', 'credentialing_status_id')
        // ->with([
        //     'credentialingpayer:id,payer_name',
        //     'credentialingfacility' => function ($query) use ($key) {
        //         $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"),);
        //     }, 'credentialingprovider' => function ($query) {
        //         $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
        //     }, 'credentialingstatus',
        //     'credentialinglogs' => function ($query) {
        //         $query->select(
        //             'id',
        //             'credentialing_task_id',
        //             DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as last_followup_date'),
        //             DB::raw('DATE_FORMAT(next_follow_up, "%Y-%m-%d") AS next_follow_up'),
        //         )->latest('id');
        //     }
        // ])->whereIn('id',$allTaskIds)->paginate($perPage);

        $credentialing = Credentialing::select(
            'credentialing_tasks.id',
            'credentialing_tasks.user_id',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.user_parent_id',
            'credentialing_tasks.credentialing_status_id',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
            DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up
            "),
        )
        ->with([
            'credentialingpayer:id,payer_name',
            'credentialingstatus',
        ])
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')
        ->Join('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                ->where('pli.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->whereIn('credentialing_tasks.id',$allTaskIds)
        ->where('pr.id', '!=', null);
    
        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id',
            'credentialing_tasks.user_id',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.user_parent_id',
            'credentialing_tasks.credentialing_status_id',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
            DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up
            "),
        )
        ->with([
            'credentialingpayer:id,payer_name',
            'credentialingstatus',
        ])
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
        ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
        })
        ->Join('individualprovider_location_map as plm', function ($join) {
            $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', '<>', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->whereIn('credentialing_tasks.id',$allTaskIds)
        ->where('pr.id', '!=', null);

        $expiredFollowup = $credentialing->union($credentialingFacility)->get();

        $expiredFollowUpDetail = [];
        foreach ($expiredFollowup as $key => $value) {
            $expiredFollowUpDetail[] = [
                'id' => $value->id,
                'payer' => $value->credentialingpayer->payer_name ?? null,
                'facility' => $value->practice ?? null,
                'provider' => $value->provider ?? null,
                'status' => $value->credentialingstatus->credentialing_status ?? null,
                'last_followup_date' => $value->last_follow_up_date,
                'next_follow_up' => $value->next_follow_up,
            ];
        }

        // $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        // $expiredFollowUpDetail = $this->paginateArray($expiredFollowUpDetail, $perPage);
        $current_page = (int) request('page', 1);
        return $this->successResponse([
            'expired_follow_up' => ['data' => $expiredFollowUpDetail, 'current_page' => $current_page],
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Approval Percentage
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardApprovalPercentage(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);

        $status = $this->creditinalingTaskStatusOrm($sessionUserId);
        $allApproval = $status['Approved'] ?? 0;
        $allNotEligiiable = $status['Not Eligible'] ?? 0;
        $allEnrollments = $status['total_enrollment'] ?? 0;
        $removeNotEligable = $allEnrollments - $allNotEligiiable;
        $approvalPercentage = ($allApproval / $removeNotEligable) * 100;

        return $this->successResponse([
            'approval_percentage' => $approvalPercentage,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Aapproval EnrollmentToday
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardAapprovalEnrollmentToday(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);

        //approval_enrollment_today
        $approvedTodayEnrollemnt = Credentialing::with(['credentialinglogs'])
            ->where('payer_id', '!=', null)
            ->whereHas('credentialinglogs', function ($query) {
                $query->where('credentialing_status_id', 3)->whereDate('created_at', Carbon::now());
            })->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)->orderby('id', 'DESC')->count();

        return $this->successResponse([
            'approval_enrollment_today' => $approvedTodayEnrollemnt,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Aapproval Enrollment Today Detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardAapprovalEnrollmentTodayDetail(Request $request)
    {

        $key = env('AES_KEY');
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;

        //approval_enrollment_today
        $approvedTodayEnrollemnt = Credentialing::with([
            'credentialingpayer:id,payer_name',
            'credentialingfacility' => function ($query) use ($key) {
                $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"),);
            }, 'credentialingprovider' => function ($query) {
                $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
            }, 'credentialingstatus',
            'credentialinglogs' => function ($query) {
                $query->select(
                    'id',
                    'credentialing_task_id',
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as last_followup_date'),
                    DB::raw('DATE_FORMAT(next_follow_up, "%Y-%m-%d") AS next_follow_up'),
                )->latest('id')->limit(1)->first();
            }
        ])->whereHas('credentialinglogs', function ($query) {
            $query->where('credentialing_status_id', 3)->whereDate('created_at', Carbon::now());
        })->where('payer_id', '!=', null)
            ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
            ->orderby('id', 'DESC')->paginate($perPage);

        $approvedTodayEnrollemntDetail = [];
        foreach ($approvedTodayEnrollemnt as $key => $value) {
            $approvedTodayEnrollemntDetail[] = [
                'id' => $value->id,
                'payer' => $value->credentialingpayer->payer_name ?? null,
                'facility' => $value->credentialingfacility->facility_name ?? null,
                'provider' => $value->credentialingprovider->user_name ?? null,
                'status' => $value->credentialingstatus->credentialing_status ?? null,
                'last_followup_date' => optional($value->credentialinglogs->first())->last_followup_date,
                'next_follow_up' => optional($value->credentialinglogs->first())->next_follow_up,
            ];
        }



        $current_page = (int) request('page', 1);
        return $this->successResponse([
            'approval_enrollment_today' => ['data' => $approvedTodayEnrollemntDetail, 'current_page' => $current_page],
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Incident Report Enrollments
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardincidentReportEnrollments(Request $request)
    {
        $key = env('AES_KEY');
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);

        //incident_report_enrollments_count
        // $incidentReportEnrollmentsCount = DB::table('billing')->select(
        //     'billing.id',
        // )
        //     ->join('user_ddpracticelocationinfo as pli', 'pli.user_id', '=', 'billing.facility_id')
        //     ->join('payers as payer', 'payer.id', '=', 'billing.payer_id')
        //     ->join('credentialing_tasks as ct', function ($join) {
        //         $join->on([
        //             ['ct.user_id', '=', 'billing.render_provider_id'],
        //             ['ct.user_parent_id', '=', 'billing.facility_id'],
        //             ['ct.payer_id', '=', 'billing.payer_id'],
        //         ]);
        //     })
        //     ->whereNotIn('billing.status_id', [3])
        //     ->whereIn('billing.facility_id', $allFacilities)
        //     ->groupBy('billing.facility_id', 'billing.payer_id', 'billing.render_provider_id')
        //     ->havingRaw('SUM(CASE WHEN cm_billing.render_provider_id != cm_billing.billing_provider_id THEN 1 ELSE 0 END) > 0')
        //     ->count();
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        $incidentReportEnrollmentsCount = Billing::select('id', 'facility_id', 'payer_id', 'render_provider_id')->with(['facility', 'payer', 'credentialingtask'])
            ->whereHas('facility', function ($query) {
                return $query->where('id', '!=', null);
            })
            ->whereHas('payer', function ($query) {
                return $query->where('id', '!=', null);
            })
            ->whereHas('credentialingtask', function ($query) {
                $query->whereNotIn('credentialing_status_id',[3,8])
                ->whereColumn('user_parent_id', '=', 'billing.facility_id')
                ->whereColumn('payer_id', '=', 'billing.payer_id');
            })
            ->whereDate('dos', '>=', $threeMonthsAgo)
            ->whereIn('facility_id', $allFacilities)
            ->groupBy('facility_id', 'payer_id', 'render_provider_id')
            ->havingRaw('SUM(CASE WHEN cm_billing.render_provider_id != cm_billing.billing_provider_id THEN 1 ELSE 0 END) > 0')
            ->count();

        return $this->successResponse([
            'incident_report_enrollments_count' => $incidentReportEnrollmentsCount,
        ], 'success');
    }

    /**
     * Get Credentialing Dashboard Incident Report Enrollments Detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboardincidentReportEnrollmentsDetail(Request $request)
    {
        $key = env('AES_KEY');
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        $incidentReportEnrollmentsCount = Billing::select('id', 'facility_id', 'payer_id', 'render_provider_id', 'billing_provider_id')
            ->with([
                'facility' => function ($query) use ($key) {
                    $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"));
                },
                'payer:id,payer_name',
                'provider' => function ($query) {
                    $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
                },
                'billingprovider' => function ($query) {
                    $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
                },
                'credentialingtask:id,user_id,user_parent_id,payer_id,credentialing_status_id'
            ])->whereHas('facility', function ($query) {
                return $query->where('id', '!=', null);
            })->whereHas('payer', function ($query) {
                return $query->where('id', '!=', null);
            })->whereHas('credentialingtask', function ($query) {
                $query->whereNotIn('credentialing_status_id',[3,8])
                ->whereColumn('user_parent_id', '=', 'billing.facility_id')
                ->whereColumn('payer_id', '=', 'billing.payer_id');
            })
            ->whereIn('facility_id', $allFacilities)
            ->whereDate('dos', '>=', $threeMonthsAgo)
            ->groupBy('facility_id', 'payer_id', 'render_provider_id')
            ->whereColumn('billing.render_provider_id', '!=', 'billing.billing_provider_id')
            ->simplePaginate($perPage);

        return $this->successResponse([
            'incident_report_enrollments_count' => $incidentReportEnrollmentsCount,
        ], 'success');
    }

    /**
     * credentialing Dashboar Tasklogs Detail
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboarTasklogsDetail($id)
    {
        $credential = Credentialing::find($id);
        if ($credential == null) {
            return $this->errorResponse([], "No Task Found", 500);
        }
        $credentialObj = new Credentialing();
        $credentialTask = $credentialObj->fetchCredentialingById($id, $credential->user_parent_id);
        return $this->successResponse($credentialTask, 'success');
    }

    /**
     * credentialing Dashboar Task logs of 1 week
     *
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboarTasklogs(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);

        $key = env('AES_KEY');
        $credLogs = CredentialingLogs::with([
            'user' => function ($query) {
                $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as username"), 'profile_image');
            },
            'payer:id,payer_name',
            'facility' => function ($query) use ($key) {
                $query->select('user_id', DB::raw("AES_DECRYPT(practice_name,'$key') as facility_name"));
            },
            'provider' => function ($query) {
                $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
            },
            // 'credentailing:id,assignee_user_id',
            // 'credentailing.credentialingassinguser' => function ($query) {
            //     $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as username"), 'profile_image');
            // }
        ])->orderby('id', 'DESC')
            ->whereIn(DB::raw("( CASE WHEN cm_credentialing_logs.facility_id = '0' THEN cm_credentialing_logs.provider_id ELSE cm_credentialing_logs.facility_id END )"), $allFacilities)
            ->limit(50)
            ->get();
        return $this->successResponse($credLogs, 'success');
    }

    /**
     * credentialing Dashboar Payer Average Days
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingDashboarPayerAverageDays(Request $request)
    {
        $yearAgo = date('Y-m-d', strtotime('-1 year'));
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);


        $allPayers = Credentialing::select('payer_id')->groupby('payer_id')->get()->pluck('payer_id')->toArray();
        $key = env('AES_KEY');

        $credentialing = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.created_at',
            'pr.payer_name AS payer',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS facility"),
            'cs.credentialing_status AS credential_status',
            DB::raw('DATEDIFF((SELECT ctlog.created_at FROM `cm_credentialing_task_logs` ctlog WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id AND ctlog.credentialing_status_id IS NOT NULL 
                    AND ctlog.credentialing_status_id != 0 ORDER BY ctlog.id DESC LIMIT 1),cm_credentialing_tasks.created_at) + 1 as totalday'
            ),
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')
        ->Join('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                ->where('pli.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->where('pr.id', '!=', null)
        ->where('credentialing_status_id', '=', 3)
        ->whereDate(DB::raw("(SELECT created_at FROM `cm_credentialing_task_logs` ctlog  WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id AND ctlog.credentialing_status_id IS NOT NULL AND ctlog.credentialing_status_id != 0  ORDER BY ctlog.id DESC LIMIT 1)"),'>=',$yearAgo);

        // dd($credentialing->get()->toArray());

        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.created_at',
            'pr.payer_name AS payer',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS facility"),
            'cs.credentialing_status AS credential_status',
            DB::raw('DATEDIFF((SELECT ctlog.created_at FROM `cm_credentialing_task_logs` ctlog WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id AND ctlog.credentialing_status_id IS NOT NULL 
                    AND ctlog.credentialing_status_id != 0 ORDER BY ctlog.id DESC LIMIT 1),cm_credentialing_tasks.created_at) + 1 as totalday'
            ),
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
        ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
        })
        ->Join('individualprovider_location_map as plm', function ($join) {
            $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', '<>', 0)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities)
        ->where('pr.id', '!=', null)
        ->where('credentialing_status_id', '=', 3)
        ->whereDate(DB::raw("(SELECT created_at FROM `cm_credentialing_task_logs` ctlog  WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id AND ctlog.credentialing_status_id IS NOT NULL AND ctlog.credentialing_status_id != 0  ORDER BY ctlog.id DESC LIMIT 1)"),'>=',$yearAgo);

        $payerAvgConsumeDay = $credentialing->union($credentialingFacility)->get()->toArray();
        $allPayersIds = array_column($payerAvgConsumeDay,'payer_id');


        $allUniquesPayesIds = array_unique($allPayersIds);
        $getPayersByIds = array_intersect_key($payerAvgConsumeDay,$allUniquesPayesIds);
        $getPayersByIds = array_values($getPayersByIds);
        
        $averageData =[];
        foreach ($getPayersByIds as $key => $value) {
            $payerId = $value['payer_id'];
            $payerAllTasksIds = array_intersect($allPayersIds,[$payerId]);
            $payerAllTasks = array_intersect_key($payerAvgConsumeDay,$payerAllTasksIds);
            $payerAllTasks =array_values($payerAllTasks);
            // if($payerId == 1129)
            //     dd($payerAllTasks);
            $tasks= array_column($payerAllTasks,'totalday');
            $tasks = array_filter($tasks);
            $allTasksCount =  count($tasks);
            $sumAvergaeDays = array_sum($tasks);
            $average = $allTasksCount != 0 ? $sumAvergaeDays / $allTasksCount : 0;
            $lowestConsumeDays='-';
            $highestConsumeDays='-';
            $allMinTask=[];
            $maxAllTask=[];

   
            
            if($allTasksCount > 0){
                $highestConsumeDays = max($tasks);
                if(min($tasks) >0){
                    $lowestConsumeDays = min($tasks);
                    $minValuesArray = array_fill_keys(array_keys($tasks), $lowestConsumeDays);
                    $intersectKeys = array_intersect_assoc($tasks,$minValuesArray);
                    $allMinTask = array_intersect_key($payerAllTasks,$intersectKeys);
                    $allMinTask = array_values($allMinTask);
                }
                $maxArray_values = array_fill_keys(array_keys($tasks), $highestConsumeDays);
                $maxintersectKeys = array_intersect_assoc($tasks,$maxArray_values);
                $maxAllTask = array_intersect_key($payerAllTasks,$maxintersectKeys);
                $maxAllTask = array_values($maxAllTask);
            }
         

            $averageData[]=[
                'payer_id'=>$value['payer_id'],
                'payer_name'=>$value['payer'],
                'average_days'=>round($average),
                'lowest_consume_days' => $lowestConsumeDays,
                'highest_consume_days'=>$highestConsumeDays,
                'lowes_task_detail'=> $allMinTask,
                'highest_task_detail'=>$maxAllTask,
            ];

        }

        usort($averageData, function ($a, $b) {
            return $b['average_days'] <=> $a['average_days'];
        });

        return $this->successResponse($averageData, 'success');
    }

    /**
     * Get All Avergaes of Payers In Credentialing Using Payer Id
     *
     * @param  int $Payerid
     */
    public function taskAVG($payerId)
    {
        // $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
        $yearAgo = date('Y-m-d', strtotime('-1 year'));
        // if (is_array($payerId)) {
        //     $payer = implode(',', $payerId);
        //     $sql = "SELECT T.payer_id,
        //     ROUND(SUM(T.task_log_days) / COUNT(T.id)) as average_days
        //     FROM(
        //     SELECT ct.id, ct.payer_id,
        //         (SELECT DATEDIFF(MAX(created_at), MIN(created_at)) + 1 AS days
        //         FROM `cm_credentialing_task_logs`
        //         WHERE credentialing_task_id = ct.id
        //         GROUP BY credentialing_task_id) as task_log_days
        //     FROM `cm_credentialing_tasks` ct
        //     WHERE ct.payer_id IN ($payer)
        //     AND ct.credentialing_status_id = '3'
        //     AND ct.created_at >= '$sixMonthsAgo') AS T
        //     GROUP BY T.payer_id";
        // } else {  
        //     $sql = "SELECT T.payer_id,
        //         COUNT(T.id), SUM(T.task_log_days),
        //         ROUND(SUM(T.task_log_days) / COUNT(T.id)) as average_days,
        //         (ROUND(SUM(T.task_log_days) / COUNT(T.id))+15) as warning_days,
        //         (ROUND(SUM(T.task_log_days) / COUNT(T.id))+30) as danger_days,
        //             (SELECT (DATEDIFF(CURDATE(), created_at) + 1) as days
        //             FROM `cm_credentialing_task_logs`
        //             WHERE credentialing_status_id = '0'
        //             HAVING MIN(id)) as consumed_days
        //         FROM(
        //         SELECT ct.id, ct.payer_id,
        //             (SELECT DATEDIFF(MAX(created_at), MIN(created_at)) + 1 AS days
        //             FROM `cm_credentialing_task_logs`
        //             WHERE credentialing_task_id = ct.id
        //             GROUP BY credentialing_task_id) as task_log_days
        //         FROM `cm_credentialing_tasks` ct
        //         WHERE ct.payer_id = '$payerId'
        //         AND ct.credentialing_status_id = '3'
        //         AND ct.created_at >= '$sixMonthsAgo'
        //         ) AS T
        //         GROUP BY T.payer_id";
        // }


        $payer = Payer::select('id','payer_name')
        ->with(['tasks'=>function($query) use ($yearAgo){
            $query->select('id','payer_id','credentialing_status_id','created_at',
                DB::raw('DATEDIFF((SELECT created_at
                FROM `cm_credentialing_task_logs` ctlog
                WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id
                AND ctlog.credentialing_status_id IS NOT NULL 
                AND ctlog.credentialing_status_id != 0
                ORDER BY ctlog.id DESC
                LIMIT 1),created_at) + 1 as totalday')
            )->where('credentialing_status_id',3)
            ->whereDate('created_at','>=',$yearAgo);
        }])
        ->whereHas('tasks', function($query) {
            $query->where('credentialing_status_id',3);
        });
        if (is_array($payerId)) {
            $payer= $payer->whereIn('id',$payerId);
        }else{
            $payer= $payer->where('id',$payerId);
        }

        $payer=$payer->get()->toArray();

        $averageData =[];
        foreach ($payer as $key => $value) {
            $tasks= array_column($value['tasks'],'totalday');
            $allTasksCount =  count($tasks);
            $sumAvergaeDays = array_sum($tasks);
            $average = $allTasksCount != 0 ? $sumAvergaeDays / $allTasksCount : 0;
            $averageData[]=[
                'payer_id'=>$value['id'],
                'payer_name'=>$value['payer_name'],
                'average_days'=> round($average),
            ];
        }

        //exit;
        // return DB::select($sql);
        return $averageData;
    }

    public function credentialingtaskOverallStatus(Request $request)
    {
        $credintailingTaskStatus = CredentialingStatus::with('credentialingtask')->get()->toArray();
    }

    /**
     * Paginate The Array
     *
     * @param  array $items
     * @param  int $perPage
     */
    public function paginateArray($items, $perPage)
    {
        $currentPage = request()->get('page', 1); // Get current page from request, default to 1
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = array_slice($items, $offset, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            count($items),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
    }

    public function activeFacilities($sessionUserId)
    {
        $credentialingObj = new Credentialing();
        $facilityIds = $credentialingObj->fetchActiveFacilitiesOfUser($sessionUserId);
        return $facilityIds;
    }

    public function creditinalingTaskStatusOrm($sessionUserId)
    {
        $credentialingObj = new Credentialing();
        $facilityIds = $credentialingObj->fetchActiveFacilitiesOfUser($sessionUserId);

        $key = env('AES_KEY');
        $credentialing = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

            ->Join('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                    ->where('pli.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', 0)
            ->where('pr.id', '!=', null)
            ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds)->groupby('credentialing_status_id');


        $credentialingFacility = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
            ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
            })
            ->Join('individualprovider_location_map as plm', function ($join) {
                $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                    ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', '<>', 0)
            ->where('pr.id', '!=', null)
            ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds)->groupby('credentialing_status_id');

        $results = $credentialing->union($credentialingFacility)->get()->toArray();
        $totalEnrollments = 0;
        $resultsArr = [];
        foreach ($results as $key => $value) {
            $totalEnrollments += $value['count'];
            if (isset($resultsArr[$value['credential_status']])) {
                $resultsArr[$value['credential_status']] += $value['count'];
            } else {
                $resultsArr[$value['credential_status']] = $value['count'];
            }
        }
        $resultsArr['total_enrollment'] = $totalEnrollments;
        return $resultsArr;
    }


    public function credentialFlags(Request $request)
    {
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $allFacilities = $this->activeFacilities($sessionUserId);
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $key = env('AES_KEY');

        $credentialing = Credentialing::select(
            'credentialing_tasks.id',
            'credentialing_tasks.user_id',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.user_parent_id',
            'credentialing_tasks.credentialing_status_id',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS facility_name"),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%d/%m/%Y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up'),
            DB::raw('( SELECT DATEDIFF(MAX(created_at), MIN(created_at)) + 1 AS consumed_days  FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS consumedays'),
            'pr.payer_name'
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

        ->Join('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                ->where('pli.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', 0)
        ->where('pr.id', '!=', null)
        ->where('credentialing_status_id','!=',3)
        ->where('info_required','!=',null)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities);


        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id',
            'credentialing_tasks.user_id',
            'credentialing_tasks.payer_id',
            'credentialing_tasks.user_parent_id',
            'credentialing_tasks.credentialing_status_id',
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS facility_name"),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%d/%m/%Y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up'),
            DB::raw('( SELECT DATEDIFF(MAX(created_at), MIN(created_at)) + 1 AS consumed_days  FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS consumedays'),
            'pr.payer_name'
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
        ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
        })
        ->Join('individualprovider_location_map as plm', function ($join) {
            $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', '<>', 0)
        ->where('pr.id', '!=', null)
        ->where('credentialing_status_id','!=',3)
        ->where('info_required','!=',null)
        ->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $allFacilities);
     
        $enrollemntsTask = $credentialingFacility->union($credentialing)->paginate($perPage);


        if($request->has('column') && $request->column == 'payer'){
            if($request->order == 'desc'){
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortByDesc(function ($task) {
                    return $task->payer_name;
                })->values();
            }else{
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortBy(function ($task) {
                    return $task->payer_name;
                })->values();
            }
            $enrollemntsTask->setCollection($sortedEnrollemntsTask);
        }

        if($request->has('column') && $request->column == 'facility'){
            if($request->order == 'desc'){
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortByDesc(function ($task) {
                    return $task->facility_name;
                })->values();
            }else{
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortBy(function ($task) {
                    return $task->facility_name;
                })->values();
            }
            $enrollemntsTask->setCollection($sortedEnrollemntsTask);
        }

        if($request->has('column') && $request->column == 'provider'){
            if($request->order == 'desc'){
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortByDesc(function ($task) {
                    return $task->provider;
                })->values();
            }else{
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortBy(function ($task) {
                    return $task->provider;
                })->values();
            }
            $enrollemntsTask->setCollection($sortedEnrollemntsTask);
        }

        if($request->has('column') && $request->column == 'consumedays'){
            if($request->order == 'desc'){
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortByDesc(function ($task) {
                    return $task->consumedays;
                })->values();
            }else{
                $sortedEnrollemntsTask = $enrollemntsTask->getCollection()->sortBy(function ($task) {
                    return $task->consumedays;
                })->values();
            }
            $enrollemntsTask->setCollection($sortedEnrollemntsTask);
        }

        // dd($enrollemntsTask->toArray());
        return $this->successResponse($enrollemntsTask, 'success');
    }
    /**
     * fetch the no enrollemnts of the providers against each payer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchNoEnrollemnts(Request $request) {
        
        $reportObj = new Report();
        
        $credentialingObj = new Credentialing();
        
        $key = env('AES_KEY');
        
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        
        // echo "function is working fine:".$sessionUserId;
        $sessionFacilitiesCnt = DB::table('emp_location_map as elm')
        
        ->select(
            "pli.user_parent_id as practice_id","pli.user_id as facility_id",
            DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name"))
        
        ->join("user_ddpracticelocationinfo as pli","pli.user_id","=","elm.location_user_id")
        
        ->where("elm.emp_id",$sessionUserId)
        
        ->count();
        
        $pages = floor($sessionFacilitiesCnt/10);

       
        $sessionFacilities = DB::table('emp_location_map as elm')
        
        ->select(
            "pli.user_parent_id as practice_id","pli.user_id as facility_id",
            DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as facility_name"))
        
        ->join("user_ddpracticelocationinfo as pli","pli.user_id","=","elm.location_user_id")
        
        ->where("elm.emp_id",$sessionUserId)
        
        ->simplePaginate(10);
        
        $sessionFacilitiesArr = $this->stdToArray($sessionFacilities);
        $sessionFacilitiesArr = $sessionFacilitiesArr["data"];
        // $this->printR($sessionFacilitiesArr,true);
        // $practiceIds = array_column($sessionFacilitiesArr,"practice_id");
        // $practiceIds = array_unique($practiceIds);

        // $facilityIds = array_column($sessionFacilitiesArr,"facility_id");
        // $facilityIds = array_unique($facilityIds);

        // $facilityIdsStr = implode(",",$facilityIds);
        
        // $practiceIdsStr = implode(",",$practiceIds);
        // $credsPayers = $reportObj->fetchCredentialingPayers($facilityIdsStr,$practiceIdsStr);
        // $this->printR($sessionFacilitiesArr,true);
        $noEnrollmentArr = [];
        $noEnrollmentReOderArr = [];
        if (count($sessionFacilitiesArr) > 0) {
            
            foreach ($sessionFacilities as $facility) {
                
                $practiceId = $facility->practice_id;
                
                $facilityId = $facility->facility_id;
                
                $credsPayers = $reportObj->fetchCredentialingPayer(1, $facilityId, 1, $practiceId);
                
                $providers = $credentialingObj->fetchProvider($facilityId);

                // $this->printR($providers,true);
                if (count($credsPayers) > 0) {
                    foreach ($providers as $index => $provider) {
                        foreach ($credsPayers as $credPayer) {
                           
                            $staus = $reportObj->getCredentialingProviderStatus($facilityId, $provider->individual_id, $credPayer->id);
                            if(is_null($staus)) {
                                $noEnrollmentArr[$credPayer->payer_name][$facility->facility_name][$provider->individual_id] = $provider->name;
                            }
                            // $this->printR($staus,true);
                        }
                    }
                }
                
            }
        }
        if(count($noEnrollmentArr)) {
            // $this->printR($noEnrollmentArr,true);
            $keys = array_keys($noEnrollmentArr);
            // $this->printR($keys,true);
            foreach($keys as $key) {
                $keys_ = array_keys($noEnrollmentArr[$key]);
                // $this->printR($keys_,true);
                foreach($keys_ as $key_) {
                    $keys__ = array_keys($noEnrollmentArr[$key][$key_]);
                    // $this->printR($provider,true);
                    foreach($keys__ as $key__) {
                        $providerName = $noEnrollmentArr[$key][$key_][$key__];
                        // echo $providerName;
                        // echo PHP_EOL;
                        array_push($noEnrollmentReOderArr,["payer_name" => $key,"facility_name" => $key_ , "provider" => $providerName]);
                    }
                }
            }
        }
        // $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        // $noEnrollmentArr = $this->paginateArray($noEnrollmentReOderArr,$perPage);
        return $this->successResponse(["data" => $noEnrollmentReOderArr , "total" => count($noEnrollmentReOderArr),"pages" => $pages], 'success');

    }
    public function noEnrollmentcredentialOld(Request $request){

        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $key = env('AES_KEY');
        $reportObj = new Report();
        $credentialingObj = new Credentialing();
        $credsPayers = [];
        $facilityArr = [];
        $notEnrolled = 0;
        $isActive=1;
        $appKey =  $this->key;

        $fetchCredentialingUsersLI = $credentialingObj->fetchCredentialingUsersLI(false, "", $sessionUserId);
        $practices= $fetchCredentialingUsersLI['practices'];
        $allPracticesIds = array_column($practices,'facility_id');
        $allPracticesNames =array_column($practices,'doing_buisness_as');
        $combinePractice = array_combine($allPracticesIds,$allPracticesNames);
        $practiceIdStr = implode(',',$allPracticesIds);

        $fetchFacilities = DB::table('user_ddpracticelocationinfo as pli')
        ->select([ 
            DB::raw("AES_DECRYPT(cm_pli.practice_name,'$appKey') as user_name"),
            "pli.user_id as user_id",
            DB::raw("'facility' as type"),
            "user_parent_id"
        ]);
        $fetchFacilities = $fetchFacilities->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            $join->on('elm.location_user_id', '=', 'pli.user_id')
                ->where('elm.emp_id', '=', $sessionUserId);
        });
        $fetchFacilities->join("users as u_facility", function ($join)  {
            $join->on('u_facility.id', '=', 'pli.user_id')
                ->where('u_facility.deleted', '=', 0);
        });
        $fetchFacilities = $fetchFacilities->whereIn("pli.user_parent_id", $allPracticesIds)->get()->toArray();
        $facilityIdArr = $fetchFacilities;
        $allFacilities = array_column($facilityIdArr,'user_id');
        $facilityIdsStr =implode(',',$allFacilities);

        $credsPayers = PracticeLocation::select(
            'user_id',
            'user_ddpracticelocationinfo.user_id as facility_id',
            DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name,'$key') as facility_name")
        )
        ->with(['credentilingtask:id,user_id,user_parent_id,payer_id','credentilingtask.credentialingpayer'=>function($query){
            $query->where('for_credentialing',1);
        }]);

        $credsPayers = $credsPayers
        // ->whereIn("user_ddpracticelocationinfo.user_id",$allFacilities)
        ->whereRaw("cm_user_ddpracticelocationinfo.user_parent_id In ($practiceIdStr) OR cm_user_ddpracticelocationinfo.user_id IN($facilityIdsStr)")
        ->where('user_ddpracticelocationinfo.for_credentialing','=' ,1)
        // ->groupBy('ct.payer_id')
        ->orderBy('facility_id','ASC')
        ->get()->toArray();
        // $credsPayers = $isActive == 1 ? $reportObj->fetchCredentialingPayer($isActive, $facilityIdsStr, 1, $practiceId) :  $reportObj->inActivePayers($isActive, $facilityIdsStr, 1, $practiceId);

        $allPayerFacility = array_column($credsPayers,'facility_id');
        $flipFacilityArr = array_flip($allPayerFacility);
        // dd($allPayerFacility,array_flip($allPayerFacility));
        // dd(array_column($credsPayers[40]['credentilingtask'],'credentialingpayer'));

        $noEnrollmentArr=[];
        $notEligableFacility=[];
        if (count($credsPayers) > 0) {
            foreach ($facilityIdArr as $eachFacility) {

                $facilityId = $eachFacility->user_id;
                $facilityPayers=[];
                if(isset($flipFacilityArr[$facilityId]) && $flipFacilityArr[$facilityId] != null){
                    $facilityArrKey = $flipFacilityArr[$facilityId];
                    $allFacilityTasks = $credsPayers[$facilityArrKey]['credentilingtask'];
                    $allCredentialPayes = array_column($allFacilityTasks,'credentialingpayer');
                    $facilityPayers = array_values(array_intersect_key($allCredentialPayes, array_unique(array_column($allCredentialPayes, 'id'))));
                    
                }
                
                foreach ($facilityPayers as $credSts) {
                    if(isset($credSts['id'])){
                        $staus = $reportObj->getCredentialingStatus($eachFacility->user_id, $credSts['id']);
                        // dd($facilityPayers,$staus);
                        if (!is_object($staus)) {
                            $notEnrolled++;
                            $practice = $combinePractice[$eachFacility->user_parent_id] ?? '';
                            // $noEnrollmentArr[$credSts->id][]=['payer_name'=>$credSts->payer_name, 'facility_id' => $eachFacility->user_id, 'facility_name' => $eachFacility->user_name,'provider'=>'-','practice'=>$practice];
                            $noEnrollmentArr[]=['payer_name'=>$credSts['payer_name'], 'facility_id' => $eachFacility->user_id, 'facility_name' => $eachFacility->user_name,'provider'=>'-','practice'=>$practice];
                        } 
                        if ($staus->credentialing_status == "Not Eligible"){          
                            array_push($notEligableFacility,$facilityId);
                        }
                    }
                }
            }
        }
        $notEligableFacility = array_unique($notEligableFacility);
        $notEligableFacility = array_values($notEligableFacility);


        foreach ($facilityIdArr as $eachFacility) {

            $facilityId = $eachFacility->user_id;
            array_push($facilityArr, ['facility_id' => $facilityId, 'practice_name' => $eachFacility->user_name]);
            $providers = $credentialingObj->fetchReportProviders($facilityId, $isActive);

            $facilityPayers=[];
            if(isset($flipFacilityArr[$facilityId]) && $flipFacilityArr[$facilityId] != null){
                $facilityArrKey = $flipFacilityArr[$facilityId];
                $allFacilityTasks = $credsPayers[$facilityArrKey]['credentilingtask'];
                $allCredentialPayes = array_column($allFacilityTasks,'credentialingpayer');
                // $facilityPayers = $allCredentialPayes;
                $facilityPayers = array_values(array_intersect_key($allCredentialPayes, array_unique(array_column($allCredentialPayes, 'id'))));

            }

            // $facilityPayers = array_unique($facilityPayers,)
            // dd($facilityId,$facilityPayers);
            if (count($facilityPayers) > 0) {
                foreach ($providers as $index => $provider) {
                    foreach ($facilityPayers as $credPayer) {
                        if(isset($credPayer['id'])){
                            $staus = $reportObj->getCredentialingProviderStatus($facilityId, $provider->id, $credPayer['id']);
                            if (!is_object($staus) && !in_array($facilityId,$notEligableFacility)) {
                                $notEnrolled++;
                                $practice = $combinePractice[$eachFacility->user_parent_id] ?? '';
                                // $noEnrollmentArr[$credPayer->id][]=['payer_name'=>$credPayer->payer_name, 'facility_id' => $facilityId, 'facility_name' => $eachFacility->user_name,'provider'=>$provider->name,'practice'=>$practice];
                                $noEnrollmentArr[]=['payer_name'=>$credPayer['payer_name'], 'facility_id' => $facilityId, 'facility_name' => $eachFacility->user_name,'provider'=>$provider->name,'practice'=>$practice];
                            } 
                        }

                    }
                }
            }
        }
      

        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $noEnrollmentArr = $this->paginateArray($noEnrollmentArr,$perPage);
        return $this->successResponse($noEnrollmentArr, "success");
    }

    public function progressByFacilityList(Request $request){

        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $creditinalingTaskStatusFacilityWise = $this->creditinalingTaskStatusFacilityWise($sessionUserId);
        return $this->successResponse($creditinalingTaskStatusFacilityWise, "success");

    }

    public function creditinalingTaskStatusFacilityWise($sessionUserId)
    {

        $credentialingObj = new Credentialing();
        $facilityIds = $credentialingObj->fetchActiveFacilitiesOfUser($sessionUserId);

        $key = env('AES_KEY');
        $credentialing = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
            'pli.user_id as pid',
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),

        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

            ->Join('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                    ->where('pli.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', 0)
            ->groupby('pid')
            ->where('pr.id', '!=', null);

        $credentialingFacility = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
            'pli.user_id as pid',
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
            ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
            })
            ->Join('individualprovider_location_map as plm', function ($join) {
                $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                    ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', '<>', 0)
            ->groupBy('pid')
            ->where('pr.id', '!=', null);

        $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
        $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);

        
        $results = $credentialing->union($credentialingFacility)->get()->toArray();
        $resultsArr = [];
        foreach ($results as $key => $value) {
            if (isset($resultsArr[$value['pid']][$value['credential_status']])) {
                $resultsArr[$value['pid']][$value['credential_status']] += $value['count'];
            } else {
                $resultsArr[$value['pid']]['facility'] = $value['practice'];
                $resultsArr[$value['pid']][$value['credential_status']] = $value['count'];
            }
            if(isset($resultsArr[$value['pid']]["total_enrollment"])){
                $resultsArr[$value['pid']]["total_enrollment"] += $value['count'];
            }else{
                $resultsArr[$value['pid']]["total_enrollment"] = $value['count'];
            }
        }
        $resultsArr = array_values($resultsArr);
        $facilityWise = [];
        foreach($resultsArr as $status){
            $allApproval = $status['Approved'] ?? 0;
            $allNotEligiiable = $status['Not Eligible'] ?? 0;
            $allEnrollments = $status['total_enrollment'] ?? 0;
            $removeNotEligable = $allEnrollments - $allNotEligiiable;
            $approvalPercentage =  $removeNotEligable == 0 ? 0  : ($allApproval / $removeNotEligable) * 100;

            $facilityWise[]=[
                'facility_name'=>$status['facility'],
                'percentage'=>$approvalPercentage,
            ];
        }

        usort($facilityWise, function($a, $b) {
            return $a['percentage']  <=>  $b['percentage'];
        });
        return $facilityWise;
    }

    public function progressByPayerList(Request $request){

        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);
        $credentialingObj = new Credentialing();
        $facilityIds = $credentialingObj->fetchActiveFacilitiesOfUser($sessionUserId);

        $key = env('AES_KEY');
        $credentialing = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
            'pr.id as payer_id',
            'pr.payer_name as payer_name',

        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

        ->Join('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                ->where('pli.for_credentialing', 1);
        })
        ->where('cs.id','!=', 8)
        ->where('credentialing_tasks.user_parent_id', 0)
        ->groupby('credentialing_status_id','payer_id')
        ->where('pr.id', '!=', null);

        $credentialingFacility = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
            'pr.id as payer_id',
            'pr.payer_name as payer_name',
        )
        ->leftJoin('payers as pr', function ($join) {
            $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                ->where('pr.for_credentialing', '=', 1);
        })
        ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
        ->leftJoin('users as cu', function ($join) {
            $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
        })
        ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
        ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
        ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
        ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
            $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
        })
        ->Join('individualprovider_location_map as plm', function ($join) {
            $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
        })
        ->where('credentialing_tasks.user_parent_id', '<>', 0)
        ->groupby('credentialing_status_id','payer_id')
        ->where('cs.id','!=', 8)
        ->where('pr.id', '!=', null);

        $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
        $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);

        
        $results = $credentialing->union($credentialingFacility)->get()->toArray();
        // dd($results);
        $resultsArr = [];
        foreach ($results as $key => $value) {
            if (isset($resultsArr[$value['payer_id']][$value['credential_status']])) {
                $resultsArr[$value['payer_id']][$value['credential_status']] += $value['count'];
            } else {
                $resultsArr[$value['payer_id']]['payer_name'] = $value['payer_name'];
                $resultsArr[$value['payer_id']][$value['credential_status']] = $value['count'];
            }
            if(isset($resultsArr[$value['payer_id']]["total_enrollment"])){
                $resultsArr[$value['payer_id']]["total_enrollment"] += $value['count'];
            }else{
                $resultsArr[$value['payer_id']]["total_enrollment"] = $value['count'];
            }
        }
        $resultsArr = array_values($resultsArr);
        $payerWise = [];
        foreach($resultsArr as $status){
            $allApproval = $status['Approved'] ?? 0;
            $allNotEligiiable = $status['Not Eligible'] ?? 0;
            $allEnrollments = $status['total_enrollment'] ?? 0;
            $removeNotEligable = $allEnrollments - $allNotEligiiable;
            $approvalPercentage = $removeNotEligable == 0 ? 0  : ($allApproval / $removeNotEligable) * 100;

            $payerWise[]=[
                'payer_name'=>$status['payer_name'],
                'percentage'=>$approvalPercentage,
            ];
        }

        usort($payerWise, function($a, $b) {
            return  $a['percentage']  <=>  $b['percentage'];
        });
        return $this->successResponse($payerWise, "success");
    }
}
