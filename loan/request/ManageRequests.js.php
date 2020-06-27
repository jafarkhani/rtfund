<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

ManageRequest.prototype = {
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

function ManageRequest(){
	
	this.FilterObj = Ext.button.Button({
		text: 'فیلتر لیست',
		iconCls: 'list',
		menu: {
			xtype: 'menu',
			plain: true,
			showSeparator : true,
			items: [{
				text: "کلیه درخواست ها",
				checked: true,
				group: 'filter',
				handler : function(){
					me = ManageRequestObject;
					me.grid.getStore().proxy.extraParams["IsEnded"] = "";
					me.grid.getStore().loadPage(1);
				}
			},{
				text: "درخواست های جاری",
				group: 'filter',
				checked: true,
				handler : function(){
					me = ManageRequestObject;
					me.grid.getStore().proxy.extraParams["IsEnded"] = "NO";
					me.grid.getStore().loadPage(1);
				}
			},{
				text: "درخواست های خاتمه یافته",
				group: 'filter',
				checked: true,
				handler : function(){
					me = ManageRequestObject;
					me.grid.getStore().proxy.extraParams["IsEnded"] = "YES";
					me.grid.getStore().loadPage(1);
				}
			},{
				text: "درخواست های تایید شده",
				group: 'filter',
				checked: true,
				handler : function(){
					me = ManageRequestObject;
					me.grid.getStore().proxy.extraParams["IsConfirm"] = "YES";
					me.grid.getStore().loadPage(1);
				}
			},{
				text: "درخواست های تایید نشده",
				group: 'filter',
				checked: true,
				handler : function(){
					me = ManageRequestObject;
					me.grid.getStore().proxy.extraParams["IsConfirm"] = "NO";
					me.grid.getStore().loadPage(1);
				}
			}]
		}
	});
	
	this.grid = <?= $grid ?>;
	this.grid.on("itemdblclick", function(view, record){
		framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
		{
			RequestID : record.data.RequestID,
			MenuID : '<?= $_POST["MenuID"] ?>'
		});
	});	
	this.grid.getView().getRowClass = function(record, index)
		{
			if(record.data.IsEnded == "YES")
				return "greenRow";
			if(record.data.IsConfirm == "YES")
				return "violetRow";
			if(record.data.StatusID == "<?= LON_REQ_STATUS_REJECT ?>")
				return "pinkRow";
			return "";
		}	
	this.grid.render(this.get("DivGrid"));

	this.rejectedParts = <?= $rejectedParts ?>;
	if(this.rejectedParts.length > 0)
	{
		this.RejectPanel = new Ext.panel.Panel({
			title : "وام هایی که شرایط پرداخت آنها تایید نشده است",
			renderTo : this.get("rejectedDIV"),
			frame : true,			
			width : 400,
			height : 140,
			autoScroll : true,
			items :[{
				xtype: 'menu',
				border : false,
				itemId : "cmp_menu",
				bodyStyle : "text-align:right;background-color:white !important;",
				floating: false
			}]			
		}); 
		for(i=0; i<this.rejectedParts.length; i++)
			this.RejectPanel.down("[itemId=cmp_menu]").add({
				text : "[ " + this.rejectedParts[i][0] + " ] " + this.rejectedParts[i][2],
				icon: '/generalUI/ext4/resources/themes/icons/arrow-left.gif',
				handler : Ext.bind(ManageRequest.OpenRequest, this,[this.rejectedParts[i][0]])
			});
	}
}

ManageRequest.OpenRequest = function(RequestID){
	
	framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
		{
			RequestID : RequestID,
			MenuID : '<?= $_POST["MenuID"] ?>'
		});
}

ManageRequest.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='ManageRequestObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

ManageRequestObject = new ManageRequest();

