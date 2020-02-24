<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.12
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "baseInfo.data.php?task=SelectBaseInfo", "grid_div");

$dg->addColumn("گروه", "TypeID", "", true);
$dg->addColumn("", "IsActive", "", true);

$col = $dg->addColumn("کد", "InfoID");
$col->width = 50; 
if($_SESSION["USER"]["UserName"] =="admin" )
{
	$col->editor = ColumnEditor::NumberField();
}

$col = $dg->addColumn("شرح", "InfoDesc", "");
$col->editor = ColumnEditor::TextField();

if($_SESSION["USER"]["UserName"] =="admin" )
{
	$col = $dg->addColumn("param1", "param1", "");
	$col->editor = ColumnEditor::TextField(true);
	$col->width = 80;
	
	$col = $dg->addColumn("param2", "param2", "");
	$col->editor = ColumnEditor::TextField(true);
	$col->width = 80;

	$col = $dg->addColumn("param3", "param3", "");
	$col->editor = ColumnEditor::TextField(true);
	$col->width = 80;
	
	$col = $dg->addColumn("param4", "param4", "");
	$col->editor = ColumnEditor::TextField(true);
	$col->width = 80;
	
	$col = $dg->addColumn("param5", "param5", "");
	$col->editor = ColumnEditor::TextField(true);
	$col->width = 80;
	
	$col = $dg->addColumn("param6", "param6", "");
	$col->editor = ColumnEditor::TextField(true);
	$col->width = 80;
	
	$col = $dg->addColumn("param7", "param7", "");
	$col->editor = ColumnEditor::TextField(true);
	$col->width = 80;
}
if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){BaseInfoObject.AddBaseInfo();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("غیر فعال", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return BaseInfo.DeleteRender(v,p,r);}";
	$col->width = 40;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return BaseInfoObject.SaveBaseInfo();}";

$dg->title = "لیست اطلاعات";
$dg->height = 460;
if($_SESSION["USER"]["UserName"] != "admin")
	$dg->width = 500;
$dg->DefaultSortField = "InfoDesc";
$dg->autoExpandColumn = "InfoDesc";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
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

BaseInfo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function BaseInfo(){

	this.grid = <?= $grid ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		if(e.record.data.IsActive == "NO")
			return false;
		if(e.record.data.ObjectID*1 > 0)
			return false;
		if(!e.record.data.InfoID)
			return BaseInfoObject.AddAccess;
		return BaseInfoObject.EditAccess;
	});
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsActive == "NO")
			return "pinkRow";
	}
	
	this.groupPnl = new Ext.form.Panel({
		title: "گروه های اطلاعات دامین",
		width : 300,
		autoHeight : true,
		applyTo : this.get("div_selectGroup"),
		frame: true,
		tbar : [{
			text : "ایجاد",
			iconCls : "add",
			//hidden : true,
			handler : function(){
				
				Ext.MessageBox.prompt('', 'عنوان  :', function(btn, text){
					if(btn == "cancel")
						return;
					BaseInfoObject.SaveType(text);
				});
			}
		}],
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {type: 'jsonp',
					url: this.address_prefix + 'baseInfo.data.php?task=SelectBaseTypeGroups',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true,
				fields : ['InfoID','InfoDesc']
			}),
			valueField : "InfoID",
			queryMode : "local",
			width : 290,
			name : "GroupID",
			displayField : "InfoDesc",
			listeners : {
				select : function(combo,records){
					ms = BaseInfoObject.groupPnl.down("[name=TypeID]");
					ms.getStore().proxy.extraParams.GroupID = this.getValue();
					ms.getStore().load();
				}
			}
		},{
			xtype : "multiselect",
			store : new Ext.data.SimpleStore({
				proxy: {type: 'jsonp',
					url: this.address_prefix + 'baseInfo.data.php?task=SelectBaseTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},				
				fields : ['TypeID','TypeDesc',{
						name : "title", convert(value,record){ 
							return "[" + record.data.TypeID + "] " + record.data.TypeDesc;}
				}]
			}),
			valueField : "TypeID",
			queryMode : "local",
			autoWidth : true,
			autoScroll : true,
			height : 400,
			name : "TypeID",
			displayField : "title"
		}]
	});
	
	this.groupPnl.down("[name=TypeID]").boundList.on('itemdblclick', function(view, record){
		BaseInfoObject.grid.getStore().proxy.extraParams.TypeID = record.data.TypeID;
		if(BaseInfoObject.grid.rendered)
			BaseInfoObject.grid.getStore().load();
		else
			BaseInfoObject.grid.render(BaseInfoObject.get("div_grid"));
	});
}

var BaseInfoObject = new BaseInfo();	

BaseInfo.DeleteRender = function(v,p,r){
	
	if(r.data.IsActive == "NO")
		return "";
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='BaseInfoObject.DeleteBaseInfo();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

BaseInfo.prototype.AddBaseInfo = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		InfoID: 0,
		TypeID : this.grid.getStore().proxy.extraParams.TypeID,
		BaseInfoCode: null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

BaseInfo.prototype.SaveBaseInfo = function(index){

	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'baseInfo.data.php',
		method: "POST",
		params: {
			task: "SaveBaseInfo",
			OldInfoID : (record.raw) ? record.raw.InfoID : 0,
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				BaseInfoObject.grid.getStore().load();
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

BaseInfo.prototype.DeleteBaseInfo = function(){
	
	Ext.MessageBox.confirm("","در صورتی که آیتم مورد نظر استفاده نشده باشد حذف می شود. آیا مایل به ادامه می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = BaseInfoObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'baseInfo.data.php',
			params:{
				task: "DeleteBaseInfo",
				TypeID : record.data.TypeID,
				InfoID : record.data.InfoID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				BaseInfoObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

BaseInfo.prototype.SaveType = function(text){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'baseInfo.data.php',
		method: "POST",
		params: {
			task: "SaveBaseType",
			GroupID : this.groupPnl.down("[name=GroupID]").getValue(),
			TypeDesc : text
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{   
				BaseInfoObject.groupPnl.down("[name=TypeID]").getStore().load();
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

</script>