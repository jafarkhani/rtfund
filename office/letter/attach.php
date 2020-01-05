<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../header.inc.php';
require_once 'letter.class.php';
require_once DOCUMENT_ROOT . "/framework/baseInfo/baseInfo.class.php";
require_once inc_dataGrid;

if(empty($_POST["LetterID"]))
	die();

$LetterID = $_POST["LetterID"];
$SendID = !empty($_POST["SendID"]) ? $_POST["SendID"] : "0";
$LetterObj = new OFC_letters($LetterID);

$AddAccess = true;
$DelAccess = true;
$DelAccess = $LetterObj->IsSigned == "YES" ? false : true;
$DelAccess = $SendID != "0" ? true : $DelAccess;
$AddAccess = session::IsPortal() ? false : $AddAccess;

//------------------------------------------------------
$dg = new sadaf_datagrid("dg", $js_prefix_address . "../dms/dms.data.php?" .
		"task=SelectAll&ObjectType=letterAttach&ObjectID=" . $LetterID . 
		($SendID > 0 ? "&checkRegPerson=true&ObjectID2=" . $SendID : ""), "grid_div");

$dg->addColumn("", "RowID", "", true);
$dg->addColumn("", "DocumentID", "", true);
$dg->addColumn("", "ObjectType", "", true);
$dg->addColumn("", "ObjectID", "", true);
$dg->addColumn("", "IsConfirm", "", true);
$dg->addColumn("", "RegPersonID", "", true);
$dg->addColumn("", "param1Title", "", true);
$dg->addColumn("", "DocTypeDesc", "", true);
$dg->addColumn("", "param1", "", true);
$dg->addColumn("", "DocType", "", true);

$col = $dg->addColumn("عنوان پیوست", "DocDesc", "");

$col = $dg->addColumn("ثبت کننده", "regfullname", "");
$col->width = 150;

$col = $dg->addColumn("فایل", "HaveFile", "");
$col->renderer = "function(v,p,r){return ManageDocument.FileRender(v,p,r)}";
$col->align = "center";
$col->width = 50;

if($AddAccess || $DelAccess)
{
	$col = $dg->addColumn("عملیات", "", "");
	$col->renderer = "function(v,p,r){return ManageDocument.OperationRender(v,p,r)}";
	$col->width = 60;
}

$dg->addButton("", "دانلود کلیه فایل ها", "archive", "ManageDocument.ZipDownload");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 310;
$dg->width = 600;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "DocTypeDesc";
$dg->autoExpandColumn = "DocDesc";
$grid = $dg->makeGrid_returnObjects();

?>
<script>
	
ManageDocument.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	LetterID : <?= $LetterID ?>,
	SendID : <?= $SendID ?>,
	AddAccess : <?= $AddAccess ? "true" : "false" ?>,
	DelAccess : <?= $DelAccess ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ManageDocument(){
	if(this.AddAccess)
	{
		this.formPanel = new Ext.form.Panel({
			renderTo: this.get("MainForm"),      
			width : 600,
			style : "margin-top:10px",
			bodyPadding: '8 0 8 0',
			defaults :{
				labelWidth : 70
			},
			frame: true,
			layout : "hbox",
			items : [{
				xtype : "textfield",
				width : 250,
				fieldLabel : "شرح پیوست",
				name : "DocDesc"
			},{
				xtype : "filefield",
				width : 200,
				allowBlank : false,
				fieldLabel : "فایل مدرک",
				name : "FileType",
				style : "margin-right:20px"
			},{
				xtype : "button",
				text : "ذخیره",
				style : "border-width:1px;",
				iconCls : "save",
				handler : function(){ ManageDocumentObject.SaveDocument(); }
			},{
				xtype : "button",
				text : "پاکن",
				style : "border-width:1px;",
				iconCls : "clear",
				handler : function(){ ManageDocumentObject.formPanel.getForm().reset(); }

			},{
				xtype : "hidden",
				name : "DocumentID"
			}]
		});
	}
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));
}

ManageDocument.FileRender = function(v,p,r){
	
	if(v == "false")
		return "";
	
	return "<div align='center' title='مشاهده فایل' class='attach' "+
		"onclick='ManageDocument.ShowFile(" + r.data.DocumentID + "," + r.data.ObjectID + "," + r.data.RowID + ");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16;float:right'></div>";
}

ManageDocument.ShowFile = function(DocumentID, ObjectID, RowID){
	
	window.open("/office/dms/ShowFile.php?DocumentID=" + DocumentID + "&ObjectID=" + ObjectID + 
		"&RowID=" + RowID);
}

ManageDocument.OperationRender = function(v,p,r){
	
	if(r.data.RegPersonID != "<?= BSC_jobs::GetModirAmelPerson()->PersonID ?>" && 
		(r.data.IsConfirm == "YES" || r.data.RegPersonID != "<?= $_SESSION["USER"]["PersonID"] ?>" ) )
		return "";
	
	var st = "";
	if(ManageDocumentObject.AddAccess)
		st += "<div align='center' title='ویرایش' class='edit' "+
		"onclick='ManageDocumentObject.EditDocument();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16;float:right'></div>";
	if(ManageDocumentObject.DelAccess)
		st += "<div align='center' title='حذف' class='remove' "+
		"onclick='ManageDocumentObject.DeleteDocument();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16;float:right'></div>" ;		
		
	return st;
}

ManageDocument.prototype.EditDocument = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.formPanel.getForm().reset();
	this.formPanel.down("[name=DocumentID]").setValue(record.data.DocumentID);
	this.formPanel.loadRecord(record);	
}

ManageDocument.prototype.SaveDocument = function(){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.formPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix +  '../dms/dms.data.php',
		method: "POST",
		isUpload : true,
		params: {
			task: "SaveDocument",
			param1 : 0,
			DocType : 0,
			ObjectID : this.LetterID,
			ObjectID2 : this.SendID,
			ObjectType : 'letterAttach'
		},
		success: function(form,action){
			mask.hide();

			if(action.result.success)
			{   
				ManageDocumentObject.grid.getStore().load();
				ManageDocumentObject.formPanel.getForm().reset();
			}
			else
			{
				if(action.result.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(action.result.data);
			}
		},
		failure: function(form,action){
			Ext.MessageBox.alert("Error",action.result.data);
			mask.hide();
		}
	});
}

ManageDocument.prototype.DeleteDocument = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ManageDocumentObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + '../dms/dms.data.php',
			params:{
				task: "DeleteDocument",
				DocumentID : record.data.DocumentID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				ManageDocumentObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

ManageDocument.ZipDownload  = function(){
	
	me = ManageDocumentObject;
	
	mask = new Ext.LoadMask(Ext.getCmp(me.TabID),{msg:'در حال آماده سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: me.address_prefix +  '../dms/dms.data.php',
		method: "POST",
		isUpload : true,
		params: {
			task: "CreateZip",
			ObjectID : me.LetterID,
			ObjectType : 'letterAttach'
		},
		success: function(response){
			mask.hide();
			result = Ext.decode(response.responseText);
			if(result.success)
			{   
				window.open("/storage/files.zip");
			}
			else
			{
				if(result.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(result.data);
			}
		},
		failure: function(){
			mask.hide();
		}
	});
}
 

ManageDocumentObject = new ManageDocument();

</script>
<div id="MainForm"></div>
<div id="div_grid"><div>