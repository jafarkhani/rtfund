<?php
//-------------------------
// Create Date:	97.11
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;
require_once 'meeting.class.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$MeetingID = (int)$_REQUEST["MeetingID"];
$obj = new MTG_meetings($MeetingID);
$readOnly = $obj->StatusID == MTG_STATUSID_RAW ? false : true;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "meeting.data.php?task=GetMeetingRecords"
		. "&MeetingID=" . $MeetingID, "grid_div");

$dg->addColumn("", "RecordID", "", true);
$dg->addColumn("", "MeetingID", "", true);
$dg->addColumn("", "PersonID", "", true);
$dg->addColumn("", "details", "", true);
$dg->addColumn("", "keywords", "", true);
$dg->addColumn("", "PreRecordNo", "", true); /*New Edition*/
$dg->addColumn("", "descriptionDocs", "", true);  /*New Create*/

$col = $dg->addColumn("موضوع", "subject", "");

$col = $dg->addColumn("مسئول اجرا", "fullname", "");
$col->width = 180;

$col = $dg->addColumn("تاریخ پیگیری", "FollowUpDate", GridColumn::ColumnType_date);
$col->width = 80;

$col = $dg->addColumn("وضعیت", "RecordStatus", "");
$col->renderer = "MTG_MeetingRecords.StatusRender";
$col->width = 60;

$col = $dg->addColumn("پیوست", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return MTG_MeetingRecords.attachRender(v,p,r);}";
$col->width = 50;

if(session::IsFramework())
{
	$col = $dg->addColumn("ابلاغ", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return MTG_MeetingRecords.LetterRender(v,p,r);}";
	$col->width = 50;
}
if($accessObj->AddFlag && !$readOnly)
{
	$dg->addButton("", "ایجاد مصوبه", "add", "function(){MTG_MeetingRecordsObject.AddRecord();}");
}
if($accessObj->EditFlag && !$readOnly)
{
	$col = $dg->addColumn("ویرایش", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return MTG_MeetingRecords.EditRender(v,p,r);}";
	$col->width = 50;
}
if($accessObj->RemoveFlag && !$readOnly)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return MTG_MeetingRecords.DeleteRender(v,p,r);}";
	$col->width = 50;
}

$dg->addButton("", "چاپ مصوبه ها", "print", "function(){MTG_MeetingRecordsObject.PrintRecords();}");
$dg->addButton("", "ابلاغ مصوبه ها", "send", "function(){MTG_MeetingRecordsObject.ShowAllLetterWindow();}"); /* New Create */

$dg->addPlugin("this.Details");

$dg->height = 377;
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "RecordID";
$dg->DefaultSortDir = "ASC";
$dg->emptyTextOfHiddenColumns = true;

$grid = $dg->makeGrid_returnObjects();

?>
<center>
        <div id="grid_div"></div>
</center>
<script>

