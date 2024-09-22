<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>

<body>
    <div id="content">
        <p class="heading1">팝빌 계좌조회 API SDK PHP Laravel Example.</p>
        <br />
        <fieldset class="fieldset1">
            <legend>계좌 관리</legend>
            <ul>
                <li><a href="EasyFinBank/RegistBankAccount">RegistBankAccount</a> (계좌 등록) </li>
                <li><a href="EasyFinBank/UpdateBankAccount">UpdateBankAccount</a> (계좌 수정) </li>
                <li><a href="EasyFinBank/GetBankAccountInfo">GetBankAccountInfo</a> (계좌정보 조회) </li>
                <li><a href="EasyFinBank/ListBankAccount">ListBankAccount</a> (계좌목록 확인) </li>
                <li><a href="EasyFinBank/GetBankAccountMgtURL">GetBankACcountMgtURL</a> (계좌 관리 팝업 URL) </li>
                <li><a href="EasyFinBank/CloseBankAccount">CloseBankAccount</a> (계좌 정액제 해지신청) </li>
                <li><a href="EasyFinBank/RevokeCloseBankAccount">RevokeCloseBankAccount</a> (계좌 정액제 해지신청 취소) </li>
                <li><a href="EasyFinBank/DeleteBankAccount">DeleteBankAccount</a> (종량제 계좌 삭제) </li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>계좌 거래내역 수집</legend>
            <ul>
                <li><a href="EasyFinBank/RequestJob">RequestJob</a> (수집 요청) </li>
                <li><a href="EasyFinBank/GetJobState">GetJobState</a> (수집 상태 확인) </li>
                <li><a href="EasyFinBank/ListActiveJob">ListActiveJob</a> (수집 상태 목록 확인) </li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>계좌 거래내역 수집 결과 조회</legend>
            <ul>
                <li><a href="EasyFinBank/Search">Search</a> (수집 결과 조회) </li>
                <li><a href="EasyFinBank/Summary">Summary</a> (수집 결과 요약정보 조회) </li>
                <li><a href="EasyFinBank/SaveMemo">SaveMemo</a> (거래내역 메모 저장) </li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>포인트 관리 / 정액제 신청</legend>
            <ul>
                <li><a href="EasyFinBank/GetFlatRateState">GetFlatRateState</a> (정액제 서비스 상태 확인) </li>
                <li><a href="EasyFinBank/GetFlatRatePopUpURL">GetFlatRatePopUpURL</a> (정액제 서비스 신청 팝업 URL) </li>
                <li><a href="EasyFinBank/GetChargeInfo">GetChargeInfo</a> (과금정보 확인) </li>
                <li><a href="EasyFinBank/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인) </li>
                <li><a href="EasyFinBank/GetChargeURL">GetChargeURL</a> (연동회원 포인트 충전 팝업 URL) </li>
                <li><a href="EasyFinBank/PaymentRequest">PaymentRequest</a> (연동회원 무통장 입금신청) </li>
                <li><a href="EasyFinBank/GetSettleResult">GetSettleResult</a> (연동회원 무통장 입금신청 정보확인) </li>
                <li><a href="EasyFinBank/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인) </li>
                <li><a href="EasyFinBank/GetPaymentURL">GetPaymentURL</a> (연동회원 포인트 결제내역 팝업 URL)</li>
                <li><a href="EasyFinBank/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인) </li>
                <li><a href="EasyFinBank/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 포인트 사용내역 팝업 URL)</li>
                <li><a href="EasyFinBank/Refund">Refund</a> (연동회원 포인트 환불신청) </li>
                <li><a href="EasyFinBank/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인) </li>
                <li><a href="EasyFinBank/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인) </li>
                <li><a href="EasyFinBank/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 팝업 URL) </li>
                <li><a href="EasyFinBank/GetRefundResult">GetRefundResult</a> (환불 신청 상태 확인)</li>
                <li><a href="EasyFinBank/GetRefundableBalance">GetRefundableBalance</a> (환불 가능 포인트 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>회원정보</legend>
            <ul>
                <li><a href="EasyFinBank/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인) </li>
                <li><a href="EasyFinBank/CheckID">CheckID</a> (아이디 중복 확인) </li>
                <li><a href="EasyFinBank/JoinMember">JoinMember</a> (연동회원 신규가입) </li>
                <li><a href="EasyFinBank/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL) </li>
                <li><a href="EasyFinBank/GetCorpInfo">GetCorpInfo</a> (회사정보 확인) </li>
                <li><a href="EasyFinBank/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정) </li>
                <li><a href="EasyFinBank/RegistContact">RegistContact</a> (담당자 등록) </li>
                <li><a href="EasyFinBank/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
                <li><a href="EasyFinBank/ListContact">ListContact</a> (담당자 목록 확인) </li>
                <li><a href="EasyFinBank/UpdateContact">UpdateContact</a> (담당자 정보 수정) </li>
                <li><a href="EasyFinBank/QuitMember">QuitMember</a> (회원 탈퇴)</li>
            </ul>
        </fieldset>
    </div>
</body>

</html>
