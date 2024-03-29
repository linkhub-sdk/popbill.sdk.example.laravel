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
        <li>referenceID (사업자번호) : {{ $Result->referenceID }}</li>
        <li>contractDT (정액제 서비스 시작일시) : {{ $Result->contractDT }}</li>
        <li>useEndDate (정액제 서비스 종료일) : {{ $Result->useEndDate }}</li>
        <li>baseDate (자동연장 결제일) : {{ $Result->baseDate }}</li>
        <li>state (정액제 서비스 상태) : {{ $Result->state }}</li>
        <li>closeRequestYN (정액제 서비스 해지신청 여부) : {{ $Result->closeRequestYN ? 'true' : 'false' }}</li>
        <li>useRestrictYN (정액제 서비스 사용제한 여부) : {{ $Result->useRestrictYN ? 'true' : 'false' }}</li>
        <li>closeOnExpired (정액제 서비스 만료 시 해지여부) : {{ $Result->closeOnExpired  ? 'true' : 'false' }}</li>
        <li>unPaidYN (미수금 보유 여부) : {{ $Result->unPaidYN ? 'true' : 'false' }}</li>
    </ul>
    </fieldset>
  </div>
 </body>
</html>
