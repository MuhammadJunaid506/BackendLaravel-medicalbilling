<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LicenseTypes as licensetypes;
use App\Models\subLicenseTypes;
use App\Models\License;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;

class LicenseTypesController extends Controller
{
    use ApiResponseHandler, Utility;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        try {
            if ($request->has('smart_search') && $request->smart_search != '') {
                $search = $request->smart_search;
                $licenseType = licensetypes::where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('description', 'LIKE', '%' . $search . '%')
                    ->paginate($this->cmperPage);
            } else {
                $licenseType = licensetypes::paginate($this->cmperPage);
            }
            return $this->successResponse(["licenseType" => $licenseType], "Success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
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
            "name"           => "required",
            "is_for"           => "required"
        ]);

        $lastParentLicense = licensetypes::where('parent_type_id', '=', 0)

            ->orderBy('id', 'desc')

            ->first();

        $lastSortIndex = is_object($lastParentLicense) ? (int)$lastParentLicense->sort_by + 1 : 1;

        try {
            $licenseTypeData = [
                "name"                            => $request->name,
                "is_for"                          => $request->is_for,
                "description"                     => NULL,
                "is_attachement_required"         => $request->has('is_attachement_required') ? $request->is_attachement_required : 0,
                "is_for_report"                   => $request->has('is_for_report') ? $request->is_for_report : 1,
                "sort_by"                         => $lastSortIndex
            ];

            $id = licensetypes::insertGetId($licenseTypeData);

            return $this->successResponse(["id" => $id], "LicensesType added successfully.");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
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
        //

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

            $updateData = $request->all();
            $isUpdate  = LicenseTypes::where("id", $id)->update($updateData);

            return $this->successResponse(["is_update" => $isUpdate], "success", 200);
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
        
        try {

            $isDelete  = LicenseTypes::where("id", $id)->delete();
            try {
                if (LicenseTypes::where("parent_type_id", $id)->count() > 0)
                    LicenseTypes::where("parent_type_id", $id)->delete(); //delete the childs if connected
            } catch (\Throwable $exception) {
            }
            return $this->successResponse(["is_delete" => $isDelete], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * Store a newly addSubLicenseType in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addSubLicenseType(Request $request)
    {
        $request->validate([
            "license_id"             => "required",
            "name"                   => "required",
            "is_mandatory"           => "required",
            "is_for"                 => "required"
        ]);

        

        try {

            $sublicenseTypeData = [
                "parent_type_id"            => $request->license_id,
                "name"                      => $request->name,
                "is_mandatory"              => $request->is_mandatory,
                "is_for"                    => $request->is_for,
                "versioning_type"           => 1
            ];

            $licenseSubType = LicenseTypes::where('parent_type_id', '=', $request->license_id)

                ->select('sort_by')

                ->orderBy('id', 'DESC')

                ->first();

            if (is_object($licenseSubType)) {
                $orderCount = $licenseSubType->sort_by;
                $sublicenseTypeData['sort_by'] = (int)$orderCount + 1;
            } else
                $sublicenseTypeData['sort_by'] =  1;


            $id = LicenseTypes::insertGetId($sublicenseTypeData);

            return $this->successResponse(["id" => $id], "Sub LicensesType added successfully.");
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * Display a listing of the fatchSubLicenseType.
     *
     * @return \Illuminate\Http\Response
     */
    public function fatchSubLicenseType(Request $request)
    {
        try {
            if ($request->has('smart_search') && $request->smart_search != '') {
                $search = $request->smart_search;
                $sublicenseType = subLicenseTypes::where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('description', 'LIKE', '%' . $search . '%')
                    ->paginate($this->cmperPage);
            } else {
                $sublicenseType = subLicenseTypes::paginate($this->cmperPage);
            }
            return $this->successResponse(["sublicenseType" => $sublicenseType], "Success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }

    /**
     * Update the specified updateSubLicenseType in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateSubLicenseType(Request $request, $id)
    {

        try {

            $updateData = $request->all();
            $isUpdate  = subLicenseTypes::where("id", $id)->update($updateData);

            return $this->successResponse(["is_update" => $isUpdate], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }


    /**
     * Remove the specified destroySubLicenseType from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroySubLicenseType($id)
    {
        try {

            $isDelete  = subLicenseTypes::where("id", $id)->delete();

            return $this->successResponse(["is_delete" => $isDelete], "success", 200);
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * fetch the license type
     * 
     *  @return \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response
     */
    public function fetchLicensesTypes(Request $request)
    {
        try {
            $licensejson = [];
            $searchInParent = 0;
            $searchInChild = 0;
            if ($request->has('search')) {

                $searchData = licensetypes::where('name', 'LIKE', '%' . $request->search . '%')

                    ->get();

                if (count($searchData)) {

                    foreach ($searchData as $key => $value) {
                        if ($value->parent_type_id) {
                            $searchInChild = 1;
                        } elseif ($value->parent_type_id == 0) {
                            $searchInParent = 1;
                        }
                    }
                }
            }

            $parentLicenseType = licensetypes::where('parent_type_id', '=', 0);
            if ($request->has('search') && $searchInParent == true) {
                $parentLicenseType = $parentLicenseType->where('name', 'LIKE', '%' . $request->search . '%');
            } elseif ($request->has('search') && $searchInParent == false && $searchInChild == true) {

                $searchDataArr = $this->stdToArray($searchData);
                $parentIds = array_column($searchDataArr, 'parent_type_id');
                $parentIds = array_unique($parentIds);
                $parentLicenseType = $parentLicenseType->whereIn('id', $parentIds);
            }
            $parentLicenseType = $parentLicenseType->orderBy('sort_by', 'ASC')

                ->get();

            if (count($parentLicenseType)) {
                $innerLicenseType = [];
                foreach ($parentLicenseType as $key => $innerLicenseType) {
                    $licensejson[$key] = [
                        "key" => (int)$key + 1,
                        "data" => [
                            "id" => $innerLicenseType->id,
                            "type_id" => $innerLicenseType->id,
                            "name" => $innerLicenseType->name,
                            "has_children" => false,
                            "children_count" => 0,
                            "parent_type_id" => 0,
                            "is_for" => $innerLicenseType->is_for
                        ]
                    ];

                    $childs = licensetypes::where('parent_type_id', '=', $innerLicenseType->id);
                    if ($request->has('search') && $searchInChild == true) {
                        $childs = licensetypes::where('name', 'LIKE', '%' . $request->search . '%');
                    }
                    $childs = $childs->select("id", "parent_type_id", "name", "is_mandatory", "is_for", "is_attachement_required", "is_for_report")

                        ->orderBy('sort_by', 'ASC')

                        ->get();

                    if (count($childs)) {
                        $licensejson[$key]['data']['has_children'] = true;
                        $licensejson[$key]['data']['children_count'] = count($childs);
                        foreach ($childs as $child) {

                            $hasChildrent = License::where('type_id', '=', $child->id)

                                ->count();

                            $child->has_children = $hasChildrent > 0 ? true : false;

                            $child->children_count = $hasChildrent;
                            $child->parent =  $licensejson[$key]['data'];
                            $licensejson[$key]['children'][]['data'] = $child;
                        }
                    }
                }

                return $this->successResponse($licensejson, "Success", 200);
            }
        } catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
    /**
     * link the license sub type with the parent type
     * 
     *  @return \Illuminate\Http\Request
     *  @return \Illuminate\Http\Response
     */
    function linkSublicenseType(Request $request)
    {
        // $this->printR($request->all(),true);

        $request->validate([
            'sublicense_id' => 'required',
            'license_id' => 'required'
        ]);

        $licenseId = $request->license_id;

        $sublicenseId = $request->sublicense_id;

        $alreadyLinked = licensetypes::where('id', '=', $sublicenseId)

            ->first();
        $isLinked = false;
        if (is_object($alreadyLinked)) {
            $isLinked = licensetypes::where('id', '=', $sublicenseId)->update(['parent_type_id' => $licenseId]);
        }
        return $this->successResponse(["is_linked" => $isLinked], "success", 200);
    }
}
