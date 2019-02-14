<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;

use Linkhub\Popbill\PopbillKakao;
use Linkhub\Popbill\ENumKakaoType;

class KakaoTalkController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 카카오톡 서비스 클래스 초기화
    $this->PopbillKakao = new PopbillKakao(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillKakao->IsTest(config('popbill.IsTest'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * 발신번호 관리 팝업 URL을 반합니다.
   * 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   */
  public function GetPlusFriendMgtURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillKakao->GetPlusFriendMgtURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "카카오톡 발신번호 관리 팝업 URL" , 'value' => $url]);

  }

  /**
   * 팝빌에 등록된 플러스친구 계정목록을 확인합니다.
   */
  public function ListPlusFriendID(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '123456780';

    try {
        $result = $this->PopbillKakao->ListPlusFriendID($testCorpNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('KakaoTalk/ListPlusfriendID', ['Result' => $result] );
  }

  /**
   * 발신번호 관리 팝업 URL을 반합니다.
   * 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   */
  public function GetSenderNumberMgtURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillKakao->GetSenderNumberMgtURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "발신번호 관리 팝업 URL" , 'value' => $url]);

  }

  /**
   * 팝빌에 등록된 발신번호 목록을 확인합니다.
   */
  public function GetSenderNumberList(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillKakao->GetSenderNumberList($testCorpNum);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetSenderNumberList', ['Result' => $result] );

  }

  /**
   * 알림톡 템플릿관리 팝업 URL을 반환합니다.
   * 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   */
  public function GetATSTemplateMgtURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillKakao->GetATSTemplateMgtURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "알림톡 템플릿 관리 팝업 URL" , 'value' => $url]);

  }

  /**
  * (주)카카오로 부터 승인된 알림톡 템플릿 목록을 확인합니다.
  * - 반환항목중 템플릿코드(templateCode)는 알림톡 전송시 사용됩니다.
  */
  public function ListATSTemplate(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillKakao->ListATSTemplate($testCorpNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('KakaoTalk/ListATSTemplate', ['Result' => $result] );

  }

  /**
   * 알림톡 전송을 요청합니다.
   * 사전에 승인된 템플릿의 내용과 알림톡 전송내용(content)이 다를 경우 전송실패 처리됩니다.
   */
  public function SendATS_one(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 템플릿 코드 - 템플릿 목록 조회 (ListATSTemplate API)의 반환항목 확인
    $templateCode = '018110000047';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 알림톡 내용, 최대 1000자
    $content = '테스트 템플릿입니다.';

    // 대체문자 내용
    $altContent = '대체문자 내용';

    // 대체문자 전송유형 공백-미전송, A-대체문자내용 전송, C-알림톡내용 전송
    $altSendType = 'A';

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = '';

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신자 정보
    $receivers[] = array(
      // 수신번호
      'rcv' => '010111222',
      // 수신자명
      'rcvnm' => '수신자명'
    );

    try {
        $receiptNum = $this->PopbillKakao->SendATS($testCorpNum, $templateCode, $sender, $content, $altContent, $altSendType, $receivers, $reserveDT, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '알림톡 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);

  }

  /**
   * [동보전송] 알림톡 전송을 요청합니다.
   * 사전에 승인된 템플릿의 내용과 알림톡 전송내용(content)이 다를 경우 전송실패 처리됩니다.
   */
  public function SendATS_same(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 템플릿 코드 - 템플릿 목록 조회 (ListATSTemplate API)의 반환항목 확인
    $templateCode = '019020000025';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 알림톡 내용, 최대 1000자
    $content = '[테스트] 테스트 템플릿입니다.';

    // 대체문자 내용
    $altContent = '대체문자 내용';

    // 대체문자 전송유형 공백-미전송, A-대체문자내용 전송, C-알림톡내용 전송
    $altSendType = 'A';

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신정보 배열, 최대 1000건
    for ($i = 0; $i < 10; $i++){
      $receivers[] = array(
        // 수신번호
        'rcv' => '010111222',
        // 수신자명
        'rcvnm' => '수신자명'
      );
    }

    try {
        $receiptNum = $this->PopbillKakao->SendATS($testCorpNum, $templateCode, $sender, $content, $altContent, $altSendType, $receivers, $reserveDT, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => '알림톡 동보전송 접수번호(receiptNum)', 'value' => $receiptNum]);

  }

  /**
   * [대량전송] 알림톡 전송을 요청합니다.
   * 사전에 승인된 템플릿의 내용과 알림톡 전송내용(content)이 다를 경우 전송실패 처리됩니다.
   */
  public function SendATS_multi(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 템플릿 코드 - 템플릿 목록 조회 (ListATSTemplate API)의 반환항목 확인
    $templateCode = '019020000025';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 대체문자 전송유형 공백-미전송, A-대체문자내용 전송, C-알림톡내용 전송
    $altSendType = 'A';

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 개별정보 배열, 최대 1000건
    for($i = 0; $i < 10; $i++){
        $receivers[] = array(
            // 수신번호
            'rcv' => '010111222',
            // 수신자명
            'rcvnm' => '수신자명',
            // 알림톡 내용, 최대 1000자
            'msg' => '[테스트] 테스트 템플릿입니다.',
            // 대체문자 내용
            'altmsg' => '대체문자 내용'.$i,
        );
    }

    try {
        $receiptNum = $this->PopbillKakao->SendATS($testCorpNum, $templateCode, $sender, '', '', $altSendType, $receivers, $reserveDT, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '알림톡 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * 친구톡(텍스트) 전송을 요청합니다.
   * - 친구톡은 심야 전송(20:00~08:00)이 제한됩니다.
   */
  public function SendFTS_one(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 팝빌에 등록된 플러스친구 아이디, ListPlusFriend API - plusFriendID 확인
    $plusFriendID = '@팝빌';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 친구톡 내용, 최대 1000자
    $content = '친구톡 내용';

    // 대체문자 내용
    $altContent = '대체문자 내용';

    // 대체문자 유형, 공백-미전송, A-대체문자내용 전송, C-친구톡내용 전송
    $altSendType = 'C';

    // 광고전송여부
    $adsYN = False;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신자 정보
    $receivers[] = array(
        // 수신번호
        'rcv' => '010111222',
        // 수신자명
        'rcvnm' => '수신자명'
    );

    // 버튼배열, 최대 5개
    $buttons[] = array(
        // 버튼 표시명
        'n' => '웹링크',
        // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
        't' => 'WL',
        // [앱링크] Android, [웹링크] Mobile
        'u1' => 'http://www.popbill.com',
        // [앱링크] IOS, [웹링크] PC URL
        'u2' => 'http://www.popbill.com',
    );

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    try {
        $receiptNum = $this->PopbillKakao->SendFTS($testCorpNum, $plusFriendID, $sender, $content, $altContent, $altSendType, $adsYN, $receivers, $buttons, $reserveDT, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => '친구톡 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * [동보전송] 친구톡(텍스트) 전송을 요청합니다.
   * - 친구톡은 심야 전송(20:00~08:00)이 제한됩니다.
   */
  public function SendFTS_same(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 팝빌에 등록된 플러스친구 아이디, ListPlusFriend API - plusFriendID 확인
    $plusFriendID = '@팝빌';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 친구톡 내용, 최대 1000자
    $content = '친구톡 동일내용 대량전송';

    // 대체문자 내용
    $altContent = '대체문자 내용';

    // 친구톡 전송 실패시 대체문자 유형, 공백-미전송, A-대체문자내용 전송, C-친구톡내용 전송
    $altSendType = 'A';

    // 광고전송여부
    $adsYN = False;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신정보 배열, 최대 1000건
    for($i=0; $i<10; $i++){
      $receivers[] = array(
        // 수신번호
        'rcv' => '010111222',
        // 수신자명
        'rcvnm' => '수신자명'
      );
    }

    // 버튼배열, 최대 5개
    $buttons[] = array(
        // 버튼 표시명
        'n' => '웹링크',
        // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
        't' => 'WL',
        // [앱링크] Android, [웹링크] Mobile
        'u1' => 'http://www.popbill.com',
        // [앱링크] IOS, [웹링크] PC URL
        'u2' => 'http://www.popbill.com',
    );

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    try {
        $receiptNum = $this->PopbillKakao->SendFTS($testCorpNum, $plusFriendID, $sender, $content, $altContent, $altSendType, $adsYN, $receivers, $buttons, $reserveDT, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => '친구톡 동보전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * [대량전송] 친구톡(텍스트) 전송을 요청합니다.
   * - 친구톡은 심야 전송(20:00~08:00)이 제한됩니다.
   */
  public function SendFTS_multi(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 팝빌에 등록된 플러스친구 아이디, ListPlusFriend API - plusFriendID 확인
    $plusFriendID = '@팝빌';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 친구톡 전송 실패시 대체문자 유형, 공백-미전송, A-대체문자내용 전송, C-친구톡내용 전송
    $altSendType = 'A';

    // 광고전송여부
    $adsYN = False;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신정보 배열, 최대 1000건
    for($i=0; $i<10; $i++){
      $receivers[] = array(
        // 수신번호
        'rcv' => '010111222',
        // 수신자명
        'rcvnm' => '수신자명',
        // 친구톡 내용, 최대 1000자
        'msg' => '친구톡 메시지 내용'.$i,
        // 대체문자
        'altmsg' => '대체문자 내용'.$i,
      );
    }

    // 버튼배열, 최대 5개
    $buttons[] = array(
      // 버튼 표시명
      'n' => '웹링크',
      // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
      't' => 'WL',
      // [앱링크] Android, [웹링크] Mobile
      'u1' => 'http://www.popbill.com',
      // [앱링크] IOS, [웹링크] PC URL
      'u2' => 'http://www.popbill.com',
    );

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    try {
        $receiptNum = $this->PopbillKakao->SendFTS($testCorpNum, $plusFriendID, $sender, '', '', $altSendType, $adsYN, $receivers, $buttons, $reserveDT, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => '친구톡 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * 친구톡(이미지) 전송을 요청합니다.
   * - 친구톡은 심야 전송(20:00~08:00)이 제한됩니다.
   * - 이미지 전송규격 / jpg 포맷, 용량 최대 500KByte, 이미지 높이/너비 비율 1.333 이하, 1/2 이상
   */
  public function SendFMS_one(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 팝빌에 등록된 플러스친구 아이디, ListPlusFriend API - plusFriendID 확인
    $plusFriendID = '@팝빌';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 친구톡 내용, 최대 400자
    $content = '친구톡 내용';

    // 대체문자 내용
    $altContent = '대체문자 내용';

    // 대체문자 유형, 공백-미전송, A-대체문자내용 전송, C-친구톡내용 전송
    $altSendType = 'A';

    // 광고전송여부
    $adsYN = True;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신자 정보
    $receivers[] = array(
        // 수신번호
        'rcv' => '010111222',
        // 수신자명
        'rcvnm' => '수신자명'
    );

    // 버튼배열, 최대 5개
    $buttons[] = array(
        // 버튼 표시명
        'n' => '웹링크',
        // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
        't' => 'WL',
        // [앱링크] Android, [웹링크] Mobile
        'u1' => 'http://www.popbill.com',
        // [앱링크] IOS, [웹링크] PC URL
        'u2' => 'http://www.popbill.com',
    );

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    // 친구톡 이미지 전송규격
    // - 전송 포맷 : JPG 파일(.jpg, jpeg)
    // - 용량 제한 : 최대 500Byte
    // - 이미지 가로/세로 비율 : 1.5 미만 (가로 500px 이상)
    $files = array('/Users/John/Desktop/image.jpg');

    // 첨부 이미지 링크 URL
    $imageURL = 'http://popbill.com';

    try {
        $receiptNum = $this->PopbillKakao->SendFMS($testCorpNum, $plusFriendID, $sender,
            $content, $altContent, $altSendType, $adsYN, $receivers, $buttons, $reserveDT, $files, $imageURL, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => '친구톡 이미지 전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * [동보전송] 친구톡(이미지) 전송을 요청합니다.
   * - 친구톡은 심야 전송(20:00~08:00)이 제한됩니다.
   * - 이미지 전송규격 / jpg 포맷, 용량 최대 500KByte, 이미지 높이/너비 비율 1.333 이하, 1/2 이상
   */
  public function SendFMS_same(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 팝빌에 등록된 플러스친구 아이디, ListPlusFriend API - plusFriendID 확인
    $plusFriendID = '@팝빌';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 친구톡 내용, 최대 400자
    $content = '친구톡 내용';

    // 대체문자 내용
    $altContent = '대체문자 내용';

    // 대체문자 유형, 공백-미전송, A-대체문자내용 전송, C-친구톡내용 전송
    $altSendType = 'A';

    // 광고전송여부
    $adsYN = True;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신정보 배열, 최대 1000건
    for($i=0; $i<10; $i++){
      $receivers[] = array(
        // 수신번호
        'rcv' => '010111222',
        // 수신자명
        'rcvnm' => '수신자명'
      );
    }

    // 버튼배열, 최대 5개
    $buttons[] = array(
      // 버튼 표시명
      'n' => '웹링크',
      // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
      't' => 'WL',
      // [앱링크] Android, [웹링크] Mobile
      'u1' => 'http://www.popbill.com',
      // [앱링크] IOS, [웹링크] PC URL
      'u2' => 'http://www.popbill.com',
    );

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    // 친구톡 이미지 전송규격
    // - 전송 포맷 : JPG 파일(.jpg, jpeg)
    // - 용량 제한 : 최대 500Byte
    // - 이미지 가로/세로 비율 : 1.5 미만 (가로 500px 이상)
    $files = array('/Users/John/Desktop/image.jpg');

    // 첨부 이미지 링크 URL
    $imageURL = 'http://popbill.com';

    try {
        $receiptNum = $this->PopbillKakao->SendFMS($testCorpNum, $plusFriendID, $sender,
            $content, $altContent, $altSendType, $adsYN, $receivers, $buttons, $reserveDT, $files, $imageURL, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => '친구톡 이미지 전송 접수번호(receiptNum)', 'value' => $receiptNum]);
  }

  /**
   * [대량전송] 친구톡(이미지) 전송을 요청합니다.
   * - 친구톡은 심야 전송(20:00~08:00)이 제한됩니다.
   * - 이미지 전송규격 / jpg 포맷, 용량 최대 500KByte, 이미지 높이/너비 비율 1.333 이하, 1/2 이상
   */
  public function SendFMS_multi(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 팝빌에 등록된 플러스친구 아이디, ListPlusFriend API - plusFriendID 확인
    $plusFriendID = '@팝빌';

    // 팝빌에 사전 등록된 발신번호
    $sender = '07043042991';

    // 대체문자 유형, 공백-미전송, A-대체문자내용 전송, C-친구톡내용 전송
    $altSendType = 'A';

    // 광고전송여부
    $adsYN = True;

    // 전송요청번호
    // 파트너가 전송 건에 대해 관리번호를 구성하여 관리하는 경우 사용.
    // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
    $requestNum = '';

    // 수신정보 배열, 최대 1000건
    for($i=0; $i<10; $i++){
        $receivers[] = array(
            // 수신번호
            'rcv' => '010111222',
            // 수신자명
            'rcvnm' => '수신자명',
            // 친구톡 내용, 최대 1000자
            'msg' => '친구톡 메시지 내용'.$i,
            // 대체문자
            'altmsg' => '대체문자 내용'.$i,
        );
    }

    // 버튼배열, 최대 5개
    $buttons[] = array(
        // 버튼 표시명
        'n' => '웹링크',
        // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
        't' => 'WL',
        // [앱링크] Android, [웹링크] Mobile
        'u1' => 'http://www.popbill.com',
        // [앱링크] IOS, [웹링크] PC URL
        'u2' => 'http://www.popbill.com',
    );

    // 예약전송일시, yyyyMMddHHmmss
    $reserveDT = null;

    // 친구톡 이미지 전송규격
    // - 전송 포맷 : JPG 파일(.jpg, jpeg)
    // - 용량 제한 : 최대 500Byte
    // - 이미지 가로/세로 비율 : 1.5 미만 (가로 500px 이상)
    $files = array('/Users/John/Desktop/image.jpg');

    // 첨부 이미지 링크 URL
    $imageURL = 'http://popbill.com';

    try {
        $receiptNum = $this->PopbillKakao->SendFMS($testCorpNum, $plusFriendID, $sender,
            '', '', $altSendType, $adsYN, $receivers, $buttons, $reserveDT, $files, $imageURL, $testUserID, $requestNum);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => '친구톡 이미지 전송 접수번호(receiptNum)', 'value' => $receiptNum]);

  }

  /**
   * 카카오톡 전송요청시 발급받은 접수번호(receiptNum)로 예약전송건을 취소합니다.
   * - 예약취소는 예약전송시간 10분전까지만 가능합니다.
   */
  public function CancelReserve(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 전송 요청시 발급받은 카카오톡 접수번호
    $ReceiptNum = '019021416101100001';

    try {
        $result = $this->PopbillKakao->CancelReserve($testCorpNum ,$ReceiptNum);
        $code = $result->code;
        $message = $result->message;
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }
    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 전송요청번호(requestNum)를 할당한 알림톡/친구톡 예약전송건을 취소합니다.
   * - 예약전송 취소는 예약시간 10분전까지만 가능합니다.
   */
  public function CancelReserveRN(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 예약전송 요청시 할당한 전송요청번호
    $requestNum = '20190214-001';

    try {
        $result = $this->PopbillKakao->CancelReserveRN($testCorpNum ,$requestNum);
        $code = $result->code;
        $message = $result->message;
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }
    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
  * 카카오톡 전송요청시 발급받은 접수번호(receiptNum)로 전송결과를 확인합니다
  */
  public function GetMessages(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 카카오톡 전송 요청 시 발급받은 접수번호(receiptNum)
    $ReceiptNum = '019021416132900001';

    try {
        $result = $this->PopbillKakao->GetMessages($testCorpNum, $ReceiptNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('KakaoTalk/GetMessages', ['Result' => $result] );
  }

  /**
   * 전송요청번호(requestNum)를 할당한 알림톡/친구톡 전송내역 및 전송상태를 확인한다.
   */
  public function GetMessagesRN(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 전송 요청시 할당한 전송요청번호
    $requestNum = '20190214-001';

    try {
        $result = $this->PopbillKakao->GetMessagesRN($testCorpNum, $requestNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('KakaoTalk/GetMessages', ['Result' => $result] );
  }

  /**
   * 검색조건을 사용하여 알림톡/친구톡 전송 내역을 조회합니다.
   * - 최대 검색기간 : 6개월 이내
   */
  public function Search(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // [필수] 시작일자, 날짜형식(yyyyMMdd)
    $SDate = '20190101';

    // [필수] 종료일자, 날짜형식(yyyyMMdd)
    $EDate = '20190501';

    // 전송상태값 배열, 0-대기, 1-전송중, 2-성공, 3-대체, 4-실패, 5-예약취소
    $State = array('0', '1', '2', '3', '4', '5');

    // 검색대상, ATS-알림톡, FTS-친구톡(텍스트), FMS-친구톡(이미지)
    $Item = array('ATS','FTS','FMS');

    // 예약여부, 공백-전체조회, 1-예약전송조회, 0-즉시전송조회
    $ReserveYN = '';

    // 개인조회여부, false-전체조회, true-개인조회
    $SenderYN = false;

    // 페이지번호
    $Page = 1;

    // 페이지 검색개수, 기본값 500, 최대값 1000
    $PerPage = 500;

    // 정렬방향, D-내림차순, A-오름차순
    $Order = 'D';

    // 조회 검색어.
    // 카카오톡 전송시 입력한 수신자명 기재.
    // 조회 검색어를 포함한 수신자명을 검색합니다.
    $QString = '';

    try {
        $result = $this->PopbillKakao->Search( $testCorpNum, $SDate, $EDate, $State, $Item, $ReserveYN, $SenderYN, $Page, $PerPage, $Order, '', $QString );
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('KakaoTalk/Search', ['Result' => $result] );
  }

  /**
   * 카카오톡 전송내역 팝업 URL을 반환합니다.
   * 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   */
  public function GetSentListURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillKakao->GetSentListURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "카카오톡 전송내역 팝업 URL" , 'value' => $url]);
  }

  /**
   * 연동회원의 잔여포인트를 확인합니다.
   * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API)
   *   를 통해 확인하시기 바랍니다.
   */
  public function GetBalance(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillKakao->GetBalance($testCorpNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "연동회원 잔여포인트" , 'value' => $remainPoint]);
  }

  /**
   * 팝빌 연동회원의 포인트충전 팝업 URL을 반환합니다.
   * 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   */
  public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillKakao->GetChargeURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "연동회원 포인트 충전 팝업 URL" , 'value' => $url]);

  }

  /**
   * 파트너의 잔여포인트를 확인합니다.
   * - 과금방식이 연동과금인 경우 연동회원 잔여포인트(GetBalance API)를 이용하시기 바랍니다.
   */
  public function GetPartnerBalance(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillKakao->GetPartnerBalance($testCorpNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "파트너 잔여포인트" , 'value' => $remainPoint]);
  }

  /**
   * 파트너 포인트 충전 팝업 URL을 반환합니다.
   * - 반환된 URL은 보안정책에 따라 30초의 유효시간을 갖습니다.
   */
  public function GetPartnerURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // [CHRG] : 포인트충전 URL
    $TOGO = 'CHRG';

    try {
        $url = $this->PopbillKakao->GetPartnerURL($testCorpNum, $TOGO);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * 카카오톡 전송단가를 확인합니다.
   */
  public function GetUnitCost(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 카카오톡 전송유형 ATS-알림톡, FTS-친구톡(텍스트), FMS-친구톡(이미지)
    $kakaoType = ENumKakaoType::ATS;

    try {
        $unitCost= $this->PopbillKakao->GetUnitCost($testCorpNum, $kakaoType);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "카카오톡(".$kakaoType.") 전송단가 " , 'value' => $unitCost]);
  }

  /**
   * 카카오톡 서비스 과금정보를 확인합니다.
   */
  public function GetChargeInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 카카오톡 전송유형 ATS-알림톡, FTS-친구톡(텍스트), FMS-친구톡(이미지)
    $kakaoType = ENumKakaoType::ATS;

    try {
        $result = $this->PopbillKakao->GetChargeInfo($testCorpNum, $kakaoType, $testUserID);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetChargeInfo', ['Result' => $result]);
  }

  /**
   * 해당 사업자의 파트너 연동회원 가입여부를 확인합니다.
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    // ./config/popbill.php 에 선언된 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillKakao->CheckIsMember($testCorpNum, $LinkID);
      $code = $result->code;
      $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 팝빌 회원아이디 중복여부를 확인합니다.
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillKakao->CheckID($testUserID);
      $code = $result->code;
      $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 팝빌에 로그인 상태로 접근할 수 있는 팝업 URL을 반환합니다.
   * 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   */
  public function GetAccessURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillKakao->GetAccessURL($testCorpNum, $testUserID);
    } catch (PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);

  }
  /**
   * 파트너의 연동회원으로 회원가입을 요청합니다.
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
      $result = $this->PopbillKakao->JoinMember($joinForm);
      $code = $result->code;
      $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 연동회원의 회사정보를 확인합니다.
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillKakao->GetCorpInfo($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
  }

  /**
   * 연동회원의 회사정보를 수정합니다
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
        $result =  $this->PopbillKakao->UpdateCorpInfo($testCorpNum, $CorpInfo);
        $code = $result->code;
        $message = $result->message;
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 연동회원의 담당자를 신규로 등록합니다.
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
        $result = $this->PopbillKakao->RegistContact($testCorpNum, $ContactInfo);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 연동회원의 담당자 목록을 확인합니다.
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillKakao->ListContact($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ContactInfo', ['ContactList' => $ContactList]);
  }

  /**
   * 연동회원의 담당자 정보를 수정합니다.
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
        $result = $this->PopbillKakao->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
        $code = $result->code;
        $message = $result->message;
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }
}
