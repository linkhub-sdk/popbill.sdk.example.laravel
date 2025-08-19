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
          @foreach ($StatementInfo as $indexKey => $stmtInfo)
          <fieldset class="fieldset2">
            <legend>전자명세서 상태 및 요약 정보 확인 [{{ $indexKey+1 }}]</legend>
            <ul>
              <li> itemCode (전자명세서 문서 유형) : {{ $stmtInfo->itemCode }}</li>
              <li> itemKey (팝빌번호) : {{ $stmtInfo->itemKey }}</li>
              <li> invoiceNum (팝빌 승인번호) : {{ $stmtInfo->invoiceNum }}</li>
              <li> mgtKey (문서번호) : {{ $stmtInfo->mgtKey }}
              <li> taxType (과세형태) : {{ $stmtInfo->taxType }}</li>
              <li> writeDate (작성일자) : {{ $stmtInfo->writeDate }}</li>
              <li> regDT (임시저장 일시) : {{ $stmtInfo->regDT }}</li>
              <li> senderCorpName (발신자 상호) : {{ $stmtInfo->senderCorpName }}</li>
              <li> senderCorpNum (발신자 사업자번호) : {{ $stmtInfo->senderCorpNum }}</li>
              <li> senderPrintYN (발신자 인쇄여부) : {{ $stmtInfo->senderPrintYN ? 'true' : 'false' }}</li>
              <li> receiverCorpName (수신자 상호) : {{ $stmtInfo->receiverCorpName }}</li>
              <li> receiverCorpNum (수신자 사업자번호) : {{ $stmtInfo->receiverCorpNum }}</li>
              <li> receiverPrintYN (수신자 인쇄여부) : {{ $stmtInfo->receiverPrintYN ? 'true' : 'false'}}</li>
              <li> supplyCostTotal (공급가액 합계) : {{ $stmtInfo->supplyCostTotal }}</li>
              <li> taxTotal (세액 합계) : {{ $stmtInfo->taxTotal }}</li>
              <li> purposeType (영수/청구) : {{ $stmtInfo->purposeType }}</li>
              <li> issueDT (발행일시) : {{ $stmtInfo->issueDT }}</li>
              <li> stateCode (상태코드) : {{ $stmtInfo->stateCode }}</li>
              <li> stateDT (상태 변경일시) : {{ $stmtInfo->stateDT }}</li>
              <li> stateMemo (상태메모) : {{ $stmtInfo->stateMemo }}</li>
              <li> openYN (개봉여부) : {{ $stmtInfo->openYN ? 'true' : 'false' }}</li>
              <li> openDT (개봉 일시) : {{ $stmtInfo->openDT }}</li>
            </ul>
          </fieldset>
          @endforeach
      </fieldset>
     </div>
  </body>
</html>
