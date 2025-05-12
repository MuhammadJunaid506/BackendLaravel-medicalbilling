<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PracticeLocation extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "user_ddpracticelocationinfo";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
    ];

    public function facilityUsers(){
        return $this->hasMany(EmpLocationMap::class,'location_user_id','facility_id')->whereRelation('user','id','!=',null);
    }

    public function credentilingtask() {
        return $this->hasMany(Credentialing::class,'user_id','user_id')->where('user_parent_id',0);
    }
    // ->join('credentialing_tasks as ct',function($join) {
    //     //     $join->on('ct.user_id','=','user_ddpracticelocationinfo.user_id')
    //     //     ->where('ct.user_parent_id','=','0');
    //     // })
}
