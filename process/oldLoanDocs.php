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

DailyIncome();die();
//FundIncomeOfAgent();die();
/*
echo "---------------------";
PdoDataAccess::runquery("
	
	insert into ACC_DocItems(DocID,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
			TafsiliType3,TafsiliID3,
			DebtorAmount,CreditorAmount,locked,
			param1,param2,param3,
			SourceID1,SourceID2,SourceID3,SourceID4)
		select 67130,CostID,TafsiliType,TafsiliID,TafsiliType2,TafsiliID2,
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


global $GToDate;
//$GToDate = '2018-03-20'; //1396/12/29
$GToDate = '2020-02-22'; //1397/12/29

$reqs = PdoDataAccess::runquery_fetchMode(" select c1 as RequestID from aaa
		where c1=2184
		order by c1 ");
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
	*/
	$result = Payment($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	if(!$result)
	{
		$pdo->rollBack();
		continue;
	}
	
	/*$result = BackPay($reqObj, $partObj, $requset["UID"], $pdo);
	if(!$result)
	{
		if($pdo->inTransaction())
			$pdo->rollBack();
		continue;
	}*/
	/*$result = DailyIncome($reqObj, $partObj, $pdo);
	if(!$result)
	{
		if($pdo->inTransaction())
			$pdo->rollBack();
		continue;
	}
	$result = DailyWage($reqObj, $partObj, $pdo);
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
function Contract(){

	$pays =PdoDataAccess::runquery("
		select c1 RequestID from aaa join LON_ReqParts on(IsHistory='NO' AND RequestID=c1) 
		left join (select SourceID1,DociD,LocalNo 
					from ACC_docs join COM_events using(EventID) 
					join ACC_DocItems using(DociD)
					where cycleID>=1398 and EventType='LoanContract'
					group by SourceID1)t on(SourceID1=c1) 
					
		where t.DocID is null AND PartDate>='2019-03-21'");
	
	foreach($pays as $pay)
	{
		$reqObj = new LON_requests($pay["RequestID"]);
		$partObj = LON_ReqParts::GetValidPartObj($pay["RequestID"]);
		$EventID = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanContract);

		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocDate = $partObj->PartDate;
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID);
		$result = $eventobj->RegisterEventDoc();
		echo $reqObj->RequestID . " Contract : " . ($result ? "true" : "false") . "<br>";
		ob_flush();flush();
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			print_r(ExceptionHandler::PopAllExceptions());
			return false;
		}
	}
	return true;

}

/**
 * رویدادهای پرداخت وام
 */
function Payment(){
	
	//$pdo = PdoDataAccess::getPdoObject();
	//$pdo->beginTransaction();
	
	$pays =PdoDataAccess::runquery("
		select p.* from sajakrrt_rtfund.LON_payments p join sajakrrt_rtfund3.LON_PayDocs d2 using(PayID)
		left join sajakrrt_rtfund.LON_PayDocs d1 on(p.PayID=d1.PayID)
		where PayDate>='2019-03-21' AND d1.PayID is null");
	
	foreach($pays as $pay)
	{
		$reqObj = new LON_requests($pay["RequestID"]);
		$partObj = LON_ReqParts::GetValidPartObj($pay["RequestID"]);
		$EventID = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanPayment);
	
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocDate = $pay["PayDate"];
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $pay["PayID"]);
		$result = $eventobj->RegisterEventDoc();
		echo "Payment : " . ($result ? "true" : "false") . "<br>";
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			print_r(ExceptionHandler::PopAllExceptions());
		}
	}
	
	ob_flush();flush();
	return true;
}

/**
 * رویدادهای بازپرداخت
 */
