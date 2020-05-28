<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.08
//-------------------------
require_once('../../header.inc.php');
require_once 'request.class.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$RequestID = 0;
$editable = false;
$DefrayMode = false;
$TotalRemain = 0;
if(session::IsFramework()) 
{
	if(empty($_POST["RequestID"]))
		die();
	
	$RequestID = $_POST["RequestID"];
	$ReqObj = new LON_requests($RequestID);
	
	if($ReqObj->IsEnded == "NO")
		$editable = true;
}	
else
{
	if(!empty($_POST["RequestID"]))
		$RequestID = $_POST["RequestID"];
	$DefrayMode = isset($_POST["DefrayMode"]) ? true : false;
	if($DefrayMode)
	{
		$TotalRemain = LON_Computes::GetTotalRemainAmount($RequestID);
	}
}

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetInstallments","grid_div");

$dg->addColumn("", "InstallmentID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "BankDesc", "", true);
$dg->addColumn("", "ChequeBranch", "", true);
$dg->addColumn("", "history", "", true);
$dg->addColumn("", "ComputeType", "", true);

$col = $dg->addColumn("سررسید", "InstallmentDate", GridColumn::ColumnType_date);
//$col->editor = ColumnEditor::SHDateField();
$col->width = 80;

$col = $dg->addColumn("مبلغ قسط", "InstallmentAmount", GridColumn::ColumnType_money);
//$col->editor = ColumnEditor::CurrencyField();
$col->summaryType = GridColumn::SummeryType_sum;
$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v );}";

if(session::IsFramework())
{
	$col = $dg->addColumn("کارمزد", "wage", GridColumn::ColumnType_money);
	$col->width = 80;
	$col->summaryType = GridColumn::SummeryType_sum;
	$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v );}";
	
	$col = $dg->addColumn("اصل", "", GridColumn::ColumnType_money);
	$col->renderer = "function(v,p,r){return r.data.InstallmentAmount - r.data.wage;}";
	$col->width = 80;
	$col->summaryType = GridColumn::SummeryType_sum;
	$col->summaryRenderer = "Installment.PureRemainSummaryRender";
	
	
	$col = $dg->addColumn("خالص کارمزد", "PureWage", GridColumn::ColumnType_money);
	$col->width = 80;
	$col->summaryType = GridColumn::SummeryType_sum;
	$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v );}";
	
	$col = $dg->addColumn("کارمزد مازاد صندوق از حساب سرمایه گذار", "PureFundWage", GridColumn::ColumnType_money);
	$col->width = 80;
	$col->summaryType = GridColumn::SummeryType_sum;
	$col->summaryRenderer = "function(v){return Ext.util.Format.Money(v );}";
}

$col = $dg->addColumn("مانده قسط", "remain", GridColumn::ColumnType_money);
$col->width = 120;

if(session::IsFramework())
{
	$col = $dg->addColumn("وضعیت تمدید", "IsDelayed");
	$col->renderer = "function(v,p,r){ return v == 'YES' ? 'تمدید شده' : '';}";
	$col->width = 120;

	$col = $dg->addColumn("ثبت سابقه", "");
	$col->renderer = "Installment.HistoryRender";
	$col->width = 80;
}
if($editable && $accessObj->EditFlag)
{
	$dg->addButton("", "محاسبه اقساط", "process", 
			"function(){InstallmentObject.ComputeInstallments();}");
	
	$dg->addButton("", "محاسبه قسط آخر", "process", 
			"function(){InstallmentObject.ComputeLatestInstallment();}");
	
	$dg->addButton("", "محاسبه اقساط بر اساس سیستم قدیم", "undo", 
			"function(){InstallmentObject.ComputeOldInstallments();}");
	
	//$dg->enableRowEdit = true;
	//$dg->rowEditOkHandler = "function(store,record){return InstallmentObject.SaveInstallment(store,record);}";
	
	$dg->addButton("", "ایجاد اقساط", "add", "function(){InstallmentObject.AddInstallments();}");
	//$dg->addButton("", "تغییر اقساط", "delay", "function(){InstallmentObject.DelayInstallments();}");
}

