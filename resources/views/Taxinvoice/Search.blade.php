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
            <li>code : {{ $Result->code }} </li>
            <li>message : {{ $Result->message }} </li>
            <li>total : {{ $Result->total }} </li>
            <li>pageNum : {{ $Result->pageNum }} </li>
            <li>perPage : {{ $Result->perPage }} </li>
            <li>pageCount : {{ $Result->pageCount }} </li>
          </ul>
          @foreach ($Result->list as $indexKey => $tiInfo)
          <fieldset class="fieldset2">
            <legend>Taxinvoice Info [{{ $indexKey+1 }}]</legend>
    				<ul>
                <li>itemKey : {{ $tiInfo->itemKey }}</li>
                <li>stateCode : {{ $tiInfo->stateCode }}</li>
                <li>taxType : {{ $tiInfo->taxType }}</li>
                <li>purposeType : {{ $tiInfo->purposeType }}</li>
                <li>modifyCode : {{ $tiInfo->modifyCode }}</li>
                <li>issueType : {{ $tiInfo->issueType }}</li>
                <li>lateIssueYN : {{ $tiInfo->lateIssueYN ? 'true' : 'false' }}</li>
                <li>interOPYN : {{ $tiInfo->interOPYN ? 'true' : 'false' }}</li>
                <li>writeDate : {{ $tiInfo->writeDate }}</li>
                <li>invoicerCorpName : {{ $tiInfo->invoicerCorpName }}</li>
                <li>invoicerCorpNum : {{ $tiInfo->invoicerCorpNum }}</li>
                <li>invoicerMgtKey : {{ $tiInfo->invoicerMgtKey }}</li>
                <li>invoicerPrintYN : {{ $tiInfo->invoicerPrintYN ? 'true' : 'false' }}</li>
                <li>invoiceeCorpName : {{ $tiInfo->invoiceeCorpName }}</li>
                <li>invoiceeCorpNum : {{ $tiInfo->invoiceeCorpNum }}</li>
                <li>invoiceeMgtKey : {{ $tiInfo->invoiceeMgtKey }}</li>
                <li>invoiceePrintYN : {{ $tiInfo->invoiceePrintYN ? 'true' : 'false' }}</li>
                <li>closeDownState : {{ $tiInfo->closeDownState }}</li>
                <li>closeDownStateDate : {{ $tiInfo->closeDownStateDate }}</li>
                <li>supplyCostTotal : {{ $tiInfo->supplyCostTotal }}</li>
                <li>taxTotal : {{ $tiInfo->taxTotal }}</li>
                <li>issueDT : {{ $tiInfo->issueDT }}</li>
                <li>stateDT : {{ $tiInfo->stateDT }}</li>
                <li>openYN : {{ $tiInfo->openYN ? 'true' : 'false' }}</li>
                <li>openDT : {{ $tiInfo->openDT }}</li>
                <li>ntsresult : {{ $tiInfo->ntsresult }}</li>
                <li>ntsconfirmNum : {{ $tiInfo->ntsconfirmNum }}</li>
                <li>ntssendDT : {{ $tiInfo->ntssendDT }}</li>
                <li>ntsresultDT : {{ $tiInfo->ntsresultDT }}</li>
                <li>ntssendErrCode : {{ $tiInfo->ntssendErrCode }}</li>
                <li>stateMemo : {{ $tiInfo->stateMemo }}</li>
            </ul>
          </fieldset>
          @endforeach
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
