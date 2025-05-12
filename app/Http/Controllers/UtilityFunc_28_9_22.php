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
use App\Models\W9form;
use App\Models\Insurance;
use App\Models\Invoice;
use App\Models\ProviderMember;
use App\Http\Controllers\Api\DiscoverydocumentController;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\StatesCities;
use Mail;
use App\Mail\ProviderCredentials;
use App\Models\ProviderCompanyMap;

class UtilityFunc extends Controller
{
    use ApiResponseHandler, Utility, EditImage;
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
    public function viewDiscoveryDocument($ddToken = "", Request $request)
    {

        $additionParam = $request->has("member") ? $request->member : 0;
        try {
            if ($ddToken != "") {
                $ddData = DiscoveryDocument::where("dd_token", "=", $ddToken)->first(["provider_id"]);
                if (is_object($ddData)) {
                    //echo $ddToken.":token is valid:".$ddData->provider_id;
                    $provider = Provider::find($ddData->provider_id);
                    $insurances = Insurance::all();

                    return $this->successResponse(['provider' => $provider, "insurances" => $insurances], "success");
                } elseif ($additionParam == 1 && !is_object($ddData)) {

                    // $ddData = ProviderMember::where("token", "=", $ddToken)->first();

                    // $provider = Provider::find($ddData->provider_id);
                    // $insurances = Insurance::all();

                    return $this->successResponse(['member_data' => []], "success");
                } else
                    return $this->warningResponse([], "Please provide the valid discovery document token.", 400);
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
            $w9Exist = W9form::where("provider_id", "=", $providerId)->count();

            $idAdd = false;
            $isUpdate = false;
            if ($w9Exist == 0) {
                $idAdd = true;
                $w9FormData["provider_id"] = $providerId;
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
                W9form::where("provider_id", "=", $providerId)->update($w9FormData);
            }

            $fileName = $providerId . '-w9-1';

            $this->saveW9FormAsPdf($fileName); //save w9 file as pdf against this rpovider

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
        $w9FormView = public_path("w9form/" . $fileName);
        if (file_exists($w9FormView)) {
            $pdfUrl = url("w9form/" . $fileName);
            return $this->successResponse(["w9form" => $pdfUrl, 'file_created' => true], "success");
        } else
            return $this->successResponse(["w9form" => NULL, 'file_created' => false], "success");
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

        $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

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

                            $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

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

                            $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

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

        $logUserId = $request->has("user_id") ? $request->user_id : 0;


        $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);

        $providerId = $ddData->provider_id;

        $provider = Provider::where("id", "=", $providerId)->first(["provider_type", "legal_business_name"]);

        $providerType = $provider->provider_type;

        $legalBName = $provider->legal_business_name;

        $ddId = $ddData->id;
        $inputAll = $request->except(["which_section"]);
        $keys = array_keys($inputAll);

        if ($whichSection == "buisness_info") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                $prepDataIns[$key]   = $inputAll[$key];
                //$prepDataIns["field_value"] = $inputAll[$key];
                $prepDataIns["provider_id"] = $providerId;
                $prepDataIns["dd_id"]       = $ddId;
                $prepDataIns["created_at"]  = $this->timeStamp();
                // array_push($insData, $prepDataIns);
            }

            // $this->printR($prepDataIns,true);
            $where = [
                ["provider_id","=",$providerId],
                ["dd_id","=",$ddId]
            ];
            $hasRec = $this->fetchData("buisnessinfo",$where,1,[]);
            if(!is_object($hasRec)) {
                $this->addData("buisnessinfo", $prepDataIns, 1);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "add","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }
            else {
                $this->updateData("buisnessinfo",$where, $prepDataIns);
                // $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"action_taken" => "update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "update","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }

            // $this->addData("buisnessinfo", $prepDataIns, 1);
        } elseif ($whichSection == "individual_provider") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                $pos = strpos($key, "file");
                if ($pos !== false) {
                    $file = $request->file($key);
                    if ($file != null && $file != "undefined") {
                        $path = public_path('provider/attachments/' . $providerId);

                        $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

                        if (!file_exists($path)) {
                            mkdir($path, 0777, true);
                        }
                        $file->move($path, $fileName);
                        $prepDataIns[$key]   = $fileName;
                        // $prepDataIns["field_value"] = $fileName;
                    }
                } else {
                    // exit("in else faheem");
                    $prepDataIns[$key]   = $inputAll[$key];
                    // $prepDataIns["field_value"] = $inputAll[$key];

                   
                }
                // array_push($insData, $prepDataIns);
            }

            $userId = 0;
           
            $prepDataIns["provider_id"] = $providerId;
            $prepDataIns["dd_id"]       = $ddId;
            $prepDataIns["user_id"]     = $userId;
            $prepDataIns["member_id"]   = $request->member_id;
            $prepDataIns["created_at"]  = $this->timeStamp();
            $where = [
                ["provider_id","=",$providerId],
                ["dd_id","=",$ddId],
                ["member_id","=",$request->member_id],
            ];
            $hasRec = $this->fetchData("group_provider_info",$where,1,[]);
            if(!is_object($hasRec)) {

                if ($request->member_id != 0) {

                    $email = $request->email;
    
                    $emailExist = User::where("email", "=", $email)->count();
                    // echo "my count:".$emailExist;
                    // exit;
                    if ($emailExist) {
                        return $this->warningResponse([], 'Email already exist with given params', 422);
                    }
                    $password = Str::random(6);
    
                    $fullName = $request->first_name . " " . $request->last_name;
    
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

                    $member = ProviderMember::where("provider_id", "=", $providerId)

                    ->where("member_id", "=", $request->member_id)
    
                    ->count();

                    if ($member == 0) {
                        //insert the provider member data
                        ProviderMember::insertGetId([
                            "provider_id" => $providerId,
                            "member_id"   => $request->member_id,
                            "dd_id"       => $ddId,
                            "email"       => $email,
                            "user_id"     => $userId,
                            "token"       => $token,
                            "created_at" => $this->timeStamp()
                        ]);
                    } else {
                        ProviderMember::where("provider_id", "=", $providerId)
                            ->where("member_id", "=", $request->member_id)
                            ->update([
                                "provider_id" => $providerId,
                                "member_id"   => $request->member_id,
                                "dd_id"       => $ddId,
                                "email"       => $email,
                                "user_id"     => $userId,
                                "token"       => $token,
                                "created_at" => $this->timeStamp()
                            ]);
                    }
                    UserProfile::create($addUserProfile);
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
                }
                $this->addData("group_provider_info", $prepDataIns, 1);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "add","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
            }
            else {
                // $email = $request->email;
    
                // $emailExist = User::where("email", "=", $email)->count();
                // // echo "my count:".$emailExist;
                // // exit;
                // if ($emailExist) {
                //     return $this->warningResponse([], 'Email already exist with given params', 422);
                // }
                $this->updateData("group_provider_info",$where, $prepDataIns);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "update","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }

        } elseif ($whichSection == "practice_location_info") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                $prepDataIns[$key]   = $inputAll[$key];
                // $prepDataIns["field_value"] = $inputAll[$key];
                $prepDataIns["provider_id"] = $providerId;
                $prepDataIns["dd_id"]       = $ddId;
                $prepDataIns["created_at"]  = $this->timeStamp();
                // array_push($insData, $prepDataIns);
            }

