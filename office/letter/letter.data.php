<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.10
//---------------------------
 
require_once '../header.inc.php';
require_once(inc_response);
require_once inc_dataReader;
require_once 'letter.class.php';
require_once '../dms/dms.class.php';
require_once '../../framework/person/persons.class.php';

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

    $list = OFC_letters::GetAll($where, $param);
    echo dataReader::getJsonData($list, count($list), $_GET['callback']);
    die();
}

function SelectAllLetter(){
	
	$where = "1=1";
    $param = array();
	
	foreach($_POST as $field => $value)
	{
		if($field == "excel" || empty($value) || strpos($field, "inputEl") !== false)
			continue;
		$prefix = "";
		switch($field)
		{
			case "PersonID": $prefix = "l."; break;
			case "Customer": $prefix = "lc."; break;
			
			case "LetterID": 
			case "LetterTitle": 
				$prefix = "l."; break;
			
			case "FromSendDate":
			case "FromLetterDate":
			case "ToSendDate":
			case "ToLetterDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
				break;
		}
		if(strpos($field, "From") === 0)
			$where .= " AND " . $prefix . substr($field,4) . " >= :$field";
		else if(strpos($field, "To") === 0)
			$where .= " AND " . $prefix . substr($field,2) . " <= :$field";
		else
			$where .= " AND " . $prefix . $field . " like :$field";
		$param[":$field"] = "%" . $value . "%";
	}
	//echo $where;
    $list = OFC_letters::FullSelect($where, $param, dataReader::makeOrder());
	
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

function ReceivedSummary(){
	
	$temp = PdoDataAccess::runquery("
		select s.SendType,InfoDesc SendTypeDesc, count(*) totalCnt, sum(if(s.IsSeen='NO',1,0)) newCnt
		from OFC_send s
			join BaseInfo on(TypeID=12 AND SendType=InfoID)
			left join OFC_send s2 on(s2.LetterID=s.LetterID AND s2.SendID>s.SendID AND s2.FromPersonID=s.ToPersonID)
		where s2.SendID is null AND s.IsDeleted='NO' AND s.ToPersonID=" . $_SESSION["USER"]["PersonID"] . "
		group by s.SendType");		
	
	return $temp;
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
	
	$dt = OFC_letters::SelectReceivedLetters($where, $param);
	//echo PdoDataAccess::GetLatestQueryString();
	$cnt = $dt->rowCount();
	$dt = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt, $cnt, $_GET["callback"]);
	die();
}

function SelectSendedLetters(){
	
	$dt = OFC_letters::SelectSendedLetters();
	//print_r(ExceptionHandler::PopAllExceptions());
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
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=76");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectAccessType(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=77");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}
//.............................................

function SaveLetter($dieing = true) {

    $Letter = new OFC_letters();
    pdoDataAccess::FillObjectByArray($Letter, $_POST);

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
        $res = $Letter->EditLetter();

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
	$dt = PdoDataAccess::runquery("select RowID, DocumentID, DocDesc, ObjectID 
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
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=12");
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
		$obj->SignPostID = $PersonObj->PostID;
		$result = $obj->EditLetter();
	}
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.............................................

function SendLetter(){
	
	SaveLetter(false);
	
	$LetterID = $_POST["LetterID"];
	$toPersonArr = array();
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	if(isset($_POST["SendID"]) && $_POST["SendID"]*1 > 0)
	{
		$obj = new OFC_send();
		$obj->SendID = $_POST["SendID"];
		$obj->IsSeen = "YES";
		$obj->EditSend($pdo);
	}
	$arr = array_keys($_POST);
	foreach($arr as $key)
	{
		if(strpos($key, "ToPersonID") === false)
			continue;
		
		$toPersonID = $_POST[$key];
		if(isset($toPersonArr[$toPersonID]) || $toPersonID*1 == 0)
			continue;
		$toPersonArr[$toPersonID] = true;		
		
		$index = preg_split("/_/", $key);
		$index = $index[0];

		$obj = new OFC_send();
		$obj->LetterID = $LetterID;
		$obj->FromPersonID = $_SESSION["USER"]["PersonID"];
		$obj->ToPersonID = $toPersonID;
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
			echo Response::createObjectiveResponse(false, "");
			die();
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
	$LetterID = $_POST["LetterID"];
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
?>
