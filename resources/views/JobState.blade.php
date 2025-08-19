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
        @foreach ($Result as $indexKey => $jobInfo)
        <fieldset class="fieldset2">
            <legend>수집 상태 확인 [{{ $indexKey+1 }}]</legend>
            <ul>
              <li>jobID (작업아이디) : {{ $jobInfo->jobID }}</li>
              <li>jobState (수집상태) : {{ $jobInfo->jobState }}</li>
              <li>queryType (수집유형) : {{ $jobInfo->queryType }}</li>
              <li>queryDateType (수집 일자 유형) : {{ $jobInfo->queryDateType }}</li>
              <li>queryStDate (시작일자) : {{ $jobInfo->queryStDate }}</li>
              <li>queryEnDate (종료일자) : {{ $jobInfo->queryEnDate }}</li>
              <li>errorCode (수집 결과코드) : {{ $jobInfo->errorCode }}</li>
              <li>errorReason (오류메시지) : {{ $jobInfo->errorReason }}</li>
              <li>jobStartDT (작업 시작일시) : {{ $jobInfo->jobStartDT }}</li>
              <li>jobEndDT (작업 종료일시) : {{ $jobInfo->jobEndDT }}</li>
              <li>collectCount (수집건수) : {{ $jobInfo->collectCount }}</li>
              <li>regDT (수집 요청일시) : {{ $jobInfo->regDT }}</li>
            </ul>
        </fieldset>
        @endforeach
    </ul>
    </fieldset>
  </div>
 </body>
</html>
