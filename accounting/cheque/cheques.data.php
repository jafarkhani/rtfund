<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	95.04
//---------------------------
  
require_once '../../header.inc.php';
require_once(inc_response);
require_once inc_dataReader;

ini_set("display_errors", "On");

require_once '../docs/doc.class.php';
require_once "../../loan/request/request.class.php";
require_once "../docs/import.data.php";
require_once 'cheque.class.php';
require_once '../../commitment/ExecuteEvent.class.php';

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
if(!empty($task))
	$task();

function MakeWhere(&$where , &$param){
	
	//.........................................................
	if(!empty($_POST["FromNo"]))
	{
		$where .= " AND ChequeNo >= :cfn";
		$param[":cfn"] = $_POST["FromNo"];
	}
	if(!empty($_POST["ToNo"]))
	{
		$where .= " AND ChequeNo <= :ctn";
		$param[":ctn"] = $_POST["ToNo"];
	}
	if(!empty($_POST["FromDate"]))
	{
		$where .= " AND ChequeDate >= :fd";
		$param[":fd"] = DateModules::shamsi_to_miladi($_POST["FromDate"], "-");
	}
	if(!empty($_POST["ToDate"]))
	{
		$where .= " AND ChequeDate <= :td";
		$param[":td"] = DateModules::shamsi_to_miladi($_POST["ToDate"], "-");
	}
	if(!empty($_POST["FromAmount"]))
	{
		$where .= " AND ChequeAmount >= :fa";
		$param[":fa"] = preg_replace('/,/', "", $_POST["FromAmount"]);
	}
	if(!empty($_POST["ToAmount"]))
	{
		$where .= " AND ChequeAmount <= :ta";
		$param[":ta"] = preg_replace('/,/', "", $_POST["ToAmount"]);
	}
	if(!empty($_POST["ChequeBank"]))
	{
		$where .= " AND ChequeBank = :cb";
		$param[":cb"] = $_POST["ChequeBank"];
	}
	if(!empty($_POST["ChequeBranch"]))
	{
		$where .= " AND ChequeBranch like :cb";
		$param[":cb"] = "%" . $_POST["ChequeBranch"] . "%";
	}
	if(!empty($_POST["ChequeStatus"]))
	{
		$where .= " AND ChequeStatus = :cst";
		$param[":cst"] = $_POST["ChequeStatus"];
	}
	//.........................................................
	if (isset($_GET["fields"]) && !empty($_GET["query"])) {
		$field = $_GET["fields"];
		$where .= " AND " . $field . " like :q";
		$param[":q"] = "%" . $_GET["query"] . "%"; 
	}
}

function selectIncomeCheques() {
	
	$where = "1=1";
	$param = array(); 
			
	MakeWhere($where, $param);
			
	$query = "
		select t.*,b.BankDesc, bf.InfoDesc ChequeStatusDesc
		from
		(
			select i.*,
				group_concat(concat_ws(' ','[ وام ',r.RequestID,']',p.CompanyName,p.fname,p.lname) 
					SEPARATOR '<br>') as fullname,
					group_concat(l.LoanDesc SEPARATOR '<br>') CostDesc,
					br.BranchName
			from ACC_IncomeCheques i
			join LON_BackPays bp using(IncomeChequeID)
			join LON_requests r on(bp.RequestID=r.RequestID)
			join BSC_persons p on(p.PersonID=r.LoanPersonID)
			join LON_loans l using(LoanID)
			join BSC_branches br on(r.BranchID=br.BranchID)
			where $where
			group by i.IncomeChequeID

		union all

			select i.*,group_concat(concat_ws(' ','[ وام ',r.RequestID,']',p.CompanyName,p.fname,p.lname) 
					SEPARATOR '<br>') as fullname,
					group_concat(l.LoanDesc SEPARATOR '<br>') CostDesc,
					br.BranchName
			from ACC_IncomeCheques i
			join LON_requests r on(i.LoanRequestID=r.RequestID)
			join BSC_persons p on(p.PersonID=r.LoanPersonID)
			join LON_loans l using(LoanID)
			join BSC_branches br on(r.BranchID=br.BranchID)
			where $where
			group by i.IncomeChequeID

		union all

			select i.*,t1.TafsiliDesc fullname, 
				concat_ws('-', b1.blockDesc, b2.blockDesc, b3.blockDesc, b4.blockDesc) CostDesc,br.BranchName
			from ACC_IncomeCheques i
			left join BSC_branches br on(i.BranchID=br.BranchID)
			left join ACC_tafsilis t1 using(TafsiliID)
			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)
			where $where
			group by i.IncomeChequeID
		)t

		left join ACC_banks b on(ChequeBank=BankID)
		left join BaseInfo bf on(bf.TypeID=4 AND bf.InfoID=ChequeStatus)
		";
		
	//.........................................................
	$query .= dataReader::makeOrder();
	$temp = PdoDataAccess::runquery_fetchMode($query, $param);
	
	//print_r(ExceptionHandler::PopAllExceptions());
	//echo "/*" . PdoDataAccess::GetLatestQueryString() . "*/";
	
	$no = $temp->rowCount();
	$temp = PdoDataAccess::fetchAll($temp, $_GET["start"], $_GET["limit"]);
	echo dataReader::getJsonData($temp, $no, $_GET["callback"]);
	die();
}

