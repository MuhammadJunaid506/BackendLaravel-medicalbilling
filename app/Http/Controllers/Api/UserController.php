<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\UserAccountActivityLog;
use Illuminate\Support\Facades\Hash;
use App\Models\Provider;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\SessionLog;
use Mail;
use App\Models\UserRoleMap;
use App\Mail\OTPCode;
Use App\Mail\InvalidAttempts;
use App\Models\DemoUser;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public $sysUserRoles = [1, 5, 6, 7, 8, 11, 12, 13, 14, 15];
    use ApiResponseHandler, Utility, UserAccountActivityLog;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // $users = UserRoleMap::select("users.*")

        //     ->join('users', 'users.id', '=', 'user_role_map.user_id')

        //     ->join('roles', 'roles.id', '=', 'user_role_map.role_id')

        //     ->whereIn('user_role_map.role_id',[1,5,6,7,8,11])

        //     ->orderBy("user_role_map.role_id","DESC")

        //     ->paginate($this->cmperPage);

        // $roles = $this->fetchData('roles',"");
        // $companies = $this->fetchData('companies',"");
        // return $this->successResponse(['users' => $users,'roles' => $roles,'companies' => $companies], "Success");
        $roles = [];
        $companies = [];
        try {

            $searching = $request->get('search');

            if ($request->has('search')) {

                $users = UserRoleMap::select("users.*")

                    ->join('users', 'users.id', '=', 'user_role_map.user_id')

                    ->join('roles', 'roles.id', '=', 'user_role_map.role_id')

                    ->whereIn('user_role_map.role_id', $this->sysUserRoles)

                    ->Where('users.first_name', 'LIKE', '%' . $searching . '%')

                    ->orWhere('users.last_name', 'LIKE', '%' . $searching . '%')

                    ->orWhere('users.email', 'LIKE', '%' . $searching . '%')

                    ->orderBy("user_role_map.role_id", "DESC")

                    ->paginate($this->cmperPage);

                // $roles = $this->fetchData('roles',"");
                // $companies = $this->fetchData('companies',"");

            } else {

                $users = UserRoleMap::select("users.*")

                    ->join('users', 'users.id', '=', 'user_role_map.user_id')

                    ->join('roles', 'roles.id', '=', 'user_role_map.role_id')

                    ->whereIn('user_role_map.role_id', $this->sysUserRoles)

                    ->orderBy("user_role_map.role_id", "DESC")

                    ->paginate($this->cmperPage);

                $roles = $this->fetchData('roles', "");
                $companies = $this->fetchData('companies', "");
            }

            return $this->successResponse(['users' => $users, 'roles' => $roles, 'companies' => $companies], "Success");
        } catch (\Throwable $exception) {

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
            "first_name"                => "required",
            "last_name"                 => "required",
            "email"                     => "required|email|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix|unique:users,email",
            "password"                  => "required|min:6",
            "zip_code"                         => "required",
            "phone"                      => "required",
            "emp_number"                => "required",
            "dob"                       => "required",
            "address_line_one"             => "required",
            "address_line_two"           => "required",
            "city"                            => "required",
            "state"                 => "required",
            "gender"               => "required",
            'company_id' => "required",
            'role_id' => "required",
        ]);


        try {



            $firstName = $request->first_name;

            $addUser = [
                "first_name"                    => $request->first_name,
                "last_name"                      => $request->last_name,
                "email"                          =>  $request->email,
                "password"                       =>  Hash::make($request->password),
                "zip_code"                         =>  $request->zip_code,
                "ssn"                         =>  $request->ssn,
                "phone"                      =>  $request->phone,
                "emp_number"                =>  $request->emp_number,
                "dob"                       =>  $request->dob,
                "address_line_one"             =>  $request->address_line_one,
                "address_line_two"           =>  $request->address_line_two,
                "city"                            =>  $request->city,
                "state"                 =>  $request->state,
                "gender"               =>  $request->gender
            ];

            $id = User::insertGetId($addUser);
            //map user with company
            $compMap = [
                'user_id' => $id,
                'company_id' => $request->company_id
            ];
            $this->addData("user_company_map", $compMap);
            //map user with role
            $roleMap = [
                'user_id' => $id,
                'role_id' => $request->role_id
            ];
            $this->addData("user_role_map", $roleMap);

            if ($request->file("image")) {
                $file = $request->file("image");
                //$nameMe = uniqid();
                $fileName = trim($file->getClientOriginalName());
                // $file = $request->file("file");
                $ext = explode(".", $fileName)[1];
                $fileName = $id . "_" . "profile_image." . $ext;


                $uploadRes = $this->uploadMyFile($fileName, $file, "images/profile");
                //$this->printR($uploadRes,true);
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
                            "field_key" => $firstName . " Profile Image",
                            "field_value" => $fileName,
                            "updated_at" => $this->timeStamp()
                        ];
                        $this->updateData("attachments", $whereProfile, $addProfileImage);
                    } else {
                        $addProfileImage = [
                            "entities" => "user_id",
                            "entity_id" => $id,
                            "field_key" => $firstName . " Profile Image",
                            "field_value" => $fileName,
                            "created_at" => $this->timeStamp()
                        ];
                        $this->addData("attachments", $addProfileImage);
                    }
                }
            }
            return $this->successResponse(["id" => $id], "User added successfully.");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * Display the searched specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchUsers(Request $request)
    {


        try {

            $keyWord = $request->keyword;

            $result = UserProfile::where('first_name', 'LIKE', '%' . $keyWord . '%')

                ->orWhere('last_name', 'LIKE', '%' . $keyWord . '%')

                ->get();

            if (count($result)) {
                return $this->successResponse($result, "Success");
            } else {
                return $this->warningResponse($result, 'No data found', 404);
            }
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if (!$request->has('keyword')) {
            try {

                $userProfile = UserProfile::find($id);

                return $this->successResponse($userProfile, "success");
            } catch (\Throwable $exception) {

                return $this->errorResponse([], $exception->getMessage(), 500);
            }
        } else {
            return $this->searchUsers($request);
        }
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
        // echo $id;
        // exit;
        try {

            $updateData = $request->all();

            $firstName = isset($updateData['first_name']) ? $updateData['first_name'] : "User";
            if ($request->file("image")) {
                $file = $request->file("image");
                //$nameMe = uniqid();
                $fileName = trim($file->getClientOriginalName());
                // $file = $request->file("file");
                $ext = explode(".", $fileName)[1];
                $fileName = $id . "_" . "profile_image." . $ext;


                $uploadRes = $this->uploadMyFile($fileName, $file, "images/profile");
                //$this->printR($uploadRes,true);
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
                            "field_key" => $firstName . " Profile Image",
                            "field_value" => $fileName,
                            "updated_at" => $this->timeStamp()
                        ];
                        $this->updateData("attachments", $whereProfile, $addProfileImage);
                    } else {
                        $addProfileImage = [
                            "entities" => "user_id",
                            "entity_id" => $id,
                            "field_key" => $firstName . " Profile Image",
                            "field_value" => $fileName,
                            "created_at" => $this->timeStamp()
                        ];
                        $this->addData("attachments", $addProfileImage);
                    }
                }
            }
            unset($updateData['image']);
            // $this->printR($updateData,true);
            $isUpdate  = User::where("id", $id)->update($updateData);
            return $this->successResponse(["is_update" => $isUpdate], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }

        // // $this->printR($request->all(),true);
        // $inputData = $request->all();
        // $isAll = true;
        // if (!isset($inputData["is_all"])) {
        //     $isAll = false;
        //     $request->validate([
        //         "col" => "required",
        //         "val" => "required"
        //     ]);
        // }

        // $col = $request->col;
        // $val = $request->val;


        // try {
        //     if ($isAll == false) {
        //         if ($col == "picture") {

        //             $oldImageName = $request->old_picture;

        //             $path = public_path('storage/user/images/') . $oldImageName;

        //             unlink($path); //delete previous uploaded image

        //             $file = $request->file('val');

        //             $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

        //             if (!file_exists($path)) {
        //                 mkdir($path, 0777, true);
        //             }

        //             $file->move($path, $fileName);

        //             $isUpdate = UserProfile::where(['user_id' => $id])->update([$col => $fileName, "updated_at" => date("Y-m-d H:i:s")]);

        //             return $this->successResponse(['id' => $id, 'is_update' => $isUpdate], "User updated successfully.");
        //         } else {

        //             $isUpdate = UserProfile::where(['user_id' => $id])->update([$col => $val, "updated_at" => date("Y-m-d H:i:s")]);

        //             return $this->successResponse(['id' => $id, 'is_update' => $isUpdate], "User update successfully.");
        //         }
        //     } else {

        //         $updateData = [
        //             "first_name"    => $inputData["first_name"],
        //             "last_name"     => $inputData["last_name"],
        //             "gender"        => $inputData["gender"]
        //         ];
        //         if($request->file("image")) {
        //             $file = $request->file("image");
        //             //$nameMe = uniqid();
        //             $fileName = trim($file->getClientOriginalName());
        //             // $file = $request->file("file");
        //             $fileName = $id."_".$fileName;
        //             $whereProfile = [
        //                 ["entities","=","user_id"],
        //                 ["entity_id","=",$id]
        //             ];
        //             //$this->fetchData("attachments",$whereProfile,1,[]);
        //             $this->uploadMyFile($fileName,$file,"images/profile");
        //         }
        //         if (isset($inputData["password"]) && strlen($inputData["password"]) > 0 ) {
        //             //$updatePassword = ["password" => Hash::make($inputData["password"])];
        //             $updateData["password"] = Hash::make($inputData["password"]);
        //         }
        //         if (isset($inputData["contact_number"]) && strlen($inputData["contact_number"]) > 0 ) {
        //             $updateData["phone"] = $inputData["contact_number"];
        //         }
        //         // $this->printR($updateData,true);
        //         $isUpdate = user::where("id", $id)->update($updateData);

        //         return $this->successResponse(['id' => $id, 'is_update' => $isUpdate], "User update successfully.");
        //     }
        // } catch (\Throwable $exception) {
        //     return $this->errorResponse([], $exception->getMessage(), 500);
        // }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {


            $whereProfile = [
                ["entities", "=", "user_id"],
                ["entity_id", "=", $id]
            ];


            $profileExist = $this->fetchData("attachments", $whereProfile, 1, []);
            if (is_object($profileExist)) {


                $fileName = $profileExist->field_value;
                $this->deleteFile("images/profile/" . $fileName); //delete the file from storage
                $isDelete  = User::where("id", $id)->delete();
                $this->deleteData("attachments", $whereProfile); //delete the image attachement table
                return $this->successResponse(["is_delete" => $isDelete], "success", 200);
            } else {
                $isDelete  = User::where("id", $id)->delete();
                return $this->successResponse(["is_delete" => $isDelete], "success", 200);
            }



            // // $isDel = User::where(['id' => $id])->delete();

            // $isDel = User::find($id)->delete();

            // $user = $request->user(); //or Auth::user()

            // $user->tokens()->where('id', $id)->delete(); //delete the user token

            // return $this->successResponse(['id' => $id, 'is_del' => $isDel], "User deleted successfully.");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }


    public function createProviderCredentials(Request $request)
    {

        $request->validate([
            "provider_id" => "required",
            "company_id" => "required"
        ]);

        try {
            $providerId = $request->provider_id;

            $provider = Provider::find($providerId);
            $email = $provider->contact_person_email;
            $fullName = $provider->contact_person_name;

            $providerType = $provider->provider_type;
            $legalBName = $provider->legal_business_name;

            $password = Str::random(6);

            $userExist = User::where("email", "=", $email)

                ->count();

            if ($userExist == 0) {

                $addUser = [
                    "name" => $fullName,
                    "email" => $email,
                    "password" => Hash::make($password)
                ];

                $user = User::create($addUser);

                $user->createToken($fullName . " Token")->plainTextToken;

                $role = Role::where("role_name", "=", "provider")->first(["id"]);

                //$this->printR($role,true);

                $userId = $user->id;

                $adminId = 0;

                $companyId = $request->company_id;

                $roleId = $role->id;

                $firstName  = $fullName;

                $lastName   = "";

                $gender   = "";

                $contactNumber = $provider->contact_person_phone;

                $employeeNumber = "";

                $cnic = "";

                $addUserProfile = [
                    "admin_id"          => $adminId,
                    'user_id'           => $userId,
                    "company_id"        => $companyId,
                    "role_id"           => $roleId,
                    "first_name"        => $firstName,
                    "last_name"         => $lastName,
                    "gender"            => $gender,
                    "contact_number"    => $contactNumber,
                    "employee_number"   => $employeeNumber,
                    "cnic"              => $cnic,
                    "picture"           => "",
                    "created_at"        => date("Y-m-d H:i:s")
                ];

                UserProfile::create($addUserProfile);

                try {
                    Provider::where("id", "=", $providerId)->update(["user_id" => $userId]); //update the user Id
                } catch (\Throwable $exception) {
                }

                $emailData = ["login_email" => $email, "password" => $password, "name" => $fullName, "provider_type" => $providerType, "legal_business_name" => $legalBName];
                $isSentEmail = 1;
                $msg = "";
                try {

                    // Mail::to($email)

                    // ->send(new ProviderCredentials($emailData));

                    // $CredentialingController = new CredentialingController();

                    // $CredentialingController->store($request); //create the credentailing task for this provider.

                } catch (\Throwable $exception) {

                    $isSentEmail = 0;

                    $msg = $exception->getMessage();
                }
                return $this->successResponse(["login_email" => $email, "password" => $password, "email_Sent" => $isSentEmail, "msg" => $msg], "success");
            }
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * create the users for system
     *
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createUsers(Request $request)
    {

        $request->validate([
            "first_name" => "required",
            "last_name" => "required",
            "email"     => "required|email|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix",
            "role_id"   => "required",
            "password"  => "required",
            "component" => "required"
        ]);
        $isNewUser = false;
        if ($request->has("component") && $request->component == "dd") {
            $user = User::where("email", "=", $request->email)->first(["id"]);
            if (is_object($user)) {
                $userId = $user->id;
                $isNewUser = false;
            } else {
                $userId = $this->addUser($request);
                $isNewUser = true;
            }
        } else {
            $request->validate([
                "email" => "unique:users,email"
            ]);
            $isNewUser = true;
            $userId = $this->addUser($request);
        }
        return $this->successResponse(['id' => $userId, 'is_new' => $isNewUser], "User created successfully.");
    }


    public function createUsersForOnboard(Request $request)
    {


        $isNewUser = false;
        $pattern = "/AES_ENCRYPT\(''\)/";
        if ($request->has("component") && $request->component == "dd") {
            $user = User::where("email", "=", $request->email)->first(["id"]);
            if (is_object($user)) {
                $userId = $user->id;
                $isNewUser = false;
            }
            if(preg_match($pattern, $request->email))
            {
                $userId = $this->addUser($request);
                $isNewUser = true;
            }

            else {
                $userId = $this->addUser($request);
                $isNewUser = true;
            }
        } else {
            $request->validate([
                "email" => "unique:users,email"
            ]);
            $isNewUser = true;
            $userId = $this->addUser($request);
        }
        return $this->successResponse(['id' => $userId, 'is_new' => $isNewUser], "User created successfully.");
    }
    /**
     * private function for create the user into database
     *
     * @param $requestParams
     * @return $userId
     */
    private function addUser($requestParams)
    {

        $parentId = $requestParams->has("parent_id") ? $requestParams->parent_id : 0;
        $password = $requestParams->password; //Str::random(6);

        $addUser = [
            "first_name" => $requestParams->first_name,
            "last_name" => $requestParams->last_name,
            "email" =>     $requestParams->email,
            "password" => Hash::make($password),
            "created_at" => $this->timeStamp()
        ];

        $user = User::create($addUser);

        $user->createToken($requestParams->first_name . " Token")->plainTextToken;

        $userId = $user->id;

        $roleId = $requestParams->role_id;
        $roleData = [
            "user_id" => $userId,
            "role_id" => $roleId
        ];

        $comapny_map = [
            "user_id" => $userId,
            "company_id" => 1,
        ];



        $this->addData("user_role_map", $roleData, 0);

        $this->addData("user_company_map", $comapny_map, 0);

        return $userId;
    }
    /**
     * logout the user from the system
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $request->validate([
            "user_id" => "required"
        ]);
        $userId = $request->user_id;
        $isUser = User::find($userId);
        if (is_object($isUser)) {
            $isLogout = $this->deleteData("personal_access_tokens", ["tokenable_id" => $userId]);
            SessionLog::where('user_id', '=', $userId)
                ->whereNull('session_expired_at')
                ->update(['session_expired_at' => $this->timeStamp()]);
            return $this->successResponse(['logout' => $isLogout], "User logout successfully.");
        } else {
            return $this->successResponse(['logout' => false], "User could not be logout successfully.", 400);
        }
    }
    /**
     * login the user into system
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request) {

        $tbl ="users";

        $key = env("AES_KEY");

        $request->validate([
            "email"     => "required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix",
            "password"  => "required|min:6"
        ]);

        $email = $request->email;

        $password = $request->password;
        
        $token = $request->captchaToken;

        $ingnoreCaptcha = false;
        
        $referrerUrl = $request->headers->get('referer');

        $haystack = $referrerUrl;
        
        $needle = "staging";

        $position = strpos($haystack, $needle);
        //bellow code for ignoring captcha when any request comming from the staging server
        if ($position !== false) {
            $ingnoreCaptcha = true;
        } else {
            $ingnoreCaptcha = false;
        }

        if (env("APP_ENV") == "production" && $ingnoreCaptcha === false) {
            $response = Http::retry(3, 100)
            ->post("https://www.google.com/recaptcha/api/siteverify?secret=" . env("CAPTHA_KEY") . "&response={$token}");

            if (!$response->json()['success']) {
                // inValid token
                return $this->successResponse([], "Please retry captcha", 400);
            }
        }
        
            // echo $key;
        //$user = User::where([$tbl.".email" => $email])->first();
        $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")

        ->select(DB::raw("AES_DECRYPT(email, '$key') AS email"),"password","id","deleted")

        ->first();
        
        // $this->printR($user,true);
        if ((!is_object($user))) {
            return $this->warningResponse([],"Given credentials are invalid.",401);
        }
        elseif(is_object($user) && ! Hash::check($password, $user->password) ) {
            $attempts = $user->password_attempts + 1;
            if ($attempts <= 3) {
                User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")->update(["password_attempts" => $attempts]);
                return $this->warningResponse([], "The provided password incorrect.", 401);
            }
            else {
                    $adminEmail = "audit@claimsmedinc.com";
                if (env('APP_ENV') === 'production')
                    $adminEmail = "testdev@yopmail.com";

                User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")->update(["deleted" => 2]);
                $msg = "";
                try {
                    $userName = $user->first_name . " " . $user->last_name;
                    Mail::to($adminEmail)
                        ->send(new InvalidAttempts(['name' => "Admin", 'locked_user' => $userName]));
                } catch (\Throwable $exception) {
                    $msg = $exception->getMessage();
                }
                return $this->warningResponse(["msg" => $msg], "Your account has been suspended due to incorrect password attempts; please contact the administrator for assistance", 401);

            }
        }
        elseif(is_object($user) && $user->deleted == 1) {
            return $this->warningResponse([],"You are trying to login with suspended account",401);
        }
        elseif(is_object($user) && $user->deleted == 2) {
            return $this->warningResponse([],"You are trying to login with in-active account",401);
        }
        
        try {
            $this->deleteData("personal_access_tokens", ["tokenable_id" => $user->id]);
        } catch (\Exception $e) {
        }
        $name = explode("@", $email)[0];
        $userId = $user->id;
        if($user->password_attempts)//reset the attempt if incase user tried with wrong password
            User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")->update(["password_attempts" => 0]);

        $passwordShouldChange = false;

        if (isset($user->last_password_changed)) {
            $lastPasswordChanged = $user->last_password_changed;
            // Assuming $timestamp is the timestamp you want to check
            $timestamp = Carbon::parse($lastPasswordChanged);

            // Get the current time
            $currentDateTime = Carbon::now();

            // Calculate the difference in days
            $daysDifference = $currentDateTime->diffInMonths($timestamp);
            if ($daysDifference >= 1)
                $passwordShouldChange = true;
        }
        // return $this->successResponse($name,"success");
        $user->createToken($name)->plainTextToken;
        $user = User::select($tbl.".id", $tbl.".first_name", $tbl.".last_name",
        "roles.role_name as role","roles.role_short_name as role_name",
        "roles.id as role_id",
        "attachments.field_value as profile_image"
        )

        ->leftJoin("user_company_map", "user_company_map.user_id", "=", $tbl.".id")

        ->leftJoin("user_role_map", function($join) use($tbl) {
            $join->on("user_role_map.user_id", "=", $tbl.".id")
           ->where("user_role_map.role_preference","=",1);
        })

        ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

        //->leftJoin("personal_access_tokens","personal_access_tokens.tokenable_id","users.id")

            //->leftJoin("user_baf_practiseinfo","user_baf_practiseinfo.user_id","users.id")

        ->leftJoin("attachments",function($join) use($tbl){
            $join->on("attachments.entity_id",$tbl.".id")
            ->where("entities","=","user_id");
        })

        ->where($tbl.".id","=",$userId)

            ->first();

        $userName = $user->first_name . " " . $user->last_name;

        $sessionExist = SessionLog::where("user_id", $userId)

            ->whereNull('session_expired_at')

            ->count();
        // $sessionExist = 0;
        $tokenData = $this->fetchData("personal_access_tokens", ['tokenable_id' => $user->id], 1, ['token']);

        $token = $tokenData->token;

        $facilitiesPrivileges = [];
        if($userId) {
            $facilitiesPrivileges = DB::table('user_facility_privileges')
                                    ->select('*')
                                    ->where('user_id', $user->id)
                                    ->get();
        }

        $rolePrivileges = [];
        if($userId) {
            $rolePrivileges = DB::table('role_privileges')
                                    ->select('role_privileges.*')
                                    ->leftJoin('user_role_map', 'user_role_map.role_id', '=', 'role_privileges.role_id')
                                    ->where('user_role_map.user_id', $userId)
                                    ->get();
        
        }

        // if(count($facilitiesPrivileges) == 0 || count($rolePrivileges) == 0) {
        //     $this->printR($user,true);
        // }

        
        $isSentEmail = 0;
        $msg = "";
        // if ($isAutoTestingMode == 0) 
        {
            // $this->printR($user,true);
            if ($sessionExist == 0) { //add the new session log
                $sessionTime = $this->timeStamp();
                // SessionLog::insertGetId(['user_id' => $userId,'session_buid_at' => $sessionTime]);
                /**
                 * add the login activity log
                 */
                $this->addLoginActivityLog($userId, $request, $this->timeStamp(), $this->timeStamp());
                $appData = $this->prepareAppData($user);
                return $this->successResponse(['user' => $appData, 'token' => $token, 'user_id' => $userId, 'has_session' => $sessionExist, 'is_sent_otp' => 0, 'signin_time' => $sessionTime, "should_change_password" => $passwordShouldChange, 'facilitiesPrivileges' => $facilitiesPrivileges, 'rolePrivileges' => $rolePrivileges], "success");
            }
            if ($sessionExist > 0) {
                $code = Str::random(4);
                User::where("id", "=", $userId)
                    ->update(['otp_code' => $code]);
                if ($request->has('app_mode') && $request->get('app_mode') == '1') //if develoer testing account then send email to the developer
                    $email = "cmdev@yopmail.com";

                try {
                    Mail::to($email)
                        ->send(new OTPCode(['otp_code' => $code, 'name' => $userName]));
                    $isSentEmail = 1;
                } catch (\Throwable $exception) {
                    $isSentEmail = 0;
                    $msg = $exception->getMessage();
                }
                $userIdEnc = base64_encode($userId);
                return $this->successResponse(['user' => [], 'token' => $token, 'has_session' => $sessionExist, 'is_sent_otp' => $isSentEmail, 'user_id' => $userIdEnc, "should_change_password" => $passwordShouldChange], "success");
            }
        } 
        // else 
        // {
        //     $sessionTime = $this->timeStamp();
        //     $appData = $this->prepareAppData($user);
        //     return $this->successResponse(['user' => $appData, 'token' => $token, 'user_id' => $userId, 'has_session' => 0, 'is_sent_otp' => 0, 'signin_time' => $sessionTime, "should_change_password" => $passwordShouldChange, 'facilitiesPrivileges' => $facilitiesPrivileges, 'rolePrivileges' => $rolePrivileges], "success");
        // }
    }

    /**
     * prepare app data
     *
     * @param $user
     */
    private function prepareAppData($user)
    {

        $roleId = $user->role_id;
        $userId = $user->id;

        $roles = $this->fetchUserRoles($userId);

        $rolesArr = $this->stdToArray($roles);

        $roleIds = array_column($rolesArr, "role_id");

        $roleIdsStr = implode(",", $roleIds);

        // $navigation = $this->fetchNavigation($roleIdsStr);
        $navigation = $this->fetchUserNavigation($userId);
        // $this->printR($userId,true);
        // echo $userId;
        // echo $roleId;
        // $this->printR($navigation,true);

        $user->navigation = $navigation;
 
        $innerNavigation = [];
        // if (count($navigation)) {
        //     foreach ($navigation as $nav) {
        //         $innerNavigation[$nav->id] = $this->fetchInnerNavigation($roleIdsStr, $nav->id);
        //     }
        // }
        if (count($navigation)) {
            foreach ($navigation as $nav) {
                $innerNavigation[$nav->id] = $this->fetchUserInnerNavigation($userId, $nav->id);
            }
        }
        // $this->printR($innerNavigation,true);
        $user->inner_navigation = $innerNavigation;

        // $this->printR($innerNavigation,true);

        // $routes = $this->fetchUserRoutes($roleIds);
        $routes = $this->fetchUserRoutes($roleIds);

        // $this->printR($routes,true);
        // $this->printR($routes,true);
        $user->routes = $routes;
        // if (count($routes)) {
        //     $parentRoute = $routes[0];
        //     $parentId = $parentRoute->parent_route_id;
        //     if (isset($parentRoute->parent_route_id) && $parentId > 0) {
        //         $routes_ = $this->fetchParentRoute($parentId);
        //         $user->routes = $routes_;
        //         foreach ($routes as $route) {
        //             $innerRoute[$parentId] = $this->fetchSpecificRoutes($route->id, 0, $roleIds);
        //         }
        //         $user->inner_routes = $innerRoute;
        //     } else {
        //         $innerRoute = [];
        //         if (count($routes)) {
        //             foreach ($routes as $route) {
        //                 $innerRoute[$route->id] = $this->fetchInnerRoutes($route->id, $roleIds);
        //             }
        //         }
        //         $user->inner_routes = $innerRoute;
        //     }
        // }

        $innerRoutes = $this->fetchUserInnerRoutes($routes, $navigation);
        // $this->printR($user->inner_routes,true);
        $user->inner_routes = $innerRoutes;
        return $user;
    }
    /**
     * Resend the otp code to the user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function resendOtp(Request $request) {
        $tbl ="users";

        $key = env("AES_KEY");

        $userId =  base64_decode($request->user_id);

        $userData = User::where("id", "=", $userId)->first([DB::raw("AES_DECRYPT(email,'$key') as email"),'first_name','last_name']);
        $userName = $userData->first_name.' '.$userData->first_name;
        $code = Str::random(4);
        $isSentEmail = 0;
        $msg = "";
        try {
            Mail::to($userData->email)
                ->send(new OTPCode(['otp_code' => $code, 'name' => $userName]));
            User::where("id", "=", $userId)
            ->update(['otp_code' => $code]);
            $isSentEmail = 1;
            $msg = "An OTP code has been sent successfully. Please check your email address.";
        } catch (\Throwable $exception) {
            $isSentEmail = 0;
            $msg = $exception->getMessage();
        }
        return $this->successResponse(['is_sent' => $isSentEmail,'message' => $msg], "success");
    }
    /**
     * validate the otp token of the user
     *
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    function verifyOtp(Request $request) {

        $tbl = "users";

        $userId =  base64_decode($request->user_id);

        $otpCode = $request->otp_code;

        $hasValidCode = User::where([
            ["id", "=", $userId],
            ["otp_code", "=", $otpCode]
        ])
        ->count();
        
        $facilitiesPrivileges = [];
        if($userId) {
            $facilitiesPrivileges = DB::table('user_facility_privileges')
                                    ->select('*')
                                    ->where('user_id', $userId)
                                    ->get();
        }

        if($hasValidCode) {
            $user = User::select($tbl.".id", $tbl.".first_name", $tbl.".last_name",
            "roles.role_name as role","roles.role_short_name as role_name",
            "roles.id as role_id",
            "attachments.field_value as profile_image"
            )

            ->leftJoin("user_company_map", "user_company_map.user_id", "=", $tbl.".id")

            ->leftJoin("user_role_map", "user_role_map.user_id", "=", $tbl.".id")

            ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

            //->leftJoin("personal_access_tokens","personal_access_tokens.tokenable_id","users.id")

                //->leftJoin("user_baf_practiseinfo","user_baf_practiseinfo.user_id","users.id")

            ->leftJoin("attachments",function($join) use($tbl) {
                $join->on("attachments.entity_id",$tbl.".id")
                ->where("entities","=","user_id");
            })

            ->where($tbl.".id", $userId)

                ->first();

            SessionLog::where('user_id', '=', $userId)
                ->whereNull('session_expired_at')
                ->update(['session_expired_at' => $this->timeStamp()]); //update the first session of the user first

            $sessionTime = $this->timeStamp();

            //SessionLog::insertGetId(['user_id' => $userId,'session_buid_at' => $sessionTime]);
            $this->addLoginActivityLog($userId, $request, $this->timeStamp(), $this->timeStamp());
            $appData = $this->prepareAppData($user);

            $rolePrivileges = [];
            if($user->role_id) {
                $rolePrivileges = DB::table('role_privileges')
                                        ->select('*')
                                        ->where('role_id', $user->role_id)
                                        ->get();
            
            }

            return $this->successResponse(['app_data' => $appData, 'otp_verified' => $hasValidCode, 'signin_time' => $sessionTime, 'facilitiesPrivileges' => $facilitiesPrivileges, 'rolePrivileges' => $rolePrivileges], "success");
        } else
            return $this->successResponse(['otp_verified' => $hasValidCode], "success");
    }
    /**
     * get the user profile against the session token
     *
     *
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function fetchPrfileAginstToken(Request $request, $token)
    {

        $user = $this->fetchUserAgainstToken($token);

       // $this->printR($user,true);


        if($user->profile_image !=null) {
            $url = env("STORAGE_PATH")."images/profile/".$user->profile_image;
            $user->profile_image_url = $url;
        }
        $roles       = $this->fetchRoles();
        $companies   = $this->fetchCompanies();
        $profileData = [
            "user"      => $user,
            "roles"     => $roles,
            "companies" => $companies
        ];
        return $this->successResponse($profileData, "success");
    }
    /**
     * update the profile of  user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request,$id) {
        $key = env("AES_KEY");
        $inputData = $request->all();
        $firstName = $inputData["first_name"];
        $updateData = [
            "first_name"    => $inputData["first_name"],
            "last_name"     => $inputData["last_name"],
            "gender"        => $inputData["gender"]
        ];
        if ($request->file("image")) {
            $file = $request->file("image");
            //$nameMe = uniqid();
            $fileName = trim($file->getClientOriginalName());
            // $file = $request->file("file");
            $ext = explode(".", $fileName)[1];
            $fileName = $id . "_" . "profile_image." . $ext;


            $uploadRes = $this->uploadMyFile($fileName, $file, "images/profile");
            //$this->printR($uploadRes,true);
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
                        "field_key" => $firstName . " Profile Image",
                        "field_value" => $fileName,
                        "updated_at" => $this->timeStamp()
                    ];
                    $this->updateData("attachments", $whereProfile, $addProfileImage);
                } else {
                    $addProfileImage = [
                        "entities" => "user_id",
                        "entity_id" => $id,
                        "field_key" => $firstName . " Profile Image",
                        "field_value" => $fileName,
                        "created_at" => $this->timeStamp()
                    ];
                    $this->addData("attachments", $addProfileImage);
                }
            }
        }
        if (isset($inputData["password"]) && strlen($inputData["password"]) > 0) {
            //$updatePassword = ["password" => Hash::make($inputData["password"])];
            $updateData["password"] = Hash::make($inputData["password"]);
        }
        if (isset($inputData["contact_number"]) && strlen($inputData["contact_number"]) > 0 ) {
            $phone = $inputData["contact_number"];
            $updateData["phone"] = DB::raw("AES_ENCRYPT('$phone', '$key')");//$inputData["contact_number"];
        }
        // $this->printR($updateData,true);
        $isUpdate = user::where("id", $id)->update($updateData);

        return $this->successResponse(['id' => $id, 'is_update' => $isUpdate], "User update successfully.");
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function fatchUserRolMap(Request $request)
    {
        try {

            $searching = $request->get('search');

            if ($request->has('search')) {

                $usersrolmap = UserRoleMap::select(
                    "user_role_map.*",
                    "user_id",
                    "role_id",
                    DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"),
                    "roles.role_name",
                    'users.deleted'
                )

                    ->join('users', 'users.id', '=', 'user_role_map.user_id')

                    ->join('roles', 'roles.id', '=', 'user_role_map.role_id')
                    
                    ->where("users.deleted","=",0)
                    
                    ->whereIn('user_role_map.role_id', [1, 5, 6, 7, 8, 11])

                    ->whereRaw(DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) LIKE '%$searching%'"))

                    ->orWhere('roles.role_name', 'LIKE', '%' . $searching . '%')

                    ->orderBy("user_role_map.role_id", "DESC")

                    ->paginate($this->cmperPage);
            } else {
                $usersrolmap = UserRoleMap::select(
                    "user_role_map.*",
                    "user_id",
                    "role_id",
                    DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"),
                    "roles.role_name",
                    'users.deleted'
                )

                    ->join('users', 'users.id', '=', 'user_role_map.user_id')

                    ->join('roles', 'roles.id', '=', 'user_role_map.role_id')
                    
                    ->where("users.deleted","=",0)

                    ->whereIn('user_role_map.role_id', [1, 5, 6, 7, 8, 11])

                    ->orderBy("user_role_map.role_id", "DESC")

                    ->paginate($this->cmperPage);
            }

            return $this->successResponse(["usersrolmap" => $usersrolmap], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch users and roles
     *
     *
     * @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function usersWithRoles(Request $request)
    {

        $roles =  DB::table('roles')->whereIn('id', [1, 5, 6, 7, 8, 11])
            ->get();
        $users = DB::table('user_role_map')
            ->select('users.id AS user_id', DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"))
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'user_role_map.user_id')
                    ->whereIn('user_role_map.role_id', [1, 5, 6, 7, 8, 11]);
            })
            ->get();

        return $this->successResponse(["roles" => $roles, "users" => $users], "success", 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addUserRolMap(Request $request)
    {

        $request->validate([
            'assign_role_user' => 'required',

        ]);

        try {

            //     $newUserRolMap = [

            //     "user_id"             => $request->user_id,
            //     "role_id"             => $request->role_id,

            // ];
            $allData = json_decode($request->assign_role_user, true);
            $id = 0;
            if (count($allData) > 0) {
                foreach ($allData as $eachData) {
                    $hasData = UserRoleMap::where('user_id', $eachData['user_id'])
                        ->count();
                    if (!$hasData) {
                        $id = UserRoleMap::insertGetId($eachData);
                    }
                }
            }
            return $this->successResponse(["id" => $id], "success");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateUserRolMap(Request $request, $id)
    {
        $updateData = $request->all();

        $isUpdate = UserRoleMap::where('user_id', $id)->update($updateData);

        return $this->successResponse(["is_update" => $isUpdate], "success");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deActiveUserRolMap(Request $request, $id)
    {

        try {

            $isDelete  = User::where("id", $id)->update(['deleted' => $request->delete]);

            return $this->successResponse(["is_delete" => $isDelete], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * login the guest user into system
     *
     * @return \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function loginDemo(Request $request) {

        $key = env("AES_KEY");

        $tbl = "users";

        $email = "guest@yopmail.com";
        // $password = "Qwerty123#";
        
        //$user = User::where(["users.email" => $email])->first();
        $user = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")

        ->select(DB::raw("AES_DECRYPT(email, '$key') AS email"),"password","id")

        ->first();
        
        $name = explode("@",$email)[0];

        $userId = $user->id;

        $user->createToken($name)->plainTextToken;

        $user = User::select($tbl.".id", $tbl.".first_name", $tbl.".last_name",
        "roles.role_name as role","roles.role_short_name as role_name",
        "roles.id as role_id",
        "attachments.field_value as profile_image"

        )

        ->leftJoin("user_company_map", "user_company_map.user_id", "=", $tbl.".id")

        ->leftJoin("user_role_map", "user_role_map.user_id", "=", $tbl.".id")

        ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")

        ->leftJoin("attachments",function($join) use($tbl) {
            $join->on("attachments.entity_id",$tbl.".id")
            ->where("entities","=","user_id");
        })

        ->where($tbl.".id", $userId)

            ->first();

        $isAgreed = $request->is_agreed;

        $ip = $request->header('X-Forwarded-For') ?? null;
        $mail = $request->mail ?? null;
        //log the demo user activity
        DemoUser::insertGetId(
            ["ip" => $ip,"is_agreed" => $isAgreed,"email" => $mail,"created_at" => $this->timeStamp()]
        );

        $tokenData = $this->fetchData("personal_access_tokens", ['tokenable_id' => $user->id], 1, ['token']);

        $token = $tokenData->token;

        $sessionTime = $this->timeStamp();
        $facilitiesPrivileges = [];
        if($userId) {
            $facilitiesPrivileges = DB::table('user_facility_privileges')
                                    ->select('*')
                                    ->where('user_id', $user->id)
                                    ->get();
        }

        $rolePrivileges = [];
        if($userId) {
            $rolePrivileges = DB::table('role_privileges')
                                    ->select('role_privileges.*')
                                    ->leftJoin('user_role_map', 'user_role_map.role_id', '=', 'role_privileges.role_id')
                                    ->where('user_role_map.user_id', $userId)
                                    ->get();
        
        }
        $appData = $this->prepareAppData($user);

        return $this->successResponse([
            'user' => $appData, 'token' => $token, 'user_id' => $user->id,
            'has_session' => 0, 'is_sent_otp' => 0, 'signin_time' => $sessionTime,
            'facilitiesPrivileges' => $facilitiesPrivileges, 'rolePrivileges' => $rolePrivileges
        ], "success");
    }
    /**
     * update user password
     *
     * @return \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function updateUserPassword(Request $request)
    {

        $userId = $request->user_id;

        $newPassword = $request->new_password;

        $update = User::where("id", "=", $userId)->update(["password" => Hash::make($newPassword), "last_password_changed" => Carbon::now()]);

        return $this->successResponse(['is_update' => $update], 200);
    }
}
