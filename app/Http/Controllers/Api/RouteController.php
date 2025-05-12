<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Route;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;


class RouteController extends Controller
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
        
        
            $parentRoute = Route::where('parent_navigation_id','=',0)
            ->where('is_navigation','=',1)
            ->orderBy('sort_by')
            ->get();
            $innerRoute = [];
            if(count($parentRoute)) {
                foreach($parentRoute as $eachRoute) {
                    $innerRoute[$eachRoute->id] = Route::where('parent_navigation_id','=',$eachRoute->id)
                    ->where('is_navigation','=',1)
                    ->orderBy('sort_by')
                    ->get();
                }
            }
        
            return $this->successResponse(["parent_routes" => $parentRoute,'inner_route' => $innerRoute ],"Success",200);
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
    /**
     * Report routes list
     * 
     *@param \Illuminate\Http\Request $request
     *@param \Illuminate\Http\Response
     */
    public function reportRoutesList(Request $request) {
        
        $reportObj = new Route();

        $routes = $reportObj->reportRoutes();
        $security =  $reportObj->securityRoutes();
        $lead =  $reportObj->leadRoutes();
        
        $reportHeader = $reportObj->reportHeader();
        // $this->printR($reportHeader,true);
        $innerHeaderData = [];
        if(count($reportHeader)) {
            foreach($reportHeader as $header) {
                $id = $header->id;
                $innerHeaderData[$id] = $reportObj->reportInnderHeader($id);
            }
        }

        $reportObj = NULL;
        
        return $this->successResponse([strtoupper("credentialing") => $routes,strtoupper('billing') => [],
        strtoupper('security') =>$security,'header' => $reportHeader,'inner_header' => $innerHeaderData,strtoupper("lead") => $lead],"success");
    }
    /**
     * Report routes list
     * 
     *@param \Illuminate\Http\Request $request
     *@param \Illuminate\Http\Response
     */
    public function settingsRoutesList(Request $request) {
        
        $reportObj = new Route();

        $routes = $reportObj->settingRoutes();
        $inneSections = [];
        if(count($routes)) {
            foreach($routes as $route) {
                $inneSections[$route->id] = $reportObj->settingSection($route->id);
            }
        }
        $reportObj = NULL;

        return $this->successResponse([strtoupper("settings") => $routes,'inner_sections' => $inneSections],"success");
    }
    
}
