<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

WarrentyRequest.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function WarrentyRequest(){
	
	this.FilterObj = Ext.button.Button({
		text: 'فیلتر لیست',
		iconCls: 'list',
		menu: {
			xtype: 'menu',
			plain: true,
			showSeparator : true,
			items: [{
				text: "کلیه ضمانتنامه ها",
				checked: true,
				group: 'filter',
				handler : function(){
					me = WarrentyRequestObject;
					me.grid.getStore().proxy.extraParams["IsEnded"] = "";
					me.grid.getStore().loadPage(1);
				}
			},{
				text: "ضمانتنامه های جاری",
				group: 'filter',
				checked: true,
				handler : function(){
					me = WarrentyRequestObject;
					me.grid.getStore().proxy.extraParams["IsEnded"] = "NO";
					me.grid.getStore().loadPage(1);
				}
			},{
				text: "ضمانتنامه های خاتمه یافته",
				group: 'filter',
				checked: true,
				handler : function(){
					me = WarrentyRequestObject;
					me.grid.getStore().proxy.extraParams["IsEnded"] = "YES";
					me.grid.getStore().loadPage(1);
				}
			}]
		}
	});
	
	this.MainPanel = new Ext.form.Panel({
		width : 650,
		hidden : true,
		layout : {
			type : "table",
			columns : 2
		},		
		applyTo : this.get("RequestInfo"),
		defaults : {
			width : 300
		},
		frame : true,
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/baseInfo/baseInfo.data.php?' +
						"task=SelectBranches&WarrentyAllowed=true",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه",
			queryMode : 'local',
			allowBlank : false,
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID",
			colspan : 2
		},{
			xtype : "combo",
			store : new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'request.data.php?task=GetWarrentyTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ["InfoID", "InfoDesc"],
				autoLoad : true
			}),
			displayField: 'InfoDesc',
			valueField : "InfoID",
			name : "TypeID",
			colspan : 2,
			allowBlank : false,
			fieldLabel : "نوع درخواست"
		},{
			xtype : "textfield",
			fieldLabel : "شماره قرارداد",
			name : "SubjectNO"
		},{
			xtype : "numberfield",
			hideTrigger : true,
			fieldLabel : "کد سپاص",
			name : "SepasCode"			
		},{
			xtype : "textfield",
			fieldLabel : "موضوع قرارداد",
			width : 600,
			colspan : 2,
			name : "SubjectDesc"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserType=IsCustomer",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "مشتری",
			displayField : "fullname",
			pageSize : 20,
			width : 600,
			colspan : 2,
			allowBlank : false,
			valueField : "PersonID",
			name : "PersonID"
		},{
			xtype : "textfield",
			name : "organization",
			allowBlank : false,
			width : 600,
			colspan : 2,
			fieldLabel : "سازمان مربوطه"
		},{
			xtype : "currencyfield",
			name : "amount",
			colspan : 2,
			hideTrigger : true,
			allowBlank : false,
			fieldLabel : "مبلغ ضمانت نامه"
		},{
			xtype : "shdatefield",
			name : "StartDate",
			allowBlank : false,
			fieldLabel : "تاریخ شروع"
		},{
			xtype : "shdatefield",
			name : "EndDate",
			allowBlank : false,
			fieldLabel : "تاریخ پایان"
		},{
			xtype : "textfield",
			allowBlank : false,
			fieldLabel : "شماره نامه معرفی",
			name : "LetterNo"
		},{
			xtype : "shdatefield",
			allowBlank : false,
			fieldLabel : "تاریخ نامه معرفی",
			name : "LetterDate"
		},{
			xtype : "numberfield",
			allowBlank : false,
			fieldLabel : "کارمزد",
			name : "wage",
			width : 150,
			afterSubTpl : "%",
			hideTrigger : true
		},{
			xtype : "currencyfield",
			name : "RegisterAmount",
			hideTrigger : true,
			fieldLabel : "کارمزد صدور"
		},{
			xtype : "numberfield",
			allowBlank : false,
			fieldLabel : "درصد سپرده",
			name : "SavePercent",
			width : 150,
			afterSubTpl : "%",
			hideTrigger : true
		},{
			xtype : "checkbox",
			boxLabel : "مبلغ ضمانت نامه از حساب سپرده فرد بلوکه شود",
			name : "IsBlock",
			inputValue : 'YES'
		},{
			xtype : "hidden",
			name : "RequestID"
		}],
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			itemId : "btn_save",
			handler : function(){ WarrentyRequestObject.SaveRequest(); }
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){ this.up('panel').hide(); }
		}]
	});
	
	
}

