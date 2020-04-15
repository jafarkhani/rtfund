<?php
//-----------------------------
//	Date		: 1395.06
//-----------------------------
 
require_once '../header.inc.php';
require_once inc_dataGrid;

$LetterID = $_REQUEST["LetterID"];
if(empty($LetterID))
	die();

$editable = true;
$ReadOnly = isset($_REQUEST["ReadOnly"]) && $_REQUEST["ReadOnly"] == "true" ? true : false;
		
$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=GetRefLetters&LetterID=" . $LetterID, "grid_div");

$col = $dg->addColumn("نامه مبدا", "LetterID","");
$col->width = 60;

$col = $dg->addColumn("شماره نامه", "RefLetterID","");
$col->renderer = "RefLetters.RefLetterRender";
$col->editor = ColumnEditor::NumberField();
$col->width = 70;

$col = $dg->addColumn("عنوان نامه", "LetterTitle");

if($editable && !$ReadOnly)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return RefLettersObject.SaveRefLetters(record);}";

	$dg->addButton("AddBtn", "اتصال نامه", "add", "function(){RefLettersObject.Add();}");

	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return RefLetters.DeleteRender(v,p,r);}";
	$col->width = 35;
}
$dg->autoExpandColumn = "LetterTitle";
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->width = 560;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "LetterTitle";
$dg->DefaultSortDir = "ASC";

$grid = $dg->makeGrid_returnObjects();

?>
<script>
RefLetters.prototype = {
	TabID : "<?= $_REQUEST["ExtTabID"] ?>",
	address_prefix : "<?= $js_prefix_address?>",

	LetterID : "<?= $LetterID ?>",
	ReadOnly : <?= $ReadOnly ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function RefLetters()
{	
	this.grid = <?= $grid ?>;
	if(!this.ReadOnly)
		this.grid.plugins[0].on("beforeedit", function(editor,e){

			if(e.record.data.RefLetterID*1 > 0)
				return false;
		});
	/*this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.RefLetterID == RefLettersObject.LetterID)
			return "violetRow";
		return "";
	}*/	
	this.grid.render(this.get("div_grid"));
}

RefLetters.RefLetterRender = function(v,p,r){
	
	if(r.data.RefLetterID == RefLettersObject.LetterID || RefLettersObject.ReadOnly)
		return v;
	return "<a href='javascript:void(0)' onclick='RefLetters.OpenRefLetter(" + v + ")'>" + v + "</a>";
}

RefLetters.OpenRefLetter = function(LetterID){
	framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", {
		LetterID : LetterID
	});
}

RefLetters.DeleteRender = function(v,p,r){
	
	/*if(r.data.RefLetterID == RefLettersObject.LetterID)
		return "";*/
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='RefLettersObject.Delete();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

RefLettersObject = new RefLetters();
	
RefLetters.prototype.Add = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RefLetterID: null,
		LetterID : this.LetterID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}
	
RefLetters.prototype.SaveRefLetters = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'letter.data.php',
		method: "POST",
		params: {
			task: "SaveRefLetter",
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
				RefLettersObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

RefLetters.prototype.Delete = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = RefLettersObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			params:{
				task: "DeleteRefLetter",
				LetterID : record.data.LetterID,
				RefLetterID : record.data.RefLetterID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					RefLettersObject.grid.getStore().load();
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