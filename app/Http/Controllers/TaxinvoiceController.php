<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillTaxinvoice;
use Linkhub\Popbill\TIENumMgtKeyType;
use Linkhub\Popbill\Taxinvoice;
use Linkhub\Popbill\TaxinvoiceDetail;
use Linkhub\Popbill\TaxinvoiceAddContact;
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class TaxinvoiceController extends Controller
{
    public function __construct()
    {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 세금계산서 서비스 클래스 초기화`
        $this->PopbillTaxinvoice = new PopbillTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정값, true-개발용, false-상업용
        $this->PopbillTaxinvoice->IsTest(config('popbill.IsTest'));

        // 인증토큰의 IP제한기능 사용여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillTaxinvoice->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 팝빌 API 서비스 고정 IP 사용여부, true-사용, false-미사용, 기본값(false)
        $this->PopbillTaxinvoice->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬서버 시간 사용 여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillTaxinvoice->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request)
    {
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 파트너가 세금계산서 관리 목적으로 할당하는 문서번호의 사용여부를 확인합니다.
     * - 이미 사용 중인 문서번호는 중복 사용이 불가하고, 세금계산서가 삭제된 경우에만 문서번호의 재사용이 가능합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#CheckMgtKeyInUse
     */
    public function CheckMgtKeyInUse()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 세금계산서 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $MgtKey = '20230102-PHP7-001';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        try {
            $result = $this->PopbillTaxinvoice->CheckMgtKeyInUse($CorpNum, $MgtKeyType, $MgtKey);
            $result ? $result = '사용중' : $result = '미사용중';
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "문서번호 사용여부 =>" . $MgtKey . "", 'value' => $result]);
    }

    /**
     * 작성된 세금계산서 데이터를 팝빌에 저장과 동시에 발행(전자서명)하여 "발행완료" 상태로 처리합니다.
     * - 세금계산서 국세청 전송 정책 [https://developers.popbill.com/guide/taxinvoice/php/introduction/policy-of-send-to-nts]
     * - "발행완료"된 전자세금계산서는 국세청 전송 이전에 발행취소(CancelIssue API) 함수로 국세청 신고 대상에서 제외할 수 있습니다.
     * - 임시저장(Register API) 함수와 발행(Issue API) 함수를 한 번의 프로세스로 처리합니다.
     * - 세금계산서 발행을 위해서 공급자의 인증서가 팝빌 인증서버에 사전등록 되어야 합니다.
     *   └ 위수탁발행의 경우, 수탁자의 인증서 등록이 필요합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#RegistIssue
     */
    public function RegistIssue()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $invoicerMgtKey = '20230102-PHP7-001';

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 즉시발행 메모
        $Memo = '즉시발행 메모';

        // 안내메일 제목, 미기재시 기본제목으로 전송
        $emailSubject = '';

        // 거래명세서 동시작성여부 (true / false 중 택 1)
        // └ true = 사용 , false = 미사용
        // - 미입력 시 기본값 false 처리
        $writeSpecification = false;

        // {writeSpecification} = true인 경우, 거래명세서 문서번호 할당
        // - 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = null;


        /************************************************************
         *                        세금계산서 정보
         ************************************************************/
        // 세금계산서 객체 생성
        $Taxinvoice = new Taxinvoice();

        // 작성일자, 형식(yyyyMMdd) 예)20150101
        $Taxinvoice->writeDate = '20230102';

        // 발행유형, {정발행, 역발행, 위수탁} 중 기재
        $Taxinvoice->issueType = '정발행';

        // 과금방향, {정과금, 역과금} 중 기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -'역과금'은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = '정과금';

        // {영수, 청구, 없음} 중 기재
        $Taxinvoice->purposeType = '영수';

        // 과세형태, {과세, 영세, 면세} 중 기재
        $Taxinvoice->taxType = '과세';


        /************************************************************
         *                         공급자 정보
         ************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoicerTaxRegID = '';

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = '공급자상호';

        // 공급자 문서번호
        // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = $invoicerMgtKey;

        // 공급자 대표자성명
        $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

        // 공급자 주소
        $Taxinvoice->invoicerAddr = '공급자 주소';

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = '공급자 종목';

        // 공급자 업태
        $Taxinvoice->invoicerBizType = '공급자 업태';

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = '공급자 담당자성명';

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = 'test@test.com';

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = '070-7070-0707';

        // 공급자 휴대폰 번호
        $Taxinvoice->invoicerHP = '010-000-2222';

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /************************************************************
         *                      공급받는자 정보
         ************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = '사업자';

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = '8888888888';

        // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoiceeTaxRegID = '';

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

        // [역발행시 필수] 공급받는자 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = '';

        // 공급받는자 대표자성명
        $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = '공급받는자 주소';

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = '공급받는자 업태';

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = 'test@invoicee.com';

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = '070-111-222';

        // 공급받는자 담당자 휴대폰 번호
        $Taxinvoice->invoiceeHP1 = '010-1111-2212';

        /************************************************************
         *                       세금계산서 기재정보
         ************************************************************/

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = '200000';

        // 세액 합계
        $Taxinvoice->taxTotal = '20000';

        // 합계금액, (공급가액 합계 + 세액 합계)
        $Taxinvoice->totalAmount = '220000';

        // 기재상 '일련번호'항목
        $Taxinvoice->serialNum = '';

        // 기재상 '현금'항목
        $Taxinvoice->cash = '';

        // 기재상 '수표'항목
        $Taxinvoice->chkBill = '';

        // 기재상 '어음'항목
        $Taxinvoice->note = '';

        // 기재상 '외상'항목
        $Taxinvoice->credit = '';

        // 비고
        // {invoiceeType}이 "외국인" 이면 remark1 필수
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = '비고1';
        $Taxinvoice->remark2 = '비고2';
        $Taxinvoice->remark3 = '비고3';

        // 기재상 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 기재상 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /************************************************************
         *                     수정 세금계산서 기재정보
         * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
         * - [참고] 수정세금계산서 작성방법 안내 - https://developers.popbill.com/guide/taxinvoice/php/introduction/modified-taxinvoice
         ************************************************************/

        // [수정세금계산서 작성시 필수] 수정사유코드, 수정사유에 따라 1~6중 선택기재
        // $Taxinvoice->modifyCode = '';

        // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
        // $Taxinvoice->orgNTSConfirmNum = '';

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/

        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[0]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[0]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[0]->spec = '';                   // 규격
        $Taxinvoice->detailList[0]->qty = '';                    // 수량
        $Taxinvoice->detailList[0]->unitCost = '';               // 단가
        $Taxinvoice->detailList[0]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[0]->tax = '10000';               // 세액
        $Taxinvoice->detailList[0]->remark = '';                 // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[1]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[1]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[1]->spec = '';                   // 규격
        $Taxinvoice->detailList[1]->qty = '';                    // 수량
        $Taxinvoice->detailList[1]->unitCost = '';               // 단가
        $Taxinvoice->detailList[1]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[1]->tax = '10000';               // 세액
        $Taxinvoice->detailList[1]->remark = '';                 // 비고

        /************************************************************
         *                      추가담당자 정보
         * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
         * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
         ************************************************************/

        $Taxinvoice->addContactList = array();

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[0]->serialNum = 1;              // 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[0]->email = 'test2@test.com';                 // 이메일주소
        $Taxinvoice->addContactList[0]->contactName = '팝빌담당자'; // 담당자명

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[1]->serialNum = 2;              // 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[1]->email = 'test2@test.com';                 // 이메일주소
        $Taxinvoice->addContactList[1]->contactName = '링크허브';   // 담당자명

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    /**
     * 최대 100건의 세금계산서 발행을 한번의 요청으로 접수합니다.
     * - 세금계산서 발행을 위해서 공급자의 인증서가 팝빌 인증서버에 사전등록 되어야 합니다.
     *   └ 위수탁발행의 경우, 수탁자의 인증서 등록이 필요합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#BulkSubmit
     */
    public function BulkSubmit()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 제출 아이디 ,최대 36자리 영문, 숫자, '-' 조합으로 구성
        $SubmitID = '20230102-PHP7-BULK';

        // 지연발행 강제 여부
        $ForceIssue = false;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 세금계산서 객체정보 배열
        $TaxinvoiceList = array();

        for ($i = 0; $i < 100; $i++) {
            /************************************************************
             *                        세금계산서 정보
             ************************************************************/
            // 세금계산서 객체 생성
            $Taxinvoice = new Taxinvoice();

            // 작성일자, 형식(yyyyMMdd) 예)20150101
            $Taxinvoice->writeDate = '20230102';

            // 발행유형, {정발행, 역발행, 위수탁} 중 기재
            $Taxinvoice->issueType = '정발행';

            // 과금방향, {정과금, 역과금} 중 기재
            // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
            // -'역과금'은 역발행 세금계산서 발행 시에만 이용가능
            $Taxinvoice->chargeDirection = '정과금';

            // [영수, 청구, 없음] 중 기재
            $Taxinvoice->purposeType = '영수';

            // 과세형태, {과세, 영세, 면세} 중 기재
            $Taxinvoice->taxType = '과세';

            /************************************************************
             *                         공급자 정보
             ************************************************************/

            // 공급자 사업자번호
            $Taxinvoice->invoicerCorpNum = '1234567890';

            // 공급자 종사업장 식별번호, 4자리 숫자 문자열
            $Taxinvoice->invoicerTaxRegID = '';

            // 공급자 상호
            $Taxinvoice->invoicerCorpName = 'BulkTEST';

            // 공급자 문서번호
            // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
            $Taxinvoice->invoicerMgtKey = $SubmitID . '-' . $i;

            // 공급자 대표자성명
            $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

            // 공급자 주소
            $Taxinvoice->invoicerAddr = '공급자 주소';

            // 공급자 종목
            $Taxinvoice->invoicerBizClass = '공급자 종목';

            // 공급자 업태
            $Taxinvoice->invoicerBizType = '공급자 업태';

            // 공급자 담당자 성명
            $Taxinvoice->invoicerContactName = '공급자 담당자성명';

            // 공급자 담당자 메일주소
            $Taxinvoice->invoicerEmail = '';

            // 공급자 담당자 연락처
            $Taxinvoice->invoicerTEL = '';

            // 공급자 휴대폰 번호
            $Taxinvoice->invoicerHP = '';

            // 발행 안내 문자 전송여부 (true / false 중 택 1)
            // └ true = 전송 , false = 미전송
            // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
            // - 전송 시 포인트 차감되며, 전송실패시 환불처리
            $Taxinvoice->invoicerSMSSendYN = false;

            /************************************************************
             *                      공급받는자 정보
             ************************************************************/

            // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
            $Taxinvoice->invoiceeType = '사업자';

            // 공급받는자 사업자번호
            // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
            // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
            // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
            $Taxinvoice->invoiceeCorpNum = '8888888888';

            // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
            $Taxinvoice->invoiceeTaxRegID = '';

            // 공급받는자 상호
            $Taxinvoice->invoiceeCorpName = 'BulkTEST';

            // [역발행시 필수] 공급받는자 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
            $Taxinvoice->invoiceeMgtKey = '';

            // 공급받는자 대표자성명
            $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

            // 공급받는자 주소
            $Taxinvoice->invoiceeAddr = '공급받는자 주소';

            // 공급받는자 업태
            $Taxinvoice->invoiceeBizType = '공급받는자 업태';

            // 공급받는자 종목
            $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

            // 공급받는자 담당자 성명
            $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

            // 공급받는자 담당자 메일주소
            // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
            // 실제 거래처의 메일주소가 기재되지 않도록 주의
            $Taxinvoice->invoiceeEmail1 = '';

            // 공급받는자 담당자 연락처
            $Taxinvoice->invoiceeTEL1 = '';

            // 공급받는자 담당자 휴대폰 번호
            $Taxinvoice->invoiceeHP1 = '';


            /************************************************************
             *                       세금계산서 기재정보
             ************************************************************/

            // 공급가액 합계
            $Taxinvoice->supplyCostTotal = '200000';

            // 세액 합계
            $Taxinvoice->taxTotal = '20000';

            // 합계금액, (공급가액 합계 + 세액 합계)
            $Taxinvoice->totalAmount = '220000';

            // 기재상 '일련번호'항목
            $Taxinvoice->serialNum = '';

            // 기재상 '현금'항목
            $Taxinvoice->cash = '';

            // 기재상 '수표'항목
            $Taxinvoice->chkBill = '';
            // 기재상 '어음'항목
            $Taxinvoice->note = '';

            // 기재상 '외상'항목
            $Taxinvoice->credit = '';

            // 비고
            // {invoiceeType}이 "외국인" 이면 remark1 필수
            // - 외국인 등록번호 또는 여권번호 입력
            $Taxinvoice->remark1 = '비고1';
            $Taxinvoice->remark2 = '비고2';
            $Taxinvoice->remark3 = '비고3';

            // 기재상 '권' 항목, 최대값 32767
            $Taxinvoice->kwon = 1;

            // 기재상 '호' 항목, 최대값 32767
            $Taxinvoice->ho = 1;

            // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
            // └ true = 첨부 , false = 미첨부(기본값)
            // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
            $Taxinvoice->businessLicenseYN = false;

            // 통장사본 이미지 첨부여부 (true / false 중 택 1)
            // └ true = 첨부 , false = 미첨부(기본값)
            // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
            $Taxinvoice->bankBookYN = false;

            /************************************************************
             *                     수정 세금계산서 기재정보
             * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
             * - [참고] 수정세금계산서 작성방법 안내 - https://developers.popbill.com/guide/taxinvoice/php/introduction/modified-taxinvoice
             ************************************************************/

            // [수정세금계산서 작성시 필수] 수정사유코드, 수정사유에 따라 1~6중 선택기재
            // $Taxinvoice->modifyCode = '';

            // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
            // $Taxinvoice->orgNTSConfirmNum = '';

            /************************************************************
             *                       상세항목(품목) 정보
             ************************************************************/

            $Taxinvoice->detailList = array();

            $Taxinvoice->detailList[] = new TaxinvoiceDetail();
            $Taxinvoice->detailList[0]->serialNum = 1;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
            $Taxinvoice->detailList[0]->purchaseDT = '20230102';     // 거래일자
            $Taxinvoice->detailList[0]->itemName = '품목명1번';      // 품명
            $Taxinvoice->detailList[0]->spec = '';                   // 규격
            $Taxinvoice->detailList[0]->qty = '';                    // 수량
            $Taxinvoice->detailList[0]->unitCost = '';               // 단가
            $Taxinvoice->detailList[0]->supplyCost = '100000';       // 공급가액
            $Taxinvoice->detailList[0]->tax = '10000';               // 세액
            $Taxinvoice->detailList[0]->remark = '';                 // 비고

            $Taxinvoice->detailList[] = new TaxinvoiceDetail();
            $Taxinvoice->detailList[1]->serialNum = 2;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
            $Taxinvoice->detailList[1]->purchaseDT = '20230102';     // 거래일자
            $Taxinvoice->detailList[1]->itemName = '품목명1번';      // 품명
            $Taxinvoice->detailList[1]->spec = '';                   // 규격
            $Taxinvoice->detailList[1]->qty = '';                    // 수량
            $Taxinvoice->detailList[1]->unitCost = '';               // 단가
            $Taxinvoice->detailList[1]->supplyCost = '100000';       // 공급가액
            $Taxinvoice->detailList[1]->tax = '10000';               // 세액
            $Taxinvoice->detailList[1]->remark = '';                 // 비고

            /************************************************************
             *                      추가담당자 정보
             * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
             * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
             ************************************************************/

            $Taxinvoice->addContactList = array();

            $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
            $Taxinvoice->addContactList[0]->serialNum = 1;              // 일련번호 1부터 순차기재
            $Taxinvoice->addContactList[0]->email = '';                 // 이메일주소
            $Taxinvoice->addContactList[0]->contactName = '팝빌담당자'; // 담당자명

            $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
            $Taxinvoice->addContactList[1]->serialNum = 2;              // 일련번호 1부터 순차기재
            $Taxinvoice->addContactList[1]->email = '';                 // 이메일주소
            $Taxinvoice->addContactList[1]->contactName = '링크허브';   // 담당자명

            // 세금계산서 추가
            $TaxinvoiceList[] = $Taxinvoice;
        }

        try {
            $result = $this->PopbillTaxinvoice->BulkSubmit($CorpNum, $SubmitID, $TaxinvoiceList, $ForceIssue, $UserID);
            $code = $result->code;
            $message = $result->message;
            $receiptID = $result->receiptID;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('/PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('/PResponse', ['code' => $code, 'message' => $message, 'receiptID' => $receiptID]);
    }

    /*
     * 접수시 기재한 SubmitID를 사용하여 세금계산서 접수결과를 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#GetBulkResult
     */
    public function getBulkResult()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 초대량 발행 접수시 기재한 제출 아이디
        $SubmitID = '20230102-PHP7-BULK';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->GetBulkResult($CorpNum, $SubmitID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('/PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('/Taxinvoice/GetBulkResult', ['Result' => $result]);
    }

    /**
     * 작성된 세금계산서 데이터를 팝빌에 저장합니다.
     * - "임시저장" 상태의 세금계산서는 발행(Issue) 함수를 호출하여 "발행완료" 처리한 경우에만 국세청으로 전송됩니다.
     * - 정발행 시 임시저장(Register)과 발행(Issue)을 한번의 호출로 처리하는 즉시발행(RegistIssue API) 프로세스 연동을 권장합니다.
     * - 역발행 시 임시저장(Register)과 역발행요청(Request)을 한번의 호출로 처리하는 즉시요청(RegistRequest API) 프로세스 연동을 권장합니다.
     * - 세금계산서 파일첨부 기능을 구현하는 경우, 임시저장(Register API) -> 파일첨부(AttachFile API) -> 발행(Issue API) 함수를 차례로 호출합니다.
     * - 역발행 세금계산서를 저장하는 경우, 객체 'Taxinvoice'의 변수 'chargeDirection' 값을 통해 과금 주체를 지정할 수 있습니다.
     *   └ 정과금 : 공급자 과금 , 역과금 : 공급받는자 과금
     * - 임시저장된 세금계산서는 팝빌 사이트 '임시문서함'에서 확인 가능합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#Register
     */
    public function Register()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 세금계산서 문서번호
        // - 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $invoicerMgtKey = '20230102-PHP7-002';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        /************************************************************
         *                        세금계산서 정보
         ************************************************************/

        // 세금계산서 객체 생성
        $Taxinvoice = new Taxinvoice();

        // 작성일자, 형식(yyyyMMdd) 예)20150101
        $Taxinvoice->writeDate = '20230102';

        // 발행유형, {정발행, 역발행, 위수탁} 중 기재
        $Taxinvoice->issueType = '정발행';

        // 과금방향, {정과금, 역과금} 중 기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -'역과금'은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = '정과금';

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = '영수';

        // 과세형태, {과세, 영세, 면세} 중 기재
        $Taxinvoice->taxType = '과세';

        /************************************************************
         *                         공급자 정보
         ************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoicerTaxRegID = '';

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = '공급자상호';

        // 공급자 문서번호
        // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = $invoicerMgtKey;

        // 공급자 대표자성명
        $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

        // 공급자 주소
        $Taxinvoice->invoicerAddr = '공급자 주소';

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = '공급자 종목';

        // 공급자 업태
        $Taxinvoice->invoicerBizType = '공급자 업태';

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = '공급자 담당자성명';

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = '';

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = '';

        // 공급자 휴대폰 번호
        $Taxinvoice->invoicerHP = '';

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /************************************************************
         *                      공급받는자 정보
         ************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = '사업자';

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = '8888888888';

        // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoiceeTaxRegID = '';

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

        // [역발행시 필수] 공급받는자 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = '';

        // 공급받는자 대표자성명
        $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = '공급받는자 주소';

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = '공급받는자 업태';

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = '';

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = '';

        // 공급받는자 담당자 휴대폰 번호
        $Taxinvoice->invoiceeHP1 = '';

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /************************************************************
         *                       세금계산서 기재정보
         ************************************************************/

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = '200000';

        // 세액 합계
        $Taxinvoice->taxTotal = '20000';

        // 합계금액, (공급가액 합계 + 세액 합계)
        $Taxinvoice->totalAmount = '220000';

        // 기재상 '일련번호'항목
        $Taxinvoice->serialNum = '';

        // 기재상 '현금'항목
        $Taxinvoice->cash = '';

        // 기재상 '수표'항목
        $Taxinvoice->chkBill = '';

        // 기재상 '어음'항목
        $Taxinvoice->note = '';

        // 기재상 '외상'항목
        $Taxinvoice->credit = '';

        // 비고
        // {invoiceeType}이 "외국인" 이면 remark1 필수
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = '비고1';
        $Taxinvoice->remark2 = '비고2';
        $Taxinvoice->remark3 = '비고3';

        // 기재상 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 기재상 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /************************************************************
         *                     수정 세금계산서 기재정보
         * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
         * - [참고] 수정세금계산서 작성방법 안내 - https://developers.popbill.com/guide/taxinvoice/php/introduction/modified-taxinvoice
         ************************************************************/

        // [수정세금계산서 작성시 필수] 수정사유코드, 수정사유에 따라 1~6중 선택기재
        // $Taxinvoice->modifyCode = '';

        // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
        // $Taxinvoice->orgNTSConfirmNum = '';

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/

        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[0]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[0]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[0]->spec = '';                   // 규격
        $Taxinvoice->detailList[0]->qty = '';                    // 수량
        $Taxinvoice->detailList[0]->unitCost = '';               // 단가
        $Taxinvoice->detailList[0]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[0]->tax = '10000';               // 세액
        $Taxinvoice->detailList[0]->remark = '';                 // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[1]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[1]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[1]->spec = '';                   // 규격
        $Taxinvoice->detailList[1]->qty = '';                    // 수량
        $Taxinvoice->detailList[1]->unitCost = '';               // 단가
        $Taxinvoice->detailList[1]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[1]->tax = '10000';               // 세액
        $Taxinvoice->detailList[1]->remark = '';                 // 비고

        /************************************************************
         *                      추가담당자 정보
         * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
         * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
         ************************************************************/

        $Taxinvoice->addContactList = array();

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[0]->serialNum = 1;              // 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[0]->email = '';                 // 이메일주소
        $Taxinvoice->addContactList[0]->contactName = '팝빌담당자'; // 담당자명

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[1]->serialNum = 2;              // 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[1]->email = '';                 // 이메일주소
        $Taxinvoice->addContactList[1]->contactName = '링크허브';   // 담당자명

        // 거래명세서 동시작성여부 (true / false 중 택 1)
        // └ true = 사용 , false = 미사용
        // - 미입력 시 기본값 false 처리
        $writeSpecification = false;

        try {
            $result = $this->PopbillTaxinvoice->Register($CorpNum, $Taxinvoice, $UserID, $writeSpecification);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * "임시저장" 상태의 세금계산서를 수정합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#Update
     */
    public function Update()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $MgtKey = '20230102-PHP7-002';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        /************************************************************
         *                        세금계산서 정보
         ************************************************************/

        // 세금계산서 객체 생성
        $Taxinvoice = new Taxinvoice();

        // 작성일자, 형식(yyyyMMdd) 예)20150101
        $Taxinvoice->writeDate = '20230102';

        // 발행유형, {정발행, 역발행, 위수탁} 중 기재
        $Taxinvoice->issueType = '정발행';

        // 과금방향, {정과금, 역과금} 중 기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -'역과금'은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = '정과금';

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = '영수';

        // 과세형태, {과세, 영세, 면세} 중 기재
        $Taxinvoice->taxType = '과세';

        /************************************************************
         *                         공급자 정보
         ************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoicerTaxRegID = '';

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = '공급자상호_수정';

        // 공급자 문서번호
        // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = $MgtKey;

        // 공급자 대표자성명
        $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

        // 공급자 주소
        $Taxinvoice->invoicerAddr = '공급자 주소';

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = '공급자 종목';

        // 공급자 업태
        $Taxinvoice->invoicerBizType = '공급자 업태';

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = '공급자 담당자성명';

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = '';

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = '';

        // 공급자 휴대폰 번호
        $Taxinvoice->invoicerHP = '';

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /************************************************************
         *                      공급받는자 정보
         ************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = '사업자';

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = '8888888888';

        // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoiceeTaxRegID = '';

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = '공급받는자 상호_수정';

        // [역발행시 필수] 공급받는자 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = '';

        // 공급받는자 대표자성명
        $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = '공급받는자 주소';

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = '공급받는자 업태';

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = '';

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = '';

        // 공급받는자 담당자 휴대폰 번호
        $Taxinvoice->invoiceeHP1 = '';

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /************************************************************
         *                       세금계산서 기재정보
         ************************************************************/

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = '200000';

        // 세액 합계
        $Taxinvoice->taxTotal = '20000';

        // 합계금액, (공급가액 합계 + 세액 합계)
        $Taxinvoice->totalAmount = '220000';

        // 기재상 '일련번호'항목
        $Taxinvoice->serialNum = '';

        // 기재상 '현금'항목
        $Taxinvoice->cash = '';

        // 기재상 '수표'항목
        $Taxinvoice->chkBill = '';

        // 기재상 '어음'항목
        $Taxinvoice->note = '';

        // 기재상 '외상'항목
        $Taxinvoice->credit = '';

        // 비고
        // {invoiceeType}이 "외국인" 이면 remark1 필수
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = '비고1';
        $Taxinvoice->remark2 = '비고2';
        $Taxinvoice->remark3 = '비고3';

        // 기재상 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 기재상 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /************************************************************
         *                     수정 세금계산서 기재정보
         * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
         * - [참고] 수정세금계산서 작성방법 안내 - https://developers.popbill.com/guide/taxinvoice/php/introduction/modified-taxinvoice
         ************************************************************/

        // [수정세금계산서 작성시 필수] 수정사유코드, 수정사유에 따라 1~6중 선택기재
        // $Taxinvoice->modifyCode = '';

        // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
        // $Taxinvoice->orgNTSConfirmNum = '';

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/

        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[0]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[0]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[0]->spec = '';                   // 규격
        $Taxinvoice->detailList[0]->qty = '';                    // 수량
        $Taxinvoice->detailList[0]->unitCost = '';               // 단가
        $Taxinvoice->detailList[0]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[0]->tax = '10000';               // 세액
        $Taxinvoice->detailList[0]->remark = '';                 // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[1]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[1]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[1]->spec = '';                   // 규격
        $Taxinvoice->detailList[1]->qty = '';                    // 수량
        $Taxinvoice->detailList[1]->unitCost = '';               // 단가
        $Taxinvoice->detailList[1]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[1]->tax = '10000';               // 세액
        $Taxinvoice->detailList[1]->remark = '';                 // 비고

        /************************************************************
         *                      추가담당자 정보
         * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
         * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
         ************************************************************/

        $Taxinvoice->addContactList = array();

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[0]->serialNum = 1;              // 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[0]->email = '';                 // 이메일주소
        $Taxinvoice->addContactList[0]->contactName = '팝빌담당자'; // 담당자명

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[1]->serialNum = 2;              // 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[1]->email = '';                 // 이메일주소
        $Taxinvoice->addContactList[1]->contactName = '링크허브';   // 담당자명

        // 거래명세서 동시작성여부 (true / false 중 택 1)
        // └ true = 사용 , false = 미사용
        // - 미입력 시 기본값 false 처리
        $writeSpecification = false;

        try {
            $result = $this->PopbillTaxinvoice->Update($CorpNum, $MgtKeyType, $MgtKey, $Taxinvoice, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * "임시저장" 또는 "(역)발행대기" 상태의 세금계산서를 발행(전자서명)하며, "발행완료" 상태로 처리합니다.
     * - 세금계산서 국세청 전송정책 [https://developers.popbill.com/guide/taxinvoice/php/introduction/policy-of-send-to-nts]
     * - "발행완료" 된 전자세금계산서는 국세청 전송 이전에 발행취소(CancelIssue API) 함수로 국세청 신고 대상에서 제외할 수 있습니다.
     * - 세금계산서 발행을 위해서 공급자의 인증서가 팝빌 인증서버에 사전등록 되어야 합니다.
     *   └ 위수탁발행의 경우, 수탁자의 인증서 등록이 필요합니다.
     * - 세금계산서 발행 시 공급받는자에게 발행 메일이 발송됩니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#Issue
     */
    public function Issue()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 메모
        $Memo = '발행 메모입니다';

        // 발행 안내메일 제목, 미기재시 기본제목으로 전송
        $EmailSubject = null;

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->Issue($CorpNum, $MgtKeyType, $MgtKey, $Memo, $EmailSubject, $ForceIssue, $UserID);
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    /**
     * 국세청 전송 이전 "발행완료" 상태의 전자세금계산서를 "발행취소"하고 국세청 신고대상에서 제외합니다.
     * - Delete(삭제)함수를 호출하여 "발행취소" 상태의 전자세금계산서를 삭제하면, 문서번호 재사용이 가능합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#CancelIssue
     */
    public function CancelIssue()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 메모
        $Memo = '발행 취소메모입니다';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->CancelIssue($CorpNum, $MgtKeyType, $MgtKey, $Memo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 공급받는자가 작성한 세금계산서 데이터를 팝빌에 저장하고 공급자에게 송부하여 발행을 요청합니다.
     * - 역발행 세금계산서 프로세스를 구현하기 위해서는 공급자/공급받는자가 모두 팝빌에 회원이여야 합니다.
     * - 발행 요청된 세금계산서는 "(역)발행대기" 상태이며, 공급자가 팝빌 사이트 또는 함수를 호출하여 발행한 경우에만 국세청으로 전송됩니다.
     * - 공급자는 팝빌 사이트의 "매출 발행 대기함"에서 발행대기 상태의 역발행 세금계산서를 확인할 수 있습니다.
     * - 임시저장(Register API) 함수와 역발행 요청(Request API) 함수를 한 번의 프로세스로 처리합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#RegistRequest
     */
    public function RegistRequest()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 공급받는자 문서번호
        // - 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $invoiceeMgtKey = '20230102-PHP7-003';

        /************************************************************
         *                        세금계산서 정보
         ************************************************************/

        // 세금계산서 객체 생성
        $Taxinvoice = new Taxinvoice();

        // 작성일자, 형식(yyyyMMdd) 예)20150101
        $Taxinvoice->writeDate = '20230102';

        // 발행유형, {정발행, 역발행, 위수탁} 중 기재
        $Taxinvoice->issueType = '역발행';

        // 과금방향, {정과금, 역과금} 중 기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -'역과금'은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = '정과금';

        // {영수, 청구, 없음} 중 기재
        $Taxinvoice->purposeType = '영수';

        // 과세형태, {과세, 영세, 면세} 중 기재
        $Taxinvoice->taxType = '과세';

        /************************************************************
         *                         공급자 정보
         ************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '8888888888';

        // 공급자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoicerTaxRegID = '';

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = '공급자상호';

        // 공급자 문서번호
        // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = '';

        // 공급자 대표자성명
        $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

        // 공급자 주소
        $Taxinvoice->invoicerAddr = '공급자 주소';

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = '공급자 종목';

        // 공급자 업태
        $Taxinvoice->invoicerBizType = '공급자 업태';

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = '공급자 담당자성명';

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = '';

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = '';

        // 공급자 휴대폰 번호
        $Taxinvoice->invoicerHP = '';

        /************************************************************
         *                      공급받는자 정보
         ************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = '사업자';

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = '1234567890';

        // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoiceeTaxRegID = '';

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

        // [역발행시 필수] 공급받는자 문서번호,
        // 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = $invoiceeMgtKey;

        // 공급받는자 대표자성명
        $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = '공급받는자 주소';

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = '공급받는자 업태';

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = '';

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = '';

        // 공급받는자 담당자 휴대폰 번호
        $Taxinvoice->invoiceeHP1 = '';

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /************************************************************
         *                       세금계산서 기재정보
         ************************************************************/

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = '200000';

        // 세액 합계
        $Taxinvoice->taxTotal = '20000';

        // 합계금액, (공급가액 합계 + 세액 합계)
        $Taxinvoice->totalAmount = '220000';

        // 기재상 '일련번호'항목
        $Taxinvoice->serialNum = '';

        // 기재상 '현금'항목
        $Taxinvoice->cash = '';

        // 기재상 '수표'항목
        $Taxinvoice->chkBill = '';

        // 기재상 '어음'항목
        $Taxinvoice->note = '';

        // 기재상 '외상'항목
        $Taxinvoice->credit = '';

        // 비고
        // {invoiceeType}이 "외국인" 이면 remark1 필수
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = '비고1';
        $Taxinvoice->remark2 = '비고2';
        $Taxinvoice->remark3 = '비고3';

        // 기재상 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 기재상 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /************************************************************
         *                     수정 세금계산서 기재정보
         * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
         * - [참고] 수정세금계산서 작성방법 안내 - https://developers.popbill.com/guide/taxinvoice/php/introduction/modified-taxinvoice
         ************************************************************/

        // [수정세금계산서 작성시 필수] 수정사유코드, 수정사유에 따라 1~6중 선택기재
        // $Taxinvoice->modifyCode = '';

        // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
        // $Taxinvoice->orgNTSConfirmNum = '';

        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/

        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[0]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[0]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[0]->spec = '';                   // 규격
        $Taxinvoice->detailList[0]->qty = '';                    // 수량
        $Taxinvoice->detailList[0]->unitCost = '';               // 단가
        $Taxinvoice->detailList[0]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[0]->tax = '10000';               // 세액
        $Taxinvoice->detailList[0]->remark = '';                 // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[1]->purchaseDT = '20230102';     // 거래일자
        $Taxinvoice->detailList[1]->itemName = '품목명1번';      // 품명
        $Taxinvoice->detailList[1]->spec = '';                   // 규격
        $Taxinvoice->detailList[1]->qty = '';                    // 수량
        $Taxinvoice->detailList[1]->unitCost = '';               // 단가
        $Taxinvoice->detailList[1]->supplyCost = '100000';       // 공급가액
        $Taxinvoice->detailList[1]->tax = '10000';               // 세액
        $Taxinvoice->detailList[1]->remark = '';                 // 비고

        // 메모
        $Memo = '즉시요청 메모';

        try {
            $result = $this->PopbillTaxinvoice->RegistRequest($CorpNum, $Taxinvoice, $Memo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 공급받는자가 저장된 역발행 세금계산서를 공급자에게 송부하여 발행 요청합니다.
     * - 역발행 세금계산서 프로세스를 구현하기 위해서는 공급자/공급받는자가 모두 팝빌에 회원이여야 합니다.
     * - 역발행 요청된 세금계산서는 "(역)발행대기" 상태이며, 공급자가 팝빌 사이트 또는 함수를 호출하여 발행한 경우에만 국세청으로 전송됩니다.
     * - 공급자는 팝빌 사이트의 "매출 발행 대기함"에서 발행대기 상태의 역발행 세금계산서를 확인할 수 있습니다.
     * - 역발행 요청시 공급자에게 역발행 요청 메일이 발송됩니다.
     * - 공급자가 역발행 세금계산서 발행시 포인트가 과금됩니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#Request
     */
    public function Request()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::BUY;

        // 문서번호
        $MgtKey = '20230102-PHP7-004';

        // 메모
        $Memo = '역발행 요청 메모입니다';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->Request($CorpNum, $MgtKeyType, $MgtKey, $Memo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 공급자가 요청받은 역발행 세금계산서를 발행하기 전, 공급받는자가 역발행요청을 취소합니다.
     * - 함수 호출시 상태 값이 "취소"로 변경되고, 해당 역발행 세금계산서는 공급자에 의해 발행 될 수 없습니다.
     * - [취소]한 세금계산서의 문서번호를 재사용하기 위해서는 삭제 (Delete API) 함수를 호출해야 합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#CancelRequest
     */
    public function CancelRequest()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::BUY;

        // 문서번호
        $MgtKey = '20230102-PHP7-004';

        // 메모
        $Memo = '역발행 요청 취소메모입니다';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->CanCelRequest($CorpNum, $MgtKeyType, $MgtKey, $Memo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 공급자가 공급받는자에게 역발행 요청 받은 세금계산서의 발행을 거부합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#Refuse
     */
    public function Refuse()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-005';

        // 메모
        $Memo = '역)발행 요청 거부메모입니다';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->Refuse($CorpNum, $MgtKeyType, $MgtKey, $Memo, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 삭제 가능한 상태의 세금계산서를 삭제합니다.
     * - 삭제 가능한 상태: "임시저장", "발행취소", "역발행거부", "역발행취소", "전송실패"
     * - 세금계산서를 삭제해야만 문서번호(mgtKey)를 재사용할 수 있습니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#Delete
     */
    public function Delete()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->Delete($CorpNum, $MgtKeyType, $MgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * "발행완료" 상태의 전자세금계산서를 국세청에 즉시 전송하며, 함수 호출 후 최대 30분 이내에 전송 처리가 완료됩니다.
     * - 국세청 즉시전송을 호출하지 않은 세금계산서는 발행일 기준 다음 영업일 오후 3시에 팝빌 시스템에서 일괄적으로 국세청으로 전송합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/issue#SendToNTS
     */
    public function SendToNTS()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->SendToNTS($CorpNum, $MgtKeyType, $MgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 세금계산서 1건의 상태 및 요약정보를 확인합니다.
     * - 리턴값 'TaxinvoiceInfo'의 변수 'stateCode'를 통해 세금계산서의 상태코드를 확인합니다.
     * - 세금계산서 상태코드 [https://developers.popbill.com/reference/taxinvoice/php/response-code]
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#GetInfo
     */
    public function GetInfo()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 조회할 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-002';

        try {
            $result = $this->PopbillTaxinvoice->GetInfo($CorpNum, $MgtKeyType, $MgtKey);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Taxinvoice/GetInfo', ['TaxinvoiceInfo' => [$result]]);
    }

    /**
     * 다수건의 세금계산서 상태 및 요약 정보를 확인합니다. (1회 호출 시 최대 1,000건 확인 가능)
     * - 리턴값 'TaxinvoiceInfo'의 변수 'stateCode'를 통해 세금계산서의 상태코드를 확인합니다.
     * - 세금계산서 상태코드 [https://developers.popbill.com/reference/taxinvoice/php/response-code]
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#GetInfos
     */
    public function GetInfos()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호 배열, 최대 1000건
        $MgtKeyList = array();
        array_push($MgtKeyList, "20230102-PHP7-001");
        array_push($MgtKeyList, '20230102-PHP7-002');

        try {
            $result = $this->PopbillTaxinvoice->GetInfos($CorpNum, $MgtKeyType, $MgtKeyList);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Taxinvoice/GetInfo', ['TaxinvoiceInfo' => $result]);
    }

    /**
     * 세금계산서 1건의 상세정보를 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#GetDetailInfo
     */
    public function GetDetailInfo()
    {

        // 팝빌회원, 사업자번호
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-002';

        try {
            $result = $this->PopbillTaxinvoice->GetDetailInfo($CorpNum, $MgtKeyType, $MgtKey);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Taxinvoice/GetDetailInfo', ['Taxinvoice' => $result]);
    }

    /**
     * 전자세금계산서 1건의 상세정보를 XML로 반환합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#GetXML
     */
    public function GetXML()
    {

        // 팝빌 회원 사업자 번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, TIENumMgtKeyType::SELL:매출, TIENumMgtKeyType::BUY:매입, TIENumMgtKeyType::TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 팝빌회원 아이디
        $userID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->GetXML($CorpNum, $MgtKeyType, $MgtKey, $userID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Taxinvoice/GetXML', ['Result' => $result]);
    }

    /**
     * 검색조건에 해당하는 세금계산서를 조회합니다. (조회기간 단위 : 최대 6개월)
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#Search
     */
    public function Search()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 일자유형 ("R" , "W" , "I" 중 택 1)
        // - R = 등록일자 , W = 작성일자 , I = 발행일자
        $DType = 'W';

        // 시작일자
        $SDate = '20230101';

        // 종료일자
        $EDate = '20230131';

        // 세금계산서 상태코드 배열 (2,3번째 자리에 와일드카드(*) 사용 가능)
        // - 미입력시 전체조회
        $State = array(
            '3**',
            '6**'
        );

        // 문서유형 배열 ("N" , "M" 중 선택, 다중 선택 가능)
        // - N = 일반세금계산서 , M = 수정세금계산서
        // - 미입력시 전체조회
        $Type = array(
            'N',
            'M'
        );

        // 과세형태 배열 ("T" , "N" , "Z" 중 선택, 다중 선택 가능)
        // - T = 과세 , N = 면세 , Z = 영세
        // - 미입력시 전체조회
        $TaxType = array(
            'T',
            'N',
            'Z'
        );

        // 발행형태 배열 ("N" , "R" , "T" 중 선택, 다중 선택 가능)
        // - N = 정발행 , R = 역발행 , T = 위수탁발행
        // - 미입력시 전체조회
        $IssueType = array(
            'N',
            'R',
            'T'
        );

        // 공급받는자 휴폐업상태 배열 ("N" , "0" , "1" , "2" , "3" , "4" 중 선택, 다중 선택 가능)
        // - N = 미확인 , 0 = 미등록 , 1 = 사업 , 2 = 폐업 , 3 = 휴업 , 4 = 확인실패
        // - 미입력시 전체조회
        $CloseDownState = array(
            'N',
            '0',
            '1',
            '2',
            '3'
        );

        // 등록유형 배열 ("P" , "H" 중 선택, 다중 선택 가능)
        // - P = 팝빌에서 등록 , H = 홈택스 또는 외부ASP 등록
        // - 미입력시 전체조회
        $RegType = array(
            'P',
            'H'
        );

        // 지연발행 여부 (null , true , false 중 택 1)
        // - null = 전체조회 , true = 지연발행 , false = 정상발행
        $LateOnly = 0;

        // 종사업장번호 유무 (null , "0" , "1" 중 택 1)
        // - null = 전체 , 0 = 없음, 1 = 있음
        $TaxRegIDYN = "";

        // 종사업장번호의 주체 ("S" , "B" , "T" 중 택 1)
        // └ S = 공급자 , B = 공급받는자 , T = 수탁자
        // - 미입력시 전체조회
        $TaxRegIDType = "S";

        // 종사업장번호
        // 다수기재시 콤마(",")로 구분하여 구성 ex ) "0001,0002"
        // - 미입력시 전체조회
        $TaxRegID = "";

        // 페이지 번호 기본값 1
        $Page = 1;

        // 페이지당 검색갯수, 기본값 500, 최대값 1000
        $PerPage = 5;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = 'D';

        // 거래처 상호 / 사업자번호 (사업자) / 주민등록번호 (개인) / "9999999999999" (외국인) 중 검색하고자 하는 정보 입력
        // └ 사업자번호 / 주민등록번호는 하이픈('-')을 제외한 숫자만 입력
        // - 미입력시 전체조회
        $QString = '';

        // 문서번호 또는 국세청승인번호 조회
        $MgtKey = '';

        // 연동문서 여부 (null , "0" , "1" 중 택 1)
        // └ null = 전체조회 , 0 = 일반문서 , 1 = 연동문서
        // - 일반문서 : 팝빌 사이트를 통해 저장 또는 발행한 세금계산서
        // - 연동문서 : 팝빌 API를 통해 저장 또는 발행한 세금계산서
        $InterOPYN = '';

        try {
            $result = $this->PopbillTaxinvoice->Search(
                $CorpNum,
                $MgtKeyType,
                $DType,
                $SDate,
                $EDate,
                $State,
                $Type,
                $TaxType,
                $LateOnly,
                $Page,
                $PerPage,
                $Order,
                $TaxRegIDType,
                $TaxRegIDYN,
                $TaxRegID,
                $QString,
                $InterOPYN,
                $UserID,
                $IssueType,
                $CloseDownState,
                $MgtKey,
                $RegType
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Taxinvoice/Search', ['Result' => $result]);
    }

    /**
     * 세금계산서의 상태에 대한 변경이력을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#GetLogs
     */
    public function GetLogs()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-002';

        try {
            $result = $this->PopbillTaxinvoice->GetLogs($CorpNum, $MgtKeyType, $MgtKey);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Taxinvoice/GetLogs', ['Result' => $result]);
    }

    /**
     * 로그인 상태로 팝빌 사이트의 전자세금계산서 문서함 메뉴에 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/info#GetURL
     */
    public function GetURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // [TBOX] 임시문서함, [SBOX] 매출문서함, [PBOX] 매입문서함,
        // [WRITE] 정발행 작성, [SWBOX] 매출 발행 대기함, [PWBOX] 매입 발행 대기함
        $TOGO = 'TBOX';

        try {
            $url = $this->PopbillTaxinvoice->GetURL($CorpNum, $UserID, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "세금계산서 문서함 팝업 URL", 'value' => $url]);
    }

    /**
     * 세금계산서 1건의 상세 정보 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetPopUpURL
     */
    public function GetPopUpURL()
    {

        // 팝빌 회원 사업자 번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetPopUpURL($CorpNum, $MgtKeyType, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "세금계산서 보기 팝업 URL", 'value' => $url]);
    }

    /**
     * 세금계산서 1건의 상세정보 페이지(사이트 상단, 좌측 메뉴 및 버튼 제외)의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetViewURL
     */
    public function GetViewURL()
    {

        // 팝빌 회원 사업자 번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetViewURL($CorpNum, $MgtKeyType, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "세금계산서 보기 팝업 URL (메뉴/버튼 제외)", 'value' => $url]);
    }

    /**
     * 세금계산서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환하며, 페이지내에서 인쇄 설정값을 "공급자" / "공급받는자" / "공급자+공급받는자"용 중 하나로 지정할 수 있습니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetPrintURL
     */
    public function GetPrintURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetPrintURL($CorpNum, $MgtKeyType, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "세금계산서 인쇄 팝업 URL", 'value' => $url]);
    }

    /**
     * 세금계산서 1건을 구버전 양식으로 인쇄하기 위한 페이지의 팝업 URL을 반환하며, 페이지내에서 인쇄 설정값을 "공급자" / "공급받는자" / "공급자+공급받는자"용 중 하나로 지정할 수 있습니다..
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetOldPrintURL
     */
    public function GetOldPrintURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetOldPrintURL($CorpNum, $MgtKeyType, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "세금계산서 (구)인쇄 팝업 URL", 'value' => $url]);
    }

    /**
     * "공급받는자" 용 세금계산서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetEPrintURL
     */
    public function GetEPrintURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetEPrintURL($CorpNum, $MgtKeyType, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "세금계산서 인쇄(공급받는자용) 팝업 URL", 'value' => $url]);
    }

    /**
     * 다수건의 세금계산서를 인쇄하기 위한 페이지의 팝업 URL을 반환합니다. (최대 100건)
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetMassPrintURL
     */
    public function GetMassPrintURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호 배열 최대 100건
        $MgtKeyList = array(
            '20230102-PHP7-001',
            '20230102-PHP7-002'
        );

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetMassPrintURL($CorpNum, $MgtKeyType, $MgtKeyList, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "세금계산서 대량 인쇄 팝업 URL", 'value' => $url]);
    }

    /**
     * 전자세금계산서 안내메일의 상세보기 링크 URL을 반환합니다.
     * - 함수 호출로 반환 받은 URL에는 유효시간이 없습니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetMailURL
     */
    public function GetMailURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetMailURL($CorpNum, $MgtKeyType, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "공급받는자 세금계산서 메일링크 URL", 'value' => $url]);
    }

    /**
     * 전자세금계산서 PDF 파일을 다운 받을 수 있는 URL을 반환합니다.
     * - 반환되는 URL은 보안정책상 30초의 유효시간을 갖으며, 유효시간 이후 호출시 정상적으로 페이지가 호출되지 않습니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/view#GetPDFURL
     */
    public function GetPDFURL()
    {

        // 팝빌 회원 사업자 번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetPDFURL($CorpNum, $MgtKeyType, $MgtKey, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "세금계산서 PDF 다운로드 URL", 'value' => $url]);
    }

    /**
     * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#GetAccessURL
     */
    public function GetAccessURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetAccessURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL", 'value' => $url]);
    }

    /**
     * 세금계산서에 첨부할 인감, 사업자등록증, 통장사본을 등록하는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#GetSealURL
     */
    public function GetSealURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetSealURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "인감 및 첨부문서 등록 URL", 'value' => $url]);
    }

    /**
     * "임시저장" 상태의 세금계산서에 1개의 파일을 첨부합니다. (최대 5개)
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#AttachFile
     */
    public function AttachFile()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 첨부파일 경로, 해당 파일에 읽기 권한이 설정되어 있어야 합니다.
        $filePath = '/image.jpg';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->AttachFile($CorpNum, $MgtKeyType, $MgtKey, $filePath, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * "임시저장" 상태의 세금계산서에 첨부된 1개의 파일을 삭제합니다.
     * - 파일 식별을 위해 첨부 시 부여되는 'FileID'는 첨부파일 목록 확인(GetFiles API) 함수를 호출하여 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#DeleteFile
     */
    public function DeleteFile()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 팝빌이 첨부파일 관리를 위해 할당하는 식별번호
        // 첨부파일 목록 확인(getFiles API) 함수의 리턴 값 중 attachedFile 필드값 기재.
        $FileID = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->DeleteFile($CorpNum, $MgtKeyType, $MgtKey, $FileID, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 세금계산서에 첨부된 파일목록을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#GetFiles
     */
    public function GetFiles()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        try {
            $result = $this->PopbillTaxinvoice->GetFiles($CorpNum, $MgtKeyType, $MgtKey);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('GetFiles', ['Result' => $result]);
    }

    /**
     * 세금계산서와 관련된 안내 메일을 재전송 합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#SendEmail
     */
    public function SendEmail()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 문서번호
        $MgtKey = '20230102-PHP7-002';

        // 수신이메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $receiver = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->SendEmail($CorpNum, $MgtKeyType, $MgtKey, $receiver, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 세금계산서와 관련된 안내 SMS(단문) 문자를 재전송하는 함수로, 팝빌 사이트 [문자·팩스] > [문자] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
     * - 메시지는 최대 90byte까지 입력 가능하고, 초과한 내용은 자동으로 삭제되어 전송합니다. (한글 최대 45자)
     * - 함수 호출시 포인트가 과금됩니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#SendSMS
     */
    public function SendSMS()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 발신번호
        $Sender = '';

        // 수신번호
        $receiver = '';

        // 메시지 내용, 90byte 초과시 길이가 조정되어 전송됨.
        $contents = '문자 메시지 내용입니다. 세금계산서가 발행되었습니다.';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->SendSMS($CorpNum, $MgtKeyType, $MgtKey, $Sender, $receiver, $contents, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 세금계산서를 팩스로 전송하는 함수로, 팝빌 사이트 [문자·팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
     * - 함수 호출시 포인트가 과금됩니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#SendFAX
     */
    public function SendFAX()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 발신번호
        $Sender = '';

        // 수신팩스번호
        $receiver = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->SendFAX($CorpNum, $MgtKeyType, $MgtKey, $Sender, $receiver, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌 전자명세서 API를 통해 발행한 전자명세서를 세금계산서에 첨부합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#AttachStatement
     */
    public function AttachStatement()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 첨부할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $subItemCode = 121;

        // 첨부할 명세서 문서번호
        $subMgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->AttachStatement($CorpNum, $MgtKeyType, $MgtKey, $subItemCode, $subMgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 세금계산서에 첨부된 전자명세서를 해제합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#DetachStatement
     */
    public function DetachStatement()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 문서번호
        $MgtKey = '20230102-PHP7-001';

        // 첨부해제할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
        $subItemCode = 121;

        // 첨부해제할 명세서 문서번호
        $subMgtKey = '20230102-PHP7-001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->DetachStatement($CorpNum, $MgtKeyType, $MgtKey, $subItemCode, $subMgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 전자세금계산서 유통사업자의 메일 목록을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#GetEmailPublicKeys
     */
    public function GetEmailPublicKeys()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $emailList = $this->PopbillTaxinvoice->GetEmailPublicKeys($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Taxinvoice/GetEmailPublicKeys', ['Result' => $emailList]);
    }

    /**
     * 팝빌 사이트를 통해 발행하였지만 문서번호가 존재하지 않는 세금계산서에 문서번호를 할당합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#AssignMgtKey
     */
    public function AssignMgtKey()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
        $MgtKeyType = TIENumMgtKeyType::SELL;

        // 세금계산서 아이템키, 문서 목록조회(Search) API의 반환항목중 ItemKey 참조
        $itemKey = '018123114240100001';

        // 할당할 문서번호, 숫자, 영문 '-', '_' 조합으로 1~24자리까지
        // 사업자번호별 중복없는 고유번호 할당
        $MgtKey = '20230102-PHP7-006';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->AssignMgtKey($CorpNum, $MgtKeyType, $itemKey, $MgtKey, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 세금계산서 관련 메일 항목에 대한 발송설정을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#ListEmailConfig
     */
    public function ListEmailConfig()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->ListEmailConfig($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Taxinvoice/ListEmailConfig', ['Result' => $result]);
    }

    /**
     * 세금계산서 관련 메일 항목에 대한 발송설정을 수정합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#UpdateEmailConfig
     *
     * 메일전송유형
     * [정발행]
     * - TAX_ISSUE : 공급받는자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
     * - TAX_ISSUE_INVOICER : 공급자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
     * - TAX_CHECK : 공급자에게 전자세금계산서가 수신확인 되었음을 알려주는 메일입니다.
     * - TAX_CANCEL_ISSUE : 공급받는자에게 전자세금계산서가 발행취소 되었음을 알려주는 메일입니다.
     *
     * [역발행]
     * - TAX_REQUEST : 공급자에게 세금계산서를 전자서명 하여 발행을 요청하는 메일입니다.
     * - TAX_CANCEL_REQUEST : 공급받는자에게 세금계산서가 취소 되었음을 알려주는 메일입니다.
     * - TAX_REFUSE : 공급받는자에게 세금계산서가 거부 되었음을 알려주는 메일입니다.
     *
     * [위수탁발행]
     * - TAX_TRUST_ISSUE : 공급받는자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
     * - TAX_TRUST_ISSUE_TRUSTEE : 수탁자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
     * - TAX_TRUST_ISSUE_INVOICER : 공급자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
     * - TAX_TRUST_CANCEL_ISSUE : 공급받는자에게 전자세금계산서가 발행취소 되었음을 알려주는 메일입니다.
     * - TAX_TRUST_CANCEL_ISSUE_INVOICER : 공급자에게 전자세금계산서가 발행취소 되었음을 알려주는 메일입니다.
     *
     * [처리결과]
     * - TAX_CLOSEDOWN : 거래처의 휴폐업 여부를 확인하여 안내하는 메일입니다.
     * - TAX_NTSFAIL_INVOICER : 전자세금계산서 국세청 전송실패를 안내하는 메일입니다.
     *
     * [정기발송]
     * - ETC_CERT_EXPIRATION : 팝빌에서 이용중인 공인인증서의 갱신을 안내하는 메일입니다.
     */
    public function UpdateEmailConfig()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 메일 전송 유형
        $emailType = 'TAX_ISSUE';

        // 전송 여부 (True = 전송, False = 미전송)
        $sendYN = True;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->UpdateEmailConfig($CorpNum, $emailType, $sendYN, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원의 국세청 전송 옵션 설정 상태를 확인합니다.
     * - 팝빌 국세청 전송 정책 [https://developers.popbill.com/guide/taxinvoice/php/introduction/policy-of-send-to-nts]
     * - 국세청 전송 옵션 설정은 팝빌 사이트 [전자세금계산서] > [환경설정] > [세금계산서 관리] 메뉴에서 설정할 수 있으며, API로 설정은 불가능 합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/etc#GetSendToNTSConfig
     */
    public function GetSendToNTSConfig()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $sendToNTSConfig = $this->PopbillTaxinvoice->GetSendToNTSConfig($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "국세청 전송 설정 확인", 'value' => $sendToNTSConfig ? 'true' : 'false']);
    }

    /**
     * 전자세금계산서 발행에 필요한 인증서를 팝빌 인증서버에 등록하기 위한 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - 인증서 갱신/재발급/비밀번호 변경한 경우, 변경된 인증서를 팝빌 인증서버에 재등록 해야합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/cert#GetTaxCertURL
     */
    public function GetTaxCertURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetTaxCertURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "공인인증서 등록 URL", 'value' => $url]);
    }

    /**
     * 팝빌 인증서버에 등록된 인증서의 만료일을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/cert#GetCertificateExpireDate
     */
    public function GetCertificateExpireDate()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        try {
            $certExpireDate = $this->PopbillTaxinvoice->GetCertificateExpireDate($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "공인인증서 만료일시", 'value' => $certExpireDate]);
    }

    /**
     * 팝빌 인증서버에 등록된 인증서의 유효성을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/cert#CheckCertValidation
     */
    public function CheckCertValidation()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->CheckCertValidation($CorpNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌 인증서버에 등록된 공동인증서의 정보를 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/cert#GetTaxCertInfo
     */
    public function GetTaxCertInfo()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $TaxinvoiceCertificate = $this->PopbillTaxinvoice->GetTaxCertInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Taxinvoice/GetTaxCertInfo', ['TaxinvoiceCertificate' => $TaxinvoiceCertificate]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetBalance
     */
    public function GetBalance()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillTaxinvoice->GetBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetUseHistory
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
            $result = $this->PopbillTaxinvoice->GetUseHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetPaymentHistory
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
            $result = $this->PopbillTaxinvoice->GetPaymentHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불 신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetRefundHistory
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
            $result = $this->PopbillTaxinvoice->GetRefundHistory($CorpNum, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불을 신청합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#Refund
     */
    public function Refund()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
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
            $result = $this->PopbillTaxinvoice->Refund($CorpNum, $RefundForm, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#PaymentRequest
     */
    public function PaymentRequest()
    {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
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
            $result = $this->PopbillTaxinvoice->PaymentRequest($CorpNum, $PaymentForm, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentResponse', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 무통장 입금신청내역 1건을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetSettleResult
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
            $result = $this->PopbillTaxinvoice->GetSettleResult($CorpNum, $SettleCode, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetChargeURL
     */
    public function GetChargeURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetChargeURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetPaymentURL
     */
    public function GetPaymentURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetPaymentURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillTaxinvoice->GetUseHistoryURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetPartnerBalance
     */
    public function GetPartnerBalance()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillTaxinvoice->GetPartnerBalance($CorpNum);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetPartnerURL
     */
    public function GetPartnerURL()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillTaxinvoice->GetPartnerURL($CorpNum, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 세금계산서 발행시 과금되는 포인트 단가를 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetUnitCost
     */
    public function GetUnitCost()
    {

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $unitCost = $this->PopbillTaxinvoice->GetUnitCost($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자세금계산서 발행단가", 'value' => $unitCost]);
    }

    /**
     * 팝빌 전자세금계산서 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetChargeInfo
     */
    public function GetChargeInfo()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->GetChargeInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#CheckIsMember
     */
    public function CheckIsMember()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 연동신청 시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillTaxinvoice->CheckIsMember($CorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#CheckID
     */
    public function CheckID()
    {

        // 조회할 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillTaxinvoice->CheckID($UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#JoinMember
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
            $result = $this->PopbillTaxinvoice->JoinMember($JoinForm);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#GetCorpInfo
     */
    public function GetCorpInfo()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillTaxinvoice->GetCorpInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#UpdateCorpInfo
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
            $result = $this->PopbillTaxinvoice->UpdateCorpInfo($CorpNum, $CorpInfo, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#RegistContact
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
            $result = $this->PopbillTaxinvoice->RegistContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#GetContactInfo
     */
    public function GetContactInfo()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        //확인할 담당자 아이디
        $ContactID = 'checkContact';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactInfo = $this->PopbillTaxinvoice->GetContactInfo($CorpNum, $ContactID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#ListContact
     */
    public function ListContact()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactList = $this->PopbillTaxinvoice->ListContact($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#UpdateContact
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
            $result = $this->PopbillTaxinvoice->UpdateContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/taxinvoice/php/api/member#QuitMember
     */
    public function QuitMember()
    {

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        // 회원 탈퇴 사유
        $QuitReason = "탈퇴 테스트";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $result = $this->PopbillTaxinvoice->QuitMember($CorpNum, $QuitReason, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PResponse', ['code' => $result->code, 'message' => $result->message]);
    }

    /**
     * 환불 가능한 포인트를 확인합니다. (보너스 포인트는 환불가능포인트에서 제외됩니다.)
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetRefundableBalance
     */
    public function GetRefundableBalance()
    {

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $refundableBalance = $this->PopbillTaxinvoice->GetRefundableBalance($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundableBalance', ['refundableBalance' => $refundableBalance]);
    }

    /**
     * 포인트 환불에 대한 상세정보 1건을 확인합니다.
     * - https://developers.popbill.com/reference/taxinvoice/php/api/point#GetRefundInfo
     */
    public function GetRefundInfo()
    {

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        // 환불코드
        $RefundCode = "023040000015";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $result = $this->PopbillTaxinvoice->GetRefundInfo($CorpNum, $RefundCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundInfo', ['result' => $result]);
    }


    public function modifyTaxinvoice01minus()
    {
        /**
         * 기재사항 착오정정 수정세금계산서를 발행합니다.
         * - '필요적 기재사항'이나 '임의적 기재사항' 등을 착오 또는 착오 외의 사유로 잘못 작성하거나, 세율을 잘못 적용하여 신고한 경우 이용하는 수정사유 입니다.
         * - 기재사항 착오정정 수정세금계산서는 총 2장(취소분/수정분) 발급해야 합니다.
         * - https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice
         */

        /**
         **************** 기재사항 착오정정 수정세금계산서 예시 (취소분) ****************
         * 작성일자 1월 2일 공급가액 200,000원으로 매출 세금계산서를 발급해야 하는데, 공급가액 100,000원으로 잘못 발급 한 경우
         * 원본 전자세금계산서와 동일한 내용의 부(-) 세금계산서 발행
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        // 원본 세금계산서를 취소할 세금계산서 객체
        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        // 원본 세금계산서 작성 일자 기재
        $Taxinvoice->writeDate = "20230102";

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = "-100000";

        // 세액 합계
        $Taxinvoice->taxTotal = "-10000";

        // 합계금액, 공급가액 + 세액
        $Taxinvoice->totalAmount = "-110000";

        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";

        // 과세형태, [과세, 영세, 면세] 중 기재
        $Taxinvoice->taxType = "과세";

        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-modify-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-1111-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/


        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        // {invoiceeType}이 "외국인" 이면 remark1 필수
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = "비고1";
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/
        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        $Taxinvoice->modifyCode = 1;

        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = "20230706-original-TI00001";

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/

        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "-50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "-50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "-5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "-50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "-50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "-5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고


        /**********************************************************************
         * 추가담당자 정보 - 세금계산서 발행 안내 메일을 수신받을 공급받는자 담당자가 다수인 경우 - 담당자 정보를 추가하여 발행 안내메일을 다수에게 전송할 수
         * 있습니다. (최대 5명)
         *********************************************************************/

        // $Taxinvoice->addContactList(new ArrayList<TaxinvoiceAddContact>());

        // TaxinvoiceAddContact addContact = new TaxinvoiceAddContact();

        // addContact.setSerialNum(1);
        // addContact.setContactName("추가 담당자 성명");
        // addContact.setEmail("test2@test.com");

        // taxinvoice.getAddContactList().add(addContact);

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    public function modifyTaxinvoice01plus()
    {
        /**
         * 기재사항 착오정정 수정세금계산서를 발행합니다.
         * - '필요적 기재사항'이나 '임의적 기재사항' 등을 착오 또는 착오 외의 사유로 잘못 작성하거나, 세율을 잘못 적용하여 신고한 경우 이용하는 수정사유 입니다
         * - 기재사항 착오정정 수정세금계산서는 총 2장(취소분/수정분) 발급해야 합니다.
         * - https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice
         */

        /**
         **************** 기재사항 착오정정 수정세금계산서 예시 (수정분) ****************
         * 작성일자 1월 2일 공급가액 200,000원으로 매출 세금계산서를 발급해야 하는데, 공급가액 100,000원으로 잘못 발급 한 경우
         * 수정사항을 반영한 정(+) 세금계산서를 발행
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        // 원본 전자세금계산서 작성일자 또는 변경을 원하는 작성일자
        $Taxinvoice->writeDate = "20230102";

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = "200000";

        // 세액 합계
        $Taxinvoice->taxTotal = "20000";

        // 합계금액, 공급가액 + 세액
        $Taxinvoice->totalAmount = "220000";

        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        $Taxinvoice->modifyCode = 1;

        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";

        // 과세형태, [과세, 영세, 면세] 중 기재
        $Taxinvoice->taxType = "과세";

        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-000-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/


        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        // {invoiceeType}이 "외국인" 이면 remark1 필수
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = "비고1";
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/

        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = null;

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/

        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    public function modifyTaxinvoice02()
    {
        /**
         * 공급가액 변동에 의한 수정세금계산서 발행
         * - 일부 금액의 계약의 해지 등을 포함하여 공급가액의 증가 또는 감소가 발생한 경우 이용하는 수정사유 입니다.
         * - 증가 : 원본 전자세금계산서 공급가액에서 증가한 금액만큼만 수정분 정(+) 세금계산서 발행
         * - 감소 : 원본 전자세금계산서 공급가액에서 감소한 금액만큼만 수정분 부(-) 세금계산서 발행
         * - ※ 원본 전자세금계산서 공급가액 + 수정세금계산서 공급가액(+/-) = 최종 공급가액
         * - 수정세금계산서 가이드: [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         */

        /**
         **************** 공급가액 변동에 의한 수정세금계산서 예시 ****************
         * 작성일자 2월 7일 공급가액 30,000원으로 매출 세금계산서를 발급해야 하는데, 공급가액 50,000원으로 잘못  발급한 경우
         * 원본 공급가액의 50,000원에서 차감되어야 하는 금액이 -20,000원의 수정세금계산서 발행
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        // 공급가액 변동이 발생한 날
        $Taxinvoice->writeDate = "20230207";

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = "-20000";

        // 세액 합계
        $Taxinvoice->taxTotal = "-2000";

        // 합계금액, 공급가액 + 세액
        $Taxinvoice->totalAmount = "-22000";

        // 비고
        // 공급가액 변동으로 인한 수정 세금계산서 작성 시, = 원본 세금계산서 작성일자 기재 필수
        $Taxinvoice->remark1 = "20230207";
        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";

        // 과세형태, [과세, 영세, 면세] 중 기재
        $Taxinvoice->taxType = "과세";

        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-000-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/

        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/
        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        $Taxinvoice->modifyCode = 2;

        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = null;

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/



        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "-50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "-50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "-5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "-50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "-50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "-5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    public function modifyTaxinvoice03()
    {
        /**
         * 환입에 의한 수정세금계산서 발행
         * - 당초 공급한 재화가 환입(반품)되는 경우 이용하는 수정사유 입니다.
         * - 환입(반품)된 금액 만큼만 수정분 부(-) 세금계산서 발행
         * - 수정세금계산서 가이드: [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         */

        /**
         **************** 환입에 의한 수정세금계산서 예시 ****************
         *  2월 8일 공급가액 30,000원의 세금계산서를 발급했으나, 2월 12일에 10,000원에 해당되는 물품이 환입(반품)된 경우
         *  2월 12일 작성일자로 환입(반품) 금액 10,000원에 대해 환입 사유로 세금계산서를 발행
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        // 환입이 발생한 날 기재
        $Taxinvoice->writeDate = "20230212";

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = "-10000";

        // 세액 합계
        $Taxinvoice->taxTotal = "-1000";

        // 합계금액, 공급가액 + 세액
        $Taxinvoice->totalAmount = "-11000";

        // 비고
        // - 환입에 의한 수정세금계산서 작성의 경우, 원본 = 세금계산서의 작성일자 기재 필수
        $Taxinvoice->remark1 = "20230208";
        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        $Taxinvoice->modifyCode = 3;

        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";

        // 과세형태, [과세, 영세, 면세] 중 기재
        $Taxinvoice->taxType = "과세";

        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-000-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/

        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/


        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = null;

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/

        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    public function modifyTaxinvoice04()
    {
        /**
         * 계약의 해제에 의한 수정세금계산서 발행
         * - 재화 또는 용역/서비스가 공급되지 아니하였거나 계약이 해제된 경우 이용하는 수정사유 입니다.
         * - 원본 전자세금계산서와 동일한 내용의 부(-) 세금계산서 발행
         * - 수정세금계산서 가이드: [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         */

        /**
         **************** 계약의 해제에 의한 수정세금계산서 예시 ****************
         * 2월 13일 공급가액 30,000원의 세금계산서를 발급했으나, 2월 15일에 전체 계약이 해제(취소)된 경우
         * 계약이 취소된 2월 15일을 작성일자로 계약의 해제 사유의 수정세금계산서를 발행
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        $Taxinvoice->writeDate = "20230215";

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = "-30000";

        // 세액 합계
        $Taxinvoice->taxTotal = "-3000";

        // 합계금액, 공급가액 + 세액
        $Taxinvoice->totalAmount = "-33000";

        // 비고
        // 계약의 해제에 의한 수정세금계산서 발행의 경우,  = 본 세금계산서의 작성 일자 기재
        $Taxinvoice->remark1 = "202302 = 3";
        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        $Taxinvoice->modifyCode = 4;

        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";

        // 과세형태, [과세, 영세, 면세] 중 기재
        $Taxinvoice->taxType = "과세";

        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-000-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/

        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/


        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = null;

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/



        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    public function modifyTaxinvoice05minus()
    {
        /**
         * 내국신용장 사후개설에 의한 수정세금계산서 발행
         * - 재화 또는 서비스/용역을 공급한 시기가 속하는 과세기간 종료(1/1~6/30 또는 7/1~12/31) 다음달(7월 또는 1월) 25일 이내에 내국신용장이 개설되었거나 구매확인서가 발급된 경우 이용하는 수정사유 입니다.
         * - 취소분 : 내국신용장이 개설된 품목에 대한 부(-) 세금계산서 발행
         * - 수정분 : 내국신용장이 개설된 품목에 대한 정(+) 영세율 세금계산서 발행
         * - 수정세금계산서 가이드: [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         */

        /**
         **************** 내국신용장 사후개설에 의한 수정세금계산서 예시 (취소분) ****************
         * 3월 13일 공급가액 3,000,000원의 전자세금계산서를 발급한 후, 4월 12일에 일부 품목인 공급가액 1,000,000원에 대해 내국신용장이 개설된 경우
         * 내국 신용장 사후개설된 1,000,000원에 대한 3월 13일 작성일자의 영세율 세금계산서 작성
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        // 원본 세금계산서의 작성일자 기재
        $Taxinvoice->writeDate = "20230313";

        // 과세형태, [과세, 영세, 면세] 중 기재
        // 부 세금계산서의 경우 과세
        $Taxinvoice->taxType = "과세";

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = "-1000000";

        // 세액 합계
        $Taxinvoice->taxTotal = "-100000";

        // 합계금액, 공급가액 + 세액
        $Taxinvoice->totalAmount = "-1100000";

        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        $Taxinvoice->modifyCode = 5;

        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";


        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-000-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/

        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = "비고1";
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/


        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = null;

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/

        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    public function modifyTaxinvoice05plus()
    {
        /**
         * 내국신용장 사후개설에 의한 수정세금계산서 발행
         * - 재화 또는 서비스/용역을 공급한 시기가 속하는 과세기간 종료(1/1~6/30 또는 7/1~12/31) 다음달(7월 또는 1월) 25일 이내에 내국신용장이 개설되었거나 구매확인서가 발급된 경우 이용하는 수정사유 입니다.
         * - 취소분 : 내국신용장이 개설된 품목에 대한 부(-) 세금계산서 발행
         * - 수정분 : 내국신용장이 개설된 품목에 대한 정(+) 영세율 세금계산서 발행
         * - 수정세금계산서 가이드: [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         */

        /**
         **************** 내국신용장 사후개설에 의한 수정세금계산서 예시 (수정분) ****************
         * 3월 13일 공급가액 3,000,000원의 전자세금계산서를 발급한 후, 4월 12일에 일부 품목인 공급가액 1,000,000원에 대해 내국신용장이 개설된 경우
         * 내국 신용장 사후개설된 1,000,000원에 대한 3월 13일 작성일자의 영세율 세금계산서 작성
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        //  원본 세금계산서의 작성 일자
        $Taxinvoice->writeDate = "20230313";

        // 공급가액 합계
        $Taxinvoice->supplyCostTotal = "1000000";

        // 세액 합계
        $Taxinvoice->taxTotal = "100000";

        // 합계금액, 공급가액 + 세액
        $Taxinvoice->totalAmount = "1100000";

        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        $Taxinvoice->modifyCode = 5;

        // 비고
        // - 내국신용장 사후개설에 의한 수정세금계산서 발행 = 시, 비고란에 내국신용장 개설일자 기재
        $Taxinvoice->remark1 = "20230402";
        // 과세형태, [과세, 영세, 면세] 중 기재
        // 내국신용장 개설 품목에 대해 영세율 세금계산서 작성
        $Taxinvoice->taxType = "영세";


        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";


        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-000-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/


        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/


        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = null;

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/

        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }

    public function modifyTaxinvoice06()
    {
        /**
         * 착오에 의한 이중발급에 의한 수정세금계산서 발행
         * - 1건의 거래에 대한 단순 착오로 인해 2건 이상의 전자세금계산서를 발행하거나, 과세형태(과세/영세율↔면세) 착오로 잘못 발급된 경우 이용하는 수정사유 입니다.
         * - 원본 전자세금계산서와 동일한 내용의 수정분 부(-) 세금계산서 발행
         * - 수정세금계산서 가이드: [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         */

        /**
         **************** 착오에 의한 이중발급에 의한 수정세금계산서 예시 ****************
         * 작성일자 2월 16일자에 물건을 납품하고 공급가액 80,000원의 세금계산서를 발급했습니다
         * 그런데 회계담당자의 실수로 동일한 세금계산서를 중복으로 발행 한 경우
         *  [착오에의한 이중발급] 사유로 작성일자 2월 16일, 공급가액 마이너스(-)80,000원의 수정세금계산서를 발급
         */

        // 팝빌 회원 사업자 번호
        $CorpNum = "1234567890";

        $Taxinvoice = new Taxinvoice();

        // 작성일자, 날짜형식(yyyyMMdd)
        // 착오에 의한 이중발급 사유로 수정세금계산서 작성 시, 원본 전자세금계산서 작성일자 기재
        $Taxinvoice->writeDate = "20230701";

        // 공급가액 합계
        // 원본 세금계산서와 동일한 내용의 수정분 부(-) 기재
        $Taxinvoice->supplyCostTotal = "-80000";

        // 세액 합계
        // 원본 세금계산서와 동일한 내용의 수정분 부(-) 기재
        $Taxinvoice->taxTotal = "-8000";

        // 합계금액, 공급가액 + 세액
        // 원본 세금계산서와 동일한 내용의 수정분 부(-) 기재
        $Taxinvoice->totalAmount = "-88000";

        // 수정사유코드, 수정사유에 따라 1~6 중 선택기재.
        // 착오에 의한 이중발급 사유로 수정세금계산서 작성 시, 수정사유코드 6 기재
        $Taxinvoice->modifyCode = 6;

        // 과금방향, [정과금, 역과금] 중 선택기재
        // └ 정과금 = 공급자 과금 , 역과금 = 공급받는자 과금
        // -"역과금"은 역발행 세금계산서 발행 시에만 이용가능
        $Taxinvoice->chargeDirection = "정과금";

        // 발행형태, [정발행, 역발행, 위수탁] 중 기재
        $Taxinvoice->issueType = "정발행";

        // [영수, 청구, 없음] 중 기재
        $Taxinvoice->purposeType = "영수";

        // 과세형태, [과세, 영세, 면세] 중 기재
        $Taxinvoice->taxType = "과세";

        /**********************************************************************
         * 공급자 정보
         *********************************************************************/

        // 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = '1234567890';

        // 공급자 종사업장 식별번호, 필요시 기재. 형식은 숫자 4자리.
        $Taxinvoice->invoicerTaxRegID = "";

        // 공급자 상호
        $Taxinvoice->invoicerCorpName = "공급자 상호";

        // 공급자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Taxinvoice->invoicerMgtKey = "20230102-BOOT001";

        // 공급자 대표자 성명
        $Taxinvoice->invoicerCEOName = "공급자 대표자 성명";

        // 공급자 주소
        $Taxinvoice->invoicerAddr = "공급자 주소";

        // 공급자 종목
        $Taxinvoice->invoicerBizClass = "공급자 종목";

        // 공급자 업태
        $Taxinvoice->invoicerBizType = "공급자 업태,업태2";

        // 공급자 담당자 성명
        $Taxinvoice->invoicerContactName = "공급자 담당자 성명";

        // 공급자 담당자 메일주소
        $Taxinvoice->invoicerEmail = "test@test.com";

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = "070-7070-0707";

        // 공급자 담당자 휴대폰번호
        $Taxinvoice->invoicerHP = "010-000-2222";

        // 발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급받는자 (주)담당자 휴대폰번호 {invoiceeHP1} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /**********************************************************************
         * 공급받는자 정보
         *********************************************************************/

        // 공급받는자 구분, [사업자, 개인, 외국인] 중 기재
        $Taxinvoice->invoiceeType = "사업자";

        // 공급받는자 사업자번호
        // - {invoiceeType}이 "사업자" 인 경우, 사업자번호 (하이픈 ('-') 제외 10자리)
        // - {invoiceeType}이 "개인" 인 경우, 주민등록번호 (하이픈 ('-') 제외 13자리)
        // - {invoiceeType}이 "외국인" 인 경우, "9999999999999" (하이픈 ('-') 제외 13자리)
        $Taxinvoice->invoiceeCorpNum = "8888888888";

        // 공급받는자 종사업장 식별번호, 필요시 숫자4자리 기재
        $Taxinvoice->invoiceeTaxRegID = "";

        // 공급받는자 상호
        $Taxinvoice->invoiceeCorpName = "공급받는자 상호";

        // [역발행시 필수] 공급받는자 문서번호, 1~24자리 (숫자, 영문, '-', '_') 를 조합하여 사업자별로 중복되지 않도록 구성
        $Taxinvoice->invoiceeMgtKey = "";

        // 공급받는자 대표자 성명
        $Taxinvoice->invoiceeCEOName = "공급받는자 대표자 성명";

        // 공급받는자 주소
        $Taxinvoice->invoiceeAddr = "공급받는자 주소";

        // 공급받는자 종목
        $Taxinvoice->invoiceeBizClass = "공급받는자 업종";

        // 공급받는자 업태
        $Taxinvoice->invoiceeBizType = "공급받는자 업태";

        // 공급받는자 담당자 성명
        $Taxinvoice->invoiceeContactName1 = "공급받는자 담당자 성명";

        // 공급받는자 담당자 메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Taxinvoice->invoiceeEmail1 = "test@invoicee.com";

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = "070-111-222";

        // 공급받는자 담당자 휴대폰번호
        $Taxinvoice->invoiceeHP1 = "010-111-222";

        // 역발행 안내 문자 전송여부 (true / false 중 택 1)
        // └ true = 전송 , false = 미전송
        // └ 공급자 담당자 휴대폰번호 {invoicerHP} 값으로 문자 전송
        // - 전송 시 포인트 차감되며, 전송실패시 환불처리
        $Taxinvoice->invoiceeSMSSendYN = false;

        /**********************************************************************
         * 세금계산서 기재정보
         *********************************************************************/

        // 일련번호
        $Taxinvoice->serialNum = "123";

        // 현금
        $Taxinvoice->cash = "";

        // 수표
        $Taxinvoice->chkBill = "";

        // 어음
        $Taxinvoice->note = "";

        // 외상미수금
        $Taxinvoice->credit = "";

        // 비고
        // {invoiceeType}이 "외국인" 이면 remark1 필수
        // - 외국인 등록번호 또는 여권번호 입력
        $Taxinvoice->remark1 = "비고1";
        $Taxinvoice->remark2 = "비고2";
        $Taxinvoice->remark3 = "비고3";

        // 책번호 '권' 항목, 최대값 32767
        $Taxinvoice->kwon = 1;

        // 책번호 '호' 항목, 최대값 32767
        $Taxinvoice->ho = 1;

        // 사업자등록증 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지 첨부여부 (true / false 중 택 1)
        // └ true = 첨부 , false = 미첨부(기본값)
        // - 팝빌 사이트 또는 인감 및 첨부문서 등록 팝업 URL (GetSealURL API) 함수를 이용하여 등록
        $Taxinvoice->bankBookYN = false;

        /**********************************************************************
         * 수정세금계산서 정보 (수정세금계산서 작성시 기재) - 수정세금계산서 작성방법 안내
         * [https://developers.popbill.com/guide/taxinvoice/java/introduction/modified-taxinvoice]
         *********************************************************************/


        // 수정세금계산서 작성시 원본세금계산서 국세청 승인번호 기재
        $Taxinvoice->orgNTSConfirmNum = null;

        /**********************************************************************
         * 상세항목(품목) 정보
         *********************************************************************/
        // 상세항목 객체
        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[0]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[0]->itemName = "품목명"; // 품목명
        $Taxinvoice->detailList[0]->spec = "규격"; // 규격
        $Taxinvoice->detailList[0]->qty = "1"; // 수량
        $Taxinvoice->detailList[0]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[0]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[0]->tax = "5000"; // 세액
        $Taxinvoice->detailList[0]->remark = "품목비고"; // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2; // 일련번호, 1부터 순차기재
        $Taxinvoice->detailList[1]->purchaseDT = "20230102"; // 거래일자
        $Taxinvoice->detailList[1]->itemName = "품목명2"; // 품목명
        $Taxinvoice->detailList[1]->spec = "규격"; // 규격
        $Taxinvoice->detailList[1]->qty = "1"; // 수량
        $Taxinvoice->detailList[1]->unitCost = "50000"; // 단가
        $Taxinvoice->detailList[1]->supplyCost = "50000"; // 공급가액
        $Taxinvoice->detailList[1]->tax = "5000"; // 세액
        $Taxinvoice->detailList[1]->remark = "품목비고2"; // 비고

        // 즉시발행 메모
        $Memo = "수정세금계산서 발행 메모";

        // 지연발행 강제여부  (true / false 중 택 1)
        // └ true = 가능 , false = 불가능
        // - 미입력 시 기본값 false 처리
        // - 발행마감일이 지난 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
        // - 가산세가 부과되더라도 발행을 해야하는 경우에는 forceIssue의 값을
        //   true로 선언하여 발행(Issue API)를 호출하시면 됩니다.
        $ForceIssue = false;

        // 팝빌회원 아이디
        $UserID = "testkorea";

        // 거래명세서 동시작성 여부
        $writeSpecification = false;

        //세금계산서 발행 안내메일 제목
        $emailSubject = "세금계산서 발행 안내메일 제목 ";

        // 거래명세서 문서번호 할당
        // ※ 미입력시 기본값 세금계산서 문서번호와 동일하게 할당
        $dealInvoiceMgtKey = "";

        try {
            $result = $this->PopbillTaxinvoice->RegistIssue(
                $CorpNum,
                $Taxinvoice,
                $UserID,
                $writeSpecification,
                $ForceIssue,
                $Memo,
                $emailSubject,
                $dealInvoiceMgtKey
            );
            $code = $result->code;
            $message = $result->message;
            $ntsConfirmNum = $result->ntsConfirmNum;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $ntsConfirmNum = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
    }
}
