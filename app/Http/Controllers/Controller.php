<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    /**
     * print the data with good format
     * @param $data 
     */
    function printR($data=[],$isExit=false) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        if($isExit)exit;
    }
}
