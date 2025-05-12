<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class AccountReceivable extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "account_receivable";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "claim_no",
        "patient_name",
        "dob",
        "dos",
        "practice",
        "facility",
        "payer_name",
        "billed",
        "paid_amount",
        "entered_date",
        "status",
        "remarks",
        "assigned_to",
        "aging_days",
        "updated_at"

    ];
    private $tbl = "user_ddpracticelocationinfo";
    private $tblU = "users";
    private $key = "";
    public $appTblP = "user_baf_practiseinfo";
    public function __construct()
    {
        $this->key = env("AES_KEY");
    }

    public function revenueCycleStatus()
    {
        return $this->belongsTo(RevenueCycleStatus::class, 'status');
    }

    public function payer()
    {
        return $this->belongsTo(Payer::class, 'payer_id');
    }

    public function practice()
    {
        return $this->belongsTo(BAF::class, 'practice_id','user_id');
    }

    public function facility()
    {
        return $this->belongsTo(PracticeLocation::class, 'facility_id','user_id');
    }


    /**
     * fetch the payer against practiceId
     *
     * @param $facilityId
     */
    function fetchARPayer($facilityId)
    {

        return DB::table('account_receivable')

            ->select('payers.payer_name as label', 'payers.id as value')

            ->join('payers', 'payers.id', '=', 'account_receivable.payer_id')

            ->where('account_receivable.facility_id', '=', $facilityId)

            ->groupBy("account_receivable.payer_id")

            ->get();
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
        /*
        return DB::table($tblU.' as u')
        ->select("u.id as facility_id",DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$key') as doing_buisness_as"))
        ->join('user_role_map as urm',function($join) {
            $join->on('urm.user_id','=','u.id')
            ->where('urm.role_id','=',9);
        })
        ->join($tbl.' as pli',function($join) {
            $join->on([
                ['pli.user_id','=','u.id'],
                ['pli.user_parent_id','=','u.id']
                ]);
        })
        ->where('u.deleted','=','0')

        ->orderBy('doing_buisness_as')

        ->get();*/
        $facilities = DB::table("emp_location_map as elm")

            ->where('elm.emp_id', '=', $sessionUserId)

            ->pluck('elm.location_user_id')

            ->toArray();

        // $this->printR($facilities,true);

        $practices = DB::table($tbl . ' as pli')

            ->select('pli.user_parent_id as facility_id', DB::raw('IFNULL(cm_p.practice_name,cm_p.doing_business_as) AS doing_buisness_as'))

            ->join($this->appTblP . ' as p', function ($join) {
                $join->on('p.user_id', '=', 'pli.user_parent_id');
            })
            ->join($tblU . ' as u', function ($join) {
                $join->on('p.user_id', '=', 'u.id')
                    ->where('u.deleted', '=', '0');
            })


            ->whereIn('pli.user_id', $facilities)

            ->groupBy('pli.user_parent_id')

            ->orderBy('doing_buisness_as')

            ->get();

        return $practices;
    }
    /**
     * fetch the Facility Of ECA
     *
     * @param $practiceId
     */
    function getFacilities($parentId, $sessionUserId)
    {
        $key = $this->key;
        $tbl = $this->tbl;
        $tblU = $this->tblU;

        $locations = DB::table($tbl . ' as pli')

            ->select([DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as practice_name"), "pli.user_id as facility_id"]);

        $locations = $locations->join('emp_location_map as elm', function ($join) use ($sessionUserId) {
            $join->on('elm.location_user_id', '=', 'pli.user_id')
                ->where('elm.emp_id', '=', $sessionUserId);
        });
        if (is_array($parentId))
            $locations = $locations->whereIn("pli.user_parent_id", $parentId);
        else
            $locations = $locations->where("pli.user_parent_id", "=", $parentId);



        return $locations->orderByRaw("practice_name ASC")

            ->get();
    }

    /**
     * The function `getSpecificFacilities` retrieves specific facilities based on the provided
     * parameters.
     *
     * @param parentId The parentId parameter is used to filter the facilities based on their parent
     * ID. It can accept either a single value or an array of values. If it is a single value, the
     * function will return facilities that have the specified parent ID. If it is an array of values,
     * the function will return facilities
     * @param sessionUserId The sessionUserId parameter is the ID of the user who is currently logged
     * in and making the request.
     * @param isArchived The parameter `` is used to determine whether to include archived
     * facilities in the result or not. If `` is set to `true`, then archived facilities
     * will be included. If it is set to `false`, then only non-archived facilities will be included in
     *
     * @return a collection of locations that match the specified criteria.
     */
    function getSpecificFacilities($parentId, $sessionUserId, $isArchived)
    {

        $key = $this->key;
        $tbl = $this->tbl;
        $tblU = $this->tblU;

        $locations = DB::table($tbl . ' as pli')

            ->select([DB::raw("AES_DECRYPT(cm_pli.doing_buisness_as,'$key') as doing_buisness_as"), DB::raw("AES_DECRYPT(cm_pli.practice_name,'$key') as practice_name"), "pli.user_id as facility_id"]);

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
     * The function `getClaimCountByUserId` retrieves the count of claim numbers from the
     * `account_receivable` table where the `assigned_to` column matches the given ``.
     *
     * @param userId The userId parameter is the unique identifier of a user. It is used to filter the
     * account_receivable table and retrieve the count of claim numbers assigned to that user.
     *
     * @return the count of claim numbers from the 'account_receivable' table where the 'assigned_to'
     * column matches the provided .
     */
    function getClaimCountByUserId($userId)
    {
        return DB::table('account_receivable')
            ->select('account_receivable.claim_no')
            ->where('assigned_to', $userId)
            ->count();
    }

    function getPracticeNamesById($practiceId)
    {
        $key = $this->key;
        $result = DB::table('user_baf_practiseinfo')
            ->select("doing_business_as as practice_name")
            ->where('user_id', $practiceId)
            ->first();

        return $result->practice_name;
    }
    /**
     * check the practice name
     *
     * @param $practiceName
     *
     */
    function chkPracticeName($practice)
    {
        $tbl = $this->tbl;
        $key = $this->key;
        /*
        return DB::table($tbl)
            ->whereRaw("AES_DECRYPT(doing_buisness_as, '$key') = '$practice'")
            ->select(DB::raw("AES_DECRYPT(doing_buisness_as,'$key') as doing_buisness_as"), "user_id")
            ->first();
        */
        return DB::table("user_baf_practiseinfo")
            //->whereRaw("AES_DECRYPT(doing_buisness_as, '$key') = '$practice'")
            ->where("doing_business_as", "=", $practice)
            //->select(DB::raw("AES_DECRYPT(doing_buisness_as,'$key') as doing_buisness_as"),"user_id")
            ->select("doing_business_as as doing_buisness_as", "user_id")
            ->first();
    }
    /**
     * check the facilty name
     *
     * @param $facilityName
     *
     */
    function chkFaciltyName($facilityName)
    {
        $tbl = $this->tbl;
        $key = $this->key;
        return DB::table($tbl)
            ->whereRaw("AES_DECRYPT(practice_name, '$key') = '$facilityName'")
            ->select(DB::raw("AES_DECRYPT(practice_name,'$key') as practice_name"), "user_id")
            ->first();
    }
}
