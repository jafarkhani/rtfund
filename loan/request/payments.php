<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.06
//-----------------------------

require_once '../header.inc.php';
require_once './request.class.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$RequestID = $_REQUEST["RequestID"];
if(empty($RequestID))
	die();
$editable = true;

$obj = new LON_requests($RequestID);

$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=GetPartPayments" . 
		"&RequestID=" . $RequestID, "grid_div");

$dg->addColumn("", "PayID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "DocID","", true);
$dg->addColumn("", "ComputeMode","", true);

$col = $dg->addColumn("تاریخ پرداخت مصوب", "PayDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->align = "center";

$col = $dg->addColumn("مبلغ پرداخت مصوب", "PayAmount", GridColumn::ColumnType_money);
$col->editor = ColumnEditor::CurrencyField();
$col->width = 120;
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v);}";
$col->align = "center";

$col = $dg->addColumn("تنفس صندوق", "OldFundDelayAmount", GridColumn::ColumnType_money);
$col->width = 120;
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v);}";
$col->align = "center";

$col = $dg->addColumn("تنفس سرمایه گذار", "OldAgentDelayAmount", GridColumn::ColumnType_money);
$col->width = 120;
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v);}";
$col->align = "center";

$col = $dg->addColumn("تاریخ پرداخت به مشتری", "RealPayedDate", GridColumn::ColumnType_date);
$col->width = 120;
$col->renderer = "function(v,p,r){if(r.data.DocID*1 == 0)return '';  return MiladiToShamsi(v);}";
$col->align = "center";

$col = $dg->addColumn("مبلغ پرداخت به مشتری", "PayAmount", GridColumn::ColumnType_money);
$col->width = 120;
$col->renderer = "PartPayment.RemainRender";
$col->align = "center";

if(session::IsFramework())
{
	$col = $dg->addColumn("شماره سند حسابداری", "LocalNo");
	$col->align = "center";
	$col->renderer = "function(v,p,r){return PartPayment.DocRender(v,p,r);}";
	$col->width = 120;
}
if($editable && $accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return PartPaymentObject.SavePartPayment(record);}";
	
	$dg->addButton("AddBtn", "ایجاد ردیف پرداخت", "add", "function(){PartPaymentObject.AddPay();}");
	
	if($accessObj->RemoveFlag)
	{
		$col = $dg->addColumn("حذف", "");
		$col->sortable = false;
		$col->renderer = "function(v,p,r){return PartPayment.DeleteRender(v,p,r);}";
		$col->width = 35;
	}
}

$dg->EnableSummaryRow = true;

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 270;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "PayDate";
$dg->DefaultSortDir = "ASC";

$grid = $dg->makeGrid_returnObjects();

?>
<script>
PartPayment.prototype = {
	TabID : "<?= $_REQUEST["ExtTabID"] ?>",
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	RequestID : "<?= $RequestID ?>",
	StatusID : "<?= $obj->StatusID ?>", 

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function PartPayment()
{
	this.grid = <?= $grid ?>;
	if(this.AddAccess)
		this.grid.plugins[0].on("beforeedit", function(editor,e){
			if(e.record.data.LocalNo*1 > 0)
				return false;
			return true;
		});
	this.grid.render(this.get("div_grid"));
	
}

PartPayment.RemainRender = function(v,p,r){
	
	if(r.data.DocID*1 == 0)
		return 0;
	
	amount = r.data.PayAmount*1;
	if(r.data.OldFundDelayAmount*1 > 0)
		amount -= r.data.OldFundDelayAmount*1;
	if(r.data.OldAgentDelayAmount*1 > 0)
		amount -= r.data.OldAgentDelayAmount*1;
	return Ext.util.Format.Money(amount);
}

PartPayment.DeleteRender = function(v,p,r){
	
	if(r.data.DocID*1 > 0)
		return "";
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='PartPaymentObject.DeletePayment();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16'></div>";
}

PartPayment.DocRender = function(v,p,r){
		
	if(r.data.DocID*1 == 0)
	{
		return "<div align='center' title='صدور سند' class='send' "+
		"onclick='PartPaymentObject.ExecuteEvent();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
	}
	return "<a target=_blank href=/accounting/docs/print_doc.php?DocID=" + r.data.DocID + ">"+v+"</a>";
}

PartPaymentObject = new PartPayment();
	
PartPayment.prototype.AddPay = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		PayID: null,
		RequestID : this.RequestID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}
	
PartPayment.prototype.SavePartPayment = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "SavePartPayment",
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
				PartPaymentObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

