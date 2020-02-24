<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;
require_once 'branches.js.php';

$dg = new sadaf_datagrid("dg",$js_prefix_address . "baseInfo.data.php?task=SelectBranches","div_grid_user");

$col = $dg->addColumn("عنوان شعبه","BranchName","string");
$col->editor = ColumnEditor::TextField();
$col->sortable = false;

$col = $dg->addColumn("وضعیت", "IsActive", "string");
$col->editor = ColumnEditor::ComboBox(array(array("id"=>"YES", "title"=>'فعال'),array("id"=>'NO', "title"=>'غیرفعال')), "id", "title");
$col->sortable = false;
$col->width = 60;
$col->align = "center";

$col = $dg->addColumn("تفصیلی بانک پیش فرض", "DefaultBankTafsiliID", "string");
$col->editor = ColumnEditor::ComboBox(
		PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=" . TAFTYPE_BANKS), 
		"TafsiliID", "TafsiliDesc", "", "", true);
$col->sortable = false;
$col->width = 130;

$col = $dg->addColumn("تفصیلی حساب پیش فرض", "DefaultAccountTafsiliID", "string");
$col->editor = ColumnEditor::ComboBox(
		PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=" . TAFTYPE_ACCOUNTS), 
		"TafsiliID", "TafsiliDesc", "", "", true);
$col->sortable = false;
$col->width = 140;

$col = $dg->addColumn("مجوز صدور ضمانتنامه", "WarrentyAllowed", "string");
$col->renderer = "function(v){return v == 'YES' ? '√' : '';}";
$col->editor = ColumnEditor::CheckField("", "YES");
$col->sortable = false;
$col->align = "center";
$col->width = 100;

$col = $dg->addColumn("حذف","BranchID","");
$col->renderer = "Branch.deleteRender";
$col->sortable = false;
$col->width = 40;

$dg->addButton = true;
$dg->addHandler = "function(){BranchObject.Adding();}";

$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(v,p,r){return BranchObject.saveData(v,p,r);}";

$dg->height = 350;
$dg->width = 780;
$dg->DefaultSortField = "BranchName";
$dg->autoExpandColumn = "BranchName";
$dg->editorGrid = true;
$dg->title = "شعبه های صندوق";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$grid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>
<script>

var BranchObject = new Branch();

BranchObject.grid = <?= $grid?>;
BranchObject.grid.getView().getRowClass = function(record)
{
	if(record.data.IsActive == "NO")
		return "pinkRow";
	return "";
}
BranchObject.grid.render(BranchObject.get("div_grid"));

</script>
<center>
	<br>
	<div id="div_grid"></div>
</center>