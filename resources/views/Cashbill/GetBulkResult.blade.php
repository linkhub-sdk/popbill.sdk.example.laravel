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
                    <li>code (응답코드) : {{ $Result->code }}</li>
                    <li>message (응답메시지) : {{ $Result->message }}</li>
                    <li>submitID (제출아이디) : {{ $Result->submitID }}</li>
                    <li>submitCount (현금영수증 접수 건수) : {{ $Result->submitCount }}</li>
                    <li>successCount (현금영수증 발행 성공 건수) : {{ $Result->successCount }}</li>
                    <li>failCount (현금영수증 발행 실패 건수) : {{ $Result->failCount }}</li>
                    <li>txState (접수상태코드) : {{ $Result->txState }}</li>
                    <li>txResultCode (접수 결과코드) : {{ $Result->txResultCode }}</li>
                    <li>txStartDT (발행처리 시작일시) : {{ $Result->txStartDT }}</li>
                    <li>txEndDT (발행처리 완료일시) : {{ $Result->txEndDT }}</li>
                    <li>receiptDT (접수일시) : {{ $Result->receiptDT }}</li>
                    <li>receiptID (접수아이디) : {{ $Result->receiptID }}</li>
                    @foreach ($Result->issueResult as $indexKey => $issueResult)
                    <fieldset class="fieldset2">
                        <legend>발행 결과 [{{ $indexKey+1 }}]</legend>
                        <ul>
                            <li>code (응답코드) : {{ $issueResult->code }}</li>
                            <li>message (응답메시지) : {{ $issueResult->message }}</li>
                            <li>mgtKey (문서번호) : {{ $issueResult->mgtKey }}</li>
                            <li>confirmNum (국세청 승인번호) : {{ $issueResult->confirmNum }}</li>
                            <li>tradeDate (거래일자) : {{ $issueResult->tradeDate }}</li>
                            <li>issueDT (발행일시) : {{ $issueResult->issueDT }}</li>
                        </ul>
                    </fieldset>
                    @endforeach
                </ul>
            </fieldset>
        </div>
    </body>
</html>
