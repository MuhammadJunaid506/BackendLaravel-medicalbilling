<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
     /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "contracts";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "provider_id","company_id",
        "contract_token","contract_company_fields",
        "contract_recipient_fields","is_sent","is_view",
        "is_expired","created_at","updated_at"
    ];
    
}