WarrentyRequest.OrgRender = function(v,p,r){
	
	str = "شماره نامه معرفی : <b>" + r.data.LetterNo + "</b>"+
		"<br>تاریخ نامه معرفی : <b>" + MiladiToShamsi(r.data.LetterDate) + "</b>";
	p.tdAttr = "data-qtip='" + str + "'";
	return v;
}

WarrentyRequest.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='WarrentyRequestObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

WarrentyRequestObject = new WarrentyRequest();

WarrentyRequest.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	if(record.data.StatusID == "<?= WAR_STEPID_RAW ?>" || record.data.SendEnable == "YES")
	{
		if(this.EditAccess)
		{
			op_menu.add({text: 'ارسال ضمانت نامه',iconCls: 'refresh',
			handler : function(){ return WarrentyRequestObject.StartFlow(); }});
		
			op_menu.add({text: 'ویرایش ضمانت نامه',iconCls: 'edit', 
			handler : function(){ return WarrentyRequestObject.editRequest(); }});
		}
		if(this.RemoveAccess)
			op_menu.add({text: 'حذف ضمانت نامه',iconCls: 'remove', 
			handler : function(){ return WarrentyRequestObject.deleteRequest(); }});
	}
	else
	{
		op_menu.add({text: 'اطلاعات ضمانت نامه',iconCls: 'info', 
			handler : function(){ return WarrentyRequestObject.InfoRequest(); }});
	}
	
	if(record.data.StatusID == "0")
	{
		op_menu.add({text: 'برگشت فرم',iconCls: 'return',
		handler : function(){ return WarrentyRequestObject.ReturnStartFlow(); }});
	}
	
	if(this.EditAccess && record.data.StatusID == "<?= WAR_STEPID_CONFIRM ?>")
	{
		//if(record.data.DocID == "" || record.data.DocID == null)
			op_menu.add({text: 'صدور سند',iconCls: 'send',
			handler : function(){ return WarrentyRequestObject.ExecuteRegEvent();/*BeforeRegDoc(1);*/ }});
	
		if(record.data.IsCurrent == "YES")
		{
			op_menu.add({text: 'خاتمه ضمانت نامه',iconCls: 'finish',
				handler : function(){ return WarrentyRequestObject.ExecuteEndEvent(); }})

			op_menu.add({text: 'ابطال ضمانت نامه',iconCls: 'cross',
				handler : function(){ return WarrentyRequestObject.BeforeCancelWarrenty(); }})

			op_menu.add({text: 'تمدید ضمانت نامه',iconCls: 'delay',
				handler : function(){ return WarrentyRequestObject.BeforeExtendWarrenty(); }})
			
			op_menu.add({text: 'تقلیل ضمانت نامه',iconCls: 'arrow_down',
				handler : function(){ return WarrentyRequestObject.BeforeReduceWarrenty(); }})
		}
	}
	if(this.EditAccess && record.data.StatusID == "<?= WAR_STEPID_CANCEL ?>" && record.data.IsCurrent == "YES")
	{
		op_menu.add({text: 'برگشت از ابطال',iconCls: 'undo',
				handler : function(){ return WarrentyRequestObject.ReturnCancel(); }})
	}
	
	op_menu.add({text: 'چاپ ضمانت نامه',iconCls: 'print',
			handler : function(){ return WarrentyRequestObject.Print(); }});
	
	op_menu.add({text: 'ضامنین/وثیقه گذاران',iconCls: 'list', 
		handler : function(){ return WarrentyRequestObject.LoanGuarantors(); }});
	
	op_menu.add({text: 'هزینه ها',iconCls: 'account', 
		handler : function(){ return WarrentyRequestObject.ShowCosts(); }});
	
	op_menu.add({text: 'چک لیست',iconCls: 'check', 
		handler : function(){ return WarrentyRequestObject.ShowCheckList(); }});
	
	op_menu.add({text: 'مدارک ضمانت نامه',iconCls: 'attach', 
		handler : function(){ return WarrentyRequestObject.WarrentyDocuments('warrenty'); }});

	op_menu.add({text: 'مدارک تضمین خواه',iconCls: 'attach', 
		handler : function(){ return WarrentyRequestObject.WarrentyDocuments('person'); }});
	
	op_menu.add({text: 'سابقه درخواست',iconCls: 'history', 
		handler : function(){ return WarrentyRequestObject.ShowHistory(); }});
	
	op_menu.add({text: 'چاپ رسید تضامین',iconCls: 'print', 
		handler : function(){ 
			me = WarrentyRequestObject;
			var record = me.grid.getSelectionModel().getLastSelected();
			window.open(me.address_prefix + "PrintDocs.php?RequestID=" + record.data.RefRequestID);
	}});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

