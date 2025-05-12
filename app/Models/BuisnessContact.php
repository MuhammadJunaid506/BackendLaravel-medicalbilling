<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuisnessContact extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "buisness_contacts";
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'buisness_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'fax_number',
        'title',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'

    ];
}
