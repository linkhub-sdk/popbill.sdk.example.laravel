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
            <li>perPage (페이지당 목록 건수) : {{ $Result->perPage }} </li>
            <li>pageNum (페이지 번호) : {{ $Result->pageNum }} </li>
            <li>pageCount (페이지 개수) : {{ $Result->pageCount }} </li>
          </ul>
          @foreach ($Result->list as $indexKey => $faxInfo)
          <fieldset class="fieldset2">
            <legend>팩스 전송결과 정보 [{{ $indexKey+1 }}]</legend>
            <ul>
              <li> state (상태코드) : {{ $faxInfo->state }} </li>
              <li> result (결과코드) : {{ $faxInfo->result }} </li>
              <li> sendNum (발신번호) : {{ $faxInfo->sendNum }} </li>
              <li> senderName (발신자명) : {{ $faxInfo->senderName }} </li>
              <li> receiveNum (수신번호) : {{ $faxInfo->receiveNum }} </li>
              <li> receiveNumType (수신번호 유형) : {{ $faxInfo->receiveNumType }} </li>
              <li> receiveName (수신자명) : {{ $faxInfo->receiveName }} </li>
              <li> title (팩스제목) : {{ $faxInfo->title }} </li>
              <li> sendPageCnt (전체 페이지수) : {{ $faxInfo->sendPageCnt }} </li>
              <li> successPageCnt (성공 페이지수) : {{ $faxInfo->successPageCnt }} </li>
              <li> failPageCnt (실패 페이지수) : {{ $faxInfo->failPageCnt }} </li>
              <li> cancelPageCnt (취소 페이지수) : {{ $faxInfo->cancelPageCnt }} </li>

              <li> reserveDT (예약일시) : {{ $faxInfo->reserveDT }} </li>
              <li> receiptDT (접수일시) : {{ $faxInfo->receiptDT }} </li>
              <li> sendDT (전송일시) : {{ $faxInfo->sendDT }} </li>
              <li> resultDT (전송결과 수신일시) : {{ $faxInfo->resultDT }} </li>
              <li> fileNames (전송파일명 리스트) : {{ print_r($faxInfo->fileNames) }} </li>
              <li> receiptNum (접수번호) : {{ $faxInfo->receiptNum }} </li>
              <li> requestNum (요청번호) : {{ $faxInfo->requestNum }} </li>
              <li> interOPRefKey (파트너 지정키) : {{ $faxInfo->interOPRefKey }} </li>
              <li> chargePageCnt (과금 페이지수) : {{ $faxInfo->chargePageCnt }} </li>
              <li> refundPageCnt (환불 페이지수) : {{ $faxInfo->refundPageCnt }} </li>
              <li> tiffFileSize (변환파일용랑) : {{ $faxInfo->tiffFileSize }}byte</li>
            </ul>
          </fieldset>
          @endforeach
        </ul>
      </fieldset>
     </div>
  </body>
</html>
