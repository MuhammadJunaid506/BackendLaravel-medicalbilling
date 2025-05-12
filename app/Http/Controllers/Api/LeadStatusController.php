<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\LeadStatus;

class LeadStatusController extends Controller
{

  use ApiResponseHandler, Utility;
  
  /**
   * Get All Lead Status
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request){

    $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
    $leadStatus = LeadStatus::orderby('id','DESC');
    if($request->has('search')){
        $leadStatus = $leadStatus->where('status', 'like', '%' . $request->search . '%');;
    }
    $leadStatus =  $leadStatus->paginate($perPage);
    return $this->successResponse($leadStatus, "success");
  }

  /**
   * Store Lead Status
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request){
    $request->validate([
        'name'=>'required',
        'percentage'=>'required|integer',
        'status'=>'required',
    ]);
    LeadStatus::create([
        'status'=>$request->name,
        'percentage'=>$request->percentage,
        'active_status' => $request->status,
    ]);
    return $this->successResponse([], "success");
  }

  /**
   * Update Lead Status
   *
   * @param  int $id
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function update($id,Request $request){
    $request->validate([
        'name'=>'required',
        'percentage'=>'required|integer',
        'status'=>'required',
    ]);
    $leadstatus = LeadStatus::find($id);
    if($leadstatus == null){
        return $this->warningResponse([], "No Lead Status Found",404);
    }
    $leadstatus->status = $request->name;
    $leadstatus->percentage = $request->percentage;
    $leadstatus->active_status = $request->status;
    $leadstatus->save();

    return $this->successResponse(['is_update'=>true], "success");
  }

  /**
   * Delete Lead Status
   *
   * @param  int $id
   * @return \Illuminate\Http\Response
   */
  public function delete($id) {
    $leadstatus = LeadStatus::find($id);
    if($leadstatus == null){
        return $this->warningResponse([], "No Lead Status Found",404);
    }
    $leadstatus->delete();
    return $this->successResponse(['is_deleted'=>true], "success");
  }
}
