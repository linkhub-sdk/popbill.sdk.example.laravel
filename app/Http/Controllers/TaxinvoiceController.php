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


class TaxinvoiceController extends Controller
{
  public function __construct() {

    // 통신방식 설정
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // 세금계산서 서비스 클래스 초기화
    $this->PopbillTaxinvoice = new PopbillTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillTaxinvoice->IsTest(config('popbill.IsTest'));

    // 인증토큰의 IP제한기능 사용여부, 권장(true)
    $this->PopbillTaxinvoice->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

    // 팝빌 API 서비스 고정 IP 사용여부(GA), true-사용, false-미사용, 기본값(false)
    $this->PopbillTaxinvoice->UseStaticIP(config('popbill.UseStaticIP'));

    // 로컬서버 시간 사용 여부 true(기본값) - 사용, false(미사용)
    $this->PopbillTaxinvoice->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
  }

  // HTTP Get Request URI -> 함수 라우팅 처리 함수
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * 파트너가 세금계산서 관리 목적으로 할당하는 문서번호의 사용여부를 확인합니다.
   * - 문서번호는 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')로 구성 합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#CheckMgtKeyInUse
   */
  public function CheckMgtKeyInUse(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 세금계산서 문서번호, 연동회원 사업자번호 범위에서 중복되지 않는 문서번호 할당
    $mgtKey = '20210712-001';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    try {
        $result = $this->PopbillTaxinvoice->CheckMgtKeyInUse($testCorpNum, $mgtKeyType, $mgtKey);
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
   * 작성된 세금계산서 데이터를 팝빌에 저장과 동시에 발행(전자서명)하여 "발행완료" 상태로 처리합니다.
   * - 세금계산서 국세청 전송 정책 : https://docs.popbill.com/taxinvoice/ntsSendPolicy?lang=phplaravel
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#RegistIssue
   */
  public function RegistIssue(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567899';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $invoicerMgtKey = '20211024-023';

    // 지연발행 강제여부
    $forceIssue = false;

    // 즉시발행 메모
    $memo = '즉시발행 메모';

    // 안내메일 제목, 미기재시 기본제목으로 전송
    $emailSubject = '';

    // 거래명세서 동시작성 여부
    $writeSpecification = false;

    // 거래명세서 동시작성시 명세서 문서번호
    // 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $dealInvoiceMgtKey = '';

    /************************************************************
     *                        세금계산서 정보
     ************************************************************/
    // 세금계산서 객체 생성
    $Taxinvoice = new Taxinvoice();

    // [필수] 작성일자, 형식(yyyyMMdd) 예)20150101
    $Taxinvoice->writeDate = '20210701';

    // [필수] 발행형태, '정발행', '역발행', '위수탁' 중 기재
    $Taxinvoice->issueType = '정발행';

    // [필수] 과금방향,
    // - '정과금'(공급자 과금), '역과금'(공급받는자 과금) 중 기재, 역과금은 역발행시에만 가능.
    $Taxinvoice->chargeDirection = '정과금';

    // [필수] '영수', '청구' 중 기재
    $Taxinvoice->purposeType = '영수';

    // [필수] 과세형태, '과세', '영세', '면세' 중 기재
    $Taxinvoice->taxType = '과세';


    /************************************************************
     *                         공급자 정보
     ************************************************************/
    // [필수] 공급자 사업자번호
    $Taxinvoice->invoicerCorpNum = $testCorpNum;

    // 공급자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoicerTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoicerCorpName = '공급자상호';

    // [필수] 공급자 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $Taxinvoice->invoicerMgtKey = $invoicerMgtKey;

    // [필수] 공급자 대표자성명
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
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // 공급자 담당자 연락처
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // 공급자 휴대폰 번호
    $Taxinvoice->invoicerHP = '010-111-222';

    // 발행시 알림문자 전송여부 (정발행에서만 사용가능)
    // - 공급받는자 주)담당자 휴대폰번호(invoiceeHP1)로 전송
    // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
    $Taxinvoice->invoicerSMSSendYN = false;

    /************************************************************
     *                      공급받는자 정보
     ************************************************************/

    // [필수] 공급받는자 구분, '사업자', '개인', '외국인' 중 기재
    $Taxinvoice->invoiceeType = '사업자';

    // [필수] 공급받는자 사업자번호
    $Taxinvoice->invoiceeCorpNum = '8888888888';

    // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoiceeTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

    // [역발행시 필수] 공급받는자 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $Taxinvoice->invoiceeMgtKey = '';

    // [필수] 공급받는자 대표자성명
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
    $Taxinvoice->invoiceeEmail1 = 'test@test.com';

    // 공급받는자 담당자 연락처
    $Taxinvoice->invoiceeTEL1 = '070-111-222';

    // 공급받는자 담당자 휴대폰 번호
    $Taxinvoice->invoiceeHP1 = '010-111-222';

    /************************************************************
     *                       세금계산서 기재정보
     ************************************************************/
    // [필수] 공급가액 합계
    $Taxinvoice->supplyCostTotal = '200000';

    // [필수] 세액 합계
    $Taxinvoice->taxTotal = '20000';

    // [필수] 합계금액, (공급가액 합계 + 세액 합계)
    $Taxinvoice->totalAmount = '220000';

    // 기재상 '일련번호'항목
    $Taxinvoice->serialNum = '123';

    // 기재상 '현금'항목
    $Taxinvoice->cash = '';

    // 기재상 '수표'항목
    $Taxinvoice->chkBill = '';

    // 기재상 '어음'항목
    $Taxinvoice->note = '';

    // 기재상 '외상'항목
    $Taxinvoice->credit = '';

    // 기재상 '비고' 항목
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // 기재상 '권' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->kwon = 'null';
    $Taxinvoice->kwon = '1';

    // 기재상 '호' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->ho = 'null';
    $Taxinvoice->ho = '1';

    // 사업자등록증 이미지파일 첨부여부
    $Taxinvoice->businessLicenseYN = false;

    // 통장사본 이미지파일 첨부여부
    $Taxinvoice->bankBookYN = false;

    /************************************************************
     *                     수정 세금계산서 기재정보
     * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
     * - [참고] 수정세금계산서 작성방법 안내 - https://docs.popbill.com/taxinvoice/modify?lang=phplaravel
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
    $Taxinvoice->detailList[0]->serialNum = 1;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[0]->purchaseDT = '20210226';	  // 거래일자
    $Taxinvoice->detailList[0]->itemName = '품목명1번';	  	// 품명
    $Taxinvoice->detailList[0]->spec = '';				      // 규격
    $Taxinvoice->detailList[0]->qty = '';					        // 수량
    $Taxinvoice->detailList[0]->unitCost = '';		    // 단가
    $Taxinvoice->detailList[0]->supplyCost = '100000';		  // 공급가액
    $Taxinvoice->detailList[0]->tax = '10000';				      // 세액
    $Taxinvoice->detailList[0]->remark = '';		    // 비고

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[1]->purchaseDT = '20210226';	  // 거래일자
    $Taxinvoice->detailList[1]->itemName = '품목명2번';	  	// 품명
    $Taxinvoice->detailList[1]->spec = '';				      // 규격
    $Taxinvoice->detailList[1]->qty = '';					        // 수량
    $Taxinvoice->detailList[1]->unitCost = '';		    // 단가
    $Taxinvoice->detailList[1]->supplyCost = '100000';		  // 공급가액
    $Taxinvoice->detailList[1]->tax = '10000';				      // 세액
    $Taxinvoice->detailList[1]->remark = '';		    // 비고

    /************************************************************
     *                      추가담당자 정보
     * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
     * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
     ************************************************************/
    $Taxinvoice->addContactList = array();
    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[0]->serialNum = 1;				        // 일련번호 1부터 순차기재
    $Taxinvoice->addContactList[0]->email = 'test@test.com';	    // 이메일주소
    $Taxinvoice->addContactList[0]->contactName	= '팝빌담당자';		// 담당자명

    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[1]->serialNum = 2;			        	// 일련번호 1부터 순차기재
    $Taxinvoice->addContactList[1]->email = 'test@test.com';	    // 이메일주소
    $Taxinvoice->addContactList[1]->contactName	= '링크허브';		  // 담당자명

    try {
        $result = $this->PopbillTaxinvoice->RegistIssue($testCorpNum, $Taxinvoice, $testUserID,
            $writeSpecification, $forceIssue, $memo, $emailSubject, $dealInvoiceMgtKey);
        $code = $result->code;
        $message = $result->message;
        $ntsConfirmNum = $result->ntsConfirmNum;
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        $ntsConfirmNum = null;
    }

    return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
  }

  /**
  * 최대 100건의 세금계산서 발행을 한번의 요청으로 접수합니다.
  * - https://docs.popbill.com/taxinvoice/phplaravel/api#BulkSubmit
  */
  public function BulkSubmit(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 제출 아이디 ,최대 36자리 영문, 숫자, '-' 조합으로 구성
    $submitID = 'Laravel-Bulk00';

    // 지연발행 강제 여부
    $fourceIssue = false;

    // 세금계산서 객체정보 배열
    $taxinvoiceList = [];

    for($i=0; $i<100; $i++){
        /************************************************************
        *                        세금계산서 정보
        ************************************************************/
        // 세금계산서 객체 생성
        $Taxinvoice = new Taxinvoice();

        // [필수] 작성일자, 형식(yyyyMMdd) 예)20150101
        $Taxinvoice->writeDate = '20210705';

        // [필수] 발행형태, '정발행', '역발행', '위수탁' 중 기재
        $Taxinvoice->issueType = '정발행';

        // [필수] 과금방향,
        // - '정과금'(공급자 과금), '역과금'(공급받는자 과금) 중 기재, 역과금은 역발행시에만 가능.
        $Taxinvoice->chargeDirection = '정과금';

            // [필수] '영수', '청구' 중 기재
        $Taxinvoice->purposeType = '영수';

        // [필수] 과세형태, '과세', '영세', '면세' 중 기재
        $Taxinvoice->taxType = '과세';

        /************************************************************
         *                         공급자 정보
         ************************************************************/

        // [필수] 공급자 사업자번호
        $Taxinvoice->invoicerCorpNum = $testCorpNum;

        // 공급자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoicerTaxRegID = '';

        // [필수] 공급자 상호
        $Taxinvoice->invoicerCorpName = 'BulkTEST';

        // [필수] 공급자 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
        $Taxinvoice->invoicerMgtKey = $submitID . $i;

        // [필수] 공급자 대표자성명
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
        $Taxinvoice->invoicerEmail = 'tester@test.com';

        // 공급자 담당자 연락처
        $Taxinvoice->invoicerTEL = '070-4304-2991';

        // 공급자 휴대폰 번호
        $Taxinvoice->invoicerHP = '010-111-222';

        // 발행시 알림문자 전송여부 (정발행에서만 사용가능)
        // - 공급받는자 주)담당자 휴대폰번호(invoiceeHP1)로 전송
        // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
        $Taxinvoice->invoicerSMSSendYN = false;

        /************************************************************
         *                      공급받는자 정보
         ************************************************************/

        // [필수] 공급받는자 구분, '사업자', '개인', '외국인' 중 기재
        $Taxinvoice->invoiceeType = '사업자';

        // [필수] 공급받는자 사업자번호
        $Taxinvoice->invoiceeCorpNum = '8888888888';

        // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
        $Taxinvoice->invoiceeTaxRegID = '';

        // [필수] 공급자 상호
        $Taxinvoice->invoiceeCorpName = 'BulkTEST';

        // [역발행시 필수] 공급받는자 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
        $Taxinvoice->invoiceeMgtKey = '';

        // [필수] 공급받는자 대표자성명
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
        $Taxinvoice->invoiceeEmail1 = 'test@test.com';

        // 공급받는자 담당자 연락처
        $Taxinvoice->invoiceeTEL1 = '070-111-222';

        // 공급받는자 담당자 휴대폰 번호
        $Taxinvoice->invoiceeHP1 = '010-111-222';


        /************************************************************
         *                       세금계산서 기재정보
         ************************************************************/

        // [필수] 공급가액 합계
        $Taxinvoice->supplyCostTotal = '200000';

        // [필수] 세액 합계
        $Taxinvoice->taxTotal = '20000';

        // [필수] 합계금액, (공급가액 합계 + 세액 합계)
        $Taxinvoice->totalAmount = '220000';

        // 기재상 '일련번호'항목
        $Taxinvoice->serialNum = '123';

        // 기재상 '현금'항목
        $Taxinvoice->cash = '';

        // 기재상 '수표'항목
        $Taxinvoice->chkBill = '';
        // 기재상 '어음'항목
        $Taxinvoice->note = '';

        // 기재상 '외상'항목
        $Taxinvoice->credit = '';

        // 기재상 '비고' 항목
        $Taxinvoice->remark1 = '비고1';
        $Taxinvoice->remark2 = '비고2';
        $Taxinvoice->remark3 = '비고3';

        // 기재상 '권' 항목, 최대값 32767
        // 미기재시 $Taxinvoice->kwon = 'null';
        $Taxinvoice->kwon = '1';

        // 기재상 '호' 항목, 최대값 32767
        // 미기재시 $Taxinvoice->ho = 'null';
        $Taxinvoice->ho = '1';

        // 사업자등록증 이미지파일 첨부여부
        $Taxinvoice->businessLicenseYN = false;

        // 통장사본 이미지파일 첨부여부
        $Taxinvoice->bankBookYN = false;

        /************************************************************
         *                     수정 세금계산서 기재정보
         * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
         * - [참고] 수정세금계산서 작성방법 안내 - https://docs.popbill.com/taxinvoice/modify?lang=php
         ************************************************************/

        // 수정사유코드, 수정사유에 따라 1~6중 선택기재
        // $Taxinvoice->modifyCode = '2';
      //
        // 원본세금계산서의 국세청 승인번호 기재
        // $Taxinvoice->orgNTSConfirmNum = '';


        /************************************************************
         *                       상세항목(품목) 정보
         ************************************************************/

        $Taxinvoice->detailList = array();

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[0]->serialNum = 1;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[0]->purchaseDT = '20210701';	  // 거래일자
        $Taxinvoice->detailList[0]->itemName = '품목명1번';	  	// 품명
        $Taxinvoice->detailList[0]->spec = '';				      // 규격
        $Taxinvoice->detailList[0]->qty = '';					        // 수량
        $Taxinvoice->detailList[0]->unitCost = '';		    // 단가
        $Taxinvoice->detailList[0]->supplyCost = '100000';		  // 공급가액
        $Taxinvoice->detailList[0]->tax = '10000';				      // 세액
        $Taxinvoice->detailList[0]->remark = '';		    // 비고

        $Taxinvoice->detailList[] = new TaxinvoiceDetail();
        $Taxinvoice->detailList[1]->serialNum = 2;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
        $Taxinvoice->detailList[1]->purchaseDT = '20210701';	  // 거래일자
        $Taxinvoice->detailList[1]->itemName = '품목명2번';	  	// 품명
        $Taxinvoice->detailList[1]->spec = '';				      // 규격
        $Taxinvoice->detailList[1]->qty = '';					        // 수량
        $Taxinvoice->detailList[1]->unitCost = '';		    // 단가
        $Taxinvoice->detailList[1]->supplyCost = '100000';		  // 공급가액
        $Taxinvoice->detailList[1]->tax = '10000';				      // 세액
        $Taxinvoice->detailList[1]->remark = '';		    // 비고



        /************************************************************
         *                      추가담당자 정보
         * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
         * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
         ************************************************************/

        $Taxinvoice->addContactList = array();

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[0]->serialNum = 1;				        // 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[0]->email = 'test@test.com';	    // 이메일주소
        $Taxinvoice->addContactList[0]->contactName	= '팝빌담당자';		// 담당자명

        $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
        $Taxinvoice->addContactList[1]->serialNum = 2;			        	// 일련번호 1부터 순차기재
        $Taxinvoice->addContactList[1]->email = 'test@test.com';	    // 이메일주소
        $Taxinvoice->addContactList[1]->contactName	= '링크허브';		  // 담당자명

        // 세금계산서 추가
        $taxinvoiceList[] = $Taxinvoice;
    }

    try {
        $result = $this->PopbillTaxinvoice->BulkSubmit($testCorpNum, $submitID, $taxinvoiceList, $fourceIssue);
        $code = $result->code;
        $message = $result->message;
        $receiptID = $result->receiptID;
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('/PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('/PResponse', ['code' => $code, 'message' => $message, 'receiptID' => $receiptID]);
  }

  public function getBulkResult(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 초대량 발행 접수시 기재한 제출 아이디
    $submitID = 'Laravel-Bulk00';

    try {
        $result = $this->PopbillTaxinvoice->GetBulkResult($testCorpNum, $submitID);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('/PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('/Taxinvoice/GetBulkResult', ['Result' => $result]);
  }

  /**
   * 작성된 세금계산서 데이터를 팝빌에 저장합니다.
   * - "임시저장" 상태의 세금계산서는 발행(Issue)함수를 호출하여 "발행완료" 처리한 경우에만 국세청으로 전송됩니다.
   * - 정발행시 임시저장(Register)과 발행(Issue)을 한번의 호출로 처리하는 즉시발행(RegistIssue API) 프로세스 연동을 권장합니다.
   * - 역발행시 임시저장(Register)과 역발행요청(Request)을 한번의 호출로 처리하는 즉시요청(RegistRequest API) 프로세스 연동을 권장합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#Register
   */
  public function Register(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 세금계산서 문서번호
    // - 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $invoicerMgtKey = '20210701-024';

    /************************************************************
     *                        세금계산서 정보
     ************************************************************/

    // 세금계산서 객체 생성
    $Taxinvoice = new Taxinvoice();

    // [필수] 작성일자, 형식(yyyyMMdd) 예)20150101
    $Taxinvoice->writeDate = '20210701';

    // [필수] 발행형태, '정발행', '역발행', '위수탁' 중 기재
    $Taxinvoice->issueType = '정발행';

    // [필수] 과금방향,
    // - '정과금'(공급자 과금), '역과금'(공급받는자 과금) 중 기재, 역과금은 역발행시에만 가능.
    $Taxinvoice->chargeDirection = '정과금';

    // [필수] '영수', '청구' 중 기재
    $Taxinvoice->purposeType = '영수';

    // [필수] 과세형태, '과세', '영세', '면세' 중 기재
    $Taxinvoice->taxType = '과세';

    /************************************************************
     *                         공급자 정보
     ************************************************************/

    // [필수] 공급자 사업자번호
    $Taxinvoice->invoicerCorpNum = $testCorpNum;

    // 공급자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoicerTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoicerCorpName = '공급자상호';

    // [필수] 공급자 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $Taxinvoice->invoicerMgtKey = $invoicerMgtKey;

    // [필수] 공급자 대표자성명
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
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // 공급자 담당자 연락처
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // 공급자 휴대폰 번호
    $Taxinvoice->invoicerHP = '010-0000-0000';

    // 발행시 알림문자 전송여부 (정발행에서만 사용가능)
    // - 공급받는자 주)담당자 휴대폰번호(invoiceeHP1)로 전송
    // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
    $Taxinvoice->invoicerSMSSendYN = false;

    /************************************************************
     *                      공급받는자 정보
     ************************************************************/

    // [필수] 공급받는자 구분, '사업자', '개인', '외국인' 중 기재
    $Taxinvoice->invoiceeType = '사업자';

    // [필수] 공급받는자 사업자번호
    $Taxinvoice->invoiceeCorpNum = '8888888888';

    // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoiceeTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

    // [역발행시 필수] 공급받는자 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $Taxinvoice->invoiceeMgtKey = '';

    // [필수] 공급받는자 대표자성명
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
    $Taxinvoice->invoiceeEmail1 = 'tester@test.com';

    // 공급받는자 담당자 연락처
    $Taxinvoice->invoiceeTEL1 = '070-0000-0000';

    // 공급받는자 담당자 휴대폰 번호
    $Taxinvoice->invoiceeHP1 = '010-0000-0000';

    // 역발행 요청시 알림문자 전송여부 (역발행에서만 사용가능)
    // - 공급자 담당자 휴대폰번호(invoicerHP)로 전송
    // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
    $Taxinvoice->invoiceeSMSSendYN = false;

    /************************************************************
     *                       세금계산서 기재정보
     ************************************************************/
    // [필수] 공급가액 합계
    $Taxinvoice->supplyCostTotal = '200000';

    // [필수] 세액 합계
    $Taxinvoice->taxTotal = '20000';

    // [필수] 합계금액, (공급가액 합계 + 세액 합계)
    $Taxinvoice->totalAmount = '220000';

    // 기재상 '일련번호'항목
    $Taxinvoice->serialNum = '123';

    // 기재상 '현금'항목
    $Taxinvoice->cash = '';

    // 기재상 '수표'항목
    $Taxinvoice->chkBill = '';

    // 기재상 '어음'항목
    $Taxinvoice->note = '';

    // 기재상 '외상'항목
    $Taxinvoice->credit = '';

    // 기재상 '비고' 항목
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // 기재상 '권' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->kwon = 'null';
    $Taxinvoice->kwon = '1';

    // 기재상 '호' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->ho = 'null';
    $Taxinvoice->ho = '1';

    // 사업자등록증 이미지파일 첨부여부
    $Taxinvoice->businessLicenseYN = false;

    // 통장사본 이미지파일 첨부여부
    $Taxinvoice->bankBookYN = false;

    /************************************************************
     *                     수정 세금계산서 기재정보
     * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
     * - [참고] 수정세금계산서 작성방법 안내 - https://docs.popbill.com/taxinvoice/modify?lang=phplaravel
     ************************************************************/

    // 수정사유코드, 수정사유에 따라 1~6중 선택기재
    //$Taxinvoice->modifyCode = '';

    // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
    //$Taxinvoice->orgNTSConfirmNUm = '';

    /************************************************************
     *                       상세항목(품목) 정보
     ************************************************************/
    $Taxinvoice->detailList = array();
    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[0]->serialNum = 1;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[0]->purchaseDT = '20210701';	  // 거래일자
    $Taxinvoice->detailList[0]->itemName = '품목명1번';	  	// 품명
    $Taxinvoice->detailList[0]->spec = '';				      // 규격
    $Taxinvoice->detailList[0]->qty = '';					        // 수량
    $Taxinvoice->detailList[0]->unitCost = '';		    // 단가
    $Taxinvoice->detailList[0]->supplyCost = '100000';		  // 공급가액
    $Taxinvoice->detailList[0]->tax = '10000';				      // 세액
    $Taxinvoice->detailList[0]->remark = '';		    // 비고

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[1]->purchaseDT = '20210701';	  // 거래일자
    $Taxinvoice->detailList[1]->itemName = '품목명2번';	  	// 품명
    $Taxinvoice->detailList[1]->spec = '';				      // 규격
    $Taxinvoice->detailList[1]->qty = '';					        // 수량
    $Taxinvoice->detailList[1]->unitCost = '';		    // 단가
    $Taxinvoice->detailList[1]->supplyCost = '100000';		  // 공급가액
    $Taxinvoice->detailList[1]->tax = '10000';				      // 세액
    $Taxinvoice->detailList[1]->remark = '';		    // 비고

    /************************************************************
     *                      추가담당자 정보
     * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
     * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
     ************************************************************/
    $Taxinvoice->addContactList = array();
    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[0]->serialNum = 1;				        // 일련번호 1부터 순차기재
    $Taxinvoice->addContactList[0]->email = 'test@test.com';	    // 이메일주소
    $Taxinvoice->addContactList[0]->contactName	= '팝빌담당자';		// 담당자명

    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[1]->serialNum = 2;			        	// 일련번호 1부터 순차기재
    $Taxinvoice->addContactList[1]->email = 'test@test.com';	    // 이메일주소
    $Taxinvoice->addContactList[1]->contactName	= '링크허브';		  // 담당자명

    // 전자거래명세서 동시작성 여부
    $writeSpecification = false;

    try {
        $result = $this->PopbillTaxinvoice->Register($testCorpNum, $Taxinvoice);
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
   * "임시저장" 상태의 세금계산서를 수정합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#Update
   */
  public function Update(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $mgtKey = '20210701-002';

    /************************************************************
     *                        세금계산서 정보
     ************************************************************/

    // 세금계산서 객체 생성
    $Taxinvoice = new Taxinvoice();

    // [필수] 작성일자, 형식(yyyyMMdd) 예)20150101
    $Taxinvoice->writeDate = '20210701';

    // [필수] 발행형태, '정발행', '역발행', '위수탁' 중 기재
    $Taxinvoice->issueType = '정발행';

    // [필수] 과금방향,
    // - '정과금'(공급자 과금), '역과금'(공급받는자 과금) 중 기재, 역과금은 역발행시에만 가능.
    $Taxinvoice->chargeDirection = '정과금';

    // [필수] '영수', '청구' 중 기재
    $Taxinvoice->purposeType = '영수';

    // [필수] 과세형태, '과세', '영세', '면세' 중 기재
    $Taxinvoice->taxType = '과세';

    /************************************************************
     *                         공급자 정보
     ************************************************************/

    // [필수] 공급자 사업자번호
    $Taxinvoice->invoicerCorpNum = $testCorpNum;

    // 공급자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoicerTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoicerCorpName = '공급자상호_수정';

    // [필수] 공급자 문서번호, 최대 24자리 숫자, 영문, '-', '_' 조합으로 사업자별로 중복되지 않도록 구성
    $Taxinvoice->invoicerMgtKey = $mgtKey;

    // [필수] 공급자 대표자성명
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
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // 공급자 담당자 연락처
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // 공급자 휴대폰 번호
    $Taxinvoice->invoicerHP = '010-0000-0000';

    // 발행시 알림문자 전송여부 (정발행에서만 사용가능)
    // - 공급받는자 주)담당자 휴대폰번호(invoiceeHP1)로 전송
    // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
    $Taxinvoice->invoicerSMSSendYN = false;

    /************************************************************
     *                      공급받는자 정보
     ************************************************************/

    // [필수] 공급받는자 구분, '사업자', '개인', '외국인' 중 기재
    $Taxinvoice->invoiceeType = '사업자';

    // [필수] 공급받는자 사업자번호
    $Taxinvoice->invoiceeCorpNum = '8888888888';

    // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoiceeTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호_수정';

    // [역발행시 필수] 공급받는자 문서번호, 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $Taxinvoice->invoiceeMgtKey = '';

    // [필수] 공급받는자 대표자성명
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
    $Taxinvoice->invoiceeEmail1 = 'tester@test.com';

    // 공급받는자 담당자 연락처
    $Taxinvoice->invoiceeTEL1 = '070-0000-0000';

    // 공급받는자 담당자 휴대폰 번호
    $Taxinvoice->invoiceeHP1 = '010-0000-0000';

    // 역발행 요청시 알림문자 전송여부 (역발행에서만 사용가능)
    // - 공급자 담당자 휴대폰번호(invoicerHP)로 전송
    // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
    $Taxinvoice->invoiceeSMSSendYN = false;

    /************************************************************
     *                       세금계산서 기재정보
     ************************************************************/

    // [필수] 공급가액 합계
    $Taxinvoice->supplyCostTotal = '200000';

    // [필수] 세액 합계
    $Taxinvoice->taxTotal = '20000';

    // [필수] 합계금액, (공급가액 합계 + 세액 합계)
    $Taxinvoice->totalAmount = '220000';

    // 기재상 '일련번호'항목
    $Taxinvoice->serialNum = '123';

    // 기재상 '현금'항목
    $Taxinvoice->cash = '';

    // 기재상 '수표'항목
    $Taxinvoice->chkBill = '';

    // 기재상 '어음'항목
    $Taxinvoice->note = '';

    // 기재상 '외상'항목
    $Taxinvoice->credit = '';

    // 기재상 '비고' 항목
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // 기재상 '권' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->kwon = 'null';
    $Taxinvoice->kwon = '1';

    // 기재상 '호' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->ho = 'null';
    $Taxinvoice->ho = '1';

    // 사업자등록증 이미지파일 첨부여부
    $Taxinvoice->businessLicenseYN = false;

    // 통장사본 이미지파일 첨부여부
    $Taxinvoice->bankBookYN = false;

    /************************************************************
     *                     수정 세금계산서 기재정보
     * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
     * - [참고] 수정세금계산서 작성방법 안내 - https://docs.popbill.com/taxinvoice/modify?lang=phplaravel
     ************************************************************/

    // 수정사유코드, 수정사유에 따라 1~6중 선택기재
    //$Taxinvoice->modifyCode = '';

    // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
    //$Taxinvoice->orgNTSConfirmNUm = '';

    /************************************************************
     *                       상세항목(품목) 정보
     ************************************************************/

    $Taxinvoice->detailList = array();
    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[0]->serialNum = 1;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[0]->purchaseDT = '20210701';	  // 거래일자
    $Taxinvoice->detailList[0]->itemName = '품목명1번';	  	// 품명
    $Taxinvoice->detailList[0]->spec = '';				      // 규격
    $Taxinvoice->detailList[0]->qty = '';					        // 수량
    $Taxinvoice->detailList[0]->unitCost = '';		    // 단가
    $Taxinvoice->detailList[0]->supplyCost = '100000';		  // 공급가액
    $Taxinvoice->detailList[0]->tax = '10000';				      // 세액
    $Taxinvoice->detailList[0]->remark = '';		    // 비고

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;				      // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[1]->purchaseDT = '20210701';	  // 거래일자
    $Taxinvoice->detailList[1]->itemName = '품목명2번';	  	// 품명
    $Taxinvoice->detailList[1]->spec = '';				      // 규격
    $Taxinvoice->detailList[1]->qty = '';					        // 수량
    $Taxinvoice->detailList[1]->unitCost = '';		    // 단가
    $Taxinvoice->detailList[1]->supplyCost = '100000';		  // 공급가액
    $Taxinvoice->detailList[1]->tax = '10000';				      // 세액
    $Taxinvoice->detailList[1]->remark = '';		    // 비고

    /************************************************************
     *                      추가담당자 정보
     * - 세금계산서 발행안내 메일을 수신받을 공급받는자 담당자가 다수인 경우
     * 추가 담당자 정보를 등록하여 발행안내메일을 다수에게 전송할 수 있습니다. (최대 5명)
     ************************************************************/
    $Taxinvoice->addContactList = array();
    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[0]->serialNum = 1;				        // 일련번호 1부터 순차기재
    $Taxinvoice->addContactList[0]->email = 'test@test.com';	    // 이메일주소
    $Taxinvoice->addContactList[0]->contactName	= '팝빌담당자';		// 담당자명

    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[1]->serialNum = 2;			        	// 일련번호 1부터 순차기재
    $Taxinvoice->addContactList[1]->email = 'test@test.com';	    // 이메일주소
    $Taxinvoice->addContactList[1]->contactName	= '링크허브';		  // 담당자명

    try {
      $result = $this->PopbillTaxinvoice->Update($testCorpNum, $mgtKeyType, $mgtKey, $Taxinvoice);
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
   * "임시저장" 또는 "(역)발행대기" 상태의 세금계산서를 발행(전자서명)하며, "발행완료" 상태로 처리합니다.
   * - 세금계산서 국세청 전송정책 : https://docs.popbill.com/taxinvoice/ntsSendPolicy?lang=php
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#TIIssue
   */
  public function Issue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210701-024';

    // 메모
    $memo = '발행 메모입니다';

    // 지연발행 강제여부
    // 지연발행 세금계산서를 발행하는 경우, 가산세가 부과될 수 있습니다.
    // 지연발행 세금계산서를 신고해야 하는 경우 $forceIssue 값을 true 선언하여 발행(Issue API)을 호출할 수 있습니다.
    $forceIssue = false;

    // 발행 안내메일 제목, 미기재시 기본제목으로 전송
    $EmailSubject = null;

    try {
        $result = $this->PopbillTaxinvoice->Issue($testCorpNum, $mgtKeyType, $mgtKey, $memo, $EmailSubject, $forceIssue);
        $code = $result->code;
        $message = $result->message;
        $ntsConfirmNum = $result->ntsConfirmNum;
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        $ntsConfirmNum = null;
    }

    return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
  }

  /**
   * 국세청 전송 이전 "발행완료" 상태의 전자세금계산서를 "발행취소"하고, 해당 건은 국세청 신고 대상에서 제외됩니다.
   * - Delete(삭제)함수를 호출하여 "발행취소" 상태의 전자세금계산서를 삭제하면, 문서번호 재사용이 가능합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#CancelIssue
   */
  public function CancelIssue(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210701-002';

    // 메모
    $memo = '발행 취소메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->CancelIssue($testCorpNum, $mgtKeyType, $mgtKey, $memo);
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
   * 삭제 가능한 상태의 세금계산서를 삭제합니다.
   * - 삭제 가능한 상태: "임시저장", "발행취소", "역발행거부", "역발행취소", "전송실패"
   * - 세금계산서를 삭제해야만 문서번호(mgtKey)를 재사용할 수 있습니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#Delete
   */
  public function Delete(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 문서번호
    $mgtKey = '20210702-002';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    try {
        $result = $this->PopbillTaxinvoice->Delete($testCorpNum, $mgtKeyType, $mgtKey);
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
   * 공급받는자가 작성한 세금계산서 데이터를 팝빌에 저장하고 공급자에게 송부하여 발행을 요청합니다.
   * - 역발행 세금계산서 프로세스를 구현하기위해서는 공급자/공급받는자가 모두 팝빌에 회원이여야 합니다.
   * - 역발행 즉시요청후 공급자가 [발행] 처리시 포인트가 차감되며 역발행 세금계산서 항목중 과금방향(ChargeDirection)에 기재한 값에 따라 정과금(공급자과금) 또는 역과금(공급받는자과금) 처리됩니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#RegistRequest
   */
  public function RegistRequest(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 공급받는자 문서번호
    // - 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $invoiceeMgtKey = '20210701-005';

    /************************************************************
     *                        세금계산서 정보
     ************************************************************/

    // 세금계산서 객체 생성
    $Taxinvoice = new Taxinvoice();

    // [필수] 작성일자, 형식(yyyyMMdd) 예)20150101
    $Taxinvoice->writeDate = '20210701';

    // [필수] 발행형태, '정발행', '역발행', '위수탁' 중 기재
    $Taxinvoice->issueType = '역발행';

    // [필수] 과금방향,
    // - '정과금'(공급자 과금), '역과금'(공급받는자 과금) 중 기재, 역과금은 역발행시에만 가능.
    $Taxinvoice->chargeDirection = '정과금';

    // [필수] '영수', '청구' 중 기재
    $Taxinvoice->purposeType = '영수';

    // [필수] 과세형태, '과세', '영세', '면세' 중 기재
    $Taxinvoice->taxType = '과세';

    /************************************************************
     *                         공급자 정보
     ************************************************************/

    // [필수] 공급자 사업자번호
    $Taxinvoice->invoicerCorpNum = '8888888888';

    // 공급자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoicerTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoicerCorpName = '공급자상호';

    // 공급자 문서번호,
    // 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $Taxinvoice->invoicerMgtKey = '';

    // [필수] 공급자 대표자성명
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
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // 공급자 담당자 연락처
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // 공급자 휴대폰 번호
    $Taxinvoice->invoicerHP = '010-111-222';

    /************************************************************
     *                      공급받는자 정보
     ************************************************************/

    // [필수] 공급받는자 구분, '사업자', '개인', '외국인' 중 기재
    $Taxinvoice->invoiceeType = '사업자';

    // [필수] 공급받는자 사업자번호
    $Taxinvoice->invoiceeCorpNum = $testCorpNum;

    // 공급받는자 종사업장 식별번호, 4자리 숫자 문자열
    $Taxinvoice->invoiceeTaxRegID = '';

    // [필수] 공급자 상호
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

    // [역발행시 필수] 공급받는자 문서번호,
    // 최대 24자리 영문 대소문자, 숫자, 특수문자('-','_')만 이용 가능
    $Taxinvoice->invoiceeMgtKey = $invoiceeMgtKey;

    // [필수] 공급받는자 대표자성명
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
    $Taxinvoice->invoiceeEmail1 = 'test@test.com';

    // 공급받는자 담당자 연락처
    $Taxinvoice->invoiceeTEL1 = '070-111-222';

    // 공급받는자 담당자 휴대폰 번호
    $Taxinvoice->invoiceeHP1 = '010-111-222';

    // 역발행 요청시 알림문자 전송여부 (역발행에서만 사용가능)
    // - 공급자 담당자 휴대폰번호(invoicerHP)로 전송
    // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
    $Taxinvoice->invoiceeSMSSendYN = false;

    /************************************************************
     *                       세금계산서 기재정보
     ************************************************************/
    // [필수] 공급가액 합계
    $Taxinvoice->supplyCostTotal = '200000';

    // [필수] 세액 합계
    $Taxinvoice->taxTotal = '20000';

    // [필수] 합계금액, (공급가액 합계 + 세액 합계)
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

    // 기재상 '비고' 항목
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // 기재상 '권' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->kwon = 'null';
    $Taxinvoice->kwon = '1';

    // 기재상 '호' 항목, 최대값 32767
    // 미기재시 $Taxinvoice->ho = 'null';
    $Taxinvoice->ho = '1';

    // 사업자등록증 이미지파일 첨부여부
    $Taxinvoice->businessLicenseYN = false;

    // 통장사본 이미지파일 첨부여부
    $Taxinvoice->bankBookYN = false;

    /************************************************************
     *                     수정 세금계산서 기재정보
     * - 수정세금계산서 관련 정보는 연동매뉴얼 또는 개발가이드 링크 참조
     * - [참고] 수정세금계산서 작성방법 안내 - https://docs.popbill.com/taxinvoice/modify?lang=phplaravel
     ************************************************************/
    // 수정사유코드, 수정사유에 따라 1~6중 선택기재
    //$Taxinvoice->modifyCode = '';

    // [수정세금계산서 작성시 필수] 원본세금계산서 국세청 승인번호 기재
    //$Taxinvoice->orgNTSConfirmNUm = '';

    /************************************************************
     *                       상세항목(품목) 정보
     ************************************************************/
    $Taxinvoice->detailList = array();
    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[0]->serialNum = 1;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[0]->purchaseDT = '20210701';     // 거래일자
    $Taxinvoice->detailList[0]->itemName = '품목명1번';        // 품명
    $Taxinvoice->detailList[0]->spec = '';                   // 규격
    $Taxinvoice->detailList[0]->qty = '';                    // 수량
    $Taxinvoice->detailList[0]->unitCost = '';               // 단가
    $Taxinvoice->detailList[0]->supplyCost = '100000';       // 공급가액
    $Taxinvoice->detailList[0]->tax = '10000';               // 세액
    $Taxinvoice->detailList[0]->remark = '';                 // 비고

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;               // [상세항목 배열이 있는 경우 필수] 일련번호 1~99까지 순차기재,
    $Taxinvoice->detailList[1]->purchaseDT = '20210701';     // 거래일자
    $Taxinvoice->detailList[1]->itemName = '품목명1번';        // 품명
    $Taxinvoice->detailList[1]->spec = '';                   // 규격
    $Taxinvoice->detailList[1]->qty = '';                    // 수량
    $Taxinvoice->detailList[1]->unitCost = '';               // 단가
    $Taxinvoice->detailList[1]->supplyCost = '100000';       // 공급가액
    $Taxinvoice->detailList[1]->tax = '10000';               // 세액
    $Taxinvoice->detailList[1]->remark = '';                 // 비고

    // 메모
    $memo = '즉시요청 메모';

    try {
        $result = $this->PopbillTaxinvoice->RegistRequest($testCorpNum, $Taxinvoice, $memo, $testUserID);
        $code = $result->code;
        $message = $result->message;
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }
    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 공급받는자가 저장된 역발행 세금계산서를 공급자에게 송부하여 발행 요청합니다.
   * - 역발행 세금계산서 프로세스를 구현하기 위해서는 공급자/공급받는자가 모두 팝빌에 회원이여야 합니다.
   * - 역발행 요청후 공급자가 [발행] 처리시 포인트가 차감되며 역발행 세금계산서 항목중 과금방향(ChargeDirection)에 기재한 값에 따라 정과금(공급자과금) 또는 역과금(공급받는자과금) 처리됩니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#Request
   */
  public function Request(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::BUY;

    // 문서번호
    $mgtKey = '20210701-002';

    // 메모
    $memo = '역발행 요청 메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->Request($testCorpNum, $mgtKeyType, $mgtKey, $memo);
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
   * 공급자가 요청받은 역발행 세금계산서를 발행하기 전, 공급받는자가 역발행요청을 취소합니다.
   * - [취소]한 세금계산서의 문서번호를 재사용하기 위해서는 삭제 (Delete API)를 호출해야 합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#CancelRequest
   */
  public function CancelRequest(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::BUY;

    // 문서번호
    $mgtKey = '20210701-001';

    // 메모
    $memo = '역발행 요청 취소메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->CanCelRequest($testCorpNum, $mgtKeyType, $mgtKey, $memo);
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
   * 공급자가 공급받는자에게 역발행 요청 받은 세금계산서의 발행을 거부합니다.
   * - 세금계산서의 문서번호를 재사용하기 위해서는 삭제 (Delete API)를 호출하여 [삭제] 처리해야 합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#Refuse
   */
  public function Refuse(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210701-002';

    // 메모
    $memo = '역)발행 요청 거부메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->Refuse($testCorpNum, $mgtKeyType, $mgtKey, $memo);
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
   * 공급자가 "발행완료" 상태의 전자세금계산서를 국세청에 즉시 전송하며, 함수 호출 후 최대 30분 이내에 전송 처리가 완료됩니다.
   * - 국세청 즉시전송을 호출하지 않은 세금계산서는 발행일 기준 익일 오후 3시에 팝빌 시스템에서 일괄적으로 국세청으로 전송합니다.
   * - 익일전송시 전송일이 법정공휴일인 경우 다음 영업일에 전송됩니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#SendToNTS
   */
  public function SendToNTS(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210701-005';

    try {
        $result = $this->PopbillTaxinvoice->SendToNTS($testCorpNum, $mgtKeyType, $mgtKey);
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
   * 세금계산서 1건의 상태 및 요약정보를 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetInfo
   */
  public function GetInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 조회할 세금계산서 문서번호
    $mgtKey = '20210701-001';

    try {
        $result = $this->PopbillTaxinvoice->GetInfo($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/GetInfo', ['TaxinvoiceInfo' => [$result] ] );
  }

  /**
   * 다수건의 세금계산서 상태 및 요약 정보를 확인합니다. (1회 호출 시 최대 1,000건 확인 가능)
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetInfos
   */
  public function GetInfos(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호 배열, 최대 1000건
    $MgtKeyList = array();
    array_push($MgtKeyList, "20210101-001");
    array_push($MgtKeyList, '20210101-002');
    array_push($MgtKeyList, '20210101-003');

    try {
        $result = $this->PopbillTaxinvoice->GetInfos($testCorpNum, $mgtKeyType, $MgtKeyList);
    }
    catch(PopbillException $pe) {
        $code= $pe->getCode();
        $message= $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('Taxinvoice/GetInfo', ['TaxinvoiceInfo' => $result] );
  }

  /**
   * 세금계산서 1건의 상세정보를 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetDetailInfo
   */
  public function GetDetailInfo(){

    // 팝빌회원, 사업자번호
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호
    $mgtKey = '20210101-001';

    try {
        $result = $this->PopbillTaxinvoice->GetDetailInfo($testCorpNum, $mgtKeyType, $mgtKey);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/GetDetailInfo', ['Taxinvoice' => $result] );
  }

  /**
   * 검색조건에 해당하는 세금계산서를 조회합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#Search
   */
  public function Search(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌회원 아이디
    $testUserID = 'testkorea';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // [필수] 일자유형, R-등록일시, W-작성일자, I-발행일시 중 1개 기입
    $DType = 'W';

    // [필수] 시작일자
    $SDate = '20210701';

    // [필수] 종료일자
    $EDate = '20210731';

    // 전송상태값 배열, 문서상태 값 3자리 배열, 2,3번째 자리 와일드카드 사용가능, 미기재시 전체조회
    $State = array (
        '3**',
        '6**'
    );

    // 문서유형 배열, N-일반, M-수정, 선택 배열
    $Type = array (
        'N',
        'M'
    );

    // 과세형태 배열 , T-과세, N-면세, Z-영세 선택 배열
    $TaxType = array (
        'T',
        'N',
        'Z'
    );

    // 발행형태 배열 , N-정발행, R-역발행, T-위수탁 선택 배열
    $IssueType = array (
        'N',
        'R',
        'T'
    );

    // 공급받는자 휴폐업상태 배열, N-미확인, 0-미등록, 1-사업중, 2-폐업, 3-휴업
    $CloseDownState = array (
        'N',
        '0',
        '1',
        '2',
        '3'
    );

    // 등록유형 배열, P-팝빌, H-홈택스 또는 외부ASP
    $RegType = array (
        'P',
        'H'
    );

    // 지연발행여부, 0-정상발행분만 조회, 1-지연발행분만 조회, 미기재시 전체조회
    $LateOnly = 0;

    // 종사업장 유무, 공백-전체조회, 0-종사업장번호 없는경우 조회, 1-종사업장번호 있는건만 조회
    $TaxRegIDYN = "";

    // 종사업장번호 사업자유형, S-공급자, B-공급받는자, T-수탁자
    $TaxRegIDType = "S";

    // 종사업장번호, 콤마(",")로 구분하여 구성, ex) 1234,0001
    $TaxRegID = "";

    // 페이지 번호 기본값 1
    $Page = 1;

    // 페이지당 검색갯수, 기본값 500, 최대값 1000
    $PerPage = 5;

    // 정렬방향, D-내림차순, A-오름차순
    $Order = 'D';

    // 거래처 조회, 거래처 상호 또는 거래처 사업자등록번호 기재하여 조회, 미기재시 전체조회
    $QString = '';

    // 문서번호 또는 국세청승인번호 조회
    $MgtKey = '';

    // 연동문서 조회여부, 공백-전체조회, 0-일반문서 조회, 1-연동문서 조회
    $InterOPYN = '';

    try {
        $result = $this->PopbillTaxinvoice->Search($testCorpNum, $mgtKeyType, $DType, $SDate,
            $EDate, $State, $Type, $TaxType, $LateOnly, $Page, $PerPage, $Order,
            $TaxRegIDType, $TaxRegIDYN, $TaxRegID, $QString, $InterOPYN, $testUserID, $IssueType,
            $CloseDownState, $MgtKey, $RegType);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/Search', ['Result' => $result] );
  }

  /**
   * 세금계산서의 상태에 대한 변경이력을 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetLogs
   */
  public function GetLogs(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호
    $mgtKey = '20210101-001';

    try {
        $result = $this->PopbillTaxinvoice->GetLogs($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetLogs', ['Result' => $result] );
  }

  /**
   * 로그인 상태로 팝빌 사이트의 전자세금계산서 문서함 메뉴에 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetURL
   */
  public function GetURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    // [TBOX] 임시문서함, [SBOX] 매출문서함, [PBOX] 매입문서함, [WRITE] 매출문서작성
    $TOGO = 'TBOX';

    try {
        $url = $this->PopbillTaxinvoice->GetURL($testCorpNum, $testUserID, $TOGO);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "세금계산서 문서함 팝업 URL" , 'value' => $url]);
  }

  /**
   * 팝빌 사이트와 동일한 세금계산서 1건의 상세 정보 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetPopUpURL
   */
  public function GetPopUpURL(){

    // 팝빌 회원 사업자 번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetPopUpURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "세금계산서 보기 팝업 URL" , 'value' => $url]);

  }

  /**
   * 팝빌 사이트와 동일한 세금계산서 1건의 상세정보 페이지(사이트 상단, 좌측 메뉴 및 버튼 제외)의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetViewURL
   */
  public function GetViewURL(){

    // 팝빌 회원 사업자 번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetViewURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "세금계산서 보기 팝업 URL (메뉴/버튼 제외)" , 'value' => $url]);

  }

  /**
   * 세금계산서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환하며, 페이지내에서 인쇄 설정값을 "공급자" / "공급받는자" / "공급자+공급받는자"용 중 하나로 지정할 수 있습니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetPrintURL
   */
  public function GetPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetPrintURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "세금계산서 인쇄 팝업 URL" , 'value' => $url]);
  }

  /**
   * 세금계산서 1건을 구버전 양식으로 인쇄하기 위한 페이지의 팝업 URL을 반환하며, 페이지내에서 인쇄 설정값을 "공급자" / "공급받는자" / "공급자+공급받는자"용 중 하나로 지정할 수 있습니다..
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetOldPrintURL
   */
  public function GetOldPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetOldPrintURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "세금계산서 (구)인쇄 팝업 URL" , 'value' => $url]);
  }

  /**
  * "공급받는자" 용 세금계산서 1건을 인쇄하기 위한 페이지의 팝업 URL을 반환합니다.
  * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
  * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetEPrintURL
  */
  public function GetEPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetEPrintURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "세금계산서 인쇄(공급받는자용) 팝업 URL" , 'value' => $url]);
  }

  /**
   * 다수건의 세금계산서를 인쇄하기 위한 페이지의 팝업 URL을 반환합니다. (최대 100건)
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetMassPrintURL
   */
  public function GetMassPrintURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호 배열 최대 100건
    $MgtKeyList = array(
        '20210213-001',
        '20210101-001',
        '20210101-002',
    );
    try {
        $url = $this->PopbillTaxinvoice->GetMassPrintURL($testCorpNum, $mgtKeyType, $MgtKeyList);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "세금계산서 대량 인쇄 팝업 URL" , 'value' => $url]);
  }

  /**
   * 안내메일과 관련된 전자세금계산서를 확인 할 수 있는 상세 페이지의 팝업 URL을 반환하며, 해당 URL은 메일 하단의 "전자세금계산서 보기" 버튼의 링크와 같습니다.
   * - 함수 호출로 반환 받은 URL에는 유효시간이 없습니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetMailURL
   */
  public function GetMailURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetMailURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "공급받는자 세금계산서 메일링크 URL" , 'value' => $url]);
  }

  /**
   * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetAccessURL
   */
  public function GetAccessURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillTaxinvoice->GetAccessURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);

  }

  /**
   * 세금계산서에 첨부할 인감, 사업자등록증, 통장사본을 등록하는 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetSealURL
   */
  public function GetSealURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillTaxinvoice->GetSealURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "인감 및 첨부문서 등록 URL" , 'value' => $url]);
  }

  /**
  * "임시저장" 상태의 세금계산서에 1개의 파일을 첨부합니다. (최대 5개)
  * - https://docs.popbill.com/taxinvoice/phplaravel/api#AttachFile
  */
  public function AttachFile(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호
    $mgtKey = '20210213-004';

    // 첨부파일 경로, 해당 파일에 읽기 권한이 설정되어 있어야 합니다.
    $filePath = '/Users/John/Desktop/03A4C36315C047B4A171CEF283ED9A40.jpg';

    try {
        $result = $this->PopbillTaxinvoice->AttachFile($testCorpNum, $mgtKeyType, $mgtKey, $filePath);
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
   * "임시저장" 상태의 세금계산서에 첨부된 1개의 파일을 삭제합니다.
   * - 파일을 식별하는 파일아이디는 첨부파일 목록(GetFiles API) 의 응답항목 중 파일아이디(AttachedFile) 값을 통해 확인할 수 있습니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#DeleteFile
   */
  public function DeleteFile(){

    // 팝빌회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-004';

    // 삭제할 첨부파일 아이디, getFiles(첨부파일목록) API 응답항목중 attachedFile 변수값 참조
    $FileID = '0D583984-FDF3-4189-B61A-40942A2D834B.PBF';

    try {
        $result = $this->PopbillTaxinvoice->DeleteFile($testCorpNum, $mgtKeyType, $mgtKey, $FileID);
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
   * 세금계산서에 첨부된 파일목록을 확인합니다.
   * - 응답항목 중 파일아이디(AttachedFile) 항목은 파일삭제(DeleteFile API) 호출시 이용할 수 있습니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetFiles
   */
  public function GetFiles(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-004';

    try {
        $result = $this->PopbillTaxinvoice->GetFiles($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('GetFiles', ['Result' => $result] );
  }

  /**
   * 세금계산서와 관련된 안내 메일을 재전송 합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#SendEmail
   */
  public function SendEmail(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210213-004';

    // 수신이메일주소
    // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
    // 실제 거래처의 메일주소가 기재되지 않도록 주의
    $receiver = 'test@test.com';

    try {
        $result = $this->PopbillTaxinvoice->SendEmail($testCorpNum, $mgtKeyType, $mgtKey, $receiver);
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
   * 세금계산서와 관련된 안내 SMS(단문) 문자를 재전송하는 함수로, 팝빌 사이트 [문자·팩스] > [문자] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
   * - 메시지는 최대 90byte까지 입력 가능하고, 초과한 내용은 자동으로 삭제되어 전송합니다. (한글 최대 45자)
   * - 함수 호출시 포인트가 과금됩니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#SendSMS
   */
  public function SendSMS(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호
    $mgtKey = '20210213-004';

    // 발신번호
    $sender = '07043042991';

    // 수신번호
    $receiver = '010111222';

    // 메시지 내용, 90byte 초과시 길이가 조정되어 전송됨.
    $contents = '문자전송 내용입니다. 90Byte를 초과한내용은 길이 조정되어 전송됩니다. 참고하시기 바랍니다.';

    try {
        $result = $this->PopbillTaxinvoice->SendSMS($testCorpNum , $mgtKeyType, $mgtKey, $sender, $receiver, $contents);
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
   * 세금계산서를 팩스로 전송하는 함수로, 팝빌 사이트 [문자·팩스] > [팩스] > [전송내역] 메뉴에서 전송결과를 확인 할 수 있습니다.
   * - 함수 호출시 포인트가 과금됩니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#SendFAX
   */
  public function SendFAX(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호
    $mgtKey = '20210101-001';

    // 발신번호
    $sender = '07043042991';

    // 수신팩스번호
    $receiver = '070111222';

    try {
        $result = $this->PopbillTaxinvoice->SendFAX($testCorpNum, $mgtKeyType, $mgtKey, $sender, $receiver);
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
   * 팝빌 전자명세서 API를 통해 발행한 전자명세서를 세금계산서에 첨부합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#AttachStatement
   */
  public function AttachStatement(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호
    $mgtKey = '20210101-001';

    // 첨부할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $subItemCode = 121;

    // 첨부할 명세서 문서번호
    $subMgtKey = '20210101-001';

    try {
        $result = $this->PopbillTaxinvoice->AttachStatement($testCorpNum, $mgtKeyType, $mgtKey, $subItemCode, $subMgtKey);
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
   * 세금계산서에 첨부된 전자명세서를 해제합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#DetachStatement
   */
  public function DetachStatement(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 문서번호
    $mgtKey = '20210101-001';

    // 첨부해제할 명세서 코드 - 121(거래명세서), 122(청구서), 123(견적서) 124(발주서), 125(입금표), 126(영수증)
    $subItemCode = 121;

    // 첨부해제할 명세서 문서번호
    $subMgtKey = '20210101-001';

    try {
        $result = $this->PopbillTaxinvoice->DetachStatement($testCorpNum, $mgtKeyType, $mgtKey, $subItemCode, $subMgtKey);
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
   * 전자세금계산서 유통사업자의 메일 목록을 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetEmailPublicKeys
   */
  public function GetEmailPublicKeys(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $emailList = $this->PopbillTaxinvoice->GetEmailPublicKeys($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/GetEmailPublicKeys', ['Result' => $emailList] );

  }

  /**
   * 팝빌 사이트를 통해 발행하였지만 문서번호가 존재하지 않는 세금계산서에 문서번호를 할당합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#AssignMgtKey
   */
  public function AssignMgtKey(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 세금계산서 아이템키, 문서 목록조회(Search) API의 반환항목중 ItemKey 참조
    $itemKey = '018123114240100001';

    // 할당할 문서번호, 숫자, 영문 '-', '_' 조합으로 1~24자리까지
    // 사업자번호별 중복없는 고유번호 할당
    $mgtKey = '20210101-001';

    try {
        $result = $this->PopbillTaxinvoice->AssignMgtKey($testCorpNum, $mgtKeyType, $itemKey, $mgtKey);
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
   * 세금계산서 관련 메일 항목에 대한 발송설정을 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#ListEmailConfig
   */
  public function ListEmailConfig(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillTaxinvoice->ListEmailConfig($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/ListEmailConfig', ['Result' => $result] );
  }

  /**
   * 세금계산서 관련 메일 항목에 대한 발송설정을 수정합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#UpdateEmailConfig
   *
   * 메일전송유형
   * [정발행]
   * TAX_ISSUE : 공급받는자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
   * TAX_ISSUE_INVOICER : 공급자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
   * TAX_CHECK : 공급자에게 전자세금계산서가 수신확인 되었음을 알려주는 메일입니다.
   * TAX_CANCEL_ISSUE : 공급받는자에게 전자세금계산서가 발행취소 되었음을 알려주는 메일입니다.
   *
   * [발행예정]
   * TAX_SEND : 공급받는자에게 [발행예정] 세금계산서가 발송 되었음을 알려주는 메일입니다.
   * TAX_ACCEPT : 공급자에게 [발행예정] 세금계산서가 승인 되었음을 알려주는 메일입니다.
   * TAX_ACCEPT_ISSUE : 공급자에게 [발행예정] 세금계산서가 자동발행 되었음을 알려주는 메일입니다.
   * TAX_DENY : 공급자에게 [발행예정] 세금계산서가 거부 되었음을 알려주는 메일입니다.
   * TAX_CANCEL_SEND : 공급받는자에게 [발행예정] 세금계산서가 취소 되었음을 알려주는 메일입니다.
   *
   * [역발행]
   * TAX_REQUEST : 공급자에게 세금계산서를 전자서명 하여 발행을 요청하는 메일입니다.
   * TAX_CANCEL_REQUEST : 공급받는자에게 세금계산서가 취소 되었음을 알려주는 메일입니다.
   * TAX_REFUSE : 공급받는자에게 세금계산서가 거부 되었음을 알려주는 메일입니다.
   *
   * [위수탁발행]
   * TAX_TRUST_ISSUE : 공급받는자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
   * TAX_TRUST_ISSUE_TRUSTEE : 수탁자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
   * TAX_TRUST_ISSUE_INVOICER : 공급자에게 전자세금계산서가 발행 되었음을 알려주는 메일입니다.
   * TAX_TRUST_CANCEL_ISSUE : 공급받는자에게 전자세금계산서가 발행취소 되었음을 알려주는 메일입니다.
   * TAX_TRUST_CANCEL_ISSUE_INVOICER : 공급자에게 전자세금계산서가 발행취소 되었음을 알려주는 메일입니다.
   *
   * [위수탁 발행예정]
   * TAX_TRUST_SEND : 공급받는자에게 [발행예정] 세금계산서가 발송 되었음을 알려주는 메일입니다.
   * TAX_TRUST_ACCEPT : 수탁자에게 [발행예정] 세금계산서가 승인 되었음을 알려주는 메일입니다.
   * TAX_TRUST_ACCEPT_ISSUE : 수탁자에게 [발행예정] 세금계산서가 자동발행 되었음을 알려주는 메일입니다.
   * TAX_TRUST_DENY : 수탁자에게 [발행예정] 세금계산서가 거부 되었음을 알려주는 메일입니다.
   * TAX_TRUST_CANCEL_SEND : 공급받는자에게 [발행예정] 세금계산서가 취소 되었음을 알려주는 메일입니다.
   *
   * [처리결과]
   * TAX_CLOSEDOWN : 거래처의 휴폐업 여부를 확인하여 안내하는 메일입니다.
   * TAX_NTSFAIL_INVOICER : 전자세금계산서 국세청 전송실패를 안내하는 메일입니다.
   *
   * [정기발송]
   * TAX_SEND_INFO : 전월 귀속분 [매출 발행 대기] 세금계산서의 발행을 안내하는 메일입니다.
   * ETC_CERT_EXPIRATION : 팝빌에서 이용중인 공인인증서의 갱신을 안내하는 메일입니다.
   */
  public function UpdateEmailConfig(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 메일 전송 유형
    $emailType = 'TAX_ISSUE';

    // 전송 여부 (True = 전송, False = 미전송)
    $sendYN = True;

    try {
        $result = $this->PopbillTaxinvoice->UpdateEmailConfig($testCorpNum, $emailType, $sendYN);
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
   * 연동회원의 국세청 전송 옵션 설정 상태를 확인합니다.
   * - 국세청 전송 옵션 설정은 팝빌 사이트 [전자세금계산서] > [환경설정] > [세금계산서 관리] 메뉴에서 설정할 수 있으며, API로 설정은 불가능 합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetSendToNTSConfig
   */
  public function GetSendToNTSConfig(){
    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $sendToNTSConfig = $this->PopbillTaxinvoice->GetSendToNTSConfig($testCorpNum, $testUserID);

    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "국세청 전송 설정 확인" , 'value' => $sendToNTSConfig ? 'true' : 'false']);
  }
  /**
   * 전자세금계산서 발행에 필요한 인증서를 팝빌 인증서버에 등록하기 위한 페이지의 팝업 URL을 반환합니다.
   * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
   * - 인증서 갱신/재발급/비밀번호 변경한 경우, 변경된 인증서를 팝빌 인증서버에 재등록 해야합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetTaxCertURL
   */
  public function GetTaxCertURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillTaxinvoice->GetTaxCertURL($testCorpNum, $testUserID);
    } catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "공인인증서 등록 URL" , 'value' => $url]);
  }

  /**
   * 팝빌 인증서버에 등록된 인증서의 만료일을 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetCertificateExpireDate
   */
  public function GetCertificateExpireDate(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $certExpireDate = $this->PopbillTaxinvoice->GetCertificateExpireDate($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "공인인증서 만료일시" , 'value' => $certExpireDate]);
  }

  /**
   * 팝빌 인증서버에 등록된 인증서의 유효성을 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#CheckCertValidation
   */
  public function CheckCertValidation(){

    // 팝빌 회원 사업자번호, '-' 제외 10자리
    $testCorpNum = '1234567890';

    try	{
        $result = $this->PopbillTaxinvoice->CheckCertValidation($testCorpNum);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetBalance
   */
  public function GetBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillTaxinvoice->GetBalance($testCorpNum);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetChargeURL
   */
  public function GetChargeURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 팝빌 회원 아이디
    $testUserID = 'testkorea';

    try {
      $url = $this->PopbillTaxinvoice->GetChargeURL($testCorpNum, $testUserID);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetPartnerBalance
   */
  public function GetPartnerBalance(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillTaxinvoice->GetPartnerBalance($testCorpNum);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetPartnerURL
   */
  public function GetPartnerURL(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // [CHRG] : 포인트충전 URL
    $TOGO = 'CHRG';

    try {
        $url = $this->PopbillTaxinvoice->GetPartnerURL($testCorpNum, $TOGO);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * 전자세금계산서 발행단가를 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetUnitCost
   */
  public function GetUnitCost(){

    // 팝빌 회원 사업자 번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try {
        $unitCost = $this->PopbillTaxinvoice->GetUnitCost($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자세금계산서 발행단가" , 'value' => $unitCost]);
  }

  /**
   * 팝빌 전자세금계산서 API 서비스 과금정보를 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetChargeInfo
   */
  public function GetChargeInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillTaxinvoice->GetChargeInfo($testCorpNum);
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
   * - LinkID는 config/popbill.php 파일에 선언되어 있는 인증정보 입니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#CheckIsMember
   */
  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    // 파트너 링크아이디
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillTaxinvoice->CheckIsMember($testCorpNum, $LinkID);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#CheckID
   */
  public function CheckID(){

    // 조회할 아이디
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillTaxinvoice->CheckID($testUserID);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#JoinMember
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
      $result = $this->PopbillTaxinvoice->JoinMember($joinForm);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetCorpInfo
   */
  public function GetCorpInfo(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillTaxinvoice->GetCorpInfo($testCorpNum);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#UpdateCorpInfo
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
        $result =  $this->PopbillTaxinvoice->UpdateCorpInfo($testCorpNum, $CorpInfo);
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
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#RegistContact
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
        $result = $this->PopbillTaxinvoice->RegistContact($testCorpNum, $ContactInfo);
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
   * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#ListContact
   */
  public function ListContact(){

    // 팝빌회원 사업자번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillTaxinvoice->ListContact($testCorpNum);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ContactInfo', ['ContactList' => $ContactList]);
  }

  /**
   * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#UpdateContact
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
        $result = $this->PopbillTaxinvoice->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
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
   * 전자세금계산서 PDF 파일을 다운 받을 수 있는 URL을 반환합니다.
   * - 반환되는 URL은 보안정책상 30초의 유효시간을 갖으며, 유효시간 이후 호출시 정상적으로 페이지가 호출되지 않습니다.
   * - https://docs.popbill.com/taxinvoice/phplaravel/api#GetPDFURL
   */
  public function GetPDFURL(){

    // 팝빌 회원 사업자 번호, '-'제외 10자리
    $testCorpNum = '1234567890';

    // 발행유형, SELL:매출, BUY:매입, TRUSTEE:위수탁
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // 문서번호
    $mgtKey = '20210401-01';

    try {
        $url = $this->PopbillTaxinvoice->GetPDFURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "세금계산서 PDF 다운로드 URL" , 'value' => $url]);

  }
}
