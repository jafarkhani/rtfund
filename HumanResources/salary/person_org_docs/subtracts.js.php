<script type="text/javascript">
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 90.10
//-----------------------------

Subtract.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	PersonID : "",
	Ptype : "" ,
	subtract_type : "<?= $subtract_type?>",
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

Subtract.printRender = function(v,p,r)
{
	if(r.data.SubtractAmount == 0)
		return "";
	return  "<div title='چاپ' class='print' onclick='SubtractObj.printSubtract();' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;height:16'></div>";
}

function Subtract()
{
	this.personCombo = new Ext.form.ComboBox({
		store: personStore,
		emptyText:'جستجوي استاد/كارمند بر اساس نام و نام خانوادگي ...',
		typeAhead: false,
		listConfig : {
			loadingText: 'در حال جستجو...'
		},
		pageSize:10,
		width: 550,
		valueField : "PersonID",
		fieldLabel : "جستجوی فرد",

		tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid3-header">'
					,'<td height="23px">کد پرسنلی</td>'
					,'<td>کد شخص</td>'
					,'<td>نام</td>'
					,'<td>نام خانوادگی</td>'
					,'<td>واحد محل خدمت</td></tr>',
				'<tpl for=".">',
				'<tr class="x-boundlist-item" style="border-left:0;border-right:0">'
					,'<td style="border-left:0;border-right:0" class="search-item">{PersonID}</td>'
					,'<td style="border-left:0;border-right:0" class="search-item">{staff_id}</td>'
					,'<td style="border-left:0;border-right:0" class="search-item">{pfname}</td>'
					,'<td style="border-left:0;border-right:0" class="search-item">{plname}</td>'
					,'<td style="border-left:0;border-right:0" class="search-item">{unit_name}&nbsp;</td></tr>',
				'</tpl>'
				,'</table>'),

		listeners :{
			select : function(combo, records){
				var record = records[0];
				this.setValue("[" + record.data.PersonID + "] " + record.data.pfname + ' ' + record.data.plname);
				SubtractObj.PersonID = record.data.PersonID;
				SubtractObj.Ptype = record.data.person_type ; 
				SubtractObj.staff_name = record.data.pfname + ' ' + record.data.plname;
				this.collapse();
			}
		}
	});

	this.SearchPanel = new Ext.Panel({
		applyTo: this.get("selectPersonDIV"),
		title: "انتخاب فرد",
		width : 700,
		collapsible : true,
		frame :true,
		items : [this.personCombo],
		buttons : [
			{
				text: "جستجو",
				iconCls: 'search',
				handler: function(){
					SubtractObj.Searching();
				}
			}
		]
	});
	
	 this.SearchPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		SubtractObj.Searching();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

SubtractObj = new Subtract();

Subtract.prototype.Searching = function()
{
	if(SubtractObj.PersonID == "")
		return;
	SubtractObj.SearchPanel.collapse();
					
	SubtractObj.grid.getStore().proxy.extraParams["PersonID"] = SubtractObj.PersonID;
	SubtractObj.grid.setTitle("اطلاعات <?= $subtractTitle?> " + SubtractObj.staff_name);

	if(!SubtractObj.grid.rendered)
		SubtractObj.grid.render(SubtractObj.get("div_dg"));
	else
		SubtractObj.grid.getStore().load();
}

Subtract.prototype.printSubtract = function(DayDate)
{
	var record = this.grid.getSelectionModel().getLastSelected();
	window.open(this.address_prefix + "factorDailyReport.php?dayDate=" + MiladiToShamsi(record.data.DayDate,'Y/m/d') + "&cashier=true");
}

Subtract.OperationRender = function(v,p,record)
{
	return  "<div title='عملیات' class='setting' onclick='SubtractObj.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;height:16'></div>";
}

Subtract.prototype.OperationMenu = function(e)
{
	var op_menu = new Ext.menu.Menu();
	var record = this.grid.getSelectionModel().getLastSelected();
	
	op_menu.add({text: 'ویرایش',iconCls: 'edit', handler : function(){SubtractObj.BeforeEdit(true);} });
	
	if(this.subtract_type == <?= SUBTRACT_TYPE_LOAN?>)
		op_menu.add({text: "گردش دستی",iconCls: 'list', handler : function(){SubtractObj.Flows();} });
	
	op_menu.add({text: 'سابقه گردش',iconCls: 'history', handler : function(){SubtractObj.ShowHistory();} });
	
	//if(record.data.IsEditable)
		op_menu.add({text: 'حذف',iconCls: 'remove', handler : function(){SubtractObj.Remove();} });	
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

Subtract.prototype.Remove = function(){
	
	if(!confirm("آیا مایل به حذف می باشید؟"))
		return;

	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال حذف...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'subtracts.data.php?task=RemoveSubtract',
		params:{
			subtract_id: record.data.subtract_id
		},
		method: 'POST',

		success: function(response){
			mask.hide();
			var res = Ext.decode(response.responseText);
			if(!res.success)
				alert("بر اساس این ردیف فیش حقوقی صادر شده است و امکان حذف آن وجود ندارد");
			else
			SubtractObj.grid.getStore().load();
		},
		failure: function(){}
	});
}

