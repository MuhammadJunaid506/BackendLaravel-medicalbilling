<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payer;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Credentialing;
use App\Models\AccountReceivable;

class PayerController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            if($request->has('smart_searching')) {
                $searching = $request->smart_searching;
                $phoneNumber = $this->sanitizePhoneNumber($request->smart_searching);
               
                if(is_numeric($phoneNumber))
                    $searching = $phoneNumber;
                else
                    $searching = $searching;

                $payers = Payer::where('payer_name','LIKE','%'.$searching.'%')
                ->orWhere('shortname','LIKE','%'.$searching.'%')
                ->orWhere('expecteddays','LIKE','%'.$searching.'%')
                ->orWhere('phone','LIKE','%'.$searching.'%')
                ->orWhere('email','LIKE','%'.$searching.'%')
                ->orWhere('for_credentialing','LIKE','%'.$searching.'%')
                ->orWhere('timely_filling_limit','LIKE','%'.$searching.'%')
                ->orderBy('id','DESC')
                ->paginate($this->cmperPage);
                if(count($payers)) {
                    foreach($payers as $payer) {
                        
                        $enrollments = Credentialing::where('payer_id','=',$payer->id)
                        
                        ->count();

                        $claims = AccountReceivable::where('payer_id','=',$payer->id)
                        
                        ->count();

                        $payer->enrollments = $enrollments;

                        $payer->claims = $claims;
                       
                    }
                    
                }
            }
            else {
                
                $payers = Payer::orderBy('id','DESC')->paginate($this->cmperPage);
                if(count($payers)) {
                    foreach($payers as $payer) {
                        
                        $enrollments = Credentialing::where('payer_id','=',$payer->id)
                        
                        ->count();

                        $claims = AccountReceivable::where('payer_id','=',$payer->id)
                        
                        ->count();

                        $payer->enrollments = $enrollments;

                        $payer->claims = $claims;
                       
                    }
                    
                }
            }
          
            return $this->successResponse(["payers" => $payers ],"Success",200);
        }
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            "payer_name" => "required | unique:payers",
            "shortname"              =>   "required",
            "expecteddays"           =>   "required",
            "phone"                  =>   "required",
            "email"                  =>   "required",
            "for_credentialing"      =>   "required",
            "timely_filling_limit"   =>   "required"
        ]);

        try {
            
            $payerData = [

            "payer_name"             => $request->payer_name, 
            "shortname"              => $request->shortname,
            "expecteddays"           => $request->expecteddays,
            "phone"                  => $this->sanitizePhoneNumber($request->phone),
            "email"                  => $request->email,
            "for_credentialing"      => $request->for_credentialing,
            "timely_filling_limit"   => $request->timely_filling_limit,
            "color"                  => isset($request->color) ? $request->color : $this->generateHexaColor()
        ];

            $id = Payer::insertGetId($payerData);

            return $this->successResponse(["id" => $id ],"Payer added successfully.");
        }
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
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
    public function update(Request $request, $id)
    {
   
         try { 

            $updateData = $request->all();
            if (isset($updateData["phone"])) {
                $updateData["phone"] = $this->sanitizePhoneNumber($updateData["phone"]);
            }
            $isUpdate  = Payer::where("id", $id)->update($updateData);
  
            return $this->successResponse(["is_update" => $isUpdate ],"success",200);

        }
        
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request,$id)
    {
        try { 

            $isDelete  = Payer::where("id", $id)->delete();
  
            return $this->successResponse(["is_delete" => $isDelete ],"success",200);

        }
        
        catch (\Throwable $exception) {
            
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
}