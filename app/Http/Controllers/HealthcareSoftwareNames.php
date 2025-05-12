<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\HealthcareSoftwareNames as HealthcareSoftwareNamesModel;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;

class HealthcareSoftwareNames extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * fetch the healthcare software names
     * 
     * @return  \Illuminate\Http\Response
     */
    public function index(Request $request) {
        
        $perPage = $this->cmperPage;
        
        $typeId = $request->type_id;

        $healthcareSoftwareNames = HealthcareSoftwareNamesModel::select(
            "healthcare_software_names.software_type_id",
            "healthcare_software_names.software_name",
            "healthcare_software_names.software_type",
            "healthcare_software_names.active_status",
            "healthcare_software_names.created_by",
            "healthcare_software_names.updated_by",
            "healthcare_software_types.type",
            "healthcare_software_names.id",

        )
        
        ->join("healthcare_software_types","healthcare_software_types.id","=","healthcare_software_names.software_type_id")
        
        ->where("healthcare_software_names.software_type_id","=",$typeId)

        ->paginate($perPage);

        return $this->successResponse(["names" => $healthcareSoftwareNames],"success");

    }
    /**
     * create the new resource
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function store(Request $request) {
        
        $request->validate([
            "software_type_id"  => "required",
            "software_name"     => "required",
            "software_type"     => "required",
            "active_status"     => "required",
            
        ]);

        $sessionUserId = $request->session_userid;

        $softwareTypeId = $request->software_type_id;

        $softwareName = $request->software_name;

        $softwareType = $request->software_type;

        $activesStatus = $request->active_status;

        //HealthcareSoftwareNamesModel
        $addNames = [
            "software_type_id" => $softwareTypeId,
            "software_name" => $softwareName,
            "software_type" => $softwareType,
            "active_status" => $activesStatus,
            "created_by" => $sessionUserId,
            "created_at" => $this->timeStamp()
        ];

        $id = HealthcareSoftwareNamesModel::insertGetId($addNames);

        return $this->successResponse(["id" => $id],"success");
    }
    /**
     * update the  resource
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Http\Response
     */
    public function update(Request $request,$id) {
        
        $updateData = [];
        if($request->has("software_name")) {
            $updateData["software_name"] = $request->software_name;
        }
        if($request->has("software_type")) {
            $updateData["software_type"] = $request->software_type;
        }
        if($request->has("active_status")) {
            $updateData["active_status"] = $request->active_status;
        }
        if($request->has("software_type_id")) {
            $updateData["software_type_id"] = $request->software_type_id;
        }
        
        $sessionUserId = $request->session_userid;
        $isUpdate = 0;
        if( count($updateData) > 0 ) {
            $updateData["updated_by"] = $sessionUserId;
            $updateData["updated_at"] = $this->timeStamp();
            
            $isUpdate = HealthcareSoftwareNamesModel::where("id",$id)
            
            ->update($updateData);
        }
        return $this->successResponse(["is_update" => $isUpdate],"success");
    }
    /**
     * delete the  resource
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request,$id) {
        
        $isDelete = HealthcareSoftwareNamesModel::where("id",$id)
            
        ->delete();

        return $this->successResponse(["is_delete" => $isDelete],"success");

    }
}
