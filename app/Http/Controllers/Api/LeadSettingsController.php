<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferredByDropdown;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\LeadTypesDropdown;
use Illuminate\Support\Facades\DB;

class LeadSettingsController extends Controller
{

    use ApiResponseHandler, Utility;

    /**
     * Get All Referred By Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getReferredbydropdowns(Request $request)
    {


        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $referredByDropdown = ReferredByDropdown::orderby('id', 'DESC');
        if ($request->has('search')) {
            $searching = $request->search;
            $phoneNumber = $this->sanitizePhoneNumber($request->search);
            if(is_numeric($phoneNumber))
                $searching = $phoneNumber;
            else
                $searching = $searching;
           
            $referredByDropdown = $referredByDropdown->where('name', 'like', '%' . strtoupper($searching). '%')
            ->orWhere('email', 'like', '%' . $searching . '%')
            ->orWhere('phone', 'like', '%' . $searching . '%');
        }
        $referredByDropdown = $referredByDropdown->paginate($perPage);

        return $this->successResponse(['referred_by_dropdown' => $referredByDropdown], "success");
    }
    /**
     * Get All Referred By Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getReferredbyAllDD(Request $request)
    {



        $referredByDropdown = ReferredByDropdown::orderby('id', 'DESC');

        $referredByDropdown = $referredByDropdown->get(["id", "name"]);
        $leadRefBy = [
            ["id" => 0, "name" => "Add New"]
        ];
        if (count($referredByDropdown)) {
            foreach ($referredByDropdown as $leadRef) {
                $leadRefBy[] = [
                    "id" => $leadRef->id,
                    "name" => $leadRef->name
                ];
            }
        }
        return $this->successResponse(['lead_ref_by' => $leadRefBy], "success");
    }
    /**
     * lead filter dropdown
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function leadFilterDropdown(Request $request) {
        
        $leadStatus = $this->fetchData("lead_status",["active_status" => 1]);

        $users = DB::table("leads")
        
        ->select("users.id as user_id",DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,''))  AS user_name"))

        ->join("users", "users.id","=","leads.created_by")
        
        ->groupBy('leads.created_by')

        ->get();

        $leadRefBDD  = $this->fetchData("referredby_dropdowns");
        
        $sysUsers = DB::table('emp_location_map AS elp')
        
        ->join('users AS u', 'u.id', '=', 'elp.emp_id')
        ->select("u.id", DB::raw("CONCAT(COALESCE(cm_u.first_name,''), ' ',COALESCE(cm_u.last_name,'')) AS user_name"),)
        ->where('elp.location_user_id', '!=', 0)
        ->where('u.deleted', 0)
        ->groupBy('elp.emp_id')
        ->get();

        return $this->successResponse(['lead_ref_by' => $leadRefBDD,'users' => $users,"status" => $leadStatus,"sys_users" => $sysUsers], "success");
    }
    /**
     * Get All Referred By Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchLeadTypeAllDD(Request $request)
    {



        $leadTypesDD = LeadTypesDropdown::orderby('id', 'DESC');

        $leadTypesDD = $leadTypesDD->get(["id", "name"]);
        $leadTypes = [
            ["id" => 0, "name" => "Add New"]
        ];
        if (count($leadTypesDD)) {
            foreach ($leadTypesDD as $leadType) {
                $leadTypes[] = [
                    "id" => $leadType->id,
                    "name" => $leadType->name
                ];
            }
        }
        return $this->successResponse(['lead_type' => $leadTypes], "success");
    }

    /**
     * Store Referred By Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeReferredbydropdowns(Request $request)
    {
        $request->validate([
            'name' => 'required',
            "session_userid" => "required"
        ]);
        ReferredByDropdown::create([
            'name' => $request->name,
            "phone" =>   isset($request->phone) ? $this->sanitizePhoneNumber($request->phone) : null,
            "email" =>   isset($request->email) ? $request->email : null,
            "url" =>     isset($request->url)   ? $request->url : null,
            "notes" =>   isset($request->notes) ? $request->notes : null,
            'created_by' => $request->session_userid
        ]);
        return $this->successResponse([], "success");
    }

    /**
     * update Referred By Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function updateReferredbydropdowns($id, Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $referredByDropdown = ReferredByDropdown::find($id);
        if ($referredByDropdown == null) {
            return $this->errorResponse([], "No Referred by found", 404);
        }
        $referredByDropdown->name = $request->name;
        $referredByDropdown->phone = $this->sanitizePhoneNumber($request->phone);
        $referredByDropdown->email = $request->email;
        $referredByDropdown->url = $request->url;
        if ($request->has('notes')) {
            $referredByDropdown->notes = $request->notes;
        }
        $referredByDropdown->save();
        return $this->successResponse([], "success");
    }

    /**
     * Delete Referred By Dropdowns
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function deleteReferredbydropdowns($id)
    {
        $referredByDropdown = ReferredByDropdown::find($id);
        if ($referredByDropdown == null) {
            return $this->errorResponse([], "No Referred by dropdown found", 404);
        }
        $referredByDropdown->delete();
        return $this->successResponse([], "success");
    }


    /**
     * Get All Lead Types Dropdown
     *
     * @return \Illuminate\Http\Response
     */
    public function getleadTypesdropdowns(Request $request)
    {

        $perPage = $request->has('per_page') && $request->per_page != "" ? $request->get('per_page')  :  $this->cmperPage;
        $leadtypesDropdown = LeadTypesDropdown::orderby('created_at', 'DESC');
        if ($request->has('search')) {
            $leadtypesDropdown = $leadtypesDropdown->where('name', 'like', '%' . $request->search . '%');;
        }
        $leadtypesDropdown = $leadtypesDropdown->paginate($perPage);
        return $this->successResponse(['leadtypes_dropdown' => $leadtypesDropdown], "success");
    }

    /**
     * Store Lead Types Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeLeadTypesdropdowns(Request $request)
    {
        $request->validate([
            'name' => 'required',
            "session_userid" => "required",
        ]);
        LeadTypesDropdown::create([
            'name' => $request->name,
            'created_by' => $request->session_userid,
            'status'=> isset($request->status) ? $request->status : null,
        ]);
        return $this->successResponse([], "success");
    }

    /**
     * update Lead Types Dropdowns
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function updateLeadTypesdropdowns($id, Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $leadtypesDropdown = LeadTypesDropdown::find($id);
        if ($leadtypesDropdown == null) {
            return $this->errorResponse([], "No Lead Types found", 404);
        }
        $leadtypesDropdown->name = $request->name;
        if($request->has('status')){
            $leadtypesDropdown->status= $request->status; 
        }
        $leadtypesDropdown->save();
        return $this->successResponse([], "success");
    }

    /**
     * Delete Lead Types Dropdowns
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function deleteLeadTypesdropdowns($id)
    {

        $leadtypesDropdown = LeadTypesDropdown::find($id);
        if ($leadtypesDropdown == null) {
            return $this->errorResponse([], "No Lead Types Dropdown found", 404);
        }
        $leadtypesDropdown->delete();
        return $this->successResponse([], "success");
    }
}
