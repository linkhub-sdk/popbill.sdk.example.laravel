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
          <li>ResultCode (응답코드) : {{ $Result->ResultCode }}</li>
          <li>Message (국세청승인번호) : {{ $Result->Message }}</li>
          <li>retObject (전자세금계산서 XML문서) : {{ $Result->retObject }}</li>
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
