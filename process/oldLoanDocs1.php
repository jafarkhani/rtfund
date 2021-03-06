<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.12
//---------------------------
 ini_set("display_errors", "On");
require_once '../header.inc.php';
require_once '../commitment/ExecuteEvent.class.php';
require_once '../loan/request/request.class.php';

ini_set("display_errors", "On");
ini_set('max_execution_time', 300000);
ini_set('memory_limit','2000M');
//header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);

//FundIncomeOfAgent();die(); 
/*
echo "---------------------";
PdoDataAccess::runquery("
	
	insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			DebtorAmount,CreditorAmount,locked,
			param1,param2,param3,
			SourceID1,SourceID2,SourceID3,SourceID4)
		select 9753,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			
			if( sum(DebtorAmount-CreditorAmount)>0, sum(DebtorAmount-CreditorAmount), 0 ),
            if( sum(CreditorAmount-DebtorAmount)>0, sum(CreditorAmount-DebtorAmount), 0 ),
			1,
			param1,param2,param3,
			SourceID1,SourceID2,SourceID3,SourceID4
		from ACC_DocItems i join ACC_docs using(DocID)
		where DocDate < '2019-03-21'
		group by CostID,TafsiliID,TafsiliID2,TafsiliID3,param1,param2,param3
		having sum(CreditorAmount-DebtorAmount)<>0"); 
print_r(ExceptionHandler::PopAllExceptions());
echo PdoDataAccess::AffectedRows();
die();*/
/*
insert into sajakrrt_rtfund.ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,DebtorAmount,CreditorAmount,locked,
			param1,param2,param3,SourceID1,SourceID2,SourceID3,SourceID4) 
            
            select 67130,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,DebtorAmount,CreditorAmount,locked,
			param1,param2,param3,SourceID1,SourceID2,SourceID3,SourceID4
            from sajakrrt_oldcomputes.ACC_DocItems where DociD=67130 */


$reqs = PdoDataAccess::runquery_fetchMode(" select RequestID from LON_requests 
		where RequestID in(1985,2215,1905,1972,2212,1969,2240,2019,1951,2017,2042,2062,1899,2150,1777,1987)");
//echo PdoDataAccess::GetLatestQueryString();
$pdo = PdoDataAccess::getPdoObject();

$DocObj = array();

while($requset=$reqs->fetch())
{
	$pdo->beginTransaction();
	echo "-------------- " . $requset["RequestID"] . " -----------------<br>";
	ob_flush();flush();
	
	$reqObj = new LON_requests($requset["RequestID"]);
	$partObj = LON_ReqParts::GetValidPartObj($requset["RequestID"]);
	
	/*$result = Contract($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	if(!$result)
	{
		$pdo->rollBack();
		continue;
	}
	
	$result = Payment($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	if(!$result)
	{
		$pdo->rollBack();
		continue;
	}
	
	$result = BackPay($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	if(!$result)
	{
		if($pdo->inTransaction())
			$pdo->rollBack();
		continue;
	}*/
	$result = DailyIncome($reqObj, $partObj, $pdo);
	if(!$result)
	{
		if($pdo->inTransaction())
			$pdo->rollBack();
		continue;
	}
	/*$result = DailyWage($reqObj, $partObj, $pdo);
	if(!$result)
	{
		if($pdo->inTransaction())
			$pdo->rollBack();
		continue;
	}*/
	
	//--------------------------------------------------
	PdoDataAccess::runquery_fetchMode(" update aaa set regDoc=1 where c1=?", array($reqObj->RequestID), $pdo);
	$pdo->commit();

	EventComputeItems::$LoanComputeArray = array();
	EventComputeItems::$LoanPuresArray = array();
}

/**
 * رویداد عقد قرارداد
 */
function Contract($reqObj , $partObj, &$DocObj, $pdo){

	$result = true;
	$EventID = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanContract);

	$eventobj = new ExecuteEvent($EventID);
	$eventobj->DocDate = $partObj->PartDate;
	$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID);
	$result = $eventobj->RegisterEventDoc($pdo);
	echo "Contract : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		return false;
	}
	ob_flush();flush();
	return true;
}

/**
 * رویدادهای پرداخت وام
 */
function Payment($reqObj , $partObj, &$DocObj, $pdo){
	
	$result = true;
	$EventID = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanPayment);
	
	$pays = PdoDataAccess::runquery("select PayID,RequestID,PayDate
			from LON_payments where RequestID=:rid"
			, array(":rid" =>$reqObj->RequestID));
	
	foreach($pays as $pay)
	{
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocDate = $pay["PayDate"];
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $pay["PayID"]);
		$result = $eventobj->RegisterEventDoc($pdo);
	}
	echo "Payment : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		return false;
	}
	ob_flush();flush();
	return true;
}

