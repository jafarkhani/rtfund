<?php
date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL = "https://sms.parsgreen.ir/Api/ScheduleService.asmx?wsdl";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";
$ScheduleIdEncrypt = "D2vxr321gHE=";
	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	}
    $parameters = array ( 
	   	   'signature' => $webServiceSignature,
	   	   'ScheduleIdEncrypt' => $ScheduleIdEncrypt 
);  
    $result = $client->call('DeleteSchedule', $parameters);
     
     print_r($result); 
     print_r('</br>');

?>