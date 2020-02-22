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

$reportOnly = false;
$PersonID = "";
if(!empty($_REQUEST["reportOnly"]))
{
	$reportOnly = true;
	if(session::IsPortal())
		$PersonID = $_SESSION["USER"]["PersonID"];
	else
		$PersonID = $_POST["PersonID"];
	
	if(empty($PersonID))
		die();
}

$dg = new sadaf_datagrid("dg", $js_prefix_address . "saving.data.php?task=GetRulePeriods", "grid_div");

$col = $dg->addColumn("", "RowID","", true);
$col = $dg->addColumn("", "RuleID","", true);
$col = $dg->addColumn("", "WagePercent","", true);
$col = $dg->addColumn("", "MinAmount","", true);
$col = $dg->addColumn("", "MaxAmount","", true);

$col = $dg->addColumn("تعداد ماه پس انداز", "months","");
$col->align = "center";
$col->width = 130;

$col = $dg->addColumn("تعداد قسط", "InstallmentCount","");
$col->align = "center";

$col = $dg->addColumn("مبلغ دریافتی", "amount", GridColumn::ColumnType_money);
$col->renderer = "SavingLoan.amountRender";

$col = $dg->addColumn("کارمزد", "wage", GridColumn::ColumnType_money);
$col->width = 110;
$col->renderer = "SavingLoan.wageRender";

$col = $dg->addColumn("مبلغ هر قسط", "InstallmentAmount", GridColumn::ColumnType_money);
$col->width = 110;
$col->renderer = "SavingLoan.InstallmentAmountRender";


$dg->emptyTextOfHiddenColumns = true;
$dg->height = 200;
$dg->width = 600;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "months";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "amount";
$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

SavingLoan.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	reportOnly : <?= $reportOnly ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function SavingLoan()
{
	this.grid = <?= $grid ?>;
	this.MakeInfoPanel();
	
	if(this.reportOnly)	
	{
		this.LoadInfo('<?= $PersonID ?>');
		return;
	}
	this.MainPanel = new Ext.form.FieldSet({
		title: "انتخاب فرد",
		width: 500,
		renderTo : this.get("div_form"),
		frame: true,
		items : [{
			xtype : "shdatefield",
			name : "StartDate",
			labelWidth : 150,
			width : 300,
			value : '<?= DateModules::FirstGDateOfYear() ?>',
			fieldLabel : "تاریخ شروع محاسبه میانگین"
		},{
			xtype : "shdatefield",
			name : "EndDate",
			labelWidth : 150,
			width : 300,
			value : '<?= DateModules::shNow() ?>',
			fieldLabel : "تاریخ پایان محاسبه میانگین"
		},{
			xtype : "combo",
			colspan : 2,
			width : 450,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=GetAccessBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه",
			queryMode : 'local',
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID",
			listeners :{
				select : function(combo,records){
					personCombo = SavingLoanObject.MainPanel.down("[name=PersonID]");
					personCombo.getStore().proxy.extraParams.BranchID = records[0].data.BranchID;
					personCombo.getStore().load();
					personCombo.enable();
				}
			}
		},{
			xtype : "combo",
			width : 450,
			fieldLabel : "انتخاب فرد",
			disabled : true,
			pageSize : 20,
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'saving.data.php?task=selectPersons',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PackNo','PersonID','fullname', {
					name : "title",
					convert : function(v,r){
						return "[ شماره پرونده : " + r.data.PackNo + " ] " + r.data.fullname;
					}
				}]
			}),
			displayField: 'title',
			valueField : "PersonID",
			name : "PersonID"
		},{
			xtype : "button",
			text : "بارگذاری اطلاعات",
			iconCls : "refresh",
			handler : function(){
				me = SavingLoanObject;
				SavingLoanObject.LoadInfo(
					me.MainPanel.down("[name=StartDate]").getRawValue(),
					me.MainPanel.down("[name=EndDate]").getRawValue(),
					me.MainPanel.down("[name=PersonID]").getValue(),
					me.MainPanel.down("[name=BranchID]").getValue());
			}
		},{
			xtype : "button",
			text : "گزارش  محاسبه میانگین",
			iconCls : "report",
			handler : function(){
				me = SavingLoanObject;
				SDate = me.MainPanel.down("[name=StartDate]").getRawValue();
				EDate = me.MainPanel.down("[name=EndDate]").getRawValue();
				PID = me.MainPanel.down("[name=PersonID]").getValue();
				BranchID = me.MainPanel.down("[name=BranchID]").getValue();
				
				window.open(me.address_prefix + "report.php?PersonID="+PID+"&StartDate="+ SDate
						+"&EndDate="+ EDate + "&BranchID=" + BranchID);
			}
		}]
	});
}

