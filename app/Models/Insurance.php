<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "insurances";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payer_id',
        'payer_name',
        'po_box',
        'fax_number',
        'credentialing_duration',
        'insurance_type',
        'short_name',
        'phone_number',
        'country_name',
        'state',
        'zip_code',
        'dependant_insurance',
        'created_at',
        'updated_at'
    ];
}
