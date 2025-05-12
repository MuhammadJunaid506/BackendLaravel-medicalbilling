<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Traits\Utility;

class UploadController extends Controller
{
    use Utility;
    //
    public function uploadFile(Request $request) {
       
        // echo $root = $_SERVER["DOCUMENT_ROOT"];
        echo "Faheem this is working fine.";
        $file = $request->file('file');
        // $result = $this->uploadFile($request->has('file') ? $file : NULL,"credentialing","222");
        // $this->printR($result,true);
        //get filename with extension
        $filenamewithextension = $request->file('file')->getClientOriginalName();

        //get filename without extension
        $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

        //get file extension
        $extension = $request->file('file')->getClientOriginalExtension();

        //filename to store
        $filenametostore = $filename.'_'.uniqid().'.'.$extension;
        $result = $this->uploadMyFile($filenametostore,$request->file('file'),"credentialing/222/activityLog/1",);
        $this->printR($result,true);
        //Upload File to external server
        $directoryExist = Storage::disk('ftp')->exists("test/222");
        if(!$directoryExist) {
            echo "created:".Storage::disk('ftp')->makeDirectory("test/222",0775, true);
        }
        // echo $directoryExist;
        // exit;
        Storage::disk('ftp')->put("test/222/".$filenametostore, fopen($request->file('file'), 'r+'));  
        echo "done with upload file"; 
    }
}