SavingLoan.prototype.MakeInfoPanel = function(){
	
		this.InfoPanel = new Ext.form.Panel({
		renderTo : this.get("div_info"),
		width : 620,
		hidden : true,
		frame : true,
		height : 350,
		layout : {
			type : "table",
			columns : 3
		},		
		defaults :{
			width : 200			
		},
		items :[{
			xtype : "displayfield",
			fieldLabel : "تاریخ شروع سپرده",
			name : "FirstDate",
			style : "margin:4px",
			fieldCls : "blueText"
		},{
			xtype : "displayfield",
			fieldLabel : "میانگین حساب",
			name : "AverageAmount",
			fieldCls : "blueText",
			renderer : function(v){ return Ext.util.Format.Money(v) + " ریال"}
		},{
			xtype : "displayfield",
			fieldLabel : "تعداد ماه پس انداز",
			name : "TotalMonths",
			fieldCls : "blueText"
		},{
			xtype : "combo",
			width : 600,	
			colspan : 3,
			fieldLabel : "قوانین مجاز",
			store : new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'saving.data.php?task=selectRules',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['RuleID','RuleDesc','details','FromDate','ToDate',
					'WagePercent','MaxAmount','MinAmount']
			}),
			queryMode : "local",
			valueField : "RuleID",
			displayField : "RuleDesc",
			name : "RuleID",
			listeners : {
				select : function(combo, records){
					me.grid.getStore().proxy.extraParams.RuleID = records[0].data.RuleID;
					me.grid.getStore().load();
					me.InfoPanel.loadRecord(records[0]);
				}
			}
		},{
			xtype : "fieldset",
			colspan : 3,
			width : 600,
			defaults :{
				width : 200			
			},
			layout : {
				type : "table",
				columns : 3
			},			
			items : [{
				xtype : "displayfield",
				name : "WagePercent",
				fieldLabel : "کارمزد",
				fieldCls : "blueText",
				renderer : function(v){ return v + " %" ; }
			},{
				xtype : "displayfield",
				name : "FromDate",
				fieldLabel : "از تاریخ",
				fieldCls : "blueText",
				renderer : function(v){ return MiladiToShamsi(v);}
			},{
				xtype : "displayfield",
				name : "ToDate",
				fieldLabel : "تا تاریخ",
				fieldCls : "blueText",
				renderer : function(v){ return MiladiToShamsi(v);}
			},{
				xtype : "displayfield",
				name : "MinAmount",
				fieldLabel : "حداقل مبلغ",
				fieldCls : "blueText",
				renderer : function(v){ return Ext.util.Format.Money(v) + " ریال"}
			},{
				xtype : "displayfield",
				name : "MaxAmount",
				fieldLabel : "حداکثر مبلغ",
				colspan : 2,
				fieldCls : "blueText",
				renderer : function(v){ return Ext.util.Format.Money(v) + " ریال"}
			},{
				xtype : "displayfield",
				name : "details",
				colspan : 3,
				fieldCls : "blueText",
				fieldLabel : "توضیحات"
			}]
		},{
			xtype : "container",
			colspan : 3,
			width : 600,
			style : "text-align:center",
			items :[this.grid]
		}],
		buttons :[{
			text : "ایجاد وام",
			disabled : this.AddAccess ? false : true,
			iconCls : "save",
			handler : function(){ SavingLoanObject.CreateLoan(); }
		}]
	});
}

SavingLoan.amountRender = function(v,p,r){

	me = SavingLoanObject;
	if(!me.InfoStore || me.InfoStore.getCount() == 0)
		return;
		
	record = me.InfoStore.getAt(0);
	ratio = Math.floor(record.data.TotalMonths/r.data.months);
	amount = ratio*record.data.AverageAmount;
	if(amount < r.data.MinAmount*1)	amount = 0;
	if(amount > r.data.MaxAmount*1)	amount = r.data.MaxAmount*1;
	
	return Ext.util.Format.Money(amount);
	
}

