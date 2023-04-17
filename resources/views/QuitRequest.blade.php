<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" type="text/css" href="/css/example.css" media="screen" />
    <title>팝빌 SDK PHP Laravel Example.</title>
</head>

<body>
    <div id="content">
        <p class="heading1">Response</p>
        <br />
        <fieldset class="fieldset1">
            <legend>{{\Request::fullUrl()}}</legend>
            <fieldset class="fieldset2">
                <ul>
                    <li>code (응답 코드) : {{$result->code}}</li>
                    <li>message (응답 메시지) : {{$result->message}}</li>

                </ul>
            </fieldset>
        </fieldset>
    </div>
</body>

</html>
