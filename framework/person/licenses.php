<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;

$PersonID = session::IsPortal() ? $_SESSION["USER"]["PersonID"] : $_REQUEST["PersonID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . 
		"persons.data.php?task=SelectLicenses&PersonID=" . $PersonID,"div_grid_user");

$dg->addColumn("","LicenseID","string", true);
$dg->addColumn("","PersonID","string", true);
$dg->addColumn("","IsConfirm","string", true);


$col = $dg->addColumn("عنوان مجوز","title","string");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("شماره مجوز","LicenseNo","string");
$col->editor = ColumnEditor::TextField();
$col->width = 150;

$col = $dg->addColumn("تاریخ اعتبار","ExpDate",  GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 80;

$col = $dg->addColumn("توضیحات کارشناس", "RejectDesc", "");
$col->renderer = "function(v,p,r){return License.commentRender(v,p,r)}";
$col->align = "center";
$col->width = 150;

if(session::IsFramework())
{
	$col = $dg->addColumn("تایید/رد", "", "");
	$col->renderer = "function(v,p,r){return License.ConfirmRender(v,p,r)}";
	$col->width = 60;
}

$col = $dg->addColumn("حذف","","");
$col->renderer = "License.deleteRender";
$col->sortable = false;
$col->width = 40;

$dg->addButton = true;
$dg->addHandler = "function(){LicenseObject.Adding();}";

$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(v,p,r){return LicenseObject.saveData(v,p,r);}";


$dg->height = 350;
$dg->width = 730;
$dg->DefaultSortField = "LicenseID";
$dg->autoExpandColumn = "title";
$dg->editorGrid = true;
$dg->title = "مجوز ها/ گواهینامه ها";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$grid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>
<script type="text/javascript">

License.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	PersonID : <?= $PersonID ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

License.deleteRender = function(v,p,r)
{
	if(r.data.IsConfirm == "YES")
		return "";
	return "<div align='center' title='حذف ' class='remove' onclick='LicenseObject.Deleting();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

function License()
{
	this.grid = <?= $grid?>;
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsConfirm == "YES")
			return "greenRow";
		if(record.data.IsConfirm == "NO")
			return "pinkRow";
		return "";
	}
	this.grid.plugins[0].on("beforeedit",function(rowEditor,e){

		var record = LicenseObject.grid.getStore().getAt(e.rowIdx);
		if(record.data.IsConfirm == "YES")
			return false;
	});
	this.grid.addDocked({
		xtype: 'toolbar',
		dock: 'bottom',
		items: [{ 
			xtype: 'container', 
			width : 680,
			html : "ردیف های سبز رنگ ردیف های تایید شده و برابر اصل شده توسط صندوق بوده و قابل تغییر نمی باشند"+
				"<br>ردیف های قرمز ردیف های رد شده توسط صندوق می باشند"+
				"<br>جهت ویرایش روی ردیف مورد نظر دبل کلیک نمایید"
		}
    ]});
	this.grid.render(this.get("div_grid"));
}

License.commentRender = function(v,p,r){
		
	if(v == null)
		return "";
	
	return v.replace(/\n/g, "<br>");
}

var LicenseObject = new License();

License.prototype.Adding = function()
{
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		LicenseID : "",
		PersonID : this.PersonID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

License.prototype.saveData = function(store,record)
{
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'SaveLicense',
			record : Ext.encode(record.data)
		},
		url: this.address_prefix +'persons.data.php',
		method: 'POST',

		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				LicenseObject.grid.getStore().load();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}
	});
}

License.prototype.Deleting = function()
{
	Ext.MessageBox.confirm("","آيا مايل به حذف مي باشيد؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = LicenseObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		Ext.Ajax.request({
		  	url : me.address_prefix + "persons.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeleteLicense",
		  		LicenseID : record.data.LicenseID
		  	},
		  	success : function(response,o)
		  	{
		  		LicenseObject.grid.getStore().load();
		  	}
		});
		
	});

}

<?if(session::IsFramework()){?>

License.ConfirmRender = function(v,p,r){
	
	if(r.data.IsConfirm == "YES")
		return "";
	
	st = "<div align='center' title='تایید' class='tick' "+
		"onclick='LicenseObject.beforeConfirm(\"YES\");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:24px;height:16;float:right'></div>";

	st += "<div align='center' title='رد' class='cross' "+
		"onclick='LicenseObject.beforeConfirm(\"NO\");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:18px;height:16;float:right'></div>";
	
	return st;
}

License.prototype.beforeConfirm = function(mode){
	if(mode == "YES")
	{
		Ext.MessageBox.confirm("","مجوز مربوطه مورد تایید می باشد؟", function(btn){
			if(btn == "no")
				return;
			
			LicenseObject.Confirm("YES");
		});
		return;
	}
	if(!this.confirmWin)
	{
		this.confirmWin = new Ext.window.Window({
			width : 412,
			height : 198,
			modal : true,
			title : "دلیل رد برای مشتری",
			bodyStyle : "background-color:white",
			items : [{
				xtype : "textarea",
				width : 400,
				rows : 8,
				name : "RejectDesc"
			}],
			closeAction : "hide",
			buttons : [{
				text : "رد",
				iconCls : "cross",
				handler : function(){LicenseObject.Confirm('NO');}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		
		Ext.getCmp(this.TabID).add(this.confirmWin);
	}
	this.confirmWin.show();
	this.confirmWin.center();
}

License.prototype.Confirm = function(mode){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(this.grid,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'persons.data.php',
		method: "POST",
		params: {
			task: "ConfirmLicense",
			LicenseID : record.data.LicenseID,
			mode : mode,
			RejectDesc : mode == "NO" ? this.confirmWin.down("[name=RejectDesc]").getValue() : ""
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				LicenseObject.grid.getStore().load();
				if(LicenseObject.confirmWin)
					LicenseObject.confirmWin.hide();
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

<?}?>
</script>
<center>
	<div id="div_grid"></div>
</center>