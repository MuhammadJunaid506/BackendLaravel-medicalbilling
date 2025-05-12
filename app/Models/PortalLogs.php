<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalLogs extends Model
{
    use HasFactory;
    protected $table = "portal_logs";
    protected $fillable = [
        'portal_id',
        'user_id',
        "created_by",
        "logs",
    ];
    public $timestamps = true;
}
