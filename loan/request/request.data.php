<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-------------------------
//ini_set("display_errors", "On");
require_once('../../header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once 'request.class.php';
require_once DOCUMENT_ROOT . '/loan/loan/loan.class.php';
require_once DOCUMENT_ROOT . "/office/workflow/wfm.class.php";
require_once DOCUMENT_ROOT . '/accounting/docs/import.data.php';
require_once DOCUMENT_ROOT . '/framework/person/persons.class.php';
require_once DOCUMENT_ROOT . '/office/letter/letter.class.php';
require_once 'compute.inc.php';
require_once inc_CurrencyModule;
require_once 'sms.php';

$task = isset($_REQUEST["task"]) ? $_REQUEST["task"] : "";
switch($task)
{
	case "SaveLoanRequest":
	case "SelectMyRequests":
	case "SelectAllRequests2":
	case "SelectAllRequests":
	case "Selectguarantees":
	case "DeleteRequest":
	case "ChangeRequestStatus":
	case "GetAllStatuses":	
	case "GetTazminDocTypes":
	case "selectFollowLevels":
	case "SelectContractType":
		case "SelectDomainType":
		
	case "GetRequestParts":
	case "SavePart":
	case "DeletePart":
	case "StartFlow":
	case "GetRequestTotalRemainder":
	case "GetDefrayAmount":
	case "EndRequest":
	case "GetEndDoc":
	case "ReturnEndRequest":
	case "DefrayRequest":	
		
	case "GetInstallments":
	case "ComputeInstallments":
	case "ComputeOldInstallments":
	case "SaveInstallment":
	case "DelayInstallments":
	case "SetHistory":
	case "GetLastFundComment":
	case "SelectReadyToPayParts":
	case "SelectReceivedRequests":
	case "selectRequestStatuses":
	case "GetBackPays":
	case "SaveBackPay":
	case "DeletePay":
	case "RegisterBackPayDoc":
	case "EditBackPayDoc":
	case "GroupSavePay":
	case "GetDelayedInstallments":
	case "GetEndedRequests":
	case "GetPartPayments":
	case "SavePartPayment":
	case "DeletePayment":
	case "RegPayPartDoc":
	case "editPayPartDoc":
	case "RetPayPartDoc":
	case "SelectAllMessages":
	case "saveMessage":
	case "removeMessage":
	case "ConfirmRequest":
	case "GetChequeStatuses":
	case "GetPayTypes":
	case "GetBanks":
	case "GetEvents":
	case "SaveEvents":
	case "DeleteEvents":
	case "GetCosts":
	case "SaveCosts":
	case "DeleteCosts":
	case "GetGuarantors":
	case "SaveGuarantor":
	case "DeleteGuarantor":
	case "GetPureAmount":
	case "emptyDataTable":
	case "ComputeManualInstallments":
	case "GetFollows":
	case "SaveFollows":
	case "DeleteFollows":
	case 'GetFollowStatuses':	
	case "GetFollowTemplates":
	case "SaveFollowTemplates":
	case "DeleteFollowTemplates":
	case "RegisterLetter":
	case "CustomerDefrayRequest":
	case "GetFollowsToDo":	
	case "DoFollow":
		
		$task();
}

function SaveLoanRequest(){
	
	$obj = new LON_requests();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$obj->AgentGuarantee = isset($_POST["AgentGuarantee"]) ? "YES" : "NO";	
	$obj->FundGuarantee = isset($_POST["FundGuarantee"]) ? "YES" : "NO";	
	
	if(isset($_POST["IsLock"]))
		$obj->IsLock = $_POST["IsLock"] == "true" ? "YES" : "NO";
	
	$obj->guarantees = array();
	$arr = array_keys($_POST);
	foreach($arr as $index)
		if(strpos($index, "guarantee") !== false)
			$obj->guarantees[] = str_replace("guarantee_", "", $index);
	$obj->guarantees = implode(",", $obj->guarantees);
	$obj->IsFree = isset($_POST["IsFree"]) ? "YES" : "NO";	
	//------------------------------------------------------
	if(session::IsPortal())
	{
		if($_SESSION["USER"]["IsAgent"] == "YES" || $_SESSION["USER"]["IsSupporter"] == "YES"
				|| $_SESSION["USER"]["IsShareholder"] == "YES")
		{
			$obj->ReqPersonID = $_SESSION["USER"]["PersonID"];
			
			if(isset($_POST["sending"]) &&  $_POST["sending"] == "true")
				$obj->StatusID = LON_REQ_STATUS_SEND;
			else
				$obj->StatusID = LON_REQ_STATUS_RAW;

			$obj->LoanID = Default_Agent_Loan;
		}
		else if($_SESSION["USER"]["IsCustomer"] == "YES")
		{
			if(!isset($obj->LoanPersonID))
				$obj->LoanPersonID = $_SESSION["USER"]["PersonID"];
			$obj->StatusID = LON_REQ_STATUS_SEND;
		}
	}
	else if(empty($obj->RequestID))
	{
		$obj->LoanID = empty($obj->LoanID) ? Default_Agent_Loan : $obj->LoanID;
		$obj->StatusID = LON_REQ_STATUS_RAW;
	}
	if(empty($obj->RequestID))
	{
		$obj->AgentGuarantee = isset($_POST["AgentGuarantee"]) ? "YES" : "NO";
		$obj->FundGuarantee = isset($_POST["FundGuarantee"]) ? "YES" : "NO";
		$result = $obj->AddRequest();
		if($result)
			LON_requests::ChangeStatus($obj->RequestID,$obj->StatusID, "", true);
		else
		{
			echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}
		$loanObj = new LON_loans($obj->LoanID);
		$PartObj = new LON_ReqParts();
		PdoDataAccess::FillObjectByObject($loanObj, $PartObj);
		$PartObj->RequestID = $obj->RequestID;
		$PartObj->PartDesc = "شرایط اولیه";
		$PartObj->FundWage = $loanObj->CustomerWage;
		$PartObj->PartAmount = $obj->ReqAmount;
		$PartObj->PartDate = PDONOW;
		$PartObj->AddPart();		
	}
	else
	{
		$preObj = new LON_requests($obj->RequestID);
		$result = $obj->EditRequest();
		if($result)
			LON_requests::ChangeStatus($obj->RequestID,$obj->StatusID, "", true);
		else
		{
			echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}
		
		if($preObj->IsLock != $obj->IsLock)
			LON_requests::ChangeStatus($obj->RequestID,$preObj->StatusID, 
			($obj->IsLock == "YES" ? "قفل کردن وام" : "باز کردن قفل وام"), true);
	}

	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, $obj->RequestID);
	die();
}

function SelectMyRequests(){
	
	$where = "1=1 ";
	if($_SESSION["USER"]["IsAgent"] == "YES" && $_REQUEST["mode"] == "agent")
		$where .= " AND r.ReqPersonID=" . $_SESSION["USER"]["PersonID"];
	if($_SESSION["USER"]["IsCustomer"] == "YES" && $_REQUEST["mode"] == "customer")
		$where .= " AND r.LoanPersonID=" . $_SESSION["USER"]["PersonID"];
	if($_SESSION["USER"]["IsShareholder"] == "YES" && $_REQUEST["mode"] == "shareholder")
		$where .= " AND r.ReqPersonID=" . $_SESSION["USER"]["PersonID"];
	$param = array();
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
		$field = $field == "ReqFullname" ? "concat_ws(' ',p1.fname,p1.lname,p1.CompanyName)" : $field;
		$field = $field == "LoanFullname" ? "concat_ws(' ',p2.fname,p2.lname,p2.CompanyName)" : $field;
        $where .= ' and ' . $field . ' like :fld';
        $param[':fld'] = '%' . $_REQUEST['query'] . '%';
    }
	
	$dt = LON_requests::SelectAll($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
	$count = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	if($_SESSION["USER"]["IsCustomer"] == "YES" && $_REQUEST["mode"] == "customer")
	{
		for($i=0; $i<count($dt); $i++)
		{
			if($dt[$i]["PartID"]*1 == 0)
				continue;
			$temp = array();
			$ComputeArr = LON_Computes::ComputePayments($dt[$i]["RequestID"], $temp);
			$dt[$i]["CurrentRemain"] = LON_Computes::GetCurrentRemainAmount($dt[$i]["RequestID"], $ComputeArr);
			$dt[$i]["TotalRemain"] = LON_Computes::GetTotalRemainAmount($dt[$i]["RequestID"], $ComputeArr);
		}
	}
	
	echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
	die();
}