WarrentyRequest.prototype.AddNew = function(){
	
	this.MainPanel.show();
	this.MainPanel.setReadOnly(false);
	
	this.MainPanel.getForm().reset();
}

WarrentyRequest.prototype.editRequest = function(){
	
	this.MainPanel.down("[itemId=btn_save]").show();
	this.MainPanel.setReadOnly(false);
	record = this.grid.getSelectionModel().getLastSelected();
	this.MainPanel.show();
	mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	this.MainPanel.loadRecord(record);
	
	this.MainPanel.down("[name=StartDate]").setValue(MiladiToShamsi(record.data.StartDate) );
	this.MainPanel.down("[name=EndDate]").setValue(MiladiToShamsi(record.data.EndDate) );
	this.MainPanel.down("[name=LetterDate]").setValue(MiladiToShamsi(record.data.LetterDate) );
	
	this.MainPanel.down("[name=PersonID]").getStore().load({
		params : {
			PersonID : record.data.PersonID
		},
		callback : function(){
			mask.hide();	
			if(this.getCount() > 0)
				WarrentyRequestObject.MainPanel.down("[name=PersonID]").setValue(this.getAt(0).data.PersonID);
		}
	});
	
	if(record.data.RequestID != record.data.RefRequestID)
	{
		this.MainPanel.down("[name=BranchID]").disable();
		this.MainPanel.down("[name=PersonID]").disable();
		this.MainPanel.down("[name=TypeID]").disable();
		this.MainPanel.down("[name=organization]").disable();
		this.MainPanel.down("[name=amount]").disable();
		this.MainPanel.down("[name=IsBlock]").disable();
	}
	else
	{
		this.MainPanel.down("[name=BranchID]").enable();
		this.MainPanel.down("[name=PersonID]").enable();
		this.MainPanel.down("[name=TypeID]").enable();
		this.MainPanel.down("[name=organization]").enable();
		this.MainPanel.down("[name=amount]").enable();
		this.MainPanel.down("[name=IsBlock]").enable();
	}
}

WarrentyRequest.prototype.InfoRequest = function(){
	
	record = this.grid.getSelectionModel().getLastSelected();
	this.MainPanel.show();
	mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	this.MainPanel.loadRecord(record);
	
	this.MainPanel.down("[name=StartDate]").setValue(MiladiToShamsi(record.data.StartDate) );
	this.MainPanel.down("[name=EndDate]").setValue(MiladiToShamsi(record.data.EndDate) );
	this.MainPanel.down("[name=LetterDate]").setValue(MiladiToShamsi(record.data.LetterDate) );
	
	this.MainPanel.down("[name=PersonID]").getStore().load({
		params : {
			PersonID : record.data.PersonID
		},
		callback : function(){
			mask.hide();	
			if(this.getCount() > 0)
				WarrentyRequestObject.MainPanel.down("[name=PersonID]").setValue(this.getAt(0).data.PersonID);
		}
	});
	
	this.MainPanel.down("[itemId=btn_save]").hide();
	this.MainPanel.setReadOnly(true);
}

WarrentyRequest.prototype.SaveRequest = function(){
	
	if(!this.MainPanel.getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.MainPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "SaveWarrentyRequest"
		},
		success: function(form,action){
			mask.hide();
			WarrentyRequestObject.grid.getStore().load();
			WarrentyRequestObject.MainPanel.hide();
		},
		failure: function(){
			mask.hide();
		}
	});
}

