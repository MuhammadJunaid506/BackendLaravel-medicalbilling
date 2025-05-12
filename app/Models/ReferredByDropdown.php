<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferredByDropdown extends Model
{
    use HasFactory;
    protected $table = "referredby_dropdowns";
    protected $fillable = [
        'name',
        "phone",
        "email",
        "url",
        "notes",
        'created_by'
    ];
    public $timestamps = true;
}
