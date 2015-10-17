<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.06
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "../../dms/dms.data.php?task=SelectAll&ObjectType=Person&ObjectID=?" .
		$_SESSION["USER"]["PersonID"] , "grid_div");

$dg->addColumn("", "DocumentID", "", true);
$dg->addColumn("", "IsConfirm", "", true);

$col = $dg->addColumn("مدرک", "DocType", "");
$col->editor = ColumnEditor::ComboBox(PdoDataAccess::runquery("select * from BaseInfo where typeID=8"), "InfoID", "InfoDesc");
$col->width = 140;

$col = $dg->addColumn("توضیح", "DocDesc", "");
$col->editor = ColumnEditor::TextField(true);

$col = $dg->addColumn("فایل", "FileType", "");
$col->renderer = "function(v,p,r){return PersonalInfo.FileRender(v,p,r)}";
$col->editor = "this.FileCmp";
$col->width = 100;

$col = $dg->addColumn("عملیات", "", "");
$col->renderer = "function(v,p,r){return PersonalInfo.OperationRender(v,p,r)}";
$col->width = 100;

$dg->addButton("", "اضافه مدرک", "add", "function(){PersonalInfoObject.AddDocument();}");

$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return PersonalInfoObject.SaveDocument();}";

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 350;
$dg->width = 690;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "DocTypeDesc";
$dg->autoExpandColumn = "DocDesc";
$grid = $dg->makeGrid_returnObjects();

?>
<script>
	
PersonalInfo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function PersonalInfo()
{
	this.FileCmp = new Ext.form.File({
		name : "FileType"
	});
	
	
	this.grid = <?= $grid ?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsConfirm == "YES")
			return "greenRow";
		return "";
	}

	this.mainPanel = new Ext.form.FormPanel({
		frame: true,
		hidden : true,
		title: 'اطلاعات شخصی',
		width: 400,
		defaults: {
			anchor : "98%"
		},
		items: [{
			xtype : "textfield",
			fieldLabel: 'نام',
			name: 'fname'
		},{
			xtype : "textfield",
			fieldLabel: 'نام خانوادگی',
			name: 'lname'
		},{
			xtype : "textfield",
			fieldLabel: 'نام شرکت',
			name: 'CompanyName'
		},{
			xtype : "textfield",
			regex: /^\d{10}$/,
			maskRe: /[\d\-]/,
			fieldLabel: 'کد ملی',
			name: 'NationalID'
		},{
			xtype : "textfield",
			regex: /^\d{10}$/,
			maskRe: /[\d\-]/,
			fieldLabel: 'کد اقتصادی',
			name: 'EconomicID'
		},{
			xtype : "textfield",
			regex: /^\d{11}$/,
			maskRe: /[\d\-]/,
			fieldLabel: 'شماره تلفن',
			name: 'PhoneNo'
		},{
			xtype : "textfield",
			regex: /^\d{11}$/,
			maskRe: /[\d\-]/,
			fieldLabel: 'تلفن همراه',
			name: 'mobile'
		},{
			xtype : "textfield",
			vtype : "email",
			fieldLabel: 'پست الکترونیک',
			name: 'email',
			fieldStyle : "direction:ltr"
		},{
			xtype : "textarea",
			fieldLabel: 'آدرس',
			width : 368,
			name: 'address'
		}],

		buttons : [{
			text : "ذخیره",
			iconCls: 'save',
			handler: function() {
				
				me = PersonalInfoObject;
				mask = new Ext.LoadMask(me.mainPanel, {msg:'در حال ذخيره سازي...'});
				mask.show();  
				me.mainPanel.getForm().submit({
					clientValidation: true,
					url: me.address_prefix + 'global.data.php?task=SavePersonalInfo' , 
					method: "POST",
					
					success : function(form,result){
						mask.hide();
						Ext.MessageBox.alert("","اطلاعات با موفقیت ذخیره شد");
					},
					failure : function(){
						mask.hide();
						Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
					}
				});
			}

		}]
	});

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
    mask.show();    
	
	this.store = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + "global.data.php?task=SelectPersonInfo",
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["IsReal","fname","lname","CompanyName","UserName","NationalID","EconomicID","PhoneNo","mobile","address","email"],
		autoLoad : true,
		listeners :{
			load : function(){
				PersonalInfoObject.mainPanel.loadRecord(this.getAt(0));
				
				if(this.getAt(0).data.IsReal == "YES")
				{
					PersonalInfoObject.mainPanel.down("[name=CompanyName]").hide();
					PersonalInfoObject.mainPanel.down("[name=EconomicID]").hide();
					
				}
				else
				{
					PersonalInfoObject.mainPanel.down("[name=fname]").hide();
					PersonalInfoObject.mainPanel.down("[name=lname]").hide();
					PersonalInfoObject.mainPanel.down("[name=NationalID]").hide();
				}
				
				PersonalInfoObject.mainPanel.show();
				PersonalInfoObject.mainPanel.center();
				mask.hide();    
			}
		}
	});	
	
	this.tabPanel = new Ext.TabPanel({
		renderTo: this.get("mainForm"),
		activeTab: 0,
		plain:true,
		autoHeight : true,
		width: 750,
		height : 420,
		defaults:{
			autoHeight: true, 
			autoWidth : true            
		},
		items:[{
			title : "اطلاعات شخصی",
			items : this.mainPanel
		},{
			title : "مدارک",
			style : "padding:20px",
			items : this.grid
		}]
	});	
}

PersonalInfoObject = new PersonalInfo();

PersonalInfo.prototype.PersonalInfo = function()
{
	if(this.get("new_pass").value != this.get("new_pass2").value)
	{
		return;
	}
}

PersonalInfo.FileRender = function(v,p,r){
	
	return "<div align='center' title='مشاهده فایل' class='attach' "+
		"onclick='PersonalInfo.ShowFile(" + r.data.DocumentID + ");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16;float:right'></div>";
}

PersonalInfo.ShowFile = function(DocumentID){
	
	window.open("../../dms/ShowFile.php?DocumentID=" + DocumentID);
}

PersonalInfo.OperationRender = function(v,p,r){
	
	if(r.data.IsConfirm == "YES")
		return "";
	
	return "<div align='center' title='ویرایش' class='edit' "+
		"onclick='PersonalInfoObject.EditDocument();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16;float:right'></div>" + 
		
		 "<div align='center' title='حذف' class='remove' "+
		"onclick='PersonalInfoObject.DeleteDocument();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16;float:right'></div>";		
}

PersonalInfo.prototype.AddDocument = function(){
	
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		DocumentID: null,
		DocDesc: null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

PersonalInfo.prototype.SaveDocument = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'../../dms/dms.data.php',
		method: "POST",
		isUpload : true,
		form : this.get("MainForm"),
		params: {
			task: "SaveDocument",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				PersonalInfoObject.grid.getStore().load();
			}
			else
			{
				if(st.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(st.data);
			}
		},
		failure: function(){}
	});
}

PersonalInfo.prototype.DeleteDocument = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PersonalInfoObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + '../../dms/dms.data.php',
			params:{
				task: "DeleteDocumentID",
				DocumentID : record.data.DocumentID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				PersonalInfoObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}


</script>
<form id="MainForm" enctype="multipart/form-data">
	<div id="mainForm"><div>
</form>