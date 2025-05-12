<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CptCodeTypes extends Model
{
    use HasFactory;

       /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "cptcode_types";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "cpt_code",
        "description",
    ];
    
}
