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
            <li>code (응답코드) : {{ $Result->code }} </li>
            <li>message (응답메시지) : {{ $Result->message }} </li>
            <li>total (총 검색결과 건수) : {{ $Result->total }} </li>
            <li>pageNum (페이지 번호) : {{ $Result->pageNum }} </li>
            <li>perPage (페이지당 목록개수) : {{ $Result->perPage }} </li>
            <li>pageCount (페이지 개수) : {{ $Result->pageCount }} </li>
          </ul>
          @foreach ($Result->list as $indexKey => $stmtInfo)
          <fieldset class="fieldset2">
            <legend>전자명세서 상태/요약정보 [{{ $indexKey+1 }}]</legend>
    				<ul>
              <li> itemKey (아이템키) : {{ $stmtInfo->itemKey }}</li>
              <li> itemCode (문서종류코드) : {{ $stmtInfo->itemCode }}</li>
              <li> stateCode (상태코드) : {{ $stmtInfo->stateCode }}</li>
              <li> taxType (세금형태) : {{ $stmtInfo->taxType }}</li>
              <li> purposeType (영수/청구) : {{ $stmtInfo->purposeType }}</li>
              <li> writeDate (작성일자) : {{ $stmtInfo->writeDate }}</li>
              <li> senderCorpName (발신자 상호) : {{ $stmtInfo->senderCorpName }}</li>
              <li> senderCorpNum (발신자 사업자번호) : {{ $stmtInfo->senderCorpNum }}</li>
              <li> senderPrintYN (발신자 인쇄여부) : {{ $stmtInfo->senderPrintYN ? 'true' : 'false' }}</li>
              <li> receiverCorpName (수신자 상호) : {{ $stmtInfo->receiverCorpName }}</li>
              <li> receiverCorpNum (수신자 사업자번호) : {{ $stmtInfo->receiverCorpNum }}</li>
              <li> receiverPrintYN (수신자 인쇄여부) : {{ $stmtInfo->receiverPrintYN ? 'true' : 'false'}}</li>
              <li> supplyCostTotal (공급가액 합계) : {{ $stmtInfo->supplyCostTotal }}</li>
              <li> taxTotal (세액 합계) : {{ $stmtInfo->taxTotal }}</li>
              <li> issueDT (발행일시) : {{ $stmtInfo->issueDT }}</li>
              <li> stateDT (상태 변경일시) : {{ $stmtInfo->stateDT }}</li>
              <li> openYN (메일 개봉 여부) : {{ $stmtInfo->openYN ? 'true' : 'false' }}</li>
              <li> openDT (개봉 일시) : {{ $stmtInfo->openDT }}</li>
              <li> stateMemo (상태메모) : {{ $stmtInfo->stateMemo }}</li>
              <li> regDT (등록일시) : {{ $stmtInfo->regDT }}</li>
            </ul>
          </fieldset>
          @endforeach
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
