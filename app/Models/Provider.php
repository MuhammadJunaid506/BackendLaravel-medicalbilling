<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "providers";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "provider_type",
        "provider_name",
        "legal_business_name",
        "business_as",
        "num_of_provider",
        "business_type",
        "num_of_physical_locations",
        "avg_pateints_day",
        "seeking_service",
        "practice_manage_software_name",
        "use_pms",
        "electronic_health_record_software",
        "use_ehr",
        "address",
        "address_line_one",
        "contact_person_name",
        "contact_person_designation",
        "contact_person_email",
        "contact_person_phone",
        "city",
        "state",
        "zip_code",
        "comments",
        "begining_date",
        "has_physical_location",
        "created_at",
        "updated_at"
    ];
}
