<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.7
//-------------------------
require_once('../header.inc.php');
require_once 'persons.class.php';
require_once '../PasswordHash.php';
require_once 'email.php';
			
require_once inc_dataReader;
require_once inc_response;

if(isset($_REQUEST["task"]))
{
	switch ($_REQUEST["task"])
	{
		case "selectPersons":
		case "selectPersonsAndGroups":
		case "selectSignerPersons":
		case "selectPendingPersons":
		case "SavePerson":
		case "DeletePerson":
		case "ResetPass":
		case "selectPosts":
		case "changePass":
		case "ConfirmPendingPerson":
		case "selectCompanyTypes":
		case "ResetAttempt":
		case "confirmAgreement":
			
		case "SelectSigners":
		case "SaveSigner":
		case "DeleteSigner":
			
		case "SelectLicenses":
		case "SaveLicense":
		case "DeleteLicense":
		case "ConfirmLicense":
			
		case "selectCities":
		case "selectPresenters":
		case "selectSubAgents":
		case "selectPersonInfoTypes":
		case "ConfirmPerson":
		case "SendRegisterProcess":
			
			call_user_func($_REQUEST["task"]);
	}
}

function selectPersons(){
	
	$where = "1=1";
	$param = array();
	
	if(!empty($_REQUEST["UserType"]))
	{
		switch($_REQUEST["UserType"])
		{
			case "IsAgent":		$where .= " AND IsAgent='YES'";break;
			case "IsCustomer":	$where .= " AND IsCustomer='YES'";break;
			case "IsStaff":		$where .= " AND IsStaff='YES'";break;
			case "IsSupporter":	$where .= " AND IsSupporter='YES'";break;
			case "IsExpert":	$where .= " AND IsExpert='YES'";break;
		}
	}
	
	if(!empty($_REQUEST["UserTypes"]))
	{
		$arr = preg_split("/,/", $_REQUEST["UserTypes"]);
		$where .= " AND ( 1=0 ";
		foreach($arr as $r)
			$where .= " OR $r='YES'";
		$where .= ")";
	}	
	
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) 
	{
        $field = $_REQUEST['fields'];
		$field = $_REQUEST['fields'] == "fullname" ? "concat_ws(' ',fname,lname,CompanyName)" : $field;
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
	
	if(!empty($_REQUEST["IsConfirm"]))
	{
		$where .= " AND IsConfirm = :e "; 
		$param[":e"] = $_REQUEST["IsConfirm"];
	}
	if(empty($_REQUEST["IncludeInactive"]))
	{
		if(!empty($_REQUEST["IsActive"]))
		{
			$where .= " AND p.IsActive = :i "; 
			$param[":i"] = $_REQUEST["IsActive"];
		}
		else
			$where .= " AND p.IsActive in ('YES','PENDING')";
	}
	if(!empty($_REQUEST["full"]))
		$temp = BSC_persons::SelectAll($where . dataReader::makeOrder(), $param);
	else
		$temp = BSC_persons::MinSelect($where . dataReader::makeOrder(), $param);
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	
	if(!empty($_REQUEST["EmptyRow"]) && empty($_REQUEST["PersonID"]))
	{
		$temp = array_merge(array(array("PersonID" => 0, "fullname" => "منابع داخلی")), $temp);
	}
	
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function selectPersonsAndGroups(){
	
	$param = array();
	$where = "";
	if (isset($_REQUEST['query'])) 
	{
		$field = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : "name";
        $where = ' and ' . $field . ' like :fld';
		$param[':fld'] = '%' . $_REQUEST['query'] . '%';
    }
	
	$query = "
		select * from (
			select 'Person' type, concat('p_',PersonID)  id, concat_ws(' ',fname,lname,CompanyName) name
			from BSC_persons where IsStaff='YES' AND IsActive='YES'				
			
			union All 
			
				select 'Group' type, concat('g_',GroupID) id, GroupDesc name
				from FRW_AccessGroups
			)t
		where 1=1".$where."
		order by type,name";
	
	$dt = PdoDataAccess::runquery($query, $param);
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectSignerPersons(){
	
	$where = " IsSigner='YES' AND IsStaff='YES' AND IsActive='YES'";
	$temp = BSC_persons::MinSelect($where);
	print_r(ExceptionHandler::PopAllExceptions());
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function selectPendingPersons(){
	
	$temp = BSC_persons::MinSelect(" p.IsActive='PENDING'");
	$no = $temp->rowCount();
	echo dataReader::getJsonData($temp->fetchAll(), $no, $_GET["callback"]);
	die();
}

function SavePerson(){
	
	$obj = new BSC_persons();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	unset($obj->PersonPic);
	
	if(session::IsPortal())
		$obj->PersonID = $_SESSION["USER"]["PersonID"];
	
	if(empty($obj->PersonID))
	{
		$hash_cost_log2 = 8;	
		$hasher = new PasswordHash($hash_cost_log2, true);
		$obj->UserPass = $hasher->HashPassword(md5("123456"));
	}
	
	if(isset($_REQUEST["adminMode"]) && $_REQUEST["adminMode"] == true)
	{
		$obj->IsAgent = !isset($_POST["IsAgent"]) ? "NO" : "YES";
		$obj->IsCustomer = !isset($_POST["IsCustomer"]) ? "NO" : "YES";
		$obj->IsStaff = !isset($_POST["IsStaff"]) ? "NO" : "YES";
		$obj->IsShareholder = !isset($_POST["IsShareholder"]) ? "NO" : "YES";
		$obj->IsSupporter = !isset($_POST["IsSupporter"]) ? "NO" : "YES";
		$obj->IsExpert = !isset($_POST["IsExpert"]) ? "NO" : "YES";
		
		$obj->IsSigner = !isset($_POST["IsSigner"]) ? "NO" : "YES";
	}
	if($obj->PersonID > 0)
		$result = $obj->EditPerson();
	else 
		$result = $obj->AddPerson();
	
	//-----------  save Person pic ----------------
	if(!empty($_FILES['PersonPic']['tmp_name']))
	{
		if($_FILES['PersonPic']['size'] > 200000)
		{
			echo Response::createObjectiveResponse(false, "حداکثر حجم مجاز فایل 200 کیلوبایت می باشد");
			die();
		}
		$st = preg_split("/\./", $_FILES['PersonPic']['name']);
		$extension = strtolower($st [count($st) - 1]);
		if(array_search($extension, array("gif","jpg","jpeg","png")) === false)
		{
			echo Response::createObjectiveResponse(false, "فقط موارد زیر برای نوع فایل مجاز می باشد: <br>" . 
				"gif , jpg , jpeg , png");
			die();
		} 
		if(!empty($_FILES['PersonPic']['tmp_name']))
		{
			PdoDataAccess::runquery_photo("update BSC_persons set PersonPic=:pdata where PersonID=:p", 
					array(":pdata" => fread(fopen($_FILES['PersonPic']['tmp_name'], 'r' ),$_FILES['PersonPic']['size'])), 
					array(":p" => $obj->PersonID));
		}
	}
	//-----------  save Person sign ----------------
	if(!empty($_FILES['PersonSign']['tmp_name']))
	{
		if($_FILES['PersonSign']['size'] > 200000)
		{
			echo Response::createObjectiveResponse(false, "حداکثر حجم مجاز فایل 200 کیلوبایت می باشد");
			die();
		}
		$st = preg_split("/\./", $_FILES['PersonSign']['name']);
		$extension = strtolower($st [count($st) - 1]);
		if(array_search($extension, array("gif","jpg","jpeg","png")) === false)
		{
			echo Response::createObjectiveResponse(false, "فقط موارد زیر برای نوع فایل مجاز می باشد: <br>" . 
				"gif , jpg , jpeg , png");
			die();
		}

		PdoDataAccess::runquery_photo("update BSC_persons set PersonSign=:pdata where PersonID=:p", 
				array(":pdata" => fread(fopen($_FILES['PersonSign']['tmp_name'], 'r' ),$_FILES['PersonSign']['size'])), 
				array(":p" => $obj->PersonID));
	}
	//---------------------------------------------
	if(!isset($_REQUEST["adminMode"]))
	{
		BSC_PersonInfo::RemoveAll($obj->PersonID);
		
		foreach($_POST as $key => $value)
		{
			if(substr($key,0,5) == "info_")
			{
				$arr = preg_split('/_/', $key);
				$infoObj = new BSC_PersonInfo();
				$infoObj->PersonID = $obj->PersonID;
				$infoObj->TypeID = (int)$arr[1];
				$infoObj->InfoID = (int)$arr[2];
				$infoObj->Add();
			}
		}
	}
	//---------------------------------------------
	//print_r(ExceptionHandler::PopAllExceptions()); 
	echo Response::createObjectiveResponse($result, !$result ? ExceptionHandler::GetExceptionsToString() : "");
	die();
}

function DeletePerson(){
	
	$result = BSC_persons::DeletePerson($_POST["PersonID"]);
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ResetPass(){
	
	$result = BSC_persons::ResetPass($_POST["PersonID"]);
	echo Response::createObjectiveResponse(true, $result);
	die();
}

function selectPosts(){
	
	$temp = PdoDataAccess::runquery("select * from BSC_posts order by PostName");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function changePass(){
	
	$dt = PdoDataAccess::runquery("select * from BSC_persons where PersonID=:p",
			array(":p" => $_SESSION['USER']["PersonID"]));
	if(count($dt) == 0)
	{ 		
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	$hash_cost_log2 = 8;	
	$hasher = new PasswordHash($hash_cost_log2, true);
	if (!$hasher->CheckPassword($_POST["cur_pass"], $dt[0]["UserPass"])) {

		echo Response::createObjectiveResponse(false, "CurPassError");
		die();
	}

	PdoDataAccess::RUNQUERY("update BSC_persons set UserPass=? where PersonID=?",
		array($hasher->HashPassword($_POST["new_pass"]), $_SESSION["USER"]["PersonID"]));
								
	if( ExceptionHandler::GetExceptionCount() != 0 )
	{				
		echo Response::createObjectiveResponse(false, "CurPassError");
		die();
	}	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function ConfirmPendingPerson(){
	$error = "";
	$PersonID = $_POST["PersonID"];
	$mode = $_POST["mode"];
	
	$obj = new BSC_persons($PersonID);
	$obj->IsActive = $mode == "1" ? "YES" : "NO";
	$result = $obj->EditPerson();
	
	if($mode == "1")
	{
		$result = SendEmail($obj->email, "تایید ثبت نام در صندوق پژوهش و فناوری خراسان رضوی", 
				OWNER_WELCOME_MESSAGE,array(), $error);
		if($error != "")
			$error = "ارسال ایمیل به دلیل زیر انجام نشد: <br>" . $error;
	}
	
	//print_r(ExceptionHandler::PopAllExceptions());
	ob_clean();
	echo Response::createObjectiveResponse($result, $error);
	die();
}

function selectCompanyTypes(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where typeID=14 AND IsActive='YES' order by InfoDesc");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function ResetAttempt(){
	
	PdoDataAccess::runquery("delete from FRW_LoginAttempts where PersonID=?",
		array($_POST["PersonID"]));
	echo Response::createObjectiveResponse(true, "");
	die();
}

function confirmAgreement(){
	
	$obj = new BSC_persons($_SESSION["USER"]["PersonID"]);
	$obj->Agreement = "YES";
	$result = $obj->EditPerson();
	echo Response::createObjectiveResponse($result, "");
	die();
}
//............................................................

function SelectSigners(){
	
	$where = "PersonID=?";
	if(session::IsPortal())
		$param = array($_SESSION["USER"]["PersonID"]);
	else
		$param = array($_REQUEST["PersonID"]);
	
	$temp = BSC_OrgSigners::GetAll($where, $param);
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveSigner(){
	
	$obj = new BSC_OrgSigners();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(session::IsPortal())
		$obj->PersonID = $_SESSION["USER"]["PersonID"];
	
	if($obj->RowID > 0)
		$result = $obj->EditSigner();
	else 
		$result = $obj->AddSigner();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteSigner(){
	
	$result = BSC_OrgSigners::DeleteSigner($_POST["RowID"]);
	echo Response::createObjectiveResponse($result, "");
	die();
}
//............................................................

function SelectLicenses(){
	$where = "PersonID=?";
	if(session::IsPortal())
		$param = array($_SESSION["USER"]["PersonID"]);
	else
		$param = array($_REQUEST["PersonID"]);
	
	$temp = BSC_licenses::GetAll($where, $param);
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SaveLicense(){
	
	$obj = new BSC_licenses();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(session::IsPortal())
		$obj->PersonID = $_SESSION["USER"]["PersonID"];
	
	$obj->IsConfirm = "NOTSET";
		
	if($obj->LicenseID > 0)
		$result = $obj->EditLicense();
	else 
		$result = $obj->AddLicense();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteLicense(){
	
	$result = BSC_licenses::DeleteLicense($_POST["LicenseID"]);
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ConfirmLicense(){
	
	$obj = new BSC_licenses();
	
	$obj->LicenseID = $_POST["LicenseID"];
	$obj->IsConfirm = $_POST["mode"];
	$obj->ConfirmPersonID = $_SESSION["USER"]["PersonID"];
	$obj->RejectDesc = $_POST["RejectDesc"];
	
	$result = $obj->EditLicense();
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

//............................................................

function selectCities(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where typeID=15 AND IsActive='YES' order by InfoDesc");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function selectPresenters(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where typeID=90 AND IsActive='YES' order by InfoDesc");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function selectSubAgents(){
	
	$PersonID = isset($_REQUEST["PersonID"]) ? $_REQUEST["PersonID"] : $_SESSION["USER"]["PersonID"];
	$temp = PdoDataAccess::runquery("select * from BSC_SubAgents where PersonID=?", array($PersonID));
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function selectPersonInfoTypes(){
	
	$TypeID = (int)$_REQUEST["TypeID"];
	$PersonID = isset($_REQUEST["PersonID"]) ? $_REQUEST["PersonID"] : $_SESSION["USER"]["PersonID"];
	$temp = PdoDataAccess::runquery("select b.*,if(p.RowID is null,'false','true') checked
			from BaseInfo b left 
			join BSC_PersonInfo p on(p.PersonID=:p AND b.TypeID=p.TypeID AND b.InfoID=p.InfoID)
			 where b.TypeID=:t order by InfoID", array(":t" => $TypeID, ":p" => $PersonID));
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function ConfirmPerson(){
	
	$PersonID = $_POST["PersonID"];
	
	PdoDataAccess::runquery("update BSC_persons set IsConfirm='YES' where PersonID=?", array($PersonID));
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SendRegisterProcess(){
	
	require_once '../baseInfo/baseInfo.class.php';
	require_once "../../office/workflow/wfm.class.php";

	$pObj = new BSC_processes(PROCESS_REGISTRATION);
	$result = WFM_FlowRows::StartFlow($pObj->FlowID, $_SESSION["USER"]["PersonID"]);
	echo Response::createObjectiveResponse($result, "");
	die();
}
?>
