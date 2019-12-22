<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.12
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid; 
require_once inc_dataReader;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]); 
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "cheques.data.php?task=selectIncomeCheques", "grid_div");

$dg->addColumn("", "IncomeChequeID", "", true);
$dg->addColumn("", "BackPayID", "", true);  
$dg->addColumn("", "ChequeStatus", "", true);
$dg->addColumn("", "BankDesc", "", true);
$dg->addColumn("", "ChequeAccNo", "", true);
$dg->addColumn("", "description", "", true);
$dg->addColumn("", "EqualizationID", "", true);

$col = $dg->addColumn("صاحب چک", "fullname", "");

$col = $dg->addColumn("حساب", "CostDesc");
$col->width = 150;

$col = $dg->addColumn("شعبه اخذ وام", "BranchName");
$col->width = 150;

$col = $dg->addColumn("بانک", "BankDesc");
$col->width = 100;

$col = $dg->addColumn("شعبه", "ChequeBranch");
$col->width = 100;

$col = $dg->addColumn("شماره چک", "ChequeNo");
$col->renderer = "IncomeCheque.ChequeNoRender";
$col->width = 70;

$col = $dg->addColumn("تاریخ چک", "ChequeDate", GridColumn::ColumnType_date);
$col->width = 80;

$col = $dg->addColumn("تاریخ وصول", "PayedDate", GridColumn::ColumnType_date);
/*$col->editor = ColumnEditor::SHDateField(true);
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return IncomeChequeObject.SavePayedDate();}";*/
$col->width = 80;

$col = $dg->addColumn("مبلغ چک", "ChequeAmount", GridColumn::ColumnType_money);
$col->width = 80;

$col = $dg->addColumn("وضعیت چک", "ChequeStatusDesc", "");
$col->width = 80;

/*$col = $dg->addColumn("اسناد", "docs", "");
$col->width = 80;*/

