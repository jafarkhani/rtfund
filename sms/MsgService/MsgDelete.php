<?php
$webServiceURL       = "http://sms.parsgreen.ir/Api/MsgService.asmx?WSDL";
$webServiceSignature = "123456-DFCA-42BB-8233-7BDA20D6D1EB"; 
$webServicemsgIdEncrypt = "112jhgscm1wi7g31e";
$parameters  = array
( 
    'signature' => $webServiceSignature,
    'msgIdEncrypt' => $webServicemsgIdEncrypt
);
try
{
    $connectionS = new SoapClient($webServiceURL);
    $responseSTD = (array) $connectionS->MsgDelete($parameters); 
    if($responseSTD['MsgDeleteResult']<0)
    {
        echo ("Your Error Code Is = ");    
        echo $responseSTD['MsgDeleteResult'];
    }
    else
    {
        echo ("Your Succeeded Code Is = ");
        echo $responseSTD['MsgDeleteResult'];
    }
}
catch (SoapFault $ex) 
{
    echo $ex->faultstring;   
}
?>