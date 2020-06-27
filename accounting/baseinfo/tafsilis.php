<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "baseinfo.data.php?task=GetAllTafsilis", "grid_div");

$dg->addColumn("", "TafsiliID", "", true);
$dg->addColumn("", "TafsiliType", "", true);
$dg->addColumn("", "title", "", true);
$dg->addColumn("", "ObjectID", "", true);

$col = $dg->addColumn("کد تفصیلی", "TafsiliCode");
$col->editor = ColumnEditor::TextField();
$col->width = 100;

$col = $dg->addColumn("عنوان تفصیلی", "TafsiliDesc", "");
$col->editor = ColumnEditor::TextField();

if($_SESSION["USER"]["UserName"] == "admin")
{
	$col = $dg->addColumn("[ObjectID", "ObjectID", "");
	$col->editor = ColumnEditor::NumberField(true);
	
	$col = $dg->addColumn("[ObjectType", "ObjectType", "");
	$col->editor = ColumnEditor::TextField(true);
}

if($accessObj->AddFlag)
{
	$dg->addButton("btn_add","ایجاد","add", "function(){TafsiliObject.AddTafsili();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("عملیات", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return Tafsili.DeleteRender(v,p,r);}";
	$col->width = 50;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return TafsiliObject.SaveTafsili();}";

$dg->title = "لیست تفصیلی ها";
$dg->height = 460;
$dg->DefaultSortField = "TafsiliCode";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "TafsiliDesc";
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
		<table style="margin: 10px; width:98%" >
			<tr>
				<td width="300px"><div id="div_selectGroup"></div></td>
				<td><div id="div_grid" style="width:100%"></div></td>
			</tr>
		</table>
    </form>
</center>
<script>

Tafsili.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function Tafsili(){

	this.grid = <?= $grid ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		
		//if(e.record.data.ObjectID*1 > 0)
		//	return false;
		if(e.record.data.ObjectID*1 > 0)
			TafsiliObject.grid.plugins[0].editor.form.findField('TafsiliDesc').disable();
		else
			TafsiliObject.grid.plugins[0].editor.form.findField('TafsiliDesc').enable(); 
		if(!e.record.data.TafsiliID)
			return TafsiliObject.AddAccess;
		return TafsiliObject.EditAccess;
	});
	
	this.groupPnl = new Ext.form.Panel({
		title: "گروه های تفصیلی",
		width : 300,
		autoHeight : true,
		applyTo : this.get("div_selectGroup"),
		frame: true,
		tbar : [{
			text : "ایجاد",
			iconCls : "add",
			hidden : this.AddAccess ? false : true,
			handler : function(){
				
				Ext.MessageBox.prompt('', 'عنوان  :', function(btn, text){
					if(btn == "cancel")
						return;
					TafsiliObject.SaveTafsiliType(text);
				});
			}
		},{
			text : "حذف گروه",
			hidden : this.RemoveAccess ? false : true,
			itemId : "cmp_removeGroup",
			iconCls : "remove",
			handler : function(){
				TafsiliObject.DeleteGroup(this.up('form').down('[name=TafsiliType]').getValue());
			}
		}],
		items : [{
			xtype : "multiselect",
			store : new Ext.data.SimpleStore({
				proxy: {type: 'jsonp',
					url: this.address_prefix + 'baseinfo.data.php?task=SelectTafsiliGroups',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},				
				fields : ['InfoID','InfoDesc','param1',{
						name : "title", convert(value,record){ 
							return "[" + record.data.InfoID + "] " + record.data.InfoDesc;}
				}],
				autoLoad : true
			}),
			valueField : "InfoID",
			queryMode : "local",
			autoWidth : true,
			autoScroll : true,
			height : 400,
			name : "TafsiliType",
			displayField : "title"
		}]
	});
	
	this.groupPnl.down("[name=TafsiliType]").boundList.on('itemdblclick', function(view, record){
		TafsiliObject.LoadTafsilis(); 
	});
}

Tafsili.prototype.SaveTafsiliType = function(text){
	
	var mask = new Ext.LoadMask(this.groupPnl,{msg: 'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		method : "POST",
		url: this.address_prefix + "baseinfo.data.php",
		params: {
			task: "AddGroup",
			GroupDesc: text
		},
		success: function(response){
			mask.hide();
			TafsiliObject.groupPnl.down("[name=TafsiliType]").getStore().load();
		}
	});
}

Tafsili.prototype.LoadTafsilis = function(){

	this.TafsiliType = this.groupPnl.down('[name=TafsiliType]').getValue()[0];

	this.grid.getStore().proxy.extraParams.TafsiliType = this.TafsiliType;

	if(this.grid.rendered)
		this.grid.getStore().load();
	else
		this.grid.render(this.get("div_grid"));
	
	this.grid.show();
}

Tafsili.DeleteRender = function(v,p,r){
	
	if(r.data.ObjectID*1 > 0)
		return "";
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='TafsiliObject.DeleteTafsili();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

Tafsili.prototype.AddTafsili = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		TafsiliID: null,
		TafsiliType : this.grid.getStore().proxy.extraParams.TafsiliType,
		TafsiliCode: null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

Tafsili.prototype.SaveTafsili = function(index){

	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'baseinfo.data.php',
		method: "POST",
		params: {
			task: "SaveTafsili",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				TafsiliObject.grid.getStore().load();
			}
			else
			{
				if(st.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(st.data);
			}
		},
		failure: function(){}
	});
}

Tafsili.prototype.DeleteGroup = function(TafsiliType){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = TafsiliObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'baseinfo.data.php',
			params:{
				task: "DeleteGroup",
				TafsiliType : TafsiliType
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);

				if(sd.success)
				{
					TafsiliObject.groupPnl.down('[name=TafsiliType]').setValue();
					TafsiliObject.groupPnl.down('[name=TafsiliType]').getStore().load();
					TafsiliObject.grid.hide();
				}	
				else
				{
					Ext.MessageBox.alert("Error","در این گروه وام تعریف شده و قادر به حذف آن نمی باشید");
				}
			},
			failure: function(){}
		});
	});
}

Tafsili.prototype.DeleteTafsili = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = TafsiliObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'baseinfo.data.php',
			params:{
				task: "DeleteTafsili",
				TafsiliID : record.data.TafsiliID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				TafsiliObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

var TafsiliObject = new Tafsili();	

</script>