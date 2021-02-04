<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------
	
RequestInfo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	MenuID : '<?= isset($_POST["MenuID"]) ? $_POST["MenuID"] : 0 ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	RequestID : <?= $RequestID ?>,
	
	EndEventID : <?= LON_requests::GetEventID($RequestID, EVENTTYPE_LoanEnd) ?>,
	ContractEventID : <?= LON_requests::GetEventID($RequestID, EVENTTYPE_LoanContract) ?>,
	AllocEventID : <?= LON_requests::GetEventID($RequestID, EVENTTYPE_LoanAllocate) ?>,
	
	
	RequestRecord : null,
	User : '<?= $User ?>',
	ReadOnly : <?= $ReadOnly ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function RequestInfo(){
	
	this.mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاري...'});
	this.mask.show();
	
	this.grid = <?= $grid ?>;
	this.grid.getStore().on("load", function(){
		if(this.getCount() > 0)
			RequestInfoObject.grid.getSelectionModel().select(this.getCount()-1);
	});
	this.grid.on("select", function(){
		record = RequestInfoObject.grid.getSelectionModel().getLastSelected();
		RequestInfoObject.PartsPanel.loadRecord(record);
		RequestInfoObject.PartsPanel.doLayout();
		RequestInfoObject.LoadSummary(record);
		RequestInfoObject.PartsPanel.down("[name=PayInterval]").setValue(record.data.PayInterval + " " + 
			(record.data.IntervalType == "DAY" ? "روز" : "ماه"));
	
		RequestInfoObject.PartsPanel.down("[name=AgentDelayReturn]").getEl().dom.style.display = "none";
		RequestInfoObject.PartsPanel.down("[name=DelayReturn]").getEl().dom.style.display = "none";
		/*if(record.data.ComputeMode == "BANK")
		{
			RequestInfoObject.PartsPanel.down("[name=AgentDelayReturn]").getEl().dom.style.display = "block";
			RequestInfoObject.PartsPanel.down("[name=DelayReturn]").getEl().dom.style.display = "block";
		}*/
	
	});
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsHistory == "YES")
			return "greenRow";
		return "";
	}	

	if(this.RequestID > 0)
		this.grid.getStore().proxy.extraParams = { RequestID : this.RequestID };
		
	this.BuildForms();
	
	if(this.RequestID > 0)
		this.LoadRequestInfo();
		
	if(this.RequestID == 0)
	{
		this.CustomizeForm(null);
		this.mask.hide();
	}	
}

RequestInfo.prototype.LoadRequestInfo = function(){
	
	this.store = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + "request.data.php?task=SelectAllRequests&RequestID=" + this.RequestID,
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["RequestID","PartID","BranchID","LoanID","BranchName","ReqPersonID","ReqFullname","LoanPersonID",
					"LoanFullname","ReqDate","ReqAmount","ReqDetails","BorrowerDesc","BorrowerID",
					"BorrowerMobile","guarantees","AgentGuarantee","FundGuarantee","StatusID","DocumentDesc","IsFree",
					"imp_GirandehCode","imp_VamCode","IsEnded","SubAgentID","ContractNo","PlanTitle","RuleNo","FundRules",
					"DomainID","DomainDesc","IsLock",
            		"LetterID","SourceID","ExpertPersonID","DocRequestDate","DocReceiveDate",
					"MeetingDate","VisitDate","WorkgroupDiscussDate","ContractType"],
		autoLoad : true,
		listeners :{
			load : function(){
				
				me = RequestInfoObject;
				
				if(me.RequestRecord != null && me.RequestRecord.RequestID == me.RequestID)
				{
					me.RequestRecord = this.getAt(0);
					me.CustomizeForm(me.RequestRecord);
					me.grid.getStore().load();
					me.mask.hide();
					return;
				}	
				//..........................................................
				record = this.getAt(0);
				me.RequestRecord = record;
				me.companyPanel.loadRecord(record);
				//..........................................................
				oldInfo = record.data.imp_VamCode != null ? "شماره وام سیستم قدیم : " + 
					record.data.imp_VamCode + "[پرونده : " + record.data.imp_GirandehCode + "]" : "";
				me.companyPanel.down("[itemId=oldInfo]").update(oldInfo);
				//..........................................................
				if(record.data.IsEnded == "YES" && me.grid.down("[itemId=addPart]"))
				{
					me.grid.down("[itemId=addPart]").hide();
				}
				//..........................................................
				me.companyPanel.down("[name=IsLock]").setValue(record.data.IsLock == "YES");
				
				// set Miladi date to shamsi date
				me.companyPanel.down("[name=DocRequestDate]").setValue(MiladiToShamsi(record.data.DocRequestDate));
				me.companyPanel.down("[name=DocReceiveDate]").setValue(MiladiToShamsi(record.data.DocReceiveDate));
				me.companyPanel.down("[name=MeetingDate]").setValue(MiladiToShamsi(record.data.MeetingDate));
				me.companyPanel.down("[name=VisitDate]").setValue(MiladiToShamsi(record.data.VisitDate));
				me.companyPanel.down("[name=WorkgroupDiscussDate]").setValue(MiladiToShamsi(record.data.WorkgroupDiscussDate));

				//if(record.data.AgentGuarantee == "YES")
				//	me.companyPanel.down("[name=AgentGuarantee]").setValue(true);
				if(record.data.FundGuarantee == "YES")
					me.companyPanel.down("[name=FundGuarantee]").setValue(true);
				if(record.data.FundRules == "YES")
					me.companyPanel.down("[itemId=Cmp_FundRule]").setValue(true);
				else
					me.companyPanel.down("[itemId=Cmp_AgentRule]").setValue(true);
				
				if(record.data.guarantees != null)
				{
					arr = record.data.guarantees.split(",");
					for(i=0; i<arr.length; i++)
						if(arr[i] != "")
							try{
								me.companyPanel.down("[name=guarantee_" + arr[i] + "]").setValue(true);
							}
							catch {}
				}
				
				//..........................................................
				var R1 = false;
				if(record.data.LoanPersonID > 0)
				{
					R1 = me.companyPanel.down("[name=LoanPersonID]").getStore().load({
						params : {
							PersonID : record.data.LoanPersonID
						},
						callback : function(){
							if(this.getCount() > 0)
								me.companyPanel.down("[name=LoanPersonID]").setValue(this.getAt(0).data.PersonID);
						}
					});
				}	
				var R2 = false;
				var R3 = false;
				var R4 = false;
				
				
				R2 = me.companyPanel.down("[name=ReqPersonID]").getStore().load({
					params : {
						PersonID : record.data.ReqPersonID
					},
					callback : function(){
						if(this.getCount() > 0)
							me.companyPanel.down("[name=ReqPersonID]").setValue(this.getAt(0).data.PersonID);
					}
				});

				R3 = me.companyPanel.down("[itemId=cmp_subAgent]").getStore().load({
					params : {
						PersonID : record.data.ReqPersonID
					},
					callback : function(){
					}
				});					
				
	
				if(record.data.LetterID > 0)
				{
					R4 = me.companyPanel.down("[name=LetterID]").getStore().load({
						params : {
							LetterID : record.data.LetterID
						},
						callback : function(){
							if(this.getCount() > 0)
								me.companyPanel.down("[name=LetterID]").setValue(this.getAt(0).data.LetterID);
						}
					});
								
				}
				//..........................................................
				var t = setInterval(function(){
					if((!R1 || !R1.isLoading()) && (!R2 || !R2.isLoading()) && (!R3 || !R3.isLoading()) && (!R4 || !R4.isLoading()))
					{
						clearInterval(t);
						me.CustomizeForm(record);
						me.mask.hide();
					}
				}, 1000);
			}
		}
	});
}

RequestInfo.PartRender = function(v,p,record){

	if(record.data.LocalNo*1 > 0)
		p.tdAttr = "data-qtip='شماره سند اختلاف : "+record.data.LocalNo+"<br>تاریخ سند : "+
			MiladiToShamsi(record.data.DocDate)+"'";
	return v;
}

RequestInfo.OperationRender = function(v,p,record){
	
	if(record.data.IsHistory == "YES")
		return;
	if(RequestInfoObject.User == "Staff")
		return "<div  title='عملیات' class='setting' onclick='RequestInfoObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";

	if(RequestInfoObject.User == "Agent" && record.data.StatusID == "1")
	{
		return "<div  title='ویرایش' class='edit' onclick='RequestInfoObject.PartInfo(true);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;float:right;height:16'></div>"/* + 

		"<div  title='حذف' class='remove' onclick='RequestInfoObject.DeletePart();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;float:right;height:16'></div>";*/
	}
}

