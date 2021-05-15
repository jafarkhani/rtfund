<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 400.01
//-----------------------------
require_once("../header.inc.php");
?>

<form enctype="multipart/form-data" id="mainForm" name ="mainForm">
	<table width="750px">
		<tr>
			<td width="40%">
				<div id="tree-div" ></div>
			</td>
			<td valign="top" style="padding-right: 5px">
				<!------------------------------------------------>
				<div id="DIV_NewUnit" >
					<div id="PNL_NewUnit">
					</div>
				</div>
				<!------------------------------------------------>
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">
    ManageProcess.prototype = {
        address_prefix: "<?= $js_prefix_address ?>",
        ProcessTree: "",
		TabID : '<?= $_REQUEST["ExtTabID"]?>',
        FlowID: <?= $_REQUEST['ParentID'] ?>,

		get : function(elementID){
			return findChild(this.TabID, elementID);
		}

    }

    function ManageProcess() {

        this.form = "mainForm";
      
        this.tree = Ext.create('Ext.tree.Panel', {
				title: "گردش فرآیند",
				store: new Ext.data.TreeStore({
					proxy: {
						type: 'ajax',						
						url: this.address_prefix + "ManageProcess.data.php?task=GetTreeNodes&ParentID=" + this.FlowID ,
					},
					root: {
						text: 'گردش فرایند',
						id: 'src',
						expanded: true
					}
				}),
				width:  350,
				height: 550,
				renderTo: this.get("tree-div")
        });
		
        this.PostStore = new Ext.data.Store({
            proxy: {
                type: 'jsonp',
                url: "/office/workflow/ManageProcess.data.php?task=PostStore",
                reader: {root: 'rows', totalProperty: 'totalCount'}
            },
            fields: ['PostID', 'PostName'],
            pageSize: 50
        });

        this.JobStore = new Ext.data.Store({
            proxy: {
                type: 'jsonp',
                url: "/office/workflow/ManageProcess.data.php?task=JobStore",
                reader: {root: 'rows', totalProperty: 'totalCount'}
            },
            fields: ['JobID', 'title'],
            pageSize: 50
        });

        this.PersonStore = new Ext.data.Store({
            proxy: {
                type: 'jsonp',
                url: this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons' +
                        '&UserType=IsStaff',
                reader: {root: 'rows', totalProperty: 'totalCount'}
            },
            fields: ['PersonID', 'fullname'],
            pageSize: 50
        });
		
		this.tree.on("itemcontextmenu", function(view, record, item, index, e)
		{
			e.stopEvent();
			e.preventDefault();
			view.select(index);

			this.Menu = new Ext.menu.Menu();

			this.Menu.add({
				text: 'ایجاد زیر پوشه',
				iconCls: 'add',
				handler : function(){ManageProcessObj.BeforeSaveFolder(false);}
			});

			if(record.data.id != "src")
			{
				this.Menu.add({
					text: 'ویرایش عنوان',
					iconCls: 'edit',
					handler : function(){ManageProcessObj.BeforeSaveFolder(true);}
				});

				this.Menu.add({
					text: 'حذف پوشه',
					iconCls: 'remove',
					handler : function(){ManageProcessObj.DeleteFolder();}
				});
			}

			var coords = e.getXY();
			this.Menu.showAt([coords[0]-120, coords[1]]);
		});

        this.ProcessPanel = new Ext.form.Panel({
            frame: true,
            title: 'گام فرآیند',
            bodyPadding: '5 5 0',
            width: 430,
            labelWidth: 140,
            renderTo: document.getElementById("DIV_NewUnit"),
            hidden: true,
            items: [
                {
                    xtype: "hidden",
                    name: "StepRowID",
                    itemId: "StepRowID"
                },
                {
                    xtype: "hidden",
                    name: "FlowID",
                    itemId: "FlowID"
                },
                {
                    xtype: "hidden",
                    name: "StepID",
                    itemId: "StepID"
                },
                {
                    xtype: "hidden",
                    name: "StepParentID",
                    itemId: "StepParentID"
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'عنوان مرحله',
                    name: 'StepDesc',
                    itemId: 'StepDesc',
                    width: 400
                },
                {
                    xtype: "combo",
                    name: "PostID",
                    fieldLabel: "پست مربوطه ",
                    itemId: "PostID",
                    store: this.PostStore,
                    width: 280,
                    valueField: 'PostID',
                    displayField: 'PostName'
                },
                {
                    xtype: "combo",
                    name: "JobID",
                    fieldLabel: " شغل مربوطه",
                    itemId: "JobID",
                    store: this.JobStore,
                    width: 280,
                    valueField: 'JobID',
                    displayField: 'title'
                },
                {
                    xtype: "combo",
                    name: "PersonID",
                    fieldLabel: " شخص مربوطه",
                    itemId: "PersonID",
                    store: this.PersonStore,
                    width: 280,
                    valueField: 'PersonID',
                    displayField: 'fullname'
                }/*,
                {
                    xtype: "treecombo",
                    selectChildren: true,
                    canSelectFolders: true,
                    multiselect: false,
                    hiddenName: "ReturnStep",
                    itemId: "ReturnStep",
                    colspan: 2,
                    width: 420,
                    fieldLabel: "چرخه فرآیند",
                    store: this.TreeStore
                }*/


            ],
            buttons: [
                {
                    text: "ذخیره",
                    iconCls: "save",
                    handler: function () {
                        ManageProcessObj.SaveUnit();
                    }
                },
                {
                    text: "بازگشت",
                    iconCls: "undo",
                    handler: function () {
                        ManageProcessObj.ProcessPanel.hide();
                    }
                }

            ]
        });


    }

    var ManageProcessObj = new ManageProcess();

	
	ManageProcess.prototype.BeforeSaveFolder = function(EditMode){
		
		this.ProcessPanel.hide();
		var record = this.tree.getSelectionModel().getSelection()[0];
		ManageProcessObj.ProcessPanel.getForm().reset();
		this.ProcessPanel.down("[itemId=FlowID]").setValue(this.FlowID);		
		this.ProcessPanel.down("[itemId=StepID]").setValue('');
		this.ProcessPanel.down("[itemId=StepParentID]").setValue('');
		this.ProcessPanel.show();
		
		this.ProcessPanel.down("[itemId=StepParentID]").setValue(record.data.id);

		if(EditMode)
		{	
			this.ProcessPanel.down("[itemId=StepRowID]").setValue(record.data.id);
			this.ProcessPanel.down("[itemId=StepDesc]").setValue(record.data.text);
			this.ProcessPanel.down("[itemId=StepParentID]").setValue(record.data.parentId);
			
			Ext.Ajax.request({
                url: this.address_prefix + "ManageProcess.data.php?task=GetRec",
                method: "POST",
                params : {
					STID : record.data.id
				},
                success: function(response){
					var st = Ext.decode(response.responseText);
					if(st.success){
						
						
						alert(st.data.PID); 
						alert(st.data.POID); 
						alert(st.data.JID); 
						
						ManageProcessObj.ProcessPanel.down("[itemId=PersonID]").setValue(st.data.PID);
						ManageProcessObj.PersonStore.load();	
						
						ManageProcessObj.ProcessPanel.down("[itemId=PostID]").setValue(st.data.POID);
						ManageProcessObj.PostStore.load();
						
						ManageProcessObj.ProcessPanel.down("[itemId=JobID]").setValue(st.data.JID);
						ManageProcessObj.JobStore.load();	
					}
				},
				failure: function(){}
				
            });
			
		}

	}

    ManageProcess.prototype.AddStep = function ()
    {

        this.ProcessPanel.hide();
        this.ProcessPanel.down("[itemId=FlowID]").setValue(this.FlowID);
        this.ProcessPanel.down("[itemId=StepID]").setValue('');
        this.ProcessPanel.down("[itemId=StepParentID]").setValue('');

        var record = this.ProcessTree.getSelectionModel().getSelection()[0];

        if (record.data.id != 'src')
        {
            this.ProcessPanel.down("[itemId=StepParentID]").setValue(record.data.id);

        } else
        {
            this.ProcessPanel.down("[itemId=StepParentID]").setValue(record.data.id);
        }

        this.ProcessPanel.show();
        ManageProcessObj.ProcessPanel.getForm().reset();
    }

    ManageProcess.prototype.EditStep = function ()
    {
        this.ProcessPanel.hide();
        var record = this.ProcessTree.getSelectionModel().getSelection()[0];

        if (record.data.id != 'src')
        {
            this.ProcessPanel.down("[itemId=StepRowID]").setValue(record.data.id);
        } else
        {
            this.ProcessPanel.down("[itemId=StepRowID]").setValue(record.data.id);
        }

        if (record.data.parentId != 'src')
        {
            this.ProcessPanel.down("[itemId=StepParentID]").setValue(record.data.parentId);
        }
        else
        {
            this.ProcessPanel.down("[itemId=StepParentID]").setValue(record.data.parentId);
        }

        this.ProcessPanel.down("[itemId=FlowID]").setValue(this.FlowID);
        this.ProcessPanel.down("[itemId=StepDesc]").setValue(record.data.text);
        this.ProcessPanel.down("[itemId=PersonID]").setValue(record.data.pid);
        this.ProcessPanel.down("[itemId=StepID]").setValue(record.data.sid);
        this.ProcessPanel.down("[itemId=PostID]").setValue(record.data.poid);
        this.ProcessPanel.down("[itemId=JobID]").setValue(record.data.jid);
        this.ProcessPanel.down("[itemId=ReturnStep]").setValue('372');
        this.PostStore.load();
        this.JobStore.load();
        this.PersonStore.load();
        this.ProcessPanel.show();

    }

    ManageProcess.prototype.SaveUnit = function ()
    {
		
		mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
		mask.show();

		ManageProcessObj.ProcessPanel.getForm().submit({
			clientValidation: true,
			url: this.address_prefix + 'wfm.data.php?task=SaveStep',
			method : "POST",
			success : function(form,action){                

				me = ManageProcessObj;				
				StepRowID = me.ProcessPanel.down("[itemId=StepRowID]").getValue();
				mode = StepRowID == "" ? "new" : "edit";

				if(mode == "new")
				{					
					ParentID = me.ProcessPanel.down("[itemId=StepParentID]").getValue();
					Parent = ParentID == "src" ? me.tree.getRootNode() :
												 me.tree.getRootNode().findChild("id",ParentID,true);
					Parent.set('leaf', false);
					Parent.appendChild({
						id : action.result.data,
						text :  me.ProcessPanel.down("[itemId=StepDesc]").getValue(),
						leaf : true
					});  
					Parent.expand();
				}
				else
				{
					node = me.tree.getRootNode().findChild("id", StepRowID, true);
					node.set('text', me.ProcessPanel.down("[itemId=StepDesc]").getValue());
				}

				me.ProcessPanel.getForm().reset();
				me.ProcessPanel.hide();

				mask.hide();

			},
			failure : function(form,action)
			{
				Ext.MessageBox.alert("Error","عملیات مورد نظر با شکست مواجه شد");
				mask.hide();
			}
		});
	
    }


</script>
