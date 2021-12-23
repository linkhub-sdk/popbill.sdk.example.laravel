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

class AccountCheckController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 예금주조회 서비스 클래스 초기화
    $this->PopbillAccountCheck = new PopbillAccountCheck(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillAccountCheck->IsTest(config('popbill.IsTest'));

    // 인증토큰의 IP제한기능 사용여부, 권장(true)
    $this->PopbillAccountCheck->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

    // 팝빌 API 서비스 고정 IP 사용여부, true-사용, false-미사용, 기본값(false)
    $this->PopbillAccountCheck->UseStaticIP(config('popbill.UseStaticIP'));

    // 로컬서버 시간 사용 여부 true(기본값) - 사용, false(미사용)
    $this->PopbillAccountCheck->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * 1건의 예금주성명을 조회합니다.
   * - https://docs.popbill.com/accountcheck/phplaravel/api#CheckAccountInfo
   */
  public function CheckAccountInfo(){

    // 팝빌회원 사업자번호
    $MemberCorpNum = "1234567890";

    // 팝빌회원 아이디
    $testUesrID = "testkorea";

    // 기관코드
    $bankCode = "0004";

    // 계좌번호
    $accountNumber = "9432451175812";

    try {
        $result = $this->PopbillAccountCheck->checkAccountInfo($MemberCorpNum, $bankCode, $accountNumber, $testUesrID);
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#CheckDepositorInfo
   */
  public function CheckDepositorInfo(){

    // 팝빌회원 사업자번호
    $MemberCorpNum = "1234567890";

    //팝빌회원 아이디
    $testUserID = "testkorea";

    // 기관코드
    $bankCode = "";

    // 계좌번호
    $accountNumber = "";

    //등록번호 유형, P-개인, B-사업자
    $identityNumType = "";

    // 등록번호
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
   * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API) 를 통해 확인하시기 바랍니다.
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetBalance
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
   * 연동회원 포인트 충전을 위한 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetChargeURL
   */
  public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = '';

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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetPaymentURL
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetUseHistoryURL
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
   * 예금주 성명 조회시 과금되는 포인트 단가를 확인합니다.
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetUnitCost
   */
  public function GetUnitCost(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 서비스 유형, 성명 / 실명 중 택 1
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
   * - 과금방식이 연동과금인 경우 연동회원 잔여포인트(GetBalance API)를 이용하시기 바랍니다.
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetPartnerBalance
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetPartnerURL
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetChargeInfo
   */

  public function GetChargeInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 서비스 유형, 성명 / 실명 중 택 1
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
   *  사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
   * - https://docs.popbill.com/accountcheck/phplaravel/api#CheckIsMember
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    // ./config/popbill.php에 선언된 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#CheckID
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#JoinMember
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetCorpInfo
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillAccountCheck->GetCorpInfo($testCorpNum);
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#UpdateCorpInfo
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
        $result =  $this->PopbillAccountCheck->UpdateCorpInfo($testCorpNum, $CorpInfo);
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#RegistContact
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
        $result = $this->PopbillAccountCheck->RegistContact($testCorpNum, $ContactInfo);
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetContactInfo
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#ListContact
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillAccountCheck->ListContact($testCorpNum);
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
   * - https://docs.popbill.com/accountcheck/phplaravel/api#UpdateContact
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
   * 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/accountcheck/phplaravel/api#GetAccessURL
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

}
