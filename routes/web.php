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
Route::get('/Taxinvoice', function () {  return view('Taxinvoice/index'); });
Route::get('/Taxinvoice/{APIName}','TaxinvoiceController@RouteHandelerFunc');

// 전자명세서 Route Mapping
Route::get('/Statement', function () {  return view('Statement/index'); });
Route::get('/Statement/{APIName}','StatementController@RouteHandelerFunc');

// 현금영수증 Route Mapping
Route::get('/Cashbill', function () { return view('Cashbill/index'); });
Route::get('/Cashbill/{APIName}','CashbillController@RouteHandelerFunc');

// 팩스 Route Mapping
Route::get('/Fax', function () { return view('Fax/index'); });
Route::get('/Fax/{APIName}','FaxController@RouteHandelerFunc');

// 문자 Route Mapping
Route::get('/Message', function () { return view('Message/index'); });
Route::get('/Message/{APIName}','MessageController@RouteHandelerFunc');

// 카카오톡 Route Mapping
Route::get('/KakaoTalk', function () { return view('KakaoTalk/index'); });
Route::get('/KakaoTalk/{APIName}','KakaoTalkController@RouteHandelerFunc');

// 사업자등록상태조회(휴폐업조회) Route Mapping
Route::get('/CloseDown', function () { return view('CloseDown/index'); });
Route::get('/CloseDown/{APIName}','ClosedownController@RouteHandelerFunc');

// 기업정보조회 Route Mapping
Route::get('/BizInfoCheck', function () { return view('BizInfoCheck/index'); });
Route::get('/BizInfoCheck/{APIName}','BizInfoCheckController@RouteHandelerFunc');

// 홈택스 전자세금계산서 Route Mapping
Route::get('/HTTaxinvoice', function () { return view('HTTaxinvoice/index'); });
Route::get('/HTTaxinvoice/{APIName}','HTTaxinvoiceController@RouteHandelerFunc');

// 홈택스 현금영수증 Route Mapping
Route::get('/HTCashbill', function () { return view('HTCashbill/index'); });
Route::get('/HTCashbill/{APIName}','HTCashbillController@RouteHandelerFunc');


// 계좌조회 Route Mapping
Route::get('/EasyFinBank', function () { return view('EasyFinBank/index'); });
Route::get('/EasyFinBank/{APIName}','EasyFinBankController@RouteHandelerFunc');

// 예금주조회 Route Mapping
Route::get('/AccountCheck', function () { return view('AccountCheck/index'); });
Route::get('/AccountCheck/{APIName}','AccountCheckController@RouteHandelerFunc');
