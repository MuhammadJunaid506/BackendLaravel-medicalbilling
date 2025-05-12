<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthcareSoftwareTypes extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "healthcare_software_types";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "type",
        "status",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
}
