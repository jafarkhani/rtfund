<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.12
//---------------------------
require_once '../header.inc.php';
ini_set("display_errors", "On");
ini_set('max_execution_time', 300000);
ini_set('memory_limit','2000M');
header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);
error_reporting(0);

require_once '../framework/configurations.inc.php';

set_include_path(DOCUMENT_ROOT . "/generalClasses");

require_once '../definitions.inc.php';
require_once DOCUMENT_ROOT . '/generalClasses/InputValidation.class.php';
require_once DOCUMENT_ROOT . '/generalClasses/PDODataAccess.class.php';
require_once DOCUMENT_ROOT . '/generalClasses/DataAudit.class.php';

require_once '../office/dms/dms.class.php';

require_once '../loan/request/request.class.php';
require_once '../commitment/ExecuteEvent.class.php';


$params = array();
$query = "
select * from dates where jdate between :sd AND :ed" ;

$params[":sd"] = $_GET["fdate"];
$params[":ed"] = $_GET["tdate"];	

$days = PdoDataAccess::runquery_fetchMode($query, $params);
echo "days:" . $days->rowCount() . "<br>";
ob_flush();flush();

foreach($days as $dayRow)
{
	$params = array();
	$query = "
	select * 
	from LON_requests  r
	join LON_ReqParts p on(r.RequestID=p.RequestID AND IsHistory='NO')
	where ComputeMode='NEW' AND StatusID=" . LON_REQ_STATUS_CONFIRM ;
	if(!empty($_GET["RequestID"]))
	{
		$query .= " AND  r.RequestID=:r";
		$params[":r"] = (int)$_GET["RequestID"];
	}
	$reqs = PdoDataAccess::runquery_fetchMode($query,$params);
	
	$objArr = array(
		EVENT_LOANDAILY_innerSource => null, 
		EVENT_LOANDAILY_agentSource_committal => null,
		EVENT_LOANDAILY_agentSource_non_committal => null,

		EVENT_LOANDAILY_innerLate => null, 
		EVENT_LOANDAILY_agentlate => null, 
		EVENT_LOANDAILY_innerPenalty => null, 
		EVENT_LOANDAILY_agentPenalty => null
	);

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();

	$ComputeDate = $dayRow["gdate"];
	echo "<br>****************************<BR>" . DateModules::miladi_to_shamsi($ComputeDate) . 
			"<br>****************************<br>";
	ob_flush();flush();
	while($row = $reqs->fetch())
	{
		$ComputeDate = $dayRow["gdate"];
		$eventID = "";
		$LateEvent = "";
		$PenaltyEvent = "";

		if($row["ReqPersonID"]*1 == 0)
		{
			$eventID = EVENT_LOANDAILY_innerSource;
			$LateEvent = EVENT_LOANDAILY_innerLate;
			$PenaltyEvent = EVENT_LOANDAILY_innerPenalty;
		}
		else
		{
			if($row["FundGuarantee"] == "YES")
				$eventID = EVENT_LOANDAILY_agentSource_committal;
			else
				$eventID = EVENT_LOANDAILY_agentSource_non_committal;

			$LateEvent = EVENT_LOANDAILY_agentlate;
			$PenaltyEvent = EVENT_LOANDAILY_agentPenalty;
		}
		if($objArr[$eventID] != null)
			$obj = &$objArr[$eventID];
		else {
			$objArr[$eventID] = new ExecuteEvent($eventID);
			$obj = &$objArr[$eventID];
		}
		$obj->DocDate = $ComputeDate;
		$obj->Sources = array($row["RequestID"], $row["PartID"] , $ComputeDate);
		$result = $obj->RegisterEventDoc($pdo);
		if(!$result || ExceptionHandler::GetExceptionCount() > 0)
		{
			echo "وام " .  $row["RequestID"] . " : <br>";
			echo ExceptionHandler::GetExceptionsToString("<br>");
			print_r(ExceptionHandler::PopAllExceptions());
			echo "\n--------------------------------------------\n";
		}


		if($objArr[$LateEvent] != null)
			$obj = &$objArr[$LateEvent];
		else {
			$objArr[$LateEvent] = new ExecuteEvent($LateEvent);
			$obj = &$objArr[$LateEvent];
		}
		$obj->DocDate = $ComputeDate;
		$obj->Sources = array($row["RequestID"], $row["PartID"] , $ComputeDate);
		$result = $obj->RegisterEventDoc($pdo);
		if(!$result || ExceptionHandler::GetExceptionCount() > 0)
		{
			echo "وام " .  $row["RequestID"] . " : <br>";
			echo ExceptionHandler::GetExceptionsToString("<br>");
			print_r(ExceptionHandler::PopAllExceptions());
			echo "\n--------------------------------------------\n";
		}


		if($objArr[$PenaltyEvent] != null)
			$obj = &$objArr[$PenaltyEvent];
		else {
			$objArr[$PenaltyEvent] = new ExecuteEvent($PenaltyEvent);
			$obj = &$objArr[$PenaltyEvent];
		}
		$obj->DocDate = $ComputeDate;
		$obj->Sources = array($row["RequestID"], $row["PartID"] , $ComputeDate);
		$result = $obj->RegisterEventDoc($pdo);
		if(!$result || ExceptionHandler::GetExceptionCount() > 0)
		{
			echo "وام " .  $row["RequestID"] . " : <br>";
			echo ExceptionHandler::GetExceptionsToString("<br>");
			print_r(ExceptionHandler::PopAllExceptions());
			echo "\n--------------------------------------------\n";
		}

	}
	$pdo->commit();
	print_r(ExceptionHandler::PopAllExceptions());
	echo "true";
}
?>
