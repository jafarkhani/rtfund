<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-----------------------
require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dgh1",$js_prefix_address."framework.data.php?task=SelectNews","div_dg");

$dg->addColumn("","NewsID",'string',true);

$col = $dg->addColumn("عنوان", "NewsTitle");

$col=$dg->addColumn("تاریخ شروع", "StartDate", GridColumn::ColumnType_date);
$col->width = 80;

$col=$dg->addColumn("تاریخ پایان", "EndDate", GridColumn::ColumnType_date);
$col->width = 80;

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

$dg->addPlugin("this.Details");

$col=$dg->addColumn("ویرایش", "context");
$col->renderer="FRW_news.ContextRender";
$col->width = 40;

if($accessObj->AddFlag)
{
	$dg->addButton("", "ایجاد اعلان جدید", "add", "function(){FRW_newsObject.Add();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "", "string");
	$col->renderer = "FRW_news.deleteRender";
	$col->width = 40;
}
$dg->emptyTextOfHiddenColumns=true;
$dg->DefaultSortField = "NewsTitle";
$dg->DefaultSortDir = "ASC";
$dg->height = 400;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$dg->pageSize=12;
$grid = $dg->makeGrid_returnObjects();
?>
<script>

FRW_news.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function FRW_news()
{
	this.Details = {
		ptype: 'rowexpander',
		rowBodyTpl : [
			'<hr>','{context}'
		]
	};
	
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("div_form"),
		frame : true,
		hidden : true,
		title : "اطلاعات اعلان",
		width : 700,	
		layout : {
			type : "table",
			columns : 2
		},		
		height : 400,
		items : [{
			xtype : "textfield",
			fieldLabel : "عنوان اعلان",
			name : "NewsTitle",
			colspan : 2
		},{
			xtype : "shdatefield",
			name : "StartDate",
			fieldLabel : "تاریخ شروع"
		},{
			xtype : "shdatefield",
			name : "EndDate",
			fieldLabel : "تاریخ پایان"
		},{
			xtype : "fieldset",
			colspan : 2,
			title : " ذینفع",
			layout : "hbox",
			defaults : {style : "margin-right : 10px"},
			items :[{
				xtype : "checkbox",
				boxLabel: 'همکاران صندوق',
				name: 'IsStaff',
				inputValue: 'YES'
			},{
				xtype : "checkbox",
				boxLabel: 'مشتری',
				name: 'IsCustomer',
				inputValue: 'YES'
			},{
				xtype : "checkbox",
				boxLabel: 'سهامدار',
				name: 'IsShareholder',
				inputValue: 'YES'
			},{
				xtype : "checkbox",
				boxLabel: 'سرمایه گذار',
				name: 'IsAgent',
				inputValue: 'YES'
			},{
				xtype : "checkbox",
				boxLabel: 'حامی',
				name: 'IsSupporter',
				inputValue: 'YES'
			},{
				xtype : "checkbox",
				boxLabel: 'کارشناس',
				name: 'IsExpert',
				inputValue: 'YES'
			}]
		},{
			xtype : "container",
			colspan : 2,
			html : "<div id='FrameworkNewsEditor'></div>"
		},{
			xtype : "hidden",
			name : "NewsID"
		}],
		buttons :[{
			text : "ذخیره",
			disabled : this.AddAccess ? false : true,
			iconCls  : "save",
			handler : function(){
				me = FRW_newsObject;
				mask = new Ext.LoadMask(me.formPanel, {msg:'در حال ذخيره سازي...'});
				mask.show();

				me.formPanel.getForm().submit({
					url : me.address_prefix + "framework.data.php?task=SaveNews",
					method : "post",
					params : {
						context : CKEDITOR.instances.FrameworkNewsEditor.getData()
					},
					
					success : function(){
						me = FRW_newsObject;
						me.grid.getStore().load();
						me.formPanel.getForm().reset();
						me.formPanel.hide();
						mask.hide();
					}
				});
			}
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){
				me = FRW_newsObject;
				me.formPanel.hide();
			}
		}]
	});
	
	if ( CKEDITOR.env.ie && CKEDITOR.env.version < 9 )
		CKEDITOR.tools.enableHtml5Elements( document );

	CKEDITOR.config.width = 'auto';
	CKEDITOR.config.height = 100;
	CKEDITOR.config.autoGrow_minHeight = 100;
	CKEDITOR.replace('FrameworkNewsEditor');	
	CKEDITOR.add;
		
	this.grid = <?= $grid ?>;                
	this.grid.render(this.get("div_dg"));
}

FRW_news.deleteRender = function(value, p, record)
{
	return "<div  title='حذف اطلاعات' class='remove' onclick='FRW_newsObject.Delete();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

FRW_news.ContextRender = function(value, p, record)
{
	return "<div  title='ویرایش' class='edit' onclick='FRW_newsObject.ShowForm();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

var FRW_newsObject = new FRW_news();

FRW_news.prototype.Add = function(){
	
	this.formPanel.getForm().reset();
	CKEDITOR.instances.FrameworkNewsEditor.setData();
	this.formPanel.show();
}

FRW_news.prototype.ShowForm = function(){
	
	record = this.grid.getSelectionModel().getLastSelected();
	this.formPanel.loadRecord(record);
	this.formPanel.down("[name=StartDate]").setValue(MiladiToShamsi(record.data.StartDate));
	this.formPanel.down("[name=EndDate]").setValue(MiladiToShamsi(record.data.EndDate));

	CKEDITOR.instances.FrameworkNewsEditor.on('instanceReady', function( ev ) {
		ev.editor.setData(record.data.context);
	});			
	CKEDITOR.instances.FrameworkNewsEditor.setData(record.data.context);
	this.formPanel.show();

}

FRW_news.prototype.Delete = function()
{    
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = FRW_newsObject;
		var record = me.grid.getSelectionModel().getLastSelected();

		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();
		
		Ext.Ajax.request({
			url: me.address_prefix + 'framework.data.php?task=DeleteNew',
			params:{
				NewsID : record.data.NewsID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				FRW_newsObject.grid.getStore().load();
			},
			failure: function(){}
		});		
	});
}

</script>

<center>
	<div id="div_form"></div>
	<br>
	<div style="margin:10px;width:98%" id="div_dg"></div>
</center>


