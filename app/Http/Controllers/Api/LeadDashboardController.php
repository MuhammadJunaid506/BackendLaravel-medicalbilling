<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Appointment;
use App\Models\AppointmentLeadMap;
use Carbon\Carbon;
class LeadDashboardController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * get the dashboard data of the leads
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchLeadBasicDashboard(Request  $request) {
        
        $noOfLead = DB::table("leads")->count();

        $noOfWonLead = DB::table("leads")
        
        ->join("lead_status","lead_status.id","=","leads.status_id")
        
        ->where("lead_status.status","=","Won")
        
        ->count();
        
        $convertedLeadPerc = round(($noOfWonLead / $noOfLead) * 100,2);

        return $this->successResponse([
            "total_leads"               => $noOfLead,
            "total_converted_leads"     => $noOfWonLead,
            'converted_leads_pecentage' => $convertedLeadPerc
        ],"success");
    }
    /**
     * get the dashboard data of the leads for graphs
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchLeadGraphDashboard(Request  $request) {
        
        $referredByData = DB::table("leads")
        
        ->select(DB::raw("COUNT(id) as referred_by_count"),"referral as referred_by")
        
        ->groupBy("referral")
        
        ->whereNotNull("referral")
        
        ->get();

        $statusWiseData = DB::table("leads")
        
        ->select(DB::raw("COUNT(cm_leads.id) as status_count"),"lead_status.status")

        ->join("lead_status","lead_status.id","=","leads.status_id")
        
        ->groupBy("lead_status.status")
        
        ->get();

        $wonByUserData = DB::table("leads")
        
        ->select(DB::raw("COUNT(cm_leads.id) as user_count"), DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,''))  AS user_name"))

        ->join("users","users.id","=","leads.created_by")
        
        ->join("lead_status","lead_status.id","=","leads.status_id")
        
        ->where("lead_status.status","=","Won")

        ->groupBy("leads.created_by")
        
        ->get();

        return $this->successResponse([
            "referred_by_data"              => $referredByData,
            "status_wise_data"              => $statusWiseData,
            'won_by_user_data'              => $wonByUserData
        ],"success");
    }
     /**
     * get the dashboard data of the leads's appointments
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchLeadsAppointmentsDashboard(Request $request) {
        $key = env("AES_KEY");
        
        $today = Carbon::today()->format("Y-m-d");
        
        $appointments = AppointmentLeadMap::select(
            "appointments.appointment_title",
            "appointments.appointment_date",
            "appointments.appointment_start_time",
            "appointments.appointment_end_time",
            "appointments.appointemnt_createdby",
            "lead_appointment_map.lead_id",
            "lead_appointment_map.appointment_id",
            DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,''))  AS lead_owner"),
            DB::raw("AES_DECRYPT(cm_leads.company_name,'$key') as company_name")

        )

            ->join('appointments', "appointments.id", "=", "lead_appointment_map.appointment_id")

            ->join("users", "users.id", "=", "appointments.appointemnt_createdby")

            ->join("leads", "leads.id", "=", "lead_appointment_map.lead_id")
            
            ->where("appointments.appointment_date",">=",$today)

            ->groupBy("lead_appointment_map.lead_id", "lead_appointment_map.appointment_id")
            
            ->orderBy("appointments.appointment_date","ASC")

            ->get();
       
        // dd($appointments);
        if ($appointments->count() > 0) {
            foreach ($appointments as $appointment) {
                // $this->printR($appointment,true);
                $appointmentId  = $appointment->appointment_id;

                $leadId         = $appointment->lead_id;

                // echo $leadId;
                // echo PHP_EOL;
                // echo $appointmentId;
                // exit;
                $attendees = AppointmentLeadMap::select("lead_appointment_map.attendee_id as user_id", DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,''))  AS user_name"))

                    ->join("users", "users.id", "=", "lead_appointment_map.attendee_id")

                    ->where("lead_appointment_map.lead_id", "=", $leadId)

                    ->where("lead_appointment_map.appointment_id", "=", $appointmentId)

                    ->get();

                // $this->printR($attendees,true);
                $appointment->attendees = $attendees;
                try {
                    $appointment->appointment_ts = Carbon::createFromFormat('Y-m-d H:i:s', $appointment->appointment_date . ' ' . $appointment->appointment_start_time);
                }
                catch (\Exception $e) {
                    $appointment->appointment_ts = null;
                }
                $status = $this->checkAppointmentStatus($appointment->appointment_date,$appointment->appointment_start_time,$appointment->appointment_end_time);

                $appointment->appointment_status = $status;
            }
            $appointments  = $this->stdToArray($appointments);
            // Assuming your multidimensional array is stored in a variable named $appointments
            $appointments = collect($appointments)->filter(function ($appointment) {
                // Access the appointment_status and expired_at properties (assuming those names)
                $appointmentStatus = $appointment['appointment_status'];
                
                // Check if the appointment status is set to 'expired' (or your desired value)
                // and the expired_at date is before the current date and time
                return $appointmentStatus !== 'expired';
            })->values() -> toArray();

            $sortedCollection = collect($appointments)->sortBy(function ($item, $key) {
                // Option 1: Using strtotime (might have compatibility issues)
                // return strtotime($item['timestamp']);
              
                // Option 2: Using Carbon (recommended)
                return Carbon::parse($item['appointment_ts'])->timestamp;
              })->values() -> toArray();;

        }

        return $this->successResponse([
            "appointments_dashboard" => $sortedCollection
        ],"success");
    }
    /**
     * check the appointement deadline
     * 
     * @param $appointmentDate
     * @param $appointmentStartTime
     * @param $appointmentEndTime
     * @return {string} status
     */
    private function checkAppointmentStatus($appointmentDate, $appointmentStartTime, $appointmentEndTime)
    {
        try {
            $currentDateTime = Carbon::now();
            $appointmentDateTimeStart = Carbon::createFromFormat('Y-m-d H:i:s', $appointmentDate . ' ' . $appointmentStartTime);
            $appointmentDateTimeEnd = Carbon::createFromFormat('Y-m-d H:i:s', $appointmentDate . ' ' . $appointmentEndTime);
            // Get the user-given date (assuming it's stored in a variable named $userDate)
            $appointmentDateObj = Carbon::parse($appointmentDate);
            // Get the current date
            $currentDate = Carbon::today();

            if ($currentDateTime->greaterThanOrEqualTo($appointmentDateTimeEnd)) {
                return 'expired';
            }
            elseif (!$currentDateTime->between($appointmentDateTimeStart, $appointmentDateTimeEnd) && $currentDate->isSameDay($appointmentDateObj)) {
                return "today";
            } 
            elseif ($currentDateTime->between($appointmentDateTimeStart, $appointmentDateTimeEnd) && $currentDate->isSameDay($appointmentDateObj)) {
                return 'active session';
            } else {
                return 'upcoming';
            }
        }
        catch (\Exception $e) {
            return null;
        }
    }
}
