<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.12
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;
require_once 'plan.class.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

if(!isset($_REQUEST["FormType"]))
    die();
$FormType = $_REQUEST["FormType"];

$framework = session::IsFramework();

if(!$framework)
{
    $dt = PLN_plans::SelectAll("p.PersonID=? AND p.StepID<>" . STEPID_END, array($_SESSION["USER"]["PersonID"]));
    $dt = $dt->fetchAll();
    $Mode = count($dt) == 0 ? "new" : ($dt[0]["StepID"] == STEPID_RAW ? "edit" : "list");

    $PlanID = $Mode == "new" ? "0" : $dt[0]["PlanID"];
    $PlanDesc = $Mode == "new" ? "" : $dt[0]["PlanDesc"];
    $LoanID = $Mode == "new" ? "" : $dt[0]["LoanID"];

    $accessObj->AddFlag = true;
    $accessObj->EditFlag = true;
    $accessObj->RemoveFlag = true;
}
else
{
    $PlanID = 0;
    $PlanDesc = '';
    $LoanID = 0;
    $Mode = "new";
}
//.............................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "plan.data.php?task=SelectMyPlans&FormType=" . $FormType, "grid_div");

$dg->addColumn("", "StepID", "", true);

$col = $dg->addColumn("شماره درخواست", "PlanID", "");
$col->width = 100;
$col->align = "center";

$col = $dg->addColumn("عنوان طرح", "PlanDesc", "");

$col = $dg->addColumn("تاریخ درخواست", "RegDate", GridColumn::ColumnType_date);
$col->width = 110;

$col = $dg->addColumn("وضعیت", "StepDesc", "");
$col->width = 120;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "NewPlan.OperationRender";
$col->width = 50;
$col->align = "center";

$dg->addButton("","مشاهده اطلاعات طرح", 'info2', 'function(){NewPlanObject.ShowPlanInfo()}');
$dg->addButton("","سابقه درخواست", 'history', 'function(){NewPlanObject.ShowHistory()}');

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 300;
$dg->width = 770;
$dg->title = "طرح های ارسالی";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "RegDate";
$dg->autoExpandColumn = "PlanDesc";
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <div id="div_plan"></div>
    <div id="div_grid"></div>
