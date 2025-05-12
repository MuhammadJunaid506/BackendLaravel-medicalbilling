<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\Utility;

class ARBackup extends Model
{
    use HasFactory,Utility;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "account_receivable_daily_backup";

    /**
     * AR trend report
     */
    function arTrendReport($practiceId,$facilityId,$startDate,$endDate,$shelterId = 0) {
        $sql ="SELECT
                DATE_FORMAT(backup_date, '%m/%d/%Y') as report_date,
                COUNT(ardb.claim_no) AS total_claims,
                SUM(ardb.billed_amount) AS total_amount,
                backup_date
              from
                cm_account_receivable_daily_backup ardb
                left join cm_revenue_cycle_status ar on ar.id = ardb.status
                AND ar.considered_as_completed = 0
              where
                date(ardb.backup_date) BETWEEN '$startDate'
                AND '$endDate'
                and ardb.practice_id = '$practiceId'
                and ardb.facility_id = '$facilityId'";

      if($shelterId > 0 )
        $sql .="ardb.shelter_id = '$shelterId'";

      $sql .=" GROUP BY
                backup_date,
                facility_id

              ORDER BY
                backup_date
          ";

        return $this->rawQuery($sql);

    }
    /**
     * AR trend report base row
     */
    function arBaseRow($practiceId,$facilityId,$startDate,$endDate) {
        $sql =" (SELECT
        DATE_FORMAT(backup_date, '%m/%d/%Y') as report_date,
        COUNT(ardb.claim_no) AS total_claims,
        SUM(ardb.billed_amount) AS total_amount
      from
        cm_account_receivable_daily_backup ardb
        left join cm_revenue_cycle_status ar on ar.id = ardb.status
        AND ar.considered_as_completed = 0
      where
        date(ardb.backup_date) = (
          SELECT
            MAX(backup_date)
          FROM
            cm_account_receivable_daily_backup
          WHERE
            DATE_FORMAT(backup_date, '%m') = MONTH('$startDate' - INTERVAL 1 MONTH)
        )
        and ardb.practice_id = '$practiceId'
        and ardb.facility_id = '$facilityId'
        GROUP BY
        backup_date,
        facility_id
        )
      UNION (
        SELECT
        DATE_FORMAT(backup_date, '%m/%d/%Y') as report_date,
        COUNT(ardb.claim_no) AS total_claims,
        SUM(ardb.billed_amount) AS total_amount
      from
        cm_account_receivable_daily_backup ardb
        left join cm_revenue_cycle_status ar on ar.id = ardb.status
        AND ar.considered_as_completed = 0
      where
        date(ardb.backup_date) = (
          SELECT
            MAX(backup_date)
          FROM
            cm_account_receivable_daily_backup
          WHERE
            DATE_FORMAT(backup_date, '%m') = MONTH('$startDate' - INTERVAL 1 MONTH)
        )
        and ardb.practice_id = '$practiceId'
        and ardb.shelter_id = '$facilityId'
        GROUP BY
        backup_date,
        shelter_id
      )
     ";

        return $this->rawQuery($sql);
    }
}
