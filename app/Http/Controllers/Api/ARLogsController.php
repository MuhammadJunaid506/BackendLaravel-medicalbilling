<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ARLogs;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use Carbon\Carbon;
use DB;
use App\Models\Attachments;
use File;
use App\Models\AccountReceivable;

class ARLogsController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = $request->has('page') ? $request->get('page') : 1;

        $perPage = $this->cmperPage;

        $offset = $page - 1;

        $newOffset = $perPage * $offset;

        $arData = [];

        $arId  = $request->ar_id;
        //
        $arLogs = ARLogs::select(
            'ar_logs.*',
            'revenue_cycle_status.status',
            DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"),
            "atch.field_value as profile_image",
            DB::raw("DATE_FORMAT(cm_ar_logs.created_at,'%m/%d/%Y') AS created_At"),
            'ath.field_value as file_name',
            DB::raw("DATE_FORMAT(cm_ar_logs.next_follow_up,'%m/%d/%Y') AS next_follow_up")
        )

            ->leftJoin('revenue_cycle_status', 'revenue_cycle_status.id', '=', 'ar_logs.ar_status_id')

            ->leftJoin('users', 'users.id', '=', 'ar_logs.user_id')

            ->leftJoin("attachments as atch", function ($join) {
                $join->on("atch.entity_id", "=", "ar_logs.user_id")
                    ->where("atch.entities", "=", "user_id");
            })
            ->leftJoin("attachments as ath", function ($join) {
                $join->on("ath.entity_id", "=", "ar_logs.id")
                    ->where("ath.entities", "=", "ar_log");
            })
            ->where('ar_id', '=', $arId)

            ->limit($perPage)

            ->offset($newOffset)

            ->orderBy("ar_logs.id", "DESC")

            ->get();

        if (count($arLogs)) {
            // $date1= "";
            foreach ($arLogs as $key => $eachLog) {

                $diffForHumans = $this->humanReadableTimeDifference($eachLog->created_at);

                $eachLog->forhuman_created_at = $diffForHumans;

                $eachLog->task_date = date("m/d/Y h:m:s A", strtotime($eachLog->created_at));
            }
            $arLogsArr = $this->stdToArray($arLogs);

            $arData = array_reverse($arLogsArr);
        }
        return $this->successResponse(['ar_logs' => $arData], 'success');
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
            'user_id'       => 'required',
            'ar_id'         => 'required',
            'ar_status_id'  => 'required'
        ]);

        $arStatus = $this->fetchData("revenue_cycle_status", ['status' => $request->ar_status_id], 1, ['id']);
        $addArData = [
            "user_id"       => $request->user_id,
            "ar_id"         => $request->ar_id,
            "ar_status_id"  => is_object($arStatus) ? $arStatus->id : 0,
            "details"       => is_null($request->details) ? NULL : $request->details,
            "next_follow_up" => $request->next_followup,
            "created_at"    => $this->timeStamp()
        ];
        //bellow code for to update the ar last followup date
        AccountReceivable::where('id', '=', $request->ar_id)
            ->update(['last_followup_date' => date('Y-m-d'), 'next_follow_up' =>  $request->next_followup]);

        $newId = ARLogs::insertGetId($addArData);
        if ($request->hasFile('file')) {
            $file = $request->file("file");
            // Get the file size in bytes
            $size = $file->getSize();

            $sizeUnit = $this->fileSizeUnits($size);

            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));
         
            $this->uploadMyFile($fileName, $file, "arlogs/" . $request->ar_id);

            $destFolder = "arlogsEnc/" . $request->ar_id;

            $fileRes = $this->encryptUpload($request,$destFolder);

            if (isset($fileRes["file_name"])) {

                $addFileData = [
                    "entities"     => "ar_log",
                    "entity_id"     => $newId,
                    "field_key"     => "AR log file",
                    "field_value"   => $fileRes["file_name"],
                    "created_by" => $request->user_id,
                    "note" => !is_null($request->note) ? $request->note : NULL,
                    "file_size" => $sizeUnit,
                    "created_at" => $this->timeStamp()
                ];
                $this->addData("attachments", $addFileData, 0);
            }
            //$this->addData("attachments", $addFileData);
        }
        return $this->successResponse(['id' => $newId], 'success');
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    /**
     * fetch the attachments of the ar logs
     * 
     * @param \Illuminate\Http\Request
     *  @param \Illuminate\Http\Response
     */
    function arAttachmentsLogs(Request $request)
    {

        $arId = $request->ar_id;

        $arLogAttachemnts = ARLogs::where('ar_id', '=', $arId)

            ->where('attachments.visibility', '=', 1)

            ->select('attachments.field_key', 'attachments.field_value', 'ar_logs.created_at', 'attachments.file_size')

            ->leftJoin('attachments', function ($join) {
                $join->on('attachments.entity_id', '=', 'ar_logs.id')
                    ->where('attachments.entities', '=', 'ar_log');
            })
            ->orderBy('attachments.created_at', 'DESC')

            ->get();

        // $url = env("STORAGE_PATH");
        $nestedFolders = "arlogsEnc";
        $attachmentsArr = [];
        if (count($arLogAttachemnts)) {
            foreach ($arLogAttachemnts as $eachLog) {


                $diffForHumans = $this->humanReadableTimeDifference($eachLog->created_at);

                $eachLog->forhuman_created_at = $diffForHumans;

                $urlStr  = $nestedFolders . "/" . $arId . "/" . $eachLog->field_value;

                //$fileParts = explode(".",$eachLog->field_value);
                $fileName = $eachLog->field_value;

                $filterFileName = $this->removeUnderScore($eachLog->field_value);

                $fileName = is_null($filterFileName) ? $fileName : $filterFileName;

                $attachmentsArr[] = ["ar_id" => $arId, "title" => $eachLog->field_key, "filename" => $fileName, "url" =>
                $urlStr, 'forhuman_created_at' => $diffForHumans, 'file_size' => $eachLog->file_size, 'created_at' => $eachLog->created_at];
            }
        }
        return $this->successResponse($attachmentsArr, 'success');
    }
    /**
     * combined the logs for AR and Billing
     *  @param \Illuminate\Http\Request
     *  @param \Illuminate\Http\Response
     */
    function fetchCombinedARBillingLogs(Request $request)
    {
        $page = $request->has('page') ? $request->get('page') : 1;

        $perPage =  $request->has('per_page') ? $request->per_page: $this->cmperPage;

        $offset = $page - 1;

        $newOffset = $perPage * $offset;

        $arId = $request->ar_id;

        $claimNo = $request->has('claim_no') ? $request->claim_no : '';

        
        $sql = "SELECT combined_logs.id,combined_logs.user_id, combined_logs.created_at,combined_logs.log_type,
        combined_logs.user_name,combined_logs.status,combined_logs.details,combined_logs.is_system,
        combined_logs.created_at_date,combined_logs.next_follow_up,combined_logs.relation_id,
        combined_logs.profile_image,combined_logs.file_name
        FROM (
            (
            SELECT al.id,al.user_id,al.created_at ,al.type AS log_type,CONCAT(u.first_name, ' ',u.last_name) AS user_name,ars.status,al.details,al.is_system,
            DATE_FORMAT(al.created_at,'%m/%d/%Y') AS created_at_date,DATE_FORMAT(al.next_follow_up,'%m/%d/%Y') AS next_follow_up,al.ar_id AS relation_id,
            attachuser.field_value AS profile_image,attach.field_value AS file_name
            FROM `cm_ar_logs` AS al
            INNER JOIN `cm_account_receivable` AS ar
            ON ar.id = al.ar_id
            LEFT JOIN `cm_users` AS u 
            ON u.id = al.user_id
            LEFT JOIN `cm_revenue_cycle_status` AS ars
            ON ars.id = al.ar_status_id 
            LEFT JOIN `cm_attachments` AS attachuser
            ON attachuser.entity_id = al.user_id AND attachuser.entities='user_id'
            LEFT JOIN `cm_attachments` AS attach
            ON attach.entity_id = al.id AND attach.entities='ar_log'
            WHERE ar.claim_no = '$claimNo'
            )
            UNION ALL
            (
            SELECT bl.id,bl.user_id,bl.created_at,'billing' AS log_type,CONCAT(u.first_name, ' ',u.last_name) AS user_name,bs.status,bl.details,bl.is_system,
            DATE_FORMAT(bl.created_at,'%m/%d/%Y') AS created_at_date,DATE_FORMAT(bl.next_follow_up,'%m/%d/%Y') AS next_follow_up,bl.billing_id AS relation_id,
            attachuser.field_value AS profile_image,attach.field_value AS file_name
            FROM `cm_billing_logs` AS bl
            INNER JOIN `cm_billing` AS billing
            ON billing.id = bl.billing_id
            LEFT JOIN `cm_users` AS u 
            ON u.id = bl.user_id
            LEFT JOIN `cm_revenue_cycle_status` AS bs
            ON bs.id = bl.billing_status_id
            LEFT JOIN `cm_attachments` AS attachuser
            ON attachuser.entity_id = bl.user_id AND attachuser.entities='user_id'
            LEFT JOIN `cm_attachments` AS attach
            ON attach.entity_id = bl.id AND attach.entities='billing_log'
            WHERE billing.claim_no = '$claimNo'
            
            )
        ) AS combined_logs";
        if($request->has("log_type_filter") && isset($request->log_type_filter)) {
            $sql .=" WHERE combined_logs.log_type = '$request->log_type_filter'";
        }
        $sql = $sql . " ORDER BY combined_logs.created_at DESC LIMIT $newOffset,$perPage";
        $combinedLogs = $this->rawQuery($sql);
        if (count($combinedLogs)) {
            foreach ($combinedLogs as $key => $eachLog) {

                $diffForHumans = $this->humanReadableTimeDifference($eachLog->created_at);

                $eachLog->forhuman_created_at = $diffForHumans;

                $eachLog->task_date = date("m/d/Y h:m:s A", strtotime($eachLog->created_at));

                $url ="arlogsEnc/".$arId."/".$eachLog->file_name;
        
                $eachLog->url = $url;
            }
            $biliingLogsArr = $this->stdToArray($combinedLogs);
            $combinedLogs = array_reverse($biliingLogsArr);
        }
        return $this->successResponse(['combined_logs' => $combinedLogs], 'success');
    }
}
