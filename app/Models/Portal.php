<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Http\Traits\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use App\Http\Controllers\Api\CredentialingDashboardController;

class Portal extends Model
{
    use HasFactory, Utility;
    // protected $table = 'portals'; // Set the table name
    protected $table = 'portals'; // Set the table name
    private $appTbl = "user_ddpracticelocationinfo";
    private $appTblU = "users";
    private $appKey = "";
    public function __construct()
    {
        $this->appKey = env("AES_KEY");
    }


    public function provider(){
        return $this->belongsTo(ProviderLocationMap::class,'user_id','location_user_id');
    }

    public function portalTypes(){
        return $this->belongsTo(PortalType::class,'type_id');

    }

    public function createdby(){
        return $this->belongsTo(User::class,'created_by');

    }

    public function updatedBy(){
        return $this->belongsTo(User::class,'updated_by');

    }


    /**
     * fetch the portals
     *
     * @param $userId
     * @return $result
     */
    public function fetchUserIdentifiers($userId)
    {
        $tbl = "cm_" . $this->appTbl;
        $key = $this->appKey;
        $sql = "SELECT
        (
            CASE WHEN ct.user_parent_id = '0'
            THEN (SELECT AES_DECRYPT(practice_name,'$key') as practice_name FROM $tbl WHERE user_id = ct.user_id)
            ELSE (SELECT AES_DECRYPT(practice_name,'$key') as practice_name FROM $tbl WHERE user_id = ct.user_parent_id)
            END
        ) AS practice_name,
        p.user_id, p.for_credentialing, pi.legal_business_name, pi.doing_business_as, it.name as portal_type, p.user_name as username, p.password, it.link, pa.payer_name as payer, ct.credentialing_status_id, cs.credentialing_status, p.identifier as portal_identifier, ct.identifier as credentialing_identifier, ct.effective_date,ct.id as cred_id,p.id
            FROM `cm_portal_types` it
            INNER JOIN `cm_portals` p
            ON p.type_id = it.id AND p.for_credentialing = '1'
            LEFT JOIN `cm_payers` pa
            ON pa.payer_name LIKE CONCAT('%', it.name, '%') AND pa.id IN (13,22)
            LEFT JOIN `cm_credentialing_tasks` ct
            ON ct.payer_id  = pa.id AND ct.credentialing_status_id IN (3,6,7) AND ct.user_id = p.user_id
            LEFT JOIN `cm_credentialing_status` cs
            ON cs.id  = ct.credentialing_status_id
            LEFT JOIN `cm_user_baf_practiseinfo` pi
            ON pi.user_id  = ct.user_parent_id
            WHERE it.name IN ('Texas Medicaid', 'Medicare', 'CAQH')
            AND p.user_id =  '$userId'";
        return DB::select($sql);
    }
    /**
     * fetch all portals of the users based on session user
     *
     * @param $userId
     * @return $result
     */
    public function getOverAllUserPortals($search, $sessionUserId,$request, $perPage = 20)
    {
        //exit("in over all portal");
        $tbl = "cm_" . $this->appTbl;
        $key = $this->appKey;
        $appTblU = $this->tblU;
        $type = $request->has('type') && $request->type != null ? json_decode($request->type,true) :[];
        $providerFilter = $request->has('provider_filter') && $request->provider_filter ? json_decode($request->provider_filter,true) :[];
        $facilityFilter = $request->has('facility_filter') && $request->facility_filter != null  ? json_decode($request->facility_filter,true) :[];
        $rangefilter =  $request->has('range_filter') && $request->range_filter != null  ? json_decode($request->range_filter,true) :[];
        $createdBy = $request->has('created_by') && $request->created_by != null  ? json_decode($request->created_by ,true) :[];
        $modifiedBy = $request->has('modified_by') && $request->modified_by != null ? json_decode($request->modified_by ,true):[];
        $portalType = $request->has('portal_type') && $request->portal_type != null ? json_decode($request->portal_type ,true) :[];
        // dd(   $portalType);
        $credentiling= new CredentialingDashboardController;
        $allFaciliy = $credentiling->activeFacilities($sessionUserId);

        $allProviders = DB::table("individualprovider_location_map")
        ->select(
            "individualprovider_location_map.user_id as provider_id"
        )
        ->join("users", "users.id", "=", "individualprovider_location_map.user_id")
        ->where('users.deleted',0)
        ->whereIn("individualprovider_location_map.location_user_id", $allFaciliy)
        ->pluck('provider_id')->toArray();



            $facilityPortal= Portal::select(
                DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS name"),
                'cm_pt.name AS portal',
                'portals.user_name AS username',
                'cm_pt.link AS link',
                'portals.password',
                'portals.notes',
                'portals.report',
                'portals.for_credentialing',
                'portals.id AS portal_id',
                'portals.user_id AS portal_user_id',
                'portals.is_admin AS isAdmin',
                'cm_pt.id as portal_type_id'
            )
            ->from('portals')
            ->join('user_ddpracticelocationinfo AS pli', 'pli.user_id', '=', 'portals.user_id')
            ->leftJoin('emp_location_map AS elm', 'elm.location_user_id', '=', 'portals.user_id')
            ->leftJoin('portal_types AS cm_pt', 'cm_pt.id', '=', 'portals.type_id')
            ->leftJoin('users AS cmu', 'cmu.id', '=', 'elm.location_user_id')
            ->where('elm.emp_id', '=', $sessionUserId)
            ->whereColumn('elm.location_user_id', '=', 'portals.user_id')
            ->whereIn('pli.user_id',$allFaciliy)
            ->where('mapping_type','facility')
            ->groupBy('elm.location_user_id', 'portals.id');
           
            if($portalType !=null && !empty($portalType)){
                $facilityPortal =  $facilityPortal->whereIn('portals.type_id',$portalType);    
            }
            if (!empty($search)) {
                $facilityPortal = $facilityPortal->whereRaw(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') like '%$search%'"))
                ->orWhere('cm_pt.name', 'LIKE', "%$search%")
                ->orWhere('portals.user_name', 'LIKE', "%$search%");
                // ->orWhere('portals.notes', 'LIKE', "%$search%")
                // ->orWhere('portals.report', 'LIKE', "%$search%");
            }
            if(!empty($facilityFilter)){
                $facilityPortal = $facilityPortal->whereIn('pli.user_id',$facilityFilter);
            }
            if(!empty($rangefilter)){
                if($rangefilter['status'] == 'created_at'){
                    $facilityPortal = $facilityPortal->whereDate('portals.created_at','>=',$rangefilter['startDate'])->whereDate('portals.created_at','<=',$rangefilter['endDate']);
                }else if($rangefilter['status'] == 'modified_at'){
                    $facilityPortal = $facilityPortal->whereDate('portals.updated_at','>=',$rangefilter['startDate'])->whereDate('portals.updated_at','<=',$rangefilter['endDate']);
                }
            }
            if($createdBy != null && !empty($createdBy)){
                $facilityPortal = $facilityPortal->whereIn('portals.created_by',$createdBy);
            }
            if($modifiedBy != null && !empty($modifiedBy)){
                $facilityPortal = $facilityPortal->whereIn('portals.updated_by',$modifiedBy);
            }
          
            // dd($facilityPortal->get()->toArray());

            $providerPortal =  Portal::select(
                DB::raw('CONCAT(COALESCE(cm_u.first_name, ""), " ", COALESCE(cm_u.last_name, "")) AS name'),
                'cm_pt.name AS portal',
                'portals.user_name AS username',
                'cm_pt.link AS link',
                'portals.password',
                'portals.notes',
                'portals.report',
                'portals.for_credentialing',
                'portals.id AS portal_id',
                'portals.user_id AS portal_user_id',
                'portals.is_admin AS isAdmin',
                'cm_pt.id as portal_type_id'
            )
            ->from('portals')
            ->join('individualprovider_location_map AS iplp', function ($join) use ($sessionUserId,$search) {
                $join->on('iplp.location_user_id', '=', 'portals.user_id')
                    ->orOn('iplp.user_id', '=', 'portals.user_id');
            })
            ->join('emp_location_map AS elm', 'elm.location_user_id', '=', 'iplp.location_user_id')
            ->leftJoin('portal_types AS cm_pt', 'cm_pt.id', '=', 'portals.type_id')
            ->leftJoin('users AS u', 'u.id', '=', 'iplp.user_id')
            ->leftJoin('user_ddpracticelocationinfo AS pli', 'pli.user_id', '=', 'elm.location_user_id')
            ->where('elm.emp_id', '=', $sessionUserId)
            ->whereRaw('cm_iplp.user_id = cm_portals.user_id')
            ->whereIn('iplp.user_id',$allProviders)
            ->where('portals.mapping_type','provider')
            ->groupBy( 'portals.id', 'iplp.user_id');
            
            // ->groupBy( 'portals.id','elm.location_user_id', 'iplp.user_id');
            // dd($providerPortal->get()->toArray());

            if (!empty($search)) {
                $providerPortal = $providerPortal->where(DB::raw('CONCAT(COALESCE(cm_u.first_name, ""), " ", COALESCE(cm_u.last_name, "")) '), 'LIKE', "%$search%")
                ->orWhere('cm_pt.name', 'LIKE', "%$search%")
                ->orWhere('portals.user_name', 'LIKE', "%$search%")
                ->where('u.first_name', 'LIKE', "%$search%")
                ->orWhere('u.last_name', 'LIKE', "%$search%");
                // ->orWhere('portals.notes', 'LIKE', "%$search%")
                // ->orWhere('portals.report', 'LIKE', "%$search%");

            }
            if($portalType !=null){
                $providerPortal =  $providerPortal->whereIn('portals.type_id',$portalType);    
            }
            if(!empty($providerFilter)){
                $providerPortal = $providerPortal->whereIn('u.id',$providerFilter); 
            }
            if(!empty($rangefilter)){
                if($rangefilter['status'] == 'created_at'){
                    $providerPortal = $providerPortal->whereDate('portals.created_at','>=',$rangefilter['startDate'])->whereDate('portals.created_at','<=',$rangefilter['endDate']);
                }else if($rangefilter['status'] == 'modified_at'){
                    $providerPortal = $providerPortal->whereDate('portals.updated_at','>=',$rangefilter['startDate'])->whereDate('portals.updated_at','<=',$rangefilter['endDate']);
                }
            }
            if($createdBy != null && !empty($createdBy)){
                $providerPortal = $providerPortal->whereIn('portals.created_by',$createdBy);
            }
            if($modifiedBy != null && !empty($modifiedBy)){
                $providerPortal = $providerPortal->whereIn('portals.updated_by',$modifiedBy);
            }
  
            // dd($providerPortal->orderby('portal_id')->groupBy('portals.id')->paginate(20)->toArray());
            if(empty($type) && empty($facilityFilter) && empty($providerFilter)){
              
                $providerPortal = $providerPortal->unionAll($facilityPortal);
            }else if(in_array('facility',$type) && empty($providerFilter) || !empty($facilityFilter) ){
                $providerPortal = $facilityPortal;
            }else if(in_array('provider',$type) && empty($facilityFilter) || !empty($providerFilter)){
                $providerPortal = $providerPortal;
            }


            // ->unionAll(function ($query) use ($sessionUserId, $key ,$search,$allFaciliy,$request) {
            //     $query->select(
            //         DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS name"),
            //         'cm_pt.name AS portal',
            //         'portals.user_name AS username',
            //         'portals.password',
            //         'cm_pt.link AS link',
            //         'portals.notes',
            //         'portals.report',
            //         'portals.for_credentialing',
            //         'portals.user_id',
            //         'portals.id AS portal_id',
            //         'portals.user_id AS portal_user_id'
            //     )
            //         ->from('portals')
            //         ->leftJoin('emp_location_map AS elm', 'elm.location_user_id', '=', 'portals.user_id')
            //         ->leftJoin('portal_types AS cm_pt', 'cm_pt.id', '=', 'portals.type_id')
            //         ->leftJoin('users AS cmu', 'cmu.id', '=', 'elm.location_user_id')
            //         ->leftJoin('user_ddpracticelocationinfo AS pli', 'pli.user_id', '=', 'elm.location_user_id')
            //         ->where('elm.emp_id', '=', $sessionUserId)
            //         ->whereColumn('elm.location_user_id', '=', 'portals.user_id')
            //         ->whereIn('pli.user_id',$allFaciliy);
            //         if($request->has('portal_type')){
            //             $query =  $query->where('portals.type_id',$request->portal_type);    
            //         }
            //         if (!empty($search)) {
            //             $query = $query->orwhereRaw(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') like '%$search%'"))
            //             ->orWhere('portals.user_name', 'LIKE', "%$search%")
            //             ->orWhere('portals.notes', 'LIKE', "%$search%")
            //             ->orWhere('portals.report', 'LIKE', "%$search%");
            //         }
            //         $query->groupBy('elm.location_user_id', 'portals.type_id');
            // });


        $userPortal = $providerPortal
            ->groupby('portals.id')
             ->orderBy('portal_id','DESC')
            ->paginate($perPage);

        $numOfPage = $userPortal->lastPage();
        $perPage = $userPortal->perPage();
        $totalRec = $userPortal->total();
        $to = $userPortal->lastItem();
        $from = $userPortal->firstItem();
        $page = $userPortal->currentPage();
        $pagination = ["last_page" => $numOfPage, "per_page" => $perPage, "total" => $totalRec, "to" => $to, "from" => $from, "current_page" => $page];

        $userPortal = $userPortal->transform(function ($portal) {

            if (isset($portal->password) && !is_null($portal->password) && !str_contains($portal->notes, 'null')) {
                try {
                    $portal->password = decrypt($portal->password);
                } catch (\Exception $e) {
                    $portal->password = NULL;
                }
            }
            if (isset($portal->notes) && !is_null($portal->notes) && !str_contains($portal->notes, 'null'))
                try {
                    $portal->notes = decrypt($portal->notes);
                } catch (\Exception $e) {
                    $portal->notes = NULL;
                }

            return $portal;
        });
        return ["pagination" => $pagination, "portals" => $userPortal];
    }

    /**
     * fetch all portals of the users
     *
     * @param $userId
     * @return $result
     */
    public function getUserPortals($userId, $search = null, $perPage = 20, $sessionUserId)
    {
        $tbl = "cm_" . $this->appTbl;
        $key = $this->appKey;
        $appTblU = $this->tblU;
        $query = $this->selectRaw("
            (CASE WHEN (cm_u.first_name IS NOT NULL AND cm_u.last_name IS NOT NULL)
                THEN CONCAT(cm_u.first_name, ' ', cm_u.last_name)
                ELSE (SELECT AES_DECRYPT(practice_name,'$key') as practice_name FROM `$tbl` WHERE user_id = cm_portals.user_id GROUP BY user_id)
            END) AS name,
            cm_pt.name as portal, cm_portals.user_name as username,
            cm_portals.password ,
            cm_portals.identifier,
            cm_portals.type_id as portaltype,

            cm_pt.link as link, cm_portals.notes , cm_portals.report, cm_portals.for_credentialing, cm_portals.user_id, cm_portals.id as portal_id
        ")
            ->leftJoin('portal_types AS pt', 'pt.id', '=', 'portals.type_id')
            ->leftJoin($appTblU . ' AS u', 'u.id', '=', 'portals.user_id');
        if ($userId)
            $query->where('portals.user_id', $userId);

        if ($search) {
            $columns = [
                'name', 'pt.name', 'portals.user_name', 'link', 'report', 'for_credentialing'
            ];
            if ($userId) {
                $query = $query->where(function ($subquery) use ($columns, $search) {
                    foreach ($columns as $column) {
                        $subquery->orWhere($column, 'LIKE', '%' . $search . '%');
                    }
                });
            } else {
                foreach ($columns as $column) {
                    $query = $query->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            }
        }
        // echo $query->toSql();
        // exit;
        $userPortal = null;
        if ($userId > 0)
            $userPortal = $query->orderBy('portals.user_id')->paginate($perPage);
        else
            $userPortal = $query->paginate($perPage);

        //dd($userPortal);
        // Decrypt the passwords using Crypt::decrypt
        $numOfPage = $userPortal->lastPage();
        $perPage = $userPortal->perPage();
        $totalRec = $userPortal->total();
        $to = $userPortal->lastItem();
        $from = $userPortal->firstItem();
        $page = $userPortal->currentPage();
        $pagination = ["last_page" => $numOfPage, "per_page" => $perPage, "total" => $totalRec, "to" => $to, "from" => $from, "current_page" => $page];

        $userPortal = $userPortal->transform(function ($portal) {

            if (isset($portal->password) && !is_null($portal->password) && !str_contains($portal->notes, 'null')) {
                try {
                    $portal->password = decrypt($portal->password);
                } catch (\Exception $e) {
                    $portal->password = NULL;
                }
            }
            if (isset($portal->notes) && !is_null($portal->notes) && !str_contains($portal->notes, 'null'))
                try {
                    $portal->notes = decrypt($portal->notes);
                } catch (\Exception $e) {
                    $portal->notes = NULL;
                }

            return $portal;
        });
        return ["pagination" => $pagination, "portals" => $userPortal];
    }
    /**
     * filter portals of the users
     *
     * @param $userId
     * @return $result
     */
    public function getUserPortalsFilter($userId, $search = null, $perPage = 20, $sessionUserId)
    {
        $tbl = "cm_" . $this->appTbl;
        $key = $this->appKey;
        $appTblU = $this->tblU;
        $query = $this->selectRaw("
            (CASE WHEN (cm_u.first_name IS NOT NULL AND cm_u.last_name IS NOT NULL)
                THEN CONCAT(cm_u.first_name, ' ', cm_u.last_name)
                ELSE (SELECT AES_DECRYPT(practice_name,'$key') as practice_name FROM `$tbl` WHERE user_id = cm_portals.user_id GROUP BY user_id)
            END) AS name,
            cm_pt.name as portal, cm_portals.user_name as username,
            cm_portals.password ,
            cm_pt.link as link, cm_portals.notes , cm_portals.report, cm_portals.for_credentialing, cm_portals.user_id, cm_portals.id as portal_id
        ")
            ->leftJoin('portal_types AS pt', 'pt.id', '=', 'portals.type_id')
            ->leftJoin($appTblU . ' AS u', 'u.id', '=', 'portals.user_id');
        if ($userId)
            $query->where('portals.user_id', $userId);

        if ($search) {
            $columns = [
                'name', 'pt.name', 'portals.user_name', 'link', 'report', 'for_credentialing'
            ];
            if ($userId) {
                $query = $query->where(function ($subquery) use ($columns, $search) {
                    foreach ($columns as $column) {
                        $subquery->orWhere($column, 'LIKE', '%' . $search . '%');
                    }
                });
            } else {
                foreach ($columns as $column) {
                    $query = $query->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            }
        }
        // echo $query->toSql();
        // exit;
        $userPortal = null;
        if ($userId > 0)
            $userPortal = $query->orderBy('portals.user_id')->paginate($perPage);
        else
            $userPortal = $query->paginate($perPage);

        //dd($userPortal);
        // Decrypt the passwords using Crypt::decrypt
        $numOfPage = $userPortal->lastPage();
        $perPage = $userPortal->perPage();
        $totalRec = $userPortal->total();
        $to = $userPortal->lastItem();
        $from = $userPortal->firstItem();
        $page = $userPortal->currentPage();
        $pagination = ["last_page" => $numOfPage, "per_page" => $perPage, "total" => $totalRec, "to" => $to, "from" => $from, "current_page" => $page];

        $userPortal = $userPortal->transform(function ($portal) {

            if ($portal->password) {
                $portal->password = decrypt($portal->password);
            }
            if ($portal->notes)
                $portal->notes = decrypt($portal->notes);

            return $portal;
        });
        return ["pagination" => $pagination, "portals" => $userPortal];
    }
    /**
     * fetch all portals of the users
     *
     * @param $userId
     * @return $result
     */
    public function usersPortals($userId, $isAdmin = 0)
    {
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $perPage = isset($_REQUEST["per_page"]) ? $_REQUEST["per_page"] : $this->cmperPage;

        $tbl = "cm_" . $this->appTbl;
        $key = $this->appKey;
        $appTblU = "cm_" . $this->appTblU;
        $query = "SELECT *
        FROM (
        SELECT
                    (CASE WHEN (u.first_name IS NOT NULL AND u.last_name IS NOT NULL)
                        THEN CONCAT(u.first_name, ' ', u.last_name)
                        ELSE (SELECT AES_DECRYPT(practice_name,'$key') as practice_name FROM `$tbl` WHERE user_id = p.user_id GROUP BY user_id)
                       END) AS name,
        pt.name as portal, p.user_name as username, p.password as password, pt.link as link, p.notes, p.report, p.for_credentialing , p.user_id,p.id as portal_id
        FROM `cm_portals` p
        LEFT JOIN cm_portal_types pt
        ON pt.id = p.type_id
        LEFT JOIN $appTblU u
        ON u.id = p.user_id
        ORDER BY p.user_id) AS T";
        if ($isAdmin == 0) {
            //$portals = $portals->where("p.user_id", "=", $userId);
            $query .= " WHERE T.user_id = '$userId'";
        }
        if ($isAdmin == 1)
            $query .= " ORDER BY  T.user_id";


        $totallRec = DB::select($query);

        $totalRec = count($totallRec);

        // $numOfPage = ceil($totalRec/$perPage);

        $numOfPage = ceil($totalRec / $perPage);

        if ($page > $numOfPage) {
            $offset = $page;
            $portals = [];
            $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);
        } else {
            $offset = $page - 1;

            $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);
            $newOffset = $perPage * $offset;
            $portals = [];
            if ($offset <= $pagination["last_page"]) {
                $query .= " LIMIT $perPage OFFSET $newOffset";

                $portals = DB::select($query);
            }
        }





        return ["pagination" => $pagination, "portals" => $portals];
    }
    /**
     * fetch all portals of the users
     *
     * @param $userId
     * @return $result
     */
    public function usersFilterPortals($userId, $keyWord)
    {
        $tbl = "cm_" . $this->appTbl;
        $key = $this->appKey;
        $appTblU = "cm_" . $this->appTblU;
        $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;

        $perPage = isset($_REQUEST["per_page"]) ? $_REQUEST["per_page"] : $this->cmperPage;

        if ($userId != 0) {
            $query = "SELECT *
            FROM (
            SELECT
                        (CASE WHEN (u.first_name IS NOT NULL AND u.last_name IS NOT NULL)
                            THEN CONCAT(u.first_name, ' ', u.last_name)
                            ELSE (SELECT AES_DECRYPT(practice_name,'$key') as practice_name FROM `$tbl` WHERE user_id = p.user_id GROUP BY user_id)
                           END) AS name,
            pt.name as portal, p.user_name as username, p.password as password, pt.link as link, p.notes, p.report, p.for_credentialing , p.user_id,p.id as portal_id
            FROM `cm_portals` p
            LEFT JOIN cm_portal_types pt
            ON pt.id = p.type_id
            LEFT JOIN $appTblU u
            ON u.id = p.user_id
            ORDER BY p.user_id) AS T WHERE T.user_id='$userId' AND (T.username LIKE '%" . $keyWord . "%' OR T.portal LIKE '%" . $keyWord . "%' OR T.password LIKE '%" . $keyWord . "%' OR T.name LIKE '%" . $keyWord . "%') ";
        } else {



            $query = "SELECT *
            FROM (
            SELECT
                        (CASE WHEN (u.first_name IS NOT NULL AND u.last_name IS NOT NULL)
                            THEN CONCAT(u.first_name, ' ', u.last_name)
                            ELSE (SELECT AES_DECRYPT(practice_name,'$key') as practice_name FROM `$tbl` WHERE user_id = p.user_id GROUP BY user_id)
                           END) AS name,
            pt.name as portal, p.user_name as username, p.password as password, pt.link as link, p.notes, p.report, p.for_credentialing , p.user_id
            FROM `cm_portals` p
            LEFT JOIN cm_portal_types pt
            ON pt.id = p.type_id
            LEFT JOIN $appTblU u
            ON u.id = p.user_id
            ORDER BY p.user_id) AS T WHERE T.username LIKE '%" . $keyWord . "%' OR T.portal LIKE '%" . $keyWord . "%' OR T.password LIKE '%" . $keyWord . "%' OR T.name LIKE '%" . $keyWord . "%' ";
        }

        $totallRec = DB::select($query);

        $totalRec = count($totallRec);

        $numOfPage = ceil($totalRec / $perPage);

        if ($page > $numOfPage) {
            $offset = $page;
            $portals = [];
            $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);
        } else {
            $offset = $page - 1;

            $pagination = $this->makePagination($page, $perPage, $offset, $totalRec);
            $newOffset = $perPage * $offset;
            $portals = [];
            if ($offset <= $pagination["last_page"]) {
                $query .= " LIMIT $perPage OFFSET $newOffset";

                $portals = DB::select($query);
            }
        }





        return ["pagination" => $pagination, "portals" => $portals];

        return DB::select($query);
    }
    /**
     * update the portal
     *
     * @param $where
     * @param $updateData
     */
    public function updataPortal($where, $update)
    {

        return DB::table("portals")

            ->where($where)

            ->update($update);
    }

    public function fetchOverAllUserPortals($search, $sessionUserId, $perPage = 20){
        $tbl = "cm_" . $this->appTbl;
        $key = $this->appKey;
        $appTblU = $this->tblU;

        $credentiling= new CredentialingDashboardController;
        $allFaciliy = $credentiling->activeFacilities($sessionUserId);
     
        $allProviders = DB::table("individualprovider_location_map")
        ->select(
            "individualprovider_location_map.user_id as provider_id"
        )
        ->join("users", "users.id", "=", "individualprovider_location_map.user_id")
        ->where('users.deleted',0)
        ->whereIn("individualprovider_location_map.location_user_id", $allFaciliy)
        ->pluck('provider_id')->toArray();

        // dd($allProviders);
        // dd($allFaciliy);


        $query_ =  Portal::select(
            DB::raw('CONCAT(COALESCE(cm_u.first_name, ""), " ", COALESCE(cm_u.last_name, "")) AS name'),
            'cm_pt.name AS portal',
            'portals.user_name AS username',
            'portals.password',
            'cm_pt.link AS link',
            'portals.notes',
            'portals.report',
            'portals.for_credentialing',
            'portals.user_id',
            'portals.id AS portal_id',
            'portals.user_id AS portal_user_id',
            'portals.type_id'
        )   
        ->join('individualprovider_location_map AS iplp', function ($join) use ($sessionUserId,$search) {
            $join->on('iplp.location_user_id', '=', 'portals.user_id')
                    ->orOn('iplp.user_id', '=', 'portals.user_id');
                })
            ->join('emp_location_map AS elm', 'elm.location_user_id', '=', 'iplp.location_user_id')
            ->leftJoin('portal_types AS cm_pt', 'cm_pt.id', '=', 'portals.type_id')
            ->leftJoin('users AS u', 'u.id', '=', 'iplp.user_id')
            ->leftJoin('user_ddpracticelocationinfo AS pli', 'pli.user_id', '=', 'elm.location_user_id')
            ->where('elm.emp_id', '=', $sessionUserId)
            ->whereRaw('cm_iplp.user_id = cm_portals.user_id')
            ->whereIn('iplp.user_id',$allProviders);
            
            if (!empty($search)) {
                $query_ = $query_->where('u.first_name', 'LIKE', "%$search%")
                ->orWhere('u.last_name', 'LIKE', "%$search%")
                ->orWhere('cm_pt.name', 'LIKE', "%$search%")
                ->orWhere('portals.user_name', 'LIKE', "%$search%")
                ->orWhere('portals.notes', 'LIKE', "%$search%")
                ->orWhere('portals.report', 'LIKE', "%$search%");
            }
            $query_->groupBy('elm.location_user_id', 'iplp.user_id', 'portals.type_id')
            ->unionAll(function ($query) use ($sessionUserId, $key ,$search,$allFaciliy) {
                $query->select(
                    DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS name"),
                    'cm_pt.name AS portal',
                    'portals.user_name AS username',
                    'portals.password',
                    'cm_pt.link AS link',
                    'portals.notes',
                    'portals.report',
                    'portals.for_credentialing',
                    'portals.user_id',
                    'portals.id AS portal_id',
                    'portals.user_id AS portal_user_id',
                    'portals.type_id'
                )   
                    ->from('portals')
                    ->leftJoin('emp_location_map AS elm', 'elm.location_user_id', '=', 'portals.user_id')
                    ->leftJoin('portal_types AS cm_pt', 'cm_pt.id', '=', 'portals.type_id')
                    ->leftJoin('users AS cmu', 'cmu.id', '=', 'elm.location_user_id')
                    ->leftJoin('user_ddpracticelocationinfo AS pli', 'pli.user_id', '=', 'elm.location_user_id')
                    ->where('elm.emp_id', '=', $sessionUserId)
                    ->whereColumn('elm.location_user_id', '=', 'portals.user_id')
                    ->whereIn('pli.user_id',$allFaciliy);

                    if (!empty($search)) {
                        $query = $query->orwhereRaw(DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') like '%$search%'"))
                        ->orWhere('portals.user_name', 'LIKE', "%$search%")
                        ->orWhere('portals.notes', 'LIKE', "%$search%")
                        ->orWhere('portals.report', 'LIKE', "%$search%");
                    }
                    $query->groupBy('elm.location_user_id', 'portals.type_id');
            });

        

        $userPortal = $query_
            ->orderBy('name')
            // ->get();
            // ->get()->toArray();
            ->paginate($perPage);


        $numOfPage = $userPortal->lastPage();
        $perPage = $userPortal->perPage();
        $totalRec = $userPortal->total();
        $to = $userPortal->lastItem();
        $from = $userPortal->firstItem();
        $page = $userPortal->currentPage();
        $pagination = ["last_page" => $numOfPage, "per_page" => $perPage, "total" => $totalRec, "to" => $to, "from" => $from, "current_page" => $page];

        $userPortal = $userPortal->transform(function ($portal) {

            if (isset($portal->password) && !is_null($portal->password) && !str_contains($portal->notes, 'null')) {
                try {
                    $portal->password = decrypt($portal->password);
                } catch (\Exception $e) {
                    $portal->password = NULL;
                }
            }
            if (isset($portal->notes) && !is_null($portal->notes) && !str_contains($portal->notes, 'null'))
                try {
                    $portal->notes = decrypt($portal->notes);
                } catch (\Exception $e) {
                    $portal->notes = NULL;
                }

            return $portal;
        });


        return ["pagination" => $pagination, "portals" => $userPortal];
    }
}
