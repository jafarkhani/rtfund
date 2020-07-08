<?php
date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL = "http://sms.parsgreen.ir/Api/ContactService.asmx?WSDL";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";
$groupName = "your Group Contact Name";
$groupDec = "";

	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	} 
    $parameters = array ( 
		  'signature' => $webServiceSignature,
		  'groupName' => $groupName,
		  'groupDec' => $groupDec,
		  'groupIDEncrypt' => ""         
    );
    
    $result = $client->call('AddGroupContact', $parameters);
     
     print_r($result); 
     print_r('</br>');

?>