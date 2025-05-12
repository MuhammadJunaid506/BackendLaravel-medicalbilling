<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ArStatus;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\ArRemarks;
use DB;

class ArStatusController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $remarksArr = [];
            if ($request->has('smart_searching') && $request->smart_searching != '') {
                
                $remarks = DB::table("arstatus_arremarks_map")
                    ->select("ar_remarks.*", "arstatus_arremarks_map.status_id", "ar_status.status")
                    ->leftJoin('ar_remarks', 'ar_remarks.id', '=', 'arstatus_arremarks_map.remarks_id')
                    ->leftJoin('ar_status', 'ar_status.id', '=', 'arstatus_arremarks_map.status_id')
                    ->where('ar_remarks.remarks', 'LIKE', '%' . $request->smart_searching . '%')
                    ->orWhere('ar_status.status', 'LIKE', '%' . $request->smart_searching . '%')
                    ->get();
            
                $remarksArr = $remarks->groupBy('status_id')->toArray();
            
                $fatchstatus = ArStatus::where('id', array_keys($remarksArr))
                    ->orwhere('status', 'LIKE', '%' . $request->smart_searching . '%') 
                    ->orderBy('id', 'DESC')
                    ->paginate($this->cmperPage);
            
                foreach ($fatchstatus as $key => $value) {
                    if (!isset($remarksArr[$value->id])) {
                        $remarksArr[$value->id] = [];
                    }
                }
            }   
        
            else {
                $notToShowId = "DELETED"; 
                $fatchstatus = ArStatus::whereNotIn('status', [$notToShowId])
                ->orderBy('id', 'DESC')
                ->paginate($this->cmperPage);
                
                if(count($fatchstatus)) {
                    foreach($fatchstatus as $key => $value) {
                        
                        $remarks = DB::table("arstatus_arremarks_map")
                        
                        ->select("ar_remarks.*")
                        
                        ->leftJoin('ar_remarks','ar_remarks.id','=','arstatus_arremarks_map.remarks_id')
                        
                        ->where('arstatus_arremarks_map.status_id','=',$value->id)
                        
                        ->get();
                        
                        if(count($remarks)) {
                            $remarksArr[$value->id] = $remarks;
                        }
                    }
                }
            }

            return $this->successResponse(["ar_status" => $fatchstatus,'remarks' => $remarksArr], "Success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
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

            "status"                    => 'required|unique:ar_status,status|unique:ar_remarks,remarks',
            "considered_as_completed"   => "required"

        ]);

        $remarksArr = json_decode($request->remarks,true);

        //try 
        {

            $arStatusData = [

                "status"                    =>  $request->status,
                "considered_as_completed"   =>  $request->considered_as_completed

            ];

            $id = ArStatus::insertGetId($arStatusData);

            if(count($remarksArr)) {
                foreach($remarksArr as $remark) {
                    DB::table("arstatus_arremarks_map")
                    ->insertGetId(['status_id' => $id,'remarks_id' => $remark['id']]);
                }
            }
            return $this->successResponse(["id" => $id], "ar_status added successfully.");
        } 
        // catch (\Throwable $exception) {

        //     return $this->errorResponse([], $exception->getMessage(), 500);
        // }
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
        if(!$request->has('updating_status_only')) {
            $request->validate([
                "status"                    => 'required|unique:ar_status,status|unique:ar_remarks,remarks'
            ]);
        }
        $remarksErr = [];
        //try 
        {

            $updateData = $request->all();
            
            $existingRemarks = $request->existing_remarks;
            
            $newRemarks = $request->new_remarks;
           
            if(is_array($existingRemarks) && count($existingRemarks)) {
                //delete all old relation
                if( DB::table("arstatus_arremarks_map")->where('status_id',$id)->count())
                    DB::table("arstatus_arremarks_map")->where('status_id',$id)->delete();
                
                foreach($existingRemarks as $existingRem) {
                 
                        DB::table("arstatus_arremarks_map")
                        
                        ->insertGetId(['status_id' => $id,'remarks_id' => $existingRem]);
                }
            }
            elseif(is_array($existingRemarks) && count($existingRemarks) == 0) {
                if( DB::table("arstatus_arremarks_map")->where('status_id',$id)->count())
                    DB::table("arstatus_arremarks_map")->where('status_id',$id)->delete();
            }
            //handle new remarks
            if(is_array($newRemarks) && count($newRemarks)) {
                foreach($newRemarks as $newAr) {
                    $hasRecords = ArRemarks::where("remarks","=",$newAr)->count(); 
                    if($hasRecords == 0) {
                        $newArRId =  ArRemarks::insertGetId(['remarks' => $newAr]);
                        DB::table("arstatus_arremarks_map")
                        ->insertGetId(['status_id' => $id,'remarks_id' => $newArRId]);
                    }
                    else {
                        array_push($remarksErr,["remarks" => $newAr]);
                    }
                }
            }

            unset($updateData['updating_status_only']);
            unset($updateData['existing_remarks']);
            unset($updateData['new_remarks']);

            $isUpdate  = ArStatus::where("id", $id)->update($updateData);

            return $this->successResponse(["is_update" => $isUpdate,'remarks_error' => $remarksErr], "success", 200);
        } 
        // catch (\Throwable $exception) {

        //     return $this->errorResponse([], $exception->getMessage(), 500);
        // }
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

            $isDelete  = ArStatus::where("id", $id)->delete();
            //delete the map relation
            DB::table("arstatus_arremarks_map")
            
            ->where('status_id','=',$id) 
            
            ->delete();

            return $this->successResponse(["is_delete" => $isDelete], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
}
