<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-------------------------

include_once('../header.inc.php');
include_once inc_dataReader;
include_once inc_response;
include_once 'plan.class.php';
include_once '../baseinfo/elements.class.php';

$task = $_REQUEST["task"];
switch ($task) {
		
	default : 
		eval($task. "();");
}

function selectGroups(){
	
	$filled = !isset($_REQUEST["filled"]) ? "" : $_REQUEST["filled"];
	$filled = $filled == "true" ? true : false;
	
	$PlanID = $_REQUEST["PlanID"];
	
	$where = "";
	$params = array(":p" => $PlanID);
	
	$planObj = new PLN_plans($PlanID);
	if(isset($_SESSION["USER"]["portal"]) && $_SESSION["USER"]["PersonID"] == $planObj->PersonID)
		$where .= " AND g4.CustomerRelated='YES'";
	if(!empty($_REQUEST["ScopeID"]))
	{
		$where .= " AND g4.ScopeID=:sc";
		$params[":sc"] = $_REQUEST["ScopeID"];
	}
	
	$nodes = PdoDataAccess::runquery("select 
			g4.ParentID,
			g4.GroupID id, 
			g4.GroupDesc text , 
			'true' leaf ,
			'javascript:void(0)' href, 
			'true' expanded, 
			'' iconCls , 
			concat(if(count(pi.RowID)>0, 'filled ', ''), 
				case t.ActType when 'REJECT' then 'reject'
						   when 'CONFIRM' then 'confirm'
						   else '' end
			) cls,
			ifnull(ActDesc,'') qtip,
			g3.GroupID g3,
			g3.GroupDesc g3Desc,
			g2.GroupID g2,
			g2.GroupDesc g2Desc,
			g1.GroupID g1,
			g1.GroupDesc g1Desc,
			g0.GroupID g0,
			g0.GroupDesc g0Desc
		
		FROM PLN_groups g4
			join PLN_Elements e on(e.ParentID=0 AND g4.GroupID=e.GroupID)
			left join PLN_groups g3 on(g4.ParentID=g3.GroupID)
			left join PLN_groups g2 on(g3.ParentID=g2.GroupID)
			left join PLN_groups g1 on(g2.ParentID=g1.GroupID)
			left join PLN_groups g0 on(g1.ParentID=g0.GroupID)			
			
		left join PLN_PlanItems pi on(pi.PlanID=:p AND e.ElementID=pi.ElementID)
		left join (
			select p.GroupID,ActType,ActDesc from PLN_PlanSurvey p,
				(select GroupID,max(RowID) RowID from PLN_PlanSurvey where PlanID=:p AND GroupID>0
				group by GroupID)t
			where PlanID=:p AND p.RowID =t.RowID AND p.GroupID=t.GroupID
			group by GroupID
		)t on(g4.GroupID=t.GroupID)
		where 1=1 $where
		group by g4.GroupID
		" . ($filled ? " having count(pi.RowID)>0 " : "") . "
	", $params);
		
	$returnArr = array(); 
	$refArr = array();
	
	foreach($nodes as $node)
	{
		if(!empty($node["g0"]) && !isset($refArr[$node["g0"]]))
		{
			$returnArr[] = array(
				"id" => $node["g0"],
				"text" => $node["g0Desc"],
				"leaf" => "false",
				"expanded" => "true"
			);
			$refArr[ $node["g0"] ] = &$returnArr[ count($returnArr)-1 ];
		}
		if(!empty($node["g1"]) && !isset($refArr[$node["g1"]]))
		{
			$newnode = array(
				"id" => $node["g1"],
				"text" => $node["g1Desc"],
				"leaf" => "false",
				"expanded" => "true"
			);
			if(!empty($node["g0"]))
			{
				$parentNode = &$refArr[$node["g0"]];
				if (!isset($parentNode["children"])) 
					$parentNode["children"] = array();
				$lastIndex = count($parentNode["children"]);
				$parentNode["children"][$lastIndex] = $newnode;
				$refArr[ $node["g1"] ] = &$parentNode["children"][$lastIndex];
			}
			else
			{
				$returnArr[] = $newnode;
				$refArr[ $node["g1"] ] = &$returnArr[ count($returnArr)-1 ];
			}
		}
		if(!empty($node["g2"]) && !isset($refArr[$node["g2"]]))
		{
			$newnode = array(
				"id" => $node["g2"],
				"text" => $node["g2Desc"],
				"leaf" => "false",
				"expanded" => "true"
			);
			if(!empty($node["g1"]))
			{
				$parentNode = &$refArr[$node["g1"]];
				if (!isset($parentNode["children"])) 
					$parentNode["children"] = array();
				$lastIndex = count($parentNode["children"]);
				$parentNode["children"][$lastIndex] = $newnode;
				$refArr[ $node["g2"] ] = &$parentNode["children"][$lastIndex];
			}
			else
			{
				$returnArr[] = $newnode;
				$refArr[ $node["g2"] ] = &$returnArr[ count($returnArr)-1 ];
			}
		}
		if(!empty($node["g3"]) && !isset($refArr[$node["g3"]]))
		{
			$newnode = array(
				"id" => $node["g3"],
				"text" => $node["g3Desc"],
				"leaf" => "false",
				"expanded" => "true"
			);
			if(!empty($node["g2"]))
			{
				$parentNode = &$refArr[$node["g2"]];
				if (!isset($parentNode["children"])) 
					$parentNode["children"] = array();
				$lastIndex = count($parentNode["children"]);
				$parentNode["children"][$lastIndex] = $newnode;
				$refArr[ $node["g3"] ] = &$parentNode["children"][$lastIndex];
			}
			else
			{
				$returnArr[] = $newnode;
				$refArr[ $node["g3"] ] = &$returnArr[ count($returnArr)-1 ];
			}
		}
		//................................................................
			
		$parentNode = &$refArr[$node["ParentID"]];
		if (!isset($parentNode["children"])) {
			$parentNode["children"] = array();
			$parentNode["leaf"] = "false";
			unset($parentNode["href"]);
		}
		$lastIndex = count($parentNode["children"]);
		$parentNode["children"][$lastIndex] = $node;
		
		$refArr[ $node["id"] ] = &$parentNode["children"][$lastIndex];
	}
	//print_r($returnArr);
	for($i=0; $i < count($returnArr); $i++)
	{
		if(!isset($returnArr[$i]["children"]))
		{
			array_splice($returnArr, $i, 1);
			$i--;
		}
	}
	///print_r($returnArr);die();
	echo json_encode($returnArr);
	die();
}

function SelectElements(){

	$PlanID = $_REQUEST["PlanID"];
	$GroupID = $_REQUEST["GroupID"];
	$dt = PdoDataAccess::runquery("select e.* from PLN_Elements e
		where IsActive='YES' AND GroupID=? order by ElementID", array($GroupID));
	
	$planValues = array();
	for($i=0; $i < count($dt); $i++)
	{
		if($dt[$i]["ElementType"] == "grid")
			continue;
		if($dt[$i]["ElementType"] == "panel")
		{
			$temp = PLN_PlanItems::SelectAll("PlanID=? AND ElementID=?", array($PlanID, $dt[$i]["ElementID"]));
			if(count($temp) == 0)
				continue;
			$p = xml_parser_create();
			xml_parse_into_struct($p, $temp[0]["ElementValue"], $vals);
			xml_parser_free($p);
			$planValues[ $dt[$i]["ElementID"] ] = $vals;
		}
		else
		{
			if(!isset($planValues[ $dt[$i]["ParentID"] ]))
			{
				$dt[$i]["ElementValue"] = "";
				break;
			}
			
			$vals = $planValues[ $dt[$i]["ParentID"] ];
			foreach($vals as $element)
			{
				if($element["tag"] == "ELEMENT_" . $dt[$i]["ElementID"])
				{
					$dt[$i]["ElementValue"] = empty($element["value"]) ? "" : $element["value"];
					break;
				}
			}
		}
	}
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SelectPlanItems(){
	
	$PlanID = $_REQUEST["PlanID"];
	$ElementID = $_REQUEST["ElementID"];
	
	$dt = PLN_PlanItems::SelectAll("PlanID=? AND ElementID=?", array($PlanID, $ElementID));
	for($i=0; $i < count($dt); $i++)
	{
		$p = xml_parser_create();
		xml_parse_into_struct($p, $dt[$i]["ElementValue"], $vals);
		xml_parser_free($p);
		
		foreach($vals as $element)
		{
			if(strpos($element["tag"],"ELEMENT_") !== false)
				$dt[$i][strtolower($element["tag"]) ] = empty($element["value"]) ? "" : $element["value"];
		}		
		unset($dt[$i]["ElementValue"]);
	}	
		
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SavePlanItems(){
	
	$obj = new PLN_PlanItems();
	
	if(isset($_POST["record"]))
	{
		$st = stripslashes(stripslashes($_POST["record"]));
		$data = json_decode($st);
	
		$obj->RowID = $data->RowID;
		$obj->PlanID = $data->PlanID;
		$obj->ElementID = $data->ElementID;

		$xml = new SimpleXMLElement('<root/>');
		$elems = array_keys(get_object_vars($data));
		foreach($elems as $el)
		{
			if(strpos($el, "element_") === false)
				continue;
			$str = $data->$el;
			
			if(strlen($str) > 10 && substr($str,10) == "T00:00:00")
				$str = substr($str,0,10);	
			
			if(strlen($str) == 10 && $str[4] == "-" && $str[7] == "-")
				$str = preg_replace('/\-/', "/", $str);
			
			$xml->addChild($el, $str);
		}
		$obj->ElementValue = $xml->asXML();
	}
	else
	{
		$obj->PlanID = $_POST["PlanID"];
		$obj->ElementID = $_POST["ElementID"];
		
		$dt = PdoDataAccess::runquery("select RowID from PLN_PlanItems where PlanID=? AND ElementID=?",
			array($obj->PlanID, $obj->ElementID));
		if(count($dt)>0)
			$obj->RowID = $dt[0]["RowID"];		
		
		$xml = new SimpleXMLElement('<root/>');
		foreach($_POST as $key => $value)
		{
			if(strpos($key, "element_") === false)
				continue;
			$xml->addChild($key, $value);
		}
		$obj->ElementValue = $xml->asXML();
	}
	
	if($obj->RowID > 0)
		$result = $obj->EditItem();
	else
		$result = $obj->AddItem();
	
	$elemObj = new PLN_Elements($obj->ElementID);
	
	$obj2 = new PLN_PlanSurvey();
	$obj2->PlanID = $obj->PlanID;
	$obj2->GroupID = $elemObj->GroupID;
	$obj2->ActType = "EDIT";
	$obj2->ActDate = PDONOW;
	$obj2->ActPersonID = $_SESSION["USER"]["PersonID"];
	$result = $obj2->AddRow();

	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeletePlanItem(){
	
	$RowID = $_POST["RowID"];
	
	$result = PLN_PlanItems::DeleteItem($RowID);
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function selectRequestStatuses(){
	
	$dt = PdoDataAccess::runquery("select * from WFM_FlowSteps where IsOuter='YES' AND FlowID=" . FLOWID );
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

//............................................

function SurveyGroup(){
	
	$PlanID = $_POST["PlanID"];
	$GroupID = $_POST["GroupID"];
	$mode = $_POST["mode"];
	$ActDesc = $_POST["ActDesc"];
	
	$obj = new PLN_PlanSurvey();
	$obj->PlanID = $PlanID;
	$obj->GroupID = $GroupID;
	$obj->ActType = $mode;
	$obj->ActDate = PDONOW;
	$obj->ActDesc = $ActDesc;
	$obj->ActPersonID = $_SESSION["USER"]["PersonID"];
	
	$result = $obj->AddRow();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//............................................

function SelectAllPlans(){
	
	$param = array();
	$where = "1=1 ";
	if(!empty($_REQUEST["PlanID"]))
	{
		$where .= " AND PlanID=:pid";
		$param[":pid"] = $_REQUEST["PlanID"];
	}
	
	if(isset($_SESSION["USER"]["portal"]))
	{
		if($_SESSION["USER"]["IsExpert"] == "YES")
		{
			$where .= " AND e.PersonID=:ep AND e.StatusDesc<>'SEND' ";
			$param[":ep"] = $_SESSION["USER"]["PersonID"];
		}
		if($_SESSION["USER"]["IsSupporter"] == "YES")
		{
			$where .= " AND SupportPersonID=:sp AND p.StepID=" . STEPID_SEND_SUPPORTER;
			$param[":sp"] = $_SESSION["USER"]["PersonID"];
		}
	}
	else if(isset($_REQUEST["expert"]))
	{
		$where .= " AND e.PersonID=:ep AND e.StatusDesc<>'SEND' ";
		$param[":ep"] = $_SESSION["USER"]["PersonID"];
	}
	else
	{
		if(!isset($_REQUEST["AllPlans"]) || $_REQUEST["AllPlans"] == "false")
			$where .= " AND p.StepID in(" . STEPID_CUSTOMER_SEND /*. "," . STEPID_ENDFLOW*/ . ")";
	}
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
		$field = $field == "ReqFullname" ? "concat_ws(' ',p1.fname,p1.lname,p1.CompanyName)" : $field;
        $where .= ' and ' . $field . ' like :fld';
        $param[':fld'] = '%' . $_REQUEST['query'] . '%';
    }
	
	$dt = PLN_plans::SelectAll($where, $param, dataReader::makeOrder());
	//print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();
	$count = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);	
	echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
	die();
}

function SelectMyPlans(){
	
	$param = array($_SESSION["USER"]["PersonID"]);
	$where = "p.PersonID=?";
	
	$dt = PLN_plans::SelectAll($where, $param, dataReader::makeOrder());
	print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();
	$count = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);	
	echo dataReader::getJsonData($dt, $count, $_GET["callback"]);
	die();
}

function SaveNewPlan(){
	
	$PlanID = $_POST["PlanID"];
	
	$obj = new PLN_plans();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if($PlanID*1 == 0)
	{
		if(isset($_SESSION["USER"]["framework"]))
			$obj->PersonID = $_POST["PersonID"];
		else
			$obj->PersonID = $_SESSION["USER"]["PersonID"];		
		$obj->RegDate = PDONOW;
		$obj->StepID = !isset($_SESSION["USER"]["framework"]) ? STEPID_RAW : STEPID_CUSTOMER_SEND;
		$result = $obj->AddPlan();
		
		PLN_plans::ChangeStatus($obj->PlanID, $obj->StepID , "", true);
	}
	else
	{
		$obj->PlanID = $PlanID;
		$result = $obj->EditPlan();
	}
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, $obj->PlanID);
	die();	
}

function ChangeStatus(){
	
	$StepID = $_POST["StepID"];
	$ActDesc = $_POST["ActDesc"];
	$PlanID = $_REQUEST["PlanID"];
	$obj = new PLN_plans($PlanID);
	
	//-------------------- control valid operation -----------------------
	if($_SESSION["USER"]["IsCustomer"] == "YES" && isset($_SESSION["USER"]["portal"]) && 
		$obj->PersonID != $_SESSION["USER"]["PersonID"])
	{
		Response::createObjectiveResponse(false, "");
		die();
	}	
	//--------------- check filling Mandatory groups ---------------------
	if($obj->PersonID == $_SESSION["USER"]["PersonID"] && $StepID == STEPID_CUSTOMER_SEND)
	{
		$dt = PdoDataAccess::runquery("
			SELECT concat_ws(' / ',g1.GroupDesc,g2.GroupDesc,g3.GroupDesc ,g.GroupDesc)  
			FROM PLN_groups g
			left join PLN_groups g3 on(g3.GroupID=g.ParentID)
			left join PLN_groups g2 on(g2.GroupID=g3.ParentID)
			left join PLN_groups g1 on(g1.GroupID=g2.ParentID)
			
			join PLN_Elements e on(g.GroupID=e.GroupID)
			left join PLN_PlanItems pe on(pe.PlanID=? AND pe.ElementID=e.ElementID)
			where g.IsMandatory='YES' AND e.ParentID=0 AND pe.PlanID is null", array($PlanID));
		if(count($dt) > 0)
		{
			$msg = array();
			foreach($dt as $row)
				$msg[] = $row[0];
			Response::createObjectiveResponse(false, "جهت ارسال طرح تکمیل بخش های زیر الزامی است <br>" . 
				implode("<br>", $msg));
			die();
		}
	}
	//--------------------------------------------------------------------
	
	if($_SESSION["USER"]["IsCustomer"] == "YES" && isset($_SESSION["USER"]["portal"]))
		$StepID = STEPID_CUSTOMER_SEND;
	
	if(isset($_SESSION["USER"]["framework"]) && $StepID == STEPID_CONFIRM)
	{
		$dt = PdoDataAccess::runquery("
			select p.GroupID,ActType from PLN_PlanSurvey p,
				(select GroupID,max(RowID) RowID from PLN_PlanSurvey where PlanID=2 AND GroupID>0
				group by GroupID)t
			where PlanID=2 AND p.RowID =t.RowID AND p.GroupID=t.GroupID AND ActType='REJECT'
			group by GroupID", array(":p" => $PlanID));
		if(count($dt)>0)
		{
			echo Response::createObjectiveResponse(false, "بعضی از جداول رد شده اند و قادر به تایید طرح نمی باشید");
			die();
		}
	}

	$result = PLN_plans::ChangeStatus($obj->PlanID, $StepID, $ActDesc);
	
	/*if($StepID == STEPID_CONFIRM)
	{
		$result = WFM_FlowRows::StartFlow(FLOWID, $obj->PlanID);
	}*/
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function GetExpertsSummary(){
	
	$dt = PLN_experts::Get(" AND (IsSeen='NO' AND StatusDesc='SEND') or (StatusDesc='RAW' AND EndDate<=now())
		order by RegDate desc");
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}
//............................................

function GetPlanExperts(){
	
	$temp = PLN_experts::Get("AND PlanID=?", array($_REQUEST["PlanID"]));
	
	print_r(ExceptionHandler::PopAllExceptions());
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SavePlanExpert(){
	
	$obj = new PLN_experts();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(empty($obj->RowID))
	{
		$obj->RegDate = PDONOW;
		$result = $obj->Add();
	}
	else
	{
		$result = $obj->Edit();
	}
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function DeletePlanExpert(){
	
	$obj = new PLN_experts();
	$obj->RowID = $_POST["RowID"];
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

function SendExpert(){
	
	$dt = PLN_experts::Get(" AND PlanID=? AND PersonID=?", array($_POST["PlanID"], $_SESSION["USER"]["PersonID"]));
	if($dt->rowCount() == 0)
	{
		echo Response::createObjectiveResponse(false, "دسترسی غیر مجاز");
		die();
	}
	$dt = $dt->fetch();
	
	$obj = new PLN_experts($dt["RowID"]);
	$obj->DoneDesc = $_POST["DoneDesc"];
	$obj->DoneDate = PDONOW;
	$obj->StatusDesc = "SEND";
	$result = $obj->Edit();
	
	Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function SeeExpert(){
	
	$obj = new PLN_experts();
	$obj->RowID = $_POST["RowID"];
	$obj->IsSeen = "YES";
	$obj->Edit();
	
	Response::createObjectiveResponse(true,"");
	die();
}

//............................................

function GetPlanEvents(){
	
	$temp = PLN_PlanEvents::Get("AND PlanID=?", array($_REQUEST["PlanID"]));
	
	//print_r(ExceptionHandler::PopAllExceptions());
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SavePlanEvents(){
	
	$obj = new PLN_PlanEvents();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(empty($obj->EventID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function DeletePlanEvents(){
	
	$obj = new PLN_PlanEvents();
	$obj->EventID = $_POST["EventID"];
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

?>