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
                allowBlank: false,
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
                fieldLabel : "متقاضی ارزیابی",
                displayField : "fullname",
                pageSize : 20,
                width : 400,
                allowBlank: false,
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
                allowBlank: false,
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
                allowBlank: false,
                valueField : "PersonID",
                name : "PersonID"
            },{
                xtype : "textfield",
                fieldLabel : "عنوان طرح",
                name : "PlanDesc",
                allowBlank: false,
                width : 600,
                value : this.PlanDesc
            },{
                xtype : "combo",
                name : "LetterID",
                allowBlank: false,
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
                allowBlank: false,
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
                xtype : "currencyfield",
                allowBlank: false,
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
                allowBlank: false,
                valueField : "PersonID",
                name : "evaluationAskerID"
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
        }
        /* end new added */

        if(this.Mode == "new" || this.Mode == "edit")
        {
            this.planFS = new Ext.form.FormPanel({
                title : "ثبت طرح جدید",
                width : 700,
                layout : "vbox",
                renderTo : this.get("div_plan"),
                items : itemsPlan
            });
        }

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
                console.log(action.result.data);
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
                        console.log('UNIT Three');
                    }
                    else
                        portal.OpenPage("plan/plan/PlanInfo.php", {
                            MenuID : NewPlanObject.MenuID,
                            PlanID : action.result.data});
                }
                else
                    Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
            },
            failure : function(form,action){
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