PartPayment.prototype.DeletePayment = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PartPaymentObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DeletePayment",
				PayID : record.data.PayID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					PartPaymentObject.grid.getStore().load();
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

PartPayment.prototype.BeforeRegDoc = function(mode){
	
	if(!this.BankWin)
	{
		this.BankWin = new Ext.window.Window({
			width : 300,
			height : 150,
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "combo",
				store: new Ext.data.Store({
					fields:["TafsiliID","TafsiliDesc"],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis&TafsiliType=6',
						reader: {root: 'rows',totalProperty: 'totalCount'},
						extraParams : {
							TafsiliType : 6
						}
					},
					autoLoad : true
				}),
				emptyText:'انتخاب بانک ...',
				width : 287,
				queryMode : 'local',
				valueField : "TafsiliID",
				itemId : "TafsiliID",
				displayField : "TafsiliDesc",
				listeners : { 
					change : function(){
						t1 = this.getStore().proxy.extraParams["TafsiliType"];
						combo = PartPaymentObject.BankWin.down("[itemId=TafsiliID2]");

						if(t1 == <?= TAFTYPE_BANKS ?>)
						{
							combo.setValue();
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
					fields:["TafsiliID","TafsiliDesc"],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis&TafsiliType=3',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				emptyText:'انتخاب حساب ...',
				width : 287,
				queryMode : 'local',
				valueField : "TafsiliID",
				itemId : "TafsiliID2",
				displayField : "TafsiliDesc"
			},{
				xtype : "numberfield",
				name: "ChequeNo",
				hideTrigger : true,
				fieldLabel : "شماره چک"
			}],
			buttons :[{
				text : "ذخیره",
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){
					PartPaymentObject.RegPayPartDoc();
				}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.BankWin);
	}
	
	this.BankWin.down("[itemId=TafsiliID]").setValue("<?= $_SESSION["accounting"]["DefaultBankTafsiliID"] ?>");
	this.BankWin.down("[itemId=TafsiliID2]").setValue("<?= $_SESSION["accounting"]["DefaultAccountTafsiliID"] ?>");
	
	this.BankWin.show();
	this.BankWin.down("[itemId=btn_save]").setHandler(function(){
		PartPaymentObject.RegPayPartDoc(mode == "1" ? "RegPayPartDoc" : "editPayPartDoc");
	});
}

PartPayment.prototype.RegPayPartDoc = function(task){
	
	var record = this.grid.getSelectionModel().getLastSelected();

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: task,
			PayID : record.data.PayID,
			BankTafsili : this.BankWin.down("[itemId=TafsiliID]").getValue(),
			AccountTafsili : this.BankWin.down("[itemId=TafsiliID2]").getValue(),
			ChequeNo : this.BankWin.down("[name=ChequeNo]").getValue()
		},
		success: function(response){

			result = Ext.decode(response.responseText);
			mask.hide();
			if(!result.success)
				Ext.MessageBox.alert("Error", result.data);
			
			PartPaymentObject.BankWin.hide();
			PartPaymentObject.grid.getStore().load();
		}
	});				
}

PartPayment.prototype.ReturnPayPartDoc = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به برگشت سند پرداخت می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = PartPaymentObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "RetPayPartDoc",
				PayID : record.data.PayID
			},
			success: function(response){
				
				result = Ext.decode(response.responseText);
				mask.hide();
				if(!result.success)
				{
					if(result.data == "")
						Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
					else
						Ext.MessageBox.alert("", result.data);
					return;
				}				
				PartPaymentObject.grid.getStore().load();
			}
		});
	});
}

PartPayment.prototype.ExecuteEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();

	var loanStore = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + "request.data.php?task=SelectAllRequests&RequestID=" + record.data.RequestID,
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["RequestID","ReqPersonID","PartID"]
	});
	loanStore.load({
		callback : function(){
			var eventID = "";
			ReqRecord = this.getAt(0);
			if(ReqRecord.data.ReqPersonID*1 > 0)
				eventID = "<?= EVENT_LOANPAYMENT_agentSource ?>";
			else
				eventID = "<?= EVENT_LOANPAYMENT_innerSource ?>";
			
			framework.ExecuteEvent(eventID, new Array(
				ReqRecord.data.RequestID,ReqRecord.data.PartID,record.data.PayID), "PartPaymentObject.AfterEvent");
		}
	})
}

PartPayment.prototype.AfterEvent = function(){
	PartPaymentObject.grid.getStore().load();
}

</script>
<center>
	<div id="div_grid"></div>
</center>
