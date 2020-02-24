<?php
//---------------------------
// developer:	Sh.Jafarkhani
// Date:		97.05
//---------------------------
require_once '../../header.inc.php'; 

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

?>
<script type="text/javascript">
Event.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix: "<?= $js_prefix_address ?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
};

function Event() {

	this.infoPanel = new Ext.form.Panel({
		applyTo: this.get("NewPnl"),
		title: "اطلاعات رویداد",
		bodyStyle: "text-align:right;padding:5px",
		frame: true,
		hidden: true,
		width: 500,
		defaults : {
			width : 480
		},
		items: [{
				xtype: "hidden",
				name: "EventID",
				itemId: "EventID"
			},{
				xtype: "textfield",
				name: "ordering",
				itemId: "ordering",
				fieldLabel: "ترتیب",
				hideTrigger: true
			}, {
				xtype: "textfield",
				name: "EventTitle",
				itemId: "EventTitle",
				fieldLabel: "عنوان"
			}, {
				xtype: "textfield",
				name: "EventType", 
				itemId: "EventType",
				fieldLabel: "نوع رویداد"
			}, {
				xtype: "textfield",
				name: "EventType2",
				itemId: "EventType2",
				fieldLabel: "EventType2"
			},{
				xtype: "textfield",
				name: "EventType3",
				itemId: "EventType3",
				fieldLabel: "EventType3"
			}, {
				xtype: "hidden",
				itemId: "ParentID",
				name: "ParentID"
			}, {
				xtype: "hidden",
				itemId: "old_EventID",
				name: "old_EventID"
			}],
		buttons: [{
				text: "ذخیره",
				handler: function () {
					EventObject.SaveEvent();
				},
				iconCls: "save"
			}, {
				text: "انصراف",
				handler: function () {
					EventObject.infoPanel.hide();
				},
				iconCls: "undo"
			}]
	});

	this.tree = new Ext.tree.Panel({
		renderTo: this.get('tree-div'),
		frame: true,
		width: 600,
		height: 600,
		title: "رویداد های مالی",
		plugins: [new Ext.tree.Search()],
		store: new Ext.data.TreeStore({
			root: {
				id: "source",
				text: "گروه های رویدادها",
				expanded: true
			},
			proxy: {
				type: 'ajax',
				url: this.address_prefix + "baseinfo.data.php?task=GetEventsTree"
			}
		})
	});

	this.tree.getDockedItems('toolbar[dock="top"]')[0].add({
			xtype: "button",
			iconCls: "print",
			text: "چاپ",
			handler: function () {
				Ext.ux.Printer.print(EventObject.tree);
			}
		}, '-', {
			xtype: "button",
			iconCls: "refresh",
			text: "بازگذاری مجدد",
			handler: function () {
				EventObject.tree.getStore().load();
			}
		},{
			xtype: "button",
			iconCls: "cross",
			text: "خطای ثبت های اتومات",
			handler: function () {
				window.open('../../storage/loanDaily.html');
			}
		}
	);

	this.tree.on("itemcontextmenu", function (view, record, item, index, e)
	{
		e.stopEvent();
		e.preventDefault();
		view.select(index);
		me = EventObject;

		this.Menu = new Ext.menu.Menu();

		if (record.data.id == "source")
		{   
			if(me.AddAccess)
				this.Menu.add({
				text: 'ایجاد گروه رویداد',
						iconCls: 'add',
						handler: function(){ EventObject.BeforeSaveEvent("new", "group"); }
				});
		}
		else if(record.parentNode.data.id == "source" ){
			if(me.AddAccess)
				this.Menu.add({
				text: 'ایجاد گروه رویداد',
						iconCls: 'add',
						handler: function(){ EventObject.BeforeSaveEvent("new", "group"); }
				});
			if(me.EditAccess)
				this.Menu.add({
					text: 'ویرایش گروه',
						iconCls: 'edit',
						handler: function(){ EventObject.BeforeSaveEvent("edit", "group"); }

				});
			if(me.RemoveAccess)
				this.Menu.add({
					text: 'حذف گروه',
						iconCls: 'remove',
						handler: function(){ EventObject.DeleteEvent("group"); }

				});
		}
		else if(record.parentNode.parentNode.data.id == "source")
		{
			if(me.EditAccess)
				this.Menu.add({
					text: 'ویرایش گروه',
						iconCls: 'edit',
						handler: function(){ EventObject.BeforeSaveEvent("edit", "group"); }

				});
			if(me.RemoveAccess)
				this.Menu.add({
					text: 'حذف گروه',
						iconCls: 'remove',
						handler: function(){ EventObject.DeleteEvent("group"); }

				});
			if(me.AddAccess)
				this.Menu.add({
					text: 'ایجاد رویداد',
						iconCls: 'add',
						handler: function(){ EventObject.BeforeSaveEvent("new", "event"); }
				});
		}
		else
		{
			if(me.EditAccess)
			{
				this.Menu.add({
					text: 'ویرایش رویداد',
					iconCls: 'edit',
					handler: function () {
						EventObject.BeforeSaveEvent("edit", "event");
					}

				});
				this.Menu.add({
					text: 'کپی ردیف های رویداد از رویداد دیگر',
					iconCls: 'copy',
					handler: function () {
						EventObject.ShowCopyWin();
					}
				});;
			}
			if(me.RemoveAccess)
				this.Menu.add({
					text: 'حذف رویداد',
					iconCls: 'remove',
					handler: function () {
						EventObject.DeleteEvent("event");
					}

				});
			this.Menu.add({
				text: 'ردیف های رویداد',
				iconCls: 'list',
				handler: function () {
					var record = EventObject.tree.getSelectionModel().getSelection()[0];
					framework.OpenPage(EventObject.address_prefix + "EventRows.php?EventID="
							+ record.data.id, "ردیف های رویداد" + record.data.id,
							{
								MenuID : "<?= $_POST["MenuID"] ?>",
								EventID: record.data.id,
								EventTitle: record.data.text

							});
				}
			}); 
		}

		var coords = e.getXY();
		this.Menu.showAt([coords[0] - 120, coords[1]]);
	});

	this.tree.on("itemdblclick", function (view, record, item, index, e){
		if(record.parentNode.parentNode.parentNode.data.id == "source")
		{
			framework.OpenPage(EventObject.address_prefix + "EventRows.php?EventID="
					+ record.data.id, "ردیف های رویداد" + record.data.id,
					{
						MenuID : "<?= $_POST["MenuID"] ?>",
						EventID: record.data.id,
						EventTitle: record.data.text

					});
		}
	});

}

