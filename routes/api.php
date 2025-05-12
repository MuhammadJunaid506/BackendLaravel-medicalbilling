<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App\Http\Middleware\UserManagementMiddleware;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\RouteRoleMapController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\InsuranceCoverageController;
use App\Http\Controllers\Api\CapabilityActionController;
use App\Http\Controllers\Api\CapabilityController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\PayerController;
use App\Http\Controllers\Api\PortalTypeController;
use App\Http\Controllers\Api\CptCodeTypesController;
use App\Http\Controllers\Api\UserCompanyMapController;
use App\Http\Controllers\Api\IdentifierTypesController;
use App\Http\Controllers\Api\CredentialingController;
use App\Http\Controllers\Api\CredentialingActivityLogController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\ThirdpartyController;
use App\Http\Controllers\Api\DiscoverydocumentController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\LicenseTypesController;
use App\Http\Controllers\Api\FeedbackLogController;
use App\Http\Controllers\Api\ArStatusController;
use App\Http\Controllers\Api\ArRemarksController;
use App\Http\Controllers\Api\AccountReceivableController;
use App\Http\Controllers\SendEmail;
use App\Http\Controllers\UtilityFunc;
use App\Http\Controllers\StripePayment;
use App\Http\Controllers\Invoice;
use App\Http\Controllers\UserCommonFunc;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\Dashboard;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\Api\ARLogsController;
use App\Http\Controllers\ARReportsController;
use App\Http\Controllers\Api\ShelterController;
use App\Http\Controllers\Api\RevenueCycleStatusController;
use App\Http\Controllers\Api\RevenueCycleRemarksController;
use App\Http\Controllers\Api\OnBoardController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\eCAToolsController;
use App\Http\Controllers\Api\SMTSettingsController;
use App\Http\Controllers\TemplatesController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\LeadLogsController;
use App\Http\Controllers\Api\LeadSettingsController;
use App\Http\Controllers\Api\PayerDynamicFieldsController;
use App\Http\Controllers\ManualDBOperation;
use App\Http\Controllers\Api\BuisnessContactsController;
use App\Http\Controllers\Api\CredentialingDashboardController;
use App\Http\Controllers\Api\CredentialController;
use App\Http\Controllers\Api\LeadStatusController;
use App\Http\Controllers\Api\LeadReportController;
use App\Models\CredentialCategory;
use App\Http\Controllers\Api\LeadDashboardController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\RevenueDashboardController;
use App\Http\Controllers\HealthcareSoftwareTypes;
use App\Http\Controllers\HealthcareSoftwareNames;
use App\Http\Controllers\CronJobController;

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



Route::post('ar-update-claims',[ManualDBOperation::class,'updateARCliams']);
Route::post('billing-update-claims',[ManualDBOperation::class,'updateBillingCliams']);
Route::post('extract-claims',[ManualDBOperation::class,'extractClaims']);
Route::post('fix-claims',[ManualDBOperation::class,'fixBillingReports']);
Route::post('update-claims',[ManualDBOperation::class,'updateBillingClaims']);
Route::get('update-dates',[ManualDBOperation::class,'fetchClaims']);


