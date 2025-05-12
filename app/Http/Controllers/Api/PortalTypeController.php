<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PortalType;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;

class PortalTypeController extends Controller
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
            if($request->has('smart_search')) {
                $search = $request->smart_search;
                $portalstype = PortalType::where('name','LIKE','%'.$search.'%')
                ->orWhere('link','LIKE','%'.$search.'%')
                ->orderBy("id", "DESC")
                ->paginate($this->cmperPage);
            }
            else {
                $portalstype = PortalType::orderBy("id", "DESC")
                ->paginate($this->cmperPage);
            }
            return $this->successResponse(["portalstype" => $portalstype ],"success",200);
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
            "name"    => "required",
            "link"    =>   "required",
           
        ]);

        try {
            
            $portalTypeData = [

            "name"         => $request->name, 
            "link"         => $request->link,
 
        ];

            $id = PortalType::insertGetId($portalTypeData);

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
        $updateData = $request->all();

        $isUpdate = PortalType::where('id', $id)
        
        ->update($updateData);

        return $this->successResponse(["is_update" => $isUpdate ],"success");
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        $isDel = PortalType::where('id', $id)
        
        ->delete();

        return $this->successResponse(["is_delete" => $isDel ],"success");
    }
}