/**
 * رویدادهای بازپرداخت
 */
function BackPay($reqObj , $partObj, &$DocObj, $pdo){
	
	$result = true;
	$backpays = PdoDataAccess::runquery(
			"select * from LON_BackPays
				left join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? 
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
			order by PayDate", array($reqObj->RequestID));
	
	$EventID1 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanBackPay);
	$EventID2 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanBackPayCheque);

	foreach($backpays as $bpay)
	{
		$EventID = ($bpay["IncomeChequeID"]*1 > 0) ? $EventID2 : $EventID1;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocDate = $bpay["PayDate"];
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $bpay["BackPayID"], $bpay["IncomeChequeID"]);
		$result = $eventobj->RegisterEventDoc($pdo);
		if(!$result)
		{
			print_r(ExceptionHandler::PopAllExceptions());
			return false;
		}
		
	}
	echo "BackPay : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		return false;
	}
	ob_flush();flush();
	return true;
}

/**
 * رویدادهای روزانه
 */
function DailyIncome($reqObj , $partObj, $pdo){
	
	$EventID = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanDailyIncome);
	if($EventID == 0)
		return true;
	
	$JFromDate = "1399/01/01";
	$JToDate = "1399/06/29";
	
	$GFromDate = DateModules::shamsi_to_miladi($JFromDate, "-");
	$GToDate = DateModules::shamsi_to_miladi($JToDate, "-");
	
	$EventObj = new ExecuteEvent($EventID);
	$EventObj->DocDate = $GToDate;
	$EventObj->Sources = array($reqObj->RequestID, $partObj->PartID);
	$EventObj->ComputedItems[ "80" ] = 0;
	$EventObj->ComputedItems[ "81" ] = 0;
	unset($EventObj->EventFunction);
	
	$StartComputeDate = $GFromDate;
	$PureArr = LON_Computes::ComputePures($reqObj->RequestID);
	$StartDate = $StartComputeDate == "" ? DateModules::AddToGDate($PureArr[0]["InstallmentDate"],1) : $StartComputeDate;
	$toDate = $GToDate;
	$prevDate = DateModules::AddToGDate($PureArr[0]["InstallmentDate"],1);
	$totalfundWage = $totalAgentWage = 0;
	for($i=1; $i < count($PureArr);$i++)
	{
		if($prevDate > $toDate){
			break;
		}

		if($StartDate > $PureArr[$i]["InstallmentDate"]){
			$prevDate = DateModules::AddToGDate($PureArr[$i]["InstallmentDate"],1);
			continue;
		}

		$tDays = DateModules::GDateMinusGDate($PureArr[$i]["InstallmentDate"],$prevDate);
		$wage = round(($PureArr[$i]["wage"]/$tDays));
		$FundWage = round(($partObj->FundWage/$partObj->CustomerWage))*$wage;
		$AgentWage = $wage - $FundWage;
		$startDay = max($prevDate,$StartDate);
		$enDay = min($PureArr[$i]["InstallmentDate"], $toDate);
		$tDays = DateModules::GDateMinusGDate($enDay,$startDay);

		$totalfundWage += $FundWage*$tDays;
		$totalAgentWage += $AgentWage*$tDays;				
		$prevDate = DateModules::AddToGDate($PureArr[$i]["InstallmentDate"],1);
	}
	$EventObj->ComputedItems[ "80" ] = $totalfundWage;
	$EventObj->ComputedItems[ "81" ] = $totalAgentWage;
	
	echo $totalfundWage . "<br>" . $totalAgentWage;
	//$result = $EventObj->RegisterEventDoc($pdo);
	echo "روزانه : " . $tDays . " days " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	return true;
}

/**
 * 	رویدادهای روزانه تاخیر و جریمه
 */
