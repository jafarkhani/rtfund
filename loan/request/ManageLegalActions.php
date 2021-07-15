<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

require_once 'ManageLegalActions.js.php';

$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=SelectAllLegalActions", "grid_div");

$dg->addColumn("", "legalActionID", "", true);

$col = $dg->addColumn("گیرنده وام", "LoanFullname");
$col->width = 80;
$col = $dg->addColumn("شماره تسهیلات", "RequestID");
$col->width = 80;
$col = $dg->addColumn("معرفی کننده", "ReqFullname");
$col->width = 80;
$col = $dg->addColumn("تاریخ ارجاع به وکیل", "ReferDate", GridColumn::ColumnType_date);
$col->width = 70;
$col = $dg->addColumn("مبلغ بدهی", "TotalRemain", GridColumn::ColumnType_money);
$col->width = 80;
$col = $dg->addColumn("مبلغ پرداخت‌شده (توسط مشتری)", "TotalPayAmount", GridColumn::ColumnType_money);
$col->width = 80;
$col = $dg->addColumn("نوع تضمینات", "DeliverDoc");
$col->width = 80;
$col = $dg->addColumn("اسناد تحویلی به وکیل / موسسه حقوقی", "DeliverDoc");
$col->width = 80;
$col = $dg->addColumn("شعبه مربوطه", "branch");
$col->width = 80;
$col = $dg->addColumn("شماره پرونده", "fileNum");
$col->width = 80;
$col = $dg->addColumn("اقدامات حقوقی انجام شده", "actionTaken");
$col->width = 80;
$col = $dg->addColumn("آخرین مستند پیگیری حقوقی", "latestDoc");
$col->width = 80;
$col = $dg->addColumn("اقدامات پیش‌رو", "actionAhead");
$col->width = 80;
$col = $dg->addColumn("تاریخ ثبت", "RegDate", GridColumn::ColumnType_date);
$col->width = 70;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "LegalActions.OperationRender";
$col->width = 50;
$col->align = "center";

if($accessObj->AddFlag)
	$dg->addButton("", "ایجاد اقدام جدید", "add", "function(){LegalActionsObject.AddNew();}");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 500;
$dg->pageSize = 15;
$dg->width = 1250;
$dg->title = "اقدامات حقوقی";

$grid = $dg->makeGrid_returnObjects();
?>
<script>
    LegalActionsObject.grid = <?= $grid ?>;
    LegalActionsObject.grid.getView().getRowClass = function(record, index)
	{
		return "";
	}

    LegalActionsObject.grid.render(LegalActionsObject.get("DivGrid"));
</script>
<center><br>
	<div><div id="RequestInfo"></div></div>
	<br>
	<div id="DivGrid"></div>	
</center>