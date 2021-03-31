<?php

require_once '../../header.inc.php';
?>
<script>
	
TestLoan.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function TestLoan(){
	
	this.RequestPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		style : "margin: 10px 0 10px",
		bodyStyle : "text-align:right;padding:5px",
		layout : "hbox",
		defaults : {
			labelWidth :120
		},
		width : 750,
		items :[{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../request/request.data.php?task=SelectAllRequests2',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['loanFullname','PartAmount',"RequestID","PartDate", "ReqDate","RequestID",{
					name : "fullTitle",
					convert : function(value,record){
						return "[ " + record.data.RequestID + " ] " + 
							record.data.loanFullname + "  به مبلغ  " + 
							Ext.util.Format.Money(record.data.PartAmount) + " مورخ " + 
							MiladiToShamsi(record.data.PartDate);
					}
				}]				
			}),
			displayField: 'fullTitle',
			pageSize : 10,
			valueField : "RequestID",
			width : 600,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد وام</td>',
				'<td style="padding:7px">وام گیرنده</td>',
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ پرداخت</td> </tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{loanFullname}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.PartAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.PartDate)]}</td> </tr>',
				'</tpl>',
				'</table>'
			),
			name : "RequestID"
		},{
			xtype : "button",
			border : true,
			text : "بارگذاری اطلاعات وام",
			iconCls : "report",
			handler : function(combo,records){
				
				TestLoanObject.ReqPartStore.load({
					params : {
						RequestID : TestLoanObject.RequestPanel.down("[name=RequestID]").getValue(),
						IsLast : "true"
					},
					callback : function(){
						me = TestLoanObject;
						record = this.getAt(0);
						me.InfoPanel.loadRecord(record);
						me.InfoPanel.down("[name=PartDate]").setValue(MiladiToShamsi(record.data.PartDate));
						me.InfoPanel.down("[name=SourceRequestID]").setValue(record.data.RequestID);						
						me.SavePart();
					}
				});
			}	
		}]
	});
	
	this.ReqPartStore =  new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + '../request/request.data.php?task=GetRequestParts',
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields :  ['RequestID','PartDate','PartAmount',"InstallmentCount","IntervalType", "PayInterval","DelayMonths",
		'DelayDays','ForfeitPercent',"CustomerWage","FundWage", "WageReturn","DelayReturn", "LatePercent",
		'PayCompute','MaxFundWage',"AgentReturn","AgentDelayReturn", "DelayPercent","PayDuration",
		"ComputeMode","BackPayCompute"]
	});
	
	this.InfoPanel = new Ext.form.Panel({
		width : 700,
		frame : true,
		renderTo : this.get("divPanel"),
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {
			xtype : "numberfield",
			hideTrigger : true,
			width : 150,
			labelWidth : 90,
			allowBlank : false
		},				
		items :[{
			xtype : "fieldset",
			colspan : 2,
			style : "margin-right:10px",
			width : 450,
			title : "نحوه محاسبه کارمزد و اقساط",
			items : [{
				xtype : "radio",
				boxLabel : "فرمول جدید بانک مرکزی",
				name : "ComputeMode",
				inputValue : "BANK"
			},{
				xtype : "radio",						
				boxLabel : "فرمول تنزیل هر قسط",
				name : "ComputeMode",
				inputValue : "NEW",
				checked : true
			},{
				xtype : "radio",						
				boxLabel : "فرمول صندوق نوآوری",
				name : "ComputeMode",
				inputValue : "NOAVARI"
			},{
				xtype : "radio",						
				boxLabel : "فرمول درصدی",
				name : "ComputeMode",
				inputValue : "PERCENT"
			}]
		},{
			xtype : "currencyfield",
			name : "PartAmount",
			fieldLabel : "مبلغ پرداخت",
			width : 220
		},{
			xtype : "shdatefield",
			name : "PartDate",
			allowBlank : true,
			hideTrigger : false,
			fieldLabel : "تاریخ پرداخت",
			width : 200
		},{
			xtype : "container",
			layout : "hbox",
			width : 220,
			items : [{
				xtype:'numberfield',
				fieldLabel: 'فاصله اقساط',
				hideTrigger : true,
				allowBlank : false,
				name: 'PayInterval',
				labelWidth: 90,
				width : 150
			},{
				xtype : "radio",
				boxLabel : "ماه",
				inputValue : "MONTH",
				itemId : "monthInterval",
				checked : true,
				name : "IntervalType"
			},{
				xtype : "radio",
				boxLabel : "روز",
				inputValue : "DAY",
				itemId : "dayInterval",
				name : "IntervalType"
			}]
		},{
			xtype : "container",
			width : 220,
			layout : "hbox",
			items : [{
				xtype : "numberfield",
				labelWidth : 90,
				width: 150,
				hideTrigger : true,
				fieldLabel: 'مدت تنفس',
				name: 'DelayMonths',
				afterSubTpl : "ماه"
			},{
				xtype : "numberfield",
				hideTrigger : true,
				width: 50,
				name: 'DelayDays',
				afterSubTpl : "روز"
			}]
		},{
			fieldLabel: 'تعداد اقساط',
			name: 'InstallmentCount',
			width : 200
		},{
			fieldLabel: 'کارمزد مشتری',
			name: 'CustomerWage'	
		},{
			fieldLabel: 'کارمزد تنفس',
			name: 'DelayPercent'
		},{
			fieldLabel: 'کارمزد صندوق',
			name: 'FundWage'
		},{
			fieldLabel: 'کارمزد تاخیر',
			name: 'LatePercent'
		},{
			fieldLabel: 'درصد جریمه',
			name: 'ForfeitPercent'
		},{
			xtype : "fieldset",
			itemId : "fs_WageCompute",
			title : "نحوه دریافت کارمزد صندوق",
			width : 220,
			style : "margin-right:10px",
			items : [{
				xtype : "radio",
				boxLabel : "پرداخت کارمزد طی اقساط",
				name : "WageReturn",
				inputValue : "INSTALLMENT",
				checked : true
			},{
				xtype : "radio",						
				boxLabel : "پرداخت کارمزد هنگام پرداخت وام",
				name : "WageReturn",
				inputValue : "CUSTOMER"
			}]
		},{
			xtype : "fieldset",
			itemId : "fs_DelayCompute",
			title : "نحوه دریافت تنفس صندوق",
			width : 220,
			style : "margin-right:10px",
			items : [{
				xtype : "radio",						
				boxLabel : "هنگام پرداخت وام",
				name : "DelayReturn",
				inputValue : "CUSTOMER",
				checked : true
			},{
				xtype : "radio",
				boxLabel : "طی اقساط",
				name : "DelayReturn",
				inputValue : "INSTALLMENT"
			}]
		},{
			xtype : "fieldset",
			itemId : "fs_AgentWageCompute",
			title : "نحوه دریافت کارمزد سرمایه گذار",
			width : 220,
			style : "margin-right:10px",
			items : [{
				xtype : "radio",
				boxLabel : "پرداخت کارمزد طی اقساط",
				name : "AgentReturn",
				inputValue : "INSTALLMENT",
				checked : true
			},{
				xtype : "radio",						
				boxLabel : "پرداخت کارمزد هنگام پرداخت وام",
				name : "AgentReturn",
				inputValue : "CUSTOMER"
			}]
		},{
			xtype : "fieldset",
			itemId : "fs_AgentDelayCompute",
			title : "نحوه دریافت تنفس سرمایه گذار",
			width : 220,
			style : "margin-right:10px",
			items : [{
				xtype : "radio",						
				boxLabel : "هنگام پرداخت وام",
				name : "AgentDelayReturn",
				inputValue : "CUSTOMER",
				checked : true
			},{
				xtype : "radio",
				boxLabel : "طی اقساط",
				name : "AgentDelayReturn",
				inputValue : "INSTALLMENT"
			}]
		},{
			xtype : "hidden",
			name : "PartID",
			value : 0
		},{
			xtype : "hidden",
			name : "SourceRequestID"
		}],
		buttons : [{
			text : "محاسبه",
			handler : function(){ TestLoanObject.SavePart();}
		}]				
	});
	
	this.PartStore = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + "../request/request.data.php?task=GetRequestParts&RequestID=0",
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["PartAmount","AllPay","LastPay","AgentDelay","FundDelay",
					"TotalCustomerWage","TotalAgentWage","TotalFundWage","SUM_NetAmount",
					"WageReturn","DelayReturn","AgentReturn","AgentDelayReturn"],
		listeners :{
			load : function(){
				me = TestLoanObject;
				record = this.getAt(0);
				
				me.get("SUM_InstallmentAmount").innerHTML = Ext.util.Format.Money(record.data.AllPay);
				me.get("SUM_LastInstallmentAmount").innerHTML = Ext.util.Format.Money(record.data.LastPay);
				me.get("SUM_FundDelay").innerHTML = Ext.util.Format.Money(record.data.FundDelay);
				me.get("SUM_AgentDelay").innerHTML = Ext.util.Format.Money(record.data.AgentDelay);
				me.get("SUM_TotalWage").innerHTML = Ext.util.Format.Money(record.data.TotalCustomerWage);
				me.get("SUM_FundWage").innerHTML = Ext.util.Format.Money(record.data.TotalFundWage);
				me.get("SUM_AgentWage").innerHTML = Ext.util.Format.Money(record.data.TotalAgentWage);
				me.get("SUM_NetAmount").innerHTML = Ext.util.Format.Money(record.data.SUM_NetAmount);
			}
		}
	});
} 

