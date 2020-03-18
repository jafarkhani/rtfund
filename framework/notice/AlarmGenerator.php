<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1397.02
//-----------------------------

require_once '../../header.inc.php';
require_once 'config.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "alarm.data.php?task=SelectAlarms", "grid_div");

$dg->addColumn("", "AlarmID", "", true);
$dg->addColumn("", "ObjectID", "", true);
$dg->addColumn("", "context", "", true);

$col = $dg->addColumn("عنوان", "AlarmTitle");

$col = $dg->addColumn("نوع ارسال", "SendType", "");
$col->width = 80;

$col = $dg->addColumn('تعداد روز', 'days');
$col->width =70;
$col->align = "center";

$col = $dg->addColumn('محاسبه', 'compute');
$col->renderer = "function(v){return v == 'BEFORE' ? 'قبل از تاریخ سررسید' : 'بعد از تاریخ سررسید'}";
$col->width = 120;
$col->align = "center";

if($accessObj->AddFlag)
	$dg->addButton("", "ایجاد", "add", "function(){NTC_AlarmsObject.AddNew();}");

if($accessObj->EditFlag)
{
	$col = $dg->addColumn("ویرایش", "", "string");
	$col->renderer = "NTC_Alarms.editRender";
	$col->width = 40;
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "", "string");
	$col->renderer = "NTC_Alarms.deleteRender";
	$col->width = 40;
}
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->height = 500;
$dg->pageSize = 15;
$dg->width = 800;
$dg->title = "آلارم های تعریف شده";
$dg->DefaultSortField = "AlarmID";
$dg->autoExpandColumn = "AlarmTitle";
$grid = $dg->makeGrid_returnObjects();
?>
<style>
	.ObjectItemIfo {width : 100%}
	.ObjectItemIfo td,.ObjectItemIfo th{text-align: center; border : 1px solid black; padding : 4px}
	.ObjectItemIfo th{background-color: #CEE4FF}
</style>
<script>

NTC_Alarms.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function NTC_Alarms(){
		
	this.MainPanel = new Ext.form.Panel({
		width : 750,
		hidden : true,
		layout : {
			type : "table",
			columns : 2
		},		
		applyTo : this.get("operationInfo"),
		defaults : {
			width : 370,
			labelWidth : 60
		},
		frame : true,
		items : [{
			xtype : "textfield",
			name : "AlarmTitle",
			fieldLabel : "عنوان"
		},{
			xtype : "container",
			layout : "hbox",
			items :	[{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					fields : ['id','title'],
					data : [
						['SMS', 'SMS'],
						['EMAIL', 'EMAIL'],
						['LETTER', 'LETTER'],
						['NONE', 'عدم ارسال']
					]				
				}),
				displayField : "title",
				valueField : "id",
				labelWidth : 60,
				fieldLabel : "نوع ارسال",
				name : "SendType"
			},{
				xtype : "checkbox",
				width : 100,
				boxLabel : "ارسال گروهی نامه",
				boxValue : "YES",
				disabled : true,
				name : "GroupLetter"
			},{
				xtype : "button",
				iconCls : "help",
				tooltip : "در ارسال گروهی یک نامه ایجاد شده"+
						" و افراد فایل پیوست به عنوان ذینفعان نامه تعریف می شوند."+
						" نامه به صورت پیش نویس در باکس شما خواهد بود"+
						 "<br> در غیر اینصورت"+
						" برای هر یک از افراد فایل پیوست یک نامه جدا ایجاد و ارسال می گردد.",
				width : 50
			}]
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'alarm.data.php?task=SelectObjects',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['ObjectID','ObjTitle','itemsInfo'],
				autoLoad : true
			}),
			queryMode : 'local',
			fieldLabel : "آیتم مربوطه",
			name : "ObjectID",
			valueField : "ObjectID",
			displayField : "ObjTitle",
			listeners : {
				select : function(combo,records){
					NTC_AlarmsObject.MainPanel.down("[itemId=ObjectItems]").update(records[0].data.itemsInfo);
				}
			}
		},{
			xtype : "container",
			layout : "hbox",
			items : [{
				xtype : "numberfield",
				name : "days",
				fieldLabel : "زمان",
				hideTrigger : true,
				labelWidth : 60,
				width : 150
			},{
				xtype : "displayfield",
				value : "روز",
				style : "margin : 0 4px 2px 4px"
			},{
				xtype : "combo",
				width : 100,
				store : new Ext.data.SimpleStore({
					fields : ['id','title'],
					data : [
						['BEFORE', 'قبل'],
						['AFTER', 'بعد']
					]				
				}),
				displayField : "title",
				valueField : "id",
				name : "compute"
			},{
				xtype : "displayfield",
				value : " از تاریخ سررسید",
				style : "margin : 0 4px 2px 4px"
			}]
		},{
			xtype : "fieldset",
			colspan : 2,
			width : 730,
			itemId : "ObjectItems",
			title : "اطلاعات آیتم انتخابی"
		},{
			xtype : "htmleditor",
			colspan : 2,
			width : 730,
			name : "context"
		},{
			xtype : "container",
			colspan : 2,
			width : 730,
			html : "برای جایگزینی اطلاعات در متن از فرمت [col(no)] استفاده کنید. به عنوان مثال : [col1]"
			
		},{
			xtype : "hidden",
			name : "AlarmID"
		}],
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ NTC_AlarmsObject.SaveAlarm(); }
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){ this.up('panel').hide(); }
		}]
	});
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("DivGrid"));
}

