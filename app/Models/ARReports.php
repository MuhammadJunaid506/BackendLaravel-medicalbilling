<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Http\Traits\Utility;

class ARReports extends Model
{
    use HasFactory,Utility;

    /**
     * fetch ar distribution by reason report
     *
     * @param $practiceId
     * @param $facilityId
     * @param $statusId
     * @param $remarks
     * @param $startDate
     * @param $endDate
     */
    public function arDistributionByReason($practiceId,$facilityId,$statusId,$startDate,$endDate,$isShlter=0,$shelterId=0) {
        $isArchived = 0;
        $tblObj = DB::table("account_receivable")
        ->select(
            DB::raw("COUNT(cm_account_receivable.claim_no) as total_claims"),'revenue_cycle_status.status',
            DB::raw("SUM(cm_account_receivable.billed_amount) as total_billed_amount"),
            DB::raw("SUM(cm_account_receivable.paid_amount) as total_paid_amount")
        )
        ->join('revenue_cycle_status','revenue_cycle_status.id','=','account_receivable.status')
        ->join("users as u_practice", function ($join) {
            $join->on('u_practice.id', '=', 'account_receivable.practice_id')
                ->where('u_practice.deleted', '=', 0);
        })
        ->join("users as u_facility", function ($join) use ($isArchived) {
            $join->on('u_facility.id', '=', 'account_receivable.facility_id')
                ->where('u_facility.deleted', '=', $isArchived);
        })
        ->where('account_receivable.practice_id',$practiceId);
        if($isShlter == 1)
            $tblObj = $tblObj->where('account_receivable.shelter_id',$shelterId)->where('account_receivable.facility_id',$facilityId);

        else
            $tblObj = $tblObj->where('account_receivable.facility_id',$facilityId);

        return $tblObj->where('account_receivable.status',$statusId)
        ->whereBetween('account_receivable.dos',[$startDate,$endDate])
        ->groupBy('account_receivable.status')
        ->get();
    }

