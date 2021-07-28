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
          <li>ntsconfirmNum (국세청승인번호) : {{ $Taxinvoice->ntsconfirmNum }}</li>
          <li>writeDate (작성일자) : {{ $Taxinvoice->writeDate }}</li>
          <li>issueDT (발행일시) : {{ $Taxinvoice->issueDT }}</li>
          <li>invoiceType (전자세금계산서 종류) : {{ $Taxinvoice->invoiceType }}</li>
          <li>taxType (과세형태) : {{ $Taxinvoice->taxType }}</li>
          <li>taxTotal (세액 합계) : {{ $Taxinvoice->taxTotal }}</li>
          <li>supplyCostTotal (공급가액 합계) : {{ $Taxinvoice->supplyCostTotal }}</li>
          <li>totalAmount (합계금액) : {{ $Taxinvoice->totalAmount }}</li>
          <li>purposeType (영수/청구) : {{ $Taxinvoice->purposeType }}</li>
          <li>serialNum (일련번호) : {{ $Taxinvoice->serialNum }}</li>
          <li>cash (현금) : {{ $Taxinvoice->cash }}</li>
          <li>chkBill (수표) : {{ $Taxinvoice->chkBill }}</li>
          <li>credit (외상) : {{ $Taxinvoice->credit }}</li>
          <li>note (어음) : {{ $Taxinvoice->note }}</li>
          <li>remark1 (비고1) : {{ $Taxinvoice->remark1 }}</li>
          <li>remark2 (비고2) : {{ $Taxinvoice->remark2 }}</li>
          <li>remark3 (비고3) : {{ $Taxinvoice->remark3 }}</li>
          <li>modifyCode (수정 사유코드) : {{ $Taxinvoice->modifyCode }}</li>
          <li>orgNTSConfirmNum (원본 전자세금계산서 국세청 승인번호) : {{ $Taxinvoice->orgNTSConfirmNum }}</li>
          <li>invoicerCorpNum (공급자 사업자번호) : {{ $Taxinvoice->invoicerCorpNum }}</li>
          <li>invoicerTaxRegID (공급자 종사업장번호) : {{ $Taxinvoice->invoicerTaxRegID }}</li>
          <li>invoicerCorpName (공급자 상호) : {{ $Taxinvoice->invoicerCorpName }}</li>
          <li>invoicerCEOName (공급자 대표자성명) : {{ $Taxinvoice->invoicerCEOName }}</li>
          <li>invoicerAddr (공급자 주소) : {{ $Taxinvoice->invoicerAddr }}</li>
          <li>invoicerBizType (공급자 업태) : {{ $Taxinvoice->invoicerBizType }}</li>
          <li>invoicerBizClass (공급자 종목) : {{ $Taxinvoice->invoicerBizClass }}</li>
          <li>invoicerContactName (공급자 담당자 성명) : {{ $Taxinvoice->invoicerContactName }}</li>
          <li>invoicerTEL (공급자 담당자 연락처) : {{ $Taxinvoice->invoicerTEL }}</li>
          <li>invoicerEmail (공급자 담당자 이메일) : {{ $Taxinvoice->invoicerEmail }}</li>
          <li>invoiceeCorpNum (공급받는자 사업자번호) : {{ $Taxinvoice->invoiceeCorpNum }}</li>
          <li>invoiceeType (공급받는자 구분) : {{ $Taxinvoice->invoiceeCorpNum }}</li>
          <li>invoiceeTaxRegID (공급받는자 종사업장번호) : {{ $Taxinvoice->invoiceeCorpNum }}</li>
          <li>invoiceeCorpName (공급받는자 상호) : {{ $Taxinvoice->invoiceeCorpNum }}</li>
          <li>invoiceeCEOName (공급받는자 대표자 성명) : {{ $Taxinvoice->invoiceeCEOName }}</li>
          <li>invoiceeAddr (공급받는자 주소) : {{ $Taxinvoice->invoiceeAddr }}</li>
          <li>invoiceeBizType (공급받는자 업태) : {{ $Taxinvoice->invoiceeBizType }}</li>
          <li>invoiceeBizClass (공급받는자 종목) : {{ $Taxinvoice->invoiceeBizClass }}</li>
          <li>invoiceeContactName1 (공급받는자 담당자 성명) : {{ $Taxinvoice->invoiceeContactName1 }}</li>
          <li>invoiceeTEL1 (공급받는자 담당자 연락처) : {{ $Taxinvoice->invoiceeTEL1 }}</li>
          <li>invoiceeEmail1 (공급받는자 담당자 이메일) : {{ $Taxinvoice->invoiceeEmail1 }}</li>
          <li>trusteeCorpNum (수탁자 사업자번호) : {{ $Taxinvoice->trusteeCorpNum }}</li>
          <li>trusteeTaxRegID (수탁자 종사업장번호) : {{ $Taxinvoice->trusteeTaxRegID }}</li>
          <li>trusteeCorpName (수탁자 상호) : {{ $Taxinvoice->trusteeCorpName }}</li>
          <li>trusteeCEOName (수탁자 대표자성명) : {{ $Taxinvoice->trusteeCEOName }}</li>
          <li>trusteeAddr (수탁자 주소) : {{ $Taxinvoice->trusteeAddr }}</li>
          <li>trusteeBizType (수탁자 업태) : {{ $Taxinvoice->trusteeBizType }}</li>
          <li>trusteeBizClass (수탁자 종목) : {{ $Taxinvoice->trusteeBizClass }}</li>
          <li>trusteeContactName (수탁자 담당자 성명) : {{ $Taxinvoice->trusteeContactName }}</li>
          <li>trusteeTEL (수탁자 담당자 연락처) : {{ $Taxinvoice->trusteeTEL }}</li>
          <li>trusteeEmail (수탁자 담당자 이메일) : {{ $Taxinvoice->trusteeEmail }}</li>

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

        </ul>
      </fieldset>
     </div>
  </body>
</html>
