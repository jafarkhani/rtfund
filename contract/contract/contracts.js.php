<script type="text/javascript">
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 95.01
//-----------------------------

ManageContracts.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix: "<?= $js_prefix_address ?>",
	
	ContractStatus_Raw: <?= CNTconfig::ContractStatus_Raw ?>,
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
}

function ManageContracts() {
    this.panel = new Ext.panel.Panel({
        renderTo : this.get("DivPanel"),
        //border : false,
        layout : "hbox",
        height : 500,
        items : [{
            xtype : "container",
            flex : 1,
            html : "<div id=div_dg width=100%></div>"    /*new Edit*/
            /*html : "<div id=div_grid width=100%></div>"*/   /*new Comment*/
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
    url: this.address_prefix + "contract.data.php?task=SelectContractTypes",
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    fields : ["InfoID","InfoDesc","param1","param2"],
    autoLoad : true,
    listeners : {
    load : function(){
    console.log(this.totalCount);
    me = ManageContractsObj;
    //..........................................................
    me.panel.down("[itemId=cmp_buttons]").removeAll();
    for(var i=0; i<this.totalCount; i++)
{
    record = this.getAt(i);
    console.log(record);
    if(record.data.param1 != "" && record.data.param1 != 0 )
{
    console.log('فرعیییییی');
    btn = me.panel.down("[itemId=g_" + record.data.param1 + "]");
    if(!btn)
{
    console.log('دکمه وجود ندارد');
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
    handler : function(){ManageContracts.LoadGrid(this)}
});
}
    else{
    console.log('اصلیییییی');
    me.panel.down("[itemId=cmp_buttons]").add({
    xtype : "button",
    width : 130,
    height : 50,
    autoScroll : true,
    scale : "large",
    style : "margin-bottom:10px",
    itemId : record.data.InfoID,       /*New Edited*/
    /*itemId : record.data.InfoID + "_" + record.data.param1,*/          /*New Commented*/
    text : (record.data.param1 != "" && record.data.param1 != 0) ? record.data.param1 : record.data.InfoDesc,
    handler : function(){ManageContracts.LoadGrid(this)}
});
}

}
}
}
});

}

    ManageContracts.LoadGrid = function(btn){
    console.log(btn);
    ManageContractsObj.grid.getStore().proxy.extraParams.ContractType = btn.itemId;
    ManageContractsObj.grid.setTitle(btn.text);
    if(ManageContractsObj.grid.rendered)
    ManageContractsObj.grid.getStore().loadPage(1);
    else
    ManageContractsObj.grid.render(ManageContractsObj.get("div_dg"));
     }


ManageContracts.prototype.OperationRender = function () {

	return  "<div title='عملیات' class='setting' onclick='ManageContractsObj.OperationMenu(event);' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;height:16'></div>";
}

ManageContracts.prototype.OperationMenu = function (e)
{
	var record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();

	if(record.data.StatusID == "<?= CNT_STEPID_RAW ?>")
	{
		op_menu.add({text: 'ارسال قرارداد',iconCls: 'refresh',
		handler : function(){ return ManageContractsObj.StartFlow(); }});
		
		if(this.EditAccess)
			op_menu.add({text: ' ویرایش', iconCls: 'edit',
			handler: function () {ManageContractsObj.Edit(record.data.ContractID, record.data.TemplateID); }});

		if(this.RemoveAccess)
			op_menu.add({text: ' حذف', iconCls: 'remove',
			handler: function () {	ManageContractsObj.RemoveContract(record.data.ContractID);	}});				
	}	
	if(record.data.StepID == "1" && record.data.ActionType == "REJECT")
	{
		op_menu.add({text: 'ارسال قرارداد',iconCls: 'refresh',
		handler : function(){ return ManageContractsObj.StartFlow(); }});
		
		if(this.EditAccess)
			op_menu.add({text: ' ویرایش', iconCls: 'edit',
			handler: function () {ManageContractsObj.Edit(record.data.ContractID, record.data.TemplateID); }});
	}
	
	op_menu.add({text: ' چاپ', iconCls: 'print',
		handler: function () {
			window.open(ManageContractsObj.address_prefix + 'PrintContract.php?ContractID=' + record.data.ContractID);
		}});
	
	op_menu.add({text: ' اطلاعات قرارداد', iconCls: 'info2',
		handler: function () {
			ManageContractsObj.ShowInfo(record.data.ContractID, record.data.TemplateID);
		}});

	op_menu.add({text: 'پیوست های قرارداد', iconCls: 'attach',
		handler: function () {
			ManageContractsObj.ContractDocuments('contract');
		}});
	op_menu.add({text: 'امضاهای قرارداد', iconCls: 'sign',
		handler: function () {
			ManageContractsObj.ShowSigns();
		}});
	
	op_menu.add({text: 'سابقه قرارداد',iconCls: 'history', 
		handler : function(){ return ManageContractsObj.ShowHistory(); }});

	op_menu.showAt(e.pageX - 120, e.pageY);
}

ManageContracts.prototype.Edit = function (ContractID, TemplateID)
{        
	framework.OpenPage(this.address_prefix + 'NewContract.php', "مشخصات قرارداد",{
		ContractID : ContractID ,
		TemplateID : TemplateID
	});
}

ManageContracts.prototype.AddContract = function () {

	framework.OpenPage(this.address_prefix + "NewContract.php", "مشخصات قرارداد");
}

ManageContracts.prototype.RemoveContract = function () {

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
	url: this.address_prefix + 'contract.data.php?task=DeleteContract',
	params: {                
		ContractID: this.grid.getSelectionModel().getLastSelected().data.ContractID            
	},
	method: 'POST',
	success: function (res) {
		mask.hide();
		var sd = Ext.decode(res.responseText);
		if (!sd.success) {
			if (sd.data != '')
				Ext.MessageBox.alert('', sd.data); 
			else
				Ext.MessageBox.alert('', 'خطا در اجرای عملیات');
			return;
		}
		ManageContractsObj.grid.getStore().load();
	}
});
}