function SelectAllRequests2(){
	
	$params = array();
	$query = "select p.*,r.ReqPersonID,r.IsEnded, concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) loanFullname,
				i.InstallmentAmount,LoanDesc,concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) ReqFullName
		from LON_requests r 
		join LON_ReqParts p on(r.RequestID=p.RequestID AND IsHistory='NO')
		join BSC_persons p1 on(LoanPersonID=PersonID)
		left join BSC_persons p2 on(p2.PersonID=ReqPersonID)
		left join LON_installments i on(i.history='NO' AND i.IsDelayed='NO' AND i.RequestID=p.RequestID)		
		left join LON_loans using(LoanID)
		where 1=1";
	if(!empty($_REQUEST["query"]))
	{
		$query .= " AND ( concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) like :f or r.RequestID = :f1)";
		$params[":f"] = "%" . $_REQUEST["query"] . "%";
		$params[":f1"] = $_REQUEST["query"] ;
	}
	
	if(session::IsPortal())
		$query .= " AND ( LoanPersonID=" . $_SESSION["USER"]["PersonID"] . 
			" or ReqPersonID  = " . $_SESSION["USER"]["PersonID"] . " )";
	
	$query .= " group by r.RequestID";
	
	$dt = PdoDataAccess::runquery_fetchMode($query, $params);
	
	print_r(ExceptionHandler::PopAllExceptions());
	
	$cnt = $dt->rowCount();
	if(!empty($_REQUEST["limit"]))
		$dt = PdoDataAccess::fetchAll($dt, $_REQUEST["start"], $_REQUEST["limit"]);
	else
		$dt = $dt->fetchAll();
	
	//--------------- remain of each loan ------------------
	for($i=0; $i<count($dt);$i++)
		$dt[$i]["totalRemain"] = LON_Computes::GetTotalRemainAmount($dt[$i]["RequestID"]);
	//-------------------------------------------------------
	
	echo dataReader::getJsonData($dt, $cnt, $_GET["callback"]);
	die();
}

function MakeRequestsWhere(&$where, &$whereParam){

	foreach($_POST as $key => $value)
	{
		if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
				$value === "" || 
				
				strpos($key, "combobox") !== false || 
				strpos($key, "rpcmp") !== false ||
				strpos($key, "checkcombo") !== false || 
				strpos($key, "treecombo") !== false || 
				strpos($key, "reportcolumn_fld") !== false || 
				strpos($key, "reportcolumn_ord") !== false)
			continue;
		
		if($key == "SubAgentID")
		{
			InputValidation::validate($value, InputValidation::Pattern_NumComma);
			$where .= " AND SubAgentID in(" . $value . ")";
			continue;
		}
		if($key == "StatusID")
		{
			InputValidation::validate($value, InputValidation::Pattern_NumComma);
			$where .= " AND StatusID in(" . $value . ")";
			continue;
		}
		if($key == "HasContractDoc")
		{
			$where .= " AND cd.DocID>0";
			continue;
		}
		if($key == "HasAllocDoc")
		{
			$where .= " AND ad.DocID>0";
			continue;
		}
		
		$prefix = "";
		switch($key)
		{
			case "fromReqDate":
			case "toReqDate":
			case "fromPartDate":
			case "toPartDate":
			case "fromEndDate":
			case "toEndDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
				break;
			case "fromReqAmount":
			case "toReqAmount":
			case "fromPartAmount":
			case "toPartAmount":
				$value = preg_replace('/,/', "", $value);
				break;
		}
		if(strpos($key, "from") === 0)
			$where_temp = " AND " . $prefix . substr($key,4) . " >= :$key";
		else if(strpos($key, "to") === 0)
			$where_temp = " AND " . $prefix . substr($key,2) . " <= :$key";
		else
			$where_temp = " AND " . $prefix . $key . " = :$key";

		$where .= $where_temp;
		$whereParam[":$key"] = $value;
	}
}	

function SelectAllRequests(){
	
	$param = array();
	$where = "1=1 ";
	if(!empty($_REQUEST["RequestID"]))
	{
		$where .= " AND r.RequestID=:r";
		$param[":r"] = $_REQUEST["RequestID"];
	}
		
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
		$field = $field == "ReqFullname" ? "concat_ws(' ',p1.fname,p1.lname,p1.CompanyName)" : $field;
		$field = $field == "LoanFullname" ? "concat_ws(' ',p2.fname,p2.lname,p2.CompanyName,BorrowerDesc)" : $field;
		$field = $field == "StatusDesc" ? "bi.InfoDesc" : $field;
		$field = $field == "RequestID" ? "r.RequestID" : $field;
		
		if($field == "r.RequestID")
		{
			$where .= ' and ' . $field . ' = :fld';
	        $param[':fld'] = $_REQUEST['query'];
		}
		else
		{
			$where .= ' and ' . $field . ' like :fld';
	        $param[':fld'] = '%' . $_REQUEST['query'] . '%';
		}
    }
	if(!empty($_REQUEST['query']) && empty($_REQUEST["fields"]))
	{
		$where .= " AND ( concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) like :f or r.RequestID = :f1 )";
		$param[":f"] = "%" . $_REQUEST["query"] . "%";
		$param[":f1"] = $_REQUEST["query"] ;
	}
	if(!empty($_REQUEST["IsEnded"]))
	{
		$where .= " AND IsEnded = :e "; 
		$param[":e"] = $_REQUEST["IsEnded"];
	}
	if(!empty($_REQUEST["IsConfirm"]))
	{
		$where .= " AND r.IsConfirm = :e "; 
		$param[":e"] = $_REQUEST["IsConfirm"];
	}
	
	//---------------- filter -------------------
	MakeRequestsWhere($where, $param);
	//-------------------------------------------
	
	$where .= dataReader::makeOrder();
	$dt = LON_requests::SelectAll($where, $param);
	//print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();
	$count = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);	
	echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
	die();
}

function Selectguarantees(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=8 AND IsActive='YES' and param1=1");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function DeleteRequest(){
	
	$res = LON_requests::DeleteRequest($_POST["RequestID"]);
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($res, !$res ? ExceptionHandler::GetExceptionsToString() : "");
	die();
}

