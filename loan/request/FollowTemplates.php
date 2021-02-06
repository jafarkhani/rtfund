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

$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=GetFollowTemplates", "grid_div");

$dg->addColumn("", "TemplateID", "", true);
$dg->addColumn("", "StatusID", "", true);
$dg->addColumn("", "LetterContent", "", true);

$col = $dg->addColumn("مرحله پیگیری", "StatusDesc");
$col->width = 200;

$col = $dg->addColumn("عنوان", "LetterSubject", "");

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){FollowTemplateObject.AddFollowTemplate();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("ویرایش", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return FollowTemplate.EditRender(v,p,r);}";
	$col->width = 40;
	
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return FollowTemplate.DeleteRender(v,p,r);}";
	$col->width = 40;
}

$dg->title = 'قالب های نامه های پیگیری مطالبات';
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

FollowTemplate.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

FollowTemplate.EditRender = function(v,p,r){
	
	return "<div align='center' title='ویرایش' class='edit' "+
		"onclick='FollowTemplateObject.EditFollowTemplate();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

FollowTemplate.DeleteRender = function(v,p,r){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='FollowTemplateObject.DeleteTemplate();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

function FollowTemplate(){

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
			xtype : "combo",
			store : new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'request.data.php?task=GetFollowStatuses',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ["InfoID", "InfoDesc"],
				autoLoad : true
			}), 
			queryMode: 'local',
			name : "StatusID",
			displayField: 'InfoDesc',
			valueField : "InfoID",
			fieldLabel : "مرحله پیگیری"
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
			handler : function(){FollowTemplateObject.Save();}
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){this.up('panel').hide();}
		}]
	});
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));
}

var FollowTemplateObject = new FollowTemplate();	

FollowTemplate.prototype.Save = function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.MainPanel.getForm().submit({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: { 
			task: "SaveFollowTemplates"
		},
		success: function(response){
			mask.hide();
			FollowTemplateObject.grid.getStore().load();
			FollowTemplateObject.MainPanel.hide();
		},
		failure: function(){}
	});
}

FollowTemplate.prototype.AddFollowTemplate = function(){
	
	this.MainPanel.show();
	this.MainPanel.getForm().reset();
}

FollowTemplate.prototype.EditFollowTemplate = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.MainPanel.show();
	this.MainPanel.loadRecord(record);
}

FollowTemplate.prototype.DeleteTemplate = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = FollowTemplateObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DeleteFollowTemplates",
				TemplateID : record.data.TemplateID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					FollowTemplateObject.grid.getStore().load();
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