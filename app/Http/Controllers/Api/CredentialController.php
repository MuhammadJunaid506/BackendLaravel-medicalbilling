<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CredentialCategory;
use App\Models\CredentialSubCategory;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\CredentialProviderCustomFields;
use App\Models\ProviderCredentials;
use App\Models\ProviderCredentialsArchive;
use App\Models\FacilityCredentialCategory;
use App\Models\FacilityCredentialSubCategory;
use App\Models\CredentialFacilityCustomFields;
use App\Models\FacilityCredentials;
use App\Models\FacilityCredentialsArchive;

class CredentialController extends Controller
{
    use ApiResponseHandler, Utility;
    /**
     * added the credential / categories
     * 
     *
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response 
     */
    public function addProviderCredentailCategory(Request $request)
    {
        $request->validate([
            "category_name" => "required",
        ]);

        $addCategory = [
            "category_name" => $request->category_name,
            "created_at"    => $this->timeStamp()
        ];

        $id = CredentialCategory::insertGetId($addCategory);

        return $this->successResponse(["id" => $id], "Success", 200);
    }
    /**
     * fetch the credential / categories
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getProviderCredentialCategory(Request $request)
    {
        $credentialCategory = CredentialCategory::get();
        return $this->successResponse($credentialCategory, "Success", 200);
    }
    /**
     * added the credential / subcategories
     * 
     *
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response 
     */
    public function addProviderCredentailSubCategory(Request $request)
    {
        $request->validate([
            "license_name" => "required",
            "category_id" => "required"
        ]);

        $addSubCategory = [
            "category_id" => $request->category_id,
            "sub_category_name" => $request->license_name,
            "created_at"    => $this->timeStamp()
        ];

        $id = CredentialSubCategory::insertGetId($addSubCategory);

        $this->addDefaultFields($request->category_id, $id); //add the default fields

        return $this->successResponse(["id" => $id], "Success", 200);
    }
    /**
     * add the fields into the created sub category
     * 
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response
     */
    private function addDefaultFields($categoryId, $subCategoryId)
    {
        $defaultFields = [
            [
                'order' => 1,
                'label' => 'Upload Attachment',
                'type' => 'file',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 2,
                'label' => 'Credential',
                'type' => 'text',
                'value' => '',
                'is_in_form' => true,
                'is_required' => true,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 3,
                'label' => 'Expiration Date',
                'type' => 'date',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 4,
                'label' => 'Issue State',
                'type' => 'dropdown',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 5,
                'label' => 'Issue Date',
                'type' => 'date',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
           
            [
                'order' => 6,
                'label' => 'Expiration Reminder',
                'type' => 'dropdown',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [
                    ['id' => 34, 'label' => '30 Days', 'value' => '30 Days'],
                    ['id' => 12, 'label' => '60 Days', 'value' => '60 Days'],
                    ['id' => 78, 'label' => '90 Days', 'value' => '90 Days'],
                    ['id' => 56, 'label' => '120 Days', 'value' => '120 Days'],
                ],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 7,
                'label' => 'Currently Practicing',
                'type' => 'radio',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [
                    ['id' => 12, 'label' => 'Yes', 'value' => 'yes'],
                    ['id' => 34, 'label' => 'No', 'value' => 'no'],
                ],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 8,
                'label' => 'Notes',
                'type' => 'textarea',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
        ];
        $addAllData = [];
        foreach ($defaultFields as $field) {
            $addCustFields                              = array();
            $addCustFields["category_id"]               = $categoryId;
            $addCustFields["sub_categoryid"]            = $subCategoryId;
            // $addCustFields["field_id"]                  = $field['id'];
            $addCustFields["order"]                     = $field['order'];
            $addCustFields["label"]                     = $field['label'];
            $addCustFields["type"]                      = $field['type'];
            $addCustFields["value"]                     = $field['value'];
            $addCustFields["is_in_form"]                = $field['is_in_form'];
            $addCustFields["is_required"]               = $field['is_required'];
            $addCustFields["is_future_date_allow"]      = $field['is_future_date_allow'];
            $addCustFields["options"]                   = count($field['options']) ? json_encode($field['options']) :  null;

            array_push($addAllData, $addCustFields);
        }

        CredentialProviderCustomFields::insert($addAllData);
    }
    /**
     * add the fields into the created sub category
     * 
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response
     */
    private function addFacilityDefaultFields($categoryId, $subCategoryId)
    {
        $defaultFields = [
            [
                'order' => 1,
                'label' => 'Upload Attachment',
                'type' => 'file',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 2,
                'label' => 'Credential',
                'type' => 'text',
                'value' => '',
                'is_in_form' => true,
                'is_required' => true,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 3,
                'label' => 'Expiration Date',
                'type' => 'date',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 4,
                'label' => 'Issue State',
                'type' => 'dropdown',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 5,
                'label' => 'Issue Date',
                'type' => 'date',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
           
            [
                'order' => 6,
                'label' => 'Expiration Reminder',
                'type' => 'dropdown',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [
                    ['id' => 34, 'label' => '30 Days', 'value' => '30 Days'],
                    ['id' => 12, 'label' => '60 Days', 'value' => '60 Days'],
                    ['id' => 78, 'label' => '90 Days', 'value' => '90 Days'],
                    ['id' => 56, 'label' => '120 Days', 'value' => '120 Days'],
                ],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 7,
                'label' => 'Currently Practicing',
                'type' => 'radio',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [
                    ['id' => 12, 'label' => 'Yes', 'value' => 'yes'],
                    ['id' => 34, 'label' => 'No', 'value' => 'no'],
                ],
                'existing_dropdown_options' => NULL
            ],
            [
                'order' => 8,
                'label' => 'Notes',
                'type' => 'textarea',
                'value' => '',
                'is_in_form' => true,
                'is_required' => false,
                'is_primary' => false,
                'is_future_date_allow' => false,
                'options' => [],
                'existing_dropdown_options' => NULL
            ],
        ];
        $addAllData = [];
        foreach ($defaultFields as $field) {
            $addCustFields                              = array();
            $addCustFields["category_id"]               = $categoryId;
            $addCustFields["sub_categoryid"]            = $subCategoryId;
            // $addCustFields["field_id"]                  = $field['id'];
            $addCustFields["order"]                     = $field['order'];
            $addCustFields["label"]                     = $field['label'];
            $addCustFields["type"]                      = $field['type'];
            $addCustFields["value"]                     = $field['value'];
            $addCustFields["is_in_form"]                = $field['is_in_form'];
            $addCustFields["is_required"]               = $field['is_required'];
            $addCustFields["is_future_date_allow"]      = $field['is_future_date_allow'];
            $addCustFields["options"]                   = count($field['options']) ? json_encode($field['options']) :  null;

            array_push($addAllData, $addCustFields);
        }

        CredentialFacilityCustomFields::insert($addAllData);
    }
    /**
     * fetch the credential / subcategories
     * 
     *
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response 
     */
    public function getProviderCredentailSubCategory(Request $request)
    {
        $request->validate([
            "category_id" => "required"
        ]);

        $categoryId =  $request->category_id;

        $data = CredentialSubCategory::where("category_id", $categoryId)

            ->get();

        return $this->successResponse($data, "Success", 200);
    }
    /**
     * add the provider custom fields
     * 
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response 
     */
    public function addProviderCustomFields(Request $request)
    {
        $request->validate([
            "category_id"     => "required",
            "custom_fields"   => "required",
            "license_id"      => "required"
        ]);

        $reqData = $request->all();

        // $this->printR(json_decode(json_encode($reqData["custom_fields"])),true);
        $filedsArr = json_decode($reqData["custom_fields"], true);
        // $this->printR($filedsArr,true);
        $subCatName = $filedsArr["license_name"];

        $licenseId  = $reqData["license_id"];

        $isLicenseReq  = $reqData["is_license_required"] == "true" ? 1 : 0;

        $isReportReq  = $reqData["is_report_required"] == "true" ? 1 : 0;

        CredentialSubCategory::where("id", $licenseId)

            ->update(["sub_category_name" => $subCatName, 'is_required' => $isLicenseReq, 'is_for_report' => $isReportReq]);

        $filedsStr = json_encode($filedsArr);
        $addData = [
            "category_id"    => $reqData["category_id"],
            "sub_categoryid" => $reqData["license_id"],
            "fields_data"    => $filedsStr
        ];

        $filedsExist = CredentialProviderCustomFields::where("category_id", $reqData["category_id"])

            ->where("sub_categoryid", $reqData["license_id"])

            ->count();

        if ($filedsExist > 0) {
            $addData["updated_by"] = $reqData["session_userid"];
            $addData["updated_at"] = $this->timeStamp();
            $id = CredentialProviderCustomFields::where("category_id", $reqData["category_id"])

                ->where("sub_categoryid", $reqData["license_id"])

                ->update(["fields_data" => $filedsStr]);
        } else {
            $addData["created_by"] = $reqData["session_userid"];
            $addData["created_at"] = $this->timeStamp();
            $id = CredentialProviderCustomFields::insertGetId($addData);
        }

        return $this->successResponse(["id" => $id], "success", 200);
    }
    /**
     * fetch the provider custom fields
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getProviderCustomFields(Request $request)
    {
        $request->validate([
            "category_id" => "required",
            "sub_categoryid" => "required"
        ]);

        $categoryId = $request->category_id;

        $subCategoryId = $request->sub_categoryid;

        $custData = CredentialProviderCustomFields::where("providercredential_custom_fields.category_id", $categoryId)

            ->select(
                "providercredential_custom_fields.category_id",
                "providercredential_custom_fields.sub_categoryid",
                "providercredential_custom_fields.is_primary",
                "providercredential_custom_fields.is_primary",
                "providercredential_custom_fields.order",
                "providercredential_custom_fields.label",
                "providercredential_custom_fields.type",
                "providercredential_custom_fields.value",
                "providercredential_custom_fields.is_in_form",
                "providercredential_custom_fields.is_required",
                "providercredential_custom_fields.is_future_date_allow",
                "providercredential_custom_fields.options",
                "providercredential_custom_fields.id",
                "providercredential_custom_fields.existing_dropdown_options"
            )

            ->where("sub_categoryid", $subCategoryId)

            ->get();

        $subCat = CredentialSubCategory::where("category_id", $categoryId)

            ->select("category_id", "sub_category_name as license_name", "id as license_id", "is_required  as is_license_required", "is_for_report  as is_report_required")

            ->where("id", $subCategoryId)

            ->first();

        if (is_object($subCat)) {
            $subCat->data_fields = $custData;
        }

        return $this->successResponse($subCat, "success", 200);
    }
    /**
     * update the subcategory attributes
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateSubCategory(Request $request, $id)
    {
        $updateData = [];
        if ($request->has("license_name")) {
            $updateData["sub_category_name"] = $request->get("license_name");
        }
        if ($request->has("is_license_required")) {
            $updateData["is_required"] = $request->get("is_license_required") == "true" ||  $request->is_license_required == "1" ? 1 : 0;
        }
        if ($request->has("is_report_required")) {
            $updateData["is_for_report"] = $request->get("is_report_required") == "true" ||  $request->is_report_required == "1"? 1 : 0;
        }
        $isUpdate = false;
        if (count($updateData) > 0) {
            $updateData["created_at"] = $this->timeStamp();
            $isUpdate = CredentialSubCategory::where("id", $id)->update($updateData);
        }
        return $this->successResponse(["is_update" => $isUpdate], "success", 200);
    }
    /**
     * fetch the license categories with hierarchy
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getLicenseCategories(Request $request)
    {

        $categories = CredentialCategory::with('subcategories')->get();

        return $this->successResponse($categories, "success", 200);
    }
    /**
     * add the new field provider credentials settings
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function addProviderCredentialField(Request $request)
    {

        $request->validate([
            "category_id"           => "required",
            "license_id"            => "required",
            "order"                 => "required",
            "label"                 => "required",
            "type"                  => "required",
            "is_in_form"            => "required",
            "is_required"           => "required",
            "is_primary"            => "required",
            "is_primary"            => "required",
            "is_future_date_allow"  => "required",
            "options"               => "required",
        ]);

        $categoryId         = $request->category_id;
        $subcategoryId      = $request->license_id;
        if($request->is_primary == "true") {
            CredentialProviderCustomFields::where("category_id", $categoryId)
            ->where("sub_categoryid", $subcategoryId)
            ->update(["is_primary" => 0]);
        }
        if($request->is_primary == "true") {
            CredentialProviderCustomFields::where("category_id", $categoryId)
            ->where("sub_categoryid", $subcategoryId)
            ->update(["is_primary" => 0]);
        }
        $order              = $request->order;
        $label              = $request->label;
        $type               = $request->type;
        $value              = $request->value;
        $isInForm           = $request->is_in_form;
        $isRequired         = $request->is_required;
        $isFutureDateAllow  = $request->is_future_date_allow;
        $options            = $request->options;
        $existingDropdownOptions = isset($request->existing_dropdown_options) ? $request->existing_dropdown_options : NULL;
        // $fieldId            = uniqid();

        $addField = [];

        $addField["category_id"]            = $categoryId;
        $addField["sub_categoryid"]         = $subcategoryId;
        // $addField["field_id"]               = $fieldId;
        $addField["order"]                  = $order;
        $addField["label"]                  = $label;
        $addField["type"]                   = $type;
        $addField["value"]                  = $value;
        $addField["is_in_form"]             = $isInForm;
        $addField["is_required"]            = $isRequired;
        $addField["is_primary"]             = $request->is_primary == "true" ? 1 : 0;
        $addField["is_future_date_allow"]   = $isFutureDateAllow;
        $addField["existing_dropdown_options"]   = $existingDropdownOptions;
        
        $addField["options"]                = isset($options) ? json_encode(json_decode($options, true))  : [];

        $id = CredentialProviderCustomFields::insertGetId($addField);

        return $this->successResponse(["id" => $id], "sucess", 200);
    }
    /**
     * update provider custom credential fields
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateProviderCredentialField(Request $request, $id)
    {
        $request->validate([
            "category_id" => "required",
            "license_id" => "required",
        ]);
        
        $categoryId = $request->category_id;

        $subCategoryId = $request->license_id;

        
        $updateClientData = [];
        if ($request->has("order")) {
            $updateClientData["order"] = $request->get("order");
        }
        if ($request->has("is_primary")) {
            if($request->is_primary == "true") {
                CredentialProviderCustomFields::where("category_id", $categoryId)
                ->where("sub_categoryid", $subCategoryId)
                ->update(["is_primary" => 0]);
            }
            $updateClientData["is_primary"] = $request->get("is_primary") == "true"  ? 1 : 0;
        }
        if ($request->has("existing_dropdown_options")) {
            $updateClientData["existing_dropdown_options"] = $request->get("existing_dropdown_options");
        }
        
        if ($request->has("label")) {
            $updateClientData["label"] = $request->get("label");
        }
        if ($request->has("type")) {
            $updateClientData["type"] = $request->get("type");
        }
        if ($request->has("value")) {
            $updateClientData["value"] = $request->get("value");
        }
        if ($request->has("is_in_form")) {
            $updateClientData["is_in_form"] = $request->get("is_in_form");
        }
        if ($request->has("is_required")) {
            $updateClientData["is_required"] = $request->get("is_required");
        }
        if ($request->has("is_future_date_allow")) {
            $updateClientData["is_future_date_allow"] = $request->get("is_future_date_allow");
        }
        if ($request->has("options")) {
            $updateClientData["options"] = json_encode(json_decode($request->get("options"), true));
        }

        $isUpdated = false;
        if (count($updateClientData) > 0) {

            $updateClientData["updated_at"]   = $this->timeStamp();

            $updateClientData["updated_by"]     = $request->session_userid;

            $isUpdated = CredentialProviderCustomFields::where("id", $id)->update($updateClientData);
        }
        return $this->successResponse(["is_updated" => $isUpdated], "sucess", 200);
    }

    /**
     * store the provider / credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function addProviderCredential(Request $request)
    {

        $clientData     = $request->all();
        $categoryId     = $request->category_id;
        $subCategoryId  = $request->license_id;
        $providerId     = $request->provider_id;
        $sessionUserId  = $request->session_user_id;
        $docId          = $request->doc_id;

        unset($clientData["category_id"]);
        unset($clientData["license_id"]);
        unset($clientData["provider_id"]);
        unset($clientData["session_user_id"]);
        unset($clientData["doc_id"]);
        // $this->printR($clientData,true);
        $credsData = ProviderCredentials::where("category_id", "=", $categoryId)

            ->where("subcategory_id", "=", $subCategoryId)

            ->where("provider_id", "=", $providerId)

            ->get();
        // $this->printR($credsData,true);
        if ($credsData->count() > 0) {
            // ProviderCredentialsArchive
            foreach ($credsData as $credential) {
                // Create a new archive record
                $archivedCredential = new ProviderCredentialsArchive;
                $archivedCredential->category_id    = $credential->category_id;
                $archivedCredential->subcategory_id = $credential->subcategory_id;
                $archivedCredential->provider_id    = $credential->provider_id;
                $archivedCredential->doc_id         = $credential->doc_id;
                $archivedCredential->field_id       = $credential->field_id;
                $archivedCredential->field_value    = $credential->field_value;
                $archivedCredential->created_by     = $credential->created_by;
                $archivedCredential->doc_id         = $credential->doc_id;
                $archivedCredential->updated_by     = $credential->updated_by;
                $archivedCredential->created_at     = $credential->created_at;
                $archivedCredential->updated_at     = $credential->updated_at;
                
                // Save the archived record
                $archivedCredential->save();
            }
            //delete the provider credential
            ProviderCredentials::where("category_id", "=", $categoryId)

                ->where("subcategory_id", "=", $subCategoryId)

                ->where("provider_id", "=", $providerId)

                ->delete();

            
            
            // $this->printR($clientData,true);
            $insertStatus = $this->addProviderCredentialData($clientData, $request, $providerId, $categoryId, $subCategoryId, $sessionUserId,$docId);
            return $this->successResponse(["is_updated" => $insertStatus], "sucess", 200);
        } else {
           
            // $this->printR($clientData,true);
            $insertStatus = $this->addProviderCredentialData($clientData, $request, $providerId, $categoryId, $subCategoryId, $sessionUserId,$docId);
            return $this->successResponse(["is_added" => $insertStatus], "sucess", 200);
        }
    }
    /**
     * add the provider credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    private function addProviderCredentialData($clientData, $request, $providerId, $categoryId, $subCategoryId, $sessionUserId,$docId)
    {
        $ts = $this->timeStamp();

        $setData  = [];
        $fileName = null;
        foreach ($clientData as $key => $value) {
            if ($request->hasFile($key)) {
                $file           = $request->file($key);
                $destFolder     = "providersEnc/licenses/" . $providerId;
                $name           = $file->getClientOriginalName();
                $token          = $request->bearerToken();
                $fileContents   = $file->get();
                $fileRes        = $this->uploadWithEncryption($token, $fileContents, $destFolder, $name);
                
                if (isset($fileRes["file_name"])) {

                    $fileName = $fileRes["file_name"];
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["provider_id"]     = $providerId;
                    $row["doc_id"]          = $docId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = $fileName;
                    $row["created_by"]      = $sessionUserId;
                    $row["created_at"]      = $ts;

                    array_push($setData, $row);
                }
                else {
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["provider_id"]     = $providerId;
                    $row["doc_id"]          = $docId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = null;
                    $row["created_by"]      = $sessionUserId;
                    $row["created_at"]      = $ts;
                    array_push($setData, $row);
                }
            } else {
                $row = [];
                $row["category_id"]     = $categoryId;
                $row["subcategory_id"]  = $subCategoryId;
                $row["provider_id"]     = $providerId;
                $row["doc_id"]          = $docId;
                $row["doc_id"]          = $docId;
                $row["field_id"]        = $key;
                $row["field_value"]     = $value;
                $row["created_by"]      = $sessionUserId;
                $row["created_at"]      = $ts;

                array_push($setData, $row);
            }
        }
    
        // $this->printR($setData,true);
       
        return ProviderCredentials::insert($setData);
    }
    /**
     * fetch the provider credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchProviderCredentials(Request $request) {
        
        $request->validate([
            "provider_id" => "required",
            "category_id" => "required",
            "license_id" => "required"
        ]);

        $providerId = $request->provider_id;
        
        $categoryId = $request->category_id;
        
        $subCategoryId = $request->license_id;

        
        $providerCreds = ProviderCredentials::where("category_id", "=", $categoryId)

        ->where("subcategory_id", "=", $subCategoryId)

        ->where("provider_id", "=", $providerId)

        ->get();
        // $providerCredsArr = $this->stdToArray($providerCreds);
        // $this->printR($providerCreds,true);
        $providerData = [];
        $allData = [];
        if($providerCreds->count() > 0) {
            foreach($providerCreds as $provider) {
                
                $fieldId = $provider->field_id;
                
                $fieldVal = $provider->field_value;
                
                $providerData[$fieldId] = $fieldVal;
                
                $userName = $this->getSessionUserName($request ,$provider->created_by);
                
                $providerData["created_by"] = $userName;

                $providerData["doc_id"] = $provider->doc_id;

                $providerData["created_at"] = $provider->created_at;
            }
            
            array_push($allData,$providerData);

            $archivedData = ProviderCredentialsArchive::where("category_id", "=", $categoryId)

            ->where("subcategory_id", "=", $subCategoryId)

            ->where("provider_id", "=", $providerId)
            
            ->orderBy("created_at", "DESC")

            ->get();

            $providerArchivedData = [];
            
            if($archivedData->count() > 0) {
                foreach($archivedData as $provider) {
                    
                    $ts = $provider->created_at;
                
                    $fieldId = $provider->field_id;
                    
                    $fieldVal = $provider->field_value;
                    
                    $userName = $this->getSessionUserName($request,$provider->created_by);
                    
                    $providerArchivedData[$provider->doc_id][] = [
                        $fieldId        => $fieldVal,
                        "created_at"    => $provider->created_at,
                        "created_by"    => $userName,
                        "doc_id"        => $provider->doc_id
                    ];
                    
                    // $this->printR($fields,true);

                    // array_push($providerData,$fields);
                }
                // $this->printR($providerArchivedData,true);
                $keys = array_keys($providerArchivedData);
                // $this->printR($providerArchivedData,true);
                if(count($keys) > 0) {
                    foreach($keys as $key) {

                        $data = $providerArchivedData[$key];
                        $keyData = [];
                        // $this->printR($data,true);
                        foreach($data as $key => $val) {
                            $keyData[$key] = $val;
                        }
                        $moreFilter = [];
                        foreach($keyData as $i => $v) {
                        
                            foreach($v as $ii=>$jj) {
                            $moreFilter[$ii] = $jj;
                                //$this->printR($v,true);
                            }
                        }
                        array_push($allData,$moreFilter);
                    }
                }
            
            }
        }
        // array_push($allData,$handleData);
        // $this->printR($allData,true);
        return $this->successResponse(["provider_creds_history" => $allData], "sucess", 200);
    }
    /**
     * update the provider credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateProviderCredentials(Request $request) {
        
        $request->validate([
            "provider_id"   => "required",
            "category_id"   => "required",
            "license_id"    => "required"
        ]);

        $clientData     = $request->all();
        // $this->printR($clientData,true);
        $categoryId     = $request->category_id;
        $subCategoryId  = $request->license_id;
        $providerId     = $request->provider_id;
        $sessionUserId  = $request->session_user_id;
        $docId          = $request->doc_id;

        unset($clientData["category_id"]);
        unset($clientData["license_id"]);
        unset($clientData["provider_id"]);
        unset($clientData["session_user_id"]);
        unset($clientData["doc_id"]);
       
        $insertStatus = $this->updateProviderCredentialData($clientData, $request, $providerId, $categoryId, $subCategoryId, $sessionUserId,$docId);
        return $this->successResponse(["is_update" => $insertStatus], "sucess", 200);
    }
     /**
     * update the provider credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    private function updateProviderCredentialData($clientData, $request, $providerId, $categoryId, $subCategoryId, $sessionUserId,$docId)
    {
        
        $ts = $this->timeStamp();
        
        $foundInCurrentVersion = ProviderCredentials::where("category_id", "=", $categoryId)

        ->where("subcategory_id", "=", $subCategoryId)

        ->where("provider_id", "=", $providerId)

        ->where("doc_id", "=", $docId)

        ->count();

        foreach ($clientData as $key => $value) {
            if ($request->hasFile($key)) {
                $file           = $request->file($key);
                $destFolder     = "providersEnc/licenses/" . $providerId;
                $name           = $file->getClientOriginalName();
                $token          = $request->bearerToken();
                $fileContents   = $file->get();
                $fileRes        = $this->uploadWithEncryption($token, $fileContents, $destFolder, $name);
                
                if (isset($fileRes["file_name"])) {

                    $fileName = $fileRes["file_name"];
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["provider_id"]     = $providerId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = $fileName;
                    $row["updated_by"]      = $sessionUserId;
                    $row["updated_at"]      = $ts;
                    if($foundInCurrentVersion > 0) {
                        
                        $fieldExist = ProviderCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("provider_id", "=", $providerId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                        if($fieldExist == 0) {
                            $row["created_at"]      = $ts;
                            $row["created_by"]      = $sessionUserId;
                            ProviderCredentials::insertGetId($row);
                        }
                        else {
                            ProviderCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("provider_id", "=", $providerId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)
                            
                            ->update($row);
                        }
                    }
                    else {
                            $fieldExist = ProviderCredentialsArchive::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("provider_id", "=", $providerId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                            if($fieldExist == 0) {
                                $row["created_at"]      = $ts;
                                $row["created_by"]      = $sessionUserId;
                                ProviderCredentialsArchive::insertGetId($row);
                            }
                            else {
                                ProviderCredentialsArchive::where("category_id", "=", $categoryId)

                                ->where("subcategory_id", "=", $subCategoryId)
                    
                                ->where("provider_id", "=", $providerId)

                                ->where("doc_id", "=", $docId)

                                ->where("field_id", "=", $key)
                                
                                ->update($row);
                            }
                    }

                    
                }
                else {
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["provider_id"]     = $providerId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = null;
                    $row["updated_by"]      = $sessionUserId;
                    $row["updated_at"]      = $ts;

                    if($foundInCurrentVersion > 0) {
                        $fieldExist = ProviderCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("provider_id", "=", $providerId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                        if($fieldExist == 0) {
                            $row["created_at"]      = $ts;
                            $row["created_by"]      = $sessionUserId;
                            ProviderCredentials::insertGetId($row);
                        }
                        else {
                            ProviderCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("provider_id", "=", $providerId)
                            
                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)
                            
                            ->update($row);
                        }
                    }
                    else {
                        $fieldExist = ProviderCredentialsArchive::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("provider_id", "=", $providerId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                        if($fieldExist == 0) {
                            $row["created_at"]      = $ts;
                            $row["created_by"]      = $sessionUserId;
                            ProviderCredentialsArchive::insertGetId($row);
                        }
                        else {
                            ProviderCredentialsArchive::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("provider_id", "=", $providerId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)
                            
                            ->update($row);
                        }
                    }
                }
            } else {
                $row = [];
                $row["category_id"]     = $categoryId;
                $row["subcategory_id"]  = $subCategoryId;
                $row["provider_id"]     = $providerId;
                $row["doc_id"]          = $docId;
                $row["field_id"]        = $key;
                $row["field_value"]     = $value;
                $row["updated_by"]      = $sessionUserId;
                $row["updated_at"]      = $ts;
               
                if($foundInCurrentVersion > 0) {
                    $fieldExist = ProviderCredentials::where("category_id", "=", $categoryId)

                    ->where("subcategory_id", "=", $subCategoryId)
        
                    ->where("provider_id", "=", $providerId)

                    ->where("doc_id", "=", $docId)

                    ->where("field_id", "=", $key)

                    ->count();
                    if($fieldExist == 0) {
                        $row["created_at"]      = $ts;
                        $row["created_by"]      = $sessionUserId;
                        ProviderCredentials::insertGetId($row);
                    }
                    else {    
                        ProviderCredentials::where("category_id", "=", $categoryId)

                        ->where("subcategory_id", "=", $subCategoryId)
            
                        ->where("provider_id", "=", $providerId)
                        
                        ->where("doc_id", "=", $docId)
                        
                        ->where("field_id", "=", $key)
                        
                        ->update($row);
                    }
                }
                else {
                    $fieldExist = ProviderCredentialsArchive::where("category_id", "=", $categoryId)

                    ->where("subcategory_id", "=", $subCategoryId)
        
                    ->where("provider_id", "=", $providerId)

                    ->where("doc_id", "=", $docId)

                    ->where("field_id", "=", $key)

                    ->count();
                    if($fieldExist == 0) {
                        $row["created_at"]      = $ts;
                        $row["created_by"]      = $sessionUserId;
                        ProviderCredentialsArchive::insertGetId($row);
                    }
                    else {
                        ProviderCredentialsArchive::where("category_id", "=", $categoryId)

                        ->where("subcategory_id", "=", $subCategoryId)
            
                        ->where("provider_id", "=", $providerId)

                        ->where("doc_id", "=", $docId)

                        ->where("field_id", "=", $key)
                        
                        ->update($row);
                    }
                }
            }
        }
    
        return true;
    }
    /**
     * update the credential fields order 
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateCrdentialFieldsOrder(Request $request) {

        $request->validate([
            "provider_fields_order" => "required",
        ]);

        $providerFieldsOder = $request->provider_fields_order;
        
        $subCategoryId = $request->license_id;
        
        $categoryId = $request->category_id;

        if(gettype($providerFieldsOder) == "string")
            $providerFieldsOder = json_decode($providerFieldsOder,true);    
        
        // $this->printR($providerFieldsOder,true);
        $updateCnt = 0;
        if(count($providerFieldsOder) > 0) {
            
            foreach($providerFieldsOder as $eachOrder) {
                // $this->printR($eachOrder,true);
                $id     = $eachOrder["id"];
                
                $order  = $eachOrder["order"];
                
                $updateCnt += CredentialProviderCustomFields::where("id","=",$id)
                
                ->where("sub_categoryid","=",$subCategoryId)
                
                ->where("category_id","=",$categoryId)

                ->update(["order" => $order]);
            }
        }
        return $this->successResponse(["is_update" => $updateCnt], "success");
    }
    /**
     * add the facility category 
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function addFacilityCredentailCategory(Request $request) {
        $request->validate([
            "category_name" => "required",
        ]);

        $addCategory = [
            "category_name" => $request->category_name,
            "created_at"    => $this->timeStamp()
        ];

        $id = FacilityCredentialCategory::insertGetId($addCategory);

        return $this->successResponse(["id" => $id], "success", 200);
    }
     
    /**
     * added the facility credential / subcategories
     * 
     *
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response 
     */
    public function addFacilityCredentailSubCategory(Request $request)
    {
        $request->validate([
            "license_name" => "required",
            "category_id" => "required"
        ]);

        $addSubCategory = [
            "category_id"       => $request->category_id,
            "sub_category_name" => $request->license_name,
            "created_at"        => $this->timeStamp()
        ];

        $id = FacilityCredentialSubCategory::insertGetId($addSubCategory);

        $this->addFacilityDefaultFields($request->category_id, $id); //add the default fields

        return $this->successResponse(["id" => $id], "success", 200);
    }
     /**
     * fetch the credential / subcategories
     * 
     *
     *  @param \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response 
     */
    public function getFacilityCredentailSubCategory(Request $request)
    {
        $request->validate([
            "category_id" => "required"
        ]);

        $categoryId =  $request->category_id;

        $data = FacilityCredentialSubCategory::where("category_id", $categoryId)

        ->get();

        return $this->successResponse($data, "success", 200);
    }
    
    /**
     * fetch the provider custom fields
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getFacilityCustomFields(Request $request)
    {
        $request->validate([
            "category_id" => "required",
            "sub_categoryid" => "required"
        ]);

        $categoryId = $request->category_id;

        $subCategoryId = $request->sub_categoryid;

        $custData = CredentialFacilityCustomFields::where("facilitycredential_custom_fields.category_id", $categoryId)

            ->select(
                "facilitycredential_custom_fields.category_id",
                "facilitycredential_custom_fields.sub_categoryid",
                "facilitycredential_custom_fields.is_primary",
                "facilitycredential_custom_fields.is_primary",
                "facilitycredential_custom_fields.order",
                "facilitycredential_custom_fields.label",
                "facilitycredential_custom_fields.type",
                "facilitycredential_custom_fields.value",
                "facilitycredential_custom_fields.is_in_form",
                "facilitycredential_custom_fields.is_required",
                "facilitycredential_custom_fields.is_future_date_allow",
                "facilitycredential_custom_fields.options",
                "facilitycredential_custom_fields.id",
                "facilitycredential_custom_fields.existing_dropdown_options"
            )

            ->where("sub_categoryid", $subCategoryId)

            ->get();

        $subCat = FacilityCredentialSubCategory::where("category_id", $categoryId)

            ->select("category_id", "sub_category_name as license_name", "id as license_id", "is_required  as is_license_required", "is_for_report  as is_report_required")

            ->where("id", $subCategoryId)

            ->first();

        if (is_object($subCat)) {
            $subCat->data_fields = $custData;
        }

        return $this->successResponse($subCat, "success", 200);
    }
    /**
     * add the new field provider credentials settings
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function addFacilityCustomFields(Request $request)
    {

        $request->validate([
            "category_id"           => "required",
            "license_id"            => "required",
            "order"                 => "required",
            "label"                 => "required",
            "type"                  => "required",
            "is_in_form"            => "required",
            "is_required"           => "required",
            "is_primary"            => "required",
            "is_primary"            => "required",
            "is_future_date_allow"  => "required",
            "options"               => "required",
        ]);

        $categoryId         = $request->category_id;
        $subcategoryId      = $request->license_id;
        if($request->is_primary == "true") {
            CredentialFacilityCustomFields::where("category_id", $categoryId)
            ->where("sub_categoryid", $subcategoryId)
            ->update(["is_primary" => 0]);
        }
        
        $order              = $request->order;
        $label              = $request->label;
        $type               = $request->type;
        $value              = $request->value;
        $isInForm           = $request->is_in_form;
        $isRequired         = $request->is_required;
        $isFutureDateAllow  = $request->is_future_date_allow;
        $options            = $request->options;
        $existingDropdownOptions = isset($request->existing_dropdown_options) ? $request->existing_dropdown_options : NULL;
        
        // $fieldId            = uniqid();

        $addField = [];

        $addField["category_id"]            = $categoryId;
        $addField["sub_categoryid"]         = $subcategoryId;
        // $addField["field_id"]               = $fieldId;
        $addField["order"]                  = $order;
        $addField["label"]                  = $label;
        $addField["type"]                   = $type;
        $addField["value"]                  = $value;
        $addField["is_in_form"]             = $isInForm;
        $addField["is_required"]            = $isRequired;
        $addField["is_primary"]             = $request->is_primary == "true" ? 1 : 0;
        $addField["is_future_date_allow"]   = $isFutureDateAllow;
        $addField["existing_dropdown_options"]   = $existingDropdownOptions;
        
        $addField["options"]                = isset($options) ? json_encode(json_decode($options, true))  : [];

        $id = CredentialFacilityCustomFields::insertGetId($addField);

        return $this->successResponse(["id" => $id], "sucess", 200);
    }
    /**
     * fetch the license categories with hierarchy of the facility
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function getFacilityCredentialCategories(Request $request)
    {

        $categories = FacilityCredentialCategory::with('subcategories')->get();

        return $this->successResponse($categories, "success", 200);
    }
    /**
     * update the facility subcategory attributes
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateFacilitySubCategory(Request $request, $id)
    {
        $updateData = [];
        if ($request->has("license_name")) {
            $updateData["sub_category_name"] = $request->get("license_name");
        }
        if ($request->has("is_license_required")) {
            $updateData["is_required"] = $request->get("is_license_required") == "true" ||  $request->is_license_required == "1" ? 1 : 0;
        }
        if ($request->has("is_report_required")) {
            $updateData["is_for_report"] = $request->get("is_report_required") == "true" ||  $request->is_report_required == "1"? 1 : 0;
        }
        $isUpdate = false;
        if (count($updateData) > 0) {
            $updateData["created_at"] = $this->timeStamp();
            $isUpdate = FacilityCredentialSubCategory::where("id", $id)->update($updateData);
        }
        return $this->successResponse(["is_update" => $isUpdate], "success", 200);
    }
    /**
     * add the new field facility credentials settings
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function addFacilityCredentialField(Request $request)
    {

        $request->validate([
            "category_id"           => "required",
            "license_id"            => "required",
            "order"                 => "required",
            "label"                 => "required",
            "type"                  => "required",
            "is_in_form"            => "required",
            "is_required"           => "required",
            "is_primary"            => "required",
            "is_primary"            => "required",
            "is_future_date_allow"  => "required",
            "options"               => "required",
        ]);

        $categoryId         = $request->category_id;
        $subcategoryId      = $request->license_id;
        if($request->is_primary == "true") {
            CredentialFacilityCustomFields::where("category_id", $categoryId)
            ->where("sub_categoryid", $subcategoryId)
            ->update(["is_primary" => 0]);
        }
        if($request->is_primary == "true") {
            CredentialFacilityCustomFields::where("category_id", $categoryId)
            ->where("sub_categoryid", $subcategoryId)
            ->update(["is_primary" => 0]);
        }
        $order              = $request->order;
        $label              = $request->label;
        $type               = $request->type;
        $value              = $request->value;
        $isInForm           = $request->is_in_form;
        $isRequired         = $request->is_required;
        $isFutureDateAllow  = $request->is_future_date_allow;
        $options            = $request->options;
        $existingDropdownOptions = isset($request->existing_dropdown_options) ? $request->existing_dropdown_options : NULL;
        // $fieldId            = uniqid();

        $addField = [];

        $addField["category_id"]            = $categoryId;
        $addField["sub_categoryid"]         = $subcategoryId;
        // $addField["field_id"]               = $fieldId;
        $addField["order"]                  = $order;
        $addField["label"]                  = $label;
        $addField["type"]                   = $type;
        $addField["value"]                  = $value;
        $addField["is_in_form"]             = $isInForm;
        $addField["is_required"]            = $isRequired;
        $addField["is_primary"]             = $request->is_primary == "true" ? 1 : 0;
        $addField["is_future_date_allow"]   = $isFutureDateAllow;
        $addField["existing_dropdown_options"]   = $existingDropdownOptions;
        
        $addField["options"]                = isset($options) ? json_encode(json_decode($options, true))  : [];

        $id = CredentialFacilityCustomFields::insertGetId($addField);

        return $this->successResponse(["id" => $id], "sucess", 200);
    }
    /**
     * update facility custom credential fields
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateFacilityCredentialField(Request $request, $id)
    {
        $request->validate([
            "category_id" => "required",
            "license_id" => "required",
        ]);
        
        $categoryId = $request->category_id;

        $subCategoryId = $request->license_id;

        
        $updateClientData = [];
        if ($request->has("order")) {
            $updateClientData["order"] = $request->get("order");
        }
        if ($request->has("is_primary")) {
            if($request->is_primary == "true") {
                CredentialFacilityCustomFields::where("category_id", $categoryId)
                ->where("sub_categoryid", $subCategoryId)
                ->update(["is_primary" => 0]);
            }
            $updateClientData["is_primary"] = $request->get("is_primary") == "true"  ? 1 : 0;
        }
        if ($request->has("existing_dropdown_options")) {
            $updateClientData["existing_dropdown_options"] = $request->get("existing_dropdown_options");
        }
        
        if ($request->has("label")) {
            $updateClientData["label"] = $request->get("label");
        }
        if ($request->has("type")) {
            $updateClientData["type"] = $request->get("type");
        }
        if ($request->has("value")) {
            $updateClientData["value"] = $request->get("value");
        }
        if ($request->has("is_in_form")) {
            $updateClientData["is_in_form"] = $request->get("is_in_form");
        }
        if ($request->has("is_required")) {
            $updateClientData["is_required"] = $request->get("is_required");
        }
        if ($request->has("is_future_date_allow")) {
            $updateClientData["is_future_date_allow"] = $request->get("is_future_date_allow");
        }
        if ($request->has("options")) {
            $updateClientData["options"] = json_encode(json_decode($request->get("options"), true));
        }

        $isUpdated = false;
        if (count($updateClientData) > 0) {

            $updateClientData["updated_at"]   = $this->timeStamp();

            $updateClientData["updated_by"]     = $request->session_userid;

            $isUpdated = CredentialFacilityCustomFields::where("id", $id)->update($updateClientData);
        }
        return $this->successResponse(["is_updated" => $isUpdated], "sucess", 200);
    }
    /**
     * store the facility / credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function addFacilityCredential(Request $request)
    {
        $request->validate([
            "category_id"       => "required",
            "license_id"        => "required",
            "facility_id"       => "required",
            "session_user_id"   => "required",
            "doc_id"            => "required",
        ]);

        $clientData     = $request->all();
        $categoryId     = $request->category_id;
        $subCategoryId  = $request->license_id;
        $facilityId     = $request->facility_id;
        $sessionUserId  = $request->session_user_id;
        $docId          = $request->doc_id;

        unset($clientData["category_id"]);
        unset($clientData["license_id"]);
        unset($clientData["facility_id"]);
        unset($clientData["session_user_id"]);
        unset($clientData["doc_id"]);
        // $this->printR($clientData,true);
        $credsData = FacilityCredentials::where("category_id", "=", $categoryId)

            ->where("subcategory_id", "=", $subCategoryId)

            ->where("facility_id", "=", $facilityId)

            ->get();
        // $this->printR($credsData,true);
        if ($credsData->count() > 0) {
            // ProviderCredentialsArchive
            foreach ($credsData as $credential) {
                // Create a new archive record
                $archivedCredential = new FacilityCredentialsArchive;
                $archivedCredential->category_id    = $credential->category_id;
                $archivedCredential->subcategory_id = $credential->subcategory_id;
                $archivedCredential->facility_id    = $credential->facility_id;
                $archivedCredential->doc_id         = $credential->doc_id;
                $archivedCredential->field_id       = $credential->field_id;
                $archivedCredential->field_value    = $credential->field_value;
                $archivedCredential->created_by     = $credential->created_by;
                $archivedCredential->doc_id         = $credential->doc_id;
                $archivedCredential->updated_by     = $credential->updated_by;
                $archivedCredential->created_at     = $credential->created_at;
                $archivedCredential->updated_at     = $credential->updated_at;
                
                // Save the archived record
                $archivedCredential->save();
            }
            //delete the provider credential
            FacilityCredentials::where("category_id", "=", $categoryId)

                ->where("subcategory_id", "=", $subCategoryId)

                ->where("facility_id", "=", $facilityId)

                ->delete();

            
            // $this->printR($clientData,true);
            $insertStatus = $this->addFacilityCredentialData($clientData, $request, $facilityId, $categoryId, $subCategoryId, $sessionUserId,$docId);
            return $this->successResponse(["is_updated" => $insertStatus], "sucess", 200);
        } else {
           
            // $this->printR($clientData,true);
            $insertStatus = $this->addFacilityCredentialData($clientData, $request, $facilityId, $categoryId, $subCategoryId, $sessionUserId,$docId);
            return $this->successResponse(["is_added" => $insertStatus], "sucess", 200);
        }
    }
    /**
     * add the facility credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    private function addFacilityCredentialData($clientData, $request, $facilityId, $categoryId, $subCategoryId, $sessionUserId,$docId)
    {
        $ts = $this->timeStamp();

        $setData  = [];
        $fileName = null;
        foreach ($clientData as $key => $value) {
            if ($request->hasFile($key)) {
                $file           = $request->file($key);
                $destFolder     = "facilityEnc/licenses/" . $facilityId;
                $name           = $file->getClientOriginalName();
                $token          = $request->bearerToken();
                $fileContents   = $file->get();
                $fileRes        = $this->uploadWithEncryption($token, $fileContents, $destFolder, $name);
                
                if (isset($fileRes["file_name"])) {

                    $fileName = $fileRes["file_name"];
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["facility_id"]     = $facilityId;
                    $row["doc_id"]          = $docId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = $fileName;
                    $row["created_by"]      = $sessionUserId;
                    $row["created_at"]      = $ts;

                    array_push($setData, $row);
                }
                else {
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["facility_id"]     = $facilityId;
                    $row["doc_id"]          = $docId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = null;
                    $row["created_by"]      = $sessionUserId;
                    $row["created_at"]      = $ts;
                    array_push($setData, $row);
                }
            } else {
                $row = [];
                $row["category_id"]     = $categoryId;
                $row["subcategory_id"]  = $subCategoryId;
                $row["facility_id"]     = $facilityId;
                $row["doc_id"]          = $docId;
                $row["doc_id"]          = $docId;
                $row["field_id"]        = $key;
                $row["field_value"]     = $value;
                $row["created_by"]      = $sessionUserId;
                $row["created_at"]      = $ts;

                array_push($setData, $row);
            }
        }
    
        // $this->printR($setData,true);
       
        return FacilityCredentials::insert($setData);
    }
    /**
     * fetch the facility credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function fetchFacilityCredentials(Request $request) {
        
        $request->validate([
            "facility_id" => "required",
            "category_id" => "required",
            "license_id" => "required"
        ]);

        $facilityId = $request->facility_id;
        
        $categoryId = $request->category_id;
        
        $subCategoryId = $request->license_id;

        
        $facilityCreds = FacilityCredentials::where("category_id", "=", $categoryId)

        ->where("subcategory_id", "=", $subCategoryId)

        ->where("facility_id", "=", $facilityId)

        ->get();
        
        $facilityData = [];
        $allData = [];
        if($facilityCreds->count() > 0) {
            foreach($facilityCreds as $facility) {
                
                $fieldId = $facility->field_id;
                
                $fieldVal = $facility->field_value;
                
                $facilityData[$fieldId] = $fieldVal;
                
                $userName = $this->getSessionUserName($request ,$facility->created_by);
                
                $facilityData["created_by"] = $userName;

                $facilityData["doc_id"] = $facility->doc_id;

                $facilityData["created_at"] = $facility->created_at;
            }
            
            array_push($allData,$facilityData);

            $archivedData = FacilityCredentialsArchive::where("category_id", "=", $categoryId)

            ->where("subcategory_id", "=", $subCategoryId)

            ->where("facility_id", "=", $facilityId)
            
            ->orderBy("created_at", "DESC")

            ->get();

            $facilityArchivedData = [];
            
            if($archivedData->count() > 0) {
                foreach($archivedData as $facility) {
                    
                    $ts = $facility->created_at;
                
                    $fieldId = $facility->field_id;
                    
                    $fieldVal = $facility->field_value;
                    
                    $userName = $this->getSessionUserName($request,$facility->created_by);
                    
                    $facilityArchivedData[$facility->doc_id][] = [
                        $fieldId        => $fieldVal,
                        "created_at"    => $facility->created_at,
                        "created_by"    => $userName,
                        "doc_id"        => $facility->doc_id
                    ];
                    
                    // $this->printR($fields,true);

                    // array_push($providerData,$fields);
                }
                // $this->printR($facilityArchivedData,true);
                $keys = array_keys($facilityArchivedData);
                // $this->printR($facilityArchivedData,true);
                if(count($keys) > 0) {
                    foreach($keys as $key) {

                        $data = $facilityArchivedData[$key];
                        $keyData = [];
                        // $this->printR($data,true);
                        foreach($data as $key => $val) {
                            $keyData[$key] = $val;
                        }
                        $moreFilter = [];
                        foreach($keyData as $i => $v) {
                        
                            foreach($v as $ii=>$jj) {
                            $moreFilter[$ii] = $jj;
                                //$this->printR($v,true);
                            }
                        }
                        array_push($allData,$moreFilter);
                    }
                }
            
            }
        }
        // array_push($allData,$handleData);
        // $this->printR($allData,true);
        return $this->successResponse(["facility_creds_history" => $allData], "sucess", 200);
    }
    /**
     * update the facility credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateFacilityCredentials(Request $request) {
        
        $request->validate([
            "facility_id"   => "required",
            "category_id"   => "required",
            "license_id"    => "required"
        ]);

        $clientData     = $request->all();
        // $this->printR($clientData,true);
        $categoryId     = $request->category_id;
        $subCategoryId  = $request->license_id;
        $facilityId     = $request->facility_id;
        $sessionUserId  = $request->session_user_id;
        $docId          = $request->doc_id;

        unset($clientData["category_id"]);
        unset($clientData["license_id"]);
        unset($clientData["facility_id"]);
        unset($clientData["session_user_id"]);
        unset($clientData["doc_id"]);
       
        $insertStatus = $this->updateFacilityCredentialData($clientData, $request, $facilityId, $categoryId, $subCategoryId, $sessionUserId,$docId);
        return $this->successResponse(["is_update" => $insertStatus], "sucess", 200);
    }
     /**
     * update the provider credentials
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    private function updateFacilityCredentialData($clientData, $request, $facilityId, $categoryId, $subCategoryId, $sessionUserId,$docId)
    {
        
        $ts = $this->timeStamp();
        
        $foundInCurrentVersion = FacilityCredentials::where("category_id", "=", $categoryId)

        ->where("subcategory_id", "=", $subCategoryId)

        ->where("facility_id", "=", $facilityId)

        ->where("doc_id", "=", $docId)

        ->count();

        foreach ($clientData as $key => $value) {
            if ($request->hasFile($key)) {
                $file           = $request->file($key);
                $destFolder     = "facilityEnc/licenses/" . $facilityId;
                $name           = $file->getClientOriginalName();
                $token          = $request->bearerToken();
                $fileContents   = $file->get();
                $fileRes        = $this->uploadWithEncryption($token, $fileContents, $destFolder, $name);
                
                if (isset($fileRes["file_name"])) {

                    $fileName = $fileRes["file_name"];
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["facility_id"]     = $facilityId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = $fileName;
                    $row["updated_by"]      = $sessionUserId;
                    $row["updated_at"]      = $ts;
                    if($foundInCurrentVersion > 0) {
                        
                        $fieldExist = FacilityCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("facility_id", "=", $facilityId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                        if($fieldExist == 0) {
                            $row["created_at"]      = $ts;
                            $row["created_by"]      = $sessionUserId;
                            FacilityCredentials::insertGetId($row);
                        }
                        else {
                            FacilityCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("facility_id", "=", $facilityId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)
                            
                            ->update($row);
                        }
                    }
                    else {
                            $fieldExist = FacilityCredentialsArchive::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("facility_id", "=", $facilityId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                            if($fieldExist == 0) {
                                $row["created_at"]      = $ts;
                                $row["created_by"]      = $sessionUserId;
                                FacilityCredentialsArchive::insertGetId($row);
                            }
                            else {
                                FacilityCredentialsArchive::where("category_id", "=", $categoryId)

                                ->where("subcategory_id", "=", $subCategoryId)
                    
                                ->where("facility_id", "=", $facilityId)

                                ->where("doc_id", "=", $docId)

                                ->where("field_id", "=", $key)
                                
                                ->update($row);
                            }
                    }

                    
                }
                else {
                    $row = [];
                    $row["category_id"]     = $categoryId;
                    $row["subcategory_id"]  = $subCategoryId;
                    $row["facility_id"]     = $facilityId;
                    $row["doc_id"]          = $docId;
                    $row["field_id"]        = $key;
                    $row["field_value"]     = null;
                    $row["updated_by"]      = $sessionUserId;
                    $row["updated_at"]      = $ts;

                    if($foundInCurrentVersion > 0) {
                        $fieldExist = FacilityCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("facility_id", "=", $facilityId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                        if($fieldExist == 0) {
                            $row["created_at"]      = $ts;
                            $row["created_by"]      = $sessionUserId;
                            FacilityCredentials::insertGetId($row);
                        }
                        else {
                            FacilityCredentials::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("facility_id", "=", $facilityId)
                            
                            ->where("doc_id", "=", $docId)
                            
                            ->where("field_id", "=", $key)
                            
                            ->update($row);
                        }
                    }
                    else {
                        $fieldExist = FacilityCredentialsArchive::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("facility_id", "=", $facilityId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)

                            ->count();
                        if($fieldExist == 0) {
                            $row["created_at"]      = $ts;
                            $row["created_by"]      = $sessionUserId;
                            FacilityCredentialsArchive::insertGetId($row);
                        }
                        else {
                            FacilityCredentialsArchive::where("category_id", "=", $categoryId)

                            ->where("subcategory_id", "=", $subCategoryId)
                
                            ->where("facility_id", "=", $facilityId)

                            ->where("doc_id", "=", $docId)

                            ->where("field_id", "=", $key)
                            
                            ->update($row);
                        }
                    }
                }
            } else {
                $row = [];
                $row["category_id"]     = $categoryId;
                $row["subcategory_id"]  = $subCategoryId;
                $row["facility_id"]     = $facilityId;
                $row["doc_id"]          = $docId;
                $row["field_id"]        = $key;
                $row["field_value"]     = $value;
                $row["updated_by"]      = $sessionUserId;
                $row["updated_at"]      = $ts;
               
                if($foundInCurrentVersion > 0) {
                    $fieldExist = FacilityCredentials::where("category_id", "=", $categoryId)

                    ->where("subcategory_id", "=", $subCategoryId)
        
                    ->where("facility_id", "=", $facilityId)

                    ->where("doc_id", "=", $docId)

                    ->where("field_id", "=", $key)

                    ->count();
                    if($fieldExist == 0) {
                        $row["created_at"]      = $ts;
                        $row["created_by"]      = $sessionUserId;
                        FacilityCredentials::insertGetId($row);
                    }
                    else {    
                        FacilityCredentials::where("category_id", "=", $categoryId)

                        ->where("subcategory_id", "=", $subCategoryId)
            
                        ->where("facility_id", "=", $facilityId)
                        
                        ->where("doc_id", "=", $docId)
                        
                        ->where("field_id", "=", $key)
                        
                        ->update($row);
                    }
                }
                else {
                    $fieldExist = FacilityCredentialsArchive::where("category_id", "=", $categoryId)

                    ->where("subcategory_id", "=", $subCategoryId)
        
                    ->where("facility_id", "=", $facilityId)

                    ->where("doc_id", "=", $docId)

                    ->where("field_id", "=", $key)

                    ->count();
                    if($fieldExist == 0) {
                        $row["created_at"]      = $ts;
                        $row["created_by"]      = $sessionUserId;
                        FacilityCredentialsArchive::insertGetId($row);
                    }
                    else {
                        FacilityCredentialsArchive::where("category_id", "=", $categoryId)

                        ->where("subcategory_id", "=", $subCategoryId)
            
                        ->where("facility_id", "=", $facilityId)

                        ->where("doc_id", "=", $docId)

                        ->where("field_id", "=", $key)
                        
                        ->update($row);
                    }
                }
            }
        }
    
        return true;
    }
    /**
     * update the credential fields order 
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function updateFacilityCrdentialFieldsOrder(Request $request) {

        $request->validate([
            "facility_fields_order" => "required",
        ]);

        $facilityFieldsOder = $request->facility_fields_order;
        
        $subCategoryId = $request->license_id;
        
        $categoryId = $request->category_id;

        if(gettype($facilityFieldsOder) == "string")
            $facilityFieldsOder = json_decode($facilityFieldsOder,true);    
        
        // $this->printR($providerFieldsOder,true);
        $updateCnt = 0;
        if(count($facilityFieldsOder) > 0) {
            
            foreach($facilityFieldsOder as $eachOrder) {
                // $this->printR($eachOrder,true);
                $id     = $eachOrder["id"];
                
                $order  = $eachOrder["order"];
                
                $updateCnt += CredentialFacilityCustomFields::where("id","=",$id)
                
                ->where("sub_categoryid","=",$subCategoryId)
                
                ->where("category_id","=",$categoryId)

                ->update(["order" => $order]);
            }
        }
        return $this->successResponse(["is_update" => $updateCnt], "success");
    }
}