WarrentyRequest.prototype.WarrentyDocuments = function(ObjectType){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 920,
			height : 440, 
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : "../../office/dms/documents.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.documentWin);
	}

	this.documentWin.show();
	this.documentWin.center();
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.documentWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.documentWin.getEl().id,
			ObjectType : ObjectType,
			ObjectID : record.data.RefRequestID
		}
	});
}

WarrentyRequest.prototype.StartFlow = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به ارسال ضمانت نامه می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = WarrentyRequestObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "StartWarrentyFlow",
				RequestID : record.data.RequestID
			},
			success: function(response){
				mask.hide();
				WarrentyRequestObject.grid.getStore().load();
			}
		});
	});
}

WarrentyRequest.prototype.ReturnStartFlow = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به برگشت فرم می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = WarrentyRequestObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: '/office/workflow/wfm.data.php',
			method: "POST",
			params: {
				task: "ReturnStartFlow",
				FlowID : <?= FLOWID_WARRENTY ?>,
				ObjectID : record.data.RequestID
			},
			success: function(response){
				mask.hide();
				WarrentyRequestObject.grid.getStore().load();
			}
		});
	});
}

WarrentyRequest.prototype.ShowHistory = function(){

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
	this.HistoryWin.loader.load({
		params : {
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		}
	});
}

WarrentyRequest.prototype.deleteRequest = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف درخواست می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = WarrentyRequestObject;
		record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "request.data.php",
			params : {
				task : "DeleteWarrentyRequest",
				RequestID : record.data.RequestID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
				{
					WarrentyRequestObject.grid.getStore().load();
					if(WarrentyRequestObject.commentWin)
						WarrentyRequestObject.commentWin.hide();
				}
				else
					Ext.MessageBox.alert("Error",result.data);
			}
		});
	});
}

