<?php

require_once '../loan/request/request.data.php';
ini_set("display_errors", "On");
ini_set('max_execution_time', 30000000);
ini_set('memory_limit','4000M');
header("X-Accel-Buffering: no");
ob_start();

$dt = PdoDataAccess::runquery("
	
select r.RequestID from LON_requests r 
join LON_ReqParts p on(r.RequestID=p.RequestID and IsHistory='NO')
where ComputeMode='PERCENT'"); 
flush();
ob_flush();
$i=0;
foreach($dt as $row)
{
	$RequestID = $row["RequestID"];
	LON_installments::ComputeInstallments($RequestID);
	echo $RequestID . " : " ;
	print_r(ExceptionHandler::PopAllExceptions());
	continue;
	
	
	
	
	
	
	
	$oldAmount = $row["amount"];
	
	echo $RequestID . " : ";
	
	$temp = PdoDataAccess::runquery("select * from LON_installments where RequestID=? AND IsDelayed='NO' AND history='NO'", array($RequestID));
	
	$installmentArray = array();
	for ($i = 0; $i < count($temp); $i++) {
		$installmentArray[] = array(
			"InstallmentAmount" => $oldAmount,
			"InstallmentDate" => DateModules::miladi_to_shamsi($temp[$i]["InstallmentDate"])
		);
	}
	$installmentArray = ExtraModules::array_sort($installmentArray, "InstallmentDate");
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	$installmentArray = LON_Computes::ComputeInstallment($partObj, $installmentArray, "", "NO");

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();
	PdoDataAccess::runquery("delete from LON_installments "
			. "where RequestID=? AND history='NO' AND IsDelayed='NO'", array($RequestID), $pdo);
	
	for($i=0; $i < count($installmentArray); $i++)
	{
		$obj = new LON_installments();
		$obj->RequestID = $RequestID;
		$obj->InstallmentDate = DateModules::shamsi_to_miladi($installmentArray[$i]["InstallmentDate"]);
		$obj->InstallmentAmount = $installmentArray[$i]["InstallmentAmount"];
		$obj->wage = isset($installmentArray[$i]["wage"]) ? $installmentArray[$i]["wage"] : 0;
		$obj->PureWage = isset($installmentArray[$i]["PureWage"]) ? $installmentArray[$i]["PureWage"] : 0;
		if(!$obj->AddInstallment($pdo))
		{
			$pdo->rollBack();
			print_r(ExceptionHandler::PopAllExceptions());
			echo "false";
		}
	}
	
	$pdo->commit();	
	echo "true<br>";
	flush();
	ob_flush();
}
die();
?>