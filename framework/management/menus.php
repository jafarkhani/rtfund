<?php
//-----------------------------
// programmer: SH.Jafarkhani
// create Date: 97.11
//-----------------------------
require_once '../../header.inc.php';

?>
<script>
FRW_menu.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function FRW_menu()
{
	this.tree = Ext.create('Ext.tree.Panel', {
		title : "منوهای سیستم",
		tbar : [],
        store: new Ext.data.TreeStore({
			proxy: {
				type: 'ajax',
				url: this.address_prefix + 'framework.data.php?task=SelectMenuNodes'
			},
			root: {
				text: "منوهای سیستم",
				id: 'src',
				expanded: true
			}
		}),
        width: 750,
        height: 500,
        renderTo: this.get("div_tree")
    });

	this.tree.getDockedItems('toolbar[dock="top"]')[0].add({
            xtype: "button",
            iconCls: "print",
            text: "چاپ",
            handler: function () {
                Ext.ux.Printer.print(FRW_menuObject.tree);
            }
        }, '-', {
            xtype: "button",
            iconCls: "refresh",
            text: "بازگذاری مجدد",
            handler: function () {
                FRW_menuObject.tree.getStore().load();
            }
        });
		
	this.tree.on("itemcontextmenu", function(view, record, item, index, e)
	{
		me = FRW_menuObject;
		
		if(me.SelectMode)
			return;
			
		e.stopEvent();
		e.preventDefault();
		view.select(index);

		me.Menu = new Ext.menu.Menu();
		me.Menu.add({
			text: 'ایجاد گروه منو',
			iconCls: 'add',
			handler : function(){FRW_menuObject.BeforeSave(false,false);}
		},{
			text: 'ایجاد منو عملیاتی',
			iconCls: 'add',
			handler : function(){FRW_menuObject.BeforeSave(false,true);}
		});

		if(record.data.id != "src")
		{
				me.Menu.add({
					text: 'ویرایش',
					iconCls: 'edit',
					handler : function(){FRW_menuObject.BeforeSave(true);}
				});

				me.Menu.add({
					text: 'حذف سطح',
					iconCls: 'remove',
					handler : function(){FRW_menuObject.DeleteMenu();}
				});
		}

		var coords = e.getXY();
		me.Menu.showAt([coords[0]-120, coords[1]]);
	});
}

var FRW_menuObject = new FRW_menu();

