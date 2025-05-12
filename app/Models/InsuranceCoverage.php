<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class InsuranceCoverage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "insurance_coverage";

    use HasFactory;

    /**
     * add the insurance coverage
     * 
     * @param $addData
     * @return $result
     */
    function addInsuranceCoverage($addData) {
        return DB::table("insurance_coverage")->insertGetId($addData);
    }
    /**
     * fetch the insurance coverage
     * 
     * @param $userId
     * @return  $result
     */
    function fetchInsuranceCoverage($userId) {
        return DB::table("insurance_coverage")
        ->where("user_id","=",$userId)
        ->first();
    }
    /**
     * update the insurance coverage
     * 
     * @param $userId
     * @param $updateData
     * @return $result
     */
    function updateInsuranceCoverage($userId,$updateData) {
        return DB::table("insurance_coverage")
        ->where("user_id","=",$userId)
        ->update($updateData);
    }

    function updateCurrentVersion($id, $where) {
        return DB::table("insurance_coverage")
        ->where('policy_number' , '=', $where)
        ->whereNot("id", $id)
        ->update(["is_current_version" => 0]);
    
    }
    
}
?>