var EventObject = new Event();

Event.prototype.BeforeSaveEvent = function (mode, obj){

	var record = this.tree.getSelectionModel().getSelection()[0];

	this.infoPanel.getForm().reset();
	this.infoPanel.show();

	if (mode == "edit")
	{
		this.infoPanel.getComponent("ParentID").setValue(record.raw.ParentID);

		this.infoPanel.getComponent("EventTitle").setValue(record.raw.EventTitle);
		this.infoPanel.getComponent("EventID").setValue(record.data.id);
		this.infoPanel.getComponent("old_EventID").setValue(record.data.id);
		this.infoPanel.getComponent("ordering").setValue(record.raw.ordering);
		this.infoPanel.getComponent("EventType").setValue(record.raw.EventType);
		this.infoPanel.getComponent("EventType2").setValue(record.raw.EventType2);
	} else {
		this.infoPanel.getComponent("ParentID").setValue(record.data.id == "source" ? 0 : record.data.id);
	}
}

Event.prototype.DeleteEvent = function () {

	var record = this.tree.getSelectionModel().getSelection()[0];

	if (record.childNodes.length != 0)
	{
		alert("ابتدا رویدادهای این گروه را حذف کنید");
		return;
	}
	if (!confirm("آیا مایل به حذف می باشید؟"))
		return;

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg: 'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		params: {
			task: 'DeleteEvent',
			EventID: record.data.id
		},
		url: this.address_prefix + 'baseinfo.data.php',
		method: 'POST',
		success: function (response) {
			mask.hide();
			record.remove();
		},
		failure: function () {}
	});

}

