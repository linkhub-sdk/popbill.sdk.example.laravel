
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="css/example.css" media="screen"/>

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>
<body>
<div id="content">
    <p class="heading1">팝빌 전자세금계산서 SDK PHP Laravel Example.</p>
    <br/>
    <fieldset class="fieldset1">
        <legend>정방행/역발행/위수탁발행</legend>
        <ul>
            <li><a href="Taxinvoice/CheckMgtKeyInUse">CheckMgtKeyInUse</a> (문서번호 확인)</li>
            <li><a href="Taxinvoice/RegistIssue">RegistIssue</a> (즉시 발행)</li>
            <li><a href="Taxinvoice/BulkSubmit">BulkSubmit</a> (초대량 발행 접수)</li>
            <li><a href="Taxinvoice/GetBulkResult">GetBulkResult</a> (초대량 접수결과 확인)</li>
            <li><a href="Taxinvoice/Register">Register</a> (임시저장)</li>
            <li><a href="Taxinvoice/Update">Update</a> (수정)</li>
            <li><a href="Taxinvoice/Issue">Issue</a> (발행)</li>
            <li><a href="Taxinvoice/CancelIssue">CancelIssue</a> (발행취소)</li>
            <li><a href="Taxinvoice/Delete">Delete</a> (삭제)</li>
            <li><a href="Taxinvoice/RegistRequest">RegistRequest</a> ([역발행] 즉시 요청)</li>
            <li><a href="Taxinvoice/Request">Request</a> (역발행요청)</li>
            <li><a href="Taxinvoice/CancelRequest">CancelRequest</a> (역발행요청 취소)</li>
            <li><a href="Taxinvoice/Refuse">Refuse</a> (역발행요청 거부)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>국세청 즉시 전송</legend>
        <ul>
            <li><a href="Taxinvoice/SendToNTS">SendToNTS</a> (국세청 즉시전송)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>세금계산서 정보확인</legend>
        <ul>
            <li><a href="Taxinvoice/GetInfo">GetInfo</a> (상태 확인)</li>
            <li><a href="Taxinvoice/GetInfos">GetInfos</a> (상태 대량 확인)</li>
            <li><a href="Taxinvoice/GetDetailInfo">GetDetailInfo</a> (상세정보 확인)</li>
            <li><a href="Taxinvoice/GetXML">GetXML</a> (상세정보 확인(XML))</li>
            <li><a href="Taxinvoice/Search">Search</a> (목록 조회)</li>
            <li><a href="Taxinvoice/GetLogs">GetLogs</a> (상태 변경이력 확인)</li>
            <li><a href="Taxinvoice/GetURL">GetURL</a> (세금계산서 문서함 관련 URL)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>세금계산서 보기/인쇄</legend>
        <ul>
            <li><a href="Taxinvoice/GetPopUpURL">GetPopUpURL</a> (세금계산서 보기 URL)</li>
            <li><a href="Taxinvoice/GetViewURL">GetViewURL</a> (세금계산서 보기 URL - 메뉴/버튼 제외)</li>
            <li><a href="Taxinvoice/GetPrintURL">GetPrintURL</a> (세금계산서 인쇄 [공급자/공급받는자] URL)</li>
            <li><a href="Taxinvoice/GetOldPrintURL">GetOldPrintURL</a> (구)(세금계산서 인쇄 [공급자/공급받는자] URL)</li>
            <li><a href="Taxinvoice/GetEPrintURL">GetEPrintURL</a> (세금계산서 인쇄 [공급받는자용] URL)</li>
            <li><a href="Taxinvoice/GetMassPrintURL">GetMassPrintURL</a> (세금계산서 대량 인쇄 URL)</li>
            <li><a href="Taxinvoice/GetMailURL">GetMailURL</a> (세금계산서 메일링크 URL)</li>
            <li><a href="Taxinvoice/GetPDFURL">GetPDFURL</a> (세금계산서 PDF 다운로드 URL)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>부가기능</legend>
        <ul>
            <li><a href="Taxinvoice/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL)</li>
            <li><a href="Taxinvoice/GetSealURL"> GetSealURL</a> (인감 및 첨부문서 등록 URL)</li>
            <li><a href="Taxinvoice/AttachFile">AttachFile</a> (첨부파일 추가)</li>
            <li><a href="Taxinvoice/DeleteFile">DeleteFile</a> (첨부파일 삭제)</li>
            <li><a href="Taxinvoice/GetFiles">GetFiles</a> (첨부파일 목록 확인)</li>
            <li><a href="Taxinvoice/SendEmail">SendEmail</a> (메일 전송)</li>
            <li><a href="Taxinvoice/SendSMS">SendSMS</a> (문자 전송)</li>
            <li><a href="Taxinvoice/SendFAX">SendFAX</a> (팩스 전송)</li>
            <li><a href="Taxinvoice/AttachStatement">AttachStatement</a> (전자명세서 첨부)</li>
            <li><a href="Taxinvoice/DetachStatement">DetachStatement</a> (전자명세서 첨부해제)</li>
            <li><a href="Taxinvoice/GetEmailPublicKeys">GetEmailPublicKeys</a> (유통사업자 메일 목록 확인)</li>
            <li><a href="Taxinvoice/AssignMgtKey">AssignMgtKey</a> (문서번호 할당)</li>
            <li><a href="Taxinvoice/ListEmailConfig">ListEmailConfig</a> (세금계산서 알림메일 전송목록 조회)</li>
            <li><a href="Taxinvoice/UpdateEmailConfig">UpdateEmailConfig</a> (세금계산서 알림메일 전송설정 수정)</li>
            <li><a href="Taxinvoice/GetSendToNTSConfig">GetSendToNTSConfig</a> (국세청 전송 설정 확인)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>공동인증서 관리</legend>
        <ul>
            <li><a href="Taxinvoice/GetTaxCertURL">GetTaxCertURL</a> (공동인증서 등록 URL)</li>
            <li><a href="Taxinvoice/GetCertificateExpireDate">GetCertificateExpireDate</a> (공동인증서 만료일 확인)</li>
            <li><a href="Taxinvoice/CheckCertValidation">CheckCertValidation</a> (공동인증서 유효성 확인)</li>
            <li><a href="Taxinvoice/GetTaxCertInfo">GetTaxCertInfo</a> (인증서 정보 확인)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>포인트 관리</legend>
        <ul>
            <li><a href="Taxinvoice/GetUnitCost">GetUnitCost</a> (발행 단가 확인)</li>
            <li><a href="Taxinvoice/GetChargeInfo">GetChargeInfo</a> (과금정보 확인)</li>
            <li><a href="Taxinvoice/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인)</li>
            <li><a href="Taxinvoice/GetChargeURL">GetChargeURL</a> (연동회원 포인트충전 URL)</li>
            <li><a href="Taxinvoice/PaymentRequest">PaymentRequest</a> (무통장 입금신청)</li>
            <li><a href="Taxinvoice/GetSettleResult">GetSettleResult</a> (연동회원 결제내역 정보확인)</li>
            <li><a href="Taxinvoice/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인)</li>
            <li><a href="Taxinvoice/GetPaymentURL">GetPaymentURL</a> (연동회원 결재내역 URL)</li>
            <li><a href="Taxinvoice/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인)</li>
            <li><a href="Taxinvoice/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 사용내역 URL)</li>
            <li><a href="Taxinvoice/Refund">Refund</a> (연동회원 포인트 환불신청)</li>
            <li><a href="Taxinvoice/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인)</li>
            <li><a href="Taxinvoice/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인)</li>
            <li><a href="Taxinvoice/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 URL)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>회원정보</legend>
        <ul>
            <li><a href="Taxinvoice/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인)</li>
            <li><a href="Taxinvoice/CheckID">CheckID</a> (아이디 중복 확인)</li>
            <li><a href="Taxinvoice/JoinMember">JoinMember</a> (연동회원 신규가입)</li>
            <li><a href="Taxinvoice/GetCorpInfo">GetCorpInfo</a> (회사정보 확인)</li>
            <li><a href="Taxinvoice/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정)</li>
            <li><a href="Taxinvoice/RegistContact">RegistContact</a> (담당자 등록)</li>
            <li><a href="Taxinvoice/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
            <li><a href="Taxinvoice/ListContact">ListContact</a> (담당자 목록 확인)</li>
            <li><a href="Taxinvoice/UpdateContact">UpdateContact</a> (담당자 정보 수정)</li>
        </ul>
    </fieldset>
</div>
</body>
</html>
