<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 96.05
//-----------------------------

require_once '../../header.inc.php';

?>
<script>
FRW_ReportDB.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	MenuID : '<?= $_REQUEST["MenuID"] ?>',
	SourceObject : <?= $_REQUEST["SourceObject"] ?>,
	mainForm : "<?= $_REQUEST["mainForm"] ?>",
	formPanel : "<?= $_REQUEST["formPanel"] ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function FRW_ReportDB(){
	
	this.runPanel = new Ext.form.Panel({
		renderTo : this.get("div2"),
		frame : false,
		width : 485,
		border : false,
		bodyStyle : "padding: 0 10px 0 10px",
		items :[{
			xtype : "fieldset",
			layout : "column",
			columns : 2,
			title : "اجرای گزارش",
			items : [{
				xtype : "combo",
				store : new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: this.address_prefix + 'ReportDB.data.php?task=SelectReports&MenuID=' + this.MenuID,
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ["ReportID", "title", "IsManagerDashboard", "IsShareholderDashboard",
						"IsAgentDashboard", "IsSupporterDashboard", "IsCustomerDashboard"],
					autoLoad : true
				}),
				displayField: 'title',
				valueField : "ReportID",
				width : 350,
				queryMode : 'local',
				name : "ReportID",
				allowBlank : false,
				fieldLabel : "انتخاب گزارش",
				listeners : {
					select : function(combo,records)
					{
						this.up('form').down("[name=cmp_IsManagerDashboard]").setDisabled(
							records[0].data.IsManagerDashboard == "YES" ? false : true );
						
						this.up('form').down("[name=cmp_IsShareholderDashboard]").setDisabled(
							records[0].data.IsShareholderDashboard == "YES" ? false : true );
							
						this.up('form').down("[name=cmp_IsAgentDashboard]").setDisabled(
							records[0].data.IsAgentDashboard == "YES" ? false : true );
							
						this.up('form').down("[name=cmp_IsSupporterDashboard]").setDisabled(
							records[0].data.IsSupporterDashboard == "YES" ? false : true );
							
						this.up('form').down("[name=IsCustomerDashboard]").setDisabled(
							records[0].data.IsCustomerDashboard == "YES" ? false : true );	
					}
				}
			},{
				xtype : "button",
				iconCls : "report",
				text : "اجرای گزارش",
				handler : function(){ FRW_ReportDBObj.ShowReport(); }
			},{
				xtype : "container",
				colspan : 2,
				style : "margin-bottom:10px",
				width : 430,
				defaults : {
					xtype : "displayfield",
					fieldCls : "blueText",
					disabled : true,
					style : "margin-left : 10px"
				},
				layout : "hbox",
				items : [{
					
					name : "cmp_IsManagerDashboard",
					value : "داشبورد مدیریت"
				},{
					name : "cmp_IsShareholderDashboard",
					value : "داشبورد سهامدار"
				},{
					name : "cmp_IsAgentDashboard",
					value : "داشبورد سرمایه گذار"
				},{
					name : "cmp_IsSupporterDashboard",
					value : "داشبورد حامی"
				},{
					name : "IsCustomerDashboard",
					value : "داشبورد مشتری"
				}]
			},{
				xtype : "combo",
				itemId : "cmp_Dashboard",
				emptyValue : "انتخاب داشبورد",
				store : new Ext.data.SimpleStore({
					data : [
						["IsManagerDashboard" , "داشبورد مدیریت" ],
						["IsShareholderDashboard" , "داشبورد سهامدار" ],
						["IsAgentDashboard" , "داشبورد سرمایه گذار" ],
						["IsSupporterDashboard" , "داشبورد حامی" ],
						["IsCustomerDashboard" , "داشبورد مشتری" ]
					],
					fields : ['id','value']
				}),
				fieldLabel : "انتخاب داشبورد",
				displayField : "value",
				valueField : "id",
				name : "DashboardType"
			},{
				xtype : "button",
				itemId : "cmp_addDashboard",
				iconCls : "add",
				text : "اضافه",
				handler : function(){ FRW_ReportDBObj.ChangeDashboard('YES'); }
			},{
				xtype : "button",
				itemId : "cmp_RemoveDashboard",
				iconCls : "cross",
				text : "حذف&nbsp;",
				handler : function(){ FRW_ReportDBObj.ChangeDashboard('NO'); }
			}]
		}]
	});
	
	this.addPanel = new Ext.form.Panel({
		renderTo : this.get("div1"),
		frame : false,
		width : 485,
		border : false,
		bodyStyle : "padding: 0 10px 0 10px",
		items :[{
			xtype : "fieldset",
			layout : "hbox",
			title : "ایجاد گزارش",
			items : [{
				xtype : "textfield",
				fieldLabel : "عنوان گزارش",
				width : 350,
				allowBlank : false,
				name : "ReportDBTitle"
			},{
				xtype : "button",
				iconCls : "save",
				text : "ذخیره گزارش",
				handler : function(){ FRW_ReportDBObj.AddReport(); }
			}]
		}]
	});
	
	this.editPanel = new Ext.form.Panel({
		renderTo : this.get("div3"),
		frame : false,
		width : 485,
		border : false,
		bodyStyle : "padding: 0 10px 0 10px",
		items :[{
			xtype : "fieldset",
			title : "تغییر گزارش",
			items : [{
				xtype : "combo",
				store : new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: this.address_prefix + 'ReportDB.data.php?task=SelectReports&MenuID=' + this.MenuID,
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ["ReportID", "title"],
					autoLoad : true
				}),
				displayField: 'title',
				valueField : "ReportID",
				name : "ReportID",
				queryMode : 'local',
				allowBlank : false,
				width : 430,
				fieldLabel : "انتخاب گزارش"
			},{
				xtype : "textfield",
				width : 430,
				allowBlank : false,
				fieldLabel : "عنوان جدید",
				name : "ReportDBTitle"
			},{
				xtype : "button",
				style : "float:left",
				iconCls : "edit",
				text : "ویرایش عنوان گزارش",
				handler : function(){ FRW_ReportDBObj.EditReport(false); }
			},{
				xtype : "button",
				style : "float:left",
				iconCls : "edit",
				text : "ویرایش تنظیمات گزارش",
				handler : function(){ FRW_ReportDBObj.EditReport(true); }
			}]
		}]
	});
	
	this.deletePanel = new Ext.form.Panel({
		renderTo : this.get("div4"),
		frame : false,
		width : 485,
		border : false,
		bodyStyle : "padding: 0 10px 0 10px",
		items :[{
			xtype : "fieldset",
			layout : "hbox",
			title : "حذف گزارش",
			items : [{
				xtype : "combo",
				store : new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: this.address_prefix + 'ReportDB.data.php?task=SelectReports&MenuID=' + this.MenuID,
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ["ReportID", "title"],
					autoLoad : true
				}),
				displayField: 'title',
				valueField : "ReportID",
				width : 350,
				name : "ReportID",
				queryMode : 'local',
				allowBlank : false,
				fieldLabel : "انتخاب گزارش"
			},{
				xtype : "button",
				iconCls : "remove",
				text : "حذف گزارش",
				handler : function(){ FRW_ReportDBObj.DeleteReport(); }
			}]
		}]
	});
}

