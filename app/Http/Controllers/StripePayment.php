<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Provider;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Controllers\Api\UserController;
use App\Models\ProviderCompanyMap;
use App\Models\Notifications;
use Illuminate\Support\Str;

class StripePayment extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * make the payement with sript
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function makePayment(Request $request,UserController $UserController)
    {

        $request->validate([
            "invoice_token"       => "required",
            "payment_intentid"  => "required",
        ]);
        $invoiceToken = $request->invoice_token;
        $paymentIntent = $request->payment_intentid;
        $invoice = Invoice::where("invoice_token", "=", $invoiceToken)
            ->first();
        $providerId = $invoice->provider_id;
        try {
          

            $amount =  $invoice->amount;
            $amount = $amount * 100;

            $providerMapData = ProviderCompanyMap::where("provider_id", $invoice->provider_id)->first(["company_id"]);
            $companyId = $providerMapData->company_id;
            $request->merge([
                'provider_id' => $providerId,
                'company_id' => $companyId
            ]);
           
            $provider = Provider::where("id", $invoice->provider_id)->first();
           
            $stripe = new \Stripe\StripeClient('sk_test_51LSaIPEb7vwyt1O63w1p2CUSbn26cXVQ2F2aKOTUUzbM1xLkioFX36ubLC0DcmHRlhdKst4DHKeB9Inkh2mkL3RV009ounPV3l');
            $customer = $stripe->customers->create([
                'name'              => $provider->contact_person_name,
                'description'       => $provider->seeking_service,
                'email'             => $provider->contact_person_email,
                'payment_method'    => $paymentIntent,
                'phone'             => $provider->contact_person_phone
            ]);
            // $this->printR($providerMapData,true);
            $payment = "";
            if (isset($customer->id)) {
                $payment = $stripe->paymentIntents->create([
                    'amount' => $amount,
                    'currency' => 'USD',
                    "description" => $provider->seeking_service,
                    'payment_method' => $paymentIntent,
                    'confirm' => true,
                    "customer" => $customer->id
                ]);
            } else {
                $payment = $stripe->paymentIntents->create([
                    'amount' => $amount,
                    'currency' => 'USD',
                    "description" => $provider->seeking_service,
                    'payment_method' => $paymentIntent,
                    'confirm' => true
                ]);
            }
            if(isset($payment->status) && $payment->status == "succeeded") {
                $request->merge([
                    'provider_id' => $providerId,
                    'company_id' => $companyId
                ]);
                $UserController->createProviderCredentials($request);//on-board proccess starts from here.
                //update the payment status in of the invoice
                Invoice::where("invoice_token", "=", $invoiceToken)->update(["payment_status" => "paid","updated_at" =>  $this->timeStamp()]);
                
                Provider::where("id","=",$providerId)->update(["is_active" => 1]);//onborad status of user
                
                /**
                 * next month invoice generation
                 */
                $nextMonth = strtotime("+1 month");
                
                $invoiceCnt = Invoice::count();
                
                $token = Str::random(32);

                $token = "cm_" . strtolower($token);
                
                $invoiceId = $invoiceCnt;

                $invoiceNumber = $this->generateInvoiceNumber($invoiceId);

                $issueDate = date('Y-m-d', $nextMonth);
                
                $nextMonthDueDate = date('Y-m-d', strtotime("+1 month", strtotime("+3 day")));

                $inviceData = [
                    "company_id"        => $invoice->company_id ,
                    "provider_id"       => $providerId,
                    "invoice_number"    => $invoiceNumber,
                    "invoice_token"     => $token,
                    "amount"            => $invoice->amount,
                    "payment_status"    => "pending",
                    "details"           => $invoice->details,
                    "issue_date"        => $issueDate,
                    "due_date"          => $nextMonthDueDate,
                    "is_recuring"       => "1",
                    "created_at"        => $this->timeStamp()
                ];

                Invoice::insertGetId($inviceData);
                
                return $this->successResponse(["payment_success" => true,"message" => "Payement charged successfully."], "success");
            }
            else {
                $dataLogs = [];
                $dataLogs["provider_id"] = $providerId;
                $dataLogs["logs_data"] = json_encode($payment);
                $dataLogs["created_At"] = $this->timeStamp();
                $this->addPaymentLogs($dataLogs);
                return $this->successResponse(["payment_success" => false,"faild_message" => "Payment could not be successfull"], "success");
            }
            $this->printR($payment,true);
        }
        catch(\Exception $e) {
            // echo $e->getMessage();
                $dataLogs["provider_id"] = $providerId;
                $dataLogs["logs_data"] = json_encode(["message" => $e->getMessage()]);
                $dataLogs["created_At"] = $this->timeStamp();
                $this->addPaymentLogs($dataLogs);
            return $this->successResponse(["payment_success" => false,"faild_message" => $e->getMessage()], "success");
        }
    }
}
