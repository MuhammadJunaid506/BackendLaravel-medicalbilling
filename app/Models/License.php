<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "user_licenses";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'license_no',
        'issue_date',
        'exp_date',
        'issuing_state',
        'created_at',
        'updated_at',
        'type_id',
        'created_by',
        'notify_before_exp',
        'currently_practicing'
    ];
}
