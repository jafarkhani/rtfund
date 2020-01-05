<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.06
//---------------------------

require_once '../header.inc.php';
require_once(inc_response);
require_once inc_dataReader;
require_once './store.class.php';
require_once 'TreeModules.class.php';

ini_set("display_errors", "On");

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
switch ($task) {

	case "SelectGoods":
	case "SaveGood":
	case "DeleteGood":
	case "GetGoodsTree":
	case "SelectGoodScales":
		
	case "SelectProperties":
	case "SaveProperty":
	case "DeleteProperty":
		
	case "SelectAllAssets":	
	case "selectPropertyValues":
	case "SaveAsset":
	case "DeleteAsset":
	
	case "SelectAllAssetFlow":
	case "SaveFlow":
	case "DeleteFlow":
		
		$task();
};

function SelectGoods() {

    $where = " AND IsActive='YES'";
    $param = array();

    if (!empty($_REQUEST['query'])) {
		$field = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "GoodName";
        $where .= ' and ' . $field . ' like :' . $field;
        $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
    }
	
	if(!empty($_GET["GoodID"]))
	{
		$where .= " AND GoodID=:g";
		$param[":g"] = (int)$_GET["GoodID"];
	}

	$list = STO_goods::Get($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->rowCount();

    if (isset($_GET["start"]) && !isset($_GET["All"]))
        $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    else
        $list = $list->fetchAll();

    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function SaveGood() {

    $obj = new STO_goods();
    pdoDataAccess::FillObjectByArray($obj, $_POST);

    if ($obj->GoodID == '')
        $result = $obj->Add();
    else
        $result = $obj->Edit();
	//print_r(ExceptionHandler::PopAllExceptions());
    Response::createObjectiveResponse($result, "");
    die();
}

function DeleteGood() {

	$obj = new STO_goods($_POST["GoodID"]*1);
	$obj->IsActive = "NO";
	$result = $obj->Edit();
    Response::createObjectiveResponse($result, '');
    die();
}

function GetGoodsTree() {

	$nodes = PdoDataAccess::runquery("
			select * from STO_goods  where IsActive='YES'
			order by ParentID,GoodName");
	$returnArr = TreeModulesclass::MakeHierarchyArray($nodes, "ParentID", "GoodID", "GoodName");
	//print_r(ExceptionHandler::PopAllExceptions());
	echo json_encode($returnArr);
	die();
}

function SelectGoodScales(){
	
	$list = PdoDataAccess::runquery("select * from BaseInfo where TypeID=94");
	echo dataReader::getJsonData($list, count($list), $_GET['callback']);
    die();
} 

//...................................

function SelectProperties(){
	
	$where = " AND IsActive='YES'";
	$params = array();
	
	if(!empty($_GET["GoodID"]))
	{
		$where .= " AND GoodID=?";
		$params[] = (int)$_GET["GoodID"];
	}
	
	$dt = STO_GoodProperties::Get($where, $params);
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SaveProperty(){
	
	$obj = new STO_GoodProperties();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->PropertyID > 0)
		$result = $obj->Edit();
	else
		$result = $obj->Add();

	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteProperty(){
	
	$obj = new STO_GoodProperties($_POST["PropertyID"]);
	$result =  $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

//...................................

function SelectAllAssets() {

    $where = "";
    $param = array();

    if (!empty($_REQUEST['query'])) {
		$field = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "GoodName";
        $where .= ' and ' . $field . ' like :' . $field;
        $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
    }

	$list = STO_Assets::Get($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->rowCount();

    if (isset($_GET["start"]) && !isset($_GET["All"]))
        $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    else
        $list = $list->fetchAll();

    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function selectPropertyValues(){
	
	$dt = STO_AssetProperties::Get(" AND AssetID=?", array((int)$_REQUEST["AssetID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SaveAsset() {

    $obj = new STO_Assets();
    pdoDataAccess::FillObjectByArray($obj, $_POST);

    if ($obj->AssetID == '')
	{
		$obj->StatusID = STO_STEPID_RAW;
		$obj->RegDate = PDONOW;
		$result = $obj->Add();
		STO_AssetFlow::AddFlow($obj->AssetID, $obj->StatusID, true, $obj->amount);
	}
    else
        $result = $obj->Edit();
	
	//-------------- params ------------------
	PdoDataAccess::runquery("delete from STO_AssetProperties where AssetID=?", array($obj->AssetID));
	$arr = array_keys($_POST);
	foreach($arr as $key)
	{
		if(strpos($key, "Property_") !== false)
		{
			PdoDataAccess::runquery("insert into STO_AssetProperties values(?,?,?)",
				array($obj->AssetID, preg_replace("/Property_/", "", $key), $_POST[$key] ));
		}
	}
	
	//print_r(ExceptionHandler::PopAllExceptions());
    Response::createObjectiveResponse($result, "");
    die();
}

function DeleteAsset() {

	$obj = new STO_Assets((int)$_POST["AssetID"]);

	$result = $obj->remove();
    Response::createObjectiveResponse($result, '');
    die();
}

//...................................

function SelectAllAssetFlow(){
	
	$where = " AND af.IsActive='YES' AND AssetID=:a";
    $param = array(":a" => $_REQUEST["AssetID"]);

    if (!empty($_REQUEST['query'])) {
		$field = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "GoodName";
        $where .= ' and ' . $field . ' like :' . $field;
        $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
    }
	
	

	$list = STO_AssetFlow::Get($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->rowCount();

    if (isset($_GET["start"]) && !isset($_GET["All"]))
        $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    else
        $list = $list->fetchAll();

    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function SaveFlow() {

    $obj = new STO_AssetFlow();
    pdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$obj->IsUsable = $obj->IsUsable == "true" ? "YES" : "NO";
	
	
    if ($obj->FlowID == '')
	{
		$obj->ActDate = PDONOW;
		$obj->ActPersonID = $_SESSION["USER"]["PersonID"];
		$result = $obj->Add();
	}
    else
        $result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
    Response::createObjectiveResponse($result, "");
    die();
}

function DeleteFlow() {

	$obj = new STO_AssetFlow((int)$_POST["FlowID"]);
	$result = $obj->remove();
    Response::createObjectiveResponse($result, '');
    die();
}

?>
