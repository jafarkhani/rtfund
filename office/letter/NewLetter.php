<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.10
//-----------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

$LetterID = !empty($_POST["LetterID"]) ? $_POST["LetterID"] : "";

//................  GET ACCESS  .....................
if(isset($_POST["MenuID"]))
	$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
else
{
	$accessObj = new FRW_access();
	$accessObj->AddFlag = true;
	$accessObj->EditFlag = true;
	$accessObj->RemoveFlag = true;
}
//...................................................
?>

<script>

Letter.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	LetterID : '<?= $LetterID ?>',
	LoadEditor : false,
	RefLetterID : '<?= !empty($_REQUEST["RefLetterID"]) ? $_REQUEST["RefLetterID"] : "" ?>',
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Letter(){

	this.BuildForms();
	
	if(this.RefLetterID != "")
		this.letterPanel.down("[name=RefLetterID]").setValue(this.RefLetterID);
	
	if(this.LetterID > 0)
	{
		this.mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاری...'});
		this.mask.show();

		this.LoadLetter();
	}
}

Letter.prototype.LoadLetter = function(){
		
	this.store = new Ext.data.Store({
		proxy : {
			type: 'jsonp',
			url: this.address_prefix + "letter.data.php?task=SelectLetter&LetterID=" + this.LetterID,
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["LetterID","LetterType","LetterTitle","SubjectID","summary","context",
			"keywords","AccessType","OuterSendType","SignerPersonID", "organization",
			"OrgPost","InnerLetterNo","InnerLetterDate","OuterCopies","IsSigned", "ProcessID"],
		autoLoad : true,
		listeners : {
			load : function(){
				me = LetterObject;
				//..........................................................
				record = this.getAt(0);
				me.letterPanel.loadRecord(record);
				me.TabPanel.down("[name=context]").setValue(record.data.context);
				
				me.TabPanel.down("[itemId=pagesView]").getStore().proxy.extraParams = {
					LetterID : record.data.LetterID
				};
				me.TabPanel.down("[itemId=pagesView]").getStore().load();
							
				LetterObject.mask.hide();
				
				me.TabPanel.down("[itemId=btn_send]").enable();
				me.TabPanel.down("[itemId=attach_tab]").enable();	
				me.TabPanel.down("[itemId=LetterPices]").enable();
				me.TabPanel.down("[itemId=customer_tab]").enable();	
				me.TabPanel.down("[itemId=notes_tab]").enable();			
				me.TabPanel.down("[itemId=refs_tab]").enable();			
				
				if(record.data.LetterType == "OUTCOME" && record.data.IsSigned == "NO" && 
				record.data.SignerPersonID == "<?= $_SESSION["USER"]["PersonID"] ?>")
					me.TabPanel.down("[itemId=btn_sign]").show();	
			}
		}
	});
}

Letter.prototype.BuildForms = function(){
	
	this.TabPanel = new Ext.TabPanel({
		renderTo : this.get("mainForm"),
		width : 780,
		height : 500,
		frame: true,
		items :[{
			title : "اطلاعات نامه",
			items : this.letterPanel = new Ext.form.Panel({
				border : false,
				layout : {
					type : "table",
					columns : 2
				},
				defaults : {
					labelWidth : 110,
					width : 370
				},
				width: 780,
				items : [{
					xtype :"container",
					layout : "hbox",
					items : [{
						xtype : "radio",
						labelWidth : 60,
						boxLabel: 'نامه داخلی',
						name: 'LetterType',
						style : "margin-right : 20px",
						checked : true,
						inputValue: 'INNER',
						listeners : {
							change : function(){
								if(this.checked)
								{
									this.up('form').down("[name=organization]").disable();
									this.up('form').down("[name=OuterSendType]").disable();
									this.up('form').down("[name=OrgPost]").disable();
								}	
								else
								{
									this.up('form').down("[name=organization]").enable();
									this.up('form').down("[name=OuterSendType]").enable();
									this.up('form').down("[name=OrgPost]").enable();
								}
							}
						}
					},{
						xtype : "radio",
						boxLabel: 'نامه صادره',
						style : "margin-right : 20px",
						name: 'LetterType',
						inputValue: 'OUTCOME'
					},{
						xtype : "radio",
						boxLabel: 'نامه وارده',
						name: 'LetterType',
						inputValue: 'INCOME',
						listeners : {
							change : function(){
								if(this.checked)
								{
									this.up('form').down("[name=InnerLetterNo]").enable();
									this.up('form').down("[name=InnerLetterDate]").enable();	
									this.up('form').down("[name=SignerPersonID]").disable();
								}
								else
								{
									this.up('form').down("[name=InnerLetterNo]").disable();
									this.up('form').down("[name=InnerLetterDate]").disable();
									this.up('form').down("[name=SignerPersonID]").enable();
								}

							}
						}				
					}]
				},{
					xtype : "container",
					layout : "hbox",
					items : [{
						xtype : "textfield",
						name : "LetterTitle",
						labelWidth : 110,
						width : 240,
						fieldLabel : "عنوان نامه",
						allowBlank : false
					},{
						xtype : "numberfield",
						name : "RefLetterID",
						fieldLabel : "عطف به",
						hideTrigger : true,
						labelWidth : 50,
						width : 130,
						allowBlank : true
					}]
				},{
					xtype : "container",
					layout : "hbox",
					items :[{
						xtype : "textfield",
						labelWidth : 110,
						width : 180,
						fieldLabel : "شماره",
						name : "InnerLetterNo",
						disabled : true
					},{
						xtype : "shdatefield",
						labelWidth : 40,
						width : 170,
						fieldLabel : "تاریخ",
						name : "InnerLetterDate",
						disabled : true
					}]
				},{
					xtype : "combo",
					name : "SignerPersonID",
					store: new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: '/framework/person/persons.data.php?task=selectSignerPersons',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ['PersonID','fullname'],
						autoLoad : true
					}),
					fieldLabel : "امضا کننده",
					queryMode : "local",
					displayField: 'fullname',
					valueField : "PersonID"
				},{
					xtype : "combo",
					store : new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + 'letter.data.php?task=selectOrganizations',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ['OrgID','OrgTitle'],
						pageSize : 25
					}),
					listeners : {
						select : function(combo, records){
							LetterObject.TabPanel.down("[name=OrgPost]").getStore().proxy.extraParams.OrgTitle = 
								records[0].data.OrgTitle;
							LetterObject.TabPanel.down("[name=OrgPost]").getStore().load({
								callback : function(){
									if(this.totalCount > 0)
										LetterObject.TabPanel.down("[name=OrgPost]").setValue(this.getAt(0).data.OrgPost);
								}
							});
						}
					},
					fieldLabel : "فرستنده/گیرنده",
					displayField: 'OrgTitle',
					valueField : "OrgTitle",
					name : "organization",					
					disabled : true,
					allowBlank : true
				},{
					xtype : "textarea",
					name : "OuterCopies",
					fieldLabel : "رونوشت به خارج از سازمان",
					rows : 2,
					rowspan :2,			
					allowBlank : true
				},{
					xtype : "combo",
					store : new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + 'letter.data.php?task=selectOrgPosts',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ['OrgPost'],
						pageSize : 25
					}),
					queruMode : "local",
					fieldLabel : "پست مربوطه",
					displayField: 'OrgPost',
					valueField : "OrgPost",
					name : "OrgPost",		
					disabled : true,
					allowBlank : true
				},{
					xtype : "combo",
					disabled : true,
					fieldLabel : "شیوه دریافت/ارسال",
					store: new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + 'letter.data.php?task=selectOuterSendType',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ['InfoID',"InfoDesc"],
						autoLoad : true
					}),
					queryMode : "local",
					name : "OuterSendType",
					displayField: 'InfoDesc',
					valueField : "InfoID"
				},{
					xtype : "combo",
					fieldLabel : "دسترسی نامه",
					store: new Ext.data.Store({
						proxy:{
							type: 'jsonp',
							url: this.address_prefix + 'letter.data.php?task=selectAccessType',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields :  ['InfoID',"InfoDesc"],
						autoLoad : true
					}),
					queryMode : "local",
					name : "AccessType",
					displayField: 'InfoDesc',
					valueField : "InfoID"
				},{
					xtype : "textfield",
					name : "keywords",
					colspan : 2,
					width : 740,
					fieldLabel : "<span>" + "کلید واژه" + "</span><button class='x-btn help' "+
						"style='border:0;background-color:white' data-qtip='برای جداسازی از # استفاده کنید'>"
				},{
					xtype : "treecombo",
					fieldLabel : "فرایند",
					colspan : 2,
					width : 740,
					name : "ProcessID",
					selectChildren: true,
					canSelectFolders: false,
					multiselect : false,
					store : new Ext.data.TreeStore({
						proxy: {
							type: 'ajax',
							url: '/framework/baseInfo/baseInfo.data.php?task=GetProcessTree' 
						},
						root: {
							text: "فرایندها",
							id: 'src',
							expanded: true
						}
					})					
				},{
					xtype : "textarea",
					name : "PostalAddress",
					rows: 5,
					fieldLabel : "آدرس پستی گیرنده"
				}]
			})
		},{
			title : "متن نامه",
			height : 450,
			items :[{
				xtype : "combo",
				width : 500,
				fieldLabel : "انتخاب متن از الگوها",
				store: new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: this.address_prefix + 'letter.data.php?task=SelectTemplates',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ['TemplateTitle',"context"],
					autoLoad : true
				}),
				queryMode : "local",
				labelWidth : 130,
				displayField: 'TemplateTitle',
				valueField : "TemplateTitle",
				listeners :{
					select : function(store,records){
						
						LetterObject.TabPanel.down("[name=context]").setValue(records[0].data.context);
						//CKEDITOR.instances.LetterEditor.setData(records[0].data.context);
					}
				}
			},{
				xtype : "htmleditor",
				width : 700,
				height : 400,
				name : "context"
			}]/*,			
			listeners : {
				activate : function(){
					if(!LetterObject.LoadEditor)
					{
						LetterObject.LoadEditor = true;
						if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
							CKEDITOR.tools.enableHtml5Elements( document );

						CKEDITOR.config.width = 'auto';
						CKEDITOR.config.height = 300;
						CKEDITOR.config.autoGrow_minHeight = 170;
						CKEDITOR.replace('LetterEditor');	
						CKEDITOR.add;
						
						if(LetterObject.LetterID > 0)
						{
							record = LetterObject.store.getAt(0);
							CKEDITOR.instances.LetterEditor.on('instanceReady', function( ev ) {
								ev.editor.setData(record.data.context);
							});			
							CKEDITOR.instances.LetterEditor.setData(record.data.context);

						}	
						else
							CKEDITOR.instances.LetterEditor.setData();
						
						LetterObject.TabPanel.down("[itemId=AddToTemplates]").enable();
					}					
				}
			}*/
		},{
			title : "تصاویر نامه",
			itemId : "LetterPices",
			style : "margin-top:10px",
			disabled : true,
			items : [{
				xtype : "form",
				border : false,
				itemId : "LetterPicsPanel",
				layout : "hbox",
				items : [{
					xtype : "filefield",
					width : 300,
					fieldLabel : "انتخاب تصویر",
					name : "PageFile"
				},{
					xtype : "button",
					text : "اضافه تصویر",
					border : true,
					iconCls : "add",
					handler : function(){
						if(this.up('panel').down("[name=PageFile]").getValue() == "")
						{
							Ext.MessageBox.alert("","ورود فایل صفحه الزامی است");
							return;
						}
						LetterObject.SaveLetter(true);
					}
				},{
					xtype : "button",
					text : "اسکن نامه",
					border : true,
					iconCls : "scan",
					handler : function(){ LetterObject.Scan();}
				}]
			},new Ext.Panel({
				frame: true,
				width : 730,
				height : 380,
				autoScroll : true,
				style : "margin:5px",
				items : new Ext.view.View({		
					itemId : "pagesView",
					store: new Ext.data.SimpleStore({
						proxy: {
							type: 'jsonp',
							url: this.address_prefix + 'letter.data.php?task=selectLetterPages',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						},
						fields : ['RowID','ObjectID','DocumentID','DocDesc','FileType']
					}),
					tpl: [
						'<tpl for=".">',
							'<div style="position:relative;float: right;padding:5px;width:100px;margin:5px">',
							'<div class="thumb">',
								'<tpl if="FileType == \'pdf\'">' ,
									'<img style="width:100px;height:100px;cursor:pointer" ',
									'src="/office/icons/pdf.jpg" ',
									'title="{DocumentTitle}" onclick="LetterObject.ShowPage({DocumentID},{ObjectID})">',
								'<tpl else>',
									'<img style="width:100px;height:100px;cursor:pointer" ',
									'src="/office/dms/ShowFile.php?RowID={RowID}&DocumentID={DocumentID}&ObjectID={ObjectID}&inline=true" ',
									'title="{DocumentTitle}" onclick="LetterObject.ShowPage({DocumentID},{ObjectID})">',
								'</tpl>',
							'</div>',
							'<div class="cross x-btn-default-small" style="cursor:pointer;float: right;position: absolute;top:8px;',
								'height: 19px; width: 19px; margin: 4px;"',
								' onclick="LetterObject.DeletePage({DocumentID},{RowID})"></div>',
							'</div>',
						'</tpl>',
						'<div class="x-clear"></div>'
					],
					overItemCls: 'x-item-over'
				}) 
			})]
		},{
			title : "پیوست های نامه",
			itemId : "attach_tab",
			disabled : true,
			loader : {
				url : this.address_prefix + "attach.php",
				method: "POST",
				text: "در حال بار گذاری...",
				scripts : true
			},
			listeners : {
				activate : function(){
					if(this.loader.isLoaded)
						return;
					this.loader.load({
						params : {
							LetterID : LetterObject.LetterID,
							ExtTabID : this.getEl().id
						}
					});
				}
			}
		},{
			title : "ذینفعان نامه",
			itemId : "customer_tab",
			disabled : true,
			loader : {
				url : this.address_prefix + "LetterCustomers.php",
				method: "POST",
				text: "در حال بار گذاری...",
				scripts : true
			},
			listeners : {
				activate : function(){
					if(this.loader.isLoaded)
						return;
					this.loader.load({
						params : {
							LetterID : LetterObject.LetterID,
							ExtTabID : this.getEl().id
						}
					});
				}
			}
		},{
			title : "یادداشت های نامه",
			itemId : "notes_tab",
			disabled : true,
			loader : {
				url : this.address_prefix + "LetterNotes.php",
				method: "POST",
				text: "در حال بار گذاری...",
				scripts : true
			},
			listeners : {
				activate : function(){
					if(this.loader.isLoaded)
						return;
					this.loader.load({
						params : {
							LetterID : LetterObject.LetterID,
							ExtTabID : this.getEl().id
						}
					});
				}
			}
		},{
			title : "نامه های وابسته",
			itemId : "refs_tab",
			disabled : true,
			loader : {
				url : this.address_prefix + "RefLetters.php",
				method: "POST",
				text: "در حال بار گذاری...",
				scripts : true
			},
			listeners : {
				activate : function(){
					if(this.loader.isLoaded)
						return;
					this.loader.load({
						params : {
							LetterID : LetterObject.LetterID,
							ExtTabID : this.getEl().id
						}
					});
				}
			}
		}],
		buttons :[{
			text : "اضافه متن نامه به الگوها",
			iconCls : "add",
			itemId : "AddToTemplates",
			handler : function(){ LetterObject.AddToTemplates() }
		},'->',{
			text : "امضاء نامه",
			iconCls : "sign",
			itemId : "btn_sign",
			hidden : true,
			handler : function(){
				LetterObject.SignLetter();
			}
		},{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){
				LetterObject.SaveLetter(false);
			}
		},{
			text : "ارجاع",
			iconCls : "sendLetter",
			itemId : "btn_send",
			disabled : true,
			handler : function(){
				LetterObject.SendWindowShow();
			}
		}]
	});
}

