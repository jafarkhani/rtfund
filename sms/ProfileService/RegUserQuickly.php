<?php
	
date_default_timezone_set('Asia/Tehran');  
require_once('nusoap.php');

$webServiceURL = "http://sms.parsgreen.ir/Api/ProfileService.asmx?WSDL";
$webServiceSignature = "12345-DFCA-42BB-8233-7BDA20D6D1EB";
$webServiceheaderData = "";
$webServiceusername = "your UserName";
$webServicefirstName = "Your First/name";
$webServicelastName = "Your Last/name";
$webServicecompany = "";
$webServiceemail = "yourEmail@test.com";
$webServicemobile = "09---------";
$webServicephoneNumbers = "";
$webServicefaxNumbers = "";
$webServiceaddress = "";
$webServicedescriptionse = "";
$webServicenationalCode = "";
$webServiceisAdvanced = false;

	$client = new nusoap_client($webServiceURL,true);
    $client->soap_defencoding = 'UTF-8';
	$err = $client->getError();
	if ($err) 
	{ 	 
 	   echo 'Constructor error' . $err; 
	} 

     $parameters['signature'] = $webServiceSignature;
     $parameters['headerData'] = $webServiceheaderData;
     $parameters['username'] = $webServiceusername;
     $parameters['firstName'] = $webServicefirstName;
     $parameters['lastName'] = $webServicelastName;
     $parameters['company'] = $webServicecompany;
     $parameters['email'] = $webServiceemail;
     $parameters['mobile'] = $webServicemobile;
     $parameters['phoneNumbers'] = $webServicephoneNumbers;
     $parameters['faxNumbers'] = $webServicefaxNumbers;
     $parameters['address'] = $webServiceaddress;
     $parameters['descriptions'] = $webServicedescriptionse;
     $parameters['nationalCode'] = $webServicenationalCode;
     $parameters['isAdvanced'] = $webServiceisAdvanced;
     $parameters['encryptUserId'] = "";
     $parameters['userSignature'] = "";
     
     $result = $client->call('RegUserQuickly', $parameters);
     
     print_r($result); 
     print_r('</br>');
        
?>