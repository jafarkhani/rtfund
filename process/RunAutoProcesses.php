<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.12
//---------------------------

ini_set("display_errors", "On");
ini_set('max_execution_time', 30000000);
ini_set('memory_limit','4000M');
set_time_limit(0); 
ini_set("default_socket_timeout", 600);

ob_start();

require_once '../framework/configurations.inc.php';
set_include_path(DOCUMENT_ROOT . "/generalClasses");

require_once '../definitions.inc.php';
require_once DOCUMENT_ROOT . '/generalClasses/InputValidation.class.php';
require_once DOCUMENT_ROOT . '/generalClasses/PDODataAccess.class.php';
require_once DOCUMENT_ROOT . '/generalClasses/DataAudit.class.php';
require_once DOCUMENT_ROOT . '/generalClasses/DateModules.class.php';

define("SYSTEMID", 1);
session_start();
$_SESSION["USER"] = array("PersonID" => 1000);
$_SESSION['LIPAddress'] = '';
$_SESSION["accounting"]["CycleID"] = DateModules::GetYear(DateModules::shNow());

require_once '../office/dms/dms.class.php';
require_once '../loan/request/request.class.php';
require_once '../commitment/ExecuteEvent.class.php';

$query = " select * from LON_requests  r
join LON_ReqParts p on(r.RequestID=p.RequestID AND IsHistory='NO')
where r.RequestID>0 AND StatusID=" . LON_REQ_STATUS_CONFIRM ;

$reqs = PdoDataAccess::runquery_fetchMode($query);

$objArr = array();

$pdo = PdoDataAccess::getPdoObject();
$pdo->beginTransaction();
	
$ComputeDate = DateModules::Now();

echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8"><body dir=rtl>';
echo "<br>****************************<BR>" . DateModules::miladi_to_shamsi($ComputeDate) . 
		"<br>****************************<br>";
while($row = $reqs->fetch())
{
	$eventID = LON_requests::GetEventID($row["RequestID"], EVENTTYPE_LoanDailyIncome);
	$LateEvent = LON_requests::GetEventID($row["RequestID"], EVENTTYPE_LoanDailyLate);
	$PenaltyEvent = LON_requests::GetEventID($row["RequestID"], EVENTTYPE_LoanDailyPenalty);
	
	if($eventID > 0)
	{
		$obj = new ExecuteEvent($eventID);
		$obj->DocObj = isset($objArr[$eventID]) ? $objArr[$eventID] : null;
		$obj->DocDate = $ComputeDate;
		$obj->Sources = array($row["RequestID"], $row["PartID"] , $ComputeDate);
		$result = $obj->RegisterEventDoc($pdo);
		$objArr[$eventID] = $obj->DocObj;
		if(!$result || ExceptionHandler::GetExceptionCount() > 0)
		{
			echo "وام " .  $row["RequestID"] . " : <br>";
			echo ExceptionHandler::GetExceptionsToString("<br>");
			print_r(ExceptionHandler::PopAllExceptions());
			echo "\n--------------------------------------------\n";
		}
	}
	if($LateEvent > 0)
	{
		$obj = new ExecuteEvent($LateEvent);
		$obj->DocObj = isset($objArr[$LateEvent]) ? $objArr[$LateEvent] : null;
		$obj->DocDate = $ComputeDate;
		$obj->Sources = array($row["RequestID"], $row["PartID"] , $ComputeDate);
		$result = $obj->RegisterEventDoc($pdo);
		$objArr[$LateEvent] = $obj->DocObj;
		if(!$result || ExceptionHandler::GetExceptionCount() > 0)
		{
			echo "وام " .  $row["RequestID"] . " : <br>";
			echo ExceptionHandler::GetExceptionsToString("<br>");
			print_r(ExceptionHandler::PopAllExceptions());
			echo "\n--------------------------------------------\n";
		}
	}
	
	if($PenaltyEvent > 0)
	{
		$obj = new ExecuteEvent($PenaltyEvent);
		$obj->DocObj = isset($objArr[$PenaltyEvent]) ? $objArr[$PenaltyEvent] : null;
		$obj->DocDate = $ComputeDate;
		$obj->Sources = array($row["RequestID"], $row["PartID"] , $ComputeDate);
		$result = $obj->RegisterEventDoc($pdo);
		$objArr[$PenaltyEvent] = $obj->DocObj;
		if(!$result || ExceptionHandler::GetExceptionCount() > 0)
		{
			echo "وام " .  $row["RequestID"] . " : <br>";
			echo ExceptionHandler::GetExceptionsToString("<br>");
			print_r(ExceptionHandler::PopAllExceptions());
			echo "\n--------------------------------------------\n";
		}
	}
}
$pdo->commit();	

//-----------------------------------------------------
//-------------- fund wage of agent -------------------
$ComputeDate = PDONOW;
$reqs = PdoDataAccess::runquery_fetchMode(" 
	select * from LON_installments join LON_requests using(RequestID)
	where InstallmentDate=? AND PureFundWage>0 AND StatusID=" . LON_REQ_STATUS_CONFIRM, 
	array($ComputeDate));
$obj = new ExecuteEvent(1977);
foreach($reqs as $row)
{
	$obj->DocDate = $ComputeDate;
	$obj->Sources = array($row["RequestID"], $row["PartID"]);
	$obj->AllRowsAmount = $row["PureFundWage"];
	$result = $obj->RegisterEventDoc();
	$DocID = $obj->DocObj;
	if(!$result || ExceptionHandler::GetExceptionCount() > 0)
	{
		echo "وام " .  $row["RequestID"] . " : <br>";
		echo ExceptionHandler::GetExceptionsToString("<br>");
		print_r(ExceptionHandler::PopAllExceptions());
		echo "\n--------------------------------------------\n";
	}
}
//-----------------------------------------------------
$fp = fopen(DOCUMENT_ROOT . "/process/loanDaily.html", "w");
$htmlStr = ob_get_contents();
ob_end_clean(); 
$htmlStr = preg_replace('/\\n/', "<br>", $htmlStr);
fwrite($fp, $htmlStr);
fclose($fp);
die(); 
?>
