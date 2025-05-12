<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CptCodeTypes;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use DB;
class CptCodeTypesController extends Controller
{
    use ApiResponseHandler,Utility;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->has('smart_search') && $request->smart_search !='') {
            $search = $request->get('smart_search');
            $cptCodes = CptCodeTypes::select("id","cpt_code","description","status",
            DB::raw("DATE_FORMAT(status_date, '%m/%d/%Y') AS  status_date"),"created_at","updated_at")
            ->where('cpt_code','LIKE','%'.$search.'%')
            ->orWhere('description','LIKE','%'.$search.'%')
            ->orderBy('id', 'DESC')
            ->paginate($this->cmperPage);;
        }
        else {
            $cptCodes = CptCodeTypes::select("id","cpt_code","description","status",
            DB::raw("DATE_FORMAT(status_date, '%m/%d/%Y') AS  status_date"),"created_at","updated_at")
            ->orderBy('id', 'DESC')
            ->paginate($this->cmperPage);
        }
        return $this->successResponse(['cpt_code' => $cptCodes],'success');
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
            "cpt_code"       => "required",
            "description"    =>   "required",
           
        ]);

        try {
            
            $cptTypeData = [

            "cpt_code"            => $request->cpt_code, 
            "description"         => $request->description,
 
        ];

            $id = cptcodetypes::insertGetId($cptTypeData);

            return $this->successResponse(["id" => $id ],"success");
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
        //
        $updateDate = $request->all();
        if($request->has('status')) {
            if($request->get('status') == 1) {
                $updateData = ['status' => 1,'status_date' => NULL];
                $isUpdate = CptCodeTypes::where('id',$id)->update($updateData);
            }
            else {
                $updateData = ['status' => 0,'status_date' => $request->get('status_date')];
                $isUpdate = CptCodeTypes::where('id',$id)->update($updateData);
            }
        }
        else
            $isUpdate = CptCodeTypes::where('id',$id)->update($updateDate);

        return $this->successResponse(["is_update" => $isUpdate ],"success");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $del = CptCodeTypes::where('id',$id)->delete();
        return $this->successResponse(["is_del" => $del ],"success");
    }
}
