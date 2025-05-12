<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class Attachments extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "attachments";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'component',
        'entity_id',
        'field_key',
        'field_value'
    ];

    /**
     * fetch the all attachments
     *
     * @param $credsTaskId
     */
    function fetchAttachments($credsTaskId) {
        $sql = "SELECT *
                FROM (
                        (
                        SELECT ctl.credentialing_task_id as task_id,ctl.id as logid, a.field_key as title, a.field_value as filename, a.created_at, a.visibility,a.entities
                        FROM `cm_attachments` a
                        INNER JOIN `cm_credentialing_task_logs` ctl
                        ON ctl.id = a.entity_id
                        WHERE a.entities = 'credentialtasklog_id'
                        )
                    UNION ALL
                        (
                        SELECT a.entity_id as task_id,0 as logid, a.field_key as title, a.field_value as filename, a.created_at, a.visibility,a.entities
                        FROM `cm_attachments` a
                        WHERE a.entities = 'credentialtask_id'
                        )
                ) AS T
                WHERE T.task_id = '$credsTaskId'
                AND T.visibility = '1'
                ORDER BY T.filename";
        $attachments = DB::select($sql);
        $attachmentsArr = [];
        if(count($attachments)) {
            foreach($attachments as $attachment) {
                if($attachment->logid) {
                    $url = "credentialingEnc/".$attachment->task_id."/activityLog/".$attachment->logid."/".$attachment->filename;
                    $attachmentsArr[] = ["task_id" => $attachment->task_id,"title" => $attachment->title,"filename" => $attachment->filename,"url" => $url];
                }
                else {
                    if($attachment->entities == "credentialtask_statusapproved") {
                        $url = "credentialingEnc/".$attachment->task_id."/approved/".$attachment->filename;
                        $attachmentsArr[] = ["task_id" => $attachment->task_id,"title" => $attachment->title,"filename" => $attachment->filename,"url" => $url];
                    }
                    else {
                        $url = "credentialingEnc/".$attachment->task_id."/".$attachment->filename;
                        $attachmentsArr[] = ["task_id" => $attachment->task_id,"title" => $attachment->title,"filename" => $attachment->filename,"url" => $url];
                    }
                }
            }
        }
        return $attachmentsArr;

    }

    public function getLeadFileUrlAttribute()  {
        $nestedFolders = "leadAttachments/".$this->entity_id;
        $fileUrl= $nestedFolders . "/" . $this->field_value;
        return $fileUrl;
    }
}
