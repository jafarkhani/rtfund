<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
require_once 'loans.js.php';

$dg = new sadaf_datagrid("dg", $js_prefix_address . "loan.data.php?task=GetAllLoans", "grid_div");

$dg->addColumn("کد وام", "LoanID", "", true);
$dg->addColumn("", "GroupID", "", true);
$dg->addColumn("درصد دیرکرد", "ForfeitPercent", "", true);
$dg->addColumn("", "CustomerWage", "", true);
$dg->addColumn("", "IntervalType", "", true);
$dg->addColumn("", "IsCustomer", "", true);
$dg->addColumn("", "IsPlan", "", true);
$dg->addColumn("", "IsActive", "", true);

$col = $dg->addColumn("عنوان وام", "LoanDesc", "");

$col = $dg->addColumn("سقف مبلغ", "MaxAmount", GridColumn::ColumnType_money);
$col->width = 140;

$col = $dg->addColumn("تعداد اقساط", "InstallmentCount");
$col->width = 80;
$col->align = "center";

$col = $dg->addColumn("فاصله اقساط", "PayInterval", "");
$col->width = 80;
$col->align = "center";

$col = $dg->addColumn("مدت تنفس", "DelayMonths", "");
$col->width = 80;
$col->align = "center";

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){LoanObject.LoanInfo('new');}";
}
$dg->title = "لیست وام ها";

$dg->height = 500;
$dg->width = 750;
$dg->EnablePaging = false;
$dg->DefaultSortField = "LoanDesc";

$col = $dg->addColumn("عملیات", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return Loan.OperationRender(v,p,r);}";
$col->width = 50;

$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
        <br>
        <div id="div_selectGroup"></div>
        <br>
		<div id="newDiv"></div>
        <div id="grid_div"></div>
    </form>
</center>
<script>
    var LoanObject = new Loan();	

	LoanObject.grid = <?= $grid ?>;
	LoanObject.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsActive == "NO")
			return "pinkRow";
		return "";
	}	
</script>