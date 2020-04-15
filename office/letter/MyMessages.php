<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.10
//-----------------------------
require_once '../header.inc.php';
require_once inc_dataGrid;
require_once inc_component;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "letter.data.php?task=SelectMyMessages", "grid_div");

$dg->addColumn("", "MessageID", "", true);
$dg->addColumn("", "SendID", "", true);
$dg->addColumn("", "IsSeen", "", true);
$dg->addColumn("", "IsDeleted", "", true);
$dg->addColumn("", "SendID", "", true);
$dg->addColumn("", "_MsgDate", "", true);
$dg->addColumn("", "MsgDate", "", true);

$col = $dg->addColumn("عنوان پیغام", "MsgTitle", "");
//$col->renderer = "MyMessage.TitleRender";
$col->width = 200;

$col = $dg->addColumn("ساعت دریافت", "_MsgTime", "");
//$col->renderer = "MyMessage.TitleRender";
$col->width = 80;
$col->align = "center";

$col = $dg->addColumn("متن پیغام", "MsgDesc", "");

$col = $dg->addColumn("فرستنده", "FromPersonName", "");
$col->renderer = "MyMessage.SenderRender";
$col->width = 150;

$col = $dg->addColumn("گیرنده", "ToPersonName", "");
$col->width = 150;

$col = $dg->addColumn("عملیات", "");
$col->renderer = "function(v,p,r){return MyMessage.OperationRender(v,p,r);}";
$col->width = 80;

$dg->addObject("this.FilterObj");
$dg->addObject("this.DeletedObj");

$dg->addButton("", "ارسال پیام", "add", "function(){MyMessageObject.NewMessage();}");

$dg->EnableGrouping = true;
$dg->DefaultGroupField = "_MsgDate";

$dg->groupHeaderTpl = "تاریخ پیغام : {[MiladiToShamsi(values.rows[0].data.MsgDate)]}";

$dg->EnableSearch = false;
$dg->emptyTextOfHiddenColumns = true;
$dg->height = 490;
$dg->title = "پیام های دریافتی";
$dg->DefaultSortField = "_MsgDate";
$dg->autoExpandColumn = "MsgDesc";
$grid = $dg->makeGrid_returnObjects();	
?>
<script>
	
MyMessage.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function MyMessage(){
	
	this.DeletedObj = Ext.button.Button({
		xtype: "button",
		text : "پیام های حذف شده", 
		iconCls : "cross",
		enableToggle : true,
		handler : function(){
			me = MyMessageObject;
			me.grid.getStore().proxy.extraParams.deleted = this.pressed ? "true" : "false";
			me.grid.getStore().load();
		}
	});
	
	this.FilterObj = Ext.button.Button({
		text: 'فیلتر لیست',
		iconCls: 'list',
		menu: {
			xtype: 'menu',
			plain: true,
			showSeparator : true,
			items: [{
				text: "پیام های دریافتی",
				checked: true,
				group: 'filter',
				handler : function(){
					me = MyMessageObject;
					me.grid.getStore().proxy.extraParams.mode = "receive";
					me.grid.columns.findObject("dataIndex","FromPersonName").show();
					me.grid.columns.findObject("dataIndex","ToPersonName").hide();
					me.grid.setTitle("پیام های دریافتی");
					me.grid.getStore().loadPage(1);
				}
			},{
				text: "پیام های ارسالی",
				group: 'filter',
				checked: true,
				handler : function(){
					me = MyMessageObject;
					me.grid.getStore().proxy.extraParams.mode = "send";
					me.grid.columns.findObject("dataIndex","FromPersonName").hide();
					me.grid.columns.findObject("dataIndex","ToPersonName").show();
					me.grid.setTitle("پیام های ارسالی");
					me.grid.getStore().loadPage(1);
				}
			}]
		}
	});
	
	this.grid = <?= $grid ?>;
	this.grid.getStore().proxy.extraParams.mode = "receive";
	this.grid.on("itemdblclick", function(view, record){
		if(MyMessageObject.grid.getStore().proxy.extraParams.mode == "send")
			return;
		if(record.data.IsSeen == "YES")
			return;
		
		MyMessageObject.SeeMessage(record.data.SendID);
	});
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.IsSeen == "NO")
			return "yellowRow";
		if(record.data.IsDeleted == "YES")
			return "pinkRow";
		return "";
	}	
	this.grid.render(this.get("DivGrid"));
	this.grid.columns.findObject("dataIndex","ToPersonName").hide();
	
	this.ReceiversStore = new Ext.data.ArrayStore({
		fields : ["fullname","PersonID"]
	}),
	
	this.newMessageWin = new Ext.window.Window({
		title : "ایجاد پیام جدید",
		width : 500,			
		height : 340,
		modal : true, 
		bodyStyle : "background-color:white;",
		closeAction : "hide",
		items : new Ext.form.Panel({
			defaults : {
				width : 450
			},
			items : [{ 
				xtype : "textfield",
				fieldLabel : "عنوان پیام",
				name : "MsgTitle",
				allowBlank : false
			},{
				xtype : "textarea",
				fieldLabel : "متن پیام",
				rows : 4,
				name : "MsgDesc",
				allowBlank : false
			},{
				xtype : "combo",
				itemId : "ToPersonID",
				store: new Ext.data.Store({
					proxy:{
						type: 'jsonp',
						url: this.address_prefix + 'letter.data.php?task=SendToMessageList',
						reader: {root: 'rows',totalProperty: 'totalCount'}
					},
					fields :  ['type','id','name']
				}),
				tpl : new Ext.XTemplate(
					'<tpl for=".">',
						'<tpl if="type == \'Group\'">',
							'<div class="x-boundlist-item" style="background-color:#fcfcb6">{name}</div>',
						'<tpl else>',
							'<div class="x-boundlist-item">{name}</div>',
						'</tpl>',						
					'</tpl>'
				),
				fieldLabel : "ارجاع به",
				displayField: 'name',
				valueField : "id"				
			},{
				xtype : "container",
				layout : "hbox",
				items :[{
					xtype : "button",
					iconCls : "add",
					text : "اضافه به لیست",
					handler : function(){
						me = MyMessageObject;
						el = me.newMessageWin.down("[itemId=ToPersonID]");
						record = el.getStore().getAt(el.getStore().find("id", el.getValue()));
						
						me.ReceiversStore.add({
							fullname : record.data.name,
							PersonID : record.data.id
						});

						el.setValue();
					}
				},{
					xtype : "button",
					iconCls : "cross",
					text : "حذف از لیست",
					handler : function(){
						me = MyMessageObject;
						comp = me.newMessageWin.down("[itemId=GroupList]");
						record = comp.getSelected()[0];
						index = me.ReceiversStore.find("PersonID",record.data.PersonID);
						me.ReceiversStore.removeAt(index);
					}
				}]
			},{
				xtype : "multiselect",
				itemId : "GroupList",
				fieldLabel : "گیرنده ها",
				store : this.ReceiversStore,
				displayField : "fullname",
				height : 80

			}]
		}),
		buttons : [{
			text : "ارسال پیام",
			iconCls : "send",
			handler : function(){ MyMessageObject.SaveMessage(); }
		}]
	});
	Ext.getCmp(this.TabID).add(this.newMessageWin);
}

