<?php
ini_set("display_errors", "On");	
date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL  = "http://sms.Parsgreen.ir/Api/SendSMS.asmx?wsdl";
$webServiceSignature = "C38C9929-F343-499B-8BD3-5C79E4992020";
$webServicetoMobile   = "09155089018"; 
$webServicetextMessage="Hello World"; 

	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	} 
     $parameters['signature'] = $webServiceSignature;
     $parameters['toMobile' ]= $webServicetoMobile;
     $parameters['smsBody' ]=$webServicetextMessage;
     $parameters[ 'retStr'] = "";  

    $result = $client->call('Send', $parameters);
     
     print_r($result); 
     print_r('</br>');
?>