<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.06
//---------------------------

require_once '../../header.inc.php';
require_once(inc_response);
require_once inc_dataReader;
require_once 'baseinfo.class.php';

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
switch ($task) {

	case "SelectBlocks":
	case "SaveBlockData":
	case "DeleteBlock":
	case "getLastID":
	case "SelectCostCode":
	case "SelectBlockableCostCode":
	case "SaveCostCode":
	case "DeleteCostCode":
	case "ActiveCostCode":
	case "SelectCostGroups":
	case "AddGroup":
	case "SelectTafsiliGroups":
	case "DeleteGroup":
	case "GetAllTafsilis":
	case "SaveTafsili":
	case "DeleteTafsili":
	case "GetBankData":
	case "SaveBankData":
	case "DeleteBank":
	case "SelectAccounts":
	case "SaveAccount":
	case "DeleteAccount":
	case "CopyCheque":
	case "SelectCheques":
	case "SaveCheque":
	case "DeleteCheque":
	case "EnableChequeBook":
	case "SelectChequeStatuses":
	case "SelectACCRoles":
	case "SelectRoles":
	case "SaveRole":
	case "DeleteRole":
	case "SelectCycles":
	case "SaveCycle":
	case "GetBanks":
	case "ReplaceCostCodes":
		
	
	case "selectParams":
	case "saveParam":
	case "DeleteParam":
	case "CopyParams":
		
	case "selectParamItems":
	case "saveParamItem":
	case "DeleteParamItem":
		
		$task();
};