Route::group(['middleware' => "CMAuth",'prefix' => 'v1'], function () {

    Route::apiResource('appointment',AppointmentController::class);
    
    Route::apiResource('healthcare-software-types',HealthcareSoftwareTypes::class);

    Route::apiResource('healthcare-software-names',HealthcareSoftwareNames::class);
    
    Route::get('leads',[LeadController::class,"index"]);

    Route::get('leads/basic/dashboard',[LeadDashboardController::class,"fetchLeadBasicDashboard"]);

    Route::get('leads/graph/dashboard',[LeadDashboardController::class,"fetchLeadGraphDashboard"]);

    Route::get('leads/appointment/dashboard',[LeadDashboardController::class,"fetchLeadsAppointmentsDashboard"]);
    
    Route::post('upload/directory/profile',[OnBoardController::class,"uploadDirectoryProfileImage"]);

    Route::post('upload/lead/profile/image',[LeadController::class,"uploadLeadProfileImage"]);

    Route::post('upload/owner/profile/image',[OnBoardController::class,"uploadOwnerProfileImage"]);

    Route::post('create/new/leads',[LeadController::class,"store"]);

    Route::post('record/lead/user/activity',[LeadController::class,"reacordLeadActivity"]);

    Route::get('fetch/lead/data',[LeadController::class,"show"]);

    Route::get('speciality/dropdown',[LeadController::class,"specialityDropdown"]);

    Route::put('update/lead/data/{id}',[LeadController::class,"update"]);

    Route::post('proccess/won/lead/{leadId}',[LeadController::class,"proccessWonLead"]);
    //Lead Attachments
    Route::get('lead/logs/attachments/{id}',[LeadLogsController::class,'fetchLeadAttachment']);


    Route::apiResource('lead/logs',LeadLogsController::class);

    Route::apiResource('/users',UserController::class);
    //update the profile user with this technique because user's image can not be sent into put verb
    Route::post('/users/update/{id}',[UserController::class,'update']);

    Route::post('add/loi/templates',[TemplatesController::class,'addLOITemplate']);

    Route::get('loi/templates',[TemplatesController::class,'getLOITemplates']);

    Route::delete('loi/templates/{id}',[TemplatesController::class,'deleteLOITemplates']);

    Route::put('loi/templates/{id}',[TemplatesController::class,'updateLOITemplates']);

    Route::apiResource('/companies',CompanyController::class);

    Route::apiResource('/roles',RoleController::class);

    Route::apiResource('/insurances',InsuranceController::class);

    Route::apiResource('/capabilityactions',CapabilityActionController::class);

    Route::apiResource('/capabilities',CapabilityController::class);

    Route::apiResource('/providers',ProviderController::class);

    Route::apiResource('/payers',PayerController::class);

    Route::apiResource('/credentialing',CredentialingController::class);

    Route::apiResource('/licenses',LicenseController::class);

    Route::post("/licenses/update/{id}",[LicenseController::class,"update"]);

    Route::apiResource('/licensestype',LicenseTypesController::class);

    Route::apiResource('/identifiertypes',IdentifierTypesController::class);

    Route::apiResource('/revenue/status',RevenueCycleStatusController::class);

    Route::apiResource('/revenue/remarks',RevenueCycleRemarksController::class);

    Route::apiResource('/portals',PortalController::class);

    Route::apiResource('/portalstype',PortalTypeController::class);

    Route::apiResource('/cptcodetype',CptCodeTypesController::class);

    Route::apiResource('/assign/user/company',UserCompanyMapController::class);

    Route::apiResource('/route',RouteController::class);

    Route::apiResource('/shelters',ShelterController::class);

    Route::apiResource('/insurance_coverage',InsuranceCoverageController::class);

    Route::apiResource('/ar/logs',ARLogsController::class);

    Route::get('combined/ar-billing/logs',[ARLogsController::class,"fetchCombinedARBillingLogs"]);

    Route::apiResource('/feedback/logs',FeedbackLogController::class);

    Route::post('/feedback/logs/update/{id}',[FeedbackLogController::class,"update"]);

    Route::apiResource('/arstatus',ArStatusController::class);

    Route::apiResource('/ar/remarks',ArRemarksController::class);

    Route::apiResource('/account/receivable',AccountReceivableController::class);

    Route::get('/ar/practice-facility-payers-status',[AccountReceivableController::class,"getPracticeFacilityPayersDD"]);

    Route::get('/ar/status',[AccountReceivableController::class,"getAccountRecieveableStatus"]);

    Route::get('/ar/footer/data',[AccountReceivableController::class,"arFooterData"]);

    Route::post('/insurance_coverage/update/{id}',[InsuranceCoverageController::class,"update"]);

    Route::apiResource('/routerolemap',RouteRoleMapController::class);

    Route::apiResource('/credentialing/activity/logs',CredentialingActivityLogController::class);

    Route::get('/credentialing/task/avg',[CredentialingActivityLogController::class,"credTaskAVG"]);

    Route::apiResource('/contracts',ContractController::class);

    Route::get('/npi/data',[ThirdpartyController::class,"fetchNPIData"]);

    Route::post('/verify/address',[ThirdpartyController::class,"verifyAddress"]);

    Route::get('/validate/speciality',[ThirdpartyController::class,"validateGroupSepciality"]);

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

    Route::get("/adminstration/credentialing/users",[UserCommonFunc::class,"fetchCredUsers"]);

    Route::get("/payment/history",[Invoice::class,"fetchInvoicesHistory"]);

    Route::post("/assign/provider",[UserCommonFunc::class,"assignProvider"]);

    Route::post("/assign/provider/credentialingtasks",[UserCommonFunc::class,"assignCredentialingTasks"]);

    Route::post("/assign/provider/bluk/credentialingtasks",[UserCommonFunc::class,"assignBlukCredentialingTasks"]);

    Route::post("/assign/credentialing/task",[CredentialingController::class,"assignCredentialingTask"]);

    Route::get("/notifications",[UserCommonFunc::class,"fetchNotifications"]);

    Route::post("/request/for/approval",[UserCommonFunc::class,"requestForApprove"]);

    Route::get('/user/task',[UserCommonFunc::class,"credentialingUserTask"]);

    Route::get('/providers/recurring/invoices',[UserCommonFunc::class,"providersRecuringInvoices"]);

    Route::get('/filter/credentialing/taks',[UserCommonFunc::class,"filterCredentialingTasks"]);


    Route::post('/add/addendum/data',[UtilityFunc::class,"addAddendumData"]);

    Route::get('/fetch/states-cities/data',[UtilityFunc::class,"fetchStatesCitiesData"]);

    Route::get('/fetch/license/types',[UtilityFunc::class,"fetchLicenseTypesData"]);

    Route::get('/fetch/user/license/types',[DocumentsController::class,"getUserLicenseTypes"]);

    Route::post('/create/provider/credentials',[SendEmail::class,"createProviderCredentials"]);

    Route::post('/delete/member/provider',[UtilityFunc::class,"deleteMemberProvider"]);

    Route::get('/user/discovery/data',[UtilityFunc::class,"fetchUserDiscoveryData"]);

    Route::post('/add/provider',[UtilityFunc::class,"addProovider"]);

    Route::get('/enrollment/stats',[StatsController::class,"enrollmentStats"]);

    Route::get('/enrollment/status/stats',[StatsController::class,"statusEnrollmentStats"]);

    Route::get('/attachments',[UserCommonFunc::class,"fetchAttachments"]);

    Route::get('profile/attachments',[UserCommonFunc::class,"fetchProfileAttachments"]);

    Route::delete('delete/attachment/{id}',[DocumentsController::class,"deleteAttachment"]);

    Route::post('upload/miscellaneous/attachments',[DocumentsController::class,"uploadMiscellaneousAttachments"]);

    Route::post('add/portals',[UserCommonFunc::class,"addPortals"]);

    Route::get('fetch/portallogs',[UserCommonFunc::class,"fetchPortalLogs"]);

    Route::post('/check/file/permission',[UserCommonFunc::class,"chkFilePermissions"]);

    Route::get("/directory/providers",[ProviderController::class,"fetchDirectoryUsers"]);

    Route::put("/provider/profile/{userId}",[UserCommonFunc::class,"updateProviderProfile"]);

    Route::get("arremarks/map",[ArRemarksController::class,'fatchRemarksMap']);

    Route::get("/settings/revenue-cycle-status",[RevenueCycleRemarksController::class,'fatchRevenueRemarksMap']);

    Route::get("/creds/attachments",[UserCommonFunc::class,"fetchCredsAttachments"]);

    Route::get("/portal/types",[UserCommonFunc::class,"portalsTypes"]);

    Route::get("profile/{token}",[UserController::class,"fetchPrfileAginstToken"]);

    Route::post("update/profile/{id}",[UserController::class,"updateProfile"]);

    Route::post("add/userrolmap",[UserController::class,"addUserRolMap"]);

    Route::get("fetch/userrolmap",[UserController::class,"fatchUserRolMap"]);

    Route::get("fetch/users-with-roles",[UserController::class,"usersWithRoles"]);

    Route::get("fetch/users-with-company",[UserCompanyMapController::class,"fetchCompanyUsers"]);

    Route::put("update/userrolmap/{id}",[UserController::class,"updateUserRolMap"]);

    Route::post("deactive/userrolmap/{id}",[UserController::class,"deActiveUserRolMap"]);

    Route::post("update/portals/{portalId}/{userId}",[PortalController::class,"updatePortal"]);

    Route::get("/fetch/creds/filters",[UserCommonFunc::class,"fetchCredsFilters"]);

    Route::post("/fetch/location/providers",[UserCommonFunc::class,"fetchSelectedLocationProviders"]);

    Route::post("/fetch/practice/locattions",[UserCommonFunc::class,"fetchSelectPracticeLocations"]);

    Route::get("/dashboard/stats",[Dashboard::class,"credentailingStatistics"]);

    Route::get("billing/dashboard/stats",[Dashboard::class,"billingStatistics"]);

    Route::get("posting/dashboard/stats",[Dashboard::class,"postingStatistics"]);

    //Route::get("facility/payer/avg",[Dashboard::class,"facilityPayerAverages"]);

    Route::post("/initiate/eca/task",[UtilityFunc::class,"initiateECATask"]);

    Route::get("eca/tasks",[UtilityFunc::class,"fetchECATasks"]);

    Route::put("forcredentailing/{id}",[UtilityFunc::class,"forCredentailing"]);

    Route::post("on-off-board",[UtilityFunc::class,"onOffBoard"]);

    Route::post('report/credentialing/facility/status',[ReportController::class,'credentialingFacilityStatusReport']);

    Route::post('report/credentialing/provider/status',[ReportController::class,'credentialingProviderStatusReport']);

    Route::get('report/credentialing/active-inactive',[ReportController::class,'fetchActiveInActiveReport']);

    Route::get('report/credentialing/comprehensive',[ReportController::class,'fetchComprehensiveReport']);

    Route::get('report/credentialing/specific/comprehensive',[ReportController::class,'fetchComprehensiveSpecificReport']);

    Route::get('report/license/validity',[ReportController::class,'fetchLicenseValdityReport']);

    Route::get('creds/tasks/filters',[UtilityFunc::class,'credentialingTaskFilters']);

    Route::get('fetch/status/based/practices',[UserCommonFunc::class,'fetchPractices']);

    Route::get('fetch/active/practices',[UserCommonFunc::class,'getActivePractices']);

    Route::post('fetch/facility/providers',[UserCommonFunc::class,'fetchProviders']);

    Route::post('fetch/practice/facility',[UserCommonFunc::class,'fetchFacility']);

    Route::get('fetch/creds/status',[UserCommonFunc::class,'fetchCredentialingStatus']);

    Route::post('reimbursement/credentialing/addfee',[CredentialingController::class,'addReimbursementFee']);

    Route::get("reimbursement/credentialing/fetchdata",[CredentialingController::class,'fetchReimbursementFee']);

    Route::get("reimbursement/credentialing/cptcode",[CredentialingController::class,'fetchCptcodeType']);

    Route::post("reimbursement/update",[CredentialingController::class,'updateReimbursement']);

    Route::get("fetch/approved/task",[CredentialingController::class,'lastApprovedTask']);

    Route::get("reports/routes",[RouteController::class,'reportRoutesList']);

    Route::get("setting/routes",[RouteController::class,'settingsRoutesList']);

    Route::get("dropdowns",[UtilityFunc::class,'getDropdownsData']);

    Route::post("add/provider",[UtilityFunc::class,'addProvider']);

    Route::get("directory/list",[OnBoardController::class,'fetchDirectoryData']);

    Route::get("get/practice/id",[OnBoardController::class,'getPracticeId']);

    Route::post("onboard/add/form",[OnBoardController::class,'addOnboardForm']);

    Route::post("create/new/practice",[OnBoardController::class,'createPractice']);

    Route::post("add/more/facility",[OnBoardController::class,'addMoreFacility']);

    Route::post("facility/link/providers",[OnBoardController::class,'facilityLinkProvider']);

    Route::post("chk/unique/provider",[OnBoardController::class,'chkUniqueProvider']);

    Route::post("add/more/provider",[OnBoardController::class,'addMoreProvider']);

    Route::get("fetch/practice/form",[OnBoardController::class,'fetchPracticeForm']);

    Route::get("fetch/facility/form",[OnBoardController::class,'fetchFacilityForm']);

    Route::get("fetch/provider/form",[OnBoardController::class,'fetchProviderForm']);

    Route::get("fetch/owner/data",[OnBoardController::class,'fetchOwnerData']);

    Route::get("fetch/owner/affliations",[OnBoardController::class,'fetchOwnerAffiliations']);

    Route::put("modify/practice/{practiceId}",[OnBoardController::class,'updatePractice']);

    Route::put("modify/facility/{facilityId}",[OnBoardController::class,'updateFacility']);

    Route::put("modify/provider/{providerId}",[OnBoardController::class,'updateProvider']);

    Route::put("modify/owner/data/{ownerId}",[OnBoardController::class,'updateOwnerData']);

    Route::post("check/owner/exist",[OnBoardController::class,'chkOwnerExists']);

    Route::delete("delete/facility/{practiceId}/{facilityId}",[OnBoardController::class,'deleteFacility']);

    Route::delete("delete/provider/{practiceId}/{facilityId}/{providerId}",[OnBoardController::class,'deleteProvider']);

    Route::delete("delete/practice/owner/{ownerId}",[OnBoardController::class,'deletePracticeOwner']);

    Route::get("fetch/onboarding/practice/data",[OnBoardController::class,"fetchOnBoardingPracticeData"]);

    Route::get("fetch/onboarding/each/form/data",[OnBoardController::class,"fetchOnBoardingEachFormData"]);

    Route::get("onboard/dropdown/list",[OnBoardController::class,'getOnBoardDropdownList']);

    Route::post('onboard/validate/unique/attributes',[OnBoardController::class,'chkUniqueAttributes']);

    Route::post('onboard/validate/facility',[OnBoardController::class,'validateFacility']);

    Route::get('onboard/validate/provider',[OnBoardController::class,'getPracticeFacilityAndProvider']);

    Route::post('directory/active-inactive/practice',[OnBoardController::class,'activeInactiveDirectoryPracitce']);

    Route::post('directory/active-inactive/facility',[OnBoardController::class,'activeInactiveDirectoryFacility']);

    Route::post('directory/active-inactive/provider',[OnBoardController::class,'activeInactiveDirectoryProvider']);

    Route::get('directory/facility/providers',[OnBoardController::class,'fetchFacilityProviders']);

    Route::get('fetch/practice/facility-provider',[OnBoardController::class,'validateProvider']);

    Route::get('facility/info',[OnBoardController::class,'facilityInfo']);

    Route::get('provider/info',[OnBoardController::class,'providerInfo']);

    Route::get('provider/attachments',[OnBoardController::class,'providerAttachments']);

    Route::get('facility/attachments',[OnBoardController::class,'facilityAttachments']);

    Route::post('add/practice/log',[OnBoardController::class,'addPracticeLog']);

    Route::get('practice/loi/log',[OnBoardController::class,'fetchPracticeLOILog']);

    Route::get('practice/profile/log',[OnBoardController::class,'fetchPracticeProfileLog']);

    Route::get('facility/profile/log',[OnBoardController::class,'fetchFacilityProfileLog']);

    Route::get('provider/profile/log',[OnBoardController::class,'fetchProviderProfileLog']);

    Route::get('provider/form/share/log',[OnBoardController::class,'fetchProviderSharedFormLogs']);

    Route::get('facility/form/share/log',[OnBoardController::class,'fetchFacilitySharedFormLogs']);

    Route::get('practice/users',[OnBoardController::class,'fetchPracticeUsers']);

    Route::put('update/practice/users',[OnBoardController::class,'updatePracticeUser']);

    Route::get("facility/enrollments",[StatsController::class,'getLocationEnrollmentData']);

    Route::get("facility/provider/enrollments",[StatsController::class,'facilityProviderEnrollments']);


    Route::get("provider/enrollments",[StatsController::class,'providerEnrollment']);

    Route::get("paginate/enrollments",[StatsController::class,'enrollmentPaginate']);

    Route::get("verify/otp",[UserController::class,'verifyOtp']);

    Route::get("resend/otp",[UserController::class,'resendOtp']);

    Route::get("fetch/profile/practices",[UtilityFunc::class,'fetchProfilePractices']);

    Route::get("fetch/practice/info",[UtilityFunc::class,'fetchPracticeInfo']);

    Route::post("provider/credentialing/task",[CredentialingController::class,'providerCredentialingTask']);

    Route::get("dashboard/credentials",[DocumentsController::class,'getCredentialsDashboard']);

    Route::get("user/credentials/dashboard",[DocumentsController::class,'credentialsDashboard']);

    Route::get("directory/docs/dashboard",[Dashboard::class,'directoryDocsDashboards']);

    Route::get("directory/practice/dashboard",[Dashboard::class,'directoryPracticeDashboard']);

    Route::get("directory/facility/dashboard",[Dashboard::class,'directoryFacilitiesDashboards']);

    Route::get("directory/provider/dashboard",[Dashboard::class,'directoryProviderDashboards']);

    Route::get("directory/leads/dashboard",[Dashboard::class,'directoryLeadsDashboards']);

    Route::get("directory/events/dashboard",[Dashboard::class,'directoryEventsDashboard']);

    Route::get("directory/validity/dashboard",[Dashboard::class,'validityDashboard']);

    Route::get("directory/expired/report/provider",[Dashboard::class,'fetchProviderExpiredDocs']);

    Route::get("directory/expired/report/facility",[Dashboard::class,'fetchFacilityExpiredDocs']);

    Route::get("directory/expiringsoon/report/provider",[Dashboard::class,'fetchProviderExpiringSoonDocs']);

    Route::get("directory/expiringsoon/report/facility",[Dashboard::class,'fetchFacilityExpiringSoonDocs']);

    Route::get("directory/missingdocs/report/facility",[Dashboard::class,'fetchFacilityMissingDocs']);

    Route::get("directory/missingdocs/report/provider",[Dashboard::class,'fetchProviderMissingDocs']);

    Route::get("fetch/miscellaneous/{id}",[DocumentsController::class,'fetchMiscellaneousDcoument']);

    Route::post("update/miscellaneous/{id}",[DocumentsController::class,'updateMiscellaneousDcoument']);

    Route::get("credentialing/task/logs",[UtilityFunc::class,'fatchCredentialingTaskLogs']);

    Route::post("add/sublicense/type",[LicenseTypesController::class,"addSubLicenseType"]);

    Route::get("/fatch/sublicense/type",[LicenseTypesController::class,"fatchSubLicenseType"]);

    Route::put("/update/sublicense/type/{id}",[LicenseTypesController::class,"updateSubLicenseType"]);

    Route::delete("/destroy/sublicense/type/{id}",[LicenseTypesController::class,"destroySubLicenseType"]);

    Route::get("license/types",[LicenseTypesController::class,"fetchLicensesTypes"]);

    Route::get("app/usage/report",[ReportController::class,"getAppUsageReport"]);

    Route::get("app/sessions/report",[ReportController::class,"getAppSessionReport"]);

    Route::post("link/sublicense/type",[LicenseTypesController::class,"linkSublicenseType"]);

    Route::post("upload/user/cv",[DocumentsController::class,"uploadUserCV"]);

    Route::post("update/user/cv/{id}",[DocumentsController::class,"updateUserCV"]);

    Route::post("add/user/clia",[DocumentsController::class,"addUserClia"]);

    Route::post("update/user/clia/{id}",[DocumentsController::class,"updateUserClia"]);

    Route::post("bank-voided/add",[DocumentsController::class,"addBankVoided"]);

    Route::post("bank-voided/update/{id}",[DocumentsController::class,"updateBankVoided"]);

    Route::post("update/special/documents/{id}/{type}",[DocumentsController::class,"specialTypesDocumentsUpdate"]);

    Route::post("add/special/documents",[DocumentsController::class,"addSpecialTypeDocuments"]);

    Route::get("fetch/special/documents",[DocumentsController::class,"fetchSpecialTypesDocuments"]);

    Route::get("fetch/special/document/{id}/{type}",[DocumentsController::class,"fetchSpecificSpecialTypesDocument"]);

    Route::delete("del/special/document/{id}/{type}/{userId}",[DocumentsController::class,"delSpecialTypeDoc"]);

    Route::post("share/docs",[DocumentsController::class,"shareDocument"]);

    Route::get("app/users/docs",[DocumentsController::class,"userDocuments"]);

    Route::post("update/bluck/selection",[AccountReceivableController::class,"updateBluckSelection"]);

    Route::post("update/eob/data",[AccountReceivableController::class,"updateEOBRelatedData"]);

    Route::get('facility/providers',[ProviderController::class,"fetchFacilityProviders"]);

    Route::get('facility/basic/information',[ProviderController::class,"fetchFacilityBasicInformation"]);

    Route::get("share/document/email",[DocumentsController::class,"shareDocument"]);

    Route::post("add/email/template",[EmailTemplateController::class,"addEmailTemplate"]);

    Route::get("email/template",[EmailTemplateController::class,"getTemplate"]);

    Route::get('ar/attachments/logs',[ARLogsController::class,"arAttachmentsLogs"]);

    Route::get('ar/dashboard',[AccountReceivableController::class,"arDashboardStats"]);

    Route::post('ar/bluck/eob',[AccountReceivableController::class,"updateBluckEOBData"]);

    Route::get('ar/trend/report',[ARReportsController::class,"arTrendReport"]);

    Route::get('ar/distribution-by-reason/report',[ARReportsController::class,"arDistributionByReason"]);

    Route::post('ar/distribution-by-employee/report',[ARReportsController::class,"arDistributionByUser"]);

    Route::get('ar/distribution-by-payer/report',[ARReportsController::class,"arDistributionByPayer"]);

    Route::get('collection-expected-revenue',[ARReportsController::class,"generateCollectionExpectedRevenueReport"]);

    Route::get('collection-expected-revenue-payer',[ARReportsController::class,"fetchCollectionExpectedRevenueReportByPayer"]);

    Route::get("caqhs",[DocumentsController::class,"fetchCaqhs"]);

    Route::get("caqhs/{id}",[DocumentsController::class,"fetchSpecificCaqh"]);

    Route::post("caqhs",[DocumentsController::class,"storeCaqhs"]);

    Route::post("caqhs/update/{id}",[DocumentsController::class,"updateCaqhs"]);

    Route::post("update/password",[UserController::class,"updateUserPassword"]);

    Route::get("/management/users/get-roles", [UserManagementController::class, 'getAllRoles']);

    Route::post("/management/users/get-practicies", [UserManagementController::class, 'getAllPracticies']);

    Route::post("/management/users/get-facilities", [UserManagementController::class, 'getAllFacilities']);

    Route::get("/management/users/{company_id}/page", [UserManagementController::class, 'index']);

    Route::post("/management/users/filter", [UserManagementController::class, 'filter']);

    Route::get("/management/users/getById/{userId}", [UserManagementController::class, 'getUserById']);

    Route::post("/management/users/add-user", [UserManagementController::class, 'addUser']);

    Route::post("/management/users/update", [UserManagementController::class, 'update']);

    Route::get("/management/users/changePassword/{userId}", [UserManagementController::class, 'changePassword']);

    Route::post("/management/users/status", [UserManagementController::class, 'updateStatus']);

    Route::post("/management/users/lock", [UserManagementController::class, 'updateLockStatus']);

    Route::post("/management/users/fetch-user-practicies", [UserManagementController::class, 'fetchUserPracticies']);

    Route::post("/management/users/fetch-all-practicies", [UserManagementController::class, 'fetchAllPracticies']);

    Route::post("/management/users/add-user-to-facility", [UserManagementController::class, 'addUserToFacility']);

    Route::post("/management/users/delete-user-from-facility", [UserManagementController::class, 'deleteUserFromFacility']);

    Route::post("/management/users/delete-bulk-user-facilities", [UserManagementController::class, 'deleteBulkFacilities']);

    Route::post("/management/users/profile-image", [UserManagementController::class, 'updateProfileImage']);

    Route::get("/management/users/get-all-privileges", [UserManagementController::class, 'getAllPrivileges']);

    Route::get("/management/users/get-providers-by-facility-id/{facilityId}", [UserManagementController::class, 'getProvidersByFacilityId']);

    Route::post("/management/users/add-role-privileges", [UserManagementController::class, 'rolePrivileges']);

    Route::post("/management/users/update-priviliges-by-user", [UserManagementController::class, 'updatePriviligesByUser']);

    Route::post("/management/users/get-user-facility-privileges", [UserManagementController::class, 'getUserFacilityPrivileges']);

    Route::post("/management/users/reset-user-facility-privileges", [UserManagementController::class, 'resetUserFacilityPrivileges']);

    Route::post("/management/users/get-role-privileges", [UserManagementController::class, 'getRolePrivileges']);

    Route::get("/management/users/get-user-generic-privileges/{roleId}", [UserManagementController::class, 'getUserGenericPrivileges']);

    Route::post("/management/users/update-role-privileges", [UserManagementController::class, 'updateRolePrivileges']);

    Route::post("/management/users/get-atomic-privilege", [UserManagementController::class, 'getAtomicPrivilege']);

    Route::get('/management/users/get-login-data-by-user-id/{userId}', [UserManagementController::class, 'getLoginDataByUserId']);

    Route::get('/management/users/get-system-activity-by-user-id/{userId}', [UserManagementController::class, 'getSystemActivityByUserId']);

    Route::get('/management/users/get-portals-edit-by-user-id/{userId}', [UserManagementController::class, 'getProviderPortalEditByUserId']);

    Route::get('/management/users/get-directory-admin-by-user-id/{userId}', [UserManagementController::class, 'getDirectoryAdminByUserId']);

    Route::get('/management/users/get-directory-admin-denied-by-user-id/{userId}', [UserManagementController::class, 'getDirectoryAdminDeniedByUserId']);

    Route::get('/management/users/get-directory-access-by-user-id/{userId}', [UserManagementController::class, 'getDirectoryAccessByUserId']);

    Route::get('/management/users/get-directory-access-denied-by-user-id/{userId}', [UserManagementController::class, 'getDirectoryAccessDeniedByUserId']);

    Route::get('/management/users/get-profile-view-by-user-id/{userId}', [UserManagementController::class, 'getProfileViewByUserId']);

    Route::get('/management/users/get-profile-view-denied-by-user-id/{userId}', [UserManagementController::class, 'getProfileViewDeniedByUserId']);

    Route::get('/management/users/reset-user-generic-privileges/{userId}', [UserManagementController::class, 'resetUserGenericPrivileges']);

    Route::get('/management/users/get-user-navigation/{userId}', [UserManagementController::class, 'getUserNavigation']);

    Route::post('/management/users/update-user-navigation', [UserManagementController::class, 'updateUserNavigation']);

    Route::get('/management/users/get-role-users/{roleId}', [UserManagementController::class, 'getRoleWiseUsers']);

    Route::get("/payers/avg/comparision", [ReportController::class, 'payerAverageComparisonReport']);

    Route::get("/provider/payers/avg/comparision", [ReportController::class, 'payerAverageComparisonProviderReport']);

    Route::get('/practice-facility/providers',[UserCommonFunc::class,'fetchPracticeFacilityProviders']);

    Route::get("creds/payer/avgs",[CredentialingController::class, 'credsPayerAvg']);

    Route::get("creds/payer/avgs/years/list",[CredentialingController::class, 'credsPayerAvgYears']);

    Route::get("facility/payer/avg",[Dashboard::class,"facilityPayerAverages"]);

    Route::post("approve/attachment/dea",[OnBoardController::class,"approveProviderDeaAttachments"]);

    Route::post("approve/attachment/cv",[OnBoardController::class,"approveProviderCvAttachments"]);

    Route::delete("del/provider/attachment/{id}",[OnBoardController::class,"deleteProviderAttachment"]);

    Route::delete("del/facility/attachment/{id}",[OnBoardController::class,"deleteFacilityAttachment"]);


    Route::post("create/facility/w9-form",[eCAToolsController::class,"createFacilityW9"]);

    Route::post("create/provider/w9-form",[eCAToolsController::class,"createProviderW9"]);

    Route::post("delete/w9-form",[eCAToolsController::class,"delW9File"]);

    Route::get('/fetch/portal',[PortalController::class,'fetchPortal']);

    Route::get('/fetch/portal/users',[PortalController::class,'fetchPortalUsers']);

    Route::get('/fetch/portal/providers',[PortalController::class,'fetchPortalProviders']);
    Route::get('/fetch/portal/facility',[PortalController::class,'fetchPortalfacility']);
    Route::get('/fetch/portal/createdby',[PortalController::class,'fetchPortalCreatedBy']);
    Route::get('/fetch/portal/updatedby',[PortalController::class,'fetchPortalUpdatedBy']);


    Route::controller(OnBoardController::class)->group(function () {
        Route::prefix('practice/servicetypes/dropdowns')->group(function () {
            Route::get('/', 'getPracticeServiceTypeDropdowns');
            Route::post('/create', 'storePracticeServiceTypeDropdowns');
            Route::put('/update/{id}', 'updatePracticeServiceTypeDropdowns');
            Route::delete('/delete/{id}', 'deletePracticeServiceTypeDropdowns');
        });
    });
    Route::controller(LeadSettingsController::class)->group(function () {
        Route::prefix('referredby/dropdowns')->group(function () {
            Route::get('/', 'getReferredbydropdowns');
            Route::get('/fetch/all', 'getReferredbyAllDD');
            Route::post('/create', 'storeReferredbydropdowns');
            Route::put('/update/{id}', 'updateReferredbydropdowns');
            Route::delete('/delete/{id}', 'deleteReferredbydropdowns');
        });
        Route::prefix('leadtypes/dropdowns')->group(function () {
            Route::get('/', 'getleadTypesdropdowns');
            Route::get('/fetch/all', 'fetchLeadTypeAllDD');
            Route::post('/create', 'storeLeadTypesdropdowns');
            Route::put('/update/{id}', 'updateLeadTypesdropdowns');
            Route::delete('/delete/{id}', 'deleteLeadTypesdropdowns');
        });
    });
    
    Route::get("lead/dropdowns",[LeadSettingsController::class,"leadFilterDropdown"]);

    Route::controller(BuisnessContactsController::class)->group(function () {
        Route::prefix('business/contacts')->group(function () {
            Route::post('/', 'addBuisnessContact');
            Route::get('/', 'getBuisnessContact'); // Assuming fetches all contacts
            Route::put('/{id}', 'updateBuisnessContact');
            Route::delete('/{id}', 'deleteBuisnessContact'); // Corrected method
            Route::get('/seach/practice', "searchPracticeContact");
            Route::get('/seach/provider', "searchProviderContact");
            Route::get('/details', "getBuisnessContactDetails");
            Route::post('/upload/profileimage', "uploadContactProfileImage");
            Route::get('/affiliations', "fetchContactAffiliations");
        });
    });
    Route::controller(PayerDynamicFieldsController::class)->group(function () {
        Route::post('/payer/user/fields', 'payerUserFields');
        Route::get('/payer/linkedcolumn', 'payerLinkedColumn');
        Route::post('/add/payer/linkedcolumn', 'addPayerLinkedColumn');
        Route::get('/payer/user/missingcolumns', 'payerUserMissingColumn');
    });
    Route::controller(CredentialingDashboardController::class)->group(function () {
        Route::get('/fetch/credentialing/dashboard', 'credentialingDashboard');

        Route::get('/fetch/credentialing/dashboard/todayfollowups', 'credentialingDashboardTodayFollowups');
        Route::get('/fetch/credentialing/dashboard/todayfollowups/detail', 'credentialingDashboardTodayFollowupsDetail');

        Route::get('/fetch/credentialing/dashboard/expiredfollowup', 'credentialingDashboardExpiredFollowup');
        Route::get('/fetch/credentialing/dashboard/expiredfollowup/detail', 'credentialingDashboardExpiredFollowupDetail');

        Route::get('/fetch/credentialing/dashboard/expiredenrollments', 'credentialingDashboardExpiredEnrollments');
        Route::get('/fetch/credentialing/dashboard/expiredenrollments/detail', 'credentialingDashboardExpiredEnrollmentsDetail');

        Route::get('/fetch/credentialing/dashboard/approvalpercentage', 'credentialingDashboardApprovalPercentage');

        Route::get('/fetch/credentialing/dashboard/approvalenrollmenttoday', 'credentialingDashboardAapprovalEnrollmentToday');
        Route::get('/fetch/credentialing/dashboard/approvalenrollmenttoday/detail', 'credentialingDashboardAapprovalEnrollmentTodayDetail');

        Route::get('/fetch/credentialing/dashboard/incidentreportenrollments', 'credentialingDashboardincidentReportEnrollments');
        Route::get('/fetch/credentialing/dashboard/incidentreportenrollments/detail', 'credentialingDashboardincidentReportEnrollmentsDetail');

        Route::get('/fetch/credentialing/dashboard/payer/average/day', 'credentialingDashboarPayerAverageDays');
        Route::get('/fetch/credentialing/dashboard/tasklogs', 'credentialingDashboarTasklogs');
        Route::get('/fetch/credentialing/dashboard/tasklogs/{id}', 'credentialingDashboarTasklogsDetail');

        Route::post('/credentialing/task/overall/status', 'credentialingtaskOverallStatus');

        Route::get('/fetch/credential/flags', 'credentialFlags');
        Route::get('/fetch/noenrollment/list', 'fetchNoEnrollemnts');
        Route::get('/fetch/progrees/byfacility/list', 'progressByFacilityList');
        Route::get('/fetch/progrees/payer/list', 'progressByPayerList');


    });
    Route::controller(CredentialController::class)->group(function () {
        /**
         * provider credentials routes
         */
        Route::post('/credential/add/category', 'addProviderCredentailCategory');
        Route::get('/credential/category', 'getProviderCredentialCategory');
        Route::post('/provider/credentials/add-new/license-type', 'addProviderCredentailSubCategory');
        Route::get('/provider/credentials/license-type', 'getProviderCredentailSubCategory');
        Route::post('/provider/add/fields', 'addProviderCustomFields');
        Route::get('/provider/fetch/fields', 'getProviderCustomFields');
        Route::get('/provider/credential/categories', 'getLicenseCategories');
        Route::put('/provider/update/license-type/{id}', 'updateSubCategory');
        Route::post('/provider/credential/add/field', 'addProviderCredentialField');
        Route::put('/provider/credential/update/field/{id}', 'updateProviderCredentialField');
        Route::post('/provider/credential/add', 'addProviderCredential');
        Route::get('/provider/credential', 'fetchProviderCredentials');
        Route::post('/provider/credential/update', 'updateProviderCredentials');
        Route::post('/provider/credential/fields/order/update', 'updateCrdentialFieldsOrder');
        /**
         * facility credentials routes
         */
        Route::post('/facility/credential/add/category', 'addFacilityCredentailCategory');
        Route::get('/facility/credential/categories', 'getFacilityCredentialCategories');
        Route::post('/facility/credential/add/sub/category', 'addFacilityCredentailSubCategory');
        Route::get('/facility/credential/sub/categories', 'getFacilityCredentailSubCategory');
        Route::post('/facility/add/fields', 'addFacilityCustomFields');
        Route::get('/facility/fetch/fields', 'getFacilityCustomFields');
        Route::get('/facility/fetch/fields', 'getFacilityCustomFields');
        Route::put('/facility/update/license-type/{id}', 'updateFacilitySubCategory');
        Route::post('/facility/credential/add/field', 'addFacilityCredentialField');
        Route::put('/facility/credential/update/field/{id}', 'updateFacilityCredentialField');
        Route::post('/facility/credential/add', 'addFacilityCredential');
        Route::get('/facility/credential', 'fetchFacilityCredentials');
        Route::post('/facility/credential/update', 'updateFacilityCredentials');
        Route::post('/facility/credential/fields/order/update', 'updateFacilityCrdentialFieldsOrder');
        
        
        
        
    });
    Route::controller(AccountReceivableController::class)->group(function () {
        Route::prefix('ar/dashboard')->group(function () {
            Route::get('/aging_stats', 'agingStats');
            Route::get('/practice_stats', 'practiceStats');
            Route::get('/assigned_users_stats', 'assignedUserStats');
            Route::get('/timely_stats', 'timelyStats');
            Route::get('/status_wise_summary', 'statusWiseSummary');
        });
    });
    
    Route::controller(LeadStatusController::class)->group(function () {
        Route::prefix('leadstatus')->group(function () {
            Route::get('/', 'index');
            Route::post('/store', 'store');
            Route::put('/update/{id}', 'update');
            Route::delete('/delete/{id}', 'delete');
        });
    });
    Route::controller(LeadReportController::class)->group(function () {
        Route::prefix('lead')->group(function () {
            Route::get('/comprehensive/report', 'leadComprehensiveReport');
        });
    });
    Route::controller(RevenueDashboardController::class)->group(function () {
        Route::prefix('revenue')->group(function () {
            // Route::get('/collection/summary', 'collectionSummary');
            // Route::get('/ar/nonclosed/payer/claims', 'nonClosedPayerClaims');
            // Route::get('/ar/expected/payment/amount/payer', 'expectedPaymentAmountPayer');
            // Route::get('/payer/avergae/comaprison', 'payerAvergaeComparison');
            // Route::get('/denials/practice/facility', 'denialsPracticeAndFacility');
            // Route::get('/denials/payer/percenatge', 'denialsPayerPercentage');
            Route::get('/active/practice', 'activePratices');
            // Route::get('/ar/outstandingamount', 'outstandingAmount');
            Route::get('/ar/trend', 'arTrendReport');
        });
    });
});

