<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Http\Traits\Utility;
class Report extends Model
{
    use HasFactory,Utility;
    private $tbl = "user_ddpracticelocationinfo";
    private $tblU = "users";
    /**
     * get the credentialing payers against the providers
     * 
     * @param $isActive
     * @param $facilityId
     */
    function fetchCredentialingPayer($isActive, $facilityId,$isAll=0,$practiceId) {
        $myTbl = $this->tbl;
        $tbl = DB::table($myTbl.' as pli')
        ->select('payer.id','payer.payer_name','pli.user_id as facility_id')
        ->join('credentialing_tasks as ct',function($join) {
            $join->on('ct.user_id','=','pli.user_id')
            ->where('ct.user_parent_id','=','0');
        })
        ->join('payers as payer',function($join) {
            $join->on('payer.id','=','ct.payer_id')
            ->where("payer.for_credentialing","=",1);
        });

        //if($isAll == '1')
            // $tbl =  $tbl->where('pli.user_parent_id','=',$practiceId)
            // ->orWhere('pli.user_id','=',$facilityId);
        // else
        //     $tbl =  $tbl->where('pli.user_id','=',$facilityId);
        $tbl = $tbl->whereRaw("cm_pli.user_parent_id = $practiceId OR cm_pli.user_id IN($facilityId)");
        
        return $tbl->where('pli.for_credentialing','=' ,$isActive)
        
        ->groupBy('ct.payer_id')

        ->orderBy('payer.payer_name','ASC')
        
        ->get();

    }
    /**
     * get the credentialing payers against the providers
     * 
     * @param $isActive
     * @param $facilityId
     */
    function fetchCredentialingPayers($facilityIds,$practiceIds) {
        $myTbl = $this->tbl;
        $tbl = DB::table($myTbl.' as pli')
        ->select('payer.id','payer.payer_name','pli.user_id as facility_id')
        ->join('credentialing_tasks as ct',function($join) {
            $join->on('ct.user_id','=','pli.user_id')
            ->where('ct.user_parent_id','=','0');
        })
        ->join('payers as payer',function($join) {
            $join->on('payer.id','=','ct.payer_id')
            ->where("payer.for_credentialing","=",1);
        });

        
        $tbl = $tbl->whereRaw("cm_pli.user_parent_id  IN($practiceIds) OR cm_pli.user_id IN($facilityIds)");
        
        return $tbl->where('pli.for_credentialing','=' ,1)
        
        ->groupBy('ct.payer_id')

        ->orderBy('payer.payer_name','ASC')
        
        ->get();

    }
    /**
     * fetch the credentialing status with payer and facility
     * 
     * @param $facilityId
     * @param $payerId
     */
    function getCredentialingStatus($facilityId,$payerId) { 
        
        $tbl = DB::table('credentialing_tasks as ct')
        
        ->select('status.id','status.credentialing_status','ct.user_id as facility_id',
        DB::raw("if(cm_status.id <> '3', (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_ct.id ORDER BY id DESC LIMIT 0,1), DATE_FORMAT(cm_ct.effective_date, '%m/%d/%Y')) as date")
        )
        
        ->join('credentialing_status as status','status.id','=','ct.credentialing_status_id')
        
        ->where('ct.payer_id','=',$payerId)
        
        ->where('ct.user_id','=',$facilityId)
        
        ->where('ct.user_parent_id','=',0);
        
       
        return $tbl->first();

    }
     /**
     * fetch the credentialing status with payer and facility
     * 
     * @param $facilityId
     * @param $payerId
     */
    function getCredentialingProviderStatus($facilityId,$individualId,$payerId) { 
        
        $tbl = DB::table('credentialing_tasks as ct')
        
        ->select('status.id','status.credentialing_status','ct.user_id',
        DB::raw("if(cm_status.id <> '3', (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_ct.id ORDER BY id DESC LIMIT 0,1), DATE_FORMAT(cm_ct.effective_date, '%m/%d/%Y')) as date")
        )
        
        ->join('credentialing_status as status','status.id','=','ct.credentialing_status_id')
        
        ->where('ct.payer_id','=',$payerId)
        
        ->where('ct.user_id','=',$individualId)
        
        ->where('ct.user_parent_id','=',$facilityId);
        
        return $tbl->first();

    }
    /**
     * fetch the credentialing payer against the provider
     * 
     * @param $isActive
     * @param $facilityId
     * @param $providerId
     * @param $isAll
     */
    function fetchCredentialingProviderPayer($isActive, $practiceId,$facilityId,$providerId, $isAll = 0) {

        $tbl = "cm_".$this->tbl;
        $key = $this->key;
        $tblU = "cm_".$this->tblU; 
        $where = "pli.user_parent_id = $practiceId AND pli.user_id IN($facilityId)"; 
        $innerWhere = "";
        if($isAll == 0)
            $innerWhere = " AND plm.user_id IN($providerId)";

        $sql = "SELECT payer.id,payer.payer_name,plm.location_user_id as facility_id
        FROM `$tbl`pli
        INNER JOIN `$tblU` u
        ON u.id = pli.user_id
        INNER JOIN  `cm_individualprovider_location_map` plm
        ON plm.location_user_id = u.id AND plm.for_credentialing=$isActive $innerWhere
        INNER JOIN cm_credentialing_tasks ct
        ON ct.user_id = plm.user_id AND ct.user_parent_id = plm.location_user_id
        INNER JOIN cm_payers payer
        ON payer.id = ct.payer_id
        WHERE $where GROUP BY ct.payer_id ORDER BY payer.payer_name ASC";
        
        
        return $this->rawQuery($sql);
    }
    /**
     * fetch the active and inactive reports
     * 
     * 
     * 
     */
    function activeInActiveReports () {
        
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;
        
        $tbl = "cm_".$this->tbl;
        
        $key = $this->key;
        
        $tblU = "cm_".$this->tblU;

        $perPage = $this->cmperPage;
        $filteStr = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : "";
        $filter = isset($_REQUEST["filter"]) ? "WHERE T.provider_name LIKE '%$filteStr%' OR T.facility_name LIKE '%$filteStr%'  OR T.role_name LIKE '%$filteStr%' OR T.from_date LIKE '%$filteStr%' OR T.to_date LIKE '%$filteStr%' OR T.status LIKE '%$filteStr%' OR T.created_by LIKE '%$filteStr%'" : "";
        $sql = "SELECT * FROM (SELECT 
        (SELECT practice_name FROM cm_user_baf_practiseinfo WHERE user_id = ail.practice_id) as practice_name,
        (SELECT AES_DECRYPT(practice_name,'$key')  FROM $tbl WHERE user_id = ail.facility_id AND user_parent_id = ail.practice_id) as facility_name,
        (SELECT   CONCAT(first_name, ' ',last_name) FROM $tblU WHERE id = ail.provider_id) as provider_name,
        (
             CASE 
             WHEN ail.role_id = '4'
                THEN 'Member'
              WHEN ail.role_id = '9'
                THEN 'Practice'
              WHEN ail.role_id = '3'
                THEN 'Facility'
             ELSE '-'
             END
        ) AS role_name,
        (SELECT   CONCAT(first_name, ' ',last_name) FROM $tblU WHERE id = ail.created_by ) as created_by,
        (
            CASE
            WHEN ail.for_credentialing = '1'
            THEN 'Active'
            ELSE 'InActive'
            END
        ) as status,
        IF(ail.from <> '0000-00-00 00:00:00', DATE_FORMAT(ail.from, '%m/%d/%Y'), '-') AS from_date,
        IF(ail.to <> '0000-00-00 00:00:00', DATE_FORMAT(ail.to, '%m/%d/%Y'), '-') AS to_date,
        (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ail.created_by) as profile_picture,
        ail.created_at,
        ail.comments
        FROM `cm_active_inactive_logs`ail) AS T $filter ORDER BY T.created_at DESC,T.status ASC";

