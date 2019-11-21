
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="stylesheet" type="text/css" href="css/example.css" media="screen"/>

    <title>Popbill SDK PHP Laravel Example.</title>
</head>
<body>
<div id="content">
    <p class="heading1">Popbill e-Tax Invoice SDK PHP Laravel Example.</p>
    <br/>
    <fieldset class="fieldset1">
        <legend>Issue/File the e-Tax invoice</legend>
        <ul>
            <li><a href="Taxinvoice/CheckMgtKeyInUse">CheckMgtKeyInUse</a> - Checking the availability of e-Tax invoice id</li>
            <li><a href="Taxinvoice/RegistIssue">RegistIssue</a> - Regist and issue the e-Tax invoice</li>
            <li><a href="Taxinvoice/Register">Register</a> - Save the e-Tax invoice</li>
            <li><a href="Taxinvoice/Update">Update</a> - Modify the e-Tax invoice/li>
            <li><a href="Taxinvoice/Issue">Issue</a> - Issue the e-Tax invoice</li>
            <li><a href="Taxinvoice/CancelIssue">CancelIssue</a> - Cancel the issuance of e-Tax invoice</li>
            <li><a href="Taxinvoice/Delete">Delete</a> - Delete the e-Tax invoice</li>
            <li><a href="Taxinvoice/RegistRequest">RegistRequest</a> - [Requested e-Tax invoice issuance] Request for issuing the e-Tax invoice to invoicer(seller)</li>
            <li><a href="Taxinvoice/Request">Request</a> - Request for issuing saved e-Tax invoice to seller</li>
            <li><a href="Taxinvoice/CancelRequest">CancelRequest</a> - Cancel the issuance request</li>
            <li><a href="Taxinvoice/Refuse">Refuse</a> - Refuse to issue the e-Tax invoice</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>File the e-Tax invoice to NTS</legend>
        <ul>
            <li><a href="Taxinvoice/SendToNTS">SendToNTS</a> - File the e-Tax invoice to NTS</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>Check the information of e-Tax invoice</legend>
        <ul>
            <li><a href="Taxinvoice/GetInfo">GetInfo</a> - Check the status of the e-Tax invoice</li>
            <li><a href="Taxinvoice/GetInfos">GetInfos</a> - Check the status of the bulk e-Tax invoices</li>
            <li><a href="Taxinvoice/GetDetailInfo">GetDetailInfo</a> - Check the details of the e-Tax invoice</li>
            <li><a href="Taxinvoice/Search">Search</a> - Search the list of e-Tax voices</li>
            <li><a href="Taxinvoice/GetLogs">GetLogs</a> - Check the log of status change</li>
            <li><a href="Taxinvoice/GetURL">GetURL</a> - URL for e-Tax invoice list</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>View/Print the e-Tax invoice</legend>
        <ul>
            <li><a href="Taxinvoice/GetPopUpURL">GetPopUpURL</a> - URL to view the e-Tax invoice</li>
            <li><a href="Taxinvoice/GetViewURL">GetViewURL</a> - URL to view the e-Tax invoice</li>
            <li><a href="Taxinvoice/GetPrintURL">GetPrintURL</a> - URL to print the e-Tax invoice [For invoicer(seller)/invoicee(buyer)]</li>
            <li><a href="Taxinvoice/GetEPrintURL">GetEPrintURL</a> - URL to print the e-Tax invoice [For buyer]</li>
            <li><a href="Taxinvoice/GetMassPrintURL">GetMassPrintURL</a> - URL to print bulk e-Tax invoices</li>
            <li><a href="Taxinvoice/GetMailURL">GetMailURL</a> - URL for a mail link of the e-Tax invoice</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>Additional functions</legend>
        <ul>
            <li><a href="Taxinvoice/GetAccessURL">GetAccessURL</a> - URL to log-in</li>
            <li><a href="Taxinvoice/GetSealURL"> GetSealURL</a> - URL to register the seal and attachments</li>
            <li><a href="Taxinvoice/AttachFile">AttachFile</a> - Attach the file to e-Tax invoice</li>
            <li><a href="Taxinvoice/DeleteFile">DeleteFile</a> - Delete the attachment</li>
            <li><a href="Taxinvoice/GetFiles">GetFiles</a> - Check the attachment list</li>
            <li><a href="Taxinvoice/SendEmail">SendEmail</a> - Send a notification mail</li>
            <li><a href="Taxinvoice/SendSMS">SendSMS</a> - Send a SMS</li>
            <li><a href="Taxinvoice/SendFAX">SendFAX</a> - Send an e-Tax invoice by fax</li>
            <li><a href="Taxinvoice/AttachStatement">AttachStatement</a> - Attach a statement</li>
            <li><a href="Taxinvoice/DetachStatement">DetachStatement</a> - Detach a statement</li>
            <li><a href="Taxinvoice/GetEmailPublicKeys">GetEmailPublicKeys</a> - Check the email list of e-Tax invoice service provider</li>
            <li><a href="Taxinvoice/AssignMgtKey">AssignMgtKey</a> - Assign an invoice id</li>
            <li><a href="Taxinvoice/ListEmailConfig">ListEmailConfig</a> - Search an outgoing mail configuration</li>
            <li><a href="Taxinvoice/UpdateEmailConfig">UpdateEmailConfig</a> - Modify an outgoing mail list</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>Certificate Management</legend>
        <ul>
            <li><a href="Taxinvoice/GetTaxCertURL">GetTaxCertURL</a> - URL to register the certificate</li>
            <li><a href="Taxinvoice/GetCertificateExpireDate">GetCertificateExpireDate</a> - Check certificate’s expiration date</li>
            <li><a href="Taxinvoice/CheckCertValidation">CheckCertValidation</a> - Check certificate’s validity</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>Points Management</legend>
        <ul>
            <li><a href="Taxinvoice/GetBalance">GetBalance</a> - Check point’s balance</li>
            <li><a href="Taxinvoice/GetChargeURL">GetChargeURL</a> - URL to charge points</li>
            <li><a href="Taxinvoice/GetPartnerBalance">GetPartnerBalance</a> - Check point’s balance of partner</li>
            <li><a href="Taxinvoice/GetPartnerURL">GetPartnerURL</a> - URL to charge partner’s points</li>
            <li><a href="Taxinvoice/GetUnitCost">GetUnitCost</a> - Check the issuance unit cost per an e-Tax invoice</li>
            <li><a href="Taxinvoice/GetChargeInfo">GetChargeInfo</a> - Check the charging information</li>
        </ul>
    </fieldset>
    <fieldset class="fieldset1">
        <legend>Member’s Information</legend>
        <ul>
            <li><a href="Taxinvoice/CheckIsMember">CheckIsMember</a> - Check whether a user is a partner user or not</li>
            <li><a href="Taxinvoice/CheckID">CheckID</a> - Check whether an ID is in use or not</li>
            <li><a href="Taxinvoice/JoinMember">JoinMember</a> - Join the POPBILL as a partner user</li>
            <li><a href="Taxinvoice/GetCorpInfo">GetCorpInfo</a> - Check the company information</li>
            <li><a href="Taxinvoice/UpdateCorpInfo">UpdateCorpInfo</a> - Modify the company information</li>
            <li><a href="Taxinvoice/RegistContact">RegistContact</a> - Regist a contact of the person in charge</li>
            <li><a href="Taxinvoice/ListContact">ListContact</a> - Check the list of persons in charge</li>
            <li><a href="Taxinvoice/UpdateContact">UpdateContact</a> - Modify the contact information</li>
        </ul>
    </fieldset>
</div>
</body>
</html>
