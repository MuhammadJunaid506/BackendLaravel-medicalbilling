<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthcareSoftwareTypes as HealthcareSoftwareTypesModel;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;

class HealthcareSoftwareTypes extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * 
     * fetch the types
     * 
     * @param \Illuminate\Http\Response
     */
    public function index() {
        
        $types = HealthcareSoftwareTypesModel::select("id","type","status")
        
        ->paginate($this->cmperPage);

        return $this->successResponse(["types" => $types],"success");
    }
    /**
     * add the health care software type
     * 
     *  @param \Illuminate\Http\Request $request
     *  @param \Illuminate\Http\Response
     */
    public function store(Request $request) {
        
        $request->validate([
            "type" => "required",
        ]);

        $sessionUserId = $request->session_userid;
        
        $status = $request->status ?? 0; 
        
        $type = $request->type;

        $addData = ["type" => $type,"status" => $status,"created_by" => $sessionUserId,"created_at" => $this->timeStamp()];
        
        $id = HealthcareSoftwareTypesModel::insertGetId($addData);

        return $this->successResponse(["id" => $id],"success");
    }
    /**
     * update the health care software type
     * 
     *  @param \Illuminate\Http\Request $request
     *  @param \Illuminate\Http\Response
     */
    public function update(Request $request,$id) {
        
        $request->validate([
            "type" => "required",
        ]);

        $sessionUserId = $request->session_userid;
        
        $status = $request->status ?? 0; 
        
        $type = $request->type;

        $updateData = ["type" => $type,"status" => $status,"updated_by" => $sessionUserId,"updated_at" => $this->timeStamp()];
        
        $id = HealthcareSoftwareTypesModel::where("id",$id)->update($updateData);

        return $this->successResponse(["id" => $id],"success");
    }
    /**
     * delete the health care software type
     * 
     *  @param \Illuminate\Http\Request $request
     *  @param \Illuminate\Http\Response
     */
    public function delete(Request $request,$id) {

        $id = HealthcareSoftwareTypesModel::where("id",$id)->delete();

        return $this->successResponse(["id" => $id],"success");
    }
}
