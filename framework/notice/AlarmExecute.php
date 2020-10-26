<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.02
//---------------------------

ini_set("display_errors", "On");
ini_set("MAX_EXECUTION_TIME",3000);

ob_start();

define("DOCUMENT_ROOT", "/home/sajakrrt/domains/saja.krrtf.ir/public_html/");

set_include_path(DOCUMENT_ROOT . "generalClasses");

require_once DOCUMENT_ROOT . 'definitions.inc.php';
require_once 'InputValidation.class.php';
require_once 'PDODataAccess.class.php';
require_once 'classconfig.inc.php';
require_once 'DataAudit.class.php';

$fp = fopen(DOCUMENT_ROOT . "storage/errors.txt", "w");

define("SYSTEMID", 1);
session_start();
$_SESSION["USER"] = array("PersonID" => 1000);
$_SESSION['LIPAddress'] = '';

require_once DOCUMENT_ROOT . 'framework/configurations.inc.php';
require_once 'operation.class.php';
require_once DOCUMENT_ROOT . 'office/letter/letter.class.php';
require_once 'email.php';
require_once 'sms.php';

$alarms = PdoDataAccess::runquery("select * from NTC_alarms 
		join NTC_AlarmObjects using(ObjectID)		
	");

foreach($alarms as $row)
{
	$query = $row["QueryString"];
	$query .= " AND DATE_ADD(".$row["DateField"].", INTERVAL ".
			(($row["compute"] == "BEFORE" ? -1 : 1)*$row["days"])." DAY) = substr(".PDONOW.",1,10)";
			
	$temp = PdoDataAccess::runquery($query);
	if(count($temp) == 0)
		continue;
	
	$ObjItems = PdoDataAccess::runquery("select * from NTC_AlarmObjItems where ObjectID=?", 
			array($row["ObjectID"]));
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	//.............. add operation ..............
	$obj = new NTC_operations();
	$obj->SendType = $row["SendType"];
	$obj->context = $row["context"];
	$obj->title = $row["AlarmTitle"];
	$obj->OperationDate = PDONOW;
	$obj->Add($pdo);
	
	//.............. add persons to operation ..............
	foreach($temp as $ObjRows)
	{
		$PersonID = $ObjRows[ $row["ReceiverField"] ];
		if($PersonID == "")
			continue;
		
		$PersonObj = new NTC_persons();
		$PersonObj->OperationID = $obj->OperationID;
		$PersonObj->PersonID = $PersonID;
		$PersonObj->context = $obj->context;

		foreach($ObjItems as $item)
		{
			$value = $ObjRows[ $item["FieldName"] ];
			if($item["FieldType"] == "date")
				$value = DateModules::miladi_to_shamsi ($value);
			if($item["FieldType"] == "money")
				$value = number_format($value);
			$PersonObj->context = preg_replace ("/\[col".$item["ordering"]."\]/", $value, $PersonObj->context);
		}
		$PersonObj->Add($pdo);		
	}
	
	$dt = NTC_persons::Get(" AND OperationID=?", array($obj->OperationID), $pdo);
	if($dt->rowCount() == 0)
		continue;
	$dt = $dt->fetchAll();
	//----------- create letter -------------
	if($obj->SendType == "LETTER" && $obj->GroupLetter == "YES")
	{
		$LetterObj = new OFC_letters();
		$LetterObj->LetterType = "INNER";
		$LetterObj->LetterTitle = $obj->title;
		$LetterObj->LetterDate = PDONOW;
		$LetterObj->RegDate = PDONOW;
		$LetterObj->PersonID = $_SESSION["USER"]["PersonID"];
		$LetterObj->context = $obj->context;
		if(!$LetterObj->AddLetter($pdo))
			ExceptionHandler::PushException ("خطا در ثبت  نامه");
	}
	//---------------------------------------	
	foreach($dt as $row)
	{
		ExceptionHandler::PopAllExceptions();
		$context = $row["context"];
		switch($obj->SendType){
			case "SMS" :
				$mobile = $row["mobile"];
				if($mobile == "")
				{
					ExceptionHandler::PushException ("فاقد شماره پیامک");
				}
				else
				{
					$SendError = "";
					$result = ariana2_sendSMS($mobile, $context, "number", $SendError);
					if(!$result)
						ExceptionHandler::PushException ("خطا در ارسال پیامک" . "[" . $SendError . "]");
				}
				break;
			//------------------------------------------------------------------
			case "EMAIL" : 
				$email = $row["email"];
				if($email == "")
				{
					ExceptionHandler::PushException ("فاقد ایمیل");
				}
				else
				{
					$errors = "";
					$result = SendEmail($email, $obj->title, $context, array(), $errors);
					if(!$result)
						ExceptionHandler::PushException ("خطا در ارسال ایمیل " . $errors);
				}
				break;
			//------------------------------------------------------------------
			case "LETTER" : 
				if($obj->GroupLetter == "NO")
				{
					$LetterObj = new OFC_letters();
					$LetterObj->LetterType = "INNER";
					$LetterObj->LetterTitle = $obj->title;
					$LetterObj->LetterDate = PDONOW;
					$LetterObj->RegDate = PDONOW;
					$LetterObj->PersonID = $_SESSION["USER"]["PersonID"];
					$LetterObj->context = $context;
					$LetterObj->AddLetter($pdo);
					
					$SendObj = new OFC_send();
					$SendObj->LetterID = $LetterObj->LetterID;
					$SendObj->FromPersonID = $LetterObj->PersonID;
					$SendObj->ToPersonID = $row["PersonID"];
					$SendObj->SendDate = PDONOW;
					$SendObj->SendType = 1;
					if(!$SendObj->AddSend($pdo))
						ExceptionHandler::PushException ("خطا در ثبت  نامه");
				}
				else{
					
					$Cobj = new OFC_LetterCustomers();
					$Cobj->LetterID = $LetterObj->LetterID;
					$Cobj->PersonID = $row["PersonID"];
					$Cobj->IsHide = "NO";
					$Cobj->LetterTitle = $obj->title;
					if(!$Cobj->Add($pdo))
						ExceptionHandler::PushException ("خطا در ثبت ذینفع نامه");
				}				
				break;
			//------------------------------------------------------------------
		}
		if(ExceptionHandler::GetExceptionCount() == 0)
		{
			$PObj = new NTC_persons();
			$PObj->RowID = $row["RowID"];
			$PObj->IsSuccess = "YES";
			if($obj->SendType == "LETTER")
				$PObj->LetterID = $LetterObj->LetterID;
			$PObj->Edit($pdo);
		}
		else
		{
			$PObj = new NTC_persons();
			$PObj->RowID = $row["RowID"];
			$PObj->ErrorMsg = ExceptionHandler::GetExceptionsToString();
			if($obj->SendType == "LETTER")
				$PObj->LetterID = $LetterObj->LetterID;
			$PObj->Edit($pdo);
		}
	}
	
	$pdo->commit();	
}

$htmlStr = ob_get_contents();
ob_end_clean(); 
fwrite($fp, $htmlStr);
fclose($fp);

?>
