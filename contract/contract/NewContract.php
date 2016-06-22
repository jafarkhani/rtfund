<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.08
//-----------------------------

require_once '../header.inc.php';

if (!empty($_REQUEST['ContractID'])) 
	$ContractID = $_REQUEST['ContractID'];
else
	$ContractID = 0;
?>
<script type="text/javascript">

NewContract.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix: "<?= $js_prefix_address ?>",
	TplItemSeperator: "<?= CNTconfig::TplItemSeperator ?>",
	
	ContractID : <?= $ContractID ?>,
	
	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
}

function NewContract() {
	
	this.MainForm = new Ext.form.Panel({
		plain: true,            
		frame: true,
		bodyPadding: 5,
		width: 800,
		autoHeight : true,
		fieldDefaults: {
			labelWidth: 100
		},
		renderTo: this.get("SelectTplComboDIV"),
		layout: {
			type: 'table',                
			columns : 2
		},
		items: [{
			xtype: 'combo',
			colspan : 2,
			fieldLabel: 'انتخاب الگو',
			itemId: 'TemplateID',
			store: new Ext.data.Store({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../templates/templates.data.php?task=SelectTemplates',
					reader: {root: 'rows', totalProperty: 'totalCount'}
				},
				fields: ['TemplateID', 'TemplateTitle', 'TplContent'],
				autoLoad : true
			}),
			displayField: 'TemplateTitle',
			valueField: "TemplateID",
			name : "TemplateID",
			
			queryMode : "local",
			allowBlank : false,
			listConfig: {
				loadingText: 'در حال جستجو...',
				emptyText: 'فاقد اطلاعات',
				itemCls: "search-item"
			},
			width: 350,
			listeners: {
				select: function (combo, records) {
					this.collapse();
					masktpl = new Ext.LoadMask(NewContractObj.MainForm, {msg:'در حال ذخيره سازي...'});
					masktpl.show();
					NewContractObj.TplItemsStore.load({
						params : {TemplateID : records[0].data.TemplateID},
						callback : function(){
							NewContractObj.ShowTplItemsForm(records[0].data.TemplateID, false);
							masktpl.hide();
						}
					});
					
				}
			}
		},{
			xtype : "combo",
			width : 740,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../loan/request/request.data.php?task=SelectAllRequests',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ["LoanPersonID",'LoanFullname','ReqAmount',"RequestID","ReqDate", {
					name : "fullTitle",
					convert : function(value,record){
						return "[ " + record.data.RequestID + " ]" + record.data.LoanFullname  + " به مبلغ " + 
							Ext.util.Format.Money(record.data.ReqAmount) + " مورخ " + 
							MiladiToShamsi(record.data.ReqDate);
					}
				}]
			}),
			fieldLabel : "وام",
			displayField : "fullTitle",
			pageSize : 20,
			valueField : "RequestID",
			name : "LoanRequestID",
			itemId : "LoanRequestID",
			colspan : 2,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد وام</td>',
				'<td style="padding:7px">وام گیرنده</td>',
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ درخواست</td> </tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{LoanFullname}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.ReqAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[MiladiToShamsi(values.ReqDate)]}</td> </tr>',
				'</tpl>',
				'</table>'
			),
			listeners : {
				select : function(combo, records){
					me = NewContractObj;
					me.MainForm.getComponent("PersonID").getStore().load({
						params : {PersonID: records[0].data.LoanPersonID},
						callback : function(){
							if(this.getCount() > 0)
								me.MainForm.getComponent("PersonID").setValue(this.getAt(0).data.PersonID);
						}
					});
					me.MainForm.getComponent("ContractAmount").setValue(records[0].data.ReqAmount);
					me.MainForm.getComponent("ContractType").setValue("1");
					
					me.MainForm.getComponent("PersonID").readOnly = true;
					me.MainForm.getComponent("ContractAmount").readOnly = true;
					me.MainForm.getComponent("ContractType").readOnly = true;
				}
			}
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: '/framework/person/persons.data.php?task=selectPersons',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "طرف قرارداد اول",
			displayField : "fullname",
			pageSize : 20,
			width: 350,
			valueField : "PersonID",
			name : "PersonID",
			itemId : "PersonID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: '/framework/person/persons.data.php?task=selectPersons',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "طرف قرارداد دوم",
			displayField : "fullname",
			pageSize : 20,
			width: 350,
			valueField : "PersonID",
			name : "PersonID2",
			itemId : "PersonID2"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'contract.data.php?task=SelectContractTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true
			}),
			fieldLabel : "نوع قرارداد",
			displayField : "InfoDesc",
			width: 350,
			queryMode : "local",
			allowBlank : false,
			valueField : "InfoID",
			name : "ContractType",
			itemId : "ContractType"
		},{
			xtype : "currencyfield",
			fieldLabel: 'مبلغ قرارداد',
			name : "ContractAmount",
			itemId: 'ContractAmount',
			hideTrigger : true
		},{
			xtype : "shdatefield",
			fieldLabel: 'تاریخ شروع',
			name : "StartDate",
			itemId: 'StartDate',
			allowBlank : false
		},{
			xtype : "shdatefield",
			fieldLabel: 'تاریخ پایان',
			name : "EndDate",
			itemId: 'EndDate',
			allowBlank : false
		},{
			xtype: 'textarea',
			fieldLabel: 'توضیحات',
			itemId: 'description',
			name : "description",
			width: 740,
			rows : 2,
			colspan : 2
		},{
			xtype: "fieldset",
			title : "جزئیات قرارداد",
			itemId: "templateItems",
			width : 780,
			maxHeight : 200,
			autoScroll: true,
			colspan : 2,
			layout: {
				type: 'table',                
				columns : 2
			},
			defaults: {
				labelWidth: 160,
				width : 370
			}
		},{
			colspan : 2,
			xtype: "hidden",
			itemId: "ContractID",
			name : "ContractID"
		}],
		buttons: [{
			text : 'مدارک وام',
			iconCls : "attach",
			itemId : "cmp_ContractDocuments",
			disabled : true,
			handler : function(){ NewContractObj.ContractDocuments('contract'); }
		},'->',{
			text: "  ذخیره",
			handler: function () {
				NewContractObj.SaveContract(false);
			},
			iconCls: "save"
		}, {
			text: "  مشاهده",
			handler: function () {
				NewContractObj.SaveContract(true);
			},
			iconCls: "print"
		}]
	});
	
	if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
		CKEDITOR.tools.enableHtml5Elements( document );

	CKEDITOR.config.width = 790;
	CKEDITOR.config.height = 200;
	CKEDITOR.config.autoGrow_minHeight = 200;
	CKEDITOR.replace('ContractEditor');
	
	this.TplItemsStore = new Ext.data.Store({
		fields: ['TemplateItemID',"TemplateID", 'ItemName', 'ItemType', "ComboValues"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + "../templates/templates.data.php?task=selectTemplateItems",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		},
		pageSize: 500
	});
	
	this.ContractStore = new Ext.data.Store({
		fields: ['ContractID', "TemplateID", 'description', 'StartDate', "EndDate","PersonID","ContractType",
			"PersonID2","LoanRequestID","content","ContractAmount"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + "contract.data.php?task=SelectContracts&content=true",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		}
	});
	
	this.ContractItemsStore = new Ext.data.Store({
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + 'contract.data.php?task=GetContractItems',
			reader: {root: 'rows', totalProperty: 'totalCount'}
		},
		fields: ['ContractItemID', 'ContractID', 'TemplateItemID', 'ItemValue']
	});
	
	if(this.ContractID > 0)
		this.LoadContract();
}

