<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.7
//-------------------------
require_once('../header.inc.php');
require_once inc_dataReader;
require_once inc_response;

if(isset($_REQUEST["task"]))
{
	switch ($_REQUEST["task"])
	{
		case "SendRegisterProcess":
			call_user_func($_REQUEST["task"]);
	}
}

function SendRegisterProcess(){
	
	require_once '../framework/baseInfo/baseInfo.class.php';
	require_once "../office/workflow/wfm.class.php";

	$pObj = new BSC_processes(PROCESS_REGISTRATION);
	$result = WFM_FlowRows::StartFlow($pObj->FlowID, PROCESS_REGISTRATION, $_SESSION["USER"]["PersonID"]);
	echo Response::createObjectiveResponse($result, "");
	die();
}

?>