LetterObject = new Letter();

Letter.prototype.Scan = function(){

	var exampleSocket = new WebSocket("ws://127.0.0.1:13000", "protocolOne");
	exampleSocket.onmessage = function (event) {
		alert(event.data);
	}
	
	/*Ext.Ajax.request({
		url : "127.0.0.1:13000",
				
		success : function(response){
			alert(response.responseText)
		}
	});
*/
	return;

	scanner.scan(function(successful, mesg, response) {
			if(!successful) { // On error
				alert('Failed: ' + mesg);
				return;
			}

			if(successful && mesg != null && mesg.toLowerCase().indexOf('user cancel') >= 0) { // User cancelled.
				alert('User cancelled');
				return;
			}
		},
		{
			"output_settings": [{
				"type": "upload",
				"format": "jpg",
				"upload_target": {
					"url" : this.address_prefix + 'letter.data.php?task=SaveLetter' , 
					"post_fields": {
						"LetterID": this.LetterID
					}
				}
			}]
		}
	);

}

Letter.prototype.SaveLetter = function(SendFile){

	mask = new Ext.LoadMask(this.TabPanel, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	
	params = {LetterID : this.LetterID};
	/*if(CKEDITOR.instances.LetterEditor)
		params.context = CKEDITOR.instances.LetterEditor.getData();*/
	params.context = this.TabPanel.down("[name=context]").getValue();
		
	if(SendFile)
	   form = this.TabPanel.down("[itemId=LetterPicsPanel]").getForm();
   else
	   form = this.letterPanel.getForm();
   
	form.submit({
		clientValidation: true,
		url: this.address_prefix + 'letter.data.php?task=SaveLetter' , 
		isUpload : true,
		method: "POST",
		params : params,
		
		success : function(form,action){
			mask.hide();
			me = LetterObject;
			me.LetterID = action.result.data;
			//Ext.MessageBox.alert("", "ذخیره نامه "+me.LetterID+" با موفقیت انجام شد");			
			
			me.TabPanel.down("[itemId=pagesView]").getStore().proxy.extraParams = {
				LetterID : me.LetterID
			}; 
			me.TabPanel.down("[itemId=pagesView]").getStore().load();
			me.TabPanel.down("[itemId=btn_send]").enable();
			me.TabPanel.down("[itemId=attach_tab]").enable();
			me.TabPanel.down("[itemId=LetterPices]").enable();			
			me.TabPanel.down("[itemId=customer_tab]").enable();
			me.TabPanel.down("[itemId=notes_tab]").enable();			
			me.TabPanel.down("[itemId=refs_tab]").enable();
		},
		failure : function(form,action){
			mask.hide();
			Ext.MessageBox.alert("Error", action.result.data);
		}
	});
}

Letter.prototype.SignLetter = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به امضا می باشید؟", function(btn){
		if(btn == "no")
			return;
		me = LetterObject;
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php?task=SignLetter' , 
			method: "POST",
			params : {
				LetterID : me.LetterID
			},

			success : function(response){
				mask.hide();
				framework.CloseTab(LetterObject.TabID);
			},
			failure : function(){
				mask.hide();
			}
		});
	})
	
}