MTG_MeetingRecords.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	MeetingID : "<?= $MeetingID ?>",
	
	LetterPersons : new Array(),
	LetterPersonNames : new Array(),
	
	RecordPersons : new Array(),
	ReordPersonNames : new Array(),
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function MTG_MeetingRecords(){
	
	this.Details = {
		ptype: 'rowexpander',
		rowBodyTpl : [
			'<hr>','توضیحات: {details}<br>',
			'کلمات کلیدی: {[values.keywords == null ? "" : values.keywords]}'
		]
	};
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("grid_div"));
	
	this.formWindow = new Ext.Window({
		width : 600,
		autoHeight : true,
		modal : true,
		closeAction : "hide",
		items : this.formPanel = new Ext.form.FormPanel({
			items :[{
				xtype : "textfield",
				name : "subject",
				width : 550,
				fieldLabel : "موضوع",
				allowBlank : false
			},{
				xtype : "textarea",
				name : "details",
				width : 550,
				fieldLabel : "شرح مصوبه",
				rows : 4
			},
                {
                    xtype : "textfield",
                    name : "PreRecordNo",
                    width : 550,
                    fieldLabel : "شماره مصوبه قبلی",
                    allowBlank : true
                }
                ,
                {
				xtype : "textfield",
				name : "keywords",
				width : 550,
				fieldLabel : "کلمات کلیدی"
			},{
                    xtype : "combo",
                    store: new Ext.data.Store({
                        proxy:{
                            type: 'jsonp',
                            url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields :  ['PersonID','fullname']
                    }),
                    fieldLabel : "مسئول اجرا",
                    displayField: 'fullname',
                    valueField : "PersonID",
                    name : "PersonID",
                    width : 480,
                    allowBlank : true
            },{
				xtype : "container",
				layout : "hbox",
				style : "margin-right:110px",
				items : [{
					xtype : "button",
					text : "اضافه به لیست",
					iconCls : "add",
					handler : function(){
						me = MTG_MeetingRecordsObject;
						PersonComp = me.formWindow.down('[name=PersonID]');
						PersonRecord = PersonComp.getStore().getAt(
							PersonComp.getStore().find("PersonID", PersonComp.getValue()) );

						me.RecordPersons.push(PersonRecord.data.PersonID);
						me.ReordPersonNames.push(new Array(PersonRecord.data.fullname));
						me.formWindow.down("[itemId=GroupList]").bindStore(me.ReordPersonNames);
						PersonComp.setValue();
					}
				},{
					xtype : "button",
					text : "حذف از لیست",
					iconCls : "cross",
					handler : function(){

						me = MTG_MeetingRecordsObject;
						el = me.formWindow.down("[itemId=GroupList]");
						index = el.getStore().indexOf(el.getSelected()[0]);
						if(index >= 0)
						{
							me.RecordPersons.splice(index,1);
							me.ReordPersonNames.splice(index,1);
							el.clearValue();
							el.bindStore(me.ReordPersonNames);
						}
					}
				},{
					/*New Create*/
					xtype : "button",
					text : "حذف تمامی مسوولین اجرای سابق",
					iconCls : "cross",
					handler : function(){ MTG_MeetingRecordsObject.DeleteExecutors();
						this.up('window').hide();
					}
				}]
			},{
				xtype : "multiselect",
				itemId : "GroupList",
				store : this.ReordPersonNames,
				height : 100,
				width : 480
			}
			/* New Create */
			,{
				xtype : "shdatefield",
				name : "FollowUpDate",
				fieldLabel : "تاریخ پیگیری"
			}
			/*New Create*/
			,{
				xtype : "textfield",
				name : "descriptionDocs",
				width : 550,
				fieldLabel : "شرح مستندات"
			}
             /*END New Create*/
			,{
				xtype : "combo",
				name : "RecordStatus",
				fieldLabel : "وضعیت",
				store : new Ext.data.SimpleStore({
					data : [
						['CUR' , "جاری" ],
						['END' , "مختومه" ],
						['REF' , "ارجاعی" ]
					],
					fields : ['id','value']
				}),
				displayField : "value",
				valueField : "id",
				allowBlank : false
			},{
				xtype : "hidden",
				name :"MeetingID",
				value : this.MeetingID
			},{
				xtype : "hidden",
				name :"RecordID"
			}]
		}),
		buttons : [{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ MTG_MeetingRecordsObject.Save(); }
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){this.up('window').hide();}
		}]
	});
}

MTG_MeetingRecords.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
	"onclick='MTG_MeetingRecordsObject.DeleteRecord();' " +
	"style='background-repeat:no-repeat;background-position:center;" +
	"cursor:pointer;width:100%;height:16'></div>";
}

MTG_MeetingRecords.EditRender = function(v,p,r){
	
	return "<div align='center' title='ویرایش' class='edit' "+
	"onclick='MTG_MeetingRecordsObject.EditRecord();' " +
	"style='background-repeat:no-repeat;background-position:center;" +
	"cursor:pointer;width:100%;height:16'></div>";
}

MTG_MeetingRecords.LetterRender = function(v,p,r){
	
	return "<div align='center' title='ابلاغ مصوبه' class='letter' "+
	"onclick='MTG_MeetingRecordsObject.ShowLetterWindow();' " +
	"style='background-repeat:no-repeat;background-position:center;" +
	"cursor:pointer;width:100%;height:16'></div>";
}

MTG_MeetingRecords.attachRender = function(v,p,r){
	
	return "<div align='center' title='پیوست' class='attach' "+
	"onclick='MTG_MeetingRecordsObject.RecordDocuments();' " +
	"style='background-repeat:no-repeat;background-position:center;" +
	"cursor:pointer;width:100%;height:16'></div>";
}

MTG_MeetingRecords.StatusRender = function(v,p,r){
	
	switch(v)
	{
		case 'CUR' : return 'جاری';
		case 'END' : return 'مختومه';
		case 'REF' : return 'ارجاعی';
	}
}

