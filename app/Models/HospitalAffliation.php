<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalAffliation extends Model
{
    use HasFactory;
      /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "hospital_affiliations";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "name",
    ];
}
