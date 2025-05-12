<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UserManagement;
use App\Models\User;
use App\Http\Traits\Utility;
use App\Http\Traits\ApiResponseHandler;
use Illuminate\Support\Facades\Hash;
use App\Http\Middleware\UserManagementMiddleware;
use Illuminate\Support\Facades\Mail;
use App\Mail\ChangePassword;

class UserManagementController extends Controller
{
    use Utility, ApiResponseHandler;
   // public $userManagement = "";
    private $key = "";

    public function __construct()
    {
        $this->key = env("AES_KEY");
        $this->middleware(UserManagementMiddleware::class);
        //$this->userManagement = new UserManagement();
    }

    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $company_id)
    {
        $userManagement = new UserManagement();
        $result = $userManagement->fetchAll($company_id);
        return $this->successResponse(["resp" => $result], "success");
    }
    
    /**
     * Retreive all available user roles.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    
    public function getAllRoles(Request $request)
    {
        $result = DB::table('roles')->selectRaw('id, role_name')->whereNotIn('id', [3, 9])->get();
        return $this->successResponse(["resp" => $result], 'success');

    }


    /**
     * Retreive all available practicis.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    
     public function getAllPracticies(Request $request)
     {

        $status = $request->status;

        $userManagement = new UserManagement();
        $practicies = $userManagement->getPractices($status);
        
        return $this->successResponse(["resp" => $practicies], 'success');
     }


    /**
     * Retreive all available facilities.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    
     public function getAllFacilities(Request $request)
     {
        $parent = $request->parentId;
        $status = $request->status;

        $parentId = json_decode($parent);

        $userManagement = new UserManagement();
        $facilities = $userManagement->getFacilities($parentId, $status);
        
        return $this->successResponse(["resp" => $facilities], 'success');
    }
    
    /**
     * general filter function for all filters
     */
    public function filter(Request $request)
    {

        $companyId = $request->companyId;
        $role = $request->role;
        $practice = $request->practice;
        $facility = $request->facility;
        $type = $request->type;
        $smartSearch = $request->keyword;
        $deleted = $request->deleted;

        $roleIds = json_decode($role, true);
        $practiceIds = json_decode($practice, true);
        $facilityIds = json_decode($facility, true);
        if(count($facilityIds) == 0) {
            $mergePF = $practiceIds;//array_merge($practiceIds,$facilityIds);
        }
        else {
            $mergePF = $facilityIds;
        }
        $types = json_decode($type, true);

        $key = $this->key;

        $result = DB::table(function ($query) use ($key, $mergePF) {
            $query->select('users.id',
                DB::raw("CONCAT(cm_users.last_name, ', ', cm_users.first_name) AS user_name"),
                DB::raw("AES_DECRYPT(cm_users.email, '$key') AS email"),
                DB::raw("CASE
                            WHEN cm_user_role_map.role_id IN (3, 9, 4) THEN 1
                            ELSE 0
                        END AS user_type"),
                DB::raw("AES_DECRYPT(cm_users.phone, '$key') AS phone"),
                'users.gender',
                'users.profile_image',
                DB::raw("CONCAT(cm_users.city, ' ', cm_users.state) AS location"),
                'users.deleted',
                'users.is_complete',
                'users.status',
                'roles.role_name',
                // 'ILM.location_user_id as location',
                // 'facilities.user_id as facility_id',
                'companies.id as company_id',
                'roles.id as role_id',
                'ELM.location_user_id as location_id1',
                'ILM.location_user_id as location_id2',
                DB::raw("(SELECT session_buid_at
                   FROM cm_user_accountactivity
                   WHERE user_id = cm_users.id
                   ORDER BY session_buid_at DESC
                   LIMIT 1) AS lastLogin")
            )
            ->from('users')
            ->leftJoin('user_company_map', 'user_company_map.user_id', '=', 'users.id')
            ->leftJoin('companies', 'companies.id', '=', 'user_company_map.company_id')
            ->leftJoin('user_role_map', 'user_role_map.user_id', '=', 'users.id')
            ->leftJoin("roles", function ($join) {
                $join->on("roles.id", "=", "user_role_map.role_id")
                    ->whereNotIn("roles.id", [3, 9]);
            })
            ->leftJoin('emp_location_map as ELM', 'ELM.emp_id', '=', 'users.id')
            ->leftJoin('individualprovider_location_map as ILM', 'ILM.user_id', '=', 'users.id')
            ->groupBy('users.id');

            if (!empty($mergePF)) {
                $query->whereIn('ELM.location_user_id', $mergePF)
                         ->orWhereIn('ILM.location_user_id', $mergePF);
            }
            }, 'T')
            ->where('T.company_id', $companyId);
            if(count($roleIds)){
                $result = $result->whereIn('T.role_id', $roleIds);
            }
            if (count($types)) {
                $result = $result->where('T.user_type', $types);
            }
            if (strlen($smartSearch)) {
                $phoneNumber = $this->sanitizePhoneNumber($smartSearch);
                if(is_numeric($phoneNumber))
                    $smartSearch = $phoneNumber;
                else
                    $smartSearch = $smartSearch;

                
                $result = $result->whereRaw("(cm_T.user_name LIKE '%$smartSearch%' OR cm_T.email LIKE '%$smartSearch%' OR cm_T.phone LIKE '%$smartSearch%' OR cm_T.location LIKE '%$smartSearch%' OR cm_T.gender LIKE '%$smartSearch%')");
            }
            // echo 'deleted : '.$request->deleted . '\n';
            // exit;
            if ($deleted != 'null') {
                $result = $result->where('T.deleted', $deleted);
            }
            $result = $result->orderBy("lastLogin","DESC")
        
            // $sql = $result->toSql();
            // echo $sql;
            // exit;
            ->paginate($this->cmperPage);

            
        return $this->successResponse(['resp' => $result], 'success');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getUserById(Request $request, $userId)
    {

        $userManagement = new UserManagement();
        $response = $userManagement->getUserById($userId);
        return $this->successResponse(["resp" => $response], 'success');
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addUser(Request $request)
    {
        $key = $this->key;

        $request->validate([
            "companyId" => "required",
            "firstName" => "required",
            "email" => "required",
            "password" => "required",
            "gender" => "required",
            "role" => "required",
        ]);

        $companyId = $request->companyId;
        $roleId = $request->role;
        
        $userData = [
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'email' => $request->email,
            'password' => $request->password,
            'gender' => $request->gender,
            'emp_number' => $request->employeeNumber,
            'cnic' => $request->cnicNumber,
        ];
        
        $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$request->email'")->count();
        
        if ($user ) {
            return $this->warningResponse([],"A user is already registered with provided email.",401);
        } else {

            $userManagement = new UserManagement();

            $response = $userManagement->addUser($userData, $companyId, $roleId);
            return $this->successResponse(["resp" => $response], 'success');
        }

    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $key = $this->key;
        $request->validate([
            "userId" => "required",
            "firstName" => "required",
            "lastName" => "required",
            "email" => "required",
            "role"    => "required",
            "gender" => "required",
            "address1" => "required",
            "phone" => "required",
            "zipCode" => "required",
            "city" => "required",
            "state" => "required",
        ]);

        $userId = $request->userId;
        $role = $request->role;

       

        $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$request->email'")->count();
        
        if ($user ) {
            $userData = [
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'gender' => $request->gender,
                "address_line_one" => $request->address1,
                "address_line_two" => $request->address2,
                "phone" => $this->sanitizePhoneNumber($request->phone),
                "zip_code" => $request->zipCode,
                "city" => $request->city,
                "state" => $request->state
            ];
            $userManagement = new UserManagement();
            $response = $userManagement->updateUser($userId, $userData, $role);

            return $this->successResponse(["resp" => $response, "userId" => $userId], 'success');
            // return $this->warningResponse([],"A user is already registered with provided email.",400);
        } else {

            $userData = [
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'email' => $request->email,
                'gender' => $request->gender,
                "address_line_one" => $request->address1,
                "address_line_two" => $request->address2,
                "phone" => $this->sanitizePhoneNumber($request->phone),
                "zip_code" => $request->zipCode,
                "city" => $request->city,
                "state" => $request->state
            ];

            $userManagement = new UserManagement();
            $response = $userManagement->updateUser($userId, $userData, $role);

            return $this->successResponse(["resp" => $response, "userId" => $userId], 'success');
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

    
    public function updateProfileImage(Request $request)
    {

    $request->validate([
        "userId" => "required",
        "image" => "required"
    ]);

    $id = $request->userId;

    if ($request->hasFile('image')) {

        $file = $request->file("image");
        $fileName = trim($file->getClientOriginalName());
        $ext = explode(".", $fileName)[1];
        $fileName = $id . "_" . "profile_image." . $ext;


        $uploadRes = $this->uploadMyFile($fileName, $file, "images/profile");
        $updateData = ['profile_image' => $fileName];
        if (isset($uploadRes["is_uploaded"]) && $uploadRes["is_uploaded"]) {
            $whereProfile = [
                ["entities", "=", "user_id"],
                ["entity_id", "=", $id]
            ];

            $profileExist = $this->fetchData("attachments", $whereProfile, 1, []);

            if (is_object($profileExist)) {
                $addProfileImage = [
                    "entities" => "user_id",
                    "entity_id" => $id,
                    "field_value" => $fileName,
                    "updated_at" => $this->timeStamp()
                ];
                $this->updateData("attachments", $whereProfile, $addProfileImage);
            } else {
                $addProfileImage = [
                    "entities" => "user_id",
                    "entity_id" => $id,
                    "field_key" => $id . " Profile Image",
                    "field_value" => $fileName,
                    "created_at" => $this->timeStamp()
                ];
                $this->addData("attachments", $addProfileImage);
            }
        }

        $isUpdate = user::where("id", $id)->update($updateData);

        if($isUpdate) {
            $url = user::select('profile_image')->where("id", $id)->first();
            return $this->successResponse(['id' => $id, 'url' => $url], "User update successfully.");
        }

        return $this->successResponse([], "Failed to update the user.");
    }

    return $this->successResponse([], 'No image uploaded.');
    }

    /**
     *  update the status to active/inactive...
     *  @param request Request object 
     *  @return isUpdate to confirm the action.
     */

    public function updateStatus(Request $request) {
        
        $request->validate([
            'userId' => 'required',
            'status' => 'required|in:0,1',
        ]);

        $id = $request->userId;
        $status = $request->status;

        $isUpdated = user::where("id", $id)->update(['deleted' => $status]);

        return $this->successResponse(['isUpdated' => $isUpdated], 'success');
        
    }

    /**
     *  update the status to active/inactive...
     *  @param request Request object 
     *  @return isUpdate to confirm the action.
     */

    public function updateLockStatus(Request $request) {
        
        $request->validate([
            'userId' => 'required',
            'lock' => 'required|in:0,2',
        ]);

        $id = $request->userId;
        $lock = $request->lock;

        $isUpdated = user::where("id", $id)->update(['deleted' => $lock]);

        return $this->successResponse(['isUpdated' => $isUpdated], 'success');
        
    }

    public function fetchUserPracticies(Request $request) {
        
        // dd($request);
        $userType = $request->user_type;
        $userId = $request->userId;

        $userManagement = new UserManagement();
        $result = $userManagement->fetchUserPracticies($userId, $userType);

        return $this->successResponse($result, 'success');
    }

    public function fetchAllPracticies(Request $request) {
        $userType = $request->user_type;
        $userId = $request->userId;

        $userManagement = new UserManagement();
        $result = $userManagement->fetchAllPracticies($userId, $userType);

        return $this->successResponse($result, 'success');
    }

    public function addUserToFacility(Request $request) {
        $userType = $request->user_type;
        $userId = $request->userId;
        $facilities = $request->facilities;

        $userManagement = new UserManagement();
        $result = $userManagement->addUserToFacility($userId, $facilities, $userType);

        return $this->successResponse($result, 'success');
    }

    public function deleteUserFromFacility(Request $request) {
        $userType = $request->user_type;
        $userId = $request->userId;
        $facilityId = $request->facilityId;

        $userManagement = new UserManagement();
        $result = $userManagement->deleteUserFromFacility($userId, $facilityId, $userType);

        return $this->successResponse($result, 'success');
    }

    // methods for roles and priviliges section.....

    public function getAllPrivileges(Request $request) {
        $userManagement = new UserManagement();
        $result = $userManagement->getAllPrivileges();

        return $this->successResponse($result, 'success');
    }

    public function changePassword(Request $request, $userId){
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $newPassword = '';
        $key = $this->key;
    
        $maxIndex = strlen($characters) - 1;
    
        for ($i = 0; $i < 13; $i++) {
            $randomIndex = mt_rand(0, $maxIndex);
            $newPassword .= $characters[$randomIndex];
        }
        

        $userManagement = new UserManagement();
        $result = $userManagement->changePassword($userId, $newPassword);
        $isTokenCleared = false;
        if($result) {
            $msg = "";
            try {
                $email = DB::table('users')->selectRaw("AES_DECRYPT(email, '$key') as email")->where('id', $userId)->get();
                $isTokenCleared = DB::table('personal_access_tokens')->where('tokenable_id', $userId)->delete();
                Mail::to($email[0]->email)->send(new ChangePassword($newPassword));
            } catch (\Throwable $exception) {
                $msg = $exception->getMessage();
                return $this->successResponse(['message' => $msg], 'success');
            }
        }
        return $this->successResponse(['token-cleared' => $isTokenCleared], 'success');
        
    }

    public function getProvidersByFacilityId(Request $request, $facilityId) {

        $userManagement = new UserManagement();
        $result = $userManagement->getProvidersByFacilityId($facilityId);

        return $this->successResponse(['result' => $result], 'success');
    }
    
    public function updatePriviligesByUser(Request $request) {

        $request->validate([
            'user_id' => 'required',
            'data' => 'required'
        ]);
        
        $userId = $request->user_id;
        $providers = $request->providers;
        $data = $request->data;

        $providers = json_decode($providers, true);
        $data = json_decode($data, true);
        
        $userManagement = new UserManagement();
        $result = $userManagement->updatePriviligesByUser($userId, $data);
        return $this->successResponse(['result' => $result], 'success');
    }

    public function deleteBulkFacilities(Request $request) {

        $request->validate([
            'user_id' => 'required',
            'data' => 'required'
        ]);
        $userType = $request->user_type;
        $data = $request->data;
        $data = json_decode($data, true);
        $userId = $request->user_id;

        $userManagement = new UserManagement();
        $result = $userManagement->deleteBulkFacilities($userId, $data, $userType);
        return $this->successResponse(['result' => $result], 'success');
    }

    public function rolePrivileges(Request $request) {

        $request->validate([
            'data' => 'required'
        ]);

        $data = $request->data;

        // $data = json_decode($data, true);

        $userManagement = new UserManagement();
        $result = $userManagement->addRolePrevileges($data);
        return $this->successResponse(['result' => $result], 'success');

    }

    public function getUserFacilityPrivileges(Request $request) {

        $request->validate([
            'user_id' => 'required',
            'role_id' => 'required',
            'facility_id' => 'required'
        ]);

        $userId = $request->user_id;
        $roleId = $request->role_id;
        $facilityId = $request->facility_id;
        
        
        $userManagement = new UserManagement();

        $result = [];
        if($facilityId == 'undefined') {
            $result = $userManagement->getRolePrivileges($roleId);
        } else {
            $result = $userManagement->getUserFaclityPrivileges($userId, $facilityId);
            if(count($result) == 0) {
                $result = $userManagement->getRolePrivileges($roleId);
            }
        }

        return $this->successResponse(['result' => $result], 'success');
    }

    public function resetUserFacilityPrivileges(Request $request) {

        $request->validate([
            'user_id' => 'required',
            'facility_id' => 'required'
        ]);

        $userId = $request->user_id;
        $facilityId = $request->facility_id;
        
        
        $userManagement = new UserManagement();

        $result = $userManagement->resetUserFacilityPrivileges($userId, $facilityId);
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getUserGenericPrivileges(Request $request, $userId) {
        $userManagement = new UserManagement();
        $result = $userManagement->getUserGenericPrivileges($userId);
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getRolePrivileges(Request $request) {
        $request->validate([
            'role_id' => 'required'
        ]);

        $roleId = $request->role_id;

        $userManagement = new UserManagement();
        $result = $userManagement->getRolePrivileges($roleId);

        return $this->successResponse(['result' => $result], 'success');
    }

    public function updateRolePrivileges(Request $request) {

        $request->validate([
            'role_id' => 'required',
            'data' => 'required'
        ]);

        $roleId = $request->role_id;
        $data = $request->data;

        $data = json_decode($data, true);
        
        $userManagement = new UserManagement();
        $result = $userManagement->updateRolePrevileges($roleId, $data);

        return $this->successResponse(['result' => $result], 'success');
    }

    public function getAtomicPrivilege(Request $request) {
        
        $request->validate([
            'user_id' => 'required',
            'role_id' => 'required',
            'facility_id' => 'required',
            'section' => 'required',
            'sub_section' => 'required'
        ]);

        $data = [
            'userId' => $request->user_id,
            'roleId' => $request->role_id,
            'facilityId' => $request->facility_id,
            'section' => $request->section,
            'subSection' => $request->sub_section
        ];

        $userManagement = new UserManagement();
        $result = $userManagement->getAtomicPrivilege($data);
    
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getLoginDataByUserId(Request $request, $userId) {

        $userManagement = new UserManagement();
        $result = $userManagement->getLoginDataByUserId($userId);
    
        return $this->successResponse(['result' => $result], 'success');
    }
    
    public function getSystemActivityByUserId(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->getSystemActivityByUserId($userId);
    
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getProviderPortalEditByUserId(Request $request, $userId) {

        $userManagement = new UserManagement();
        $result = $userManagement->getProviderPortalEditByUserId($userId);

        return $this->successResponse(['result' => $result], 'success');
    }

    public function getDirectoryAdminByUserId(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->getDirectoryAdminByUserId($userId);
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getDirectoryAdminDeniedByUserId(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->getDirectoryAdminDeniedByUserId($userId);
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getDirectoryAccessByUserId(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->getDirectoryAccessByUserId($userId);
    
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getDirectoryAccessDeniedByUserId(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->getDirectoryAccessDeniedByUserId($userId);
    
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getProfileViewByUserId(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->getProfileViewByUserId($userId);
    
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getProfileViewDeniedByUserId(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->getProfileViewDeniedByUserId($userId);
        
        return $this->successResponse(['result' => $result], 'success');
    }

    public function resetUserGenericPrivileges(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $result = $userManagement->resetUserGenericPrivileges($userId);
    
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getUserNavigation(Request $request, $userId) {
        
        $userManagement = new UserManagement();
        $mainNavigation = $userManagement->getAllMainNavigations();
        $userNavigation = $userManagement->getNavigationByUserId($userId);
    
        return $this->successResponse(['main_navigation' => $mainNavigation, 'user_navigation' => $userNavigation], 'success');
    }

    public function updateUserNavigation(Request $request) {  
        
        $request->validate([
            'user_id' => 'required',
            'data' => 'required'
        ]);
        
        $userId = $request->user_id;
        $data = $request->data;
        $data = json_decode($data, true);
        
        $userManagement = new UserManagement();
        $result = $userManagement->updateUserNavigation($userId, $data);
    
        return $this->successResponse(['result' => $result], 'success');
    }

    public function getRoleWiseUsers(Request $request, $roleId) {

        $userManagement = new UserManagement();
        $result = $userManagement->getRoleWiseUsers($roleId);

        return $this->successResponse(['result' => $result], 'success');
    }

}

    