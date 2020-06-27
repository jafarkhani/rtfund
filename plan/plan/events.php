<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.12
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$PlanID = $_REQUEST["PlanID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "plan.data.php?task=GetPlanEvents&PlanID=" .$PlanID,"grid_div");

$dg->addColumn("", "EventID","", true);
$dg->addColumn("", "PlanID","", true);
$dg->addColumn("", "EventTypeDesc","", true);

//new added
$col = $dg->addColumn("ثبت کننده رویداد", "fullname");
$col->editor = ColumnEditor::TextField();
//end new added

$col = $dg->addColumn("شرح رویداد", "EventTitle");
$col->editor = ColumnEditor::TextField();
	
$col = $dg->addColumn("تاریخ رویداد", "EventDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 100;

if($accessObj->AddFlag)
{
	$dg->addButton("AddBtn", "ایجاد رویداد", "add", "function(){PlanEventObject.AddEvent();}");
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return PlanEventObject.SaveEvent(record);}";
}

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return PlanEvent.DeleteRender(v,p,r);}";
	$col->width = 35;
}
$dg->height = 336;
$dg->width = 585;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "EventDate";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "EventTitle";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

PlanEvent.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	PlanID : <?= $PlanID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function PlanEvent()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
}

PlanEvent.DeleteRender = function(v,p,r){
	
	if(r.data.EventRefNo != null &&  r.data.EventRefNo != "")
		return "";
	
	if(r.data.EventType == "9" && r.data.ChequeStatus != "1")
		return "";
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='PlanEventObject.DeleteEvent();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

var PlanEventObject = new PlanEvent();
	
PlanEvent.prototype.SaveEvent = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'plan.data.php',
		method: "POST",
		params: {
			task: "SavePlanEvents",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				PlanEventObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

PlanEvent.prototype.AddEvent = function(){


	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		EventID: null,
		PlanID : this.PlanID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

PlanEvent.prototype.DeleteEvent = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PlanEventObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'plan.data.php',
			params:{
				task: "DeletePlanEvents",
				EventID : record.data.EventID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					PlanEventObject.grid.getStore().load();
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
<center>
	<div id="div_grid"></div>
</center>