function SelectIncomeChequeStatuses() {
	
	$temp = PdoDataAccess::runquery("select * from BaseInfo where TypeID=4 AND IsActive='YES'");
	//$temp = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=7");
	/*
	$temp = PdoDataAccess::runquery("
		select b.* , 
		concat('[',c1.CostCode,']',concat_ws('-',b11.blockDesc,b12.blockDesc,b13.blockDesc,b14.blockDesc)) 
			bed_CostCode, 
		concat('[',c2.CostCode,']',concat_ws('-',b21.blockDesc,b22.blockDesc,b23.blockDesc,b24.blockDesc)) 
			bes_CostCode 
		from BaseInfo b 
		left join ACC_CostCodes c1 on(c1.CostID=b.param1)
		left join ACC_blocks b11 on(c1.level1=b11.blockID)
		left join ACC_blocks b12 on(c1.level2=b12.blockID)
		left join ACC_blocks b13 on(c1.level3=b13.blockID)
		left join ACC_blocks b14 on(c1.level4=b14.blockID)
		
		left join ACC_CostCodes c2 on(c2.CostID=b.param2)
		left join ACC_blocks b21 on(c1.level1=b21.blockID)
		left join ACC_blocks b22 on(c2.level2=b22.blockID)
		left join ACC_blocks b23 on(c2.level3=b23.blockID)
		left join ACC_blocks b24 on(c2.level4=b24.blockID)
		
		where TypeID=4 AND b.IsActive='YES'");
		*/
	echo dataReader::getJsonData($temp, count($temp), $_GET['callback']);
	die();
}

