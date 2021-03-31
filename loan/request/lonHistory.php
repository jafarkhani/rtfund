<?php
//-------------------------
// programmer:	Mokhtari
// Create Date:	99.06
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$RequestID = $_REQUEST["RequestID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetLonHistory&RequestID=" .$RequestID,"grid_div");

$dg->addColumn("", "HistoryID","", true);
$dg->addColumn("", "RequestID","", true);


$col = $dg->addColumn("عنوان سابقه", "HistoryTitle");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("شرح سابقه", "HistoryDesc");
$col->editor = ColumnEditor::TextArea();
$col->width = 600;

$col = $dg->addColumn("ثبت کننده", "RegFullname");
$col->width = 100;

$col = $dg->addColumn("تاریخ ثبت", "HistoryDate", GridColumn::ColumnType_date);
$col->width = 100;

if($accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return LoanHistoryObject.SaveHistory(record);}";

	$dg->addButton("AddBtn", "ایجاد رویداد", "add", "function(){LoanHistoryObject.AddHistory();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return LoanHistory.DeleteRender(v,p,r);}";
	$col->width = 35;
}
$dg->height = 336;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "HistoryDate";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "HistoryTitle";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

LoanHistory.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	RequestID : <?= $RequestID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanHistory()
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

LoanHistory.DeleteRender = function(v,p,r){
	
	if(r.data.HistoryRefNo != null &&  r.data.HistoryRefNo != "")
		return "";
	
	if(r.data.HistoryType == "9" && r.data.ChequeStatus != "1")
		return "";
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='LoanHistoryObject.DeleteHistory();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}
		
LoanHistory.prototype.SaveHistory = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params: {
			task: "SaveLonHistory",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{
                LoanHistoryObject.grid.getStore().load();
			}
			else
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
			}
		},
		failure: function(){}
	});
}

LoanHistory.prototype.AddHistory = function(){


	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
        HistoryID: null,
		RequestID : this.RequestID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

LoanHistory.prototype.DeleteHistory = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LoanHistoryObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DeleteLonHistory",
                HistoryID : record.data.HistoryID
			},
			method: 'POST',

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
                    LoanHistoryObject.grid.getStore().load();
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


var LoanHistoryObject = new LoanHistory();

</script>
<center>
	<div id="div_grid"></div>
</center>