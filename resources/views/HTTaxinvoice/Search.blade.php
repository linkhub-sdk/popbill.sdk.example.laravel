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
          @foreach ($Result->list as $indexKey => $tiInfo)
          <fieldset class="fieldset2">
            <legend>세금계산서 정보 [{{ $indexKey+1 }}]</legend>
            <ul>
              <li>ntsconfirmNum (국세청승인번호) : {{ $tiInfo->ntsconfirmNum }}</li>
              <li>writeDate (작성일자) : {{ $tiInfo->writeDate }}</li>
              <li>issueDate (발행일자) : {{ $tiInfo->issueDate }}</li>
              <li>sendDate (전송일자) : {{ $tiInfo->sendDate }}</li>
              <li>invoiceType (구분) : {{ $tiInfo->invoiceType }}</li>
              <li>taxType (과세형태) : {{ $tiInfo->taxType }}</li>
              <li>purposeType (결제대금 수취여부) : {{ $tiInfo->purposeType }}</li>
              <li>supplyCostTotal (공급가액 합계) : {{ $tiInfo->supplyCostTotal }}</li>
              <li>taxTotal (세액 합계) : {{ $tiInfo->taxTotal }}</li>
              <li>totalAmount (합계금액) : {{ $tiInfo->totalAmount }}</li>
              <li>remark1 (비고) : {{ $tiInfo->remark1 }}</li>

              <li>invoicerCorpNum (공급자 사업자번호) : {{ $tiInfo->invoicerCorpNum }}</li>
              <li>invoicerTaxRegID (공급자 종사업장번호) : {{ $tiInfo->invoicerTaxRegID }}</li>
              <li>invoicerCorpName (공급자 상호) : {{ $tiInfo->invoicerCorpName }}</li>
              <li>invoicerCEOName (공급자 대표자성명) : {{ $tiInfo->invoicerCEOName }}</li>
              <li>invoicerEmail (공급자 담당자 이메일) : {{ $tiInfo->invoicerEmail }}</li>

              <li>invoiceeCorpNum (공급받는자 사업자번호) : {{ $tiInfo->invoiceeCorpNum }}</li>
              <li>invoiceeType (공급받는자 구분) : {{ $tiInfo->invoiceeType }}</li>
              <li>invoiceeTaxRegID (공급받는자 종사업장번호) : {{ $tiInfo->invoiceeTaxRegID }}</li>
              <li>invoiceeCorpName (공급받는자 상호) : {{ $tiInfo->invoiceeCorpName }}</li>
              <li>invoiceeCEOName (공급받는자 대표자 성명) : {{ $tiInfo->invoiceeCEOName }}</li>
              <li>invoiceeEmail1 (공급받는자 담당자 이메일) : {{ $tiInfo->invoiceeEmail1 }}</li>
              <li>invoiceeEmail2 (공급받는자 ASP 연계사업자 이메일) : {{ $tiInfo->invoiceeEmail2 }}</li>

              <li>trusteeCorpNum (수탁자 사업자번호) : {{ $tiInfo->trusteeCorpNum }}</li>
              <li>tursteeTaxRegID (수탁자 종사업장번호) : {{ $tiInfo->trusteeTaxRegID }}</li>
              <li>tursteeCorpName (수탁자 상호) : {{ $tiInfo->trusteeCorpName }}</li>
              <li>trusteeCEOName (수탁자 대표자 성명) : {{ $tiInfo->trusteeCEOName }}</li>
              <li>trusteeEmail (수탁자 담당자 이메일) : {{ $tiInfo->trusteeEmail }}</li>

              <li>purchaseDate (거래일자) : {{ $tiInfo->purchaseDate }}</li>
              <li>itemName (품명) : {{ $tiInfo->itemName }}</li>
              <li>spec (규격) : {{ $tiInfo->spec }}</li>
              <li>qty (수량) : {{ $tiInfo->qty }}</li>
              <li>unitCost (단가) : {{ $tiInfo->unitCost }}</li>
              <li>supplyCost (공급가액) : {{ $tiInfo->supplyCost }}</li>
              <li>tax (세액) : {{ $tiInfo->tax }}</li>
              <li>remark (비고) : {{ $tiInfo->remark }}</li>

              <li>modifyYN (수정 전자세금계산서 여부) : {{ $tiInfo->modifyYN  ? 'true' : 'false' }}</li>
              <li>orgNTSConfirmNum (당초 전자세금계산서 국세청승인번호) : {{ $tiInfo->orgNTSConfirmNum }}</li>
            </ul>
          </fieldset>
          @endforeach
        </ul>
      </fieldset>
     </div>
  </body>
</html>
