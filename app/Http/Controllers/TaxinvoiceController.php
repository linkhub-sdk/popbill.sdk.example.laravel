<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\LinkhubException;
use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;
use Linkhub\Popbill\PopbillException;

use Linkhub\Popbill\PopbillTaxinvoice;
use Linkhub\Popbill\TIENumMgtKeyType;
use Linkhub\Popbill\Taxinvoice;
use Linkhub\Popbill\TaxinvoiceDetail;
use Linkhub\Popbill\TaxinvoiceAddContact;


class TaxinvoiceController extends Controller
{
  public function __construct() {

    // HTTP Connction Library Mode, default(CURL)
    define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE'));

    // Tax invoice Service Initialize
    $this->PopbillTaxinvoice = new PopbillTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));

    // Service Test Mode On Off Configuration, true(Test-Mode), false(Real-Mode)
    $this->PopbillTaxinvoice->IsTest(config('popbill.IsTest'));

    // HTTP Authorization header IP Restrict On Off Configuration. recomanded(true)
    $this->PopbillTaxinvoice->IPRestrictOnOff(config('popbill.IPRestrictOnOff'));
  }

  // HTTP Get Request URI -> Controller's function mapping
  public function RouteHandelerFunc(Request $request){
    $APIName = $request->route('APIName');
    return $this->$APIName();
  }

  /**
   * Checking the availability of e-Tax invoice id
   */
  public function CheckMgtKeyInUse(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    try {
        $result = $this->PopbillTaxinvoice->CheckMgtKeyInUse($testCorpNum, $mgtKeyType, $mgtKey);
        $result ? $result = 'In Use' : $result = 'Not Use';
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "문서번호 사용여부 =>".$mgtKey."", 'value' => $result]);
  }


  /**
   * Regist and issue the e-Tax invoice
   */
  public function RegistIssue(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    // Invoice id assigned by partner
    $invoicerMgtKey = '20191024-023';

    // Whether you force to issue an overdued e-Tax invoice or not [true-Yes / false-No]
    $forceIssue = false;

    // Memo
    $memo = '즉시발행 메모';

    // A notification mail’s title sent to a person in charge of Buyer (If, you write nothing, a title set by POPBILL will be assigned)
    $emailSubject = '';

    // Whether you want to write a transaction details or not [true-Yes / false-No]
    $writeSpecification = false;

    // - writeSpecification’s value = true : invoice id of transaction details
    // - writeSpecification’s value = false : invoice id of e-Tax invoice
    $dealInvoiceMgtKey = '';

    /************************************************************
     *                        Tax invoice Info
     ************************************************************/
    // // e-Tax Invoice object
    $Taxinvoice = new Taxinvoice();

    // Date of trading, Date format(yyyyMMdd) e.g. 20180509
    $Taxinvoice->writeDate = '20191024';

    // Issuance type, Put a KOREAN word [ “정발행”(e-Tax invoice) or “역발행” (Requested e-Tax invoice) or “위수탁”(Delegated e-Tax invoice) ]
    $Taxinvoice->issueType = '정발행';

    // Charging direction
    // Put a KOREAN word[ “정과금(Charge to seller)” or “역과금(Charge to buyer” ]
    // (*Charge to buyer is only available in requested e-Tax invoice issuance process)
    $Taxinvoice->chargeDirection = '정과금';

    // Receipt/Charge, Put a KOREAN word [ “영수”(Receipt) or “청구”(Charge) ]
    $Taxinvoice->purposeType = '영수';

    // [Array] Taxation Type [T-Taxation / N-Exemption / Z-Zero-rate]
    $Taxinvoice->taxType = '과세';

    // Issuing Timing, Don't Change this field.
    $Taxinvoice->issueTiming = '직접발행';

    /************************************************************
     *                         Seller Info
     ************************************************************/
    // Seller’s business registration number ( 10 digits except ‘-’ )
    $Taxinvoice->invoicerCorpNum = $testCorpNum;

    // [Seller] Identification number for minor place of business
    $Taxinvoice->invoicerTaxRegID = '';

    // [Seller] Company name
    $Taxinvoice->invoicerCorpName = '공급자상호';

    // Seller invoice id (No redundancy)
    // - Combination of English letter, number, hyphen(‘-’) and underscore(‘_’)
    $Taxinvoice->invoicerMgtKey = $invoicerMgtKey;

    // [Seller] CEO’s name
    $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

    // [Seller] Company address
    $Taxinvoice->invoicerAddr = '공급자 주소';

     // [Seller] Business item
    $Taxinvoice->invoicerBizClass = '공급자 종목';

    // [Seller] Business type
    $Taxinvoice->invoicerBizType = '공급자 업태';

    // [Seller] Name of the person in charge
    $Taxinvoice->invoicerContactName = '공급자 담당자성명';

    // [Seller] Email address of the person in charge
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // [Seller] Telephone number of the person in charge
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // [Seller] Mobile number of the person in charge
    $Taxinvoice->invoicerHP = '010-111-222';

    // [Seller] Mobile number of the person in charge
    $Taxinvoice->invoicerSMSSendYN = false;

    /************************************************************
     *                      Buyer Info
     ************************************************************/

    // [Buyer] Buyer’s type, Put a KOREAN word [ “사업자(company)” or “개인(individual)” or “외국인(foreigner)” ]
    $Taxinvoice->invoiceeType = '사업자';

    // [Buyer] Business registration number
    $Taxinvoice->invoiceeCorpNum = '8888888888';

    // [Buyer] Identification number for minor place of business
    $Taxinvoice->invoiceeTaxRegID = '';

    // [Buyer] Company name
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

    // [Buyer] Invoice id
    $Taxinvoice->invoiceeMgtKey = '';

    // [Buyer] CEO’s name
    $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

    // [Buyer] Company address
    $Taxinvoice->invoiceeAddr = '공급받는자 주소';

    // [Buyer] Business type
    $Taxinvoice->invoiceeBizType = '공급받는자 업태';

    // [Buyer] Business item
    $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

    // [Buyer] Name of the person in charge
    $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

    // [Buyer] Email address of the person in charge
    $Taxinvoice->invoiceeEmail1 = 'test@test.com';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeTEL1 = '070-111-222';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeHP1 = '010-111-222';

    // The sum of supply cost, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->supplyCostTotal = '200000';

    // The sum of tax amount	, Only numbers and - (minus) are acceptable
    $Taxinvoice->taxTotal = '20000';

    // Total amount, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->totalAmount = '220000';

    // Serial number, One of e-Tax invoice’s items to manage document – ‘Serial number’
    $Taxinvoice->serialNum = '123';

    // Cash, One of e-Tax invoice’s items – ‘cash’
    $Taxinvoice->cash = '';

    // Check, One of e-Tax invoice’s items – ‘check’
    $Taxinvoice->chkBill = '';

    // Note, One of e-Tax invoice’s items – ‘note’
    $Taxinvoice->note = '';

    // Credit, One of e-Tax invoice’s litems – ‘credit’
    $Taxinvoice->credit = '';

    // Remark
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // Volume, One of e-Tax invoice’s items to manage document – ‘Volume’ for a book
    $Taxinvoice->kwon = '1';

    // Number, One of e-Tax invoice’s items to manage document – ‘Number’ for a book
    $Taxinvoice->ho = '1';

    // Attaching a business license, Prerequisite : Registration of a business license in advance
    $Taxinvoice->businessLicenseYN = false;

    // Attaching a copy of bankbook, Prerequisite : Registration of a copy of bankbook in advance
    $Taxinvoice->bankBookYN = false;


    /************************************************************
     *                       Detail List
     ************************************************************/
    $Taxinvoice->detailList = array();
    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[0]->serialNum = 1;				      // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[0]->purchaseDT = '20190226';	  // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[0]->itemName = '품목명1번';	  	// Item name
    $Taxinvoice->detailList[0]->spec = '';				      // Specification
    $Taxinvoice->detailList[0]->qty = '';					        // Quantity
    $Taxinvoice->detailList[0]->unitCost = '';		    // Unit cost
    $Taxinvoice->detailList[0]->supplyCost = '100000';		  // Supply Cost
    $Taxinvoice->detailList[0]->tax = '10000';				      // Tax amount
    $Taxinvoice->detailList[0]->remark = '';		    // Remark

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;				      // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[1]->purchaseDT = '20190226';	  // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[1]->itemName = '품목명2번';	  	// Item name
    $Taxinvoice->detailList[1]->spec = '';				      // Specification
    $Taxinvoice->detailList[1]->qty = '';					        // Quantity
    $Taxinvoice->detailList[1]->unitCost = '';		    // Unit cost
    $Taxinvoice->detailList[1]->supplyCost = '100000';		  // Supply Cost
    $Taxinvoice->detailList[1]->tax = '10000';				      // Tax amount
    $Taxinvoice->detailList[1]->remark = '';		    // Remark

    /************************************************************
     *                    TaxinvoiceAdd Buyer Email
     ************************************************************/
    $Taxinvoice->addContactList = array();
    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[0]->serialNum = 1;				        // Serial Number, Must write the number in a row starting from 1 (Maximum: 5)
    $Taxinvoice->addContactList[0]->email = 'test@test.com';	    // Email
    $Taxinvoice->addContactList[0]->contactName	= '팝빌담당자';		// Name of the person in charge

    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[1]->serialNum = 2;			        	// Serial Number, Must write the number in a row starting from 1 (Maximum: 5)
    $Taxinvoice->addContactList[1]->email = 'test@test.com';	    // Email
    $Taxinvoice->addContactList[1]->contactName	= '링크허브';		  // Name of the person in charge

    try {
        $result = $this->PopbillTaxinvoice->RegistIssue($testCorpNum, $Taxinvoice, $testUserID,
            $writeSpecification, $forceIssue, $memo, $emailSubject, $dealInvoiceMgtKey);
        $code = $result->code;
        $message = $result->message;
        $ntsConfirmNum = $result->ntsConfirmNum;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        $ntsConfirmNum = null;
    }

    return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
  }

  /**
  * Save the e-Tax invoice
  * - Save the e-Tax invoice before issuing. Saved e-Tax invoice isn’t filed to NTS. Only after calling function ‘Issue API’, it would be filed.
  */
  public function Register(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Invoice id assigned by partner
    $invoicerMgtKey = '20190226-024';

    /************************************************************
     *                        Taxinvoice Info
     ************************************************************/

    // e-Tax Invoice object
    $Taxinvoice = new Taxinvoice();

    // Date of trading, Date format(yyyyMMdd) e.g. 20180509
    $Taxinvoice->writeDate = '20190226';

    // Issuance type, Put a KOREAN word [ “정발행”(e-Tax invoice) or “역발행” (Requested e-Tax invoice) or “위수탁”(Delegated e-Tax invoice) ]
    $Taxinvoice->issueType = '정발행';

    // Charging direction
    // Put a KOREAN word[ “정과금(Charge to seller)” or “역과금(Charge to buyer” ]
    // (*Charge to buyer is only available in requested e-Tax invoice issuance process)
    $Taxinvoice->chargeDirection = '정과금';

    // Receipt/Charge, Put a KOREAN word [ “영수”(Receipt) or “청구”(Charge)
    $Taxinvoice->purposeType = '영수';

    // Taxation type, Put a KOREAN word [ “과세” (Taxation) or “영세” (Zero-rate) or “면세” (Exemption) ]
    $Taxinvoice->taxType = '과세';

    // Issuing Timing, Don't Change this field.
    $Taxinvoice->issueTiming = '직접발행';

    /************************************************************
     *                         Seller Info
     ************************************************************/

    // Seller’s business registration number ( 10 digits except ‘-’ )
    $Taxinvoice->invoicerCorpNum = $testCorpNum;

    // [Seller] Identification number for minor place of business
    $Taxinvoice->invoicerTaxRegID = '';

    // [Seller] Company name
    $Taxinvoice->invoicerCorpName = '공급자상호';

    // Seller invoice id (No redundancy)
    // - Combination of English letter, number, hyphen(‘-’) and underscore(‘_’)
    $Taxinvoice->invoicerMgtKey = $invoicerMgtKey;

    // [Seller] CEO’s name
    $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

    // [Seller] Company address
    $Taxinvoice->invoicerAddr = '공급자 주소';

     // [Seller] Business item
    $Taxinvoice->invoicerBizClass = '공급자 종목';

    // [Seller] Business type
    $Taxinvoice->invoicerBizType = '공급자 업태';

    // [Seller] Name of the person in charge
    $Taxinvoice->invoicerContactName = '공급자 담당자성명';

    // [Seller] Email address of the person in charge
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // [Seller] Telephone number of the person in charge
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // [Seller] Mobile number of the person in charge
    $Taxinvoice->invoicerHP = '010-0000-0000';

    // [Seller] Whether you send a notification SMS or not
    // - 공급받는자 주)담당자 휴대폰번호(invoiceeHP1)로 전송
    // - 전송시 포인트가 차감되며 전송실패하는 경우 포인트 환불처리
    $Taxinvoice->invoicerSMSSendYN = false;

    /************************************************************
     *                      공급받는자 정보
     ************************************************************/

    // [Buyer] Buyer’s type, Put a KOREAN word [ “사업자(company)” or “개인(individual)” or “외국인(foreigner)” ]
    $Taxinvoice->invoiceeType = '사업자';

    // [Buyer] Business registration number
    $Taxinvoice->invoiceeCorpNum = '8888888888';

    // [Buyer] Identification number for minor place of business
    $Taxinvoice->invoiceeTaxRegID = '';

    // [Buyer] Company name
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

    // [Buyer] Invoice id
    $Taxinvoice->invoiceeMgtKey = '';

    // [Buyer] CEO’s name
    $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

    // [Buyer] Company address
    $Taxinvoice->invoiceeAddr = '공급받는자 주소';

    // [Buyer] Business type
    $Taxinvoice->invoiceeBizType = '공급받는자 업태';

    // [Buyer] Business item
    $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

    // [Buyer] Name of the person in charge
    $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

    // [Buyer] Email address of the person in charge
    $Taxinvoice->invoiceeEmail1 = 'tester@test.com';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeTEL1 = '070-0000-0000';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeHP1 = '010-0000-0000';

    // [Buyer] Whether you send a notification SMS or not to Seller
    $Taxinvoice->invoiceeSMSSendYN = false;

    // The sum of supply cost, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->supplyCostTotal = '200000';

    // The sum of tax amount	, Only numbers and - (minus) are acceptable
    $Taxinvoice->taxTotal = '20000';

    // Total amount, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->totalAmount = '220000';

    // Serial number, One of e-Tax invoice’s items to manage document – ‘Serial number’
    $Taxinvoice->serialNum = '123';

    // Cash, One of e-Tax invoice’s items – ‘cash’
    $Taxinvoice->cash = '';

    // Check, One of e-Tax invoice’s items – ‘check’
    $Taxinvoice->chkBill = '';

    // Note, One of e-Tax invoice’s items – ‘note’
    $Taxinvoice->note = '';

    // Credit, One of e-Tax invoice’s litems – ‘credit’
    $Taxinvoice->credit = '';

    // Remark
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // Volume, One of e-Tax invoice’s items to manage document – ‘Volume’ for a book
    $Taxinvoice->kwon = '1';

    // Number, One of e-Tax invoice’s items to manage document – ‘Number’ for a book
    $Taxinvoice->ho = '1';

    // Attaching a business license, Prerequisite : Registration of a business license in advance
    $Taxinvoice->businessLicenseYN = false;

    // Attaching a copy of bankbook, Prerequisite : Registration of a copy of bankbook in advance
    $Taxinvoice->bankBookYN = false;

    /************************************************************
     *                       Detail List
     ************************************************************/
    $Taxinvoice->detailList = array();
    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[0]->serialNum = 1;				      // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[0]->purchaseDT = '20190101';	  // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[0]->itemName = '품목명1번';	  	// Item name
    $Taxinvoice->detailList[0]->spec = '';				      // Specification
    $Taxinvoice->detailList[0]->qty = '';					        // Quantity
    $Taxinvoice->detailList[0]->unitCost = '';		    // Unit cost
    $Taxinvoice->detailList[0]->supplyCost = '100000';		  // Supply Cost
    $Taxinvoice->detailList[0]->tax = '10000';				      // Tax amount
    $Taxinvoice->detailList[0]->remark = '';		    // Remark

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;				      // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[1]->purchaseDT = '20190101';	  // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[1]->itemName = '품목명2번';	  	// Item name
    $Taxinvoice->detailList[1]->spec = '';				      // Specification
    $Taxinvoice->detailList[1]->qty = '';					        // Quantity
    $Taxinvoice->detailList[1]->unitCost = '';		    // Unit cost
    $Taxinvoice->detailList[1]->supplyCost = '100000';		  // Supply Cost
    $Taxinvoice->detailList[1]->tax = '10000';				      // Tax amount
    $Taxinvoice->detailList[1]->remark = '';		    // Remark

    /************************************************************
     *                      TaxinvoiceAdd Buyer Email
     ************************************************************/
    $Taxinvoice->addContactList = array();
    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[0]->serialNum = 1;				        // Serial Number, Must write the number in a row starting from 1 (Maximum: 5)
    $Taxinvoice->addContactList[0]->email = 'test@test.com';	    // Email
    $Taxinvoice->addContactList[0]->contactName	= '팝빌담당자';		// Name of the person in charge

    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[1]->serialNum = 2;			        	// Serial Number, Must write the number in a row starting from 1 (Maximum: 5)
    $Taxinvoice->addContactList[1]->email = 'test@test.com';	    // Email
    $Taxinvoice->addContactList[1]->contactName	= '링크허브';		  // Name of the person in charge

    // Whether you want to write a transaction details or not [true-Yes / false-No]
    $writeSpecification = false;

    try {
        $result = $this->PopbillTaxinvoice->Register($testCorpNum, $Taxinvoice);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Modify the e-Tax invoice. It is only available to saved e-Tax invoice.
   */
  public function Update(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-002';

    /************************************************************
     *                        세금계산서 정보
     ************************************************************/

    // e-Tax Invoice object
    $Taxinvoice = new Taxinvoice();

    // Date of trading, Date format(yyyyMMdd) e.g. 20180509
    $Taxinvoice->writeDate = '20190213';

    // Issuance type, Put a KOREAN word [ “정발행”(e-Tax invoice) or “역발행” (Requested e-Tax invoice) or “위수탁”(Delegated e-Tax invoice) ]
    $Taxinvoice->issueType = '정발행';

    // Charging direction
    // Put a KOREAN word[ “정과금(Charge to seller)” or “역과금(Charge to buyer” ]
    // (*Charge to buyer is only available in requested e-Tax invoice issuance process)
    $Taxinvoice->chargeDirection = '정과금';

    // Receipt/Charge, Put a KOREAN word [ “영수”(Receipt) or “청구”(Charge) ]
    $Taxinvoice->purposeType = '영수';

    // Taxation type, Put a KOREAN word [ “과세” (Taxation) or “영세” (Zero-rate) or “면세” (Exemption) ]
    $Taxinvoice->taxType = '과세';

    // Issuing Timing, Don't Change this field.
    $Taxinvoice->issueTiming = '직접발행';

    /************************************************************
     *                         Seller Info
     ************************************************************/

    // Seller’s business registration number ( 10 digits except ‘-’ )
    $Taxinvoice->invoicerCorpNum = $testCorpNum;

    // [Seller] Identification number for minor place of business
    $Taxinvoice->invoicerTaxRegID = '';

    // [Seller] Company name
    $Taxinvoice->invoicerCorpName = '공급자상호_수정';

    // Seller invoice id (No redundancy)
    // - Combination of English letter, number, hyphen(‘-’) and underscore(‘_’)
    $Taxinvoice->invoicerMgtKey = $mgtKey;

    // [Seller] CEO’s name
    $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

    // [Seller] Company address
    $Taxinvoice->invoicerAddr = '공급자 주소';

     // [Seller] Business item
    $Taxinvoice->invoicerBizClass = '공급자 종목';

    // [Seller] Business type
    $Taxinvoice->invoicerBizType = '공급자 업태';

    // [Seller] Name of the person in charge
    $Taxinvoice->invoicerContactName = '공급자 담당자성명';

    // [Seller] Email address of the person in charge
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // [Seller] Telephone number of the person in charge
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // [Seller] Mobile number of the person in charge
    $Taxinvoice->invoicerHP = '010-0000-0000';

    // [Seller] Whether you send a notification SMS or not
    $Taxinvoice->invoicerSMSSendYN = false;

    /************************************************************
     *                     Buyer Info
     ************************************************************/

    // [Buyer] Buyer’s type, Put a KOREAN word [ “사업자(company)” or “개인(individual)” or “외국인(foreigner)” ]
    $Taxinvoice->invoiceeType = '사업자';

    // [Buyer] Business registration number
    $Taxinvoice->invoiceeCorpNum = '8888888888';

    // [Buyer] Identification number for minor place of business
    $Taxinvoice->invoiceeTaxRegID = '';

    // [Buyer] Company name
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호_수정';

    // [Buyer] Invoice id
    $Taxinvoice->invoiceeMgtKey = '';

    // [Buyer] CEO’s name
    $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

    // [Buyer] Company address
    $Taxinvoice->invoiceeAddr = '공급받는자 주소';

    // [Buyer] Business type
    $Taxinvoice->invoiceeBizType = '공급받는자 업태';

    // [Buyer] Business item
    $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

    // [Buyer] Name of the person in charge
    $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

    // [Buyer] Email address of the person in charge
    $Taxinvoice->invoiceeEmail1 = 'tester@test.com';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeTEL1 = '070-0000-0000';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeHP1 = '010-0000-0000';

    // [Buyer] Whether you send a notification SMS or not to Seller
    $Taxinvoice->invoiceeSMSSendYN = false;

    // The sum of supply cost, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->supplyCostTotal = '200000';

    // The sum of tax amount	, Only numbers and - (minus) are acceptable
    $Taxinvoice->taxTotal = '20000';

    // Total amount, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->totalAmount = '220000';

    // Serial number, One of e-Tax invoice’s items to manage document – ‘Serial number’
    $Taxinvoice->serialNum = '123';

    // Cash, One of e-Tax invoice’s items – ‘cash’
    $Taxinvoice->cash = '';

    // Check, One of e-Tax invoice’s items – ‘check’
    $Taxinvoice->chkBill = '';

    // Note, One of e-Tax invoice’s items – ‘note’
    $Taxinvoice->note = '';

    // Credit, One of e-Tax invoice’s litems – ‘credit’
    $Taxinvoice->credit = '';

    // Remark
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // Volume, One of e-Tax invoice’s items to manage document – ‘Volume’ for a book
    $Taxinvoice->kwon = '1';

    // Number, One of e-Tax invoice’s items to manage document – ‘Number’ for a book
    $Taxinvoice->ho = '1';

    // Attaching a business license, Prerequisite : Registration of a business license in advance
    $Taxinvoice->businessLicenseYN = false;

    // Attaching a copy of bankbook, Prerequisite : Registration of a copy of bankbook in advance
    $Taxinvoice->bankBookYN = false;

    /************************************************************
     *                       Detail List
     ************************************************************/

    $Taxinvoice->detailList = array();
    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[0]->serialNum = 1;				      // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[0]->purchaseDT = '20190101';	  // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[0]->itemName = '품목명1번';	  	// Item name
    $Taxinvoice->detailList[0]->spec = '';				      // Specification
    $Taxinvoice->detailList[0]->qty = '';					        // Quantity
    $Taxinvoice->detailList[0]->unitCost = '';		    // Unit cost
    $Taxinvoice->detailList[0]->supplyCost = '100000';		  // Supply Cost
    $Taxinvoice->detailList[0]->tax = '10000';				      // Tax amount
    $Taxinvoice->detailList[0]->remark = '';		    // Remark

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;				      // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[1]->purchaseDT = '20190101';	  // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[1]->itemName = '품목명2번';	  	// Item name
    $Taxinvoice->detailList[1]->spec = '';				      // Specification
    $Taxinvoice->detailList[1]->qty = '';					        // Quantity
    $Taxinvoice->detailList[1]->unitCost = '';		    // Unit cost
    $Taxinvoice->detailList[1]->supplyCost = '100000';		  // Supply Cost
    $Taxinvoice->detailList[1]->tax = '10000';				      // Tax amount
    $Taxinvoice->detailList[1]->remark = '';		    // Remark

    /************************************************************
     *                      TaxinvoiceAdd Buyer Email
     ************************************************************/
    $Taxinvoice->addContactList = array();
    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[0]->serialNum = 1;				        // Serial Number, Must write the number in a row starting from 1 (Maximum: 5)
    $Taxinvoice->addContactList[0]->email = 'test@test.com';	    // Email
    $Taxinvoice->addContactList[0]->contactName	= '팝빌담당자';		// Name of the person in charge

    $Taxinvoice->addContactList[] = new TaxinvoiceAddContact();
    $Taxinvoice->addContactList[1]->serialNum = 2;			        	// Serial Number, Must write the number in a row starting from 1 (Maximum: 5)
    $Taxinvoice->addContactList[1]->email = 'test@test.com';	    // Email
    $Taxinvoice->addContactList[1]->contactName	= '링크허브';		  // Name of the person in charge

    try {
      $result = $this->PopbillTaxinvoice->Update($testCorpNum, $mgtKeyType, $mgtKey, $Taxinvoice);
      $code = $result->code;
      $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   *  Issue saved e-Tax invoice or e-Tax invoice waiting for issuing by invoicer(seller) after invoicee(buyer)’s request.
   * -  When it is called, points(fee) is deducted and notification mail will be sent to e-mail address of a person in charge of buyer (Variable: invoiceeEmail1) based on e-Tax invoice’s information.
   */
  public function Issue(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;


    $mgtKey = '20190226-024';

    // Memo
    $memo = '발행 메모입니다';

    // Whether you force to issue an overdued e-Tax invoice or not [true-Yes / false-No]
    $forceIssue = false;

    // A notification mail’s title sent to a person in charge of Buyer (If, you write nothing, a title set by POPBILL will be assigned)
    $EmailSubject = null;

    try {
        $result = $this->PopbillTaxinvoice->Issue($testCorpNum, $mgtKeyType, $mgtKey, $memo, $EmailSubject, $forceIssue);
        $code = $result->code;
        $message = $result->message;
        $ntsConfirmNum = $result->ntsConfirmNum;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        $ntsConfirmNum = null;
    }

    return view('PResponse', ['code' => $code, 'message' => $message, 'ntsConfirmNum' => $ntsConfirmNum]);
  }

  /**
   * Cancel the issuance of e-Tax invoice in the state of ‘Issuance complete’ waiting for filing to NTS.
   * - Cancelled e-Tax invoice isn’t filed to NTS.
   * - If you delete cancelled e-Tax invoice calling function ‘Delete API’, invoice id that have been assigned to manage e-Tax invoice is going to be re-usable.
   * - An e-Tax invoice in the state of ‘filing’ or ‘Succeed’ isn’t available to cancel.
   */
  public function CancelIssue(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-002';

    // Memo
    $memo = '발행 취소메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->CancelIssue($testCorpNum, $mgtKeyType, $mgtKey, $memo);
        $code = $result->code;
        $message = $result->message;
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
  * Delete the e-Tax invoice only in deletable status. An invoice id that have been assigned to it is going to be re-usable.
  * - The deletable status: ‘Saved’, ‘Canceled the issuance’, ‘Refused the issuance(By seller)’, ‘Canceled the issuance request(By buyer)’, ‘Failed to file’
  */
  public function Delete(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Invoice id assigned by partner
    $mgtKey = '20190213-002';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    try {
        $result = $this->PopbillTaxinvoice->Delete($testCorpNum, $mgtKeyType, $mgtKey);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
  * Invoicee(Buyer) request for issuing e-Tax invoice to seller just after filling out it themselves
  * - If buyer use it successfully, a status of e-Tax invoice is going to be changed to ‘waiting for issuing’. When seller check this e-Tax invoice and issue, points(fee) is deducted and the status is changed to ‘Issuance complete’.
  * - An e-Tax invoice in the state of ‘waiting for issuing’ isn’t going to be filed to NTS. To complete the process, seller must issue it making an e-sign with certificate.
  * - Depends on value of ‘ChargeDirection’ in a list of e-Tax invoice, a fee will be charged. If the value is ‘to seller’, points(fee) is deducted from seller’s. If the value is ‘to buyer’, points is deducted from buyer’s
 */
  public function RegistRequest(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    // Invoice id assigned by partner
    $invoiceeMgtKey = '20190213-005';

    /************************************************************
     *                        Tax Invoice Info
     ************************************************************/

    // e-Tax Invoice object
    $Taxinvoice = new Taxinvoice();

    // Date of trading, Date format(yyyyMMdd) e.g. 20180509
    $Taxinvoice->writeDate = '20190101';

    // Issuance type, Put a KOREAN word [ “정발행”(e-Tax invoice) or “역발행” (Requested e-Tax invoice) or “위수탁”(Delegated e-Tax invoice) ]
    $Taxinvoice->issueType = '역발행';

    // Charging direction
    // Put a KOREAN word[ “정과금(Charge to seller)” or “역과금(Charge to buyer” ]
    // (*Charge to buyer is only available in requested e-Tax invoice issuance process)
    $Taxinvoice->chargeDirection = '정과금';

    // Receipt/Charge, Put a KOREAN word [ “영수”(Receipt) or “청구”(Charge) ]
    $Taxinvoice->purposeType = '영수';

    // Taxation type, Put a KOREAN word [ “과세” (Taxation) or “영세” (Zero-rate) or “면세” (Exemption) ]
    $Taxinvoice->taxType = '과세';

    // Issuing Timing, Don't Change this field.
    $Taxinvoice->issueTiming = '직접발행';

    /************************************************************
     *                         Seller Info
     ************************************************************/

    // Seller’s business registration number ( 10 digits except ‘-’ )
    $Taxinvoice->invoicerCorpNum = '8888888888';

    // [Seller] Identification number for minor place of business
    $Taxinvoice->invoicerTaxRegID = '';

    // [Seller] Company name
    $Taxinvoice->invoicerCorpName = '공급자상호';

    // Seller invoice id (No redundancy)
    // - Combination of English letter, number, hyphen(‘-’) and underscore(‘_’)
    $Taxinvoice->invoicerMgtKey = '';

    // [Seller] CEO’s name
    $Taxinvoice->invoicerCEOName = '공급자 대표자성명';

    // [Seller] Company address
    $Taxinvoice->invoicerAddr = '공급자 주소';

     // [Seller] Business item
    $Taxinvoice->invoicerBizClass = '공급자 종목';

    // [Seller] Business type
    $Taxinvoice->invoicerBizType = '공급자 업태';

    // [Seller] Name of the person in charge
    $Taxinvoice->invoicerContactName = '공급자 담당자성명';

    // [Seller] Email address of the person in charge
    $Taxinvoice->invoicerEmail = 'tester@test.com';

    // [Seller] Telephone number of the person in charge
    $Taxinvoice->invoicerTEL = '070-4304-2991';

    // [Seller] Mobile number of the person in charge
    $Taxinvoice->invoicerHP = '010-111-222';

    /************************************************************
     *                      공급받는자 정보
     ************************************************************/

    // [Buyer] Buyer’s type, Put a KOREAN word [ “사업자(company)” or “개인(individual)” or “외국인(foreigner)” ]
    $Taxinvoice->invoiceeType = '사업자';

    // [Buyer] Business registration number
    $Taxinvoice->invoiceeCorpNum = $testCorpNum;

    // [Buyer] Identification number for minor place of business
    $Taxinvoice->invoiceeTaxRegID = '';

    // [Buyer] Company name
    $Taxinvoice->invoiceeCorpName = '공급받는자 상호';

    // [Buyer] Invoice id
    $Taxinvoice->invoiceeMgtKey = $invoiceeMgtKey;

    // [Buyer] CEO’s name
    $Taxinvoice->invoiceeCEOName = '공급받는자 대표자성명';

    // [Buyer] Company address
    $Taxinvoice->invoiceeAddr = '공급받는자 주소';

    // [Buyer] Business type
    $Taxinvoice->invoiceeBizType = '공급받는자 업태';

    // [Buyer] Business item
    $Taxinvoice->invoiceeBizClass = '공급받는자 종목';

    // [Buyer] Name of the person in charge
    $Taxinvoice->invoiceeContactName1 = '공급받는자 담당자성명';

    // [Buyer] Email address of the person in charge
    $Taxinvoice->invoiceeEmail1 = 'test@test.com';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeTEL1 = '070-111-222';

    // [Buyer] Telephone number of the person in charge
    $Taxinvoice->invoiceeHP1 = '010-111-222';

    // [Buyer] Whether you send a notification SMS or not to Seller
    $Taxinvoice->invoiceeSMSSendYN = false;

    // The sum of supply cost, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->supplyCostTotal = '200000';

    // The sum of tax amount	, Only numbers and - (minus) are acceptable
    $Taxinvoice->taxTotal = '20000';

    // Total amount, Only numbers and – (hyphen) are acceptable
    $Taxinvoice->totalAmount = '220000';

    // Serial number, One of e-Tax invoice’s items to manage document – ‘Serial number’
    $Taxinvoice->serialNum = '';

    // Cash, One of e-Tax invoice’s items – ‘cash’
    $Taxinvoice->cash = '';

    // Check, One of e-Tax invoice’s items – ‘check’
    $Taxinvoice->chkBill = '';

    // Note, One of e-Tax invoice’s items – ‘note’
    $Taxinvoice->note = '';

    // Credit, One of e-Tax invoice’s litems – ‘credit’
    $Taxinvoice->credit = '';

    // Remark
    $Taxinvoice->remark1 = '비고1';
    $Taxinvoice->remark2 = '비고2';
    $Taxinvoice->remark3 = '비고3';

    // Volume, One of e-Tax invoice’s items to manage document – ‘Volume’ for a book
    // 미기재시 $Taxinvoice->kwon = 'null';
    $Taxinvoice->kwon = '1';

    // Number, One of e-Tax invoice’s items to manage document – ‘Number’ for a book
    // 미기재시 $Taxinvoice->ho = 'null';
    $Taxinvoice->ho = '1';

    // Attaching a business license, Prerequisite : Registration of a business license in advance
    $Taxinvoice->businessLicenseYN = false;

    // Attaching a copy of bankbook, Prerequisite : Registration of a copy of bankbook in advance
    $Taxinvoice->bankBookYN = false;

    /************************************************************
     *                       Detail List
     ************************************************************/
    $Taxinvoice->detailList = array();
    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[0]->serialNum = 1;               // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[0]->purchaseDT = '20190101';     // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[0]->itemName = '품목명1번';        // Item name
    $Taxinvoice->detailList[0]->spec = '';                   // Specification
    $Taxinvoice->detailList[0]->qty = '';                    // Quantity
    $Taxinvoice->detailList[0]->unitCost = '';               // Unit cost
    $Taxinvoice->detailList[0]->supplyCost = '100000';       // Supply Cost
    $Taxinvoice->detailList[0]->tax = '10000';               // Tax amount
    $Taxinvoice->detailList[0]->remark = '';                 // Remark

    $Taxinvoice->detailList[] = new TaxinvoiceDetail();
    $Taxinvoice->detailList[1]->serialNum = 2;               // Serial Number, Must write the number in a row starting from 1 (Maximum: 99)
    $Taxinvoice->detailList[1]->purchaseDT = '20190101';     // Date of trading, Date of trading (of an e-Tax invoice) [ Format : yyyyMMdd, except ‘-’ ]
    $Taxinvoice->detailList[1]->itemName = '품목명1번';        // Item name
    $Taxinvoice->detailList[1]->spec = '';                   // Specification
    $Taxinvoice->detailList[1]->qty = '';                    // Quantity
    $Taxinvoice->detailList[1]->unitCost = '';               // Unit cost
    $Taxinvoice->detailList[1]->supplyCost = '100000';       // Supply Cost
    $Taxinvoice->detailList[1]->tax = '10000';               // Tax amount
    $Taxinvoice->detailList[1]->remark = '';                 // Remark

    // Memo
    $memo = '즉시요청 메모';

    try {
        $result = $this->PopbillTaxinvoice->RegistRequest($testCorpNum, $Taxinvoice, $memo, $testUserID);
        $code = $result->code;
        $message = $result->message;
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }
    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
  * Buyer request for issuing saved e-Tax invoice to Seller.
  * - We recommend usage of function ‘RegistRequest API’ to make it available to process ‘Regist API’ and ‘Request API’ at once.
 */
  public function Request(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::BUY;

    // Invoice id assigned by partner
    $mgtKey = '20190101-002';

    // Memo
    $memo = '역발행 요청 메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->Request($testCorpNum, $mgtKeyType, $mgtKey, $memo);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Buyer cancel to request for issuing an e-Tax invoice to seller.
   */
  public function CancelRequest(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::BUY;

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    // Memo
    $memo = '역발행 요청 취소메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->CanCelRequest($testCorpNum, $mgtKeyType, $mgtKey, $memo);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }
  /**
   * Seller refuse to issue the e-Tax invoice requested by buyer.
   */
  public function Refuse(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-002';

    // Memo
    $memo = '역)발행 요청 거부메모입니다';

    try {
        $result = $this->PopbillTaxinvoice->Refuse($testCorpNum, $mgtKeyType, $mgtKey, $memo);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }
    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
  * Seller files issued e-Tax invoice waiting for filing to NTS.
  * After calling function ‘SendToNTS’, you can check the file result withing 20-30 min.
  *ㆍ In Test-bed, issued e-Tax invoice isn’t filed to NTS actually. Process ‘File to NTS’ is a kind of mock process so only file result is going to be changed to ‘Success’ after 5min from issuing.
  */
  public function SendToNTS(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-005';

    try {
        $result = $this->PopbillTaxinvoice->SendToNTS($testCorpNum, $mgtKeyType, $mgtKey);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Check the status of AN e-Tax invoice and summary of it.
   */
  public function GetInfo(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    try {
        $result = $this->PopbillTaxinvoice->GetInfo($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/GetInfo', ['TaxinvoiceInfo' => [$result] ] );
  }

  /**
   * Check the status of bulk e-Tax invoices and summary of it. (Maximum: 1000 counts)
   */
  public function GetInfos(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice Array id assigned by partner, Maximum 1,000
    $MgtKeyList = array();
    array_push($MgtKeyList, "20190101-001");
    array_push($MgtKeyList, '20190101-002');
    array_push($MgtKeyList, '20190101-003');

    try {
        $result = $this->PopbillTaxinvoice->GetInfos($testCorpNum, $mgtKeyType, $MgtKeyList);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code= $pe->getCode();
        $message= $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('Taxinvoice/GetInfo', ['TaxinvoiceInfo' => $result] );
  }

  /**
   * Check the details of AN e-Tax invoice.
   */
  public function GetDetailInfo(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    try {
        $result = $this->PopbillTaxinvoice->GetDetailInfo($testCorpNum, $mgtKeyType, $mgtKey);
    } catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/GetDetailInfo', ['Taxinvoice' => $result] );
  }

  /**
   * Search the list of e-Tax invoices corresponding to the search criteria.
   */
  public function Search(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Date type [R-Date of registration, W-of trading, I-of issuance]
    $DType = 'W';

    // Start date of search scope (Format : yyyyMMdd)
    $SDate = '20181201';

    // End date of search scope (Format : yyyyMMdd)
    $EDate = '20190101';

    // [Array] Status code (Wild card(*) can be put on 2nd, 3rd letter – e.g. “3**”, “6**”)
    $State = array (
        '3**',
        '6**'
    );

    // [Array] Document type [N-General/ M-For modification]
    $Type = array (
        'N',
        'M'
    );

    // [Array] Taxation Type [T-Taxation / N-Exemption / Z-Zero-rate]
    $TaxType = array (
        'T',
        'N',
        'Z'
    );

    // [Array] Issuance type [N-e-Tax invoice/ R-Requested e-Tax invoice / T-Delegated e-Tax invoice]
    $IssueType = array (
        'N',
        'R',
        'T'
    );

    // Whether an issuing is delayed or not [CHOOSE 1 : null-Search all / false-Search the case issued within a due-date / true-the case issued after a due-date]
    $LateOnly = 0;

    // Whether the business registration number of minor place is enrolled or not [CHOOSE 1 : blank-Search all / 0-None / 1-Enrolled]
    $TaxRegIDYN = "";

    // Type of the business registration number of minor place [CHOOSE 1 : S-Invoicer(Seller) / B-Invoicee(Buyer) / T-Trustee]
    $TaxRegIDType = "S";

    // The business registration number of minor place: several search criteria of it must be recognized by comma(“,”) (e.g. 1234, 1110)
    $TaxRegID = "";

    // Page number (Default:’1’)
    $Page = 1;

    // Available search number per page (Default: 500 / Maximum: 1000)
    $PerPage = 5;

    // Sort direction (Default:’D’) [CHOOSE 1 : D-Descending / A-Ascending]
    $Order = 'D';

    // Search for a company name or business registration number (Blank:Search all)
    $QString = '';

    // Whether a document registered by API is searched or not [CHOOSE 1 : blank-Search all / 0-Search for docu registered by Site UI / 1 – for docu registered by API]
    $InterOPYN = '';

    try {
        $result = $this->PopbillTaxinvoice->Search($testCorpNum, $mgtKeyType, $DType, $SDate,
            $EDate, $State, $Type, $TaxType, $LateOnly, $Page, $PerPage, $Order,
            $TaxRegIDType, $TaxRegIDYN, $TaxRegID, $QString, $InterOPYN, $testUserID, $IssueType);
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/Search', ['Result' => $result] );
  }

  /**
   * Check the log of e-Tax invoice’s status change.
   */
  public function GetLogs(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    try {
        $result = $this->PopbillTaxinvoice->GetLogs($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetLogs', ['Result' => $result] );
  }

  /**
   * Return the URL to access the menu ‘e-Tax invoice list’ on POPBILL website in login status.
  * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    // TBOX(Draft list), SBOX(Sales list), PBOX(Purchase list), WRITE(Register sales invoice)
    $TOGO = 'TBOX';

    try {
        $url = $this->PopbillTaxinvoice->GetURL($testCorpNum, $testUserID, $TOGO);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "GetURL" , 'value' => $url]);
  }

  /**
   * Return popup URL to view AN e-Tax invoice on POPBILL website.
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetPopUpURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetPopUpURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "세금계산서 보기 팝업 URL" , 'value' => $url]);

  }

  /**
   * Return popup URL to view AN e-Tax invoice.
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetViewURL(){

     Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetViewURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "세금계산서 보기 팝업 URL (메뉴/버튼 제외)" , 'value' => $url]);

  }

  /**
   * Return popup URL to print AN e-Tax invoice.
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetPrintURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetPrintURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "세금계산서 인쇄 팝업 URL" , 'value' => $url]);
  }

  /**
  * Return popup URL to print AN e-Tax invoice for buyer.
  * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetEPrintURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetEPrintURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "세금계산서 인쇄(공급받는자용) 팝업 URL" , 'value' => $url]);
  }

  /**
   * Return popup URL to print bulk e-Tax invoices.
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetMassPrintURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner 배열 최대 100건
    $MgtKeyList = array(
        '20190213-001',
        '20190101-001',
        '20190101-002',
    );
    try {
        $url = $this->PopbillTaxinvoice->GetMassPrintURL($testCorpNum, $mgtKeyType, $MgtKeyList);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "GetMassPrintURL" , 'value' => $url]);
  }

  /**
   * Return URL of button located on bottom of a notification mail sent to buyer.
   * - There is no valid time about the URL returned by function ‘GetMailURL’.
  */
  public function GetMailURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-001';

    try {
        $url = $this->PopbillTaxinvoice->GetMailURL($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "공급받는자 세금계산서 메일링크 URL" , 'value' => $url]);
  }

  /**
   *  Return popup URL to access POPBILL website in login status.
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetAccessURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillTaxinvoice->GetAccessURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "팝빌 로그인 URL" , 'value' => $url]);

  }

  /**
   *  Return popup URL to register the seal, business license and copy of a bankbook to e-Tax invoice.
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
   */
  public function GetSealURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillTaxinvoice->GetSealURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "인감 및 첨부문서 등록 URL" , 'value' => $url]);
  }

  /**
  * Attach the file to e-Tax invoice (Maximum: 5 files).
  * - It is only available to saved e-Tax invoice.*/
  public function AttachFile(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-004';

    // Path of attached file
    $filePath = '/Users/John/Desktop/03A4C36315C047B4A171CEF283ED9A40.jpg';

    try {
        $result = $this->PopbillTaxinvoice->AttachFile($testCorpNum, $mgtKeyType, $mgtKey, $filePath);
        $code = $result->code;
        $message = $result->message;
    }
    catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Delete a file attached to e-Tax invoice.
   * - Check ‘FileID’ to recognize the attachment referring returned function GetFiles
  */
  public function DeleteFile(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-004';

    // File ID (*Refer field ‘attachedFile’ – one of returned values of GetFiles API
    $FileID = '0D583984-FDF3-4189-B61A-40942A2D834B.PBF';

    try {
        $result = $this->PopbillTaxinvoice->DeleteFile($testCorpNum, $mgtKeyType, $mgtKey, $FileID);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Check the file list attached to an e-Tax invoice.
   */
  public function GetFiles(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-004';

    try {
        $result = $this->PopbillTaxinvoice->GetFiles($testCorpNum, $mgtKeyType, $mgtKey);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('GetFiles', ['Result' => $result] );
  }

  /**
   * Re-send a mail to notify that an e-Tax invoice is issued.
   */
  public function SendEmail(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-004';

    // [Buyer] Email address of the person in charge
    $receiver = 'test@test.com';

    try {
        $result = $this->PopbillTaxinvoice->SendEmail($testCorpNum, $mgtKeyType, $mgtKey, $receiver);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Send a SMS(Short message).
   * - Contents only within capacityare delivered. SMS’s capacity is 90byte and exceeded contents are deleted automatically when a SMS is delivered. (Maximum of Korean letters: 45)
   * - Points(Fee) are deducted when user send a SMS. If sending is failed, points are going to be refunded.
   * - Check sending result on POPBILL website [Messaging/FAX -> Messaging -> Sending log]
   */
  public function SendSMS(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190213-004';

    // Sender’s Number
    $sender = '07043042991';

    // Receiver’s Number
    $receiver = '010111222';

    // Contents of SMS (*Exceeded contents are deleted automatically when a SMS is delivered.)
    $contents = '문자전송 내용입니다. 90Byte를 초과한내용은 길이 조정되어 전송됩니다. 참고하시기 바랍니다.';

    try {
        $result = $this->PopbillTaxinvoice->SendSMS($testCorpNum , $mgtKeyType, $mgtKey, $sender, $receiver, $contents);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * 전Send an e-Tax invoice by fax.
   * - Points(Fee) are deducted when user send a fax. If sending is failed, points are going to be refunded.
   * - Check sending result on POPBILL website [Messaging/FAX -> FAX -> Sending log]*/
  public function SendFAX(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    // Sender’s Number
    $sender = '07043042991';

    // Receiver’s Number
    $receiver = '070111222';

    try {
        $result = $this->PopbillTaxinvoice->SendFAX($testCorpNum, $mgtKeyType, $mgtKey, $sender, $receiver);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Attach a statement to e-Tax invoice.
   */
  public function AttachStatement(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    // Statement’s type code, [121: Transaction details, 122: Bill, 123: Estimate, 124: Purchase order, 125: Deposit slip, 126: Receipt]
    $subItemCode = 121;

    // Statement’s id required to be attached to e-Tax invoice
    $subMgtKey = '20190101-001';

    try {
        $result = $this->PopbillTaxinvoice->AttachStatement($testCorpNum, $mgtKeyType, $mgtKey, $subItemCode, $subMgtKey);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Detach a statement from e-Tax invoice.
   */
  public function DetachStatement(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    // Statement’s type code, [121: Transaction details, 122: Bill, 123: Estimate, 124: Purchase order, 125: Deposit slip, 126: Receipt]
    $subItemCode = 121;

    // Statement’s id required to be detached to e-Tax invoice
    $subMgtKey = '20190101-001';

    try {
        $result = $this->PopbillTaxinvoice->DetachStatement($testCorpNum, $mgtKeyType, $mgtKey, $subItemCode, $subMgtKey);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Check the email list of e-Tax invoice service provider.
   */
  public function GetEmailPublicKeys(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
        $emailList = $this->PopbillTaxinvoice->GetEmailPublicKeys($testCorpNum);
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/GetEmailPublicKeys', ['Result' => $emailList] );

  }

  /**
   * Assign an invoice id to e-Tax invoice that was not assigned by partner.
   */
  public function AssignMgtKey(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Type of the e-Tax invoice [CHOOSE 1: SELL-e-Tax invoice/ BUY-Requested e-Tax invoice / TRUSTEE-Delegated e-Tax invoice]
    $mgtKeyType = TIENumMgtKeyType::SELL;

    // POPBILL invoice id (*Refer ‘ItemKey’ – one of returned values of Search API)
    $itemKey = '018123114240100001';

    // Invoice id assigned by partner
    $mgtKey = '20190101-001';

    try {
        $result = $this->PopbillTaxinvoice->AssignMgtKey($testCorpNum, $mgtKeyType, $itemKey, $mgtKey);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);

  }

  /**
   * Return the outgoing mail list related to e-Tax invoice being sent to notify the issuance or cancellation etc..
   */
  public function ListEmailConfig(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillTaxinvoice->ListEmailConfig($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('Taxinvoice/ListEmailConfig', ['Result' => $result] );
  }

  //////////////////////////////////////////////////////
  //[*Purpose of the outgoing mail]
  // e-Tax invoice issuance process
  // TAX_ISSUE : [to buyer] to notify the issuing an e-Tax invoice
  // TAX_ISSUE_INVOICER : [to seller] to notify the issuing an e-Tax invoice
  // TAX_CHECK : [to seller] to notify that buyer have checked an e-Tax invoice
  // TAX_CANCEL_ISSUE : [to buyer] to notify that seller have cancelled the issuing an e-Tax invoice

  // About e-tax invoice being ready to issue
  // TAX_SEND : [to buyer] to notify that an e-Tax invoice being ready to issue is sent
  // TAX_ACCEPT : [to seller] to notify that an e-Tax invoice being ready to issue is accepted
  // TAX_ACCEPT_ISSUE : [to seller] to notify that an e-Tax invoice being ready to issue is issued automatically
  // TAX_DENY : [to seller] to notify that an e-Tax invoice being ready to issue is refused
  // TAX_CANCEL_SEND : [to buyer] to notify that an e-Tax invoice being ready to issue is cancelled

  // Requested e-Tax invoice issuance process
  // TAX_REQUEST : [to seller] to request the issuing an e-Tax invoice with e-signature
  // TAX_CANCEL_REQUEST : [to buyer] to notify that the request for issuing an e-Tax invoice is cancelled
  // TAX_REFUSE : [to buyer] to notify that an e-Tax invoice requested for issuing is refused

  // Delegated e-Tax invoice issuance process
  // TAX_TRUST_ISSUE : [to buyer] to notify the issuing an e-Tax invoice
  // TAX_TRUST_ISSUE_TRUSTEE : [to trustee] to notify the issuing an e-Tax invoice
  // TAX_TRUST_ISSUE_INVOICER : [to seller] to notify the issuing an e-Tax invoice
  // TAX_TRUST_CANCEL_ISSUE : [to buyer] to notify that trustee have cancelled the issuing an e-Tax invoice
  // TAX_TRUST_CANCEL_ISSUE_INVOICER : [to seller] to notify that trustee have cancelled the issuing an e-Tax invoice

  // About delegated e-tax invoice being ready to issue [delegated e-Tax invoice issuance process]
  // TAX_TRUST_SEND : [to buyer] to notify that an e-Tax invoice being ready to issue is sent
  // TAX_TRUST_ACCEPT : [to trustee] to notify that an e-Tax invoice being ready to issue is accepted
  // TAX_TRUST_ACCEPT_ISSUE : [to trustee] to notify that an e-Tax invoice being ready to issue is issued automatically
  // TAX_TRUST_DENY : [to trustee] to notify that an e-Tax invoice being ready to issue is refused
  // TAX_TRUST_CANCEL_SEND : [to buyer] to notify that an e-Tax invoice being ready to issue is cancelled

  // About results
  // TAX_CLOSEDOWN : to notify a result of NTS business status check
  // TAX_NTSFAIL_INVOICER : to notify filing failure of e-Tax invoice

  // About regular mailing
  // TAX_SEND_INFO : to notify a e-Tax invoice belonged to last month is issued
  // ETC_CERT_EXPIRATION : to notify that a registered certificate on POPBILL is required to renew
  ///////////////////////////////////////////////
  public function UpdateEmailConfig(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // [*Purpose of the outgoing mail]
    $emailType = 'TAX_ISSUE';

    // Whether a mail will be sent or not.
    $sendYN = True;

    try {
        $result = $this->PopbillTaxinvoice->UpdateEmailConfig($testCorpNum, $emailType, $sendYN);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * URL to register the certificate
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
  */
  public function GetTaxCertURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    try {
        $url = $this->PopbillTaxinvoice->GetTaxCertURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "공인인증서 등록 URL" , 'value' => $url]);
  }

  /**
   * Return certificate’s expiration date of partner user registered in POPBILL website.
   */
  public function GetCertificateExpireDate(){

    // 팝빌회원 사업자번호
    $testCorpNum = '1234567890';

    try {
        $certExpireDate = $this->PopbillTaxinvoice->GetCertificateExpireDate($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "공인인증서 만료일시" , 'value' => $certExpireDate]);
  }

  /**
   * Check certificate’s validity of partner user registered in POPBILL website.
   */
  public function CheckCertValidation(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try	{
        $result = $this->PopbillTaxinvoice->CheckCertValidation($testCorpNum);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Check point’s balance of partner user.
   * - Business registration number of POPBILL user (10 digits except ‘-’)
   */
  public function GetBalance(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillTaxinvoice->GetBalance($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "연동회원 잔여포인트" , 'value' => $remainPoint]);
  }

  /**
   * Return popup URL to charge points of partner user.
   * - Returned URL is valid only during 30 seconds following the security policy. So when you call the URL after the valid time, page will not be opened at all.
   */
  public function GetChargeURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    try {
      $url = $this->PopbillTaxinvoice->GetChargeURL($testCorpNum, $testUserID);
    } catch (PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "연동회원 포인트 충전 팝업 URL" , 'value' => $url]);

  }

  /**
   * Check point’s balance of partner.
   */
  public function GetPartnerBalance(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
        $remainPoint = $this->PopbillTaxinvoice->GetPartnerBalance($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "파트너 잔여포인트" , 'value' => $remainPoint]);
  }

  /**
   * Return popup URL to charge partner’s points.
   */
  public function GetPartnerURL(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // CHRG-Charging partner’s points
    $TOGO = 'CHRG';

    try {
        $url = $this->PopbillTaxinvoice->GetPartnerURL($testCorpNum, $TOGO);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ReturnValue', ['filedName' => "파트너 포인트 충전 팝업 URL" , 'value' => $url]);
  }

  /**
   * Check the charging information of POPBILL services.
   */
  public function GetUnitCost(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
        $unitCost = $this->PopbillTaxinvoice->GetUnitCost($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('ReturnValue', ['filedName' => "전자세금계산서 발행단가" , 'value' => $unitCost]);
  }

  /**
   * Check the issuance unit cost per an e-Tax invoice.
   */
  public function GetChargeInfo(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
        $result = $this->PopbillTaxinvoice->GetChargeInfo($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }
    return view('GetChargeInfo', ['Result' => $result]);
  }

  /**
   * Check whether a user is a partner user or not.
   */
  public function CheckIsMember(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // LinkID
    $LinkID = config('popbill.LinkID');

    try	{
      $result = $this->PopbillTaxinvoice->CheckIsMember($testCorpNum, $LinkID);
      $code = $result->code;
      $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Check whether POPBILL member’s ID is in use or not.
   */
  public function CheckID(){

    // ID to be required to check the redundancy
    $testUserID = 'testkorea';

    try	{
      $result = $this->PopbillTaxinvoice->CheckID($testUserID);
      $code = $result->code;
      $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Join the POPBILL as a partner user.
   */
  public function JoinMember(){

    $joinForm = new JoinForm();

    // LinkID
    $joinForm->LinkID = config('popbill.LinkID');

    // Business registration number ( 10 digits except ‘-’ )
    $joinForm->CorpNum = '1234567890';

    // CEO’s name
    $joinForm->CEOName = '대표자성명';

    // Company name
    $joinForm->CorpName = '테스트사업자상호';

    // Company address
    $joinForm->Addr	= '테스트사업자주소';

    // Business type
    $joinForm->BizType = '업태';

    // Business item
    $joinForm->BizClass	= '종목';

    // Name of the person in charge
    $joinForm->ContactName = '담당자상명';

    // Email address of the person in charge
    $joinForm->ContactEmail	= 'tester@test.com';

    // Telephone number of the person in charge
    $joinForm->ContactTEL	= '07043042991';

    // ID, From 6 to 50 letters
    $joinForm->ID = 'userid_phpdd';

    // Password, From 6 to 20 letters
    $joinForm->PWD = 'thisispassword';

    try	{
      $result = $this->PopbillTaxinvoice->JoinMember($joinForm);
      $code = $result->code;
      $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Check the company information of partner user.
   */
  public function GetCorpInfo(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
      $CorpInfo = $this->PopbillTaxinvoice->GetCorpInfo($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
      $code = $pe->getCode();
      $message = $pe->getMessage();
      return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('CorpInfo', ['CorpInfo' => $CorpInfo]);
  }

  /**
   * Modify the company information of partner user
   */
  public function UpdateCorpInfo(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Objects of company information
    $CorpInfo = new CorpInfo();

    // CEO’s name
    $CorpInfo->ceoname = '대표자명';

    // Company name
    $CorpInfo->corpName = '링크허브';

    // Company address
    $CorpInfo->addr = '서울시 강남구 영동대로';

    // Business type
    $CorpInfo->bizType = '업태';

    // Business item
    $CorpInfo->bizClass = '종목';

    try {
        $result =  $this->PopbillTaxinvoice->UpdateCorpInfo($testCorpNum, $CorpInfo);
        $code = $result->code;
        $message = $result->message;
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Add a contact of the person in charge(POPBILL account) of partner user.
   */
  public function RegistContact(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // Objects of contact information
    $ContactInfo = new ContactInfo();

    // ID, From 6 to 50 letters
    $ContactInfo->id = 'testkorea001';

    // Password, From 6 to 20 letters
    $ContactInfo->pwd = 'testkorea!@//$/';

    // Name of the person in charge
    $ContactInfo->personName = '담당자_수정';

    // Telephone number of the person in charge
    $ContactInfo->tel = '070-4304-2991';

    // Mobile number of the person in charge
    $ContactInfo->hp = '010-1234-1234';

    // Email
    $ContactInfo->email = 'test@test.com';

    // Fax number of the person in charge
    $ContactInfo->fax = '070-111-222';

    // Configuration for search authorization
    $ContactInfo->searchAllAllowYN = true;

    // Whether an user is a administrator or not
    $ContactInfo->mgrYN = false;

    try {
        $result = $this->PopbillTaxinvoice->RegistContact($testCorpNum, $ContactInfo);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }

  /**
   * Check the list of persons in charge of partner user.
   */
  public function ListContact(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    try {
      $ContactList = $this->PopbillTaxinvoice->ListContact($testCorpNum);
    }
    catch(PopbillException | LinkhubException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
        return view('PResponse', ['code' => $code, 'message' => $message]);
    }

    return view('ContactInfo', ['ContactList' => $ContactList]);
  }

  /**
   * Modify the contact information of partner user.
   */
  public function UpdateContact(){

    // Business registration number of POPBILL user (10 digits except ‘-’)
    $testCorpNum = '1234567890';

    // POPBILL user ID
    $testUserID = 'testkorea';

    // Objects of contact information
    $ContactInfo = new ContactInfo();

    // Name of the person in charge
    $ContactInfo->personName = '담당자_수정';

    // ID, From 6 to 50 letters
    $ContactInfo->id = 'testkorea';

    // Telephone number of the person in charge
    $ContactInfo->tel = '070-4304-2991';

    // Mobile number of the person in charge
    $ContactInfo->hp = '010-1234-1234';

    // Email address of the person in charge
    $ContactInfo->email = 'test@test.com';

    // Fax number of the person in charge
    $ContactInfo->fax = '070-111-222';

    // Configuration for search authorization
    $ContactInfo->searchAllAllowYN = true;

    try {
        $result = $this->PopbillTaxinvoice->UpdateContact($testCorpNum, $ContactInfo, $testUserID);
        $code = $result->code;
        $message = $result->message;
    }
    catch ( PopbillException | LinkhubException $pe ) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message]);
  }
}
