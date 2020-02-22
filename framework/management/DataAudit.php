<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.01
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg",$js_prefix_address . "framework.data.php?task=selectDataAudits","div_grid");


$col = $dg->addColumn("عنوان سیستم","SysName","string");

$col = $dg->addColumn("کاربر","fullname","string");
$col->width = 150;

$col = $dg->addColumn("جدول مربوطه","table_comment","string");
$col->width = 120;

$col = $dg->addColumn("تاریخ عملیات","ActionTime",  GridColumn::ColumnType_datetime);
$col->width = 120;

$col = $dg->addColumn("IP","IPAddress");
$col->width = 120;

$col = $dg->addColumn("تاریخ عملیات","ActionTime",  GridColumn::ColumnType_datetime);
$col->width = 120;
$col = $dg->addColumn("عملیات","ActionType", "");
$col->renderer = "DataAudit.ActionTypeRender";
$col->width = 110;

$col = $dg->addColumn("کلید اصلی","MainObjectID");
$col->width = 80;

$col = $dg->addColumn("کلید فرعی","SubObjectID");
$col->width = 80;

$dg->height = 350;
$dg->width = 1000;
$dg->DefaultSortField = "ActionTime";
$dg->autoExpandColumn = "SysName";
$dg->EnableSearch = false;
$grid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>
<script type="text/javascript">
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

DataAudit.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function DataAudit()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("div_form"),
		width : 800,
		frame : true,
		title : "تنظیمات گزارش عملکرد",
		layout : {
			type : "table",
			columns : 2			
		},
		defaults : {
			width : 350
		},
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../person/persons.data.php?' +
						"task=selectPersons",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "کاربر",
			displayField : "fullname",
			pageSize : 20,
			valueField : "PersonID",
			hiddenName :"PersonID"
		},{
			xtype : "combo",
			store: new Ext.data.Store({
				autoLoad : true,
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'framework.data.php?task=selectSystems',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['SystemID','SysName']
			}),
			displayField: 'SysName',
			valueField : "SystemID",
			hiddenName : "SystemID",
			queryMode: "local",
			fieldLabel : "سیستم"
		},{
			xtype : "shdatefield",
			name : "StartDate",
			fieldLabel : "از تاریخ"
		},{
			xtype : "shdatefield",
			name : "EndDate",
			fieldLabel : "تا تاریخ"
		}],
		buttons :[{
			text : "گزارش عملکرد",
			iconCls : "report",
			handler : function(){
				if(!DataAuditObject.grid.rendered)
					DataAuditObject.grid.render(DataAuditObject.get("div_grid"));
				else
					DataAuditObject.grid.getStore().loadPage(1);
			}
		}]
	});
	this.grid = <?= $grid?>;
	this.grid.getStore().proxy.form = this.get("MainForm");
}

DataAudit.ActionTypeRender = function(v,p,r){
	switch(v)
	{
		case "ADD" : return "ایجاد رکورد";
		case "DELETE" : return "حذف رکورد";
		case "UPDATE" : return "ویرایش رکورد";
		case "VIEW" : return "مشاهده رکورد";
		case "SEARCH" : return "جستجوی رکورد";
		case "SEND" : return "ارسال رکورد";
		case "RETURN" : return "برگشت رکورد";
		case "CONFIRM" : return "تایید رکورد";
		case "REJECT" : return "رد رکورد";
		case "OTHER" : return "سایر";
	}
}

var DataAuditObject = new DataAudit();

</script>
<center>
	<br>
	<form id="MainForm">
		<div id="div_form"></div>
	</form>
	<br>
	<div id="div_grid"></div>
</center>