RequestInfo.prototype.ActDomainLOV = function(){
		
	if(!this.DomainWin)
	{
		this.DomainWin = new Ext.window.Window({
			autoScroll : true,
			width : 420,
			height : 420,
			title : "حوزه فناوری",
			closeAction : "hide",
			loader : {
				url : "/framework/baseInfo/ActDomain.php?mode=adding",
				scripts : true
			}
		});
		
		Ext.getCmp(this.TabID).add(this.DomainWin);
	}
	
	this.DomainWin.show();
	
	this.DomainWin.loader.load({
		params : {
			ExtTabID : this.DomainWin.getEl().dom.id,
			parent : "RequestInfoObject.DomainWin",
			selectHandler : function(id, name){
				RequestInfoObject.companyPanel.down("[name=DomainDesc]").setValue(name);
				RequestInfoObject.companyPanel.down("[name=DomainID]").setValue(id);
			}
		}
	});
}

RequestInfo.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	firstPart = this.grid.getStore().indexOf(record) == 0 ? true : false;
	ReqRecord = this.store.getAt(0);
	
	var op_menu = new Ext.menu.Menu();
	
	if(record.data.imp_VamCode*1 > 0)
	{
		if(this.EditAccess && this.RequestRecord.data.IsEnded == "NO" )
			op_menu.add({text: 'ویرایش',iconCls: 'edit', 
			handler : function(){ return RequestInfoObject.PartInfo(true); }});
		
		op_menu.showAt(e.pageX-120, e.pageY);
		return;
	}
	if(record.data.IsStarted == "NO" || record.data.SendEnable == "YES")
	{
		if(record.data.StatusID == "70")
			op_menu.add({text: 'ارسال درخواست',iconCls: 'refresh',
			handler : function(){ return RequestInfoObject.StartFlow(); }});
		if(this.EditAccess )
			op_menu.add({text: 'ویرایش',iconCls: 'edit', 
			handler : function(){ return RequestInfoObject.PartInfo(true); }});
		if(this.RemoveAccess)
			op_menu.add({text: 'حذف',iconCls: 'remove', 
			handler : function(){ return RequestInfoObject.DeletePart(true); }});				
	}	
	if(record.data.IsEnded == "YES")
	{
		if(this.EditAccess && firstPart && record.data.IsDocRegister == "NO" && this.RequestRecord.data.IsEnded == "NO")
			op_menu.add({text: 'ویرایش',iconCls: 'edit', 
			handler : function(){ return RequestInfoObject.PartInfo(true); }});
			
		/*if(this.EditAccess && !firstPart)
			op_menu.add({text: 'حذف',iconCls: 'remove', 
			handler : function(){ return RequestInfoObject.DeletePart(false); }});	*/
	}		
	
	if(record.data.StatusID == "70")
	{
		op_menu.add({text: 'پیگیری مطالبات',iconCls: 'process', 
		handler : function(){ return RequestInfoObject.ShowFollows(); }});
	
		op_menu.add({text: 'سابقه درخواست',iconCls: 'history', 
		handler : function(){ return RequestInfoObject.ShowPartHistory(); }});
	}
	op_menu.showAt(e.pageX-120, e.pageY);
}

RequestInfo.prototype.MakePartsPanel = function(){
	
	this.PartsPanel =  new Ext.form.FormPanel({
		width: 735,
		border : 0,
		colspan : 2,
		minHeight:20,
		items: [{
			xtype : "fieldset",
			title : "شرایط پرداخت",
			layout : "column",
			columns : 2,
			items :[this.grid,{
				xtype : "container",
				width: 580,
				style : "margin-right:5px",
				layout : {
					type : "table",
					columns : 3
				},
				defaults : {
					xtype : "displayfield",
					hideTrigger : true,
					width : 200,
					labelWidth : 90,
					fieldCls : "blueText"
				},
				items : [{ 
					name : "ComputeMode",
					fieldLabel: 'مبنای محاسبه',
					renderer : function(v){
						if(v == "BANK") return "فرمول بانک مرکزی";
						if(v == "NEW") return 'فرمول تنزیل اقساط';
						if(v == "NOAVARI") return 'فرمول صندوق نوآوری';
						if(v == "PERCENT") return 'فرمول درصدی';
					}
				},{
					name : "details",
					colspan : 2,
					width : 390,
					fieldLabel: 'توضیحات'
				},{
					fieldLabel: 'مبلغ پرداخت',
					name: 'PartAmount',
					renderer : function(v){ return Ext.util.Format.Money(v) + " ریال"}
				},{
					fieldLabel: 'تاریخ پرداخت',
					name: 'PartDate',
					renderer : function(v){return MiladiToShamsi(v);}
				},{
					fieldLabel: 'فاصله اقساط',
					name: 'PayInterval'
				},{
					xtype : "container",
					layout : "hbox",
					items : [{
						xtype : "displayfield",
						fieldLabel: 'مدت تنفس',
						fieldCls : "blueText",
						name: 'DelayMonths',
						renderer : function(v){ return v + " ماه"}
					},{
						xtype : "displayfield",
						fieldCls : "blueText",
						name: 'DelayDays',
						renderer : function(v){ return v + " روز"}
					}]					
				},{
					fieldLabel: 'تعداد اقساط',
					name: 'InstallmentCount'
				},{
					fieldLabel: 'دوره پرداخت',
					name: 'PayDuration',
					renderer : function(v){ return v + " ماه"}
				},{
					fieldLabel: 'کارمزد تنفس',
					name: 'DelayPercent',
					renderer : function(v){ return v + " %"}
				},{
					fieldLabel: 'کارمزد مشتری',
					name: 'CustomerWage'	,		
					renderer : function(v){ return v + " %"}
				},{
					fieldLabel: 'سهم صندوق',
					name: 'FundWage',
					renderer : function(v){ return v + " %"}
				},{
					fieldLabel: 'کارمزد تاخیر',
					name: "LatePercent",
					renderer : function(v){ return v + " %"}
				},{
					fieldLabel: 'درصد جریمه',
					name: 'ForfeitPercent'	,		
					renderer : function(v){ return v + " %"}
				},{
					fieldLabel: 'جریمه صندوق',
					name: 'FundForfeitPercent'	,		
					renderer : function(v){ return v + " %"}
				},{
					name : "WageReturn",
					fieldLabel: 'کارمزد صندوق',
					renderer : function(v){
						if(v == "CUSTOMER") return "هنگام پرداخت وام";
						if(v == "AGENT") return 'سپرده سرمایه گذار';
						if(v == "INSTALLMENT") return 'طی اقساط';
					}
				},{
					name : "DelayReturn",
					colspan : 2,
					fieldLabel: 'تنفس صندوق',
					renderer : function(v){
						if(v == "CUSTOMER") return "هنگام پرداخت وام";
						if(v == "INSTALLMENT") return 'طی اقساط';
						if(v == "CHEQUE") return 'چک';
						if(v == "NEXTYEARCHEQUE") return 'چک سالهای بعد';
					}
				},{ 
					name : "AgentReturn",
					fieldLabel: 'کارمزد سرمایه گذار',
					renderer : function(v){
						if(v == "CUSTOMER") return "هنگام پرداخت وام";
						if(v == "INSTALLMENT") return 'طی اقساط';
					}
				},{
					name : "AgentDelayReturn",
					fieldLabel: 'تنفس سرمایه گذار',
					renderer : function(v){
						if(v == "CUSTOMER") return "هنگام پرداخت وام";
						if(v == "INSTALLMENT") return 'طی اقساط';
						if(v == "CHEQUE") return 'چک';
						if(v == "NEXTYEARCHEQUE") return 'چک سالهای بعد';
					},
					colspan : 2
				}/*,{
					name : "MaxFundWage",
					labelWidth : 110,
					fieldLabel: 'سقف کارمزد صندوق',
					renderer : function(v){ return Ext.util.Format.Money(v)	}
				},{
					colspan : 3,
					name : "BackPayComputeDesc",
					width : 500,
					fieldLabel: 'محاسبه پرداخت'
				}*/,{
					colspan : 3,
					xtype : "container",
					width : 580,
					contentEl : this.get("summaryDIV")
				}/*,{
					xtype : "button",
					style : "margin-top:5px",
					text : "فرمول های محاسبات وام",
					iconCls : "process",
					handler : function(){
						window.open("/framework/help/compute.pdf");
					}
				}*/]
			}]
		}]
	});
}

