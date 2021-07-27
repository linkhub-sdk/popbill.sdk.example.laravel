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
				<ul>
            <li> writeDate (작성일자) : {{ $Taxinvoice->writeDate }} </li>
            <li> chargeDirection (과금방향) : {{ $Taxinvoice->chargeDirection }} </li>
            <li> issueType (발행형태) : {{ $Taxinvoice->issueType }} </li>
            <li> taxType (과세형태) : {{ $Taxinvoice->taxType }} </li>
            <li> supplyCostTotal (공급가액 합계) : {{ $Taxinvoice->supplyCostTotal }} </li>
            <li> taxTotal (세액 합계) : {{ $Taxinvoice->taxTotal}}  </li>
            <li> totalAmount (합계금액) : {{ $Taxinvoice->totalAmount }} </li>
            <li> ntsconfirmNum (국세청승인번호) : {{ $Taxinvoice->ntsconfirmNum }} </li>

            <li> invoicerCorpNum (공급자 사업자번호) : {{ $Taxinvoice->invoicerCorpNum}} </li>
            <li> invoicerMgtKey (공급자 문서번호) : {{ $Taxinvoice->invoicerMgtKey }} </li>
            <li> invoicerCorpName (공급자 상호) : {{ $Taxinvoice->invoicerCorpName }} </li>
            <li> invoicerCEOName (공급자 대표자명) : {{ $Taxinvoice->invoicerCEOName }} </li>
            <li> invoicerAddr (공급자 주소) : {{ $Taxinvoice->invoicerAddr }} </li>
            <li> invoicerContactName (공급자 담당자명) : {{ $Taxinvoice->invoicerContactName }} </li>
            <li> invoicerTEL (공급자 담당자 연락처) : {{ $Taxinvoice->invoicerTEL }} </li>
            <li> invoicerHP (공급자 담당자 휴대폰) : {{ $Taxinvoice->invoicerHP }} </li>
            <li> invoicerEmail (공급자 담당자 메일) : {{ $Taxinvoice->invoicerEmail }} </li>
            <li> invoicerSMSSendYN (발행안내문자 전송여부) : {{ $Taxinvoice->invoicerSMSSendYN ? 'true' : 'false' }} </li>

            <li> invoiceeCorpNum (공급받는자 사업자번호) : {{ $Taxinvoice->invoiceeCorpNum }} </li>
            <li> invoiceeType (공급받는자 구분) : {{ $Taxinvoice->invoiceeType}}  </li>
            <li> invoiceeMgtKey (공급받는자 문서번호) : {{ $Taxinvoice->invoiceeMgtKey }} </li>
            <li> invoiceeCorpName (공급받는자 상호) : {{ $Taxinvoice->invoiceeCorpName }} </li>
            <li> invoiceeCEOName (공급받는자 대표자명 : {{ $Taxinvoice->invoiceeCEOName }} </li>
            <li> invoiceeAddr (공급받는자 주소) : {{ $Taxinvoice->invoiceeAddr }} </li>
            <li> invoiceeContactName1 (공급받는자 담당자명) : {{ $Taxinvoice->invoiceeContactName1 }} </li>
            <li> invoiceeTEL1 (공급받는자 담당자 연락처) : {{ $Taxinvoice->invoiceeTEL1 }} </li>
            <li> invoiceeHP1 (공급받는자 담당자 휴대폰) : {{ $Taxinvoice->invoiceeHP1 }} </li>
            <li> invoiceeEmail1 (공급받는자 담당자 메일) : {{ $Taxinvoice->invoiceeEmail1}} </li>
            <li> invoiceeSMSSendYN (역발행안내문자 전송여부) : {{ $Taxinvoice->invoiceeSMSSendYN ? 'true' : 'false' }} </li>
            <li> closeDownState (공급받는자 휴폐업상태) : {{ $Taxinvoice->closeDownState}} </li>
            <li> closeDownStateDate (공급받는자 휴폐업일자) : {{ $Taxinvoice->closeDownStateDate}} </li>

            <li> purposeType (영수/청구) : {{ $Taxinvoice->purposeType }} </li>
            <li> serialNum (일련번호) : {{ $Taxinvoice->serialNum}}  </li>
            <li> remark1 (비고1) : {{ $Taxinvoice->remark1}}  </li>
            <li> remark2 (비고2) : {{ $Taxinvoice->remark2 }} </li>
            <li> remark3 (비고3) : {{ $Taxinvoice->remark3 }} </li>
            <li> kwon (권) : {{ $Taxinvoice->kwon }} </li>
            <li> ho(호)  : {{ $Taxinvoice->ho }} </li>
            <li> businessLicenseYN (사업자등록증 이미지 첨부여부) : {{ $Taxinvoice->businessLicenseYN  ? 'true' : 'false' }} </li>
            <li> bankBookYN (통장사본이미지 첨부여부) : {{ $Taxinvoice->bankBookYN ? 'true' : 'false' }} </li>

            @if (count($Taxinvoice->detailList) > 0)
              @foreach ($Taxinvoice->detailList as $indexKey => $TaxinvoiceDetail)
              <fieldset class="fieldset2">
                <legend>세금계산서 상세항목(품목)정보 [{{ $indexKey+1 }}]</legend>
        				<ul>
                  <li> serialNum (일련번호) : {{ $TaxinvoiceDetail->serialNum }} </li>
                    <li> purchaseDT (거래일자) : {{ $TaxinvoiceDetail->purchaseDT }} </li>
                    <li> itemName (품명) : {{ $TaxinvoiceDetail->itemName }} </li>
                    <li> spec (규격) : {{ $TaxinvoiceDetail->spec }} </li>
                    <li> qty (수량) : {{ $TaxinvoiceDetail->qty }} </li>
                    <li> unitCost (단가) : {{ $TaxinvoiceDetail->unitCost }} </li>
                    <li> supplyCost (공급가액) : {{ $TaxinvoiceDetail->supplyCost }} </li>
                    <li> tax (세액) : {{ $TaxinvoiceDetail->tax }} </li>
                    <li> remark (비고) : {{ $TaxinvoiceDetail->remark }} </li>
                </ul>
              </fieldset>
              @endforeach
            @endif

            @if (count($Taxinvoice->addContactList) > 0)
              @foreach ($Taxinvoice->addContactList as $indexKey => $TaxinvoiceContact)
              <fieldset class="fieldset2">
                <legend>추가담당자 정보 [{{ $indexKey+1 }}]</legend>
        				<ul>
                  <li> serialNum (일련번호) : {{ $TaxinvoiceContact->serialNum }} </li>
									<li> email (담당자 이메일) : {{ $TaxinvoiceContact->email }} </li>
									<li> contactName (담당자 성명) : {{ $TaxinvoiceContact->contactName }} </li>
                </ul>
              </fieldset>
              @endforeach
            @endif
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
