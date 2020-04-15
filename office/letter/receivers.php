<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=GetReceivers", "grid_div");

$dg->addColumn("", "RowID", "", true);
$dg->addColumn("", "id", "", true);
$dg->addColumn("", "title", "", true);
$dg->addColumn("", "type", "", true);

$col = $dg->addColumn("نام و نام خانوادگی", "id", "");
$col->renderer="function(v,p,r){return r.data.title;}";
$col->editor = "this.PersonCombo";

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){OFC_receiverObject.AddPerson();}";
	
	$dg->enableRowEdit = true ;
	$dg->rowEditOkHandler = "function(v,p,r){ return OFC_receiverObject.Save(v,p,r);}";
}
$dg->title = "لیست کاربران جهت ارجاع نامه";

$dg->height = 500;
$dg->width = 750;
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "id";
$dg->emptyTextOfHiddenColumns = true;

$col = $dg->addColumn("حذف", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return OFC_receiver.DeleteRender(v,p,r);}";
$col->width = 50;

$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
        <br>
        <div id="grid_div"></div>
    </form>
</center>
<script>
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------

OFC_receiver.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',
	SelectedType : "",


	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function OFC_receiver(){
	
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + 'letter.data.php?task=receiversSelect',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['id','title','type']
		}),
		tpl : new Ext.XTemplate(
			'<tpl for=".">',
				'<tpl if="type == \'Group\'">',
					'<div class="x-boundlist-item" style="background-color:#fcfcb6">{title}</div>',
				'<tpl else>',
					'<div class="x-boundlist-item">{title}</div>',
				'</tpl>',						
			'</tpl>'
		),
		fieldLabel : "کاربر",
		displayField: 'title',
		valueField : "id",
		hiddenName : "id",
		width : 400,
		itemId : "id",
		listeners :{
			select : function(combo,records){
				OFC_receiverObject.SelectedType = records[0].data.type;
			}			
		}
	});
	
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.type == "Group")
			return "yellowRow";
		return "";
	}	
	this.grid.render(this.get("grid_div"));
}

OFC_receiver.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
	"onclick='OFC_receiverObject.DeletePerson();' " +
	"style='background-repeat:no-repeat;background-position:center;" +
	"cursor:pointer;width:100%;height:16'></div>";
}

OFC_receiver.prototype.Save = function(store,record,op){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();    
	Ext.Ajax.request({
		url: this.address_prefix + 'letter.data.php?task=SaveReceiver',
		params:{
			id : record.data.id,
			type : this.SelectedType
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			OFC_receiverObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

OFC_receiver.prototype.DeletePerson = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = OFC_receiverObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php',
			params:{
				task: "DeleteReceiver",
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				OFC_receiverObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

OFC_receiver.prototype.AddPerson = function(){
	
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		id:null		

	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

var OFC_receiverObject = new OFC_receiver();	

</script>