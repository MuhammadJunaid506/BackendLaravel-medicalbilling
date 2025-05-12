<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderLocationMap extends Model
{
    use HasFactory;
    protected $table = "individualprovider_location_map";

    protected $fillable = [
        'user_id',
        'location_user_id',
        'for_credentialing',
    ];
}
