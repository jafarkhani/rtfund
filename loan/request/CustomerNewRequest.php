<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.06
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "../../loan/loan/loan.data.php?task=GetAllLoans", "grid_div");

$dg->addColumn("کد وام", "LoanID", "", true);
$dg->addColumn("", "GroupID", "", true);
$dg->addColumn("", "GroupDesc", "", true);
$dg->addColumn("", "InstallmentCount", "", true);
$dg->addColumn("", "IntervalType", "", true);
$dg->addColumn("", "PayInterval", "", true);
$dg->addColumn("", "DelayMonths", "", true);
$dg->addColumn("", "MaxAmount", "", true);
$dg->addColumn("","ForfeitPercent", "", true);
$dg->addColumn("","CustomerWage", "", true);

$col = $dg->addColumn("عنوان وام", "LoanDesc", "");
$col->sortable = false;

$dg->addObject("this.LoanGroups");

$dg->HeaderMenu = false;
$dg->hideHeaders = true;

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 150;
$dg->width = 220;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "MaxAmount";
$dg->disableFooter = true;

$grid = $dg->makeGrid_returnObjects();

$LoanID = 0;
$RequestID = !empty($_REQUEST["RequestID"]) ? $_REQUEST["RequestID"] : 0;
if($RequestID > 0)
{
	$obj = new LON_requests($RequestID);
	$LoanID = $obj->LoanID;
}

?>
<script>
	
