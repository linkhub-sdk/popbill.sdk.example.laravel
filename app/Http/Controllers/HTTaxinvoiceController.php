<?php
/**
  * 팝빌 홈택스 전자세금계산서 API PHP SDK Laravel Example
  *
  * Laravel 연동 튜토리얼 안내 : https://developers.popbill.com/guide/httaxinvoice/php/getting-started/tutorial?fwn=laravel
  * 연동 기술지원 연락처 : 1600-9854
  * 연동 기술지원 이메일 : code@linkhubcorp.com
  *
  * <테스트 연동개발 준비사항>
  * 1) 홈택스 로그인 인증정보를 등록합니다. (부서사용자등록 / 공동인증서 등록)
  *    - 팝빌로그인 > [홈택스연동] > [환경설정] > [인증 관리] 메뉴
  *    - 홈택스연동 인증 관리 팝업 URL(GetCertificatePopUpURL API) 반환된 URL을 이용하여
  *      홈택스 인증 처리를 합니다.
  */
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillHTTaxinvoice;
use Linkhub\Popbill\HTTIKeyType;
use Linkhub\Popbill\RefundForm;
use Linkhub\Popbill\PaymentForm;

class HTTaxinvoiceController extends Controller
{
    public function __construct()
    {

        // 통신방식 설정
        define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

        // 홈택스 전자세금계산서 서비스 클래스 초기화
        $this->PopbillHTTaxinvoice = new PopbillHTTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));

        // 연동환경 설정, true-테스트, false-운영(Production), (기본값:false)
        $this->PopbillHTTaxinvoice->IsTest(config('popbill.IsTest'));

        // 인증토큰 IP 검증 설정, true-사용, false-미사용, (기본값:true)
        $this->PopbillHTTaxinvoice->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));

        // 통신 IP 고정, true-사용, false-미사용, (기본값:false)
        $this->PopbillHTTaxinvoice->UseStaticIP(config('popbill.UseStaticIP'));

        // 로컬시스템 시간 사용여부, true-사용, false-미사용, (기본값:true)
        $this->PopbillHTTaxinvoice->UseLocalTimeYN(config('popbill.UseLocalTimeYN'));
    }

    // HTTP Get Request URI -> 함수 라우팅 처리 함수
    public function RouteHandelerFunc(Request $request)
    {
        $APIName = $request->route('APIName');
        return $this->$APIName();
    }

    /**
     * 홈택스에 신고된 전자세금계산서 매입/매출 내역 수집을 팝빌에 요청합니다. (조회기간 단위 : 최대 3개월)
     * - 주기적으로 자체 DB에 세금계산서 정보를 INSERT 하는 경우, 조회할 일자 유형(DType) 값을 "S"로 하는 것을 권장합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/job#RequestJob
     */
    public function RequestJob()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 전자세금계산서 유형, SELL-매출, BUY-매입, TRUSTEE-위수탁
        $TIKeyType = HTTIKeyType::SELL;

        // 수집일자유형, W-작성일자, I-발행일자, S-전송일자
        $DType = 'S';

        // 시작일자, 형식(yyyyMMdd)
        $SDate = '20230101';

        // 종료일자, 형식(yyyyMMdd)
        $EDate = '20230131';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $jobID = $this->PopbillHTTaxinvoice->RequestJob($CorpNum, $TIKeyType, $DType, $SDate, $EDate, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "작업아이디(jobID)", 'value' => $jobID]);
    }

    /**
     * 수집 요청(RequestJob API) 함수를 통해 반환 받은 작업 아이디의 상태를 확인합니다.
     * - 수집 결과 조회(Search API) 함수 또는 수집 결과 요약 정보 조회(Summary API) 함수를 사용하기 전에
     *   수집 작업의 진행 상태, 수집 작업의 성공 여부를 확인해야 합니다.
     * - 작업 상태(jobState) = 3(완료)이고 수집 결과 코드(errorCode) = 1(수집성공)이면
     *   수집 결과 내역 조회(Search) 또는 수집 결과 요약 정보 조회(Summary)를 해야합니다.
     * - 작업 상태(jobState)가 3(완료)이지만 수집 결과 코드(errorCode)가 1(수집성공)이 아닌 경우에는
     *   오류메시지(errorReason)로 수집 실패에 대한 원인을 파악할 수 있습니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/job#GetJobState
     */
    public function GetJobState()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 수집 요청시 반환받은 작업아이디
        $jobID = '';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->GetJobState($CorpNum, $jobID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('JobState', ['Result' => [$result]]);
    }

    /**
     * 전자세금계산서 매입/매출 내역 수집요청에 대한 상태 목록을 확인합니다.
     * - 수집 요청 후 1시간이 경과한 수집 요청건은 상태정보가 반환되지 않습니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/job#ListActiveJob
     */
    public function ListActiveJob()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->ListActiveJob($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('JobState', ['Result' => $result]);
    }

    /**
     * 수집 상태 확인(GetJobState API) 함수를 통해 상태 정보가 확인된 작업아이디를 활용하여 수집된 전자세금계산서 매입/매출 내역을 조회합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/search#Search
     */
    public function Search()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 수집 요청(RequestJob) 호출시 반환받은 작업아이디
        $JobID = '';

        // 문서형태 배열 ("N" 와 "M" 중 선택, 다중 선택 가능)
        // └ N = 일반 , M = 수정
        // - 미입력 시 전체조회
        $Type = array(
            'N',
            'M'
        );

        // 과세형태 배열 ("T" , "N" , "Z" 중 선택, 다중 선택 가능)
        // └ T = 과세, N = 면세, Z = 영세
        // - 미입력 시 전체조회
        $TaxType = array(
            'T',
            'N',
            'Z'
        );

        // 결제대금 수취여부 ("R" , "C", "N" 중 선택, 다중 선택 가능)
        // └ R = 영수, C = 청구, N = 없음
        // - 미입력 시 전체조회
        $PurposeType = array(
            'R',
            'C',
            'N'
        );

        // 종사업장번호 유무 (null , "0" , "1" 중 택 1)
        // - null = 전체조회 , 0 = 없음, 1 = 있음
        $TaxRegIDYN = null;

        // 종사업장번호의 주체 ("S" , "B" , "T" 중 택 1)
        // - S = 공급자 , B = 공급받는자 , T = 수탁자
        $TaxRegIDType = null;

        // 종사업장번호
        // - 다수기재 시 콤마(",")로 구분. ex) "0001,0002"
        // - 미입력 시 전체조회
        $TaxRegID = null;

        // 페이지 번호
        $Page = 1;

        // 페이지당 목록개수
        $PerPage = 10;

        // 정렬방향, D-내림차순, A-오름차순
        $Order = "D";

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 거래처 상호 / 사업자번호 (사업자) / 주민등록번호 (개인) / "9999999999999" (외국인) 중 검색하고자 하는 정보 입력
        // - 사업자번호 / 주민등록번호는 하이픈('-')을 제외한 숫자만 입력
        // - 미입력시 전체조회
        $SearchString = null;

        try {
            $result = $this->PopbillHTTaxinvoice->Search(
                $CorpNum,
                $JobID,
                $Type,
                $TaxType,
                $PurposeType,
                $TaxRegIDYN,
                $TaxRegIDType,
                $TaxRegID,
                $Page,
                $PerPage,
                $Order,
                $UserID,
                $SearchString
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/Search', ['Result' => $result]);
    }

    /**
     * 수집 상태 확인(GetJobState API) 함수를 통해 상태 정보가 확인된 작업아이디를 활용하여 수집된 전자세금계산서 매입/매출 내역의 요약 정보를 조회합니다.
     * - 요약 정보 : 전자세금계산서 수집 건수, 공급가액 합계, 세액 합계, 합계 금액
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/search#Summary
     */
    public function Summary()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 수집 요청(RequestJob) 함수 호출시 반환받은 작업아이디
        $JobID = '';

        // 문서형태 배열 ("N" 와 "M" 중 선택, 다중 선택 가능)
        // └ N = 일반 , M = 수정
        // - 미입력 시 전체조회
        $Type = array(
            'N',
            'M'
        );

        // 과세형태 배열 ("T" , "N" , "Z" 중 선택, 다중 선택 가능)
        // └ T = 과세, N = 면세, Z = 영세
        // - 미입력 시 전체조회
        $TaxType = array(
            'T',
            'N',
            'Z'
        );

        // 결제대금 수취여부 ("R" , "C", "N" 중 선택, 다중 선택 가능)
        // └ R = 영수, C = 청구, N = 없음
        // - 미입력 시 전체조회
        $PurposeType = array(
            'R',
            'C',
            'N'
        );

        // 종사업장번호 유무 (null , "0" , "1" 중 택 1)
        // - null = 전체조회 , 0 = 없음, 1 = 있음
        $TaxRegIDYN = null;

        // 종사업장번호의 주체 ("S" , "B" , "T" 중 택 1)
        // - S = 공급자 , B = 공급받는자 , T = 수탁자
        $TaxRegIDType = null;

        // 종사업장번호
        // - 다수기재 시 콤마(",")로 구분. ex) "0001,0002"
        // - 미입력 시 전체조회
        $TaxRegID = null;

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        // 거래처 상호 / 사업자번호 (사업자) / 주민등록번호 (개인) / "9999999999999" (외국인) 중 검색하고자 하는 정보 입력
        // - 사업자번호 / 주민등록번호는 하이픈('-')을 제외한 숫자만 입력
        // - 미입력시 전체조회
        $SearchString = null;

        try {
            $result = $this->PopbillHTTaxinvoice->Summary(
                $CorpNum,
                $JobID,
                $Type,
                $TaxType,
                $PurposeType,
                $TaxRegIDYN,
                $TaxRegIDType,
                $TaxRegID,
                $UserID,
                $SearchString
            );
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/Summary', ['Result' => $result]);
    }

    /**
     * 국세청 승인번호를 통해 수집한 전자세금계산서 1건의 상세정보를 반환합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/search#GetTaxinvoice
     */
    public function GetTaxinvoice()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        //국세청 승인번호
        $NTSConfirmNum = '';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->GetTaxinvoice($CorpNum, $NTSConfirmNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/GetTaxinvoice', ['Taxinvoice' => $result]);
    }

    /**
     * 국세청 승인번호를 통해 수집한 전자세금계산서 1건의 상세정보를 XML 형태의 문자열로 반환합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/search#GetXML
     */
    public function GetXML()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        //국세청 승인번호
        $NTSConfirmNum = '';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->GetXML($CorpNum, $NTSConfirmNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('HTTaxinvoice/GetXML', ['Result' => $result]);
    }

    /**
     * 수집된 전자세금계산서 1건의 상세내역을 확인하는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/search#GetPopUpURL
     */
    public function GetPopUpURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 국세청 승인번호
        $NTSConfirmNum = "";

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->getPopUpURL($CorpNum, $NTSConfirmNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자세금계산서 보기 URL", 'value' => $url]);
    }

    /**
     * 수집된 전자세금계산서 1건의 상세내역을 인쇄하는 페이지의 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/search#GetPrintURL
     */
    public function GetPrintURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 국세청 승인번호
        $NTSConfirmNum = "";

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->getPrintURL($CorpNum, $NTSConfirmNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "전자세금계산서 인쇄 URL", 'value' => $url]);
    }

    /**
     * 홈택스연동 인증정보를 관리하는 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/cert#GetCertificatePopUpURL
     */
    public function GetCertificatePopUpURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetCertificatePopUpURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "홈택스 인증관리 팝업 URL", 'value' => $url]);
    }

    /**
     * 팝빌에 등록된 인증서 만료일자를 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/cert#GetCertificateExpireDate
     */
    public function GetCertificateExpireDate()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $ExpireDate = $this->PopbillHTTaxinvoice->GetCertificateExpireDate($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "공인인증서 만료일시", 'value' => $ExpireDate]);
    }

    /**
     * 팝빌에 등록된 인증서로 홈택스 로그인 가능 여부를 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/cert#CheckCertValidation
     */
    public function CheckCertValidation()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckCertValidation($CorpNum);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 홈택스연동 인증을 위해 팝빌에 전자세금계산서용 부서사용자 계정을 등록합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/cert#RegistDeptUser
     */
    public function RegistDeptUser()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 홈택스에서 생성한 전자세금계산서 부서사용자 아이디
        $deptUserID = 'userid_test';

        // 홈택스에서 생성한 전자세금계산서 부서사용자 비밀번호
        $deptUserPWD = 'passwd_test';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->RegistDeptUser($CorpNum, $deptUserID, $deptUserPWD, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 홈택스연동 인증을 위해 팝빌에 등록된 전자세금계산서용 부서사용자 계정을 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/cert#CheckDeptUser
     */
    public function CheckDeptUser()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckDeptUser($CorpNum);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에 등록된 전자세금계산서용 부서사용자 계정 정보로 홈택스 로그인 가능 여부를 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/cert#CheckLoginDeptUser
     */
    public function CheckLoginDeptUser()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckLoginDeptUser($CorpNum);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 팝빌에 등록된 홈택스 전자세금계산서용 부서사용자 계정을 삭제합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/cert#DeleteDeptUser
     */
    public function DeleteDeptUser()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->DeleteDeptUser($CorpNum, $UserID);
            $code = $result->code;
            $message = $result->message;
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
        }

        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    /**
     * 홈택스연동 정액제 서비스 신청 페이지의 팝업 URL을 반환합니다.
     * - 반환되는 URL은 보안 정책상 30초 동안 유효하며, 시간을 초과한 후에는 해당 URL을 통한 페이지 접근이 불가합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetFlatRatePopUpURL
     */
    public function GetFlatRatePopUpURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetFlatRatePopUpURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "정액제 서비스 신청 팝업 URL", 'value' => $url]);
    }

    /**
     * 홈택스연동 정액제 서비스 상태를 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetFlatRateState
     */
    public function GetFlatRateState()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->GetFlatRateState($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('FlatRateState', ['Result' => $result]);
    }

    /**
     * 연동회원의 잔여포인트를 확인합니다.
     * - 과금방식이 파트너과금인 경우 파트너 잔여포인트 확인(GetPartnerBalance API) 함수를 통해 확인하시기 바랍니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetBalance
     */
    public function GetBalance()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillHTTaxinvoice->GetBalance($CorpNum);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "연동회원 잔여포인트", 'value' => $remainPoint]);
    }

    /**
     * 연동회원의 포인트 사용내역을 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetUseHistory
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
            $result = $this->PopbillHTTaxinvoice->GetUseHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $Order, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('UseHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 결제내역을 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetPaymentHistory
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
            $result = $this->PopbillHTTaxinvoice->GetPaymentHistory($CorpNum, $SDate, $EDate, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원의 포인트 환불신청내역을 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetRefundHistory
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
            $result = $this->PopbillHTTaxinvoice->GetRefundHistory($CorpNum, $Page, $PerPage, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('RefundHistoryResult', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트를 환불 신청합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#Refund
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
            $result = $this->PopbillHTTaxinvoice->Refund($CorpNum, $RefundForm, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#PaymentRequest
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
            $result = $this->PopbillHTTaxinvoice->PaymentRequest($CorpNum, $PaymentForm, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PaymentResponse', ['Result' => $result]);
    }

    /**
     * 연동회원 포인트 무통장 입금신청내역 1건을 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetSettleResult
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
            $result = $this->PopbillHTTaxinvoice->GetSettleResult($CorpNum, $SettleCode, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetChargeURL
     */
    public function GetChargeURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetChargeURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetPaymentURL
     */
    public function GetPaymentURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetPaymentURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetUseHistoryURL
     */
    public function GetUseHistoryURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetUseHistoryURL($CorpNum, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetPartnerBalance
     */
    public function GetPartnerBalance()
    {

        // 팝빌회원 사업자번호
        $CorpNum = '1234567890';

        try {
            $remainPoint = $this->PopbillHTTaxinvoice->GetPartnerBalance($CorpNum);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetPartnerURL
     */
    public function GetPartnerURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // [CHRG] : 포인트충전 URL
        $TOGO = 'CHRG';

        try {
            $url = $this->PopbillHTTaxinvoice->GetPartnerURL($CorpNum, $TOGO);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL", 'value' => $url]);
    }

    /**
     * 팝빌 홈택스연동(세금) API 서비스 과금정보를 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetChargeInfo
     */
    public function GetChargeInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->GetChargeInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetChargeInfo', ['Result' => $result]);
    }

    /**
     * 사업자번호를 조회하여 연동회원 가입여부를 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#CheckIsMember
     */
    public function CheckIsMember()
    {

        // 사업자번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 연동신청 시 팝빌에서 발급받은 링크아이디
        $LinkID = config('popbill.LinkID');

        try {
            $result = $this->PopbillHTTaxinvoice->CheckIsMember($CorpNum, $LinkID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#CheckID
     */
    public function CheckID()
    {

        // 조회할 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->CheckID($UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#JoinMember
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
            $result = $this->PopbillHTTaxinvoice->JoinMember($JoinForm);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#GetAccessURL
     */
    public function GetAccessURL()
    {

        // 팝빌회원 사업자 번호, "-"제외 10자리
        $CorpNum = '1234567890';

        // 팝빌 회원 아이디
        $UserID = 'testkorea';

        try {
            $url = $this->PopbillHTTaxinvoice->GetAccessURL($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('ReturnValue', ['filedName' => "팝빌 로그인 URL", 'value' => $url]);
    }

    /**
     * 연동회원의 회사정보를 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#GetCorpInfo
     */
    public function GetCorpInfo()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $CorpInfo = $this->PopbillHTTaxinvoice->GetCorpInfo($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
    }

    /**
     * 연동회원의 회사정보를 수정합니다
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#UpdateCorpInfo
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

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result =  $this->PopbillHTTaxinvoice->UpdateCorpInfo($CorpNum, $CorpInfo, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#RegistContact
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

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $result = $this->PopbillHTTaxinvoice->RegistContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#GetContactInfo
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
            $ContactInfo = $this->PopbillHTTaxinvoice->GetContactInfo($CorpNum, $ContactID, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ContactInfo', ['ContactInfo' => $ContactInfo]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 목록을 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#ListContact
     */
    public function ListContact()
    {

        // 팝빌회원 사업자번호, '-'제외 10자리
        $CorpNum = '1234567890';

        // 팝빌회원 아이디
        $UserID = 'testkorea';

        try {
            $ContactList = $this->PopbillHTTaxinvoice->ListContact($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }

        return view('ListContact', ['ContactList' => $ContactList]);
    }

    /**
     * 연동회원 사업자번호에 등록된 담당자(팝빌 로그인 계정) 정보를 수정합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#UpdateContact
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
            $result = $this->PopbillHTTaxinvoice->UpdateContact($CorpNum, $ContactInfo, $UserID);
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
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/member#QuitMember
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
            $result = $this->PopbillHTTaxinvoice->QuitMember($CorpNum, $QuitReason, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('PResponse', ['code' => $result->code, 'message' => $result->message]);
    }

    /**
     * 환불 가능한 포인트를 확인합니다. (보너스 포인트는 환불가능포인트에서 제외됩니다.)
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetRefundableBalance
     */
    public function GetRefundableBalance()
    {

        // 팝빌회원 사업자 번호
        $CorpNum = "1234567890";

        // 팝빌 회원 아이디
        $UserID = "testkorea";

        try {
            $refundableBalance = $this->PopbillHTTaxinvoice->GetRefundableBalance($CorpNum, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundableBalance', ['refundableBalance' => $refundableBalance]);
    }

    /**
     * 포인트 환불에 대한 상세정보 1건을 확인합니다.
     * - https://developers.popbill.com/reference/httaxinvoice/php/api/point#GetRefundInfo
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
            $result = $this->PopbillHTTaxinvoice->GetRefundInfo($CorpNum, $RefundCode, $UserID);
        } catch (PopbillException $pe) {
            $code = $pe->getCode();
            $message = $pe->getMessage();
            return view('PResponse', ['code' => $code, 'message' => $message]);
        }
        return view('GetRefundInfo', ['result' => $result]);
    }
}
