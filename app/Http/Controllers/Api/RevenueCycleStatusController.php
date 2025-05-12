<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RevenueCycleStatus;
use App\Models\RevenueCycleRemarks;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\UserAccountActivityLog;
use DB;

class RevenueCycleStatusController extends Controller
{
    use ApiResponseHandler, Utility, UserAccountActivityLog;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $finalJSON = [];
            if ($request->has('search') && isset($request->search)) {

                $keyWord = $request->get('search');



                $revenueStatus = RevenueCycleStatus::select("status AS label", "id", "considered_as_completed","status_for as module");

                $revenueStatus = $revenueStatus->where("status", "LIKE", "%" . $keyWord . "%");

                $revenueStatus = $revenueStatus->orderBy("status")

                    ->get();

                // $this->printR($revenueStatus,true);
                if (count($revenueStatus)) {
                    foreach ($revenueStatus as $indx => $eachRevenueStatus) {
                        $finalJSON[$indx] = $eachRevenueStatus;
                        //$finalJSON[]["remarks"] = [];
                        $remarks = DB::table('revenue_cycle_remarks_map as srm')

                            ->join('revenue_cycle_remarks as rem', 'rem.id', '=', 'srm.remark_id')

                            ->select('rem.id', 'rem.remarks AS label')

                            ->where("status_id", "=", $eachRevenueStatus->id);


                        $remarks = $remarks->get();

                        $finalJSON[$indx]["remarks"] = $remarks;
                    }
                }
            } else {

                $revenueStatus = RevenueCycleStatus::select("status AS label", "id", "considered_as_completed","status_for as module")

                    ->orderBy("status")

                    ->get();

                foreach ($revenueStatus as $indx => $eachRevenueStatus) {
                    $finalJSON[$indx] = $eachRevenueStatus;
                    //$finalJSON[]["remarks"] = [];
                    $remarks = DB::table('revenue_cycle_remarks_map as srm')

                        ->join('revenue_cycle_remarks as rem', 'rem.id', '=', 'srm.remark_id')

                        ->select('rem.id', 'rem.remarks AS label')

                        ->where("status_id", "=", $eachRevenueStatus->id)

                        ->get();
                    $finalJSON[$indx]["remarks"] = $remarks;
                }
            }
            return $this->successResponse(["statuses" => $finalJSON], "success", 200);
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