WarrentyRequest.prototype.WarrentyPeriods = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
	{
		Ext.MessageBox.alert("","ابتدا شرایط مورد نظر خود را انتخاب کنید");
		return;
	}
	
	if(!this.PeriodWin)
	{
		this.PeriodWin = new Ext.window.Window({
			width : 600,
			title : "دوره های زمانی ضمانت نامه",
			height : 300,
			modal : true,
			loader : {
				url : this.address_prefix + "periods.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide",
			buttons : [{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){ this.up('window').hide(); }
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.PeriodWin);
	}
	this.PeriodWin.show();
	this.PeriodWin.center();
	
	this.PeriodWin.loader.load({
		params : {
			ExtTabID : this.PeriodWin.getEl().id,
			RequestID : record.data.RequestID
		}
	});
}

WarrentyRequest.prototype.BeforeRegDoc = function(mode){
	
	if(!this.BankWin)
	{
		this.BankWin = new Ext.window.Window({
			width : 400,
			height : 180,
			bodyStyle : "background-color:white",
			title : "نحوه پرداخت کارمزد",
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "combo",
				width : 385,
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
				fieldLabel : "حساب مربوطه",
				valueField : "CostID",
				itemId : "CostID",
				displayField : "CostDesc",
				listeners : {
					select : function(combo,records){
						me = WarrentyRequestObject;
						me.BankWin.down("[itemId=TafsiliID]").setValue();
						me.BankWin.down("[itemId=TafsiliID]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType1;
						me.BankWin.down("[itemId=TafsiliID]").getStore().load();

						me.BankWin.down("[itemId=TafsiliID2]").setValue();
						me.BankWin.down("[itemId=TafsiliID2]").getStore().proxy.extraParams.TafsiliType = records[0].data.TafsiliType2;
						me.BankWin.down("[itemId=TafsiliID2]").getStore().load();

						if(this.getValue() == "<?= COSTID_Bank ?>")
						{
							me.BankWin.down("[itemId=TafsiliID]").setValue(
								"<?= isset($_SESSION["accounting"]) ? $_SESSION["accounting"]["DefaultBankTafsiliID"] : "" ?>");
							me.BankWin.down("[itemId=TafsiliID2]").setValue(
								"<?= isset($_SESSION["accounting"]) ? $_SESSION["accounting"]["DefaultAccountTafsiliID"] : "" ?>");
						}
							
					}
				}
			},{
				xtype : "combo",
				store: new Ext.data.Store({
					fields:["TafsiliID","TafsiliDesc"],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				fieldLabel : "تفصیلی",
				width : 385,
				typeAhead: false,
				pageSize : 10,
				valueField : "TafsiliID",
				itemId : "TafsiliID",
				displayField : "TafsiliDesc",
				listeners : { 
					change : function(){
						t1 = this.getStore().proxy.extraParams["TafsiliType"];
						combo = WarrentyRequestObject.BankWin.down("[itemId=TafsiliID2]");

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
					fields:["TafsiliID","TafsiliDesc"],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				fieldLabel : "تفصیلی2",
				width : 385,
				typeAhead: false,
				pageSize : 10,
				valueField : "TafsiliID",
				itemId : "TafsiliID2",
				displayField : "TafsiliDesc"
			},{
				xtype : "container",
				html : "<hr>"
			},{
				xtype : "combo",
				store: new Ext.data.Store({
					fields:["CostID","CostCode", "CostDesc"],
					proxy: {
						type: 'jsonp',
						url: '/accounting/baseinfo/baseinfo.data.php?task=SelectBlockableCostCode',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				tpl: new Ext.XTemplate(
					'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct">',
					'<td height="23px">کد حساب</td>',
					'<td>عنوان حساب</td></tr>',
					'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0;">',
					'<td style="border-left:0;border-right:0" class="search-item">{CostCode}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{CostDesc}</td></tr>',
					'</tpl>',
					'</table>'),
				emptyText:'حساب مورد نظر جهت بلوکه ...',
				width : 385,
				typeAhead: false,
				pageSize : 10,
				valueField : "CostID",
				itemId : "Block_CostID",
				displayField : "CostCode"
			}],
			buttons :[{
				text : "ذخیره",
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){
					WarrentyPeriodObject.RegWarrentyDoc();
				}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.BankWin);
	}
	
	this.BankWin.show();
	//..........................................
	var record = this.grid.getSelectionModel().getLastSelected();
	if(record.data.IsBlock == "YES")
		this.BankWin.down("[itemId=Block_CostID]").show();
	else
		this.BankWin.down("[itemId=Block_CostID]").hide();
	//..........................................	
	this.BankWin.down("[itemId=btn_save]").setHandler(function(){
		WarrentyRequestObject.RegWarrentyDoc(mode == "1" ? "RegWarrentyDoc" : "editWarrentyDoc");
	});
}

WarrentyRequest.prototype.RegWarrentyDoc = function(task){
	
	var record = this.grid.getSelectionModel().getLastSelected();

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: task,
			RequestID : record.data.RequestID,
			CostID : this.BankWin.down("[itemId=CostID]").getValue(),
			TafsiliID : this.BankWin.down("[itemId=TafsiliID]").getValue(),
			TafsiliID2 : this.BankWin.down("[itemId=TafsiliID2]").getValue(),
			Block_CostID : this.BankWin.down("[itemId=Block_CostID]").getValue()
		},
		success: function(response){

			result = Ext.decode(response.responseText);
			mask.hide();
			if(!result.success)
				Ext.MessageBox.alert("Error", result.data);
			
			WarrentyRequestObject.BankWin.hide();
			WarrentyRequestObject.grid.getStore().load();
		}
	});				
}

WarrentyRequest.prototype.ReturnWarrentyDoc = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به برگشت سند می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = WarrentyRequestObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "ReturnWarrentyDoc",
				RequestID : record.data.RequestID
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
				WarrentyRequestObject.grid.getStore().load();
			}
		});
	});
}

WarrentyRequest.prototype.EndWarrentyDoc = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به صدور سند خاتمه ضمانت نامه می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = WarrentyRequestObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "EndWarrentyDoc",
				RequestID : record.data.RequestID
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
				WarrentyRequestObject.grid.getStore().load();
			}
		});
	});
}

WarrentyRequest.prototype.CancelWarrentyDoc = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();

	var mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.CancelWin.down('form').getForm().submit({

		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "CancelWarrentyDoc",
			RequestID : record.data.RequestID
		},
		success: function(){
			mask.hide();
			WarrentyRequestObject.CancelWin.hide();
			WarrentyRequestObject.grid.getStore().load();
		},
		failure : function(form, action){
			mask.hide();
			Ext.MessageBox.alert("",action.result.data);
		}
	});
}

