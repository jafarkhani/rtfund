<?php
//-------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-------------------------
require_once('header.inc.php');
require_once inc_dataReader;
require_once inc_response;
require_once 'dms.class.php';
require_once '../../framework/baseInfo/baseInfo.class.php';

$task = $_REQUEST["task"];
if(!empty($task)) 
	$task();

function SelectAll(){
	
	$where = "1=1";
	$param = array();
	
	if(!empty($_REQUEST["ObjectType"]))
	{
		$where .= " AND ObjectType=:st";
		$param[":st"] = $_REQUEST["ObjectType"];
	}
	if(!empty($_REQUEST["ObjectID"]))
	{
		$where .= " AND ObjectID=:sid";
		$param[":sid"] = $_REQUEST["ObjectID"];
	}
	if(!empty($_REQUEST["ObjectID2"]))
	{
		$where .= " AND ObjectID2=:oid2";
		$param[":oid2"] = $_REQUEST["ObjectID2"];
	}
	if(isset($_REQUEST["checkRegPerson"]) && $_REQUEST["checkRegPerson"] == "true")
	{
		$where .= " AND RegPersonID=" . $_SESSION["USER"]["PersonID"];
	}

	if(isset($_REQUEST["IsPortal"]) && $_REQUEST["IsPortal"] == 'NO')
	{
		$where .= " AND d.IsHide= 'NO' ";
	}

	if(!empty($_REQUEST["ObjectType"]) && $_REQUEST["ObjectType"] == "package")
		$temp = DMS_documents::SelectFullPackage($_REQUEST["ObjectID"]);
    else if (!empty($_REQUEST["ObjectType"]) && $_REQUEST["ObjectType"] == "letterAttach")
        $temp = DMS_documents::SelectAllLet($where, $param);
    else if (!empty($_REQUEST["ObjectType"]) && $_REQUEST["ObjectType"] == "safeBoxAttach")
        $temp = DMS_documents::SelectAllSafe($where, $param);

    else if (!empty($_REQUEST["ObjectType"]) && $_REQUEST["ObjectType"] == "agencydoc")
        $temp = DMS_documents::SelectAllAgent($where, $param);

	else
		$temp = DMS_documents::SelectAll($where, $param);
	
	for($i=0; $i<count($temp); $i++)
	{
		$temp[$i]["paramValues"] = "";
		
		$dt = PdoDataAccess::runquery("select * from DMS_DocParamValues join DMS_DocParams using(ParamID)
			where DocumentID=?", array($temp[$i]["DocumentID"]));
		foreach($dt as $row)
		{
			$value = $row["ParamValue"];
			if($row["ParamType"] == "currencyfield")
				$value = number_format($value*1);
			
			if($row["DocType"] == DMS_DOCTYPE_LETTER || $row["DocType"] == DMS_DOCTYPE_Documents)
				$temp[$i]["paramValues"] .= $value . "<br>";
			else
				$temp[$i]["paramValues"] .= $row["ParamDesc"] . " : " . $value . "<br>";
		}
		if($temp[$i]["paramValues"] != "")
			$temp[$i]["paramValues"] = substr($temp[$i]["paramValues"], 0 , strlen($temp[$i]["paramValues"])-4);
	}
	
	
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}
function SelectAl(){
	
	$where = "1=1";
	$param = array();
	
	if(!empty($_REQUEST["ObjectType"]))
	{
		$where .= " AND ObjectType=:st";
		$param[":st"] = $_REQUEST["ObjectType"];
	}
	if(!empty($_REQUEST["ObjectID"]))
	{
		$where .= " AND ObjectID=:sid";
		$param[":sid"] = $_REQUEST["ObjectID"];
	}
    
    $temp = DMS_documents::SelectAl($where, $param);
	/*if(!empty($_REQUEST["ObjectID2"]))
	{
		$where .= " AND ObjectID2=:oid2";
		$param[":oid2"] = $_REQUEST["ObjectID2"];
	}
	if(isset($_REQUEST["checkRegPerson"]) && $_REQUEST["checkRegPerson"] == "true")
	{
		$where .= " AND RegPersonID=" . $_SESSION["USER"]["PersonID"];
	}

//............for upload basic doc in portal.....................................
function SaveDocNotReal() {
	/*var_dump($_POST);
    echo '<br>';
    var_dump($_FILES);*/
	foreach($_FILES as $name => $file)
	{
		$num = substr($name, strrpos($name, '-' )+1);
		if(!empty($file["tmp_name"]))
		{
			$st = preg_split("/\./", $file["name"]);
			$extension = strtolower($st [count($st) - 1]);
			if (in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf",
					"xls", "xlsm", "xlsx", "csv", "doc", "docx", "rar", "zip")) === false)
			{
				Response::createObjectiveResponse(false, "فرمت فایل ارسالی نامعتبر است");
				die();
			}
		}
	}
	/*echo $num;
    echo '<br>';*/
	//..............................................

	$obj = new DMS_documents();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->ObjectID = $_POST["ObjectID"];
	$obj->ObjectID2 = isset($_POST["ObjectID2"]) ? $_POST["ObjectID2"] : "0";
	$obj->ObjectType = $_POST["ObjectType"];
	$obj->DocType = 144;

	if($obj->DocMode == "ELEC")
		$obj->place = PDONULL;

	if (empty($obj->DocumentID))
	{
		$result = $obj->AddDocument();
		//new added
		$exitDocumentID = $result[1][0];
		$obj->DocumentID = $exitDocumentID;
		//end new added
	}
	else
	{
		$oldObj = new DMS_documents($obj->DocumentID);
		if($oldObj->IsConfirm == "YES")
		{
			echo Response::createObjectiveResponse(false, "");
			die();
		}
		$obj->IsConfirm = "NOTSET";

		$result = $obj->EditDocument();
	}
	if(!$result)
	{
		//print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse($result, "");
		die();
	}

	//----------------------------------------
	foreach($_FILES as $name => $file)
	{
		/*echo '<br>';
        var_dump('name = '.$name);
        echo '<br>';
        var_dump($file);
        echo '<br>';*/
		if(empty($file["tmp_name"]))
			continue;

		$strNum = substr($name, strrpos($name, '-' )+1);
		for ($i=1; $i<=$num; $i++){

			if ($strNum == $i){
				/*echo $i;
                echo '<br>';*/
				$st = preg_split("/\./", $file["name"]);
				$extension = strtolower($st [count($st) - 1]);

				$obj2 = new DMS_DocFiles();
				$obj2->DocumentID = $obj->DocumentID;
				$PNo = str_replace("baseDoc_", "", $name);
				$obj2->PageNo = str_replace("baseDoc_", "", $name);
				$obj2->PageNo = ($obj2->PageNo)+(2*($i-1));
				$obj2->PageNo = $obj2->PageNo*1 == 0 ? 1 : $obj2->PageNo;
				$obj2->FileType = $extension;
				$obj2->FileContent = substr(fread(fopen($file['tmp_name'], 'r'), $file['size']), 0, 200);

				$dt = PdoDataAccess::runquery("select RowID from DMS_DocFiles where DocumentID=? AND PageNo=?",
					array($obj2->DocumentID, $obj2->PageNo));

				foreach($dt as $row)
					DMS_DocFiles::DeletePage($row["RowID"]);
				/*var_dump($obj2);*/
				$obj2->AddPage();

				$fp = fopen(getenv("DOCUMENT_ROOT") . "/storage/documents/". $obj2->RowID . "." . $extension, "w");
				fwrite($fp, substr(fread(fopen($file['tmp_name'], 'r'), $file['size']),200) );
				fclose($fp);
			}

		}
		/*$st = preg_split("/\./", $file["name"]);
        $extension = strtolower($st [count($st) - 1]);

        $obj2 = new DMS_DocFiles();
        $obj2->DocumentID = $obj->DocumentID;
        $PNo = str_replace("baseDoc_", "", $name);
        $obj2->PageNo = str_replace("baseDoc_", "", $name);
        $obj2->PageNo = $obj2->PageNo*1 == 0 ? 1 : $obj2->PageNo;
        $obj2->FileType = $extension;
        $obj2->FileContent = substr(fread(fopen($file['tmp_name'], 'r'), $file['size']), 0, 200);

        $dt = PdoDataAccess::runquery("select RowID from DMS_DocFiles where DocumentID=? AND PageNo=?",
            array($obj2->DocumentID, $obj2->PageNo));

        foreach($dt as $row)
            DMS_DocFiles::DeletePage($row["RowID"]);

        $obj2->AddPage();

        $fp = fopen(getenv("DOCUMENT_ROOT") . "/storage/documents/". $obj2->RowID . "." . $extension, "w");
        fwrite($fp, substr(fread(fopen($file['tmp_name'], 'r'), $file['size']),200) );
        fclose($fp);*/
	}

	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();



}
function SaveDocReal() {
	/*var_dump($_POST);
    echo '<br>';
    var_dump($_FILES);*/
	foreach($_FILES as $file)
	{
		if(!empty($file["tmp_name"]))
		{
			$st = preg_split("/\./", $file["name"]);
			$extension = strtolower($st [count($st) - 1]);
			if (in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf",
					"xls", "xlsm", "xlsx", "csv", "doc", "docx", "rar", "zip")) === false)
			{
				Response::createObjectiveResponse(false, "فرمت فایل ارسالی نامعتبر است");
				die();
			}
		}
	}
	//..............................................

	$obj = new DMS_documents();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->ObjectID = $_POST["ObjectID"];
	$obj->ObjectID2 = isset($_POST["ObjectID2"]) ? $_POST["ObjectID2"] : "0";
	$obj->ObjectType = $_POST["ObjectType"];
	$obj->DocType = 144;

	if($obj->DocMode == "ELEC")
		$obj->place = PDONULL;
	/*var_dump($obj);*/
	if (empty($obj->DocumentID))
	{
		$result = $obj->AddDocument();
		//new added
		$exitDocumentID = $result[1][0];
		$obj->DocumentID = $exitDocumentID;
		//end new added
	}
	else
	{
		$oldObj = new DMS_documents($obj->DocumentID);
		if($oldObj->IsConfirm == "YES")
		{
			echo Response::createObjectiveResponse(false, "");
			die();
		}
		$obj->IsConfirm = "NOTSET";

		$result = $obj->EditDocument();
	}
	if(!$result)
	{
		//print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse($result, "");
		die();
	}

	//----------------------------------------
	foreach($_FILES as $name => $file)
	{
		if(empty($file["tmp_name"]))
			continue;

		$st = preg_split("/\./", $file["name"]);
		$extension = strtolower($st [count($st) - 1]);

		$obj2 = new DMS_DocFiles();
		$obj2->DocumentID = $obj->DocumentID;
		$obj2->PageNo = str_replace("baseDoc_", "", $name);
		$obj2->PageNo = $obj2->PageNo*1 == 0 ? 1 : $obj2->PageNo;
		$obj2->FileType = $extension;
		$obj2->FileContent = substr(fread(fopen($file['tmp_name'], 'r'), $file['size']), 0, 200);

		$dt = PdoDataAccess::runquery("select RowID from DMS_DocFiles where DocumentID=? AND PageNo=?",
			array($obj2->DocumentID, $obj2->PageNo));

		foreach($dt as $row)
			DMS_DocFiles::DeletePage($row["RowID"]);

		$obj2->AddPage();

		$fp = fopen(getenv("DOCUMENT_ROOT") . "/storage/documents/". $obj2->RowID . "." . $extension, "w");
		fwrite($fp, substr(fread(fopen($file['tmp_name'], 'r'), $file['size']),200) );
		fclose($fp);
	}

	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();



}
//...............................................................................

