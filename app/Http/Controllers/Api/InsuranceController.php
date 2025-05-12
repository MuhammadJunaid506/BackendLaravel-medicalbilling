<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Models\Insurance;

class InsuranceController extends Controller
{
    use ApiResponseHandler;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $insurances = Insurance::paginate(20);
            
            return $this->successResponse($insurances,"Success");
        }
        catch (\Throwable $exception) {
            //throw $th;
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
            "payer_id"                  => "required | unique:insurances",
            "po_box"                    => "required",
            "fax_number"                => "required",
            "credentialing_duration"    => "required",
            "insurance_type"            => "required",
            "short_name"                => "required",
            "phone_number"              => "required",
            "country_name"              => "required",
            "state"                     => "required",
            "zip_code"                  => "required",
            "payer_name"                => "required"
        ]);
        
        try {
            
            $depandentInsurance = $request->has('dependant_insurance') ? $request->dependant_insurance : "NULL";

            $addInsuranceData = [
                "payer_id"                  => $request->payer_id,
                "payer_name"                => $request->payer_name,
                "po_box"                    => $request->po_box,
                "fax_number"                => $request->fax_number,
                "credentialing_duration"    => $request->credentialing_duration,
                "insurance_type"            => $request->insurance_type,
                "short_name"                => $request->short_name,
                "phone_number"              => $request->phone_number,
                "country_name"              => $request->country_name,
                "state"                     => $request->state,
                "zip_code"                  => $request->zip_code,
                "dependant_insurance"       => $depandentInsurance,
                "created_at"                => date("Y-m-d H:i:s")
            ];
            
            $newInsurance = Insurance::create($addInsuranceData);

            return $this->successResponse(["id" => $newInsurance->id],"Insurance added successfully.");
        }
        catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
    /**
     * Display the searched specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function filterInsurance(Request $request) {
        try {

            $keyWord = $request->keyword;
            
            $col     = $request->col;
            
            $result = "";

            if($col == "payer_name") {
                $result = Insurance::where('payer_name', 'LIKE', '%'. $keyWord. '%')
            
                ->get();
            }
            elseif($col == "country") {

                $result = Insurance::where('country_name', 'LIKE', '%'. $keyWord. '%')
            
                ->get();
            }
            elseif($col == "state") {

                $result = Insurance::where('state', 'LIKE', '%'. $keyWord. '%')
            
                ->get();
            }
            elseif($col == "payer_id") {

                $result = Insurance::where('payer_id', 'LIKE', '%'. $keyWord. '%')
            
                ->get();
            }
            elseif($col == "all") {
                
                 //over all filters
                 $result = Insurance::where('payer_name', 'LIKE', '%'. $keyWord. '%')
                
                 ->orWhere('country_name', 'LIKE', '%'. $keyWord. '%')
                 
                 ->orWhere('state', 'LIKE', '%'. $keyWord. '%')
                 
                 ->orWhere('payer_id', 'LIKE', '%'. $keyWord. '%')
                 
                 ->orWhere('short_name', 'LIKE', '%'. $keyWord. '%')
 
                 ->orWhere('phone_number', 'LIKE', '%'. $keyWord. '%')
 
                 ->orWhere('credentialing_duration', 'LIKE', '%'. $keyWord. '%')
 
                 ->get();
            }
            else {

                $result = Insurance::where('short_name', 'LIKE', '%'. $keyWord. '%')
            
                ->get();
            }
            

            if( count($result) ) {
                return $this->successResponse($result,"Success");
            }
            else {
                return $this->warningResponse($result,'No data not found', 204);
            }
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
    public function show(Request $request,$id)
    {
        if(!$request->has('keyword')) {
            try {
                $insurance = Insurance::find($id);
                return $this->successResponse($insurance,"Success");
            }
            catch (\Throwable $exception) {
                //throw $th;
                return $this->errorResponse([],$exception->getMessage(),500);
            }
        }
        else {
            return $this->filterInsurance($request);
        }
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
        $request->validate([
            "col" => "required",
            "val" => "required"
        ]);

        try {
            $col = $request->col;
            $val = $request->val;
            
            $isUpdate = Insurance::find($id)->update([$col => $val,"updated_at" => date("Y-m-d H:i:s")]);

            return $this->successResponse(['id' => $id,'is_update' => $isUpdate],"Insurance updated successfully.");
        }
        catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([],$exception->getMessage(),500);
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
        try {
            
            $isDel = Insurance::find($id)->delete();

            return $this->successResponse(['id' => $id,'is_del' => $isDel],"Insurance deleted successfully.");
        }
        catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
}
