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

$dg = new sadaf_datagrid("dg", $js_prefix_address . "doc.data.php?task=GetAccountSummary", "grid_div");

$dg->addColumn("", "TafsiliID","", true);

$col = $dg->addColumn("تفصیلی", "TafsiliDesc","");

/*$col = $dg->addColumn("شعبه", "BranchName","");
$col->width = 150;*/

$col = $dg->addColumn("حساب مشتری", "pasandaz",  GridColumn::ColumnType_money);
$col->renderer = "InOutAccount.AccountDetailRender";
$col->width = 110;

/*$col = $dg->addColumn("کوتاه مدت", "kootah",  GridColumn::ColumnType_money);
$col->renderer = "InOutAccount.AccountDetailRender";
$col->width = 110;

$col = $dg->addColumn("بلند مدت", "boland",  GridColumn::ColumnType_money);
$col->renderer = "InOutAccount.AccountDetailRender";
$col->width = 110;

$col = $dg->addColumn("جاری", "jari",  GridColumn::ColumnType_money);
$col->renderer = "InOutAccount.AccountDetailRender";
$col->width = 110;*/

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 220;
$dg->pageSize = 5;
$dg->width = 780;
$dg->title = "خلاصه حساب های ذینفعان";
$dg->DefaultSortField = "TafsiliDesc";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "TafsiliDesc";
$Hgrid = $dg->makeGrid_returnObjects();

//..............................................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "doc.data.php?task=GetAccountFlow", "grid_div");

$col = $dg->addColumn("شماره سند", "LocalNo","");
$col->width = 60;

$dg->addColumn("", "DocID","", true);
$dg->addColumn("", "ItemID","", true);
$dg->addColumn("", "TafsiliID","", true);
$dg->addColumn("", "CostID","", true);
$dg->addColumn("", "StatusID","", true);
$dg->addColumn("شرح سند", "description","");

$col = $dg->addColumn("شرح ردیف", "details","");
$col->width = 200;

$col = $dg->addColumn("تاریخ سند", "DocDate", GridColumn::ColumnType_date);

$col = $dg->addColumn("برداشت", "DebtorAmount", GridColumn::ColumnType_money);
$col->width = 110;
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v);}";
$col->align = "center";

$col = $dg->addColumn("واریز", "CreditorAmount", GridColumn::ColumnType_money);
$col->width = 110;
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v);}";
$col->align = "center";

if($accessObj->AddFlag)
{
	$dg->addButton("", "واریز وجه", "arrow_down", "function(){InOutAccountObject.BeforeOperation(1);}");
	$dg->addButton("", "برداشت وجه", "arrow_up", "function(){InOutAccountObject.BeforeOperation(-1);}");
}
$dg->EnableSummaryRow = true;

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 280;
$dg->width = 780;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "DocDate";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "description";
$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

InOutAccount.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

InOutAccount.AccountDetailRender = function(v,p,r,rowIndex, colIndex,store){
	
	return "<a href='javascript:void(1)' onclick='InOutAccountObject.LoadAccountDetail(" +colIndex+ ")'>" 
		+ Ext.util.Format.Money(v) + "</a>";
}

function InOutAccount()
{
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.StatusID == "<?= ACC_STEPID_CONFIRM ?>")
			return "yellowRow";
		if(record.data.StatusID == "<?= ACC_STEPID_RAW ?>")
			return "";	
		return "greenRow";
	}	
	this.grid.addDocked({
		xtype : "toolbar",
		dock:'bottom', 
		layout : "vbox",
		items : ['->',{
			xtype : "displayfield",
			itemId : "remaindar",
			fieldCls : "blueText",
			fieldLabel : "مانده حساب : "
		},{
			xtype : "displayfield",
			itemId : "bllocked",
			fieldCls : "blueText",
			fieldLabel : "مبلغ بلوکه شده : "
		},{
			xtype : "displayfield",
			itemId : "freeAmount",
			fieldCls : "blueText",
			fieldLabel : "مبلغ قابل برداشت :"
		}]
	});
	this.grid.getStore().on("load", function(){
		summaryObject = InOutAccountObject.grid.features.findObject("ftype", "summary");
		DebtorCol = InOutAccountObject.grid.columns.findObject("dataIndex", "DebtorAmount").id;
		CreditorCol = InOutAccountObject.grid.columns.findObject("dataIndex", "CreditorAmount").id;
		
		remaindar = summaryObject.summaryData[CreditorCol]*1 - summaryObject.summaryData[DebtorCol]*1;
		InOutAccountObject.grid.down("[itemId=remaindar]").setValue( Ext.util.Format.Money(remaindar) );
		
		var r = this.getProxy().getReader().jsonData;
		BlockedAmount = r.message;
		if(BlockedAmount == null)
			BlockedAmount = 0;
		InOutAccountObject.grid.down("[itemId=bllocked]").setValue( Ext.util.Format.Money(BlockedAmount) );
		free = remaindar - BlockedAmount*1;
		InOutAccountObject.grid.down("[itemId=freeAmount]").setValue( Ext.util.Format.Money(free) );
	});
	
	this.Hgrid = <?= $Hgrid ?>;
	this.Hgrid.render(this.get("div_hgrid"));
}

