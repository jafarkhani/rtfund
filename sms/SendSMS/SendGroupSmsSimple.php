<?php
date_default_timezone_set('Asia/Tehran');
require_once('nusoap.php');

$webServiceURL  = "http://sms.Parsgreen.ir/Api/SendSMS.asmx?wsdl";  
$webServiceSignature = "12345-D799-4992-A2A4-FCE094DF987A";
$webServiceNumber   = ""; //Message Sende Number
$Mobiles = array ('string' =>'09---------');
mb_internal_encoding("utf-8");
$textMessage="hello World";
$textMessage= mb_convert_encoding($textMessage,"UTF-8");

$client = new nusoap_client($webServiceURL,true);
$client->soap_defencoding = 'UTF-8';
$err = $client->getError();
if ($err)
{
   echo 'Constructor error' . $err; 
} 

$parameters['signature'] = $webServiceSignature;
$parameters['from' ]= $webServiceNumber;
$parameters['to' ]  = $Mobiles ;
$parameters['text' ]=$textMessage;
$parameters[ 'isFlash'] = true;
$parameters['udh' ]= "";

$result = $client->call('SendGroupSmsSimple', $parameters);
print_r($result);
print_r('</br>');
?>
 