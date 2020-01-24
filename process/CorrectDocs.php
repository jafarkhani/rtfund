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
error_reporting(0);

$dt = PdoDataAccess::runquery_fetchMode("
	select SourceID1,SourceID2,SourceID3,DocID 
	from ACC_docs join ACC_DocItems using(DocID) 
	where EventID in (151,152,153,154,155,156) and SourceID1>0 
	group by SourceID3");

$LoanComputeArray = array();

while($Reqrow=$dt->fetch())
{
	$RequestID = $Reqrow["SourceID1"];
	$BackPayID = $Reqrow["SourceID3"];
	$DocID = $Reqrow["DocID"];
	
	$pdo->beginTransaction();
	echo "-------------- " . $RequestID . " -----------------<br>";
	ob_flush();flush();
	
	$reqObj = new LON_requests($requset["RequestID"]);
	$partObj = LON_ReqParts::GetValidPartObj($requset["RequestID"]);
	
	/*$DocObj[ $reqObj->RequestID ] = null;
	if($partObj->PartDate >= $GFromDate)
	{
		Allocate($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
		$DocObj[ $reqObj->RequestID ] = null;
		Contract($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
		$DocObj[ $reqObj->RequestID ] = null;
		
	}
	Payment($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	$DocObj[ $reqObj->RequestID ] = null;
	PaymentCheque($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	$DocObj[ $reqObj->RequestID ] = null;
	*/
	BackPayCheques($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	$DocObj[ $reqObj->RequestID ] = null;

	/*BackPay($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	$DocObj[ $reqObj->RequestID ] = null;
	//DailyIncome($reqObj, $partObj, $pdo);
	//DailyWage($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	$DocObj[ $reqObj->RequestID ] = null;
	*/
	//--------------------------------------------------
	$pdo->commit();

	EventComputeItems::$LoanComputeArray = array();
	EventComputeItems::$LoanPuresArray = array();
}
/*$arr = get_defined_vars();
print_r($arr);
die();*/
/**
 * رویداد تخصیص 
 */
function Allocate($reqObj , $partObj, &$DocObj, $pdo){
	
	if($reqObj->ReqPersonID*1 == 0)
		return;
	
	$EventID = EVENT_LOAN_ALLOCATE;

	$eventobj = new ExecuteEvent($EventID);
	$eventobj->DocObj = $DocObj;
	$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID);
	$result = $eventobj->RegisterEventDoc($pdo);
	if($result)
		$DocObj = $eventobj->DocObj;
	echo "تخصیص وام : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	
}

/**
 * رویداد عقد قرارداد
 */
function Contract($reqObj , $partObj, &$DocObj, $pdo){
	
	if($reqObj->ReqPersonID*1 == 0)
		$EventID = EVENT_LOANCONTRACT_innerSource;
	else
	{
		if($reqObj->FundGuarantee == "YES")
			$EventID = EVENT_LOANCONTRACT_agentSource_committal;
		else
			$EventID = EVENT_LOANCONTRACT_agentSource_non_committal;
	}

	$eventobj = new ExecuteEvent($EventID);
	$eventobj->DocObj = $DocObj;
	$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID);
	$result = $eventobj->RegisterEventDoc($pdo);
	if($result)
		$DocObj = $eventobj->DocObj;
	echo "عقد قرارداد : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
}

/**
 * رویدادهای پرداخت وام
 */
function Payment($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GFromDate;
	global $GToDate;
	
	if($reqObj->ReqPersonID*1 > 0)
		$EventID = EVENT_LOANPAYMENT_agentSource;
	else
		$EventID = EVENT_LOANPAYMENT_innerSource;
	
	$pays = PdoDataAccess::runquery("select * from LON_payments "
			. " where PayDate>='$GFromDate' AND PayDate<='$GToDate'  AND RequestID=?", array($reqObj->RequestID));
	
	foreach($pays as $pay)
	{
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $pay["PayDate"];
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $pay["PayID"]);
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
	}
	echo "پرداخت وام : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
}

/**
 * وصول چک وام 
 */
