<?php
/**
  * 팝빌 문자 API PHP SDK Laravel Example
  *
  * Laravel 연동 튜토리얼 안내 : https://developers.popbill.com/guide/sms/php/getting-started/tutorial?fwn=laravel
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
use Linkhub\Popbill\PopbillMessaging;
use Linkhub\Popbill\ENumMessageType;
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class MessageController extends Controller
{
    public function __construct()
    {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 문자 서비스 클래스 초기화
        $this->PopbillMessaging = new PopbillMessaging(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정, true-테스트, false-운영(Production), (기본값:false)
        $this->PopbillMessaging->IsTest(config('popbill.IsTest'));

        // 인증토큰 IP 검증 설정, true-사용, false-미사용, (기본값:true)
        $this->PopbillMessaging->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 통신 IP 고정, true-사용, false-미사용, (기본값:false)
        $this->PopbillMessaging->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬시스템 시간 사용여부, true-사용, false-미사용, (기본값:true)
        $this->PopbillMessaging->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request)
    {
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 문자 발신번호 등록여부를 확인합니다.
     * - 발신번호 상태가 '승인'인 경우에만 code가 1로 반환됩니다.
     * - https://developers.popbill.com/reference/sms/php/api/sendnum#CheckSenderNumber
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
            $result = $this->PopbillMessaging->CheckSenderNumber($CorpNum, $SenderNumber, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 발신번호를 등록하고 내역을 확인하는 문자 발신번호 관리 페이지 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/sms/php/api/sendnum#GetSenderNumberMgtURL
     */
    public function GetSenderNumberMgtURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetSenderNumberMgtURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "문자 발신번호 팝업 URL", 'value' => $url]);
    }

    /**
     * 팝빌에 등록한 연동회원의 문자 발신번호 목록을 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/api/sendnum#GetSenderNumberList
     */
    public function GetSenderNumberList()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetSenderNumberList($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetSenderNumberList', ['Result' => $result]);
    }

    /**
     * 최대 90byte의 단문(SMS) 메시지 1건 전송을 팝빌에 접수합니다.
     * - https://developers.popbill.com/reference/sms/php/api/send#SendSMS
     */
    public function SendSMS()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 사전 등록된 발신번호
        $Sender = '';

        // 메시지 내용
        $Content = '';

        // 문자전송정보
        $Messages[] = array(
            'rcv' => '',   // 수신번호
            'rcvnm' => '수신자성명'  // 수신자성명
        );

        // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인 경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        $senderName = '';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendSMS($CorpNum, $Sender, $Content, $Messages, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'SMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 90byte의 단문(SMS) 메시지 다수건 전송을 팝빌에 접수합니다. (최대 1,000건)
     * - 모든 수신자에게 동일한 내용을 전송하거나(동보전송), 수신자마다 개별 내용을 전송할 수 있습니다(대량전송).
     * - https://developers.popbill.com/reference/sms/php/api/send#SendSMS
     */
    public function SendSMS_Multi()
    {

        // 팝빌회원 사업자번호, "-" 제외 10자리
        $CorpNum = '1234567890';

        // 발신번호
        // 대량전송 경우 사용하지 않음
        $Sender = null;

        // 메시지 내용
        // 대량전송 경우 사용하지 않음
        $Content = null;

        // 문자전송정보, 최대 1,000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                'snd' => '',             // 발신번호
                'sndnm' => '발신자명',    // 발신자명
                'rcv' => '',              // 수신번호
                'rcvnm' => '수신자성명' . $i, // 수신자성명
                'msg' => '개별 메시지 내용',  // 개별 메시지 내용
                'interOPRefKey' => '20230127' . $i    // 파트너 지정키, 수신자 구별용 메모
            );
        }

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인 경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        // 대량전송 경우 사용하지 않음.
        $senderName = null;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendSMS($CorpNum, $Sender, $Content, $Messages, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'SMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 2,000byte의 장문(LMS) 메시지 1건 전송을 팝빌에 접수합니다.
     * - https://developers.popbill.com/reference/sms/php/api/send#SendLMS
     */
    public function SendLMS()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 사전 등록된 발신번호
        $Sender = '';

        // 메시지 제목
        $Subject = '';

        // 메시지 내용
        $Content = '';

        // 믄지전송정보
        $Messages[] = array(
            'rcv' => '',              // 수신번호
            'rcvnm' => '수신자성명'    // 수신자 성명
        );

        // 예약전송일시(yyyyMMddHHmmss), null인경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        $senderName = '';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendLMS($CorpNum, $Sender, $Subject, $Content, $Messages, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'LMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 2,000byte의 장문(LMS) 메시지 다수건 전송을 팝빌에 접수합니다. (최대 1,000건)
     * - 모든 수신자에게 동일한 내용을 전송하거나(동보전송), 수신자마다 개별 내용을 전송할 수 있습니다(대량전송).
     * - https://developers.popbill.com/reference/sms/php/api/send#SendLMS
     */
    public function SendLMS_Multi()
    {

        // 팝빌회원 사업자번호, "-" 제외 10자리
        $CorpNum = '1234567890';

        // 발신번호
        // 대량전송 경우 사용하지 않음
        $Sender = null;

        // 메시지 제목
        // 대량전송 경우 사용하지 않음
        $Subject = null;

        // 메시지 내용
        // 대량전송 경우 사용하지 않음
        $Content = null;

        // 문자전송정보, 최대 1,000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
                'sndnm' => '발신자명',   // 발신자명
                'rcv' => '',   // 수신번호
                'rcvnm' => '수신자성명' . $i, // 수신자 성명
                'msg' => '개별 메시지 내용',  // 개별 메시지 내용. 장문은 2000byte로 길이가 조정되어 전송됨.
                'sjt' => '개발 메시지 제목',  // 개별 메시지 내용
                'interOPRefKey' => '20230127' . $i    // 파트너 지정키, 동보전송시 수신자 구별용 메모
            );
        }

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        // 대량전송 경우 사용하지 않음
        $senderName = null;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendLMS($CorpNum, $Sender, $Subject, $Content, $Messages, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'LMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 2,000byte의 메시지와 이미지로 구성된 포토문자(MMS) 1건 전송을 팝빌에 접수합니다.
     * - 이미지 파일 포맷/규격 : 최대 300Kbyte(JPEG, JPG), 가로/세로 1,000px 이하 권장
     * - https://developers.popbill.com/reference/sms/php/api/send#SendMMS
     */
    public function SendMMS()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 사전 등록된 발신번호
        $Sender = '';

        // 메시지 제목
        $Subject = '';

        // 메시지 내용
        $Content = '';

        // 문자전송정보
        $Messages[] = array(
            'rcv' => '',            // 수신번호
            'rcvnm' => '수신자성명'  // 수신자 성명
        );

         // 최대 300KByte, JPEG 파일포맷 전송가능
        $FilePaths = array('/image.jpg');

        // 예약전송일시(yyyyMMddHHmmss) ex)20161108200000, null인경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        $senderName = '';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendMMS($CorpNum, $Sender, $Subject, $Content, $Messages, $FilePaths, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => 'MMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 2,000byte의 메시지와 이미지로 구성된 포토문자(MMS) 다수건 전송을 팝빌에 접수합니다. (최대 1,000건)
     * - 모든 수신자에게 동일한 내용을 전송하거나(동보전송), 수신자마다 개별 내용을 전송할 수 있습니다(대량전송).
     * - 이미지 파일 포맷/규격 : 최대 300Kbyte(JPEG), 가로/세로 1,000px 이하 권장
     *  - https://developers.popbill.com/reference/sms/php/api/send#SendMMS
     */
    public function SendMMS_Multi()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 발신번호
        // 대량전송 경우 사용하지 않음
        $Sender = null;

        // 메시지 내용
        // 대량전송 경우 사용하지 않음
        $Subject = null;

        // 메시지 내용
        // 대량전송 경우 사용하지 않음
        $Content = null;

        // 문자전송정보, 최대 1,000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                'snd' => '',            // 발신번호
                'sndnm' => '발신자명',   // 발신자명
                'rcv' => '',            // 수신번호
                'rcvnm' => '수신자성명' . $i, // 수신자성명
                'msg' => '개별 메시지 내용',  // 개별 메시지 내용
                'sjt' => '개발 메시지 제목',  // 개별 메시지 제목
                'interOPRefKey' => '20230127' . $i    // 파트너 지정키, 동보전송시 수신자 구별용 메모
            );
        }

        // 최대 300KByte, JPEG 파일포맷 전송가능
        $FilePaths = array('/image.jpg');

        // 예약전송일시(yyyyMMddHHmmss) ex) 20161108200000, null인경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        // 대량전송 경우 사용하지 않음.
        $senderName = null;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendMMS($CorpNum, $Sender, $Subject, $Content, $Messages, $FilePaths, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'MMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 메시지 길이(90byte)에 따라 단문/장문(SMS/LMS)을 자동으로 인식하여 1건의 메시지를 전송을 팝빌에 접수합니다.
     * - https://developers.popbill.com/reference/sms/php/api/send#SendXMS
     */
    public function SendXMS()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 사전 등록된 발신번호
        $Sender = '';

        // 메시지 제목
        $Subject = '';

        // 메시지 내용
        $Content = '';

        $Messages[] = array(
            'rcv' => '',            // 수신번호
            'rcvnm' => '수신자성명'  // 수신자성명
        );

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        $senderName = '';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendXMS($CorpNum, $Sender, $Subject, $Content, $Messages, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'XMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 메시지 길이(90byte)에 따라 단문/장문(SMS/LMS)을 자동으로 인식하여 다수건의 메시지 전송을 팝빌에 접수합니다. (최대 1,000건)
     * - https://developers.popbill.com/reference/sms/php/api/send#SendXMS
     */
    public function SendXMS_Multi()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 사전 등록된 발신번호
        // 대량전송 경우 사용하지 않음
        $Sender = null;

        // 메시지 제목
        // 대량전송 경우 사용하지 않음
        $Subject = null;

        // 메시지 내용
        // 대량전송 경우 사용하지 않음
        $Content = null;

        // 문자전송정보, 최대 1000건
        $Messages = array();
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                'snd' => '',    // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
                'sndnm' => '발신자명',     // 발신자명
                'rcv' => '',     // 수신번호
                'rcvnm' => '수신자성명',     // 수신자성명
                'sjt' => '개별 메시지 제목', // 개별전송 메시지 제목
                'msg' => '개별 메시지 내용',  // 개별전송 메시지 내용
                'interOPRefKey' => '20230127' . $i    // 파트너 지정키, 동보전송시 수신자 구별용 메모
            );
        }

        // 예약전송일시(yyyyMMddHHmmss) ex)20161108200000, null인경우 즉시전송
        $ReserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 발신자명
        // 단건전송, 동보전송 경우 사용
        $senderName = null;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        try {
            $receiptNum = $this->PopbillMessaging->SendXMS($CorpNum, $Sender, $Subject, $Content, $Messages, $ReserveDT, $adsYN, $UserID, $senderName, $RequestNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'XMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 예약접수된 문자 메시지 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/sms/php/api/send#CancelReserve
     */
    public function CancelReserve()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약문자전송 요청시 발급받은 접수번호
        $ReceiptNum = '022040511000000020';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CancelReserve($CorpNum, $ReceiptNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 예약접수된 문자 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/sms/php/api/send#CancelReserveRN
     */
    public function CancelReserveRN()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약문자전송 요청시 할당한 전송요청번호
        $RequestNum = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CancelReserveRN($CorpNum, $RequestNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    /**
     * 팝빌에서 반환받은 접수번호와 수신번호를 통해 예약접수된 문자 메시지 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/sms/php/api/send#CancelReservebyRCV
     */
    public function CancelReservebyRCV()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약문자전송 요청시 발급받은 접수번호
        $ReceiptNum = '022102116000000028';

        // 예약문자전송 요청시 입력한 수신번호
        $receiveNum = '01012341234';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CancelReservebyRCV($CorpNum, $ReceiptNum, $receiveNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 파트너가 할당한 전송요청 번호와 수신번호를 통해 예약접수된 문자 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/sms/php/api/send#CancelReserveRNbyRCV
     */
    public function CancelReserveRNbyRCV()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약문자전송 요청시 할당한 전송요청번호
        $RequestNum = '20230102_001';

        // 예약문자전송 요청시 입력한 수신번호
        $receiveNum = '01012341234';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CancelReserveRNbyRCV($CorpNum, $RequestNum, $receiveNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 문자 전송상태 및 결과를 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/api/info#GetMessages
     */
    public function GetMessages()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 문자전송 요청 시 발급받은 접수번호(receiptNum)
        $ReceiptNum = '022040511000000020';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetMessages($CorpNum, $ReceiptNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Message/GetMessage', ['Result' => $result]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 문자 전송상태 및 결과를 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/api/info#GetMessagesRN
     */
    public function GetMessagesRN()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 문자전송 요청 시 할당한 전송요청번호(requestNum)
        $RequestNum = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetMessagesRN($CorpNum, $RequestNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Message/GetMessage', ['Result' => $result]);
    }

    /**
     * 검색조건에 해당하는 문자 전송내역을 조회합니다. (조회기간 단위 : 최대 2개월)
     * - 문자 접수일시로부터 6개월 이내 접수건만 조회할 수 있습니다.
     * - https://developers.popbill.com/reference/sms/php/api/info#Search
     */
    public function Search()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 시작일자
        $SDate = '20250801';

        // 종료일자
        $EDate = '20250831';

        // 전송상태 배열 ("1" , "2" , "3" , "4" 중 선택, 다중 선택 가능)
        // └ 1 = 대기 , 2 = 성공 , 3 = 실패 , 4 = 취소
        // - 미입력 시 전체조회
        $State = array('1', '2', '3', '4');

        // 검색대상 배열 ("SMS" , "LMS" , "MMS" 중 선택, 다중 선택 가능)
        // └ SMS = 단문 , LMS = 장문 , MMS = 포토문자
        // - 미입력 시 전체조회
        $Item = array('SMS', 'LMS', 'MMS');

        // 예약여부 (null, false, true 중 택 1)
        // └ null = 전체, false = 즉시전송건, true = 예약전송건
        // - 미입력 시 전체조회
        $ReserveYN = null;

        // 사용자권한별 조회
        // └ true = 팝빌회원 아이디(UserID)로 전송한 문자만 조회
        // └ false = 전송한 문자 전체 조회 : 기본값
        $SenderYN = false;

        // 페이지번호
        $Page = 1;

        // 페이지 검색개수, 기본값 500, 최대값 1000
        $PerPage = 500;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = 'D';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 조회 검색어(발신자명/수신자명)
        // - 미입력시 전체조회
        $QString = null;

        try {
            $result = $this->PopbillMessaging->Search($CorpNum, $SDate, $EDate, $State, $Item, $ReserveYN, $SenderYN, $Page, $PerPage, $Order, $UserID, $QString);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Message/Search', ['Result' => $result]);
    }

    /**
     * 문자 전송내역 확인 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/sms/php/api/info#GetSentListURL
     */
    public function GetSentListURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetSentListURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "문자 전송내역 팝업 URL", 'value' => $url]);
    }

    /**
     * 전용 080 번호에 등록된 수신거부 목록을 반환합니다.
     * - https://developers.popbill.com/reference/sms/php/api/info#GetAutoDenyList
     */
    public function GetAutoDenyList()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetAutoDenyList($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Message/GetAutoDenyList', ['Result' => $result]);
    }

    /**
     * 팝빌회원에 등록된 080 수신거부 번호 정보를 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/api/info#CheckAutoDenyNumber
     */
    public function CheckAutoDenyNumber()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CheckAutoDenyNumber($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Message/CheckAutoDenyNumber', ['Result' => $result]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetBalance
     */
    public function GetBalance()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillMessaging->GetBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetUseHistory
     */
    public function GetUseHistory()
    {

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20250801";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20250831";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 정렬방향, A-오름차순, D-내림차순
        $Order = "D";

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetUseHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetPaymentHistory
     */
    public function GetPaymentHistory()
    {

        // 팝빌회원 사업자번호 (하이픈 '-' 제외 10 자리)
        $CorpNum = "1234567890";

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = "20250801";

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = "20250831";

        // 페이지번호
        $Page = 1;

        // 페이지당 검색개수, 최대 1000건
        $PerPage = 30;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetPaymentHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 환불신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetRefundHistory
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
            $result = $this->PopbillMessaging->GetRefundHistory($CorpNum, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트를 환불 신청합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#Refund
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
            $result = $this->PopbillMessaging->Refund($CorpNum, $RefundForm, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/point#PaymentRequest
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
            $result = $this->PopbillMessaging->PaymentRequest($CorpNum, $PaymentForm, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentResponse', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 무통장 입금신청내역 1건을 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetSettleResult
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
            $result = $this->PopbillMessaging->GetSettleResult($CorpNum, $SettleCode, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetChargeURL
     */
    public function GetChargeURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetChargeURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetPaymentURL
     */
    public function GetPaymentURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetPaymentURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetUseHistoryURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetPartnerBalance
     */
    public function GetPartnerBalance()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillMessaging->GetPartnerBalance($CorpNum);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetPartnerURL
     */
    public function GetPartnerURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillMessaging->GetPartnerURL($CorpNum, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 문자 전송시 과금되는 포인트 단가를 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetUnitCost
     */
    public function GetUnitCost()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 문자 전송유형 ENumMessageType::SMS(단문), ENumMessageType::LMS(장문), ENumMessageType::MMS(포토)
        $messageType = ENumMessageType::SMS;

        try {
            $unitCost = $this->PopbillMessaging->GetUnitCost($CorpNum, $messageType);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "문자메시지(" . ENumMessageType::SMS . ") 전송단가", 'value' => $unitCost]);
    }

    /**
     * 팝빌 문자 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetChargeInfo
     */
    public function GetChargeInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 문자 전송유형, ENumMessageType::SMS(단문), ENumMessageType::LMS(장문), ENumMessageType::MMS(포토)
        $messageType = ENumMessageType::SMS;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetChargeInfo($CorpNum, $messageType, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/member#CheckIsMember
     */
    public function CheckIsMember()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // ./config/popbill.php 에 선언된 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillMessaging->CheckIsMember($CorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/member#CheckID
     */
    public function CheckID()
    {

        // 조회할 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CheckID($UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/member#JoinMember
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

        // 담당자 성명
        $JoinForm->ContactName = '담당자성명';

        // 담당자 메일
        $JoinForm->ContactEmail = '';

        // 담당자 휴대폰
        $JoinForm->ContactTEL = '';

        // 아이디
        $JoinForm->ID = 'userid_phpdd';

        // 비밀번호
        $JoinForm->Password = 'asdf1234!@';

        try {
            $result = $this->PopbillMessaging->JoinMember($JoinForm);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/member#GetAccessURL
     */
    public function GetAccessURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetAccessURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL", 'value' => $url]);
    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/member#GetCorpInfo
     */
    public function GetCorpInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillMessaging->GetCorpInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/member#UpdateCorpInfo
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
            $result =  $this->PopbillMessaging->UpdateCorpInfo($CorpNum, $CorpInfo, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/member#RegistContact
     */
    public function RegistContact()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 담당자 정보 객체 생성
        $ContactInfo = new ContactInfo();

        // 아이디
        $ContactInfo->id = 'testkorea001';

        // 비밀번호
        $ContactInfo->Password = 'asdf123!@#';

        // 담당자 성명
        $ContactInfo->personName = '담당자_수정';

        // 담당자 휴대폰
        $ContactInfo->tel = '';

        // 담당자 메일
        $ContactInfo->email = '';

        // 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->RegistContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/member#DeleteContact
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
            $result = $this->PopbillMessaging->DeleteContact($CorpNum, $TargetUserID, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/member#GetContactInfo
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
            $ContactInfo = $this->PopbillMessaging->GetContactInfo($CorpNum, $ContactID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/member#ListContact
     */
    public function ListContact()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactList = $this->PopbillMessaging->ListContact($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/member#UpdateContact
     */
    public function UpdateContact()
    {

        // 팝빌회원 사업자번호, '-' 제외 10자리
        $CorpNum = '1234567890';

        // 담당자 정보 객체 생성
        $ContactInfo = new ContactInfo();

        // 담당자 성명
        $ContactInfo->personName = '담당자_수정';

        // 아이디
        $ContactInfo->id = 'testkorea';

        // 담당자 휴대폰
        $ContactInfo->tel = '';

        // 담당자 메일
        $ContactInfo->email = '';

        // 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->UpdateContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/sms/php/common-api/member#QuitMember
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
            $result = $this->PopbillMessaging->QuitMember($CorpNum, $QuitReason, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PResponse', ['code' => $result->code, 'message' => $result->message]);
    }

    /**
     * 환불 가능한 포인트를 확인합니다. (보너스 포인트는 환불가능포인트에서 제외됩니다.)
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetRefundableBalance
     */
    public function GetRefundableBalance()
    {

        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $refundableBalance = $this->PopbillMessaging->GetRefundableBalance($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundableBalance', ['refundableBalance' => $refundableBalance]);
    }

    /**
     * 포인트 환불에 대한 상세정보 1건을 확인합니다.
     * - https://developers.popbill.com/reference/sms/php/common-api/point#GetRefundInfo
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
            $result = $this->PopbillMessaging->GetRefundInfo($CorpNum, $RefundCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundInfo', ['result' => $result]);
    }
}
