<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.02
//-------------------------
require_once('../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$PackageID = $_REQUEST["PackageID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "dms.data.php?task=GetEvents&PackageID=" .$PackageID,"grid_div");

$dg->addColumn("", "EventID","", true);
$dg->addColumn("", "PackageID","", true);
$dg->addColumn("", "EventTypeDesc","", true);
$dg->addColumn("", "FollowUpFullname","", true);

$col = $dg->addColumn("شرح رویداد", "EventTitle");
$col->editor = ColumnEditor::TextField();
	
$col = $dg->addColumn("تاریخ رویداد", "EventDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 100;

$col = $dg->addColumn("ثبت کننده", "RegFullname");
$col->width = 100;

$col = $dg->addColumn("شماره نامه", "LetterID");
$col->renderer = "PackageEvent.LetterRender";
$col->editor = ColumnEditor::NumberField(true);
$col->width = 70;

$col = $dg->addColumn("پیگیری کننده آینده", "FollowUpPersonID");
$col->editor = "this.PersonCombo";
$col->renderer = "function(v,p,r){return r.data.FollowUpFullname }";
$col->width = 120;

$col = $dg->addColumn("تاریخ پیگیری آینده", "FollowUpDate", GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField(true);
$col->width = 100;

$col = $dg->addColumn("شرح پیگیری آینده", "FollowUpDesc");
$col->editor = ColumnEditor::TextField(true);
$col->width = 200;

if($accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return PackageEventObject.SaveEvent(record);}";

	$dg->addButton("AddBtn", "ایجاد رویداد", "add", "function(){PackageEventObject.AddEvent();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return PackageEvent.DeleteRender(v,p,r);}";
	$col->width = 35;
}
$dg->height = 336;
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

PackageEvent.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	PackageID : <?= $PackageID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function PackageEvent()
{
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: '/framework/person/persons.data.php?task=selectPersons&IsStaff=YES',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		displayField: 'fullname',
		valueField : "PersonID"
	});
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
}

PackageEvent.DeleteRender = function(v,p,r){
	
	if(r.data.EventRefNo != null &&  r.data.EventRefNo != "")
		return "";
	
	if(r.data.EventType == "9" && r.data.ChequeStatus != "1")
		return "";
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='PackageEventObject.DeleteEvent();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

PackageEvent.LetterRender = function(v,p,r){
	
	if(v == null)
		return "";
	return "<a onclick='PackageEventObject.OpenLetter(" + v + ")' href=javascript:void(1) >" + v + "</a>";
}
		
PackageEvent.prototype.SaveEvent = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'dms.data.php',
		method: "POST",
		params: {
			task: "SaveEvents",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				PackageEventObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

PackageEvent.prototype.AddEvent = function(){


	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		EventID: null,
		PackageID : this.PackageID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

PackageEvent.prototype.DeleteEvent = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PackageEventObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'dms.data.php',
			params:{
				task: "DeleteEvents",
				EventID : record.data.EventID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					PackageEventObject.grid.getStore().load();
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

PackageEvent.prototype.OpenLetter = function(LetterID){
	
	framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
	{
		LetterID : LetterID
	});
}

var PackageEventObject = new PackageEvent();

</script>
<center>
	<div id="div_grid"></div>
</center>