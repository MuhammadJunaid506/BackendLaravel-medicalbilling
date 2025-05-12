<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class subLicenseTypes extends Model
{
    use HasFactory;

     /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "sub_licensetypes";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "license_id",
        "name",
        "description",
        "created_at",
        "updated_at",
    ];
    
    public $timestamps = false;

}


?>