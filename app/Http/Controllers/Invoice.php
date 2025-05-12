<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Traits\Utility;
use App\Http\Traits\ApiResponseHandler;
use App\Models\Provider;
use App\Models\Invoice as InvoiceModal;
use App\Models\ProviderCompanyMap;
use Mail;
use App\Mail\InvoiceReminder;

class Invoice extends Controller
{
    use Utility, ApiResponseHandler;
    /**
     * create the invoice against the specific provider
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createInvoice(Request $request)
    {
        $request->validate([
            "provider_id" => "required"
        ]);
        try {

            $providerId = $request->provider_id;
            //$provider = Provider::find($providerId);
            //if (is_object($provider)) 
            {

                $providerMap = ProviderCompanyMap::where("provider_id", "=", $providerId)->first(["company_id"]);

                $companyId = $providerMap->company_id;

                $invoiceCnt = InvoiceModal::count();

                //$this->printR($provider, true);
                // $invoiceCost = 0;
                // if ($provider->provider_type == "group")
                //     $numOfProviders = $provider->num_of_provider + 1;
                // else
                //     $numOfProviders = 1;

                //echo $numOfProviders;
                // $invoiceCost = $this->perPersonCharges * $numOfProviders;

                $token = Str::random(32);

                $token = "cm_" . strtolower($token);

                $invoiceId = $invoiceCnt;

                $invoiceNumber = $this->generateInvoiceNumber($invoiceId);

                $hasInvoice = InvoiceModal::where("provider_id","=",$providerId)->count();
                
                if ($hasInvoice == 0) {

                    $inviceData = [
                        "company_id"        => $companyId,
                        "provider_id"       => $providerId,
                        "invoice_number"    => $invoiceNumber,
                        "invoice_token"     => $token,
                        "amount"            => $request->amount,
                        "payment_status"    => "pending",
                        "details"           => $request->details,
                        "issue_date"        => $request->issue_date,
                        "due_date"          => $request->due_date,
                        "created_at"        => $this->timeStamp()
                    ];

                    $id = InvoiceModal::insertGetId($inviceData);

                    return $this->successResponse(["id" => $id, "is_created" => true], "success");
                } else {
                    
                    $inviceData = [
                        "amount"            => $request->amount,
                        "details"           => $request->details,
                        "issue_date"        => $request->issue_date,
                        "due_date"          => $request->due_date,
                        "updated_at"        => $this->timeStamp()
                    ];

                    $isUpdate = InvoiceModal::where("provider_id","=",$providerId)->update(
                        $inviceData
                    );
                    return $this->successResponse(["is_updatd" => $isUpdate, "is_created" => false], "success");
                }
            }
            //else {
            //     return $this->warningResponse($provider, 'Provider not found', 404);
            // }
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the invoice against specific provider
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchInvoice(Request $request)
    {
        $request->validate([
            "provider_id" => "required"
        ]);
        try {
            $providerId = $request->provider_id;

            $invoice = InvoiceModal::where("provider_id", "=", $providerId)
            
            ->where("is_recuring","=",0)
            
            ->orderBy("id","DESC")
            
            ->first();

            return $this->successResponse(["invoice" => $invoice], "success");

        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * prepare the invoice data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function invoiceBasicData(Request $request)
    {
        $request->validate([
            "provider_id" => "required"
        ]);

        try {
            $providerId = $request->provider_id;
            $provider = Provider::find($providerId);
            if (is_object($provider)) {

                $providerMap = ProviderCompanyMap::where("provider_id", "=", $providerId)->first(["company_id"]);

                $companyId = $providerMap->company_id;

                //$lastInvoiceId = InvoiceModal::latest()->first();
                $invoiceCnt = InvoiceModal::count();
                //$this->printR($provider, true);
                $invoiceCost = 0;
                if ($provider->provider_type == "group")
                    $numOfProviders = $provider->num_of_provider + 1;
                else
                    $numOfProviders = 1;

                //echo $numOfProviders;
                $invoiceCost = $this->perPersonCharges * $numOfProviders;

                $token = Str::random(32);

                $token = "cm_" . strtolower($token);

                $invoiceId = $invoiceCnt;
                $invoice = InvoiceModal::where("provider_id","=",$providerId)->first(["invoice_number"]);
                if(!is_object($invoice))
                    $invoiceNumber = $this->generateInvoiceNumber($invoiceId);
                else
                    $invoiceNumber = $invoice->invoice_number;

                $invoiceData = [

                    "invoice_number"    => $invoiceNumber,
                    "amount"            => $invoiceCost,
                    "issue_date"        => $this->issueDate(),
                    "due_date"          => $this->dueDate(),
                    "qty"               => $numOfProviders
                ];

                return $this->successResponse(["invoice_data" => $invoiceData], "success");
            } else {
                return $this->warningResponse($provider, 'Provider not found', 404);
            }
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the provider payment history
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function fetchInvoicesHistory(Request $request) {
        
        $request->validate([
            "provider_id" => "required"
        ]);
        
        $providerId = $request->provider_id;

        try {
            
            $invoices = InvoiceModal::where("provider_id","=",$providerId)

            ->orderBy("id","DESC")
        
            ->get();

            return $this->successResponse(["invoice_history" => $invoices], "success");
        }
        catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * send the invoice reminder email
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateMonthlyInvoice(Request $request) {
        
        $firstDate = date('d',time());
        
        $year = date("Y",time());
        
        $month = date("m",time());
        
        // $prevMonthth = date('m', strtotime("last month"));
        
        //if($firstDate == "01") 
        {
           
        //     $allProviders = InvoiceModal::whereMonth($prevMonthth)        
            
        //    ->year($year)
            
        //    ->get(["company_id","provider_id","invoice_number","amount","details"]);
            $allProviders = Provider::where("contact_person_email","!=","null")
            
            ->where("contact_person_email","LIKE","%_@__%.__%")

            ->get(["id","contact_person_email","contact_person_name"]);
           
            $issueDate = date("Y-m-d");
            //echo "-----";
            $dueDate = date('Y-m-d', strtotime($issueDate. ' + 3 days'));
            //exit;
            if(count($allProviders) > 0) {

                foreach($allProviders as $provider) {
                    
                    $lastInvoice = InvoiceModal::where("invoices.payment_status","!=","paid")
                
                    ->where("provider_id","=", $provider->id)
                    
                    ->where("is_recuring","=",1)
                    
                    ->orderBy("invoices.id","DESC")
                    
                    ->first();
    
                    $providerId = $provider->id;
                    if(is_object($lastInvoice)) {
                        // $providerMapData = ProviderCompanyMap::where("provider_id", $providerId)->first(["company_id"]);
                        
                        // $companyId = $providerMapData->company_id;

                        $invoiceCnt = InvoiceModal::count();
                        
                        $token = Str::random(32);

                        $token = "cm_" . strtolower($token);

                        $invoiceId = $invoiceCnt;

                        $invoiceNumber = $this->generateInvoiceNumber($invoiceId);

                        // $hasInvoice = InvoiceModal::where("provider_id","=",$providerId)
                        
                        // ->whereMonth("created_at",$month)
                        
                        // ->whereYear("created_at",$year)

                        // ->count();
                        $hasInvoice = 0;

                        if ($hasInvoice == 0) {

                            $inviceData = [
                                "company_id"        => $lastInvoice->company_id,
                                "provider_id"       => $providerId,
                                "invoice_number"    => $invoiceNumber,
                                "invoice_token"     => $token,
                                "amount"            => $lastInvoice->amount,
                                "payment_status"    => "pending",
                                "details"           => $lastInvoice->details,
                                "issue_date"        => $issueDate,
                                "due_date"          => $dueDate,
                                "is_recuring"       => "1",
                                "created_at"        => $this->timeStamp()
                            ];

                            InvoiceModal::insertGetId($inviceData);
                        }
                    }
                }
            }
           
        }
    }
    /**
     * send the invoice reminder email
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendInvoiceReminderEmail(Request $request) {
       
        $allProviders = Provider::where("contact_person_email","!=","null")
        
        ->where("contact_person_email","LIKE","%_@__%.__%")

        ->get(["id","contact_person_email","contact_person_name"]);
        // return $this->successResponse(["allProviders" => $allProviders], "success");
        
        $baseURL = $this->baseURL();

        if(count($allProviders)) {
            foreach($allProviders as $provider) {
                
                $lastInvoice = InvoiceModal::where("invoices.payment_status","!=","paid")
                
                ->where("provider_id","=", $provider->id)
                
                ->where("is_recuring","=",1)
                
                ->orderBy("invoices.id","DESC")
                
                ->first();

                if(is_object($lastInvoice)) {
                    $month = date("F",strtotime($lastInvoice->due_date));
                    $year = date("Y",strtotime($lastInvoice->due_date));
                    // echo date("m/d/Y");
                    // exit;
                    $msg = "";
                    try {
                        $invoiceURL = $baseURL . "/view/invoice/" . $lastInvoice->invoice_token . "/" . $lastInvoice->invoice_number;
                        $myData = [
                            "invoice_url" => $invoiceURL,
                            "name"  => $provider->contact_person_name,
                            "month" => $month,
                            "year" => $year,
                            "sending_date" => date("m/d/Y"),
                            "invoice_number" => $lastInvoice->invoice_number
                        ];
                        
                        $monthNum = date("m");
                        $year = date("Y");
                        $monthName = date("F", mktime(0, 0, 0, $monthNum, 10));

                        // $providerId = $provider->provider_id;
                        // $provider = Provider::where("id","=",$providerId)->first();
                        $providerId = $provider->id;
                        $action = "invoice_reminder";
                        $heading = $provider->contact_person_name;
                        $msg = "Invoice Reminder For month of " .$monthName. " ".$year;

                        $this->addNotification($providerId,$action,NULL,$msg,$heading);
                        // $this->printR($myData,true);
                        Mail::to($provider->contact_person_email)
    
                        ->send(new InvoiceReminder($myData));
                        
                            $isSentEmail = 0;
                    }
                    catch (\Throwable $exception) {
    
                        $isSentEmail = 0;
        
                        $msg = $exception->getMessage();
                    }
                    echo "email sent:".$isSentEmail;
                    echo "<br>";
                    echo "msg:".$msg;
                    echo "email:".$provider->contact_person_email;
                    echo "<br/>";
                }

               
            }
        }

        // return $this->successResponse(["allProviders" => $allProviders], "success");
        // exit;
        // $this->printR($allProviders,true);

        //$prevMonthth = date('m', strtotime("last month"));
        
        // $year = date("Y");

        // $allProviders = InvoiceModal::where("invoices.payment_status","!=","paid")
        
        // ->leftJoin("providers","providers.id","invoices.provider_id")
        
        // //->whereMonth("invoices.due_date",$prevMonthth)
                    
        // ->year($year)

        // ->get(["invoices.provider_id","contact_person_email"]);

        // if(count($allProviders) > 0) {
            
        //     foreach($allProviders as $provider) {
        //         try {

        //             Mail::to($provider->contact_person_email)

        //             ->send(new InvoiceReminder([]));
                    
        //              $isSentEmail = 0;
        //         }
        //         catch (\Throwable $exception) {

        //             $isSentEmail = 0;
    
        //             $msg = $exception->getMessage();
        //         }
        //     }
        // }
    }
    public function generateRecuringNotification(Request $request) {
        $invoice = InvoiceModal::first();
        // $future_timestamp = strtotime("+1 month");
        // echo $data = date('Y-m-d', $future_timestamp);
        // exit;
        //$this->printR($invoice,true);
        
        $monthNum = date("m");
        $year = date("Y");
        $monthName = date("F", mktime(0, 0, 0, $monthNum, 10));

        $providerId = $invoice->provider_id;
        $provider = Provider::where("id","=",$providerId)->first();
        $action = "recuring_invoice";
        $heading = $provider->contact_person_name;
        $msg = "Invoice Reminder For month of " .$monthName. " ".$year;

        $this->addNotification($providerId,$action,NULL,$msg,$heading);
    }
    
}