            "status"                    => "required|unique:revenue_cycle_status,status",
            "considered_as_completed"   => "required",
            "remarks" => "required",

        ]);
        $sessionUserName = $this->getSessionUserName($request, $request->user_id);
        $userId = -1; //this id we have assigned to the revenue cycle status, for tracking in log
        $remarksArr = json_decode($request->remarks, true);

        try {
            $addRevenueStatus = [
                "status"                   =>  $request->status,
                "considered_as_completed"  =>  $request->considered_as_completed,
                "status_for"                => $request->module,
                "created_at"               => $this->timeStamp()
            ];

            $id = RevenueCycleStatus::insertGetId($addRevenueStatus);

            if (count($remarksArr)) {
                foreach ($remarksArr as $remark) {
                    DB::table("revenue_cycle_remarks_map")
                        ->insertGetId(['status_id' => $id, 'remark_id' => $remark['id']]);
                }
            }
            $statusType = $this->fetchData("revenue_cycle_status", ['id' => $id], 1, ['status']);
            $msg = $this->addNewDataLogMsg($sessionUserName, $statusType->status);
            //handle the user activity
            $this->handleUserActivity(
                $userId,
                $request->user_id,
                "Revenue Cycle Status",
                "Add",
                $msg,
                $this->timeStamp(),
                NULL
            );
            return $this->successResponse(["id" => $id], "success");
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
        $chkBilling = DB::table("billing")->where("status_id", $id)->count();
        
        $chkAR = DB::table("account_receivable")->where("status", $id)->count();
        
        $count = $chkBilling+$chkAR;

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


        $remarksErr = [];

        try {

            $updateData = [
                "status"                   => $request->status,
                "considered_as_completed"  => $request->considered_as_completed,
                "status_for" => $request->module
            ];

            $sessionUserName = $this->getSessionUserName($request, $request->user_id);

            $userId = -1; //this id we have assigned to the revenue cycle status, for tracking in log
            $logMsg = "";

            $existingRemarks = $request->existing_remarks;

            $newRemarks = $request->remarks;

            $statusExists = RevenueCycleStatus::where('status', '=', $updateData['status'])
                ->where('id', '!=', $id)
                ->exists();

            if ($statusExists) {

                return response()->json(['message' => 'Status already exists'], 409);
            }

            if (is_array($existingRemarks) && count($existingRemarks)) {
                //delete all old relation
                if (DB::table("revenue_cycle_remarks_map")->where('status_id', $id)->count())
                    DB::table("revenue_cycle_remarks_map")->where('status_id', $id)->delete();

                foreach ($existingRemarks as $existingRem) {

                    DB::table("revenue_cycle_remarks_map")

                        ->insertGetId(['status_id' => $id, 'remark_id' => $existingRem]);
                }
            } elseif (is_array($existingRemarks) && count($existingRemarks) == 0) {
                if (DB::table("revenue_cycle_remarks_map")->where('status_id', $id)->count())
                    DB::table("revenue_cycle_remarks_map")->where('status_id', $id)->delete();
            }
            //handle new remarks
            if (is_array($newRemarks) && count($newRemarks)) {
                foreach ($newRemarks as $newAr) {
                    $hasRecords = RevenueCycleRemarks::where("remarks", "=", $newAr)->count();
                    if ($hasRecords == 0) {
                        $newArRId =  RevenueCycleRemarks::insertGetId(['remarks' => $newAr]);
                        DB::table("revenue_cycle_remarks_map")
                            ->insertGetId(['status_id' => $id, 'remark_id' => $newArRId]);
                    } else {
                        array_push($remarksErr, ["remarks" => $newAr]);
                    }
                }
            }



            unset($updateData['updating_status_only']);
            unset($updateData['existing_remarks']);
            unset($updateData['remarks']);

            $status = RevenueCycleStatus::find($id);
            $statusArr = $this->stdToArray($status);
            $logMsg .= $this->makeTheLogMsg($sessionUserName, $updateData, $statusArr);


            $isUpdate  = RevenueCycleStatus::where('id', $id)
                ->update(['considered_as_completed' => $updateData['considered_as_completed'],
                 'status' => $updateData['status'],'status_for' => $updateData['status_for']]);

            //handle the user activity
            $this->handleUserActivity(
                $userId,
                $request->user_id,
                "Revenue Cycle Status",
                "update",
                $logMsg,
                NULL,
                $this->timeStamp()
            );

            return $this->successResponse(["is_update" => $isUpdate, 'remarks_error' => $remarksErr], "success", 200);
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
    public function destroy(Request $request, $id)
    {
        $sessionUserName = $this->getSessionUserName($request, $request->user_id);

        $sessionUserId = $this->getSessionUserId($request);

        $userId = -1; //this id we have assigned to the revenue cycle status, for tracking in log

        try {

            $statusType = $this->fetchData("revenue_cycle_status", ['id' => $id], 1, ['status']);

            $delMsg = $this->delDataLogMsg($sessionUserName, $statusType->status);

            $isDelete  = RevenueCycleStatus::where("id", $id)->delete();
            //delete the map relation
            DB::table("revenue_cycle_remarks_map")

                ->where('status_id', '=', $id)

                ->delete();

            $this->handleUserActivity(
                $userId,
                $sessionUserId,
                "Revenue Cycle Status",
                "Delete",
                $delMsg,
                NULL,
                $this->timeStamp()
            );
            return $this->successResponse(["is_delete" => $isDelete], "success", 200);
        } catch (\Throwable $exception) {
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
}
