<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.07
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

$RequestID = $_REQUEST["RequestID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetCosts&RequestID=" .$RequestID,"grid_div");

$dg->addColumn("", "CostID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "DocID","", true);
$dg->addColumn("", "CostCode","", true);
$dg->addColumn("", "CostCodeDesc","", true);

$col = $dg->addColumn("شرح هزینه", "CostDesc");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("کد حساب", "CostCodeID");
$col->renderer = "function(v,p,r){ return '[ ' + r.data.CostCode + ' ] ' + r.data.CostCodeDesc;}";
$col->editor = "this.CostCodeCombo";
$col->width = 150;

$col = $dg->addColumn("مبلغ", "CostAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 100;

$col = $dg->addColumn("ماهیت", "CostType");
$col->editor = ColumnEditor::ComboBox(array(
	array("id"=>'DEBTOR',"title"=>'بدهکار'),
	array("id"=>"CREDITOR",'title'=>"بستانکار")), 
		"id", "title");
$col->width = 100;


$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(store,record){return WarrentyCostObject.SaveCost(record);}";

$dg->addButton("AddBtn", "ایجاد ردیف هزینه", "add", "function(){WarrentyCostObject.AddCost();}");

$col = $dg->addColumn("حذف", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return WarrentyCost.DeleteRender(v,p,r);}";
$col->width = 35;

$dg->height = 336;
$dg->width = 585;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "CostID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "CostDesc";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

WarrentyCost.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	RequestID : <?= $RequestID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function WarrentyCost()
{
	this.CostCodeCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			fields:["CostID","CostCode","CostDesc",{
				name : "fullDesc",
				convert : function(value,record){
					return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
				}				
			}],
			proxy: {
				type: 'jsonp',
				url: '/accounting/baseinfo/baseinfo.data.php?task=SelectCostCode',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			}
		}),
		typeAhead: false,
		allowBlank : false,
		valueField : "CostID",
		displayField : "fullDesc"
	})
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
}

WarrentyCost.DeleteRender = function(v,p,r){
	
	if(r.data.DocID != null &&  r.data.DocID != "")
		return "";

	return "<div align='center' title='حذف' class='remove' "+
		"onclick='WarrentyCostObject.DeleteCost();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}
	
WarrentyCost.prototype.SaveCost = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "SaveCosts",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				WarrentyCostObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

WarrentyCost.prototype.AddCost = function(){


	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		CostID: null,
		RequestID : this.RequestID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

WarrentyCost.prototype.DeleteCost = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = WarrentyCostObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DeleteCosts",
				CostID : record.data.CostID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					WarrentyCostObject.grid.getStore().load();
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

var WarrentyCostObject = new WarrentyCost();

</script>
<center>
	<div id="div_grid"></div>
</center>