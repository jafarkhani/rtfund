<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.06
//---------------------------
require_once '../../header.inc.php';
require_once 'baseInfo.class.php';
require_once 'TreeModules.class.php';
require_once inc_response;
require_once inc_dataReader;

$task = isset($_POST["task"]) ? $_POST["task"] : (isset($_GET["task"]) ? $_GET["task"] : "");
if(!empty($task))
	$task();

function SaveUnit(){
	$obj = new BSC_units();
	PdoDataAccess::FillObjectByArray($obj, $_POST);

	if($_POST["ParentID"] == "source")
		unset($obj->ParentID);
	else
		$obj->ParentID = $_POST["ParentID"];
	
	if($obj->UnitID > 0)
		$res = $obj->EditUnit();
	else
		$res = $obj->AddUnit();

	echo Response::createObjectiveResponse($res, $obj->UnitID);
	die();
}

function DeleteUnit(){
	$res = BSC_units::RemoveUnit($_POST["UnitID"]);
	echo Response::createObjectiveResponse($res,"");
	die();
}

function GetTreeNodes(){
	$nodes = PdoDataAccess::runquery("
		select UnitID as id,UnitName as text, 
		case when ParentID is null then 0 else ParentID end ParentID, 'true' as leaf
		from BSC_units order by ParentID,UnitName");
	
	$returnArray = array();
    $refArray = array();

    foreach ($nodes as $row) {
        if ($row["ParentID"] == 0) {
            $returnArray[] = $row;
            $refArray[$row["id"]] = &$returnArray[count($returnArray) - 1];
            continue;
        }

        $parentNode = &$refArray[$row["ParentID"]];

        if (!isset($parentNode["children"])) {
            $parentNode["children"] = array();
            $parentNode["leaf"] = "false";
        }
        $lastIndex = count($parentNode["children"]);
        $parentNode["children"][$lastIndex] = $row;
        $refArray[$row["id"]] = &$parentNode["children"][$lastIndex];
    }
	
    if($_REQUEST['AddMode'] == "false" ){
		$jobs = PdoDataAccess::runquery("
			select concat('p_',JobID) id,
				concat_ws(' ',JobID,'-',PostName,'[ ',
					if(IsMain='YES' AND p.PersonID is not null,'* ',''),fname,lname,CompanyName,' ]') text,
				'true' leaf,'user' iconCls, j.*
			from BSC_jobs j join BSC_posts using(PostID) left join BSC_persons p using(PersonID) order by PostName");
	foreach($jobs as $job)
	{
		$parentNode = &$refArray[ $job["UnitID"] ];

        if (!isset($parentNode["children"])) {
            $parentNode["children"] = array();
            $parentNode["leaf"] = "false";
        }
		$lastIndex = count($parentNode["children"]);
        $parentNode["children"][$lastIndex] = $job;
	}
        }
	$str = json_encode($returnArray);

    $str = str_replace('"children"', 'children', $str);
    $str = str_replace('"leaf"', 'leaf', $str);
	$str = str_replace('"iconCls"', 'iconCls', $str);
    $str = str_replace('"text"', 'text', $str);
    $str = str_replace('"id"', 'id', $str);
    $str = str_replace('"true"', 'true', $str);
    $str = str_replace('"false"', 'false', $str);

    echo $str;
    die();
}

//---------------------------------

function SelectPosts(){
	
	$temp = BSC_posts::Get(" AND IsActive='YES'" . dataReader::makeOrder());
	echo dataReader::getJsonData($temp->fetchAll(), $temp->rowCount(), $_GET["callback"]);
	die();
}

function SavePost(){
	
	$obj = new BSC_posts();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$obj->MissionSigner = !isset($obj->MissionSigner) ? "NO" : "YES";
	
	if($obj->PostID > 0)
		$res = $obj->Edit();
	else
		$res = $obj->Add();

	//print_r(ExceptionHandler::PopAllExceptions()); 
	echo Response::createObjectiveResponse($res, "");
	die();
}

function DeletePost(){
	
	$obj = new BSC_posts($_POST["PostID"]);
	$res = $obj->Remove();
	echo Response::createObjectiveResponse($res,  ExceptionHandler::GetExceptionsToString());
	die();
}

//---------------------------------

function SaveJob(){
	
	$obj = new BSC_jobs();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if($obj->JobID > 0)
		$res = $obj->Edit();
	else
		$res = $obj->Add();

	//print_r(ExceptionHandler::PopAllExceptions()); 
	echo Response::createObjectiveResponse($res, $res ? $obj->JobID : ExceptionHandler::GetExceptionsToString());
	die();
}

function DeleteJob(){
	
	$JobID = $_POST["JobID"];
	$JobID = str_replace("p_", "", $JobID);
	
	$obj = new BSC_jobs($JobID);
	$res = $obj->Remove();
	echo Response::createObjectiveResponse($res,  ExceptionHandler::GetExceptionsToString());
	die();
}
//---------------------------------

function SelectBranches(){
	
	$query = "select * from BSC_branches where 1=1 ";
	$query .= !empty($_REQUEST["WarrentyAllowed"]) ? " AND WarrentyAllowed='YES'" : "";
			
	$temp = PdoDataAccess::runquery($query);
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveBranch(){
	
	$obj = new BSC_branches();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	$obj->WarrentyAllowed = $obj->WarrentyAllowed == "true" ? "YES" : "NO";
	
	if($obj->BranchID > 0)
		$result = $obj->EditBranch();
	else 
		$result = $obj->AddBranch();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteBranch(){
	
	$result = BSC_branches::RemoveBranch($_POST["BranchID"]);
	echo Response::createObjectiveResponse($result, "");
	die();
}

//---------------------------------

function SelectUserBranches(){
	
	$temp = PdoDataAccess::runquery("
		select b.*,concat(fname, ' ', lname) fullname, BranchName 
		from BSC_BranchAccess b 
		join BSC_persons using(PersonID)
		join BSC_branches using(BranchID)");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveBranchAccess(){
	
	PdoDataAccess::runquery("insert into BSC_BranchAccess values(?,?,'NO')", array($_POST["PersonID"],$_POST["BranchID"]));
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteBranchAccess(){
	
	PdoDataAccess::runquery("delete from BSC_BranchAccess where PersonID=? AND BranchID=?", array($_POST["PersonID"],$_POST["BranchID"]));
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetAccessBranches(){
	
	$branches = FRW_access::GetAccessBranches();
	$dt = PdoDataAccess::runquery("select * from BSC_branches where BranchID in(" .
		implode(",", $branches) . ")");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

//---------------------------------

function SelectBaseTypes(){
	
	$where = "editable='YES'";
	if($_SESSION["USER"]["UserName"] =="admin")
		$where = "1=1";
	$temp = PdoDataAccess::runquery("select * from BaseTypes where GroupID=? AND " . $where, 
			array($_REQUEST["GroupID"]));
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveBaseType(){
	
	$temp = PdoDataAccess::runquery("insert into BaseTypes(GroupID,TypeDesc) values(?,?)", array(
		$_REQUEST["GroupID"],
		$_REQUEST["TypeDesc"]
	));
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

function SelectBaseTypeGroups(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=0");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SelectBaseInfo(){
	
	$where = " typeID=:t ";
	$param = array(":t" => $_REQUEST["TypeID"]);
	
	if(!empty($_REQUEST["fields"]) && !empty($_REQUEST["query"]))
	{
		$where .= " AND ".$_REQUEST["fields"]." like :d";
		$param[":d"] = "%" . $_REQUEST["query"] . "%";
		
	}	
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where " . $where, $param);
	print_r(ExceptionHandler::PopAllExceptions());
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveBaseInfo(){
	
	$obj = new BaseInfo();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);

	if($obj->InfoID*1 == 0 || $_POST["OldInfoID"]*1 == 0)
	{
		$pdo = PdoDataAccess::getPdoObject();
		$pdo->beginTransaction();
	
		if($obj->InfoID*1 == 0)
		{
			$obj->InfoID = PdoDataAccess::GetLastID("BaseInfo", "InfoID", "TypeID=?", array($obj->TypeID), $pdo);
			$obj->InfoID = $obj->InfoID*1 + 1;
		}
		$obj->Add($pdo);		
		$pdo->commit();
	}
	else
		$obj->Edit($_POST["OldInfoID"]);
	
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

function DeleteBaseInfo(){
	
	$obj = new BaseInfo($_REQUEST["TypeID"], $_REQUEST["InfoID"]);
	$obj->Remove();
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

//.............................................

function SelectDomainNodes(){

	$dt = PdoDataAccess::runquery("
		SELECT 
			ParentID,DomainID id,DomainDesc as text,'true' as leaf, 'javascript:void(0)' href,d.*
		FROM BSC_ActDomain d order by ParentID,DomainDesc");

    $returnArray = array();
    $refArray = array();

    foreach ($dt as $row) {
        if ($row["ParentID"] == 0) {
            $returnArray[] = $row;
            $refArray[$row["id"]] = &$returnArray[count($returnArray) - 1];
            continue;
        }

        $parentNode = &$refArray[$row["ParentID"]];

        if (!isset($parentNode["children"])) {
            $parentNode["children"] = array();
            $parentNode["leaf"] = "false";
			$parentNode["href"] = "";
        }
        $lastIndex = count($parentNode["children"]);
        $parentNode["children"][$lastIndex] = $row;
        $refArray[$row["id"]] = &$parentNode["children"][$lastIndex];
    }

	echo json_encode($returnArray);
	die();
}

function SaveDomain(){
	
	$obj = new BSC_ActDomain();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->ParentID = $obj->ParentID == "src" ? "0" : $obj->ParentID;		
	
	if(empty($obj->DomainID))
		$result = $obj->AddDomain();
	else
		$result = $obj->EditDomain();

	echo Response::createObjectiveResponse($result, $result ? $obj->DomainID : "");
	die();
}

function DeleteDomain(){
	
	$DomainID = $_POST["DomainID"];
	$result = BSC_ActDomain::DeleteDomain($DomainID);
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function SelectExpertDomainNodes(){

	$dt = PdoDataAccess::runquery("
		SELECT 
			ParentID,DomainID id,DomainDesc as text,'true' as leaf, 'javascript:void(0)' href,d.*
		FROM BSC_ExpertDomain d order by ParentID,DomainDesc");

    $returnArray = array();
    $refArray = array();

    foreach ($dt as $row) {
        if ($row["ParentID"] == 0) {
            $returnArray[] = $row;
            $refArray[$row["id"]] = &$returnArray[count($returnArray) - 1];
            continue;
        }

        $parentNode = &$refArray[$row["ParentID"]];

        if (!isset($parentNode["children"])) {
            $parentNode["children"] = array();
            $parentNode["leaf"] = "false";
			$parentNode["href"] = "";
        }
        $lastIndex = count($parentNode["children"]);
        $parentNode["children"][$lastIndex] = $row;
        $refArray[$row["id"]] = &$parentNode["children"][$lastIndex];
    }

	echo json_encode($returnArray);
	die();
}

function SaveExpertDomain(){
	
	$obj = new BSC_ExpertDomain();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->ParentID = $obj->ParentID == "src" ? "0" : $obj->ParentID;		
	
	if(empty($obj->DomainID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();

	echo Response::createObjectiveResponse($result, $result ? $obj->DomainID : "");
	die();
}

function DeleteExpertDomain(){
	
	$obj = new BSC_ExpertDomain();
	$obj->DomainID = $_POST["DomainID"];
	
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function SelectPersonExpertDomains(){
	
	$query = "select RowID,PersonID,d.DomainID,
		concat_ws(' ',fname,lname,CompanyName) fullname,DomainDesc
		
		from BSC_PersonExpertDomain d
		join BSC_ExpertDomain using(DomainID)
		join BSC_persons using(PersonID)
		
		where 1=1";
	$param = array();
	
	if(!empty($_REQUEST["fields"]) && !empty($_REQUEST["query"]))
	{
		if($_REQUEST["fields"] == "PersonID")
		{
			$query .= " AND concat_ws(' ',fname,lname,CompanyName) like :fl";
			$param[":fl"] = "%" . $_REQUEST["query"] . "%";
		}
		else
		{
			$query .= " AND DomainDesc like :d";
			$param[":d"] = "%" . $_REQUEST["query"] . "%";
		}
	}	
	
	$temp = PdoDataAccess::runquery_fetchMode($query, $param);
	$result = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($result, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SavePersonExpertDomain(){
	
	$obj = new BSC_PersonExpertDomain();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$result = $obj->Add();
	//print_r(ExceptionHandler::PopAllExceptions());	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeletePersonExpertDomain(){
	
	$obj = new BSC_PersonExpertDomain();
	$obj->RowID = $_POST["RowID"];
	
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function SelectCheckListSources(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=11");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SelectCheckLists(){
	
	$temp = BSC_CheckLists::Get(" AND SourceType=? " . dataReader::makeOrder() ,array($_REQUEST["SourceType"]));
	echo dataReader::getJsonData($temp->fetchAll(), $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveCheckList(){
	
	$obj = new BSC_CheckLists();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->ItemID == 0)
		$result = $obj->Add();
	else
		$result = $obj->Edit();

	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteCheckList(){
	
	$obj = new BSC_CheckLists($_POST["ItemID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function GetCheckValues(){
	
	$temp = PdoDataAccess::runquery("select c.ItemID,c.ItemDesc, if(v.ItemID is null,0,1) checked,
			v.DoneDate,v.description
		from BSC_CheckLists c
		left join BSC_CheckListValues v on(c.ItemID=v.ItemID AND SourceID=?) 
		where SourceType=? 
		order by ordering", array($_REQUEST["SourceID"], $_REQUEST["SourceType"]));
	
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveCheckValue(){
	
	$mode = $_REQUEST["checked"];
	if($mode == "1")
	{
		PdoDataAccess::runquery("insert into BSC_CheckListValues values(?,?,".PDONOW.",?)", 
			array($_REQUEST["ItemID"], $_REQUEST["SourceID"], $_REQUEST["description"]));
	}
	else
	{
		PdoDataAccess::runquery("delete from BSC_CheckListValues where ItemID=? AND SourceID=?", 
			array($_REQUEST["ItemID"], $_REQUEST["SourceID"]));
	}
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}
//.............................................

function SelectProcesses(){
	
	$where = "1=1";
	$param = array();
	
	if(isset($_REQUEST["ParentID"]))
	{
		$where .= " AND p.ParentID=:pid";
		$param[":pid"] = (int)$_REQUEST["ParentID"];
	}
	
	$dt = BSC_processes::SelectProcesss($where, $param,isset($_REQUEST["hasChild"]));
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();		
}

function SaveProcess() {

	$processObj = new BSC_processes();
	PdoDataAccess::FillObjectByArray($processObj, $_POST);
	$processObj->IsActive = 'YES';

	if ($_POST["old_ProcessID"] == '')
		$res = $processObj->InsertProcess();
	else
		$res = $processObj->UpdateProcess((int)$_POST["old_ProcessID"]);

	Response::createObjectiveResponse($res, $processObj->ProcessID);
	die();
}

function DeleteProcess() {

	$res = BSC_processes::DeleteProcess((int)$_POST['ProcessID']);
	echo Response::createResponse($res, "");
	die();
}

function GetProcessTree() {

	$nodes = PdoDataAccess::runquery("
			select *, concat('[',ProcessID,'] ',ProcessTitle) text
			from BSC_processes p
			where p.IsActive='YES' order by ParentID,ProcessID");
	$returnArr = TreeModulesclass::MakeHierarchyArray($nodes,"ParentID","ProcessID","text");
	echo json_encode($returnArr);
	die();
}

//------------------------------------------------

function selectBases(){
	$list = PdoDataAccess::runquery("select * from BaseInfo where TypeID=85 AND IsActive='YES'");
	echo dataReader::getJsonData($list, count($list), $_GET['callback']);
	die();
}

function selectMethods(){
	$list = PdoDataAccess::runquery("select * from BaseInfo where TypeID=86 AND IsActive='YES'");
	echo dataReader::getJsonData($list, count($list), $_GET['callback']);
	die();
}

function selectSharing(){

	$where = " AND s.ProcessID=? ";
	if(!isset($_REQUEST["AllHistory"]) || $_REQUEST["AllHistory"] == "false")
		$where .= " AND s.IsActive='YES'";
		
	$where .= " order by CostCode,IsActive desc,ChangeDate";
	
	$list = COM_sharing::Get($where, array($_REQUEST["ProcessID"]));
	echo dataReader::getJsonData($list->fetchAll(), $list->rowCount(), $_GET['callback']);
	die();
}

function saveSharing(){
	
	$obj = new COM_sharing();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(empty($obj->ShareID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();	
}

function DeleteSharing(){
	
	$obj = new COM_sharing($_POST["ShareID"]);
	$obj->ChangeDesc = $_POST["ChangeDesc"];
	$res = $obj->Remove();	
	echo Response::createObjectiveResponse($res, "");
	die();
}

?>
