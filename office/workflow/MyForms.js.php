<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

MyForm.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function MyForm(){
	
	this.LoanInfoPanel = new Ext.panel.Panel({
		renderTo : this.get("LoanInfo"),
		border : 0,
		hidden : true,
		loader : {
			url : this.address_prefix + "RequestInfo.php",
			scripts : true
		}
	});
}

MyFormObject = new MyForm();

MyForm.prototype.MyForm = function(){
	if(this.get("new_pass").value != this.get("new_pass2").value)
	{
		return;
	}
}

MyForm.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='MyFormObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

MyForm.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	op_menu.add({text: 'اطلاعات آیتم',iconCls: 'info2', 
		handler : function(){ return MyFormObject.LoanInfo(); }});
	
	op_menu.add({text: 'تایید درخواست',iconCls: 'tick', 
	handler : function(){ return MyFormObject.beforeChangeStatus('CONFIRM'); }});

	op_menu.add({text: 'رد درخواست',iconCls: 'cross',
	handler : function(){ return MyFormObject.beforeChangeStatus('REJECT'); }});
	
	op_menu.add({text: 'سابقه درخواست',iconCls: 'history', 
		handler : function(){ return MyFormObject.ShowHistory(); }});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

MyForm.prototype.ChangeStatus = function(mode, ActionComment){
	
	record = this.grid.getSelectionModel().getLastSelected();
	
	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	
	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "wfm.data.php",
		params : {
			task : "ChangeStatus",
			RowID : record.data.RowID,
			mode : mode,
			ActionComment : ActionComment
		},
		
		success : function(){
			mask.hide();
			MyFormObject.grid.getStore().load();
			if(MyFormObject.commentWin)
				MyFormObject.commentWin.hide();
			MyFormObject.LoanInfoPanel.hide();
		}
	});
}

MyForm.prototype.LoanInfo = function(){
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.LoanInfoPanel.loader.load({
		params : {
			ExtTabID : this.LoanInfoPanel.getEl().id,
			RequestID : record.data.RequestID}
	});
	this.LoanInfoPanel.show();	
	return;
	
	framework.OpenPage(this.address_prefix + "RequestInfo.php", "اطلاعات درخواست وام" , {
		RequestID : record.data.RequestID
	});
	
	return;
	
	
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.LoanInfoWin.down('form').loadRecord(record);
	this.LoanInfoWin.show();
	this.LoanInfoWin.center();
}

MyForm.prototype.SaveLoanRequest = function(){
	
	mask = new Ext.LoadMask(this.LoanInfoWin, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	this.LoanInfoWin.down('form').getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'request.data.php?task=SaveLoanRequest' , 
		method: "POST",
		params : {
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		},
		
		success : function(form,action){
			mask.hide();
			MyFormObject.LoanInfoWin.hide();
			MyFormObject.grid.getStore().load();
		},
		failure : function(){
			mask.hide();
			//Ext.thisssageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
		}
	});
}

MyForm.prototype.LoanDocuments = function(ObjectType){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 720,
			height : 440,
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : "../../dms/documents.php",
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
			ObjectType : ObjectType,
			ObjectID : ObjectType == "loan" ? record.data.RequestID : record.data.LoanPersonID
		}
	});
}

MyForm.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش',
			modal : true,
			autoScroll : true,
			width: 700,
			height : 500,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "history.php",
				scripts : true
			},
			buttons : [{
					text : "بازگشت",
					iconCls : "undo",
					handler : function(){
						this.up('window').hide();
					}
				}]
		});
		Ext.getCmp(this.TabID).add(this.HistoryWin);
	}
	this.HistoryWin.show();
	this.HistoryWin.center();
	this.HistoryWin.loader.load({
		params : {
			RowID : this.grid.getSelectionModel().getLastSelected().data.RowID
		}
	});
}

</script>