if($accessObj->EditFlag)
{
	$dg->addButton("", "اضافه چک", "add", "function(){IncomeChequeObject.AddCheque();}");
	$dg->addButton("", "اضافه چکهای وام", "add", "function(){IncomeChequeObject.AddLoanCheque();}");
	$dg->addButton("", "ویرایش چک", "edit", "function(){IncomeChequeObject.beforeEdit();}");
	$dg->addButton("", "تغییر وضعیت", "refresh", "function(){IncomeChequeObject.beforeChangeStatus();}");
	$dg->addButton("", "برگشت عملیات", "undo", "function(){IncomeChequeObject.ReturnLatestOperation();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn('حذف', '', 'string');
	$col->renderer = "IncomeCheque.DeleteRender";
	$col->width = 40;
	$col->align = "center";
}

$col = $dg->addColumn("", "", "");
$col->renderer = "IncomeCheque.HistoryRender";
$col->width = 40;

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 400;
$dg->title = "چک های دریافتی";
$dg->DefaultSortField = "ChequeDate";
$dg->DefaultSortDir = "Desc";
$dg->autoExpandColumn = "fullname";
$grid = $dg->makeGrid_returnObjects();

?>
<script>

IncomeCheque.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	GroupPays : new Array(),
	GroupPaysTitles : new Array(),
	
	GroupCheques : new Ext.data.ArrayStore({
		fields : ["ChequeDate","ChequeAmount","ChequeBank","ChequeBranch","description","ChequeNo",
			{name : "fullDesc",	convert : function(value,record){ return "چک به شماره " + 
					record.data.ChequeNo + " و تاریخ " + record.data.ChequeDate + " و مبلغ " + 
					record.data.ChequeAmount} }]
	}),
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

IncomeCheque.prototype.MakeFilterPanel = function(){
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("div_form"),
		width : 600,
		frame : true,
		collapsible : true,
		collapsed : true,
		title : "فیلتر لیست",
		layout : {
			type : "table",
			columns : 2			
		},
		
		items : [{
			xtype : "numberfield",
			name : "FromNo",
			hideTrigger : true,
			fieldLabel : "از شماره چک",
			listeners : {
				blur : function(){
					IncomeChequeObject.formPanel.down("[name=ToNo]").setValue(this.getValue())
				}
			}
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
				/*fields : ['InfoID','InfoDesc'],*/
				fields : ['TafsiliID','TafsiliDesc'],
				autoLoad : true
			}),
			fieldLabel : "وضعیت چک",
			/*displayField : "InfoDesc",
			valueField : "InfoID",*/
			displayField : "TafsiliDesc",
			valueField : "TafsiliID",
			queryMode : "local",			
			hiddenName :"ChequeStatus"
		}],
		buttons :[{
			text : "جستجو",
			iconCls : "search",
			handler : function(){
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
}

IncomeCheque.prototype.MakeLoanPanel = function(){

	return {
		title : "واریز گروهی اقسط وام",
		items : [{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/loan/request/request.data.php?task=SelectAllRequests2',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PartAmount',"IsEnded","RequestID","PartDate","loanFullname",
					"InstallmentAmount","ReqFullName","LoanDesc","totalRemain",{
					name : "fullTitle",
					convert : function(value,record){
						return "کد وام : " + record.data.RequestID + " به مبلغ " + 
							Ext.util.Format.Money(record.data.PartAmount) + " مورخ " + 
							MiladiToShamsi(record.data.PartDate) + " " + record.data.loanFullname;
					}
				}]
			}),
			displayField: 'fullTitle',
			pageSize : 25,
			name : "RequestID",
			fieldLabel : "انتخاب وام",
			valueField : "RequestID",
			width : 850,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد</td>',
				'<td style="padding:7px">نوع وام</td>',
				'<td style="padding:7px">وام گیرنده</td>',
				'<td style="padding:7px">معرفی کننده</td>',	
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ وام</td>',	
				'<td style="padding:7px">مانده وام</td>',	
				'</tr>',
				'<tpl for=".">',
					'<tpl if="IsEnded == \'YES\'">',
						'<tr class="x-boundlist-item pinkRow" style="border-left:0;border-right:0">',
					'<tpl else>',
						'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'</tpl>',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{LoanDesc}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{loanFullname}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{ReqFullName}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.PartAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.PartDate)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.totalRemain)]}</td>',
				' </tr>',
				'</tpl>',
				'</table>'
			),
			listeners : {
				select : function(combo, records){
					
					me = IncomeChequeObject;
					//me.ChequeInfoWin.down('[name=PayAmount]').setValue(records[0].data.InstallmentAmount);
					me.ChequeInfoWin.down('[name=PayAmount]').setValue(
							me.ChequeInfoWin.down('[name=ChequeAmount]').getValue());
				}
			}
		},{
			xtype : "currencyfield",
			hideTrigger : true,
			fieldLabel : "مبلغ پرداخت",
			name : "PayAmount"
		},{
			xtype : "container",
			layout : "hbox",
			items : [{
				xtype : "button",
				text : "اضافه به لیست",
				iconCls : "add",
				handler : function(){
					me = IncomeChequeObject;
					amountComp = me.ChequeInfoWin.down('[name=PayAmount]');
					LoanCombo = me.ChequeInfoWin.down('[name=RequestID]');
					RequestID = LoanCombo.getValue();
					LoanRecord = LoanCombo.getStore().getAt( LoanCombo.getStore().find("RequestID", RequestID) );
								
					if(LoanRecord.data.IsEnded == "YES")
					{
						Ext.MessageBox.alert("Error","این وام خاتمه یافته و امکان ثبت چک برای آن وجود ندارد");
						return;
					}
								
					me.GroupPays.push(RequestID + "_" + amountComp.getValue());
					me.GroupPaysTitles.push(new Array(LoanRecord.data.fullTitle + " به مبلغ " + 
						Ext.util.Format.Money(amountComp.getValue()), amountComp.getValue()));
					me.ChequeInfoWin.down("[itemId=GroupList]").bindStore(me.GroupPaysTitles);
					LoanCombo.setValue();
					amountComp.setValue();
					
				}
			},{
				xtype : "button",
				text : "حذف از لیست",
				iconCls : "cross",
				handler : function(){

					me = IncomeChequeObject;
					el = me.ChequeInfoWin.down("[itemId=GroupList]");
					index = el.getStore().indexOf(el.getSelected()[0]);
					if(index >= 0)
					{
						me.GroupPays.splice(index,1);
						me.GroupPaysTitles.splice(index,1);
						el.clearValue();
						el.bindStore(me.GroupPaysTitles);
					}
				}
			}]
		},{
			xtype : "multiselect",
			itemId : "GroupList",
			store : this.GroupPaysTitles,
			height : 100,
			width : 600
		}]	
	};
}

