<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Shelters extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "shelters";

    /**
     * get the facility shelters
     * 
     * @param $facilityId
     */
    static function facilityShelters($facilityId) {
        
        return DB::table("shelters")

        ->select("shelters.name AS doing_buisness_as","shelters.name AS practice_name",DB::raw("CONCAT($facilityId, '.',cm_shelters.id) as facility_id"))
        
        ->leftJoin("shelter_facility_map","shelter_facility_map.shelter_id","=","shelters.id")
        
        ->leftJoin("shelter_facility_affiliations","shelter_facility_affiliations.shelter_facility_map_id","=","shelter_facility_map.id")

        ->where("shelter_facility_map.facility_id","=",$facilityId)
        
        ->orderBy("shelters.id","DESC")

        ->get();
    }
    /**
     * check has shelters
     * 
     * @param $facilityIds
     */
    static function hasShelters($facilityIds) {

        return DB::table("shelters")

        ->select("shelters.name AS doing_buisness_as","shelters.name AS practice_name","shelters.id AS facility_id")
        
        ->leftJoin("shelter_facility_map","shelter_facility_map.shelter_id","=","shelters.id")
        
        ->leftJoin("shelter_facility_affiliations","shelter_facility_affiliations.shelter_facility_map_id","=","shelter_facility_map.id")

        ->whereIn("shelter_facility_map.facility_id",$facilityIds)
        
        ->orderBy("shelters.id","DESC")

        ->count();
    }
    /**
     * fetch the shelter against the shalter name
     * 
     * @param $name
     */
    static function chkShelterAgainstName($name) {

        return DB::table("shelters")

        ->select("shelters.name AS doing_buisness_as","shelters.name AS practice_name","shelters.id AS user_id","shelter_facility_map.facility_id")
        
        ->join("shelter_facility_map","shelter_facility_map.shelter_id" ,"=","shelters.id")

        ->where("name","=",$name)
        
        ->first();
    }
}
