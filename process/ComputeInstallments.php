<?php
require_once '../header.inc.php';
ini_set("display_errors", "On");
require_once '../loan/request/request.class.php';
require_once '../commitment/ExecuteEvent.class.php';

ini_set('max_execution_time', 30000000);
ini_set('memory_limit','4000M');
header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);

$dt = PdoDataAccess::runquery("select * from LON_ReqParts join LON_requests using(RequestID) 
    join LON_loans using(LoanID)
   left join (select SourceID1 from ACC_DocItems join ACC_docs using(DocID) where eventID=2001)t on(SourceID1=RequestID)
    where ChangeDate>0 and ReqPersonID=1001 AND StatusID=70 AND GroupID=2 AND t.SourceID1 is null
    "); 
flush();
ob_flush();
$i=0;
foreach($dt as $row)
{
	$RequestID = $row["RequestID"];
	$EventID = LON_requests::GetEventID($RequestID, "LoanChange");
	if(!$EventID){
		echo $RequestID . " : no event" ;
		continue;
	}
		
	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	
	
	$dt = PdoDataAccess::runquery("select * from LON_ReqParts 
		where requestID=? order by PartID desc", array($RequestID), $pdo);
	
	$newpart = new LON_ReqParts($dt[0]["PartID"]);
	$newpart->RequestID = 0;
	$newpart->EditPart($pdo);
	
	$prePart = new LON_ReqParts($dt[1]["PartID"]);
	$prePart->IsHistory = "NO";
	$prePart->EditPart($pdo);
	
	$dt = PdoDataAccess::runquery("update LON_installments set RequestID=2487,partID=?
		where RequestID=? AND IsDelayed='NO' AND history='No'", array(
			$newpart->PartID,
			$RequestID
		), $pdo);
		
	LON_installments::ComputeInstallments($RequestID, $pdo);
	
	$newpart->RequestID = $RequestID;
	$newpart->EditPart($pdo);
	
	$prePart->IsHistory = "YES";
	$prePart->EditPart($pdo);
	
	PdoDataAccess::runquery("update LON_installments set history='YES' where RequestID=? AND PartID<>?",
				array($RequestID, $newpart->PartID));
	
	PdoDataAccess::runquery("update LON_installments set RequestID=?
		where RequestID=2487", array(	$RequestID	), $pdo);
	
	
	$eventobj = new ExecuteEvent($EventID);
	$eventobj->Sources = array($RequestID, $newpart->PartID);
	$result = $eventobj->RegisterEventDoc($pdo);

	if(ExceptionHandler::GetExceptionCount() > 0)
		$pdo->rollBack ();
	else
		$pdo->commit();
	
	echo $RequestID . " : " . "<br><br>";
	print_r(ExceptionHandler::PopAllExceptions());
	ob_flush();flush();
	
}
die();
?>
