<?php
//-----------------------------
// programmer: SH.Jafarkhani
// create Date: 94.12
//-----------------------------
require_once '../header.inc.php';
require_once inc_dataGrid;

$AddMode = isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "adding" ? true : false;

?>
<script>
ExpertDomain.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",
	AddMode : <?= $AddMode ? "true" : "false" ?>,

	parent : <?= isset($_REQUEST["parent"]) ? $_REQUEST["parent"] : "null" ?>,
	selectHandler : <?= isset($_REQUEST["selectHandler"]) ? $_REQUEST["selectHandler"] : "function(){}" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ExpertDomain()
{
	this.tree = Ext.create('Ext.tree.Panel', {
		title : "حوزه کارشناسی",
        store: new Ext.data.TreeStore({
			proxy: {
				type: 'ajax',
				url: this.address_prefix + 'baseInfo.data.php?task=SelectExpertDomainNodes'
			},
			root: {
				text: "حوزه کارشناسی",
				id: 'src',
				expanded: true
			}
		}),
		listeners : {
			itemclick : function(v,record){
				if(!record.data.leaf) return; 
				ExpertDomainObject.selectHandler(record.data.id, record.data.text);
				ExpertDomainObject.parent.hide();
			}
		},
        width: this.AddMode ? 405 : 750,
        height: this.AddMode ? 385 : 500,
        renderTo: this.get("div_tree")
    });
	
	if(!this.AddMode)
		this.tree.on("itemcontextmenu", function(view, record, item, index, e)
		{
			e.stopEvent();
			e.preventDefault();
			view.select(index);

			this.Menu = new Ext.menu.Menu();

			this.Menu.add({
				text: 'ایجاد زیر سطح',
				iconCls: 'add',
				handler : function(){ExpertDomainObject.BeforeSaveDomain(false);}
			});

			if(record.data.id != "src")
			{
				this.Menu.add({
					text: 'ویرایش عنوان',
					iconCls: 'edit',
					handler : function(){ExpertDomainObject.BeforeSaveDomain(true);}
				});

				this.Menu.add({
					text: 'حذف سطح',
					iconCls: 'remove',
					handler : function(){ExpertDomainObject.DeleteDomain();}
				});
			}

			var coords = e.getXY();
			this.Menu.showAt([coords[0]-120, coords[1]]);
		});
}

var ExpertDomainObject = new ExpertDomain();

ExpertDomain.prototype.BeforeSaveDomain = function(EditMode){

	if(!this.infoWin)
	{
		this.infoWin = new Ext.window.Window({
            applyTo: this.get("NewWIN"),
            modal : true,
            title: "زیر سطح",
            width : 500,
            closeAction : "hide",
            items : new Ext.form.Panel({
                bodyStyle : "text-align:right;padding:5px",
                items :[{
					xtype : "textfield",
					name : "DomainDesc",
					itemId : "DomainDesc",
					fieldLabel : "عنوان",
					anchor : "100%"
				},{
					xtype : "hidden",
					itemId : "ParentID",
					name : "ParentID"
				},{
					xtype : "hidden",
					itemId : "DomainID",
					name : "DomainID"
				}],
                buttons :[{
					text : "ذخیره",
					handler : function(){ExpertDomainObject.SaveDomain();},
					iconCls : "save"
				},{
					text : "انصراف",
					handler : function(){
						this.up('window').hide();
					},
					iconCls : "undo"
				}]
            })
        });
	}
	
	var record = this.tree.getSelectionModel().getSelection()[0];
	this.infoWin.down('form').getForm().reset();
	this.infoWin.show();
	this.infoWin.down('form').getComponent("ParentID").setValue(record.data.id);

	if(EditMode)
	{
		this.infoWin.down('form').getComponent("DomainID").setValue(record.data.id);
		this.infoWin.down('form').getComponent("DomainDesc").setValue(record.data.text);
		this.infoWin.down('form').getComponent("ParentID").setValue(record.data.parentId);
	}
}

ExpertDomain.prototype.SaveDomain= function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	this.infoWin.down('form').getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'baseInfo.data.php?task=SaveExpertDomain',
		method : "POST",

		success : function(form,action){                

			me = ExpertDomainObject;
			DomainID = me.infoWin.down('form').getComponent("DomainID").getValue();
			mode = DomainID == "" ? "new" : "edit";

			if(mode == "new")
			{
				ParentID = me.infoWin.down('form').getComponent("ParentID").getValue();
				Parent = ParentID == "src" ? me.tree.getRootNode() :
											 me.tree.getRootNode().findChild("id",ParentID,true);
				Parent.set('leaf', false);
				Parent.appendChild({
					id : action.result.data,
					text :  me.infoWin.down('form').getComponent("DomainDesc").getValue(),
					leaf : true
				});  
				Parent.expand();
			}
			else
			{
				node = me.tree.getRootNode().findChild("id", DomainID, true);
				node.set('text', me.infoWin.down('form').getComponent("DomainDesc").getValue());
			}

			me.infoWin.down('form').getForm().reset();
			me.infoWin.hide();

			mask.hide();

		},
		failure : function(form,action)
		{
			Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
			mask.hide();
		}
	});
}

ExpertDomain.prototype.DeleteDomain = function(){
	
	me = ExpertDomainObject;
	var record = me.tree.getSelectionModel().getSelection()[0];
	if(record.hasChildNodes())
	{
		Ext.MessageBox.alert("","این سطح دارای زیر سطح می باشد و قادر به حذف آن نمی باشید.");
		return;
	}
	Ext.Ajax.request({
		url : me.address_prefix + "baseInfo.data.php",
		method : "POST",
		params : {
			task : "DeleteExpertDomain",
			DomainID : record.data.id
		},
		success : function(response){
			result = Ext.decode(response.responseText);
			if(!result.success)
			{
				Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				return;
			}				
			record.remove();
		}
	});
		
}

</script>
<div id="div_body" style="<? if(!$AddMode){ ?>margin: 20 20 0 0<?}?>">
	<div id="div_tree"></div>
	<div id="div_grid"></div>
</div>
