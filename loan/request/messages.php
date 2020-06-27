<?php
//---------------------------
// programmer:	Sh.Jafarkhani
// create Date:	95.01
//---------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
//$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

if(empty($_REQUEST["RequestID"]))
	die();
$RequestID = $_REQUEST["RequestID"];

$dgh = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=SelectAllMessages&".
		"RequestID=" . $RequestID,"div_dg");

$dgh->addColumn("", "MessageID","",true);
$dgh->addColumn("", "DoneDesc","",true);
$dgh->addColumn("", "DoneDate","",true);
$dgh->addColumn("", "RequestID","",true);

$col = $dgh->addColumn("شرح", "details");
$col->renderer = "function(v,p,r){ return LoanMessagesObj.DescRender(v,p,r);}";
$col->editor = ColumnEditor::TextField();

$col = $dgh->addColumn("زمان ایجاد", "CreateDate", GridColumn::ColumnType_datetime);
$col->width = 120;

$col = $dgh->addColumn("وضعیت", "MsgStatus");
$col->renderer = "function(v,p,r){ return LoanMessagesObj.StatusRender(v,p,r);}";
$col->width = 90;

$col = $dgh->addColumn("زمان اقدام", "DoneDate", GridColumn::ColumnType_datetime);
$col->width = 120;

if(session::IsPortal() )
{
	$dgh->addButton("", "ایجاد پیام جدید", "add", "function(){LoanMessagesObj.AddMessage();}");
	$dgh->enableRowEdit = true;
	$dgh->rowEditOkHandler = "function(store,record){return LoanMessagesObj.SaveMessage(record);}";
	
	$col = $dgh->addColumn("عملیات", "");
	$col->renderer = "function(v,p,r){ return LoanMessagesObj.OperationRender(v,p,r);}";
	$col->width = 60;
}

if(session::IsFramework())
{
	$col = $dgh->addColumn("", "");
	$col->renderer = "function(v,p,r){ return LoanMessagesObj.ActionRender(v,p,r);}";
	$col->width = 30;
}

$dgh->width = 700;
$dgh->DefaultSortField = "CreateDate";
$dgh->autoExpandColumn = "details";
$dgh->DefaultSortDir = "DESC";
$dgh->height = 400;
$dgh->emptyTextOfHiddenColumns = true;
$dgh->EnableSearch = false;
$dgh->pageSize = 15;
$grid = $dgh->makeGrid_returnObjects();

?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
.greenRow,.greenRow td,.greenRow div{ background-color:#D0F7E2 !important;}
</style>
<script>
LoanMessages.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	RequestID : "<?= $RequestID ?>",
		
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function LoanMessages()
{
	this.grid = <?=$grid?>;
	this.grid.getView().getRowClass = function(record)
	{
		if(record.data.MsgStatus == "RESPONSE" || record.data.MsgStatus == "DONE")
			return "greenRow";
		return "";
	}
	this.grid.render(this.get("div_dg"));
}

LoanMessagesObj = new LoanMessages();

LoanMessages.prototype.AddMessage = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		MessageID: null,
		RequestID : this.RequestID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

LoanMessages.prototype.SaveMessage = function(record){
	
	mask = new Ext.LoadMask(this.grid, {msg:'در حال حذف...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'request.data.php?task=saveMessage',
		params:{
			record: Ext.encode(record.data)
		},
		method: 'POST',

		success: function(){
			mask.hide();
			LoanMessagesObj.grid.getStore().load();
		},
		failure: function(){}
	});
	
}

LoanMessages.prototype.Remove = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return; 
		
		me = LoanMessagesObj;
		var record = me.grid.getSelectionModel().getLastSelected();
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php?task=removeMessage',
			params:{
				MessageID: record.data.MessageID
			},
			method: 'POST',

			success: function(response){
				mask.hide();
				LoanMessagesObj.grid.getStore().load();
			},
			failure: function(){}
		});
	});
	
}

LoanMessages.prototype.DescRender = function(v, p, record){

	var desc = record.data.details;
	desc = desc.replace(/\n/g, "<br>");
	var DoneDesc = record.data.DoneDesc == null ? "" : record.data.DoneDesc; 
	DoneDesc = DoneDesc.replace(/\n/g, "<br>");
	
	desc = "<b>شرح : </b>" + (desc == "" ? "---" : desc);	

	if(DoneDesc != "" && DoneDesc != null)
		desc += "<hr><b>پاسخ : </b>" + DoneDesc;

	p.tdAttr = 'data-qtip="' + desc + '"';
	return v;
}

LoanMessages.prototype.StatusRender = function(v, p, record){

	switch(v)
	{
		case "RAW": return "ثبت پیام";
		case "DONE": return "اقدام شده";
		case "RESPONSE": return "پاسخ داده شده";
	}
}

LoanMessages.prototype.OperationRender = function(v,p,r){

	if(r.data.MsgStatus == "RAW")
		return "<div style='background-repeat:no-repeat;background-position:center;"+
			"cursor:pointer;height:16;width:20px;float:right' "+
			" onclick=LoanMessagesObj.Remove() class=remove></div>";
	return "";
}

LoanMessages.prototype.ActionRender = function(v,p,r){

	if(r.data.MsgStatus == "RAW")
		return "<div style='background-repeat:no-repeat;background-position:center;"+
			"cursor:pointer;height:16;width:20px;float:left' "+
			" onclick=LoanMessagesObj.ActionMessage() class=send></div>";
}

LoanMessages.prototype.ActionMessage = function(){
	
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 412,
			height : 200,
			modal : true,
			title : "پاسخ",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "DoneDesc"
			}],
			closeAction : "hide",
			buttons : [{
				text : "ذخیره",				
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){
					me = LoanMessagesObj;
					var record = me.grid.getSelectionModel().getLastSelected();
					mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف...'});
					mask.show();

					Ext.Ajax.request({
						url: me.address_prefix + 'request.data.php?task=saveMessage',
						params:{
							MessageID: record.data.MessageID,
							DoneDesc : me.commentWin.down("[name=DoneDesc]").getValue()
						},
						method: 'POST',

						success: function(response){
							mask.hide();
							LoanMessagesObj.grid.getStore().load();
							LoanMessagesObj.commentWin.hide();
						},
						failure: function(){}
					});
				}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	this.commentWin.show();
	this.commentWin.center();	
}



</script>
<div id="div_dg"></div>
