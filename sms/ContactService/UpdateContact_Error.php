<?php

date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL = "http://sms.parsgreen.ir/Api/ContactService.asmx?WSDL";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB";
$FirstName = "First Name";
$LastNamee = "Last Name";
$Corporation = "";
$MobileNumbers = "09---------";
$PhoneNumbers = "";
$FaxNumbers = "";
$BirthDate =strtotime("10:30pm April 15 2014");
$Email = "";
$Gender = 2;
$Address = "";
$PostalCode = "";
$Descriptions = "";
$welcomText = "";
$GroupIDEncrypt = array ("86OjkhgA9mI=");

	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	} 

    $parameters = array ( 
		  'signature' => $webServiceSignature,
		  'FirstName' => $FirstName,
		  'LastName' => $LastNamee,
          'Corporation' => $Corporation,
          'MobileNumbers' => $MobileNumbers,
          'PhoneNumbers' => $PhoneNumbers,
          'FaxNumbers' => $FaxNumbers,
          'BirthDate' => $BirthDate,
          'Email' => $Email,
          'GendGenderer' => $Gender,
          'Address' => $Address,
          'PostalCode' => $PostalCode,
          'Descriptions' => $Descriptions,
          'welcomText' => $welcomText,
          'GroupIDEncrypt' => $GroupIDEncrypt,
          'ContactIdEncrypt' => "",
    );
    
    $result = $client->call('UpdateContact', $parameters);
     
     print_r($result); 
     print_r('</br>');

?>