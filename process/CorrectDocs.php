<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.12
//---------------------------
 
require_once '../header.inc.php';
require_once '../commitment/ExecuteEvent.class.php';
require_once '../loan/request/request.class.php';

ini_set("display_errors", "On");
ini_set('max_execution_time', 30000);
ini_set('memory_limit','2000M');
header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);

$dt = PdoDataAccess::runquery_fetchMode("
	/*select SourceID1 RequestID,SourceID2 PartID,SourceID3 BackPayID,DocID 
	from ACC_docs join ACC_DocItems using(DocID) 
	where EventID in (151,152,153,154,155,156) and SourceID1>0 
	group by SourceID3*/
	select IncomeChequeID,r.RequestID,BackPayID,0 DocID from ACC_IncomeCheques i join LON_requests r on(i.LoanRequestID=r.RequestID)
	join LON_BackPays using(IncomeChequeID) 
	where ChequeDate>= '2019-03-21'
	group by IncomeChequeID
	
");

$LoanComputeArray = array();

while($Reqrow=$dt->fetch())
{
	$RequestID = $Reqrow["RequestID"];
	$BackPayID = $Reqrow["BackPayID"];
	$DocID = $Reqrow["DocID"];
	
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	echo "-------------- " . $RequestID . " -----------------<br>";
	ob_flush();flush();
	
	$reqObj = new LON_requests($RequestID);
	
	BackPay($reqObj, $BackPayID, $DocID, $pdo);
	BackPayCheques($reqObj, $Reqrow["IncomeChequeID"], $pdo );
	//--------------------------------------------------
	$pdo->commit();
}

print_r(ExceptionHandler::PopAllExceptions());

/**
 * رویدادهای بازپرداخت
 */
function BackPay($reqObj, $BackPayID, $DocID, $pdo){
	$result = true;
	$backpays = PdoDataAccess::runquery(
			"select * from LON_BackPays b
				left join ACC_IncomeCheques i using(IncomeChequeID) 
				join LON_ReqParts p on(p.IsHistory='NO' and p.RequestID=b.RequestID)
				where b.BackPayID=? 
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
			order by PayDate", array($BackPayID));
	
	foreach($backpays as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
		{
			if($reqObj->FundGuarantee == "YES")
			{
				if($bpay["IncomeChequeID"]*1 > 0)
					$EventID = EVENT_LOANBACKPAY_agentSource_committal_cheque;
				else
					$EventID = EVENT_LOANBACKPAY_agentSource_committal_non_cheque;
			}
			else
			{
				if($bpay["IncomeChequeID"]*1 > 0)
					$EventID = EVENT_LOANBACKPAY_agentSource_non_committal_cheque;
				else
					$EventID = EVENT_LOANBACKPAY_agentSource_non_committal_non_cheque;
			}
		}
		else
		{
			if($bpay["IncomeChequeID"]*1 > 0)
				$EventID = EVENT_LOANBACKPAY_innerSource_cheque;
			else
				$EventID = EVENT_LOANBACKPAY_innerSource_non_cheque;
		}
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($bpay["RequestID"], $bpay["PartID"], $bpay["BackPayID"]);
		if($DocID*1 > 0)
			$eventobj->DocObj = new ACC_docs($DocID);
		$eventobj->DocDate = $bpay["PayDate"];
		$result = $eventobj->RegisterEventDoc($pdo);
	}
	echo "بازپرداخت : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
}

/**
 * رویدادهای دریافت چک
 */
function BackPayCheques($reqObj, $IncomeChequeID, $pdo){
	
	$result = true;
	$cheques = PdoDataAccess::runquery(
			"select * from LON_BackPays
                join ACC_IncomeCheques i using(IncomeChequeID) 
                where i.IncomeChequeID=? AND i.ChequeStatus=".INCOMECHEQUE_NOTVOSUL.
			" group by i.IncomeChequeID "
			, array($IncomeChequeID));
	echo "دریافت چک : ";
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_LOANCHEQUE_agentSource;
		else
			$EventID = EVENT_LOANCHEQUE_innerSource;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocDate = $bpay["ChequeDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		echo $eventobj->DocObj->LocalNo . "\n";
		
	}
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	//--------------در جریان وصول------------------------
	$cheques = PdoDataAccess::runquery(
			"select * from LON_BackPays
                join ACC_IncomeCheques i using(IncomeChequeID) 
                where i.IncomeChequeID=? AND i.ChequeStatus<>".INCOMECHEQUE_NOTVOSUL.
			" group by i.IncomeChequeID order by PayDate"
			, array($IncomeChequeID));
	echo " چکهای درجریان وصول : ";
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_SENDTOBANK_agent;
		else
			$EventID = EVENT_CHEQUE_SENDTOBANK_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocDate = $bpay["ChequeDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		echo $eventobj->DocObj->LocalNo . "\n";
	}
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	//--------------برگشتی------------------------
	$cheques = PdoDataAccess::runquery(
			"select *,substr(ATS,1,10) regDate from LON_BackPays
                join ACC_IncomeCheques i using(IncomeChequeID) 
                join ACC_ChequeHistory h on(i.IncomeChequeID=h.IncomeChequeID) 
                where i.IncomeChequeID=? AND i.ChequeStatus=".INCOMECHEQUE_BARGASHTI.
			" group by i.IncomeChequeID order by PayDate"
			, array($IncomeChequeID));
	echo " چکهای برگشتی : ";
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_BARGASHT_agent;
		else
			$EventID = EVENT_CHEQUE_BARGASHT_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		echo $eventobj->DocObj->LocalNo . "\n";
	}
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	//----------------مسترد و تعویض----------------------------
	$cheques = PdoDataAccess::runquery(
			"select *,substr(ATS,1,10) regDate from LON_BackPays
                join ACC_IncomeCheques i using(IncomeChequeID) 
                join ACC_ChequeHistory h on(i.IncomeChequeID=h.IncomeChequeID) 
                where i.IncomeChequeID=? AND ChequeStatus in(".INCOMECHEQUE_CHANGE.",".INCOMECHEQUE_MOSTARAD.") "
			. " group by i.IncomeChequeID order by PayDate"
			, array($IncomeChequeID));
	echo " چکهای مسترد : " ;
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_MOSTARAD_agent;
		else
			$EventID = EVENT_CHEQUE_MOSTARAD_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		echo $eventobj->DocObj->LocalNo . "\n";
	}
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	//-------------------برگشتی مسترد-------------------------
	$cheques = PdoDataAccess::runquery(
			"select *,substr(ATS,1,10) regDate from LON_BackPays
                join ACC_IncomeCheques i using(IncomeChequeID) 
                join ACC_ChequeHistory h on(i.IncomeChequeID=h.IncomeChequeID) 
                where i.IncomeChequeID=? AND ChequeStatus=".INCOMECHEQUE_BARGASHTI_MOSTARAD.
			" group by i.IncomeChequeID order by PayDate"
			, array($IncomeChequeID));
	echo " چکهای برگشتی مسترد : " ;
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_BARGASHTMOSTARAD_agent;
		else
			$EventID = EVENT_CHEQUE_BARGASHTMOSTARAD_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		echo $eventobj->DocObj->LocalNo . "\n";
	}
	
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
}

?>
