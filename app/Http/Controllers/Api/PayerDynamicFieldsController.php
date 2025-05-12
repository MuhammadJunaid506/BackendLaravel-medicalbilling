<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Attachments;
use App\Models\Education;
use App\Models\HospitalAffliation;
use App\Models\InsuranceCoverage;
use App\Models\License;
use Illuminate\Support\Str;
use App\Models\LicenseTypes;
use App\Models\Payer;
use App\Models\PayerDynamicFields;
use App\Models\PayerLinkedColumn;
use App\Models\PracticeLocation;
use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayerDynamicFieldsController extends Controller
{
    use ApiResponseHandler, Utility;

    private $key = "";

    public function __construct()
    {
        $this->key = env("AES_KEY");
    }


    public function payerUserFields(Request $request)
    {

        $request->validate([
            'payer_id' => 'required',
            'provider_id' => 'required',
            'facilityid' => 'required',
        ]);

        $key = $this->key;
        $payerId = $request->payer_id;
        $provider_id = $request->provider_id;
        $payers = Payer::select('id', 'payer_name')->where('id', $payerId)->first();
        if ($payers == null) {
            return $this->errorResponse([], "No Payer found", 404);
        }

        //users
        $users = User::select('id',  DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as name"))->where('id', $provider_id)->get()->toArray();
        $allusersIds = array_column($users, 'id');
        $allusersName = array_column($users, 'name');
        $combineUsersidName = array_combine($allusersIds, $allusersName);

        // //facility
        $facility = PracticeLocation::select('user_id as id', DB::raw("AES_DECRYPT(practice_name,'$key') as name"))->where('user_id', $request->facilityid)->get()->toArray();
        $allFacilityIds = array_column($facility, 'id');
        $allFacilityNames = array_column($facility, 'name');
        $combineFacilityidName = array_combine($allFacilityIds, $allFacilityNames);

        $facility = PracticeLocation::where('user_id', $request->facilityid)->first();
        $practiceId = $facility->user_parent_id;

        $type = $provider_id == 0 ? 'facility' : 'provider';
        $payerLinkedColumn = PayerLinkedColumn::where('payer_id', $payers->id)->where('type', $type)->first();

        $providers = DB::table("individualprovider_location_map as ilp")
            ->select("ilp.user_id as provider_id", DB::raw("CONCAT(COALESCE(cm_users.first_name,''), ' ', COALESCE(cm_users.last_name,'')) as provider_name"))
            ->join("users", "users.id", "=", "ilp.user_id")
            ->where("ilp.location_user_id", $request->facilityid)
            ->groupBy("ilp.user_id")
            ->get();
        $facilityFieldsData = [];
        if ($payerLinkedColumn != null) {
            $facilityFields = $this->payerproviderdata($payers->id, $request->facilityid, $type, 'facility');
            $facilityFieldsData = [...$facilityFields, 'providers' => $providers];
        }

        if ($type == 'facility') {
            $facilityWiseData = [
                'payer_id' => $payers->id,
                'payer_name' => $payers->payer_name,
                'type' => $type,
                'provider_id' => $provider_id,
                'facility_id' => $request->facilityid,
                'name' => $combineFacilityidName[$request->facilityid] ?? '',
                'facility' => $facilityFieldsData,
                'practice' => $payerLinkedColumn == null ? [] :  $this->payerproviderdata($payers->id, $practiceId, $type, 'practice'),
            ];
        } else {

            $facilityWiseData = [
                'payer_id' => $payers->id,
                'payer_name' => $payers->payer_name,
                'type' => $type,
                'provider_id' => $provider_id,
                'facility_id' => $request->facilityid,
                'name' => $combineUsersidName[$provider_id] ?? '',
                'provider' => $payerLinkedColumn == null ? [] :  $this->payerproviderdata($payers->id, $provider_id, $type, 'provider'),
                'facility' => $facilityFieldsData,
                'practice' =>  $payerLinkedColumn == null ? [] : $this->payerproviderdata($payers->id, $practiceId, $type, 'practice'),
            ];
        }

        return $this->successResponse(['fields_data' => $facilityWiseData], "success");
    }

    public function payerproviderdata($payerid, $providerId, $type, $subType)
    {

        $userid = $providerId;
        $columnValues['profile_fields'] = [];
        $columnValues['attacments_fields'] = [];
        $payerLinkedColumn = PayerLinkedColumn::where('payer_id', $payerid)->where('type', $type)->first();
        if ($payerLinkedColumn != null && $payerLinkedColumn->linked_column != null) {
            $linkedColumn = json_decode($payerLinkedColumn->linked_column, true);
            if (isset($linkedColumn[$subType])) {

                $typelinkedColumn = $linkedColumn[$subType];
                $profileColumn = $typelinkedColumn['profile_column'];
                $attacmentsTypes = $typelinkedColumn['attacment_types'];
                foreach ($profileColumn as $key => $profilevalue) {
                    $inputvalue =  $this->userProfileValue($profilevalue['value'], $userid, $subType);
                    $label = strtolower(str_replace(' ', '_', $profilevalue['label']));
                    $columnValues['profile_fields'][$label] = $inputvalue;
                }
                if ($subType != 'practice') {
                    foreach ($attacmentsTypes as $key => $attachmentvalue) {
                        $inputvalue =  $this->attachmentValue($attachmentvalue['id'], $userid);

                        $columnValues['attacments_fields'][] = [
                            'file' => $inputvalue['file'],
                            'label' => $attachmentvalue['name'],
                            'expiry_date' => $inputvalue['expiry_date'],
                            'is_expired' => $inputvalue['is_expired'],
                            'expiring_soon' => $inputvalue['expiring_soon'],
                        ];
                    }
                }
            }
        }
        return $columnValues;
    }

    /**
     * Fetch the linked column for payers
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function payerLinkedColumn(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'payer_id' => 'required|integer',
        ]);

        $payerLinkdedColumn = PayerLinkedColumn::where('payer_id', $request->payer_id)->where('type', $request->type)->first();
        $linkedColumn = $payerLinkdedColumn == null || $payerLinkdedColumn->linked_column == null ? [] : json_decode($payerLinkdedColumn->linked_column, true);
        $typeColumns = $this->userColumnAndAttachmentTypes($request->type);

        foreach ($typeColumns as $key => $typeColumn) {
            foreach ($typeColumn['profile_column'] as $profileKey => $profilecolumn) {
                $profileColumnValue = isset($linkedColumn[$key]) ? array_column($linkedColumn[$key]['profile_column'], 'value') : [];
                $status = false;
                if (in_array($profilecolumn['value'], $profileColumnValue)) {
                    $status  = true;
                }
                if (isset($typeColumns[$key])) {
                    $typeColumns[$key]['profile_column'][$profileKey]['is_checked'] = $status;
                }
            }
            foreach ($typeColumn['attacment_types'] as $attachmentKey => $attacmenttypes) {
                $attacmentTypesId = isset($linkedColumn[$key]) ? array_column($linkedColumn[$key]['attacment_types'], 'id') : [];

                $status = false;
                if (in_array($attacmenttypes['id'], $attacmentTypesId)) {
                    $status  = true;
                } else {
                    $typeColumns[$key]['attacment_types'][$attachmentKey]['is_checked'] = false;
                }
                if (isset($typeColumns[$key])) {
                    $typeColumns[$key]['attacment_types'][$attachmentKey]['is_checked'] = $status;
                }
            }
        }

        return $this->successResponse($typeColumns, "success");
    }

    /**
     * Fetch the User Profile Value
     *
     * @param $linked_column
     * @param $userid
     */
    public function userProfileValue($linked_column, $userid, $type)
    {

        $selectQuery = null;
        $AES_KEY        = $this->key;
        if ($type == 'facility') {
            $columnNameWithTypes = DB::select('describe cm_user_ddpracticelocationinfo');
            $usersColumn = array_column($columnNameWithTypes, 'Field');
            // dd(      $columnNameWithTypes);

        } else if ($type == 'practice') {

            $columnNameWithTypes1 = DB::select("SELECT COLUMN_NAME as Field,DATA_TYPE as Type,TABLE_NAME as Table_name   FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'cm_user_baf_practiseinfo' AND COLUMN_NAME in ('practice_name', 'doing_business_as')");
            $columnNameWithTypes2 = DB::select("SELECT COLUMN_NAME as Field,DATA_TYPE as Type,TABLE_NAME as Table_name    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'cm_user_dd_businessinformation' AND COLUMN_NAME in ('facility_npi', 'facility_tax_id','business_established_date','taxonomy_code')");
            $columnNameWithTypes3 = DB::select("SELECT COLUMN_NAME as Field,DATA_TYPE as Type,TABLE_NAME as Table_name    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'cm_user_baf_contactinfo' AND COLUMN_NAME in ('contact_person_fax', 'contact_person_phone','contact_person_email','contact_person_designation','contact_person_name')");
            $columnNameWithTypes = [...$columnNameWithTypes1, ...$columnNameWithTypes2, ...$columnNameWithTypes3];
            $usersColumn = array_column($columnNameWithTypes, 'Field');
        } else {
            $columnNameWithTypes = DB::select('describe cm_users');
            $usersColumn = array_column($columnNameWithTypes, 'Field');
        }

        $inputvalue = null;
        $tableName = '';
        if ($linked_column == 'full_name') {
            $selectQuery =  DB::raw("CONCAT(COALESCE(first_name,''), ' ',COALESCE(last_name,'')) AS value");
        } else {
            if (in_array($linked_column, $usersColumn)) {

                $searchKey = array_search($linked_column, $usersColumn);
                $filedType = null;
                if ($searchKey !== false && $searchKey !== null) {
                    $filedType = $columnNameWithTypes[$searchKey]->Type;
                    if ($type == 'practice') {
                        $tableName = $columnNameWithTypes[$searchKey]->Table_name;
                    }
                }

                if (in_array($filedType, ['blob', 'varbinary(255)']) || strpos($filedType, 'varbinary') !== false) {
                    $selectQuery =   DB::raw("AES_DECRYPT($linked_column, '$AES_KEY') as value");
                } else {
                    $selectQuery = DB::raw("$linked_column as value");
                }
            }
        }



        if ($selectQuery != null) {
            if ($type == 'facility') {
                $userData = PracticeLocation::select($selectQuery)->where('user_id', $userid)->value('value');
            } else if ($type == 'practice') {
                $tableName = str_replace('cm_', '', $tableName);
                $userData = DB::table($tableName)->select($selectQuery)->where('user_id', $userid)->value('value');
            } else {
                $userData = DB::table('users')->select($selectQuery)->where('id', $userid)->value('value');
            }

            if ($userData != null) {
                $inputvalue =  $userData;
                if ($linked_column == 'gender' && in_array($userData, ["1", "2", "3"])) {
                    if ($inputvalue == 1) {
                        $inputvalue = 'Male';
                    } else if ($inputvalue == 2) {
                        $inputvalue = 'Female';
                    } else {
                        $inputvalue = 'Other';
                    }
                }
            }
        } else {
            if (strpos($linked_column, 'license_number') !== false) {

                $liceneseNumsArr['driver_license_number'] = 23;
                $liceneseNumsArr['state_license_number'] = 33;
                $liceneseNumsArr['dea_license_number'] = 36;
                $liceneseNumsArr['caqh_license_number'] = 81;
                $liceneTypeId = $liceneseNumsArr[$linked_column] ?? 0;
                if ($liceneTypeId == 81) {
                    $fetchCAQHInfo = $this->fetchCAQHInfo($userid)->CAQH_id ?? null;
                    $inputvalue = $fetchCAQHInfo;
                } else {
                    $license = License::where('user_id', $userid)->where('type_id', $liceneTypeId)->first();
                    $licenseNumber = $license->license_no ?? null;
                    if (!is_null($licenseNumber) && Str::contains($licenseNumber, "_")) {
                        $licenseParts = explode("_", $licenseNumber);
                        if (is_array($licenseParts))
                            $inputvalue = $licenseParts[1];
                    } else {
                        $inputvalue = $licenseNumber;
                    }
                }
            }
        }
        if ($linked_column == 'professional_type_id') {
            $professionalType = DB::table('professional_types')->where('id', $inputvalue)->value('name');
            $inputvalue = $professionalType;
        } else if ($linked_column == 'professional_group_id') {
            $professional_groups = DB::table('professional_groups')->where('id', $inputvalue)->value('name');
            $inputvalue = $professional_groups;
        } else if ($linked_column == 'citizenship_id') {
            $citizenships = DB::table('citizenships')->where('id', $inputvalue)->value('name');
            $inputvalue = $citizenships;
        } else if (in_array($linked_column, ['dob', 'business_established_date'])) {
            $inputvalue  = date('d-m-Y', strtotime($inputvalue));
        }


        return $inputvalue;
    }

    /**
     * Fetch the Attachment Value
     *
     * @param $linked_column
     * @param $userid
     */
    public function attachmentValue($linked_column, $userid)
    {
        $expirationDate = null;
        if ($linked_column == 41) {
            $license = InsuranceCoverage::where('user_id', $userid)->where('type_id', $linked_column)->where('is_current_version', 1)->first();
            $entityId = 'coverage';
            $expirationDate = $license->expiration_date ?? null;
        } elseif ($linked_column == 32) {
            $entityId = 'hospital';
            $license = HospitalAffliation::where('user_id', $userid)->where('type_id', $linked_column)->where('is_current_version', 1)->first();
        } elseif (in_array($linked_column, [30, 31, 26])) {
            $entityId = 'education';
            $license = Education::where('user_id', $userid)->where('type_id', $linked_column)->where('is_current_version', 1)->first();
        } else {
            $entityId = 'license_id';
            $license = License::where('type_id', $linked_column)->where('is_current_version', 1)->where('user_id', $userid)->first();
            $expirationDate = $license->exp_date ?? null;
        }

        // dd($expiringSoon);
        $inputvalue = null;
        $is_expired = false;
        $expiring_soon = null;
        if ($license != null) {
            $attachments = Attachments::where("entities", "=", $entityId)->where("entity_id", $license->id)->first();
            if ($attachments != null) {
                if ($entityId == 'coverage') {
                    $nestedFolders = "providersEnc/coverage";
                } else {
                    $nestedFolders = "providersEnc/licenses";
                }
                $inputvalue = $nestedFolders . "/" . $license->user_id . "/" . $attachments->field_value;
            }

            $expiringSoon = $this->expiringSoonDocs($userid, $linked_column, $license->id);
            $expiredDocs = $this->licenseTypesExpired($userid, $linked_column, $license->id);
            // dd($expiredDocs, $expiringSoon);
            if ($expiredDocs->count() > 0) {
                $is_expired = true;
            } elseif ($expiringSoon->count() > 0) {
                $expiringSoon = $expiringSoon[0];
                $expiryDate = Carbon::createFromFormat('m/d/Y', $expiringSoon->exp_date);
                $currentDate = Carbon::now();
                $expiring_soon = $currentDate->diffInDays($expiryDate) . ' days Remaining';
            }
        }


        return ['file' => $inputvalue, 'expiry_date' => $expirationDate, 'expiring_soon' => $expiring_soon, 'is_expired' => $is_expired];
    }

    /**
     * get User Column And Attachment Types on the basis of types
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function userColumnAndAttachmentTypes($type)
    {

        $licenseType = [];
        $practiceColumn = [
            ['label' => 'NPI', 'value' => 'facility_npi'],
            ['label' => 'Practice Name / Legal Business Name', 'value' => 'practice_name'],
            ['label' => 'Doing Business As', 'value' => 'doing_business_as'],
            ['label' => 'Tax ID', 'value' => 'facility_tax_id'],
            ['label' => 'Business Established Date', 'value' => 'business_established_date'],
            ['label' => 'Taxonomy Code', 'value' => 'taxonomy_code'],
            ['label' => 'Contact Name', 'value' => 'contact_person_name'],
            ['label' => 'Contact Title', 'value' => 'contact_person_designation'],
            ['label' => 'Contact Phone Number', 'value' => 'contact_person_phone'],
            ['label' => 'Contact Fax Number', 'value' => 'contact_person_fax'],
            ['label' => 'Contact Email', 'value' => 'contact_person_email'],

        ];
        $facilityColumn = [
            ['label' => 'Facility Name', 'value' => 'practice_name'],
            ['label' => 'NPI', 'value' => 'npi'],
            ['label' => 'Contact Person Name', 'value' => 'contact_name'],
            ['label' => 'Contact Person Title', 'value' => 'contact_title'],
            ['label' => 'Contact Person Phone Number', 'value' => 'contact_phone'],
            ['label' => 'Contact Person Fax Number', 'value' => 'contact_fax'],
            ['label' => 'Contact Person Email', 'value' => 'contact_email'],
            ['label' => 'Street Address', 'value' => 'practise_address'],
            ['label' => 'City', 'value' => 'city'],
            ['label' => 'State', 'value' => 'state'],
            ['label' => 'Zip Code', 'value' => 'zip_five'],
            ['label' => 'County', 'value' => 'country'],
            ['label' => 'Phone Number', 'value' => 'phone'],
            ['label' => 'Fax Number', 'value' => 'fax'],
            ['label' => 'Email', 'value' => 'email'],
        ];
        $providerColumn = [
            ['label' => 'First Name', 'value' => 'first_name'],
            ['label' => 'Last Name', 'value' => 'last_name'],
            ['label' => 'Date Of Birth', 'value' => 'dob'],
            ['label' => 'SSN', 'value' => 'ssn'],
            ['label' => 'NPI', 'value' => 'facility_npi'],
            ['label' => 'Gender', 'value' => 'gender'],
            ['label' => 'Place Of Birth', 'value' => 'place_of_birth'],
            ['label' => 'Citizenship', 'value' => 'citizenship_id'],
            ['label' => 'Supervisor Physician', 'value' => 'supervisor_physician'],
            ['label' => 'Street Address', 'value' => 'address_line_one'],
            ['label' => 'City', 'value' => 'city'],
            ['label' => 'State', 'value' => 'state'],
            ['label' => 'Zip Code', 'value' => 'zip_five'],
            ['label' => 'County', 'value' => 'country'],
            ['label' => 'Phone Number Home', 'value' => 'phone'],
            ['label' => 'Phone Number Work', 'value' => 'work_phone'],
            ['label' => 'Email', 'value' => 'email'],
            ['label' => 'Primary Specialty', 'value' => 'primary_speciality'],
            ['label' => 'Secondary Specialty', 'value' => 'secondary_speciality'],
            ['label' => 'Professional Group', 'value' => 'professional_group_id'],
            ['label' => 'Professional Type', 'value' => 'professional_type_id'],
            ['label' => 'Driver License Number', 'value' => 'driver_license_number'],
            ['label' => 'State License Number', 'value' => 'state_license_number'],
            ['label' => 'DEA License Number', 'value' => 'dea_license_number'],
            ['label' => 'CAQH License Number', 'value' => 'caqh_license_number'],
        ];

        if ($type == 'facility') {
            $licenseType = LicenseTypes::select('id', 'name')->where('parent_type_id', '!=', 0)->whereIn('is_for', ['both', 'Practice'])->get()->toArray();
            return [
                'facility' => ['profile_column' => $facilityColumn, 'attacment_types' => $licenseType],
                'practice' => ['profile_column' => $practiceColumn, 'attacment_types' => []],
            ];
        } else if ($type == 'provider') {
            $licenseTypeProvider = LicenseTypes::select('id', 'name')->where('parent_type_id', '!=', 0)->whereIn('is_for', ['both', 'Provider'])->get()->toArray();
            $licenseTypePractice = LicenseTypes::select('id', 'name')->where('parent_type_id', '!=', 0)->whereIn('is_for', ['both', 'Practice'])->get()->toArray();

            return [
                'facility' => ['profile_column' => $facilityColumn, 'attacment_types' => $licenseTypePractice],
                'practice' => ['profile_column' => $practiceColumn, 'attacment_types' => []],
                'provider' => ['profile_column' => $providerColumn, 'attacment_types' => $licenseTypeProvider],
            ];
        }
    }

    /**
     * add Payer Linked Column
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addPayerLinkedColumn(Request $request)
    {


        $request->validate([
            'payer_id' => 'required',
            'type' => 'required|in:provider,facility',
        ]);

        $payer  = Payer::find($request->payer_id);
        if ($payer == null) {
            return $this->errorResponse([], "No Payer Found", 404);
        }
        $sessionUserId = $this->getSessionUserId($request);
        $typeColumns = $this->userColumnAndAttachmentTypes($request->type);

        // $userColumns = $typeColumns['userCol'];
        // $attahcmentColumns = $typeColumns['licenseType'];
        // $userColumnValues = array_column($userColumns, 'value');
        // $attachmentColumnValues = array_column($attahcmentColumns, 'id');
        // if ($request->has('linked_column') && $request->linked_column != null) {
        //     $linkedColumn = is_string($request->linked_column) ?  json_decode($request->linked_column) : $request->linked_column;
        //     $profileColumn = array_column($linkedColumn->profile_column, 'value');
        //     $attacmentsTypes = array_column($linkedColumn->attacments_types, 'id');
        //     foreach ($profileColumn as $key => $profileColumns) {
        //         if (!in_array($profileColumns, $userColumnValues)) {
        //             return $this->errorResponse([], "invalid '$profileColumns' Profile column for '$request->type' ", 500);
        //         }
        //     }
        //     foreach ($attacmentsTypes as $key => $attchemntType) {
        //         if (!in_array($attchemntType, $attachmentColumnValues)) {
        //             return $this->errorResponse([], "invalid '$attchemntType' Attachment Type for '$request->type' ", 500);
        //         }
        //     }
        // }

        PayerLinkedColumn::UpdateOrCreate([
            'payer_id' => $request->payer_id,
            'type' => $request->type,
        ], [
            'payer_id' => $request->payer_id,
            'type' => $request->type,
            'linked_column' => $request->has('linked_column') && $request->linked_column != null ? $request->linked_column : null,
            'created_by' => $sessionUserId,
        ]);

        return $this->successResponse([], "success");
    }

    public function payerUserMissingColumn(Request $request)
    {
        $request->validate([
            'payerid' => 'required',
            'providerid' => 'required',
            'type' => 'required|in:facility,provider'
        ]);

        $userid = $request->providerid;
        $messageMissingColumn = '';
        $payerLinkedColumn = PayerLinkedColumn::where('payer_id', $request->payerid)->where('type', $request->type)->first();
        if ($payerLinkedColumn != null && $payerLinkedColumn->linked_column != null) {
            $linkedColumn = json_decode($payerLinkedColumn->linked_column);
            $profileColumn = $linkedColumn->profile_column;
            $attacmentsTypes = $linkedColumn->attacments_types;
            foreach ($profileColumn as $key => $value) {
                $inputvalue =  $this->userProfileValue($value->value, $userid, $request->type);
                if ($inputvalue == null) {
                    $messageMissingColumn .= $value->label . ' is missing <br>';
                }
            }
            foreach ($attacmentsTypes as $key => $value) {
                $inputvalue =  $this->attachmentValue($value->id, $userid);
                if ($inputvalue == null) {
                    $messageMissingColumn .= $value->name . ' is missing <br>';
                }
            }
        }
        return $this->successResponse(['message' => $messageMissingColumn], "success");
    }

    /**
     * each license type expiring soon docs
     */
    public function expiringSoonDocs($userId, $typeId, $licenseId, $notifyBeforeExp = 30)
    {

        $soonExpiredLicenses = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            "user_licenses.issuing_state",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
            DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
            "license_types.name as type",
            DB::raw("'Expiring Soon' as validity")
        )

            ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id")
                    ->where("license_types.is_for_report", "=", 1);
            })


            ->where("user_licenses.is_delete", "=", 0)

            ->where("user_licenses.is_current_version", "=", 1)

            ->where("user_licenses.user_id", "=", $userId)

            ->where("user_licenses.type_id", "=", $typeId)
            ->where("user_licenses.id", "=", $licenseId)

            ->whereRaw("cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL notify_before_exp DAY)")

            ->orderBy("user_licenses.id", "DESC");


        $insuranceCoverage = InsuranceCoverage::select(
            "insurance_coverage.id",
            DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
            DB::raw("'-' as issuing_state"),
            DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
            DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
            DB::raw('cm_license_types.name as type'),
            DB::raw("'Expiring Soon' as validity")
        )
            ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
            ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
            ->where("insurance_coverage.user_id", $userId)
            ->where("insurance_coverage.type_id", $typeId)
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", "=", 0)
            ->where("insurance_coverage.id", "=", $licenseId)
            ->whereRaw("expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL notify_before_exp DAY)");

        $hospitalAffiliations = HospitalAffliation::select(
            "hospital_affiliations.id",
            "admitting_previleges as license_no",
            DB::raw('NULL as issuing_state'),
            DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
            DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
            DB::raw('"hospital_affiliations" as type'),
            DB::raw("'Expiring Soon' as validity")
        )
            ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
            ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
            ->where("hospital_affiliations.user_id", $userId)
            ->where("hospital_affiliations.type_id", $typeId)
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", "=", 0)
            ->where("hospital_affiliations.id", "=", $licenseId)
            ->whereRaw("end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)");

        $soonExpiredLicenses = $soonExpiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();

        return $soonExpiredLicenses;
    }

    /**
     * each license type expired docs
     */
    public function licenseTypesExpired($userId, $typeId, $licenseId)
    {

        $expiredLicenses = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            "user_licenses.issuing_state",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
            DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
            "license_types.name as type",
            DB::raw("'Expired' as validity")
        )
            ->leftJoin("users", "users.id", "=", "user_licenses.user_id")
            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id");
                //->where("license_types.is_for_report", "=", 1);
            })
            ->where("user_licenses.is_delete", "=", 0)
            ->where("user_licenses.is_current_version", "=", 1)
            ->where("user_licenses.user_id", "=", $userId)
            ->where("user_licenses.type_id", "=", $typeId)
            ->where("user_licenses.id", "=", $licenseId)
            ->whereRaw("cm_user_licenses.exp_date < CURDATE()")
            ->orderBy("user_licenses.id", "DESC");

        // ->get();

        $insuranceCoverage = InsuranceCoverage::select(
            "insurance_coverage.id",
            DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
            DB::raw("'-' as issuing_state"),
            DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
            DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
            DB::raw('cm_license_types.name as type'),
            DB::raw("'Expired' as validity")
        )
            ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
            ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
            ->where("insurance_coverage.user_id", $userId)
            ->where("insurance_coverage.type_id", $typeId)
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", 0)
            ->where("insurance_coverage.id", "=", $licenseId)
            ->whereRaw("expiration_date < CURDATE()");

        $hospitalAffiliations = HospitalAffliation::select(
            "hospital_affiliations.id",
            "admitting_previleges as license_no",
            DB::raw('NULL as issuing_state'),
            DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
            DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
            DB::raw('"hospital_affiliations" as type'),
            DB::raw("'Expired' as validity")
        )
            ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
            ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
            ->where("hospital_affiliations.user_id", $userId)
            ->where("hospital_affiliations.type_id", $typeId)
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", 0)
            //->where("license_types.is_for_report", 1)
            ->where("hospital_affiliations.id", "=", $licenseId)
            ->whereRaw("end_date < CURDATE()");

        $expiredLicensesRes = $expiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();

        return $expiredLicensesRes;
    }

    /**
     * fetch the CAQH information
     *
     * @param $providerId
     */
    public function fetchCAQHInfo($providerId)
    {

        $portalType = $this->fetchData("portal_types", ["name" => "CAQH"], 1, []);

        $nppesPortal = DB::table("portals")->select("user_name as username", "password", "identifier as CAQH_id")
            ->where("type_id", "=", $portalType->id)
            ->where("user_id", "=", $providerId)
            ->first();
        if (is_object($nppesPortal) && isset($nppesPortal->password) && strlen($nppesPortal->password) > 5)
            $nppesPortal->password = decrypt($nppesPortal->password);

        return $nppesPortal;
    }
}
