<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.12
//---------------------------
 
require_once '../header.inc.php';
require_once '../commitment/ExecuteEvent.class.php';
require_once '../loan/request/request.class.php';

ini_set('max_execution_time', 3000);
ini_set('memory_limit','1000M');

global $GToDate;
$GToDate = '2018-03-21'; //1397/01/01
//$GToDate = '2019-03-21'; //1398/01/01

$reqs = PdoDataAccess::runquery(" select r.RequestID from LON_requests r
	join LON_ReqParts p on(r.RequestID=p.RequestID AND IsHistory='NO')
	where PartDate<'$GToDate' 
		AND ComputeMode='NEW' AND IsEnded='NO' AND StatusID=" . LON_REQ_STATUS_CONFIRM . " 
		order by RequestID");
//print_r(ExceptionHandler::PopAllExceptions());
$pdo = PdoDataAccess::getPdoObject();

$AllocDocObj = $ContractDocObj = $PaymentDocObj = $PaymentChequeDocObj = 
		$BackPayChequesDocObj = $BackPayDocObj = $DailyIncomeDocObj = $DailyWageDocObj = null;

foreach($reqs as $requset)
{
	$pdo->beginTransaction();
	echo "-------------- " . $requset["RequestID"] . " -----------------<br>";
	ob_flush();flush();
	
	$reqObj = new LON_requests($requset["RequestID"]);
	$partObj = LON_ReqParts::GetValidPartObj($requset["RequestID"]);
	
	Allocate($reqObj, $partObj, $AllocDocObj, $pdo);
//	Contract($reqObj, $partObj, $ContractDocObj, $pdo);
//	Payment($reqObj, $partObj, $PaymentDocObj, $pdo);
//	PaymentCheque($reqObj, $partObj, $PaymentChequeDocObj, $pdo);
//	BackPayCheques($reqObj, $partObj, $BackPayChequesDocObj, $pdo);
//	BackPay($reqObj, $partObj, $BackPayDocObj, $pdo);
//	DailyIncome($reqObj, $partObj, $DailyIncomeDocObj, $pdo);
//	DailyWage($reqObj, $partObj, $DailyWageDocObj, $pdo);
	
	//--------------------------------------------------
	$pdo->commit();
	
	EventComputeItems::$LoanComputeArray = array();
	EventComputeItems::$LoanPuresArray = array();
}

/**
 * رویداد تخصیص 
 */
function Allocate($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;
	
	$EventID = EVENT_LOAN_ALLOCATE;

	$eventobj = new ExecuteEvent($EventID);
	$eventobj->DocObj = $DocObj;
	$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID);
	$result = $eventobj->RegisterEventDoc($pdo);
	
	echo "تخصیص وام : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	$DocObj = $eventobj->DocObj;
}

/**
 * رویداد عقد قرارداد
 */
function Contract($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;
	
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
	
	echo "عقد قرارداد : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	$DocObj = $eventobj->DocObj;
}

/**
 * رویدادهای پرداخت وام
 */
function Payment($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;
	
	if($reqObj->ReqPersonID*1 > 0)
		$EventID = EVENT_LOANPAYMENT_agentSource;
	else
		$EventID = EVENT_LOANPAYMENT_innerSource;
	
	$pays = PdoDataAccess::runquery("select * from LON_payments "
			. " where PayDate<'$GToDate' AND RequestID=?", array($reqObj->RequestID));
	
	$eventobj = new ExecuteEvent($EventID);
	foreach($pays as $pay)
	{
		$eventobj->DocObj = $DocObj;
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $pay["PayID"]);
		$result = $eventobj->RegisterEventDoc($pdo);
	}
	echo "پرداخت وام : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	$DocObj = $eventobj->DocObj;
	ob_flush();flush();
}

/**
 * وصول چک وام 
 */