NewLoanRequest.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	LoanID : <?= $LoanID?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function NewLoanRequest()
{
	this.LoanGroups = new Ext.form.ComboBox({
		store : new Ext.data.SimpleStore({
			proxy: {type: 'jsonp',
				url: this.address_prefix + '../../loan/loan/loan.data.php?task=SelectLoanGroups',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields : ['InfoID','InfoDesc'],
			autoLoad : true,
			listeners : {
				load : function(){
					me = NewLoanRequestObject;
					me.LoanGroups.setValue(this.getAt(0).data.InfoID);
				}
			}
		}),
		valueField : "InfoID",
		queryMode : "local",
		name : "GroupID",
		displayField : "InfoDesc",
		labelWidth : 50,
		fieldLabel : "گروه وام",
		listeners :{
			change : function(){
				me = NewLoanRequestObject;
				me.grid.getStore().proxy.extraParams.GroupID = this.getValue();
				me.grid.getStore().load();
			}
		}
	});
	
	this.grid = <?= $grid ?>;
	this.grid.on("itemclick", function(){
		record = NewLoanRequestObject.grid.getSelectionModel().getLastSelected();
		NewLoanRequestObject.mainPanel.loadRecord(record);
		NewLoanRequestObject.mainPanel.doLayout();
		NewLoanRequestObject.LoadSummary(record);
		NewLoanRequestObject.mainPanel.down("[name=ReqAmount]").setMaxValue(record.data.MaxAmount);
		NewLoanRequestObject.mainPanel.down("[name=ReqAmount]").setValue(record.data.MaxAmount);
		NewLoanRequestObject.mainPanel.down("[name=PayInterval]").setValue(record.data.PayInterval + " " + 
			(record.data.IntervalType == "DAY" ? "روز" : "ماه"));
	});
	
	this.grid.getStore().on("beforeload", function(){
		if(this.proxy.extraParams.GroupID == null)
			return false;
	});
	
	this.mainPanel = new Ext.form.FormPanel({
		renderTo : this.get("mainForm"),
		width: 770,
		border : 0,
		items: [{
			xtype : "fieldset",
			title : "انتخاب وام درخواستی",
			layout : "column",
			columns : 2,
			anchor : "100%",
			items :[this.grid,{
				xtype : "container",
				layout : {
					type : "table",
					columns : 4
				},
				defaults : {
					xtype : "displayfield",
					style : "margin-top:10px",
					labelWidth : 73,
					width : 130,
					fieldCls : "blueText"
				},
				items : [{
					xtype : "container",
					colspan : 4 ,
					width: 300,
					style : "margin-right:5px; color:#0d6eb2",					
					html : "<font color=red>" + "توجه: " + "</font>" + "برای مشاهده جزئیات هر وام روی عنوان وام کلیک کنید."
				},{
					fieldLabel: 'سقف مبلغ',
					colspan : 3,
					width : 280,
					name: 'MaxAmount',
					renderer : function(v){ return Ext.util.Format.Money(v) + " ریال"}
				},{
					fieldLabel: 'تعداد اقساط',
					name: 'InstallmentCount'
				},{
					fieldLabel: 'فاصله اقساط',
					name: 'PayInterval',
					value : 0
				},{
					fieldLabel: 'مدت تنفس',
					renderer : function(v){ return v + " ماه"},
					name: 'DelayMonths'
				},{
					fieldLabel: 'درصد کارمزد',
					renderer : function(v){ return v + " %"},
					name: 'CustomerWage'
				},{
					fieldLabel: 'درصد دیرکرد',
					renderer : function(v){ return v + " %"},
					name: 'ForfeitPercent'
				},{
					xtype : "container",
					cospan : 4,
					contentEl : this.get("summaryDIV")
				}]
			}]
		},{
			xtype : "fieldset",
			title : "جزئیات درخواست",
			items : [{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					proxy: {
						type: 'jsonp',
						url: this.address_prefix + '../../framework/baseinfo/baseinfo.data.php?' +
							"task=SelectBranches",
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields : ['BranchID','BranchName'],
					autoLoad : true					
				}),
				fieldLabel : "شعبه اخذ وام",
				queryMode : 'local',
				allowBlank : false,
				beforeLabelTextTpl: required,
				displayField : "BranchName",
				valueField : "BranchID",
				name : "BranchID"
			},{
				xtype : "currencyfield",
				name : "ReqAmount",
				allowBlank : false,
				beforeLabelTextTpl: required,
				fieldLabel : "مبلغ درخواستی",
				hideTrigger: true,
				afterSubTpl: '<tpl>ریال</tpl>'
			},{
				xtype : "textarea",
				fieldLabel : "توضیحات",
				anchor : "90%",
				name : "ReqDetails"
			}]
		}],

		buttons : [{
			text : "ثبت درخواست وام و ارسال به صندوق",
			iconCls: 'save',
			handler: function() {
				
				if(!NewLoanRequestObject.grid.getSelectionModel().getLastSelected())
				{
					Ext.MessageBox.alert("","لطفا وام مورد نظر خود را با کلیک بر روی عنوان وام انتخاب نمایید.");
					return;
				}
				
				me = NewLoanRequestObject;
				mask = new Ext.LoadMask(me.mainPanel, {msg:'در حال ذخيره سازي...'});
				mask.show();  
				me.mainPanel.getForm().submit({
					clientValidation: true,
					url: me.address_prefix + '../../loan/request/request.data.php?task=SaveLoanRequest' , 
					method: "POST",
					params : {
						LoanID : NewLoanRequestObject.grid.getSelectionModel().getLastSelected().data.LoanID
					},
					success : function(form,action){
						mask.hide();
						me = NewLoanRequestObject;
						me.mainPanel.hide();
						me.SendedPanel.getComponent("requestID").update('شماره پیگیری درخواست : ' + action.result.data);
						me.SendedPanel.show();
					},
					failure : function(){
						mask.hide();
						//Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
					}
				});
			}

		}]
	});

	if(this.LoanID > 0)
	{
		this.grid.hide();
		this.store = new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + "../loan/loan.data.php?task=GetAllLoans&LoanID=" + this.LoanID,
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields : ["InstallmentCount","IntervalType","PayInterval","DelayMonths","MaxAmount","ForfeitPercent","CustomerWage"],
			autoLoad : true,
			listeners :{
				load : function(){
					me = RequestInfoObject;
					me.mainPanel.loadRecord(this.getAt(0));
				}
			}
		});
		
		return;
	}

	this.SendedPanel = new Ext.panel.Panel({
		hidden : true,
		renderTo : this.get("SendForm"),
		width : 400,
		style : "margin-top:30px",
		frame : true,
		items : [{
			xtype : "container",
			html : "<br>" + "درخواست شما با موفقیت ثبت گردید" + "<br><br>"
		},{
			xtype : "container",
			cls : "blueText",
			itemId : "requestID"
		},{
			xtype : "container",
			html : "<br>" + "از منوی وام های دریافتی می توانید وضعیت درخواست خود را بررسی کنید" + "<br><br>"
		}]
	});
	
}