if(session::IsFramework()) 
{
	$dg->addButton("cmp_report2", "گزارش پرداخت", "report", "function(){InstallmentObject.PayReport();}");
}

$dg->EnableSummaryRow = true;
$dg->height = 377;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "InstallmentID";
$dg->DefaultSortDir = "ASC";
$dg->title = "جدول اقساط";
$dg->autoExpandColumn = "InstallmentAmount";

$grid = $dg->makeGrid_returnObjects();

//--------------------------------------------

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=emptyDataTable","grid_div");

$col = $dg->addColumn("سررسید", "InstallmentDate");
$col->width = 100;

$col = $dg->addColumn("مبلغ قسط", "InstallmentAmount", GridColumn::ColumnType_money);

$col = $dg->addColumn("حذف", "");
$col->renderer = "function(v,p,r){return Installment.DeleteRender(v,p,r)}";
$col->width =50;
$col->align = "center";

$dg->enableRowEdit = true;
$dg->disableFooter = true;
$dg->rowEditOkHandler = "function(store,record){record.InstallmentDate}";
$dg->height = 250;
$dg->width = 270;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "InstallmentDate";
$dg->autoExpandColumn = "InstallmentAmount";
$grid2 = $dg->makeGrid_returnObjects();


?>
<script type="text/javascript">

Installment.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	framework : <?= session::IsFramework() ? "true" : "false" ?>,
	RequestID : <?= $RequestID ?>,
	DefrayMode : <?= $DefrayMode ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Installment()
{
	this.grid = <?= $grid ?>;
	this.AddGrid = <?= $grid2 ?>;
		
	if(this.framework)
	{
		this.grid.addDocked({
			xtype: 'toolbar',
			dock: 'bottom',
			items: [{ 
				xtype: 'container', 
				autoWidth : true,
				flex : 1,
				html : "ردیف های زرد اقساط ثبت سابقه می باشند"+
					"<br>ردیف های سبز رنگ اقساطی هستند که به صورت دستی ایجاد شده اند"
			}
		]});
		if(this.grid.plugins[0])
			this.grid.plugins[0].on("beforeedit", function(editor,e){
				
				if(e.record.data.IsDelayed == "YES")
					return false;
				if(e.rowIdx == e.grid.getStore().getCount()-1)
					return false;
			});
		
		this.grid.getView().getRowClass = function(record, index)
		{
			if(record.data.history == "YES")
				return "yellowRow";
			if(record.data.ComputeType == "USER")
				return "greenRow";

			return "";
		}
		
		this.grid.getStore().proxy.extraParams = {RequestID : this.RequestID};
		this.grid.render(this.get("div_grid"));
		return;
	}
		
	this.PartPanel = new Ext.form.FieldSet({
		title: "انتخاب وام",
		width: 700,
		renderTo : this.get("div_loans"),
		collapsible : true,
		collapsed : false,
		frame: true,
		items : [{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'request.data.php?task=SelectMyRequests&mode=customer',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PartAmount',"RequestID","ReqAmount","ReqDate", "RequestID", "CurrentRemain",
							"IsEnded","StatusID",{
					name : "fullTitle",
					convert : function(value,record){
						return "کد وام : " + record.data.RequestID + " به مبلغ " + 
							Ext.util.Format.Money(record.data.ReqAmount) + " مورخ " + 
							MiladiToShamsi(record.data.ReqDate);
					}
				}]
			}),
			displayField: 'fullTitle',
			valueField : "RequestID",
			width : 600,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد وام</td>',
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ پرداخت</td> </tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.ReqAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.ReqDate)]}</td> </tr>',
				'</tpl>',
				'</table>'
			),
			itemId : "RequestID",
			listeners :{
				select : function(combo,records){
					InstallmentObject.SelectLoan(records[0]);					
				}
			}
		}]
	});
	
	this.PayPanel = new Ext.form.FieldSet({
		title: "پرداخت وام",
		hidden : true,
		layout : "column",
		columns : 3,
		width: 650,
		renderTo : this.get("div_paying"),
		frame: true,
		items : [{
			xtype : "displayfield",
			fieldCls : "blueText",
			itemId : "PayCode",
			fieldLabel : "شناسه پرداخت"
		},{
			xtype : "currencyfield",
			hideTrigger : true,
			width: 300,
			fieldLabel : "مبلغ قابل پرداخت",
			itemId : "PayAmount"
		}/*,{
			xtype : "button",
			border : true,
			disabled : true,
			style : "margin-right:10px",
			text : "پرداخت الکترونیک بانک اقتصاد نوین",
			iconCls : "epay",
			handler : function(){ InstallmentObject.PayInstallment(); }
		}*/,{
			xtype : "button",
			border : true,
			style : "margin-right:10px",
			text : "پرداخت الکترونیک بانک تجارت",
			iconCls : "epay",
			handler : function(){ InstallmentObject.PayInstallment_tejarat(); }
		},{
			xtype : "button",
			border : true,
			style : "margin-right:10px",
			text : "پرداخت الکترونیک بانک آینده",
			iconCls : "epay",
			handler : function(){ InstallmentObject.PayInstallment_ayande(); }
		},{
			xtype : "container",
			columns : 3,
			html : "* برای مشاهده ریز گزارش پرداخت وام خود می توانید از منوی گزارش پرداخت وام استفاده کنید ",
			cls : "blueText"
		}]
	});
	
	if(this.RequestID != 0)
	{
		this.PartPanel.down("[itemId=RequestID]").getStore().load({
			params : {
				RequestID : this.RequestID
			},
			callback : function(){
				record = this.getAt(0);
				InstallmentObject.PartPanel.down("[itemId=RequestID]").setValue(record.data.RequestID);
				InstallmentObject.SelectLoan(record);
				if(InstallmentObject.DefrayMode)
					InstallmentObject.PayPanel.down("[itemId=PayAmount]").setValue(<?= $TotalRemain ?>); 
			}
		});
	}
}

