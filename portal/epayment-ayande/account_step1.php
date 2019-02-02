<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	93.06
//-------------------------
require_once("../header.inc.php");
require_once 'nusoap.php';
require_once getenv("DOCUMENT_ROOT") . '/accounting/baseinfo/baseinfo.class.php';

if(empty($_SESSION["USER"]["PersonID"]))
{
	echo "دسترسی نامعتبر است.";
	die();
}

$PayObj = new ACC_EPays();
$PayObj->amount = $_REQUEST["amount"];
$PayObj->PayDate = PDONOW;
$PayObj->PersonID = $_SESSION["USER"]["PersonID"];
$PayObj->CostID = $_REQUEST["CostID"];
$PayObj->Add();

ini_set ( "soap.wsdl_cache_enabled", "0" );
$wsdl_url = "https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL";
$callbackUrl = "http://framework.krrtf.ir/portal/epayment-ayande/account_step2.php";
$amount = $_REQUEST["amount"];
$pin = BANK_AYANDEH_PIN;
$orderId = $PayObj->PayID;

$params = array (
	"LoginAccount" => $pin,
	"Amount" => $amount,
	"OrderId" => $orderId,
	"CallBackUrl" => $callbackUrl 
);
$client = new SoapClient ( $wsdl_url );
try {
	$result = $client->SalePaymentRequest ( array (
			"requestData" => $params 
	) );
	if ($result->SalePaymentRequestResult->Token && $result->SalePaymentRequestResult->Status === 0) {
		header ( "Location: https://pec.shaparak.ir/NewIPG/?Token=" . $result->SalePaymentRequestResult->Token ); /* Redirect browser */
		exit ();
	}
	elseif ( $result->SalePaymentRequestResult->Status  != '0') {
		$err_msg = "(<strong> کد خطا : " . $result->SalePaymentRequestResult->Status . "</strong>) " .
		 $result->SalePaymentRequestResult->Message ;
	} 
} catch ( Exception $ex ) {
	$err_msg =  $ex->getMessage()  ; 
}
?>