<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.12
//-------------------------
require_once('../../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "WarrentyPrintSetting.data.php?task=SelectWarrentyPrintSetting", "grid_div");

$dg->addColumn("", "TypeID", "", true);
$dg->addColumn("", "IsActive", "", true);

$col = $dg->addColumn("کد", "InfoID");
$col->width = 100;

$col = $dg->addColumn("شرح", "InfoDesc", "");
$col->editor = ColumnEditor::TextField();

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){WarrentyPrintSettingObject.AddWarrentyPrintSetting();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("غیر فعال", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return WarrentyPrintSetting.DeleteRender(v,p,r);}";
	$col->width = 70;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return WarrentyPrintSettingObject.SaveWarrentyPrintSetting();}";

$dg->title = "لیست اطلاعات";
$dg->height = 500;
$dg->width = 500;
$dg->DefaultSortField = "InfoDesc";
$dg->autoExpandColumn = "InfoDesc";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
        <br>
        <div id="div_selectGroup"></div>
        <br>
        <div id="div_form"></div>
    </form>
</center>
<script>

WarrentyPrintSetting.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function WarrentyPrintSetting(){

	this.groupPnl = new Ext.form.Panel({
		renderTo: this.get("div_selectGroup"),
		title: "انتخاب نوع درخواست",
		width: 400,
		frame: true,
		bodyCfg: {style: "background-color:white"},
		items : [{
			xtype : "combo",
			store : new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../request.data.php?task=GetWarrentyTypes',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ["InfoID", "InfoDesc"],
				autoLoad : true
			}), 
			queryMode: 'local',
			name : "TypeID",
			displayField: 'InfoDesc',
			valueField : "InfoID",
			fieldLabel : "نوع درخواست",
			listeners :{
				select : function(el, records){
					me = WarrentyPrintSettingObject;
					me.SettingPanel.show();
					mask = new Ext.LoadMask(me.SettingPanel, {msg: 'در حال ذخیره سازی ...'});
					mask.show();
					Ext.Ajax.request({
						url : me.address_prefix + "../request.data.php?task=GetPrintContent",
						method: "post",	
						params : {
							TypeID : records[0].data.InfoID
						},
						
						success : function(response){
							CKEDITOR.instances.WarrentySettingEditor.setData(response.responseText);
							mask.hide();
						}
					});
				}
			}
		}]
	});	
	
	this.SettingPanel = new Ext.form.Panel({
		renderTo: this.get("div_form"),
		title: "انتخاب نوع درخواست",
		width: 700,
		hidden : true,
		collapsible : true,
		collapsed : false,
		frame: true,
		bodyCfg: {style: "background-color:white"},
		items :[{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					["#RequestID#" , "شماره ضمانت نامه" ],
					["#TypeDesc#" , "نوع درخواست" ],
					["#SubjectNO#" , "شماره قرارداد" ],
					["#SubjectDesc#" , "موضوع قرارداد" ],
					["#fullname#" , "مشتری" ],
					["#NationalID#" , "شناسه ملی مشتری / کدملی" ],
					["#address#" , "نشانی مشتری" ],
					["#PhoneNo#" , "تلفن مشتری" ],
					["#mobile#" , "تلفن همراه مشتری" ],
                    ["#PostalCode#" , "کدپستی مشتری" ],/*new added*/
					["#organization#" , "سازمان مربوطه" ],
					["#LetterNo#" , "شماره نامه سازمان مربوطه" ],
					["#LetterDate#" , "تاریخ نامه سازمان مربوطه" ],
					["#amount#" , "مبلغ ضمانت نامه" ],
					["#amount_char#" , "مبلغ حروفی ضمانت نامه" ],
					["#StartDate#" , "تاریخ شروع ضمانت نامه" ],
					["#StartDate_char#" , "تاریخ شروع حروفی" ], 
					["#EndDate#" , "تاریخ پایان ضمانت نامه" ],
					["#EndDate_char#" , "تاریخ پایان حروفی" ],
					["#duration_month#" , "مدت ضمانت نامه به ماه" ],
					["#wage#" , "کارمزد ضمانت نامه" ]					
				],
				fields : ['id','value']
			}),
			fieldLabel : "اطلاعات مورد نیاز",
			displayField : "value",
			valueField : "id",
			width : 500,
			listeners: {
				select: function (combo, records) {
					this.collapse();
					CKEDITOR.instances.WarrentySettingEditor.insertText(records[0].data.id);
					this.setValue();
				}
			}
		},{
			xtype : "container",
			html : "<div id=WarrentySettingEditor></div>"
		}],
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){WarrentyPrintSettingObject.Save();}
		}]
	});
	
	if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
		CKEDITOR.tools.enableHtml5Elements( document );

	CKEDITOR.config.width = 'auto';
	CKEDITOR.config.height = 300;
	CKEDITOR.config.autoGrow_minHeight = 350;
	CKEDITOR.replace('WarrentySettingEditor');
	CKEDITOR.add;
}

var WarrentyPrintSettingObject = new WarrentyPrintSetting();	

WarrentyPrintSetting.prototype.Save = function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'../request.data.php',
		method: "POST",
		params: { 
			task: "SavePrintSetting",
			context : CKEDITOR.instances.WarrentySettingEditor.getData(), 
			TypeID : this.groupPnl.down("[name=TypeID]").getValue()
		},
		success: function(response){
			mask.hide();
		},
		failure: function(){}
	});
}



</script>