    /**
     * fetch ar distribution by user report
     *
     */
    public function arDistributionByUser($searchAgainstDate = "2022-01-01",$user=0) {

      $reportByUser = DB::table('account_receivable_daily_backup as ar')

             ->select(DB::raw("if(cm_u.first_name IS NULL, 'Unassigned', CONCAT(cm_u.first_name, ' ', cm_u.last_name)) as employee"),
              'ar.assigned_to',

              DB::raw("(SELECT COUNT(id) FROM cm_account_receivable_daily_backup
              WHERE assigned_to = cm_ar.assigned_to AND backup_date = '$searchAgainstDate') as open_claims"),

              DB::raw("(SELECT COUNT(id) FROM cm_account_receivable_daily_backup
               WHERE assigned_to = cm_ar.assigned_to AND backup_date = '$searchAgainstDate'
               AND last_followup_date = '$searchAgainstDate') as worked_on"),

              DB::raw("(SELECT COUNT(cm_account_receivable_daily_backup.id)
              FROM cm_account_receivable_daily_backup
              INNER JOIN cm_revenue_cycle_status
              ON cm_revenue_cycle_status.id = cm_account_receivable_daily_backup.status
              AND cm_revenue_cycle_status.considered_as_completed = '1'
              WHERE cm_account_receivable_daily_backup.assigned_to = cm_ar.assigned_to
               AND cm_account_receivable_daily_backup.backup_date = '$searchAgainstDate') as claims_closed"),

              DB::raw("(SELECT COUNT(cm_account_receivable_daily_backup.id)
              FROM cm_account_receivable_daily_backup
              INNER JOIN cm_revenue_cycle_status
              ON cm_revenue_cycle_status.id = cm_account_receivable_daily_backup.status
              AND cm_revenue_cycle_status.status LIKE '%Paid%' WHERE cm_account_receivable_daily_backup.assigned_to = cm_ar.assigned_to
              AND cm_account_receivable_daily_backup.backup_date = '$searchAgainstDate') as claims_paid"),

              DB::raw("(SELECT SUM(cm_account_receivable_daily_backup.paid_amount)
              FROM cm_account_receivable_daily_backup INNER JOIN cm_revenue_cycle_status
              ON cm_revenue_cycle_status.id = cm_account_receivable_daily_backup.status
              AND cm_revenue_cycle_status.status LIKE '%Paid%'
              WHERE cm_account_receivable_daily_backup.assigned_to = cm_ar.assigned_to
              AND cm_account_receivable_daily_backup.backup_date = '$searchAgainstDate')
              as amount_paid")

             )

        ->leftJoin('users as u', 'u.id', '=', 'ar.assigned_to');


        $reportByUser = $reportByUser->where('ar.assigned_to','=',$user);

        $reportByUser = $reportByUser->groupBy('assigned_to')

        ->orderBy('u.first_name')

        ->get();

        return $reportByUser;


    }
    /**
     * fetch the distribution by payer
     *
     * @param $practiceId
     * @param $faciltyId
     * @param $payerId
     */
    function fetchDistributionByPayer($practiceId="2", $faciltyId="2",$payerId="7") {
        $sql = "SELECT M.payer_name, M.payer_id,
        (SELECT COUNT(cm_account_receivable.claim_no)
        FROM cm_account_receivable
        INNER JOIN cm_revenue_cycle_status
        ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
        WHERE cm_account_receivable.payer_id = M.payer_id
        AND cm_account_receivable.practice_id = '$practiceId'
        AND cm_account_receivable.facility_id = '$faciltyId') as total_open_claims,
        (SELECT GROUP_CONCAT(CONCAT(T.status_name,': ',T.claims) SEPARATOR '<br/>')
        FROM(
        SELECT cm_account_receivable.status, cm_revenue_cycle_status.status as status_name, COUNT(cm_account_receivable.claim_no) as claims
        FROM cm_account_receivable
        INNER JOIN cm_revenue_cycle_status
        ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
        WHERE cm_account_receivable.practice_id = '$practiceId'
        AND cm_account_receivable.facility_id = '$faciltyId'
        AND cm_account_receivable.payer_id = '$payerId'
        GROUP BY cm_account_receivable.status
        ORDER BY claims DESC
        LIMIT 0,3
        ) AS T) as top_three_status
        FROM(
        SELECT p.payer_name, ar.payer_id
        FROM `cm_account_receivable` ar
        LEFT JOIN cm_payers p
        ON p.id = ar.payer_id
        GROUP BY payer_id
        ORDER BY p.payer_name
        ) AS M";
        echo $sql;
        exit;
        return $this->rawQuery($sql);

    }
    /**
     * fetch the distribution by payer top three status
     *
     * @param $practiceId
     * @param $faciltyId
     * @param $payerId
     */
    function fetchDistributionByPayerTopThree($practiceId="2", $faciltyId="2",$payerId="7",$startDate,$endDate,$isShlter=0) {
        $andClaus = "AND cm_account_receivable.facility_id = '$faciltyId'";
        if($isShlter == 1)
            $andClaus = "AND cm_account_receivable.shelter_id = '$faciltyId'";

        $sql ="SELECT cm_revenue_cycle_status.status as status, COUNT(cm_account_receivable.claim_no) as count
        FROM cm_account_receivable
        INNER JOIN cm_revenue_cycle_status
        ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
        WHERE cm_account_receivable.practice_id = '$practiceId'
        $andClaus
        AND cm_account_receivable.payer_id = '$payerId'
        AND cm_account_receivable.dos BETWEEN '$startDate' AND '$endDate'
        GROUP BY cm_account_receivable.status
        ORDER BY count DESC
        LIMIT 0,3";
        return $this->rawQuery($sql);
    }
     /**
     * fetch the payer top three status
     *
     * @param $practiceId
     * @param $faciltyId
     * @param $payerId
     */
    function payerTopThree($practiceId="", $faciltyId="",$payerId="",$startDate,$endDate,$isShlter=0) {


        $sql ="(
            SELECT cm_revenue_cycle_status.status as status, COUNT(cm_account_receivable.claim_no) as count
            FROM cm_account_receivable
            INNER JOIN cm_revenue_cycle_status
            ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
            WHERE cm_account_receivable.practice_id IN($practiceId)
            AND cm_account_receivable.facility_id IN($faciltyId)
            AND cm_account_receivable.payer_id = '$payerId'
            AND cm_account_receivable.dos BETWEEN '$startDate' AND '$endDate'
            GROUP BY cm_account_receivable.status
            ORDER BY count DESC
            LIMIT 0,3
        )
        UNION
        (
            SELECT cm_revenue_cycle_status.status as status, COUNT(cm_account_receivable.claim_no) as count
            FROM cm_account_receivable
            INNER JOIN cm_revenue_cycle_status
            ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
            WHERE cm_account_receivable.practice_id IN($practiceId)
            AND cm_account_receivable.shelter_id IN($faciltyId)
            AND cm_account_receivable.payer_id = '$payerId'
            AND cm_account_receivable.dos BETWEEN '$startDate' AND '$endDate'
            GROUP BY cm_account_receivable.status
            ORDER BY count DESC
            LIMIT 0,3
        )
        ";

