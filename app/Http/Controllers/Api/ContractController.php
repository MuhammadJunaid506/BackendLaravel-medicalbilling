<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Contract;
use Illuminate\Support\Str;
use App\Models\Provider;
use App\Models\ProviderCompanyMap;

class ContractController extends Controller
{
    use ApiResponseHandler,Utility;
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
        $request->validate([
            "provider_id"               => "required",
            "company_id"                => "required",
            "contract_company_fields"   => "required"
        ]);

        try {
            $providerId         = $request->provider_id;
            
            $companyId          = $request->company_id;
            if($companyId == "undefined" || $companyId == "null") {
                $provider = ProviderCompanyMap::where("provider_id","=",$providerId)->first("company_id");
                $companyId = $provider->company_id;
            }
            $companyFields      = $request->contract_company_fields;
            
            $recipientFields    = $request->contract_recipient_fields;
            
            $token = Str::random(32);

            $token = "cm_".strtolower($token);
            
            $contractExist = Contract::where("provider_id","=",$providerId)
            
            ->count();

            if($contractExist) {
                $updateContractData = [
                    "contract_company_fields"   => $companyFields,
                    "updated_at"                => $this->timeStamp()
                ];
                
                $isUpdate = Contract::where([
                    ["provider_id","=",$providerId],
                    ["company_id","=",$companyId]
                ])
                ->update($updateContractData);

                return $this->successResponse(["is_updated" => $isUpdate],"Contract saved successfully.");
            }
            else {
                $addContractData = [
                    "provider_id"               => $providerId,
                    "company_id"                => $companyId,
                    "contract_token"            => $token,
                    "contract_company_fields"   => $companyFields,
                    "contract_recipient_fields" => "NULL",
                    "created_at"                => $this->timeStamp()
                ];
                //$this->printR($addContractData,true);
                $addNewContract = Contract::create($addContractData);
                return $this->successResponse(["is_created" => true],"Contract created successfully.");
            }
        }
        catch (\Throwable $exception) {
            //throw $th;
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
        try {
            
            $contract = Contract::where("provider_id","=",$id)
            
            ->first(["contract_company_fields","contract_recipient_fields"]);

            return $this->successResponse(["contract" => $contract],"success");
        }
        catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([],$exception->getMessage(),500);
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