FRW_ReportDBObj = new FRW_ReportDB();

FRW_ReportDB.prototype.ShowReport = function(){
	
	if(!this.ReportItemsStore)
	{
		this.ReportItemsStore = new Ext.data.SimpleStore({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + 'ReportDB.data.php?task=SelectReportItems',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ["ElemName", "ElemValue"],
			listeners : {
				load : function(){
					
					me = FRW_ReportDBObj;
					for(i=0; i<this.totalCount; i++)
					{
						record = this.getAt(i);
						el = me.SourceObject[me.formPanel].down("[name=" + record.data.ElemName + "]");
						if(!el)
							el = me.SourceObject[me.formPanel].down("[hiddenName=" + record.data.ElemName + "]");
						if(!el)
						{
							el = me.SourceObject.get(record.data.ElemName);
							if(el.type == "checkbox")
								el.checked = record.data.ElemValue == "false" ? false : true;
							else	
								el.value = record.data.ElemValue;
							//------------ radio ---------------
							el = me.SourceObject.get(record.data.ElemName + "-" + record.data.ElemValue)
							if(el)
								el.checked = true;
							//----------------------------------
							continue;
						}
						el.setValue(record.data.ElemValue);
					}
					
					me.SourceObject.ReportWin.hide();
				}
			}
		});
	}
	
	if(!this.runPanel.getForm().isValid())
		return;
	
	this.ReportItemsStore.load({
		params : { ReportID : this.runPanel.down("[name=ReportID]").getValue() }
	});
	
}