WarrentyRequest.prototype.ReturnCancel = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به برگشت سند می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = WarrentyRequestObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "ReturnCancel",
				RequestID : record.data.RequestID
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
				WarrentyRequestObject.grid.getStore().load();
			}
		});
	});
}

WarrentyRequest.prototype.BeforeExtendWarrentyDoc = function(){

	if(!this.ExtendWin)
	{
		this.ExtendWin = new Ext.window.Window({
			width : 300,
			height : 200,
			items : new Ext.form.Panel({
				width : 290,
				items : [{
					xtype : "currencyfield",
					name : "amount",
					allowBlank : false,
					hideTrigger : true,
					fieldLabel : "مبلغ ضمانت نامه"
				},{
					xtype : "shdatefield",
					name : "EndDate",
					allowBlank : false,
					fieldLabel : "تاریخ پایان"
				},{
					xtype : "numberfield",
					allowBlank : false,
					fieldLabel : "کارمزد",
					name : "wage",
					width : 150,
					afterSubTpl : "%",
					hideTrigger : true
				},{
					xtype : "currencyfield",
					name : "RegisterAmount",
					hideTrigger : true,
					fieldLabel : "کارمزد صدور"
				}],
				buttons :[{
					text : "تمدید",
					iconCls : "delay",
					handler : function(){WarrentyRequestObject.ExtendWarrentyDoc();}
				},{
					text : "انصراف",
					iconCls : "undo",
					handler : function(){ this.up('window').hide(); }
				}]
			})
		});
	}
	this.ExtendWin.show();
	this.ExtendWin.center();
}

//.......................................

WarrentyRequest.prototype.BeforeExtendWarrenty = function(){

	if(!this.ExtendWin)
	{
		this.ExtendWin = new Ext.window.Window({
			width : 300,
			height : 200,
			closeAction : "hide",
			items : new Ext.form.Panel({
				width : 290,
				items : [{
					xtype : "currencyfield",
					name : "amount",
					allowBlank : false,
					hideTrigger : true,
					fieldLabel : "مبلغ ضمانت نامه"
				},{
					xtype : "shdatefield",
					name : "EndDate",
					allowBlank : false,
					fieldLabel : "تاریخ پایان"
				},{
					xtype : "numberfield",
					allowBlank : false,
					fieldLabel : "کارمزد",
					name : "wage",
					width : 150,
					afterSubTpl : "%",
					hideTrigger : true
				},{
					xtype : "currencyfield",
					name : "RegisterAmount",
					hideTrigger : true,
					fieldLabel : "کارمزد صدور"
				}],
				buttons :[{
					text : "تمدید",
					iconCls : "delay",
					handler : function(){WarrentyRequestObject.ExtendWarrenty();}
				},{
					text : "انصراف",
					iconCls : "undo",
					handler : function(){ this.up('window').hide(); }
				}]
			})
		});
	}
	this.ExtendWin.show();
	this.ExtendWin.center();
}

WarrentyRequest.prototype.ExtendWarrenty = function(){

	var record = this.grid.getSelectionModel().getLastSelected();

	mask = new Ext.LoadMask(this.ExtendWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.ExtendWin.down('form').getForm().submit({
		
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "ExtendWarrenty",
			RequestID : record.data.RequestID
		},
		success: function(){

			mask.hide();
			WarrentyRequestObject.ExtendWin.hide();
			WarrentyRequestObject.grid.getStore().load();
		},
		failure : function(){
			mask.hide();
			Ext.MessageBox.alert("ERROR", "عملیات مورد نظر با شکست مواجه گردید");
		}
	});

}

WarrentyRequest.prototype.Print = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	window.open(this.address_prefix + "PrintWarrenty.php?RequestID=" + record.data.RequestID);
}

