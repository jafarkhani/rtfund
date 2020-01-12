<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-------------------------
require_once('../header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once 'loan.class.php';

$task = $_REQUEST["task"];
switch ($task) {

	default : 
		eval($task. "();");
}

function AddGroup(){
	
	$InfoID = PdoDataAccess::GetLastID("BaseInfo", "InfoID", "TypeID=1");
	
	PdoDataAccess::runquery("insert into BaseInfo(TypeID,InfoID, InfoDesc) 
		values(1,?,?)", array($InfoID+1, $_POST["GroupDesc"]));
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SelectLoanGroups(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=1 AND IsActive='YES'");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}
/* NEW Create */
function SelectAllLoanSources(){

    $temp = PdoDataAccess::runquery("select * from LON_sources"); 
    echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
    die();
}
/* END NEW Create */

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
	
	$where = " l.IsActive='YES' ";
	$whereParam = array();
	
	if(isset($_GET["IsCustomer"]))
		$where .= " AND IsCustomer=true";
	
	if(isset($_GET["IsPlan"]))
		$where .= " AND IsPlan='YES'";
	
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
	//print_r(ExceptionHandler::PopAllExceptions());
	$no = $temp->rowCount();
	$temp = $temp->fetchAll();	
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function SaveLoan() {

	$obj = new LON_loans();
	PdoDataAccess::FillObjectByArray($obj, $_POST);

	$obj->IsCustomer = isset($_POST["IsCustomer"]) ? "YES" : "NO";
	$obj->IsPlan = isset($_POST["IsPlan"]) ? "YES" : "NO";
	
	if (empty($_POST["LoanID"]))
	{
		$pdo = PdoDataAccess::getPdoObject();
		$pdo->beginTransaction();
		
		$result = $obj->AddLoan($pdo);
		
		/*$CostCodesArr = array(
			array(8, $obj->BlockID),				// 110-?
			array(28, $obj->BlockID, 120),			// 721-?-51
			array(28, $obj->BlockID, 121, 213),		// 721-?-52-01
			array(28, $obj->BlockID, 121, 214),		// 721-?-52-02
			array(28, $obj->BlockID, 121, 215),		// 721-?-52-03
			array(11, $obj->BlockID, 211),			// 200-?-01
			array(11, $obj->BlockID, 120),			// 200-?-51
			array(40, $obj->BlockID),				// 750-?
			array(41, $obj->BlockID)				// 60-
		);
		
		foreach($CostCodesArr as $row)
		{
			$Cobj = new ACC_CostCodes();
			for($i=0; $i<count($row); $i++)
				$Cobj->{"level" . ($i+1)} = $row[$i];
			
			$Cobj->InsertCost($pdo);
		}*/
		
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			$pdo->rollBack();
			$result = false;
		}
		else
			$pdo->commit();
	}
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

function GetAllPayTypes(){
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=6 AND IsActive='YES'");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}
?>
