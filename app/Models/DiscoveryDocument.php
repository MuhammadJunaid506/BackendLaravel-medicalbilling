<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscoveryDocument extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "discoverydocuments";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
                    "company_id",
                    "provider_id",
                    "dd_token",
                    "dd_data",
                    "is_sent",
                    "created_at",
                    "updated_at"
    ];
}