function DailyWage($reqObj , $partObj, $pdo){
	
	//$JToDate = '1398/12/29';
	$JToDate = "1398/12/29";
	$GToDate = DateModules::shamsi_to_miladi($JToDate, "-");
	$computeArr = LON_Computes::ComputePayments($reqObj->RequestID, $GToDate);
	
	$totalLate = 0;
	$totalPenalty = 0;
	foreach($computeArr as $row)
	{
		if($row["type"] == "installment" && $row["InstallmentID"]*1 > 0)
		{
			$totalLate += $row["totallate"]*1;
			$totalPenalty += $row["totalpnlt"]*1;
		}
	}
	echo $totalLate . "  -  " . $totalPenalty;
	//--------------------------------------
	$JToDate = "1399/06/29";
	$GToDate = DateModules::shamsi_to_miladi($JToDate, "-");
	$computeArr = LON_Computes::ComputePayments($reqObj->RequestID, $GToDate);
	$totalLate2 = 0;
	$totalPenalty2 = 0;
	foreach($computeArr as $row)
	{
		if($row["type"] == "installment" && $row["InstallmentID"]*1 > 0)
		{
			$totalLate2 += $row["totallate"]*1;
			$totalPenalty2 += $row["totalpnlt"]*1;
		}
	}
	$totalLate = $totalLate2 - $totalLate;
	$totalPenalty = $totalPenalty2 - $totalPenalty;
	
	if($totalLate>0)
	{
		$event1 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanDailyLate);
		if($event1 > 0)
		{
			$EventObj1 = new ExecuteEvent($event1);
			$EventObj1->DocDate = $GToDate;
			$EventObj1->ComputedItems[ 82 ] = round(($partObj->FundWage/$partObj->CustomerWage)*$totalLate);
			$EventObj1->ComputedItems[ 83 ] = $totalLate - round(($partObj->FundWage/$partObj->CustomerWage)*$totalLate);
			if($EventObj1->ComputedItems[ 82 ] > 0 || $EventObj1->ComputedItems[ 83 ] > 0)
			{
				$EventObj1->DocDate = DateModules::shNow();
				$EventObj1->Sources = array($reqObj->RequestID, $partObj->PartID, $GToDate);
				$result = $EventObj1->RegisterEventDoc($pdo);
				echo "شناسایی کارمزد تاخیر : " . ($result ? "true" : "false"). "<br>"; 
				if(ExceptionHandler::GetExceptionCount() > 0)
				{
					print_r(ExceptionHandler::PopAllExceptions());
					$pdo->rollBack();
					return;
				}
				ob_flush();flush();
			}
		}
	}
	//-----------------------------------------------------
	if($totalPenalty>0)
	{
		$event2 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanDailyPenalty);
		if($event2 > 0)
		{
			$EventObj2 = new ExecuteEvent($event2);
			$EventObj2->DocDate = $GToDate;
			$EventObj2->ComputedItems[ 84 ] = $partObj->ForfeitPercent == 0? 0 :
					round(($partObj->FundForfeitPercent/$partObj->ForfeitPercent)*$totalPenalty);
			$EventObj2->ComputedItems[ 85 ] = $partObj->ForfeitPercent == 0? 0 : 
					$totalPenalty - round(($partObj->FundForfeitPercent/$partObj->ForfeitPercent)*$totalPenalty);

			if($EventObj2->ComputedItems[ 84 ] > 0 || $EventObj2->ComputedItems[ 85 ] > 0)
			{
				$EventObj2->DocDate = DateModules::shNow();
				$EventObj2->Sources = array($reqObj->RequestID, $partObj->PartID, $GToDate);
				$result = $EventObj2->RegisterEventDoc($pdo);
				echo "شناسایی جریمه تاخیر : " . ($result ? "true" : "false") . "<br>";
				if(ExceptionHandler::GetExceptionCount() > 0)
				{
					print_r(ExceptionHandler::PopAllExceptions());
					$pdo->rollBack();
					return;
				}
				ob_flush();flush();
			}
		}
	}
	return true;
}

function FundIncomeOfAgent(){
	
	//-------------- fund wage of agent -------------------
	$reqs = PdoDataAccess::runquery_fetchMode(" 
		select * from LON_installments join LON_requests using(RequestID)
		where PureFundWage>0 AND InstallmentDate<=now() AND StatusID=" . LON_REQ_STATUS_CONFIRM . 
		" order by InstallmentDate");
	
	$prevComputeDate = null;
	$obj = new ExecuteEvent(1977);
	foreach($reqs as $row)
	{
		if($prevComputeDate != $row["InstallmentDate"])
			unset($obj->DocObj);
		
		$prevComputeDate = $row["InstallmentDate"];
		
		$obj->DocDate = $row["InstallmentDate"];
		$obj->Sources = array($row["RequestID"], $row["PartID"]);
		$obj->AllRowsAmount = $row["PureFundWage"];
		$result = $obj->RegisterEventDoc();
		if(!$result || ExceptionHandler::GetExceptionCount() > 0)
		{
			echo "درآمد سرمایه گذار صندوق  " .  $row["RequestID"] . " : <br>";
			//echo ExceptionHandler::GetExceptionsToString("<br>");
			print_r(ExceptionHandler::PopAllExceptions());
			echo "\n--------------------------------------------\n";
		}
		else
			echo "درآمد سرمایه گذار صندوق  " .  $row["RequestID"] . " : true \n";
	}
	
}
?>