FRW_menu.prototype.BeforeSave = function(EditMode,IsMenu){

	if(!this.infoWin)
	{
		this.infoWin = new Ext.window.Window({
            applyTo: this.get("NewWIN"),
            modal : true,
            title: "زیر سطح",
            width : 550,
            closeAction : "hide",
            items : new Ext.form.Panel({
                bodyStyle : "text-align:right;padding:5px",
                items :[{
					xtype : "textfield",
					name : "MenuDesc",
					itemId : "MenuDesc",
					fieldLabel : "عنوان",
					anchor : "100%"
				},{
					xtype : "numberfield",
					name : "ordering",
					itemId : "ordering",
					fieldLabel : "ترتیب",
					hideTrigger : true
				},{
					xtype : "textfield",
					name : "icon",
					itemId : "icon",
					style : "direction:ltr",
					fieldLabel : "icon",
					anchor : "100%"
				},{
					xtype : "textfield",
					name : "MenuPath",
					style : "direction:ltr",
					itemId : "MenuPath",
					fieldLabel : "path",
					anchor : "100%"
				},{
					xtype : "fieldset",
					colspan : 2,
					itemId : "FS_UserTypes",
					title : "نوع ذینفع",
					layout : "hbox",
					defaults : {style : "margin-right : 20px"},
					items :[{
						xtype : "checkbox",
						boxLabel: 'همکاران صندوق',
						name: 'IsStaff',
						inputValue: 'YES'
					},{
						xtype : "checkbox",
						boxLabel: 'مشتری',
						name: 'IsCustomer',
						inputValue: 'YES'
					},{
						xtype : "checkbox",
						boxLabel: 'سهامدار',
						name: 'IsShareholder',
						inputValue: 'YES'
					},{
						xtype : "checkbox",
						boxLabel: 'سرمایه گذار',
						name: 'IsAgent',
						inputValue: 'YES'
					},{
						xtype : "checkbox",
						boxLabel: 'حامی',
						name: 'IsSupporter',
						inputValue: 'YES'
					},{
						xtype : "checkbox",
						boxLabel: 'کارشناس خارج از صندوق',
						name: 'IsExpert',
						inputValue: 'YES'
					}]
				},{
					xtype : "hidden",
					itemId : "ParentID",
					name : "ParentID"
				},{
					xtype : "hidden",
					itemId : "MenuID",
					name : "MenuID"
				}],
                buttons :[{
					text : "ذخیره",
					handler : function(){FRW_menuObject.SaveMenu();},
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

	this.infoWin.down("[itemId=FS_UserTypes]").hide();
	if(EditMode)
	{
		this.infoWin.down('form').getComponent("MenuID").setValue(record.data.id);
		this.infoWin.down('form').getComponent("MenuDesc").setValue(record.raw.MenuDesc);
		this.infoWin.down('form').getComponent("ParentID").setValue(record.data.parentId);
		this.infoWin.down('form').getComponent("ordering").setValue(record.raw.ordering);
		this.infoWin.down('form').getComponent("icon").setValue(record.raw.SrcIcon);
		this.infoWin.down('form').getComponent("MenuPath").setValue(record.raw.MenuPath);
		
		if(record.raw.MenuPath != null)
			IsMenu = true;
		
		if(record.parentNode.parentNode.data.id == "1000")
		{
			this.infoWin.down("[itemId=FS_UserTypes]").show();
			this.infoWin.down("[name=IsStaff]").setValue(record.raw.IsStaff);
			this.infoWin.down("[name=IsCustomer]").setValue(record.raw.IsCustomer);
			this.infoWin.down("[name=IsShareholder]").setValue(record.raw.IsShareholder);
			this.infoWin.down("[name=IsAgent]").setValue(record.raw.IsAgent);
			this.infoWin.down("[name=IsExpert]").setValue(record.raw.IsExpert);
			this.infoWin.down("[name=IsSupporter]").setValue(record.raw.IsSupporter);
		}
	}
	else
	{
		if(record.parentNode.data.id == "1000")
			this.infoWin.down("[itemId=FS_UserTypes]").show();
	}
	
	if(IsMenu)
		this.infoWin.down("[itemId=MenuPath]").show();
	else
		this.infoWin.down("[itemId=MenuPath]").hide();
	
	this.infoWin.down('form').getComponent("MenuDesc").focus();
}
 
FRW_menu.prototype.SaveMenu = function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	this.infoWin.down('form').getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'framework.data.php?task=SaveMenu',
		method : "POST",

		success : function(form,action){                

			me = FRW_menuObject;
			//me.tree.getStore().load();
			me.infoWin.down('form').getForm().reset();
			me.infoWin.hide();
			mask.hide();
			
			MenuID = me.infoWin.down('form').getComponent("MenuID").getValue();
			mode = MenuID == "" ? "new" : "edit";
			if(mode == "new")
			{
				ParentID = me.infoWin.down('form').getComponent("ParentID").getValue();
				if(ParentID == "source")
					Parent = me.tree.getRootNode();
				else
					Parent = me.tree.getRootNode().findChild("id",ParentID,true);
				Parent.set('leaf', false);
				Parent.appendChild({
					id : action.result.data,
					text :  me.infoWin.down('form').getComponent("MenuDesc").getValue(),
					leaf : true
				});  
				Parent.expand();
			}
			else
			{
				node = me.tree.getRootNode().findChild("id", MenuID, true);
				node.set('text', me.infoWin.down('form').getComponent("MenuDesc").getValue());
			}

		},
		failure : function(form,action)
		{
			Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
			mask.hide();
		}
	});
}

FRW_menu.prototype.DeleteMenu = function(){
	
	me = FRW_menuObject;
	var record = me.tree.getSelectionModel().getSelection()[0];
	if(record.hasChildNodes())
	{
		Ext.MessageBox.alert("","این سطح دارای زیر سطح می باشد و قادر به حذف آن نمی باشید.");
		return;
	}
	Ext.Ajax.request({
		url : me.address_prefix + "framework.data.php",
		method : "POST",
		params : {
			task : "DeleteMenu",
			MenuID : record.data.id
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
<div id="div_body" style="margin: 10px">
	<div id="div_tree"></div>
	<div id="div_grid"></div>
</div>
