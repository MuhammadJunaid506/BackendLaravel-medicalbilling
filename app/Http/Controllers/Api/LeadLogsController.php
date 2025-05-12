<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadLogs;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Attachments;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\LeadUserActivity;
class LeadLogsController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $request->validate([
            "lead_id" => "required"
        ]);

        $leadId = $request->lead_id;

        $key    = $this->key;

        $page = $request->has('page') ? $request->get('page') : 1;

        $perPage = $this->cmperPage;

        $offset = $page - 1;


        $newOffset = $perPage * $offset;
        
        $leadLogs    = LeadLogs::where("lead_id", '=', $leadId)

            ->select(
                'lead_logs.lead_id',
                'lead_logs.session_userid',
                'lead_logs.id',
                'lead_logs.lead_status_id',
                DB::raw("AES_DECRYPT(cm_lead_logs.details,'$key') as details"),
                'lead_logs.created_at',
                DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ', COALESCE(cm_u.last_name,'')) as created_by_name"),
                'ls.status as status_name',
                'lead_logs.correspondence_type',
                "atch.field_value as file_name",
            )

            ->join('users as u', 'u.id', '=', 'lead_logs.session_userid', 'left')

            ->join('lead_status as ls', 'ls.id', '=', 'lead_logs.lead_status_id', 'left')

            ->leftJoin("attachments as atch", function ($join) {
                $join->on("atch.entity_id", "=", "lead_logs.id")
                    ->where("atch.entities", "=", "lead_log_id");
            })
            ->orderBy('lead_logs.created_at', 'desc')
            
            ->where("lead_logs.is_deleted","=",0)

            ->limit($perPage)

            ->offset($newOffset)
            

            ->get();



        $leadLogsReOrder = array();
        $hasMore = false;
        if ($leadLogs->count() > 0) {
            foreach ($leadLogs as $leadLog) {
                $leadLog->human_readable_time = $this->humanReadableTimeDifference($leadLog->created_at);
                $timestamp               = strtotime($leadLog->created_at);
                $formattedTimestamp      = date("Y-m-d H:i:s", $timestamp);
                $leadLog->created_at_ts  = $formattedTimestamp;
                $leadLog->lead_file_url  = "leadAttachments/" . $leadLog->id . "/" . $leadLog->file_name;
            }
            $hasMore = $leadLogs->count()  == $perPage ? true : false;
            $leadLogsReOrder = $this->stdToArray($leadLogs);
            $leadLogsReOrder = array_reverse($leadLogsReOrder);
        }
        //$this->printR($leadLogsReOrder,true);
        $leadStatus = $this->fetchData("lead_status");

        $currStatus = DB::table("leads")->where("id", $leadId)->first(["status_id"]);

        $statusId = is_object($currStatus) ? $currStatus->status_id : 1;

        return $this->successResponse([
            'lead_logs'     => $leadLogsReOrder,
            'lead_status'   => $leadStatus, 
            "status_id"     => $statusId, 
            'has_more'      => $hasMore
        ], "success");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            "lead_id"           => "required",
            "status_id"         => "required",
            "session_userid"    => "required"
        ]);
        $leadId         = $request->lead_id;
        $statusId       = $request->status_id;
        $sessionUserId  = $request->session_userid;
        $correspondenceType = isset($request->correspondence_type) ? $request->correspondence_type : NULL;

        $isManual = 0;

        $key            = $this->key;
        $whereStatus = [
            ["id", "=", $leadId]
        ];
        $currentStatus = $this->fetchData("leads", $whereStatus, 1, ["status_id"]);
        $log = "";
        if (is_object($currentStatus)) {
            $prevStatusId = isset($currentStatus->status_id) ? $currentStatus->status_id : 1;
            if ($prevStatusId != $statusId) {
                $whereStatusPrev = [
                    ["id", "=", $prevStatusId]
                ];
                $whereStatusCurr = [
                    ["id", "=", $statusId]
                ];
                $prevStatus = $this->fetchData("lead_status", $whereStatusPrev, 1, ["status"]);
                $currStatus = $this->fetchData("lead_status", $whereStatusCurr, 1, ["status"]);
                $log = "Status changed from " . $prevStatus->status . " to " . $currStatus->status;
                $isManual = 1;
            } else {
                $isManual = 1;
                $log = isset($request->details) ? $request->details : NULL;
            }
        } else {
            $isManual = 1;
            $log = isset($request->details) ? $request->details : NULL;
        }
        $log = str_replace("'", "", $log);
        $addLog = [
            "lead_id"           => $leadId,
            "session_userid"    => $sessionUserId,
            "lead_status_id"    => $statusId,
            "details"           => DB::raw("AES_ENCRYPT('" .    $log     . "', '$key')"),
            "correspondence_type" => $correspondenceType,
            "created_at"        => $this->timeStamp()
        ];
        $currentDate = Carbon::now()->format('Y-m-d');
        $ts = $this->timeStamp();
        $updateLead = ["status_id" => $statusId,"is_manual" => $isManual,"last_followup" => $currentDate,"updated_at" =>$ts];
        if($request->has("profile_complete_percentage")) {
            $updateLead["profile_complete_percentage"] = $request->get("profile_complete_percentage");
        }
        //update the status
        DB::table("leads")

            ->where("id", "=", $leadId)

            ->update($updateLead);

        $id = LeadLogs::insertGetId($addLog);
        
        if ($id) {
            if ($request->has('file')) {
                $this->uploadLeadFile($request, $id);
            }
        }
        //reset the activity recorded against the lead
        LeadUserActivity::where("lead_id", $leadId)->update(["is_msg_seen" => 0]);

        return $this->successResponse(["is_added" => true, 'id' => $id], "Logs updated");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        if ($request->has('details')) {
            $log = $request->details;
            $key = $this->key;
            $log = str_replace("'", "", $log);
            LeadLogs::where("id", '=', $id)
            ->update(["details" => DB::raw("AES_ENCRYPT('" .    $log     . "', '$key')")]);
        }
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        
        $isDel = LeadLogs::where("id", '=', $id)

        ->update(["is_deleted" => 1]);

        return $this->successResponse(["is_deleted" => true, 'id' => $id], "Log deleted successfully");
    }

    /**
     * upload lead attachment
     *
     * @param  request  $request
     * @param  int  $leadID
     * @return boolean
     */
    public function uploadLeadFile($request, $leadLogID)
    {
        try {
            $file = $request->file('file');
            $size = $file->getSize();
            $sizeUnit = $this->fileSizeUnits($size);
            $destFolder = "leadAttachments/" . $leadLogID;
            $fileRes = $this->encryptEachUpload($request, $file, $destFolder);
            if (isset($fileRes['message'])) {
                return $this->errorResponse([], $fileRes['message'], 500);
            }
            $addFileData = [
                "entities"     => "lead_log_id",
                "entity_id"     => $leadLogID,
                "field_key"     => "lead_attachment",
                "field_value"   => $fileRes["file_name"],
                "file_size"     => $sizeUnit,
                "note" => ''
            ];
            $this->addData("attachments", $addFileData, 0);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch Attachemnt against Lead
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchLeadAttachment($id)
    {
        try {

            $leadFile = LeadLogs::select("attachments.id","attachments.field_key","attachments.field_value","attachments.file_size","attachments.created_at","lead_logs.id as log_id")
            ->join("attachments", function($join) {
                $join->on("attachments.entity_id", "=", "lead_logs.id")
                ->where("attachments.entities", "=", "lead_log_id");
            })
            ->where("lead_logs.lead_id", $id)
            ->where("lead_logs.is_deleted","=",0)
            ->get();
            //$licenseAttachments = [];
            $nestedFolders = "leadAttachments/";
            if ($leadFile->count() > 0) {
                foreach ($leadFile as $attachment) {
                    $attachment->file_url = $nestedFolders .$attachment->log_id. "/" . $attachment->field_value;
                    $attachment->human_readable_time = $this->humanReadableTimeDifference($attachment->created_at);
                    //$licenseAttachments[] = $attachment;
                }
            }
            return $this->successResponse(['attachments' => $leadFile], "attachment found successfully");
        } catch (\Exception $e) {
            return $this->errorResponse([], $e->getMessage(), 500);
        }
    }
}
