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
          <li>count (수집 결과 건수) : {{ $Result->count }}</li>
          <li>cntAccIn (입금거래 건수) : {{ $Result->cntAccIn }}</li>
          <li>cntAccOut (출금거래 건수) : {{ $Result->cntAccOut }}</li>
          <li>totalAccIn (입금액 합계) : {{ $Result->totalAccIn }}</li>
          <li>totalAccOut (출금액 합계) : {{ $Result->totalAccIn }}</li>
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
