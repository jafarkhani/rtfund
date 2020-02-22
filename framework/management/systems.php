<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;
require_once 'systems.js.php';

$dg = new sadaf_datagrid("dg",$js_prefix_address . "framework.data.php?task=selectSystems","div_grid_user");

$col = $dg->addColumn("کد","SystemID","string");
$col->editor = ColumnEditor::NumberField();
$col->width = 50;

$col = $dg->addColumn("ترتیب","ordering","string");
$col->editor = ColumnEditor::NumberField();
$col->sortable = false;
$col->width = 60;

$col = $dg->addColumn("عنوان سیستم","SysName","string");
$col->editor = ColumnEditor::TextField();
$col->sortable = false;

$col = $dg->addColumn("پوشه اصلی","SysPath","string");
$col->editor = ColumnEditor::TextField();
$col->sortable = false;
$col->width = 150;

$col = $dg->addColumn("آیکون","SysIcon","string");
$col->editor = ColumnEditor::TextField();
$col->sortable = false;
$col->width = 120;

$col = $dg->addColumn("وضعیت", "IsActive", "string");
$col->editor = ColumnEditor::ComboBox(array(array("id"=>"YES", "title"=>'فعال'),array("id"=>'NO', "title"=>'غیرفعال')), "id", "title");
$col->sortable = false;
$col->width = 60;
$col->align = "center";

$dg->addButton = true;
$dg->addHandler = "function(){SystemObject.Adding();}";

$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(v,p,r){return SystemObject.saveData(v,p,r);}";

$dg->height = 350;
$dg->width = 700;
$dg->DefaultSortField = "SysName";
$dg->autoExpandColumn = "SysName";
$dg->editorGrid = true;
//$dg->notRender = true;
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$grid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>
<script>

var SystemObject = new System();

SystemObject.grid = <?= $grid?>;
SystemObject.grid.getView().getRowClass = function(record)
{
	if(record.data.IsActive == "NO")
		return "pinkRow";
	return "";
}
SystemObject.grid.render(SystemObject.get("div_grid_user"));

</script>
<center>
	<br>
	<div id="div_grid_user"></div>
</center>