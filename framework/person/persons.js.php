<script type="text/javascript">
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 90.10
//-----------------------------

Person.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Person()
{	
	this.InfoPanel = new Ext.form.FormPanel({
		renderTo : this.get("div_info"),
		frame: true,
		bodyPadding : "10 10 10 10",
		hidden : true,
		title: 'اطلاعات شخصی',
		width: 700,
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {labelWidth : 80},
		items : [{
			xtype :"container",
			layout : "hbox",
			colspan : 2,
			items : [{
				xtype : "radio",
				boxLabel: 'شخص حقیقی',
				name: 'IsReal',
				style : "margin-right : 20px",
				checked : true,
				inputValue: 'YES',
				listeners : {
					change : function(){
						if(this.getValue())
						{
							PersonObject.InfoPanel.getComponent("RealFS").enable();
							PersonObject.InfoPanel.getComponent("NotRealFS").disable();
						}
						else
						{
							PersonObject.InfoPanel.getComponent("RealFS").disable();
							PersonObject.InfoPanel.getComponent("NotRealFS").enable();
						}
					}
				}
			},{
				xtype : "radio",
				boxLabel: 'شخص حقوقی',
				name: 'IsReal',
				inputValue: 'NO'
			}]
		},{
			xtype : "fieldset",
			title : "اطلاعات شخص حقیقی",
			colspan : 2,
			layout : "hbox",
			itemId : "RealFS",
			defaults : {labelWidth : 70},
			items : [{
				xtype : "textfield",
				fieldLabel : "نام",
				name : "fname",
				width : 180
			},{
				xtype : "textfield",
				fieldLabel : "نام خانوادگی",
				name : "lname",
				width : 180
			},{
				xtype : "textfield",
				fieldLabel : "کد ملی",
				regex: /^\d{10}$/,
				maskRe: /[\d\-]/,
				name : "NationalID"
			}]
		},{
			xtype : "fieldset",
			disabled : true,
			defaults : {labelWidth : 70},
			title : "اطلاعات شخص حقوقی",
			colspan : 2,
			layout : "hbox",
			itemId : "NotRealFS",
			items : [{
				xtype : "textfield",
				fieldLabel : "نام شرکت",
				name : "CompanyName",
				width : 360
			},{
				xtype : "textfield",
				fieldLabel : "شناسه ملی",
				regex: /^\d{11}$/,
				maskRe: /[\d\-]/,
				name : "NationalID"
			}]
		},{
			xtype : "textfield",
			vtype : "email",
			fieldLabel: 'پست الکترونیک',
			name: 'email',
			width : 360,
			fieldStyle : "direction:ltr"
		},{
			xtype : "textfield",
			fieldLabel : "کلمه کاربری",
			name : "UserName"
		},{
			xtype : "textfield",
			maskRe: /[\d\-]/,
			fieldLabel: 'تلفن همراه',
			name: 'mobile'
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'persons.data.php?task=selectPosts',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PostID','PostName'],
				autoLoad : true					
			}),
			name : "PostID",
			displayField : "PostName",
			valueField : "PostID",
			queryMode : "local",
			fieldLabel : "پست سازمانی"			
		},{
			xtype : "numberfield",
			name : "ShareNo",
			colspan : 2,
			hideTrigger : true,
			labelWidth : 100,
			width : 235,
			fieldLabel : "شماره دفتر سهام"
		},{
			xtype : "fieldset",
			colspan : 2,
			title : "نوع ذینفع",
			layout : "hbox",
			defaults : {style : "margin-right : 20px"},
			items :[{
				xtype : "checkbox",
                boxLabel: 'پرسنل صندوق',
                name: 'IsStaff',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'مشتری',
                name: 'IsCustomer',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'سهامدار',
                name: 'IsShareholder',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'سرمایه گذار',
                name: 'IsAgent',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'حامی',
                name: 'IsSupporter',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'کارشناس',
                name: 'IsExpert',
                inputValue: 'YES'
			}]
		},{
			xtype : "hidden",
			name : "PersonID"
		}],
		buttons :[{
			text : "ریست تلاش های ناموفق ورود به سیستم",
			disabled : true,
			itemId : "ResetAttemptBTN",
			iconCls : "refresh",
			handler : function(){ PersonObject.ResetAttempt(); }
		},{
			text : "ریست رمز عبور",
			disabled : true,
			itemId : "ResetPassBTN",
			iconCls : "lock",
			handler : function(){ PersonObject.ResetPass(); }
		},{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ PersonObject.saveData(); }
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){ PersonObject.InfoPanel.hide();}
		}]
	});
}

