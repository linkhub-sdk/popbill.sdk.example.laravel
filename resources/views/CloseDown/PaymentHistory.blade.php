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
                    <li>productType (결제 내용) : {{ $Result->productType }}</li>
                    <li>productName (정액제 상품명) : {{ $Result->productName }}</li>
                    <li>settleType (결제유형) : {{ $Result->settleType }}</li>
                    <li>settlerName (담당자명) : {{ $Result->settlerName }}</li>
                    <li>settlerEmail (담당자메일) : {{ $Result->settlerEmail }}</li>
                    <li>settleCost (결제금액) : {{ $Result->settleCost }}</li>
                    <li>settlePoint (충전포인트) : {{ $Result->settlePoint }}</li>
                    <li>settleState (결제상태) : {{ $Result->settleState }}</li>
                    <li>regDT (등록일시) : {{ $Result->regDT }}</li>
                    <li>stateDT (상태일시) : {{ $Result->stateDT }}</li>
                </ul>
            </fieldset>
         </div>
    </body>
</html>