Installment.PureRemainSummaryRender = function(v,summaryData){
	e1 = InstallmentObject.grid.columns.findObject('dataIndex','wage').id;
	e2 = InstallmentObject.grid.columns.findObject('dataIndex','InstallmentAmount').id;
	return Ext.util.Format.Money(summaryData[e2] - summaryData[e1] );
}

Installment.prototype.SelectLoan = function(record){

	this.grid.getStore().proxy.extraParams = {
		RequestID : record.data.RequestID
	};
	if(!this.grid.rendered)
		this.grid.render(this.get("div_grid"));
	else
		this.grid.getStore().load();
	if(record.data.IsEnded == "YES")
	{
		Ext.MessageBox.alert("","این وام خاتمه یافته است");
		return;
	}
	if(record.data.StatusID != "<?= LON_REQ_STATUS_CONFIRM ?>")
	{
		Ext.MessageBox.alert("","این وام هنوز در صندوق تایید نشده است");
		return;
	}
	
	this.RequestID = record.data.RequestID;

	this.PayPanel.show();
	this.PayPanel.down("[itemId=PayCode]").setValue(
		LoanRFID(record.data.RequestID));

	this.PayPanel.down("[itemId=PayAmount]").setValue(record.data.CurrentRemain);	
}

Installment.HistoryRender = function(v,p,r){
	if(r.data.history == "YES")
		return "";
	return  "<div  title='سابقه' class='history' onclick='InstallmentObject.SetHistory();' " +
		"style='float:left;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

var InstallmentObject = new Installment();

Installment.prototype.PayCodeRender = function(){

	RequestID = this.PartPanel.down("[itemId=RequestID]").getValue();

	st = RequestID.lpad("0", 11);
	num = (st[0]*11) + (st[1]*10) + (st[2]*9) + (st[3]*1) + (st[4]*2) + (st[5]*3)
		+ (st[6]*4) + (st[7]*5) + (st[8]*6) + (st[9]*7) + (st[10]*8);
	remain = num % 99;
	
	return st + remain.toString().lpad("0", 2);
}

Installment.prototype.PayInstallment = function(){
	
	RequestID = this.PartPanel.down("[itemId=RequestID]").getValue();
	PayAmount = this.PayPanel.down("[itemId=PayAmount]").getValue();
	
	if(PayAmount == "")
		return;

	window.open(this.address_prefix + "../../portal/epayment/epayment_step1.php?RequestID=" + 
		RequestID + "&amount=" + PayAmount);	
}

Installment.prototype.PayInstallment_ayande = function(){
	
	RequestID = this.PartPanel.down("[itemId=RequestID]").getValue();
	PayAmount = this.PayPanel.down("[itemId=PayAmount]").getValue();
	
	if(PayAmount == "")
		return;

	window.open(this.address_prefix + "../../portal/epayment-ayande/epayment_step1.php?RequestID=" + 
		RequestID + "&amount=" + PayAmount);	
}

Installment.prototype.PayInstallment_tejarat = function(){
	
	RequestID = this.PartPanel.down("[itemId=RequestID]").getValue();
	PayAmount = this.PayPanel.down("[itemId=PayAmount]").getValue();
	
	if(PayAmount == "")
		return;

	window.open(this.address_prefix + "../../portal/epayment-tejarat/epayment_step1.php?RequestID=" + 
		RequestID + "&amount=" + PayAmount);	
}

Installment.prototype.SetHistory = function(){
	
	Ext.MessageBox.confirm("","آیا مایلید که این ردیف را ثبت سابقه کنید؟",function(btn){
		if(btn == "no")
			return;
		
		me = InstallmentObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "SetHistory",
				InstallmentID : record.data.InstallmentID
			},
			success: function(response){
				mask.hide();
				InstallmentObject.grid.getStore().load();
			}
		});
	});	
}

