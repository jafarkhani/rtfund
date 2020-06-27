<?php
//---------------------------
// developer:	Sh.Jafarkhani
// Date:		97.05
//---------------------------
require_once '../../header.inc.php';
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

?>
<script type="text/javascript" src="/generalUI/ext4/ux/Printer/Printer-all.js"></script>
<script type="text/javascript">
    Process.prototype = {
        TabID: '<?= $_REQUEST["ExtTabID"] ?>',
        address_prefix: "<?= $js_prefix_address ?>",

		AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
		EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
		RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
        get: function (elementID) {
            return findChild(this.TabID, elementID);
        }
    };

    function Process() {

        this.infoWin = new Ext.window.Window({
            modal: true,
            title: "اطلاعات فرایندها",
            width: 600,
            closeAction: "hide",

            items: new Ext.form.Panel({
                bodyStyle: "text-align:right;padding:5px",
                frame: true,
                fieldDefaults: {
                    labelWidth: 120
                },
                items: [{
                        xtype: "numberfield",
                        name: "ProcessID",
                        itemId: "ProcessID",
                        fieldLabel: "کد",
                        hideTrigger: true
                    }, {
                        xtype: "textfield",
                        name: "ProcessTitle",
                        itemId: "ProcessTitle",
                        fieldLabel: "عنوان",
                        anchor: "100%"
                    },{
						xtype: 'combo',
						fieldLabel: 'فرایند گردش',
						store: new Ext.data.Store({
							proxy: {
								type: 'jsonp',
								url: '/office/workflow/wfm.data.php?task=SelectAllFlows'+
									'&ObjectType=<?= FLOWID_WFM_FORM ?>',
								reader: {root: 'rows', totalProperty: 'totalCount'}
							},
							fields: ['FlowID', "FlowDesc"],
							autoLoad : true
						}),
						queryMode : 'local',
						displayField: 'FlowDesc',
						valueField: "FlowID",
						itemId : "FlowID",
						name : "FlowID",
						width: 400
					},{
                        xtype: "textfield",
                        fieldLabel: "توضیحات",
                        name: "description",
                        itemId: "description",
                        anchor: "100%"
                    }, {
                        xtype: "hidden",
                        itemId: "ParentID",
                        name: "ParentID"
                    }, {
                        xtype: "hidden",
                        itemId: "old_ProcessID",
                        name: "old_ProcessID"
                    }],
                buttons: [{
                        text: "ذخیره",
                        handler: function () {
                            ProcessObject.SaveProcess();
                        },
                        iconCls: "save"
                    }, {
                        text: "انصراف",
                        handler: function () {
                            ProcessObject.infoWin.hide();
                        },
                        iconCls: "undo"
                    }]
            })
        });
		Ext.getCmp(this.TabID).add(this.infoWin);


        this.tree = new Ext.tree.Panel({
            renderTo: this.get('tree-div'),
            frame: true,
            width: 750,
            height: 600,
            title: "فرایندها",
            plugins: [new Ext.tree.Search()],
            store: new Ext.data.TreeStore({
                root: {
                    id: "source",
                    text: "فرایندها",
                    expanded: true
                },
                proxy: {
                    type: 'ajax',
                    url: this.address_prefix + "baseInfo.data.php?task=GetProcessTree"
                }
            })
        }); 

        this.tree.getDockedItems('toolbar[dock="top"]')[0].add({
            xtype: "button",
            iconCls: "print",
            text: "چاپ",
            handler: function () {
                Ext.ux.Printer.print(ProcessObject.tree);
            }
        }, '-', {
            xtype: "button",
            iconCls: "refresh",
            text: "بازگذاری مجدد",
            handler: function () {
                ProcessObject.tree.getStore().load();
            }
        });

        this.tree.on("itemcontextmenu", function (view, record, item, index, e)
        {
            e.stopEvent();
            e.preventDefault();
            view.select(index);
			me = ProcessObject;

            this.Menu = new Ext.menu.Menu();

			if(me.AddAccess)
				this.Menu.add({
					text: 'ایجاد گره جدید',
					iconCls: 'add',
					handler: function () {
						ProcessObject.BeforeSaveProcess("new");
					}
				});
			
            if (record.data.id != "source")
            {
				if(me.EditAccess)
                    this.Menu.add({
                        text: 'ویرایش گره',
                        iconCls: 'edit',
                        handler: function () {
                            ProcessObject.BeforeSaveProcess("edit");
                        }
                    });
				if(me.RemoveAccess)
					this.Menu.add({
						text: 'حذف گره',
						iconCls: 'remove',
						handler: function () {
							ProcessObject.DeleteProcess("process");
						}
					});
				this.Menu.add({
					text: 'جدول تسهیم',
					iconCls: 'list',
					handler: function () {
						ProcessObject.OpenSharing();
					}
				});
            } 
						
            var coords = e.getXY();
            this.Menu.showAt([coords[0] - 120, coords[1]]);
        });
    }

    var ProcessObject = new Process();

    Process.prototype.BeforeSaveProcess = function (mode){
		
        var record = this.tree.getSelectionModel().getSelection()[0];

        this.infoWin.down('form').getForm().reset();
        this.infoWin.show();

        if (mode == "edit")
        {
            this.infoWin.down('form').getComponent("ParentID").setValue(record.raw.ParentID);
            this.infoWin.down('form').getComponent("ProcessTitle").setValue(record.raw.ProcessTitle);
            this.infoWin.down('form').getComponent("ProcessID").setValue(record.data.id);
            this.infoWin.down('form').getComponent("description").setValue(record.raw.description);
			this.infoWin.down('form').getComponent("FlowID").setValue(record.raw.FlowID);
            this.infoWin.down('form').getComponent("old_ProcessID").setValue(record.data.id);
            
        } else {
            this.infoWin.down('form').getComponent("ParentID").setValue(
					record.data.id == "source" ? "0" : record.data.id);
        }
    }

    Process.prototype.DeleteProcess = function () {

        var record = this.tree.getSelectionModel().getSelection()[0];

        if (record.childNodes.length != 0)
        {
            Ext.MessageBox.alert("خطا","ابتدا زیر فرایندهای این گروه را حذف کنید");
            return;
        }
        if (!confirm("آیا مایل به حذف می باشید؟"))
            return;

        mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg: 'در حال ذخيره سازي...'});
        mask.show();

        Ext.Ajax.request({
            params: {
                task: 'DeleteProcess',
                ProcessID: record.data.id
            },
            url: this.address_prefix + 'baseInfo.data.php',
            method: 'POST',
            success: function (response) {
                mask.hide();
                record.remove();
            },
            failure: function () {}
        });

    }

    Process.prototype.SaveProcess = function () {

        this.infoWin.down('form').getForm().submit({
            clientValidation: true,
            url: this.address_prefix + 'baseInfo.data.php?task=SaveProcess',
            method: "POST",

            success: function (form, action) {
         
                ProcessObject.tree.getStore().load();
                ProcessObject.infoWin.down('form').getForm().reset();
                ProcessObject.infoWin.hide();

            },
            failure: function (form, action)
            {
                alert("کد وارد شده تکراری می باشد");
            }
        });
    }

    Process.prototype.OpenSharing = function () {

        if (!this.SharingWin)
        {
            this.SharingWin = new Ext.window.Window({
                title: 'جدول تسهیم',
                modal: true,
                width: 900,
                height: 500,
				autoScroll : true,
				bodyStyle : "background-color:white",
                closeAction: "hide",
                loader: {
                    url: this.address_prefix + "sharing.php",
                    params: {
                        parentObj: "ProcessObject.SharingWin"
                    },
                    method: "POST",
                    scripts: true
                }
            });
        }
		this.SharingWin.show();
        var record = this.tree.getSelectionModel().getSelection()[0];
        this.SharingWin.loader.load({
			params: {
				ExtTabID : this.SharingWin.getEl().dom.id,
				MenuID : <?= $_POST["MenuID"] ?>,
				ProcessID: record.data.id
            }
		});
        
    }

</script>
<div style="margin: 10" align="center">
	<div id="tree-div"></div>
	<div id="NewWIN" class="x-hide-display"></div>
</div>

