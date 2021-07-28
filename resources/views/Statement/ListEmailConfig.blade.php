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
            <ul>
              @if ($object->emailType == "SMT_ISSUE")
                <li>SMT_ISSUE (공급받는자에게 전자명세서가 발행 되었음을 알려주는 메일) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "SMT_ACCEPT")
                <li>SMT_ACCEPT (공급자에게 전자명세서가 승인 되었음을 알려주는 메일) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "SMT_DENY")
                <li>SMT_DENY (공공급자에게 전자명세서가 거부 되었음을 알려주는 메일) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "SMT_CANCEL")
                <li>SMT_CANCEL (공급받는자에게 전자명세서가 취소 되었음을 알려주는 메일) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "SMT_CANCEL_ISSUE")
                <li>SMT_CANCEL_ISSUE (공급받는자에게 전자명세서가 발행취소 되었음을 알려주는 메일) : {{ $object->sendYN ? 'true' : 'false' }}</li>

              @endif
            </ul>
          </fieldset>
          @endforeach
      </fieldset>
     </div>
  </body>
</html>
