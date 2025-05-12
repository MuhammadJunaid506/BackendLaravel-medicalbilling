<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capability extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "capabilities";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'path',
        'created_at',
        'updated_at'
    ];
}
