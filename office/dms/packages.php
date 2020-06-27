<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.02
//-------------------------
require_once('../../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?task=selectPackages", "grid_div");

$dg->addColumn("", "PackageID", "", true);
$dg->addColumn("", "BranchID", "", true);
$dg->addColumn("", "fullname", "", true);

$col = $dg->addColumn("نام و نام خانوادگی", "PersonID");
$col->renderer = "function(v,p,r){return r.data.fullname;}";
$col->editor = "this.PersonCombo";

$col = $dg->addColumn("<span style=font-size:9px>"."شماره پرونده"."</span>", "PackNo", "");
$col->editor = ColumnEditor::NumberField(true);
$col->width = 90;
$col->align = "center";

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){PackageObject.AddPackage();}";
}

$dg->enableRowEdit = true;
$dg->rowEditOkHandler = "function(){return PackageObject.SavePackage();}";

$col = $dg->addColumn("حذف", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return Package.DeleteRender(v,p,r);}";
$col->width = 35;

$col = $dg->addColumn("", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return Package.OperationRender(v,p,r);}";
$col->width = 30;

$dg->title = "لیست پرونده ها";
$dg->height = 400;
$dg->width = 380;
$dg->DefaultSortField = "PackNo";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "PersonID";
$dg->emptyTextOfHiddenColumns = true;
$grid = $dg->makeGrid_returnObjects();

//----------------------------------------------------------------

$dg = new sadaf_datagrid("dg", $js_prefix_address . "dms.data.php?task=selectPackageItems", "grid2_div");

$dg->addColumn("", "PackageID", "", true);
$dg->addColumn("", "RowID", "", true);
$dg->addColumn("", "ObjectType", "", true);
$dg->addColumn("", "ObjectDesc", "", true);
$dg->addColumn("", "ObjectID", "", true);
$dg->addColumn("", "param1", "", true);
$dg->addColumn("", "param2", "", true);
$dg->addColumn("", "param3", "", true);
$dg->addColumn("", "fullname", "", true);

$dg->addColumn("", "DocumentID", "", true);
$dg->addColumn("", "IsConfirm", "", true);

$title = $accessObj->AddFlag ? 
	'<span style="float:right;width:16px;height: 16px;margin:2px;cursor:pointer" class=add '.
	'onclick=PackageObject.AddPackageItem()></span>' : "";
$col = $dg->addColumn("عنوان مدرک" . $title, "DocDesc", "");
$col->sortable = false;
$col->width = 100;

$col = $dg->addColumn("اطلاعات مدرک", "paramValues", "");

$col = $dg->addColumn("فایل", "HaveFile", "");
$col->renderer = "Package.FileRender";
$col->align = "center";
$col->width = 40;

$dg->HeaderMenu = false;
$dg->EnableGrouping = true;
$dg->DefaultGroupField = "ObjectID";
$dg->groupHeaderTpl = " <table width=100% >" .
	"<tr>" .
	"<td><span class=blueText>{[values.rows[0].data.ObjectDesc]} {[values.rows[0].data.fullname]} ".
	"[ {[values.rows[0].data.ObjectID]} ] </span></td>" .
	"<td width=40px>" .
		"<div title=اطلاعات آیتم onclick=PackageObject.ObjectInfo(event,{[values.rows[0].data.RowID]}); class=info2 " .
		"style=background-repeat:no-repeat;float:right;background-position:center;cursor:pointer;width:16px;height:16></div>" .
		
		"<div title=حذف onclick=PackageObject.DeletePackageItem(event,{[values.rows[0].data.RowID]}); class=remove " .
		"style=background-repeat:no-repeat;float:right;background-position:center;cursor:pointer;width:16px;height:16></div>" .
	"</td>" .
	"</tr>" .        
	"</table>";

$dg->height = 400;
$dg->DefaultSortField = "ObjectID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "paramValues";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->emptyTextOfHiddenColumns = true;
$grid2 = $dg->makeGrid_returnObjects();
?>
<center>
    <form id="mainForm">
        <div id="div_selectBranch"></div>
        <table width="98%">
			<tr>
				<td style="width:350px"><div id="grid_div"></div></td>
				<td>&nbsp;</td>
				<td><div id="grid2_div"></div></td>
			</tr>
		</table>
    </form>
</center>
<script>

