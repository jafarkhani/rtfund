<script>
//---------------------------
// programmer:	Mahdipour
// create Date:		400.02
//---------------------------   
<?php
//.................. secure section .....................
InputValidation::validate($_REQUEST["ExtTabID"], InputValidation::Pattern_EnAlphaNum);
?>
    WelfareCenters.prototype = {
        TabID: '<?= $_REQUEST["ExtTabID"] ?>',
        address_prefix: "<?= $js_prefix_address ?>",
        get: function (elementID) {
            return findChild(this.TabID, elementID);
        }
    };
    function WelfareCenters()
    {

        this.formPanel = new Ext.form.Panel({
            applyTo: this.get("mainpanel2"),
            layout: {
                type: "table",
                columns: 2
            },
            collapsible: true,
            frame: true,
            title: 'چک لیست امکان صدور ضمانت نامه',
            bodyPadding: '2 2 0',
            width: 680,
            fieldDefaults: {
                msgTarget: 'side',
                labelWidth: 150
            },
            defaultType: 'textfield',
            items: [{
                    xtype: "combo",
                    store: new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                    "task=selectPersons&UserType=IsCustomer",
                            reader: {root: 'rows', totalProperty: 'totalCount'}
                        },
                        fields: ['PersonID', 'fullname']
						}),
						fieldLabel: "نام شخص حقيقي/حقوقي",
						displayField: "fullname",
						pageSize: 20,
						width: 400,
						colspan: 2,
						valueField: "PersonID",
						name: "PersonID"
					}, {
                    xtype: "combo",
                    store: new Ext.data.Store({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + 'request.data.php?task=GetWarrentyTypes',
                            reader: {root: 'rows', totalProperty: 'totalCount'}
                        },
                        fields: ["InfoID", "InfoDesc"],
                        autoLoad: true
                    }),
						displayField: 'InfoDesc',
						valueField: "InfoID",
						name: "BailType",
						allowBlank: false,
						width: 350,
						colspan: 2,
						fieldLabel: "نوع ضمانتنامه"
					},
					{
						xtype: "textfield",
						name: "subject",
						colspan: 2,
						width: 460,
						fieldLabel: "موضوع قرارداد"
					},{
						xtype: "textfield",
						fieldLabel: "شماره نامه",
						colspan:2,
						name: "LetterNo"
					},
					{
						xtype: 'checkbox',
						boxLabel: 'مبلغ ضمانت‌نامه‌های درخواستی کمتر از 80% سرمایه صندوق است',						
						style: 'margin:10px',
						colspan: 2,
						name: 'param1',
						allowBlank: false
					},
					{
						xtype: 'checkbox',
						boxLabel: ' مجموع ضمانت‌نامه‌های فعال شرکت از 50% سرمایه صندوق کمتر است.   ',						
						style: 'margin:10px',
						colspan: 2,
						name: 'param2',
						allowBlank: false
					},
					{
						xtype: 'checkbox',
						boxLabel: 'مجموع ضمانت‌نامه‌های صادره فعال صندوق کمتر از 12 برابر سرمایه صندوق است.   ',						
						style: 'margin:10px',
						colspan: 2,
						name: 'param3',
						allowBlank: false
					},
					{
						xtype: 'checkbox',
						boxLabel: 'مجموع ضمانت‌نامه‌های فعالی که یکی از ضامنین آن هیات‌مدیره شرکت درخواست‌کننده است، کمتر از 15 میلیاردریال است. ',						
						style: 'margin:10px',
						colspan: 2,
						name: 'param4',
						allowBlank: false
					},
					{
						xtype: 'checkbox',
						boxLabel: 'تعداد ضمانت‌نامه‌های صادره برای هر ضمانت‌خواه حداکثر 30% تعداد کل ضمانت‌نامه‌های صادره باشد.   ',						
						style: 'margin:10px',
						colspan: 2,
						name: 'param5',
						allowBlank: false
					},
					{
						xtype: "hidden",
						name: 'BID'
					}],
            buttons: [{
                    text: "ذخیره",
                    iconCls: "save",
                    handler: function () {
                      
						mask = new Ext.LoadMask(WelfareCentersObject.formPanel, {msg:'در حال ذخیره ...'});
									mask.show();

									WelfareCentersObject.formPanel.getForm().submit({
										clientValidation: true,
										url : WelfareCentersObject.address_prefix + 'ManageWarrentyReq.data.php?task=SaveWR',
										method : "POST",
										params : {
											GroupID : 1//WelfareCentersObject.groupPnl.down("[name=GroupID]").getValue()
										},

										success : function(form,action){
											
											mask.hide();
											if(action.result.success)
											{
												WelfareCentersObject.grid.getStore().load();
												WelfareCentersObject.formPanel.hide();
											}												
											else
												alert("عملیات مورد نظر با شکست مواجه شد.");

											WelfareCentersObject.formPanel.hide();
										},
										failure : function(){
											mask.hide();
										}
									});



                    }
                }, {
                    text: "انصراف",
                    iconCls: "undo",
                    handler: function () {
                        WelfareCentersObject.formPanel.hide();
                    }
                }]

        });

        this.formPanel.hide();

        return;

    }

    var WelfareCentersObject = new WelfareCenters();
   
    WelfareCenters.prototype.AddWFC = function ()
    {
        this.formPanel.getForm().reset();
        this.formPanel.show();
        this.formPanel.center();
    }


    WelfareCenters.opRender = function (value, p, record)
    {
        var st = "";

        st += "<div  title='صدور معرفی نامه' class='sign' onclick='WelfareCentersObject.IssueWarrenty();' " +
                "style='float:right;background-repeat:no-repeat;background-position:center;" +
                "cursor:pointer;width:40%;height:16'></div>";

        st += "<div  title='حذف اطلاعات' class='remove' onclick='WelfareCentersObject.deleteWFC();' " +
                "style='float:left;background-repeat:no-repeat;background-position:center;" +
                "cursor:pointer;width:30%;height:16'></div>";

        st += "<div  title='ویرایش اطلاعات' class='edit' onclick='WelfareCentersObject.editWFC();' " +
                "style='float:left;background-repeat:no-repeat;background-position:center;" +
                "cursor:pointer;width:30%;height:16'></div>";


        return st;
    }

    WelfareCenters.prototype.IssueWarrenty = function ()
    {		
        var record = this.grid.getSelectionModel().getLastSelected();

        framework.OpenPage(this.address_prefix + "IssuanceForm2.php", "صدور فرم ضمانت نامه",
                {
                    BID: record.data.BID,
                    PID: record.data.PersonID,
                    BT: record.data.BailType,
					ST: record.data.subject,
					LNo:record.data.LetterNo,
					RG:record.data.RelatedOrg,
					AM:record.data.amount,
					DU:record.data.duration,
					SB:record.data.SugBail,
					CO: record.data.comments,
					EC:record.data.ExtraComments,
					KB:record.data.KnowledgeBase,
					ET:record.data.EmpType,
					ObjID : 0 
                });
    }

    WelfareCenters.prototype.editWFC = function ()
    {

        this.formPanel.show();
        var record = this.grid.getSelectionModel().getLastSelected();				       
		this.formPanel.getForm().loadRecord(record);
		this.formPanel.down('[name=PersonID]').getStore().load();				
    }

    WelfareCenters.prototype.deleteWFC = function ()
    {
        if (!confirm("آیا از حذف اطمینان دارید؟"))
            return;

        var record = this.grid.getSelectionModel().getLastSelected();

        mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg: 'در حال ذخيره سازي...'});
        mask.show();


        Ext.Ajax.request({
            url: this.address_prefix + '/ManageWarrentyReq.data.php?task=removeWFC',
            params: {
                BID: record.data.BID
            },
            method: 'POST',
            success: function (response, option) {
                mask.hide();
                var st = Ext.decode(response.responseText);
                if (st.success)
                {
                    alert("حذف با موفقیت انجام شد.");
                    WelfareCentersObject.grid.getStore().load();
                }
                else
                {
                    alert(st.data);
                }
            },
            failure: function () {
            }
        });
    }

</script>












