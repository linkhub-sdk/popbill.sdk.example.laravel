<?php
/**
  * 팝빌 예금주조회 API PHP SDK Laravel Example
  *
  * Laravel 연동 튜토리얼 안내 : https://developers.popbill.com/guide/accountcheck/php/getting-started/tutorial?fwn=laravel
  * 연동 기술지원 연락처 : 1600-9854
  * 연동 기술지원 이메일 : code@linkhubcorp.com
  */
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
    public function __construct()
    {
        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 예금주조회 서비스 클래스 초기화
        $this->PopbillAccountCheck = new PopbillAccountCheck(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정, true-테스트, false-운영(Production), (기본값:false)
        $this->PopbillAccountCheck->IsTest(config('popbill.IsTest'));

        // 인증토큰 IP 검증 설정, true-사용, false-미사용, (기본값:true)
        $this->PopbillAccountCheck->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 통신 IP 고정, true-사용, false-미사용, (기본값:false)
        $this->PopbillAccountCheck->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬시스템 시간 사용여부, true-사용, false-미사용, (기본값:true)
        $this->PopbillAccountCheck->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request)
    {
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 1건의 예금주성명을 조회합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/check#CheckAccountInfo
     */
    public function CheckAccountInfo()
    {
        // 팝빌회원 사업자번호
        $MemberCorpNum = "1234567890";

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 조회할 기관코드
        $bankCode = "";

        // 조회할 기관의 계좌번호 (하이픈 '-' 제외 7자리 이상 14자리 이하)
        $accountNumber = "";

        try {
            $result = $this->PopbillAccountCheck->checkAccountInfo($MemberCorpNum, $bankCode, $accountNumber, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/CheckAccountInfo', ['Result' => $result]);
    }

    /**
     * 1건의 예금주실명을 조회합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/api/check#CheckDepositorInfo
     */
    public function CheckDepositorInfo()
    {
        // 팝빌회원 사업자번호
        $MemberCorpNum = "1234567890";

        //팝빌회원 아이디
        $UserID = "testkorea";

        // 조회할 기관코드
        $bankCode = "";

        //  조회할 기관의 계좌번호 (하이픈 '-' 제외 7자리 이상 14자리 이하)
        $accountNumber = "";

        //등록번호 유형, P-개인, B-사업자
        $identityNumType = "";

        // 등록번호
        // └ 등록번호 유형 값이 "B"인 경우 사업자번호(10 자리) 입력
        // └ 등록번호 유형 값이 "P"인 경우 생년월일(6 자리) 입력 (형식 : YYMMDD)
        // 하이픈 '-' 제외하고 입력
        $identityNum = "";

        try {
            $result = $this->PopbillAccountCheck->checkDepositorInfo($MemberCorpNum, $bankCode, $accountNumber, $identityNumType, $identityNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('AccountCheck/CheckDepositorInfo', ['Result' => $result]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetBalance
     */
    public function GetBalance()
    {
        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillAccountCheck->GetBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetUseHistory
     */
    public function GetUseHistory()
    {
        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20250801";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20250831";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 정렬방향, A-오름차순, D-내림차순
        $Order = "D";

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->GetUseHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetPaymentHistory
     */
    public function GetPaymentHistory()
    {
        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20250801";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20250831";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->GetPaymentHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 환불신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetRefundHistory
     */
    public function GetRefundHistory()
    {
        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->GetRefundHistory($CorpNum, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트를 환불 신청합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#Refund
     */
    public function Refund()
    {
        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

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
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->Refund($CorpNum, $RefundForm, $UserID);
            $code = $result->code;
            $message = $result->message;
            $refundCode = $result->refundCode;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message, 'refundCode' => $refundCode]);
    }

    /**
     * 연동회원 포인트 충전을 위해 무통장입금을 신청합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#PaymentRequest
     */
    public function PaymentRequest()
    {
        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        $PaymentForm = new PaymentForm();

        // 담당자명
        // 미입력 시 기본값 적용 - 팝빌 회원 담당자명.
        $PaymentForm->settlerName = '담당자명';

        // 담당자 이메일
        // 사이트에서 신청하면 자동으로 담당자 이메일.
        // 미입력 시 공백 처리
        $PaymentForm->settlerEmail = 'test@test.com';

        // 담당자 휴대폰
        // 무통장 입금 승인 알림톡이 전송됩니다.
        $PaymentForm->notifyHP = '01012341234';

        // 입금자명
        $PaymentForm->paymentName = '입금자명';

        // 결제금액
        $PaymentForm->settleCost = '11000';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->PaymentRequest($CorpNum, $PaymentForm, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentResponse', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 무통장 입금신청내역 1건을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetSettleResult
     */
    public function GetSettleResult()
    {
        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // paymentRequest 를 통해 얻은 settleCode.
        $SettleCode = '202210040000000070';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->GetSettleResult($CorpNum, $SettleCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistory', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetChargeURL
     */
    public function GetChargeURL()
    {
        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetChargeURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 연동회원 포인트 결제내역 확인을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetPaymentURL
     */
    public function GetPaymentURL()
    {
        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetPaymentURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 포인트 결제내역 팝업 URL", 'value' => $url]);
    }

    /**
     * 연동회원 포인트 사용내역 확인을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL()
    {
        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetUseHistoryURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 포인트 사용내역 팝업 URL", 'value' => $url]);
    }

    /**
     * 예금주조회시 과금되는 포인트 단가를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetUnitCost
     */
    public function GetUnitCost()
    {
        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 서비스 유형 , "성명" / "실명" 중 택 1
        $serviceType = '실명';

        try {
            $unitCost = $this->PopbillAccountCheck->GetUnitCost($CorpNum, $serviceType, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "예금주조회 단가", 'value' => $unitCost]);
    }

    /**
     * 파트너의 잔여포인트를 확인합니다.
     * - 과금방식이 연동과금인 경우 연동회원 잔여포인트 확인(GetBalance API) 함수를 이용하시기 바랍니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetPartnerBalance
     */
    public function GetPartnerBalance()
    {
        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillAccountCheck->GetPartnerBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 파트너 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetPartnerURL
     */
    public function GetPartnerURL()
    {
        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillAccountCheck->GetPartnerURL($CorpNum, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 예금주조회 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetChargeInfo
     */

    public function GetChargeInfo()
    {
        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 서비스 유형 , "성명" / "실명" 중 택 1
        $serviceType = '성명';

        try {
            $result = $this->PopbillAccountCheck->GetChargeInfo($CorpNum, $UserID, $serviceType);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#CheckIsMember
     */
    public function CheckIsMember()
    {
        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 연동신청시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillAccountCheck->CheckIsMember($CorpNum, $LinkID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 사용하고자 하는 아이디의 중복여부를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#CheckID
     */
    public function CheckID()
    {
        // 중복여부를 확인할 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->CheckID($UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 사용자를 연동회원으로 가입처리합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#JoinMember
     */
    public function JoinMember()
    {
        $JoinForm = new JoinForm();

        // 링크아이디
        $JoinForm->LinkID = config('popbill.LinkID');

        // 사업자번호, "-"제외 10자리
        $JoinForm->CorpNum = '1234567890';

        // 대표자성명
        $JoinForm->CEOName = '대표자성명';

        // 사업자상호
        $JoinForm->CorpName = '테스트사업자상호';

        // 사업자주소
        $JoinForm->Addr = '테스트사업자주소';

        // 업태
        $JoinForm->BizType = '업태';

        // 종목
        $JoinForm->BizClass = '종목';

        // 담당자명
        $JoinForm->ContactName = '담당자성명';

        // 담당자 이메일
        $JoinForm->ContactEmail = '';

        // 담당자 연락처
        $JoinForm->ContactTEL = '';

        // 아이디, 6자 이상 20자미만
        $JoinForm->ID = 'userid_phpdd';

        // 비밀번호, 8자 이상 20자 이하(영문, 숫자, 특수문자 조합)
        $JoinForm->Password = 'asdf1234!@';

        try {
            $result = $this->PopbillAccountCheck->JoinMember($JoinForm);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#GetCorpInfo
     */
    public function GetCorpInfo()
    {
        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillAccountCheck->GetCorpInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#UpdateCorpInfo
     */
    public function UpdateCorpInfo()
    {
        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

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
        $UserID = 'testkorea';

        try {
            $result =  $this->PopbillAccountCheck->UpdateCorpInfo($CorpNum, $CorpInfo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 담당자(팝빌 로그인 계정)를 추가합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#RegistContact
     */
    public function RegistContact()
    {
        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

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
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->RegistContact($CorpNum, $ContactInfo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#GetContactInfo
     */
    public function GetContactInfo()
    {
        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        //확인할 담당자 아이디
        $ContactID = 'checkContact';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactInfo = $this->PopbillAccountCheck->GetContactInfo($CorpNum, $ContactID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#ListContact
     */
    public function ListContact()
    {
        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactList = $this->PopbillAccountCheck->ListContact($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#UpdateContact
     */
    public function UpdateContact()
    {
        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

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
            $result = $this->PopbillAccountCheck->UpdateContact($CorpNum, $ContactInfo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 담당자(팝빌 로그인 계정)를 삭제합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#DeleteContact
     */
    public function DeleteContact()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 삭제할 담당자 아이디
        $TargetUserID = 'testkorea20250818_01';

        // 팝빌 회원 관리자 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillAccountCheck->DeleteContact($CorpNum, $TargetUserID, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#GetAccessURL
     */
    public function GetAccessURL()
    {
        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillAccountCheck->GetAccessURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL", 'value' => $url]);
    }

    /**
     * 가입된 연동회원의 탈퇴를 요청합니다.
     * 회원탈퇴 신청과 동시에 팝빌의 모든 서비스 이용이 불가하며, 관리자를 포함한 모든 담당자 계정도 일괄탈퇴 됩니다.
     * 회원탈퇴로 삭제된 데이터는 복원이 불가능합니다.
     * 관리자 계정만 사용 가능합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/member#QuitMember
     */
    public function QuitMember()
    {
        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 회원 탈퇴 사유
        $QuitReason = "탈퇴 테스트";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $result = $this->PopbillAccountCheck->QuitMember($CorpNum, $QuitReason, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PResponse', ['code' => $result->code, 'message' => $result->message]);
    }

    /**
     * 환불 가능한 포인트를 확인합니다. (보너스 포인트는 환불가능포인트에서 제외됩니다.)
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetRefundableBalance
     */
    public function GetRefundableBalance()
    {
        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $refundableBalance = $this->PopbillAccountCheck->GetRefundableBalance($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundableBalance', ['refundableBalance' => $refundableBalance]);
    }

    /**
     * 포인트 환불에 대한 상세정보 1건을 확인합니다.
     * - https://developers.popbill.com/reference/accountcheck/php/common-api/point#GetRefundInfo
     */
    public function GetRefundInfo()
    {
        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 환불코드
        $RefundCode = "023040000015";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $result = $this->PopbillAccountCheck->GetRefundInfo($CorpNum, $RefundCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundInfo', ['result' => $result]);
    }
}
