<?php
//---------------------------
// developer:	Sh.Jafarkhani
// Date:		97.10
//---------------------------
require_once '../header.inc.php';
require_once(inc_response);
require_once(inc_dataReader);
require_once './baseinfo/baseinfo.class.php';
require_once '../loan/request/request.class.php';
require_once './ComputeItems.class.php';
require_once './ExecuteEvent.class.php';
	
$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';

switch ($task) {
	
	case "selectEventRows":
	case "RegisterEventDoc":
	
		$task();
}

function selectEventRows(){
	
	$EventID = $_REQUEST["EventID"]*1;
	$eObj = new ExecuteEvent($EventID);
	
	$where = " er.IsActive='YES' AND EventID=? ";
	$where .= " order by CostType,CostCode";
	$list = COM_EventRows::SelectAll($where, array($EventID));
echo PdoDataAccess::GetLatestQueryString();
	//-------------- set source objects ----------------
	$SourcesArr = array();
	if(!empty($_REQUEST["SourceID1"]))
		$SourcesArr[] = $_REQUEST["SourceID1"];
	if(!empty($_REQUEST["SourceID2"]))
		$SourcesArr[] = $_REQUEST["SourceID2"];
	if(!empty($_REQUEST["SourceID3"]))
		$SourcesArr[] = $_REQUEST["SourceID3"];
	
	//--------------- get compute items values -----------
	$returnArr = array();
	for($i=0; $i < count($list); $i++)
	{
		$result = EventComputeItems::SetSpecialTafsilis($list[$i], $SourcesArr);
		$list[$i]["TafsiliID1"] = $result[0]["TafsiliID"];
		$list[$i]["TafsiliDesc1"] = $result[0]["TafsiliDesc"];
		$list[$i]["TafsiliID2"] = $result[1]["TafsiliID"];
		$list[$i]["TafsiliDesc2"] = $result[1]["TafsiliDesc"];
		$list[$i]["TafsiliID3"] = $result[2]["TafsiliID"];
		$list[$i]["TafsiliDesc3"] = $result[2]["TafsiliDesc"]; 
		
		$obj = new ACC_DocItems();
		$result = EventComputeItems::SetParams($eObj->EventID, $list[$i], $SourcesArr, $obj);
		$list[$i]["param1"] = $obj->param1;
		$list[$i]["param2"] = $obj->param2;
		$list[$i]["param3"] = $obj->param3;
			
		if($list[$i]["ComputeItemID"]*1 > 0 && $eObj->EventFunction != "")
		{
			$value = call_user_func($eObj->EventFunction, $list[$i]["ComputeItemID"], $SourcesArr);
			
			if(is_array($value))
			{
				continue;
				if(isset($value["amount"]))
				{
					if($list[$i]["CostType"] == "DEBTOR")
						$list[$i]["DebtorAmount"] = $value["amount"];
					else
						$list[$i]["CreditorAmount"] = $value["amount"];
					
					foreach($value as $k => $v)
							$list[$i][$k] = $v;
					
					SetParamValues($list[$i]);
					$returnArr[] = $list[$i];
				}
				else
				{
					foreach($value as $val)
					{
						if(is_array($val) && isset($val["amount"]))
							$amount = $val["amount"];
						else
							$amount = $val;
						
						if($list[$i]["CostType"] == "DEBTOR")
							$list[$i]["DebtorAmount"] = $amount;
						else
							$list[$i]["CreditorAmount"] = $amount;
						
						foreach($val as $k => $v)
							$list[$i][$k] = $v;
						
						SetParamValues($list[$i]);
						$returnArr[] = $list[$i];
					}
				}
				continue;
			}
			
			if($list[$i]["CostType"] == "DEBTOR")
				$list[$i]["DebtorAmount"] = $value;
			else
				$list[$i]["CreditorAmount"] = $value;
		}
		SetParamValues($list[$i]);
		$returnArr[] = $list[$i];
	}
	
	//print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();
	echo dataReader::getJsonData($returnArr, count($returnArr), $_GET['callback']);
	die();
}

function SetParamValues(&$list){
	for($j=1; $j<=3; $j++)
	{
		if(!empty($list["SrcTable" . $j]))
		{
			$dt = PdoDataAccess::runquery("select ". $list["SrcDisplayField" . $j] . " as title " .
				" from " . $list["SrcTable" . $j] . 
				" where " . $list["SrcValueField" . $j] . "=?", array($list["param" . $j]));
			if(count($dt) > 0)
				$list["ParamValue" . $j] = $dt[0]["title"];
		}
	}
}

function RegisterEventDoc(){
	
	$EventID = (int)$_POST["EventID"];
	$SourceIDs = isset($_POST["SourcesArr"]) ? $_POST["SourcesArr"] : array();
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new ExecuteEvent($EventID);
	$obj->Sources = $SourceIDs;
	$result = $obj->RegisterEventDoc($pdo);
	if(!$result)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	
	$pdo->commit();
	Response::createObjectiveResponse(true, "سند شماره " . $obj->DocObj->LocalNo . " با موفقیت صادر گردید");
	die();
	
}

?>