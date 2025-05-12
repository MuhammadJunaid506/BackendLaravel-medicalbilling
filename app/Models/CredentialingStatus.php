<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CredentialingStatus extends Model
{
    use HasFactory;
    protected $table = 'credentialing_status';

    public function credentialingTask()
    {
        return $this->hasMany(Credentialing::class, 'credentialing_status_id');
    }
}
