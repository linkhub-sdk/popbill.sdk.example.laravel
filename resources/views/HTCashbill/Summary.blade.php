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
                    <li>supplyCostTotal (공급가액 합계) : {{ $Result->supplyCostTotal }}</li>
                    <li>taxTotal (세액 합계) : {{ $Result->taxTotal }}</li>
                    <li>serviceFeeTotal (봉사료 합계) : {{ $Result->serviceFeeTotal }}</li>
                    <li>amountTotal (합계 금액) : {{ $Result->amountTotal }}</li>
                </ul>
            </fieldset>
         </div>
    </body>
</html>
