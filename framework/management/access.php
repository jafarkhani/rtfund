<?php
//-----------------------------
// programmer: SH.Jafarkhani
// create Date: 97.11
//-----------------------------
require_once '../../header.inc.php';
 
?>
<script>
FRW_access.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function FRW_access()
{
	this.filterPanel = new Ext.form.Panel({
		title: "مدیریت دسترسی کاربران",
		width: 300,
		applyTo : this.get("div_form"),
		frame: true,
		tbar : [{
			text : "بارگذاری دسترسی ها",
			iconCls : "refresh",
			handler : function(){
				
				id = FRW_accessObject.filterPanel.down("[itemId=id]").getValue().toString();
				if(id[0] == "g")
				{
					GroupID = id.toString().substring(2);
					PersonID = 0;
				}
				else
				{
					GroupID = 0;
					PersonID = id.toString().substring(2);					
				}
				
				FRW_accessObject.tree.getStore().proxy.extraParams = {
					GroupID : GroupID,
					PersonID : PersonID
				};
				if(!FRW_accessObject.tree.rendered)
				{
					FRW_accessObject.tree.render(FRW_accessObject.get("div_tree"));
					FRW_accessObject.tree.getStore().load();
				}
				else
					FRW_accessObject.tree.getStore().load();
			}
		}],
		items : [{
			xtype : "multiselect",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + 'framework.data.php?task=SelectPersonAndGroups',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['type','id','title'],
				autoLoad : true
			}),
			displayField: 'title',
			valueField : "id",
			itemId : "id",
			width : 290,
			height : 370
		}]
	});
	
	this.tree = Ext.create('Ext.tree.Panel', {
		title : "منوهای سیستم",
		//useArrows: true,
        rootVisible: false,
		singleExpand: true,
		rowLines: true,
		frame : true,
		buttons : [{
            xtype: "button",
            iconCls: "save",
            text: "ذخیره دسترسی ها",
            handler: function () {
				FRW_accessObject.saveAction();
            }
        }],
        store: new Ext.data.TreeStore({
			fields: ["id","text","MenuID", "MenuDesc","MenuPath","ViewFlag","AddFlag","EditFlag","RemoveFlag"],
			proxy: {
				type: 'ajax',
				url: this.address_prefix + 'framework.data.php?task=SelectAccessMenuNodes',
				extraParams : {
					GroupID : 0,
					PersonID : 0
				}
			},
			root: {
				text: "منوهای سیستم",
				id: 'src',
				expanded: true
			}
		}),
        width: 750,
        height: 430,
		columns: [{
            xtype: 'treecolumn', //this is so we know which column will show the tree
            text: 'لیست منوهای سیستم',
            width : 390,
            dataIndex: 'MenuDesc'
        },{
            text: 'دسترسی کامل' + '<br><input type=checkbox onclick=FRW_access.CheckAll("fullChk",this)>',
			align: 'center',
			menuDisabled : true,
			sortable : false,
            width : 80,
            dataIndex: 'ViewFlag',
			renderer : function(v,p,r){
				if(r.data.MenuPath == null)
					return;
				str = '<input type="checkbox" id="fullChk_' + r.data.MenuID +
					'" onclick="FRW_accessObject.fullClick(this,' + r.data.MenuID + ');" ';
				str += (r.data.ViewFlag == "YES" && r.data.AddFlag == "YES" &&
					r.data.EditFlag == "YES" && r.data.RemoveFlag == "YES") ? " checked " : "";
				str += ">";
				return str;
			}
        },{
            text: 'مشاهده' + '<br><input type=checkbox onclick=FRW_access.CheckAll("viewChk",this)>',
			align: 'center',
			menuDisabled : true,
			sortable : false,
            width : 60,
            dataIndex: 'ViewFlag',
			renderer : function(v,p,r){
				if(r.data.MenuPath == null)
					return;
				str = '<input type="checkbox" id="viewChk_' + r.data.MenuID +
					'" onclick="FRW_accessObject.ViewFlagClick(this,' + r.data.MenuID +
					');" name="viewChk_' + r.data.MenuID + '"';
				str += (v == "YES") ? " checked " : "";
				str += ">";
				return str;
			}
        },{
            text: 'ایجاد' + '<br><input type=checkbox onclick=FRW_access.CheckAll("addChk",this)>',
			align: 'center',
			menuDisabled : true,
			sortable : false,
            width : 60,
            dataIndex: 'AddFlag',
			renderer : function(v,p,r){
				if(r.data.MenuPath == null)
					return;
				str = '<input type="checkbox" id="addChk_' + r.data.MenuID +
					'" onclick="FRW_accessObject.checkViewFlag(this,' + r.data.MenuID +
					');" name="addChk_' + r.data.MenuID + '"';
				str += (v == "YES") ? " checked " : "";
				str += ">";
				return str;
			}
        },{
            text: 'ویرایش' + '<br><input type=checkbox onclick=FRW_access.CheckAll("editChk",this)>',
			align: 'center',
			menuDisabled : true,
			sortable : false,
            width : 60,
            dataIndex: 'EditFlag',
			renderer : function(v,p,r){
				if(r.data.MenuPath == null)
					return;
				str = '<input type="checkbox" id="editChk_' + r.data.MenuID +
					'" onclick="FRW_accessObject.checkViewFlag(this,' + r.data.MenuID +
					');" name="editChk_' + r.data.MenuID + '"';
				str += (v == "YES") ? " checked " : "";
				str += ">";
				return str;
			}
        },{
            text: 'حذف' + '<br><input type=checkbox onclick=FRW_access.CheckAll("removeChk",this)>',
			align: 'center',
			menuDisabled : true,
			sortable : false,
            width : 60,
            dataIndex: 'RemoveFlag',
			renderer : function(v,p,r){
				if(r.data.MenuPath == null)
					return;
				str = '<input type="checkbox" id="removeChk_' + r.data.MenuID +
					'" onclick="FRW_accessObject.checkViewFlag(this,' + r.data.MenuID +
					');" name="removeChk_' + r.data.MenuID + '"';
				str += (v == "YES") ? " checked " : "";
				str += ">";
				return str;
			}
        }]
    });
}

