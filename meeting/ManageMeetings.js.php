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
	this.grid.render(this.get("DivGrid"));
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
	
	op_menu.add({text: 'مدارک جلسه',iconCls: 'attach', 
		handler : function(){ return MeetingsObject.MeetingDocuments(); }});

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
			width : 720,
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