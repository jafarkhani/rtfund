<?php
//-----------------------------
//	Date		: 1395.06
//-----------------------------
 
require_once '../header.inc.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=GetDailyTips", "grid_div");

$dg->addColumn("", "RowID","", true);

$col = $dg->addColumn("سخن روز", "description");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("از تاریخ", "FromDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();

$col = $dg->addColumn("تا تاریخ", "ToDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();

$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(store,record){return DailyTipObject.SaveDailyTip(record);}";

$dg->addButton("AddBtn", "ایجاد متن جدید", "add", "function(){DailyTipObject.Add();}");

$col = $dg->addColumn("حذف", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return DailyTip.DeleteRender(v,p,r);}";
$col->width = 35;

$dg->autoExpandColumn = "description";
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 465;
$dg->width = 600;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "FromDate";
$dg->DefaultSortDir = "ASC";

$grid = $dg->makeGrid_returnObjects();

?>
<script>
DailyTip.prototype = {
	TabID : "<?= $_REQUEST["ExtTabID"] ?>",
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function DailyTip()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));
	
}

DailyTip.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='DailyTipObject.Delete();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

DailyTipObject = new DailyTip();
	
DailyTip.prototype.Add = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RowID : null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}
	
DailyTip.prototype.SaveDailyTip = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'letter.data.php',
		method: "POST",
		params: {
			task: "SaveDailyTip",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(!st.success)
			{
				if(st.data == "")
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("Error",st.data);
			}
			else
				DailyTipObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

DailyTip.prototype.Delete = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = DailyTipObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			params:{
				task: "DeleteDailyTip",
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					DailyTipObject.grid.getStore().load();
				else if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);
				mask.hide();
				
			},
			failure: function(){}
		});
	});
}

</script>
<center>
	<br>
	<div id="div_grid"></div>
</center>