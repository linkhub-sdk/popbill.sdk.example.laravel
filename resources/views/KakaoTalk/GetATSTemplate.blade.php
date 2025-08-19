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
                <fieldset class="fieldset2">
                    <legend>알림톡 템플릿 정보</legend>
                    <ul>

                        <li> templateCode (템플릿 코드) : {{ $Result->templateCode }} </li>
                        <li> templateName (템플릿 제목) : {{ $Result->templateName }} </li>
                        <li> template (템플릿 내용) : {{ $Result->template }} </li>
                        <li> plusFriendID (검색용 아이디) : {{ $Result->plusFriendID }} </li>
                        <li> ads (광고 메시지) : {{ $Result->ads }} </li>
                        <li> appendix (부가 메시지) : {{ $Result->appendix }} </li>
                        <li> secureYN (보안템플릿 여부) : {{ $Result->secureYN }} </li>
                        <li> state (템플릿 상태) : {{ $Result->state }} </li>
                        <li> stateDT (템플릿 상태 일시) : {{ $Result->stateDT }} </li>
                        @if (empty($Result->btns) == false)
                        @foreach ($Result->btns as $indexKey => $btnInfo)
                        <fieldset class="fieldset2">
                            <legend>버튼 정보 [{{ $indexKey+1 }}]</legend>
                            <ul>
                                <li>n (버튼명) : {{ $btnInfo->n }} </li>
                                <li>t (버튼유형) : {{ $btnInfo->t }} </li>
                                <li>u1 (버튼링크1) : {{ false == empty($btnInfo->u1) ? $btnInfo->u1 : ''}} </li>
                                <li>u2 (버튼링크2) : {{ false == empty($btnInfo->u2) ? $btnInfo->u2 : ''}} </li>
                            </ul>
                        </fieldset>
                        @endforeach
                        @endif
                    </ul>
                </fieldset>
            </fieldset>
        </div>
    </body>
</html>
