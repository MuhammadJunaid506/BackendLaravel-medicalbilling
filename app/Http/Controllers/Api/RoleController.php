<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Models\Role;
use App\Models\RoleCapabilitiesMap;
use App\Models\CapabilityActionMap;
use App\Http\Traits\Utility;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        try {
            if($request->has('search')) {
                $search = $request->get('search');
                $roles = Role::where('role_name','LIKE','%'.$search.'%')
                ->orWhere('role_short_name','LIKE','%'.$search.'%')
                ->orderBy('role_name')
                ->paginate($this->cmperPage);
            }
            else
                $roles = Role::orderBy('role_name')                
                ->paginate($this->cmperPage);

            
            return $this->successResponse($roles,"Success");
        } 
        catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([],$exception->getMessage(),500);
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
            "role_name"         => "required | unique:roles",
            "role_short_name"   => "required",
            "role_description"  => "required",
            "role_privileges"   => "required",
        ]);
        
        $newRoleData = [
            "role_name" => $request->role_name,
            "role_short_name" => $request->role_short_name,
            "role_description" => $request->role_description,
        ];
    
        $role_privileges = $request->role_privileges;
        $role_privileges = json_decode($role_privileges,true);

        try {
            $role = Role::create($newRoleData);
            $result = [];
            foreach($role_privileges as $role_privilege) {
                $role_privilege['role_id'] = $role->id;
                $id = DB::table('role_privileges')->insertGetId($role_privilege);
                array_push($result, $id); 
            }
           
            return $this->successResponse(["id" => $role->id, "privileges_added" => $result],"Role created successfully");
        } 
        catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        
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

        $updateData['updated_at'] = $this->timeStamp();
        
        try {

            $isUpdate = Role::where("id",$id)->update($updateData);

            return $this->successResponse(['id' => $id,'is_update' => $isUpdate],"role updated successfully.");
        }
        catch (\Throwable $exception) {
            return $this->errorResponse([],$exception->getMessage(),500);
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
        try {
            
            $isDel = Role::find($id)->delete();
            $privilegesCleared = DB::table('role_privileges')->where('role_id',$id)->delete();
            return $this->successResponse(['id' => $id,'is_del' => $isDel, 'privileges_cleared' => $privilegesCleared],"Role deleted successfully.");
        }
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
}
