<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InsuranceCoverage;
use App\Models\Attachments;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use DB;

class InsuranceCoverageController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            "user_id" => "required"
        ]);
        
        $userId = $request->user_id;

        $catType = $request->cat_type;
        
        $customPerPage = $request->has('cust_per_page') ? $request->cust_per_page : $this->cmperPage;

        $insuranceCoverageData = InsuranceCoverage::select('id','policy_number','document_version',DB::raw("DATE_FORMAT(cm_insurance_coverage.effective_date,'%m/%d/%Y') AS effective_date"), 'is_current_version',
            DB::raw("DATE_FORMAT(cm_insurance_coverage.expiration_date,'%m/%d/%Y') AS expiration_date"), DB::raw("DATE_FORMAT(cm_insurance_coverage.created_at,'%m/%d/%Y') AS created_At")
        )
        ->whereRaw("cm_insurance_coverage.document_version = (SELECT MAX(document_version) FROM `cm_insurance_coverage`as ic WHERE ic.for_category = '$catType' AND  ic.user_id = '$userId' AND ic.policy_number = cm_insurance_coverage.policy_number)")
        
        ->where('insurance_coverage.is_delete','=',0)

        ->groupBy('policy_number')
        
        ->orderBy("created_at")
        
        ->paginate($customPerPage);
        // $this->printR($insuranceCoverageData,true);
        $userInsuranceCoverageArr = [];
        if(count($insuranceCoverageData) > 0 ) {
            foreach($insuranceCoverageData as $key=>$iCoverageData) {
                // $this->printR($license,true);
                $userInsuranceCoverageArr[$key] = [
                    "key" => (int)$key + 1,
                    "data" => [
                       "id" => $iCoverageData->id,
                       "policy_number" => $iCoverageData->policy_number,
                       "document_version" => $iCoverageData->document_version,
                       "exp_date" => $iCoverageData->expiration_date,
                       "updated_at" => $iCoverageData->created_At,
                       "is_current_version" => $iCoverageData->is_current_version
                    ]
                ];
                
                $childICDVersions = InsuranceCoverage::select('id','policy_number','document_version',DB::raw("DATE_FORMAT(cm_insurance_coverage.effective_date,'%m/%d/%Y') AS effective_date"), "is_current_version",
                    DB::raw("DATE_FORMAT(cm_insurance_coverage.expiration_date,'%m/%d/%Y') AS expiration_date"), DB::raw("DATE_FORMAT(cm_insurance_coverage.created_at,'%m/%d/%Y') AS created_at")
                )
                ->where("insurance_coverage.id", "<>", $iCoverageData->id)
                
                ->where('policy_number','=',$iCoverageData->policy_number)
                
                ->where('insurance_coverage.is_delete','=',0)

                ->where('user_id','=',$userId)
                
                ->where('for_category','=',$catType)

                ->orderBy("document_version","DESC")

                ->get();
                
                // $this->printR($childICDVersions,true);
                if(count($childICDVersions)) {
                    foreach($childICDVersions as $childVersion) {
                        $userInsuranceCoverageArr[$key]['children'][] = [
                            "key" =>  $childVersion->id,
                            "data" => [
                            "id" => $childVersion->id,
                            "policy_number" => $childVersion->policy_number,
                            "document_version" => $childVersion->document_version,
                            "exp_date" => $childVersion->expiration_date,
                            "updated_at" => $childVersion->created_At,
                            "is_current_version" => $childVersion->is_current_version
                            ]
                        ];
                    }
                }
                else
                    $userInsuranceCoverageArr[$key]['children']= [];
                
            }
        }
        return $this->successResponse(["insurance_coverage" => $userInsuranceCoverageArr],"success");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $InsuranceCoverageObj = new InsuranceCoverage();
        
        $fileStatus = NULL;
        $userId = $request->user_id;
        
        $policyNo = $userId."_".$request->policy_number;

        $insuranceCoverageData = InsuranceCoverage::where('policy_number','=',$policyNo)
        
        ->where('user_id','=',$request->user_id)

        ->orderBy('id', 'DESC')
        
        ->first();
        
        $recordId =  is_object($insuranceCoverageData) ? $insuranceCoverageData->id : 0 ;

        $version = is_object($insuranceCoverageData) ? (int)$insuranceCoverageData->document_version + 1 : 1;
        
        $forCategory = "insurance_coverage";
       if($request->has('for_category'))
        $forCategory = $request->for_category;

        $addInsuranceCoverageData = [
            "user_id"                     => $request->user_id,
            "policy_number"               => $policyNo,
            "malpractice_name"               => $request->malpractice_name,
            "retroactive_date"               => $request->retroactive_date,
            "effective_date"                 => $request->effective_date,
            "amount_coverage_aggregate"      => $request->amount_coverage_aggregate,
            "amount_coverage_occurance"      => $request->amount_coverage_occurance,
            "expiration_date"                   => $request->exp_date,
            "notify_before_exp"                  => $request->notify_before_exp,
            "is_current_version"            => $request->is_current_version,
            "document_version" => $version,
            "created_by" => $request->created_by,
            "type" => $request->type,
            "for_category" => $forCategory,
            "type_id" => $request->type_id,
            "note" => $request->note
        ];
        
        $newId = $InsuranceCoverageObj->addInsuranceCoverage($addInsuranceCoverageData);

        if($newId) {
            $InsuranceCoverageObj->updateCurrentVersion($newId, $policyNo);
        }

        if($request->hasFile('file')) {
            $file = $request->file("file");
            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));
            
            $destFolder = "providersEnc/coverage/" . $request->user_id;


            $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
            if(isset($fileRes["file_name"])) {
                $addFileData = [
                    "entities"     => "coverage",
                    "entity_id"     => $newId,
                    "field_key"     => "coverage file",
                    "field_value"   => $fileRes["file_name"],
                    "created_by" => $request->created_by,
                    "note" => $request->note,
                ];
                $this->addData("attachments",$addFileData);
            }
            $this->uploadMyFile($fileName,$file,"providers/coverage/".$request->user_id);
           
        }
        else {
            if($recordId > 0) {

                $whereFile=[
                    ["entities","=","coverage"],
                    ["entity_id","=",$recordId]
                  
                ];
                $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
                if(is_object($hasFile)) {
                    $addFileData = [
                        "entities"     => "coverage",
                        "entity_id"     => $newId,
                        "field_key"     => $hasFile->field_key,
                        "field_value"   => $hasFile->field_value,
                        "note"   => $hasFile->note
                    ];
                }
            }
        }
      

        return $this->successResponse(["id" => $newId,"file_status" => $fileStatus],"added successfully.");
    
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        
        $insuranceCoverageData = InsuranceCoverage::where('insurance_coverage.id','=',$id)
        ->select('insurance_coverage.id','insurance_coverage.user_id','insurance_coverage.malpractice_name','insurance_coverage.address_line_one',
            'insurance_coverage.address_line_two','insurance_coverage.city','insurance_coverage.note','insurance_coverage.type_id','insurance_coverage.state','insurance_coverage.zip_code','insurance_coverage.phone_number','insurance_coverage.policy_number', 'insurance_coverage.is_current_version',
            DB::raw("DATE_FORMAT(cm_insurance_coverage.expiration_date,'%m/%d/%Y') AS exp_date"),
            "amount_coverage_occurance","amount_coverage_aggregate","notify_before_exp","document_version",
            DB::raw("DATE_FORMAT(cm_insurance_coverage.created_at,'%m/%d/%Y') AS created_At"),
            DB::raw("DATE_FORMAT(cm_insurance_coverage.updated_at,'%m/%d/%Y') AS updated_At"),
            "effective_date","retroactive_date","type",
            DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS created_by")
            
        )
        ->leftJoin('users','users.id','=','insurance_coverage.created_by')
        ->where('insurance_coverage.is_delete', '=', 0)
        ->first();

        // $this->printR($insuranceCoverageData,true);

        $whereFile=[
            ["entities","=","coverage"],
            ["entity_id","=",$id]
          
        ];
        $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
        $url = env("STORAGE_PATH");
        $nestedFolders = "providersEnc/coverage";
        if(is_object($hasFile)) {
            $insuranceCoverageData->file_url = $nestedFolders."/".$insuranceCoverageData->user_id."/".$hasFile->field_value;
            $insuranceCoverageData->field_value = $hasFile->field_value;
            // $insuranceCoverageData->note = $hasFile->note;
        }
        
        return $this->successResponse(['insurance_coverage' => $insuranceCoverageData],"success",200);
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
        $data = InsuranceCoverage::where('id','=',$id)->first();
        
        $allData = $request->all();
        
        $version = is_object($data) ? (int)$data->document_version +1 : 1;
        $fileStatus = NULL;
        $allData['document_version'] = $version;
        unset($allData['file']);
        // unset($allData['note']);
        $isUpdate = InsuranceCoverage::insertGetId($allData);
        if($isUpdate) {
            InsuranceCoverage::where("policy_number", $request->policy_number)
            ->whereNot("id", $isUpdate)
            ->update(["is_current_version" => 0]);
        }
        if($request->hasFile('file')) {
            $file = $request->file("file");
            $fileName = uniqid() . '_'.trim($this->removeWhiteSpaces($file->getClientOriginalName()));
            $destFolder = "providersEnc/coverage/" . $request->user_id;


            $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
            if(isset($fileRes["file_name"])) {
                $addFileData = [
                    "entities"     => "coverage",
                    "entity_id"     => $isUpdate,
                    "field_key"     => "coverage file",
                    "field_value"   => $fileRes["file_name"],
                    "created_by" => $request->created_by,
                    "note" => $request->note,
                    
                ];
                $this->addData("attachments",$addFileData);
            }
            $this->uploadMyFile($fileName,$file,"providers/coverage/".$request->user_id);
            
        }
        else {
            if($id > 0) {

                $whereFile=[
                    ["entities","=","coverage"],
                    ["entity_id","=",$id]
                  
                ];
                $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
                if(is_object($hasFile)) {
                    $addFileData = [
                        "entities"     => "coverage",
                        "entity_id"     => $isUpdate,
                        "field_key"     => $hasFile->field_key,
                        "field_value"   => $hasFile->field_value,
                        "created_by"   => $hasFile->created_by,
                        "note"          => $hasFile->note
                    ];
                    $this->addData("attachments",$addFileData);
                }
            }
        }
        return $this->successResponse(['is_update' => $isUpdate,"file_status" => $fileStatus],"success",200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
    //    $userId = $request->user_id;

       $isDel = InsuranceCoverage::where('id','=',$id)->update(["is_delete" => 1]);
        
    //    $whereFile=[
    //         ["entities","=","coverage"],
    //         ["entity_id","=",$id]
      
    //     ];

    //     $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
    //     if(is_object($hasFile)) {
    //         $this->deleteFile("providersEnc/coverage/".$userId."/". $hasFile->field_value);
    //         Attachments::where($whereFile)->delete();//delete fiel from table
    //     }
       return $this->successResponse(['is_del' => $isDel],"success",200);
    }
}
?>