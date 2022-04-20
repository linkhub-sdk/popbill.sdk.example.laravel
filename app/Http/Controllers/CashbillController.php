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

        // 연동환경 설정값, true-개발용, false-상업용
        $this->PopbillCashbill->IsTest(config('popbill.IsTest'));

        // 인증토큰의 IP제한기능 사용여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillCashbill->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 팝빌 API 서비스 고정 IP 사용여부, true-사용, false-미사용, 기본값(false)
        $this->PopbillCashbill->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬서버 시간 사용 여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillCashbill->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request){
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 파트너가 현금영수증 관리 목적으로 할당하는 문서번호 사용여부를 확인합니다.
     * - 이미 사용 중인 문서번호는 중복 사용이 불가하고, 현금영수증이 삭제된 경우에만 문서번호의 재사용이 가능합니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#CheckMgtKeyInUse
     */
    public function CheckMgtKeyInUse(){

        // 팝빌회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $mgtKey = '20220405-PHP7-001';

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
     * 작성된 현금영수증 데이터를 팝빌에 저장과 동시에 발행하여 "발행완료" 상태로 처리합니다.
     * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
     * - "발행완료"된 현금영수증은 국세청 전송 이전에 발행취소(CancelIssue API) 함수로 국세청 신고 대상에서 제외할 수 있습니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#RegistIssue
     */
    public function RegistIssue(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $mgtKey = '20220405-PHP7-001';

        // 메모
        $memo = '현금영수증 즉시발행 메모';

        // 안내메일 제목, 공백처리시 기본양식으로 전송
        $emailSubject = '';

        // 현금영수증 객체 생성
        $Cashbill = new Cashbill();

        // 현금영수증 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
        $Cashbill->mgtKey = $mgtKey;

        // 문서형태, 승인거래 기재
        $Cashbill->tradeType = '승인거래';

        // 거래구분, {소득공제용, 지출증빙용} 중 기재
        $Cashbill->tradeUsage = '소득공제용';

        // 거래유형, {일반, 도서공연, 대중교통} 중 기재
        // - 미입력시 기본값 "일반" 처리
        $Cashbill->tradeOpt = '일반';

        // 과세형태, {과세, 비과세} 중 기재
        $Cashbill->taxationType = '과세';

        // 거래금액, ','콤마 불가 숫자만 가능
        $Cashbill->totalAmount = '11000';

        // 공급가액, ','콤마 불가 숫자만 가능
        $Cashbill->supplyCost = '10000';

        // 부가세, ','콤마 불가 숫자만 가능
        $Cashbill->tax = '1000';

        // 봉사료, ','콤마 불가 숫자만 가능
        $Cashbill->serviceFee = '0';

        // 가맹점 사업자번호
        $Cashbill->franchiseCorpNum = $testCorpNum;

        // 가맹점 종사업장 식별번호
        $Cashbill->franchiseTaxRegID = '';

        // 가맹점 상호
        $Cashbill->franchiseCorpName = '발행자 상호';

        // 가맹점 대표자 성명
        $Cashbill->franchiseCEOName = '발행자 대표자명';

        // 가맹점 주소
        $Cashbill->franchiseAddr = '발행자 주소';

        // 가맹점 전화번호
        $Cashbill->franchiseTEL = '070-1234-1234';

        // 식별번호, 거래구분에 따라 작성
        // └ 소득공제용 - 주민등록/휴대폰/카드번호(현금영수증 카드)/자진발급용 번호(010-000-1234) 기재가능
        // └ 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호(현금영수증 카드) 기재가능
        // └ 주민등록번호 13자리, 휴대폰번호 10~11자리, 카드번호 13~19자리, 사업자번호 10자리 입력 가능
        $Cashbill->identityNum = '0101112222';

        // 주문자명
        $Cashbill->customerName = '고객명';

        // 주문상품명
        $Cashbill->itemName = '상품명';

        // 주문번호
        $Cashbill->orderNumber = '주문번호';

        // 주문자 이메일
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $Cashbill->email = '';

        // 발행시 알림문자 전송여부
        $Cashbill->smssendYN = false;

        // 주문자 휴대폰
        // - {smssendYN} 의 값이 true 인 경우 아래 휴대폰번호로 안내 문자 전송
        $Cashbill->hp = '';

        try {
            $result = $this->PopbillCashbill->RegistIssue($testCorpNum, $Cashbill, $memo, $testUserID, $emailSubject);
            $code = $result->code;
            $message = $result->message;
            $confirmNum = $result->confirmNum;
            $tradeDate = $result->tradeDate;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $confirmNum = null;
            $tradeDate = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'confirmNum' => $confirmNum, 'tradeDate' => $tradeDate]);
    }

    /**
     * 최대 100건의 현금영수증 발행을 한번의 요청으로 접수합니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#BulkSubmit
     */
    public function BulkSubmit() {

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 제출아이디, 대량 발행 접수를 구별하는 식별키
        // └ 최대 36자리 영문, 숫자, '-' 조합으로 구성
        $submitID = "20220405-PHP7-BULK";

        // 최대 100건
        $cashbillList = array();

        for($i=0; $i<100; $i++) {
            // 현금영수증 객체 생성
            $Cashbill = new Cashbill();

            // 현금영수증 문서번호, 1~24자리 (숫자, 영문, '-', '_') 조합으로 사업자 별로 중복되지 않도록 구성
            $Cashbill->mgtKey = $submitID . "-" . $i;

            // 문서형태, {승인거래, 취소거래} 중 기재
            $Cashbill->tradeType = '승인거래';

            // 거래구분, (소득공제용, 지출증빙용) 중 기재
            $Cashbill->tradeUsage = '소득공제용';

            // 거래유형, {일반, 도서공연, 대중교통} 중 기재
            // - 미입력시 기본값 "일반" 처리
            $Cashbill->tradeOpt = '일반';

            // // 원본 현금영수증 국세청 승인번호
            // // 취소 현금영수증 작성시 필수
            // $Cashbill->orgConfirmNum = '';
            //
            // // 원본 현금영수증 거래일자
            // // 취소 현금영수증 작성시 필수
            // $Cashbill->orgTradeDate = '';

            // 과세형태, (과세, 비과세) 중 기재
            $Cashbill->taxationType = '과세';

            // 거래금액, ','콤마 불가 숫자만 가능
            $Cashbill->totalAmount = '11000';

            // 공급가액, ','콤마 불가 숫자만 가능
            $Cashbill->supplyCost = '10000';

            // 부가세, ','콤마 불가 숫자만 가능
            $Cashbill->tax = '1000';

            // 봉사료, ','콤마 불가 숫자만 가능
            $Cashbill->serviceFee = '0';

            // 가맹점 사업자번호, '-'제외 10자리
            $Cashbill->franchiseCorpNum = $testCorpNum;

            // 가맹점 종사업장 식별번호
            $Cashbill->franchiseTaxRegID = "";

            // 가맹점 상호
            $Cashbill->franchiseCorpName = '발행자 상호';

            // 가맹점 대표자 성명
            $Cashbill->franchiseCEOName = '발행자 대표자명';

            // 가맹점 주소
            $Cashbill->franchiseAddr = '발행자 주소';

            // 가맹점 전화번호
            $Cashbill->franchiseTEL = '';

            // 식별번호, 거래구분에 따라 작성
            // └ 소득공제용 - 주민등록/휴대폰/카드번호(현금영수증 카드)/자진발급용 번호(010-000-1234) 기재가능
            // └ 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호(현금영수증 카드) 기재가능
            // └ 주민등록번호 13자리, 휴대폰번호 10~11자리, 카드번호 13~19자리, 사업자번호 10자리 입력 가능
            $Cashbill->identityNum = '0101112222';

            // 주문자명
            $Cashbill->customerName = '주식회사주문자명담당자';

            // 주문상품명
            $Cashbill->itemName = '상품명';

            // 주문번호
            $Cashbill->orderNumber = '주문번호';

            // 주문자 이메일
            // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
            // 실제 거래처의 메일주소가 기재되지 않도록 주의
            $Cashbill->email = '';

            // 발행시 알림문자 전송여부
            $Cashbill->smssendYN = false;

            // 주문자 휴대폰
            // - {smssendYN} 의 값이 true 인 경우 아래 휴대폰번호로 안내 문자 전송
            $Cashbill->hp = '';

            $cashbillList[] = $Cashbill;
        }

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->BulkSubmit($testCorpNum, $submitID, $cashbillList, $testUserID);
            $code = $result->code;
            $message = $result->message;
            $receiptID = $result->receiptID;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('/PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'receiptID' => $receiptID]);
    }

    /**
     * 접수시 기재한 SubmitID를 사용하여 현금영수증 접수결과를 확인합니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#GetBulkResult
     */
    public function GetBulkResult() {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 초대량 발행 접수시 기재한 제출아이디
        $submitID = '20220405-PHP7-BULK';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->GetBulkResult($testCorpNum, $submitID, $testUserID);
        }
        catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Cashbill/GetBulkResult', ['Result' => $result]);
    }

    /**
     * 1건의 현금영수증을 [임시저장]합니다.
     * - [임시저장] 상태의 현금영수증은 발행(Issue API) 함수를 호출해야만 국세청에 전송됩니다.
     */
    public function Register(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $mgtKey = '20220405-PHP7-002';

        // 현금영수증 객체 생성
        $Cashbill = new Cashbill();

        // 현금영수증 문서번호,
        $Cashbill->mgtKey = $mgtKey;

        // 문서형태, (승인거래, 취소거래) 중 기재
        $Cashbill->tradeType = '승인거래';

        // 거래구분, (소득공제용, 지출증빙용) 중 기재
        $Cashbill->tradeUsage = '소득공제용';

        // 거래유형, (일반, 도서공연, 대중교통) 중 기재
        $Cashbill->tradeOpt = '일반';

        // 취소거래시 기재, 원본 현금영수증 국세청 승인번호 - 상태확인(getInfo API) 함수를 통해 confirmNum 값 기재
        $Cashbill->orgConfirmNum = "";

        // 취소거래시 기재, 원본 현금영수증 거래일자 - 상태확인(getInfo API) 함수를 통해 tradeDate 값 기재
        $Cashbill->orgTradeDate = "";

        // 과세형태, (과세, 비과세) 중 기재
        $Cashbill->taxationType = '과세';

        // 거래금액, ','콤마 불가 숫자만 가능
        $Cashbill->totalAmount = '11000';

        // 공급가액, ','콤마 불가 숫자만 가능
        $Cashbill->supplyCost = '10000';

        // 부가세, ','콤마 불가 숫자만 가능
        $Cashbill->tax = '1000';

        // 봉사료, ','콤마 불가 숫자만 가능
        $Cashbill->serviceFee = '0';

        // 가맹점 사업자번호
        $Cashbill->franchiseCorpNum = $testCorpNum;

        // 가맹점 종사업장 식별번호
        $Cashbill->franchiseTaxRegID = '';

        // 가맹점 상호
        $Cashbill->franchiseCorpName = '발행자 상호';

        // 가맹점 대표자 성명
        $Cashbill->franchiseCEOName = '발행자 대표자명';

        // 가맹점 주소
        $Cashbill->franchiseAddr = '발행자 주소';

        // 가맹점 전화번호
        $Cashbill->franchiseTEL = '070-1234-1234';

        // 식별번호, 거래구분에 따라 작성
        // └ 소득공제용 - 주민등록/휴대폰/카드번호(현금영수증 카드)/자진발급용 번호(010-000-1234) 기재가능
        // └ 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호(현금영수증 카드) 기재가능
        // └ 주민등록번호 13자리, 휴대폰번호 10~11자리, 카드번호 13~19자리, 사업자번호 10자리 입력 가능
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
        $Cashbill->email = '';

        // 발행시 알림문자 전송여부
        $Cashbill->smssendYN = false;

        // 주문자 휴대폰
        // - {smssendYN} 의 값이 true 인 경우 아래 휴대폰번호로 안내 문자 전송
        $Cashbill->hp = '';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->Register($testCorpNum, $Cashbill, $testUserID);
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
     */
    public function Update(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-002';

        // 현금영수증 객체 생성
        $Cashbill = new Cashbill();

        // 현금영수증 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $Cashbill->mgtKey = $mgtKey;

        // 문서형태, (승인거래, 취소거래) 중 기재
        $Cashbill->tradeType = '승인거래';

        // 거래구분, (소득공제용, 지출증빙용) 중 기재
        $Cashbill->tradeUsage = '소득공제용';

        // 거래유형, (일반, 도서공연, 대중교통) 중 기재
        $Cashbill->tradeOpt = '일반';

        // 취소거래시 기재, 원본 현금영수증 국세청 승인번호 - 상태확인(getInfo API) 함수를 통해 confirmNum 값 기재
        $Cashbill->orgConfirmNum = "";

        // 취소거래시 기재, 원본 현금영수증 거래일자 - 상태확인(getInfo API) 함수를 통해 tradeDate 값 기재
        $Cashbill->orgTradeDate = "";

        // 과세형태, (과세, 비과세) 중 기재
        $Cashbill->taxationType = '과세';

        // 거래금액, ','콤마 불가 숫자만 가능
        $Cashbill->totalAmount = '11000';

        // 공급가액, ','콤마 불가 숫자만 가능
        $Cashbill->supplyCost = '10000';

        // 부가세, ','콤마 불가 숫자만 가능
        $Cashbill->tax = '1000';

        // 봉사료, ','콤마 불가 숫자만 가능
        $Cashbill->serviceFee = '0';

        // 가맹점 사업자번호
        $Cashbill->franchiseCorpNum = $testCorpNum;

        // 가맹점 종사업장 식별번호
        $Cashbill->franchiseTaxRegID = '';

        // 가맹점 상호
        $Cashbill->franchiseCorpName = '발행자 상호_수정';

        // 가맹점 대표자 성명
        $Cashbill->franchiseCEOName = '발행자 대표자명';

        // 가맹점 주소
        $Cashbill->franchiseAddr = '발행자 주소';

        // 가맹점 전화번호
        $Cashbill->franchiseTEL = '070-1234-1234';

        // 식별번호, 거래구분에 따라 작성
        // └ 소득공제용 - 주민등록/휴대폰/카드번호(현금영수증 카드)/자진발급용 번호(010-000-1234) 기재가능
        // └ 지출증빙용 - 사업자번호/주민등록/휴대폰/카드번호(현금영수증 카드) 기재가능
        // └ 주민등록번호 13자리, 휴대폰번호 10~11자리, 카드번호 13~19자리, 사업자번호 10자리 입력 가능
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
        $Cashbill->email = '';

        // 발행시 알림문자 전송여부
        $Cashbill->smssendYN = false;

        // 주문자 휴대폰
        // - {smssendYN} 의 값이 true 인 경우 아래 휴대폰번호로 안내 문자 전송
        $Cashbill->hp = '';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->Update($testCorpNum, $mgtKey, $Cashbill, $testUserID);
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
        $mgtKey = '20220405-PHP7-002';

        // 메모
        $memo = '현금영수증 발행메모';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->Issue($testCorpNum, $mgtKey, $memo, $testUserID);
            $code = $result->code;
            $message = $result->message;
            $confirmNum = $result->confirmNum;
            $tradeDate = $result->tradeDate;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $confirmNum = null;
            $tradeDate = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'confirmNum' => $confirmNum, 'tradeDate' => $tradeDate]);
    }

    /**
     * 국세청 전송 이전 "발행완료" 상태의 현금영수증을 "발행취소"하고 국세청 전송 대상에서 제외합니다.
     * - 삭제(Delete API) 함수를 호출하여 "발행취소" 상태의 현금영수증을 삭제하면, 문서번호 재사용이 가능합니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#CancelIssue
     */
    public function CancelIssue(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-001';

        // 메모
        $memo = '현금영수증 발행취소메모';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->CancelIssue($testCorpNum, $mgtKey, $memo, $testUserID);
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
        $mgtKey = '20220405-PHP7-001';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->Delete($testCorpNum, $mgtKey, $testUserID);
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
     * 취소 현금영수증 데이터를 팝빌에 저장과 동시에 발행하여 "발행완료" 상태로 처리합니다.
     * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
     * - "발행완료"된 취소 현금영수증은 국세청 전송 이전에 발행취소(cancelIssue API) 함수로 국세청 신고 대상에서 제외할 수 있습니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#RevokeRegistIssue
     */
    public function RevokeRegistIssue(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $mgtKey = '20220405-PHP7-005';

        // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API)을 통해 확인가능.
        $orgConfirmNum = 'TB0000016';

        // 원본현금영수증 거래일자, 작성형식(yyyyMMdd) 문서정보 확인(GetInfo API)을 통해 확인가능.
        $orgTradeDate = '20220404';

        // 안내 문자 전송여부 , true / false 중 택 1
        // └ true = 전송 , false = 미전송
        // └ 원본 현금영수증의 구매자(고객)의 휴대폰번호 문자 전송
        $smssendYN = false;

        // 현금영수증 상태 이력을 관리하기 위한 메모
        $memo = "취소 현금영수증 발행 메모";

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 현금영수증 취소유형 - false 기재
        $isPartCancel = false;

        // 취소사유 , 1 / 2 / 3 중 택 1
        // └ 1 = 거래취소 , 2 = 오류발급취소 , 3 = 기타
        // └ 미입력시 기본값 1 처리
        $cancelType = 1;

        try {
            $result = $this->PopbillCashbill->RevokeRegistIssue($testCorpNum, $mgtKey, $orgConfirmNum, $orgTradeDate, $smssendYN, $memo, $testUserID, $isPartCancel, $cancelType);
            $code = $result->code;
            $message = $result->message;
            $confirmNum = $result->confirmNum;
            $tradeDate = $result->tradeDate;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $confirmNum = null;
            $tradeDate = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'confirmNum' => $confirmNum, 'tradeDate' => $tradeDate]);
    }

    /**
     * 작성된 (부분)취소 현금영수증 데이터를 팝빌에 저장과 동시에 발행하여 "발행완료" 상태로 처리합니다.
     * - 취소 현금영수증의 금액은 원본 금액을 넘을 수 없습니다.
     * - 현금영수증 국세청 전송 정책 : https://docs.popbill.com/cashbill/ntsSendPolicy?lang=phplaravel
     * - "발행완료"된 취소 현금영수증은 국세청 전송 이전에 발행취소(cancelIssue API) 함수로 국세청 신고 대상에서 제외할 수 있습니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#RevokeRegistIssue
     */
    public function RevokeRegistIssue_part(){

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 문서번호, 사업자별로 중복없이 1~24자리 영문, 숫자, '-', '_' 조합으로 구성
        $mgtKey = '20220405-PHP7-006';

        // 원본현금영수증 승인번호, 문서정보 확인(GetInfo API) 함수를 통해 확인가능.
        $orgConfirmNum = 'TB0000016';

        // 원본현금영수증 거래일자, 문서정보 확인(GetInfo API) 함수를 통해 확인가능.
        $orgTradeDate = '20220404';

        // 안내 문자 전송여부 , true / false 중 택 1
        // └ true = 전송 , false = 미전송
        // └ 원본 현금영수증의 구매자(고객)의 휴대폰번호 문자 전송
        $smssendYN = false;

        // 메모
        $memo = '부분취소현금영수증 발행메모';

        // 현금영수증 취소유형 - true 기재
        $isPartCancel = true;

        // 취소사유 , 1 / 2 / 3 중 택 1
        // └ 1 = 거래취소 , 2 = 오류발급취소 , 3 = 기타
        // └ 미입력시 기본값 1 처리
        $cancelType = 1;

        // [취소] 공급가액
        // - 취소할 공급가액 입력
        $supplyCost = '4000';

        // [취소] 부가세
        // - 취소할 부가세 입력
        $tax = '400';

        // [취소] 봉사료
        // - 취소할 봉사료 입력
        $serviceFee = '0';

        // [취소] 거래금액 (공급가액+부가세+봉사료)
        // - 취소할 거래금액 입력
        $totalAmount = '4400';

        try {
            $result = $this->PopbillCashbill->RevokeRegistIssue($testCorpNum, $mgtKey, $orgConfirmNum,
            $orgTradeDate, $smssendYN, $memo, $testUserID, $isPartCancel, $cancelType,
            $supplyCost, $tax, $serviceFee, $totalAmount);

            $code = $result->code;
            $message = $result->message;
            $confirmNum = $result->confirmNum;
            $tradeDate = $result->tradeDate;
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            $confirmNum = null;
            $tradeDate = null;
        }

        return view('PResponse', ['code' => $code, 'message' => $message, 'confirmNum' => $confirmNum, 'tradeDate' => $tradeDate]);
    }

    /**
     * 1건의 취소현금영수증을 [임시저장]합니다.
     * - [임시저장] 상태의 현금영수증은 발행(Issue API)을 호출해야만 국세청에 전송됩니다.
     */
    public function RevokeRegister(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $mgtKey = '20220405-PHP7-003';

        // 원본 현금영수증 국세청 승인번호 - 상태확인(getInfo API) 함수를 통해 confirmNum 값 기재
        $orgConfirmNum = "TB0000016";

        // 원본 현금영수증 거래일자 - 상태확인(getInfo API) 함수를 통해 tradeDate 값 기재
        $orgTradeDate = "20220404";

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
     * - [임시저장] 상태의 현금영수증은 발행(Issue API) 함수를 호출해야만 국세청에 전송됩니다.
     */
    public function RevokeRegister_part(){

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 문서번호, 최대 24자리, 영문, 숫자 '-', '_'를 조합하여 사업자별로 중복되지 않도록 구성
        $mgtKey = '20220405-PHP7-004';

        // 원본 현금영수증 국세청 승인번호 - 상태확인(getInfo API) 함수를 통해 confirmNum 값 기재
        $orgConfirmNum = "TB0000016";

        // 원본 현금영수증 거래일자 - 상태확인(getInfo API) 함수를 통해 tradeDate 값 기재
        $orgTradeDate = "20220404";

        // 안내 문자 전송여부 , true / false 중 택 1
        // └ true = 전송 , false = 미전송
        // └ 원본 현금영수증의 구매자(고객)의 휴대폰번호 문자 전송
        $smssendYN = false;

        // 현금영수증 취소유형 - true 기재
        $isPartCancel = true;

        // 취소사유 , 1 / 2 / 3 중 택 1
        // └ 1 = 거래취소 , 2 = 오류발급취소 , 3 = 기타
        // └ 미입력시 기본값 1 처리
        $cancelType = 1;

        // [취소] 공급가액
        // - 취소할 공급가액 입력
        $supplyCost = '4000';

        // [취소] 부가세
        // - 취소할 부가세 입력
        $tax = '400';

        // [취소] 봉사료
        // - 취소할 봉사료 입력
        $serviceFee = '0';

        // [취소] 거래금액 (공급가액+부가세+봉사료)
        // - 취소할 거래금액 입력
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
     * - 리턴값 'CashbillInfo'의 변수 'stateCode'를 통해 현금영수증의 상태코드를 확인합니다.
     * - 현금영수증 상태코드 [https://docs.popbill.com/cashbill/stateCode?lang=phplaravel]
     * - https://docs.popbill.com/cashbill/phplaravel/api#GetInfo
     */
    public function GetInfo(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-001';

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
     * - 리턴값 'CashbillInfo'의 변수 'stateCode'를 통해 현금영수증의 상태코드를 확인합니다.
     * - 현금영수증 상태코드 [https://docs.popbill.com/cashbill/stateCode?lang=phplaravel]
     * - https://docs.popbill.com/cashbill/phplaravel/api#GetInfos
     */
    public function GetInfos(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        // 문서번호 배열, 최대 1000건
        $MgtKeyList = array(
            '20220405-PHP7-001',
            '20220405-PHP7-002'
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
        $mgtKey = '20220405-PHP7-001';

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
     * 검색조건에 해당하는 현금영수증을 조회합니다. (조회기간 단위 : 최대 6개월)
     * - https://docs.popbill.com/cashbill/phplaravel/api#Search
     */
    public function Search(){

        // 팝빌회원 사업자번호
        $testCorpNum = '1234567890';

        // 일자 유형 ("R" , "T" , "I" 중 택 1)
        // └ R = 등록일자 , T = 거래일자 , I = 발행일자
        $DType = 'R';

        // 시작일자
        $SDate = '20220401';

        // 종료일자
        $EDate = '20220430';

        // 상태코드 배열 (2,3번째 자리에 와일드카드(*) 사용 가능)
        // - 미입력시 전체조회
        $State = array(
            '100',
            '2**',
            '3**',
            '4**'
        );

        // 문서형태 배열 ("N" , "C" 중 선택, 다중 선택 가능)
        // - N = 일반 현금영수증 , C = 취소 현금영수증
        // - 미입력시 전체조회
        $TradeType = array(
            'N',
            'C'
        );

        // 거래구분 배열 ("P" , "C" 중 선택, 다중 선택 가능)
        // - P = 소득공제용 , C = 지출증빙용
        // - 미입력시 전체조회
        $TradeUsage = array(
            'P',
            'C'
        );

        // 거래유형 배열 ("N" , "B" , "T" 중 선택, 다중 선택 가능)
        // - N = 일반 , B = 도서공연 , T = 대중교통
        // - 미입력시 전체조회
        $TradeOpt = array(
            'N',
            'B',
            'T'
        );

        // 과세형태 배열 ("T" , "N" 중 선택, 다중 선택 가능)
        // - T = 과세 , N = 비과세
        // - 미입력시 전체조회
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

        // 가맹점 종사업장 번호
        // └ 다수건 검색시 콤마(",")로 구분. 예) "1234,1000"
        // └ 미입력시 전제조회
        $FranchiseTaxRegID = "";

        try {
            $result = $this->PopbillCashbill->Search( $testCorpNum, $DType, $SDate,
            $EDate, $State, $TradeType, $TradeUsage, $TaxationType, $Page, $PerPage,
            $Order, $QString, $TradeOpt, $FranchiseTaxRegID);
        }
        catch(PopbillException $pe) {
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
        $mgtKey = '20220405-PHP7-002';

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
     * 로그인 상태로 팝빌 사이트의 현금영수증 문서함 메뉴에 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
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
     * 현금영수증 1건의 상세 정보 페이지의 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#GetPopUpURL
     */
    public function GetPopUpURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-001';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillCashbill->GetPopUpURL($testCorpNum, $mgtKey, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "현금영수증 보기 URL" , 'value' => $url]);
    }

    /**
     * 현금영수증 1건의 상세 정보 페이지(사이트 상단, 좌측 메뉴 및 버튼 제외)의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#GetViewURL
     */
    public function GetViewURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-001';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillCashbill->GetViewURL($testCorpNum, $mgtKey, $testUserID);
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
        $mgtKey = '20220405-PHP7-001';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillCashbill->GetPrintURL($testCorpNum, $mgtKey, $testUserID);
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
            '20220405-PHP7-001',
            '20220405-PHP7-002'
        );

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillCashbill->GetMassPrintURL($testCorpNum, $mgtKeyList, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "현금영수증 인쇄 (대량) URL" , 'value' => $url]);
    }

    /**
     * 현금영수증 안내메일의 상세보기 링크 URL을 반환합니다.
     * - 함수 호출로 반환 받은 URL에는 유효시간이 없습니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#GetMailURL
     */
    public function GetMailURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-001';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillCashbill->GetMailURL($testCorpNum, $mgtKey, $testUserID);
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
        $mgtKey = '20220405-PHP7-001';

        // 수신메일 주소
        $receiver = '';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->SendEmail($testCorpNum, $mgtKey, $receiver, $testUserID);
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
     * - 메시지는 최대 90byte까지 입력 가능하고, 초과한 내용은 자동으로 삭제되어 전송합니다. (한글 최대 45자)
     * - 함수 호출 시 포인트가 과금됩니다. (전송실패시 환불처리)
     * - https://docs.popbill.com/cashbill/phplaravel/api#SendSMS
     */
    public function SendSMS(){

        // 팝빌 회원 사업자번호, "-" 제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-001';

        // 발신번호
        $sender = '';

        // 수신자번호
        $receiver = '';

        // 메시지 내용, 90byte 초과시 길이가 조정되어 전송됨.
        $contents = '메시지 전송 테스트입니다.';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->SendSMS($testCorpNum, $mgtKey, $sender, $receiver, $contents, $testUserID);
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
     * - 함수 호출 시 포인트가 과금됩니다. (전송실패시 환불처리)
     * - https://docs.popbill.com/cashbill/phplaravel/api#SendFAX
     */
    public function SendFAX(){

        // 팝빌 회원 사업자번호, "-" 제외 10자리
        $testCorpNum = '1234567890';

        // 문서번호
        $mgtKey = '20220405-PHP7-001';

        // 발신번호
        $sender = '';

        // 수신팩스번호
        $receiver = '';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->SendFAX($testCorpNum, $mgtKey, $sender, $receiver, $testUserID);
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
     * 팝빌 사이트를 통해 발행하여 문서번호가 부여되지 않은 현금영수증에 문서번호를 할당합니다.
     * - https://docs.popbill.com/cashbill/phplaravel/api#AssignMgtKey
     */
    public function AssignMgtKey(){

        // 팝빌 회원 사업자번호, '-' 제외 10자리
        $testCorpNum = '1234567890';

        // 현금영수증 아이템키
        $itemKey = '022040514332500001';

        // 부여할 파트너 문서번호
        $mgtKey = '20220405-PHP7-007';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->AssignMgtKey($testCorpNum, $itemKey, $mgtKey, $testUserID);
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

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->ListEmailConfig($testCorpNum, $testUserID);
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

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->UpdateEmailConfig($testCorpNum, $emailType, $sendYN, $testUserID);
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
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
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
     * - 과금방식이 연동과금인 경우 연동회원 잔여포인트 확인(GetBalance API) 함수를 이용하시기 바랍니다.
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

        // 연동신청시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
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

        // 중복여부를 확인할 아이디
        $testUserID = 'testkorea';

        try {
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
        $joinForm->Addr = '테스트사업자주소';

        // 업태
        $joinForm->BizType = '업태';

        // 종목
        $joinForm->BizClass = '종목';

        // 담당자명
        $joinForm->ContactName = '담당자성명';

        // 담당자 이메일
        $joinForm->ContactEmail = '';

        // 담당자 연락처
        $joinForm->ContactTEL = '';

        // 아이디, 6자 이상 20자미만
        $joinForm->ID = 'userid_phpdd';

        // 비밀번호, 8자 이상 20자 이하(영문, 숫자, 특수문자 조합)
        $joinForm->Password = 'asdf1234!@';

        try {
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

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillCashbill->GetCorpInfo($testCorpNum, $testUserID);
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

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result =  $this->PopbillCashbill->UpdateCorpInfo($testCorpNum, $CorpInfo, $testUserID);
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
        $ContactInfo->tel = '';

        // 이메일주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $ContactInfo->email = '';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillCashbill->RegistContact($testCorpNum, $ContactInfo, $testUserID);
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

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $ContactList = $this->PopbillCashbill->ListContact($testCorpNum, $testUserID);
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
        $ContactInfo->tel = '';

        // 이메일 주소
        // 팝빌 개발환경에서 테스트하는 경우에도 안내 메일이 전송되므로,
        // 실제 거래처의 메일주소가 기재되지 않도록 주의
        $ContactInfo->email = '';

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
}
