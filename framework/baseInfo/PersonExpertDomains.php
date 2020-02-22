<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.02
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
$dg = new sadaf_datagrid("dg", $js_prefix_address . "baseInfo.data.php?task=SelectPersonExpertDomains", "grid_div");

$dg->addColumn("", "RowID", "", true);
$dg->addColumn("", "fullname", "", true);
$dg->addColumn("", "DomainDesc", "", true);

$col = $dg->addColumn("نام و نام خانوادگی", "PersonID", "");
$col->editor = "this.PersonCombo";
$col->renderer = "function(v,p,r){return r.data.fullname;}";

$col = $dg->addColumn("حوزه کارشناسی", "DomainID");
$col->renderer = "function(v,p,r){return r.data.DomainDesc;}";
$col->editor = "this.DomainTrigger";
$col->width = 150;

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){PersonExpertDomainObject.AddPersonExpertDomain();}";
	
	$dg->enableRowEdit = true;
	$dg->rowEditOkHandler = "function(store,record){".
		"return PersonExpertDomainObject.SavePersonExpertDomain(store,record);}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("حذف", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return PersonExpertDomain.OperationRender(v,p,r);}";
	$col->width = 40;
}
$dg->title = "حوزه کارشناسی کارشناسان";
$dg->height = 500;
$dg->width = 500;
$dg->DefaultSortField = "PersonID";
$dg->autoExpandColumn = "PersonID";
$dg->emptyTextOfHiddenColumns = true;
$grid = $dg->makeGrid_returnObjects();

?>
<script>

PersonExpertDomain.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

PersonExpertDomain.OperationRender = function(v,p,r)
{
	if(PersonExpertDomainObject.RemoveAccess)	
		return "<div align='center' title='حذف ' class='remove' "+
		"onclick='PersonExpertDomainObject.DeletePersonExpertDomain();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

function PersonExpertDomain(){
	
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: '/framework/person/persons.data.php?task=selectPersons&UserType=IsStaff,IsExpert',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		displayField: 'fullname',
		valueField : "PersonID",
		name : "PersonID"
	});
	
	this.DomainTrigger = new Ext.form.TriggerField({
		triggerCls:'x-form-search-trigger',
		onTriggerClick : function(){
			PersonExpertDomainObject.ExpertDomainLOV();
		}
	});
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("grid_div"));
}

var PersonExpertDomainObject = new PersonExpertDomain();	

PersonExpertDomain.prototype.ExpertDomainLOV = function(){
		
	if(!this.DomainWin)
	{
		this.DomainWin = new Ext.window.Window({
			autoScroll : true,
			width : 420,
			height : 420,
			title : "حوزه کارشناسی",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "ExpertDomain.php?mode=adding",
				scripts : true
			}
		});
		
		Ext.getCmp(this.TabID).add(this.DomainWin);
	}
	
	this.DomainWin.show();
	
	this.DomainWin.loader.load({
		params : {
			ExtTabID : this.DomainWin.getEl().dom.id,
			parent : "PersonExpertDomainObject.DomainWin",
			selectHandler : function(id, name){
				PersonExpertDomainObject.DomainTrigger.setValue(name);
				PersonExpertDomainObject.DomainID = id;
			}
		}
	});
}


PersonExpertDomain.prototype.AddPersonExpertDomain = function(){

	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		PersonID : null,
		DomainID : null
	});

	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

PersonExpertDomain.prototype.SavePersonExpertDomain = function(store,resord){

	mask = new Ext.LoadMask(this.grid,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix +'baseInfo.data.php',
		method: "POST",
		params: {
			task: "SavePersonExpertDomain",
			PersonID: resord.data.PersonID,
			DomainID : this.DomainID
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(!st.success)
			{
				if(st.data == "")
					Ext.MessageBox.alert("","خطا در اجرای عملیات");
				else
					Ext.MessageBox.alert("",st.data);
			}
			
			PersonExpertDomainObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

PersonExpertDomain.prototype.DeletePersonExpertDomain = function()
{
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = PersonExpertDomainObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'baseInfo.data.php',
			params:{
				task: "DeletePersonExpertDomain",
				RowID : record.data.RowID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				PersonExpertDomainObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

</script>
<center>
    <form id="mainForm">
        <br>
        <div id="div_selectGroup"></div>
        <br>
		<div id="newDiv"></div>
        <div id="grid_div"></div>
    </form>
</center>
