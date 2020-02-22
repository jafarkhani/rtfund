<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.04
//-----------------------------

require_once '../../header.inc.php';
require_once '../docs/doc.class.php';
require_once '../docs/import.data.php';
require_once 'deposite.class.php';
require_once inc_dataReader;
require_once inc_response;

$task = isset($_REQUEST["task"]) ? $_REQUEST["task"] : "";
if(!empty($task))
	$task();

function selectDeposites() {
	
	$temp = PdoDataAccess::runquery_fetchMode("
		select TafsiliID,CostID,concat_ws('-',b1.BlockDesc,b2.BlockDesc) CostDesc,
			sum(CreditorAmount-DebtorAmount) amount,TafsiliDesc
		from ACC_DocItems 
			join ACC_docs using(DocID)
			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(b1.BlockID=cc.level1)
			left join ACC_blocks b2 on(b2.BlockID=cc.level2)
			join ACC_tafsilis using(TafsiliID)
		
		where /*StatusID != ".ACC_STEPID_RAW." */ 1=1
			AND CostID in(" . COSTID_ShortDeposite . "," . COSTID_LongDeposite . ", ".COSTID_SupportDeposite.")
			AND CycleID=" . $_SESSION["accounting"]["CycleID"] . "
		group by TafsiliID,CostID");
	
	$dt = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	//echo PdoDataAccess::GetLatestQueryString();
	echo dataReader::getJsonData($dt, $temp->rowCount(), $_GET ["callback"]);
	die();
}

function DepositeProfit(){
	
	$ComputeFn = $_POST["ComputeFn"];
	$TafsiliArr = array();
	$keys = array_keys($_POST);
	foreach($keys as $key)
		if(strpos($key, "chk_") !== false)
		{
			$arr = preg_split("/_/", $key);
			$TafsiliArr[] = array(
				"CostID" =>  $arr[1],
				"TafsiliID" => $arr[2]
			);
		}
	
	if(count($TafsiliArr) == 0)
	{
		echo Response::createObjectiveResponse(false, "هیچ ردیفی انتخاب نشده است");
		die();
	}
		
	$ToDate = DateModules::shamsi_to_miladi($_POST["ToDate"]);
	ComputeDepositeProfit($ToDate, $TafsiliArr, $ComputeFn);
	die();
}


//---------------------------------------------

function SelectPercents(){
	
	$temp = ACC_DepositePercents::Get(dataReader::makeOrder());
	$dt = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($dt, $temp->rowCount(), $_GET['callback']);
	die();
}

function SavePercent(){
	
	$obj = new ACC_DepositePercents();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if($obj->RowID != "")
		$result = $obj->Edit();
	else
		$result = $obj->Add();
	
	echo Response::createObjectiveResponse($result, "");
	die();
}

function DeletePercent(){
	
	$obj = new ACC_DepositePercents($_POST["RowID"]);
	$result = $obj->Remove();
	echo Response::createObjectiveResponse($result, "");
	die();
}

?>