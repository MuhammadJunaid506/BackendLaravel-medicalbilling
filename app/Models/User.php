<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use App\Http\Traits\Utility;
use Mail;
use App\Mail\ForgotPassword;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,Utility;

     /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = "users";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
    */
    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "password",
        "gender",
        "phone",
        "cnic",
        "emp_number",
        "profile_image",
        "deleted",
        "facility_npi",
        "primary_speciality",
        "secondary_speciality",
        "dob",
        "state_of_birth",
        "country_of_birth",
        "citizenship_id ",
        "supervisor_physician",
        "professional_group_id ",
        "professional_type_id ",
        "address_line_one",
        "address_line_two",
        "ssn",
        "city",
        "state",
        "zip_code",
        "work_phone",
        "fax",
        "visa_number",
        "eligible_to_work",
        "place_of_birth",
        "status",
        "hospital_privileges",
        "otp_code",
        "provider_zip_five",
        "updated_at",
        "provider_zip_four",
        "provider_country",
        "provider_city",
        "provider_state",
        "provider_state_code",
        "provider_county",
        "created_at",
        "last_password_changed",
        "password_attempts",
        "is_complete",
        "profile_complete_percentage"
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * send notification function
     *
     * @param [type] $token
     * @return void
     */
    public function sendPasswordResetNotification($token) {

        $userData = $this->userData($this->email);
        
        $url =$this->resetPasswordLink()."?token=".$token.'&email=' . $this->email;

        $emailData =  ["link" => $url, 'name' => $userData->name,'to' => $this->email];

        return Mail::queue(new ForgotPassword($emailData));
        
    }
}
