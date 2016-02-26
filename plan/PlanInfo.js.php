<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.11
//-----------------------------
	
PlanInfo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	PlanID : <?= $PlanID ?>,
	RequestRecord : null,
	User : '<?= $User ?>',
	portal : <?= isset($_REQUEST["portal"]) ? "true" : "false" ?>,
	readOnly : <?= $readOnly ? "true" : "false" ?>,
	StatusID : <?= $PlanObj->StatusID ?>,
	
	GroupForms : {},

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function PlanInfo(){
	
	this.tree = new Ext.tree.Panel({
		store: new Ext.data.TreeStore({
			proxy: {
				type: 'ajax',
				url: this.address_prefix + 'plan.data.php?task=selectGroups&PlanID=' + this.PlanID
			}					
		}),
		root: {id: 'src'},
		rootVisible: false,
		autoScroll : true,
		width : 758,
		height : 120,
		listeners : {
			itemclick : function(v,record){
				if(!record.data.leaf) return; 
				PlanInfoObject.LoadElements(record);
			},
			itemcontextmenu : function(view, record, item, index, e){
				PlanInfoObject.ShowMenu(view, record, item, index, e);
			}
		}
	});
	
	this.itemsPanel = new Ext.panel.Panel({
		bodyStyle : 'padding:4px;',
		height : this.portal ? 381 : 478,
		width: 758,
		autoScroll : true
	});

   this.MainPanel =  new Ext.panel.Panel({
		applyTo : this.get("mainForm"),
		width: 760,
		height : this.portal ? 530 : 600,
		items : [this.tree,this.itemsPanel],
		tbar : [{
			text : "مشاهده ردیف های دارای اطلاعات",
			iconCls : "list",
			itemId : "btn_filled",
			enableToggle : true,
			handler : function(){
				PlanInfoObject.itemsPanel.items.each(function(item){item.hide();});
				PlanInfoObject.tree.getStore().load({
					params : {
						filled : this.pressed ? "true" : "false"
					}
				});
			}
		}]
	});	
	
	if(this.User == "Customer" && !this.readOnly)
	{
		this.MainPanel.getDockedItems()[0].add(['-',{
			text : "ارسال طرح جهت ارزیابی",
			iconCls : "send",
			handler : function(){
				PlanInfoObject.BeforeSendPlan(2);
			}
		}]);
	}
	if(this.User == "Staff")
	{
		if( new Array(1,3,5).indexOf(this.StatusID*1) != -1)
			return;
			
		if(this.StatusID == "2")
		{
			this.MainPanel.getDockedItems()[0].add(['-',{
				text : "تایید اولیه طرح و شروع گردش داخلی",
				iconCls : "send",
				handler : function(){
					PlanInfoObject.BeforeSendPlan(4);
				}
			}]);
		}
		this.MainPanel.getDockedItems()[0].add(['-',{
			text : "برگشت به مشتری جهت انجام اصلاحات",
			iconCls : "undo",
			handler : function(){
				PlanInfoObject.BeforeSendPlan(5);
			}
		},'-',{
			text : "رد طرح",
			iconCls : "cross",
			handler : function(){
				PlanInfoObject.BeforeSendPlan(3);
			}
		}]);
	}
}

PlanInfoObject = new PlanInfo();

PlanInfo.prototype.LoadElements = function(record, season){

	var parentEl = this.itemsPanel;
	parentEl.items.each(function(item){item.hide();});
	
	var mask2 = new Ext.LoadMask(parentEl, {msg:'در حال بارگذاري...'});
	mask2.show();
	
	var frm = null;
	eval("frm = this.GroupForms.elem_" + record.data.id);
	if(frm == null)
	{
		eval("this.GroupForms.elem_" + record.data.id + " = new Array();");
		this.store = new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				extraParams : {
					GroupID : record.data.id,
					PlanID : this.PlanID
				},
				url: this.address_prefix + "plan.data.php?task=SelectElements",
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields : ["ElementID", "ParentID", "GroupID", "ElementTitle", "ElementType", 
				"properties", "EditorProperties", "ElementValue", "values"],
			autoLoad : true,
			listeners :{
				load : function(){
					PlanInfoObject.MakeElemForms(this , season);
					mask2.hide();
				}
			}
		});
	}
	else
	{
		for(i=0; i<frm.length; i++)
			parentEl.down("[itemId=" + frm[i] + "]").show();
		mask2.hide();
	}
	
} 

