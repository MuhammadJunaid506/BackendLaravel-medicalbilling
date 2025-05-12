<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Portal;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Traits\UserAccountActivityLog;
use App\Models\Credentialing;
use App\Models\PortalType;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;


class PortalController extends Controller
{
    use ApiResponseHandler,Utility,UserAccountActivityLog;
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

        $sessionUserId = $this->getSessionUserId($request);

        $search = $request->has("keyword") ? $request->get("keyword") : null;

        $portalObj = new Portal();
        if($userId > 0 && $userId)
            $userPortalData = $portalObj->getUserPortals($userId,$search,20,$sessionUserId);
        else
            $userPortalData = $portalObj->getOverAllUserPortals($search,$sessionUserId,$request);
        $portalObj = NULL;

        return $this->successResponse(["portals" => $userPortalData],"success");

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
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        if($request->has("is_filter") && $request->is_filter == "true") {
            $sessionUserId = $this->getSessionUserId($request);
            $keyword = $request->keyword;
            $userId = $request->user_id;
            $portalObj = new Portal();
            if($userId > 0)
                $portals = $portalObj->getUserPortalsFilter($userId,$keyword,20,$sessionUserId);
            else
                $portals = $portalObj->getOverAllUserPortals($keyword,$sessionUserId,$request);

            return $this->successResponse(["portals" => $portals],"success");
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $portalId
     * @param  int  $userId
     * @return \Illuminate\Http\Response
     */
    public function updatePortal(Request $request,$portalId,$userId) {

        $request->validate([
            "user_id" =>  "required",
            "user_name" =>  "required",
            "password"  =>  "required",
            "type_id"    => "required",
            'is_admin'   => 'required',
            'for_credentialing'=>'required',
            'type'=>'required|in:provider,facility',
            'report'=>'required',
        ]);

        $updateData = $request->all();
        $sessionUserId = $this->getSessionUserId($request);
        $portalObj = Portal::where('user_id',$userId)->where('id',$portalId)->first();
        if($portalObj ==null){
            return $this->warningResponse([],"No Portal Found",404);
        }
        $portalObj->user_name = $updateData["user_name"];
        $portalObj->password  =encrypt($updateData["password"]);
        if($request->has('notes')){
            $portalObj->notes    = encrypt($updateData["notes"]);
        }
        $portalObj->user_id   = $updateData["user_id"];
        $portalObj->type_id   = $updateData["type_id"];
        $portalObj->updated_by = $sessionUserId;
        $portalObj->for_credentialing = $request->for_credentialing;
        $portalObj->report = $request->report;
        $portalObj->mapping_type = $request->type;
        $portalObj->save();
  
        $portalObj->refresh();
        $portalDataArr = $this->stdToArray($portalObj);
  
        return $this->successResponse(["is_update" => true],"success");
    }

    public function fetchPortal(Request $request){

        $request->validate([
            "user_id" => "required"
        ]);

        $userId = $request->user_id;

        $sessionUserId = $this->getSessionUserId($request);


        $search = $request->has("keyword") ? $request->get("keyword") : null;
        $portalObj = new Portal();
        if($userId > 0 && $userId)
            $userPortalData = $portalObj->getUserPortals($userId,$search,20,$sessionUserId);
        else
            $userPortalData = $portalObj->fetchOverAllUserPortals($search,$sessionUserId);

        // dd( $userPortalData );
        $portalObj = NULL;

        return $this->successResponse(["portals" => $userPortalData],"success");
    }

    public function fetchPortalUsers(Request $request){
        $sessionUserId = $this->getSessionUserId($request);

        $Credentialing = new Credentialing();
        $allfacilityUsers = $Credentialing->fetchActiveFacilitiesAndProviderOfUser($sessionUserId,0);
        $facilities = $allfacilityUsers['facility'];
        $providers = $allfacilityUsers['providers'];
        $allUsers = [...$providers,...$facilities];
      
        return $this->successResponse($allUsers,"success");

    }

    public function fetchPortalProviders(Request $request){
        $sessionUserId = $this->getSessionUserId($request);

        $Credentialing = new Credentialing();
        $allfacilityUsers = $Credentialing->fetchActiveFacilitiesAndProviderOfUser($sessionUserId,0);
        $providers = $allfacilityUsers['providers'];
      
        return $this->successResponse($providers,"success");

    }

    public function fetchPortalfacility(Request $request){
        $sessionUserId = $this->getSessionUserId($request);

        $Credentialing = new Credentialing();
        $allfacilityUsers = $Credentialing->fetchActiveFacilitiesAndProviderOfUser($sessionUserId,0);
        $facilities = $allfacilityUsers['facility'];
      
        return $this->successResponse($facilities,"success");

    }


    public function fetchPortalCreatedBy(){
        $createdby = Portal::select('id','created_by')
        ->where('created_by','!=',null)
        ->with(['createdby'=>function($query){
            $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
        }])
        ->whereHas('createdby',function($query){
            $query->where('id','!=',null)->where(DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) "),'!='," ");
        })
        ->groupby('created_by')->get()->toArray();
        $allCreatedby = array_column($createdby,"createdby");
        return $this->successResponse($allCreatedby,"success");

    }

    public function fetchPortalUpdatedBy(){

        $createdby = Portal::select('id','updated_by')
        ->where('updated_by','!=',null)
        ->with(['updatedby'=>function($query){
            $query->select('id', DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as user_name"));
        }])
        ->whereHas('updatedby',function($query){
            $query->where('id','!=',null)->where(DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) "),'!='," ");
        })
        ->groupby('updated_by')->get()->toArray();
       
        $allCreatedby = array_column($createdby,"updatedby");
        return $this->successResponse($allCreatedby,"success");
        
    }


    public function portalDataDump()  {

    
        $key = env('AES_KEY');

        $providerPortal =  DB::table('portals')->select(
            DB::raw('CONCAT(COALESCE(cm_u.first_name, ""), " ", COALESCE(cm_u.last_name, "")) AS name'),
            'cm_pt.name AS portal',
            'portals.user_name AS username',
            'cm_pt.link AS link',
            'portals.password',
            'portals.notes',
            'portals.report',
            'portals.for_credentialing',
            'portals.id AS portal_id',
            'portals.user_id AS portal_user_id',
            'portals.type_id as portal_type_id',
            'portals.is_admin',
            'portals.updated_by',
            'portals.created_by',
            'portals.created_at',
            'portals.updated_at',
            'portals.identifier',
        )
        ->from('portals as portals')
        ->join('individualprovider_location_map AS iplp', function ($join)  {
            $join->
                // on('iplp.location_user_id', '=', 'portals.user_id')
                On('iplp.user_id', '=', 'portals.user_id');
        })
        // ->join('emp_location_map AS elm', 'elm.location_user_id', '=', 'iplp.location_user_id')
        ->leftJoin('portal_types AS cm_pt', 'cm_pt.id', '=', 'portals.type_id')
        ->leftJoin('users AS u', 'u.id', '=', 'iplp.user_id')
        // ->leftJoin('user_ddpracticelocationinfo AS pli', 'pli.user_id', '=', 'elm.location_user_id')
        // ->whereRaw('cm_iplp.user_id = cm_portals.user_id')
        ->groupBy( 'portals.id', 'iplp.user_id')
        ->get()->toArray();

        $providerInserArr = [];
        foreach ($providerPortal as $key => $provider) {
           $providerInserArr[]=[
            'user_id'=>$provider->portal_user_id,
            'mapping_type'=>'provider',
            'user_name'=>$provider->username,
            'password'=>$provider->password,
            'type_id'=>$provider->portal_type_id,
            'notes'=>$provider->notes,
            'report'=>$provider->report,
            'for_credentialing'=>$provider->for_credentialing,
            'is_admin'=>$provider->is_admin,
            'updated_by'=>$provider->updated_by,
            'created_by'=>$provider->created_by,
            'created_at'=>$provider->created_at,
            'updated_at'=>$provider->updated_at,
            'identifier'=>$provider->identifier,
           ];

        }
        
        $allPortalsId = array_column($providerPortal,'portal_id');
        $facilityPortal= DB::table('portals')->select(
            DB::raw("AES_DECRYPT(cm_pli.practice_name, '$key') AS name"),
            'cm_pt.name AS portal',
            'portals.user_name AS username',
            'cm_pt.link AS link',
            'portals.password',
            'portals.notes',
            'portals.report',
            'portals.for_credentialing',
            'portals.id AS portal_id',
            'portals.user_id AS portal_user_id',
            'portals.type_id as portal_type_id',
            'portals.is_admin',
            'portals.updated_by',
            'portals.created_by',
            'portals.created_at',
            'portals.updated_at',
            'portals.identifier',
        )
        ->from('portals as portals')
        ->whereNotIn('portals.id',$allPortalsId)
        ->join('user_ddpracticelocationinfo AS pli', 'pli.user_id', '=', 'portals.user_id')
        // ->leftJoin('emp_location_map AS elm', 'elm.location_user_id', '=', 'portals.user_id')
        ->leftJoin('portal_types AS cm_pt', 'cm_pt.id', '=', 'portals.type_id')
        // ->leftJoin('users AS cmu', 'cmu.id', '=', 'elm.location_user_id')
        // ->whereColumn('elm.location_user_id', '=', 'portals.user_id')
        ->groupBy(//'elm.location_user_id', 
            'portals.id')
        ->get()->toArray();

        $facilityInsArr = [];
        foreach ($facilityPortal as $key => $facility) {
           $facilityInsArr[]=[
            'user_id'=>$facility->portal_user_id,
            'mapping_type'=>'facility',
            'user_name'=>$facility->username,
            'password'=>$facility->password,
            'type_id'=>$facility->portal_type_id,
            'notes'=>$facility->notes,
            'report'=>$facility->report,
            'for_credentialing'=>$facility->for_credentialing,
            'is_admin'=>$facility->is_admin,
            'updated_by'=>$facility->updated_by,
            'created_by'=>$facility->created_by,
            'created_at'=>$facility->created_at,
            'updated_at'=>$facility->updated_at,
            'identifier'=>$facility->identifier,
           ];

        }
        
        $mergreBothPortal = [...$facilityInsArr,...$providerInserArr];
        $mergreBothPortal = array_chunk($mergreBothPortal,150);
        foreach ($mergreBothPortal as $key => $value) {
           Portal::insert($value);
        }
        return [
            'total_portal'=>DB::table('portals')->count(),
            'portal'=>DB::table('portals')->get(),
            'update_total'=>Portal::count(),
            'update_portal'=>DB::table('portals')->get(),
        ];


    }
    
}
