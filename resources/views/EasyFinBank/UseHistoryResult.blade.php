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
                    <li>code (응답코드) : {{ $Result->code }} </li>
                    <li>total (총 검색결과 건수) : {{ $Result->total }} </li>
                    <li>pageNum (페이지 번호) : {{ $Result->pageNum }} </li>
                    <li>perPage (페이지당 목록개수) : {{ $Result->perPage }} </li>
                    <li>pageCount (페이지 개수) : {{ $Result->pageCount }} </li>
                </ul>
                @foreach ($Result->list as $indexKey => $tradeInfo)
                <fieldset class="fieldset2">
                    <legend>포인트 결제내역정보 [{{ $indexKey+1 }}]</legend>
                    <ul>
                        <li>itemCode (서비스 코드) : {{ $tradeInfo->itemCode }}</li>
                        <li>txType (포인트 증감 유형) : {{ $tradeInfo->txType }}</li>
                        <li>balance (증감 포인트) : {{ $tradeInfo->balance }}</li>
                        <li>txDT (포인트 증감 일시) : {{ $tradeInfo->txDT }}</li>
                        <li>userID (담당자 아이디) : {{ $tradeInfo->userID }}</li>
                        <li>userName (담당자명) : {{ $tradeInfo->userName }}</li>
                    </ul>
                </fieldset>
                @endforeach
            </fieldset>
         </div>
    </body>
</html>