RequestInfo.prototype.BuildForms = function(){

	this.MakePartsPanel();
	
	this.attachButtons = [{
		text : 'مدارک وام',
		iconCls : "attach",
		itemId : "cmp_LoanDocuments",
		handler : function(){ RequestInfoObject.LoanDocuments('loan'); }	
	},{
		text : 'مدارک وام گیرنده',
		iconCls : "attach",
		itemId : "cmp_PersonDocuments",
		handler : function(){ RequestInfoObject.LoanDocuments('person'); }
	},{
		text : 'ضامنین/وثیقه گذاران',
		iconCls : "list",
		itemId : "cmp_guarantors",
		handler : function(){ RequestInfoObject.LoanGuarantors(); }
	},{
		text : 'سابقه',
		iconCls : "history",
		itemId : "cmp_history",
		handler : function(){ RequestInfoObject.ShowHistory(); }
	},{
		text : 'پیام ها',
		iconCls : "comment",
		itemId : "cmp_comment",
		handler : function(){ RequestInfoObject.ShowMessages(); }
	},{
		text : 'رویدادها',
		iconCls : "task",
		itemId : "cmp_events",
		handler : function(){ RequestInfoObject.ShowEvents(); }
	}];
	
	this.companyPanel = new Ext.form.FormPanel({
		renderTo : this.get("mainForm"),
		width: 750,
		bodyStyle : "padding:2px",
		frame : true,
		layout : {
			type : "column",
			columns : 2
		},	
		defaults : {
			width : 350,				
			labelWidth : 130
		},
		items : [{
			xtype : "displayfield",
			fieldLabel : "شماره وام",
			value : this.RequestID,
			width : 400,
			fieldCls : "blueText"
		},{
			xtype : "combo",
			hidden : true,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserTypes=IsAgent,IsSupporter,IsShareholder&EmptyRow=true",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "منبع",
			pageSize : 25,
			displayField : "fullname",
			valueField : "PersonID",
			name : "ReqPersonID",
			itemId : "cmp_requester",
			listeners :{
				select : function(record){
					el = RequestInfoObject.companyPanel.down("[itemId=cmp_subAgent]");
					el.getStore().proxy.extraParams["PersonID"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectSubAgents",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['SubID','SubDesc'],
				autoLoad : true
			}),
			fieldLabel : "منبع فرعی",
			queryMode : "local",
			displayField : "SubDesc",
			valueField : "SubID",
			name : "SubAgentID",
			itemId : "cmp_subAgent"
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
			hidden : true,
			valueField : "PersonID",
			name : "LoanPersonID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../loan/loan.data.php?task=GetAllLoans',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['LoanID','LoanDesc'],
				autoLoad : true					
			}),
			fieldLabel : "نوع وام",
			queryMode : 'local',
			hidden : true,
			displayField : "LoanDesc",
			valueField : "LoanID",
			name : "LoanID"
		},{
			xtype : "textfield",
			name : "BorrowerDesc",
			fieldLabel : "فرد حقیقی / حقوقی"
		},{
			xtype : "textfield",
			name : "BorrowerID",
			fieldLabel : "کد ملی / کد اقتصادی"
		},{
			xtype : "numberfield",
			hideTrigger : true,
			name : "BorrowerMobile",
			fieldLabel : "موبایل وام گیرنده"
			
		},{
			xtype : "currencyfield",
			name : "ReqAmount",
			allowBlank : false,
			fieldLabel : "مبلغ درخواست",
			hideTrigger: true
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/baseInfo/baseInfo.data.php?' +
						"task=SelectBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه اخذ وام",
			queryMode : 'local',
			allowBlank : false,
			hidden : true,
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'request.data.php?' +
						"task=SelectContractType",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			fieldLabel : "نوع قرارداد",
			queryMode : 'local',
			displayField : "InfoDesc",
			valueField : "InfoID",
			name : "ContractType"
		},/*{
			xtype : "trigger",
			colspan : 2,
			width : 700,
			fieldLabel: 'حوزه فناوری',
			name: 'DomainDesc',	
			triggerCls:'x-form-search-trigger',
			onTriggerClick : function(){
				RequestInfoObject.ActDomainLOV();
			},
			listeners : {
				blur : function(){
					if(this.getValue() == "")
						RequestInfoObject.companyPanel.down("[name=DomainID]").setValue();
				}
			}
		}*/{
    xtype : "combo",
                        colspan : 2,
                        width : 700,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'request.data.php?' +
						"task=SelectDomainType",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc','param1'],
				autoLoad : true					
			}),
    fieldLabel: 'حوزه فناوری',
    queryMode : 'local',
			displayField : "InfoDesc",
			valueField : "InfoID",
			name : "DomainID"
},{
			xtype : "container",
			layout : "hbox",
			colspan : 2,
			itemId : "setting",
			items :[{
				xtype : "checkbox",
				fieldStyle : "margin-right : 4px",
				boxLabel : "وام بلاعوض می باشد",
				inputValue : "YES",
				name : "IsFree"
			},/*{
				xtype : "checkbox",
				name : "AgentGuarantee",
				value : "YES",
				boxLabel : "با ضمانت سرمایه گذار"
			}و*/{
				xtype : "checkbox",
				name : "FundGuarantee",
				fieldStyle : "margin-right : 25px",
				value : "YES",
				colspan : 2,
				boxLabel : "ضمانت اقساط وام با صندوق می باشد"
			}]
		},/*{
			xtype : "hidden",
			name : "DomainID"
		},*/{
			xtype : "container",
			hidden : true,
			colspan : 2,
			itemId : "oldInfo",
			width : 300,
			items : [{
				xtype : "numberfield",
				name : "imp_VamCode",
				hideTrigger : true,
				fieldLabel : "شماره وام قدیم"
			}],
			cls : "blueText"
		},{
			xtype:'toggleslidefield',
			fieldLabel : "تغییرات در شرایط و اقساط وام قفل می باشد",
			state: false,
			colspan : 2,
			width : 700,
			name: 'IsLock',
			onText: 'فعال', 
			offText: 'غیرفعال',
			labelWidth: 200
		},{
			xtype : "fieldset",
			title : "تضمین",
			colspan : 2,
			layout : {
				type : "table",
				columns : 6
			},
			itemId : "cmp_guarantees",
			width : 700,
			items :[{
				xtype : "radiogroup",
				colspan : 6,
				width : 400,
				fieldLabel : "تضامین بر اساس",
				items : [{
					boxLabel : "نظر صندوق",
					inputValue : "YES",
					checked : true,
					itemId : "Cmp_FundRule",
					name : "FundRules"
				},{
					boxLabel : "نظر منبع",
					inputValue : "NO",
					itemId : "Cmp_AgentRule",
					name : "FundRules"
				}]
			},{
				xtype : "container",
				colspan : 6,
				width : 700,
				html : "<hr>"
			},{
				xtype : "checkbox",
				boxLabel: 'وثیقه ملکی',
				name: 'guarantee_1',	
				inputValue: 1,
				style : "margin-left : 20px"
			},{
				xtype : "checkbox",
				boxLabel: 'ضمانت بانکی',
				name: 'guarantee_2',	
				inputValue: 1,
				style : "margin-left : 20px"
			},{
				xtype : "checkbox",
				boxLabel: 'چک',
				name: 'guarantee_3',	
				inputValue: 1,
				style : "margin-left : 20px"
			},{
				xtype : "checkbox",
				boxLabel: 'سفته',
				name: 'guarantee_4',	
				inputValue: 1,
				style : "margin-left : 20px"
			},{
				xtype : "checkbox",
				boxLabel: 'کسر از حقوق',
				name: 'guarantee_5',	
				inputValue: 1,
				style : "margin-left : 20px"
			},{
				xtype : "checkbox",
				boxLabel: 'ماشین آلات',
				name: 'guarantee_6',	
				inputValue: 1,
				style : "margin-left : 20px"
			},{
				xtype : "checkbox",
				boxLabel: 'سایر اوراق بهادار بانکی',
				name: 'guarantee_7',	
				inputValue: 1,
				style : "margin-left : 20px"
			},{
				xtype : "checkbox",
				boxLabel: 'اوراق مشارکت بی نام',
				name: 'guarantee_8',	
				inputValue: 1,
				style : "margin-left : 20px"
			}]			
		},{
			xtype : "textarea",
			fieldLabel : "توضیحات",
			rows : 1,
			style : this.RequestID == 0 ? "margin-bottom:15px" : "",
			name : "ReqDetails"
		},{
			xtype : "textarea",
			fieldLabel : "توضیحات مدارک",
			rows : 1,
			name : "DocumentDesc"
		},{
			xtype : "textfield",
			fieldLabel : "شماره قرارداد",
			name : "ContractNo",
		},{
			xtype : "textfield",
			fieldLabel : "شماره مصوبه",
			name : "RuleNo"
		},{
			xtype : "textfield",
			fieldLabel : "عنوان طرح",
			name : "PlanTitle",
			colspan: 2,
			width : 700
		},{
			xtype : "combo",
			name : "LetterID",
			colspan : 2,
			width : 700,
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/office/letter/letter.data.php?task=SelectLetter',
					reader: {root: 'rows', totalProperty: 'totalCount'}
				},
				fields :  ['LetterID','LetterTitle',{
						name : 'LetterDate',
						convert : function(v){return MiladiToShamsi(v);}
					},{
						name : "fulltitle", 
						convert: function(v,r){
							return '['+r.data.LetterID + '] ' + r.data.LetterTitle + ' [ ' + r.data.LetterDate + ' ]'; } 
					}],
				pageSize : 20
			}),
			pageSize : 20,
			fieldLabel : "شماره نامه",
			displayField: 'fulltitle',
			valueField : "LetterID",
			tpl: new Ext.XTemplate(
				'<table class=gridCombo cellspacing="0" >',
					'<tr>',
						'<th>شماره نامه</th>',
						'<th>عنوان نامه</th>',
						'<th>تاریخ نامه</th>',
					'</tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item">',
						'<td>{LetterID}</td>',
						'<td>{LetterTitle}</td>',
						'<td>{LetterDate}</td>',
					'</tr>',
				'</tpl>',
				'</table>')
		},{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../loan/loan.data.php?task=SelectAllLoanSources',
					reader: {root: 'rows', totalProperty: 'totalCount'}
				},
				fields :  ['SourceID', 'SourceName'],
				autoLoad : true
			}),
			queryMode : "local",
			fieldLabel : "منابع پرداخت",
			displayField: 'SourceName',
			valueField : "SourceID",
			name : "SourceID",
			allowBlank : true
		},{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type:'jsonp',
					url:this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons&UserTypes=IsStaff',
					reader: {root: 'rows', totalProperty: 'totalCount'}
				},
				fields :  ['PersonID', 'fullname'],
				autoLoad : true
			}),
			queryMode : "local",
			fieldLabel : "کارشناس",
			displayField: 'fullname',
			valueField : "PersonID",
			name : "ExpertPersonID"
		},{
			xtype : "shdatefield",
			name : "DocRequestDate",
			fieldLabel : "تاریخ درخواست مدارک"
		},{
			xtype : "shdatefield",
			name : "DocReceiveDate",
			fieldLabel : "تاریخ دریافت مدارک"
		},{
			xtype : "shdatefield",
			name : "MeetingDate",
			fieldLabel : "تاریخ جلسه"
		},{
			xtype : "shdatefield",
			name : "VisitDate",
			fieldLabel : "تاریخ بازدید"
		},{
			xtype : "shdatefield",
			name : "WorkgroupDiscussDate",
			fieldLabel : "تاریخ طرح در کارگروه"
		},


    this.PartsPanel
		],
		tbar :[{
			text : "پیوست ها",
			itemId : "cmp_menu",
			iconCls : "setting",
			menu : this.attachButtons
		},{
			text : "عملیات",
			hidden : true,
			itemId : "cmp_processes",
			iconCls : "process",
			menu : [{
				text: 'اقساط',
				itemId : "cmp_installments",
				iconCls: 'list',
				handler : function(){ return RequestInfoObject.LoadInstallments(); }
			},{
				text: 'پرداخت های مشتری',
				itemId : "cmp_BackPays",
				iconCls: 'list',
				handler : function(){ return RequestInfoObject.LoadBackPays(); }
			},{
				text: 'مراحل پرداخت',
				itemId : "cmp_payments",
				iconCls: 'epay',
				handler : function(){ return RequestInfoObject.LoadPayments(); }
			},{
				text : 'هزینه ها',
				iconCls : "account",
				hidden : true,
				itemId : "cmp_costs",
				handler : function(){ RequestInfoObject.ShowCosts(); }
			},{
				text : 'پیگیری مطالبات',
				iconCls : "process",
				itemId : "cmp_follows",
				handler : function(){ RequestInfoObject.ShowFollows(); }
			},{
				text : 'چک لیست',
				iconCls : "check",
				hidden : true,
				itemId : "cmp_checklist",
				handler : function(){ RequestInfoObject.ShowCheckList(); }
			},{
				text: 'چاپ رسید اقساط',
				iconCls: 'print', 
				handler : function(){ 
					window.open(ManageRequestObject.address_prefix + "PrintLoanDocs.php?type=checks&RequestID=" +
						RequestInfoObject.RequestID);	
				}
			},{
				text: 'چاپ رسید تضامین',
				iconCls: 'print', 
				handler : function(){ 
					window.open(ManageRequestObject.address_prefix + "PrintLoanDocs.php?type=tazmin&RequestID=" +
						RequestInfoObject.RequestID);	
				}
			},{
				text: 'چاپ کاردکس',iconCls: 'print', 
				handler : function(){ 
					window.open(ManageRequestObject.address_prefix + "../report/LoanSummary.php?RequestID=" +
						RequestInfoObject.RequestID);	
				}
			}]
		},'->',{
			text : "رویدادهای مالی",
			hidden : true,
			iconCls : "account",
			itemId : "cmp_accevents",
			menu :[{
				text : 'اجرای رویداد عقد قرارداد با مشتری',
				iconCls : "send",
				handler : function(){ RequestInfoObject.ExecuteEvent(); }
			},{
				text : 'اجرای رویداد تخصیص وام به مشتری',
				iconCls : "send",
				handler : function(){ 
					RequestInfoObject.AllocExecuteEvent();
				}
			}]
		},{
			text : 'ویرایش شرایط پرداخت',
			hidden : true,
			iconCls : "edit",
			itemId : "cmp_editPart",
			handler : function(){ RequestInfoObject.PartInfo(true); }
		},{
			text : 'مراحل پرداخت',
			hidden : true,
			iconCls : "list",
			itemId : "cmp_payments",
			handler : function(){ RequestInfoObject.LoadPayments(); }
		},{
			text : 'تایید ارسال مدارک',
			hidden : true,
			iconCls : "tick",
			itemId : "cmp_confirm50",
			handler : function(){ RequestInfoObject.beforeChangeStatus(50); }
		},{
			xtype : "button",
			itemId : "cmp_save",
			iconCls : "save",
			text : "ذخیره",
			handler : function(){ RequestInfoObject.SaveRequest('save'); }
		},{
			text : "ذخیره و ارسال درخواست",
			iconCls : "save",
			hidden : true,
			itemId : "cmp_saveAndSend",
			handler : function(){ RequestInfoObject.SaveRequest('send'); }
		},{
			text : 'تغییر وضعیت',
			hidden : true,
			iconCls : "refresh",
			itemId : "cmp_changeStatus",
			handler : function(){ RequestInfoObject.SetStatus(); }
		},{
			text : 'تایید درخواست',
			hidden : true,
			iconCls : "tick",
			itemId : "cmp_confirm30",
			handler : function(){ RequestInfoObject.beforeChangeStatus(30); }
		},{
			text : 'رد درخواست',
			hidden : true,
			iconCls : "cross",
			itemId : "cmp_reject20",
			handler : function(){ RequestInfoObject.beforeChangeStatus(20); }
		},{
			text : 'ارسال به مشتری جهت تکمیل مدارک',
			hidden : true,
			iconCls : "send",
			itemId : "cmp_SendToCustomer",
			handler : function(){ RequestInfoObject.ChangeStatus(40); }
		},{
			text : 'برگشت از مشتری',
			hidden : true,
			iconCls : "back",
			itemId : "cmp_returnFromCustomer",
			handler : function(){ RequestInfoObject.ChangeStatus(35, ""); }
		},{
			text : 'تایید مدارک مشتری',
			hidden : true,
			iconCls : "tick",
			itemId : "cmp_confirm70",
			handler : function(){ RequestInfoObject.beforeChangeStatus(70); }
		},{
			text : 'عدم تایید مدارک',
			hidden : true,
			iconCls : "cross",
			itemId : "cmp_reject60",
			handler : function(){ RequestInfoObject.beforeChangeStatus(60); }
		},{
			text : 'تسویه وام',
			hidden : true,
			iconCls : "account",
			itemId : "cmp_Defray",
			handler : function(){ RequestInfoObject.DefrayRequest(); }
		},{
			text : 'خاتمه وام',
			hidden : true,
			iconCls : "finish",
			itemId : "cmp_end",
			handler : function(){ RequestInfoObject.EndRequest(); }
		},{
			text : "برگشت خاتمه وام",
			hidden : true,
			iconCls : "undo",
			itemId : "cmp_undoend",
			handler : function(){ RequestInfoObject.ReturnEndRequest(); }
		}]
	});
		
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
			html : "<br>"
		}]
	});
		
	this.get("summaryDIV").style.display = "";
} 