        // echo $sql;
        // exit;
        $result = $this->rawQuery($sql);

        $totalRec = count($result);

        //$numOfPage = ceil($totalRec/$perPage); 

        $offset = $page - 1;
        $newOffset = $perPage * $offset;
        $sql .=" LIMIT $perPage OFFSET $newOffset";
        $pagination = $this->makePagination($page,$perPage,$offset,$totalRec);
        $result = $this->rawQuery($sql);

        return ["pagination" => $pagination, "result" => $result];
    }
    /**
     * fetch in active payers
     */
    function inActivePayers($isActive, $facilityId,$isAll=0,$practiceId) {
        $tbl = "cm_".$this->tbl;
        
        $key = $this->key;
        
        $tblU = "cm_".$this->tblU;

        $where = " M.user_parent_id IN ($practiceId) OR M.user_id IN ($facilityId)";
        // if($isAll ==0)
        //     $where = "M.user_id IN ($facilityId)";

        $sql = "SELECT M.creds_taskid, M.user_parent_id, M.user_id, M.payer as payer_name, M.payer_id as id, M.individual_id, M.facility_id
        FROM(
        (SELECT T.creds_taskid, T.user_parent_id, T.user_id, T.payer, T.payer_id, T.individual_id, T.facility_id
        FROM (SELECT ct.id as creds_taskid, ct.user_parent_id, ct.user_id, (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer, (SELECT id FROM cm_payers WHERE id = ct.payer_id) as payer_id,
                         (CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END) AS individual_id,
                        (CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END) AS facility_id
                       FROM `cm_credentialing_tasks` ct
                       WHERE ct.user_parent_id = '0') AS T
                       INNER JOIN `$tbl` pli
                       ON pli.user_id = T.facility_id AND pli.for_credentialing = '0')
        UNION ALL
        (SELECT T.creds_taskid, T.user_parent_id, T.user_id, T.payer, T.payer_id, T.individual_id, T.facility_id
        FROM (SELECT ct.id as creds_taskid, ct.user_parent_id, ct.user_id,
              (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer,
              (SELECT id FROM cm_payers WHERE id = ct.payer_id) as payer_id,
              (CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END) AS individual_id,
              (CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END) AS facility_id
                       FROM `cm_credentialing_tasks` ct
                        WHERE ct.user_parent_id <> 0) AS T
                       INNER JOIN cm_individualprovider_location_map plm
                       ON plm.location_user_id = T.facility_id AND plm.user_id = T.individual_id AND plm.for_credentialing = '0')
        ) AS M
        WHERE $where
        GROUP BY M.payer_id
        ORDER BY M.payer";
       
         return $this->rawQuery($sql);
    }
    /**
     * fetch the comprehensive report of the credentialing
     * 
     * @param $facilityId
     * @param $userId
     * @param $isParent
     */
    function comprehensiveReport($facilityId, $userId, $isParent,$moreFilter="",$entities=[],$payersIds="") {
       
        $cols = [
            "payer_name" => "p.payer_name",
            "credentialing_status" => "cs.credentialing_status",
            "effective_date" => "if(ct.credentialing_status_id <> '3', NULL, DATE_FORMAT(effective_date, '%m/%d/%Y')) as result_effective_date",
            "contract_type" => "ct.contract_type","last_follow_up_date" => "(SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM `cm_credentialing_task_logs` WHERE credentialing_task_id = ct.id AND credentialing_status_id IS NULL AND details IS NOT NULL ORDER BY created_at  DESC LIMIT 0,1) as last_follow_up_date",
            "info_required" => "ct.info_required","log" => "(SELECT details FROM `cm_credentialing_task_logs` WHERE credentialing_task_id = ct.id AND credentialing_status_id IS NULL AND details IS NOT NULL ORDER BY created_at  DESC LIMIT 0,1) as log"
        ];
        if(count($entities) == 0) {
           
            if($isParent == 1)
                $where = "ct.user_id = $facilityId AND ct.user_parent_id = 0";
            else
                $where = "ct.user_id = $userId AND ct.user_parent_id = $facilityId";
            
            $moreFilterStr = "";
            if($moreFilter !="") {
                $moreFilterStr.= " AND ct.credentialing_status_id IN ($moreFilter)";
            }
            if($payersIds !="") {
                $moreFilterStr.= " AND ct.payer_id IN ($payersIds)";
            }
            $sql = " SELECT p.payer_name, cs.credentialing_status,
            if(ct.credentialing_status_id <> '3', NULL, DATE_FORMAT(effective_date, '%m/%d/%Y')) as result_effective_date,
            ct.id as credentialing_task_id,
            (SELECT details FROM `cm_credentialing_task_logs` WHERE credentialing_task_id = ct.id AND credentialing_status_id IS NULL AND details IS NOT NULL ORDER BY created_at  DESC LIMIT 0,1) as log,
            (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM `cm_credentialing_task_logs` WHERE credentialing_task_id = ct.id AND credentialing_status_id IS NULL AND details IS NOT NULL ORDER BY created_at  DESC LIMIT 0,1) as last_follow_up_date,
            ct.contract_type, ct.info_required,ct.payer_id,ct.user_id
            FROM `cm_credentialing_tasks` ct
            INNER JOIN cm_payers p
            ON p.id = ct.payer_id
            INNER JOIN cm_credentialing_status cs
            ON cs.id = ct.credentialing_status_id
            WHERE $where $moreFilterStr
            ORDER BY p.payer_name";
            return $this->rawQuery($sql);
        }
        else {
            // print_r($entities);
            // exit("Hi");
            $sql = [];
            foreach($entities as $entity) {
                // print_r($entity['value']);
                // exit;
                $sqlCol = $cols[$entity['value']];
                array_push($sql,$sqlCol);
                //echo "<br>";
                
            }
            if($isParent == 1)
            $where = "ct.user_id = $facilityId AND ct.user_parent_id = 0";
            else
                $where = "ct.user_id = $userId AND ct.user_parent_id = $facilityId";
            
            $moreFilterStr = "";
            if($moreFilter !="") {
                $moreFilterStr.= " AND ct.credentialing_status_id IN ($moreFilter)";
            }
            if($payersIds !="") {
                $moreFilterStr.= " AND ct.payer_id IN ($payersIds)";
            }
            $sql = implode(",",$sql);
            //exit;
           $sql.=",ct.payer_id,ct.user_id,ct.id as credentialing_task_id";
           //exit;
           $mySql = "SELECT $sql 
            FROM `cm_credentialing_tasks` ct
            INNER JOIN cm_payers p
            ON p.id = ct.payer_id
            INNER JOIN cm_credentialing_status cs
            ON cs.id = ct.credentialing_status_id
            WHERE $where $moreFilterStr
            ORDER BY p.payer_name
           ";
            return $this->rawQuery($mySql);
        }
    }
}
