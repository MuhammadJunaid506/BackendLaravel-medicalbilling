<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\CapabilityActionController;
use App\Http\Controllers\Api\CapabilityController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\PayerController;
use App\Http\Controllers\Api\CredentialingController;
use App\Http\Controllers\Api\CredentialingActivityLogController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\ThirdpartyController;
use App\Http\Controllers\Api\DiscoverydocumentController;
use App\Http\Controllers\SendEmail;
use App\Http\Controllers\UtilityFunc;
use App\Http\Controllers\StripePayment;
use App\Http\Controllers\Invoice;
use App\Http\Controllers\UserCommonFunc;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => "CMAuth",'prefix' => 'v1'], function () {
    // exit("hi");
    Route::apiResource('/users',UserController::class);
    
    Route::apiResource('/companies',CompanyController::class);
    
    Route::apiResource('/roles',RoleController::class);

    Route::apiResource('/insurances',InsuranceController::class);

    Route::apiResource('/capabilityactions',CapabilityActionController::class);

    Route::apiResource('/capabilities',CapabilityController::class);

    Route::apiResource('/providers',ProviderController::class);

    Route::apiResource('/payers',PayerController::class);

    Route::apiResource('/credentialing',CredentialingController::class);

    Route::apiResource('/credentialing/activity/logs',CredentialingActivityLogController::class);

    Route::apiResource('/contracts',ContractController::class);

    Route::get('/npi/data',[ThirdpartyController::class,"fetchNPIData"]);

    Route::apiResource('/discoverydocuments',DiscoverydocumentController::class);

    Route::post('/send/contract-discoverydocument',[SendEmail::class,"sendContractDiscoveryDocumentEmail"]);

    Route::post('/send/contract-invoice',[SendEmail::class,"sendContractInvoiceEmail"]);

    Route::get("/view/email/contract-discoverydocument/{providerId}",[UtilityFunc::class,"viewContractDiscoveryDocument"]);

    Route::get("/view/email/contract-invoice/{providerId}",[UtilityFunc::class,"viewContractInvoice"]);

    Route::get("/client/view/contract/{contractToken}",[UtilityFunc::class,"viewContract"]);

    Route::put("/client/save/contract/{contractToken}",[UtilityFunc::class,"saveContract"]);

    Route::get("/client/view/discovery-document/{ddToken}",[UtilityFunc::class,"viewDiscoveryDocument"]);

    Route::get("/client/view/invoice/{invoiceToken}/{invoiceNumber}",[UtilityFunc::class,"viewInvoice"]);

    Route::post("/add/w9from",[UtilityFunc::class,"addW9Form"]);

    Route::post("/view/w9from",[UtilityFunc::class,"viewW9Form"]);

    Route::post("/stripe/payment",[StripePayment::class,"makePayment"]);

    Route::post("/create/invoice",[Invoice::class,"createInvoice"]);

    Route::get("/invoice",[Invoice::class,"fetchInvoice"]);

    Route::get("/prepare/invoice",[Invoice::class,"invoiceBasicData"]);

    Route::post("/upload/dd/attachment",[UtilityFunc::class,"uploadAttachmentFile"]);

    Route::post("/client/discoverydocuments/{ddToken}",[UtilityFunc::class,"clientDiscoveryDocument"]);

    Route::post("/add/client/discoverydocuments/{ddToken}",[UtilityFunc::class,"addClientDsicoveryDocument"]);
    
    Route::delete("/delete/client/discoverydocuments/{ddToken}/{id}",[UtilityFunc::class,"deleteClientDsicoveryDocument"]);

    Route::get("/view/client/discoverydocuments/{ddToken}",[UtilityFunc::class,"viewClientDsicoveryDocument"]);

    Route::get("/discovery/token",[UtilityFunc::class,"discoveryToken"]);

    Route::post("/client/send/discoverydocument",[SendEmail::class,"clientSendDiscoveryDocument"]);

    Route::get("/adminstration/users",[UserCommonFunc::class,"fetchOperationalSupervisorUsers"]);

    Route::get("/adminstration/users/search",[UserCommonFunc::class,"searchAdminstrationUsers"]);

    Route::get("/adminstration/users/team-lead-member",[UserCommonFunc::class,"fetchTeamLeadAndMember"]);

    Route::get("/payment/history",[Invoice::class,"fetchInvoicesHistory"]);

    Route::post("/assign/provider",[UserCommonFunc::class,"assignProvider"]);

    Route::post("/assign/provider/credentialingtasks",[UserCommonFunc::class,"assignCredentialingTasks"]);

    Route::post("/assign/provider/bluk/credentialingtasks",[UserCommonFunc::class,"assignBlukCredentialingTasks"]);

    Route::get("/notifications",[UserCommonFunc::class,"fetchNotifications"]);

    Route::post("/add/approve/task/notification",[UserCommonFunc::class,"createTaskApproveNotification"]);

    Route::get('/user/task',[UserCommonFunc::class,"credentialingUserTask"]);

    Route::get('/providers/recurring/invoices',[UserCommonFunc::class,"providersRecuringInvoices"]);

    Route::get('/filter/credentialing/taks',[UserCommonFunc::class,"filterCredentialingTasks"]);

    Route::get('/copy/data',[UtilityFunc::class,"copyInsurancesData"]);

    Route::post('/add/addendum/data/{ddToken}',[UtilityFunc::class,"addAddendumData"]);

    Route::get('/fetch/states-cities/data',[UtilityFunc::class,"fetchStatesCitiesData"]);

    Route::post('/create/provider/credentials',[SendEmail::class,"createProviderCredentials"]);

    Route::post('/delete/member/provider',[UtilityFunc::class,"deleteMemberProvider"]);

    Route::get('/user/discoverytoken',[UtilityFunc::class,"fetchUserDiscoveryToken"]);

    
    
});

Route::group(['prefix' => 'v1/admin/'], function () {

    Route::post("register",[AdminController::class,"adminRegister"]);
});

// Route::group(['middleware' => "cors",'prefix' => 'v1/'], function () {
    
    Route::post("login",[AdminController::class,"doAdminLogin"]);
    
    Route::post("forgot/password",[ForgotPasswordController::class,"store"]);

    Route::post("reset/password",[ForgotPasswordController::class,"update"]);
    
    Route::post("create/provider/credentials",[UserController::class,"createProviderCredentials"]);
// });
Route::post("/add/provider",[UtilityFunc::class,"addProviderData"]);