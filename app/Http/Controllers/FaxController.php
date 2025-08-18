<?php
/**
  * 팝빌 팩스 API PHP SDK Laravel Example
  *
  * Laravel 연동 튜토리얼 안내 : https://developers.popbill.com/guide/fax/php/getting-started/tutorial?fwn=laravel
  * 연동 기술지원 연락처 : 1600-9854
  * 연동 기술지원 이메일 : code@linkhubcorp.com
  */
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillFax;
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class FaxController extends Controller
{
    public function __construct()
    {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 팩스 서비스 클래스 초기화
        $this->PopbillFax = new PopbillFax(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정, true-테스트, false-운영(Production), (기본값:false)
        $this->PopbillFax->IsTest(config('popbill.IsTest'));

        // 인증토큰 IP 검증 설정, true-사용, false-미사용, (기본값:true)
        $this->PopbillFax->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 통신 IP 고정, true-사용, false-미사용, (기본값:false)
        $this->PopbillFax->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬시스템 시간 사용여부, true-사용, false-미사용, (기본값:true)
        $this->PopbillFax->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request)
    {
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 팩스 발신번호 등록여부를 확인합니다.
     * - 발신번호 상태가 '승인'인 경우에만 code가 1로 반환됩니다.
     * - https://developers.popbill.com/reference/fax/php/api/sendnum#CheckSenderNumber
     */
    public function CheckSenderNumber()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 확인할 발신번호
        $SenderNumber = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->CheckSenderNumber($CorpNum, $SenderNumber, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 발신번호를 등록하고 내역을 확인하는 팩스 발신번호 관리 페이지 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/api/sendnum#GetSenderNumberMgtURL
     */
    public function GetSenderNumberMgtURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillFax->GetSenderNumberMgtURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팩스 발신번호 관리 팝업 URL", 'value' => $url]);
    }

    /**
     * 팝빌에 등록한 연동회원의 팩스 발신번호 목록을 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/api/sendnum#GetSenderNumberList
     */
    public function GetSenderNumberList()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->GetSenderNumberList($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetSenderNumberList', ['Result' => $result]);
    }

    /**
     * 팩스 1건을 전송합니다. (최대 전송파일 개수: 20개)
     * - https://developers.popbill.com/reference/fax/php/api/send#SendFAX
     */
    public function SendFAX()
    {
        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 팩스전송 발신번호
        // 팝빌에 등록되지 않은 번호를 입력하는 경우 '원발신번호'로 팩스 전송됨
        $Sender = '';

        // 팩스 수신정보 배열, 최대 1000건
        $Receivers[] = array(
            // 팩스 수신번호
            'rcv' => '070111222',
            // 수신자명
            'rcvnm' => '수신자명'
        );

        // 팩스전송파일, 해당파일에 읽기 권한이 설정되어 있어야 함. 최대 20개.
        $FilesPaths = array('/test.pdf');

        // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스전송 발신자명
        $SenderName = '발신자명';

        // 광고팩스 전송여부 , true / false 중 택 1
        // └ true = 광고 , false = 일반
        // └ 미입력 시 기본값 false 처리
        $adsYN = false;

        // 팩스제목
        $title = '팩스 단건전송 제목';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        try {
            $receiptNum = $this->PopbillFax->SendFAX(
                $CorpNum,
                $Sender,
                $Receivers,
                $FilesPaths,
                $ReserveDT,
                $UserID,
                $SenderName,
                $adsYN,
                $title,
                $RequestNum
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '팩스 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 동일한 팩스파일을 다수의 수신자에게 전송하기 위해 팝빌에 접수합니다. (최대 전송파일 개수 : 20개) (최대 1,000건)
     * - https://developers.popbill.com/reference/fax/php/api/send#SendFAX
     */
    public function SendFAX_Multi()
    {
        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 팩스전송 발신번호
        $Sender = '';

        // 팩스 수신정보 배열, 최대 1000건
        for($i=0; $i<10; $i++){
            $Receivers[] = array(
                // 팩스 수신번호
                'rcv' => '070333444',
                // 팩스 수신자명
                'rcvnm' => '수신담당자',
                // 파트너 지정키, 보전송시 수신자 구별용 메모
                'interOPRefKey' => '20230127-'.$i
            );
        }

        // 팩스전송파일, 해당파일에 읽기 권한이 설정되어 있어야 함. 최대 20개.
        $Files = array('/test.pdf');

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스전송 발신자명
        $SenderName = '발신자명';

        // 광고팩스 전송여부 , true / false 중 택 1
        // └ true = 광고 , false = 일반
        // └ 미입력 시 기본값 false 처리
        $adsYN = false;

        // 팩스 제목
        $title = '팩스 동보전송 제목';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        try {
            $receiptNum = $this->PopbillFax->SendFAX(
                $CorpNum,
                $Sender,
                $Receivers,
                $Files,
                $ReserveDT,
                $UserID,
                $SenderName,
                $adsYN,
                $title,
                $RequestNum
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '팩스 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 전송할 파일의 바이너리 데이터를 팩스 1건 전송합니다. (최대 전송파일 개수: 20개)
     * - https://developers.popbill.com/reference/fax/php/api/send#SendFAXBinary
     */
    public function SendFAXBinary()
    {
        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 팩스전송 발신번호
        $Sender = '';

        // 팩스 수신정보 배열, 최대 1000건
        $Receivers[] = array(
            // 팩스 수신번호
            'rcv' => '070111222',
            // 수신자명
            'rcvnm' => '팝빌담당자'
        );

        // 파일정보 배열, 최대 20개.
        $FileDatas[] = array(
            //파일명
            'fileName' => 'test.pdf',
            //fileData - BLOB 데이터 입력
            'fileData' => file_get_contents('/test.pdf') //file_get_contenst-바이너리데이터 추출
        );

        $FileDatas[] = array(
            //파일명
            'fileName' => 'test2.pdf',
            //fileData - BLOB 데이터 입력
            'fileData' => file_get_contents('/test2.pdf') //file_get_contenst-바이너리데이터 추출
        );

        // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스전송 발신자명
        $SenderName = '발신자명';

        // 광고팩스 전송여부
        $adsYN = false;

        // 팩스제목
        $title = '팩스 단건전송 제목';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        try {
            $receiptNum = $this->PopbillFax->SendFAXBinary(
                $CorpNum,
                $Sender,
                $Receivers,
                $FileDatas,
                $ReserveDT,
                $UserID,
                $SenderName,
                $adsYN,
                $title,
                $RequestNum
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '바이너리데이터 팩스 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 동일한 파일의 바이너리 데이터를 다수의 수신자에게 전송하기 위해 팝빌에 접수합니다. (최대 전송파일 개수 : 20개) (최대 1,000건)
     * - https://developers.popbill.com/reference/fax/php/api/send#SendFAXBinary
     */
    public function SendFAXBinary_Multi()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 팩스전송 발신번호
        $Sender = '';

        // 팩스 수신정보 배열, 최대 1000건
        for($i=0; $i<10; $i++){
            $Receivers[] = array(
                // 팩스 수신번호
                'rcv' => '070111222',
                // 팩스 수신자명
                'rcvnm' => '팝빌담당자',
                // 파트너 지정키, 보전송시 수신자 구별용 메모
                'interOPRefKey' => '20230127-'.$i
            );
        }

        // 파일정보 배열, 최대 20개.
        $FileDatas[] = array(
            //파일명
            'fileName' => 'test.pdf',
            //바이너리데이터
            'fileData' => file_get_contents('/test.pdf') //file_get_contenst-바이너리데이터 추출
        );

        $FileDatas[] = array(
            //파일명
            'fileName' => 'test2.pdf',
            //바이너리데이터
            'fileData' => file_get_contents('/test2.pdf') //file_get_contenst-바이너리데이터 추출
        );

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스전송 발신자명
        $SenderName = '발신자명';

        // 광고팩스 전송여부 , true / false 중 택 1
        // └ true = 광고 , false = 일반
        // └ 미입력 시 기본값 false 처리
        $adsYN = false;

        // 팩스 제목
        $title = '팩스 동보전송 제목';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        try {
            $receiptNum = $this->PopbillFax->SendFAXBinary(
                $CorpNum,
                $Sender,
                $Receivers,
                $FileDatas,
                $ReserveDT,
                $UserID,
                $SenderName,
                $adsYN,
                $title,
                $RequestNum
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '바이너리데이터 팩스 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 팩스 1건을 재전송합니다.
     * - 발신/수신 정보 미입력시 기존과 동일한 정보로 팩스가 전송되고, 접수일 기준 최대 60일이 경과되지 않는 건만 재전송이 가능합니다.
     * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
     * - 변환실패 사유로 전송실패한 팩스 접수건은 재전송이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/api/send#ResendFAX
     */
    public function ResendFAX()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 팩스 접수번호
        $ReceiptNum = '022040516355100002';

        // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
        $SenderNum = '';

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
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스 제목
        $title = '팩스 재전송 제목';

        // 재전송 팩스의 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        try {
            $receiptNum = $this->PopbillFax->ResendFAX(
                $CorpNum,
                $ReceiptNum,
                $SenderNum,
                $SenderName,
                $Receivers,
                $ReserveDT,
                $UserID,
                $title,
                $RequestNum
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 팩스 1건을 재전송합니다.
     * - 발신/수신 정보 미입력시 기존과 동일한 정보로 팩스가 전송되고, 접수일 기준 최대 60일이 경과되지 않는 건만 재전송이 가능합니다.
     * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
     * - 변환실패 사유로 전송실패한 팩스 접수건은 재전송이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/api/send#ResendFAXRN
     */
    public function ResendFAXRN()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 재전송 팩스의 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
        $SenderNum = '';

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

        // 원본 팩스 전송시 할당한 전송요청번호(requestNum)
        $originalFAXrequestNum = '';

        // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스 제목
        $title = '팩스 재전송 제목';

        try {
            $receiptNum = $this->PopbillFax->ResendFAXRN(
                $CorpNum,
                $RequestNum,
                $SenderNum,
                $SenderName,
                $Receivers,
                $originalFAXrequestNum,
                $ReserveDT,
                $UserID,
                $title
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 동일한 팩스파일을 다수의 수신자에게 전송하기 위해 팝빌에 접수합니다. (최대 전송파일 개수: 20개) (최대 1,000건)
     * - 발신/수신 정보 미입력시 기존과 동일한 정보로 팩스가 전송되고, 접수일 기준 최대 60일이 경과되지 않는 건만 재전송이 가능합니다.
     * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
     * - 변환실패 사유로 전송실패한 팩스 접수건은 재전송이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/api/send#ResendFAX
     */
    public function ResendFAX_Multi()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 팩스 접수번호
        $ReceiptNum = '022040516355100002';

        // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
        $SenderNum = '';

        // 팩스전송 발신자명, 공백처리시 기존전송정보로 재전송
        $SenderName = '발신자명';

        // 팩스 수신정보, NULL로 처리하는 경우 기존전송정보로 재전송
        //$Receivers = NULL;

        // 팩스 수신정보가 기존전송정보와 다르게 동보전송하는 경우 아래의 코드 참조
        for($i=0; $i<10; $i++){
            $Receivers[] = array(
                // 팩스 수신번호
                'rcv' => '070111222',
                // 팩스 수신자명
                'rcvnm' => '팝빌담당자',
                // 파트너 지정키, 보전송시 수신자 구별용 메모
                'interOPRefKey' => '20230127-'.$i
            );
        }

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스 제목
        $title = '팩스 재전송 제목';

        // 재전송 팩스의 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        try {
            $receiptNum = $this->PopbillFax->ResendFAX(
                $CorpNum,
                $ReceiptNum,
                $SenderNum,
                $SenderName,
                $Receivers,
                $ReserveDT,
                $UserID,
                $title,
                $RequestNum
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 다수건의 팩스를 재전송합니다. (최대 전송파일 개수: 20개) (최대 1,000건)
     * - 발신/수신 정보 미입력시 기존과 동일한 정보로 팩스가 전송되고, 접수일 기준 최대 60일이 경과되지 않는 건만 재전송이 가능합니다.
     * - 팩스 재전송 요청시 포인트가 차감됩니다. (전송실패시 환불처리)
     * - 변환실패 사유로 전송실패한 팩스 접수건은 재전송이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/api/send#ResendFAXRN
     */
    public function ResendFAXRN_Multi()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // 재전송 팩스의 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 팩스전송 발신번호, 공백처리시 기존전송정보로 재전송
        $SenderNum = '';

        // 팩스전송 발신자명, 공백처리시 기존전송정보로 재전송
        $SenderName = '발신자명';

        // 팩스 수신정보, NULL로 처리하는 경우 기존전송정보로 재전송
        //$Receivers = NULL;

        // 팩스 수신정보가 기존전송정보와 다르게 동보전송하는 경우 아래의 코드 참조
        for($i=0; $i<10; $i++){
            $Receivers[] = array(
                // 팩스 수신번호
                'rcv' => '070111222',
                // 팩스 수신자명
                'rcvnm' => '팝빌담당자',
                // 파트너 지정키, 보전송시 수신자 구별용 메모
                'interOPRefKey' => '20230127-01'
            );
        }

        // 원본 팩스 전송시 할당한 전송요청번호(requestNum)
        $originalFAXrequestNum = '';

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 팩스 제목
        $title = '팩스 재전송 제목';

        try {
            $receiptNum = $this->PopbillFax->ResendFAXRN(
                $CorpNum,
                $RequestNum,
                $SenderNum,
                $SenderName,
                $Receivers,
                $originalFAXrequestNum,
                $ReserveDT,
                $UserID,
                $title
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '팩스 재전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 예약접수된 팩스 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/fax/php/api/send#CancelReserve
     */
    public function CancelReserve()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팩스예약전송 접수번호
        $ReceiptNum = '022040517574300001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->CancelReserve($CorpNum, $ReceiptNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 예약접수된 팩스 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/fax/php/api/send#CancelReserveRN
     */
    public function CancelReserveRN()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약팩스전송 요청시 할당한 전송요청번호
        $RequestNum = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->CancelReserveRN($CorpNum, $RequestNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에서 반환 받은 접수번호를 통해 팩스 전송상태 및 결과를 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/api/info#GetFaxDetail
     */
    public function GetFaxDetail()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팩스전송 접수번호
        $ReceiptNum = '022040513573800005';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->GetFaxDetail($CorpNum, $ReceiptNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Fax/GetFaxDetail', ['Result' => $result]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 팩스 전송상태 및 결과를 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/api/info#GetFaxDetailRN
     */
    public function GetFaxDetailRN()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팩스전송 요청시 할당한 전송요청번호
        $RequestNum = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->GetFaxDetailRN($CorpNum, $RequestNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Fax/GetFaxDetail', ['Result' => $result]);
    }

    /**
     * 검색조건에 해당하는 팩스 전송내역 목록을 조회합니다. (조회기간 단위 : 최대 2개월)
     * - 팩스 접수일시로부터 2개월 이내 접수건만 조회할 수 있습니다.
     * - https://developers.popbill.com/reference/fax/php/api/info#Search
     */
    public function Search()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 검색시작일자
        $SDate = '20230101';

        // 검색종료일자
        $EDate = '20230131';

        // 전송상태 배열 ("1" , "2" , "3" , "4" 중 선택, 다중 선택 가능)
        // └ 1 = 대기 , 2 = 성공 , 3 = 실패 , 4 = 취소
        // - 미입력 시 전체조회
        $State = array(1, 2, 3, 4);

        // 예약여부 (null, false, true 중 택 1)
        // └ null = 전체, false = 즉시전송건, true = 예약전송건
        // - 미입력 시 전체조회
        $ReserveYN = false;

        // 사용자권한별 조회
        // └ true = 팝빌회원 아이디(UserID)로 전송한 팩스만 조회
        // └ false = 전송한 팩스 전체 조회 : 기본값
        $SenderOnly = false;

        // 페이지 번호, 기본값 1
        $Page = 1;

        // 페이지당 검색갯수, 기본값 500, 최대값 1000
        $PerPage = 500;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = 'D';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 조회 검색어(발신자명/수신자명)
        // - 미입력시 전체조회
        $QString = null;

        try {
            $result = $this->PopbillFax->Search($CorpNum, $SDate, $EDate, $State, $ReserveYN, $SenderOnly, $Page, $PerPage, $Order, $UserID, $QString);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Fax/Search', ['Result' => $result]);
    }

    /**
     * 팩스 전송내역 확인 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/api/info#GetSentListURL
     */
    public function GetSentListURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillFax->GetSentListURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팩스 전송내역 팝업 URL", 'value' => $url]);
    }

    /**
     * 팩스 변환결과 확인 팝업 URL을 반환하며, 팩스전송을 위한 TIF 포맷 변환 완료 후 호출 할 수 있습니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/api/info#GetPreviewURL
     */
    public function GetPreviewURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팩스전송 접수번호
        $ReceiptNum = '022040518123700001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillFax->GetPreviewURL($CorpNum, $ReceiptNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "팩스 변환결과 확인 팝업 URL", 'value' => $url]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetBalance
     */
    public function GetBalance()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillFax->GetBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetUseHistory
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

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->GetUseHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetPaymentHistory
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

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->GetPaymentHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불 신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetRefundHistory
     */
    public function GetRefundHistory()
    {

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->GetRefundHistory($CorpNum, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불을 신청합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#Refund
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
            $result = $this->PopbillFax->Refund($CorpNum, $RefundForm, $UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/point#PaymentRequest
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

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->PaymentRequest($CorpNum, $PaymentForm, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentResponse', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 무통장 입금신청내역 1건을 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetSettleResult
     */
    public function GetSettleResult()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        // paymentRequest 를 통해 얻은 settleCode.
        $SettleCode = '202210040000000070';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->GetSettleResult($CorpNum, $SettleCode, $UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetChargeURL
     */
    public function GetChargeURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillFax->GetChargeURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetPaymentURL
     */
    public function GetPaymentURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillFax->GetPaymentURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillFax->GetUseHistoryURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetPartnerBalance
     */
    public function GetPartnerBalance()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillFax->GetPartnerBalance($CorpNum);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetPartnerURL
     */
    public function GetPartnerURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillFax->GetPartnerURL($CorpNum, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 팩스 전송시 과금되는 포인트 단가를 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetUnitCost
     */
    public function GetUnitCost()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 수신번호 유형 : "일반" / "지능" 중 택 1
        // └ 일반망 : 지능망을 제외한 번호
        // └ 지능망 : 030*, 050*, 070*, 080*, 대표번호
        $receiveNumType = '일반';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $unitCost = $this->PopbillFax->GetUnitCost($CorpNum, $receiveNumType, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "팩스 전송단가 ", 'value' => $unitCost]);
    }

    /**
     * 팝빌 팩스 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetChargeInfo
     */
    public function GetChargeInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 수신번호 유형 : "일반" / "지능" 중 택 1
        // └ 일반망 : 지능망을 제외한 번호
        // └ 지능망 : 030*, 050*, 070*, 080*, 대표번호
        $receiveNumType = '지능';

        try {
            $result = $this->PopbillFax->GetChargeInfo($CorpNum, $UserID, $receiveNumType);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/member#CheckIsMember
     */
    public function CheckIsMember()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 연동신청 시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillFax->CheckIsMember($CorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/member#CheckID
     */
    public function CheckID()
    {

        // 중복여부를 확인할 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->CheckID($UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/member#JoinMember
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
            $result = $this->PopbillFax->JoinMember($JoinForm);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌 사이트에 로그인 상태로 접근할 수 있는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/member#GetAccessURL
     */
    public function GetAccessURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillFax->GetAccessURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL", 'value' => $url]);
    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/member#GetCorpInfo
     */
    public function GetCorpInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillFax->GetCorpInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/member#UpdateCorpInfo
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

        try {
            $result =  $this->PopbillFax->UpdateCorpInfo($CorpNum, $CorpInfo);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/member#RegistContact
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

        try {
            $result = $this->PopbillFax->RegistContact($CorpNum, $ContactInfo);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 담당자(팝빌 로그인 계정)를 삭제합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/member#DeleteContact
     */
    public function DeleteContact()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 삭제할 담당자 아이디
        $TargetUserID = 'testkorea20250818_01';

        // 팝빌 회원 관리자 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillFax->DeleteContact($CorpNum, $TargetUserID, $UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/member#GetContactInfo
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
            $ContactInfo = $this->PopbillFax->GetContactInfo($CorpNum, $ContactID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/member#ListContact
     */
    public function ListContact()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactList = $this->PopbillFax->ListContact($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/member#UpdateContact
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
            $result = $this->PopbillFax->UpdateContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/fax/php/common-api/member#QuitMember
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
            $result = $this->PopbillFax->QuitMember($CorpNum, $QuitReason, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PResponse', ['code' => $result->code, 'message' => $result->message]);
    }

    /**
     * 환불 가능한 포인트를 확인합니다. (보너스 포인트는 환불가능포인트에서 제외됩니다.)
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetRefundableBalance
     */
    public function GetRefundableBalance()
    {

        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $refundableBalance = $this->PopbillFax->GetRefundableBalance($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundableBalance', ['refundableBalance' => $refundableBalance]);
    }

    /**
     * 포인트 환불에 대한 상세정보 1건을 확인합니다.
     * - https://developers.popbill.com/reference/fax/php/common-api/point#GetRefundInfo
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
            $result = $this->PopbillFax->GetRefundInfo($CorpNum, $RefundCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundInfo', ['result' => $result]);
    }
}
