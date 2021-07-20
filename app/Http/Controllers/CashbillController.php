<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillCashbill;
use Linkhub\Popbill\Cashbill;

class CashbillController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 현금영수증 서비스 클래스 초기화
    $this->PopbillCashbill = new PopbillCashbill(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillCashbill->IsTest(config('popbill.IsTest'));

    // 인증토큰의 IP제한기능 사용여부, 권장(true)
    $this->PopbillCashbill->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

    // 팝빌 API 서비스 고정 IP 사용여부(GA), true-사용, false-미사용, 기본값(false)
    $this->PopbillCashbill->UseStaticIP(config('popbill.UseStaticIP'));

    // 로컬서버 시간 사용 여부 true(기본값) - 사용, false(미사용)
    $this->PopbillCashbill->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * 현금영수증 문서번호 중복여부를 확인합니다.
   * - 문서번호는 1~24자리로 숫자, 영문 '-', '_' 조합으로 사업자별로 중복되지 않도록 구성해야합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#CheckMgtKeyInUse
   */
  public function CheckMgtKeyInUse(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 1~24자리, 영문, 숫자, '-', '_' 조합으로 사업자별로 중복되지 않도록 구성
    $mgtKey = '20190101-001';

    try {
        $result = $this->PopbillCashbill->CheckMgtKeyInUse($testCorpNum, $mgtKey);
        $result ? $result = '사용중' : $result = '미사용중';
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "문서번호 사용여부 =>".$mgtKey."", 'value' => $result]);

  }

  /**
   * 1건의 현금영수증을 [즉시발행]합니다.
   * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
   * - https://docs.popbill.com/cashbill/phplaravel/api#RegistIssue
   */
  public function RegistIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 문서번호, 사업자별로 중복없이 1~24자리 영문, 숫자, '-', '_' 조합으로 구성
    $mgtKey = '20191024-001';

    // 메모
    $memo = '현금영수증 즉시발행 메모';

    // 안내메일 제목, 공백처리시 기본양식으로 전송
    $emailSubject = '';

    // 현금영수증 객체 생성
    $Cashbill = new Cashbill();

    // [필수] 현금영수증 문서번호,
    $Cashbill->mgtKey = $mgtKey;

    // [필수] 문서형태, (승인거래, 취소거래) 중 기재
    $Cashbill->tradeType = '승인거래';

    // [필수] 거래구분, (소득공제용, 지출증빙용) 중 기재
    $Cashbill->tradeUsage = '소득공제용';

    // [필수] 거래유형, (일반, 도서공연, 대중교통) 중 기재
    $Cashbill->tradeOpt = '일반';

    // [필수] 과세형태, (과세, 비과세) 중 기재
    $Cashbill->taxationType = '과세';

    // [필수] 거래금액, ','콤마 불가 숫자만 가능
    $Cashbill->totalAmount = '11000';

    // [필수] 공급가액, ','콤마 불가 숫자만 가능
    $Cashbill->supplyCost = '10000';

    // [필수] 부가세, ','콤마 불가 숫자만 가능
    $Cashbill->tax = '1000';

    // [필수] 봉사료, ','콤마 불가 숫자만 가능
    $Cashbill->serviceFee = '0';

    // [필수] 가맹점 사업자번호
    $Cashbill->franchiseCorpNum = $testCorpNum;

    // 가맹점 상호
    $Cashbill->franchiseCorpName = '발행자 상호';

    // 가맹점 대표자 성명
    $Cashbill->franchiseCEOName = '발행자 대표자명';

    // 가맹점 주소
    $Cashbill->franchiseAddr = '발행자 주소';

    // 가맹점 전화번호
    $Cashbill->franchiseTEL = '070-1234-1234';

    // [필수] 식별번호, 거래구분에 따라 작성
    // 소득공제용 - 주민등록/휴대폰/카드번호 기재가능
    // 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호 기재가능
    $Cashbill->identityNum = '0101112222';

    // 주문자명
    $Cashbill->customerName = '고객명';

    // 주문상품명
    $Cashbill->itemName = '상품명';

    // 주문주문번호
    $Cashbill->orderNumber = '주문번호';

    // 주문자 이메일
    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $Cashbill->email = 'test@test.com';

    // 주문자 휴대폰
    $Cashbill->hp = '010-111-222';

    // 발행시 알림문자 전송여부
    $Cashbill->smssendYN = false;

    try {
        $result = $this->PopbillCashbill->RegistIssue($testCorpNum, $Cashbill, $memo, $testUserID, $emailSubject);
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
   * 1건의 현금영수증을 [임시저장]합니다.
   * - [임시저장] 상태의 현금영수증은 발행(Issue API)을 호출해야만 국세청에 전송됩니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#Register
   */
  public function Register(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 발행자별 중복없이 1~24자리 영문,숫자로 구성
    $mgtKey = '20190214-002';

    // 현금영수증 객체 생성
    $Cashbill = new Cashbill();

    // [필수] 현금영수증 문서번호,
    $Cashbill->mgtKey = $mgtKey;

    // [필수] 문서형태, (승인거래, 취소거래) 중 기재
    $Cashbill->tradeType = '승인거래';

    // [필수] 거래구분, (소득공제용, 지출증빙용) 중 기재
    $Cashbill->tradeUsage = '소득공제용';

    // [필수] 거래유형, (일반, 도서공연, 대중교통) 중 기재
    $Cashbill->tradeOpt = '일반';

    // [필수] 과세형태, (과세, 비과세) 중 기재
    $Cashbill->taxationType = '과세';

    // [필수] 거래금액, ','콤마 불가 숫자만 가능
    $Cashbill->totalAmount = '11000';

    // [필수] 공급가액, ','콤마 불가 숫자만 가능
    $Cashbill->supplyCost = '10000';

    // [필수] 부가세, ','콤마 불가 숫자만 가능
    $Cashbill->tax = '1000';

    // [필수] 봉사료, ','콤마 불가 숫자만 가능
    $Cashbill->serviceFee = '0';

    // [필수] 가맹점 사업자번호
    $Cashbill->franchiseCorpNum = $testCorpNum;

    // 가맹점 상호
    $Cashbill->franchiseCorpName = '발행자 상호';

    // 가맹점 대표자 성명
    $Cashbill->franchiseCEOName = '발행자 대표자명';

    // 가맹점 주소
    $Cashbill->franchiseAddr = '발행자 주소';

    // 가맹점 전화번호
    $Cashbill->franchiseTEL = '070-1234-1234';

    // [필수] 식별번호, 거래구분에 따라 작성
    // 소득공제용 - 주민등록/휴대폰/카드번호 기재가능
    // 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호 기재가능
    $Cashbill->identityNum = '01011112222';

    // 주문자명
    $Cashbill->customerName = '고객명';

    // 주문상품명
    $Cashbill->itemName = '상품명';

    // 주문번호
    $Cashbill->orderNumber = '주문번호';

    // 주문자 이메일
    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $Cashbill->email = 'test@test.com';

    // 주문자 휴대폰
    $Cashbill->hp = '010-111-222';

    // 발행시 알림문자 전송여부
    $Cashbill->smssendYN = false;

    try {
        $result = $this->PopbillCashbill->Register($testCorpNum, $Cashbill);
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
   * 1건의 현금영수증을 [수정]합니다.
   * - [임시저장] 상태의 현금영수증만 수정할 수 있습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#Update
   */
  public function Update(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190214-002';

    // 현금영수증 객체 생성
    $Cashbill = new Cashbill();

    // [필수] 현금영수증 문서번호,
    $Cashbill->mgtKey = $mgtKey;

    // [필수] 문서형태, (승인거래, 취소거래) 중 기재
    $Cashbill->tradeType = '승인거래';

    // [필수] 거래구분, (소득공제용, 지출증빙용) 중 기재
    $Cashbill->tradeUsage = '소득공제용';

    // [필수] 거래유형, (일반, 도서공연, 대중교통) 중 기재
    $Cashbill->tradeOpt = '일반';

    // [필수] 과세형태, (과세, 비과세) 중 기재
    $Cashbill->taxationType = '과세';

    // [필수] 거래금액, ','콤마 불가 숫자만 가능
    $Cashbill->totalAmount = '11000';

    // [필수] 공급가액, ','콤마 불가 숫자만 가능
    $Cashbill->supplyCost = '10000';

    // [필수] 부가세, ','콤마 불가 숫자만 가능
    $Cashbill->tax = '1000';

    // [필수] 봉사료, ','콤마 불가 숫자만 가능
    $Cashbill->serviceFee = '0';

    // [필수] 가맹점 사업자번호
    $Cashbill->franchiseCorpNum = $testCorpNum;

    // 가맹점 상호
    $Cashbill->franchiseCorpName = '발행자 상호_수정';

    // 가맹점 대표자 성명
    $Cashbill->franchiseCEOName = '발행자 대표자명';

    // 가맹점 주소
    $Cashbill->franchiseAddr = '발행자 주소';

    // 가맹점 전화번호
    $Cashbill->franchiseTEL = '070-1234-1234';

    // [필수] 식별번호, 거래구분에 따라 작성
    // 소득공제용 - 주민등록/휴대폰/카드번호 기재가능
    // 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호 기재가능
    $Cashbill->identityNum = '01011112222';

    // 주문자명
    $Cashbill->customerName = '고객명';

    // 주문상품명
    $Cashbill->itemName = '상품명';

    // 주문번호
    $Cashbill->orderNumber = '주문번호';

    // 주문자 이메일
    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $Cashbill->email = 'test@test.com';

    // 주문자 휴대폰
    $Cashbill->hp = '010-4324-5117';

    // 발행시 알림문자 전송여부
    $Cashbill->smssendYN = false;

    try {
        $result = $this->PopbillCashbill->Update($testCorpNum, $mgtKey, $Cashbill);
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
   * 1건의 [임시저장] 현금영수증을 [발행]합니다.
   * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
   * - https://docs.popbill.com/cashbill/phplaravel/api#CBIssue
   */
  public function Issue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190214-002';

    // 메모
    $memo = '현금영수증 발행메모';

    try {
        $result = $this->PopbillCashbill->Issue($testCorpNum, $mgtKey, $memo);
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
   * [발행완료] 상태의 현금영수증을 [발행취소]합니다.
   * - 발행취소는 국세청 전송전에만 가능합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#CancelIssue
   */
  public function CancelIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190214-002';

    // 메모
    $memo = '현금영수증 발행취소메모';

    try	{
        $result = $this->PopbillCashbill->CancelIssue($testCorpNum, $mgtKey, $memo);
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
   * 1건의 현금영수증을 [삭제]합니다.
   * - 현금영수증을 삭제하면 사용된 문서번호(mgtKey)를 재사용할 수 있습니다.
   * - 삭제가능한 문서 상태 : [임시저장], [발행취소]
   * - https://docs.popbill.com/cashbill/phplaravel/api#Delete
   */
  public function Delete(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190214-002';

    try {
        $result = $this->PopbillCashbill->Delete($testCorpNum, $mgtKey);
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
   * 1건의 취소현금영수증을 [즉시발행]합니다.
   * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
   * - https://docs.popbill.com/cashbill/phplaravel/api#RevokeRegistIssue
   */
  public function RevokeRegistIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 사업자별로 중복없이 1~24자리 영문, 숫자, '-', '_' 조합으로 구성
    $mgtKey = '20190214-005';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 작성형식(yyyyMMdd) 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20190211';

    try {
        $result = $this->PopbillCashbill->RevokeRegistIssue($testCorpNum, $mgtKey, $orgConfirmNum, $orgTradeDate);
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
   * 1건의 (부분)취소현금영수증을 [즉시발행]합니다.
   * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
   * - https://docs.popbill.com/cashbill/phplaravel/api#RevokeRegistIssue
   */
  public function RevokeRegistIssue_part(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 문서번호, 사업자별로 중복없이 1~24자리 영문, 숫자, '-', '_' 조합으로 구성
    $mgtKey = '20190214-006';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20190211';

    // 안내문자 전송여부
    $smssendYN = false;

    // 메모
    $memo = '부분취소현금영수증 발행메모';

    // 부분취소여부, true-부분취소, false-전체취소
    $isPartCancel = true;

    // 취소사유, 1-거래취소, 2-오류발급취소, 3-기타
    $cancelType = 1;

    // [취소] 공급가액
    $supplyCost = '4000';

    // [취소] 세액
    $tax = '400';

    // [취소] 봉사료
    $serviceFee = '0';

    // [취소] 합계금액
    $totalAmount = '4400';

    try {
        $result = $this->PopbillCashbill->RevokeRegistIssue($testCorpNum, $mgtKey, $orgConfirmNum,
          $orgTradeDate, $smssendYN, $memo, $testUserID, $isPartCancel, $cancelType,
          $supplyCost, $tax, $serviceFee, $totalAmount);

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
   * 1건의 취소현금영수증을 [임시저장]합니다.
   * - [임시저장] 상태의 현금영수증은 발행(Issue API)을 호출해야만 국세청에 전송됩니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#RevokeRegister
   */
  public function RevokeRegister(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 사업자별로 중복없이 1~24자리 영문, 숫자, '-', '_' 조합으로 구성
    $mgtKey = '20190214-007';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20190211';

    try {
        $result = $this->PopbillCashbill->RevokeRegister($testCorpNum, $mgtKey, $orgConfirmNum, $orgTradeDate);
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
   * 1건의 (부분)취소현금영수증을 [임시저장]합니다.
   * - [임시저장] 상태의 현금영수증은 발행(Issue API)을 호출해야만 국세청에 전송됩니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#RevokeRegister
   */
  public function RevokeRegister_part(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 문서번호, 사업자별로 중복없이 1~24자리 영문, 숫자, '-', '_' 조합으로 구성
    $mgtKey = '20190214-009';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20190211';

    // 안내문자 전송여부
    $smssendYN = false;

    // 부분취소여부, true-부분취소, false-전체취소
    $isPartCancel = true;

    // 취소사유, 1-거래취소, 2-오류발급취소, 3-기타
    $cancelType = 1;

    // [취소] 공급가액
    $supplyCost = '4000';

    // [취소] 세액
    $tax = '400';

    // [취소] 봉사료
    $serviceFee = '0';

    // [취소] 합계금액
    $totalAmount = '4400';

    try {
        $result = $this->PopbillCashbill->RevokeRegister($testCorpNum, $mgtKey, $orgConfirmNum, $orgTradeDate,
            $smssendYN, $testUserID, $isPartCancel, $cancelType, $supplyCost, $tax, $serviceFee, $totalAmount);
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
   * 1건의 현금영수증 상태/요약 정보를 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetInfo
   */
  public function GetInfo(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190214-005';

    try {
      $result = $this->PopbillCashbill->GetInfo($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Cashbill/GetInfo', ['CashbillInfo' => [$result] ] );
  }

  /**
   * 대량의 현금영수증 상태/요약 정보를 확인합니다. (최대 1000건)
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetInfos
   */
  public function GetInfos(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    // 문서번호 배열, 최대 1000건
    $MgtKeyList = array(
        '20190214-001',
        '20190214-005',
    );

    try {
        $result = $this->PopbillCashbill->GetInfos($testCorpNum, $MgtKeyList);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('Cashbill/GetInfo', ['CashbillInfo' => $result ] );
  }

  /**
   * 현금영수증 1건의 상세정보를 조회합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetDetailInfo
   */
  public function GetDetailInfo(){
    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190214-005';

    try {
        $result = $this->PopbillCashbill->GetDetailInfo($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Cashbill/GetDetailInfo', ['CashbillInfo' => $result ] );
  }

  /**
   * 검색조건을 사용하여 현금영수증 목록을 조회합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#Search
   */
  public function Search(){

    // [필수] 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    // [필수] 조회일자 유형, R-등록일자, T-거래일자, I-발행일자
    $DType = 'R';

    // [필수] 시작일자
    $SDate = '20190101';

    // [필수] 종료일자
    $EDate = '20190228';

    // 문서상태코드, 2,3번째 자리 와일드카드 사용가능, 미기재시 전체조회
    $State = array(
        '100',
        '2**',
        '3**',
        '4**'
    );

    // 문서형태, N-일반현금영수증, C-취소현금영수증
    $TradeType = array(
        'N',
        'C'
    );

    // 거래구분, P-소득공제, C-지출증빙
    $TradeUsage = array(
        'P',
        'C'
    );

    // 거래유형, N-일반, B-도서공연, T-대중교통
    $TradeOpt = array(
        'N',
        'B',
        'T'
    );

    // 과세형태, T-과세, N-비과세
    $TaxationType = array(
        'T',
        'N'
    );

    // 페이지번호, 기본값 1
    $Page = 1;

    // 페이지당 검색갯수, 기본값 500, 최대값 1000
    $PerPage = 30;

    // 정렬방향, D-내림차순, A-오름차순
    $Order = 'D';

    // 식별번호 조회, 미기재시 전체조회
    $QString = '';

    try {
        $result = $this->PopbillCashbill->Search( $testCorpNum, $DType, $SDate,
          $EDate, $State, $TradeType, $TradeUsage, $TaxationType, $Page, $PerPage,
          $Order, $QString, $TradeOpt);
    }	catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Cashbill/Search', ['Result' => $result] );
  }

  /**
   * 현금영수증 상태 변경이력을 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetLogs
   */
  public function GetLogs(){

    // 팝빌회원, 사업자번호
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    try {
        $result = $this->PopbillCashbill->GetLogs($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('GetLogs', ['Result' => $result] );

  }

  /**
   * 팝빌 현금영수증 문서함 팝업 URL을 반환합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetURL
   */
  public function GetURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // TBOX(임시문서함), PBOX(발행문서함), WRITE(현금영수증 작성)
    $TOGO = 'WRITE';

    try {
        $url = $this->PopbillCashbill->GetURL($testCorpNum, $testUserID, $TOGO);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "현금영수증 문서함 팝업 URL" , 'value' => $url]);

  }

  /**
   * 1건의 현금영수증 보기 팝업 URL을 반환합니다.
   * - 보안정책으로 인해 반환된 URL의 유효시간은 30초입니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPopUpURL
   */
  public function GetPopUpURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    try {
        $url = $this->PopbillCashbill->GetPopUpURL($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "현금영수증 보기 URL" , 'value' => $url]);
  }

  /**
   * 1건의 현금영수증 인쇄팝업 URL을 반환합니다.
   * - 보안정책으로 인해 반환된 URL의 유효시간은 30초입니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPrintURL
   */
  public function GetPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    try {
        $url = $this->PopbillCashbill->GetPrintURL($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "현금영수증 인쇄 URL" , 'value' => $url]);
  }

  /**
   * 대량의 현금영수증 인쇄팝업 URL을 반환합니다. (최대 100건)
   * - 보안정책으로 인해 반환된 URL의 유효시간은 30초입니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetMassPrintURL
   */
  public function GetMassPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호 배열, 최대 100건
    $mgtKeyList = array (
        '20190101-001',
        '20190101-002',
        '20190214-001',
        '20190214-005',
    );

    try {
        $url = $this->PopbillCashbill->GetMassPrintURL($testCorpNum, $mgtKeyList);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "현금영수증 인쇄 (대량) URL" , 'value' => $url]);
  }

  /**
   * 공급받는자 메일링크 URL을 반환합니다.
   * - 메일링크 URL은 유효시간이 존재하지 않습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetMailURL
   */
  public function GetMailURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    try {
        $url = $this->PopbillCashbill->GetMailURL($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "공급받는자 메일링크 URL" , 'value' => $url]);
  }

  /**
   * 팝빌에 로그인 상태로 접근할 수 있는 팝업 URL을 반환합니다.
   * - 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetAccessURL
   */
  public function GetAccessURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
      $url = $this->PopbillCashbill->GetAccessURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);
  }

  /**
   * 현금영수증 발행 안내메일을 재전송합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#SendEmail
   */
  public function SendEmail(){

    // 팝빌 회원 사업자번호, "-" 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    // 수신메일 주소
    $receiver = 'test@test.com';

    try {
        $result = $this->PopbillCashbill->SendEmail($testCorpNum, $mgtKey, $receiver);
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
   * 알림문자를 전송합니다. (단문/SMS- 한글 최대 45자)
   * - 알림문자 전송시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - 전송내역 확인은 "팝빌 로그인" > [문자 팩스] > [문자] > [전송내역] 탭에서 전송결과를 확인할 수 있습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#SendSMS
   */
  public function SendSMS(){

    // 팝빌 회원 사업자번호, "-" 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    // 발신번호
    $sender = '07043042991';

    // 수신자번호
    $receiver = '010111222';

    // 메시지 내용, 90byte 초과시 길이가 조정되어 전송됨.
    $contents = '메시지 전송 내용입니다. 메세지의 길이가 90Byte를 초과하는 경우에는 메시지의 길이가 조정되어 전송되오니 참고하여 테스트하시기 바랍니다, 링크허브 문자 API 테스트 메시지 ';

    try {
        $result = $this->PopbillCashbill->SendSMS($testCorpNum, $mgtKey, $sender, $receiver, $contents);
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
   * 현금영수증을 팩스전송합니다.
   * - 팩스 전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - 전송내역 확인은 "팝빌 로그인" > [문자 팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인할 수 있습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#SendFAX
   */
  public function SendFAX(){

    // 팝빌 회원 사업자번호, "-" 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    // 발신번호
    $sender = '07043042991';

    // 수신팩스번호
    $receiver = '070111222';

    try {
        $result = $this->PopbillCashbill->SendFAX($testCorpNum, $mgtKey, $sender, $receiver);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }
    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  public function AssignMgtKey(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 현금영수증 아이템키
    $itemKey = '020061910184000001';

    // 부여할 파트너 문서번호
    $mgtKey = '20200709-002';

    try {
        $result = $this->PopbillCashbill->AssignMgtKey($testCorpNum, $itemKey, $mgtKey);
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
   * 현금영수증 관련 메일전송 항목에 대한 전송여부를 목록을 반환한다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#ListEmailConfig
   */
  public function ListEmailConfig(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillCashbill->ListEmailConfig($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Cashbill/ListEmailConfig', ['Result' => $result] );

  }

  /**
   * 현금영수증 관련 메일전송 항목에 대한 전송여부를 수정합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#UpdateEmailConfig
   *
   * 메일전송유형
   * CSH_ISSUE : 고객에게 현금영수증이 발행 되었음을 알려주는 메일 입니다.
   * CSH_CANCEL : 고객에게 현금영수증이 발행취소 되었음을 알려주는 메일 입니다.
   */
  public function UpdateEmailConfig(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 메일 전송 유형
    $emailType = 'CSH_ISSUE';

    // 전송 여부 (True = 전송, False = 미전송)
    $sendYN = True;

    try {
        $result = $this->PopbillCashbill->UpdateEmailConfig($testCorpNum, $emailType, $sendYN);
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
   * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API)를 통해 확인하시기 바랍니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetBalance
   */
  public function GetBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillCashbill->GetBalance($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "연동회원 잔여포인트" , 'value' => $remainPoint]);

  }

  /**
   * 팝빌 연동회원의 포인트충전 팝업 URL을 반환합니다.
   * - 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetChargeURL
   */
  public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
      $url = $this->PopbillCashbill->GetChargeURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "연동회원 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * 파트너의 잔여포인트를 확인합니다.
   * - 과금방식이 연동과금인 경우 연동회원 잔여포인트(GetBalance API)를 이용하시기 바랍니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPartnerBalance
   */
  public function GetPartnerBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillCashbill->GetPartnerBalance($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "파트너 잔여포인트" , 'value' => $remainPoint]);
  }

  /**
   * 파트너 포인트 충전 팝업 URL을 반환합니다.
   * - 반환된 URL은 보안정책에 따라 30초의 유효시간을 갖습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPartnerURL
   */
  public function GetPartnerURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // [CHRG] : 포인트충전 URL
    $TOGO = 'CHRG';

    try {
        $url = $this->PopbillCashbill->GetPartnerURL($testCorpNum, $TOGO);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * 현금영수증 발행단가를 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetUnitCost
   */
  public function GetUnitCost(){

    // 팝빌 회원 사업자 번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    try {
        $unitCost = $this->PopbillCashbill->GetUnitCost($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "현금영수증 발행단가" , 'value' => $unitCost]);
  }

  /**
   * 현금영수증 API 서비스 과금정보를 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetChargeInfo
   */
  public function GetChargeInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    try {
        $result = $this->PopbillCashbill->GetChargeInfo($testCorpNum, $testUserID);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetChargeInfo', ['Result' => $result]);
  }


  /**
   * 해당 사업자의 파트너 연동회원 가입여부를 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#CheckIsMember
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillCashbill->CheckIsMember($testCorpNum, $LinkID);
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
   * 팝빌 회원아이디 중복여부를 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#CheckID
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillCashbill->CheckID($testUserID);
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
   * 파트너의 연동회원으로 회원가입을 요청합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#JoinMember
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
    $joinForm->Addr	= '테스트사업자주소';

    // 업태
    $joinForm->BizType = '업태';

    // 종목
    $joinForm->BizClass	= '종목';

    // 담당자명
    $joinForm->ContactName = '담당자상명';

    // 담당자 이메일
    $joinForm->ContactEmail	= 'tester@test.com';

    // 담당자 연락처
    $joinForm->ContactTEL	= '07043042991';

    // 아이디, 6자 이상 20자미만
    $joinForm->ID = 'userid_phpdd';

    // 비밀번호, 6자 이상 20자미만
    $joinForm->PWD = 'thisispassword';

    try	{
      $result = $this->PopbillCashbill->JoinMember($joinForm);
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
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetCorpInfo
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillCashbill->GetCorpInfo($testCorpNum);
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
   * - https://docs.popbill.com/cashbill/phplaravel/api#UpdateCorpInfo
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
        $result =  $this->PopbillCashbill->UpdateCorpInfo($testCorpNum, $CorpInfo);
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
   * 연동회원의 담당자를 신규로 등록합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#RegistContact
   */
  public function RegistContact(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 담당자 정보 객체 생성
    $ContactInfo = new ContactInfo();

    // 담당자 아이디
    $ContactInfo->id = 'testkorea001';

    // 담당자 패스워드
    $ContactInfo->pwd = 'testkorea!@#$/';

    // 담당자명
    $ContactInfo->personName = '담당자_수정';

    // 연락처
    $ContactInfo->tel = '070-4304-2991';

    // 핸드폰번호
    $ContactInfo->hp = '010-1234-1234';

    // 이메일주소
    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $ContactInfo->email = 'test@test.com';

    // 팩스
    $ContactInfo->fax = '070-111-222';

    // 회사조회 여부, false-개인조회, true-회사조회
    $ContactInfo->searchAllAllowYN = true;

    // 관리자여부
    $ContactInfo->mgrYN = false;

    try {
        $result = $this->PopbillCashbill->RegistContact($testCorpNum, $ContactInfo);
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
   * 연동회원의 담당자 목록을 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#ListContact
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillCashbill->ListContact($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ContactInfo', ['ContactList' => $ContactList]);
  }

  /**
   * 연동회원의 담당자 정보를 수정합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#UpdateContact
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
    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $ContactInfo->email = 'test@test.com';

    // 팩스번호
    $ContactInfo->fax = '070-111-222';

    // 전체조회 여부, false-개인조회, true-전체조회
    $ContactInfo->searchAllAllowYN = true;

    try {
        $result = $this->PopbillCashbill->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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
   * 현금영수증 PDF 다운로드 URL을 반환합니다.
   * - 보안정책으로 인해 반환된 URL의 유효시간은 30초입니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPDFURL
   */
  public function GetPDFURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    try {
        $url = $this->PopbillCashbill->GetPDFURL($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "현금영수증 PDF 다운로드 URL" , 'value' => $url]);
  }

  /**
   * 현금영수증 PDF byte array를 파일로 저장합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPDF
   */
  public function GetPDF(){

    // 팝빌 회원 사업자 번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20190101-001';

    // PDF 파일경로, PDF 파일을 저장할 폴더에 777 권한 필요.
    $pdfFilePath = '/Users/John/Desktop/'.$mgtKey.'.pdf';

    try {
        $bytes = $this->PopbillCashbill->GetPDF($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    if(file_put_contents( $pdfFilePath, $bytes )){
      $code = 1;
      $message = $pdfFilePath;
    };

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }
}