Route::group(['middleware' => "CMAuth2",'prefix' => 'v2'], function () {

    // exit("hi");
    Route::apiResource('/users',UserController::class);
    //update the profile user with this technique because user's image can not be sent into put verb
    Route::post('/users/update/{id}',[UserController::class,'update']);

    Route::apiResource('/companies',CompanyController::class);

    Route::apiResource('/roles',RoleController::class);

    Route::apiResource('/insurances',InsuranceController::class);

    Route::apiResource('/capabilityactions',CapabilityActionController::class);

    Route::apiResource('/capabilities',CapabilityController::class);

    Route::apiResource('/providers',ProviderController::class);

    Route::apiResource('/payers',PayerController::class);

    Route::apiResource('/credentialing',CredentialingController::class);

    Route::apiResource('/licenses',LicenseController::class);

    Route::post("/licenses/update/{id}",[LicenseController::class,"update"]);

    Route::apiResource('/licensestype',LicenseTypesController::class);

    Route::apiResource('/identifiertypes',IdentifierTypesController::class);

    Route::apiResource('/revenue/status',RevenueCycleStatusController::class);

    Route::apiResource('/revenue/remarks',RevenueCycleRemarksController::class);

    Route::apiResource('/portals',PortalController::class);

    Route::apiResource('/portalstype',PortalTypeController::class);

    Route::apiResource('/cptcodetype',CptCodeTypesController::class);

    Route::apiResource('/assign/user/company',UserCompanyMapController::class);

    Route::apiResource('/route',RouteController::class);

    Route::apiResource('/shelters',ShelterController::class);

    Route::apiResource('/insurance_coverage',InsuranceCoverageController::class);

    Route::apiResource('/ar/logs',ARLogsController::class);

    Route::get('combined/ar-billing/logs',[ARLogsController::class,"fetchCombinedARBillingLogs"]);

    Route::apiResource('/feedback/logs',FeedbackLogController::class);

    Route::post('/feedback/logs/update/{id}',[FeedbackLogController::class,"update"]);

    Route::apiResource('/arstatus',ArStatusController::class);

    Route::apiResource('/ar/remarks',ArRemarksController::class);

    Route::apiResource('/account/receivable',AccountReceivableController::class);

    Route::get('/ar/practice-facility-payers-status',[AccountReceivableController::class,"getPracticeFacilityPayersDD"]);

    Route::get('/ar/status',[AccountReceivableController::class,"getAccountRecieveableStatus"]);

    Route::post('/insurance_coverage/update/{id}',[InsuranceCoverageController::class,"update"]);

    Route::apiResource('/routerolemap',RouteRoleMapController::class);

    Route::apiResource('/credentialing/activity/logs',CredentialingActivityLogController::class);

    Route::get('/credentialing/task/avg',[CredentialingActivityLogController::class,"credTaskAVG"]);

    Route::apiResource('/contracts',ContractController::class);

    Route::get('/npi/data',[ThirdpartyController::class,"fetchNPIData"]);

    Route::post('/verify/address',[ThirdpartyController::class,"verifyAddress"]);

    Route::get('/validate/speciality',[ThirdpartyController::class,"validateGroupSepciality"]);

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

    Route::get("/adminstration/credentialing/users",[UserCommonFunc::class,"fetchCredUsers"]);

    Route::get("/payment/history",[Invoice::class,"fetchInvoicesHistory"]);

    Route::post("/assign/provider",[UserCommonFunc::class,"assignProvider"]);

    Route::post("/assign/provider/credentialingtasks",[UserCommonFunc::class,"assignCredentialingTasks"]);

    Route::post("/assign/provider/bluk/credentialingtasks",[UserCommonFunc::class,"assignBlukCredentialingTasks"]);

    Route::post("/assign/credentialing/task",[CredentialingController::class,"assignCredentialingTask"]);

    Route::get("/notifications",[UserCommonFunc::class,"fetchNotifications"]);

    Route::post("/request/for/approval",[UserCommonFunc::class,"requestForApprove"]);

    Route::get('/user/task',[UserCommonFunc::class,"credentialingUserTask"]);

    Route::get('/providers/recurring/invoices',[UserCommonFunc::class,"providersRecuringInvoices"]);

    Route::get('/filter/credentialing/taks',[UserCommonFunc::class,"filterCredentialingTasks"]);

    Route::post('/add/addendum/data',[UtilityFunc::class,"addAddendumData"]);

    Route::get('/fetch/states-cities/data',[UtilityFunc::class,"fetchStatesCitiesData"]);

    Route::get('/fetch/license/types',[UtilityFunc::class,"fetchLicenseTypesData"]);

    Route::get('/fetch/user/license/types',[DocumentsController::class,"getUserLicenseTypes"]);

    Route::post('/create/provider/credentials',[SendEmail::class,"createProviderCredentials"]);

    Route::post('/delete/member/provider',[UtilityFunc::class,"deleteMemberProvider"]);

    Route::get('/user/discovery/data',[UtilityFunc::class,"fetchUserDiscoveryData"]);

    Route::post('/add/provider',[UtilityFunc::class,"addProovider"]);

    Route::get('/enrollment/stats',[StatsController::class,"enrollmentStats"]);

    Route::get('/enrollment/status/stats',[StatsController::class,"statusEnrollmentStats"]);

    Route::get('/attachments',[UserCommonFunc::class,"fetchAttachments"]);

    Route::get('profile/attachments',[UserCommonFunc::class,"fetchProfileAttachments"]);

    Route::delete('delete/attachment/{id}',[DocumentsController::class,"deleteAttachment"]);

    Route::post('upload/miscellaneous/attachments',[DocumentsController::class,"uploadMiscellaneousAttachments"]);

    Route::post('add/portals',[UserCommonFunc::class,"addPortals"]);

    Route::post('/check/file/permission',[UserCommonFunc::class,"chkFilePermissions"]);

    Route::get("/directory/providers",[ProviderController::class,"fetchDirectoryUsers"]);

    Route::put("/provider/profile/{userId}",[UserCommonFunc::class,"updateProviderProfile"]);

    Route::get("arremarks/map",[ArRemarksController::class,'fatchRemarksMap']);

    Route::get("/settings/revenue-cycle-status",[RevenueCycleRemarksController::class,'fatchRevenueRemarksMap']);

    Route::get("/creds/attachments",[UserCommonFunc::class,"fetchCredsAttachments"]);

    Route::get("/portal/types",[UserCommonFunc::class,"portalsTypes"]);

    Route::get("profile/{token}",[UserController::class,"fetchPrfileAginstToken"]);

    Route::post("update/profile/{id}",[UserController::class,"updateProfile"]);

    Route::post("add/userrolmap",[UserController::class,"addUserRolMap"]);

    Route::get("fetch/userrolmap",[UserController::class,"fatchUserRolMap"]);

    Route::get("fetch/users-with-roles",[UserController::class,"usersWithRoles"]);

    Route::get("fetch/users-with-company",[UserCompanyMapController::class,"fetchCompanyUsers"]);

    Route::put("update/userrolmap/{id}",[UserController::class,"updateUserRolMap"]);

    Route::post("deactive/userrolmap/{id}",[UserController::class,"deActiveUserRolMap"]);

    Route::post("update/portals/{portalId}/{userId}",[PortalController::class,"updatePortal"]);

    Route::get("/fetch/creds/filters",[UserCommonFunc::class,"fetchCredsFilters"]);

    Route::post("/fetch/location/providers",[UserCommonFunc::class,"fetchSelectedLocationProviders"]);

    Route::post("/fetch/practice/locattions",[UserCommonFunc::class,"fetchSelectPracticeLocations"]);

    Route::get("/dashboard/stats",[Dashboard::class,"credentailingStatistics"]);

    Route::get("billing/dashboard/stats",[Dashboard::class,"billingStatistics"]);

    Route::get("posting/dashboard/stats",[Dashboard::class,"postingStatistics"]);

    Route::post("/initiate/eca/task",[UtilityFunc::class,"initiateECATask"]);

    Route::get("eca/tasks",[UtilityFunc::class,"fetchECATasks"]);

    Route::put("forcredentailing/{id}",[UtilityFunc::class,"forCredentailing"]);

    Route::post("on-off-board",[UtilityFunc::class,"onOffBoard"]);

    Route::post('report/credentialing/facility/status',[ReportController::class,'credentialingFacilityStatusReport']);

    Route::post('report/credentialing/provider/status',[ReportController::class,'credentialingProviderStatusReport']);

    Route::get('report/credentialing/active-inactive',[ReportController::class,'fetchActiveInActiveReport']);

    Route::get('report/credentialing/comprehensive',[ReportController::class,'fetchComprehensiveReport']);

    Route::get('report/credentialing/specific/comprehensive',[ReportController::class,'fetchComprehensiveSpecificReport']);

    Route::get('report/license/validity',[ReportController::class,'fetchLicenseValdityReport']);

    Route::get('creds/tasks/filters',[UtilityFunc::class,'credentialingTaskFilters']);

    Route::get('fetch/status/based/practices',[UserCommonFunc::class,'fetchPractices']);

    Route::get('fetch/active/practices',[UserCommonFunc::class,'getActivePractices']);

    Route::post('fetch/facility/providers',[UserCommonFunc::class,'fetchProviders']);

    Route::post('fetch/practice/facility',[UserCommonFunc::class,'fetchFacility']);

    Route::get('fetch/creds/status',[UserCommonFunc::class,'fetchCredentialingStatus']);

    Route::post('reimbursement/credentialing/addfee',[CredentialingController::class,'addReimbursementFee']);

    Route::get("reimbursement/credentialing/fetchdata",[CredentialingController::class,'fetchReimbursementFee']);

    Route::get("reimbursement/credentialing/cptcode",[CredentialingController::class,'fetchCptcodeType']);

    Route::post("reimbursement/update",[CredentialingController::class,'updateReimbursement']);

    Route::get("fetch/approved/task",[CredentialingController::class,'lastApprovedTask']);

    Route::get("reports/routes",[RouteController::class,'reportRoutesList']);

    Route::get("setting/routes",[RouteController::class,'settingsRoutesList']);

    Route::get("dropdowns",[UtilityFunc::class,'getDropdownsData']);

    Route::post("add/provider",[UtilityFunc::class,'addProvider']);

    Route::get("directory/list",[OnBoardController::class,'fetchDirectoryData']);

    Route::get("get/practice/id",[OnBoardController::class,'getPracticeId']);

    Route::post("onboard/add/form",[OnBoardController::class,'addOnboardForm']);

    Route::post("create/new/practice",[OnBoardController::class,'createPractice']);

    Route::post("add/more/facility",[OnBoardController::class,'addMoreFacility']);

    Route::post("add/more/provider",[OnBoardController::class,'addMoreProvider']);

    Route::get("fetch/practice/form",[OnBoardController::class,'fetchPracticeForm']);

    Route::get("fetch/facility/form",[OnBoardController::class,'fetchFacilityForm']);

    Route::get("fetch/provider/form",[OnBoardController::class,'fetchProviderForm']);

    Route::put("modify/practice/{practiceId}",[OnBoardController::class,'updatePractice']);

    Route::put("modify/facility/{facilityId}",[OnBoardController::class,'updateFacility']);

    Route::put("modify/provider/{providerId}",[OnBoardController::class,'updateProvider']);

    Route::delete("delete/facility/{practiceId}/{facilityId}",[OnBoardController::class,'deleteFacility']);

    Route::delete("delete/provider/{practiceId}/{facilityId}/{providerId}",[OnBoardController::class,'deleteProvider']);

    Route::get("fetch/onboarding/practice/data",[OnBoardController::class,"fetchOnBoardingPracticeData"]);

    Route::get("fetch/onboarding/each/form/data",[OnBoardController::class,"fetchOnBoardingEachFormData"]);

    Route::get("onboard/dropdown/list",[OnBoardController::class,'getOnBoardDropdownList']);

    Route::post('onboard/validate/unique/attributes',[OnBoardController::class,'chkUniqueAttributes']);

    Route::post('onboard/validate/facility',[OnBoardController::class,'validateFacility']);

    Route::get('onboard/validate/provider',[OnBoardController::class,'getPracticeFacilityAndProvider']);

    Route::post('directory/active-inactive/practice',[OnBoardController::class,'activeInactiveDirectoryPracitce']);

    Route::post('directory/active-inactive/facility',[OnBoardController::class,'activeInactiveDirectoryFacility']);

    Route::post('directory/active-inactive/provider',[OnBoardController::class,'activeInactiveDirectoryProvider']);

    Route::get('directory/facility/providers',[OnBoardController::class,'fetchFacilityProviders']);

    Route::get('fetch/practice/facility-provider',[OnBoardController::class,'validateProvider']);

    Route::post('add/practice/log',[OnBoardController::class,'addPracticeLog']);

    Route::get("facility/enrollments",[StatsController::class,'getLocationEnrollmentData']);

    Route::get("facility/provider/enrollments",[StatsController::class,'facilityProviderEnrollments']);

    Route::get("provider/enrollments",[StatsController::class,'providerEnrollment']);

    Route::get("paginate/enrollments",[StatsController::class,'enrollmentPaginate']);

    Route::get("verify/otp",[UserController::class,'verifyOtp']);

    Route::get("resend/otp",[UserController::class,'resendOtp']);

    Route::get("fetch/profile/practices",[UtilityFunc::class,'fetchProfilePractices']);

    Route::get("fetch/practice/info",[UtilityFunc::class,'fetchPracticeInfo']);

    Route::post("provider/credentialing/task",[CredentialingController::class,'providerCredentialingTask']);

    Route::get("dashboard/credentials",[DocumentsController::class,'getCredentialsDashboard']);

    Route::get("user/credentials/dashboard",[DocumentsController::class,'credentialsDashboard']);

    Route::get("fetch/miscellaneous/{id}",[DocumentsController::class,'fetchMiscellaneousDcoument']);

    Route::post("update/miscellaneous/{id}",[DocumentsController::class,'updateMiscellaneousDcoument']);

    Route::get("credentialing/task/logs",[UtilityFunc::class,'fatchCredentialingTaskLogs']);

    Route::post("add/sublicense/type",[LicenseTypesController::class,"addSubLicenseType"]);

    Route::get("/fatch/sublicense/type",[LicenseTypesController::class,"fatchSubLicenseType"]);

    Route::put("/update/sublicense/type/{id}",[LicenseTypesController::class,"updateSubLicenseType"]);

    Route::delete("/destroy/sublicense/type/{id}",[LicenseTypesController::class,"destroySubLicenseType"]);

    Route::get("license/types",[LicenseTypesController::class,"fetchLicensesTypes"]);

    Route::get("app/usage/report",[ReportController::class,"getAppUsageReport"]);

    Route::get("app/sessions/report",[ReportController::class,"getAppSessionReport"]);

    Route::post("link/sublicense/type",[LicenseTypesController::class,"linkSublicenseType"]);

    Route::post("upload/user/cv",[DocumentsController::class,"uploadUserCV"]);

    Route::post("update/user/cv/{id}",[DocumentsController::class,"updateUserCV"]);

    Route::post("add/user/clia",[DocumentsController::class,"addUserClia"]);

    Route::post("update/user/clia/{id}",[DocumentsController::class,"updateUserClia"]);

    Route::post("bank-voided/add",[DocumentsController::class,"addBankVoided"]);

    Route::post("bank-voided/update/{id}",[DocumentsController::class,"updateBankVoided"]);

    Route::post("update/special/documents/{id}/{type}",[DocumentsController::class,"specialTypesDocumentsUpdate"]);

    Route::post("add/special/documents",[DocumentsController::class,"addSpecialTypeDocuments"]);

    Route::get("fetch/special/documents",[DocumentsController::class,"fetchSpecialTypesDocuments"]);

    Route::get("fetch/special/document/{id}/{type}",[DocumentsController::class,"fetchSpecificSpecialTypesDocument"]);

    Route::delete("del/special/document/{id}/{type}/{userId}",[DocumentsController::class,"delSpecialTypeDoc"]);

    Route::post("share/docs",[DocumentsController::class,"shareDocument"]);

    Route::get("app/users/docs",[DocumentsController::class,"userDocuments"]);

    Route::post("update/bluck/selection",[AccountReceivableController::class,"updateBluckSelection"]);

    Route::post("update/eob/data",[AccountReceivableController::class,"updateEOBRelatedData"]);

    Route::get('facility/providers',[ProviderController::class,"fetchFacilityProviders"]);

    Route::get('facility/basic/information',[ProviderController::class,"fetchFacilityBasicInformation"]);

    Route::get("share/document/email",[DocumentsController::class,"shareDocument"]);

    Route::post("add/email/template",[EmailTemplateController::class,"addEmailTemplate"]);

    Route::get("email/template",[EmailTemplateController::class,"getTemplate"]);

    Route::get('ar/attachments/logs',[ARLogsController::class,"arAttachmentsLogs"]);

    Route::get('ar/dashboard',[AccountReceivableController::class,"arDashboardStats"]);

    Route::post('ar/bluck/eob',[AccountReceivableController::class,"updateBluckEOBData"]);

    Route::get('ar/trend/report',[ARReportsController::class,"arTrendReport"]);

    Route::get('ar/distribution-by-reason/report',[ARReportsController::class,"arDistributionByReason"]);

    Route::post('ar/distribution-by-employee/report',[ARReportsController::class,"arDistributionByUser"]);

    Route::get('ar/distribution-by-payer/report',[ARReportsController::class,"arDistributionByPayer"]);

    Route::get('collection-expected-revenue',[ARReportsController::class,"generateCollectionExpectedRevenueReport"]);

    Route::get("caqhs",[DocumentsController::class,"fetchCaqhs"]);

    Route::get("caqhs/{id}",[DocumentsController::class,"fetchSpecificCaqh"]);

    Route::post("caqhs",[DocumentsController::class,"storeCaqhs"]);

    Route::post("caqhs/update/{id}",[DocumentsController::class,"updateCaqhs"]);

    Route::post("update/password",[UserController::class,"updateUserPassword"]);

    Route::get("/management/users/get-roles", [UserManagementController::class, 'getAllRoles']);

    Route::post("/management/users/get-practicies", [UserManagementController::class, 'getAllPracticies']);

    Route::post("/management/users/get-facilities", [UserManagementController::class, 'getAllFacilities']);

    Route::get("/management/users/{company_id}/page", [UserManagementController::class, 'index']);

    Route::post("/management/users/filter", [UserManagementController::class, 'filter']);

    Route::get("/management/users/getById/{userId}", [UserManagementController::class, 'getUserById']);

    Route::post("/management/users/add-user", [UserManagementController::class, 'addUser']);

    Route::post("/management/users/update", [UserManagementController::class, 'update']);

    Route::get("/management/users/changePassword/{userId}", [UserManagementController::class, 'changePassword']);

    Route::post("/management/users/status", [UserManagementController::class, 'updateStatus']);

    Route::post("/management/users/lock", [UserManagementController::class, 'updateLockStatus']);

    Route::post("/management/users/fetch-user-practicies", [UserManagementController::class, 'fetchUserPracticies']);

    Route::post("/management/users/fetch-all-practicies", [UserManagementController::class, 'fetchAllPracticies']);

    Route::post("/management/users/add-user-to-facility", [UserManagementController::class, 'addUserToFacility']);

    Route::post("/management/users/delete-user-from-facility", [UserManagementController::class, 'deleteUserFromFacility']);

    Route::post("/management/users/profile-image", [UserManagementController::class, 'updateProfileImage']);

    Route::get("/management/users/get-all-priviliges", [UserManagementController::class, 'getAllPriviliges']);

    Route::get("/management/users/get-providers-by-facility-id/{facilityId}", [UserManagementController::class, 'getProvidersByFacilityId']);

    Route::post("/management/users/add-role-privileges", [UserManagementController::class, 'rolePrivileges']);

    Route::post("/management/users/update-priviliges-by-user", [UserManagementController::class, 'updatePriviligesByUser']);

    Route::post("/management/users/get-user-facility-privileges", [UserManagementController::class, 'getUserFacilityPrivileges']);

    Route::post("/management/users/reset-user-facility-privileges", [UserManagementController::class, 'resetUserFacilityPrivileges']);

    Route::post("/management/users/get-role-privileges", [UserManagementController::class, 'getRolePrivileges']);

    Route::get("/management/users/get-user-generic-privileges/{roleId}", [UserManagementController::class, 'getUserGenericPrivileges']);

    Route::post("/management/users/update-role-privileges", [UserManagementController::class, 'updateRolePrivileges']);

    Route::post("/management/users/get-atomic-privilege", [UserManagementController::class, 'getAtomicPrivilege']);

    Route::get('/management/users/get-login-data-by-user-id/{userId}', [UserManagementController::class, 'getLoginDataByUserId']);

    Route::get('/management/users/get-system-activity-by-user-id/{userId}', [UserManagementController::class, 'getSystemActivityByUserId']);

    Route::get('/management/users/get-directory-access-by-user-id/{userId}', [UserManagementController::class, 'getDirectoryAccessByUserId']);

    Route::get('/management/users/get-directory-access-denied-by-user-id/{userId}', [UserManagementController::class, 'getDirectoryAccessDeniedByUserId']);

    Route::get('/management/users/get-profile-view-by-user-id/{userId}', [UserManagementController::class, 'getProfileViewByUserId']);

    Route::get('/management/users/get-profile-view-denied-by-user-id/{userId}', [UserManagementController::class, 'getProfileViewDeniedByUserId']);

    Route::get('/management/users/reset-user-generic-privileges/{userId}', [UserManagementController::class, 'resetUserGenericPrivileges']);

    Route::get('/management/users/get-user-navigation/{userId}', [UserManagementController::class, 'getUserNavigation']);

    Route::post('/management/users/update-user-navigation', [UserManagementController::class, 'updateUserNavigation']);

    Route::get("/payers/avg/comparision", [ReportController::class, 'payerAverageComparisonReport']);

    Route::get("/provider/payers/avg/comparision", [ReportController::class, 'payerAverageComparisonProviderReport']);

    Route::get('/practice-facility/providers',[UserCommonFunc::class,'fetchPracticeFacilityProviders']);

    Route::get("creds/payer/avgs",[CredentialingController::class, 'credsPayerAvg']);

    Route::get("creds/payer/avgs/years/list",[CredentialingController::class, 'credsPayerAvgYears']);



    Route::post("approve/attachment/dea",[OnBoardController::class,"approveProviderDeaAttachments"]);

    Route::post("approve/attachment/cv",[OnBoardController::class,"approveProviderCvAttachments"]);

    Route::delete("del/provider/attachment/{id}",[OnBoardController::class,"deleteProviderAttachment"]);

    Route::delete("del/facility/attachment/{id}",[OnBoardController::class,"deleteFacilityAttachment"]);

    Route::post("create/practice/w9-form",[eCAToolsController::class,"createPracticeW9"]);

    Route::get("view/practice/w9-form",[eCAToolsController::class,"viewPracticeW9"]);

});