function merge(obj1,obj2){
    var obj3 = {};
    for (var attrname in obj1) { obj3[attrname] = obj1[attrname]; }
    for (var attrname in obj2) { obj3[attrname] = obj2[attrname]; }
    return obj3;
}

PlanInfo.prototype.MakeElemForms = function(store, season){

	var parentEl = this.itemsPanel;
	
	for(var i=0; i < store.getCount(); i++)
	{
		record = store.getAt(i);
		switch(record.data.ElementType)
		{
			case "panel" : 
				btn = {
					text : "ذخیره",
					iconCls : "save",
					handler : function(){
						mask = new Ext.LoadMask(parentEl, {msg:'در حال ذخيره سازي...'});
						mask.show();    
						this.up('form').getForm().submit({
							url:  PlanInfoObject.address_prefix + 'plan.data.php?task=SavePlanItems',
							method: 'POST',
							params : {
								PlanID : PlanInfoObject.PlanID,
								ElementID : this.up('form').itemId.split("_")[1]
							},
							success: function(form,result){
								record = PlanInfoObject.tree.getSelectionModel().getSelection()[0];
								record.set("cls","filled");
								mask.hide();
							},
							failure: function(){}
						});
					}
				};
				NewElement = {
					xtype : "form",
					frame : true,
					itemId : "element_" + record.data.ElementID,
					style : "margin-bottom:4px",
					buttons : [!this.readOnly ? btn : ""]
				};
				break;
			//..................................................................
			case "grid" :
				
				var fields = new Array();
				var columns = [ {dataIndex : "RowID",hidden : true},
								{dataIndex : "PlanID",hidden : true},
								{dataIndex : "ElementID",hidden : true}];
				while(true)
				{
					i++;
					var sub_record = store.getAt(i);
					if(sub_record == null || sub_record.data.ParentID != record.data.ElementID)
					{
						i--;
						break;
					}
					var editor = {xtype : sub_record.data.ElementType};
					if(sub_record.data.ElementType == "combo")
					{
						arr = sub_record.data.values.split("#");
						data = [];
						for(j=0;j<arr.length;j++)
							data.push([ arr[j] ]);
						editor.store = new Ext.data.SimpleStore({
							fields : ['value'],
							data : data
						});
						editor.displayField = "value";
						editor.valueField = "value";
					}
					eval("editor = merge(editor,{" + sub_record.data.EditorProperties + "});");
					
					NewColumn = {
						menuDisabled : true,
						sortable : false,
						text : sub_record.data.ElementTitle,
						dataIndex : "element_" + sub_record.data.ElementID,
						editor : editor						
					};
					if(sub_record.data.ElementType == "currencyfield")
						NewColumn.renderer = Ext.util.Format.Money;
					if(sub_record.data.ElementType == "currencyfield" || 
						sub_record.data.ElementType == "numberfield")
						NewColumn.editor.hideTrigger = "true";
					
					
					eval("NewColumn = merge(NewColumn,{" + sub_record.data.properties + "});");
					columns.push(NewColumn);
					fields.push("element_" + sub_record.data.ElementID);
				}
				NewElement = {
					xtype : "grid",
					viewConfig: {
						stripeRows: true,
						enableTextSelection: true
					},					
					selType : 'rowmodel',
					scroll: 'vertical', 
					itemId : "element_" + record.data.ElementID,
					store : new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + "plan.data.php?task=SelectPlanItems",
							reader: {root: 'rows',totalProperty: 'totalCount'},
							extraParams : {
								PlanID : this.PlanID,
								ElementID : record.data.ElementID
							}							
						},
						fields : ["RowID", "PlanID", "ElementID"].concat(fields),
						autoLoad : true,
						listeners : {
							update : function(store,record){
								mask = new Ext.LoadMask(parentEl, {msg:'در حال ذخيره سازي...'});
								mask.show();    
								Ext.Ajax.request({
									url:  PlanInfoObject.address_prefix + 'plan.data.php?task=SavePlanItems',
									params:{
										record : Ext.encode(record.data)
									},
									method: 'POST',
									success: function(response,option){
										mask.hide();
										store.load();
									},
									failure: function(){}
								});
								return true;
							}
						}
					}),
					columns: columns
				};
				
				if(!this.readOnly)
				{
					NewElement.plugins = [new Ext.grid.plugin.RowEditing()];
					NewElement.tbar = [{
						text : "ایجاد ردیف",
						iconCls : "add",
						handler : function(){
							var grid = this.up('grid');
							var modelClass = grid.getStore().model;
							var record = new modelClass({
								RowID : null,
								PlanID : PlanInfoObject.PlanID,
								ElementID : grid.getStore().proxy.extraParams.ElementID
							});
							grid.plugins[0].cancelEdit();
							grid.getStore().insert(0, record);
							grid.plugins[0].startEdit(0, 0);
						}
					},'-',{
						text : "ویرایش ردیف",
						iconCls : "edit",
						handler : function(){
							var grid = this.up('grid');
							var record = grid.getSelectionModel().getLastSelected();
							if(record == null)
							{
								Ext.MessageBox.alert("","ابتدا ردیف مورد نظر را انتخاب کنید");
								return;
							}
							grid.plugins[0].startEdit(grid.getStore().indexOf(record),0);
						}
					},'-',{
						text : "حذف ردیف",
						iconCls : "remove",
						handler : function(){
							var grid = this.up('grid');
							var record = grid.getSelectionModel().getLastSelected();
							if(record == null)
							{
								Ext.MessageBox.alert("","ابتدا ردیف مورد نظر را انتخاب کنید");
								return;
							}

							Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
								if(btn == "no")
									return;
								var mask = new Ext.LoadMask(parentEl, {msg:'در حال ذخيره سازي...'});
								mask.show();    
								Ext.Ajax.request({
									url:  PlanInfoObject.address_prefix + 'plan.data.php?task=DeletePlanItem',
									params:{
										RowID : record.data.RowID
									},
									method: 'POST',
									success: function(response,option){
										mask.hide();
										grid.getStore().load();
									},
									failure: function(){}
								});
							});
						}
					}];
				}
				
				break;
			//..................................................................
			case "radio" :
				record.data.ElementValue = record.data.ElementValue== "" ? -1 : record.data.ElementValue;
				var items = new Array();
				values = record.data.values.split('#');
				for(j=0;j<values.length;j++)
					items.push({
						boxLabel : values[j],
						name : "element_" + record.data.ElementID,
						inputValue : j,
						readOnly : this.readOnly,
						checked : record.data.ElementValue == j ? true : false
					});
				NewElement = {
					xtype : "radiogroup",
					fieldLabel : record.data.ElementTitle,
					itemId : "element_" + record.data.ElementID,
					items : items,					
					columns: values.length
				};
				break;
			case "displayfield":
				NewElement = {
					xtype : record.data.ElementType,
					fieldLabel : record.data.ElementTitle,
					value : record.data.values.replace(/\n/g,"<br>"),
					fieldCls : "desc"
				};
				break;				
			default : 
				NewElement = {
					xtype : record.data.ElementType,
					readOnly : this.readOnly,
					fieldLabel : record.data.ElementTitle,
					itemId : "element_" + record.data.ElementID,
					name : "element_" + record.data.ElementID,
					value : record.data.ElementValue
				};
		}
		
		eval("NewElement = merge(NewElement,{" + record.data.properties + "});");
		
		if(record.data.ParentID == 0)
		{
			eval("PlanInfoObject.GroupForms.elem_" + record.data.GroupID + ".push('element_" + record.data.ElementID + "');");
			parentEl.add(NewElement);
		}
		else
		{
			var parent = this.itemsPanel.down("[itemId=element_" + record.data.ParentID + "]");
			parent.add(NewElement);
		}
	}
}

