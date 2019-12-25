<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 95.02
//-------------------------
require_once('../header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once 'traffic.class.php';
require_once "../../office/workflow/wfm.class.php";

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
switch ($task) {

	case "AddTraffic":
	case "GetMyRequests":
	case "GetAllRequests":
	case "SaveRequest":
	case "DeleteRequest":
	case "selectOffTypes":
	case "selectMeans":
	case "ChangeStatus":
	case "ArchiveRequest":
	case "SelectDayTraffics":
	case "DeleteTraffic":
	case "GetExtraInfo":
	case "ImportTrafficsFromExcel":
		
	case "StartFlow":
		$task();
}

function AddTraffic(){
	
	$obj = new ATN_traffic();
	$obj->TrafficDate = PDONOW;
	$obj->TrafficTime = DateModules::NowTime();
	$obj->IsSystemic = "YES";
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	$result = $obj->Add();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function GetMyRequests(){
	
	$dt = ATN_requests::Get(" AND t.PersonID=?" . dataReader::makeOrder(), 
		array($_SESSION["USER"]["PersonID"]));
	print_r(ExceptionHandler::PopAllExceptions());
	$result = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($result, $dt->rowCount(), $_GET["callback"]);
	die();
}

function GetAllRequests(){
	
	$where = "";
	$param = array();
	
	if(!empty($_REQUEST["RequestID"]))
	{
		$where .= " AND	RequestID=:r ";
		$param[":r"] = $_REQUEST["RequestID"];
	}
	
	if(!empty($_REQUEST["fields"]) && !empty($_GET["query"]))
	{
		$field = $_REQUEST["fields"] == "fullname" ? "concat(p1.fname,' ',p1.lname)" : $_REQUEST["fields"];
		$field = $_REQUEST["fields"] == "ReqType" ? 
				"case ReqType when 'CORRECT' then'فراموشی'
							when 'DayOFF' then 'مرخصی روزانه'
							when 'OFF' then 'مرخصی ساعتی'
							when 'DayMISSION' then 'ماموریت روزانه'
							when 'MISSION' then 'ماموریت ساعتی'
							when 'EXTRA' then 'اضافه کار غیر مجاز'
							when 'CHANGE_SHIFT' then 'تغییر شیفت روزانه' end " : $_REQUEST["fields"];
		
		$where .= " AND	" . $field . " like :q";
		$param[":q"] = "%" . $_GET["query"] . "%";
	}
	
	if(isset($_REQUEST["AllReqs"]) && $_REQUEST["AllReqs"] == "false")
	{
		$where .= " AND IsArchive='NO'";
	}	
	
	$dt = ATN_requests::Get($where . dataReader::makeOrder(), $param);
	//print_r(ExceptionHandler::PopAllExceptions());	
	$result = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($result, $dt->rowCount(), $_GET["callback"]);
	die();
}

function SaveRequest(){
	
	$obj = new ATN_requests();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if($obj->ReqType != "CORRECT")
	{
		if(!empty($obj->ToDate))
		{
			$dt = PdoDataAccess::runquery("
				select * from ATN_requests 
				where PersonID=:p AND RequestID<>:r 
				AND ( (FromDate<=:f AND ToDate>=:f) OR (FromDate<=:t AND ToDate>=:t) )
			", array(
				":p" => $_SESSION["USER"]["PersonID"],
				":r" => $obj->RequestID,
				":f" => DateModules::shamsi_to_miladi($obj->FromDate, "-"),
				":t" => DateModules::shamsi_to_miladi($obj->ToDate, "-")
			));
			if(count($dt) > 0)
			{
				echo Response::createObjectiveResponse(false, "در بازه زمانی وارد شده قبلا درخواستی ثبت شده است");
				die();
			}
		}
		else
		{
			$dt = PdoDataAccess::runquery("
				select * from ATN_requests 
				where PersonID=:p AND RequestID<>:r 
				AND (
						(FromDate<=:f AND ToDate>=:f) OR 
						( if(ToDate is null,FromDate=:f,FromDate<=:f AND ToDate>=:f) AND 
							StartTime<=:st AND EndTime>=:st) OR 
						( if(ToDate is null,FromDate=:f,FromDate<=:f AND ToDate>=:f) AND 
							StartTime<=:et AND EndTime>= :et) )
			", array(
				":p" => $_SESSION["USER"]["PersonID"],
				":r" => $obj->RequestID,
				":f" => DateModules::shamsi_to_miladi($obj->FromDate, "-"),
				":st" => $obj->StartTime,
				":et" => $obj->EndTime
			));
			
			//echo PdoDataAccess::GetLatestQueryString();die();
			
			if(count($dt) > 0)
			{
				echo Response::createObjectiveResponse(false, "در بازه زمانی وارد شده قبلا درخواستی ثبت شده است");
				die();
			}
		}
	}
	
	if(empty($obj->RequestID))
	{
		$obj->PersonID = $_SESSION["USER"]["PersonID"];
		$obj->ReqDate = PDONOW;
		$result = $obj->Add();
	}
	else
		$result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteRequest(){
	
	$obj = new ATN_requests($_POST["RequestID"]);

	if($obj->ReqStatus != "1")
	{
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	$result = $obj->Remove();
	//print_r(ExceptionHandler::PopAllExceptions());
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function selectOffTypes(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=20 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectMeans(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=23  AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function ChangeStatus(){
	
	$obj = new ATN_requests($_POST["RequestID"]);
	$obj->ReqStatus = $_POST["mode"];
	$obj->SurveyPersonID = $_SESSION["USER"]["PersonID"];
	$obj->SurveyDate = PDONOW;
	$obj->SurveyDesc = $_POST["SurveyDesc"];
	$obj->ConfirmExtra = $_POST["ConfirmExtra"];
	
	if($obj->ReqType == "EXTRA")
	{
		$dt = ATN_requests::Get(" AND RequestID=?", array($obj->RequestID));
		$dt = $dt->fetch();
		$SUM = ATN_traffic::Compute($dt["FromDate"], $dt["FromDate"], $dt["PersonID"]);
		$obj->RealExtra = $SUM["extra"];
		$obj->LegalExtra = $SUM["LegalExtra"];
	}
	$result = $obj->Edit();
	
	/*if($obj->ReqType == "CORRECT")
	{
		$obj2 = new ATN_traffic();
		$obj2->IsSystemic = "NO";
		$obj2->RequestID = $obj->RequestID;
		$obj2->PersonID = $obj->PersonID;
		$obj2->TrafficDate = $obj->FromDate;
		$obj2->TrafficTime = $obj->StartTime;
		$result = $obj2->Add();
	}*/
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ArchiveRequest(){
	
	$obj = new ATN_requests($_POST["RequestID"]);
	$obj->IsArchive = "YES";
	$result = $obj->Edit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function SelectDayTraffics(){
	
	$params = array();
	$params[":d"] = $_GET["TrafficDate"];
	
	if($_REQUEST["admin"] == "true")
	{
		$params[":p"] = $_GET["PersonID"];
		$ReqStatusWhere = " AND ReqStatus=".ATN_STEPID_CONFIRM;
	}
	else
	{
		$params[":p"] = $_SESSION["USER"]["PersonID"];
		$ReqStatusWhere = "";
	}
	
	$dt = PdoDataAccess::runquery("
		select * from (

			select 'user' ReqType, TrafficID,TrafficDate,TrafficTime,'' EndTime,IsActive 
			from ATN_traffic where PersonID=:p AND TrafficDate=:d

			union all

			select t.ReqType,null,t.FromDate,StartTime,EndTime,'YES'
			from ATN_requests t
			where t.PersonID=:p AND t.ReqType in('CORRECT','OFF','MISSION') 
				AND t.ToDate is null $ReqStatusWhere AND t.FromDate=:d

		)t 
		order by TrafficTime,ReqType",$params);
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function DeleteTraffic(){
	
	$TrafficID = $_POST["TrafficID"];
	$mode = $_POST["DeleteMode"] == "delete" ? "NO" : "YES";
	PdoDataAccess::runquery("update ATN_traffic set IsActive='".$mode."' where TrafficID=?",
		array($TrafficID));
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetExtraInfo(){
	
	$RequestID = $_REQUEST["RequestID"];
	$dt = ATN_requests::Get(" AND RequestID=?", array($RequestID));
	$dt = $dt->fetch();
	$SUM = ATN_traffic::Compute($dt["FromDate"], $dt["FromDate"], $dt["PersonID"]);
	echo dataReader::getJsonData($SUM, 1, $_GET["callback"]);
	die();
}

//.................................

function StartFlow(){
	
	$RequestID = $_REQUEST["RequestID"];
	$obj = new ATN_requests($RequestID);
	$FlowID = constant("FLOWID_TRAFFIC_" . $obj->ReqType);
	
	$result = WFM_FlowRows::StartFlow($FlowID, $RequestID);
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ImportTrafficsFromExcel(){
	
	require_once inc_phpExcelReader;

	$data = new Spreadsheet_Excel_Reader();
	$data->setOutputEncoding('utf-8');
	$data->setRowColOffset(0);
	$data->read($_FILES["attach"]["tmp_name"]);

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$errors = "";
	$device = $_POST["device"];
	
	for ($i = 1; $i < $data->sheets[0]['numRows']; $i++) 
	{
		$row = $data->sheets[0]['cells'][$i];
		
		if($device == "park")
		{
			$AttCode = $row[2];
			$TrafficDate = $row[0];
			$TrafficTime = isset($row[4]) && trim($row[4]) ? $row[4] : "";
			$TrafficTime2 = isset($row[5]) && trim($row[5]) ? $row[5] : "";
		}
		else
		{
			$AttCode = $row[0];
			$TrafficDate = $row[3];
			$TrafficTime = isset($row[4]) && trim($row[4]) ? $row[4] : "";
			$TrafficTime2 = "";
		}
		
		$dt = PdoDataAccess::runquery("select PersonID from BSC_persons where AttCode=?", array($AttCode));
		if(count($dt) == 0)
		{
			$errors .= "کد عضو " . $AttCode . " در ذینفعان یافت نشد." . "<br>";
			continue;
		}
		
		$obj = new ATN_traffic();
		$obj->PersonID = $dt[0][0];
		$obj->TrafficDate = DateModules::shamsi_to_miladi($TrafficDate, "-");
		$obj->IsSystemic = 'NO'; 
		$obj->IsActive = "YES";
		
		PdoDataAccess::runquery("update ATN_traffic set IsActive='NO' where PersonID=? AND TrafficDate=? AND IsSystemic='YES'", array(
				$obj->PersonID, $obj->TrafficDate));
		
		if($TrafficTime != "")
		{
			$obj->TrafficTime = $TrafficTime;
			
			$dt = PdoDataAccess::runquery("select * from ATN_traffic where PersonID=? AND TrafficDate=? AND TrafficTime=?", array(
				$obj->PersonID, $obj->TrafficDate , $obj->TrafficTime));
			if(count($dt) > 0)
			{
				$errors .= "برای ردیف " . ($i+1) . "با ساعت ".$obj->TrafficTime." قبلا تردد در سیستم ثبت شده است" . "<br>";
			}
			else
			{
				$result = $obj->Add($pdo);
				if(!$result)
				{
					$errors .=  "خطا در ذخیره ردیف ورود " . ($i+1) . "<br>";
				}
			}
		}
		if($TrafficTime2 != "")
		{
			unset($obj->TrafficID);
			$obj->TrafficTime = $TrafficTime2;
			
			$dt = PdoDataAccess::runquery("select * from ATN_traffic where PersonID=? AND TrafficDate=? AND TrafficTime=?", array(
				$obj->PersonID, $obj->TrafficDate , $obj->TrafficTime));
			if(count($dt) > 0)
			{
				$errors .= "برای ردیف " . ($i+1) . "با ساعت ".$obj->TrafficTime." قبلا تردد در سیستم ثبت شده است" . "<br>";
			}
			else
			{
				$result = $obj->Add($pdo);
				if(!$result)
				{
					$errors .=  "خطا در ذخیره ردیف ورود " . ($i+1) . "<br>";
				}
			}
		}
	}
	$pdo->commit();
	echo Response::createObjectiveResponse($errors == "", $errors);
	die();
}
?>
