<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillHTCashbill;
use Linkhub\Popbill\HTCBKeyType;
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class HTCashbillController extends Controller
{
    public function __construct() {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 홈택스 현금영수증 서비스 클래스 초기화
        $this->PopbillHTCashbill = new PopbillHTCashbill(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정값, true-개발용, false-상업용
        $this->PopbillHTCashbill->IsTest(config('popbill.IsTest'));

        // 인증토큰의 IP제한기능 사용여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillHTCashbill->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 팝빌 API 서비스 고정 IP 사용여부, true-사용, false-미사용, 기본값(false)
        $this->PopbillHTCashbill->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬서버 시간 사용 여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillHTCashbill->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request){
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /*
     * 홈택스에 신고된 현금영수증 매입/매출 내역 수집을 팝빌에 요청합니다. (조회기간 단위 : 최대 3개월)
     * - 수집 요청후 반환받은 작업아이디(JobID)의 유효시간은 1시간 입니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/job#RequestJob
     */
    public function RequestJob(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 현금영수증, SELL-매출, BUY-매입
        $CBType = HTCBKeyType::BUY;

        // 시작일자, 형식(yyyyMMdd)
        $SDate = '20220401';

        // 종료일자, 형식(yyyyMMdd)
        $EDate = '20220430';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $jobID = $this->PopbillHTCashbill->RequestJob( $testCorpNum, $CBType, $SDate, $EDate, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "작업아이디(jobID)" , 'value' => $jobID]);
    }

    /*
     *  수집 요청(RequestJob API) 함수를 통해 반환 받은 작업 아이디의 상태를 확인합니다.
     * - 수집 결과 조회(Search API) 함수 또는 수집 결과 요약 정보 조회(Summary API) 함수를 사용하기 전에
     *   수집 작업의 진행 상태, 수집 작업의 성공 여부를 확인해야 합니다.
     * - 작업 상태(jobState) = 3(완료)이고 수집 결과 코드(errorCode) = 1(수집성공)이면
     *   수집 결과 내역 조회(Search) 또는 수집 결과 요약 정보 조회(Summary)를 해야합니다.
     * - 작업 상태(jobState)가 3(완료)이지만 수집 결과 코드(errorCode)가 1(수집성공)이 아닌 경우에는
     *   오류메시지(errorReason)로 수집 실패에 대한 원인을 파악할 수 있습니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/job#GetJobState
     */
    public function GetJobState(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 수집요청(requestJob API) 함수 호출 시 반환받은 작업아이디
        $jobID = '';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->GetJobState( $testCorpNum, $jobID, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('JobState', ['Result' => [$result] ] );
      }

    /*
     * 현금영수증 매입/매출 내역 수집요청에 대한 상태 목록을 확인합니다.
     * - 수집 요청 후 1시간이 경과한 수집 요청건은 상태정보가 반환되지 않습니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/job#ListActiveJob
     */
    public function ListActiveJob(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->ListActiveJob($testCorpNum, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('JobState', ['Result' => $result ] );
    }

    /**
     * 수집 상태 확인(GetJobState API) 함수를 통해 상태 정보 확인된 작업아이디를 활용하여 현금영수증 매입/매출 내역을 조회합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/search#Search
     */
    public function Search(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 수집 요청(RequestJob) 함수 호출시 반환받은 작업아이디
        $JobID = '022040510000000018';

        // 문서형태 배열 ("N" 와 "C" 중 선택, 다중 선택 가능)
        // └ N = 일반 현금영수증 , C = 취소현금영수증
        // - 미입력 시 전체조회
        $TradeType = array(
            'N',
            'C'
        );

        // 거래구분 배열 ("P" 와 "C" 중 선택, 다중 선택 가능)
        // └ P = 소득공제용 , C = 지출증빙용
        // - 미입력 시 전체조회
        $TradeUsage = array(
            'P',
            'C'
        );

        // 페이지 번호
        $Page = 1;

        // 페이지당 목록개수
        $PerPage = 10;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = "D";

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->Search($testCorpNum, $JobID, $TradeType, $TradeUsage, $Page, $PerPage, $Order, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTCashbill/Search', ['Result' => $result] );
    }

    /**
     * 수집 상태 확인(GetJobState API) 함수를 통해 상태 정보가 확인된 작업아이디를 활용하여 수집된 현금영수증 매입/매출 내역의 요약 정보를 조회합니다.
     * - 요약 정보 : 현금영수증 수집 건수, 공급가액 합계, 세액 합계, 봉사료 합계, 합계 금액
     * - https://developers.popbill.com/reference/htcashbill/php/api/search#Summary
     */
    public function Summary(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 수집 요청(RequestJob) 호출시 반환받은 작업아이디
        $JobID = '';

        // 문서형태 배열 ("N" 와 "C" 중 선택, 다중 선택 가능)
        // └ N = 일반 현금영수증 , C = 취소현금영수증
        // - 미입력 시 전체조회
        $TradeType = array (
            'N',
            'C'
        );

        // 거래구분 배열 ("P" 와 "C" 중 선택, 다중 선택 가능)
        // └ P = 소득공제용 , C = 지출증빙용
        // - 미입력 시 전체조회
        $TradeUsage = array (
            'P',
            'C'
        );

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->Summary($testCorpNum, $JobID, $TradeType, $TradeUsage, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTCashbill/Summary', ['Result' => $result] );
    }

    /**
     * 홈택스연동 인증정보를 관리하는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/cert#GetCertificatePopUpURL
     */
    public function GetCertificatePopUpURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTCashbill->GetCertificatePopUpURL($testCorpNum, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "홈택스 인증관리 팝업 URL" , 'value' => $url]);
    }

    /**
     * 팝빌에 등록된 인증서 만료일자를 확인합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/cert#GetCertificateExpireDate
     */
    public function GetCertificateExpireDate(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $ExpireDate = $this->PopbillHTCashbill->GetCertificateExpireDate($testCorpNum);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/cert#CheckCertValidation
     */
    public function CheckCertValidation(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->CheckCertValidation($testCorpNum, $testUserID);
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
     * 홈택스연동 인증을 위해 팝빌에 현금영수증 자료조회 부서사용자 계정을 등록합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/cert#RegistDeptUser
     */
    public function RegistDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 홈택스에서 생성한 현금영수증 부서사용자 아이디
        $deptUserID = 'userid_test';

        // 홈택스에서 생성한 현금영수증 부서사용자 비밀번호
        $deptUserPWD = 'passwd_test';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->RegistDeptUser($testCorpNum, $deptUserID, $deptUserPWD, $testUserID);
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
     * 홈택스연동 인증을 위해 팝빌에 등록된 현금영수증 자료조회 부서사용자 계정을 확인합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/cert#CheckDeptUser
     */
    public function CheckDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->CheckDeptUser($testCorpNum, $testUserID);
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
     * 팝빌에 등록된 현금영수증 자료조회 부서사용자 계정 정보로 홈택스 로그인 가능 여부를 확인합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/cert#CheckLoginDeptUser
     */
    public function CheckLoginDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->CheckLoginDeptUser($testCorpNum, $testUserID);
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
     * 팝빌에 등록된 홈택스 현금영수증 자료조회 부서사용자 계정을 삭제합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/cert#DeleteDeptUser
     */
    public function DeleteDeptUser(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->DeleteDeptUser($testCorpNum, $testUserID);
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
     * 홈택스연동 정액제 서비스 신청 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetFlatRatePopUpURL
     */
    public function GetFlatRatePopUpURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTCashbill->GetFlatRatePopUpURL($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetFlatRateState
     */
    public function GetFlatRateState(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->GetFlatRateState($testCorpNum, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('FlatRateState', ['Result' => $result]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetBalance
     */
    public function GetBalance(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillHTCashbill->GetBalance($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트" , 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * https://developers.popbill.com/reference/htcashbill/php/api/point#GetUseHistory
     */
    public function GetUseHistory(){

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $testCorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20220901";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20220930";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 정렬방향, A-오름차순, D-내림차순
        $Order = "D";

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try	{
            $result = $this->PopbillHTCashbill->GetUseHistory($testCorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTCashbill/UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 포인트 결제내역을 확인합니다.
     * https://developers.popbill.com/reference/htcashbill/php/api/point#GetPaymentHistory
     */
    public function GetPaymentHistory(){

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $testCorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20220901";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20220930";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try	{
            $result = $this->PopbillHTCashbill->GetPaymentHistory($testCorpNum, $SDate, $EDate, $Page, $PerPage, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTCashbill/PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불 신청내역을 확인합니다.
     * https://developers.popbill.com/reference/htcashbill/php/api/point#GetRefundHistory
     */
    public function GetRefundHistory(){

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $testCorpNum = "1234567890";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try	{
            $result = $this->PopbillHTCashbill->GetRefundHistory($testCorpNum, $Page, $PerPage, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTCashbill/RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불을 신청합니다.
     * https://developers.popbill.com/reference/htcashbill/php/api/point#Refund
     */
    public function Refund(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        $RefundForm = new RefundForm();

        // 담당자명
        $RefundForm->contactname = '담당자명';

        // 담당자 연락처
        $RefundForm->tel = '01011112222';

        // 환불 신청 포인트
        $RefundForm->requestpoint = '100';

        // 계좌은행
        $RefundForm->accountbank = '국민';

        // 계좌번호
        $RefundForm->accountnum = '123123123-123';

        // 예금주
        $RefundForm->accountname = '테스트';

        // 환불사유
        $RefundForm->reason = '환불사유';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try	{
            $result = $this->PopbillHTCashbill->Refund($testCorpNum, $RefundForm, $testUserID);
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
     * 무통장 입금을 신청합니다.
     * https://developers.popbill.com/reference/htcashbill/php/api/point#PaymentRequest
     */
    public function PaymentRequest(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        $paymentForm = new PaymentForm();

        // 담당자명
        // 미입력 시 기본값 적용 - 팝빌 회원 담당자명.
        $paymentForm->settlerName = '담당자명';

        // 담당자 이메일
        // 사이트에서 신청하면 자동으로 담당자 이메일.
        // 미입력 시 공백 처리
        $paymentForm->settlerEmail = 'test@test.com';

        // 담당자 휴대폰
        // 무통장 입금 승인 알림톡이 전송됩니다.
        $paymentForm->notifyHP = '01012341234';

        // 입금자명
        $paymentForm->paymentName = '입금자명';

        // 결제금액
        $paymentForm->settleCost = '11000';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->PaymentRequest($testCorpNum, $paymentForm, $testUserID);
            $code = $result->code;
            $message = $result->message;
            $settleCode = $result->settleCode;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTCashbill/PaymentResponse', ['Result' => $result]);
    }

    /**
     * 무통장 입금신청한 건의 정보를 확인합니다.
     * https://developers.popbill.com/reference/htcashbill/php/api/point#GetSettleResult
     */
    public function GetSettleResult(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        // paymentRequest 를 통해 얻은 settleCode.
        $settleCode = '202210040000000070';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->GetSettleResult($testCorpNum, $settleCode, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTCashbill/PaymentHistory', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetChargeURL
     */
    public function GetChargeURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTCashbill->GetChargeURL($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetPaymentURL
     */
    public function GetPaymentURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTCashbill->GetPaymentURL($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTCashbill->GetUseHistoryURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 포인트 사용내역 팝업 URL" , 'value' => $url]);

    }

    /**
     * 파트너의 잔여포인트를 확인합니다.
     * - 과금방식이 연동과금인 경우 연동회원 잔여포인트 확인(GetBalance API) 함수를 이용하시기 바랍니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetPartnerBalance
     */
    public function GetPartnerBalance(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillHTCashbill->GetPartnerBalance($testCorpNum);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetPartnerURL
     */
    public function GetPartnerURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillHTCashbill->GetPartnerURL($testCorpNum, $TOGO);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
    }

    /**
     * 홈택스연동 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/point#GetChargeInfo
     */
    public function GetChargeInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->GetChargeInfo($testCorpNum,$testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     *  사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#CheckIsMember
     */
    public function CheckIsMember(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 연동신청 시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillHTCashbill->CheckIsMember($testCorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#CheckID
     */
    public function CheckID(){

        // 조회할 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->CheckID($testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#JoinMember
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
        $joinForm->ContactName = '담당자성명';

        // 담당자 이메일
        $joinForm->ContactEmail = '';

        // 담당자 연락처
        $joinForm->ContactTEL = '';

        // 아이디, 6자 이상 20자미만
        $joinForm->ID = 'userid_phpdd';

        // 비밀번호, 8자 이상 20자 이하(영문, 숫자, 특수문자 조합)
        $joinForm->Password = 'asdf1234!@';

        try {
            $result = $this->PopbillHTCashbill->JoinMember($joinForm);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#GetAccessURL
     */
    public function GetAccessURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillHTCashbill->GetAccessURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);

    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#GetAccessURL
     */
    public function GetCorpInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        try {
            $CorpInfo = $this->PopbillHTCashbill->GetCorpInfo($testCorpNum);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#UpdateCorpInfo
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

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result =  $this->PopbillHTCashbill->UpdateCorpInfo($testCorpNum, $CorpInfo, $testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#RegistContact
     */
    public function RegistContact(){

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 담당자 정보 객체 생성
        $ContactInfo = new ContactInfo();

        // 담당자 아이디
        $ContactInfo->id = '001';

        // 담당자 비밀번호, 8자 이상 20자 이하(영문, 숫자, 특수문자 조합)
        $ContactInfo->Password = 'asdf123!@#';

        // 담당자명
        $ContactInfo->personName = '담당자_수정';

        // 연락처
        $ContactInfo->tel = '';

        // 이메일주소
        $ContactInfo->email = '';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillHTCashbill->RegistContact($testCorpNum, $ContactInfo, $testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#GetContactInfo
     */
    public function GetContactInfo(){
        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        //확인할 담당자 아이디
        $contactID = 'checkContact';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $ContactInfo = $this->PopbillHTCashbill->GetContactInfo($testCorpNum, $contactID, $testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#ListContact
     */
    public function ListContact(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $ContactList = $this->PopbillHTCashbill->ListContact($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/htcashbill/php/api/member#UpdateContact
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
        $ContactInfo->id = '';

        // 담당자 연락처
        $ContactInfo->tel = '';

        // 이메일 주소
        $ContactInfo->email = '';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        try {
            $result = $this->PopbillHTCashbill->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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