NewContract.prototype.LoadContract = function(){

	mask1 = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask1.show();
	
	this.ContractStore.load({
		params : {
			ContractID : this.ContractID
		},
		callback : function(){
			
			me = NewContractObj;
			record = this.getAt(0);
		
			me.TplItemsStore.load({
				params : {TemplateID : record.data.TemplateID}
			});
			
			me.MainForm.down("[itemId=cmp_ContractDocuments]").enable();
			
			record.data.StartDate = MiladiToShamsi(record.data.StartDate);
			record.data.EndDate = MiladiToShamsi(record.data.EndDate);
			
			me.MainForm.loadRecord(record);
			
			if(record.data.LoanRequestID != null)
				me.MainForm.getComponent("LoanRequestID").getStore().load({
					params :{RequestID : record.data.LoanRequestID}
				});
			
			if(record.data.PersonID != null)
				me.MainForm.getComponent("PersonID").getStore().load({
					params :{PersonID : record.data.PersonID}
				});
			if(record.data.PersonID2 != null)
				me.MainForm.getComponent("PersonID2").getStore().load({
					params :{PersonID : record.data.PersonID2}
				});
			
			me.ContractItemsStore.load({
				params: {ContractID: record.data.ContractID},
				callback : function(){
					me.ShowTplItemsForm(record.data.TemplateID, true);			
					mask1.hide();
				}
			});	
			
			//CKEDITOR.instances.ContractEditor.on('instanceReady', function( ev ) {
			//	ev.editor.setData(record.data.content);
			//});
			CKEDITOR.instances.ContractEditor.setData(record.data.content);
		}
	});
}

