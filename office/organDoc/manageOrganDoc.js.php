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

	op_menu.add({text: 'پیوست های سند سازمانی', iconCls: 'attach',
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