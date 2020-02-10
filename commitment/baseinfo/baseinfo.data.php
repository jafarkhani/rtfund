<?php
//---------------------------
// developer:	Sh.Jafarkhani
// Date:		97.05
//---------------------------
require_once '../header.inc.php';
require_once 'baseinfo.class.php';
require_once 'TreeModules.class.php';
require_once(inc_response);
require_once(inc_dataReader);
	
$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';

switch ($task) {
		
	case 'GetAllEvents':
	case 'DeleteEvent':
	case 'SaveEvent':
	case "GetEventsTree":

	case "selectComputeGroups":
	case "selectComputeItems":
	case "selectEventRows":
	case "saveEventRow":
	case "DeleteEventRow":
	case 'selectEventTafsilis':
	case "CopyEventRows":
	
		$task();
}


//------------------------------------------------------------------------------

function GetAllEvents() {
	
	$where = '1=1';
	$param = array();
             if (!InputValidation::validate($_POST['query'], InputValidation::Pattern_FaEnAlphaNum, false)) {
                echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
                die();
           }
        
	if(!empty($_REQUEST["query"]))
	{
		$where .= " AND ( EventTitle like :q or EventID like :q)";
		$param[":q"] = "%" . $_REQUEST["query"] . "%";
	}
	
	if(!empty($_REQUEST["EventID"]))
	{
		$where .= " AND EventID =:eid";
		$param[":eid"] =(int) $_REQUEST["EventID"];
	}
	
	if(!isset($_REQUEST["all"]))
		$where .= " AND ParentID>0";

	$list = COM_events::SelectEvents($where, $param);
	echo dataReader::getJsonData($list, count($list), $_GET['callback']);
	die();
}

function SaveEvent() {

	$eventObj = new COM_events();
	PdoDataAccess::FillObjectByArray($eventObj, $_POST);
	$eventObj->IsActive = 'YES';

	if ($_POST["old_EventID"] == '')
		$res = $eventObj->InsertEvent();
	else
		$res = $eventObj->UpdateEvent((int)$_POST["old_EventID"]);

	Response::createObjectiveResponse($res, $eventObj->EventID);
	die();
}

function DeleteEvent() {

	$res = COM_events::DeleteEvent((int)$_POST['EventID']);
	echo Response::createResponse($res, "");
	die();
}

function GetEventsTree() {

	$nodes = PdoDataAccess::runquery("
			select concat('[',EventID,'][',ordering,'] ',EventTitle) text, e.*
			from COM_events e
			where e.IsActive='YES'
			order by ParentID,ordering");
	$returnArr = TreeModulesclass::MakeHierarchyArray($nodes, "ParentID", "EventID", "text");
	//print_r(ExceptionHandler::PopAllExceptions());
	echo json_encode($returnArr);
	die();
}

function CopyEventRows(){
	
	$SRC_EventID = (int)$_POST["Src_EventID"];
	$DST_EventID = (int)$_POST["Dst_EventID"];
	$mode = (int)$_POST["mode"];
	
	PdoDataAccess::runquery("
		insert into COM_EventRows(EventID,CostID,CostType,DocDesc,ComputeItemID)
		select :dst,CostID,
				" . ($mode == 0 ? "CostType" : "case when CostType='DEBTOR' then 'CREDITOR' else 'DEBTOR' end") . " 
				,DocDesc,ComputeItemID
		from COM_EventRows where EventID=:src AND IsActive='YES'
	",array(
		":dst" => $DST_EventID,
		":src" => $SRC_EventID
	));
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse(true, PdoDataAccess::AffectedRows());
	die();
}
//------------------------------------------------------------------------------

function selectComputeGroups(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where typeID=83 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectComputeItems(){
	
	$param1 = $_REQUEST["param1"];
	$dt = PdoDataAccess::runquery("select * from BaseInfo 
		where typeID=84 AND IsActive='YES' AND param1=?", array($param1));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectEventRows(){

	$where = "EventID=? ";
	if(!isset($_REQUEST["AllHistory"]) || $_REQUEST["AllHistory"] == "false")
		$where .= " AND er.IsActive='YES'";
		
	$where .= " order by CostType,CostCode,er.IsActive desc,ChangeDate";
	
	$list = COM_EventRows::SelectAll($where, array($_REQUEST["EventID"]));
	print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();
	echo dataReader::getJsonData($list, count($list), $_GET['callback']);
	die();
}

function saveEventRow(){
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new COM_EventRows();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	//------ add new Row -------------
	if(empty($obj->RowID))
	{
		if(!$obj->InsertEventRow($pdo))
		{
			$pdo->rollBack();
			echo Response::createObjectiveResponse(false, "");
			die();
		}
		$pdo->commit();
		echo Response::createObjectiveResponse(true, "");
		die();	
	}
	//--------- edit row ------------
	$OldRowID = $obj->RowID;
	unset($obj->RowID);
	//unset($obj->ChangeDesc);
	if(!$obj->InsertEventRow($pdo))
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	$obj2 = new COM_EventRows();
	$obj2->NewRowID = $OldRowID;
	$obj2->RowID = $_POST["RowID"];
	$obj2->IsActive = 'NO';
	$obj2->ChangeDesc = $_POST["ChangeDesc"];
	if(!$obj2->UpdateEventRow($pdo))
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();	
}

function DeleteEventRow(){
	
	$obj = new COM_EventRows();
	$obj->RowID = $_POST["RowID"];
	$obj->ChangeDesc = $_POST["ChangeDesc"];
	$obj->ChangeDate = PDONOW;
	$obj->ChangePersonID = $_SESSION["USER"]["PersonID"];
	$obj->IsActive = 'NO';
	$res = $obj->UpdateEventRow();
	
	echo Response::createObjectiveResponse($res, "");
	die();
}

function selectEventTafsilis(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where typeID=87 AND IsActive='YES' ");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}



?>