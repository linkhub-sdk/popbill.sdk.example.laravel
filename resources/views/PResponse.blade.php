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
            @isset($receiptID)
            <li>$receiptID (접수 아이디) : {{ $receiptID }}</li>
            @endisset
            @isset($confirmNum)
            <li>confirmNum (국세청 승인번호) : {{ $confirmNum }}</li>
            @endisset
            @isset($tradeDate)
            <li>tradeDate (거래일자) : {{ $tradeDate }}</li>
            @endisset
            @isset($tradeDT)
            <li>tradeDT (거래일시) : {{ $tradeDT }}</li>
            @endisset
            @isset($invoiceNum)
            <li>invoiceNum (팝빌 승인번호) : {{ $invoiceNum }}</li>
            @endisset
            @isset($refundCode)
            <li>refundCode (환불 코드) : {{ $refundCode }}</li>
            @endisset
        </ul>
    </fieldset>
</div>
</body>
</html>
