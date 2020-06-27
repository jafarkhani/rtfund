<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.07
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

$SourceType = $_REQUEST["SourceType"];
$SourceID =  $_REQUEST["SourceID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "baseInfo.data.php?task=GetCheckValues&".
		"SourceType=" .$SourceType . "&SourceID=" . $SourceID,"grid_div");

$dg->addColumn("", "ItemID","", true);
$dg->addColumn("", "ItemDesc","", true);
$dg->addColumn("", "checked","", true);

$col = $dg->addColumn("گزینه", "ItemDesc");
	
$col = $dg->addColumn("", "checked");
$col->renderer = "CheckValues.CheckRender";
$col->width = 40;

$col = $dg->addColumn("تاریخ انجام", "DoneDate", GridColumn::ColumnType_datetime);
$col->width = 120;

$col = $dg->addColumn("توضیحات", "description");
$col->ellipsis = 60;
$col->width = 140;

$dg->height = 336;
$dg->width = 585;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->autoExpandColumn = "ItemDesc";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

CheckValues.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function CheckValues()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
}

CheckValues.CheckRender = function(v,p,r){
	
	return "<input type=checkbox onclick='CheckValuesObject.BeforeSave(this.checked)' "+
		(r.data.checked == 1 ? "checked" : "") + " >";
}

CheckValues.prototype.BeforeSave = function(checked){

	if(!checked)
	{
		this.Save(checked, "");
		return;
	}
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 412,
			height : 198,
			title : "توضیحات",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 6,
				name : "description"
			}],
			closeAction : "hide",
			buttons : [{
				text : "ذخیره",				
				iconCls : "save",
				itemId : "btn_save"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	this.commentWin.down("[itemId=btn_save]").setHandler(function(){
		CheckValuesObject.Save(checked,
			this.up('window').down("[name=description]").getValue());});
		
	this.commentWin.show();
	this.commentWin.center();
}

CheckValues.prototype.Save = function(checked, description){

	var record = this.grid.getSelectionModel().getLastSelected();
	if(record == null)
	{
		this.commentWin.hide();
		return;
	}
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'baseInfo.data.php',
		method: "POST",
		params: {
			task: "SaveCheckValue",
			ItemID : record.data.ItemID,
			SourceID : <?= $_REQUEST["SourceID"] ?>,
			checked : checked ? 1 : 0,
			description : description
		},
		success: function(response){
			mask.hide();
			if(CheckValuesObject.commentWin)
				CheckValuesObject.commentWin.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				CheckValuesObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

var CheckValuesObject = new CheckValues();

</script>
<center>
	<div id="div_grid"></div>
</center>