PlanInfo.prototype.ShowMenu = function(view, record, item, index, e)
{
	if(this.User == "Customer")
		retirn;
	e.stopEvent();
	e.preventDefault();
	view.select(index);

	this.Menu = new Ext.menu.Menu();

	if(record.data.id == "src" || !record.isLeaf())
		return;
	
	this.Menu.add({
		text: 'تایید اطلاعات',
		iconCls: 'tick',
		handler : function(){PlanInfoObject.BeforeSurveyGroup('CONFIRM', record);}
	},{
		text: 'رد اطلاعات',
		iconCls: 'cross',
		handler : function(){PlanInfoObject.BeforeSurveyGroup('REJECT', record);}
	},{
		text: 'سابقه',
		iconCls: 'history',
		handler : function(){PlanInfoObject.ShowHistory(record);}
	});
	
	var coords = e.getXY();
	this.Menu.showAt([coords[0]-120, coords[1]]);
}

PlanInfo.prototype.BeforeSurveyGroup = function(mode, record){
	
	if(mode == "CONFIRM")
	{
		Ext.MessageBox.confirm("","آیا مایل به تایید می باشید؟", function(btn){
			if(btn == "no")
				return;
			
			PlanInfoObject.SurveyGroup(mode, "", record);
		});
		return;
	}
	if(!this.commentWin)
	{
		this.commentWin = new Ext.window.Window({
			width : 412,
			height : 198,
			modal : true,
			title : "توضیحات رد اطلاعات",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "ActDesc"
			}],
			closeAction : "hide",
			buttons : [{
				text : "رد اطلاعات",				
				iconCls : "cross",
				itemId : "btn_reject"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin);
	}
	this.commentWin.down("[itemId=btn_reject]").setHandler(function(){
		PlanInfoObject.SurveyGroup('REJECT', this.up('window').down("[name=ActDesc]").getValue(), record);
	});
	this.commentWin.show();
	this.commentWin.center();
}

PlanInfo.prototype.SurveyGroup = function(mode, ActDesc, record){
	
	mask = new Ext.LoadMask(this.itemsPanel, {msg:'در حال بارگذاري...'});
	mask.show();
	
	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "plan.data.php",
		params : {
			task : "SurveyGroup",
			PlanID : this.PlanID,
			GroupID : record.data.id,
			mode : mode,
			ActDesc : ActDesc
		},
		
		success : function(){
			
			if(PlanInfoObject.commentWin)
				PlanInfoObject.commentWin.hide();
			PlanInfoObject.itemsPanel.items.each(function(item){item.hide();});
			var btn = PlanInfoObject.MainPanel.down("[itemId=btn_filled]");
			PlanInfoObject.tree.getStore().load({
				params : {
					filled : btn.pressed ? "true" : "false"
				}
			});
			mask.hide();
		}
	});
}

