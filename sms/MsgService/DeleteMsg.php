<?php
date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL       = "http://sms.parsgreen.ir/Api/MsgService.asmx?WSDL";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB"; 
$webServicemsgIdEncrypt = "kgderfrm63pgscm1wi7g31e";

	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	}
    $parameters  = array( 
        'signature' => $webServiceSignature,
        'msgIdEncrypt' => $webServicemsgIdEncrypt
    );

    $result = $client->call('DeleteMsg', $parameters);
     
     print_r($result); 
     print_r('</br>');   

?>