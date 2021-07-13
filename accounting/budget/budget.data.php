<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.06
//---------------------------

require_once '../../header.inc.php';
require_once(inc_response);
require_once inc_dataReader;
require_once './budget.class.php';
require_once 'TreeModules.class.php';

//ini_set("display_errors", "On");

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
switch ($task) {

	case "SelectBudgets":
	case "SaveBudget":
	case "DeleteBudget":
	case "ActivateBudget":
	case "GetBudgetTree":
	
	case "GetBudgetCostCodes":
	case "SaveBudgetCostCode":
	case "RemoveBudgetCostCode":
		
	case "SelectBudgetAllocs":
	case "SaveBudgetAlloc":	
	case "DeleteBudgetAlloc":

    //new added
    case "SelectBudgetApproved":
    case "SaveBudgetApproved":
    case "DeleteBudgetApproved":

        //new added for budget archive
    case "SaveBudgetArchive":
    case "DeleteBudgetArchive":
    case "GetBudgetArchiveTree":
		
		$task();
};

function SelectBudgets() {

    $where = " AND IsActive='YES'";
    $param = array();

    if (!empty($_REQUEST['query'])) {
		$field = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "BudgetDesc";
        $where .= ' and ' . $field . ' like :' . $field;
        $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
    }

	$list = ACC_budgets::Get($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->rowCount();

    if (isset($_GET["start"]) && !isset($_GET["All"]))
        $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    else
        $list = $list->fetchAll();

    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function SaveBudget() {

    $obj = new ACC_budgets();
    pdoDataAccess::FillObjectByArray($obj, $_POST);

    if ($obj->BudgetID == '')
        $result = $obj->Add();
    else
        $result = $obj->Edit();
	
    Response::createObjectiveResponse($result, $obj->BudgetID);
    die();
}

function DeleteBudget() {

	$obj = new ACC_budgets($_POST["BudgetID"]*1);
	$obj->IsActive = "NO";
	$result = $obj->Edit();
    Response::createObjectiveResponse($result, '');
    die();
}

function ActivateBudget(){
	
	$obj = new ACC_budgets($_POST["BudgetID"]*1);
	$obj->IsActive = "YES";
	$result = $obj->Edit();
    Response::createObjectiveResponse($result, '');
    die();
}

function GetBudgetTree() {

	$nodes = PdoDataAccess::runquery("
			select * from ACC_budgets  where IsActive='YES'
			order by ParentID,BudgetDesc");
	$returnArr = TreeModulesclass::MakeHierarchyArray($nodes, "ParentID", "BudgetID", "BudgetDesc", true);
	//print_r(ExceptionHandler::PopAllExceptions());
	echo json_encode($returnArr);
	die();
}

//...................................

function GetBudgetCostCodes(){
	
	$dt = ACC_BudgetCostCodes::Get(" AND BudgetID=?", array($_REQUEST["BudgetID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount());
	die();
}

function SaveBudgetCostCode(){
	
	$obj = new ACC_BudgetCostCodes();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$result = $obj->Add();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function RemoveBudgetCostCode(){
	
	$obj = new ACC_BudgetCostCodes($_REQUEST["RowID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//...................................

function SelectBudgetAllocs() {

    $where = " AND IsActive='YES'";
    $param = array();

    if (!empty($_REQUEST['query'])) {
		$field = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "BudgetDesc";
        $where .= ' and ' . $field . ' like :' . $field;
        $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
    }

	$list = ACC_BudgetAlloc::Get($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->rowCount();

    if (isset($_GET["start"]) && !isset($_GET["All"]))
        $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    else
        $list = $list->fetchAll();

    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function SaveBudgetAlloc() {

    $obj = new ACC_BudgetAlloc();
    pdoDataAccess::FillObjectByArray($obj, $_POST);

    if ($obj->AllocID == '')
        $result = $obj->Add();
    else
        $result = $obj->Edit();
	
    Response::createObjectiveResponse($result, "");
    die();
}

function DeleteBudgetAlloc() {

	$obj = new ACC_BudgetAlloc($_POST["AllocID"]*1);
	$result = $obj->Remove();
    Response::createObjectiveResponse($result, '');
    die();
}

//................new added.....................

function SelectBudgetApproved() {

    $where = " AND IsActive='YES'";
    $param = array();
    /*var_dump($_REQUEST);*/
    if (!empty($_REQUEST['query'])) {
        $field = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "BudgetDesc";
        $where .= ' and ' . $field . ' like :' . $field;
        if($field == "approvedAmount" || $field == "PrevisionAmount"){
            $param[':' . $field] =  $_REQUEST['query'] ;
        }
        elseif($field == "CycleYear"){
            $param[':' . $field] =  $_REQUEST['query'];
            $field = "ac.CycleYear";
        }
        else{
            $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
        }
    }

    $list = ACC_BudgetApproved::Get($where . dataReader::makeOrder(), $param);
    print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->rowCount();

    if (isset($_GET["start"]) && !isset($_GET["All"]))
        $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    else
        $list = $list->fetchAll();

    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function SaveBudgetApproved() {

    $obj = new ACC_BudgetApproved();
    pdoDataAccess::FillObjectByArray($obj, $_POST);

    if ($obj->ApprovedID == '')
        $result = $obj->Add();
    else
        $result = $obj->Edit();

    Response::createObjectiveResponse($result, "");
    die();
}

function DeleteBudgetApproved() {
    $obj = new ACC_BudgetApproved($_POST["ApprovedID"]*1);
    $result = $obj->Remove();
    Response::createObjectiveResponse($result, '');
    die();
}

//.............new added for Budget Archive......................

function SaveBudgetArchive() {
    /*var_dump($_POST);*/
    $obj = new ACC_BudgetsArchive();
    pdoDataAccess::FillObjectByArray($obj, $_POST);

    if ($obj->BudgetArchiveID == '')
        $result = $obj->Add();
    else
        $result = $obj->Edit();

    Response::createObjectiveResponse($result, $obj->BudgetArchiveID);
    die();
}

function DeleteBudgetArchive() {

    $obj = new ACC_BudgetsArchive($_POST["BudgetArchiveID"]*1);
    /*$obj->IsActive = "NO";*/
    $result = $obj->Remove();
    Response::createObjectiveResponse($result, '');
    die();
}

function GetBudgetArchiveTree() {
    $cycleid = $_REQUEST['CycleID'];
    /*$cycleid = 99;*/
    $nodes = PdoDataAccess::runquery("
			select * from ACC_BudgetsArchive  where CycleID=" . $cycleid . "
			order by ParentID,BudgetDesc");
    $returnArr = TreeModulesclass::MakeHierarchyArrayNew($nodes, "ParentID", "BudgetArchiveID", "BudgetDesc", "OperationalDef", true);
    //print_r(ExceptionHandler::PopAllExceptions());
    echo json_encode($returnArr);
    die();
}

//...................................

?>
