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
          <li>mgtKey (문서번호) : {{ $CashbillInfo->mgtKey }} </li>
          <li>orgConfirmNum (당초 국세청승인번호) : {{ $CashbillInfo->orgConfirmNum }} </li>
          <li>orgTradeDate (당초 거래일자) : {{ $CashbillInfo->orgTradeDate }} </li>
          <li>tradeDate (거래일자) : {{ $CashbillInfo->tradeDate }} </li>
          <li>tradeDT (거래일시) : {{ $CashbillInfo->tradeDT }} </li>
          <li>tradeType (문서형태) : {{ $CashbillInfo->tradeType }} </li>
          <li>tradeUsage (거래구분) : {{ $CashbillInfo->tradeUsage }} </li>
          <li>tradeOpt (거래유형) : {{ $CashbillInfo->tradeOpt }} </li>
          <li>taxationType (과세형태) : {{ $CashbillInfo->taxationType }} </li>
          <li>totalAmount (거래금액) : {{ $CashbillInfo->totalAmount }} </li>
          <li>supplyCost (공급가액) : {{ $CashbillInfo->supplyCost }} </li>
          <li>tax (부가세) : {{ $CashbillInfo->tax }} </li>
          <li>serviceFee (봉사료) : {{ $CashbillInfo->serviceFee }} </li>
          <li>franchiseCorpNum (가맹점 사업자번호) : {{ $CashbillInfo->franchiseCorpNum }} </li>
          <li>franchiseTaxRegID (가맹점 종사업장 식별번호) : {{ $CashbillInfo->franchiseTaxRegID }} </li>
          <li>franchiseCorpName (가맹점 상호) : {{ $CashbillInfo->franchiseCorpName }} </li>
          <li>franchiseCEOName (가맹점 대표자 성명) : {{ $CashbillInfo->franchiseCEOName }} </li>
          <li>franchiseAddr (가맹점 주소) : {{ $CashbillInfo->franchiseAddr }} </li>
          <li>franchiseTEL (가맹점 전화번호) : {{ $CashbillInfo->franchiseTEL }} </li>
          <li>identityNum (식별번호) : {{ $CashbillInfo->identityNum }} </li>
          <li>customerName (구매자(고객) 성명) : {{ $CashbillInfo->customerName }} </li>
          <li>itemName (주문상품명) : {{ $CashbillInfo->itemName }} </li>
          <li>orderNumber (주문번호) : {{ $CashbillInfo->orderNumber }} </li>
          <li>email (구매자(고객) 메일) : {{ $CashbillInfo->email }} </li>
          <li>hp (구매자(고객) 휴대폰) : {{ $CashbillInfo->hp }} </li>
          <li>smssendYN (구매자 알림문자 전송 여부) : {{ $CashbillInfo->smssendYN }} </li>
          <li>cancelType (취소사유) : {{ $CashbillInfo->cancelType }} </li>
        </ul>
      </fieldset>
     </div>
  </body>
</html>
