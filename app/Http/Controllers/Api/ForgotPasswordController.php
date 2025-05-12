<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\Rules\Password as RulesPassword;
use App\Http\Traits\ApiResponseHandler;
use Mail;
use App\Mail\commonEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    use ApiResponseHandler;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            "email" => "required"
        ]);


        $key = env("AES_KEY");

        $email = $request->email;


        $chkActiveUser = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")

            ->where("deleted", "=", 0)

            ->first(["id", DB::raw("AES_DECRYPT(email, '$key') as email")]);

        if (is_object($chkActiveUser)) {
            // Generate a password reset token.
            $token = Password::createToken($chkActiveUser);
            $chkActiveUser->sendPasswordResetNotification($token);
            $successStatus = Password::RESET_LINK_SENT;
            // $this->printR($status,true);
            if ("passwords.sent" == Password::RESET_LINK_SENT) {
                return $this->successResponse($successStatus, "Reset password link sent successfully.");
            } else {
                return $this->warningResponse($successStatus, "Reset password link could not be sent, try again", 449);
            }
        } else {
            return $this->warningResponse([], "You are trying to reset password with supsended or invalid account, please contact with admin", 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            "token" => "required",
            "email" => "required|email",
            "password" => "required|min:6|confirmed"
        ]);
        $key = env("AES_KEY");
        $email = $request->email;
        $chkActiveUser = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")

            ->where("deleted", "=", 0)

            ->first(["id", DB::raw("AES_DECRYPT(email, '$key') as email")]);

        $exist  = Password::tokenExists($chkActiveUser, $request->token);
        if ($exist) {
            $isReset = User::whereRaw("AES_DECRYPT(email, '$key') = '$email'")

                ->update(["password" => Hash::make($request->password), "remember_token" => Str::random(60), "last_password_changed" => Carbon::now()]);

            if ($isReset)
                return $this->successResponse("Password reseted successfully", "Password reseted successfully.");
            else
                return $this->warningResponse("Password could not be reseted", "Password could not be reseted.", 449);
        } else {
            $msg = Password::INVALID_TOKEN;
            return $this->warningResponse($msg, "Invalid reset password token given.", 449);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
