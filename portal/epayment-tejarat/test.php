<?php

require_once '../header.inc.php';
require_once getenv("DOCUMENT_ROOT") . '/loan/request/request.class.php';
require_once getenv("DOCUMENT_ROOT") . '/commitment/ExecuteEvent.class.php';
require_once 'nusoap.php';

ini_set("display_errors", "On");

$result = RegDoc(1574,1000,1111);
echo $result ? "true" : "false";
print_r(ExceptionHandler::PopAllExceptions());

function RegDoc($RequestID, $amount, $PayRefNo){
	
	$dt = PdoDataAccess::runquery("select * from LON_BackPays where PayRefNo=?", array($PayRefNo));
	if(count($dt) > 0)
	{
		ExceptionHandler::PushException("<br> کد رهگیری قبلا ثبت شده است");
		return false;
	}
	
	$obj = new LON_BackPays();
	$obj->RequestID = $RequestID;
	$obj->PayType = 4;
	$obj->PayAmount = $amount;
	$obj->PayDate = PDONOW;
	$obj->PayRefNo = $PayRefNo;

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();

	if(!$obj->Add($pdo))
		return false;

	$reqObj = new LON_requests($RequestID);
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	$EventID = LON_requests::GetEventID($RequestID, EVENTTYPE_LoanBackPay);

	$_POST["TafsiliID1_168"] = 4025; //حساب جاری
	$_POST["TafsiliID2_168"] = 1947; //تجارت کوتاه مدت
	$_POST["param1_168"] = "تجارت کوتاه مدت 425273566";
	
	$eventobj = new ExecuteEvent($EventID);
	$eventobj->Sources = array($RequestID, $partObj->PartID, $obj->BackPayID);
	$result = $eventobj->RegisterEventDoc($pdo);
	if(!$result)
	{
		$pdo->rollBack();
		return false;
	}
	
	$pdo->commit();
	return true;	
}