</center>
<script>
    NewPlan.prototype = {
        TabID : '<?= $_REQUEST["ExtTabID"]?>',
        address_prefix : "<?= $js_prefix_address?>",
        MenuID : "<?= $_POST["MenuID"] ?>",

        FormType : <?= $FormType?>,
        PlanID : <?= $PlanID ?>,
        PlanDesc : '<?= $PlanDesc ?>',
        LoanID : '<?= $LoanID ?>',
        Mode : '<?= $Mode ?>',

        AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
        EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
        RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

        framework : <?= session::IsFramework() ? "true" : "false" ?>,

        get : function(elementID){
            return findChild(this.TabID, elementID);
        }
    };

    function NewPlan(){
        /* new added */
        if (this.FormType == 1 ){
            var title = "ثبت نظارت طرح جدید";
            var itemsPlan =[




                {
                    xtype: 'radiogroup',
                    fieldLabel: 'آیا طرح ارزیابی مرتبط با طرح نظارت وجود دارد؟',
                    labelStyle: 'width:300px',
                    columns: 2,
                    itemId: 'IsGetCost',
                    beforeLabelTextTpl: required,
                    items: [
                        {
                            xtype: 'radiofield',
                            boxLabel: 'بلی',
                            name: 'IsGetCost',
                            checked: true,
                            inputValue: 'Yes',
                            listeners: {
                                change : function() {
                                    console.log('Yessssssssssssss');
                                    if(this.getValue())
                                    {
                                        console.log('GetCost');
                                        NewPlanObject.planFS.down("[name=RefPlanID]").enable();
                                    }
                                    else
                                    {
                                        console.log('Not GetCost');
                                        NewPlanObject.planFS.down("[name=RefPlanID]").disable();
                                        NewPlanObject.planFS.down("[name=RefPlanID]").setValue("0");
                                        me = NewPlanObject;

                                        me.planFS.down("[name=PersonID]").readOnly = false;
                                        me.planFS.down("[name=evaluationAskerID]").readOnly = false;
                                        me.planFS.down("[name=LetterID]").readOnly = false;
                                        me.planFS.down("[name=LoanID]").readOnly = false;
                                        me.planFS.down("[name=FinancialBroker]").readOnly = false;
                                        me.planFS.down("[name=EvaluationBroker]").readOnly = false;
                                        me.planFS.down("[name=technologyArea]").readOnly = false;
                                        me.planFS.down("[name=LoanType]").readOnly = false;
                                        me.planFS.down("[name=FacilityAmount]").readOnly = false;
                                        me.planFS.down("[name=ApprovedAmount]").readOnly = false;
                                        me.planFS.down("[name=RecordMeetingID]").readOnly = false;
                                        me.planFS.down("[name=RecordMeetingDate]").readOnly = false;
                                        me.planFS.down("[name=wage]").readOnly = false;
                                    }
                                }
                            }
                        },
                        {
                            xtype: 'radiofield',
                            boxLabel: 'خیر',
                            name: 'IsGetCost',
                            inputValue: 'NO'
                        }
                    ]
                },

                {
                    xtype : "combo",
                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + 'plan.data.php?' +
                                "task=SelectAllPlan&FormType=2",
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['PlanID','PersonID','evaluationAskerID','LetterID','LoanID','PlanDesc','FacilityAmount','ApprovedAmount',
                            'FinancialBroker','EvaluationBroker','technologyArea','RecordMeetingID','RecordMeetingDate','wage','LoanType',{
                                name : "fulltitle",
                                convert: function(v,r){
                                    return '['+r.data.PlanID + '] ' + r.data.PlanDesc; }
                            }]
                    }),
                    fieldLabel : "شماره طرح ارزیابی",
                    displayField : "fulltitle",
                    pageSize : 20,
                    width : 400,
                    /*allowBlank: false,*/
                    valueField : "PlanID",
                    name : "RefPlanID",
                    listeners : {
                        select : function(combo, records){
                            console.log(records[0].data);
                            /*NewPlanObject.planFS.down("[name=FacilityAmount]").disable();*/
                            NewPlanObject.planFS.down("[name=PlanDesc]").setValue(records[0].data['PlanDesc']);
                            NewPlanObject.planFS.down("[name=FacilityAmount]").setValue(records[0].data['FacilityAmount']);
                            NewPlanObject.planFS.down("[name=ApprovedAmount]").setValue(records[0].data['ApprovedAmount']);
                            NewPlanObject.planFS.down("[name=RecordMeetingID]").setValue(records[0].data['RecordMeetingID']);
                            NewPlanObject.planFS.down("[name=RecordMeetingDate]").setValue(records[0].data['RecordMeetingDate']);
                            NewPlanObject.planFS.down("[name=wage]").setValue(records[0].data['wage']);

                            /*NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);
                            NewPlanObject.planFS.down("[name=]").setValue(records[0].data['']);*/

                            me = NewPlanObject;


                            me.planFS.down("[name=PersonID]").getStore().load({
                                params : {PersonID: records[0].data.PersonID},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=PersonID]").setValue(this.getAt(0).data.PersonID);
                                }
                            });
                            me.planFS.down("[name=evaluationAskerID]").getStore().load({
                                params : {PersonID: records[0].data.evaluationAskerID},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=evaluationAskerID]").setValue(this.getAt(0).data.PersonID);
                                }
                            });
                            me.planFS.down("[name=LetterID]").getStore().load({
                                params : {LetterID: records[0].data.LetterID},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=LetterID]").setValue(this.getAt(0).data.LetterID);
                                }
                            });
                            me.planFS.down("[name=LoanID]").getStore().load({
                                params : {LoanID: records[0].data.LoanID},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=LoanID]").setValue(this.getAt(0).data.LoanID);
                                }
                            });
                            me.planFS.down("[name=FinancialBroker]").getStore().load({
                                params : {PersonID: records[0].data.FinancialBroker},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=FinancialBroker]").setValue(this.getAt(0).data.PersonID);
                                }
                            });
                            me.planFS.down("[name=EvaluationBroker]").getStore().load({
                                params : {PersonID: records[0].data.EvaluationBroker},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=EvaluationBroker]").setValue(this.getAt(0).data.PersonID);
                                }
                            });
                            me.planFS.down("[name=technologyArea]").getStore().load({
                                params : {technologyArea: records[0].data.technologyArea},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=technologyArea]").setValue(this.getAt(0).data.technologyArea);
                                }
                            });
                            me.planFS.down("[name=LoanType]").getStore().load({
                                params : {LoanType: records[0].data.LoanType},
                                callback : function(){
                                    if(this.getCount() > 0)
                                        me.planFS.down("[name=LoanType]").setValue(this.getAt(0).data.LoanType);
                                }
                            });

                            me.planFS.down("[name=PersonID]").readOnly = true;
                            me.planFS.down("[name=evaluationAskerID]").readOnly = true;
                            me.planFS.down("[name=LetterID]").readOnly = true;
                            me.planFS.down("[name=LoanID]").readOnly = true;
                            me.planFS.down("[name=FinancialBroker]").readOnly = true;
                            me.planFS.down("[name=EvaluationBroker]").readOnly = true;
                            me.planFS.down("[name=technologyArea]").readOnly = true;
                            me.planFS.down("[name=LoanType]").readOnly = true;


                            me.planFS.down("[name=FacilityAmount]").readOnly = true;
                            me.planFS.down("[name=ApprovedAmount]").readOnly = true;
                            me.planFS.down("[name=RecordMeetingID]").readOnly = true;
                            me.planFS.down("[name=RecordMeetingDate]").readOnly = true;
                            me.planFS.down("[name=wage]").readOnly = true;
                            /* me.MainForm.getComponent("ContractAmount").setValue(records[0].data.amount);
                             me.MainForm.getComponent("StartDate").setValue(MiladiToShamsi(records[0].data.StartDate));
                             me.MainForm.getComponent("EndDate").setValue(MiladiToShamsi(records[0].data.EndDate));

                             me.MainForm.getComponent("PersonID").readOnly = true;
                             me.MainForm.getComponent("ContractAmount").readOnly = true;
                             me.MainForm.getComponent("ContractType").readOnly = true;*/
                        }
                    }

                }


                ,{
                    xtype : "combo",
                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                "task=selectPersons&UserType=IsCustomer",
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['PersonID','fullname']
                    }),
                    fieldLabel : "مشتری",
                    displayField : "fullname",
                    pageSize : 20,
                    width : 400,
                    hidden : true,
                    valueField : "PersonID",
                    name : "PersonID"
                },{
                    xtype : "combo",
                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                "task=selectPersons&UserTypes=IsAgent,IsSupporter&EmptyRow=true",
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['PersonID','fullname']
                    }),
                    fieldLabel : "متقاضی نظارت",
                    displayField : "fullname",
                    pageSize : 20,
                    width : 400,
                    /*allowBlank: false,*/
                    valueField : "PersonID",
                    name : "evaluationAskerID",
                    listeners: {
                        change : function() {
                            var val = this.getValue();
                            console.log(this.getValue());
                            console.log('Yesingggggggggg');
                            if(val==0)
                            {
                                console.log('Truinggggggggg');
                                NewPlanObject.planFS.down("[name=LetterID]").disable();
                                NewPlanObject.planFS.down("[name=LoanID]").enable();
                                NewPlanObject.planFS.down("[name=LetterID]").setValue("");
                            }
                            else
                            {
                                console.log('Falsingggggggg');
                                NewPlanObject.planFS.down("[name=LoanID]").disable();
                                NewPlanObject.planFS.down("[name=LetterID]").enable();
                                NewPlanObject.planFS.down("[name=LoanID]").setValue("");
                            }
                        }
                    }
                },{
                    xtype : "combo",
                    name : "LetterID",
                    /*allowBlank: false,*/
                    colspan : 2,
                    width : 600,
                    store: new Ext.data.Store({
                        proxy:{
                            type: 'jsonp',
                            url:  this.address_prefix + '../../office/letter/letter.data.php?task=SelectLetter',
                            reader: {root: 'rows', totalProperty: 'totalCount'}
                        },
                        fields :  ['LetterID','LetterTitle',{
                            name : 'LetterDate',
                            convert : function(v){return MiladiToShamsi(v);}
                        },{
                            name : "fulltitle",
                            convert: function(v,r){
                                return '['+r.data.LetterID + '] ' + r.data.LetterTitle + ' [ ' + r.data.LetterDate + ' ]'; }
                        }],
                        pageSize : 20
                    }),
                    pageSize : 20,
                    fieldLabel : "شماره نامه",
                    displayField: 'fulltitle',
                    valueField : "LetterID"

                },{
                    xtype : "combo",
                    colspan : 2,
                    width : 500,

                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../loan/request/request.data.php?' +
                                "task=SelectAllRequests",
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['RequestID','ReqDetails',
                            {name : "fullDesc",	convert : function(value,record){
                                    return "[" + record.data.RequestID + "] " + record.data.ReqDetails
                                } }
                        ]
                    }),
                    displayField : "fullDesc",
                    valueField : "RequestID",
                    /*name : "Param" + record.data.ParamID,
                    fieldLabel : record.data.ParamDesc*/
                    name : "LoanID",
                    fieldLabel: 'شماره تسهیلات'
                },{
                    xtype : "textfield",
                    fieldLabel : "عنوان طرح",
                    name : "PlanDesc",
                    allowBlank: false,
                    width : 600,
                    value : this.PlanDesc
                },{
                    xtype : "currencyfield",
                    fieldLabel : "مبلغ تسهیلات درخواستی",
                    name : "FacilityAmount",
                    width : 400,
                    hideTrigger: true,
                    value : this.FacilityAmount
                },{
                    xtype : "currencyfield",
                    fieldLabel : "مبلغ تسهیلات مصوب",
                    name : "ApprovedAmount",
                    width : 400,
                    hideTrigger: true,
                    value : this.ApprovedAmount
                },{
                    xtype : "combo",
                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                "task=selectPersons&UserTypes=IsAgent,IsSupporter&EmptyRow=true",
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['PersonID','fullname']
                    }),
                    fieldLabel : "کارگزار مالی",
                    displayField : "fullname",
                    pageSize : 20,
                    width : 400,
                    valueField : "PersonID",
                    name : "FinancialBroker"
                },{
                    xtype : "combo",
                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                "task=selectPersons&UserTypes=IsAgent,IsSupporter&EmptyRow=true",
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['PersonID','fullname']
                    }),
                    fieldLabel : "کارگزار ارزیابی",
                    displayField : "fullname",
                    pageSize : 20,
                    width : 400,
                    valueField : "PersonID",
                    name : "EvaluationBroker"
                },
                {
                    xtype : "combo",
                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix +'/plan.data.php?task=selectTechnologyAreaTypes&TypeID=101',
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['TypeID','InfoID','InfoDesc'],
                        autoLoad : true
                    }),
                    width : 400,
                    fieldLabel : "حوزه فناوری ",
                    queryMode : 'local',
                    displayField : "InfoDesc",
                    valueField : "InfoID",
                    name : "technologyArea"
                },{
                    xtype:'numberfield',
                    fieldLabel: 'شماره صورتجلسه مصوبه',
                    hideTrigger : true,
                    allowBlank : false,
                    name: 'RecordMeetingID',
                    /*labelWidth: 90,*/
                    width : 400
                },{
                    xtype : "shdatefield",
                    name : "RecordMeetingDate",
                    hideTrigger : false,
                    fieldLabel : "تاریخ صورتجلسه مصوبه",
                    width : 400
                },{
                    xtype:'numberfield',
                    fieldLabel: 'کارمزد',
                    hideTrigger : true,
                    name: 'wage',
                    /*labelWidth: 90,*/
                    width : 400
                },{
                    xtype : "combo",
                    store : new Ext.data.SimpleStore({
                        proxy: {
                            type: 'jsonp',
                            url: this.address_prefix + '../../loan/loan/loan.data.php?task=GetAllLoans',
                            reader: {root: 'rows',totalProperty: 'totalCount'}
                        },
                        fields : ['LoanID','LoanDesc'],
                        autoLoad : true
                    }),
                    fieldLabel : "نوع تسهیلات",
                    queryMode : 'local',
                    width : 400,
                    displayField : "LoanDesc",
                    valueField : "LoanID",
                    name : "LoanType"
                }




                ,{
                    xtype : "button",
                    disabled : this.AddAccess ? false : true,
                    text : this.PlanID == 0 ? "ثبت طرح و تکمیل جداول اطلاعاتی" : "ویرایش جداول اطلاعاتی",
                    iconCls : "arrow_left",
                    handler : function(){ NewPlanObject.SaveNewPlan(); }
                },{
                    xtype : "hidden",
                    name : "PlanID",
                    value : this.PlanID
                },{
                    xtype : "hidden",
                    name : "FormType",
                    value : this.FormType
                }];

            if(this.Mode == "new" || this.Mode == "edit")
            {
                this.planFS = new Ext.form.FormPanel({

                    title : title,
                    width : 700,
                    layout : "vbox",
                    renderTo : this.get("div_plan"),
                    items : itemsPlan
                    /*,border : false,
                    style: {border: '0px solid #000',}*/
                });
            }
        }
        if (this.FormType == 2 ){
            var title = "ثبت ارزیابی طرح جدید";
            var itemsPlan = [{
                xtype : "combo",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix + '../../framework/person/persons.data.php?' +
                            "task=selectPersons&UserType=IsCustomer",
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['PersonID','fullname']
                }),
                fieldLabel : "مشتری",
                displayField : "fullname",
                pageSize : 20,
                width : 400,
                hidden : true,
                /*allowBlank: false,*/
                valueField : "PersonID",
                name : "PersonID"
            },{
                xtype : "textfield",
                fieldLabel : "عنوان طرح",
                name : "PlanDesc",
                /*allowBlank: false,*/
                width : 600,
                value : this.PlanDesc
            },{
                xtype : "combo",
                name : "LetterID",
                /*allowBlank: false,*/
                colspan : 2,
                width : 600,
                store: new Ext.data.Store({
                    proxy:{
                        type: 'jsonp',
                        url:  this.address_prefix + '../../office/letter/letter.data.php?task=SelectLetter',
                        reader: {root: 'rows', totalProperty: 'totalCount'}
                    },
                    fields :  ['LetterID','LetterTitle',{
                        name : 'LetterDate',
                        convert : function(v){return MiladiToShamsi(v);}
                    },{
                        name : "fulltitle",
                        convert: function(v,r){
                            return '['+r.data.LetterID + '] ' + r.data.LetterTitle + ' [ ' + r.data.LetterDate + ' ]'; }
                    }],
                    pageSize : 20
                }),
                pageSize : 20,
                fieldLabel : "شماره نامه",
                displayField: 'fulltitle',
                valueField : "LetterID"

            },{
                xtype : "combo",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix +'../../framework/person/persons.data.php?task=selectPersonInfoTypes&TypeID=97',
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['TypeID','InfoID','InfoDesc'],
                    autoLoad : true
                }),
                width : 400,
                fieldLabel : "نوع ارزیابی",
                queryMode : 'local',
                displayField : "InfoDesc",
                valueField : "InfoID",
                /*allowBlank: false,*/
                /*hidden : true,*/
                listeners: {
                    change : function() {
                        var val = this.getValue();
                        if(val==3 || val==5)
                        {
                            NewPlanObject.planFS.down("[name=FacilityAmount]").disable();
                            NewPlanObject.planFS.down("[name=FacilityAmount]").setValue("");
                        }
                        else
                        {
                            NewPlanObject.planFS.down("[name=FacilityAmount]").enable();
                        }
                    }
                },
                name : "evaluationType"
            },{
                xtype : "combo",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix +'/plan.data.php?task=selectTechnologyAreaTypes&TypeID=101',
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['TypeID','InfoID','InfoDesc'],
                    autoLoad : true
                }),
                width : 400,
                fieldLabel : "حوزه فناوری ",
                queryMode : 'local',
                displayField : "InfoDesc",
                valueField : "InfoID",
                /*allowBlank: false,*/
                name : "technologyArea"
            },{
                xtype : "currencyfield",
                /*allowBlank: false,*/
                /*listeners: {
                    render: onRenderTextField
                },*/

                fieldLabel : "مبلغ درخواستی",
                name : "FacilityAmount",
                width : 400,
                hideTrigger: true,
                value : this.FacilityAmount
            },{
                xtype : "combo",
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: this.address_prefix + '../../framework/person/persons.data.php?' +
                            "task=selectPersons&UserTypes=IsAgent,IsSupporter&EmptyRow=true",
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['PersonID','fullname']
                }),
                fieldLabel : "متقاضی ارزیابی",
                displayField : "fullname",
                pageSize : 20,
                width : 400,
                /*allowBlank: false,*/
                valueField : "PersonID",
                name : "evaluationAskerID"
            },{
                xtype : "currencyfield",
                fieldLabel : "هزینه ارزیابی دریافت شده (ریال) ",
                name : "evaluationCost",
                width : 400,
                hideTrigger: true,
                value : this.evaluationCost
            },{
                xtype : "button",
                disabled : this.AddAccess ? false : true,
                text : this.PlanID == 0 ? "ثبت طرح و تکمیل جداول اطلاعاتی" : "ویرایش جداول اطلاعاتی",
                iconCls : "arrow_left",
                handler : function(){ NewPlanObject.SaveNewPlan(); }
            },{
                xtype : "hidden",
                name : "PlanID",
                value : this.PlanID
            },{
                xtype : "hidden",
                name : "FormType",
                value : this.FormType
            }];
            if(this.Mode == "new" || this.Mode == "edit")
            {
                this.planFS = new Ext.form.FormPanel({
                    /*title : "ثبت طرح جدید",*/
                    title : title,
                    width : 700,
                    layout : "vbox",
                    renderTo : this.get("div_plan"),
                    items : itemsPlan
                });
            }
        }
        /* end new added */
        if(this.framework)
        {
            this.planFS.down("[name=PersonID]").show();
            /*this.planFS.down("[name=LoanID]").show();*/
        }
        else
        {
            this.grid = <?= $grid ?>;
            this.grid.getView().getRowClass = function(record, index)
            {
                if(record.data.StepID == <?= STEPID_REJECT ?>)
                    return "pinkRow";

                return "";
            }
            this.grid.render(this.get("div_grid"));
        }

    }

    NewPlan.OperationRender = function(v,p,record){

        var str = "";

        str += "<div  title='اطلاعات طرح' class='info2' onclick='NewPlanObject.ShowPlanInfo();' " +
            "style='background-repeat:no-repeat;background-position:center;" +
            "cursor:pointer;width:16px;height:16;float:right'></div>";

        str += "<div  title='سابقه درخواست' class='history' onclick='NewPlanObject.ShowHistory();' " +
            "style='background-repeat:no-repeat;background-position:center;" +
            "cursor:pointer;width:16px;height:16;float:right'></div>";

        return str;
    }

    NewPlanObject = new NewPlan();

    NewPlan.prototype.SaveNewPlan = function(){

        mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
        mask.show();


        this.planFS.getForm().submit({
            clientValidation: true,
            methos : "post",
            url : this.address_prefix + "plan.data.php",
            params : {
                task : "SaveNewPlan"
            },
            success : function(form,action){
                mask.hide();
                /*result = Ext.decode(response.responseText);*/
                console.log('Helooooooooooo');
                if(action.result.success)
                {
                    if(action.result.data == "LetterExist"){
                        console.log('UNIT LetterExist');
                        Ext.MessageBox.alert("Error", "شماره نامه تکراری است");
                        return;
                    }

                    if(NewPlanObject.framework)
                    {
                        console.log('UNIT Two');
                        framework.CloseTab(NewPlanObject.TabID);
                        framework.OpenPage("plan/plan/PlanInfo.php", "جداول اطلاعاتی طرح", {
                            MenuID : NewPlanObject.MenuID,
                            PlanID : action.result.data});
                        console.log('MenuID : ' + NewPlanObject.MenuID);
                        console.log('PlanID : ' + action.result.data);
                        console.log('UNIT Three');
                    }
                    else{
                        portal.OpenPage("plan/plan/PlanInfo.php", {
                            MenuID : NewPlanObject.MenuID,
                            PlanID : action.result.data});
                        console.log('UNIT Four');
                    }
                }
                else
                    Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
            },
            failure : function(form,action){
                console.log('Failureeee');
                console.log(action);
                mask.hide();
                /*if(action.result.data == "")
                    Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
                else
                    Ext.MessageBox.alert("Error",action.result.data);*/
            }
        });

    }

    NewPlan.prototype.ShowPlanInfo = function(){

        record = this.grid.getSelectionModel().getLastSelected();
        if(!record)
        {
            Ext.MessageBox.alert("","ابتدا رکورد مورد نظر را انتخاب کنید");
            return;
        }
        portal.OpenPage("/plan/plan/PlanInfo.php", {
            MenuID : this.MenuID,
            PlanID : record.data.PlanID});
    }

    NewPlan.prototype.ShowHistory = function(){

        record = this.grid.getSelectionModel().getLastSelected();
        if(!record)
        {
            Ext.MessageBox.alert("","ابتدا رکورد مورد نظر را انتخاب کنید");
            return;
        }
        if(!this.HistoryWin)
        {
            this.HistoryWin = new Ext.window.Window({
                title: 'سابقه گردش طرح',
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
                PlanID : record.data.PlanID
            }
        });
    }

</script>
