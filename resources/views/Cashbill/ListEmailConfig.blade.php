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
              @if ($object->emailType == "CSH_ISSUE")
                <li>CSH_ISSUE (고객에게 현금영수증이 발행 되었음을 알려주는 메일 전송 여부) :
                  {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "CSH_CANCEL")
                <li>CSH_CANCEL (고객에게 현금영수증이 발행취소 되었음을 알려주는 메일 전송 여부) :
                  {{ $object->sendYN ? 'true' : 'false' }}</li>
              @endif
            </ul>
          </fieldset>
          @endforeach
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
