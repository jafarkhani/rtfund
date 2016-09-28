<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.12
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;
require_once inc_dataReader;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "cheques.data.php?task=selectIncomeCheques", "grid_div");

$col = $dg->addColumn("", "BackPayID", "", true);
$dg->addColumn("", "ChequeStatus", "", true);

$col = $dg->addColumn("وام گیرنده", "fullname", "");

$col = $dg->addColumn("تاریخ وام", "PartDate", GridColumn::ColumnType_date);
$col->width = 70;

//$col = $dg->addColumn("مبلغ فاز وام", "PartAmount", GridColumn::ColumnType_money);
//$col->width = 80;

$col = $dg->addColumn("شماره چک", "ChequeNo");
$col->width = 70;

$col = $dg->addColumn("تاریخ چک", "PayDate", GridColumn::ColumnType_date);
$col->width = 80;

$col = $dg->addColumn("مبلغ چک", "PayAmount", GridColumn::ColumnType_money);
$col->width = 80;

$col = $dg->addColumn("بانک", "BankDesc");
$col->width = 100;

$col = $dg->addColumn("شعبه", "ChequeBranch");
$col->width = 100;

$col = $dg->addColumn("وضعیت چک", "ChequeStatusDesc", "");
$col->width = 80;

$dg->addButton("", "تغییر وضعیت چک", "refresh", "function(){IncomeChequeObject.beforeChangeStatus();}");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->width = 800;
$dg->title = "چک های دریافتی";
$dg->DefaultSortField = "PayDate";
$dg->DefaultSortDir = "Desc";
$dg->autoExpandColumn = "fullname";
$grid = $dg->makeGrid_returnObjects();

?>
<script>

IncomeCheque.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function IncomeCheque(){
	
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
			name : "FromNo",
			hideTrigger : true,
			fieldLabel : "از شماره چک"
		},{
			xtype : "numberfield",
			name : "ToNo",
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
			xtype : "textfield",
			name : "ChequeBranch",
			fieldLabel : "شعبه"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'cheques.data.php?' +
						"task=SelectIncomeChequeStatuses",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true
			}),
			fieldLabel : "وضعیت چک",
			displayField : "InfoDesc",
			queryMode : "local",
			valueField : "InfoID",
			hiddenName :"ChequeStatus"
		}],
		buttons :[{
			text : "جستجو",
			iconCls : "search",
			handler : function(){
				if(!IncomeChequeObject.grid.rendered)
					IncomeChequeObject.grid.render(IncomeChequeObject.get("div_grid"));
				else
					IncomeChequeObject.grid.getStore().loadPage(1);
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
		if(!IncomeChequeObject.grid.rendered)
			IncomeChequeObject.grid.render(IncomeChequeObject.get("div_grid"));
		else
			IncomeChequeObject.grid.getStore().loadPage(1);
		e.preventDefault();
		e.stopEvent();
		return false;
	});
		
	this.grid = <?= $grid ?>;
	this.grid.getStore().proxy.form = this.get("MainForm");
	//this.grid.render(this.get("div_grid"));
}

IncomeChequeObject = new IncomeCheque();

IncomeCheque.prototype.beforeChangeStatus = function(StatusID){
	
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 414,
			height : 85,
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
	this.commentWin.down("[name=DstID]").getStore().proxy.extraParams.SrcID = record.data.ChequeStatus;
	this.commentWin.down("[name=DstID]").getStore().load();
	
	this.commentWin.down("[itemId=btn_save]").setHandler(function(){
		IncomeChequeObject.ChangeStatus(record.data.BackPayID, 
			this.up('window').down("[name=DstID]").getValue());});
		
	this.commentWin.show();
	this.commentWin.center();
}

IncomeCheque.prototype.ChangeStatus = function(BackPayID, StatusID){
	
	if(StatusID == null || StatusID == "")
		return;
	
	mask = new Ext.LoadMask(this.grid, {msg:'در حال تغییر وضعیت ...'});
	mask.show();
	
	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "cheques.data.php",
		params : {
			task : "ChangeChequeStatus",
			BackPayID : BackPayID,
			StatusID : StatusID
		},
		
		success : function(response){
			mask.hide();
			IncomeChequeObject.commentWin.hide();
			
			result = Ext.decode(response.responseText);
			if(result.success)
				IncomeChequeObject.grid.getStore().load();
			else
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد;")
			
			
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
	<div id="div_grid"></div>
</center>