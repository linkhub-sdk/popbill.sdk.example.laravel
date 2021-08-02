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
   * 파트너가 현금영수증 관리 목적으로 할당하는 문서번호 사용여부를 확인합니다.
   * - 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
   * - https://docs.popbill.com/cashbill/phplaravel/api#CheckMgtKeyInUse
   */
  public function CheckMgtKeyInUse(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210101-001';

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
   * 현금영수증 데이터를 팝빌에 전송하여 발행합니다.
   * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
   * - https://docs.popbill.com/cashbill/phplaravel/api#RegistIssue
   */
  public function RegistIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20211024-001';

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

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210214-002';

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
    $mgtKey = '20210214-002';

    // 현금영수증 객체 생성
    $Cashbill = new Cashbill();

    // [필수] 현금영수증 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
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
    $mgtKey = '20210214-002';

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
   * 국세청 전송 이전 "발행완료" 상태의 현금영수증을 "발행취소"하고 국세청 전송 대상에서 제외됩니다.
   * - 발행취소는 국세청 전송전에만 가능합니다.
   * - 발행취소된 형금영수증은 국세청에 전송되지 않습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#CancelIssue
   */
  public function CancelIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210214-002';

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
   * 삭제 가능한 상태의 현금영수증을 삭제합니다.
   * - 삭제 가능한 상태: "임시저장", "발행취소", "전송실패"
   * - 현금영수증을 삭제하면 사용된 문서번호(mgtKey)를 재사용할 수 있습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#Delete
   */
  public function Delete(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210214-002';

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
   * 취소 현금영수증을 발행하며 취소 현금영수증의 금액은 원본 금액을 넘을 수 없습니다.
   * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
   * - https://docs.popbill.com/cashbill/phplaravel/api#RevokeRegistIssue
   */
  public function RevokeRegistIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210214-005';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 작성형식(yyyyMMdd) 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20210211';

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
    $mgtKey = '20210214-006';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20210211';

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

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210214-007';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20210211';

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

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210214-009';

    // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgConfirmNum = '171312673';

    // 원본현금영수증 거래일자, 문서정보 확인(GetInfo API)을 통해 확인가능.
    $orgTradeDate = '20210211';

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
   * 현금영수증 1건의 상태 및 요약정보를 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetInfo
   */
  public function GetInfo(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210214-005';

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
   * 다수건의 현금영수증 상태 및 요약 정보를 확인합니다. (1회 호출 시 최대 1,000건 확인 가능)
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetInfos
   */
  public function GetInfos(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    // 문서번호 배열, 최대 1000건
    $MgtKeyList = array(
        '20210214-001',
        '20210214-005',
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
   * 현금영수증 1건의 상세정보를 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetDetailInfo
   */
  public function GetDetailInfo(){
    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210214-005';

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
   * 검색조건에 해당하는 현금영수증을 조회합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#Search
   */
  public function Search(){

    // [필수] 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    // [필수] 조회일자 유형, R-등록일자, T-거래일자, I-발행일자
    $DType = 'R';

    // [필수] 시작일자
    $SDate = '20210101';

    // [필수] 종료일자
    $EDate = '20210228';

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
   * 현금영수증의 상태에 대한 변경이력을 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetLogs
   */
  public function GetLogs(){

    // 팝빌회원, 사업자번호
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210101-001';

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
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
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
   * 팝빌 사이트와 동일한 현금영수증 1건의 상세 정보 페이지의 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPopUpURL
   */
  public function GetPopUpURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210101-001';

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
   * 팝빌 사이트와 동일한 현금영수증 1건의 상세 정보 페이지(사이트 상단, 좌측 메뉴 및 버튼 제외)의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetViewURL
   */
  public function GetViewURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210722-01';

    try {
        $url = $this->PopbillCashbill->GetViewURL($testCorpNum, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "현금영수증 보기 URL (메뉴/버튼 제외)" , 'value' => $url]);
  }

  /**
   * 현금영수증 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPrintURL
   */
  public function GetPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210101-001';

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
   * 다수건의 현금영수증을 인쇄하기 위한 페이지의 팝업 URL을 반환합니다. (최대 100건)
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetMassPrintURL
   */
  public function GetMassPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호 배열, 최대 100건
    $mgtKeyList = array (
        '20210101-001',
        '20210101-002',
        '20210214-001',
        '20210214-005',
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
   * 구매자가 수신하는 현금영수증 안내 메일의 하단에 버튼 URL 주소를 반환합니다.
   * - 함수 호출로 반환 받은 URL에는 유효시간이 없습니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetMailURL
   */
  public function GetMailURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210101-001';

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
   * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
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
   * 현금영수증과 관련된 안내 메일을 재전송 합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#SendEmail
   */
  public function SendEmail(){

    // 팝빌 회원 사업자번호, "-" 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210101-001';

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
   * 현금영수증과 관련된 안내 SMS(단문) 문자를 재전송하는 함수로, 팝빌 사이트 [문자·팩스] > [문자] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
   * - 알림문자 전송시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - https://docs.popbill.com/cashbill/phplaravel/api#SendSMS
   */
  public function SendSMS(){

    // 팝빌 회원 사업자번호, "-" 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210101-001';

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
   * 현금영수증을 팩스로 전송하는 함수로, 팝빌 사이트 [문자·팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
   * - 팩스 전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - https://docs.popbill.com/cashbill/phplaravel/api#SendFAX
   */
  public function SendFAX(){

    // 팝빌 회원 사업자번호, "-" 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210101-001';

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
    $mgtKey = '20210709-002';

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
   * 현금영수증 관련 메일 항목에 대한 발송설정을 확인합니다.
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
   * 현금영수증 관련 메일 항목에 대한 발송설정을 수정합니다.
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
   * 연동회원 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
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
   * 연동회원 포인트 결제내역 확인을 위한 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetPaymentURL
   */
  public function GetPaymentURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillCashbill->GetPaymentURL($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetUseHistoryURL
   */
  public function GetUseHistoryURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillCashbill->GetUseHistoryURL($testCorpNum, $testUserID);
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
   * 파트너 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
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
   * 현금영수증 발행시 과금되는 포인트 단가를 확인합니다.
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
   * 팝빌 현금영수증 API 서비스 과금정보를 확인합니다.
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
   *  사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
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
   * 사용하고자 하는 아이디의 중복여부를 확인합니다.
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
   * 사용자를 연동회원으로 가입처리합니다.
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

    // 비밀번호, 8자 이상 20자 이하(영문, 숫자, 특수문자 조합)
    $joinForm->Password = 'asdf1234!@';

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
   * 연동회원 사업자번호에 담당자(팝빌 로그인 계정)를 추가합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#RegistContact
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
    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $ContactInfo->email = 'test@test.com';

    // 팩스
    $ContactInfo->fax = '070-111-222';

    // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
    $ContactInfo->searchRole = 3;

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
   * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보을 확인합니다.
   * - https://docs.popbill.com/cashbill/phplaravel/api#GetContactInfo
   */
  public function GetContactInfo(){
    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    //확인할 담당자 아이디
    $contactID = 'checkContact';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    try {
        $ContactInfo = $this->PopbillCashbill->GetContactInfo($testCorpNum, $contactID, $testUserID);
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

    return view('ListContact', ['ContactList' => $ContactList]);
  }

  /**
   * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
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

    // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
    $ContactInfo->searchRole = 3;

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
    $mgtKey = '20210101-001';

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

}
