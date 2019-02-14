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
            <legend>발신번호 목록 [{{$index+1}}]</legend>
    				<ul>
                @foreach ($object as $key => $value)
                  @if ($key == 'number')
                    <li> {{ $key }} (발신번호) : {{ $value }} </li>
                  @elseif ($key == 'state')
                    <li> {{ $key }} (등록상태) : {{ $value }}</li>
                  @elseif ($key == 'representYN')
                    <li> {{ $key }} (대표번호 지정여부) : {{ $value ? 'true' : 'false' }}</li>
                  @endif
                @endforeach
            </ul>
          </fieldset>
          @endforeach
				</ul>
			</fieldset>
		 </div>
	</body>
</html>
