<?php
//-----------------------------
//	Date		: 1395.06
//-----------------------------
 
require_once '../header.inc.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=GetSendComments", "grid_div");

$dg->addColumn("", "RowID","", true);
$dg->addColumn("", "PersonID","", true);

$col = $dg->addColumn("شرح ارجاع", "SendComment");
$col->editor = ColumnEditor::TextField();

$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(store,record){return SendCommentObject.SaveSendComment(record);}";

$dg->addButton("AddBtn", "ایجاد شرح جدید", "add", "function(){SendCommentObject.Add();}");

$col = $dg->addColumn("حذف", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return SendComment.DeleteRender(v,p,r);}";
$col->width = 35;

$dg->autoExpandColumn = "SendComment";
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 465;
$dg->width = 600;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "SendComment";
$dg->DefaultSortDir = "ASC";

$grid = $dg->makeGrid_returnObjects();

?>
<script>
SendComment.prototype = {
	TabID : "<?= $_REQUEST["ExtTabID"] ?>",
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function SendComment()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));
	
}

SendComment.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='SendCommentObject.Delete();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

SendCommentObject = new SendComment();
	
SendComment.prototype.Add = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RowID : null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}
	
SendComment.prototype.SaveSendComment = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'letter.data.php',
		method: "POST",
		params: {
			task: "SaveSendComment",
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
				SendCommentObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

SendComment.prototype.Delete = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = SendCommentObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			params:{
				task: "DeleteSendComment",
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					SendCommentObject.grid.getStore().load();
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