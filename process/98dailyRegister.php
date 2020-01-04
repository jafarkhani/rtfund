<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.12
//---------------------------
require_once '../header.inc.php';
ini_set("display_errors", "On");
ini_set('max_execution_time', 30000000);
ini_set('memory_limit','4000M');
header("X-Accel-Buffering: no");
ob_start();
//set_time_limit(0);
//error_reporting(0);

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
	from LON_requests  r join aa on(r.RequestID=aa.DocID)
	join LON_ReqParts p on(r.RequestID=p.RequestID AND IsHistory='NO')
	where ComputeMode='NEW' AND StatusID<>" . LON_REQ_STATUS_CONFIRM ;
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
			$EarlyEvent = EVENT_LOANDAILY_innerEarly;
		}
		else
		{
			if($row["FundGuarantee"] == "YES")
				$eventID = EVENT_LOANDAILY_agentSource_committal;
			else
				$eventID = EVENT_LOANDAILY_agentSource_non_committal;

			$LateEvent = EVENT_LOANDAILY_agentlate;
			$PenaltyEvent = EVENT_LOANDAILY_agentPenalty;
			$EarlyEvent = EVENT_LOANDAILY_agentEarly;
		}
	
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
		
		$obj = new ExecuteEvent($EarlyEvent);
		$obj->DocObj = isset($objArr[$EarlyEvent]) ? $objArr[$EarlyEvent] : null;
		$obj->DocDate = $ComputeDate;
		$obj->Sources = array($row["RequestID"], $row["PartID"] , $ComputeDate);
		$result = $obj->RegisterEventDoc($pdo);
		$objArr[$EarlyEvent] = $obj->DocObj;
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
	//print_r($objArr);
	echo "true";
}
?>
