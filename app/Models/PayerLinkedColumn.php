<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayerLinkedColumn extends Model
{
    use HasFactory;

    protected $table = 'payerlinked_column';

    protected $fillable = [
        'payer_id', 'type', 'linked_column', 'created_by'
    ];
}
