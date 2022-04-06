<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillHTTaxinvoice;
use Linkhub\Popbill\HTTIKeyType;

class HTTaxinvoiceController extends Controller
{
    public function __construct() {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 홈택스 전자세금계산서 서비스 클래스 초기화
        $this->PopbillHTTaxinvoice = new PopbillHTTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정값, 개발용(true), 상업용(false)
        $this->PopbillHTTaxinvoice->IsTest(config('popbill.IsTest'));

        // 인증토큰의 IP제한기능 사용여부, 권장(true)
        $this->PopbillHTTaxinvoice->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 팝빌 API 서비스 고정 IP 사용여부, true-사용, false-미사용, 기본값(false)
        $this->PopbillHTTaxinvoice->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬서버 시간 사용 여부 true(기본값) - 사용, false(미사용)
        $this->PopbillHTTaxinvoice->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request){
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 홈택스에 신고된 전자세금계산서 매입/매출 내역 수집을 팝빌에 요청합니다. (조회기간 단위 : 최대 3개월)
     * - 수집 요청후 반환받은 작업아이디(JobID)의 유효시간은 1시간 입니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#RequestJob
     */
    public function RequestJob(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 전자세금계산서 유형, SELL-매출, BUY-매입, TRUSTEE-위수탁
        $TIKeyType = HTTIKeyType::SELL;

        // 수집일자유형, W-작성일자, I-발행일자, S-전송일자
        $DType = 'S';

        // 시작일자, 형식(yyyyMMdd)
        $SDate = '20210701';

        // 종료일자, 형식(yyyyMMdd)
        $EDate = '20210730';

        try {
            $jobID = $this->PopbillHTTaxinvoice->RequestJob($testCorpNum, $TIKeyType, $DType, $SDate, $EDate);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "작업아이디(jobID)" , 'value' => $jobID]);

    }

