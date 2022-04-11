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
          @foreach ($Result->list as $indexKey => $msgInfo)
          <fieldset class="fieldset2">
            <legend>카카오톡 전송결과 정보 [{{ $indexKey+1 }} / {{count($Result->list)}}]</legend>
            <ul>
              <li> state (전송상태 코드) : {{ $msgInfo->state }} </li>
              <li> sendDT (전송일시) : {{ $msgInfo->sendDT }} </li>
              <li> result (전송결과 코드) : {{ $msgInfo->result }} </li>
              <li> resultDT (전송결과 수신일시) : {{ $msgInfo->resultDT }} </li>
              <li> contentType (카카오톡 유형) : {{ $msgInfo->contentType }} </li>
              <li> receiveNum (수신번호) : {{ $msgInfo->receiveNum }} </li>
              <li> receiveName (수신자명) : {{ $msgInfo->receiveName }} </li>
              <li> content (알림톡/친구톡 내용) : {{ $msgInfo->content }} </li>
              <li> altContentType (대체문자 전송타입) : {{ $msgInfo->altContentType }} </li>
              <li> altSendDT (대체문자 전송일시) : {{ $msgInfo->altSendDT }} </li>
              <li> altResult (대체문자 전송결과 코드) : {{ $msgInfo->altResult }} </li>
              <li> altResultDT (대체문자 전송결과 수신일시) : {{ $msgInfo->altResultDT }} </li>
              <li> altSubject (대체문자 제목) : {{ $msgInfo->altSubject }} </li>
              <li> altContent (대체문자 내용) : {{ $msgInfo->altContent }} </li>
              <li> reserveDT (예약일시) : {{ $msgInfo->reserveDT }} </li>
              <li> receiptNum (접수번호) : {{ $msgInfo->receiptNum }} </li>
            </ul>
          </fieldset>
          @endforeach
        </ul>
      </fieldset>
     </div>
  </body>
</html>