function BackPay(){
	
	$backpays = PdoDataAccess::runquery(" 
		select p.* from sajakrrt_rtfund.LON_BackPays p join sajakrrt_rtfund3.LON_BackPayDocs d2 using(BackPayID)
		left join sajakrrt_rtfund.LON_BackPayDocs d1 on(p.BackPayID=d1.BackPayID)
		where PayDate>='2019-03-21' AND d1.BackPayID is null");


	foreach($backpays as $bpay)
	{
		$reqObj = new LON_requests($bpay["RequestID"]);
		$partObj = LON_ReqParts::GetValidPartObj($bpay["RequestID"]);
		$EventID1 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanBackPay);
		$EventID2 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanBackPayCheque);
		$EventID = ($bpay["IncomeChequeID"]*1 > 0) ? $EventID2 : $EventID1;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocDate = $bpay["PayDate"];
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $bpay["BackPayID"], $bpay["IncomeChequeID"]);
		$result = $eventobj->RegisterEventDoc();
		if(!$result)
		{
			print_r(ExceptionHandler::PopAllExceptions());
			return false;
		}
		echo "BackPay " . $reqObj->RequestID . " : " . ($result ? "true" : "false") . "<br>";
	}
	
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
function DailyIncome(){
	
	$dt = PdoDataAccess::runquery("select c1 RequestID from aaa where regDoc=0");
	foreach($dt as $row)
	{
		echo $row["RequestID"] . ":<br>";
		
		$reqObj = new LON_requests($row["RequestID"]);
		$partObj = LON_ReqParts::GetValidPartObj($row["RequestID"]);
		$EventID = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanDailyIncome);
		if($EventID == 0)
			continue;

		$JToDate = "1398/12/29";
		$GToDate = DateModules::shamsi_to_miladi($JToDate, "-");
		$JFromDate = "1398/01/01";
		$GFromDate = DateModules::shamsi_to_miladi($JFromDate, "-");
		
		$PureArr = LON_requests::ComputePures($reqObj->RequestID);
		$ComputeDate = DateModules::AddToGDate($PureArr[0]["InstallmentDate"],1);
		
		$days = 0;
		$EventObj = new ExecuteEvent($EventID);
		$EventObj->DocDate = $GToDate;
		$EventObj->Sources = array($reqObj->RequestID, $partObj->PartID);
		$EventObj->ComputedItems[ "80" ] = 0;
		$EventObj->ComputedItems[ "81" ] = 0;
		unset($EventObj->EventFunction);
		for($i=1; $i < count($PureArr);$i++)
		{
			if($PureArr[$i]["InstallmentDate"] < $GFromDate )
				continue;
				
			if($PureArr[$i]["InstallmentDate"] > $GToDate && 
					$PureArr[$i-1]["InstallmentDate"] > $GToDate)
				break;
			
			$days = DateModules::GDateMinusGDate(min($GToDate, $PureArr[$i]["InstallmentDate"]),
					max($PureArr[$i-1]["InstallmentDate"], $GFromDate));
			$totalDays = DateModules::GDateMinusGDate($PureArr[$i]["InstallmentDate"],$PureArr[$i-1]["InstallmentDate"]);
			$wage = round(($PureArr[$i]["wage"]/$totalDays)*$days);
			$FundWage = round(($partObj->FundWage/$partObj->CustomerWage)*$wage);
			$AgentWage = $wage - $FundWage;
			$EventObj->ComputedItems[ "80" ] += $FundWage;
			$EventObj->ComputedItems[ "81" ] += $AgentWage;
			echo $days . " - ";
		}
		$result = $EventObj->RegisterEventDoc();
		echo "روزانه : " . ($result ? "true" : "false") . "<br>";
		if(ExceptionHandler::GetExceptionCount() > 0)
		{
			print_r(ExceptionHandler::PopAllExceptions());
		}
		else
			 PdoDataAccess::runquery("update aaa set regDoc=1 where c1=" . $row["RequestID"]);
		ob_flush();flush();
	}
	//.............................................
	return true;
	
	$JToDate = "1399/04/10";
	$GToDate = DateModules::shamsi_to_miladi($JToDate, "-");
	
	$EventObj = new ExecuteEvent($EventID);
	$EventObj->DocDate = $GToDate;
	$EventObj->Sources = array($reqObj->RequestID, $partObj->PartID);
	$EventObj->ComputedItems[ "80" ] = 0;
	$EventObj->ComputedItems[ "81" ] = 0;
	unset($EventObj->EventFunction);
	
	$PureArr = LON_requests::ComputePures($reqObj->RequestID);
	$ComputeDate = DateModules::AddToGDate($PureArr[0]["InstallmentDate"],1);
	
	$JFromDate = "1399/01/01";
	$GFromDate = DateModules::shamsi_to_miladi($JFromDate, "-");
	
	$days = 0;
	for($i=1; $i < count($PureArr);$i++)
	{
		if( $ComputeDate < $GFromDate || $ComputeDate > $GToDate)
			break;
		$days = DateModules::GDateMinusGDate(min($GToDate, $PureArr[$i]["InstallmentDate"]),$ComputeDate);
		$totalDays = DateModules::GDateMinusGDate($PureArr[$i]["InstallmentDate"],$ComputeDate);
		$wage = round(($PureArr[$i]["wage"]/$totalDays)*$days);
		$FundWage = round(($partObj->FundWage/$partObj->CustomerWage)*$wage);
		$AgentWage = $wage - $FundWage;
		$EventObj->ComputedItems[ "80" ] += $FundWage;
		$EventObj->ComputedItems[ "81" ] += $AgentWage;
		$ComputeDate = min($GToDate, $PureArr[$i]["InstallmentDate"]);
	}
	echo $EventObj->ComputedItems[ "80" ] . "<br>" . $EventObj->ComputedItems[ "81" ];
	$result = $EventObj->RegisterEventDoc($pdo);
	echo "روزانه : " . $days . " days " . ($result ? "true" : "false") . "<br>";
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
	
	$JToDate = '1397/12/29';	
	$GToDate = DateModules::shamsi_to_miladi($JToDate, "-");
	
	$result = true;
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
	//--------------------------------------
	
	$JToDate = "1398/12/29";
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
		where PureFundWage>0 AND InstallmentDate<='2020-03-19' AND InstallmentDate>='2019-03-21' AND StatusID=" . LON_REQ_STATUS_CONFIRM . 
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
