<?php
//-------------------------
// programmer:	Mokhtari
// Create Date:	98.06
//-------------------------

require_once("conf/header.inc.php");
require_once 'alterPersons.class.php';
			
require_once inc_dataReader;
require_once inc_response;

if(isset($_REQUEST["task"]))
{
	switch ($_REQUEST["task"])
	{
		case "SaveNewAlterPerson":
		case "selectAlterPersons":
		case "selectPersons":
        case "DeletePerson":
			
			call_user_func($_REQUEST["task"]);
	}
}
function selectPersons(){

    $where = "1=1";
    $param = array();

    if(!empty($_REQUEST["PersonID"]))
    {
        $where .= " AND AlterPersonID=:p";
        $param[":p"] = $_REQUEST["PersonID"];
    }

        $temp = BSC_AlterPersons::SelectAll($where . dataReader::makeOrder(), $param);

    $no = $temp->rowCount();
    $temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);

    if(!empty($_REQUEST["EmptyRow"]) && empty($_REQUEST["PersonID"]))
    {
        $temp = array_merge(array(array("PersonID" => 0)), $temp);
    }

    echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
    die();
}
function selectAlterPersons(){
	$where = "1=1";
	$param = array();

if (isset($_REQUEST['fields']) && isset($_REQUEST['query']))
    {
        /*var_dump($_REQUEST['fields']);
        echo '<br>';
        var_dump($_REQUEST['query']);
        echo '<br>';*/
        $field = $_REQUEST['fields'];
        /*$field = $_REQUEST['fields'] == "fullname" ? "concat_ws(' ',fname,lname,CompanyName)" : $field;*/
        $where .= ' and ' . $field . ' like :fld';
        $_REQUEST['query'] = $_REQUEST['query'] == "*" ? "YES" : $_REQUEST['query'];
        $param[':fld'] = '%' . $_REQUEST['query'] . '%';
    }

    if(!empty($_REQUEST["PersonID"]))
    {
        $where .= " AND PersonID=:p";
        $param[":p"] = $_REQUEST["PersonID"];
    }

    if(!empty($_REQUEST["query"]) && !isset($_REQUEST['fields']))
    {
        $where .= " AND ( concat(fname,' ',lname) like :p or CompanyName like :p)";
        $param[":p"] = "%" . $_REQUEST["query"] . "%";
    }
    
		$temp = BSC_AlterPersons::SelectAll($where . dataReader::makeOrder(), $param);

	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	
	if(!empty($_REQUEST["EmptyRow"]) && empty($_REQUEST["AlterPersonID"]))
	{
		$temp = array_merge(array(array("AlterPersonID" => 0)), $temp);
	}
	
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}
function SaveNewAlterPerson(){
	$obj = new BSC_AlterPersons();

	PdoDataAccess::FillObjectByArray($obj, $_POST);

	if($obj->AlterPersonID > 0)
		$result = $obj->EditPerson();
	else 
		$result = $obj->AddPerson();

	echo Response::createObjectiveResponse($result, !$result ? ExceptionHandler::GetExceptionsToString() : "");
	die();
}
function DeletePerson(){

    $result = BSC_Alterpersons::DeletePerson($_POST["PersonID"]);
    echo Response::createObjectiveResponse($result, "");
    die();
}

?>
