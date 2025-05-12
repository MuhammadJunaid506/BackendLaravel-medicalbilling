<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "users_profile";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'admin_id',
        'user_id',
        'company_id',
        'role_id',
        'first_name',
        'last_name',
        'gender',
        'contact_number',
        'employee_number',
        'cnic',
        'picture',
        'old_userid',
        'created_at',
        'updated_at'
    ];
}
