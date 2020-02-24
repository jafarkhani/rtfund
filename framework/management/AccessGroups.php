<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "framework.data.php?task=SelectGroupList", "grid_div");

$dg->addColumn("", "GroupID", "", true);
$dg->addColumn("", "fullname", "", true);


$col = $dg->addColumn("نام و نام خانوادگی", "PersonID", "");
$col->renderer="function(v,p,r){return r.data.fullname;}";
$col->editor = "this.PersonCombo";

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){AccessGroupObject.AddPerson();}";
	
	$dg->enableRowEdit = true ;
	$dg->rowEditOkHandler = "function(v,p,r){ return AccessGroupObject.Save(v,p,r);}";
}
$dg->title = "لیست کاربران";

$dg->height = 500;
$dg->width = 750;
$dg->EnablePaging = false;
$dg->DefaultSortField = "AccessGroupDesc";
$dg->emptyTextOfHiddenColumns = true;

$col = $dg->addColumn("عملیات", "");
$col->sortable = false;
$col->renderer = "function(v,p,r){return AccessGroup.OperationRender(v,p,r);}";
$col->width = 50;

$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
        <br>
        <div id="div_selectGroup"></div>
        <br>
		<div id="newDiv"></div>
        <div id="grid_div"></div>
    </form>
</center>
<script>
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------

AccessGroup.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	GroupID : null,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function AccessGroup(){
	
	this.PersonCombo = new Ext.form.ComboBox({
		store: new Ext.data.Store({
			proxy:{
				type: 'jsonp',
				url: this.address_prefix + '../person/persons.data.php?task=selectPersons',
				reader: {root: 'rows',totalProperty: 'totalCount'}
			},
			fields :  ['PersonID','fullname']
		}),
		pageSize : 25,
		fieldLabel : "کاربر",
		displayField: 'fullname',
		valueField : "PersonID",
		hiddenName : "PersonID",
		width : 400,
		itemId : "PersonID"
	});
	
	this.grid = <?= $grid ?>;

	this.groupPnl = new Ext.form.Panel({
		renderTo: this.get("div_selectGroup"),
		title: "انتخاب گروه",
		width: 400,
		collapsible : true,
		collapsed : false,
		frame: true,
		bodyCfg: {style: "background-color:white"},
		items : [{
				xtype : "combo",
				store : new Ext.data.SimpleStore({
					proxy: {type: 'jsonp',
						url: this.address_prefix + 'framework.data.php?task=SelectAccessGroups',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					autoLoad : true,
					fields : ['GroupID','GroupDesc']
				}),
				valueField : "GroupID",
				queryMode : "local",
				name : "GroupID",
				displayField : "GroupDesc",
				fieldLabel : "انتخاب گروه"
			},{
				xtype : "fieldset",
				collapsible: true,
				collapsed : true,
				title : "ایجاد گروه جدید",
				width : 350,
				style : "background-color: #F2FCFF",
				items : [{
						xtype : "textfield",
						name : "GroupDesc",
						fieldLabel : "عنوان گروه"
					},{
						xtype : "button",
						disabled : this.AddAccess ? false : true,
						text: "ایجاد گروه",
						handler: function(){

							GroupDesc = this.up('form').down("[name=GroupDesc]").getValue();
							AccessGroupObject.SaveGroups(0,GroupDesc);
						}
					}]
			}],
		buttons:[{
				text : "ویرایش گروه",
				disabled : this.RemoveAccess ? false : true,
				iconCls : "edit",
				handler : function(){
					AccessGroupObject.EditGroup(this.up('form').down('[name=GroupID]').getValue());
				}
			},{
				text : "حذف گروه",
				disabled : this.RemoveAccess ? false : true,
				iconCls : "remove",
				handler : function(){
					AccessGroupObject.DeleteGroup(this.up('form').down('[name=GroupID]').getValue());
				}
			},'->',{
				text: "لیست افراد",
				iconCls: "refresh",
				handler: function(){ AccessGroupObject.LoadAccessGroups(); }
			}]
	});	
}

AccessGroup.prototype.SaveGroups = function(GroupID,GroupDesc){
	
	var mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg: 'تغییر اطلاعات ...'});
	mask.show();

	Ext.Ajax.request({
		method : "POST",
		url: AccessGroupObject.address_prefix + "framework.data.php",
		params: {
			task: "SaveAccessGroup",
			GroupID : GroupID,
			GroupDesc: GroupDesc
		},
		success: function(response){
			mask.hide();
			if(GroupID == 0)
			{
				AccessGroupObject.groupPnl.down("[name=GroupID]").getStore().load({
					callback : function(){
						AccessGroupObject.groupPnl.down("[name=GroupID]").setValue(
							this.getAt(this.getCount()-1));
						AccessGroupObject.LoadAccessGroups();
					}});
				AccessGroupObject.groupPnl.down('fieldset').collapse();
			}
			else
			{
				AccessGroupObject.groupPnl.down("[name=GroupID]").getStore().load({
					callback : function(){
						AccessGroupObject.groupPnl.down("[name=GroupID]").setValue(GroupID);
					}});
				AccessGroupObject.editWin.hide();
			}
		}
	});
}

