<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Role;
use App\Models\AssignProvider as AssignProviderModel;
use App\Models\AssignCredentialingTaks as AssignCredentialingTaksModel;
use App\Http\Traits\Utility;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\UserAccountActivityLog;
use App\Models\Notifications;
use App\Models\Credentialing;
use App\Models\Provider;
use App\Models\CredentialingActivityLog;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\Models\Attachments;
use App\Models\UserRoleMap;
use App\Models\Assignment;
use App\Models\ChildUser;
use App\Models\InsuranceCoverage;
use App\Models\Insurance;
use App\Models\Payer;
use App\Models\PortalLogs;
use App\Models\PortalType;
use App\Models\Portal;

class UserCommonFunc extends Controller
{
    use Utility, ApiResponseHandler,UserAccountActivityLog;
    private $tbl = "user_ddpracticelocationinfo";
    private $key = "";
    private $tblU = "users";
    private $tblB= "user_dd_businessinformation";
    private $tblBank = "user_ddbankinginfo";
    public function __construct()
    {
        $this->key = env("AES_KEY");
    }
    /**
     * fetch the specific role users like operational manager and supervisor
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchOperationalSupervisorUsers(Request $request)
    {

        try {

            $typerUsers = $request->type;

            $providerId = $request->provider_id;

            if (strpos($typerUsers, "operational manager,supervisor") !== false) {

                // $rolesSP = Role::whereRaw("`role_short_name` = 'supervisor'")

                //     ->first(["id"]);

                // $rolesOM = Role::whereRaw("`role_short_name` = 'operational_manager'")

                //     ->first(["id"]);


                $usersSP = User::select("users.*", "roles.role_name","roles.id as role_id")

                    //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                    //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                    ->leftJoin("user_role_map", "user_role_map.user_id", "=", "users.id")

                    ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                    ->where("user_role_map.role_id", 8)

                    ->get();

                $usersOM = User::select("users.*", "roles.role_name","roles.id as role_id")

                //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                ->leftJoin("user_role_map", "user_role_map.user_id", "=", "users.id")

                ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                ->where("user_role_map.role_id",5)

                ->get();

                $providerAssignedDataArr = [];
                if ($request->has("provider_id") && $request->provider_id) {

                    $providerId =  $request->provider_id;

                    $providerAssignedData = Assignment::where("entities", "=", "provider_id")
                        ->where("entity_id", "=", $providerId)
                        ->get();
                    // $this->printR($providerAssignedData,true);
                    // exit;
                    if (count($providerAssignedData)) {
                        foreach ($providerAssignedData as $assignedData) {
                            $providerAssignedDataArr[$assignedData->user_id] = $providerId;
                        }
                    }
                }

                return $this->successResponse(["supervisor" => $usersSP, "operational_manager" => $usersOM,"provider_assigned_data" => $providerAssignedDataArr], "success");
            }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * search the specific role users like operational manager and supervisor
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchAdminstrationUsers(Request $request)
    {

        try {

            $roleId = $request->role_id;

            $name = $request->name;
            if ($request->has("credentialing") && $request->credentialing == true) {

                // $rolesTL = Role::whereRaw("`role_name` = 'team_lead'")

                //     ->first(["id"]);

                // $rolesTM = Role::whereRaw("`role_name` = 'team_member'")

                //     ->first(["id"]);

                // $users1 = UserProfile::select("users_profile.*", "roles.role_name")

                //     ->leftJoin("roles", "roles.id", "=", "users_profile.role_id")

                //     ->where("users_profile.first_name", "LIKE", "%" . $name . "%")

                //     // ->orWhere("users_profile.last_name", "LIKE", "%" . $name . "%")

                //     ->where("users_profile.role_id", $rolesTL->id)

                //     //->orWhere("users_profile.role_id", $rolesTM->id)

                //     ->get();

                // $users1 = User::select("users.*", "roles.role_name","roles.id as role_id")

                // //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                // //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                // ->leftJoin("user_role_map", "user_role_map.user_id", "=", "user_company_map.user_id")

                // ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                // //->where("users.first_name", "LIKE", "%" . $name . "%")

                // ->whereRaw("CONCAT(cm_users.first_name,' ',cm_users.last_name) LIKE '%$name%'")

                // //->orWhere("users.last_name", "LIKE", "%" . $name . "%")

                // ->where("user_role_map.role_id", $rolesTL->id)

                // ->get();

                // if(count($users1) == 0) {

                //     $users1 = User::select("users.*", "roles.role_name","roles.id as role_id")

                //     ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //     //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //     ->leftJoin("user_role_map", "user_role_map.user_id", "=", "user_company_map.user_id")

                //     ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                //     // ->where("users.first_name", "LIKE", "%" . $name . "%")

                //     ->where("users.last_name", "LIKE", "%" . $name . "%")

                //     ->where("user_role_map.role_id", $rolesTL->id)

                //     ->get();
                // }
                // $users2 = UserProfile::select("users_profile.*", "roles.role_name")

                //     ->leftJoin("roles", "roles.id", "=", "users_profile.role_id")

                //     ->where("users_profile.first_name", "LIKE", "%" . $name . "%")

                //     //->orWhere("users_profile.last_name", "LIKE", "%" . $name . "%")

                //     //->where("users_profile.role_id", $rolesTL->id)

                //     ->where("users_profile.role_id", "=", $rolesTM->id)

                //     ->get();
                $users = User::select("users.*", "roles.role_name","roles.id as role_id")

                //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                ->leftJoin("user_role_map", "user_role_map.user_id", "=", "users.id")

                ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                //->where("users.first_name", "LIKE", "%" . $name . "%")

                //->orWhere("users.last_name", "LIKE", "%" . $name . "%")
                ->whereRaw("CONCAT(cm_users.first_name,' ',cm_users.last_name) LIKE '%$name%'")

                ->whereIn("user_role_map.role_id", [6,7])

                ->get();
                // if(count($users2) == 0 ) {

                //     $users2 = User::select("users.*", "roles.role_name","roles.id as role_id")

                //     ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //     //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //     ->leftJoin("user_role_map", "user_role_map.user_id", "=", "user_company_map.user_id")

                //     ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                //     //->where("users.first_name", "LIKE", "%" . $name . "%")

                //     ->where("users.last_name", "LIKE", "%" . $name . "%")

                //     ->where("user_role_map.role_id", $rolesTM->id)

                //     ->get();
                // }



                //$users1 = $this->stdToArray($users1);

                //$users2 = $this->stdToArray($users2);

                //$users = //array_merge($users1, $users2);
            } else {

                // $users = UserProfile::select("users_profile.*", "roles.role_name")

                //     ->leftJoin("roles", "roles.id", "=", "users_profile.role_id")

                //     ->where("users_profile.first_name", "LIKE", "%" . $name . "%")

                //     //->orWhere("users_profile.last_name", "LIKE", "%" . $name . "%")

                //     ->where("users_profile.role_id","=", $roleId)

                //     ->get();
                $users = User::select("users.*", "roles.role_name","roles.id as role_id")

                //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                ->leftJoin("user_role_map", "user_role_map.user_id", "=", "users.id")

                ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                //->where("users.first_name", "LIKE", "%" . $name . "%")
                ->whereRaw("CONCAT(cm_users.first_name,' ',cm_users.last_name) LIKE '%$name%'")
                // ->where("users.last_name", "LIKE", "%" . $name . "%")

                ->where("user_role_map.role_id", $roleId)

                ->get();
                //$this->printR($users,true);
                // if(count($users) == 0 ) {

                //     $users = User::select("users.*", "roles.role_name","roles.id as role_id")

                //     ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //     //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                //     ->leftJoin("user_role_map", "user_role_map.user_id", "=", "user_company_map.user_id")

                //     ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

                //     //->where("users.first_name", "LIKE", "%" . $name . "%")

                //     ->where("users.last_name", "LIKE", "%" . $name . "%")

                //     ->where("user_role_map.role_id", $roleId)

                //     ->get();
                // }
            }




            return $this->successResponse(["users" => $users,], "success");
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the specific role users like team lead
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     */
    public function fetchTeamLeadAndMember(Request $request)
    {
        $request->validate([
            "provider_id" => "required"
        ]);
        $roleId = $request->role_id;
        $userData = [];
        $perpTaskData = [];

        try {

            if($request->provider_id !=0) {

                $usersTL = UserRoleMap::select("assignments.entity_id","assignments.user_id")
                ->leftJoin("users","users.id","=","user_role_map.user_id")
                ->leftJoin("assignments", function($join)  {
                    $join->on("assignments.user_id","=","users.id");
                })
                ->where("assignments.entity_id", $request->provider_id)
                ->where("assignments.entities","provider_id")
                ->get();

                $usersTLArr = $this->stdToArray($usersTL);
                $ids = array_column($usersTLArr,"user_id");
                // $this->printR($usersTL,true);
                $childUsers = ChildUser::whereIn("parent_user_id",$ids)->select("child_user_id")->get();
                $childUsersArr = $this->stdToArray($childUsers);

                $ids_ = array_column($childUsersArr,"child_user_id");

                // $this->printR($ids_,true);
                $userData  = [];
                foreach($ids_ as $id) {
                    $usersTL_ = UserRoleMap::select("users.id","users.first_name","users.last_name","roles.role_name","roles.id as role_id")
                    ->leftJoin("users","users.id","=","user_role_map.user_id")
                    ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")
                    ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")
                    ->where("user_role_map.user_id","=",$id)
                    ->first();
                    $usersTL_Arr = $this->stdToArray($usersTL_);
                    array_push($userData,$usersTL_Arr);
                }
                // $usersTL_Arr = $this->stdToArray($usersTL_);
                // $this->printR($userData,true);

                $assignTaskData = "";
                $perpTaskData = [];
                if ($request->has("task_id")) {

                    $taskId = $request->input("task_id");

                    $assignTaskData = Assignment::where("entities", "=", "credentialingtask_id")

                        ->where("entity_id", "=", $taskId)

                        ->get();

                    if (count($assignTaskData) > 0) {
                        foreach ($assignTaskData as $task) {
                            $perpTaskData[$task->user_id] = $taskId;
                        }
                    }
                }
            }
            else {
                //echo "this is me.";
                // if($roleId == 1 || $roleId == 5) {

                //     $usersTL_ = UserRoleMap::select("users.id","users.first_name","users.last_name","roles.role_name","roles.id as role_id")
                //     ->leftJoin("users","users.id","=","user_role_map.user_id")
                //     ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")
                //     ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")
                //     ->whereIn("user_role_map.role_id",['6','7'])
                //     ->get();
                //     $userData = $usersTL_;

                //     if ( $request->has("task_id") && $request->task_id > 0 ) {

                //         $taskId = $request->input("task_id");

                //         $assignTaskData = Assignment::where("entities", "=", "credentialingtask_id")

                //             ->where("entity_id", "=", $taskId)

                //             ->get();

                //         if (count($assignTaskData) > 0) {
                //             foreach ($assignTaskData as $task) {
                //                 $perpTaskData[$task->user_id] = $taskId;
                //             }
                //         }
                //     }

                // }
                // else
                {
                    // $usersTL_ = UserRoleMap::select("users.id","users.first_name","users.last_name","roles.role_name","roles.id as role_id")
                    // ->leftJoin("users","users.id","=","user_role_map.user_id")
                    // ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")
                    // ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")
                    // ->whereIn("user_role_map.role_id",['7'])
                    // ->get();
                    $childUsers = ChildUser::where("parent_user_id","=",$request->session_userid)->select("child_user_id")->get();

                    $childUsersArr = $this->stdToArray($childUsers);

                    if(count($childUsersArr)) {

                        $ids_ = array_column($childUsersArr,"child_user_id");

                        // $this->printR($ids_,true);
                        $userData  = [];
                        foreach($ids_ as $id) {
                            $usersTL_ = UserRoleMap::select("users.id","users.first_name","users.last_name","roles.role_name","roles.id as role_id")
                            ->leftJoin("users","users.id","=","user_role_map.user_id")
                            ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")
                            ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")
                            ->where("user_role_map.user_id","=",$id)
                            ->first();
                            $usersTL_Arr = $this->stdToArray($usersTL_);
                            array_push($userData,$usersTL_Arr);
                        }
                    }
                    //$userData = $usersTL_;

                    if ( $request->has("task_id") && $request->task_id > 0 ) {

                        $taskId = $request->input("task_id");

                        $assignTaskData = Assignment::where("entities", "=", "credentialingtask_id")

                            ->where("entity_id", "=", $taskId)

                            ->get();

                        if (count($assignTaskData) > 0) {
                            foreach ($assignTaskData as $task) {
                                $perpTaskData[$task->user_id] = $taskId;
                            }
                        }
                    }
                }
            }
            return $this->successResponse(["team_lead" => $userData, "assign_task_data" => $perpTaskData], "success");
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }



        /**
         * fetching credentialing users
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Illuminate\Http\Response
         *
         */

    public function fetchCredUsers(Request $request) {

        $users = Role::select("URM.user_id",DB::raw("concat(cm_users.first_name,' ', cm_users.last_name) as user_name"))
        ->leftJoin("user_role_map as URM","URM.role_id","=","roles.id")
        ->leftJoin("users","users.id","=","URM.user_id")
        ->where("users.deleted","=",0)
        ->whereIn("roles.id",[6,7])
        ->get();

        return $this->successResponse($users, "success");


    }


    /**
     * assign the provider to perational manager
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     */
    public function assignProvider(Request $request)
    {

        $request->validate([
            "provider_id"       => "required",
            "operational_m_id"  => "required",
            "company_id"        => "required"
        ]);

        $providerId     = $request->provider_id;
        $operationalMId = $request->operational_m_id;
        $companyId      = $request->company_id;
        if ($providerId !== "undefined") {
            $isAdded = Assignment::where("entities", "=", "provider_id")
                //->where("company_id", "=", $companyId)
                //->where("operational_m_id", "=", $operationalMId)
                ->where("entity_id", "=", $providerId)
                ->where("user_id", "=", $operationalMId)
                ->count();

            if ($request->has("select") && $isAdded == 0) {
                $id = Assignment::insertGetId([
                    "entities"       => "provider_id",
                    "entity_id"      => $providerId,
                    "user_id"       => $operationalMId,
                    "created_at"    => $this->timeStamp()
                ]);
                return $this->successResponse(["is_added" => true, 'id' => $id], "success");
            } elseif ($request->has("de-select")) {
                $idDel = Assignment::where("entities", "=", "provider_id")
                ->where("entity_id", "=", $providerId)
                ->where("user_id", "=", $operationalMId)
                ->delete();
                return $this->successResponse(["is_del" => $idDel, 'id' => 0], "success");
            }
        }
    }
    /**
     * assign the credentialing task to user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assignCredentialingTasks(Request $request)
    {

        $request->validate([
            "provider_id"       => "required",
            "user_id"           => "required",
            "company_id"        => "required",
            "task_id"           => "required"
        ]);

        $providerId = $request->provider_id;
        $userId     = $request->user_id;
        $companyId  = $request->company_id;
        $taskId     = $request->task_id;

        $isAdded = Assignment::where("entities", "=", "credentialingtask_id")
            //->where("company_id", "=", $companyId)
            ->where("entity_id", "=", $taskId)
            ->where("user_id", "=", $userId)
            ->count();

        if ($request->has("select") && $isAdded == 0) {
            $id = Assignment::insertGetId([
                "entities"      => "credentialingtask_id",
                "entity_id"     => $taskId,
                "user_id"       => $userId,
                "created_at"    => $this->timeStamp()
            ]);
            return $this->successResponse(["is_added" => true, 'id' => $id], "success");
        } elseif ($request->has("de-select")) {
            $idDel = Assignment::where("entities", "=", "credentialingtask_id")
            //->where("company_id", "=", $companyId)
            ->where("entity_id", "=", $taskId)
            ->where("user_id", "=", $userId)
            ->delete();
            return $this->successResponse(["is_del" => $idDel, 'id' => 0], "success");
        } else {
            return $this->warningResponse([], 'Record already exist with given params', 422);
        }
    }
    /**
     * bluk assign the credentialing task to user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assignBlukCredentialingTasks(Request $request)
    {

        $isAdd = $request->is_add == true ? 1 : 0;

        if ($isAdd == 1) {

            $bulkData = $request->bluk_data;
            $bulkDataArr = json_decode($bulkData,true);
           // $this->printR($bulkDataArr,true);
            $id = Assignment::insert($bulkDataArr);
            return $this->successResponse(["is_added" => true, 'id' => $id], "success");
        } elseif ($isAdd == 0) {

            $userId     = $request->user_id;
            $idDel = Assignment::where("entities", "=", "credentialingtask_id")
                ->where("user_id", "=", $userId)
                ->delete();
            return $this->successResponse(["is_del" => $idDel, 'id' => 0], "success");
        } else {
            return $this->warningResponse([], 'Record already exist with given params', 422);
        }
    }
    /**
     * create the notification for approval of any task
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function requestForApprove(Request $request) {

        $request->validate([
            "date"          => "required",
            "revalid"       => "required",
            "user_id"       => "required",
            "task_id"       => "required",
        ]);
        // $this->printR($request->all(),true);

        $fileName = "";
        if($request->file("attachment") !=null) {
            $file = $request->file("attachment");
            $request->merge(["file" => $request->file('attachment')]);
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

            $this->uploadMyFile($fileName,$file,"credentialing/".$request->task_id."/activityLog/".$request->log_id);
            //upload files on our storage
            $destFolder = "credentialingEnc/".$request->task_id."/activityLog/".$request->log_id;

            $fileRes = $this->encryptUpload($request,$destFolder);

            if (isset($fileRes["file_name"])){

            $aid = [
                "entities" => "credentialtasklog_id",
                "entity_id" => $request->log_id,
                "field_key" => "Approve Doc",
                "field_value" =>  $fileRes["file_name"]
            ];

            $this->addData("attachments", $aid, 0);

         }
        }
        $status = $request->status;

        $statusId = $status;

        $this->updateData("credentialing_tasks",["id" =>$request->task_id],["credentialing_status_id" => $statusId,"Identifier" => $request->p_id,
        "effective_date" => $request->date,"revalidation_date" => $request->revalidation_date,"is_inreview" => 1,"contract_type" => $request->contract_type]);


        return $this->successResponse(["added" => true],"success");

    }
    /**
     * fetch the notifications
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchNotifications(Request $request) {

        $notificationData = Notifications::orderBy("id","DESC")

        ->paginate(10);

        return $this->successResponse(["notifications" => $notificationData],"success");
    }

    /**
     * fetch user credentialing task
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function credentialingUserTask(Request $request) {

        $userId = $request->user_id;

        $user = UserProfile::where("id","=",$userId)->first(["user_id"]);

        $userId = $user->user_id;

        $providerId = $request->provider_id;

        $tasks = AssignCredentialingTaksModel::select("task_id")

        ->where("user_id", "=",$userId)

        ->where("provider_id", "=",$providerId)

        ->get();
        $credentialingTasksData = [];
        $providersData = [];
        $activityLogs = [];
        $logsStatus = [];
        $logsStatusDates = [];
        if(count($tasks)) {

            $taskIds = $this->stdToArray($tasks);

            $taskIds = array_column($taskIds,"task_id");


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

                ->whereIn("credentialing_tasks.id",$taskIds)

                ->get();


            if (count($credentialingTasks) > 0) {
                $credentialingTasksArr = $this->stdToArray($credentialingTasks);
                $credentialingTasksArr = $this->stdToArray($credentialingTasks);

                // $this->printR($credentialingTasksArr[0],true);
                $credentialingTaskId = $credentialingTasksArr[0]['id']; //getting first record id as it's activity logs can be fetched.

                //getting the activity loag og first record
                $credentialingActivityLogs = CredentialingActivityLog::select("credentialing_task_logs.*", DB::raw("CONCAT(cm_users_profile.first_name,' ',cm_users_profile.last_name) AS full_name"), "users_profile.picture")

                    ->leftJoin("users_profile", "users_profile.user_id", "credentialing_task_logs.user_id")

                    ->where("credentialing_task_id", "=", $credentialingTaskId)

                    ->orderBy("credentialing_task_logs.id", "ASC")

                    ->get();

                $activityLogs = $credentialingActivityLogs;
                $logsStatus = [];

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
                'logs_status' => $logsStatus,"logs_Status_dates" => $logsStatusDates
            ], "success");
        }
    }
    /**
     * fetch providers recuring invoices
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function providersRecuringInvoices(Request $request) {

        $invoices = Invoice::select("invoices.*","providers.*")

        ->leftJoin("providers","providers.id","=","invoices.provider_id")

        ->where("invoices.is_recuring","=",1)

        ->paginate(20);

        return $this->successResponse([
            "invoices" => $invoices,
        ], "success");
    }
    /**
     * filter the credentialing tasks
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function filterCredentialingTasks(Request $request) {

        $status = $request->status;

        $date = $request->date;

       $taskId = $request->task_id;

        if($status !="" && ($date !="" && $taskId !="") && ($date !="null" && $taskId !="null")) {
            // echo $status.":===:".$date;
            if(strpos($date, "T") !== false) {
                $date = explode("T",$date);
            }

            $credentialingActivityLogs = CredentialingActivityLog::select("credentialing_task_logs.*", DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS full_name"), "users.profile_image")

            //->leftJoin("users_profile", "users_profile.user_id", "credentialing_task_logs.user_id")

            ->leftJoin("users", "users.id", "credentialing_task_logs.user_id")

            ->where("credentialing_task_logs.status", "=", $status)

            ->whereDate("credentialing_task_logs.created_at","=",$date)

            ->where("credentialing_task_logs.credentialing_task_id","=",$taskId)

            ->orderBy("credentialing_task_logs.id", "ASC")

            ->get();
            $credentialingActivityLogsArr = $this->stdToArray($credentialingActivityLogs);
            $clTaskIds = array_column($credentialingActivityLogsArr,"id");

            $attachments1 = Attachments::where("entities","=","credentialtask_id")->where("entity_id","=",$taskId)->get();
            $attachments2 = Attachments::where("entities","=","credentialtasklog_id")->whereIn("entity_id",$clTaskIds)->get();
            $attachments1 = $this->stdToArray($attachments1);
            $attachments2 = $this->stdToArray($attachments2);
            $attachments = array_merge($attachments1,$attachments2);
            $url = env("STORAGE_PATH");
            if(count($attachments)) {
                foreach($attachments as $attachment) {
                    $attachment['field_value'] = $url.$attachment['entity_id']."/".$attachment['field_value'];
                }
            }

            // $this->printR($credentialingActivityLogs,true);
            return $this->successResponse([
                "credentialing_activity_logs" => $credentialingActivityLogs,
                "attachments" => $attachments
            ], "success");
        }
        else {

            $credentialingActivityLogs = CredentialingActivityLog::select("credentialing_task_logs.*", DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS full_name"), "users.profile_image")

            // ->leftJoin("users_profile", "users_profile.user_id", "credentialing_task_logs.user_id")

            ->leftJoin("users", "users.id", "credentialing_task_logs.user_id")

            //->where("credentialing_task_logs.status", "=", $status)

            //->whereDate("credentialing_task_logs.created_at","=",$date)

            ->where("credentialing_task_logs.credentialing_task_id","=",$taskId)

            ->orderBy("credentialing_task_logs.id", "ASC")

            ->get();
            $attachments = Attachments::where("entities","=","credentialtask_id")->where("entity_id","=",$taskId)->get();
            $url = env("STORAGE_PATH");
            if(count($attachments)) {
                foreach($attachments as $attachment) {
                    $attachment->field_value = $url.$attachment->entity_id."/".$attachment->field_value;
                }
            }
            // $this->printR($credentialingActivityLogs,true);
            return $this->successResponse([
                "credentialing_activity_logs" => $credentialingActivityLogs,
                "attachments" => $attachments
            ], "success");
        }
    }
    /**
     * fetch the document listing
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchAttachments(Request $request) {

        $entityId   = $request->entity_id;

        $entity     = $request->entity;

        $attachments = Attachments::where("entities","=",$entity)

        ->where("entity_id","=",$entityId)

        ->get();
        $url = env("STORAGE_PATH");
        $folder = "";
        if($entity == "provider_id")
            $folder = "providers/";
        if(count($attachments)) {
            foreach($attachments as $attachment) {
                $attachment->field_value = $url.$folder.$attachment->entity_id."/".$attachment->field_value;
            }
        }
        return $this->successResponse(["attachments" => $attachments,"document_count" => count($attachments)], "success");

    }
     /**
     * fetch the document listing
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchProfileAttachments(Request $request) {

        $entityId   = $request->entity_id;

        $entity     = $request->entity;

        $customPerPage = $request->has('cust_per_page') ? $request->cust_per_page : $this->cmperPage;

        if($request->has("filter")) {
            $searchVal = $request->keyword;
            $attachments = Attachments::select(DB::raw("DATE_FORMAT(cm_attachments.created_at, '%m/%d/%Y') AS  created_At"),
            "attachments.id","attachments.entities","attachments.entity_id","attachments.field_key","attachments.field_value", "attachments.is_current_version"
            ,DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS user_name"))
            ->join("users","attachments.created_by","=","users.id")
            // ->where("entity_id","=",$entityId)
            ->whereRaw("cm_attachments.entities = '".$entity."' AND cm_attachments.entity_id = '".$entityId."' AND (cm_attachments.field_key LIKE '%".$searchVal."%' OR cm_attachments.field_value LIKE '%".$searchVal."%' )")

            ->where('attachments.visibility','=',1)

            ->orderBy("id","DESC")

            ->paginate($customPerPage);
        }
        else {

            $attachments = Attachments::select(DB::raw("DATE_FORMAT(cm_attachments.created_at, '%m/%d/%Y') AS  created_At"),
            "attachments.id","attachments.entities","attachments.entity_id","attachments.field_key","attachments.field_value",  "attachments.is_current_version"
            ,DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS user_name"))

            ->join("users","attachments.created_by","=","users.id")

            ->where("entities","=",$entity)

            ->where("entity_id","=",$entityId)

            ->where('attachments.visibility','=', 1)

            ->orderBy("id","DESC")

            ->paginate($customPerPage);
        }
        $url = env("STORAGE_PATH");
        $folder = "";
        if($entity == "provider_id")
            $folder = "providers/";
        if(count($attachments)) {
            foreach($attachments as $attachment) {
                $attachment->file_name = $attachment->field_value;
                $attachment->field_value = $url.$folder.$attachment->entity_id."/".$attachment->field_value;

            }
        }
        return $this->successResponse(["attachments" => $attachments], "success");

    }

    /**
     * check the file permission of file
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function chkFilePermissions(Request $request) {
        $request->validate([
            "file_id" => "required",
            "user_id" => "required"
        ]);
        $fileId = $request->file_id;
        $userId = $request->user_id;
        $where = [
            ["user_id","=",$userId],
            ["attachment_id","=",$fileId],
        ];
        $data  = $this->fetchData("user_attachment_map",$where,1,["id"]);
        if(is_object($data)) {
            return $this->successResponse(["can_view" => true],"success");
        }
        else {
            return $this->successResponse(["can_view" => true],"success");
        }
    }

    /**
     * update the provider profile data
     *
     *
     */
    public function updateProviderProfile($userId,Request $request) {
        // echo "This is the userid:".$userId;
        // $this->printR($request->all(),true);
        $inputData =$request->all();
        $isUpdate = false;
        $where= [
            ["user_id","=",$userId]
        ];

        $key = $this->key;

        $sessionUserId = $this->getSessionUserId($request);

        $sessionUserName = $this->getSessionUserName($request,$sessionUserId);

        if($inputData["section"] == "provider_info") {
            $logStr = "";

            $tbl = $this->tbl;
            $tbl2 = $this->tblB;
            $updateLocation = [
                "email"                 => DB::raw("AES_ENCRYPT('".$inputData['email']."', '$key')"),
                "phone"                 => DB::raw("AES_ENCRYPT('".$inputData['phone']."', '$key')"),
                "fax"                   => DB::raw("AES_ENCRYPT('".$inputData['fax']."', '$key')"),
                "npi"                   => DB::raw("AES_ENCRYPT('".$inputData['npi']."', '$key')"),
                "practise_address1"     => DB::raw("AES_ENCRYPT('".$inputData['address_line_two']."', '$key')"),
                "practise_address"      => DB::raw("AES_ENCRYPT('".$inputData['address_line_one']."', '$key')"),
                "doing_buisness_as"     => DB::raw("AES_ENCRYPT('".$inputData['doing_business_as']."', '$key')"),
                "office_manager_name"   => $inputData["office_manager_name"],
                "city"                  => $inputData["city"],
                "state"                 => $inputData["state"],
                "zip_code"              => $inputData["zip_code"],
                "specialty"             => $inputData["group_specialty"]
            ];

            $fetchLocationCols = [
                DB::raw("AES_DECRYPT(email, '$key') as email"),
                DB::raw("AES_DECRYPT(phone, '$key') as phone"),
                DB::raw("AES_DECRYPT(fax, '$key') as fax"),
                DB::raw("AES_DECRYPT(npi, '$key') as npi"),
                DB::raw("AES_DECRYPT(practise_address1, '$key') as practise_address1"),
                DB::raw("AES_DECRYPT(practise_address, '$key') as practise_address"),
                DB::raw("AES_DECRYPT(doing_buisness_as, '$key') as doing_buisness_as"),
               "office_manager_name","city","state","zip_code","specialty"
            ];


            $fetchLocation = $this->fetchData($tbl,$where,1,$fetchLocationCols);

            $fetchLocationArr = $this->stdToArray($fetchLocation);

            $logStr .= $this->makeTheLogMsg($sessionUserName,$updateLocation,$fetchLocationArr);

            $isUpdate = $this->updateData($tbl,$where,$updateLocation);

            $updateBInfo = [
                    "group_specialty" => $inputData["group_specialty"],
                    "business_established_date" => $inputData["business_established_date"]
            ];

            $ddbi_ = $this->fetchData($tbl2,$where,1,[]);

            $ddbiArr_ = $this->stdToArray($ddbi_);

            $logStr .= $this->makeTheLogMsg($sessionUserName,$updateBInfo,$ddbiArr_);

            $parentData = $this->fetchData($tbl,$where,1,["user_parent_id"]);
            $parentId = is_object($parentData) ? $parentData->user_parent_id : 0;
            $isUpdate = $this->updateData($tbl2,["user_id" => $parentId],$updateBInfo);

             //handle the user activity
             $this->handleUserActivity(
                $userId,$sessionUserId,"Profile","update",$logStr,NULL,$this->timeStamp()
            );


            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "provider_info_main") {
            $logStr = "";

            if(isset($inputData["group_specialty"]) && isset($inputData["facility_npi"])
            && isset($inputData["facility_tax_id"]) && isset($inputData["fax"])
            && isset($inputData["federal_tax_classification"])) {

                $updateDataArr = [
                    "group_specialty" => $inputData["group_specialty"],
                    "facility_npi" => $inputData["facility_npi"],
                    "facility_tax_id" => $inputData["facility_npi"],
                    "fax" => $inputData["fax"],
                    "federal_tax_classification" => $inputData["federal_tax_classification"]
                ];
                $ddbi = $this->fetchData("user_dd_businessinformation",$where,1,[]);

                $ddbiArr = $this->stdToArray($ddbi);

                $logStr .= $this->makeTheLogMsg($sessionUserName,$updateDataArr,$ddbiArr);

                $isUpdate = $this->updateData("user_dd_businessinformation",$where,$updateDataArr);
            }

            $updateBafB = [
                "legal_business_name"   => $inputData["legal_business_name"]
            ];

            $fetchBafB = $this->fetchData("user_baf_practiseinfo",$where,1,[]);

            $fetchBafBArr = $this->stdToArray($fetchBafB);

            $logStr .= $this->makeTheLogMsg($sessionUserName,$updateBafB,$fetchBafBArr);

            $isUpdate = $this->updateData("user_baf_practiseinfo",$where,$updateBafB);

            $updateLocation = [
                "email"                 => DB::raw("AES_ENCRYPT('".$inputData['contact_person_email']."', '$key')"),
                "phone"                 => DB::raw("AES_ENCRYPT('".$inputData['contact_person_phone']."', '$key')"),
                "fax"                   => DB::raw("AES_ENCRYPT('".$inputData['fax']."', '$key')"),
                "npi"                   => DB::raw("AES_ENCRYPT('".$inputData['npi']."', '$key')"),
                "doing_buisness_as"     => DB::raw("AES_ENCRYPT('".$inputData['doing_business_as']."', '$key')"),
                "tax_id"                => DB::raw("AES_ENCRYPT('".$inputData['facility_tax_id']."', '$key')"),
            ];
            // $this->printR($updateLocation,true);
            $tbl = $this->tbl;
            $tbl2 = $this->tblB;

            $fetchLocationCols = [
                DB::raw("AES_DECRYPT(cm_".$tbl.".email, '$key') AS email"),
                DB::raw("AES_DECRYPT(cm_".$tbl.".phone, '$key') AS phone"),
                DB::raw("AES_DECRYPT(cm_".$tbl.".fax, '$key') AS fax"),
                DB::raw("AES_DECRYPT(cm_".$tbl.".npi, '$key') AS npi"),
                DB::raw("AES_DECRYPT(cm_".$tbl.".doing_buisness_as, '$key') AS doing_buisness_as"),
                DB::raw("AES_DECRYPT(cm_".$tbl.".tax_id, '$key') AS tax_id")
            ];

            $fetchLocation = $this->fetchData($tbl,$where,1,$fetchLocationCols);

            // $this->printR($fetchLocation,true);

            $fetchLocationArr = $this->stdToArray($fetchLocation);

            $logStr .= $this->makeTheLogMsg($sessionUserName,$updateLocation,$fetchLocationArr);

            $isUpdate = $this->updateData($tbl,$where,$updateLocation);

            $updateBInfo = [
                    "group_specialty" => $inputData["group_specialty"],
                    "business_established_date" => $inputData["business_established_date"]
            ];

            $ddbi_ = $this->fetchData("user_dd_businessinformation",$where,1,[]);

            // $this->printR($updateBInfo,true);
            $ddbiArr_ = $this->stdToArray($ddbi_);

            $logStr .= $this->makeTheLogMsg($sessionUserName,$updateBInfo,$ddbiArr_);

            $parentData = $this->fetchData($tbl,$where,1,["user_parent_id"]);
            $parentId = is_object($parentData) ? $parentData->user_parent_id : 0;
            $isUpdate = $this->updateData($tbl2,["user_id" => $parentId],$updateBInfo);

             //handle the user activity
             $this->handleUserActivity(
                $userId,$sessionUserId,"Profile","update",$logStr,NULL,$this->timeStamp()
            );
            // $isUpdate = $this->updateData("user_baf_contactinfo",$where,$updateBafC);

            // $updateBafBB = [
            //     "begining_date"   => $inputData["begining_date"]
            // ];
            // $isUpdate = $this->updateData("user_baf_businessinfo",$where,$updateBafBB);

            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "bank_info") {

            $key = $this->key;
            $tbl = $this->tblBank;
            $updateBI = [
                "bank_name"   => $inputData["bank_name"],
                "account_name" => DB::raw("AES_ENCRYPT('".$inputData["account_name"]."','$key')"),
                "routing_number" => DB::raw("AES_ENCRYPT('".$inputData["routing_number"]."','$key')"),
                "account_number" => DB::raw("AES_ENCRYPT('".$inputData["account_number"]."','$key')"),
                "bank_address" => $inputData["bank_address"],
                "bank_address2" => $inputData["bank_address2"],
                "state" => $inputData["state"],
                "city" => $inputData["city"],
                "zipcode" => $inputData["zipcode"],
                "bank_phone" => $inputData["bank_phone"],
                "bank_contact_person" => $inputData["bank_contact_person"],
            ];
            // $this->printR($updateBI,true);
            $bankInfoOld = $this->bankingInformation($userId);

            $bankInfoOldArr = is_object($bankInfoOld) ? $this->stdToArray($bankInfoOld) : [];

            $logStr = $this->makeTheLogMsg($sessionUserName,$updateBI,$bankInfoOldArr);
            if(count($bankInfoOldArr)) {
                //handle the user activity
                $this->handleUserActivity(
                    $userId,$sessionUserId,"Bank Info","update",$logStr,NULL,$this->timeStamp()
                );
                $isUpdate = $this->updateData($tbl,$where,$updateBI);
            }
            else {
                $updateBI["user_id"] = $userId;
                //handle the user activity
                $this->handleUserActivity(
                    $userId,$sessionUserId,"Bank Info","add",$logStr,NULL,$this->timeStamp()
                );
                $isUpdate = $this->addData($tbl,$updateBI);
            }

            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "address") {
            $key = $this->key;
            $tbl = $this->tbl;
            $tbl1 = $this->tblB;
            $logStr = "";

            // return $this->successResponse(["is_updated" => true], "success");
            $locationAddress = [
                "city" => $inputData["city"],
                "practise_address" => DB::raw("AES_ENCRYPT('".$inputData["practise_address"]."','$key')"),
                "practise_address1" => DB::raw("AES_ENCRYPT('".$inputData["practise_address1"]."','$key')"),
                "fax" =>  DB::raw("AES_ENCRYPT('".$inputData["practise_fax"]."','$key')"),
                "phone" => DB::raw("AES_ENCRYPT('".$inputData["practise_phone"]."','$key')"),
                "state" => $inputData["state"],
                "zip_code" => $inputData["zip_code"],

            ];

            $locationSelect = [
                DB::raw("AES_DECRYPT('practise_address','$key')"),
                DB::raw("AES_DECRYPT('practise_address1','$key')"),
                DB::raw("AES_DECRYPT('fax','$key')"),
                DB::raw("AES_DECRYPT('phone','$key')"),
                "city",
                "state",
                "zip_code"
            ];

            $bafLocation = [
                "address" =>$inputData["baf_addressone"],
                "address_line_one" => $inputData["baf_addresstwo"],
                "city" => $inputData["baf_city"],
                "state" => $inputData["baf_state"],
                "zip_code" => $inputData["baf_zipcode"],
                "contact_person_phone" => $inputData["baf_phone"]
            ];

            $ddbi = $this->fetchData($tbl1,$where,1,[]);

            $ddbiArr = $this->stdToArray($ddbi);

            $logStr .= $this->makeTheLogMsg($sessionUserName,["fax" => $inputData["baf_fax"]],$ddbiArr);

            $this->updateData($tbl1,$where,["fax" =>  DB::raw("AES_ENCRYPT('".$inputData["baf_fax"]."','$key')")]);

            $bafCt = $this->fetchData("user_baf_contactinfo",$where,1,[]);

            $bafCtArr = $this->stdToArray($bafCt);

            $logStr .= $this->makeTheLogMsg($sessionUserName,$bafLocation,$bafCtArr);

            $isUpdate = $this->updateData("user_baf_contactinfo",$where,$bafLocation);

            $parent = $this->fetchData("user_baf_contactinfo",$where,1,[]);
            if(is_object($parent))
                $locationAddress["is_primary"] = 1;

            // $this->printR($locationAddress,true);
            $isAdded = $this->fetchData($tbl,$where,1,$locationSelect);

            if(is_object($isAdded)) {
                $isUpdate = $this->updateData($tbl,$where,$locationAddress);

                $locArr = $this->stdToArray($isAdded);

                $logStr .= $this->makeTheLogMsg($sessionUserName,$locationAddress,$locArr);
            }
            else {
                $practiseInfo = $this->fetchData("user_baf_practiseinfo", ["user_id" => $userId], 1, ["provider_name","legal_business_name","number_of_individual_provider"]);
                $bInfo = $this->fetchData($tbl1, ["user_id" => $userId], 1, ["group_specialty",DB::raw("AES_DECRYPT('facility_tax_id','$key')"),DB::raw("AES_DECRYPT('facility_npi','$key')")]);
                $cInfo = $this->fetchData("user_baf_contactinfo", ["user_id" => $userId], 1, ["city","state","zip_code"]);
                $baInfo = $this->fetchData("user_baf_businessinfo", ["user_id" => $userId], 1, ["number_of_physical_location"]);

                $locationAddress['practice_name'] = is_object($practiseInfo) ? $practiseInfo->legal_business_name : "NULL";
                $locationAddress['specialty'] = is_object($bInfo) ? $bInfo->group_specialty : "NULL";
                $locationAddress['tax_id'] = is_object($bInfo) ? $bInfo->facility_tax_id : "NULL";
                $locationAddress['npi'] = is_object($bInfo) ? $bInfo->facility_npi : "NULL";
                $locationAddress['city'] = is_object($cInfo) ? $cInfo->city : "NULL";
                $locationAddress['state'] = is_object($cInfo) ? $cInfo->state : "NULL";
                $locationAddress['zip_code'] = is_object($cInfo) ? $cInfo->zip_code : "NULL";
                $locationAddress['number_of_individual_provider'] = is_object($practiseInfo) ? $practiseInfo->number_of_individual_provider : "NULL";
                $locationAddress['number_of_physical_location'] = is_object($baInfo) ? $baInfo->number_of_physical_location : "NULL";

                $locationAddressAdd['practice_name'] = is_object($practiseInfo) ?  DB::raw("AES_ENCRYPT('".$practiseInfo->legal_business_name."','$key')") : NULL;
                $locationAddressAdd['specialty'] = is_object($bInfo) ? $bInfo->group_specialty : "NULL";
                $locationAddressAdd['tax_id'] = is_object($bInfo) ?  DB::raw("AES_ENCRYPT('".$bInfo->facility_tax_id."','$key')") : NULL;
                $locationAddressAdd['npi'] = is_object($bInfo) ?  DB::raw("AES_ENCRYPT('".$bInfo->facility_npi."','$key')") : NULL;
                $locationAddressAdd['city'] = is_object($cInfo) ? $cInfo->city : NULL;
                $locationAddressAdd['state'] = is_object($cInfo) ? $cInfo->state : NULL;
                $locationAddressAdd['zip_code'] = is_object($cInfo) ? $cInfo->zip_code : NULL;
                $locationAddressAdd['number_of_individual_provider'] = is_object($practiseInfo) ? $practiseInfo->number_of_individual_provider : NULL;
                $locationAddressAdd['number_of_physical_location'] = is_object($baInfo) ? $baInfo->number_of_physical_location : NULL;

                $locationAddressAdd["user_id"] = $userId;

                $logStr .= $this->makeTheLogMsg($sessionUserName,$locationAddress,[]);

                $isUpdate = $this->addData($tbl,$locationAddressAdd);
            }
            //handle the user activity
            $this->handleUserActivity(
                $userId,$sessionUserId,"Address","update",$logStr,NULL,$this->timeStamp()
            );
            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "identifiers") {
            //handle the user activity
            $this->handleUserActivity(
                $userId,$sessionUserId,"Identifiers","update",json_encode($inputData),NULL,$this->timeStamp()
            );

            $logStr = "";
            $updateCreds = [
                "Identifier" => $inputData["Identifier"],
                "effective_date" => $inputData["effective_date"]
            ];
            if(isset($inputData["credential_id"])) {

                $creds = $this->fetchData("credentialing_tasks",["id" => $inputData["credential_id"]],1,[]);

                $credsArr = $this->stdToArray($creds);

                $logStr .= $this->makeTheLogMsg($sessionUserName,$updateCreds,$credsArr);

                $isUpdate = $this->updateData("credentialing_tasks",["id" =>  $inputData["credential_id"]],$updateCreds);

                $updateLinks = [
                    "user_name" => $inputData["user_name"],
                    "password" => $inputData["password"]
                ];

                $portals = $this->fetchData("portals",["id" => $inputData["protal_id"]],1,[]);

                $portalsArr = $this->stdToArray($portals);

                $logStr .= $this->makeTheLogMsg($sessionUserName,$updateLinks,$portalsArr);

                $isUpdate = $this->updateData("portals",["id" =>  $inputData["protal_id"]],$updateLinks);

                return $this->successResponse(["is_updated" => $isUpdate], "success");
            }
            else {
                $myString = $inputData["password"];
                $findMe   = '***';
                $pos = strpos($myString, $findMe);

                $portals = $this->fetchData("portals",["id" => $inputData["protal_id"]],1,[]);

                $portalsArr = $this->stdToArray($portals);


                // Note our use of ===.  Simply == would not work as expected
                // because the position of 'a' was the 0th (first) character.
                if ($pos === false) {
                    $isUpdate = $this->updateData("portals",["id" =>  $inputData["protal_id"]],["Identifier" => $inputData["Identifier"],"user_name" =>  $inputData["user_name"],"password" => $inputData["password"]]);
                    $logStr .= $this->makeTheLogMsg($sessionUserName,["Identifier" => $inputData["Identifier"],"user_name" =>  $inputData["user_name"],"password" => $inputData["password"]],$portalsArr);
                } else {
                    $isUpdate = $this->updateData("portals",["id" =>  $inputData["protal_id"]],["Identifier" => $inputData["Identifier"],"user_name" =>  $inputData["user_name"] ]);
                    $logStr .= $this->makeTheLogMsg($sessionUserName,["Identifier" => $inputData["Identifier"],"user_name" =>  $inputData["user_name"] ],$portalsArr);
                }
                return $this->successResponse(["is_updated" => $isUpdate], "success");
            }
        }
        elseif($inputData["section"] == "hours_of_operation") {
            $tbl = $this->tbl;
            $updateScheduels = [
                "monday_from" => $inputData["monday_from"],
                "monday_to" => $inputData["monday_to"],
                "tuesday_from" => $inputData["tuesday_from"],
                "tuesday_to" => $inputData["tuesday_to"],
                "wednesday_from" => $inputData["wednesday_from"],
                "wednesday_to" => $inputData["wednesday_to"],
                "thursday_from" => $inputData["thursday_from"],
                "thursday_to" => $inputData["thursday_to"],
                "friday_from" => $inputData["friday_from"],
                "friday_to" => $inputData["friday_to"],
                "saturday_from" => $inputData["saturday_from"],
                "saturday_to" => $inputData["saturday_to"],
                "sunday_from" => $inputData["sunday_from"],
                "sunday_to" => $inputData["sunday_to"]
            ];

            $scheduels = $this->fetchData($tbl,$where,1,[]);

            $scheduelsArr = $this->stdToArray($scheduels);

            $logStr = $this->makeTheLogMsg($sessionUserName,$updateScheduels,$scheduelsArr);

             //handle the user activity
             $this->handleUserActivity(
                $userId,$sessionUserId,"Hours Of Operation","update",$logStr,NULL,$this->timeStamp()
            );
            $isUpdate = $this->updateData($tbl,$where,$updateScheduels);

            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "location_summary") {
            $tbl = $this->tbl;
            $updateSummary = [
                "location_summary" => $inputData["location_summary"]
            ];
            $scheduels = $this->fetchData($tbl,$where,1,[]);

            $scheduelsArr = $this->stdToArray($scheduels);

            $logStr = $this->makeTheLogMsg($sessionUserName,$updateSummary,$scheduelsArr);

            $isUpdate = $this->updateData($tbl,$where,$updateSummary);
            //handle the user activity
            $this->handleUserActivity(
                $userId,$sessionUserId,"Location Summary","update",$logStr,NULL,$this->timeStamp()
            );

            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "ownership_info") {

            $updateOwnerShipInfo = [
                "first_name" => $inputData["first_name"],
                "last_name" => $inputData["last_name"],
                "dob" => $inputData["dob"],
                "state_of_birth" => $inputData["state_of_birth"],
                "country_of_birth" => $inputData["country_of_birth"],
                "ss_number" => $inputData["ss_number"],
                "ownership_perc" => $inputData["ownership_perc"],
                "ownership_effective_date" => $inputData["ownership_effective_date"],
                "is_partnership" => $inputData["is_partnership"],
                "user_id" => $userId,
                "index_id" => 1,
                "num_of_owners" => $inputData["num_of_owners"]
            ];
            $hasownerInfo = $this->fetchData("user_ddownerinfo",$where,1,[]);
            if(!is_object($hasownerInfo)) {
                $isUpdate = $this->addData("user_ddownerinfo",$updateOwnerShipInfo);

                $logStr = $this->makeTheLogMsg($sessionUserName,$updateOwnerShipInfo,[]);
            }
            else {
                $isUpdate = $this->updateData("user_ddownerinfo",$where,$updateOwnerShipInfo);
                $hasownerInfoArr = $this->stdToArray($hasownerInfo);
                $logStr = $this->makeTheLogMsg($sessionUserName,$updateOwnerShipInfo,$hasownerInfoArr);
            }
            //handle the user activity
            $this->handleUserActivity(
                $userId,$sessionUserId,"Ownership Info","update",$logStr,NULL,$this->timeStamp()
            );
            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "affiliated_locations") {
            $key = $this->key;
            $logStr = "";
            if($inputData["is_update"] == 1) {
                $id = $inputData["location_id"];
                $locationUpdate = [
                    "practise_address" => DB::raw("AES_ENCRYPT('".$inputData["practise_address"]."','$key')"),
                    "npi" => DB::raw("AES_ENCRYPT('".$inputData["npi"]."','$key')")
                ];
                $pli = $this->fetchData($this->tbl,["id" => $id],1,[]);

                $pliArr = $this->stdToArray($pli);

                $logStr = $this->makeTheLogMsg($sessionUserName,$locationUpdate,$pliArr);

                $isUpdate = $this->updateData($this->tbl,["id" => $id],$locationUpdate);

            }
            else {
                $locationUpdate = [
                    "practise_address" => DB::raw("AES_ENCRYPT('".$inputData["practise_address"]."','$key')"),
                    "npi" => DB::raw("AES_ENCRYPT('".$inputData["npi"]."','$key')"),
                    "user_id" => $userId,
                    "index_id" => 1
                ];
                $logStr = $this->makeTheLogMsg($sessionUserName,$locationUpdate,[]);
                $isUpdate = $this->addData($this->tbl,$locationUpdate);
            }
            //handle the user activity
            $this->handleUserActivity(
                $userId,$sessionUserId,"Affliated Locations","update",$logStr,NULL,$this->timeStamp()
            );
            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "insurance_coverage") {

            $insuranceCoverageObj = new InsuranceCoverage();
            $incUpdate = [
                "address_line_one" =>  $inputData["address_line_one"],
                "address_line_two" =>  $inputData["address_line_two"],
                "amount_coverage_aggregate" =>  $inputData["amount_coverage_aggregate"],
                "amount_coverage_occurance" =>  $inputData["amount_coverage_occurance"],
                "city" =>  $inputData["city"],
                "effective_date" =>  $inputData["effective_date"],
                "expiration_date" =>  $inputData["expiration_date"],
                "malpractice_name" =>  $inputData["malpractice_name"],
                "phone_number" =>  $inputData["phone_number"],
                "policy_number" =>  $inputData["policy_number"],
                "state" =>  $inputData["state"],
                "zip_code" =>  $inputData["zip_code"],
            ];
            $logStr= "";
            $recAdded = $insuranceCoverageObj->fetchInsuranceCoverage($userId);
            if(is_object($recAdded)) {

                $isUpdate = $insuranceCoverageObj->updateInsuranceCoverage($userId,$incUpdate);

                $recAddedArr = $this->stdToArray($recAdded);

                $logStr = $this->makeTheLogMsg($sessionUserName,$incUpdate,$recAddedArr);
            }
            else {
                $incUpdate["user_id"] = $userId;
                $isUpdate = $insuranceCoverageObj->addInsuranceCoverage($incUpdate);
                $logStr = $this->makeTheLogMsg($sessionUserName,$incUpdate,[]);
            }
             //handle the user activity
             $this->handleUserActivity(
                $userId,$sessionUserId,"Insurance Coverage","update",$logStr,NULL,$this->timeStamp()
            );
            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "invidual_provider_info") {

            $key = $this->key;

            $tbl = $this->tblU;

            $ipiUpdate = [
                "address_line_one" =>  DB::raw("AES_ENCRYPT('".$inputData["address_line_one"]."','$key')"),
                "address_line_two" =>  DB::raw("AES_ENCRYPT('".$inputData["address_line_two"]."','$key')"),
                "phone" =>  DB::raw("AES_ENCRYPT('".$inputData["cell_phone"]."','$key')"),
                "city" =>  $inputData["city"],
                "dob" =>  DB::raw("AES_ENCRYPT('".$inputData["dob"]."','$key')"),
                "fax" =>  DB::raw("AES_ENCRYPT('".$inputData["fax"]."','$key')"),
                "first_name" =>  $inputData["first_name"],
                "gender" =>  $inputData["gender"],
                "last_name" =>  $inputData["last_name"],
                "place_of_birth" =>  $inputData["place_of_birth"],
                "state" =>  $inputData["state"],
                "status" =>  $inputData["status"],
                "supervisor_physician" =>  isset($inputData["supervisor_physician"]) ? $inputData["supervisor_physician"] : NULL,
                "visa_number" =>  DB::raw("AES_ENCRYPT('".$inputData["visa_number"]."','$key')") ,
                "work_phone" =>  DB::raw("AES_ENCRYPT('".$inputData["work_phone"]."','$key')") ,
                "zip_code" =>  $inputData["zip_code"],
                "eligible_to_work" => $inputData["eligible_to_work"],
                'email' => DB::raw("AES_ENCRYPT('".$inputData["email"]."','$key')")
            ];

            // The $cols array will now be:
            $cols = [
                DB::raw("AES_DECRYPT(address_line_one,'$key') as address_line_one"),
                DB::raw("AES_DECRYPT(address_line_two,'$key') as address_line_two"),
                DB::raw("AES_DECRYPT(phone,'$key') as phone"),
                "city",
                DB::raw("AES_DECRYPT(dob,'$key') as dob"),
                DB::raw("AES_DECRYPT(fax,'$key') as fax"),
                "first_name",
                "gender",
                "last_name",
                "place_of_birth",
                "state",
                "status",
                "supervisor_physician",
                DB::raw("AES_DECRYPT(visa_number,'$key') as visa_number"),
                DB::raw("AES_DECRYPT(work_phone,'$key') as work_phone"),
                "zip_code",
                "eligible_to_work",
                DB::raw("AES_DECRYPT(email,'$key') as email"),
                DB::raw("AES_DECRYPT(ssn,'$key') as ssn")
            ];

            if(isset($inputData["citizenship"])) {
                $ipiUpdate["citizenship_id"] = $inputData["citizenship"];
            }

            $myString =  isset($inputData["ssn"]) ? $inputData["ssn"] : "";
            $findMe   = 'XXX';
            $pos = strpos($myString, $findMe);

            // Note our use of ===.  Simply == would not work as expected
            // because the position of 'a' was the 0th (first) character.
            if ($pos === false) {
                //"ssn" =>  $inputData["ssn"],
                $ipiUpdate['ssn'] = DB::raw("AES_ENCRYPT('".$inputData["ssn"]."','$key')");
            }

            $user = $this->fetchData($tbl,["id" => $userId],1,$cols);

            $userArr = $this->stdToArray($user);

            // $this->printR($userArr,true);
            $logStr = $this->makeTheLogMsg($sessionUserName,$ipiUpdate,$userArr);

            //handle the user activity
            $this->handleUserActivity(
                $userId,$sessionUserId,"Profile","update",$logStr,NULL,$this->timeStamp()
            );
            $isUpdate = $this->updateData($tbl,["id" => $userId],$ipiUpdate);

            return $this->successResponse(["is_updated" => $isUpdate], "success");
        }
        elseif($inputData["section"] == "education") {

            // $whereEd = [
            //     ["education_type","=","professional"],
            //     ["user_id","=",$userId]
            // ];
            $logStr = "";
            if(isset($inputData["professional"])) {
                $professional = $inputData["professional"];
                $professional["user_id"] = $userId;
                if($professional["can_update"] > 0) {
                    $rowId = $professional["can_update"];
                    unset($professional["can_update"]);
                    //return $this->successResponse(["is_updated" => $professional], "success");
                    $pedu = $this->fetchData("education",["id" => $rowId],1,[]);

                    $peduArr = $this->stdToArray($pedu);
                    $logStr .= $this->makeTheLogMsg($sessionUserName,$professional,$peduArr);
                    $id= $this->updateData("education",["id" => $rowId,"education_type" => 'professional'],$professional);
                }
                else {
                    unset($professional["can_update"]);
                    $professional["facility_id"] = 0;
                    $professional["program_completed"] = 0;
                    $professional["program_director"] = NULL;
                    $professional["current_program_director"] = NULL;
                    $professional["created_by"] = $inputData["session_user_id"];
                    $id = $this->addData("education",$professional);
                    $logStr .= $this->makeTheLogMsg($sessionUserName,$professional,[]);
                }
            }
            if(isset($inputData["post_graduate_update"])) {
                $postGraduate = json_decode($inputData["post_graduate_update"],true);
                if(count($postGraduate)) {
                    foreach($postGraduate as $postGrad) {
                        $rowId = $postGrad["can_update"];
                        unset($postGrad["can_update"]);
                        $postGrad["user_id"] = $userId;
                        $facilityId = $postGrad["facility"];
                        $facilityIdObj = $this->fetchData("facilities",["facility" => $facilityId],1,["id"]);
                        $facilityId = $facilityIdObj->id;
                        $postGrad["facility_id"] = $facilityId;
                        unset($postGrad["facility"]);

                        $pedu = $this->fetchData("education",["id" => $rowId,"education_type" => 'post_graduate'],1,[]);

                        $peduArr = $this->stdToArray($pedu);

                        $logStr .= $this->makeTheLogMsg($sessionUserName,$postGrad,$peduArr);

                        $id= $this->updateData("education",["id" => $rowId],$postGrad);
                    }
                }
            }
            if(isset($inputData["post_graduate_add"])) {
                //return $this->successResponse(["is_updated" => $professional], "success");
                $postGradAdd = json_decode($inputData["post_graduate_add"],true);
                if(count($postGradAdd) > 0 ) {
                    //$facilityId = $postGradAdd["facility_id"];
                    foreach($postGradAdd as $newPostGrad) {
                        $facilityIdObj = $this->fetchData("facilities",["facility" => $newPostGrad['facility']],1,["id"]);
                        if(is_object($facilityIdObj)) {
                            $facilityId = $facilityIdObj->id;
                            $newPostGrad["facility_id"] = $facilityId;
                            $newPostGrad["user_id"] = $userId;
                            $newPostGrad["issuing_institute"] = NULL;
                            unset($newPostGrad["facility"]);
                            //unset($postGradAdd["can_update"]);
                            $id = $this->addData("education",$newPostGrad);
                            $logStr .= $this->makeTheLogMsg($sessionUserName,$newPostGrad,[]);
                        }
                    }
                }
            }
             //handle the user activity
             $this->handleUserActivity(
                $userId,$sessionUserId,"Education","update",$logStr,NULL,$this->timeStamp()
            );
            return $this->successResponse(["is_updated" => $id], "success");


        }
        elseif($inputData["section"] =="hospital_affiliations") {

            $hAData = [
                "user_id" => $inputData["user_id"],
                "hospital_previleges" => isset($inputData["hospital_previleges"]) ? $inputData["hospital_previleges"] : NULL,
                "admitting_arrangements" => $inputData["admitting_arrangements"],
                "admitting_previleges" => $inputData["admitting_previleges"],
                "start_date" => $inputData["start_date"],
                "address_line_one" => $inputData["address_line_one"],
                "address_line_two" => $inputData["address_line_two"],
                "city" => $inputData["city"],
                "state" => $inputData["state"],
                "zipcode" => $inputData["zipcode"],
                "phone" => $inputData["phone"],
                "fax" => $inputData["fax"],
                "email" => $inputData["email"]

            ];
            $hasData = $this->fetchData("hospital_affiliations",$where,1,[]);
            $id = "";
            if(is_object($hasData)) {
                $hasDataArr = $this->stdToArray($hasData);
                $id = $this->updateData("hospital_affiliations",$where,$hAData);

                $logStr = $this->makeTheLogMsg($sessionUserName,$hAData,$hasDataArr);
            }
            else {
                $id = $this->addData("hospital_affiliations",$hAData);
                $logStr = $this->makeTheLogMsg($sessionUserName,$hAData,[]);
            }
            //handle the user activity

            $this->handleUserActivity(
                $userId,$sessionUserId,"Hospital Affiliations","update",$logStr,NULL,$this->timeStamp()
            );
            return $this->successResponse(["is_updated" => $id], "success");
        }
        elseif($inputData["section"] =="psi") {

            $logStr = "";
            $sectionData = json_decode($inputData["section_data"],true);
            // $this->printR($sectionData,true);
            $id = 0;
            foreach($sectionData as $eachData) {
                if($eachData["can_update"] > 0 ) {
                    $recId = $eachData["can_update"];
                    unset($eachData["can_update"]);

                    if($eachData["certification_date"] =="" && strlen($eachData["certification_date"] ) == 0 ) {
                        $eachData["certification_date"] = NULL;
                    }
                    if($eachData["recertification_date"] =="" && strlen($eachData["recertification_date"] ) == 0 ) {
                        $eachData["recertification_date"] = NULL;
                    }
                    if($eachData["expiration_date"] =="" && strlen($eachData["expiration_date"] ) == 0 ) {
                        $eachData["expiration_date"] = NULL;
                    }


                    $wherePSI = [
                        ["user_id","=",$eachData["user_id"]],
                        ["id","=",$recId]
                    ];

                    $spi = $this->fetchData("specialty_information",$wherePSI,1,[]);

                    $spiArr = $this->stdToArray($spi);

                    $logStr .= $this->makeTheLogMsg($sessionUserName,$eachData,$spiArr);

                    $id = $this->updateData("specialty_information",$wherePSI,$eachData);
                }
                else {
                    if($eachData["certification_date"] =="" && strlen($eachData["certification_date"] ) == 0 ) {
                        $eachData["certification_date"] = NULL;
                    }
                    if($eachData["recertification_date"] =="" && strlen($eachData["recertification_date"] ) == 0 ) {
                        $eachData["recertification_date"] = NULL;
                    }
                    if($eachData["expiration_date"] =="" && strlen($eachData["expiration_date"] ) == 0 ) {
                        $eachData["expiration_date"] = NULL;
                    }

                    unset($eachData["can_update"]);
                    $logStr .= $this->makeTheLogMsg($sessionUserName,$eachData,[]);
                    $id = $this->addData("specialty_information",$eachData,0);
                }
            }
            //handle the user activity
            $this->handleUserActivity(
                $userId,$sessionUserId,"Specialty Information","update",$logStr,NULL,$this->timeStamp()
            );
            return $this->successResponse(["is_updated" => $id], "success");
        }
    }
    /**
     * add the profile portals
     *
     *  @param \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    function addPortals(Request $request) {

        $request->validate([
            "user_name" =>  "required",
            "password"  =>  "required",
            "user_id"   =>  "required",
            "type_id"    => "required",
            "created_by" => "required",
            'type'=>'required|in:provider,facility',
            'for_credentialing'=>'required',
            'report'=>'required',
        ]);
   
        $isAdmin = $request->has("is_admin") ? $request->get("is_admin") : 0;
        $sessionUserId = $request->has('session_userid') ? $request->session_userid :  $this->getSessionUserId($request);

        $identifier = $request->has("identifier") ? $request->identifier : NULL;
        $notes = $request->has("notes") ? $request->notes : NULL;
        $portalType = PortalType::find($request->type_id)->value('name');
        $userId = is_int($request->user_id) ? $request->user_id : (int) $request->user_id;
   
        $addPortals = [
            "mapping_type"      =>$request->type,
            "user_id"           => $userId,
            "user_name"         => $request->user_name,
            "password"          => encrypt($request->password),
            "type_id"           => $request->type_id,
            "notes"             => encrypt($notes),
            "created_by"        => $request->created_by ?? $sessionUserId,
            "created_at"        => $this->timeStamp(),
            'is_admin'          => $isAdmin,
            'for_credentialing' => $request->has('for_credentialing') ? $request->for_credentialing : 0,
            'report'            => $request->has('report') ? $request->report : 0,
            'identifier'=>  $identifier
        ];

        $key            = $this->key;
        $insId = Portal::insertGetId($addPortals);
        return $this->successResponse(["is_added" => true,"id" => $insId], "success");
    }

    /**
     * fetch the portals Logs
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function fetchPortalLogs(Request $request) {
        $request->validate([
            "user_id" => "required"
        ]);
        $userId = $request->user_id;
        $key = $this->key;
        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $logs = PortalLogs::select(
            'portal_logs.id','portal_logs.portal_id','portal_logs.created_by','portal_logs.created_at',
            DB::raw("AES_DECRYPT(cm_portal_logs.logs,'$key') as logs"),
            DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ',COALESCE(cm_users.last_name,'')) AS created_by_name")
        )
        ->join("users","users.id","=","portal_logs.created_by")
        ->where('portal_logs.user_id', $userId)
        ->orderby('portal_logs.id', 'DESC');
        $logs = $logs->paginate($perPage);
        if($logs->count() > 0) {
            foreach($logs as $log) {
                $log->human_readable = $this->humanReadableTimeDifference($log->created_at);
            }
        }
        return $this->successResponse(['logs'=>$logs], "success");
    }

    /**
     * fetch the portals types
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function portalsTypes(Request $request) {
        $portalsTypes = $this->fetchData("portal_types","");
        return $this->successResponse(["portals_types" => $portalsTypes], "success");
    }
    /**
     * fetch the credentialing tasks attachments
     */
    function fetchCredsAttachments(Request $request) {
        $request->validate([
            "credstask_id" => "required"
        ]);

        $attachmentObj =  new Attachments();

        $attachments = $attachmentObj->fetchAttachments($request->credstask_id);

        return $this->successResponse(["attachments" => $attachments], "success");
    }
    /**
     * feth credentailing taks filters data
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchCredsFilters(Request $request) {

        $usageType = $request->has('usage_type') ? $request->get('usage_type') : "both" ;

        $sessionUserId = $this->getSessionUserId($request);

        if($usageType == "both")  {
            $users = $this->sysUsers();
        }
        elseif($usageType == "ar") {
            $users = $this->sysUsers([12,13]);
        }
        elseif($usageType == "creds") {
            $users = $this->sysUsers([6,7]);
        }

        $usersFiltterArr = [];
        foreach($users as $user) {
            array_push($usersFiltterArr,["value" => $user->id , "label" => $user->full_name]);
        }

        $payersFilter      = Payer::where("for_credentialing", "=" ,"1")

        ->select("id AS value","payer_name AS label")

        ->orderBy('label')

        ->get();

        // $payersFilter = [];
        // foreach ($payers as $payer) {
        //     array_push($payersFilter,["value" => $payer->id,"label" => $payer->payer_name]);
        // }
        $credentialing   = new Credentialing();
        $forCreds = $request->has("for_credentialing") ? $request->get("for_credentialing") : 1;
        $allProviders =  $credentialing->fetchAllUsers($forCreds);

        $allProvidersArr = [];
        foreach($allProviders as $puser) {
            array_push($allProvidersArr,["value" => $puser->id , "label" => $puser->name]);
        }

        $statusData      = $this->fetchData("credentialing_status","");
        $statusFilter = [];
        foreach($statusData as $status) {
            array_push($statusFilter,["value" => $status->id , "label" => $status->credentialing_status]);
        }
        if($request->has("for_credentialing") && $request->get("for_credentialing")=="1") {
            $activePractices = $credentialing->fetchCredentialingUsersLI(false,"",$sessionUserId);
            $activePractices = $activePractices["practices"];
            // $this->printR($activePractices,true);
            $activePracticesIds = array_column($activePractices,"facility_id");
            $activePractices = $credentialing->fetchCredentialingUsers($activePracticesIds,1,"",$sessionUserId);
            // $this->printR($activePractices,true);

        }
        else {
            if($request->has("for_credentialing") && $request->get("for_credentialing")=="0") {

                $activePractices = $credentialing->fetchCredentialingUsersLIInActive(false,"",$sessionUserId);
                $activePractices = $activePractices["practices"];

                $activePracticesIds = array_column($activePractices,"facility_id");
                //$this->printR($activePracticesIds,true);
                $activePractices = $credentialing->fetchCredentialingUsersInActive($activePracticesIds,0,"",$sessionUserId);
                // $this->printR($activePracticesIds,true);
            }
            else {
                // exit('in else');
                if($request->has('has_practice') && $request->has_practice == 1) {
                    $activePractices = $credentialing->fetchCredentialingUsersLI(false,"",$sessionUserId);
                    $activePractices = $activePractices["practices"];
                    // $this->printR($activePractices,true);
                }
                else {
                    $activePractices = $credentialing->fetchCredentialingUsersLI(false,"",$sessionUserId);
                    $activePractices = $activePractices["practices"];
                    // $this->printR($activePractices,true);
                    $activePracticesIds = array_column($activePractices,"facility_id");
                    $activePractices = $credentialing->fetchCredentialingUsers($activePracticesIds,1);
                }
                // $this->printR($activePracticesIds,true);

            }
        }

        // $this->printR($activePractices,true);
        $practicsArrFilter = [];
        if($request->has('has_practice') && $request->has_practice == 1) {
            foreach($activePractices as $activePractice) {
                array_push($practicsArrFilter,["value" => $activePractice->facility_id , "label" => $activePractice->doing_buisness_as]);
            }
        }
        else {
            foreach($activePractices as $activePractice) {
                array_push($practicsArrFilter,["value" => $activePractice->facility_id , "label" => $activePractice->practice_name]);
            }
        }
        // $this->printR($practicsArrFilter,true);
        return $this->successResponse([
            "payers" => $payersFilter,"status" => $statusFilter,
            "practices" => $practicsArrFilter,
            "users" => $usersFiltterArr,
            "all_providers" => $allProvidersArr
        ],"success");

    }
    /**
     * fetch practices based on stats
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    function fetchPractices(Request $request) {
        $sessionUserId = $this->getSessionUserId($request);
        $credentialing   = new Credentialing();
        $practices = $request->is_active == 1 ? $credentialing->fetchCredentialingUsersLI(false, "", $sessionUserId): $credentialing->fetchCredentialingUsersLIInActive(false, "", $sessionUserId);
        // $this->printR($practices,true);
        $practices = $practices['practices'];
        // $this->printR($practices,true);
        $practicsArrFilter = [];
        foreach($practices as $practice) {
            if($practice->facility_id)
                array_push($practicsArrFilter,["value" => $practice->facility_id , "label" => $practice->doing_buisness_as]);
        }
        return $this->successResponse([
            "practices" => $practicsArrFilter,
        ],"success");
    }
    /**
     * fetch the active practice
     *
     */
    function getActivePractices() {
        $key = $this->key;
        return DB::table($this->tblU.' as u')
        ->select("u.id as value ",DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$key') AS label"))
        ->join('user_role_map as urm',function($join) {
            $join->on('urm.user_id','=','u.id')
            ->where('urm.role_id','=',9);
        })
        ->join($this->tbl.' as pli',function($join) {
            $join->on([
                ['pli.user_id','=','u.id'],
                ['pli.user_parent_id','=','u.id']
                ]);
        })
        ->where('u.deleted','=','0')

        ->orderBy('label')

        ->get();
    }
    /**
     * fetch provider selected locations
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchSelectedLocationProviders(Request $request) {

        $credentialing   = new Credentialing();
        $providersFilter = [];
        if(!$request->has('is_single') && !$request->has('is_multi') && $request->has('location_ids')) {
            $selLocations = json_decode($request->location_ids,true);

            $ids = array_column($selLocations,"value");
            if(count($selLocations)) {
                $idsStr = implode(",",$ids);

                $filterRes = $credentialing->fetchAllProviders($idsStr);

                // $this->printR($filterRes,true);

                foreach($filterRes as $eachRes) {
                    array_push($providersFilter,["value" => $eachRes->id , "label" => $eachRes->name]);
                }
            }
            else
                $providersFilter = [];
        }
        if($request->has('is_single')) {
            $locationId = $request->location_id;
            $isActive = $request->is_active;
            $filterRes = $credentialing->fetchLinkedUsers($locationId,0,$isActive);
            // $this->printR($filterRes,true);
            // exit("here".$locationId);
            foreach($filterRes as $eachRes) {
                array_push($providersFilter,["value" => $eachRes->individual_id , "label" => $eachRes->name]);
            }
        }
        if($request->has('is_multi')) {
            $locationId = $request->location_id;
            $isActive = $request->is_active;
            $filterRes = $credentialing->fetchFacilityProviders($locationId,$isActive);
            // $this->printR($filterRes,true);
            // exit("here".$locationId);
            foreach($filterRes as $eachRes) {
                array_push($providersFilter,["value" => $eachRes->id , "label" => $eachRes->name]);
            }
        }
        if(!$request->has('is_single') && !$request->has('is_multi') && !$request->has('location_ids')) {
            $isActive = $request->is_active;
            $allProviders =  $credentialing->fetchAllUsers($isActive);
            foreach($allProviders as $puser) {
                array_push($providersFilter,["value" => $puser->id , "label" => $puser->name]);
            }
        }

        return $this->successResponse(["filtered_providers" => $providersFilter],"success");
    }
    /**
     * fetch the providers dependent on the facility / status
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchProviders(Request $request) {
        $providersFilter = [];
        $credentialing   = new Credentialing();
        $facilityId = json_decode($request->facility_ids,true);
        try {
            $ids = array_column($facilityId,"value");
            $ids = implode(",",$ids);
        }
        catch(\Exception $e) {
            $ids = "";
        }
        $isActive = $request->is_active;
        $filterRes = $credentialing->fetchFacilityProviders($ids,$isActive);
        // $this->printR($filterRes,true);
        // exit("here".$locationId);
        array_push($providersFilter,["value" => 0 , "label" => "All"]);
        foreach($filterRes as $eachRes) {
            if($eachRes->name !="Facility")
                array_push($providersFilter,["value" => $eachRes->id , "label" => $eachRes->name]);
        }
        return $this->successResponse(["filtered_providers" => $providersFilter],"success");
    }
    /**
     * fetch practice locations
     *
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchSelectPracticeLocations(Request $request) {
        $selPracticeIds = json_decode($request->practice_ids,true);
        $sessionUserId = $this->getSessionUserId($request);
        try {
            $ids = array_column($selPracticeIds,"value");
        }
        catch(\Exception $e) {
            $ids = "";
        }
        $isActive = $request->has('is_active') ? $request->get('is_active') : 1;

        // $this->printR($ids,true);
        $activeLocationsFilter = [];
        if(count($selPracticeIds)) {
            $credentialing   = new Credentialing();
            $locations = $credentialing->fetchCredentialingUsers($ids,$isActive,"",$sessionUserId);
            if(count($locations)) {
                foreach($locations as $credesLocationUser) {
                    array_push($activeLocationsFilter,["value" => $credesLocationUser->facility_id , "label" => $credesLocationUser->practice_name]);
                }
            }
        }
        return $this->successResponse(["filtered_locations" => $activeLocationsFilter],"success");
    }
    /**
     * fetch practice locations
     *
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchFacility(Request $request) {
        $sessionUserId = $this->getSessionUserId($request);

        $selPracticeIds = json_decode($request->practice_ids,true);
        try {
            $ids = array_column($selPracticeIds,"value");
        }
        catch(\Exception $e) {
            $ids = "";
        }
        $isActive = $request->has('is_active') ? $request->get('is_active') : 1;
        // $this->printR($ids,true);
        $activeLocationsFilter = [];
        if($request->has('filter_type') && $request->filter_type == 0)
            array_push($activeLocationsFilter,["value" => 0 , "label" => "All"]);

        if(count($selPracticeIds)) {
            $credentialing   = new Credentialing();
            $locations = $isActive == 1 ? $credentialing->fetchCredentialingUsers($ids,$isActive, "", $sessionUserId) :  $credentialing->fetchCredentialingUsersInActive($ids,$isActive, "", $sessionUserId);
            if(count($locations)) {
                foreach($locations as $credesLocationUser) {
                    if($credesLocationUser->practice_name !="Facility")
                        array_push($activeLocationsFilter,["value" => $credesLocationUser->facility_id , "label" => $credesLocationUser->practice_name]);
                }
            }
        }
        return $this->successResponse(["filtered_locations" => $activeLocationsFilter],"success");
    }
    /**
     * fetch the credentailing status
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchCredentialingStatus(Request $request) {

        $credsStatus = $this->fetchData("credentialing_status","");

        $credsStatusFilter = [];
        foreach($credsStatus as $credStatus) {
            array_push($credsStatusFilter,["value" => $credStatus->id , "label" => $credStatus->credentialing_status]);
        }
        return $this->successResponse(["status" => $credsStatusFilter],"success");
    }
    /**
     * fetch the practice / facility  providers
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchPracticeFacilityProviders(Request $request) {

        $request->validate([
            "practice_ids" => "required"
        ]);
        $practiceIds = json_decode($request->practice_ids,true);
        $providers = DB::table("user_ddpracticelocationinfo")
        ->selectRaw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as provider_name,cm_user_dd_individualproviderinfo.user_id as provider_id")
        ->join('user_dd_individualproviderinfo','user_dd_individualproviderinfo.parent_user_id','=','user_ddpracticelocationinfo.user_id')
        ->join('users','users.id','=','user_dd_individualproviderinfo.user_id')
        ->whereIn('user_ddpracticelocationinfo.user_parent_id',$practiceIds);

        if($request->has('facility_ids')) {
            $facilityIds = json_decode($request->facility_ids,true);
            $providers = $providers->whereIn('user_ddpracticelocationinfo.user_id',$facilityIds);
        }
        $providers = $providers->get();
        return $this->successResponse(["providers" => $providers],"success");
    }
}
