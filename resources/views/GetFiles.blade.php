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
      @foreach ($Result as $indexKey => $FileInfo)
      <fieldset class="fieldset2">
      <legend> 첨부파일 정보 [ {{ $indexKey+1 }} / {{ count($Result) }} ]</legend>
      <ul>
        <li> serialNum (첨부파일 일련번호) : {{ $FileInfo->serialNum }}</li>
        <li> attachedFile (파일 식별번호) : {{ $FileInfo->attachedFile }}</li>
        <li> displayName (파일명) : {{ $FileInfo->displayName }}</li>
        <li> regDT (첨부일시) : {{ $FileInfo->regDT }}</li>
      </ul>
      </fieldset>
      @endforeach
    </ul>
    </fieldset>
  </div>
 </body>
</html>
