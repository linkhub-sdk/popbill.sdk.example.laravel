<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen"/>

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>
<body>
<div id="content">
    <p class="heading1">팝빌 팩스 SDK PHP Laravel Example.</p>
    <br/>
    <fieldset class="fieldset1">
        <legend>발신번호 사전등록</legend>
        <ul>
            <li><a href="Fax/CheckSenderNumber">CheckSenderNumber</a> (발신번호 등록여부 확인)</li>
            <li><a href="Fax/GetSenderNumberMgtURL">GetSenderNumberMgtURL</a> (발신번호 관리 팝업 URL)</li>
            <li><a href="Fax/GetSenderNumberList">GetSenderNumberList</a> (발신번호 목록 확인)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>팩스 전송</legend>
        <ul>
            <li><a href="Fax/SendFAX">SendFAX</a> (팩스 전송. 파일(최대 20개) 1건 전송)</li>
            <li><a href="Fax/SendFAX_Multi">SendFAX_Multi</a> (팩스 전송. 파일(최대 20개) 동보 전송(수신번호 최대 1000개))</li>
            <li><a href="Fax/SendFAXBinary">SendFAXBinary</a> (팩스 전송. 바이너리 데이터(최대 20개) 1건 전송)</li>
            <li><a href="Fax/SendFAXBinary_Multi">SendFAXBinary_Multi</a> (팩스 전송. 바이너리 데이터(최대 20개) 동보 전송(수신번호 최대 1000개))</li>
            <li><a href="Fax/ResendFAX">ResendFAX</a> (팩스 재전송)</li>
            <li><a href="Fax/ResendFAXRN">ResendFAXRN</a> (팩스 재전송 - 요청번호할당)</li>
            <li><a href="Fax/ResendFAX_Multi">ResendFAX_Multi</a> (팩스 동보 재전송)</li>
            <li><a href="Fax/ResendFAXRN_Multi">ResendFAXRN_Multi</a> (팩스 동보 재전송 - 요청번호할당)</li>
            <li><a href="Fax/CancelReserve">CancelReserve</a> (예약전송 팩스 취소)</li>
            <li><a href="Fax/CancelReserveRN">CancelReserveRN</a> (예약전송 팩스 취소 - 요청번호할당)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>전송내역조회</legend>
        <ul>
            <li><a href="Fax/GetFaxDetail">GetFaxDetail</a> (팩스전송 전송결과 확인)</li>
            <li><a href="Fax/GetFaxDetailRN">GetFaxDetailRN</a> (팩스전송 전송결과 확인 - 요청번호할당)</li>
            <li><a href="Fax/Search">Search</a> (팩스전송 목록조회)</li>
            <li><a href="Fax/GetSentListURL">GetSentListURL</a> (팩스 전송내역 팝업 URL)</li>
            <li><a href="Fax/GetPreviewURL">GetPreviewURL</a> (팩스 미리보기 팝업 URL)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>포인트 관리</legend>
        <ul>
            <li><a href="Fax/GetUnitCost">GetUnitCost</a> (발행 단가 확인)</li>
            <li><a href="Fax/GetChargeInfo">GetChargeInfo</a> (과금정보 확인)</li>
            <li><a href="Fax/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인)</li>
            <li><a href="Fax/GetChargeURL">GetChargeURL</a> (연동회원 포인트 충전 팝업 URL)</li>
            <li><a href="Fax/PaymentRequest">PaymentRequest</a> (연동회원 무통장 입금신청)</li>
            <li><a href="Fax/GetSettleResult">GetSettleResult</a> (연동회원 무통장 입금신청 정보확인)</li>
            <li><a href="Fax/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인)</li>
            <li><a href="Fax/GetPaymentURL">GetPaymentURL</a> (연동회원 포인트 결제내역 팝업 URL)</li>
            <li><a href="Fax/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인)</li>
            <li><a href="Fax/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 포인트 사용내역 팝업 URL)</li>
            <li><a href="Fax/Refund">Refund</a> (연동회원 포인트 환불신청)</li>
            <li><a href="Fax/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인)</li>
            <li><a href="Fax/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인)</li>
            <li><a href="Fax/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 팝업 URL)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>회원정보</legend>
        <ul>
            <li><a href="Fax/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인)</li>
            <li><a href="Fax/CheckID">CheckID</a> (아이디 중복 확인)</li>
            <li><a href="Fax/JoinMember">JoinMember</a> (연동회원 신규가입)</li>
            <li><a href="Fax/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL)</li>
            <li><a href="Fax/RegistContact">RegistContact</a> (담당자 등록)</li>
            <li><a href="Fax/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
            <li><a href="Fax/ListContact">ListContact</a> (담당자 목록 확인)</li>
            <li><a href="Fax/UpdateContact">UpdateContact</a> (담당자 정보 수정)</li>
            <li><a href="Fax/GetCorpInfo">GetCorpInfo</a> (회사정보 확인)</li>
            <li><a href="Fax/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정)</li>
        </ul>
    </fieldset>
</div>
</body>
</html>
