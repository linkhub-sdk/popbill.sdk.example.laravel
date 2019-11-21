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
            <li> writeDate  : {{ $Taxinvoice->writeDate }} </li>
            <li> chargeDirection : {{ $Taxinvoice->chargeDirection }} </li>
            <li> issueType : {{ $Taxinvoice->issueType }} </li>
            <li> taxType : {{ $Taxinvoice->taxType }} </li>
            <li> supplyCostTotal  : {{ $Taxinvoice->supplyCostTotal }} </li>
            <li> taxTotal : {{ $Taxinvoice->taxTotal}}  </li>
            <li> totalAmount : {{ $Taxinvoice->totalAmount }} </li>
            <li> ntsconfirmNum : {{ $Taxinvoice->ntsconfirmNum }} </li>

            <li> invoicerCorpNum  : {{ $Taxinvoice->invoicerCorpNum}} </li>
            <li> invoicerMgtKey  : {{ $Taxinvoice->invoicerMgtKey }} </li>
            <li> invoicerCorpName  : {{ $Taxinvoice->invoicerCorpName }} </li>
            <li> invoicerCEOName  : {{ $Taxinvoice->invoicerCEOName }} </li>
            <li> invoicerAddr  : {{ $Taxinvoice->invoicerAddr }} </li>
            <li> invoicerContactName  : {{ $Taxinvoice->invoicerContactName }} </li>
            <li> invoicerTEL  : {{ $Taxinvoice->invoicerTEL }} </li>
            <li> invoicerHP  : {{ $Taxinvoice->invoicerHP }} </li>
            <li> invoicerEmail  : {{ $Taxinvoice->invoicerEmail }} </li>
            <li> invoicerSMSSendYN  : {{ $Taxinvoice->invoicerSMSSendYN ? 'true' : 'false' }} </li>

            <li> invoiceeCorpNum  : {{ $Taxinvoice->invoiceeCorpNum }} </li>
            <li> invoiceeType : {{ $Taxinvoice->invoiceeType}}  </li>
            <li> invoiceeMgtKey  : {{ $Taxinvoice->invoiceeMgtKey }} </li>
            <li> invoiceeCorpName  : {{ $Taxinvoice->invoiceeCorpName }} </li>
            <li> invoiceeCEOName  : {{ $Taxinvoice->invoiceeCEOName }} </li>
            <li> invoiceeAddr  : {{ $Taxinvoice->invoiceeAddr }} </li>
            <li> invoiceeContactName1  : {{ $Taxinvoice->invoiceeContactName1 }} </li>
            <li> invoiceeTEL1  : {{ $Taxinvoice->invoiceeTEL1 }} </li>
            <li> invoiceeHP1  : {{ $Taxinvoice->invoiceeHP1 }} </li>
            <li> invoiceeEmail1  : {{ $Taxinvoice->invoiceeEmail1}} </li>
            <li> invoiceeSMSSendYN  : {{ $Taxinvoice->invoiceeSMSSendYN ? 'true' : 'false' }} </li>
            <li> closeDownState : {{ $Taxinvoice->closeDownState}} </li>
            <li> closeDownStateDate : {{ $Taxinvoice->closeDownStateDate}} </li>

            <li> purposeType : {{ $Taxinvoice->purposeType }} </li>
            <li> serialNum : {{ $Taxinvoice->serialNum}}  </li>
            <li> remark1 : {{ $Taxinvoice->remark1}}  </li>
            <li> remark2 : {{ $Taxinvoice->remark2 }} </li>
            <li> remark3 : {{ $Taxinvoice->remark3 }} </li>
            <li> kwon : {{ $Taxinvoice->kwon }} </li>
            <li> ho : {{ $Taxinvoice->ho }} </li>
            <li> businessLicenseYN : {{ $Taxinvoice->businessLicenseYN  ? 'true' : 'false' }} </li>
            <li> bankBookYN : {{ $Taxinvoice->bankBookYN ? 'true' : 'false' }} </li>

            @if (count($Taxinvoice->detailList) > 0)
              @foreach ($Taxinvoice->detailList as $indexKey => $TaxinvoiceDetail)
              <fieldset class="fieldset2">
                <legend>Taxinvoice Detail [{{ $indexKey+1 }}]</legend>
        				<ul>
                  <li> serialNum : {{ $TaxinvoiceDetail->serialNum }} </li>
                    <li> purchaseDT : {{ $TaxinvoiceDetail->purchaseDT }} </li>
                    <li> itemName : {{ $TaxinvoiceDetail->itemName }} </li>
                    <li> spec : {{ $TaxinvoiceDetail->spec }} </li>
                    <li> qty : {{ $TaxinvoiceDetail->qty }} </li>
                    <li> unitCost : {{ $TaxinvoiceDetail->unitCost }} </li>
                    <li> supplyCost : {{ $TaxinvoiceDetail->supplyCost }} </li>
                    <li> tax : {{ $TaxinvoiceDetail->tax }} </li>
                    <li> remark : {{ $TaxinvoiceDetail->remark }} </li>
                </ul>
              </fieldset>
              @endforeach
            @endif

            @if (count($Taxinvoice->addContactList) > 0)
              @foreach ($Taxinvoice->addContactList as $indexKey => $TaxinvoiceContact)
              <fieldset class="fieldset2">
                <legend>AddContactList [{{ $indexKey+1 }}]</legend>
        				<ul>
                  <li> serialNum : {{ $TaxinvoiceContact->serialNum }} </li>
									<li> email : {{ $TaxinvoiceContact->email }} </li>
									<li> contactName : {{ $TaxinvoiceContact->contactName }} </li>
                </ul>
              </fieldset>
              @endforeach
            @endif
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
