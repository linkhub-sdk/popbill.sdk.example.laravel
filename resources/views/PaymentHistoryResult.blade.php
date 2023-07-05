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
                    <li>perPage (페이지 번호) : {{ $Result->perPage }} </li>
                    <li>pageNum (페이지당 목록개수) : {{ $Result->pageNum }} </li>
                    <li>pageCount (페이지 개수) : {{ $Result->pageCount }} </li>
                </ul>
                @foreach ($Result->list as $indexKey => $tradeInfo)
                <fieldset class="fieldset2">
                    <legend>결제내역 [{{ $indexKey+1 }}]</legend>
                    <ul>
                        <li>productType (결제 내용) : {{ $tradeInfo->productType }}</li>
                        <li>productName (결제 상품명) : {{ $tradeInfo->productName }}</li>
                        <li>settleType (결제유형) : {{ $tradeInfo->settleType }}</li>
                        <li>settlerName (담당자명) : {{ $tradeInfo->settlerName }}</li>
                        <li>settlerEmail (담당자메일) : {{ $tradeInfo->settlerEmail }}</li>
                        <li>settleCost (결제금액) : {{ $tradeInfo->settleCost }}</li>
                        <li>settlePoint (충전포인트) : {{ $tradeInfo->settlePoint }}</li>
                        <li>settleState (결제상태) : {{ $tradeInfo->settleState }}</li>
                        <li>regDT (등록일시) : {{ $tradeInfo->regDT }}</li>
                        <li>stateDT (상태일시) : {{ $tradeInfo->stateDT }}</li>
                    </ul>
                </fieldset>
                @endforeach
            </fieldset>
        </div>
    </body>
</html>
