<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;

class TemplatesController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * add the loi template
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    public function addLOITemplate(Request $request) {
        
        $request->validate([
            "template_name"    => "required",
            "template_text"    => "required",
            "template_regards" => "required"
        ]);

        $templateName       = $request->get("template_name");
        $templateText       = $request->get("template_text");
        $templateRegards    = $request->get("template_regards");
        
        $id = DB::table("loi_templates")->insertGetId([
            "template_name"     => $templateName,
            "template_text"     => $templateText,
            "template_regards"  => $templateRegards,
            "created_at"        => $this->timeStamp(),
        ]);

        return $this->successResponse(['id' => $id], "success");
    }
    /**
     * get the loi template
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    public function getLOITemplates(Request $request) {
        
        $templates = DB::table("loi_templates")
        
        ->paginate($this->cmperPage);

        return $this->successResponse(['templates' => $templates], "success");
    }
    /**
     * update the loi template
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    public function updateLOITemplates($id,Request $request) {
        
        // $templateName   = $request->get("template_name");
        
        // $templateDetail = $request->get("template_detail");
        $templateName       = $request->get("template_name");
        $templateText       = $request->get("template_text");
        $templateRegards    = $request->get("template_regards");
        
        $update = DB::table("loi_templates")
        ->where("id","=",$id)
        ->update(
            [
            "template_name"     => $templateName,
            "template_text"     => $templateText,
            "template_regards"  => $templateRegards
            ]);

        return $this->successResponse(['update' => $update], "success");
    }
    /**
     * delete the loi template
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    public function deleteLOITemplates($id,Request $request) {
        $del = DB::table("loi_templates")
        ->where("id","=",$id)
        ->delete();

        return $this->successResponse(['is_del' => $del], "success");
    }

}
