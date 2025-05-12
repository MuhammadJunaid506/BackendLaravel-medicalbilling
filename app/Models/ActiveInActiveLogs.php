<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\Utility;
use DB;
class ActiveInActiveLogs extends Model
{
    use HasFactory,Utility;
    private $tbl = "user_ddpracticelocationinfo";
    private $key = "";
    public function __construct() {
        $this->key = env("AES_KEY");
    }
    /**
     * add the activity logs against the practice
     * 
     * 
     * @param $practiceId
     * @param $isActive
     * @param $form
     * @param $to
     * @param $createdBy
     */
    function managePracticeActiveInActiveLogs($practiceId,$isActive,$from,$to,$createdBy,$comment) {
        $practiceLogData = [
            "practice_id" => $practiceId,
            "facility_id" => 0,
            "provider_id" => 0,
            "for_credentialing" => $isActive,
            "role_id" => "9",
            "from"  => $from,
            "to" => $to,
            "comments" => $comment,
            "created_by" => $createdBy

        ];
        return DB::table("active_inactive_logs")->insertGetId($practiceLogData);//add practice active inactive log
        // $connectedLocations = $this->fetchData($this->tbl,["user_parent_id" => $practiceId],0,["user_id"]);
        // foreach($connectedLocations  as $location) {
        //     $facilityLogData = [
        //         "practice_id" => $practiceId,
        //         "facility_id" => $location->user_id,
        //         "provider_id" => 0,
        //         "for_credentialing" => $isActive,
        //         "role_id" => "3",
        //         "from"  => $from,
        //         "to" => $to,
        //         "comments" => $comment,
        //         "created_by" => $createdBy
    
        //     ];
        //     $this->addData("active_inactive_logs",$facilityLogData,0);//add facility active inactive log
        //     $connectedProviders = $this->fetchData("individualprovider_location_map",["location_user_id" => $location->user_id],0,["user_id"]);
        //     if(count($connectedProviders)) {
        //         foreach($connectedProviders as $provider) {

        //             $providerLogData = [
        //                 "practice_id" => $practiceId,
        //                 "facility_id" => $location->user_id,
        //                 "provider_id" => $provider->user_id,
        //                 "for_credentialing" => $isActive,
        //                 "role_id" => "4",
        //                 "from"  => $from,
        //                 "to" => $to,
        //                 "comments" => $comment,
        //                 "created_by" => $createdBy
        //             ];
        //             $this->addData("active_inactive_logs",$providerLogData,0);//add provider active inactive log
        //         }
        //     }
        // }

    }
    /**
     * add the activity logs against the location
     * 
     * 
     *  @param $practiceId
     *  @param $facilityId
     *  @param $isActive
     *  @param $from
     *  @param $to
     *  @param $createdBy
     */
    function manageLocationActiveInactivityLog($practiceId, $facilityId, $isActive,$from,$to,$createdBy,$comment) {
        $facilityLogData = [
            "practice_id" => $practiceId,
            "facility_id" => $facilityId,
            "provider_id" => 0,
            "for_credentialing" => $isActive,
            "role_id" => "3",
            "from"  => $from,
            "to" => $to,
            "comments" => $comment,
            "created_by" => $createdBy,
            "created_at" => date("Y-m-d H:i:s")
        ];
        return $this->addData("active_inactive_logs",$facilityLogData,0);//add facility active inactive log

        // $connectedProviders = $this->fetchData("individualprovider_location_map",["location_user_id" => $facilityId],0,["user_id"]);
        //     if(count($connectedProviders)) {
        //         foreach($connectedProviders as $provider) {

        //             $providerLogData = [
        //                 "practice_id" => $practiceId,
        //                 "facility_id" => $facilityId,
        //                 "provider_id" => $provider->user_id,
        //                 "for_credentialing" => $isActive,
        //                 "role_id" => "4",
        //                 "from"  => $from,
        //                 "to" => $to,
        //                 "comments" => $comment,
        //                 "created_by" => $createdBy
        //             ];
        //             $this->addData("active_inactive_logs",$providerLogData,0);//add provider active inactive log
        //         }
        //     }
    }

