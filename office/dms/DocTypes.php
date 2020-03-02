<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.12
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
$dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?task=selectDocTypeGroups", "grid_div");

$dg->addColumn("", "TypeID", "", true);

$col = $dg->addColumn("کد", "InfoID");
$col->width = 40;

$col = $dg->addColumn("شرح", "InfoDesc", "");
$col->editor = ColumnEditor::TextField();

$dt = PdoDataAccess::runquery("select * from BaseInfo where TYpeID=11");
$col = $dg->addColumn("آیتم اطلاعاتی", "param1", "");
$col->editor = ColumnEditor::ComboBox($dt,"param4","InfoDesc","","", true);
$col->width = 90;

$dt = PdoDataAccess::runquery("select * from BaseInfo where TYpeID=11");
$col = $dg->addColumn("آیتم اطلاعاتی", "param2", "");
$col->editor = ColumnEditor::ComboBox($dt,"param4","InfoDesc","","", true);
$col->width = 90;

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){DocTypeObject.AddDocType(1);}";
}

$col = $dg->addColumn("آیتم ها", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return DocType.listRender(v,p,r,2);}";
$col->width = 50;

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return DocType.DeleteRender(v,p,r,1);}";
	$col->width = 50;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){
	var record = DocTypeObject.grid.getSelectionModel().getLastSelected();
	return DocTypeObject.SaveDocType(record);}";

$dg->title = "لیست گروه مدارک";
$dg->height = 450;
$dg->DefaultSortField = "InfoDesc";
$dg->autoExpandColumn = "InfoDesc";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$grid1 = $dg->makeGrid_returnObjects();

//.............................................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?task=selectDocTypes", "grid_div");

$dg->addColumn("", "TypeID", "", true);

$col = $dg->addColumn("گروه", "param1");
$col->editor = ColumnEditor::NumberField();
$col->width = 40;

$col = $dg->addColumn("کد", "InfoID");
$col->width = 40;

$col = $dg->addColumn("شرح", "InfoDesc", "");
$col->editor = ColumnEditor::TextField();

$col = $dg->addColumn("دسترسی پرتال", "param2", "");
$col->renderer = sadaf_datagrid::checkRender();
$col->editor = ColumnEditor::CheckField("", "1");
$col->align = "center";
$col->width = 50;

$col = $dg->addColumn("آیتم ها", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return DocType.ParamRender(v,p,r,2);}";
$col->width = 50;

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){DocTypeObject.AddDocType(2);}";
}

if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return DocType.DeleteRender(v,p,r,2);}";
	$col->width = 50;
}
$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){
	var record = DocTypeObject.grid2.getSelectionModel().getLastSelected();
	return DocTypeObject.SaveDocType(record);}";

$dg->title = "لیست اطلاعات";
$dg->height = 450;
$dg->width = 400;
$dg->DefaultSortField = "InfoDesc";
$dg->autoExpandColumn = "InfoDesc";
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->EnablePaging = false;
$grid2 = $dg->makeGrid_returnObjects();

?>
<center>
	<table width="98%" style="margin:10px">
		<tr>
			<td style="padding-left: 10px"><div id="div_grid"></div></td>
			<td width="400px"><div id="div_grid2"></div></td>
		</tr>
	</table>
</center>
<script>

DocType.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	GroupID : 0,

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function DocType(){

	this.grid = <?= $grid1 ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		if(e.record.data.ObjectID*1 > 0)
			return false;
		if(!e.record.data.InfoID)
			return DocTypeObject.AddAccess;
		return DocTypeObject.EditAccess;
	});
	this.grid.render(this.get("div_grid"));
	//.........................................................
	
	this.grid2 = <?= $grid2 ?>;
	this.grid2.plugins[0].on("beforeedit", function(editor,e){
		if(e.record.data.ObjectID*1 > 0)
			return false;
		if(!e.record.data.InfoID)
			return DocTypeObject.AddAccess;
		return DocTypeObject.EditAccess;
	});
}

DocType.listRender = function(v,p,r, gridIndex){
	
	return "<div align='center' title='لیست آیتم ها' class='list' "+
		"onclick='DocTypeObject.LoadItems();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

DocType.DeleteRender = function(v,p,r, gridIndex){
	
	return "<div align='center' title='حذف' class='remove' "+
		"onclick='DocTypeObject.DeleteDocType(" + gridIndex + ");' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

DocType.ParamRender = function(v,p,r, gridIndex){
	
	return "<div align='center' title='لیست آیتم ها' class='list' "+
		"onclick='DocTypeObject.LoadParams();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

DocType.prototype.LoadItems = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	this.grid2.getStore().proxy.extraParams = {
		GroupID : record.data.InfoID
	};
	if(this.grid2.rendered)
		this.grid2.getStore().load();
	else
		this.grid2.render(this.get("div_grid2"));

	this.GroupID = record.data.InfoID;
}

DocType.prototype.AddDocType = function(gridIndex){

	if(gridIndex == 1)
	{
		var modelClass = this.grid.getStore().model;
		var record = new modelClass({
			InfoID: 0,
			TypeID : 7,
			param1 : 0,
			DocTypeCode: null
		});

		this.grid.plugins[0].cancelEdit();
		this.grid.getStore().insert(0, record);
		this.grid.plugins[0].startEdit(0, 0);
		return;
	}
	
	var modelClass = this.grid2.getStore().model;
	var record = new modelClass({
		InfoID: 0,
		TypeID : 8,
		param1 : this.GroupID,
		DocTypeCode: null
	});

	this.grid2.plugins[0].cancelEdit();
	this.grid2.getStore().insert(0, record);
	this.grid2.plugins[0].startEdit(0, 0);
}

DocType.prototype.SaveDocType = function(record){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'dms.data.php',
		method: "POST",
		params: {
			task: "SaveDocType",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				DocTypeObject.grid.getStore().load();
				DocTypeObject.grid2.getStore().load();
			}
			else
			{
				if(st.data == "")
					alert("خطا در اجرای عملیات");
				else
					alert(st.data);
			}
		},
		failure: function(){}
	});
}

DocType.prototype.DeleteDocType = function(gridIndex){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = DocTypeObject;
		var record = gridIndex == 1 ? me.grid.getSelectionModel().getLastSelected() : 
			me.grid2.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'dms.data.php',
			params:{
				task: "DeleteDocType",
				TypeID : record.data.TypeID,
				InfoID : record.data.InfoID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				DocTypeObject.grid.getStore().load();
				DocTypeObject.grid2.getStore().load();
			},
			failure: function(){}
		});
	});
}

//-----------------------------------------------------------

DocType.prototype.LoadParams = function(){

	if(!this.ParamWin)
	{
		this.ParamWin = new Ext.window.Window({
			width : 600,
			title : "آیتم های الگو",
			height : 520,
			modal : true,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "DocParams.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.ParamWin);
	}

	this.ParamWin.show();
	this.ParamWin.center();
	
	var record = this.grid2.getSelectionModel().getLastSelected();
	this.ParamWin.loader.load({
		params : {
			ExtTabID : this.ParamWin.getEl().id,
			DocType : record.data.InfoID
		}
	});
}

var DocTypeObject = new DocType();	

</script>