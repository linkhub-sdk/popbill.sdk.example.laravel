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
        @foreach ($Result as $indexKey => $faxInfo)
        <fieldset class="fieldset2">
          <legend>팩스전송 결과정보 확인 [ {{ $indexKey+1 }} / {{count($Result)}} ]</legend>
  				<ul>
            <li> state (전송상태 코드) : {{ $faxInfo->state }} </li>
            <li> result (전송결과 코드) : {{ $faxInfo->result }} </li>
            <li> sendNum (발신번호) : {{ $faxInfo->sendNum }} </li>
            <li> senderName (발신자명) : {{ $faxInfo->senderName }} </li>
            <li> receiveNum (수신번호) : {{ $faxInfo->receiveNum }} </li>
            <li> receiveName (수신자명) : {{ $faxInfo->receiveName }} </li>
            <li> title (팩스제목) : {{ $faxInfo->title }} </li>
            <li> sendPageCnt (전체 페이지수) : {{ $faxInfo->sendPageCnt }} </li>
            <li> successPageCnt (성공 페이지수) : {{ $faxInfo->successPageCnt }} </li>
            <li> failPageCnt (실패 페이지수) : {{ $faxInfo->failPageCnt }} </li>
            <li> refundPageCnt (환불 페이지수) : {{ $faxInfo->refundPageCnt }} </li>
            <li> cancelPageCnt (취소 페이지수) : {{ $faxInfo->cancelPageCnt }} </li>
            <li> receiptDT (접수일시) : {{ $faxInfo->receiptDT }} </li>
            <li> reserveDT (예약일시) : {{ $faxInfo->reserveDT }} </li>
            <li> sendDT (전송일시) : {{ $faxInfo->sendDT }} </li>
            <li> resultDT (전송결과 수신일시) : {{ $faxInfo->resultDT }} </li>
            <li> fileNames (전송파일명 리스트) : {{ print_r($faxInfo->fileNames) }} </li>
            <li> receiptNum (접수번호) : {{ $faxInfo->receiptNum }} </li>
            <li> requestNum (요청번호) : {{ $faxInfo->requestNum }} </li>
            <li> chargePageCnt (과금 페이지수) : {{ $faxInfo->chargePageCnt }} </li>
            <li> tiffFileSize (변환파일용랑) : {{ $faxInfo->tiffFileSize }}byte</li>
  				</ul>
        </fieldset>
        @endforeach
			</fieldset>
		 </div>
	</body>
</html>