AccessGroup.prototype.LoadAccessGroups = function(){

	AccessGroupObject.GroupID = this.groupPnl.down('[name=GroupID]').getValue();

	AccessGroupObject.grid.getStore().proxy.extraParams.GroupID = AccessGroupObject.GroupID;

	if(AccessGroupObject.grid.rendered)
		AccessGroupObject.grid.getStore().load();
	else
		AccessGroupObject.grid.render(AccessGroupObject.get("grid_div"));
	
	AccessGroupObject.grid.show();
	AccessGroupObject.groupPnl.collapse();
}

AccessGroup.OperationRender = function(v,p,r){
	
	if(AccessGroupObject.RemoveAccess)	
		return "<div align='center' title='حذف' class='remove' "+
		"onclick='AccessGroupObject.DeletePerson();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
	return "";
}

AccessGroup.prototype.DeleteGroup = function(GroupID){
	
	Ext.MessageBox.confirm("","درصورت حذف کلیه افراد گروه نیز حذف می شوند.<br>آیا مایل به حذف می باشید؟", 
	function(btn){
		if(btn == "no")
			return;
		
		me = AccessGroupObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'framework.data.php',
			params:{
				task: "DeleteAccessGroup",
				GroupID : GroupID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);

				if(sd.success)
				{
					AccessGroupObject.groupPnl.down('[name=GroupID]').setValue();
					AccessGroupObject.groupPnl.down('[name=GroupID]').getStore().load();
					AccessGroupObject.grid.hide();
				}	
			},
			failure: function(){}
		});
	});
}

AccessGroup.prototype.EditGroup = function(){
	
	if(!this.editWin)
	{
		this.editWin = new Ext.window.Window({
			title: 'ویرایش گروه',
			modal : true,
			width: 400,
			closeAction : "hide",
			items : new Ext.form.Panel({
				plain: true,
				border: 0,
				bodyPadding: 5,
				items : [{
					xtype : "textfield",
					fieldLabel: "عنوان گروه",
					name : "GroupDesc"
				}],
				buttons : [{
					text : "ذخیره",
					iconCls : "save",
					handler : function(){ 
						GroupDesc = this.up('form').down("[name=GroupDesc]").getValue();
						AccessGroupObject.SaveGroups(AccessGroupObject.GroupID,GroupDesc);	}
				},{
					text : "انصراف",
					iconCls : "undo",
					handler : function(){
						this.up('window').hide();
					}
				}]
			})
		});	
		
		Ext.getCmp(this.TabID).add(this.editWin);
	}
	
	el = this.groupPnl.down('[name=GroupID]');
	this.GroupID = el.getValue();
	if(this.GroupID == null)
		return;
	
	this.editWin.down('[name=GroupDesc]').setValue(
			el.getStore().findRecord("GroupID",el.getValue()).data.GroupDesc);
	this.editWin.show();
	this.editWin.center();	
}

AccessGroup.prototype.Save = function(store,record,op){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();    
	Ext.Ajax.request({
		url: this.address_prefix + 'framework.data.php?task=SaveGroupList',
		params:{
			PersonID : record.data.PersonID,
			GroupID : this.GroupID
		},
		method: 'POST',
		success: function(response,option){
			mask.hide();
			AccessGroupObject.grid.getStore().load();
		},
		failure: function(){}
	});
}

AccessGroup.prototype.DeletePerson = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = AccessGroupObject;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'framework.data.php',
			params:{
				task: "DeleteGroupList",
				GroupID : record.data.GroupID,
				PersonID : record.data.PersonID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				AccessGroupObject.grid.getStore().load();
			},
			failure: function(){}
		});
	});
}

AccessGroup.prototype.AddPerson = function(){
	
	var modelClass = this.grid.getStore().model;
	var record = new modelClass({
		GroupID:null,
		PersonID:null		

	});
	this.grid.plugins[0].cancelEdit();
	this.grid.getStore().insert(0, record);
	this.grid.plugins[0].startEdit(0, 0);
}

var AccessGroupObject = new AccessGroup();	

</script>