PlanInfo.prototype.BeforeSendPlan = function(StatusID){
	
	if(StatusID == "2")
	{
		Ext.MessageBox.confirm("", "پس از ارسال طرح دیگر قادر به ویرایش طرح نمی باشید.<br>آیا مایل به ارسال می باشید؟", 
		function(btn){
			if(btn == "no")
				return;
			PlanInfoObject.SendPlan(StatusID, "");
		});
		return;
	}
	if(StatusID == "4")
	{
		Ext.MessageBox.confirm("", "آیا مایل به تایید می باشید؟", 
		function(btn){
			if(btn == "no")
				return;
			PlanInfoObject.SendPlan(StatusID, "");
		});
		return;
	}
	
	if(!this.commentWin2)
	{
		this.commentWin2 = new Ext.window.Window({
			width : 412,
			height : 198,
			modal : true,
			title : "توضیحات رد اطلاعات",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "ActDesc"
			}],
			closeAction : "hide",
			buttons : [{
				text : "رد طرح",				
				iconCls : "cross",
				hidden : true,
				itemId : "btn_reject"
			},{
				text : "برگشت طرح به مشتری",				
				hidden : true,
				iconCls : "undo",
				itemId : "btn_return"
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.commentWin2);
	}
	if(StatusID == "3")
	{
		this.commentWin2.down("[itemId=btn_reject]").show();
		this.commentWin2.down("[itemId=btn_reject]").setHandler(function(){
			PlanInfoObject.SendPlan(StatusID, this.up('window').down("[name=ActDesc]").getValue());
		});
	}
	if(StatusID == "5")
	{
		this.commentWin2.down("[itemId=btn_return]").show();
		this.commentWin2.down("[itemId=btn_return]").setHandler(function(){
			PlanInfoObject.SendPlan(StatusID, this.up('window').down("[name=ActDesc]").getValue());
		});
	}
	this.commentWin2.show();
	this.commentWin2.center();
}

PlanInfo.prototype.SendPlan = function(StatusID, ActDesc){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاري...'});
	mask.show();

	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "plan.data.php",
		params : {
			task : "ChangeStatus",
			PlanID : this.PlanID,
			StatusID : StatusID,
			ActDesc : ActDesc
		},

		success : function(response){
			mask.hide();
			if(PlanInfoObject.commentWin2)
				PlanInfoObject.commentWin2.hide();
			
			result = Ext.decode(response.responseText);
			if(result.success)
			{
				if(PlanInfoObject.portal)
					portal.OpenPage("../plan/NewPlan.php");
				else
				{
					framework.CloseTab(PlanInfoObject.TabID);
					if(typeof ManagePlanObject == "object")
						ManagePlanObject.grid.getStore().load();
				}
			}
			else
			{
				if(result.data != "")
					Ext.MessageBox.alert("Error", result.data);
				else
					Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
			}
		}
	});
}

PlanInfo.prototype.ShowHistory = function(record){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش طرح',
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
			PlanID : this.PlanID,
			GroupID : record.data.id
		}
	});
}

</script>
