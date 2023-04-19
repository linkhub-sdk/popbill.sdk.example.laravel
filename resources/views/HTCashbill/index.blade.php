<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>

<body>
    <div id="content">
        <p class="heading1">팝빌 홈택스연동(현금영수증) API SDK PHP Laravel Example.</p>
        <br />
        <fieldset class="fieldset1">
            <legend>홈택스 현금영수증 매입/매출 내역 수집</legend>
            <ul>
                <li><a href="HTCashbill/RequestJob">RequestJob</a> (수집 요청)</li>
                <li><a href="HTCashbill/GetJobState">GetJobState</a> (수집 상태 확인)</li>
                <li><a href="HTCashbill/ListActiveJob">ListActiveJob</a> (수집 상태 목록 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>홈택스 현금영수증 매입/매출 내역 수집 결과 조회</legend>
            <ul>
                <li><a href="HTCashbill/Search">Search</a> (수집 결과 조회)</li>
                <li><a href="HTCashbill/Summary">Summary</a> (수집 결과 요약정보 조회)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>홈택스연동 인증 관리</legend>
            <ul>
                <li><a href="HTCashbill/GetCertificatePopUpURL">GetCertificatePopUpURL</a> (홈택스연동 인증 관리 팝업 URL)</li>
                <li><a href="HTCashbill/GetCertificateExpireDate">GetCertificateExpireDate</a> (홈택스연동 공인인증서 만료일자 확인)</li>
                <li><a href="HTCashbill/CheckCertValidation">CheckCertValidation</a> (홈택스 공인인증서 로그인 테스트)</li>
                <li><a href="HTCashbill/RegistDeptUser">RegistDeptUser</a> (부서사용자 계정등록)</li>
                <li><a href="HTCashbill/CheckDeptUser">CheckDeptUser</a> (부서사용자 등록정보 확인)</li>
                <li><a href="HTCashbill/CheckLoginDeptUser">CheckLoginDeptUser</a> (부서사용자 로그인 테스트)</li>
                <li><a href="HTCashbill/DeleteDeptUser">DeleteDeptUser</a> (부서사용자 등록정보 삭제)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>포인트 관리 / 정액제 신청</legend>
            <ul>
                <li><a href="HTCashbill/GetFlatRateState">GetFlatRateState</a> (정액제 서비스 상태 확인)</li>
                <li><a href="HTCashbill/GetFlatRatePopUpURL">GetFlatRatePopUpURL</a> (정액제 서비스 신청 팝업 URL)</li>
                <li><a href="HTCashbill/GetChargeInfo">GetChargeInfo</a> (과금정보 확인)</li>
                <li><a href="HTCashbill/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인)</li>
                <li><a href="HTCashbill/GetChargeURL">GetChargeURL</a> (연동회원 포인트 충전 팝업 URL)</li>
                <li><a href="HTCashbill/PaymentRequest">PaymentRequest</a> (연동회원 무통장 입금신청)</li>
                <li><a href="HTCashbill/GetSettleResult">GetSettleResult</a> (연동회원 무통장 입금신청 정보확인)</li>
                <li><a href="HTCashbill/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인)</li>
                <li><a href="HTCashbill/GetPaymentURL">GetPaymentURL</a> (연동회원 포인트 결제내역 팝업 URL)</li>
                <li><a href="HTCashbill/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인)</li>
                <li><a href="HTCashbill/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 포인트 사용내역 팝업 URL)</li>
                <li><a href="HTCashbill/Refund">Refund</a> (연동회원 포인트 환불신청)</li>
                <li><a href="HTCashbill/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인)</li>
                <li><a href="HTCashbill/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인)</li>
                <li><a href="HTCashbill/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 팝업 URL)</li>
                <li><a href="HTCashbill/GetRefundResult">GetRefundResult</a> (환불 신청 상태 확인)</li>
<li><a href="HTCashbill/GetRefundablePoint">GetRefundablePoint</a> (환불 가능 포인트 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>회원정보</legend>
            <ul>
                <li><a href="HTCashbill/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인)</li>
                <li><a href="HTCashbill/CheckID">CheckID</a> (아이디 중복 확인)</li>
                <li><a href="HTCashbill/JoinMember">JoinMember</a> (연동회원 신규가입)</li>
                <li><a href="HTCashbill/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL)</li>
                <li><a href="HTCashbill/GetCorpInfo">GetCorpInfo</a> (회사정보 확인)</li>
                <li><a href="HTCashbill/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정)</li>
                <li><a href="HTCashbill/RegistContact">RegistContact</a> (담당자 등록)</li>
                <li><a href="HTCashbill/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
                <li><a href="HTCashbill/ListContact">ListContact</a> (담당자 목록 확인)</li>
                <li><a href="HTCashbill/UpdateContact">UpdateContact</a> (담당자 정보 수정)</li>
                <li><a href="HTCashbill/QuitRequest">QuitRequest</a> (회원 탈퇴)</li>
            </ul>
        </fieldset>
</body>

</html>