Person.deleteRender = function(v,p,r)
{
	if(r.data.IsActive == "NO")
		return "";
	return "<div align='center' title='حذف کاربر' class='remove' onclick='PersonObject.Deleting();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

Person.editRender = function(v,p,r)
{
	if(r.data.IsActive == "NO")
		return "";
	return "<div align='center' title='ویرایش کاربر' class='edit' onclick='PersonObject.Editing();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

Person.resetPassRender = function(v,p,r)
{
	if(r.data.IsActive == "NO")
		return "";
	return "<div align='center' title='حذف رمز عبور' class='undo' onclick='PersonObject.ResetPass();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

Person.prototype.Adding = function()
{
	this.InfoPanel.getForm().reset();
	this.InfoPanel.down("[itemId=ResetPassBTN]").disable();
	this.InfoPanel.down("[itemId=ResetAttemptBTN]").disable();
	this.InfoPanel.show();	
}

Person.prototype.Editing = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	this.InfoPanel.loadRecord(record);
	this.InfoPanel.down("[itemId=ResetPassBTN]").enable();
	this.InfoPanel.down("[itemId=ResetAttemptBTN]").enable();
	this.InfoPanel.show();	
}

Person.prototype.Deleting = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	
	Ext.MessageBox.confirm("","آيا مايل به حذف مي باشيد؟", function(btn){
		if(btn == "no")
			return;
		
		Ext.Ajax.request({
		  	url : PersonObject.address_prefix + "persons.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeletePerson",
		  		PersonID : record.data.PersonID
		  	},
		  	success : function(response,o)
		  	{
		  		PersonObject.grid.getStore().load();
		  	}
		});
	});
}

Person.prototype.saveData = function()
{
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.InfoPanel.getForm().submit({
		clientValidation: true,
		url : this.address_prefix + 'persons.data.php?task=SavePerson',
		method : "POST",
		
		success : function(form,action){
			mask.hide();
			PersonObject.grid.getStore().load();
			PersonObject.InfoPanel.hide();
		},
		failure : function(form,action){
			if(action.result.data == "")
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد.");
			else
				Ext.MessageBox.alert("",action.result.data);
			mask.hide();
		}
	});
}

Person.prototype.ResetPass = function()
{
	Ext.MessageBox.confirm("","آیا مایل به ریست رمز عبور می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PersonObject;
		
		PersonID = me.InfoPanel.down("[name=PersonID]").getValue();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			params: {
				task: 'ResetPass',
				PersonID : PersonID
			},
			url: me.address_prefix +'persons.data.php',
			method: 'POST',

			success: function(response){
				mask.hide();
				var st = Ext.decode(response.responseText);
				if(st.success)
				{
					Ext.MessageBox.alert("Warning","رمز عبور به 123456 تغییر یافت");
					PersonObject.grid.getStore().load();
				}
				else
				{
					alert(st.data);
				}
			},
			failure: function(){}
		});
		
	});
}

Person.prototype.ResetAttempt = function()
{
	Ext.MessageBox.confirm("","آیا مایل به ریست تلاش های ناموفق ورود به سیستم می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PersonObject;
		
		PersonID = me.InfoPanel.down("[name=PersonID]").getValue();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			params: {
				task: 'ResetAttempt',
				PersonID : PersonID
			},
			url: me.address_prefix +'persons.data.php',
			method: 'POST',

			success: function(response){
				mask.hide();
				var st = Ext.decode(response.responseText);
				if(st.success)
				{
					Ext.MessageBox.alert("Warning","عملیات با موفقیت انجام شد");
					PersonObject.grid.getStore().load();
				}
				else
				{
					alert(st.data);
				}
			},
			failure: function(){}
		});
		
	});
}

</script>
