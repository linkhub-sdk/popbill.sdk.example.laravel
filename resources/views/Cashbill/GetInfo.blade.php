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
          @foreach ($CashbillInfo as $indexKey => $cbInfo)
          <fieldset class="fieldset2">
            <legend>현금영수증 상태 및 요약 정보 확인 [{{ $indexKey+1 }}]</legend>
            <ul>
                <li> itemKey (팝빌번호) : {{ $cbInfo->itemKey }}</li>
                <li> mgtKey (문서번호) : {{ $cbInfo->mgtKey }}</li>
                <li> tradeDate (거래일자) : {{ $cbInfo->tradeDate }}</li>
                <li> tradeDT (거래일시) : {{ $cbInfo->tradeDT }}</li>
                <li> tradeType (문서형태) : {{ $cbInfo->tradeType }}</li>
                <li> tradeUsage (거래구분) : {{ $cbInfo->tradeUsage }}</li>
                <li> tradeOpt (거래유형) : {{ $cbInfo->tradeOpt }}</li>
                <li> taxationType (과세형태) : {{ $cbInfo->taxationType }}</li>
                <li> totalAmount (거래금액) : {{ $cbInfo->totalAmount }}</li>
                <li> supplyCost (공급가액) :  {{ $cbInfo->supplyCost }}</li>
                <li> tax (부가세) :  {{ $cbInfo->tax }}</li>
                <li> serviceFee (봉사료) :  {{ $cbInfo->serviceFee }}</li>  
                <li> issueDT (발행일시) : {{ $cbInfo->issueDT }}</li>
                <li> regDT (등록일시) : {{ $cbInfo->regDT }}</li>
                <li> stateMemo (상태메모) : {{ $cbInfo->stateMemo }}</li>
                <li> stateCode (상태코드) : {{ $cbInfo->stateCode }}</li>
                <li> stateDT (상태변경일시) : {{ $cbInfo->stateDT }}</li>
                <li> identityNum (식별번호) : {{ $cbInfo->identityNum }}</li>
                <li> itemName (주문상품명) : {{ $cbInfo->itemName }}</li>
                <li> orderNumber (주문번호) :  {{ $cbInfo->orderNumber }}</li>
                <li> email (구매자(고객) 이메일) :  {{ $cbInfo->email }}</li>
                <li> hp (구매자(고객) 휴대폰) :  {{ $cbInfo->hp }}</li>
                <li> customerName (주문자명) : {{ $cbInfo->customerName }}</li>
                <li> confirmNum (국세청승인번호) : {{ $cbInfo->confirmNum }}</li>
                <li> orgConfirmNum (당초 승인 현금영수증 국세청승인번호) : {{ $cbInfo->orgConfirmNum }}</li>
                <li> orgTradeDate (당초 승인 현금영수증 거래일자) : {{ $cbInfo->orgTradeDate }}</li>
                <li> ntssendDT (국세청 전송일시) : {{ $cbInfo->ntssendDT }}</li>
                <li> ntsresultDT (국세청 처리결과 수신일시) : {{ $cbInfo->ntsresultDT }}</li>
                <li> ntsresultCode (국세청 처리결과 상태코드) : {{ $cbInfo->ntsresultCode }}</li>
                <li> ntsresultMessage (국세청 처리결과 메시지) : {{ $cbInfo->ntsresultMessage }}</li>
                <li> printYN (인쇄여부) : {{ $cbInfo->printYN }}</li>
                <li> interOPYN (연동문서 여부) :  {{ $cbInfo->interOPYN ? 'true' : 'false' }}</li>
            </ul>
          </fieldset>
          @endforeach
        </ul>
      </fieldset>
     </div>
  </body>
</html>
