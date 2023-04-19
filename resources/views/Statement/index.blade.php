<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>

<body>
    <div id="content">
        <p class="heading1">팝빌 전자명세서 SDK PHP Laravel Example.</p>
        <br />
        <fieldset class="fieldset1">
            <legend>전자명세서 발행</legend>
            <ul>
                <li><a href="Statement/CheckMgtKeyInUse">CheckMgtKeyInUse</a> (문서번호 확인)</li>
                <li><a href="Statement/RegistIssue">RegistIssue</a> (즉시 발행)</li>
                <li><a href="Statement/Register">Register</a> (임시저장)</li>
                <li><a href="Statement/Update">Update</a> (수정)</li>
                <li><a href="Statement/Issue">Issue</a> (발행)</li>
                <li><a href="Statement/CancelIssue">CancelIssue</a> (발행취소)</li>
                <li><a href="Statement/Delete">Delete</a> (삭제)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>전자명세서 정보확인</legend>
            <ul>
                <li><a href="Statement/GetInfo">GetInfo</a> (상태 확인)</li>
                <li><a href="Statement/GetInfos">GetInfos</a> (상태 대량 확인)</li>
                <li><a href="Statement/GetDetailInfo">GetDetailInfo</a> (상세정보 확인)</li>
                <li><a href="Statement/Search">Search</a> (목록 조회)</li>
                <li><a href="Statement/GetLogs">GetLogs</a> (상태 변경이력 확인)</li>
                <li><a href="Statement/GetURL">GetURL</a> (전자명세서 문서함 관련 URL)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>전자명세서 보기/인쇄</legend>
            <ul>
                <li><a href="Statement/GetPopUpURL">GetPopUpURL</a> (전자명세서 보기 URL)</li>
                <li><a href="Statement/GetViewURL">GetViewURL</a> (전자명세서 보기 URL-메뉴,버튼 제외)</li>
                <li><a href="Statement/GetPrintURL">GetPrintURL</a> (전자명세서 인쇄 [공급자] URL)</li>
                <li><a href="Statement/GetEPrintURL">GetEPrintURL</a> (전자명세서 인쇄 [공급받는자용] URL)</li>
                <li><a href="Statement/GetMassPrintURL">GetMassPrintURL</a> (전자명세서 대량 인쇄 URL)</li>
                <li><a href="Statement/GetMailURL">GetMailURL</a> (전자명세서 메일링크 URL)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>부가기능</legend>
            <ul>
                <li><a href="Statement/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL)</li>
                <li><a href="Statement/GetSealURL"> GetSealURL</a> (인감 및 첨부문서 등록 URL)</li>
                <li><a href="Statement/AttachFile">AttachFile</a> (첨부파일 추가)</li>
                <li><a href="Statement/DeleteFile">DeleteFile</a> (첨부파일 삭제)</li>
                <li><a href="Statement/GetFiles">GetFiles</a> (첨부파일 목록 확인)</li>
                <li><a href="Statement/SendEmail">SendEmail</a> (메일 전송)</li>
                <li><a href="Statement/SendSMS">SendSMS</a> (문자 전송)</li>
                <li><a href="Statement/SendFAX">SendFAX</a> (팩스 전송)</li>
                <li><a href="Statement/FAXSend">FAXSend</a> (선팩스 전송)</li>
                <li><a href="Statement/AttachStatement">AttachStatement</a> (전자명세서 첨부)</li>
                <li><a href="Statement/DetachStatement">DetachStatement</a> (전자명세서 첨부해제)</li>
                <li><a href="Statement/ListEmailConfig">ListEmailConfig</a> (전자명세서 알림메일 전송목록 조회)</li>
                <li><a href="Statement/UpdateEmailConfig">UpdateEmailConfig</a> (전자명세서 알림메일 전송설정 수정)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>포인트관리</legend>
            <ul>
                <li><a href="Statement/GetUnitCost">GetUnitCost</a> (발행 단가 확인)</li>
                <li><a href="Statement/GetChargeInfo">GetChargeInfo</a> (과금정보 확인)</li>
                <li><a href="Statement/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인)</li>
                <li><a href="Statement/GetChargeURL">GetChargeURL</a> (연동회원 포인트 충전 팝업 URL)</li>
                <li><a href="Statement/PaymentRequest">PaymentRequest</a> (연동회원 무통장 입금신청)</li>
                <li><a href="Statement/GetSettleResult">GetSettleResult</a> (연동회원 무통장 입금신청 정보확인)</li>
                <li><a href="Statement/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인)</li>
                <li><a href="Statement/GetPaymentURL">GetPaymentURL</a> (연동회원 포인트 결제내역 팝업 URL)</li>
                <li><a href="Statement/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인)</li>
                <li><a href="Statement/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 포인트 사용내역 팝업 URL)</li>
                <li><a href="Statement/Refund">Refund</a> (연동회원 포인트 환불신청)</li>
                <li><a href="Statement/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인)</li>
                <li><a href="Statement/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인)</li>
                <li><a href="Statement/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 URL)</li>
                <li><a href="Statement/GetRefundResult">GetRefundResult</a> (환불 신청 상태 확인)</li>
                <li><a href="Statement/GetRefundablePoint">GetRefundablePoint</a> (환불 가능 포인트 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>회원정보</legend>
            <ul>
                <li><a href="Statement/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인)</li>
                <li><a href="Statement/CheckID">CheckID</a> (아이디 중복 확인)</li>
                <li><a href="Statement/JoinMember">JoinMember</a> (연동회원 신규가입)</li>
                <li><a href="Statement/GetCorpInfo">GetCorpInfo</a> (회사정보 확인)</li>
                <li><a href="Statement/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정)</li>
                <li><a href="Statement/RegistContact">RegistContact</a> (담당자 등록)</li>
                <li><a href="Statement/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
                <li><a href="Statement/ListContact">ListContact</a> (담당자 목록 확인)</li>
                <li><a href="Statement/UpdateContact">UpdateContact</a> (담당자 정보 수정)</li>
                <li><a href="Statement/QuitRequest">QuitRequest</a> (회원 탈퇴)</li>
            </ul>
        </fieldset>
    </div>
</body>

</html>
