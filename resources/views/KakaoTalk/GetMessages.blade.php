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
          <li>contentType (카카오톡 유형) : {{ $Result->contentType }} </li>
          <li>templateCode (템플릿 코드) : {{ $Result->templateCode ? $Result->templateCode : '' }} </li>
          <li>plusFriendID (카카오톡 검색용 아이디) : {{ $Result->plusFriendID }} </li>
          <li>sendNum (발신번호) : {{ $Result->sendNum }} </li>
          <li>altSubject ([동보]대체문자 제목) : {{ $Result->altSubject }} </li>
          <li>altContent ([동보]대체문자 내용) : {{ $Result->altContent }} </li>
          <li>altSendType (대체문자 전송유형) : {{ $Result->altSendType }} </li>
          <li>reserveDT (예약일시) : {{ $Result->reserveDT }} </li>
          <li>adsYN (광고전송 여부) : {{ $Result->adsYN }} </li>
          <li>imageURL (친구톡 이미지 URL) : {{ $Result->imageURL ? $Result->imageURL : '' }} </li>
          <li>sendCnt (전송건수) : {{ $Result->sendCnt }} </li>
          <li>successCnt (성공건수) : {{ $Result->successCnt }} </li>
          <li>failCnt (실패건수) : {{ $Result->failCnt }} </li>
          <li>altCnt (대체문자 건수) : {{ $Result->altCnt }} </li>
          <li>cancelCnt (취소건수) : {{ $Result->cancelCnt }} </li>
        </ul>

        @if (empty($Result->btns) == false)
          @foreach ($Result->btns as $indexKey => $btnInfo)
          <fieldset class="fieldset2">
            <legend>버튼 정보 [{{ $indexKey+1 }} / {{count($Result->btns)}}]</legend>
            <ul>
              <li>n (버튼명) : {{ $btnInfo->n }} </li>
              <li>t (버튼유형) : {{ $btnInfo->t }} </li>
              <li>u1 (버튼링크1) : {{ false == empty($btnInfo->u1) ? $btnInfo->u1 : ''}} </li>
              <li>u2 (버튼링크2) : {{ false == empty($btnInfo->u2) ? $btnInfo->u2 : ''}} </li>
            </ul>
          </fieldset>
          @endforeach
        @endif

        @foreach ($Result->msgs as $indexKey => $msgInfo)
        <fieldset class="fieldset2">
          <legend>카카오톡 결과정보 확인 [ {{ $indexKey+1 }} / {{count($Result->msgs)}} ]</legend>
          <ul>
            <li> state (전송상태 코드) : {{ $msgInfo->state }} </li>
            <li> sendDT (전송일시) : {{ $msgInfo->sendDT }} </li>
            <li> receiveNum (수신번호) : {{ $msgInfo->receiveNum }} </li>
            <li> receiveName (수신자명) : {{ $msgInfo->receiveName }} </li>
            <li> content (알림톡/친구톡 내용) : {{ $msgInfo->content }} </li>
            <li> result (전송결과 코드) : {{ $msgInfo->result }} </li>
            <li> resultDT (전송결과 수신일시) : {{ $msgInfo->resultDT }} </li>
            <li> altSubject (대체문자 제목) : {{ $msgInfo->altSubject }} </li>
            <li> altContent (대체문자 내용) : {{ $msgInfo->altContent }} </li>
            <li> altContentType (대체문자 전송유형) : {{ $msgInfo->altContentType }} </li>
            <li> altSendDT (대체문자 전송일시) : {{ $msgInfo->altSendDT }} </li>
            <li> altResult (대체문자 전송결과 코드) : {{ $msgInfo->altResult }} </li>
            <li> altResultDT (대체문자 전송결과 수신일시) : {{ $msgInfo->altResultDT }} </li>
            <li> receiptNum (접수번호) : {{ $msgInfo->receiptNum }} </li>
            <li> requestNum (요청번호) : {{ $msgInfo->requestNum }} </li>
            <li> interOPRefKey (파트너 지정키) : {{ $msgInfo->interOPRefKey }} </li>
          </ul>
        </fieldset>
        @endforeach
      </fieldset>
     </div>
  </body>
</html>
