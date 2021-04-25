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
		address_prefix : "<?= $js_prefix_address?>",
		ProcessTree: "",
		FlowID: <?= $_REQUEST['ParentID']?>
		

	}
		
	function ManageProcess() {
		
        this.form = "mainForm";		

        this.ProcessTree = new Ext.create('Ext.tree.TreePanel'
                , {
                    plugins: [new Ext.tree.Search()],
                    store: new Ext.data.TreeStore({
                        proxy: {
                            type: 'ajax',
                            url: "/office/workflow/ManageProcess.data.php?task=GetTreeNodes&ParentID=" + this.FlowID ,
                        }
                    })
                    , root: {
                        text: 'ارسال اولیه',
                        id: 'src',
                        expanded: true
                    }
                    , title: "فرآیند"
                    , autoScroll: true
                    , width: 300
                    , itemId: 'Treeprocess'
                    , minHeight: 700
                    , listeners: {
                        itemappend(selfi, node, index, eOpts) {
                            node.set('FlowID', node.raw.FlowID);
							node.set('ptext', node.raw.ptext);
                            node.set('active', node.raw.active);
                            node.set('iout', node.raw.iout);
                            node.set('jid', node.raw.jid);
                            node.set('poid', node.raw.poid);
							node.set('pid', node.raw.pid);
							node.set('sid', node.raw.sid);
							node.set('cusid', node.raw.cusid);
                        },
                        itemcontextmenu: function (view, record, item, index, e) {
                            e.stopEvent();
                            e.preventDefault();
                            view.select(index);
                            this.Menu = new Ext.menu.Menu();
//							if (record.data.id != 'src') {
                            this.Menu.add({
                                text: 'ایجاد گام فرآیند',
                                iconCls: 'add',
                                handler: function () {
                                    ManageProcessObj.AddStep();
                                }
                            });

                            this.Menu.add({
                                text: 'ویرایش گام فرآیند',
                                iconCls: 'edit',
                                handler: function () {
                                    ManageProcessObj.EditStep();
                                }
                            });

                            var coords = e.getXY();
                            this.Menu.showAt([coords[0] - 120, coords[1]]);
                        }
                    },
                    renderTo: document.getElementById("tree-div")
                });
				
				this.PostStore = new Ext.data.Store({
													proxy: {
														type: 'jsonp',										
														url: "/office/workflow/ManageProcess.data.php?task=PostStore" ,
														reader: {root: 'rows', totalProperty: 'totalCount'}
													},
													fields: ['PostID', 'PostName'],
													pageSize: 50
													});	
		
				this.JobStore = new Ext.data.Store({
													proxy: {
														type: 'jsonp',										
														url: "/office/workflow/ManageProcess.data.php?task=JobStore" ,
														reader: {root: 'rows', totalProperty: 'totalCount'}
													},
													fields: ['JobID', 'title'],
													pageSize: 50
													});	
										
				this.PersonStore = new Ext.data.Store({
													proxy:{
														type: 'jsonp',
														url: this.address_prefix + '../../framework/person/persons.data.php?task=selectPersons'+
															'&UserType=IsStaff',
														reader: {root: 'rows',totalProperty: 'totalCount'}
													},
													fields :  ['PersonID','fullname'],
													pageSize: 50
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
												hiddenName: "PostID",
												fieldLabel: "پست مربوطه ",
												itemId: "PostID",
												store: this.PostStore,
												width: 280,
												valueField: 'PostID',
												displayField: 'PostName'
											},
											{
												xtype: "combo",
												hiddenName: "JobID",
												fieldLabel: " شغل مربوطه",
												itemId: "JobID",
												store: this.JobStore,
												width: 280,
												valueField: 'JobID',
												displayField: 'title'
											},
											{
												xtype: "combo",
												hiddenName: "PersonID",
												fieldLabel: " شخص مربوطه",
												itemId: "PersonID",
												store: this.PersonStore,
												width: 280,
												valueField: 'PersonID',
												displayField: 'fullname'
											}
                
                
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
		
		alert(record.data.parentId) ;
		
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
		this.PostStore.load();
		this.JobStore.load();
		this.PersonStore.load();
        this.ProcessPanel.show();
		
    }

	ManageProcess.prototype.SaveUnit = function ()
    {
		
        Ext.Ajax.request({           
			url: "/office/workflow/wfm.data.php" ,
            method: "POST",
            form: this.form,
            params: {
                task: "SaveStep"
            },
            success: function (response) {
                var st = Ext.decode(response.responseText);
                if (st.success == 'true')
                {
                    Ext.MessageBox.alert("پیام", "اطلاعات به درستی ذخیره شد.");
                    ManageUnitObj.ProcessTree.getStore().load();
                    /*ManageUnitObj.MissionGrid.getStore().proxy.extraParams["StructID"] = st.data;
                    ManageUnitObj.MissionGrid.getStore().load();
                    ManageUnitObj.UnitPanel.down("[itemId=StMissionID]").show();
                    ManageUnitObj.UnitPanel.down("[itemId=StructID]").setValue(st.data);*/

                } else
                {
                    Ext.MessageBox.alert("پیام", ("خطا در ثبت اطلاعات"));
                }
            },
            failure: function () {}
        });
    }

		
	</script>