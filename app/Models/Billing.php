<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'billing';

    public function facility()
    {
        return $this->belongsTo(PracticeLocation::class, 'facility_id', 'user_id');
    }

    public function payer()
    {
        return $this->belongsTo(Payer::class, 'payer_id', 'id');
    }

    public function credentialingTask()
    {
        return $this->belongsTo(Credentialing::class, 'render_provider_id', 'user_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'render_provider_id');
    }

    public function billingProvider()
    {
        return $this->belongsTo(User::class, 'billing_provider_id');
    }
}
