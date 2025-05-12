<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CredentialingLogs extends Model
{
    use HasFactory;

    public function credentailing()
    {
        return $this->belongsTo(Credentialing::class, 'credentialing_task_id');
    }

    public function facility()
    {
        return $this->belongsTo(PracticeLocation::class, 'facility_id', 'user_id');
    }

    public function payer()
    {
        return $this->belongsTo(Payer::class, 'payer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