NewLoanRequestObject = new NewLoanRequest();

NewLoanRequest.prototype.NewLoanRequest = function()
{
	if(this.get("new_pass").value != this.get("new_pass2").value)
	{
		return;
	}
}

NewLoanRequest.prototype.LoadSummary = function(record){

	function PMT(F8, F9, F7, YearMonths) {  
		F8 = F8/(YearMonths*100);
		F7 = -F7;
		return F8 * F7 * Math.pow((1 + F8), F9) / (1 - Math.pow((1 + F8), F9)); 
	} 
	function ComputeWage(F7, F8, F9, YearMonths){
		
		return (((F7*F8/YearMonths*( Math.pow((1+(F8/YearMonths)),F9)))/
			((Math.pow((1+(F8/YearMonths)),F9))-1))*F9)-F7;
	}
	function roundUp(number, digits)
	{
		var factor = Math.pow(10,digits);
		return Math.ceil(number*factor) / factor;
	}
	
	YearMonths = 12;
	if(record.data.IntervalType == "DAY")
		YearMonths = Math.floor(365/record.data.PayInterval);

	FirstPay = roundUp(PMT(record.data.CustomerWage,record.data.InstallmentCount, record.data.MaxAmount, YearMonths),-3);
	TotalWage = Math.round(ComputeWage(record.data.MaxAmount, record.data.CustomerWage/100, record.data.InstallmentCount, YearMonths));

	TotalDelay = Math.round(record.data.MaxAmount*record.data.CustomerWage*record.data.DelayMonths/1200);
	LastPay = record.data.MaxAmount*1 + TotalWage - FirstPay*(record.data.InstallmentCount-1);
	
	this.get("SUM_InstallmentAmount").innerHTML = Ext.util.Format.Money(FirstPay);
	this.get("SUM_Delay").innerHTML = Ext.util.Format.Money(TotalDelay);
	this.get("SUM_NetAmount").innerHTML = Ext.util.Format.Money(record.data.MaxAmount - TotalDelay);	
	this.get("SUM_TotalWage").innerHTML = Ext.util.Format.Money(TotalWage);	

}

</script>

	<div id="DivGrid"></div>
	<div id="mainForm"></div>
<center>
	<div id="SendForm"></div>
	
	<style>
	.summary {
		border : 1px solid #b5b8c8;
		border-collapse: collapse;
	}
	.summary td{
		border: 1px solid #b5b8c8;
		line-height: 21px;
		direction: ltr;
		text-align: center;
		padding: 0 5px;
	}
	</style>
	<div id="summaryDIV">
		<div style="float:right">
			<table style="width:500px" class="summary">
			<tr>
				<td style="width:25%;background-color: #dfe8f6;">مبلغ هر قسط</td>
				<td style="width:25%;background-color: #dfe8f6;">سود دوره تنفس</td>
				<td style="width:25%;background-color: #dfe8f6;">کارمزد وام</td>
				<td style="width:25%;background-color: #dfe8f6;">خالص پرداختی</td>
			</tr>
			<tr>
				<td><div id="SUM_InstallmentAmount" class="blueText">&nbsp;</div></td>
				<td><div id="SUM_Delay" class="blueText">&nbsp;</div></td>
				<td><div id="SUM_TotalWage" class="blueText">&nbsp;</div></td>
				<td><div id="SUM_NetAmount" class="blueText">&nbsp;</div></td>
			</tr>
		</table></div>		
	</div> 
</center>