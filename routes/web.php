<?php

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
    return view('index');
});

Route::get('/Taxinvoice', function () {
    return view('Taxinvoice/index');
});

Route::get('/Taxinvoice/CheckIsMember','TaxinvoiceController@CheckIsMember');
Route::get('/Taxinvoice/CheckID','TaxinvoiceController@CheckID');
Route::get('/Taxinvoice/JoinMember','TaxinvoiceController@JoinMember');
Route::get('/Taxinvoice/GetCorpInfo','TaxinvoiceController@GetCorpInfo');
Route::get('/Taxinvoice/UpdateCorpInfo','TaxinvoiceController@UpdateCorpInfo');
Route::get('/Taxinvoice/RegistContact','TaxinvoiceController@RegistContact');
Route::get('/Taxinvoice/ListContact','TaxinvoiceController@ListContact');
Route::get('/Taxinvoice/UpdateContact','TaxinvoiceController@UpdateContact');

Route::get('/Taxinvoice/CheckMgtKeyInUse','TaxinvoiceController@CheckMgtKeyInUse');
Route::get('/Taxinvoice/RegistIssue','TaxinvoiceController@RegistIssue');
Route::get('/Taxinvoice/Register','TaxinvoiceController@Register');
Route::get('/Taxinvoice/Update','TaxinvoiceController@Update');
Route::get('/Taxinvoice/Issue','TaxinvoiceController@Issue');
Route::get('/Taxinvoice/CancelIssue','TaxinvoiceController@CancelIssue');
Route::get('/Taxinvoice/Send','TaxinvoiceController@Send');
Route::get('/Taxinvoice/CancelSend','TaxinvoiceController@CancelSend');
Route::get('/Taxinvoice/Accept','TaxinvoiceController@Accept');
Route::get('/Taxinvoice/Deny','TaxinvoiceController@Deny');
Route::get('/Taxinvoice/Delete','TaxinvoiceController@Delete');
Route::get('/Taxinvoice/RegistRequest','TaxinvoiceController@RegistRequest');
Route::get('/Taxinvoice/Request','TaxinvoiceController@Request');
Route::get('/Taxinvoice/CancelRequest','TaxinvoiceController@CancelRequest');
Route::get('/Taxinvoice/Refuse','TaxinvoiceController@Refuse');

Route::get('/Taxinvoice/SendToNTS','TaxinvoiceController@SendToNTS');

Route::get('/Taxinvoice/GetInfo','TaxinvoiceController@GetInfo');
Route::get('/Taxinvoice/GetInfos','TaxinvoiceController@GetInfos');
Route::get('/Taxinvoice/GetDetailInfo','TaxinvoiceController@GetDetailInfo');
Route::get('/Taxinvoice/Search','TaxinvoiceController@Search');
Route::get('/Taxinvoice/GetLogs','TaxinvoiceController@GetLogs');
Route::get('/Taxinvoice/GetURL','TaxinvoiceController@GetURL');

Route::get('/Taxinvoice/GetPopUpURL','TaxinvoiceController@GetPopUpURL');
Route::get('/Taxinvoice/GetPrintURL','TaxinvoiceController@GetPrintURL');
Route::get('/Taxinvoice/GetEPrintURL','TaxinvoiceController@GetEPrintURL');
Route::get('/Taxinvoice/GetMassPrintURL','TaxinvoiceController@GetMassPrintURL');
Route::get('/Taxinvoice/GetMailURL','TaxinvoiceController@GetMailURL');

Route::get('/Taxinvoice/GetAccessURL','TaxinvoiceController@GetAccessURL');
Route::get('/Taxinvoice/GetSealURL','TaxinvoiceController@GetSealURL');
Route::get('/Taxinvoice/AttachFile','TaxinvoiceController@AttachFile');
Route::get('/Taxinvoice/DeleteFile','TaxinvoiceController@DeleteFile');
Route::get('/Taxinvoice/GetFiles','TaxinvoiceController@GetFiles');
Route::get('/Taxinvoice/SendEmail','TaxinvoiceController@SendEmail');
Route::get('/Taxinvoice/SendSMS','TaxinvoiceController@SendSMS');
Route::get('/Taxinvoice/SendFAX','TaxinvoiceController@SendFAX');
Route::get('/Taxinvoice/AttachStatement','TaxinvoiceController@AttachStatement');
Route::get('/Taxinvoice/DetachStatement','TaxinvoiceController@DetachStatement');
Route::get('/Taxinvoice/GetEmailPublicKeys','TaxinvoiceController@GetEmailPublicKeys');
Route::get('/Taxinvoice/AssignMgtKey','TaxinvoiceController@AssignMgtKey');
Route::get('/Taxinvoice/ListEmailConfig','TaxinvoiceController@ListEmailConfig');
Route::get('/Taxinvoice/UpdateEmailConfig','TaxinvoiceController@UpdateEmailConfig');


Route::get('/Taxinvoice/GetTaxCertURL','TaxinvoiceController@GetTaxCertURL');
Route::get('/Taxinvoice/GetCertificateExpireDate','TaxinvoiceController@GetCertificateExpireDate');
Route::get('/Taxinvoice/CheckCertValidation','TaxinvoiceController@CheckCertValidation');

Route::get('/Taxinvoice/GetBalance','TaxinvoiceController@GetBalance');
Route::get('/Taxinvoice/GetChargeURL','TaxinvoiceController@GetChargeURL');
Route::get('/Taxinvoice/GetPartnerBalance','TaxinvoiceController@GetPartnerBalance');
Route::get('/Taxinvoice/GetPartnerURL','TaxinvoiceController@GetPartnerURL');
Route::get('/Taxinvoice/GetUnitCost','TaxinvoiceController@GetUnitCost');
Route::get('/Taxinvoice/GetChargeInfo','TaxinvoiceController@GetChargeInfo');


Route::get('/Cashbill', function () {
    return view('Cashbill/index');
});

Route::get('/Cashbill/{FileName}', function ($FileName) {
    return view('Cashbill/'.$FileName);
});

Route::get('/Statement', function () {
    return view('Statement/index');
});

Route::get('/Statement/{FileName}', function ($FileName) {
    return view('Statement/'.$FileName);
});


Route::get('/Fax', function () {
    return view('Fax/index');
});

Route::get('/Fax/{FileName}', function ($FileName) {
    return view('Fax/'.$FileName);
});

Route::get('/Message', function () {
    return view('Message/index');
});

Route::get('/Message/{FileName}', function ($FileName) {
    return view('Message/'.$FileName);
});


Route::get('/KakaoTalk', function () {
    return view('KakaoTalk/index');
});

Route::get('/KakaoTalk/{FileName}', function ($FileName) {
    return view('KakaoTalk/'.$FileName);
});


Route::get('/CloseDown', function () {
    return view('CloseDown/index');
});

Route::get('/CloseDown/{FileName}', function ($FileName) {
    return view('CloseDown/'.$FileName);
});


Route::get('/HTTaxinvoice', function () {
    return view('HTTaxinvoice/index');
});

Route::get('/HTTaxinvoice/{FileName}', function ($FileName) {
    return view('HTTaxinvoice/'.$FileName);
});


Route::get('/HTCashbill', function () {
    return view('HTCashbill/index');
});

Route::get('/HTCashbill/{FileName}', function ($FileName) {
    return view('HTCashbill/'.$FileName);
});