var InOutAccountObject = new InOutAccount();

InOutAccount.prototype.LoadAccountDetail = function(col){
	
	var record = this.Hgrid.getSelectionModel().getLastSelected();
	CostID = "";
	switch(col)
	{
		case 2 : CostID = <?= COSTID_saving ?>; break;
		case 3 : CostID = <?= COSTID_ShortDeposite ?>; break;
		case 4 : CostID = <?= COSTID_LongDeposite ?>; break;
		case 5 : CostID = <?= COSTID_current ?>; break;
	}

	this.grid.getStore().proxy.extraParams.BaseCostID = CostID;
	this.grid.getStore().proxy.extraParams.TafsiliID = record.data.TafsiliID;
	if(this.grid.rendered)
		this.grid.getStore().load();
	else
		this.grid.render(this.get("div_grid"));			
	
	
}

InOutAccount.prototype.BeforeOperation = function(mode){
	
	if(!this.mainWin)
	{
		this.mainWin = new Ext.window.Window({
			width : 400,
			height : 250,
			bodyStyle : "background-color:white",
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "combo",
				width : 380,
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
				valueField : "CostID",
				fieldLabel : "کد حساب",
				name : "CostID",
				displayField : "fullDesc",
				listeners : {
					select : function(combo,records){
						me = InOutAccountObject;
						if(records[0].data.TafsiliType1 != null)
						{
							me.mainWin.down("[name=TafsiliID]").setValue();
							me.mainWin.down("[name=TafsiliID]").getStore().proxy.extraParams.TafsiliType = 
								records[0].data.TafsiliType1;
							me.mainWin.down("[name=TafsiliID]").getStore().load();
						}
						if(records[0].data.TafsiliType2 != null)
						{
							me.mainWin.down("[name=TafsiliID2]").setValue();
							me.mainWin.down("[name=TafsiliID2]").getStore().proxy.extraParams.TafsiliType = 
								records[0].data.TafsiliType2;
							me.mainWin.down("[name=TafsiliID2]").getStore().load();
						}
						if(this.getValue() == "<?= COSTID_Bank ?>")
						{
							me.mainWin.down("[name=TafsiliID]").setValue(
								"<?= $_SESSION["accounting"]["DefaultBankTafsiliID"] ?>");
							me.mainWin.down("[name=TafsiliID2]").setValue(
								"<?= $_SESSION["accounting"]["DefaultAccountTafsiliID"] ?>");
						}
					}
				}
			},{
				xtype : "combo",
				store: new Ext.data.Store({
					fields:["TafsiliID","TafsiliCode","TafsiliDesc"],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				typeAhead: false,
				width : 380,
				fieldLabel : "تفصیلی",
				valueField : "TafsiliID",
				name : "TafsiliID",
				displayField : "TafsiliDesc",
				listeners : { 
					change : function(){
						t1 = this.getStore().proxy.extraParams["TafsiliType"];
						combo = InOutAccountObject.mainWin.down("[name=TafsiliID2]");

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
					fields:["TafsiliID","TafsiliCode","TafsiliDesc"],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				typeAhead: false,
				width : 380,
				fieldLabel : "تفصیلی 2",
				valueField : "TafsiliID",
				name : "TafsiliID2",
				displayField : "TafsiliDesc"
			},{
				xtype : "currencyfield",
				fieldLabel : "مبلغ",
				name : "amount",
				width : 380,
				hideTrigger : true				
			},{
				xtype : "textarea",
				name : "description",
				width : 380,
				rows : 2,
				fieldLabel : "توضیحات"
			}],
			buttons :[{
				text : "ذخیره",
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){ InOutAccountObject.SaveOperation(); }
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.mainWin);
	}
	this.mode = mode;
	
	this.mainWin.down("[name=CostID]").setValue();
	this.mainWin.down("[name=amount]").setValue();
	
	this.mainWin.show();	
	this.mainWin.center();
}
	
InOutAccount.prototype.SaveOperation = function(){

	if(this.mainWin.down("[name=amount]").getValue() == null)
	{
		Ext.MeesageBox.alert("Error","مبلغ را وارد کنید");
		return;
	}
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'doc.data.php',
		method: "POST",
		params: {
			task: "RegisterInOutDoc",
			mode : this.mode,
			BaseCostID : this.grid.getStore().proxy.extraParams.BaseCostID,
			BaseTafsiliID : this.grid.getStore().proxy.extraParams.TafsiliID,
			CostID : this.mainWin.down("[name=CostID]").getValue(),
			TafsiliID: this.mainWin.down("[name=TafsiliID]").getValue(),
			TafsiliID2: this.mainWin.down("[name=TafsiliID2]").getValue(),
			description : this.mainWin.down("[name=description]").getValue(),
			amount: this.mainWin.down("[name=amount]").getValue()
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				InOutAccountObject.grid.getStore().load();
				InOutAccountObject.mainWin.hide();
				Ext.MessageBox.alert("","سند حسابداری مربوطه صادر گردید");
			}
			else
			{
				if(st.data == "")
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("Error",st.data);
			}
		},
		failure: function(){}
	});
}

</script>
<center>
	<br>
	<div id="div_hgrid"></div>
	<br>
	<div id="div_grid"></div>
</center>