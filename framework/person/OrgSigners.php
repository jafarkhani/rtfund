<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------
require_once("../../header.inc.php");
require_once inc_dataGrid;

$PersonID = session::IsPortal() ? $_SESSION["USER"]["PersonID"] : $_REQUEST["PersonID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . 
		"persons.data.php?task=SelectSigners&PersonID=" . $PersonID,"div_grid_user");

$dg->addColumn("","RowID","string", true);
$dg->addColumn("","PersonID","string", true);
$dg->addColumn("","address","string", true);
$dg->addColumn("","PostalCode","string", true);
$dg->addColumn("","ShNo","string", true);
$dg->addColumn("","ShPlace","string", true);
$dg->addColumn("","email","string", true);
$dg->addColumn("","sex","string", true);
$dg->addColumn("","NationalID","string", true);

$col = $dg->addColumn("سمت","PostDesc","string");
$col->width = 70;

$col = $dg->addColumn("نام و نام خانوادگی ","fullname","string");

$col = $dg->addColumn("نام پدر","FatherName","string");
$col->width = 60;

$col = $dg->addColumn("تاریخ تولد","BirthDate",  GridColumn::ColumnType_date);
$col->width = 80;

$col = $dg->addColumn("تلفن","telephone","string");
$col->width = 90;

$col = $dg->addColumn("موبایل","mobile","string");
$col->width = 90;

$dg->addPlugin("{
            ptype: 'rowexpander',
            rowBodyTpl : [
				'<p><b>شماره شناسنامه : </b> {ShNo}&nbsp;&nbsp;&nbsp;&nbsp;',
				'<b>کد ملی : </b>{NationalID}&nbsp;&nbsp;&nbsp;&nbsp;',
				'<b>صادره از : </b>{ShPlace}&nbsp;&nbsp;&nbsp;&nbsp;',
				'<b>ایمیل : </b> {email}</p>',
				'<p><b>آدرس : </b> {address}&nbsp;&nbsp;&nbsp;&nbsp;<b>کد پستی : </b>{PostalCode}</p>'
            ]
        }");

$col = $dg->addColumn("ویرایش","","");
$col->renderer = "OrgSigner.editRender";
$col->sortable = false;
$col->width = 40;

$col = $dg->addColumn("حذف","","");
$col->renderer = "OrgSigner.deleteRender";
$col->sortable = false;
$col->width = 40;

$dg->addButton = true;
$dg->addHandler = "function(){OrgSignerObject.Adding();}";

$dg->height = 350;
$dg->width = 730;
$dg->DefaultSortField = "RowID";
$dg->autoExpandColumn = "fullname";
$dg->emptyTextOfHiddenColumns = true;
$dg->editorGrid = true;
$dg->title = "صاحبان امضاء";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$grid = $dg->makeGrid_returnObjects();
?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>
<script type="text/javascript">

OrgSigner.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	PersonID : <?= $PersonID ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

OrgSigner.deleteRender = function(v,p,r)
{
	return "<div align='center' title='حذف ' class='remove' onclick='OrgSignerObject.Deleting();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

OrgSigner.editRender = function(v,p,r)
{
	return "<div align='center' title='ویرایش' class='edit' onclick='OrgSignerObject.Editing();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

function OrgSigner()
{
	this.grid = <?= $grid?>;
	this.grid.render(this.get("div_grid"));
	
	this.MainForm = new Ext.form.Panel({
		renderTo : this.get("mainForm"),
		frame : true,
		hidden : true,
		style : "margin-bottom:20px",
		width : 730,
		defaults : {
			width : 350,
			allowBlank : false
		},
		layout : {
			type : "table",
			columns : 2
		},
		items : [{
			xtype : "combo",
			name : "sex",
			fieldLabel : "جنسیت",
			store : new Ext.data.SimpleStore({
				data : [
					["FEMALE" , "زن" ],["MALE" , "مرد" ]
				],
				fields : ['id','value']
			}),
			displayField : "value",
			valueField : "id"
		},{
			xtype : "textfield",
			name : "fullname",
			fieldLabel : "نام و نام خانوادگی"
		},{
			xtype : "textfield",
			name : "PostDesc",
			fieldLabel : "سمت"
		},{
			xtype : "textfield",
			name : "FatherName",
			fieldLabel : "نام پدر"
		},{
			xtype : "numberfield",
			name : "ShNo",
			hideTrigger : true,
			fieldLabel : "شماره شناسنامه"
		},{
			xtype : "textfield",
			name : "ShPlace",
			fieldLabel : "صادره از"
		},{
			xtype : "shdatefield",
			name : "BirthDate",
			fieldLabel : "تاریخ تولد"
		},{
			xtype : "numberfield",
			name : "NationalID",
			hideTrigger : true,
			fieldLabel : "کد ملی"
		},{
			xtype : "numberfield",
			name : "telephone",
			hideTrigger : true,
			fieldLabel : "تلفن"
		},{
			xtype : "numberfield",
			name : "mobile",
			hideTrigger : true,
			fieldLabel : "تلفن همراه"
		},{
			xtype : "textfield",
			name : "address",
			colspan : 2,
			width : 560,
			fieldLabel : "آدرس"
		},{
			xtype : "numberfield",
			name : "PostalCode",
			hideTrigger : true,
			fieldLabel : "کد پستی"
		},{
			xtype : "textfield",
			name : "email",
			fieldLabel : "پست الکترونیک"
		},{
			xtype : "hidden",
			name : "RowID"
		}],
		buttons : [{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ OrgSignerObject.saveData(); }
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){ this.up('panel').hide(); }
		}]
	});
}

var OrgSignerObject = new OrgSigner();

OrgSigner.prototype.Adding = function()
{
	this.MainForm.show();
	this.MainForm.getForm().reset();
}

OrgSigner.prototype.Editing = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	this.MainForm.show();
	this.MainForm.loadRecord(record);
	this.MainForm.down("[name=BirthDate]").setValue(MiladiToShamsi(record.data.BirthDate));
}

OrgSigner.prototype.saveData = function()
{
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.MainForm.getForm().submit({
		url: this.address_prefix +'persons.data.php?task=SaveSigner',
		method: 'POST',
		params : {
			PersonID : this.PersonID
		},

		success: function(form,action){
			mask.hide();
			OrgSignerObject.MainForm.hide();
			OrgSignerObject.grid.getStore().load();
		},
		failure: function(){
			mask.hide();
		}
	});
}

OrgSigner.prototype.Deleting = function()
{
	Ext.MessageBox.confirm("","آيا مايل به حذف مي باشيد؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = OrgSignerObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		Ext.Ajax.request({
		  	url : me.address_prefix + "persons.data.php",
		  	method : "POST",
		  	params : {
		  		task : "DeleteSigner",
		  		RowID : record.data.RowID
		  	},
		  	success : function(response,o)
		  	{
		  		OrgSignerObject.grid.getStore().load();
		  	}
		});
		
	});

}

</script>
<center>
	<div id="mainForm"></div>
	<div id="div_grid"></div>
</center>