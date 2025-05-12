<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RouteRoleMap;
// use App\Models\UserRoleMap;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use DB;

class RouteRoleMapController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
          

            $searching = $request->get('search');
                
            if( $request->has('search')) {
         
            $routesrolmap = RouteRoleMap::select("route_role_map.*","route_id", "role_id",DB::raw("cm_routes.name"), 
            "roles.role_name")

            ->join('routes', 'routes.id', '=', 'route_role_map.route_id')

            ->join('roles', 'roles.id', '=', 'route_role_map.role_id')

            ->Where('routes.name','LIKE','%'.$searching.'%')
                
            ->orWhere('roles.role_name','LIKE','%'.$searching.'%')

            ->orderBy("route_role_map.role_id","DESC")

            ->paginate($this->cmperPage);
           
            }
            else {
                
                $routesrolmap = RouteRoleMap::select("route_role_map.*","route_id", "role_id",DB::raw("cm_routes.name"), 
                "roles.role_name")

                    ->join('routes', 'routes.id', '=', 'route_role_map.route_id')

                    ->join('roles', 'roles.id', '=', 'route_role_map.role_id')  

                    ->orderBy("route_role_map.role_id","DESC")

                    ->paginate($this->cmperPage);
            }

            return $this->successResponse(["routesrolmap" => $routesrolmap ],"success",200);
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
            
            "route_data"    =>   "required",
        ]);

            
        $routsData = $request->route_data;
        
        $routsDataArr = json_decode($routsData,true);

        // $this->printR($routsDataArr,true);
        // $routesData = [
        //     ["route_id" => 1,"role_id" => 111],
        //     ["route_id" => 2,"role_id" => 111],
        //     ["route_id" => 3,"role_id" => 111],
        //     ["route_id" => 4,"role_id" => 111],
        //     ["route_id" => 5,"role_id" => 111],
        // ];
        // $this->printR($insData,true);
        try{
           
            foreach($routsDataArr as $route) {
                $roleId = $route['role_id'];
                $routeId = $route['route_id'];
                $hasData = RouteRoleMap::where([
                    ["role_id", "=", $roleId],
                    ["route_id", "=", $routeId]
                ])
                ->count();
                if(!$hasData) {
                    $id = RouteRoleMap::insertGetId($route);
                }
            }
           

            return $this->successResponse(["id" => $id ],"success");
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
            $isUpdate  = RouteRoleMap::where("id", $id)->update($updateData);
  
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

            $isDelete  = RouteRoleMap::where("id", $id)->delete();
  
            return $this->successResponse(["is_delete" => $isDelete ],"success",200);

        }
        
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
    }
    }
}
