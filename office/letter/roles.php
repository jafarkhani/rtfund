<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 94.06
//-----------------------
require_once '../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dgh = new sadaf_datagrid("dgh1",$js_prefix_address."letter.data.php?task=SelectOFCRoles","div_dg");

$dgh->addColumn("","RowID",'string',true);
$dgh->addColumn("","fullname",'string',true);
$dgh->addColumn("","RoleDesc",'string',true);

$col = $dgh->addColumn("نام کاربر ", "PersonID");
$col->renderer="function(v,p,r){return r.data.fullname;}";
$col->editor = "this.PersonCombo";

$col=$dgh->addColumn("سمت", "RoleID");
$col->renderer="function(v,p,r){return r.data.RoleDesc;}";
$col->editor = "this.RoleCombo";
$col->width = 200;

if($accessObj->RemoveFlag)
{
	$col = $dgh->addColumn("حذف", "", "string");
	$col->renderer = "OfficeRole.deleteRender";
	$col->width = 40;
}
if($accessObj->AddFlag)
{
	$dgh->addButton = true;
	$dgh->addHandler = "function(v,p,r){ return OfficeRoleObject.Add(v,p,r);}";
	$dgh->enableRowEdit = true ;
	$dgh->rowEditOkHandler = "function(v,p,r){ return OfficeRole.Save(v,p,r);}";
}
$dgh->title ="نوع دسترسی";

$dgh->emptyTextOfHiddenColumns=true;
$dgh->width = 600;
$dgh->DefaultSortField = "RoleID";
$dgh->DefaultSortDir = "ASC";
$dgh->height = 400;
$dgh->EnableSearch = false;
$dgh->EnablePaging = false;
$dgh->pageSize=12;
$grid = $dgh->makeGrid_returnObjects();
?>
<script>

OfficeRole.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function OfficeRole()
{
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: '/framework/management/framework.data.php?task=selectPersons',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		displayField: 'fullname',
		valueField : "PersonID"
	});

	this.RoleCombo = new Ext.form.ComboBox({
		store :  new Ext.data.Store({
			proxy: {type: 'jsonp',
				url: this.address_prefix + 'letter.data.php?task=SelectRoles',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields:['InfoID','InfoDesc'],
			autoLoad : true
		}),
		valueField : "InfoID",
		displayField : "InfoDesc",
		queryMode : 'local'
	});

	this.grid = <?= $grid ?>;                
	this.grid.render(this.get("div_dg"));
	
}

OfficeRole.deleteRender = function(value, p, record)
{
	return "<div  title='حذف اطلاعات' class='remove' onclick='OfficeRoleObject.DeleteAccess();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:20px;height:16'></div>";
}

var OfficeRoleObject = new OfficeRole();

OfficeRole.Save = function(store,record,op)
{    
	mask = new Ext.LoadMask(Ext.getCmp(OfficeRoleObject.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();    
	
	Ext.Ajax.request({
		url:  OfficeRoleObject.address_prefix + 'letter.data.php?task=SaveRole',
		params:{
			PersonID : record.data.PersonID,
			RoleID : record.data.RoleID
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			OfficeRoleObject.grid.getStore().load();
		},
		failure: function(){}
	});
}	

OfficeRole.prototype.Add = function()
{  
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		RoleID:null,
		PersonID:null		

	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

OfficeRole.prototype.DeleteAccess = function()
{    
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		
		if(btn == "no")
			return;
		
		me = OfficeRoleObject;
		var record = me.grid.getSelectionModel().getLastSelected();

		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();
		
		Ext.Ajax.request({
			url: me.address_prefix + 'letter.data.php?task=DeleteRole',
			params:{
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				OfficeRoleObject.grid.getStore().load();
			},
			failure: function(){}
		});		
	});
}

</script>

<center>
	<br>
	<div id="div_dg"></div>
</center>


