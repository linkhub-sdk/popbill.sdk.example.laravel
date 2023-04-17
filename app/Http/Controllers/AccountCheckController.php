<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillAccountCheck;
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class AccountCheckController extends Controller
{
    public function __construct() {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 예금주조회 서비스 클래스 초기화
        $this->PopbillAccountCheck = new PopbillAccountCheck(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정값, true-개발용, false-상업용
        $this->PopbillAccountCheck->IsTest(config('popbill.IsTest'));

        // 인증토큰의 IP제한기능 사용여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillAccountCheck->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 팝빌 API 서비스 고정 IP 사용여부, true-사용, false-미사용, 기본값(false)
        $this->PopbillAccountCheck->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬서버 시간 사용 여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillAccountCheck->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request){
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 1건의 예금주성명을 조회합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/check#CheckAccountInfo
     */
    public function CheckAccountInfo(){

        // 팝빌회원 사업자번호
        $MemberCorpNum = "1234567890";

        // 팝빌회원 아이디
        $testUserID = "testkorea";

        // 조회할 기관코드
        $bankCode = "";

        // 조회할 기관의 계좌번호 (하이픈 '-' 제외 8자리 이상 14자리 이하)
        $accountNumber = "";

        try {
            $result = $this->PopbillAccountCheck->checkAccountInfo($MemberCorpNum, $bankCode, $accountNumber, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/CheckAccountInfo', ['Result' => $result] );
    }

    /**
     * 1건의 예금주실명을 조회합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/check#CheckDepositorInfo
     */
    public function CheckDepositorInfo(){

        // 팝빌회원 사업자번호
        $MemberCorpNum = "1234567890";

        //팝빌회원 아이디
        $testUserID = "testkorea";

        // 조회할 기관코드
        $bankCode = "";

        //  조회할 기관의 계좌번호 (하이픈 '-' 제외 8자리 이상 14자리 이하)
        $accountNumber = "";

        //등록번호 유형, P-개인, B-사업자
        $identityNumType = "";

        // 등록번호
        // └ 등록번호 유형 값이 "B"인 경우 사업자번호(10 자리) 입력
        // └ 등록번호 유형 값이 "P"인 경우 생년월일(6 자리) 입력 (형식 : YYMMDD)
        // 하이픈 '-' 제외하고 입력
        $identityNum = "";

        try {
            $result = $this->PopbillAccountCheck->checkDepositorInfo($MemberCorpNum, $bankCode, $accountNumber, $identityNumType, $identityNum, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/CheckDepositorInfo', ['Result' => $result] );
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetBalance
     */
    public function GetBalance(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillAccountCheck->GetBalance($testCorpNum);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetUseHistory
     */
    public function GetUseHistory(){

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $testCorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20230101";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20230131";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 정렬방향, A-오름차순, D-내림차순
        $Order = "D";

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try	{
            $result = $this->PopbillAccountCheck->GetUseHistory($testCorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetPaymentHistory
     */
    public function GetPaymentHistory(){

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $testCorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20230101";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20230131";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try	{
            $result = $this->PopbillAccountCheck->GetPaymentHistory($testCorpNum, $SDate, $EDate, $Page, $PerPage, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 환불신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetRefundHistory
     */
    public function GetRefundHistory(){

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $testCorpNum = "1234567890";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try	{
            $result = $this->PopbillAccountCheck->GetRefundHistory($testCorpNum, $Page, $PerPage, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트를 환불 신청합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#Refund
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
            $result = $this->PopbillAccountCheck->Refund($testCorpNum, $RefundForm, $testUserID);
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
     * 연동회원 포인트 충전을 위해 무통장입금을 신청합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#PaymentRequest
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

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->PaymentRequest($testCorpNum, $paymentForm, $testUserID);
            $code = $result->code;
            $message = $result->message;
            $settleCode = $result->settleCode;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/PaymentResponse', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 무통장 입금신청내역 1건을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetSettleResult
     */
    public function GetSettleResult(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        // paymentRequest 를 통해 얻은 settleCode.
        $settleCode = '202210040000000070';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->GetSettleResult($testCorpNum, $settleCode, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/PaymentHistory', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetChargeURL
     */
    public function GetChargeURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetChargeURL($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetPaymentURL
     */
    public function GetPaymentURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetPaymentURL($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetUseHistoryURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
          return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 포인트 사용내역 팝업 URL" , 'value' => $url]);

    }

    /**
     * 예금주조회시 과금되는 포인트 단가를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetUnitCost
     */
    public function GetUnitCost(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 서비스 유형 , "성명" / "실명" 중 택 1
        $serviceType = '실명';

        try {
            $unitCost = $this->PopbillAccountCheck->GetUnitCost($testCorpNum, $serviceType, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "예금주조회 단가" , 'value' => $unitCost]);
    }

    /**
     * 파트너의 잔여포인트를 확인합니다.
     * - 과금방식이 연동과금인 경우 연동회원 잔여포인트 확인(GetBalance API) 함수를 이용하시기 바랍니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetPartnerBalance
     */
    public function GetPartnerBalance(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillAccountCheck->GetPartnerBalance($testCorpNum);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetPartnerURL
     */
    public function GetPartnerURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillAccountCheck->GetPartnerURL($testCorpNum, $TOGO);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
    }

    /**
     * 예금주조회 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/point#GetChargeInfo
     */

    public function GetChargeInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 서비스 유형 , "성명" / "실명" 중 택 1
        $serviceType = '성명';

        try {
            $result = $this->PopbillAccountCheck->GetChargeInfo($testCorpNum, $testUserID, $serviceType);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#CheckIsMember
     */
    public function CheckIsMember(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 연동신청시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
          $result = $this->PopbillAccountCheck->CheckIsMember($testCorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#CheckID
     */
    public function CheckID(){

        // 중복여부를 확인할 아이디
        $testUserID = 'testkorea';

        try {
          $result = $this->PopbillAccountCheck->CheckID($testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#JoinMember
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
            $result = $this->PopbillAccountCheck->JoinMember($joinForm);
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
     * 연동회원의 회사정보를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#GetCorpInfo
     */
    public function GetCorpInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillAccountCheck->GetCorpInfo($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#UpdateCorpInfo
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
            $result =  $this->PopbillAccountCheck->UpdateCorpInfo($testCorpNum, $CorpInfo, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#RegistContact
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
        $ContactInfo->tel = '';

        // 이메일주소
        $ContactInfo->email = '';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->RegistContact($testCorpNum, $ContactInfo, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#GetContactInfo
     */
    public function GetContactInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        //확인할 담당자 아이디
        $contactID = 'checkContact';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $ContactInfo = $this->PopbillAccountCheck->GetContactInfo($testCorpNum, $contactID, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#ListContact
     */
    public function ListContact(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
        $ContactList = $this->PopbillAccountCheck->ListContact($testCorpNum, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#UpdateContact
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
        $ContactInfo->tel = '';

        // 이메일 주소
        $ContactInfo->email = '';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        try {
            $result = $this->PopbillAccountCheck->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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
     * - https://developers.popbill.com/reference/accountcheck/php/api/member#GetAccessURL
     */
    public function GetAccessURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetAccessURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);
    }

/**
* 회원 탈퇴 요청을 합니다.
* - https://developers.popbill.com/reference/accountcheck/php/api/member#QuitRequest
*/
public function QuitRequest(){

    // 팝빌 회원 사업자 번호
    $CorpNum = "1234567890";

    // 회원 탈퇴 사유
    $QuitReason = "탈퇴합니다.";

    // 팝빌 회원 아이디
    $UserID = "testkorea";

    try {
    $result = $this->PopbillAccountCheck->QuitRequest($CorpNum, $QuitReason, $UserID);
    }
    catch(PopbillException $pe) {
    $code = $pe->getCode();
    $message = $pe->getMessage();
    return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('PResponse', ['code' => $result->code , 'message'=> $result->message]);

    }

    /**
        * 환불 가능 포인트를 조회합니다.
        * * - https://developers.popbill.com/reference/accountcheck/php/api/member#GetRefundablePoint
        */
    public function GetRefundablePoint(){

    // 팝빌 회원 사업자 번호
    $CorpNum = "1234567890";

    // 팝빌 회원 아이디
    $UserID = "testkorea";

    try {
    $result = $this->PopbillAccountCheck->GetRefundablePoint($CorpNum, $UserID);
    }
    catch(PopbillException $pe) {
    $code = $pe->getCode();
    $message = $pe->getMessage();
    return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetRefundablePoint', ['refundableBalance' => $result->refundableBalance]);

    }

    /**
        * 환불 신청 상태를 조회합니다
        * * - https://developers.popbill.com/reference/accountcheck/php/api/member#GetRefundResult
        */
    public function GetRefundResult(){

    // 팝빌 회원 사업자 번호
    $CorpNum = "1234567890";

    // 환불 신청 코드
    $RefundCode = "";

    // 팝빌 회원 아이디
    $UserID = "testkorea";

    try {
    $result = $this->PopbillAccountCheck->GetRefundResult($CorpNum, $RefundCode, $UserID);

    }
    catch(PopbillException $pe) {
    $code = $pe->getCode();
    $message = $pe->getMessage();
    return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('AccountCheck/GetRefundResult', ['reqDT' => $result->reqDT,
    'requestPoint' => $result->requestPoint,
    'accountBank' => $result->accountBank,
    'accountNum' => $result->accountNum,
    'accountName' => $result->accountName,
    'state' => $result->state,
    'reason' => $result->reason]
    );

    }
}
