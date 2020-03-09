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
ini_set('max_execution_time', 30000);
ini_set('memory_limit','2000M');
header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);


/*FundIncomeOfAgent();
die();*/

global $GToDate;
//$GToDate = '2018-03-20'; //1396/12/29
$GToDate = '2020-02-22'; //1397/12/29

$reqs = PdoDataAccess::runquery_fetchMode(" select DocID as RequestID from aa where regDoc=0 
		 order by DocID");
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
	
	//Allocate($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	$result = Contract($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	if(!$result)
	{
		$pdo->rollBack();
		continue;
	}
	
	/*$result = Payment($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	if(!$result)
	{
		$pdo->rollBack();
		continue;
	}*/
	
	/*$result = BackPay($reqObj, $partObj, $DocObj[ $reqObj->RequestID ], $pdo);
	if(!$result)
	{
		if($pdo->inTransaction())
			$pdo->rollBack();
		continue;
	}*/
	
	//DailyIncome($reqObj, $partObj, $pdo);
	//DailyWage($reqObj, $partObj, $pdo);
	//$DocObj[ $reqObj->RequestID ] = null;
	 
	//--------------------------------------------------
	PdoDataAccess::runquery_fetchMode(" update aa set regDoc=1 where DocID=?", array($reqObj->RequestID), $pdo);
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
	echo "Allocate : " . ($result ? "true" : "false") . "<br>";
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
			from krrtfir_rtfund.LON_PayDocs join LON_payments using(PayID)
			where RequestID=:rid
			union 
        
			select PayID,RequestID,PayDate  from LON_payments 
			where RequestID=:rid AND PayDate<'2017-03-21'
			"
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
 * وصول چک وام 
 */
function PaymentCheque($reqObj , $partObj, &$DocObj, $pdo){
	
	global $GToDate;
	
	$pays = PdoDataAccess::runquery("select * from LON_payments "
			. " where PayDate<='$GToDate' AND RequestID=?", array($reqObj->RequestID));	
	
	foreach($pays as $pay)
	{
		$eventobj = new ExecuteEvent(EVENT_LOANCHEQUE_payed);
		$eventobj->DocObj = $DocObj;
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $pay["PayID"]);
		$eventobj->AllRowsAmount = $pay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);		
		if($result)
			$DocObj = $eventobj->DocObj;
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
	
	global $GToDate;
	$result = true;
	$cheques = PdoDataAccess::runquery(
			"select * from LON_BackPays
				join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? order by PayDate"
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
		if($result)
			$DocObj = $eventobj->DocObj;
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
			"select * from LON_BackPays
				join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND ChequeStatus <>".INCOMECHEQUE_NOTVOSUL." order by PayDate"
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
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
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
			"select * from LON_BackPays
				join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND ChequeStatus=".INCOMECHEQUE_BARGASHTI." order by PayDate"
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
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
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
			"select * from LON_BackPays
				join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND ChequeStatus in(".INCOMECHEQUE_CHANGE.",".INCOMECHEQUE_MOSTARAD.")
				order by PayDate"
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
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
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
			"select * from LON_BackPays
				join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND ChequeStatus=".INCOMECHEQUE_BARGASHTI_MOSTARAD." order by PayDate"
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
		$eventobj->AllRowsAmount = $bpay["PayAmount"];
		$result = $eventobj->RegisterEventDoc($pdo);
		if($result)
			$DocObj = $eventobj->DocObj;
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
	global $GToDate;
	$result = true;
	$backpays = PdoDataAccess::runquery(
			"select * from LON_BackPays
				left join ACC_IncomeCheques i using(IncomeChequeID) 
				where RequestID=? AND PayDate<='$GToDate'
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
			order by PayDate", array($reqObj->RequestID));
	
	$EventID1 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanBackPay);
	$EventID2 = LON_requests::GetEventID($reqObj->RequestID, EVENTTYPE_LoanBackPayCheque);

	foreach($backpays as $bpay)
	{
		$EventID = ($bpay["IncomeChequeID"]*1 > 0) ? $EventID2 : $EventID1;
		
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->DocDate = $bpay["PayDate"];
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $bpay["BackPayID"]);
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
	
	/*$JFromDate = $partObj->PartDate;
	$JToDate = "1397/12/29";*/
	
	$JFromDate = "1398/01/01";
	$JToDate = DateModules::shNow();
	
	$GFromDate = DateModules::shamsi_to_miladi($JFromDate, "-");
	$GToDate = DateModules::shamsi_to_miladi($JToDate, "-");
	
	$EventObj = new ExecuteEvent($EventID);
	$EventObj->DocDate = $GToDate;
	$EventObj->Sources = array($reqObj->RequestID, $partObj->PartID);
	$EventObj->ComputedItems[ "80" ] = 0;
	$EventObj->ComputedItems[ "81" ] = 0;
	unset($EventObj->EventFunction);
	
	$PureArr = LON_requests::ComputePures($reqObj->RequestID);
	$ComputeDate = DateModules::AddToGDate($PureArr[0]["InstallmentDate"],1);
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
	$result = $EventObj->RegisterEventDoc($pdo);
	echo "روزانه : " . $days . " days " . ($result ? "true" : "false") . "<br>";
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
	if($totalLate>0)
	{
		$event1 = LON_requests::GetEventID($reqObj->ReqPersonID, EVENTTYPE_LoanDailyLate);
		$EventObj1 = new ExecuteEvent($event1);
		$EventObj1->ComputedItems[ 82 ] = round(($partObj->FundWage/$partObj->CustomerWage)*$totalLate);
		$EventObj1->ComputedItems[ 83 ] = $totalLate - round(($partObj->FundWage/$partObj->CustomerWage)*$totalLate);
		if($EventObj1->ComputedItems[ 82 ] > 0 || $EventObj1->ComputedItems[ 83 ] > 0)
		{
			$EventObj1->DocDate = $GToDate;
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
	//-----------------------------------------------------
	if($totalPenalty>0)
	{
		$event2 = LON_requests::GetEventID($reqObj->ReqPersonID, EVENTTYPE_LoanDailyPenalty);
		$EventObj2 = new ExecuteEvent($event2);
		$EventObj2->ComputedItems[ 84 ] = $partObj->ForfeitPercent == 0? 0 :
				round(($partObj->FundForfeitPercent/$partObj->ForfeitPercent)*$totalPenalty);
		$EventObj2->ComputedItems[ 85 ] = $partObj->ForfeitPercent == 0? 0 : 
				$totalPenalty - round(($partObj->FundForfeitPercent/$partObj->ForfeitPercent)*$totalPenalty);

		if($EventObj2->ComputedItems[ 84 ] > 0 || $EventObj2->ComputedItems[ 85 ] > 0)
		{
			$EventObj2->DocDate = $GToDate;
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
	//--------------------------------------
	$computeArr = LON_Computes::ComputePayments($reqObj->RequestID);
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
		$event1 = LON_requests::GetEventID($reqObj->ReqPersonID, EVENTTYPE_LoanDailyLate);
		$EventObj1 = new ExecuteEvent($event1);
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
	//-----------------------------------------------------
	if($totalPenalty>0)
	{
		$event2 = LON_requests::GetEventID($reqObj->ReqPersonID, EVENTTYPE_LoanDailyPenalty);
		$EventObj2 = new ExecuteEvent($event2);
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

function FundIncomeOfAgent(){
	
	//-------------- fund wage of agent -------------------
	$reqs = PdoDataAccess::runquery_fetchMode(" select * from LON_installments join LON_requests using(RequestID)
		where PureFundWage>0 AND InstallmentDate<=now() AND StatusID=" . LON_REQ_STATUS_CONFIRM . " order by InstallmentDate");
	
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
