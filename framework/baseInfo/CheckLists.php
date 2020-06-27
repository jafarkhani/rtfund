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

$dg = new sadaf_datagrid("dg", $js_prefix_address . "baseInfo.data.php?task=SelectCheckLists", "grid_div");

$dg->addColumn("", "SourceType", "", true);
$dg->addColumn("کد", "ItemID", "", true);

$col = $dg->addColumn("شرح", "ItemDesc", "");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("ترتیب", "ordering", "");
$col->editor = ColumnEditor::NumberField();
$col->width = 40;

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){CheckListObject.AddCheckList();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return CheckList.DeleteRender(v,p,r);}";
	$col->width = 50;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return CheckListObject.SaveCheckList();}";

$dg->title = "لیست آیتم های چک لیست";
$dg->height = 500;
$dg->width = 500;
$dg->DefaultSortField = "ordering";
$dg->DefaultSortDir = "Asc";
$dg->autoExpandColumn = "ItemDesc";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
        <br>
        <div id="div_selectGroup"></div>
        <br>
        <div id="div_grid"></div>
    </form>
</center>
<script>

CheckList.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function CheckList(){

	this.grid = <?= $grid ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		if(!e.record.data.InfoID)
			return CheckListObject.AddAccess;
		return CheckListObject.EditAccess;
	});
	
	this.groupPnl = new Ext.form.Panel({
		renderTo: this.get("div_selectGroup"),
		title: "انتخاب آیتم",
		width: 400,
		collapsible : true,
		collapsed : false,
		frame: true,
		bodyCfg: {style: "background-color:white"},
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {type: 'jsonp',
					url: this.address_prefix + 'baseInfo.data.php?task=SelectCheckListSources',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true,
				fields : ['InfoID','InfoDesc']
			}),
			valueField : "InfoID",
			queryMode : "local",
			width : 380,
			name : "SourceType",
			displayField : "InfoDesc",
			fieldLabel : "انتخاب آیتم",
			listeners :{
				select : function(){
					CheckListObject.grid.getStore().proxy.extraParams = {
						SourceType : this.getValue()
					};
					if(CheckListObject.grid.rendered)
						CheckListObject.grid.getStore().load();
					else
						CheckListObject.grid.render(CheckListObject.get("div_grid"));
				}
			}
		}]
	});	
}

var CheckListObject = new CheckList();	

CheckList.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='CheckListObject.DeleteCheckList();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

CheckList.prototype.AddCheckList = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		ItemID: 0,
		SourceType : this.groupPnl.down("[name=SourceType]").getValue()
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

CheckList.prototype.SaveCheckList = function(index){

	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'baseInfo.data.php',
		method: "POST",
		params: {
			task: "SaveCheckList",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				CheckListObject.grid.getStore().load();
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

CheckList.prototype.DeleteCheckList = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = CheckListObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'baseInfo.data.php',
			params:{
				task: "DeleteCheckList",
				ItemID : record.data.ItemID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				CheckListObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

</script>