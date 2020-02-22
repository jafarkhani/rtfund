<?php

require_once '../loan/request/request.data.php';
ini_set("display_errors", "On");
ini_set('max_execution_time', 30000);
ini_set('memory_limit','2000M');
header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);

$dt = PdoDataAccess::runquery("select RequestID from LON_ReqParts join LON_requests using(RequestID) 
where IsHistory='NO' AND StatusID=70 and FundWage>CustomerWage ");
$i=0;
foreach($dt as $row)
{
	echo $i++ . " - " . $row["RequestID"] . " : ";
	
	$result = LON_installments::ComputeInstallments($row["RequestID"]);
	print_r(ExceptionHandler::PopAllExceptions());
	echo ($result ? "true" : "false") . "<br>";
	ob_flush();flush();
}
die();
?>