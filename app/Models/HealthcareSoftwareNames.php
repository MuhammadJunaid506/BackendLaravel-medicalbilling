<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthcareSoftwareNames extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "healthcare_software_names";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "software_type_id",
        "software_name",
        "software_type",
        "active_status",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
}
