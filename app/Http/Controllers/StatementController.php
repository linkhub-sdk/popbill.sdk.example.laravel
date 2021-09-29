<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillStatement;
use Linkhub\Popbill\Statement;
use Linkhub\Popbill\StatementDetail;

class StatementController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 전자명세서 서비스 클래스 초기화
    $this->PopbillStatement = new PopbillStatement(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillStatement->IsTest(config('popbill.IsTest'));

    // 인증토큰의 IP제한기능 사용여부, 권장(true)
    $this->PopbillStatement->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

    // 팝빌 API 서비스 고정 IP 사용여부(GA), true-사용, false-미사용, 기본값(false)
    $this->PopbillStatement->UseStaticIP(config('popbill.UseStaticIP'));

    // 로컬서버 시간 사용 여부 true(기본값) - 사용, false(미사용)
    $this->PopbillStatement->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * 전자명세서 문서번호 중복여부를 확인합니다.
   * - 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
   * - https://docs.popbill.com/statement/phplaravel/api#CheckMgtKeyInUse
   */
  public function CheckMgtKeyInUse(){

    // 팝빌 회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 종류코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210701-001';

    try {
        $result = $this->PopbillStatement->CheckMgtKeyInUse($testCorpNum ,$itemCode, $mgtKey);
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
   * 작성된 전자명세서 데이터를 팝빌에 저장과 동시에 발행하여, "발행완료" 상태로 처리합니다.
   * - 팝빌 사이트 [전자명세서] > [환경설정] > [전자명세서 관리] 메뉴의 발행시 자동승인 옵션 설정을 통해 전자명세서를 "발행완료" 상태가 아닌 "승인대기" 상태로 발행 처리 할 수 있습니다.
   * - https://docs.popbill.com/statement/phplaravel/api#RegistIssue
   */
  public function RegistIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 전자명세서 문서번호
    // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210804-001';

    // 명세서 종류코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 메모
    $memo = '즉시발행 메모';

    // 안내 메일 제목, 공백 입력시 기본양식으로 전송
    $emailSubject = '';

    // 전자명세서 객체 생성
    $Statement = new Statement();

    /************************************************************
     *                       전자명세서 정보
     ************************************************************/

    // [필수] 기재상 작성일자
    $Statement->writeDate = '20210804';

    // [필수] (영수, 청구) 중 기재
    $Statement->purposeType = '영수';

    // [필수]  과세형태, (과세, 영세, 면세) 중 기재
    $Statement->taxType = '과세';

    // 맞춤양식코드, 미기재시 기본양식으로 처리
    $Statement->formCode = '';

    // 명세서 종류 코드
    $Statement->itemCode = $itemCode;

    // 전자명세서 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $Statement->mgtKey = $mgtKey;

    /************************************************************
     *                         공급자 정보
     ************************************************************/
    $Statement->senderCorpNum = $testCorpNum;
    $Statement->senderTaxRegID = '';
    $Statement->senderCorpName = '공급자 상호';
    $Statement->senderCEOName = '공급자 대표자 성명';
    $Statement->senderAddr = ' 공급자 주소';
    $Statement->senderBizClass = '공급자 업종';
    $Statement->senderBizType = '공급자 업태';
    $Statement->senderContactName = '공급자 담당자명';
    $Statement->senderTEL = '070-7070-0707';
    $Statement->senderHP = '010-000-2222';
    $Statement->senderEmail = 'test@test.com';

    /************************************************************
     *                         공급받는자 정보
     ************************************************************/
    $Statement->receiverCorpNum = '8888888888';
    $Statement->receiverTaxRegID = '';						// 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
    $Statement->receiverCorpName = '공급받는자 상호';
    $Statement->receiverCEOName = '공급받는자 대표자 성명';
    $Statement->receiverAddr = '공급받는자 주소';
    $Statement->receiverBizClass = '공급받는자 업종';
    $Statement->receiverBizType = '공급받는자 업태';
    $Statement->receiverContactName = '공급받는자 담당자명';
    $Statement->receiverTEL = '010-0000-1111';
    $Statement->receiverHP = '010-1111-2222';

    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $Statement->receiverEmail = 'test@test.com';

    /************************************************************
     *                       전자명세서 기재정보
     ************************************************************/
    $Statement->supplyCostTotal = '200000' ;				// [필수] 공급가액 합계
    $Statement->taxTotal = '20000';							// [필수] 세액 합계
    $Statement->totalAmount = '220000';						// [필수] 합계금액 (공급가액 합계+세액합계)
    $Statement->serialNum = '123';							// 기재상 일련번호 항목
    $Statement->remark1 = '비고1';
    $Statement->remark2 = '비고2';
    $Statement->remark3 = '비고3';
    $Statement->businessLicenseYN = False;					//사업자등록증 첨부 여부
    $Statement->bankBookYN = False;							//통장사본 첨부 여부
    $Statement->smssendYN = False;							//발행시 안내문자 전송여부

    /************************************************************
     *                       상세항목(품목) 정보
     ************************************************************/
    $Statement->detailList = array();
    $Statement->detailList[0] = new StatementDetail();
    $Statement->detailList[0]->serialNum = '1';					//품목 일련번호 1부터 순차 기재
    $Statement->detailList[0]->purchaseDT = '20210701';			//거래일자 yyyyMMdd
    $Statement->detailList[0]->itemName = '품명';
    $Statement->detailList[0]->spec = '규격';
    $Statement->detailList[0]->unit = '단위';
    $Statement->detailList[0]->qty = '1000';						//수량
    $Statement->detailList[0]->unitCost = '1000000';
    $Statement->detailList[0]->supplyCost = '10000000';
    $Statement->detailList[0]->tax = '1000000';
    $Statement->detailList[0]->remark = '11,000,000';
    $Statement->detailList[0]->spare1 = '1000000';
    $Statement->detailList[0]->spare2 = '1000000';
    $Statement->detailList[0]->spare3 = 'spare3';
    $Statement->detailList[0]->spare4 = 'spare4';
    $Statement->detailList[0]->spare5 = 'spare5';

    $Statement->detailList[1] = new StatementDetail();
    $Statement->detailList[1]->serialNum = '2';					//품목 일련번호 순차기재
    $Statement->detailList[1]->purchaseDT = '20210701';			//거래일자 yyyyMMdd
    $Statement->detailList[1]->itemName = '품명';
    $Statement->detailList[1]->spec = '규격';
    $Statement->detailList[1]->unit = '단위';
    $Statement->detailList[1]->qty = '1';
    $Statement->detailList[1]->unitCost = '100000';
    $Statement->detailList[1]->supplyCost = '100000';
    $Statement->detailList[1]->tax = '10000';
    $Statement->detailList[1]->remark = '비고';
    $Statement->detailList[1]->spare1 = 'spare1';
    $Statement->detailList[1]->spare2 = 'spare2';
    $Statement->detailList[1]->spare3 = 'spare3';
    $Statement->detailList[1]->spare4 = 'spare4';
    $Statement->detailList[1]->spare5 = 'spare5';

    /************************************************************
     * 전자명세서 추가속성
     * - 추가속성에 관한 자세한 사항은 "[전자명세서 API 연동매뉴얼] >
     *   5.2. 기본양식 추가속성 테이블"을 참조하시기 바랍니다.
     ************************************************************/
    $Statement->propertyBag = array(
        'Balance' => '50000',
        'Deposit' => '100000',
        'CBalance' => '150000'
    );

    try {
        $result = $this->PopbillStatement->RegistIssue($testCorpNum, $Statement, $memo, $testUserID, $emailSubject);
        $code = $result->code;
        $message = $result->message;
        $invoiceNum = $result->invoiceNum;
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        $invoiceNum = null;
    }

    return view('PResponse', ['code' => $code, 'message' => $message, 'invoiceNum' => $invoiceNum]);
  }

  /**
   * 작성된 전자명세서 데이터를 팝빌에 저장합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#Register
   */
  public function Register(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 발행자별 고유번호 할당, 1~24자리 영문,숫자 조합으로 중복없이 구성
    $mgtKey = '20210701-003';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 전자명세서 객체 생성
    $Statement = new Statement();

    /************************************************************
     *                       전자명세서 정보
     ************************************************************/

    // [필수] 기재상 작성일자
    $Statement->writeDate = '20210701';

    // [필수] (영수, 청구) 중 기재
    $Statement->purposeType = '영수';

    // [필수]  과세형태, (과세, 영세, 면세) 중 기재
    $Statement->taxType = '과세';

    // 맞춤양식코드, 미기재시 기본양식으로 처리
    $Statement->formCode = '';

    // 명세서 종류 코드
    $Statement->itemCode = $itemCode;

    // 전자명세서 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $Statement->mgtKey = $mgtKey;

    /************************************************************
     *                         공급자 정보
     ************************************************************/
    $Statement->senderCorpNum = $testCorpNum;
    $Statement->senderTaxRegID = '';
    $Statement->senderCorpName = '공급자 상호';
    $Statement->senderCEOName = '공급자 대표자 성명';
    $Statement->senderAddr = ' 공급자 주소';
    $Statement->senderBizClass = '공급자 업종';
    $Statement->senderBizType = '공급자 업태';
    $Statement->senderContactName = '공급자 담당자명';
    $Statement->senderTEL = '070-7070-0707';
    $Statement->senderHP = '010-000-2222';
    $Statement->senderEmail = 'test@test.com';

    /************************************************************
     *                         공급받는자 정보
     ************************************************************/
    $Statement->receiverCorpNum = '8888888888';
    $Statement->receiverTaxRegID = '';						// 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
    $Statement->receiverCorpName = '공급받는자 대표자 성명';
    $Statement->receiverCEOName = '공급받는자 대표자 성명';
    $Statement->receiverAddr = '공급받는자 주소';
    $Statement->receiverBizClass = '공급받는자 업종';
    $Statement->receiverBizType = '공급받는자 업태';
    $Statement->receiverContactName = '공급받는자 담당자명';
    $Statement->receiverTEL = '010-0000-1111';
    $Statement->receiverHP = '010-1111-2222';

    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $Statement->receiverEmail = 'test@test.com';

    /************************************************************
     *                       전자명세서 기재정보
     ************************************************************/
    $Statement->supplyCostTotal = '200000' ;				// [필수] 공급가액 합계
    $Statement->taxTotal = '20000';							// [필수] 세액 합계
    $Statement->totalAmount = '220000';						// [필수] 합계금액 (공급가액 합계+세액합계)
    $Statement->serialNum = '123';							// 기재상 일련번호 항목
    $Statement->remark1 = '비고1';
    $Statement->remark2 = '비고2';
    $Statement->remark3 = '비고3';
    $Statement->businessLicenseYN = False;					//사업자등록증 첨부 여부
    $Statement->bankBookYN = False;							//통장사본 첨부 여부
    $Statement->smssendYN = False;							//발행시 안내문자 전송여부

    /************************************************************
     *                       상세항목(품목) 정보
     ************************************************************/
    $Statement->detailList = array();
    $Statement->detailList[0] = new StatementDetail();
    $Statement->detailList[0]->serialNum = '1';					//품목 일련번호 1부터 순차 기재
    $Statement->detailList[0]->purchaseDT = '20210701';			//거래일자 yyyyMMdd
    $Statement->detailList[0]->itemName = '품명';
    $Statement->detailList[0]->spec = '규격';
    $Statement->detailList[0]->unit = '단위';
    $Statement->detailList[0]->qty = '1';						//수량
    $Statement->detailList[0]->unitCost = '100000';
    $Statement->detailList[0]->supplyCost = '100000';
    $Statement->detailList[0]->tax = '10000';
    $Statement->detailList[0]->remark = '비고';
    $Statement->detailList[0]->spare1 = 'spare1';
    $Statement->detailList[0]->spare2 = 'spare2';
    $Statement->detailList[0]->spare3 = 'spare3';
    $Statement->detailList[0]->spare4 = 'spare4';
    $Statement->detailList[0]->spare5 = 'spare5';

    $Statement->detailList[1] = new StatementDetail();
    $Statement->detailList[1]->serialNum = '2';					//품목 일련번호 순차기재
    $Statement->detailList[1]->purchaseDT = '20210701';			//거래일자 yyyyMMdd
    $Statement->detailList[1]->itemName = '품명';
    $Statement->detailList[1]->spec = '규격';
    $Statement->detailList[1]->unit = '단위';
    $Statement->detailList[1]->qty = '1';
    $Statement->detailList[1]->unitCost = '100000';
    $Statement->detailList[1]->supplyCost = '100000';
    $Statement->detailList[1]->tax = '10000';
    $Statement->detailList[1]->remark = '비고';
    $Statement->detailList[1]->spare1 = 'spare1';
    $Statement->detailList[1]->spare2 = 'spare2';
    $Statement->detailList[1]->spare3 = 'spare3';
    $Statement->detailList[1]->spare4 = 'spare4';
    $Statement->detailList[1]->spare5 = 'spare5';

    /************************************************************
     * 전자명세서 추가속성
     * - 추가속성에 관한 자세한 사항은 "[전자명세서 API 연동매뉴얼] >
     *   5.2. 기본양식 추가속성 테이블"을 참조하시기 바랍니다.
     ************************************************************/
    $Statement->propertyBag = array(
        'Balance' => '50000',
        'Deposit' => '100000',
        'CBalance' => '150000'
    );

    try {
        $result = $this->PopbillStatement->Register($testCorpNum, $Statement);
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
   * "임시저장" 상태의 전자명세서를 수정합니다.건의 전자명세서를 [수정]합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#Update
   */
  public function Update(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 전자명세서 문서번호
    // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210701-003';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 전자명세서 객체 생성
    $Statement = new Statement();

    /************************************************************
     *                       전자명세서 정보
     ************************************************************/

    // [필수] 기재상 작성일자
    $Statement->writeDate = '20210701';

    // [필수] (영수, 청구) 중 기재
    $Statement->purposeType = '청구';

    // [필수]  과세형태, (과세, 영세, 면세) 중 기재
    $Statement->taxType = '과세';

    // 맞춤양식코드, 미기재시 기본양식으로 처리
    $Statement->formCode = '';

    // 명세서 종류 코드
    $Statement->itemCode = $itemCode;

    // 전자명세서 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $Statement->mgtKey = $mgtKey;

    /************************************************************
     *                         공급자 정보
     ************************************************************/
    $Statement->senderCorpNum = $testCorpNum;
    $Statement->senderTaxRegID = '';
    $Statement->senderCorpName = '공급자 상호_수정';
    $Statement->senderCEOName = '공급자 대표자 성명';
    $Statement->senderAddr = ' 공급자 주소';
    $Statement->senderBizClass = '공급자 업종';
    $Statement->senderBizType = '공급자 업태';
    $Statement->senderContactName = '공급자 담당자명';
    $Statement->senderTEL = '070-7070-0707';
    $Statement->senderHP = '010-000-2222';
    $Statement->senderEmail = 'test@test.com';

    /************************************************************
     *                         공급받는자 정보
     ************************************************************/
    $Statement->receiverCorpNum = '8888888888';
    $Statement->receiverTaxRegID = '';						// 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
    $Statement->receiverCorpName = '공급받는자 대표자 성명';
    $Statement->receiverCEOName = '공급받는자 대표자 성명';
    $Statement->receiverAddr = '공급받는자 주소';
    $Statement->receiverBizClass = '공급받는자 업종';
    $Statement->receiverBizType = '공급받는자 업태';
    $Statement->receiverContactName = '공급받는자 담당자명';
    $Statement->receiverTEL = '010-0000-1111';
    $Statement->receiverHP = '010-1111-2222';

    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $Statement->receiverEmail = 'test@test.com';

    /************************************************************
     *                       전자명세서 기재정보
     ************************************************************/
    $Statement->supplyCostTotal = '200000' ;				// [필수] 공급가액 합계
    $Statement->taxTotal = '20000';							// [필수] 세액 합계
    $Statement->totalAmount = '220000';						// [필수] 합계금액 (공급가액 합계+세액합계)
    $Statement->serialNum = '123';							// 기재상 일련번호 항목
    $Statement->remark1 = '비고1';
    $Statement->remark2 = '비고2';
    $Statement->remark3 = '비고3';
    $Statement->businessLicenseYN = False;					//사업자등록증 첨부 여부
    $Statement->bankBookYN = False;							//통장사본 첨부 여부
    $Statement->smssendYN = False;							//발행시 안내문자 전송여부

    /************************************************************
     *                       상세항목(품목) 정보
     ************************************************************/
    $Statement->detailList = array();
    $Statement->detailList[0] = new StatementDetail();
    $Statement->detailList[0]->serialNum = '1';					//품목 일련번호 1부터 순차 기재
    $Statement->detailList[0]->purchaseDT = '20210701';			//거래일자 yyyyMMdd
    $Statement->detailList[0]->itemName = '품명';
    $Statement->detailList[0]->spec = '규격';
    $Statement->detailList[0]->unit = '단위';
    $Statement->detailList[0]->qty = '1';						//수량
    $Statement->detailList[0]->unitCost = '100000';
    $Statement->detailList[0]->supplyCost = '100000';
    $Statement->detailList[0]->tax = '10000';
    $Statement->detailList[0]->remark = '비고';
    $Statement->detailList[0]->spare1 = 'spare1';
    $Statement->detailList[0]->spare2 = 'spare2';
    $Statement->detailList[0]->spare3 = 'spare3';
    $Statement->detailList[0]->spare4 = 'spare4';
    $Statement->detailList[0]->spare5 = 'spare5';

    $Statement->detailList[1] = new StatementDetail();
    $Statement->detailList[1]->serialNum = '2';					//품목 일련번호 순차기재
    $Statement->detailList[1]->purchaseDT = '20210701';			//거래일자 yyyyMMdd
    $Statement->detailList[1]->itemName = '품명';
    $Statement->detailList[1]->spec = '규격';
    $Statement->detailList[1]->unit = '단위';
    $Statement->detailList[1]->qty = '1';
    $Statement->detailList[1]->unitCost = '100000';
    $Statement->detailList[1]->supplyCost = '100000';
    $Statement->detailList[1]->tax = '10000';
    $Statement->detailList[1]->remark = '비고';
    $Statement->detailList[1]->spare1 = 'spare1';
    $Statement->detailList[1]->spare2 = 'spare2';
    $Statement->detailList[1]->spare3 = 'spare3';
    $Statement->detailList[1]->spare4 = 'spare4';
    $Statement->detailList[1]->spare5 = 'spare5';

    /************************************************************
     * 전자명세서 추가속성
     * - 추가속성에 관한 자세한 사항은 "[전자명세서 API 연동매뉴얼] >
     *   5.2. 기본양식 추가속성 테이블"을 참조하시기 바랍니다.
     ************************************************************/
    $Statement->propertyBag = array(
        'Balance' => '50000',
        'Deposit' => '100000',
        'CBalance' => '150000'
    );

    try {
        $result = $this->PopbillStatement->Update($testCorpNum, $itemCode, $mgtKey, $Statement);
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
   * "임시저장" 상태의 전자명세서를 발행하여, "발행완료" 상태로 처리합니다.
   * - 팝빌 사이트 [전자명세서] > [환경설정] > [전자명세서 관리] 메뉴의 발행시 자동승인 옵션 설정을 통해 전자명세서를 "발행완료" 상태가 아닌 "승인대기" 상태로 발행 처리 할 수 있습니다.
   * - 전자명세서 발행 함수 호출시 포인트가 과금되며, 수신자에게 발행 안내 메일이 발송됩니다.
   * - https://docs.popbill.com/statement/phplaravel/api#StmIssue
   */
  public function Issue(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 전자명세서 문서번호
    $MgtKey = '20210701-003';

    // 메모
    $memo = '전자명세서 발행 메모';

    try	{
        $result = $this->PopbillStatement->Issue($testCorpNum, $itemCode, $MgtKey, $memo);
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
   * 발신자가 발행한 전자명세서를 발행취소합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#CancelIssue
   */
  public function CancelIssue(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $MgtKey = '20210701-003';

    // 메모
    $memo = '전자명세서 발행취소 메모';

    try	{
        $result = $this->PopbillStatement->CancelIssue($testCorpNum, $itemCode, $MgtKey, $memo);
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
   * 삭제 가능한 상태의 전자명세서를 삭제합니다.
   * - 삭제 가능한 상태: "임시저장", "취소", "승인거부", "발행취소"
   * - 전자명세서를 삭제하면 사용된 문서번호(mgtKey)를 재사용할 수 있습니다.
   * - https://docs.popbill.com/statement/phplaravel/api#Delete
   */
  public function Delete(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $MgtKey = '20210701-003';

    try	{
        $result = $this->PopbillStatement->Delete($testCorpNum, $itemCode, $MgtKey);
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
   * 전자명세서의 1건의 상태 및 요약정보 확인합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetInfo
   */
  public function GetInfo(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210701-001';

    try {
        $result = $this->PopbillStatement->GetInfo($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Statement/GetInfo', ['StatementInfo' => [$result] ] );

  }

  /**
   * 다수건의 전자명세서 상태/요약 정보를 확인합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetInfos
   */
  public function GetInfos(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 조회할 전자명세서 문서번호 배열, 최대 1000건
    $MgtKeyList = array(
        '20210701-001',
        '20210701-002',
        '20210701-003'
    );

    try {
        $resultList = $this->PopbillStatement->GetInfos($testCorpNum, $itemCode, $MgtKeyList);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Statement/GetInfo', ['StatementInfo' => $resultList ] );

  }

  /**
   * 전자명세서 1건의 상세정보 확인합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetDetailInfo
   */
  public function GetDetailInfo(){

    // 팝빌회원 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210701-002';

    try {
        $result = $this->PopbillStatement->GetDetailInfo($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Statement/GetDetailInfo', ['Statement' => $result] );
  }

  /**
   * 검색조건에 해당하는 전자명세서를 조회합니다. (조회기간 단위 : 최대 6개월)
   * - https://docs.popbill.com/statement/phplaravel/api#Search
   */
  public function Search(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // [필수] 조회일자 유형, R-등록일자, W-작성일자, I-발행일자
    $DType = 'W';

    // [필수] 시작일자
    $SDate = '20210701';

    // [필수] 종료일자
    $EDate = '20210730';

    // 전송상태값 배열, 문서상태값 3자리 배열, 2,3번째 와일드카드 사용가능
    $State = array(
        '100',
        '2**',
        '3**'
    );

    // 명세서 코드배열 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $ItemCode = array(
        121,
        122,
        123,
        124,
        125,
        126
    );

    // 페이지 번호, 기본값 1
    $Page = 1;

    // 페이지당 검색갯수, 기본값(500), 최대값(1000)
    $PerPage = 20;

    // 정렬방향, D-내림차순, A-오름차순
    $Order = 'D';

    // 거래처 조회, 거래처 상호 또는 거래처 사업자등록번호 기재하여 조회, 미기재시 전체조회
    $QString = '';

    try {
        $result = $this->PopbillStatement->Search($testCorpNum, $DType, $SDate, $EDate,
            $State, $ItemCode, $Page, $PerPage, $Order, $QString);
    }	catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Statement/Search', ['Result' => $result] );
  }

  /**
   * 전자명세서의 상태에 대한 변경이력을 확인합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetLogs
   */
  public function GetLogs(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210701-001';

    try {
        $result = $this->PopbillStatement->GetLogs($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetLogs', ['Result' => $result] );
  }

  /**
   * 로그인 상태로 팝빌 사이트의 전자명세서 문서함 메뉴에 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetURL
   */
  public function GetURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // 임시문서함(TBOX), 발행문서함(SBOX)
    $TOGO = 'TBOX';

    try {
        $url = $this->PopbillStatement->GetURL($testCorpNum, $testUserID, $TOGO);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자명세서 문서함 팝업 URL" , 'value' => $url]);
  }

  /**
   * 팝빌 사이트와 동일한 전자명세서 1건의 상세 정보 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetPopUpURL
   */
  public function GetPopUpURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 전자명세서 문서번호
    $mgtKey = '20210701-001';

    try {
        $url = $this->PopbillStatement->GetPopUpURL($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자명세서 내용 보기 팝업 URL" , 'value' => $url]);
  }

  /**
   * 팝빌 사이트와 동일한 전자명세서 1건의 상세 정보 페이지(사이트 상단, 좌측 메뉴 및 버튼 제외)의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetViewURL
   */
  public function GetViewURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 전자명세서 문서번호
    $mgtKey = '20210701-001';

    try {
        $url = $this->PopbillStatement->GetViewURL($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자명세서 보기 팝업 URL" , 'value' => $url]);
  }

  /**
   * 전자명세서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환하며, 페이지내에서 인쇄 설정값을 "공급자" / "공급받는자" / "공급자+공급받는자"용 중 하나로 지정할 수 있습니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetPrintURL
   */
  public function GetPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 전자명세서 문서번호
    $mgtKey = '20210701-001';

    try {
        $url = $this->PopbillStatement->GetPrintURL($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자명세서 인쇄 팝업 URL" , 'value' => $url]);
  }

  /**
   * "공급받는자" 용 전자명세서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetEPrintURL
   */
  public function GetEPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210701-001';

    try {
        $url = $this->PopbillStatement->GetEPrintURL($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "전자명세서 인쇄(공급받는자용) 팝업 URL" , 'value' => $url]);
  }

  /**
   * 다수건의 전자명세서를 인쇄하기 위한 페이지의 팝업 URL을 반환합니다. (최대 100건)
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetMassPrintURL
   */
  public function GetMassPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호 배열, 최대 100건
    $mgtKeyList = array (
        '20210801-001',
        '20210801-002',
        '20210801-003'
    );

    try {
        $url = $this->PopbillStatement->GetMassPrintURL($testCorpNum, $itemCode, $mgtKeyList);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자명세서 인쇄(대량) 팝업 URL" , 'value' => $url]);
  }

  /**
   * 안내메일과 관련된 전자명세서를 확인 할 수 있는 상세 페이지의 팝업 URL을 반환하며, 해당 URL은 메일 하단의 파란색 버튼의 링크와 같습니다.
   * - 함수 호출로 반환 받은 URL에는 유효시간이 없습니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetMailURL
   */
  public function GetMailURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 전자명세서 문서번호
    $mgtKey = '20210801-001';

    try {
        $url = $this->PopbillStatement->GetMailURL($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자명세서 공급받는자 메일 링크 URL" , 'value' => $url]);
  }

  /**
   * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetAccessURL
   */
  public function GetAccessURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillStatement->GetAccessURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);
  }

  /**
   * "임시저장" 상태의 명세서에 1개의 파일을 첨부합니다. (최대 5개)
   * - https://docs.popbill.com/statement/phplaravel/api#AttachFile
   */
  public function AttachFile(){

    // 팝빌 회원 사업자번호, "-" 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode= '121';

    // 문서번호
    $mgtKey = '20210801-003';

    // 첨부파일 경로, 해당 파일에 읽기 권한이 설정되어 있어야 합니다.
    $filePath = '/Users/John/Desktop/03A4C36315C047B4A171CEF283ED9A40.jpg';

    try {
        $result = $this->PopbillStatement->AttachFile($testCorpNum, $itemCode, $mgtKey, $filePath);
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
   * 전자명세서에 첨부된 파일목록을 확인합니다.
   * - 응답항목 중 파일아이디(AttachedFile) 항목은 파일삭제(DeleteFile API) 호출시 이용할 수 있습니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetFiles
   */
  public function GetFiles(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210801-003';

    try {
        $result = $this->PopbillStatement->GetFiles($testCorpNum, $itemCode, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('GetFiles', ['Result' => $result] );
  }

  /**
   * "임시저장" 상태의 전자명세서에 첨부된 1개의 파일을 삭제합니다.
   * - 파일을 식별하는 파일아이디는 첨부파일 목록(GetFiles API) 의 응답항목 중 파일아이디(AttachedFile) 값을 통해 확인할 수 있습니다.
   * - https://docs.popbill.com/statement/phplaravel/api#DeleteFile
   */
  public function DeleteFile(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210801-003';

    // 첨부된 파일의 아이디, GetFiles API 응답항목중 AttachedFile 항목
    $FileID= '7D536E85-7CA7-44AA-89F4-A85781A1CD55.PBF';

    try {
        $result = $this->PopbillStatement->DeleteFile($testCorpNum, $itemCode, $mgtKey, $FileID);
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
   * "승인대기", "발행완료" 상태의 전자명세서와 관련된 발행 안내 메일을 재전송 합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#SendEmail
   */
  public function SendEmail(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210801-001';

    // 수신자 이메일주소
    $receiver = 'test@test.com';

    try {
        $result = $this->PopbillStatement->SendEmail($testCorpNum, $itemCode, $mgtKey, $receiver);
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
   * 전자명세서와 관련된 안내 SMS(단문) 문자를 재전송하는 함수로, 팝빌 사이트 [문자·팩스] > [문자] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
   * - 메시지는 최대 90byte까지 입력 가능하고, 초과한 내용은 자동으로 삭제되어 전송합니다. (한글 최대 45자)
   * - 함수 호출시 포인트가 과금됩니다.
   * - https://docs.popbill.com/statement/phplaravel/api#SendSMS
   */
  public function SendSMS(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210801-001';

    // 발신번호
    $sender = '07043042991';

    // 수신번호
    $receiver = '010111222';

    // 메시지 내용, 90byte 초과시 길이가 조정되어 전송됨
    $contents = '메세지 전송 내용입니다. 메세지의 길이가 90Byte를 초과하는 길이가 조정되어 전송되오니 참고하여 테스트하시기 바랍니다';

    try {
        $result = $this->PopbillStatement->SendSMS($testCorpNum, $itemCode, $mgtKey, $sender,
            $receiver, $contents);
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
   * 전자명세서를 팩스로 전송하는 함수로, 팝빌 사이트 [문자·팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
   * - 함수 호출시 포인트가 과금됩니다.
   * - https://docs.popbill.com/statement/phplaravel/api#SendFAX
   */
  public function SendFAX(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210801-001';

    // 발신번호
    $sender = '07043042991';

    // 수신팩스번호
    $receiver = '070111222';

    try {
        $result = $this->PopbillStatement->SendFAX($testCorpNum, $itemCode, $mgtKey, $sender, $receiver);
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
   * 전자명세서를 팩스로 전송하는 함수로, 팝빌에 데이터를 저장하는 과정이 없습니다.
   * - 팝빌 사이트 [문자·팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
   * - 함수 호출시 포인트가 과금됩니다.
   * - 팩스 발행 요청시 작성한 문서번호는 팩스전송 파일명으로 사용됩니다.
   * - 팩스 전송결과를 확인하기 위해서는 선팩스 전송 요청 시 반환받은 접수번호를 이용하여 팩스 API의 전송결과 확인 (GetFaxDetail) API를 이용하면 됩니다.
   * - https://docs.popbill.com/statement/phplaravel/api#FAXSend
   */
  public function FAXSend(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
    $mgtKey = '20210801-005';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 팩스전송 발신번호
    $sendNum = '07043042991';

    // 팩스수신번호
    $receiveNum = '070111222';

    // 전자명세서 객체 생성
    $Statement = new Statement();

    /************************************************************
     *                       전자명세서 정보
     ************************************************************/
    // [필수] 기재상 작성일자
    $Statement->writeDate = '20210801';

    // [필수] (영수, 청구) 중 기재
    $Statement->purposeType = '영수';

    // [필수]  과세형태, (과세, 영세, 면세) 중 기재
    $Statement->taxType = '과세';

    // 맞춤양식코드, 미기재시 기본양식으로 처리
    $Statement->formCode = '';

    // 명세서 종류 코드
    $Statement->itemCode = $itemCode;

    // 전자명세서 문서번호
    $Statement->mgtKey = $mgtKey;

    /************************************************************
     *                         공급자 정보
     ************************************************************/
    $Statement->senderCorpNum = $testCorpNum;
    $Statement->senderTaxRegID = '';
    $Statement->senderCorpName = '공급자 상호';
    $Statement->senderCEOName = '공급자 대표자 성명';
    $Statement->senderAddr = ' 공급자 주소';
    $Statement->senderBizClass = '공급자 업종';
    $Statement->senderBizType = '공급자 업태';
    $Statement->senderContactName = '공급자 담당자명';
    $Statement->senderTEL = '070-7070-0707';
    $Statement->senderHP = '010-000-2222';
    $Statement->senderEmail = 'test@test.com';

    /************************************************************
     *                         공급받는자 정보
     ************************************************************/
    $Statement->receiverCorpNum = '8888888888';
    $Statement->receiverTaxRegID = '';						// 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
    $Statement->receiverCorpName = '공급받는자 대표자 성명';
    $Statement->receiverCEOName = '공급받는자 대표자 성명';
    $Statement->receiverAddr = '공급받는자 주소';
    $Statement->receiverBizClass = '공급받는자 업종';
    $Statement->receiverBizType = '공급받는자 업태';
    $Statement->receiverContactName = '공급받는자 담당자명';
    $Statement->receiverTEL = '010-0000-1111';
    $Statement->receiverHP = '010-1111-2222';

    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $Statement->receiverEmail = 'test@test.com';

    /************************************************************
     *                       전자명세서 기재정보
     ************************************************************/
    $Statement->supplyCostTotal = '200000' ;				// [필수] 공급가액 합계
    $Statement->taxTotal = '20000';							// [필수] 세액 합계
    $Statement->totalAmount = '220000';						// [필수] 합계금액 (공급가액 합계+세액합계)
    $Statement->serialNum = '123';							// 기재상 일련번호 항목
    $Statement->remark1 = '비고1';
    $Statement->remark2 = '비고2';
    $Statement->remark3 = '비고3';
    $Statement->businessLicenseYN = False;					//사업자등록증 첨부 여부
    $Statement->bankBookYN = False;							//통장사본 첨부 여부
    $Statement->smssendYN = False;							//발행시 안내문자 전송여부

    /************************************************************
     *                       상세항목(품목) 정보
     ************************************************************/
    $Statement->detailList = array();
    $Statement->detailList[0] = new StatementDetail();
    $Statement->detailList[0]->serialNum = '1';					//품목 일련번호 1부터 순차 기재
    $Statement->detailList[0]->purchaseDT = '20210801';			//거래일자 yyyyMMdd
    $Statement->detailList[0]->itemName = '품명';
    $Statement->detailList[0]->spec = '규격';
    $Statement->detailList[0]->unit = '단위';
    $Statement->detailList[0]->qty = '1';						//수량
    $Statement->detailList[0]->unitCost = '100000';
    $Statement->detailList[0]->supplyCost = '100000';
    $Statement->detailList[0]->tax = '10000';
    $Statement->detailList[0]->remark = '비고';
    $Statement->detailList[0]->spare1 = 'spare1';
    $Statement->detailList[0]->spare2 = 'spare2';
    $Statement->detailList[0]->spare3 = 'spare3';
    $Statement->detailList[0]->spare4 = 'spare4';
    $Statement->detailList[0]->spare5 = 'spare5';

    $Statement->detailList[1] = new StatementDetail();
    $Statement->detailList[1]->serialNum = '2';					//품목 일련번호 순차기재
    $Statement->detailList[1]->purchaseDT = '20210801';			//거래일자 yyyyMMdd
    $Statement->detailList[1]->itemName = '품명';
    $Statement->detailList[1]->spec = '규격';
    $Statement->detailList[1]->unit = '단위';
    $Statement->detailList[1]->qty = '1';
    $Statement->detailList[1]->unitCost = '100000';
    $Statement->detailList[1]->supplyCost = '100000';
    $Statement->detailList[1]->tax = '10000';
    $Statement->detailList[1]->remark = '비고';
    $Statement->detailList[1]->spare1 = 'spare1';
    $Statement->detailList[1]->spare2 = 'spare2';
    $Statement->detailList[1]->spare3 = 'spare3';
    $Statement->detailList[1]->spare4 = 'spare4';
    $Statement->detailList[1]->spare5 = 'spare5';

    /************************************************************
     * 전자명세서 추가속성
     * - 추가속성에 관한 자세한 사항은 "[전자명세서 API 연동매뉴얼] >
     *   5.2. 기본양식 추가속성 테이블"을 참조하시기 바랍니다.
     ************************************************************/
    $Statement->propertyBag = array(
        'Balance' => '50000',
        'Deposit' => '100000',
        'CBalance' => '150000'
    );

    try {
        $receiptNum = $this->PopbillStatement->FAXSend($testCorpNum, $Statement, $sendNum, $receiveNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "선팩스전송 접수번호(receiptNum)" , 'value' => $receiptNum]);
  }

  /**
   * 하나의 전자명세서에 다른 전자명세서를 첨부합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#AttachStatement
   */
  public function AttachStatement(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210801-001';

    // 첨부할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $subItemCode = '121';

    // 첨부할 명세서 문서번호
    $subMgtKey = '20210801-002';

    try {
        $result = $this->PopbillStatement->AttachStatement($testCorpNum, $itemCode, $mgtKey, $subItemCode, $subMgtKey);
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
   * 하나의 전자명세서에 첨부된 다른 전자명세서를 해제합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#DetachStatement
   */
  public function DetachStatement(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 문서번호
    $mgtKey = '20210801-001';

    // 첨부해제할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $subItemCode = '121';

    // 첨부해제할 명세서 문서번호
    $subMgtKey = '20210801-002';

    try {
        $result = $this->PopbillStatement->DetachStatement($testCorpNum, $itemCode, $mgtKey, $subItemCode, $subMgtKey);
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
   * 전자명세서 관련 메일 항목에 대한 발송설정을 확인합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#ListEmailConfig
   */
  public function ListEmailConfig(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillStatement->ListEmailConfig($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Statement/ListEmailConfig', ['Result' => $result] );
  }

  /**
   * 전자명세서 관련 메일 항목에 대한 발송설정을 수정합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#UpdateEmailConfig
   *
   * 메일전송유형
   * SMT_ISSUE : 공급받는자에게 전자명세서가 발행 되었음을 알려주는 메일입니다.
   * SMT_ACCEPT : 공급자에게 전자명세서가 승인 되었음을 알려주는 메일입니다.
   * SMT_DENY : 공급자에게 전자명세서가 거부 되었음을 알려주는 메일입니다.
   * SMT_CANCEL : 공급받는자에게 전자명세서가 취소 되었음을 알려주는 메일입니다.
   * SMT_CANCEL_ISSUE : 공급받는자에게 전자명세서가 발행취소 되었음을 알려주는 메일입니다.
   */
  public function UpdateEmailConfig(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 메일 전송 유형
    $emailType = 'SMT_ISSUE';

    // 전송 여부 (True = 전송, False = 미전송)
    $sendYN = True;

    try {
        $result = $this->PopbillStatement->UpdateEmailConfig($testCorpNum, $emailType, $sendYN);
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
   * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetBalance
   */
  public function GetBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillStatement->GetBalance($testCorpNum);
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
   * - https://docs.popbill.com/statement/phplaravel/api#GetChargeURL
   */
  public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
      $url = $this->PopbillStatement->GetChargeURL($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/statement/phplaravel/api#GetPaymentURL
   */
  public function GetPaymentURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillStatement->GetPaymentURL($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/statement/phplaravel/api#GetUseHistoryURL
   */
  public function GetUseHistoryURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillStatement->GetUseHistoryURL($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/statement/phplaravel/api#GetPartnerBalance
   */
  public function GetPartnerBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillStatement->GetPartnerBalance($testCorpNum);
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
   * - https://docs.popbill.com/statement/phplaravel/api#GetPartnerURL
   */
  public function GetPartnerURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // [CHRG] : 포인트충전 URL
    $TOGO = 'CHRG';

    try {
        $url = $this->PopbillStatement->GetPartnerURL($testCorpNum, $TOGO);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * 전자명세서 발행시 과금되는 포인트 단가를 확인합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetUnitCost
   */
  public function GetUnitCost(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';
    try {
        $unitCost = $this->PopbillStatement->GetUnitCost($testCorpNum,$itemCode);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자명세서 발행단가" , 'value' => $unitCost]);
  }

  /**
   * 팝빌 전자명세서 API 서비스 과금정보를 확인합니다.
   * - https://docs.popbill.com/statement/phplaravel/api#GetChargeInfo
   */
  public function GetChargeInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $itemCode = '121';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';
    try {
        $result = $this->PopbillStatement->GetChargeInfo( $testCorpNum, $itemCode, $testUserID );
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
   * - https://docs.popbill.com/statement/phplaravel/api#CheckIsMember
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    // /config/popbill.php 선언된 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillStatement->CheckIsMember($testCorpNum, $LinkID);
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
   * - https://docs.popbill.com/statement/phplaravel/api#CheckID
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillStatement->CheckID($testUserID);
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
   * - https://docs.popbill.com/statement/phplaravel/api#JoinMember
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
      $result = $this->PopbillStatement->JoinMember($joinForm);
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
   * - https://docs.popbill.com/statement/phplaravel/api#GetCorpInfo
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillStatement->GetCorpInfo($testCorpNum);
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
   * - https://docs.popbill.com/statement/phplaravel/api#UpdateCorpInfo
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
        $result =  $this->PopbillStatement->UpdateCorpInfo($testCorpNum, $CorpInfo);
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
   * - https://docs.popbill.com/statement/phplaravel/api#RegistContact
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
        $result = $this->PopbillStatement->RegistContact($testCorpNum, $ContactInfo);
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
   * - https://docs.popbill.com/statement/phplaravel/api#GetContactInfo
   */
  public function GetContactInfo(){
    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    //확인할 담당자 아이디
    $contactID = 'checkContact';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    try {
        $ContactInfo = $this->PopbillStatement->GetContactInfo($testCorpNum, $contactID, $testUserID);
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
   * - https://docs.popbill.com/statement/phplaravel/api#ListContact
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $ContactList = $this->PopbillStatement->ListContact($testCorpNum);
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
   * - https://docs.popbill.com/statement/phplaravel/api#UpdateContact
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
        $result = $this->PopbillStatement->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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
