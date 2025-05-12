<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityCredentialsArchive extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "facility_credentials_archive";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "category_id",
        "subcategory_id",
        "facility_id",
        "field_id",
        "field_value",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
}
