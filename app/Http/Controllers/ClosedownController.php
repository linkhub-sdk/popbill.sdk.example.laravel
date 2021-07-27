<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillClosedown;

class ClosedownController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 휴폐업조회 서비스 클래스 초기화
    $this->PopbillClosedown = new PopbillClosedown(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillClosedown->IsTest(config('popbill.IsTest'));

    // 인증토큰의 IP제한기능 사용여부, 권장(true)
    $this->PopbillClosedown->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

    // 팝빌 API 서비스 고정 IP 사용여부(GA), true-사용, false-미사용, 기본값(false)
    $this->PopbillClosedown->UseStaticIP(config('popbill.UseStaticIP'));

    // 로컬서버 시간 사용 여부 true(기본값) - 사용, false(미사용)
    $this->PopbillClosedown->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * 사업자번호 1건에 대한 휴폐업정보를 확인합니다.
   * - https://docs.popbill.com/closedown/phplaravel/api#CheckCorpNum
   */
  public function CheckCorpNum(){

    // 팝빌회원 사업자번호
    $MemberCorpNum = "1234567890";

    // 조회 사업자번호
    $CheckCorpNum = "6798700433";

    try {
        $result = $this->PopbillClosedown->checkCorpNum($MemberCorpNum, $CheckCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('CloseDown/CheckCorpNum', ['Result' => [$result] ] );
  }

  /**
   * 다수건의 사업자번호에 대한 휴폐업정보를 확인합니다. (최대 1,000건)
   * - https://docs.popbill.com/closedown/phplaravel/api#CheckCorpNums
   */
  public function CheckCorpNums(){

    //팝빌회원 사업자번호
    $MemberCorpNum = "1234567890";

    // 조회할 사업자번호 배열, 최대 1000건
    $CorpNumList = array(
        "1234567890",
        "6798700433",
        "401-03-94930",
    );

    try {
        $result = $this->PopbillClosedown->checkCorpNums($MemberCorpNum, $CorpNumList);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('CloseDown/CheckCorpNum', ['Result' => $result ] );
  }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API) 를 통해 확인하시기 바랍니다.
     * - https://docs.popbill.com/closedown/phplaravel/api#GetBalance
     */
    public function GetBalance(){

      // 팝빌회원 사업자번호
      $testCorpNum = '1234567890';

      try {
          $remainPoint = $this->PopbillClosedown->GetBalance($testCorpNum);
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
     * - https://docs.popbill.com/closedown/phplaravel/api#GetChargeURL
     */
    public function GetChargeURL(){

      // 팝빌 회원 사업자 번호, "-"제외 10자리
      $testCorpNum = '1234567890';

      // 팝빌 회원 아이디
      $testUserID = '';

      try {
          $url = $this->PopbillClosedown->GetChargeURL($testCorpNum, $testUserID);
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
     * - https://docs.popbill.com/closedown/phplaravel/api#GetPaymentURL
     */
    public function GetPaymentURL(){

      // 팝빌 회원 사업자 번호, "-"제외 10자리
      $testCorpNum = '1234567890';

      // 팝빌 회원 아이디
      $testUserID = 'testkorea';

      try {
          $url = $this->PopbillClosedown->GetPaymentURL($testCorpNum, $testUserID);
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
     * - https://docs.popbill.com/closedown/phplaravel/api#GetUseHistoryURL
     */
    public function GetUseHistoryURL(){

      // 팝빌 회원 사업자 번호, "-"제외 10자리
      $testCorpNum = '1234567890';

      // 팝빌 회원 아이디
      $testUserID = 'testkorea';

      try {
          $url = $this->PopbillClosedown->GetUseHistoryURL($testCorpNum, $testUserID);
      } catch(PopbillException $pe) {
          $code = $pe->getCode();
          $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
      }

      return view('ReturnValue', ['filedName' => "연동회원 포인트 사용내역 팝업 URL" , 'value' => $url]);

    }

    /**
     * 휴폐업 조회시 과금되는 포인트 단가를 확인합니다.
     * - https://docs.popbill.com/closedown/phplaravel/api#GetUnitCost
     */
    public function GetUnitCost(){

      // 팝빌 회원 사업자 번호, "-"제외 10자리
      $testCorpNum = '1234567890';

      try {
          $unitCost = $this->PopbillClosedown->GetUnitCost($testCorpNum);
      }
      catch(PopbillException $pe) {
          $code = $pe->getCode();
          $message = $pe->getMessage();
          return view('PResponse', ['code' => $code, 'message' => $message]);
      }
      return view('ReturnValue', ['filedName' => "휴폐업조회 단가" , 'value' => $unitCost]);
    }

    /**
     * 파트너의 잔여포인트를 확인합니다.
     * - 과금방식이 연동과금인 경우 연동회원 잔여포인트(GetBalance API)를 이용하시기 바랍니다.
     * - https://docs.popbill.com/closedown/phplaravel/api#GetPartnerBalance
     */
    public function GetPartnerBalance(){

      // 팝빌회원 사업자번호
      $testCorpNum = '1234567890';

      try {
          $remainPoint = $this->PopbillClosedown->GetPartnerBalance($testCorpNum);
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
     * - https://docs.popbill.com/closedown/phplaravel/api#GetPartnerURL
     */
    public function GetPartnerURL(){

      // 팝빌 회원 사업자 번호, "-"제외 10자리
      $testCorpNum = '1234567890';

      // [CHRG] : 포인트충전 URL
      $TOGO = 'CHRG';

      try {
          $url = $this->PopbillClosedown->GetPartnerURL($testCorpNum, $TOGO);
      }
      catch(PopbillException $pe) {
          $code = $pe->getCode();
          $message = $pe->getMessage();
          return view('PResponse', ['code' => $code, 'message' => $message]);
      }
      return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
    }

    /**
     * 휴폐업조회 API 서비스 과금정보를 확인합니다.
     * - https://docs.popbill.com/closedown/phplaravel/api#GetChargeInfo
     */
    public function GetChargeInfo(){

      // 팝빌회원 사업자번호, '-'제외 10자리
      $testCorpNum = '1234567890';

      // 팝빌회원 아이디
      $testUserID = '';

      try {
          $result = $this->PopbillClosedown->GetChargeInfo($testCorpNum,$testUserID);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#CheckIsMember
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    // ./config/popbill.php에 선언된 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillClosedown->CheckIsMember($testCorpNum, $LinkID);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#CheckID
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillClosedown->CheckID($testUserID);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#JoinMember
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
      $result = $this->PopbillClosedown->JoinMember($joinForm);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#GetCorpInfo
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillClosedown->GetCorpInfo($testCorpNum);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#UpdateCorpInfo
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
        $result =  $this->PopbillClosedown->UpdateCorpInfo($testCorpNum, $CorpInfo);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#RegistContact
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
        $result = $this->PopbillClosedown->RegistContact($testCorpNum, $ContactInfo);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#GetContactInfo
   */
  public function GetContactInfo(){
    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    //확인할 담당자 아이디
    $contactID = 'checkContact';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    try {
        $ContactInfo = $this->PopbillClosedown->GetContactInfo($testCorpNum, $contactID, $testUserID);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#ListContact
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillClosedown->ListContact($testCorpNum);
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
   * - https://docs.popbill.com/closedown/phplaravel/api#UpdateContact
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
        $result = $this->PopbillClosedown->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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
   * 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/closedown/phplaravel/api#GetAccessURL
   */
  public function GetAccessURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillClosedown->GetAccessURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);

  }
}