IncomeCheque.prototype.MakeCostPanel = function(){

	return {
		title : "واریز به حساب دیگر",
		items : [{
			xtype : "combo",
			width : 350,
			fieldLabel : "کد حساب",
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
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectCostCode',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			typeAhead: false,
			name : "CostID",
			valueField : "CostID",
			displayField : "fullDesc",
			listConfig: {
				loadingText: 'در حال جستجو...',
				emptyText: 'فاقد اطلاعات'
			},
			listeners :{
				select : function(combo,records){
					if(records[0].data.TafsiliType1 != null)
					{
						combo = IncomeChequeObject.ChequeInfoWin.down("[name=TafsiliID]");
						combo.enable();
						combo.setValue();
						combo.getStore().proxy.extraParams["TafsiliType"] = records[0].data.TafsiliType1;
						combo.getStore().load();

						combo = IncomeChequeObject.ChequeInfoWin.down("[name=TafsiliID2]");
						combo.enable();
						combo.setValue();
						combo.getStore().proxy.extraParams["TafsiliType"] = records[0].data.TafsiliType2;
						combo.getStore().load();
					}
				}
			}
		},{
			xtype : "combo",
			width : 350,
			disabled : true,
			fieldLabel : "تفصیلی",
			store: new Ext.data.Store({
				fields:["TafsiliID","TafsiliCode","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			typeAhead: false,
			pageSize : 10,
			name : "TafsiliID",
			valueField : "TafsiliID",
			displayField : "TafsiliDesc",
			listeners : { 
				select : function(){
					t1 = this.getStore().proxy.extraParams["TafsiliType"];
					combo = IncomeChequeObject.ChequeInfoWin.down("[name=TafsiliID2]");
					
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
			width : 350,
			disabled : true,
			fieldLabel : "تفصیلی2",
			store: new Ext.data.Store({
				fields:["TafsiliID","TafsiliCode","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			typeAhead: false,
			pageSize : 10,
			name : "TafsiliID2",
			valueField : "TafsiliID",
			displayField : "TafsiliDesc"
		}]
	};
}

IncomeCheque.HistoryRender = function(){
	return "<div  title='سابقه تغییرات' class='history' "+
		" onclick='IncomeChequeObject.ShowHistory();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

IncomeCheque.ChequeNoRender = function(v,p,r){
	
	st = "بانک : <b>" + r.data.BankDesc + "</b><br>شعبه : <b>" + 
		r.data.ChequeBranch + "</b><br>شماره حساب چک : <b>" + r.data.ChequeAccNo + "</b><br>توضیحات : <b>" + r.data.description + "</b>";
	p.tdAttr = "data-qtip='" + st + "'";
	return v;
}

IncomeCheque.DeleteRender = function(value, p, record){
	
	if(record.data.ChequeStatus == "<?= INCOMECHEQUE_NOTVOSUL ?>")
		return "<div  title='حذف' class='remove' onclick='IncomeChequeObject.DeleteCheque();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

function IncomeCheque(){
	
	this.MakeFilterPanel();
	
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
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.EqualizationID*1 > 0)
			return "yellowRow";
		return "";
	}

	this.grid.getStore().proxy.form = this.get("MainForm");
	this.grid.render(this.get("div_grid"));
	
	this.LoanPanel = this.MakeLoanPanel();
	this.CostPanel = this.MakeCostPanel();
	
	this.ChequeInfoWin = new Ext.window.Window({
		width : 900,
		height : 400,
		modal : true,
		closeAction : "hide",
		items : new Ext.form.Panel({
			layout :{
				type : "table",
				columns : 2
			},
			items :[{
				xtype : "combo",
				store : new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: '/framework/baseInfo/baseInfo.data.php?task=SelectBranches',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ["BranchID", "BranchName"]
				}),
				displayField: 'BranchName',
				valueField : "BranchID",
				name : "BranchID",
				fieldLabel : "شعبه ثبت سند"
			},{
				xtype : "shdatefield",
				name : "ChequeDate",
				allowBlank : false,
				fieldLabel : "تاریخ چک"
			},{
				xtype : "currencyfield",
				name : "ChequeAmount",
				hideTrigger : true,
				allowBlank : false,
				fieldLabel : "مبلغ چک"
			},{
				xtype : "numberfield",
				name : "ChequeNo",
				colspan : 2,
				allowBlank : false,
				minValue : 1,
				hideTrigger : true,
				fieldLabel : "شماره چک"
			},{
				xtype : "combo",
				store : new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetBanks',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ["BankID", "BankDesc"],
					autoLoad : true
				}),
				queryMode : "local",
				displayField: 'BankDesc',
				allowBlank : false,
				valueField : "BankID",
				name : "ChequeBank",
				fieldLabel : "بانک"
			},{
				xtype : "textfield",
				name : "ChequeBranch",
				fieldLabel : "شعبه"
			},{
				xtype : "textfield",
				name : "ChequeAccNo",
				colspan : 2,
				fieldLabel : "شماره حساب چک"
			},{
				xtype : "textfield",
				colspan : 2,
				width : 650,
				name : "description",
				fieldLabel : "توضیحات"
			},{
				xtype : "tabpanel",
				colspan : 2,
				height : 200,
				items :[this.LoanPanel,this.CostPanel]
			}]
		}),
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			itemId : "btn_save",
			handler : function(){ IncomeChequeObject.SaveIncomeCheque();}
		}]
	});
	Ext.getCmp(this.TabID).add(this.ChequeInfoWin);
}

IncomeChequeObject = new IncomeCheque();

IncomeCheque.prototype.beforeChangeStatus = function(){
	
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
					/*fields :  ['InfoID',"InfoDesc"]*/
					fields :  ['InfoID',"InfoDesc"]
				}),
				queryMode : "local",
				displayField: 'InfoDesc',
				valueField : "InfoID",
				/*displayField: 'InfoDesc',
				valueField : "InfoID",*/
				width : 400,
				name : "DstID",
				listeners : {
					select : function(){
						if(this.getValue() == <?= INCOMECHEQUE_VOSUL ?>)
						{
							this.up('window').down("[name=PayedDate]").enable();
							this.up('window').down("[name=UpdateLoanBackPay]").enable();
						}
						else
						{
							this.up('window').down("[name=PayedDate]").disable();
							this.up('window').down("[name=UpdateLoanBackPay]").disable();
						}
						
					}
				}
			},{
				xtype : "shdatefield",
				name : "PayedDate",
				fieldLabel : "تاریخ وصول",
				disabled : true
			},{
				xtype : "checkbox",
				name : "UpdateLoanBackPay",
				checked : true,
				boxLabel : "تاریخ پرداخت مشتری بر اساس تاریخ وصول به روزرسانی شود",
				disabled : true
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
	if(record.data.EqualizationID*1 > 0)
	{
		Ext.MessageBox.alert("Error","چکی که تایید مغایرت شده است تحت هیچ شرایطی قابل تغییر نمی باشد");
		return;
	}

	this.commentWin.down("[name=DstID]").setValue();
	this.commentWin.down("[name=DstID]").getStore().proxy.extraParams.SrcID = record.data.ChequeStatus;
	this.commentWin.down("[name=DstID]").getStore().load();
	
	this.commentWin.down("[itemId=btn_save]").setHandler(function(){
		status = this.up('window').down("[name=DstID]").getValue();
		/*if(status == "<?= INCOMECHEQUE_VOSUL ?>")
			IncomeChequeObject.AccountInfoWin();
		else*/
			IncomeChequeObject.ChangeStatus();
	});
		
	this.commentWin.show();
	this.commentWin.center();
}

