<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "invoices";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'provider_id',
        'issue_date',
        'due_date',
        'payment_status',
        'created_at',
        'updated_at',
        'invoice_number',
        'invoice_token',
        'amount',
        'details',
        "is_recuring"
    ];
    protected $hidden = [
        ""
    ];
}
