<script>
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

    LegalActions.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LegalActions(){
	
	this.MainPanel = new Ext.form.Panel({
		width : 650,
		hidden : true,
		layout : {
			type : "table",
			columns : 2
		},
		applyTo : this.get("RequestInfo"),
		defaults : {
			width : 300
		},
		frame : true,
		items : [{
            xtype : "combo",
            width : 635,
            fieldLabel : "تسهیلات",
            colspan : 2,
            store : new Ext.data.SimpleStore({
                proxy: {
                    type: 'jsonp',
                    url: this.address_prefix + '../../loan/request/request.data.php?task=SelectAllRequests',
                    reader: {root: 'rows',totalProperty: 'totalCount'}
                },
                fields : ["LoanPersonID",'LoanFullname','ReqAmount',"RequestID","ReqDate", {
                    name : "fullTitle",
                    convert : function(value,record){
                        return "[ " + record.data.RequestID + " ]" + record.data.LoanFullname  + " به مبلغ " +
                            Ext.util.Format.Money(record.data.ReqAmount) + " مورخ " +
                            MiladiToShamsi(record.data.ReqDate);
                    }
                }]
            }),
            displayField : "fullTitle",
            pageSize : 20,
            valueField : "RequestID",
            name : "RequestID",
            itemId : "LoanRequestID"

        },{
            xtype : "shdatefield",
            name : "ReferDate",
            allowBlank : false,
            colspan : 2,
            fieldLabel : "تاریخ ارجاع به وکیل"
        },{
            xtype : "textfield",
            fieldLabel : "اسناد تحویل به وکیل",
            width : 600,
            colspan : 2,
            name : "DeliverDoc"
        },{
            xtype : "textfield",
            fieldLabel : "شعبه مربوطه",
            width : 600,
            colspan : 2,
            name : "branch"
        },{
            xtype : "textfield",
            fieldLabel : "شماره پرونده",
            width : 600,
            colspan : 2,
            name : "fileNum"
        },{
            xtype : "textfield",
            fieldLabel : "اقدامات حقوقی انجام شده",
            width : 600,
            colspan : 2,
            name : "actionTaken"
        },{
            xtype : "textfield",
            fieldLabel : "آخرین مستند پیگیری حقوقی",
            width : 600,
            colspan : 2,
            name : "latestDoc"
        },{
            xtype : "textfield",
            fieldLabel : "اقدامات پیش رو",
            width : 600,
            colspan : 2,
            name : "actionAhead"
        },{
            xtype : "hidden",
            name : "legalActionID"
        }],
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
			itemId : "btn_save",
			handler : function(){ LegalActionsObject.SaveActions(); }
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){ this.up('panel').hide(); }
		}]
	});
	
	
}

    LegalActions.OperationRender = function(value, p, record){

    return "<div  title='عملیات' class='setting' onclick='LegalActionsObject.OperationMenu(event);' " +
    "style='background-repeat:no-repeat;background-position:center;" +
    "cursor:pointer;width:100%;height:16'></div>";
}

    LegalActionsObject = new LegalActions();

    LegalActions.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();


		if(this.EditAccess)
		{
			op_menu.add({text: 'ویرایش اقدام',iconCls: 'edit',
			handler : function(){ return LegalActionsObject.editAction(); }});
		}
		if(this.RemoveAccess)
        {
             op_menu.add({text: 'حذف اقدام',iconCls: 'remove',
             handler : function(){ return LegalActionsObject.deleteAction(); }});
        }

    op_menu.add({text: 'پیوست',iconCls: 'attach',
    handler : function(){ return LegalActionsObject.LegalActionsDocuments('LegalActions'); }});

	op_menu.showAt(e.pageX-120, e.pageY);
}

    LegalActions.prototype.AddNew = function(){

	this.MainPanel.show();
	this.MainPanel.setReadOnly(false);

	this.MainPanel.getForm().reset();
}

    LegalActions.prototype.editAction = function(){

    this.MainPanel.down("[itemId=btn_save]").show();
    this.MainPanel.setReadOnly(false);
    record = this.grid.getSelectionModel().getLastSelected();
    this.MainPanel.show();
    /*mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
    mask.show();*/

    this.MainPanel.loadRecord(record);

    this.MainPanel.down("[name=ReferDate]").setValue(MiladiToShamsi(record.data.ReferDate) );

}

    LegalActions.prototype.SaveActions = function(){

    if(!this.MainPanel.getForm().isValid())
    return;

    mask = new Ext.LoadMask(this.MainPanel, {msg:'در حال ذخیره سازی ...'});
    mask.show();

    this.MainPanel.getForm().submit({
    clientValidation: true,
    url: this.address_prefix +'request.data.php',
    method: "POST",
    params: {
    task: "SaveLegalActions"
},
    success: function(form,action){
    mask.hide();
    LegalActionsObject.grid.getStore().load();
    LegalActionsObject.MainPanel.hide();
},
    failure: function(){
    mask.hide();
}
});
}

    LegalActions.prototype.LegalActionsDocuments = function(ObjectType){

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
    ObjectType : ObjectType,
    ObjectID : record.data.legalActionID
}
});
}

    LegalActions.prototype.deleteAction = function(){

    Ext.MessageBox.confirm("","آیا مایل به حذف قدام می باشید؟",function(btn){
        if(btn == "no")
            return;

        me = LegalActionsObject;
        record = me.grid.getSelectionModel().getLastSelected();

        mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
        mask.show();

        Ext.Ajax.request({
            methos : "post",
            url : me.address_prefix + "request.data.php",
            params : {
                task : "DeleteLegalActions",
                legalActionID : record.data.legalActionID
            },

            success : function(response){
                result = Ext.decode(response.responseText);
                mask.hide();
                if(result.success)
                {
                    LegalActionsObject.grid.getStore().load();
                    if(LegalActionsObject.commentWin)
                        LegalActionsObject.commentWin.hide();
                }
                else
                    Ext.MessageBox.alert("Error",result.data);
            }
        });
    });
}












    //.........................................................

</script>