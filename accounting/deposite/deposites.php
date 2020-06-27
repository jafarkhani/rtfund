<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1395.04
//-----------------------------

require_once '../../header.inc.php';
require_once inc_dataGrid;
require_once inc_dataReader;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "deposite.data.php?task=selectDeposites", "grid_div", "mainForm");

$dg->addColumn("", "CostID", "", true);

$col = $dg->addColumn('<input type=checkbox onclick=Deposite.CheckAll(this)>', "TafsiliID", "");
$col->renderer = "Deposite.SelectRender";
$col->sortable = false;
$col->width = 40;

$col = $dg->addColumn("حساب", "CostDesc");
$col->width = 200;

$col = $dg->addColumn("تفصیلی", "TafsiliDesc");

$col = $dg->addColumn("مانده حساب", "amount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("کنترل محاسبه", "");
$col->renderer = "Deposite.ReportRender";
$col->width = 40;

if($accessObj->EditFlag)
{
	$dg->addButton("", "گردش حساب سپرده", "report", "function(){DepositeObject.ComputeProfit(true);}");
	$dg->addButton("", "صدور سند سود سپرده", "process", "function(){DepositeObject.BeforeComputeProfit(false);}");
	//$dg->addObject("this.OperationObj");
}
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->width = 800;
$dg->title = "سپرده های کوتاه مدت و بلند مدت";
$dg->DefaultSortField = "amount";
$dg->DefaultSortDir = "Desc";
$dg->autoExpandColumn = "TafsiliDesc";
$grid = $dg->makeGrid_returnObjects();

?>
<script>

Deposite.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

Deposite.SelectRender = function(v,p,r){
	return "<input type=checkbox id=chk_" + r.data.CostID + "_" + v + 
			" name=chk_" + r.data.CostID + "_" + v + " >";
}

Deposite.ReportRender = function(v,p,r){
	
	return "<div align='center' title='کنترل محاسبه' class='report' "+
		"onclick='DepositeObject.BeforeComputeProfit(true);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

Deposite.CheckAll = function(checkAllElem){
	
	elems = DepositeObject.get("div_grid").getElementsByTagName("input");
	for(i=0; i<elems.length; i++)
		if(elems[i].id.indexOf("chk_") != -1)
			elems[i].checked = checkAllElem.checked;
}
	
function Deposite(){
	
	/*this.OperationObj = Ext.button.Button({
		text: 'عملیات',
		iconCls: 'setting',
		menu: {
			xtype: 'menu',
			plain: true,
			showSeparator : true,
			items: [{
				text: "صدور سند سود سپرده",
				iconCls: 'process',
				handler : function(){
					DepositeObject.BeforeComputeProfit(false);
				}
			},{
				text: "گزارش محاسبه سود",
				iconCls: 'report',
				handler : function(){
					DepositeObject.BeforeComputeProfit(true);
				}
			}]
		}
	});*/
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));
}

DepositeObject = new Deposite();

Deposite.prototype.BeforeComputeProfit = function(ReportMode){
	
	if(!this.DateWin)
	{
		this.DateWin = new Ext.window.Window({
			width : 414,
			height : 90,
			modal : true,
			bodyStyle : "background-color:white",
			items : [{
				xtype : "shdatefield",
				labelWidth : 200,
				fieldLabel : "محاسبه سود تا تاریخ",
				name : "ToDate"
			},{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					data : [
						['Daily' , "محاسبه روزانه" ],
						['Monthly' , "محاسبه ماهانه" ]
					],
					fields : ['id','value']
				}),
				displayField : "value",
				valueField : "id",
				name : "ComputeFn"
			}],
			closeAction : "hide",
			buttons : [{
				text : "محاسبه سود",
				iconCls : "send",
				itemId : "btn_compute",
				handler : function(){DepositeObject.ComputeProfit(false);}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.DateWin);
	}
	
	this.DateWin.show();
	this.DateWin.center();
	if(ReportMode)
		this.DateWin.down("[itemId=btn_compute]").setHandler(function(){
			var record = DepositeObject.grid.getSelectionModel().getLastSelected();
			if(!record)
				return;
			window.open(DepositeObject.address_prefix +  
				"report.php?CostID=" + record.data.CostID + "&TafsiliID=" + 
				record.data.TafsiliID + "&ToDate=" + 
				DepositeObject.DateWin.down("[name=ToDate]").getRawValue());
			DepositeObject.DateWin.hide();
		});
	else
		this.DateWin.down("[itemId=btn_compute]").setHandler(function(){
			DepositeObject.ComputeProfit();
		});
}

Deposite.prototype.ComputeProfit = function(flow){
	
	if(flow)
	{
		var record = DepositeObject.grid.getSelectionModel().getLastSelected();
		if(!record)
		{
			Ext.MessageBox.alert("","ردیف مورد نظر خود را انتخاب کنید");
			return;
		}
		window.open(DepositeObject.address_prefix +  
			"report.php?CostID=" + record.data.CostID + "&TafsiliID=" + 
			record.data.TafsiliID + "&IsFlow=true");
		return;
	}
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال محاسبه و صدور سند ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'deposite.data.php?task=DepositeProfit',
		params:{
			ComputeType : "DepositeProfit",
			ToDate : this.DateWin.down("[name=ToDate]").getRawValue(),
			IsFlow : flow ? "true" : "false"
		},
		form : this.get("mainForm"),
		method: 'POST',

		success: function(response){
			result = Ext.decode(response.responseText);
			DepositeObject.DateWin.hide();
			mask.hide();
			if(result.success)
				Ext.MessageBox.alert("", "سود مربوطه در سند " + result.data + " صادر گردید.");
			else
				Ext.MessageBox.alert("Error", 
					result.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : result.data);
		},
		failure: function(){}
	});
}

</script>
<center>
	<br>
	<form id='mainForm' method='POST'>    
		<div id="div_grid"></div>
	</form>
</center>