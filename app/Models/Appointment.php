<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AppointmentLeadMap;
class Appointment extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "appointments";

    protected $fillable = [
        "appointment_title",
        "appointment_date",
        "appointment_start_time",
        "appointment_end_time",
        "appointemnt_details",
        "appointemnt_createdby",
        "appointemnt_updatedby",
        "created_at",
        "updated_at"
    ];
    public function attendees()
    {
        return $this->hasMany(AppointmentLeadMap::class, 'appointment_id', 'id');
    }

}