IncomeCheque.prototype.ReturnLatestOperation = function(){

	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
	{
		Ext.MessageBox.alert("Error","ابتدا ردیف چک مورد نظر خود را انتخاب کنید");
		return;
	}
	
	if(<?= $_SESSION["USER"]["UserName"] == "admin" ? "false" : "true" ?> && record.data.EqualizationID*1 > 0)
	{
		Ext.MessageBox.alert("Error","چکی که تایید مغایرت شده است تحت هیچ شرایطی قابل تغییر نمی باشد");
		return;
	}

	Ext.MessageBox.confirm("","آیا مایل به برگشت آخرین عملیات انجام شده روی چک می باشید؟", function(btn){
		if(btn == "no")
			return ;
		
		me = IncomeChequeObject;
		
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال تغییر وضعیت ...'});
		mask.show();

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "cheques.data.php",
			params : {
				task : "ReturnLatestOperation",
				IncomeChequeID : record.data.IncomeChequeID
			},

			success : function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
					IncomeChequeObject.grid.getStore().load();
				else if(result.data != "")
					Ext.MessageBox.alert("",result.data);
				else
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		});
	});
}

IncomeCheque.prototype.AccountInfoWin = function(){
	
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
							me = IncomeChequeObject;
							if(records[0].data.TafsiliType1 != null)
							{
								me.BankWin.down("[itemId=TafsiliID]").setValue();
								me.BankWin.down("[itemId=TafsiliID]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType1;
								me.BankWin.down("[itemId=TafsiliID]").getStore().load();
							}
							if(records[0].data.TafsiliType2 != null)
							{
								me.BankWin.down("[itemId=TafsiliID2]").setValue();
								me.BankWin.down("[itemId=TafsiliID2]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType2;
								me.BankWin.down("[itemId=TafsiliID2]").getStore().load();
							}
							
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
							combo = IncomeChequeObject.BankWin.down("[itemId=TafsiliID2]");

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
				handler : function(){IncomeChequeObject.ChangeStatus();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.BankWin);
	}
	this.BankWin.show();
	this.BankWin.down("[itemId=btn_save]").setHandler(function(){ 
		IncomeChequeObject.BankWin.hide();
		IncomeChequeObject.ChangeStatus(); 
	});
}

IncomeCheque.prototype.ChangeStatus = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	StatusID = this.commentWin.down("[name=DstID]").getValue();
	PayedDate = "";
	/*if(StatusID == "<?= INCOMECHEQUE_VOSUL ?>")
	{
		PayedDate = this.commentWin.down("[name=PayedDate]").getRawValue();
		params.UpdateLoanBackPay = this.commentWin.down("[name=UpdateLoanBackPay]").getValue();
		
		if(PayedDate > new Ext.SHDate().format("Y/m/d"))
		{
			Ext.MessageBox.alert("Error","تنها بعد از تاریخ وصول چک می توانید سند مربوطه را صادر کنید");
			return;
		}
		//params = mergeObjects(params, this.BankWin.down('form').getForm().getValues());
	}	*/
	
	//this.ExecuteEvent(StatusID,PayedDate);
	//return;
	
	params = {
		task : "ChangeChequeStatus",
		BackPayID : record.data.BackPayID,
		IncomeChequeID : record.data.IncomeChequeID,
		StatusID : StatusID
	};
	
	if(StatusID == "<?= INCOMECHEQUE_VOSUL ?>")
	{
		params.PayedDate = this.commentWin.down("[name=PayedDate]").getRawValue();
		params.UpdateLoanBackPay = this.commentWin.down("[name=UpdateLoanBackPay]").getValue();
		
		if(params.PayedDate > new Ext.SHDate().format("Y/m/d"))
		{
			Ext.MessageBox.alert("Error","تنها بعد از تاریخ وصول چک می توانید سند مربوطه را صادر کنید");
			return;
		}
		//params = mergeObjects(params, this.BankWin.down('form').getForm().getValues());
	}	
	
	if(StatusID == null || StatusID == "")
		return;
	
	this.commentWin.hide();		
	mask = new Ext.LoadMask(this.grid, {msg:'در حال تغییر وضعیت ...'});
	mask.show();

	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "cheques.data.php",
		params : params,

		success : function(response){
			mask.hide();

			result = Ext.decode(response.responseText);
			if(result.success)
				IncomeChequeObject.grid.getStore().load();
			else if(result.data != "")
				Ext.MessageBox.alert("",result.data);
			else
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");


		}
	});
}

