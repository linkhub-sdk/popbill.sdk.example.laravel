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


// 전자세금계산서 Route Mapping

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



// 전자명세서 Route Mapping

Route::get('/Statement', function () {
    return view('Statement/index');
});

Route::get('/Statement/CheckMgtKeyInUse','StatementController@CheckMgtKeyInUse');
Route::get('/Statement/RegistIssue','StatementController@RegistIssue');
Route::get('/Statement/Register','StatementController@Register');
Route::get('/Statement/Update','StatementController@Update');
Route::get('/Statement/Issue','StatementController@Issue');
Route::get('/Statement/CancelIssue','StatementController@CancelIssue');
Route::get('/Statement/Delete','StatementController@Delete');
Route::get('/Statement/GetPopUpURL','StatementController@GetPopUpURL');
Route::get('/Statement/GetPrintURL','StatementController@GetPrintURL');
Route::get('/Statement/GetEPrintURL','StatementController@GetEPrintURL');
Route::get('/Statement/GetMassPrintURL','StatementController@GetMassPrintURL');
Route::get('/Statement/GetMailURL','StatementController@GetMailURL');

Route::get('/Statement/GetInfo','StatementController@GetInfo');
Route::get('/Statement/GetInfos','StatementController@GetInfos');
Route::get('/Statement/GetDetailInfo','StatementController@GetDetailInfo');
Route::get('/Statement/Search','StatementController@Search');
Route::get('/Statement/GetLogs','StatementController@GetLogs');
Route::get('/Statement/GetURL','StatementController@GetURL');

Route::get('/Statement/GetAccessURL','StatementController@GetAccessURL');
Route::get('/Statement/AttachFile','StatementController@AttachFile');
Route::get('/Statement/DeleteFile','StatementController@DeleteFile');
Route::get('/Statement/GetFiles','StatementController@GetFiles');
Route::get('/Statement/SendEmail','StatementController@SendEmail');
Route::get('/Statement/SendSMS','StatementController@SendSMS');
Route::get('/Statement/SendFAX','StatementController@SendFAX');
Route::get('/Statement/FAXSend','StatementController@FAXSend');
Route::get('/Statement/AttachStatement','StatementController@AttachStatement');
Route::get('/Statement/DetachStatement','StatementController@DetachStatement');
Route::get('/Statement/ListEmailConfig','StatementController@ListEmailConfig');
Route::get('/Statement/UpdateEmailConfig','StatementController@UpdateEmailConfig');

Route::get('/Statement/GetBalance','StatementController@GetBalance');
Route::get('/Statement/GetChargeURL','StatementController@GetChargeURL');
Route::get('/Statement/GetPartnerBalance','StatementController@GetPartnerBalance');
Route::get('/Statement/GetPartnerURL','StatementController@GetPartnerURL');
Route::get('/Statement/GetUnitCost','StatementController@GetUnitCost');
Route::get('/Statement/GetChargeInfo','StatementController@GetChargeInfo');

Route::get('/Statement/CheckIsMember','StatementController@CheckIsMember');
Route::get('/Statement/CheckID','StatementController@CheckID');
Route::get('/Statement/JoinMember','StatementController@JoinMember');
Route::get('/Statement/GetCorpInfo','StatementController@GetCorpInfo');
Route::get('/Statement/UpdateCorpInfo','StatementController@UpdateCorpInfo');
Route::get('/Statement/RegistContact','StatementController@RegistContact');
Route::get('/Statement/ListContact','StatementController@ListContact');
Route::get('/Statement/UpdateContact','StatementController@UpdateContact');


// 현금영수증 Route Mapping

Route::get('/Cashbill', function () {
    return view('Cashbill/index');
});

Route::get('/Cashbill/CheckIsMember','CashbillController@CheckIsMember');
Route::get('/Cashbill/CheckID','CashbillController@CheckID');
Route::get('/Cashbill/JoinMember','CashbillController@JoinMember');
Route::get('/Cashbill/GetCorpInfo','CashbillController@GetCorpInfo');
Route::get('/Cashbill/UpdateCorpInfo','CashbillController@UpdateCorpInfo');
Route::get('/Cashbill/RegistContact','CashbillController@RegistContact');
Route::get('/Cashbill/ListContact','CashbillController@ListContact');
Route::get('/Cashbill/UpdateContact','CashbillController@UpdateContact');




// 팩스 Route Mapping

Route::get('/Fax', function () {
    return view('Fax/index');
});

