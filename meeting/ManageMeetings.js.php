<script>
//-----------------------------
//	Date		: 1397.11
//-----------------------------

Meetings.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	MenuID : "<?= $_POST["MenuID"] ?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Meetings(){
	
	this.panel = new Ext.panel.Panel({
		renderTo : this.get("DivPanel"),
		//border : false,
		layout : "hbox",
		height : 500,
		items : [{
			xtype : "container",
			flex : 1,
			html : "<div id=div_grid width=100%></div>"
		},{
			xtype : "container",
			width : 150,
			autoScroll : true,
			height: 500,
			style : "border-left : 1px solid #99bce8;margin-left:5px",
			layout : "vbox",
			itemId : "cmp_buttons"
		}]
	});	
	
	new Ext.data.Store({
		proxy : {
			type: 'jsonp',
			url: this.address_prefix + "meeting.data.php?task=selectMeetingTypes",
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["InfoID","InfoDesc","param1","param2"],
		autoLoad : true,
		listeners : {
			load : function(){
				me = MeetingsObject;
				//..........................................................
				me.panel.down("[itemId=cmp_buttons]").removeAll();
				for(var i=0; i<this.totalCount; i++)
				{
					record = this.getAt(i);
					if(record.data.param1 != "")
					{
						btn = me.panel.down("[itemId=g_" + record.data.param1 + "]");
						if(!btn)
						{
							btn = me.panel.down("[itemId=cmp_buttons]").add({
								xtype : "button",
								width : 130,
								height : 50,
								autoScroll : true,
								scale : "large",
								style : "margin-bottom:10px",	
								itemId : "g_" + record.data.param1,
								text : record.data.param2,
								menu : []
							});
						}
						
						btn.menu.add({
							itemId : record.data.InfoID,
							text : record.data.InfoDesc,
							handler : function(){Meetings.LoadGrid(this)}
						});
					}
					else
						me.panel.down("[itemId=cmp_buttons]").add({
							xtype : "button",
							width : 130,
							height : 50,
							autoScroll : true,
							scale : "large",
							style : "margin-bottom:10px",	
							itemId : record.data.InfoID + "_" + record.data.param1,
							text : record.data.param1 != "" ? record.data.param1 : record.data.InfoDesc,
							handler : function(){Meetings.LoadGrid(this)}
						});
				}
			}
		}
	}); 
		
	this.grid = <?= $grid ?>;
	this.grid.on("itemdblclick", function(view, record){
		Meetings.OpenMeeting(record.data.MeetingID);
	});	
	this.grid.getView().getRowClass = function(record, index)
	{
		if(record.data.StatusID == "<?= MTG_STATUSID_DONE ?>")
			return "greenRow";
		if(record.data.StatusID == "<?= MTG_STATUSID_CANCLE ?>")
			return "pinkRow";
		return "";
	}	
	//this.grid.render(this.get("DivGrid"));
	
	framework.centerPanel.items.get(this.TabID).on("activate", function(){
		MeetingsObject.grid.getStore().load();
	});
}

Meetings.LoadGrid = function(btn){
	
	MeetingsObject.grid.getStore().proxy.extraParams.MeetingType = btn.itemId;
	
	MeetingsObject.grid.setTitle(btn.text);
	if(MeetingsObject.grid.rendered)
		MeetingsObject.grid.getStore().loadPage(1);
	else
		MeetingsObject.grid.render(MeetingsObject.get("div_grid"));
}
		
Meetings.OpenMeeting = function(MeetingID){
	
	framework.OpenPage("/meeting/MeetingInfo.php", "اطلاعات جلسه", 
		{
			MeetingID : MeetingID,
			MenuID : MeetingsObject.MenuID
		});
}

Meetings.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='MeetingsObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

MeetingsObject = new Meetings();

Meetings.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	if(record.data.StatusID == "<?= MTG_STATUSID_RAW ?>")
	{
		if(this.RemoveAccess)
			op_menu.add({text: 'حذف جلسه',iconCls: 'remove', 
			handler : function(){ return MeetingsObject.deleteMeeting(); }});
	}
	
	op_menu.add({text: 'اطلاعات جلسه',iconCls: 'info', 
		handler : function(){ 
			record = MeetingsObject.grid.getSelectionModel().getLastSelected();
			Meetings.OpenMeeting(record.data.MeetingID);
	}});	
	
	op_menu.add({text: 'چاپ دعوتنامه',iconCls: 'print', 
		handler : function(){ 
			record = MeetingsObject.grid.getSelectionModel().getLastSelected();
			window.open(MeetingsObject.address_prefix + "PrintAgendas.php?MeetingID=" +
				record.data.MeetingID);	
	}});
	op_menu.add({text: 'چاپ صورتجلسه',iconCls: 'print', 
		handler : function(){ 
			record = MeetingsObject.grid.getSelectionModel().getLastSelected();
			window.open(MeetingsObject.address_prefix + "PrintRecords.php?MeetingID=" +
				record.data.MeetingID);	
	}});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

Meetings.prototype.MeetingDocuments = function(){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 920, 
			height : 440,
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : "../../office/dms/documents.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.documentWin);
	}

	this.documentWin.show();
	this.documentWin.center();
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.documentWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.documentWin.getEl().id,
			ObjectType : 'meeting',
			ObjectID : record.data.MeetingID
		}
	});
}

Meetings.prototype.deleteMeeting = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف جلسه می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = MeetingsObject;
		record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "meeting.data.php",
			params : {
				task : "DeleteMeeting",
				MeetingID : record.data.MeetingID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
				{
					MeetingsObject.grid.getStore().load();
				}
				else
					Ext.MessageBox.alert("Error",result.data);
			}
		});
	});
}

Meetings.prototype.Confirm = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
		return;
	Ext.MessageBox.confirm("","آیا مایل به تایید می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = MeetingsObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			params: {
				task: 'ConfirmMeeting',
				MeetingID : record.data.MeetingID
			},
			url: me.address_prefix +'meeting.data.php',
			method: 'POST',

			success: function(response){
				mask.hide();
				var st = Ext.decode(response.responseText);
				if(st.success)
				{
					MeetingsObject.grid.getStore().load();
				}
				else
				{
					alert(st.data);
				}
			},
			failure: function(){}
		});
		
	});
}

</script>