IncomeCheque.prototype.DeleteCheque = function(){
	
	Ext.MessageBox.confirm("","با حذف چک سند مربوطه نیز حذف می گردد. آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return ;
		
		me = IncomeChequeObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "cheques.data.php",
			params : {
				task : "DeleteCheque",
				IncomeChequeID : record.data.IncomeChequeID
			},

			success : function(response){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
					IncomeChequeObject.grid.getStore().load();
				else if(result.data != "")
					Ext.MessageBox.alert("ERROR",result.data);
				else
					Ext.MessageBox.alert("ERROR","عملیات مورد نظر با شکست مواجه شد");
			}
		});
	})
}

IncomeCheque.prototype.AddCheque = function(){
	
	this.ChequeInfoWin.down('form').getForm().reset();
	this.GroupPays = new Array();
	this.GroupPaysTitles = new Array();
	el = this.ChequeInfoWin.down("[itemId=GroupList]");
	el.bindStore(this.GroupPaysTitles)
	   
	this.ChequeInfoWin.show();
	this.ChequeInfoWin.down("[name=TafsiliID]").disable();
	this.ChequeInfoWin.down("[name=TafsiliID2]").disable();
}

IncomeCheque.prototype.SaveIncomeCheque = function(){
	
	if(!this.ChequeInfoWin.down('form').getForm().isValid())
		return;
	
	params = {};
	if(this.GroupPaysTitles.length > 0)
	{
		SumAmount = 0;
		for(i=0; i<this.GroupPaysTitles.length; i++)
			SumAmount += this.GroupPaysTitles[i][1];

		if(SumAmount != this.ChequeInfoWin.down("[name=ChequeAmount]").getValue()*1)
		{
			Ext.MessageBox.alert("Error","جمع مبالغ با مبلغ چک برابر نمی باشد");
			return false;
		}
		params.parts = Ext.encode(this.GroupPays);
	}
	Ext.MessageBox.prompt("","شماره سند<br>[در صورتی که شماره سند را وارد نکنید سند جدید ایجاد می گردد]" , function(btn, DocNo){
		if(btn == "cancel")
			return "";
		
		params.LocalNo = DocNo;
		me = IncomeChequeObject;
		
		mask = new Ext.LoadMask(me.ChequeInfoWin, {msg:'در حال ذخيره سازي...'});
		mask.show();

		me.ChequeInfoWin.down('form').getForm().submit({
			clientValidation: true,
			url: me.address_prefix + 'cheques.data.php?task=SaveIncomeCheque',
			method : "POST",
			params : params,

			success : function(form,action){                
				IncomeChequeObject.grid.getStore().load();
				IncomeChequeObject.ChequeInfoWin.hide();
				mask.hide();

			},
			failure : function(form,action)
			{
				if(action.result.data == "")
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("Error", action.result.data);
				mask.hide();
			}
		});
	});
}

