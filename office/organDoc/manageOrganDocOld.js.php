<script type="text/javascript">
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 99.07
//-----------------------------

    ManageOrgDocs.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix: "<?= $js_prefix_address ?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get: function (elementID) {
		return findChild(this.TabID, elementID);
	}
};

function ManageOrgDocs() {
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
    url: this.address_prefix + "organDoc.data.php?task=selectOrgDocTypes",
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    fields : ["InfoID","InfoDesc","param1","param2"],
    autoLoad : true,
    listeners : {
    load : function(){
    console.log(this.totalCount);
    me = ManageOrgDocObj;
    console.log(me);
    //..........................................................
    me.panel.down("[itemId=cmp_buttons]").removeAll();
    for(var i=0; i<this.totalCount; i++)
{
    record = this.getAt(i);
    console.log(record);
    if(record.data.param2 != "" && record.data.param2 != 0 )
{
    console.log('فرعیییییی');
    btn = me.panel.down("[itemId=g_" + record.data.param2 + "]");
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
    itemId : "g_" + record.data.param2,
    text : record.data.param3,
    menu : []
});
}

    btn.menu.add({
    itemId : record.data.InfoID,
    text : record.data.InfoDesc,
    handler : function(){ManageOrgDocs.LoadGrid(this)}
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
    text : (record.data.param2 != "" && record.data.param2 != 0) ? record.data.param2 : record.data.InfoDesc,
    handler : function(){ManageOrgDocs.LoadGrid(this)}
});
}

}
}
}
});

}

    ManageOrgDocs.LoadGrid = function(btn){
    console.log(btn);
    ManageOrgDocObj.grid.getStore().proxy.extraParams.orgDocType = btn.itemId;
    ManageOrgDocObj.grid.setTitle(btn.text);
    if(ManageOrgDocObj.grid.rendered)
    ManageOrgDocObj.grid.getStore().loadPage(1);
    else
    ManageOrgDocObj.grid.render(ManageOrgDocObj.get("div_dg"));
     }


    ManageOrgDocs.prototype.OperationRender = function () {

	return  "<div title='عملیات' class='setting' onclick='ManageOrgDocObj.OperationMenu(event);' " +
			"style='background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;height:16'></div>";
}

    ManageOrgDocs.prototype.OperationMenu = function (e)
{
	var record = this.grid.getSelectionModel().getLastSelected();
	console.log(record.data);
	var op_menu = new Ext.menu.Menu();

	/*if(record.data.StatusID == "<?/*= CNT_STEPID_RAW */?>")
	{
		op_menu.add({text: 'ارسال قرارداد',iconCls: 'refresh',
		handler : function(){ return ManageOrgDocObj.StartFlow(); }});

		if(this.EditAccess)
			op_menu.add({text: ' ویرایش', iconCls: 'edit',
			handler: function () {ManageOrgDocObj.Edit(record.data.orgDocID); }});

		if(this.RemoveAccess)
			op_menu.add({text: ' حذف', iconCls: 'remove',
			handler: function () {	ManageOrgDocObj.deleteContract(record.data.orgDocID);	}});
	}

	op_menu.add({text: ' چاپ', iconCls: 'print',
		handler: function () {
			window.open(ManageOrgDocObj.address_prefix + 'PrintContract.php?ContractID=' + record.data.orgDocID);
		}});

	op_menu.add({text: ' اطلاعات قرارداد', iconCls: 'info2',
		handler: function () {
    ManageOrgDocObj.ShowInfo(record.data.orgDocID);
		}});*/


    if(this.EditAccess)
    op_menu.add({text: ' ویرایش', iconCls: 'edit',
    handler: function () {ManageOrgDocObj.Edit(record.data.orgDocID); }});

    if(this.RemoveAccess)
    op_menu.add({text: ' حذف', iconCls: 'remove',
    handler: function () {	ManageOrgDocObj.delete(record.data.orgDocID);	}});

	op_menu.add({text: '&#1587;&#1606;&#1583; &#1587;&#1575;&#1586;&#1605;&#1575;&#1606;&#1740;', iconCls: 'attach',
		handler: function () {
    ManageOrgDocObj.orgDocAttach('orgdoc');
		}});

	op_menu.showAt(e.pageX - 120, e.pageY);
}

    ManageOrgDocs.prototype.Edit = function (orgDocID)
{        
	framework.OpenPage(this.address_prefix + 'newOrganDoc.php', "مشخصات سند سازمانی",{
        orgDocID : orgDocID
	});
}

    ManageOrgDocs.prototype.AddContract = function () {

	framework.OpenPage(this.address_prefix + "newOrganDoc.php", "مشخصات سند سازمانی");
}


    ManageOrgDocs.prototype.delete = function(){

    Ext.MessageBox.confirm("","آیا مایل به حذف درخواست می باشید؟",function(btn){
        if(btn == "no")
            return;

        me = ManageOrgDocObj;
        record = me.grid.getSelectionModel().getLastSelected();

        mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخيره سازي...'});
        mask.show();

        Ext.Ajax.request({
            methos : "post",
            url: me.address_prefix + 'organDoc.data.php?task=DeleteOrgDoc',
            params : {
                orgDocID: record.data.orgDocID
            },

            success : function(response){
                result = Ext.decode(response.responseText);
                mask.hide();
                if (!result.success) {
                    if (result.data != '')
                        Ext.MessageBox.alert('', result.data);
                    else
                        Ext.MessageBox.alert('', 'خطا در اجرای عملیات');
                    return;
                }
                ManageOrgDocObj.grid.getStore().load();
            }
        });
    });
    }

    ManageOrgDocs.prototype.orgDocAttach= function(ObjectType){

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
			ObjectID : record.data.orgDocID
		}
	});
}

    ManageOrgDocs.prototype.ShowSigns = function(){

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





    ManageOrgDocs.prototype.ShowInfo = function(){

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