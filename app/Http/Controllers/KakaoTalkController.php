<?php
/**
  * 팝빌 카카오톡 API PHP SDK Laravel Example
  *
  * Laravel 연동 튜토리얼 안내 : https://developers.popbill.com/guide/kakaotalk/php/getting-started/tutorial?fwn=laravel
  * 연동 기술지원 연락처 : 1600-9854
  * 연동 기술지원 이메일 : code@linkhubcorp.com
  *
  * <테스트 연동개발 준비사항>
  *   비즈니스 채널 등록 및 알림톡 템플릿을 신청합니다.
  *    - 1. 비즈니스 채널 등록 (등록방법은 사이트/API 두가지 방식이 있습니다.)
  *       └ 팝빌 사이트 로그인 [문자/팩스] > [카카오톡] > [카카오톡 관리] > '카카오톡 채널 관리' 메뉴에서 등록
  *       └ GetPlusFriendMgtURL API 를 통해 반환된 URL을 이용하여 등록
  *    - 2. 알림톡 템플릿 신청 (등록방법은 사이트/API 두가지 방식이 있습니다.)
  *       └ 팝빌 사이트 로그인 [문자/팩스] > [카카오톡] > [카카오톡 관리] > '알림톡 템플릿 관리' 메뉴에서 등록
  *       └ GetATSTemplateMgtURL API 를 통해 URL을 이용하여 등록
  */
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillKakao;
use Linkhub\Popbill\ENumKakaoType;
use Linkhub\Popbill\KakaoButton;
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class KakaoTalkController extends Controller
{
    public function __construct()
    {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 카카오톡 서비스 클래스 초기화
        $this->PopbillKakao = new PopbillKakao(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정, true-테스트, false-운영(Production), (기본값:false)
        $this->PopbillKakao->IsTest(config('popbill.IsTest'));

        // 인증토큰 IP 검증 설정, true-사용, false-미사용, (기본값:true)
        $this->PopbillKakao->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 통신 IP 고정, true-사용, false-미사용, (기본값:false)
        $this->PopbillKakao->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬시스템 시간 사용여부, true-사용, false-미사용, (기본값:true)
        $this->PopbillKakao->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request)
    {
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 카카오톡 채널을 등록하고 내역을 확인하는 카카오톡 채널 관리 페이지 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/channel#GetPlusFriendMgtURL
     */
    public function GetPlusFriendMgtURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetPlusFriendMgtURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "카카오톡 발신번호 관리 팝업 URL", 'value' => $url]);
    }

    /**
     * 팝빌에 등록한 연동회원의 카카오톡 채널 목록을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/channel#ListPlusFriendID
     */
    public function ListPlusFriendID()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        try {
            $result = $this->PopbillKakao->ListPlusFriendID($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('KakaoTalk/ListPlusFriendID', ['Result' => $result]);
    }

    /**
     * 카카오톡 발신번호 등록여부를 확인합니다.
     * - 발신번호 상태가 '승인'인 경우에만 code가 1로 반환됩니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/sendnum#CheckSenderNumber
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
            $result = $this->PopbillKakao->CheckSenderNumber($CorpNum, $SenderNumber, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 발신번호를 등록하고 내역을 확인하는 카카오톡 발신번호 관리 페이지 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/sendnum#GetSenderNumberMgtURL
     */
    public function GetSenderNumberMgtURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetSenderNumberMgtURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "발신번호 관리 팝업 URL", 'value' => $url]);
    }

    /**
     * 팝빌에 등록한 연동회원의 카카오톡 발신번호 목록을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/sendnum#GetSenderNumberList
     */
    public function GetSenderNumberList()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $result = $this->PopbillKakao->GetSenderNumberList($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('GetSenderNumberList', ['Result' => $result]);
    }

    /**
     * 알림톡 템플릿을 신청하고 승인심사 결과를 확인하며 등록 내역을 확인하는 알림톡 템플릿 관리 페이지 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/template#GetATSTemplateMgtURL
     */
    public function GetATSTemplateMgtURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetATSTemplateMgtURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "알림톡 템플릿 관리 팝업 URL", 'value' => $url]);
    }

    /**
     * 승인된 알림톡 템플릿 정보를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/template#GetATSTemplate
     */
    public function GetATSTemplate()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        //확인할 템플릿 코드
        $templateCode = '021110000491';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->GetATSTemplate($CorpNum, $templateCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('KakaoTalk/GetATSTemplate', ['Result' => $result]);
    }

    /**
     * 승인된 알림톡 템플릿 목록을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/template#ListATSTemplate
     */
    public function ListATSTemplate()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        try {
            $result = $this->PopbillKakao->ListATSTemplate($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('KakaoTalk/ListATSTemplate', ['Result' => $result]);
    }

    /**
     * 승인된 템플릿의 내용을 작성하여 1건의 알림톡 전송을 팝빌에 접수합니다.
     * - 사전에 승인된 템플릿의 내용과 알림톡 전송내용(content)이 다를 경우 전송실패 처리됩니다.
     * - 전송실패 시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendATS
     */
    public function SendATS_one()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 승인된 알림톡 템플릿코드
        // └ 알림톡 템플릿 관리 팝업 URL(GetATSTemplateMgtURL API) 함수, 알림톡 템플릿 목록 확인(ListATStemplate API) 함수를 호출하거나
        //   팝빌사이트에서 승인된 알림톡 템플릿 코드를  확인 가능.
        $templateCode = '019020000163';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

        // 알림톡 내용, 최대 1000자
        $content = '[ 팝빌 ]' . PHP_EOL;
        $content .= '신청하신 #{템플릿코드}에 대한 심사가 완료되어 승인 처리되었습니다.해당 템플릿으로 전송 가능합니다.' . PHP_EOL . PHP_EOL;
        $content .= '문의사항 있으시면 파트너센터로 편하게 연락주시기 바랍니다.' . PHP_EOL . PHP_EOL;
        $content .= '팝빌 파트너센터 : 1600-8536' . PHP_EOL;
        $content .= 'support@linkhub.co.kr' . PHP_EOL;

        // 대체문자 유형(altSendType)이 "A"일 경우, 대체문자로 전송할 내용 (최대 2000byte)
        // └ 팝빌이 메시지 길이에 따라 단문(90byte 이하) 또는 장문(90byte 초과)으로 전송처리
        $altContent = '대체문자 내용';

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 알림톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'A';

        // 수신자 정보
        $Messages[] = array(
            // 수신번호
            'rcv' => '',
            // 수신자명
            'rcvnm' => '수신자명'
        );

        // 예약전송일시, yyyyMMddHHmmss
        // - 분단위 전송, 미입력 시 즉시 전송
        $ReserveDT = '';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 알림톡 버튼정보를 템플릿 신청시 기재한 버튼정보와 동일하게 전송하는 경우 null 처리.
        $Btns = null;

        // 버튼배열, 버튼링크URL에 #{템플릿변수}를 기재하여 승인받은 경우 URL 수정가능.
        // $Btns[] = array(
        //     // 버튼 표시명
        //     'n' => '템플릿 안내',
        //     // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
        //     't' => 'WL',
        //     // 링크1, [앱링크] iOS, [웹링크] Mobile
        //     'u1' => 'https://www.popbill.com',
        //     // 링크2, [앱링크] Android, [웹링크] PC URL
        //     'u2' => 'http://www.popbill.com',
        //     // 아웃 링크, out - 디바이스 기본 브라우저, (기본값 : 카카오톡 인앱 브라우저)
        //     // 미입력시 기본값 적용
        //     // 친구톡 사용 시 적용되지 않음
        //     'tg' => 'out' 
        // );

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendATS(
                $CorpNum,
                $templateCode,
                $Sender,
                $content,
                $altContent,
                $AltSendType,
                $Messages,
                $ReserveDT,
                $UserID,
                $RequestNum,
                $Btns,
                $AltSubject
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '알림톡 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 승인된 템플릿 내용을 작성하여 다수건의 알림톡 전송을 팝빌에 접수하며, 모든 수신자에게 동일 내용을 전송합니다. (최대 1,000건)
     * - 사전에 승인된 템플릿의 내용과 알림톡 전송내용(content)이 다를 경우 전송실패 처리됩니다.
     * - 전송실패시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendATS
     */
    public function SendATS_same()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 승인된 알림톡 템플릿코드
        // └ 알림톡 템플릿 관리 팝업 URL(GetATSTemplateMgtURL API) 함수, 알림톡 템플릿 목록 확인(ListATStemplate API) 함수를 호출하거나
        //   팝빌사이트에서 승인된 알림톡 템플릿 코드를  확인 가능.
        $templateCode = '019020000163';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

        // 알림톡 내용, 최대 1000자
        $content = '[ 팝빌 ]' . PHP_EOL;
        $content .= '신청하신 #{템플릿코드}에 대한 심사가 완료되어 승인 처리되었습니다.해당 템플릿으로 전송 가능합니다.' . PHP_EOL . PHP_EOL;
        $content .= '문의사항 있으시면 파트너센터로 편하게 연락주시기 바랍니다.' . PHP_EOL . PHP_EOL;
        $content .= '팝빌 파트너센터 : 1600-8536' . PHP_EOL;
        $content .= 'support@linkhub.co.kr' . PHP_EOL;

        // 대체문자 유형(altSendType)이 "A"일 경우, 대체문자로 전송할 내용 (최대 2000byte)
        // └ 팝빌이 메시지 길이에 따라 단문(90byte 이하) 또는 장문(90byte 초과)으로 전송처리
        $altContent = '대체문자 내용';

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 알림톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'A';

        // 수신정보 배열, 최대 1000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                // 수신번호
                'rcv' => '',
                // 수신자명
                'rcvnm' => '수신자명',
                // 파트너 지정키, 대량전송시 수신자 구분용 메모
                'interOPRefKey' => '20220405-' . $i
            );
        }

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 알림톡 버튼정보를 템플릿 신청시 기재한 버튼정보와 동일하게 전송하는 경우 null 처리.
        $buttons = null;

        // 버튼배열, 버튼링크URL에 #{템플릿변수}를 기재하여 승인받은 경우 URL 수정가능.
        // $buttons[] = array(
        //     // 버튼 표시명
        //     'n' => '템플릿 안내',
        //     // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
        //     't' => 'WL',
        //     // 링크1, [앱링크] iOS, [웹링크] Mobile
        //     'u1' => 'https://www.popbill.com',
        //     // 링크2, [앱링크] Android, [웹링크] PC URL
        //     'u2' => 'http://www.popbill.com'
        //     // 아웃 링크, out - 디바이스 기본 브라우저, (기본값 : 카카오톡 인앱 브라우저)
        //     // 미입력시 기본값 적용
        //     //친구톡 사용 시 적용되지 않음
        //     'tg' => 'out' 
        // );

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendATS(
                $CorpNum,
                $templateCode,
                $Sender,
                $content,
                $altContent,
                $AltSendType,
                $Messages,
                $ReserveDT,
                $UserID,
                $RequestNum,
                $buttons,
                $AltSubject
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '알림톡 동보전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 승인된 템플릿의 내용을 작성하여 다수건의 알림톡 전송을 팝빌에 접수하며, 수신자 별로 개별 내용을 전송합니다. (최대 1,000건)
     * - 사전에 승인된 템플릿의 내용과 알림톡 전송내용(content)이 다를 경우 전송실패 처리됩니다.
     * - 전송실패 시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendATS
     */
    public function SendATS_multi()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 승인된 알림톡 템플릿코드
        // └ 알림톡 템플릿 관리 팝업 URL(GetATSTemplateMgtURL API) 함수, 알림톡 템플릿 목록 확인(ListATStemplate API) 함수를 호출하거나
        //   팝빌사이트에서 승인된 알림톡 템플릿 코드를  확인 가능.
        $templateCode = '019020000163';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

        // 대량 전송의 경우 미사용
        $Content = null;

        // 대량 전송의 경우 미사용
        $AltContent = null;

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 알림톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'A';

        // 알림톡 내용, 최대 1000자
        // 사전에 승인받은 템플릿 내용과 다를 경우 전송실패 처리
        $msg = '[ 팝빌 ]' . PHP_EOL;
        $msg .= '신청하신 #{템플릿코드}에 대한 심사가 완료되어 승인 처리되었습니다.해당 템플릿으로 전송 가능합니다.' . PHP_EOL . PHP_EOL;
        $msg .= '문의사항 있으시면 파트너센터로 편하게 연락주시기 바랍니다.' . PHP_EOL . PHP_EOL;
        $msg .= '팝빌 파트너센터 : 1600-8536' . PHP_EOL;
        $msg .= 'support@linkhub.co.kr' . PHP_EOL;

        // 수신정보 배열, 최대 1000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                // 수신번호
                'rcv' => '',
                // 수신자명
                'rcvnm' => '수신자명',
                // 알림톡 내용, 최대 1000자
                'message' => $msg,
                // 대체문자 제목
                // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
                // - 모든 수신자에게 동일한 제목을 보낼 경우 배열의 모든 원소에 동일한 값을 입력하거나
                //   값을 입력하지 않고 441번 라인에 있는 altSubject 를 이용
                'altSubject' => '대체문자 제목' . $i,
                // 대체문자 내용
                'altMessage' => '대체문자 내용' . $i,
                // 파트너 지정키, 대량전송시, 수신자 구별용 메모.
                'interOPRefKey' => '20220405-' . $i
            );

            // 수신자별 개별 버튼내용 전송하는 경우
            // 개별 버튼의 개수는 템플릿 신청 시 승인받은 버튼의 개수와 동일하게 생성, 다를 경우 전송실패 처리
            // 버튼링크URL에 #{템플릿변수}를 기재하여 승인받은 경우 URL 수정가능.
            // 버튼 표시명, 버튼 유형 수정 불가능.

            // // 개별 버튼정보 배열 생성
            // $btns = array();
            //
            // // 수신자별 개별 전송할 버튼 정보
            // // 버튼 생성
            // $btn1 = new KakaoButton;
            // //버튼 표시명
            // $btn1->n = '템플릿 안내';
            // //버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            // $btn1->t = 'WL';
            // //[앱링크] iOS, [웹링크] Mobile
            // $btn1->u1 = 'http://www.popbill.com';
            // //[앱링크] Android, [웹링크] PC URL
            // $btn1->u2 = 'http://www.popbill.com';
            //
            // // 생성한 버튼 개별 버튼정보 배열에 입력
            // $btns[] = $btn1;
            //
            // //버튼 생성
            // $btn2 = new KakaoButton;
            // //버튼 표시명
            // $btn2->n = '템플릿 안내';
            // //버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            // $btn2->t = 'WL';
            // //[앱링크] iOS, [웹링크] Mobile
            // $btn2->u1 = 'http://www.popbill.com';
            // //[앱링크] Android, [웹링크] PC URL
            // $btn2->u2 = 'http://www.popbill.com' . $i;
            //
            // // 생성한 버튼 개별 버튼정보 배열에 입력
            // $btns[] = $btn2;
            //
            // // 개별 버튼정보 배열 수신자정보에 추가
            // $Messages[$i]['btns'] = $btns;
        }

        // 버튼정보를 수정하지 않고 템플릿 신청시 기재한 버튼내용을 전송하는 경우, null처리.
        // 개별 버튼내용 전송하는 경우, null처리.
        // $Btns = null;

        // 동일 버튼정보 배열, 수신자별 동일 버튼내용 전송하는경우
        // 버튼링크URL에 #{템플릿변수}를 기재하여 승인받은 경우 URL 수정가능.
        // 버튼의 개수는 템플릿 신청 시 승인받은 버튼의 개수와 동일하게 생성, 다를 경우 전송실패 처리
        // 동일 버튼정보 배열 생성
        $Btns[] = array(
            // 버튼 표시명
            'n' => '템플릿 안내',
            // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            't' => 'WL',
            // 링크1, [앱링크] iOS, [웹링크] Mobile
            'u1' => 'https://www.popbill.com',
            // 링크2, [앱링크] Android, [웹링크] PC URL
            'u2' => 'http://www.popbill.com',
            // 아웃 링크, out - 디바이스 기본 브라우저, (기본값 : 카카오톡 인앱 브라우저)
            // 미입력시 기본값 적용
            // 친구톡 사용 시 적용되지 않음
            'tg' => 'out' 
        );

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        // - 수신정보 배열에 대체문자 제목이 입력되지 않은 경우 적용.
        // - 모든 수신자에게 다른 제목을 보낼 경우 464번 라인에 있는 altsjt 를 이용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendATS(
                $CorpNum,
                $templateCode,
                $Sender,
                $Content,
                $AltContent,
                $AltSendType,
                $Messages,
                $ReserveDT,
                $UserID,
                $RequestNum,
                $Btns,
                $AltSubject
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '알림톡 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 텍스트로 구성된 1건의 친구톡 전송을 팝빌에 접수합니다.
     * - 친구톡의 경우 야간 전송은 제한됩니다. (20:00 ~ 익일 08:00)
     * - 전송실패시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendFTS
     */
    public function SendFTS_one()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 등록된 카카오톡 검색용 아이디
        $PlusFriendID = '@팝빌';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

        // 친구톡 내용, 최대 1000자
        $content = '친구톡 내용';

        // 대체문자 유형(altSendType)이 "A"일 경우, 대체문자로 전송할 내용 (최대 2000byte)
        // └ 팝빌이 메시지 길이에 따라 단문(90byte 이하) 또는 장문(90byte 초과)으로 전송처리
        $altContent = '대체문자 내용';

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 알림톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'C';

        // 광고성 메시지 여부 ( true , false 중 택 1)
        // └ true = 광고 , false = 일반
        // - 미입력 시 기본값 false 처리
        $adsYN = False;

        // 수신자 정보
        $Messages[] = array(
            // 수신번호
            'rcv' => '',
            // 수신자명
            'rcvnm' => '수신자명'
        );

        // 버튼배열, 최대 5개
        $Btns[] = array(
            // 버튼 표시명
            'n' => '웹링크',
            // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            't' => 'WL',
            // [앱링크] iOS, [웹링크] Mobile
            'u1' => 'http://www.popbill.com',
            // [앱링크] Android, [웹링크] PC URL
            'u2' => 'http://www.popbill.com'
        );

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendFTS($CorpNum, $PlusFriendID, $Sender, $content, $altContent, $AltSendType, $adsYN, $Messages, $Btns, $ReserveDT, $UserID, $RequestNum, $AltSubject);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '친구톡 단건전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 텍스트로 구성된 다수건의 친구톡 전송을 팝빌에 접수하며, 모든 수신자에게 동일 내용을 전송합니다. (최대 1,000건)
     * - 친구톡의 경우 야간 전송은 제한됩니다. (20:00 ~ 익일 08:00)
     * - 전송실패시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendFTS
     */
    public function SendFTS_same()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 등록된 카카오톡 검색용 아이디
        $PlusFriendID = '@팝빌';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

        // 친구톡 내용, 최대 1000자
        $content = '친구톡 동일내용 대량전송';

        // 대체문자 내용
        $altContent = '대체문자 내용';

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 친구톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'A';

        // 광고성 메시지 여부 ( true , false 중 택 1)
        // └ true = 광고 , false = 일반
        // - 미입력 시 기본값 false 처리
        $adsYN = False;

        // 수신정보 배열, 최대 1000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                // 수신번호
                'rcv' => '',
                // 수신자명
                'rcvnm' => '수신자명'
            );
        }

        // 버튼배열, 최대 5개
        $Btns[] = array(
            // 버튼 표시명
            'n' => '웹링크',
            // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            't' => 'WL',
            // [앱링크] iOS, [웹링크] Mobile
            'u1' => 'http://www.popbill.com',
            // [앱링크] Android, [웹링크] PC URL
            'u2' => 'http://www.popbill.com'
        );

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendFTS($CorpNum, $PlusFriendID, $Sender, $content, $altContent, $AltSendType, $adsYN, $Messages, $Btns, $ReserveDT, $UserID, $RequestNum, $AltSubject);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '친구톡 동보전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 텍스트로 구성된 다수건의 친구톡 전송을 팝빌에 접수하며, 수신자 별로 개별 내용을 전송합니다. (최대 1,000건)
     * - 친구톡의 경우 야간 전송은 제한됩니다. (20:00 ~ 익일 08:00)
     * - 전송실패시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendFTS
     */
    public function SendFTS_multi()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 등록된 카카오톡 검색용 아이디
        $PlusFriendID = '@팝빌';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

         // 대량 전송의 경우 미사용
        $Content = null;

        // 대량 전송의 경우 미사용
        $AltContent = null;

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 친구톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'C';

        // 광고성 메시지 여부 ( true , false 중 택 1)
        // └ true = 광고 , false = 일반
        // - 미입력 시 기본값 false 처리
        $adsYN = false;

        // 수신정보 배열, 최대 1000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                // 수신번호
                'rcv' => '',
                // 수신자명
                'rcvnm' => '수신자명',
                // 친구톡 내용, 최대 1000자
                'msg' => '친구톡 메시지 내용' . $i,
                // 대체문자 제목
                // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
                // - 모든 수신자에게 동일한 제목을 보낼 경우 배열의 모든 원소에 동일한 값을 입력하거나
                //   값을 입력하지 않고 729번 라인에 있는 altSubject 를 이용
                'altsjt' => '대체문자 제목' . $i,
                // 대체문자
                'altmsg' => '대체문자 내용' . $i,
                // 파트너 지정키, 대량전송시, 수신자 구별용 메모.
                'interOPRefKey' => '20220405-' . $i
            );

            // // 수신자별 개별 버튼내용 전송하는 경우
            // // 개별 버튼정보 배열 생성.
            // $btns = array();
            //
            // // 수신자별 개별 전송할 버튼 정보, 생성 가능 개수 최대 5개.
            // // 버튼 생성
            // $btn1 = new KakaoButton;
            // //버튼 표시명
            // $btn1->n = '템플릿 안내';
            // //버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            // $btn1->t = 'WL';
            // //[앱링크] iOS, [웹링크] Mobile
            // $btn1->u1 = 'http://www.popbill.com';
            // //[앱링크] Android, [웹링크] PC URL
            // $btn1->u2 = 'http://www.popbill.com';
            //
            // // 생성한 버튼 개별 버튼정보 배열에 입력
            // $btns[] = $btn1;
            //
            // //버튼 생성
            // $btn2 = new KakaoButton;
            // //버튼 표시명
            // $btn2->n = '템플릿 안내';
            // //버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            // $btn2->t = 'WL';
            // //[앱링크] iOS, [웹링크] Mobile
            // $btn2->u1 = 'http://www.popbill.com';
            // //[앱링크] Android, [웹링크] PC URL
            // $btn2->u2 = 'http://www.popbill.com' . $i;
            //
            // // 생성한 버튼 개별 버튼정보 배열에 입력
            // $btns[] = $btn2;
            //
            // // 개별 버튼정보 배열 수신자정보에 추가
            // $Messages[$i]['btns'] = $btns;
        }

        // 버튼내용을 전송하지 않는 경우, null처리.
        // 개별 버튼내용 전송하는 경우, null처리.
        // $Btns = null;

        // 동일 버튼정보 배열, 수신자별 동일 버튼내용 전송하는경우
        // 동일 버튼정보 배열 생성, 최대 5개
        $Btns[] = array(
            // 버튼 표시명
            'n' => '웹링크',
            // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            't' => 'WL',
            // [앱링크] iOS, [웹링크] Mobile
            'u1' => 'http://www.popbill.com',
            // [앱링크] Android, [웹링크] PC URL
            'u2' => 'http://www.popbill.com'
        );

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        // - 수신정보 배열에 대체문자 제목이 입력되지 않은 경우 적용.
        // - 모든 수신자에게 다른 제목을 보낼 경우 754번 라인에 있는 altsjt 를 이용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendFTS($CorpNum, $PlusFriendID, $Sender, $Content, $AltContent, $AltSendType, $adsYN, $Messages, $Btns, $ReserveDT, $UserID, $RequestNum, $AltSubject);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '친구톡 대량전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 이미지가 첨부된 1건의 친구톡 전송을 팝빌에 접수합니다.
     * - 친구톡의 경우 야간 전송은 제한됩니다. (20:00 ~ 익일 08:00)
     * - 전송실패시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - 대체문자의 경우, 포토문자(MMS) 형식은 지원하고 있지 않습니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendFMS
     */
    public function SendFMS_one()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 등록된 카카오톡 검색용 아이디
        $PlusFriendID = '@팝빌';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

        // 친구톡 내용, 최대 400자
        $content = '친구톡 내용';

        // 대체문자 유형(altSendType)이 "A"일 경우, 대체문자로 전송할 내용 (최대 2000byte)
        // └ 팝빌이 메시지 길이에 따라 단문(90byte 이하) 또는 장문(90byte 초과)으로 전송처리
        $altContent = '대체문자 내용';

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 친구톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'A';

        // 광고성 메시지 여부 ( true , false 중 택 1)
        // └ true = 광고 , false = 일반
        // - 미입력 시 기본값 false 처리
        $adsYN = True;

        // 수신자 정보
        $Messages[] = array(
            // 수신번호
            'rcv' => '',
            // 수신자명
            'rcvnm' => '수신자명'
        );

        // 버튼배열, 최대 5개
        $Btns[] = array(
            // 버튼 표시명
            'n' => '웹링크',
            // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            't' => 'WL',
            // [앱링크] iOS, [웹링크] Mobile
            'u1' => 'http://www.popbill.com',
            // [앱링크] Android, [웹링크] PC URL
            'u2' => 'http://www.popbill.com'
        );

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 첨부이미지 파일 경로
        // - 이미지 파일 규격: 전송 포맷 – JPG 파일 (.jpg, .jpeg), 용량 – 최대 500 Kbyte, 크기 – 가로 500px 이상, 가로 기준으로 세로 0.5~1.3배 비율 가능
        $filePaths = array('/image.jpg');

        // 이미지 링크 URL
        // └ 수신자가 친구톡 상단 이미지 클릭시 호출되는 URL
        // - 미입력시 첨부된 이미지를 링크 기능 없이 표시
        $ImageURL = 'http://popbill.com';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = null;

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendFMS(
                $CorpNum,
                $PlusFriendID,
                $Sender,
                $content,
                $altContent,
                $AltSendType,
                $adsYN,
                $Messages,
                $Btns,
                $ReserveDT,
                $filePaths,
                $ImageURL,
                $UserID,
                $RequestNum,
                $AltSubject
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '친구톡 이미지 전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 이미지가 첨부된 다수건의 친구톡 전송을 팝빌에 접수하며, 모든 수신자에게 동일 내용을 전송합니다. (최대 1,000건)
     * - 친구톡의 경우 야간 전송은 제한됩니다. (20:00 ~ 익일 08:00)
     * - 전송실패시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - 대체문자의 경우, 포토문자(MMS) 형식은 지원하고 있지 않습니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendFMS
     */
    public function SendFMS_same()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 등록된 카카오톡 검색용 아이디, ListPlusFriend API - plusFriendID 확인
        $PlusFriendID = '@팝빌';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

        // 친구톡 내용, 최대 400자
        $content = '친구톡 내용';

        // 대체문자 유형(altSendType)이 "A"일 경우, 대체문자로 전송할 내용 (최대 2000byte)
        // └ 팝빌이 메시지 길이에 따라 단문(90byte 이하) 또는 장문(90byte 초과)으로 전송처리
        $altContent = '대체문자 내용';

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 친구톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'A';

        // 광고성 메시지 여부 ( true , false 중 택 1)
        // └ true = 광고 , false = 일반
        // - 미입력 시 기본값 false 처리
        $adsYN = True;

        // 수신정보 배열, 최대 1000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                // 수신번호
                'rcv' => '',
                // 수신자명
                'rcvnm' => '수신자명'
            );
        }

        // 버튼배열, 최대 5개
        $Btns[] = array(
            // 버튼 표시명
            'n' => '웹링크',
            // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            't' => 'WL',
            // [앱링크] iOS, [웹링크] Mobile
            'u1' => 'http://www.popbill.com',
            // [앱링크] Android, [웹링크] PC URL
            'u2' => 'http://www.popbill.com'
        );

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 첨부이미지 파일 경로
        // - 이미지 파일 규격: 전송 포맷 – JPG 파일 (.jpg, .jpeg), 용량 – 최대 500 Kbyte, 크기 – 가로 500px 이상, 가로 기준으로 세로 0.5~1.3배 비율 가능
        $filePaths = array('/image.jpg');

        // 이미지 링크 URL
        // └ 수신자가 친구톡 상단 이미지 클릭시 호출되는 URL
        // - 미입력시 첨부된 이미지를 링크 기능 없이 표시
        $ImageURL = 'http://popbill.com';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 대체문자 제목
        // 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendFMS(
                $CorpNum,
                $PlusFriendID,
                $Sender,
                $content,
                $altContent,
                $AltSendType,
                $adsYN,
                $Messages,
                $Btns,
                $ReserveDT,
                $filePaths,
                $ImageURL,
                $UserID,
                $RequestNum,
                $AltSubject
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => '친구톡 이미지 전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 이미지가 첨부된 다수건의 친구톡 전송을 팝빌에 접수하며, 수신자 별로 개별 내용을 전송합니다. (최대 1,000건)
     * - 친구톡의 경우 야간 전송은 제한됩니다. (20:00 ~ 익일 08:00)
     * - 전송실패시 사전에 지정한 변수 'altSendType' 값으로 대체문자를 전송할 수 있고, 이 경우 문자(SMS/LMS) 요금이 과금됩니다.
     * - 대체문자의 경우, 포토문자(MMS) 형식은 지원하고 있지 않습니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#SendFMS
     */
    public function SendFMS_multi()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌에 등록된 카카오톡 검색용 아이디
        $PlusFriendID = '@팝빌';

        // 팝빌에 사전 등록된 발신번호
        // altSendType = 'C' / 'A' 일 경우, 대체문자를 전송할 발신번호
        // altSendType = '' 일 경우, null 또는 공백 처리
        // ※ 대체문자를 전송하는 경우에는 사전에 등록된 발신번호 입력 필수
        $Sender = null;

         // 대량 전송의 경우 미사용
        $Content = null;

        // 대량 전송의 경우 미사용
        $AltContent = null;

        // 대체문자 유형 (null , "C" , "A" 중 택 1)
        // null = 미전송, C = 친구톡과 동일 내용 전송 , A = 대체문자 내용(altContent)에 입력한 내용 전송
        $AltSendType = 'A';

        // 광고성 메시지 여부 ( true , false 중 택 1)
        // └ true = 광고 , false = 일반
        // - 미입력 시 기본값 false 처리
        $adsYN = false;

        // 수신정보 배열, 최대 1000건
        for ($i = 0; $i < 10; $i++) {
            $Messages[] = array(
                // 수신번호
                'rcv' => '',
                // 수신자명
                'rcvnm' => '수신자명',
                // 친구톡 내용, 최대 1000자
                'msg' => '친구톡 메시지 내용' . $i,
                // 대체문자 제목
                // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
                // - 모든 수신자에게 동일한 제목을 보낼 경우 배열의 모든 원소에 동일한 값을 입력하거나
                //   값을 입력하지 않고 1040번 라인에 있는 altSubject 를 이용
                'altsjt' => '대체문자 제목' . $i,
                // 대체문자
                'altmsg' => '대체문자 내용' . $i,
                // 파트너 지정키, 대량전송시, 수신자 구별용 메모.
                'interOPRefKey' => '20220405-' . $i
            );

            // // 수신자별 개별 버튼내용 전송하는 경우
            // // 개별 버튼정보 배열 생성.
            // $btns = array();
            //
            // // 수신자별 개별 전송할 버튼 정보, 생성 가능 개수 최대 5개.
            // // 버튼 생성
            // $btn1 = new KakaoButton;
            // //버튼 표시명
            // $btn1->n = '템플릿 안내';
            // //버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            // $btn1->t = 'WL';
            // //[앱링크] iOS, [웹링크] Mobile
            // $btn1->u1 = 'http://www.popbill.com';
            // //[앱링크] Android, [웹링크] PC URL
            // $btn1->u2 = 'http://www.popbill.com';
            //
            // // 생성한 버튼 개별 버튼정보 배열에 입력
            // $btns[] = $btn1;
            //
            // //버튼 생성
            // $btn2 = new KakaoButton;
            // //버튼 표시명
            // $btn2->n = '템플릿 안내';
            // //버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            // $btn2->t = 'WL';
            // //[앱링크] iOS, [웹링크] Mobile
            // $btn2->u1 = 'http://www.popbill.com';
            // //[앱링크] Android, [웹링크] PC URL
            // $btn2->u2 = 'http://www.popbill.com' . $i;
            //
            // // 생성한 버튼 개별 버튼정보 배열에 입력
            // $btns[] = $btn2;
            //
            // // 개별 버튼정보 배열 수신자정보에 추가
            // $Messages[$i]['btns'] = $btns;
        }

        // 버튼내용을 전송하지 않는 경우, null처리.
        // 개별 버튼내용 전송하는 경우, null처리.
        // $Btns = null;

        // 동일 버튼정보 배열, 수신자별 동일 버튼내용 전송하는경우
        // 동일 버튼정보 배열 생성, 최대 5개
        $Btns[] = array(
            // 버튼 표시명
            'n' => '웹링크',
            // 버튼 유형, WL-웹링크, AL-앱링크, MD-메시지 전달, BK-봇키워드
            't' => 'WL',
            // [앱링크] iOS, [웹링크] Mobile
            'u1' => 'http://www.popbill.com',
            // [앱링크] Android, [웹링크] PC URL
            'u2' => 'http://www.popbill.com'
        );

        // 예약전송일시, yyyyMMddHHmmss
        $ReserveDT = null;

        // 첨부이미지 파일 경로
        // - 이미지 파일 규격: 전송 포맷 – JPG 파일 (.jpg, .jpeg), 용량 – 최대 500 Kbyte, 크기 – 가로 500px 이상, 가로 기준으로 세로 0.5~1.3배 비율 가능
        $FilePaths = array('/image.jpg');

        // 이미지 링크 URL
        // └ 수신자가 친구톡 상단 이미지 클릭시 호출되는 URL
        // - 미입력시 첨부된 이미지를 링크 기능 없이 표시
        $ImageURL = 'http://popbill.com';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 전송요청번호
        // 팝빌이 접수 단위를 식별할 수 있도록 파트너가 할당하는 식별번호.
        // 1~36자리로 구성. 영문, 숫자, 하이픈(-), 언더바(_)를 조합하여 팝빌 회원별로 중복되지 않도록 할당.
        $RequestNum = '';

        // 대체문자 제목
        // - 메시지 길이(90byte)에 따라 장문(LMS)인 경우에만 적용.
        // - 수신정보 배열에 대체문자 제목이 입력되지 않은 경우 적용.
        // - 모든 수신자에게 다른 제목을 보낼 경우 1065번 라인에 있는 altsjt 를 이용.
        $AltSubject = '대체문자 제목';

        try {
            $receiptNum = $this->PopbillKakao->SendFMS(
                $CorpNum,
                $PlusFriendID,
                $Sender,
                $Content,
                $AltContent,
                $AltSendType,
                $adsYN,
                $Messages,
                $Btns,
                $ReserveDT,
                $FilePaths,
                $ImageURL,
                $UserID,
                $RequestNum,
                $AltSubject
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => '친구톡 이미지 전송 접수번호(receiptNum)', 'value' => $receiptNum]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 예약접수된 카카오톡을 전송 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#CancelReserve
     */
    public function CancelReserve()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 전송 요청시 발급받은 카카오톡 접수번호
        $ReceiptNum = '022040516101100001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->CancelReserve($CorpNum, $ReceiptNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 예약접수된 카카오톡을 전송 취소합니다. (예약시간 10분 전까지 가능)
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#CancelReserveRN
     */
    public function CancelReserveRN()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약전송 요청시 할당한 전송요청번호
        $RequestNum = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->CancelReserveRN($CorpNum, $RequestNum, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#CancelReservebyRCV
     */
    public function CancelReservebyRCV()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약문자전송 요청시 발급받은 접수번호
        $ReceiptNum = '022102017000000019';

        // 예약문자전송 요청시 입력한 수신번호
        $ReceiveNum = '01011112222';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->CancelReservebyRCV($CorpNum, $ReceiptNum, $ReceiveNum, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/send#CancelReserveRNbyRCV
     */
    public function CancelReserveRNbyRCV()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 예약문자전송 요청시 할당한 전송요청번호
        $RequestNum = '';

        // 예약문자전송 요청시 입력한 수신번호
        $ReceiveNum = '010222333';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->CancelReserveRNbyRCV($CorpNum, $RequestNum, $ReceiveNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에서 반환받은 접수번호를 통해 알림톡/친구톡 전송상태 및 결과를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/info#GetMessages
     */
    public function GetMessages()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 카카오톡 전송 접수 시 팝빌로부터 반환받은 접수번호(receiptNum)
        $ReceiptNum = '022040516101100001';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->GetMessages($CorpNum, $ReceiptNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('KakaoTalk/GetMessages', ['Result' => $result]);
    }

    /**
     * 파트너가 할당한 전송요청 번호를 통해 알림톡/친구톡 전송상태 및 결과를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/info#GetMessagesRN
     */
    public function GetMessagesRN()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 전송 접수 시 파트너가 할당한 전송요청번호
        $RequestNum = '';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->GetMessagesRN($CorpNum, $RequestNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('KakaoTalk/GetMessages', ['Result' => $result]);
    }

    /**
     * 검색조건에 해당하는 카카오톡 전송내역을 조회합니다. (조회기간 단위 : 최대 2개월)
     * - 카카오톡 접수일시로부터 6개월 이내 접수건만 조회할 수 있습니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/info#Search
     */
    public function Search()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 시작일자, 날짜형식(yyyyMMdd)
        $SDate = '20230101';

        // 종료일자, 날짜형식(yyyyMMdd)
        $EDate = '20230131';

        // 전송상태 배열 ("0" , "1" , "2" , "3" , "4" , "5" 중 선택, 다중 선택 가능)
        // └ 0 = 전송대기 , 1 = 전송중 , 2 = 전송성공 , 3 = 대체문자 전송 , 4 = 전송실패 , 5 = 전송취소
        // - 미입력 시 전체조회
        $State = array('0', '1', '2', '3', '4', '5');

        // 검색대상 배열 ("ATS", "FTS", "FMS" 중 선택, 다중 선택 가능)
        // └ ATS = 알림톡 , FTS = 친구톡(텍스트) , FMS = 친구톡(이미지)
        // - 미입력 시 전체조회
        $Item = array('ATS', 'FTS', 'FMS');

        // 전송유형별 조회 (null , "0" , "1" 중 택 1)
        // └ null = 전체 , 0 = 즉시전송건 , 1 = 예약전송건
        // - 미입력 시 전체조회
        $ReserveYN = null;

        // 사용자권한별 조회
        // └ true = 팝빌회원 아이디(UserID)로 전송한 카카오톡만 조회
        // └ false = 전송한 카카오톡 전체 조회 : 기본값
        $SenderYN = false;

        // 페이지번호
        $Page = 1;

        // 페이지 검색개수, 기본값 500, 최대값 1000
        $PerPage = 500;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = 'D';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        // 조회 검색어(수신자명)
        // - 미입력시 전체조회
        $QString = null;

        try {
            $result = $this->PopbillKakao->Search($CorpNum, $SDate, $EDate, $State, $Item, $ReserveYN, $SenderYN, $Page, $PerPage, $Order, $UserID, $QString);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('KakaoTalk/Search', ['Result' => $result]);
    }

    /**
     * 카카오톡 전송내역을 확인하는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/info#GetSentListURL
     */
    public function GetSentListURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetSentListURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "카카오톡 전송내역 팝업 URL", 'value' => $url]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetBalance
     */
    public function GetBalance()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillKakao->GetBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetUseHistory
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
            $result = $this->PopbillKakao->GetUseHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetPaymentHistory
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
            $result = $this->PopbillKakao->GetPaymentHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불 신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetRefundHistory
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
            $result = $this->PopbillKakao->GetRefundHistory($CorpNum, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 환불을 신청합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#Refund
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
            $result = $this->PopbillKakao->Refund($CorpNum, $RefundForm, $UserID);
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
     * 무통장 입금을 신청합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#PaymentRequest
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
            $result = $this->PopbillKakao->PaymentRequest($CorpNum, $PaymentForm, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentResponse', ['Result' => $result]);
    }

    /**
     * 무통장 입금신청한 건의 정보를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetSettleResult
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
            $result = $this->PopbillKakao->GetSettleResult($CorpNum, $SettleCode, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetChargeURL
     */
    public function GetChargeURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetChargeURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetPaymentURL
     */
    public function GetPaymentURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetPaymentURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetUseHistoryURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetPartnerBalance
     */
    public function GetPartnerBalance()
    {

        // 팝빌회원 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillKakao->GetPartnerBalance($CorpNum);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetPartnerURL
     */
    public function GetPartnerURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillKakao->GetPartnerURL($CorpNum, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 카카오톡 전송시 과금되는 포인트 단가를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetUnitCost
     */
    public function GetUnitCost()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 카카오톡 전송유형 ATS-알림톡, FTS-친구톡(텍스트), FMS-친구톡(이미지)
        $MessageType = ENumKakaoType::ATS;

        try {
            $unitCost = $this->PopbillKakao->GetUnitCost($CorpNum, $MessageType);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "카카오톡(" . $MessageType . ") 전송단가 ", 'value' => $unitCost]);
    }

    /**
     * 팝빌 카카오톡 API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetChargeInfo
     */
    public function GetChargeInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 카카오톡 전송유형 ATS-알림톡, FTS-친구톡(텍스트), FMS-친구톡(이미지)
        $MessageType = ENumKakaoType::ATS;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->GetChargeInfo($CorpNum, $MessageType, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#CheckIsMember
     */
    public function CheckIsMember()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // ./config/popbill.php 에 선언된 파트너 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillKakao->CheckIsMember($CorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#CheckID
     */
    public function CheckID()
    {

        // 조회할 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillKakao->CheckID($UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#JoinMember
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
            $result = $this->PopbillKakao->JoinMember($JoinForm);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#GetAccessURL
     */
    public function GetAccessURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillKakao->GetAccessURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL", 'value' => $url]);
    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#GetCorpInfo
     */
    public function GetCorpInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillKakao->GetCorpInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#UpdateCorpInfo
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
            $result =  $this->PopbillKakao->UpdateCorpInfo($CorpNum, $CorpInfo, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#RegistContact
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
            $result = $this->PopbillKakao->RegistContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#GetContactInfo
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
            $ContactInfo = $this->PopbillKakao->GetContactInfo($CorpNum, $ContactID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#ListContact
     */
    public function ListContact()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactList = $this->PopbillKakao->ListContact($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#UpdateContact
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
            $result = $this->PopbillKakao->UpdateContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/kakaotalk/php/api/member#QuitMember
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
            $result = $this->PopbillKakao->QuitMember($CorpNum, $QuitReason, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PResponse', ['code' => $result->code, 'message' => $result->message]);
    }

    /**
     * 환불 가능한 포인트를 확인합니다. (보너스 포인트는 환불가능포인트에서 제외됩니다.)
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetRefundableBalance
     */
    public function GetRefundableBalance()
    {

        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $refundableBalance = $this->PopbillKakao->GetRefundableBalance($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundableBalance', ['refundableBalance' => $refundableBalance]);
    }

    /**
     * 포인트 환불에 대한 상세정보 1건을 확인합니다.
     * - https://developers.popbill.com/reference/kakaotalk/php/api/point#GetRefundInfo
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
            $result = $this->PopbillKakao->GetRefundInfo($CorpNum, $RefundCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundInfo', ['result' => $result]);
    }
}