IncomeCheque.prototype.SavePayedDate = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'cheques.data.php',
		method: "POST",
		params: {
			task: "SavePayedDate",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				IncomeChequeObject.grid.getStore().load();
			}
			else
			{
				if(st.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(st.data);
			}
		},
		failure: function(){}
	});
}

IncomeCheque.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش درخواست',
			modal : true,
			autoScroll : true,
			width: 700,
			height : 500,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "history.php",
				scripts : true
			},
			buttons : [{
					text : "بازگشت",
					iconCls : "undo",
					handler : function(){
						this.up('window').hide();
					}
				}]
		});
		Ext.getCmp(this.TabID).add(this.HistoryWin);
	}
	this.HistoryWin.show();
	this.HistoryWin.center();
	mask = new Ext.LoadMask(this.HistoryWin, {msg:'در حال بارگذاری ...'});
	mask.show();
	this.HistoryWin.loader.load({
		params : {
			IncomeChequeID : this.grid.getSelectionModel().getLastSelected().data.IncomeChequeID
		},
		callback : function(){mask.hide();}
	});
}

IncomeCheque.prototype.AddLoanCheque = function(){

	if(!this.LoanChequeWin)
	{
		this.LoanChequeWin = new Ext.window.Window({
			width : 900,
			height : 450,
			modal : true,
			closeAction : "hide",
			items : new Ext.form.Panel({
				layout :{
					type : "table",
					columns : 1
				},
				items :[{
					xtype : "container",
					layout : "hbox",
					items : [{
						xtype : "radio",						
						boxLabel : "بابت اقساط وام",
						name : "ChequeFor",
						inputValue : "INSTALLMENT",
						checked : true,
						style : "margin-left : 10px"
					},{
						xtype : "radio",
						boxLabel : "بابت تنفس وام",
						itemId : "RadioDelay",
						name : "ChequeFor",
						inputValue : "Delay"
					}]
				},{
					xtype : "combo",
					fieldLabel : "انتخاب وام",
					store: new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: '/loan/request/request.data.php?task=SelectAllRequests2',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ['PartAmount',"IsEnded","RequestID","PartDate","loanFullname",
							"DelayReturn","AgentDelayReturn",
							"InstallmentAmount","ReqFullName","LoanDesc","totalRemain",{
							name : "fullTitle",
							convert : function(value,record){
								return "کد وام : " + record.data.RequestID + " به مبلغ " + 
									Ext.util.Format.Money(record.data.PartAmount) + " مورخ " + 
									MiladiToShamsi(record.data.PartDate) + " " + record.data.loanFullname;
							}
						}]
					}),
					displayField: 'fullTitle',
					pageSize : 25,
					allowBlank : false,
					name : "RequestID",
					valueField : "RequestID",
					width : 850,
					listeners : {
						select : function(combo,records){
							if(IncomeChequeObject.LoanChequeWin.down("[itemId=RadioDelay]").getValue() && 
								records[0].data.DelayReturn != "CHEQUE" && 
								records[0].data.AgentDelayReturn != "CHEQUE" && 
								records[0].data.DelayReturn != "NEXTYEARCHEQUE" && 
								records[0].data.AgentDelayReturn != "NEXTYEARCHEQUE")
							{
								Ext.MessageBox.alert("ERROR", "نوع پرداخت تنفس وام انتخابی چک نمی باشد");
								this.setValue();
								return false;
							}
						}
					},
					tpl: new Ext.XTemplate(
						'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
						'<td style="padding:7px">کد </td>',
						'<td style="padding:7px">نوع وام</td>',
						'<td style="padding:7px">وام گیرنده</td>',
						'<td style="padding:7px">معرفی کننده</td>',
						'<td style="padding:7px">مبلغ وام</td>',
						'<td style="padding:7px">تاریخ وام</td>',
						'<td style="padding:7px">مانده وام</td>',
						'</tr>',
						'<tpl for=".">',
							'<tpl if="IsEnded == \'YES\'">',
								'<tr class="x-boundlist-item pinkRow" style="border-left:0;border-right:0">',
							'<tpl else>',
								'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
							'</tpl>',
							'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
							'<td style="border-left:0;border-right:0" class="search-item">{LoanDesc}</td>',
							'<td style="border-left:0;border-right:0" class="search-item">{loanFullname}</td>',
							'<td style="border-left:0;border-right:0" class="search-item">{ReqFullName}</td>',
							'<td style="border-left:0;border-right:0" class="search-item">',
								'{[Ext.util.Format.Money(values.PartAmount)]}</td>',
							'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.PartDate)]}</td>',
							'<td style="border-left:0;border-right:0" class="search-item">',
								'{[Ext.util.Format.Money(values.totalRemain)]}</td>',
						' </tr>',
						'</tpl>',
						'</table>'
					)
				},{
					xtype : "shdatefield",
					name : "ChequeDate",
					fieldLabel : "تاریخ چک"
				},{
					xtype : "currencyfield",
					name : "ChequeAmount",
					hideTrigger : true,
					fieldLabel : "مبلغ چک"
				},{
					xtype : "numberfield",
					name : "ChequeNo",
					hideTrigger : true,
					minValue : 1,
					fieldLabel : "شماره چک"
				},{
					xtype : "combo",
					store : new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetBanks',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ["BankID", "BankDesc"],
						autoLoad : true
					}),
					queryMode : "local",
					displayField: 'BankDesc',
					valueField : "BankID",
					name : "ChequeBank",
					fieldLabel : "بانک"
				},{
					xtype : "textfield",
					name : "ChequeBranch",
					fieldLabel : "شعبه"
				},{
					xtype : "textfield",
					name : "ChequeAccNo",
					fieldLabel : "شماره حساب چک"
				},{
					xtype : "textfield",
					width : 650,
					name : "description",
					fieldLabel : "توضیحات"
				},{
					xtype : "container",
					layout : "hbox",
					items :[{
						xtype : "button",
						iconCls : "add",
						text : "اضافه به لیست",
						handler : function(){
							me = IncomeChequeObject;
							parent = me.LoanChequeWin;
							me.GroupCheques.add({
								ChequeDate : parent.down("[name=ChequeDate]").getRawValue(),
								ChequeAmount : parent.down("[name=ChequeAmount]").getValue(),
								ChequeNo : parent.down("[name=ChequeNo]").getValue(),
								ChequeBank : parent.down("[name=ChequeBank]").getValue(),
								ChequeBranch : parent.down("[name=ChequeBranch]").getValue(),
								ChequeAccNo : parent.down("[name=ChequeAccNo]").getValue(),
								description : parent.down("[name=description]").getValue()
							});
							
							parent.down("[itemId=GroupList]").bindStore(me.GroupCheques);
							parent.down("[name=ChequeDate]").setValue();
							parent.down("[name=ChequeNo]").setValue();
							parent.down("[name=description]").setValue();
							
							parent.down("[name=ChequeDate]").focus();
						}
					},{
						xtype : "button",
						iconCls : "cross",
						text : "حذف از لیست",
						handler : function(){
							comp = IncomeChequeObject.LoanChequeWin.down("[itemId=GroupList]");
							record = comp.getSelected()[0];
							index = IncomeChequeObject.GroupCheques.find("ChequeNo",record.data.ChequeNo);
							IncomeChequeObject.GroupCheques.removeAt(index);
						}
					}]
				},{
					xtype : "multiselect",
					itemId : "GroupList",
					store : this.GroupCheques,
					displayField : "fullDesc",
					height : 100,
					width : 500
		
				}]
			}),
			buttons :[{
				text : "ذخیره",
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){ IncomeChequeObject.SaveLoanCheque();}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.LoanChequeWin);
	}
	
	this.LoanChequeWin.show();
}

