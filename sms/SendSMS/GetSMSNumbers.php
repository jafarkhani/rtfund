<?php

date_default_timezone_set('Asia/Tehran');
require_once('nusoap.php');

$webServiceURL  = "http://sms.Parsgreen.ir/Api/SendSMS.asmx?wsdl";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";
$webServicenumberType   = 3; 

$client = new nusoap_client($webServiceURL,true);
$client->soap_defencoding = 'UTF-8';
$err = $client->getError();
if ($err) 
{ 	 
    echo 'Constructor error' . $err; 
}
$parameters['signature'] = $webServiceSignature;
$parameters['numberType' ]= $webServicenumberType;
$parameters[ 'numbers'] = array( 0  );

$result = $client->call('GetSMSNumbers', $parameters);
print_r($result); 
print_r('</br>');
?>
 