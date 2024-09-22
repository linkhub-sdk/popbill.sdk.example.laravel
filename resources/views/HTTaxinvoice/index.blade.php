<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />

    <title>팝빌 SDK PHP Laravel Example.</title>
</head>

<body>
    <div id="content">
        <p class="heading1">팝빌 홈택스연동(전자세금계산서) API SDK PHP Laravel Example.</p>
        <br />
        <fieldset class="fieldset1">
            <legend>홈택스 전자세금계산서 매입/매출 내역 수집</legend>
            <ul>
                <li><a href="HTHTTaxinvoice/RequestJob">RequestJob</a> (수집 요청) </li>
                <li><a href="HTHTTaxinvoice/GetJobState">GetJobState</a> (수집 상태 확인) </li>
                <li><a href="HTHTTaxinvoice/ListActiveJob">ListActiveJob</a> (수집 상태 목록 확인) </li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>홈택스 전자세금계산서 매입/매출 내역 수집 결과 조회</legend>
            <ul>
                <li><a href="HTHTTaxinvoice/Search">Search</a> (수집 결과 조회) </li>
                <li><a href="HTHTTaxinvoice/Summary">Summary</a> (수집 결과 요약정보 조회) </li>
                <li><a href="HTHTTaxinvoice/GetHTTaxinvoice">GetHTTaxinvoice</a> (상세정보 확인 (JSON) </li>
                <li><a href="HTHTTaxinvoice/GetXML">GetXML</a> (상세정보 확인 (XML) </li>
                <li><a href="HTHTTaxinvoice/GetPopUpURL">GetPopUpURL</a> (홈택스 전자세금계산서 보기 팝업 URL) </li>
                <li><a href="HTHTTaxinvoice/GetPrintURL">GetPrintURL</a> (홈택스 전자세금계산서 인쇄 팝업 URL) </li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>홈택스연동 인증 관리</legend>
            <ul>
                <li><a href="HTHTTaxinvoice/GetCertificatePopUpURL">GetCertificatePopUpURL</a> (홈택스연동 인증 관리 팝업 URL) </li>
                <li><a href="HTHTTaxinvoice/GetCertificateExpireDate">GetCertificateExpireDate</a> (홈택스연동 공인인증서 만료일자 확인) </li>
                <li><a href="HTHTTaxinvoice/CheckCertValidation">CheckCertValidation</a> (홈택스 공인인증서 로그인 테스트) </li>
                <li><a href="HTHTTaxinvoice/RegistDeptUser">RegistDeptUser</a> (부서사용자 계정등록) </li>
                <li><a href="HTHTTaxinvoice/CheckDeptUser">CheckDeptUser</a> (부서사용자 등록정보 확인) </li>
                <li><a href="HTHTTaxinvoice/CheckLoginDeptUser">CheckLoginDeptUser</a> (부서사용자 로그인 테스트) </li>
                <li><a href="HTHTTaxinvoice/DeleteDeptUser">DeleteDeptUser</a> (부서사용자 등록정보 삭제) </li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>포인트 관리 / 정액제 신청</legend>
            <ul>
                <li><a href="HTHTTaxinvoice/GetFlatRateState">GetFlatRateState</a> (정액제 서비스 상태 확인) </li>
                <li><a href="HTHTTaxinvoice/GetFlatRatePopUpURL">GetFlatRatePopUpURL</a> (정액제 서비스 신청 팝업 URL) </li>
                <li><a href="HTHTTaxinvoice/GetChargeInfo">GetChargeInfo</a> (과금정보 확인) </li>
                <li><a href="HTHTTaxinvoice/GetBalance">GetBalance</a> (연동회원 잔여포인트 확인) </li>
                <li><a href="HTHTTaxinvoice/GetChargeURL">GetChargeURL</a> (연동회원 포인트 충전 팝업 URL) </li>
                <li><a href="HTHTTaxinvoice/PaymentRequest">PaymentRequest</a> (연동회원 무통장 입금신청)</li>
                <li><a href="HTHTTaxinvoice/GetSettleResult">GetSettleResult</a> (연동회원 무통장 입금신청 정보확인)</li>
                <li><a href="HTHTTaxinvoice/GetPaymentHistory">GetPaymentHistory</a> (연동회원 포인트 결제내역 확인)</li>
                <li><a href="HTHTTaxinvoice/GetPaymentURL">GetPaymentURL</a> (연동회원 포인트 결제내역 팝업 URL)</li>
                <li><a href="HTHTTaxinvoice/GetUseHistory">GetUseHistory</a> (연동회원 포인트 사용내역 확인)</li>
                <li><a href="HTHTTaxinvoice/GetUseHistoryURL">GetUseHistoryURL</a> (연동회원 포인트 사용내역 팝업 URL)</li>
                <li><a href="HTHTTaxinvoice/Refund">Refund</a> (연동회원 포인트 환불신청)</li>
                <li><a href="HTHTTaxinvoice/GetRefundHistory">GetRefundHistory</a> (연동회원 포인트 환불내역 확인)</li>
                <li><a href="HTHTTaxinvoice/GetPartnerBalance">GetPartnerBalance</a> (파트너 잔여포인트 확인) </li>
                <li><a href="HTHTTaxinvoice/GetPartnerURL">GetPartnerURL</a> (파트너 포인트충전 팝업 URL) </li>
                <li><a href="HTTaxinvoice/GetRefundResult">GetRefundResult</a> (환불 신청 상태 확인)</li>
                <li><a href="HTTaxinvoice/GetRefundableBalance">GetRefundableBalance</a> (환불 가능 포인트 확인)</li>
            </ul>
        </fieldset>
        <fieldset class="fieldset1">
            <legend>회원정보</legend>
            <ul>
                <li><a href="HTHTTaxinvoice/CheckIsMember">CheckIsMember</a> (연동회원 가입여부 확인) </li>
                <li><a href="HTHTTaxinvoice/CheckID">CheckID</a> (아이디 중복 확인) </li>
                <li><a href="HTHTTaxinvoice/JoinMember">JoinMember</a> (연동회원 신규가입) </li>
                <li><a href="HTHTTaxinvoice/GetAccessURL">GetAccessURL</a> (팝빌 로그인 URL) </li>
                <li><a href="HTHTTaxinvoice/GetCorpInfo">GetCorpInfo</a> (회사정보 확인) </li>
                <li><a href="HTHTTaxinvoice/UpdateCorpInfo">UpdateCorpInfo</a> (회사정보 수정) </li>
                <li><a href="HTHTTaxinvoice/RegistContact">RegistContact</a> (담당자 등록) </li>
                <li><a href="HTHTTaxinvoice/GetContactInfo">GetContactInfo</a> (담당자 정보 확인)</li>
                <li><a href="HTHTTaxinvoice/ListContact">ListContact</a> (담당자 목록 확인) </li>
                <li><a href="HTHTTaxinvoice/UpdateContact">UpdateContact</a> (담당자 정보 수정) </li>
                <li><a href="HTTaxinvoice/QuitMember">QuitMember</a> (회원 탈퇴)</li>
            </ul>
        </fieldset>
    </div>
</body>

</html>