RequestInfo.prototype.CustomizeForm = function(record){
	
	if(this.User == "Staff")
	{
		this.companyPanel.down("[itemId=cmp_saveAndSend]").hide();
		this.companyPanel.down("[itemId=cmp_requester]").show();
		this.companyPanel.down("[name=LoanID]").show();
		this.companyPanel.down("[name=BranchID]").show();
		this.companyPanel.down("[name=LoanPersonID]").show();		
		this.companyPanel.down("[itemId=oldInfo]").show();				
		
		if(record == null)
		{
			this.companyPanel.down("[name=BorrowerDesc]").hide();
			this.companyPanel.down("[name=BorrowerID]").hide();
			this.companyPanel.down("[name=BorrowerMobile]").hide();			
			//this.companyPanel.down("[name=AgentGuarantee]").hide();
			this.companyPanel.down("[name=FundGuarantee]").hide();
			this.PartsPanel.hide();
		}
	}
	if(this.User == "Agent")
	{
		if(this.RequestID == 0)
			this.PartsPanel.hide();
		else
		{
			this.companyPanel.down("[itemId=cmp_save]").hide();
			this.companyPanel.down("[itemId=cmp_saveAndSend]").show();
			this.grid.hide();
			this.companyPanel.down("[itemId=cmp_editPart]").show();
			this.companyPanel.down("[itemId=cmp_payments]").show();			
		}		
		
		this.companyPanel.down("[name=BranchID]").setValue(0); 
		this.companyPanel.down("[name=BranchID]").hide();	
		
		this.companyPanel.doLayout();
	}
	if(this.User == "Shareholder")
	{
		this.PartsPanel.hide();
		this.companyPanel.down("[itemId=cmp_save]").hide();
		this.companyPanel.down("[itemId=cmp_subAgent]").hide();
		this.companyPanel.down("[itemId=cmp_guarantees]").hide();
		this.companyPanel.down("[itemId=setting]").hide();
		this.companyPanel.down("[name=PlanTitle]").hide();
		this.companyPanel.down("[name=ContractNo]").hide();
		this.companyPanel.down("[name=RuleNo]").hide();
		
		this.companyPanel.down("[itemId=cmp_saveAndSend]").show();
		this.grid.hide();
		this.companyPanel.down("[name=BranchID]").setValue(1);
		this.companyPanel.down("[name=BranchID]").hide();	
		this.companyPanel.doLayout();
	}
	
	if(record != null)
	{
		if(this.User == "Agent" && record.data.StatusID != "1" && record.data.StatusID != "20")
		{
			this.companyPanel.getEl().readonly();
			this.companyPanel.down("[itemId=cmp_save]").hide();
			this.companyPanel.down("[itemId=cmp_saveAndSend]").hide();
			this.companyPanel.down("[itemId=cmp_editPart]").hide();
			this.companyPanel.down("[itemId=cmp_payments]").hide();	
			this.grid.down("[dataIndex=PartID]").hide();
		}	
		
		if(this.User == "Staff")
		{
			if(record.data.IsEnded == "YES")
			{
				//this.companyPanel.getEl().readonly();
				this.companyPanel.getComponent("cmp_requester").setReadOnly(true);
				this.companyPanel.getComponent("cmp_subAgent").setReadOnly(true);
				this.companyPanel.down("[name=LoanPersonID]").setReadOnly(true);
				this.companyPanel.down("[name=LoanID]").setReadOnly(true);
				this.companyPanel.down("[name=BorrowerDesc]").setReadOnly(true);
				this.companyPanel.down("[name=BorrowerID]").setReadOnly(true);
				this.companyPanel.down("[name=BorrowerMobile]").setReadOnly(true);
				this.companyPanel.down("[name=ReqAmount]").setReadOnly(true);
				this.companyPanel.down("[name=BranchID]").setReadOnly(true);
				//this.companyPanel.down("[itemId=cmp_save]").hide();
			}
			else
			{
				if(record.data.StatusID == "70")
				{
					if(record.data.imp_VamCode*1 == null || record.data.imp_VamCode*1 == 0)
					{
						//this.companyPanel.getEl().readonly();
						this.companyPanel.getComponent("cmp_requester").setReadOnly(true);
						this.companyPanel.getComponent("cmp_subAgent").setReadOnly(true);
						this.companyPanel.down("[name=LoanPersonID]").setReadOnly(true);
						this.companyPanel.down("[name=LoanID]").setReadOnly(true);
						this.companyPanel.down("[name=BorrowerDesc]").setReadOnly(true);
						this.companyPanel.down("[name=BorrowerID]").setReadOnly(true);
						this.companyPanel.down("[name=BorrowerMobile]").setReadOnly(true);
						this.companyPanel.down("[name=ReqAmount]").setReadOnly(true);
						this.companyPanel.down("[name=BranchID]").setReadOnly(true);
						//this.companyPanel.down("[itemId=cmp_save]").hide();
					}					
				}
				if(record.data.ReqPersonID == null)
				{
					this.companyPanel.down("[name=BorrowerDesc]").hide();
					this.companyPanel.down("[name=BorrowerID]").hide();
					this.companyPanel.down("[name=BorrowerMobile]").hide();					
				}
			}		
			this.companyPanel.down("[itemId=cmp_events]").show();
			this.companyPanel.down("[itemId=cmp_costs]").show();
			this.companyPanel.down("[itemId=cmp_checklist]").show();			
			this.companyPanel.down("[itemId=cmp_processes]").show();	
			this.companyPanel.down("[itemId=cmp_accevents]").show();	
			
		}	
		if(this.User == "Customer")
		{
			this.companyPanel.down("[name=BorrowerDesc]").hide();
			this.companyPanel.down("[name=BorrowerID]").hide();
			this.companyPanel.down("[name=BorrowerMobile]").hide();
			this.companyPanel.down("[name=ReqDetails]").hide();
			this.companyPanel.down("[itemId=cmp_save]").hide();
			//this.companyPanel.down("[name=AgentGuarantee]").hide();
			this.companyPanel.down("[name=FundGuarantee]").hide();
			this.companyPanel.down("[itemId=cmp_events]").hide();
			this.companyPanel.down("[itemId=cmp_costs]").hide();
			this.companyPanel.down("[itemId=cmp_checklist]").hide();			
						
			this.companyPanel.getEl().readonly();
			
			//this.grid.down("[itemId=addPart]").hide();
			this.grid.down("[dataIndex=PartID]").hide();	
			this.companyPanel.down("[itemId=cmp_saveAndSend]").hide();
			
			this.PartsPanel.down("[name=FundWage]").getEl().dom.style.display = "none";
			this.PartsPanel.down("[name=WageReturn]").getEl().dom.style.display = "none";
			this.PartsPanel.down("[name=ComputeMode]").getEl().dom.style.display = "none";
			//this.PartsPanel.down("[name=PayCompute]").getEl().dom.style.display = "none";
			//this.PartsPanel.down("[name=MaxFundWage]").getEl().dom.style.display = "none";
			this.PartsPanel.down("[name=AgentReturn]").getEl().dom.style.display = "none";
			//this.PartsPanel.down("[name=AgentDelayReturn]").getEl().dom.style.display = "none";
			//this.PartsPanel.down("[name=DelayReturn]").getEl().dom.style.display = "none";
			this.get("TR_FundWage").style.display = "none";
			this.get("TR_AgentWage").style.display = "none";
			this.get("div_yearly").style.display = "none";
			this.companyPanel.down("[itemId=cmp_menu]").hide();
			this.companyPanel.down("toolbar").insert(0,this.attachButtons);

		}
		this.companyPanel.doLayout();
	}
	
	//..........................................................................
	this.companyPanel.down("[itemId=cmp_confirm30]").hide();
	this.companyPanel.down("[itemId=cmp_reject20]").hide();
	this.companyPanel.down("[itemId=cmp_SendToCustomer]").hide();
	this.companyPanel.down("[itemId=cmp_returnFromCustomer]").hide();
	this.companyPanel.down("[itemId=cmp_confirm70]").hide();
	this.companyPanel.down("[itemId=cmp_reject60]").hide();
	this.companyPanel.down("[itemId=cmp_changeStatus]").hide();
	this.companyPanel.down("[itemId=cmp_end]").hide();
	this.companyPanel.down("[itemId=cmp_Defray]").hide();	
	this.companyPanel.down("[itemId=cmp_undoend]").hide();
	this.companyPanel.down("[itemId=cmp_confirm50]").hide();
	
	if(record == null)
	{
		this.companyPanel.down("[itemId=cmp_LoanDocuments]").hide();
		this.companyPanel.down("[itemId=cmp_PersonDocuments]").hide();
		this.companyPanel.down("[itemId=cmp_history]").hide();
		this.companyPanel.down("[itemId=cmp_comment]").hide();
	}
	
	if(this.ReadOnly || !this.EditAccess)
	{
		this.companyPanel.down("[itemId=cmp_save]").hide();
		return;
	}
	
	if(record == null)
		return;
	
	if(this.User == "Customer")
	{
		if(record.data.StatusID == "40" || record.data.StatusID == "60")
			this.companyPanel.down("[itemId=cmp_confirm50]").show();
		return;
	}
	
	if(record.data.IsEnded == "YES")
	{
		this.companyPanel.down("[itemId=cmp_undoend]").show();
		return;
	}		
	
	if(record != null && this.User == "Staff")
	{
		//if('<?= $_SESSION["USER"]["UserName"] ?>' == 'admin')
		//{
			this.companyPanel.down("[itemId=cmp_changeStatus]").show();
		//}
		if(record.data.StatusID == "<?= LON_REQ_STATUS_RAW ?>")
		{
			this.companyPanel.down("[itemId=cmp_confirm30]").show();
		}
		if(record.data.StatusID == "<?= LON_REQ_STATUS_SEND ?>")
		{
			this.companyPanel.down("[itemId=cmp_confirm30]").show();
			this.companyPanel.down("[itemId=cmp_reject20]").show();
		}
		if(record.data.StatusID == "<?= LON_REQ_STATUS_PRECONFIRM ?>" || record.data.StatusID == "<?= LON_REQ_STATUS_RETURN_CUSTOMER ?>")
		{
			this.companyPanel.down("[itemId=cmp_SendToCustomer]").show();
		}
		if(record.data.StatusID == "<?= LON_REQ_STATUS_SEND_CUSTOMER ?>")
		{
			this.companyPanel.down("[itemId=cmp_returnFromCustomer]").show();
		}
		if(record.data.StatusID == "<?= LON_REQ_STATUS_CUSTOMER_COMPLETE ?>")
		{
			this.companyPanel.down("[itemId=cmp_confirm70]").show();
			this.companyPanel.down("[itemId=cmp_reject60]").show();
		}
		if(record.data.IsEnded == "NO" && record.data.StatusID == "<?= LON_REQ_STATUS_CONFIRM ?>")
		{
			this.companyPanel.down("[itemId=cmp_Defray]").show();
		}
		if(record.data.IsEnded == "NO" && record.data.StatusID == "<?= LON_REQ_STATUS_DEFRAY ?>")
		{
			this.companyPanel.down("[itemId=cmp_end]").show();
		}
	}
}