    /**
     * add the activity logs against the provider
     * 
     * 
     *  @param $practiceId
     *  @param $facilityId
     *  @param $providerId
     *  @param $isActive
     *  @param $from
     *  @param $to
     *  @param $createdBy
     */
    function manageProviderActivityLogs($providerId, $isActive,$from,$to,$createdBy,$comment) {
        $tbl = $this->tbl;
        $locationsInfo = DB::table("individualprovider_location_map")
        ->select("individualprovider_location_map.location_user_id as facility_id","$tbl.user_parent_id as practice_id",'individualprovider_location_map.user_id as provider_id')
        ->join("$tbl","$tbl.user_id","=","individualprovider_location_map.location_user_id")
        ->where("individualprovider_location_map.user_id",'=',$providerId)
        ->get();

        // echo "<pre>";print_r($locationsInfo); echo "</pre>";
        // exit;
        if(count($locationsInfo)) {
            foreach($locationsInfo as $loc) {
                $providerLogData = [
                    "practice_id" => $loc->practice_id,
                    "facility_id" => $loc->facility_id,
                    "provider_id" => $providerId,
                    "for_credentialing" => $isActive,
                    "role_id" => "4",
                    "from"  => $from,
                    "to" => $to,
                    "comments" => $comment,
                    "created_by" => $createdBy,
                    "created_at" => date("Y-m-d H:i:s")
                ];
                $this->addData("active_inactive_logs",$providerLogData,0);//add provider active inactive log
            }
        }
        
    }
    /**
     * add the activity logs against the location
     * 
     * 
     *  @param $practiceId
     *  @param $facilityId
     *  @param $providerId
     *  @param $isActive
     *  @param $from
     *  @param $to
     *  @param $createdBy
     */
    function manageSpecificLocationActiveInactivityLog($practiceId, $facilityId, $isActive,$from,$to,$createdBy,$comment) {
        $facilityLogData = [
            "practice_id" => $practiceId,
            "facility_id" => $facilityId,
            "provider_id" => 0,
            "for_credentialing" => $isActive,
            "role_id" => "3",
            "from"  => $from,
            "to" => $to,
            "comments" => $comment,
            "created_by" => $createdBy
        ];
        // print_r($facilityLogData);
        // exit;
        $this->addData("active_inactive_logs",$facilityLogData,0);//add provider active inactive log
        $connectedProviders = $this->fetchData("individualprovider_location_map",["location_user_id" => $facilityId],0,["user_id"]);
        // print_r($connectedProviders);
        // exit;
        if(count($connectedProviders)) {
            foreach($connectedProviders as $provider) {

                $providerLogData = [
                    "practice_id" => $practiceId,
                    "facility_id" => $facilityId,
                    "provider_id" => $provider->user_id,
                    "for_credentialing" => $isActive,
                    "role_id" => "4",
                    "from"  => $from,
                    "to" => $to,
                    "comments" => $comment,
                    "created_by" => $createdBy
                ];
                $this->addData("active_inactive_logs",$providerLogData,0);//add provider active inactive log
            }
        }
    }
    /**
     * add the activity logs against the provider
     * 
     * 
     *  @param $practiceId
     *  @param $facilityId
     *  @param $providerId
     *  @param $isActive
     *  @param $from
     *  @param $to
     *  @param $createdBy
     */
    function manageSpecificProviderActivityLogs($recId, $isActive,$from,$to,$createdBy,$comment) {
        $tbl = $this->tbl;
        $locationsInfo = DB::table("individualprovider_location_map")
        ->select("individualprovider_location_map.location_user_id as facility_id","$tbl.user_parent_id as practice_id",'individualprovider_location_map.user_id as provider_id')
        ->join("$tbl","$tbl.user_id","=","individualprovider_location_map.location_user_id")
        ->where("individualprovider_location_map.id",'=',$recId)
        ->get();

        // echo "<pre>";print_r($locationsInfo); echo "</pre>";
        // exit;
        if(count($locationsInfo)) {
            foreach($locationsInfo as $loc) {
                $providerLogData = [
                    "practice_id" => $loc->practice_id,
                    "facility_id" => $loc->facility_id,
                    "provider_id" => $loc->provider_id,
                    "for_credentialing" => $isActive,
                    "role_id" => "4",
                    "from"  => $from,
                    "to" => $to,
                    "comments" => $comment,
                    "created_by" => $createdBy
                ];
                $this->addData("active_inactive_logs",$providerLogData,0);//add provider active inactive log
            }
        }
        
    }
}
