<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.08
//-----------------------------
 
require_once '../header.inc.php';
require_once 'form.class.php';
require_once 'wfm.class.php';

if (!empty($_REQUEST['RequestID'])) 
{
	$RequestID = $_REQUEST['RequestID'];
	$ReqObj = new WFM_requests($RequestID);
	$FormID = $ReqObj->FormID;
	$SmsSend = $ReqObj->_SmsSend == "YES" ? true : false;
	$flowInfo = WFM_FlowRows::GetFlowInfo($ReqObj->_FlowID, $ReqObj->RequestID);
	$StepRowID = $flowInfo["StepRowID"];
}
else
{
	$RequestID = "";
	$FormID = !empty($_REQUEST['FormID']) ? $_REQUEST['FormID'] : "";
	$formObj = new WFM_forms($FormID);
	$SmsSend = $formObj->SmsSend == "YES" ? true : false;
	$StepRowID = 0;
}

$LoanRequestID = !empty($_REQUEST["LoanRequestID"]) ? $_REQUEST["LoanRequestID"] : "0";

?>
<script type="text/javascript">
function merge(obj1,obj2){
    var obj3 = {};
    for (var attrname in obj1) { obj3[attrname] = obj1[attrname]; }
    for (var attrname in obj2) { obj3[attrname] = obj2[attrname]; }
    return obj3;
}

WFM_NewRequest.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix: "<?= $js_prefix_address ?>",
	TplItemSeperator: "<?= WFM_forms::TplItemSeperator ?>",
	parentHandler : <?= !empty($_REQUEST["parentHandler"]) ? $_REQUEST["parentHandler"] : "function(){}" ?>,
	
	RequestID : "<?= $RequestID ?>",
	FormID : "<?= $FormID ?>",
	SmsSend : <?= $SmsSend ? "true" : "false"  ?>,
	StepRowID : "<?= $StepRowID ?>",
	preview : <?= isset($_REQUEST["preview"]) ? "true" : "false" ?>,
	
	LoanRequestID : <?= $LoanRequestID?>,
	
	ItemsMmask : null,
	
	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
}

function WFM_NewRequest() {
	
	this.MainForm = new Ext.form.Panel({
		plain: true,            
		frame: false,
		border : false,
		width: 700,
		autoHeight : true,
		minHeight : 200,
		renderTo: this.get("FormDIV"),
		items: [{
			xtype: "container",
			itemId: "FormItems",
			width : 700,
			style : "text-align:right"
		},{
			xtype: "hidden",
			itemId: "RequestID",
			name : "RequestID",
			value : this.RequestID
		}],
		buttons: [{
			text: "ذخیره موقت",
			hidden : this.preview,
			handler: function () {
				WFM_NewRequestObj.SaveRequest(false, false);
			},
			iconCls: "save"
		},{
			text: "ذخیره و ارسال فرم",
			hidden : this.preview,
			handler: function () {
				WFM_NewRequestObj.SaveRequest(false, true);
			},
			iconCls: "send"
		}, {
			text: "  مشاهده",
			itemId : "btn_view",
			handler: function () {
				WFM_NewRequestObj.SaveRequest(true, false);
			},
			iconCls: "print"
		}]
	});
	if(this.RequestID != "")
	{
		this.historyDiv = new Ext.form.Panel({
			plain: true,            
			title : "سابقه گردش فرم",
			width: 300,
			autoHeight : true,
			autoScroll : true,
			minHeight : 400,
			renderTo: this.get("historyDiv"),
			loader : {
				url : this.address_prefix + "history.php?RequestID=" + this.RequestID,
				scripts : true,
				autoLoad : true,
				callback : function(){
					WFM_NewRequestObj.historyDiv.doLayout();
				}
			}
		});
	}
	
	this.FormItemsStore = new Ext.data.Store({
		fields: ['FormItemID',"GroupID","GroupDesc","FormID", 'ItemName',
			"DisplayType","DisplayField","DisplayValue", 'ItemType', "ComboValues", "access"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + "form.data.php?task=selectFormItems"+
					"&RequestID=" + this.RequestID + "&RequestMode=true&NotGlobal=true&StepRowID=" + this.StepRowID,
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		},
		pageSize: 500
	});
	
	this.ReqItemsStore = new Ext.data.Store({
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + 'form.data.php?task=GetRequestItems',
			reader: {root: 'rows', totalProperty: 'totalCount'}
		},
		fields: ['ReqItemID', 'RequestID','FormID', 'FormItemID', 'ItemValue', 'ItemType', "ComboValues"]
	});
	
	this.ColumnsStore = new Ext.data.Store({
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + 'form.data.php?task=selectGridColumns&FormID=' + this.FormID,
			reader: {root: 'rows', totalProperty: 'totalCount'}
		},
		fields: ['FormItemID', 'ColumnID','ItemName', 'ItemType', "ComboValues", "EditorProperties", "properties"],
		autoLoad : true
	});
	
	var t = setInterval(function(){
		if(!WFM_NewRequestObj.ColumnsStore.isLoading())
		{
			clearInterval(t);
			if(WFM_NewRequestObj.RequestID > 0)
				WFM_NewRequestObj.LoadRequest();
			else
				WFM_NewRequestObj.FormSelect(WFM_NewRequestObj.FormID);
		}
	}, 1000);
}

