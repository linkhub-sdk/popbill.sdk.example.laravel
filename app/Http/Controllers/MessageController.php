<?php

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

class MessageController extends Controller
{
    public function __construct() {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 문자 서비스 클래스 초기화
        $this->PopbillMessaging = new PopbillMessaging(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정값, true-개발용, false-상업용
        $this->PopbillMessaging->IsTest(config('popbill.IsTest'));

        // 인증토큰의 IP제한기능 사용여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillMessaging->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 팝빌 API 서비스 고정 IP 사용여부, true-사용, false-미사용, 기본값(false)
        $this->PopbillMessaging->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬서버 시간 사용 여부, true-사용, false-미사용, 기본값(true)
        $this->PopbillMessaging->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request){
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 문자 발신번호 등록여부를 확인합니다.
    * - 발신번호 상태가 '승인'인 경우에만 code가 1로 반환됩니다.
     * - https://docs.popbill.com/message/phplaravel/api#CheckSenderNumber
     */
    public function CheckSenderNumber(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 확인할 발신번호
        $senderNumber = '';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CheckSenderNumber($testCorpNum, $senderNumber, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 발신번호를 등록하고 내역을 확인하는 문자 발신번호 관리 페이지 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetSenderNumberMgtURL
     */
    public function GetSenderNumberMgtURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetSenderNumberMgtURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "문자 발신번호 팝업 URL" , 'value' => $url]);
    }

    /**
     * 팝빌에 등록한 연동회원의 문자 발신번호 목록을 확인합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetSenderNumberList
     */
    public function GetSenderNumberList(){

        // 팝빌회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetSenderNumberList($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetSenderNumberList', ['Result' => $result] );
    }

    /**
     * 최대 90byte의 단문(SMS) 메시지 1건 전송을 팝빌에 접수합니다.
     * - https://docs.popbill.com/message/phplaravel/api#SendSMS
     */
    public function SendSMS(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약전송일시(yyyyMMddHHmmss) ex) 20151212230000, null인 경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        $Messages[] = array(
            'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
            'sndnm' => '발신자명',   // 발신자명
            'rcv' => '',   // 수신번호
            'rcvnm' => '수신자성명',  // 수신자성명
            'msg' => '안녕하세요.' // 메시지 내용
        );

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillMessaging->SendSMS($testCorpNum, '', '', $Messages, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'SMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 90byte의 단문(SMS) 메시지 다수건 전송을 팝빌에 접수합니다. (최대 1,000건)
     * - 모든 수신자에게 동일한 내용을 전송하거나(동보전송), 수신자마다 개별 내용을 전송할 수 있습니다(대량전송).
     * - https://docs.popbill.com/message/phplaravel/api#SendSMS
     */
    public function SendSMS_Multi(){

        // 팝빌 회원 사업자번호, "-" 제외 10자리
        $testCorpNum = '1234567890';

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인 경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        // 문자전송정보 최대 1000건까지 호출가능
        for ($i = 0; $i < 10; $i++ ) {
            $Messages[] = array(
                'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
                'sndnm' => '발신자명',   // 발신자명
                'rcv' => '',   // 수신번호
                'rcvnm' => '수신자성명'.$i, // 수신자성명
                'msg' => '개별 메시지 내용'  // 개별 메시지 내용
            );
        }

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillMessaging->SendSMS($testCorpNum,'','', $Messages, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'SMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 2,000byte의 장문(LMS) 메시지 1건 전송을 팝빌에 접수합니다.
     * - https://docs.popbill.com/message/phplaravel/api#SendLMS
     */
    public function SendLMS(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약전송일시(yyyyMMddHHmmss), null인경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        $Messages[] = array(
            'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
            'sndnm' => '발신자명',   // 발신자명
            'rcv' => '',   // 수신번호
            'rcvnm' => '수신자성명',    // 수신자 성명
            'msg' => '메시지 내용',  // 메시지 내용. 장문은 2000byte로 길이가 조정되어 전송됨.
            'sjt' => '메시지 제목'  // 메시지 제목
        );

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillMessaging->SendLMS($testCorpNum, '', '', '', $Messages, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'LMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
    * 최대 2,000byte의 장문(LMS) 메시지 다수건 전송을 팝빌에 접수합니다. (최대 1,000건)
    * - 모든 수신자에게 동일한 내용을 전송하거나(동보전송), 수신자마다 개별 내용을 전송할 수 있습니다(대량전송).
    * - https://docs.popbill.com/message/phplaravel/api#SendLMS
    */
    public function SendLMS_Multi(){

        // 팝빌 회원 사업자번호, "-" 제외 10자리
        $testCorpNum = '1234567890';

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        for ($i = 0; $i < 10; $i++){
            $Messages[] = array(
                'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
                'sndnm' => '발신자명',   // 발신자명
                'rcv' => '',   // 수신번호
                'rcvnm' => '수신자성명'.$i, // 수신자 성명
                'msg' => '개별 메시지 내용',  // 개별 메시지 내용. 장문은 2000byte로 길이가 조정되어 전송됨.
                'sjt' => '개발 메시지 제목'  // 개별 메시지 내용
            );
        }

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillMessaging->SendLMS($testCorpNum, '', '', '', $Messages, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'LMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 최대 2,000byte의 메시지와 이미지로 구성된 포토문자(MMS) 1건 전송을 팝빌에 접수합니다.
     * - 이미지 파일 포맷/규격 : 최대 300Kbyte(JPEG, JPG), 가로/세로 1,000px 이하 권장
     * - https://docs.popbill.com/message/phplaravel/api#SendMMS
     */
    public function SendMMS(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약전송일시(yyyyMMddHHmmss) ex)20161108200000, null인경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        $Messages[] = array(
            'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
            'sndnm' => '발신자명',   // 발신자명
            'rcv' => '',   // 수신번호
            'rcvnm' => '수신자성명',   // 수신자 성명
            'msg' => '메시지 내용', // 메시지 내용. 장문은 2000byte로 길이가 조정되어 전송됨.
            'sjt' => '메시지 제목' // 메시지 제목
        );
        // 최대 300KByte, JPEG 파일포맷 전송가능
        $Files = array('/image.jpg');

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillMessaging->SendMMS($testCorpNum,'','','',$Messages, $Files, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => 'MMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
    * 최대 2,000byte의 메시지와 이미지로 구성된 포토문자(MMS) 다수건 전송을 팝빌에 접수합니다. (최대 1,000건)
    *  - 모든 수신자에게 동일한 내용을 전송하거나(동보전송), 수신자마다 개별 내용을 전송할 수 있습니다(대량전송).
    *  - 이미지 파일 포맷/규격 : 최대 300Kbyte(JPEG), 가로/세로 1,000px 이하 권장
    * - https://docs.popbill.com/message/phplaravel/api#SendMMS
    */
    public function SendMMS_Multi(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약전송일시(yyyyMMddHHmmss) ex) 20161108200000, null인경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        // 전송정보 배열, 최대 1000건
        for ($i = 0; $i < 10; $i++){
            $Messages[] = array(
                'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
                'sndnm' => '발신자명',   // 발신자명
                'rcv' => '',   // 수신번호
                'rcvnm' => '수신자성명'.$i, // 수신자성명
                'msg' => '개별 메시지 내용',  // 개별 메시지 내용
                'sjt' => '개발 메시지 제목'  // 개별 메시지 제목
            );
        }

        // 최대 300KByte, JPEG 파일포맷 전송가능
        $Files = array('/image.jpg');

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillMessaging->SendMMS($testCorpNum, '', '', '', $Messages, $Files, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'MMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 메시지 길이(90byte)에 따라 단문/장문(SMS/LMS)을 자동으로 인식하여 1건의 메시지를 전송을 팝빌에 접수합니다.
     * - https://docs.popbill.com/message/phplaravel/api#SendXMS
     */
    public function SendXMS(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약전송일시(yyyyMMddHHmmss) ex)20151212230000, null인경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        $Messages[] = array(
            'snd' => '',  // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
            'sndnm' => '발신자명',   // 발신자명
            'rcv' => '',   // 수신번호
            'rcvnm' => '수신자성명',  // 수신자성명
            'msg' => '장문 메시지 내용 장문으로 보내는 기준은 메시지 길이을 기준으로 90byte이상입니다. 2000byte에서 길이가 조정됩니다.' // 메시지 내용
        );

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $receiptNum = $this->PopbillMessaging->SendXMS($testCorpNum, '', '', '', $Messages, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'XMS 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 메시지 길이(90byte)에 따라 단문/장문(SMS/LMS)을 자동으로 인식하여 다수건의 메시지 전송을 팝빌에 접수합니다. (최대 1,000건)
     *  - https://docs.popbill.com/message/phplaravel/api#SendXMS
     */
    public function SendXMS_Multi(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 문자전송정보 배열, 최대 1000건
        $Messages = array();
        for ( $i = 0; $i < 100; $i++ ) {
            $Messages[] = array(
                'snd' => '',    // 발신번호, 팝빌에 등록되지 않은 발신번호 기재시 오류처리
                'sndnm' => '발신자명',     // 발신자명
                'rcv' => '',     // 수신번호
                'rcvnm' => '수신자성명',     // 수신자성명
                'sjt' => '개별 메시지 제목', // 개별전송 메시지 제목
                'msg' => '개별 메시지 내용'  // 개별전송 메시지 내용
            );
        }

        // 예약전송일시(yyyyMMddHHmmss) ex)20161108200000, null인경우 즉시전송
        $reserveDT = null;

        // 광고문자 전송여부
        $adsYN = false;

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 부여하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $requestNum = '';

        try {
            $receiptNum = $this->PopbillMessaging->SendXMS($testCorpNum, '', '', '', $Messages, $reserveDT, $adsYN, $testUserID, '', '', $requestNum);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => 'XMS 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 예약접수된 문자 메시지 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://docs.popbill.com/message/phplaravel/api#CancelReserve
     */
    public function CancelReserve(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약문자전송 요청시 발급받은 접수번호
        $ReceiptNum = '022040511000000020';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CancelReserve($testCorpNum ,$ReceiptNum, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 예약접수된 문자 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://docs.popbill.com/message/phplaravel/api#CancelReserveRN
     */
    public function CancelReserveRN(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약문자전송 요청시 할당한 전송요청번호
        $requestNum = '';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CancelReserveRN($testCorpNum ,$requestNum, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    /**
     * 팝빌에서 반환받은 접수번호와 수신번호를 통해 예약접수된 문자 메시지 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://docs.popbill.com/message/phplaravel/api#CancelReservebyRCV
     */
    public function CancelReservebyRCV(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약문자전송 요청시 발급받은 접수번호
        $ReceiptNum = '022102116000000028';

        // 예약문자전송 요청시 입력한 수신번호
        $receiveNum = '01012341234';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';
        

        try {
            $result = $this->PopbillMessaging->CancelReservebyRCV($testCorpNum ,$ReceiptNum, $receiveNum, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 파트너가 할당한 전송요청 번호와 수신번호를 통해 예약접수된 문자 전송을 취소합니다. (예약시간 10분 전까지 가능)
     * - https://docs.popbill.com/message/phplaravel/api#CancelReserveRNbyRCV
     */
    public function CancelReserveRNbyRCV(){

        // 팝빌 회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 예약문자전송 요청시 할당한 전송요청번호
        $requestNum = '20221021_001';

        // 예약문자전송 요청시 입력한 수신번호
        $receiveNum = '01012341234';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CancelReserveRNbyRCV($testCorpNum ,$requestNum, $receiveNum, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 문자 전송상태 및 결과를 확인합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetMessages
     */
    public function GetMessages(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 문자전송 요청 시 발급받은 접수번호(receiptNum)
        $ReceiptNum = '022040511000000020';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetMessages($testCorpNum, $ReceiptNum, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Message/GetMessage', ['Result' => $result] );
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 문자 전송상태 및 결과를 확인합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetMessagesRN
     */
    public function GetMessagesRN(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 문자전송 요청 시 할당한 전송요청번호(requestNum)
        $requestNum = '';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetMessagesRN($testCorpNum, $requestNum, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Message/GetMessage', ['Result' => $result] );
    }

    /**
     * 문자전송에 대한 전송결과 요약정보를 확인합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetStates
     */
    public function GetStates(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 문자전송 요청 시 발급받은 접수번호 배열(receiptNum)
        $ReceiptNumList = array();

        array_push($ReceiptNumList, '022040511000000020');

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->GetStates($testCorpNum, $ReceiptNumList, $testUserID);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Message/GetStates', ['Result' => $result] );
    }

    /**
     * 검색조건에 해당하는 문자 전송내역을 조회합니다. (조회기간 단위 : 최대 2개월)
     * - 문자 접수일시로부터 6개월 이내 접수건만 조회할 수 있습니다.
     * - https://docs.popbill.com/message/phplaravel/api#Search
     */
    public function Search(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 시작일자
        $SDate = '20220401';

        // 종료일자
        $EDate = '20220430';

        // 전송상태 배열 ("1" , "2" , "3" , "4" 중 선택, 다중 선택 가능)
        // └ 1 = 대기 , 2 = 성공 , 3 = 실패 , 4 = 취소
        // - 미입력 시 전체조회
        $State = array('1', '2', '3', '4');

        // 검색대상 배열 ("SMS" , "LMS" , "MMS" 중 선택, 다중 선택 가능)
        // └ SMS = 단문 , LMS = 장문 , MMS = 포토문자
        // - 미입력 시 전체조회
        $Item = array( 'SMS', 'LMS', 'MMS' );

        // 예약여부 (false , true 중 택 1)
        // └ false = 전체조회, true = 예약전송건 조회
        // - 미입력시 기본값 false 처리
        $ReserveYN = false;

        // 개인조회 여부 (false , true 중 택 1)
        // └ false = 접수한 문자 전체 조회 (관리자권한)
        // └ true = 해당 담당자 계정으로 접수한 문자만 조회 (개인권한)
        // - 미입력시 기본값 false 처리
        $SenderYN = false;

        // 페이지번호
        $Page = 1;

        // 페이지 검색개수, 기본값 500, 최대값 1000
        $PerPage = 500;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = 'D';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        // 조회하고자 하는 발신자명 또는 수신자명
        // - 미입력시 전체조회
        $QString = '';

        try {
            $result = $this->PopbillMessaging->Search( $testCorpNum, $SDate, $EDate, $State, $Item, $ReserveYN, $SenderYN, $Page, $PerPage, $Order, $testUserID, $QString );
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('Message/Search', ['Result' => $result] );
    }

    /**
     * 문자 전송내역 확인 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetSentListURL
     */
    public function GetSentListURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetSentListURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "문자 전송내역 팝업 URL" , 'value' => $url]);
    }

    /**
     * 전용 080 번호에 등록된 수신거부 목록을 반환합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetAutoDenyList
     */
    public function GetAutoDenyList(){

        // 팝빌회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $result = $this->PopbillMessaging->GetAutoDenyList($testCorpNum);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('Message/GetAutoDenyList', ['Result' => $result] );
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetBalance
     */
    public function GetBalance(){

        // 팝빌회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillMessaging->GetBalance($testCorpNum);
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
     * - https://docs.popbill.com/message/phplaravel/api#GetChargeURL
     */
    public function GetChargeURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetChargeURL($testCorpNum, $testUserID);
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
     * - https://docs.popbill.com/message/phplaravel/api#GetPaymentURL
     */
    public function GetPaymentURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetPaymentURL($testCorpNum, $testUserID);
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
     * - https://docs.popbill.com/message/phplaravel/api#GetUseHistoryURL
     */
    public function GetUseHistoryURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetUseHistoryURL($testCorpNum, $testUserID);
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
     * - https://docs.popbill.com/message/phplaravel/api#GetPartnerBalance
     */
    public function GetPartnerBalance(){

        // 팝빌회원 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillMessaging->GetPartnerBalance($testCorpNum);
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
     * - https://docs.popbill.com/message/phplaravel/api#GetPartnerURL
     */
    public function GetPartnerURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillMessaging->GetPartnerURL($testCorpNum, $TOGO);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
    }

    /**
    * 문자 전송시 과금되는 포인트 단가를 확인합니다.
    * - https://docs.popbill.com/message/phplaravel/api#GetUnitCost
    */
    public function GetUnitCost(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 문자 전송유형 ENumMessageType::SMS(단문), ENumMessageType::LMS(장문), ENumMessageType::MMS(포토)
        $messageType = ENumMessageType::SMS;

        try {
            $unitCost= $this->PopbillMessaging->GetUnitCost($testCorpNum, $messageType);
        }
        catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "문자메시지(".ENumMessageType::SMS.") 전송단가", 'value' => $unitCost]);
    }

    /**
     * 팝빌 문자 API 서비스 과금정보를 확인합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetChargeInfo
     */
    public function GetChargeInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        // 문자 전송유형, ENumMessageType::SMS(단문), ENumMessageType::LMS(장문), ENumMessageType::MMS(포토)
        $messageType = ENumMessageType::SMS;

        try {
            $result = $this->PopbillMessaging->GetChargeInfo($testCorpNum, $messageType, $testUserID);
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
     * - https://docs.popbill.com/message/phplaravel/api#CheckIsMember
     */
    public function CheckIsMember(){

        // 사업자번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // ./config/popbill.php 에 선언된 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillMessaging->CheckIsMember($testCorpNum, $LinkID);
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
     * - https://docs.popbill.com/message/phplaravel/api#CheckID
     */
    public function CheckID(){

        // 조회할 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->CheckID($testUserID);
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
     * - https://docs.popbill.com/message/phplaravel/api#JoinMember
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
            $result = $this->PopbillMessaging->JoinMember($joinForm);
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
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetAccessURL
     */
    public function GetAccessURL(){

        // 팝빌 회원 사업자 번호, "-"제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $url = $this->PopbillMessaging->GetAccessURL($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);
    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetCorpInfo
     */
    public function GetCorpInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillMessaging->GetCorpInfo($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다.
     * - https://docs.popbill.com/message/phplaravel/api#UpdateCorpInfo
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

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result =  $this->PopbillMessaging->UpdateCorpInfo($testCorpNum, $CorpInfo, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 담당자(팝빌 로그인 계정)를 추가합니다.
     * - https://docs.popbill.com/message/phplaravel/api#RegistContact
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
        $ContactInfo->email = '';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $result = $this->PopbillMessaging->RegistContact($testCorpNum, $ContactInfo, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보을 확인합니다.
     * - https://docs.popbill.com/message/phplaravel/api#GetContactInfo
     */
    public function GetContactInfo(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        //확인할 담당자 아이디
        $contactID = 'checkContact';

        // 팝빌회원 아이디
        $testUserID = 'testkorea';

        try {
            $ContactInfo = $this->PopbillMessaging->GetContactInfo($testCorpNum, $contactID, $testUserID);
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
     * - https://docs.popbill.com/message/phplaravel/api#ListContact
     */
    public function ListContact(){

        // 팝빌회원 사업자번호, '-'제외 10자리
        $testCorpNum = '1234567890';

        // 팝빌 회원 아이디
        $testUserID = 'testkorea';

        try {
            $ContactList = $this->PopbillMessaging->ListContact($testCorpNum, $testUserID);
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://docs.popbill.com/message/phplaravel/api#UpdateContact
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
        $ContactInfo->email = '';

        // 담당자 권한, 1 : 개인권한, 2 : 읽기권한, 3: 회사권한
        $ContactInfo->searchRole = 3;

        try {
            $result = $this->PopbillMessaging->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
            $code = $result->code;
            $message = $result->message;
        } catch(PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
}