ManageRequest.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	if(record.data.StatusID == "1")
	{
		if(this.RemoveAccess)
			op_menu.add({text: 'حذف درخواست',iconCls: 'remove', 
			handler : function(){ return ManageRequestObject.deleteRequest(); }});
	}
	
	op_menu.add({text: 'اطلاعات وام',iconCls: 'info', 
		handler : function(){ 
			record = ManageRequestObject.grid.getSelectionModel().getLastSelected();
			framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
			{
				RequestID : record.data.RequestID,
				MenuID : ManageRequestObject.MenuID
			});
	}});	
	
	op_menu.add({text: 'مدارک وام',iconCls: 'attach', 
		handler : function(){ return ManageRequestObject.LoanDocuments('loan'); }});

	op_menu.add({text: 'مدارک وام گیرنده',iconCls: 'attach', 
		handler : function(){ return ManageRequestObject.LoanDocuments('person'); }});
	
	op_menu.add({text: 'ضامنین/وثیقه گذاران',iconCls: 'list', 
		handler : function(){ return ManageRequestObject.LoanGuarantors(); }});
	
	op_menu.add({text: 'سابقه درخواست',iconCls: 'history', 
		handler : function(){ return ManageRequestObject.ShowHistory(); }});
	
	op_menu.add({text: 'رویدادها',iconCls: 'task', 
		handler : function(){ return ManageRequestObject.ShowEvents(); }});
	
	op_menu.add({text: 'چک لیست',iconCls: 'check', 
		handler : function(){ return ManageRequestObject.ShowCheckList(); }});
	
	op_menu.add({
				text: 'اقساط',
				itemId : "cmp_installments",
				iconCls: 'list',
				handler : function(){ return ManageRequestObject.LoadInstallments(); }
			},{
				text: 'پرداخت های مشتری',
				itemId : "cmp_BackPays",
				iconCls: 'list',
				handler : function(){ return ManageRequestObject.LoadBackPays(); }
			},{
				text: 'مراحل پرداخت',
				itemId : "cmp_payments",
				iconCls: 'epay',
				handler : function(){ return ManageRequestObject.LoadPayments(); }
			},{
				text : 'هزینه ها',
				iconCls : "account",
				itemId : "cmp_costs",
				handler : function(){ ManageRequestObject.ShowCosts(); }
			},{
				text : 'پیگیری مطالبات',
				iconCls : "process",
				itemId : "cmp_follows",
				handler : function(){ ManageRequestObject.ShowFollows(); }
			});
	
	
	op_menu.add({text: 'چاپ رسید اقساط',iconCls: 'print', 
		handler : function(){ 
			record = ManageRequestObject.grid.getSelectionModel().getLastSelected();
			window.open(ManageRequestObject.address_prefix + "PrintLoanDocs.php?type=checks&RequestID=" +
				record.data.RequestID);	
	}});
	op_menu.add({text: 'چاپ رسید تضامین',iconCls: 'print', 
		handler : function(){ 
			record = ManageRequestObject.grid.getSelectionModel().getLastSelected();
			window.open(ManageRequestObject.address_prefix + "PrintLoanDocs.php?type=tazmin&RequestID=" +
				record.data.RequestID);	
	}});
	
	op_menu.add({text: 'چاپ کاردکس',iconCls: 'print', 
		handler : function(){ 
			record = ManageRequestObject.grid.getSelectionModel().getLastSelected();
			window.open(ManageRequestObject.address_prefix + "../report/LoanSummary.php?RequestID=" +
				record.data.RequestID);	
	}});
	
	op_menu.showAt(e.pageX-120, e.pageY);
}

ManageRequest.prototype.LoanDocuments = function(ObjectType){

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
			ObjectType : ObjectType,
			ObjectID : ObjectType == "loan" ? record.data.RequestID : record.data.LoanPersonID
		}
	});
}

ManageRequest.prototype.LoanGuarantors = function(){

	if(!this.GuarantorWin)
	{
		this.GuarantorWin = new Ext.window.Window({
			width : 750,
			height : 600,
			autoScroll : true,
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "guarantors.php",
				scripts : true
			},
			buttons :[{
				text : "بازگشت",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.GuarantorWin);
	}

	this.GuarantorWin.show();
	this.GuarantorWin.center();
	
	var record = this.grid.getSelectionModel().getLastSelected();
	this.GuarantorWin.loader.load({
		scripts : true,
		params : {
			ExtTabID : this.GuarantorWin.getEl().id,
			RequestID : record.data.RequestID
		}
	});
}

ManageRequest.prototype.ShowHistory = function(){

	if(!this.HistoryWin)
	{
		this.HistoryWin = new Ext.window.Window({
			title: 'سابقه گردش درخواست',
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
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		}
	});
}

ManageRequest.prototype.deleteRequest = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف درخواست می باشید؟",function(btn){
		if(btn == "no")
			return;
		
		me = ManageRequestObject;
		record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
		mask.show();  

		Ext.Ajax.request({
			methos : "post",
			url : me.address_prefix + "request.data.php",
			params : {
				task : "DeleteRequest",
				RequestID : record.data.RequestID
			},

			success : function(response){
				result = Ext.decode(response.responseText);
				mask.hide();
				if(result.success)
				{
					ManageRequestObject.grid.getStore().load();
					if(ManageRequestObject.commentWin)
						ManageRequestObject.commentWin.hide();
				}
				else
					Ext.MessageBox.alert("Error",result.data);
			}
		});
	});
	
	
}

