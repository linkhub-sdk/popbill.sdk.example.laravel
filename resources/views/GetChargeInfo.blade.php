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
            <li>unitCost (단가) : {{ $Result->unitCost }}</li>
						<li>chargeMethod (과금유형) : {{ $Result->chargeMethod }}</li>
						<li>rateSystem (과금제도) : {{ $Result->rateSystem }}</li>
			</fieldset>
		 </div>
	</body>
</html>
