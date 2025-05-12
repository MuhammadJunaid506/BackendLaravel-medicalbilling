<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderCredentialsArchived extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "provider_credentials_archived";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "category_id",
        "subcategory_id",
        "provider_id",
        "field_key",
        "field_value",
        "created_by",
        "updated_by",
        "created_at",
        "updated_at"
    ];
}