            $where = [
                ["provider_id","=",$providerId],
                ["dd_id","=",$ddId],
                ["num_of_physical_location","=",$request->num_of_physical_location]
            ];
            $hasRec = $this->fetchData("practice_location_info",$where,1,[]);
            if(!is_object($hasRec)) {
                $this->addData("practice_location_info", $prepDataIns, 1);
                $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
            }
            else {
                $this->updateData("practice_location_info",$where, $prepDataIns);
                $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }

            // $this->addData("practice_location_info", $prepDataIns, 1);
        } elseif ($whichSection == "ownership_info") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                $prepDataIns[$key]   = $inputAll[$key];
                // $prepDataIns["field_value"] = $inputAll[$key];
                $prepDataIns["provider_id"] = $providerId;
                $prepDataIns["dd_id"]       = $ddId;
                $prepDataIns["created_at"]  = $this->timeStamp();
                // array_push($insData, $prepDataIns);
            }
            $where = [
                ["provider_id","=",$providerId],
                ["dd_id","=",$ddId],
                ["type_of_ownership","=", $request->type_of_ownership]
            ];
            $hasRec = $this->fetchData("ownership_info",$where,1,[]);
            if(!is_object($hasRec)) {
                $this->addData("ownership_info", $prepDataIns, 1);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "add","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
            }
            else {
                $this->updateData("ownership_info",$where, $prepDataIns);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "update","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }
            // $this->addData("ownership_info", $prepDataIns, 1);
        } elseif ($whichSection == "payer_info") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                $pos = strpos($key, "file");
                if ($pos !== false) {
                    $file = $request->file($key);
                    if ($file != null && $file != "undefined") {
                        $path = public_path('provider/attachments/' . $providerId);

                        $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

                        if (!file_exists($path)) {
                            mkdir($path, 0777, true);
                        }
                        $file->move($path, $fileName);
                        $prepDataIns[$key]   = $fileName;
                        // $prepDataIns["field_value"] = $fileName;
                    }
                } else
                    $prepDataIns[$key]   = $inputAll[$key];

                // $prepDataIns["field_value"] = $inputAll[$key];
                $prepDataIns["provider_id"] = $providerId;
                $prepDataIns["dd_id"]       = $ddId;
                $prepDataIns["created_at"]  = $this->timeStamp();
                // array_push($insData, $prepDataIns);
            }
            $where = [
                ["provider_id","=",$providerId],
                ["dd_id","=",$ddId],
                ["payer_id","=",$request->payer_id]
            ];
            $hasRec = $this->fetchData("payer_info",$where,1,[]);
            if(!is_object($hasRec)) {
                $this->addData("payer_info", $prepDataIns, 1);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "add","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken" => "add","created_at" => $this->timeStamp()]);
            }
            else {
                $this->updateData("payer_info",$where, $prepDataIns);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "update","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }
            // $this->addData("payer_info", $prepDataIns, 1);
        } elseif ($whichSection == "bank_info") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                // $prepDataIns["field_key"]   = $key;
                // $prepDataIns["field_value"] = $inputAll[$key];
                $prepDataIns[$key]   =  $inputAll[$key];
                $prepDataIns["provider_id"] = $providerId;
                $prepDataIns["dd_id"]       = $ddId;
                $prepDataIns["created_at"]  = $this->timeStamp();
                array_push($insData, $prepDataIns);
            }
            $where = [
                ["provider_id","=",$providerId],
                ["dd_id","=",$ddId]
            ];
            $hasRec = $this->fetchData("banking_info",$where,1,[]);
            if(!is_object($hasRec)) {
                $this->addData("banking_info", $prepDataIns, 1);
                $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
            }
            else {
                $this->updateData("banking_info",$where, $prepDataIns);
                $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
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
                        $path = public_path('provider/attachments/' . $providerId);

                        $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

                        if (!file_exists($path)) {
                            mkdir($path, 0777, true);
                        }
                        $file->move($path, $fileName);
                        $prepDataIns[$key]   = $fileName;
                        // $prepDataIns["field_value"] = $fileName;
                    }
                    else {
                        $prepDataIns[$key]   = $inputAll[$key];
                    }
                } else {
                    $prepDataIns[$key]   = $inputAll[$key];
                }
                $prepDataIns["provider_id"] = $providerId;
                $prepDataIns["dd_id"]       = $ddId;
                $prepDataIns["created_at"]  = $this->timeStamp();
                // array_push($insData, $prepDataIns);
            }
            if (count($prepDataIns)) {

                $where = [
                    ["provider_id","=",$providerId],
                    ["dd_id","=",$ddId]
                ];
                $hasRec = $this->fetchData("attachments",$where,1,[]);
                if(!is_object($hasRec)) {
                    $this->addData("attachments", $prepDataIns, 1);
                    $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "add","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                    // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
                }
                else {
                    $this->updateData("attachments",$where, $prepDataIns);
                    $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "update","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                    // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                }
                //$this->addData("attachments", $prepDataIns, 1);
            }

        } else if ($whichSection == "wishlist") {
            $prepDataIns  = [];
            $insData = [];
            foreach ($keys as $key) {
                // $prepDataIns["field_key"]   = $key;
                // $prepDataIns["field_value"] = $inputAll[$key];
                $prepDataIns[$key]   =  $inputAll[$key];
                $prepDataIns["provider_id"] = $providerId;
                $prepDataIns["dd_id"]       = $ddId;
                $prepDataIns["created_at"]  = $this->timeStamp();
                array_push($insData, $prepDataIns);
            }
            $where = [
                ["provider_id","=",$providerId],
                ["dd_id","=",$ddId],
                ["wishlist_id","=",$request->wishlist_id]
            ];
            $hasRec = $this->fetchData("wishlists",$where,1,[]);
            if(!is_object($hasRec)) {
                $this->addData("wishlists", $prepDataIns, 1);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "add","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"add","created_at" => $this->timeStamp()]);
            }
            else {
                $this->updateData("wishlists",$where, $prepDataIns);
                $this->addProviderLogs("providers_logs",["user_id" => $logUserId,"provider_id" => $providerId,"action_taken" => "update","section" => $whichSection,"log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
                // $this->addProviderLogs("providers_logs",["user_id" => $providerId,"action_taken"=>"update","log_data" => json_encode($prepDataIns),"created_at" => $this->timeStamp()]);
            }
            // $this->addData("wishlists", $prepDataIns, 1);
        }
        return $this->successResponse(["is_added" => true, "section" => $whichSection], "success");
    }
    /**
     * view client given discovery document
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function viewClientDsicoveryDocument(Request $request, $token) {

        $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);

        $providerId = $ddData->provider_id;
        
        $provider = Provider::where("id","=",$providerId)->first();

        $ddId = $ddData->id;

        $where = [
            ["provider_id","=",$providerId],
            ["dd_id","=",$ddId]
        ];

        $whishList              = $this->fetchData("wishlists",$where,0,[]);
        $attachments            = $this->fetchData("attachments",$where,1,[]);
        $bankInfo               = $this->fetchData("banking_info",$where,1,[]);
        $payerInfo              = $this->fetchData("payer_info",$where,0,[]);
        $ownershipInfo          = $this->fetchData("ownership_info",$where,0,[]);
        $practiceLocationInfo   = $this->fetchData("practice_location_info",$where,0,[]);
        $providerInfo           = $this->fetchData("group_provider_info",$where,0,[]);
        $buisnessInfo           = $this->fetchData("buisnessinfo",$where,1,[]);
        $addendum               = $this->fetchData("addendum",$where,0,[]);

        $response = [
            "whish_list"                => $whishList,
            "attachments"               => $attachments,
            "bank_info"                 => $bankInfo,
            "payer_info"                => $payerInfo,
            "ownership_info"            => $ownershipInfo,
            "practice_location_info"    => $practiceLocationInfo,
            "provider_info"             => $providerInfo,
            "buisness_info"             => $buisnessInfo,
            "addendum"                  => $addendum,
            "provider_id"               => $providerId,
            "provider"                  => $provider
        ];
        return $this->successResponse($response, "success");
    }
    /**
     * delete the discovery document section data
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteClientDsicoveryDocument(Request $request, $token,$sectionId) {
        
        $whichSection = $request->which_section;
        
        $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);

        $providerId = $ddData->provider_id;
        
        $ddId = $ddData->id;

        if($whichSection == "ownership_info") {
            $where = [
                ["id","=",$sectionId]
            ];
            $isDel = $this->deleteData("ownership_info",$where);
            if($isDel) {
                $where_ = [
                    ["provider_id","=",$providerId],
                    ["dd_id","=",$ddId]
                ];
                $oInfo              = $this->fetchData("ownership_info",$where_,0,[]);
                if(count($oInfo)) {
                    foreach($oInfo as $key => $info) {
                        $oId = $key + 1;
                        $where__ = [
                            ["provider_id","=",$providerId],
                            ["dd_id","=",$ddId],
                            ["id","=",$info->id]
                        ];
                        $this->updateData("ownership_info",$where__, ["type_of_ownership" => $oId]);
                    }
                }
            }
            return $this->successResponse(["id_delete" => $isDel], "success");
        }
        else if($whichSection == "payer_info") {
            $where = [
                ["id","=",$sectionId]
            ];
            $isDel = $this->deleteData("payer_info",$where);
            if($isDel) {
                $where_ = [
                    ["provider_id","=",$providerId],
                    ["dd_id","=",$ddId]
                ];
                $payerInfo              = $this->fetchData("payer_info",$where_,0,[]);
                if(count($payerInfo)) {
                    foreach($payerInfo as $key => $info) {
                        $payerId = $key + 1;
                        $where__ = [
                            ["provider_id","=",$providerId],
                            ["dd_id","=",$ddId],
                            ["id","=",$info->id]
                        ];
                        $this->updateData("payer_info",$where__, ["payer_id" => $payerId]);
                    }
                }
            }
            return $this->successResponse(["id_delete" => $isDel], "success");
        }
        else if($whichSection == "wishlists") {
            $where = [
                ["id","=",$sectionId]
            ];
            $isDel = $this->deleteData("wishlists",$where);
            if($isDel) {
                $where_ = [
                    ["provider_id","=",$providerId],
                    ["dd_id","=",$ddId]
                ];
                $wishlistInfo              = $this->fetchData("wishlists",$where_,0,[]);
                if(count($wishlistInfo)) {
                    foreach($wishlistInfo as $key => $info) {
                        $where__ = [
                            ["provider_id","=",$providerId],
                            ["dd_id","=",$ddId],
                            ["id","=",$info->id]
                        ];
                        $wishlistId = $key + 1;
                        $this->updateData("wishlists",$where__, ["wishlist_id" => $wishlistId]);
                    }
                }
            }
            return $this->successResponse(["id_delete" => $isDel], "success");
        }
    }
    public function copyInsurancesData() {
        $allPayers = $this->fetchData("payers","",0);
        // $this->printR($allPayers,true);
        foreach($allPayers as $payer) {
            $insData = [
                "payer_id" => "",
                "payer_name" => $payer->payer_name,
                "po_box" => "",
                "fax_number" => "",
                "credentialing_duration" => "",
                "insurance_type" => "",
                "short_name" => "",
                "phone_number" => "",
                "country_name" => "",
                "state" => "",
                "zip_code" => "",
                "dependant_insurance" => ""
            ];
            $this->addData("insurances",$insData,0);
        }
        echo "all done";
    }
    /**
     * add the discovery document section data
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addAddendumData($token,Request $request) {
        
        $ddData = DiscoveryDocument::where("dd_token", "=", $token)->first(["provider_id", "id"]);

        $providerId = $ddData->provider_id;
        
        $ddId = $ddData->id;

        $memberId = $request->has("member_id") ? $request->member_id : 0;

        $where = [
            ["provider_id","=",$providerId],
            ["dd_id","=",$ddId],
            ["member_id" , "=", $memberId]
        ];

        $dataExist   = $this->fetchData("addendum",$where,0,[]);
        $inputAll = $request->except(["which_section"]);
        $keys = array_keys($inputAll);
        $prepDataIns = [];
        foreach ($keys as $key) {
            $prepDataIns[$key]   = $inputAll[$key];
        }
        $prepDataIns["provider_id"] = $providerId;
        $prepDataIns["dd_id"]       = $ddId;
      
        if(count($dataExist)) {
            $prepDataIns["updated_at"]  = $this->timeStamp();
            $this->updateData("addendum",$where, $prepDataIns);
            return $this->successResponse(["is_updated" => true], "success");
        }
        else {
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
    public function fetchStatesCitiesData(Request $request) {
        
        $statesCitiesData = StatesCities::select("state")->distinct()->get();

        $stateAndCities = [];
        
        foreach($statesCitiesData as $state) {
            $stateAndCities[$state->state] =  StatesCities::where("state","=",$state->state)->select("city")->distinct()->get();
        }

        // $this->printR($statesCitiesData,true);
        return $this->successResponse(["stats" => $statesCitiesData,"cities" => $stateAndCities,"count" => count($statesCitiesData)], "success");

    }
    /**
     * check for discovery token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function discoveryToken(Request $request) {
        
        $providerId = $request->provider_id;

        $ddData = DiscoveryDocument::where("provider_id","=",$providerId)->first(["dd_token"]);
        
        if(!is_object($ddData)) {
            $request->merge([
                "provider_id" => $providerId,
                "company_id" => "null"
            ]);
            
            $discoverControllerObj = new DiscoverydocumentController();

            $discoverControllerObj->store($request); //generate the discovery related tokens

            $ddData = DiscoveryDocument::where("provider_id","=",$providerId)->first(["dd_token"]);

            return $this->successResponse(["dd_data" => $ddData], "success");
        }
        else {
            return $this->successResponse(["dd_data" => $ddData], "success");
        }
    }
    /**
     * check for discovery token
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteMemberProvider(Request $request) {
        
        $where = [
            ["member_id" , "=",$request->member_id],
            ["provider_id","=",$request->provider_id],
        ];

        $user = $this->fetchData("provider_members",$where,1,["user_id"]);
        
        if(is_object($user)) {
            $userId = $user->user_id;

            try {
                
                $this->deleteData("provider_members",$where);

                $members = $this->fetchData("provider_members",$where,0,[]);
                
                if(count($members)) {
                    foreach($members as $key => $member) {
                        $memberId = $key + 1;
                        $this->updateData("provider_members",["id" => $member->id],["member_id" => $memberId]);
                    }
                }
                
            }
            catch(\Throwable $exception) {

            }

            try {
                $this->deleteData("users",["id" => $userId]);
            }
            catch(\Throwable $exception) {

            }

            try {
                $this->deleteData("users_profile",["user_id" => $userId]);
            }
            catch(\Throwable $exception) {

            }

            try {
                $this->deleteData("personal_access_tokens",["tokenable_id " => $userId]);
            }
            catch(\Throwable $exception) {

            }

            try {
                $this->deleteData("group_provider_info",["user_id " => $userId]);

                $soloProviders = $this->fetchData("group_provider_info",$where,0,[]);
                
                if(count($soloProviders)) {
                    foreach($soloProviders as $key => $provider) {
                        $memberId = $key + 1;
                        $this->updateData("group_provider_info",["id" => $provider->id],["member_id" => $memberId]);
                    }
                }
            }
            catch(\Throwable $exception) {

            }
           

            $where_ = [
                ["id","=",$request->provider_id]
            ];

            $provider = $this->fetchData("providers",$where_,1,["num_of_provider"]);

            $newCountProviderCnt = (int)$provider->num_of_provider - 1;

            $this->updateData("providers",$where_,["num_of_provider" => $newCountProviderCnt]);

        }
        else {

            $where_ = [
                ["id","=",$request->provider_id]
            ];

            $provider = $this->fetchData("providers",$where_,1,["num_of_provider"]);

            $newCountProviderCnt = (int)$provider->num_of_provider - 1;

            $this->updateData("providers",$where_,["num_of_provider" => $newCountProviderCnt]);

            try {
                $this->deleteData("group_provider_info",$where);
                $soloProviders = $this->fetchData("group_provider_info",$where,0,[]);
                
                if(count($soloProviders)) {
                    foreach($soloProviders as $key => $provider) {
                        $memberId = $key + 1;
                        $this->updateData("group_provider_info",["id" => $provider->id],["member_id" => $memberId]);
                    }
                }
            }
            catch(\Throwable $exception) {

            }

        }
        return $this->successResponse(["is_deleted" => true], "success");
    }
    /**
     * migrate provider data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addProviderData(Request $request) {

        $allData = $request->csv_data;
        $allDataArr = json_decode($allData,true);
        $prepData = [];
        // $this->printR($allDataArr,true);
        foreach($allDataArr as $key=>$data) {
            // $allDataArr = json_decode($data,true);
            
            if($data["forcredentialing"] == 1 && $data["forbilling"] == 1) {
                unset($data["forcredentialing"]);
                unset($data["forbilling"]);
                $data["seeking_service"] = "Credentialing and billing";
                $data["provider_type"] = "group";
            }
            else if($data["forcredentialing"] == 1 && $data["forbilling"] == 0) {
                unset($data["forcredentialing"]);
                unset($data["forbilling"]);
                $data["seeking_service"] = "Credentialing only";
                $data["provider_type"] = "group";
            }
            else if($data["forcredentialing"] == 0 && $data["forbilling"] == 1) {
                unset($data["forcredentialing"]);
                unset($data["forbilling"]);
                $data["seeking_service"] = "Billing only";
                $data["provider_type"] = "group";
            }
            else {
                unset($data["forcredentialing"]);
                unset($data["forbilling"]);
                $data["seeking_service"] = "NULL";
                $data["provider_type"] = "group";
            }
            array_push($prepData, $data);
            // $this->printR($data,true);
            // $innerData = [];
            // foreach($data as $key2=>$nested) {
            //     $this->printR($key2,true);
            //     if($key2 == "forcredentialing" && )
            // }
        }
        $this->addData("providers",$prepData,1);
        // $this->printR($prepData,true);
        // echo json_encode($allData);
    }
    /**
     * fetch user dd token and provider id
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchUserDiscoveryToken(Request $request) {
        
        $userProfile = $this->fetchData("users_profile",["id" => $request->user_id],1,["user_id"]);
        
        $userId      = $userProfile->user_id;

        $memberData = $this->fetchData("provider_members",["user_id" => $userId],1,["token","provider_id"]);

        return $this->successResponse(["data" => $memberData], "success");
    }
}