SavingLoan.wageRender = function(v,p,r){

	me = SavingLoanObject;
	if(!me.InfoStore || me.InfoStore.getCount() == 0)
		return;
		
	record = me.InfoStore.getAt(0);
	ratio = Math.floor(record.data.TotalMonths/r.data.months);
	amount = ratio*record.data.AverageAmount;
	if(amount < r.data.MinAmount*1)	amount = 0;
	if(amount > r.data.MaxAmount*1)	amount = r.data.MaxAmount*1;
	
	return Ext.util.Format.Money(Math.round(amount*r.data.WagePercent/100));
	
}

SavingLoan.InstallmentAmountRender = function(v,p,r){

	me = SavingLoanObject;
	if(!me.InfoStore || me.InfoStore.getCount() == 0)
		return;
		
	record = me.InfoStore.getAt(0);
	ratio = Math.floor(record.data.TotalMonths/r.data.months);
	amount = ratio*record.data.AverageAmount;
	if(amount < r.data.MinAmount*1)	amount = 0;
	if(amount > r.data.MaxAmount*1)	amount = r.data.MaxAmount*1;
	
	totalAmount = amount + Math.round(amount*r.data.WagePercent/100);
	return Ext.util.Format.Money( Math.round(totalAmount/(r.data.InstallmentCount*ratio)) );
	
}

SavingLoan.prototype.LoadInfo = function(StartDate,EndDate, PersonID, BranchID){

	if(!this.InfoStore)
	{
		this.InfoStore = new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + "saving.data.php?task=GetSavingLoanInfo",
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields : ["PersonID","AverageAmount","TotalMonths","FirstDate"],
			listeners :{
				load : function(){
					
					me = SavingLoanObject;
					me.InfoPanel.getForm().reset();
					
					if(this.getCount() == 0)
					{
						Ext.MessageBox.alert("",this.getProxy().getReader().jsonData.message);
						return;
					}
					record = this.getAt(0);
					me.InfoPanel.loadRecord(record);
					me.InfoPanel.down("[name=RuleID]").getStore().load({
						params : {
							Date : record.data.FirstDate
						}
					});
					//.........................
					me.InfoPanel.show();
				}
			}
		});
	}
	
	mask = new Ext.LoadMask(this.InfoPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	this.InfoStore.load({
		params : {
			StartDate : StartDate,
			EndDate : EndDate,
			PersonID : PersonID,
			BranchID : BranchID
		},
		callback : function(){mask.hide();}
	});
}

SavingLoan.prototype.CreateLoan = function(){

	if(this.InfoStore.getCount() == 0)
	{
		Ext.MessageBox.alert("","اطلاعات ناقص است");
		return;
	}
	record = this.InfoStore.getAt(0);
	record2 =  this.grid.getSelectionModel().getLastSelected();
	if(!record2)	
	{
		Ext.MessageBox.alert("","ردیف مورد نظر در جدول دوره ها را انتخاب کنید");
		return;
	}
	ratio = Math.floor(record.data.TotalMonths/record2.data.months);
	amount = ratio*record.data.AverageAmount;
	if(amount < record2.data.MinAmount*1)	amount = 0;
	if(amount > record2.data.MaxAmount*1)	amount = record2.data.MaxAmount*1;
	
	if(amount == 0)	
	{
		Ext.MessageBox.alert("","مبلغ ردیف دوره انتخاب شده صفر می باشد");
		return;
	}
	
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	Ext.Ajax.request({
		url: this.address_prefix +'saving.data.php',
		method: "POST",
		params: {
			task: "CreateLoan",
			LoanPersonID : record.data.PersonID,
			ReqAmount : amount,
			PartAmount : amount,
			InstallmentCount : record2.data.InstallmentCount*ratio,
			CustomerWage : record2.data.WagePercent
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(!st.success)
			{
				if(st.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("Error",st.data);
			}
			else
			{
				SavingRuleObject.InfoPanel.hide();
				Ext.MessageBox.alert("","وام مربوطه با موفقیت ایجاد گردید.");
			}
				
		},
		failure: function(){}
	});
}

var SavingLoanObject = new SavingLoan();

</script>
<center>
	<div id="div_form"></div>
	<div id="div_info" align="center"></div>
</center>