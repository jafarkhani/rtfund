<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-------------------------
include_once('../header.inc.php');
include_once inc_dataReader;
include_once inc_response;
include_once 'request.class.php';
require_once '../loan/loan.class.php';

$task = $_REQUEST["task"];
switch ($task) {
		
	case "SaveLoanRequest":
		SaveLoanRequest();
		
	case "SelectMyRequests":
		SelectMyRequests();
		
	case "SelectAllRequests":
		SelectAllRequests();
		
	//-------------------------------------------
	
	case "GetRequestParts":
		GetRequestParts();
		
	case "SavePart":
		SavePart();
		
	case "FillParts":
		FillParts();
}

function SaveLoanRequest(){
	
	$obj = new LON_requests();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(empty($obj->RequestID))
	{
		$LoanObj = new LON_loans($obj->LoanID);
		PdoDataAccess::FillObjectByObject($LoanObj, $obj);
		$obj->PersonID = $_SESSION["USER"]["PersonID"];
		$obj->StatusID = 10;
		$result = $obj->AddRequest();
	}
	else
		$result = $obj->EditRequest();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, $obj->RequestID);
	die();
}

function SelectMyRequests(){
	
	$dt = LON_requests::SelectAll("r.PersonID=? " . dataReader::makeOrder(), 
			array($_SESSION["USER"]["PersonID"]));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SelectAllRequests(){
	
	$branches = FRW_access::GetAccessBranches();
	$where = "BranchID in(" . implode(",", $branches) . ")";
	
	$where .= dataReader::makeOrder();
	$dt = LON_requests::SelectAll($where);
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

//------------------------------------------------

function GetRequestParts(){
	
	if(empty($_REQUEST["RequestID"]))
	{
		echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
		die();
	}
	
	$RequestID = $_REQUEST["RequestID"];
	
	$dt = LON_ReqParts::SelectAll("RequestID=?", array($RequestID));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();	
}

function SavePart(){
	
	$RequestID = $_REQUEST["RequestID"];
	
	if(empty($_REQUEST["RequestID"]))
	{
		$obj = new LON_requests();
		$obj->PersonID = $_SESSION["USER"]["PersonID"];
		$obj->StatusID = 1;
		$obj->AddRequest();
		$RequestID = $obj->RequestID;
	}
	
	
	
}

function FillParts(){
	
}
?>
