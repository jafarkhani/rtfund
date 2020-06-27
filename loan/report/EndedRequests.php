<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.01
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../request/request.data.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg",$js_prefix_address . "../request/request.data.php?task=GetEndedRequests","grid_div");

$dg->addColumn("", "PartID","", true);
$dg->addColumn("", "ComputeMode","", true);

$col = $dg->addColumn("کد وام", "RequestID","");
$col->width = 50;

$col = $dg->addColumn("وام گیرنده", "LoanPersonName");

$col = $dg->addColumn("تاریخ درخواست", "ReqDate", GridColumn::ColumnType_date);
$col->width = 120;

$col = $dg->addColumn("مانده", "TotalRemainder", GridColumn::ColumnType_money);
$col->width = 100;

$dg->addButton("", "گزارش پرداخت", "report", "function(){LoanReport_EndedRequestsObj.PayReport();}");

$dg->height = 377;
$dg->width = 850;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->DefaultSortField = "ReqDate";
$dg->DefaultSortDir = "ASC";
$dg->title = "وام های پرداخت شده";
$dg->autoExpandColumn = "LoanPersonName";

$grid = $dg->makeGrid_returnObjects();

?>
<script>
LoanReport_EndedRequests.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function LoanReport_EndedRequests()
{
	this.grid = <?= $grid ?>;
	this.grid.on("itemdblclick", function(view, record){
		framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", {RequestID : record.data.RequestID});
	});
	this.grid.render(this.get("divGrid"));
}

LoanReport_EndedRequestsObj = new LoanReport_EndedRequests();

LoanReport_EndedRequests.prototype.PayReport = function(){

	record = this.grid.getSelectionModel().getLastSelected();
	window.open(this.address_prefix + "../report/LoanPayment.php?show=true&RequestID=" + record.data.RequestID);
}

</script>
<center><br>
	در گرید زیر وام هایی لیست شده اند که کلیه اقساط و بدهی آنها توسط مشتری پرداخت گردیده است.
	لذا آماده خاتمه و برگشت تضمین های مشتری می باشند.
	<br>
	<br>
	<div id="divGrid" ></div>
</center>