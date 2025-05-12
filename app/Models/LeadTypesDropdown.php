<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadTypesDropdown extends Model
{
    use HasFactory;
    protected $table = "leadtypes_dropdowns";
    protected $fillable = [
        'name',
        'created_by',
        'status'
    ];
    public $timestamps = true;
}

