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
          @foreach ($Result as $index => $object)
              @if ($object->emailType == "TAX_ISSUE_INVOICER")
                <li>[정발행] TAX_ISSUE_INVOICER (공급자에게 전자세금계산서 발행 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_CHECK")
                <li>[정발행] TAX_CHECK (공급자에게 전자세금계산서 수신확인 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_CANCEL_ISSUE")
                <li>[정발행] TAX_CANCEL_ISSUE (공급받는자에게 전자세금계산서 발행취소 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_REQUEST")
                <li>[역발행] TAX_REQUEST (공급자에게 세금계산서를 발행요청 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_CANCEL_REQUEST")
                <li>[역발행] TAX_CANCEL_REQUEST (공급받는자에게 세금계산서 취소 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_REFUSE")
                <li>[역발행] TAX_REFUSE (공급받는자에게 세금계산서 거부 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_REVERSE_ISSUE")
                <li>[역발행] TAX_REFUSE (공급받는자에게 세금계산서 발행 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_TRUST_ISSUE")
                <li>[위수탁발행] TAX_TRUST_ISSUE (공급받는자에게 전자세금계산서 발행 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_TRUST_ISSUE_TRUSTEE")
                <li>[위수탁발행] TAX_TRUST_ISSUE_TRUSTEE (수탁자에게 전자세금계산서 발행 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_TRUST_ISSUE_INVOICER")
                <li>[위수탁발행] TAX_TRUST_ISSUE_INVOICER (공급자에게 전자세금계산서 발행 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_TRUST_CANCEL_ISSUE")
                <li>[위수탁발행] TAX_TRUST_CANCEL_ISSUE (공급받는자에게 전자세금계산서 발행취소 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_TRUST_CALCEL_ISSUE_INVOICER")
                <li>[위수탁발행] TAX_TRUST_CALCEL_ISSUE_INVOICER (공급자에게 전자세금계산서 발행취소 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_CLOSEDOWN")
                <li>[처리결과] TAX_CLOSEDOWN (거래처의 휴폐업 여부 확인 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "TAX_NTSFAIL_INVOICER")
                <li>[처리결과] TAX_NTSFAIL_INVOICER (전자세금계산서 국세청 전송실패 안내 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @elseif ($object->emailType == "ETC_CERT_EXPIRATION")
                <li>[정기발송] ETC_CERT_EXPIRATION (팝빌에서 이용중인 공인인증서의 갱신 메일 전송 여부) : {{ $object->sendYN ? 'true' : 'false' }}</li>
              @endif
          @endforeach
          </ul>
      </fieldset>
     </div>
  </body>
</html>