WarrentyRequest.prototype.ShowCosts = function(){

	if(!this.CostsWin)
	{
		this.CostsWin = new Ext.window.Window({
			title: 'هزینه های ضمانت نامه',
			modal : true,
			autoScroll : true,
			width: 600,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "costs.php",
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
		Ext.getCmp(this.TabID).add(this.CostsWin);
	}
	var record = this.grid.getSelectionModel().getLastSelected();
	this.CostsWin.show();
	this.CostsWin.center();	
	this.CostsWin.loader.load({
		params : {
			ExtTabID : this.CostsWin.getEl().id,
			RequestID : record.data.RequestID
		}
	});
}

WarrentyRequest.prototype.ShowCheckList = function(){

	if(!this.CheckListWin)
	{
		this.CheckListWin = new Ext.window.Window({
			title: 'چک لیست',
			modal : true,
			autoScroll : true,
			width: 600,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : "baseInfo/checkValues.php",
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
		Ext.getCmp(this.TabID).add(this.CheckListWin);
	}
	this.CheckListWin.show();
	this.CheckListWin.center();	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.CheckListWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.CheckListWin.getEl().id,
			SourceID : record.data.RequestID,
			SourceType : <?= SOURCETYPE_WARRENTY ?>
		}
	});
}

WarrentyRequest.prototype.LoanGuarantors = function(){

	if(!this.GuarantorWin)
	{
		this.GuarantorWin = new Ext.window.Window({
			width : 750,
			height : 600,
			autoScroll : true,
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "guarantors.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.GuarantorWin);
	}

	this.GuarantorWin.show();
	this.GuarantorWin.center();
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.GuarantorWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.GuarantorWin.getEl().id,
			RequestID : record.data.RequestID
		}
	});
}

//.........................................................

WarrentyRequest.prototype.BeforeCancelWarrenty = function(){

	if(!this.CancelWin)
	{
		this.CancelWin = new Ext.window.Window({
			width : 400,
			height : 120,
			closeAction : "hide",
			items : new Ext.form.Panel({
				items : [{
					xtype : "numberfield",
					name : "extradays",
					labelWidth : 200,
					hideTrigger : true,
					fieldLabel : "تعداد روز مازاد کارمزد",
					allowBlank : false,
					value : 30
				},{
					xtype : "shdatefield",
					name : "CancelDate",
					labelWidth : 200,
					allowBlank : false,
					fieldLabel : "تاریخ ابطال ضمانت نامه"
				}]
			}),
			buttons :[{
				text : "ابطال",
				iconCls : "cross",
				handler : function(){WarrentyRequestObject.ExecuteCancelEvent();}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){ this.up('window').hide(); }
			}]
		});
	}
	this.CancelWin.show();
	this.CancelWin.center();
}

WarrentyRequest.prototype.BeforeReduceWarrenty = function(){

	if(!this.ReduceWin)
	{
		this.ReduceWin = new Ext.window.Window({
			width : 400,
			height : 120,
			closeAction : "hide",
			items : new Ext.form.Panel({
				items : [{
					xtype : "currencyfield",
					name : "newAmount",
					labelWidth : 200,
					hideTrigger : true,
					fieldLabel : "مبلغ جدید ضمانتنامه",
					allowBlank : false
				},{
					xtype : "shdatefield",
					name : "ReduceDate",
					labelWidth : 200,
					allowBlank : false,
					fieldLabel : "تاریخ تقلیل ضمانت نامه"
				}]
			}),
			buttons :[{
				text : "تقلیل",
				iconCls : "cross",
				handler : function(){WarrentyRequestObject.ExecuteReduceEvent();}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){ this.up('window').hide(); }
			}]
		});
	}
	this.ReduceWin.show();
	this.ReduceWin.center();
}

WarrentyRequest.prototype.ExecuteRegEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	if(record.data.RefRequestID != record.data.RequestID)
		return this.ExecuteExtendEvent();
	
	switch(record.data.TypeID)
	{
		case "2" : eventID= "<?= EVENT_WAR_REG_2 ?>";break;
		case "3" : eventID= "<?= EVENT_WAR_REG_3 ?>";break;
		case "4" : eventID= "<?= EVENT_WAR_REG_4 ?>";break;
		case "6" : eventID= "<?= EVENT_WAR_REG_6 ?>";break;
		case "7" : eventID= "<?= EVENT_WAR_REG_7 ?>";break;
		case "8" : eventID= "<?= EVENT_WAR_REG_8 ?>";break;
		default  : eventID= "<?= EVENT_WAR_REG_other ?>";break;
	}
	framework.ExecuteEvent(eventID, new Array(record.data.RequestID));
}

