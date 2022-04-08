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
                    <li>id (아이디) : {{ $ContactInfo->id }}</li>
                    <li>personName (담당자 성명) : {{ $ContactInfo->personName }}</li>
                    <li>email (담당자 이메일) : {{ $ContactInfo->email }}</li>
                    <li>tel (담당자 연락처) : {{ $ContactInfo->tel }}</li>
                    <li>regDT (등록일시) : {{ $ContactInfo->regDT }}</li>
                    <li>searchRole (담당자 권한) : {{ $ContactInfo->searchRole }}</li>
                    <li>mgrYN (관리자 여부) : {{ $ContactInfo->mgrYN }}</li>
                    <li>state (상태) : {{ $ContactInfo->state }}</li>
                </ul>
            </fieldset>
        </div>
    </body>
</html>