RequestInfoObject = new RequestInfo();

RequestInfo.prototype.SaveRequest = function(mode){

	if(this.companyPanel.down("[name=ReqPersonID]").getValue()*1 == 0 &&
		this.companyPanel.down("[name=LoanID]").getValue()*1 == 9)
	{
		Ext.MessageBox.alert("Error", "برای وام های عاملیت انتخاب منبع الزامی است");
		return;
	}

	mask = new Ext.LoadMask(this.companyPanel, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	
	this.companyPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + '../../loan/request/request.data.php?task=SaveLoanRequest' , 
		method: "POST",
		params : {
			RequestID : this.RequestID,
			sending : mode == "send" ? "true" : "false"
		},
		
		success : function(form,action){
			mask.hide();
			me = RequestInfoObject;
			
			me.RequestID = action.result.data;
			
			me.grid.getStore().proxy.extraParams = {RequestID: me.RequestID};
			me.grid.getStore().load();
			
			if(me.User == "Agent")
				me.companyPanel.down("[itemId=cmp_save]").hide();
			
			me.PartsPanel.show();
			me.LoadRequestInfo();
			me.PartsPanel.doLayout();
			if( mode == "send")
			{
				me.companyPanel.hide();
				me.PartsPanel.hide();
				me.SendedPanel.show();
				me.SendedPanel.getComponent("requestID").
					update('شماره پیگیری درخواست : ' + me.RequestID);
			}
		},
		failure : function(form,action){
			mask.hide();
			if(action.result.data == "")
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			else
				Ext.MessageBox.alert("Error",action.result.data);
		}
	});
}

