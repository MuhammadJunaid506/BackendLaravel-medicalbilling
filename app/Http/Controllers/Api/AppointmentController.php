<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Appointment;
use App\Models\AppointmentLeadMap;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            "lead_id" => "required",
        ]);

        $leadId = $request->lead_id;

        $appointments = AppointmentLeadMap::select(
            "appointments.appointment_title",
            "appointments.appointment_date",
            "appointments.appointment_start_time",
            "appointments.appointment_end_time",
            "appointments.appointemnt_details",
            "appointments.appointemnt_createdby",
            "appointments.appointemnt_updatedby",
            "appointments.created_at",
            "lead_appointment_map.lead_id",
            "lead_appointment_map.appointment_id",
            DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,''))  AS user_name")

        )

            ->join('appointments', "appointments.id", "=", "lead_appointment_map.appointment_id")

            ->join("users", "users.id", "=", "appointments.appointemnt_createdby")

            ->where("lead_appointment_map.lead_id", $leadId)

            ->groupBy("lead_appointment_map.lead_id", "lead_appointment_map.appointment_id")

            ->get();

        // $this->printR($appointments,true);
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

                $status = $this->checkAppointmentStatus($appointment->appointment_date,$appointment->appointment_start_time,$appointment->appointment_end_time);

                $appointment->appointment_status = $status;
            }
        }
        return $this->successResponse(['appointments' => $appointments], 'success');
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
            //"title"         => "required|unique:appointments,appointment_title",
            "title"         => "required",
            "date"          => "required|date_format:Y-m-d",
            "start_time"    => "required",
            "end_time"      => "required",
            "attendees"     => "required",
            "lead_id"       => "required"
        ]);



        $title      = $request->get('title');

        $date       = $request->get('date');

        $startTime  = $request->get('start_time');

        $endTime    = $request->get('end_time');

        $notes      = $request->get('notes');

        $attendees  = $request->get('attendees');

        $attendees = json_decode($attendees, true);

        $sessionUserId = $request->get('session_user_id');

        $leadId = $request->get('lead_id');

        $addAppointment = [
            "appointment_title"         => $title,
            "appointment_date"          => $date,
            "appointment_start_time"    => $startTime,
            "appointment_end_time"      => $endTime,
            "appointemnt_details"       => $notes,
            "appointemnt_createdby"     => $sessionUserId,
            "created_at"                => $this->timeStamp()
        ];

        $appointmentId = Appointment::insertGetId($addAppointment);
        if ($appointmentId) {
            if (count($attendees) > 0) {
                $prepMap = [];
                foreach ($attendees as $attendee) {
                    $prepMapp["lead_id"]         = $leadId;
                    $prepMapp["appointment_id"]  = $appointmentId;
                    $prepMapp["attendee_id"]     = $attendee;
                    array_push($prepMap, $prepMapp);
                }
                // $this->printR($prepMap,true);
                DB::table("lead_appointment_map")->insert($prepMap);
            }
        }
        return $this->successResponse(['appointment_id' => $appointmentId], 'success');
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
        $sessionUserId = $request->get('session_user_id');

        $leadId = $request->get('lead_id');

        // $this->printR($request->all(),true);
        $updateAppointmentData = [];
        if($request->has('title')) {
            $updateAppointmentData["appointment_title"] = $request->get('title');
        }
        if($request->has('date')) {
            $updateAppointmentData["appointment_date"] = $request->get('date');
        }
        if($request->has('start_time')) {
            $updateAppointmentData["appointment_start_time"] = $request->get('start_time');
        }
        if($request->has('end_time')) {
            $updateAppointmentData["appointment_end_time"] = $request->get('end_time');
        }
        if($request->has('notes')) {
            $updateAppointmentData["appointemnt_details"] = $request->get('notes');
        }
        // $this->printR($updateAppointmentData,true);
        //    echo $id;
        //    exit;
        if(count($updateAppointmentData) > 0) {
            $updateAppointmentData["appointemnt_updatedby"] = $sessionUserId;
            $updateAppointmentData["updated_at"]            = $this->timeStamp();
            // dd($updateAppointmentData);

            Appointment::where("id",$id)->update($updateAppointmentData);
        }
        if($request->has('attendees')) {

            $attendees = is_array($request->attendees) ? $request->attendees : json_decode($request->attendees, true);
            if(count($attendees)) {
                //delete already added attendees
                DB::table("lead_appointment_map")
                ->where("lead_id", "=",$leadId)
                ->where("appointment_id", "=",$id)
                ->delete();

                //create the re-mapping of attendees
                $prepMap = [];
                foreach ($attendees as $attendee) {
                    $prepMapp["lead_id"]         = $leadId;
                    $prepMapp["appointment_id"]  = $id;
                    $prepMapp["attendee_id"]     = $attendee;
                    array_push($prepMap, $prepMapp);
                }
                // $this->printR($prepMap,true);
                DB::table("lead_appointment_map")->insert($prepMap);
            }
        }
        return $this->successResponse(['is_update' => true], 'success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        AppointmentLeadMap::where("appointment_id",$id)
        
        ->delete();

        Appointment::where("id",$id)->delete();

        return $this->successResponse(['is_delete' => true], 'success');
        AppointmentLeadMap::where("appointment_id",$id)
        
        ->delete();

        Appointment::where("id",$id)->delete();

        return $this->successResponse(['is_delete' => true], 'success');
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
