<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArRemarks extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "ar_remarks";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "remarks",
    ];
}
