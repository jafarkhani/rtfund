<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	97.01
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "cheques.data.php?task=SelectIncomeChequeStatuses", "grid_div");

$dg->addColumn("", "IsActive", "", true);
$dg->addColumn("", "bed_CostCode", "", true);
$dg->addColumn("", "bes_CostCode", "", true);

$col = $dg->addColumn("کد", "InfoID");
$col->width = 50;

$col = $dg->addColumn("شرح", "InfoDesc", "");
$col->editor = ColumnEditor::TextField();

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){ChequeStatusObject.AddChequeStatus();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("غیر فعال", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return ChequeStatus.DeleteRender(v,p,r);}";
	$col->width = 70;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return ChequeStatusObject.SaveChequeStatus();}";

$dg->title = "لیست اطلاعات";
$dg->height = 500;
$dg->width = 750;
$dg->DefaultSortField = "InfoDesc";
$dg->autoExpandColumn = "InfoDesc";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
        <br>
        <div id="div_grid"></div>
    </form>
</center>
<script>

ChequeStatus.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function ChequeStatus(){

	this.accountCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		valueField : "CostID",
		displayField : "fullDesc"
	});
	
	this.accountCombo2 = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		valueField : "CostID",
		displayField : "fullDesc"
	});

	this.grid = <?= $grid ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		if(e.record.data.IsActive == "NO")
			return false;
		if(e.record.data.ObjectID*1 > 0)
			return false;
		if(!e.record.data.InfoID)
			return ChequeStatusObject.AddAccess;
		return ChequeStatusObject.EditAccess;
	});
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsActive == "NO")
			return "pinkRow";
	}
	this.grid.render(this.get("div_grid"));
}

var ChequeStatusObject = new ChequeStatus();	

ChequeStatus.DeleteRender = function(v,p,r){
	
	if(r.data.IsActive == "NO")
		return "";
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='ChequeStatusObject.DeleteChequeStatus();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

ChequeStatus.prototype.AddChequeStatus = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		InfoID: 0,
		ChequeStatusCode: null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

ChequeStatus.prototype.SaveChequeStatus = function(index){

	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'cheques.data.php',
		method: "POST",
		params: {
			task: "SaveStatus",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				ChequeStatusObject.grid.getStore().load();
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

ChequeStatus.prototype.DeleteChequeStatus = function(){
	
	Ext.MessageBox.confirm("","در صورتی که آیتم مورد نظر استفاده نشده باشد حذف می شود. آیا مایل به ادامه می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ChequeStatusObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'cheques.data.php',
			params:{
				task: "DeleteStatus",
				InfoID : record.data.InfoID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				ChequeStatusObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

</script>