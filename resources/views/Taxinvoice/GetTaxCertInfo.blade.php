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
            <li>regDT (등록일시) : {{ $TaxinvoiceCertificate->regDT }}</li>
            <li>expireDT (만료일시) : {{ $TaxinvoiceCertificate->expireDT }}</li>
            <li>issuerDN (인증서 발급자 DN) : {{ $TaxinvoiceCertificate->issuerDN }}</li>
            <li>subjectDN (등록된 인증서 DN) : {{ $TaxinvoiceCertificate->subjectDN }}</li>
            <li>issuerName (인증서 종류) : {{ $TaxinvoiceCertificate->issuerName }}</li>
            <li>oid (OID) : {{ $TaxinvoiceCertificate->oid }}</li>
            <li>regContactName (등록 담당자 성명) : {{ $TaxinvoiceCertificate->regContactName }}</li>
            <li>regContactID (등록 담당자 아이디) : {{ $TaxinvoiceCertificate->regContactID }}</li>
        </ul>
      </fieldset>
     </div>
  </body>
</html>
