<?php
//-------------------------
// programmer:	khoshroo
// create Date: 96.03
//-------------------------
require_once('../header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once 'extra.class.php';
require_once '../traffic/traffic.class.php';
require_once '../baseinfo/shift.class.php';

$task = $_REQUEST["task"];
switch ($task) {

	default : 
		eval($task. "();");
}
//..................................

function GetAllExtraSummarys() {
	
	$where = "";
	$whereParam = array();
	
	$where .= " AND SummaryYear=? AND SummaryMonth=?";
	$whereParam[] = $_REQUEST["SummaryYear"];
	$whereParam[] = $_REQUEST["SummaryMonth"];
	
	$temp = ATN_ExtraSummary::Get($where . dataReader::makeOrder(), $whereParam);
	//print_r(ExceptionHandler::PopAllExceptions());
	echo dataReader::getJsonData($temp->fetchAll(), $temp->rowCount(), $_GET["callback"]);
	die();
}

function ComputeExtraSummary() {

	$SummaryYear = $_POST["SummaryYear"];
	$SummaryMonth = $_POST["SummaryMonth"];
	
	//........................ confirm before .................................
	$dt = ATN_ExtraSummary::Get(" AND SummaryYear=? AND SummaryMonth=? and StatusCode='CONFIRM'", 
			array($SummaryYear, $SummaryMonth));
	if($dt->rowCount() > 0)
	{
		$message = "اضافه کار این ماه قبلا تایید شده است";
		echo Response::createObjectiveResponse(false, $message);
		die();
	}
	//.......................................................................
	
	if($SummaryMonth == "1")
		$StartDate = ($SummaryYear-1) . "-12-29";
	else
		$StartDate = $SummaryYear . "-" . ($SummaryMonth-1) . "-29";
			
	$StartDate = DateModules::shamsi_to_miladi($StartDate, "-");
	/*$EndDate = DateModules::shamsi_to_miladi($SummaryYear . "-" . $SummaryMonth ."-" . 
			DateModules::DaysOfMonth($SummaryYear ,$SummaryMonth), "-");*/
	$EndDate = DateModules::shamsi_to_miladi($SummaryYear . "-" . $SummaryMonth ."-28", "-");
	
	$PersonsDT = PdoDataAccess::runquery("
		select PersonID, concat(fname,' ',lname) fullname from BSC_persons
		where IsStaff='YES' AND IsActive='YES' ");
	
	$pdo = PdoDataAccess::getPdoObject(); 
	$pdo->beginTransaction();
			
	ATN_ExtraSummary::RemoveAll($SummaryYear,$SummaryMonth, $pdo);
	
	foreach($PersonsDT as $personRecord)
	{
		$SUM = ATN_traffic::Compute($StartDate, $EndDate, $personRecord["PersonID"], false);
			
		$extra = round(($SUM["extra"]<0 ? 0 : $SUM["extra"])/3600,2);
		$LegalExtra = round($SUM["LegalExtra"]/3600,2);
		$AllowedExtra = round($SUM["AllowedExtra"]/3600,2);
		
		$obj = new ATN_ExtraSummary();
		$obj->PersonID = $personRecord["PersonID"];
		$obj->SummaryYear = $SummaryYear;
		$obj->SummaryMonth = $SummaryMonth;
		$obj->RealAmount = $extra;
		$obj->LegalAmount = $LegalExtra;
		$obj->AllowedAmount = $AllowedExtra;
		$obj->FinalAmount = $AllowedExtra;
		$obj->Add($pdo);
	}		
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SaveExtraSummary($returning = false) {

	$SummaryYear = $_POST["SummaryYear"];
	$SummaryMonth = $_POST["SummaryMonth"];

	//........................ confirm before .................................
	$dt = ATN_ExtraSummary::Get(" AND SummaryYear=? AND SummaryMonth=? and StatusCode='CONFIRM'", 
			array($SummaryYear, $SummaryMonth));
	if($dt->rowCount() > 0)
	{
		$message = "اضافه کار این ماه قبلا ارسال تایید شده است";
		echo Response::createObjectiveResponse(false, $message);
		die();
	}
	
	//..........................................................................
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	foreach($_POST as $key => $value)
	{
		if(strpos($key, "row_") === false)
			continue;
		
		$SummaryID = str_replace("row_", "", $key );
		$obj = new ATN_ExtraSummary($SummaryID);
		$obj->FinalAmount = $value;
		$obj->Edit($pdo);
	}
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	$pdo->commit();
	
	if($returning)
		return true;
	
	echo Response::createObjectiveResponse(true, "");
	die();	
}

function ConfirmSummary() {
	
	$SummaryYear = $_POST["SummaryYear"];
	$SummaryMonth = $_POST["SummaryMonth"];

	SaveExtraSummary(true);
	
	PdoDataAccess::runquery("update ATN_ExtraSummary set StatusCode='CONFIRM' "
			. " where SummaryYear=? and SummaryMonth=?", array(
				$SummaryYear, $SummaryMonth
			));
	echo Response::createObjectiveResponse(true, "");
	die();	
}

function UndoSummary() {
	
	$SummaryYear = $_POST["SummaryYear"];
	$SummaryMonth = $_POST["SummaryMonth"];

	$dt = PdoDataAccess::runquery("select * from HRM_payments where 
			payment_type=1 and pay_year=? and pay_month=?",
			array($SummaryYear, $SummaryMonth));
	if(count($dt)> 0){
		echo Response::createObjectiveResponse(false, "در این ماه محاسبه حقوق انجام شده است");
		die();
	}
	
	PdoDataAccess::runquery("update ATN_ExtraSummary set StatusCode='RAW' "
			. " where SummaryYear=? and SummaryMonth=?", array(
				$SummaryYear, $SummaryMonth
			));
	echo Response::createObjectiveResponse(true, "");
	die();	
}

?>
