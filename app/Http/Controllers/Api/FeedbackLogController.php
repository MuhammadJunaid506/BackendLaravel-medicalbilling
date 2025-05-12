<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\Utility;
use App\Http\Traits\ApiResponseHandler;
use App\Models\FeedbackLog;
use App\Models\User;
use App\Models\Attachments;
use DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FeedbackLogController extends Controller
{
    use ApiResponseHandler,Utility;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      

    try {

        //$userId = $request->user_id;
  
        $feedbacksLog = FeedbackLog::select("feedback_logs.*","user_id","a.field_value as filename",
        // DB::raw("CONCAT(storage.eclinicassist.com/images/profile,a.field_value) as filename"),
        DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"))
        ->leftJoin('users', 'users.id', '=', 'feedback_logs.user_id')
        ->leftJoin('attachments as a', function($join) {
            $join->on('a.entity_id', '=', 'feedback_logs.id')
            ->where('a.entities', '=', 'feedback_log');
        })
        ->whereRaw("cm_feedback_logs.id = (SELECT MAX(id) FROM `cm_feedback_logs`as fdl WHERE fdl.task_id = cm_feedback_logs.task_id)")
        
        
        ->orderBy("feedback_logs.created_at","DESC")
        
        ->get();
       
        $licenseAttachments = [];
        $url = env("STORAGE_PATH");
        $nestedFolders = "feedbacks";
        
        if(count($feedbacksLog) > 0 ) {
            $feedbackAllArr = $this->stdToArray($feedbacksLog);
            $feedbackIds = array_column($feedbackAllArr,"id");
            $attachments = Attachments::where("entities","=","feedback_log")->whereIn("entity_id",$feedbackIds)->get();
           
            if(count($attachments)) {
                foreach($attachments as $attachment) {
                    $attachment->file_url = $url.$nestedFolders."/".$attachment->entity_id."/".$attachment->field_value;
                    $licenseAttachments[$attachment->entity_id] = $attachment;
                }
            }
        }
        $taskHistory = [];
        $taskHistoryAttachments = [];
        $feedbacksLogArr = [];
        
        if(count($feedbacksLog) > 0 ) {
            foreach($feedbacksLog as $feedback) {
                $createdAt =  is_null($feedback->created_at) ?  NULL : $this->humanReadableTimeDifference($feedback->created_at);
                $updatedAt =  is_null($feedback->updated_at)  ? NULL : $this->humanReadableTimeDifference($feedback->updated_at);
                
                $feedback->created_human_read = $createdAt;
                $feedback->updated_human_read = $updatedAt;
                
                array_push($feedbacksLogArr,$feedback);

                $licenseAttachmentsChild = [];
                $feedbacksLogChildArr = [];
                $feedbacksLogChild = FeedbackLog::select("feedback_logs.*","user_id","a.field_value as filename",
                // DB::raw("CONCAT(storage.eclinicassist.com/images/profile,a.field_value) as filename"),
                DB::raw("CONCAT(cm_users.first_name, ' ',cm_users.last_name) as user_name"))
                ->leftJoin('users', 'users.id', '=', 'feedback_logs.user_id')
                ->leftJoin('attachments as a', function($join) {
                    $join->on('a.entity_id', '=', 'feedback_logs.id')
                    ->where('a.entities', '=', 'feedback_log');
                })
                ->whereRaw("cm_feedback_logs.id < (SELECT MAX(id) FROM `cm_feedback_logs`as fdl WHERE fdl.task_id = cm_feedback_logs.task_id)")
                
                ->where("feedback_logs.id","<>",$feedback->id)
                
                ->where("feedback_logs.task_id","=",$feedback->task_id)

                ->orderBy("feedback_logs.updated_at","DESC")
                
                ->get();
                if(count($feedbacksLogChild)) {
                    $feedbackAllArr = $this->stdToArray($feedbacksLogChild);
                    $feedbackIds = array_column($feedbackAllArr,"id");
                    $attachments = Attachments::where("entities","=","feedback_log")->whereIn("entity_id",$feedbackIds)->get();
                
                    if(count($attachments)) {
                        foreach($attachments as $attachment) {
                            $attachment->file_url = $url.$nestedFolders."/".$attachment->entity_id."/".$attachment->field_value;
                            $licenseAttachmentsChild[$attachment->entity_id] = $attachment;
                        }
                    }
                }
                
                if(count($feedbacksLogChild)) {
                    foreach($feedbacksLogChild as $feedbackLogChild) {
                        $createdAt =  is_null($feedbackLogChild->created_at) ?  NULL : $this->humanReadableTimeDifference($feedbackLogChild->created_at);
                        $updatedAt =  is_null($feedbackLogChild->updated_at)  ? NULL : $this->humanReadableTimeDifference($feedbackLogChild->updated_at);
                        
                        $feedbackLogChild->created_human_read = $createdAt;
                        $feedbackLogChild->updated_human_read = $updatedAt;
                        array_push($feedbacksLogChildArr,$feedbackLogChild);
                    }
                }

                $taskHistoryAttachments[$feedback->id] = $licenseAttachmentsChild;
                $taskHistory[$feedback->id] = $feedbacksLogChildArr;
            }
        }

       

        return $this->successResponse(['feedBack' => $feedbacksLogArr ,'attachment' => $licenseAttachments,
        'task_history' => $taskHistory,'tasks_history_attachments' => $taskHistoryAttachments],"success",200);
    }

    catch (\Throwable $exception) {
            
        return $this->errorResponse([],$exception->getMessage(),500);
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
            "user_id"        =>   "required",
            "task"           =>   "required",
            "status"         =>   "required"
        ]);
       
        $code =  strtolower(Str::random(11));
        $withHashCode = "#". $code;
        try {
            
            $feedBackData = [

            "user_id"            => $request->user_id, 
            "task_id"           => $withHashCode,
            "task"               => $request->task,
            "status"             => $request->status,
            "created_at"             => $this->timeStamp()
 
        ];

        $newId = FeedbackLog::insertGetId($feedBackData);

            
        if($request->hasFile('file')) {
            $file = $request->file("file");
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));
            $addFileData = [
                "entities"     => "feedback_log",
                "entity_id"     => $newId,
                "field_key"     => "feedback file",
                "field_value"   => $fileName,
              
               
            ];
            $this->uploadMyFile($fileName,$file,"feedbacks/".$newId);
            $this->addData("attachments",$addFileData);
        }
        else {
            if($newId > 0) {

                $whereFile=[
                    ["entities","=","feedback_log"],
                    ["entity_id","=",$newId]
                  
                ];
                $hasFile = $this->fetchData("attachments", $whereFile, 1, []);
                if(is_object($hasFile)) {
                    $addFileData = [
                        "entities"     => "feedback_log",
                        "entity_id"     => $newId,
                        "field_key"     => $hasFile->field_key,
                        "field_value"   => $hasFile->field_value
                    ];
                }
            }
        }
      

        return $this->successResponse(["id" => $newId],"added successfully.");



            // return $this->successResponse(["id" => $id ],"success");
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
       
        $inputData = $request->all();

        $updateData = [
            "user_id"       => $inputData["user_id"],
            "task"          => $inputData["task"],
            "status"        => $inputData["status"],
            "task_id"       => $inputData["task_id"],
            "created_at"    => $this->timeStamp(),
            "updated_at"    => $this->timeStamp()
        ];
        $isUpdate = FeedbackLog::insertGetId($updateData);
        if($request->file("file")) {

            $file = $request->file("file");
           
            $fileName = uniqid() . '_' . trim($this->removeWhiteSpaces($file->getClientOriginalName()));
         
            // $ext= explode(".",$fileName)[1];
            // $fileName = $id."_".$ext;
            
            
            $uploadRes = $this->uploadMyFile($fileName,$file,"feedbacks/".$isUpdate);
            //$this->printR($uploadRes,true);
            if(isset($uploadRes["is_uploaded"]) && $uploadRes["is_uploaded"]) {
                // $whereFeedback = [
                //     ["entities","=","feedback_log"],
                //     ["entity_id","=",$id]
                // ];


                // $feedbackExist = $this->fetchData("attachments",$whereFeedback,1,[]);
                // if(is_object($feedbackExist)) {
                //     // $addFeedbackImage = [
                //     //     "entities" => "feedback_log",
                //     //     "entity_id" => $id,
                //     //     "field_key" => "feedback file",
                //     //     "field_value" => $fileName,
                //     //     "updated_at" => $this->timeStamp()
                //     // ];
                //     // $this->updateData("attachments",$whereFeedback,$addFeedbackImage);
                // }
                // else 
                {
                    $addFeedbackImage = [
                        "entities" => "feedback_log",
                        "entity_id" => $isUpdate,
                        "field_key" => "feedback file",
                        "field_value" => $fileName,
                        "created_at" => $this->timeStamp()
                    ];
                    $this->addData("attachments",$addFeedbackImage);
                }
            }
        }
       
        // $this->printR($updateData,true);
        // $isUpdate = FeedbackLog::where("id", $id)->update($updateData);
      
        return $this->successResponse(['id' => $id, 'is_update' => $isUpdate], "feedback update successfully.");
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

            $wherefeedback = [
                ["entities","=","feedback_log"],
                ["entity_id","=",$id]
            ];

            
            $feedbackExist = $this->fetchData("attachments",$wherefeedback,1,[]);
            if(is_object($feedbackExist)) {
                $fileName = $feedbackExist->field_value;
                $this->deleteFile("feedbacks/".$fileName);//delete the file from storage
                $isDelete  = FeedbackLog::where("id", $id)->delete();
                $this->deleteData("attachments",$wherefeedback);//delete the image attachement table
                return $this->successResponse(["is_delete" => $isDelete ],"success",200);
            }
            else {
                $isDelete  = FeedbackLog::where("id", $id)->delete();
                return $this->successResponse(["is_delete" => $isDelete ],"success",200);
            }

    
        } 
        
        catch (\Throwable $exception) {

            return $this->errorResponse([], $exception->getMessage(), 500);
        }
    }
}
