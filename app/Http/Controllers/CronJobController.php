<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccountReceivable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ARLogs;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;

class CronJobController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * AR daily backup cron job
     *
     *
     */
    public function arDailyBackupCron()
    {
        set_time_limit(0);

        $results = AccountReceivable::select('practice_id', 'facility_id', 'status', DB::raw('COUNT(id) as claim_count'))
            ->whereNotNull('status')
            ->where(function ($query) {
                $query->where('status', '!=', '')
                    ->orWhere('status', ' ');
            })
            ->groupBy('practice_id', 'facility_id', 'status')
            ->get();

        $currDate = Carbon::now()->format('Y-m-d');

        if ($results->count() > 0) {

            $backupExist = DB::table('ar_daily_backup')

                ->whereDate("backup_date", "=", $currDate)

                ->count();
            $addBackupData = [];
            if ($backupExist == 0) {
                foreach ($results as $result) {
                    $addBackupData[] = [
                        'backup_date' => $currDate,
                        'practice_id' => $result->practice_id,
                        'facility_id' => $result->facility_id,
                        'status_id' => $result->status,
                        'claims_count' => $result->claim_count
                    ];
                }
                DB::table('ar_daily_backup')->insert($addBackupData);
                return $this->successResponse(["backup_insert" => true], "success", 200);
            } else {
                foreach ($results as $result) {
                    DB::table('ar_daily_backup')
                        ->whereDate("backup_date", "=", $currDate)
                        ->where('practice_id', $result->practice_id)
                        ->where('facility_id', $result->facility_id)
                        ->where('status_id', $result->status)
                        ->update([
                            'claims_count' => $result->claim_count
                        ]);
                }
                return $this->successResponse(["backup_updated" => true], "success", 200);
            }
        }
    }

    /**
     * cron job un assigned those claims which has aging days greater then 60
     *
     *
     */
    public function unAssignedUsers()
    {
        set_time_limit(0);
        $agingDays = AccountReceivable::whereRaw("DATEDIFF(CURDATE(), cm_account_receivable.dos) = 60")

            ->select("id", "assigned_to", "status", "dos", DB::raw("DATEDIFF(CURDATE(), cm_account_receivable.dos) as aging_days"))

            ->get();

        $assigned = false;
        if (count($agingDays)) {
            foreach ($agingDays as $agingDay) {
                $assignedTo = $agingDay->assigned_to;
                $userName = $this->getUserNameById($assignedTo);
                if ($userName != "Unknown") {
                    $sysLogs = "claim unassigned from  $userName due to reason of 60 days exceeded";
                    // echo $agingDay->id . PHP_EOL;
                    AccountReceivable::where("id", "=", $agingDay->id)

                        ->update(["assigned_to" => NULL]);

                    //add the system generated log into the database
                    ARLogs::insertGetId([
                        'user_id' => 0,
                        'ar_id' => $agingDay->id,
                        'ar_status_id' => $agingDay->status,
                        'details' => $sysLogs,
                        'is_system' => 1,
                        'created_at' => $this->timeStamp()
                    ]);
                }
            }
            $assigned = true;
        }
        return $this->successResponse(["is_unassigned" => $assigned], "success", 200);
    }
}
