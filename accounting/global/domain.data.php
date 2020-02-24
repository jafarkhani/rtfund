<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../../header.inc.php';
require_once inc_dataReader;
require_once inc_response;

if(!empty($_REQUEST["task"]))
	$_REQUEST["task"]();

function GetAccessBranches(){
	
	$branches = FRW_access::GetAccessBranches();
	$dt = PdoDataAccess::runquery("select * from BSC_branches where BranchID in(" .
		implode(",", $branches) . ")");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectCycles(){
	
	$dt = PdoDataAccess::runquery("select * from ACC_cycles");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SelectDocTypes(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=9 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}
?>