FRW_access.prototype.checkViewFlag = function(elem,MenuID)
{
	if(elem.checked)
		this.get("viewChk_" + MenuID).checked = true;
	else
		this.get("fullChk_" + MenuID).checked = false;
}

FRW_access.prototype.fullClick = function(elem,MenuID)
{
	if(elem.checked)
	{
		this.get("viewChk_" + MenuID).checked = true;
		this.get("addChk_" + MenuID).checked = true;
		this.get("editChk_" + MenuID).checked = true;
		this.get("removeChk_" + MenuID).checked = true;
	}
	else
	{
		this.get("viewChk_" + MenuID).checked = false;
		this.get("addChk_" + MenuID).checked = false;
		this.get("editChk_" + MenuID).checked = false;
		this.get("removeChk_" + MenuID).checked = false;
	}
}

FRW_access.prototype.ViewFlagClick = function(elem,MenuID)
{
	if(!elem.checked)
	{
		this.get("fullChk_" + MenuID).checked = false;
		this.get("addChk_" + MenuID).checked = false;
		this.get("editChk_" + MenuID).checked = false;
		this.get("removeChk_" + MenuID).checked = false;
	}
}

FRW_access.CheckAll = function(prefix,elem){
	
	elems = FRW_accessObject.get("MainForm").getElementsByTagName("input");
	for(i=0; i<elems.length; i++)
		if(elems[i].id.indexOf(prefix) != -1)
			elems[i].checked = elem.checked;
}

FRW_access.prototype.saveAction = function()
{
	var mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg: 'در حال ذخيره اطلاعات ...'});
	mask.show();
	Ext.Ajax.request({
		url: this.address_prefix + 'framework.data.php?task=SaveAccess',
		form: this.get("MainForm"),
		method: "POST",
		params : {
			PersonID : this.tree.getStore().proxy.extraParams.PersonID,
			GroupID : this.tree.getStore().proxy.extraParams.GroupID
		},

		success: function(response)
		{
			FRW_accessObject.tree.getStore().load();
			mask.hide();
		}
	});
}

var FRW_accessObject = new FRW_access();

</script>
<form id="MainForm">
<table style="margin: 10px" >
	<tr>
		<td><div id="div_form"></div></td>
		<td><div id="div_tree"></div></td>
	</tr>
</table>
</form>