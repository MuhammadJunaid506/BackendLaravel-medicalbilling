<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticeServiceTypeDropdown extends Model
{
    use HasFactory;
    protected $table = 'practice_servicetype_dropdown';
    protected $fillable = ['name', 'created_by'];
}