Letter.prototype.ShowPage = function(DocumentID, ObjectID){
	window.open("/office/dms/ShowFile.php?DocumentID=" + DocumentID + "&ObjectID=" + ObjectID);	
}

Letter.prototype.DeletePage = function(DocumentID, RowID){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		mask = new Ext.LoadMask(LetterObject.TabPanel, {msg:'در حال حذف...'});
		mask.show();  

		Ext.Ajax.request({
			url: LetterObject.address_prefix + 'letter.data.php?task=DeletePage', 
			method: "POST",
			params : {
				DocumentID : DocumentID,
				RowID : RowID,
				ObjectID : LetterObject.LetterID
			},

			success : function(){
				mask.hide();
				LetterObject.TabPanel.down("[itemId=pagesView]").getStore().load();
			},
			failure : function(){
				mask.hide();
			}
		});
	});
}

Letter.prototype.SendWindowShow = function(){
	
	if(!this.SendingWin)
	{
		this.SendingWin = new Ext.window.Window({
			width : 620,			
			height : 435,
			modal : true,
			bodyStyle : "background-color:white;",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "sending.php",
				scripts : true
			}
		});
		Ext.getCmp(this.TabID).add(this.SendingWin);
	}

	this.SendingWin.show();
	this.SendingWin.center();
	
	this.SendingWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.SendingWin.getEl().id,
			parent : "LetterObject.SendingWin",
			AfterSendHandler : "LetterObject.AfterSend" ,			
			LetterID : this.LetterID
		}
	});
	this.SaveLetter(false);
}