ManageRequest.prototype.Confirm = function()
{
	var record = this.grid.getSelectionModel().getLastSelected();
	if(!record)
		return;
	Ext.MessageBox.confirm("","آیا مایل به تایید می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ManageRequestObject;
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();

		Ext.Ajax.request({
			params: {
				task: 'ConfirmRequest',
				RequestID : record.data.RequestID
			},
			url: me.address_prefix +'request.data.php',
			method: 'POST',

			success: function(response){
				mask.hide();
				var st = Ext.decode(response.responseText);
				if(st.success)
				{
					ManageRequestObject.grid.getStore().load();
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

ManageRequest.prototype.ShowEvents = function(){

	if(!this.EventsWin)
	{   
		this.EventsWin = new Ext.window.Window({
			title: 'رویدادهای مرتبط با طرح',
			modal : true,
			autoScroll : true,
			width: 1000,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "events.php",
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
		Ext.getCmp(this.TabID).add(this.EventsWin);
	}
	this.EventsWin.show();
	this.EventsWin.center();	
	record = this.grid.getSelectionModel().getLastSelected();
	this.EventsWin.loader.load({
		params : {
			ExtTabID : this.EventsWin.getEl().id,
			RequestID : record.data.RequestID,
			MenuID : this.MenuID
		}
	});
}

//.........................................................

ManageRequest.prototype.LoadInstallments = function(){
	
	if(!this.InstallmentsWin)
	{
		this.InstallmentsWin = new Ext.window.Window({
			width : 1000,
			title : "لیست اقساط",
			height : 410,
			modal : true,
			loader : {
				url : this.address_prefix + "installments.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide"
		});
		
		Ext.getCmp(this.TabID).add(this.InstallmentsWin);
	}
	this.InstallmentsWin.show();
	this.InstallmentsWin.center();
	
	this.InstallmentsWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.InstallmentsWin.getEl().id,
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		}
	});
}

ManageRequest.prototype.LoadBackPays = function(){
	
	if(!this.PayWin)
	{
		this.PayWin = new Ext.window.Window({
			width : 1000,
			title : "لیست پرداخت های مشتری",
			height : 410,
			autoScroll : true,
			modal : true,
			loader : {
				url : this.address_prefix + "BackPays.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide"
		});
		
		Ext.getCmp(this.TabID).add(this.PayWin);
	}
	this.PayWin.show();
	this.PayWin.center();
	
	this.PayWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.PayWin.getEl().id,
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		}
	});
	
	this.PayWin.show();
	this.PayWin.center();
}

ManageRequest.prototype.LoadPayments = function(){
	
	if(!this.PaymentWin)
	{
		this.PaymentWin = new Ext.window.Window({
			width : 1000 ,
			title : "مراحل پرداخت",
			height : 305,
			modal : true,
			loader : {
				url : this.address_prefix + "payments.php",
				method : "post",
				scripts : true
			},
			closeAction : "hide"
		});
		
		Ext.getCmp(this.TabID).add(this.PaymentWin);
	}
	this.PaymentWin.show();
	this.PaymentWin.center();
	
	this.PaymentWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.PaymentWin.getEl().id,
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		}
	});
}

ManageRequest.prototype.ShowCosts = function(){

	if(!this.CostsWin)
	{
		this.CostsWin = new Ext.window.Window({
			title: 'هزینه های وام',
			modal : true,
			autoScroll : true,
			width: 600,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "costs.php",
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
		Ext.getCmp(this.TabID).add(this.CostsWin);
	}
	this.CostsWin.show();
	this.CostsWin.center();	
	this.CostsWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.CostsWin.getEl().id,
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		}
	});
}

ManageRequest.prototype.ShowFollows = function(){

	if(!this.FollowWin)
	{
		this.FollowWin = new Ext.window.Window({
			title: 'پیگیری مطالبات',
			modal : true,
			autoScroll : true,
			width: 1000,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "follows.php",
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
		Ext.getCmp(this.TabID).add(this.FollowWin);
	}
	this.FollowWin.show();
	this.FollowWin.center();	
	this.FollowWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.FollowWin.getEl().id,
			RequestID : this.grid.getSelectionModel().getLastSelected().data.RequestID
		}
	});
}

ManageRequest.prototype.ShowCheckList = function(){

	if(!this.CostsWin)
	{
		this.CostsWin = new Ext.window.Window({
			title: 'چک لیست',
			modal : true,
			autoScroll : true,
			width: 600,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : "baseInfo/checkValues.php",
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
		Ext.getCmp(this.TabID).add(this.CostsWin);
	}
	this.CostsWin.show();
	this.CostsWin.center();	
	this.CostsWin.loader.load({
		params : {
			MenuID : this.MenuID,
			ExtTabID : this.CostsWin.getEl().id,
			SourceID : this.grid.getSelectionModel().getLastSelected().data.RequestID,
			SourceType : <?= SOURCETYPE_LOAN ?>
		}
	});
}

</script>