<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.03
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$BudgetID = $_REQUEST["BudgetID"];

$dg = new sadaf_datagrid("dg", $js_prefix_address . "budget.data.php?task=GetBudgetCostCodes"
		. "&BudgetID=" . $BudgetID, "grid_div");

$dg->addColumn("", "RowID", "", true);
$dg->addColumn("", "BudgetID", "", true);
$dg->addColumn("", "CostDesc", "", true);

$col = $dg->addColumn("کد حساب", "CostCode", "");
$col->width = 150;

$col = $dg->addColumn("عنوان حساب", "CostID", "");
$col->renderer="function(v,p,r){return r.data.CostDesc;}";
$col->editor = "this.CostCombo";

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){ACC_BudgetCostCodeObject.AddCostCode();}";
	
	$dg->enableRowEdit = true ;
	$dg->rowEditOkHandler = "function(v,p,r){ return ACC_BudgetCostCodeObject.Save(v,p,r);}";
}

$dg->height = 500;
$dg->width = 750;
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->autoExpandColumn = "CostID";
$dg->DefaultSortField = "CostCode";
$dg->emptyTextOfHiddenColumns = true;

$col = $dg->addColumn("حذف", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return ACC_BudgetCostCode.DeleteRender(v,p,r);}";
$col->width = 50;

$grid = $dg->makeGrid_returnObjects();

?>
<center>
        <div id="grid_div"></div>
</center>
<script>
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------

ACC_BudgetCostCode.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	BudgetID : "<?= $BudgetID ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function ACC_BudgetCostCode(){
	
	this.CostCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc", 
				"TafsiliType1","TafsiliType2","TafsiliType3",{
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
		typeAhead: false,
		valueField : "CostID",
		displayField : "fullDesc"
	});
	
	this.grid = <?= $grid ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		if(e.record.data.RowID*1 > 0)
			return false;
	});
	this.grid.render(this.get("grid_div"));
}

ACC_BudgetCostCode.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
	"onclick='ACC_BudgetCostCodeObject.DeleteCostCode();' " +
	"style='background-repeat:no-repeat;background-position:center;" +
	"cursor:pointer;width:100%;height:16'></div>";
}

ACC_BudgetCostCode.prototype.Save = function(store,record,op){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();    
	Ext.Ajax.request({
		url: this.address_prefix + 'budget.data.php?task=SaveBudgetCostCode',
		params:{
			record : Ext.encode(record.data)
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			ACC_BudgetCostCodeObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

ACC_BudgetCostCode.prototype.DeleteCostCode = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ACC_BudgetCostCodeObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'budget.data.php',
			params:{
				task: "RemoveBudgetCostCode",
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				ACC_BudgetCostCodeObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

ACC_BudgetCostCode.prototype.AddCostCode = function(){
	
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		BudgetID : this.BudgetID,
		CostID:null		

	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

var ACC_BudgetCostCodeObject = new ACC_BudgetCostCode();	

</script>