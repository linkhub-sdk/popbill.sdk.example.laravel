<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Linkhub\Popbill\JoinForm;
use Linkhub\Popbill\CorpInfo;
use Linkhub\Popbill\ContactInfo;
use Linkhub\Popbill\ChargeInfo;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillTaxinvoice;

use Linkhub\Popbill\TaxinvoiceDetail;
use Linkhub\Popbill\TaxinvoiceAddContact;
use Linkhub\Popbill\Taxinvoice;
use Linkhub\Popbill\TISearchResult;
use Linkhub\Popbill\TaxinvoiceInfo;
use Linkhub\Popbill\TaxinvoiceLog;
use Linkhub\Popbill\TIENumMgtKeyType;


class TaxinvoiceController extends Controller
{
  public function __construct() {

    $this->LinkID = config('popbill.LinkID');
    $this->SecretKey = config('popbill.SecretKey');
    $IsTest = config('popbill.IsTest');
    $LINKHUB_COMM_MODE = config('popbill.LINKHUB_COMM_MODE');

    $this->PopbillTaxinvoice = new PopbillTaxinvoice($this->LinkID, $this->SecretKey);

    // 연동환경 설정값, 개발용(true), 상업용(false)
    $this->PopbillTaxinvoice->IsTest($IsTest);

    define('LINKHUB_COMM_MODE',$LINKHUB_COMM_MODE);

    var_dump($this->LinkID);
    var_dump($this->SecretKey);
    var_dump($IsTest);

  }

  public function CheckIsMember(){

    // 사업자번호, "-"제외 10자리
    $testCorpNum = '1234567890';

    try	{
        $result = $this->PopbillTaxinvoice->CheckIsMember($testCorpNum, $this->LinkID);
        $code = $result->code;
        $message = $result->message;
    }
    catch(PopbillException $pe) {
        $code = $pe->getCode();
        $message = $pe->getMessage();
    }

    return view('PResponse', ['code' => $code, 'message' => $message] );
  }


  public function RegistIssue(){
    return view('Taxinvoice/RegistIssue');
  }
}
