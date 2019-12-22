<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.10
//---------------------------
require_once '../header.inc.php';
ini_set("display_errors", "On");
require_once(inc_response);
require_once inc_dataReader;
require_once 'letter.class.php';
require_once getenv("DOCUMENT_ROOT") . '/office/dms/dms.class.php';
require_once getenv("DOCUMENT_ROOT") . '/framework/person/persons.class.php';
			
$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
if(!empty($task))
	$task();    

function SelectLetter() {

    $where = "1=1";
    $param = array();
	
	if(isset($_REQUEST["LetterID"]))
	{
		$where .= " AND LetterID=:lid";
		$param[":lid"] = $_REQUEST["LetterID"];
	}
	
	if(!empty($_REQUEST["query"]))
	{
		$where .= " AND ( LetterID= :q or LetterTitle like :q2 )";
		$param[":q"] = $_REQUEST["query"];
		$param[":q2"] = "%" .  $_REQUEST["query"] . "%";
	}

    $list = OFC_letters::GetAll($where, $param);
    echo dataReader::getJsonData($list, count($list), $_GET['callback']);
    die();
}

function SelectAllLetter(){
	
	$where = "1=1";
    $param = array();
	$RefInclude = false;
	
	$index = 0;
	foreach($_POST as $field => $value)
	{
		if($field == "excel" || empty($value) || strpos($field, "inputEl") !== false)
			continue;
		
		$value = str_replace(" ", "", $value);
		
		$prefix = "";
		switch($field)
		{
			case "PersonID": $prefix = "l."; break;
			case "Customer": $prefix = "lc."; $field = "PersonID"; break;
			
			case "LetterID": 
				$prefix = "l."; break;
			
			case "LetterTitle": 
				 $field = "replace(l.LetterTitle,' ','')"; break;
			case "context": 
				 $field = "replace(l.context,' ','')"; break;
			
			case "FromSendDate":
			case "FromLetterDate":
			case "ToSendDate":
			case "ToLetterDate":
			case "FromInnerLetterDate":
			case "ToInnerLetterDate":	
				$value = DateModules::shamsi_to_miladi($value, "-");
				break;
		}
		if($field == "FromPersonID" || $field == "ToPersonID")
		{
			$where .= " AND s." . $field . " = :f" . $index;
			$param[":f" . $index] = $value;
		}
		else if(strpos($field, "From") === 0)
		{
			$where .= " AND " . $prefix . substr($field,4) . " >= :f" . $index;
			$param[":f" . $index] = $value;
		}
		else if(strpos($field, "To") === 0)
		{
			$where .= " AND " . $prefix . substr($field,2) . " <= :f" . $index;
			$param[":f" . $index] = $value;
		}
		else
		{
			$where .= " AND " . $prefix . $field . " like :f" . $index;
			$param[":f" . $index] = "%" . $value . "%";
		}
		
		if(array_search($field, array("FromSendDate", "ToSendDate", "FromPersonID", "ToPersonID", 
				"SendType", "IsUrgent", "SendComment")) !== false)
			$RefInclude = true;
		
		$index++;
	}
	
    $list = OFC_letters::FullSelect($where, $param, dataReader::makeOrder(), $RefInclude);
	
	print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();
	
	$no = $list->rowCount();
	$list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    echo dataReader::getJsonData($list, $no, $_GET['callback']);
    die();
}

function SelectDraftLetters() {

    $list = OFC_letters::SelectDraftLetters();
    echo dataReader::getJsonData($list, count($list), $_GET['callback']);
    die();
}

