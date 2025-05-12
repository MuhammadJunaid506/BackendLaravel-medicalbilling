<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Portal;
use DB;
use GuzzleHttp\Client;

class ThirdpartyController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * check the npi number from the goverment serve
     */
    public function fetchNPIData(Request $request)
    {
        $request->validate([
            "npi_number" => "required",
            "type" => "required"
        ]);
        $key = env("AES_KEY");

        $key = env("AES_KEY");

        if ($request->type == 2) {
            try {
                $response = file_get_contents("https://npiregistry.cms.hhs.gov/api/?version=2.1&number=" . $request->npi_number . "&enumeration_type=" . $request->type);
                $resArr = array();
                $resArr = json_decode($response);
                return $this->successResponse($resArr, "success");
            } catch (\Throwable $exception) {
                //throw $th;
                return $this->errorResponse([], $exception->getMessage(), 500);
            }
        } else if ($request->type == 1) {
            $npi = $request->npi_number;
            $userExist = DB::table("users")
                ->select(
                "users.first_name",
                "users.last_name", 
                DB::raw("AES_DECRYPT(cm_users.email,'$key') as email"),
                "gender", DB::raw("AES_DECRYPT(cm_users.phone,'$key') as phone"),
                "users.cnic","users.facility_npi","users.primary_speciality","users.secondary_speciality",
                DB::raw("AES_DECRYPT(cm_users.dob,'$key') as dob"),
                "users.state_of_birth","users.country_of_birth",
                "users.citizenship_id","users.supervisor_physician","users.professional_group_id",
                "users.professional_type_id", DB::raw("AES_DECRYPT(cm_users.address_line_one,'$key') as address_line_one"),
                DB::raw("AES_DECRYPT(cm_users.address_line_two,'$key') as address_line_two"),DB::raw("AES_DECRYPT(cm_users.ssn,'$key') as ssn"),
                "users.city","users.state","users.zip_code", DB::raw("AES_DECRYPT(cm_users.work_phone,'$key') as work_phone"),
                DB::raw("AES_DECRYPT(cm_users.fax,'$key') as fax"),
                DB::raw("AES_DECRYPT(cm_users.visa_number,'$key') as visa_number"),
                "users.eligible_to_work","users.place_of_birth","users.status",
                "users.hospital_privileges","users.zip_five","users.zip_four",
                "users.country","users.state_code","users.county",
                "professional_groups.name as professional_group_name", 
                "professional_types.name as professional_type_name", 
                "citizenships.name as citizen_name"
                )
                ->leftJoin("professional_groups", "professional_groups.id", "=", "users.professional_group_id")
                ->leftJoin("professional_types", "professional_types.id", "=", "users.professional_type_id")
                ->leftJoin("citizenships", "citizenships.id", "=", "users.citizenship_id")
                ->where("users.facility_npi", "=", $npi)
                ->first();
            $licensesData = [];
            if (is_object($userExist)) {
                $portalData = Portal::select("portals.user_name", "portals.password", "portals.identifier as caqh_number", "portal_types.link")
                    ->leftJoin("portal_types", "portal_types.id", "=", "portals.type_id")
                    ->where("portals.user_id", "=", $userExist->id)
                    ->first();


                $licensesData[$userExist->id][1] = $this->fetchLicensesData($userExist->id, 1);
                $licensesData[$userExist->id][2] = $this->fetchLicensesData($userExist->id, 2);
                $licensesData[$userExist->id][4] = $this->fetchLicensesData($userExist->id, 4);
                return $this->successResponse(["from_np1" => 0, "user" => $userExist, "portal" => $portalData, "license" => $licensesData], "success");
            } else {
                try {
                    $response = file_get_contents("https://npiregistry.cms.hhs.gov/api/?version=2.1&number=" . $request->npi_number . "&enumeration_type=" . $request->type);
                    $resArr = array();
                    $resArr = json_decode($response);
                    return $this->successResponse($resArr, "success");
                } catch (\Throwable $exception) {
                    //throw $th;
                    return $this->errorResponse([], $exception->getMessage(), 500);
                }
            }
        }
    }


    /*
    verify address api

    */
    public function verifyAddress(Request $request)
    {
        $request->validate([

            "address1" => "required",
            "city" => "required",
            "state" => "required",
            "zip" => "required",


         ]);
        // $revision = 1;
        // $address1 = "3003 TEXAS PKWY UNIT B";
        // $city = "MISSOURI";
        // $state = "TX";
        // $zip5 = 77489;
        // $userId = '010CLAIMT5857';

        $revision = 1;
        $address1 = $request->get('address1');
        $city = $request->get('city');
        $state = $request->get('state');
        $zip = $request->get('zip');
        //$userId = $request->get('user_id');

        //dd($revision,$address1);
        try {
            $userId = '010CLAIMT5857';
            $xmlData = '<AddressValidateRequest USERID="' . $userId . '">
                <Revision>' . $revision . '</Revision>
                <Address>
                    <Address1>' . $address1 . '</Address1>
                    <Address2></Address2>
                    <City>' . $city . '</City>
                    <State>' . $state . '</State>
                    <Zip5>' . $zip . '</Zip5>
                    <Zip4></Zip4>
                </Address>
            </AddressValidateRequest>';

            $url = 'https://secure.shippingapis.com/ShippingAPI.dll?API=Verify&XML=' . urlencode($xmlData);

            $client = new Client();
            $response = $client->get($url);


            $apiResponse = $response->getBody()->getContents();

            // Set the content type to XML
            $headers = [
                'Content-Type' => 'application/xml',
            ];

            // Return the XML response with status code 200
            return response($apiResponse, 200, $headers);

        } catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }


    /**
     * validate group sepeciality
     *
     */
    public function validateGroupSepciality(Request $request)
    {

        $specility = $request->specility;

        $specility = explode(" ", $specility)[0];

        $specilityData = DB::table("facilities")

            ->where("type", "=", "group")

            ->where("facility", "LIKE", "%" . $specility . "%")

            ->first();

        $specilityName = "";
        if (is_object($specilityData)) {
            $specilityName = $specilityData->facility;
        }
        return $this->successResponse(["facility_name" => $specilityName], "success");
    }
}
