<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.10
//-----------------------------
require_once '../header.inc.php';

if(empty($_POST["LetterID"]))
	die();

$LetterID = $_POST["LetterID"];
$SendID = !empty($_POST["SendID"]) ? $_POST["SendID"] : "0";
$SendIDArr = !empty($_POST["SendIDArr"]) ? $_POST["SendIDArr"] : "[]";
?>
<script>

SendLetter.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	parent : <?= $_REQUEST["parent"] ?>,
		
	AfterSendHandler : <?= $_REQUEST["AfterSendHandler"] ?>,
	
	LetterID : <?= $LetterID ?>,
	SendID : '<?= $SendID ?>',
	SendIDArr : '<?= $SendIDArr ?>',
	index : 1,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function SendLetter(){
	
	this.mainPanel = new Ext.form.FormPanel({
		frame : true,
		height : 375,
		autoScroll : true,
		defaults : {
			anchor : "100%",
			style : "margin:3px"			
		},
		buttons : [{
			text : "ارجاع جدید",
			iconCls : "add",
			handler : function(){
				SendLetterObject.AddSendingFieldSet();
			}
		},'->',{
			text : "ارسال نامه",
			iconCls : "sendLetter",
			handler : function(){
				SendLetterObject.SendingLetter();
			}
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){
				SendLetterObject.parent.hide();
			}
		}]
	});
	
	this.tabPanel = new Ext.tab.Panel({
		renderTo : this.get("div_panel"),
		plain: true,
		height : 400,
		autoScroll : true,
		width : 610,
		
		items : [{
			title : "ارجاع نامه",
			items : this.mainPanel
		}, {
			title : "درج پیوست",
			hidden : this.SendID*1 > 0 ? false : true,
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
							LetterID : SendLetterObject.LetterID,
							SendID : SendLetterObject.SendID,
							ExtTabID : this.getEl().id
						}
					});
				}
			}
		}]
	});
	
	this.AddSendingFieldSet();
}

SendLetter.prototype.AddSendingFieldSet = function(){
	
	this.mainPanel.add({
		xtype : "fieldset",
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {
			width : 270,
			labelWidth : 100
		},
		items : [{
			xtype : "combo",
			name : this.index + "_ToPersonID",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'letter.data.php?task=SendToList',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['type','id','name']
			}),
			tpl : new Ext.XTemplate(
				'<tpl for=".">',
					'<tpl if="type == \'Group\'">',
						'<div class="x-boundlist-item" style="background-color:#fcfcb6">{name}</div>',
					'<tpl else>',
						'<div class="x-boundlist-item">{name}</div>',
					'</tpl>',						
				'</tpl>'
			),
			fieldLabel : "ارجاع به",
			displayField: 'name',
			valueField : "id"				
		},{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'letter.data.php?task=GetSendComments',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['SendComment']
			}),
			width : 270,
			displayField: 'SendComment',
			valueField : "SendComment",
			listeners : {
				select : function(){
					for(i=SendLetterObject.index-1; i>0; i--)
					{
						el = SendLetterObject.mainPanel.down("[name=" + i + "_SendComment]");
						if(el.getValue() == "")
							el.setValue(this.getValue());
					}
				}
			}
		},{
			xtype : "combo",
			name : this.index + "_SendType",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'letter.data.php?task=selectSendTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['InfoID','InfoDesc'],
				autoLoad : true
			}),
			fieldLabel : "نوع ارجاع",
			queryMode : "local",
			displayField: 'InfoDesc',
			valueField : "InfoID",
			value : this.index > 1 ? this.mainPanel.down("[name=1_SendType]").getValue() : "1"
		},{
			xtype : "textarea",
			emptyText : "شرح ارجاع",
			name : this.index + "_SendComment",
			value : this.index > 1 ? this.mainPanel.down("[name=1_SendComment]").getValue() : "",
			rowspan : 5,
			rows : 6,
			width :270
		},{
			xtype : "combo",
			name : this.index + "_IsUrgent",
			store: new Ext.data.SimpleStore({
				fields : ['id','title'],
				data : [ 
					['NO', "عادی"],
					["YES", "فوری"] 
				]
			}),  
			value : this.index > 1 ? this.mainPanel.down("[name=1_IsUrgent]").getValue() : "NO",
			fieldLabel : "فوریت",
			displayField: 'title',
			valueField : "id"	
		},{
			xtype : "shdatefield",
			name : this.index + "_ResponseTimeout",
			fieldLabel : "مهلت پاسخ"			
		},{
			xtype : "shdatefield",
			name : this.index + "_FollowUpDate",
			fieldLabel : "تاریخ پیگیری"
		},{
			xtype : "checkbox",
			fieldLabel : "رونوشت",
			disabled : this.index == 1 ? true : false,
			name : this.index + "_IsCopy",
			checked: this.index > 1 ? this.mainPanel.down("[name=1_IsCopy]").checked : false
		}]
	});
	this.index++;
}

SendLetter.prototype.SendingLetter = function(){

	if(this.mainPanel.down("[name=1_ToPersonID]").getValue() == null)
		return;

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.mainPanel.getForm().submit({
		url: this.address_prefix + 'letter.data.php',
		method: "POST",
		params: {
			task: "SendLetter",
			LetterID : this.LetterID,
			SendID : this.SendID,
			SendIDArr : this.SendIDArr
		},
		success: function(form,action){
			mask.hide();
			SendLetterObject.parent.hide();
			eval("SendLetterObject.AfterSendHandler();");
		},
		failure : function(form,action){
			mask.hide();
			if(action.result.data == "")
				Ext.MessageBox.alert("ERROR","عملیات مورد نظر با شکست مواجه شد");
			else
				Ext.MessageBox.alert("ERROR",action.result.data);
		}
	});
}

SendLetterObject = new SendLetter();

</script>
<center>
	<div id="div_panel"></div>
</center>