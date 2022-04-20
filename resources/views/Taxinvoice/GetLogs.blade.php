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
      @foreach ($Result as $indexKey => $info)
      <fieldset class="fieldset2">
      <legend> 상태변경 이력 [ {{ $indexKey+1 }} / {{ count($Result) }} ]</legend>
      <ul>
              <li>docLogType (로그타입) : {{ $info->docLogType }}</li>
              <li>log (이력정보) : {{ $info->log  }}</li>
              <li>procType (처리형태) : {{ $info->procType  }}</li>
              <li>procContactName (처리담당자) : {{ $info->procContactName  }}</li>
              <li>procCorpName (처리회사명) : {{ $info->procCorpName  }}</li>
              <li>procMemo (처리메모) : {{ $info->procMemo  }}</li>
              <li>regDT (등록일시) : {{ $info->regDT  }}</li>
              <li>ip (아이피) : {{ $info->ip }}</li>
      </ul>
      </fieldset>
      @endforeach
    </ul>
    </fieldset>
  </div>
 </body>
</html>
