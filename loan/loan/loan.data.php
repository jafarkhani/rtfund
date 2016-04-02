<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-------------------------
include_once('../header.inc.php');
include_once inc_dataReader;
include_once inc_response;
include_once 'loan.class.php';

$task = $_REQUEST["task"];
switch ($task) {

	case "AddGroup":
		AddGroup();

	case "SelectLoanGroups":
		SelectLoanGroups();

	case "DeleteGroup":
		DeleteGroup();

	case "GetAllLoans":
		GetAllLoans();

	case "SaveLoan":
		SaveLoan();

	case "DeleteLoan":
		DeleteLoan();
}

function AddGroup(){
	
	$InfoID = PdoDataAccess::GetLastID("BaseInfo", "InfoID", "TypeID=1");
	
	PdoDataAccess::runquery("insert into BaseInfo(TypeID,InfoID, InfoDesc) 
		values(1,?,?)", array($InfoID+1, $_POST["GroupDesc"]));
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SelectLoanGroups(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=1");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function DeleteGroup(){
	
	$dt = PdoDataAccess::runquery("select * from LON_loans where GroupID=?",array($_POST["GroupID"]));
	if(count($dt)  > 0)
	{
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	PdoDataAccess::runquery("delete from BaseInfo where TypeID=1 AND InfoID=?",array($_POST["GroupID"]));
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetAllLoans() {
	
	$where = "1=1";
	$whereParam = array();
	
	if(isset($_GET["IsCustomer"]))
		$where .= " AND IsCustomer=true";
	
	if(!empty($_GET["GroupID"]))
	{
		$where .= " AND GroupID=:g";
		$whereParam[":g"] = $_GET["GroupID"];
	}
	if(!empty($_REQUEST["LoanID"]))
	{
		$where .= " AND LoanID=:l";
		$whereParam[":l"] = $_REQUEST["LoanID"];
	}
	
	$field = isset($_GET ["fields"]) ? $_GET ["fields"] : "";
	if (isset($_GET ["query"]) && $_GET ["query"] != "") {
		$where .= " AND " . $field . " LIKE :qry ";
		$whereParam[":qry"] = "%" . $_GET["query"] . "%";
	}

	$temp = LON_loans::SelectAll($where, $whereParam);
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);

	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function SaveLoan() {

	$obj = new LON_loans();
	PdoDataAccess::FillObjectByArray($obj, $_POST);

	$obj->IsCustomer = isset($_POST["IsCustomer"]) ? "YES" : "NO";
	
	if (empty($_POST["LoanID"]))
		$result = $obj->AddLoan();
	else
		$result = $obj->EditLoan();

	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteLoan() {
	
	$LoanID = $_POST["LoanID"];
	$result = LON_loans::DeleteLoan($LoanID);
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

?>
