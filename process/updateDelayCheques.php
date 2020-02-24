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
	
select * from ACC_IncomeCheques where LoanRequestID>0 
	
");

while($Reqrow=$dt->fetch())
{
	$RequestID = $Reqrow["LoanRequestID"];
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	
	$pays = LON_payments::Get(" AND RequestID=".$RequestID);
	if(count($pays) == 0)
	{
		echo 'فاقد پرداخت';
		continue;
	}
	$pays = $pays->fetchAll();
	$payObj = new LON_payments($pays[0]["PayID"]);

				
	$tanzilAmount = LON_Computes::Tanzil($Reqrow["ChequeAmount"], $partObj->CustomerWage, 
			$Reqrow["ChequeDate"], $payObj->PayDate);
	
	echo $RequestID . " - " . $payObj->PayID;
	ob_flush();flush();
	
	$payObj->OldFundWage += $tanzilAmount;
	$payObj->Edit();
	
}

print_r(ExceptionHandler::PopAllExceptions());

?>
