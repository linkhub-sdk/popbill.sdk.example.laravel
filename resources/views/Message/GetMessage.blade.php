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
        @foreach ($Result as $indexKey => $msgInfo)
        <fieldset class="fieldset2">
          <legend>문자전송 결과정보 확인 [ {{ $indexKey+1 }} / {{count($Result)}} ]</legend>
          <ul>
              <li> state (전송상태 코드) : {{ $msgInfo->state }} </li>
              <li> result (전송결과 코드) : {{ $msgInfo->result }} </li>
              <li> subject (제목) : {{ $msgInfo->subject }} </li>
              <li> type (메시지 유형) : {{ $msgInfo->type }} </li>
              <li> content (메시지 내용) : {{ $msgInfo->content }} </li>
              <li> sendNum (발신번호) : {{ $msgInfo->sendNum }} </li>
              <li> senderName (발신자명) : {{ $msgInfo->senderName }} </li>
              <li> receiveNum (수신번호) : {{ $msgInfo->receiveNum }} </li>
              <li> receiveName (수신자명) : {{ $msgInfo->receiveName }} </li>
              <li> receiptDT (접수일시) : {{ $msgInfo->receiptDT }} </li>
              <li> sendDT (전송일시) : {{ $msgInfo->sendDT }} </li>
              <li> resultDT (전송결과 수신일시) : {{ $msgInfo->resultDT }} </li>
              <li> reserveDT (예약일시) : {{ $msgInfo->reserveDT }} </li>
              <li> tranNet (전송처리 이동통신사명) : {{ $msgInfo->tranNet }} </li>
              <li> receiptNum (접수번호) : {{ $msgInfo->receiptNum }} </li>
              <li> requestNum (요청번호) : {{ $msgInfo->requestNum }} </li>
          </ul>
        </fieldset>
        @endforeach
      </fieldset>
     </div>
  </body>
</html>