function SelectChequeStatuses(){
	
	$dt = PdoDataAccess::runquery("select * from ACC_ChequeStatuses");
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function SaveChequeStatus(){
	
	PdoDataAccess::runquery("insert into ACC_ChequeStatuses(SrcID,DstID,EventID) values(?,?,?)", 
		array((int)$_POST["SrcID"], (int)$_POST["DstID"], (int)$_POST["EventID"]));
	echo Response::createObjectiveResponse(true, "");
	die();
}
 
function DeleteChequeStatuses(){
	
	PdoDataAccess::runquery("delete from ACC_ChequeStatuses where RowID=?", 
		array($_POST["RowID"]));
	echo Response::createObjectiveResponse(true, "");
	die();
}

function selectValidChequeStatuses(){
	
	$SrcID = $_REQUEST["SrcID"];
	$temp = PdoDataAccess::runquery("
		select InfoID,InfoDesc
		from BaseInfo join ACC_ChequeStatuses on(SrcID=? AND DstID=InfoID)
		where TypeID=4", array($SrcID));
	
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();
}

//...........................................

function SaveIncomeCheque(){
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new ACC_IncomeCheques();
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	$obj->ChequeStatus = INCOMECHEQUE_NOTVOSUL;
	if(!$obj->Add($pdo))
	{
		echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	//................. add back pays ........................
	if(!empty($_POST["parts"]))
	{
		$parts = json_decode($_POST["parts"]);
		foreach($parts as $partStr)
		{
			$arr = preg_split("/_/", $partStr);
			$RequestID = $arr[0];
			$PayAmount = $arr[1];

			$bobj = new LON_BackPays();
			$bobj->PayDate = $obj->ChequeDate;
			$bobj->IncomeChequeID = $obj->IncomeChequeID;
			$bobj->RequestID = $RequestID;
			$bobj->PayAmount = $PayAmount;
			$bobj->PayType = BACKPAY_PAYTYPE_CHEQUE;
			$bobj->IsGroup = "YES";
			$bobj->Add($pdo);
			
			if($obj->BranchID == "")
			{
				$ReqObj = new LON_requests($RequestID);
				$obj->BranchID = $ReqObj->BranchID;
				$obj->Edit($pdo);
			}
		}
	}
	
	//--------------- execute event ----------------
	$EventID = LON_requests::GetEventID(0, EVENTTYPE_IncomeCheque , INCOMECHEQUE_NOTVOSUL);
	$eventobj = new ExecuteEvent($EventID);
	$eventobj->Sources = array(0, $obj->IncomeChequeID);
	$eventobj->AllRowsAmount = $obj->ChequeAmount;
	$result = $eventobj->RegisterEventDoc($pdo);
	if(!$result)
	{
		Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
		die();
	}
	//.......................................................
	ACC_IncomeCheques::AddToHistory($obj->IncomeChequeID, $obj->ChequeStatus, $pdo);
	/*
	//--------------- get DocID ------------------
	$DocID = "";
	if(!empty($_POST["LocalNo"]))
	{
		$dt = PdoDataAccess::runquery("select DocID,StatusID from ACC_docs where LocalNo=? AND CycleID=?",
			array($_POST["LocalNo"], $_SESSION["accounting"]["CycleID"]));
		if(count($dt) == 0)
		{
			$pdo->rollback();
			echo Response::createObjectiveResponse(false, "شماره سند یافت نشد");
			die();
		}	
		if($dt[0]["StatusID"] != ACC_STEPID_RAW)
		{
			$pdo->rollback();
			echo Response::createObjectiveResponse(false, "سند مربوطه تایید شده و امکان اضافه به آن ممکن نیست");
			die();
		}
		$DocID = $dt[0][0];
	}
	//--------------------------------------------
	if(!RegisterOuterCheque($DocID,$obj,$pdo))
	{
		//print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false,ExceptionHandler::GetExceptionsToString());
		die();
	}*/
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function DeleteCheque(){
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new ACC_IncomeCheques($_POST["IncomeChequeID"]);
	if($obj->ChequeStatus != INCOMECHEQUE_NOTVOSUL)
	{
		echo Response::createObjectiveResponse(false, "تنها چک های وصول نشده قابل حذف می باشند");
		die();
	}
	
	$obj->DeleteDocs($pdo);
	
	if($obj->HasDoc($pdo))
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "برای این چک سند حسابداری تایید شده وجود دارد");
		die();
	}
	
	$obj->Remove($pdo);	
	
	PdoDataAccess::runquery("delete from LON_BackPays where IncomeChequeID=?", array($obj->IncomeChequeID), $pdo);
	
	$pdo->commit();	
	echo Response::createObjectiveResponse(true, "");
	die();
}

function GetBackPays(){
	
	$IncomeChequeID = $_POST["IncomeChequeID"];
	$Status = $_POST["StatusID"];
	
	$obj = new ACC_IncomeCheques($IncomeChequeID);
	$dt = $obj->GetBackPays(); 
	if(count($dt) == 0)
		return 0;
	if(count($dt) == 1)
	{
		$PayObj = new LON_BackPays($dt[0]["BackPayID"]);
		$partObj = LON_ReqParts::GetValidPartObj($PayObj->RequestID);
		$ReqObj = new LON_requests($PayObj->RequestID);
		
		$EventID = LON_requests::GetEventID($ReqObj->RequestID, "LoanBackPayCheque", $Status);
		echo Response::createObjectiveResponse("true", $EventID."_".
				$ReqObj->RequestID."_".$partObj->PartID."_".$PayObj->BackPayID);
		die();
	}
	
	$RegDocID = 0;
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	foreach($dt as $row)
	{
		$PayObj = new LON_BackPays($row["BackPayID"]);
		$ReqObj = new LON_requests($PayObj->RequestID);

		$EventID = LON_requests::GetEventID($ReqObj->RequestID, "LoanBackPayCheque", $Status);
		if($EventID == 0)
		{
			$pdo->rollBack();
			Response::createObjectiveResponse(false, "رویداد مرتبط یافت نشد");
			die();
		}
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($ReqObj->RequestID, $partObj->PartID, $PayObj->BackPayID);
		if($Status != INCOMECHEQUE_VOSUL)
			$eventobj->AllRowsAmount = $PayObj->PayAmount;
		$eventobj->DocObj = $DocObj;
		$result = $eventobj->RegisterEventDoc($pdo);
		if(!$result)
		{
			$pdo->rollBack();
			Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}
		$DocObj = $eventobj->DocObj;
		$RegDocID = $eventobj->DocObj->DocID;
	}
	$pdo->commit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ChangeChequeStatus(){
	
	$IncomeChequeID = $_POST["IncomeChequeID"];
	$Status = $_POST["StatusID"];
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new ACC_IncomeCheques($IncomeChequeID);
	$PreStatus = $obj->ChequeStatus;
	$obj->ChequeStatus = $Status;
	if($Status == INCOMECHEQUE_VOSUL)
		$obj->PayedDate = $_POST["PayedDate"];
	$result = $obj->Edit($pdo);
	
	$dt = $obj->GetBackPays($pdo); 
	$RegDocID = 0;
				
	//--------------- execute event ----------------
	if(count($dt) > 0)
	{
		$DocObj = null;
		foreach($dt as $row)
		{
			$PayObj = new LON_BackPays($row["BackPayID"]);
			$ReqObj = new LON_requests($PayObj->RequestID);
			$partObj = LON_ReqParts::GetValidPartObj($ReqObj->RequestID);
			
			$IsInner = $ReqObj->ReqPersonID*1 > 0 ? false : true;

			if($Status == INCOMECHEQUE_VOSUL)
			{
				if(isset($_POST["UpdateLoanBackPay"]) && $obj->PayedDate != "")
				{
					$PayObj->PayDate = $obj->PayedDate;
					$result = $PayObj->Edit($pdo);
					if(!$result)
					{
						$pdo->rollback();
						echo Response::createObjectiveResponse(false, "خطا در بروزرسانی تاریخ پرداخت مشتری");
						die();
					}	
				}

				$EventID = LON_requests::GetEventID($ReqObj->RequestID, EVENTTYPE_LoanBackPayCheque, $Status);
				if($EventID == 0)
				{
					$pdo->rollBack();
					Response::createObjectiveResponse(false, "رویداد وصول چک یافت نشد");
					die();
				}
				$eventobj = new ExecuteEvent($EventID);
				$eventobj->Sources = array($ReqObj->RequestID, $partObj->PartID, $PayObj->BackPayID,$PayObj->IncomeChequeID);
				$eventobj->DocObj = $DocObj;
				$result = $eventobj->RegisterEventDoc($pdo);
				if(!$result)
				{
					Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
					die();
				}
				$DocObj = $eventobj->DocObj;
				$RegDocID = $eventobj->DocObj->DocID;
			}
		}
	}
	//---------------------------------------------------------------
	if($Status != INCOMECHEQUE_VOSUL)
	{
		$EventID = LON_requests::GetEventID($ReqObj->RequestID, EVENTTYPE_IncomeCheque, $Status);
		if($EventID != 0)
		{
			$eventobj = new ExecuteEvent($EventID);
			$eventobj->Sources = array(0,0,0,$PayObj->IncomeChequeID);
			$eventobj->AllRowsAmount = $PayObj->PayAmount;
			$eventobj->DocObj = $DocObj;
			$result = $eventobj->RegisterEventDoc($pdo);
			if(!$result)
			{
				Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
				die();
			}
			$DocObj = $eventobj->DocObj;
			$RegDocID = $eventobj->DocObj->DocID;
		}
	}
	//---------------------------------------------------------------
	ACC_IncomeCheques::AddToHistory($IncomeChequeID, $Status, $RegDocID, $pdo);
	
	$pdo->commit();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function ChangeOutcomeChequeStatus(){
	
	$DocChequeID = $_POST["DocChequeID"];
	$Status = $_POST["StatusID"];
	$DocDate = $_POST["DocDate"];
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new ACC_DocCheques($DocChequeID);
	$obj->CheckStatus = $Status;
	$result = $obj->Edit($pdo);
	
	//------------------ execute event -------------------
	$dt = PdoDataAccess::runquery("select * from BaseInfo where TypeID=4 AND InfoID=?", array($Status));
	$EventID = $dt[0]["param3"];
	$message = "";
	
	if($EventID != "")
	{
		$LocalNo = ExecuteEvent::GetRegisteredDoc($EventID, array($obj->DocID, $obj->DocChequeID));
		if($LocalNo !== false)
		{
			echo Response::createObjectiveResponse(false, "تغییر وضعیت مربوطه قبلا انجام شده و سند مربوطه با شماره " . $LocalNo . " صادر گردیده است");
			die();
		}
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocDate = DateModules::shamsi_to_miladi($DocDate, "-");
		$eventobj->Sources = array($obj->DocID, $obj->DocChequeID);
		$eventobj->AllRowsAmount = $obj->amount;
		$result = $eventobj->RegisterEventDoc($pdo);
		if(!$result)
		{
			$pdo->rollBack();
			Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
			die();
		}
		$DocObj = $eventobj->DocObj;
		$message = "سند با شماره " . $DocObj->LocalNo . " با موفقیت صادر گردید";
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse($result, $message);
	die();
}

function ReturnLatestOperation($returnMode = false){
	
	$OuterObj = new ACC_IncomeCheques($_POST["IncomeChequeID"]);
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$dt = PdoDataAccess::runquery("select h.* from ACC_ChequeHistory h where IncomeChequeID=? AND details not like '%برگشت%' order by RowID desc",	
			array($OuterObj->IncomeChequeID), $pdo);
	$DocID = $dt[0]["DocID"]*1;	
	
	if(count($dt) < 2)
	{
		$pdo->rollBack();
		echo Response::createObjectiveResponse(false, "عملیات قبلی برای برگشت وجود ندارد");
		die();
	}
	
	if($DocID > 0)
	{
		$OuterObj->ChequeStatus = $dt[1]["StatusID"];
		
		PdoDataAccess::runquery("delete from ACC_DocItems where DocID=?", array($DocID), $pdo);
		PdoDataAccess::runquery("delete from ACC_docs where DocID=? ",	array($DocID), $pdo);
	}
		
	$OuterObj->Edit($pdo);
	//..................................................
	ACC_IncomeCheques::AddToHistory($OuterObj->IncomeChequeID, $OuterObj->ChequeStatus,0, $pdo, "برگشت عملیات");
	//..................................................
	/*$dt = $OuterObj->GetBackPays($pdo);
	foreach($dt as $row)
	{
		$PayObj = new LON_BackPays($row["BackPayID"]);
		ReturnCustomerPayDoc($PayObj, $pdo);
	}*/
	//..................................................
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, "خطا در برگشت");
		die();
	}
	
	$pdo->commit();
	
	if($returnMode)
		return true;
	
	echo Response::createObjectiveResponse(true, "");
	die();	
}

function SaveLoanCheque(){
	
	$ReqObj = new LON_requests($_POST["RequestID"]);
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	$DocID = "";
	
	$cheques = json_decode($_POST["cheques"]);
	foreach($cheques as $cheque)
	{
		$obj = new ACC_IncomeCheques();
		PdoDataAccess::FillObjectByJsonData($obj, $cheque);
		$obj->ChequeStatus = INCOMECHEQUE_NOTVOSUL;
		$obj->BranchID = $ReqObj->BranchID;
		
		if($_POST["ChequeFor"] == "INSTALLMENT")
		{
			if(!$obj->Add($pdo))
			{
				echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
				die();
			}
			//................. add back pays ........................
			$bobj = new LON_BackPays();
			$bobj->PayDate = $obj->ChequeDate;
			$bobj->IncomeChequeID = $obj->IncomeChequeID;
			$bobj->RequestID = $_POST["RequestID"];
			$bobj->PayAmount = $obj->ChequeAmount;
			$bobj->PayType = BACKPAY_PAYTYPE_CHEQUE;
			$bobj->Add($pdo);			
		}
		else
		{
			$obj->CostID = COSTID_GetDelay;
			$obj->LoanRequestID = $_POST["RequestID"];
			$obj->TafsiliType = TAFSILITYPE_PERSON;
			$obj->TafsiliID = FindTafsiliID($ReqObj->LoanPersonID, TAFSILITYPE_PERSON);
			if(!empty($ReqObj->ReqPersonID))
			{
				$obj->TafsiliType2 = TAFSILITYPE_PERSON;
				$obj->TafsiliID2 = FindTafsiliID($ReqObj->ReqPersonID, TAFSILITYPE_PERSON);
			}
			if(!$obj->Add($pdo))
			{
				echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
				die();
			}
		}
		
		//--------------- execute event ----------------
		$EventID = LON_requests::GetEventID(0, EVENTTYPE_IncomeCheque , INCOMECHEQUE_NOTVOSUL);
		if($EventID != 0)
		{
			$eventobj = new ExecuteEvent($EventID);
			$eventobj->Sources = array(0, $obj->IncomeChequeID);
			$eventobj->AllRowsAmount = $obj->ChequeAmount;
			$eventobj->ExtraDescription = ' شماره چک '. $obj->ChequeNo .
					' تاریخ سررسید چک '. $obj->ChequeDate .
					' نام مشتری '. $ReqObj->_LoanPersonFullname .
					' شماره تسهیلات '. $_POST["RequestID"];
			$result = $eventobj->RegisterEventDoc($pdo);
			if(!$result)
			{
				$pdo->rollBack();
				Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
				die();
			}
		}
		//--------------------------------------------
		ACC_IncomeCheques::AddToHistory($obj->IncomeChequeID, $obj->ChequeStatus, 
				$eventobj->DocObj->DocID, $pdo);
		//--------------------------------------------
		/*$DocID = RegisterOuterCheque($DocID,$obj,$pdo);
		if(!$DocID){
			echo Response::createObjectiveResponse(false,ExceptionHandler::GetExceptionsToString());
			die();
		}*/
	}
	
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

function SavePayedDate(){
	
	$st = stripslashes(stripslashes($_POST["record"]));
	$data = json_decode($st);
		
	$obj = new ACC_IncomeCheques();
	$obj->IncomeChequeID = $data->IncomeChequeID;
	$obj->PayedDate = $data->PayedDate;
	$obj->ChequeDate = $data->ChequeDate;
	$result = $obj->Edit();

	//print_r(ExceptionHandler::PopAllExceptions());
	echo Response::createObjectiveResponse($result, "");
	die();
}

function editCheque(){
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	$obj = new ACC_IncomeCheques($_POST["IncomeChequeID"]);
	
	if($obj->ChequeStatus != INCOMECHEQUE_NOTVOSUL)
	{
		echo Response::createObjectiveResponse(false, "تنها چکهای وصول نشده قابل تغییر می باشد");
		die();
	}
	
	$comment = "";
	if($obj->ChequeAmount != $_POST["newAmount"])
		$comment .= "مبلغ قبلی : " . number_format($obj->ChequeAmount) . "<br>";
	if($obj->ChequeDate != $_POST["newDate"])
		$comment .= "تاریخ قبلی : " . DateModules::miladi_to_shamsi($obj->ChequeDate) . "<br>";
	$comment .= "دلیل تغییر : " . $_POST["reason"];
	
	ACC_IncomeCheques::AddToHistory($obj->IncomeChequeID, INCOMECHEQUE_EDIT, 0, $pdo,  $comment);
	
	if($obj->ChequeAmount != $_POST["newAmount"])
	{
		if(!EditIncomeCheque($obj, $_POST["newAmount"], $pdo))
		{
			$pdo->rollBack();
			print_r(ExceptionHandler::PopAllExceptions());
			echo Response::createObjectiveResponse(false, "");
			die();
		}
	}
	$obj->ChequeAmount = $_POST["newAmount"];
	$obj->ChequeDate = $_POST["newDate"];
	$obj->Edit($pdo);
	
	$BackPays = $obj->GetBackPays($pdo);
	if(count($BackPays) > 0)
	{
		$bobj = new LON_BackPays($BackPays[0]["BackPayID"]);
		$bobj->PayAmount = $_POST["newAmount"];
		$bobj->PayDate = $_POST["newDate"];
		$bobj->Edit($pdo);
	}
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		$pdo->rollBack();
		print_r(ExceptionHandler::PopAllExceptions());
		echo Response::createObjectiveResponse(false, "");
		die();
	}
	$pdo->commit();
	echo Response::createObjectiveResponse(true, "");
	die();
}

//...........................................

function selectOutcomeCheques(){
	
	$query = "
		select c.*,d.LocalNo,d.DocDate,a.*,b.InfoDesc as StatusDesc,t.TafsiliDesc,bankDesc,t.VosulLocalNo

		from ACC_DocCheques c
		left join ACC_tafsilis t using(tafsiliID)
		join ACC_docs d using(DocID)
		join ACC_accounts a using(AccountID)
		join ACC_banks bb using(BankID)
		join BaseInfo b on(b.typeID=4 AND b.infoID=CheckStatus)
		left join (
			select SourceID2,LocalNo VosulLocalNo
			from ACC_docs join ACC_DocItems using(DocID)
			join COM_events e using(EventID)
			where EventType='".EVENTTYPE_OutcomeCheque."' AND EventType2=".INCOMECHEQUE_VOSUL."
			group by SourceID1,SourceID2
		)t on(t.SourceID2=c.DocChequeID)
		where 1=1 ";

	$whereParam = array();
	
	if(!empty($_POST["FromDocNo"]))
	{
		$query .= " AND d.LocalNo >= :fdno ";
		$whereParam[":fdno"] = $_POST["FromDocNo"];
	}
	if(!empty($_POST["ToDocNo"]))
	{
		$query .= " AND d.LocalNo <= :tdno ";
		$whereParam[":tdno"] = $_POST["ToDocNo"];
	}
	if(!empty($_POST["DFromDate"]))
	{
		$query .= " AND d.DocDate >= :fdd ";
		$whereParam[":fdd"] = DateModules::shamsi_to_miladi($_POST["DFromDate"], "-");
	}
	if(!empty($_POST["DToDate"]))
	{
		$query .= " AND d.DocDate <= :tdd ";
		$whereParam[":tdd"] = DateModules::shamsi_to_miladi($_POST["DToDate"], "-");
	}
	if(!empty($_POST["FromDate"]))
	{
		$query .= " AND c.checkDate >= :fd ";
		$whereParam[":fd"] = DateModules::shamsi_to_miladi($_POST["FromDate"], "-");
	}
	if(!empty($_POST["ToDate"]))
	{
		$query .= " AND c.checkDate <= :td ";
		$whereParam[":td"] = DateModules::shamsi_to_miladi($_POST["ToDate"], "-");
	}
	if(!empty($_POST["CheckStatus"]))
	{
		$query .= " AND c.CheckStatus = :cs ";
		$whereParam[":cs"] = $_POST["CheckStatus"];
	}
	if(!empty($_POST["FromCheckNo"]))
	{
		$query .= " AND c.checkNo >= :fcn ";
		$whereParam[":fcn"] = $_POST["FromCheckNo"];
	}
	if(!empty($_POST["ToCheckNo"]))
	{
		$query .= " AND c.checkNo <= :tcn ";
		$whereParam[":tcn"] = $_POST["ToCheckNo"];
	}
	if(!empty($_POST["FromAmount"]))
	{
		$query .= " AND c.amount >= :fa ";
		$whereParam[":fa"] = preg_replace('/,/', "", $_POST["FromAmount"]);
	}
	if(!empty($_POST["ToAmount"]))
	{
		$query .= " AND c.amount <= :ta ";
		$whereParam[":ta"] = preg_replace('/,/', "", $_POST["ToAmount"]);
	}
	if(!empty($_POST["bankID"]))
	{
		$query .= " AND a.bankID = :b ";
		$whereParam[":b"] = $_POST["bankID"];
	}
	if(!empty($_POST["accountID"]))
	{
		$query .= " AND c.accountID = :ac ";
		$whereParam[":ac"] = $_POST["accountID"];
	}
	if(!empty($_POST["tafsiliID"]))
	{
		$query .= " AND c.tafsiliID = :taf ";
		$whereParam[":taf"] = $_POST["tafsiliID"];
	}
	
	if (isset($_GET["fields"]) && !empty($_GET["query"])) {
		$field = $_GET["fields"];
		$query .= " AND " . $field . " like :f";
		$whereParam[":f"] = "%" . $_GET["query"] . "%";
	}
	
	$query .= dataReader::makeOrder();

	$dataTable = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	//echo PdoDataAccess::GetLatestQueryString();
	print_r(ExceptionHandler::PopAllExceptions());
	$count = $dataTable->rowCount();
	$data = PdoDataAccess::fetchAll($dataTable, (int)$_GET["start"], (int)$_GET["limit"]);
	echo dataReader::getJsonData($data,$count, $_GET["callback"]);
	die();
}

//...........................................

function SaveStatus(){
	
	require_once '../../framework/baseInfo/baseInfo.class.php';
	$obj = new BaseInfo();
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	$obj->TypeID = 4;
	
	if(!isset($obj->param1))
		$obj->param1 = PDONULL;
	if(!isset($obj->param2))
		$obj->param2 = PDONULL;
	
	if($obj->InfoID*1 == 0)
	{
		$pdo = PdoDataAccess::getPdoObject();
		$pdo->beginTransaction();
	
		$obj->InfoID = PdoDataAccess::GetLastID("BaseInfo", "InfoID", "TypeID=?", array($obj->TypeID), $pdo);
		$obj->InfoID = $obj->InfoID*1 + 1;
		
		$obj->Add($pdo);		
		
		/*$obj2 = new ACC_tafsilis();
		$obj2->TafsiliType = TAFTYPE_ChequeStatus;
		$obj2->ObjectID = $obj->InfoID;
		$obj2->TafsiliDesc = $obj->InfoDesc;
		$obj2->TafsiliCode = $obj->InfoID;
		$obj2->AddTafsili($pdo);*/
		
		$pdo->commit();
	}
	else
		$obj->Edit();

	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

function DeleteStatus(){

	require_once '../../framework/baseInfo/baseInfo.class.php';
	$TypeID = 4;
	$obj = new BaseInfo($TypeID, $_REQUEST["InfoID"]);
	$obj->Remove();
	echo Response::createObjectiveResponse(ExceptionHandler::GetExceptionCount() == 0, "");
	die();
}

?>
