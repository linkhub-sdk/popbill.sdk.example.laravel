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
                @foreach ($Result as $index => $object)
                <fieldset class="fieldset2">
                    <legend>카카오톡채널 계정정보 [{{$index+1}}]</legend>
                    <ul>
                        <li> plusFriendID (카카오톡 검색용 아이디) : {{ $object->plusFriendID }} </li>
                        <li> plusFriendName (채널명) : {{ $object->plusFriendName }} </li>
                        <li> regDT (등록일시) : {{ $object->regDT }} </li>
                        <li> state (채널 상태) : {{ $object->state }} </li>
                        <li> stateDT (채널 상태 일시) : {{ $object->stateDT }} </li>
                    </ul>
                </fieldset>
                @endforeach
            </fieldset>
         </div>
    </body>
</html>
