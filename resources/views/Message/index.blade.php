<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen"/>

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>
<body>
<div id="content">
    <p class="heading1">팝빌 문자메시지 SDK PHP Laravel Example.</p>
    <br/>
    <fieldset class="fieldset1">
        <legend>발신번호 관리</legend>
        <ul>
            <li><a href="Message/CheckSenderNumber">CheckSenderNumber</a> (발신번호 등록여부 확인)</li>
            <li><a href="Message/GetSenderNumberMgtURL">GetSenderNumberMgtURL</a> (발신번호 관리 팝업 URL)</li>
            <li><a href="Message/GetSenderNumberList">GetSenderNumberList</a> (발신번호 목록 확인)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>문자 전송</legend>
        <fieldset class="fieldset2">
            <legend>단문 문자 전송</legend>
            <ul>
                <li><a href="Message/SendSMS">SendSMS</a> (단문 문자메시지 1건 전송)</li>
                <li><a href="Message/SendSMS_Multi">SendSMS</a> (단문 문자메시지 다량(최대1000건) 전송)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset2">
            <legend>장문 문자 전송</legend>
            <ul>
                <li><a href="Message/SendLMS">SendLMS</a> (장문 문자메시지 1건 전송)</li>
                <li><a href="Message/SendLMS_Multi">SendLMS</a> (장문 문자메시지 다량(최대1000건) 전송)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset2">
            <legend>단/장문 문자 자동인식 전송</legend>
            <ul>
                <li><a href="Message/SendXMS">SendXMS</a> (단/장문 자동인식 문자메시지 1건 전송)</li>
                <li><a href="Message/SendXMS_Multi">SendXMS</a> (단/장문 자동인식 문자메시지 다량(최대1000건) 전송)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset2">
            <legend>포토 문자 전송</legend>
            <ul>
                <li><a href="Message/SendMMS">SendMMS</a> (포토 문자메시지 1건 전송)</li>
                <li><a href="Message/SendMMS_Multi">SendMMS</a> (포토 문자메시지 (최대1000건) 전송)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset2">
            <legend>예약전송 취소</legend>
            <ul>
                <li><a href="Message/CancelReserve">CancelReserve</a> (예약문자 메시지 예약취소)</li>
                <li><a href="Message/CancelReserveRN">CancelReserveRN</a> (예약문자 메시지 예약취소 - 요청번호 할당)</li>
                <li><a href="Message/CancelReservebyRCV">CancelReservebyRCV</a> (예약문자 메시지 예약취소 - 수신번호 추가)</li>
                <li><a href="Message/CancelReserveRNbyRCV">CancelReserveRNbyRCV</a> (예약문자 메시지 예약취소 - 요청번호 할당, 수신번호 추가)</li>
            </ul>
        </fieldset>

    </fieldset>
    <fieldset class="fieldset1">
        <legend>정보확인</legend>
        <ul>
            <li><a href="Message/GetMessages">GetMessages</a> (문자메시지 전송결과 확인)</li>
            <li><a href="Message/GetMessagesRN">GetMessagesRN</a> (문자메시지 전송결과 확인 - 요청번호 할당)</li>
            <li><a href="Message/GetStates">GetStates</a> (문자메시지 전송결과 요약정보 확인)</li>
            <li><a href="Message/Search">Search</a> (문자전송 목록 조회)</li>
            <li><a href="Message/GetSentListURL">GetSentListURL</a> (문자 전송내역 팝업 URL)</li>
            <li><a href="Message/GetAutoDenyList">GetAutoDenyList</a> (080 수신거부 목록 확인)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>포인트 관리</legend>
        <ul>
            <li><a href="Message/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인)</li>
            <li><a href="Message/GetChargeURL">GetChargeURL</a> (연동회원 포인트충전 URL)</li>
            <li><a href="Message/GetPaymentURL">GetPaymentURL</a> (연동회원 결재내역 URL)</li>
            <li><a href="Message/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 사용내역 URL)</li>
            <li><a href="Message/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인)</li>
            <li><a href="Message/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 URL)</li>
            <li><a href="Message/GetUnitCost">GetUnitCost</a> (전송 단가 확인)</li>
            <li><a href="Message/GetChargeInfo">GetChargeInfo</a> (과금정보 확인)</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>회원정보</legend>
        <ul>
            <li><a href="Message/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인)</li>
            <li><a href="Message/CheckID">CheckID</a> (아이디 중복 확인)</li>
            <li><a href="Message/JoinMember">JoinMember</a> (연동회원 신규가입)</li>
            <li><a href="Message/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL)</li>
            <li><a href="Message/RegistContact">RegistContact</a> (담당자 등록)</li>
            <li><a href="Message/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
            <li><a href="Message/ListContact">ListContact</a> (담당자 목록 확인)</li>
            <li><a href="Message/UpdateContact">UpdateContact</a> (담당자 정보 수정)</li>
            <li><a href="Message/GetCorpInfo">GetCorpInfo</a> (회사정보 확인)</li>
            <li><a href="Message/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정)</li>
        </ul>
    </fieldset>
</div>
</body>
</html>
