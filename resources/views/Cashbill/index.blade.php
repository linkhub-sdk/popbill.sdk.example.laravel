<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>

<body>
    <div id="content">
        <p class="heading1">팝빌 현금영수증 SDK PHP Laravel Example.</p>
        <br />
        <fieldset class="fieldset1">
            <legend>현금영수증 발행</legend>
            <ul>
                <li><a href="Cashbill/CheckMgtKeyInUse">CheckMgtKeyInUse</a> (문서번호 확인)</li>
                <li><a href="Cashbill/RegistIssue">RegistIssue</a> (즉시발행)</li>
                <li><a href="Cashbill/BulkSubmit">BulkSubmit</a> (초대량 발행 접수)</li>
                <li><a href="Cashbill/GetBulkResult">GetBulkResult</a> (초대량 접수결과 확인)</li>
                <li><a href="Cashbill/Delete">Delete</a> (삭제)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>취소현금영수증 발행</legend>
            <ul>
                <li><a href="Cashbill/RevokeRegistIssue">RevokeRegistIssue</a> (즉시발행 - 전체금액)</li>
                <li><a href="Cashbill/RevokeRegistIssue_part">RevokeRegistIssue_part</a> (즉시발행 - 부분금액)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>현금영수증 정보확인</legend>
            <ul>
                <li><a href="Cashbill/GetInfo">GetInfo</a> (상태확인)</li>
                <li><a href="Cashbill/GetInfos">GetInfos</a> (상태 대량 확인)</li>
                <li><a href="Cashbill/GetDetailInfo">GetDetailInfo</a> (상세정보 확인)</li>
                <li><a href="Cashbill/Search">Search</a> (목록 조회)</li>
                <li><a href="Cashbill/GetURL">GetURL</a> (현금영수증 문서함 관련 URL)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>현금영수증 보기/인쇄</legend>
            <ul>
                <li><a href="Cashbill/GetPopUpURL">GetPopUpURL</a> (현금영수증 보기 URL)</li>
                <li><a href="Cashbill/GetViewURL">GetViewURL</a> (현금영수증 보기 URL - 메뉴/버튼 제외)</li>
                <li><a href="Cashbill/GetPrintURL">GetPrintURL</a> (현금영수증 인쇄 URL)</li>
                <li><a href="Cashbill/GetMassPrintURL">GetMassPrintURL</a> (현금영수증 대량 인쇄 URL)</li>
                <li><a href="Cashbill/GetMailURL">GetMailURL</a> (현금영수증 메일링크 URL)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>부가기능</legend>
            <ul>
                <li><a href="Cashbill/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL)</li>
                <li><a href="Cashbill/SendEmail">SendEmail</a> (메일 전송)</li>
                <li><a href="Cashbill/SendSMS">SendSMS</a> (문자 전송)</li>
                <li><a href="Cashbill/SendFAX">SendFAX</a> (팩스 전송)</li>
                <li><a href="Cashbill/AssignMgtKey">AssignMgtKey</a> (문서번호 할당)</li>
                <li><a href="Cashbill/ListEmailConfig">ListEmailConfig</a> (현금영수증 알림메일 전송목록 조회)</li>
                <li><a href="Cashbill/UpdateEmailConfig">UpdateEmailConfig</a> (현금영수증 알림메일 전송설정 수정)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>포인트관리</legend>
            <ul>
                <li><a href="Cashbill/GetUnitCost">GetUnitCost</a> (발행 단가 확인)</li>
                <li><a href="Cashbill/GetChargeInfo">GetChargeInfo</a> (과금정보 확인)</li>
                <li><a href="Cashbill/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인)</li>
                <li><a href="Cashbill/GetChargeURL">GetChargeURL</a> (연동회원 포인트 충전 팝업 URL)</li>
                <li><a href="Cashbill/PaymentRequest">PaymentRequest</a> (연동회원 무통장 입금신청)</li>
                <li><a href="Cashbill/GetSettleResult">GetSettleResult</a> (연동회원 무통장 입금신청 정보확인)</li>
                <li><a href="Cashbill/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인)</li>
                <li><a href="Cashbill/GetPaymentURL">GetPaymentURL</a> (연동회원 포인트 결제내역 팝업 URL)</li>
                <li><a href="Cashbill/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인)</li>
                <li><a href="Cashbill/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 포인트 사용내역 팝업 URL)</li>
                <li><a href="Cashbill/Refund">Refund</a> (연동회원 포인트 환불신청)</li>
                <li><a href="Cashbill/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인)</li>
                <li><a href="Cashbill/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인)</li>
                <li><a href="Cashbill/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 팝업 URL)</li>
                <li><a href="Cashbill/GetRefundResult">GetRefundResult</a> (환불 신청 상태 확인)</li>
                <li><a href="Cashbill/GetRefundableBalance">GetRefundableBalance</a> (환불 가능 포인트 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>회원정보</legend>
            <ul>
                <li><a href="Cashbill/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인)</li>
                <li><a href="Cashbill/CheckID">CheckID</a> (아이디 중복 확인)</li>
                <li><a href="Cashbill/JoinMember">JoinMember</a> (연동회원 신규가입)</li>
                <li><a href="Cashbill/GetCorpInfo">GetCorpInfo</a> (회사정보 확인)</li>
                <li><a href="Cashbill/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정)</li>
                <li><a href="Cashbill/RegistContact">RegistContact</a> (담당자 등록)</li>
                <li><a href="Cashbill/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
                <li><a href="Cashbill/ListContact">ListContact</a> (담당자 목록 확인)</li>
                <li><a href="Cashbill/UpdateContact">UpdateContact</a> (담당자 정보 수정)</li>
                <li><a href="Cashbill/QuitMember">QuitMember</a> (회원 탈퇴)</li>
            </ul>
        </fieldset>
    </div>
</body>

</html>
