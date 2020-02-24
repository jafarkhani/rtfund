<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.08
//-------------------------
require_once('../../header.inc.php');
require_once 'request.data.php';
 
?>
<script type="text/javascript">

DefrayRequest.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function DefrayRequest()
{
	this.PartPanel = new Ext.form.FieldSet({
		title: "انتخاب وام",
		width: 700,
		renderTo : this.get("div_loans"),
		collapsible : true,
		collapsed : false,
		frame: true,
		layout : {
			type : "table",
			columns : 2
		},
		items : [{
			xtype : "combo",
			colspan : 2,
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'request.data.php?task=SelectMyRequests&mode=customer',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['PartAmount',"RequestID","ReqAmount","ReqDate", "RequestID", 
							"CurrentRemain", "TotalRemain","IsEnded",{
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
				'<td style="padding:7px">تاریخ پرداخت</td>',
				'<td style="padding:7px">مانده تا انتها</td>',
				' </tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.ReqAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[MiladiToShamsi(values.ReqDate)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.TotalRemain)]}</td>',
					'</tr>',
				'</tpl>',
				'</table>'
			),
			itemId : "RequestID",
			listeners :{
				select : function(combo,records){
					
					if(records[0].data.IsEnded == "YES")
					{
						Ext.MessageBox.alert("","این وام خاتمه یافته است");
						combo.setValue();
						return;
					}
					if(records[0].data.TotalRemain*1 > 0)
					{
						Ext.MessageBox.confirm("","تنها زمانی قادر به ارسال فرم تسویه می باشید که مبلغ باقیمانده وام را پرداخت نمایید" + 
							"<br>"+"آیا مایل به پرداخت مانده وام می باشید ؟", function(btn){
								if(btn == "no")
									return;
								portal.OpenPage('../loan/request/installments.php',{
									RequestID : records[0].data.RequestID,
									DefrayMode : true
								});
							});
							
						combo.setValue();
						return;
					}
					
					DefrayRequestObject.VoteBtn.enable();
					DefrayRequestObject.WfmBtn.enable();
				}
			}
		}]
	});
	
	this.VoteBtn = new Ext.button.Button({
		renderTo : this.get("btn_vote"),
		text : "تکمیل فرم",
		disabled : true,
		handler : function(){ DefrayRequestObject.FillVote(); }
	});
	
	this.WfmBtn = new Ext.button.Button({
		renderTo : this.get("btn_wfm"),
		disabled : true,
		text : "تکمیل فرم",
		handler : function(){ DefrayRequestObject.OpenWfmForm(); }
	});
}

var DefrayRequestObject = new DefrayRequest();

DefrayRequest.prototype.OpenWfmForm = function(){
	
	mask = new Ext.LoadMask(this.PartPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "CustomerDefrayRequest",
			RequestID : this.PartPanel.down("[itemId=RequestID]").getValue()
		},
		success: function(response){
			mask.hide();
			result = Ext.decode(response.responseText);
			if(!result.success)
				Ext.MessageBox.alert("Error",result.data);
			else 
				DefrayRequestObject.FillWfmForm(result.data);
		}
	});
}

DefrayRequest.prototype.FillVote = function(){
	
	if(!this.FormWin)
	{
		this.FormWin = new Ext.window.Window({
			width : 800,
			title : "تکمیل فرم نظر سنجی",
			height : 500,
			loader : {
				url : "/office/vote/FormInfo.php",
				scripts : true
			},
			autoScroll : true,
			bodyStyle : "background-color:white",
			modal : true,
			closeAction : "hide",
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.FormWin);
	}
	
	this.FormWin.show();
	this.FormWin.center();
	this.FormWin.loader.load({
		params : {
			ExtTabID : this.FormWin.getEl().id,
			parentObj : "DefrayRequestObject",
			FormID : "<?= DEFRAYLOAN_VOTEFORM ?>",
			LoanRequestID : this.PartPanel.down("[itemId=RequestID]").getValue()
		}
	});
}

DefrayRequest.prototype.FillWfmForm = function(WfmReqID){
	
	if(!this.wfmWin)
	{
		this.wfmWin = new Ext.window.Window({
			width : 800,
			title : "تکمیل فرم تسویه",
			height : 500,
			loader : {
				url : "/office/workflow/NewRequest.php",
				scripts : true
			},
			autoScroll : true,
			bodyStyle : "background-color:white",
			modal : true,
			closeAction : "hide",
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.wfmWin);
	}
	
	this.wfmWin.show();
	this.wfmWin.center();
	this.wfmWin.loader.load({
		params : {
			ExtTabID : this.wfmWin.getEl().id,
			FormID : "<?= DEFRAYLOAN_WFMFORM ?>",
			RequestID : WfmReqID,
			LoanRequestID : this.PartPanel.down("[itemId=RequestID]").getValue(),
			parentHandler : function(){
				DefrayRequestObject.wfmWin.hide();
			}
		}
	});
}

</script>
<center>
	<br>
	<div id="div_loans"></div>
	<table  width="600">
		<tr style="height:30px">
			<td width="200" >
				<img style="vertical-align: middle" src="/generalUI/ext4/resources/themes/icons/arrow-left.gif">
				مرحله اول : تکمیل فرم نظرسنجی</td>
			<td><div id="btn_vote"></div></td>
		</tr>
		<tr>
			<td>
				<img style="vertical-align: middle" src="/generalUI/ext4/resources/themes/icons/arrow-left.gif">
				مرحله دوم : تکمیل فرم تسویه حساب وام</td>
			<td><div id="btn_wfm"></div></td>
		</tr>
	</table>
	
</center>