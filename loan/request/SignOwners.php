<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.07
//-------------------------
require_once('../header.inc.php');
require_once 'request.class.php';
require_once inc_dataGrid;
 
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$GuarantorID = (int)$_REQUEST["GuarantorID"];
$editable = true;

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetSignOwners&FormType=2&GuarantorID=" .$GuarantorID,"grid_div");
$dg->addColumn("","RowID","string", true);
$dg->addColumn("","PersonID","string", true);
$dg->addColumn("","address","string", true);
$dg->addColumn("","PostalCode","string", true);
/*$dg->addColumn("","ShNo","string", true);*/
$dg->addColumn("","ShPlace","string", true);
$dg->addColumn("","email","string", true);
$dg->addColumn("","sex","string", true);
/*$dg->addColumn("","NationalID","string", true);*/
$dg->addColumn("","telephone","string", true);
$dg->addColumn("","mobile","string", true);

$col = $dg->addColumn("نام و نام خانوادگی ","fullname","string");
$col->editor = ColumnEditor::TextField();
$col->width = 130;
$col = $dg->addColumn("نام پدر","FatherName","string");
$col->editor = ColumnEditor::TextField();
$col->width = 60;

$col = $dg->addColumn("شماره شناسنامه","ShNo","string");
$col->editor = ColumnEditor::NumberField();
$col->width = 100;

$col = $dg->addColumn("کد ملی","NationalID","string");
$col->editor = ColumnEditor::NumberField();
$col->width = 110;

$col = $dg->addColumn("تاریخ تولد","BirthDate",  GridColumn::ColumnType_date);
$col->editor = ColumnEditor::SHDateField();
$col->width = 80;

$col = $dg->addColumn("سمت","PostDesc","string");
$col->editor = ColumnEditor::TextField();
$col->width = 70;



$accessObj->AddFlag = true;
$accessObj->RemoveFlag = true;

if($accessObj->AddFlag)
{
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){return MonitorStepObject.SaveSignOwner(record);}";
	$dg->addButton("AddBtn", "ایجاد صاحب امضا  ", "add", "function(){MonitorStepObject.AddSignOwner();}");
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return MonitorStep.DeleteRender(v,p,r);}";
	$col->width = 30;
}
$dg->height = 336;
$dg->width = 585;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "RowID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "RowID";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

MonitorStep.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	GuarantorID : <?= $GuarantorID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function MonitorStep()
{
	this.grid = <?= $grid ?>;
	if(this.AddAccess)
		this.grid.plugins[0].on("beforeedit", function(editor,e){
		    /*console.log(e.record.data);*/

			
				return true;
		});
	
	this.grid.render(this.get("div_grid"));	
}

MonitorStep.DeleteRender = function(v,p,r){
	
	/*if(r.data.DocID != null &&  r.data.DocID != "")
		return "";
	
	if(r.data.IsPartDiff == "YES")
		return "";*/

	return "<div align='center' title='حذف' class='remove' "+
		"onclick='MonitorStepObject.DeleteMonitorStep();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}


MonitorStep.prototype.SaveSignOwner = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	Ext.Ajax.request({
		url: this.address_prefix +'../../framework/person/persons.data.php',
		method: "POST",
		params : {
			task: "SaveSigner",
			FormType : "2",
			PersonID : this.GuarantorID,
			record : Ext.encode(record.data),
			fullname: Ext.encode(record.data.fullname),
			FatherName : Ext.encode(record.data.FatherName),
			ShNo : Ext.encode(record.data.ShNo),
			BirthDate : Ext.encode(record.data.BirthDate),
			NationalID : Ext.encode(record.data.NationalID),
			PostDesc : Ext.encode(record.data.PostDesc),
			RowID: Ext.encode(record.data.RowID)
			
		},
		
		success: function(response){
		    console.log('success');
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
			    console.log('success.success');
				MonitorStepObject.grid.getStore().load();
			}
			else
			{
			    console.log('success.failure');
				if(st.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",st.data);
			}
		},
		failure: function(){
		    console.log('failure');
		}
	});
}

MonitorStep.prototype.AddSignOwner = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RowID: null,
		PersonID : this.PersonID
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

MonitorStep.prototype.DeleteMonitorStep = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = MonitorStepObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		/*console.log(record.data);*/
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + '../../framework/person/persons.data.php',
			method: 'POST',
			params:{
				task: "DeleteSigner",
				RowID : record.data.RowID
			},

			success: function(response,option){
				result = Ext.decode(response.responseText);
				if(result.success)
					MonitorStepObject.grid.getStore().load();
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

var MonitorStepObject = new MonitorStep();

</script>
<center>
	<div id="div_grid"></div>
</center>