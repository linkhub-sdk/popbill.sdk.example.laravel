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
        @foreach ($Result as $indexKey => $msgBriefInfo)
        <fieldset class="fieldset2">
          <legend>문자전송 결과정보 확인 [ {{ $indexKey+1 }} / {{count($Result)}} ]</legend>
  				<ul>
            <li> rNum (접수번호) : {{ $msgBriefInfo->rNum }} </li>
            <li> sn (일련번호) : {{ $msgBriefInfo->sn }} </li>
            <li> stat (전송 상태코드) : {{ $msgBriefInfo->stat }} </li>
            <li> rlt (전송 결과코드) : {{ $msgBriefInfo->rlt }} </li>
            <li> sDT (전송일시) : {{ $msgBriefInfo->sDT }} </li>
            <li> rDT (전송결과 수신일시) : {{ $msgBriefInfo->rDT }} </li>
            <li> net (전송처리 이동통신사명) : {{ $msgBriefInfo->net }} </li>
            <li> srt (구 전송결과 코드) : {{ $msgBriefInfo->srt }} </li>
  				</ul>
        </fieldset>
        @endforeach
			</fieldset>

				<ul>

				</ul>
			</fieldset>
		 </div>
	</body>
</html>