        return $this->rawQuery($sql);
    }
    /**
     * fetch the payer claims
     *
     * @param $practiceId
     * @param $faciltyId
     * @param $payerId
     */
    function fetchPayerClaims($practiceId="", $faciltyId="",$payerId="",$startDate,$endDate,$isShlter=0) {


        $sql ="(
            SELECT COUNT(cm_account_receivable.claim_no) AS claims
            FROM cm_account_receivable
            INNER JOIN cm_revenue_cycle_status
            ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
            WHERE cm_account_receivable.payer_id = '$payerId'
            AND cm_account_receivable.practice_id IN($practiceId)
            AND cm_account_receivable.facility_id IN($faciltyId)
            AND cm_account_receivable.dos BETWEEN '$startDate' AND '$endDate'
        )
        UNION
        (
            SELECT COUNT(cm_account_receivable.claim_no) AS claims
            FROM cm_account_receivable
            INNER JOIN cm_revenue_cycle_status
            ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
            WHERE cm_account_receivable.payer_id = '$payerId'
            AND cm_account_receivable.practice_id IN($practiceId)
            AND cm_account_receivable.shelter_id IN($faciltyId)
            AND cm_account_receivable.dos BETWEEN '$startDate' AND '$endDate'
        )
        ";

        return $this->rawQuery($sql);
    }
     /**
     * fetch the distribution by payer claims
     *
     * @param $practiceId
     * @param $faciltyId
     * @param $payerId
     */
    function fetchDistributionByPayerClaims($practiceId="2", $faciltyId="2",$payerId="7",$startDate,$endDate,$isShlter=0) {
        $andClaus = "AND cm_account_receivable.facility_id IN($faciltyId)";
        if($isShlter == 1)
            $andClaus = "AND cm_account_receivable.shelter_id IN($faciltyId)";

        $sql ="SELECT COUNT(cm_account_receivable.claim_no) AS claims
        FROM cm_account_receivable
        INNER JOIN cm_revenue_cycle_status
        ON cm_revenue_cycle_status.id = cm_account_receivable.status AND cm_revenue_cycle_status.considered_as_completed = '0'
        WHERE cm_account_receivable.payer_id = '$payerId'
        AND cm_account_receivable.practice_id = '$practiceId'
        $andClaus
        AND cm_account_receivable.dos BETWEEN '$startDate' AND '$endDate'
        ";

        return $this->rawQuery($sql);
    }
}
