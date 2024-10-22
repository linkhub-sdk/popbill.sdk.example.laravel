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
          <li> itemCode (명세서코드) : {{ $Statement->itemCode }} </li>
          <li> mgtKey (문서번호) : {{ $Statement->mgtKey }} </li>
          <li> invoiceNum (팝빌부여 문서고유번호) : {{ $Statement->invoiceNum }} </li>
          <li> formCode (맞춤양식 코드) : {{ $Statement->formCode }} </li>
          <li> writeDate (작성일자) : {{ $Statement->writeDate }} </li>
          <li> taxType (세금형태) : {{ $Statement->taxType  }} </li>
          <li> senderCorpNum (발신자 사업자번호) : {{ $Statement->senderCorpNum }} </li>
          <li> senderTaxRegID (발신자 종사업장번호) : {{ $Statement->senderTaxRegID }} </li>
          <li> senderCorpName (발신자 상호) : {{ $Statement->senderCEOName }} </li>
          <li> senderCEOName (발신자 대표자성명) : {{ $Statement->senderCEOName }} </li>
          <li> senderAddr (발신자 주소) : {{ $Statement->senderAddr }} </li>
          <li> senderBizClass (발신자 종목) : {{ $Statement->senderBizClass }} </li>
          <li> senderBizType (발신자 업태) : {{ $Statement->senderBizType }} </li>
          <li> senderContactName (발신자 담당자명) : {{ $Statement->senderContactName }} </li>
          <li> senderTEL (발신자 연락처) : {{ $Statement->senderTEL }} </li>
          <li> senderHP (발신자 휴대폰번호) : {{ $Statement->senderHP }} </li>
          <li> senderEmail (발신자 메일주소) : {{ $Statement->senderEmail }} </li>
          <li> receiverCorpNum (수신자 사업자번호) : {{ $Statement->receiverCorpNum }} </li>
          <li> receiverTaxRegID (수신자 종사업장번호) : {{ $Statement->receiverTaxRegID }} </li>
          <li> receiverCorpName (수신자 상호) : {{ $Statement->receiverCorpName }} </li>
          <li> receiverCEOName (수신자 대표자성명) : {{ $Statement->receiverCEOName }} </li>
          <li> receiverAddr (수신자 주소) : {{ $Statement->receiverAddr }} </li>
          <li> receiverBizClass (수신자 종목) : {{ $Statement->receiverBizClass }} </li>
          <li> receiverBizType (수신자 업태) : {{ $Statement->receiverBizType }} </li>
          <li> receiverContactName (수신자 담당자명) : {{ $Statement->receiverContactName }} </li>
          <li> receiverTEL (수신자 연락처) : {{ $Statement->receiverTEL }} </li>
          <li> receiverHP (수신자 휴대폰번호) : {{ $Statement->receiverHP }} </li>
          <li> receiverEmail (수신자 메일주소) : {{ $Statement->receiverEmail }} </li>
          <li> totalAmount (합계금액) : {{ $Statement->totalAmount }} </li>
          <li> supplyCostTotal (공급가액 합계) : {{ $Statement->supplyCostTotal }} </li>
          <li> taxTotal (세액 합계) : {{ $Statement->taxTotal }} </li>
          <li> purposeType (결제대금 수취여부) : {{ $Statement->purposeType }} </li>
          <li> serialNum (기재상 일련번호) : {{ $Statement->serialNum }} </li>
          <li> remark1 (비고1) : {{ $Statement->remark1 }} </li>
          <li> remark2 (비고2) : {{ $Statement->remark2 }} </li>
          <li> remark3 (비고3) : {{ $Statement->remark3 }} </li>
          <li> businessLicenseYN (사업자등록증 첨부여부) : {{ $Statement->businessLicenseYN ? 'true' : 'false' }} </li>
          <li> bankBookYN (통장사본 첨부여부) : {{ $Statement->bankBookYN ? 'true' : 'false' }} </li>
          <li> smssendYN (알림문자 전송여부) : {{ $Statement->smssendYN ? 'true' : 'false'  }} </li>
          <li> autoacceptYN (발행시 자동승인 여부) : {{ $Statement->autoacceptYN ? 'true' : 'false' }} </li>

          @if (count($Statement->detailList) > 0)
            @foreach ($Statement->detailList as $indexKey => $StatementDetail)
            <fieldset class="fieldset2">
              <legend>전자명세서 상세항목(품목)정보 [{{ $indexKey+1 }}]</legend>
              <ul>
                <li> serialNum (일련번호) : {{ $StatementDetail->serialNum }} </li>
                <li> purchaseDT (거래일자) : {{ $StatementDetail->purchaseDT }} </li>
                <li> itemName (품목명) : {{ $StatementDetail->itemName }} </li>
                <li> spec (규격) : {{ $StatementDetail->spec }} </li>
                <li> qty (수량) : {{ $StatementDetail->qty }} </li>
                <li> unitCost (단가) : {{ $StatementDetail->unitCost }} </li>
                <li> supplyCost (공급가액) : {{ $StatementDetail->supplyCost }} </li>
                <li> tax (세액) : {{ $StatementDetail->tax }} </li>
                <li> remark (비고) : {{ $StatementDetail->remark }} </li>
                <li> spare1 (여분1) : {{ $StatementDetail->spare1 }} </li>
                <li> spare2 (여분2) : {{ $StatementDetail->spare2 }} </li>
                <li> spare3 (여분3) : {{ $StatementDetail->spare3 }} </li>
                <li> spare4 (여분4) : {{ $StatementDetail->spare4 }} </li>
                <li> spare5 (여분5) : {{ $StatementDetail->spare5 }} </li>
              </ul>
            </fieldset>
            @endforeach
          @endif

          @if ($Statement->propertyBag != null)
            <fieldset class="fieldset2">
              <legend>propertyBag [추가속성 정보]</legend>
              <ul>
                @foreach ($Statement->propertyBag as $key => $value)
                  <li> {{ $key }} : {{ $value }} </li>
                @endforeach
              </ul>
            </fieldset>
          @endif
        </ul>
      </fieldset>
     </div>
  </body>
</html>
