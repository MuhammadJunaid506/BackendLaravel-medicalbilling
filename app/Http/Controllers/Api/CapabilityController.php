<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CapabilityActionMap;
use App\Models\Capability;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\CapabilityAction;

class CapabilityController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            
            $capabilities = Capability::paginate(20);
            
            $capabilityActionData = [];

            if(count($capabilities) > 0 ) {
                
                $capabilitiesArr = $this->stdToArray($capabilities);
                
                $capabilityIds = array_column($capabilitiesArr['data'], 'id');
                
                $capabilitiesActionData = CapabilityActionMap::select("capability_actions.*","capabilities_capabilityactions_map.capability_id")

                ->leftJoin("capability_actions","capability_actions.id","=","capabilities_capabilityactions_map.capability_action_id")
                
                ->whereIn("capability_id",$capabilityIds)

                ->get();

                //create the data format for action related to the capability
                foreach($capabilityIds as $capabilityId) {
                    $capabilityRec = [];
                    if( count($capabilitiesActionData) ) {
                        
                        foreach($capabilitiesActionData as $capabilityAction) {
                            if($capabilityId == $capabilityAction->capability_id) {
                                array_push($capabilityRec,$capabilityAction);
                            }
                        }
                    }
                    if( count($capabilityRec) )
                        $capabilityActionData[$capabilityId] = $capabilityRec;
                }
            }
            // $this->printR($capabilitiesActionData,true);
            return $this->successResponse(["capabilities" => $capabilities , "capability_actions" => $capabilityActionData],"Success");
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
            "title"         => "required | unique:capabilities,title",
            "description"   => "required",
            "actions"       => "required",
            "path"          => "required"
        ]);

        $path = $request->has('path') ? $request->path : "";

        $addCapabilityData = [
            "title"         => $request->title,
            "description"   => $request->description,
            "path"          => $path,
            "created_at"    => date("Y-m-d H:i:s")
        ];
        
        $actions = $request->actions;

        $newCapability = Capability::create($addCapabilityData);

        if( isset($newCapability->id) && strpos($actions,",") ) {
            $breakCapabilityActions = explode(",",$actions);
            $prepData = [];
            foreach($breakCapabilityActions as $action) {
               array_push($prepData,['capability_action_id' => $action,'capability_id' => $newCapability->id,'created_at' => date("Y-m-d H:i:s")]);
            }
            CapabilityActionMap::insert($prepData);//insert map relation of capability and capability action

            return $this->successResponse(["id" => $newCapability->id],"Capability added successfully.");

        }

    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    function filterCapabilties(Request $request) {
        
        try {
            $keyWord = $request->keyword;
            if($request->has("col")) {
                
                $col = $request->col;
                
                $capabilities = Capability::where($col, 'LIKE', '%'. $keyWord. '%')
                
                ->get();
            }
            else {
                
                $capabilities = Capability::where("title", 'LIKE', '%'. $keyWord. '%')
                
                ->orWhere("path",'LIKE', '%'. $keyWord. '%')
                
                ->get();
            }
            
            $capabilityActionData = [];

            if(count($capabilities) > 0 ) {
                
                $capabilitiesArr = $this->stdToArray($capabilities);
                
                $capabilityIds = array_column($capabilitiesArr, 'id');
                
                $capabilitiesActionData = CapabilityActionMap::select("capability_actions.*","capabilities_capabilityactions_map.capability_id")

                ->leftJoin("capability_actions","capability_actions.id","=","capabilities_capabilityactions_map.capability_action_id")
                
                ->whereIn("capability_id",$capabilityIds)

                ->get();

                //create the data format for action related to the capability
                foreach($capabilityIds as $capabilityId) {
                    $capabilityRec = [];
                    if( count($capabilitiesActionData) ) {
                        
                        foreach($capabilitiesActionData as $capabilityAction) {
                            if($capabilityId == $capabilityAction->capability_id) {
                                array_push($capabilityRec,$capabilityAction);
                            }
                        }
                    }
                    if( count($capabilityRec) )
                        $capabilityActionData[$capabilityId] = $capabilityRec;
                }
            }
            // $this->printR($capabilitiesActionData,true);
            return $this->successResponse(["capabilities" => $capabilities , "capability_actions" => $capabilityActionData],"Success");
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
        if(!$request->has('keyword')) {
            try {
                
                $capability = Capability::find($id);

                // $this->printR($capability,true);
                $capabilityActionData = CapabilityActionMap::select("capability_actions.*","capabilities_capabilityactions_map.capability_id")

                ->leftJoin("capability_actions","capability_actions.id","=","capabilities_capabilityactions_map.capability_action_id")
                
                ->where("capability_id",$id)

                ->get();

                return $this->successResponse(["capability" => $capability , "capability_actions" => $capabilityActionData],"Success");
            }
            catch (\Throwable $exception) {
                //throw $th;
                return $this->errorResponse([],$exception->getMessage(),500);
            }
        }
        else {
            return $this->filterCapabilties($request);
        }
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
        $request->validate([
            "col" => "required",
            "val" => "required"
        ]);

        try {
            $col = $request->col;
            $val = $request->val;

            if( $col == "actions" ) {
                $actionExist = CapabilityActionMap::where("capability_id",$id)->count();
                if( $actionExist > 0 ) {
                    
                    CapabilityActionMap::where("capability_id",$id)->delete();//delete added map relation
                    
                    if( strpos($val,",") ) {
                        $breakCapabilityActions = explode(",",$val);
                        $prepData = [];
                        foreach($breakCapabilityActions as $action) {
                           array_push($prepData,['capability_action_id' => $action,'capability_id' => $id,'created_at' => date("Y-m-d H:i:s")]);
                        }
                        CapabilityActionMap::insert($prepData);//insert new map relation of capability and capability action
            
                        return $this->successResponse(["id" => $id,"is_update" => true],"Capability updated successfully.");
                    }
                }
                else {
                    if( strpos($val,",") ) {
                        $breakCapabilityActions = explode(",",$val);
                        $prepData = [];
                        foreach($breakCapabilityActions as $action) {
                           array_push($prepData,['capability_action_id' => $action,'capability_id' => $id,'created_at' => date("Y-m-d H:i:s")]);
                        }
                        CapabilityActionMap::insert($prepData);//insert new map relation of capability and capability action
            
                        return $this->successResponse(["id" => $id,"is_update" => true],"Capability updated successfully.");
                    }
                }
            }
            else {
                $isUpdate = Capability::find($id)->update([$col => $val,"updated_at" => date("Y-m-d H:i:s")]);
            }
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

            $actionExist = CapabilityActionMap::where("capability_id",$id)->count();

            if( $actionExist > 0 )
                CapabilityActionMap::where("capability_id",$id)->delete();//delete added map relation

            $isDel = Capability::find($id)->delete();

            return $this->successResponse(['id' => $id,'is_del' => $isDel],"Capability deleted successfully.");
        }
        catch (\Throwable $exception) {
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
}
