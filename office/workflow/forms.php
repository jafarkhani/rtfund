<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.09
//-----------------------------
ini_set("display_errors", "On");

require_once '../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "form.data.php?task=SelectForms", "div_dg");

$dg->addColumn("عنوان", "FormTitle");

$col = $dg->addColumn("فرایند گردش", "FlowDesc");
$col->width = 200;


$col = $dg->addColumn("<font style=font-size:10px>کاربر</font>","IsStaff","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->editor = ColumnEditor::CheckField("","YES");
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>مشتری</font>","IsCustomer","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->editor = ColumnEditor::CheckField("","YES");
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>سهامدار</font>","IsShareholder","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->editor = ColumnEditor::CheckField("","YES");
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>سرمایه گذار</font>","IsAgent","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->editor = ColumnEditor::CheckField("","YES");
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>حامی</font>","IsSupporter","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->editor = ColumnEditor::CheckField("","YES");
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("<font style=font-size:10px>کارشناس</font>","IsExpert","string");
$col->renderer = "function(v){return (v=='YES') ? '٭' : '';}";
$col->editor = ColumnEditor::CheckField("","YES");
$col->align = "center";
$col->width = 35;

$col = $dg->addColumn("افراد","","");
$col->renderer = "WFM_form.PersonsRender";
$col->sortable = false;
$col->align = "center";
$col->width = 40;

$col = $dg->addColumn("دسترسی","","");
$col->renderer = "WFM_form.AccessRender";
$col->sortable = false;
$col->align = "center";
$col->width = 40;

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "FormID");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return WFM_form.OperationRender(v,p,r);}";
	$col->width = 40;
}
if($accessObj->AddFlag)
{
	$dg->addButton("", "ایجاد فرم جدید", "add", "function(){WFM_formObject.ShowNewForm();}");
	$dg->addButton("", "کپی فرم", "copy", "function(){WFM_formObject.copyForm();}");
}
$dg->title = "فرم ها";
$dg->DefaultSortField = "FormID";
$dg->emptyTextOfHiddenColumns = true;
$dg->DefaultSortDir = "desc";
$dg->autoExpandColumn = "FormTitle";
$dg->width = 780;
$dg->height = 400;
$dg->pageSize = 15;

$grid = $dg->makeGrid_returnObjects();
?>
<script>
	
WFM_form.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix: "<?= $js_prefix_address ?>",

	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
};

WFM_form.OperationRender = function(v,p,r){

	return "<div align='center' title='حذف' class='remove' "+
		"onclick='WFM_formObject.RemoveForm();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;height:16'></div>";
}

WFM_form.PersonsRender = function(v,p,r){
	return "<div align='center' title='افراد' class='list' onclick='WFM_formObject.ShowPersons();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;height:16'></div>";
}

WFM_form.AccessRender = function(v,p,r){
	return "<div align='center' title='دسترسی های فرم' class='user' onclick='WFM_formObject.ShowAccess();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;height:16'></div>";
}

function WFM_form() {

	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_dg"));

	this.grid.on("itemdblclick", function(view, record){
		WFM_formObject.EditItem();
	});
}

WFM_formObject = new WFM_form();

WFM_form.prototype.ShowNewForm = function () {
	framework.OpenPage(this.address_prefix + "NewForm.php", "ایجاد فرم جدید");
}

WFM_form.prototype.EditItem = function () {
	
	record = WFM_formObject.grid.getSelectionModel().getLastSelected();
	framework.OpenPage(this.address_prefix + "NewForm.php", "ویرایش فرم",
			{FormID: record.data.FormID});
}

WFM_form.prototype.RemoveForm = function () {
	
	Ext.MessageBox.confirm("","آیا مایل به حذف فرم می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = WFM_formObject;
		mask = new Ext.LoadMask(WFM_formObject.grid, {msg:'در حال حذف...'});
		mask.show();
	
		Ext.Ajax.request({
			url: me.address_prefix + 'form.data.php?task=deleteForm',
			params: {                
				FormID: me.grid.getSelectionModel().getLastSelected().data.FormID            
			},
			method: 'POST',
			success: function (res) {
				mask.hide();
				var sd = Ext.decode(res.responseText);
				if (!sd.success) {
					if (sd.data != '')
						Ext.MessageBox.alert('', sd.data); 
					else
						Ext.MessageBox.alert('', 'خطا در اجرای عملیات');
					return;
				}
				WFM_formObject.grid.getStore().load();
			}
		});
	});
		
}

WFM_form.prototype.copyForm = function () {
	
	record = this.grid.getSelectionModel().getLastSelected();
	if(record == null)
	{
		Ext.MessageBox.alert("","ابتدا فرم مورد نظر را خود را انتخاب کنید");
		return;
	}
	
	Ext.MessageBox.confirm("","آیا مایل به ایجاد کپی از فرم می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = WFM_formObject;
		mask = new Ext.LoadMask(me.grid, {msg:'در حال کپی فرم...'});
		mask.show();
	
		Ext.Ajax.request({
			url: me.address_prefix + 'form.data.php?task=CopyForm',
			params: {                
				FormID: me.grid.getSelectionModel().getLastSelected().data.FormID            
			},
			method: 'POST',
			success: function (res) {
				mask.hide();
				WFM_formObject.grid.getStore().load();
			}
		});
	});
		
}

WFM_form.prototype.ShowPersons = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	if(!this.PersonsWin)
	{
		this.PersonsWin = new Ext.window.Window({
			width : 765,
			title : "لیست کاربران مجاز جهت پر کردن فرم",
			bodyStyle : "background-color:white;text-align:-moz-center",
			height : 565,
			modal : true,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "FormPersons.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.PersonsWin);
	}
	this.PersonsWin.show();
	this.PersonsWin.center();
	this.PersonsWin.loader.load({
		params : { 
			ExtTabID : this.PersonsWin.getEl().id,
			MenuID : <?= $_REQUEST["MenuID"] ?>,
			FormID : record.data.FormID}
	});
}

WFM_form.prototype.ShowAccess = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	if(!this.AccessWin)
	{
		this.AccessWin = new Ext.window.Window({
			width : 500,
			title : "تعیین دسترسی های آیتم های فرم برای هر مرحله",
			bodyStyle : "background-color:white;text-align:-moz-center",
			height : 480,
			modal : true,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "FormItemAccess.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.AccessWin);
	}
	this.AccessWin.show();
	this.AccessWin.center();
	this.AccessWin.loader.load({
		params : { 
			ExtTabID : this.AccessWin.getEl().id,
			MenuID : <?= $_REQUEST["MenuID"] ?>,
			FormID : record.data.FormID}
	});
}

</script>
<br>
<center>    
    <div id="div_dg"></div>
</center>
