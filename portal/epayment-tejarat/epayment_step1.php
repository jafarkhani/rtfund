<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	97.06
//-------------------------
require_once("../header.inc.php");
require_once 'nusoap.php';
require_once getenv("DOCUMENT_ROOT") . '/accounting/baseinfo/baseinfo.class.php';

ini_set("display_errors", "On");

if(empty($_REQUEST["RequestID"]))
{
	echo "دسترسی نامعتبر است.";
	die();
}

$PayObj = new ACC_EPays();
$PayObj->BankType = "tejarat"; 
$PayObj->amount = $_REQUEST["amount"];
$PayObj->PayDate = PDONOW;
$PayObj->PersonID = $_SESSION["USER"]["PersonID"];
$PayObj->RequestID = $_REQUEST["RequestID"];
$PayObj->Add();

$_SESSION['merchantId'] = BANK_TEJARAT_MERCHANTID;
$_SESSION['sha1Key'] = BANK_TEJARAT_SHALKEY;
$_SESSION['admin_email'] = BANK_TEJARAT_ADMINEMAIL;
$_SESSION['amount'] = $PayObj->amount;
$_SESSION['PayOrderId'] = $PayObj->PayID;

$revertURL = "https://saja.krrtf.ir/portal/epayment-tejarat/epayment_step2.php";

$client = new SoapClient('https://ikc.shaparak.ir/XToken/Tokens.xml', array('soap_version' => SOAP_1_1));

$params['amount'] = $PayObj->amount;
$params['merchantId'] = BANK_TEJARAT_MERCHANTID;
$params['invoiceNo'] = $PayObj->PayID;
$params['paymentId'] = $PayObj->PayID;
$params['specialPaymentId'] = $PayObj->PayID;
$params['revertURL'] = $revertURL;
$params['description'] = "";
$result = $client->__soapCall("MakeToken", array($params));
$_SESSION['token'] = $result->MakeTokenResult->token;
$data['token'] = $_SESSION['token'];
$data['merchantId'] = BANK_TEJARAT_MERCHANTID;

redirect_post('https://ikc.shaparak.ir/TPayment/Payment/index',$data);

function redirect_post($url, array $data)
{

  echo '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<title>در حال اتصال ...</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
	#main {
	    background-color: #F1F1F1;
	    border: 1px solid #CACACA;
	    height: 90px;
	    left: 50%;
	    margin-left: -265px;
	    position: absolute;
	    top: 200px;
	    width: 530px;
	}
	#main p {
	    color: #757575;
	    direction: rtl;
	    font-family: Arial;
	    font-size: 16px;
	    font-weight: bold;
	    line-height: 27px;
	    margin-top: 30px;
	    padding-right: 60px;
	    text-align: right;
	}
    </style>
        <script type="text/javascript">
            function closethisasap() {
                document.forms["redirectpost"].submit();
            }
        </script>
    </head>
    <body onload="closethisasap();">';
   echo '<form name="redirectpost" method="post" action="'.$url.'">';
       
        if ( !is_null($data) ) {
            foreach ($data as $k => $v) {
                echo '<input type="hidden" name="' . $k . '" value="' . $v . '"> ';
            }
        }
       
   echo' </form><div id="main">
<p>درحال اتصال به درگاه بانک</p></div>
    </body>
    </html>';
   
    exit;
}
?>
