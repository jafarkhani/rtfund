<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../../header.inc.php';
require_once '../docs/doc.class.php';
require_once '../docs/import.data.php';
require_once '../../loan/request/request.class.php';
require_once 'rule.class.php';
require_once inc_dataReader;
require_once inc_response;

$task = isset($_POST ["task"]) ? $_POST ["task"] : (isset($_GET ["task"]) ? $_GET ["task"] : "");
if(!empty($task))
	$task();

function selectPersons() {
	
	$query = "select PackNo,p.PersonID,concat_ws(' ', fname,lname,CompanyName) fullname 
		from ACC_DocItems di
			join ACC_docs d using(DocID)
			join ACC_tafsilis t on(di.TafsiliID=t.TafsiliID)
			join BSC_persons p on(t.ObjectID=p.PersonID)
			left join DMS_packages k on(p.PersonID=k.PersonID AND k.BranchID=d.BranchID)
		where d.CycleID=:c AND d.BranchID=:b AND 
			di.CostID=:cost AND di.TafsiliType = :t";
			
	$param = array(
		":c" => $_SESSION["accounting"]["CycleID"],
		":b" => $_REQUEST["BranchID"],
		":cost" => COSTID_saving,
		":t" => TAFSILITYPE_PERSON
	);
	if(!empty($_REQUEST["query"]))
	{
		$query .= " AND ( concat_ws(' ', fname,lname,CompanyName) like :p or PackNo = :p )";
		$param[":p"] = "%" . $_REQUEST["query"] . "%";
	}
	
	$query .= " group by PersonID";
	
	$temp = PdoDataAccess::runquery_fetchMode($query, $param);
	
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function GetSavingFlow() {
	
	die();
	//-------------- not used
	$query = "select d.*,di.*
		from ACC_DocItems di
			join ACC_docs d using(DocID)
			join ACC_tafsilis t on(di.TafsiliID=t.TafsiliID)
			join BSC_persons p on(t.ObjectID=p.PersonID)
		where d.CycleID=:c AND d.BranchID=:b AND 
			di.CostID=:cost AND di.TafsiliType = :t AND p.PersonID=:p";
	
	$param = array(
		":c" => $_SESSION["accounting"]["CycleID"],
		":b" => $_SESSION["accounting"]["BranchID"],
		":cost" => COSTID_saving,
		":t" => TAFSILITYPE_PERSON,
		":p" => $_REQUEST["PersonID"]
	);
	
	$temp = PdoDataAccess::runquery_fetchMode($query, $param);
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function GetSavingLoanInfo($ReportMode = false){
	
	if(session::IsPortal())
		$PersonID = $_SESSION["USER"]["PersonID"];
	else
		$PersonID = $_REQUEST["PersonID"];
	$StartDate = DateModules::shamsi_to_miladi($_REQUEST["StartDate"], "-");
	$EndDate = DateModules::shamsi_to_miladi($_REQUEST["EndDate"], "-");
	$BranchID = $_REQUEST["BranchID"];
	//----------- check for all docs confirm --------------
	if(!$ReportMode)
	{
		/*$dt = PdoDataAccess::runquery("select group_concat(distinct LocalNo) from ACC_docs 
			join ACC_DocItems using(DocID) join ACC_tafsilis t using(TafsiliType,TafsiliID)
			where TafsiliType=" . TAFSILITYPE_PERSON . " 
				AND ObjectID = ? AND CostID in(" . COSTID_saving . ")
				AND StatusID <> ".ACC_STEPID_CONFIRM."
				AND BranchID=?
				AND DocDate >= ?", array($PersonID, $BranchID, $StartDate));
		if(count($dt) > 0 && $dt[0][0] != "")
		{
			$msg = "اسناد با شماره های [" . $dt[0][0] . "] تایید نشده اند.";
			echo dataReader::getJsonData(array(), 0, $_GET["callback"], $msg);
			die();
		}*/
	}
	//------------ get sum of savings ----------------
	$dt = PdoDataAccess::runquery("
		select DocDate,sum(CreditorAmount-DebtorAmount) amount
		from ACC_DocItems di
			join ACC_docs d using(DocID)
			join ACC_tafsilis t using(TafsiliType,TafsiliID)
		
		where TafsiliType=" . TAFSILITYPE_PERSON . " 
			AND ObjectID = ?
			AND CostID in(" . COSTID_saving . ")
			AND BranchID=" . $BranchID . "
			AND DocDate >= ? AND DocDate <= ?
		group by DocDate
		order by DocDate", array($PersonID, $StartDate, $EndDate));
	
	if(count($dt) == 0)
	{
		$msg = "شخص مورد نظر فاقد حساب پس انداز می باشد";
		echo dataReader::getJsonData(array(), 0, $_GET["callback"], $msg);
		die();
	}
	//------------ get the Deposite amount -------------
	$TraceArr = array();
	$remain = $dt[0]["amount"]*1;
	$totalDays = 0;
	$totalAmount = 0;
	$TraceArr[] = array(
		"Date" => $dt[0]["DocDate"],
		"amount" => $dt[0]["amount"],
		"remain" => $dt[0]["amount"],
		"days" => 0,
		"average" => 0
	);
	for($i=1; $i < count($dt); $i++)
	{
		$days = DateModules::GDateMinusGDate($dt[$i]["DocDate"],$dt[$i-1]["DocDate"]);
		$totalDays += $days;
		
		$totalAmount += $remain*$days;
		$remain += $dt[$i]["amount"];
			
		$TraceArr[count($TraceArr)-1]["days"] = $days;
		$TraceArr[count($TraceArr)-1]["average"] = $totalAmount / $totalDays;
		$TraceArr[] = array(
			"Date" => $dt[$i]["DocDate"],
			"amount" => $dt[$i]["amount"],
			"remain" => $remain,
			"days" => 0
		);
	}
	$days = DateModules::GDateMinusGDate($EndDate,$dt[$i-1]["DocDate"]);
	$totalAmount += $remain*$days;
	$totalDays += $days;
	$totalAmount = round($totalAmount / $totalDays);
	$TraceArr[count($TraceArr)-1]["days"] = $days;
	$TraceArr[count($TraceArr)-1]["average"] = $totalAmount;
		
	if($ReportMode)	
		return $TraceArr;
	
	$returnArray = array(
		"PersonID" => $PersonID,
		"FirstDate" => DateModules::miladi_to_shamsi($dt[0]["DocDate"]),
		"AverageAmount" => $totalAmount,
		"TotalMonths" => floor($totalDays/30.5)
	);
	echo dataReader::getJsonData($returnArray, 1, $_GET["callback"]);
	die();	
}

//-------------------------------------------------

function selectRules(){
	
	$where = "";
	$param = array();
	
	if(!empty($_REQUEST["Date"]))
	{
		$where .= " AND ToDate > ? or ToDate is null";
		$param[] = $_REQUEST["Date"];
	}
	
	$dt = ACC_SavingRules::Get($where, $param);
	
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SaveRule(){
	
	$obj = new ACC_SavingRules();
	
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->RuleID != "")
		$result = $obj->Edit();
	else
		$result = $obj->Add();

	//print_r(ExceptionHandler::PopAllExceptions());
	Response::createObjectiveResponse($result, "");
	die();
}

function DeleteRule(){
	
	$obj = new ACC_SavingRules($_POST["RuleID"]);
	$result = $obj->Remove();
	
	Response::createObjectiveResponse($result, "");
	die();
}

function GetRulePeriods(){
	
	if(!isset($_REQUEST["RuleID"]))
	{
		echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
		die();
	}
	$RuleID = $_REQUEST["RuleID"];
	$dt = PdoDataAccess::runquery("select * from ACC_RulePeriods p
		join ACC_SavingRules r using(RuleID) where RuleID=?",array($RuleID));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SavePeriod(){
	
	$obj = new ACC_RulePeriods();
	
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->RowID != "")
		$result = $obj->Edit();
	else
		$result = $obj->Add();

	//print_r(ExceptionHandler::PopAllExceptions());
	Response::createObjectiveResponse($result, "");
	die();
}

function DeletePeriod(){
	
	$obj = new ACC_RulePeriods($_POST["RowID"]);
	$result = $obj->Remove();
	
	Response::createObjectiveResponse($result, "");
	die();
}

function CreateLoan(){
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new LON_requests();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->LoanID = 1;
	$obj->BranchID = $_SESSION["accounting"]["BranchID"];
	$obj->ReqDate = PDONOW;
	
	$result = $obj->AddRequest($pdo);
	if(!$result)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "خطا در ایجاد وام");
		die();
	}	
	
	$pobj = new LON_ReqParts();
	$pobj->RequestID = $obj->RequestID;
	PdoDataAccess::FillObjectByArray($pobj, $_POST);
	$pobj->PartDate = PDONOW;
	$pobj->PartDesc = "شرایط اولیه";
	$pobj->PayInterval = 1;
	$pobj->FundWage = $pobj->CustomerWage;
	$result = $pobj->AddPart();
	if(!$result)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "خطا در ایجاد شرایط وام");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}
?>