Installment.prototype.ComputeInstallments = function(){
	
	Ext.MessageBox.confirm("","در صورت محاسبه مجدد کلیه ردیف ها حذف و مجدد محاسبه و ایجاد می شوند <br>" + 
		"آیا مایل به محاسبه مجدد می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = InstallmentObject;
	
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "ComputeInstallments",
				RequestID : me.RequestID,
				IsLastest : "false"
			},
			success: function(response){
				
				result = Ext.decode(response.responseText);
				if(!result.success)
				{
					if(result.data == "DocExists")
						Ext.MessageBox.alert("Error", "این وام دارای سند اختلاف قسط می باشد و قادر به محاسبه مجدد نمی باشید");
					else if(result.data == "")
						Ext.MessageBox.alert("", "عملیات مورد نظر با شکست مواجه شد");
					else
						Ext.MessageBox.alert("", result.data);
				}
				mask.hide();
				InstallmentObject.grid.getStore().load();
			}
		});
	});	
}

Installment.prototype.ComputeLatestInstallment = function(){
	
	Ext.MessageBox.confirm("","آیا مایلید که شرایط جدید فقط روی قسط آخر محاسبه شود؟",function(btn){
		if(btn == "no")
			return;
		
		me = InstallmentObject;
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "ComputeInstallments",
				RequestID : me.RequestID,
				IsLastest : "true"
			},
			success: function(response){
				mask.hide();
				
				result = Ext.decode(response.responseText);
				if(!result.success)
				{
					if(result.data == "DocExists")
						Ext.MessageBox.alert("Error", "این وام دارای سند اختلاف قسط می باشد و قادر به محاسبه مجدد نمی باشید");
					else if(result.data == "")
						Ext.MessageBox.alert("", "عملیات مورد نظر با شکست مواجه شد");
					else
						Ext.MessageBox.alert("", result.data);
				}
				
				InstallmentObject.grid.getStore().load();
			}
		});
	});	
}

Installment.prototype.ComputeOldInstallments = function(){
	
	Ext.MessageBox.confirm("","آیا مایله به تغییر مبلغ اقساط به مبلغ محاسبات قدیم می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = InstallmentObject;
	
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "ComputeOldInstallments",
				RequestID : me.RequestID
			},
			success: function(response){
				
				result = Ext.decode(response.responseText);
				if(!result.success)
				{
					if(result.data == "")
						Ext.MessageBox.alert("", "عملیات مورد نظر با شکست مواجه شد");
					else
						Ext.MessageBox.alert("", result.data);
				}
				mask.hide();
				InstallmentObject.grid.getStore().load();
			}
		});
	});	
}

