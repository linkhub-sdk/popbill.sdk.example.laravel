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
                    <legend>ASP 사업자 유통메일 목록 [{{$index+1}}]</legend>
                    <ul>
                    @foreach ($object as $key => $value)
                        <li> {{ $key }} : {{ $value }} </li>
                    @endforeach
                    </ul>
                </fieldset>
                @endforeach
            </fieldset>
         </div>
    </body>
</html>
