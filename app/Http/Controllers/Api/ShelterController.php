<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Models\Shelters;
use DB;

class ShelterController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        
        $shelters = Shelters::select("shelters.id AS shelter_id","shelters.*","shelter_facility_affiliations.*",
        DB::raw("DATE_FORMAT(cm_shelters.created_at,'%m/%d/%Y') AS uploaded_at"),DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as created_by"))
        
        ->leftJoin("users","users.id","=","shelters.created_by")

        ->leftJoin("shelter_facility_map","shelter_facility_map.shelter_id","=","shelters.id")
        
        ->leftJoin("shelter_facility_affiliations","shelter_facility_affiliations.shelter_facility_map_id","=","shelter_facility_map.id")

        ->where("shelter_facility_map.facility_id","=",$userId)
        
        ->orderBy("shelters.id","DESC")

        ->get();
        $sheltersArr = [];
        if(count($shelters)) {
            foreach($shelters as $key=>$shelter) {
                $sheltersArr[$key] = [
                    "key" => (int)$key + 1,
                    "data" => [
                       "id" => $shelter->shelter_id,
                       "name" => $shelter->name,
                       "start_date" => $shelter->start_date,
                       "end_date" => $shelter->end_date,
                       "name" => $shelter->name,
                       "active" => $shelter->affiliation_status,
                       "updated_at" => $shelter->uploaded_at,
                       "children" => []
                    ]
                ];
            }
        }
        return $this->successResponse($sheltersArr,"success");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $userId             = $request->user_id;
        $typeId             = $request->type_id;
        $createdBy          = $request->created_by;
        $shelterName        = $request->name;
        $address_line_one   = $request->address_line_one;
        $address_line_two   = $request->address_line_two;
        $city               = $request->city;
        $state              = $request->state;
        $zipcode            = $request->zipcode;
        $email              = $request->email;
        $fax                = $request->fax;   
        $contactPerson      = $request->contact_person;
        $phoneNumber        = $request->phone_number;
        $faxNumber          = $request->fax_number;
        $isActive           = $request->is_active;
        $remarks            = $request->remarks;
        $startDate          = $request->start_date;
        $endDate            = $request->end_date;

        $shelterData = [
            "name"              => $shelterName,
            "address"           => $address_line_one,
            "contact_person"    => $contactPerson,
            "phone_number"      => $phoneNumber,
            "fax_number"        => $faxNumber,
            "address_two"       => $address_line_two,
            "city"              => $city,
            "state"             => $state,
            "zipcode"           => $zipcode,
            "email"             => $email,
            "fax"               => $fax,
            "note"              => $request->notes,
            "created_by"        => $createdBy,
            "created_at"        => $this->timeStamp()
        ];

        $id = Shelters::insertGetId($shelterData);
        
        $mapId = DB::table("shelter_facility_map")
        ->insertGetId([
            "facility_id" => $userId,
            "shelter_id" => $id,
            "affiliation_status" => $isActive,
            "created_at" => $this->timeStamp()
        ]);

        DB::table("shelter_facility_affiliations")
        ->insertGetId([
            "shelter_facility_map_id" => $mapId,
            "start_date" => $startDate,
            "end_date" => $endDate,
            "affiliation_status" => $isActive,
            "remarks" => $remarks,
            "created_at" => $this->timeStamp()
        ]);
        return $this->successResponse(["ins_id" => $id],"success");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $shelters = Shelters::select("shelters.id AS shelter_id","shelters.*","shelter_facility_affiliations.*",
        DB::raw("DATE_FORMAT(cm_shelters.created_at,'%m/%d/%Y') AS uploaded_at"),DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as created_by"))
        
        ->leftJoin("users","users.id","=","shelters.created_by")

        ->leftJoin("shelter_facility_map","shelter_facility_map.shelter_id","=","shelters.id")
        
        ->leftJoin("shelter_facility_affiliations","shelter_facility_affiliations.shelter_facility_map_id","=","shelter_facility_map.id")

        ->where("shelters.id","=",$id)
        
        ->first();

        return $this->successResponse($shelters,"success");
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
        
        $shelterData = [
            "name"              => $request->name,
            "address"           => $request->address_line_one,
            "contact_person"    => $request->contact_person,
            "phone_number"      => $request->phone_number,
            "fax_number"        => $request->fax_number,
            "address_two"       => $request->address_line_two,
            "city"              => $request->city,
            "state"             => $request->state,
            "zipcode"           => $request->zipcode,
            "email"             => $request->email,
            "fax"               => $request->fax,
            "note"              => $request->note,
            "updated_at"        => $this->timeStamp()
        ];

        $mapId = DB::table("shelter_facility_map")->where("shelter_id",$id)->first(['id']);
        DB::table("shelter_facility_affiliations")->where("shelter_facility_map_id",$mapId->id)
        ->update(['start_date' => $request->start_date,"end_date"=> $request->end_date,"remarks" => $request->remarks,"affiliation_status" => $request->is_active]);
        $isUpdate = Shelters::where('id','=',$id)

        ->update($shelterData);

        return $this->successResponse(["is_update" => $isUpdate],"success");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Shelters::where("id",$id)->delete();
        
        $shelterId = DB::table("cm_shelter_facility_map")->where("shelter_id",$id)->pluck("id");

        DB::table("cm_shelter_facility_map")->where("shelter_id",$id)->delete();
       
        DB::table("shelter_facility_affiliations")->where("shelter_facility_map_id",$shelterId)->delete();

        return $this->successResponse(["is_delete" => true],"success");
    }
}
