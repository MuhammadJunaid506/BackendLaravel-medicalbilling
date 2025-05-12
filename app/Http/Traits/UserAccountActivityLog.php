<?php

namespace App\Http\Traits;

use DB;
use Mail;
use GeoIP;
use Agent;

trait UserAccountActivityLog {
        // Helper method to get browser name
        function getBrowserName($userAgent)
        {
            
            $browser = Agent::browser();
            return $browser;
        }
        // Helper method to get browser name
        function getBrowserVersion($userAgent)
        {
            
            $browser = Agent::browser();
            $version = Agent::version($browser);
            return $version;
        }
        // Helper method to get device name
        function getDeviceName($userAgent)
        {
            $deviceName = 'Unknown';

            if(Agent::isPhone())
                $deviceName = Agent::device();

            return $deviceName;
        }
        // Helper method to get operating system
        function getOperatingSystem($userAgent)
        {
           
            $operatingSystem = Agent::platform();

            return $operatingSystem;
        }
        // Helper method to get operating system
        function getOperatingSystemVersion($userAgent)
        {   
            $operatingSystem = Agent::platform();
            $version = Agent::version($operatingSystem);
            return $version;
        }
         // Helper method to get operating system
         function getRobotActivity($userAgent)
         {
            $robot = 'Unknown';
 
            if(Agent::isRobot())
                $robot = Agent::robot();
            
            return $robot;
         }
        /**
         * add the login activity log
         * 
         * @param $request
         * @param $userId
         */
        function addLoginActivityLog($userId,$request,$timeStamp,$sessionBuildAt=NULL,$sessionExpiredAt=NULL) {

            $addLogData = [];

            $ip = $request->header('X-Forwarded-For');//this is the key for getting the ip address

            $userAgent = $request->header('User-Agent');

            $os = $this->getOperatingSystem($userAgent);

            $deviceName = $this->getDeviceName($userAgent);

            $browserName = $this->getBrowserName($userAgent);
            
            $browserVersion = $this->getBrowserVersion($userAgent);

            $osVersion = $this->getOperatingSystemVersion($userAgent);

            $robot = $this->getRobotActivity($userAgent);
            
            $addLogData['user_id']      = $userId;
            $addLogData['os']           = $os;
            $addLogData['os_version']   = $osVersion;
            $addLogData['device']       = $deviceName;
            $addLogData['browser']      = $browserName;
            $addLogData['browser_version']  = $browserVersion;
            $addLogData['robot']  = $robot;
            $addLogData['ip']       = $ip;
            $addLogData['session_buid_at']       = $sessionBuildAt;
            $addLogData['session_expired_at']       = $sessionExpiredAt;

            $userLocation = GeoIP::getLocation($ip);

            if(is_object($userLocation)) {
                $addLogData['iso_code']     = $userLocation->iso_code;
                $addLogData['country']      = $userLocation->country;
                $addLogData['city']         = $userLocation->city;
                $addLogData['state']        = $userLocation->state;
                $addLogData['state_name']   = $userLocation->state_name;
                $addLogData['postal_code']  = $userLocation->postal_code;
                $addLogData['lat']          = $userLocation->lat;
                $addLogData['lon']          = $userLocation->lon;
                $addLogData['timezone']     = $userLocation->timezone;
                $addLogData['continent']    = $userLocation->continent;
            }
            $addLogData['created_at'] = $timeStamp;
            return DB::table("user_accountactivity")->insertGetId($addLogData);
        }
        /**
         * handle user account activities and actions
         * 
         * @param $userId
         * @param $loggedBy
         * @param $effectedComponent
         * @param $action
         * @param $actionData
         * @param $createdAt
         * @param $updatedAt
         */
        function handleUserActivity(
            $userId, $loggedBy, $effectedComponent, $action, 
            $actionData, $createdAt, $updatedAt) {
                if(strlen($actionData) > 2 ) {
                    return DB::table('system_logs')
                    ->insertGetId([
                        'user_id'               => $userId,
                        'logged_by'             => $loggedBy,
                        'effected_component'    => $effectedComponent,
                        'action'                => $action,
                        'action_data'           => $actionData,
                        'created_at'            => $createdAt,
                        'updated_at'            => $updatedAt
                    ]);
                }
        }
        /**
         * fetch the app activity data
         */
        function fetchAppActivityData($page,$perPage) {
            
           
            $offset = $page - 1;
            
            $newOffset = $perPage * $offset;

            return DB::table('system_logs')
            
            ->select("system_logs.effected_component","system_logs.action_data","system_logs.action", 
            DB::raw('DATE_FORMAT(cm_system_logs.updated_at, "%r") as updated_time'),
            DB::raw('DATE_FORMAT(cm_system_logs.created_at, "%r") as created_time'),
            DB::raw('DATE_FORMAT(cm_system_logs.created_at, "%m/%d/%Y") as created_date'),
            DB::raw('DATE_FORMAT(cm_system_logs.updated_at, "%m/%d/%Y") as updated_date'),
            DB::raw("CONCAT(cm_usr.first_name,' ',cm_usr.last_name) AS session_user"),
            DB::raw("CONCAT(cm_u.first_name,' ',cm_u.last_name) AS effected_user")
            )
            
            ->join('users as u', 'u.id','=','system_logs.user_id')
            
            ->join('users as usr', 'usr.id','=','system_logs.logged_by')

            ->offset($newOffset)
            
            ->limit($perPage)
            
            ->get();
        }
}
?>