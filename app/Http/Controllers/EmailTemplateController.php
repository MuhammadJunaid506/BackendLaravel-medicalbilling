<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Http\Traits\ApiResponseHandler;
use App\Http\Traits\Utility;
use App\Http\Controllers\DocumentsController;

class EmailTemplateController extends Controller
{
    use ApiResponseHandler,Utility;
    /**
     * add the new email template
     * 
     *  @param \Illuminate\Http\Request $request
     *  @param \Illuminate\Http\Response
     */
    function addEmailTemplate(Request $request ) {
        
        $isFor = "credentials_email";
        
        $hasAlready = EmailTemplate::where('is_for',$isFor)
        
        ->count();
        $id = 0;
        if($hasAlready == 0) {
            
            $id = EmailTemplate::insertGetId([
                "is_for"    => $isFor,
                "email"     => $request->email,
                "message"   => $request->message,
                "created_at" => $this->timeStamp()
            ]);
           
        }
        else {
            $id = EmailTemplate::where('is_for',$isFor)
            ->update([
                "email"     => $request->email,
                "message"   => $request->message,
                "updated_at" => $this->timeStamp()
            ]);
        }
        /**
         * Send the expiration Email
         */
        $docObj = new DocumentsController();

        $docObj->sendDocumentExpiryEmail();
        
        $docObj = NULL;
        
        return $this->successResponse(["id" => $id],"success");


    }
    /**
     * fetch the email template
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response
     */
    function getTemplate(Request $request) {

        $isFor = "credentials_email";
        
        $template = EmailTemplate::where('is_for',$isFor)
        
        ->first();

        return $this->successResponse(["template" => $template],"success");
    }
}
