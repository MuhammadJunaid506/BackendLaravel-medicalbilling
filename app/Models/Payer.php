<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payer extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "payers";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "payer_name",
        "shortname",
        "expecteddays",
        "phone",
        "email",
        "for_credentialing",
        "timely_filling_limit",
       
    ];

    public function tasks(){
        return $this->hasMany(Credentialing::class,'payer_id');
    }
 

}
