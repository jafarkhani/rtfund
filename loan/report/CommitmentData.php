<?php
//-----------------------------
//	Programmer	: M.Mokhtari
//	Date		: 99.01
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../request/request.class.php'; 
require_once '../request/request.data.php';

ini_set("display_errors", "On");

if(isset($_REQUEST["RequestID"]))
{
	$RequestID = $_REQUEST["RequestID"];
		$PureArr = LON_Computes::ComputePures($RequestID); 
		
		BeginReport();
		
	//..........................................................
	$report2 = "";
	//..........................................................
	$rpg2 = new ReportGenerator();
	$rpg2->mysql_resource = $PureArr;

	$col = $rpg2->addColumn("تاریخ قسط", "InstallmentDate","ReportDateRender");
	$col = $rpg2->addColumn("مبلغ قسط", "InstallmentAmount","ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg2->addColumn("بهره قسط", "wage","ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg2->addColumn("اصل قسط", "pure","ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg2->addColumn("مانده اصل وام", "totalPure","ReportMoneyRender");
	ob_start();
	$rpg2->generateReport();
	$report2 = ob_get_clean();
	//..........................................................
	
	echo "<div style='direction:rtl;' id='CommitmentData' >" . $report2 . "</div>";
	die();
}
?>
