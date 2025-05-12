<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;
use App\Models\Provider;
use App\Models\User;
use App\Models\Contract;
use App\Models\DiscoveryDocument;
use App\Models\UserProfile;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Mail\ContractAndDiscoveryDocument;
use App\Mail\ContractAndInvoice;
use App\Mail\ProviderMember;
use App\Models\Invoice;
use App\Models\ProviderMember as ProviderMemberModel;
use Illuminate\Support\Facades\Hash;
use App\Models\ProviderCompanyMap;
use App\Models\Role;
use App\Mail\ProviderCredentials;
use App\Http\Controllers\Api\DiscoverydocumentController;

class SendEmail extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * send the contract and discovery document email
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendContractDiscoveryDocumentEmail(Request $request)
    {

        $request->validate([
            "provider_id" => "required"
        ]);

        try {

            $providerId = $request->provider_id;

            $providerContract = Contract::where("provider_id", "=", $providerId)->first(["contract_token"]);

            $providerDD = DiscoveryDocument::where("provider_id", "=", $providerId)->first(["dd_token"]);

            $providerData = Provider::where("id", "=", $providerId)->first(["contact_person_email", "contact_person_name"]);

            $baseURL = $this->baseURL();

            $contractURL = $baseURL . "/view/contract/" . $providerContract->contract_token;

            $discoverDocumentURL = $baseURL . "/view/discovery-document/" . $providerDD->dd_token;



            $isSentEmail = 0;
            $msg = "";
            $emailData = ["contract_url" => $contractURL, "discoverydocument_url" => $discoverDocumentURL, "name" => $providerData->contact_person_name];
            try {

                Mail::to($providerData->contact_person_email)

                    ->send(new ContractAndDiscoveryDocument($emailData));

                $isSentEmail = 1;

                $ts = $this->timeStamp();

                Contract::where("provider_id", "=", $providerId)->update(["is_sent" => 1, "updated_at" => $ts]); //set the status for contract sent 

                DiscoveryDocument::where("provider_id", "=", $providerId)->update(["is_sent" => 1, "updated_at" => $ts]); //set the status for disovery document sent
            } catch (\Throwable $exception) {

                $isSentEmail = 0;

                $msg = $exception->getMessage();
            }
            return $this->successResponse(['is_email_sent' => $isSentEmail, 'msg' => $msg], "Email sent successfully.");
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * send the contract and invoice email
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendContractInvoiceEmail(Request $request)
    {

        $request->validate([
            "provider_id" => "required"
        ]);

        try {

            $providerId = $request->provider_id;

            $providerContract = Contract::where("provider_id", "=", $providerId)->first(["contract_token"]);

            // $providerDD = DiscoveryDocument::where("provider_id","=",$providerId)->first(["dd_token"]);

            $providerData = Provider::where("id", "=", $providerId)->first(["contact_person_email", "contact_person_name"]);

            $invoice = Invoice::where("provider_id", "=", $providerId)->first(["invoice_number", "invoice_token"]);



            $baseURL = $this->baseURL();

            $contractURL = $baseURL . "/view/contract/" . $providerContract->contract_token;

            $invoiceURL = $baseURL . "/view/invoice/" . $invoice->invoice_token . "/" . $invoice->invoice_number;



            $isSentEmail = 0;
            $msg = "";
            $emailData = ["contract_url" => $contractURL, "invoice_url" => $invoiceURL, "name" => $providerData->contact_person_name];
            try {

                Mail::to($providerData->contact_person_email)

                    ->send(new ContractAndInvoice($emailData));

                $isSentEmail = 1;
            } catch (\Throwable $exception) {

                $isSentEmail = 0;

                $msg = $exception->getMessage();
            }
            return $this->successResponse(['is_email_sent' => $isSentEmail, 'msg' => $msg], "Email sent successfully.");
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * send discovery document fill form to specific provider
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clientSendDiscoveryDocument(Request $request)
    {

        $request->validate([
            "provider_id"   => "required",
            "member_id"     => "required",
            "email"         => "required"
        ]);
        $providerId = $request->provider_id;
        $memberId   = $request->member_id;
        $email      = $request->email;
        $token      = $request->token;
        $msg = "";
        try {
            // $token = Str::random(32);
            // $token = "cm_" . strtolower($token);
            $baseURL = $this->baseURL();
            $link = $baseURL . "/provider/member/form/" . $token . "?member_id=" . $memberId;
            $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);
            $ddId = $ddData->id;
            Mail::to($request->email)

                ->send(new ProviderMember(["link" => $link]));

            $isSentEmail = 1;
            $member = ProviderMemberModel::where("provider_id", "=", $providerId)
                ->where("member_id", "=", $memberId)
                ->count();
            if ($member == 0) {
                //insert the provider member data
                ProviderMemberModel::insertGetId([
                    "provider_id" => $providerId,
                    "member_id"   => $memberId,
                    "dd_id"       => $ddId,
                    "email"       => $email,
                    "token"       => $token,
                    "created_at" => $this->timeStamp()
                ]);
            } else {
                ProviderMemberModel::where("provider_id", "=", $providerId)
                    ->where("member_id", "=", $memberId)
                    ->update([
                        "provider_id" => $providerId,
                        "member_id"   => $memberId,
                        "dd_id"       => $ddId,
                        "email"       => $email,
                        "token"       => $token,
                        "updated_at" => $this->timeStamp()
                    ]);
            }
        } catch (\Throwable $exception) {

            $isSentEmail = 0;

            $msg = $exception->getMessage();
        }
        return $this->successResponse(['is_email_sent' => $isSentEmail, 'msg' => $msg], "Email sent successfully.");
    }
    /**
     * send discovery document fill form to specific provider
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createProviderCredentials(Request $request)
    {

        $providerId = $request->provider_id;

        $email      = $request->email;
        
        $firstName  = $request->first_name;

        $lastName   = $request->last_name;

        $emailExist = User::where("email", "=", $email)->count();

        if ($emailExist) {
            return $this->warningResponse([], 'Email already exist with given params', 422);
        } else {
            $ddData = DiscoveryDocument::where("provider_id", "=", $providerId)->first(["dd_token", "id"]);

            if (!is_object($ddData)) {
                $request->merge([
                    "provider_id" => $providerId,
                    "company_id" => "null"
                ]);

                $discoverControllerObj = new DiscoverydocumentController();

                $discoverControllerObj->store($request); //generate the discovery related tokens

                $ddData = DiscoveryDocument::where("provider_id", "=", $providerId)->first(["dd_token", "id"]);
            }

            // $where = [
            //     ["provider_id", "=", $providerId]
            // ];

            // $hasRec = $this->fetchData("group_provider_info", $where, 0, []);

            $alreadyMember = ProviderMemberModel::where("provider_id", "=", $providerId)

            ->count();
            
            $memberId = 0;

            if ($alreadyMember) {
                $memberId = (int)$alreadyMember + 1;
            } else {
                $memberId = 1;
            }



            $provider = Provider::where("id", "=", $providerId)->first(["provider_type", "legal_business_name"]);

            $providerType = $provider->provider_type;

            $legalBName = $provider->legal_business_name;
            // echo "my count:".$emailExist;
            // exit;

            $password = Str::random(6);

            $fullName = $firstName . " " . $lastName;

            $addUser = [
                "name" => $fullName,
                "email" => $email,
                "password" => Hash::make($password)
            ];

            $user = User::create($addUser);

            $user->createToken($fullName . " Token")->plainTextToken;

            $userId = $user->id;

            $adminId = 0;

            $providerMapData = ProviderCompanyMap::where("provider_id", $providerId)->first(["company_id"]);

            $companyId = $providerMapData->company_id;

            //$companyId = $request->company_id;

            $role = Role::where("role_name", "=", "Provider Member")->first(["id"]);

            $roleId = $role->id;

            $gender   = "";

            $contactNumber = "";

            $employeeNumber = "";

            $cnic = "";

            $addUserProfile = [
                "admin_id"          => $adminId,
                'user_id'           => $userId,
                "company_id"        => $companyId,
                "role_id"           => $roleId,
                "first_name"        => $firstName,
                "last_name"         => $lastName,
                "gender"            => $gender,
                "contact_number"    => $contactNumber,
                "employee_number"   => $employeeNumber,
                "cnic"              => $cnic,
                "picture"           => "",
                "created_at"        => date("Y-m-d H:i:s")
            ];

            UserProfile::create($addUserProfile);

            
            //insert the provider member data
            ProviderMemberModel::insertGetId([
                "provider_id" => $providerId,
                "member_id"   => $memberId,
                "dd_id"       => $ddData->id,
                "email"       => $email,
                "user_id"     => $userId,
                "token"       => $ddData->dd_token,
                "created_at" => $this->timeStamp()
            ]);
           
            $emailData = ["login_email" => $email, "password" => $password, "name" => $fullName, "provider_type" => $providerType, "legal_business_name" => $legalBName];

            // $this->printR($emailData,true);
            $isSentEmail = 1;
            $msg = "";
            try {

                Mail::to($email)

                    ->send(new ProviderCredentials($emailData));
            } catch (\Throwable $exception) {

                $isSentEmail = 0;

                $msg = $exception->getMessage();
            }
            return $this->successResponse(['is_email_sent' => $isSentEmail, 'msg' => $msg], "Email sent status.");
        }
    }
}
