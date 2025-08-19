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
            <li>perPage (페이지 당 목록 건수) : {{ $Result->perPage }} </li>
            <li>pageNum (페이지 번호) : {{ $Result->pageNum }} </li>
            <li>pageCount (페이지 개수) : {{ $Result->pageCount }} </li>
            <li>lastScrapDT (최종 조회일시) : {{ $Result->lastScrapDT }} </li>
            <li>balance (현재 잔액) : {{ $Result->balance }} </li>
          </ul>
          @foreach ($Result->list as $indexKey => $tradeInfo)
          <fieldset class="fieldset2">
            <legend>거래내역 [{{ $indexKey+1 }}]</legend>
            <ul>
              <li>tid (거래내역 아이디) : {{ $tradeInfo->tid }}</li>
              <li>trdate (거래일자) : {{ $tradeInfo->trdate }}</li>
              <li>trserial (거래일련번호) : {{ $tradeInfo->trserial }}</li>
              <li>trdt (거래일시) : {{ $tradeInfo->trdt }}</li>
              <li>accIn (입금액) : {{ $tradeInfo->accIn }}</li>
              <li>accOut (출금액) : {{ $tradeInfo->accOut }}</li>
              <li>balance (잔액) : {{ $tradeInfo->balance }}</li>
              <li>remark1 (비고1) : {{ $tradeInfo->remark1 }}</li>
              <li>remark2 (비고2) : {{ $tradeInfo->remark2 }}</li>
              <li>remark3 (비고3) : {{ $tradeInfo->remark3 }}</li>
              <li>remark4 (비고4) : {{ $tradeInfo->remark4 }}</li>
              <li>regDT (등록일시) : {{ $tradeInfo->regDT }}</li>
              <li>memo (메모) : {{ $tradeInfo->memo }}</li>
            </ul>
          </fieldset>
          @endforeach
        </ul>
      </fieldset>
     </div>
  </body>
</html>
