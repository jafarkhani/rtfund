<?php
date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL = "http://sms.parsgreen.ir/Api/ContactService.asmx?WSDL";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";
$webServicegroupIDEncrypt = "Kh4qaqaqpI=";

	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	}

    $parameters['signature'] = $webServiceSignature;
    $parameters['GroupIDEncrypt'] = $webServicegroupIDEncrypt;
    $parameters['child'] = true;
    $parameters['count'] = 0;
    
    $result = $client->call('ContactCount', $parameters);
     
     print_r($result); 
     print_r('</br>');    

?>