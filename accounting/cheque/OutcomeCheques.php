<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.12
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;
require_once inc_dataReader;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "cheques.data.php?task=selectOutcomeCheques", "grid_div");

$col = $dg->addColumn("", "DocChequeID", "", true);
$col = $dg->addColumn("", "CheckStatus", "", true);

$col = $dg->addColumn("شماره سند", "LocalNo");
$col->width = 80;

$col = $dg->addColumn("حساب", "AccountDesc");
$col->width = 150;

$col = $dg->addColumn("شماره چک", "CheckNo");
$col->width = 70;

$col = $dg->addColumn("تاریخ چک", "CheckDate", GridColumn::ColumnType_date);
$col->width = 80;

$col = $dg->addColumn("مبلغ چک", "amount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("گیرنده", "TafsiliDesc", "");
$col->width = 120;

$col = $dg->addColumn("وضعیت چک", "StatusDesc", "");
$col->width = 90;

$col = $dg->addColumn("توضیحات", "description", "");

$dg->addButton("", "تغییر وضعیت", "refresh", "function(){OutcomeChequeObject.beforeChangeStatus();}");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->title = "چک های پرداختی";
$dg->DefaultSortField = "CheckDate";
$dg->DefaultSortDir = "Desc";
$dg->autoExpandColumn = "description";
$grid = $dg->makeGrid_returnObjects();

?>
<script>

OutcomeCheque.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function OutcomeCheque(){
	
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("div_form"),
		width : 600,
		frame : true,
		title : "فیلتر لیست",
		layout : {
			type : "table",
			columns : 2			
		},
		
		items : [{
			xtype : "numberfield",
			name : "FromDocNo",
			hideTrigger : true,
			fieldLabel : "از شماره سند"
		},{
			xtype : "numberfield",
			name : "ToDocNo",
			hideTrigger : true,
			fieldLabel : "تا شماره سند"
		},{
			xtype : "shdatefield",
			name : "DFromDate",
			fieldLabel : "از تاریخ سند"
		},{
			xtype : "shdatefield",
			name : "DToDate",
			fieldLabel : "تا تاریخ سند"
		},{
			xtype : "numberfield",
			name : "FromCheckNo",
			hideTrigger : true,
			fieldLabel : "از شماره چک",
			listeners : {
				blur : function(){
					OutcomeChequeObject.formPanel.down("[name=ToCheckNo]").setValue(this.getValue())
				}
			}
		},{
			xtype : "numberfield",
			name : "ToCheckNo",
			hideTrigger : true,
			fieldLabel : "تا شماره چک"
		},{
			xtype : "shdatefield",
			name : "FromDate",
			fieldLabel : "از تاریخ چک"
		},{
			xtype : "shdatefield",
			name : "ToDate",
			fieldLabel : "تا تاریخ چک"
		},{
			xtype : "currencyfield",
			name : "FromAmount",
			hideTrigger : true,
			fieldLabel : "از مبلغ"
		},{
			xtype : "currencyfield",
			name : "ToAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?' +
						"task=GetBankData",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BankID','BankDesc'],
				autoLoad : true
			}),
			fieldLabel : "بانک",
			displayField : "BankDesc",
			queryMode : "local",
			valueField : "BankID",
			hiddenName :"ChequeBank"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?' +
						"task=SelectChequeStatuses",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true
			}),
			fieldLabel : "وضعیت چک",
			displayField : "InfoDesc",
			queryMode : "local",
			valueField : "InfoID",
			hiddenName :"CheckStatus"
		}],
		buttons :[{
			text : "جستجو",
			iconCls : "search",
			handler : function(){
				if(!OutcomeChequeObject.grid.rendered)
					OutcomeChequeObject.grid.render(OutcomeChequeObject.get("div_grid"));
				else
					OutcomeChequeObject.grid.getStore().loadPage(1);
			}
		},{
			text : "پاک کردن فرم",
			iconCls : "clear",
			handler : function(){
				this.up('form').getForm().reset();
			}
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		if(!OutcomeChequeObject.grid.rendered)
			OutcomeChequeObject.grid.render(OutcomeChequeObject.get("div_grid"));
		else
			OutcomeChequeObject.grid.getStore().loadPage(1);
		e.preventDefault();
		e.stopEvent();
		return false;
	});
		
	this.grid = <?= $grid ?>;
	this.grid.getStore().proxy.form = this.get("MainForm");
	this.grid.render(this.get("div_grid"));
}

OutcomeChequeObject = new OutcomeCheque();

OutcomeCheque.prototype.beforeChangeStatus = function(){
	
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 414,
			height : 150,
			modal : true,
			bodyStyle : "background-color:white",
			items : [{
				xtype : "combo",
				store: new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: this.address_prefix + 'cheques.data.php?task=selectValidChequeStatuses',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ['InfoID',"InfoDesc"]
				}),
				queryMode : "local",
				displayField: 'InfoDesc',
				valueField : "InfoID",
				width : 400,
				name : "DstID"
			}],
			closeAction : "hide",
			buttons : [{
				text : "تغییر وضعیت",				
				iconCls : "refresh",
				itemId : "btn_save"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	var record = this.grid.getSelectionModel().getLastSelected();
	this.commentWin.down("[name=DstID]").setValue();
	this.commentWin.down("[name=DstID]").getStore().proxy.extraParams.SrcID = record.data.CheckStatus;
	this.commentWin.down("[name=DstID]").getStore().load();
	
	this.commentWin.down("[itemId=btn_save]").setHandler(function(){
		status = this.up('window').down("[name=DstID]").getValue();
		OutcomeChequeObject.ChangeStatus();
	});
		
	this.commentWin.show();
	this.commentWin.center();
}

OutcomeCheque.prototype.ChangeStatus = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	StatusID = this.commentWin.down("[name=DstID]").getValue();
	
	if(StatusID == null || StatusID == "")
		return;
	
	this.commentWin.hide();		
	mask = new Ext.LoadMask(this.grid, {msg:'در حال تغییر وضعیت ...'});
	mask.show();

	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "cheques.data.php",
		params : {
			task : "ChangeOutcomeChequeStatus",
			DocChequeID : record.data.DocChequeID,
			StatusID : StatusID
		},

		success : function(response){
			mask.hide();

			result = Ext.decode(response.responseText);
			if(result.success)
			{
				OutcomeChequeObject.grid.getStore().load();
				if(result.data != "")
					Ext.MessageBox.alert("",result.data);
			}
			else if(result.data != "")
				Ext.MessageBox.alert("",result.data);
			else
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");


		}
	});
}

</script>
<center>
	<br>
	<form id="MainForm">
		<div id="div_form"></div>
	</form>
	<br>
	<div style="width:98%" id="div_grid"></div>
</center>