Subtract.prototype.BeforeEdit = function(EditMode){
	
	if(!this.infoWin)
	{
		this.infoForm = new Ext.form.Panel({
			plain: true,
			border: 0,
			bodyPadding: 5,
			items : [{
				xtype : "combo",
				store : new Ext.data.Store({
					fields: ['salary_item_type_id','full_title'],
					proxy : {
						type : 'jsonp',
						url : this.address_prefix + "../../global/domain.data.php?task=searchSalaryItemTypes&pt=" + SubtractObj.Ptype  + "&subtype=" + SubtractObj.subtract_type  ,
						reader : {
							root: 'rows',
							totalProperty: 'totalCount'
						}
					},
					autoLoad : true
				}),
				fieldLabel : "قلم حقوقی",
				displayField : "full_title",
				valueField : "salary_item_type_id",
				allowBlank : false,
				width : 350,
				name : "salary_item_type_id"		
			},{
				xtype : "combo",
				store : new Ext.data.Store({
					fields: ['bank_id','name'],
					proxy : {
						type : 'jsonp',
						url : this.address_prefix + "../../global/domain.data.php?task=searchBank",
						reader : {
							root: 'rows',
							totalProperty: 'totalCount'
						}
					},
					autoLoad : true
				}),
				fieldLabel : "بانک/صندوق",
				displayField : "name",
				valueField : "bank_id",
				/*allowBlank : false,*/ 
				width : 350,
				name : "bank_id"
			},{
				xtype : "textfield",
				fieldLabel : "شماره قرارداد",
				name : "contract_no"
			},{
				xtype : "textfield",
				fieldLabel : "شماره وام",
				name : "loan_no"
			},{
				xtype : "currencyfield",
				fieldLabel : "مبلغ وام/ مزایا",
				name : "first_value",
				hideTrigger : true,
				allowBlank : false
			},{
				xtype : "currencyfield",
				fieldLabel : "مبلغ ماهانه",
				name : "instalment",
				hideTrigger : true,
				allowBlank : false
			},{
				xtype : "shdatefield",
				fieldLabel : "تاریخ شروع",
				name : "start_date",
				allowBlank : false
			},{
				xtype : "shdatefield",
				fieldLabel : "تاریخ پایان",
				name : "end_date"
			},{
				xtype : "textarea",
				fieldLabel : "توضیحات",
				name : "comments"
			},{
				xtype : "hidden",
				name : "subtract_id"
			}]
		});
		this.infoWin = new Ext.window.Window({
			autoScroll : true,
			width : 400,
			modal : true,
			bodyStyle: 'background:white; padding:10px;',
			minHeight : 300,
			title : "اطلاعات <?= $subtractTitle?>",
			closeAction : "hide",
			items : this.infoForm,
			buttons : [{
				text : "ذخیره",
				iconCls : "save",
				handler : function()
				{
					 SubtractObj.infoForm.getForm().submit({
						clientValidation: true,
						url: SubtractObj.address_prefix + 'subtracts.data.php?task=saveSubtract',
						method : "POST",
						params : {
							PersonID : SubtractObj.PersonID,
							subtract_type : SubtractObj.subtract_type
						},
						success : function(form,action){
							SubtractObj.infoForm.getForm().reset();
							SubtractObj.infoWin.hide();
							SubtractObj.grid.getStore().load();
						},
						failure : function(form,action)
						{
						}
					});
				}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){
					this.up('window').hide();
				}
			}]
		});
	}
	
	if(EditMode)
	{
		mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگزاری...'});
		mask.show();
		
		var record = this.grid.getSelectionModel().getLastSelected();
		this.infoForm.loadRecord(record);
	
		var R1 = this.infoForm.down("[name=salary_item_type_id]").getStore().load({
			params : {salary_item_type_id : record.data.salary_item_type_id},
			callback : function(){
				SubtractObj.infoForm.down("[name=salary_item_type_id]").setValue(this.getAt(0)); 
			}			
		});

		var R2 = this.infoForm.down("[name=bank_id]").getStore().load({
			params : {bank_id : record.data.bank_id ,
					  sub : 1 },
			callback : function(){
				SubtractObj.infoForm.down("[name=bank_id]").setValue(this.getAt(0)); 
			}
		});
		
		var t = setInterval(function(){
			if(!R1.isLoading() && !R2.isLoading())
			{
				clearInterval(t);
				mask.hide();
			}
		}, 1000);
		
		this.infoForm.down("[name=start_date]").setValue(MiladiToShamsi(record.data.start_date));
		this.infoForm.down("[name=end_date]").setValue(MiladiToShamsi(record.data.end_date));
		
		if(!record.data.IsEditable)
		{
			this.infoForm.down("[name=start_date]").disable();
			this.infoForm.down("[name=first_value]").disable();
			this.infoForm.down("[name=bank_id]").disable();
			this.infoForm.down("[name=salary_item_type_id]").disable();
		}
		else
		{
			this.infoForm.down("[name=start_date]").enable();
			this.infoForm.down("[name=first_value]").enable();
			this.infoForm.down("[name=bank_id]").enable();
			this.infoForm.down("[name=salary_item_type_id]").enable();
		}
	}
	else
	{
		this.infoForm.getForm().reset();
		this.infoForm.down("[name=start_date]").enable();
		this.infoForm.down("[name=first_value]").enable();
		this.infoForm.down("[name=bank_id]").enable();
		this.infoForm.down("[name=salary_item_type_id]").enable();
	}	
	
	if(this.subtract_type != <?= SUBTRACT_TYPE_LOAN?>)
	{
		this.infoForm.down("[name=contract_no]").hide();
		this.infoForm.down("[name=loan_no]").hide();
	}
	
	this.infoWin.show();
	this.infoWin.center();
}