    /**
     * 함수 RequestJob(수집 요청)를 통해 반환 받은 작업 아이디의 상태를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetJobState
     */
    public function GetJobState(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 수집 요청시 반환받은 작업아이디
        $jobID = '021021509000000001';

        try {
            $result = $this->PopbillHTTaxinvoice->GetJobState($testCorpNum, $jobID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('JobState', ['Result' => [$result] ] );

    }

    /**
     * 전자세금계산서 매입/매출 내역 수집요청에 대한 상태 목록을 확인합니다.
     * - 수집 요청 후 1시간이 경과한 수집 요청건은 상태정보가 반환되지 않습니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#ListActiveJob
     */
    public function ListActiveJob(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->ListActiveJob($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('JobState', ['Result' => $result ] );
    }

    /**
     * 함수 GetJobState(수집 상태 확인)를 통해 상태 정보가 확인된 작업아이디를 활용하여 수집된 전자세금계산서 매입/매출 내역을 조회합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#Search
     */
    public function Search(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = '';

        // 수집 요청(RequestJob) 호출시 반환받은 작업아이디
        $JobID = '021102414000000006';

        // 문서형태 배열, N-일반세금계산서, M-수정세금계산서
        $Type = array (
            'N',
            'M'
        );

        // 과세형태 배열, T-과세, N-면세, Z-영세
        $TaxType = array (
            'T',
            'N',
            'Z'
        );

        // 영수/청구 배열, R-영수, C-청구, N-없음
        $PurposeType = array (
            'R',
            'C',
            'N'
        );

        // 종사업장 유무, 공백-전체조회, 0-종사업장 없는 건만 조회, 1-종사업장번호 조건에 따라 조회
        $TaxRegIDYN = "";

        // 종사업장번호 유형, 공백-전체, S-공급자, B-공급받는자, T-수탁자
        $TaxRegIDType = "";

        // 종사업장번호, 콤마(",")로 구분하여 구성 ex) "1234,0001";
        $TaxRegID = "";

        // 페이지 번호
        $Page = 1;

        // 페이지당 목록개수
        $PerPage = 10;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = "D";

        // 조회 검색어, 거래처 사업자번호 또는 거래처명 like 검색
        $SearchString = "";

        try {
            $result = $this->PopbillHTTaxinvoice->Search ( $testCorpNum, $JobID, $Type,
                $TaxType, $PurposeType, $TaxRegIDYN, $TaxRegIDType, $TaxRegID,
                $Page, $PerPage, $Order, $testUserID, $SearchString);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/Search', ['Result' => $result] );

    }

    /**
     * 함수 GetJobState(수집 상태 확인)를 통해 상태 정보가 확인된 작업아이디를 활용하여 수집된 전자세금계산서 매입/매출 내역의 요약 정보를 조회합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#Summary
     */
    public function Summary(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = '';

        // 수집 요청(RequestJob) 호출시 반환받은 작업아이디
        $JobID = '021102414000000006';

        // 문서형태 배열, N-일반세금계산서, M-수정세금계산서
        $Type = array (
            'N',
            'M'
        );

        // 과세형태 배열, T-과세, N-면세, Z-영세
        $TaxType = array (
            'T',
            'N',
            'Z'
        );

        // 영수/청구 배열, R-영수, C-청구, N-없음
        $PurposeType = array (
            'R',
            'C',
            'N'
        );

        // 종사업장 유무, 공백-전체조회, 0-종사업장 없는 건만 조회, 1-종사업장번호 조건에 따라 조회
        $TaxRegIDYN = "";

        // 종사업장번호 유형, 공백-전체, S-공급자, B-공급받는자, T-수탁자
        $TaxRegIDType = "";

        // 종사업장번호, 콤마(",")로 구분하여 구성 ex) "1234,0001";
        $TaxRegID = "";

        // 조회 검색어, 거래처 사업자번호 또는 거래처명 like 검색
        $SearchString = "";

        try {
            $result = $this->PopbillHTTaxinvoice->Summary($testCorpNum, $JobID, $Type, $TaxType,
                $PurposeType, $TaxRegIDYN, $TaxRegIDType, $TaxRegID, $testUserID, $SearchString);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/Summary', ['Result' => $result] );
    }

    /**
     * 국세청 승인번호를 통해 수집한 전자세금계산서 1건의 상세정보를 반환합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetTaxinvoice
     */
    public function GetTaxinvoice(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        //국세청 승인번호
        $NTSConfirmNum = '202112264100020300002e07';

        try {
            $result = $this->PopbillHTTaxinvoice->GetTaxinvoice($testCorpNum, $NTSConfirmNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/GetTaxinvoice', ['Taxinvoice' => $result] );
    }

    /**
     * 국세청 승인번호를 통해 수집한 전자세금계산서 1건의 상세정보를 XML 형태의 문자열로 반환합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetXML
     */
    public function GetXML(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        //국세청 승인번호
        $NTSConfirmNum = '202112264100020300002e07';

        try {
            $result = $this->PopbillHTTaxinvoice->GetXML($testCorpNum, $NTSConfirmNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/GetXML', ['Result' => $result] );
    }

    /**
     * 수집된 전자세금계산서 1건의 상세내역을 확인하는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetPopUpURL
     */
    public function GetPopUpURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 국세청 승인번호
        $NTSConfirmNum = "202112264100020300002e07";

        try {
            $url = $this->PopbillHTTaxinvoice->getPopUpURL($testCorpNum, $NTSConfirmNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자세금계산서 보기 URL" , 'value' => $url]);
    }

    /**
     * 수집된 전자세금계산서 1건의 상세내역을 인쇄하는 페이지의 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetPrintURL
     */
    public function GetPrintURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 국세청 승인번호
        $NTSConfirmNum = "202112264100020300002e07";

        try {
            $url = $this->PopbillHTTaxinvoice->getPrintURL($testCorpNum, $NTSConfirmNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자세금계산서 인쇄 URL" , 'value' => $url]);
    }

    /**
     * 홈택스연동 인증정보를 관리하는 페이지의 팝업 URL을 반환합니다.
     * - 인증방식에는 부서사용자/공인인증서 인증 방식이 있습니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetCertificatePopUpURL
     */
    public function GetCertificatePopUpURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $url = $this->PopbillHTTaxinvoice->GetCertificatePopUpURL($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "홈택스 인증관리 팝업 URL" , 'value' => $url]);
    }

    /**
     * 홈택스연동 인증을 위해 팝빌에 등록된 인증서 만료일자를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetCertificateExpireDate
     */
    public function GetCertificateExpireDate(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $ExpireDate = $this->PopbillHTTaxinvoice->GetCertificateExpireDate($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "공인인증서 만료일시" , 'value' => $ExpireDate]);
    }

    /**
     * 팝빌에 등록된 인증서로 홈택스 로그인 가능 여부를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#CheckCertValidation
     */
    public function CheckCertValidation(){

    // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckCertValidation($testCorpNum);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 홈택스연동 인증을 위해 팝빌에 전자세금계산서용 부서사용자 계정을 등록합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#RegistDeptUser
     */
    public function RegistDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 홈택스에서 생성한 전자세금계산서 부서사용자 아이디
        $deptUserID = 'userid_test';

        // 홈택스에서 생성한 전자세금계산서 부서사용자 비밀번호
        $deptUserPWD = 'passwd_test';

        try {
            $result = $this->PopbillHTTaxinvoice->RegistDeptUser($testCorpNum, $deptUserID, $deptUserPWD);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 홈택스연동 인증을 위해 팝빌에 등록된 전자세금계산서용 부서사용자 계정을 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#CheckDeptUser
     */
    public function CheckDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckDeptUser($testCorpNum);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에 등록된 전자세금계산서용 부서사용자 계정 정보로 홈택스 로그인 가능 여부를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#CheckLoginDeptUser
     */
    public function CheckLoginDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckLoginDeptUser($testCorpNum);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에 등록된 홈택스 전자세금계산서용 부서사용자 계정을 삭제합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#DeleteDeptUser
     */
    public function DeleteDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->DeleteDeptUser($testCorpNum);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API) 를 통해 확인하시기 바랍니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetBalance
     */
    public function GetBalance(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillHTTaxinvoice->GetBalance($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트" , 'value' => $remainPoint]);
    }

    /**
     * 연동회원 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetChargeURL
     */
    public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetChargeURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 포인트 충전 팝업 URL" , 'value' => $url]);
    }

    /**
     * 연동회원 포인트 결제내역 확인을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetPaymentURL
     */
    public function GetPaymentURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetPaymentURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 포인트 결제내역 팝업 URL" , 'value' => $url]);

    }

    /**
     * 연동회원 포인트 사용내역 확인을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetUseHistoryURL
     */
    public function GetUseHistoryURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetUseHistoryURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 포인트 사용내역 팝업 URL" , 'value' => $url]);

    }

    /**
     * 파트너의 잔여포인트를 확인합니다.
     * - 과금방식이 연동과금인 경우 연동회원 잔여포인트(GetBalance API)를 이용하시기 바랍니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetPartnerBalance
     */
    public function GetPartnerBalance(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillHTTaxinvoice->GetPartnerBalance($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 잔여포인트" , 'value' => $remainPoint]);
    }

    /**
     * 파트너 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetPartnerURL
     */
    public function GetPartnerURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillHTTaxinvoice->GetPartnerURL($testCorpNum, $TOGO);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
    }

    /**
     * 팝빌 홈택스연동(세금) API 서비스 과금정보를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetChargeInfo
     */
    public function GetChargeInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->GetChargeInfo($testCorpNum,$testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 홈택스연동 정액제 서비스 신청 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetFlatRatePopUpURL
     */
    public function GetFlatRatePopUpURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $url = $this->PopbillHTTaxinvoice->GetFlatRatePopUpURL($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "정액제 서비스 신청 팝업 URL" , 'value' => $url]);
    }

    /**
     * 홈택스연동 정액제 서비스 상태를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetFlatRateState
     */
    public function GetFlatRateState(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->GetFlatRateState($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('FlatRateState', ['Result' => $result]);
    }

    /**
     *  사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#CheckIsMember
     */
    public function CheckIsMember(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 파트너 링크아이디
        // ./config/popbill.php에 선언된 파트너 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillHTTaxinvoice->CheckIsMember($testCorpNum, $LinkID);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 사용하고자 하는 아이디의 중복여부를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#CheckID
     */
    public function CheckID(){

        // 조회할 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckID($testUserID);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 사용자를 연동회원으로 가입처리합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#JoinMember
     */
    public function JoinMember(){

        $joinForm = new JoinForm();

        // 링크아이디
        $joinForm->LinkID = config('popbill.LinkID');

        // 사업자번호, "-"제외 10자리
        $joinForm->CorpNum = '1234567890';

        // 대표자성명
        $joinForm->CEOName = '대표자성명';

        // 사업자상호
        $joinForm->CorpName = '테스트사업자상호';

        // 사업자주소
        $joinForm->Addr = '테스트사업자주소';

        // 업태
        $joinForm->BizType = '업태';

        // 종목
        $joinForm->BizClass = '종목';

        // 담당자명
        $joinForm->ContactName = '담당자상명';

        // 담당자 이메일
        $joinForm->ContactEmail = 'tester@test.com';

        // 담당자 연락처
        $joinForm->ContactTEL = '07043042991';

        // 아이디, 6자 이상 20자미만
        $joinForm->ID = 'userid_phpdd';

        // 비밀번호, 8자 이상 20자 이하(영문, 숫자, 특수문자 조합)
        $joinForm->Password = 'asdf1234!@';

        try {
            $result = $this->PopbillHTTaxinvoice->JoinMember($joinForm);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetAccessURL
     */
    public function GetAccessURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetAccessURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);
    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetCorpInfo
     */
    public function GetCorpInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        try {
            $CorpInfo = $this->PopbillHTTaxinvoice->GetCorpInfo($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#UpdateCorpInfo
     */
    public function UpdateCorpInfo(){

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 회사정보 클래스 생성
        $CorpInfo = new CorpInfo();

        // 대표자명
        $CorpInfo->ceoname = '대표자명';

        // 상호
        $CorpInfo->corpName = '링크허브';

        // 주소
        $CorpInfo->addr = '서울시 강남구 영동대로';

        // 업태
        $CorpInfo->bizType = '업태';

        // 종목
        $CorpInfo->bizClass = '종목';

        try {
            $result =  $this->PopbillHTTaxinvoice->UpdateCorpInfo($testCorpNum, $CorpInfo);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 담당자(팝빌 로그인 계정)를 추가합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#RegistContact
     */
    public function RegistContact(){

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 담당자 정보 객체 생성
        $ContactInfo = new ContactInfo();

        // 담당자 아이디
        $ContactInfo->id = 'testkorea001';

        // 담당자 비밀번호, 8자 이상 20자 이하(영문, 숫자, 특수문자 조합)
        $ContactInfo->Password = 'asdf123!@#';

        // 담당자명
        $ContactInfo->personName = '담당자_수정';

        // 연락처
        $ContactInfo->tel = '070-4304-2991';

        // 핸드폰번호
        $ContactInfo->hp = '010-1234-1234';

        // 이메일주소
        $ContactInfo->email = 'test@test.com';

        // 팩스
        $ContactInfo->fax = '070-111-222';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        try {
            $result = $this->PopbillHTTaxinvoice->RegistContact($testCorpNum, $ContactInfo);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보을 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#GetContactInfo
     */
    public function GetContactInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        //확인할 담당자 아이디
        $contactID = 'checkContact';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $ContactInfo = $this->PopbillHTTaxinvoice->GetContactInfo($testCorpNum, $contactID, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#ListContact
     */
    public function ListContact(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        try {
          $ContactList = $this->PopbillHTTaxinvoice->ListContact($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://docs.popbill.com/httaxinvoice/phplaravel/api#UpdateContact
     */
    public function UpdateContact(){

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 담당자 정보 객체 생성
        $ContactInfo = new ContactInfo();

        // 담당자명
        $ContactInfo->personName = '담당자_수정';

        // 담당자 아이디
        $ContactInfo->id = 'testkorea';

        // 담당자 연락처
        $ContactInfo->tel = '070-4304-2991';

        // 핸드폰 번호
        $ContactInfo->hp = '010-1234-1234';

        // 이메일 주소
        $ContactInfo->email = 'test@test.com';

        // 팩스번호
        $ContactInfo->fax = '070-111-222';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        try {
            $result = $this->PopbillHTTaxinvoice->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
            $code = $result->code;
            $message = $result->message;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
}