WFM_NewRequest.prototype.FormSelect = function(FormID){
	
	fieldset = this.MainForm.down("[itemId=FormItems]");
	this.ItemsMmask = new Ext.LoadMask(fieldset, {msg:'در حال بارگذاری...'});
	this.ItemsMmask.show();
	this.FormItemsStore.load({
		params : {FormID : FormID},
		callback : function(){
			WFM_NewRequestObj.ShowTplItemsForm();
		}
	});
}

WFM_NewRequest.prototype.LoadRequest = function(){
	
	fieldset = this.MainForm.down("[itemId=FormItems]");
	this.ItemsMmask = new Ext.LoadMask(fieldset, {msg:'در حال بارگذاری...'});
	this.ItemsMmask.show();
	
	this.ReqItemsStore.load({
		params : {RequestID : this.RequestID},
		callback : function(){
			
			me = WFM_NewRequestObj;
			if(this.totalCount == 0)
			{
				me.FormItemsStore.load({
					params : {FormID : me.FormID},
					callback : function(){
						WFM_NewRequestObj.ShowTplItemsForm();
						WFM_NewRequestObj.ItemsMmask.hide();
					}
				});				
				return;
			}				
			me.FormItemsStore.load({
				params : {FormID : me.FormID},
				callback : function(){
					me = WFM_NewRequestObj;
					me.ShowTplItemsForm();	
					for(i=0; i<me.ReqItemsStore.getCount(); i++){
						record = me.ReqItemsStore.getAt(i);
						switch(record.data.ItemType){
							case "grid":
								continue;
							case "shdatefield" :
								me.MainForm.getComponent("FormItems").
									down('[name=ReqItem_' + record.data.FormItemID + "]").setValue(MiladiToShamsi(record.data.ItemValue));
								break;
							case "checkbox" :
								if(record.data.ComboValues == null)
									me.MainForm.getComponent("FormItems").
										down('[name=ReqItem_' + record.data.FormItemID + "]").setValue(record.data.ItemValue);
								else
									me.MainForm.getComponent("FormItems").
										down('[name=ReqItem_' + record.data.FormItemID + "_checkbox_" + record.data.ItemValue + "]").setValue(true);
								break; 
							default :
								me.MainForm.getComponent("FormItems").
									down('[name=ReqItem_' + record.data.FormItemID + "]").setValue(record.data.ItemValue);
						}
					};
					
					WFM_NewRequestObj.ItemsMmask.hide();
				}
			});
		}
	});
}

var WFM_NewRequestObj = new WFM_NewRequest();

