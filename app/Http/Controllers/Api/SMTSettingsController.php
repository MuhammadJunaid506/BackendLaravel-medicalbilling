<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Mail\TestEmail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Swift_Transport;
use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_Message;
use Illuminate\Support\Facades\Artisan;
class SMTSettingsController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $settings = $request->validate([
            'host'              => 'required|string',
            'port'              => 'required|numeric',
            'username'          => 'required|string',
            'password'          => 'required|string',
            'encryption'        => 'nullable|string',
            'company_id'        => 'required|numeric',
            'mail_from_address' => 'required|string',
            'mail_from_name'    => 'required|string'
        ]);
        // $this->printR($validatedData,true);
        $key = env('AES_KEY');
        $password = $settings['password'];

        $res = $this->validateSmtpSettings($settings);
        if(isset($res["smtp_success"]) && $res["smtp_success"] == 1) {
            if(DB::table('smtp_settings')->where('username', '=', $settings['username'])->count() == 0) {
                DB::table('smtp_settings')->insertGetId([
                    "company_id"        => $settings['company_id'],
                    "host"              => $settings['host'],
                    "port"              => $settings['port'],
                    "username"          => $settings['username'],
                    "password"          =>  DB::raw("AES_ENCRYPT('" .    $password     . "', '$key')"),
                    "mail_from_name"    => $settings['mail_from_name'],
                    "mail_from_address" => $settings['mail_from_address'],
                    "encryption"        => $settings['encryption']
                ]);
            }
            else {
                DB::table('smtp_settings')->where('username', '=', $settings['username'])->update([
                    "company_id"        => $settings['company_id'],
                    "host"              => $settings['host'],
                    "port"              => $settings['port'],
                    "username"          => $settings['username'],
                    "password"          =>  DB::raw("AES_ENCRYPT('" .    $password     . "', '$key')"),
                    "mail_from_name"    => $settings['mail_from_name'],
                    "mail_from_address" => $settings['mail_from_address'],
                    "encryption"        => $settings['encryption']
                ]);
            }
        }
        return $this->successResponse(["vali" => $res], "success");
    }
    /**
     * validate the smtp settings
     * 
     * @param array $smtpSettings
     */
    private function validateSmtpSettings(array $smtpSettings)
    {
        $encryption = isset($smtpSettings['encryption']) ? $smtpSettings['encryption'] : null;
        try {
            $transport = new \Swift_SmtpTransport($smtpSettings['host'], $smtpSettings['port'], $encryption);
            $transport->setUsername($smtpSettings['username']);
            $transport->setPassword($smtpSettings['password']);

            $mailer = new \Swift_Mailer($transport);
            $message = new \Swift_Message('Test Message', 'This is a test message to validate your SMTP settings.');
            $message->setFrom('testecadev@yopmail.com'); // Set the sender email address

            $msg = $mailer->send($message);

            return ["smtp_success" => 1,'msg' => $msg]; // Validation successful
        } catch (\Exception $e) {
            return ['smtp_error' => $e->getMessage(),"smtp_success" => 0]; // Return specific error message for debugging
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
        // //
        // $this->setSMTPSettings(1);
        // // $envVariables = array_keys($_ENV);
        // // $this->printR($_ENV,true);
        // //env('MAIL_HOST','smtp.gmail.commmm');
        
        // Artisan::call('config:cache');
        // // echo env('MAIL_HOST');
        // // exit;
        putenv("MAIL_MAILER=smtp");
        putenv("MAIL_HOST=smtp.mailgun.org");
        putenv("MAIL_PORT=587");
        putenv("MAIL_USERNAME=manzoor@claimsmedinc.com");
        putenv("MAIL_PASSWORD=vQGajA_db8@4aPC");
        Artisan::call('config:cache');
        $message = Mail::to("faheem_eca@yopmail.com")

        ->send(new TestEmail([]),function($message) {
            $message->from('faheem@appon.io', 'Faheem Mahar');
        });

        //echo "Subject: " . $sentMessage->getSubject();
        // dd($msg);
        // echo $message->sentMessage;
        // var_dump($message);
        // $subject = $message->getOriginalMessage()->getSubject();
        // echo "Subject: " . $subject;

        // $to = $message->getOriginalMessage()->getTo();
        // // echo "to: " . $to;
        // var_dump($to);
        echo "Email sent";
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
