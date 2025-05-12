<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\EditImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class eCAToolsController extends Controller
{
    use ApiResponseHandler, Utility, EditImage;
    //
    private $key = "";


    public function __construct()
    {
        $this->key = env("AES_KEY");
    }
    /**
     * create the practice w9 form
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createFacilityW9(Request $request)
    {
        $request->validate([
            "practice_id" => "required",
            "facility_id" => "required",
            // "practice_name" => "required",
            // "doing_business_as" => "required",
            // "ownership_status" => "required",
            // "ownership_classification_status" => "required",
            "street_address" => "required",
            "city" => "required",
            "state" => "required",
            "zip_code" => "required",
            //"tax_id"    => "required"
        ]);

        $practiceId = $request->input("practice_id");
        
        $facilityId = $request->input("facility_id");

        $practiceData = DB::table("user_baf_practiseinfo")
        
        ->where("user_id", $practiceId)
        
        ->first();
        
        $key = $this->key;

        $practiceBData = DB::table("user_dd_businessinformation")
        
        ->select("ownership_classification_status",'ownership_status',DB::raw("AES_DECRYPT(facility_tax_id,'$key') as tax_id"))

        ->where("user_id", $practiceId)
        
        ->first();
        
        $classificationText = [
            "C = C - Corporation" => "C",
            "S = S - Corporation" => "S",
            "P - Partnership" => "P",
        ];

        $taxId = isset($practiceBData->tax_id) ? $practiceBData->tax_id : "";
        $ownerShipStatus = isset($practiceBData->ownership_status) ? $practiceBData->ownership_status : "";
        $classification = isset($practiceBData->ownership_classification_status) ? $practiceBData->ownership_classification_status : "";
        // $this->printR($practiceBData,true);
        if($classification !="") {
            $classification = $classificationText[$classification];
        }

        $practiceName = isset($practiceData->practice_name) ? $practiceData->practice_name : "";
        $dBA = isset($practiceData->doing_business_as) ? $practiceData->doing_business_as : "";
        
       
        $allInputData = $request->all();

        $cityStateZip       = $allInputData["city"] . ", " . $allInputData["state"] . " " . $allInputData["zip_code"];
        // $classification     = $classificationText[$allInputData["ownership_classification_status"]];
        // $ownerShipStatus    = $allInputData["ownership_status"];
        $address            = $allInputData["street_address"];
        // $taxId              = $allInputData["tax_id"];
        // Remove the hyphens
        $taxId = str_replace('-', '', $taxId);

        // $practiceId         = $allInputData["practice_id"];
        $w9FormPath         = public_path("certificate_template/fw9-1.jpg");
        // echo $classification;
        // exit;
        $res = $this->editW9PracticeImage($w9FormPath, $practiceName, $dBA, $classification, $ownerShipStatus, $address, $cityStateZip, $taxId, $facilityId);

        $fileName = $facilityId . '-w9-1';

        //$myFile = $fileName . ".pdf";

        $this->saveW9FormAsPdf($fileName); //save w9 file as pdf

        // echo $sampleFilePath = public_path("certificate_template/saved/".$fileName.".jpg");

        $publicPath = public_path();

        // Construct the full path to the file
        $filePath = $publicPath . DIRECTORY_SEPARATOR . "certificate_template" . DIRECTORY_SEPARATOR . "saved" . DIRECTORY_SEPARATOR . $fileName . ".jpg";
        
        $filePathpdf = $publicPath . DIRECTORY_SEPARATOR . "w9form" . DIRECTORY_SEPARATOR  . $fileName . ".pdf";

        $sampleContents = File::get($filePath);

        $sampleContentsPdf = File::get($filePathpdf);

        // $url = url("certificate_template/saved/" . $fileName . ".jpg");

        $uploadStatus = $this->uploadW9File($sampleContents, $fileName . ".jpg");

        if ($uploadStatus == true) {
            File::delete($filePath);
        }
        
        $uploadStatusPdf = $this->uploadW9File($sampleContentsPdf, $fileName . ".pdf");
        
        if ($uploadStatusPdf == true) {
            File::delete($filePathpdf);
        }

        // $samplepdf = url("w9form/" . $fileName . ".pdf");

        return $this->successResponse(["is_generated" => true, "generated_name" => $facilityId . "-w9-1"], "success");

        //$this->printR($allInputData,true);
    }
    
    /**
     * create  provider w9 form
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createProviderW9(Request $request) {
        $request->validate([
            "provider_id" => "required",
            "first_name" => "required",
            "last_name" => "required",
            "street_address" => "required",
            "city" => "required",
            "state" => "required",
            "zip_code" => "required",
            "ssn_number"    => "required"
        ]);

        $allInputData = $request->all();

        $cityStateZip       = $allInputData["city"] . ", " . $allInputData["state"] . " " . $allInputData["zip_code"];
        
        $address            = $allInputData["street_address"];
        
        $ssn                = $allInputData["ssn_number"];
        // Remove the hyphens
        $ssn = str_replace('-', '', $ssn);

        $w9FormPath         = public_path("certificate_template/fw9-1.jpg");
        
        $providerId         = $allInputData["provider_id"];

        $providerName         = $allInputData["first_name"]." ".$allInputData["last_name"];
        
        $isEdit = $this->editW9ProviderImage($w9FormPath, $providerName, $address, $cityStateZip, $ssn, $providerId);

       
        $fileName = $providerId . '-w9-1';

        //$myFile = $fileName . ".pdf";

        $this->saveW9FormAsPdf($fileName); //save w9 file as pdf

        // echo $sampleFilePath = public_path("certificate_template/saved/".$fileName.".jpg");

        $publicPath = public_path();

        // Construct the full path to the file
        $filePath = $publicPath . DIRECTORY_SEPARATOR . "certificate_template" . DIRECTORY_SEPARATOR . "saved" . DIRECTORY_SEPARATOR . $fileName . ".jpg";
        
        $filePathpdf = $publicPath . DIRECTORY_SEPARATOR . "w9form" . DIRECTORY_SEPARATOR  . $fileName . ".pdf";

        $sampleContents = File::get($filePath);

        $sampleContentsPdf = File::get($filePathpdf);

        // $url = url("certificate_template/saved/" . $fileName . ".jpg");

        $uploadStatus = $this->uploadW9File($sampleContents, $fileName . ".jpg");

        if ($uploadStatus == true) {
            File::delete($filePath);
        }
        
        $uploadStatusPdf = $this->uploadW9File($sampleContentsPdf, $fileName . ".pdf");
        
        if ($uploadStatusPdf == true) {
            File::delete($filePathpdf);
        }

        // $samplepdf = url("w9form/" . $fileName . ".pdf");

        return $this->successResponse(["is_generated" => true, "generated_name" => $providerId . "-w9-1"], "success");
    }
    /**
     * delete w9 form
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteW9(Request $request)
    {

        $request->validate([
            "file_name" => "required",
            'extension' => "required",
        ]);

        $fileName = $request->file_name . ".".$request->extension;
        
        $del = $this->delW9File($fileName);

        return $this->successResponse(["is_delete" => $del],'success');
        
    }
}
