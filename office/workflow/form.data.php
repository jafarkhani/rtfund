<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.08
//-----------------------------

require_once '../header.inc.php';
require_once 'wfm.class.php';
require_once 'form.class.php';
require_once inc_dataReader;
require_once inc_response;

$task = !empty($_REQUEST["task"]) ? $_REQUEST["task"] : "";
switch($task)
{
	case "SelectForms":
	case "selectFormItems":
	
	case "selectGridColumns":
	case "saveColumn":
	case "deleteColumn":
	case "SelectGridRows":
	case "SaveGridRow":
	case "DeleteGridRow":
		
	case "GetEmptyFormID":
	case "SaveForm":
	case "saveFormItem":
	case "GetFormContent":
	case "GetFormTitle":
	case "deleteFormItem":
	case "deleteForm":
	case "PrepareContentToEdit":
	case "CopyForm":
	case "SelectGroups":
	case "SaveGroup":
	case "DeleteGroup":
	case "MoveGroup":
	case "MoveItem":
	case "SelectValidForms":
	case "SelectMyRequests":
	case "SaveRequest":
	case "GetRequestItems":
	case "DeleteRequest":
	case "GetFormPersons":
	case "SaveFormPerson":
	case "RemoveFormPersons":
	case "SelectFormSteps":
	case "SelectAccessFromItems":
	case "ChangeFormAccess":
	case "ChangeTotalFormAccess":
		
	case "CheckConfirmationCode":
		
		$task();
}