RequestInfo.prototype.ShowHistory = function(){

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
		
		this.HistoryWin.show();
		this.HistoryWin.center();
		this.HistoryWin.loader.load({
			params : {
				RequestID : this.RequestID
			}
		});
		return;
	}
	
	this.HistoryWin.show();
	this.HistoryWin.center();
}

RequestInfo.prototype.LoanGuarantors = function(){

	if(!this.GuarantorWin)
	{
		this.GuarantorWin = new Ext.window.Window({
			title: 'ضامنین / وثیقه گذاران',
			modal : true,
			autoScroll : true,
			width: 750,
			height : 600,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "guarantors.php",
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
		Ext.getCmp(this.TabID).add(this.GuarantorWin);
		
		this.GuarantorWin.show();
		this.GuarantorWin.center();
		this.GuarantorWin.loader.load({
			params : {
				ExtTabID : this.GuarantorWin.getEl().id,
				RequestID : this.RequestID
			}
		});
		return;
	}
	
	this.GuarantorWin.show();
	this.GuarantorWin.center();
}

RequestInfo.prototype.LoanDocuments = function(ObjectType){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 920, 
			height : 440,
			modal : true,
			autoScroll:true,
			bodyStyle : "background-color:white;padding: 4px 4px 4px 4px",
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
	
	this.documentWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.documentWin.getEl().id,
			ObjectType : ObjectType,
			ObjectID : ObjectType == "loan" ? this.RequestID : this.RequestRecord.data.LoanPersonID
		}
	});
}

RequestInfo.prototype.beforeChangeStatus = function(StatusID){
	
	if(new Array(20,60).indexOf(StatusID) == -1)
	{
		message = "آیا مایل به تایید می باشید؟";
		if(StatusID == "50")
			message = "پس از تایید دیگر قادر به تغییر در اطلاعات نمی باشید<br>" +"آیا مایل به تایید می باشید؟";
	
		Ext.MessageBox.confirm("",message, function(btn){
			if(btn == "no")
				return;
			
			RequestInfoObject.ChangeStatus (StatusID, "");
		});
		return;
	}
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 412,
			height : 198,
			modal : true,
			title : "دلیل رد مدرک برای درخواست کننده",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "StepComment"
			}],
			closeAction : "hide",
			buttons : [{
				text : "اعمال",				
				iconCls : "save",
				itemId : "btn_save"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	this.commentWin.down("[itemId=btn_save]").setHandler(function(){
		RequestInfoObject.ChangeStatus(StatusID, 
			this.up('window').down("[name=StepComment]").getValue());});
	this.commentWin.show();
	this.commentWin.center();
}

RequestInfo.prototype.ChangeStatus = function(StatusID, StepComment){
	
	this.mask.show();
	
	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "request.data.php",
		params : {
			task : "ChangeRequestStatus",
			RequestID : this.RequestID,
			StatusID : StatusID,
			StepComment : StepComment
		},
		
		success : function(response){
			
			result = Ext.decode(response.responseText);
			if(StatusID == 50)
				Ext.MessageBox.alert("","مدارک شما به صندوق پژوهش و فناوری ارسال گردید");
			RequestInfoObject.LoadRequestInfo();
			if(RequestInfoObject.commentWin)
				RequestInfoObject.commentWin.hide();
			if(!result.success)
			{
				Ext.MessageBox.alert("ERROR", result.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : result.data )
			}
		}
	});
}

RequestInfo.prototype.SetStatus = function(){
	
	if(!this.setStatusWin)
	{
		this.setStatusWin = new Ext.window.Window({
			width : 412,
			height : 198,
			modal : true,
			title : "تغییر وضعیت",
			defaults : {width : 380},
			bodyStyle : "background-color:white",
			items : [{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					proxy: {
						type: 'jsonp',
						url: this.address_prefix + "request.data.php?task=selectRequestStatuses",
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields : ['InfoID','InfoDesc'],
					autoLoad : true					
				}),
				fieldLabel : "وضعیت جدید",
				queryMode : 'local',
				allowBlank : false,
				displayField : "InfoDesc",
				valueField : "InfoID",
				itemId : "StatusID"
			},{
				xtype : "textarea",
				itemId : "comment",
				fieldLabel : "توضیحات"
			}],
			closeAction : "hide",
			buttons : [{
				text : "تغییر وضعیت",				
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){
					status = this.up('window').getComponent("StatusID").getValue();
					comment = this.up('window').getComponent("comment").getValue();
					RequestInfoObject.ChangeStatus(status, "[تغییر وضعیت]" + comment);
					this.up('window').hide();
				}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.setStatusWin);
	}
	this.setStatusWin.show();
	this.setStatusWin.center();
}

RequestInfo.prototype.EndRequest = function(){
	
	framework.ExecuteEvent(RequestInfoObject.EndEventID, 
		new Array(RequestInfoObject.RequestRecord.data.RequestID,
					RequestInfoObject.RequestRecord.data.PartID), "RequestInfoObject.afterEndLoan");
	
	return;
};

RequestInfo.prototype.afterEndLoan = function(){
	
	RequestInfoObject.mask.show();
	RequestInfoObject.LoadRequestInfo();
	RequestInfoObject.mask.hide();
}
	
RequestInfo.prototype.DefrayRequest = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به تسویه وام می باشید؟", function(btn){
		if(btn == "no")
			return false;

		me = RequestInfoObject;
		
		me.mask.show();
		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "request.data.php",
			params : {
				task : "DefrayRequest",
				RequestID : me.RequestID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				if(result.success)
				{
					RequestInfoObject.LoadRequestInfo();					
				}	
				else if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);

				me.mask.hide();

			}
		});
	});
	
}

RequestInfo.prototype.ReturnEndRequest = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به برگشت خاتمه وام می باشید؟", function(btn){
		if(btn == "no")
			return false;

		me = RequestInfoObject;
		me.mask.show();

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "request.data.php",
			params : {
				task : "ReturnEndRequest",
				RequestID : me.RequestID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				if(result.success)
				{
					Ext.MessageBox.alert("","سند مربوطه با موفقیت باطل گردید");
					RequestInfoObject.LoadRequestInfo();					
				}	
				else if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);

				RequestInfoObject.mask.hide();
			}
		});
	});
}

//.........................................................

RequestInfo.prototype.BeforeAddPart = function(){
	
	if(this.grid.getStore().totalCount > 0) 
	{
		Ext.MessageBox.confirm("","با ایجاد شرایط جدید سند حسابداری اختلاف صادر می گردد.<br>" + 
			"آیا مایل به ایجاد شرایط جدید می باشید؟", function(btn){
				if(btn == "no")
					return false;
				
				RequestInfoObject.PartInfo(false);
			});
	}
	else
		RequestInfoObject.PartInfo(false);
}

