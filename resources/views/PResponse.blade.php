<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen"/>
    <title>팝빌 SDK PHP Laravel Example.</title>
</head>
<body>
<div id="content">
    <p class="heading1">Response</p>
    <br/>
    <fieldset class="fieldset1">
        <legend>{{\Request::fullUrl()}}</legend>
        <ul>
            <li>code (응답코드) : {{ $code }}</li>
            <li>message (응답메시지) : {{ $message }}</li>
            @isset($ntsConfirmNum)
            <li>ntsConfirmNum (국세청 승인번호) : {{ $ntsConfirmNum }}</li>
            @endisset
        </ul>
    </fieldset>
</div>
</body>
</html>
