<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Provider;
use App\Models\User;
use App\Models\Contract;
use App\Models\DiscoveryDocument;
use App\Models\UserProfile;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\EditImage;
use App\Mail\ContractAndDiscoveryDocument;
use PDF;
use DB;
use App\Models\W9form;
use App\Models\Insurance;
use App\Models\Invoice;
use App\Models\ProviderMember;
use App\Http\Controllers\Api\DiscoverydocumentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\StatsController as EnrollmentStats;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\StatesCities;
use Mail;
use App\Mail\ProviderCredentials;
use App\Models\ProviderCompanyMap;
use App\Models\Attachments;
use App\Models\PracticeLocation;
use App\Models\Portal;
use App\Models\TaskManager;
use App\Models\ActiveInActiveLogs;
use App\Models\Stats;
use App\Models\CredentialingActivityLog;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\UserCommonFunc as UserCommonFuncApi;
use Carbon\Carbon; // This is a date library for PHP.
use App\Models\License;
use Illuminate\Support\Facades\Http;


class UtilityFunc extends Controller
{
    use ApiResponseHandler, Utility, EditImage;
    private $tbl = "user_ddpracticelocationinfo";
    private $tblU = "users";
    /**
     * view contract and discovery document data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewContractDiscoveryDocument($providerId = 0, Request $request)
    {

        try {

            $providerContract = Contract::where("provider_id", "=", $providerId)->first(["contract_token"]);

            $providerDD = DiscoveryDocument::where("provider_id", "=", $providerId)->first(["dd_token"]);

            $providerData = Provider::where("id", "=", $providerId)->first(["contact_person_email", "contact_person_name"]);

            $baseURL = $this->baseURL();

            if (!is_object($providerDD)) {
                $request->merge([
                    "provider_id" => $providerId,
                    "company_id" => "null"
                ]);
                $discoverControllerObj = new DiscoverydocumentController();
                $discoverControllerObj->store($request); //generate the discovery related tokens
            } else {
                $providerDD = DiscoveryDocument::where("provider_id", "=", $providerId)->first(["dd_token"]);
            }

            if (!is_object($providerContract) && !is_object($providerDD)) {
                return $this->successResponse(["is_contract_completed" => false, "is_dd_completed" => false, "provider_id" => $providerId], "success");
            } elseif (!is_object($providerContract) && is_object($providerDD)) {
                $discoverDocumentURL = $baseURL . "/view/discovery-document/" . $providerDD->dd_token;
                return $this->successResponse(["is_contract_completed" => false, "is_dd_completed" => true, "discoverydocument_url" => $discoverDocumentURL, "name" => $providerData->contact_person_name, "provider_id" => $providerId], "success");
            } elseif (is_object($providerContract) && !is_object($providerDD)) {
                $contractURL = $baseURL . "/view/contract/" . $providerContract->contract_token;
                return $this->successResponse(["is_contract_completed" => true, "contract_url" => $contractURL, "is_dd_completed" => false, "name" => $providerData->contact_person_name, "provider_id" => $providerId], "success");
            } else {
                $contractURL = $baseURL . "/view/contract/" . $providerContract->contract_token;

                $discoverDocumentURL = $baseURL . "/view/discovery-document/" . $providerDD->dd_token;

                $emailData =  ["contract_url" => $contractURL, "discoverydocument_url" => $discoverDocumentURL, "name" => $providerData->contact_person_name, "provider_id" => $providerId];

                return $this->successResponse($emailData, "success");
            }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * view contract and discovery document data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewContractInvoice($providerId = 0, Request $request)
    {

        try {

            $providerContract = Contract::where("provider_id", "=", $providerId)->first(["contract_token"]);

            //$providerDD = DiscoveryDocument::where("provider_id","=",$providerId)->first(["dd_token"]);
            $invoice = Invoice::where("provider_id", "=", $providerId)->first(["invoice_number", "invoice_token"]);

            $providerData = Provider::where("id", "=", $providerId)->first(["contact_person_email", "contact_person_name"]);

            $baseURL = $this->baseURL();

            if (!is_object($providerContract) && !is_object($invoice)) {
                return $this->successResponse(["is_contract_completed" => false, "is_invoice_completed" => false, "provider_id" => $providerId], "success");
            } elseif (!is_object($providerContract) && is_object($invoice)) {
                $invoiceURL = $baseURL . "/view/invoice/" . $invoice->invoice_token . "/" . $invoice->invoice_number;
                return $this->successResponse(["is_contract_completed" => false, "is_invoice_completed" => true, "invoice_url" => $invoiceURL, "name" => $providerData->contact_person_name, "provider_id" => $providerId], "success");
            } elseif (is_object($providerContract) && !is_object($invoice)) {
                $contractURL = $baseURL . "/view/contract/" . $providerContract->contract_token;
                return $this->successResponse(["is_contract_completed" => true, "contract_url" => $contractURL, "is_invoice_completed" => false, "name" => $providerData->contact_person_name, "provider_id" => $providerId], "success");
            } else {
                $contractURL = $baseURL . "/view/contract/" . $providerContract->contract_token;

                $invoiceURL = $baseURL . "/view/invoice/" . $invoice->invoice_token . "/" . $invoice->invoice_number;

                $emailData =  ["contract_url" => $contractURL, "invoice_url" => $invoiceURL, "name" => $providerData->contact_person_name, "provider_id" => $providerId];

                return $this->successResponse($emailData, "success");
            }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the contract for client
     * 
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     * 
     */
    public function viewContract($contractToken = "", Request $request)
    {

        try {
            if ($contractToken != "") {

                $companyData = Contract::where("contract_token", "=", $contractToken)->first(["provider_id", "contract_company_fields", "contract_recipient_fields", "is_view"]);

                if (is_object($companyData)) {

                    if ($companyData->is_view == 0) {
                        $ts = $this->timeStamp();
                        Contract::where("contract_token", "=", $contractToken)->update(["is_view" => 1, "updated_at" => $ts]);
                    }

                    $providerId = $companyData->provider_id;

                    $provider = Provider::where("id", $providerId)->select("num_of_provider")->first();

                    $companyData->contract_company_fields = json_decode($companyData->contract_company_fields, true);
                    $companyData->contract_recipient_fields = json_decode($companyData->contract_recipient_fields, true);

                    // $this->printR($companyDataArr,true);
                    return $this->successResponse(["contract_data" => $companyData, 'provider' => $provider], "success");
                } else {
                    return $this->warningResponse([], "Please provide the valid contract token.", 400);
                }
            } else
                return $this->warningResponse([], "Please provide the contract token.", 400);
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the invoice for client
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewInvoice($invoiceToken, $invoiceNumber, Request $request)
    {
        try {
            if ($invoiceToken != "" && $invoiceNumber) {

                $invoice = Invoice::where("invoice_token", "=", $invoiceToken)

                    ->where("invoice_number", "=", $invoiceNumber)

                    ->first();

                $provider = Provider::where("id", $invoice->provider_id)->select("seeking_service")->first();
                return $this->successResponse(['invoice' => $invoice, "provider" => $provider], "success");
            } else {
                return $this->warningResponse([], "Please provide the valid contract token.", 400);
            }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * save the client contract
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveContract($contractToken = "", Request $request)
    {

        try {

            $contractData = $request->all();
            //$this->printR($contractData["client_contract_data"],true);
            if ($contractToken != "") {

                $validToken = Contract::where("contract_token", "=", $contractToken)->count();

                if ($validToken > 0) {

                    $clientContractData = json_encode($contractData["client_contract_data"]);

                    $updateData = [
                        "contract_recipient_fields" => $clientContractData,
                        "updated_at" => $this->timeStamp()
                    ];

                    $isUpdate = Contract::where("contract_token", "=", $contractToken)->update($updateData);

                    return $this->successResponse(["is_update" => $isUpdate], "success");
                } else
                    return $this->warningResponse([], "Please provide the contract token.", 400);
            } else
                return $this->warningResponse([], "Please provide the contract token.", 400);
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the discovery document data for client
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewDiscoveryDocument($ddToken = 0, Request $request)
    {
        
        $providerObj = new ProviderController();
        $user = $providerObj->fetchProviderUser($ddToken);
        $additionParam = $request->has("member") ? $request->member : 0;
        try {
            if ($ddToken != "0") {
                // $ddData = DiscoveryDocument::where("dd_token", "=", $ddToken)->first(["provider_id"]);
                if (is_object($user)) {
                    //echo $ddToken.":token is valid:".$ddData->provider_id;
                    // $provider = Provider::find($ddData->provider_id);
                    $insurances = Insurance::all();

                    return $this->successResponse(['provider' => $user, "insurances" => $insurances], "success");
                } elseif ($additionParam == 1 && !is_object($user)) {

                    // $ddData = ProviderMember::where("token", "=", $ddToken)->first();

                    // $provider = Provider::find($ddData->provider_id);
                    // $insurances = Insurance::all();

                    return $this->successResponse(['member_data' => []], "success");
                } else
                    return $this->successResponse(NULL, "success");
            } else
                return $this->warningResponse([], "Please provide the contract token.", 400);
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * edit the w9form and map the user given fields
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addW9Form(Request $request)
    {

        $request->validate([
            "name"                  => "required",
            "buisness"              => "required",
            "tax_classification"    => "required",
            "exempt_payee"          => "required",
            "fatca_code"            => "required",
            "address"               => "required",
            "city"                  => "required",
            "state"                 => "required",
            "zip_code"              => "required",
            "requester_name"        => "required",
            "requester_address"     => "required",
            "provider_id"           => "required",
        ]);

        $ssn = "";
        $empin = "";
        if ($request->has("social_security_number") && $request->social_security_number != "") $ssn = $request->social_security_number;
        if ($request->has("emp_identification_number") && $request->emp_identification_number != "") $empin = $request->emp_identification_number;
        if ($ssn == "" && $empin == "") {
            return $this->warningResponse(["error" => ["The Social Security Number or Employeer identification number should not empty."]], "The field is required", 422);
        }

        if ($ssn != "" && $empin == "" && strlen($ssn) < 9) {
            return $this->warningResponse(["error" => ["The Social Security Number shoud not be less then 9 numbers."]], "The field is required", 422);
        }
        if ($ssn == "" && $empin != "" && strlen($empin) < 9) {
            return $this->warningResponse(["error" => ["The Employeer identification number shoud not be less then 9 numbers."]], "The field is required", 422);
        }
        if ($ssn != "" && $empin != "" && strlen($empin) < 9 && strlen($ssn) < 9) {
            return $this->warningResponse(["error" => ["The Social Security Number or Employeer identification number shoud not be less then 9 numbers."]], "The field is required", 422);
        }

        $name               = $request->name;
        $buisness           = $request->buisness;
        $taxClassification  = $request->tax_classification;
        $liabilityCompany   = $request->liability_company;
        $other              = $request->has("others_txt") ? $request->others_txt : "";
        $liabilityTxt       = $request->has("liability_txt") ? $request->liability_txt : "";
        $exemptPayee        = $request->exempt_payee;
        $fatcaCode          = $request->fatca_code;
        $address            = $request->address;
        $city               = $request->city;
        $state              = $request->state;
        $zipCode            = $request->zip_code;
        $requesterName      = $request->requester_name;
        $requesterAddress   = $request->requester_address;
        $providerId         = $request->provider_id;
        $signature          = $request->signature;
        $w9FormPath         = public_path("certificate_template/fw9-1.jpg");
        $date               = date("m/d/Y");
        $cityStateZip       = $city . "," . $state . "," . $zipCode;

        $limitedLiabilityCompanytxt = $request->limited_liability_company_txt;
        //exit("Everything is fine.");
        // $this->printR($request->all(),true);
        $buisness = $buisness == "null" ? "" : $buisness;
        $accountNumber = $request->account_number;

        $isEdit = $this->addTextOnImage($w9FormPath, $name, $buisness, $taxClassification, $exemptPayee, $fatcaCode, $address, $cityStateZip, $requesterName, $requesterAddress, $ssn, $empin, $date, $limitedLiabilityCompanytxt, $liabilityTxt, $other, $accountNumber, $providerId, $signature);

        if ($isEdit) {
            $w9FormData                                     = [];
            $w9FormData["name"]                             = $name;
            $w9FormData["buisness"]                         = $buisness;
            $w9FormData["federal_tax_classification"]       = $taxClassification;
            $w9FormData["limited_liability_company"]        = $liabilityCompany;
            $w9FormData["other"]                            = $other;
            $w9FormData["liability_txt"]                    = $liabilityTxt;
            $w9FormData["exempt_payee"]                     = $exemptPayee;
            $w9FormData["fatca_code"]                       = $fatcaCode;
            $w9FormData["address"]                          = $address;
            $w9FormData["city_state_zip"]                   = $cityStateZip;
            $w9FormData["requester_name"]                   = $requesterName;
            $w9FormData["requester_address"]                = $requesterAddress;
            $w9FormData["social_security_number"]           = $ssn;
            $w9FormData["emp_identification_number"]        = $empin;

            $w9FormJSON = json_encode($w9FormData);
            $w9FormData["form_data"]        = $w9FormJSON;
            $w9Exist = W9form::where("user_id", "=", $providerId)->count();

            $idAdd = false;
            $isUpdate = false;
            if ($w9Exist == 0) {
                $idAdd = true;
                $w9FormData["user_id"] = $providerId;
                $w9FormData["created_at"] = $this->timeStamp();
                // $insData = [
                //     "provider_id"   => $providerId,
                //     "form_data"     => $w9FormJSON,
                //     "created_at"    => $this->timeStamp()
                // ];

                W9form::insertGetId($w9FormData); //add the w9form data
            } else {
                $isUpdate = true;
                // $updateData = [
                //     "form_data"     => $w9FormJSON,
                //     "updated_at"    => $this->timeStamp()
                // ];
                $w9FormData["form_data"]        = $w9FormJSON;
                $w9FormData["updated_at"] = $this->timeStamp();
                W9form::where("user_id", "=", $providerId)->update($w9FormData);
            }

            $fileName = $providerId . '-w9-1';
            $myFile = $fileName . ".pdf";
            $hasAttachment = Attachments::where("entities", "=", "provider_id")->where("entity_id", "=", $providerId)->where("field_key", "=", $fileName)->first();
            if (!is_object($hasAttachment)) {
                $aid = Attachments::insertGetId(["entities" => "provider_id", "entity_id" => $providerId, "field_key" => $fileName, "field_value" => $myFile]);
                $addMap = ["user_id" => $providerId, "attachment_id" => $aid];
                $this->addData("user_attachment_map", $addMap);
            }
            $this->saveW9FormAsPdf($fileName); //save w9 file as pdf against this rpovider
            $pdfPath = public_path("w9form/" . $fileName . ".pdf");
            if (file_exists($pdfPath)) {
                // $path = url("w9form/".$fileName.".pdf");
                // echo "providers/".$providerId;
                $this->moveMyFile("providers/" . $providerId, $myFile, fopen($pdfPath, 'r+'));
                unlink($pdfPath);
            }
            return $this->successResponse(["is_update" => $isUpdate, "is_added" => $idAdd], "success");
        }
    }
    /**
     * view w9 form
     * 
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function viewW9Form(Request $request)
    {

        $request->validate([
            "provider_id" => "required"
        ]);

        $providerId = $request->provider_id;
        $fileName = $providerId . '-w9-1.pdf';
        // $w9FormView = public_path("w9form/" . $fileName);
        // if (file_exists($w9FormView)) {
        $path = env("STORAGE_PATH") . "providers/" . $providerId . "/" . $fileName;
        // $pdfUrl = url("w9form/" . $fileName);
        return $this->successResponse(["w9form" => $path, 'file_created' => true], "success");
        // } else
        //     return $this->successResponse(["w9form" => NULL, 'file_created' => false], "success");
    }
    public function viewPdf()
    {
        set_time_limit(0);
        $html = view('pdf.w9form1');
        //exit;
        $pdfPAth = public_path("certificate_template/common/w9form.pdf");
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->setWarnings(false)->save($pdfPAth);
    }
    /**
     * upload file discovery documents attachments
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadAttachmentFile(Request $request)
    {

        $providerId = $request->provider_id;

        $file = $request->file("attachment");

        $path = public_path('provider/attachments/' . $providerId);

        $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file->move($path, $fileName);

        return $this->successResponse(["is_upload" => true, "file_name" => $fileName], "success");
    }
    /**
     * save client given discovery document
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clientDiscoveryDocument(Request $request, $token)
    {
        $isMember = $request->has("is_member") ? $request->is_member :  0;

        $isParent   = $request->is_parent;

        $submitType = $request->submit_type;

        if ($isMember == 0 && $isParent == 1) {
            try {
                $inputAll = $request->all();
                $providerId = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id"]);
                $providerId = $providerId->provider_id;
                $keys = array_keys($inputAll);

                $prepareArr = [];
                $files = [];
                foreach ($keys as $key) {

                    $pos = strpos($key, "file");
                    if ($pos !== false) {
                        $file = $request->file($key);
                        if ($file != null) {
                            $path = public_path('provider/attachments/' . $providerId);

                            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                            if (!file_exists($path)) {
                                mkdir($path, 0777, true);
                            }
                            $file->move($path, $fileName);
                            $files[$key] = $fileName;
                        }
                    } else {
                        $prepareArr[$key] = $inputAll[$key];
                    }
                }

                $updateData = [];
                $updateData["dd_data"] = json_encode($prepareArr);
                $updateData["attachments"] = json_encode($files);
                $updateData["updated_at"] = $this->timeStamp();

                $isUpdated = DiscoveryDocument::where("dd_token", "=", $token)->update($updateData);

                return $this->successResponse(["is_updated" => $isUpdated], "success");
            } catch (\Throwable $exception) {
                //throw $th;
                return $this->errorResponse([], $exception->getMessage(), 500);
            }
        } else {
            try {

                $inputAll = $request->all();
                if ($submitType == "self") {
                    $provider = ProviderMember::where("token", "=", $token)->first(["provider_id", "provider_type", "legal_business_name"]);
                    $providerId = $provider->provider_id;
                    $providerType = $provider->provider_type;
                    $legalBName = $provider->legal_business_name;
                } else {
                    $provider = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "provider_type", "legal_business_name"]);
                    $providerId = $provider->provider_id;
                    $providerType = $provider->provider_type;
                    $legalBName = $provider->legal_business_name;
                }
                $email = $request->email;

                $emailExist = User::where("email", "=", $email)->count();

                if ($emailExist) {
                    return $this->warningResponse([], 'Email already exist with given params', 422);
                }
                $password = Str::random(6);

                $fullName = $request->first_name . " " . $$request->last_name;

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
                    "first_name"        => $request->first_name,
                    "last_name"         => $request->last_name,
                    "gender"            => $gender,
                    "contact_number"    => $contactNumber,
                    "employee_number"   => $employeeNumber,
                    "cnic"              => $cnic,
                    "picture"           => "",
                    "created_at"        => date("Y-m-d H:i:s")
                ];

                UserProfile::create($addUserProfile);


                $keys = array_keys($inputAll);

                $prepareArr = [];
                $attachamentsData = [];
                foreach ($keys as $key) {

                    $pos = strpos($key, "file");
                    if ($pos !== false) {
                        $file = $request->file($key);
                        if ($file != null) {
                            $path = public_path('provider/attachments/' . $providerId);

                            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                            if (!file_exists($path)) {
                                mkdir($path, 0777, true);
                            }
                            $file->move($path, $fileName);
                            // $prepareArr[$key] = $fileName;
                            $attachamentsData[$key] = $fileName;
                        }
                    } else {
                        $prepareArr[$key] = $inputAll[$key];
                    }
                }

                $updateData = [];
                $updateData["member_id"] = $request->member_id;
                $updateData["name"] = $fullName;
                $updateData["data"] = json_encode($prepareArr);
                $updateData["attachments"] = json_encode($attachamentsData);
                $updateData["is_complete"] = 1;
                $updateData['email']    = $email;

                if ($submitType == "self") {
                    $updateData["updated_at"] = $this->timeStamp();
                    $isUpdated = ProviderMember::where("token", "=", $token)->update($updateData);
                } else {

                    $token = Str::random(32);
                    $token = "cm_" . strtolower($token);
                    $updateData["token"] = $token;
                    $updateData["created_at"] = $this->timeStamp();
                    $isUpdated = ProviderMember::insertGetId($updateData);
                }

                $emailData = ["login_email" => $email, "password" => $password, "name" => $fullName, "provider_type" => $providerType, "legal_business_name" => $legalBName];
                $isSentEmail = 1;
                $msg = "";
                try {

                    Mail::to($email)

                        ->send(new ProviderCredentials($emailData));
                } catch (\Throwable $exception) {

                    $isSentEmail = 0;

                    $msg = $exception->getMessage();
                }
                return $this->successResponse(["is_updated" => $isUpdated, "is_email_sent" => $isSentEmail, "msg" => $msg], "success");
            } catch (\Throwable $exception) {
                //throw $th;
                return $this->errorResponse([], $exception->getMessage(), 500);
            }
        }
    }

    /**
     * save client given discovery document
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addClientDsicoveryDocument(Request $request, $token)
    {

        $whichSection = $request->which_section;
        $gPId = $request->id;
        // $logUserId = $request->has("user_id") ? $request->user_id : 0;


        // $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);

        // $providerId = $ddData->provider_id;

        // $provider = Provider::where("id", "=", $providerId)->first(["provider_type", "legal_business_name"]);

        // $providerType = $provider->provider_type;

        // $legalBName = $provider->legal_business_name;
        $userId = $token;
        // $ddId = $ddData->id;
        if ($request->has("id"))
            $inputAll = $request->except(["which_section", "id"]);
        else
            $inputAll = $request->except(["which_section"]);

        $keys = array_keys($inputAll);

        if ($whichSection == "buisness_info") {
            $prepDataIns  = [];
            $insData = [];
            $insData["user_id"]                         = $userId;
            $insData["facility_npi"]                    = $inputAll['facility_npi'];
            $insData["legal_business_name"]             = $inputAll['legal_business_name'];
            $insData["primary_correspondence_address"]  = $inputAll['primary_correspondence_ddress'];
            $insData["phone"]                           = $inputAll['phone'];
            $insData["fax"]                             = $inputAll['fax'];
            $insData["federal_tax_classification"]      = $inputAll['federal_tax_classification'];
            $insData["email"]                           = $inputAll['email_address'];
            $insData["group_specialty"]                 = $inputAll['group_specialty'];
            $insData["facility_tax_id"]                 = $inputAll['facility_tax_id'];
            $insData["business_established_date"]       = $inputAll['established_date'];
            $insData["created_at"]                      = $this->timeStamp();
            // foreach ($keys as $key) {
            //     $prepDataIns[$key]   = $inputAll[$key];
            //     //$prepDataIns["field_value"] = $inputAll[$key];
            //     $prepDataIns["provider_id"] = $providerId;
            //     $prepDataIns["dd_id"]       = $ddId;
            //     $prepDataIns["created_at"]  = $this->timeStamp();
            //     // array_push($insData, $prepDataIns);
            // }

            // $this->printR($insData,true);
            $where = [
                ["user_id", "=", $userId]
            ];
            $hasRec = $this->fetchData("user_dd_businessinformation", $where, 1, []);
            if (!is_object($hasRec)) {
                $this->addData("user_dd_businessinformation", $insData, 1);
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => 0, "action_taken" => "add", "section" => $whichSection, "log_data" => json_encode($prepDataIns), "created_at" => $this->timeStamp()]);
            } else {
                $this->updateData("user_dd_businessinformation", $where, $insData);
                // $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"action_taken" => "update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => 0, "action_taken" => "update", "section" => $whichSection, "log_data" => json_encode($prepDataIns), "created_at" => $this->timeStamp()]);
            }

            // $this->addData("buisnessinfo", $prepDataIns, 1);
        } elseif ($whichSection == "individual_provider") {
            $user = new UserController();
            $request->merge([
                'first_name' =>  $inputAll['first_name'],
                "last_name" =>   $inputAll['last_name'],
                "email" =>  $inputAll['email'],
                "role_id" => "4",
                "password" => "Qwerty123#",
                "component" => "dd"
            ]);
            $newUser = $user->createUsers($request)->getContent();
            $resArr = json_decode($newUser, true);

            if (isset($resArr["data"]["is_new"]) && $resArr["data"]["is_new"] == true) {
                $childUser = $resArr["data"]["id"];
            } else {
                $childUser = $resArr["data"]["id"];
            }


            $insData = [];

            $insData["facility_npi"]                = $inputAll['facility_npi'];
            $insData["primary_speciality"]          = $inputAll['primary_speciality'];
            $insData["secondary_speciality"]        = $inputAll['secondary_speciality'];
            if ($inputAll['date_of_birth'] != "null")
                $insData["dob"]                         = $inputAll['date_of_birth'];

            $insData["phone"]                       = $inputAll['cell_phone'];
            $insData["state_of_birth"]              = $inputAll['state_of_birth'];
            $insData["country_of_birth"]            = $inputAll['country_of_birth'];
            if ($inputAll['citizenship_id'] != "null")
                $insData["citizenship_id"]              = $inputAll['citizenship_id'];

            $insData["supervisor_physician"]        = $inputAll['supervisor_physician'];
            $insData["address_line_one"]            = $inputAll['address_line_one'];
            $insData["address_line_two"]            = $inputAll['address_line_two'];
            $insData["gender"]                      = $inputAll['gender'];
            $insData["ssn"]                         = $inputAll['ssn'];
            if ($inputAll['professional_group_id'] != "null")
                $insData["professional_group_id"]       = $inputAll['professional_group_id'];
            if ($inputAll['professional_type_id'] != "null")
                $insData["professional_type_id"]        = $inputAll['professional_type_id'];

            $insData["first_name"]                  = $inputAll['first_name'];
            $insData["last_name"]                   = $inputAll['last_name'];

            // echo $childUser;
            // exit;
            $where__ = [
                ["id", "=", $childUser],
            ];
            $hasRec = $this->fetchData("users", $where__, 1, []);
            //update invidual profile data
            if (is_object($hasRec)) {
                $this->updateData("users", $where__, $insData);
            } else {
                $this->updateData("users", $where__, $insData);
            }
            // echo $childUser;
            // echo $inputAll['driver_license_number'];
            // echo "driver license <br/>";
            // echo $inputAll['state_license_number'];
            // echo "state license <br/>";
            // echo $inputAll['dae_number'];
            // echo "dea license <br/>";
            // exit;
            //add or update license data
            //try 
            {
                if ($inputAll['driver_license_number'] != "null") {

                    $driverLicenseType = 2;
                    $whereDriverLicense = [
                        ["user_id", "=", $childUser],
                        ["type_id", "=", $driverLicenseType]
                    ];

                    $hasDiverLicense = $this->fetchData("user_licenses", $whereDriverLicense, 1, []);

                    if (is_object($hasDiverLicense)) {
                        $diverLicense = [];
                        $diverLicense["user_id"] = $childUser;
                        $diverLicense["license_no"] = $inputAll['driver_license_number'];
                        $diverLicense["type_id"] = $driverLicenseType;
                        $diverLicense["status"] = 1;
                        if ($inputAll['driver_license_issue_date'] != "null")
                            $diverLicense["issue_date"] = $inputAll['driver_license_issue_date'];
                        if ($inputAll['driver_license_expiration_date'] != "null")
                            $diverLicense["exp_date"] = $inputAll['driver_license_expiration_date'];
                        if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                            $diverLicense["issuing_state"] = $inputAll['issue_state'];

                        // $diverLicense = ["user_id" => $childUser,"license_no" => $inputAll['driver_license_number'],"issue_date" => $inputAll['driver_license_issue_date'],"exp_date" => $inputAll['driver_license_expiration_date'],"type_id" => $driverLicenseType,"status"=>1];
                        $this->updateData("user_licenses", $whereDriverLicense, $diverLicense);
                    } else {
                        // exit("in iff driver");
                        // $diverLicense = ["user_id" => $childUser,"license_no" => $inputAll['driver_license_number'],"issue_date" => $inputAll['driver_license_issue_date'],"exp_date" => $inputAll['driver_license_expiration_date'],"type_id" => $driverLicenseType,"status"=>1];
                        $diverLicense = [];
                        $diverLicense["user_id"] = $childUser;
                        $diverLicense["license_no"] = $inputAll['driver_license_number'];
                        $diverLicense["type_id"] = $driverLicenseType;
                        $diverLicense["status"] = 1;
                        if ($inputAll['driver_license_issue_date'] != "null")
                            $diverLicense["issue_date"] = $inputAll['driver_license_issue_date'];
                        if ($inputAll['driver_license_expiration_date'] != "null")
                            $diverLicense["exp_date"] = $inputAll['driver_license_expiration_date'];
                        if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                            $diverLicense["issuing_state"] = $inputAll['issue_state'];

                        $this->addData("user_licenses", $diverLicense, 0);
                    }
                }
                if ($inputAll['state_license_number'] != "null") {
                    //$this->printR(,true);
                    //exit("Faheem here");
                    $stateLicenseType = 1;
                    $whereStateLicense = [
                        ["user_id", "=", $childUser],
                        ["type_id", "=", $stateLicenseType]
                    ];

                    $hasStateLicense = $this->fetchData("user_licenses", $whereStateLicense, 1, []);

                    if (is_object($hasStateLicense)) {
                        $stateLicense = [];
                        $stateLicense["user_id"] = $childUser;
                        $stateLicense["license_no"] = $inputAll['state_license_number'];
                        $stateLicense["type_id"] = $stateLicenseType;
                        $stateLicense["status"] = 1;
                        if ($inputAll['state_license_issue_date'] != "null")
                            $stateLicense["issue_date"] = $inputAll['state_license_issue_date'];
                        if ($inputAll['state_license_expiration_date'] != "null")
                            $stateLicense["exp_date"] = $inputAll['state_license_expiration_date'];
                        if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                            $stateLicense["issuing_state"] = $inputAll['issue_state'];

                        $this->updateData("user_licenses", $whereStateLicense, $stateLicense);
                    } else {

                        $stateLicense = [];
                        $stateLicense["user_id"] = $childUser;
                        $stateLicense["license_no"] = $inputAll['state_license_number'];
                        $stateLicense["type_id"] = $stateLicenseType;
                        $stateLicense["status"] = 1;
                        if ($inputAll['state_license_issue_date'] != "null")
                            $stateLicense["issue_date"] = $inputAll['state_license_issue_date'];
                        if ($inputAll['state_license_expiration_date'] != "null")
                            $stateLicense["exp_date"] = $inputAll['state_license_expiration_date'];
                        if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                            $stateLicense["issuing_state"] = $inputAll['issue_state'];

                        $this->addData("user_licenses", $stateLicense, 0);
                    }
                }
                if ($inputAll['dae_number'] != "null") {
                    // exit("Here i am");
                    $deaLicenseType = 4;
                    $whereDeaLicense = [
                        ["user_id", "=", $childUser],
                        ["type_id", "=", $deaLicenseType]
                    ];
                    $hasDeaLicense = $this->fetchData("user_licenses", $whereDeaLicense, 1, []);
                    if (is_object($hasDeaLicense)) {
                        //$deaLicense = ["user_id" => $childUser,"license_no" => $inputAll['dae_number'],"issue_date" => $inputAll['dae_issue_date'],"type_id" => $deaLicenseType,"status"=>1,"exp_date" => $inputAll['dae_expire_date']];
                        $deaLicense = [];
                        $deaLicense["user_id"] = $childUser;
                        $deaLicense["license_no"] = $inputAll['dae_number'];
                        $deaLicense["type_id"] = $deaLicenseType;
                        $deaLicense["status"] = 1;
                        if ($inputAll['dae_issue_date'] != "null")
                            $deaLicense["issue_date"] = $inputAll['dae_issue_date'];
                        if ($inputAll['dae_expire_date'] != "null")
                            $deaLicense["exp_date"] = $inputAll['dae_expire_date'];
                        if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                            $deaLicense["issuing_state"] = $inputAll['issue_state'];

                        $this->updateData("user_licenses", $whereDeaLicense, $deaLicense);
                    } else {
                        //exit("in iffdea");
                        // $deaLicense = ["user_id" => $childUser,"license_no" => $inputAll['dae_number'],"issue_date" => $inputAll['dae_issue_date'],"type_id" => $deaLicenseType,"status"=>1,"exp_date" => $inputAll['dae_expire_date']];
                        $deaLicense = [];
                        $deaLicense["user_id"] = $childUser;
                        $deaLicense["license_no"] = $inputAll['dae_number'];
                        $deaLicense["type_id"] = $deaLicenseType;
                        $deaLicense["status"] = 1;
                        if ($inputAll['dae_issue_date'] != "null")
                            $deaLicense["issue_date"] = $inputAll['dae_issue_date'];
                        if ($inputAll['dae_expire_date'] != "null")
                            $deaLicense["exp_date"] = $inputAll['dae_expire_date'];
                        if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                            $deaLicense["issuing_state"] = $inputAll['issue_state'];

                        $this->addData("user_licenses", $deaLicense, 0);
                    }
                }
            }
            // catch(\Exception $e) {
            //     echo $e->getMessage();
            //     exit("Faheem");
            // }

            //portals add or update
            $portalData = [];
            $portalData["user_id"]      = $childUser;
            // $portalData["payer_id"]     = 0;
            $portalData["user_name"]    = $inputAll['caqh_user_name'];
            $portalData["password"]     = $inputAll['caqh_password'];
            //$portalData["link"]         = $inputAll['caqh_link'];
            //$portalData["identifier_id"]    = 1;
            $portalData["identifier"]       = $inputAll['cahq_number'];
            $portalData["notes"]       = "NULL";
            $portalData["report"]       = "0";
            //return response(["data" => $insData]);
            $hasPortal = $this->fetchData("portals", ["user_id" => $childUser], 1, []);
            // $this->printR($hasPortal,true);
            // exit("Faheem");
            if (is_object($hasPortal)) {
                //$typeId = $hasPortal->type_id;
                // $this->updateData("portal_types", ["id" => $typeId], ["link" => $inputAll['caqh_link']]);
                $this->updateData("portals", ["user_id" => $childUser], $portalData);
            } else {
                // $portalsTypes = ["name" => "CAQH"];
                // $id = $this->fetchData("portal_types", $portalsTypes, 1, []);
                // $portalData["type_id"] = $id;
                $this->addData("portals", $portalData);
            }



            // $prepDataIns["created_at"]  = $this->timeStamp();
            $where = [
                ["user_id", "=", $childUser],
                ["parent_user_id", "=", $userId]
            ];

            $indvPRelation  = [];
            $indvPRelation["user_id"]                 = $childUser;
            $indvPRelation["parent_user_id"]          = $userId;

            $hasRec = $this->fetchData("user_dd_individualproviderinfo", $where, 1, []);
            if (!is_object($hasRec)) {
                $insData["created_at"] = $this->timeStamp();

                // $insData["user_id"]     = $userId;
                $id = $this->addData("user_dd_individualproviderinfo", $indvPRelation, 0);

                if ($id) {
                    foreach ($keys as $key) {
                        $pos = strpos($key, "file");
                        if ($pos !== false) {
                            $file = $request->file($key);
                            if ($file != null && $file != "undefined") {
                                // $path = public_path('provider/attachments/' . $id);

                                $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                                $this->uploadMyFile($fileName, $file, "providers/indviduals/" . $id);
                                $addFileData = [
                                    "entities"     => "provider_id",
                                    "entity_id"     => $id,
                                    "field_key"     => $key,
                                    "field_value"   => $fileName
                                ];
                                $aid = $this->addData("attachments", $addFileData, 0);
                                $addMap = ["user_id" => $id, "attachment_id" => $aid];
                                $this->addData("user_attachment_map", $addMap);
                            }
                        }
                    }
                }
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => $providerId, "action_taken" => "add", "section" => $whichSection, "log_data" => json_encode($prepDataIns), "created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
            } else {
                $insData["updated_at"] = $this->timeStamp();
                $this->updateData("user_dd_individualproviderinfo", $where, $indvPRelation);
                foreach ($keys as $key) {
                    $pos = strpos($key, "file");
                    if ($pos !== false) {
                        $file = $request->file($key);
                        if ($file != null && $file != "undefined") {
                            // $path = public_path('provider/attachments/' . $gPId);

                            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                            $this->uploadMyFile($fileName, $file, "providers/indviduals/" . $gPId);
                            $addFileData = [
                                "entities"     => "provider_id",
                                "entity_id"     => $gPId,
                                "field_key"     => $key,
                                "field_value"   => $fileName
                            ];
                            $whereFile = [
                                ["entities", "=", "provider_id"],
                                ["entity_id", "=", $gPId],
                                ["field_key", "=", $key],
                            ];
                            $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
                            if (is_object($hasFile))
                                $this->updateData("attachments", $whereFile, $addFileData);
                            else {
                                $aid = $this->addData("attachments", $addFileData, 0);
                                $addMap = ["user_id" => $gPId, "attachment_id" => $aid];
                                $this->addData("user_attachment_map", $addMap);
                            }
                        }
                    }
                }
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => $providerId, "action_taken" => "update", "section" => $whichSection, "log_data" => json_encode($prepDataIns), "created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }
        } elseif ($whichSection == "practice_location_info") {
            // exit("In iff");
            $prepDataIns  = [];
            $insData = [];

            //$insData['user_id'] = $userId;
            $insData["for_credentialing"] = "1";
            $insData['index_id'] = $request->num_of_physical_location;
            $insData['emrs_using'] = $request->emrs_using;
            $insData['primary_correspondence_address'] = $request->has("primary_correspondence_address") ? $request->primary_correspondence_address : "NULL";
            // $insData['phone'] =  $request->has("phone") ? $request->phone : "NULL";
            // $insData['fax'] = $request->has("fax") ? $request->fax : "NULL";
            $insData['email'] = $request->has("email") ? $request->email : "NULL";
            $insData['office_manager_name'] = $request->has("office_manager_name") ? $request->office_manager_name : "NULL";
            $insData['practise_address'] = $request->practise_address;
            $insData['phone'] = $request->phone;
            $insData['fax'] = $request->fax;
            $insData['email'] = $request->email;
            $insData['practice_name'] = $request->practice_name;

            $insData['monday_from'] = $request->monday_from;
            $insData['tuesday_from'] = $request->tuesday_from;
            $insData['wednesday_from'] = $request->wednesday_from;
            $insData['thursday_from'] = $request->thursday_from;
            $insData['friday_from'] = $request->friday_from;
            $insData['saturday_from'] = $request->saturday_from;
            $insData['sunday_from'] = $request->sunday_from;

            $insData['monday_to'] = $request->monday_to;
            $insData['tuesday_to'] = $request->tuesday_to;
            $insData['wednesday_to'] = $request->wednesday_to;
            $insData['thursday_to'] = $request->thursday_to;
            $insData['friday_to'] = $request->friday_to;
            $insData['saturday_to'] = $request->saturday_to;
            $insData['sunday_to'] = $request->sunday_to;

            $insData['emr_using'] = $request->emrs_using;
            $insData['doing_buisness_as'] = $request->doing_buisness_as;
            $insData['npi'] = $request->npi;
            $insData['is_primary'] = $request->is_primary;
            $insData["location_summary"] = $request->location_summary;

            $practiseInfo = $this->fetchData("user_baf_practiseinfo", ["user_id" => $userId], 1, ["provider_name", "legal_business_name", "number_of_individual_provider"]);
            $bInfo = $this->fetchData("user_dd_businessinformation", ["user_id" => $userId], 1, ["group_specialty", "facility_tax_id"]);
            $cInfo = $this->fetchData("user_baf_contactinfo", ["user_id" => $userId], 1, ["city", "state", "zip_code"]);
            $baInfo = $this->fetchData("user_baf_businessinfo", ["user_id" => $userId], 1, ["number_of_physical_location"]);

            //$insData['practice_name'] = !isNull($practiseInfo->legal_business_name) ? $practiseInfo->legal_business_name : $practiseInfo->provider_name;
            $insData['specialty'] = is_object($bInfo) ? $bInfo->group_specialty : "NULL";
            $insData['tax_id'] = is_object($bInfo) ? $bInfo->facility_tax_id : "NULL";
            $insData['city'] = is_object($cInfo) ? $cInfo->city : "NULL";
            $insData['state'] = is_object($cInfo) ? $cInfo->state : "NULL";
            $insData['zip_code'] = is_object($cInfo) ? $cInfo->zip_code : "NULL";
            $insData['number_of_individual_provider'] = is_object($practiseInfo) ? $practiseInfo->number_of_individual_provider : "NULL";
            $insData['number_of_physical_location'] = is_object($baInfo) ? $baInfo->number_of_physical_location : "NULL";
            $insData['satisfied_with_emr'] = $request->has("satisfied_with_emr") ? $request->satisfied_with_emr : "NULL";
            $insData['emr_plan_on_using'] = $request->has("emr_plan_on_using") ? $request->emr_plan_on_using : "NULL";
            $insData['num_of_physical_location'] = $request->has("num_of_physical_location") ? $request->num_of_physical_location : "NULL";
            $insData['satisfied_with_emr'] = $request->has("satisfied_with_emr") ? $request->satisfied_with_emr : "NULL";
            $insData['emr_plan_on_using'] = $request->has("emr_plan_on_using") ? $request->emr_plan_on_using : "NULL";
            $insData['num_of_physical_location'] = $request->has("num_of_physical_location") ? $request->num_of_physical_location : "NULL";

            // if($request->is_primary == 0 || $request->num_of_physical_location > 1) 
            {
                // exit("in iff");
                $where = [
                    ["user_parent_id", "=", $userId],
                    ["user_id", "=", $request->loc_user_id]
                    // ["index_id", "=", $request->num_of_physical_location]
                ];
                if (strlen($inputAll['office_manager_name']) > 0) {
                    // exit("In this iff");
                    $hasRec = $this->fetchData($this->tbl, ["user_id" => $request->loc_user_id, "user_parent_id" => $userId], 1, ["user_id"]);
                    if (!is_object($hasRec)) {
                        $user = new UserController();
                        $request->merge([
                            'first_name' =>  $inputAll['office_manager_name'],
                            "last_name" =>   "NULL",
                            "email" =>  isset($inputAll['practise_email']) ? $inputAll['practise_email'] : $inputAll['email'],
                            "role_id" => 9,
                            "password" => "Qwerty123#",
                            "component" => "dd"
                        ]);
                        $newUser = $user->createUsers($request)->getContent();
                        $resArr = json_decode($newUser, true);
                        $childUser = $resArr["data"]["id"];
                        $insData['user_parent_id'] = $userId;
                        $insData['user_id'] = $childUser;
                        $this->connectUserWithRole($childUser, 9);
                    } else {
                        $insData['user_id'] = $hasRec->user_id;
                        if (isset($request->practise_email))
                            $this->updateData("users", ["id" => $hasRec->user_id], ["email" => $request->practise_email]);

                        $this->connectUserWithRole($hasRec->user_id, 9);
                    }
                }
                $hasRec = $this->fetchData($this->tbl, $where, 1, []);
                // $this->printR($insData,true);
                if (!is_object($hasRec)) {
                    $this->addData($this->tbl, $insData, 1);
                    try {
                        if ($request->connected_providers != "") {
                            $connectedProviders = $request->connected_providers;
                            // $this->printR($connectedProviders,true);
                            $connectedProviders = json_decode($connectedProviders, true);
                            $ids = array_column($connectedProviders, "value");
                            $this->addOrUpdateLocationUser($ids, isset($insData['user_id']) ? $insData['user_id'] : $request->loc_user_id);
                        }
                    } catch (\Exception $e) {
                    }
                } else {
                    $id = $hasRec->id;
                    $this->updateData($this->tbl, ["id" => $id], $insData);
                    try {
                        if ($request->connected_providers != "") {
                            $connectedProviders = $request->connected_providers;
                            $connectedProviders = json_decode($connectedProviders, true);
                            //$this->printR($connectedProviders,true);
                            $ids = array_column($connectedProviders, "value");
                            $this->addOrUpdateLocationUser($ids, isset($insData['user_id']) ? $insData['user_id'] : $request->loc_user_id);
                        }
                    } catch (\Exception $e) {
                        // echo $e->getMessage();
                        // exit();
                        

                    }
                }
            }
          
        } elseif ($whichSection == "ownership_info") {

            $insData = [];

            // $this->printR($request->all(),true);
            $user = new UserController();
            $childUser = 0;
            if (isset($inputAll['email']) && ($inputAll['email'] != "" && $inputAll['email'] != "undefined") && $inputAll['user_id'] == 0) {
                $request->merge([
                    'first_name' =>  $inputAll['name'],
                    "last_name" =>   $inputAll['last_name'],
                    "email" =>  $inputAll['email'],
                    "role_id" => "10",
                    "password" => "Qwerty123#",
                    "component" => "dd"
                ]);
                $newUser = $user->createUsers($request)->getContent();
                $resArr = json_decode($newUser, true);

                if (isset($resArr["data"]["is_new"]) && $resArr["data"]["is_new"] == true) {
                    $childUser = $resArr["data"]["id"];
                } else {
                    $childUser = $resArr["data"]["id"];
                }
                $insData["first_name"]          = $inputAll["name"] == "undefined" ? NULL : $inputAll["name"];
                $insData["last_name"]           = $inputAll["last_name"] == "undefined" ? NULL : $inputAll["last_name"];
                $insData["dob"]                 = $inputAll["date_of_birth"] == "undefined" ? NULL : $inputAll["date_of_birth"];
                $insData["state_of_birth"]      = $inputAll["state_of_birth"] == "undefined" ? NULL : $inputAll["state_of_birth"];
                $insData["country_of_birth"]    = $inputAll["country_of_birth"] == "undefined" ? NULL : $inputAll["country_of_birth"];
                $insData["ssn"]                 = $inputAll["social_security_number"] == "undefined" ? NULL : $inputAll["social_security_number"];
                $this->updateData("users", ["id" => $childUser], $insData);
                $this->updateData("user_ddownerinfo", ["id" => $inputAll["primary_id"]], ["user_id" => $childUser]);
            }
            $ownershipInfoData = [];
            $ownershipInfoData["index_id"]  = $inputAll["type_of_ownership"] == "undefined" ? NULL : $inputAll["type_of_ownership"];
            $ownershipInfoData["ownership_percentage"]          = $inputAll["ownership"] == "undefined" ? 0 : $inputAll["ownership"];
            $ownershipInfoData["effective_date"] = $inputAll["effective_date"] == "undefined" ? NULL : $inputAll["effective_date"];

            $ownershipInfoData["is_partnership"] = 0;
            if ($inputAll["user_id"] == 0)
                $insData["user_id"] = $childUser;

            if (isset($inputAll["primary_id"]) && $inputAll["primary_id"] != 0) {
                if ($inputAll["user_id"] != 0)
                    $ownershipInfoData["user_id"] = $inputAll["user_id"];

                $this->updateData("user_ddownerinfo", ["id" => $inputAll["primary_id"]], $ownershipInfoData);
            } else {
                $ownershipInfoData["parent_user_id"] = $inputAll["parent_user_id"] == "" ? "0" : $inputAll["parent_user_id"];
                $ownershipInfoData["user_id"] = $inputAll["user_id"] == "" ? "0" : $inputAll["user_id"];
                $this->addData("user_ddownerinfo", $ownershipInfoData, 1);
            }
        } elseif ($whichSection == "payer_info") {
            
            
            $prepDataIns  = [];
            $insData = [];
            
            $insData["user_id"]         = $userId;
            $insData["status"]          = $inputAll["status"];
            $insData["payer_id"]        = $inputAll["payer_id"];
            $insData["payer_status"]    = $inputAll["payer_status"];
            $insData["initiation_date"] = $inputAll["initiation_date"];
            $insData["effective_date"]  = $inputAll["effective_date"];
            $insData["index_id"]        = $inputAll["index_id"];
            $insData["assigned_provider_id"]  = $inputAll["assigned_provider_id"];
            // $this->printR($insData,true);
            $where = [
                ["user_id", "=", $userId],
                ["id", "=", $request->primary_id]
            ];
            $hasRec = $this->fetchData("user_ddpayerinfo", $where, 1, []);
            if (!is_object($hasRec)) {
                $insData["created_at"] = $this->timeStamp();
                $id = $this->addData("user_ddpayerinfo", $insData, 0);
                foreach ($keys as $key) {
                    $pos = strpos($key, "file");
                    if ($pos !== false) {
                        $file = $request->file($key);
                        if ($file != null && $file != "undefined") {
                            // $path = public_path('provider/attachments/' . $id);

                            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                            // if (!file_exists($path)) {
                            //     mkdir($path, 0777, true);
                            // }
                            // $file->move($path, $fileName);
                            $this->uploadMyFile($fileName, $file, "providers/payers/" . $id);
                            // $prepDataIns[$key]   = $fileName;
                            // $prepDataIns["field_value"] = $fileName;
                            $addFileData = [
                                "entities"     => "payer_id",
                                "entity_id"     => $id,
                                "field_key"     => $key,
                                "field_value"   => $fileName
                            ];
                            $whereFile = [
                                ["entities", "=", "payer_id"],
                                ["entity_id", "=", $id],
                                ["field_key", "=", $key],
                            ];
                            $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
                            if (is_object($hasFile))
                                $this->updateData("attachments", $whereFile, $addFileData);
                            else {
                                $aid = $this->addData("attachments", $addFileData, 0);
                                $addMap = ["user_id" => $userId, "attachment_id" => $aid];
                                $this->addData("user_attachment_map", $addMap);
                            }
                        }
                    }
                }
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => 0, "action_taken" => "add", "section" => $whichSection, "log_data" => json_encode($insData), "created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken" => "add","created_at" => $this->timeStamp()]);
            } else {
                $id = $hasRec->id;
                $insData["updated_at"] = $this->timeStamp();
                $this->updateData("user_ddpayerinfo", $where, $insData);
                foreach ($keys as $key) {
                    $pos = strpos($key, "file");
                    if ($pos !== false) {
                        $file = $request->file($key);
                        if ($file != null && $file != "undefined") {
                            // $path = public_path('provider/attachments/' . $id);

                            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                            // if (!file_exists($path)) {
                            //     mkdir($path, 0777, true);
                            // }
                            // $file->move($path, $fileName);
                            // $prepDataIns[$key]   = $fileName;
                            // $prepDataIns["field_value"] = $fileName;
                            $this->uploadMyFile($fileName, $file, "providers/payers/" . $id);
                            $addFileData = [
                                "entities"     => "payer_id",
                                "entity_id"     => $id,
                                "field_key"     => $key,
                                "field_value"   => $fileName
                            ];
                            $whereFile = [
                                ["entities", "=", "payer_id"],
                                ["entity_id", "=", $id],
                                ["field_key", "=", $key],
                            ];
                            $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
                            if (is_object($hasFile))
                                $this->updateData("attachments", $whereFile, $addFileData);
                            else {
                                $aid = $this->addData("attachments", $addFileData, 0);
                                $addMap = ["user_id" => $userId, "attachment_id" => $aid];
                                $this->addData("user_attachment_map", $addMap);
                            }
                        }
                    }
                }
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => 0, "action_taken" => "update", "section" => $whichSection, "log_data" => json_encode($insData), "created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }
            // $this->addData("payer_info", $prepDataIns, 1);
        } elseif ($whichSection == "bank_info") {
            // $prepDataIns  = [];
            $insData = [];
            // foreach ($keys as $key) {
            //     // $prepDataIns["field_key"]   = $key;
            //     // $prepDataIns["field_value"] = $inputAll[$key];
            //     $prepDataIns[$key]   =  $inputAll[$key];
            //     $prepDataIns["provider_id"] = $providerId;
            //     $prepDataIns["dd_id"]       = $ddId;
            //     $prepDataIns["created_at"]  = $this->timeStamp();
            //     array_push($insData, $prepDataIns);
            // }
            $where = [
                ["user_id", "=", $userId]
            ];
            $insData["user_id"] = $userId;
            $insData["bank_name"] = $inputAll["bank_name"];
            $insData["routing_number"] = $inputAll["routing_number"];
            $insData["account_number"] = $inputAll["account_number"];
            $insData["bank_address"] = $inputAll["bank_address"];
            $insData["bank_phone"] = $inputAll["bank_phone"];
            $insData["bank_contact_person"] = $inputAll["contact_person_name"];

            $hasRec = $this->fetchData("user_ddbankinginfo", $where, 1, []);
            if (!is_object($hasRec)) {
                $insData["created_at"] = $this->timeStamp();
                $this->addData("user_ddbankinginfo", $insData, 1);
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "action_taken" => "add", "log_data" => json_encode($insData),"created_at" => $this->timeStamp()]);
            } else {
                $insData["updated_at"] = $this->timeStamp();
                $this->updateData("user_ddbankinginfo", $where, $insData);
                // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "action_taken" => "update", "log_data" => json_encode($insData), "created_at" => $this->timeStamp()]);
            }

            // $this->addData("banking_info", $prepDataIns, 1);
        } elseif ($whichSection == "attachments") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                $pos = strpos($key, "file");
                if ($pos !== false) {
                    $file = $request->file($key);
                    if ($file != null && $file != "undefined") {
                        // $path = public_path('provider/attachments/' . $userId);

                        $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                        // if (!file_exists($path)) {
                        //     mkdir($path, 0777, true);
                        // }
                        // $file->move($path, $fileName);
                        $this->uploadMyFile($fileName, $file, "providers/" . $userId);
                        // $prepDataIns[$key]   = $fileName;
                        // $prepDataIns["field_value"] = $fileName;
                        $addFileData = [
                            "entities"     => "provider_id",
                            "entity_id"     => $userId,
                            "field_key"     => $key,
                            "field_value"   => $fileName
                        ];
                        $whereFile = [
                            ["entities", "=", "provider_id"],
                            ["entity_id", "=", $userId],
                            ["field_key", "=", $key],
                        ];
                        $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
                        if (is_object($hasFile))
                            $this->updateData("attachments", $whereFile, $addFileData);
                        else {
                            $aid = $this->addData("attachments", $addFileData, 0);
                            $addMap = ["user_id" => $userId, "attachment_id" => $aid];
                            $this->addData("user_attachment_map", $addMap);
                        }
                    }
                }
                // $prepDataIns["provider_id"] = $providerId;
                // $prepDataIns["dd_id"]       = $ddId;
                // $prepDataIns["created_at"]  = $this->timeStamp();
                // array_push($insData, $prepDataIns);
            }
            // if (count($prepDataIns)) {

            //     $where = [
            //         ["component","=","provider_id"],
            //         ["entity_id", "=", $ddId]
            //     ];
            //     $hasRec = $this->fetchData("attachments", $where, 1, []);
            //     if (!is_object($hasRec)) {
            //         $this->addData("attachments", $prepDataIns, 1);
            //         $this->addProviderLogs("providers_logs", ["user_id" => $logUserId, "provider_id" => $providerId, "action_taken" => "add", "section" => $whichSection, "log_data" => json_encode($prepDataIns), "created_at" => $this->timeStamp()]);
            //         // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
            //     } else {
            //         $this->updateData("attachments", $where, $prepDataIns);
            //         $this->addProviderLogs("providers_logs", ["user_id" => $logUserId, "provider_id" => $providerId, "action_taken" => "update", "section" => $whichSection, "log_data" => json_encode($prepDataIns), "created_at" => $this->timeStamp()]);
            //         // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            //     }
            //     //$this->addData("attachments", $prepDataIns, 1);
            // }
        } else if ($whichSection == "wishlist") {
            $prepDataIns  = [];
            $insData = [];
            
            //$this->printR($inputAll,true);
            $whishListData = json_decode($inputAll["wishlist_data"],true);
            
            //$this->printR($whishListData,true);
            foreach($whishListData as $wishData) {
                $insData["user_id"]     = $userId;
                $insData["index_id"]    = $wishData["wishlist_id"];
                $insData["name"]        = $wishData["name"];
                $where = [
                    ["user_id", "=", $userId],
                    ["id", "=", $wishData["primary_id"]]
                ];
                $hasRec = $this->fetchData("user_ddwishlist", $where, 1, []);
                if (!is_object($hasRec)) {
                    $insData["created_at"] = $this->timeStamp();
                    $this->addData("user_ddwishlist", $insData, 1);
                    // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => 0, "action_taken" => "add", "section" => $whichSection, "log_data" => json_encode($insData), "created_at" => $this->timeStamp()]);
                    // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
                } else {
                    $insData["updated_at"] = $this->timeStamp();
                    $this->updateData("user_ddwishlist", $where, $insData);
                    // $this->addProviderLogs("providers_logs", ["user_id" => $userId, "provider_id" => 0, "action_taken" => "update", "section" => $whichSection, "log_data" => json_encode($insData), "created_at" => $this->timeStamp()]);
                    // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                }
            }
            // $this->addData("wishlists", $prepDataIns, 1);
        }
        return $this->successResponse(["is_added" => true, "section" => $whichSection], "success");
    }
    /**
     * fetch dropdown option data
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    public function getDropdownsData() {
        $profesionalGroups = $this->fetchProfessionalGroups();
        $profesionalTypesDD = [];
        $profesionalGroupsDD = [];
        if (count($profesionalGroups)) {
            foreach ($profesionalGroups as $group) {
                array_push($profesionalGroupsDD, ["value" => $group->id, "label" => $group->name]);
                $types = $this->fetchProfessionalTypes($group->id);
                if (count($types)) {
                    foreach ($types as $type) {
                        $profesionalTypesDD[$group->id][] = ["value" => $type->id, "label" => $type->name];
                    }
                }
            }
        }
        $citizenships       = $this->fetchData("citizenships", "");
        foreach ($citizenships as $citizenship) {
            $citizenshipsRes[] = ["value" => $citizenship->id, "label" => $citizenship->name];
        }
        $facilityGroupData = $this->fetchData("facilities", ["type" => "group"]);
        $filterFaciltyGroupData = [];
        foreach ($facilityGroupData as $faclityg) {
            //$value = explode(":",$faclityg->facility)[0];
            array_push($filterFaciltyGroupData, ["value" => $faclityg->facility, "label" => $faclityg->facility]);
        }
        $facilityData = $this->fetchData("facilities", ["type" => "solo"]);
        $filterFaciltyData = [];
        foreach ($facilityData as $faclity) {
            array_push($filterFaciltyData, ["value" => $faclity->facility, "label" => $faclity->facility]);
        }
        $response = [
            
            "profesional_groups"        => $profesionalGroupsDD,
            "profesional_types"         => $profesionalTypesDD,
            "citizenships"              => $citizenshipsRes,
            "facilty_group"             => $filterFaciltyGroupData,
            "facilities"                => $filterFaciltyData
            
        ];
        return $this->successResponse($response, "success");
    }
    /**
     * add the provider
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addProvider(Request $request) {
        
        $user = new UserController();
        $inputAll = $request->all();
        $isNewUser = 0;
        $userId = $inputAll['practice_id'];
        $facilitIds = $inputAll['facility_ids'];
        $facilitIdsArr = json_decode($facilitIds, true);
        
        $request->merge([
            'first_name' =>  $inputAll['first_name'],
            "last_name" =>   $inputAll['last_name'],
            "email" =>  $inputAll['email'],
            "role_id" => "4",
            "password" => "Qwerty123#",
            "component" => "dd"
        ]);
        $newUser = $user->createUsers($request)->getContent();
        $resArr = json_decode($newUser, true);

        if (isset($resArr["data"]["is_new"]) && $resArr["data"]["is_new"] == true) {
            $childUser = $resArr["data"]["id"];
            $isNewUser = 1;
        } else {
            $isNewUser = 0;
            $childUser = $resArr["data"]["id"];
        }


        $insData = [];

        $insData["facility_npi"]                = $inputAll['provider_npi'] == "null" ? NULL :$inputAll['provider_npi'];
        $insData["primary_speciality"]          = $inputAll['primary_speciality'] == "null" ? NULL :$inputAll['primary_speciality'];
        $insData["secondary_speciality"]        = $inputAll['secondary_speciality'] == "null" ? NULL : $inputAll['secondary_speciality'];
        
        $insData["phone"]                       = $inputAll['cell_phone'] == "null" ? NULL: $inputAll['cell_phone'];
        $insData["state_of_birth"]              = $inputAll['state_of_birth'] == "null" ? NULL : $inputAll['state_of_birth'];
        $insData["country_of_birth"]            = $inputAll['country_of_birth'] == "null" ? NULL : $inputAll['country_of_birth'];
        $insData["citizenship_id"]              = $inputAll['citizenship_id'] == "null" ? 0 : $inputAll['citizenship_id'];

        $insData["supervisor_physician"]        = $inputAll['supervisor_physician'] == "null" ? NULL : $inputAll['supervisor_physician'];
        $insData["address_line_one"]            = $inputAll['address_line_one'] == "null" ? NULL : $inputAll['address_line_one'];
        $insData["address_line_two"]            = $inputAll['address_line_two'] == "null" ? NULL : $inputAll['address_line_two'];
        $insData["gender"]                      = $inputAll['gender'] == "null" ? NULL : $inputAll['gender'];
        $insData["ssn"]                         = $inputAll['ssn'] == "null" ? NULL : $inputAll['ssn'];
        $insData["dob"]                         = $inputAll['date_of_birth'] == "null" ? NULL : $inputAll['date_of_birth'];
        $insData["hospital_privileges"]         = $inputAll['hospital_privileges'] == "null" ? NULL : $inputAll['hospital_privileges'];
        if ($inputAll['professional_group_id'] != "null")
            $insData["professional_group_id"]       = $inputAll['professional_group_id'];
        if ($inputAll['professional_type_id'] != "null")
            $insData["professional_type_id"]        = $inputAll['professional_type_id'];

        $insData["first_name"]                  = $inputAll['first_name'] == "null" ? NULL : $inputAll['first_name'];
        $insData["last_name"]                   = $inputAll['last_name'] == "null" ? NULL : $inputAll['last_name'];

        // echo $childUser;
        // exit;
        $where__ = [
            ["id", "=", $childUser],
        ];
        $hasRec = $this->fetchData("users", $where__, 1, []);
        //update invidual profile data
        if (is_object($hasRec) && $isNewUser == 1) {
            $this->updateData("users", $where__, $insData);
        } 
       
        //add or update license data
        //try 
        {
            if ($inputAll['driver_license_number'] != "null") {

                $driverLicenseType = 2;
                $whereDriverLicense = [
                    ["user_id", "=", $childUser],
                    ["type_id", "=", $driverLicenseType]
                ];

                $hasDiverLicense = $this->fetchData("user_licenses", $whereDriverLicense, 1, []);

                if (is_object($hasDiverLicense)) {
                    $diverLicense = [];
                    $diverLicense["user_id"] = $childUser;
                    $diverLicense["license_no"] = $inputAll['driver_license_number'];
                    $diverLicense["type_id"] = $driverLicenseType;
                    $diverLicense["status"] = 1;
                    if ($inputAll['driver_license_issue_date'] != "null")
                        $diverLicense["issue_date"] = $inputAll['driver_license_issue_date'];
                    if ($inputAll['driver_license_expiration_date'] != "null")
                        $diverLicense["exp_date"] = $inputAll['driver_license_expiration_date'];
                    if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                        $diverLicense["issuing_state"] = $inputAll['issue_state'];

                    // $diverLicense = ["user_id" => $childUser,"license_no" => $inputAll['driver_license_number'],"issue_date" => $inputAll['driver_license_issue_date'],"exp_date" => $inputAll['driver_license_expiration_date'],"type_id" => $driverLicenseType,"status"=>1];
                    $this->updateData("user_licenses", $whereDriverLicense, $diverLicense);
                } else {
                    // exit("in iff driver");
                    // $diverLicense = ["user_id" => $childUser,"license_no" => $inputAll['driver_license_number'],"issue_date" => $inputAll['driver_license_issue_date'],"exp_date" => $inputAll['driver_license_expiration_date'],"type_id" => $driverLicenseType,"status"=>1];
                    $diverLicense = [];
                    $diverLicense["user_id"] = $childUser;
                    $diverLicense["license_no"] = $inputAll['driver_license_number'];
                    $diverLicense["type_id"] = $driverLicenseType;
                    $diverLicense["status"] = 1;
                    if ($inputAll['driver_license_issue_date'] != "null")
                        $diverLicense["issue_date"] = $inputAll['driver_license_issue_date'];
                    if ($inputAll['driver_license_expiration_date'] != "null")
                        $diverLicense["exp_date"] = $inputAll['driver_license_expiration_date'];
                    if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                        $diverLicense["issuing_state"] = $inputAll['issue_state'];

                    $this->addData("user_licenses", $diverLicense, 0);
                }
            }
            if ($inputAll['state_license_number'] != "null") {
                //$this->printR(,true);
                //exit("Faheem here");
                $stateLicenseType = 1;
                $whereStateLicense = [
                    ["user_id", "=", $childUser],
                    ["type_id", "=", $stateLicenseType]
                ];

                $hasStateLicense = $this->fetchData("user_licenses", $whereStateLicense, 1, []);

                if (is_object($hasStateLicense)) {
                    $stateLicense = [];
                    $stateLicense["user_id"] = $childUser;
                    $stateLicense["license_no"] = $inputAll['state_license_number'];
                    $stateLicense["type_id"] = $stateLicenseType;
                    $stateLicense["status"] = 1;
                    if ($inputAll['state_license_issue_date'] != "null")
                        $stateLicense["issue_date"] = $inputAll['state_license_issue_date'];
                    if ($inputAll['state_license_expiration_date'] != "null")
                        $stateLicense["exp_date"] = $inputAll['state_license_expiration_date'];
                    if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                        $stateLicense["issuing_state"] = $inputAll['issue_state'];

                    $this->updateData("user_licenses", $whereStateLicense, $stateLicense);
                } else {

                    $stateLicense = [];
                    $stateLicense["user_id"] = $childUser;
                    $stateLicense["license_no"] = $inputAll['state_license_number'];
                    $stateLicense["type_id"] = $stateLicenseType;
                    $stateLicense["status"] = 1;
                    if ($inputAll['state_license_issue_date'] != "null")
                        $stateLicense["issue_date"] = $inputAll['state_license_issue_date'];
                    if ($inputAll['state_license_expiration_date'] != "null")
                        $stateLicense["exp_date"] = $inputAll['state_license_expiration_date'];
                    if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                        $stateLicense["issuing_state"] = $inputAll['issue_state'];

                    $this->addData("user_licenses", $stateLicense, 0);
                }
            }
            if ($inputAll['dae_number'] != "null") {
                // exit("Here i am");
                $deaLicenseType = 4;
                $whereDeaLicense = [
                    ["user_id", "=", $childUser],
                    ["type_id", "=", $deaLicenseType]
                ];
                $hasDeaLicense = $this->fetchData("user_licenses", $whereDeaLicense, 1, []);
                if (is_object($hasDeaLicense)) {
                    //$deaLicense = ["user_id" => $childUser,"license_no" => $inputAll['dae_number'],"issue_date" => $inputAll['dae_issue_date'],"type_id" => $deaLicenseType,"status"=>1,"exp_date" => $inputAll['dae_expire_date']];
                    $deaLicense = [];
                    $deaLicense["user_id"] = $childUser;
                    $deaLicense["license_no"] = $inputAll['dae_number'];
                    $deaLicense["type_id"] = $deaLicenseType;
                    $deaLicense["status"] = 1;
                    if ($inputAll['dae_issue_date'] != "null")
                        $deaLicense["issue_date"] = $inputAll['dae_issue_date'];
                    if ($inputAll['dae_expire_date'] != "null")
                        $deaLicense["exp_date"] = $inputAll['dae_expire_date'];
                    if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                        $deaLicense["issuing_state"] = $inputAll['issue_state'];

                    $this->updateData("user_licenses", $whereDeaLicense, $deaLicense);
                } else {
                    //exit("in iffdea");
                    // $deaLicense = ["user_id" => $childUser,"license_no" => $inputAll['dae_number'],"issue_date" => $inputAll['dae_issue_date'],"type_id" => $deaLicenseType,"status"=>1,"exp_date" => $inputAll['dae_expire_date']];
                    $deaLicense = [];
                    $deaLicense["user_id"] = $childUser;
                    $deaLicense["license_no"] = $inputAll['dae_number'];
                    $deaLicense["type_id"] = $deaLicenseType;
                    $deaLicense["status"] = 1;
                    if ($inputAll['dae_issue_date'] != "null")
                        $deaLicense["issue_date"] = $inputAll['dae_issue_date'];
                    if ($inputAll['dae_expire_date'] != "null")
                        $deaLicense["exp_date"] = $inputAll['dae_expire_date'];
                    if (isset($inputAll['issue_state']) && $inputAll['issue_state'] != "null")
                        $deaLicense["issuing_state"] = $inputAll['issue_state'];

                    $this->addData("user_licenses", $deaLicense, 0);
                }
            }
        }
        // catch(\Exception $e) {
        //     echo $e->getMessage();
        //     exit("Faheem");
        // }

        //portals add or update
        $portalData = [];
        $portalData["user_id"]      = $childUser;
        // $portalData["payer_id"]     = 0;
        $portalData["user_name"]    = $inputAll['caqh_user_name'];
        $portalData["password"]     = $inputAll['caqh_password'];
        //$portalData["link"]         = $inputAll['caqh_link'];
        //$portalData["identifier_id"]    = 1;
        $portalData["identifier"]       = $inputAll['cahq_number'];
        $portalData["notes"]       = "NULL";
        $portalData["report"]       = "0";
        //return response(["data" => $insData]);
        $hasPortal = $this->fetchData("portals", ["user_id" => $childUser], 1, []);
        // $this->printR($hasPortal,true);
        // exit("Faheem");
        if (is_object($hasPortal)) {
            //$typeId = $hasPortal->type_id;
            // $this->updateData("portal_types", ["id" => $typeId], ["link" => $inputAll['caqh_link']]);
            $this->updateData("portals", ["user_id" => $childUser], $portalData);
        } else {
            // $portalsTypes = ["name" => "CAQH"];
            // $id = $this->fetchData("portal_types", $portalsTypes, 1, []);
            // $portalData["type_id"] = $id;
            $this->addData("portals", $portalData);
        }



        // $prepDataIns["created_at"]  = $this->timeStamp();
        $where = [
            ["user_id", "=", $childUser],
            ["parent_user_id", "=", $userId]
        ];

        $indvPRelation  = [];
        $indvPRelation["user_id"]                 = $childUser;
        $indvPRelation["parent_user_id"]          = $userId;

        $keys = array_keys($inputAll);
        // $this->printR($keys,true);
        $hasRec = $this->fetchData("user_dd_individualproviderinfo", $where, 1, []);
        if (!is_object($hasRec)) {
            $insData["created_at"] = $this->timeStamp();

            // $insData["user_id"]     = $userId;
            $id = $this->addData("user_dd_individualproviderinfo", $indvPRelation, 0);

            if ($id) {
                //link newly created user to the relavent location / facility
                foreach($facilitIdsArr as $facilitId) {
           
                    if($facilitId['value'] !=0) {
                        $facilitIdInt = $facilitId['value'];
                       $this->addUserToLocation($childUser,$facilitIdInt);
                    }
                }
                //if file exists then upload it on server
                foreach ($keys as $key) {
                    $pos = strpos($key, "file");
                    if ($pos !== false) {
                        $file = $request->file($key);
                        if ($file != null && $file != "undefined") {
                            // $path = public_path('provider/attachments/' . $id);

                            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                           
                            $this->uploadMyFile($fileName, $file, "providers/indviduals/" . $childUser);
                           
                            $addFileData = [
                                "entities"     => "provider_id",
                                "entity_id"     => $childUser,
                                "field_key"     => $key,
                                "field_value"   => $fileName
                            ];
                            $aid = $this->addData("attachments", $addFileData, 0);
                            $addMap = ["user_id" => $childUser, "attachment_id" => $aid];
                            $this->addData("user_attachment_map", $addMap);
                        }
                    }
                }
                return $this->successResponse(["is_added" => true,'provider_id' => $childUser ], "success");
            }
            else {
                return $this->successResponse(["is_added" => false,'provider_id' => $childUser ], "success");
            }
        }
        else {
            //link newly created user to the relavent location / facility
            foreach($facilitIdsArr as $facilitId) {
           
                if($facilitId['value'] !=0) {
                    $facilitIdInt = $facilitId['value'];
                   $this->addUserToLocation($childUser,$facilitIdInt);
                }
            }
            //if file exists then upload it on server
            foreach ($keys as $key) {
                $pos = strpos($key, "file");
                if ($pos !== false) {
                    $file = $request->file($key);
                    if ($file != null && $file != "undefined") {
                        // $path = public_path('provider/attachments/' . $id);

                        $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                        $this->uploadMyFile($fileName, $file, "providers/indviduals/" . $childUser);
                        $addFileData = [
                            "entities"     => "provider_id",
                            "entity_id"     => $childUser,
                            "field_key"     => $key,
                            "field_value"   => $fileName
                        ];
                        $aid = $this->addData("attachments", $addFileData, 0);
                        $addMap = ["user_id" => $childUser, "attachment_id" => $aid];
                        $this->addData("user_attachment_map", $addMap);
                    }
                }
            }
            return $this->successResponse(["is_added" => false,'provider_id' => $childUser], "success");
        }
    
    }
    /**
     * view client given discovery document
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewClientDsicoveryDocument(Request $request, $token)
    {
        $section = $request->section;
        if ($section == "BuisnessInfo") {
            $userId = $token;
            $buisnessInfo           = $this->fetchData("user_dd_businessinformation", ["user_id" => $userId], 1, []);
            $facilityGroupData = $this->fetchData("facilities", ["type" => "group"]);
            $filterFaciltyGroupData = [];
            foreach ($facilityGroupData as $faclityg) {
                array_push($filterFaciltyGroupData, ["value" => $faclityg->facility, "label" => $faclityg->facility]);
            }
            $response = [
                "buisness_info" => $buisnessInfo,
                "facilty_group" => $filterFaciltyGroupData
            ];

            return $this->successResponse($response, "success");
        } elseif ($section == "individual") {
            $userId = $token;
            $profesionalGroups = $this->fetchProfessionalGroups();
            $profesionalTypesDD = [];
            $profesionalGroupsDD = [];
            if (count($profesionalGroups)) {
                foreach ($profesionalGroups as $group) {
                    array_push($profesionalGroupsDD, ["value" => $group->id, "label" => $group->name]);
                    $types = $this->fetchProfessionalTypes($group->id);
                    if (count($types)) {
                        foreach ($types as $type) {
                            $profesionalTypesDD[$group->id][] = ["value" => $type->id, "label" => $type->name];
                        }
                    }
                }
            }

            $facilityData = $this->fetchData("facilities", ["type" => "solo"]);
            $filterFaciltyData = [];
            foreach ($facilityData as $faclity) {
                array_push($filterFaciltyData, ["value" => $faclity->facility, "label" => $faclity->facility]);
            }

            $facilityGroupData = $this->fetchData("facilities", ["type" => "group"]);
            $filterFaciltyGroupData = [];
            foreach ($facilityGroupData as $faclityg) {
                //$value = explode(":",$faclityg->facility)[0];
                array_push($filterFaciltyGroupData, ["value" => $faclityg->facility, "label" => $faclityg->facility]);
            }
            $providerInfo           = $this->fetchIndividualProviders($userId);

            $attachmentsMember      = [];
            $portal = [];
            $licensesData = [];
            $citizenshipsRes = [];
            if (count($providerInfo)) {
                foreach ($providerInfo as $inprovider) {
                    $portalData = Portal::select("portals.user_name", "portals.password", "portals.identifier as caqh_number", "portal_types.link")
                        ->leftJoin("portal_types", "portal_types.id", "=", "portals.type_id")
                        ->where("portals.user_id", "=", $inprovider->user_id)
                        ->first();

                    $portal[$inprovider->user_id] = $portalData;

                    $licensesData[$inprovider->user_id][1] = $this->fetchLicensesData($inprovider->user_id, 1);
                    $licensesData[$inprovider->user_id][2] = $this->fetchLicensesData($inprovider->user_id, 2);
                    $licensesData[$inprovider->user_id][4] = $this->fetchLicensesData($inprovider->user_id, 4);
                }
                $providerInfoArr = $this->stdToArray($providerInfo);
                $ids   = array_column($providerInfoArr, "id");
                $attachmentsMember = Attachments::whereIn("entity_id", $ids)->get();
                if (count($attachmentsMember)) {
                    $url = env("STORAGE_PATH");
                    foreach ($attachmentsMember as $member) {
                        $member->field_value = $url . "providers/indviduals/" . $member->entity_id . "/" . $member->field_value;
                    }
                }
                
            }
            $citizenships       = $this->fetchData("citizenships", "");
            foreach ($citizenships as $citizenship) {
                $citizenshipsRes[] = ["value" => $citizenship->id, "label" => $citizenship->name];
            }
            $response = [
                "member_attachments"        => $attachmentsMember,
                "facilities"                => $filterFaciltyData,
                "portal"                    => $portal,
                "profesional_groups"        => $profesionalGroupsDD,
                "profesional_types"         => $profesionalTypesDD,
                "citizenships"              => $citizenshipsRes,
                "facilty_group"             => $filterFaciltyGroupData,
                "licenses"                  => $licensesData,
                "provider_info"             => $providerInfo,
            ];
            return $this->successResponse($response, "success");

        } elseif ($section == "locations") {
            $userId = $token;
            $practiceLocationInfo = PracticeLocation::where("user_id", "=", $userId)
                ->orWhere("user_parent_id", "=", $userId)
                ->get();

            $linkedLocationUsers = [];
            if (count($practiceLocationInfo) > 0) {
                foreach ($practiceLocationInfo as $locInfo) {
                    $connectedLocationUsers = [];
                    
                    if ($locInfo->user_parent_id != 0) {
                        
                        $practiceLocationMapInfo   = $this->fetchData("individualprovider_location_map", ["location_user_id" => $locInfo->user_id], 0, []);
                        
                        if(count($practiceLocationMapInfo) > 0) {
                            foreach($practiceLocationMapInfo as $locMapInfo) {
                               
                                $assignedLocations = $this->fetchData("users", ["id" => $locMapInfo->user_id], 1, ["id", "first_name", "last_name"]);
                                if(is_object($assignedLocations)) {
                                    $linkedLocationUsers[$locInfo->user_id][] = ["value" => $assignedLocations->id, "label" => $assignedLocations->first_name . " " . $assignedLocations->last_name];
                                }
                            }

                        }
                    }
                }
            }
            $providerInfo           = $this->fetchIndividualProviders($userId);
            $locationProviders = [];
            if (count($providerInfo)) {
                foreach ($providerInfo as $inprovider) {
                    array_push($locationProviders, ["value" => $inprovider->user_id, "label" => $inprovider->first_name . " " . $inprovider->last_name]);
                }
            }
            $response = [
                "selected_location"         => $linkedLocationUsers,
                "location_providers"        => $locationProviders,
                "practice_location_info"    => $practiceLocationInfo
            ];
            return $this->successResponse($response, "success");
        } elseif ($section == "OwnerInfo") {
            $userId = $token;
            $ownershipInfo          = $this->fetchOwnerInfo($userId);
            $providerInfo           = $this->fetchIndividualProviders($userId);
            $locationProviders = [];
            if (count($providerInfo)) {
                foreach ($providerInfo as $inprovider) {
                    array_push($locationProviders, ["value" => $inprovider->user_id, "label" => $inprovider->first_name . " " . $inprovider->last_name]);
                }
            }
            $response = [
                "ownership_info"         => $ownershipInfo,
                "location_providers"        => $locationProviders
            ];
            return $this->successResponse($response, "success");
        } elseif ($section == "PayerInfo") {
            $userId = $token;
            $payerInfo              = $this->fetchData("user_ddpayerinfo", ["user_id" => $userId], 0, []);
            $whishList              = $this->fetchData("user_ddwishlist", ["user_id" => $userId], 0, []);
            $insurances             = Insurance::all();
            $attachmentsPayer = [];
            if (count($payerInfo)) {
                $payerInfoArr = $this->stdToArray($payerInfo);
                $ids   = array_column($payerInfoArr, "id");
                $attachmentsPayer = Attachments::where("entities", "=", "payer_id")->whereIn("entity_id", $ids)->get();
                if (count($attachmentsPayer)) {
                    $url = env("STORAGE_PATH");
                    foreach ($attachmentsPayer as $payer) {
                        $payer->field_value = $url . "providers/payers/" . $payer->entity_id . "/" . $payer->field_value;
                    }
                }
            }
            $response = [
                "whish_list"                => $whishList,
                "payer_info"                => $payerInfo,
                "payer_attachments"         => $attachmentsPayer,
                "insurances"                => $insurances
            ];
            return $this->successResponse($response, "success");
        } elseif ($section == "BankingInfo") {
            $userId = $token;
            $bankInfo               = $this->fetchData("user_ddbankinginfo", ["user_id" => $userId], 1, []);
            $response = [
                "bank_info"                 => $bankInfo
            ];
            return $this->successResponse($response, "success");
        } else {
            // $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);
            $userId = $token;
            $providerId = 0; //$ddData->provider_id;

            $providerObj = new ProviderController();
            $provider = $providerObj->fetchProviderUser($token); //Provider::where("id", "=", $providerId)->first();

            $profesionalGroups = $this->fetchProfessionalGroups();
            $profesionalTypesDD = [];
            $profesionalGroupsDD = [];
            $locationProviders = [];
            if (count($profesionalGroups)) {
                foreach ($profesionalGroups as $group) {
                    array_push($profesionalGroupsDD, ["value" => $group->id, "label" => $group->name]);
                    $types = $this->fetchProfessionalTypes($group->id);
                    if (count($types)) {
                        foreach ($types as $type) {
                            $profesionalTypesDD[$group->id][] = ["value" => $type->id, "label" => $type->name];
                        }
                    }
                }
            }
            // $this->printR($profesionalGroups,true);
            // $ddId = 0;//$ddData->id;

            // $where = [
            //     ["provider_id", "=", $providerId],
            //     ["dd_id", "=", $ddId]
            // ];

            $whishList              = $this->fetchData("user_ddwishlist", ["user_id" => $userId], 0, []);
            // $providerId_            = $request->provider_id;
            $attachments            = $this->fetchData("attachments", ["entities" => "provider_id", "entity_id" => $userId], 0, []);
            $attachmentsArr = [];
            $facilityData = $this->fetchData("facilities", ["type" => "solo"]);
            $filterFaciltyData = [];
            foreach ($facilityData as $faclity) {
                array_push($filterFaciltyData, ["value" => $faclity->facility, "label" => $faclity->facility]);
            }
            if (count($attachments)) {
                $url = env("STORAGE_PATH");
                foreach ($attachments as $attachment) {
                    $filePath = $url . "providers/" . $attachment->entity_id . "/" . $attachment->field_value;
                    $attachmentsArr[] = [
                        "id"            => $attachment->id,
                        "created_by"    => $attachment->created_by,
                        "entities"      => $attachment->entities,
                        "entity_id"     => $attachment->entity_id,
                        "field_key"     => $attachment->field_key,
                        "field_value"   => $filePath
                    ];
                }
            }

            $facilityGroupData = $this->fetchData("facilities", ["type" => "group"]);
            $filterFaciltyGroupData = [];
            foreach ($facilityGroupData as $faclityg) {
                //$value = explode(":",$faclityg->facility)[0];
                array_push($filterFaciltyGroupData, ["value" => $faclityg->facility, "label" => $faclityg->facility]);
            }
            // if (count($attachments)) {
            //     $url = env("STORAGE_PATH");
            //     foreach ($attachments as $attachment) {
            //         $attachment->field_value = $url . "providers/" . $attachment->entity_id . "/" . $attachment->field_value;
            //     }
            // }

            $bankInfo               = $this->fetchData("user_ddbankinginfo", ["user_id" => $userId], 1, []);
            $payerInfo              = $this->fetchData("user_ddpayerinfo", ["user_id" => $userId], 0, []);
            $attachmentsPayer = [];
            if (count($payerInfo)) {
                $payerInfoArr = $this->stdToArray($payerInfo);
                $ids   = array_column($payerInfoArr, "id");
                $attachmentsPayer = Attachments::whereIn("entity_id", $ids)->get();
                if (count($attachmentsPayer)) {
                    $url = env("STORAGE_PATH");
                    foreach ($attachmentsPayer as $payer) {
                        $payer->field_value = $url . "providers/payers/" . $payer->entity_id . "/" . $payer->field_value;
                    }
                }
            }
            $ownershipInfo          = $this->fetchOwnerInfo($userId);
            $practiceLocationInfo   = $this->fetchData($this->tbl, ["user_id" => $userId], 0, []);
            $practiceLocationInfo = PracticeLocation::where("user_id", "=", $userId)
                ->orWhere("user_parent_id", "=", $userId)
                ->get();
            $connectedLocationUsers = [];
            if (count($practiceLocationInfo) > 0) {
                foreach ($practiceLocationInfo as $locInfo) {
                    if ($locInfo->user_parent_id != 0) {
                        $assignedLocations = $this->fetchData($this->tblU, ["id" => $locInfo->user_id], 0, ["id", "first_name", "last_name"]);
                        if (count($assignedLocations)) {
                            foreach ($assignedLocations as $al) {
                                array_push($connectedLocationUsers, ["value" => $al->id, "label" => $al->first_name . " " . $al->last_name]);
                            }
                        }
                    }
                }
            }
            $providerInfo           = $this->fetchIndividualProviders($userId);
            // if(count($providerInfo)) {
            //     $providerInfo           = $this->fetchData("user_dd_individualproviderinfo", ["parent_user_id" => $userId], 0, []);
            // }
            $attachmentsMember      = [];
            $portal = [];
            $licensesData = [];
            if (count($providerInfo)) {
                foreach ($providerInfo as $inprovider) {
                    $portalData = Portal::select("portals.user_name", "portals.password", "portals.identifier as caqh_number", "portal_types.link")
                        ->leftJoin("portal_types", "portal_types.id", "=", "portals.type_id")
                        ->where("portals.user_id", "=", $inprovider->user_id)
                        ->first();

                    $portal[$inprovider->user_id] = $portalData;

                    $licensesData[$inprovider->user_id][1] = $this->fetchLicensesData($inprovider->user_id, 1);
                    $licensesData[$inprovider->user_id][2] = $this->fetchLicensesData($inprovider->user_id, 2);
                    $licensesData[$inprovider->user_id][4] = $this->fetchLicensesData($inprovider->user_id, 4);

                    array_push($locationProviders, ["value" => $inprovider->user_id, "label" => $inprovider->first_name . " " . $inprovider->last_name]);
                }
                $providerInfoArr = $this->stdToArray($providerInfo);
                $ids   = array_column($providerInfoArr, "id");
                $attachmentsMember = Attachments::whereIn("entity_id", $ids)->get();
                if (count($attachmentsMember)) {
                    $url = env("STORAGE_PATH");
                    foreach ($attachmentsMember as $member) {
                        $member->field_value = $url . "providers/indviduals/" . $member->entity_id . "/" . $member->field_value;
                    }
                }
            }
            // if(count($providerInfo) == 0) {
            //     $providerInfo           = $this->fetchData("group_provider_info",["provider_id" => $providerId],0,[]);
            // }
            $buisnessInfo           = $this->fetchData("user_dd_businessinformation", ["user_id" => $userId], 1, []);
            $addendum               = $this->fetchData("addendum", ["user_id" => $userId], 0, []);
            $citizenships       = $this->fetchData("citizenships", "");
            $citizenshipsRes = [];
            foreach ($citizenships as $citizenship) {
                $citizenshipsRes[] = ["value" => $citizenship->id, "label" => $citizenship->name];
            }
            $response = [
                "whish_list"                => $whishList,
                "attachments"               => $attachmentsArr,
                "bank_info"                 => $bankInfo,
                "payer_info"                => $payerInfo,
                "ownership_info"            => $ownershipInfo,
                "practice_location_info"    => $practiceLocationInfo,
                "provider_info"             => $providerInfo,
                "buisness_info"             => $buisnessInfo,
                "addendum"                  => $addendum,
                "provider_id"               => $providerId,
                "provider"                  => $provider,
                "member_attachments"        => $attachmentsMember,
                "payer_attachments"         => $attachmentsPayer,
                "facilities"                => $filterFaciltyData,
                "portal"                    => $portal,
                "profesional_groups"        => $profesionalGroupsDD,
                "profesional_types"         => $profesionalTypesDD,
                "location_providers"        => $locationProviders,
                "selected_location"         => $connectedLocationUsers,
                "citizenships"              => $citizenshipsRes,
                "facilty_group"             => $filterFaciltyGroupData,
                "licenses"                  => $licensesData
            ];
            return $this->successResponse($response, "success");
        }
    }
    /**
     * delete the discovery document section data
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteClientDsicoveryDocument(Request $request, $token, $sectionId)
    {

        $whichSection = $request->which_section;

        // $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);

        // $providerId = $ddData->provider_id;

        // $ddId = $ddData->id;
        $userId = $token;
        if ($whichSection == "ownership_info") {
            $where = [
                ["id", "=", $sectionId]
            ];
            $isDel = $this->deleteData("user_ddownerinfo", $where);
            if ($isDel) {
                $where_ = [
                    ["user_id", "=", $userId]
                ];
                $oInfo              = $this->fetchData("user_ddownerinfo", $where_, 0, []);
                if (count($oInfo)) {
                    foreach ($oInfo as $key => $info) {
                        $oId = $key + 1;
                        $where__ = [
                            ["user_id", "=", $userId],
                            ["id", "=", $info->id]
                        ];
                        $this->updateData("user_ddownerinfo", $where__, ["index_id" => $oId]);
                    }
                }
            }
            return $this->successResponse(["id_delete" => $isDel], "success");
        } else if ($whichSection == "payer_info") {

            $where = [
                ["id", "=", $sectionId]
            ];
            $isDel = $this->deleteData("user_ddpayerinfo", $where);
            if ($isDel) {
                $where_ = [
                    ["user_id", "=", $userId]
                ];
                $payerInfo              = $this->fetchData("user_ddpayerinfo", $where_, 0, []);
                if (count($payerInfo)) {
                    foreach ($payerInfo as $key => $info) {
                        $payerId = $key + 1;
                        $where__ = [
                            ["id", "=", $info->id]
                        ];
                        $this->updateData("user_ddpayerinfo", $where__, ["index_id" => $payerId]);
                    }
                }
            }
            return $this->successResponse(["id_delete" => $isDel], "success");
        } else if ($whichSection == "wishlists") {
            $userId = "555";
            $where = [
                ["id", "=", $sectionId]
            ];
            $isDel = $this->deleteData("user_ddwishlist", $where);
            if ($isDel) {
                $where_ = [
                    ["user_id", "=", $userId]
                ];
                $wishlistInfo              = $this->fetchData("user_ddwishlist", $where_, 0, []);
                if (count($wishlistInfo)) {
                    foreach ($wishlistInfo as $key => $info) {
                        $where__ = [
                            ["id", "=", $info->id]
                        ];
                        $wishlistId = $key + 1;
                        $this->updateData("user_ddwishlist", $where__, ["index_id" => $wishlistId]);
                    }
                }
            }
            return $this->successResponse(["id_delete" => $isDel], "success");
        }
    }
   
    /**
     * add the discovery document section data
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addAddendumData(Request $request)
    {

        // $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);

        // $providerId = $ddData->user_id;

        // $ddId = $ddData->id;

        $userId = $request->user_id;

        $where = [
            ["user_id", "=", $userId]
        ];

        $dataExist   = $this->fetchData("addendum", $where, 0, []);
        $inputAll = $request->except(["member_id"]);
        //$inputAll = $request->all();
        $keys = array_keys($inputAll);
        $prepDataIns = [];
        foreach ($keys as $key) {
            $prepDataIns[$key]   = $inputAll[$key];
        }
        $prepDataIns["user_id"] = $userId;
        // $prepDataIns["dd_id"]       = $ddId;

        if (count($dataExist)) {
            $prepDataIns["updated_at"]  = $this->timeStamp();
            $this->updateData("addendum", $where, $prepDataIns);
            return $this->successResponse(["is_updated" => true], "success");
        } else {
            $prepDataIns["created_at"]  = $this->timeStamp();
            $this->addData("addendum", $prepDataIns, 1);
            return $this->successResponse(["is_added" => true], "success");
        }
    }
    /**
     * fetch states / cities
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchStatesCitiesData(Request $request)
    {

        $statesCitiesData = StatesCities::select("state")->distinct()->get();

        $stateAndCities = [];

        foreach ($statesCitiesData as $state) {
            $stateAndCities[$state->state] =  StatesCities::where("state", "=", $state->state)->select("city")->distinct()->get();
        }

        // $this->printR($statesCitiesData,true);
        return $this->successResponse(["stats" => $statesCitiesData, "cities" => $stateAndCities, "count" => count($statesCitiesData)], "success");
    }
    /**
     * check for discovery token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function discoveryToken(Request $request)
    {

        $providerId = $request->provider_id;

        $ddData = DiscoveryDocument::where("provider_id", "=", $providerId)->first(["dd_token"]);

        if (!is_object($ddData)) {
            $request->merge([
                "provider_id" => $providerId,
                "company_id" => "null"
            ]);

            $discoverControllerObj = new DiscoverydocumentController();

            $discoverControllerObj->store($request); //generate the discovery related tokens

            $ddData = DiscoveryDocument::where("provider_id", "=", $providerId)->first(["dd_token"]);

            return $this->successResponse(["dd_data" => $ddData], "success");
        } else {
            return $this->successResponse(["dd_data" => $ddData], "success");
        }
    }
    /**
     * check for discovery token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteMemberProvider(Request $request)
    {

        $where = [
            ["id", "=", $request->id],
        ];

        $user = $this->fetchData("user_dd_individualproviderinfo", $where, 1, ["user_id"]);

        if (is_object($user)) {
            $userId = $user->user_id;

            try {

                $this->deleteData("user_dd_individualproviderinfo", $where);
            } catch (\Throwable $exception) {
            }

            try {
                $this->deleteData("users", ["id" => $userId]);
            } catch (\Throwable $exception) {
            }

            try {
                $this->deleteData("personal_access_tokens", ["tokenable_id " => $userId]);
            } catch (\Throwable $exception) {
            }
        } else {

            $where_ = [
                ["id", "=", $request->id]
            ];

            $this->deleteData("user_dd_individualproviderinfo", $where);

            // $provider = $this->fetchData("providers", $where_, 1, ["num_of_provider"]);

            // $newCountProviderCnt = (int)$provider->num_of_provider - 1;

            // $this->updateData("providers", $where_, ["num_of_provider" => $newCountProviderCnt]);

            // try {
            //     $this->deleteData("group_provider_info", ["id" => $request->member_id]);
            //     // $soloProviders = $this->fetchData("group_provider_info",$where,0,[]);

            //     // if(count($soloProviders)) {
            //     //     foreach($soloProviders as $key => $provider) {
            //     //         $memberId = $key + 1;
            //     //         $this->updateData("group_provider_info",["id" => $provider->id],["member_id" => $memberId]);
            //     //     }
            //     // }
            // } catch (\Throwable $exception) {
            // }
        }
        return $this->successResponse(["is_deleted" => true], "success");
    }
    
    /**
     * fetch user dd token and provider id
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchUserDiscoveryData(Request $request)
    {
        // $licenses = new LicenseController();

        // $licenses = $licenses->index($request)->getContent();

        // $stats = new EnrollmentStats();

        // $providerId = $request->provider_id;

        // $enrollment = $stats->enrollmentStats($request)->getContent();

        // $licensesArr = json_decode($licenses, true);

        // $licensesArr = $licensesArr['data'];
        // $this->printR($licensesArr,true);
        $providerData = $this->fetchData("user_dd_individualproviderinfo", ["user_id" => $request->user_id], 0);
        if (count($providerData) == 0)
            $providerData = $this->fetchData("user_dd_individualproviderinfo", ["parent_user_id" => $request->user_id], 0);

        $providerData = $this->stdToArray($providerData);
        $addendumArr = [];
        if (count($providerData)) {
            foreach ($providerData as $provider) {
                $userId = $provider["user_id"];
                $addendum = $this->fetchData("addendum", ["user_id" => $userId], 1);
                $addendumArr[$userId] = $addendum;
            }
        }


        return $this->successResponse(["provider_data" => $providerData, 'addendum' => $addendumArr], "success");
    }
    /**
     * add the new provider
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addProovider(Request $request)
    {
        $providerId = $request->provider_id;
        // $where_ = [
        //     ["id", "=", $providerId]
        // ];

        // $provider = $this->fetchData("providers", $where_, 1, ["num_of_provider"]);

        // $newCountProviderCnt = (int)$provider->num_of_provider + 1;

        // $this->updateData("providers", $where_, ["num_of_provider" => $newCountProviderCnt]);
        $addDataArr = ["user_id" => 0];
        $this->addData("user_dd_individualproviderinfo", $addDataArr, 0);

        return $this->successResponse(["is_added" => true], "success");
    }
    /**
     * add the new provider
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchLicenseTypesData()
    {
        $licenses = $this->fetchData("license_types", "");
        return $this->successResponse(["licenses" => $licenses], "success");
    }
    /**
     * initiate the new task
     *
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function initiateECATask(Request $request) {
        $request->validate([
            "user_id" => "required",
            "comment" => "required",
        ]);
        
        $userId = $request->user_id;   
        
        $comment = $request->comment;   

        $addTaskData = ["user_id" => $userId, "comment" => $comment];
        
        $insId = TaskManager::insertGetId($addTaskData);

        return $this->successResponse(["id" => $insId], "Task initiated successfully");

    }

    /**
     * fetch the eca tasks
     *
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchECATasks(Request $request) {
        
        
        $tasks = TaskManager::paginate($this->cmperPage);

        return $this->successResponse(["data" => $tasks], "success");

    }
    /**
     * update the for credentialing status
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function forCredentailing(Request $request,$id) {

        $forCrdentialing = $request->input('for_credentailing');
        $updateType = $request->input('update_type');
        $form = $request->input('from');
        $to = $request->input('to');
        $createdBy = $request->input('created_by');
        $comment = $request->input('comment');
        $updateData = ["for_credentialing" => $forCrdentialing];
        
        // exit("here");
        $where = ["id" => $id];
        $table = $updateType == "location" ? $this->tbl: "individualprovider_location_map";
        $isUpdate = $this->updateData($table,$where,$updateData);
        if( $updateType == "location") {
            // exit("in iff");
            $locationData = $this->fetchData($this->tbl,["id" => $id],1,['user_id','user_parent_id']);
            // $this->printR($locationData,true);
            $ActiveInActiveLogsObj = new ActiveInActiveLogs();
            $ActiveInActiveLogsObj->manageSpecificLocationActiveInactivityLog($locationData->user_parent_id,$locationData->user_id,$forCrdentialing,$form,$to,$createdBy,$comment);
            $ActiveInActiveLogsObj = NULL;
        }
        else {
            $ActiveInActiveLogsObj = new ActiveInActiveLogs();
            $ActiveInActiveLogsObj->manageSpecificProviderActivityLogs($id,$forCrdentialing,$form,$to,$createdBy,$comment);
            $ActiveInActiveLogsObj = NULL;
        }
        if($updateType == "location") {
            $locationUserId = $request->input("location_userid");
            $this->updateAffliatedContacts($locationUserId,$forCrdentialing);
            $this->updateData("users",["id" => $locationUserId],["deleted" => $forCrdentialing == 1 ? 0 : 1]);
        }

        return $this->successResponse(["is_update" => $isUpdate], "Update successfully");
    }
    /**
     * on / off board users
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function onOffBoard(Request $request) {

        $userType = $request->input('user_type');
        $status = $request->input('status');
        $userId = $request->input('user_id');
        $createdBy = $request->input('created_by');
        $from = $request->input('from');
        $to = $request->input('to');
        $comment = $request->input('comment');
        
        $userToken = $this->getUserAccessToken($createdBy);
        $token = isset($userToken) ? $userToken->token : "";
        $serverURL = $this->getServerURL();
        $port = 801;
        if(Str::contains($serverURL,"127.0.0.1")) {
            $port = 8001;
        }
        $response = NULL;
        // $apiURL = $serverURL.":".$port.'/api/v1/handle/off-on-boarding';
        // echo $token;
        // exit;
        if($userType == "location") {
            $parentId = $request->input('parent_id');
            $table1 = $this->tbl;
            $table2 = $this->tblU;
            $table3 = "individualprovider_location_map";

            $this->updateData($table1,["user_id" => $userId],["for_credentialing" => $status,'updated_at' => $this->timeStamp()]);
            $this->updateData($table2,["id" => $userId],["deleted" => $status == 1 ? 0 : 1,'updated_at' => $this->timeStamp()]);
            $this->updateData($table3,["location_user_id" => $userId],["for_credentialing" => $status,'updated_at' => $this->timeStamp()]);
            $ActiveInActiveLogsObj = new ActiveInActiveLogs();
            $ActiveInActiveLogsObj->manageLocationActiveInactivityLog($parentId,$userId,$status,$from,$to,$createdBy,$comment);
            $ActiveInActiveLogsObj = NULL;
            
           //code for tell billing about directory activity log
            // $response = Http::withHeaders([
            //     'Authorization' => 'Bearer '.$token,
            //     "Accept"=> 'application/json'
            // ])->post($apiURL, [
            //     'user_id'       => $createdBy,
            //     'facility_id'   => $userId,
            //     'practice_id'   => $parentId,
            //     'status'        => $status,
            //     'provider_id'   => 0,
            // ]);

        }
        else if($userType == "Practice") {
            $table1 = $this->tbl;
            $table2 = $this->tblU;
            $table3 = "individualprovider_location_map";

            $this->updateData($table1,["user_parent_id" => $userId],["for_credentialing" => $status]);
            $connectedLocations = $this->fetchData($table1,["user_parent_id" => $userId],0,["user_id"]);
            //$this->printR($connectedLocations,true);
            foreach($connectedLocations  as $location) {
                $this->updateData($table3,["location_user_id" => $location->user_id],["for_credentialing" => $status,'updated_at' => $this->timeStamp()]);
                $this->updateData($table2,["id" => $location->user_id],["deleted" => $status == 1 ? 0 : 1,'updated_at' => $this->timeStamp()]);
                $userInManyLocation = $this->fetchData($table3,["location_user_id" => $location->user_id],0,["user_id"]);
                if(count($userInManyLocation)) {//this code for in active the providers with practice and location
                    foreach($userInManyLocation as $provider) {
                        if($status == 0) {
                            $inOtherLocation = $this->fetchData($table3,["user_id" => $provider->user_id,'for_credentialing' => 1,'updated_at' => $this->timeStamp()],0,["user_id"]);
                            if(count($inOtherLocation) == 0) {
                                $this->updateData($table2,["id" => $provider->user_id],["deleted" => $status == 1 ? 0 : 1,'updated_at' => $this->timeStamp()]);
                            }
                        }
                        else {
                            $this->updateData($table2,["id" => $provider->user_id],["deleted" => $status == 1 ? 0 : 1,'updated_at' => $this->timeStamp()]);
                        }
                    }
                }
            }
            $this->updateData($table2,["id" => $userId],["deleted" => $status == 1 ? 0 : 1,'updated_at' => $this->timeStamp()]);
            $ActiveInActiveLogsObj = new ActiveInActiveLogs();
            $ActiveInActiveLogsObj->managePracticeActiveInActiveLogs($userId,$status,$from,$to,$createdBy,$comment);
            $ActiveInActiveLogsObj = NULL;
            //code for tell billing about directory activity log
            // $response = Http::withHeaders([
            //     'Authorization' => 'Bearer '.$token,
            //     "Accept"=> 'application/json'
            // ])->post($apiURL, [
            //     'user_id'       => $createdBy,
            //     'facility_id'   => 0,
            //     'practice_id'   => $userId,
            //     'status'        => $status,
            //     'provider_id'   => 0,
            // ]);
           
        }
        else if($userType == "individual") {
            
            $table1 = "users";
            $table2 = "individualprovider_location_map";
            
            
            $this->updateData($table1,["id" => $userId],["deleted" => $status == 1 ? 0 : 1,'updated_at' => $this->timeStamp()]);
            $this->updateData($table2,["user_id" => $userId],["for_credentialing" => $status,'updated_at' => $this->timeStamp()]);
            
            $ActiveInActiveLogsObj = new ActiveInActiveLogs();
            $ActiveInActiveLogsObj->manageProviderActivityLogs($userId,$status,$from,$to,$createdBy,$comment);
            $ActiveInActiveLogsObj = NULL;
            //code for tell billing about directory activity log
            // $response = Http::withHeaders([
            //     'Authorization' => 'Bearer '.$token,
            //     "Accept"=> 'application/json'
            // ])->post($apiURL, [
            //     'user_id'       => $createdBy,
            //     'facility_id'   => 0,
            //     'practice_id'   => 0,
            //     'status'        => $status,
            //     'provider_id'   => $userId,
            // ]);
        }
        return $this->successResponse(["is_update" => true], "Updated successfully");
    }
    /**
     * credentialing  task filters 
     * 
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     */
    function credentialingTaskFilters(Request $request) {
        $userCommonFuncApiObj = new UserCommonFuncApi();
        $isActive = $request->has("is_active") ? $request->is_active : 1;
        $userId = $request->has("user_id")   ? $request->user_id : 0;
        $facilityFilter = $request->has("facility_filter") ? $request->facility_filter : "";
        if(strlen($facilityFilter) > 2) {
            $facilityFilter = json_decode($facilityFilter,true);
            // $this->printR($facilityFilter,true);
            $facilityFilter = array_column($facilityFilter,"value");
        }
        else
            $facilityFilter = "";

        // $this->printR($facilityFilter,true);    
        $request->merge([
            "for_credentialing" => $isActive,
            'usage_type' => 'creds'
        ]);
        if($request->has("user_id") && $request->get("user_id") > 0 ) {
            $request->merge([
                "is_single" => 1,
                'location_id' => $userId,
                'is_active' => $isActive,
                'usage_type' => 'creds'

            ]);
        }
        if($request->has("facility_filter") && is_array($facilityFilter)) {
            $request->merge([
                "is_multi" => 1,
                'location_id' => implode(",",$facilityFilter),
                'is_active' => $isActive,
                'usage_type' => 'creds'

            ]);
        }
        
        $filters = json_decode($userCommonFuncApiObj->fetchCredsFilters($request)->getContent());
        $filtersArrMain = $filters->data;
        // $this->printR($filtersArrMain,true);
        if($userId !=0) {

            $filters = json_decode($userCommonFuncApiObj->fetchSelectedLocationProviders($request)->getContent());

            $filtersArr = $filters->data;
        
            $userCommonFuncApiObj = NULL;
            
            $filtersArrMain->all_providers = isset($filtersArr->filtered_providers) ? $filtersArr->filtered_providers : $filtersArrMain->all_providers;
            // $this->printR($filtersArrMain,true);
            return $this->successResponse([
                "creds_filters" => $filtersArrMain
            ],"success");
        }
        else {
            
            // $this->printR($request->all(),true);
            $filters = json_decode($userCommonFuncApiObj->fetchSelectedLocationProviders($request)->getContent());
            // $this->printR($filters,true);
            $filtersArr = $filters->data;
           
            $filtersArrMain->all_providers = isset($filtersArr->filtered_providers) && $facilityFilter !="" ? $filtersArr->filtered_providers : $filtersArrMain->all_providers;
            
        //    exit("hi");
            $userCommonFuncApiObj = NULL;
            return $this->successResponse([
                "creds_filters"                 => $filtersArrMain
            ],"success");
        }
        
    }
    /**
     *fetch the profile practices
     *  
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
    **/
    function fetchProfilePractices(Request $request) {
        
        $statsObj = new Stats(); 
        $resJSON = [];
        if($request->has('provider_id') && $request->provider_id) {
            
            $providerId = $request->provider_id;

            $providerPractice = $statsObj->providerPractices($providerId);
            if (count($providerPractice)) {
            
                foreach($providerPractice as $practice) {
                    $providerFacility = $statsObj->providerFacilities($providerId,$practice->practice_id);
                    $resJSON['facility'][$practice->practice_id] = $providerFacility;
                }
            }
            $resJSON['practices'] = $providerPractice;
        }
        if($request->has('location_id') && $request->location_id) {
            $locationId = $request->location_id;
            $practices = $statsObj->fetchEnrollmentsPractices($locationId);
            $resJSON['practices'] = $practices;
            foreach($practices as $practice) {
                $facilities = $this->getFacilities($practice->practice_id);
                $resJSON['facility'][$practice->practice_id] = $facilities;
            }
        }
        $statsObj = NULL;

        return $this->successResponse($resJSON, "success");
        
    }
    /**
     *fetch the practice affiliation data 
     * 
     *  @param  \Illuminate\Http\Request  $request
     *  @return \Illuminate\Http\Response
     **/
    function fetchPracticeInfo(Request $request) {

        
        $practiceId = $request->practice_id;
        $userId = $request->user_id;
        $type = $request->type;
        if($type == "owner")
            $practice = $this->ownerAfflietedLocation($userId,$practiceId);
        else
            $practice = $this->fetchPracticeDetailInfo($practiceId);

        return $this->successResponse($practice, "success");

    }
   
    public function fatchCredentialingTaskLogs(Request $request){

        try {
            $searching = $request->get('search');
                
            if( $request->has('search')) {
   
                $credentialingTaskLogs = CredentialingActivityLog::select("credentialing_task_logs.*","user_id",DB::raw
                ("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"))
    
                ->join('users', 'users.id', '=', 'credentialing_task_logs.user_id')

                ->whereRaw(DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) LIKE '%$searching%'"))
    
                ->orderBy("credentialing_task_logs.user_id","DESC")
    
                ->paginate($this->cmperPage);

                
            }
            else {
        
                $credentialingTaskLogs = CredentialingActivityLog::select("credentialing_task_logs.*","user_id",DB::raw
                ("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"))
    
                ->join('users', 'users.id', '=', 'credentialing_task_logs.user_id')

                ->whereRaw(DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) LIKE '%$searching%'"))
    
                ->orderBy("credentialing_task_logs.user_id","DESC")
    
                ->paginate($this->cmperPage);
            }

            return $this->successResponse(["credentialingtasklogs" => $credentialingTaskLogs ],"success",200);
        }

        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }

    }

}