RequestInfo.prototype.PartInfo = function(EditMode){

	if(!this.PartWin)
	{
		this.PartWin = new Ext.window.Window({
			width : 550,
			height : 560,
			autoScroll : true,
			modal : true,
			closeAction : 'hide',
			title : "شرایط وام",
			items : new Ext.form.Panel({
				layout : {
					type : "table",
					columns : 2
				},
				defaults : {
					xtype : "numberfield",
					labelWidth : 80,
					hideTrigger : true,
					width : 150,
					labelWidth : 90,
					allowBlank : false
				},				
				items :[{
					xtype : "textfield",
					name : "PartDesc",
					fieldLabel : "عنوان شرایط",
					colspan : 2,
					width : 450
				},{
					xtype : "textfield",
					name : "details",
					allowBlank : true,
					fieldLabel : "توضیحات",
					colspan : 2,
					width : 450
				},{
					xtype : "currencyfield",
					name : "PartAmount",
					fieldLabel : "مبلغ وام",
					maxValue : this.RequestRecord.data.RequestAmount,
					width : 220
				},{
					xtype : "container",
					width : 300,
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
					xtype : "shdatefield",
					name : "PartDate",
					allowBlank : true,
					hideTrigger : false,
					fieldLabel : "تاریخ پرداخت",
					width : 200
				},{
					xtype : "shdatefield",
					name : "PartStartDate",
					hidden : true,
					allowBlank : true,
					hideTrigger : false,
					fieldLabel : "اعمال شرایط",
					width : 200
				},{
					xtype : "container",
					layout : "hbox",
					width : 250,
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
					fieldLabel: 'تعداد اقساط',
					name: 'InstallmentCount'
				},{
					fieldLabel: 'کارمزد تنفس',
					name: 'DelayPercent',
					afterSubTpl : "%"
				},{
					fieldLabel: 'کارمزد مشتری',
					name: 'CustomerWage',
					afterSubTpl : "%"	
				},{
					fieldLabel: 'کارمزد صندوق',
					name: 'FundWage',
					afterSubTpl : "%"
				},{
					fieldLabel: 'کارمزد تاخیر',
					name: 'LatePercent',
					afterSubTpl : "%"
				},{
					fieldLabel: 'دوره پرداخت(ماه)',
					name: 'PayDuration',
					allowBlank : true,
					colspan : 2
				},{
					fieldLabel: 'درصد دیرکرد',
					name: 'ForfeitPercent',
					afterSubTpl : "%"
				},{
					fieldLabel: 'سهم صندوق از دیرکرد',
					name: 'FundForfeitPercent',
					afterSubTpl : "%",
					labelStyle : "font-size:11px"
				},{
					xtype : "fieldset",
					colspan : 3,
					style : "margin-right:10px",
					width : 450,
					title : "نحوه محاسبه کارمزد و اقساط",
					items : [{
						xtype : "radio",
						boxLabel : "فرمول جدید بانک مرکزی",
						name : "ComputeMode",
						disabled : true,
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
					},{
						xtype : "radio",						
						boxLabel : "کسر کارمزد از حساب سرمایه گذار",
						name : "WageReturn",
						inputValue : "AGENT"
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
				}/*,{
					xtype : "fieldset",
					itemId : "fs_DelayCompute",
					title : "نحوه دریافت تنفس صندوق",
					width : 220,
					style : "margin-right:10px",
					items : [{
						xtype : "radio",						
						boxLabel : "هنگام پرداخت وام",
						name : "DelayReturn",
						inputValue : "CUSTOMER"
					},{
						xtype : "radio",
						boxLabel : "طی اقساط",
						name : "DelayReturn",
						inputValue : "INSTALLMENT",
						checked : true
					},{
						xtype : "radio",
						boxLabel : "چک برای کل مبلغ",
						name : "DelayReturn",
						inputValue : "CHEQUE"
					},{
						xtype : "radio",
						boxLabel : "چک برای سالهای بعد",
						name : "DelayReturn",
						inputValue : "NEXTYEARCHEQUE"
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
						inputValue : "CUSTOMER"
					},{
						xtype : "radio",
						boxLabel : "طی اقساط",
						name : "AgentDelayReturn",
						inputValue : "INSTALLMENT",
						checked : true
					},{
						xtype : "radio",
						boxLabel : "چک برای کل مبلغ",
						name : "AgentDelayReturn",
						inputValue : "CHEQUE"
					},{
						xtype : "radio",
						boxLabel : "چک برای سالهای بعد",
						name : "AgentDelayReturn",
						inputValue : "NEXTYEARCHEQUE"
					}]
				}*//*,{
					xtype : "fieldset",
					colspan :2,
					width : 450,
					style : "margin-right:10px",
					itemId : "fs_PayCompute",
					items : [{
						xtype : "radio",
						boxLabel : "محاسبه  پرداخت بر اساس اول جریمه سپس قسط",
						name : "PayCompute",
						inputValue : "forfeit",
						checked : true
					},{
						xtype : "radio",
						boxLabel : "محاسبه پرداخت بر اساس اول اصل مبلغ قسط سپس جرایم",
						name : "PayCompute",
						inputValue : "installment"
					}]
				},{
					xtype : "fieldset",
					colspan :2,
					width : 450,
					style : "margin-right:10px",
					itemId : "fs_MaxFundWage",
					items : [{
						xtype : "currencyfield",
						hideTrigger : true,
						labelWidth : 120,
						fieldLabel : "سقف کارمزد صندوق",
						name : "MaxFundWage"
					},{
						xtype : "container",
						html : "این کارمزد بر اساس نحوه دریافت کارمزد محاسبه می گردد."
					}]
				}*/,{
					xtype : "hidden",
					name : "PartID"
				}]				
			}),
			buttons : [{
				text : "ذخیره",
				iconCls : "save",
				handler : function(){
					RequestInfoObject.SavePart();
				}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){
					this.up('window').hide();
				}
			}]
		});
		
		if(this.User == "Agent")
		{
			this.PartWin.down("[name=PartDate]").hide();
			this.PartWin.down("[itemId=fs_WageCompute]").hide();
			this.PartWin.down("[itemId=fs_AgentWageCompute]").hide();
			//this.PartWin.down("[itemId=fs_DelayCompute]").hide();
			//this.PartWin.down("[itemId=fs_AgentDelayCompute]").hide();
			//this.PartWin.down("[itemId=fs_PayCompute]").hide();
			//this.PartWin.down("[itemId=fs_MaxFundWage]").hide();
			this.PartWin.down("[name=PartAmount]").colspan = 2;
			this.PartWin.setHeight(250);
		}
	}
	
	this.PartWin.show();
	if(EditMode)
	{
		record = this.grid.getSelectionModel().getLastSelected();
		this.PartWin.down('form').loadRecord(record);
		this.PartWin.down("[name=PartDate]").setValue(MiladiToShamsi(record.data.PartDate));
		this.PartWin.down("[name=PartStartDate]").setValue(MiladiToShamsi(record.data.PartStartDate));
		this.PartWin.down("[name=PayInterval]").setValue(record.data.PayInterval*1);
		this.PartWin.down("[itemId=monthInterval]").setValue(record.data.IntervalType == "MONTH" ? true : false);
		this.PartWin.down("[itemId=dayInterval]").setValue(record.data.IntervalType == "DAY" ? true : false);
	}
	else
	{
		this.PartWin.down('form').getForm().reset();
		record = this.grid.getStore().getAt( this.grid.getStore().totalCount-1 );
		this.PartWin.down('form').loadRecord(record);
		this.PartWin.down("[name=PartDate]").setValue(MiladiToShamsi(record.data.PartDate));
		this.PartWin.down("[name=PartStartDate]").setValue(MiladiToShamsi(record.data.PartStartDate));
		this.PartWin.down("[name=PayInterval]").setValue(record.data.PayInterval*1);
		this.PartWin.down("[itemId=monthInterval]").setValue(record.data.IntervalType == "MONTH" ? true : false);
		this.PartWin.down("[itemId=dayInterval]").setValue(record.data.IntervalType == "DAY" ? true : false);
		this.PartWin.down("[name=PartID]").setValue();
	}
}

RequestInfo.prototype.SavePart = function(){

	mask = new Ext.LoadMask(this.PartWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.PartWin.down('form').getForm().submit({
		clientValidation: true,
		url: this.address_prefix +'../../loan/request/request.data.php',
		method: "POST",
		params: {
			task: "SavePart",
			RequestID : this.RequestID
		},
		success: function(form,action){
			if(action.result.data != "")
				Ext.MessageBox.alert("",action.result.data);
			mask.hide();
			RequestInfoObject.grid.getStore().load();
			RequestInfoObject.PartWin.hide();
		},
		failure: function(form,action){
			mask.hide();
			if(action.result.data == "")
				Ext.MessageBox.alert("","عملیات مربوطه با شکست مواجه شد");
			else
				Ext.MessageBox.alert("",action.result.data);
		}
	});
}

RequestInfo.prototype.DeletePart = function(firstPart){

	if(!firstPart)
		Ext.MessageBox.alert("","در صورت حذف این شرایط سند اختلاف نیز حذف می گردد");

	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = RequestInfoObject;
		record = me.grid.getSelectionModel().getLastSelected();
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "DeletePart",
				PartID : record.data.PartID
			},
			success: function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
					RequestInfoObject.grid.getStore().load();
				else
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد;")
			}
		});
	});
}

