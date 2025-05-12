<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\UserAccountActivityLog;
use App\Models\License;
use App\Models\LicenseTypes;
use DB;
use App\Models\Attachments;
use Illuminate\Support\Facades\Http;


class LicenseController extends Controller
{
    use ApiResponseHandler, Utility, UserAccountActivityLog;
    public $licenseTypeIds = ["1", "2", "3", "4"];
   
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            "user_id" => "required"
        ]);

        $userId = $request->user_id;
        $customPerPage = $request->has('cust_per_page') ? $request->cust_per_page : $this->cmperPage;
        $typeId = $request->has('type_id') ? $request->get('type_id') : "";
        $group = $this->fetchData("user_baf_practiseinfo", ["user_id" => $userId], 1, ["provider_type"]);
        $whereIn = [];
        if (is_object($group) && $group->provider_type == "group") {
            // exit()
            $whereIn = $typeId != "" ? [$typeId] : ["3"];
        } elseif (is_object($group) && $group->provider_type == "solo") {
            $whereIn = $typeId != "" ? [$typeId] : ["3"];
        } else {
            //$whereIn = ["1","2","3","4"];
            $whereIn = $typeId != "" ? [$typeId] : ["1", "2", "3", "4"];
        }
        // $this->printR($whereIn,true);
        $licenses = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),
            "user_licenses.issuing_state",
            "is_current_version",
            DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
            "license_types.name as type"
        )

            ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

            ->join("license_types", function ($join) use ($whereIn) {
                $join->on("license_types.id", "=", "user_licenses.type_id")
                    ->whereIn("user_licenses.type_id", $whereIn);
            })
            ->where("user_licenses.is_delete", "=", 0)

            ->where("user_licenses.user_id", "=", $userId)

            ->orderBy("user_licenses.id", "DESC")


            ->get();

        $licenseType = LicenseTypes::where('id', '=', $typeId)

            ->select('versioning_type')

            ->first();

        $licensesAll = License::select(
            "user_licenses.id",
            "user_licenses.license_no",
            DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),
            DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),
            "user_licenses.issuing_state",

            DB::raw("DATE_FORMAT(cm_user_licenses.created_at,'%m/%d/%Y') AS created_At"),
            "user_licenses.type_id",
            'document_version',
            'name',
            'account_name',
            'routing_number',
            'account_type',
            'contact_person',
            "is_current_version",
            'phone',
            'email',
            'user_licenses.bank_name'
        );
        if (is_object($licenseType) && $licenseType->versioning_type == "number") {

            $licensesAll = $licensesAll->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.license_no = cm_user_licenses.license_no AND is_delete = 0)")

                ->groupBy('user_licenses.license_no');
        } elseif (is_object($licenseType) && $licenseType->versioning_type == "name") {


            $licensesAll = $licensesAll->whereRaw("cm_user_licenses.document_version = (SELECT MAX(document_version) FROM `cm_user_licenses`as ul WHERE ul.type_id = '$typeId' AND ul.user_id = '$userId' AND ul.name = cm_user_licenses.name AND is_delete = 0)")

                ->groupBy('user_licenses.name');
        }
        $licensesAll = $licensesAll->where("user_licenses.user_id", "=", $userId)

            ->where("user_licenses.type_id", "=", $typeId)

            ->where("user_licenses.is_delete", "=", 0)

            ->orderBy("user_licenses.created_at")

            ->paginate($customPerPage);
        // $this->printR($licensesAll,true);
        $licensesAllCount = License::select(
            "user_licenses.id"
        )

            ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

            ->join("users as u", "u.id", "=", "user_licenses.created_by")

            ->join("license_types", function ($join) {
                $join->on("license_types.id", "=", "user_licenses.type_id");
                // ->whereIn("user_licenses.type_id",["1","2","4"]);
            })

            ->where("user_licenses.user_id", "=", $userId)

            ->where("user_licenses.is_delete", "=", 0)
            
            ->count();

        // if(count($licenses) > 0 ) {
        //     foreach($licenses as $license) {
        //         if($this->isValidDate($license->issue_date))
        //             $license->issue_date = date("Y-m-d",strtotime($license->issue_date));
        //         if($this->isValidDate($license->exp_date))
        //             $license->exp_date = date("Y-m-d",strtotime($license->exp_date));
        //     }
        // }
        $licenseAttachments = [];
        // $url = env("STORAGE_PATH");
        // $nestedFolders = "providers/licenses";
        // if(count($licensesAll) > 0 ) {
        //     $licensesAllArr = $this->stdToArray($licensesAll);
        //     $licensesIds = array_column($licensesAllArr["data"],"id");
        //     // $this->printR($licensesIds,true);
        //     $attachments = Attachments::where("entities","=","license_id")->whereIn("entity_id",$licensesIds)->get();

        //     if(count($attachments)) {
        //         foreach($attachments as $attachment) {
        //             $attachment->file_url = $url.$nestedFolders."/".$userId."/".$attachment->field_value;
        //             $licenseAttachments[$attachment->entity_id] = $attachment;
        //         }
        //     }
        //     // foreach($licensesAll as $alicense) {

        //     //     // exit;
        //     //     if($this->isValidDate($alicense->issue_date))
        //     //         $alicense->issue_date = date("Y-m-d",strtotime($alicense->issue_date));
        //     //     if($this->isValidDate($alicense->exp_date))
        //     //         $alicense->exp_date = date("Y-m-d",strtotime($alicense->exp_date));
        //     //     // if(!is_null($alicense->created_at)) {
        //     //     //     $alicense->created_at = date("Y-m-d",strtotime($alicense->created_at));
        //     //     //     exit("in iff".$alicense->created_at);
        //     //     // }
        //     // }
        // }
        $userLicenseArr = [];
        if (count($licensesAll) > 0) {
            foreach ($licensesAll as $key => $license) {
                // $this->printR($license,true);
                $userLicenseArr[$key] = [
                    "key" => (int)$key + 1,
                    "data" => [
                        "id" => $license->id,
                        "license_no" => $license->license_no,
                        "document_version" => $license->document_version,
                        "exp_date" => $license->exp_date,
                        "updated_at" => $license->created_At,
                        "is_current_version" => $license->is_current_version,
                        "name" => $license->name
                    ]
                ];

                $childLicenseVersions = License::select(
                    "user_licenses.id",
                    "user_licenses.license_no",
                    DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),
                    DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),
                    "user_licenses.issuing_state",

                    DB::raw("DATE_FORMAT(cm_user_licenses.created_at,'%m/%d/%Y') AS created_At"),
                    "user_licenses.type_id",
                    'document_version',
                    'name',
                    'account_name',
                    'routing_number',
                    'account_type',
                    "is_current_version",
                    'contact_person',
                    'phone',
                    'email'
                );
                if (is_object($licenseType) && $licenseType->versioning_type == "number") {
                    $childLicenseVersions = $childLicenseVersions->where('user_licenses.license_no', '=', $license->license_no);
                } elseif (is_object($licenseType) && $licenseType->versioning_type == "name") {
                    $childLicenseVersions = $childLicenseVersions->where('user_licenses.name', '=', $license->name);
                }

                $childLicenseVersions = $childLicenseVersions->where("user_licenses.id", "<>", $license->id)

                    ->where('user_licenses.type_id', '=', $typeId)

                    ->where('user_licenses.user_id', '=', $userId)
                    
                    ->where("user_licenses.is_delete", "=", 0)

                    ->orderBy("user_licenses.document_version", "DESC")

                    ->get();

                foreach ($childLicenseVersions as $childVersion) {
                    $userLicenseArr[$key]['children'][] = [
                        "key" =>  $childVersion->id,
                        "data" => [
                            "id" => $childVersion->id,
                            "license_no" => $childVersion->license_no,
                            "document_version" => $childVersion->document_version,
                            "exp_date" => $childVersion->exp_date,
                            "name" => $childVersion->name,
                            "is_current_version" => $childVersion->is_current_version,
                            "updated_at" => $childVersion->created_At
                        ]
                    ];
                }
                // if($license->document_version == 1 ) {
                //     $childLicenseVersions = License::select("user_licenses.id","user_licenses.license_no",DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),"user_licenses.issuing_state",
                //     DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),"license_types.name as type", DB::raw("CONCAT(cm_u.first_name,' ',cm_u.last_name) AS user_name"),
                //     DB::raw("DATE_FORMAT(cm_user_licenses.created_at,'%m/%d/%Y') AS created_At"),
                //     "user_licenses.type_id",
                //     DB::raw("if(cm_user_licenses.type_id IN (1,2,3,4), '1', '2') as sort_by"),
                //     'document_version'
                //     )

                //     ->join("users","users.id","=","user_licenses.user_id")

                //     ->join("users as u","u.id","=","user_licenses.created_by")

                //     ->join("license_types",function($join) use($whereIn) {
                //         $join->on("license_types.id","=","user_licenses.type_id")
                //         ->whereIn("user_licenses.type_id",$whereIn);
                //     })

                //     ->where("user_licenses.user_id","=",$userId)

                //     ->where("user_licenses.license_no","=",$license->license_no)

                //     ->whereNotIn("user_licenses.id",[$license->id])

                //     ->get();

                //     //$license->childrens = $childLicenseVersions;
                //     // $this->printR($childLicenseVersions,true);
                // foreach($childLicenseVersions as $childVersion) {
                //     $userLicenseArr[$key]['children'][] = [
                //         "key" =>  $childVersion->id,
                //         "data" => [
                //            "id" => $childVersion->id,
                //            "license_no" => $childVersion->license_no,
                //            "document_version" => $childVersion->document_version,
                //            "exp_date" => $childVersion->exp_date,
                //            "updated_at" => $childVersion->created_At
                //         ]
                //     ];
                // }

                // }
                // else {
                //     $userLicenseArr[$key]['children'] = [];
                // }

            }
        }
        return $this->successResponse([
            "specific_licenses" => $licenses, "all_licenses" => $userLicenseArr,
            "license_attachments" => $licenseAttachments,
            "totall" => $licensesAllCount
        ], "success");
        // $this->printR($licenses,true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            "type_id"               => "required",
            "license_no"            => "required",
            "issuing_state"         => "required",
            "issue_date"            => "required",
            "exp_date"              => "required",
            "user_id"               => "required",
            "is_current_version"    => "required",
            "created_by"            => "required",
        ]);

        $sessionUserName = $this->getSessionUserName($request, $request->created_by);
        $fileStatus = 0;
        $userId = $request->user_id;
        $status = $request->has("status") ? $request->status : 0;
        $addLicenseData = [];
        $fileName = "";
        $fileRes = "";
        if ($request->file("file") != null && $request->file("file") != "undefined") {
            $file = $request->file("file");
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));
            // $this->uploadMyFile($fileName,$file,"providers/licenses/".$userId);
            // $link = env("STORAGE_PATH")."providers/licenses/".$userId."/".$fileName;
            // $addLicenseData["file_src"] = $link;
        }

        $userId = $request->user_id;
        $typeId = $request->type_id;
        $LicenseNo = $userId . "_" . $request->license_no;

        $licenseVersion = License::where("user_id", "=", $userId)
            ->where("type_id", "=", $typeId)
            //->where("license_no", "=", $LicenseNo)
            ->orderBy('id', 'DESC')
            ->first();

        $documentLicenseVersion = is_object($licenseVersion) ? (int)$licenseVersion->document_version + 1 : 1;

        $addLicenseData["user_id"]      = $request->user_id;
        $addLicenseData["license_no"]   = $LicenseNo;
        $addLicenseData["issue_date"]   = $request->issue_date;
        $addLicenseData["exp_date"]     = $request->exp_date;
        $addLicenseData["issuing_state"] = $request->issuing_state;
        $addLicenseData["type_id"]      = $request->type_id;
        $addLicenseData["created_by"]   = $request->created_by;
        $addLicenseData["notify_before_exp"]   = strlen($request->remind_before_days) > 0 && $request->remind_before_days > 0 ?  $request->remind_before_days : 30;
        $addLicenseData["status"]       = $status;
        $addLicenseData["currently_practicing"]       = $request->currently_practicing;
        $addLicenseData["note"]       = $request->notes;
        $addLicenseData["created_at"]   = $this->timeStamp();
        $addLicenseData["is_current_version"]   = $request->is_current_version;
        $addLicenseData["document_version"]       = $documentLicenseVersion;
        // $this->PrintR($addLicenseData,true);
        // exit;
        $userId = $request->user_id;
        $typeId = $request->type_id;
        $issuingState = $request->issuing_state;

        $license = License::where("user_id", "=", $userId)
            ->where("type_id", "=", $typeId)
            ->where("issuing_state", "=", $issuingState)
            ->orderBy('id', 'DESC')
            ->first();
        $documentVersion = 1;
        if (is_object($license)) {
            $whereFile = [
                ["entities", "=", "license_id"],
                ["entity_id", "=", $license->id]

            ];
            $hasFile = Attachments::where($whereFile)
                ->orderBy("id", "DESC")
                ->first();
            // $documentVersion = is_object($hasFile) ? (int)$hasFile->document_version + 1 : 1;
        }

        $id = License::insertGetId($addLicenseData);
        if($id) {
            License::where("license_no", $LicenseNo)
                    ->whereNot("id", $id)
                    ->update(["is_current_version" => 0]);
        }
        if ($id > 0 && $fileName != "") {

            $file = $request->file("file");
            $fileStatus = $this->uploadMyFile($fileName,$file,"providers/licenses/".$userId);


            $destFolder = "providersEnc/licenses/" . $userId;


            $fileRes = $this->encryptUpload($request,$destFolder);


            if (isset($fileRes["file_name"])) {

                $addFileData = [
                    "entities"     => "license_id",
                    "entity_id"     => $id,
                    "field_key"     => "license_file",
                    "field_value"   => $fileRes["file_name"],
                    "note" => $request->notes
                ];
                $this->addData("attachments", $addFileData, 0);
                //$addMap = ["user_id" => $request->user_id,"attachment_id" => $aid];
                //$this->addData("user_attachment_map",$addMap);
            }
        }
        $licenseType = $this->fetchData("license_types", ['id' => $typeId], 1, ['name']);
        $msg = $this->addNewDataLogMsg($sessionUserName, $licenseType->name);
        //handle the user activity
        $this->handleUserActivity(
            $userId,
            $request->created_by,
            "Credentails",
            "Add",
            $msg,
            $this->timeStamp(),
            NULL
        );
        return $this->successResponse(["is_create" => true, "id" => $id, 'file_status'  => $fileRes,"file_two" => $fileStatus], "success");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        // exit("hello");
        if ($request->has("search_filter")) {

            $searchVal = $request->keyword;
            $userId = $request->user_id;
            $licenseType = $request->license_type;
            $licenses = License::select(
                "user_licenses.id",
                "user_licenses.license_no",
                DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),
                DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),
                "user_licenses.issuing_state",
                DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
                "license_types.name as type",
                DB::raw("CONCAT(cm_u.first_name,' ',cm_u.last_name) AS user_name"),
                'user_licenses.is_current_version',
                DB::raw("DATE_FORMAT(cm_user_licenses.created_at,'%m/%d/%Y') AS created_At", "user_licenses.notify_before_exp", "user_licenses.currently_practicing")
            )

                ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

                ->join("users as u", "u.id", "=", "user_licenses.created_by")

                ->join("license_types", function ($join) use ($licenseType) {
                    $join->on("license_types.id", "=", "user_licenses.type_id")
                        ->whereIn("user_licenses.type_id", [$licenseType]);
                })
                ->where("user_licenses.is_delete", "=", 0)

                ->whereRaw("cm_user_licenses.user_id = $userId AND (cm_user_licenses.license_no LIKE '%" . $searchVal . "%' OR cm_user_licenses.issuing_state LIKE '%" . $searchVal . "%'  OR cm_user_licenses.exp_date LIKE '%" . $searchVal . "%' OR cm_user_licenses.issue_date LIKE '%" . $searchVal . "%' OR cm_license_types.name LIKE '%" . $searchVal . "%')")

                ->paginate($this->cmperPage);
            // echo $licenses;
            // exit;

            $licensesAllCount = License::select(
                "user_licenses.id",
                "user_licenses.license_no",
                DB::raw("DATE_FORMAT(cm_user_licenses.issue_date,'%m/%d/%Y') AS issue_date"),
                DB::raw("DATE_FORMAT(cm_user_licenses.exp_date,'%m/%d/%Y') AS exp_date"),
                "user_licenses.issuing_state",
                DB::raw("CONCAT(cm_users.first_name,' ',cm_users.last_name) AS name"),
                "license_types.name as type",
                DB::raw("CONCAT(cm_u.first_name,' ',cm_u.last_name) AS user_name"),
                'user_licenses.is_current_version',
                DB::raw("DATE_FORMAT(cm_user_licenses.created_at,'%m/%d/%Y') AS created_At", "user_licenses.currently_practicing", "user_licenses.user_id")
            )

                ->leftJoin("users", "users.id", "=", "user_licenses.user_id")

                ->join("users as u", "u.id", "=", "user_licenses.created_by")

                ->join("license_types", function ($join) use ($licenseType) {
                    $join->on("license_types.id", "=", "user_licenses.type_id")
                        ->whereIn("user_licenses.type_id", [$licenseType]);
                })
                ->where("user_licenses.is_delete", "=", 0)
                ->where("user_licenses.user_id", "=", $userId)

                ->count();
            // $this->printR($licensesAllCount, true);
            // exit;
            $licenseAttachments = [];
            //$url = env("STORAGE_PATH");
            $nestedFolders = "providersEnc/licenses";
            if (count($licenses) > 0) {
                $licensesAllArr = $this->stdToArray($licenses);
                $licensesIds = array_column($licensesAllArr, "id");
                $attachments = Attachments::where("entities", "=", "license_id")->whereIn("entity_id", $licensesIds)->get();

                if (count($attachments)) {
                    foreach ($attachments as $attachment) {
                        $attachment->file_url = $nestedFolders . "/" . $attachment->entity_id . "/" . $attachment->field_value;
                        $licenseAttachments[$attachment->entity_id] = $attachment;
                    }
                }
            }
            return $this->successResponse([
                "all_licenses" => $licenses,
                "license_attachments" => $licenseAttachments,
                "totall" => $licensesAllCount
            ], "success");
        } else {
            // $userId = $id;

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
                "user_licenses.note",
                'user_licenses.account_name',
                'user_licenses.routing_number',
                'user_licenses.account_type',
                'user_licenses.contact_person',
                'user_licenses.phone',
                'user_licenses.email',
                'user_licenses.is_current_version',
                'user_licenses.bank_name'
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
            //$url = env("STORAGE_PATH");
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
        //$inputAll = $request->all();
        $fileUploadStatus = NULL;
        $userId = $request->user_id;

        $status = $request->has("status") ? $request->status : 0;

        $updateLicenseData = [];
        $updateLicenseData["user_id"]      = $request->user_id;
        $updateLicenseData["license_no"]   = $request->license_no;
        $updateLicenseData["issue_date"]   = $request->issue_date;
        $updateLicenseData["exp_date"]     = $request->exp_date;
        $updateLicenseData["issuing_state"] = $request->issuing_state;
        $updateLicenseData["type_id"]      = $request->type_id;
        $updateLicenseData["created_by"]   = $request->created_by;
        $updateLicenseData["notify_before_exp"]   = strlen($request->remind_before_days) > 0 && $request->remind_before_days > 0 ?  $request->remind_before_days : 30;
        $updateLicenseData["status"]       = $status;
        $updateLicenseData["currently_practicing"]       = $request->currently_practicing;
        $updateLicenseData["is_current_version"]       = $request->is_current_version;
        $updateLicenseData["note"]       = $request->notes;
        $updateLicenseData["created_at"]   = $this->timeStamp();
        $sessionUserName = $this->getSessionUserName($request, $request->created_by);
        $logMsg = "";

        $license = License::find($id);
        $licenseArr = $this->stdToArray($license);
        $logMsg .= $this->makeTheLogMsg($sessionUserName, $updateLicenseData, $licenseArr);
        $version = (int)$license->document_version + 1;
        $updateLicenseData['document_version'] = $version;
        $updateLicenseData['issue_date'] = $license->issue_date;
        //$isUpdate = License::find($id)->update($updateLicenseData);
        $isUpdate = License::insertGetId($updateLicenseData);
        if($isUpdate) {
            License::where("license_no", $request->license_no)
                    ->whereNot("id", $isUpdate)
                    ->update(["is_current_version" => 0]);
        }
        if ($request->file("file") != null && $request->file("file") != "undefined") {
            $file = $request->file("file");
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));

            //unset($inputAll["file"]);
            // $link = env("STORAGE_PATH")."providers/licenses/".$userId."/".$fileName;
            // $updateLicenseData["file_src"] = $link;
            $whereFile = [
                ["entities", "=", "license_id"],
                ["entity_id", "=", $isUpdate]

            ];
            $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
            $fileUpdate = ["field_value" => $fileName, "notify_before_exp" => $request->remind_before_days, "note" => $request->notes];
            if ($hasFile) {

                $fileArr = $this->stdToArray($hasFile);
                $logMsg .= $this->makeTheLogMsg($sessionUserName, $fileUpdate, $fileArr);
                //$this->deleteFile("providers/licenses/".$userId."/". $hasFile->field_value);

                $documentVersion = 1; //(int) $hasFile->document_version + 1;

                $this->uploadMyFile($fileName,$file,"providers/licenses/".$userId);
                $destFolder = "providersEnc/licenses/" . $userId;

                $fileUploadStatus = $fileRes = $this->encryptUpload($request,$destFolder);
                if(isset($fileRes['file_name'])) {
                    $updateFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $isUpdate,
                        "field_key"     => "license_file",
                        "field_value"   => $fileRes['file_name'],
                        "note" => $request->notes

                    ];
                    $this->updateData("attachments", $whereFile, $updateFileData);
                }
            } else {
                // $this->uploadMyFile($fileName,$file,"providers/licenses/".$userId);
                $destFolder = "providersEnc/licenses/" . $userId;
                
                $$fileUploadStatus = $fileRes = $this->encryptUpload($request,$destFolder);

                $logMsg .= $this->makeTheLogMsg($sessionUserName, $fileUpdate, []);
                if(isset($fileRes['file_name'])) {
                    $addFileData = [
                        "entities"     => "license_id",
                        "entity_id"     => $isUpdate,
                        "field_key"     => "license_file",
                        "field_value"   =>  $fileRes['file_name'],
                        "note" => $request->notes
                    ];
                }
                $aid = $this->addData("attachments", $addFileData, 0);
                $addMap = ["user_id" => $request->user_id, "attachment_id" => $aid];
                $this->addData("user_attachment_map", $addMap);
            }
        } else {
            $whereFile = [
                ["entities", "=", "license_id"],
                ["entity_id", "=", $id]

            ];
            $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
            if (is_object($hasFile)) {
                $addFileData = [
                    "entities"     => "license_id",
                    "entity_id"     => $isUpdate,
                    "field_key"     => "license_file",
                    "field_value"   => $hasFile->field_value,
                    "note" => $request->notes
                ];
                $aid = $this->addData("attachments", $addFileData, 0);
            }
        }
        //handle the user activity
        $this->handleUserActivity(
            $userId,
            $request->created_by,
            "Credentails",
            "Update",
            $logMsg,
            NULL,
            $this->timeStamp()
        );
        return $this->successResponse(["is_update" => $isUpdate, "id" => $id,'file_status' => $fileUploadStatus], "success");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $sessionUserName = $this->getSessionUserName($request);

        $sessionUserId = $this->getSessionUserId($request);
        //
        $deleteLicense = License::where("id", "=", $id)
        ->first();
        if (is_object($deleteLicense)) {
            $userId = $deleteLicense->user_id;
            $typeId = $deleteLicense->type_id;

            $licenseType = $this->fetchData("license_types", ['id' => $typeId], 1, ['name']);

            $delMsg = $this->delDataLogMsg($sessionUserName, $licenseType->name);

            // $whereFile = [
            //     ["entities", "=", "license_id"],
            //     ["entity_id", "=", $id]

            // ];
            // $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
            // if ($hasFile) {
            //     $this->deleteFile("providersEnc/licenses/" . $userId . "/" . $hasFile->field_value);
            //     Attachments::where($whereFile)->delete(); //delete fiel from table
            // }
            $isDel = License::where("id", "=", $id)->update(['is_delete' =>1]);
            // $allRecords = License::where("user_id", "=", $userId)
            // ->where("type_id", "=", $typeId)
            // ->where("is_delete", "=", 0)
            // ->get();

            // if(count($allRecords)) {
            //     //reset the version of the license
            //     foreach($allRecords as $key=>$record) {
            //         $newVersion = $key + 1;
            //         License::where("id", "=", $record->id)->update(['document_version' =>$newVersion]);
            //     }
            // }
            //handle the user activity
            $this->handleUserActivity(
                $userId,
                $sessionUserId,
                "Credentails",
                "Delete",
                $delMsg,
                NULL,
                $this->timeStamp()
            );
            return $this->successResponse(["is_delete" => $isDel, "id" => $id], "success");
        } else {
            return $this->successResponse(["is_delete" => 0, "id" => $id], "success");
        }
    }
}
