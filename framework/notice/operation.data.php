<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 95.05
//-------------------------

require_once('../../header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once getenv("DOCUMENT_ROOT") . '/office/letter/letter.class.php';
require_once "config.inc.php";
require_once 'operation.class.php';
require_once 'email.php';
require_once 'sms.php';
ini_set('max_execution_time', 30000000);

$task = isset($_REQUEST["task"]) ? $_REQUEST["task"] : "";
if(!empty($task))
{
	$task();
}

function SaveOperation(){
	
	$obj = new NTC_operations();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$obj->GroupLetter = isset($_POST["GroupLetter"]) ? "YES" : "NO";
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	
	if(empty($obj->OperationID))
	{
		$obj->OperationDate = PDONOW;
		$result = $obj->Add($pdo);
	}
	else
	{
		$result = $obj->Edit($pdo);
	}
	require_once("phpExcelReader.php");
	
	$data = new Spreadsheet_Excel_Reader();
    $data->setOutputEncoding('utf-8');
    $data->setRowColOffset(0);
	$data->read($_FILES["PersonFile"]["tmp_name"]);		
	
	for ($i = 0; $i < $data->sheets[0]['numRows']; $i++) 
	{
		if(!empty($data->sheets[0]['cells'][$i][0]))
		{
			$PersonID = $data->sheets[0]['cells'][$i][0];
			$dt = PdoDataAccess::runquery("select PersonID from BSC_persons where PersonID=?", array($PersonID));
			if(count($dt) > 0)
			{
				$PersonObj = new NTC_persons();
				$PersonObj->OperationID = $obj->OperationID;
				$PersonObj->PersonID = $PersonID;
				$PersonObj->context = $obj->context;
				
				for($j=1; $j<count($data->sheets[0]['cells'][$i]); $j++)
				{
					$PersonObj->context = preg_replace ("/\[col".$j."\]/", 
							$data->sheets[0]['cells'][$i][$j], 
							$PersonObj->context);
				}
				$PersonObj->Add($pdo);
			}
		}
	}
	$dt = NTC_persons::Get(" AND OperationID=?", array($obj->OperationID), $pdo);
	if($dt->rowCount() == 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "در فایل ارسالی هیچ فرد معتبری یافت نشد");
		die();
	}
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
					$result = SendEmail($email, $obj->title, $context);
					if(!$result)
						ExceptionHandler::PushException ("خطا در ارسال ایمیل");
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
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function SelectOperations(){
	
	$param = array();
	$where = "";
	
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
        $where .= ' and ' . $field . ' like :fld';
        $param[':fld'] = '%' . $_REQUEST['query'] . '%';
    }
		
	$dt = NTC_operations::Get($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();
	$count = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);	
	
	echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
	die();
}

function DeleteOperation(){
	
	$obj = new NTC_operations($_POST["OperationID"]);
	
	if($obj->SendType != NTC_SENDTYPE_LETTER)
	{
		echo Response::createObjectiveResponse(false, "قادر به حذف نمی باشید");
		die();
	}
	
	if(!$obj->Remove())
	{
		echo Response::createObjectiveResponse(false, "خطا در حذف");
		die();
	}

	echo Response::createObjectiveResponse(true, "");
	die();
}

function SelectPersons(){
	
	$temp = NTC_persons::Get(" AND OperationID=?", array($_REQUEST["OperationID"]));
	echo dataReader::getJsonData($temp->fetchAll(), $temp->rowCount(), $_GET["callback"]);
	die();
}
//.............................................

function SelectTemplates(){

	$where  = "";
	$param = array();
	
	if(!empty($_REQUEST["SendType"]))
	{
		$where .= " AND SendType=?";
		$param[] = $_REQUEST["SendType"];
	}
	
	$temp = NTC_templates::Get($where, $param);
	//print_r(ExceptionHandler::PopAllExceptions());
	echo dataReader::getJsonData($temp->fetchAll(), $temp->rowCount(), $_GET["callback"]);
	die();
}

function AddToTemplates(){
	
	$obj = new NTC_templates();
	$obj->FillObjectByArray($obj, $_POST);
	
	$result = $obj->Add();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function SaveTemplates(){
	
	$obj = new NTC_templates();
	$obj->FillObjectByArray($obj, $_POST);
	
	$result = $obj->Edit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteTemplate(){
	
	$obj = new NTC_templates($_POST["TemplateID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

?>