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

$dgh = new sadaf_datagrid("dgh1",$js_prefix_address."cheques.data.php?task=SelectChequeStatuses","div_dgu");

$dgh->addColumn("","RowID",'string',true);

$temp = PdoDataAccess::runquery("select * from BaseInfo where
	InfoID<>".INCOMECHEQUE_EDIT." AND TypeID=4");

$col = $dgh->addColumn("وضعیت مبدا", "SrcID");
$col->editor = ColumnEditor::ComboBox($temp, "InfoID", "InfoDesc");

$col=$dgh->addColumn("وضعیت مقصد", "DstID");
$col->editor = ColumnEditor::ComboBox($temp, "InfoID", "InfoDesc");
$col->width = 200; 


if($accessObj->RemoveFlag)
{
	$col = $dgh->addColumn("حذف", "", "string");
	$col->renderer = "ChequeStatuses.deleteRender";
	$col->width = 10;
}
if($accessObj->AddFlag)
{
	$dgh->addButton = true;
	$dgh->addHandler = "function(v,p,r){ return ChequeStatusesObject.Add(v,p,r);}";

	$dgh->enableRowEdit = true ;
	$dgh->rowEditOkHandler = "function(v,p,r){ return ChequeStatuses.Save(v,p,r);}";
}
$dgh->title ="تبدیل وضعیت های چک ها";
$dgh->emptyTextOfHiddenColumns=true;
$dgh->width = 600;
$dgh->EnableSearch = false;
$dgh->DefaultSortField = "UserName";
$dgh->DefaultSortDir = "ASC";
$dgh->height = 400;
$dgh->EnablePaging = false;
$dgh->pageSize=12;
$gridUsers = $dgh->makeGrid_returnObjects();
?>
<script>

ChequeStatuses.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ChequeStatuses()
{	
	this.grid = <?= $gridUsers?>;                
	this.grid.render(this.get("div_dgu"));
	
	if(this.grid.plugins.length > 0)
		this.grid.plugins[0].on("beforeedit", function(editor,e){

			if(e.record.data.RowID > 0)
				return false;
		});
}

ChequeStatuses.Save = function(store,record,op)
{    
	mask = new Ext.LoadMask(Ext.getCmp(ChequeStatusesObject.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();    
	Ext.Ajax.request({
		url:  ChequeStatusesObject.address_prefix + 'cheques.data.php?task=SaveChequeStatus',
		params:{
			SrcID : record.data.SrcID,
			DstID : record.data.DstID
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			ChequeStatusesObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

ChequeStatuses.prototype.Add = function()
{  
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RowID : null,
		SrcID:null,
		DstID : null
	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

ChequeStatuses.deleteRender = function(value, p, record)
{
	return  "<div  title='حذف اطلاعات' class='remove' onclick='ChequeStatusesObject.Delete();' " +
		"style='float:left;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:50%;height:16'></div>";
}

ChequeStatuses.prototype.Delete = function()
{    
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ChequeStatusesObject;
		var record = me.grid.getSelectionModel().getLastSelected();

		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخيره سازي...'});
		mask.show();
		Ext.Ajax.request({
			url: me.address_prefix + 'cheques.data.php?task=DeleteChequeStatuses',
			params:{
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				ChequeStatusesObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

var ChequeStatusesObject = new ChequeStatuses();

</script>

<center>
<br>
<div id="form_Users">
	<div id="div_dgu"></div>
	<br><br>
	<div align="center" id="InfoPNL" ></div>
</div>
</center>


