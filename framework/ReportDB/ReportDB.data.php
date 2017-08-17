<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	96.05
//---------------------------

require_once '../header.inc.php';
require_once(inc_response);
require_once inc_dataReader;
require_once './ReportDB.class.php';
require_once inc_reportGenerator;

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
if(!empty($task))
	$task();


function SelectReports() {
	
	$where = " AND MenuID=?";
	$params = array($_REQUEST["MenuID"]);
	
	$list = FRW_reports::Get($where, $params);
	print_r(ExceptionHandler::PopAllExceptions());
	$count = $list->rowCount();
	echo dataReader::getJsonData($list->fetchAll(), $count, $_GET['callback']);
	die();
}

function SaveReport() {

	$obj = new FRW_reports();
	$obj->MenuID = $_POST["MenuID"];
	if(!empty($_POST["ReportID"]))
		$obj->ReportID = $_POST["ReportID"];
	if(!empty($_POST["ReportDBTitle"]))
		$obj->title = $_POST["ReportDBTitle"];
	
	$EditItems = $_REQUEST["EditItems"] == "YES" ? true : false;
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if ($obj->ReportID == '')
		$obj->Add($pdo);
	else
	{
		if($EditItems)
			PdoDataAccess::runquery("delete from FRW_ReportItems where ReportID=?", 
				array($obj->ReportID), $pdo);
		$obj->Edit($pdo);
	}
	if($EditItems)
	{
		foreach($_POST as $key => $value)
		{
			if(empty($value))
				continue;
			if($key == "ReportID" || $key == "MenuID" || $key == "ReportDBTitle" || $key == "EditItems")
				continue;
			if(strpos($key, "combobox-") !== false)
				continue;
			if(strpos($key, ReportSetting::$FieldPrefix) !== false && $value == "false")
				continue;

			$obj2 = new FRW_ReportItems();
			$obj2->ReportID = $obj->ReportID;
			$obj2->ElemName = $key;
			$obj2->ElemValue = $value;
			$obj2->Add($pdo);
		}	
	}
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		Response::createObjectiveResponse(false, '');
		die();
	}
	
	$pdo->commit();
	Response::createObjectiveResponse(true, '');
	die();
}

function DeleteReport() {

	$obj = new FRW_reports($_POST['ReportID']);
	
	PdoDataAccess::runquery("delete from FRW_ReportItems where ReportID=?", array($obj->ReportID));
	$res = $obj->Remove();

	Response::createObjectiveResponse($res, "");
	die();
}

function SelectReportItems() {
	
	$where = " AND ReportID=?";
	$params = array($_REQUEST["ReportID"]);
	
	$list = FRW_ReportItems::Get($where, $params);
	print_r(ExceptionHandler::PopAllExceptions());
	$count = $list->rowCount();
	echo dataReader::getJsonData($list->fetchAll(), $count, $_GET['callback']);
	die();
}
?>