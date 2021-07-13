<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.04
//-----------------------------

require_once '../../header.inc.php';
require_once '../docs/doc.class.php';
require_once '../docs/import.data.php';
require_once 'safebox.class.php';
require_once inc_dataReader;
require_once inc_response;

$task = isset($_REQUEST["task"]) ? $_REQUEST["task"] : "";
if(!empty($task))
	$task();




//---------------------------------------------

function SelectPercents(){
	
	$temp = SFBX_holding::Get(dataReader::makeOrder());
	$dt = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);

	echo dataReader::getJsonData($dt, $temp->rowCount(), $_GET['callback']);
	die();
}

function SavePercent(){
	
	$obj = new SFBX_holding();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->holdingID != "")
		$result = $obj->Edit();
	else
		$result = $obj->Add();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeletePercent(){
	
	$obj = new SFBX_holding($_POST["holdingID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

?>