Route::group(['prefix' => 'v1/admin/'], function () {

    Route::post("register",[AdminController::class,"adminRegister"]);
});

Route::get("send/document/expiry/email",[DocumentsController::class,"sendDocumentExpiryEmail"]);

Route::post("login",[UserController::class,"login"]);

Route::post("demo/login",[UserController::class,"loginDemo"]);

Route::post("logout",[UserController::class,"logout"]);

Route::get("loadapp/{token}",[UserController::class,"loadApp"]);

Route::post("forgot/password",[ForgotPasswordController::class,"store"]);

Route::post("reset/password",[ForgotPasswordController::class,"update"]);

Route::post("create/provider/credentials",[UserController::class,"createProviderCredentials"]);

Route::post("create/users",[UserController::class,"createUsers"]);

Route::get('ar/daily/backup/cron',[CronJobController::class,"arDailyBackupCron"]);

Route::get('ar/unassign/users',[CronJobController::class,"unAssignedUsers"]);

Route::post("add/practice",[ProviderController::class,"addPractice"]);

Route::post("add/facility",[ProviderController::class,"addFacility"]);

Route::post("assign/to/user",[ProviderController::class,"assignToUser"]);

Route::post("revoke/user",[ProviderController::class,"revokeUser"]);

//external provider form
Route::post("share/provider/form",[OnBoardController::class,"shareProviderForm"]);

