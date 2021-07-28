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
          @foreach ($Result->list as $indexKey => $cbInfo)
          <fieldset class="fieldset2">
            <legend>현금영수증 정보 [{{ $indexKey+1 }}]</legend>
            <ul>
              <li>ntsconfirmNum (국세청승인번호) : {{ $cbInfo->ntsconfirmNum }}</li>
              <li>tradeDate (거래일자) : {{ $cbInfo->tradeDate }}</li>
              <li>tradeDT (거래일시) : {{ $cbInfo->tradeDT }}</li>
              <li>tradeType (문서형태) : {{ $cbInfo->tradeType }}</li>
              <li>tradeUsage (거래구분) : {{ $cbInfo->tradeUsage }}</li>
              <li>totalAmount (거래금액) : {{ $cbInfo->totalAmount }}</li>
              <li>supplyCost (공급가액) : {{ $cbInfo->supplyCost }}</li>
              <li>tax (세액) : {{ $cbInfo->tax }}</li>
              <li>serviceFee (봉사료) : {{ $cbInfo->serviceFee }}</li>
              <li>invoiceType (매입/매출) : {{ empty($cbInfo->invoiceType) ? '' : $cbInfo->invoiceType }}</li>
              <li>franchiseCorpNum (발행자 사업자번호) : {{ $cbInfo->franchiseCorpNum }}</li>
              <li>franchiseCorpName (발행자 상호) : {{ $cbInfo->franchiseCorpName }}</li>
              <li>franchiseCorpType (발행자 사업자유형) : {{ $cbInfo->franchiseCorpType }}</li>
              <li>identityNum (거래처 식별번호) : {{ $cbInfo->identityNum }}</li>
              <li>identityNumType (식별번호유형) : {{ $cbInfo->identityNumType }}</li>
              <li>customerName (고객명) : {{ $cbInfo->customerName }}</li>
              <li>cardOwnerName (카드소유자명) : {{ $cbInfo->cardOwnerName }}</li>
              <li>deductionType (공제유형) : {{ $cbInfo->deductionType }}</li>
            </ul>
          </fieldset>
          @endforeach
        </ul>
      </fieldset>
     </div>
  </body>
</html>
