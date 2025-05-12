<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Api\AuthController;

class AdminController extends Controller
{
    use ApiResponseHandler;
    /**
     * login admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
    */
    public function doAdminLogin(Request $request) {
        
        $request->validate([
            "email"     => "required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix",
            "password"  => "required|min:6"
        ]);
      
        try 
        {
            
            $email = $request->email;

            $password = $request->password;

            $admin = User::where(["users.email" => $email])
            
            ->select(["users.id","users.first_name","users.password","admins.type","personal_access_tokens.token"])
            
            ->leftJoin("admins","admins.user_id","=","users.id")
            
            ->leftJoin("personal_access_tokens","personal_access_tokens.tokenable_id","users.id")

            ->first();
           
           
            if(is_object($admin) && Hash::check($password,$admin->password) && isset($admin->type) && $admin->type !="null") {
               
                $admin->password = "";//hiding password for security reason
                
                // $admin->access_token = $admin->createToken("Admin TOKEN")->plainTextToken;
               
                $admin->is_admin = 1;
                return $this->successResponse($admin,"Success");
            }
            elseif(is_object($admin) && gettype($admin->type) == "NULL" && Hash::check($password,$admin->password)) {
                
                // $user = User::where(["users.email" => $email])
                
                // ->select([
                //     "users.id","users.*","users.password","companies.company_name",
                //     ,"personal_access_tokens.token",
                //     "roles.role_name","users_profile.company_id","users.email"
                    
                // ])
                
                // ->leftJoin("users_profile","users_profile.user_id","=","users.id")

                // ->leftJoin("personal_access_tokens","personal_access_tokens.tokenable_id","users.id")
                
                // ->leftJoin("companies","companies.id","=","users_profile.company_id")

                // ->leftJoin("roles","roles.id","=","users_profile.role_id")

                // ->first();

                $user = User::select("users.*", "roles.role_name","personal_access_tokens.token")

                    ->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")
                    
                    //->leftJoin("user_company_map", "user_company_map.user_id", "=", "users.id")

                    ->leftJoin("user_role_map", "user_role_map.user_id", "=", "user_company_map.user_id")
                    
                    ->leftJoin("roles", "roles.id", "=", "user_role_map.role_id")
                    
                    ->leftJoin("personal_access_tokens","personal_access_tokens.tokenable_id","users.id")

                    ->where("users.email", $email)

                    ->first();

                return $this->successResponse($user,"Success");
            }
            elseif(is_object($admin) && !Hash::check($password,$admin->password)) {
                return $this->warningResponse([],"Invalid password.",401);
            }
            else if(!is_object($admin)){
                return $this->warningResponse($admin,"Invalid email.",401);
            }
        } 
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
       
    }
     /**
     * register admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
    */
    public function adminRegister(Request $request,AuthController $AuthController) {
        
        $request->validate([
            "email"     => "required|email|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix|unique:users",
            "password"  => "required|min:6|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/",
            "name"      => "required",
            "type"      => "required"
        ]);

        try {
            
            $email = $request->email;

            $password = Hash::make($request->password);
            
            $name     = $request->name;
            
            $type     = $request->type;

            $admin = User::where("email","=", $email)
            
            ->first();
            
            
            if(!is_object($admin)) {
                $admin = new Admin();
                
                $res = $AuthController->createUser($request)->getContent();
                
                $resArr = json_decode($res,true);
                // $this->printR($resArr,true);
                $token = explode("|",$resArr['token'])[1];
                
                $userId                 = $resArr['id'];

                $admin->user_id         = $userId;
                $admin->access_token    = $token;
                $admin->type            = $type;
                $admin->created_at      = date("Y-m-d H:i:s");
                
                $admin->save();

                
                return $this->successResponse(["access_token" => $token,"id" => $userId],"Admin created successfully");

            }
            else {
                return $this->warningResponse($admin,"User already found.",302);
            }
        } catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
}
