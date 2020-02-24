<?php
//---------------------------
// programmer:	Sh.Jafarkhani
// create Date:	95.10
//---------------------------
require_once('../header.inc.php');

require_once inc_dataGrid;
require_once inc_dataReader;
require_once inc_response;

$dgh = new sadaf_datagrid("dg",$js_prefix_address . "management/framework.data.php?task=SelectTodayReminders","div_dg");

$dgh->addColumn("", "EventID","",true);
$dgh->addColumn("", "EventDesc","",true);

$col = $dgh->addColumn("", "ColorID");
$col->renderer = "CalendarReminder.ColorRender";
$col->width = 30;

$col = $dgh->addColumn("عنوان رویداد", "EventTitle");
$col->renderer = "CalendarReminder.titleRender";

$col = $dgh->addColumn("شروع", "StartDate");
$col->width = 80;

$col = $dgh->addColumn("پایان", "EndDate");
$col->width = 80;

$col = $dgh->addColumn("از ساعت", "FromTime");
$col->width = 70;

$col = $dgh->addColumn("تا ساعت", "ToTime");
$col->width = 70;

$col = $dgh->addColumn("", "");
$col->renderer = "function(v,p,r){ return CalendarReminderObj.OperationRender(v,p,r);}";
$col->width = 30;

$dgh->width = 505;
$dgh->DefaultSortField = "StartDate";
$dgh->EnablePaging = false;
$dgh->EnableSearch = false;
$dgh->autoExpandColumn = "EventTitle";
$dgh->DefaultSortDir = "DESC";
$dgh->height = 335;
$dgh->emptyTextOfHiddenColumns = true;
$dgh->EnableSearch = false;
$dgh->pageSize = 15;
$grid = $dgh->makeGrid_returnObjects();

?>
<script>
CalendarReminder.prototype = {
	TabID : document.body,
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function CalendarReminder()
{
	this.grid = <?=$grid?>;
	this.grid.render(this.get("div_dg"));
}

CalendarReminder.prototype.Seen = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(this.grid, {msg:'در حال انجام عملیات...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'management/framework.data.php?task=SeenReminder',
		params:{
			EventID: record.data.EventID
		},
		method: 'POST',

		success: function(response){
			mask.hide();
			CalendarReminderObj.grid.getStore().load();
		},
		failure: function(){}
	});
	
}

CalendarReminder.prototype.OperationRender = function(v,p,r){

	return "<div style='background-repeat:no-repeat;background-position:center;cursor:pointer;height:16;width:20px;' "+
		" onclick=CalendarReminderObj.Seen() class=tick></div>";
		
}

CalendarReminder.ColorRender = function(v,p,r){

	return "<div class=ext-color-"+v+"><div class=ext-cal-picker-icon> </div></div>";
}

CalendarReminder.titleRender = function(v,p,r){

	p.tdAttr = "data-qtip='" + r.data.EventDesc + "'";
	return v;
}

CalendarReminderObj = new CalendarReminder();

</script>
<form id="mainForm">
	<center>
		<div id="div_dg"></div>
	</center>
</form>