function CustomerLetters($returnMode = false){
	
	$list = PdoDataAccess::runquery("
		select * from OFC_letters l join OFC_LetterCustomers c using(LetterID)
		where c.IsHide='NO' AND l.AccessType=".OFC_ACCESSTYPE_NORMAL." 
			AND c.PersonID=" . $_SESSION["USER"]["PersonID"]);

	if($returnMode)
		return $list;
    echo dataReader::getJsonData($list, count($list), $_GET['callback']);
    die();
}

function ReceivedSummary(){
	
	$temp = PdoDataAccess::runquery("
		select s.SendType,InfoDesc SendTypeDesc, sum(if(s.IsDeleted='NO',1,0)) totalCnt, sum(if(s.IsSeen='NO' AND s.IsDeleted='NO',1,0)) newCnt
        from OFC_send s
            left join BaseInfo b on(TypeID=12 AND SendType=InfoID)
            left join OFC_send s2 on(s2.LetterID=s.LetterID AND s2.SendID>s.SendID AND s2.FromPersonID=s.ToPersonID)
        where s2.SendID is null AND s.IsDeleted='NO' AND s.ToPersonID=" . $_SESSION["USER"]["PersonID"] . "
        group by b.InfoID
        
        union all
        
        select 0 SendType,'کلیه نامه ها' SendTypeDesc, sum(if(s.IsDeleted='NO',1,0)) totalCnt, sum(if(s.IsSeen='NO' AND s.IsDeleted='NO',1,0)) newCnt
        from OFC_send s
            left join OFC_send s2 on(s2.LetterID=s.LetterID AND s2.SendID>s.SendID AND s2.FromPersonID=s.ToPersonID)
        where s2.SendID is null  AND s.ToPersonID=" . $_SESSION["USER"]["PersonID"]);		
	
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function SelectReceivedLetters(){
	
	$where = " AND s.IsDeleted='NO'";
	$param = array();
	
	if(isset($_REQUEST["deleted"]) && $_REQUEST["deleted"] == "true")
		$where = " AND s.IsDeleted='YES'";
	
	if(!empty($_REQUEST["SendType"]))
	{
		$where .= " AND s.SendType=:st";
		$param[":st"] = $_REQUEST["SendType"];
	}
	
	if (isset($_GET["fields"]) && !empty($_GET["query"])) {
		
		$field = $_GET["fields"];
		$field = $field == "FromPersonName" ? "concat_ws(' ',fname, lname,CompanyName)" : $field;
		$field = $field == "LetterID" ? "l.LetterID" : $field;
		
		$where .= " AND ".$field." like :f";
		$param[":f"] = "%" . $_GET["query"] . "%";
	}
	
	$dt = OFC_letters::SelectReceivedLetters($where, $param);
	//echo PdoDataAccess::GetLatestQueryString();
	//print_r(ExceptionHandler::PopAllExceptions());
	$cnt = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt, $cnt, $_GET["callback"]);
	die();
}

function SelectSendedLetters(){
	
	$where = " AND s.SenderDelete='NO'";
	$param = array();
	
	if(isset($_REQUEST["deleted"]) && $_REQUEST["deleted"] == "true")
		$where = " AND s.SenderDelete='YES'";
	
	if (isset($_GET["fields"]) && !empty($_GET["query"])) {
		
		$field = $_GET["fields"];
		$field = $field == "ToPersonName" ? "concat_ws(' ',fname, lname,CompanyName)" : $field;
		
		$where .= " AND ".$field." like :f";
		$param[":f"] = "%" . $_GET["query"] . "%";
	}
	
	$dt = OFC_letters::SelectSendedLetters($where, $param);
	print_r(ExceptionHandler::PopAllExceptions());
	$cnt = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt, $cnt, $_GET["callback"]);
	die();
}

function SelectTodayResponseLetters(){
	
	$query = "select s.*,l.*, 
			concat_ws(' ',fname, lname,CompanyName) FromPersonName			

		from OFC_send s
			join OFC_letters l using(LetterID)
			join BSC_persons p on(s.FromPersonID=p.PersonID)		
		where s.ToPersonID=? AND ResponseTimeout=" . PDONOW . "
		group by SendID";
	$param = array( $_SESSION["USER"]["PersonID"]);

	$query .= dataReader::makeOrder();
	$dt = PdoDataAccess::runquery_fetchMode($query, $param);
	
	$cnt = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt, $cnt, $_GET["callback"]);
	die();
}

function SelectTodayFollowUpLetters(){
	
	$query = "select s.*,l.*, 
			concat_ws(' ',fname, lname,CompanyName) ToPersonName			

		from OFC_send s
			join OFC_letters l using(LetterID)
			join BSC_persons p on(s.ToPersonID=p.PersonID)		
		where s.FromPersonID=? AND FollowUpDate=" . PDONOW . "
		group by SendID";
	$param = array( $_SESSION["USER"]["PersonID"]);

	$query .= dataReader::makeOrder();
	$dt = PdoDataAccess::runquery_fetchMode($query, $param);
	$cnt = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt, $cnt, $_GET["callback"]);
	die();
}