MTG_MeetingRecords.prototype.Save = function(){
	
	if(!this.formPanel.getForm().isValid())
		return;
    
    me = MTG_MeetingRecordsObject;
    
	mask = new Ext.LoadMask(this.formWindow, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	
	this.formPanel.getForm().submit({
		url: this.address_prefix + 'meeting.data.php?task=SaveMeetingRecord',
        /*New Create*/
        params:{
            persons : Ext.encode(me.RecordPersons)
        },
        /*END New Create*/
		method: 'POST',
		success: function(){
			mask.hide();
			MTG_MeetingRecordsObject.grid.getStore().load();
			MTG_MeetingRecordsObject.formWindow.hide();
		},
		failure: function(){
			mask.hide();
			Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه گردید");
		}
	});
}

MTG_MeetingRecords.prototype.DeleteRecord = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = MTG_MeetingRecordsObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'meeting.data.php',
			params:{
				task: "RemoveMeetingRecords",
				RecordID : record.data.RecordID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				MTG_MeetingRecordsObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

MTG_MeetingRecords.prototype.DeleteExecutors = function(){

	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;

		me = MTG_MeetingRecordsObject;
		var record = me.grid.getSelectionModel().getLastSelected();

		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'meeting.data.php',
			params:{
				task: "RemoveAllRecordExecutors",
				RecordID : record.data.RecordID
			},
			method: 'POST',

			success: function(){
				mask.hide();
				MTG_MeetingRecordsObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

MTG_MeetingRecords.prototype.AddRecord = function(){
	
	this.formWindow.show();
	this.formPanel.getForm().reset();
	this.RecordPersons = new Array();
	this.ReordPersonNames = new Array();
	this.formWindow.down("[itemId=GroupList]").bindStore(this.ReordPersonNames);
}

MTG_MeetingRecords.prototype.EditRecord = function(){
	
	record = this.grid.getSelectionModel().getLastSelected();
	this.formWindow.show();
	this.formPanel.getForm().loadRecord(record);
	this.formPanel.down("[name=FollowUpDate]").setValue(MiladiToShamsi(record.data.FollowUpDate))
}

MTG_MeetingRecords.prototype.PrintRecords = function(){
	
	window.open(this.address_prefix + "PrintRecords.php?MeetingID=" + this.MeetingID);
}

MTG_MeetingRecords.prototype.ShowLetterWindow = function(){
	
	if(!this.LetterWin)
	{
		this.LetterWin = new Ext.Window({
			width : 500,
			title : "ابلاغ مصوبه",
			autoHeight : true,
			closeAction : "hide",
			modal : true,
			items : [{
				xtype : "textfield",
				name : "subject",
				fieldLabel : "عنوان"
			},{
				xtype : "combo",
				store: new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ['PersonID','fullname']
				}),
				fieldLabel : "کاربر",
				displayField: 'fullname',
				valueField : "PersonID",
				name : "PersonID",
				width : 480
			},{
				xtype : "container",
				layout : "hbox",
				style : "margin-right:110px",
				items : [{
					xtype : "button",
					text : "اضافه به لیست",
					iconCls : "add",
					handler : function(){
						me = MTG_MeetingRecordsObject;
						PersonComp = me.LetterWin.down('[name=PersonID]');
						PersonRecord = PersonComp.getStore().getAt( 
								PersonComp.getStore().find("PersonID", PersonComp.getValue()) );

						me.LetterPersons.push(PersonRecord.data.PersonID);
						me.LetterPersonNames.push(new Array(PersonRecord.data.fullname));
						me.LetterWin.down("[itemId=GroupList]").bindStore(me.LetterPersonNames);
						PersonComp.setValue();
					}
				},{
					xtype : "button",
					text : "حذف از لیست",
					iconCls : "cross",
					handler : function(){

						me = MTG_MeetingRecordsObject;
						el = me.LetterWin.down("[itemId=GroupList]");
						index = el.getStore().indexOf(el.getSelected()[0]);
						if(index >= 0)
						{
							me.LetterPersons.splice(index,1);
							me.LetterPersonNames.splice(index,1);
							el.clearValue();
							el.bindStore(me.LetterPersonNames);
						}
					}
				}]
			},{
				xtype : "multiselect",
				itemId : "GroupList",
				store : this.ReordPersonNames,
				height : 100,
				width : 480
			},

                {
                    xtype : "textarea",
                    emptyText : "شرح ارجاع",
                    fieldLabel: 'شرح ارجاع',
                    ItemId: 'textId',
                    name : "eerjaa",
                    rowspan : 5,
                    rows : 6,
                    width :270,
                }
			],
			buttons : [{
				text : "ارسال نامه",
				iconCls : "send",
				handler : function(){ MTG_MeetingRecordsObject.SendLetter(); }
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.LetterWin);
	}
	
	this.LetterWin.show();
	this.LetterWin.center();
}
      /* New Create */  
MTG_MeetingRecords.prototype.ShowAllLetterWindow = function(){

	if(!this.LetterForAllWin)
	{
		this.LetterForAllWin = new Ext.Window({
			width : 500,
			title : "ابلاغ مصوبه",
			autoHeight : true,
			modal : true,
			items : [{
				xtype : "combo",
				store: new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ['PersonID','fullname']
				}),
				fieldLabel : "کاربر",
				displayField: 'fullname',
				valueField : "PersonID",
				name : "PersonID",
				width : 480
			},{
				xtype : "container",
				layout : "hbox",
				style : "margin-right:110px",
				items : [{
					xtype : "button",
					text : "اضافه به لیست",
					iconCls : "add",
					handler : function(){
						me = MTG_MeetingRecordsObject;
						PersonComp = me.LetterForAllWin.down('[name=PersonID]');
						PersonRecord = PersonComp.getStore().getAt(
							PersonComp.getStore().find("PersonID", PersonComp.getValue()) );

						me.LetterPersons.push(PersonRecord.data.PersonID);
						me.LetterPersonNames.push(new Array(PersonRecord.data.fullname));
						me.LetterForAllWin.down("[itemId=GroupList]").bindStore(me.LetterPersonNames);
						PersonComp.setValue();
					}
				},{
					xtype : "button",
					text : "حذف از لیست",
					iconCls : "cross",
					handler : function(){

						me = MTG_MeetingRecordsObject;
						el = me.LetterForAllWin.down("[itemId=GroupList]");
						index = el.getStore().indexOf(el.getSelected()[0]);
						if(index >= 0)
						{
							me.LetterPersons.splice(index,1);
							me.LetterPersonNames.splice(index,1);
							el.clearValue();
							el.bindStore(me.LetterPersonNames);
						}
					}
				}]
			},{
				xtype : "multiselect",
				itemId : "GroupList",
				store : this.ReordPersonNames,
				height : 100,
				width : 480
			},

				{
					xtype : "textarea",
					emptyText : "شرح ارجاع",
					fieldLabel: 'شرح ارجاع',
					itemId: 'textId',
					name : "eerjaa",
					rowspan : 5,
					rows : 6,
					width :270,
				}

			],
			buttons : [{
				text : "ارسال نامه",
				iconCls : "send",
				handler : function(){ MTG_MeetingRecordsObject.SendAllLetter(); }
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});

		Ext.getCmp(this.TabID).add(this.LetterForAllWin);

	}

	this.LetterForAllWin.show();
	this.LetterForAllWin.center();
}

MTG_MeetingRecords.prototype.SendLetter = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به ابلاغ نامه مصوبه می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = MTG_MeetingRecordsObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ارسال نامه ...'});
		mask.show();
        textareaValue = this.LetterWin.down('[itemId=textId]').getValue(); /* New Create */
		Ext.Ajax.request({
			url: me.address_prefix + 'meeting.data.php',
			params:{
				task: "SendRecordLetter",
				RecordID : record.data.RecordID,
				persons : Ext.encode(me.LetterPersons),
				subject : me.LetterWin.down("[name=subject]").getValue(),
                eerjaa : Ext.encode(textareaValue) /* New Create */
			},
			method: 'POST',

			success: function(response){
				res = Ext.decode(response.responseText);
				mask.hide();
				if(res.success)
				{
					MTG_MeetingRecordsObject.LetterWin.hide();
					Ext.MessageBox.alert("","نامه ابلاغ مصوبه با موفقیت به افراد مورد نظر ارسال گردید");
				}
				else if(res.data == "")
					Ext.MessageBox.alert("",res.data);
				else
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه گردید");
					
			},
			failure: function(){}
		});
	});
}
/* New Create */  
MTG_MeetingRecords.prototype.SendAllLetter = function(){

	Ext.MessageBox.confirm("","آیا مایل به ابلاغ نامه مصوبه ها می باشید؟", function(btn){
		if(btn == "no")
			return;

		me = MTG_MeetingRecordsObject;

		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ارسال نامه ...'});
		mask.show();

		textareaValue = me.LetterForAllWin.down('[itemId=textId]').getValue();
		Ext.Ajax.request({
			url: me.address_prefix + 'meeting.data.php',
			params:{
				task: "SendRecordsLetter",
				MeetingID : me.MeetingID,
				persons : Ext.encode(me.LetterPersons),
				eerjaa : Ext.encode(textareaValue)
			},
			method: 'POST',

			success: function(response){
				res = Ext.decode(response.responseText);
				mask.hide();
				if(res.success)
				{
					MTG_MeetingRecordsObject.LetterForAllWin.hide();
					Ext.MessageBox.alert("","نامه ابلاغ مصوبه با موفقیت به افراد مورد نظر ارسال گردید");
				}

				else
					Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه گردید");

			},
			failure: function(){}
		});
	});
};
/* END New Create */

MTG_MeetingRecords.prototype.RecordDocuments = function(){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 720,
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
			ObjectType : 'meetingrecord',
			ObjectID : record.data.RecordID
		}
	});
}

var MTG_MeetingRecordsObject = new MTG_MeetingRecords();	

</script>