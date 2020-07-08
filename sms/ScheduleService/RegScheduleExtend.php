<?php
date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL = "https://sms.parsgreen.ir/Api/ScheduleService.asmx?wsdl";  
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";  
$webServiceyearfa = 1397; 
$webServicemonthOfYearFa = 10; 
$webServicedayOfMonthFa = 15; 
$webServicehour = 14; 
$webServiceminute = 18; 
mb_internal_encoding("utf-8");
$webServicebody ="Hello World"; 
$webServicebody = mb_convert_encoding($webServicebody,"UTF-8"); 
$webServiceto = array ("09---------");
$webServicefrom = "";//sende message number

$client = new nusoap_client($webServiceURL,true);
$client->soap_defencoding = 'UTF-8';
$err = $client->getError();
if ($err) 
{ 	 
   echo 'Constructor error' . $err; 
} 

$parameters['signature'] = $webServiceSignature;
$parameters['yearfa'] = $webServiceyearfa;
$parameters['monthOfYearFa'] = $webServicemonthOfYearFa;
$parameters['dayOfMonthFa'] = $webServicedayOfMonthFa;
$parameters['hour'] = $webServicehour;
$parameters['minute'] = $webServiceminute;
$parameters['body'] = $webServicebody;
$parameters['to'] = $webServiceto;
$parameters['from'] = $webServicefrom;
$parameters['ScheduleIdEncrypt'] = "";

$result = $client->call('RegSchdeule_Extend', $parameters);     
print_r($result); 
print_r('</br>');

?>