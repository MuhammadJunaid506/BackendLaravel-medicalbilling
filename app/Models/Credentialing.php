<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\Utility;
use Carbon\Carbon;

class Credentialing extends Model
{
    use HasFactory, Utility;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "credentialing_tasks";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "provider_id",
        "insurance_id",
        "group_provider",
        "info_required",
        "created_at",
        "updated_at"
    ];
    private $tbl = "user_ddpracticelocationinfo";
    private $tblU = "users";
    private $key = "";
    public function __construct()
    {
        $this->key = env("AES_KEY");
    }

    /**
     * Has Many Relation with CredentialingActivityLog
     *
     */
    public function credentialingLogs()
    {
        return $this->hasMany(CredentialingActivityLog::class, 'credentialing_task_id');
    }

    /**
     * Belongs To Relation with CredentialingStatus
     *
     */
    public function credentialingStatus()
    {
        return $this->belongsTo(CredentialingStatus::class, 'credentialing_status_id');
    }

    /**
     * Belongs To Relation with Payer
     *
     */
    public function credentialingPayer()
    {
        return $this->belongsTo(Payer::class, 'payer_id');
    }

    /**
     * Belongs To Relation with PracticeLocation
     *
     */
    public function credentialingFacility()
    {
        return $this->belongsTo(PracticeLocation::class, 'user_parent_id', 'user_id');
    }

    /**
     * Belongs To Relation with PracticeLocation
     *
     */
    public function credentialingProvider()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Belongs To Relation with PracticeLocation
     *
     */
    public function credentialingassinguser()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    /**
     * Belongs To Relation with PracticeLocation
     *
     */
    public function credentialingfacilityUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function latestlog()
    {
        return $this->hasone(CredentialingActivityLog::class, 'credentialing_task_id')->latest('id');
    }


    /**
     * get the crdentialing LI listing
     *
     * @param $filter
     */
    function fetchCredentialingUsersLI($isPaginate = true, $filter = "", $sessionUserId = NULL)
    {
        if ($filter != "")
            return $this->activePracticesWithFilter($isPaginate, $filter, $sessionUserId);
        else
            return $this->activePractices($isPaginate, $sessionUserId);
    }
    /**
     * get the session user practices
     *
     *
     */
    private function sessionActivePractices($sessionUserId, $isActive = 0)
    {
        $appTblL = "user_ddpracticelocationinfo";
        $tblU = "users";
        $tbl = "user_baf_practiseinfo";
        $facilities = DB::table("emp_location_map as elm")

            ->where('elm.emp_id', '=', $sessionUserId)

            ->pluck('elm.location_user_id')

            ->toArray();

        // $this->printR($facilities,true);

        $practices = DB::table($appTblL . ' as pli')

            ->select('pli.user_parent_id as facility_id', DB::raw('IFNULL(cm_p.doing_business_as, cm_p.practice_name) AS doing_buisness_as'))

            ->join($tbl . ' as p', function ($join) {
                $join->on('p.user_id', '=', 'pli.user_parent_id');
            })
            ->join($tblU . ' as u', function ($join) use ($isActive) {
                $join->on('p.user_id', '=', 'u.id')
                    ->where('u.deleted', '=', $isActive);
            })


            ->whereIn('pli.user_id', $facilities)

            ->groupBy('pli.user_parent_id')

            ->orderBy('p.doing_business_as')

            ->get();

        // $this->printR($practices,true);
        return $practices;
    }
    /**
     * get the session user practices
     *
     *
     */
    private function sessionActiveInActivePractices($sessionUserId, $isActive = 0)
    {
        $appTblL = "user_ddpracticelocationinfo";
        $tblU = "users";
        $tbl = "user_baf_practiseinfo";
        $facilities = DB::table("emp_location_map as elm")

            ->where('elm.emp_id', '=', $sessionUserId)

            ->pluck('elm.location_user_id')

            ->toArray();

        // $this->printR($facilities,true);

        $practices = DB::table($appTblL . ' as pli')

            ->select('pli.user_parent_id as facility_id', DB::raw('IFNULL(cm_p.doing_business_as, cm_p.practice_name) AS doing_buisness_as'))

            ->join($tbl . ' as p', function ($join) {
                $join->on('p.user_id', '=', 'pli.user_parent_id');
            })
            // ->join($tblU . ' as u', function ($join) use($isActive) {
            //     $join->on('p.user_id', '=', 'u.id')
            //         ->where('u.deleted', '=', 0)
            //         ->orWhere('u.deleted', '=', 1);
            // })
            ->join($tblU . ' as u', 'u.id', '=', 'p.user_id')

            ->whereIn('u.deleted', [0, 1])

            ->whereIn('pli.user_id', $facilities)

            ->groupBy('pli.user_parent_id')

            ->orderBy('p.doing_business_as')

            ->get();

        // $this->printR($practices,true);
        return $practices;
    }

    function getSpecificFacilities($parentId, $sessionUserId, $isArchived)
    {
        $tbl = "user_ddpracticelocationinfo";
        $tblU = "users";
        $appKey =  $this->key;

        $locations = DB::table($tbl . ' as pli')

            ->select([DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$appKey') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_pli.practice_name,'$appKey') as practice_name"), "pli.user_id as facility_id"]);

        $locations = $locations->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            $join->on('elm.location_user_id', '=', 'pli.user_id')
                ->where('elm.emp_id', '=', $sessionUserId);
        });
        $locations->join($tblU . " as u_facility", function ($join) use ($isArchived) {
            $join->on('u_facility.id', '=', 'pli.user_id')
                ->where('u_facility.deleted', '=', $isArchived);
        });
        if (is_array($parentId))
            $locations = $locations->whereIn("pli.user_parent_id", $parentId);
        else
            $locations = $locations->where("pli.user_parent_id", "=", $parentId);



        return $locations->get();
    }
    /**
     * active practice without filter
     */
    private function activePractices($isPaginate, $sessionUserId)
    {

        $practices = $this->sessionActivePractices($sessionUserId);
        // $this->printR($practices,true);
        $practiceIds = 0;
        if (count($practices)) {
            $practiceIdsArr = [];
            foreach ($practices as $practice) {
                $practiceIdsArr[] = $practice->facility_id;
            }
            $practiceIds = implode(",", $practiceIdsArr);
        }
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $sql = "SELECT *
        FROM ((SELECT t2.facility_id,
        t2.doing_buisness_as,
        t2.for_credentialing
        FROM (SELECT user_id AS facility_id,
        IFNULL(practice_name,doing_business_as)
        AS doing_buisness_as,
        '1' AS for_credentialing
        FROM `cm_user_baf_practiseinfo`
        WHERE user_id = user_id
        ORDER BY doing_buisness_as) t2
        WHERE EXISTS (SELECT 1
        FROM `cm_user_ddpracticelocationinfo`
        WHERE for_credentialing = '1'
        AND user_parent_id = t2.facility_id))) AS T
        where T.facility_id in($practiceIds)
        ORDER BY T.doing_buisness_as";
        /*echo $sql;
        exit;*/
        //  $totallRec = DB::select($sql);

        $perPage = $this->cmperPage;

        //  $totalRec = count($totallRec);
        //exit;
        $offset = $page - 1;

        //  $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

        $newOffset = $perPage * $offset;
        if ($isPaginate)
            $sql .= " LIMIT $perPage OFFSET $newOffset";


        $asignedPractices = DB::select($sql);

        return ["practices" => $asignedPractices];
    }
    /**
     * active practice with filter
     */
    private function activePracticesWithFilter($isPaginate, $filter, $sessionUserId)
    {
        $filterSql = $filter; //$filter !="" ? "LIKE '%$filter%'" : "";
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $tbl = "cm_" . $this->tbl;

        $key = $this->key;

        $tblU = "cm_" . $this->tblU;

        $practices = $this->sessionActivePractices($sessionUserId);
        // dd($practices);
        //$this->printR($practices,true);
        $practiceIds = 0;
        if (count($practices)) {
            $practiceIdsArr = [];
            foreach ($practices as $practice) {
                $practiceIdsArr[] = $practice->facility_id;
            }
            $practiceIds = implode(",", $practiceIdsArr);
        }

        $sql = "SELECT result.practiceid as facility_id, result.practicename as doing_buisness_as, result.for_credentialing, result.is_expandable, '1' as is_visible
        FROM(
        SELECT T.practiceid, T.practicename, T.for_credentialing,
        if(T.is_expandable_facility > 0 OR T.is_expandable_provider > 0, '1', '0') as is_expandable
        FROM
        (
        (select t.practiceid, t.practicename, t.for_credentialing, '0' as is_expandable_facility, '0' as is_expandable_provider
        from (select '0' as practiceid, '-ALL-' as practicename, '1' as for_credentialing) t
        where exists (SELECT 1 FROM `$tbl` WHERE for_credentialing = '1' AND user_parent_id = user_parent_id group by user_parent_id))
        UNION ALL
        (SELECT t2.practiceid, t2.practicename, t2.for_credentialing,
             (SELECT if(COUNT(id) > 0, '1', '0') FROM `$tbl` pli WHERE pli.for_credentialing = '1' AND pli.user_parent_id = t2.practiceid AND AES_DECRYPT(pli.practice_name,'$key') LIKE '%$filterSql%') as is_expandable_facility,

            (SELECT IF(COUNT(u.id) > 0, '1', '0')
            FROM `cm_individualprovider_location_map` plm
            INNER JOIN `$tblU` u
            ON u.id = plm.user_id
            INNER JOIN `$tbl` pli
            ON pli.user_id = plm.location_user_id
            WHERE plm.location_user_id IN (SELECT user_id FROM `$tbl` WHERE user_parent_id = t2.practiceid)
            AND plm.for_credentialing = '1'
            AND CONCAT(u.first_name, ' ',u.last_name) LIKE '%$filterSql%') as is_expandable_provider
        FROM
        (SELECT user_id as practiceid, IFNULL(practice_name,doing_business_as)   AS practicename, '1' as for_credentialing
        FROM `cm_user_baf_practiseinfo`
        WHERE user_id = user_id
        ORDER BY practicename) t2
        where exists (SELECT 1 FROM `$tbl` WHERE for_credentialing = '1' AND user_parent_id = t2.practiceid))
        ) AS T WHERE T.practiceid IN($practiceIds)
        ORDER BY T.practicename
        ) AS result
        WHERE result.is_expandable = '1'
        ORDER BY result.is_expandable DESC";

        // echo $sql;
        // exit;
        // $totallRec = DB::select($sql);

        $perPage = $this->cmperPage;

        // $totalRec = count($totallRec);
        // //exit;
        $offset = $page - 1;

        // $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

        $newOffset = $perPage * $offset;
        if ($isPaginate)
            $sql .= " LIMIT $perPage OFFSET $newOffset";

        $asignedPractices = DB::select($sql);

        // dd($asignedPractices);
        return ["practices" => $asignedPractices];
    }
    /**
     * get the crdentialing LI listing in active
     *
     * @param $filter
     */
    function fetchCredentialingUsersLIInActive($isPaginate = true, $filter = "", $sessionUserId)
    {
        if ($filter != "")
            return $this->inActivePracticesFilter($isPaginate, $filter, $sessionUserId);
        else
            return $this->inActivePractices($isPaginate, $sessionUserId);
    }
    /**
     * in active practice without filter
     */
    private function inActivePractices($isPaginate, $sessionUserId)
    {
        $tbl = "cm_" . $this->tbl;

        $key = $this->key;

        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $practices = $this->sessionActiveInActivePractices($sessionUserId, 1);
        // $this->printR($practices,true);
        $practiceIds = 0;
        if (count($practices)) {
            $practiceIdsArr = [];
            foreach ($practices as $practice) {
                $practiceIdsArr[] = $practice->facility_id;
            }
            $practiceIds = implode(",", $practiceIdsArr);
        }

        $sql = "SELECT *
        FROM   ((

                         SELECT t2.facility_id,
                                t2.doing_buisness_as,
                                t2.for_credentialing
                         FROM   (
                                         SELECT   cpi.user_id    AS facility_id,
                                         IFNULL(cpi.practice_name,cpi.doing_business_as)   AS doing_buisness_as,
                                                  pli.for_credentialing
                                         FROM     `$tbl` pli
                                         INNER JOIN `cm_user_baf_practiseinfo` cpi
                                        ON cpi.user_id = pli.user_parent_id
                                         WHERE    pli.user_parent_id = cpi.user_id
                                         AND pli.for_credentialing = '0'
                                         AND      pli.user_id NOT IN
                                                  (
                                                           SELECT   location_user_id
                                                           FROM     `cm_individualprovider_location_map`
                                                           GROUP BY location_user_id)
                                         ORDER BY cpi.doing_business_as) t2
                         WHERE  EXISTS
                                (
                                       SELECT 1
                                       FROM   `$tbl`
                                       WHERE  for_credentialing = '0'
                                       AND    user_parent_id = t2.facility_id))
           UNION ALL
                     (
                                SELECT     pli.user_parent_id    AS facility_id,
                                IFNULL(cpi.practice_name,cpi.doing_business_as) AS doing_buisness_as,
                                           pli.for_credentialing
                                FROM       `cm_individualprovider_location_map` plm
                                INNER JOIN `$tbl` pli
                                ON         pli.user_id = plm.location_user_id
                                INNER JOIN `cm_user_baf_practiseinfo` cpi
                                ON         cpi.user_id = pli.user_parent_id
                                WHERE      plm.for_credentialing = '0'
                                GROUP BY   pli.user_parent_id
                                ORDER BY   cpi.doing_business_as) ) AS T WHERE T.facility_id IN($practiceIds)
        ORDER BY  T.doing_buisness_as";

        $perPage = $this->cmperPage;

        // $totalRec = count($totallRec);
        //exit;
        $offset = $page - 1;

        // $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

        $newOffset = $perPage * $offset;
        if ($isPaginate)
            $sql .= " LIMIT $perPage OFFSET $newOffset";

        // echo $sql;exit;
        $asignedPractices = DB::select($sql);

        return ["practices" => $asignedPractices];
    }
    /**
     * in active practice without filter
     */
    private function inActivePracticesFilter($isPaginate, $filter, $sessionUserId)
    {
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $tbl = "cm_" . $this->tbl;

        $key = $this->key;
        $tblU = "cm_" . $this->tblU;
        $practices = $this->sessionActiveInActivePractices($sessionUserId, 1);
        $practiceIds = 0;
        if (count($practices)) {
            $practiceIdsArr = [];
            foreach ($practices as $practice) {
                $practiceIdsArr[] = $practice->facility_id;
            }
            $practiceIds = implode(",", $practiceIdsArr);
        }
        $sql = " SELECT result.practiceid AS facility_id , result.practicename AS doing_buisness_as, result.for_credentialing, result.is_visible, result.is_expandable
    FROM (
    SELECT T.practiceid, T.practicename, T.for_credentialing,
    if(T.is_expandable_facility > 0 OR T.is_expandable_provider > 0, '1', '0') as is_visible,
    if(T.is_expandable_facility > 0 OR T.is_expandable_provider > 0, '1', '0') as is_expandable
    FROM
    ((SELECT t2.practiceid, t2.practicename, t2.for_credentialing, NULL as is_visible,
       (SELECT if(COUNT(id) > 0, '1', '0')
       FROM `$tbl`
       WHERE for_credentialing = '0'
       AND AES_DECRYPT(practice_name,'$key') LIKE '%$filter%'
       AND user_parent_id = t2.practiceid
       AND user_id NOT IN (SELECT location_user_id FROM `cm_individualprovider_location_map` GROUP BY location_user_id)) as is_expandable_facility,
       (SELECT IF(COUNT(u.id) > 0, '1', '0')
       FROM `cm_individualprovider_location_map` plm
       INNER JOIN `$tblU` u
       ON u.id = plm.user_id
       INNER JOIN `$tbl` pli
       ON pli.user_id = plm.location_user_id
       WHERE plm.location_user_id IN (SELECT user_id FROM `$tbl` WHERE user_parent_id = t2.practiceid)
       AND plm.for_credentialing = '0'
       AND CONCAT(u.first_name, ' ',u.last_name) LIKE '%$filter%') as is_expandable_provider
    FROM
    (SELECT pli.user_parent_id as practiceid,   IFNULL(cpi.practice_name,cpi.doing_business_as) as practicename, pli.for_credentialing
    FROM `$tbl` pli
    INNER JOIN `cm_user_baf_practiseinfo` cpi
    ON cpi.user_id = pli.user_parent_id
    WHERE pli.user_parent_id = cpi.user_id
    AND pli.for_credentialing = '0'
    AND pli.user_id NOT IN (SELECT location_user_id FROM `cm_individualprovider_location_map` GROUP BY location_user_id)
    ORDER BY practicename) t2
    where exists (SELECT 1 FROM `$tbl` WHERE for_credentialing = '0' AND user_parent_id = t2.practiceid))
    UNION ALL
    SELECT t3.practiceid, t3.practicename, t3.for_credentialing, t3.is_visible,
          (SELECT IF(COUNT(pl.user_id) > 0, '1', '0') as count
           FROM `cm_individualprovider_location_map` plm
           INNER JOIN `$tbl` pl
           ON pl.user_id = plm.location_user_id
           WHERE plm.for_credentialing = '0'
           AND pl.user_parent_id = t3.practiceid
           AND AES_DECRYPT(pl.practice_name,'$key') LIKE '%$filter%'
           GROUP BY pl.user_parent_id) as is_expandable_facility,
           (SELECT IF(COUNT(u.id) > 0, '1', '0')
           FROM `cm_individualprovider_location_map` plm
           INNER JOIN `$tblU` u
           ON u.id = plm.user_id
           INNER JOIN `$tbl` pl
           ON pl.user_id = plm.location_user_id
           WHERE plm.location_user_id IN (SELECT user_id FROM `$tbl` WHERE user_parent_id = t3.practiceid)
           AND plm.for_credentialing = '0'
           AND CONCAT(u.first_name, ' ',u.last_name) LIKE '%$filter%') as is_expandable_provider
    FROM
    (SELECT pli.user_parent_id as practiceid, IFNULL(cpi.practice_name,cpi.doing_business_as) as practicename, pli.for_credentialing, NULL as is_visible
    FROM `cm_individualprovider_location_map` plm
    INNER JOIN `$tbl` pli
    ON pli.user_id = plm.location_user_id
    INNER JOIN `cm_user_baf_practiseinfo` cpi
    ON cpi.user_id = pli.user_parent_id
    WHERE plm.for_credentialing = '0'
    GROUP BY pli.user_parent_id
    ORDER BY cpi.doing_business_as) AS t3
    ) AS T
    ORDER BY T.practicename
    ) AS result WHERE result.practiceid IN($practiceIds)
    ORDER BY result.is_expandable DESC";
        // echo $sql;
        // exit;
        // $totallRec = DB::select($sql);

        $perPage = $this->cmperPage;

        // $totalRec = count($totallRec);
        //exit;
        $offset = $page - 1;

        // $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

        $newOffset = $perPage * $offset;
        if ($isPaginate)
            $sql .= " LIMIT $perPage OFFSET $newOffset";

        $asignedPractices = DB::select($sql);

        return ["practices" => $asignedPractices];
    }
    /**
     * get the crdentialing LI listing
     *
     * @param $filter
     */
    function allParentPractices($ids = [], $isActive = 0)
    {
        $tbl = "cm_" . $this->tbl;

        $key = $this->key;

        $ids = implode(",", $ids);
        if ($isActive == 1) {
            $sql = "SELECT *
            FROM   ((SELECT t2.facility_id,
                            t2.doing_buisness_as,
                            t2.for_credentialing
                    FROM   (SELECT user_parent_id AS facility_id,
                                    AES_DECRYPT(doing_buisness_as,'$key') as doing_buisness_as,
                                    '1'            AS for_credentialing
                            FROM   `$tbl`
                            WHERE  user_id = user_parent_id
                            ORDER  BY doing_buisness_as) t2
                    WHERE  EXISTS (SELECT 1
                                    FROM   `$tbl`
                                    WHERE  for_credentialing = '1'
                                        AND user_parent_id = t2.facility_id))) AS T WHERE T.facility_id IN($ids)
            ORDER  BY T.doing_buisness_as ";
        } else {

            $sql = "SELECT *
                FROM   ((

                                SELECT t2.facility_id,
                                        t2.doing_buisness_as,
                                        t2.for_credentialing
                                FROM   (
                                                SELECT   user_parent_id    AS facility_id,
                                                        AES_DECRYPT(doing_buisness_as,'$key') as doing_buisness_as ,
                                                        for_credentialing
                                                FROM     `$tbl`
                                                WHERE    user_id = user_parent_id
                                                AND      user_id NOT IN
                                                        (
                                                                SELECT   location_user_id
                                                                FROM     `cm_individualprovider_location_map`
                                                                GROUP BY location_user_id)
                                                ORDER BY doing_buisness_as) t2
                                WHERE  EXISTS
                                        (
                                            SELECT 1
                                            FROM   `$tbl`
                                            WHERE  for_credentialing = '0'
                                            AND    user_parent_id = t2.facility_id))
                UNION ALL
                            (
                                        SELECT     pli.user_parent_id    AS facility_id,
                                                AES_DECRYPT(pli.doing_buisness_as,'$key') as doing_buisness_as ,
                                                pli.for_credentialing
                                        FROM       `cm_individualprovider_location_map` plm
                                        INNER JOIN `$tbl` pli
                                        ON         pli.user_id = plm.location_user_id
                                        WHERE      plm.for_credentialing = '0'
                                        GROUP BY   pli.user_parent_id
                                        ORDER BY   pli.doing_buisness_as) ) AS T WHERE T.facility_id IN($ids)
                ORDER BY  T.doing_buisness_as";
        }
        // echo $sql;
        // exit;
        $practices = $this->rawQuery($sql);
        $pagination = $this->makePagination(1, $this->cmperPage, $this->cmperPage, 0);
        return ["practices" => $practices, "pagination" => $pagination];
    }
    /**
     * get the crdentialing LI listing
     *
     * @param $filter
     */
    function practiceORProviderSmartSearch($filter)
    {
        $tbl = "cm_" . $this->tbl;

        $key = $this->key;

        $tblU = "cm_" . $this->tblU;

        $sql = "SELECT *
        FROM(
        SELECT CONCAT(AES_DECRYPT(u.first_name,'$key'), ' ', AES_DECRYPT(u.last_name,'$key')) as username, plm.user_id as provider_id, pli.practice_name as facilityname, plm.location_user_id as facility_id,
        (SELECT AES_DECRYPT(doing_buisness_as,'$key') FROM $tbl WHERE user_parent_id = pli.user_parent_id GROUP BY user_parent_id) as practicename,
        pli.user_parent_id as practice_id
        FROM `cm_individualprovider_location_map` plm
        INNER JOIN `$tblU` u
        ON u.id = plm.user_id
        INNER JOIN `$tbl` pli
        ON pli.user_id = plm.location_user_id) AS T
        WHERE ((T.username LIKE '%$filter%') OR (T.facilityname LIKE '%$filter%'))
        GROUP BY T.facility_id, T.practice_id
        ORDER BY T.practice_id";
        return $this->rawQuery($sql);
    }


    /**
     * get the specific crdentialing li
     *
     * @param $filter
     */
    function fetchSpecificCredentialingUsersLI($sessionUserId = "", $filter = "", $isActive = 0)
    {
        $tbl = "cm_" . $this->tbl;

        $key = $this->key;

        $tblU = "cm_" . $this->tblU;

        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;
        $perPage = $this->cmperPage;
        $filterSql = $filter != "" ? "WHERE  T.doing_buisness_as LIKE '" . $filter . "%'" : "";
        $sql = "SELECT T.facility_id,T.doing_buisness_as
                FROM (SELECT
                        (
                        CASE WHEN ct.user_parent_id = '0'
                        THEN ct.user_id
                        ELSE ct.user_parent_id
                        END
                        ) AS facility_id,
                        (SELECT AES_DECRYPT(doing_buisness_as,'$key') FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as doing_buisness_as,
                        (SELECT user_parent_id FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as user_parent_id
                    FROM `cm_assignments` a
                    INNER JOIN cm_credentialing_tasks ct
                    ON ct.id = a.entity_id
                    WHERE a.user_id = '$sessionUserId'
                    AND a.entities = 'credentialingtask_id') as T
                    INNER JOIN `$tblU` cu
                    ON cu.id = T.facility_id AND cu.deleted = '$isActive'
                    $filterSql
                    GROUP BY T.user_parent_id
                    ORDER BY T.user_parent_id";
        $totallRec = DB::select($sql);
        $totalRec = count($totallRec);
        //exit;
        $offset = $page - 1;

        $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

        $newOffset = $perPage * $offset;

        $sql .= " LIMIT $perPage OFFSET $newOffset";

        $asignedPractice = DB::select($sql);

        return ["locations_users" => $asignedPractice, "pagination" => $pagination];
    }
    /**
     * Get the credentialing users
     *
     *
     * @param $parentId
     */
    function fetchCredentialingUsers($parentId = 0, $iActive = 0, $filter = "", $sessionUserId)
    {
        if ($filter != "")
            return $this->activeFacilitiesWithFilters($parentId, $iActive, $filter, $sessionUserId);
        else
            return $this->activeFacilities($parentId, $iActive, $sessionUserId);
    }
    /**
     * active facilities without filters
     *
     */
    function activeFacilities($parentId, $iActive, $sessionUserId)
    {
        $tbl = $this->tbl;

        $key = $this->key;

        $locations = DB::table($tbl)

            ->select([DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as,'$key') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_$tbl.practice_name,'$key') as practice_name"), "$tbl.user_id as facility_id"])

            // ->join("users", function ($join) use ($iActive) {
            //     $join->on("users.id", "=", "user_ddpracticelocationinfo.user_id")
            //         ->where("users.deleted", "=", $iActive);
            // });
            ->join('emp_location_map AS elp', 'elp.location_user_id', '=', 'user_ddpracticelocationinfo.user_id');

        $locations = $locations->where("user_ddpracticelocationinfo.for_credentialing", "=", $iActive)
            ->where('elp.emp_id', '=', $sessionUserId);
        if (is_array($parentId))
            $locations = $locations->whereIn("user_ddpracticelocationinfo.user_parent_id", $parentId);
        else
            $locations = $locations->where("user_ddpracticelocationinfo.user_parent_id", "=", $parentId);



        return $locations->orderByRaw("cm_$tbl.user_parent_id ASC,cm_$tbl.user_id ASC")

            ->get();
    }
    /**
     * active facilities with filters
     *
     */
    function activeFacilitiesWithFilters($parentId, $iActive, $filter, $sessionUserId)
    {
        $tbl = $this->tbl;

        $key = $this->key;
        $tblU = "cm_" . $this->tblU;

        $filterSql = $filter; //$filter != "" ? " LIKE '%$filter%' " : "";

        $parentId = is_array($parentId) ? implode(",", $parentId) : $parentId;


        $sql = "SELECT result.facilityid as facility_id, result.facilityname as practice_name , result.user_parent_id, result.is_expandable,
                if(result.is_expandable > 0, 1, result.is_visible) as is_visible
                FROM (
                SELECT T.facilityid, T.facilityname, T.user_parent_id,
                    (SELECT IF(COUNT(u.id) > 0, '1', '0')
                    FROM `cm_individualprovider_location_map` plm
                    INNER JOIN `$tblU` u
                    ON u.id = plm.user_id
                    INNER JOIN `cm_$tbl` pli
                    ON pli.user_id = plm.location_user_id
                    WHERE plm.location_user_id = T.facilityid
                    AND plm.for_credentialing = '1'
                    AND CONCAT(u.first_name, ' ',u.last_name) LIKE '%$filterSql%') as is_expandable,
                    IF(T.facilityname LIKE '%$filterSql%', '1', '0') as is_visible
                FROM(
                SELECT pli.user_id as facilityid, AES_DECRYPT(pli.practice_name,'$key') as facilityname, pli.user_parent_id
                FROM `cm_$tbl` pli
                inner join `cm_emp_location_map` elp on elp.location_user_id = pli.user_id
                WHERE pli.for_credentialing = '1'
                AND elp.emp_id = $sessionUserId
                AND pli.user_parent_id in ($parentId)
                ORDER BY pli.user_parent_id, pli.user_id
                ) AS T
                ) AS result";

        return $this->rawQuery($sql);
    }

    /**
     * Get the credentialing location
     *
     *
     * @param $parentId
     */
    function fetchCredentialingLocation($parentId = 0, $iActive = 0)
    {
        $tbl = $this->tbl;

        $key = $this->key;

        $locations = DB::table($tbl)

            ->select([DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as,'$key') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_$tbl.practice_name,'$key') as practice_name"), "$tbl.user_id as facility_id"]);


        $locations = $locations->where("user_id", "=", $parentId);



        return $locations->orderByRaw("cm_$tbl.user_parent_id ASC,cm_$tbl.user_id ASC")

            ->get();
    }
    /**
     * get active in active session user facility
     *
     * @param $parentId
     * @param $sessionUserId
     */
    function getActiveInactiveFacilities($parentId, $sessionUserId, $isArchived)
    {
        $tbl = "user_ddpracticelocationinfo";
        $tblU = "users";
        $appKey =  $this->key;

        $locations = DB::table($tbl . ' as pli')

            ->select([DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$appKey') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_pli.practice_name,'$appKey') as practice_name"), "pli.user_id as facility_id"]);

        $locations = $locations->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            $join->on('elm.location_user_id', '=', 'pli.user_id')
                ->where('elm.emp_id', '=', $sessionUserId);
        });
        $locations->join($tblU . " as u_facility", function ($join) use ($isArchived) {
            $join->on('u_facility.id', '=', 'pli.user_id')
                ->whereIn('u_facility.deleted', [0, 1]);
        });
        if (is_array($parentId))
            $locations = $locations->whereIn("pli.user_parent_id", $parentId);
        else
            $locations = $locations->where("pli.user_parent_id", "=", $parentId);



        return $locations->get();
    }
    /**
     * Get the credentialing users
     *
     *
     * @param $parentId
     */
    function fetchCredentialingUsersInActive($parentId = 0, $iActive = 0, $filter = "", $sessionUserId)
    {
        if ($filter != "")
            return $this->inActiveFacilityFiltered($parentId, $filter, $sessionUserId);
        else
            return $this->inActiveFacility($parentId, $iActive, $sessionUserId);
    }
    /**
     * in active facility without filtering
     */
    private function inActiveFacility($parentId, $isActive, $sessionUserId)
    {
        $tbl = "cm_" . $this->tbl;

        $key = $this->key;
        // print_r($parentId);

        $facilities = [];
        //foreach ($practiceIds as $practiceId)
        {
            $facilities[] = $this->getActiveInactiveFacilities($parentId, $sessionUserId, 1);
        }

        $facilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $facilityIds[] = $f->facility_id;
            }
        }
        $facilityIds = array_unique($facilityIds);

        $facilityIdsStr = implode(', ', $facilityIds);

        $parentId = is_array($parentId) && count($parentId) > 0 ? implode(",", $parentId) : $parentId;

        $sql = "SELECT T.facility_id, T.practice_name,T.doing_buisness_as,T.user_parent_id,T.user_id,T.for_credentialing
        FROM (
        (SELECT AES_DECRYPT(doing_buisness_as,'$key') as doing_buisness_as, AES_DECRYPT(practice_name,'$key') as practice_name , user_id as facility_id,user_parent_id,user_id,for_credentialing
        FROM `$tbl` pli
        -- INNER JOIN `cm_emp_location_map` elp ON elp.location_user_id = pli.user_id
        WHERE pli.for_credentialing = '0'
        -- AND elp.emp_id = $sessionUserId
        AND pli.user_id NOT IN (SELECT location_user_id FROM `cm_individualprovider_location_map` GROUP BY location_user_id)
        ORDER BY user_parent_id, user_id)
        UNION ALL
        (SELECT AES_DECRYPT(pli.doing_buisness_as,'$key') as doing_buisness_as,AES_DECRYPT(pli.practice_name,'$key') as practice_name,pli.user_id as facility_id,pli.user_parent_id,pli.user_id,pli.for_credentialing
        FROM `cm_individualprovider_location_map` plm
        INNER JOIN `$tbl` pli
        ON pli.user_id = plm.location_user_id
        -- INNER JOIN `cm_emp_location_map` elp ON elp.location_user_id = pli.user_id
        WHERE plm.for_credentialing = '0'
        -- AND elp.emp_id = $sessionUserId

        GROUP BY plm.location_user_id
        ORDER BY pli.user_parent_id, pli.user_id)
        ) AS T
        WHERE T.facility_id IN ($facilityIdsStr) AND T.user_parent_id IN( $parentId) ORDER BY T.user_parent_id, T.user_id";

        // echo $sql;
        // exit;
        return $this->rawQuery($sql);
    }
    /**
     * in active facility with filtering
     */
    private function inActiveFacilityFiltered($parentId, $filter, $sessionUserId)
    {
        $tbl = "cm_" . $this->tbl;

        $key = $this->key;

        $tblU = "cm_" . $this->tblU;

        $filterSql = $filter; //$filter !="" ? "LIKE '%$filter%'": "";

        $facilities = [];
        //foreach ($practiceIds as $practiceId)
        {
            $facilities[] = $this->getActiveInactiveFacilities($parentId, $sessionUserId, 1);
        }

        $facilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $facilityIds[] = $f->facility_id;
            }
        }
        $facilityIds = array_unique($facilityIds);

        $facilityIdsStr = implode(', ', $facilityIds);

        $sql = "SELECT result.facilityid as facility_id, result.facilityname as practice_name, result.user_parent_id, result.is_expandable,
        if(result.is_expandable > 0, 1, result.is_visible) as is_visible
        FROM(
        SELECT T.facilityid, T.facilityname, T.user_parent_id,
            (SELECT IF(COUNT(u.id) > 0, '1', '0')
            FROM `cm_individualprovider_location_map` plm
            INNER JOIN `$tblU` u
            ON u.id = plm.user_id
            INNER JOIN `$tbl` pli
            ON pli.user_id = plm.location_user_id
            WHERE plm.location_user_id = T.facilityid
            AND plm.for_credentialing = '0'
            AND CONCAT(u.first_name, ' ',u.last_name) LIKE '%$filterSql%') as is_expandable,
            IF(T.facilityname LIKE '%$filterSql%', '1', '0') as is_visible
        FROM (
        (SELECT user_id as facilityid, AES_DECRYPT(practice_name,'$key') as facilityname, user_parent_id, user_id
        FROM `$tbl` pli
        -- INNER JOIN `cm_emp_location_map` elp ON elp.location_user_id = pli.user_id
        WHERE pli.for_credentialing = '0'
        -- AND elp.emp_id = $sessionUserId
        AND user_id NOT IN (SELECT location_user_id FROM `cm_individualprovider_location_map` GROUP BY location_user_id)
        ORDER BY user_parent_id, user_id)
        UNION ALL
        (SELECT pli.user_id as facilityid, AES_DECRYPT(pli.practice_name,'$key') as facilityname, pli.user_parent_id, pli.user_id
        FROM `cm_individualprovider_location_map` plm
        INNER JOIN `$tbl` pli
        ON pli.user_id = plm.location_user_id
        -- INNER JOIN `cm_emp_location_map` elp ON elp.location_user_id = pli.user_id
        WHERE plm.for_credentialing = '0'
        -- AND elp.emp_id = $sessionUserId
        GROUP BY plm.location_user_id
        ORDER BY pli.user_parent_id, pli.user_id)
        ) AS T
        WHERE T.facilityid IN ($facilityIdsStr) AND T.user_parent_id = '$parentId'
        ORDER BY T.user_parent_id, T.user_id
        ) AS result";
        return $this->rawQuery($sql);
    }
    /**
     * Get the credentialing users
     *
     *
     * @param $parentId
     */
    function fetchCredentialingLocations($parentId = 0, $filter, $isActive = 0)
    {
        $tbl = $this->tbl;

        $key = $this->key;


        $locations = DB::table($tbl)

            ->select([DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as,'$key') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_$tbl.practice_name,'$key') as practice_name"), "$tbl.user_id as facility_id"])

            // ->join("users", function ($join) use ($isActive) {

            //     $join->on("users.id", "=", "user_ddpracticelocationinfo.user_id")
            //         ->where("users.deleted", "=", $isActive);
            // });
            ->where("for_credentialing", "=", $isActive);
        $locations = $locations->where("user_parent_id", "=", $parentId)
            ->where("practice_name", "LIKE", "%" . $filter . "%");

        return $locations->orderByRaw("cm_$tbl.user_parent_id ASC,cm_$tbl.user_id ASC")

            ->get();
    }
    /**
     * Get the credentialing users
     *
     *
     * @param $parentId
     */
    function fetchUserLocation($parentId = 0, $isActive = 0)
    {
        $tbl = $this->tbl;

        $key = $this->key;

        $locations = DB::table($tbl)

            ->select([DB::raw("AES_DECRYPT(cm_$tbl.doing_buisness_as,'$key') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_$tbl.practice_name,'$key') as practice_name"), "$tbl.user_id as facility_id"])

            ->join("users", function ($join) use ($isActive, $tbl) {

                $join->on("users.id", "=", "$tbl.user_id")
                    ->where("users.deleted", "=", $isActive);
            });

        $locations = $locations->where("user_id", "=", $parentId);
        //->where("practice_name", "LIKE", "%" . $filter . "%");

        return $locations->orderByRaw("cm_$tbl.user_parent_id ASC,cm_$tbl.user_id ASC")

            ->get();
    }
    /**
     * Get the specific crdentialing users
     */
    function fetchSpecificCredsUsers($sessionUserId, $parentId, $isActive = 0)
    {
        $tbl = $this->tbl;

        $key = $this->key;

        $sql = "SELECT *
                    FROM(
                        SELECT
                            (
                                CASE WHEN ct.user_parent_id = '0'
                                THEN ct.user_id
                                ELSE ct.user_parent_id
                                END
                            ) AS facility_id,
                            (SELECT AES_DECRYPT(practice_name,'$key') FROM cm_$tbl WHERE user_id = facility_id GROUP BY user_id) as practice_name,
                            (SELECT user_id FROM cm_$tbl WHERE user_id = facility_id GROUP BY user_id) as user_id,
                            (SELECT user_parent_id FROM cm_$tbl WHERE user_id = facility_id GROUP BY user_id) as user_parent_id
                    FROM `cm_assignments` a
                    INNER JOIN cm_credentialing_tasks ct
                    ON ct.id = a.entity_id
                    WHERE a.user_id = '$sessionUserId'
                    AND a.entities = 'credentialingtask_id') AS T
                    WHERE T.user_parent_id = '$parentId'
                    GROUP BY T.facility_id
                    ORDER BY T.user_parent_id, T.user_id
        ";

        return DB::select($sql);
    }
    /**
     * Get the specific crdentialing users
     */
    function fetchSpecificLinkedUsers($sessionUserId, $facilityId, $isActive = 0)
    {
        $tblU = $this->tblU;

        $sql = "(SELECT '$facilityId' as individual_id, '0' as facility_id, 'Facility' as name, NULL as first_name)
            UNION
            (SELECT T.individual_id, T.facility_id, T.name, T.first_name
            FROM (
            SELECT
            (
            CASE WHEN ct.user_parent_id = '0'
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
            (SELECT CONCAT(first_name, ' ', last_name) FROM $tblU WHERE id = individual_id) as name,
            (SELECT first_name FROM $tblU WHERE id = individual_id) as first_name
            FROM `cm_assignments` a
            INNER JOIN cm_credentialing_tasks ct
            ON ct.id = a.entity_id
            WHERE a.user_id = '$sessionUserId'
            AND a.entities = 'credentialingtask_id'
            ) AS T
            INNER JOIN $tblU u
            ON u.id = T.individual_id AND u.deleted = '$isActive'
            WHERE T.facility_id = '$facilityId')
            ORDER BY first_name";

        return DB::select($sql);

        //return ["practice" => $asignedPractice,"pagination" => $pagination];
    }
    /**
     * fetch credentialing tasks
     *
     * @param $userId
     */
    function fetchCredentialingTasks(
        $userId = 0,
        $filter = "",
        $parentId = "",
        $rangerFilter = [],
        $statusFilter = [],
        $assigneeFilter = "",
        $payerFilter = [],
        $facilityFilter = [],
        $providerFilter = [],
        $hasFacilityFilter = false,
        $nexlastFollowupCol = "",
        $nexlastFollowupVal = ""

    ) {
        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;
        // $start_time = microtime(true);
        $rangerFilter   = $rangerFilter         == ""   ? [] : $rangerFilter;
        $statusFilter   = $statusFilter         == ""   ? [] : $statusFilter;
        $payerFilter    = $payerFilter          == ""   ? [] : $payerFilter;
        $facilityFilter = $facilityFilter       == ""   ? [] : $facilityFilter;
        $providerFilter  = $providerFilter      == ""   ? [] : $providerFilter;

        // $lastFollowupFilterOprend = $lastFollowupFilter == 1 ? "DESC" : "ASC";

        // $nextFollowupFilterOprend = $nextFollowupFilter == 1 ? "DESC" : "ASC";
        $nexlastFollowupFilterOprendStr = $nexlastFollowupVal != "" && $nexlastFollowupCol != '' ? "M." . $nexlastFollowupCol . " " . $nexlastFollowupVal : "M.last_follow_up_date DESC";

        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $perPage = $this->cmperPage;

        $offset = $page - 1;

        $newOffset = $perPage * $offset;

        $isActive = isset($_REQUEST["is_active"]) ? $_REQUEST["is_active"] : 1;
        $isActivePractice = isset($_REQUEST["is_active"]) ? ($_REQUEST["is_active"] == 0 ? 1 : 0) : 0;


        $sessionUserId = $_REQUEST["session_userid"];

        $whereUserSession = "";
        if ($isActivePractice == 0) {

            $practices      = $this->sessionActivePractices($sessionUserId, $isActivePractice);

            // now get ids from the practicies...
            $practiceIds = [];
            foreach ($practices as $practice) {
                $practiceIds[] = $practice->facility_id;
            }

            // now get facilities by practice ids and user session id in a loop on practice ids...
            $facilities = [];
            foreach ($practiceIds as $practiceId) {
                $facilities[] = $this->getSpecificFacilities($practiceId, $sessionUserId, $isActivePractice);
            }

            $facilityIds = [];
            foreach ($facilities as $facility) {
                foreach ($facility as $f) {
                    $facilityIds[] = $f->facility_id;
                }
            }
            $facilityIds = array_unique($facilityIds);
            if (count($facilityIds)) {
                $facilityIdsStr = implode(', ', $facilityIds);
                $whereUserSession = "WHERE T.facility_id IN($facilityIdsStr)";
            } else {
                $whereUserSession = "WHERE T.facility_id = 0";
            }
        } else {
            $practices      = $this->sessionActiveInActivePractices($sessionUserId, $isActivePractice);

            // now get ids from the practicies...
            $practiceIds = [];
            foreach ($practices as $practice) {
                $practiceIds[] = $practice->facility_id;
            }
            $facilities = [];
            foreach ($practiceIds as $practiceId) {
                $facilities[] = $this->getActiveInactiveFacilities($practiceId, $sessionUserId, $isActivePractice);
            }

            $facilityIds = [];
            foreach ($facilities as $facility) {
                foreach ($facility as $f) {
                    $facilityIds[] = $f->facility_id;
                }
            }
            $facilityIds = array_unique($facilityIds);
            if (count($facilityIds)) {
                $facilityIdsStr = implode(', ', $facilityIds);
                $whereUserSession = "WHERE T.facility_id IN($facilityIdsStr)";
            } else {
                $whereUserSession = "WHERE T.facility_id = 0";
            }
            // $facilities[] = $this->getActiveInactiveFacilities($parentId,$sessionUserId,1);
        }

        // $statusCols = $userId !=0 ? "COUNT(M.creds_taskid) as count, M.credential_status as status,M.user_id,M.user_parent_id" : "COUNT(M.creds_taskid) as count, M.credential_status as status,M.user_id,M.user_parent_id";

        $requiredFilters = [
            "M.credential_status",
            "M.individual_dob", "M.individual_npi", "M.individual_ssn", "M.individual_type_of_professional",
            "M.individual_speciality", "M.facility_npi", "M.facility_tax", "M.facility_phone", "M.facility_specialty", "M.next_follow_up"
        ];


        $andCombination = ["M.provider", "M.payer", "M.practice"];
        $mainFilterStr = "";
        if ($filter != "") {
            $numOfWords = str_word_count($filter);
            if ($numOfWords == 2) {
                $andCombination1 = ["M.provider", "M.payer"];
                $andCombination2 = ["M.provider", "M.practice"];
                $andCombination3 = ["M.payer", "M.practice"];

                $andSqlStr1 = $this->sqlAndFilterString($filter, $andCombination1);
                $andSqlStr2 = $this->sqlAndFilterString($filter, $andCombination2);
                $andSqlStr3 = $this->sqlAndFilterString($filter, $andCombination3);

                $andSqlStr = "($andSqlStr1) OR ($andSqlStr2) OR ($andSqlStr3)";
            } else {
                $andSqlStr = $this->sqlAndFilterString($filter, $andCombination);
            }
            $filterStr = $this->sqlFilterString($filter, $requiredFilters);

            $mainFilterStr = " (($andSqlStr) OR ($filterStr))";
        }

        $additionalFilter = "";


        if ($userId == 0) {


            $assigneemoreFilter = "";
            $payermoreFilter = "";
            $facilitymoreFilter = "";
            $providermoreFilter = "";
            if ($assigneeFilter != "")
                $assigneemoreFilter = " AND M.assignee_userid = '$assigneeFilter'";
            if (count($payerFilter) > 0) {
                $payerFilter_ = implode(",", $payerFilter);
                $payermoreFilter = "AND M.payer_id IN($payerFilter_)";
            }
            if (count($facilityFilter) > 0 && $hasFacilityFilter == false) {
                $facilityFilter_ = implode(",", $facilityFilter);
                $facilitymoreFilter = "AND M.facility_id IN($facilityFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == false) {
                $providerFilter_ = implode(",", $providerFilter);
                $providermoreFilter = "AND M.individual_id IN($providerFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == true) {
                $providerFilter_ = implode(",", $facilityFilter);
                $providermoreFilter = "AND (M.user_parent_id = 0 AND M.user_id IN($providerFilter_) )";
            }
            // echo $providermoreFilter;
            // exit;
            // echo $providermoreFilter;
            // exit;
            if (count($statusFilter) > 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) AND $rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) > 0   && count($rangerFilter) == 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) == 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "($rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter)";
            } else {
                //alone filter of all types
                if (!$hasFacilityFilter) {
                    if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { // facility filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { // provider filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.individual_id IN($providerFilter_))";
                    }
                    //end alone selection here

                    //assignee combination
                    else if ($assigneeFilter != "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { // assignee and facility  filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { // assignee and provider  filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    }

                    //end assignee combination

                    //payer combination
                    else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  payer and facility filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  payer and assignee filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) ==  0 && count($providerFilter) > 0) { //  payer and provider filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_))";
                    }
                    //end payer combination

                    //facility filter combination

                    else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  facility and assignee filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  facility and payer filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.facility_id IN($facilityFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) ==  0 && count($providerFilter) > 0) { //  facility and provider filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.facility_id IN($facilityFilter_) AND M.individual_id IN($providerFilter_))";
                    }
                    //end facility filter combination

                    //provider filter combination
                    else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { //  provider and assignee filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { //  provider and payer filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) >  0 && count($providerFilter) > 0) { //  provider and facility filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.facility_id IN($facilityFilter_) AND M.individual_id IN($providerFilter_))";
                    }
                    //end provider filter combination
                    else if (count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // payer, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_))";
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // payer, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // provider, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        //$payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_)  AND M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    }
                    // print_r($facilityFilter);
                    // echo $additionalFilter;
                    // exit;
                } elseif ($hasFacilityFilter) {

                    if ($assigneeFilter != "" && count($payerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter') $providermoreFilter";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) $providermoreFilter)";
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter' $providermoreFilter)";
                    } elseif ($assigneeFilter == "" && count($payerFilter) == 0) {
                        $providerFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "(M.user_parent_id = 0 AND M.user_id IN($providerFilter_) )";
                    }
                }
            }

            if ($additionalFilter != "" && $mainFilterStr != "") {

                $additionalFilter .= " AND ";
            }
            $where = "WHERE M.payer_id IS NOT NULL ";
            if ($additionalFilter != "" || $mainFilterStr != "")
                $where .= " AND ";


            $sql = "SELECT M.creds_taskid,M.info_required, M.user_parent_id,M.user_id, M.facility_address,M.provider, M.practice, M.payer, M.payer_id, M.credential_status, M.credentialing_status_id, M.individual_id, M.individual_dob, M.individual_npi, M.individual_ssn, M.individual_type_of_professional, M.individual_speciality, M.facility_id, M.facility_npi, M.facility_tax, M.facility_phone, M.facility_specialty, M.next_follow_up, M.last_follow_up, M.created_date, M.approved_date, M.next_follow_up_date, M.last_follow_up_date,M.assignee_userid,M.assignee_username,M.assignee_filename,
            (
                SELECT COUNT(ctlog.id) FROM `cm_credentialing_task_logs` ctlog
                INNER JOIN `cm_attachments` atch
                on atch.entity_id = ctlog.id AND atch.entities ='credentialtasklog_id'
                WHERE ctlog.credentialing_task_id = M.creds_taskid
            ) as attachment_flag

            FROM
            (
            (SELECT T.creds_taskid,T.info_required, T.provider,T.user_parent_id,T.user_id,  T.facility_address ,T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, DATE_FORMAT(T.next_follow, '%m/%d/%Y') as next_follow_up, DATE_FORMAT(T.last_follow, '%m/%d/%y') as last_follow_up, T.created_date, T.approved_date, DATE_FORMAT(T.next_follow, '%Y-%m-%d') as next_follow_up_date, DATE_FORMAT(T.last_follow, '%Y-%m-%d') as last_follow_up_date, T.assignee_userid,T.assignee_username,T.assignee_filename
            FROM (SELECT
                    ct.id as creds_taskid, ct.info_required, ct.user_parent_id, ct.user_id, ct.credentialing_status_id, ct.assignee_user_id as assignee_userid, ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id, ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id, DATE_FORMAT(ct.updated_at, '%Y-%m-%d') as approved_date, pr.payer_name as payer, pr.id as payer_id, cs.credentialing_status as credential_status,
                    CONCAT(SUBSTRING( AES_DECRYPT(cu.dob,'$key'),6,2),'/', SUBSTRING(AES_DECRYPT(cu.dob,'$key'),9,2),'/',SUBSTRING(AES_DECRYPT(cu.dob,'$key'),1, 4) ) as individual_dob,
                        AES_DECRYPT(cu.facility_npi,'$key') as individual_npi,
                        AES_DECRYPT(cu.ssn,'$key') as individual_ssn,
                        cu.primary_speciality as individual_speciality,
                        pt.name as individual_type_of_professional,
                        AES_DECRYPT(pli.npi,'$key') as facility_npi,
                        AES_DECRYPT(pli.tax_id,'$key') as facility_tax,
                        AES_DECRYPT(pli.phone,'$key') as facility_phone,
                        pli.specialty as facility_specialty,
                        CONCAT_WS(', ', NULLIF(AES_DECRYPT(pli.practise_address,'$key'),''), NULLIF(pli.city, ''), NULLIF(pli.state, ''), NULLIF(pli.zip_code, '')) as facility_address,

                        (SELECT created_at FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow,
                        (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                        CONCAT(COALESCE(cu2.first_name,''),' ',COALESCE(cu2.last_name,'')) as assignee_username,
                        (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename,
                        CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE CONCAT(COALESCE(cu3.first_name,''), ' ',COALESCE(cu3.last_name,'')) END AS provider,
                        AES_DECRYPT(pli.practice_name,'$key') as practice,

                        CASE WHEN ct.credentialing_status_id = 3 THEN DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY)
                        ELSE (SELECT next_follow_up from cm_credentialing_task_logs where credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC	LIMIT 0,1)
                        END as next_follow
                    FROM `cm_credentialing_tasks` ct
                    LEFT join cm_payers pr on pr.id = ct.payer_id AND for_credentialing = 1
                    LEFT join cm_credentialing_status cs on cs.id = ct.credentialing_status_id
                    LEFT join cm_users cu on cu.id = CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END
                    LEFT join cm_users cu2 on cu2.id = ct.assignee_user_id
                    LEFT join cm_users cu3 on cu3.id = ct.user_id
                    LEFT JOIN `cm_professional_types` pt ON cu.professional_type_id = pt.id
                    LEFT join cm_user_ddpracticelocationinfo pli on pli.user_id = CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END
                    WHERE ct.user_parent_id = '0'
                ) AS T
                INNER JOIN `$tbl` pli
                ON pli.user_id = T.facility_id AND pli.for_credentialing = '$isActive'
               $whereUserSession
            )
            UNION ALL
            (SELECT T.creds_taskid,T.info_required, T.provider,T.user_parent_id,T.user_id,  T.facility_address ,T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, DATE_FORMAT(T.next_follow, '%m/%d/%Y') as next_follow_up, DATE_FORMAT(T.last_follow, '%m/%d/%y') as last_follow_up, T.created_date, T.approved_date, DATE_FORMAT(T.next_follow, '%Y-%m-%d') as next_follow_up_date, DATE_FORMAT(T.last_follow, '%Y-%m-%d') as last_follow_up_date, T.assignee_userid,T.assignee_username,T.assignee_filename

            FROM (SELECT

                ct.id as creds_taskid,
                ct.info_required,
                ct.user_parent_id,
                ct.user_id,
                ct.credentialing_status_id,
                ct.assignee_user_id as assignee_userid,
                ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id,
                ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id,
                DATE_FORMAT(ct.updated_at, '%Y-%m-%d') as approved_date,
                pr.payer_name as payer,
                pr.id as payer_id,
                cs.credentialing_status as credential_status,
                CONCAT(
                SUBSTRING( AES_DECRYPT(cu.dob,'$key'),6,2),
                '/',
                SUBSTRING(AES_DECRYPT(cu.dob,'$key'),9,2),
                '/',SUBSTRING(AES_DECRYPT(cu.dob,'$key'),1, 4)
                ) as individual_dob,
                AES_DECRYPT(cu.facility_npi,'$key') as individual_npi,
                AES_DECRYPT(cu.ssn,'$key') as individual_ssn,
                cu.primary_speciality as individual_speciality,
                pt.name as individual_type_of_professional,
                AES_DECRYPT(pli.npi,'$key') as facility_npi,
                AES_DECRYPT(pli.tax_id,'$key') as facility_tax,
                AES_DECRYPT(pli.phone,'$key') as facility_phone,
                pli.specialty as facility_specialty,
                CONCAT_WS(', ', NULLIF(AES_DECRYPT(pli.practise_address,'$key'),''), NULLIF(pli.city, ''), NULLIF(pli.state, ''), NULLIF(pli.zip_code, '')) as facility_address,

                (SELECT created_at FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow,
                (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                CONCAT(COALESCE(cu2.first_name,''),' ',COALESCE(cu2.last_name,'')) as assignee_username,
                (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename,
                CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE CONCAT(COALESCE(cu3.first_name,''), ' ',COALESCE(cu3.last_name,'')) END AS provider,
                AES_DECRYPT(pli.practice_name,'$key') as practice,

                CASE WHEN ct.credentialing_status_id = 3 THEN DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT next_follow_up from cm_credentialing_task_logs where credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC	LIMIT 0,1)
                END as next_follow
            FROM `cm_credentialing_tasks` ct
            left join cm_payers pr on pr.id = ct.payer_id AND pr.for_credentialing = 1
            left join cm_credentialing_status cs on cs.id = ct.credentialing_status_id
            left join cm_users cu on cu.id = CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END
            left join cm_users cu2 on cu2.id = ct.assignee_user_id
            left join cm_users cu3 on cu3.id = ct.user_id
            left JOIN `cm_professional_types` pt ON cu.professional_type_id = pt.id
            left join cm_user_ddpracticelocationinfo pli on pli.user_id = CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END
            WHERE ct.user_parent_id <> 0
                ) AS T
                INNER JOIN cm_individualprovider_location_map plm
                ON plm.location_user_id = T.facility_id AND plm.user_id = T.individual_id AND plm.for_credentialing = '$isActive'
                $whereUserSession
            )
            ) AS M
            $where $additionalFilter $mainFilterStr
            ORDER BY  $nexlastFollowupFilterOprendStr LIMIT $perPage OFFSET $newOffset";

            $credsTaks = DB::select($sql);

            return ["tasks" => $credsTaks];
        } elseif ($userId != 0) {
            // exit("HI");
            $hasProviderFilter = "";
            $assigneemoreFilter = "";
            $payermoreFilter = "";
            $facilitymoreFilter = "";
            $providermoreFilter = "";
            if ($assigneeFilter != "")
                $assigneemoreFilter = " AND M.assignee_userid = '$assigneeFilter'";
            if (count($payerFilter) > 0) {
                $payerFilter_ = implode(",", $payerFilter);
                $payermoreFilter = "AND M.payer_id IN($payerFilter_)";
            }
            if (count($facilityFilter) > 0 && $hasFacilityFilter == false) {
                $facilityFilter_ = implode(",", $facilityFilter);
                $facilitymoreFilter = "AND M.facility_id IN($facilityFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == false) {
                $providerFilter_ = implode(",", $providerFilter);
                $providermoreFilter = "AND M.individual_id IN($providerFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == true) {
                $providerFilter_ = implode(",", $facilityFilter);
                $providermoreFilter = "AND (M.user_parent_id = 0 AND M.user_id IN($providerFilter_) )";
            }
            // echo $hasFacilityFilter;
            // exit;
            // echo $providermoreFilter;
            // exit;
            if (count($statusFilter) > 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) AND $rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) > 0   && count($rangerFilter) == 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) == 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "($rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter)";
            } else {
                //alone filter of all types
                if (!$hasFacilityFilter) {

                    if ($assigneeFilter != "" && count($payerFilter) == 0 && count($providerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) == 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0  && count($providerFilter) > 0) { // provider filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    }
                    //end alone selection here

                    //assignee combination
                    else if ($assigneeFilter != "" && count($payerFilter) > 0  && count($providerFilter) == 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0  && count($providerFilter) > 0) { // assignee and provider  filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.assignee_userid = '$assigneeFilter')";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    }

                    //end assignee combination

                    //payer combination
                    else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) == 0) { //  payer and facility filter
                        //$facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) == 0) { //  payer and assignee filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) > 0) { //  payer and provider filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.payer_id IN($payerFilter_) AND M.user_id IN($providerFilter_))";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    }
                    //end payer combination



                    //provider filter combination
                    else if ($assigneeFilter != "" && count($payerFilter) == 0  && count($providerFilter) > 0) { //  provider and assignee filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.assignee_userid = '$assigneeFilter')";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) > 0) { //  provider and payer filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_))";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0  && count($providerFilter) > 0) { //  provider and facility filter

                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0  && count($providerFilter) > 0) { //  provider and facility filter

                        $providerFilter_ = implode(",", $providerFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "( M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // provider, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        //$payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_)  AND M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    }
                    //end provider filter combination

                } elseif ($hasFacilityFilter) {
                    if ($assigneeFilter != "" && count($payerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter')";
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_))";
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0) { // assignee and payer filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    }
                }
            }
            // echo $additionalFilter;
            // exit;
            if ($additionalFilter != "" && $mainFilterStr != "") {

                $additionalFilter .= " AND ";
            }

            $andOperendMain = "";
            if ($mainFilterStr != "" || $additionalFilter != "") {

                $andOperendMain = "AND";
            }

            if ($hasProviderFilter ==  "") {
                $operand = "OR";
                if ($parentId != $userId)
                    $operand = "AND";

                $parentCondition = " M.user_id ='$userId' $operand M.user_parent_id = '$parentId' AND M.payer_id IS NOT NULL";
            } else
                $parentCondition = "M.user_parent_id = '$parentId' AND $hasProviderFilter AND M.payer_id IS NOT NULL";


            $where = "WHERE M.payer_id IS NOT NULL ";
            if ($additionalFilter != "" || $mainFilterStr != "")
                $where .= " AND ";


            $providerCondition = '';
            if ($parentId == 0 || $parentId == $userId) {
                $providerCondition =  "WHERE  ($parentCondition) $andOperendMain $additionalFilter $mainFilterStr ORDER BY M.last_follow_up_date DESC LIMIT $perPage OFFSET $newOffset";
            } else {
                $providerCondition = " $where $additionalFilter AND(M.individual_id IN($userId)) ORDER BY  $nexlastFollowupFilterOprendStr LIMIT $perPage OFFSET $newOffset";
            }
            $sql = "SELECT M.user_id,M.user_parent_id,M.creds_taskid,M.info_required, M.provider,M.facility_address, M.practice, M.payer, M.payer_id, M.credential_status, M.credentialing_status_id, M.individual_id, M.individual_dob, M.individual_npi, M.individual_ssn, M.individual_type_of_professional, M.individual_speciality, M.facility_id, M.facility_npi, M.facility_tax, M.facility_phone, M.facility_specialty, M.next_follow_up, M.last_follow_up, M.created_date, M.approved_date, M.next_follow_up_date, M.last_follow_up_date,M.assignee_userid,M.assignee_username,M.assignee_filename,
                    (
                        SELECT COUNT(ctlog.id) FROM `cm_credentialing_task_logs` ctlog
                        INNER JOIN `cm_attachments` atch
                        on atch.entity_id = ctlog.id AND atch.entities ='credentialtasklog_id'
                        WHERE ctlog.credentialing_task_id = M.creds_taskid
                    ) as attachment_flag
            FROM
            (
            (SELECT T.user_id,T.user_parent_id,T.creds_taskid,T.info_required, T.provider,T.facility_address, T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, T.next_follow_up, T.last_follow_up, T.created_date, T.approved_date, T.next_follow_up_date, T.last_follow_up_date,T.assignee_userid,T.assignee_username,T.assignee_filename
            FROM (SELECT ct.user_id,ct.user_parent_id,ct.id as creds_taskid,ct.info_required, ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider, ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id) ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_parent_id) END ) AS practice, (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer, (SELECT id FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer_id,
                        (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
                        ct.credentialing_status_id,
                        ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id, (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob, (SELECT AES_DECRYPT(facility_npi,'$key') FROM $tblU WHERE id = individual_id) as individual_npi, (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn, (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional, (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality, ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id, (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id) as facility_npi, (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id) as facility_tax, (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id) as facility_phone, (SELECT specialty FROM $tbl WHERE user_id = facility_id) as facility_specialty,
                        CASE WHEN ct.credentialing_status_id = 3
                        THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
                        ELSE (SELECT DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
                        (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
                        (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                        DATE_FORMAT(ct.updated_at, '%Y-%m-%d') as approved_date,
                        CASE WHEN ct.credentialing_status_id = 3
                        THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
                        ELSE (SELECT DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                        (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date	,
                        ct.assignee_user_id as assignee_userid,
                        (SELECT  CONCAT_WS(', ',
                                NULLIF(AES_DECRYPT(practise_address, '$key'), ''),
                                NULLIF(city, ''),
                                NULLIF(state, ''),
                                NULLIF(zip_code, '')
                        ) FROM $tbl WHERE user_id = facility_id) as facility_address,
                        (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
                        (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename
                        FROM `cm_credentialing_tasks` ct
                        LEFT join cm_payers pr on pr.id = ct.payer_id AND for_credentialing = 1
                        LEFT join cm_credentialing_status cs on cs.id = ct.credentialing_status_id
                        LEFT join cm_users cu on cu.id = CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END
                        LEFT join cm_users cu2 on cu2.id = ct.assignee_user_id
                        LEFT join cm_users cu3 on cu3.id = ct.user_id
                        LEFT JOIN `cm_professional_types` pt ON cu.professional_type_id = pt.id
                        LEFT join cm_user_ddpracticelocationinfo pli on pli.user_id = CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END
                        WHERE ct.user_parent_id = '0') AS T
                        INNER JOIN `$tbl` pli
                        ON pli.user_id = T.facility_id AND pli.for_credentialing = '$isActive'
                        $whereUserSession
                        )
            UNION ALL
            (
                SELECT T.creds_taskid,T.info_required, T.provider,T.user_parent_id,T.user_id,T.facility_address, T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, T.next_follow_up, T.last_follow_up, T.created_date, T.approved_date, T.next_follow_up_date, T.last_follow_up_date,T.assignee_userid,T.assignee_username,T.assignee_filename

            FROM (SELECT ct.id as creds_taskid,ct.info_required,ct.user_parent_id,ct.user_id,
                  ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider,
                  ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id) ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_parent_id) END ) AS practice,
                  (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer,
                  (SELECT id FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer_id,
                  (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
                  ct.credentialing_status_id,
                           ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id,
                  (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob,
                  (SELECT AES_DECRYPT(facility_npi,'$key') FROM $tblU WHERE id = individual_id) as individual_npi,
                  (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn,
                  (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional,
                  (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality,
                  ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id,
                  (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id) as facility_npi,
                  (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id) as facility_tax,
                  (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id) as facility_phone,
                  (SELECT specialty FROM $tbl WHERE user_id = facility_id) as facility_specialty,
                  (SELECT  CONCAT_WS(', ',
                                        NULLIF(AES_DECRYPT(practise_address, '$key'), ''),
                                        NULLIF(city, ''),
                                        NULLIF(state, ''),
                                        NULLIF(zip_code, '')
                                ) FROM $tbl WHERE user_id = facility_id) as facility_address,
                           CASE WHEN ct.credentialing_status_id = 3
                           THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
                           ELSE (SELECT DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
                           (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
                           (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                           DATE_FORMAT(ct.updated_at, '%Y-%m-%d') as approved_date,
                           CASE WHEN ct.credentialing_status_id = 3
                           THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
                           ELSE (SELECT DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                           (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date,
                           ct.assignee_user_id as assignee_userid,
                        (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
                        (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename
                           FROM `cm_credentialing_tasks` ct
                            left join cm_payers pr on pr.id = ct.payer_id AND pr.for_credentialing = 1
                            left join cm_credentialing_status cs on cs.id = ct.credentialing_status_id
                            left join cm_users cu on cu.id = CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END
                            left join cm_users cu2 on cu2.id = ct.assignee_user_id
                            left join cm_users cu3 on cu3.id = ct.user_id
                            left JOIN `cm_professional_types` pt ON cu.professional_type_id = pt.id
                            left join cm_user_ddpracticelocationinfo pli on pli.user_id = CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END
                            WHERE ct.user_parent_id <> 0) AS T
                           INNER JOIN cm_individualprovider_location_map plm
                           ON plm.location_user_id = T.facility_id AND plm.user_id = T.individual_id AND plm.for_credentialing = '$isActive'
                           $whereUserSession

            )
            ) AS M
                $providerCondition";
            // WHERE  ($parentCondition) $andOperendMain $additionalFilter $mainFilterStr ORDER BY M.last_follow_up_date DESC LIMIT $perPage OFFSET $newOffset";
            // -- $where $additionalFilter AND(M.individual_id IN($userId))
            // -- ORDER BY  $nexlastFollowupFilterOprendStr LIMIT $perPage OFFSET $newOffset";




            // echo $sql;
            // exit;
            $credsTaks = DB::select($sql);



            return ["tasks" => $credsTaks];
        }
    }


    /**
     * fetch credentialing tasks
     *
     * @param $userId
     */
    function fetchCredentialingTasksStatus(
        $userId = 0,
        $filter = "",
        $parentId = "",
        $rangerFilter = [],
        $statusFilter = [],
        $assigneeFilter = "",
        $payerFilter = [],
        $facilityFilter = [],
        $providerFilter = [],
        $hasFacilityFilter = false

    ) {
        // $start_time = microtime(true);
        $rangerFilter   = $rangerFilter         == ""   ? [] : $rangerFilter;
        $statusFilter   = $statusFilter         == ""   ? [] : $statusFilter;
        $payerFilter    = $payerFilter          == ""   ? [] : $payerFilter;
        $facilityFilter = $facilityFilter       == ""   ? [] : $facilityFilter;
        $providerFilter  = $providerFilter      == ""   ? [] : $providerFilter;

        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;
        // $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $isActive = isset($_REQUEST["is_active"]) ? $_REQUEST["is_active"] : 1;

        $sessionUserId = $_REQUEST["session_userid"];

        $isActivePractice = isset($_REQUEST["is_active"]) ? ($_REQUEST["is_active"] == 0 ? 1 : 0) : 0;

        $practices      = $this->sessionActivePractices($sessionUserId, $isActivePractice);

        // now get ids from the practicies...
        $practiceIds = [];
        foreach ($practices as $practice) {
            $practiceIds[] = $practice->facility_id;
        }

        // now get facilities by practice ids and user session id in a loop on practice ids...
        $facilities = [];
        foreach ($practiceIds as $practiceId) {
            $facilities[] = $this->getSpecificFacilities($practiceId, $sessionUserId, $isActivePractice);
        }

        $facilityIds = [];
        foreach ($facilities as $facility) {
            foreach ($facility as $f) {
                $facilityIds[] = $f->facility_id;
            }
        }
        $facilityIds = array_unique($facilityIds);

        $facilityIdsStr = implode(', ', $facilityIds);
        $whereUserSession = "";
        if ($isActivePractice == 0) {
            if (count($facilityIds)) {
                $facilityIdsStr = implode(', ', $facilityIds);
                $whereUserSession = "WHERE T.facility_id IN($facilityIdsStr)";
            } else {
                $whereUserSession = "WHERE T.facility_id = 0";
            }
        }

        $statusCols = $userId != 0 ? "COUNT(M.creds_taskid) as count, M.credential_status as status,M.user_id,M.user_parent_id" : "COUNT(M.creds_taskid) as count, M.credential_status as status,M.user_id,M.user_parent_id";

        $requiredFilters = [
            "M.credential_status",
            "M.individual_dob", "M.individual_npi", "M.individual_ssn", "M.individual_type_of_professional",
            "M.individual_speciality", "M.facility_npi", "M.facility_tax", "M.facility_phone", "M.facility_specialty", "M.next_follow_up"
        ];


        $andCombination = ["M.provider", "M.payer", "M.practice"];
        $mainFilterStr = "";
        if ($filter != "") {
            $numOfWords = str_word_count($filter);
            if ($numOfWords == 2) {
                $andCombination1 = ["M.provider", "M.payer"];
                $andCombination2 = ["M.provider", "M.practice"];
                $andCombination3 = ["M.payer", "M.practice"];

                $andSqlStr1 = $this->sqlAndFilterString($filter, $andCombination1);
                $andSqlStr2 = $this->sqlAndFilterString($filter, $andCombination2);
                $andSqlStr3 = $this->sqlAndFilterString($filter, $andCombination3);

                $andSqlStr = "($andSqlStr1) OR ($andSqlStr2) OR ($andSqlStr3)";
            } else {
                $andSqlStr = $this->sqlAndFilterString($filter, $andCombination);
            }
            $filterStr = $this->sqlFilterString($filter, $requiredFilters);
            //$andSqlStr = $this->sqlAndFilterString($filter, $andCombination);
            $mainFilterStr = " (($andSqlStr) OR ($filterStr))";
        }

        $additionalFilter = "";
        // $perPage = $this->cmperPage;

        if ($userId == 0) {


            $assigneemoreFilter = "";
            $payermoreFilter = "";
            $facilitymoreFilter = "";
            $providermoreFilter = "";
            if ($assigneeFilter != "")
                $assigneemoreFilter = " AND M.assignee_userid = '$assigneeFilter'";
            if (count($payerFilter) > 0) {
                $payerFilter_ = implode(",", $payerFilter);
                $payermoreFilter = "AND M.payer_id IN($payerFilter_)";
            }
            if (count($facilityFilter) > 0 && $hasFacilityFilter == false) {
                $facilityFilter_ = implode(",", $facilityFilter);
                $facilitymoreFilter = "AND M.facility_id IN($facilityFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == false) {
                $providerFilter_ = implode(",", $providerFilter);
                $providermoreFilter = "AND M.individual_id IN($providerFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == true) {
                $providerFilter_ = implode(",", $facilityFilter);
                $providermoreFilter = "AND (M.user_parent_id = 0 AND M.user_id IN($providerFilter_) )";
            }
            // echo $hasFacilityFilter;
            // exit;
            // echo $providermoreFilter;
            // exit;
            if (count($statusFilter) > 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) AND $rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) > 0   && count($rangerFilter) == 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) == 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "($rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter)";
            } else {
                //alone filter of all types
                if (!$hasFacilityFilter) {
                    if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { // facility filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { // provider filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.individual_id IN($providerFilter_))";
                    }
                    //end alone selection here

                    //assignee combination
                    else if ($assigneeFilter != "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { // assignee and facility  filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { // assignee and provider  filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    }

                    //end assignee combination

                    //payer combination
                    else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  payer and facility filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  payer and assignee filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) ==  0 && count($providerFilter) > 0) { //  payer and provider filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_))";
                    }
                    //end payer combination

                    //facility filter combination

                    else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  facility and assignee filter
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  facility and payer filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $facilityFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.facility_id IN($facilityFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) ==  0 && count($providerFilter) > 0) { //  facility and provider filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.facility_id IN($facilityFilter_) AND M.individual_id IN($providerFilter_))";
                    }
                    //end facility filter combination

                    //provider filter combination
                    else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { //  provider and assignee filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { //  provider and payer filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) >  0 && count($providerFilter) > 0) { //  provider and facility filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.facility_id IN($facilityFilter_) AND M.individual_id IN($providerFilter_))";
                    }
                    //end provider filter combination
                    else if (count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // payer, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_))";
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // payer, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_) AND M.payer_id IN($payerFilter_) AND M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // provider, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        //$payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_)  AND M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    }
                } elseif ($hasFacilityFilter) {
                    if ($assigneeFilter != "" && count($payerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter') $providermoreFilter";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) $providermoreFilter)";
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter' $providermoreFilter)";
                    } elseif ($assigneeFilter == "" && count($payerFilter) == 0) {
                        $providerFilter_ = implode(",", $facilityFilter);
                        $additionalFilter = "(M.user_parent_id = 0 AND M.user_id IN($providerFilter_) )";
                    }
                }
            }

            if ($additionalFilter != "" && $mainFilterStr != "") {

                $additionalFilter .= " AND ";
            }
            $where = "WHERE M.payer_id IS NOT NULL ";
            if ($additionalFilter != "" || $mainFilterStr != "")
                $where .= " AND  ";



            $sql_ = "SELECT $statusCols
            FROM
            (
            (SELECT T.creds_taskid,T.user_parent_id,T.user_id, T.provider, T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, T.next_follow_up, T.last_follow_up, T.created_date, T.approved_date, T.next_follow_up_date, T.last_follow_up_date,T.assignee_userid,T.assignee_username,T.assignee_filename
            FROM (SELECT ct.id as creds_taskid,ct.user_parent_id,ct.user_id, ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider, ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id) ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_parent_id) END ) AS practice, (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer, (SELECT id FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer_id,
                           (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
                           ct.credentialing_status_id,
                           ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id, (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob, (SELECT AES_DECRYPT(facility_npi,'$key') FROM $tblU WHERE id = individual_id) as individual_npi, (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn, (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional, (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality, ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id, (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id) as facility_npi, (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id) as facility_tax, (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id) as facility_phone, (SELECT specialty FROM $tbl WHERE user_id = facility_id) as facility_specialty,
                           CASE WHEN ct.credentialing_status_id = 3
                           THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
                           ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
                           (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
                           (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                           DATE_FORMAT(updated_at, '%Y-%m-%d') as approved_date,
                           CASE WHEN ct.credentialing_status_id = 3
                           THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
                           ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                         (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date	,
                         ct.assignee_user_id as assignee_userid,
                        (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
                        (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename
                           FROM `cm_credentialing_tasks` ct
                           WHERE ct.user_parent_id = '0') AS T
                           INNER JOIN `$tbl` pli
                           ON pli.user_id = T.facility_id AND pli.for_credentialing = '$isActive'
                            $whereUserSession
                           )
            UNION ALL
            (SELECT T.user_parent_id,T.user_id,T.creds_taskid, T.provider, T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, T.next_follow_up, T.last_follow_up, T.created_date, T.approved_date, T.next_follow_up_date, T.last_follow_up_date,T.assignee_userid,T.assignee_username,T.assignee_filename

            FROM (SELECT ct.id as creds_taskid,ct.user_parent_id,ct.user_id,
                  ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider,
                  ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id) ELSE (SELECT practice_name FROM $tbl WHERE user_id = ct.user_parent_id) END ) AS practice,
                  (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer,
                  (SELECT id FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer_id,
                  (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
                  ct.credentialing_status_id,
                           ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id,
                  (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob,
                  (SELECT AES_DECRYPT(facility_npi,'$key') FROM $tblU WHERE id = individual_id) as individual_npi,
                  (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn,
                  (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional,
                  (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality,
                  ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id,
                  (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id) as facility_npi,
                  (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id) as facility_tax,
                  (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id) as facility_phone,
                  (SELECT specialty FROM $tbl WHERE user_id = facility_id) as facility_specialty,
                           CASE WHEN ct.credentialing_status_id = 3
                           THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
                           ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
                           (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
                           (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                           DATE_FORMAT(updated_at, '%Y-%m-%d') as approved_date,
                           CASE WHEN ct.credentialing_status_id = 3
                           THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
                           ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                           (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date,
                           ct.assignee_user_id as assignee_userid,
                        (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
                        (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename
                           FROM `cm_credentialing_tasks` ct
                            WHERE ct.user_parent_id <> 0) AS T
                           INNER JOIN cm_individualprovider_location_map plm
                           ON plm.location_user_id = T.facility_id AND plm.user_id = T.individual_id AND plm.for_credentialing = '$isActive'
                           $whereUserSession
                           )
            ) AS M
            $where $additionalFilter $mainFilterStr
            GROUP BY M.credentialing_status_id";



            $statusStats = DB::select($sql_);



            return ['status_stats' => $statusStats];
        } elseif ($userId != 0) {
            // exit("HI");
            $hasProviderFilter = "";
            $assigneemoreFilter = "";
            $payermoreFilter = "";
            $facilitymoreFilter = "";
            $providermoreFilter = "";
            if ($assigneeFilter != "")
                $assigneemoreFilter = " AND M.assignee_userid = '$assigneeFilter'";
            if (count($payerFilter) > 0) {
                $payerFilter_ = implode(",", $payerFilter);
                $payermoreFilter = "AND M.payer_id IN($payerFilter_)";
            }
            if (count($facilityFilter) > 0 && $hasFacilityFilter == false) {
                $facilityFilter_ = implode(",", $facilityFilter);
                $facilitymoreFilter = "AND M.facility_id IN($facilityFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == false) {
                $providerFilter_ = implode(",", $providerFilter);
                $providermoreFilter = "AND M.individual_id IN($providerFilter_)";
            }
            if (count($providerFilter) > 0 && $hasFacilityFilter == true) {
                $providerFilter_ = implode(",", $facilityFilter);
                $providermoreFilter = "AND (M.user_parent_id = 0 AND M.user_id IN($providerFilter_) )";
            }
            // echo $hasFacilityFilter;
            // exit;
            // echo $providermoreFilter;
            // exit;
            if (count($statusFilter) > 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) AND $rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) > 0   && count($rangerFilter) == 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $additionalFilter = "(M.credentialing_status_id IN($statusFilter_) $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
            } else if (count($statusFilter) == 0  && count($rangerFilter) > 0) {
                $statusFilter_ = implode(",", $statusFilter);
                $startDate = $rangerFilter["startDate"];
                $endDate = $rangerFilter["endDate"];
                $type = $rangerFilter["status"];
                $rangFilterStr = $type == "approved_date" ? "(M.approved_date BETWEEN '$startDate' AND '$endDate') AND M.credentialing_status_id = '3'" : "(M.$type BETWEEN '$startDate' AND '$endDate')";
                $additionalFilter = "($rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter)";
            } else {
                //alone filter of all types
                if (!$hasFacilityFilter) {

                    if ($assigneeFilter != "" && count($payerFilter) == 0 && count($providerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) == 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0  && count($providerFilter) > 0) { // provider filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    }
                    //end alone selection here

                    //assignee combination
                    else if ($assigneeFilter != "" && count($payerFilter) > 0  && count($providerFilter) == 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0  && count($providerFilter) > 0) { // assignee and provider  filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.assignee_userid = '$assigneeFilter')";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    }

                    //end assignee combination

                    //payer combination
                    else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) == 0) { //  payer and facility filter
                        //$facilityFilter_ = implode(",", $facilityFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_))";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) == 0) { //  payer and assignee filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) > 0) { //  payer and provider filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.payer_id IN($payerFilter_) AND M.user_id IN($providerFilter_))";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    }
                    //end payer combination



                    //provider filter combination
                    else if ($assigneeFilter != "" && count($payerFilter) == 0  && count($providerFilter) > 0) { //  provider and assignee filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.assignee_userid = '$assigneeFilter')";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0  && count($providerFilter) > 0) { //  provider and payer filter

                        $payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "(  M.payer_id IN($payerFilter_))";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0  && count($providerFilter) > 0) { //  provider and facility filter

                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0  && count($providerFilter) > 0) { //  provider and facility filter

                        $providerFilter_ = implode(",", $providerFilter);
                        $payerFilter_   = implode(",", $payerFilter);
                        $additionalFilter = "( M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                    } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // provider, facility and assignee filter

                        $facilityFilter_ = implode(",", $facilityFilter);
                        //$payerFilter_   = implode(",", $payerFilter);
                        $providerFilter_ = implode(",", $providerFilter);
                        $additionalFilter = "( M.facility_id IN($facilityFilter_)  AND M.individual_id IN($providerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                    }
                    //end provider filter combination

                } elseif ($hasFacilityFilter) {
                    if ($assigneeFilter != "" && count($payerFilter) == 0) { //assignee filter

                        $additionalFilter = "(M.assignee_userid = '$assigneeFilter')";
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    } else if ($assigneeFilter == "" && count($payerFilter) > 0) { //payer filter


                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_))";
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    } else if ($assigneeFilter != "" && count($payerFilter) > 0) { // assignee and payer filter

                        $payerFilter_ = implode(",", $payerFilter);
                        $additionalFilter = "(M.payer_id IN($payerFilter_) AND M.assignee_userid = '$assigneeFilter')";
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    } else if ($assigneeFilter == "" && count($payerFilter) == 0) { // assignee and payer filter
                        $providerFilter_ = implode(",", $providerFilter);
                        $hasProviderFilter = "M.user_id IN($providerFilter_)";
                        $parentId = 0;
                    }
                }
            }

            // exit;
            if ($additionalFilter != "" && $mainFilterStr != "") {

                $additionalFilter .= " AND ";
            }

            $andOperendMain = "";
            if ($mainFilterStr != "" || $additionalFilter != "") {

                $andOperendMain = "AND";
            }

            if ($hasProviderFilter ==  "") {
                $operand = "OR";
                if ($parentId != $userId)
                    $operand = "AND";

                $parentCondition = " M.user_id ='$userId' $operand M.user_parent_id = '$parentId' AND M.payer_id IS NOT NULL ";
            } else
                $parentCondition = "M.user_parent_id = '$parentId' AND $hasProviderFilter AND M.payer_id IS NOT NULL ";


            $sql_ = "SELECT $statusCols
        FROM
        (
        (SELECT T.user_id,T.user_parent_id,T.creds_taskid, T.provider, T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, T.next_follow_up, T.last_follow_up, T.created_date, T.approved_date, T.next_follow_up_date, T.last_follow_up_date,T.assignee_userid,T.assignee_username,T.assignee_filename
        FROM (SELECT ct.user_id,ct.user_parent_id,ct.id as creds_taskid, ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider, ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id) ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_parent_id) END ) AS practice, (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer, (SELECT id FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer_id,
                       (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
                       ct.credentialing_status_id,
                       ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id, (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob, (SELECT facility_npi FROM $tblU WHERE id = individual_id) as individual_npi, (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn, (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional, (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality, ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id, (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id) as facility_npi, (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id) as facility_tax, (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id) as facility_phone, (SELECT specialty FROM $tbl WHERE user_id = facility_id) as facility_specialty,
                       CASE WHEN ct.credentialing_status_id = 3
                       THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
                       ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
                       (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
                       (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                       DATE_FORMAT(updated_at, '%Y-%m-%d') as approved_date,
                       CASE WHEN ct.credentialing_status_id = 3
                       THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
                       ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                     (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date	,
                     ct.assignee_user_id as assignee_userid,
                    (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
                    (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename
                       FROM `cm_credentialing_tasks` ct
                       WHERE ct.user_parent_id = '0') AS T
                       INNER JOIN `$tbl` pli
                       ON pli.user_id = T.facility_id AND pli.for_credentialing = '$isActive'
                       $whereUserSession
                       )
        UNION ALL
        (SELECT T.user_id,T.user_parent_id,T.creds_taskid, T.provider, T.practice, T.payer, T.payer_id, T.credential_status, T.credentialing_status_id, T.individual_id, T.individual_dob, T.individual_npi, T.individual_ssn, T.individual_type_of_professional, T.individual_speciality, T.facility_id, T.facility_npi, T.facility_tax, T.facility_phone, T.facility_specialty, T.next_follow_up, T.last_follow_up, T.created_date, T.approved_date, T.next_follow_up_date, T.last_follow_up_date,T.assignee_userid,T.assignee_username,T.assignee_filename

        FROM (SELECT ct.user_id,ct.user_parent_id,ct.id as creds_taskid,
              ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider,
              ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id) ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_parent_id) END ) AS practice,
              (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer,
              (SELECT id FROM cm_payers WHERE id = ct.payer_id AND for_credentialing = 1) as payer_id,
              (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
              ct.credentialing_status_id,
                       ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id,
              (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob,
              (SELECT AES_DECRYPT(facility_npi,'$key') FROM $tblU WHERE id = individual_id) as individual_npi,
              (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn,
              (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional,
              (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality,
              ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id,
              (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id) as facility_npi,
              (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id) as facility_tax,
              (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id) as facility_phone,
              (SELECT specialty FROM $tbl WHERE user_id = facility_id) as facility_specialty,
                       CASE WHEN ct.credentialing_status_id = 3
                       THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
                       ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
                       (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
                       (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                       DATE_FORMAT(updated_at, '%Y-%m-%d') as approved_date,
                       CASE WHEN ct.credentialing_status_id = 3
                       THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
                       ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                       (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date,
                       ct.assignee_user_id as assignee_userid,
                    (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
                    (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename
                       FROM `cm_credentialing_tasks` ct
                        WHERE ct.user_parent_id <> 0) AS T
                       INNER JOIN cm_individualprovider_location_map plm
                       ON plm.location_user_id = T.facility_id AND plm.user_id = T.individual_id AND plm.for_credentialing = '$isActive'
                       $whereUserSession
                       )
        ) AS M
        WHERE ($parentCondition) $andOperendMain $additionalFilter $mainFilterStr GROUP BY M.credentialing_status_id";


            $statusStats = DB::select($sql_);



            return ['status_stats' => $statusStats];
        }
    }
    /**
     *
     */
    function fetchSpecificCredentialingTasks(
        $userId = 0,
        $filter = "",
        $parentId = "",
        $sessionUserId,
        $rangerFilter = [],
        $statusFilter = [],
        $assigneeFilter = "",
        $payerFilter = [],
        $facilityFilter = [],
        $providerFilter = []
    ) {
        $rangerFilter   = $rangerFilter         == ""   ? [] : $rangerFilter;
        $statusFilter   = $statusFilter         == ""   ? [] : $statusFilter;
        $payerFilter    = $payerFilter          == ""   ? [] : $payerFilter;
        $facilityFilter = $facilityFilter       == ""   ? [] : $facilityFilter;
        $providerFilter  = $providerFilter      == ""   ? [] : $providerFilter;
        //exit("here".$userId);
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $statusCols = "COUNT(T.creds_taskid) as count, T.credential_status as status";

        $requiredFilters = [
            "T.practice", "T.credential_status",
            "T.individual_dob", "T.individual_npi", "T.individual_ssn", "T.individual_type_of_professional",
            "T.individual_speciality", "T.facility_npi", "T.facility_tax", "T.facility_phone", "T.facility_specialty", "T.next_follow_up"
        ];

        $tbl = $this->tbl;

        $key = $this->key;

        $tblU = $this->tblU;

        $andCombination = ["T.provider", "T.payer"];
        $mainFilterStr = "";
        if ($filter != "") {
            $filterStr = $this->sqlFilterString($filter, $requiredFilters);
            $andSqlStr = $this->sqlAndFilterString($filter, $andCombination);
            $mainFilterStr = " (($andSqlStr) OR ($filterStr))";
        }

        $additionalFilter = "";

        $perPage = $this->cmperPage;
        $parentCondition = "";
        if ($userId != 0) {
            $operand = "OR";
            if ($parentId != $userId)
                $operand = "AND";

            $parentCondition = " AND (T.user_id ='$userId' $operand T.user_parent_id = '$parentId')";
        }
        $assigneemoreFilter = "";
        $payermoreFilter = "";
        $facilitymoreFilter = "";
        $providermoreFilter = "";
        if ($assigneeFilter != "")
            $assigneemoreFilter = " AND T.assignee_userid = '$assigneeFilter'";
        if (count($payerFilter) > 0) {
            $payerFilter_ = implode(",", $payerFilter);
            $payermoreFilter = "AND T.payer_id IN($payerFilter_)";
        }
        if (count($facilityFilter) > 0) {
            $facilityFilter_ = implode(",", $facilityFilter);
            $facilitymoreFilter = "AND T.facility_id IN($facilityFilter_)";
        }
        if (count($providerFilter) > 0) {
            $providerFilter_ = implode(",", $providerFilter);
            $providermoreFilter = "AND T.individual_id IN($providerFilter_)";
        }

        if (count($statusFilter) > 0  && count($rangerFilter) > 0) {
            $statusFilter_ = implode(",", $statusFilter);
            $startDate = $rangerFilter["startDate"];
            $endDate = $rangerFilter["endDate"];
            $type = $rangerFilter["status"];
            $rangFilterStr = $type == "approved_date" ? "(T.approved_date BETWEEN '$startDate' AND '$endDate') AND T.credentialing_status_id = '3'" : "(T.$type BETWEEN '$startDate' AND '$endDate')";
            $additionalFilter = "(T.credentialing_status_id IN($statusFilter_) AND $rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
        } else if (count($statusFilter) > 0   && count($rangerFilter) == 0) {
            $statusFilter_ = implode(",", $statusFilter);
            $additionalFilter = "(T.credentialing_status_id IN($statusFilter_) $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter) ";
        } else if (count($statusFilter) == 0  && count($rangerFilter) > 0) {
            $statusFilter_ = implode(",", $statusFilter);
            $startDate = $rangerFilter["startDate"];
            $endDate = $rangerFilter["endDate"];
            $type = $rangerFilter["status"];
            $rangFilterStr = $type == "approved_date" ? "(T.approved_date BETWEEN '$startDate' AND '$endDate') AND T.credentialing_status_id = '3'" : "(T.$type BETWEEN '$startDate' AND '$endDate')";
            $additionalFilter = "($rangFilterStr $assigneemoreFilter $payermoreFilter $facilitymoreFilter $providermoreFilter)";
        } else {
            //alone filter of all types
            if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { //assignee filter

                $additionalFilter = "(T.assignee_userid = '$assigneeFilter')";
            } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { //payer filter


                $payerFilter_ = implode(",", $payerFilter);
                $additionalFilter = "(T.payer_id IN($payerFilter_))";
            } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { // facility filter
                $facilityFilter_ = implode(",", $facilityFilter);
                $additionalFilter = "( T.facility_id IN($facilityFilter_))";
            } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { // provider filter
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "( T.individual_id IN($providerFilter_))";
            }
            //end alone selection here

            //assignee combination
            else if ($assigneeFilter != "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) == 0) { // assignee and payer filter

                $payerFilter_ = implode(",", $payerFilter);
                $additionalFilter = "(T.payer_id IN($payerFilter_) AND T.assignee_userid = '$assigneeFilter')";
            } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { // assignee and facility  filter
                $facilityFilter_ = implode(",", $facilityFilter);
                $additionalFilter = "( T.facility_id IN($facilityFilter_) AND T.assignee_userid = '$assigneeFilter')";
            } else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { // assignee and provider  filter
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "( T.individual_id IN($providerFilter_) AND T.assignee_userid = '$assigneeFilter')";
            }

            //end assignee combination

            //payer combination
            else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  payer and facility filter
                $facilityFilter_ = implode(",", $facilityFilter);
                $payerFilter_   = implode(",", $payerFilter);
                $additionalFilter = "( T.facility_id IN($facilityFilter_) AND T.payer_id IN($payerFilter_))";
            } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  payer and assignee filter

                $payerFilter_   = implode(",", $payerFilter);
                $additionalFilter = "(  T.payer_id IN($payerFilter_) AND T.assignee_userid = '$assigneeFilter')";
            } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) ==  0 && count($providerFilter) > 0) { //  payer and provider filter

                $payerFilter_   = implode(",", $payerFilter);
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "(  T.payer_id IN($payerFilter_) AND T.individual_id IN($providerFilter_))";
            }
            //end payer combination

            //facility filter combination

            else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  facility and assignee filter
                $facilityFilter_ = implode(",", $facilityFilter);
                $additionalFilter = "( T.facility_id IN($facilityFilter_) AND T.assignee_userid = '$assigneeFilter')";
            } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) == 0) { //  facility and payer filter

                $payerFilter_   = implode(",", $payerFilter);
                $facilityFilter_ = implode(",", $facilityFilter);
                $additionalFilter = "(  T.payer_id IN($payerFilter_) AND T.facility_id IN($facilityFilter_))";
            } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) ==  0 && count($providerFilter) > 0) { //  facility and provider filter

                $facilityFilter_ = implode(",", $facilityFilter);
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "(  T.facility_id IN($facilityFilter_) AND T.individual_id IN($providerFilter_))";
            }
            //end facility filter combination

            //provider filter combination
            else if ($assigneeFilter != "" && count($payerFilter) == 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { //  provider and assignee filter
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "( T.individual_id IN($providerFilter_) AND T.assignee_userid = '$assigneeFilter')";
            } else if ($assigneeFilter == "" && count($payerFilter) > 0 && count($facilityFilter) == 0 && count($providerFilter) > 0) { //  provider and payer filter

                $payerFilter_   = implode(",", $payerFilter);
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "(  T.payer_id IN($payerFilter_) AND T.individual_id IN($providerFilter_))";
            } else if ($assigneeFilter == "" && count($payerFilter) == 0 && count($facilityFilter) >  0 && count($providerFilter) > 0) { //  provider and facility filter

                $facilityFilter_ = implode(",", $facilityFilter);
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "(  T.facility_id IN($facilityFilter_) AND T.individual_id IN($providerFilter_))";
            }
            //end provider filter combination
            else if (count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // payer, facility and assignee filter

                $facilityFilter_ = implode(",", $facilityFilter);
                $payerFilter_   = implode(",", $payerFilter);
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "( T.facility_id IN($facilityFilter_) AND T.payer_id IN($payerFilter_) AND T.individual_id IN($providerFilter_))";
            } else if ($assigneeFilter != "" && count($payerFilter) > 0 && count($facilityFilter) > 0 && count($providerFilter) > 0) { // payer, facility and assignee filter

                $facilityFilter_ = implode(",", $facilityFilter);
                $payerFilter_   = implode(",", $payerFilter);
                $providerFilter_ = implode(",", $providerFilter);
                $additionalFilter = "( T.facility_id IN($facilityFilter_) AND T.payer_id IN($payerFilter_) AND T.individual_id IN($providerFilter_) AND T.assignee_userid = '$assigneeFilter')";
            }
        }

        if ($additionalFilter != "" && $mainFilterStr != "") {

            $additionalFilter .= " AND ";
        }
        $andOperendMain = "";
        if ($mainFilterStr != "" || $additionalFilter != "") {

            $andOperendMain = "AND";
        }
        // $filterStrSql = "";
        // if($filter !="") {
        //     $filterStr = $this->sqlFilterString($filter,$requiredFilters);
        //     $filterStrSql =" AND ($filterStr)";
        // }

        $sql = "SELECT * FROM (SELECT ct.id as creds_taskid,ct.user_id,ct.user_parent_id, ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider, ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id  GROUP BY user_id) ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_parent_id GROUP BY user_id) END ) AS practice, (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer, (SELECT id FROM cm_payers WHERE id = ct.payer_id) as payer_id,
                (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
                ct.credentialing_status_id,
                ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id, (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob, (SELECT AES_DECRYPT(facility_npi,'$key') FROM $tblU WHERE id = individual_id) as individual_npi, (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn, (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional, (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality, ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id, (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_npi, (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_tax, (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_phone, (SELECT specialty FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_specialty,
                CASE WHEN ct.credentialing_status_id = 3
                THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
                ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
                (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
            ct.assignee_user_id as assignee_userid,
                (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
                (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename,
                (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
                DATE_FORMAT(ct.updated_at, '%Y-%m-%d') as approved_date,
                CASE WHEN ct.credentialing_status_id = 3
                THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
                ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                                                (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date
                FROM `cm_credentialing_tasks` ct
                ) AS T
                INNER JOIN `cm_assignments` a
                ON a.entity_id = T.creds_taskid
                AND a.user_id = '$sessionUserId'
                AND a.entities = 'credentialingtask_id' $parentCondition $andOperendMain $additionalFilter $mainFilterStr
            ";

        $sql_ = "SELECT $statusCols FROM (SELECT ct.id as creds_taskid,ct.user_id,ct.user_parent_id, ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE (SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM $tblU WHERE id = ct.user_id) END ) AS provider, ( CASE WHEN ct.user_parent_id = '0' THEN (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_id  GROUP BY user_id) ELSE (SELECT AES_DECRYPT(practice_name,'$key') FROM $tbl WHERE user_id = ct.user_parent_id GROUP BY user_id) END ) AS practice, (SELECT payer_name FROM cm_payers WHERE id = ct.payer_id) as payer, (SELECT id FROM cm_payers WHERE id = ct.payer_id) as payer_id,
        (SELECT credentialing_status FROM cm_credentialing_status WHERE id = ct.credentialing_status_id) as credential_status,
        ct.credentialing_status_id,
        ( CASE WHEN ct.user_parent_id = '0' THEN NULL ELSE ct.user_id END ) AS individual_id, (SELECT CONCAT(SUBSTRING(AES_DECRYPT(dob,'$key'), 6, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 9, 2),'/',SUBSTRING(AES_DECRYPT(dob,'$key'), 1, 4)) FROM $tblU WHERE id = individual_id) as individual_dob, (SELECT AES_DECRYPT(facility_npi,'$key') FROM $tblU WHERE id = individual_id) as individual_npi, (SELECT AES_DECRYPT(ssn,'$key') FROM $tblU WHERE id = individual_id) as individual_ssn, (SELECT pt.name FROM $tblU u INNER JOIN cm_professional_types pt ON pt.id = u.professional_type_id WHERE u.id = individual_id) as individual_type_of_professional, (SELECT primary_speciality FROM $tblU WHERE id = individual_id) as individual_speciality, ( CASE WHEN ct.user_parent_id = '0' THEN ct.user_id ELSE ct.user_parent_id END ) AS facility_id, (SELECT AES_DECRYPT(npi,'$key') FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_npi, (SELECT AES_DECRYPT(tax_id,'$key') FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_tax, (SELECT AES_DECRYPT(phone,'$key') FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_phone, (SELECT specialty FROM $tbl WHERE user_id = facility_id GROUP BY user_id) as facility_specialty,
        CASE WHEN ct.credentialing_status_id = 3
        THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%m/%d/%Y')
        ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up,
        (SELECT DATE_FORMAT(created_at, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up,
        ct.assignee_user_id as assignee_userid,
        (SELECT CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,'')) FROM `$tblU` WHERE id = ct.assignee_user_id) as assignee_username,
        (SELECT field_value FROM `cm_attachments` WHERE entities = 'user_id' AND entity_id = ct.assignee_user_id) as assignee_filename,
        (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id LIMIT 0,1) as created_date,
        DATE_FORMAT(ct.updated_at, '%Y-%m-%d') as approved_date,
        CASE WHEN ct.credentialing_status_id = 3
        THEN DATE_FORMAT(DATE_SUB(ct.revalidation_date, INTERVAL 60 DAY), '%Y-%m-%d')
        ELSE (SELECT DATE_FORMAT(DATE_ADD(next_follow_up, INTERVAL 6 DAY), '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1) END AS next_follow_up_date,
                                        (SELECT DATE_FORMAT(created_at, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = ct.id ORDER BY id DESC LIMIT 0,1) as last_follow_up_date
        FROM `cm_credentialing_tasks` ct
        ) AS T
        INNER JOIN `cm_assignments` a
        ON a.entity_id = T.creds_taskid
        AND a.user_id = '$sessionUserId'
        AND a.entities = 'credentialingtask_id' $parentCondition $andOperendMain $additionalFilter $mainFilterStr GROUP BY T.credentialing_status_id
        ";
        $statusStats = DB::select($sql_);

        $totallRec = DB::select($sql);

        $totalRec = count($totallRec);

        $offset = $page - 1;

        $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);

        $newOffset = $perPage * $offset;

        $sql .= " LIMIT $perPage OFFSET $newOffset";

        $credsTaks = DB::select($sql);

        return ["tasks" => $credsTaks, "pagination" => $pagination, 'status_stats' => $statusStats];
    }
    /**
     * fetch linked provider of location user
     *
     * @param $locationUserId
     */
    function fetchLinkedUsers($locationUserId, $providerId = 0, $isActive = 0, $addUnion = 1, $filter = "")
    {
        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;

        if ($addUnion == 1) {
            $sqlStr = $filter != "" ? "WHERE T.name LIKE '%$filter%'" : "";
            if ($providerId == 0) {


                $sql = "SELECT *
                FROM (
                    (
                        select * from (select '$locationUserId' as individual_id, '' as first_name, 'Facility' as name, '0' as facility_id) t
                        where exists (select 1 FROM `cm_individualprovider_location_map` WHERE location_user_id = '$locationUserId' AND for_credentialing = '$isActive')
                    )
                    UNION ALL
                    (
                    SELECT u.id as individual_id, u.first_name, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name,  '$locationUserId' as facility_id
                    FROM `cm_individualprovider_location_map` plm
                    INNER JOIN `$tblU` u
                    ON u.id = plm.user_id
                    INNER JOIN `$tbl` pli
                    ON pli.user_id = plm.location_user_id
                    WHERE plm.location_user_id = '$locationUserId'
                    AND plm.for_credentialing = '$isActive'
                    )
                ) AS T
                $sqlStr
                ORDER BY T.first_name";
            } else {

                $sql = "SELECT *
                FROM (
                    (
                        select * from (select '$locationUserId' as individual_id, '' as first_name, 'Facility' as name, '0' as facility_id) t
                        where exists (select 1 FROM `cm_individualprovider_location_map` WHERE location_user_id = '$locationUserId' AND for_credentialing = '$isActive')
                    )
                UNION ALL
                    (
                    SELECT u.id as individual_id, u.first_name, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name,  '$locationUserId' as facility_id
                    FROM `cm_individualprovider_location_map` plm
                    INNER JOIN `$tblU` u
                    ON u.id = plm.user_id
                    INNER JOIN `$tbl` pli
                    ON pli.user_id = plm.location_user_id
                    WHERE plm.location_user_id = '$locationUserId' AND plm.user_id = '$providerId'
                    AND plm.for_credentialing = '$isActive'
                    )
                ) AS T
                $sqlStr
                ORDER BY T.first_name";
            }
        } else {
            $sqlStr = $filter != "" ? " AND CONCAT(u.first_name, ' ', u.last_name) LIKE '%$filter%'" : "";
            if ($providerId == 0) {

                $sql = "SELECT u.id as individual_id, u.first_name, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name,  '$locationUserId' as facility_id
                FROM `cm_individualprovider_location_map` plm
                INNER JOIN `$tblU` u
                ON u.id = plm.user_id
                INNER JOIN `$tbl` pli
                ON pli.user_id = plm.location_user_id
                WHERE plm.location_user_id = '$locationUserId'
                AND plm.for_credentialing = '$isActive' $sqlStr ORDER BY u.first_name ";
            } else {
                $sql = "SELECT u.id as individual_id, u.first_name, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name, '$locationUserId' as facility_id
                FROM `cm_individualprovider_location_map` plm
                INNER JOIN `$tblU` u
                ON u.id = plm.user_id
                INNER JOIN `$tbl` pli
                ON pli.user_id = plm.location_user_id
                WHERE plm.location_user_id = '$locationUserId' AND plm.user_id = '$providerId'
                AND plm.for_credentialing = '$isActive' $sqlStr ORDER BY u.first_name";
            }
        }

        return $this->rawQuery($sql);
    }
    /**
     * fetch users for filters of creds report
     *
     * @param $isActive
     * @param $locationId
     */
    function fetchReportSpecificProviders($locationUserId, $isActive, $specificUsers = "")
    {
        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;
        $andCond = "";
        if ($specificUsers != "")
            $andCond = " AND plm.user_id IN($specificUsers)";

        $sql = "SELECT u.id , u.first_name, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name
        FROM `cm_individualprovider_location_map` plm
        INNER JOIN `$tblU` u
        ON u.id = plm.user_id
        INNER JOIN `$tbl` pli
        ON pli.user_id = plm.location_user_id
        WHERE plm.location_user_id IN( $locationUserId) $andCond
        AND plm.for_credentialing = '$isActive' ORDER BY u.first_name";
        //exit($sql);
        return $this->rawQuery($sql);
    }
    /**
     * fetch users for filters of creds report
     *
     * @param $isActive
     * @param $locationId
     */
    function fetchReportProviders($locationUserId, $isActive, $specificUsers = "")
    {
        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;

        $andCond = "";
        if ($specificUsers != "")
            $andCond = " AND plm.user_id IN($specificUsers)";

        $sql = "SELECT u.id , u.first_name, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name
        FROM `cm_individualprovider_location_map` plm
        INNER JOIN `$tblU` u
        ON u.id = plm.user_id
        INNER JOIN `$tbl` pli
        ON pli.user_id = plm.location_user_id
        WHERE plm.location_user_id IN( $locationUserId) $andCond
        AND plm.for_credentialing = '$isActive' ORDER BY u.first_name";
        //exit($sql);
        return $this->rawQuery($sql);
    }
    /**
     * fetch linked provider of location user
     *
     * @param $locationUserId
     */
    function fetchAllUsers($isActive)
    {

        $tbl = "cm_" . $this->tbl;
        $key = $this->key;
        $tblU = "cm_" . $this->tblU;

        $sql = "
        select CONCAT(COALESCE($tblU.first_name,''),' ', COALESCE($tblU.last_name,'')) as name, `$tblU`.`id`,
        $tblU.first_name
         from `cm_individualprovider_location_map` inner join `$tblU` on `$tblU`.`id` =
        `cm_individualprovider_location_map`.`user_id` and `cm_individualprovider_location_map`.`for_credentialing` = '$isActive'
        GROUP BY id order by `first_name` asc ";

        return $this->rawQuery($sql);
    }
    /**
     * fetch the location providers
     *
     * @param $locationId
     */
    function fetchProvider($locationUserId)
    {
        // $tblU = "cm_" . $this->tblU;
        // $sql = "
        //     select CONCAT(COALESCE($tblU.first_name,''),' ', COALESCE($tblU.last_name,'')) as name, `$tblU`.`id` as `individual_id`, '$locationUserId' as facility_id,
        //         $tblU.first_name
        //         from `cm_individualprovider_location_map` inner join `$tblU` on `$tblU`.`id` =
        //         `cm_individualprovider_location_map`.`user_id` and `$tblU`.`deleted` = 0 where
        //         `cm_individualprovider_location_map`.`location_user_id` = '$locationUserId'
        //     order by `first_name` asc";
        // echo $sql;
        // exit;
        // return $this->rawQuery($sql);
        $users = DB::table('individualprovider_location_map')
        ->join('users', function($join) {
            $join->on('users.id', '=', 'individualprovider_location_map.user_id')
                ->where('users.deleted', '=', 0);
        })
        ->where('individualprovider_location_map.location_user_id', '=', $locationUserId)
        ->where('individualprovider_location_map.for_credentialing', '=', 1)
        ->selectRaw("CONCAT(COALESCE(cm_users.first_name, ''), ' ', COALESCE(cm_users.last_name, '')) as name, cm_users.id as individual_id, '$locationUserId' as facility_id, cm_users.first_name")
        ->orderBy('users.first_name', 'asc')
        ->get();

        return $users;
    }
    /**
     * fetch the All locations providers
     *
     */
    function fetchAllProviders($locationUserIds)
    {
        $tblU = "cm_" . $this->tblU;
        $sql = "
            (SELECT '0' as id, 'Facility' as name, NULL as first_name)
            UNION
            select  `$tblU`.`id`,CONCAT(COALESCE($tblU.first_name,''),' ', COALESCE($tblU.last_name,'')) as name,
            $tblU.first_name
            from `cm_individualprovider_location_map`
            inner join `$tblU`
            on `$tblU`.`id` = `cm_individualprovider_location_map`.`user_id`
            and `cm_individualprovider_location_map`.`for_credentialing` = 1 where
            `cm_individualprovider_location_map`.`location_user_id` IN($locationUserIds)
        group by `$tblU`.`id`
        order by `first_name` asc";


        return $this->rawQuery($sql);
    }
    /**
     * fetch the All locations providers
     *
     */
    function fetchFacilityProviders($locationUserId, $isActive)
    {
        $tblU = "cm_" . $this->tblU;
        $tbl = "cm_" . $this->tbl;
        $sql = "SELECT *
                FROM (
                    (
                        select * from (select '$locationUserId' as id, '' as first_name, 'Facility' as name) t
                        where exists (select 1 FROM `cm_individualprovider_location_map` WHERE location_user_id IN( $locationUserId) AND for_credentialing = '$isActive')
                    )
                    UNION ALL
                    (
                    SELECT u.id , u.first_name, CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as name
                    FROM `cm_individualprovider_location_map` plm
                    INNER JOIN `$tblU` u
                    ON u.id = plm.user_id
                    INNER JOIN `$tbl` pli
                    ON pli.user_id = plm.location_user_id
                    WHERE plm.location_user_id IN( $locationUserId)
                    AND plm.for_credentialing = '$isActive'
                    )
                ) AS T
                GROUP BY T.name ORDER BY T.first_name ";


        // echo $locationUserId;
        // exit;
        return $this->rawQuery($sql);
    }


    /**
     * add the All data locations reimbursementRate.
     *
     */

    function addCoverage($addData)
    {
        return DB::table("reimbursement_rate")->insertGetId($addData);
    }

    /**
     * fetch the All data locations reimbursementRate
     *
     */

    public function fetchReimberceData($taskId)
    {

        return DB::table("reimbursement_rate")

            ->select("reimbursement_rate.*", "cptcode_types.description")

            ->join('cptcode_types', 'cptcode_types.cpt_code', '=', 'reimbursement_rate.cpt_code')

            ->where("credentialing_task_id", "=", $taskId)

            ->get();
    }
    /**
     * fetch the All data locations reimbursementRate
     *
     */

    public function fetchReimberceGroupData($facilityId, $taskId)
    {

        return DB::table("reimbursement_rate")

            ->select("reimbursement_rate.*", "cptcode_types.description")

            ->join('cptcode_types', 'cptcode_types.cpt_code', '=', 'reimbursement_rate.cpt_code')

            //s->where("credentialing_task_id","=",$taskId)

            ->where("facility_id", "=", $facilityId)

            ->where("individual_id", "=", 0)

            ->get();
    }
    /**
     * add the All data locations cptcodeTypes.
     *
     */

    function addCptCode($addCptData)
    {
        return DB::table("cptcode_types")->insertGetId($addCptData);
    }


    /**
     * fetch the All data locations cptcodeTypes
     *
     */
    public function CptCodeType()
    {
        return DB::table("cptcode_types")->get();
    }
    /**
     * fetch the credentialing last approved task data
     *
     * @param $taskId
     */
    function fetchLastApprovedTaskData($taskId)
    {
        $sql = "SELECT  ct.id as credentialing_task_id,ct.Identifier,ct.effective_date,DATE_FORMAT(ct.revalidation_date,'%Y-%m-%d') as revalidation_date,ct.contract_type,
        (SELECT attch.field_value FROM cm_credentialing_task_logs ctl
        LEFT JOIN cm_attachments attch
         ON attch.entity_id = ctl.id AND attch.entities = 'credentialtasklog_id'
         WHERE ctl.credentialing_task_id = ct.id AND ctl.credentialing_status_id = '3' ORDER BY ctl.created_at DESC LIMIT 0,1) as file_name,
        (SELECT ctl.id FROM cm_credentialing_task_logs ctl
         WHERE ctl.credentialing_task_id = ct.id AND ctl.credentialing_status_id = '3' ORDER BY ctl.created_at DESC LIMIT 0,1) as log_id
        FROM `cm_credentialing_tasks` ct

        WHERE ct.id = '$taskId'";

        return $this->rawQuery($sql);
    }
    /**
     * update the re-imbercement status
     *
     * @param $where
     * @param $updateData
     */
    function updateReImbercement($where, $updateData)
    {

        return DB::table("reimbursement_rate")

            ->where($where)

            ->update($updateData);
    }

    public function fetchActiveFacilitiesOfUser($sessionUserId)
    {
        $practices   = $this->sessionActivePractices($sessionUserId, 0)->toArray();
        $practiceIds = array_column($practices, 'facility_id');

        $facilities = $this->getSpecificFacilities($practiceIds, $sessionUserId, 0)->toArray();
        $facilityIds = array_column($facilities, 'facility_id');
        return $facilityIds;
    }

    public function fetchActiveFacilitiesAndPracticeOfUser($sessionUserId)
    {
        $practices   = $this->sessionActivePractices($sessionUserId, 0)->toArray();
        $practiceIds = array_column($practices, 'facility_id');

        $facilities = $this->getSpecificFacilities($practiceIds, $sessionUserId, 0)->toArray();
        $facilityIds = array_column($facilities, 'facility_id');
        return ['facility' => $facilityIds, 'practices' => $practiceIds];
    }

    public function fetchActiveFacilitiesAndProviderOfUser($sessionUserId)
    {

        //Fetch All Practice
        $practices   = $this->sessionActivePractices($sessionUserId, 0)->toArray();
        $practiceIds = array_column($practices, 'facility_id');


        //Fetch All Facility
        $tbl = "user_ddpracticelocationinfo";
        $tblU = "users";
        $appKey =  $this->key;
        $facilities = DB::table($tbl . ' as pli')
        ->select([ 
            DB::raw("AES_DECRYPT(cm_pli.practice_name,'$appKey') as user_name"),
            "pli.user_id as user_id",
            DB::raw("'facility' as type")
        ]);
        $facilities = $facilities->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            $join->on('elm.location_user_id', '=', 'pli.user_id')
                ->where('elm.emp_id', '=', $sessionUserId);
        });
        $facilities->join($tblU . " as u_facility", function ($join)  {
            $join->on('u_facility.id', '=', 'pli.user_id')
                ->where('u_facility.deleted', '=', 0);
        });
        $facilities = $facilities->whereIn("pli.user_parent_id", $practiceIds)->get()->toArray();
    
        //Fetch All Providers
        $facilityIds = array_column($facilities, 'user_id');
        $providers = DB::table("individualprovider_location_map")
        ->select(
            "individualprovider_location_map.user_id as user_id",
            DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as user_name"),
            DB::raw("'provider' as type")
        )
        ->join("users", "users.id", "=", "individualprovider_location_map.user_id")
        ->groupby('user_id')
        ->whereIn("individualprovider_location_map.location_user_id",  $facilityIds)
        ->get()->toArray();

        
        return ['facility' => $facilities, 'providers' => $providers];
    }


    /**
     * Fetch Credentialing tasks Status Using Orm Way
     *
     * @param $sessionUserId
     */
    public function creditinalingTaskStatusOrm($sessionUserId, $statusFilter = [], $payerFilter = [], $facilityFilter = [], $providerFilter = [], $hasFacilty = false, $assigneeFilter = '', $filter = "", $rangerFilter = [])
    {
        $statusFilter   = $statusFilter         == ""   ? [] : $statusFilter;
        $payerFilter    = $payerFilter          == ""   ? [] : $payerFilter;
        $facilityFilter = $facilityFilter       == ""   ? [] : $facilityFilter;
        $providerFilter  = $providerFilter      == ""   ? [] : $providerFilter;
        $rangerFilter   = $rangerFilter         == ""   ? [] : $rangerFilter;
        $facilityIds = $this->fetchActiveFacilitiesOfUser($sessionUserId);

        $key = env('AES_KEY');
        $credentialing = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

            ->Join('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                    ->where('pli.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', 0)
            ->groupby('credentialing_status_id')
            ->where('pr.id', '!=', null);


        $credentialingFacility = Credentialing::select(
            DB::raw("COUNT(cm_credentialing_tasks.id) as count"),
            DB::raw("cm_credentialing_tasks.user_id"),
            DB::raw("cm_credentialing_tasks.user_parent_id"),
            'cs.credentialing_status AS credential_status',
        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
            ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
            })
            ->Join('individualprovider_location_map as plm', function ($join) {
                $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                    ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', '<>', 0)
            ->groupby('credentialing_status_id')
            ->where('pr.id', '!=', null);


        if (count($facilityFilter) > 0 && $hasFacilty == false) {
            $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityFilter);
            $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityFilter);
        } else {
            $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
            $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
        }

        if (count($statusFilter) > 0) {
            $credentialing->whereIn('credentialing_tasks.credentialing_status_id', $statusFilter);
            $credentialingFacility->whereIn('credentialing_tasks.credentialing_status_id', $statusFilter);
        }
        if (count($payerFilter) > 0) {
            $credentialing->whereIn('pr.id', $payerFilter);
            $credentialingFacility->whereIn('pr.id', $payerFilter);
        }
        if (count($providerFilter) > 0) {

            if ($hasFacilty) {
                $credentialing->whereIn("credentialing_tasks.user_id", $facilityFilter)->where("credentialing_tasks.user_parent_id", 0);
                $credentialingFacility->whereIn("credentialing_tasks.user_id", $facilityFilter)->where("credentialing_tasks.user_parent_id", 0);
            } else {
                $credentialing->whereIn(DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END)'), $providerFilter);
                $credentialingFacility->whereIn(DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END)'), $providerFilter);
            }
        }
        if (count($rangerFilter) > 0) {
            $startDate = $rangerFilter["startDate"];
            $endDate = $rangerFilter["endDate"];
            $type = $rangerFilter["status"];
            if ($type == "approved_date") {
                $credentialing->whereBetween(DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d")'), [$startDate, $endDate])->where("credentialing_tasks.credentialing_status_id", 3);
                $credentialingFacility->whereBetween(DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d")'), [$startDate, $endDate])->where("credentialing_tasks.credentialing_status_id", 3);
            } else if ($type == "next_follow_up_date") {
                $credentialing->whereBetween(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                        ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1)
                        END
                    "), [$startDate, $endDate]);

                $credentialingFacility->whereBetween(DB::raw("
                        CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                        ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1)
                        END
                    "), [$startDate, $endDate]);
            } else if ($type == "last_follow_up_date") {
                $credentialing->whereBetween(DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), [$startDate, $endDate]);
                $credentialingFacility->whereBetween(DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), [$startDate, $endDate]);
            } else if ($type == "created_date") {
                $credentialing->whereBetween(DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1)'), [$startDate, $endDate]);
                $credentialingFacility->whereBetween(DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1)'), [$startDate, $endDate]);
            }
        }
        if ($assigneeFilter != "") {
            $credentialing->where('credentialing_tasks.assignee_user_id', $assigneeFilter);
            $credentialingFacility->where('credentialing_tasks.assignee_user_id', $assigneeFilter);
        }
        if ($filter != "") {
            $credentialing->where(DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END "), 'like', '%' . $filter . '%')
                ->orWhere('pr.payer_name', 'like', '%' . $filter . '%')
                ->orWhere("pt.name", 'like', '%' . $filter . '%')
                ->orWhere("pli.specialty", 'like', '%' . $filter . '%')
                ->orWhere("cu.primary_speciality", 'like', '%' . $filter . '%')
                ->orWhere("cs.credentialing_status", 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.ssn, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.phone, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                        CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                        ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                        END
                    "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                        CONCAT(
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                        )
                    "), 'like', '%' . $filter . '%');

            $credentialingFacility->where(DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END "), 'like', '%' . $filter . '%')
                ->orWhere('pr.payer_name', 'like', '%' . $filter . '%')
                ->orWhere("pt.name", 'like', '%' . $filter . '%')
                ->orWhere("pli.specialty", 'like', '%' . $filter . '%')
                ->orWhere("cu.primary_speciality", 'like', '%' . $filter . '%')
                ->orWhere("cs.credentialing_status", 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.ssn, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.phone, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                        CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                        ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                        END
                    "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                        CONCAT(
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                        )
                    "), 'like', '%' . $filter . '%');
        }


        $results = $credentialing->union($credentialingFacility)->get()->toArray();
        $resultsArr = [];
        foreach ($results as $key => $value) {
            if (isset($resultsArr[$value['credential_status']])) {
                $resultsArr[$value['credential_status']]["count"] += $value['count'];
            } else {
                $resultsArr[$value['credential_status']]["count"] = $value['count'];
                $resultsArr[$value['credential_status']]["status"] = $value['credential_status'];
                $resultsArr[$value['credential_status']]["user_id"] = $value['user_id'];
                $resultsArr[$value['credential_status']]["user_parent_id"] = $value['user_parent_id'];
            }
        }
        $resultsArr = array_values($resultsArr);
        return ["status_stats" => $resultsArr];
    }

    /**
     * Fetch Credentialing tasks Using Orm Way
     *
     * @param $sessionUserId
     */
    public function fetchCredentialingOrm($sessionUserId, $credentialing_task_id = null, $statusFilter = [], $payerFilter = [], $facilityFilter = [], $providerFilter = [], $hasFacilty = false, $assigneeFilter = '', $filter = "", $rangerFilter = [], $nexlastFollowupCol = "", $nexlastFollowupVal = "")
    {
        $statusFilter   = $statusFilter         == ""   ? [] : $statusFilter;
        $payerFilter    = $payerFilter          == ""   ? [] : $payerFilter;
        $facilityFilter = $facilityFilter       == ""   ? [] : $facilityFilter;
        $providerFilter  = $providerFilter      == ""   ? [] : $providerFilter;
        $rangerFilter   = $rangerFilter         == ""   ? [] : $rangerFilter;

        $key = env('AES_KEY');


        $practices   = $this->sessionActivePractices($sessionUserId, 0)->toArray();
        $practiceIds = array_column($practices, 'facility_id');

        $facilities = $this->getSpecificFacilities($practiceIds, $sessionUserId, 0)->toArray();
        $facilityIds = array_column($facilities, 'facility_id');


        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;
        $perPage = $this->cmperPage;
        $offset = $page - 1;
        $newOffset = $perPage * $offset;

        $credentialing = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.info_required as info_required',
            'credentialing_tasks.user_parent_id',
            'credentialing_tasks.user_id',
            DB::raw('CONCAT_WS(", ", NULLIF(AES_DECRYPT(cm_pli.practise_address, "' . $key . '"), ""), NULLIF(cm_pli.city, ""), NULLIF(cm_pli.state, ""), NULLIF(cm_pli.zip_code, "")) AS facility_address'),
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
            'pr.payer_name AS payer',
            'pr.id AS payer_id',
            'cs.credentialing_status AS credential_status',
            'credentialing_tasks.credentialing_status_id',
            DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END) as individual_id'),
            DB::raw("
                CONCAT(
                    SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                    SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                    SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                ) as individual_dob
            "),
            DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key') as individual_npi"),
            DB::raw("AES_DECRYPT(cm_cu.ssn, '$key') as individual_ssn"),
            'pt.name AS individual_type_of_professional',
            'cu.primary_speciality AS individual_speciality',
            DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END ) AS facility_id"),
            DB::raw("AES_DECRYPT(cm_pli.npi, '$key') as facility_npi"),
            DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key') as facility_tax"),
            DB::raw("AES_DECRYPT(cm_pli.phone, '$key') as facility_phone"),
            'pli.specialty as facility_specialty',
            DB::raw("
            CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up
            "),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up'),
            DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1) AS created_date'),
            DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d") AS approved_date'),
            DB::raw("
            CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up_date
            "),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
            'credentialing_tasks.assignee_user_id AS assignee_userid',
            DB::raw("CONCAT(
                COALESCE(cm_cu2.first_name, ''),
                ' ',
                COALESCE(cm_cu2.last_name, '')
            ) AS assignee_username"),
            DB::raw("( SELECT field_value FROM `cm_attachments`  WHERE  entities = 'user_id' AND entity_id = cm_credentialing_tasks.assignee_user_id  ) AS assignee_filename"),
            DB::raw("( SELECT COUNT(ctlog.id) FROM `cm_credentialing_task_logs` ctlog  INNER JOIN `cm_attachments` atch ON atch.entity_id = ctlog.id AND atch.entities = 'credentialtasklog_id'
                WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id
            ) AS attachment_flag"),

        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

            ->Join('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                    ->where('pli.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', 0)
            ->where('pr.id', '!=', null);



        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
            'credentialing_tasks.info_required as info_required',
            'credentialing_tasks.user_parent_id',
            'credentialing_tasks.user_id',
            DB::raw('CONCAT_WS(", ", NULLIF(AES_DECRYPT(cm_pli.practise_address, "' . $key . '"), ""), NULLIF(cm_pli.city, ""), NULLIF(cm_pli.state, ""), NULLIF(cm_pli.zip_code, "")) AS facility_address'),
            DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
            'pr.payer_name AS payer',
            'pr.id AS payer_id',
            'cs.credentialing_status AS credential_status',
            'credentialing_tasks.credentialing_status_id',
            DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END) as individual_id'),
            DB::raw("
                CONCAT(
                    SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                    SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                    SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                ) as individual_dob
            "),
            DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key') as individual_npi"),
            DB::raw("AES_DECRYPT(cm_cu.ssn, '$key') as individual_ssn"),
            'pt.name AS individual_type_of_professional',
            'cu.primary_speciality AS individual_speciality',
            DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END ) AS facility_id"),
            DB::raw("AES_DECRYPT(cm_pli.npi, '$key') as facility_npi"),
            DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key') as facility_tax"),
            DB::raw("AES_DECRYPT(cm_pli.phone, '$key') as facility_phone"),
            'pli.specialty as facility_specialty',
            DB::raw("
            CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up
            "),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up'),
            DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1) AS created_date'),
            DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d") AS approved_date'),
            DB::raw("
            CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                END as next_follow_up_date
            "),
            DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
            'credentialing_tasks.assignee_user_id AS assignee_userid',
            DB::raw("CONCAT(
                COALESCE(cm_cu2.first_name, ''),
                ' ',
                COALESCE(cm_cu2.last_name, '')
            ) AS assignee_username"),
            DB::raw("( SELECT field_value FROM `cm_attachments`  WHERE  entities = 'user_id' AND entity_id = cm_credentialing_tasks.assignee_user_id  ) AS assignee_filename"),
            DB::raw("( SELECT COUNT(ctlog.id) FROM `cm_credentialing_task_logs` ctlog  INNER JOIN `cm_attachments` atch ON atch.entity_id = ctlog.id AND atch.entities = 'credentialtasklog_id'
                WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id
            ) AS attachment_flag"),

        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
            ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
            })
            ->Join('individualprovider_location_map as plm', function ($join) {
                $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                    ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', '<>', 0)
            ->where('pr.id', '!=', null);

        if ($credentialing_task_id != null) {
            $credentialing->where('credentialing_tasks.id', $credentialing_task_id);
            $credentialingFacility->where('credentialing_tasks.id', $credentialing_task_id);
        }
        if (count($facilityFilter) > 0 && $hasFacilty == false) {
            $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityFilter);
            $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityFilter);
        } else {
            $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
            $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
        }

        if (count($statusFilter) > 0) {
            $credentialing->whereIn('credentialing_tasks.credentialing_status_id', $statusFilter);
            $credentialingFacility->whereIn('credentialing_tasks.credentialing_status_id', $statusFilter);
        }
        if (count($payerFilter) > 0) {
            $credentialing->whereIn('pr.id', $payerFilter);
            $credentialingFacility->whereIn('pr.id', $payerFilter);
        }
        if (count($providerFilter) > 0) {

            if ($hasFacilty) {
                $credentialing->whereIn("credentialing_tasks.user_id", $facilityFilter)->where("credentialing_tasks.user_parent_id", 0);
                $credentialingFacility->whereIn("credentialing_tasks.user_id", $facilityFilter)->where("credentialing_tasks.user_parent_id", 0);
            } else {
                $credentialing->whereIn(DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END)'), $providerFilter);
                $credentialingFacility->whereIn(DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END)'), $providerFilter);
            }
        }
        if (count($rangerFilter) > 0) {
            $startDate = $rangerFilter["startDate"];
            $endDate = $rangerFilter["endDate"];
            $type = $rangerFilter["status"];
            if ($type == "approved_date") {
                $credentialing->whereBetween(DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d")'), [$startDate, $endDate])->where("credentialing_tasks.credentialing_status_id", 3);
                $credentialingFacility->whereBetween(DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d")'), [$startDate, $endDate])->where("credentialing_tasks.credentialing_status_id", 3);
            } else if ($type == "next_follow_up_date") {
                $credentialing->whereBetween(DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1)
                    END
                "), [$startDate, $endDate]);

                $credentialingFacility->whereBetween(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1)
                    END
                "), [$startDate, $endDate]);
            } else if ($type == "last_follow_up_date") {
                $credentialing->whereBetween(DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), [$startDate, $endDate]);
                $credentialingFacility->whereBetween(DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), [$startDate, $endDate]);
            } else if ($type == "created_date") {
                $credentialing->whereBetween(DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1)'), [$startDate, $endDate]);
                $credentialingFacility->whereBetween(DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1)'), [$startDate, $endDate]);
            }
        }
        if ($assigneeFilter != "") {
            $credentialing->where('credentialing_tasks.assignee_user_id', $assigneeFilter);
            $credentialingFacility->where('credentialing_tasks.assignee_user_id', $assigneeFilter);
        }
        if ($filter != "") {

            $credentialing->where(DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END "), 'like', '%' . $filter . '%')
                ->orWhere('pr.payer_name', 'like', '%' . $filter . '%')
                ->orWhere("pt.name", 'like', '%' . $filter . '%')
                ->orWhere("pli.specialty", 'like', '%' . $filter . '%')
                ->orWhere("cu.primary_speciality", 'like', '%' . $filter . '%')
                ->orWhere("cs.credentialing_status", 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key')"), 'like', '%' . strtoupper($filter) . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.ssn, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.phone, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CONCAT(
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                    )
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), 'like', '%' . $filter . '%');


            $credentialingFacility->where(DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END "), 'like', '%' . $filter . '%')
                ->orWhere('pr.payer_name', 'like', '%' . $filter . '%')
                ->orWhere("pt.name", 'like', '%' . $filter . '%')
                ->orWhere("pli.specialty", 'like', '%' . $filter . '%')
                ->orWhere("cu.primary_speciality", 'like', '%' . $filter . '%')
                ->orWhere("cs.credentialing_status", 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key')"), 'like', '%' . strtoupper($filter) . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.ssn, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.phone, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CONCAT(
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                    )
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), 'like', '%' . $filter . '%');
        }
        // dd($filter,  $credentialing->toSql());
        if ($nexlastFollowupVal != "" && $nexlastFollowupCol != '') {
            $results = $credentialing->union($credentialingFacility)
                ->orderby($nexlastFollowupCol, $nexlastFollowupVal)
                ->limit($perPage)
                ->offset($newOffset)
                ->get();
        } else {
            $results = $credentialing->union($credentialingFacility)
                ->orderby("last_follow_up_date", "DESC")
                ->limit($perPage)
                ->offset($newOffset)
                ->get();
        }
        return ['tasks' => $results];
    }

    public function fetchCredentialingById($taskId, $parentId = null)
    {
        $key = env("AES_KEY");
        $isFacility = false;
        if ($parentId == null) {
            $credential = Credentialing::find($taskId);
            if ($credential->user_parent_id == 0) {
                $isFacility = true;
            }
        }
        if ($parentId == 0) {
            $isFacility = true;
        }

        if ($isFacility) {
            $credentialing = Credentialing::select(
                'credentialing_tasks.id as creds_taskid',
                'credentialing_tasks.info_required as info_required',
                'credentialing_tasks.user_parent_id',
                'credentialing_tasks.user_id',
                DB::raw('CONCAT_WS(", ", NULLIF(AES_DECRYPT(cm_pli.practise_address, "' . $key . '"), ""), NULLIF(cm_pli.city, ""), NULLIF(cm_pli.state, ""), NULLIF(cm_pli.zip_code, "")) AS facility_address'),
                DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
                DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
                'pr.payer_name AS payer',
                'pr.id AS payer_id',
                'cs.credentialing_status AS credential_status',
                'credentialing_tasks.credentialing_status_id',
                DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END) as individual_id'),
                DB::raw("
                    CONCAT(
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                    ) as individual_dob
                "),
                DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key') as individual_npi"),
                DB::raw("AES_DECRYPT(cm_cu.ssn, '$key') as individual_ssn"),
                'pt.name AS individual_type_of_professional',
                'cu.primary_speciality AS individual_speciality',
                DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END ) AS facility_id"),
                DB::raw("AES_DECRYPT(cm_pli.npi, '$key') as facility_npi"),
                DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key') as facility_tax"),
                DB::raw("AES_DECRYPT(cm_pli.phone, '$key') as facility_phone"),
                'pli.specialty as facility_specialty',
                DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END as next_follow_up
                "),
                DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up'),
                DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1) AS created_date'),
                DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d") AS approved_date'),
                DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END as next_follow_up_date
                "),
                DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
                'credentialing_tasks.assignee_user_id AS assignee_userid',
                DB::raw("CONCAT(
                    COALESCE(cm_cu2.first_name, ''),
                    ' ',
                    COALESCE(cm_cu2.last_name, '')
                ) AS assignee_username"),
                DB::raw("( SELECT field_value FROM `cm_attachments`  WHERE  entities = 'user_id' AND entity_id = cm_credentialing_tasks.assignee_user_id  ) AS assignee_filename"),
                DB::raw("( SELECT COUNT(ctlog.id) FROM `cm_credentialing_task_logs` ctlog  INNER JOIN `cm_attachments` atch ON atch.entity_id = ctlog.id AND atch.entities = 'credentialtasklog_id'
                    WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id
                ) AS attachment_flag"),

            )
                ->leftJoin('payers as pr', function ($join) {
                    $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                        ->where('pr.for_credentialing', '=', 1);
                })
                ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
                ->leftJoin('users as cu', function ($join) {
                    $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
                })
                ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
                ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
                ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

                ->Join('user_ddpracticelocationinfo as pli', function ($join) {
                    $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                        ->where('pli.for_credentialing', 1);
                })
                ->where('credentialing_tasks.user_parent_id', 0)
                ->where('credentialing_tasks.id', $taskId)
                ->where('pr.id', '!=', null);
        } else {


            $credentialing = Credentialing::select(
                'credentialing_tasks.id as creds_taskid',
                'credentialing_tasks.info_required as info_required',
                'credentialing_tasks.user_parent_id',
                'credentialing_tasks.user_id',
                DB::raw('CONCAT_WS(", ", NULLIF(AES_DECRYPT(cm_pli.practise_address, "' . $key . '"), ""), NULLIF(cm_pli.city, ""), NULLIF(cm_pli.state, ""), NULLIF(cm_pli.zip_code, "")) AS facility_address'),
                DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END AS provider"),
                DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS practice"),
                'pr.payer_name AS payer',
                'pr.id AS payer_id',
                'cs.credentialing_status AS credential_status',
                'credentialing_tasks.credentialing_status_id',
                DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END) as individual_id'),
                DB::raw("
                        CONCAT(
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                            SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                        ) as individual_dob
                    "),
                DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key') as individual_npi"),
                DB::raw("AES_DECRYPT(cm_cu.ssn, '$key') as individual_ssn"),
                'pt.name AS individual_type_of_professional',
                'cu.primary_speciality AS individual_speciality',
                DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END ) AS facility_id"),
                DB::raw("AES_DECRYPT(cm_pli.npi, '$key') as facility_npi"),
                DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key') as facility_tax"),
                DB::raw("AES_DECRYPT(cm_pli.phone, '$key') as facility_phone"),
                'pli.specialty as facility_specialty',
                DB::raw("
                        CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                        ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                        END as next_follow_up
                    "),
                DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up'),
                DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1) AS created_date'),
                DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d") AS approved_date'),
                DB::raw("
                        CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                            ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                            END as next_follow_up_date
                    "),
                DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 ) AS last_follow_up_date'),
                'credentialing_tasks.assignee_user_id AS assignee_userid',
                DB::raw("CONCAT(
                        COALESCE(cm_cu2.first_name, ''),
                        ' ',
                        COALESCE(cm_cu2.last_name, '')
                    ) AS assignee_username"),
                DB::raw("( SELECT field_value FROM `cm_attachments`  WHERE  entities = 'user_id' AND entity_id = cm_credentialing_tasks.assignee_user_id  ) AS assignee_filename"),
                DB::raw("( SELECT COUNT(ctlog.id) FROM `cm_credentialing_task_logs` ctlog  INNER JOIN `cm_attachments` atch ON atch.entity_id = ctlog.id AND atch.entities = 'credentialtasklog_id'
                        WHERE ctlog.credentialing_task_id = cm_credentialing_tasks.id
                        ) AS attachment_flag"),
            )
                ->leftJoin('payers as pr', function ($join) {
                    $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                        ->where('pr.for_credentialing', '=', 1);
                })
                ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
                ->leftJoin('users as cu', function ($join) {
                    $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
                })
                ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
                ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
                ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
                ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
                    $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
                })
                ->Join('individualprovider_location_map as plm', function ($join) {
                    $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                        ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
                })
                ->where('credentialing_tasks.user_parent_id', '<>', 0)
                ->where('credentialing_tasks.id', $taskId)
                ->where('pr.id', '!=', null);
        }

        $credentialing = $credentialing->first();

        return $credentialing;
    }

    public function fetchCredentialingOrmAllIds($sessionUserId, $credentialing_task_id = null, $statusFilter = [], $payerFilter = [], $facilityFilter = [], $providerFilter = [], $hasFacilty = false, $assigneeFilter = '', $filter = "", $rangerFilter = [], $nexlastFollowupCol = "", $nexlastFollowupVal = "")
    {
        $statusFilter   = $statusFilter         == ""   ? [] : $statusFilter;
        $payerFilter    = $payerFilter          == ""   ? [] : $payerFilter;
        $facilityFilter = $facilityFilter       == ""   ? [] : $facilityFilter;
        $providerFilter  = $providerFilter      == ""   ? [] : $providerFilter;
        $rangerFilter   = $rangerFilter         == ""   ? [] : $rangerFilter;

        $key = env('AES_KEY');


        $practices   = $this->sessionActivePractices($sessionUserId, 0)->toArray();
        $practiceIds = array_column($practices, 'facility_id');

        $facilities = $this->getSpecificFacilities($practiceIds, $sessionUserId, 0)->toArray();
        $facilityIds = array_column($facilities, 'facility_id');


        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;
        $perPage = $this->cmperPage;
        $offset = $page - 1;
        $newOffset = $perPage * $offset;

        $credentialing = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',

        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt', 'cu.professional_type_id', '=', 'pt.id')

            ->Join('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = "0" THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'))
                    ->where('pli.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', 0)
            ->where('pr.id', '!=', null);



        $credentialingFacility = Credentialing::select(
            'credentialing_tasks.id as creds_taskid',
           

        )
            ->leftJoin('payers as pr', function ($join) {
                $join->on('pr.id', '=', 'credentialing_tasks.payer_id')
                    ->where('pr.for_credentialing', '=', 1);
            })
            ->leftjoin('credentialing_status as cs', 'cs.id', '=', 'credentialing_tasks.credentialing_status_id')
            ->leftJoin('users as cu', function ($join) {
                $join->on('cu.id', '=', DB::raw(" CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END"));
            })
            ->leftjoin('users as cu2', 'cu2.id', '=', 'credentialing_tasks.assignee_user_id')
            ->leftjoin('users as cu3', 'cu3.id', '=', 'credentialing_tasks.user_id')
            ->leftjoin('professional_types as pt',  'cu.professional_type_id', '=', 'pt.id')
            ->leftJoin('user_ddpracticelocationinfo as pli', function ($join) {
                $join->on('pli.user_id', '=', DB::raw('CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END'));
            })
            ->Join('individualprovider_location_map as plm', function ($join) {
                $join->on('plm.location_user_id', '=', DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"))
                    ->where('plm.user_id', '=', DB::raw("(CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE cm_credentialing_tasks.user_id END )"))->where('plm.for_credentialing', 1);
            })
            ->where('credentialing_tasks.user_parent_id', '<>', 0)
            ->where('pr.id', '!=', null);

        if ($credentialing_task_id != null) {
            $credentialing->where('credentialing_tasks.id', $credentialing_task_id);
            $credentialingFacility->where('credentialing_tasks.id', $credentialing_task_id);
        }
        if (count($facilityFilter) > 0 && $hasFacilty == false) {
            $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityFilter);
            $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityFilter);
        } else {
            $credentialing->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
            $credentialingFacility->whereIn(DB::raw("( CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN cm_credentialing_tasks.user_id ELSE cm_credentialing_tasks.user_parent_id END )"), $facilityIds);
        }

        if (count($statusFilter) > 0) {
            $credentialing->whereIn('credentialing_tasks.credentialing_status_id', $statusFilter);
            $credentialingFacility->whereIn('credentialing_tasks.credentialing_status_id', $statusFilter);
        }
        if (count($payerFilter) > 0) {
            $credentialing->whereIn('pr.id', $payerFilter);
            $credentialingFacility->whereIn('pr.id', $payerFilter);
        }
        if (count($providerFilter) > 0) {

            if ($hasFacilty) {
                $credentialing->whereIn("credentialing_tasks.user_id", $facilityFilter)->where("credentialing_tasks.user_parent_id", 0);
                $credentialingFacility->whereIn("credentialing_tasks.user_id", $facilityFilter)->where("credentialing_tasks.user_parent_id", 0);
            } else {
                $credentialing->whereIn(DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END)'), $providerFilter);
                $credentialingFacility->whereIn(DB::raw('(CASE WHEN cm_credentialing_tasks.user_parent_id = 0 THEN NULL ELSE cm_credentialing_tasks.user_id END)'), $providerFilter);
            }
        }
        if (count($rangerFilter) > 0) {
            $startDate = $rangerFilter["startDate"];
            $endDate = $rangerFilter["endDate"];
            $type = $rangerFilter["status"];
            if ($type == "approved_date") {
                $credentialing->whereBetween(DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d")'), [$startDate, $endDate])->where("credentialing_tasks.credentialing_status_id", 3);
                $credentialingFacility->whereBetween(DB::raw('DATE_FORMAT(cm_credentialing_tasks.updated_at, "%Y-%m-%d")'), [$startDate, $endDate])->where("credentialing_tasks.credentialing_status_id", 3);
            } else if ($type == "next_follow_up_date") {
                $credentialing->whereBetween(DB::raw("
                CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1)
                    END
                "), [$startDate, $endDate]);

                $credentialingFacility->whereBetween(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 0,1)
                    END
                "), [$startDate, $endDate]);
            } else if ($type == "last_follow_up_date") {
                $credentialing->whereBetween(DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), [$startDate, $endDate]);
                $credentialingFacility->whereBetween(DB::raw('( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), [$startDate, $endDate]);
            } else if ($type == "created_date") {
                $credentialing->whereBetween(DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1)'), [$startDate, $endDate]);
                $credentialingFacility->whereBetween(DB::raw(' ( SELECT  DATE_FORMAT(created_at, "%Y-%m-%d")    FROM  cm_credentialing_task_logs  WHERE credentialing_task_id = cm_credentialing_tasks.id  ORDER BY  id LIMIT 0, 1)'), [$startDate, $endDate]);
            }
        }
        if ($assigneeFilter != "") {
            $credentialing->where('credentialing_tasks.assignee_user_id', $assigneeFilter);
            $credentialingFacility->where('credentialing_tasks.assignee_user_id', $assigneeFilter);
        }
        if ($filter != "") {

            $credentialing->where(DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END "), 'like', '%' . $filter . '%')
                ->orWhere('pr.payer_name', 'like', '%' . $filter . '%')
                ->orWhere("pt.name", 'like', '%' . $filter . '%')
                ->orWhere("pli.specialty", 'like', '%' . $filter . '%')
                ->orWhere("cu.primary_speciality", 'like', '%' . $filter . '%')
                ->orWhere("cs.credentialing_status", 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key')"), 'like', '%' . strtoupper($filter) . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.ssn, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.phone, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CONCAT(
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                    )
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), 'like', '%' . $filter . '%');


            $credentialingFacility->where(DB::raw("CASE WHEN cm_credentialing_tasks.user_parent_id = '0' THEN NULL ELSE CONCAT( COALESCE(cm_cu3.first_name, ''),  ' ', COALESCE(cm_cu3.last_name, '')) END "), 'like', '%' . $filter . '%')
                ->orWhere('pr.payer_name', 'like', '%' . $filter . '%')
                ->orWhere("pt.name", 'like', '%' . $filter . '%')
                ->orWhere("pli.specialty", 'like', '%' . $filter . '%')
                ->orWhere("cu.primary_speciality", 'like', '%' . $filter . '%')
                ->orWhere("cs.credentialing_status", 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key')"), 'like', '%' . strtoupper($filter) . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.facility_npi, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_cu.ssn, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.tax_id, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("AES_DECRYPT(cm_pli.phone, '$key')"), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%m/%d/%Y') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CONCAT(
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 6, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 9, 2), '/',
                        SUBSTRING(AES_DECRYPT(cm_cu.dob, '$key'), 1, 4)
                    )
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw("
                    CASE WHEN cm_credentialing_tasks.credentialing_status_id = 3 THEN DATE_SUB(cm_credentialing_tasks.revalidation_date, INTERVAL 60 DAY)
                    ELSE (SELECT  DATE_FORMAT(next_follow_up, '%Y-%m-%d') FROM cm_credentialing_task_logs WHERE credentialing_task_id = cm_credentialing_tasks.id AND (next_follow_up IS NOT NULL AND next_follow_up <> 'NULL') ORDER BY id DESC LIMIT 1)
                    END
                "), 'like', '%' . $filter . '%')
                ->orWhere(DB::raw('( SELECT  DATE_FORMAT(created_at, "%m/%d/%y")   FROM cm_credentialing_task_logs  WHERE   credentialing_task_id = cm_credentialing_tasks.id ORDER BY id  DESC LIMIT 0, 1 )'), 'like', '%' . $filter . '%');
        }
        // dd($filter,  $credentialing->toSql());
        if ($nexlastFollowupVal != "" && $nexlastFollowupCol != '') {
            $results = $credentialing->union($credentialingFacility)
                ->orderby($nexlastFollowupCol, $nexlastFollowupVal)
                ->get()->toArray();
        } else {
            $results = $credentialing->union($credentialingFacility)
                // ->orderby("last_follow_up_date", "DESC")
                ->get()->toArray();
        }
        return $results;
    }


}
