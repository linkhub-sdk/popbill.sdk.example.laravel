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
                    <li>사업자번호 (corpNum) : {{ $Result->corpNum }}</li>
                    <li>법인번호 (companyRegNum) : {{ $Result->companyRegNum }}</li>
                    <li>확인일시 (checkDT) : {{ $Result->checkDT }}</li>
                    <li>상호 (corpName) : {{ $Result->corpName }}</li>
                    <li>기업형태코드 (corpCode) : {{ $Result->corpCode }}</li>
                    <li>기업규모코드 (corpScaleCode) : {{ $Result->corpScaleCode }}</li>
                    <li>개인법인코드 (personCorpCode) : {{ $Result->personCorpCode }}</li>
                    <li>본점지점코드 (headOfficeCode) : {{ $Result->headOfficeCode }}</li>
                    <li>산업코드 (industryCode) : {{ $Result->industryCode }}</li>
                    <li>설립구분코드 (establishCode) : {{ $Result->establishCode }}</li>
                    <li>설립일자 (establishDate) : {{ $Result->establishDate }}</li>
                    <li>대표자명 (CEOName) : {{ $Result->ceoname }}</li>
                    <li>사업장구분코드 (workPlaceCode) : {{ $Result->workPlaceCode }}</li>
                    <li>주소구분코드 (addrCode) : {{ $Result->addrCode }}</li>
                    <li>우편번호 (zipCode) : {{ $Result->zipCode }}</li>
                    <li>주소 (addr) : {{ $Result->addr }}</li>
                    <li>상세주소 (addrDetail) : {{ $Result->addrDetail }}</li>
                    <li>영문주소 (enAddr) : {{ $Result->enAddr }}</li>
                    <li>업종 (bizClass) : {{ $Result->bizClass }}</li>
                    <li>업태 (bizType) : {{ $Result->bizType }}</li>
                    <li>결과코드 (Result) : {{ $Result->result }}</li>
                    <li>결과메시지 (resultMessage) : {{ $Result->resultMessage }}</li>
                    <li>사업자과세유형 (closeDownTaxType) : {{ $Result->closeDownTaxType }}</li>
                    <li>과세유형전환일자 (closeDownTaxTypeDate) : {{ $Result->closeDownTaxTypeDate }}</li>
                    <li>휴폐업상태 (closeDownState) : {{ $Result->closeDownState }}</li>
                    <li>휴폐업일자 (closeDownStateDate) : {{ $Result->closeDownStateDate }}</li>
                </ul>
            </fieldset>
         </div>
    </body>
</html>
