<script type="text/javascript">
//---------------------------
// programmer:	Jafarkhani
// create Date:	88.07
//---------------------------

ManageGroup.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ManageGroup()
{
	this.formPanel = new Ext.form.Panel({
            
            applyTo: this.get("FormDIV"),
            width: 450,            
            title: "فرم ایجاد گروه",
            frame: true,
            bodyPadding: '0 0 0',
            collapsible: false,
            fieldDefaults: {labelWidth: 100},
            layout: {
                type: "table",
                columns: 2,
            },
            items: [
                {
                    xtype: "textfield",
                    name: "GroupTitle",
                    itemId: "GroupTitle",                                 
                    fieldLabel: 'عنوان گروه',
                    allowBlank: false,
                    colspan:2,
                    width:400
                },
                {
                    xtype: "filefield",
                    name: "FileType",
                    fieldLabel: "پیوست فایل",                    
                    itemId: 'FileType',
                    width: 400,                   
                    colspan:2
                },
                {
                    xtype: "container",
                    items: [
                        {
                            xtype: "button",
                            iconCls: "down",
                            itemId: "DownPic",
                            width: 10,
                            handler: function () {
                                var GID = ManageGroupObject.grid.getSelectionModel().getLastSelected().data.GID;
                                window.open("/messenger/ShowFile.php?source=GrpPic&GID=" + GID );
                            }
                        }
                    ]
                },
                {
                    xtype: "hidden",
                    name: "GID",
                    itemId: "GID"
                }],
            buttons: [{
                    text: "ذخیره",
                    iconCls: "save",
                    handler: function () {
                              
                             ManageGroupObject.formPanel.getForm().submit({
                                    clientValidation: true,
                                    url:  ManageGroupObject.address_prefix + 'ManageGroup.data.php?task=SaveGrp&' + ManageGroupObject.GID ,
                                    method : "POST",                                   
                                    params: {
                                            GID: ManageGroupObject.GID
                                    },
                                    success : function(form,action){
                                            if(action.result.success)
                                            {
                                                ManageGroupObject.formPanel.hide();
                                                ManageGroupObject.grid.getStore().load();
                                            }
                                            else
                                            {
                                                    alert(action.result.data);
                                            }
                                    }
                            });

                            ManageGroupObject.formPanel.hide();
                    }
                }, {
                    text: "انصراف",
                    iconCls: "undo",
                    handler: function () {
                       ManageGroupObject.formPanel.hide();
                    }
                }]
        });
        this.formPanel.hide();
        this.formPanel.down('[itemId=DownPic]').hide();
        
	this.afterLoad();
}

ManageGroup.opRender = function(value, p, record)
{
    
    return   "<div  title='حذف اطلاعات' class='remove' onclick='ManageGroupObject.deleteGrp();' " +
			 "style='float:left;background-repeat:no-repeat;background-position:center;" +
			 "cursor:pointer;width:40%;height:16'></div>" +
             "<div  title='ویرایش' class='edit' onclick='ManageGroupObject.EditGrp();' " +
			 "style='float:left;background-repeat:no-repeat;background-position:center;" +
			 "cursor:pointer;width:30%;height:16'></div>" +
             "<div  title='مشاهده' class='view' onclick='ManageGroupObject.ShowMember();' " +
			 "style='float:left;background-repeat:no-repeat;background-position:center;" +
			 "cursor:pointer;width:30%;height:16'></div>"    ;
}

ManageGroup.opDelRender = function(value, p, record)
{

    return   "<div  title='حذف اطلاعات' class='remove' onclick='ManageGroupObject.deleteMember();' " +
			 "style='float:left;background-repeat:no-repeat;background-position:center;" +
			 "cursor:pointer;width:50%;height:16'></div>" ;
}


ManageGroup.prototype.AddGrp = function()
{    
    this.formPanel.show();    
}

ManageGroup.prototype.EditGrp = function()
{    
    this.formPanel.show();
    var record = this.grid.getSelectionModel().getLastSelected();
    ManageGroupObject.formPanel.down("[itemId=GID]").setValue(record.data.GID);
    ManageGroupObject.formPanel.down("[itemId=GroupTitle]").setValue(record.data.GroupTitle);
    this.formPanel.down('[itemId=DownPic]').show();
    
}
/*
ManageGroup.prototype.SaveGrp = function(store,record)
{
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'ManageGroup.data.php?task=SaveGrp',
		method: 'POST',
		params: {
			record : Ext.encode(record.data)
		},

		success: function(response,option){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				ManageGroupObject.grid.getStore().load();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}
	});
}
*/
ManageGroup.prototype.AddMember = function()
{
    var modelClass = this.branchGrid.getStore().model;
	var record = new modelClass({
        GID:this.branchGrid.getStore().proxy.extraParams["GID"],
		MID: null,
		PersonID: null

	});
    
	this.branchGrid.plugins[0].cancelEdit();
	this.branchGrid.getStore().insert(0, record);
	this.branchGrid.plugins[0].startEdit(0, 0);
}

ManageGroup.prototype.SaveMember = function(store,record)
{   
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'ManageGroup.data.php?task=SaveMember',
		method: 'POST',
		params: {
			record : Ext.encode(record.data)
		},

		success: function(response,option){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				ManageGroupObject.branchGrid.getStore().load();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}
	});
}

ManageGroup.prototype.deleteGrp = function()
{
	if(!confirm("آیا از حذف اطمینان دارید؟"))
		return;
	
	var record = this.grid.getSelectionModel().getLastSelected();
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'ManageGroup.data.php?task=removeGrp',
		params:{
			GID: record.data.GID
		},
		method: 'POST',
        success: function(response,option){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				alert("حذف با موفقیت انجام شد.");
				ManageGroupObject.grid.getStore().load();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}		
	});
}

ManageGroup.prototype.ShowMember = function()
{
    this.formPanel.hide();
	var record = this.grid.getSelectionModel().getLastSelected();
    this.grid.collapse();
    
    this.LoadPersonInfo() ; 
    
    this.branchGrid.getStore().proxy.extraParams["GID"] =  record.data.GID;
    this.branchGrid.getStore().proxy.extraParams["GroupTitle"] =  record.data.GroupTitle;
    ManageGroupObject.branchGrid.title = "اعضای گروه&nbsp;" + record.data.GroupTitle ; 
    if(this.branchGrid.rendered == true)
        this.branchGrid.getStore().load();
    else
        this.branchGrid.render("div_branch");

}

ManageGroup.prototype.deleteMember = function()
{
	if(!confirm("آیا از حذف اطمینان دارید؟"))
		return;

	var record = this.branchGrid.getSelectionModel().getLastSelected();

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();


	Ext.Ajax.request({
		url: this.address_prefix + 'ManageGroup.data.php?task=removeMember',
		params:{
			MID: record.data.MID,
            GID: record.data.GID
		},
		method: 'POST',
        success: function(response,option){
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				alert("حذف با موفقیت انجام شد.");
				ManageGroupObject.branchGrid.getStore().load();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}
	});
}

</script>