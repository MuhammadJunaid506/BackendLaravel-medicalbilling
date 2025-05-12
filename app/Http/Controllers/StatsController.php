<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Provider;
use App\Models\User;
use App\Models\Contract;
use App\Models\DiscoveryDocument;
use App\Models\UserProfile;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\EditImage;
use App\Mail\ContractAndDiscoveryDocument;
use PDF;
use App\Models\W9form;
use App\Models\Insurance;
use App\Models\Invoice;
use App\Models\ProviderMember;
use App\Http\Controllers\Api\DiscoverydocumentController;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\StatesCities;
use Mail;
use App\Mail\ProviderCredentials;
use App\Models\ProviderCompanyMap;
use App\Models\Stats;

class StatsController extends Controller
{
    use ApiResponseHandler, Utility;
    private $tbl = "user_ddpracticelocationinfo";
    private $key = "";
    public function __construct()
    {
        $this->key = env("AES_KEY");
        
    }
     /**
     * Display a listing of the enrollemnet.
     *@param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enrollmentStats(Request $request) {
        $tbl = $this->tbl;
        $key = $this->key;
        $enrollment = [];
        $userId = $request->user_id;
        $sqlQuery = "SELECT 						
                            (
                                CASE WHEN T.status_id = '0'
                                THEN 'Not started'
                                WHEN T.status_id = '2'
                                THEN 'In Progess'
                                WHEN T.status_id = '3'
                                THEN 'Completed'
                                ELSE 'Rejected'
                                END
                            ) AS status, 	
                            COUNT(T.status_id) as total_enrollements
                    FROM(
                        SELECT 						
                                (
                                    CASE WHEN ct.user_parent_id = '0'
                                    THEN NULL
                                    ELSE (SELECT CONCAT(first_name, ' ', last_name) FROM cm_users WHERE id = ct.user_id)
                                    END
                                ) AS provider,
                                (
                                    CASE WHEN ct.user_parent_id = '0'
                                    THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM cm_$tbl WHERE user_id = ct.user_id)
                                    ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM cm_$tbl WHERE user_id = ct.user_parent_id)
                                    END
                                ) AS practice, 
                
                                (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as Payer, 

                                (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as Status,

                                (   CASE WHEN ct.user_parent_id = '0'
                                    THEN NULL
                                    ELSE ct.user_id
                                    END
                                ) AS individual_id,
                
                                (
                                    CASE WHEN ct.user_parent_id = '0'
                                    THEN ct.user_id
                                    ELSE ct.user_parent_id
                                    END
                                ) AS facility_id,

                                ct.credentialing_status_id as status_id
                
                    FROM `cm_credentialing_tasks` ct
                ) AS T
                WHERE T.individual_id = '$userId' OR T.facility_id = '$userId'
        ";
        
        $allCount = $this->rawQuery($sqlQuery);
       
        $sqlQuery.="AND T.status_id IN(0,2,3,5)
        GROUP BY T.status_id";
        $otherCount = $this->rawQuery($sqlQuery);
        // $this->printR($otherCount,true);
        // $sql1 = "SELECT count(*) as total_enrollements FROM `cm_credentialing_tasks` WHERE (user_id='$userId' or user_parent_id='$userId')";
        // $sql2 = "SELECT count(*) as enrollements_completed FROM `cm_credentialing_tasks` WHERE (user_id='$userId' or user_parent_id='$userId') and credentialing_status_id = '3'";
        // $sql3 = "SELECT count(*) as inprogress_enrollements FROM `cm_credentialing_tasks` WHERE (user_id='$userId' or user_parent_id='$userId') and credentialing_status_id = '2'";
        // $sql4 = "SELECT count(*) as notstarted_enrollements FROM `cm_credentialing_tasks` WHERE (user_id='$userId' or user_parent_id='$userId') and credentialing_status_id = '0'";
        // $sql5 = "SELECT count(*) as rejected_enrollements FROM `cm_credentialing_tasks` WHERE (user_id='$userId' or user_parent_id='$userId') and credentialing_status_id = '5'";
        
        // $all        = $this->rawQuery($sql1);
        $completed  = 0 ;
        $inprogress = 0 ;
        $notstarted = 0 ;
        $rejected   = 0;
        if(count($otherCount)) {
            foreach($otherCount as $eachCount) {
                //$this->printR($eachCount,true);
                if($eachCount->status == "Not started") {
                    $notstarted = $eachCount->total_enrollements;
                }
                elseif($eachCount->status == "In Progess") {
                        $inprogress = $eachCount->total_enrollements;
                }
                elseif($eachCount->status == "Completed") {
                    $completed  = $eachCount->total_enrollements;
                }
                elseif($eachCount->status == "Rejected") {
                    $rejected  = $eachCount->total_enrollements;
                }
            }
        }
        return $this->successResponse(['all' => $allCount[0]->total_enrollements,'completed' => $completed,'inprogress' => $inprogress,'notstarted' => $notstarted,'rejected' => $rejected], "success");
    }
    /**
     * fetch each status stats.
     *@param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function statusEnrollmentStats(Request $request) {
        
        $tbl = $this->tbl;
        $key = $this->key;
        $page = $request->has("page") ? $request->page : 1;
        
        $perPage = $this->cmperPage;

        $enrollment = [];
        $userId = $request->user_id;
        // $profile = User::find($userId);
        $status = $request->status;
        if($status == "not_started") {
            $status = $this->fetchData("credentialing_status", ["credentialing_status" => "New/Not Initiated"],1);
        }
        if($status == "in_progress") {
            $status = $this->fetchData("credentialing_status", ["credentialing_status" => "In Process"],1);
        }
        if($status == "completed") {
            $status = $this->fetchData("credentialing_status", ["credentialing_status" => "Approved"],1);
        }
        if($status == "rejected") {
            $status = $this->fetchData("credentialing_status", ["credentialing_status" => "Rejected"],1);
        }
        $where2 = "";
        if($request->status != "0") {
           $where2= " AND T.status_id='$status->id'";
        }
        $sql = "SELECT *
        FROM(
            SELECT 					
                (
                    CASE WHEN ct.user_parent_id = '0'
                    THEN NULL
                    ELSE (SELECT CONCAT(first_name, ' ', last_name) FROM cm_users WHERE id = ct.user_id)
                    END
                ) AS provider,
                (
                    CASE WHEN ct.user_parent_id = '0'
                    THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM cm_$tbl WHERE user_id = ct.user_id)
                    ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM cm_$tbl WHERE user_id = ct.user_parent_id)
                    END
                ) AS practice, 
                                    
                (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer, 
            
                (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as status,
    
                (   CASE WHEN ct.user_parent_id = '0'
                    THEN NULL
                    ELSE ct.user_id
                    END
                ) AS individual_id,
                                    
                (
                    CASE WHEN ct.user_parent_id = '0'
                    THEN ct.user_id
                    ELSE ct.user_parent_id
                    END
                ) AS facility_id,
    
                ct.credentialing_status_id as status_id
                                    
            FROM `cm_credentialing_tasks` ct
        ) AS T
        WHERE (T.individual_id = '$userId' OR T.facility_id = '$userId') ".$where2;

         $enrollment = $this->rawQuery($sql);
         $totalRec = count($enrollment);
         $numOfPage = ceil($totalRec/$perPage); 
         if($page > $numOfPage) {
            $offset = $page;
            $stats = [];
            $pagination = $this->makePagination($page,$perPage,$offset,$totalRec);

        }
        else {        
            $offset = $page - 1;
            
            $pagination = $this->makePagination($page,$perPage,$offset,$totalRec);
            $newOffset = $perPage * $offset;
            $stats = [];
            if($offset <= $pagination["last_page"] ) {
                $sql .=" LIMIT $perPage OFFSET $newOffset";

                $stats = $this->rawQuery($sql);
            }
        }
        return $this->successResponse(['enrollment' => $stats,'pagination' => $pagination], "success");
    }
    /**
     * Enrollment facilities
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLocationEnrollmentDataOld(Request $request) {
        $statsObj = new Stats();
        $locationId = $request->location_id;
        $enrollments = $statsObj->fetchEnrollmentsPractices($locationId);
        // $this->printR($enrollments,true);
        $resJSON = [];
        $resJSON['practice'] = $enrollments;
        if(count($enrollments) > 0 ) {
            foreach($enrollments as $enrollment) {
                $facility = $statsObj->fetchEnrollmentsFacility($enrollment->practice_id);
                $resJSON['facility'][$enrollment->practice_id] = $facility;
                // $this->printR($facility,true);
                if(count($facility)) {
                    foreach($facility as $facil) {
                        $provider = $statsObj->fetchEnrollmentsProviders($facil->facility_id);
                        $resJSON['provider'][$facil->facility_id] = $provider;
                    }
                }
            }
        }
        $statsObj = NULL;
        return $this->successResponse(['enrollment_data' => $resJSON], "success");
       
    }
    /**
     * Enrollment facilities
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLocationEnrollmentData(Request $request) {
        $statsObj = new Stats();
        $locationId = $request->location_id;
        $facility = $statsObj->fetchFacilityEnrollments($locationId);
        $resJSON['facility'] = $facility;
        // $this->printR($facility,true);
        if(count($facility)) {
            foreach($facility as $facil) {
                $provider = $statsObj->fetchEnrollmentsProviders($facil->facility_id);
                $resJSON['provider'][$facil->facility_id] = $provider;
            }
        }
        $statsObj = NULL;
        return $this->successResponse(['enrollment_data' => $resJSON], "success");
       
    }
    /**
     * Enrollment facilities / provider / enrollment
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function facilityProviderEnrollments(Request $request) {
        $request->validate([
            "provider_id" => "required",
            "facility_id" => "required",
        ]);
        
        $providerId = $request->provider_id;
        
        $facilityId = $request->facility_id;

        $statsObj = new Stats();
        
        $enrollmentRes = $statsObj->fetchProviderEnrollment($facilityId,$providerId);
        
        $statsObj = NULL;
        $status = $this->fetchData("credentialing_status");
        return $this->successResponse(['enrollment_res' => $enrollmentRes,"all_status" => $status], "success");
      

    }
    /**
     * Enrollment  provider
     * 
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function providerEnrollment(Request $request) {
        
        $request->validate([
            "provider_id"       => "required",
            "session_userid"    => "required"
        ]);
        $providerId = $request->provider_id;
        $sessionUserId = $request->session_userid;
        $resJSON = [];
        
        $statsObj = new Stats();

        $providerPractice = $statsObj->providerPractices($providerId, $sessionUserId);
        
        $resJSON['practices'] = $providerPractice;

         if (count($providerPractice)) {
            
            foreach($providerPractice as $practice) {
              
                $providerFacility = $statsObj->providerFacilities($providerId,$practice->practice_id,$sessionUserId);
                
                $resJSON['facility'][$practice->practice_id] = $providerFacility;
                
                if(count($providerFacility)) {
                    
                    foreach($providerFacility as $facility) {
                        
                        $facilityId = $facility->facility_id;
                        
                        $providerEnrollment = $statsObj->providerEnrollment($facilityId,$providerId);
                        
                        $resJSON['enrollment'][$facilityId] = $providerEnrollment;
                    }
                }
            }
         }
        
        $statsObj = NULL;
        $status = $this->fetchData("credentialing_status");
        return $this->successResponse(['enrollment_res' => $resJSON,"all_status" => $status], "success");
        
    }
    /**
     * Enrollment paginate results
     * 
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function enrollmentPaginate(Request $request) {
        
        $statsObj = new Stats();

        $facilityId = $request->facility_id;

        $providerId = $request->provider_id;
        
        $data = $statsObj->providerEnrollment($facilityId,$providerId);
        
        return $this->successResponse(['enrollment_res' => $data], "success");

        $statsObj= NULL;
    }
}