Subtract.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش',
			modal : true,
			autoScroll : true,
			width: 650,
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
	
	mask = new Ext.LoadMask(this.HistoryWin, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	this.HistoryWin.loader.load({
		params : {
			subtract_id : this.grid.getSelectionModel().getLastSelected().data.subtract_id
		},
		callback : function(){mask.hide();}
	});
}

//..........................................................

Subtract.prototype.Flows = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.flowGrid.getStore().proxy.extraParams["subtract_id"] = record.data.subtract_id;
	if(this.flowGrid.rendered)
		this.flowGrid.getStore().load();
		
	if(!this.FlowsWin)
	{
		this.FlowsWin = new Ext.window.Window({
			autoScroll : true,
			modal : true,
			width : 740,
			bodyStyle: 'background:white; padding:10px;',
			minHeight : 300,
			title : "گردش های دستی",
			closeAction : "hide",
			items : this.flowGrid,
			buttons : [{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){
					this.up('window').hide();
				}
			}]
		});
	}
	this.FlowsWin.show();
	this.FlowsWin.center();
	this.subtract_id = record.data.subtract_id;
}

Subtract.deleteRender = function(v,p,record)
{
	if(record.data.IsEditable)
		return  "<div title='حذف اطلاعات' class='remove' onclick='SubtractObj.RemoveFlow();' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;height:16'></div>";
}

Subtract.prototype.BeforeAddFlow = function(){
		
	var modelClass = this.flowGrid.getStore().model;
	var record = new modelClass({
		row_no : "",
		subtract_id : this.subtract_id,
		amount : "",
		flow_type : "3",
		flow_coaf : "1",
		tempFlow : "1" ,
		comments : "" 		
	});
	this.flowGrid.plugins[0].cancelEdit();
	this.flowGrid.getStore().insert(0, record);
	this.flowGrid.plugins[0].startEdit(0, 0);
	
}

Subtract.prototype.SaveFlow = function(store,record)
{
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'subtracts.data.php?task=saveSubtractFlow',
		method: 'POST',
		params: {
			record : Ext.encode(record.data)
		},

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				SubtractObj.flowGrid.getStore().load();
				SubtractObj.grid.getStore().load();
			}
			else {
				alert('wewe');
				alert(st.data); 
				}
		},
		failure: function(){}
	});
}

Subtract.prototype.RemoveFlow = function(){
	
	if(!confirm("آیا مایل به حذف می باشید؟"))
		return;

	var record = this.flowGrid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال حذف...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'subtracts.data.php?task=RemoveSubtractFlow',
		params:{
			row_no: record.data.row_no
		},
		method: 'POST',

		success: function(response){
			mask.hide();
			SubtractObj.flowGrid.getStore().load();
			SubtractObj.grid.getStore().load();
		},
		failure: function(){}
	});
}

</script>