Event.prototype.SaveEvent = function () {

	this.infoPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'baseinfo.data.php?task=SaveEvent',
		method: "POST",

		success: function (form, action) {

			mode = EventObject.infoPanel.getComponent("old_EventID").getValue() == "" ? "new" : "edit";
			group = EventObject.infoPanel.getComponent("ParentID").getValue() == "" ? "group" : "event";

			EventID = EventObject.infoPanel.getComponent("old_EventID").getValue();
			ParentID = EventObject.infoPanel.getComponent("ParentID").getValue();
			if (ParentID != "0")
				Parent = EventObject.tree.getRootNode().findChild("id", ParentID, true);
			else
				Parent = EventObject.tree.getRootNode();

			if (mode == "new")
			{
				Parent.set('leaf', false);
				Parent.appendChild({
					id: action.result.data,
					text: "[" + EventObject.infoPanel.getComponent("ordering").getValue()
							+ "] " + EventObject.infoPanel.getComponent("EventTitle").getValue(),
					leaf: true
				});
			} else
			{
				EventObject.tree.getRootNode().findChild("id", EventID, true).
						set('text', "[" + EventObject.infoPanel.getComponent("ordering").getValue() + "] " 
						+ EventObject.infoPanel.getComponent("EventTitle").getValue());
				EventObject.tree.getRootNode().findChild("id", EventID, true).
						set('id', EventObject.infoPanel.getComponent("EventID").getValue());
			}

			EventObject.infoPanel.getForm().reset();
			EventObject.infoPanel.hide();

		},
		failure: function (form, action)
		{
			alert("کد وارد شده تکراری می باشد");
		}
	});
}

Event.prototype.ShowCopyWin = function(){
	
	if(!this.CopyWin)
	{
		this.CopyWin = new Ext.window.Window({
			width : 700,
			title : "کپی ردیف های رویداد",
			height : 100,
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "combo",
				fieldLabel : "رویداد مبدا",
				labelWidth : 150,
				width : 680,
				store: new Ext.data.Store({
					fields:["EventID","EventTitle",{
							name : "fullDesc",
							convert : function(value,record){
								return "[ " + record.data.EventID + " ] " + record.data.EventTitle
							}				
						}],
					proxy: {
						type: 'jsonp',
						url: this.address_prefix + 'baseinfo.data.php?task=GetAllEvents',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					}
				}),
				typeAhead: false,
				name : "Src_EventID",
				valueField : "EventID",
				displayField : "fullDesc"
			}],
			buttons :[{
				text : "کپی ردیف های رویداد",
				iconCls : "copy",
				handler : function(){ EventObject.CopyEventRows(0);}
			},{
				text : "کپی وارونه ردیف های رویداد",
				iconCls : "copy",
				handler : function(){ EventObject.CopyEventRows(1);}
			},{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.CopyWin);
	}
	
	this.CopyWin.show();
	this.CopyWin.center();
}

Event.prototype.CopyEventRows = function(mode){
	
	var record = this.tree.getSelectionModel().getSelection()[0];
	
	mask = new Ext.LoadMask(this.CopyWin, {msg:'در حال ذخيره سازي...'});
	mask.show();
	
	Ext.Ajax.request({
		url: this.address_prefix + 'baseinfo.data.php?task=CopyEventRows',
		method: "POST",
		params : {
			Src_EventID : this.CopyWin.down("[name=Src_EventID]").getValue(),
			Dst_EventID : record.data.id,
			mode : mode
		},
		success: function (response) {
			mask.hide();
			result = Ext.decode(response.responseText);
			EventObject.CopyWin.hide();
			if(result.success)
				Ext.MessageBox.alert("خطا", result.data + " ردیف با موفقیت اضافه گردید" );
			else
			{
				if(result.data == "")
					Ext.MessageBox.alert("خطا", "عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("خطا", result.data);
			}
		},
		failure: function ()
		{
			mask.hide();			
		}
	});
}

</script>
<table width="750px" style="margin:10px">
	<tr>
		<td valign="top" width="40%"><div id="tree-div"></div></td>
		<td valign="top" style="padding-right : 20px">
			<div id="NewPnl" class="x-hide-display"></div>
		</td>
	</tr>
</table>
