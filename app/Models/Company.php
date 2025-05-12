<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "companies";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'owner_first_name',
        'owner_last_name',
        'company_name',
        'company_address',
        'company_type',
        'company_country',
        'company_copy_right',
        'company_state',
        'company_contact',
        'company_logo',
        'created_at',
        'updated_at'
    ];
}