Letter.prototype.AfterSend = function(){
	
	framework.CloseTab(LetterObject.TabID);
		if(typeof DraftLetterObject == "object")
			DraftLetterObject.grid.getStore().load();
}

Letter.prototype.AddToTemplates = function(){
	
	if(!this.TemplatesWin)
	{
		this.TemplatesWin = new Ext.window.Window({
			width : 500,			
			height : 100,
			modal : true,
			bodyStyle : "background-color:white;",
			closeAction : "hide",
			items : [{
				xtype : "textfield",
				name : "TemplateTitle",
				emptyText : "عنوان الگو ...",
				width : 480
			}],
			buttons :[{
				text : "اضافه به الگو",
				iconCls  : "add",
				handler : function(){
					if(LetterObject.TemplatesWin.down("[name=TemplateTitle]").getValue() == "")
						return;
					Ext.Ajax.request({
						url : LetterObject.address_prefix + "letter.data.php?task=AddToTemplates",
						method : "post",
						params :{
							/*context : CKEDITOR.instances.LetterEditor.getData(),*/
							context : LetterObject.TabPanel.down("[name=context]").getValue(),
							TemplateTitle : LetterObject.TemplatesWin.down("[name=TemplateTitle]").getValue()
						},
						success : function(){
							LetterObject.TemplatesWin.hide();
						}
					});
				}
			}]
		});
		Ext.getCmp(this.TabID).add(this.TemplatesWin);
	}

	this.TemplatesWin.show();
	this.TemplatesWin.center();
}

</script>
<center>
	<br>
	<div id="mainForm"></div>
	<div id="div_grid"></div>
</center>
