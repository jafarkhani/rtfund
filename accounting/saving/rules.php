<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1395.03
//-----------------------------

require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "saving.data.php?task=selectRules", "grid_div");

$dg->addColumn("", "RuleID","", true);

$col = $dg->addColumn("عنوان", "RuleDesc");
$col->editor = ColumnEditor::TextField();
$col->width = 120;

$col = $dg->addColumn("کارمزد", "WagePercent");
$col->editor = ColumnEditor::NumberField();
$col->width = 40;
$col->align = "center";

$col = $dg->addColumn("تاریخ شروع", "FromDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->align = "center";
$col->width = 80;

$col = $dg->addColumn("تاریخ پایان", "ToDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField(true);
$col->align = "center";
$col->width = 80;

$col = $dg->addColumn("حداقل مبلغ", "MinAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 110;
$col->align = "center";

$col = $dg->addColumn("حداکثر مبلغ", "MaxAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 110;
$col->align = "center";

$col = $dg->addColumn("توضیحات", "details");
$col->editor = ColumnEditor::TextField(true);
$col->ellipsis = 40;
$col->align = "center";

if($accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return SavingRuleObject.SaveRule(record);}";

	$dg->addButton("AddBtn", "ایجاد", "add", "function(){SavingRuleObject.AddRule();}");
}
$col = $dg->addColumn("", "");
$col->sortable = false;
$col->renderer = "SavingRule.PeriodsRender";
$col->width = 35;

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "SavingRule.DeleteRender";
	$col->width = 35;
}
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->width = 780;
$dg->title = "قوانین وام از محل پس انداز";
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->autoExpandColumn = "details";
$dg->DefaultSortField = "PayDate";
$dg->DefaultSortDir = "ASC";

$grid = $dg->makeGrid_returnObjects();

//..............................................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "saving.data.php?task=GetRulePeriods", "grid_div");

$dg->addColumn("", "RuleID","", true);
$dg->addColumn("", "RowID","", true);

$col = $dg->addColumn("تعداد ماه دوره", "months");
$col->editor = ColumnEditor::NumberField();
$col->align = "center";

$col = $dg->addColumn("تعداد اقساط", "InstallmentCount");
$col->editor = ColumnEditor::NumberField();
$col->align = "center";

if($accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return SavingRuleObject.SavePeriod(record);}";

	$dg->addButton("AddBtn", "ایجاد", "add", "function(){SavingRuleObject.AddPeriod();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "SavingRule.PeriodDeleteRender";
	$col->width = 35;
}
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 300;
$dg->width = 300;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "months";
$dg->DefaultSortDir = "ASC";

$grid2 = $dg->makeGrid_returnObjects();
?>
<script>
SavingRule.prototype = {
	TabID : "<?= $_REQUEST["ExtTabID"] ?>",
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function SavingRule()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));
	
	this.grid2 = <?= $grid2 ?>;
}

SavingRule.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='SavingRuleObject.DeleteRule();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

SavingRule.PeriodsRender = function(v,p,r){
	
	return "<div align='center' title='دوره ها' class='list' "+
		"onclick='SavingRuleObject.Periods();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

SavingRule.PeriodDeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='SavingRuleObject.DeletePeriod();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

SavingRuleObject = new SavingRule();
	
SavingRule.prototype.AddRule = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RuleID: null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}
	
SavingRule.prototype.SaveRule = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'saving.data.php',
		method: "POST",
		params: {
			task: "SaveRule",
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
				SavingRuleObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

SavingRule.prototype.DeleteRule = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = SavingRuleObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'saving.data.php',
			params:{
				task: "DeleteRule",
				RuleID : record.data.RuleID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					SavingRuleObject.grid.getStore().load();
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

//....................................................

SavingRule.prototype.Periods = function(){

	record = this.grid.getSelectionModel().getLastSelected();
	this.grid2.getStore().proxy.extraParams.RuleID = record.data.RuleID;
	
	if(!this.itemWin)
	{
		this.itemWin = new Ext.window.Window({
			width : 315,
			title : "دوره های سپرده گذاری",
			height : 365,
			modal : true,
			closeAction : "hide",
			items : [this.grid2],
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.itemWin);
	}
	else
		this.grid2.getStore().load();

	this.itemWin.show();
	this.itemWin.center();
}

SavingRule.prototype.AddPeriod = function(){

	var modelClass = this.grid2.getStore().model;
	var record = new modelClass({
		RuleID: this.grid2.getStore().proxy.extraParams.RuleID,
		RowID : null
	});

	this.grid2.plugins[0].cancelEdit();
	this.grid2.getStore().insert(0, record);
	this.grid2.plugins[0].startEdit(0, 0);
}
	
SavingRule.prototype.SavePeriod = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'saving.data.php',
		method: "POST",
		params: {
			task: "SavePeriod",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(!st.success)
			{
				if(st.data == "")
					Ext.MessageBox.alert("Error","این دوره قبلا تعریف شده است");
				else
					Ext.MessageBox.alert("Error",st.data);
			}
			else
				SavingRuleObject.grid2.getStore().load();
		},
		failure: function(){}
	});
}

SavingRule.prototype.DeletePeriod = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = SavingRuleObject;
		var record = me.grid2.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid2, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'saving.data.php',
			params:{
				task: "DeletePeriod",
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					SavingRuleObject.grid2.getStore().load();
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