RequestInfo.prototype.SplitYears = function(startDate, endDate, TotalAmount){
	
	arr = startDate.split(/[\-\/]/);
	StartYear = arr[0]*1;
	
	totalDays = 0;
	yearDays = new Array();
	newStartDate = startDate;
	while(DateModule.IsDateGreater(endDate,newStartDate)){
		arr = newStartDate.split(/[\-\/]/);
		LastDayOfYear = DateModule.lastJDateOfYear(arr[0]);
		if(DateModule.IsDateGreater(LastDayOfYear, endDate))
			LastDayOfYear = endDate;
		
		thedays = DateModule.JDateMinusJDate(LastDayOfYear, newStartDate)+1;
		yearDays.push({
			year : StartYear, 
			days : thedays
		});
		totalDays += thedays;
		StartYear++;
		newStartDate = DateModule.AddToJDate(LastDayOfYear, 1);
	}
	TotalDays = DateModule.JDateMinusJDate(endDate, startDate)+1;
	sum = 0;
	for(i=0; i<yearDays.length; i++)
	{
		yearDays[i].amount = Math.round((yearDays[i].days/TotalDays)*TotalAmount);
		sum += yearDays[i].amount;
	}
	if(sum != TotalAmount)
		yearDays[i-1].amount += TotalAmount-sum;
	
	return yearDays;
}

RequestInfo.prototype.LoadSummary = function(record){

	this.get("SUM_InstallmentAmount").innerHTML = Ext.util.Format.Money(record.data.AllPay);
	this.get("SUM_LastInstallmentAmount").innerHTML = Ext.util.Format.Money(record.data.LastPay);
	this.get("SUM_FundDelay").innerHTML = Ext.util.Format.Money(record.data.FundDelay);
	this.get("SUM_AgentDelay").innerHTML = Ext.util.Format.Money(record.data.AgentDelay);
	this.get("SUM_TotalCustomerWage").innerHTML = Ext.util.Format.Money(record.data.TotalCustomerWage);
	this.get("SUM_FundWage").innerHTML = Ext.util.Format.Money(record.data.TotalFundWage);
	this.get("SUM_AgentWage").innerHTML = Ext.util.Format.Money(record.data.TotalAgentWage);
	this.get("SUM_Wage_1Year").innerHTML = Ext.util.Format.Money(record.data.WageYear1);
	this.get("SUM_Wage_2Year").innerHTML = Ext.util.Format.Money(record.data.WageYear2);
	this.get("SUM_Wage_3Year").innerHTML = Ext.util.Format.Money(record.data.WageYear3);
	this.get("SUM_Wage_4Year").innerHTML = Ext.util.Format.Money(record.data.WageYear4);
	this.get("SUM_NetAmount").innerHTML = Ext.util.Format.Money(record.data.SUM_NetAmount);
}

//.........................................................

RequestInfo.prototype.LoadInstallments = function(){
	
	if(!this.InstallmentsWin)
	{
		this.InstallmentsWin = new Ext.window.Window({
			width : 1000,
			title : "لیست اقساط",
			height : 410,
			modal : true,
			loader : {
				url : this.address_prefix + "installments.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide"
		});
		
		Ext.getCmp(this.TabID).add(this.InstallmentsWin);
	}
	this.InstallmentsWin.show();
	this.InstallmentsWin.center();
	
	this.InstallmentsWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.InstallmentsWin.getEl().id,
			RequestID : this.RequestID
		}
	});
}

RequestInfo.prototype.LoadBackPays = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
	{
		Ext.MessageBox.alert("","ابتدا شرایط مورد نظر خود را انتخاب کنید");
		return;
	}
	
	if(!this.PayWin)
	{
		this.PayWin = new Ext.window.Window({
			width : 1000,
			title : "لیست پرداخت های مشتری",
			height : 410,
			modal : true,
			loader : {
				url : this.address_prefix + "BackPays.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide"
		});
		
		Ext.getCmp(this.TabID).add(this.PayWin);
	}
	this.PayWin.show();
	this.PayWin.center();
	
	this.PayWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.PayWin.getEl().id,
			RequestID : this.RequestID
		}
	});
	
	this.PayWin.show();
	this.PayWin.center();
}
//.........................................................

RequestInfo.prototype.StartFlow = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به ارسال شرایط پرداخت جهت تایید می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = RequestInfoObject;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix +'request.data.php',
			method: "POST",
			params: {
				task: "StartFlow",
				PartID : record.data.PartID
			},
			success: function(response){
				mask.hide();
				RequestInfoObject.grid.getStore().load();
			}
		});
	});
}

RequestInfo.prototype.ShowPartHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش',
			modal : true,
			autoScroll : true,
			width: 700,
			height : 500,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "../../office/workflow/history.php",
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
			FlowID : 1,
			ObjectID : this.grid.getSelectionModel().getLastSelected().data.PartID
		}
	});
}

RequestInfo.prototype.LoadPayments = function(){
	
	if(!this.PaymentWin)
	{
		this.PaymentWin = new Ext.window.Window({
			width : 1000 ,
			title : "مراحل پرداخت",
			height : 305,
			modal : true,
			loader : {
				url : this.address_prefix + "payments.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide"
		});
		
		Ext.getCmp(this.TabID).add(this.PaymentWin);
	}
	this.PaymentWin.show();
	this.PaymentWin.center();
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	this.PaymentWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.PaymentWin.getEl().id,
			RequestID : this.RequestID
		}
	});
}
//.........................................................

RequestInfo.prototype.ShowMessages = function(){

	if(!this.messagesWin)
	{
		this.messagesWin = new Ext.window.Window({
			width : 713,
			title : "پیام های وام",
			height : 435,
			modal : true,
			loader : {
				url : this.address_prefix + "messages.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide"
		});
		
		Ext.getCmp(this.TabID).add(this.messagesWin);
		
		this.messagesWin.show();
		this.messagesWin.center();

		this.messagesWin.loader.load({
			params : {
				MenuID : this.MenuID,
				ExtTabID : this.messagesWin.getEl().id,
				RequestID : this.RequestID
			}
		});
		return;
	}
	this.messagesWin.show();
	this.messagesWin.center();
}

RequestInfo.prototype.ShowEvents = function(){
 
	if(!this.EventsWin)
	{
		this.EventsWin = new Ext.window.Window({
			title: 'رویدادهای مرتبط با وام',
			modal : true,
			autoScroll : true,
			width: 1000, 
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "events.php",
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
		Ext.getCmp(this.TabID).add(this.EventsWin);
		this.EventsWin.show();
		this.EventsWin.center();	
		this.EventsWin.loader.load({
			params : {
				MenuID : this.MenuID,
				ExtTabID : this.EventsWin.getEl().id,
				RequestID : this.RequestID
			}
		});
		return;
	}
	this.EventsWin.show();
	this.EventsWin.center();	
}

RequestInfo.prototype.ShowCosts = function(){

	if(!this.CostsWin)
	{
		this.CostsWin = new Ext.window.Window({
			title: 'هزینه های وام',
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
	this.CostsWin.show();
	this.CostsWin.center();	
	this.CostsWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.CostsWin.getEl().id,
			RequestID : this.RequestID
		}
	});
}

RequestInfo.prototype.ShowFollows = function(){

	if(!this.FollowWin)
	{
		this.FollowWin = new Ext.window.Window({
			title: 'پیگیری مطالبات',
			modal : true,
			autoScroll : true,
			width: 1000,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "follows.php",
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
		Ext.getCmp(this.TabID).add(this.FollowWin);
	}
	this.FollowWin.show();
	this.FollowWin.center();	
	this.FollowWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.FollowWin.getEl().id,
			RequestID : this.RequestID
		}
	});
}

RequestInfo.prototype.ShowCheckList = function(){

	if(!this.CostsWin)
	{
		this.CostsWin = new Ext.window.Window({
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
		Ext.getCmp(this.TabID).add(this.CostsWin);
	}
	this.CostsWin.show();
	this.CostsWin.center();	
	this.CostsWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.CostsWin.getEl().id,
			SourceID : this.RequestID,
			SourceType : <?= SOURCETYPE_LOAN ?>,
		}
	});
}

RequestInfo.prototype.ExecuteEvent = function(){
	
	var eventID = "";
	eventID = this.ContractEventID;

	framework.ExecuteEvent(eventID, new Array(this.RequestRecord.data.RequestID,this.RequestRecord.data.PartID));
}

RequestInfo.prototype.AllocExecuteEvent = function(){
	
	var eventID = "";
	eventID = this.AllocEventID;

	framework.ExecuteEvent(eventID, new Array(this.RequestRecord.data.RequestID,this.RequestRecord.data.PartID));
}


</script>
