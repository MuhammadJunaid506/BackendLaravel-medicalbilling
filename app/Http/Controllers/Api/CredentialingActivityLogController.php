<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\CredentialingActivityLog;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use DB;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Attachments;
use App\Models\Credentialing;
use Illuminate\Mail\Attachment;

class CredentialingActivityLogController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $credentialingActivityLogs = CredentialingActivityLog::select("credentialing_task_logs.*", DB::raw("CONCAT(cm_users_profile.first_name,' ',cm_users_profile.last_name) AS full_name"), "users_profile.picture", "users.name")

                ->leftJoin("users_profile", "users_profile.user_id", "credentialing_task_logs.user_id")

                ->leftJoin("users", "users.id", "credentialing_task_logs.user_id")

                ->orderBy("credentialing_task_logs.id", "DESC")

                ->paginate(20);

            return $this->successResponse(["credentialing_activity_logs" => $credentialingActivityLogs], "Success");
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
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
        $request->validate([
            "credentialing_task_id" => "required",
            "status"                => "required",
            "user_id"               => "required"
        ]);
        $fileRes = NULL;
        $credentialingController = new CredentialingController();


        $credentialingTaskId = $request->credentialing_task_id;
        $lastFollowup        = $request->has("last_follow_up") ? $request->last_follow_up : NULL;
        $nextFollowup        = $request->has("next_follow_up") ? $request->next_follow_up : NULL;
        $status              = $request->status;
        $userId              = $request->user_id;
        $credentialingTask = Credentialing::with(['credentialingassinguser' => function ($query) {
            $query->select(
                'id',
                DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"),
            );
        }])->find($credentialingTaskId);
        $detailLogMsg = null;

        if (!$this->isValidDate($lastFollowup))
            $lastFollowup = NULL;
        if (!$this->isValidDate($nextFollowup))
            $nextFollowup = NULL;

        $statusId = $status;

        if ($request->has("details") && $request->details == "null") {

            $prevData = $this->fetchData("credentialing_tasks", ["id" => $credentialingTaskId], 1, ["credentialing_status_id"]);

            $preStatusId = $prevData->credentialing_status_id;

            $statusPrevRes = $this->fetchData("credentialing_status", ["id" => $preStatusId], 1, ["credentialing_status"]);

            $statusPrevName = $statusPrevRes->credentialing_status;

            $statusCurrRes = $this->fetchData("credentialing_status", ["id" => $status], 1, ["credentialing_status"]);

            $statusCurrName = $statusCurrRes->credentialing_status;

            $details = "Task status changed from " . $statusPrevName . " to " . $statusCurrName;

            $request->details = $details;

            $this->updateData("credentialing_tasks", ["id" => $credentialingTaskId], ["credentialing_status_id" => $statusId, "updated_at" => $this->timeStamp()]);
        } else {
            if (!$request->has("is_automated"))
                $statusId = NULL;
        }

        $addActivityLog = [
            "credentialing_task_id" => $credentialingTaskId,
            "last_follow_up"        => $lastFollowup,
            "next_follow_up"        => $nextFollowup,
            "user_id"               => $userId,
            "credentialing_status_id" => $statusId,
            "correspondence_type" =>  $request->correspondance_type == "null" ? NULL : $request->correspondance_type
        ];



        $fileName = "";
        $file = "";
        $fileLabel = "";
        if ($request->has("image") && $request->image != "null" && $request->image != null) {

            // $path = public_path('storage/activitylogs/attachments/'.$credentialingTaskId);

            $file = $request->file('image');
            $request->merge(["file" => $request->file('image')]);

            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

            $fileLabel = trim($this->removeWhiteSpaces($file->getClientOriginalName()));
            $fileLabel = explode(".", $fileLabel)[0];
            // if ( ! file_exists($path) ) {
            //     mkdir($path, 0777, true);
            // }
            // $file->move($path, $fileName);
            // $this->uploadMyFile($fileName,$file,"credentialing",$credentialingTaskId);
            // $addActivityLog["image"] = $fileName;
        }
        if ($request->has("pdf") && $request->pdf != "null" && $request->pdf != null) {
            // $path = public_path('storage/activitylogs/attachments/'.$credentialingTaskId);
            $file = $request->file('pdf');
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));
        }

        if ($request->has("details") && $request->details != "")
            $addActivityLog["details"] = $request->details;
        else {
            $addActivityLog["details"] = "NULL";
        }

        $addActivityLog["created_at"] = $this->timeStamp();

        $addActivityLog["updated_at"] = "0000-00-00 00:00:00";

        $id = CredentialingActivityLog::insertGetId($addActivityLog);
        if ($request->status != 'undefined') {

            if ($request->status == 0) {
                $detailLogMsg = "Enrollment Created by " . $credentialingTask->credentialingassinguser->user_name ?? '';
            }  else {
                $detailLogMsg = $addActivityLog["details"];
            }

            $credentialingController->storeCredentialingLogs($userId, $credentialingTaskId, $detailLogMsg, $credentialingTask->payer_id, $credentialingTask->user_parent_id, $credentialingTask->user_id);
        } else {
            $credentialingController->storeCredentialingLogs($userId, $credentialingTaskId, 'Enrollment updated', $credentialingTask->payer_id, $credentialingTask->user_parent_id, $credentialingTask->user_id);
        }


        if ($fileName != "") {

            $destFolder = "credentialingEnc/" . $credentialingTaskId . "/activityLog/" . $id;
            $fileRes = $this->encryptUpload($request, $destFolder);

            if (isset($fileRes["file_name"])) {
                $aid = [
                    "entities" => "credentialtasklog_id",
                    "entity_id" => $id,
                    "field_key" => $fileLabel,
                    "field_value" =>  $fileRes["file_name"]
                ];

                $this->addData("attachments", $aid, 0);
                //$this->printR($res,true);
                //CredentialingActivityLog::where("id","=",$id)->update(["attachement_id" => $aid]);
            }
        }
        $taskLogObj = new CredentialingActivityLog();
        $nextFollowUp = $taskLogObj->logNextFollowUp($credentialingTaskId);
        $taskLogObj = NULL;


        return $this->successResponse(["id" => $id, 'next_follow_up' => $nextFollowUp, 'file_res' => $fileRes], "Activity logs added successfully.");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        //try
        {

            $taskLogObj = new CredentialingActivityLog();

            $nextFollowUp = $taskLogObj->logNextFollowUp($id);

            $taskLogObj = NULL;
            // $this->printR($nextFollowUp,true);
            $assingUser = null;
            $assingeUser = Credentialing::where('id', $id)->with(['credentialingassinguser' => function ($query) {
                $query->select(
                    'id',
                    DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"),
                    DB::raw("profile_image as profile_image_url")


                );
            }])->first();
            if ($assingeUser != null) {
                $assingUser = $assingeUser->credentialingassinguser;
            }

            $lastTaskStatus = $this->fetchData("credentialing_tasks", ["id" => $id], 1, ["credentialing_status_id"]);
            $lastStatus = "";
            if (is_object($lastTaskStatus)) {
                $lastStatus = $lastTaskStatus->credentialing_status_id;
            }

            if (is_object($nextFollowUp)) {
                $nextFollowUp = $nextFollowUp->next_follow_up;
            }

            $credsData = [];
            $pagination = [];

            $page = $request->has('page') ? $request->get('page') : 1;

            $perPage = $this->cmperPage;

            $offset = $page - 1;

            // $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

            $newOffset = $perPage * $offset;

            $credentialingActivityLogs = CredentialingActivityLog::select(
                "credentialing_task_logs.*",
                DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS full_name"),
                "attachments.field_key",
                "attachments.field_value",
                "credentialing_status.credentialing_status as status",
                "atch.field_value as profile_image"
            )

                //->leftJoin("users_profile","users_profile.user_id","credentialing_task_logs.user_id")

                ->leftJoin("users", "users.id", "credentialing_task_logs.user_id")

                ->leftJoin("credentialing_status", "credentialing_status.id", "credentialing_task_logs.credentialing_status_id")

                ->leftJoin("attachments", function ($join) {
                    $join->on("attachments.entity_id", "=", "credentialing_task_logs.id")
                        ->where("attachments.entities", "=", "credentialtasklog_id");
                })
                ->leftJoin("attachments as atch", function ($join) {
                    $join->on("atch.entity_id", "=", "credentialing_task_logs.user_id")
                        ->where("atch.entities", "=", "user_id");
                })
                ->where("credentialing_task_id", "=", $id)

                //->latest()

                ->orderBy("credentialing_task_logs.created_at", "DESC")

                ->limit($perPage)

                ->offset($newOffset)

                ->get();




            $statusBar = [];


            if (count($credentialingActivityLogs)) {
                // $date1= "";
                foreach ($credentialingActivityLogs as $key => $activityLogs) {

                    $diff = $this->humanReadableTimeDifference($activityLogs->created_at);

                    $activityLogs->relative_date = $diff;
                    $activityLogs->task_date = date("m/d/Y h:m:s A", strtotime($activityLogs->created_at));

                    $url = "credentialingEnc/" . $id . "/activityLog/" . $activityLogs->id . "/" . $activityLogs->field_value;

                    $activityLogs->file_name = $activityLogs->field_value;

                    $activityLogs->field_value = $url;
                }

                $credentialingActivityLogsArr = $this->stdToArray($credentialingActivityLogs);
                $credsData = $credentialingActivityLogsArr; //$credentialingActivityLogsArr['data'];
                $credsData = array_reverse($credsData);
            }
            $sql = "(SELECT 1 as has_more FROM `cm_credentialing_task_logs` WHERE credentialing_task_id = '$id' AND id < (SELECT MIN(t2.id)
            FROM( SELECT * FROM `cm_credentialing_task_logs` WHERE credentialing_task_id = $id order by id desc limit $perPage offset $newOffset) t2)
            LIMIT 0,1)";
            // echo $sql;
            // exit;
            $hasMore = DB::select($sql);
            $status = $this->fetchData("credentialing_status", "");
            return $this->successResponse(
                [
                    "credentialing_activity_logs"   => $credsData,
                    "status_bar"                    => $statusBar,
                    "credentialing_status"          => $status,
                    "next_follow_up"                => $nextFollowUp,
                    "last_status"                   => $lastStatus,
                    "has_more"                      => count($hasMore) ? true : false,
                    "assing_user" => $assingUser
                ],
                "Success"
            );
        }
        // catch (\Throwable $exception) {
        //     //throw $th;
        //     return $this->errorResponse([],$exception->getMessage(),500);
        // }
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
        $updateData = $request->all();
        // $this->printR($updateData,true);
        $isUpdate = CredentialingActivityLog::where("id", "=", $id)->update($updateData);
        return $this->successResponse(["is_update" => $isUpdate], "success");
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credTaskAVG(Request $request)
    {
        $payerId    = $request->payer_id;

        $credTaksId = $request->cred_taskid;

        $taskStatus = $this->fetchData("credentialing_tasks", ["id" => $credTaksId], 1, ["credentialing_status_id"]);

        $taskAvgData = [];

        // $this->printR($taskStatus,true);
        $canViewAvg = 0;
        $credsLogModel = new CredentialingActivityLog();
        if (is_object($taskStatus) && $taskStatus->credentialing_status_id != 3) {

            $canViewAvg = 1;
            $taskAvgData = $credsLogModel->taskAVG($payerId, $credTaksId);
        } else {
            $taskAvgData = $credsLogModel->consumedDays($credTaksId);
            // $this->printR($taskAvgData,true);
            $taskAvgDataTemp = $taskAvgData[0];
            if ($this->isValidDate($taskAvgDataTemp->effective_date))
                $taskAvgDataTemp->effective_date = date("m/d/Y", strtotime($taskAvgDataTemp->effective_date));
            if ($this->isValidDate($taskAvgDataTemp->termination_date))
                $taskAvgDataTemp->termination_date = date("m/d/Y", strtotime($taskAvgDataTemp->termination_date));

            $taskAvgData[0] = $taskAvgDataTemp;
        }
        return $this->successResponse([
            "creds_avg"     => $taskAvgData,
            "can_view_avg"  => $canViewAvg
        ], "success");
    }
}
