<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
 
require_once '../header.inc.php';
require_once 'saving.data.php';
require_once inc_reportGenerator;

$dataTable = GetSavingLoanInfo(true);

$rpg = new ReportGenerator();
$rpg->mysql_resource = $dataTable;

function InRender($row){
	if($row["amount"]*1 < 0)
		return 0;
	return number_format($row["amount"]);
}

function OutRender($row){
	if($row["amount"]*1 > 0)
		return 0;
	return number_format($row["amount"]);
}

$rpg->addColumn("تاریخ", "Date", "ReportDateRender");
$col = $rpg->addColumn("واریز", "amount", "InRender");
$col = $rpg->addColumn("برداشت", "amount", "OutRender");
$rpg->addColumn("مانده", "remain", "ReportMoneyRender");
$col = $rpg->addColumn("تعداد روز", "days");
$rpg->addColumn("میانگین", "average", "ReportMoneyRender");

BeginReport();
$rpg->generateReport();

?>