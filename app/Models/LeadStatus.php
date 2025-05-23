<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatus extends Model
{
    use HasFactory;
    protected $table = 'lead_status';
    protected $fillable =[
        'status',
        'percentage',
        'active_status'
    ];
    public $timestamps = false; 
}
