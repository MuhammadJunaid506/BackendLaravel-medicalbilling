<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use Illuminate\Support\Facades\DB;

class LeadReportController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * create the comprehensive report for lead
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function leadComprehensiveReport(Request $request) {
        
        $request->validate([
            'created_date_range' => 'required_without:follow_up_range',
            'follow_up_range' => 'required_without:created_date_range',
        ]);
        
        
        $key = $this->key;

        $leadReport = DB::table("leads")
        
        ->select( 
            DB::raw("AES_DECRYPT(cm_leads.email,'$key') as email"),
            DB::raw("AES_DECRYPT(cm_leads.phone,'$key') as phone"),
            DB::raw("AES_DECRYPT(cm_leads.company_name,'$key') as company_name"),
            'ls.status as status_name',"leads.last_followup","leads.speciality",
            "leads.id","leads.created_by",
            DB::raw("(SELECT AES_DECRYPT(details,'$key') FROM cm_lead_logs WHERE lead_id = cm_leads.id ORDER BY id DESC LIMIT 0,1) as comment")
        )
        
        ->join('lead_status as ls','ls.id','=','leads.status_id','left');
        
        if($request->has("created_date_range")) {
            $dateRange = json_decode($request->created_date_range,true);
            // $this->printR($dateRange,true);
            $fromDate   = $dateRange["start_date"]." 00:00:00";
    
            $toDate     = $dateRange["end_date"]." 23:59:59";

            $leadReport = $leadReport->whereBetween("leads.created_at",[$fromDate, $toDate]);
        }
        if($request->has("follow_up_range")) {
            $dateRange = json_decode($request->follow_up_range,true);
            // $this->printR($dateRange,true);
            $fromDate   = $dateRange["start_date"];
    
            $toDate     = $dateRange["end_date"];

            $leadReport = $leadReport->whereBetween("leads.last_followup",[$fromDate, $toDate]);
        }
        if($request->has("refer_by")) {
            
            $referBy = json_decode($request->refer_by,true);
            
            $refByname = DB::table("referredby_dropdowns")
            
            ->whereIn("id",$referBy)
            
            ->pluck("name")
            
            ->toArray();
            
            $leadReport = $leadReport->whereIn("leads.referral",$refByname);
        }
        if($request->has("status")) {
            
            $status = json_decode($request->status,true);
            
            $leadReport = $leadReport->whereIn("leads.status_id",$status);
        }
        if($request->has("users")) {
            // print_r($request->users);
            // exit;
            $users = json_decode($request->users,true);
            
            $leadReport = $leadReport->whereIn("leads.created_by",$users);
        }
        
        $leadReport = $leadReport->whereRaw("AES_DECRYPT(cm_leads.company_name,'$key') NOT LIKE '%TEST%'")

        ->orderBy('leads.last_followup',"DESC")
        
        ->get();

        // $this->printR($leadReport);
        return $this->successResponse(["report" => $leadReport],"success");

    }
}