Package.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',
	MenuID : "<?= $_POST["MenuID"] ?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function Package(){
	
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: '/framework/person/persons.data.php?task=selectPersons',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		displayField: 'fullname',
		valueField : "PersonID",
		itemId : "cmp_PersonID"
	});
	
	//...................................................
	
	this.grid = <?= $grid ?>;
	this.grid.plugins[0].on("beforeedit", function(editor,e){
		
		if(e.record.data.PersonID*1 > 0)
			editor.editor.down("[itemId=cmp_PersonID]").disable();
		else
			editor.editor.down("[itemId=cmp_PersonID]").enable();
		
		return true;
	});
		
	//...................................................
	
	this.docPanel = new Ext.panel.Panel({
		border : false,
		autoWidth : true,
		minHeight : 200,
		minWidth : 200,
		autoHeight : true,
		renderTo : this.get("grid2_div"),
		loader : {
			url : this.address_prefix + "documents.php",
			scripts : true
		}
	});
	
	//...................................................
	
	this.groupPnl = new Ext.form.FieldSet({
		title : "انتخاب شعبه",
		renderTo: this.get("div_selectBranch"),
		width: 500,
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/framework/baseInfo/baseInfo.data.php?task=GetAccessBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			anchor : "90%",
			queryMode : 'local',
			allowBlank : false,
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID",
			listeners :{
				select : function(combo,records){
					PackageObject.grid.getStore().proxy.extraParams.BranchID = records[0].data.BranchID;
					if(PackageObject.grid.rendered)
						PackageObject.grid.getStore().loadPage(1);
					else
						PackageObject.grid.render(PackageObject.get("grid_div"));
					
					PackageObject.docPanel.hide();
				}
			}
		}]
	});	
}

Package.prototype.AddPackage = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		BranchID : this.groupPnl.down("[name=BranchID]").getValue(),
		PackageID : null,
		PackageNo : null,
		PersonID: null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

Package.prototype.SavePackage = function(){

	var record = this.grid.getSelectionModel().getLastSelected();
	mask = new Ext.LoadMask(this.grid,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'dms.data.php',
		method: "POST",
		params: {
			task: "SavePackage",
			record: Ext.encode(record.data)
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				PackageObject.grid.getStore().load();
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

Package.DeleteRender = function(v,p,r){
	if(PackageObject.RemoveAccess)	
		return "<div align='center' title='حذف' class='remove' "+
		"onclick='PackageObject.DeletePackage();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";

}

Package.OperationRender = function(v,p,r){
	return "<div  title='عملیات' class='setting' onclick='PackageObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

Package.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();

	op_menu.add({text: 'محتویات پرونده',iconCls: 'list', 
		handler : function(){ return PackageObject.LoadItems(); }});
	
	op_menu.add({text: 'رویدادها',iconCls: 'task', 
		handler : function(){ return PackageObject.ShowEvents(); }});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

Package.prototype.DeletePackage = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PackageObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'dms.data.php',
			params:{
				task: "DeletePackage",
				PackageID : record.data.PackageID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				PackageObject.grid.getStore().load();
				PackageObject.docPanel.hide();
			},
			failure: function(){}
		});
	});
}

Package.prototype.LoadItems = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	this.docPanel.loader.load({
		params : {
			ExtTabID : this.docPanel.getEl().id,
			ObjectType : "package",
			ObjectID : record.data.PackageID
		}
	});
	this.docPanel.show();
	return;
}

Package.prototype.ShowEvents = function(){

	if(!this.EventsWin)
	{   
		this.EventsWin = new Ext.window.Window({
			title: 'رویدادهای مرتبط با پرونده',
			modal : true,
			autoScroll : true,
			width: 1000,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "events.php",
				scripts : true
			},
			buttons : [{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){
					this.up('window').hide();
				}
			}]
		});
		Ext.getCmp(this.TabID).add(this.EventsWin);
	}
	this.EventsWin.show();
	this.EventsWin.center();	
	record = this.grid.getSelectionModel().getLastSelected();
	this.EventsWin.loader.load({
		params : {
			ExtTabID : this.EventsWin.getEl().id,
			PackageID : record.data.PackageID,
			MenuID : this.MenuID
		}
	});
}

//.................................................

Package.prototype.ObjectInfo = function(e,RowID){
	
	var index = this.itemGrid.getStore().find("RowID", RowID);
	var record = this.itemGrid.getStore().getAt(index);
	e.stopImmediatePropagation();
	
	if(record.data.param3 == "1")
		window.open(record.data.param1 + "?" + record.data.param2 + "=" + record.data.ObjectID);
	else
	{
		eval("param={" + record.data.param2 + ": " + record.data.ObjectID + "}");
		framework.OpenPage(record.data.param1, "اطلاعات " + record.data.ObjectDesc, param);
	}
}

var PackageObject = new Package();	

</script>