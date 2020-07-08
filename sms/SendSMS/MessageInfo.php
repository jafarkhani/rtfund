<?php
date_default_timezone_set('Asia/Tehran');
require_once('nusoap.php');

$webServiceURL  = "http://sms.Parsgreen.ir/Api/SendSMS.asmx?wsdl";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";
$WebServiceMsgBody = "hello World";

$client = new nusoap_client($webServiceURL,true);
$client->soap_defencoding = 'UTF-8';
$err = $client->getError();
if ($err) 
{ 	 
   echo 'Constructor error' . $err; 
} 
$parameters['signature'] = $webServiceSignature;
$parameters['MsgBody' ]=$WebServiceMsgBody;
$parameters['part' ]= 0;
$parameters['isUnicode' ]= true;     
$result = $client->call('MessageInfo', $parameters);  
   
print_r($result); 
print_r('</br>');
?>
 