IncomeCheque.prototype.SaveLoanCheque = function(){
		
	if(!this.LoanChequeWin.down('form').getForm().isValid())
		return;
	
	var store_data = new Array();
	this.GroupCheques.each(function(record){
		store_data.push(JSON.stringify({
			ChequeDate : record.data.ChequeDate,
			ChequeAmount : record.data.ChequeAmount,
			ChequeNo : record.data.ChequeNo,
			ChequeBank : record.data.ChequeBank,
			ChequeBranch : record.data.ChequeBranch,
			ChequeAccNo : record.data.ChequeAccNo,
			description : record.data.description
		}));
	});
	if(store_data.length == 0)
	{
		Ext.MessageBox.alert("Error","هیچ چکی به لیست اضافه نشده است");
		return;
	}
	
	mask = new Ext.LoadMask(this.LoanChequeWin, {msg:'در حال ذخيره سازي...'});
	mask.show();
	
	this.LoanChequeWin.down('form').getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'cheques.data.php?task=SaveLoanCheque',
		method : "POST",
		params : {
			cheques : JSON.stringify(store_data)
		},

		success : function(form,action){              
			
			IncomeChequeObject.grid.getStore().load();
			IncomeChequeObject.LoanChequeWin.hide();
			IncomeChequeObject.LoanChequeWin.down('form').getForm().reset();
			IncomeChequeObject.GroupCheques.removeAll();
			mask.hide();

		},
		failure : function(form,action)
		{
			mask.hide();
			if(action.result.data == "")
				Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
			else
				Ext.MessageBox.alert("Error", action.result.data);
		}
	});
}