NTC_Alarms.editRender = function(value, p, record){
	return "<div  title='ویرایش' class='edit' onclick='NTC_AlarmsObject.EditAlarm();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

NTC_Alarms.deleteRender = function(value, p, record){
	return "<div  title='حذف' class='remove' onclick='NTC_AlarmsObject.DeleteAlarm();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

NTC_AlarmsObject = new NTC_Alarms();

NTC_Alarms.prototype.AddNew = function(){
	
	this.MainPanel.show();
	this.MainPanel.getForm().reset();
	NTC_AlarmsObject.MainPanel.down("[itemId=ObjectItems]").update();
}

NTC_Alarms.prototype.EditAlarm = function(){
	
	this.MainPanel.show();
	this.MainPanel.getForm().reset();
	
	record = this.grid.getSelectionModel().getLastSelected();
	this.MainPanel.loadRecord(record);
	
	obj = NTC_AlarmsObject.MainPanel.down("[name=ObjectID]");
	NTC_AlarmsObject.MainPanel.down("[itemId=ObjectItems]").update(
			obj.getStore().findRecord("ObjectID",obj.getValue()).data.itemsInfo);
}

NTC_Alarms.prototype.SaveAlarm = function(){
	
	if(!this.MainPanel.getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.MainPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix +'alarm.data.php',
		method: "POST",
		
		params: {
			task: "SaveAlarm"
		},
		success: function(form,action){
			mask.hide();
			NTC_AlarmsObject.grid.getStore().load();
			NTC_AlarmsObject.MainPanel.hide();
		},
		failure: function(form,action){
			
			Ext.MessageBox.alert("Error", action.result.data);
			mask.hide();
		}
	});
}

NTC_Alarms.prototype.DeleteAlarm = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = NTC_AlarmsObject;
		record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "alarm.data.php",
			params : {
				task : "DeleteAlarm",
				AlarmID : record.data.AlarmID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
					NTC_AlarmsObject.grid.getStore().load();
				else
					Ext.MessageBox.alert("Error",result.data);
			}
		});
	});
}

</script>
<center>
	<br> کلیه آلارم های تعریف شده ساعت 8 صبح هر روز به صورت اتومات اجرا می گردند.
	<div><div id="operationInfo"></div></div>
	<div id="DivGrid"></div>	
</center>