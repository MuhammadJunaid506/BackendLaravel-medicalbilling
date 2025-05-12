<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Provider;
use App\Models\Insurance;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\DiscoveryDocument;
use App\Models\ProviderCompanyMap;
use Illuminate\Support\Str;

class DiscoverydocumentController extends Controller
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
        $request->validate([
            "company_id"    => "required",
            "provider_id"   => "required",
        ]);
        try {
            $companyId          = $request->company_id;
            $providerId         = $request->provider_id;
            if ($companyId == "undefined" || $companyId == "null") {
                $provider = ProviderCompanyMap::where("provider_id", "=", $providerId)->first("company_id");
                $companyId = $provider->company_id;
            }

            $token = Str::random(32);

            $token = "cm_" . strtolower($token);

            $discoveryDocumentExist = DiscoveryDocument::where("provider_id", "=", $providerId)

                ->count();

            if ($discoveryDocumentExist == 0) {
                $addDiscoveryDocumentData = [
                    "provider_id"               => $providerId,
                    "company_id"                => $companyId,
                    "dd_token"                  => $token,
                    "dd_data"                   => "NULL",
                    "created_at"                => $this->timeStamp()
                ];
                //$this->printR($addContractData,true);
                $addNewDD = DiscoveryDocument::create($addDiscoveryDocumentData);
                return $this->successResponse(["is_created" => true], "Discovery document created successfully.");
            } else {
                return $this->successResponse(["is_created" => false], "Discovery document created already.");
            }
        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {

        if ($request->has("discovery_view") && $request->discovery_view == true) {
            
            $clientDiscoveryDocument = DiscoveryDocument::where("provider_id", "=", $id)
            
            ->first(["dd_data"]);
            $clientDiscoveryDocument->dd_data = json_decode( $clientDiscoveryDocument->dd_data , true);
            return $this->successResponse(["client_discoverydocument" => $clientDiscoveryDocument], "success");

        } else {
            try {
                $provider = Provider::select($this->providerCols)

                    ->join("providers_companies_map", "providers_companies_map.provider_id", "=", "providers.id")

                    ->leftJoin("companies", "companies.id", "=", "providers_companies_map.company_id")

                    ->where("providers.id", "=", $id)

                    ->first();

                $insurances = Insurance::all();

                $result = [
                    "provider"      => $provider,
                    "insurances"    => $insurances
                ];

                return $this->successResponse($result, "success");
            } catch (\Throwable $exception) {

                return $this->errorResponse([], $exception->getMessage(), 500);
            }
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
        try {
            $ddDataAll = $request->all();

            $updataData = [];

            $updateData["dd_data"] = json_encode($ddDataAll);

            $updateData["updated_at"] = $this->timeStamp();

            $isUpdate = DiscoveryDocument::where("dd_token", "=", $id)->update($updateData);

            return $this->successResponse(["is_update" => $isUpdate], "success");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
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