function ChangeRequestStatus(){
	
	if($_POST["StatusID"] == "11")
	{
		$result = LON_requests::ChangeStatus($_POST["RequestID"],$_POST["StatusID"],$_POST["StepComment"], true);
		$result = LON_requests::ChangeStatus($_POST["RequestID"],1,$_POST["StepComment"], false, null, true);
		Response::createObjectiveResponse($result, "");
		die();
	}
	
	$result = LON_requests::ChangeStatus($_POST["RequestID"],$_POST["StatusID"],$_POST["StepComment"]);
	Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function GetAllStatuses(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=5 and param1=0");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function GetTazminDocTypes(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=8 and param1=1");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function selectFollowLevels(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=98");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SelectContractType(){ 
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=106");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}
function SelectDomainType(){

    $temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=107");
    echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
    die();
}


//------------------------------------------------

function GetRequestParts(){
	
	if(!isset($_REQUEST["RequestID"] ))
	{
		echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
		die();
	}
	
	$RequestID = $_REQUEST["RequestID"];
	
	$where = "";
	if(!empty($_REQUEST["IsLast"]))
		$where .= " AND  IsHistory='NO' ";
	
	$dt = LON_ReqParts::SelectAll("RequestID=?". $where, array($RequestID));
	
	$CostCode_commitment = 165; // 200-05
	for($i=0; $i < count($dt);$i++)
	{
		$temp = PdoDataAccess::runquery("select ifnull(sum(CreditorAmount),0)
			from ACC_DocItems join ACC_docs using(DocID) where 
			 CostID=? AND SourceType=" . DOCTYPE_LOAN_PAYMENT . " AND 
			SourceID1=? AND SourceID2=? AND StatusID = " . ACC_STEPID_CONFIRM, 
			array($CostCode_commitment, $dt[$i]["RequestID"], $dt[$i]["PartID"]));
		$dt[$i]["IsPaid"] = $temp[0][0] == $dt[$i]["PartAmount"] ? "YES" : "NO"; 		
		
		$temp = PdoDataAccess::runquery("select count(*)
			from ACC_DocItems join ACC_docs using(DocID) where 
			 CostID=? AND SourceType=" . DOCTYPE_LOAN_PAYMENT . "  
				 AND StatusID=".ACC_STEPID_CONFIRM." AND SourceID1=? AND SourceID2=? ", 
			array($CostCode_commitment, $dt[$i]["RequestID"], $dt[$i]["PartID"]));
		$dt[$i]["IsDocRegister"] = $temp[0][0]*1 > 0 ? "YES" : "NO"; 	
		
		$result = WFM_FlowRows::GetFlowInfo(FLOWID_LOAN, $dt[$i]["PartID"]);
		$dt[$i]["IsStarted"] = $result["IsStarted"] ? "YES" : "NO";
		$dt[$i]["IsEnded"] = $result["IsEnded"] ? "YES" : "NO";
		$dt[$i]["SendEnable"] = $result["SendEnable"] ? "YES" : "NO";		
		
		//--------------- computes ------------------
		$installments = LON_installments::GetValidInstallments($dt[$i]["RequestID"]);
		if(count($installments) > 0)
		{
			$dt[$i]["AllPay"] = $installments[0]["InstallmentAmount"];
			$dt[$i]["LastPay"] = $installments[count($installments)-1]["InstallmentAmount"];
		}
		else
		{
			$dt[$i]["AllPay"] = 0;
			$dt[$i]["LastPay"] = 0;			
		}
		$PartObj = new LON_ReqParts($dt[$i]["PartID"]);
		
		$result = LON_requests::GetWageAmounts($PartObj->RequestID, $PartObj);
		$dt[$i]["TotalCustomerWage"] = $result["CustomerWage"];
		$dt[$i]["TotalAgentWage"] = $result["AgentWage"];
		$dt[$i]["TotalFundWage"] = $result["FundWage"];

		$dt[$i]["SUM_NetAmount"] = LON_requests::GetPayedAmount($PartObj->RequestID);
		
	}
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();	
}

function SavePart(){
	
	$msg = "";
	$obj = new LON_ReqParts();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	//---------------- copy payments for test loan -----------------------------
	if($obj->RequestID == 0)
	{
		PdoDataAccess::runquery("delete from LON_payments where RequestID=0");
		$pobj = new LON_payments();
		$pobj->PayAmount = $_POST["PartAmount"];
		$pobj->PayDate = $_POST["PartDate"];
		$pobj->RequestID = 0;
		$pobj->PayID = "";
		$pobj->Add();
		
		$result = $obj->EditPart();
		LON_installments::ComputeInstallments($obj->RequestID);
		
		echo Response::createObjectiveResponse(true, "");
		die();
	}
	
	//--------------------------------------------------------------------------
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if($obj->PartID*1 > 0)
		$result = $obj->EditPart($pdo);
	else
		$result = $obj->AddPart($pdo);
	
	if(!$result)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	
	//--------------------------------------------------------------------------
	
	$parts = LON_ReqParts::SelectAll("RequestID=? AND PartID<>?", array($obj->RequestID, $obj->PartID));
	$firstPart = count($parts) > 0 ? false : true;
	
	$OldDocID = 0;
	if(!$firstPart)
	{
		$temp = PdoDataAccess::runquery("select DocID,LocalNo,CycleDesc,StatusID 
			from ACC_DocItems 
			join ACC_docs using(DocID)
			join COM_events using(EventID)
			join ACC_cycles using(CycleID)
			where EventType='" . EVENTTYPE_LoanChange ."' AND SourceID1=? AND SourceID2=?",
			array($obj->RequestID, $obj->PartID));

		if(count($temp) > 0 && $temp[0]["StatusID"] != ACC_STEPID_RAW)
		{
			$pdo->rollBack();
			echo Response::createObjectiveResponse(false, "سند اختلاف به شماره ".$temp[0]["LocalNo"]."در ".
					$temp[0]["CycleDesc"]." تایید شده و قادر به صدور مجدد نمی باشید");
			die();
		} 
		$OldDocID = count($temp)>0 ? $temp[0]["DocID"] : 0;
		
		foreach($parts as $row)
		{
			$partobj = new LON_ReqParts($row["PartID"]);
			if($partobj->IsHistory == "NO")
			{
				$partobj->IsHistory = "YES";
				$partobj->EditPart($pdo);
			}
		}
		LON_installments::ComputeInstallments($obj->RequestID, $pdo, true);
		$DiffDoc = LON_difference::RegisterDiffernce($obj->RequestID, $pdo, (int)$OldDocID);
		if($DiffDoc == false)
		{
			$pdo->rollBack(); 
			echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}
		$msg = "سند اختلاف با شماره " . $DiffDoc->LocalNo . " با موفقیت صادر گردید.";
	}
	
	//--------------------------------------------------------------------------
	$pdo->commit();
	echo Response::createObjectiveResponse(true, $msg);
	die();
}

function DeletePart(){
	
	$obj = new LON_ReqParts($_POST["PartID"]);
	
	$dt = PdoDataAccess::runquery("select * from ACC_DocItems join ACC_docs using(DocID)
		where SourceType=" . DOCTYPE_LOAN_DIFFERENCE . "
		AND SourceID1=? AND SourceID2=?", array($obj->RequestID, $obj->PartID));
	
	if(count($dt) > 0 && $dt[0]["StatusID"] != ACC_STEPID_RAW)
	{
		echo Response::createObjectiveResponse(false, "سند اختلاف تایید شده و قادر به حذف نمی باشید");
		die();
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if(count($dt) > 0)
	{
		if(!ACC_docs::Remove($dt[0]["DocID"], $pdo))
		{
			echo Response::createObjectiveResponse(false, "خطا در حذف سند");
			die();
		}
	}
	if(!LON_ReqParts::DeletePart($_POST["PartID"], $pdo))
	{
		echo Response::createObjectiveResponse(false, "خطا در حذف شرایط");
		die();
	}
	
	$dt = PdoDataAccess::runquery("select PartID from LON_ReqParts where RequestID=? order by PartID desc", 
			array($obj->RequestID), $pdo);
	if(count($dt)> 0)
	{
		$obj2 = new LON_ReqParts($dt[0]["PartID"]);
		$obj2->IsHistory = "NO";
		$obj2->EditPart($pdo);
	}
	//ComputeInstallments($obj->RequestID, true, $pdo);
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function StartFlow(){
	
	$PartID = $_REQUEST["PartID"];
	$result = WFM_FlowRows::StartFlow(1, $PartID);
	echo Response::createObjectiveResponse($result, "");
	die();
}

function GetRequestTotalRemainder(){
	
	$remain = 0;
	$RequestID = $_REQUEST["RequestID"];
	$remain = LON_Computes::GetTotalRemainAmount($RequestID);	
	echo Response::createObjectiveResponse(true, $remain);
	die();
}

function EndRequest(){
	
	$RequestID = $_POST["RequestID"];
	$ReqObj = new LON_requests($RequestID);
		
	$ReqObj->IsEnded = "YES";
	$ReqObj->StatusID = LON_REQ_STATUS_ENDED;
	$ReqObj->EndDate = PDONOW;
	if(!$ReqObj->EditRequest())
	{
		echo Response::createObjectiveResponse(false, "خطا در تغییر درخواست");
		die();
	}
	LON_requests::ChangeStatus($ReqObj->RequestID,$ReqObj->StatusID,"", false);		
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetEndDoc(){
	
	$RequestID = $_POST["RequestID"];
	
	$dt = PdoDataAccess::runquery("select d.DocID,LocalNo,StatusID,CycleDesc
		from ACC_DocItems d join ACC_docs using(DocID) join ACC_cycles using(CycleID)
		where DocType=" . DOCTYPE_END_REQUEST . " AND SourceID2=?",
			array($RequestID));
	
	if(count($dt) > 0)
	{
		if($dt[0]["StatusID"] != ACC_STEPID_RAW)
		{
			Response::createObjectiveResponse (false, "سند مربوط به خاتمه وام با شماره " . $dt[0]["LocalNo"] . " در " . 
					$dt[0]["CycleDesc"] . " تایید شده و قادر به برگشت از خاتمه وام نمی باشید.");
			die();
		}
		
		Response::createObjectiveResponse (true, "سند مربوط به خاتمه وام با شماره " . $dt[0]["LocalNo"] . " در " . 
				$dt[0]["CycleDesc"] . " صادر شده است. آیا مایله به برگشت خاتمه وام و حذف سند مربوطه می باشید؟");
		die();
	}
	Response::createObjectiveResponse (true, "سند خاتمه برای این وام یافت نشد. آیا مایل به برگشت از خاتمه می باشید؟");
	die();
}

function ReturnEndRequest(){
	
	$ReqObj = new LON_requests($_POST["RequestID"]);
	
	$LocalNo = ExecuteEvent::GetRegisteredDoc(EVENT_LOAN_END, array($ReqObj->RequestID));
	if($LocalNo !== false)
	{
		echo Response::createObjectiveResponse(false, "تا زمانیکه سند خاتمه وام به شماره " . $LocalNo . 
				" باطل نگردد قادر به برگشت خاتمه وام نمی باشید");
		die();
	}
	
	$ReqObj->IsEnded = "NO";
	$ReqObj->StatusID = LON_REQ_STATUS_DEFRAY;
	$ReqObj->EndDate = "";
	if(!$ReqObj->EditRequest())
	{
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	LON_requests::ChangeStatus($ReqObj->RequestID, $ReqObj->StatusID);
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetDefrayAmount(){
	
	$RequestID = (int)$_REQUEST["RequestID"];
	$computeArr = LON_Computes::ComputePayments($RequestID);
	$DefrayAmount = LON_requests::GetDefrayAmount($RequestID, $computeArr);
	echo Response::createObjectiveResponse(true, $DefrayAmount);
	die();
}

function DefrayRequest(){
	
	$RequestID = $_POST["RequestID"];
	$ReqObj = new LON_requests($RequestID);
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$remain = LON_Computes::GetTotalRemainAmount($RequestID);
	if($remain > 0)
	{		
		echo Response::createObjectiveResponse(false, "مانده وام " . number_format($remain) . " ریال می باشد و تا زمانی که صفر نشود قادر به تسویه وام نمی باشید");
		die();
	}
	
	$ReqObj->DefrayDate = PDONOW;
	if(!$ReqObj->EditRequest($pdo))
	{
		echo Response::createObjectiveResponse(false, "خطا در تغییر درخواست");
		die();
	}
	
	LON_requests::ChangeStatus($ReqObj->RequestID,LON_REQ_STATUS_DEFRAY,"", false, $pdo);
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
} 

//--------------------------------------------------

function GetInstallments(){
	
	$RequestID = $_REQUEST["RequestID"];
	$temp = LON_installments::SelectAll(" i.RequestID=?", array($RequestID));
	
	$refArray = array();
	$ComputeArr = LON_Computes::ComputePayments($RequestID);
	foreach($ComputeArr as $row)
		if($row["type"] == "installment")
			$refArray[ $row["id"] ] = &$row;

	for($i=0; $i<count($temp); $i++)
	{
		if(isset($refArray[ $temp[$i]["InstallmentID"] ]))
		{
			$src = $refArray[  $temp[$i]["InstallmentID"]  ];
			$temp[$i]["remain"] = $src["remain_pure"] + $src["remain_wage"] + 
					$src["remain_late"] + $src["remain_pnlt"];
		}
	}
	
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function ComputeInstallments($RequestID = "", $returnMode = false, $pdo2 = null, $IsLastest = true){
	
	$RequestID = empty($RequestID) ? (int)$_REQUEST["RequestID"] : (int)$RequestID;
	
	//............... control all pays register .................
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	$dt = PdoDataAccess::runquery("select sum(PayAmount) sumamount from LON_payments where requestID=?",$RequestID);
	if($partObj->PartAmount*1 <> $dt[0][0]*1)
	{
		echo Response::createObjectiveResponse(false, "تا زمانی که کلیه مراحل پرداخت را وارد نکرده اید قادر به محاسبه اقساط نمی باشید");
		die();
	}
	//...........................................................
	
	if(isset($_REQUEST["IsLastest"]))
		$IsLastest = $_REQUEST["IsLastest"] == "true" ? true : false;
	
	$result = LON_installments::ComputeInstallments($RequestID, null, $IsLastest);
	if(!$result)
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
	else
		echo Response::createObjectiveResponse(true, "");
	die();
}

function ComputeOldInstallments(){
	
	$RequestID = (int)$_POST["RequestID"];
	$dt = PdoDataAccess::runquery("select * from `TABLE 235` where id=?", array($RequestID)); 
	if(count($dt) == 0)
	{
		echo Response::createObjectiveResponse(false, "مبلغ محاسبات قدیم موجود نمی باشد");
		die();
	}
	
	$oldAmount = $dt[0]["amount"];
	
	$installmentArray = array();
	$temp = PdoDataAccess::runquery("select * from LON_installments where RequestID=? AND IsDelayed='NO' AND history='NO'", array($RequestID));
	for ($i = 0; $i < count($temp); $i++) {
		$installmentArray[] = array(
			"InstallmentAmount" => $oldAmount,
			"InstallmentDate" => DateModules::miladi_to_shamsi($temp[$i]["InstallmentDate"])
		);
	}
	$installmentArray = ExtraModules::array_sort($installmentArray, "InstallmentDate");
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	$installmentArray = LON_Computes::ComputeInstallment($partObj, $installmentArray, "", "NO");

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	PdoDataAccess::runquery("delete from LON_installments "
			. "where RequestID=? AND history='NO' AND IsDelayed='NO'", array($RequestID), $pdo);

	for($i=0; $i < count($installmentArray); $i++)
	{
		$obj = new LON_installments();
		$obj->RequestID = $RequestID;
		$obj->InstallmentDate = DateModules::shamsi_to_miladi($installmentArray[$i]["InstallmentDate"]);
		$obj->InstallmentAmount = $installmentArray[$i]["InstallmentAmount"];
		$obj->wage = isset($installmentArray[$i]["wage"]) ? $installmentArray[$i]["wage"] : 0;
		$obj->PureWage = isset($installmentArray[$i]["PureWage"]) ? $installmentArray[$i]["PureWage"] : 0;
		if(!$obj->AddInstallment($pdo))
		{
			$pdo->rollBack();
			print_r(ExceptionHandler::PopAllExceptions());
			echo "false";
		}
	}

	$pdo->commit();	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SaveInstallment(){
	
	$obj = new LON_installments();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	$result = $obj->EditInstallment();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DelayInstallments(){
	
	$RequestID = $_POST["RequestID"];
	$InstallmentID = $_POST["InstallmentID"];
	$newDate = $_POST["newDate"];
	$newAmount = $_POST["newAmount"]*1;
	
	$ReqObj = new LON_requests($RequestID);
	$PartObj = LON_ReqParts::GetValidPartObj($RequestID);
	$DocID= "";
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if($_POST["IsRemainCompute"] == "0")
	{
		$dt = LON_installments::SelectAll("r.RequestID=? AND InstallmentID>=?", 
			array($RequestID, $InstallmentID));
	}
	else
	{
		$dt = LON_Computes::ComputePayments($RequestID);
	}
	
	$prevExtraAmount = 0;
	for($i=0; $i<count($dt); $i++)
	{
		if($dt[$i]["InstallmentID"] == $InstallmentID)
		{
			$newDate = DateModules::shamsi_to_miladi($newDate, "-");
			$days = DateModules::GDateMinusGDate($newDate, $dt[$i]["InstallmentDate"]);
		}
		if($dt[$i]["InstallmentID"] < $InstallmentID)
			continue;
		if($_POST["ContinueToEnd"] == "0" && $dt[$i]["InstallmentID"] > $InstallmentID)
		{
			if($prevExtraAmount > 0)
			{
				$obj = new LON_installments($dt[$i]["InstallmentID"]);
				$obj->InstallmentAmount = $obj->InstallmentAmount*1 + $prevExtraAmount;
				if(!$obj->EditInstallment($pdo))
				{
					$pdo->rollBack ();
					echo Response::createObjectiveResponse(false, "1");
					die();
				}
				$prevExtraAmount = 0;
			}
			break;
		}
				
		$obj = new LON_installments($dt[$i]["InstallmentID"]);
		$obj->IsDelayed = "YES";
		if(!$obj->EditInstallment($pdo))
		{
			$pdo->rollBack ();
			echo Response::createObjectiveResponse(false, "1");
			die();
		}
		//...........................................

		$obj2 = new LON_installments();
		$obj2->RequestID = $RequestID;
		$obj2->InstallmentDate = DateModules::AddToGDate($dt[$i]["InstallmentDate"], $days);

		$ComputeInstallmentAmount = $dt[$i]["InstallmentAmount"]*1;
		if($_POST["IsRemainCompute"] == "1")
		{
			$ComputeInstallmentAmount = $dt[$i]["TotalRemainder"]*1;
			if($dt[$i]["TotalRemainder"]*1 > $dt[$i]["InstallmentAmount"]*1)
				$ComputeInstallmentAmount = $dt[$i]["InstallmentAmount"]*1;
		}
		
		if($dt[$i]["InstallmentID"] == $InstallmentID && $newAmount != "" && $newAmount <> $dt[$i]["InstallmentAmount"])
		{
			$extraWage = 0;
			$extraWage = round($ComputeInstallmentAmount*$PartObj->CustomerWage*$days/36500);
			$days2 = DateModules::GDateMinusGDate($dt[$i+1]["InstallmentDate"], $dt[$i]["InstallmentDate"]);
			$extraWage += round( ($ComputeInstallmentAmount-$newAmount)*$PartObj->CustomerWage*$days2/36500 );
			$prevExtraAmount = $ComputeInstallmentAmount-$newAmount;
			$obj2->InstallmentAmount = $newAmount + $extraWage;
		}
		else
		{
			$extraWage = round($ComputeInstallmentAmount*$PartObj->CustomerWage*$days/36500);
			$obj2->InstallmentAmount = $dt[$i]["InstallmentAmount"]*1 + $extraWage + $prevExtraAmount;
			$prevExtraAmount = 0;
		}
		if(!$obj2->AddInstallment($pdo))
		{
			$pdo->rollBack ();
			echo Response::createObjectiveResponse(false, "2");
			die();
		}

		/*$DocID = RegisterChangeInstallmentWage($DocID, $ReqObj, $PartObj, 
					$obj, $obj2->InstallmentDate, $extraWage, $pdo);*/
	}

	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack ();
		
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();	
}

function SetHistory(){
	
	$obj = new LON_installments($_POST["InstallmentID"]);
	$obj->history = "YES";
	$result = $obj->EditInstallment();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//-------------------------------------------------

function GetLastFundComment(){
	
	if(empty($_POST["RequestID"]))
	{
		echo Response::createObjectiveResponse(true, $comment);
		die();
	}
	$RequestID = $_POST["RequestID"];
	
	$dt = PdoDataAccess::runquery("select * from LON_ReqFlow 
		where RequestID=? AND StatusID=60 order by FlowID desc", array($RequestID));
	
	$comment = count($dt)>0 ? $dt[0]["StepComment"] : '';
	echo Response::createObjectiveResponse(true, $comment);
	die();
}

//-------------------------------------------------

function SelectReadyToPayParts($returnCount = false){
	
	$dt = PdoDataAccess::runquery("select max(StepID) from WFM_FlowSteps where FlowID=1 AND IsActive='YES'");

	$dt = PdoDataAccess::runquery("
		select r.RequestID,PartAmount,PartDesc,PartDate,
			if(p1.IsReal='YES',concat(p1.fname, ' ', p1.lname),p1.CompanyName) ReqFullname,
			if(p2.IsReal='YES',concat(p2.fname, ' ', p2.lname),p2.CompanyName) LoanFullname
			
		from WFM_FlowRows fr
		join WFM_FlowSteps using(StepRowID)
		join LON_ReqParts on(PartID=ObjectID)
		join LON_requests r using(RequestID)
		left join LON_payments pay on(r.RequestID=pay.RequestID)
		left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
		left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
		where fr.FlowID=1 AND StepID=? AND ActionType='CONFIRM' 
			AND r.StatusID=" . LON_REQ_STATUS_CONFIRM . " AND pay.RequestID is null
		group by r.RequestID" . dataReader::makeOrder(),
		array($dt[0][0]));
	if($returnCount)
		return count($dt);

	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SelectReceivedRequests($returnCount = false){
	
	$where = "StatusID in (10,50)";
	 
	$branches = FRW_access::GetAccessBranches();
	$where .= " AND BranchID in(" . implode(",", $branches) . ")";
	
	$dt = LON_requests::SelectAll($where . dataReader::makeOrder());
	
	if($returnCount)
		return $dt->rowCount();
	
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function selectRequestStatuses(){
	$dt = PdoDataAccess::runquery("select * from BaseInfo where typeID=5 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

//------------------------------------------------

function GetBackPays(){
	
	$dt = LON_BackPays::SelectAll("p.RequestID=? " . dataReader::makeOrder() , array($_REQUEST["RequestID"]));
	print_r(ExceptionHandler::PopAllExceptions());
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveBackPay(){
	
	$obj = new LON_BackPays();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->PayType == "9")
		$obj->ChequeStatus = 1;
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if(empty($obj->BackPayID))
	{
		$result = $obj->Add($pdo);
		if($obj->PayType == "9")
			RegisterOuterCheque("",$obj,$pdo);
	}
	else
		$result = $obj->Edit($pdo);
	
	if(!$result)
	{
		$pdo->rollback();
		echo Response::createObjectiveResponse(false, "خطا در ثبت ردیف پرداخت");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeletePay(){
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$PayObj = new LON_BackPays($_POST["BackPayID"]);
	
	if($PayObj->PayType == "9" && $PayObj->ChequeStatus != "1")
	{
		echo Response::createObjectiveResponse(false, "چک مربوطه تغییر وضعیت یافته است");
		die();
	}
	
	if(!ReturnCustomerPayDoc($PayObj, $pdo))
	{
		//print_r(ExceptionHandler::PopAllExceptions());
		//$pdo->rollBack();		
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	
	if($PayObj->PayType == "9")
	{
		if(!ReturnOuterCheque($PayObj, $pdo))
		{
			$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "خطا در حذف سند انتظامی چک");
		die();
		}
	}
	
	if(!LON_BackPays::DeletePay($_POST["BackPayID"], $pdo))
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "خطا در حذف ردیف پرداخت");
		die();
	}
	
	PdoDataAccess::runquery("delete from LON_BackPays where RequestID=? AND PayType=? AND PayBillNo=?",
			array($PayObj->RequestID, BACKPAY_PAYTYPE_CORRECT, $PayObj->BackPayID), $pdo);
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "خطا در حذف ردیف های اصلاحی");
		die();
	}
			
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function RegisterBackPayDoc(){

	$obj = new LON_BackPays($_POST["BackPayID"]);
	$ReqObj = new LON_requests($obj->RequestID);
	$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if($PersonObj->IsSupporter == "YES")
		$result = RegisterSHRTFUNDCustomerPayDoc(null, $obj, 
			$_POST["CostID"], 
			$_POST["TafsiliID"], 
			$_POST["TafsiliID2"], 
			$pdo);
	else
		$result = RegisterCustomerPayDoc(null, $obj, 
			$_POST["CostID"], 
			$_POST["TafsiliID"], 
			$_POST["TafsiliID2"], 
			$pdo);
	if(!$result)
	{
		$pdo->rollback();
		$msg = ExceptionHandler::GetExceptionsToString();
		$msg = is_array($msg) ? "خطا در صدور سند حسابداری" : $msg;
		echo Response::createObjectiveResponse(false, $msg);
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function EditBackPayDoc(){
	
	$obj = new LON_BackPays($_POST["BackPayID"]);
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$DocID = LON_BackPays::GetAccDoc($obj->BackPayID);
	if($DocID == 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه یافت نشد");
		die();
	}
	$DocObj = new ACC_docs($DocID);
	
	//-----------------------------------------------------------------
	if(!ReturnCustomerPayDoc($obj, $pdo, true))
	{
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	
	$ReqObj = new LON_requests($obj->RequestID);
	$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
	if($PersonObj->IsSupporter == "YES")
		$result = RegisterSHRTFUNDCustomerPayDoc($DocObj, $obj, 
				$_POST["CostID"], 
				$_POST["TafsiliID"], 
				$_POST["TafsiliID2"], 
				$pdo);
	else
		$result = RegisterCustomerPayDoc($DocObj, $obj, 
				$_POST["CostID"], 
				$_POST["TafsiliID"], 
				$_POST["TafsiliID2"], 
				$pdo);
	
	if(!$result)
	{
		$pdo->rollback();
		//print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GroupSavePay(){
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$parts = json_decode($_POST["parts"]);
	
	$FirstPay = true;
	$DocObj = null;
	$sumAmount = 0;
	foreach($parts as $partStr)
	{
		$arr = preg_split("/_/", $partStr);
		$RequestID = $arr[0];
		$PayAmount = $arr[1];

		$obj = new LON_BackPays();
		PdoDataAccess::FillObjectByArray($obj, $_POST);
		$obj->RequestID = $RequestID;
		$obj->PayAmount = $PayAmount;
		$obj->IsGroup = "YES";
		if($obj->PayType == "9")
			$obj->ChequeStatus = 3;
		$obj->Add($pdo);
		
		$ReqObj = new LON_requests($RequestID);
		$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
		if($PersonObj->IsSupporter == "YES")
			$result = RegisterSHRTFUNDCustomerPayDoc($DocObj, $obj, 
				$_POST["CostID"], 
				$_POST["TafsiliID"], 
				$_POST["TafsiliID2"], 
				$pdo, true);
		else
			$result = RegisterCustomerPayDoc($DocObj, $obj, 
				$_POST["CostID"], 
				$_POST["TafsiliID"], 
				$_POST["TafsiliID2"], 
				$pdo, true);
		
		if(!$result)
		{
			$pdo->rollback();
			print_r(ExceptionHandler::PopAllExceptions());
			echo Response::createObjectiveResponse(false, "خطا در صدور سند حسابداری");
			die();
		}
		
		if($FirstPay)
		{
			$DocID = LON_BackPays::GetAccDoc($obj->BackPayID, $pdo);
			$DocObj = new ACC_docs($DocID, $pdo);
			$FirstPay = false;			
		}
		
		$sumAmount += $PayAmount*1;
	}
	
	if($_POST["PayType"] == "9")
	{
		$obj = new LON_BackPays();
		PdoDataAccess::FillObjectByArray($obj, $_POST);
		$obj->ChequeStatus = "1";
		$obj->PayAmount = $sumAmount;
		
		$result = RegisterOuterCheque($DocID,$obj,$pdo);
		
		$obj->ChequeStatus = "3";
		$result = RegisterOuterCheque($DocID,$obj,$pdo);
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

//------------------------------------------------

function GetEndedRequests(){
	
	$query = "select rp.RequestID,ReqDate,RequestID,concat_ws(' ',fname,lname,CompanyName) LoanPersonName
			from LON_ReqParts rp
			join LON_requests using(RequestID)
			join BSC_persons on(LoanPersonID=PersonID)
						
			where IsEnded='NO' 
			group by rp.RequestID
			order by rp.RequestID";
	$dt = PdoDataAccess::runquery_fetchMode($query);
	
	$result = array();
	while($row = $dt->fetch())
	{
		$remain = LON_Computes::GetTotalRemainAmount($row["RequestID"]);
		if($remain == 0)
			$result[] = $row;
	}
	
	$cnt = count($result);
	$result = array_slice($result, $_REQUEST["start"], $_REQUEST["limit"]);
	
	echo dataReader::getJsonData($result, $cnt, $_GET["callback"]);
	die();
}

//-------------------------------------------------

function GetPartPayments(){
	ini_set("display_errors", "On");
	$dt = LON_payments::Get(" AND p.RequestID=? ", array($_REQUEST["RequestID"]),dataReader::makeOrder());
	print_r(ExceptionHandler::PopAllExceptions());
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SavePartPayment(){
	
	$obj = new LON_payments();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->PayID > 0)
		$result = $obj->Edit();
	else
		$result = $obj->Add();
	
	//---------------- compute initial wage --------------------
	$partObj = LON_ReqParts::GetValidPartObj($obj->RequestID);
	if($partObj->ComputeMode == "NOAVARI" && 
		($partObj->WageReturn != "INSTALLMENT" || $partObj->AgentReturn != "INSTALLMENT"))
	{
		if($partObj->AgentReturn == "CUSTOMER")
		{
			$AgentWage = $obj->PayAmount*($partObj->CustomerWage-$partObj->FundWage)/100;
		}
		if($partObj->FundWage*1 > 0 && $partObj->WageReturn == "CUSTOMER")
		{
			$FundWage = $obj->PayAmount*$partObj->FundWage/100;
		}
		$obj->OldAgentWage = round($AgentWage);
		$obj->OldFundWage = round($FundWage);
		$obj->Edit();
	}
	//---------------------------------------------------------
	
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function DeletePayment(){
	
	$obj = new LON_payments($_POST["PayID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function RegPayPartDoc($ReturnMode = false, $pdo = null){
	
	$PayID = $_POST["PayID"];
	$PayObj = new LON_payments($PayID);
	
	//------------- check for all checklist checked ---------------
	$dt = PdoDataAccess::runquery("
		SELECT * FROM BSC_CheckLists c
		left join BSC_CheckListValues v on(c.ItemID=v.ItemID AND SourceID=:l)
		where SourceType=".SOURCETYPE_LOAN." and v.ItemID is null", array(":l" => $PayObj->RequestID));
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "تا زمانی که کلیه آیتم های چک لیست انجام نشوند قادر به صدور سند نمی باشید");
		die();
	}
	//-------------------------------------------------------------
	
	//---------- check for previous payments docs registered --------------
	$dt = LON_payments::Get(" AND RequestID=? AND PayDate<? AND d.DocID is null",
			array($PayObj->RequestID, $PayObj->PayDate));
	if($dt->rowCount() > 0)
	{
		echo Response::createObjectiveResponse(false, "تا سند مراحل قبلی پرداخت صادر نشود قادر به صدور سند این مرحله نمی باشید");
		die();	
	}
	//---------------------------------------------------------------------
	if($pdo == null)
	{
		$pdo = PdoDataAccess::getPdoObject();
		$pdo->beginTransaction();
	}
	$ReqObj = new LON_requests($PayObj->RequestID);
	$partobj = LON_ReqParts::GetValidPartObj($PayObj->RequestID);
	$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
	
	LON_requests::ChangeStatus($PayObj->RequestID, "80", "پرداخت مبلغ " . number_format($PayObj->PayAmount), true, $pdo);
	
	if($partobj->MaxFundWage*1 > 0)
		$partobj->MaxFundWage = round($partobj->MaxFundWage*$PayObj->PayAmount/$partobj->PartAmount);
	
	if($PersonObj->IsSupporter == "YES")
		$result = RegisterSHRTFUNDPayPartDoc($ReqObj, $partobj, $PayObj, 
				$_POST["BankTafsili"], $_POST["AccountTafsili"], $_POST["ChequeNo"], $pdo);
	else
		$result = RegisterPayPartDoc($ReqObj, $partobj, $PayObj, 
				$_POST["BankTafsili"], $_POST["AccountTafsili"], $_POST["ChequeNo"], $pdo);
	
	if(!$result)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, PdoDataAccess::GetExceptionsToString());
		die();	
	}
	
	if($ReturnMode)
		return true;
	
	$PayObj->RealPayedDate = PDONOW;
	$PayObj->Edit($pdo);
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true,"");
	die();
}

function editPayPartDoc(){
	
	$PayID = $_POST["PayID"];
	$PayObj = new LON_payments($PayID);
	$partobj = LON_ReqParts::GetValidPartObj($PayObj->RequestID);
	$ReqObj = new LON_requests($PayObj->RequestID);
	$PersonObj = new BSC_persons($ReqObj->ReqPersonID);
	
	$DocObj = new ACC_docs(LON_payments::GetDocID($PayObj->PayID));
	if($DocObj->StatusID != ACC_STEPID_RAW)
	{
		echo Response::createObjectiveResponse(false,"سند تایید شده و قابل ویرایش نمی باشد");
		die();
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
		
	if(!ReturnPayPartDoc($DocObj->DocID, $pdo, false))
	{
		$pdo->rollBack();		
		echo Response::createObjectiveResponse(false, PdoDataAccess::GetExceptionsToString());
		die();
	}
	if($partobj->ComputeMode == "NOAVARI")
		$result = RegisterSHRTFUNDPayPartDoc($ReqObj, $partobj, $PayObj, 
				$_POST["BankTafsili"], $_POST["AccountTafsili"], $_POST["ChequeNo"], $pdo, $DocObj->DocID);
	else
		$result = RegisterPayPartDoc($ReqObj, $partobj, $PayObj, 
				$_POST["BankTafsili"], $_POST["AccountTafsili"], $_POST["ChequeNo"], $pdo, $DocObj->DocID);

	if(!$result)
	{
		echo Response::createObjectiveResponse(false,PdoDataAccess::GetExceptionsToString());
		die();
	}
	
	$pdo->commit();				
	echo Response::createObjectiveResponse(true,"");
	die();
}

function RetPayPartDoc($ReturnMode = false, $pdo = null){
	
	if(empty($_POST["PayID"]))
	{
		echo Response::createObjectiveResponse(false, "درخواست نامعتبر");
		die();
	}
	$PayID = $_POST["PayID"];
	$PayObj = new LON_payments($PayID);
	$DocID = LON_payments::GetDocID($PayObj->PayID);	
	//------------- check for Acc doc confirm -------------------
	$temp = PdoDataAccess::runquery("select StatusID 
		from ACC_DocItems join ACC_docs using(DocID) where SourceType=" . DOCTYPE_LOAN_PAYMENT . " AND 
		DocID=?", array($DocID));
	if(count($temp) == 0)
	{
		echo Response::createObjectiveResponse(false, "سند مربوطه یافت نشد");
		die();
	}
	if(count($temp) > 0 && $temp[0]["StatusID"] != ACC_STEPID_RAW)
	{
		echo Response::createObjectiveResponse(false, "سند حسابداری این شرایط تایید شده است. و قادر به برگشت نمی باشید");
		die();
	}
	//------- check for being first doc and there excists docs after -----------
	$CostCode_todiee = COSTID_Todiee;
	$temp = PdoDataAccess::runquery("select * from ACC_DocItems 
		where CostID=? AND CreditorAmount>0 AND DocID=?",
		array($CostCode_todiee, $DocID));
	if(count($temp) > 0)
	{
		$dt = PdoDataAccess::runquery("select * from ACC_DocItems where CostID=? AND DebtorAmount>0 
			AND SourceType=? AND SourceID1=?",
			array($CostCode_todiee, DOCTYPE_LOAN_PAYMENT, $PayObj->RequestID));
		if(count($dt) > 0)
		{
			echo Response::createObjectiveResponse(false, "به دلیل اینکه این سند اولین سند پرداخت می باشد و بعد از آن اسناد پرداخت دیگری صادر شده است" . 
				" قادر به برگشت نمی باشید. <br> برای برگشت ابتدا کلیه اسناد بعدی را برگشت بزنید");
			die();
		}
	}
	//-----------------------------------------------------------
	if($pdo == null)
	{
		$pdo = PdoDataAccess::getPdoObject();
		$pdo->beginTransaction();
	}
	
	if(!ReturnPayPartDoc($DocID, $pdo, !$ReturnMode))
	{
		if($ReturnMode)
			return false;
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, PdoDataAccess::GetExceptionsToString());
		die();
	}
	
	LON_requests::ChangeStatus($PayObj->RequestID, "90", "", true, $pdo);
	
	if($ReturnMode)
		return true;
	
	$pdo->commit();	
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

//-------------------------------------------------

function SelectAllMessages($returnCount = false){
	
	$where = "";
	$param = array();
	
	if(!empty($_REQUEST["RequestID"]))
	{
		$where .= " AND RequestID=?";
		$param[] = $_REQUEST["RequestID"];
	}
	
	if(!empty($_REQUEST["MsgStatus"]))
	{
		$where .= " AND MsgStatus=?";
		$param[] = $_REQUEST["MsgStatus"];
	}
	$res = LON_messages::Get($where, $param, dataReader::makeOrder());
	
	if($returnCount)
		return $res->rowCount();
	
	print_r(ExceptionHandler::PopAllExceptions());
	$cnt = $res->rowCount();
	$res = PdoDataAccess::fetchAll($res, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($res, $cnt, $_GET["callback"]);
	die();
}

function saveMessage(){
	
	$obj = new LON_messages();
	
	if(isset($_POST["record"]))
		PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	else
	{
		PdoDataAccess::FillObjectByArray($obj, $_POST);
		$obj->MsgStatus = "DONE";
	}
	
	if(isset($_POST["DoneDesc"]))
		$obj->DoneDate = PDONOW;
	
	if($obj->MessageID != "")
		$result = $obj->Edit();
	else
	{
		$obj->RegPersonID = $_SESSION["USER"]["PersonID"];
		$obj->CreateDate = PDONOW;
		$result = $obj->Add();
	}
	print_r(ExceptionHandler::PopAllExceptions());
	Response::createObjectiveResponse($result, "");
	die();
}

function removeMessage(){
	
	$obj = new LON_messages($_POST["MessageID"]);
	if($obj->MsgStatus == "RAW")
		$result = $obj->Remove();
	else
		$result = false;
	
	Response::createObjectiveResponse($result, "");
	die();
}

function ConfirmRequest(){
	
	$RequestID = $_POST["RequestID"];
	
	PdoDataAccess::runquery("update LON_requests set IsConfirm='YES' where RequestID=?", array($RequestID));
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse(true, "");
	die();
}

//-------------------------------------------------

function GetChequeStatuses(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where typeID=16 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function GetPayTypes(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where typeID=6 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function GetBanks(){
	
	$dt = PdoDataAccess::runquery("select * from ACC_banks");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

//............................................

function GetEvents(){
	
	$temp = LON_events::Get("AND RequestID=?", array($_REQUEST["RequestID"]));
	//print_r(ExceptionHandler::PopAllExceptions());
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveEvents(){
	
	$obj = new LON_events();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(empty($obj->FollowUpPersonID))
		$obj->FollowUpPersonID = $_SESSION["USER"]["PersonID"];
	
	if(empty($obj->EventID))
	{
		$obj->RegPersonID = $_SESSION["USER"]["PersonID"];
		$result = $obj->Add();
	}
	else
		$result = $obj->Edit();
	
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function DeleteEvents(){
	
	$obj = new LON_events();
	$obj->EventID = $_POST["EventID"];
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

//------------------------------------------------

function GetCosts(){
	
	$temp = LON_costs::Get("AND RequestID=?", array($_REQUEST["RequestID"]));
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveCosts(){
	
	$obj = new LON_costs();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if(empty($obj->CostID))
	{
		if(!$obj->Add($pdo))
		{
			echo Response::createObjectiveResponse(false, "خطا در ثبت هزینه");
			die();
		}
		/*if(!RegisterLoanCost($obj, $_POST["CostID"], $_POST["TafsiliID"], $_POST["TafsiliID2"], $pdo))
		{
			echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}*/
	}
	else
	{
		if(!$obj->Edit($pdo))
		{
			echo Response::createObjectiveResponse(false, "خطا در ویرایش هزینه");
			die();
		}
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteCosts(){
	
	$obj = new LON_costs($_POST["CostID"]);
	
	$DocRecord = $obj->GetAccDoc();
	if($DocRecord)
	{
		if($DocRecord["StatusID"] != ACC_STEPID_RAW)
		{
			echo Response::createObjectiveResponse(false, "سند مربوطه تایید شده و قابل حذف نمی باشد");
			die();	
		}
		
		ACC_docs::Remove($DocRecord["DocID"]);
	}
	
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

//------------------------------------------------

function GetGuarantors(){
	
	/*$temp = LON_guarantors::Get("AND RequestID=?", array($_REQUEST["RequestID"]));*/
    $temp = LON_guarantors::Get("AND FormType=? AND RequestID=?", array($_REQUEST["FormType"],$_REQUEST["RequestID"])); //new edited
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveGuarantor(){
	
	$obj = new LON_guarantors();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(empty($obj->GuarantorID))
	{
		if(!$obj->Add())
		{
			echo Response::createObjectiveResponse(false, "");
			die();
		}
	}
	else
	{
		if(!$obj->Edit())
		{
			echo Response::createObjectiveResponse(false, "");
			die();
		}
	}
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteGuarantor(){
	
	$obj = new LON_guarantors($_POST["GuarantorID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

//------------------------------------------------

function GetFollows(){
	
	$RequestID = (int)$_REQUEST["RequestID"];
	$params = array();
	$where = "";
	
	if($RequestID>0)
	{
		$where .= "AND f.RequestID=:reqid";
		$params[":reqid"] = $RequestID;
	}
	
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
		$field = $field == "ReqFullname" ? "concat_ws(' ',p1.fname,p1.lname,p1.CompanyName)" : $field;
		$field = $field == "LoanFullname" ? "concat_ws(' ',p2.fname,p2.lname,p2.CompanyName)" : $field;
		
        $where .= ' and ' . $field . ' like :fld';
        $params[':fld'] = '%' . $_REQUEST['query'] . '%';
    }
	
	$temp = LON_follows::Get($where . dataReader::makeOrder(), $params);
	$res = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveFollows(){
	
	$obj = new LON_follows();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(empty($obj->FollowID))
	{
		$obj->RegPersonID = $_SESSION["USER"]["PersonID"];
		$result = $obj->Add();
	}
	else
		$result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	if(!$result)
		echo Response::createObjectiveResponse(false, "خطا در ثبت ردیف");
	else
		echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteFollows(){
	
	$obj = new LON_follows($_POST["FollowID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

function GetFollowStatuses(){
	
	$temp = PdoDataAccess::runquery_fetchMode("select * from BaseInfo where TypeID=98");
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function GetFollowTemplates(){
	
	$temp = LON_FollowTemplates::Get();
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveFollowTemplates(){
	
	$obj = new LON_FollowTemplates();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(empty($obj->TemplateID))
	{
		$result = $obj->Add();
	}
	else
		$result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	if(!$result)
		echo Response::createObjectiveResponse(false, "خطا در ثبت ردیف");
	else
		echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteFollowTemplates(){
	
	$obj = new LON_FollowTemplates($_POST["TemplateID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

function RegisterLetter(){
	
	$FollowID = $_POST["FollowID"];
	$TemplateID = $_POST["TemplateID"];
	$RequestID = $_POST["RequestID"];
	
	$LetterID = LON_FollowLetters::AddLetter($FollowID, $TemplateID);
	if(!$LetterID)
	{
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	echo Response::createObjectiveResponse(true, $LetterID);
	die();
}

function GetFollowsToDo(){
	
	/*if($_SESSION["USER"]["UserName"] == "admin"){
		$temp = PdoDataAccess::runquery("
			select * from LON_follows f join( 
					select RequestID, max(StatusID) StatusID from LON_follows group by RequestID)t 
			using(RequestID,StatusID)
			where f.StatusID in(10,20)");
		foreach($temp as $row)
		{
			$instalmentRecord = LON_requests::GetMinNotPayedInstallment($row["RequestID"]);
			$diffDays = DateModules::GDateMinusGDate(DateModules::Now(), $instalmentRecord["RecordDate"]);
			if($row["StatusID"] == "10" && $diffDays < 3)
				continue;
			if($row["StatusID"] == "20" && $diffDays < 7)
				continue;
			echo "FollowID: " . $row["FollowID"] . " InstallmentID: " . $instalmentRecord["InstallmentID"] . "\n";

			PdoDataAccess::runquery("update LON_follows set InstallmentID=? where FollowiD=?", array(
				$instalmentRecord["InstallmentID"],
				$row["FollowID"]
			));
		}
		die();
	}*/
	
	$loans = PdoDataAccess::runquery("
		select r.RequestID,f.FollowID,f.StatusID,f.RegDate,f.InstallmentID,f.CurrentStatusDesc,
			concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) LoanPersonName,
			concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) ReqPersonName
			
		from LON_requests r
		join BSC_persons p1 on(LoanPersonID=p1.PersonID)
		left join BSC_persons p2 on(ReqPersonID=p2.PersonID)
		left join(
			select f1.*,InfoDesc CurrentStatusDesc from LON_follows f1 
			join (select max(FollowID)FollowID,RequestID from LON_follows group by RequestID )t using(FollowID,RequestID)
			join BaseInfo on(TypeID=" . TYPEID_LoanFollowStatusID . " AND InfoID=StatusID)
		)f on(r.RequestID=f.RequestID)
		where r.StatusID=" . LON_REQ_STATUS_CONFIRM .
		" order by f.StatusID desc");
	
	$followSteps = PdoDataAccess::runquery("select * from BaseInfo where IsActive='YES' AND typeID=" . 
			TYPEID_LoanFollowStatusID . " order by InfoID");
	
	$result = array();
	foreach($loans as $record)
	{
		$RequestID = $record["RequestID"];
		$computeArr = LON_Computes::ComputePayments($RequestID);
		$instalmentRecord = LON_requests::GetMinNotPayedInstallment($RequestID, $computeArr);
		
		$record["CurrentRemain"] = LON_Computes::GetCurrentRemainAmount($RequestID,$computeArr);
		$record["totalAmount"] = LON_Computes::GetTotalRemainAmount($RequestID,$computeArr);
		
		if($record["CurrentRemain"] <= 0) 
			continue;
		
		$debtClass = LON_Computes::GetDebtClassificationInfo($RequestID, $computeArr);
		$record["DebtClass"] = $debtClass["title"];
		
		//--------- sms steps reset for each delayed installment ------------------
		if($record["StatusID"] == "10"){
			if($instalmentRecord["InstallmentID"] != $record["InstallmentID"])
				$record["StatusID"] = "";
		}		
		
		//------------- first alert --------------
		if($record["StatusID"] == "")
		{
			$diffDays = DateModules::GDateMinusGDate(DateModules::Now(), $instalmentRecord["RecordDate"]);
			if($diffDays <= $followSteps[0]["param1"]*1)
				continue;
			
			//---------- check the min of debt ---------
			if($followSteps[0]["param3"]*1 > 0 && 
				$record["CurrentRemain"] < $followSteps[0]["param3"]*$instalmentRecord["RecordAmount"]/100){
				continue;
			}
			if($followSteps[0]["param4"]*1 > 0 && 
				$record["CurrentRemain"] < $followSteps[0]["param4"]*1){
				continue;
			}
			//-------------------------------------------
			
			$record["DiffDays"] = $diffDays;
			$record["ToDoStatusID"] = $followSteps[0]["InfoID"];
			$record["ToDoDesc"] = $followSteps[0]["InfoDesc"];
			$result[] = $record;
			continue;
		}
		
		//-------------- find next alert ------------
		$nextAlertRow = null;
		for($i=1; $i<count($followSteps); $i++)
		{
			if($followSteps[$i-1]["InfoID"] == $record["StatusID"])
			{
				$nextAlertRow = $followSteps[$i];
				break;
			}
		}
		if($nextAlertRow == null)
		{
			continue;
			//$nextAlertRow = $followSteps[ count($followSteps)-1 ];
		}
		
		$diffDays = DateModules::GDateMinusGDate(DateModules::Now(), $record["RegDate"]);
		if($diffDays <= $nextAlertRow["param1"]*1)
			continue;
		
		//---------- check the min of debt ---------
		if($nextAlertRow["param3"]*1 > 0 && 
			$record["CurrentRemain"] < $nextAlertRow["param3"]*$instalmentRecord["RecordAmount"]/100){
			continue;
		}
		if($nextAlertRow["param4"]*1 > 0 && 
			$record["CurrentRemain"] < $nextAlertRow["param4"]*1){
			continue;
		}
		//------------------------------------------
		
		if( 
			$debtClass["classes"]["2"]["amount"]*1 < $debtClass["FollowAmount2"] &&
			$debtClass["classes"]["3"]["amount"]*1 < $debtClass["FollowAmount3"] &&
			$debtClass["classes"]["4"]["amount"]*1 < $debtClass["FollowAmount4"])
			continue;


		$record["DiffDays"] = $diffDays;
		$record["ToDoStatusID"] = $nextAlertRow["InfoID"];
		$record["ToDoDesc"] = $nextAlertRow["InfoDesc"];
		$result[] = $record;
		
	}
	
	echo dataReader::getJsonData($result, count($result), $_GET["callback"]);
	die();
}

function DoFollow(){
	
	$RequestID = (int)$_POST["RequestID"];
	$ToDoStatusID = (int)$_POST["ToDoStatusID"];
	$instalmentRecord = LON_requests::GetMinNotPayedInstallment($RequestID);
	
	$followObj = new LON_follows();
	$followObj->RequestID = $RequestID;
	$followObj->InstallmentID = $instalmentRecord["id"];  
	$followObj->RegDate = PDONOW;
	$followObj->RegPersonID = $_SESSION["USER"]["PersonID"];
	$followObj->StatusID = $ToDoStatusID;
	$followObj->Add();
	
	//...........................................................
	$dt = PdoDataAccess::runquery("select * from BaseInfo 
			where TypeID=".TYPEID_LoanFollowStatusID." and InfoID=?", array($ToDoStatusID));
	
	if($dt[0]["param2"] != "" || $dt[0]["param5"] != ""){
	
		$reqObj = new LON_requests($RequestID);
		$personObj = new BSC_persons($reqObj->LoanPersonID);
		$computeArr = LON_Computes::ComputePayments($RequestID);
		$instalmentRecord = LON_requests::GetMinNotPayedInstallment($RequestID, $computeArr);
		$totalRemain = LON_Computes::GetCurrentRemainAmount($RequestID,$computeArr);

		$name = $personObj->CompanyName != "" ? $personObj->CompanyName . " محترم " :
					(($personObj->sex == "2" ? "سرکارخانم " : "جناب آقای ") . $personObj->_fullname );
		if($dt[0]["param2"] != "")
		{
			if($personObj->mobile == "")
			{
				echo Response::createObjectiveResponse(false, "با توجه به عدم تکمیل شماره موبایل توسط مشتری پیامک مربوطه ارسال نگردید");
				die();
			}
			$SendError = "";
			$context = $dt[0]["param2"];
			$context = preg_replace("/name/", $name, $context);
			$context = preg_replace("/no/", $reqObj->RequestID, $context);
			$context = preg_replace("/amount/", $totalRemain, $context);
			$context = preg_replace("/date/", DateModules::miladi_to_shamsi($instalmentRecord["RecordDate"]), $context);

			$result = ariana2_sendSMS($personObj->mobile, $context, "number", $SendError);
			if(!$result)
			{
				echo Response::createObjectiveResponse(false, "ارسال پیامک به دلیل خطای زیر انجام نگردید" . "[" . $SendError . "]");
				die();
			}
		}
		if($dt[0]["param5"] != "")
		{
			$guarantors = LON_guarantors::Get(" AND RequestID=? AND mobile <> ''", array($RequestID));

			$context = $dt[0]["param5"];
			$context = preg_replace("/name/",$name, $context);
			$context = preg_replace("/no/", $reqObj->RequestID, $context);
			$context = preg_replace("/amount/", $totalRemain, $context);
			$context = preg_replace("/date/", DateModules::miladi_to_shamsi($instalmentRecord["RecordDate"]), $context);

			$SendError = "";
			foreach($guarantors as $row)
			{
				$mobile = $row["mobile"];
				$result = ariana2_sendSMS($mobile, $context, "number", $SendError);
				if(!$result)
				{
					$SendError .= "ارسال پیامک به " . $context . $row["fullname"] . " انجام نگردید.<br>";
				}
			}
			if($SendError != ""){
				echo Response::createObjectiveResponse(false, $SendError);
				die();
			}
		}
	}
	
	//...........................................................
	
	$dt = LON_FollowTemplates::Get(" AND StatusID=?", array($ToDoStatusID));
	if($dt->rowCount() > 0)
	{
		$row = $dt->fetch();
		$LetterID = LON_FollowLetters::AddLetter($followObj->FollowID, $row["TemplateID"]);
		if(!$LetterID)
		{
			echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}
		
		echo Response::createObjectiveResponse(true, $LetterID);
		die();
	}
	
	echo Response::createObjectiveResponse(true, "");
	die();
	
}
//------------------------------------------------

function GetPureAmount(){
	
	$RequestID = (int)$_POST["RequestID"];
	$ComputeDate = empty($_POST["ComputeDate"]) ? "" : DateModules::shamsi_to_miladi($_POST["ComputeDate"], "-");
	
	$dt = LON_requests::GetPureAmount($RequestID, null, null, $ComputeDate);
	$amount = $dt["PureAmount"];
	echo Response::createObjectiveResponse(true, $amount);
	die();
}

function emptyDataTable(){
	echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
	die();
}

function ComputeManualInstallments(){
	
	$RequestID = $_POST["RequestID"];
	
	$items = json_decode(stripcslashes($_REQUEST["records"]));
	$installmentArray = array();
	for ($i = 0; $i < count($items); $i++) {
		$installmentArray[] = array(
			"InstallmentAmount" => $items[$i]->InstallmentAmount,
			"InstallmentDate" => $items[$i]->InstallmentDate
		);
	}
	$installmentArray = ExtraModules::array_sort($installmentArray, "InstallmentDate");
		
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	if($partObj->ComputeMode == "NEW")
		$installmentArray = LON_Computes::ComputeInstallment($partObj, $installmentArray, 
			$ComputeDate, $ComputeWage);
	else
		$installmentArray = ComputeNonEqualInstallment($partObj, $installmentArray, $ComputeDate, 
				$ComputeWage, $WithWage);
	
	//........................

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	PdoDataAccess::runquery("delete from LON_installments "
			. "where RequestID=? AND history='NO' AND IsDelayed='NO'", array($RequestID), $pdo);
	
	for($i=0; $i < count($installmentArray); $i++)
	{
		$obj = new LON_installments();
		$obj->RequestID = $RequestID;
		$obj->InstallmentDate = DateModules::shamsi_to_miladi($installmentArray[$i]["InstallmentDate"]);
		$obj->InstallmentAmount = $installmentArray[$i]["InstallmentAmount"];
		$obj->wage = isset($installmentArray[$i]["wage"]) ? $installmentArray[$i]["wage"] : 0;
		$obj->PureWage = isset($installmentArray[$i]["PureWage"]) ? $installmentArray[$i]["PureWage"] : 0;
		$obj->ComputeType = "USER";
		if(!$obj->AddInstallment($pdo))
		{
			$pdo->rollBack();
			print_r(ExceptionHandler::PopAllExceptions());
			echo Response::createObjectiveResponse(false, "");
			die();
		}
	}
	
	$pdo->commit();	
	echo Response::createObjectiveResponse(true, "");
	die();
}

//------------------------------------------------



//------------------------------------------------
	
function CustomerDefrayRequest(){
	
	$RequestID = (int)$_POST["RequestID"];
	
	//-------------- GetDefrayVoteForm -----------------
	$dt = PdoDataAccess::runquery("
		SELECT f.FormID FROM VOT_FilledItems
			join VOT_FilledForms f using(FormID)
			join VOT_FormItems using(ItemID)
			where f.PersonID=? AND f.FormID=".DEFRAYLOAN_VOTEFORM." AND ItemType='loan' AND FilledValue=?", 
			array($_SESSION["USER"]["PersonID"],  $RequestID));
	if(count($dt) == 0)
	{
		echo Response::createObjectiveResponse(false, "ابتدا باید  فرم نظرسنجی فرم مربوطه را تکمیل کنید");
		die();
	}
	
	//---------------- GetDefrayWFMForm ---------------
	$dt = PdoDataAccess::runquery("
		SELECT RequestID,FlowID FROM WFM_requests r
			join WFM_forms using(FormID)
			join WFM_RequestItems using(RequestID)
			join WFM_FormItems using(FormItemID)
		where r.FormID=".DEFRAYLOAN_WFMFORM." AND r.PersonID=? AND ItemType='loan' AND ItemValue=?", 
		array($_SESSION["USER"]["PersonID"],  $RequestID));
	
	if(count($dt) == 0)
	{
		echo Response::createObjectiveResponse(true,"");
		die();
	}
	
	$arr = WFM_FlowRows::GetFlowInfo($dt[0]["FlowID"], $dt[0]["RequestID"]);
	if($arr["IsStarted"])
	{
		echo Response::createObjectiveResponse(false, "درخواست تسویه حساب شما برای این وام قبلا ارسال شده است" . 
				"<br>برای اطلاع از وضعیت درخواست خود می توانید به منوی مدیریت فرم ها در قسمت اطلاعات شخصی مراجعه کنید");
		die();
	}
	
	echo Response::createObjectiveResponse(true,$dt[0]["RequestID"]);
	die();
}
	
?>