function SaveDocument() {

	foreach($_FILES as $file)
	{
		if(!empty($file["tmp_name"]))
		{
			$st = preg_split("/\./", $file["name"]);
			$extension = strtolower($st [count($st) - 1]);
			if (in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf", 
				"xls", "xlsm", "xlsx", "csv", "doc", "docx", "rar", "zip")) === false) 
			{
				Response::createObjectiveResponse(false, "فرمت فایل ارسالی نامعتبر است");
				die();
			}
		}
	}
	//..............................................
	
	$obj = new DMS_documents();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->IsHide = !isset($_POST['IsHide'])  ? "YES" : "NO"; // new added
	$obj->ObjectID = $_POST["ObjectID"];
	$obj->ObjectID2 = isset($_POST["ObjectID2"]) ? $_POST["ObjectID2"] : "0";
	$obj->ObjectType = $_POST["ObjectType"];

	if($obj->DocMode == "ELEC")
		$obj->place = PDONULL;
	
	if (empty($obj->DocumentID))
		$result = $obj->AddDocument();
	else
	{
		$oldObj = new DMS_documents($obj->DocumentID);
		if($oldObj->IsConfirm == "YES")
		{
			echo Response::createObjectiveResponse(false, "");
			die();
		}
		$obj->IsConfirm = "NOTSET";
		
		$result = $obj->EditDocument();
	}
	if(!$result)
	{
		//print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse($result, "");
		die();
	}
	
	//-------------- params ------------------
	PdoDataAccess::runquery("delete from DMS_DocParamValues where DocumentID=?", array($obj->DocumentID));
	$arr = array_keys($_POST);
	foreach($arr as $key)
	{
		if(strpos($key, "Param") !== false)
		{
			PdoDataAccess::runquery("insert into DMS_DocParamValues values(?,?,?)",
				array($obj->DocumentID, substr($key,5), $_POST[$key] ));
		}
	}
	//----------------------------------------
	foreach($_FILES as $name => $file)
	{
		if(empty($file["tmp_name"]))
			continue;
		
		$st = preg_split("/\./", $file["name"]);
		$extension = strtolower($st [count($st) - 1]);
		
		$obj2 = new DMS_DocFiles();
		$obj2->DocumentID = $obj->DocumentID;
		$obj2->PageNo = str_replace("FileType_", "", $name);
		$obj2->PageNo = $obj2->PageNo*1 == 0 ? 1 : $obj2->PageNo;
		$obj2->FileType = $extension;
		$obj2->FileContent = substr(fread(fopen($file['tmp_name'], 'r'), $file['size']), 0, 200);
		
		$dt = PdoDataAccess::runquery("select RowID from DMS_DocFiles where DocumentID=? AND PageNo=?",
			array($obj2->DocumentID, $obj2->PageNo));
		
		foreach($dt as $row)
			DMS_DocFiles::DeletePage($row["RowID"]);
				
		$obj2->AddPage();
		
		$fp = fopen(getenv("DOCUMENT_ROOT") . "/storage/documents/". $obj2->RowID . "." . $extension, "w");
		fwrite($fp, substr(fread(fopen($file['tmp_name'], 'r'), $file['size']),200) );
		fclose($fp);
	}
	
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteDocument() {
	
	$DocumentID = $_POST["DocumentID"];
	
	$obj = new DMS_documents($DocumentID);
	if($obj->IsConfirm == "YES")
	{
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	PdoDataAccess::runquery("delete from DMS_DocParamValues where DocumentID=?", array($DocumentID));	
	$result = DMS_documents::DeleteDocument($DocumentID);
	
	$dt = PdoDataAccess::runquery("select RowID from DMS_DocFiles where DocumentID=?", array($DocumentID));
	foreach($dt as $row)
		DMS_DocFiles::DeletePage($row["RowID"]);
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ConfirmDocument(){
	
	$obj = new DMS_documents();
	
	$obj->DocumentID = $_REQUEST["DocumentID"];
	$obj->IsConfirm = $_POST["mode"];
	$obj->ConfirmPersonID = $_SESSION["USER"]["PersonID"];
	$obj->RejectDesc = $_POST["RejectDesc"];
	
	$result = $obj->EditDocument();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function UnConfirmDocument(){
	
	$obj = new DMS_documents();
	
	$obj->DocumentID = $_REQUEST["DocumentID"];
	$obj->IsConfirm = "NOTSET";
	
	$result = $obj->EditDocument();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function selectDocTypeGroups(){
	
	if(!empty($_REQUEST["ObjectType"]) &&  $_REQUEST["ObjectType"] != "package")
		$dt = PdoDataAccess::runquery ("
			select b.* from BaseInfo b " .
			(session::IsPortal() ? " join BaseInfo b2 on(b2.param2=1 AND b2.TypeID=8 AND b.InfoID=b2.param1)" : "") . " 
			where b.typeID=7 AND b.IsActive='YES' AND (b.param1='0' or b.param1=:ot or b.param2=:ot)
			group by b.InfoID", array(":ot" => $_REQUEST["ObjectType"]));
	else
		$dt = PdoDataAccess::runquery("select * from BaseInfo where typeID=7 AND IsActive='YES'");
	
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectDocTypes(){
	
	$groupID = $_REQUEST["GroupID"];
	$dt = PdoDataAccess::runquery("select * from BaseInfo 
		where typeID=8 AND IsActive='YES' AND param1=? " . 
		(session::IsPortal() ? " AND param2=1" : ""), array($groupID));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectAllParams(){
	
	$dt = PdoDataAccess::runquery("select * from DMS_DocParams where IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function selectParamValues(){
	
	$dt = PdoDataAccess::runquery("select * from DMS_DocParamValues where DocumentID=?", 
			array($_GET["DocumentID"]));
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SearchLetters(){
	
	$params = array(":lt" => "%" . $_REQUEST["query"] . "%");
	$list = PdoDataAccess::runquery_fetchMode("select LetterID,LetterTitle 
			from OFC_letters 
			where AccessType=1 AND ( LetterTitle like :lt or LetterID like :lt )", $params);
	$no = $list->rowCount();
	$list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    echo dataReader::getJsonData($list, $no, $_GET['callback']);
    die();
}

//.................................................

function SaveDocType(){
	
	$st = stripslashes(stripslashes($_POST["record"]));
	$data = json_decode($st);

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new BaseInfo();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	if($obj->TypeID == "8")
		$obj->param2 = $data->param2 == "1" ? "1" : "0"; 
		
	if($data->InfoID*1 == 0)
	{
		$result = $obj->Add($pdo);
	}
	else
		$result = $obj->Edit($obj->InfoID, $pdo);

	$pdo->commit();
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

function DeleteDocType(){
	
	PdoDataAccess::runquery("update BaseInfo set IsActive='NO' 
		where TypeID=? AND InfoID=?",array($_REQUEST["TypeID"], $_REQUEST["InfoID"]));

	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

//.................................................

function selectPackages(){
	
	$where = " AND BranchID=?";
	$param = array($_REQUEST["BranchID"]);
	
	if(!empty($_GET["fields"]))
	{
		$field = $_GET["fields"];
		$field = $field == "PersonID" ? "concat_ws(' ',fname,lname,CompanyName)" : $field;
		
		$where .= " AND " . $field . " like ?";
		$param[] = "%" . $_GET["query"] . "%";
	}
	
	$dt = DMS_packages::Get($where . dataReader::makeOrder(), $param);
	$list = PdoDataAccess::fetchAll($dt, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($list, $dt->rowCount(), $_GET["callback"]);
	die();
}

function SavePackage(){
	
	$obj = new DMS_packages();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(empty($obj->PackNo))
		$obj->PackNo = DMS_packages::GetPackNo ($obj->BranchID);
	
	if(!$obj->PackNoIsValid())
	{
		echo Response::createObjectiveResponse(false, "شماره پرونده در شعبه مربوطه تکراری است");
		die();
	}
	
	if($obj->PackageID*1 > 0)
		$result = $obj->Edit();
	else
		$result = $obj->Add();
	
	//print_r(ExceptionHandler::PopAllExceptions());	
	echo Response::createObjectiveResponse($result, "");
	die();
	
}

function DeletePackage(){
	
	$obj = new DMS_packages();
	$obj->PackageID = $_POST["PackageID"];
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.................................................

function selectPackageItems(){
	
	$temp = DMS_PackageItems::Get(" AND PackageID=?", array($_REQUEST["PackageID"]));
	$temp = $temp->fetchAll();
	
	for($i=0; $i<count($temp); $i++)
	{
		$temp[$i]["paramValues"] = "";
		
		$dt = PdoDataAccess::runquery("select * from DMS_DocParamValues join DMS_DocParams using(ParamID)
			where DocumentID=?", array($temp[$i]["DocumentID"]));
		foreach($dt as $row)
		{
			$value = $row["ParamValue"];
			if($row["ParamType"] == "currencyfield")
				$value = number_format((int)$value);
			$temp[$i]["paramValues"] .= $row["ParamDesc"] . " : " . $value . "<br>";
		}
		if($temp[$i]["paramValues"] != "")
			$temp[$i]["paramValues"] = substr($temp[$i]["paramValues"], 0 , strlen($temp[$i]["paramValues"])-4);
	}
	
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function selectObjectTypes(){
	
	$list = PdoDataAccess::runquery("select * from BaseInfo where typeID=11 AND IsActive='YES'");
	
	echo dataReader::getJsonData($list, count($list), $_GET["callback"]);
	die();
}

function SavePackageItem(){
	
	$obj = new DMS_PackageItems();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$dt = PdoDataAccess::runquery("select PackageID,PackNo 
		from DMS_PackageItems join DMS_packages using(PackageID) where ObjectType=? AND ObjectID=?",
		array($obj->ObjectType, $obj->ObjectID));
	if(count($dt) > 0)
	{
		if($dt[0]["PackageID"] == $obj->PackageID)
		{
			echo Response::createObjectiveResponse(false, "آیتم مربوطه قبلا به این پرونده اضافه شده است");
			die();
		}
		
		echo Response::createObjectiveResponse(false, "آیتم مربوطه قبلا به پرونده شماره " .
				$dt[0]["PackNo"] . " اضافه شده است");
		die();
	}
	
	switch ($obj->ObjectType)
	{
		case "1" : // loan
			require_once '../../loan/request/request.class.php';
			$sourceObj = new LON_requests($obj->ObjectID);
			if(empty($sourceObj->RequestID))
			{
				echo Response::createObjectiveResponse(false, "شماره آیتم مورد نظر معتبر نمی باشد");
				die();
			}
			break;
		case "2" : // contract
			require_once '../../loan/contract/contract/contract.class.php';
			$sourceObj = new CNT_contracts($obj->ObjectID);
			if(empty($sourceObj->ContractID))
			{
				echo Response::createObjectiveResponse(false, "شماره آیتم مورد نظر معتبر نمی باشد");
				die();
			}
			break;
		case "3" : // plan
			require_once '../../plan/plan/plan.class.php';
			$sourceObj = new PLN_plans($obj->ObjectID);
			if(empty($sourceObj->PlanID))
			{
				echo Response::createObjectiveResponse(false, "شماره آیتم مورد نظر معتبر نمی باشد");
				die();
			}
			break;
	}
	
	$result = $obj->Add();
	
	//print_r(ExceptionHandler::PopAllExceptions());	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeletePackageItem(){
	
	$obj = new DMS_PackageItems();
	$obj->RowID = $_POST["RowID"];
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//.................................................

function selectDocParams() {
	
	$temp = DMS_DocParams::Get(" AND IsActive='YES' AND DocType=?" . 
			dataReader::makeOrder(), array($_REQUEST["DocType"]));
	
	$res = $temp->fetchAll();
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function saveDocParam() {
	
	$obj = new DMS_DocParams();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST['record']);
	
	if($obj->ParamType == "currencyfield")
		$obj->KeyTitle = "amount";
	
	if ($obj->ParamID > 0) 
		$obj->Edit();
	 else 
		$obj->Add();

	echo Response::createObjectiveResponse(true, '');
	die();
}

function deleteDocParam() {

	$obj = new DMS_DocParams($_POST['ParamID']);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, '');
	die();
}

//.................................................

function selectPlaces(){
	
	$list = PdoDataAccess::runquery("select * from BaseInfo where typeID=87 AND IsActive='YES'");
	echo dataReader::getJsonData($list, count($list), $_GET["callback"]);
	die();
}

//............................................

function GetEvents(){
	
	$temp = DMS_PackageEvents::Get("AND PackageID=?", array($_REQUEST["PackageID"]));
	//print_r(ExceptionHandler::PopAllExceptions());
	$res = $temp->fetchAll();
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveEvents(){
	
	$obj = new DMS_PackageEvents();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(empty($obj->FollowUpPersonID))
		$obj->FollowUpPersonID = $_SESSION["USER"]["PersonID"];
	
	if(empty($obj->EventID))
	{
		$obj->RegPersonID = $_SESSION["USER"]["PersonID"];
		$result = $obj->Add();
	}
	else
		$result = $obj->Edit();
	
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function DeleteEvents(){
	
	$obj = new DMS_PackageEvents();
	$obj->EventID = $_POST["EventID"];
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();	
}

function CreateZip(){

	$where = "";
	$param = array();
	
	if(!empty($_REQUEST["ObjectType"]))
	{
		$where .= " AND ObjectType=:st";
		$param[":st"] = $_REQUEST["ObjectType"];
	}
	if(!empty($_REQUEST["ObjectID"]))
	{
		$where .= " AND ObjectID=:oid";
		$param[":oid"] = $_REQUEST["ObjectID"];
	}
	if(!empty($_REQUEST["ObjectID2"]))
	{
		$where .= " AND ObjectID2=:oid2";
		$param[":oid2"] = $_REQUEST["ObjectID2"];
	}
	$temp = PdoDataAccess::runquery("
		select * from DMS_DocFiles join DMS_documents d	using(DocumentID)
			where 1=1 " . $where, $param);
	
	$zip = new ZipArchive();
	$filename = DOCUMENT_ROOT . "/storage/files.zip";
	if ($zip->open($filename, ZipArchive::OVERWRITE)!==TRUE) {
		Response::createObjectiveResponse(false, "can not create zip file");
		die();
	}
	$index = 1;
	foreach($temp as $row)
	{
		$FileContent = $row["FileContent"] . 
			file_get_contents(DOCUMENT_ROOT . "/storage/documents/" . $row["RowID"] . "." . $row["FileType"]);
		
		$zip->addFromString("file". $index++ . "." . $row["FileType"], $FileContent);
	}
	$zip->close();
	Response::createObjectiveResponse(true, "");
	die();
}

function SearchInternalDocuments(){
	ini_set("display_errors", "On");
	$where = "";
	$param = array();
	
	if(!empty($_GET["query"]))
	{
		$where .= " AND ( DocDesc like :q or InfoDesc like :q)";
		$param[":q"] = "%" . $_GET["query"] . "%";
	}
	
	$temp = PdoDataAccess::runquery_fetchMode("select DocumentID,DocDesc,InfoDesc groupDesc from DMS_documents join BaseInfo on(InfoID=DocType and Typeid=8)
			where param1=17 ". $where, $param);
	
	$res = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
	die();
}


?>
