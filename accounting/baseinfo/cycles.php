<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;
 
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg",$js_prefix_address . "baseinfo.data.php?task=SelectCycles","div_grid_user");

$dg->addColumn("","CycleID","", true);
$dg->addColumn("","IsClosed","", true);

$col = $dg->addColumn("عنوان دوره","CycleDesc","string");
$col->editor = ColumnEditor::TextField();
$col->sortable = false;

$col = $dg->addColumn("سال", "CycleYear", "string");
$col->editor = ColumnEditor::NumberField();

/*$col = $dg->addColumn("درصد سود سپرده کوتاه مدت", "ShortDepositPercent", "string");
$col->editor = ColumnEditor::NumberField();
$col->width = 130;
$col->align = "center";

$col = $dg->addColumn("درصد سود سپرده بلند مدت", "LongDepositPercent", "string");
$col->editor = ColumnEditor::NumberField();
$col->width = 130;
$col->align = "center";*/

$col = $dg->addColumn("بسته شده", "IsClosed", "string");
$col->renderer = "function(v){if(v == 'YES') return '*';}";
$col->editor = ColumnEditor::CheckField("", "YES");
$col->sortable = false;
$col->width = 70;

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){ACC_CycleObject.Adding();}";
}
if($accessObj->AddFlag || $accessObj->EditFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(v,p,r){return ACC_CycleObject.saveData(v,p,r);}";
}
$dg->height = 350;
$dg->width = 500;
$dg->DefaultSortField = "CycleYear";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "CycleDesc";
$dg->editorGrid = true;
$dg->title = "دوره های مالی";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$grid = $dg->makeGrid_returnObjects();
?>
<script>
ACC_Cycle.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ACC_Cycle()
{
	this.grid = <?= $grid?>;
	this.grid.render(this.get("div_grid"));
}

ACC_Cycle.prototype.Adding = function()
{
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		CycleID : ""
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

ACC_Cycle.prototype.saveData = function(store,record)
{
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'SaveCycle',
			record : Ext.encode(record.data)
		},
		url: this.address_prefix +'baseinfo.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				ACC_CycleObject.grid.getStore().load();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}
	});
}

var ACC_CycleObject = new ACC_Cycle();

</script>
<center>
	<br>
	<div id="div_grid"></div>
</center>