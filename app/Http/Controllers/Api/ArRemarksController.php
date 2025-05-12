<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ArRemarks;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use DB;

class ArRemarksController extends Controller
{
    use ApiResponseHandler, Utility;
    public $perPage = 10000;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        try {

            if ($request->has('smart_searching')) {
                $searching = $request->smart_searching;
                $remarks = ArRemarks::where('remarks', 'LIKE', '%' . $searching . '%')
                    ->orderBy('id', 'DESC')
                    ->paginate($this->perPage);
            } else {

                $remarks = ArRemarks::orderBy('id', 'DESC')->paginate($this->perPage);
            }

            return $this->successResponse(["ar_remarks" => $remarks], "Success", 200);
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

            "remarks"  => "required|unique:ar_status,status|unique:ar_remarks,remarks"

        ]);

        try {

            $arRemarksData = [

                "remarks"   =>  $request->remarks

            ];

            $id = ArRemarks::insertGetId($arRemarksData);

            return $this->successResponse(["id" => $id], "ar_remarks added successfully.");
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
        $request->validate([

            "remarks"  => "required|unique:ar_status,status|unique:ar_remarks,remarks"

        ]);

        try {

            $updateData = $request->all();
            $isUpdate  = ArRemarks::where("id", $id)->update($updateData);

            return $this->successResponse(["is_update" => $isUpdate], "success", 200);
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
    public function destroy($id)
    {
        try {

            $isDelete  = ArRemarks::where("id", $id)->delete();

            return $this->successResponse(["is_delete" => $isDelete], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * 
     * fatch remarks agains arstatus 
     * 
     */

    public function fatchRemarksMap(Request $request)
    {

        if ($request->has('status_id') && $request->status_id) {

            $statusId  = $request->status_id;

            $arStatusData = DB::table('arstatus_arremarks_map')

                ->where("status_id", "=", $statusId)

                ->select("status_id")

                ->distinct()

                ->get();

            $arRemarksData = [];
            if (count($arStatusData)) {
                foreach ($arStatusData as $status_id) {
                    $arRemarksData[$status_id->status_id] = DB::table('arstatus_arremarks_map as srm')

                        ->join('ar_remarks as rem', 'rem.id', '=', 'srm.remarks_id')

                        ->select('srm.remarks_id', 'rem.remarks')

                        ->where("status_id", "=", $status_id->status_id)

                        ->get();


                    // $this->PrintR($statesCityData,true);
                    // exit;

                }
            }
            return $this->successResponse(["ar_status" => $arStatusData, "ar_remarks" => $arRemarksData,], "success");
        }
    }
}
