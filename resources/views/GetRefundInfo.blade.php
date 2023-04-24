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

                    <li>reqDT (신청일시) : {{$result->reqDT}}</li>
                    <li>requestPoint (환불 신청 포인트) : {{$result->requestPoint}}</li>
                    <li>accountBank (환불계좌 은행명) : {{$result->accountBank}}</li>
                    <li>accountNum (환불계좌번호) : {{$result->accountNum}}</li>
                    <li>accountName (환불계좌 예금주명) : {{$result->accountName}}</li>
                    <li>state (상태) : {{$result->state}}</li>
                    <li>reason (환불 사유) : {{$result->reason}}</li>
                </ul>
            </fieldset>
        </fieldset>
    </div>
</body>

</html>