ManageContracts.prototype.ContractDocuments = function(ObjectType){

	if(!this.documentWin)
	{
		this.documentWin = new Ext.window.Window({
			width : 720,
			height : 440,
			modal : true,
			bodyStyle : "background-color:white;padding: 0 10px 0 10px",
			closeAction : "hide",
			loader : {
				url : "/office/dms/documents.php",
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
			ObjectID : record.data.ContractID
		}
	});
}

ManageContracts.prototype.ShowSigns = function(){

	if(!this.SignsWin)
	{
		this.SignsWin = new Ext.window.Window({
			title: 'امضاهای قرارداد',
			modal : true,
			autoScroll : true,
			width: 700,
			height : 400,
			bodyStyle : "background-color:white",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "signs.php",
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
		Ext.getCmp(this.TabID).add(this.SignsWin);
	}
	var record = this.grid.getSelectionModel().getLastSelected();
	this.SignsWin.show();
	this.SignsWin.center();	
	this.SignsWin.loader.load({
		params : {
			ExtTabID : this.SignsWin.getEl().id,
			ContractID : record.data.ContractID
		}
	});
}

ManageContracts.prototype.StartFlow = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به ارسال قرارداد می باشید؟",function(btn){
		
		if(btn == "no")
			return;
		
		me = ManageContractsObj;
		var record = me.grid.getSelectionModel().getLastSelected();
	
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال ذخیره سازی ...'});
		mask.show();
		
		Ext.Ajax.request({
			url: me.address_prefix + 'contract.data.php',
			method: "POST",
			params: {
				task: "StartFlow",
				ContractID : record.data.ContractID
			},
			success: function(response){
				mask.hide();
				ManageContractsObj.grid.getStore().load();
			}
		});
	});
}

ManageContracts.prototype.ShowHistory = function(){

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
				url : "/office/workflow/history.php",
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
			FlowID : <?= FLOWID_CONTRACT ?>,
			ObjectID : this.grid.getSelectionModel().getLastSelected().data.ContractID
		}
	});
}

ManageContracts.prototype.ShowInfo = function(){

	if(!this.InfoWin)
	{
		this.InfoWin = new Ext.window.Window({
			title: "مشخصات قرارداد",
			modal : true,
			autoScroll : true,
			width: 830,
			height : 560,
			closeAction : "hide",
			loader : {
				url : this.address_prefix + 'NewContract.php',
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
		Ext.getCmp(this.TabID).add(this.InfoWin);
	}
	record = this.grid.getSelectionModel().getLastSelected();
	this.InfoWin.show();
	this.InfoWin.center();
	this.InfoWin.loader.load({
		params : {
			ExtTabID : this.InfoWin.getEl().id,
			ContractID : record.data.ContractID ,
			TemplateID : record.data.TemplateID,
			readOnly : true
		}
	});
}
</script>