MyMessage.TitleRender = function(v,p,r){
	
	p.tdAttr = "data-qtip='" + r.data.MsgDesc + "'";
	return v;
}

MyMessage.SenderRender = function(v,p,r){

	p.tdAttr = "data-qtip='تاریخ ارسال : " + MiladiToShamsi(r.data.MsgDate) + " " + 
			r.data.MsgDate.substr(10) + "'";
	return v;
}

MyMessageObject = new MyMessage();

MyMessage.prototype.NewMessage = function(){
	
	this.newMessageWin.down('form').getForm().reset();
	this.ReceiversStore.removeAll();
	this.newMessageWin.show();
}

MyMessage.prototype.SaveMessage = function(){
	
	var store_data = new Array();
	this.ReceiversStore.each(function(record){
		store_data.push(record.data.PersonID);
	});
	if(store_data.length == 0)
	{
		Ext.MessageBox.alert("Error", "اضافه حداقل یک گیرنده الزامی است");
		return;
	}
	
	if(!this.newMessageWin.down('form').getForm().isValid())
		return;
	
	mask = new Ext.LoadMask(this.grid,{msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	this.newMessageWin.down('form').submit({
		url : this.address_prefix + "letter.data.php?task=SaveMessage",
		methos : "POST",
		params : {
			receivers : JSON.stringify(store_data)
		},
		
		success : function(){
			mask.hide();
			MyMessageObject.newMessageWin.hide();
			MyMessageObject.grid.getStore().loadPage(1);
		},
		
		failure : function(form, response){
			mask.hide();
			Ext.MessageBox.alert("Error", response.result.data);
		}
	});
}

MyMessage.prototype.SeeMessage = function(SendID){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	Ext.Ajax.request({
		url : this.address_prefix + "letter.data.php?task=SeeMessage",
		method : "post",
		params : {
			SendID : SendID
		},
		
		success : function(response){
			mask.hide();
			MyMessageObject.grid.getStore().load();
		}
	});
}

MyMessage.OperationRender = function(v,p,r){
	
	if(r.data.IsDeleted == "YES")
		return "";
	return "<div  title='حذف' class='remove' " +
			" onclick='MyMessageObject.DeleteMessage();' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;float:right;width:20px;height:16'></div>" 
}

MyMessage.prototype.DeleteMessage = function(){
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال حذف ...'});
	mask.show();
	
	record = this.grid.getSelectionModel().getLastSelected();
	
	if(this.grid.getStore().proxy.extraParams.mode == "send")
		params = { MessageID : record.data.MessageID };
	else
		params = { SendID : record.data.SendID };
		
	Ext.Ajax.request({
		url : this.address_prefix + "letter.data.php?task=DeleteMessage",
		method : "post",
		params : params,
		
		success : function(response){
			mask.hide();
			MyMessageObject.grid.getStore().load();
		}
	})
}

</script>
<div id="DivGrid" style="margin:8px;"></div>
با دبل کلیک روی هر ردیف، پیام مورد نظر مشاهده شده می شود.