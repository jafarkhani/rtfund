<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1395.08
//-----------------------------

require_once '../../header.inc.php';
require_once 'config.inc.php';
require_once inc_dataGrid;
require_once 'operations.js.php';

$dg = new sadaf_datagrid("dg", $js_prefix_address . "operation.data.php?task=SelectOperations", "grid_div");

$dg->addColumn("", "OperationID", "", true);

$col = $dg->addColumn("تاریخ عملیات", "OperationDate", GridColumn::ColumnType_datetime);
$col->width = 120;

$col = $dg->addColumn("شرح", "title");

$col = $dg->addColumn("نوع ارسال", "SendType", "");
$col->width = 80;

$col = $dg->addColumn('لیست افراد', '', 'string');
$col->renderer = "NTC_Operation.OperationRender";
$col->width =70;
$col->align = "center";

$dg->addButton("", "ایجاد", "add", "function(){NTC_OperationObject.AddNew();}");

$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->height = 500;
$dg->pageSize = 15;
$dg->width = 800;
$dg->title = "ارتباط با ذینفعان";
$dg->DefaultSortField = "OperationDate";
$dg->autoExpandColumn = "title";
$grid = $dg->makeGrid_returnObjects();

//-------------------------------------------------------------------

$dg = new sadaf_datagrid("dg", $js_prefix_address . "operation.data.php?task=SelectPersons", "grid_div");

$col = $dg->addColumn("", "RowID", "", true);
$col = $dg->addColumn("", "IsSuccess", "", true);
$col = $dg->addColumn("PID", "PersonID");
$col->width = 70;

$col = $dg->addColumn("شماره نامه", "LetterID");
$col->width = 70;

$col = $dg->addColumn("متن ارسالی", "context", "");

/*$col = $dg->addColumn("ستون 1", "col1", "");

$col = $dg->addColumn("ستون 2", "col2", "");
$col->width = 70;
$col = $dg->addColumn("ستون 3", "col3", "");
$col->width = 70;
$col = $dg->addColumn("ستون 4", "col4", "");
$col->width = 70;
$col = $dg->addColumn("ستون 5", "col5", "");
$col->width = 70;
$col = $dg->addColumn("ستون 6", "col6", "");
$col->width = 70;
$col = $dg->addColumn("ستون 7", "col7", "");
$col->width = 70;
$col = $dg->addColumn("ستون 8", "col8", "");
$col->width = 70;
$col = $dg->addColumn("ستون 9", "col9", "");
$col->width = 70;*/

$col = $dg->addColumn("خطا", "ErrorMsg", "");
$col->width = 250;

$dg->height = 500;
$dg->width = 900;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "RowID";
$dg->autoExpandColumn = "context";
$grid2 = $dg->makeGrid_returnObjects();
?>
<script>
NTC_OperationObject.grid = <?= $grid ?>;
NTC_OperationObject.grid.render(NTC_OperationObject.get("DivGrid"));

NTC_OperationObject.PersonsGrid = <?= $grid2 ?>;
NTC_OperationObject.PersonsGrid.getView().getRowClass = function(record, index)
{
	if(record.data.IsSuccess == "YES")
		return "greenRow";
	return "";
}

</script>
<center><br>
	<div><div id="operationInfo"></div></div>
	<br>
	<div id="DivGrid"></div>	
</center>