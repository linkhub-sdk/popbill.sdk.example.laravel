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
          @foreach ($TaxinvoiceInfo as $indexKey => $tiInfo)
          <fieldset class="fieldset2">
            <legend>세금계산서 상태 및 요약 정보 확인 [{{ $indexKey+1 }}]</legend>
    				<ul>
                <li>itemKey (팝빌 관리번호) : {{ $tiInfo->itemKey }}</li>
                <li>stateCode (상태코드) : {{ $tiInfo->stateCode }}</li>
                <li>taxType (과세형태) : {{ $tiInfo->taxType }}</li>
                <li>purposeType (영수/청구) : {{ $tiInfo->purposeType }}</li>
                <li>modifyCode (수정 사유코드) : {{ $tiInfo->modifyCode }}</li>
                <li>issueType (발행형태) : {{ $tiInfo->issueType }}</li>
                <li>lateIssueYN (지연발행 여부) : {{ $tiInfo->lateIssueYN ? 'true' : 'false' }}</li>
                <li>interOPYN (연동문서 여부) : {{ $tiInfo->interOPYN ? 'true' : 'false' }}</li>
                <li>writeDate (작성일자) : {{ $tiInfo->writeDate }}</li>
                <li>invoicerCorpName (공급자 상호) : {{ $tiInfo->invoicerCorpName }}</li>
                <li>invoicerCorpNum (공급자 사업자번호) : {{ $tiInfo->invoicerCorpNum }}</li>
                <li>invoicerMgtKey (공급자 문서관리번호) : {{ $tiInfo->invoicerMgtKey }}</li>
                <li>invoicerPrintYN (공급자 인쇄여부) : {{ $tiInfo->invoicerPrintYN ? 'true' : 'false' }}</li>
                <li>invoiceeCorpName (공급받는자 상호) : {{ $tiInfo->invoiceeCorpName }}</li>
                <li>invoiceeCorpNum (공급받는자 사업자번호) : {{ $tiInfo->invoiceeCorpNum }}</li>
                <li>invoiceeMgtKey (공급받는자 관리번호) : {{ $tiInfo->invoiceeMgtKey }}</li>
                <li>invoiceePrintYN (공급받는자 인쇄여부) : {{ $tiInfo->invoiceePrintYN ? 'true' : 'false' }}</li>
                <li>closeDownState (공급받는자 휴폐업상태) : {{ $tiInfo->closeDownState }}</li>
                <li>closeDownStateDate (공급받는자 휴폐업일자) : {{ $tiInfo->closeDownStateDate }}</li>
                <li>supplyCostTotal (공급가액 합계): {{ $tiInfo->supplyCostTotal }}</li>
                <li>taxTotal (세액 합계) : {{ $tiInfo->taxTotal }}</li>
                <li>issueDT (발행일시) : {{ $tiInfo->issueDT }}</li>
                <li>stateDT (상태변경일시) : {{ $tiInfo->stateDT }}</li>
                <li>openYN (개봉 여부) : {{ $tiInfo->openYN ? 'true' : 'false' }}</li>
                <li>openDT (개봉 일시) : {{ $tiInfo->openDT }}</li>
                <li>ntsresult (국세청 전송결과) : {{ $tiInfo->ntsresult }}</li>
                <li>ntsconfirmNum (국세청승인번호) : {{ $tiInfo->ntsconfirmNum }}</li>
                <li>ntssendDT (국세청 전송일시) : {{ $tiInfo->ntssendDT }}</li>
                <li>ntsresultDT (국세청 결과 수신일시) : {{ $tiInfo->ntsresultDT }}</li>
                <li>ntssendErrCode (전송실패 사유코드) : {{ $tiInfo->ntssendErrCode }}</li>
                <li>stateMemo (상태메모) : {{ $tiInfo->stateMemo }}</li>
            </ul>
          </fieldset>
          @endforeach
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
