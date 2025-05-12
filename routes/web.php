<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UtilityFunc;
use App\Http\Controllers\Invoice;
use App\Http\Controllers\UploadController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get("/save/pdf",[UtilityFunc::class,"viewPdf"]);

Route::get("/generate/recurring/invoice",[Invoice::class,"generateMonthlyInvoice"]);

Route::get("/invoice/notification",[Invoice::class,"sendInvoiceReminderEmail"]);

Route::get("/generate/recurring/notification",[Invoice::class,"generateRecuringNotification"]);

Route::post("/upload",[UploadController::class,"uploadFile"]);