FRW_ReportDB.prototype.AddReport = function(){
	
	if(!this.addPanel.getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	params = new Ext.FormSerializer(this.SourceObject.get(this.mainForm)).toObject();
	params = mergeObjects(params, this.addPanel.getForm().getValues());
	params.MenuID = this.MenuID;
	params.EditItems = "YES";
	//--------- multiseects ---------------
	multiElems = this.SourceObject[this.formPanel].query('multiselect');
	for(i=0; i<multiElems.length; i++)
		if(multiElems[i].getValue().toString() != "")
			params[multiElems[i].name] = multiElems[i].getValue().toString();
	//-------------------------------------

	Ext.Ajax.request({
		url: this.address_prefix +'ReportDB.data.php?task=SaveReport',
		method: "POST",
		form : this.get(this.mainForm),
		params: params,
		success: function(response){
			result = Ext.decode(response.responseText);
			mask.hide();
			if(result.success)
				Ext.MessageBox.alert("","گزارش مورد نظر با موفقیت ذخیره شد")
			else
				Ext.MessageBox.alert("ERROR","عملیات مورد نظر با شکست مواجه گردید")
			FRW_ReportDBObj.SourceObject.ReportWin.hide();
		}
	});
	
}

FRW_ReportDB.prototype.EditReport = function(EditAll){
	
	if(!EditAll)
		if(!this.editPanel.getForm().isValid())
			return;
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	if(!EditAll)
	{
		params = {
			ReportID : this.editPanel.down("[name=ReportID]").getValue(),
			MenuID : this.MenuID,
			EditItems : "NO",
			ReportDBTitle : this.editPanel.down("[name=ReportDBTitle]").getValue()
		};
	}
	else
	{
		params = new Ext.FormSerializer(this.SourceObject.get(this.mainForm)).toObject();
		params = mergeObjects(params, this.addPanel.getForm().getValues());
		params.MenuID = this.MenuID;
		params.EditItems = "YES";
		params.ReportID = this.editPanel.down("[name=ReportID]").getValue();
		params.ReportDBTitle = this.editPanel.down("[name=ReportDBTitle]").getValue();
		//--------- multiseects ---------------
		multiElems = this.SourceObject[this.formPanel].query('multiselect');
		for(i=0; i<multiElems.length; i++)
			if(multiElems[i].getValue().toString() != "")
				params[multiElems[i].name] = multiElems[i].getValue().toString();
		//-------------------------------------
	}
	
	Ext.Ajax.request({
		url: this.address_prefix +'ReportDB.data.php?task=SaveReport',
		method: "POST",
		form : this.get(this.mainForm),
		params: params,
		success: function(response){
			mask.hide();
			FRW_ReportDBObj.SourceObject.ReportWin.hide();
		}
	});
	
}

FRW_ReportDB.prototype.ChangeDashboard = function(IsDashboard){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'ReportDB.data.php?task=SaveReport',
		method: "POST",
		params: {
			ReportID : this.runPanel.down("[name=ReportID]").getValue(),
			EditItems : "NO",
			MenuID : this.MenuID,
			DashboardType : this.runPanel.down("[name=DashboardType]").getValue(),
			IsDashboard : IsDashboard
		},
		success: function(response){
			mask.hide();
			Ext.MessageBox.alert("", IsDashboard == "YES" ? "گزارش به داشبورد اضافه شد.":
					"گزارش از داشبورد حذف شد")
		}
	});
	
}

FRW_ReportDB.prototype.DeleteReport = function(){
	
	if(!this.deletePanel.getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'ReportDB.data.php?task=DeleteReport',
		method: "POST",
		form : this.get(this.mainForm),
		params: {
			ReportID : this.deletePanel.down("[name=ReportID]").getValue()
		},
		success: function(response){
			result = Ext.decode(response.responseText);
			mask.hide();
			if(result.success)
				Ext.MessageBox.alert("","گزارش مورد نظر با موفقیت حذف شد")
			else
				Ext.MessageBox.alert("ERROR","عملیات مورد نظر با شکست مواجه گردید")
			
			FRW_ReportDBObj.SourceObject.ReportWin.hide();
		}
	});
	
}

</script>
<div id="div1" ></div>
<div id="div2" ></div>
<div id="div3" ></div>
<div id="div4" ></div>