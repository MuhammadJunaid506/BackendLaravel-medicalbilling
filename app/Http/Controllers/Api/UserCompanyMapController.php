<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserCompanyMap;
use App\Models\UserRoleMap;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use DB;
class UserCompanyMapController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     * 
     */
 
    public function index(Request $request)
    {

        try {  
            $searching = $request->get('search');
            if( $request->has('search')) {
                
               
                $userscompanymap = UserRoleMap::select("user_company_map.*","user_company_map.user_id", "user_company_map.company_id",DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"), 
                "companies.company_name")
                
            
                ->join('users', 'users.id', '=', 'user_role_map.user_id')

                ->join('user_company_map', 'user_company_map.user_id', '=', 'user_role_map.user_id')
                
                ->join('companies', 'companies.id', '=', 'user_company_map.company_id')

                ->whereIn('user_role_map.role_id',[1,5,6,7,8,11])
                
                ->whereRaw(DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) LIKE '%$searching%'"))
                
                ->orWhere('companies.company_name','LIKE','%'.$searching.'%')

                ->orderBy("user_company_map.company_id","DESC")
                
                ->paginate($this->cmperPage);
                
            }
            else {
                $userscompanymap = UserRoleMap::select("user_company_map.*","user_company_map.user_id", "user_company_map.company_id",DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"), 
                "companies.company_name")
                
            
                ->join('users', 'users.id', '=', 'user_role_map.user_id')

                ->join('user_company_map', 'user_company_map.user_id', '=', 'user_role_map.user_id')
                
                ->join('companies', 'companies.id', '=', 'user_company_map.company_id')

                ->whereIn('user_role_map.role_id',[1,5,6,7,8,11])

                ->orderBy("user_company_map.company_id","DESC")
                
                ->paginate($this->cmperPage);
                
            }

        

        return $this->successResponse(["company_users" => $userscompanymap],"success",200);
    }
    catch (\Throwable $exception) {
        
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
            "user_ids"       => "required",
            "company_id"    =>   "required",
           
        ]);

        $userIds = json_decode($request->user_ids,true);
        try {
            
            // $UserCompanyMapData = [

            // "user_id"            => $request->user_id, 
            // "company_id"         => $request->company_id,
 
            // ];
            if(count($userIds)) {
                foreach($userIds as $userId) {
                    $hasUser = UserCompanyMap::where('user_id','=',$userId)->count();
                    if($hasUser) {
                        UserCompanyMap::where('user_id','=',$userId)->update(['company_id' => $request->company_id]);
                    }
                    else {
                        UserCompanyMap::insertGetId(['company_id' => $request->company_id,'user_id' => $userId]);
                    }
                }
            }
            //$id = UserCompanyMap::insertGetId($UserCompanyMapData);

            return $this->successResponse(["result" => 1 ],"success",200);
        }
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
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
        try { 

            $updateData = $request->all();
            $isUpdate  = UserCompanyMap::where("id", $id)->update($updateData);
  
            return $this->successResponse(["is_update" => $isUpdate ],"success",200);

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

            $isDelete  = UserCompanyMap::where("id", $id)->delete();
  
            return $this->successResponse(["is_delete" => $isDelete ],"success",200);

        }
        
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
    /**
     *  fetch the company and users
     */
    function fetchCompanyUsers(Request $request) {
        if($request->has('company_search') && $request->get('company_search') !="") {
            $companies =  DB::table('companies')
            ->where('company_name','LIKE','%'.$request->company_search.'%')
            ->get();
        }
        else {
            $companies =  DB::table('companies')
            ->get();
        }
        if($request->has('user_search') && $request->get('user_search') !="") {
            $users = DB::table('user_role_map')
            ->select('users.id AS user_id',DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"))
            ->join('users',function($join) {
                $join->on('users.id','=','user_role_map.user_id')
                ->whereIn('user_role_map.role_id',[1,5,6,7,8,11]);
            })
            ->whereRaw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) LIKE '%$request->user_search%'")
            ->get();
        }
        else {
            $users = DB::table('user_role_map')
            ->select('users.id AS user_id',DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"))
            ->join('users',function($join) {
                $join->on('users.id','=','user_role_map.user_id')
                ->whereIn('user_role_map.role_id',[1,5,6,7,8,11]);
            })
            ->get();
        }
        return $this->successResponse(["companies" => $companies,'users' => $users],"success",200);
    }
}