TestLoanObject = new TestLoan();

TestLoan.prototype.SavePart = function(){

	mask = new Ext.LoadMask(this.InfoPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.InfoPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix +'../request/request.data.php',
		method: "POST",
		params: {
			task: "SavePart",
			RequestID : 0
		},
		success: function(form,action){
			mask.hide();
			TestLoanObject.PartStore.load();
		},
		failure: function(form,action){
			mask.hide();
		}
	});
}

</script>
<style>
	.summary {
		border : 1px solid #b5b8c8;
		border-collapse: collapse;
	}
	.summary td{
		border: 1px solid #b5b8c8;
		line-height: 21px;
		direction: ltr;
		padding: 0 5px;
	}
</style>
<center>
	<div id="main"></div>
	<div id="divPanel"></div>
	<div id="divPanel2"></div>
	<div id="summaryDIV">
		<table style="width:700px" class="summary">
			<tr>
				<td style="width:25%;background-color: #dfe8f6;">مبلغ هر قسط</td>
				<td style="width:25%;background-color: #dfe8f6;">  سود تنفس صندوق</td>
				<td style="width:25%;direction:rtl;background-color: #dfe8f6;">کارمزد وام</td>
				<td style="width:25%;"><div id="SUM_TotalWage" class="blueText">&nbsp;</div></td>
			</tr>
			<tr>
				<td><div id="SUM_InstallmentAmount" class="blueText">&nbsp;</div></td>
				<td><div id="SUM_FundDelay" class="blueText">&nbsp;</div></td>
				<td style="direction:rtl;background-color: #dfe8f6;">سهم صندوق</td>
				<td><div id="SUM_FundWage" class="blueText">&nbsp;</div></td>
			</tr>
			<tr>
				<td style="background-color: #dfe8f6;">مبلغ قسط آخر</td>
				<td style="background-color: #dfe8f6;">تنفس سرمایه گذار</td>
				<td style="direction:rtl;background-color: #dfe8f6;">سهم سرمایه گذار</td>
				<td><div id="SUM_AgentWage" class="blueText">&nbsp;</div></td>
			</tr>
			<tr>
				<td><div id="SUM_LastInstallmentAmount" class="blueText">&nbsp;</div></td>
				<td><div id="SUM_AgentDelay" class="blueText">&nbsp;</div></td>
				<td style="background-color: #dfe8f6;">خالص پرداختی</td>
				<td><div id="SUM_NetAmount" class="blueText">&nbsp;</div></td>
			</tr>			
		</table>
	</div> 
	<br>
</center>