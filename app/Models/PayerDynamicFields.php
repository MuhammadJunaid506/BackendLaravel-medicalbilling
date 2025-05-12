<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayerDynamicFields extends Model
{
    use HasFactory;

    protected $fillable =[
        'label',
        'fieldtype',
        'fieldname',
        'fieldvalue',
        'linked_column',
        'linked_column_type',
        'created_by',
    ];
}
