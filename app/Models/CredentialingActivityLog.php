<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class CredentialingActivityLog extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "credentialing_task_logs";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "user_id",
        "credentialing_task_id",
        "last_follow_up",
        "next_follow_up",
        "status",
        "details",
        "attachments",
        "created_at",
        "updated_at"
    ];
    /**
     * get the average of credentialing task
     * 
     * @param $payerId
     */
    function taskAVG($payerId,$credTaksId) {
        $sql = "SELECT T.payer_id, 
                COUNT(T.id), SUM(T.task_log_days),
                ROUND(SUM(T.task_log_days) / COUNT(T.id)) as average_days,
                (ROUND(SUM(T.task_log_days) / COUNT(T.id))+15) as warning_days,
                (ROUND(SUM(T.task_log_days) / COUNT(T.id))+30) as danger_days,
                    (SELECT (DATEDIFF(CURDATE(), created_at) + 1) as days
                    FROM `cm_credentialing_task_logs`
                    WHERE credentialing_task_id = '$credTaksId'
                    AND credentialing_status_id = '0'
                    HAVING MIN(id)) as consumed_days
                FROM(
                SELECT ct.id, ct.payer_id,
                    (SELECT DATEDIFF(MAX(created_at), MIN(created_at)) + 1 AS days
                    FROM `cm_credentialing_task_logs`
                    WHERE credentialing_task_id = ct.id
                    GROUP BY credentialing_task_id) as task_log_days
                FROM `cm_credentialing_tasks` ct
                WHERE ct.payer_id = '$payerId'
                AND ct.credentialing_status_id = '3') AS T
                GROUP BY T.payer_id
        ";
        //exit;
        return DB::select($sql);
    }
    /**
     * on approved status consumed datas
     * 
     * @param $taskId
     */
    function consumedDays($taskId) {
        $sql = "SELECT DATEDIFF(MAX(created_at), MIN(created_at)) + 1 AS consumed_days,
                (SELECT effective_date FROM cm_credentialing_tasks WHERE id = cm_credentialing_task_logs.credentialing_task_id) as  effective_date,
                (SELECT identifier FROM cm_credentialing_tasks WHERE id = cm_credentialing_task_logs.credentialing_task_id) as  provider_id,
                (SELECT revalidation_date FROM cm_credentialing_tasks WHERE id = cm_credentialing_task_logs.credentialing_task_id) as  termination_date
                FROM `cm_credentialing_task_logs`
                WHERE credentialing_task_id = '$taskId'
            ";
         return DB::select($sql);
    }
    /**
     * get task next follow date
     * 
     * @param $taskId
     */
    function fetchTaskNextFollowUp($taskId) {
        return DB::table("credentialing_tasks")
        ->selectRaw(DB::raw(
            'CASE WHEN cm_credentialing_tasks.credentialing_status_id = "3"
            THEN  if(cm_credentialing_tasks.revalidation_date IS NOT NULL, DATE_FORMAT(DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY), "%Y-%m-%d"), 	DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 15 DAY), "%Y-%m-%d"))
            ELSE DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 15 DAY), "%Y-%m-%d")
            END AS next_follow_up'
        ))
        ->where("credentialing_tasks.id","=",$taskId)
        ->first();
        // return DB::select(
        //     "SELECT  CASE WHEN ct.credentialing_status_id = '3'
        //     THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
        //     ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%Y-%m-%d') as next_follow_up
        //     FROM `cm_credentialing_task_logs`
        //     WHERE `credentialing_task_id` = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') order by `id` desc limit 0,1)
        //     END AS next_follow_up
        //     FROM cm_credentialing_tasks ct
        //     WHERE ct.id = '$taskId'"
        // );
    }
    /**
     * fetch the new add log next followup date
     * 
     * @param $credsId
     */
    function logNextFollowUp($credsId) {
        return DB::select(
            "SELECT ct.id as credentialing_task_id,
            (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
            CASE WHEN ct.credentialing_status_id = '3'
            THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
            ELSE  (SELECT DATE_FORMAT(next_follow_up, '%m/%d/%Y')
                  FROM cm_credentialing_task_logs
                  WHERE credentialing_task_id = ct.id
                  AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL')
                  ORDER BY id DESC LIMIT 0,1)
            END AS next_follow_up,
            cs.credentialing_status as credential_status,
            DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 15 DAY), '%Y-%m-%d') AS next_follow_up_date
            FROM cm_credentialing_tasks ct
            INNER JOIN cm_credentialing_status cs
            ON cs.id = ct.credentialing_status_id
            WHERE ct.id = '$credsId'"
        );
    }
}
