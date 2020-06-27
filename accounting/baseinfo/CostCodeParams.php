<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.06
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "baseinfo.data.php?task=selectParamItems", "grid_div");

$dg->addColumn("", "ItemID", "", true);

$col = $dg->addColumn("عنوان آیتم", "ParamValue");
$col->editor = ColumnEditor::TextField();


$col = $dg->addColumn("پارامتر اول", "f1", ""); 
$col->editor = ColumnEditor::TextField(true);
$col->width = 100;

$col = $dg->addColumn("پارامتر دوم", "f2", "");
$col->editor = ColumnEditor::TextField(true);
$col->width = 100;

if($accessObj->AddFlag)
{
	$dg->addButton("btn_add","ایجاد","add", "function(){CostCodeParamItemObject.AddItem();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("عملیات", "ParamID");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return CostCodeParamItem.DeleteRender(v,p,r);}";
	$col->width = 50;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return CostCodeParamItemObject.SaveItem();}";

$dg->title = "لیست مقادیر";
$dg->height = 460;
$dg->DefaultSortField = "ItemID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "ParamValue";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
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

CostCodeParamItem.prototype = { 
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function CostCodeParamItem(){

	this.grid = <?= $grid ?>;
	
	this.groupPnl = new Ext.form.Panel({
		title: "آیتم های کد حساب",
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
					CostCodeParamItemObject.SaveParam(text);
				});
			}
		},{
			text : "حذف گروه",
			hidden : this.RemoveAccess ? false : true,
			itemId : "cmp_removeGroup",
			iconCls : "remove",
			handler : function(){
				CostCodeParamItemObject.DeleteParam(this.up('form').down('[name=ParamID]').getValue());
			}
		}],
		items : [{
			xtype : "multiselect",
			store : new Ext.data.SimpleStore({
				proxy: {type: 'jsonp',
					url: this.address_prefix + 'baseinfo.data.php?task=selectParams',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},				
				fields : ['ParamID','ParamDesc','SrcTable',{
						name : "fulltitle",
						convert : function(v,r){return "[" + r.data.ParamID + "] " + r.data.ParamDesc;}
				}],
				autoLoad : true
			}),
			valueField : "ParamID",
			queryMode : "local",
			autoWidth : true,
			autoScroll : true,
			height : 400,
			name : "ParamID",
			displayField : "fulltitle"
		}]
	});
	
	this.groupPnl.down("[name=ParamID]").boundList.on('itemdblclick', function(view, record){
		CostCodeParamItemObject.LoadCostCodeParamItems(record); 
	});
}

CostCodeParamItem.prototype.SaveParam = function(text){
	
	var mask = new Ext.LoadMask(this.groupPnl,{msg: 'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		method : "POST",
		url: this.address_prefix + "baseinfo.data.php",
		params: {
			task: "SaveParam",
			ParamDesc: text
		},
		success: function(response){
			mask.hide();
			CostCodeParamItemObject.groupPnl.down("[name=ParamID]").getStore().load();
		}
	});
}

CostCodeParamItem.prototype.LoadCostCodeParamItems = function(record){

	this.ParamID = record.data.ParamID;

	this.grid.getStore().proxy.extraParams.ParamID = this.ParamID;

	if(this.grid.rendered)
		this.grid.getStore().load();
	else
		this.grid.render(this.get("div_grid"));
	
	this.grid.show();
	if(record.data.SrcTable != null)
	{
		this.grid.down("[itemId=btn_add]").hide();
		this.grid.columns.findObject('dataIndex','ParamID').hide();
	}
	else
	{
		this.grid.down("[itemId=btn_add]").show();
		this.grid.columns.findObject('dataIndex','ParamID').show();
	}
}

CostCodeParamItem.DeleteRender = function(v,p,r){
	
	if(r.data.ObjectID*1 > 0)
		return "";
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='CostCodeParamItemObject.DeleteCostCodeParamItem();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

CostCodeParamItem.prototype.AddItem = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		CostCodeParamItemID: null,
		ParamID : this.grid.getStore().proxy.extraParams.ParamID,
		CostCodeParamItemCode: null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

CostCodeParamItem.prototype.SaveItem = function(index){

	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'baseinfo.data.php',
		method: "POST",
		params: {
			task: "saveParamItem",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				CostCodeParamItemObject.grid.getStore().load();
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

CostCodeParamItem.prototype.DeleteParam = function(ParamID){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = CostCodeParamItemObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'baseinfo.data.php',
			params:{
				task: "DeleteParam",
				ParamID : ParamID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);

				if(sd.success)
				{
					CostCodeParamItemObject.groupPnl.down('[name=ParamID]').getStore().load();
					CostCodeParamItemObject.grid.hide();
				}	
				else
				{
					Ext.MessageBox.alert("Error",sd.data);
				}
			},
			failure: function(){}
		});
	});
}

CostCodeParamItem.prototype.DeleteCostCodeParamItem = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = CostCodeParamItemObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'baseinfo.data.php',
			params:{
				task: "DeleteParamItem",
				ItemID : record.data.ItemID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				CostCodeParamItemObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

var CostCodeParamItemObject = new CostCodeParamItem();	

</script>