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
                    <li>bankCode (기관코드) : {{ $Result->bankCode }}</li>
                    <li>accountNumber (계좌번호) : {{ $Result->accountNumber }}</li>
                    <li>accountName (예금주 성명) : {{ $Result->accountName }}</li>
                    <li>checkDT (확인일시) : {{ $Result->checkDT }}</li>
                    <li>result (응답코드) : {{ $Result->result }}</li>
                    <li>resultMessage (응답메시지) : {{ $Result->resultMessage }}</li>
                </ul>
            </fieldset>
         </div>
    </body>
</html>
