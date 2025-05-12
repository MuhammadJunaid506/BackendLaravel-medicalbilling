<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RevenueCycleRemarks;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use DB;
use App\Http\Traits\UserAccountActivityLog;

class RevenueCycleRemarksController extends Controller
{
    use ApiResponseHandler, Utility,UserAccountActivityLog;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $revenueRemarksData = RevenueCycleRemarks::where('remarks', 'LIKE', '%' . $search . '%')
                    ->orderBy('remarks')
                    ->get();
            } else {
                $revenueRemarksData = RevenueCycleRemarks::orderBy('remarks')
                    ->get();
            }

            return $this->successResponse(["revenue_remarks" => $revenueRemarksData], "Success", 200);
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

            "remarks" => "required|unique:revenue_cycle_remarks,remarks",

        ]);
        $sessionUserName = $this->getSessionUserName($request, $request->user_id);
        $userId = -2; //this id we have assigned to the revenue cycle status, for tracking in log
        try {

            $addRevenueRemarks = [

                "remarks"      => $request->remarks,
                "created_at"   => $this->timeStamp()

            ];

            $ids = RevenueCycleRemarks::insertGetId($addRevenueRemarks);
            $msg = $this->addNewDataLogMsg($sessionUserName, $request->remarks);
            //handle the user activity
            $this->handleUserActivity(
                $userId,
                $request->user_id,
                "Revenue Cycle Remarks",
                "Add",
                $msg,
                $this->timeStamp(),
                NULL
            );
            return $this->successResponse(["id" => $ids], "success");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
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
        $chkRemarkBilling = DB::table("billing")->where("remark_id", $id)->count();
        
        $chkRemarkAR = DB::table("account_receivable")->where("remarks", $id)->count();
        
        $count = $chkRemarkBilling+$chkRemarkAR;

        return $this->successResponse(["total_in_use" => $count], "success");
       
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

            "remarks"  => "required"
        ]);
        $userId = -2;
        $sessionUserName = $this->getSessionUserName($request, $request->user_id);


        $logMsg = "";

        try {

            $remarks = $request->remarks;
            
            $duplicateRemarks = RevenueCycleRemarks::where("id","!=", $id)
            
            ->where("remarks","=",$remarks)
            
            ->count();
            $isUpdate = 0;
            if($duplicateRemarks == 0) {
                $remarksData = RevenueCycleRemarks::find($id);
                $remarksArr = $this->stdToArray($remarksData);
                $logMsg .= $this->makeTheLogMsg($sessionUserName, ["remarks" => $remarks], $remarksArr);
                 //handle the user activity
                $this->handleUserActivity(
                    $userId,
                    $request->user_id,
                    "Revenue Cycle Remarks",
                    "update",
                    $logMsg,
                    NULL,
                    $this->timeStamp()
                );
                $isUpdate  = RevenueCycleRemarks::where("id", $id)->update(["remarks" => $remarks]);
            }
            if($duplicateRemarks == 0)
                return $this->successResponse(["is_update" => $isUpdate], "success", 200);
            else
                return $this->successResponse(["is_update" => $isUpdate,"is_duplicate" => $duplicateRemarks,"msg" => "Duplicate remarks found."], "success", 409);
             
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
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
        $sessionUserName = $this->getSessionUserName($request, $request->user_id);

        
        $remarkrksType = $this->fetchData("revenue_cycle_remarks", ['id' => $id], 1, ['remarks']);

        $delMsg = $this->delDataLogMsg($sessionUserName, $remarkrksType->remarks);

        $userId = -2;
        try {
            $relationExist = DB::table("revenue_cycle_remarks_map")
            ->where('remark_id', $id)
            ->count();
            if($relationExist == 0) {
                $isDelete  = RevenueCycleRemarks::where("id", $id)->delete();
                $this->handleUserActivity(
                    $userId,
                    $request->user_id,
                    "Revenue Cycle Remarks",
                    "Delete",
                    $delMsg,
                    NULL,
                    $this->timeStamp()
                );
                return $this->successResponse(["is_delete" => $isDelete], "success", 200);
            }
            else {
                return $this->successResponse(["is_delete" => 0,"msg" => "The remark you trying to delete , is linked with status($relationExist)"], "success", 409);
            }
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    
    
}