IncomeCheque.prototype.beforeEdit = function(){
	
	if(!this.editWin)
	{
		this.editWin = new Ext.window.Window({
			width : 414,
			height : 250,
			modal : true,
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				name : "reason",
				fieldLabel : "دلیل تغییر"
			},{
				xtype : "currencyfield",
				name : "newAmount",
				hideTrigger : true,
				fieldLabel : "مبلغ جدید"
			},{
				xtype : "shdatefield",
				name : "newDate",
				fieldLabel : "تاریخ جدید"
			}],
			closeAction : "hide",
			buttons : [{
				text : "تغییر چک",				
				iconCls : "edit",
				itemId : "btn_save"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.editWin);
	}
	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
	{
		Ext.MessageBox.alert("Error","ابتدا ردیف مورد نظر خود را انتخاب کنید");
		return;
	}
	if(record.data.EqualizationID*1 > 0)
	{
		Ext.MessageBox.alert("Error","چکی که تایید مغایرت شده است تحت هیچ شرایطی قابل تغییر نمی باشد");
		return;
	}
	if(record.data.ChequeStatus == "<?= INCOMECHEQUE_VOSUL ?>")
	{
		Ext.MessageBox.alert("Error","چکی که وصول شده است قابل تغییر نمی باشد");
		return;
	}
	this.editWin.down("[name=newAmount]").setValue(record.data.ChequeAmount);
	this.editWin.down("[name=newDate]").setValue(MiladiToShamsi(record.data.ChequeDate));
	
	this.editWin.down("[itemId=btn_save]").setHandler(function(){
		newAmount = this.up('window').down("[name=newAmount]").getValue();
		newDate = this.up('window').down("[name=newDate]").getRawValue();
		reason = this.up('window').down("[name=reason]").getValue();

		mask = new Ext.LoadMask(IncomeChequeObject.grid, {msg:'در حال تغییر ...'});
		mask.show();

		Ext.Ajax.request({
			methos : "post",
			url : IncomeChequeObject.address_prefix + "cheques.data.php",
			params : {
				task : "editCheque",
				newAmount : newAmount,
				newDate : newDate,
				reason : reason,
				IncomeChequeID : record.data.IncomeChequeID
			},

			success : function(response){
				mask.hide();
				IncomeChequeObject.editWin.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
					IncomeChequeObject.grid.getStore().load();
				else if(result.data != "")
					Ext.MessageBox.alert("",result.data);
				else
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		});
	});
		
	this.editWin.show();
	this.editWin.center();
}

IncomeCheque.prototype.ExecuteEvent = function(StatusID,PayedDate){
	
	var record = this.grid.getSelectionModel().getLastSelected();

	Ext.Ajax.request({
		url : this.address_prefix + "cheques.data.php?task=GetBackPays",
		method : "POST",
		params: {
			IncomeChequeID : record.data.IncomeChequeID,
			StatusID : StatusID
		},
		success : function(response){
			result = Ext.decode(response.responseText);
			
			if(result.success)
			{
				result = result.data.split("_");
				if(result.length == 0)
					this.grid.getStore().load();
				else
					framework.ExecuteEvent(result[0], new Array(
					result[1],result[2],result[3],StatusID,PayedDate));
			}
		}
	})
}

</script>
<center>
	<br>
	<form id="MainForm">
		<div id="div_form"></div>
	</form>
	<br>
	<div style="width: 98%" id="div_grid"></div>
	ردیف های زرد رنگ چک هایی هستند که از طریق مغایرت تایید شده اند
</center>