function PaymentCheque($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;
	
	$pays = PdoDataAccess::runquery("select * from LON_payments "
			. " where PayDate<'$GToDate' AND RequestID=?", array($reqObj->RequestID));
	
	if($reqObj->ReqPersonID*1 > 0)
		$EventID = EVENT_LOANPAYMENT_agentSource;
	else
		$EventID = EVENT_LOANPAYMENT_innerSource;
		
	$eventobj = new ExecuteEvent(EVENT_LOANCHEQUE_payed);
	foreach($pays as $pay)
	{
		$eventobj->DocObj = $DocObj;
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $pay["PayID"]);
		$eventobj->AllRowsAmount = $pay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);		
	}
	echo "وصول چک وام : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	$DocObj = $eventobj->DocObj;
	ob_flush();flush();
}

/**
 * رویدادهای دریافت چک
 */
function BackPayCheques($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;
	
	$cheques = PdoDataAccess::runquery(
			"select * from LON_BackPays
				join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND PayDate<'$GToDate'			
			order by PayDate"
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
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
	}
	echo "دریافت چک : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	$DocObj = $eventobj->DocObj;
	ob_flush();flush();
}

/**
 * رویدادهای بازپرداخت
 */
function BackPay($reqObj , $partObj, &$DocObj, $pdo){
	global $GToDate;
	
	$backpays = PdoDataAccess::runquery(
			"select * from LON_BackPays
				left join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND PayDate<'$GToDate'
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
			order by PayDate"
			, array($reqObj->RequestID));
	$DocObj = null;
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
		$result = $eventobj->RegisterEventDoc($pdo);
	}
	echo "بازپرداخت : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	$DocObj = $eventobj->DocObj;
	ob_flush();flush();
}

/**
 * رویدادهای روزانه
 */
function DailyIncome($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;
	
	$days = PdoDataAccess::runquery_fetchMode("select * from jdate where Jdate between ? AND '1397/12/29'", 
			DateModules::miladi_to_shamsi($partObj->PartDate), $pdo);
	echo "days : " . $days->rowCount() . "<br>";
	ob_flush();flush();
	echo " ";
	ob_flush();flush();
	if($reqObj->ReqPersonID*1 == 0)
		$EventID = EVENT_LOANDAILY_innerSource;
	else
	{
		if($reqObj->FundGuarantee == "YES")
			$EventID = EVENT_LOANDAILY_agentSource_committal;
		else
			$EventID = EVENT_LOANDAILY_agentSource_non_committal;
	}
	$EventObj = new ExecuteEvent($EventID);
	while($day = $days->fetch())
	{
		$EventObj->Sources = array($reqObj->RequestID, $partObj->PartID, $day["gdate"]);
		$result = $EventObj->RegisterEventDoc($pdo);
	}
	echo "روزانه : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
}

/**
 * 	رویدادهای روزانه تاخیر و جریمه
 */
function DailyWage($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;	
	
	$computeArr = LON_Computes::NewComputePayments($reqObj->RequestID, $GToDate);
	$totalLate = 0;
	$totalPenalty = 0;
	foreach($computeArr as $row)
	{
		if($row["type"] == "installment")
		{
			$totalLate += $row["late"]*1;
			$totalPenalty += $row["pnlt"]*1;
		}
	}
	
	$EventID = $reqObj->ReqPersonID*1 == 0 ? EVENT_LOANDAILY_innerLate : EVENT_LOANDAILY_agentlate;
	$EventObj = new ExecuteEvent($EventID);
	$EventObj->AllRowsAmount = $totalLate;
	$EventObj->Sources = array($reqObj->RequestID, $partObj->PartID, $GToDate);
	if($EventObj->AllRowsAmount > 0)
		$result = $EventObj->RegisterEventDoc($pdo);
	echo "شناسایی کارمزد تاخیر : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
	
	$EventID = $reqObj->ReqPersonID*1 == 0 ? EVENT_LOANDAILY_innerPenalty : EVENT_LOANDAILY_agentPenalty;
	$EventObj = new ExecuteEvent($EventID);
	$EventObj->AllRowsAmount = $totalPenalty;
	$EventObj->Sources = array($reqObj->RequestID, $partObj->PartID, $GToDate);
	if($EventObj->AllRowsAmount > 0)
		$result = $EventObj->RegisterEventDoc($pdo);
	echo "شناسایی جریمه تاخیر : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();
}
?>
