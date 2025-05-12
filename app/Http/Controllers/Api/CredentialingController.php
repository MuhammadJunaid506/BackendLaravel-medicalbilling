<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payer;
use App\Models\Insurance;
use App\Models\Provider;
use App\Models\Credentialing;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\CredentialingActivityLog;
use App\Models\Notifications;
use Illuminate\Support\Facades\DB;
use App\Models\BAF;
use App\Models\User;
use App\Models\Attachments;
use Carbon\Carbon;
use App\Http\Controllers\Api\CredentialingActivityLogController as credentialingActivityLogs;

use App\Http\Controllers\UserCommonFunc as UserCommonFuncApi;
use App\Models\CredentialingLogs;
use App\Models\EmpLocationMap;

class CredentialingController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, credentialingActivityLogs $credentialingActivityLogs)
    {
        set_time_limit(0);
        //try
        {
            //$page = $request->has("page") ? $request->page : 0;
            $credentialingObj = new Credentialing();
            $credsUsersLi = [];
            $linkedUsers = [];
            $linkedcredsLocs = [];
            $listViewType = [];
            $listInnerViewType = [];
            $isActive = $request->has("is_active") ? $request->is_active : 1;
            $sessionUserId = $request->get("session_userid");
            if (!$request->has("user_type")) {


                // $credentialingTasks = $credentialingObj->fetchCredentialingTasks();
                $credentialing_task_id = null;
                if ($request->has('credentialing_task_id')) {
                    $credentialing_task_id = $request->credentialing_task_id;
                }
                $credentialingTasks = $credentialingObj->fetchCredentialingOrm($sessionUserId, $credentialing_task_id);

                // $request->merge([
                //     "for_credentialing" => $isActive
                // ]);
                // $userCommonFuncApiObj = new UserCommonFuncApi();
                // $filters = json_decode($userCommonFuncApiObj->fetchCredsFilters($request)->getContent());
                // $filtersArr = $filters->data;
                $credentialingObj = NULL;
                return $this->successResponse([
                    'credentialing_tasks'           => $credentialingTasks,
                ], "success");
            } elseif ($request->has("user_type") && $request->user_type == "credentialing_users") {

                //$userId = $request->has("user_id") ? $request->userId : 0;
                $filter = $request->has("filter") ? strtolower($request->filter) : "";
                // $this->printR($filter,true);
                $pagination = [];


                $credsUsersLi = $isActive == 1 ? $credentialingObj->fetchCredentialingUsersLI(true, $filter, $sessionUserId) : $credentialingObj->fetchCredentialingUsersLIInActive(true, $filter, $sessionUserId);

                $pagination     = ""; //$credsUsersLi["pagination"];
                $credsUsersLi   = $credsUsersLi["practices"];

                foreach ($credsUsersLi as $credsUserLi) {

                    $credesLocationUsers = $credesLocationUsers = $isActive == 1 ? $credentialingObj->fetchCredentialingUsers($credsUserLi->facility_id, $isActive, $filter, $sessionUserId) : $credentialingObj->fetchCredentialingUsersInActive($credsUserLi->facility_id, $isActive, $filter, $sessionUserId);
                    // $this->printR($credesLocationUsers,true);

                    $linkedcredsLocs[$credsUserLi->facility_id] = $credesLocationUsers;
                    if (count($credesLocationUsers)) {
                        foreach ($credesLocationUsers as $credUser) {
                            //$addUnion = $isActive == 0 && $credUser->for_credentialing == 1 ? 0 : 1;
                            if ($filter != "" && $credUser->is_visible == 1 && $credUser->is_expandable == 1)
                                $linkedUsers[$credUser->facility_id] = $credentialingObj->fetchLinkedUsers($credUser->facility_id, 0, $isActive, 1, $filter);
                            else
                                $linkedUsers[$credUser->facility_id] = $credentialingObj->fetchLinkedUsers($credUser->facility_id, 0, $isActive, 1, "");
                        }
                    } else
                        $linkedUsers[$credsUserLi->facility_id] = [];
                }
                $credentialingObj = NULL;
                return $this->successResponse([
                    "linked_credentialing_users"    => $linkedUsers,
                    "pagination"                    => $pagination,
                    "creds_li"                      => $credsUsersLi,
                    "creds_locs"                    => $linkedcredsLocs,
                    "listview_types"                => $listViewType,
                    "listinnerview_type"            => $listInnerViewType

                ], "success");
            } elseif ($request->has("user_type") && $request->user_type == "credentialing_tasks") {
                $userId = $request->has("user_id")   ? $request->user_id : 0;
                $filter = $request->has("filter")    ? $request->filter : "";
                $parentId = $request->has("parent_id") ? $request->parent_id : "";
                $parentId = $parentId == "undefined" ? "" : $parentId;

                $rangeFilter = $request->has("range_filter") ? $request->range_filter : "";
                if (strlen($rangeFilter) > 2) {
                    $rangeFilter = json_decode($rangeFilter, true);
                } else
                    $rangeFilter = "";

                $statusFilter = $request->has("status_filter") ? $request->status_filter : "";
                if (strlen($statusFilter) > 2) {
                    $statusFilter = json_decode($statusFilter, true);
                } else
                    $statusFilter = "";



                $payerFilter = $request->has("payer_filter") ? $request->payer_filter : "";
                if (strlen($payerFilter) > 2) {
                    $payerFilter = json_decode($payerFilter, true);
                    $payerFilter = array_column($payerFilter, "value");
                } else
                    $payerFilter = "";

                $facilityFilter = $request->has("facility_filter") ? $request->facility_filter : "";
                if (strlen($facilityFilter) > 2) {
                    $facilityFilter = json_decode($facilityFilter, true);
                    // $this->printR($facilityFilter,true);
                    $facilityFilter = array_column($facilityFilter, "value");
                } else
                    $facilityFilter = "";

                $hasFacilty = false;
                $providerFilter = $request->has("provider_filter") ? $request->provider_filter : "";
                if (strlen($providerFilter) > 2) {
                    $providerFilter = json_decode($providerFilter, true);
                    // $this->printR($providerFilter,true);
                    $hasString = $this->arrayContains($providerFilter, "Facility", "label");
                    if ($hasString) {
                        $hasFacilty = true;
                        $providerFilter = array_column($providerFilter, "value");
                    } else
                        $providerFilter = array_column($providerFilter, "value");
                } else
                    $providerFilter = "";


                // $this->printR($facilityFilter,true);
                $assigneeFilter = $request->has("assignee_filter") ? $request->assignee_filter : "";

                $nexlastFollowupCol = $request->has("nextlast_followup_col") ? $request->nextlast_followup_col : "";

                $nexlastFollowupVal = $request->has("nextlast_followup_val") ? $request->nextlast_followup_val : "";

                $credentialing_task_id = null;
                if ($request->has('credentialing_task_id')) {
                    $credentialing_task_id = $request->credentialing_task_id;
                }
                // $credentialingTasks = $credentialingObj->fetchCredentialingTasks($userId, $filter, $parentId, $rangeFilter, $statusFilter, $assigneeFilter, $payerFilter, $facilityFilter, $providerFilter, $hasFacilty, $nexlastFollowupCol, $nexlastFollowupVal);
                $credentialingTasks =  $credentialingObj->fetchCredentialingOrm($sessionUserId, $credentialing_task_id, $statusFilter, $payerFilter, $facilityFilter, $providerFilter, $hasFacilty, $assigneeFilter, $filter, $rangeFilter, $nexlastFollowupCol, $nexlastFollowupVal);
                // dd($credentialingTasks);


                // if($request->has("user_id")) {
                //     $request->merge([
                //         "is_single" => 1,
                //         'location_id' => $userId,
                //         'is_active' => $isActive,

                //     ]);
                // }
                // if($request->has("facility_filter") && is_array($facilityFilter)) {
                //     // echo implode(",",$facilityFilter);
                //     // exit;
                //     // $this->printR($facilityFilter,true);
                //     $request->merge([
                //         "is_multi" => 1,
                //         'location_id' => implode(",",$facilityFilter),
                //         'is_active' => $isActive

                //     ]);
                // }
                // $userCommonFuncApiObj = new UserCommonFuncApi();
                // $filters = json_decode($userCommonFuncApiObj->fetchSelectedLocationProviders($request)->getContent());
                // $filtersArr = $filters->data;
                // //$this->printR($filtersArr,true);
                // $providers = isset($filtersArr->filtered_providers) ? $filtersArr->filtered_providers : [];
                $credentialingObj = NULL;
                return $this->successResponse([
                    'credentialingtask_pagination'  => $credentialingTasks
                ], "success");
            } elseif ($request->has("user_type") && $request->user_type == "credentialing_status") {
                $userId = $request->has("user_id")   ? $request->user_id : 0;
                $filter = $request->has("filter")    ? $request->filter : "";
                $parentId = $request->has("parent_id") ? $request->parent_id : "";
                $parentId = $parentId == "undefined" ? "" : $parentId;

                $rangeFilter = $request->has("range_filter") ? $request->range_filter : "";
                if (strlen($rangeFilter) > 2) {
                    $rangeFilter = json_decode($rangeFilter, true);
                } else
                    $rangeFilter = "";

                $statusFilter = $request->has("status_filter") ? $request->status_filter : "";
                if (strlen($statusFilter) > 2) {
                    $statusFilter = json_decode($statusFilter, true);
                } else
                    $statusFilter = "";

                $payerFilter = $request->has("payer_filter") ? $request->payer_filter : "";
                if (strlen($payerFilter) > 2) {
                    $payerFilter = json_decode($payerFilter, true);
                    $payerFilter = array_column($payerFilter, "value");
                } else
                    $payerFilter = "";

                $facilityFilter = $request->has("facility_filter") ? $request->facility_filter : "";
                if (strlen($facilityFilter) > 2) {
                    $facilityFilter = json_decode($facilityFilter, true);
                    // $this->printR($facilityFilter,true);
                    $facilityFilter = array_column($facilityFilter, "value");
                } else
                    $facilityFilter = "";

                $hasFacilty = false;
                $providerFilter = $request->has("provider_filter") ? $request->provider_filter : "";
                if (strlen($providerFilter) > 2) {
                    $providerFilter = json_decode($providerFilter, true);
                    // $this->printR($providerFilter,true);
                    $hasString = $this->arrayContains($providerFilter, "Facility", "label");
                    if ($hasString) {
                        $hasFacilty = true;
                        $providerFilter = array_column($providerFilter, "value");
                    } else
                        $providerFilter = array_column($providerFilter, "value");
                } else
                    $providerFilter = "";


                // $this->printR($facilityFilter,true);
                $assigneeFilter = $request->has("assignee_filter") ? $request->assignee_filter : "";


                // $credentialingTasksStatus = $credentialingObj->fetchCredentialingTasksStatus($userId, $filter, $parentId, $rangeFilter, $statusFilter, $assigneeFilter, $payerFilter, $facilityFilter, $providerFilter, $hasFacilty);
                $credentialingTasksStatus = $credentialingObj->creditinalingTaskStatusOrm($sessionUserId, $statusFilter, $payerFilter, $facilityFilter, $providerFilter, $hasFacilty, $assigneeFilter, $filter, $rangeFilter);
                // dd($credentialingTasksStatus);

                $credentialingObj = NULL;
                return $this->successResponse([
                    'credentialingtask_status'  => $credentialingTasksStatus,
                ], "success");
            }
        }
        // catch (\Throwable $exception) {
        //     return $this->errorResponse([], $exception->getMessage(), 500);
        // }
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
            "practice"   => "required",
            "provider"       => "required",
            "payer"      => "required"
        ]);
        $sessionUserId = $request->has('session_userid') ? $request->session_userid : 0;
        if (isset($request->user)) {
            $sessionUserId = $request->user->id;
        }
        try {
            $payers      = json_decode($request->payer, true);
            $providers   = json_decode($request->provider, true);
            $practices   = json_decode($request->practice, true);
            $assignee    = json_decode($request->assignee_users, true);
            $assignee    = $assignee[0]["value"];
            $summary    = [];

            //$this->printR($practices,true);
            if (count($practices)) {
                foreach ($practices as $practice) {
                    $practiceId = $practice["value"];
                    $practiceName = $practice["label"];

                    foreach ($providers as $provider) {
                        $providerId = $provider["value"];
                        $providerName = $provider["label"];

                        foreach ($payers as $payer) {
                            $payerId = $payer["value"];
                            $payerName = $payer["label"];
                            // echo "practice:".$practiceId;
                            // echo "<br/>";
                            // echo "provider:".$providerId;
                            // echo "<br/>";
                            // echo "payer:".$payerId;
                            if ($providerId == 0) {
                                $taskExist = Credentialing::where(
                                    [
                                        ["user_id", "=", $practiceId],
                                        ["user_parent_id", "=", 0],
                                        ["payer_id", "=", $payerId]
                                    ]
                                )
                                    ->count();
                            } else {
                                $taskExist = Credentialing::where(
                                    [
                                        ["user_id", "=", $providerId],
                                        ["user_parent_id", "=", $practiceId],
                                        ["payer_id", "=", $payerId]
                                    ]
                                )
                                    ->count();
                            }

                            if ($taskExist == 0) {
                                if ($providerId == 0) {
                                    $data = $this->createNewTask(0, $practiceId, $payerId, $sessionUserId, $assignee);
                                    if ($data['task_created'] == true)
                                        array_push($summary, ["practice_name" => $practiceName, "provider_name" => $providerName, "payer_name" => $payerName, 'is_already' => false, "is_created" => true, 'data' => $data]);
                                } else {
                                    $data = $this->createNewTask($practiceId, $providerId, $payerId, $sessionUserId, $assignee);
                                    if ($data['task_created'] == true)
                                        array_push($summary, ["practice_name" => $practiceName, "provider_name" => $providerName, "payer_name" => $payerName, 'is_already' => false, "is_created" => true, 'data' => $data]);
                                }
                            } else {
                                array_push($summary, ["practice_name" => $practiceName, "provider_name" => $providerName, "payer_name" => $payerName, 'is_already' => true, "is_created" => false]);
                            }
                        }
                    }
                }
                return $this->successResponse(["is_done" => true, 'summary' => $summary], "success");
            } else
                return $this->successResponse(["is_done" => false, 'summary' => $summary], "success");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * create the specific provider tasks
     */
    function providerCredentialingTask(Request $request)
    {

        $payers = json_decode($request->payers, true);

        $assigneeUser = json_decode($request->assignee_user, true);

        $assignee    = $assigneeUser[0]["value"];

        $providerId = $request->provider_id;

        $practices   = json_decode($request->practice, true);

        $user = $this->fetchData("users", ['id' => $providerId], 1, ['first_name', 'last_name']);
        // $this->printR($user,true);
        $providerName = is_object($user) ? $user->first_name . " " . $user->last_name : "";
        $summary = [];
        $sessionUserId = $request->has('session_userid') ? $request->session_userid : 0;
        if (isset($request->user)) {
            $sessionUserId = $request->user->id;
        }
        // $this->printR($request->user,true);
        if (count($practices)) {
            foreach ($practices as $practice) {
                $practiceId = $practice["value"];
                $practiceName = $practice["label"];
                if ($practiceId != 0) {
                    foreach ($payers as $payer) {
                        $payerId = $payer["value"];
                        $payerName = $payer["label"];


                        $taskExist = Credentialing::where(
                            [
                                ["user_id", "=", $providerId],
                                ["user_parent_id", "=", $practiceId],
                                ["payer_id", "=", $payerId]
                            ]
                        )
                            ->count();


                        if ($taskExist == 0) {
                            if ($providerId == 0) {
                                $data = $this->createNewTask(0, $practiceId, $payerId, $sessionUserId, $assignee);
                                if ($data['task_created'] == true)
                                    array_push($summary, ["practice_name" => $practiceName, "provider_name" => $providerName, "payer_name" => $payerName, 'is_already' => false, "is_created" => true, 'data' => $data]);
                            } else {
                                $data = $this->createNewTask($practiceId, $providerId, $payerId, $sessionUserId, $assignee);
                                if ($data['task_created'] == true)
                                    array_push($summary, ["practice_name" => $practiceName, "provider_name" => $providerName, "payer_name" => $payerName, 'is_already' => false, "is_created" => true, 'data' => $data]);
                            }
                        } else {
                            array_push($summary, ["practice_name" => $practiceName, "provider_name" => $providerName, "payer_name" => $payerName, 'is_already' => true, "is_created" => false]);
                        }
                    }
                }
            }
        }
        return $this->successResponse(["is_done" => true, 'summary' => $summary], "success");
    }
    /**
     * create the new task of the credentailing
     *
     * @param $practiceId
     * @param $userId
     * @param $payerId
     * @param $sessionUserId
     * @return result{*}
     */
    private function createNewTask($practiceId, $userId, $payerId, $sessionUserId, $assignee)
    {
        if ($practiceId != 0) {
            $isLinked = $this->isLinkedWithLocation($practiceId, $userId);
            if ($isLinked == true)
                return $this->addCredentialingTask($practiceId, $userId, $payerId, $sessionUserId, $assignee);
            else
                return ["task_created" => false, 'res' => []];
        } else {
            return $this->addCredentialingTask($practiceId, $userId, $payerId, $sessionUserId, $assignee);
        }
    }
    /**
     * check user linked with location or group
     *
     * @param $locationId
     * @param $providerId
     */
    private function isLinkedWithLocation($locationId, $providerId)
    {
        $whereUser = [
            ["location_user_id", "=", $locationId],
            ["user_id", "=", $providerId]
        ];
        $linked = $this->fetchData("individualprovider_location_map", $whereUser, 1);
        return is_object($linked) ? true : false;
    }
    /**
     * add credentailing taks
     *
     * @param $practiceId
     * @param $userId
     * @param $payerId
     * @param $sessionUserId
     * @return result{*}
     */
    private function addCredentialingTask($practiceId, $userId, $payerId, $sessionUserId, $assignee)
    {


        $key = env('AES_KEY');
        $addTask = [
            "user_id"        => $userId,
            "user_parent_id" => $practiceId,
            "payer_id"       => $payerId,
            "credentialing_status_id" => 0,
            "created_at" => $this->timeStamp(),
            "updated_at" => "0000-00-00 00:00:00",
            "assignee_user_id" => $assignee
        ];

        // dd($addTask);
        // $this->assignTask($newTaskId);

        $newTaskId = Credentialing::insertGetId($addTask);

        $nextFollowUp = date("Y-m-d");
        $addNewTaskLog = [
            "user_id"                   => $sessionUserId,
            "credentialing_task_id"     => $newTaskId,
            "next_follow_up"            => $nextFollowUp,
            "last_follow_up"            => NULL,
            "status"                    => 0,
            "details"                   => "Task created",
            "is_automated"              => 1
        ];

        $request = new Request();
        $request->merge($addNewTaskLog);
        $credActivityLogObj = new credentialingActivityLogs();
        $res = $credActivityLogObj->store($request)->getContent();

        $logDetail = "Enrollment created";
        // $this->storeCredentialingLogs($sessionUserId, $newTaskId, $logDetail, $payerId, $practiceId, $userId);

        $res = json_decode($res, true);


        return ["task_created" => true, 'res' => $res];
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        // try
        {
            if ($request->has("has_notify") && $request->has_notify) {

                $notifications = Notifications::where("provider_id", "=", $id)

                    ->where("task_id", "=", $request->task_id)

                    ->get();
                $notificationArr = [];
                if (count($notifications)) {
                    foreach ($notifications as $notification) {
                        $notificationArr[$notification->log_id] = json_decode($notification->details);
                    }
                }

                $credentialingTasks = Credentialing::select(
                    "providers.provider_type",
                    "providers.legal_business_name",
                    "providers.provider_name",
                    "credentialing_tasks.id",
                    "insurances.payer_name",
                    "credentialing_tasks.created_at",
                    "credentialing_tasks.provider_id",
                    "credentialing_tasks.group_provider"
                )

                    ->leftJoin("providers", "providers.id", "credentialing_tasks.provider_id")

                    ->leftJoin("insurances", "insurances.id", "credentialing_tasks.insurance_id")

                    ->where("credentialing_tasks.provider_id", "=", $id)

                    ->where("credentialing_tasks.id", "=", $request->task_id)

                    ->get();

                $credentialingTasksData = [];
                $activityLogs = [];
                $providersData = [];

                if (count($credentialingTasks) > 0) {
                    $credentialingTasksArr = $this->stdToArray($credentialingTasks);

                    // $this->printR($credentialingTasksArr[0],true);
                    $credentialingTaskId = $credentialingTasksArr[0]['id']; //getting first record id as it's activity logs can be fetched.

                    //getting the activity loag og first record
                    $credentialingActivityLogs = CredentialingActivityLog::select("credentialing_task_logs.*", DB::raw("CONCAT(cm_users_profile.first_name,' ',cm_users_profile.last_name) AS full_name"), "users_profile.picture", "users.name")

                        ->leftJoin("users_profile", "users_profile.user_id", "credentialing_task_logs.user_id")

                        ->leftJoin("users", "users.id", "credentialing_task_logs.user_id")

                        ->where("credentialing_task_id", "=", $credentialingTaskId)

                        ->orderBy("credentialing_task_logs.id", "ASC")

                        ->get();

                    $activityLogs = $credentialingActivityLogs;

                    $logsStatus = [];
                    $logsStatusDates = [];
                    if (count($credentialingActivityLogs)) {

                        $credentialingActivityLogsArr = $this->stdToArray($credentialingActivityLogs);

                        $logsStatus = array_column($credentialingActivityLogsArr, "status");
                        $logsStatusDates = array_column($credentialingActivityLogsArr, "created_at");
                        //$this->printR($status,true);
                    }

                    $providerIds = array_column($credentialingTasksArr, "provider_id");

                    $providerIds = array_unique($providerIds);

                    $providersData = Provider::whereIn("id", $providerIds)

                        ->get();

                    // $this->printR($providersData,true);
                    foreach ($providersData as $provider) {
                        $providerId = $provider->id;
                        foreach ($credentialingTasks as $index => $task) {
                            if ($providerId == $task->provider_id) {
                                $credentialingTasksData[$providerId][] = $task;
                            }
                        }
                    }
                }
                return $this->successResponse([
                    "providers" => $providersData, "credentialing_tasks" => $credentialingTasksData,
                    "credentialing_tasks_activity_logs" => $activityLogs,
                    'logs_status' => $logsStatus, "logs_Status_dates" => $logsStatusDates,
                    "notification" => $notificationArr
                ], "Success");
            } else {

                // $groupUsers = User::select("id")->where("parent_id","=",$id)->get();
                // $groupUsersArr = $this->stdToArray($groupUsers);
                // $groupUsersArr = array_column($groupUsersArr,"id");
                // $this->printR($groupUsersArr,true);
                $notifications = Notifications::where("provider_id", "=", $id)

                    // ->where("task_id","=",$request->task_id)

                    ->get();

                $notificationArr = [];
                if (count($notifications)) {
                    foreach ($notifications as $notification) {
                        $notificationArr[$notification->log_id] = json_decode($notification->details);
                    }
                }

                // $credentialingTasks = Credentialing::select(
                //     "user_baf_practiseinfo.provider_type",
                //     "user_baf_practiseinfo.legal_business_name",
                //     "user_baf_practiseinfo.provider_name",
                //     "credentialing_tasks.id",
                //     "insurances.payer_name",
                //     "credentialing_tasks.created_at",
                //     "credentialing_tasks.user_id",
                //     "credentialing_tasks.user_parent_id",
                //     "user_dd_individualproviderinfo.first_name"
                // )

                //     ->leftJoin("user_baf_practiseinfo", function($join) {
                //         //"user_baf_practiseinfo.user_id","=", "credentialing_tasks.user_id"
                //         $join->on('user_baf_practiseinfo.user_id', '=', 'credentialing_tasks.user_id')
                //         ->orWhere("user_baf_practiseinfo.user_id","credentialing_tasks.user_parent_id");
                //         // $join->on('user_baf_practiseinfo.user_id', '=', 'credentialing_tasks.user_parent_id');
                //     })
                //     ->leftJoin("user_dd_individualproviderinfo", function($join) {
                //         //"user_baf_practiseinfo.user_id","=", "credentialing_tasks.user_id"
                //         $join->on('user_dd_individualproviderinfo.user_id', '=', 'credentialing_tasks.user_id');
                //     })
                //     ->leftJoin("insurances", "insurances.id","=", "credentialing_tasks.payer_id")

                //     ->where("credentialing_tasks.user_parent_id","=",$id)

                //     ->orWhere("credentialing_tasks.user_id", "=", $id)

                //     ->get();
                // $id = 13201;
                $credentialingTasks = Credentialing::select("credentialing_tasks.id", "credentialing_tasks.user_id", "credentialing_tasks.user_parent_id", "payers.payer_name as payer")
                    ->join("credentialing_status", "credentialing_status.id", "=", "credentialing_tasks.credentialing_status_id")
                    ->join("payers", "payers.id", "=", "credentialing_tasks.payer_id")
                    ->where("credentialing_tasks.user_id", "=", $id)
                    ->orWhere("credentialing_tasks.user_parent_id", "=", $id)
                    ->get();

                $cJSON = [];
                if (count($credentialingTasks) > 0) {
                    foreach ($credentialingTasks as $task) {
                        // $today = date("Y-m-d H:i:s");

                        if ($task->user_id != 0 && $task->user_parent_id != 0) {
                            $pName = $this->fetchData("users", ["id" => $task->user_id], 1, ["first_name", "last_name", "facility_npi"]);
                            //$npi = $this->fetchData("user_dd_individualproviderinfo",["user_id" => $task->user_id],1,["facility_npi"]);
                            $providerName = $this->fetchData("user_baf_practiseinfo", ["user_id" => $task->user_parent_id], 1, ["provider_type", "provider_name", "legal_business_name"]);
                            $cJSON[$id][] = [
                                "id" => $task->id,
                                "provider_type" => $providerName->provider_type,
                                "provider_name" => $providerName->provider_name,
                                "legal_business_name" => $providerName->legal_business_name,
                                "payer_name" => $task->payer,
                                "created_at" => $task->created_at,
                                "user_id" => $task->user_id,
                                "user_parent_id" => $task->user_parent_id,
                                "first_name" => $pName->first_name . " " . $pName->last_name,
                                "NPI" => $pName->facility_npi

                            ];
                        } elseif ($task->user_id != 0 && $task->user_parent_id == 0) {
                            $pName = $this->fetchData("users", ["id" => $task->user_id], 1, ["first_name", "last_name"]);
                            $providerName = $this->fetchData("user_baf_practiseinfo", ["user_id" => $task->user_id], 1, ["provider_type", "provider_name", "legal_business_name"]);
                            $cJSON[$id][] = [
                                "id" => $task->id,
                                "provider_type" => $providerName->provider_type,
                                "provider_name" => $providerName->provider_name,
                                "legal_business_name" => $providerName->legal_business_name,
                                "payer_name" => $task->payer,
                                "created_at" => $task->created_at,
                                "user_id" => $task->user_id,
                                "user_parent_id" => $task->user_parent_id,
                                "first_name" => $pName->first_name . " " . $pName->last_name,
                            ];
                        }
                    }
                }
                // echo count($credentialingTasks);
                // $this->printR($cJSON,true);

                $credentialingTasksData = [];
                $activityLogs = [];
                $providersData = [];
                $attachments = [];
                $logsStatus = [];
                $logsStatusDates = [];
                $attachmentsArr = [];
                $statusBar = [];
                if (count($credentialingTasks) > 0) {
                    $credentialingTasksArr = $this->stdToArray($credentialingTasks);

                    // $this->printR($credentialingTasksArr[0],true);
                    $credentialingTaskId = $credentialingTasksArr[0]['id']; //getting first record id as it's activity logs can be fetched.
                    $attachments1 = Attachments::where("entities", "=", "credentialtask_id")->where("entity_id", "=", $credentialingTaskId)->where("visibility", "=", 1)->get();
                    //getting the activity loag og first record
                    $credentialingActivityLogs = CredentialingActivityLog::select("credentialing_task_logs.*", DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS full_name"), "users.profile_image")

                        //->leftJoin("users_profile", "users_profile.user_id", "credentialing_task_logs.user_id")

                        ->leftJoin("users", "users.id", "credentialing_task_logs.user_id")

                        ->where("credentialing_task_id", "=", $credentialingTaskId)

                        ->orderBy("credentialing_task_logs.id", "ASC")

                        ->get();
                    $numItems = count($credentialingActivityLogs);
                    $i = 0;
                    if (count($credentialingActivityLogs)) {
                        $date1 = "";
                        foreach ($credentialingActivityLogs as $key => $activityLogs) {
                            if ($key == 0)
                                $date1 = $activityLogs->created_at;

                            if (++$i === $numItems) {
                                $date2 = new Carbon($activityLogs->created_at);
                                $DeferenceInDays = Carbon::parse($date2)->diffInDays($date1);
                                $activityLogs->days_diff = $DeferenceInDays > 0 ? $DeferenceInDays + 1 : 0;
                            }
                        }
                    }
                    $credentialingActivityLogsArr = $this->stdToArray($credentialingActivityLogs);
                    $clTaskIds = array_column($credentialingActivityLogsArr, "id");
                    $attachments2 = Attachments::where("entities", "=", "credentialtasklog_id")->where("visibility", "=", 1)->whereIn("entity_id", $clTaskIds)->get();
                    $attachments1 = $this->stdToArray($attachments1);
                    $attachments2 = $this->stdToArray($attachments2);
                    $attachments = array_merge($attachments1, $attachments2);
                    $url = env("STORAGE_PATH");

                    if (count($attachments)) {
                        foreach ($attachments as $attachment) {
                            if ($attachment["entities"] == "credentialtask_id") {
                                // $attachment['field_value'] = $url.$attachment['entity_id']."/".$attachment['field_value'];
                                $myUrl = $url . $attachment['entity_id'] . "/" . $attachment['field_value'];
                                $attachmentsArr[] = ["id" => $attachment["id"], "field_key" => $attachment["field_key"], "field_value" => $myUrl, "file_name" => $attachment['field_value']];
                            } else {
                                $myUrl = $url . "credentialing/activityLog/" . $attachment['entity_id'] . "/" . $attachment['field_value'];
                                // $attachment['field_value'] = $url."credentialing/activityLog/".$attachment['entity_id']."/".$attachment['field_value'];
                                $attachmentsArr[] = ["id" => $attachment["id"], "field_key" => $attachment["field_key"], "field_value" => $myUrl, "file_name" => $attachment['field_value']];
                            }
                        }
                    }
                    $activityLogs = $credentialingActivityLogs;


                    if (count($credentialingActivityLogs)) {

                        // $credentialingActivityLogsArr = $this->stdToArray($credentialingActivityLogs);
                        foreach ($credentialingActivityLogs as $log) {
                            //$dateArr = explode("-",$log->created_at);
                            $date = date("m/d/Y", strtotime($log->created_at));
                            // $date = $dateArr[1]."/".$dateArr[2]."/".$dateArr[0];
                            $data = [
                                "date" => $date,
                                "status" => $log->status,
                                "status_id" => $log->status_id
                            ];
                            array_push($statusBar, $data);
                        }
                        // $logsStatus = array_column($credentialingActivityLogsArr, "status");
                        // $logsStatusDates = array_column($credentialingActivityLogsArr, "created_at");
                        //$this->printR($status,true);
                    }

                    $providerIds = array_column($credentialingTasksArr, "user_id");

                    $providerIds = array_unique($providerIds);
                    // $this->printR($providerIds,true);
                    // $providersData = Provider::whereIn("id", $providerIds)

                    //     ->get();
                    $providersData = BAF::select([
                        "user_baf_practiseinfo.id", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.user_id", "user_baf_practiseinfo.provider_type", "user_baf_practiseinfo.provider_name",
                        'user_baf_practiseinfo.legal_business_name', 'user_baf_practiseinfo.doing_business_as', 'user_baf_practiseinfo.number_of_individual_provider', "user_baf_contactinfo.address",
                        "user_baf_contactinfo.address_line_one", "user_baf_contactinfo.city", "user_baf_contactinfo.state", "user_baf_contactinfo.zip_code", "user_baf_contactinfo.contact_person_name",
                        "user_baf_contactinfo.contact_person_email", "user_baf_contactinfo.contact_person_designation", "user_baf_contactinfo.contact_person_phone", "user_baf_contactinfo.comments",
                        "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type", "user_baf_businessinfo.business_type",
                        "user_baf_businessinfo.business_type", "user_baf_businessinfo.begining_date", "user_baf_businessinfo.number_of_physical_location", "user_baf_businessinfo.avg_patient_day",
                        "user_baf_businessinfo.practise_managemnt_software", "user_baf_businessinfo.use_pms", "user_baf_businessinfo.electronic_health_record_software", "user_baf_businessinfo.use_ehr",
                        "user_baf_businessinfo.seeking_service",  "user_baf_businessinfo.seeking_service", "user_baf_practiseinfo.created_at", "user_baf_practiseinfo.updated_at", "users.deleted",
                        "user_dd_businessinformation.facility_npi as NPI", "user_dd_businessinformation.facility_tax_id", "user_dd_businessinformation.group_specialty"
                    ])

                        ->leftJoin("user_baf_contactinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_contactinfo.user_id")

                        ->leftJoin("user_baf_businessinfo", "user_baf_practiseinfo.user_id", "=", "user_baf_businessinfo.user_id")

                        ->leftJoin("users", "user_baf_practiseinfo.user_id", "=", "users.id")

                        ->leftJoin("user_dd_businessinformation", "user_dd_businessinformation.user_id", "=", "users.id")

                        ->whereIn("user_baf_practiseinfo.user_id", $providerIds)

                        ->get();

                    // $this->printR($provider,true);
                    // foreach ($providersData as $provider) {
                    //     $providerId = $provider->user_id;
                    //     foreach ($credentialingTasks as $index => $task) {
                    //         if ($providerId == $task->user_id || $providerId == $task->user_parent_id) {
                    //             $credentialingTasksData[$providerId][] = $task;
                    //         }
                    //     }
                    // }
                }
                $status = $this->fetchData("credentialing_status", "");
                return $this->successResponse([
                    "providers" => $providersData, "credentialing_tasks" => $cJSON,
                    "credentialing_tasks_activity_logs" => $activityLogs,
                    "notification" => $notificationArr,
                    "attachments" => $attachmentsArr,
                    "credentialing_status" => $status,
                    "status_bar" => $statusBar
                ], "Success");
            }
        }
        // catch (\Throwable $exception) {
        //     return $this->errorResponse([], $exception->getMessage(), 500);
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
        try {

            $updateData = $request->all();
            // $updataData = [];
            // $updateData["info_required"] = json_encode($ddDataAll);
            $setData = $updateData["info_required"] == 'NULL' ? ['info_required' => NULL] : ['info_required' => $updateData["info_required"]];
            $isUpdate  = Credentialing::where("id", $id)->update($setData);

            return $this->successResponse(["is_update" => $isUpdate], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
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
     * Store a newly created AddReimbursementFee
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addReimbursementFee(Request $request)
    {

        $CredentialingObj = new Credentialing();

        $request->validate([
            "credentialing_task_id" => "required",
            "cpt_code"              => "required",
            "fees"                  => "required",
        ]);

        $addData = [
            "credentialing_task_id" => $request->credentialing_task_id,
            "cpt_code" => $request->cpt_code,
            "fees"  => $request->fees,
            "facility_id" => $request->facility_id,
            "individual_id" => $request->individual_id

        ];

        $hasSameCode = $this->fetchData("reimbursement_rate", ["id" => $request->id], 1, ["id"]);
        if (!is_object($hasSameCode))
            $newId = $CredentialingObj->addCoverage($addData);
        else {
            $newId = $this->updateData("reimbursement_rate", ["id" => $request->id], ["fees" => $request->fees, "cpt_code" => $request->cpt_code]);
        }

        $CredentialingObj = NULL;
        return $this->successResponse(["id" => $newId], "added successfully.");
    }
    /**
     * Display the specified data ReimbursementFee.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function fetchReimbursementFee(Request $request)
    {
        $CredentialingObj = new Credentialing();

        $taskId = $request->task_id;
        $reimbursementRate = $CredentialingObj->fetchReimberceData($taskId);

        return $this->successResponse($reimbursementRate, "success", 200);
    }


    /**
     * Store a newly created addCptCodeType
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addCptCodeType(Request $request)
    {
        // = $request->get('name')

        $CredentialingObj = new Credentialing();

        $request->validate([
            "cpt_code"          => "required",
            "description"       => "required",
        ]);

        $addCptData = [
            "cpt_code"    => $request->cpt_code,
            "description" => $request->description,

        ];
        // return $request->credentialing_task_id;
        // exit;
        // $this->printR($inputData,true);

        $newId = $CredentialingObj->addCptCode($addCptData);

        return $this->successResponse(["id" => $newId], "added successfully.");
    }


    /**
     * Display a listing to the CptCodeType.
     *
     * @return \Illuminate\Http\Response
     */

    public function fetchCptcodeType()
    {
        $CredentialingObj = new Credentialing();

        $cptcode_types = $CredentialingObj->CptCodeType();
        $CredentialingObj = NULL;
        return $this->successResponse($cptcode_types, "success", 200);
    }
    /**
     * fetch the last approved task data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function lastApprovedTask(Request $request)
    {
        $request->validate([
            'credentialing_taskid' => 'required'
        ]);

        $credentialingObj = new Credentialing();

        $taskId = $request->credentialing_taskid;

        $facilityId = $request->facility_id;

        $reimbursementRate = $credentialingObj->fetchReimberceData($taskId);

        $reimbursementGroup = $credentialingObj->fetchReimberceGroupData($facilityId, $taskId);

        $approvedTaskData = $credentialingObj->fetchLastApprovedTaskData($taskId);

        return $this->successResponse(['approved_task_data' => $approvedTaskData, 'reimbursement_rate' => $reimbursementRate, 'reimbursement_group' => $reimbursementGroup], "success", 200);
    }
    /**
     * update the re-imbercment data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function updateReimbursement(Request $request)
    {
        $request->validate([
            'credentialing_taskid' => 'required',
            'code' => 'required',
            'amount' => 'required',
            'id' => 'required'
        ]);
        $credentialingObj = new Credentialing();
        $credId = $request->credentialing_taskid;
        $code = $request->code;
        $amount = $request->amount;
        $id = $request->id;

        // if($updateStatus == 0)
        {
            $where = [
                'credentialing_task_id' => $credId,
                'id' => $id
            ];
            $updateData['cpt_code']  = $code;
            $updateData['fees']     = $amount;
            // $this->printR($updateData,true);
            $credentialingObj->updateReImbercement($where, $updateData);
        }
        // else {
        //     $where = [
        //         'credentialing_task_id' => $credId,
        //         'id' => $id
        //     ];
        //     $updateData['status']       = 1;
        //     $updateData['status_date']  = NULL;
        //     $credentialingObj->updateReImbercement($where,$updateData);
        // }
        $credentialingObj = NULL;
        return $this->successResponse(["result" => true], "success");
    }

    /**
     * assigning credentialing task to a user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function assignCredentialingTask(Request $request)
    {

        $request->validate([
            'user_id' => 'required',
            'tasks' => 'required'
        ]);

        $userId = $request->user_id;
        $tasks = $request->tasks;
        $sessionUserId =   $this->getSessionUserId($request);

        $assingedUserName = User::selectRaw(DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"),)->where('id', $userId)->first();
        $credentialingController = new CredentialingController();

        $tasksArr = json_decode($tasks, true);
        if (count($tasksArr) > 0) {
            foreach ($tasksArr as $key => $task) {
                $credentialingTask = Credentialing::where('id', $task)->with(['credentialingassinguser' => function ($query) {
                    $query->select(
                        'id',
                        DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"),
                    );
                }])->first();

                $oldAssingUser = $credentialingTask->credentialingassinguser->user_name ?? '';
                $detail =  'Enrollment assigned from ' . $oldAssingUser .  ' to ' .   $assingedUserName->user_name ?? '';
                $activityLog = [
                    'user_id' => $sessionUserId,
                    'credentialing_task_id' => $task,
                    'last_follow_up' => null,
                    'next_follow_up' => null,
                    'credentialing_status_id' => '',
                    'details' => $detail,
                    'correspondence_type' => null,
                ];
                Credentialing::where('id', $task)->update(['assignee_user_id' => $userId]);
                CredentialingActivityLog::insertGetId($activityLog);
                $credentialingController->storeCredentialingLogs($userId, $task, $detail, $credentialingTask->payer_id, $credentialingTask->user_parent_id, $credentialingTask->user_id);
            }
        }
        return $this->successResponse(["is_update" => true], "success");
    }
    /**
     * fetch the creds payer avg
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credsPayerAvg(Request $request)
    {
        $request->validate([
            'facility_id' => 'required',
            'payer_id' => 'required',
            'provider_id' => 'required',
            'year' => 'required'
        ]);

        $key = env("AES_KEY");

        // $dosFilter = json_decode($request->dos_filter, true);

        // Get the first date of the current year
        //$firstDayOfYear = Carbon::now()->startOfYear()->format('Y-m-d');
        // $dosFrom = Carbon::now()->subYear()->format('Y-m-d');
        // // Get the last date of the current year
        // $dosTo = Carbon::now()->format('Y-m-d');
        $selectedYear = $request->year;

        $dosFrom = Carbon::create($selectedYear, 1, 1)->startOfYear()->format('Y-m-d');
        $dosTo = Carbon::create($selectedYear, 12, 31)->endOfYear()->format('Y-m-d');
        $subQuery = "SELECT
        COUNT(ar.claim_no) AS total_claims,
        SUM(ar.paid_amount) AS total_paid,
        (SUM(ar.paid_amount) / COUNT(ar.claim_no)) AS average_ar,

        MONTH(ar.dos) AS MONTH
    FROM
        cm_account_receivable AS ar
        INNER JOIN cm_billing AS billing ON billing.claim_no = ar.claim_no
    WHERE
        ar.status IN (5,6,8,2)
        AND ar.is_delete = 0
        AND ar.dos BETWEEN '$dosFrom' AND '$dosTo'";
        if ($request->has("facility_id")) {
            $facilityIdsStr = $request->facility_id;
            $subQuery .= " AND ar.facility_id = '$facilityIdsStr'";
        }

        if ($request->has("provider_id")) {
            $providerId = $request->provider_id;
            if (isset($providerId) && $providerId != 'null' && !is_null($providerId))
                $subQuery .= " AND billing.billing_provider_id = '$providerId'";
        }
        if ($request->has("payer_id")) {
            $payerId = $request->payer_id;
            if (isset($payerId))
                $subQuery .= " AND ar.payer_id = '$payerId'";
        }


        $subQuery .= " GROUP BY MONTH(ar.dos)";


        $result = DB::table(DB::raw("($subQuery) AS subquery"))
            ->select([
                'total_claims',
                DB::raw('ROUND(total_paid, 2) AS total_paid'),
                DB::raw('ROUND(average_ar, 2) AS average_ar'),
                'MONTH',
                DB::raw('0 AS percentage')
            ])
            ->orderBy('MONTH', 'ASC')
            ->get();

        // $this->printR($result,true);
        // Initialize the final result array
        $finalResultArray = array();
        $months = array();
        $avgs = array();
        $eachPayerAvg = [];
        //each payer month count along with total number of avg
        if ($result->count() > 0) {
            foreach ($result as $payer) {
                if (isset($months[$payer->MONTH]))
                    $months[$payer->MONTH] += 1;
                else
                    $months[$payer->MONTH] = 1;


                if (isset($avgs[$payer->MONTH]))
                    $avgs[$payer->MONTH] +=  $payer->average_ar;
                else
                    $avgs[$payer->MONTH] = $payer->average_ar;
            }
        }
        //each payer avg
        if (count($avgs)) {
            foreach ($avgs as $key => $value) {
                $eachPayerAvg[$key] = $value / $months[$key];
            }
        }

        if ($result->count() > 0) {
            foreach ($result as $payer) {
                $monthName = date('F', mktime(0, 0, 0, $payer->MONTH, 1));

                if ($eachPayerAvg[$payer->MONTH] > 0) {
                    $payer->percentage = round(($payer->average_ar / $eachPayerAvg[$payer->MONTH]) * 100);

                    $payer->percentage_color = "";
                    if ($payer->percentage > 100)
                        $payer->percentage_color = "green";
                    elseif ($payer->percentage < 100)
                        $payer->percentage_color = "red";
                } else
                    $payer->percentage_color = "";

                if ($payer->average_ar == 0)
                    $payer->average_ar = "-";
                else {
                    if (is_numeric($payer->average_ar) && strpos($payer->average_ar, '.') !== false)
                        $payer->average_ar = $payer->average_ar;
                    else
                        $payer->average_ar = $payer->average_ar . ".00";
                }

                $payer->MONTH = $monthName;
                $finalResultArray[] = $payer;
            }
        }


        return $this->successResponse(['report' => $finalResultArray], 'success');
    }
    /**
     * fetch the creds payer avg years list from databse
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credsPayerAvgYears(Request $request)
    {
        $currentYear = Carbon::now()->year;

        $years = DB::table("account_receivable")->selectRaw('YEAR(dos) as year')
            ->whereDate('dos', '<=', Carbon::now()->format('Y-m-d'))
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('year')
            ->prepend($currentYear);
        if (count($years) > 0) {
            $years = collect($years)->unique()->sort()->values();
        }
        return $this->successResponse(['years' => $years], 'success');
    }

    public function storeCredentialingLogs($userId, $taskId, $logDetail, $payer_id, $facility_id, $provider_id)
    {
        $credentialingLog = new CredentialingLogs();
        $credentialingLog->user_id = $userId;
        $credentialingLog->payer_id = $payer_id;
        $credentialingLog->facility_id = $facility_id == 0 ? $provider_id : $facility_id;
        $credentialingLog->provider_id = $facility_id != 0 ? $provider_id : 0;
        $credentialingLog->credentialing_task_id = $taskId;
        $credentialingLog->details = $logDetail;
        $credentialingLog->save();
    }
}