Route::get('/Fax/CheckIsMember','FaxController@CheckIsMember');
Route::get('/Fax/CheckID','FaxController@CheckID');
Route::get('/Fax/JoinMember','FaxController@JoinMember');
Route::get('/Fax/GetCorpInfo','FaxController@GetCorpInfo');
Route::get('/Fax/UpdateCorpInfo','FaxController@UpdateCorpInfo');
Route::get('/Fax/RegistContact','FaxController@RegistContact');
Route::get('/Fax/ListContact','FaxController@ListContact');
Route::get('/Fax/UpdateContact','FaxController@UpdateContact');

// 문자 Route Mapping

Route::get('/Message', function () {
    return view('Message/index');
});

Route::get('/Message/CheckIsMember','MessageController@CheckIsMember');
Route::get('/Message/CheckID','MessageController@CheckID');
Route::get('/Message/JoinMember','MessageController@JoinMember');
Route::get('/Message/GetCorpInfo','MessageController@GetCorpInfo');
Route::get('/Message/UpdateCorpInfo','MessageController@UpdateCorpInfo');
Route::get('/Message/RegistContact','MessageController@RegistContact');
Route::get('/Message/ListContact','MessageController@ListContact');
Route::get('/Message/UpdateContact','MessageController@UpdateContact');

// 카카오톡 Route Mapping

Route::get('/KakaoTalk', function () {
    return view('KakaoTalk/index');
});

Route::get('/KakaoTalk/CheckIsMember','KakaoTalkController@CheckIsMember');
Route::get('/KakaoTalk/CheckID','KakaoTalkController@CheckID');
Route::get('/KakaoTalk/JoinMember','KakaoTalkController@JoinMember');
Route::get('/KakaoTalk/GetCorpInfo','KakaoTalkController@GetCorpInfo');
Route::get('/KakaoTalk/UpdateCorpInfo','KakaoTalkController@UpdateCorpInfo');
Route::get('/KakaoTalk/RegistContact','KakaoTalkController@RegistContact');
Route::get('/KakaoTalk/ListContact','KakaoTalkController@ListContact');
Route::get('/KakaoTalk/UpdateContact','KakaoTalkController@UpdateContact');


// 휴폐업조회 Route Mapping

Route::get('/CloseDown', function () {
    return view('CloseDown/index');
});

Route::get('/CloseDown/CheckIsMember','ClosedownController@CheckIsMember');
Route::get('/CloseDown/CheckID','ClosedownController@CheckID');
Route::get('/CloseDown/JoinMember','ClosedownController@JoinMember');
Route::get('/CloseDown/GetCorpInfo','ClosedownController@GetCorpInfo');
Route::get('/CloseDown/UpdateCorpInfo','ClosedownController@UpdateCorpInfo');
Route::get('/CloseDown/RegistContact','ClosedownController@RegistContact');
Route::get('/CloseDown/ListContact','ClosedownController@ListContact');
Route::get('/CloseDown/UpdateContact','ClosedownController@UpdateContact');


// 홈택스 전자세금계산서 Route Mapping

Route::get('/HTTaxinvoice', function () {
    return view('HTTaxinvoice/index');
});

Route::get('/HTTaxinvoice/CheckIsMember','HTTaxinvoiceController@CheckIsMember');
Route::get('/HTTaxinvoice/CheckID','HTTaxinvoiceController@CheckID');
Route::get('/HTTaxinvoice/JoinMember','HTTaxinvoiceController@JoinMember');
Route::get('/HTTaxinvoice/GetCorpInfo','HTTaxinvoiceController@GetCorpInfo');
Route::get('/HTTaxinvoice/UpdateCorpInfo','HTTaxinvoiceController@UpdateCorpInfo');
Route::get('/HTTaxinvoice/RegistContact','HTTaxinvoiceController@RegistContact');
Route::get('/HTTaxinvoice/ListContact','HTTaxinvoiceController@ListContact');
Route::get('/HTTaxinvoice/UpdateContact','HTTaxinvoiceController@UpdateContact');



// 홈택스 현금영수증 Route Mapping

Route::get('/HTCashbill', function () {
    return view('HTCashbill/index');
});

Route::get('/HTCashbill/CheckIsMember','HTCashbillController@CheckIsMember');
Route::get('/HTCashbill/CheckID','HTCashbillController@CheckID');
Route::get('/HTCashbill/JoinMember','HTCashbillController@JoinMember');
Route::get('/HTCashbill/GetCorpInfo','HTCashbillController@GetCorpInfo');
Route::get('/HTCashbill/UpdateCorpInfo','HTCashbillController@UpdateCorpInfo');
Route::get('/HTCashbill/RegistContact','HTCashbillController@RegistContact');
Route::get('/HTCashbill/ListContact','HTCashbillController@ListContact');
Route::get('/HTCashbill/UpdateContact','HTCashbillController@UpdateContact');
