<?php

require_once '../loan/request/request.data.php';
ini_set("display_errors", "On");

$dt = PdoDataAccess::runquery("select * from LON_payments p join LON_requests using(RequestID) 
			join LON_ReqParts rp on(rp.RequestID=p.RequestID AND isHistory='NO')
		 where StatusID=70 and ReqPersonID in(1003) 
		  and (DelayReturn != 'INSTALLMENT' OR AgentDelayReturn != 'INSTALLMENT')");
foreach($dt as $row)
{
	echo $row["RequestID"] . " - " . $row["PayID"] . " : ";
	$PartObj = LON_ReqParts::GetValidPartObj($row["RequestID"]);
	//$result = ComputeWagesAndDelays($PartObj, $row["PayAmount"], $PartObj->PartDate, $row["PayDate"]);
	
	$PayAmount = $row["PayAmount"];
	
	$AgentWage = $FundWage = $FundDelay = $AgentDelay = 0;
			
	if($PartObj->AgentReturn == "CUSTOMER")
	{
		$totalWage = $PayAmount*$PartObj->CustomerWage/100;
		$AgentFactor = ($PartObj->CustomerWage*1-$PartObj->FundWage*1)/$PartObj->CustomerWage*1;
		$AgentWage = $totalWage*$AgentFactor;		
	}
	if($PartObj->FundWage*1 > 0 && $PartObj->WageReturn == "CUSTOMER")
	{
		$FundWage = $PayAmount*$PartObj->FundWage/100;
	}
	
	$endDelayDate = DateModules::AddToGDate($PartObj->PartDate, $PartObj->DelayDays*1, $PartObj->DelayMonths*1);
	$DelayDuration = DateModules::GDateMinusGDate($endDelayDate, $PartObj->PartDate)+1;
	
	PdoDataAccess::runquery("update LON_payments set OldFundDelayAmount=? where PayID=?", array(
		$FundDelay, $row["PayID"]));
	PdoDataAccess::runquery("update LON_payments set OldAgentDelayAmount=? where PayID=?", array(
		$AgentDelay, $row["PayID"]));
	PdoDataAccess::runquery("update LON_payments set OldFundWage=? where PayID=?", array(
		$FundWage, $row["PayID"]));
	PdoDataAccess::runquery("update LON_payments set OldAgentWage=? where PayID=?", array(
		$AgentWage, $row["PayID"]));
	
	
	print_r(ExceptionHandler::PopAllExceptions());
	echo "<br>";
	flush();
	ob_flush();
}
die();
?>