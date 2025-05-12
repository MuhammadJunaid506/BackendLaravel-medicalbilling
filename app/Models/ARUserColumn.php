<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
class ARUserColumn extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "ar_user_column_map";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "user_id",
        "column_name",
        "created_at",
        "updated_at"
    ];
    /**
     * add od update the user filter columns
     * 
     * @param $userId
     * @param $colsData
     */
    public function addOrUpdateUserColumn($userId,$colsData) {

        $hasCols = DB::table('ar_user_column_map')
        
        ->where('user_id','=',$userId)
        
        ->count();
        
        $insStatus = "";
        
        if($hasCols) {
            DB::table('ar_user_column_map')
            
            ->where('user_id','=',$userId)
        
            ->delete();
            
            $insStatus = DB::table('ar_user_column_map')
            
            ->insert($colsData);
        }
        else {
            $insStatus = DB::table('ar_user_column_map')
            
            ->insert($colsData);
        }

        return $insStatus;
    }
}