function SelectForms() {
    $where = " AND IsActive='YES'";
	
    $whereParams = array();
    if (!empty($_REQUEST['FormID'])) {
        $where = " AND FormID = :FormID";
        $whereParams[':FormID'] = $_REQUEST['FormID'];
    }
    $temp = WFM_forms::ListForms($where, $whereParams);
    $res = PdoDataAccess::fetchAll($temp, $_GET['start'], $_GET['limit']);
	
	if(!empty($_REQUEST['FormID']) && isset($_REQUEST["EditContent"]))
	{
		$obj = new WFM_forms($_REQUEST['FormID']);
		$content = $obj->FormContent;
		$res[0]["content"] = PrepareContentToEdit($content);
	}
	print_r(ExceptionHandler::PopAllExceptions());
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function selectFormItems() {
	
	$where = " AND fi.IsActive='YES'";
	$params = array();
	
	$params[":StepRowID"] = !isset($_REQUEST["StepRowID"]) ? "-1" : $_REQUEST["StepRowID"];
	
	if(isset($_REQUEST["FormID"]))
	{
		$where .= " AND fi.FormID in(0,:t)";
		$params[":t"] = $_REQUEST["FormID"];
	}
	
	if(!empty($_REQUEST["NotGlobal"]))
		$where .= " AND fi.FormID >0";
	
	if(isset($_REQUEST["CreateMode"]))
	{
		$where .= " AND if(fi.ItemType='displayfield' AND fi.FieldName is not null,1=0,1=1)";
	}
	
    $temp = WFM_FormItems::Get($where . " order by if(fi.FormID=0,1,0),fg.ordering,fi.ordering", $params);
	if(!empty($_REQUEST["limit"]))
		$res = PdoDataAccess::fetchAll ($temp, $_GET["start"], $_GET["limit"]);
	else
		$res = $temp->fetchAll();
	
	if(!empty($_REQUEST["RequestMode"]))
	{
		if(!empty($_REQUEST["RequestID"]))
		{
			$ReqObj = new WFM_requests($_REQUEST["RequestID"]);
			$PersonID = $ReqObj->PersonID;
			$RequestID = $_REQUEST["RequestID"];
		}
		else
		{
			$PersonID = $_SESSION["USER"]["PersonID"];
			$RequestID = 0;
		}
		$PersonalInfo = WFM_requests::GlobalInfoRecord($PersonID, $RequestID);
		for($i=0; $i<count($res); $i++)
		{
			if($res[$i]["FieldName"] != "" && isset($PersonalInfo[ $res[$i]["DisplayField"] ]))
				$res[$i]["DisplayValue"] = $PersonalInfo[ $res[$i]["DisplayField"] ];
			else
				$res[$i]["DisplayValue"] = "";
		}
	}
	//echo PdoDataAccess::GetLatestQueryString();
	
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

//----------------------------------------------------------
function selectGridColumns() {
	
	$where = "";
	if(!empty($_REQUEST["FormItemID"]))
	{
		$where .= " AND FormItemID=?";
		$params = array($_REQUEST["FormItemID"]);
	}
	if(!empty($_REQUEST["FormID"]))
	{
		$where .= " AND FormID=?";
		$params = array($_REQUEST["FormID"]);
	}
	
    $temp = WFM_FormGridColumns::Get($where . " order by ordering", $params);
	print_r(ExceptionHandler::PopAllExceptions());
	$res = PdoDataAccess::fetchAll ($temp, $_GET["start"], $_GET["limit"]);
	
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function saveColumn(){
	
	$obj = new WFM_FormGridColumns();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if (!empty($obj->ColumnID)) 
		$result = $obj->Edit();
	else 
		$result = $obj->Add();
		
	if(!$result)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	echo Response::createObjectiveResponse(true, "");
	die();
}

function deleteColumn(){
	
	$obj = new WFM_FormGridColumns($_POST['ColumnID']);
	$result = $obj->Remove();	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function SelectGridRows(){
	
	$RequestID = $_REQUEST["RequestID"];
	$FormItemID = $_REQUEST["FormItemID"];
	
	$dt = WFM_RequestItems::Get(" AND RequestID=? AND FormItemID=?", array($RequestID, $FormItemID));
	$dt = $dt->fetchAll();
	for($i=0; $i < count($dt); $i++)
	{
		$p = xml_parser_create();
		xml_parse_into_struct($p, $dt[$i]["ItemValue"], $vals);
		xml_parser_free($p);
		
		foreach($vals as $element)
		{
			if(strpos($element["tag"],"COLUMN_") !== false)
				$dt[$i][strtolower($element["tag"]) ] = empty($element["value"]) ? "" : $element["value"];
		}		
		unset($dt[$i]["ItemValue"]);
	}	
		
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveGridRow(){
	
	$obj = new WFM_RequestItems();
	
	$st = stripslashes(stripslashes($_POST["record"]));
	$data = json_decode($st);

	$obj->RequestID = $data->RequestID;
	$obj->ReqItemID = $data->ReqItemID;
	$obj->FormItemID = $data->FormItemID;

	$xml = new SimpleXMLElement('<root/>');
	$elems = array_keys(get_object_vars($data));
	foreach($elems as $el)
	{
		if(strpos($el, "column_") === false)
			continue;
		$str = $data->$el;

		if(strlen($str) > 10 && substr($str,10) == "T00:00:00")
			$str = substr($str,0,10);	

		if(strlen($str) == 10 && $str[4] == "-" && $str[7] == "-")
			$str = preg_replace('/\-/', "/", $str);

		$xml->addChild($el, $str);
	}
	$obj->ItemValue = $xml->asXML();
	//print_r($obj);
	if((int)$obj->ReqItemID > 0)
		$result = $obj->Edit();
	else
		$result = $obj->Add();

	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteGridRow(){
	$RowID = $_POST["ReqItemID"];
	$obj = new WFM_RequestItems($RowID);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//----------------------------------------------------------

function GetEmptyFormID() {
	
	$dt = PdoDataAccess::runquery("select FormID from WFM_forms where FormContent is null");
	if(count($dt) > 0)
		return $dt[0]["FormID"];
	
	$obj = new WFM_forms();
	$obj->Add();
	
	return $obj->FormID;
}

function SaveForm() {
	
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
	
	$obj = new WFM_forms();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$CorrectContent = WFM_forms::CorrectFormContentItems($_POST['FormContent']);
	$obj->FormContent = $CorrectContent;
	
	$obj->IsStaff = $obj->IsStaff ? "YES" : "NO";
	$obj->IsCustomer = $obj->IsCustomer ? "YES" : "NO";
	$obj->IsShareholder = $obj->IsShareholder ? "YES" : "NO";
	$obj->IsSupporter = $obj->IsSupporter ? "YES" : "NO";
	$obj->IsExpert = $obj->IsExpert ? "YES" : "NO";
	$obj->IsAgent = $obj->IsAgent ? "YES" : "NO";
	$obj->SmsSend = $obj->SmsSend ? "YES" : "NO";
	$obj->SendOnce = $obj->SendOnce ? "YES" : "NO";
	
	if ($_POST['FormID'] > 0) {
		$obj->FormID = $_POST['FormID'];
		$result = $obj->Edit($pdo);
	} else {
		$result = $obj->Add($pdo);
	}
	
	if(!$result)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	$pdo->commit();
	echo Response::createObjectiveResponse(true, $obj->FormID);
	die();
}

function saveFormItem() {
	
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
    try {
        $obj = new WFM_FormItems();
        PdoDataAccess::FillObjectByJsonData($obj, $_POST['record']);
        if ($obj->FormItemID > 0) {
            $obj->Edit();
        } else {
			$dt = PdoDataAccess::runquery("select ifnull(max(ordering),0) from WFM_FormItems where FormID=? AND IsActive='YES'", 
				array($obj->FormID));
			$obj->ordering = $dt[0][0]*1 + 1;
            $obj->Add($pdo);
        }
        $pdo->commit();
        echo Response::createObjectiveResponse(true, '');
        die();
    } catch (Exception $e) {
        $pdo->rollBack();
        //print_r(ExceptionHandler::PopAllExceptions());
        //echo PdoDataAccess::GetLatestQueryString();
        echo Response::createObjectiveResponse(false, $e->getMessage());
        die();
    }
}

function GetFormContent() {
	
    $obj = new WFM_forms($_POST['FormID']);
    //echo Response::createObjectiveResponse(true, $obj->FormContent);
	echo $obj->FormContent;
    die();
}

function GetFormTitle() {
    $obj = new WFM_forms($_POST['FormID']);
    echo Response::createObjectiveResponse(true, $obj->FormTitle);
    die();
}

function deleteFormItem() {

	$obj = new WFM_FormItems($_POST['FormItemID']);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function deleteForm() {
    $pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
	
	$obj = new WFM_forms($_POST['FormID']);
	$result = $obj->Remove();
	
	if(!$result)
	{
		$pdo->rollBack();
		//print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
		echo Response::createObjectiveResponse(false, $e->getMessage());
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, '');
	die();
}

function PrepareContentToEdit($content){
	
	$dt = WFM_FormItems::Get("", array(":StepRowID" => -1));
	$ItemsArr = array();
	foreach($dt as $item)
		$ItemsArr[ $item["FormItemID"] ] = $item["ItemName"];
		
	$RevContent = '';
    $arr = explode(WFM_forms::TplItemSeperator, $content);
    for ($i = 0; $i < count($arr); $i++) {
        $FormItemID = $arr[$i];
        if (is_numeric($FormItemID)) {
			if(isset($ItemsArr[$FormItemID]))
				$RevContent .= WFM_forms::TplItemSeperator . $FormItemID . '--' . $ItemsArr[$FormItemID] . WFM_forms::TplItemSeperator;
        } else {
            $RevContent .= $FormItemID;
        }
    }
	return $RevContent;
}

function CopyForm(){
	
	$FormID = $_POST["FormID"];
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new WFM_forms($FormID);
	$obj->FormTitle .= " (کپی)";
	unset($obj->FormID);
	$obj->Add($pdo);
	
	PdoDataAccess::runquery("insert into WFM_FormItems(FormID,ItemName,ItemType,ordering)
		select :copy,ItemName,ItemType,ordering from WFM_FormItems where FormID=:src",
			array(":src" => $FormID, ":copy" => $obj->FormID), $pdo);
	
	PdoDataAccess::runquery("insert into WFM_FormAccess(FormItemID,StepRowID)
		select m2.FormItemID,a.StepRowID
		from WFM_FormItems m1
		join WFM_FormAccess a using(FormItemID)
		join WFM_FormItems m2 on(m2.FormID=:copy AND m1.ItemType=m2.ItemType AND m1.ItemName=m2.ItemName AND m1.ordering=m2.ordering)
		where m1.FormID=:src ",
			array(":src" => $FormID, ":copy" => $obj->FormID), $pdo);
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

//----------------------------------

function SelectGroups(){
	
	$dt = WFM_FormGroups::Get(" AND FormID=? order by ordering", array($_GET["FormID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SaveGroup(){
	
	$obj = new WFM_FormGroups();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->GroupID > 0)
		$result = $obj->Edit();
	else
	{
		$dt = PdoDataAccess::runquery("select ifnull(max(ordering),0) 
			from WFM_FormGroups where FormID=?", array($obj->FormID));
		$obj->ordering = $dt[0][0]*1 + 1;
		
		$result = $obj->Add();
	}
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteGroup(){
	
	$obj = new WFM_FormGroups($_POST["GroupID"]);
	$result =  $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function MoveGroup(){
	
	$FormID = $_POST["FormID"];
	$GroupID = $_POST["GroupID"];
	$ordering = $_POST["ordering"];
	$direction = $_POST["direction"];
	
	$direction = $direction == "-1" ? "-1" : "+1";
	
	PdoDataAccess::runquery("update WFM_FormGroups 
		set ordering=ordering $direction
		where FormID=? AND GroupID=?",
			array($FormID, $GroupID));
		
	PdoDataAccess::runquery("update WFM_FormGroups 
			set ordering=? 
			where FormID=? AND GroupID<>? AND ordering=? ",
			array($ordering, $FormID, $GroupID, $ordering*1 + $direction*1));
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function MoveItem(){
	
	$FormID = $_POST["FormID"];
	$FormItemID = $_POST["FormItemID"];
	$direction = $_POST["direction"] == "-1" ? -1 : 1;
	$revdirection = $direction == "-1" ? "+1" : "-1";
	
	$obj = new WFM_FormItems($FormItemID);
	
	PdoDataAccess::runquery("update WFM_FormItems 
			set ordering=ordering $revdirection 
			where FormID=? AND ordering=? AND IsActive='YES'",
			array($FormID, $obj->ordering*1 + $direction));
	
	PdoDataAccess::runquery("update WFM_FormItems 
		set ordering=? 
		where FormID=? AND FormItemID=? AND IsActive='YES'",
			array($obj->ordering*1 + $direction, $FormID, $FormItemID));
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}
//------------------------------------------------------------------------------

function SelectValidForms($returnMode = false){
	
	$dt = PdoDataAccess::runquery("
		select f.FormID,f.FormTitle , fp.*
		from WFM_forms f
		left join WFM_FormPersons fp on(fp.FormID=f.FormID)
		left join (select GroupID from FRW_AccessGroupList where PersonID=:pid)fg on(fg.GroupID=fp.GroupID)
		left join BSC_persons p on(
				p.PersonID=:pid AND (
					if(f.IsStaff='YES',f.IsStaff=p.IsStaff,1=0) OR
					if(f.IsCustomer='YES',f.IsCustomer=p.IsCustomer,1=0) OR
					if(f.IsShareholder='YES',f.IsShareholder=p.IsShareholder,1=0) OR
					if(f.IsAgent='YES',f.IsAgent=p.IsAgent,1=0) OR
					if(f.IsSupporter='YES',f.IsSupporter=p.IsSupporter,1=0) OR
					if(f.IsExpert='YES',f.IsExpert=p.IsExpert,1=0) ) 
			)
		where f.IsActive='YES' AND if(fp.FormID is null, p.PersonID>0, fp.PersonID=:pid or fg.GroupID>0)
		group by f.FormID
		", array(":pid" => $_SESSION["USER"]["PersonID"]));
	if($returnMode)
		return $dt;
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SelectMyRequests() {
	
	$where = " AND r.PersonID=:p";
	$params = array(":p" => $_SESSION["USER"]["PersonID"]);
	
	if(!empty($_REQUEST["RequestID"]))
	{
		$where .= " AND RequestID=:req";
		$params[":req"] = $_REQUEST["RequestID"];
	}
	
    $temp = WFM_requests::Get($where, $params, dataReader::makeOrder());
	$res = PdoDataAccess::fetchAll($temp, $_GET['start'], $_GET['limit']);
	
	for($i=0; $i < count($res);$i++)
	{
		$arr = WFM_FlowRows::GetFlowInfo($res[$i]["FlowID"], $res[$i]["RequestID"]);
		
		$res[$i]["IsStarted"] = $arr["IsStarted"] ? "YES" : "NO";
		$res[$i]["IsEnded"] = $arr["IsEnded"] ? "YES" : "NO";
		$res[$i]["JustStarted"] = $arr["JustStarted"] ? "YES" : "NO";
		$res[$i]["ActionType"] = $arr["ActionType"];
		$res[$i]["StepDesc"] = $arr["StepDesc"];
		$res[$i]["SendEnable"] = $arr["SendEnable"] ? "YES" : "NO";		
	}
	
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function SaveRequest() {
   
	$pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
	
	$ReqObj = new WFM_requests();
	PdoDataAccess::FillObjectByArray($ReqObj, $_POST);
	$formObj = new WFM_forms($ReqObj->FormID);
	
	$ReqObj->ReqContent = $formObj->FormContent;
	
	if ($_POST["RequestID"] == "")
	{
		$ReqObj->PersonID = $_SESSION['USER']["PersonID"];
		$ReqObj->RegDate = PDONOW;
		$result = $ReqObj->Add($pdo);
	} 
	else
	{
		$result = $ReqObj->Edit($pdo);
		/* removing values of contract items */
		//WFM_RequestItems::RemoveAll($ReqObj->RequestID, $pdo);
	}

	if(!$result)
	{
		$pdo->rollBack();
        print_r(ExceptionHandler::PopAllExceptions());
        //echo PdoDataAccess::GetLatestQueryString();
        echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
        die();
	}	
	
	/* Adding the values of Request items */
	foreach ($_POST as $PostData => $val) 
	{
		if(empty($val))
			continue;
		
		if (!(substr($PostData, 0, 8) == "ReqItem_"))
			continue;
		
		$items = explode('_', $PostData);
		$FormItemID = $items[1];
		
		$ReqItemsObj = new WFM_RequestItems();
		$ReqItemsObj->RequestID = $ReqObj->RequestID;
		$ReqItemsObj->FormItemID = $FormItemID;
		
		$FormItemObj = new WFM_FormItems($ReqItemsObj->FormItemID);
		switch ($FormItemObj->ItemType) {
			case 'shdatefield':
				$ReqItemsObj->ItemValue = DateModules::shamsi_to_miladi($val);
				break;
			case "checkbox":
				if(count($items) > 2 && $items[2] == "checkbox")
				{
					$val = $items[3];
				}
			default :
				$ReqItemsObj->ItemValue = $val;
		}
		$result = $ReqItemsObj->ReplaceRecord($pdo);
	}
	if(!$result)
	{
		$pdo->rollBack();
        echo Response::createObjectiveResponse(false, "خطا در ذخیره اطلاعات");
        die();
	}
	$pdo->commit();
	
	//------------------ sending form --------------------
	
	if(isset($_REQUEST["sending"]) && $_REQUEST["sending"] == "true")
	{
		if($formObj->SmsSend == "YES")
		{
			$mobile = SendConfirmationCode($ReqObj->RequestID);
			if(!$mobile)
			{
				echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
				die();
			}
			echo Response::createObjectiveResponse(true, $ReqObj->RequestID . "-" . $mobile);
			die();
		}
		else
		{
			$dt = WFM_FlowRows::GetFlowInfo($formObj->FlowID, $ReqObj->RequestID);
			if(!$dt["IsStarted"])
				$result = WFM_FlowRows::StartFlow($formObj->FlowID, $ReqObj->RequestID);
			else
				$result = WFM_requests::ConfirmRequest ($ReqObj->RequestID);
			
			if(!$result)
			{
				echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
				die();
			}
		}
	}
	
	echo Response::createObjectiveResponse(true, $ReqObj->RequestID);
	die();
}

function SendConfirmationCode($RequestID){
	
	$ReqObj = new WFM_requests($RequestID);
	require_once '../../framework/person/persons.class.php';
	$PersonObj = new BSC_persons($ReqObj->PersonID);
	
	if($PersonObj->mobile == "")
	{
		ExceptionHandler::PushException("با توجه به اینکه ارسال این فرم فقط از طریق تایید کد امنیتی از طریق پیامک می باشد و شماره پیامک شما در سیستم ثبت نشده است،" . 
			" لطفا جهت ثبت شماره پیامک خود با صندوق تماس حاصل فرمایید.");
		return false;
	}
	if(isset($_SESSION["ConfirmCode"]))
	{
		if(time() - $_SESSION["ConfirmCode"]["time"]*1 < 600)
		{
			return $PersonObj->mobile;
		}
		unset($_SESSION["ConfirmCode"]);
	}
	
	$ConfirmCode = rand(111111, 999999);
	$_SESSION["ConfirmCode"] = array(
		"time" => time(),
		"code" => $ConfirmCode,
		"tries" => 0
	);
	require_once 'sms.php';
	$result = ariana2_sendSMS($PersonObj->mobile, $ConfirmCode, "number", $SendError);
	//$result = true;
	if(!$result)
	{
		ExceptionHandler::PushException ("خطا در ارسال پیامک");
		unset($_SESSION["ConfirmCode"]);
		return false;
	}
	
	return $PersonObj->mobile;
}

function CheckConfirmationCode(){
	
	$RequestID = (int)$_REQUEST["RequestID"];
	$code = (int)$_REQUEST["code"];
	
	if(empty($_SESSION["ConfirmCode"]))
	{
		echo Response::createObjectiveResponse(false, "ExpireCode");
		die();
	}
	if(time() - $_SESSION["ConfirmCode"]["time"]*1 > 600)
	{
		unset($_SESSION["ConfirmCode"]);
		echo Response::createObjectiveResponse(false, "ExpireCode");
		die();
	}
	if($_SESSION["ConfirmCode"]["code"] != $code)
	{
		if($_SESSION["ConfirmCode"]["tries"] == 3)
		{
			unset($_SESSION["ConfirmCode"]);
			echo Response::createObjectiveResponse(false, "MaxTry");
			die();
		}
		$_SESSION["ConfirmCode"]["tries"]++;
		echo Response::createObjectiveResponse(false, "WrongeCode");
		die();
	}
	else
	{
		$ReqObj = new WFM_requests($RequestID);
		$result = WFM_FlowRows::StartFlow($ReqObj->_FlowID, $ReqObj->RequestID);
		if(!$result)
		{
			echo Response::createObjectiveResponse(false, "خطا در ارسال فرم");
			die();
		}
		unset($_SESSION["ConfirmCode"]);
		echo Response::createObjectiveResponse(true, "");
		die();
	}	
}

function GetRequestItems() {
	
    $res = WFM_RequestItems::Get(" AND RequestID=?", array($_REQUEST['RequestID']));
	$res = $res->fetchAll();
    echo dataReader::getJsonData($res,count($res), $_GET["callback"]);
    die();
}

function DeleteRequest(){
	
	$pdo = PdoDataAccess::getPdoObject();
    $pdo->beginTransaction();
	
	$obj = new WFM_requests($_POST['RequestID']);
	
	$result = WFM_RequestItems::RemoveAll($obj->RequestID, $pdo);
	$result = $obj->Remove();
	if(!$result)
	{
		$pdo->rollBack();
		//print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	$pdo->commit();
	echo Response::createObjectiveResponse(true, '');
	die();
	
}

//-------------------------------------

function GetFormPersons(){
	
	$dt = WFM_FormPersons::Get(" AND FormID=?", array($_REQUEST["FormID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount());
	die();
}

function SaveFormPerson(){
	
	$obj = new WFM_FormPersons();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(substr($obj->PersonID,0,1) == "g")
	{
		$obj->GroupID = substr($obj->PersonID,2);
		$obj->PersonID = 0;
	}
	else
	{
		$obj->GroupID = 0;
		$obj->PersonID = substr($obj->PersonID,2);
	}
	
	$result = $obj->Add();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function RemoveFormPersons(){
	
	$obj = new WFM_FormPersons($_REQUEST["RowID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//---------------------------------------

function SelectFormSteps(){
	
	$FormID = $_REQUEST["FormID"];
	
	$dt = PdoDataAccess::runquery("SELECT s.* FROM WFM_FlowSteps s join WFM_forms using(FlowID)
		where FormID=?", array($FormID));
	
	$dt = array_merge(array(array(
		"StepRowID" => 0,
		"StepDesc" => "درخواست دهنده"
	)), $dt);
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SelectAccessFromItems(){
	
	$FormID = $_REQUEST["FormID"];
	$StepRowID = $_REQUEST["StepRowID"];
	
	$dt = PdoDataAccess::runquery("
		SELECT fi.FormItemID,fi.GroupID,ItemName, if(StepRowID is null,'NO','YES') access,AccessID,fg.GroupDesc
		FROM WFM_FormItems fi 
			left join WFM_FormGroups fg using(GroupID) 
			left join WFM_FormAccess fa on(fi.FormItemID=fa.FormItemID AND fa.StepRowiD=?)
		where fi.FormID=? order by fg.ordering,fi.ordering", array($StepRowID, $FormID));
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function ChangeFormAccess(){
	
	$access = $_POST["access"];
	if($access == "true")
	{
		$obj = new WFM_FormAccess();
		$obj->FormItemID = $_POST["FormItemID"];
		$obj->StepRowID = $_POST["StepRowID"];
		$result = $obj->Add();
	}
	else
	{
		$obj = new WFM_FormAccess($_POST["AccessID"]);
		$result = $obj->Remove();
	}
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ChangeTotalFormAccess(){
	
	$access = $_POST["access"];
	$StepRowID = $_POST["StepRowID"];
	$FormID = $_POST["FormID"];
	
	$dt = PdoDataAccess::runquery("delete a from WFM_FormAccess a join WFM_FormItems using(FormItemID) "
				. " where FormID=? AND StepRowID=?" , array($FormID, $StepRowID));
	
	if($access == "true")
		$dt = PdoDataAccess::runquery("insert into WFM_FormAccess(FormItemID,StepRowID) 
				SELECT FormItemID,? FROM WFM_FormItems s join WFM_forms using(FormID)
				where FormID=?" , array($StepRowID, $FormID));

	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}
?>
