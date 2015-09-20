<script>
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------

Loan.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function Loan(){
	
	this.groupPnl = new Ext.form.Panel({
		renderTo: this.get("div_selectGroup"),
		title: "انتخاب گروه",
		width: 400,
		collapsible : true,
		collapsed : false,
		frame: true,
		bodyCfg: {style: "background-color:white"},
		items : [{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					proxy: {type: 'jsonp',
						url: this.address_prefix + 'loan.data.php?task=SelectLoanGroups',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					autoLoad : true,
					fields : ['InfoID','InfoDesc']
				}),
				valueField : "InfoID",
				queryMode : "local",
				name : "GroupID",
				displayField : "InfoDesc",
				fieldLabel : "انتخاب گروه"
			},{
				xtype : "fieldset",
				collapsible: true,
				collapsed : true,
				title : "ایجاد گروه جدید",
				width : 350,
				style : "background-color: #F2FCFF",
				items : [{
						xtype : "textfield",
						name : "GroupDesc",
						fieldLabel : "عنوان گروه"
					},{
						xtype : "button",
						text: "ایجاد گروه",
						handler: function(){

							var mask = new Ext.LoadMask(this.up('form'),{msg: 'تغییر اطلاعات ...'});
							mask.show();

							Ext.Ajax.request({
								method : "POST",
								url: LoanObject.address_prefix + "loan.data.php",
								params: {
									task: "AddGroup",
									GroupDesc: this.up('form').down("[name=GroupDesc]").getValue()
								},
								success: function(response){
									mask.hide();
									LoanObject.groupPnl.down("[name=GroupID]").getStore().load({
										callback : function(){
											LoanObject.groupPnl.down("[name=GroupID]").setValue(
												this.getAt(this.getCount()-1));
											LoanObject.LoadLoans();
										}});
									LoanObject.groupPnl.down('fieldset').collapse();
								}
							});
						}
					}]
			}],
		buttons:[{
				text : "حذف گروه",
				iconCls : "remove",
				handler : function(){
					LoanObject.DeleteGroup(this.up('form').down('[name=GroupID]').getValue());
				}
			},{
				text: "لیست وام ها",
				iconCls: "refresh",
				handler: function(){ LoanObject.LoadLoans(); }
			}]
	});	
}

Loan.prototype.LoadLoans = function(){

	LoanObject.GroupID = this.groupPnl.down('[name=GroupID]').getValue();

	LoanObject.grid.getStore().proxy.extraParams.GroupID = LoanObject.GroupID;

	if(LoanObject.grid.rendered)
		LoanObject.grid.getStore().load();
	else
		LoanObject.grid.render(LoanObject.get("grid_div"));
	
	LoanObject.grid.show();
	LoanObject.groupPnl.collapse();
}

Loan.prototype.LoanInfo = function(mode)
{
	if(!this.formPanel)
	{
		this.formPanel = new Ext.form.Panel({
			renderTo: this.get("newDiv"),                  
			collapsible: true,
			frame: true,
			title: 'اطلاعات وام',
			bodyPadding: ' 10 10 12 10',
			width:750,
			layout :{
				type : "table",
				columns :2,
				width:750
			},
			items: [{
					xtype:'textfield',
					fieldLabel: 'عنوان وام',
					name: 'LoanDesc',
					colspan : 2,
					width : 510,
					allowBlank : false	
				},{
					xtype:'currencyfield',
					fieldLabel: 'سقف مبلغ',
					name: 'MaxAmount',
					hideTrigger : true
				},{
					xtype:'numberfield',
					fieldLabel: 'تعداد اقساط',
					name: 'CostusCount',
					hideTrigger : true
				},{
					xtype:'numberfield',
					fieldLabel: 'فاصله اقساط(روز)',
					name: 'CostusInterval',
					hideTrigger : true
				},{
					xtype:'numberfield',
					fieldLabel: 'مدت تنفس (ماه)',
					name: 'DelayCount',
					hideTrigger : true
				},{
					xtype:'currencyfield',
					fieldLabel: 'مبلغ بیمه',
					name: 'InsureAmount',
					hideTrigger : true			
				},{
					xtype:'currencyfield',
					fieldLabel: 'مبلغ قسط اول',
					name: 'FirstCostusAmount',
					hideTrigger : true	
				},{
					xtype:'numberfield',
					fieldLabel: 'درصد سود',
					name: 'ProfitPercent',
					maxValue  : 100,
					hideTrigger : true
				},{
					xtype:'numberfield',
					fieldLabel: 'درصد دیرکرد',
					name: 'ForfeitPercent',
					maxValue  : 100,
					hideTrigger : true
				},{
					xtype:'numberfield',
					fieldLabel: 'درصد کارمزد',
					name: 'FeePercent',
					maxValue  : 100,
					hideTrigger : true
				},{
					xtype:'currencyfield',
					fieldLabel: 'مبلغ کارمزد',
					name: 'FeeAmount',
					hideTrigger : true					
				},{
					xtype : "hidden",
					name : "LoanID"
				}],		
			buttons: [{
					text : "ذخیره",
					iconCls : "save",
					handler : function(){
						LoanObject.formPanel.getForm().submit({
							clientValidation: true,
							url : LoanObject.address_prefix + 'loan.data.php?task=SaveLoan',
							method : "POST",
							params : {
								GroupID : LoanObject.groupPnl.down("[name=GroupID]").getValue()
							},

							success : function(form,action){
								if(action.result.success)
									LoanObject.grid.getStore().load();
								else
									alert("عملیات مورد نظر با شکست مواجه شد.");
								
								LoanObject.formPanel.hide();
							}
						});
					}
				},{
					text : "انصراف",
					iconCls : "undo",
					handler : function(){
						LoanObject.formPanel.hide();
					}
				}]
		});
	}
	
	if(mode == "new")
	{
		this.formPanel.getForm().reset();
	}
	else
	{
		var record = this.grid.getSelectionModel().getLastSelected();
		this.formPanel.getForm().loadRecord(record);
	}
	
	this.formPanel.show();
}

Loan.OperationRender = function(v,p,r)
{
	return "<table width=100%><tr><td><div align='center' title='ویرایش وام' class='edit' "+
		"onclick='LoanObject.LoanInfo(\"edit\");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div></td>" + 
		
		"<td><div align='center' title='حذف وام' class='remove' "+
		"onclick='LoanObject.DeleteLoan();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div></td></tr></table>";
}

Loan.prototype.DeleteGroup = function(GroupID)
{
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LoanObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'loan.data.php',
			params:{
				task: "DeleteGroup",
				GroupID : GroupID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);

				if(sd.success)
				{
					LoanObject.groupPnl.down('[name=GroupID]').setValue();
					LoanObject.groupPnl.down('[name=GroupID]').getStore().load();
					LoanObject.grid.hide();
				}	
				else
				{
					Ext.MessageBox.alert("Error","در این گروه وام تعریف شده و قادر به حذف آن نمی باشید");
				}
			},
			failure: function(){}
		});
	});
}

Loan.prototype.DeleteLoan = function()
{
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LoanObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'loan.data.php',
			params:{
				task: "DeleteLoan",
				LoanID : record.data.LoanID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				LoanObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

</script>