WFM_NewRequest.prototype.SaveRequest = function (print, sending) {

	if(!this.MainForm.getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();
	
	this.MainForm.getForm().submit({
		
		url: this.address_prefix + 'form.data.php?task=SaveRequest',
		method: 'POST',
		params : {
			FormID : this.FormID,
			sending : sending
		},
		
		success: function (form,action) {
			mask.hide();
			st = action.result.data.split("-");
			WFM_NewRequestObj.RequestID = st[0];
			
			if(typeof DraftLetterObject == "object")
				WFM_MyRequestsObject.grid.getStore().load();
			
			WFM_NewRequestObj.MainForm.getComponent('RequestID').setValue(WFM_NewRequestObj.RequestID);
			if (print) 
			{
				window.open(WFM_NewRequestObj.address_prefix + 'PrintRequest.php?RequestID=' + 
						WFM_NewRequestObj.RequestID);
			}
			if(sending)
			{
				if(!WFM_NewRequestObj.SmsSend)
				{
					Ext.MessageBox.alert("", "فرم شما با موفقیت ارسال گردید");
					WFM_NewRequestObj.parentHandler();
				}
				else
					WFM_NewRequestObj.ConfirmSms(WFM_NewRequestObj.RequestID, st[1]);
			}
			
		},
		failure : function(form,action){
			mask.hide();
			if(action.result.data != "")
				Ext.MessageBox.alert('', action.result.data);
			else
				Ext.MessageBox.alert('', 'خطا در اجرای عملیات');
		}
	});
}

WFM_NewRequest.prototype.ConfirmSms = function (RequestID, mobile){

	if(!this.SmsWin)
	{
		this.SmsWin = new Ext.window.Window({
			width : 400,
			title : "تایید کد امنیتی",
			bodyStyle : "background-color:white;text-align:-moz-center",
			height : 250,
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "container",
				style : "font-size: 18px; color: darkcyan; margin: 10px 20px 10px 20px;",
				html : "با توجه به دلایل امنیتی ارسال این فرم با تایید کد امنیتی می باشد، یک کد شش رقمی به شماره پیامک ثبت شده شما در سیستم ارسال گردید لطفا کد دریافتی را در باکس زیر وارد کنید"
			},{
				xtype : "displayfield",
				fieldLabel : "شماره پیامک ارسالی",
				style : "text-align:center;font-size:20px;color: darkcyan;font-weight:bold",
				fieldStyle : "text-align:center;font-size:20px;color: darkcyan;font-weight:bold",
				itemId : "mobile",
				value : mobile
			},{
				xtype : "numberfield",
				name : "code",
				hideTrigger : true,
				height : 30,
				fieldStyle : "text-align:center;font-size:20px;color: darkcyan;font-weight:bold"
			},{
				xtype : "button",
				width : 150,
				style : "font-size:20px",
				text : "ارسال کد امنیتی",
				handler : function(){
					me = WFM_NewRequestObj;
					mask = new Ext.LoadMask(me.SmsWin, {msg:'در حال ذخيره سازي...'});
					mask.show();
					Ext.Ajax.request({
						url: me.address_prefix + 'form.data.php?task=CheckConfirmationCode',
						method: 'POST',
						params : {
							RequestID : RequestID,
							code : me.SmsWin.down("[name=code]").getValue()
						},
						success: function (response) {
							mask.hide();
							res = Ext.decode(response.responseText);
							if(res.success)
							{
								Ext.MessageBox.alert("", "فرم شما با موفقیت ارسال گردید");
								WFM_NewRequestObj.SmsWin.hide();
								WFM_NewRequestObj.parentHandler();
							}
							else
							{
								switch(res.data){
									case "ExpireCode":
										Ext.MessageBox.alert('ERROR', "اعتبار کد ارسالی به پایان رسیده است.");
										WFM_NewRequestObj.SmsWin.hide();
										break;
									case "MaxTry":
										Ext.MessageBox.alert('ERROR', "شما بیش از حد مجاز کد اشتباه وارد کرده اید");
										WFM_NewRequestObj.SmsWin.hide();
										break;
									case "WrongeCode":
										Ext.MessageBox.alert('ERROR', "کد وارد شده مطابق با کد ارسالی نیست");
										WFM_NewRequestObj.SmsWin.down("[name=code]").setValue();
										break;
									default:
										Ext.MessageBox.alert('ERROR', res.data);
								}								
							}
						}
					});
				}
			}]
			
		});
		Ext.getCmp(this.TabID).add(this.SmsWin);
	}
	this.SmsWin.show();
	this.SmsWin.center();
	this.SmsWin.down("[name=code]").setValue();
	this.SmsWin.down("[itemId=mobile]").setValue(mobile);
}