Installment.prototype.SaveInstallment = function(store, record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "SaveInstallment",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				InstallmentObject.grid.getStore().load();
			}
			else
			{
				alert("خطا در اجرای عملیات");
			}
		},
		failure: function(){}
	});
}

Installment.prototype.PayReport = function(){

	window.open(this.address_prefix + "../report/LoanPayment.php?show=true&RequestID=" + this.RequestID);
}

Installment.prototype.DelayInstallments = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
	{
		Ext.MessageBox.alert("","قسطی که بعد از آن مایل به تمدید می باشید را انتخاب کنید");
		return;
	}
	
	if(!this.delayWin)
	{
		this.delayWin = new Ext.window.Window({
			width : 350,
			height : 160,
			modal : true,
			title : "تمدید اقساط",
			bodyStyle : "background-color:white;padding-right:20px",
			items : [{
				xtype : "currencyfield",
				fieldLabel : "مبلغ جدید",
				name : "newAmount",
				hideTrigger : true
			},{
				xtype : "shdatefield",
				fieldLabel : "تمدید تا تاریخ",
				name : "newDate"
			},{
				xtype : "checkbox",
				boxLabel : "محاسبه کارمزد بر اساس باقیمانده قسط باشد",
				inputValue : 1,
				name : "IsRemainCompute"
			},{
				xtype : "checkbox",
				boxLabel : "تمدید برای کلیه اقساط بعدی نیز انجام شود",
				inputValue : 1,
				name : "ContinueToEnd"
			}],
			closeAction : "hide",
			buttons : [{
				text : "اعمال",				
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){
					
					me = InstallmentObject;
					var record = me.grid.getSelectionModel().getLastSelected();
					if(!record)
					{
						Ext.MessageBox.alert("","قسطی که بعد از آن مایل به تمدید می باشید را انتخاب کنید");
						return;
					}

					mask = new Ext.LoadMask(me.delayWin, {msg:'در حال ذخیره سازی ...'});
					mask.show();

					Ext.Ajax.request({
						url: me.address_prefix +'request.data.php',
						method: "POST",
						params: {
							task: "DelayInstallments",
							RequestID : record.data.RequestID,
							InstallmentID : record.data.InstallmentID,
							newDate : me.delayWin.down("[name=newDate]").getRawValue(),
							newAmount : me.delayWin.down("[name=newAmount]").getValue(),
							IsRemainCompute : me.delayWin.down("[name=IsRemainCompute]").checked ? 1 : 0,
							ContinueToEnd : me.delayWin.down("[name=ContinueToEnd]").checked ? 1 : 0
						},
						success: function(response){
							mask.hide();
							result = Ext.decode(response.responseText);
							if(!result.success)
								Ext.MessageBox.alert("", result.data);	
							
							InstallmentObject.delayWin.hide();
							InstallmentObject.grid.getStore().load();
						}
					});
				}				
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.delayWin);
	}
	
	this.delayWin.show();
	this.delayWin.center();
}

//............................................................

Installment.DeleteRender = function(){
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='InstallmentObject.DeletRow();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

Installment.prototype.AddInstallments = function(){
	
	if(!this.AddWin)
	{
		this.card0 = {
			itemId: 'card-0',
			anchor: "100%",
			layout : "vbox",
			items: [{
				xtype : "radiogroup",
				columns : 1,
				defaults : {width:450},
				items : [{
					xtype : "radio",
					boxLabel : "ورود اقساط بر اساس مبلغ کل وام و اعمال از ابتدای شروع اقساط",
					inputValue : "START",
					checked : true,
					name : "ComputeMode"
				},{
					xtype : "radio",
					boxLabel : "ورود اقساط بر اساس مانده اصل وام و اعمال از تاریخ خاص"	,
					inputValue : "REMAIN",
					itemId : "CMP_remain",
					name : "ComputeMode",
					listeners : {
						change : function(el){
							InstallmentObject.formPanel.down("[name=ComputeDate]").setDisabled(!el.getValue());
						}
					}
				}]
			},{
				xtype : "shdatefield",
				fieldLabel : "تاریخ شروع تغییرات",
				name : "ComputeDate",
				disabled : true
			}]
		};
		this.addRecord = {
			xtype : "panel",
			layout : "vbox",
			height : 250,
			width : 200,
			bodyStyle : "padding : 10px",
			defaults : {labelWidth : 40, width: 180},
			items : [{
				xtype : "shdatefield",
				name : "date",
				fieldLabel : "تاریخ"
			},{
				xtype : "currencyfield",
				name : "amount",
				hideTrigger : true, 
				fieldLabel : "مبلغ"
			},{
				xtype : "button",
				width : 80,
				style : "margin:0 40px",
				text : "ایجاد ردیف",
				iconCls : "add",
				handler : function(){
					me = InstallmentObject;
					me.AddGrid.getStore().add({
						InstallmentDate : me.formPanel.down("[name=date]").getRawValue(),
						InstallmentAmount : me.formPanel.down("[name=amount]").getValue()
					})
				}
			}]
		};
		this.card1 = {
			itemId : "card-1",
			anchor: "100%",
			layout : "vbox",
			items : [{
				xtype : "currencyfield",
				name : "DefrayAmount",
				readOnly : true,
				value : "0",
				width : 300,
				labelWidth : 150,
				fieldLabel : "مانده اصل وام در تاریخ فوق"
			},{
				xtype : "checkbox",
				name : 'WithWage',
				width : 230,
				checked : true,
				boxLabel : "کارمزد تغییر زمان اقساط محاسبه شود"
			},{
				xtype : "checkbox",
				name : 'ComputeWage',
				width : 230,
				boxLabel : "کارمزد هر قسط به مبلغ وارد شده اضافه گردد."
			},{
				xtype : "container",
				layout :{
					type : "table",
					columns : 2
				},
				items : [this.addRecord, this.AddGrid,{
					xtype : "button",
					iconCls : "process",
					text : "محاسبه اقساط",
					style : "text-align:center",
					handler : function(){ InstallmentObject.ComputeManualInstallments();}
				}]
			}],
			listeners :{
				activate : function(){
					me = InstallmentObject;
					var mask = new Ext.LoadMask(me.formPanel,{msg:'در حال بارگذاری ...'});
					//mask.show();
					Ext.Ajax.request({
						url : me.address_prefix + "request.data.php?task=GetPureAmount",
						method : "POST",
						params : {
							RequestID : me.RequestID,
							ComputeDate : me.formPanel.down("[itemId=CMP_remain]").getValue() ?
								me.formPanel.down("[name=ComputeDate]").getRawValue() : ""
						},
						success : function(response){
							st = Ext.decode(response.responseText);
							InstallmentObject.formPanel.down("[name=DefrayAmount]").setValue(st.data);
							mask.hide();
						}
					});
				}
			}
		};
		
		/*this.AddWin = new Ext.window.Window({
			width : 510,
			height : 460,
			title : "ثبت دستی اقساط",
			items : [
				this.formPanel = new Ext.form.Panel({
					defaults: {border: false},
					height : 400,
					width : 500,
					frame: true,
					bodyPadding: '5 5 0',
					layout: "card",
					activeItem: 0,
					items : [this.card0,this.card1],
					bbar: [{
						itemId: 'card-prev',
						text: '&laquo; قبلی',
						handler: function() {
							var itemId = InstallmentObject.formPanel.getLayout().getActiveItem().itemId;
							var i = itemId.split('card-')[1];
							var pre = i*1 - 1;
							InstallmentObject.formPanel.getLayout().setActiveItem(pre);
							InstallmentObject.formPanel.down('[itemId=card-prev]').setDisabled(pre === 0);
							InstallmentObject.formPanel.down('[itemId=card-next]').setDisabled(pre === 1);
						},
						disabled: true
					},'->', {
						itemId: 'card-next',
						text: 'بعدی &raquo;',
						handler: function() {
							var itemId = InstallmentObject.formPanel.getLayout().getActiveItem().itemId;
							var i = itemId.split('card-')[1];
							var next = parseInt(i, 10) + 1;
							InstallmentObject.formPanel.getLayout().setActiveItem(next);
							InstallmentObject.formPanel.down('[itemId=card-prev]').setDisabled(next === 0);
							InstallmentObject.formPanel.down('[itemId=card-next]').setDisabled(next === 1);
						}
					}]
				})
			],
			closeAction : "hide",
			buttons : [{
				text : "بازگشت",				
				iconCls : "undo",
				handler : function(){
					InstallmentObject.AddWin.hide();
				}				
			}]
		});*/
		
		this.AddWin = new Ext.window.Window({
			width : 510,
			height : 460,
			title : "ثبت دستی اقساط",
			items : [
				this.formPanel = new Ext.form.Panel({
					defaults: {border: false},
					height : 400,
					width : 500,
					frame: true,
					bodyPadding: '5 5 0',
					layout: "card",
					activeItem: 0,
					items : [{
						xtype : "container",
						layout :{
							type : "table",
							columns : 2
						},
						items : [this.addRecord, this.AddGrid,{
							xtype : "button",
							iconCls : "process",
							text : "محاسبه اقساط",
							style : "text-align:center",
							handler : function(){ InstallmentObject.ComputeManualInstallments();}
						}]
					}]					
				})
			],
			closeAction : "hide",
			buttons : [{
				text : "بازگشت",				
				iconCls : "undo",
				handler : function(){
					InstallmentObject.AddWin.hide();
				}				
			}]
		});
		Ext.getCmp(this.TabID).add(this.AddWin);
	}
	
	this.AddWin.show();
	this.AddWin.center();
}

Installment.prototype.AddRow = function(){

	var modelClass = this.AddGrid.getStore().model;
	var record = new modelClass({
		InstallmentDate : null,
		InstallmentAmount : null
	});

	this.AddGrid.plugins[0].cancelEdit();
	this.AddGrid.getStore().insert(0, record);
	this.AddGrid.plugins[0].startEdit(0, 0);
}

Installment.prototype.DeletRow = function(){

	var record = this.AddGrid.getSelectionModel().getLastSelected();
	this.AddGrid.getStore().remove(record);
}

Installment.prototype.ComputeManualInstallments = function(){
	
	var items = "[";
	for(i=0; i < this.AddGrid.getStore().getCount(); i++)
		items += Ext.encode(this.AddGrid.getStore().getAt(i).data) + ",";
	if(this.AddGrid.getStore().getCount() != 0)
		items = items.substring(0, items.length - 1);
	items += "]";
	
	mask = new Ext.LoadMask(this.AddWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();
		
	Ext.Ajax.request({
		url : this.address_prefix + "request.data.php?task=ComputeManualInstallments",
		method : "POST",
		params : {
			RequestID : this.RequestID,
			//ComputeDate : this.formPanel.down("[itemId=CMP_remain]").getValue() ?
			//this.formPanel.down("[name=ComputeDate]").getRawValue() : "",
			//ComputeWage : this.formPanel.down("[name=ComputeWage]").getValue() ? "YES" : "NO",
			//WithWage : this.formPanel.down("[name=WithWage]").getValue() ? "YES" : "NO",
			records : items
		},
		
		success : function(response){
			mask.hide();
			sd = Ext.decode(response.responseText);
			if(sd.success)
			{
				InstallmentObject.AddWin.hide();
				InstallmentObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("",sd.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : sd.data);
			}
		}
	});
}

function LoanRFID(RequestID){
	st = RequestID.lpad("0", 7);
	SUM = st[0]*1 + st[1]*2 + st[2]*3 + st[3]*4 + st[4]*5 + st[5]*6 + st[6]*7;
	remain = SUM % 11;
	remain = remain == 10 ? 0 : remain;
	
	code = st + remain;
	return code;
}
</script>
<center>
	<div id="div_loans"></div>
	<div id="div_paying"></div>	
	<div id="div_grid" style="width:98%"></div>	
</center>