function PaymentCheque($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GFromDate;
	global $GToDate;
	
	$pays = PdoDataAccess::runquery("select * from LON_payments "
			. " where PayDate>='$GFromDate' AND PayDate<='$GToDate' AND RequestID=?", array($reqObj->RequestID));	
	
	foreach($pays as $pay)
	{
		$eventobj = new ExecuteEvent(EVENT_LOANCHEQUE_payed);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $pay["PayDate"];
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $pay["PayID"]);
		$eventobj->AllRowsAmount = $pay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);		
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
	}
	echo "وصول چک وام : " . ($result ? "true" : "false") . "<br>";
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
function BackPayCheques($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GFromDate;
	$result = true;
	$cheques = PdoDataAccess::runquery(
			"select *,substr(ATS,1,10) regDate from LON_BackPays
                join ACC_IncomeCheques i using(IncomeChequeID) 
                join ACC_ChequeHistory h on(i.IncomeChequeID=h.IncomeChequeID) 
                where RequestID=? AND substr(ATS,1,10)>='$GFromDate' AND i.ChequeStatus=".INCOMECHEQUE_NOTVOSUL." order by ATS"
			, array($reqObj->RequestID));
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_LOANCHEQUE_agentSource;
		else
			$EventID = EVENT_LOANCHEQUE_innerSource;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
	}
	echo "دریافت چک : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	//--------------در جریان وصول------------------------
	$cheques = PdoDataAccess::runquery(
			"select *,substr(ATS,1,10) regDate from LON_BackPays
                join ACC_IncomeCheques i using(IncomeChequeID) 
                join ACC_ChequeHistory h on(i.IncomeChequeID=h.IncomeChequeID) 
                where RequestID=? AND substr(ATS,1,10)>='$GFromDate' AND i.ChequeStatus<>".INCOMECHEQUE_NOTVOSUL." order by PayDate"
			, array($reqObj->RequestID));
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_SENDTOBANK_agent;
		else
			$EventID = EVENT_CHEQUE_SENDTOBANK_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
	}
	echo " چکهای درجریان وصول : " . ($result ? "true" : "false") . "<br>";
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
                where RequestID=? AND substr(ATS,1,10)>='$GFromDate' AND i.ChequeStatus=".INCOMECHEQUE_BARGASHTI." order by PayDate"
			, array($reqObj->RequestID));
	
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_BARGASHT_agent;
		else
			$EventID = EVENT_CHEQUE_BARGASHT_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
	}
	echo " چکهای برگشتی : " . ($result ? "true" : "false") . "<br>";
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
                where RequestID=? AND substr(ATS,1,10)>='$GFromDate' AND ChequeStatus in(".INCOMECHEQUE_CHANGE.",".INCOMECHEQUE_MOSTARAD.") order by PayDate"
			, array($reqObj->RequestID));
	
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_MOSTARAD_agent;
		else
			$EventID = EVENT_CHEQUE_MOSTARAD_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
	}
	echo " چکهای مسترد : " . ($result ? "true" : "false") . "<br>";
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
                where RequestID=? AND substr(ATS,1,10)>='$GFromDate' AND ChequeStatus=".INCOMECHEQUE_BARGASHTI_MOSTARAD." order by PayDate"
			, array($reqObj->RequestID));
	foreach($cheques as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
			$EventID = EVENT_CHEQUE_BARGASHTMOSTARAD_agent;
		else
			$EventID = EVENT_CHEQUE_BARGASHTMOSTARAD_inner;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $bpay["IncomeChequeID"]);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $bpay["regDate"];
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
	}
	echo " چکهای برگشتی مسترد : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
}

/**
 * رویدادهای بازپرداخت
 */
function BackPay($reqObj , $partObj, &$DocObj, $pdo){
	global $GFromDate;
	$result = true;
	$backpays = PdoDataAccess::runquery(
			"select * from LON_BackPays
				left join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND PayDate>='$GFromDate'
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
			order by PayDate", array($reqObj->RequestID));
	
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
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $bpay["BackPayID"]);
		$eventobj->DocObj = $DocObj;
		$eventobj->DocDate = $bpay["PayDate"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
		$DocObj = null;
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

?>
