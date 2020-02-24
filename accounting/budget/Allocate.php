<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98..03
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "budget.data.php?task=SelectBudgetAllocs", "grid_div");

$dg->addColumn("", "AllocID", "", true);
$dg->addColumn("", "BudgetID", "", true);
$dg->addColumn("", "BudgetDesc", "", true);

$col = $dg->addColumn("بودجه", "BudgetDesc");
$col->width = 200;
$col = $dg->addColumn("تاریخ تخصیص", "AllocDate", GridColumn::ColumnType_date);
$col->width = 150;
$col = $dg->addColumn("مبلغ تخصیص", "AllocAmount", GridColumn::ColumnType_money);
$col->width = 150;
$dg->addColumn("جزئیات", "details", "");
//$dg->EnableGrouping = true;
//$dg->DefaultGroupField = "BudgetDesc";

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){ACC_BudgetAllocObject.AddBudgetAlloc();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("عملیات", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return ACC_BudgetAlloc.OperationRender(v,p,r);}";
	$col->width = 50;
}

$dg->title = "تخصیص بودجه";
$dg->height = 500;
$dg->DefaultSortField = "AllocDate";
$dg->autoExpandColumn = "details";
$dg->emptyTextOfHiddenColumns = true;
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
		<div id="newDiv"></div>
        <div id="grid_div" style="margin: 10px"></div>
    </form>
</center>
<script>

ACC_BudgetAlloc.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function ACC_BudgetAlloc(){

	this.grid = <?= $grid ?>;
	this.grid.render(this.get("grid_div"));
	
	this.formPanel = new Ext.form.Panel({
		frame : true,
		hidden : true,
		style : "margin:10px 0 10px",
		renderTo : this.get("newDiv"),
		width : 700,
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {width : 300},
		title : "تخصیص بودجه",
		items : [{
			xtype : "treecombo",
			selectChildren: true,
			canSelectFolders: false,
			multiselect : false,
			name : "BudgetID",
			colspan : 2,
			width : 620,
			fieldLabel: "بودجه",
			store : new Ext.data.TreeStore({
				proxy: {
					type: 'ajax',
					url:  this.address_prefix + 'budget.data.php?task=GetBudgetTree' 
				},
				root: {
					text: "بودجه",
					id: 'src',
					expanded: true
				}
			})
		},{
			xtype : "shdatefield",
			fieldLabel : "تاریخ تخصیص",
			name : "AllocDate"
		},{
			xtype : "currencyfield",
			fieldLabel : "مبلغ تخصیص",
			name : "AllocAmount",
			hideTrigger : true
		},{
			xtype : "textfield",
			fieldLabel : "جزئیات",
			name : "details",
			colspan : 2,
			width : 630
		},{
			xtype : "hidden",
			name : "AllocID"
		}],
		buttons : [{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ACC_BudgetAllocObject.SaveBudgetAlloc();}
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){this.up('panel').hide();}
		}]
	});
}

ACC_BudgetAlloc.OperationRender = function(v,p,r){
	
	st = "";
	
	if(ACC_BudgetAllocObject.EditAccess)
		st += "<div align='center' title='ویرایش' class='edit' "+
		"onclick='ACC_BudgetAllocObject.EditBudgetAlloc();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:50%;height:16'></div>";
	if(ACC_BudgetAllocObject.RemoveAccess)
		st += "<div align='center' title='حذف' class='remove' "+
		"onclick='ACC_BudgetAllocObject.DeleteBudgetAlloc();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:50%;height:16'></div>";
		
	return st;
}

ACC_BudgetAlloc.prototype.AddBudgetAlloc = function(){

	this.formPanel.getForm().reset();
	this.formPanel.show();
}

ACC_BudgetAlloc.prototype.EditBudgetAlloc = function(){

	var record = this.grid.getSelectionModel().getLastSelected();
	this.formPanel.loadRecord(record);
	this.formPanel.show();
}

ACC_BudgetAlloc.prototype.SaveBudgetAlloc = function(){

	mask = new Ext.LoadMask(this.formPanel,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.formPanel.getForm().submit({
		url: this.address_prefix +'budget.data.php',
		method: "POST",
		params: {
			task: "SaveBudgetAlloc"
		},
		success: function(form,result){
			mask.hide();
			ACC_BudgetAllocObject.grid.getStore().load();
			ACC_BudgetAllocObject.formPanel.hide();
		},
		failure: function(form,action){
			mask.hide();
			Ext.MessageBox.alert("ERROR", action.result.data == "" ? "عملیات مورد نظر با شکست مواجه گردید" : action.result.data);
		}
	});
}

ACC_BudgetAlloc.prototype.DeleteBudgetAlloc = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ACC_BudgetAllocObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'budget.data.php',
			params:{
				task: "DeleteBudgetAlloc",
				AllocID : record.data.AllocID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);
				if(sd.success)
				{
					ACC_BudgetAllocObject.grid.getStore().load();
				}	
				else
				{
					Ext.MessageBox.alert("ERROR", sd.data == "" ? "عملیات مورد نظر با شکست مواجه گردید" : sd.data);
				}
			},
			failure: function(){}
		});
	});
}

var ACC_BudgetAllocObject = new ACC_BudgetAlloc();	

</script>