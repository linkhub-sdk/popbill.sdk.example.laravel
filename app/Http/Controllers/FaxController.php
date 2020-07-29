<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillFax;

class FaxController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 팩스 서비스 클래스 초기화
    $this->PopbillFax = new PopbillFax(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillFax->IsTest(config('popbill.IsTest'));

    // 인증토큰의 IP제한기능 사용여부, 권장(true)
    $this->PopbillFax->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

    // 팝빌 API 서비스 고정 IP 사용여부(GA), true-사용, false-미사용, 기본값(false)
    $this->PopbillFax->UseStaticIP(config('popbill.UseStaticIP'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * 팩스 발신번호 관리 팝업 URL을 반합니다.
   * - 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetSenderNumberMgtURL
   */
  public function GetSenderNumberMgtURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillFax->GetSenderNumberMgtURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팩스 발신번호 관리 팝업 URL" , 'value' => $url]);
  }

  /**
   * 팩스 발신번호 목록을 반환합니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetSenderNumberList
   */
  public function GetSenderNumberList(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillFax->GetSenderNumberList($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetSenderNumberList', ['Result' => $result] );

  }

  /**
   * 팩스를 전송합니다. (전송할 파일 개수는 최대 20개까지 가능)
   * - https://docs.popbill.com/fax/phplaravel/api#SendFAX
   */
  public function SendFAX(){

    // 팝빌 회원 사업자번호
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 팩스전송 발신번호
    $Sender = '07043042992';

    // 팩스전송 발신자명
    $SenderName = '발신자명';

    // 팩스 수신정보 배열, 최대 1000건
    $Receivers[] = array(
        // 팩스 수신번호
        'rcv' => '070111222',
        // 수신자명
        'rcvnm' => '팝빌담당자'
    );

    // 팩스전송파일, 해당파일에 읽기 권한이 설정되어 있어야 함. 최대 20개.
    $Files = array('/Users/John/Desktop/tax_image.png');

    // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인경우 즉시전송
    $reserveDT = null;

    // 광고팩스 전송여부
    $adsYN = false;

    // 팩스제목
    $title = '팩스 단건전송 제목';

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    try {
        $receiptNum = $this->PopbillFax->SendFAX($testCorpNum, $Sender, $Receivers, $Files,
            $reserveDT, $testUserID, $SenderName, $adsYN, $title, $requestNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '팩스 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
  * [대량전송] 팩스를 전송합니다. (전송할 파일 개수는 최대 20개까지 가능)
  * - https://docs.popbill.com/fax/phplaravel/api#SendFAX
  */
  public function SendFAX_Multi(){

    // 팝빌 회원 사업자번호
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 팩스전송 발신번호
    $Sender = '07043042991';

    // 팩스전송 발신자명
    $SenderName = '발신자명';

    // 팩스 수신정보 배열, 최대 1000건
    $Receivers[] = array(
        // 팩스 수신번호
        'rcv' => '070111222',
        // 팩스 수신자명
        'rcvnm' => '팝빌담당자'
    );

    $Receivers[] = array(
        // 팩스 수신번호
        'rcv' => '070333444',
        // 팩스 수신자명
        'rcvnm' => '수신담당자'
    );

    // 팩스전송파일, 해당파일에 읽기 권한이 설정되어 있어야 함. 최대 20개.
    $Files = array('/Users/John/Desktop/tax_image.png');

    // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
    $reserveDT = null;

    // 광고팩스 전송여부
    $adsYN = false;

    // 팩스 제목
    $title = '팩스 동보전송 제목';

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    try {
        $receiptNum = $this->PopbillFax->SendFAX($testCorpNum, $Sender, $Receivers, $Files,
            $reserveDT, $testUserID, $SenderName, $adsYN, $title, $requestNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => '팩스 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * 팩스를 재전송합니다.
   * - 접수일로부터 60일이 경과된 경우 재전송할 수 없습니다.
   * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - https://docs.popbill.com/fax/phplaravel/api#ResendFAX
   */
  public function ResendFAX(){

    // 팝빌 회원 사업자번호
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 팩스 접수번호
    $ReceiptNum = '019021417365800001';

    // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
    $Sender = '07043042991';

    // 팩스전송 발신자명, 공백처리시 기존전송정보로 재전송
    $SenderName = '발신자명';

    // 팩스 수신정보 배열, NULL로 처리하는 경우 기존전송정보로 재전송
    $Receivers = NULL;

    /*
    // 팩스 수신정보가 기존전송정보와 다를경우 아래의 코드 참조
      $Receivers[] = array(
      // 팩스 수신번호
          'rcv' => '070111222',
      // 수신자명
          'rcvnm' => '팝빌담당자'
      );
    */

    // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인경우 즉시전송
    $reserveDT = null;

    // 팩스 제목
    $title = '팩스 재전송 제목';

    // 재전송 팩스의 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    // 재전송 팩스의 전송상태확인(GetSendDetailRN) / 예약전송취소(CancelReserveRN) 에 이용됩니다.
    $requestNum = '';

    try {
        $receiptNum = $this->PopbillFax->ResendFAX($testCorpNum, $ReceiptNum, $Sender,
            $SenderName, $Receivers, $reserveDT, $testUserID, $title, $requestNum);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * 전송요청번호(requestNum)을 할당한 팩스를 재전송합니다.
   * - 접수일로부터 60일이 경과된 경우 재전송할 수 없습니다.
   * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - https://docs.popbill.com/fax/phplaravel/api#ResendFAXRN
   */
  public function ResendFAXRN(){

    // 팝빌 회원 사업자번호
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
    $Sender = '07043042991';

    // 팩스전송 발신자명, 공백처리시 기존전송정보로 재전송
    $SenderName = '발신자명';

    // 팩스 수신정보 배열, NULL로 처리하는 경우 기존전송정보로 재전송
    $Receivers = NULL;

    /*
    // 팩스 수신정보가 기존전송정보와 다를경우 아래의 코드 참조
      $Receivers[] = array(
      // 팩스 수신번호
          'rcv' => '070111222',
      // 수신자명
          'rcvnm' => '팝빌담당자'
      );
    */

    // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인경우 즉시전송
    $reserveDT = null;

    // 팩스 제목
    $title = '팩스 재전송 제목';

    // 원본 팩스 전송시 할당한 전송요청번호(requestNum)
    $originalFAXrequestNum = '';

    // 재전송 팩스의 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    // 재전송 팩스의 전송상태확인(GetSendDetailRN) / 예약전송취소(CancelReserveRN) 에 이용됩니다.
    $requestNum = '';

    try {
        $receiptNum = $this->PopbillFax->ResendFAXRN($testCorpNum, $requestNum, $Sender,
            $SenderName, $Receivers, $originalFAXrequestNum, $reserveDT, $testUserID, $title);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);

  }

  /**
   * [대량전송] 전송요청번호(requestNum)을 할당한 팩스를 재전송합니다.
   * - 접수일로부터 60일이 경과된 경우 재전송할 수 없습니다.
   * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - https://docs.popbill.com/fax/phplaravel/api#ResendFAX
   */
  public function ResendFAX_Multi(){

    // 팝빌 회원 사업자번호
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
    $Sender = '07043042991';

    // 팩스전송 발신자명, 공백처리시 기존전송정보로 재전송
    $SenderName = '발신자명';

    // 팩스 수신정보, NULL로 처리하는 경우 기존전송정보로 재전송
    //$Receivers = NULL;

    // 팩스 수신정보가 기존전송정보와 다르게 동보전송하는 경우 아래의 코드 참조
    $Receivers[] = array(
        // 팩스 수신번호
        'rcv' => '070111222',
        // 팩스 수신자명
        'rcvnm' => '팝빌담당자'
    );
    $Receivers[] = array(
        // 팩스 수신번호
        'rcv' => '070333444',
        // 팩스 수신자명
        'rcvnm' => '수신담당자'
    );

    // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
    $reserveDT = null;

    // 팩스 제목
    $title = '팩스 재전송 제목';

    // 원본 팩스 전송시 할당한 전송요청번호(requestNum)
    $originalFAXrequestNum = '';

    // 재전송 팩스의 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    // 재전송 팩스의 전송상태확인(GetSendDetailRN) / 예약전송취소(CancelReserveRN) 에 이용됩니다.
    $requestNum = '';

    try {
        $receiptNum = $this->PopbillFax->ResendFAXRN($testCorpNum, $requestNum, $Sender,
            $SenderName, $Receivers, $originalFAXrequestNum, $reserveDT, $testUserID, $title, $requestNum);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);

  }

  /**
   * [대량전송] 전송요청번호(requestNum)을 할당한 팩스를 재전송합니다.
   * - 접수일로부터 60일이 경과된 경우 재전송할 수 없습니다.
   * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
   * - https://docs.popbill.com/fax/phplaravel/api#ResendFAXRN
   */
  public function ResendFAXRN_Multi(){

    // 팝빌 회원 사업자번호
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
    $Sender = '07043042991';

    // 팩스전송 발신자명, 공백처리시 기존전송정보로 재전송
    $SenderName = '발신자명';

    // 팩스 수신정보, NULL로 처리하는 경우 기존전송정보로 재전송
    //$Receivers = NULL;

    // 팩스 수신정보가 기존전송정보와 다르게 동보전송하는 경우 아래의 코드 참조
    $Receivers[] = array(
        // 팩스 수신번호
        'rcv' => '070111222',
        // 팩스 수신자명
        'rcvnm' => '팝빌담당자'
    );
    $Receivers[] = array(
        // 팩스 수신번호
        'rcv' => '070333444',
        // 팩스 수신자명
        'rcvnm' => '수신담당자'
    );

    // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
    $reserveDT = null;

    // 팩스 제목
    $title = '팩스 재전송 제목';

    // 원본 팩스 전송시 할당한 전송요청번호(requestNum)
    $originalFAXrequestNum = '';

    // 재전송 팩스의 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    // 재전송 팩스의 전송상태확인(GetSendDetailRN) / 예약전송취소(CancelReserveRN) 에 이용됩니다.
    $requestNum = '';

    try {
        $receiptNum = $this->PopbillFax->ResendFAXRN($testCorpNum, $requestNum, $Sender,
            $SenderName, $Receivers, $originalFAXrequestNum, $reserveDT, $testUserID, $title, $requestNum);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * 팩스전송요청시 발급받은 접수번호(receiptNum)로 팩스 예약전송건을 취소합니다.
   * - 예약전송 취소는 예약전송시간 10분전까지 가능하며, 팩스변환 이후 가능합니다.
   * - https://docs.popbill.com/fax/phplaravel/api#CancelReserve
   */
  public function CancelReserve(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팩스예약전송 접수번호
    $ReceiptNum = '018062617574300001';

    try {
        $result = $this->PopbillFax->CancelReserve($testCorpNum ,$ReceiptNum);
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
   * 팩스전송요청시 할당한 전송요청번호(requestNum)로 팩스 예약전송건을 취소합니다.
   * - 예약전송 취소는 예약전송시간 10분전까지 가능하며, 팩스변환 이후 가능합니다.
   * - https://docs.popbill.com/fax/phplaravel/api#CancelReserveRN
   */
  public function CancelReserveRN(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 예약팩스전송 요청시 할당한 전송요청번호
    $requestNum = '20190214-01';

    try {
        $result = $this->PopbillFax->CancelReserveRN($testCorpNum ,$requestNum);
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
   * 팩스전송요청시 발급받은 접수번호(receiptNum)로 전송결과를 확인합니다
   * - https://docs.popbill.com/fax/phplaravel/api#GetFaxDetail
   */
  public function GetFaxDetail(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팩스전송 접수번호
    $ReceiptNum = '018092923330400001';

    try {
        $result = $this->PopbillFax->GetFaxDetail($testCorpNum, $ReceiptNum);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Fax/GetFaxDetail', ['Result' => $result] );
  }

  /**
   * 팩스전송요청시 할당한 전송요청번호(requestNum)으로 전송결과를 확인합니다
   * - https://docs.popbill.com/fax/phplaravel/api#GetFaxDetailRN
   */
  public function GetFaxDetailRN(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팩스전송 요청시 할당한 전송요청번호
    $requestNum = '20180929-001';

    try {
        $result = $this->PopbillFax->GetFaxDetailRN($testCorpNum, $requestNum);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Fax/GetFaxDetail', ['Result' => $result] );
  }

  /**
   * 검색조건을 사용하여 팩스전송 내역을 조회합니다.
   * - 최대 검색기간 : 6개월 이내
   * - https://docs.popbill.com/fax/phplaravel/api#Search
   */
  public function Search(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 검색시작일자
    $SDate = '20200101';

    // 검색종료일자
    $EDate = '20200131';

    // 전송상태값 배열, 1-대기, 2-성공, 3-실패, 4-취소
    $State = array(1, 2, 3, 4);

    // 예약전송 조회여부, true(예약전송건 검색)
    $ReserveYN = false;

    // 개인조회여부, true(개인조회), false(회사조회)
    $SenderOnly = false;

    // 페이지 번호, 기본값 1
    $Page = 1;

    // 페이지당 검색갯수, 기본값 500, 최대값 1000
    $PerPage = 500;

    // 정렬방향, D-내림차순, A-오름차순
    $Order = 'D';

    // 조회 검색어.
    // 팩스 전송시 입력한 발신자명 또는 수신자명 기재.
    // 조회 검색어를 포함한 발신자명 또는 수신자명을 검색합니다.
    $QString = '';

    try {
        $result = $this->PopbillFax->Search($testCorpNum, $SDate, $EDate, $State, $ReserveYN, $SenderOnly, $Page, $PerPage, $Order, '', $QString);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('Fax/Search', ['Result' => $result] );
  }

  /**
   * 팩스 전송내역 팝업 URL을 반환합니다.
   * - 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetSentListURL
   */
  public function GetSentListURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillFax->GetSentListURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팩스 전송내역 팝업 URL" , 'value' => $url]);
  }

  /**
   * 접수한 팩스 전송건에 대한 미리보기 팝업 URL을 반환합니다.
   * - 반환된 URL은 보안정책에 따라 30초의 유효시간을 갖습니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetPreviewURL
   */
  public function GetPreviewURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팩스전송 접수번호
    $ReceiptNum = '019021418123700001';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillFax->GetPreviewURL($testCorpNum,$ReceiptNum,$testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "팩스 미리보기 팝업 URL" , 'value' => $url]);
  }

  /**
   * 연동회원의 잔여포인트를 확인합니다.
   * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API)를 통해 확인하시기 바랍니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetBalance
   */
  public function GetBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillFax->GetBalance($testCorpNum);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "연동회원 잔여포인트" , 'value' => $remainPoint]);
  }

  /**
   * 팝빌 연동회원의 포인트충전 팝업 URL을 반환합니다.
   * 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetChargeURL
   */
  public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillFax->GetChargeURL($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/fax/phplaravel/api#GetPartnerBalance
   */
  public function GetPartnerBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillFax->GetPartnerBalance($testCorpNum);
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
   * - https://docs.popbill.com/fax/phplaravel/api#GetPartnerURL
   */
  public function GetPartnerURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // [CHRG] : 포인트충전 URL
    $TOGO = 'CHRG';

    try {
        $url = $this->PopbillFax->GetPartnerURL($testCorpNum, $TOGO);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * 팩스 전송단가를 확인합니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetUnitCost
   */
  public function GetUnitCost(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $unitCost= $this->PopbillFax->GetUnitCost($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "팩스 전송단가 " , 'value' => $unitCost]);
  }

  /**
   * 연동회원의 팩스 API 서비스 과금정보를 확인합니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetChargeInfo
   */
  public function GetChargeInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    try {
        $result = $this->PopbillFax->GetChargeInfo($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/fax/phplaravel/api#CheckIsMember
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    // ./config/popbill.php에 선언된 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillFax->CheckIsMember($testCorpNum, $LinkID);
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
   * - https://docs.popbill.com/fax/phplaravel/api#CheckID
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillFax->CheckID($testUserID);
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
   * 팝빌에 로그인 상태로 접근할 수 있는 팝업 URL을 반환합니다.
   * - 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   * - https://docs.popbill.com/fax/phplaravel/api#GetAccessURL
   */
  public function GetAccessURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillFax->GetAccessURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);

  }

  /**
   * 파트너의 연동회원으로 회원가입을 요청합니다.
   * - https://docs.popbill.com/fax/phplaravel/api#JoinMember
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
      $result = $this->PopbillFax->JoinMember($joinForm);
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
   * - https://docs.popbill.com/fax/phplaravel/api#GetCorpInfo
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillFax->GetCorpInfo($testCorpNum);
    }
    catch(PopbillException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
  }

  /**
   * 연동회원의 회사정보를 수정합니다.
   * - https://docs.popbill.com/fax/phplaravel/api#UpdateCorpInfo
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
        $result =  $this->PopbillFax->UpdateCorpInfo($testCorpNum, $CorpInfo);
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
   * - https://docs.popbill.com/fax/phplaravel/api#RegistContact
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
    $ContactInfo->email = 'test@test.com';

    // 팩스
    $ContactInfo->fax = '070-111-222';

    // 회사조회 여부, false-개인조회, true-회사조회
    $ContactInfo->searchAllAllowYN = true;

    // 관리자여부
    $ContactInfo->mgrYN = false;

    try {
        $result = $this->PopbillFax->RegistContact($testCorpNum, $ContactInfo);
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
   * - https://docs.popbill.com/fax/phplaravel/api#ListContact
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillFax->ListContact($testCorpNum);
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
   * - https://docs.popbill.com/fax/phplaravel/api#UpdateContact
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

    // 전체조회 여부, false-개인조회, true-전체조회
    $ContactInfo->searchAllAllowYN = true;

    try {
        $result = $this->PopbillFax->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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