NewContractObj = new NewContract();

NewContract.prototype.SaveContract = function (print) {

	if(!this.MainForm.getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();
	
	this.MainForm.getForm().submit({
		
		url: this.address_prefix + 'contract.data.php?task=SaveContract',
		method: 'POST',
		params : {
			content : CKEDITOR.instances.ContractEditor.getData()
		},
		
		success: function (form,action) {
			mask.hide();
			
			NewContractObj.MainForm.getComponent('ContractID').setValue(action.result.data);
			if (print) 
			{
				var ContractID = NewContractObj.MainForm.getComponent('ContractID').getValue();
				window.open(NewContractObj.address_prefix + 'PrintContract.php?ContractID=' + ContractID);
			}
			else
				Ext.MessageBox.alert('', 'با موفقیت ذخیره شد');
		},
		failure : function(form,action){
			mask.hide();
			Ext.MessageBox.alert('', 'خطا در اجرای عملیات');
		}
	});
}

NewContract.prototype.ShowTplItemsForm = function (TemplateID, LoadValues) {

	this.MainForm.getComponent("templateItems").removeAll();

	mask = new Ext.LoadMask(this.MainForm.getComponent("templateItems"), {msg:'در حال ذخيره سازي...'});
	mask.show();
	  
	Ext.Ajax.request({
		url: NewContractObj.address_prefix + '../templates/templates.data.php?task=GetTemplateContent',
		params: {
			TemplateID: TemplateID
		},
		method: 'POST',
		success: function (response) {
			me = NewContractObj;
			var TplContent = response.responseText;
			if(me.ContractID == "" || me.ContractID == 0)
				CKEDITOR.instances.ContractEditor.setData(TplContent);
			
			for(i=0; i<me.TplItemsStore.getCount(); i++)
			{
				record = me.TplItemsStore.getAt(i);
				if(record.data.ItemType == "")
					continue;
				
				if(record.data.ItemType == "combo")
				{
					arr = record.data.ComboValues.split("#");
					data = [];
					for(j=0;j<arr.length;j++)
						data.push([ arr[j] ]);
					
					me.MainForm.getComponent("templateItems").add({
						store : new Ext.data.SimpleStore({
							fields : ['value'],
							data : data
						}),
						xtype: record.data.ItemType,
						valueField : "value",
						displayField : "value",
						itemId: 'TplItem_' + record.data.TemplateItemID,
						name: 'TplItem_' + record.data.TemplateItemID,
						fieldLabel : record.data.ItemName
					});
				}
				else
				{
					me.MainForm.getComponent("templateItems").add({
						xtype: record.data.ItemType,
						itemId: 'TplItem_' + record.data.TemplateItemID,
						name: 'TplItem_' + record.data.TemplateItemID,
						fieldLabel : record.data.ItemName,
						hideTrigger : record.data.ItemType == 'numberfield' || record.data.ItemType == 'currencyfield' ? true : false
					});
				}
				
				if (LoadValues > 0) {
					var num = me.ContractItemsStore.find('TemplateItemID', record.data.TemplateItemID);                                    
					if (me.ContractItemsStore.getAt(num)){
						switch(record.data.ItemType){
							case "shdatefield" :
								me.MainForm.getComponent("templateItems").
									getComponent('TplItem_' + record.data.TemplateItemID).setValue(
										MiladiToShamsi(me.ContractItemsStore.getAt(num).data.ItemValue));
								break;
							default : 
								me.MainForm.getComponent("templateItems").
									getComponent('TplItem_' + record.data.TemplateItemID).setValue(
										me.ContractItemsStore.getAt(num).data.ItemValue);                                    
						}
					}
				}            
			}
			mask.hide();
			return;			            
		},
		failure: function () {
		}
	});
}

NewContract.prototype.getShdatefield = function (fieldname, ren) {
	return new Ext.form.SHDateField(
			{
				name: fieldname,
				width: 150,
				format: 'Y/m/d',
				renderTo: NewContractObj.get(ren)
			}
	);
};

NewContract.prototype.ContractDocuments = function(ObjectType){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 720,
			height : 440,
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : "/office/dms/documents.php",
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
			ObjectID : this.ContractID
		}
	});
}
</script>
<br>
<center>
    <div id="SelectTplComboDIV"></div>
    <div id="ContractEditor"></div>
</center>
<br>