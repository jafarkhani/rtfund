<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 97.02
//-------------------------

require_once('../../header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once 'alarm.class.php';

$task = isset($_REQUEST["task"]) ? $_REQUEST["task"] : "";
switch($task)
{
	case "SelectAlarms":
	case "SelectObjects":	
	case "SaveAlarm":
	case "DeleteAlarm":
		$task();
}

function SelectAlarms(){
	
	$param = array();
	$where = "";
	
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
        $where .= ' and ' . $field . ' like :fld';
        $param[':fld'] = '%' . $_REQUEST['query'] . '%';
    }
		
	$dt = NTC_alarms::Get($where . dataReader::makeOrder(), $param);
	print_r(ExceptionHandler::PopAllExceptions());
	$count = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);	
	
	echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
	die();
}

function SelectObjects(){
	
	$where  = "";
	$param = array();
	
	if(!empty($_REQUEST["ObjectID"]))
	{
		$where .= " AND ObjectID=?";
		$param[] = $_REQUEST["ObjectID"];
	}
	$temp = PdoDataAccess::runquery("select * from NTC_AlarmObjects where 1=1" . $where, $param);
	for($i=0; $i<count($temp); $i++)
	{
		$dt = PdoDataAccess::runquery("select * from NTC_AlarmObjItems where ObjectID=? order by ordering", 
			array($temp[$i]["ObjectID"]));
		$header = $body = "";		
		foreach($dt as $row)
		{
			$header .= "<th>[col" . $row["ordering"] . "]</th>";
			$body .= "<td>" . $row["title"] . "</td>";
		}
		$temp[$i]["itemsInfo"] = "<table class=ObjectItemIfo><tr>" . $header . "</tr><tr>" . $body . "</tr></table>";
	}
	
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveAlarm(){
	
	$obj = new NTC_alarms();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$obj->GroupLetter = isset($_POST["GroupLetter"]) ? "YES" : "NO";
	
	if(empty($obj->AlarmID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function DeleteAlarm(){
	
	$obj = new NTC_alarms($_POST["AlarmID"]);
	if(!$obj->Remove())
	{
		echo Response::createObjectiveResponse(false, "خطا در حذف");
		die();
	}

	echo Response::createObjectiveResponse(true, "");
	die();
}

?>