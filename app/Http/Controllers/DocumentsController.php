<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\EditImage;
use App\Http\Traits\UserAccountActivityLog;
use PDF;
use DB;

use Mail;
use App\Models\Attachments;
use Carbon\Carbon; // This is a date library for PHP.
use App\Models\License;
use App\Models\InsuranceCoverage;
use App\Models\HospitalAffliation;
use App\Models\LicenseTypes as licensetypes;
use App\Models\Documents;
use App\Models\EmailTemplate;
use App\Models\Education;
use Illuminate\Support\Facades\Http;

class DocumentsController extends Controller
{
    use ApiResponseHandler, Utility, EditImage, UserAccountActivityLog;
    //

    /**
     *fetch the user license types
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     **/
    function getUserLicenseTypes(Request $request)
    {

        $userId = $request->user_id;

        $hasMultiRole = $this->userHasMultiRoles($userId);

        // $this->printR($hasMultiRole,true);
        $roleId = 0;
        if (count($hasMultiRole) > 1) {
            foreach ($hasMultiRole as $eachRole) {
                if ($eachRole->role_id == 4 || $eachRole->role_id == 10) {
                    $roleId =  $eachRole->role_id;
                    break;
                } elseif ($eachRole->role_id != 4 && $eachRole->role_id != 10) {
                    $roleId =  $eachRole->role_id;
                    break;
                }
            }
        } else {

            $role = $this->getUserRole($userId);

            $roleId = is_object($role) ? $role->role_id : 0;
        }



        $rolesArr = ['3' => "Practice", "9" => "Practice", "4" => "Provider", "10" => "Provider"]; //role name

        $isFor = $rolesArr[$roleId];

        if ($request->has('search') && $request->get('search') != '') {
            $expendStructure = [];
            $search = $request->get('search');
            $searchData = licensetypes::select(
                'parent_type_id as id',
                DB::raw('(SELECT name FROM cm_license_types as lt WHERE lt.id = cm_license_types.parent_type_id) as name'),
                DB::raw('1 as is_expandable')
            )
                ->where('name', 'LIKE', '%' . $search . '%')

                ->where('parent_type_id', '<>', '0')

                ->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

                ->groupBy('parent_type_id')

                ->orderBy('name')

                ->get();

            // $this->printR($searchData,true);
            $licensesTypesExpirations = [];
            if (count($searchData) > 0) {
                foreach ($searchData as $key => $licenseType) {
                    // $this->printR($licenseType,true);
                    $childLicenseTypes = licensetypes::where('parent_type_id', '=', $licenseType->id)

                        ->where('name', 'LIKE', '%' . $search . '%')

                        ->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

                        ->select("id", "parent_type_id", "name", 'is_attachement_required')

                        ->orderBy('sort_by', 'ASC')

                        ->get();

                    $expired = 0;
                    $missingDocs = 0;
                    $hasReminders = 0;
                    $hasWarning = 0;
                    $closeToExpiring = 0;
                    $totalEntities = 0;

                    $licensesTypesExpirations[$key] = [
                        "key" => (int)$key + 1,
                        "data" => [
                            "id" => $licenseType->id,
                            "type_id" => $licenseType->id,
                            "name" => $licenseType->name,
                            "note" => $licenseType->notes,
                            "missing_docs" => $missingDocs,
                            "has_reminders" => $hasReminders,
                            "has_warning" => $hasWarning,
                            "close_to_expiring" => $closeToExpiring,
                            "expired" => $expired,
                            "total_entities" => $totalEntities,

                        ]
                    ];
                    $expendStructure[(int)$key + 1] = $licenseType->is_expandable;

                    if (count($childLicenseTypes)) {
                        foreach ($childLicenseTypes as $childLicenseType) {

                            $childExpired = 0;
                            $childMissingDocs = 0;
                            $childHasReminders = 0;
                            $childHasWarning = 0;
                            $childCloseToExpiring = 0;
                            $childTotalEntities = 0;

                            $typeId = $childLicenseType->id;

                            $isMandatory = $childLicenseType->is_mandatory;

                            if ($childLicenseType->name == "Post Graduate Education") {
                                $childTotalEntities += $this->eduTypesCount($userId, 'post_graduate');
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Medical School") {
                                $childTotalEntities += $this->eduTypesCount($userId, 'medical_school');
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Other Graduate Level Education") {
                                $childTotalEntities += $this->eduTypesCount($userId, 'other_graduate');
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Hospital Affiliations") {
                                $childTotalEntities += $this->hospitalAffliationCount($userId);
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Malpractice coverage policy") {
                                $childTotalEntities += $this->malPracticeCoverageCount($userId);
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Shelters") {
                                $childTotalEntities += $this->sheltersCount($userId);
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if (
                                $childLicenseType->name != "Post Graduate Education"
                                && $childLicenseType->name != "Medical School"
                                && $childLicenseType->name != "Other Graduate Level Education"
                                && $childLicenseType->name != "Hospital Affiliations"
                                && $childLicenseType->name != "Malpractice coverage policy"
                                && $childLicenseType->name != "Shelters"
                            ) {

                                $licenseType = licensetypes::where('id', '=', $typeId)

                                    ->select('versioning_type')

                                    ->first();

                                $expirationStatus = $this->getLicenseTypeExpiration($userId, $typeId, $licenseType);
                                if (count($expirationStatus)) {
                                    foreach ($expirationStatus as $exp) {
                                        $expired            += $exp->expired;
                                        $closeToExpiring    += $exp->close_to_expiring;
                                        $hasWarning         += $exp->has_warning;
                                        $hasReminders       += $exp->has_reminders;

                                        $childExpired  += $exp->expired;

                                        $childHasReminders  += $exp->has_reminders;
                                        $childHasWarning += $exp->has_warning;
                                        $childCloseToExpiring += $exp->close_to_expiring;
                                    }
                                }

                                // $licenses = License::select(['user_licenses.id'])

                                // ->whereRaw("cm_user_licenses.document_version  = (SELECT MAX(document_version ) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.license_no = cm_user_licenses.license_no)")

                                // ->groupBy('user_licenses.license_no')

                                // ->orderBy("user_licenses.created_at")

                                // ->get();

                                $licenseType = LicenseTypes::where('id', '=', $typeId)

                                    ->select('versioning_type')

                                    ->first();

                                if (is_object($licenseType) && $licenseType->versioning_type == "number") {

                                    $licenses = License::select(['user_licenses.id'])

                                        ->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.license_no = cm_user_licenses.license_no)")

                                        ->where("user_licenses.is_delete", "=", 0)

                                        ->groupBy('user_licenses.license_no')

                                        ->orderBy("user_licenses.created_at")

                                        ->get();
                                } elseif (is_object($licenseType) && $licenseType->versioning_type == "name") {

                                    $licenses = License::select(['user_licenses.id'])

                                        ->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.name = cm_user_licenses.name)")

                                        ->where("user_licenses.is_delete", "=", 0)

                                        ->groupBy('user_licenses.name')

                                        ->orderBy("user_licenses.created_at")

                                        ->get();
                                }

                                $totalEntities += count($licenses);
                                $childTotalEntities += count($licenses);
                                if (count($licenses) == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }


                            $parentKey =  $licensesTypesExpirations[$key]['key'];

                            $licensesTypesExpirations[$key]['children'][] = [
                                "key" => $parentKey . '-' . $typeId,
                                "data" => [
                                    "id" => $typeId,
                                    "type_id" => $typeId,
                                    "name" => $childLicenseType->name,
                                    "parent_type_id" => $childLicenseType->parent_type_id,
                                    "missing_docs" => $childMissingDocs,
                                    "has_reminders" => $childHasReminders,
                                    "has_warning" => $childHasWarning,
                                    "close_to_expiring" => $childCloseToExpiring,
                                    "expired" => $childExpired,
                                    "total_entities" => $childTotalEntities,
                                    "validation" => [
                                        'is_attachement_required' => $childLicenseType->is_attachement_required
                                    ]
                                ]
                            ];
                            // $this->printR($licensesTypesExpirations,true);
                        }
                    }
                    $licensesTypesExpirations[$key]['data']['total_entities'] = $totalEntities;
                    $licensesTypesExpirations[$key]['data']['missing_docs'] = $missingDocs;
                    $licensesTypesExpirations[$key]['data']['has_reminders'] = $hasReminders;
                    $licensesTypesExpirations[$key]['data']['has_warning'] = $hasWarning;
                    $licensesTypesExpirations[$key]['data']['close_to_expiring'] = $closeToExpiring;
                    $licensesTypesExpirations[$key]['data']['expired'] = $expired;
                }
            }
            return $this->successResponse(["user_license_types" => $licensesTypesExpirations, 'expend' => $expendStructure], 'success');
        } else {

            $parentLicenseType = licensetypes::where('parent_type_id', '=', 0)

                ->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

                ->orderBy('sort_by', 'ASC')

                ->get();


            // $this->printR($parentLicenseType,true);
            $licensesTypesExpirations = [];
            if (count($parentLicenseType)) {
                foreach ($parentLicenseType as $key => $licenseType) {
                    // $this->printR($licenseType,true);
                    $childLicenseTypes = licensetypes::where('parent_type_id', '=', $licenseType->id)

                        ->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

                        ->select("id", "parent_type_id", "name", 'is_mandatory', 'is_attachement_required')

                        ->orderBy('sort_by', 'ASC')

                        ->get();

                    $expired = 0;
                    $missingDocs = 0;
                    $hasReminders = 0;
                    $hasWarning = 0;
                    $closeToExpiring = 0;
                    $totalEntities = 0;

                    $licensesTypesExpirations[$key] = [
                        "key" => (int)$key + 1,
                        "data" => [
                            "id" => $licenseType->id,
                            "type_id" => $licenseType->id,
                            "name" => $licenseType->name,
                            "missing_docs" => $missingDocs,
                            "has_reminders" => $hasReminders,
                            "has_warning" => $hasWarning,
                            "close_to_expiring" => $closeToExpiring,
                            "expired" => $expired,
                            "total_entities" => $totalEntities,

                        ]
                    ];

                    if (count($childLicenseTypes)) {
                        foreach ($childLicenseTypes as $childKey => $childLicenseType) {

                            $childExpired = 0;
                            $childMissingDocs = 0;
                            $childHasReminders = 0;
                            $childHasWarning = 0;
                            $childCloseToExpiring = 0;
                            $childTotalEntities = 0;

                            $typeId = $childLicenseType->id;


                            $isMandatory = $childLicenseType->is_mandatory;
                            if ($childLicenseType->name == "Post Graduate Education") {
                                $data = $this->eduTypesCount($userId, 'post_graduate');

                                $childTotalEntities += $data;
                                // echo $missingDocs;
                                // echo $childMissingDocs;
                                // exit;
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    // exit("in iff");
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Medical School") {
                                $childTotalEntities += $this->eduTypesCount($userId, 'medical_school');
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Other Graduate Level Education") {
                                $childTotalEntities += $this->eduTypesCount($userId, 'other_graduate');
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Hospital Affiliations") {
                                $childTotalEntities += $this->hospitalAffliationCount($userId);
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Malpractice coverage policy") {

                                $mlpCount = $this->malPracticeCoverageCount($userId);

                                $childTotalEntities += count($mlpCount);
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if ($childLicenseType->name == "Shelters") {
                                $childTotalEntities += $this->sheltersCount($userId);
                                if ($childTotalEntities == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }
                            if (
                                $childLicenseType->name != "Post Graduate Education"
                                && $childLicenseType->name != "Medical School"
                                && $childLicenseType->name != "Other Graduate Level Education"
                                && $childLicenseType->name != "Hospital Affiliations"
                                && $childLicenseType->name != "Malpractice coverage policy"
                                && $childLicenseType->name != "Shelters"
                            ) {
                                $licenseType = licensetypes::where('id', '=', $typeId)

                                    ->select('versioning_type')

                                    ->first();

                                $expirationStatus = $this->getLicenseTypeExpiration($userId, $typeId, $licenseType);
                                if (count($expirationStatus)) {
                                    foreach ($expirationStatus as $exp) {
                                        $expired            += $exp->expired;
                                        $closeToExpiring    += $exp->close_to_expiring;
                                        $hasWarning         += $exp->has_warning;
                                        $hasReminders       += $exp->has_reminders;

                                        $childExpired  += $exp->expired;

                                        $childHasReminders  += $exp->has_reminders;
                                        $childHasWarning += $exp->has_warning;
                                        $childCloseToExpiring += $exp->close_to_expiring;
                                    }
                                }
                                $licenseType = LicenseTypes::where('id', '=', $typeId)

                                    ->select('versioning_type')

                                    ->first();
                                if (is_object($licenseType) && $licenseType->versioning_type == "number") {

                                    $licenses = License::select(['user_licenses.id'])

                                        ->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.license_no = cm_user_licenses.license_no)")

                                        ->where("user_licenses.is_delete", "=", 0)

                                        ->groupBy('user_licenses.license_no')

                                        ->orderBy("user_licenses.created_at")

                                        ->get();
                                } elseif (is_object($licenseType) && $licenseType->versioning_type == "name") {

                                    $licenses = License::select(['user_licenses.id'])

                                        ->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.name = cm_user_licenses.name)")

                                        ->where("user_licenses.is_delete", "=", 0)

                                        ->groupBy('user_licenses.name')

                                        ->orderBy("user_licenses.created_at")

                                        ->get();
                                }

                                $totalEntities += count($licenses);
                                $childTotalEntities += count($licenses);
                                if (count($licenses) == 0 && $isMandatory == 1) {
                                    $missingDocs += 1;
                                    $childMissingDocs += 1;
                                }
                            }

                            $parentKey =  $licensesTypesExpirations[$key]['key'];

                            $licensesTypesExpirations[$key]['children'][] = [
                                "key" => $parentKey . '-' . $typeId,
                                "data" => [
                                    "id" => $typeId,
                                    "type_id" => $typeId,
                                    "name" => $childLicenseType->name,
                                    "parent_type_id" => $childLicenseType->parent_type_id,
                                    "missing_docs" => $childMissingDocs,
                                    "has_reminders" => $childHasReminders,
                                    "has_warning" => $childHasWarning,
                                    "close_to_expiring" => $childCloseToExpiring,
                                    "expired" => $childExpired,
                                    "total_entities" => $childTotalEntities,
                                    "validation" => [
                                        'is_attachement_required' => $childLicenseType->is_attachement_required
                                    ]
                                ]
                            ];
                            // $this->printR($licensesTypesExpirations,true);
                        }
                    }
                    $licensesTypesExpirations[$key]['data']['total_entities'] = $totalEntities;
                    $licensesTypesExpirations[$key]['data']['missing_docs'] = $missingDocs;
                    $licensesTypesExpirations[$key]['data']['has_reminders'] = $hasReminders;
                    $licensesTypesExpirations[$key]['data']['has_warning'] = $hasWarning;
                    $licensesTypesExpirations[$key]['data']['close_to_expiring'] = $closeToExpiring;
                    $licensesTypesExpirations[$key]['data']['expired'] = $expired;
                }
            }


            $miscellaneousExpirations = [];

            /**
             * get the Miscellaneous document info
             */
            $miscellaneous = $this->getMiscellaneousExpiration($userId);
            $miscellaneousExpirations['total_entities'] = count($miscellaneous);
            if (count($miscellaneous) > 0) {

                foreach ($miscellaneous as $exp) {

                    if (isset($miscellaneousExpirations['expired'])) {
                        $miscellaneousExpirations['expired'] += $exp->expired;
                    } else {
                        $miscellaneousExpirations['expired'] = $exp->expired;
                    }

                    if (isset($miscellaneousExpirations['close_to_expiring'])) {
                        $miscellaneousExpirations['close_to_expiring'] += $exp->close_to_expiring;
                    } else {
                        $miscellaneousExpirations['close_to_expiring'] = $exp->close_to_expiring;
                    }

                    if (isset($miscellaneousExpirations['has_warning'])) {
                        $miscellaneousExpirations['has_warning'] += $exp->has_warning;
                    } else {
                        $miscellaneousExpirations['has_warning'] = $exp->has_warning;
                    }

                    if (isset($miscellaneousExpirations['has_reminders'])) {
                        $miscellaneousExpirations['has_reminders'] += $exp->has_reminders;
                    } else {
                        $miscellaneousExpirations['has_reminders'] = $exp->has_reminders;
                    }
                    if (is_null($exp->field_value) || $exp->field_value == "" || $exp->field_value == "null") {
                        if (isset($miscellaneousExpirations['missing_docs'])) {
                            $miscellaneousExpirations['missing_docs'] += 1;
                        } else {
                            $miscellaneousExpirations['missing_docs'] = 1;
                        }
                    }
                }
            } else {
                $miscellaneousExpirations['missing_docs'] = 0;
                $miscellaneousExpirations['has_reminders'] = 0;
                $miscellaneousExpirations['has_warning'] = 0;
                $miscellaneousExpirations['close_to_expiring'] = 0;
                $miscellaneousExpirations['expired'] = 0;
                $miscellaneousExpirations['total_entities'] = 0;
            }

            $insuranceCoverageExpiration = [];

            /**
             * insurance converage info
             */
            $insuranceCoverage = $this->getInsuranceCoverageExpiration($userId);

            $insuranceCoverageExpiration['total_entities'] = count($insuranceCoverage);
            if (count($insuranceCoverage) > 0) {

                foreach ($insuranceCoverage as $exp) {

                    if (isset($insuranceCoverageExpiration['expired'])) {
                        $insuranceCoverageExpiration['expired'] += $exp->expired;
                    } else {
                        $insuranceCoverageExpiration['expired'] = $exp->expired;
                    }

                    if (isset($insuranceCoverageExpiration['close_to_expiring'])) {
                        $insuranceCoverageExpiration['close_to_expiring'] += $exp->close_to_expiring;
                    } else {
                        $insuranceCoverageExpiration['close_to_expiring'] = $exp->close_to_expiring;
                    }

                    if (isset($insuranceCoverageExpiration['has_warning'])) {
                        $insuranceCoverageExpiration['has_warning'] += $exp->has_warning;
                    } else {
                        $insuranceCoverageExpiration['has_warning'] = $exp->has_warning;
                    }

                    if (isset($insuranceCoverageExpiration['has_reminders'])) {
                        $insuranceCoverageExpiration['has_reminders'] += $exp->has_reminders;
                    } else {
                        $insuranceCoverageExpiration['has_reminders'] = $exp->has_reminders;
                    }
                }
            } else {
                $insuranceCoverageExpiration['missing_docs'] = 0;
                $insuranceCoverageExpiration['has_reminders'] = 0;
                $insuranceCoverageExpiration['has_warning'] = 0;
                $insuranceCoverageExpiration['close_to_expiring'] = 0;
                $insuranceCoverageExpiration['expired'] = 0;
                $insuranceCoverageExpiration['total_entities'] = 0;
            }
            //License::where(user_id)
            return $this->successResponse([
                "user_license_types" => $licensesTypesExpirations,
                "miscellaneous_expirations" => $miscellaneousExpirations, "insurance_coverage_expiration" => $insuranceCoverageExpiration
            ], "success");
        }
    }
    /**
     * each license type expired docs
     */
    public function licenseTypesExpired($userId,$typeId) {

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
            ->whereRaw("end_date < CURDATE()");

        $expiredLicensesRes = $expiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();

        return $expiredLicensesRes;
    }
    /**
     * all license expired docs
     */
    public function allLicenseTypesExpired($users,$isFor) {

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
            })

            ->where("user_licenses.is_delete", "=", 0)

            ->where("user_licenses.is_current_version", "=", 1)
            
            ->where('license_types.parent_type_id', '!=', 0)
            
            ->whereIn("user_licenses.user_id", $users)
            
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")

            //->where("user_licenses.type_id", "=", $typeId)

            ->whereRaw("cm_user_licenses.exp_date < CURDATE()")

            ->groupBy("user_licenses.user_id","user_licenses.type_id");

            //->orderBy("user_licenses.id", "DESC");

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
            ->whereIn("insurance_coverage.user_id", $users)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", 0)
            ->where('license_types.parent_type_id', '!=', 0)
            ->whereRaw("expiration_date < CURDATE()")
            ->groupBy("insurance_coverage.user_id","license_types.id");

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
            ->where("hospital_affiliations.user_id", $users)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", 0)
            ->where('license_types.parent_type_id', '!=', 0)
            //->where("license_types.is_for_report", 1)
            ->whereRaw("end_date < CURDATE()")
            ->groupBy("hospital_affiliations.user_id","license_types.id");

        $expiredLicensesRes = $expiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->count();

        return $expiredLicensesRes;
    }
    
    /**
     * all license expired docs
     */
    public function allProviderLicenseTypesExpiredDetails($users,$isFor) {

        $expiredLicenses = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            "user_licenses.issuing_state",
            "user_licenses.user_id as provider_id",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
            DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS provider_name"),
            "license_types.name as type",
            DB::raw("'Expired' as validity")
        )

            ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id");
            })

            ->where("user_licenses.is_delete", "=", 0)

            ->where("user_licenses.is_current_version", "=", 1)
            
            ->where('license_types.parent_type_id', '!=', 0)
            
            ->whereIn("user_licenses.user_id", $users)
            
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")

            //->where("user_licenses.type_id", "=", $typeId)

            ->whereRaw("cm_user_licenses.exp_date < CURDATE()")

            ->groupBy("user_licenses.user_id","user_licenses.type_id");
            //->orderBy("user_licenses.id", "DESC");

        // ->get();

        $insuranceCoverage = InsuranceCoverage::select(
            "insurance_coverage.id",
            DB::raw("REPLACE(policy_number, concat(user_id, '_'), '') as license_no"),
            DB::raw("'-' as issuing_state"),
            DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
            DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
            DB::raw('cm_license_types.name as type'),
            DB::raw("'Expired' as validity"),
            "users.id as provider_id"
        )
            ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
            ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
            ->whereIn("insurance_coverage.user_id", $users)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", 0)
            ->where('license_types.parent_type_id', '!=', 0)
            ->whereRaw("expiration_date < CURDATE()")
            ->groupBy("insurance_coverage.user_id","license_types.id");

        $hospitalAffiliations = HospitalAffliation::select(
            "hospital_affiliations.id",
            "admitting_previleges as license_no",
            DB::raw('NULL as issuing_state'),
            DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
            DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
            DB::raw('"hospital_affiliations" as type'),
            DB::raw("'Expired' as validity"),
            "hospital_affiliations.user_id as provider_id"
        )
            ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
            ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
            ->where("hospital_affiliations.user_id", $users)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", 0)
            ->where('license_types.parent_type_id', '!=', 0)
            //->where("license_types.is_for_report", 1)
            ->whereRaw("end_date < CURDATE()")
            ->groupBy("hospital_affiliations.user_id","license_types.id");

        $expiredLicensesRes = $expiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();

        return $expiredLicensesRes;
    }
    /**
     * all license expired docs
     */
    public function allFacilityLicenseTypesExpiredDetails($users,$isFor) {
        $key = env("AES_KEY");
        $expiredLicenses = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            "user_licenses.issuing_state",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
            DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
            "license_types.name as type",
            DB::raw("'Expired' as validity"),
            "user_licenses.user_id as facility_id"
        )

            ->leftJoin("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "user_licenses.user_id")

            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id");
            })

            ->where("user_licenses.is_delete", "=", 0)

            ->where("user_licenses.is_current_version", "=", 1)
            
            ->where('license_types.parent_type_id', '!=', 0)
            
            ->whereIn("user_licenses.user_id", $users)
            
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")

            //->where("user_licenses.type_id", "=", $typeId)

            ->whereRaw("cm_user_licenses.exp_date < CURDATE()")
            
            ->groupBy("user_licenses.user_id","license_types.id");

            //->orderBy("user_licenses.id", "DESC");

        // ->get();

        $insuranceCoverage = InsuranceCoverage::select(
            "insurance_coverage.id",
            DB::raw("REPLACE(cm_insurance_coverage.policy_number, concat(cm_insurance_coverage.user_id, '_'), '') as license_no"),
            DB::raw("'-' as issuing_state"),
            DB::raw("DATE_FORMAT(effective_date, '%m/%d/%y') as issue_date"),
            DB::raw("DATE_FORMAT(expiration_date, '%m/%d/%y') as exp_date"),
            DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
            DB::raw('cm_license_types.name as type'),
            DB::raw("'Expired' as validity"),
            "user_ddpracticelocationinfo.user_id as facility_id"
        )
            ->leftJoin("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "insurance_coverage.user_id")
            ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
            ->whereIn("insurance_coverage.user_id", $users)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", 0)
            ->where('license_types.parent_type_id', '!=', 0)
            ->whereRaw("expiration_date < CURDATE()")
            ->groupBy("insurance_coverage.user_id","license_types.id");

        $hospitalAffiliations = HospitalAffliation::select(
            "hospital_affiliations.id",
            "admitting_previleges as license_no",
            DB::raw('NULL as issuing_state'),
            DB::raw("DATE_FORMAT(start_date, '%m/%d/%Y') as issue_date"),
            DB::raw("DATE_FORMAT(end_date, '%m/%d/%Y') as exp_date"),
            DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
            DB::raw('"hospital_affiliations" as type'),
            DB::raw("'Expired' as validity"),
            "user_ddpracticelocationinfo.user_id as facility_id"
        )
            ->leftJoin("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "hospital_affiliations.user_id")
            ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
            ->where("hospital_affiliations.user_id", $users)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", 0)
            ->where('license_types.parent_type_id', '!=', 0)
            //->where("license_types.is_for_report", 1)
            ->whereRaw("end_date < CURDATE()")
            ->groupBy("hospital_affiliations.user_id","license_types.id");

        $expiredLicensesRes = $expiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();

        return $expiredLicensesRes;
    }
    /**
     * each license type expiring soon docs
     */
    public function expiringSoonDocs($userId,$typeId,$notifyBeforeExp=30) {

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

            ->join("license_types", function ($join)  {
                $join->on("license_types.id", "=", "user_licenses.type_id")
                    ->where("license_types.is_for_report", "=", 1);
            })

            
            ->where("user_licenses.is_delete", "=", 0)

            ->where("user_licenses.is_current_version", "=", 1)

            ->where("user_licenses.user_id", "=", $userId)

            ->where("user_licenses.type_id", "=", $typeId)

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
            ->whereRaw("end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)");

        $soonExpiredLicenses = $soonExpiredLicenses->union($insuranceCoverage)->union($hospitalAffiliations)->get();

        return $soonExpiredLicenses;
    }
    
     
    /**
     * expiring soon docs all
     * 
     */
    public function expiringSoonDocsAll($userIds,$isFor) {
        
        
        $notifyBeforeExp = 30;
        
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
                $join->on("license_types.id", "=", "user_licenses.type_id");
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("user_licenses.is_delete", "=", 0)
            ->where("user_licenses.is_current_version", "=", 1)
            ->whereIn("user_licenses.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereRaw("cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("user_licenses.user_id", "license_types.id");
        
        $insuranceCoverage = InsuranceCoverage::select(
                "insurance_coverage.id",
                DB::raw("REPLACE(cm_insurance_coverage.policy_number, concat(cm_insurance_coverage.user_id, '_'), '') as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("DATE_FORMAT(cm_insurance_coverage.effective_date, '%m/%d/%Y') as issue_date"),
                DB::raw("DATE_FORMAT(cm_insurance_coverage.expiration_date, '%m/%d/%Y') as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                DB::raw('cm_license_types.name as type'),
                DB::raw("'Expiring Soon' as validity")
            )
            ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
            ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
            ->whereIn("insurance_coverage.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", "=", 0)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->whereRaw("expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("insurance_coverage.user_id", "license_types.id");
        
        $hospitalAffiliations = HospitalAffliation::select(
                "hospital_affiliations.id",
                "hospital_affiliations.admitting_previleges as license_no",
                DB::raw('NULL as issuing_state'),
                DB::raw("DATE_FORMAT(cm_hospital_affiliations.start_date, '%m/%d/%Y') as issue_date"),
                DB::raw("DATE_FORMAT(cm_hospital_affiliations.end_date, '%m/%d/%Y') as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                DB::raw('"Hospital Affiliation" as type'),
                DB::raw("'Expiring Soon' as validity")
            )
            ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
            ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
            ->whereIn("hospital_affiliations.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", "=", 0)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->whereRaw("end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("hospital_affiliations.user_id", "license_types.id");
        
        $soonExpiredDocuments = $soonExpiredLicenses
            ->union($insuranceCoverage)
            ->union($hospitalAffiliations)
            ->count();
        
        return $soonExpiredDocuments;
        
    }
    /**
     * expiring soon docs providers
     * 
     */
    public function providerExpiringSoonDetail($userIds,$isFor) {
        
        
        $notifyBeforeExp = 30;
        
        $soonExpiredLicenses = License::select(
                "user_licenses.id",
                "user_licenses.license_no",
                "user_licenses.issuing_state",
                DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
                DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
                DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS provider_name"),
                "license_types.name as type",
                DB::raw("'Expiring Soon' as validity"),
                "user_licenses.user_id as provider_id"
            )
            ->leftJoin("users", "users.id", "=", "user_licenses.user_id")
            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id");
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("user_licenses.is_delete", "=", 0)
            ->where("user_licenses.is_current_version", "=", 1)
            ->whereIn("user_licenses.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereRaw("cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("user_licenses.user_id", "license_types.id");
        
        $insuranceCoverage = InsuranceCoverage::select(
                "insurance_coverage.id",
                DB::raw("REPLACE(cm_insurance_coverage.policy_number, concat(cm_insurance_coverage.user_id, '_'), '') as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("DATE_FORMAT(cm_insurance_coverage.effective_date, '%m/%d/%Y') as issue_date"),
                DB::raw("DATE_FORMAT(cm_insurance_coverage.expiration_date, '%m/%d/%Y') as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
                DB::raw('cm_license_types.name as type'),
                DB::raw("'Expiring Soon' as validity"),
                "users.id as provider_id"
            )
            ->leftJoin("users", "users.id", "=", "insurance_coverage.user_id")
            ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
            ->whereIn("insurance_coverage.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", "=", 0)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->whereRaw("expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("insurance_coverage.user_id", "license_types.id");
        
        $hospitalAffiliations = HospitalAffliation::select(
                "hospital_affiliations.id",
                "hospital_affiliations.admitting_previleges as license_no",
                DB::raw('NULL as issuing_state'),
                DB::raw("DATE_FORMAT(cm_hospital_affiliations.start_date, '%m/%d/%Y') as issue_date"),
                DB::raw("DATE_FORMAT(cm_hospital_affiliations.end_date, '%m/%d/%Y') as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
                DB::raw('"Hospital Affiliation" as type'),
                DB::raw("'Expiring Soon' as validity"),
                "users.id as provider_id"
            )
            ->leftJoin("users", "users.id", "=", "hospital_affiliations.user_id")
            ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
            ->whereIn("hospital_affiliations.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", "=", 0)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->whereRaw("end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("hospital_affiliations.user_id", "license_types.id");
        
        $soonExpiredDocuments = $soonExpiredLicenses
            ->union($insuranceCoverage)
            ->union($hospitalAffiliations)
            ->get();
        
        return $soonExpiredDocuments;
        
    }
     /**
     * expiring soon docs facility
     * 
     */
    public function facilityExpiringSoonDetail($userIds,$isFor) {
        
        
        $notifyBeforeExp = 30;
        
        $key = env("AES_KEY");

        $soonExpiredLicenses = License::select(
                "user_licenses.id",
                "user_licenses.license_no",
                "user_licenses.issuing_state",
                DB::raw("DATE_FORMAT(cm_user_licenses.issue_date, '%m/%d/%Y') AS  issue_date"),
                DB::raw("DATE_FORMAT(cm_user_licenses.exp_date, '%m/%d/%Y') AS  exp_date"),
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
                "license_types.name as type",
                DB::raw("'Expiring Soon' as validity"),
                "user_licenses.user_id as facility_id"
            )
             ->leftJoin("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "user_licenses.user_id")

            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id");
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where("user_licenses.is_delete", "=", 0)
            ->where("user_licenses.is_current_version", "=", 1)
            ->whereIn("user_licenses.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereRaw("cm_user_licenses.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("user_licenses.user_id", "license_types.id");
        
        $insuranceCoverage = InsuranceCoverage::select(
                "insurance_coverage.id",
                DB::raw("REPLACE(cm_insurance_coverage.policy_number, concat(cm_insurance_coverage.user_id, '_'), '') as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("DATE_FORMAT(cm_insurance_coverage.effective_date, '%m/%d/%Y') as issue_date"),
                DB::raw("DATE_FORMAT(cm_insurance_coverage.expiration_date, '%m/%d/%Y') as exp_date"),
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
                DB::raw('cm_license_types.name as type'),
                DB::raw("'Expiring Soon' as validity"),
                "insurance_coverage.user_id as facility_id"
            )
            ->leftJoin("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "insurance_coverage.user_id")
            ->leftJoin("license_types", "insurance_coverage.type_id", "=", "license_types.id")
            ->whereIn("insurance_coverage.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->where("insurance_coverage.is_current_version", 1)
            ->where("insurance_coverage.is_delete", "=", 0)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->whereRaw("expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("insurance_coverage.user_id", "license_types.id");
        
        $hospitalAffiliations = HospitalAffliation::select(
                "hospital_affiliations.id",
                "hospital_affiliations.admitting_previleges as license_no",
                DB::raw('NULL as issuing_state'),
                DB::raw("DATE_FORMAT(cm_hospital_affiliations.start_date, '%m/%d/%Y') as issue_date"),
                DB::raw("DATE_FORMAT(cm_hospital_affiliations.end_date, '%m/%d/%Y') as exp_date"),
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
                DB::raw('"Hospital Affiliation" as type'),
                DB::raw("'Expiring Soon' as validity"),
                "hospital_affiliations.user_id as facility_id"
            )
            ->leftJoin("user_ddpracticelocationinfo", "user_ddpracticelocationinfo.user_id", "=", "hospital_affiliations.user_id")
            ->leftJoin("license_types", "hospital_affiliations.type_id", "=", "license_types.id")
            ->whereIn("hospital_affiliations.user_id", $userIds)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->where("hospital_affiliations.is_current_version", 1)
            ->where("hospital_affiliations.is_delete", "=", 0)
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->whereRaw("end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $notifyBeforeExp DAY)")
            ->groupBy("hospital_affiliations.user_id", "license_types.id");
        
        $soonExpiredDocuments = $soonExpiredLicenses
            ->union($insuranceCoverage)
            ->union($hospitalAffiliations)
            ->get();
        
        return $soonExpiredDocuments;
        
    }
    /**
     * missing docs of license type
     * 
     */
    public function missingDocs($userId,$typeId) {

        $missingLicenseDocuments = LicenseTypes::select(
            DB::raw("'-' as id"),
            DB::raw("'-' as license_no"),
            DB::raw("'-' as issuing_state"),
            DB::raw("'-' as issue_date"),
            DB::raw("'-' as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
            DB::raw("cm_license_types.name as type"),
            DB::raw("'Document Missing' as validity"),
        )
            ->leftJoin('user_licenses', function ($join) {
                $join->on("user_licenses.type_id", "=", "license_types.id")
                    ->where("user_licenses.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) use ($userId) {
                $join->on('users.id', '=', 'user_licenses.user_id');
                    //->where("users.id", "=", $userId);
            })
            //->where('license_types.is_mandatory', "=", 1)
            //->where("license_types.is_for_report", "=", 1)
            //->whereIn("license_types.is_for", [$isFor, "Both"])
            //->whereNotIn("license_types.id", [30, 31, 26, 32, 41])
            ->where('user_licenses.type_id',$typeId)
            ->where('user_licenses.user_id',$userId)
            ->where('user_licenses.is_delete',0)
            ->groupBy("type");

        $missingInsuaranceDocuments = LicenseTypes::select(
            DB::raw("'-' as id"),
            DB::raw("'-' as license_no"),
            DB::raw("'-' as issuing_state"),
            DB::raw("'-' as issue_date"),
            DB::raw("'-' as exp_date"),
            DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
            DB::raw("cm_license_types.name as type"),
            DB::raw("'Document Missing' as validity"),
        )
            ->leftJoin('insurance_coverage', function ($join) use ($userId) {
                $join->on("insurance_coverage.type_id", "=", "license_types.id");
            })
            ->leftJoin('users', function ($join) use ($userId) {
                $join->on('users.id', '=', 'insurance_coverage.user_id');
                    //->where("users.id", "=", $userId);
            })
            //->where('license_types.is_mandatory', "=", 1)
            //->where("license_types.is_for_report", "=", 1)
            //->whereIn("license_types.is_for", [$isFor, "Both"])
            ->where("insurance_coverage.type_id",'=',$typeId)
            ->where("insurance_coverage.user_id",'=',$userId)
            ->where("insurance_coverage.is_delete",'=',0)
            ->groupBy("type");

            $missingEducationDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
            )
                ->leftJoin('education', function ($join) use ($userId) {
                    $join->on("education.type_id", "=", "license_types.id");
                })
                ->leftJoin('users', function ($join) use ($userId) {
                    $join->on('users.id', '=', 'education.user_id');
                        //->where("users.id", "=", $userId);
                })
                //->where('license_types.is_mandatory', "=", 1)
                //->where("license_types.is_for_report", "=", 1)
                //->whereIn("license_types.is_for", [$isFor, "Both"])
                ->where("education.type_id",'=',$typeId)
                ->where("education.user_id",'=',$userId)
                ->where("education.is_delete",'=',0)
                ->groupBy("type");

                $missingHospitalAffiliationDocuments = LicenseTypes::select(
                    DB::raw("'-' as id"),
                    DB::raw("'-' as license_no"),
                    DB::raw("'-' as issuing_state"),
                    DB::raw("'-' as issue_date"),
                    DB::raw("'-' as exp_date"),
                    DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                    DB::raw("cm_license_types.name as type"),
                    DB::raw("'Document Missing' as validity"),
                )
                    ->leftJoin('hospital_affiliations', function ($join) use ($userId) {
                        $join->on("hospital_affiliations.type_id", "=", "license_types.id");
                    })
                    ->leftJoin('users', function ($join) use ($userId) {
                        $join->on('users.id', '=', 'hospital_affiliations.user_id');
                            //->where("users.id", "=", $userId);
                    })
                    //->where('license_types.is_mandatory', "=", 1)
                    //->where("license_types.is_for_report", "=", 1)
                    //->whereIn("license_types.is_for", [$isFor, "Both"])
                    ->where("hospital_affiliations.type_id",'=',$typeId)
                    ->where("hospital_affiliations.user_id",'=',$userId)
                    ->where("hospital_affiliations.is_delete",'=',0)
                    ->groupBy("type");
            
            
        $missingLicenses = $missingLicenseDocuments->union($missingInsuaranceDocuments)
        
        ->union($missingEducationDocuments)
        
        ->union($missingHospitalAffiliationDocuments)

        ->get();
        
        return $missingLicenses;

    }
    /**
     * all missing documents
     * 
     * 
     */
    public function allMissingDocuments($userIds,$isFor) {

        

        $missingLicenseDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
            )
            ->leftJoin('user_licenses', function ($join) {
                $join->on("user_licenses.type_id", "=", "license_types.id")
                    ->where("user_licenses.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'user_licenses.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('user_licenses.id')
            ->groupBy("user_licenses.user_id","license_types.id");
        
        $missingInsuranceDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
            )
            ->leftJoin('insurance_coverage', function ($join) {
                $join->on("insurance_coverage.type_id", "=", "license_types.id")
                    ->where("insurance_coverage.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'insurance_coverage.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('insurance_coverage.id')
            ->groupBy("insurance_coverage.user_id","license_types.id");
        
        $missingEducationDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
            )
            ->leftJoin('education', function ($join) {
                $join->on("education.type_id", "=", "license_types.id")
                    ->where("education.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'education.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('education.id')
            ->groupBy("education.user_id","license_types.id");
        
        $missingHospitalAffiliationDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
            )
            ->leftJoin('hospital_affiliations', function ($join) {
                $join->on("hospital_affiliations.type_id", "=", "license_types.id")
                    ->where("hospital_affiliations.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'hospital_affiliations.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('hospital_affiliations.id')
            ->groupBy("hospital_affiliations.user_id","license_types.id");
        
        $missingDocuments = $missingLicenseDocuments
            ->union($missingInsuranceDocuments)
            ->union($missingEducationDocuments)
            ->union($missingHospitalAffiliationDocuments)
            ->count();

        return $missingDocuments;
        
    
    }
    /**
     * provider missing documents
     * 
     * 
     */
    public function providerMissingDocuments($userIds,$isFor) {

        

        $missingLicenseDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "user_licenses.user_id as provider_id"
            )
            ->leftJoin('user_licenses', function ($join) {
                $join->on("user_licenses.type_id", "=", "license_types.id")
                    ->where("user_licenses.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'user_licenses.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('user_licenses.id')
            ->groupBy("user_licenses.user_id","license_types.id");
        
        $missingInsuranceDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "insurance_coverage.user_id as provider_id"
            )
            ->leftJoin('insurance_coverage', function ($join) {
                $join->on("insurance_coverage.type_id", "=", "license_types.id")
                    ->where("insurance_coverage.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'insurance_coverage.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('insurance_coverage.id')
            ->groupBy("insurance_coverage.user_id","license_types.id");
        
        $missingEducationDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "education.user_id as provider_id"
            )
            ->leftJoin('education', function ($join) {
                $join->on("education.type_id", "=", "license_types.id")
                    ->where("education.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'education.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('education.id')
            ->groupBy("education.user_id","license_types.id");
        
        $missingHospitalAffiliationDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("CONCAT(cm_users.first_name, ' ', cm_users.last_name) AS provider_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "hospital_affiliations.user_id as provider_id"
            )
            ->leftJoin('hospital_affiliations', function ($join) {
                $join->on("hospital_affiliations.type_id", "=", "license_types.id")
                    ->where("hospital_affiliations.is_delete", "=", 0);
            })
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'hospital_affiliations.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('users.id', $userIds)
            ->whereNull('hospital_affiliations.id')
            ->groupBy("hospital_affiliations.user_id","license_types.id");
        
        $missingDocuments = $missingLicenseDocuments
            ->union($missingInsuranceDocuments)
            ->union($missingEducationDocuments)
            ->union($missingHospitalAffiliationDocuments)
            ->get();

        return $missingDocuments;
    }
    /**
     * facility missing documents
     * 
     * 
     */
    public function facilityMissingDocuments($userIds,$isFor) {

        $key = env("AES_KEY");
        
        $missingLicenseDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "user_licenses.user_id as facility_id"
            )
            ->leftJoin('user_licenses', function ($join) {
                $join->on("user_licenses.type_id", "=", "license_types.id")
                    ->where("user_licenses.is_delete", "=", 0);
            })
            ->leftJoin('user_ddpracticelocationinfo', function ($join) {
                $join->on('user_ddpracticelocationinfo.user_id', '=', 'user_licenses.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('user_ddpracticelocationinfo.user_id', $userIds)
            ->whereNull('user_licenses.id')
            ->groupBy("user_licenses.user_id","license_types.id");
        
        $missingInsuranceDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "insurance_coverage.user_id as facility_id"
            )
            ->leftJoin('insurance_coverage', function ($join) {
                $join->on("insurance_coverage.type_id", "=", "license_types.id")
                    ->where("insurance_coverage.is_delete", "=", 0);
            })
            ->leftJoin('user_ddpracticelocationinfo', function ($join) {
                $join->on('user_ddpracticelocationinfo.user_id', '=', 'insurance_coverage.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('user_ddpracticelocationinfo.user_id', $userIds)
            ->whereNull('insurance_coverage.id')
            ->groupBy("insurance_coverage.user_id","license_types.id");
        
        $missingEducationDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "education.user_id as facility_id"
            )
            ->leftJoin('education', function ($join) {
                $join->on("education.type_id", "=", "license_types.id")
                    ->where("education.is_delete", "=", 0);
            })
            ->leftJoin('user_ddpracticelocationinfo', function ($join) {
                $join->on('user_ddpracticelocationinfo.user_id', '=', 'education.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('user_ddpracticelocationinfo.user_id', $userIds)
            ->whereNull('education.id')
            ->groupBy("education.user_id","license_types.id");
        
        $missingHospitalAffiliationDocuments = LicenseTypes::select(
                DB::raw("'-' as id"),
                DB::raw("'-' as license_no"),
                DB::raw("'-' as issuing_state"),
                DB::raw("'-' as issue_date"),
                DB::raw("'-' as exp_date"),
                DB::raw("AES_DECRYPT(cm_user_ddpracticelocationinfo.practice_name, '$key') AS facility_name"),
                DB::raw("cm_license_types.name as type"),
                DB::raw("'Document Missing' as validity"),
                "user_ddpracticelocationinfo.user_id as facility_id"
            )
            ->leftJoin('hospital_affiliations', function ($join) {
                $join->on("hospital_affiliations.type_id", "=", "license_types.id")
                    ->where("hospital_affiliations.is_delete", "=", 0);
            })
            ->leftJoin('user_ddpracticelocationinfo', function ($join) {
                $join->on('user_ddpracticelocationinfo.user_id', '=', 'hospital_affiliations.user_id');
            })
            ->whereRaw("(cm_license_types.is_for = '$isFor' OR cm_license_types.is_for = 'Both')")
            ->where('license_types.is_mandatory', "=", 1)
            ->where('license_types.parent_type_id', '!=', 0)  // Check for subcategories
            ->whereIn('user_ddpracticelocationinfo.user_id', $userIds)
            ->whereNull('hospital_affiliations.id')
            ->groupBy("hospital_affiliations.user_id","license_types.id");
        
        $missingDocuments = $missingLicenseDocuments
            ->union($missingInsuranceDocuments)
            ->union($missingEducationDocuments)
            ->union($missingHospitalAffiliationDocuments)
            ->get();

        return $missingDocuments;
    }
    /**
     * get the credentials documents dashboard data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    function credentialsDashboard(Request $request)
    {
        ini_set('memory_limit', '-1'); // Set memory limit to 256MB
        $request->validate([
            "user_id" => "required",
        ]);

        $userId = $request->user_id;
        $hasMultiRole = $this->userHasMultiRoles($userId);

        // $this->printR($hasMultiRole,true);
        $roleId = 0;
        if (count($hasMultiRole) > 1) {
            foreach ($hasMultiRole as $eachRole) {
                if ($eachRole->role_id == 4 || $eachRole->role_id == 10) {
                    $roleId =  $eachRole->role_id;
                    break;
                } elseif ($eachRole->role_id != 4 && $eachRole->role_id != 10) {
                    $roleId =  $eachRole->role_id;
                    break;
                }
            }
        } else {

            $role = $this->getUserRole($userId);

            $roleId = is_object($role) ? $role->role_id : 0;
        }



        $rolesArr = ['3' => "Practice", "9" => "Practice", "4" => "Provider", "10" => "Provider"]; //role name

        $isFor = isset($rolesArr[$roleId]) ? $rolesArr[$roleId] : null;
        $isFor = isset($rolesArr[$roleId]) ? $rolesArr[$roleId] : null;
        
        $parentLicenseType = licensetypes::where('parent_type_id', '=', 0)

        ->orderBy('sort_by', 'ASC')

        ->get();
        
        $expiredLicensesArr =[];
        $expiringSoonLicensesArr =[];
        $missingLicensesArr =[];
        // $this->printR($parentLicenseType,true);
        foreach($parentLicenseType as $license) {
           
            $childLicenseTypes = licensetypes::where('parent_type_id', '=', $license->id)

            ->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

            ->select("id", "parent_type_id", "name",'is_mandatory')

            ->orderBy('sort_by', 'ASC')

            ->get();

            if (count($childLicenseTypes)) {
                //$this->printR($childLicenseTypes,true);
                foreach ($childLicenseTypes as $eachChild) {
                    $isMandatory = $eachChild->is_mandatory;
                   
                    $expiredDocs = $this->licenseTypesExpired($userId,$eachChild->id);
                    if(count($expiredDocs)) {
                        //$this->printR($expiredDocs,true);
                        array_push($expiredLicensesArr,$expiredDocs[0]);
                    }
                    $expiringSoon = $this->expiringSoonDocs($userId,$eachChild->id);
                    if(count($expiringSoon)) {
                        //$this->printR($expiredDocs,true);
                        array_push($expiringSoonLicensesArr,$expiringSoon[0]);
                    }
                    if($isMandatory == 1) {
                        $missingType = ["id" => '-',"license_no" => '-','issuing_state' => '-','issue_date' => '-','name' => '-','type' => $eachChild->name,'validity' => "Document Missing"];
                        $missingDocs = $this->missingDocs($userId,$eachChild->id);
                        if(count($missingDocs) == 0) {
                            array_push($missingLicensesArr,$missingType);
                        }
                    }
                }
            }
        }
        
        //$this->printR($missingLicensesArr,true);
        return $this->successResponse(["expiring_soon_docs" => $expiringSoonLicensesArr, 'expired_docs' => $expiredLicensesArr,'missing_docs' => $missingLicensesArr], "success");
      
    }
    function getCredentialsDashboard(Request $request)
    {
        $request->validate([
            "user_id" => "required",
        ]);

        $userId = $request->user_id;

        $parentLicenseType = licensetypes::where('parent_type_id', '=', 0)

            //->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

            ->orderBy('sort_by', 'ASC')

            ->get();

        $docsUpCommingExpirations = [];
        $docsExpired = [];
        $role = $this->getUserRole($userId);
        //$this->printR($role,true);
        $roleId = is_object($role) ? $role->role_id : 0;

        $rolesArr = ['3' => "Facility", "9" => "Practice", "4" => "Provider", "10" => "Provider"]; //role name

        $isFor = $rolesArr[$roleId];

        $docsOverAll = ["missing_docs" => 0, "soon_expire_docs" => 0, "expired_docs" => 0, "total_docs" => 0];
        $url = ""; //env("STORAGE_PATH");
        $nestedFolders = "providers/licenses";

        if (count($parentLicenseType)) {
            foreach ($parentLicenseType as $key => $licenseType) {
                // $this->printR($licenseType,true);
                $childLicenseTypes = licensetypes::where('parent_type_id', '=', $licenseType->id)

                    ->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

                    ->select("id", "parent_type_id", "name")

                    ->orderBy('sort_by', 'ASC')

                    ->get();

                if (count($childLicenseTypes)) {
                    foreach ($childLicenseTypes as $childLicenseType) {
                        if (count($childLicenseTypes)) {
                            foreach ($childLicenseTypes as $childLicenseType) {
                                $typeId = $childLicenseType->id;
                                $isMandatory = $childLicenseType->is_mandatory;
                                $expirationStatus = $this->getLicenseTypeExpiration($userId, $typeId);

                                if (count($expirationStatus)) {
                                    foreach ($expirationStatus as $exp) {
                                        if ($exp->expired) {

                                            $docsOverAll['expired_docs'] += 1;
                                            $docsOverAll['total_docs'] += 1;
                                            $expiryDate = date("M j, Y", strtotime($exp->exp_date));

                                            $expireDoc = Attachments::where('entities', '=', 'license_id')

                                                ->where('entity_id', '=', $exp->id)

                                                ->first(['field_value']);
                                            $validFile = is_object($expireDoc) ? $expireDoc->field_key : "";
                                            $expFile = $url . $nestedFolders . "/" . $userId . "/" . $validFile;
                                            array_push($docsExpired, ['exp_date' => $expiryDate, 'exp_file' => $expFile, 'file_name' => $validFile]);
                                        }
                                        if ($exp->close_to_expiring) {
                                            // $this->printR($expirationStatus);
                                            $expiryDate = date("M j, Y", strtotime($exp->exp_date));
                                            $docsOverAll['soon_expire_docs'] += 1;
                                            $docsOverAll['total_docs'] += 1;
                                            $expireDoc = Attachments::where('entities', '=', 'license_id')

                                                ->where('entity_id', '=', $exp->id)

                                                ->first(['field_value']);
                                            $validFile = is_object($expireDoc) ? $expireDoc->field_key : "";
                                            $expFile = $url . $nestedFolders . "/" . $userId . "/" . $validFile;
                                            $docsUpCommingExpirations[$typeId] = ['exp_date' => $expiryDate, 'exp_file' => $expFile, 'file_name' => $validFile];
                                            // array_push($docsUpCommingExpirations, ['exp_date' => $expiryDate, 'exp_file' => $expFile, 'file_name' => $validFile]);
                                        }
                                    }
                                } else {
                                    if ($isMandatory == 1) {
                                       
                                        $docsOverAll['missing_docs'] += 1;
                                        $docsOverAll['total_docs'] += 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->successResponse(["upcomming_expiration" => $docsUpCommingExpirations, 'expired_docs' => $docsExpired, 'docs_status' => $docsOverAll], "success");
    }
    /**
     * delete the attachment
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteAttachment($id, Request $request)
    {

        $sessionUserName = $this->getSessionUserName($request);

        $sessionUserId = $this->getSessionUserId($request);

        $attachmentData = Attachments::where("id", "=", $id)->first();
        //delete the attachment from the storage
        if (is_object($attachmentData)) {
            $this->deleteFile("providersEnc/" . $attachmentData->entity_id . "/" . $attachmentData->field_value);
        }
        $delMsg = $this->delDataLogMsg($sessionUserName, "Miscellaneous");
        //handle the user activity
        $this->handleUserActivity(
            $attachmentData->entity_id,
            $sessionUserId,
            "Miscellaneous",
            "Delete",
            $delMsg,
            NULL,
            $this->timeStamp()
        );
        $isDel = Attachments::where("id", "=", $id)->delete();

        return $this->successResponse(["is_delete" => $isDel, "id" => $id], "success");
    }
    /**
     * upload the attachments of the documents
     * 
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadMiscellaneousAttachments(Request $request)
    {
        $request->validate([
            "document_name" => "required",
            "entity_id" => "required",
            "entities" => "required",
            "created_by" => "required"
        ]);
        $fileStatus = NULL;
        // $expirationDate = $request->has('exp_date') ? $request->get('exp_date') : NULL;

        // $expirationAlert = $request->has('remind_before_days') && $request->remind_before_days > 0 ? $request->get('remind_before_days') : 30;

        $sessionUserName = $this->getSessionUserName($request, $request->created_by);

        $notes = $request->has('notes') ? $request->get('notes') : NULL;
        $aid = 0;
        if ($request->file("file") != null && $request->file("file") != "undefined") {
            $file = $request->file("file");
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));
            $file = $request->file("file");

            $this->uploadMyFile($fileName, $file, "providers/" . $request->entity_id);
            $destFolder = "providersEnc/" . $request->entity_id;


            $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);

            $expirationDate = $request->has('exp_date') ? $request->get('exp_date') : NULL;

            $expirationAlert = $request->has('remind_before_days') ? $request->get('remind_before_days') : 30;

            $aid = 0;
            if (isset($fileRes['file_name'])) {
                $addFileData = [
                    "entities"     => $request->entities,
                    "entity_id"     => $request->entity_id,
                    "field_key"     => $request->document_name,
                    "field_value"   => $fileRes['file_name'],
                    "created_by"   => $request->created_by,
                    "exp_date"      => $expirationDate,
                    "notify_before_exp" => $expirationAlert,
                    "is_current_version" => $request->is_current_version,
                    "note"  => $notes,
                    "created_at" => $this->timeStamp()
                ];
                $aid = $this->addData("attachments", $addFileData, 0);
                // $addMap = ["user_id" => $request->entity_id,"attachment_id" => $aid];
                // $this->addData("user_attachment_map",$addMap);
            }
            $msg = $this->addNewDataLogMsg($sessionUserName, "Miscellaneous");
            //handle the user activity
            $this->handleUserActivity(
                $request->entity_id,
                $request->created_by,
                "Miscellaneous",
                "Add",
                $msg,
                $this->timeStamp(),
                NULL
            );
        }
        return $this->successResponse(["added" => true, "id" => $aid, "file_status" => $fileStatus], "success");
    }
    /**
     * fetch miscellaneous specific documet
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function fetchMiscellaneousDcoument($id, Request $request)
    {

        $document = Attachments::where("id", $id)->first();

        $url = env("STORAGE_PATH");

        $nestedFolders = "providersEnc";

        $document->file_url = $nestedFolders . "/" . $document->entity_id . "/" . $document->field_value;

        $document->exp_date = date('m/d/Y', strtotime($document->exp_date));

        $userName = $this->getUserNameById($document->created_by);

        $document->created_by = $userName;

        return $this->successResponse(["document" => $document, "id" => $id], "success");
    }
    /**
     * update  the miscellaneous documents data
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function updateMiscellaneousDcoument($id, Request $request)
    {

        $request->validate([
            "document_name" => "required",
            "entity_id" => "required",
            "entities" => "required",
            "created_by" => "required"
        ]);
        $fileStatus = NULL;
        $expirationDate = $request->has('exp_date') ? $request->get('exp_date') : NULL;

        $expirationAlert = $request->has('remind_before_days') ? $request->get('remind_before_days') : 30;

        $notes = $request->has('notes') ? $request->get('notes') : NULL;

        $updateFileData = [
            "entities"     => $request->entities,
            "entity_id"     => $request->entity_id,
            "field_key"     => $request->document_name,
            "created_by"    => $request->created_by,
            "exp_date"      => $expirationDate,
            "notify_before_exp" => $expirationAlert,
            "note"  => $notes,
            "is_current_version" => $request->is_current_version,
            "updated_at" => $this->timeStamp()
        ];
        $sessionUserName = $this->getSessionUserName($request, $request->created_by);

        $sessionUserId = $this->getSessionUserId($request, $request->created_by);

        $attachmentData = Attachments::where("id", "=", $id)->first();
        $fileArr = $this->stdToArray($attachmentData);
        $logMsg = "";
        if ($request->file("file") != null && $request->file("file") != "undefined" && $request->hasFile("file")) {

            //delete the attachment from the storage
            if (is_object($attachmentData)) {

                $logMsg .= $this->makeTheLogMsg($sessionUserName, $updateFileData, $fileArr);
                $this->deleteFile("providersEnc/" . $attachmentData->entity_id . "/" . $attachmentData->field_value);
            }
            // $file = $request->file("file");
            //$nameMe = uniqid();
            $file = $request->file("file");
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));
            $this->uploadMyFile($fileName, $file, "providers/" . $request->entity_id);
            $destFolder = "providersEnc/" . $request->entity_id;


            $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
            if (isset($fileRes['file_name']))
                $updateFileData["field_value"] = $fileRes['file_name'];
        } else {
            $logMsg .= $this->makeTheLogMsg($sessionUserName, $updateFileData, $fileArr);
        }
        //handle the user activity
        $this->handleUserActivity(
            $request->entity_id,
            $request->created_by,
            "Miscellaneous",
            "Update",
            $logMsg,
            NULL,
            $this->timeStamp()
        );
        $isUpdate = Attachments::where('id', $id)->update($updateFileData);

        return $this->successResponse(["updated" => $isUpdate, "id" => $id, "file_status" => $fileStatus], "success");
    }
    /**
     * Add CLIA record
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     * 
     */
    function addUserClia(Request $request)
    {

        // if($request->hasFile('file')) 
        {

            $userId = $request->user_id;

            $reqNumber = $userId . "_" . $request->number;

            $typeId = $request->type_id;

            $type = $request->type;

            $fileStatus = NULL;

            $license = License::select('document_version')

                ->where('user_id', '=', $userId)

                ->where('type_id', '=', $typeId)

                ->where('license_no', '=', $reqNumber)

                ->orderBy("id", "DESC")

                ->first();

            $version = is_object($license) ? (int)$license->document_version + 1 : 1;


            $addLicenseData["user_id"]      = $request->user_id;
            $addLicenseData["note"]      = $request->notes;
            $addLicenseData["license_no"]   = $reqNumber;
            $addLicenseData["issue_date"]   =  $request->issue_date;
            $addLicenseData["exp_date"]     =  $request->exp_date;
            $addLicenseData["issuing_state"] = NULL;
            $addLicenseData["type_id"]      = $typeId;
            $addLicenseData["created_by"]   = $request->created_by;
            $addLicenseData["notify_before_exp"]   = $request->notify_before_exp;
            $addLicenseData["is_current_version"]   = $request->is_current_version;
            $addLicenseData["status"]       = 0;
            $addLicenseData["currently_practicing"]       = 0;
            $addLicenseData["created_at"]   = $this->timeStamp();
            $addLicenseData["document_version"]       = $version;

            $id = License::insertGetId($addLicenseData);

            if ($id) {
                License::where("license_no", $reqNumber)
                    ->whereNot("id", $id)
                    ->update(["is_current_version" => 0]);
            }
            if ($request->hasFile('file')) {

                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);

                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $id,
                        "field_key"     => "license_file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->notes
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }

            return $this->successResponse(["is_add" => $id, "file_status" => $fileStatus], "success");
        }
    }
    /**
     * Update CLIA record
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     * 
     */
    function updateUserClia($id, Request $request)
    {

        //if($request->hasFile('file')) 
        {

            $userId = $request->user_id;

            $reqNumber = $userId . "_" . $request->number;

            $typeId = $request->type_id;

            $type = $request->type;

            $fileStatus = NULL;

            $license = License::select('document_version')

                ->where('user_id', '=', $userId)

                ->where('type_id', '=', $typeId)

                ->where('license_no', '=', $reqNumber)

                ->orderBy("id", "DESC")

                ->first();

            $version = is_object($license) ? (int)$license->document_version + 1 : 1;


            $addLicenseData["user_id"]      = $request->user_id;
            $addLicenseData["note"]      = $request->notes;
            $addLicenseData["license_no"]   = $reqNumber;
            $addLicenseData["issue_date"]   =  $request->issue_date;
            $addLicenseData["exp_date"]     =  $request->exp_date;
            $addLicenseData["issuing_state"] = NULL;
            $addLicenseData["type_id"]      = $typeId;
            $addLicenseData["created_by"]   = $request->created_by;
            $addLicenseData["notify_before_exp"]   = $request->notify_before_exp;
            $addLicenseData["is_current_version"] = $request->is_current_version;
            $addLicenseData["status"]       = 0;
            $addLicenseData["currently_practicing"]       = 0;
            $addLicenseData["created_at"]   = $this->timeStamp();
            $addLicenseData["document_version"]       = $version;

            $id = License::insertGetId($addLicenseData);

            if ($id) {
                License::where("license_no", $reqNumber)
                    ->whereNot("id", $id)
                    ->update(["is_current_version" => 0]);
            }
            if ($request->hasFile('file')) {
                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $id,
                        "field_key"     => "license_file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->notes
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }

            return $this->successResponse(["is_update" => $id, "file_status" => $fileStatus], "success");
        }
    }
    /**
     * add the voided / bank data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function addBankVoided(Request $request)
    {

        // if($request->hasFile('file')) 
        {

            $userId = $request->user_id;

            $acNumber = $userId . "_" . $request->acc_number;

            $typeId = $request->type_id;

            $type = $request->type;

            $fileStatus = NULL;

            $license = License::select('document_version')

                ->where('user_id', '=', $userId)

                ->where('type_id', '=', $typeId)

                ->where('license_no', '=', $acNumber)

                ->orderBy("id", "DESC")

                ->first();

            $version = is_object($license) ? (int)$license->document_version + 1 : 1;

            $addLicenseData["user_id"]          = $request->user_id;
            $addLicenseData["note"]          = $request->notes;
            $addLicenseData["license_no"]       = $acNumber;
            $addLicenseData["type_id"]          = $typeId;
            $addLicenseData["created_by"]       = $request->created_by;
            $addLicenseData["account_name"]     = $request->acc_name;
            $addLicenseData["routing_number"]   = $request->routing_number;
            $addLicenseData["account_type"]     = $request->acc_type;
            $addLicenseData["created_at"]       = $this->timeStamp();
            $addLicenseData["document_version"] = $version;
            $addLicenseData["contact_person"]   = $request->contact_person;
            $addLicenseData["is_current_version"] = $request->is_current_version;
            $addLicenseData["phone"]            = $request->phone;
            $addLicenseData["email"]            = $request->email;
            $addLicenseData["bank_name"]        = $request->bank_name;


            $id = License::insertGetId($addLicenseData);
            if ($request->hasFile('file')) {
                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $id,
                        "field_key"     => "license_file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->notes
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }

            return $this->successResponse(["is_update" => $id, "file_Status" => $fileStatus], "success");
        }
    }
    /**
     * add the voided / bank data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function updateBankVoided($id, Request $request)
    {

        //if($request->hasFile('file')) {

        $userId = $request->user_id;

        $acNumber = $userId . "_" . $request->acc_number;

        $typeId = $request->type_id;

        $type = $request->type;

        $fileStatus = NULL;

        $license = License::select('document_version')

            ->where('user_id', '=', $userId)

            ->where('type_id', '=', $typeId)

            ->where('license_no', '=', $acNumber)

            ->orderBy("id", "DESC")

            ->first();

        $version = is_object($license) ? (int)$license->document_version + 1 : 1;

        $addLicenseData["user_id"]          = $request->user_id;
        $addLicenseData["note"]          = $request->notes;
        $addLicenseData["license_no"]       = $acNumber;
        $addLicenseData["type_id"]          = $typeId;
        $addLicenseData["created_by"]       = $request->created_by;
        $addLicenseData["account_name"]     = $request->acc_name;
        $addLicenseData["routing_number"]   = $request->routing_number;
        $addLicenseData["account_type"]     = $request->acc_type;
        $addLicenseData["created_at"]       = $this->timeStamp();
        $addLicenseData["document_version"] = $version;
        $addLicenseData["contact_person"]   = $request->contact_person;
        $addLicenseData["is_current_version"] = $request->is_current_version;
        $addLicenseData["phone"]            = $request->phone;
        $addLicenseData["email"]            = $request->email;
        $addLicenseData["bank_name"]        = $request->bank_name;



        $newId = License::insertGetId($addLicenseData);
        if ($newId) {
            License::where("license_no", $acNumber)
                ->whereNot("id", $newId)
                ->update(["is_current_version" => 0]);
        }
        if ($request->hasFile('file')) {
            $file = $request->file("file");

            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

            $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);

            $destFolder = "providersEnc/licenses/" . $userId;


            $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
            if (isset($fileRes['file_name'])) {
                $addFileData = [
                    "entities"     => "license_id",
                    "entity_id"     => $newId,
                    "field_key"     => "license_file",
                    "field_value"   => $fileRes['file_name'],
                    "note" => $request->notes
                ];
                $this->addData("attachments", $addFileData, 0);
            }
        } else {
            $hasFile = Attachments::where("entities", "=", "license_id")

                ->where("entity_id", "=", $id)

                ->first();

            if (is_object($hasFile)) {
                $addFileData = [
                    "entities"      => "license_id",
                    "entity_id"     => $newId,
                    "field_key"     => "license_file",
                    "field_value"   => $hasFile->field_value,
                    "note"          => $request->notes != "" ? $request->notes : $hasFile->notes
                ];
                $this->addData("attachments", $addFileData, 0);
            }
        }

        return $this->successResponse(["is_update" => $newId, "file_Status" => $fileStatus], "success");
        //}
    }
    /**
     * add cv document of the user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    function uploadUserCV(Request $request)
    {

        //if($request->hasFile('file')) 
        {

            $userId = $request->user_id;

            //$userName = $this->getUserNameById($userId);

            $reqName = $userId . "_" . $request->name;

            $typeId = $request->type_id;

            $type = $request->type;

            $fileStatus = NULL;

            $license = License::select('document_version')

                ->where('user_id', '=', $userId)

                ->where('type_id', '=', $typeId)

                ->where('name', '=', $reqName)

                ->orderBy("id", "DESC")

                ->first();

            $version = is_object($license) ? (int)$license->document_version + 1 : 1;

            // $docNo = is_object($license) ? $license->license_no : 'Curriculum Vitae';
            // if(is_null($docNo)) {
            //    $docNo =  'Curriculum Vitae';
            // }

            $addLicenseData["user_id"]      = $request->user_id;
            $addLicenseData["note"]      = $request->notes;
            $addLicenseData["license_no"]   = $type;
            $addLicenseData["name"]         = $reqName;
            $addLicenseData["issue_date"]   = NULL;
            $addLicenseData["exp_date"]     = NULL;
            $addLicenseData["issuing_state"] = NULL;
            $addLicenseData["type_id"]      = $typeId;
            $addLicenseData["created_by"]   = $request->created_by;
            $addLicenseData["notify_before_exp"]   = 0;
            $addLicenseData["status"]       = 0;
            $addLicenseData["currently_practicing"]       = 0;
            $addLicenseData["is_current_version"] = $request->is_current_version;
            $addLicenseData["created_at"]   = $this->timeStamp();
            $addLicenseData["document_version"]       = $version;

            $id = License::insertGetId($addLicenseData);
            if ($request->hasFile('file')) {

                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);

                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $id,
                        "field_key"     => "license_file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->notes
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }

            return $this->successResponse(["is_add" => $id, "file_status" => $fileStatus], "success");
        }
    }
    /**
     * update the user cv
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function updateUserCV(Request $request, $id)
    {
        $type = $request->type;
        //if($request->hasFile('file')) 
        {

            $userId = $request->user_id;

            $typeId = $request->type_id;

            $reqName = $request->name;

            $fileStatus = NULL;

            $license = License::select('document_version')

                ->where('user_id', '=', $userId)

                ->where('type_id', '=', $typeId)

                ->where('id', '=', $id)

                ->where('name', '=', $reqName)

                ->first();

            $version = is_object($license) ? (int)$license->document_version + 1 : 1;

            // $docNo = is_object($license) ? $license->license_no : 'Curriculum Vitae';
            // if(is_null($docNo)) {
            //    $docNo =  'Curriculum Vitae';
            // }

            $addLicenseData["user_id"]      = $request->user_id;
            $addLicenseData["note"]      = $request->notes;
            $addLicenseData["license_no"]   = $type;
            $addLicenseData["name"]         = $reqName;
            $addLicenseData["issue_date"]   = NULL;
            $addLicenseData["exp_date"]     = NULL;
            $addLicenseData["issuing_state"] = NULL;
            $addLicenseData["type_id"]      = $typeId;
            $addLicenseData["created_by"]   = $request->created_by;
            $addLicenseData["is_current_version"] = $request->is_current_version;
            $addLicenseData["notify_before_exp"]   = 0;
            $addLicenseData["status"]       = 0;
            $addLicenseData["currently_practicing"]       = 0;
            $addLicenseData["created_at"]   = $this->timeStamp();
            $addLicenseData["document_version"]       = $version;

            $id = License::insertGetId($addLicenseData);
            if ($id) {
                License::where("name", $reqName)
                    ->whereNot("id", $id)
                    ->update(["is_current_version" => 0]);
            }
            if ($request->hasFile('file')) {
                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);

                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $id,
                        "field_key"     => "license_file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->notes
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }

            return $this->successResponse(["is_update" => $id, "file_status" => $fileStatus], "success");
        }
    }
    /**
     * add the document special types
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function addSpecialTypeDocuments(Request $request)
    {

        $type = $request->type;
        $userId = $request->user_id;
        $typeId = $request->type_id;
        $id = 0;
        $fileStatus = NULL;
        if ($type == "education") {
            $eduType = $request->education_type;
            $addData = [
                "user_id" => $userId,
                "education_type" => $eduType,
                "issuing_institute" =>  $request->institute_name,
                "address_line_one" => $request->address,
                "degree" => $request->degree,
                "attendance_date_to" =>  $request->completion_date,
                "is_current_version" => $request->is_current_version,
                "created_at" => $this->timeStamp(),
                "created_by" => $request->created_by,
                "note" => $request->notes,
                "type_id" => $typeId
            ];
            $id = $this->addData("education", $addData);
            if ($request->hasFile("file")) {

                $file = $request->file("file");

                $fileName =  uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "education",
                        "entity_id"     => $id,
                        "field_key"     => "education file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->has('notes') ? $request->notes : NULL
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }
        } elseif ($type == "hospital_affiliations") {
            $addData = [
                "user_id" => $userId,
                "admitting_previleges" => $request->hospital_name,
                "address_line_one" => $request->address,
                "is_primary" => $request->is_primary,
                "start_date" => $request->start_date,
                "end_date" => $request->end_date,
                "is_current_version" => $request->is_current_version,
                "created_by" => $request->created_by,
                "note" => $request->notes,
                "type_id" => $typeId
            ];
            $id = $this->addData("hospital_affiliations", $addData);
            if ($request->hasFile("file")) {

                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "hospital",
                        "entity_id"     => $id,
                        "field_key"     => "hospitl file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->has('notes') ? $request->notes : NULL
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }
        } else {



            $license = License::where('user_id', '=', $userId)

                ->select('document_version')

                ->where('type_id', '=', $typeId)

                ->where('license_no', '=', $request->license_no)

                ->orderBy('id', 'DESC')

                ->first();

            $version = is_object($license) ? (int)$license->document_version + 1 : 1;

            $docNo = is_object($license) ? $license->license_no : $type;
            if (is_null($docNo)) {
                $docNo =  $type;
            }
            $addLicenseData["user_id"]      = $request->user_id;
            $addLicenseData["note"]      = $request->notes;
            $addLicenseData["license_no"]   = $request->has('license_no') ? $request->license_no : NULL;
            $addLicenseData["name"]         =  $request->has('name') ? $request->name : NULL;
            $addLicenseData["issue_date"]   = $request->has('issue_date') ? $request->issue_date : NULL;
            $addLicenseData["exp_date"]     = $request->has('exp_date') ? $request->exp_date : NULL;
            $addLicenseData["issuing_state"] = $request->has('issuing_state') ? $request->issuing_state : NULL;
            $addLicenseData["type_id"]      = $typeId;
            $addLicenseData["created_by"]   = $request->created_by;
            $addLicenseData["notify_before_exp"]   = $request->has('remind_before_days') ? $request->remind_before_days : 30;
            $addLicenseData["status"]       = 0;
            $addLicenseData["currently_practicing"]      = $request->has('currently_practicing') ? $request->currently_practicing : 0;
            $addLicenseData["created_at"]   = $this->timeStamp();
            $addLicenseData["is_current_version"] = $request->is_current_version;
            $addLicenseData["document_version"]       = $version;

            $id = License::insertGetId($addLicenseData);

            if ($request->hasFile("file")) {

                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $id,
                        "field_key"     => "license file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->has('notes') ? $request->notes : NULL
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            }
        }
        return $this->successResponse(["id" => $id, "file_status" => $fileStatus], "success");
    }
    /**
     * fetch special document type data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function fetchSpecialTypesDocuments(Request $request)
    {

        $type = $request->type;
        $userId = $request->user_id;
        $typeId = $request->type_id;
        if ($type == "education") {
            $eduType = $request->education_type;
            $where = [
                ['user_id', '=', $userId],
                ['education_type', '=', $eduType],
                ['is_delete', '=', 0],
            ];
            $eductionData = $this->fetchData("education", $where);
            $eductionDataArr = [];
            if (count($eductionData)) {
                foreach ($eductionData as $key => $education) {
                    $eductionDataArr[$key] = [
                        "key" => (int)$key + 1,
                        "data" => [
                            "id" => $education->id,
                            "issuing_institute" => $education->issuing_institute,
                            "degree" => $education->degree,
                            "updated_at" => date("m/d/Y", strtotime($education->created_at)),
                            "is_current_version" => $education->is_current_version,
                            "completion_date" => date("m/d/Y", strtotime($education->attendance_date_to))
                        ]
                    ];
                }
            }

            return $this->successResponse(["education" => $eductionDataArr], "success");
        } elseif ($type == "hospital_affiliations") {
            $where = [
                ['user_id', '=', $userId],
                ['is_delete', '=', 0],
            ];
            $eductionData = $this->fetchData("hospital_affiliations", $where);
            $eductionDataArr = [];
            if (count($eductionData)) {
                foreach ($eductionData as $key => $education) {
                    $eductionDataArr[$key] = [
                        "key" => (int)$key + 1,
                        "data" => [
                            "id" => $education->id,
                            "hospital_name" => $education->admitting_previleges,
                            "updated_at" => date("m/d/Y", strtotime($education->created_at)),
                            "is_current_version" => $education->is_current_version,
                            "end_date" => date("m/d/Y", strtotime($education->end_date)),
                            "start_date" => date("m/d/Y", strtotime($education->start_date))
                        ]
                    ];
                }
            }
            return $this->successResponse(["hospital_affiliations" => $eductionDataArr], "success");
        }
    }
    /**
     * fetch the speific records
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function fetchSpecificSpecialTypesDocument(Request $request, $id, $type)
    {
        if ($type == "education") {
            $where = [
                ['id', '=', $id]
            ];
            $eductionData = $this->fetchData("education", $where, 1);

            $eductionData->attendance_date_to = date("m/d/Y", strtotime($eductionData->attendance_date_to));
            $eductionData->created_at = date("m/d/Y", strtotime($eductionData->created_at));
            $eductionData->updated_at = date("m/d/Y", strtotime($eductionData->updated_at));
            $eductionData->created_by = $this->getUserNameById($eductionData->created_by);
            $whereFile = [
                ["entities", "=", "education"],
                ["entity_id", "=", $id]

            ];

            $hasFile = Attachments::where($whereFile)

                ->orderBy("id", "DESC")

                ->first();
            $url = env("STORAGE_PATH");
            $nestedFolders = "providersEnc/licenses";
            if (is_object($hasFile)) {
                $eductionData->file_url = $nestedFolders . "/" . $eductionData->user_id . "/" . $hasFile->field_value;
                $eductionData->field_value = $hasFile->field_value;
                // $eductionData->note = $hasFile->note;
            }
            return $this->successResponse(["education" => $eductionData], "success");
        } elseif ($type == "hospital_affiliations") {
            $where = [
                ['id', '=', $id]
            ];
            $eductionData = $this->fetchData("hospital_affiliations", $where, 1);

            $eductionData->end_date = date("m/d/Y", strtotime($eductionData->end_date));
            $eductionData->start_date = date("m/d/Y", strtotime($eductionData->start_date));
            $eductionData->created_at = date("m/d/Y", strtotime($eductionData->created_at));
            $eductionData->updated_at = date("m/d/Y", strtotime($eductionData->updated_at));
            $eductionData->created_by = $this->getUserNameById($eductionData->created_by);
            $eductionData->hospital_name =  $eductionData->admitting_previleges;
            $whereFile = [
                ["entities", "=", "hospital"],
                ["entity_id", "=", $id]

            ];

            $hasFile = Attachments::where($whereFile)

                ->orderBy("id", "DESC")

                ->first();
            $url = env("STORAGE_PATH");
            $nestedFolders = "providersEnc/licenses";
            if (is_object($hasFile)) {
                $eductionData->file_url = $nestedFolders . "/" . $eductionData->user_id . "/" . $hasFile->field_value;
                $eductionData->field_value = $hasFile->field_value;
                // $eductionData->note = $hasFile->note;
            }
            return $this->successResponse(["education" => $eductionData], "success");
        }
    }
    /**
     * update the special document type
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function specialTypesDocumentsUpdate(Request $request, $id, $type)
    {

        $userId = $request->user_id;
        $fileStatus = NULL;
        if ($type == "education") {
            $updateData = [
                "user_id" => $request->user_id,
                "education_type" => $request->education_type,
                "issuing_institute" =>  $request->institute_name,
                "address_line_one" => $request->address,
                "degree" => $request->degree,
                "note" => $request->notes,
                "is_current_version" => $request->is_current_version,
                "attendance_date_to" =>  $request->completion_date,
                "created_at" => $this->timeStamp()
            ];
            $this->updateData("education", ['id' => $id], $updateData);
            if ($request->hasFile("file")) {

                $file = $request->file("file");

                $whereFile = [
                    ["entities", "=", "education"],
                    ["entity_id", "=", $id]

                ];

                $hasFile = Attachments::where($whereFile)

                    ->orderBy("id", "DESC")

                    ->first();

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $fileName = $this->removeWhiteSpaces($fileName);

                if (!is_object($hasFile)) {
                    $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);

                    $destFolder = "providersEnc/licenses/" . $userId;


                    $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                    if (isset($fileRes["file_name"])) {
                        $addFileData = [
                            "entities"     => "education",
                            "entity_id"     => $id,
                            "field_key"     => "education file",
                            "field_value"   => $fileRes['file_name'],
                            "note" => $request->has('notes') ? $request->notes : NULL
                        ];
                        $this->addData("attachments", $addFileData, 0);
                    }
                } else {

                    $this->deleteFile("providersEnc/licenses/" . $userId . "/" . $hasFile->field_value);

                    $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                    $destFolder = "providersEnc/licenses/" . $userId;


                    $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);

                    // exit;
                    //$this->printR($hasFile,true);
                    if (isset($fileRes['file_name'])) {
                        $updateFileData = [
                            "entities"     => "education",
                            "entity_id"     => $id,
                            "field_key"     => "education file",
                            "field_value"   => $fileRes['file_name'],
                            "note" => $request->has('notes') ? $request->notes : NULL
                        ];
                        $this->updateData("attachments", ['id' => $hasFile->id], $updateFileData);
                    }
                }
            }
            return $this->successResponse(["is_update" => $id, "file_status" => $fileStatus], "success");
        } elseif ($type == "hospital_affiliations") {
            $updateData = [
                "user_id" => $userId,
                "admitting_previleges" => $request->hospital_name,
                "address_line_one" => $request->address,
                "is_primary" => $request->is_primary,
                "note" => $request->notes,
                "start_date" => $request->start_date,
                "is_current_version" => $request->is_current_version,
                "end_date" => $request->end_date,
                "created_by" => $request->created_by
            ];
            $this->updateData("hospital_affiliations", ['id' => $id], $updateData);
            if ($request->hasFile("file")) {

                $file = $request->file("file");

                $whereFile = [
                    ["entities", "=", "hospital"],
                    ["entity_id", "=", $id]

                ];

                $hasFile = Attachments::where($whereFile)

                    ->orderBy("id", "DESC")

                    ->first();

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $fileName = $this->removeWhiteSpaces($fileName);

                if (!is_object($hasFile)) {
                    $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                    $destFolder = "providersEnc/licenses/" . $userId;


                    $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                    if (isset($fileRes['file_name'])) {
                        $addFileData = [
                            "entities"     => "hospital",
                            "entity_id"     => $id,
                            "field_key"     => "hospital file",
                            "field_value"   => $fileRes['file_name'],
                            "note" => $request->has('notes') ? $request->notes : NULL
                        ];
                        $this->addData("attachments", $addFileData, 0);
                    }
                } else {
                    $this->deleteFile("providersEnc/licenses/" . $userId . "/" . $hasFile->field_value);
                    $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                    $destFolder = "providersEnc/licenses/" . $userId;


                    $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                    if (isset($fileRes['file_name'])) {
                        $updateFileData = [
                            "entities"     => "hospital",
                            "entity_id"     => $id,
                            "field_key"     => "hospital file",
                            "field_value"   => $fileRes['file_name'],
                            "note" => $request->has('notes') ? $request->notes : NULL
                        ];
                        $this->updateData("attachments", ['id' => $hasFile->id], $updateFileData);
                    }
                }
            }
            return $this->successResponse(["is_update" => $id, "file_status" => $fileStatus], "success");
        } else {

            $license = License::where('user_id', '=', $userId)

                ->select('document_version')

                ->where('type_id', '=', $request->type_id)

                ->where('license_no', '=', $request->license_no)

                ->orderBy('id', 'DESC')

                ->first();

            $version = is_object($license) ? (int)$license->document_version + 1 : 1;

            $docNo = is_object($license) ? $license->license_no : $type;
            if (is_null($docNo)) {
                $docNo =  $type;
            }
            $addLicenseData["user_id"]      = $request->user_id;
            $addLicenseData["note"]      = $request->notes;
            $addLicenseData["license_no"]   = $request->has('license_no') ? $request->license_no : NULL;
            $addLicenseData["name"]         =  $request->has('name') ? $request->name : NULL;
            $addLicenseData["issue_date"]   = $request->has('issue_date') ? $request->issue_date : NULL;
            $addLicenseData["exp_date"]     = $request->has('exp_date') ? $request->exp_date : NULL;
            $addLicenseData["issuing_state"] = $request->has('issuing_state') ? $request->issuing_state : NULL;
            $addLicenseData["type_id"]      = $request->type_id;
            $addLicenseData["created_by"]   = $request->created_by;
            $addLicenseData["notify_before_exp"]   = $request->has('remind_before_days') ? $request->remind_before_days : 30;
            $addLicenseData["is_current_version"] = $request->is_current_version;
            $addLicenseData["status"]       = 0;
            $addLicenseData["currently_practicing"]      = $request->has('currently_practicing') ? $request->currently_practicing : 0;
            $addLicenseData["created_at"]   = $this->timeStamp();
            $addLicenseData["document_version"]       = $version;

            $newId = License::insertGetId($addLicenseData);

            if ($newId) {
                License::where("license_no", $request->license_no)
                    ->whereNot("id", $newId)
                    ->update(["is_current_version" => 0]);
            }

            $whereFile = [
                ["entities", "=", "license_id"],
                ["entity_id", "=", $id]

            ];

            $hasFile = Attachments::where($whereFile)

                ->orderBy("id", "DESC")

                ->first();

            if ($request->hasFile("file")) {

                $file = $request->file("file");

                $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

                $fileName = $this->removeWhiteSpaces($fileName);


                $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
                $destFolder = "providersEnc/licenses/" . $userId;


                $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
                if (isset($fileRes["file_name"])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $newId,
                        "field_key"     => "license_file",
                        "field_value"   => $fileRes["file_name"],
                        "note" => $request->has('notes') ? $request->notes : NULL
                    ];
                    $this->addData("attachments", $addFileData, 0);
                }
            } else {

                $addFileData = [
                    "entities"     => "license_id",
                    "entity_id"     => $newId,
                    "field_key"     => "license_file",
                    "field_value"   => $hasFile->field_value,
                    "note" => $request->has('notes') ? $request->notes : $hasFile->note
                ];
                $this->addData("attachments", $addFileData, 0);
            }
            return $this->successResponse(["is_update" => $newId, "file_status" => $fileStatus], "success");
        }
    }
    /**
     * delete the record of the special types
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function delSpecialTypeDoc(Request $request, $id, $type, $userId)
    {

        if ($type == "education") {
            // $whereFile = [
            //     ["entities", "=", "education"],
            //     ["entity_id", "=", $id]

            // ];

            // $hasFile = Attachments::where($whereFile)

            //     ->orderBy("id", "DESC")

            //     ->first();

            // if (is_object($hasFile)) { //remove the file from the storage
            //     $this->deleteFile("providersEnc/licenses/" . $userId . "/" . $hasFile->field_value);
            //     Attachments::where($whereFile)->delete();
            // }
            $this->updateData('education', ['id' => $id],["is_delete" => 1]);
        } elseif ($type == "hospital_affiliations") {
            // $whereFile = [
            //     ["entities", "=", "hospital"],
            //     ["entity_id", "=", $id]

            // ];

            // $hasFile = Attachments::where($whereFile)

            //     ->orderBy("id", "DESC")

            //     ->first();

            // if (is_object($hasFile)) { //remove the file from the storage
            //     $this->deleteFile("providersEnc/licenses/" . $userId . "/" . $hasFile->field_value);
            //     Attachments::where($whereFile)->delete();
            // }
            // $this->deleteData('hospital_affiliations', ['id' => $id]);
            $this->updateData('hospital_affiliations', ['id' => $id],["is_delete" => 1]);
        }
        return $this->successResponse(["is_del" => $id], "success");
    }
    /**
     * user documents listing 
     * 
     *  @param  \Illuminate\Http\Request  $request
     *  @param  \Illuminate\Http\Response
     */
    function userDocuments(Request $request)
    {

        $userId = $request->user_id;

        $hasMultiRole = $this->userHasMultiRoles($userId);

        // $this->printR($hasMultiRole,true);
        $roleId = 0;
        if (count($hasMultiRole) > 1) {
            foreach ($hasMultiRole as $eachRole) {
                if ($eachRole->role_id == 4 || $eachRole->role_id == 10) {
                    $roleId =  $eachRole->role_id;
                    break;
                } elseif ($eachRole->role_id != 4 && $eachRole->role_id != 10) {
                    $roleId =  $eachRole->role_id;
                    break;
                }
            }
        } else {

            $role = $this->getUserRole($userId);

            $roleId = is_object($role) ? $role->role_id : 0;
        }



        $rolesArr = ['3' => "Practice", "9" => "Practice", "4" => "Provider", "10" => "Provider"]; //role name

        $isFor = $rolesArr[$roleId];

        $parentLicenseType = licensetypes::where('parent_type_id', '=', 0)

            //->whereRaw("(is_for = '$isFor' OR is_for = 'Both')")

            ->orderBy('sort_by', 'ASC')

            ->get();
        // $url = "providersEnc/licenses/";//env("STORAGE_PATH");
        // $nestedFolders = "providersEnc/licenses/";
        // $finalURL = $url . $nestedFolders;
        $licensesTypesExpirations = [
            "label" => "eClinicAssist",
            "value" => "eClinicAssist",
            "children" => []
        ];
        if (count($parentLicenseType)) {

            foreach ($parentLicenseType as $key => $licenseType) {

                $parentTypeId = $licenseType->id;

                $childLicenseTypes = $this->licenseSubTypes($userId, $parentTypeId, $isFor);

                // $this->printR($childLicenseTypes,true);
                $licensesTypesExpirations['children'][$key] = [
                    "label" => $licenseType->name,
                    "value" => $licenseType->id,
                ];
                if (count($childLicenseTypes)) {

                    foreach ($childLicenseTypes as $key2 => $childLicenseType) {

                        $typeId = $childLicenseType->id;



                        $attachment = $this->licenseSubTypesAttachments($userId, $typeId);
                        $fileAttachmentArr = [];
                        if (count($attachment)) {
                            foreach ($attachment as $attac) {

                                if (!is_null($attac->filename)) {
                                    $attachmentArr = [
                                        "label" => $attac->filename,
                                        "value" => "providersEnc/" . $attac->directory . "/" . $userId . "/" . $attac->filename . '?time=' . time() . "_" . uniqid() . "_" . rand(1, 9999),
                                    ];
                                    array_push($fileAttachmentArr, $attachmentArr);
                                }
                            }
                        }
                        $licensesTypesExpirations['children'][$key]['children'][] = [
                            "label" => $childLicenseType->name,
                            "value" => $parentTypeId . "-" . $childLicenseType->id,
                            "children" => $fileAttachmentArr

                        ];
                    }
                } else {
                    $licensesTypesExpirations['children'][$key]['children'] = [];
                }
            }
        }
        return $this->successResponse($licensesTypesExpirations, "success");
    }

    /**
     * share the document with user
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function shareDocument(Request $request)
    {

        $userId     = $request->user_id;
        $email      = $request->email;
        $subject    = $request->subject;

        $data = array(
            'msg' => $request->message
        );

        // $this->printR($data,true);
        $selectedFiles = json_decode($request->selectedFiles, true);
        $serviceReq = env("ENC_APP_URL") . "/api/file/decrypt/file";
        // Get the bearer token
        $token = $request->bearerToken();
        // Add the GET method here

        try {

            Mail::send('mail.share_docs', $data, function ($mail) use ($subject, $selectedFiles, $email, $serviceReq, $token) {
                $mail->to($email)
                    ->from('noreply@eclinicassist.com', 'eClinicAssist')
                    ->subject($subject);

                foreach ($selectedFiles as $file) {
                    //$mail->attach($file);
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        "Accept" => 'application/json'
                    ])
                        ->get($serviceReq, [
                            'file' => $file
                        ]);

                    // Return the response without JSON decoding
                    $fileContents = $response->body();

                    $fileName = basename($file);
                    if (!is_null($fileContents))
                        $mail->attachData($fileContents, $fileName);
                }
            });
            return $this->successResponse(['sent' => true], "success");
        } catch (\Exception $e) {
            return $this->successResponse(['message' => $e->getMessage()], "error");
        }
    }
    /**
     * Check the document expiration
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function sendDocumentExpiryEmail()
    {

        $documentsObj = new Documents();

        $emailTemplateObj = new EmailTemplate();

        $emailTemplate = EmailTemplate::where("is_for", "=", "credentials_email")

            ->first();

        // $this->printR($emailTemplate,true);

        $emailBody = $emailTemplate->message;
        $email = $emailTemplate->email ? $emailTemplate->email : "faheem@yopmail.com"; //$userDetail[0]->user_email;
        $usersInExpiration = $documentsObj->checkDocumentsExpiration();
        $subject = "Expiration of Your Information";
        $isSent = false;
        $errorMsg = "";

        if (count($usersInExpiration) > 0 && is_object($emailTemplate)) {

            foreach ($usersInExpiration as $eachExpiration) {
                $emailBody_ = $emailBody;
                $userDetail = $documentsObj->userDocumentDetails($eachExpiration->user_id);

                // $email = "faheem@yopmail.com";//$userDetail[0]->user_email;
                $userName = $userDetail[0]->user_name;
                if (strpos($emailBody_, '@Name') !== false) {
                    $emailBody_ = str_replace("@Name", $userName, $emailBody_);
                }
                if (strpos($emailBody_, '@Table') !== false) {

                    $dynTable = $emailTemplateObj->expirationTable($userDetail);

                    $emailBody_ = str_replace("@Table", $dynTable, $emailBody_);
                }
                // $data['user_name'] = $userName;
                // $data['docs'] = $userDetail;
                // $this->printR($userDetail,true);
                $data["body"] = $emailBody_;
                try {

                    Mail::send('mail.doc_expiration', $data, function ($mail) use ($subject, $email) {
                        $mail->to($email)
                            ->from('noreply@eclinicassist.com', 'eClinicAssist')
                            ->subject($subject);
                    });
                    $isSent = true;
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                }
            }
        }
        return $this->successResponse(['sent' => $isSent, 'msg' => $errorMsg], "success");
    }
    /**
     * get the caqh data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function fetchCaqhs(Request $request)
    {

        $typeId = $request->get('type_id');

        $userId = $request->get('user_id');

        $customPerPage = $request->has('cust_per_page') ? $request->cust_per_page : $this->cmperPage;

        $licenseType = LicenseTypes::where('id', '=', $typeId)

            ->select('versioning_type')

            ->first();

        $licensesAll = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            "user_licenses.note",
            "user_licenses.is_current_version",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.revalidation_date,'%m/%d/%Y') AS revalidation_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.created_at,'%m/%d/%Y') AS updated_at"),

        );
        if (is_object($licenseType) && $licenseType->versioning_type == "number") {

            $licensesAll = $licensesAll->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.license_no = cm_user_licenses.license_no AND cm_user_licenses.is_delete = 0)")

                ->groupBy('user_licenses.license_no');
        } elseif (is_object($licenseType) && $licenseType->versioning_type == "name") {


            $licensesAll = $licensesAll->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.name = cm_user_licenses.name AND cm_user_licenses.is_delete = 0)")

                ->groupBy('user_licenses.name');
        }
        $licensesAll = $licensesAll
        ->where("user_licenses.is_delete",0)

        ->orderBy("user_licenses.created_at")

        ->paginate($customPerPage);

        $userLicenseArr = [];
        if (count($licensesAll) > 0) {
            foreach ($licensesAll as $key => $license) {
                // $this->printR($license,true);
                $userLicenseArr[$key] = [
                    "key" => (int)$key + 1,
                    "data" => [
                        "license_no"             => $license->license_no,
                        "revalidation_date"      => $license->revalidation_date,
                        "exp_date"               => $license->exp_date,
                        "updated_at"             => $license->updated_at,
                        "id"                     => $license->id,
                        "is_current_version"     => $license->is_current_version,
                        "children"               => []
                    ]
                ];
            }
        }
        return $this->successResponse(['caqhs' => $userLicenseArr], "success");
    }
    /**
     * get the specific caqh data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Request  $id
     * @param  \Illuminate\Http\Response
     */
    function fetchSpecificCaqh(Request $request, $id)
    {

        $license = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),
            "user_licenses.issuing_state",
            DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS user_name"),
            "license_types.name as type",
            "user_licenses.notify_before_exp",
            "user_licenses.currently_practicing",
            "user_licenses.user_id",
            DB::raw("CONCAT(cm_u.first_name,' ',cm_u.last_name) AS created_by"),
            "user_licenses.name",
            'user_licenses.account_name',
            'user_licenses.routing_number',
            'user_licenses.note',
            'user_licenses.account_type',
            'user_licenses.contact_person',
            'user_licenses.is_current_version',
            'user_licenses.phone',
            'user_licenses.email',
            DB::raw("DATE_FORMAT(cm_user_licenses.revalidation_date,'%m/%d/%Y') AS revalidation_date")
        )

            ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

            ->leftJoin("users as u", "u.id", "=", "user_licenses.created_by")

            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id");
            })
            ->where("user_licenses.is_delete", "=", 0)

            ->where("user_licenses.id", "=", $id)

            ->orderBy("user_licenses.id", "DESC")

            ->first();
        // $this->printR($license,true);
        $licenseAttachments = [];
        $url = env("STORAGE_PATH");
        $nestedFolders = "providersEnc/licenses";
        if (is_object($license) > 0) {

            $attachments = Attachments::where("entities", "=", "license_id")->where("entity_id", $license->id)->get();

            if (count($attachments)) {
                foreach ($attachments as $attachment) {
                    $attachment->file_url = $nestedFolders . "/" . $license->user_id . "/" . $attachment->field_value;
                    $licenseAttachments = $attachment;
                }
            }
        }
        return $this->successResponse(['license' => $license, 'attachments' => $licenseAttachments], "success");
    }
    /**
     * store caqh data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function storeCaqhs(Request $request)
    {
        $fileStatus =  NULL;
        $addLicenseData     = [];
        $licenseNo          = $request->license_no;
        $expDate            = $request->exp_date;
        $notifyBeforeExp    = $request->notify_before_exp;
        $revalidationDate   = $request->revalidation_date;
        $typeId             = $request->type_id;
        $userId             = $request->user_id;
        $createdBy          = $request->created_by;
        $isCurrentVersion   = $request->is_current_version;
        $note               = $request->notes;
        $file               = $request->file("file");
        $hasFile            = $request->hasFile("file");

        $addLicenseData["user_id"]              = $userId;
        $addLicenseData["license_no"]           = $licenseNo;
        $addLicenseData["revalidation_date"]    = $revalidationDate;
        $addLicenseData["exp_date"]             = $expDate;
        $addLicenseData["type_id"]              = $typeId;
        $addLicenseData["created_by"]           = $createdBy;
        $addLicenseData["note"]                 = $note;
        $addLicenseData["notify_before_exp"]    = $notifyBeforeExp;
        $addLicenseData["is_current_version"] = $isCurrentVersion;
        $addLicenseData["status"]               = 0;
        $addLicenseData["created_at"]           = $this->timeStamp();
        $addLicenseData["document_version"]     = 1;

        $newId = License::insertGetId($addLicenseData);

        if ($hasFile) {

            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

            $fileName = $this->removeWhiteSpaces($fileName);


            $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);
            $destFolder = "providersEnc/licenses/" . $userId;


            $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);
            if (isset($fileRes["file_name"])) {
                $addFileData = [
                    "entities"     => "license_id",
                    "entity_id"     => $newId,
                    "field_key"     => "license_file",
                    "field_value"   => $fileRes["file_name"],
                    "note" => $request->has('notes') ? $request->notes : NULL
                ];
                $this->addData("attachments", $addFileData, 0);
            }
        }
        return $this->successResponse(['id' => $newId, "file_status" => $fileStatus], "success");
    }
    /**
     * update caqh data
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response
     */
    function updateCaqhs(Request $request, $id)
    {
        $fileStatus = NULL;
        $updateLicenseData  = [];
        $licenseNo          = $request->license_no;
        $expDate            = $request->exp_date;
        $notifyBeforeExp    = $request->notify_before_exp;
        $revalidationDate   = $request->revalidation_date;
        $typeId             = $request->type_id;
        $userId             = $request->user_id;
        $note             = $request->notes;
        $createdBy          = $request->created_by;
        $file               = $request->file("file");
        $hasFile            = $request->hasFile("file");

        $updateLicenseData["user_id"]              = $userId;
        $updateLicenseData["license_no"]           = $licenseNo;
        $updateLicenseData["revalidation_date"]    = $revalidationDate;
        $updateLicenseData["exp_date"]             = $expDate;
        $updateLicenseData["type_id"]              = $typeId;
        $updateLicenseData["created_by"]           = $createdBy;
        $updateLicenseData["note"]                 = $note;
        $updateLicenseData["notify_before_exp"]    = $notifyBeforeExp;
        $updateLicenseData["is_current_version"] = $request->is_current_version;
        $updateLicenseData["status"]               = 0;
        $updateLicenseData["updated_at"]           = $this->timeStamp();
        $updateLicenseData["document_version"]     = 1;

        License::where("id", "=", $id)->update($updateLicenseData);

        if ($hasFile) {

            $whereFile = [
                ["entities", "=", "license_id"],
                ["entity_id", "=", $id]

            ];

            $hasFileAttch = Attachments::where($whereFile)

                ->orderBy("id", "DESC")

                ->first();

            if (is_object($hasFileAttch)) {

                $this->deleteFile("providers/licenses/" . $userId . "/" . $hasFileAttch->field_value);

                Attachments::where($whereFile)

                    ->delete();
            }

            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

            $fileName = $this->removeWhiteSpaces($fileName);


            $this->uploadMyFile($fileName, $file, "providers/licenses/" . $userId);

            $destFolder = "providersEnc/licenses/" . $userId;


            $fileStatus = $fileRes = $this->encryptUpload($request, $destFolder);

            if (isset($fileRes["file_name"])) {
                $addFileData = [
                    "entities"     => "license_id",
                    "entity_id"     => $id,
                    "field_key"     => "license_file",
                    "field_value"   => $fileRes["file_name"],
                    "note" => $request->has('notes') ? $request->notes : NULL
                ];
                $this->addData("attachments", $addFileData, 0);
            }
        }
        return $this->successResponse(['id' => $id, "file_status" => $fileStatus], "success");
    }
}
