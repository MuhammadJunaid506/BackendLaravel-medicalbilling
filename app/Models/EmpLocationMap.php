<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpLocationMap extends Model
{
    use HasFactory;
    protected $table = 'emp_location_map';

    public function user()
    {
        return $this->belongsTo(User::class, 'emp_id');
    }

    protected $fillable = [
        'emp_id',
        'location_user_id',
    ];
}
