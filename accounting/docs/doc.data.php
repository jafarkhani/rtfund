<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../header.inc.php';
require_once 'doc.class.php';
require_once 'import.data.php';
require_once '../baseinfo/baseinfo.class.php';
require_once inc_dataReader;
require_once inc_response;

$task = empty($_REQUEST["task"]) ? "" : $_REQUEST["task"];
switch($task)
{
	case "selectDocs":
	case "saveDoc":
	case "removeDoc":
	case "confirm":
	case "archive":
	case "regResid":
	case "CopyDoc":
	case "GetLastLocalNo":
	case "GetSearchCount":
	case "GroupStartFlow":
	case "CreatePayCheque":
		
	case "selectDocItems":
	case "saveDocItem":
	case "removeDocItem":
	case "SelectAccounts":
	case "GetTafsilis":
	case "selectCheques":
	case "saveChecks":	
	case "removeChecks":
	case "RegisterCheck":
	case "UpdateChecks":
		
	case "RegisterEndDoc":
	case "RegisterStartDoc":
	case "RegisterCloseDoc":
		
	case "ComputeDoc":
	case "GetAccountSummary":
	case "GetAccountFlow":
	case "RegisterInOutDoc":
	case "GetSubjects":
	case "GetAllCostBlocks":
	case "SaveCostBlock":
	case "DeleteCostBlock":
		
	case "selectCostParams":
	case "selectAllParams":
	case "selectParamItems":
		
		$task();
		die();
}

function selectDocs() {
	$where = " sd.CycleID=:cid ";
	$whereParam = array(":cid" => $_SESSION["accounting"]["CycleID"]);
	
	if (isset($_GET["fields"]) && !empty($_GET["query"])) {
		switch ($_GET["fields"]) {
			case "DocID":
				$where .= " AND sd.DocID = :tl";
				$whereParam[":tl"] = $_GET["query"];
				break;
			case "DocDate":
				$where .= " AND sd.DocDate = :tl";
				$whereParam[":tl"] = DateModules::shamsi_to_miladi($_GET["query"]);
				break;
			case "description":
				$where .= " AND sd.description like :d";
				$whereParam[":d"] = "%" . $_GET["query"] . "%";
				break;
			/*case "storeDocID":
				$where .= " AND sd.storeDocID = :d";
				$whereParam[":d"] = $_GET["query"];
				break;*/
		}
	}
	$where .= dataReader::makeOrder();
	$temp = ACC_docs::GetAll($where, $whereParam);
	//echo PdoDataAccess::GetLatestQueryString();
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
}

function saveDoc() {
	
	$obj = new ACC_docs();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->CycleID = $_SESSION["accounting"]["CycleID"];

	if ($obj->DocID == "") {
		$obj->RegDate = DateModules::Now();
		$obj->regPersonID = $_SESSION['USER']["PersonID"];
		$obj->DocDate = empty($obj->DocDate) ? PDONOW : $obj->DocDate;
		$return = $obj->Add();
		ACC_DocHistory::AddLog($obj->DocID, "ایجاد سند");
	} 
	else {		
		$return = $obj->Edit();
		ACC_DocHistory::AddLog($obj->DocID, "ویرایش اطلاعات سند");
	}

	if (!$return) {
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}

	echo Response::createObjectiveResponse(true, "");
	die();
}

function removeDoc() {
	
	$result = ACC_docs::Remove($_REQUEST["DocID"]);
	echo Response::createObjectiveResponse($result, ExceptionHandler::GetExceptionsToString());
	die();
}

function confirm() {

	if(ACC_cycles::IsClosed())
	{
		echo Response::createObjectiveResponse(false, "دوره مالی جاری بسته شده است و قادر به اعمال تغییرات نمی باشید");
		die();	
	}
	
	$status = "CONFIRM";
	if(isset($_POST["undo"]) && $_POST["undo"] == "true")
		$status = "RAW";
	
	$obj = new ACC_docs();
	$obj->DocID = $_POST["DocID"];
	$obj->DocStatus = $status;
	$obj->Edit();
	
	ACC_DocHistory::AddLog($obj->DocID, $status == "CONFIRM" ? "تایید سند" : "برگشت از تایید سند");
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function regResid() {
	$pdo = PdoDataAccess::getPdoObject();
	/* @var $pdo PDO */
	$pdo->beginTransaction();

	$obj = new ACC_docs();
	$obj->CycleID = $_SESSION["accounting"]["CycleID"];
	$obj->storeID = $_POST["storeID"];
	$obj->doc_type = 20;
	$obj->reg_date = PDONOW;
	$obj->doc_date = PDONOW;
	$obj->ref_DocID = $_POST["DocID"];

	if (isset($_POST["desc_storeID"]))
		$obj->desc_storeID = $_POST["desc_storeID"];

	$result = $obj->Add($pdo);
	if (!$result) {
		$pdo->rollBack();
		echo "false";
		die();
	}

	$query = "insert into store_doc_items(CycleID,DocID,itemID,itemCount,buyPrice,
			coverPrice,expireDate,salePrice,tax)
		select " . $obj->CycleID . "," . $obj->DocID . ",itemID,itemCount,buyPrice,
			coverPrice,expireDate,salePrice,tax
		from store_doc_items where DocID=?";
	$result = PdoDataAccess::runquery($query, array($_POST["DocID"]), $pdo);
	if ($result === false) {
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		echo "false";
		die();
	}

	$obj2 = new ACC_docs();
	$obj2->DocID = $_POST["DocID"];
	$obj2->ref_DocID = $obj->DocID;
	$result = $obj2->Edit(false, true, $pdo);
	if (!$result) {
		$pdo->rollBack();
		echo "false";
		die();
	}
	$pdo->commit();
	echo "true";
	die();
}

