<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Dashboard extends Model
{
    use HasFactory;
    private $tbl = "user_ddpracticelocationinfo";
    private $key = "";
    private $tblU = "users";
    public function __construct()
    {
        $this->key = env("AES_KEY");
    }
    /**
     * get the dashboad states.
     * 
     * 
     */
    function dashboadStates($facilityId = 0,$sessionUserId)
    {
        $tbl = $this->tbl;

        if ($facilityId != 0) {
            $select1 = "SELECT M.facility_id as facilityid, M.practice_name as facilityname, COUNT(M.id) as tasks, 'ALL' as status";
            $select2 = "SELECT T.facility_id as facilityid, T.practice_name as facilityname, COUNT(T.facility_id) as tasks, T.credentialing_status as status";
            $where1 = "WHERE M.facility_id = '$facilityId'";
            $where2 = "WHERE T.facility_id = '$facilityId'";
            $groupBy = "GROUP BY T.facility_id, T.credentialing_status_id";
        } else {
            $select1 = "SELECT COUNT(M.id) as tasks, 'ALL' as status";
            $select2 = "SELECT COUNT(T.facility_id) as tasks, T.credentialing_status as status";
            $where1 = "";
            $where2 = "";
            $groupBy = "GROUP BY T.credentialing_status_id";
        }

        $sql = "
            ($select1
            FROM(
            SELECT (CASE WHEN ct.user_parent_id = '0' THEN ct.user_id
                    ELSE ct.user_parent_id END) AS facility_id, pli.practice_name, ct.id
            FROM `cm_credentialing_tasks` ct
            INNER JOIN `cm_$tbl` pli
            ON pli.user_id IN(ct.user_id, ct.user_parent_id)
            INNER JOIN `cm_users` u
            ON u.id = pli.user_id AND u.deleted = '0'
            INNER JOIN `cm_emp_location_map` elm 
            ON pli.user_id = elm.location_user_id AND elm.emp_id = $sessionUserId
            ) AS M
            $where1)
            UNION ALL
            ($select2
            FROM (
            SELECT ct.user_id, ct.user_parent_id, ct.payer_id,
                    (CASE WHEN ct.user_parent_id = '0' THEN ct.user_id
                    ELSE ct.user_parent_id END) AS facility_id,
            pli.practice_name,
            ct.credentialing_status_id,
            cs.credentialing_status
            FROM `cm_credentialing_tasks` ct
            INNER JOIN `cm_$tbl` pli
            ON pli.user_id IN(ct.user_id, ct.user_parent_id)
            INNER JOIN `cm_users` u
            ON u.id = pli.user_id AND u.deleted = '0'
            INNER JOIN `cm_emp_location_map` elm 
            ON pli.user_id = elm.location_user_id AND elm.emp_id = $sessionUserId
            INNER JOIN `cm_credentialing_status` cs
            ON cs.id = ct.credentialing_status_id
            ORDER BY ct.user_parent_id
            ) AS T
            $where2
            $groupBy
            ORDER BY T.facility_id);
        ";

        return DB::select($sql);
    }
    /**
     * filter dropdown
     */
    function filterDD()
    {
        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        return DB::select("(SELECT 0 as facilityid,'All' as facilityname)
        UNION
        (select `cm_pli`.`user_id` as `facilityid`, AES_DECRYPT(`cm_pli`.`practice_name`,'$key') as `facilityname` from
        `$tbl` as `cm_pli` inner join `cm_users` as `cm_u` on `cm_pli`.`user_id` = `cm_u`.`id` and
        `cm_u`.`deleted` = 0 order by `cm_pli`.`user_id` asc)");
    }
    /**
     * fetch active Practices of ECA
     * 
     * 
     */
    function activePractices($sessionUserId)
    {
        $key = $this->key;
        $tbl = $this->tbl;
        $tblU = $this->tblU;
        $tblP = "user_baf_practiseinfo";
        // return DB::table($tblU . ' as u')
        //     ->select("u.id as practice_id", DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$key') as practice_name"))
        //     ->join('user_role_map as urm', function ($join) {
        //         $join->on('urm.user_id', '=', 'u.id')
        //             ->where('urm.role_id', '=', 9);
        //     })
        //     ->join($tbl . ' as pli', function ($join) {
        //         $join->on([
        //             ['pli.user_id', '=', 'u.id'],
        //             ['pli.user_parent_id', '=', 'u.id']
        //         ]);
        //     })
        //     ->where('u.deleted', '=', '0')

        //     ->orderBy('practice_name')

        //     ->get();

        $facilities = DB::table("emp_location_map as elm")
        
        ->where('elm.emp_id', '=',$sessionUserId)
        
        ->pluck('elm.location_user_id')
        
        ->toArray();

        // $this->printR($facilities,true);

        $practices = DB::table($tbl.' as pli')
        
        ->select('pli.user_id as practice_id',DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as practice_name"))
        
        // ->join($tblP.' as p', function ($join) {
        //     $join->on('p.user_id', '=', 'pli.user_parent_id');
        // })
        ->join($tblU.' as u', function ($join) {
            $join->on('pli.user_id', '=', 'u.id')
            ->where('u.deleted', '=', '0');
        })
        
        
        ->whereIn('pli.user_id',$facilities)
        
        //->groupBy('pli.user_parent_id')
        
        ->orderBy('pli.practice_name')

        ->get();

        return $practices;
        //exit;
    }
    /**
     * billing statistics
     * 
     * 
     * @param $facilityId
     * @param $startDate
     * @param $endDate
     */
    function billingStatistics($facilityId, $startDate, $endDate,$sessionUserId)
    {
        $facilities = DB::table("emp_location_map as elm")
        
        ->where('elm.emp_id', '=',$sessionUserId)
        
        ->pluck('elm.location_user_id')
        
        ->toArray();

        $tbl = DB::table("billing")
            ->select(
                DB::raw('YEAR(cm_billing.dos) as year'),
                DB::raw('DATE_FORMAT(cm_billing.dos, "%b") as short_month_name'),
                DB::raw('COUNT(cm_billing.claim_no) as total_claims')
            )
            // ->join("users as u_practice", function ($join) {
            //     $join->on('u_practice.id', '=', 'billing.practice_id')
            //         ->where('u_practice.deleted', '=', 0);
            // })
            // ->join("users as u_facility", function ($join) {
            //     $join->on('u_facility.id', '=', 'billing.facility_id')
            //         ->where('u_facility.deleted', '=', 0);
            // })
            // ->join("users as u_facility", function ($join) {
            //     $join->on('u_facility.id', '=', 'billing.facility_id')
            //         ->where('u_facility.deleted', '=', 0);
            // })
            ->where('billing.is_deleted', '=', 0);
        if ($facilityId != 0)
            $tbl = $tbl->where('billing.facility_id', '=', $facilityId);
        else
            $tbl = $tbl->whereIn('billing.facility_id', $facilities);


        return $tbl->whereBetween('billing.dos', [$startDate, $endDate])
            ->groupBy(DB::raw('YEAR(cm_billing.dos), MONTH(cm_billing.dos)'))
            ->get();
    }
    /**
     * fetch posting graph time frame
     * 
     * @param $practiceId
     * @param $startDate
     * @param $endDate
     */
    function postingGraphTimeFrame($practiceId, $startDate, $endDate)
    {
        $tbl = DB::table('billing_posting AS bp')
            ->select(DB::raw("CONCAT(MONTH(cm_bp.instrument_date),'-',YEAR(cm_bp.instrument_date)) as period"));
        if ($practiceId > 0)
            $tbl = $tbl->where("bp.practice_id", $practiceId);

        return $tbl->whereBetween('bp.instrument_date', [$startDate, $endDate])
            ->groupBy(DB::raw("DATE_FORMAT(cm_bp.instrument_date, '%m-%Y')"))
            ->get();
    }
    /**
     * posting payer with collection for graph
     */
    function postingGraphPayers($practiceId, $timeFrame)
    {
        if ($practiceId == 0) {
            $query = DB::select(DB::raw('
            (
                SELECT (@row_number := @row_number + 1) AS sequential_number, N.*
                FROM (
                    SELECT M.period, M.payer_id, M.payer_name, SUM(M.collection) as collection, M.color
                    FROM (
                        SELECT CONCAT(MONTH(bp.instrument_date),"-",YEAR(bp.instrument_date)) as period, bp.payer_id, p.payer_name, p.color, SUM(bp.amount) as collection
                        FROM `cm_billing_posting` bp
                        INNER JOIN cm_payers p ON p.id = bp.payer_id
                        GROUP BY bp.payer_id, DATE_FORMAT(bp.instrument_date, "%m-%Y")
                    ) AS M
                    WHERE M.period = "' . $timeFrame . '"
                    GROUP BY M.payer_id
                    ORDER BY M.collection DESC
                    LIMIT 0, 8
                ) AS N
                CROSS JOIN (SELECT @row_number := 0) AS dummy
            )
            UNION ALL
            (
                SELECT (@row_number := @row_number + 1) AS sequential_number, "' . $timeFrame . '" as period, "-" as payer_id, "others" as payer_name, SUM(N.collection), "-" as color
                FROM (
                    SELECT M.period, M.payer_id, M.payer_name, SUM(M.collection) as collection, M.color
                    FROM (
                        SELECT CONCAT(MONTH(bp.instrument_date),"-",YEAR(bp.instrument_date)) as period, bp.payer_id, p.payer_name, p.color, SUM(bp.amount) as collection
                        FROM `cm_billing_posting` bp
                        INNER JOIN cm_payers p ON p.id = bp.payer_id
                        GROUP BY bp.payer_id, DATE_FORMAT(bp.instrument_date, "%m-%Y")
                    ) AS M
                    WHERE M.period = "' . $timeFrame . '"
                    GROUP BY M.payer_id
                    ORDER BY M.collection DESC
                    LIMIT 8, 18446744073709551615
                ) AS N
                CROSS JOIN (SELECT @row_number := 0) AS dummy
            )
        '));
        }
        if ($practiceId > 0) {
            $sql = '
            (
                SELECT (@row_number := @row_number + 1) AS sequential_number, N.*
                FROM (
                    SELECT M.practice_id,M.period, M.payer_id, M.payer_name, SUM(M.collection) as collection, M.color
                    FROM (
                        SELECT bp.practice_id,CONCAT(MONTH(bp.instrument_date),"-",YEAR(bp.instrument_date)) as period, bp.payer_id, p.payer_name, p.color, SUM(bp.amount) as collection
                        FROM `cm_billing_posting` bp
                        INNER JOIN cm_payers p ON p.id = bp.payer_id
                        GROUP BY bp.payer_id, DATE_FORMAT(bp.instrument_date, "%m-%Y")
                    ) AS M
                    WHERE M.period = "' . $timeFrame . '"
                    AND M.practice_id = "' . $practiceId . '"
                    GROUP BY M.payer_id
                    ORDER BY M.collection DESC
                    LIMIT 0, 8
                ) AS N
                CROSS JOIN (SELECT @row_number := 0) AS dummy
            )
            UNION ALL
            (
                SELECT (@row_number := @row_number + 1) AS sequential_number, "' . $practiceId . '" as practice_id, "' . $timeFrame . '" as period, "-" as payer_id, "others" as payer_name, SUM(N.collection), "-" as color
                FROM (
                    SELECT M.practice_id,M.period, M.payer_id, M.payer_name, SUM(M.collection) as collection, M.color
                    FROM (
                        SELECT bp.practice_id,CONCAT(MONTH(bp.instrument_date),"-",YEAR(bp.instrument_date)) as period, bp.payer_id, p.payer_name, p.color, SUM(bp.amount) as collection
                        FROM `cm_billing_posting` bp
                        INNER JOIN cm_payers p ON p.id = bp.payer_id
                        GROUP BY bp.payer_id, DATE_FORMAT(bp.instrument_date, "%m-%Y")
                    ) AS M
                    WHERE M.period = "' . $timeFrame . '"
                    AND M.practice_id = "' . $practiceId . '"
                    GROUP BY M.payer_id
                    ORDER BY M.collection DESC
                    LIMIT 8, 18446744073709551615
                ) AS N
                CROSS JOIN (SELECT @row_number := 0) AS dummy
            )
        ';

            $query = DB::select(DB::raw($sql));
        }

        $results = collect($query);
        return $results;
    }
    /**
     * posting statistics
     * 
     * 
     */
    function postingStatistics($practiceId, $startDate, $endDate)
    {


        $result = DB::table(function ($subquery) use ($practiceId, $startDate, $endDate) {
            $subquery = $subquery->select(
                DB::raw("CONCAT(MONTH(cm_bp.instrument_date),'-',YEAR(cm_bp.instrument_date)) as period"),
                'bp.payer_id',
                'bp.instrument_no',
                'p.payer_name',
                'p.color',
                DB::raw("SUM(cm_bp.amount) as collection")
            )
                ->from('billing_posting as bp')
                ->join('payers as p', 'p.id', '=', 'bp.payer_id');

            if ($practiceId > 0)
                $subquery = $subquery->where("bp.practice_id", $practiceId);

            $subquery = $subquery
                ->whereBetween('bp.instrument_date', [$startDate, $endDate])
                ->groupBy('bp.payer_id', 'bp.instrument_no', DB::raw('YEAR(cm_bp.instrument_date)'), DB::raw('MONTH(cm_bp.instrument_date)'))
                ->orderByDesc('collection');
        }, 'T')
            ->select('T.period', 'T.payer_name', DB::raw('SUM(cm_T.collection) as collection'), 'T.color')
            ->groupBy('T.payer_id', 'T.period')
            ->orderBy('T.period')
            ->get();
        // echo $result;
        // exit;    
        return $result;
    }

    /**
     * facility payer averages
     * 
     * @param $facilityId
     * @param $startDate
     * @param $endDate
     */
    function facilitypayerAvgs($facilityId, $startDate, $endDate,$sessionUserId)
    {
        $facilities = DB::table("emp_location_map as elm")
        
        ->where('elm.emp_id', '=',$sessionUserId)
        
        ->pluck('elm.location_user_id')
        
        ->toArray();

        $facilitiesStr = implode(",",$facilities);
        $key = env("AES_KEY");

        $subQuery = "SELECT 
        COUNT(ar.claim_no) AS total_claims,
        SUM(ar.paid_amount) AS total_paid,
        (SUM(ar.paid_amount) / COUNT(ar.claim_no)) AS average_ar,
        cm_payers.payer_name,
        ar.payer_id,
        ar.facility_id,
        MONTH(ar.dos) AS MONTH
    FROM
        cm_account_receivable AS ar
        INNER JOIN cm_payers ON cm_payers.id = ar.payer_id
    WHERE
        ar.status IN (5,6,8,2)
        AND ar.is_delete = 0
        AND ar.dos BETWEEN '$startDate' AND '$endDate'";
        $grouping = "ar.facility_id,";
        if ($facilityId > 0) {
            $subQuery .= " AND ar.facility_id ='$facilityId'";
            $grouping = "";
        }
        else {
            $subQuery .= " AND ar.facility_id in($facilitiesStr)";
        }

        $subQuery .= " GROUP BY $grouping ar.payer_id";

        $result = DB::table(DB::raw("($subQuery) AS subquery"))
            ->select([
                'total_claims',
                DB::raw('ROUND(total_paid, 2) AS total_paid'),
                DB::raw('ROUND(average_ar, 2) AS average_ar'),
                'payer_name',
                'payer_id',
                'MONTH',
                'facility_id',
                DB::raw("(SELECT AES_DECRYPT(practice_name,'$key') FROM cm_user_ddpracticelocationinfo WHERE user_id = facility_id) AS facility_name"),
                DB::raw('0 AS percentage')
            ])
            ->orderBy('MONTH', 'asc')
            ->get();
        return $result;
    }
    /**
     * facility payer averages
     * 
     * @param $facilityId
     * @param $startDate
     * @param $endDate
     */
    function facilityEachpayerAvgs($facilityId, $payerId, $startDate, $endDate)
    {
        $key = env("AES_KEY");

        $subQuery = "SELECT 
        COUNT(ar.claim_no) AS total_claims,
        SUM(ar.paid_amount) AS total_paid,
        (SUM(ar.paid_amount) / COUNT(ar.claim_no)) AS average_ar,
        cm_payers.payer_name,
        ar.payer_id,
        ar.facility_id
    FROM
        cm_account_receivable AS ar
        INNER JOIN cm_payers ON cm_payers.id = ar.payer_id
    WHERE
        ar.status IN (5,6,8,2)
        AND ar.is_delete = 0
        AND ar.dos BETWEEN '$startDate' AND '$endDate'";
        if ($facilityId > 0) {
            $subQuery .= " AND ar.facility_id ='$facilityId'";
        }
        if ($payerId > 0) {
            $subQuery .= " AND ar.payer_id ='$payerId'";
        }




        $subQuery .= " GROUP BY ar.payer_id";


        $result = DB::table(DB::raw("($subQuery) AS subquery"))
            ->select([
                'total_claims',
                DB::raw('ROUND(total_paid, 2) AS total_paid'),
                DB::raw('ROUND(average_ar, 2) AS average_ar'),
                'payer_name',
                'payer_id',
                'facility_id',
                DB::raw("(SELECT AES_DECRYPT(practice_name,'$key') FROM cm_user_ddpracticelocationinfo WHERE user_id = facility_id) AS facility_name"),
                DB::raw('0 AS percentage')
            ])
            ->first();
        return $result;
    }
}