Route::post("validate/provider/form",[OnBoardController::class,"checkProviderLink"]);

Route::post("send/provider/otp",[OnBoardController::class,"sendProviderOTP"]);

Route::post("verify/provider/otp",[OnBoardController::class,"verifyOtp"]);

Route::put("modify/by/provider/{providerId}",[OnBoardController::class,"updateProvider"]);

Route::get("fetch/by/provider",[OnBoardController::class,"fetchProviderForm"]);

Route::get('provider/states-cities/data',[UtilityFunc::class,"fetchStatesCitiesData"]);

Route::get("provider/onboard/dropdown/list",[OnBoardController::class,'getOnBoardDropdownList']);

Route::post('upload/provider/attachments',[OnBoardController::class,'uploadProviderDocuments']);
//external facility form
Route::post("share/facility/form",[OnBoardController::class,"shareFacilityForm"]);

Route::post("validate/facility/form",[OnBoardController::class,"checkFacilityLink"]);

Route::post("send/facility/otp",[OnBoardController::class,"sendFacilityOTP"]);

Route::post("verify/facility/otp",[OnBoardController::class,"verifyFacilityOtp"]);

Route::get("fetch/by/facility/form",[OnBoardController::class,'fetchFacilityForm']);

Route::put("modify/by/facility/{facilityId}",[OnBoardController::class,'updateFacility']);

Route::post('upload/facility/attachments',[OnBoardController::class,'uploadFacilityDocuments']);

Route::get('/portal/datadump',[PortalController::class,'portalDataDump']);


// Route::get('countries/list',[OnBoardController::class,'addCountries']);

// Route::get('update/facility/names',[OnBoardController::class,'makeFacilityNamesUpperCase']);

// Route::get('move/owner/data',[OnBoardController::class,'moveOwnerInformation']);

