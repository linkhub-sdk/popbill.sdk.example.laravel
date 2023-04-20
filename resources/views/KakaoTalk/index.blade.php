<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>

<body>
    <div id="content">
        <p class="heading1">팝빌 카카오톡 SDK PHP Laravel Example.</p>
        <br />
        <fieldset class="fieldset1">
            <legend>플리스친구 계정관리</legend>
            <ul>
                <li><a href="KakaoTalk/GetPlusFriendMgtURL">GetPlusFriendMgtURL</a> (카카오톡채널 계정관리 팝업 URL)</li>
                <li><a href="KakaoTalk/ListPlusFriendID">ListPlusFriendID</a> (카카오톡채널 목록 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>발신번호 관리</legend>
            <ul>
                <li><a href="KakaoTalk/CheckSenderNumber">CheckSenderNumber</a> (발신번호 등록여부 확인)</li>
                <li><a href="KakaoTalk/GetSenderNumberMgtURL">GetSenderNumberMgtURL</a> (발신번호 관리 팝업 URL)</li>
                <li><a href="KakaoTalk/GetSenderNumberList">GetSenderNumberList</a> (발신번호 목록 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>알림톡 템플릿 관리</legend>
            <ul>
                <li><a href="KakaoTalk/GetATSTemplateMgtURL">GetATSTemplateMgtURL</a> (알림톡 템플릿관리 팝업 URL)</li>
                <li><a href="KakaoTalk/GetATSTemplate">GetATSTemplate</a> (알림톡 템플릿 정보 확인)</li>
                <li><a href="KakaoTalk/ListATSTemplate">ListATSTemplate</a> (알림톡 템플릿 목록 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>알림톡 / 친구톡 전송</legend>
            <fieldset class="fieldset2">
                <legend>알림톡 전송</legend>
                <ul>
                    <li><a href="KakaoTalk/SendATS_one">SendATS</a> (알림톡 단건 전송)</li>
                    <li><a href="KakaoTalk/SendATS_same">SendATS</a> (알림톡 동일내용 대량 전송)</li>
                    <li><a href="KakaoTalk/SendATS_multi">SendATS</a> (알림톡 개별내용 대량 전송)</li>
                </ul>
            </fieldset>
            <fieldset class="fieldset2">
                <legend>친구톡 텍스트 전송</legend>
                <ul>
                    <li><a href="KakaoTalk/SendFTS_one">SendFTS</a> (친구톡 텍스트 단건 전송)</li>
                    <li><a href="KakaoTalk/SendFTS_same">SendFTS</a> (친구톡 텍스트 동일내용 대량전송)</li>
                    <li><a href="KakaoTalk/SendFTS_multi">SendFTS</a> (친구톡 텍스트 개별내용 대량전송)</li>
                </ul>
            </fieldset>
            <fieldset class="fieldset2">
                <legend>친구톡 이미지 전송</legend>
                <ul>
                    <li><a href="KakaoTalk/SendFMS_one">SendFMS</a> (친구톡 이미지 단건 전송)</li>
                    <li><a href="KakaoTalk/SendFMS_same">SendFMS</a> (친구톡 이미지 동일내용 대량전송)</li>
                    <li><a href="KakaoTalk/SendFMS_multi">SendFMS</a> (친구톡 이미지 개별내용 대량전송)</li>
                </ul>
            </fieldset>
            <fieldset class="fieldset2">
                <legend>예약전송 취소</legend>
                <ul>
                    <li><a href="KakaoTalk/CancelReserve">CancelReserve</a> (예약전송 취소)</li>
                    <li><a href="KakaoTalk/CancelReservebyRCV">CancelReservebyRCV</a> 예약전송 일부 취소 (접수번호)</li>
                    <li><a href="KakaoTalk/CancelReserveRN">CancelReserveRN</a> (예약전송 취소 - 요청번호 할당)</li>
                    <li><a href="KakaoTalk/CancelReserveRNbyRCV">CancelReserveRNbyRCV</a> 예약전송 일부 취소 (전송 요청번호)</li>
                </ul>
            </fieldset>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>정보확인</legend>
            <ul>
                <li><a href="KakaoTalk/GetMessages">GetMessages</a> (알림톡/친구톡 전송내역 확인)</li>
                <li><a href="KakaoTalk/GetMessagesRN">GetMessagesRN</a> (알림톡/친구톡 전송내역 확인 - 요청번호 할당)</li>
                <li><a href="KakaoTalk/Search">Search</a> (전송내역 목록 조회)</li>
                <li><a href="KakaoTalk/GetSentListURL">GetSentListURL</a> (카카오톡 전송내역 팝업 URL)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>포인트관리</legend>
            <ul>
                <li><a href="KakaoTalk/GetUnitCost">GetUnitCost</a> (전송 단가 확인)</li>
                <li><a href="KakaoTalk/GetChargeInfo">GetChargeInfo</a> (과금정보 확인)</li>
                <li><a href="KakaoTalk/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인)</li>
                <li><a href="KakaoTalk/GetChargeURL">GetChargeURL</a> (연동회원 포인트 충전 팝업 URL)</li>
                <li><a href="KakaoTalk/PaymentRequest">PaymentRequest</a> (연동회원 무통장 입금신청)</li>
                <li><a href="KakaoTalk/GetSettleResult">GetSettleResult</a> (연동회원 무통장 입금신청 정보확인)</li>
                <li><a href="KakaoTalk/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인)</li>
                <li><a href="KakaoTalk/GetPaymentURL">GetPaymentURL</a> (연동회원 포인트 결제내역 팝업 URL)</li>
                <li><a href="KakaoTalk/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인)</li>
                <li><a href="KakaoTalk/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 포인트 사용내역 팝업 URL)</li>
                <li><a href="KakaoTalk/Refund">Refund</a> (연동회원 포인트 환불신청)</li>
                <li><a href="KakaoTalk/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인)</li>
                <li><a href="KakaoTalk/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인)</li>
                <li><a href="KakaoTalk/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 URL)</li>
                <li><a href="KakaoTalk/GetRefundResult">GetRefundResult</a> (환불 신청 상태 확인)</li>
                <li><a href="KakaoTalk/GetRefundableBalance">GetRefundableBalance</a> (환불 가능 포인트 확인)</li>
            </ul>
        </fieldset>

        <fieldset class="fieldset1">
            <legend>회원관리</legend>
            <ul>
                <li><a href="KakaoTalk/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인)</li>
                <li><a href="KakaoTalk/CheckID">CheckID</a> (연동회원 아이디 중복 확인)</li>
                <li><a href="KakaoTalk/JoinMember">JoinMember</a> (연동회원사 신규가입)</li>
                <li><a href="KakaoTalk/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL)</li>
                <li><a href="KakaoTalk/RegistContact">RegistContact</a> (담당자 추가)</li>
                <li><a href="KakaoTalk/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
                <li><a href="KakaoTalk/ListContact">ListContact</a> (담당자 목록 확인)</li>
                <li><a href="KakaoTalk/UpdateContact">UpdateContact</a> (담당자 정보 수정)</li>
                <li><a href="KakaoTalk/GetCorpInfo">GetCorpInfo</a> (회사정보 확인)</li>
                <li><a href="KakaoTalk/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정)</li>
                <li><a href="KakaoTalk/QuitRequest">QuitRequest</a> (회원 탈퇴)</li>
            </ul>
        </fieldset>
    </div>
</body>

</html>