function CopyDoc(){
	
	if(ACC_cycles::IsClosed())
	{
		echo Response::createObjectiveResponse(false, "دوره مالی جاری بسته شده است و قادر به اعمال تغییرات نمی باشید");
		die();	
	}
	
	$RefDocID = $_POST["DocID"];
	$RefDocObj = new ACC_docs($RefDocID);
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$hobj = new ACC_docs();
	$hobj->CycleID = $_SESSION["accounting"]["CycleID"];
	$hobj->BranchID = $RefDocObj->BranchID;
	$hobj->RegDate = DateModules::Now();
	$hobj->regPersonID = $_SESSION['USER']["PersonID"];
	$hobj->DocDate = PDONOW;
	$hobj->Add($pdo);
	ACC_DocHistory::AddLog($hobj->DocID, "ایجاد سند");
	
	$dt = PdoDataAccess::runquery("select * from ACC_DocItems where DocID=?", array($RefDocID));
	foreach($dt as $row)
	{
		$obj = new ACC_DocItems();
		PdoDataAccess::FillObjectByArray($obj, $row);
		
		if($_POST["mode"] == "2")
		{
			$temp = $obj->DebtorAmount;
			$obj->DebtorAmount = $obj->CreditorAmount;
			$obj->CreditorAmount = $temp;
		}
		
		$obj->DocID = $hobj->DocID;
		unset($obj->ItemID);
		$obj->locked = "NO";
		unset($obj->SourceType);	
		unset($obj->SourceID1);
		unset($obj->SourceID2);
		unset($obj->SourceID3);
		$obj->Add($pdo);
	}
	
	$result = ExceptionHandler::GetExceptionCount() == 0;
	if($result)
		$pdo->commit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function GetLastLocalNo(){
	
	echo ACC_docs::GetLastLocalNo();
	die();
	
}

function GetSearchCount() {
    
	$query = "select count(*) as CurrentPage 
		from ACC_docs dh
		where CycleID=:c AND LocalNo < :n ";
	
    $param = array(":c" => $_SESSION["accounting"]["CycleID"], 
					":n" => $_REQUEST['Number']);
	
	$dt = PdoDataAccess::runquery($query, $param);
    echo Response::createObjectiveResponse(true, $dt[0]['CurrentPage']);
    die();
}

function GroupStartFlow(){
	
	if(ACC_cycles::IsClosed())
	{
		echo Response::createObjectiveResponse(false, "دوره مالی جاری بسته شده است و قادر به اعمال تغییرات نمی باشید");
		die();	
	}
	
	$where = "";
	$param = array(
		":c" => $_SESSION["accounting"]["CycleID"]
	);
	if(!empty($_POST["FromDate"]))
	{
		$where .= " AND DocDate >= :fd";
		$param[":fd"] = DateModules::shamsi_to_miladi($_POST["FromDate"], "-");
	}
	if(!empty($_POST["ToDate"]))
	{
		$where .= " AND DocDate <= :td";
		$param[":td"] = DateModules::shamsi_to_miladi($_POST["ToDate"], "-");
	}
	if(!empty($_POST["FromNo"]))
	{
		$where .= " AND LocalNo >= :fl";
		$param[":fl"] = $_POST["FromNo"];
	}
	if(!empty($_POST["ToNo"]))
	{
		$where .= " AND LocalNo <= :tl";
		$param[":tl"] = $_POST["ToNo"];
	}
	
	$dt = PdoDataAccess::runquery("select DocID from ACC_docs d
		join ACC_DocItems di using(DocID)
		where StatusID=".ACC_STEPID_RAW." AND CycleID=:c " . $where . "
		group by d.DocID
		having sum(DebtorAmount)=sum(CreditorAmount) ", $param);
	if(count($dt) == 0)
	{
		echo Response::createObjectiveResponse(false, "هیچ سندی یافت نشد");
		die();
	}
	
	require_once '../../office/workflow/wfm.class.php';
	foreach($dt as $row)
	{
		$FlowID = FLOWID_ACCDOC;
		$ObjectID = $row["DocID"];
		WFM_FlowRows::StartFlow($FlowID, $ObjectID);
	}
	
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

function CreatePayCheque(){
	
	$DocID = $_POST["DocID"]*1;
	
	PdoDataAccess::runquery("insert into ACC_DocCheques(DocID,AccountID,CheckDate,amount,CheckStatus) 
		select DocID,ObjectID,".PDONOW.",sum(CreditorAmount),3001 
		from ACC_DocItems di 
			join ACC_CostCodes cc using(CostID)
			left join ACC_tafsilis t on(di.TafsiliID2=t.TafsiliID)  
			left join dates on(jdate=di.param2) 
		where DocID=? AND IsCheque='YES' group by cc.CostID", array($DocID));
	
	if(PdoDataAccess::AffectedRows() == 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false , "کد حسابی که قابلیت صدور چک داشته باشد یافت نشد");
		die();	
	}
	echo Response::createObjectiveResponse(true , "");
	die();	
}

//............................

function selectDocItems() {
	
	ini_set("display_errors", "On");
	
	$where = "DocID=:did";
	$whereParam = array(":did" => $_REQUEST["DocID"]);

	if (isset($_GET["fields"]) && !empty($_GET["query"])) {
		switch ($_GET["fields"]) {
			case "TafsiliID":
				$where .= " AND t.tafsiliDesc like :tl";
				$whereParam[":tl"] = "%" . $_GET["query"] . "%";
				break;
			case "moinID":
				$where .= " AND moinTitle like :tl";
				$whereParam[":tl"] = "%" . $_GET["query"] . "%";
				break;
			case "TafsiliID2":
				$where .= " AND t2.tafsiliDesc like :tl";
				$whereParam[":tl"] = "%" . $_GET["query"] . "%";
				break;
			case "kolID":
				$where .= " AND kolTitle like :tl";
				$whereParam[":tl"] = "%" . $_GET["query"] . "%";
				break;
			case "DebtorAmount":
				$where .= " AND DebtorAmount = :tl";
				$whereParam[":tl"] = $_GET["query"];
				break;
			case "CreditorAmount":
				$where .= " AND CreditorAmount = :tl";
				$whereParam[":tl"] = $_GET["query"];
				break;
			case "CostDesc":
				$where .= " AND (cc.CostCode like :cd or 
					concat_ws('-',b1.blockDesc,b2.BlockDesc,b3.BlockDesc) like :cd)";
				$whereParam[":cd"] = "%" . $_GET["query"] . "%";
				break;
			
			default:
				
				$field = $_GET["fields"];
				$field = $field == "param1" ? "si.param1" : $field;
				$field = $field == "param2" ? "si.param2" : $field;
				$field = $field == "param3" ? "si.param3" : $field;
				
				$where .= " AND " . $field . " like :f";
				$whereParam[":f"] = "%" . $_GET["query"] . "%";
		}
	}
	$where .= dataReader::makeOrder();
	
	//$no = ACC_DocItems::GetAllCount($where, $whereParam);
	//$temp = ACC_DocItems::GetAll($where . " limit " . $_REQUEST["start"] . "," . $_REQUEST["limit"], $whereParam);
	//$temp = $temp->fetchAll();
	
	$temp = ACC_DocItems::GetAll($where, $whereParam);
	print_r(ExceptionHandler::PopAllExceptions());	
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_REQUEST["start"], $_REQUEST["limit"]);

	
	$dt = PdoDataAccess::runquery("
		select sum(DebtorAmount) bd,sum(CreditorAmount) bs
		from ACC_DocItems
		where DocID=? 
		group by DocID", array($_REQUEST["DocID"]));
	$bdSum = count($dt) != 0 ? $dt[0]["bd"] : 0;
	$bsSum = count($dt) != 0 ? $dt[0]["bs"] : 0;
	//..........................................................................	
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"], $bdSum . "," . $bsSum);
	die();
}

function saveDocItem() {

	$obj = new ACC_DocItems();
	if(isset($_POST["record"]))
	{
		PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
		$return = $obj->Edit();
	}
	else
	{
		PdoDataAccess::FillObjectByArray($obj, $_POST);

		if($obj->TafsiliID == "")
			$obj->TafsiliID = PDONULL;
		if($obj->TafsiliID2 == "")
			$obj->TafsiliID2 = PDONULL;
		if($obj->TafsiliID3 == "")
			$obj->TafsiliID3 = PDONULL;

		$costObj = new ACC_CostCodes($obj->CostID);
		$obj->TafsiliType = $costObj->TafsiliType1;
		$obj->TafsiliType2 = $costObj->TafsiliType2;
		$obj->TafsiliType3 = $costObj->TafsiliType3;

		if ($obj->ItemID == "")
		{
			$return = $obj->Add();
			ACC_DocHistory::AddLog($obj->DocID, "ایجاد ردیف سند");
		}
		else
		{
			$return = $obj->Edit();
		}
	}
	//print_r(ExceptionHandler::PopAllExceptions());
	if (!$return) {
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	echo Response::createObjectiveResponse(true, "");
	die();
}

function removeDocItem() {
	
	$obj = new ACC_DocItems($_POST["ItemID"]);
	$result = $obj->Remove();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//............................

function SelectAccounts(){
	
	$temp = PdoDataAccess::runquery("select * from ACC_accounts ");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function GetTafsilis(){
	
	$DocID = $_GET["DocID"];
	$temp = PdoDataAccess::runquery("
		select t.* from ACC_DocItems d
		join ACC_tafsilis t on(t.TafsiliID=d.TafsiliID or t.TafsiliID=d.TafsiliID2)
		where d.DociD=:d and (d.TafsiliType in(".TAFSILITYPE_PERSON.") or d.TafsiliType2 in(".TAFSILITYPE_PERSON."))
		group by t.TafsiliID
			", array(":d" => $DocID));
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function selectCheques() {
	$where = "1=1";
	$whereParam = array();
	if (isset($_REQUEST["DocID"])) {
		$where .= " AND DocID=:d";
		$whereParam[":d"] = $_REQUEST["DocID"];
	}

	if (isset($_GET["fields"]) && !empty($_GET["query"])) {
		switch ($_GET["fields"]) {
			case "CheckNo":
				$where .= " AND CheckNo = :q";
				$whereParam[":q"] = $_GET["query"];
				break;
			case "reciever":
				$where .= " AND reciever like :q";
				$whereParam[":q"] = '%' . $_GET["query"] . '%';
				break;
			case "DocID":
				$where .= " AND DocID = :q";
				$whereParam[":q"] = $_GET["query"];
				break;
			case "accountTitle":
				$where .= " AND accountTitle like :q";
				$whereParam[":q"] = '%' . $_GET["query"] . '%';
				break;
			case "amount":
				$where .= " AND amount = :q";
				$whereParam[":q"] = $_GET["query"];
				break;
			case "CheckStatus":
				$where .= " AND b.title like :q";
				$whereParam[":q"] = '%' . $_GET["query"] . '%';
				break;
		}
	}

	if (!empty($_GET["date"])) {
		$where .= " AND CheckDate = :q";
		$whereParam[":q"] = DateModules::shamsi_to_miladi($_GET["date"]);
	}

	$where .= dataReader::makeOrder();

	$temp = ACC_DocCheques::GetAll($where, $whereParam);
	print_r(ExceptionHandler::PopAllExceptions());
	$no = count($temp);
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function saveChecks() {
	$obj = new ACC_DocCheques();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	//..........................................
	$query = "select * from ACC_DocCheques where CheckNo=? AND AccountID=?";
	$query .=!empty($obj->DocChequeID) ? " AND DocChequeID<>" . $obj->DocChequeID : "";

	$dt = PdoDataAccess::runquery($query, array($obj->CheckNo, $obj->AccountID));
	if (count($dt) > 0) {
		echo Response::createObjectiveResponse(false, "duplicate");
		die();
	}
	//..........................................
	if (empty($obj->DocChequeID))
	{
		unset($obj->CheckStatus);
		$return = $obj->Add();
	}
	else
		$return = $obj->Edit();

	if (!$return) {
		echo "false";
		print_r(ExceptionHandler::PopAllExceptions());
		die();
	}
	echo Response::createObjectiveResponse(true, "");
	die();
}

function removeChecks() {
	
	$result = ACC_DocCheques::Remove($_POST["DocChequeID"]);
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function RegisterCheck() {
	
	PdoDataAccess::runquery("update ACC_DocCheques set CheckStatus=1 where DocChequeID=?", array($_POST["DocChequeID"]));
	echo Response::createObjectiveResponse(PdoDataAccess::AffectedRows() == 0 ? false : true, "");
	die();
}

//............................

function UpdateChecks(){
	
	$AccountID = $_POST["AccountID"];
	$result = "";
	
	require_once("phpExcelReader.php");
	
	$data = new Spreadsheet_Excel_Reader();
    $data->setOutputEncoding('utf-8');
    $data->setRowColOffset(0);
	$data->read($_FILES["attach"]["tmp_name"]);
	
	for ($i = 0; $i < $data->sheets[0]['numRows']; $i++) 
	{
		
		$CheckNo = "";		
		switch($AccountID)
		{
			case "1": // ملی
				if(trim($data->sheets[0]['cells'][$i][3]) == "چك")
				{
					$CheckNo = $data->sheets[0]['cells'][$i][4];
					$CheckNo = substr($CheckNo, strlen($CheckNo) - 6);
				}
				break;
			case "5": //پاسارگاد
				if(strpos($data->sheets[0]['cells'][$i][8], "وصول چک") !== false)
				{
					$CheckNo = $data->sheets[0]['cells'][$i][7];
					$CheckNo = substr($CheckNo, strlen($CheckNo) - 6);
				}
		}
		if($CheckNo == "")
			continue;
	
		//............... add debtor rows to doc ...........................
		if($_POST["DocID"] != "")
		{
			$dt = PdoDataAccess::runquery("select * from ACC_DocCheques 
				where CheckStatus in(1,2) AND AccountID=? AND CheckNo=?", array($AccountID,$CheckNo));
			if(count($dt) > 0)
			{
				$obj = new ACC_DocItems();
				$obj->DocID = $_POST["DocID"];
				$obj->kolID = 42; // اسناد پرداختنی
				$obj->moinID = 1; // کوتاه مدت
				$obj->TafsiliID = $dt[0]["TafsiliID"];	
				$obj->DebtorAmount = $dt[0]["amount"];
				$obj->CreditorAmount = 0;
				$obj->Add();
			}
		}		
		//..................................................................

		PdoDataAccess::runquery("update ACC_DocCheques set CheckStatus=3 where AccountID=? AND CheckNo=?", array($AccountID,$CheckNo));
		if(PdoDataAccess::AffectedRows() > 0)
			$result .= "شماره چک : " . $CheckNo . " [ تعداد ردیف به روز شده : " . PdoDataAccess::AffectedRows() . "]<br>";
			
		
	}
	echo Response::createObjectiveResponse(true, $result == "" ? "هیچ چکی به روز نگردید" : $result);
	die();

	
	
	
	
	
	
	while (($row = fgetcsv($handle)) !== false) {
		
		$row[3] = iconv(mb_detect_encoding($row[3], mb_detect_order(), true), "UTF-8", $row[3]);
		echo $row[3] . "-------";
		if(trim($row[3]) == "چك")
		{
			echo $row[4] . "*********";
			$CheckNo = substr($row[4], 4);
			PdoDataAccess::runquery("update ACC_DocCheques set CheckStatus=2 where AccountID=? AND CheckNo=?", array($AccountID,(int)$CheckNo));

			if(PdoDataAccess::AffectedRows() > 0)
				$result .= "شماره چک : " . $CheckNo . " [ تعداد ردیف به روز شده : " . PdoDataAccess::AffectedRows() . "]<br>";
		}
	}
	fclose($handle);
	echo Response::createObjectiveResponse(true, $result == "" ? "هیچ چکی به روز نگردید" : $result);
	die();
}

//..........................

function RegisterCloseDoc(){
	
	$BranchID = $_REQUEST["BranchID"];
	
	$dt = PdoDataAccess::runquery("select * from ACC_docs where DocType=" . DOCTYPE_CLOSECYCLE . " 
		AND BranchID=? AND CycleID=?", 
			array($BranchID, $_SESSION["accounting"]["CycleID"]));
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند بستن حساب های موقت در این دوره قبلا صادر شده است");
		die();
	}
	
	$LocalNo = $_POST["LocalNo"];
	if($LocalNo != "")
	{
		$dt = PdoDataAccess::runquery("select * from ACC_docs 
			where BranchID=? AND CycleID=? AND LocalNo=?" , 

			array($BranchID, 
				$_SESSION["accounting"]["CycleID"], 
				$LocalNo));

		if(count($dt) > 0)
		{
			echo Response::createObjectiveResponse(false, "شماره سند وارد شده موجود می باشد");
			die();
		}
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	//---------------- account header doc --------------------
	$obj = new ACC_docs();
	$obj->LocalNo = $LocalNo;
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $_SESSION["accounting"]["CycleID"];
	$obj->BranchID = $BranchID;
	$obj->description = "سند بستن حساب های موقت";
	$obj->DocType = DOCTYPE_CLOSECYCLE;
	$result = $obj->Add($pdo);

	if (!$result) {
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
		
	PdoDataAccess::runquery("
		insert into ACC_DocItems(DocID,CostID,DebtorAmount,CreditorAmount,locked)
		select $obj->DocID,MainCostID,
			if( sum(DebtorAmount-CreditorAmount)>0, sum(DebtorAmount-CreditorAmount), 0 ),
			if( sum(CreditorAmount-DebtorAmount)>0, sum(CreditorAmount-DebtorAmount), 0 ),
			1
		from ACC_DocItems i
		join ACC_CostCodes c using(CostID)
		join ACC_blocks b1 on(level1=BlockID AND MainCostID>0)
		join ACC_docs using(DocID)
		where CycleID=" . $_SESSION["accounting"]["CycleID"] . "
			AND BranchID = ?
			
		group by MainCostID
		having sum(CreditorAmount-DebtorAmount)<>0
	", array($BranchID), $pdo);
	
	PdoDataAccess::runquery("
		insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			DebtorAmount,CreditorAmount,locked)
		select $obj->DocID,i.CostID,i.TafsiliType,i.TafsiliID,i.TafsiliType2,i.TafsiliID2,
			i.TafsiliType3,i.TafsiliID3,
			if( sum(CreditorAmount-DebtorAmount)>0, sum(CreditorAmount-DebtorAmount), 0 ),
			if( sum(DebtorAmount-CreditorAmount)>0, sum(DebtorAmount-CreditorAmount), 0 ),
			1
		from ACC_DocItems i
		join ACC_CostCodes c using(CostID)
		join ACC_blocks b1 on(level1=BlockID AND MainCostID>0)
		join ACC_docs using(DocID)
		where CycleID=" . $_SESSION["accounting"]["CycleID"] . "
			AND BranchID = ?
			
		group by i.CostID,i.TafsiliID,i.TafsiliID2	
		having sum(CreditorAmount-DebtorAmount)<>0
	", array($BranchID), $pdo);
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, "خطا در اضافه ردیف ها");
		die();
	}
	
	if(PdoDataAccess::AffectedRows($pdo) == 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "ردیفی برای صدور سند بستن حسابها یافت نشد");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function RegisterEndDoc(){
	
	$BranchID = empty($_REQUEST["BranchID"]) ? BRANCH_UM : $_REQUEST["BranchID"];
	
	$dt = PdoDataAccess::runquery("select * from ACC_docs where DocType=" . DOCTYPE_ENDCYCLE . " 
		AND BranchID=? AND CycleID=?", 
			array($BranchID, $_SESSION["accounting"]["CycleID"]));
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند اختتامیه در این دوره قبلا صادر شده است");
		die();
	}
	
	$LocalNo = $_POST["LocalNo"];
	if($LocalNo != "")
	{
		$dt = PdoDataAccess::runquery("select * from ACC_docs 
			where BranchID=? AND CycleID=? AND LocalNo=?" , 

			array($BranchID, 
				$_SESSION["accounting"]["CycleID"], 
				$LocalNo));

		if(count($dt) > 0)
		{
			echo Response::createObjectiveResponse(false, "شماره سند وارد شده موجود می باشد");
			die();
		}
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	//---------------- account header doc --------------------
	$obj = new ACC_docs();
	$obj->LocalNo = $LocalNo;
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $_SESSION["accounting"]["CycleID"];
	$obj->BranchID = $BranchID;
	$obj->description = "سند اختتامیه";
	$obj->DocType = DOCTYPE_ENDCYCLE;
	$result = $obj->Add($pdo);

	if (!$result) {
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	
	PdoDataAccess::runquery("
		insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			DebtorAmount,CreditorAmount,locked,
			param1,param2,param3)
		select $obj->DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			if( sum(CreditorAmount-DebtorAmount)>0, sum(CreditorAmount-DebtorAmount), 0 ),
			if( sum(DebtorAmount-CreditorAmount)>0, sum(DebtorAmount-CreditorAmount), 0 ),
			1,
			param1,param2,param3
		from ACC_DocItems i
		join ACC_docs using(DocID)
		where CycleID=" . $_SESSION["accounting"]["CycleID"] . "
			AND BranchID = ?
		group by CostID,TafsiliID,TafsiliID2,TafsiliID3,param1,param2,param3
		having sum(CreditorAmount-DebtorAmount)<>0
	", array($BranchID), $pdo);
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	if(PdoDataAccess::AffectedRows($pdo) == 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "ردیفی برای صدور سند اختتامیه یافت نشد");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function RegisterStartDoc(){
	
	$BranchID = $_REQUEST["BranchID"];
	
	$dt = PdoDataAccess::runquery("select * from ACC_docs where DocType=" . DOCTYPE_STARTCYCLE . "
		AND BranchID=? AND CycleID=?", 
			array($BranchID,$_SESSION["accounting"]["CycleID"]));
	if(count($dt) > 0)
	{
		echo Response::createObjectiveResponse(false, "سند افتتاحیه در این دوره قبلا صادر شده است");
		die();
	}
	
	$LocalNo = $_POST["LocalNo"];
	if($LocalNo != "")
	{
		$dt = PdoDataAccess::runquery("select * from ACC_docs 
			where BranchID=? AND CycleID=? AND LocalNo=?" , 

			array($BranchID, 
				$_SESSION["accounting"]["CycleID"], 
				$LocalNo));

		if(count($dt) > 0)
		{
			echo Response::createObjectiveResponse(false, "شماره سند وارد شده موجود می باشد");
			die();
		}
	}
	
	$dt = PdoDataAccess::runquery("select * from ACC_cycles where CycleID<" . 
			$_SESSION["accounting"]["CycleID"]);
	if(count($dt) == 0)
	{
		Response::createObjectiveResponse(false, "دوره ایی قبل این دوره برای صدور سند افتتاحیه موجود نمی باشد");
		die();
	}
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	//---------------- account header doc --------------------
	$obj = new ACC_docs();
	$obj->LocalNo = $LocalNo;
	$obj->RegDate = PDONOW;
	$obj->regPersonID = $_SESSION['USER']["PersonID"];
	$obj->DocDate = PDONOW;
	$obj->CycleID = $_SESSION["accounting"]["CycleID"];
	$obj->BranchID = $BranchID;
	$obj->description = "سند افتتاحیه";
	$obj->DocType = DOCTYPE_STARTCYCLE;
	$result = $obj->Add($pdo);

	if (!$result) {
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	PdoDataAccess::runquery("
		insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			DebtorAmount,CreditorAmount,locked)
		select $obj->DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			if( sum(DebtorAmount-CreditorAmount)>0, sum(DebtorAmount-CreditorAmount), 0 ),
			if( sum(CreditorAmount-DebtorAmount)>0, sum(CreditorAmount-DebtorAmount), 0 ),
			1
		from ACC_DocItems i
		join ACC_docs using(DocID)
		where DocType <> ".DOCTYPE_ENDCYCLE." 
			AND CycleID=" . ($_SESSION["accounting"]["CycleID"]-1) . "
			AND BranchID = ?
		group by CostID,TafsiliID,TafsiliID2	
		having sum(CreditorAmount-DebtorAmount)<>0
	", array($BranchID), $pdo);
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	if(PdoDataAccess::AffectedRows($pdo) == 0)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "ردیفی برای صدور سند افتتاحیه یافت نشد");
		die();
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function ComputeDoc(){
	
	require_once 'import.data.php';
	
	switch($_POST["ComputeType"])
	{
		case "ShareProfit":
			ComputeShareProfit();
			break;
	}
}

//.................................

function GetAccountSummary($ReturnMode = false, $where = "", $param = array()){
	
	$param[":c"] = $_SESSION["accounting"]["CycleID"];
	
	if(!empty($_GET["fields"]) && !empty($_GET["query"]))
	{
		$where .= " AND " . $_GET["fields"] . " like :f";
		$param[":f"] = "%" . $_GET["query"] . "%";
	}	
	
	$temp = PdoDataAccess::runquery_fetchMode("
		select t.TafsiliID,t.TafsiliDesc, 
			ifnull(pasandaz.amount,0) pasandaz 
			/*,ifnull(kootah.amount,0) kootah,
			ifnull(boland.amount,0) boland,
			ifnull(jari.amount,0) jari */
			
		from ACC_tafsilis t 
			left join BSC_persons p on(t.TafsiliType=".TAFSILITYPE_PERSON." AND t.ObjectID=p.PersonID)
			
			left join (select TafsiliID2,sum(CreditorAmount-DebtorAmount) amount
						from ACC_DocItems join ACC_docs using(DocID)
						where CycleID=:c AND CostID=".COSTID_saving."
						group by TafsiliID2
			)pasandaz on(pasandaz.TafsiliID2=t.TafsiliID)
			/*left join (select TafsiliID,sum(CreditorAmount-DebtorAmount) amount
						from ACC_DocItems join ACC_docs using(DocID)
						where CycleID=:c AND CostID=".COSTID_ShortDeposite."
						group by TafsiliID
			)kootah on(kootah.TafsiliID=t.TafsiliID)
			left join (select TafsiliID,sum(CreditorAmount-DebtorAmount) amount
						from ACC_DocItems join ACC_docs using(DocID)
						where CycleID=:c AND CostID=".COSTID_LongDeposite."
						group by TafsiliID
			)boland on(boland.TafsiliID=t.TafsiliID)
			left join (select TafsiliID,sum(CreditorAmount-DebtorAmount) amount
						from ACC_DocItems join ACC_docs using(DocID)
						where CycleID=:c AND CostID=".COSTID_current."
						group by TafsiliID
			)jari on(jari.TafsiliID=t.TafsiliID)*/
		where TafsiliType=" . TAFSILITYPE_PERSON . $where . dataReader::makeOrder(), $param);
	
	if($ReturnMode)
		return $temp;
	//echo PdoDataAccess::GetLatestQueryString();
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($temp, $no, $_GET ["callback"]);
	die();
	
}

function GetAccountFlow() {
	
	$CostID = $_REQUEST["BaseCostID"];
	$TafsiliID = $_REQUEST["TafsiliID"];
	
	$query = "select d.*,di.*
		from ACC_DocItems di
			join ACC_docs d using(DocID)
		where d.CycleID=:cycle
			AND di.CostID=:cost AND di.TafsiliID2=:tid " . dataReader::makeOrder();
	
	$param = array(
		":cycle" => $_SESSION["accounting"]["CycleID"],
		":cost" => $CostID,
		":tid" => $TafsiliID
	);
	
	$temp = PdoDataAccess::runquery_fetchMode($query, $param);
	$no = $temp->rowCount();
	//echo PdoDataAccess::GetLatestQueryString();
	//------------------------------------------------
	$BlockedAmount = ACC_CostBlocks::GetBlockAmount($CostID, TAFSILITYPE_PERSON, $TafsiliID);
	//------------------------------------------------
	echo dataReader::getJsonData($temp->fetchAll(), $no, $_GET ["callback"],$BlockedAmount);
	die();
}

function RegisterInOutDoc() {
	
	$BaseCostID = $_REQUEST["BaseCostID"];
	$BaseTafsiliID = $_REQUEST["BaseTafsiliID"];
	$mode = $_POST["mode"]*1;
	$description = $_POST["description"];
	
	$CostID = $_POST["CostID"];
	$CostObj = new ACC_CostCodes($_POST["CostID"]);
	
	$result = RegisterInOutAccountDoc($_POST["amount"], $mode, $description, 
			$BaseCostID, "","",TAFSILITYPE_PERSON, $BaseTafsiliID, 
			$CostID, $CostObj->TafsiliType1, $_POST["TafsiliID"], $CostObj->TafsiliType2, $_POST["TafsiliID2"]);
	
	echo Response::createObjectiveResponse($result,  ExceptionHandler::GetExceptionsToString());
	die();
}

function GetSubjects(){
	
	$dt = PdoDataAccess::runquery("select * from BaseInfo where typeID=73 AND IsActive='YES'");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

//.................................

function GetAllCostBlocks(){
	
	$temp = ACC_CostBlocks::Get();
	$dt = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($dt, $temp->rowCount(), $_GET["callback"]);
	die();
}

function SaveCostBlock(){
	
	$obj = new ACC_CostBlocks();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if(empty($obj->CostBlockID))
	{
		$obj->RegDate = PDONOW;
		$obj->RegPersonID = $_SESSION['USER']["PersonID"];
		$result = $obj->Add();
	}
	else
		$result = $obj->Edit ();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteCostBlock(){
	
	$obj = new ACC_CostBlocks($_POST["CostBlockID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//...................................

function selectCostParams(){
	
	$dt = PdoDataAccess::runquery_fetchMode("select p.* from ACC_CostCodeParams p,ACC_CostCodes c
			where c.CostID=? AND p.ParamID in(c.param1,c.param2,c.param3) ",
			array($_GET["CostID"]));
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function selectAllParams(){
	
	$dt = PdoDataAccess::runquery_fetchMode("select * from ACC_CostCodeParams ");
	echo dataReader::getJsonData($dt->fetchAll(), $dt->rowCount(), $_GET["callback"]);
	die();
}

function selectParamItems(){
	
	$dt = PdoDataAccess::runquery("select * from ACC_CostCodeParams where ParamID=?", 
			array($_GET["ParamID"]));
	if($dt[0]["SrcTable"] != "")
	{
		$params = array();
		$query = "select ".$dt[0]["SrcValueField"]." as id, "
			.$dt[0]["SrcDisplayField"] . " as title from " . $dt[0]["SrcTable"] . " where 1=1 "
			. ($dt[0]["SrcWhere"] != "" ? " AND " . $dt[0]["SrcWhere"] : "");
		if(!empty($_GET["query"]))
		{
			$query .= " AND concat(".$dt[0]["SrcValueField"].",".$dt[0]["SrcDisplayField"].") like :q ";
			$params[":q"] = "%" . $_GET["query"] . "%";
		}
		$dt = PdoDataAccess::runquery($query,$params);
	}
	else
	{
		$params = array(":p" => $_GET["ParamID"]);
		$query = "select ItemID as id,ParamValue as title"
				. " from ACC_CostCodeParamItems where ParamID=:p";
		if(!empty($_GET["query"]))
		{
			$query .= " AND ParamValue like :q ";
			$params[":q"] = "%" . $_GET["query"] . "%";
		}
		$dt = PdoDataAccess::runquery($query,$params);
	}
	print_r(ExceptionHandler::PopAllExceptions());
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}
?>
