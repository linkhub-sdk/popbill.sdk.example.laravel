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
                <br/>
                <p class="info">> state (휴폐업상태) : null-알수없음, 0-등록되지 않은 사업자번호, 1-사업중, 2-폐업, 3-휴업</p>
                <p class="info">> taxType (사업 유형) : null-알수없음, 10-일반과세자, 20-면세과세자, 30-간이과세자, 31-간이과세자(세금계산서 발급사업자), 40-비영리법인, 국가기관</p>
                @foreach ($Result as $indexKey => $corpInfo)
                <fieldset class="fieldset2">
                    <legend>사업자등록상태조회 (휴폐업조회) 확인 [{{ $indexKey+1 }} / {{ count($Result) }}]</legend>
                    <ul>
                        <li>사업자번호 (corpNum) : {{ $corpInfo->corpNum }}</li>
                        <li>휴폐업상태 (state) : {{ $corpInfo->state }}</li>
                        <li>휴폐업일자 (stateDate) : {{ $corpInfo->stateDate }}</li>
                        <li>사업자유형 (taxType) : {{ $corpInfo->taxType }}</li>
                        <li>과세유형 전환일자 (typeDate) : {{ $corpInfo->typeDate }}</li>
                        <li>국세청 확일일자 (checkDate) : {{ $corpInfo->checkDate }}</li>
                    </ul>
                </fieldset>
                @endforeach
            </fieldset>
         </div>
    </body>
</html>
