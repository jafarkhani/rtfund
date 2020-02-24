<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.07
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;
require_once './request.class.php';
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$RequestID = $_REQUEST["RequestID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetCosts&RequestID=" .$RequestID,"grid_div");

$dg->addColumn("", "CostID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "DocID","", true);
$dg->addColumn("", "IsPartDiff","", true);

$col = $dg->addColumn("شرح هزینه", "CostDesc");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("تاریخ", "CostDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 80;

$col = $dg->addColumn("مبلغ", "CostAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 100;

$col = $dg->addColumn("سند حسابداری", "LocalNo");
$col->align = "center";
$col->renderer = "function(v,p,r){return LoanCost.DocRender(v,p,r);}";
$col->width = 100;

if($accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return LoanCostObject.SaveCost(record);}";
	$dg->addButton("AddBtn", "ایجاد ردیف هزینه", "add", "function(){LoanCostObject.AddCost();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LoanCost.DeleteRender(v,p,r);}";
	$col->width = 35;
}
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

LoanCost.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	RequestID : <?= $RequestID ?>,
	PartID : <?= LON_ReqParts::GetValidPartObj($RequestID)->PartID ?>,
	EventID : <?= LON_requests::GetEventID($RequestID, EVENTTYPE_LoanCost) ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanCost()
{
	this.grid = <?= $grid ?>;
	if(this.AddAccess)
		this.grid.plugins[0].on("beforeedit", function(editor,e){

			if(e.record.data.CostID != null)
				return false;
		});
	
	this.grid.render(this.get("div_grid"));	
}

LoanCost.DeleteRender = function(v,p,r){
	
	if(r.data.DocID != null &&  r.data.DocID != "")
		return "";
	
	if(r.data.IsPartDiff == "YES")
		return "";

	return "<div align='center' title='حذف' class='remove' "+
		"onclick='LoanCostObject.DeleteCost();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

LoanCost.DocRender = function(v,p,r){
		
	if(r.data.DocID*1 == 0)
	{
		return "<div align='center' title='صدور سند' class='send' "+
		"onclick='LoanCostObject.ExecuteEvent();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
	}
	return "<a target=_blank href=/accounting/docs/print_doc.php?DocID=" + r.data.DocID + ">"+v+"</a>";
}

LoanCost.prototype.BeforeSaveCost = function(record){
	
	if(!this.BankWin)
	{
		this.BankWin = new Ext.window.Window({
			width : 400,
			height : 350,
			bodyStyle : "background-color:white",
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "form",
				border : false,
				items :[{
					xtype : "combo",
					width : 385,
					fieldLabel : "حساب مربوطه",
					colspan : 2,
					store: new Ext.data.Store({
						fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
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
					name : "CostID",
					valueField : "CostID",
					displayField : "fullDesc",
					listeners : {
						select : function(combo,records){
							
							me = LoanCostObject;
							me.BankWin.down("[itemId=TafsiliID]").setValue();
							me.BankWin.down("[itemId=TafsiliID]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType1;
							me.BankWin.down("[itemId=TafsiliID]").getStore().load();

							me.BankWin.down("[itemId=TafsiliID2]").setValue();
							me.BankWin.down("[itemId=TafsiliID2]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType2;
							me.BankWin.down("[itemId=TafsiliID2]").getStore().load();
							
							if(this.getValue() == "<?= COSTID_Bank ?>")
							{
								me.BankWin.down("[itemId=TafsiliID]").setValue(
									"<?= $_SESSION["accounting"]["DefaultBankTafsiliID"] ?>");
								me.BankWin.down("[itemId=TafsiliID2]").setValue(
									"<?= $_SESSION["accounting"]["DefaultAccountTafsiliID"] ?>");
							}
						}
					}
				},{
					xtype : "combo",
					store: new Ext.data.Store({
						fields:["TafsiliID","TafsiliCode","TafsiliDesc",{
							name : "title",
							convert : function(v,r){ return "[ " + r.data.TafsiliCode + " ] " + r.data.TafsiliDesc;}
						}],
						proxy: {
							type: 'jsonp',
							url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					emptyText:'انتخاب تفصیلی1 ...',
					typeAhead: false,
					pageSize : 10,
					width : 385,
					valueField : "TafsiliID",
					itemId : "TafsiliID",
					name : "TafsiliID",
					displayField : "title",
					listeners : { 
						change : function(){
							t1 = this.getStore().proxy.extraParams["TafsiliType"];
							combo = LoanCostObject.BankWin.down("[itemId=TafsiliID2]");

							if(t1 == <?= TAFTYPE_BANKS ?>)
							{
								combo.getStore().proxy.extraParams["ParentTafsili"] = this.getValue();
								combo.getStore().load();
							}			
							else
								combo.getStore().proxy.extraParams["ParentTafsili"] = "";
						}
					}
				},{
					xtype : "combo",
					store: new Ext.data.Store({
						fields:["TafsiliID","TafsiliCode","TafsiliDesc",{
							name : "title",
							convert : function(v,r){ return "[ " + r.data.TafsiliCode + " ] " + r.data.TafsiliDesc;}
						}],
						proxy: {
							type: 'jsonp',
							url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					emptyText:'انتخاب تفصیلی2 ...',
					typeAhead: false,
					pageSize : 10,
					width : 385,
					valueField : "TafsiliID",
					itemId : "TafsiliID2",
					name : "TafsiliID2",
					displayField : "title"
				}]
			}],
			buttons :[{
				text : "ذخیره",
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){ LoanCostObject.SaveCost();}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){this.up('window').hide(); LoanPayObject.grid.getStore().load();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.BankWin);
	}
	
	this.BankWin.show();
	this.BankWin.down("[itemId=btn_save]").setHandler(function(){ 
		LoanCostObject.SaveCost(record); 
	});
}

LoanCost.prototype.SaveCost = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params : {
			task: "SaveCosts",
			record: Ext.encode(record.data)
		},
		
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				LoanCostObject.grid.getStore().load();
			}
			else
			{
				if(st.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",st.data);
			}
		},
		failure: function(){}
	});
}

LoanCost.prototype.ExecuteEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	framework.ExecuteEvent(this.EventID, new Array(
				this.RequestID,this.PartID,record.data.CostID));
}

LoanCost.prototype.AddCost = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		CostID: null,
		RequestID : this.RequestID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

LoanCost.prototype.DeleteCost = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LoanCostObject;
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
					LoanCostObject.grid.getStore().load();
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

var LoanCostObject = new LoanCost();

</script>
<center>
	<div id="div_grid"></div>
</center>