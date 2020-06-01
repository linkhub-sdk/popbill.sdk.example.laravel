<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />
		<title>팝빌 SDK PHP Laravel Example.</title>
	</head>
	<body>
		<div id="content">
			<p class="heading1">Response</p>
			<br/>
      <fieldset class="fieldset1">
				<legend>{{\Request::fullUrl()}}</legend>
          @foreach ($Result as $indexKey => $bankAccountInfo)
          <fieldset class="fieldset2">
            <legend>계좌정보 [{{ $indexKey+1 }}]</legend>
    				<ul>
              <li>bankCode (은행코드) : {{ $bankAccountInfo->bankCode }}</li>
              <li>accountNumber (계좌번호) : {{ $bankAccountInfo->accountNumber }}</li>
              <li>accountName (계좌별칭) : {{ $bankAccountInfo->accountName }}</li>
              <li>accountType (계좌유형) : {{ $bankAccountInfo->accountType }}</li>
              <li>state (정액제 상태) : {{ $bankAccountInfo->state }}</li>
              <li>regDT (등록일시) : {{ $bankAccountInfo->regDT }}</li>
              <li>memo (메모) : {{ $bankAccountInfo->memo }}</li>

              <li>contractDT (정액제 서비스 시작일시) : {{ $bankAccountInfo->contractDT }}</li>
              <li>useEndDate (정액제 서비스 종료일) : {{ $bankAccountInfo->useEndDate }}</li>
              <li>baseDate (자동연장 결제일) : {{ $bankAccountInfo->baseDate }}</li>
              <li>contractState (정액제 서비스 상태) : {{ $bankAccountInfo->contractState }}</li>
              <li>closeRequestYN (정액제 서비스 해지신청 여부) : {{ $bankAccountInfo->closeRequestYN }}</li>
              <li>useRestrictYN (정액제 서비스 사용제한 여부) : {{ $bankAccountInfo->useRestrictYN }}</li>
              <li>closeOnExpired (정액제 서비스 만료 시 해지 여부) : {{ $bankAccountInfo->closeOnExpired }}</li>
              <li>unPaidYN (미수금 보유 여부) : {{ $bankAccountInfo->unPaidYN }}</li>

            </ul>
          </fieldset>
          @endforeach
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
