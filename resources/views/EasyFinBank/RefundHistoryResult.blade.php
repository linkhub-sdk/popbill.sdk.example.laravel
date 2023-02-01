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
                    <legend>환불신청 내역정보 [{{ $indexKey+1 }}]</legend>
                    <ul>
                        <li>reqDT (신청일자) : {{ $tradeInfo->reqDT }}</li>
                        <li>requestPoint (환불 신청포인트) : {{ $tradeInfo->requestPoint }}</li>
                        <li>accountBank (환불계좌 은행명) : {{ $tradeInfo->accountBank }}</li>
                        <li>accountNum (환불계좌번호) : {{ $tradeInfo->accountNum }}</li>
                        <li>accountName (환불계좌 예금주명) : {{ $tradeInfo->accountName }}</li>
                        <li>state (상태) : {{ $tradeInfo->state }}</li>
                        <li>reason (환불사유) : {{ $tradeInfo->reason }}</li>
                    </ul>
                </fieldset>
                @endforeach
            </fieldset>
         </div>
    </body>
</html>
