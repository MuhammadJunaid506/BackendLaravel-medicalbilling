<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User; 
use App\Models\Role;
use App\Http\Traits\Utility;
class AuthController extends Controller
{
    use Utility;
    /**
     * Create User
     * @author : Faheem Mahar
     * @param Request $request
     * @return User
     */
    public function createUser(Request $request) {
        try {
            $validateUser = Validator::make($request->all(),[
                "name"      => "required",
                "email"     => "required|email|unique:users,email",
                "password"  => "required"
            ]);
    
            if ($validateUser->fails()) {
                return response([
                    "status" => false,
                    "message" => "Validation error",
                    "errors" => $validateUser->errors()
                ],401);
            }
            $user = User::create([
                "first_name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password)
            ]);

            $role = Role::where("role_name", "=", "admin")->first(["id"]);
            
            $roleId = $role->id;

            $roleData=[

                "user_id"=>$user->id,

                "role_id"=>$roleId

            ];

            $this->addData("user_role_map",$roleData,0);

            $comapny_map=[

                "user_id"=>$user->id,

                "company_id"=>1,

            ];

            $this->addData("user_company_map",$comapny_map,0);

            return response([
                "status" => true,
                "message" => "User created successfully",
                "token" => $user->createToken("Admin TOKEN")->plainTextToken,
                "id" => $user->id
            ],200);


        } catch (\Throwable $th) {
            return response([
                "status" => false,
                "message" => $th->getMessage()
            ],500);
        }
        
    }
}