WarrentyRequest.prototype.ExecuteEndEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	switch(record.data.TypeID)
	{
		case "2" : eventID= "<?= EVENT_WAR_END_2 ?>";break;
		case "3" : eventID= "<?= EVENT_WAR_END_3 ?>";break;
		case "4" : eventID= "<?= EVENT_WAR_END_4 ?>";break;
		case "6" : eventID= "<?= EVENT_WAR_END_6 ?>";break;
		case "7" : eventID= "<?= EVENT_WAR_END_7 ?>";break;
		case "8" : eventID= "<?= EVENT_WAR_END_8 ?>";break;
		default  : eventID= "<?= EVENT_WAR_END_other ?>";break;
	}
	framework.ExecuteEvent(eventID, new Array(record.data.RequestID)
			,"WarrentyRequestObject.AfterExecuteEvent");
}

WarrentyRequest.prototype.ExecuteCancelEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	switch(record.data.TypeID)
	{
		case "2" : eventID= "<?= EVENT_WAR_CANCEL_2 ?>";break;
		case "3" : eventID= "<?= EVENT_WAR_CANCEL_3 ?>";break;
		case "4" : eventID= "<?= EVENT_WAR_CANCEL_4 ?>";break;
		case "6" : eventID= "<?= EVENT_WAR_CANCEL_6 ?>";break;
		case "7" : eventID= "<?= EVENT_WAR_CANCEL_7 ?>";break;
		case "8" : eventID= "<?= EVENT_WAR_CANCEL_8 ?>";break;
		default  : eventID= "<?= EVENT_WAR_CANCEL_other ?>";break;
	}
	framework.ExecuteEvent(eventID, new Array(
			record.data.RequestID,
			this.CancelWin.down('[name=extradays]').getValue(),
			this.CancelWin.down('[name=CancelDate]').getRawValue()
	),"WarrentyRequestObject.AfterExecuteEvent");
}

WarrentyRequest.prototype.ExecuteReduceEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	switch(record.data.TypeID)
	{
		case "2" : eventID= "<?= EVENT_WAR_SUB_2 ?>";break;
		case "3" : eventID= "<?= EVENT_WAR_SUB_3 ?>";break;
		case "4" : eventID= "<?= EVENT_WAR_SUB_4 ?>";break;
		case "6" : eventID= "<?= EVENT_WAR_SUB_6 ?>";break;
		case "7" : eventID= "<?= EVENT_WAR_SUB_7 ?>";break;
		case "8" : eventID= "<?= EVENT_WAR_SUB_8 ?>";break;
		default  : eventID= "<?= EVENT_WAR_SUB_other ?>";break;
	}
	framework.ExecuteEvent(eventID, new Array(
			record.data.RequestID,
			this.ReduceWin.down('[name=newAmount]').getValue(),
			this.ReduceWin.down('[name=ReduceDate]').getRawValue()
	),"WarrentyRequestObject.AfterExecuteEvent");
}

WarrentyRequest.prototype.ExecuteExtendEvent = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	switch(record.data.TypeID)
	{
		case "2" : eventID= "<?= EVENT_WAR_EXTEND_2 ?>";break;
		case "3" : eventID= "<?= EVENT_WAR_EXTEND_3 ?>";break;
		case "4" : eventID= "<?= EVENT_WAR_EXTEND_4 ?>";break;
		case "6" : eventID= "<?= EVENT_WAR_EXTEND_6 ?>";break;
		case "7" : eventID= "<?= EVENT_WAR_EXTEND_7 ?>";break;
		case "8" : eventID= "<?= EVENT_WAR_EXTEND_8 ?>";break;
		default  : eventID= "<?= EVENT_WAR_EXTEND_other ?>";break;
	}
	framework.ExecuteEvent(eventID, new Array(
			record.data.RefRequestID,
			record.data.RequestID
			
	),"WarrentyRequestObject.AfterExecuteEvent");
}


WarrentyRequest.prototype.AfterExecuteEvent = function(){
	
	this.grid.getStore().load();
	if(this.CancelWin)
		this.CancelWin.hide();
	
	if(this.ReduceWin)
		this.ReduceWin.hide();
	
	if(this.ExtendWin)
		this.ExtendWin.hide();
}
</script>