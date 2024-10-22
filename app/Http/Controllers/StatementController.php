<?php
/**
  * 팝빌 전자명세서 API PHP SDK Laravel Example
  *
  * Laravel 연동 튜토리얼 안내 : https://developers.popbill.com/guide/statement/php/getting-started/tutorial?fwn=laravel
  * 연동 기술지원 연락처 : 1600-9854
  * 연동 기술지원 이메일 : code@linkhubcorp.com
  *
  */
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
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class StatementController extends Controller
{
    public function __construct()
    {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 전자명세서 서비스 클래스 초기화
        $this->PopbillStatement = new PopbillStatement(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정, true-테스트, false-운영(Production), (기본값:false)
        $this->PopbillStatement->IsTest(config('popbill.IsTest'));

        // 인증토큰 IP 검증 설정, true-사용, false-미사용, (기본값:true)
        $this->PopbillStatement->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 통신 IP 고정, true-사용, false-미사용, (기본값:false)
        $this->PopbillStatement->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬시스템 시간 사용여부, true-사용, false-미사용, (기본값:true)
        $this->PopbillStatement->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request)
    {
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 파트너가 전자명세서 관리 목적으로 할당하는 문서번호의 사용여부를 확인합니다.
     * - 이미 사용 중인 문서번호는 중복 사용이 불가하고, 전자명세서가 삭제된 경우에만 문서번호의 재사용이 가능합니다.
     * - https://developers.popbill.com/reference/statement/php/api/info#CheckMgtKeyInUse
     */
    public function CheckMgtKeyInUse()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 종류코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $MgtKey = '20230102-PHP7-001';

        try {
            $result = $this->PopbillStatement->CheckMgtKeyInUse($CorpNum, $itemCode, $MgtKey);
            $result ? $result = '사용중' : $result = '미사용중';
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "문서번호 사용여부 =>" . $MgtKey . "", 'value' => $result]);
    }

    /**
     * 작성된 전자명세서 데이터를 팝빌에 저장과 동시에 발행하여, "발행완료" 상태로 처리합니다.
     * - 팝빌 사이트 [전자명세서] > [환경설정] > [전자명세서 관리] 메뉴의 발행시 자동승인 옵션 설정을 통해 전자명세서를 "발행완료" 상태가 아닌 "승인대기" 상태로 발행 처리 할 수 있습니다.
     * - https://developers.popbill.com/reference/statement/php/api/issue#RegistIssue
     */
    public function RegistIssue()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 전자명세서 문서번호
        // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $MgtKey = '20230102-PHP7-001';

        // 명세서 종류코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 객체 생성
        $Statement = new Statement();

        /************************************************************
         *                       전자명세서 정보
         ************************************************************/

        // 기재상 작성일자
        $Statement->writeDate = '20220405';

        // 결제대금 수취여부, (영수, 청구, 없음) 중 기재
        $Statement->purposeType = '영수';

        //  과세형태, (과세, 영세, 면세) 중 기재
        $Statement->taxType = '과세';

        // 맞춤양식코드, 미기재시 기본양식으로 처리
        $Statement->formCode = '';

        // 명세서 종류 코드
        $Statement->itemCode = $itemCode;

        // 전자명세서 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Statement->mgtKey = $MgtKey;

        /************************************************************
         *                         공급자 정보
         ************************************************************/
        $Statement->senderCorpNum = $CorpNum;
        $Statement->senderTaxRegID = '';
        $Statement->senderCorpName = '공급자 상호';
        $Statement->senderCEOName = '공급자 대표자 성명';
        $Statement->senderAddr = ' 공급자 주소';
        $Statement->senderBizClass = '공급자 업종';
        $Statement->senderBizType = '공급자 업태';
        $Statement->senderContactName = '공급자 담당자명';
        $Statement->senderTEL = '';
        $Statement->senderHP = '';
        $Statement->senderEmail = '';

        /************************************************************
         *                         공급받는자 정보
         ************************************************************/
        $Statement->receiverCorpNum = '8888888888';
        $Statement->receiverTaxRegID = '';      // 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
        $Statement->receiverCorpName = '공급받는자 상호';
        $Statement->receiverCEOName = '공급받는자 대표자 성명';
        $Statement->receiverAddr = '공급받는자 주소';
        $Statement->receiverBizClass = '공급받는자 업종';
        $Statement->receiverBizType = '공급받는자 업태';
        $Statement->receiverContactName = '공급받는자 담당자명';
        $Statement->receiverTEL = '';
        $Statement->receiverHP = '';

        // 팝빌 테스트 환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Statement->receiverEmail = '';

        /************************************************************
         *                       전자명세서 기재정보
         ************************************************************/
        $Statement->supplyCostTotal = '200000';    // 공급가액 합계
        $Statement->taxTotal = '20000';       // 세액 합계
        $Statement->totalAmount = '220000';      // 합계금액 (공급가액 합계+세액합계)
        $Statement->serialNum = '123';       // 기재상 일련번호 항목
        $Statement->remark1 = '비고1';
        $Statement->remark2 = '비고2';
        $Statement->remark3 = '비고3';

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->businessLicenseYN = False;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->bankBookYN = False;

        // 문자 자동전송 여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송(기본값)
        $Statement->smssendYN = False;

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/
        $Statement->detailList = array();
        $Statement->detailList[0] = new StatementDetail();
        $Statement->detailList[0]->serialNum = '1';     //품목 일련번호 1부터 순차 기재
        $Statement->detailList[0]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
        $Statement->detailList[0]->itemName = '품명';
        $Statement->detailList[0]->spec = '규격';
        $Statement->detailList[0]->unit = '단위';
        $Statement->detailList[0]->qty = '1000';      //수량
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
        $Statement->detailList[1]->serialNum = '2';     //품목 일련번호 순차기재
        $Statement->detailList[1]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
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
         *   기본양식 추가속성 테이블"을 참조하시기 바랍니다.
         * [https://developers.popbill.com/guide/statement/php/introduction/statement-form#propertybag-table]
         ************************************************************/
        $Statement->propertyBag = array(
            'Balance' => '50000',           // 전잔액
            'Deposit' => '100000',          // 입금액
            'CBalance' => '150000'          // 현잔액
        );

        // 메모
        $Memo = '즉시발행 메모';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 발행 안내 메일 제목
        // - 미입력 시 팝빌에서 지정한 이메일 제목으로 전송
        $emailSubject = null;

        try {
            $result = $this->PopbillStatement->RegistIssue($CorpNum, $Statement, $Memo, $UserID, $emailSubject);
            $code = $result->code;
            $message = $result->message;
            $invoiceNum = $result->invoiceNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $invoiceNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'invoiceNum' => $invoiceNum]);
    }

    /**
     * 작성된 전자명세서 데이터를 팝빌에 저장합니다.
     * - "임시저장" 상태의 전자명세서는 발행(Issue API) 함수를 호출하여 "발행완료"처리한 경우에만 수신자에게 발행 안내 메일이 발송됩니다.
     * - https://developers.popbill.com/reference/statement/php/api/issue#Register
     */
    public function Register()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 문서번호, 발행자별 고유번호 할당, 1~24자리 영문,숫자 조합으로 중복없이 구성
        $MgtKey = '20230102-PHP7-003';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 객체 생성
        $Statement = new Statement();

        /************************************************************
         *                       전자명세서 정보
         ************************************************************/

        // 기재상 작성일자
        $Statement->writeDate = '20220405';

        // 결제대금 수취여부, (영수, 청구, 없음) 중 기재
        $Statement->purposeType = '영수';

        //  과세형태, (과세, 영세, 면세) 중 기재
        $Statement->taxType = '과세';

        // 맞춤양식코드, 미기재시 기본양식으로 처리
        $Statement->formCode = '';

        // 명세서 종류 코드
        $Statement->itemCode = $itemCode;

        // 전자명세서 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Statement->mgtKey = $MgtKey;

        /************************************************************
         *                         공급자 정보
         ************************************************************/
        $Statement->senderCorpNum = $CorpNum;
        $Statement->senderTaxRegID = '';
        $Statement->senderCorpName = '공급자 상호';
        $Statement->senderCEOName = '공급자 대표자 성명';
        $Statement->senderAddr = ' 공급자 주소';
        $Statement->senderBizClass = '공급자 업종';
        $Statement->senderBizType = '공급자 업태';
        $Statement->senderContactName = '공급자 담당자명';
        $Statement->senderTEL = '';
        $Statement->senderHP = '';
        $Statement->senderEmail = '';

        /************************************************************
         *                         공급받는자 정보
         ************************************************************/
        $Statement->receiverCorpNum = '8888888888';
        $Statement->receiverTaxRegID = '';      // 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
        $Statement->receiverCorpName = '공급받는자 대표자 성명';
        $Statement->receiverCEOName = '공급받는자 대표자 성명';
        $Statement->receiverAddr = '공급받는자 주소';
        $Statement->receiverBizClass = '공급받는자 업종';
        $Statement->receiverBizType = '공급받는자 업태';
        $Statement->receiverContactName = '공급받는자 담당자명';
        $Statement->receiverTEL = '';
        $Statement->receiverHP = '';

        // 팝빌 테스트 환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Statement->receiverEmail = '';

        /************************************************************
         *                       전자명세서 기재정보
         ************************************************************/
        $Statement->supplyCostTotal = '200000';    // 공급가액 합계
        $Statement->taxTotal = '20000';       // 세액 합계
        $Statement->totalAmount = '220000';      // 합계금액 (공급가액 합계+세액합계)
        $Statement->serialNum = '123';       // 기재상 일련번호 항목
        $Statement->remark1 = '비고1';
        $Statement->remark2 = '비고2';
        $Statement->remark3 = '비고3';

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->businessLicenseYN = False;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->bankBookYN = False;

        // 문자 자동전송 여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송(기본값)
        $Statement->smssendYN = False;

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/
        $Statement->detailList = array();
        $Statement->detailList[0] = new StatementDetail();
        $Statement->detailList[0]->serialNum = '1';     //품목 일련번호 1부터 순차 기재
        $Statement->detailList[0]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
        $Statement->detailList[0]->itemName = '품명';
        $Statement->detailList[0]->spec = '규격';
        $Statement->detailList[0]->unit = '단위';
        $Statement->detailList[0]->qty = '1';      //수량
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
        $Statement->detailList[1]->serialNum = '2';     //품목 일련번호 순차기재
        $Statement->detailList[1]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
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
         *   기본양식 추가속성 테이블"을 참조하시기 바랍니다.
         * [https://developers.popbill.com/guide/statement/php/introduction/statement-form#propertybag-table]
         ************************************************************/
        $Statement->propertyBag = array(
            'Balance' => '50000',
            'Deposit' => '100000',
            'CBalance' => '150000'
        );

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->Register($CorpNum, $Statement, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * "임시저장" 상태의 전자명세서를 수정합니다.건의 전자명세서를 [수정]합니다.
     * - https://developers.popbill.com/reference/statement/php/api/issue#Update
     */
    public function Update()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 문서번호
        // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $MgtKey = '20230102-PHP7-005';

        // 전자명세서 객체 생성
        $Statement = new Statement();

        /************************************************************
         *                       전자명세서 정보
         ************************************************************/

        // 기재상 작성일자
        $Statement->writeDate = '20230102';

        // 결제대금 수취여부 (영수, 청구, 없음) 중 기재
        $Statement->purposeType = '청구';

        //  과세형태, (과세, 영세, 면세) 중 기재
        $Statement->taxType = '과세';

        // 맞춤양식코드, 미기재시 기본양식으로 처리
        $Statement->formCode = '';

        // 명세서 종류 코드
        $Statement->itemCode = $itemCode;

        // 전자명세서 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Statement->mgtKey = $MgtKey;

        /************************************************************
         *                         공급자 정보
         ************************************************************/
        $Statement->senderCorpNum = $CorpNum;
        $Statement->senderTaxRegID = '';
        $Statement->senderCorpName = '공급자 상호_수정';
        $Statement->senderCEOName = '공급자 대표자 성명';
        $Statement->senderAddr = ' 공급자 주소';
        $Statement->senderBizClass = '공급자 업종';
        $Statement->senderBizType = '공급자 업태';
        $Statement->senderContactName = '공급자 담당자명';
        $Statement->senderTEL = '';
        $Statement->senderHP = '';
        $Statement->senderEmail = '';

        /************************************************************
         *                         공급받는자 정보
         ************************************************************/
        $Statement->receiverCorpNum = '8888888888';
        $Statement->receiverTaxRegID = '';      // 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
        $Statement->receiverCorpName = '공급받는자 대표자 성명';
        $Statement->receiverCEOName = '공급받는자 대표자 성명';
        $Statement->receiverAddr = '공급받는자 주소';
        $Statement->receiverBizClass = '공급받는자 업종';
        $Statement->receiverBizType = '공급받는자 업태';
        $Statement->receiverContactName = '공급받는자 담당자명';
        $Statement->receiverTEL = '';
        $Statement->receiverHP = '';

        // 팝빌 테스트 환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Statement->receiverEmail = '';

        /************************************************************
         *                       전자명세서 기재정보
         ************************************************************/
        $Statement->supplyCostTotal = '200000';    // 공급가액 합계
        $Statement->taxTotal = '20000';       // 세액 합계
        $Statement->totalAmount = '220000';      // 합계금액 (공급가액 합계+세액합계)
        $Statement->serialNum = '123';       // 기재상 일련번호 항목
        $Statement->remark1 = '비고1';
        $Statement->remark2 = '비고2';
        $Statement->remark3 = '비고3';

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->businessLicenseYN = False;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->bankBookYN = False;

        // 문자 자동전송 여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송(기본값)
        $Statement->smssendYN = False;

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/
        $Statement->detailList = array();
        $Statement->detailList[0] = new StatementDetail();
        $Statement->detailList[0]->serialNum = '1';     //품목 일련번호 1부터 순차 기재
        $Statement->detailList[0]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
        $Statement->detailList[0]->itemName = '품명';
        $Statement->detailList[0]->spec = '규격';
        $Statement->detailList[0]->unit = '단위';
        $Statement->detailList[0]->qty = '1';      //수량
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
        $Statement->detailList[1]->serialNum = '2';     //품목 일련번호 순차기재
        $Statement->detailList[1]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
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
         *   기본양식 추가속성 테이블"을 참조하시기 바랍니다.
         * [https://developers.popbill.com/guide/statement/php/introduction/statement-form#propertybag-table]
         ************************************************************/
        $Statement->propertyBag = array(
            'Balance' => '50000',
            'Deposit' => '100000',
            'CBalance' => '150000'
        );

         // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->Update($CorpNum, $itemCode, $MgtKey, $Statement, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * "임시저장" 상태의 전자명세서를 발행하여, "발행완료" 상태로 처리합니다.
     * - 팝빌 사이트 [전자명세서] > [환경설정] > [전자명세서 관리] 메뉴의 발행시 자동승인 옵션 설정을 통해
     *   전자명세서를 "발행완료" 상태가 아닌 "승인대기" 상태로 발행 처리 할 수 있습니다.
     * - 전자명세서 발행 함수 호출시 수신자에게 발행 안내 메일이 발송됩니다.
     * - https://developers.popbill.com/reference/statement/php/api/issue#Issue
     */
    public function Issue()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 메모
        $Memo = '전자명세서 발행 메모';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 전자명세서 발행 안내메일 제목
        // 미입력시 팝빌에서 지정한 이메일 제목으로 전송
        $emailSubject = null;

        try {
            $result = $this->PopbillStatement->Issue($CorpNum, $itemCode, $MgtKey, $Memo, $UserID, $emailSubject);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 발신자가 발행한 전자명세서를 발행취소합니다.
     * - "발행취소" 상태의 전자명세서를 삭제(Delete API) 함수를 이용하면, 전자명세서 관리를 위해 부여했던 문서번호를 재사용 할 수 있습니다.
     * - https://developers.popbill.com/reference/statement/php/api/issue#Cancel
     */
    public function CancelIssue()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 메모
        $Memo = '전자명세서 발행취소 메모';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->CancelIssue($CorpNum, $itemCode, $MgtKey, $Memo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 삭제 가능한 상태의 전자명세서를 삭제합니다.
     * - 삭제 가능한 상태: "임시저장", "취소", "승인거부", "발행취소"
     * - 전자명세서를 삭제하면 사용된 문서번호(mgtKey)를 재사용할 수 있습니다.
     * - https://developers.popbill.com/reference/statement/php/api/issue#Delete
     */
    public function Delete()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->Delete($CorpNum, $itemCode, $MgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 전자명세서의 1건의 상태 및 요약정보 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/info#GetInfo
     */
    public function GetInfo()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->GetInfo($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Statement/GetInfo', ['StatementInfo' => [$result]]);
    }

    /**
     * 다수건의 전자명세서 상태/요약 정보를 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/info#GetInfos
     */
    public function GetInfos()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 조회할 전자명세서 문서번호 배열, 최대 1000건
        $MgtKeyList = array(
            '20230102-PHP7-001',
            '20230102-PHP7-002'
        );

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $resultList = $this->PopbillStatement->GetInfos($CorpNum, $itemCode, $MgtKeyList, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Statement/GetInfo', ['StatementInfo' => $resultList]);
    }

    /**
     * 전자명세서 1건의 상세정보 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/info#GetDetailInfo
     */
    public function GetDetailInfo()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->GetDetailInfo($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Statement/GetDetailInfo', ['Statement' => $result]);
    }

    /**
     * 검색조건에 해당하는 전자명세서를 조회합니다. (조회기간 단위 : 최대 6개월)
     * - https://developers.popbill.com/reference/statement/php/api/info#Search
     */
    public function Search()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 일자 유형 ("R" , "W" , "I" 중 택 1)
        // └ R = 등록일자 , W = 작성일자 , I = 발행일자
        $DType = 'W';

        // 시작일자
        $SDate = '20230101';

        // 종료일자
        $EDate = '20230131';

        // 전자명세서 상태코드 배열 (2,3번째 자리에 와일드카드(*) 사용 가능)
        // - 미입력시 전체조회
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

        // 조회 검색어(거래처 상호/사업자번호)
        // - 미입력시 전체조회
        $QString = null;

        try {
            $result = $this->PopbillStatement->Search(
                $CorpNum,
                $DType,
                $SDate,
                $EDate,
                $State,
                $ItemCode,
                $Page,
                $PerPage,
                $Order,
                $QString
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Statement/Search', ['Result' => $result]);
    }

    /**
     * 전자명세서의 상태에 대한 변경이력을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/info#GetLogs
     */
    public function GetLogs()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->GetLogs($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetLogs', ['Result' => $result]);
    }

    /**
     * 로그인 상태로 팝빌 사이트의 전자명세서 문서함 메뉴에 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/statement/php/api/info#GetURL
     */
    public function GetURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 임시문서함(TBOX), 발행문서함(SBOX)
        $TOGO = 'TBOX';

        try {
            $url = $this->PopbillStatement->GetURL($CorpNum, $UserID, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자명세서 문서함 팝업 URL", 'value' => $url]);
    }

    /**
     * 전자명세서 1건의 상세 정보 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/statement/php/api/view#GetPopUpURL
     */
    public function GetPopUpURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 문서번호
        $MgtKey = '20230103-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetPopUpURL($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자명세서 내용 보기 팝업 URL", 'value' => $url]);
    }

    /**
     * 전자명세서 1건의 상세 정보 페이지(사이트 상단, 좌측 메뉴 및 버튼 제외)의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/statement/php/api/view#GetViewURL
     */
    public function GetViewURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetViewURL($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자명세서 보기 팝업 URL", 'value' => $url]);
    }

    /**
     * 전자명세서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환하며, 페이지내에서 인쇄 설정값을 "공급자" / "공급받는자" / "공급자+공급받는자"용 중 하나로 지정할 수 있습니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - 전자명세서의 공급자는 "발신자", 공급받는자는 "수신자"를 나타내는 용어입니다.
     * - https://developers.popbill.com/reference/statement/php/api/view#GetPrintURL
     */
    public function GetPrintURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetPrintURL($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자명세서 인쇄 팝업 URL", 'value' => $url]);
    }

    /**
     * "공급받는자" 용 전자명세서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - 전자명세서의 공급받는자는 "수신자"를 나타내는 용어입니다.
     * - https://developers.popbill.com/reference/statement/php/api/view#GetEPrintURL
     */
    public function GetEPrintURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetEPrintURL($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "전자명세서 인쇄(공급받는자용) 팝업 URL", 'value' => $url]);
    }

    /**
     * 다수건의 전자명세서를 인쇄하기 위한 페이지의 팝업 URL을 반환합니다. (최대 100건)
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/statement/php/api/view#GetMassPrintURL
     */
    public function GetMassPrintURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호 배열, 최대 100건
        $MgtKeyList = array(
            '20230102-PHP7-001',
            '20230102-PHP7-002'
        );

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetMassPrintURL($CorpNum, $itemCode, $MgtKeyList, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자명세서 인쇄(대량) 팝업 URL", 'value' => $url]);
    }

    /**
     * 전자명세서 안내메일의 상세보기 링크 URL을 반환합니다.
     * - 함수 호출로 반환 받은 URL에는 유효시간이 없습니다.
     * - https://developers.popbill.com/reference/statement/php/api/view#GetMailURL
     */
    public function GetMailURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetMailURL($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자명세서 공급받는자 메일 링크 URL", 'value' => $url]);
    }

    /**
     * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#GetAccessURL
     */
    public function GetAccessURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetAccessURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL", 'value' => $url]);
    }

    /**
     * 전자명세서에 첨부할 인감, 사업자등록증, 통장사본을 등록하는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#GetSealURL
     */
    public function GetSealURL()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetSealURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "인감 및 첨부문서 등록 URL", 'value' => $url]);
    }

    /**
     * "임시저장" 상태의 명세서에 1개의 파일을 첨부합니다. (최대 5개)
     * - https://developers.popbill.com/reference/statement/php/api/etc#AttachFile
     */
    public function AttachFile()
    {

        // 팝빌회원 사업자번호, "-" 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 첨부파일 경로, 해당 파일에 읽기 권한이 설정되어 있어야 합니다.
        $FilePath = '/image.jpg';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 첨부파일명
        $DisplayName = 'DisplayName.jpg';

        try {
            $result = $this->PopbillStatement->AttachFile($CorpNum, $itemCode, $MgtKey, $FilePath, $UserID, $DisplayName);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * "임시저장" 상태의 전자명세서에 첨부된 1개의 파일을 삭제합니다.
     * - 파일을 식별하는 파일아이디는 첨부파일 목록(GetFiles API) 의 응답항목 중 파일아이디(AttachedFile) 값을 통해 확인할 수 있습니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#DeleteFile
     */
    public function DeleteFile()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 팝빌이 첨부파일 관리를 위해 할당하는 식별번호
        // 첨부파일 목록 확인(getFiles API) 함수의 리턴 값 중 attachedFile 필드값 기재.
        $FileID = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->DeleteFile($CorpNum, $itemCode, $MgtKey, $FileID, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 전자명세서에 첨부된 파일목록을 확인합니다.
     * - 응답항목 중 파일아이디(AttachedFile) 항목은 파일삭제(DeleteFile API) 호출시 이용할 수 있습니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#GetFiles
     */
    public function GetFiles()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->GetFiles($CorpNum, $itemCode, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('GetFiles', ['Result' => $result]);
    }

    /**
     * "승인대기", "발행완료" 상태의 전자명세서와 관련된 발행 안내 메일을 재전송 합니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#SendEmail
     */
    public function SendEmail()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 수신자 이메일주소
        $receiver = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->SendEmail($CorpNum, $itemCode, $MgtKey, $receiver, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 전자명세서와 관련된 안내 SMS(단문) 문자를 재전송하는 함수로, 팝빌 사이트 [문자·팩스] > [문자] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
     * - 메시지는 최대 90byte까지 입력 가능하고, 초과한 내용은 자동으로 삭제되어 전송합니다. (한글 최대 45자)
     * - 함수 호출시 포인트가 과금됩니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#SendSMS
     */
    public function SendSMS()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 발신번호
        $Sender = '';

        // 수신번호
        $receiver = '';

        // 메시지 내용, 90byte 초과시 길이가 조정되어 전송됨
        $contents = '전자명세서 문자메시지 전송 테스트입니다.';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->SendSMS(
                $CorpNum,
                $itemCode,
                $MgtKey,
                $Sender,
                $receiver,
                $contents,
                $UserID
            );
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 전자명세서를 팩스로 전송하는 함수로, 팝빌 사이트 [문자·팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
     * - 함수 호출시 포인트가 과금됩니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#SendFAX
     */
    public function SendFAX()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 발신번호
        $Sender = '';

        // 수신팩스번호
        $receiver = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->SendFAX($CorpNum, $itemCode, $MgtKey, $Sender, $receiver, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 전자명세서를 팩스로 전송하는 함수로, 팝빌에 데이터를 저장하는 과정이 없습니다.
     * - 팝빌 사이트 [문자·팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
     * - 함수 호출시 포인트가 과금됩니다.
     * - 선팩스 전송 요청시 작성한 문서번호는 팩스전송 파일명으로 사용됩니다.
     * - 팩스 전송결과를 확인하기 위해서는 선팩스 전송 요청 시 반환받은 접수번호를 이용하여 팩스 API의 전송결과 확인 (GetFaxDetail) API를 이용하면 됩니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#FAXSend
     */
    public function FAXSend()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $MgtKey = '20230102-PHP7-003';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 전자명세서 객체 생성
        $Statement = new Statement();

        /************************************************************
         *                       전자명세서 정보
         ************************************************************/
        // 기재상 작성일자
        $Statement->writeDate = '20230102';

        // 결제대금 수취여부 (영수, 청구, 없음) 중 기재
        $Statement->purposeType = '영수';

        //  과세형태, (과세, 영세, 면세) 중 기재
        $Statement->taxType = '과세';

        // 맞춤양식코드, 미기재시 기본양식으로 처리
        $Statement->formCode = '';

        // 명세서 종류 코드
        $Statement->itemCode = $itemCode;

        // 전자명세서 문서번호
        $Statement->mgtKey = $MgtKey;

        /************************************************************
         *                         공급자 정보
         ************************************************************/
        $Statement->senderCorpNum = $CorpNum;
        $Statement->senderTaxRegID = '';
        $Statement->senderCorpName = '공급자 상호';
        $Statement->senderCEOName = '공급자 대표자 성명';
        $Statement->senderAddr = ' 공급자 주소';
        $Statement->senderBizClass = '공급자 업종';
        $Statement->senderBizType = '공급자 업태';
        $Statement->senderContactName = '공급자 담당자명';
        $Statement->senderTEL = '';
        $Statement->senderHP = '';
        $Statement->senderEmail = '';

        /************************************************************
         *                         공급받는자 정보
         ************************************************************/
        $Statement->receiverCorpNum = '8888888888';
        $Statement->receiverTaxRegID = '';      // 공급받는자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리
        $Statement->receiverCorpName = '공급받는자 대표자 성명';
        $Statement->receiverCEOName = '공급받는자 대표자 성명';
        $Statement->receiverAddr = '공급받는자 주소';
        $Statement->receiverBizClass = '공급받는자 업종';
        $Statement->receiverBizType = '공급받는자 업태';
        $Statement->receiverContactName = '공급받는자 담당자명';
        $Statement->receiverTEL = '';
        $Statement->receiverHP = '';

        // 팝빌 테스트 환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Statement->receiverEmail = '';

        /************************************************************
         *                       전자명세서 기재정보
         ************************************************************/
        $Statement->supplyCostTotal = '200000';    // 공급가액 합계
        $Statement->taxTotal = '20000';       // 세액 합계
        $Statement->totalAmount = '220000';      // 합계금액 (공급가액 합계+세액합계)
        $Statement->serialNum = '123';       // 기재상 일련번호 항목
        $Statement->remark1 = '비고1';
        $Statement->remark2 = '비고2';
        $Statement->remark3 = '비고3';

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->businessLicenseYN = False;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Statement->bankBookYN = False;

        // 문자 자동전송 여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송(기본값)
        $Statement->smssendYN = False;

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/
        $Statement->detailList = array();
        $Statement->detailList[0] = new StatementDetail();
        $Statement->detailList[0]->serialNum = '1';     //품목 일련번호 1부터 순차 기재
        $Statement->detailList[0]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
        $Statement->detailList[0]->itemName = '품명';
        $Statement->detailList[0]->spec = '규격';
        $Statement->detailList[0]->unit = '단위';
        $Statement->detailList[0]->qty = '1';      //수량
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
        $Statement->detailList[1]->serialNum = '2';     //품목 일련번호 순차기재
        $Statement->detailList[1]->purchaseDT = '20230102';   //거래일자 yyyyMMdd
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
         *   기본양식 추가속성 테이블"을 참조하시기 바랍니다.
         * [https://developers.popbill.com/guide/statement/php/introduction/statement-form#propertybag-table]
         ************************************************************/
        $Statement->propertyBag = array(
            'Balance' => '50000',
            'Deposit' => '100000',
            'CBalance' => '150000'
        );

        // 팩스전송 발신번호
        $sendNum = '';

        // 팩스수신번호
        $receiveNum = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillStatement->FAXSend($CorpNum, $Statement, $sendNum, $receiveNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "선팩스전송 접수번호(receiptNum)", 'value' => $receiptNum]);
    }

    /**
     * 하나의 전자명세서에 다른 전자명세서를 첨부합니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#AttachStatement
     */
    public function AttachStatement()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 첨부할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $subItemCode = '121';

        // 첨부할 명세서 문서번호
        $subMgtKey = '20230102-PHP7-002';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->AttachStatement($CorpNum, $itemCode, $MgtKey, $subItemCode, $subMgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 하나의 전자명세서에 첨부된 다른 전자명세서를 해제합니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#DetachStatement
     */
    public function DetachStatement()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 첨부해제할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $subItemCode = '121';

        // 첨부해제할 명세서 문서번호
        $subMgtKey = '20230102-PHP7-002';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->DetachStatement($CorpNum, $itemCode, $MgtKey, $subItemCode, $subMgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 전자명세서 관련 메일 항목에 대한 발송설정을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#ListEmailConfig
     */
    public function ListEmailConfig()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->ListEmailConfig($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Statement/ListEmailConfig', ['Result' => $result]);
    }

    /**
     * 전자명세서 관련 메일 항목에 대한 발송설정을 수정합니다.
     * - https://developers.popbill.com/reference/statement/php/api/etc#UpdateEmailConfig
     *
     * 메일전송유형
     * - SMT_ISSUE : 공급받는자에게 전자명세서가 발행 되었음을 알려주는 메일입니다.
     * - SMT_ACCEPT : 공급자에게 전자명세서가 승인 되었음을 알려주는 메일입니다.
     * - SMT_DENY : 공급자에게 전자명세서가 거부 되었음을 알려주는 메일입니다.
     * - SMT_CANCEL : 공급받는자에게 전자명세서가 취소 되었음을 알려주는 메일입니다.
     * - SMT_CANCEL_ISSUE : 공급받는자에게 전자명세서가 발행취소 되었음을 알려주는 메일입니다.
     */
    public function UpdateEmailConfig()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 메일 전송 유형
        $emailType = 'SMT_ISSUE';

        // 전송 여부 (True = 전송, False = 미전송)
        $sendYN = True;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->UpdateEmailConfig($CorpNum, $emailType, $sendYN, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetBalance
     */
    public function GetBalance()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillStatement->GetBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetUseHistory
     */
    public function GetUseHistory()
    {

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

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
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->GetUseHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetPaymentHistory
     */
    public function GetPaymentHistory()
    {

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20230101";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20230131";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->GetPaymentHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 환불신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetRefundHistory
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
            $result = $this->PopbillStatement->GetRefundHistory($CorpNum, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트를 환불 신청합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#Refund
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
            $result = $this->PopbillStatement->Refund($CorpNum, $RefundForm, $UserID);
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
     * - https://developers.popbill.com/reference/statement/php/api/point#PaymentRequest
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
            $result = $this->PopbillStatement->PaymentRequest($CorpNum, $PaymentForm, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentResponse', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 무통장 입금신청내역 1건을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetSettleResult
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
            $result = $this->PopbillStatement->GetSettleResult($CorpNum, $SettleCode, $UserID);
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
     * - https://developers.popbill.com/reference/statement/php/api/point#GetChargeURL
     */
    public function GetChargeURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetChargeURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/statement/php/api/point#GetPaymentURL
     */
    public function GetPaymentURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetPaymentURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/statement/php/api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillStatement->GetUseHistoryURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 포인트 사용내역 팝업 URL", 'value' => $url]);
    }

    /**
     * 파트너의 잔여포인트를 확인합니다.
     * - 과금방식이 연동과금인 경우 연동회원 잔여포인트 확인(GetBalance API) 함수를 이용하시기 바랍니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetPartnerBalance
     */
    public function GetPartnerBalance()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillStatement->GetPartnerBalance($CorpNum);
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
     * - https://developers.popbill.com/reference/statement/php/api/point#GetPartnerURL
     */
    public function GetPartnerURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillStatement->GetPartnerURL($CorpNum, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 전자명세서 발행시 과금되는 포인트 단가를 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetUnitCost
     */
    public function GetUnitCost()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';
        try {
            $unitCost = $this->PopbillStatement->GetUnitCost($CorpNum, $itemCode);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자명세서 발행단가", 'value' => $unitCost]);
    }

    /**
     * 팝빌 전자명세서 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetChargeInfo
     */
    public function GetChargeInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $itemCode = '121';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->GetChargeInfo($CorpNum, $itemCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/member#CheckIsMember
     */
    public function CheckIsMember()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 연동신청 시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillStatement->CheckIsMember($CorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/statement/php/api/member#CheckID
     */
    public function CheckID()
    {

        // 조회할 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->CheckID($UserID);
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
     * - https://developers.popbill.com/reference/statement/php/api/member#JoinMember
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
            $result = $this->PopbillStatement->JoinMember($JoinForm);
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
     * - https://developers.popbill.com/reference/statement/php/api/member#GetCorpInfo
     */
    public function GetCorpInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillStatement->GetCorpInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다.
     * - https://developers.popbill.com/reference/statement/php/api/member#UpdateCorpInfo
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

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result =  $this->PopbillStatement->UpdateCorpInfo($CorpNum, $CorpInfo, $UserID);
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
     * - https://developers.popbill.com/reference/statement/php/api/member#RegistContact
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

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillStatement->RegistContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/statement/php/api/member#GetContactInfo
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
            $ContactInfo = $this->PopbillStatement->GetContactInfo($CorpNum, $ContactID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/member#ListContact
     */
    public function ListContact()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactList = $this->PopbillStatement->ListContact($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://developers.popbill.com/reference/statement/php/api/member#UpdateContact
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
            $result = $this->PopbillStatement->UpdateContact($CorpNum, $ContactInfo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 가입된 연동회원의 탈퇴를 요청합니다.
     * 회원탈퇴 신청과 동시에 팝빌의 모든 서비스 이용이 불가하며, 관리자를 포함한 모든 담당자 계정도 일괄탈퇴 됩니다.
     * 회원탈퇴로 삭제된 데이터는 복원이 불가능합니다.
     * 관리자 계정만 사용 가능합니다.
     * - https://developers.popbill.com/reference/statement/php/api/member#QuitMember
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
            $result = $this->PopbillStatement->QuitMember($CorpNum, $QuitReason, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PResponse', ['code' => $result->code, 'message' => $result->message]);
    }

    /**
     * 환불 가능한 포인트를 확인합니다. (보너스 포인트는 환불가능포인트에서 제외됩니다.)
     * - https://developers.popbill.com/reference/statement/php/api/point#GetRefundableBalance
     */
    public function GetRefundableBalance()
    {

        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $refundableBalance = $this->PopbillStatement->GetRefundableBalance($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundableBalance', ['refundableBalance' => $refundableBalance]);
    }

    /**
     * 포인트 환불에 대한 상세정보 1건을 확인합니다.
     * - https://developers.popbill.com/reference/statement/php/api/point#GetRefundInfo
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
            $result = $this->PopbillStatement->GetRefundInfo($CorpNum, $RefundCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundInfo', ['result' => $result]);
    }
}
