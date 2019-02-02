<?php

require_once 'request.data.php';
ini_set("display_errors", "On");

$dt = PdoDataAccess::runquery("select RequestID from LON_requests where StatusID=70 "
		. "and ReqPersonID not in(1003,1004,2051,1762,1825)");
flush();
ob_flush();
$i=0;
foreach($dt as $row)
{
	echo $i++ . " - " . $row["RequestID"] . " : ";
	
	$obj = LON_ReqParts::GetValidPartObj($row["RequestID"]);
	$obj->ComputeMode = "NEW";
	$obj->EditPart();
	
	$result = ComputeInstallments($row["RequestID"], true, null);
	print_r(ExceptionHandler::PopAllExceptions());
	echo ($result ? "true" : "false") . "<br>";
	flush();
	ob_flush();
}
die();
?>