<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.10
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "loan.data.php?task=GetLetterTemplates", "grid_div");

$dg->addColumn("", "TemplateID", "", true);
$dg->addColumn("", "LetterContent", "", true);

$col = $dg->addColumn("عنوان قالب", "TemplateDesc");
$col->width = 200;

$col = $dg->addColumn("عنوان", "LetterSubject", "");

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){LetterTemplateObject.AddLetterTemplate();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("ویرایش", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LetterTemplate.EditRender(v,p,r);}";
	$col->width = 40;
	
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LetterTemplate.DeleteRender(v,p,r);}";
	$col->width = 40;
}

$dg->title = 'قالب های نامه های مربوط به وام';
$dg->height = 500;
$dg->width = 700;
$dg->DefaultSortField = "StatusID";
$dg->autoExpandColumn = "LetterSubject";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
        <br>
        <div id="div_form"></div><br>
		<div id="div_grid"></div>
    </form>
</center>
<script>

LetterTemplate.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LetterTemplate.EditRender = function(v,p,r){
	
	return "<div align='center' title='ویرایش' class='edit' "+
		"onclick='LetterTemplateObject.EditLetterTemplate();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

LetterTemplate.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='LetterTemplateObject.DeleteTemplate();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

function LetterTemplate(){

	this.MainPanel = new Ext.form.Panel({
		renderTo: this.get("div_form"),
		title: "اطلاعات الگو",
		width: 600,
		height: 350,
		hidden : true,
		frame: true,
		autoHeight: true,
		defaults : {width : 580},
		bodyCfg: {style: "background-color:white"},
		items :[{
			xtype : "textfield",
			name : "TemplateDesc",
			fieldLabel : "عنوان قالب"
		},{
			xtype : "textfield",
			name : "LetterSubject",
			fieldLabel : "عنوان نامه"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					["#RequestID#" , "شماره وام" ],
					["#ReqFullname#" , "سرمایه گذار" ],
					["#PartAmount#" , "مبلغ وام" ],
					["#amount_char#" , "مبلغ حروفی وام" ],	
					["#PartDate#" , "تاریخ وام" ],
					["#DefrayDate#" , "تاریخ تسویه" ],
					["#EndDate#" , "تاریخ خاتمه" ],
					["#LoanFullname#" , "مشتری" ],
					["#NationalID#" , "شناسه ملی مشتری / کدملی" ],
					["#address#" , "نشانی مشتری" ],
					["#PhoneNo#" , "تلفن مشتری" ],
					["#mobile#" , "تلفن همراه مشتری" ],
					["#totalRemain#" , "بدهی معوق" ]	
				],
				fields : ['id','value']
			}),
			fieldLabel : "اطلاعات مورد نیاز",
			displayField : "value",
			valueField : "id",
			listeners: {
				select: function (combo, records) {
					this.collapse();
					this.up("form").down("htmleditor").insertAtCursor(records[0].data.id);
					this.setValue();
				}
			}
		},{
			xtype : "htmleditor",
			height: 200,
			name : "LetterContent"
		},{
			xtype : "hidden",
			name : "TemplateID"
		}],
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){LetterTemplateObject.Save();}
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){this.up('panel').hide();}
		}]
	});
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));
}

var LetterTemplateObject = new LetterTemplate();	

LetterTemplate.prototype.Save = function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.MainPanel.getForm().submit({
		url: this.address_prefix +'loan.data.php',
		method: "POST",
		params: { 
			task: "SaveLetterTemplates"
		},
		success: function(response){
			mask.hide();
			LetterTemplateObject.grid.getStore().load();
			LetterTemplateObject.MainPanel.hide();
		},
		failure: function(){}
	});
}

LetterTemplate.prototype.AddLetterTemplate = function(){
	
	this.MainPanel.show();
	this.MainPanel.getForm().reset();
}

LetterTemplate.prototype.EditLetterTemplate = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.MainPanel.show();
	this.MainPanel.loadRecord(record);
}

LetterTemplate.prototype.DeleteTemplate = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LetterTemplateObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'loan.data.php',
			params:{
				task: "DeleteLetterTemplates",
				TemplateID : record.data.TemplateID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					LetterTemplateObject.grid.getStore().load();
				else if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);
				mask.hide();
				
			},
			failure: function(){}
		});
	});
}
</script>