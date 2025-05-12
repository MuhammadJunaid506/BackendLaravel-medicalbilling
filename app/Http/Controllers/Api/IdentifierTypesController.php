<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IdentifierTypes;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;

class IdentifierTypesController extends Controller
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
            if($request->has('smart_search') && $request->smart_search !='') {
                $search = $request->smart_search;
                $identifiertype = identifiertypes::where('name','LIKE','%'.$search.'%')
                ->orderBy("id", "DESC")
                ->paginate($this->cmperPage);
            }
            else {
                $identifiertype = identifiertypes::orderBy("id", "DESC")
                    ->paginate($this->cmperPage);
            }
            return $this->successResponse(["identifiertype" => $identifiertype ],"Success",200);
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
        ]);

        try {   
            $IdentifierTypeData = [
            "name"         => $request->name, 

        ];

            $id = identifiertypes::insertGetId($IdentifierTypeData);

            return $this->successResponse(["id" => $id ],"IdentifierType added successfully.");
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
            $isUpdate  = IdentifierTypes::where("id", $id)->update($updateData);
  
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
    public function destroy(Request $request,$id)
    {

            try { 

                $isDelete  = IdentifierTypes::where("id", $id)->delete();
      
                return $this->successResponse(["is_delete" => $isDelete ],"success",200);
    
            }
            
            catch (\Throwable $exception) {
                
                return $this->errorResponse([],$exception->getMessage(),500);
        }
        
    }
}
