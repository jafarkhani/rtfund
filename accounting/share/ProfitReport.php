<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 91.02
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

$query = "select di.*,d.DocDate								
	from ACC_DocItems di
		join ACC_docs d using(DocID)
		join ACC_tafsilis using(TafsiliID)
		join BSC_persons on(ObjectID=PersonID)
	where CostID=" . COSTID_ShareProfit . " AND PersonID= " . $_SESSION["USER"]["PersonID"]
	. " order by DocDate";

$dataTable = PdoDataAccess::runquery($query);
//print_r(ExceptionHandler::PopAllExceptions());

$rpg = new ReportGenerator();
$rpg->mysql_resource = $dataTable;

$col = $rpg->addColumn("تاریخ محاسبه سود", "DocDate", "ReportDateRender");

$col = $rpg->addColumn("مبلغ", "CreditorAmount", "ReportMoneyRender");
$col->align = "center";
$col->EnableSummary();

$rpg->generateReport();

?>