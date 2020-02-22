<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 91.03
//-----------------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;
require_once inc_dataReader;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "operation.data.php?task=selectEqualizations", "grid_div");

$dg->addColumn("", "EqualizationID", "", true);

$col = $dg->addColumn("تاریخ عملیات", "RegDate", GridColumn::ColumnType_datetime);
$col->width = 130;

$col = $dg->addColumn("بانک", "BankDesc");

$col = $dg->addColumn("", "", "");
$col->renderer = "UpdateChecks.FileRender";
$col->width = 40;

$dg->height = 400;
$dg->width = 800;
$dg->title = "مغایرت گیری های انجام شده";
$dg->DefaultSortField = "EqualizationID";
$dg->DefaultSortDir = "Desc";
$dg->autoExpandColumn = "BankDesc";
$grid = $dg->makeGrid_returnObjects();

?>
<script>
UpdateChecks.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function UpdateChecks()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("divGrid"));
	
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		bodyStyle : "text-align:right;padding:5px",
		title : "به روز رسانی اطلاعات چک ها با فایل بانک",
		defaults : {
			labelWidth :150
		},
		width : 510,
		items :[{
			xtype : "combo",
			fieldLabel : "بانک",
			store: new Ext.data.Store({
				fields:["BankID","BankDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetBankData',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			displayField : "BankDesc",
			name : "BankID",
			valueField : "BankID"
		},{
			xtype : "filefield",
			name : "attach",
			fieldLabel : "فایل csv",
			anchor : "100%"
		}],
		buttons : [{
			text : "ذخیره و بروز رسانی چک ها",
			iconCls : "refresh",
			disabled : this.AddAccess ? false : true,
			handler : function(){ UpdateChecksObj.Save("true"); }
		},{
			text : "ذخیره فایل",
			iconCls : "save",
			disabled : this.AddAccess ? false : true,
			handler : function(){ UpdateChecksObj.Save("false"); }
		}]
	});
	
	this.resultFS = new Ext.form.FieldSet({
		renderTo : this.get("result"),
		width : 510,
		cls : "blueText",
		style : "line-height:22px;text-align:right"
	});
}

UpdateChecks.FileRender = function(v,p,r){
	
	return "<div align='center' title='مشاهده فایل' class='attach' "+
		"onclick='UpdateChecksObj.ShowFile();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16;float:right'></div>";
}

UpdateChecks.prototype.Save = function(DoEqual){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاری ...'});
	mask.show()
	this.formPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'operation.data.php?task=Equalization_UpdateChecks',
		method : "POST",
		params : {
			DoEqual : DoEqual
		},
		success : function(form,action){

			mask.hide();
			UpdateChecksObj.resultFS.update(action.result.data);
			UpdateChecksObj.grid.getStore().load();
		},
		failure : function(form,action)
		{
			mask.hide();
			alert("عملیات مورد نظر با شکست مواجه شد");
		}
	});
}

UpdateChecks.prototype.ShowFile = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	window.open(this.address_prefix + "operation.data.php?task=showFile&EqualizationID=" + 
		record.data.EqualizationID);
}

UpdateChecksObj = new UpdateChecks();


</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
		<div id="result"></div>
		<div id="divGrid"></div>
	</center>
</form>