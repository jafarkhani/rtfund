<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.10
//-----------------------------
require_once '../header.inc.php';
require_once inc_dataGrid;

$LetterID = !empty($_POST["LetterID"]) ? $_POST["LetterID"] : "";

?>

<script>

Letter.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	LetterID : '<?= $LetterID ?>',
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Letter(){
	 /*new ImageViewer({
			renderTo : this.get("mainForm"),
			width : 600,
			height : 600,
			src: '../xx.jpg'
		});*/
	this.BuildForms();
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
		fields : ["LetterID","LetterType","LetterTitle","SubjectID","summary","context"],
		autoLoad : true,
		listeners : {
			load : function(){
				me = LetterObject;
				//..........................................................
				record = this.getAt(0);
				me.letterPanel.loadRecord(record);
				
				me.letterPanel.down("[itemId=pagesView]").getStore().proxy.extraParams = {
					LetterID : record.data.LetterID
				};
				me.letterPanel.down("[itemId=pagesView]").getStore().load();
				
				CKEDITOR.on('instanceReady', function( ev ) {
					if(LetterObject.LetterID > 0)
					{
						ev.editor.setData(record.data.context);
						LetterObject.mask.hide();
					}					
				});			
				
				me.letterPanel.down("[itemId=btn_send]").enable();
			}
		}
	});
}

Letter.prototype.BuildForms = function(){
	
	this.letterPanel = new Ext.form.FormPanel({
		renderTo : this.get("mainForm"),
		title : "مشخصات نامه",
		frame : true,
		height : 540,
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {
			labelWidth : 60,
			width : 350
		},
		width: 780,
		items : [{
			xtype :"container",
			layout : "hbox",
			items : [{
				xtype : "radio",
				fieldLabel : "نوع نامه",
				labelWidth : 60,
				boxLabel: 'نامه داخلی',
				name: 'LetterType',
				style : "margin-right : 20px",
				checked : true,
				inputValue: 'INNER'
			},{
				xtype : "radio",
				boxLabel: 'نامه صادره',
				name: 'LetterType',
				inputValue: 'OUTCOME'
			},{
				xtype : "radio",
				boxLabel: 'نامه وارده',
				name: 'LetterType',
				inputValue: 'INCOME'
			}]
		},{
			xtype : "textarea",
			fieldLabel : "چکیده",
			name : "summary",
			width : 400,
			rows : 3,
			rowspan : 3
		},{
			xtype : "textfield",
			name : "LetterTitle",
			fieldLabel : "عنوان نامه",
			allowBlank : false
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'letter.data.php?task=selectSubjects',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			fieldLabel : "موضوع نامه",
			displayField : "InfoDesc",
			valueField : "InfoID",
			name : "SubjectID"
		},{
			xtype : "tabpanel",
			colspan : 2,
			plain: true,
			height : 400,
			width : 760,
			items :[{
				title : "نامه تایپی",
				html : "<div id='Div_context'></div>"
			},{
				title : "نامه تصویری",
				style : "margin-top:10px",
				items : [{
					xtype : "textfield",
					fieldLabel : "عنوان صفحه",
					name : "PageTitle",
					width : 300
				},{
					xtype : "container",
					layout : "hbox",
					items : [{
						xtype : "filefield",
						width : 300,
						fieldLabel : "انتخاب تصویر",
						name : "PageFile"
					},{
						xtype : "button",
						iconCls : "add",
						handler : function(){
							if(this.up('panel').down("[name=PageTitle]").getValue() == "" || 
								this.up('panel').down("[name=PageFile]").getValue() == "")
							{
								Ext.MessageBox.alert("","ورود عنوان صفحه و فایل الزامی است");
								return;
							}
							LetterObject.SaveLetter();
						}
					}]
				},new Ext.Panel({
					frame: true,
					width : 730,
					height : 290,
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
							fields : ['ObjectID','DocumentID','DocDesc']
						}),
						tpl: [
							'<tpl for=".">',
								'<div style="position:relative;float: right;padding:5px;width:100px;margin:5px">',
								'<div class="thumb"><img style="width:100px;height:100px;cursor:pointer" ',
									'src="/dms/ShowFile.php?DocumentID={DocumentID}&ObjectID={ObjectID}" ',
									'title="{DocumentTitle}" onclick="LetterObject.ShowPage({DocumentID})"></div>',
								'<div style="width:100%;text-align:center">{DocDesc}</div>',
								'<div class="cross x-btn-default-small" style="cursor:pointer;float: right;position: absolute;top:8px;',
									'height: 19px; width: 19px; margin: 4px;"',
									' onclick="LetterObject.DeletePage({DocumentID})"></div>',
								'</div>',
							'</tpl>',
							'<div class="x-clear"></div>'
						],
						overItemCls: 'x-item-over'
					}) 
				})]
			}]
		}],
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){
				LetterObject.SaveLetter();
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
	
	if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
		CKEDITOR.tools.enableHtml5Elements( document );

	CKEDITOR.config.width = 'auto';
	CKEDITOR.config.height = 270;
	CKEDITOR.config.autoGrow_minHeight = 200;
	
	CKEDITOR.document.getById( 'Div_context' );
	CKEDITOR.replace( 'Div_context' );	
}

LetterObject = new Letter();

Letter.prototype.SaveLetter = function(){

	mask = new Ext.LoadMask(this.letterPanel, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	
	this.letterPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'letter.data.php?task=SaveLetter' , 
		method: "POST",
		params : {
			LetterID : this.LetterID,
			context : CKEDITOR.instances.Div_context.getData()
		},
		
		success : function(form,action){
			mask.hide();
			LetterObject.LetterID = action.result.data;
			LetterObject.letterPanel.down("[name=PageTitle]").setValue();
			LetterObject.letterPanel.down("[itemId=pagesView]").getStore().proxy.extraParams = {
				LetterID : LetterObject.LetterID
			};
			LetterObject.letterPanel.down("[itemId=pagesView]").getStore().load();
			me.letterPanel.down("[itemId=btn_send]").enable();
		},
		failure : function(){
			mask.hide();
		}
	});
}

Letter.prototype.ShowPage = function(DocumentID, ObjectID){
	window.open("/dms/ShowFile.php?DocumentID=" + DocumentID + "&ObjectID=" + ObjectID);	
}

Letter.prototype.DeletePage = function(DocumentID){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		mask = new Ext.LoadMask(LetterObject.letterPanel, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			url: LetterObject.address_prefix + 'letter.data.php?task=DeletePage', 
			method: "POST",
			params : {
				DocumentID : DocumentID,
				ObjectID : LetterObject.LetterID
			},

			success : function(){
				mask.hide();
				LetterObject.letterPanel.down("[name=PageTitle]").setValue();
				LetterObject.letterPanel.down("[itemId=pagesView]").getStore().load();
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
			title : "ارجاع نامه",
			width : 462,			
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
			AfterSendHandler : function(){
				framework.CloseTab(LetterObject.TabID);
				if(DraftLetterObject != "object")
					DraftLetterObject.grid.getStore().load();
			},
			
			LetterID : this.LetterID
		}
	});
}


</script>
<center>
	<br>
	<div id="mainForm"></div>
	<div id="div_grid"></div>
</center>