WFM_NewRequest.prototype.MakeGrid = function (SrcRecord){

	var fields = new Array();
	var columns = [ 
		{dataIndex : "ReqItemID",hidden : true},
		{dataIndex : "ColumnID",hidden : true},
		{dataIndex : "ItemValue",hidden : true}];
	
	for(var i=0; i<this.ColumnsStore.totalCount; i++)
	{
		record = this.ColumnsStore.getAt(i);
		if(record.data.FormItemID != SrcRecord.data.FormItemID)
			continue;
		
		var editor = {xtype : record.data.ItemType};
		if(record.data.ElementType == "datefield")
			editor.format = "Y/m/d";
		if(record.data.ItemType == "combo")
		{
			arr = record.data.ComboValues.split("#");
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

		if(record.data.EditorProperties != null)
			eval("editor = merge(editor,{" + record.data.EditorProperties + "});");

		NewColumn = {
			menuDisabled : true,
			sortable : false,	
			text : record.data.ItemName,
			dataIndex : "column_" + record.data.ColumnID,
			editor : editor						
		};
		if(record.data.ItemType == "currencyfield")
		{
			NewColumn.type = "numbercolumn";
			NewColumn.renderer = Ext.util.Format.Money;
			NewColumn.summaryType = "sum";
			NewColumn.summaryRenderer = Ext.util.Format.Money;
		}	
		if(record.data.ItemType == "currencyfield" || 
			record.data.ItemType == "numberfield")
			NewColumn.editor.hideTrigger = "true";

		if(record.data.properties != null)
			eval("NewColumn = merge(NewColumn,{" + record.data.properties + "});");

		columns.push(NewColumn);
		if(record.data.ItemType == "currencyfield")
			fields.push({name : "column_" + record.data.ColumnID, type : "int"});
		else
			fields.push("column_" + record.data.ColumnID);
	}
	
	NewElement = {
		xtype : "grid",
		minHeight : 160,
		title : SrcRecord.data.ItemName,
		features: [{ftype: 'summary'}],
		viewConfig: {
			stripeRows: true,
			enableTextSelection: true
		},					
		selType : 'rowmodel',
		bbar: new Ext.ExtraBar({store: Ext.getCmp(Ext.id),displayInfo: true}),
		scroll: 'vertical', 
		store : new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + "form.data.php?task=SelectGridRows",
				reader: {root: 'rows',totalProperty: 'totalCount'},
				extraParams : {
					RequestID : this.RequestID,
					FormItemID : SrcRecord.data.FormItemID					
				}							
			},
			fields : ["ReqItemID", "RequestID", "FormItemID"].concat(fields),
			autoLoad : true,
			listeners : {
				update : function(store,record){
					
					if(WFM_NewRequestObj.RequestID*1 == 0)
						WFM_NewRequestObj.SaveRequest(false, false);
					
					var t = setInterval(function(){
						if(WFM_NewRequestObj.RequestID*1 > 0)
						{
							clearInterval(t);
							record.data.RequestID = WFM_NewRequestObj.RequestID;
							store.proxy.extraParams.RequestID = WFM_NewRequestObj.RequestID;
							
							mask = new Ext.LoadMask(WFM_NewRequestObj.MainForm, {msg:'در حال ذخيره سازي...'});
							mask.show();    
							Ext.Ajax.request({
								url:  WFM_NewRequestObj.address_prefix + 'form.data.php?task=SaveGridRow',
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
						}
					}, 1000);
					
					return true;
				}
			}
		}),
		columns: columns,
		listeners : {
			beforerender : function(){
				var ExtraToolbar = this.getDockedItems('extrabar');
				if(ExtraToolbar.length > 0)
					ExtraToolbar[0].bind(this.getStore());
				}
		}
	};

	if(SrcRecord.data.access == "YES")
	{
		NewElement.plugins = [new Ext.grid.plugin.RowEditing()];
		NewElement.tbar = [{
			text : "ایجاد ردیف",
			iconCls : "add",
			handler : function(){
				var grid = this.up('grid');
				var modelClass = grid.getStore().model;
				var record = new modelClass({
					ReqItemID : null,
					RequestID : WFM_NewRequestObj.RequestID,
					FormItemID : grid.getStore().proxy.extraParams.FormItemID
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
					var mask = new Ext.LoadMask(WFM_NewRequestObj.MainForm, {msg:'در حال ذخيره سازي...'});
					mask.show();    
					Ext.Ajax.request({
						url:  WFM_NewRequestObj.address_prefix + 'form.data.php?task=DeleteGridRow',
						params:{
							ReqItemID : record.data.ReqItemID
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
	
	return NewElement;
}

WFM_NewRequest.prototype.ShowTplItemsForm = function () {

	this.MainForm.getComponent("FormItems").removeAll();
	var CurGroupID = 0;
	var parent = null;
	for(i=0; i<this.FormItemsStore.getCount(); i++)
	{
		record = this.FormItemsStore.getAt(i);
		if(record.data.ItemType == "" || record.data.FormID == "0")
			continue;
		
		if(CurGroupID != record.data.GroupID)
		{
			this.MainForm.getComponent("FormItems").add({
				xtype : "fieldset",
				title : record.data.GroupDesc,
				itemId : "Group_" + record.data.GroupID,
				width : 680,
				defaults : {labelWidth:200}
			});
			parent = this.MainForm.down("[itemId=Group_" + record.data.GroupID + "]");
			CurGroupID = record.data.GroupID;
		}
		//...........................................
		if(record.data.ItemType === "loan")
		{
			this.LoanCmp = new Ext.form.ComboBox({
				store: new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: '/loan/request/request.data.php?task=SelectMyRequests&mode=customer',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ['PartAmount',"RequestID","ReqAmount","ReqDate", "RequestID", "CurrentRemain","IsEnded",{
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
				tpl: new Ext.XTemplate(
					'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
					'<td style="padding:7px">کد وام</td>',
					'<td style="padding:7px">مبلغ وام</td>',
					'<td style="padding:7px">تاریخ پرداخت</td> </tr>',
					'<tpl for=".">',
						'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
						'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
						'<td style="border-left:0;border-right:0" class="search-item">',
							'{[Ext.util.Format.Money(values.ReqAmount)]}</td>',
						'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.ReqDate)]}</td> </tr>',
					'</tpl>',
					'</table>'
				),
				readOnly : record.data.access == "NO" ? true : false,
				width : 610,
				itemId: 'ReqItem_' + record.data.FormItemID,
				name: 'ReqItem_' + record.data.FormItemID,
				fieldLabel : record.data.ItemName
			});
			parent.add(this.LoanCmp);
			if(this.LoanRequestID*1 > 0)
			{
				this.LoanCmp.getStore().load({
					params : {
						RequestID : this.LoanRequestID
					},
					callback : function(){
						WFM_NewRequestObj.LoanCmp.setValue(this.getAt(0).data.RequestID)
					}
				});
			}
		}
		else if(record.data.ItemType === "branch")
		{ 
			parent.add({
				xtype : "combo",
				store: new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: '/framework/baseInfo/baseInfo.data.php?task=SelectBranches',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ["BranchID","BranchName"],
					autoLoad : true
				}),
				displayField: 'BranchName',
				valueField : "BranchID",
				readOnly : record.data.access == "NO" ? true : false,
				width : 400,
				itemId: 'ReqItem_' + record.data.FormItemID,
				name: 'ReqItem_' + record.data.FormItemID,
				fieldLabel : record.data.ItemName
			});
		}		
		else if(record.data.ItemType == "combo")
		{
			arr = record.data.ComboValues.split("#");
			data = [];
			for(j=0;j<arr.length;j++)
				if(arr[j] != "")
					data.push([ arr[j] ]);
			
			titleInLine = false;
			if(record.data.ItemName.length > 40)
			{
				parent.add({
					xtype : "displayfield",
					value : record.data.ItemName,
					anchor : "100%"
				});
				titleInLine = true;
			}
			parent.add({
				store : new Ext.data.SimpleStore({
					fields : ['value'],
					data : data
				}),
				xtype: record.data.ItemType,
				valueField : "value",
				readOnly : record.data.access == "NO" ? true : false,
				displayField : "value",
				width : 610,
				itemId: 'ReqItem_' + record.data.FormItemID,
				name: 'ReqItem_' + record.data.FormItemID,
				fieldLabel : titleInLine ? "" : record.data.ItemName
			});
		}
		//...........................................
		else if(record.data.ItemType == "checkbox")
		{
			if(record.data.ComboValues == null)
			{
				parent.add({
					boxLabel : record.data.ItemName,
					xtype : "checkbox",
					name : "ReqItem_" + record.data.FormItemID,
					itemId : "ReqItem_" + record.data.FormItemID,
					readOnly : record.data.access == "NO" ? true : false
				});
			}
			else
			{
				parent.add({
					xtype : "displayfield",
					value : record.data.ItemName,
					anchor : "100%"
				});
				var items = new Array();
				arr = record.data.ComboValues.split("#");
				for(j=0; j<arr.length; j++)
					if(arr[j] != "")
						items.push({
							boxLabel : arr[j],
							name : "ReqItem_" + record.data.FormItemID + "_checkbox_" + j,
							itemId : "ReqItem_" + record.data.FormItemID + "_checkbox_" + j
						});
				parent.add({
					xtype : "checkboxgroup",
					items : items,
					width : 610,
					columns : 1,				
					readOnly : record.data.access == "NO" ? true : false
				});
			}
		}			
		//...........................................
		else if(record.data.ItemType == "grid")
		{
			parent.add(this.MakeGrid(record));
		}
		else
		{
			titleInLine = false;
			if(record.data.ItemType === "textarea" || record.data.ItemName.length > 40)
			{
				parent.add({
					xtype : "displayfield",
					value : record.data.ItemName,
					anchor : "100%"
				});
				titleInLine = true;
			}
			item = {
				xtype: record.data.ItemType,
				itemId: 'ReqItem_' + record.data.FormItemID,
				name: 'ReqItem_' + record.data.FormItemID,
				fieldLabel : titleInLine ? "" : record.data.ItemName,
				readOnly : record.data.access == "NO" && record.data.ItemType !== "displayfield" ? true : false,
			};
			if(record.data.ItemType == 'numberfield' || record.data.ItemType == 'currencyfield')
				item.hideTrigger =  true;
			if(record.data.ItemType == 'textarea' || record.data.ItemType == 'textfield')
			{
				item.rows = 2;
				item.width = 650;
			}
			if(new Array('numberfield','currencyfield','shdatefield').indexOf(record.data.ItemType) >= 0)
				item.width = 400;
			
			if(record.data.ItemType == "displayfield")
			{
				item.value = record.data.DisplayField != null ? record.data.DisplayValue : record.data.ComboValues;
				if(record.data.DisplayType == "currencyfield")
					item.value = Ext.util.Format.Money(item.value) + " ریال"
			}
			
			parent.add(item);
		}
	}
	
	this.ItemsMmask.hide();
}


</script>
<br>
<center>
    <div id="FormDIV" style="float:right;width:600px"></div>
	<div id="historyDiv" style="float:left;width:300px"></div>
</center>
<br>