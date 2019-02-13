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
						<li>ceoname (대표자 성명) : {{ $CorpInfo->ceoname }} </li>
						<li>corpName (상호) : {{ $CorpInfo->corpName }} </li>
						<li>addr (주소) : {{ $CorpInfo->addr }} </li>
						<li>bizType (업태) : {{ $CorpInfo->bizType }} </li>
						<li>bizClass (종목) : {{ $CorpInfo->bizClass }} </li>
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
