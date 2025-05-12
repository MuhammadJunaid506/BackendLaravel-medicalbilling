<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Company;
use App\Models\CompanyCustomFields;

class CompanyController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {

            $companies = Company::paginate($this->cmperPage);
            
            return $this->successResponse($companies,"success");
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
            // "admin_id"              => "required",
            "owner_first_name"      => "required",
            "owner_last_name"      => "required",
            "company_name"         => "required | unique:companies",
            "company_address"      => "required",
            "company_type"         => "required",
            "company_country"      => "required",
            "company_copyright"   => "required",
            "company_state"        => "required",
            "company_contact"      => "required"
        ]);
        try {
            $fileName = "";
            if(
                $request->file('company_logo') !=""     &&  
                $request->file('company_logo') !="null" &&  
                $request->file('company_logo') !=null
            ) {
                $path = public_path('storage/companies/logos');
                
                $file = $request->file('business_logo');

                $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

                if ( ! file_exists($path) ) {
                    mkdir($path, 0777, true);
                }
                $file->move($path, $fileName);
            }
            
            // $adminId            = $request->admin_id;
            $ownerFirstName     = $request->owner_first_name;
            $ownerLastName      = $request->owner_last_name;
            $companyName       = $request->company_name;
            $companyAddress    = $request->company_address;
            $companyType       = $request->company_type;
            $companyCountry    = $request->company_country;
            $companyCopyRight  = $request->company_copyright;
            $companyState      = $request->company_state;
            $companyContact    = $request->company_contact;
            
            $addCompanyData = [
                // "admin_id"              => $adminId,
                "owner_first_name"      => $ownerFirstName,
                "owner_last_name"       => $ownerLastName,
                "company_name"         => $companyName,
                "company_address"      => $companyAddress,
                "company_type"         => $companyType,
                "company_country"      => $companyCountry,
                "company_copyright"   => $companyCopyRight,
                "company_state"        => $companyState,
                "company_contact"      => $companyContact,
                "company_logo"         => $fileName,
                "created_at"            => date("Y-m-d H:i:s")
            ];

            
            $company = Company::create($addCompanyData);//create new company
            
            if( $request->has('custom_fields') ) {
                $customFields = $request->custom_fields;
                $addCustomFields = ["company_id" => $company->id,"fields_data" => $customFields,"created_at" => date("Y-m-d H:i:s")];
                CompanyCustomFields::create($addCustomFields);//add the compnay custom fields
            }
            return $this->successResponse(["id" => $company->id],"Company added successfully.");
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
    public function searchCompany(Request $request) {
       

        try {
            
            $keyWord = $request->keyword;

            $result = Company::where('company_name', 'LIKE', '%'. $keyWord. '%')
            
            ->orWhere('owner_first_name', 'LIKE', '%'. $keyWord. '%')
            
            ->orWhere('owner_last_name', 'LIKE', '%'. $keyWord. '%')
            
            ->orWhere('company_contact', 'LIKE', '%'. $keyWord. '%')

            ->get();

            if( count($result) ) {
                return $this->successResponse($result,"Success");
            }
            else {
                return $this->warningResponse($result,'No data not found', 404);
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
                $company = Company::find($id);
                return $this->successResponse($company,"Success");
            }
            catch (\Throwable $exception) {
                //throw $th;
                return $this->errorResponse([],$exception->getMessage(),500);
            }
        }
        else {
            return $this->searchCompany($request);
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
            
            if($col == "company_logo") {

                $path = public_path('companies/logos');
                
                $file = $request->val;
                
                $oldLogo = $request->old_logo;
                
                $path = public_path('storage/companies/logos/').$oldLogo;
                
                unlink($path);//delete previous logo

                $fileName = uniqid() . '_' . trim($file->getClientOriginalName());

                if ( ! file_exists($path) ) {
                    mkdir($path, 0777, true);
                }
                $file->move($path, $fileName);

                $isUpdate = Company::find($id)->update([$col => $fileName,"updated_at" => date("Y-m-d H:i:s")]);

                return $this->successResponse(['id' => $id,'is_update' => $isUpdate],"Company updated successfully.");
            }
            else {
                $isUpdate = Company::find($id)->update([$col => $val,"updated_at" => date("Y-m-d H:i:s")]);

                return $this->successResponse(['id' => $id,'is_update' => $isUpdate],"Company updated successfully.");
            }
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
            $company = Company::find($id);
            // $this->printR($company,true);
            if(is_object($company)) {
                if($company->company_logo) {
                    
                    $path = public_path('storage/companies/logos/').$company->company_logo;
                    if (file_exists($path)) {
                        unlink($path);//delete the company logo
                    }
                }
            }
            
            $isDel = Company::find($id)->delete();

            $customFieldExist = CompanyCustomFields::where("company_id",$id)->first();//check if custom fields exist against the company
            if(is_object($customFieldExist))
                CompanyCustomFields::where("company_id",$id)->delete();//delete the company related custom fields

            return $this->successResponse(['id' => $id,'is_del' => $isDel],"Company deleted successfully.");
        }
        catch (\Throwable $exception) {
            //throw $th;
            return $this->errorResponse([],$exception->getMessage(),500);
        }
    }
}
