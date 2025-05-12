<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\Utility;
use Illuminate\Support\Facades\DB;
class Stats extends Model
{
    use HasFactory,Utility;

    private $key = "";
    private $tbl = "user_ddpracticelocationinfo";
    private $tblU = "users";
    public function __construct() {
        $this->key = env("AES_KEY");
    }
    /**
     * fet the enrollment Practices
     * 
     * @param $facilityId
     */
    function fetchEnrollmentsPractices($facilityId) {
        /*$tbl = "cm_".$this->tbl;
        $key = $this->key;

        $sql = "SELECT user_parent_id as practice_id, AES_DECRYPT(doing_buisness_as,'$key') as practice_name, '1' as is_expandable
        FROM $tbl
        WHERE user_id = $facilityId
        ORDER BY user_parent_id, user_id";
        return $this->rawQuery($sql);
        /**/
        return DB::table('user_ddpracticelocationinfo AS pli')
        
        ->select('pli.user_parent_id AS practice_id', 'ubp.practice_name', DB::raw("'1' AS is_expandable"))
        
        ->join('user_baf_practiseinfo AS ubp', 'ubp.user_id', '=', 'pli.user_parent_id')
        
        ->where('pli.user_id', '=', $facilityId)
        
        ->orderBy('pli.user_parent_id')
        
        ->get();
    }
    /**
     * fet the enrollment Facilities
     * 
     * @param $facilityId
     */
    function fetchEnrollmentsFacility($practiceId) {
        $tbl = $this->tbl;
        $key = $this->key;
        /*$sql = "SELECT T.facility_id, T.facility_name, if(T.is_expandable > 0, '1', '0') is_expandable
        FROM(
        SELECT user_id as facility_id, AES_DECRYPT(practice_name,'$key') as facility_name,
        (SELECT COUNT(user_id) FROM cm_individualprovider_location_map WHERE location_user_id = $facilityId GROUP BY location_user_id) as is_expandable
        FROM $tbl
        WHERE user_parent_id = $facilityId
        AND user_id = $facilityId
        ) AS T";
        return $this->rawQuery($sql);*/
        $result = DB::table($tbl)
            ->select('user_id as facility_id', DB::raw("AES_DECRYPT(practice_name, '$key') as facility_name"), 
            DB::raw("(SELECT COUNT(user_id) FROM cm_individualprovider_location_map WHERE location_user_id = cm_$tbl.user_id GROUP BY location_user_id) as is_expandable"))
            ->where('user_parent_id', $practiceId)
            //->where('user_id', $facilityId)
            ->get();

        // Converting to Laravel Collection
        $result = collect($result)->map(function($item) {
            $item->is_expandable = $item->is_expandable > 0 ? '1' : '0';
            return $item;
        });
        return $result;
    }
    /**
     * fet the enrollment Facilities
     * 
     * @param $facilityId
     */
    function fetchFacilityEnrollments($facilityId) {
        $tbl = $this->tbl;
        $key = $this->key;
        /*$sql = "SELECT T.facility_id, T.facility_name, if(T.is_expandable > 0, '1', '0') is_expandable
        FROM(
        SELECT user_id as facility_id, AES_DECRYPT(practice_name,'$key') as facility_name,
        (SELECT COUNT(user_id) FROM cm_individualprovider_location_map WHERE location_user_id = $facilityId GROUP BY location_user_id) as is_expandable
        FROM $tbl
        WHERE user_parent_id = $facilityId
        AND user_id = $facilityId
        ) AS T";
        return $this->rawQuery($sql);*/
        $result = DB::table($tbl)
            ->select('user_id as facility_id', DB::raw("AES_DECRYPT(practice_name, '$key') as facility_name"), 
            DB::raw("(SELECT COUNT(user_id) FROM cm_individualprovider_location_map WHERE location_user_id = cm_$tbl.user_id GROUP BY location_user_id) as is_expandable"))
            //->where('user_parent_id', $practiceId)
            ->where('user_id', $facilityId)
            ->get();

        // Converting to Laravel Collection
        $result = collect($result)->map(function($item) {
            $item->is_expandable = $item->is_expandable > 0 ? '1' : '0';
            return $item;
        });
        return $result;
    }
     /**
     * fetch the enrollment Facilitty providers
     * 
     * @param $facilityId
     */
    function fetchEnrollmentsProviders($facilityId) {
        /*$sql = "SELECT T.*
        FROM(
        (SELECT $facilityId as provider_id, 'FACILITY' as provider_name, NULL as first_name)
        UNION ALL
        (SELECT plm.user_id as provider_id, CONCAT(u.first_name, ' ', u.last_name) as provider_name, u.first_name
        FROM cm_individualprovider_location_map plm
        INNER JOIN cm_users u
        ON u.id = plm.user_id
        WHERE plm.location_user_id = $facilityId
        ORDER BY u.first_name)
        ) AS T
        ORDER BY T.first_name;";
        return $this->rawQuery($sql);*/
        $results = DB::table(DB::raw("((SELECT $facilityId as provider_id, 'FACILITY' as provider_name, NULL as first_name)
            UNION ALL
            (SELECT plm.user_id as provider_id, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as provider_name, u.first_name
            FROM cm_individualprovider_location_map plm
            INNER JOIN cm_users u ON u.id = plm.user_id
            WHERE plm.location_user_id = $facilityId
            ORDER BY u.first_name)
        ) as T"))
            ->orderByRaw('T.first_name')
            ->get();
      
        return $results;
    }
    /**
     * 
     * fetch the provider / enrollments
     * 
     * @param integer $providerId
     * @param integer $facilityId
     */
    function fetchProviderEnrollment($facilityId,$providerId) {
        // $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;
        // if($facilityId == $providerId) {
        //     $facilityId = 0;
        // }

        // $perPage = $this->cmperPage;
        // $offset = $page - 1;
        // $newOffset = $perPage * $offset;
        
        /*$sql ="SELECT T.payer, T.status,T.effective_date,T.provider_id
        FROM (
        SELECT
        (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer,
        (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as status,
        if(ct.credentialing_status_id <> '3', '-', DATE_FORMAT(ct.effective_date, '%m/%d/%Y')) as effective_date,
        if(ct.credentialing_status_id <> '3', '-', ct.Identifier ) as provider_id
        FROM cm_credentialing_tasks ct
        WHERE ct.user_id = $providerId AND ct.user_parent_id = $facilityId
        ) AS T";

        $enrollments = $this->rawQuery($sql);*/
        $enrollments = DB::table('credentialing_tasks')
        ->select([
            DB::raw("(SELECT payer_name FROM cm_payers WHERE id = cm_credentialing_tasks.payer_id) as payer"),
            DB::raw("(SELECT credentialing_status FROM cm_credentialing_status WHERE id = cm_credentialing_tasks.credentialing_status_id) as status"),
            DB::raw("IF(cm_credentialing_tasks.credentialing_status_id <> '3', '-', DATE_FORMAT(cm_credentialing_tasks.effective_date, '%m/%d/%Y')) as effective_date"),
            DB::raw("IF(cm_credentialing_tasks.credentialing_status_id <> '3', '-', cm_credentialing_tasks.Identifier ) as provider_id")
        ]);
        if($providerId == $facilityId) {//when fetching the enrollment of facility
            $enrollments = $enrollments->where('user_id', $facilityId);
        }
        else {
           $enrollments = $enrollments->where('user_id', $providerId)
            ->where('user_parent_id', $facilityId);
        }
        $enrollments = $enrollments->get();
        // $totall = count($data);
        $pagination = $this->makePagination(0,0,0,0);

        // $sql.=" ORDER BY T.payer  LIMIT $perPage OFFSET $newOffset";

        // $enrollments = $this->rawQuery($sql);

        return ['enrollments' => $enrollments,'pagination' => $pagination];
    }
    /**
     * Provider practices 
     * 
     * 
     * @param $providerId
     */
    function providerPractices($providerId,$sessionUserId) {
        $key = $this->key;
        /*$tbl = "cm_".$this->tbl;
        $sql = "SELECT pli.user_parent_id as practice_id, AES_DECRYPT(pli.doing_buisness_as,'$key') as practice_name, '1' as is_expandable
        FROM `cm_individualprovider_location_map` iplm
        INNER JOIN `$tbl` pli
        ON pli.user_id = iplm.location_user_id
        WHERE iplm.user_id = $providerId
        AND iplm.location_user_id IN( SELECT location_user_id FROM cm_emp_location_map WHERE emp_id = $sessionUserId)
        AND pli.user_parent_id = iplm.location_user_id
        ORDER BY pli.user_parent_id";
        echo $sql;
        exit;
        return $this->rawQuery($sql);/**/
        $practiceData = DB::table('individualprovider_location_map as iplm')
        ->select('pli.user_parent_id as practice_id', 'bpi.practice_name as practice_name', DB::raw('1 as is_expandable'))
        ->join('user_ddpracticelocationinfo as pli', 'pli.user_id', '=', 'iplm.location_user_id')
        ->join('user_baf_practiseinfo as bpi', 'bpi.user_id', '=', 'pli.user_parent_id')
        ->whereIn('iplm.location_user_id', function($query) use ($sessionUserId) {
            $query->select('location_user_id')->from('emp_location_map')->where('emp_id', $sessionUserId);
        })
        ->where('iplm.user_id','=',$providerId)
        ->groupBy('pli.user_parent_id')
        ->orderBy('pli.user_parent_id')
        ->get();

        return $practiceData;
    }
    /**
     * provider facilities
     * 
     * @param $providerId
     * @param $practiceId
     */
    function providerFacilities($providerId,$practiceId,$sessionUserId) {
        $key = $this->key;
        $tbl = $this->tbl;
        /*
        $tbl = "cm_".$this->tbl;
        $sql = "SELECT pli.user_parent_id as practice_id, iplm.location_user_id as facility_id, AES_DECRYPT(pli.practice_name,'$key') as facility_name
        FROM `cm_individualprovider_location_map` iplm
        INNER JOIN `$tbl` pli
        ON pli.user_id = iplm.location_user_id
        WHERE iplm.user_id = $providerId
        AND iplm.location_user_id IN( SELECT location_user_id FROM cm_emp_location_map WHERE emp_id = $sessionUserId)
        GROUP BY iplm.location_user_id
        ORDER BY iplm.location_user_id";
        echo $sql;
        exit;
        return $this->rawQuery($sql);/**/
        $results = DB::table('individualprovider_location_map as iplm')
            ->select('pli.user_parent_id as practice_id', 'iplm.location_user_id as facility_id')
            ->selectRaw('AES_DECRYPT(cm_pli.practice_name, ?) as facility_name', [$key])
            ->join($tbl . ' as pli', 'pli.user_id', '=', 'iplm.location_user_id')
            ->whereIn('iplm.location_user_id', function($query) use ($sessionUserId) {
                $query->select('location_user_id')
                    ->from('emp_location_map')
                    ->where('emp_id', $sessionUserId);
            })
            ->where('iplm.user_id', $providerId)
            ->where('pli.user_parent_id', $practiceId)
            ->groupBy('iplm.location_user_id')
            ->orderBy('iplm.location_user_id')
            ->get();
        return $results;
        //dd($results);

    }
    /**
     * provider enrollment
     * 
     * @param $faciltyId
     * @param $providerId
     */
    function providerEnrollment($facilityId, $providerId) {
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;
        $perPage = $this->cmperPage;
        $offset = $page - 1;
        $newOffset = $perPage * $offset;
        /*$sql = "SELECT T.payer, T.status,T.effective_date,T.provider_id
        FROM (
        SELECT
        (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer,
        (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as status,
        if(ct.credentialing_status_id <> '3', '-', DATE_FORMAT(ct.effective_date, '%m/%d/%Y')) as effective_date,
        if(ct.credentialing_status_id <> '3', '-', ct.Identifier ) as provider_id
        FROM cm_credentialing_tasks ct
        WHERE ct.user_id = $providerId AND ct.user_parent_id = $facilityId
        ) AS T";
        $enrollments = $this->rawQuery($sql);/**/
        $query = DB::table(DB::raw('(SELECT
            (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer,
            (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as status,
            IF(ct.credentialing_status_id <> 3, "-", DATE_FORMAT(ct.effective_date, "%m/%d/%Y")) as effective_date,
            IF(ct.credentialing_status_id <> 3, "-", ct.Identifier) as provider_id
            FROM cm_credentialing_tasks ct
            WHERE ct.user_id = ? AND ct.user_parent_id = ?) AS cm_T'))
        ->select('T.payer', 'T.status', 'T.effective_date', 'T.provider_id')
        ->setBindings([$providerId, $facilityId])
        ->get();

        // Execute the query and retrieve results
        $enrollments = $query->toArray();
        
        // $enrollments = count($data);

        $pagination = $this->makePagination(0,0,0,0);

        // $sql.=" ORDER BY T.payer  LIMIT $perPage OFFSET $newOffset";
        
        // $enrollments = $this->rawQuery($sql);
        
        return ['enrollments' => $enrollments, 'pagination' => $pagination];
    }
}