function SelectArchiveLetters(){
	
	$FolderID = isset($_REQUEST["FolderID"]) ? $_REQUEST["FolderID"] : "";
	if(empty($FolderID))
	{
		echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
		die();
	}
	$query = "select l.*,a.FolderID,if(count(DocumentID) > 0,'YES','NO') hasAttach

			from OFC_ArchiveItems a
				join OFC_letters l using(LetterID)
				left join DMS_documents on(ObjectType='letterAttach' AND ObjectID=l.LetterID)				
			where FolderID=:fid";
	
	$param = array(":fid" => $FolderID);
	
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
        $query .= ' and ' . $field . ' like :f';
        $param[':f'] = '%' . $_REQUEST['query'] . '%';
    }
	
	$query .= " group by LetterID";
	$dt = PdoDataAccess::runquery($query, $param);
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectOuterSendType(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=76 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectAccessType(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=77 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectOrganizations(){
	
	$params = array();
	$query = "select * from OFC_organizations ";
	if(!empty($_GET["query"]))
	{
		$query .= " where OrgTitle like :q";
		$params[":q"] = "%" . $_GET["query"] . "%";
	}
	$dt = PdoDataAccess::runquery_fetchMode($query . dataReader::makeOrder(), $params);
	$temp = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($temp, $dt->rowCount(), $_GET["callback"]);
	die();
}

function selectOrgPosts(){
	
	$query = "select * from OFC_organizations where OrgTitle=?";
	$dt = PdoDataAccess::runquery($query, array($_REQUEST["OrgTitle"]));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveOrganization(){
	
	$obj = new OFC_organizations();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(!isset($obj->OrgID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteOrganization(){
	
	$obj = new OFC_organizations($_POST["OrgID"]);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function SaveLetter($dieing = true) {

    $Letter = new OFC_letters();
    pdoDataAccess::FillObjectByArray($Letter, $_POST);
	/*$Letter->context = InputValidation::filteyByHTMLPurifier($Letter->context);*/
	
	//------------ add organiation ----------------
	if(!empty($Letter->organization))
	{	
		$dt = PdoDataAccess::runquery("select * from OFC_organizations where OrgTitle=? AND OrgPost=?", 
			array($Letter->organization, $Letter->OrgPost));
		if(count($dt) == 0)
		{
			$obj = new OFC_organizations();
			$obj->OrgTitle = $Letter->organization;
			$obj->OrgPost = $Letter->OrgPost;
			$obj->Add();
		}
	}
	//---------------------------------------------
	
	if($Letter->RefLetterID != "")
	{
		$obj = new OFC_letters($Letter->RefLetterID);
		if(empty($obj->LetterID))
		{
			Response::createObjectiveResponse(false, "شماره نامه عطف قابل بازیابی نمی باشد");
			die();
		}
	}
    if ($Letter->LetterID == '') {
		$Letter->PersonID = $_SESSION["USER"]["PersonID"];
		$Letter->LetterDate = PDONOW;
		$Letter->RegDate = PDONOW;
        $res = $Letter->AddLetter();
    }
    else
	{
        $res = $Letter->EditLetter();
	}
	
	if(!empty($_FILES["PageFile"]["tmp_name"]))
	{
		$st = preg_split("/\./", $_FILES ['PageFile']['name']);
		$extension = strtolower($st [count($st) - 1]);
		if (in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf")) === false) 
		{
			Response::createObjectiveResponse(false, "فرمت فایل ارسالی نامعتبر است");
			die();
		}
		
		$dt = DMS_documents::SelectAll("ObjectType='letter' AND ObjectID=?", array($Letter->LetterID));
		if(count($dt) == 0)
		{
			$obj = new DMS_documents();
			$obj->DocType = 0;
			$obj->ObjectType = "letter";		
			$obj->ObjectID = $Letter->LetterID;
			$obj->AddDocument();
			$DocumentID = $obj->DocumentID;
		}
		else
			$DocumentID = $dt[0]["DocumentID"];
		
		//..............................................

		$obj2 = new DMS_DocFiles();
		$obj2->DocumentID = $DocumentID;
		$obj2->PageNo = PdoDataAccess::GetLastID("DMS_DocFiles", "PageNo", 
			"DocumentID=?", array($DocumentID)) + 1;
		$obj2->FileType = $extension;
		$obj2->FileContent = substr(fread(fopen($_FILES['PageFile']['tmp_name'], 'r'), 
				$_FILES ['PageFile']['size']), 0, 200);
		$obj2->AddPage();

		$fp = fopen(getenv("DOCUMENT_ROOT") . "/storage/documents/". $obj2->RowID . "." . $extension, "w");
		fwrite($fp, substr(fread(fopen($_FILES['PageFile']['tmp_name'], 'r'), 
				$_FILES ['PageFile']['size']),200) );
		fclose($fp);
	}

	//------------- sign if regPerson and SignPerson are the same --------------
	$obj = new OFC_letters($Letter->LetterID);
	if($obj->PersonID == $obj->SignerPersonID && $obj->PersonID == $_SESSION["USER"]["PersonID"])
	{
		$PersonObj = new BSC_persons($obj->SignerPersonID);
		$obj->IsSigned = "YES";
		$obj->SignPostID = $PersonObj->_PostID;
		$obj->EditLetter();
	}
	//--------------------------------------------------------------------------
	
	if($dieing)
	{
		Response::createObjectiveResponse($res, $Letter->GetExceptionCount() != 0 ? 
			$Letter->popExceptionDescription() : $Letter->LetterID);
		die();
	}
	return true;    
}

function deleteLetter() {

    $res = OFC_letters::RemoveLetter($_POST["LetterID"]);
    Response::createObjectiveResponse($res, '');
    die();
}

function selectLetterPages(){
	
	$letterID = !empty($_REQUEST["LetterID"]) ? $_REQUEST["LetterID"] : 0;
	$dt = PdoDataAccess::runquery("select RowID, DocumentID, DocDesc, ObjectID , FileType
		from DMS_DocFiles join DMS_documents using(DocumentID)
		where ObjectType='letter' AND ObjectID=?", array($letterID));
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function DeletePage(){
	
	$DocumentID = $_POST["DocumentID"];
	$ObjectID = $_POST["ObjectID"];
	$RowID = $_POST["RowID"];
	
	$obj = new DMS_documents($DocumentID);
	if($obj->ObjectID != $ObjectID)
	{
		echo Response::createObjectiveResponse (false, "");
		die();
	}
	
	$result = DMS_DocFiles::DeletePage($RowID);
	
	$dt = DMS_DocFiles::SelectAll("DocumentID=?", array($DocumentID));
	if(count($dt) == 0)
	{
		$result = DMS_documents::DeleteDocument($DocumentID);
	}
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function selectSendTypes(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=12 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SignLetter(){
	
	$LetterID = $_POST["LetterID"];
	
	$obj = new OFC_letters($LetterID);
	$result = false;
	if($obj->SignerPersonID == $_SESSION["USER"]["PersonID"])
	{
		$PersonObj = new BSC_persons($obj->SignerPersonID);
		
		$obj->IsSigned = "YES";
		$obj->SignPostID = $PersonObj->_PostID;
		$obj->LetterDate = PDONOW;
		$result = $obj->EditLetter();
	}
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function GroupSignLetter(){
	
	foreach($_POST as $key=>$val)
	{
		if(strpos($key, "chk_") !== false)
		{
			$SendID = preg_replace("/chk_/", "", $key);
			$sobj = new OFC_send($SendID);	
			$obj = new OFC_letters($sobj->LetterID);
			if($obj->SignerPersonID == $_SESSION["USER"]["PersonID"])
			{
				$PersonObj = new BSC_persons($obj->SignerPersonID);

				$obj->IsSigned = "YES";
				$obj->SignPostID = $PersonObj->_PostID;
				$obj->LetterDate = PDONOW;
				$result = $obj->EditLetter();
			}
		}
	}
		
	echo Response::createObjectiveResponse(true, "");
	die();
}

function CopyLetter(){
	
	$baseObj = new OFC_letters($_POST["LetterID"]);
	$obj = new OFC_letters();
	PdoDataAccess::FillObjectByObject($baseObj, $obj);
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	$obj->LetterDate = PDONOW;
	$obj->RegDate = PDONOW;
	unset($obj->LetterID);
	unset($obj->IsSigned);
	unset($obj->RefLetterID);
	$result = $obj->AddLetter();
	
	echo Response::createObjectiveResponse($result, $obj->LetterID);
	die();
}

function UnSeen(){
	
	$obj = new OFC_send($_POST["SendID"]);
	$obj->IsSeen = "NO";
	$result = $obj->EditSend();
	echo Response::createObjectiveResponse($result, "");
	die();
}
//.............................................

function receiversSelect(){
	
	$param = array(":q" => "%" . $_REQUEST["query"] . "%");
	
	$query = "select 'Person' type,p.PersonID id, concat_ws(' ',fname,lname,CompanyName) title
			from BSC_persons p 
				where IsStaff='YES'
				AND concat_ws(' ',fname,lname,CompanyName) like :q
			
			union all
			
			select 'Group' type,GroupID id, GroupDesc title from FRW_AccessGroups
			where GroupDesc like :q
			
			order by type";
	
	$dt = PdoDataAccess::runquery($query, $param);
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SendToList(){
	
	$param = array(":q" => "%" . $_REQUEST["query"] . "%");
	
	$query = "select if(r.ToPersonID>0,'Person','Group' ) type,
					if(r.ToPersonID>0, concat('p_',r.ToPersonID),concat('g_',ToGroupID) )  id,  
					if(r.ToPersonID>0, concat_ws(' ',fname,lname,CompanyName),GroupDesc )  name
					
			from OFC_receivers r 
				left join BSC_persons p on(r.ToPersonID=p.PersonID) 
				left join FRW_AccessGroups g on(g.GroupID=r.ToGroupID)
			
			where r.PersonID=" . $_SESSION["USER"]["PersonID"] . "
				AND if(r.ToPersonID>0, concat_ws(' ',fname,lname,CompanyName),GroupDesc ) like :q

			order by type";
	
	$dt = PdoDataAccess::runquery($query, $param);
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SendLetter(){
	
	SaveLetter(false);
	$var = new OFC_send($_POST["SendID"]); /*new added*/
	$sendType = $var->SendType; /*new added*/
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if(isset($_POST["SendID"]) && $_POST["SendID"]*1 > 0)
	{
		$obj = new OFC_send();
		$obj->SendID = $_POST["SendID"];
		$obj->IsSeen = "YES";
		$obj->SendType = $sendType; /*new added*/
		$obj->EditSend($pdo);
	}
	
	$sendArr = json_decode($_POST["SendIDArr"]);
	if(empty($sendArr))
		$sendArr[] = $_POST["SendID"];

	foreach($sendArr as $SendID)
	{
		if($SendID == "0")
		{
			$LetterObj = new OFC_letters($_POST["LetterID"]);
		}
		else
		{
			$SendObj = new OFC_send($SendID);
			$LetterObj = new OFC_letters($SendObj->LetterID);
		}
		
		$toPersonArr = array();
		foreach($_POST as $key => $value)
		{
			if(strpos($key, "ToPersonID") === false)
				continue;

			$index = preg_split("/_/", $key);
			$index = $index[0];
			$toPersonID = $value;

			//------------------------
			$arr = array();
			if(strpos($toPersonID,"p_") !== false)
			{
				$personID = substr($toPersonID, 2);
				if(isset($toPersonArr[ $personID ]))
					continue;
				$arr[] = $personID;
				$toPersonArr[ $personID ] = true;
			}
			else {
				$GroupID = substr($toPersonID, 2);
				$dt = PdoDataAccess::runquery("select * from FRW_AccessGroupList where GroupID=?", array($GroupID));
				foreach($dt as $row)
				{
					if(!isset($toPersonArr[ $row["PersonID"] ]))
					{
						$arr[] = $row["PersonID"];
						$toPersonArr[ $row["PersonID"] ] = true;
					}
				}
			}
			//------------------------
			for($i=0; $i<count($arr); $i++)
			{
				if($LetterObj->AccessType == OFC_ACCESSTYPE_SECRET)
				{
					if(!OFC_roles::UserHasRole($arr[$i], OFC_ROLE_SECRET))
					{
						$pdo->rollBack();
						echo Response::createObjectiveResponse(false, "نامه محرمانه را تنها به افرادی که دسترسی نامه محرمانه دارند می توانید ارسال کنید");
						die();
					}
				}

				$obj = new OFC_send();
				$obj->LetterID = $LetterObj->LetterID;
				$obj->FromPersonID = $_SESSION["USER"]["PersonID"];
				$obj->ToPersonID = $arr[$i];
				$obj->SendDate = PDONOW;
				$obj->SendType = $_POST[$index . "_SendType"];
				$obj->ResponseTimeout = $_POST[$index . "_ResponseTimeout"];
				$obj->FollowUpDate = $_POST[$index . "_FollowUpDate"];
				$obj->IsUrgent = $_POST[$index . "_IsUrgent"];
				$obj->IsCopy = isset($_POST[$index . "_IsCopy"]) ? "YES" : "NO";
				$obj->SendComment = $_POST[$index . "_SendComment"];
				$obj->SendComment = $obj->SendComment == "شرح ارجاع" ? "" : $obj->SendComment;
				if(!$obj->AddSend($pdo))
				{
					$pdo->rollBack();
					print_r(ExceptionHandler::PopAllExceptions());
					echo Response::createObjectiveResponse(false, "");
					die();
				}
			}
		}
	}
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function ReturnSend(){
	
	$LetterID = $_POST["LetterID"];
	$SendID = $_POST["SendID"];
	
	$obj = new OFC_send($SendID);
	if($obj->LetterID <> $LetterID)
	{
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	if($obj->IsSeen == "YES")
	{
		echo Response::createObjectiveResponse(false, "IsSeen");
		die();
	}
	
	$result = $obj->DeleteSend();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteSend(){
	
	$mode = $_POST["mode"];
	$SendID = $_POST["SendID"];
	$obj = new OFC_send($SendID);	
	if($obj->ToPersonID == $_SESSION["USER"]["PersonID"])
	{
		$obj->IsDeleted = $mode == "1" ? "NO" : "YES";
		$obj->EditSend();
	}	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GroupDeleteSend(){
	
	foreach($_POST as $key=>$val)
	{
		if(strpos($key, "chk_") !== false)
		{
			$SendID = preg_replace("/chk_/", "", $key);
			$obj = new OFC_send($SendID);	
			if($obj->ToPersonID == $_SESSION["USER"]["PersonID"])
			{
				$obj->IsDeleted = "YES";
				$obj->EditSend();
			}	
		}
	}
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteSender(){
	
	$mode = $_POST["mode"];
	$SendID = $_POST["SendID"];
	$obj = new OFC_send($SendID);	
	if($obj->FromPersonID == $_SESSION["USER"]["PersonID"])
	{
		$obj->SenderDelete = $mode == "1" ? "NO" : "YES";
		$obj->EditSend();
	}	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GroupDeleteSender(){
	
	foreach($_POST as $key=>$val)
	{
		if(strpos($key, "chk_") !== false)
		{
			$SendID = preg_replace("/chk_/", "", $key);
			$obj = new OFC_send($SendID);	
			if($obj->FromPersonID == $_SESSION["USER"]["PersonID"])
			{
				$obj->SenderDelete = "YES";
				$obj->EditSend();
			}	
		}
	}
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetLetterComments(){
	
	$dt = PdoDataAccess::runquery("
		select s.* ,concat_ws(' ',fname, lname,CompanyName) fromPerson
		from OFC_send s
			join BSC_persons on(PersonID=FromPersonID)
		where LetterID=? AND SendComment<>'' AND SendComment is not null
		group by FromPersonID,SendComment
		order by SendID desc", 
		array($_REQUEST["LetterID"]));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}
//.............................................

function SelectArchiveNodes(){

	$dt = PdoDataAccess::runquery("
		SELECT 
			ParentID,FolderID id,FolderName as text,'true' as leaf, f.*
		FROM OFC_archive f
		where PersonID=?
		order by ParentID,FolderName", array($_SESSION["USER"]["PersonID"]));

    $returnArray = array();
    $refArray = array();

    foreach ($dt as $row) {
        if ($row["ParentID"] == 0) {
            $returnArray[] = $row;
            $refArray[$row["id"]] = &$returnArray[count($returnArray) - 1];
            continue;
        }

        $parentNode = &$refArray[$row["ParentID"]];

        if (!isset($parentNode["children"])) {
            $parentNode["children"] = array();
            $parentNode["leaf"] = "false";
        }
        $lastIndex = count($parentNode["children"]);
        $parentNode["children"][$lastIndex] = $row;
        $refArray[$row["id"]] = &$parentNode["children"][$lastIndex];
    }

    $str = json_encode($returnArray);

    $str = str_replace('"children"', 'children', $str);
    $str = str_replace('"leaf"', 'leaf', $str);
    $str = str_replace('"text"', 'text', $str);
    $str = str_replace('"id"', 'id', $str);
    $str = str_replace('"true"', 'true', $str);
    $str = str_replace('"false"', 'false', $str);

    echo $str;
    die();
}

function SaveFolder(){
	
	$obj = new OFC_archive();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	$obj->ParentID = $obj->ParentID == "src" ? "0" : $obj->ParentID;		
	
	if(empty($obj->FolderID))
		$result = $obj->AddFolder();
	else
		$result = $obj->EditFolder();
	
	echo Response::createObjectiveResponse($result, $result ? $obj->FolderID : "");
	die();
}

function DeleteFolder(){
	
	$FolderID = $_POST["FolderID"];
	$result = OFC_archive::DeleteFolder($FolderID);
	echo Response::createObjectiveResponse($result, "");
	die();
}

function AddLetterToFolder(){
	
	$LetterID = $_POST["LetterID"];
	$FolderID = $_POST["FolderID"];
	
	PdoDataAccess::runquery("insert into OFC_ArchiveItems values(?,?)", array($FolderID, $LetterID));
	
	$dt = PdoDataAccess::runquery("select SendID from OFC_send where ToPersonID=? AND LetterID=?", 
			array($_SESSION["USER"]["PersonID"], $LetterID));
	if(count($dt) > 0)
	{
		foreach($dt as $row)
		{
			$obj = new OFC_send($row["SendID"]);	
			$obj->IsDeleted = "YES";
			$obj->EditSend();
		}
	}
	echo Response::createObjectiveResponse(true, "");
	die();
}

function RemoveLetterFromFolder(){
	
	$LetterID = $_POST["LetterID"];
	$FolderID = $_POST["FolderID"];
	
	PdoDataAccess::runquery("delete from OFC_ArchiveItems where FolderID=? AND LetterID=?",
		array($FolderID, $LetterID));

	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

//.............................................

function GetLetterCustomerss(){

	$dt = PdoDataAccess::runquery("
		select RowID,LetterID,IsHide,LetterTitle,o.PersonID,concat_ws(' ',CompanyName,fname,lname) fullname 
		from OFC_LetterCustomers o join BSC_persons using(PersonID)
		where LetterID=?", array($_REQUEST["LetterID"]));
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveLetterCustomer(){
	
	$obj = new OFC_LetterCustomers();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$obj->IsHide = $obj->IsHide ? "YES" : "NO";
	
	if($obj->RowID == "")
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteLetterCustomer(){
	
	$obj = new OFC_LetterCustomers($_POST["RowID"]);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function GetLetterNotes(){

	$dt = OFC_LetterNotes::Get(" AND LetterID=? AND PersonID=?", 
		array($_REQUEST["LetterID"], $_SESSION["USER"]["PersonID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function GetRemindNotes(){

	$dt = OFC_LetterNotes::GetRemindNotes();
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SeeNote(){
	
	$obj = new OFC_LetterNotes();
	$obj->NoteID = $_POST["NoteID"];
	$obj->IsSeen = "YES";
	$result = $obj->Edit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function SaveLetterNote(){
	
	$obj = new OFC_LetterNotes();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	
	if($obj->NoteID == "")
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteLetterNote(){
	
	$obj = new OFC_LetterNotes($_POST["NoteID"]);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function SelectTemplates(){

	$temp = OFC_templates::Get();
	echo dataReader::getJsonData($temp->fetchAll(), $temp->rowCount(), $_GET["callback"]);
	die();
}

function AddToTemplates(){
	
	$obj = new OFC_templates();
	$obj->FillObjectByArray($obj, $_POST);
	
	$result = $obj->Add();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function SaveTemplates(){
	
	$obj = new OFC_templates();
	$obj->FillObjectByArray($obj, $_POST);
	
	$result = $obj->Edit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteTemplate(){
	
	$obj = new OFC_templates($_POST["TemplateID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//..............................................

function SendToMessageList(){
	
	$param = array(":q" => "%" . $_REQUEST["query"] . "%");
	
	$query = "select 'Person' type, concat('p_',PersonID)  id, concat_ws(' ',fname,lname,CompanyName) name
			from BSC_persons where IsStaff='YES' AND IsActive='YES'				
			
			union All 
			
				select 'Group' type, concat('g_',GroupID) id, GroupDesc name
				from FRW_AccessGroups
			
			order by type,name";
	
	$dt = PdoDataAccess::runquery($query, $param);
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SelectMyMessages(){
	
	if($_REQUEST["mode"] == "receive")
	{
		$query = "select m.*,r.* ,concat(fname,' ',lname) FromPersonName, 
				substr(MsgDate,1,10) _MsgDate,substr(MsgDate,11,6) _MsgTime
			from OFC_messages m join OFC_MessageReceivers r using(MessageID)
				join BSC_persons p on(m.PersonID=p.PersonID)
			where r.PersonID=:p";
		
		$query .= isset($_REQUEST["deleted"]) && $_REQUEST["deleted"] == "true" ? 
			" AND r.IsDeleted='YES'" : " AND r.IsDeleted='NO'";
	}
	else
	{
		$query = "select m.*,r.* ,concat(fname,' ',lname) ToPersonName, 
				substr(MsgDate,1,10) _MsgDate,substr(MsgDate,11,6) _MsgTime
			from OFC_messages m join OFC_MessageReceivers r using(MessageID)
			join BSC_persons p on(r.PersonID=p.PersonID)
			where m.PersonID=:p";
		
		$query .= isset($_REQUEST["deleted"]) && $_REQUEST["deleted"] == "true" ? 
			" AND m.IsDeleted='YES'" : " AND m.IsDeleted='NO'";
	}
	
	$param = array(":p" => $_SESSION["USER"]["PersonID"]);
	
	$dt = PdoDataAccess::runquery_fetchMode($query . dataReader::makeOrder(), $param);
	//echo PdoDataAccess::GetLatestQueryString();
	$cnt = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt, $cnt, $_GET["callback"]);
	die();
}

function SaveMessage(){
	
	$obj = new OFC_messages();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	$obj->MsgDate = PDONOW;
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if(!$obj->Add($pdo))
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "خطا در ایجاد پیام");
		die();
	}
	
	$receivers = json_decode($_POST["receivers"]);
	$arr = array();
	$toPersonArr = array();
	foreach($receivers as $toPersonID)
	{
		if(strpos($toPersonID,"p_") !== false)
		{
			$personID = substr($toPersonID, 2);
			if(isset($toPersonArr[ $personID ]))
				continue;
			$arr[] = $personID;
			$toPersonArr[ $personID ] = true;
		}
		else {
			$GroupID = substr($toPersonID, 2);
			$dt = PdoDataAccess::runquery("select * from FRW_AccessGroupList where GroupID=?", array($GroupID));
			foreach($dt as $row)
			{
				if(!isset($toPersonArr[ $row["PersonID"] ]))
				{
					$arr[] = $row["PersonID"];
					$toPersonArr[ $row["PersonID"] ] = true;
				}
			}
		}
	}
	
	for($i=0; $i<count($arr); $i++)
	{
		$obj2 = new OFC_MessageReceivers();
		$obj2->MessageID = $obj->MessageID;
		$obj2->PersonID = $arr[$i];
		$obj2->Add($pdo);
	}
	
	if(ExceptionHandler::GetExceptionCount() != 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "خطا در ارسال پیام");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SeeMessage(){
	
	$obj = new OFC_MessageReceivers($_POST["SendID"]);
	$obj->IsSeen = "YES";
	$obj->Edit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteMessage(){
	
	if(!empty($_POST["SendID"]))
	{
		$obj = new OFC_MessageReceivers($_POST["SendID"]);
		$obj->IsDeleted = "YES";
	}
	else
	{
		$obj = new OFC_messages($_POST["MessageID"]);
		$obj->IsDeleted = "YES";
	}
	
	$obj->Edit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

//.............................................

function GetRefLetters(){

	$dt = PdoDataAccess::runquery("
		select r.LetterID,r.RefLetterID,l.LetterTitle
		from OFC_RefLetters r
		join OFC_letters l on(if(r.RefLetterID=:l,r.LetterID=l.LetterID,r.RefLetterID=l.LetterID))
		where (r.LetterID=:l or r.RefLetterID=:l)

		union all
		
		select r.LetterID,r.RefLetterID,l.LetterTitle
		from OFC_RefLetters r0 join OFC_RefLetters r on(r0.RefLetterID=r.LetterID)
		join OFC_letters l on(r.RefLetterID=l.LetterID)
		where r0.LetterID=:l

		union all
		
		select r.LetterID,r.RefLetterID,l.LetterTitle
		from OFC_RefLetters r00 join OFC_RefLetters r0 on(r00.RefLetterID=r0.LetterID)
		join OFC_RefLetters r on(r0.RefLetterID=r.LetterID)
		join OFC_letters l on(r.RefLetterID=l.LetterID)
		where r00.LetterID=:l", 
			array(":l" => $_REQUEST["LetterID"]));
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveRefLetter(){
	
	$obj = new OFC_RefLetters();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	$LObj = new OFC_letters($obj->RefLetterID);
	if(!$LObj->LetterID)
	{
		echo Response::createObjectiveResponse(false, "شماره نامه موجود نیست");
		die();
	}
	
	if(!$obj->Add())
	{
		echo Response::createObjectiveResponse(false, "این نامه قبلا اضافه شده است");
		die();
	}
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteRefLetter(){
	
	$obj = new OFC_RefLetters();
	$result = $obj->RemoveRef($_POST["LetterID"], $_POST["RefLetterID"]);
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function GetSendComments(){

	$dt = OFC_SendComments::Get(" AND PersonID=?", array($_SESSION["USER"]["PersonID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SaveSendComment(){
	
	$obj = new OFC_SendComments();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	
	if(!isset($obj->RowID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteSendComment(){
	
	$obj = new OFC_SendComments($_POST["RowID"]);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function GetReceivers(){

	$dt = OFC_receivers::Get(" AND r.PersonID=?", array($_SESSION["USER"]["PersonID"]));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveReceiver(){
	
	$obj = new OFC_receivers();
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	
	if($_POST["type"] == "Person")
		$obj->ToPersonID = $_POST["id"];
	else
		$obj->ToGroupID = $_POST["id"];
	
	$result = $obj->Add();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteReceiver(){
	
	$obj = new OFC_receivers($_POST["RowID"]);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function GetDailyTips(){

	$dt = OFC_DailyTips::Get();
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function SaveDailyTip(){
	
	$obj = new OFC_DailyTips();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(!isset($obj->RowID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteDailyTip(){
	
	$obj = new OFC_DailyTips($_POST["RowID"]);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function EmailLetter(){
	
	$PersonID = $_POST["PersonID"];
	$PObj = new BSC_persons($PersonID);
	if($PObj->email == "")
	{
		echo Response::createObjectiveResponse(false, "ذینفع انتخابی فاقد پست الکترونیک می باشد");
		die();
	}
	
	$LetterID = $_POST["LetterID"];
	$LObj = new OFC_letters($LetterID);
	$CObj = new OFC_LetterCustomers($_POST["RowID"]);
	$html = $LObj->LetterContent(true);
	
	//------------ create PDF of letter -------------------
	require_once inc_Mpdf;
	$html = iconv("utf-8","UTF-8//IGNORE",$html);
	$mpdf = new mPDF('fa','A4','','BNazanin',5,5,5,5,16,13);
	$mpdf->SetDirectionality('rtl');
	$mpdf->WriteHTML($html);
	$mpdf->Output("letter.pdf", "F");
	
	/*if($_SESSION["USER"]["UserName"] == "admin")
	{
		header('Content-disposition:inline; filename=file.pdf');
		header('Content-type: application/pdf');
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header("Content-Transfer-Encoding: binary");

		echo file_get_contents("letter.pdf");
		die();	
	}*/
	
	//------------ email letter ------------------------
	require_once 'email.php';
	$attachmnets = array(
		array("name" => "letter.pdf","path" => "letter.pdf")
	);
	$dt = PdoDataAccess::runquery("select * from DMS_documents d
			join DMS_DocFiles df using(DocumentID)
			where ObjectType='letterAttach' AND ObjectID=?", array($LObj->LetterID));
	foreach($dt as $file)
	{
		$fp = fopen($file["RowID"] . "." . $file["FileType"], "w");
		fwrite($fp, $file["FileContent"] . 
			file_get_contents(getenv("DOCUMENT_ROOT") . "/storage/documents/" .
			$file["RowID"] . "." . $file["FileType"]));
		fclose($fp);
			
		$attachmnets[] = array(
			"name" => $file["DocDesc"] . "." . $file["FileType"],
			"path" => $file["RowID"] . "." . $file["FileType"]
		);
	}

	$result = SendEmail($PObj->email, 
			$CObj->LetterTitle == "" ? $LObj->LetterTitle : $CObj->LetterTitle, 
			"--", $attachmnets);
	if(!$result)
	{
		echo Response::createObjectiveResponse(false, "خطا در ارسال ایمیل");
		die();
	}
	unlink("letter.pdf");
	foreach($dt as $file)
		unlink($file["RowID"] . "." . $file["FileType"]);
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SmsCustomer(){
	
	require_once 'sms.php';
	
	$PersonID = $_POST["PersonID"];
	$PObj = new BSC_persons($PersonID);
	if($PObj->mobile == "")
	{
		echo Response::createObjectiveResponse(false, "مشتری فاقد شماره موبایل یا شماره پیامک می باشد.");
		die();
	}
	else
	{
		$SendError = "";
		$context = "یک نامه جدید در پرتال صندوق پژوهش و فناوری شما وارد شده است \n\n" . 
				"http://saja.krrtf.ir";
		$result = ariana2_sendSMS($PObj->mobile, $context, "number", $SendError);
		if(!$result)
			ExceptionHandler::PushException ("ارسال پیامک به دلیل خطای زیر انجام نگردید" . "[" . $SendError . "]");
	}
	
	echo Response::createObjectiveResponse(true, "");
	die();
}
//.............................................

function SelectOFCRoles(){
	
	$temp = PdoDataAccess::runquery("select RowID,
			PersonID,concat_ws(' ',CompanyName,fname,lname) fullname,
			RoleID,InfoDesc RoleDesc
		from OFC_roles 
			join BSC_persons using(PersonID)
			join BaseInfo on(TypeID=79 AND InfoID=RoleID)");
	echo dataReader::getJsonData($temp, count($temp), $_GET['callback']);
	die();
}

function SelectRoles() {
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=79 AND IsActive='YES'");
	echo dataReader::getJsonData($temp, count($temp), $_GET['callback']);
	die();
}

function SaveRole(){
	
	$obj = new OFC_roles();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$obj->Add();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteRole(){
	
	$obj = new OFC_roles($_POST["RowID"]);
	$obj->Remove();
	echo Response::createObjectiveResponse(true, "");
	die();
}

?>
