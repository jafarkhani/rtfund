<?php
date_default_timezone_set('Asia/Tehran');
require_once('nusoap.php');

$webServiceURL       = "http://sms.Parsgreen.ir/Api/SendSMS.asmx?wsdl";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";
$webServicemobile = "09---------";
$webServiceLang = "fa";
$webServiceotpType = 2; 
$webServicepatternId = 1; 

	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	} 
     $parameters['signature'] = $webServiceSignature;
     $parameters['mobile'] = $webServicemobile;
     $parameters['Lang'] = $webServiceLang;
     $parameters['otpType'] = $webServiceotpType;
     $parameters['patternId'] = $webServicepatternId;
     $parameters['otpCode'] = 0x0;
     
     $result = $client->call('SendOtp', $parameters);
     
     print_r($result); 
     print_r('</br>');
?>
 