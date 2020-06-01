<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;


use Linkhub\Popbill\PopbillEasyFinBank;
use Linkhub\Popbill\EasyFinBankAccountForm;
use Linkhub\Popbill\UpdateEasyFinBankAccountForm;

class EasyFinBankController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 계좌조회 서비스 클래스 초기화
    $this->PopbillEasyFinBank = new PopbillEasyFinBank(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillEasyFinBank->IsTest(config('popbill.IsTest'));

    // 인증토큰의 IP제한기능 사용여부, 권장(true)
    $this->PopbillEasyFinBank->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }


  /**
   * 계좌를 등록합니다.
   */
  public function RegistBankAccount(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 계좌정보 클래스 생성
    $BankAccountInfo = new EasyFinBankAccountForm();

    // [필수] 은행코드
    // 산업은행-0002 / 기업은행-0003 / 국민은행-0004 /수협은행-0007 / 농협은행-0011 / 우리은행-0020
    // SC은행-0023 / 대구은행-0031 / 부산은행-0032 / 광주은행-0034 / 제주은행-0035 / 전북은행-0037
    // 경남은행-0039 / 새마을금고-0045 / 신협은행-0048 / 우체국-0071 / KEB하나은행-0081 / 신한은행-0088 /씨티은행-0027
    $BankAccountInfo->BankCode = '0039';

    // [필수] 계좌번호 하이픈('-') 제외
    $BankAccountInfo->AccountNumber = '';

    // [필수] 계좌비밀번호
    $BankAccountInfo->AccountPWD = '';

    // [필수] 계좌유형, "법인" 또는 "개인" 입력
    $BankAccountInfo->AccountType = '';

    // [필수] 예금주 식별정보 (‘-‘ 제외)
    // 계좌유형이 “법인”인 경우 : 사업자번호(10자리)
    // 계좌유형이 “개인”인 경우 : 예금주 생년월일 (6자리-YYMMDD)
    $BankAccountInfo->IdentityNumber = '';

    // 계좌 별칭
    $BankAccountInfo->AccountName = '';

    // 인터넷뱅킹 아이디 (국민은행 필수)
    $BankAccountInfo->BankID = '';

    // 조회전용 계정 아이디 (대구은행, 신협, 신한은행 필수)
    $BankAccountInfo->FastID = '';

    // 조회전용 계정 비밀번호 (대구은행, 신협, 신한은행 필수
    $BankAccountInfo->FastPWD = '';

    // 결제기간(개월), 1~12 입력가능, 미기재시 기본값(1) 처리
    // - 파트너 과금방식의 경우 입력값에 관계없이 1개월 처리
    $BankAccountInfo->UsePeriod = '';

    // 메모
    $BankAccountInfo->Memo = '';

    try {
        $result =  $this->PopbillEasyFinBank->RegistBankAccount($testCorpNum, $BankAccountInfo);
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
   * 팝빌에 등록된 은행계좌의 정액제 해지를 요청한다.
   */
  public function CloseBankAccount(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 은행코드
    // 산업은행-0002 / 기업은행-0003 / 국민은행-0004 /수협은행-0007 / 농협은행-0011 / 우리은행-0020
    // SC은행-0023 / 대구은행-0031 / 부산은행-0032 / 광주은행-0034 / 제주은행-0035 / 전북은행-0037
    // 경남은행-0039 / 새마을금고-0045 / 신협은행-0048 / 우체국-0071 / KEB하나은행-0081 / 신한은행-0088 /씨티은행-0027
    $bankCode = '';

    // 계좌번호
    $accountNumber = '';

    // 해지유형, “일반”, “중도” 중 선택 기재
    // 일반해지 – 이용중인 정액제 사용기간까지 이용후 정지
    // 중도해지 – 요청일 기준으로 정지, 정액제 잔여기간은 일할로 계산되어 포인트 환불 (무료 이용기간 중 중도해지 시 전액 환불)
    $closeType = '';

    try {
        $result =  $this->PopbillEasyFinBank->CloseBankAccount($testCorpNum, $bankCode, $accountNumber, $closeType);
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
   * 은행계좌의 정액제 해지요청을 취소한다.
   */
  public function RevokeCloseBankAccount(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 은행코드
    // 산업은행-0002 / 기업은행-0003 / 국민은행-0004 /수협은행-0007 / 농협은행-0011 / 우리은행-0020
    // SC은행-0023 / 대구은행-0031 / 부산은행-0032 / 광주은행-0034 / 제주은행-0035 / 전북은행-0037
    // 경남은행-0039 / 새마을금고-0045 / 신협은행-0048 / 우체국-0071 / KEB하나은행-0081 / 신한은행-0088 /씨티은행-0027
    $bankCode = '';

    // 계좌번호
    $accountNumber = '';

    try {
        $result =  $this->PopbillEasyFinBank->RevokeCloseBankAccount($testCorpNum, $bankCode, $accountNumber);
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
   * 계좌정보를 수정합니다.
   */
  public function UpdateBankAccount(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // [필수] 은행코드
    // 산업은행-0002 / 기업은행-0003 / 국민은행-0004 /수협은행-0007 / 농협은행-0011 / 우리은행-0020
    // SC은행-0023 / 대구은행-0031 / 부산은행-0032 / 광주은행-0034 / 제주은행-0035 / 전북은행-0037
    // 경남은행-0039 / 새마을금고-0045 / 신협은행-0048 / 우체국-0071 / KEB하나은행-0081 / 신한은행-0088 /씨티은행-0027
    $BankCode = '';

    // [필수]계좌번호
    $AccountNumber = '';

    // 계좌정보 클래스 생성
    $UpdateInfo = new UpdateEasyFinBankAccountForm();

    // [필수] 계좌비밀번호
    $UpdateInfo->AccountPWD = '';

    // 계좌 별칭
    $UpdateInfo->AccountName = '';

    // 인터넷뱅킹 아이디 (국민은행 필수)
    $UpdateInfo->BankID = '';

    // 조회전용 계정 아이디 (대구은행, 신협, 신한은행 필수)
    $UpdateInfo->FastID = '';

    // 조회전용 계정 비밀번호 (대구은행, 신협, 신한은행 필수
    $UpdateInfo->FastPWD = '';

    // 메모
    $UpdateInfo->Memo = '';

    try {
        $result =  $this->PopbillEasyFinBank->UpdateBankAccount($testCorpNum, $BankCode, $AccountNumber, $UpdateInfo);
        $code = $result->code;
        $message = $result->message;
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }


  /*
   * 은행 계좌 관리 팝업 URL을 반환한다.
   * - 반환된 URL은 보안정책에 따라 30초의 유효시간을 갖습니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetBankAccountMgtURL
   */
  public function GetBankAccountMgtURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $url = $this->PopbillEasyFinBank->GetBankAccountMgtURL($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "계좌 관리 팝업 URL" , 'value' => $url]);
  }


  /**
   * 팝빌에 등록된 계좌의 상세정보를 확인합니다.
   */
  public function GetBankAccountInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 은행코드
    // 산업은행-0002 / 기업은행-0003 / 국민은행-0004 /수협은행-0007 / 농협은행-0011 / 우리은행-0020
    // SC은행-0023 / 대구은행-0031 / 부산은행-0032 / 광주은행-0034 / 제주은행-0035 / 전북은행-0037
    // 경남은행-0039 / 새마을금고-0045 / 신협은행-0048 / 우체국-0071 / KEB하나은행-0081 / 신한은행-0088 /씨티은행-0027
    $bankCode = '';

    // 계좌번호, 하이픈('-') 제외
    $accountNumber = '';

    try {
        $result = $this->PopbillEasyFinBank->GetBankAccountInfo($testCorpNum, $bankCode, $accountNumber);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('EasyFinBank/GetBankAccountInfo', ['bankAccountInfo' => $result ] );
  }

  /**
   * 은행 계좌목록을 확인합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#ListBankAccount
   */
  public function ListBankAccount(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillEasyFinBank->ListBankAccount($testCorpNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('EasyFinBank/ListBankAccount', ['Result' => $result ] );
  }

  /*
   * 계좌 거래내역 수집을 요청한다.
   * - 검색기간은 현재일 기준 90일 이내로만 요청할 수 있다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#RequestJob
   */
  public function RequestJob(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 은행코드
    $BankCode = '0048';

    // 계좌번호
    $AccountNumber = '131020538645';

    // 시작일자, 형식(yyyyMMdd)
    $SDate = '20191001';

    // 종료일자, 형식(yyyyMMdd)
    $EDate = '20191230';

    try {
        $jobID = $this->PopbillEasyFinBank->RequestJob($testCorpNum, $BankCode, $AccountNumber, $SDate, $EDate);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "작업아이디(jobID)" , 'value' => $jobID]);
  }

  /**
   * 수집 요청 상태를 확인합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetJobState
   */
  public function GetJobState(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 수집 요청시 반환받은 작업아이디
    $jobID = '019123015000000001';

    try {
        $result = $this->PopbillEasyFinBank->GetJobState($testCorpNum, $jobID);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('EasyFinBank/JobState', ['Result' => [$result] ] );
  }

  /**
   * 수집 요청건들에 대한 상태 목록을 확인합니다.
   * - 수집 요청 작업아이디(JobID)의 유효시간은 1시간 입니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#ListActiveJob
   */
  public function ListActiveJob(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillEasyFinBank->ListActiveJob($testCorpNum);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('EasyFinBank/JobState', ['Result' => $result ] );
  }


  /*
  * 수집이 완료된 계좌 거래내역을 조회한다.
  * - https://docs.popbill.com/easyfinbank/phplaravel/api#Search
  */
  public function Search(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 수집 요청(RequestJob) 호출시 반환받은 작업아이디
    $JobID = '019123015000000004';

    // 거래유형 배열, I-입금, O-출금
    $TradeType = array ('I', 'O' );

    // 조회 검색어, 입금/출금액, 메모, 적요 like 검색
    $SearchString = "";

    // 페이지 번호
    $Page = 1;

    // 페이지당 목록개수
    $PerPage = 10;

    // 정렬방향, D-내림차순, A-오름차순
    $Order = "D";

    try {
        $result = $this->PopbillEasyFinBank->Search ( $testCorpNum, $JobID, $TradeType, $SearchString,
          $Page, $PerPage, $Order);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('EasyFinBank/Search', ['Result' => $result] );
  }

  /*
  * 수집이 완료된 계좌 거래내역 요약정보를 조회한다.
  * - https://docs.popbill.com/easyfinbank/phplaravel/api#Summary
  */
  public function Summary(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 수집 요청(RequestJob) 호출시 반환받은 작업아이디
    $JobID = '019123015000000004';

    // 거래유형 배열, I-입금, O-출금
    $TradeType = array ('I', 'O' );

    // 조회 검색어, 입금/출금액, 메모, 적요 like 검색
    $SearchString = "";

    try {
        $result = $this->PopbillEasyFinBank->Summary ( $testCorpNum, $JobID, $TradeType, $SearchString);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('EasyFinBank/Summary', ['Result' => $result] );
  }

  /**
   * 거래내역의 메모를 저장합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#SaveMemo
   */
  public function SaveMemo(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 거래내역 아이디, SeachAPI 응답항목 중 tid
    $TID = "01912181100000000120191210000003";

    // 메모
    $Memo = "0191230-PHPLaravel";

    try {
        $result =  $this->PopbillEasyFinBank->SaveMemo($testCorpNum, $TID, $Memo);
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
   * 계좌조회 API 서비스 과금정보를 확인합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetChargeInfo
   */

  public function GetChargeInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    try {
        $result = $this->PopbillEasyFinBank->GetChargeInfo($testCorpNum,$testUserID);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetChargeInfo', ['Result' => $result]);
  }

  /**
   * 정액제 서비스 신청 URL을 반환합니다.
   * - 반환된 URL은 보안정책에 따라 30초의 유효시간을 갖습니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetFlatRatePopUpURL
   */
  public function GetFlatRatePopUpURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $url = $this->PopbillEasyFinBank->GetFlatRatePopUpURL($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "정액제 서비스 신청 팝업 URL" , 'value' => $url]);
  }

  /**
   * 연동회원의 정액제 서비스 이용상태를 확인합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetFlatRateState
   */
  public function GetFlatRateState(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 은행코드
    $BankCode = '0048';

    // 계좌번호
    $AccountNumber = '131020538645';

    try {
        $result = $this->PopbillEasyFinBank->GetFlatRateState($testCorpNum, $BankCode, $AccountNumber);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('EasyFinBank/FlatRateState', ['Result' => $result]);
  }

  /**
   * 연동회원의 잔여포인트를 확인합니다.
   * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API) 를 통해 확인하시기 바랍니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetBalance
   */
  public function GetBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillEasyFinBank->GetBalance($testCorpNum);
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
   * - 반환된 URL은 보안정책에 따라 30초의 유효시간을 갖습니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetChargeURL
   */
  public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillEasyFinBank->GetChargeURL($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetPartnerBalance
   */
  public function GetPartnerBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillEasyFinBank->GetPartnerBalance($testCorpNum);
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
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetPartnerURL
   */
  public function GetPartnerURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // [CHRG] : 포인트충전 URL
    $TOGO = 'CHRG';

    try {
        $url = $this->PopbillEasyFinBank->GetPartnerURL($testCorpNum, $TOGO);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * 해당 사업자의 파트너 연동회원 가입여부를 확인합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#CheckIsMember
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    // ./config/popbill.php에 선언된 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillEasyFinBank->CheckIsMember($testCorpNum, $LinkID);
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
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#CheckID
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillEasyFinBank->CheckID($testUserID);
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
   * 파트너의 연동회원으로 회원가입을 요청합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#JoinMember
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
      $result = $this->PopbillEasyFinBank->JoinMember($joinForm);
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
   * - 반환된 URL의 유지시간은 30초이며, 제한된 시간 이후에는 정상적으로 처리되지 않습니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetAccessURL
   */
  public function GetAccessURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillEasyFinBank->GetAccessURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);

  }
  /**
   * 연동회원의 회사정보를 확인합니다.
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#GetCorpInfo
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillEasyFinBank->GetCorpInfo($testCorpNum);
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
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#UpdateCorpInfo
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
        $result =  $this->PopbillEasyFinBank->UpdateCorpInfo($testCorpNum, $CorpInfo);
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
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#RegistContact
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
        $result = $this->PopbillEasyFinBank->RegistContact($testCorpNum, $ContactInfo);
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
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#ListContact
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillEasyFinBank->ListContact($testCorpNum);
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
   * - https://docs.popbill.com/easyfinbank/phplaravel/api#UpdateContact
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
        $result = $this->PopbillEasyFinBank->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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

?>