function SelectBlocks() {

	if(!empty($_REQUEST["PreLevel"]))
	{
		$level = $_REQUEST['level'];
		$query = "select b$level.* from ACC_CostCodes 
			 join ACC_blocks b1 on(b1.BlockID=level1)
			left join ACC_blocks b2 on(b2.BlockID=level2)
			left join ACC_blocks b3 on(b3.BlockID=level3)
			left join ACC_blocks b4 on(b4.BlockID=level4)
		
		where b$level.BlockID is not null AND b" . ($level*1 - 1) . ".BlockID=:b";
		$param = array(":b" => $_REQUEST["PreLevel"]);
		
		if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
			$field = $_REQUEST['fields'];
			$query .= ' and ' . $field . ' like :' . $field;
			$param[':' . $field] = '%' . $_REQUEST['query'] . '%';
		}
		$query .= " group by b$level.BlockID";
		
		$list = PdoDataAccess::runquery_fetchMode($query, $param);
		$count = $list->rowCount();

		if (isset($_GET["start"]))
			$list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
		else  
			$list = $list->fetchAll();

		echo dataReader::getJsonData($list, $count, $_GET['callback']);
		die();
	}
	
	
    $where = "b.IsActive='YES' AND b.LevelID=:LevelID";
    $param = array();
    $param[':LevelID'] = $_REQUEST['level'];

    if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
        $where .= ' and b.' . $field . ' like :' . $field;
        $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
    }
	
	if(!isset($_REQUEST['fields']) && !empty($_REQUEST['query']))
	{
		$where .= " AND ( b.BlockDesc like :qu or b.BlockCode like :qu)";
		$param[':qu'] = '%' . $_REQUEST['query'] . '%';
	}
	
	if(!empty($_REQUEST['BlockID']))
	{
		$where .= " AND b.BlockID=:b";
		$param[':b'] = $_REQUEST['BlockID'];
	}
	
    $where .= " order by b.BlockCode*1";

    $list = ACC_blocks::GetAll($where, $param);
	print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->rowCount();

    if (isset($_GET["start"]) && !isset($_GET["All"]))
        $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    else
        $list = $list->fetchAll();

    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function SaveBlockData() {

    $block = new ACC_blocks();
    pdoDataAccess::FillObjectByJsonData($block, $_POST['record']);

	if($block->LevelID > 1)
		$block->BlockCode = str_pad($block->BlockCode, 2, '0', STR_PAD_LEFT);		
	
	if(!isset($block->MainCostID))
		$block->MainCostID = PDONULL;
	
    $newFlag = false;
    if ($block->BlockID == '') {
        $res = $block->AddBlock();
        $newFlag = true;
    }
    else
        $res = $block->EditBlock();

    if ($res) {
        PdoDataAccess::runquery("
		update ACC_CostCodes cc
			left join ACC_blocks cb1 on cb1.BlockID = cc.Level1 
			left join ACC_blocks cb2 on cb2.BlockID = cc.Level2 
			left join ACC_blocks cb3 on cb3.BlockID = cc.Level3 
			left join ACC_blocks cb4 on cb4.BlockID = cc.Level4 

		set cc.CostCode = concat_ws('',cb1.BlockCode,cb2.BlockCode,cb3.BlockCode,cb4.BlockCode)

		where level" . $block->LevelID . " = " . $block->BlockID);
    }

    Response::createObjectiveResponse($res, $block->GetExceptionCount() != 0 ? $block->popExceptionDescription() : $block->BlockID);
    die();
}

function DeleteBlock() {

    $res = ACC_blocks::RemoveBlock($_POST["BlockID"]);
    Response::createObjectiveResponse($res, '');
    die();
}

function getLastID() {

    $dt = PdoDataAccess::runquery("select ifnull(max(BlockCode*1),0)+1 from ACC_blocks 
			where IsActive='YES' AND levelID=?", array($_POST["levelID"]));
  
    if (count($dt) == 0) {
        echo Response::createObjectiveResponse(false, "");
        die();
    }
    if ($dt[0][0] < 10)
        $dt[0][0] = "0" . $dt[0][0];

    echo Response::createObjectiveResponse(true, $dt[0][0]);
    die();
}

//-------------------------------------------

function SelectCostCode() {

    $param = array();

    $where = isset($_REQUEST["All"]) ? "1=1" : "cc.IsActive='YES' ";

    if (!empty($_REQUEST['query'])) {
        if (isset($_REQUEST['fields'])) 
		{
			$field = $_REQUEST['fields'];
			$field = strpos($_REQUEST['fields'], "LevelTitle") !== false ? 
						str_replace("LevelTitle", "b", $_REQUEST['fields']) . ".BlockDesc" : $field;
			
			$field = $field == "TafsiliTypeDesc1" ? "bf1.InfoDesc"  : $field;
			$field = $field == "TafsiliTypeDesc2" ? "bf2.InfoDesc"  : $field;
			$field = $field == "TafsiliTypeDesc3" ? "bf3.InfoDesc"  : $field;
			
			$where .= " AND " . $field . " like :q";
            $param[":q"] = $_REQUEST['query'] . "%";
            
        } else {
            $where .= " AND ( concat_ws(' ',b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc) like :f4";
            $where .= " OR CostCode like :f5 )";
            $param[":f4"] = "%" . $_REQUEST['query'] . "%";
            $param[":f5"] = $_REQUEST['query'] . "%";
        }
    }
    if (!empty($_REQUEST['CostID'])) {
        $where.=" and cc.CostID=:CostID ";
        $param[':CostID'] = $_REQUEST['CostID'];
    }
    $where .= dataReader::makeOrder() == "" ? " order by CostCode" : dataReader::makeOrder();

    $list = ACC_CostCodes::SelectCost($where, $param);
    print_r(ExceptionHandler::PopAllExceptions());
    $count = $list->RowCount();
    $list = PdoDataAccess::fetchAll($list, $_GET["start"], $_GET["limit"]);
    
	/*for ($i = 0; $i < count($list); $i++) {
        $remainderRecord = CostCode::GetCostRemainder($_SESSION["ACCUSER"]['UnitID'], $_SESSION["ACCUSER"]['PeriodID'], $list[$i]['CostID']);
        $difference = $remainderRecord["amount"];
        if ($remainderRecord["essence"] == "CREDITOR")
            $difference = (-1) * $difference;
        if (empty($difference) || $difference == 0)
            $list[$i]['CostRemain'] = '0';
        else {

            if ($difference < 0) {
                $list[$i]['CostRemain'] = number_format($difference * -1);
                $list[$i]['SignRemain'] = 1;
            } else {
                $list[$i]['CostRemain'] = number_format($difference);
                $list[$i]['SignRemain'] = 0;
            }
        }
    }*/
    echo dataReader::getJsonData($list, $count, $_GET['callback']);
    die();
}

function SelectBlockableCostCode() {

    $where = "IsBlockable='YES'";
    $list = ACC_CostCodes::SelectCost($where);
    echo dataReader::getJsonData($list->fetchAll(), $list->RowCount(), $_GET['callback']);
    die();
}

function SaveCostCode() {

    $cc = new ACC_CostCodes();
    PdoDataAccess::FillObjectByArray($cc, $_POST);
	
	if(empty($_POST["IsBlockable"]))
		$cc->IsBlockable = "NO";
	if(empty($_POST["IsCheque"]))
		$cc->IsCheque = "NO";

    if ($cc->CostID == '')
    {
		$where = " cc.IsActive='YES' AND level1=?";
		$param = array($cc->level1);
		if($cc->level2*1 > 0)
		{
			$where .= " AND level2=?";
			$param[] = $cc->level2;
		}
		else
			$where .= " AND level2 is null";
		if($cc->level3*1 > 0)
		{
			$where .= " AND level3=?";
			$param[] = $cc->level3;
		}
		else
			$where .= " AND level3 is null";
		if($cc->level4*1 > 0)
		{
			$where .= " AND level4=?";
			$param[] = $cc->level4;
		}
		else
			$where .= " AND level4 is null";

		$dt = ACC_CostCodes::SelectCost($where, $param);

		if ($dt->rowCount() > 0) {
			$record = $dt->fetch();
			if($record["CostID"] != $cc->CostID)
			{
				Response::createObjectiveResponse(false, 'کد حساب تکراری است');
				die();
			}        
		}
		$res = $cc->InsertCost();
	}
    else
	{
		unset($cc->CostCode);
		unset($cc->level1);
		unset($cc->level2);
		unset($cc->level3);
		unset($cc->level4);
		$cc->param1 = empty($cc->param1) ? 0 : $cc->param1;
		$cc->param2 = empty($cc->param2) ? 0 : $cc->param2;
		$cc->param3 = empty($cc->param3) ? 0 : $cc->param3;
		$res = $cc->UpdateCost();
	}

    Response::createObjectiveResponse($res, $cc->popExceptionDescription());
    die();
}

function DeleteCostCode() {

    $cc = new ACC_CostCodes($_POST['CId']);
    $res = $cc->DeleteCost();
    Response::createObjectiveResponse($res, $cc->popExceptionDescription());

    die();
}

function ActiveCostCode(){
	
	$cc = new ACC_CostCodes();
	$cc->CostID = $_POST['CostID'];
    $res = $cc->ActiveCode();
    Response::createObjectiveResponse($res, $cc->GetExceptionsToString());

    die();
}

function SelectCostGroups(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=80 AND IsActive='YES'");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}
//-------------------------------------------

function AddGroup(){
	
	$InfoID = PdoDataAccess::GetLastID("BaseInfo", "InfoID", "TypeID=2");
	
	PdoDataAccess::runquery("insert into BaseInfo(TypeID,InfoID, InfoDesc) 
		values(2,?,?)", array($InfoID+1, $_POST["GroupDesc"]));
	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SelectTafsiliGroups(){
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=2 AND IsActive='YES'");
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

function DeleteGroup(){
	
	$dt = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=?",array($_POST["TafsiliType"]));
	if(count($dt)  > 0)
	{
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	
	PdoDataAccess::runquery("delete from BaseInfo where TypeID=2 AND InfoID=?",array($_POST["TafsiliType"]));
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetAllTafsilis() {
	
	if(empty($_GET["TafsiliType"]))
	{
		echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
		die();
	}
	$where = " t.IsActive='YES' AND t.TafsiliType=:g";
	$whereParam = array();
	$whereParam[":g"] = $_GET["TafsiliType"];
	
	$field = isset($_GET ["fields"]) ? $_GET ["fields"] : "concat(TafsiliCode,' ', TafsiliDesc)";
	if (isset($_GET ["query"]) && $_GET ["query"] != "") {
		$where .= " AND " . $field . " LIKE :qry ";
		$whereParam[":qry"] = "%" . $_GET["query"] . "%";
	}
	
	if(!empty($_REQUEST["Shareholder"]))
	{
		$where .= " AND p.IsShareholder='YES' ";
	}
	
	if(isset($_REQUEST["TafsiliID"]) && $_REQUEST["TafsiliID"] != "")
	{
		$where .= " AND TafsiliID=:t ";
		$whereParam[":t"] = $_REQUEST["TafsiliID"];
	}
	
	if($_GET["TafsiliType"] == TAFTYPE_ACCOUNTS && !empty($_REQUEST["ParentTafsili"]))
	{
		$dt = PdoDataAccess::runquery("select group_concat(AccountID) 
			from ACC_tafsilis t
			join ACC_accounts on(BankID=ObjectID)
			where TafsiliType=" . TAFTYPE_BANKS . " and t.tafsiliID=?", 
			array($_REQUEST["ParentTafsili"]));
		
		$where .= " and ObjectID in(" . ($dt[0][0] == "" ? "0" : $dt[0][0]) . " )";
	}

	$temp = ACC_tafsilis::SelectAll($where . dataReader::makeOrder(), $whereParam);
	
	//if($_SESSION["USER"]["UserName"] == "admin")
	//	echo PdoDataAccess::GetLatestQueryString ();
	
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);

	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function SaveTafsili() {

	$obj = new ACC_tafsilis();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);

	if (empty($obj->TafsiliID))
		$result = $obj->AddTafsili();
	else
		$result = $obj->EditTafsili();

	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeleteTafsili() {
	
	$TafsiliID = $_POST["TafsiliID"];
	$result = ACC_tafsilis::DeleteTafsili($TafsiliID);
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

//---------------------------------------------

function GetBankData() {
	$where = "IsActive='YES' ";
	$param = array();
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
		$fld = $_REQUEST['fields'];
		$where = " and " .  $fld . ' like :' . $fld;
		$param[':' . $fld] = '%' . $_REQUEST['query'] . '%';
	}

	$list = ACC_banks::SelectBanks($where, $param);
	$count = $list->rowCount();
	echo dataReader::getJsonData($list->fetchAll(), $count, $_GET['callback']);
	die();
}

function SaveBankData() {

	$bnk = new ACC_banks();
	PdoDataAccess::FillObjectByJsonData($bnk, $_POST['record']);

	if ($bnk->BankID == '')
		$res = $bnk->InsertBank();
	else
		$res = $bnk->UpdateBank();
	
	Response::createObjectiveResponse($res, '');
	die();
}

function DeleteBank() {

	$bnk = new ACC_banks($_POST['BId']);
	$res = $bnk->DeleteBank();

	Response::createObjectiveResponse($res, "");
	die();
}

//----------------------------------------------

function SelectAccounts() {
	
    $where = "IsActive='YES' ";
	$param = array();
    
	$field = false;
    if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
        $field = $_REQUEST['fields'];
        $where = " AND " . $field . ' like :' . $field;
        $param[':' . $field] = '%' . $_REQUEST['query'] . '%';
        $field = true;
    }
    if (!empty($_GET['AccountID'])) {
        $where.=" and acc.accountid<>:AccountID ";
        $param[':AccountID'] = $_GET['AccountID'];
    }
	if(!empty($_GET['BankID']))
	{
		$where .= " AND BankID=:BankID";
		$param[':BankID'] = $_GET['BankID'];
	}
    $list = ACC_accounts::Selectaccounts($where, $param);

    $count = $list->rowCount();
    echo dataReader::getJsonData($list->fetchAll(), $count, $_GET['callback']);
    die();
}

function SaveAccount() {

    $account = new ACC_accounts();
    PdoDataAccess::FillObjectByJsonData($account, $_POST['record']);
	$account->BankID = $_POST["BankID"];
	
    if ($account->AccountID == '') {
        $res = $account->InsertAccount();
    } else {
        $res = $account->UpdateAccount();
    }
	//print_r(ExceptionHandler::PopAllExceptions());
    Response::createObjectiveResponse($res, !$res ? ExceptionHandler::GetExceptionsToString() : "");
    die();
}

function DeleteAccount() {

    $res = ACC_accounts::DeleteAccount($_POST['AccId']);
    Response::createObjectiveResponse($res, '');
    die();
}

//----------------------------------------------

function SelectCheques() {
	$where = '1=1';
	$param = array();
	$field = false;
	if (isset($_REQUEST['fields']) && isset($_REQUEST['query'])) {
		$field = $_REQUEST['fields'];
		$where = "AND" . $field . ' like :' . $field;
		$param[':' . $field] = '%' . $_REQUEST['query'] . '%';
		$field = true;
	}
	$where .= " AND AccountID=:AccId";
	$param[':AccId'] = $_GET['BAccId'];
	
	$list = ACC_ChequeBooks::SelectCheques($where, $param);
	$count = count($list);

	echo dataReader::getJsonData($list, $count, $_GET['callback']);
	die();
}

function SaveCheque() {

	$ChequeBook = new ACC_ChequeBooks();
	PdoDataAccess::FillObjectByJsonData($ChequeBook, $_POST['record']);
	
	if($ChequeBook->MinNo >= $ChequeBook->MaxNo)
	{
		Response::createResponse(false, 'بازه تعریف شده برای شماره دسته چک نامعتبر است!');
		die();
	}    
	if ($ChequeBook->ChequeBookID == '')
	{
		$res = $ChequeBook->InsertCheque();
		
		$dt = PdoDataAccess::runquery("select * from ACC_ChequeBooks 
				where AccountID=? AND ChequeBookID<>? order by ChequeBookID desc", 
				array($ChequeBook->AccountID, $ChequeBook->ChequeBookID));
		if(count($dt)>0)
		{
			$sourceFilename = "/attachment/accounting/cheques/" . $dt[0]["ChequeBookID"] . ".html";
			$filename = "/attachment/accounting/cheques/" . $ChequeBook->ChequeBookID . ".html";
				
			if(file_exists($sourceFilename))
			{
				$fp = fopen($filename, "w");
				fwrite($fp,  file_get_contents($sourceFilename));
				fclose($fp);

				$sourceFilename = "/attachment/accounting/cheques/" . $dt[0]["ChequeBookID"] . ".jpg";
				$filename = "/attachment/accounting/cheques/" . $ChequeBook->ChequeBookID . ".jpg";
				
				$fp = fopen($filename, "w");
				fwrite($fp,  file_get_contents($sourceFilename));
				fclose($fp);
			}
		}
	}
	else
		$res = $ChequeBook->UpdateCheque();
	Response::createResponse($res, '');
	die();
}

function DeleteCheque() {
	
	$ChequeBook = new ACC_ChequeBooks($_POST['CBId']);
	$res = $ChequeBook->DeleteCheque();

	Response::createObjectiveResponse($res, '');
	die();
}

function EnableChequeBook() {
	$ChequeBook = new cheques($_POST['CBId']);
        $ChequeBook->IsActive='YES';
	$res = $ChequeBook->UpdateChequeBook();
	if ($res === false)
		Response::createObjectiveResponse($res, 'false');
	else
		Response::createObjectiveResponse($res, 'true');
	die();
}

function CopyCheque() {
	
    $ChObj = new ACC_ChequeBooks($_POST['ChequeBookID']);
	unset($ChObj->ChequeBookID);
	$ChObj->MaxNo = 0;
	$ChObj->MinNo = 0;
	$ChObj->SerialNo = "کپی " . $ChObj->SerialNo;
	$ChObj->InsertCheque();
	
    $src_file = "checkBuilder/output/" . $_POST['ChequeBookID'] . ".html";
    $src_background = "checkBuilder/backgrounds/" . $_POST['ChequeBookID'] . ".jpg";

	$output_filename = "checkBuilder/output/" . $ChObj->ChequeBookID . ".html";
    $output_background = "checkBuilder/backgrounds/" . $ChObj->ChequeBookID . ".jpg";	
	
    $fp = fopen($output_filename, "w");
    fwrite($fp, file_get_contents($src_file, "r"));
    fclose($fp);
	
    $fp = fopen($output_background, "w");
    fwrite($fp, file_get_contents($src_background, "r"));
    fclose($fp);
	
    Response::createObjectiveResponse(true, '');
    die();
}
//----------------------------------------------

function SelectChequeStatuses() {
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=4 AND IsActive='YES'");

	echo dataReader::getJsonData($temp, count($temp), $_GET['callback']);
	die();
}

//---------------------------------------------

function SelectACCRoles(){
	
	$temp = PdoDataAccess::runquery("select RowID,
			PersonID,concat_ws(' ',CompanyName,fname,lname) fullname,
			RoleID,InfoDesc RoleDesc
		from ACC_roles 
			join BSC_persons using(PersonID)
			join BaseInfo on(TypeID=75 AND InfoID=RoleID)");
	echo dataReader::getJsonData($temp, count($temp), $_GET['callback']);
	die();
}

function SelectRoles() {
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=75 AND IsActive='YES'");
	echo dataReader::getJsonData($temp, count($temp), $_GET['callback']);
	die();
}

function SaveRole(){
	
	$obj = new ACC_roles();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$obj->Add();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteRole(){
	
	$obj = new ACC_roles($_POST["RowID"]);
	$obj->Remove();
	echo Response::createObjectiveResponse(true, "");
	die();
}

//---------------------------------------------

function SelectCycles() {
	
	$temp = ACC_cycles::Get();
	echo dataReader::getJsonData($temp->fetchAll(), $temp->rowCount(), $_GET['callback']);
	die();
}

function SaveCycle(){
	
	$obj = new ACC_cycles();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->IsClosed != "YES")
		unset($obj->IsClosed);
	
	if(empty($obj->CycleID))
		$result = $obj->Add();
	else
		$result = $obj->Edit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

//---------------------------------------------

function GetBanks(){
	
	$dt = PdoDataAccess::runquery("select * from ACC_banks");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function ReplaceCostCodes(){
	
	$OLD_CostID = $_POST["OLD_CostID"];
	$NEW_CostID = $_POST["NEW_CostID"];
	
	PdoDataAccess::runquery("update ACC_DocItems join ACC_docs using(DocID)
		set CostID=:new 
		where CycleID=:c AND costID=:old ", array(
			":c" => $_SESSION["accounting"]["CycleID"], 
			":old" => $OLD_CostID,
			":new" => $NEW_CostID
		));
	
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, 
			PdoDataAccess::AffectedRows());
	die();
}

//---------------------------------------------

function selectParams() {

	$temp = ACC_CostCodeParams::Get(dataReader::makeOrder());
	$res = $temp->fetchAll();
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function SaveParam(){
	
	$obj = new ACC_CostCodeParams();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	if ($obj->ParamID > 0) 
		$obj->Edit();
	 else 
		$obj->Add();

	echo Response::createObjectiveResponse(true, '');
	die();
}

function selectParamItems() {
	
	$obj = new ACC_CostCodeParams($_REQUEST["ParamID"]);
	if($obj->SrcTable != "")
	{
		$temp = PdoDataAccess::runquery_fetchMode("select ".$obj->SrcValueField . " as ItemID, "
			.$obj->SrcDisplayField . " as ParamValue from " . $obj->SrcTable
			. ($obj->SrcWhere != "" ? " where " . $obj->SrcWhere : ""));
	}
	else
	{
		$temp = ACC_CostCodeParamItems::Get(" AND ParamID=?" . dataReader::makeOrder(), $_REQUEST["ParamID"]);
	}
	
	$res = $temp->fetchAll();
    echo dataReader::getJsonData($res, $temp->rowCount(), $_GET["callback"]);
    die();
}

function saveParamItem() {
	
	$obj = new ACC_CostCodeParamItems();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST['record']);
	if ((int)$obj->ItemID > 0) 
		$result = $obj->Edit();
	 else 
		$result = $obj->Add();
	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result , '');
	die();
}

function DeleteParam() { 

	$obj = new ACC_CostCodeParams($_POST['ParamID']);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, '');
	die();
}

function CopyParams(){
	
	$SRC_CostID = (int)$_POST["Src_CostID"];
	$DST_CostID = (int)$_POST["Dst_CostID"];
	
	PdoDataAccess::runquery("
		insert into ACC_CostCodeParams(CostID,ordering,ParamDesc,ParamType,KeyTitle,ParamValues)
		select :src,ordering,ParamDesc,ParamType,KeyTitle,ParamValues
		from ACC_CostCodeParams where CostID=:dst
	",array(
		":dst" => $DST_CostID,
		":src" => $SRC_CostID
	));
	echo Response::createObjectiveResponse(true, '');
	die();
}

function DeleteParamItem() {

	$obj = new ACC_CostCodeParamItems($_POST['ItemID']);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, '');
	die();
}

?>
