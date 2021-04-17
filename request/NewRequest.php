<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.12
//-----------------------------

require_once 'header.inc.php';
require_once inc_dataGrid;
require_once 'Request.class.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

/*if(!isset($_REQUEST["FormType"]))
	die();
$FormType = $_REQUEST["FormType"];*/
$FormType = 2;

$framework = session::IsFramework();

if(!$framework)
{
	$dt = request::SelectAll("1=1");
	$dt = $dt->fetchAll();
	var_dump($dt);
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

$dg = new sadaf_datagrid("dg", $js_prefix_address . "Request.data.php?task=SelectMyPlans&FormType=" . $FormType, "grid_div");

$dg->addColumn("", "StepID", "", true);

$col = $dg->addColumn("شماره درخواست", "PlanID", "");
$col->width = 100;
$col->align = "center";

$col = $dg->addColumn("عنوان طرح", "PlanDesc", "");

$col = $dg->addColumn("تاریخ درخواست", "ReqDate", GridColumn::ColumnType_date);
$col->width = 110;

$col = $dg->addColumn("وضعیت", "StepDesc", "");
$col->width = 120;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "NewServiceRequest.OperationRender";
$col->width = 50;
$col->align = "center";

$dg->addButton("","مشاهده اطلاعات طرح", 'info2', 'function(){NewServiceRequestObject.ShowPlanInfo()}');
$dg->addButton("","سابقه درخواست", 'history', 'function(){NewServiceRequestObject.ShowHistory()}');

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
NewServiceRequest.prototype = {
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

function NewServiceRequest(){

	if(this.Mode == "new" || this.Mode == "edit")
	{
		this.planFS = new Ext.form.FormPanel({
			title : "ثبت درخواست جدید",
			width : 1000,
			layout : "vbox",
			renderTo : this.get("div_plan"),
			items : [{
                xtype : "fieldset",
                title : "اطلاعات متقاضی",
                colspan : 1,
                layout : "column",
                itemId : "RealFS01",
                width : 700,
                defaults : {labelWidth : 100},
                items : [{
                        xtype: 'radio',
                        name: 'IsRegister',
                        hideLabel: false,
                        boxLabel: 'مشتری',
                        checked : true,
                        width : 100,
                        listeners: {
                            change : function() {
                                console.log('Yesingggggggggg');
                                if(this.getValue())
                                {
                                    console.log('Truinggggggggg');
                                    NewServiceRequestObject.planFS.down("[name=PersonID]").enable();
                                    /*NewServiceRequestObject.planFS.down("[name=CustomerName]").enable();*/
                                    NewServiceRequestObject.planFS.down("[name=CustomerMob]").enable();
                                    NewServiceRequestObject.planFS.down("[name=askerName]").disable();
                                    NewServiceRequestObject.planFS.down("[name=askerName]").setValue("");
                                    NewServiceRequestObject.planFS.down("[name=askerMob]").disable();
                                    NewServiceRequestObject.planFS.down("[name=askerMob]").setValue("");
                                }
                                else
                                {
                                    console.log('Falsingggggggg');
                                    NewServiceRequestObject.planFS.down("[name=PersonID]").disable();
                                    NewServiceRequestObject.planFS.down("[name=PersonID]").setValue("");
                                    /*NewServiceRequestObject.planFS.down("[name=CustomerName]").disable();*/
                                    NewServiceRequestObject.planFS.down("[name=CustomerMob]").disable();
                                    NewServiceRequestObject.planFS.down("[name=CustomerMob]").setValue("");
                                    NewServiceRequestObject.planFS.down("[name=askerName]").enable();
                                    NewServiceRequestObject.planFS.down("[name=askerMob]").enable();
                                }
                            }
                        },
                         inputValue:'Yes'
                },/*{
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
                    fieldLabel : "نام ذینفع",
                    displayField : "fullname",
                    pageSize : 20,
                    width : 250,
                    valueField : "PersonID",
                    name : "PersonID"
                    /!*name : "CustomerName"*!/

                },{
                        xtype : "textfield",
                        fieldLabel : "شماره همراه ذینفع",
                        name : "CustomerMob",
                        itemId : "CustomerMob",
                        width : 250
                    },*/



                    {
                        xtype : "combo",
                        store : new Ext.data.SimpleStore({
                            proxy: {
                                type: 'jsonp',
                                url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                    "task=selectPersons&UserType=IsCustomer",
                                reader: {root: 'rows',totalProperty: 'totalCount'}
                            },
                            fields : ['PersonID','fullname','mobile']
                        }),
                        fieldLabel : "نام ذینفع",
                        displayField : "fullname",
                        pageSize : 20,
                        width : 250,
                        allowBlank : false,
                        beforeLabelTextTpl: required,
                        valueField : "PersonID",
                        name : "PersonID",
                        listeners : {
                            select :function  (combo, records, index, eOpts ){
                                Ext.getCmp('MobCustomer').setValue(records[0].get('mobile'))
                            }
                        }


                    },{
                        xtype : "textfield",
                        fieldLabel : "شماره همراه ذینفع",
                        name : "CustomerMob",
                        itemId : "CustomerMob",
                        id : "MobCustomer",
                        allowBlank : false,
                        beforeLabelTextTpl: required,
                        width : 250
                    },
                   {
                        xtype: 'radio',
                        name: 'IsRegister',
                        boxLabel: 'متقاضی جدید',
                        colspan : 2,
                        width : 100,
                        inputValue:'No'

                    },{
                        xtype : "textfield",
                        fieldLabel : "نام متقاضی",
                        name : "askerName",
                        itemId : "askerName",
                        allowBlank : false,
                        beforeLabelTextTpl: required,
                        disabled : true,
                        width : 250
                    },{
                        xtype : "textfield",
                        fieldLabel : "شماره همراه متقاضی",
                        name : "askerMob",
                        itemId : "askerMob",
                        allowBlank : false,
                        beforeLabelTextTpl: required,
                        disabled : true,
                        width : 250
                    }
                    ,

                    {
                        xtype: 'radiogroup',
                        fieldLabel: 'نوع مراجعه',
                        //arrange Radio Buttons into 2 columns
                        columns: 2,
                        itemId: 'IsPresent',
                        allowBlank : false,
                        beforeLabelTextTpl: required,
                        items: [
                            {
                                xtype: 'radiofield',
                                boxLabel: 'حضوری',
                                name: 'IsPresent',
                                checked: true,
                                inputValue: 'Yes'
                            },
                            {
                                xtype: 'radiofield',
                                boxLabel: 'غیرحضوری',
                                name: 'IsPresent',
                                inputValue: 'NO'
                            }
                        ]
                    }

                    ,{
                        xtype : "shdatefield",
                        /*allowBlank : false,
                        beforeLabelTextTpl: required,*/
                        fieldLabel: 'تاریخ مراجعه',
                        name: 'referalDate'
                    },{
                        xtype : "numberfield",
                        fieldLabel: 'ساعت مراجعه',
                        /*allowBlank : false,
                        beforeLabelTextTpl: required,*/
                        name: 'referalTime'
                    }/*,{
                        xtype : "timefield",
                        fieldLabel: 'ساعت مراجعه',
                        allowBlank : false,
                        beforeLabelTextTpl: required,
                        name: 'referalTime'
                    }*/,{
                        xtype : "combo",
                        colspan : 2,
                        width : 500,

                        store : new Ext.data.SimpleStore({
                            proxy: {
                                type: 'jsonp',
                                url: this.address_prefix + '../office/dms/dms.data.php?' +
                                    "task=SearchLetters",
                                reader: {root: 'rows',totalProperty: 'totalCount'}
                            },
                            fields : ['LetterID','LetterTitle',
                                {name : "fullDesc",	convert : function(value,record){
                                        return "[" + record.data.LetterID + "] " + record.data.LetterTitle
                                    } }
                            ]
                        }),
                        displayField : "fullDesc",
                        valueField : "LetterID",
                        /*name : "Param" + record.data.ParamID,
                        fieldLabel : record.data.ParamDesc*/
                        name : "LetterID",
                        fieldLabel: 'نامه اتوماسیون'
                    }

                ]
            },
                {
                    xtype : "fieldset",
                    title : "اطلاعات درخواست",
                    colspan : 2,
                    itemId : "RealFS02",
                    /*defaults : {labelWidth : 70},
                    layout : "hbox",*/
                    layout : "column",
                    width : 700,
                    defaults : {labelWidth : 100},
                    items : [
                        {
                            xtype: 'radio',
                            name: 'IsInfoORService',
                            hideLabel: false,
                            boxLabel: 'درخواست خدمت',
                            checked : true,
                            width : 100,
                            listeners: {
                                change : function() {
                                    console.log('Yessssssssssssss');
                                    if(this.getValue())
                                    {
                                        console.log('Service');
                                        NewServiceRequestObject.planFS.down("[name=serviceType]").enable();
                                        NewServiceRequestObject.planFS.down("[name=otherService]").enable();
                                        NewServiceRequestObject.planFS.down("[name=InformationDesc]").disable();
                                    }
                                    else
                                    {
                                        console.log('Info');
                                        NewServiceRequestObject.planFS.down("[name=serviceType]").disable();
                                        NewServiceRequestObject.planFS.down("[name=otherService]").disable();
                                        NewServiceRequestObject.planFS.down("[name=InformationDesc]").enable();
                                    }
                                }
                            },
                            inputValue:'Service'

                        },{
                            xtype : "combo",
                            store : new Ext.data.SimpleStore({
                                proxy: {
                                    type: 'jsonp',
                                    url: this.address_prefix +'../../framework/person/persons.data.php?task=selectPersonInfoTypes&TypeID=96&PersonID='+ this.PersonID,
                                    reader: {root: 'rows',totalProperty: 'totalCount'}
                                },
                                /*fields : ['BranchID','BranchName'],*/
                                fields : ['TypeID','InfoID','InfoDesc'],
                                autoLoad : true
                            }),
                            fieldLabel : "نوع خدمت", /*new create*/
                            queryMode : 'local',
                            /*allowBlank : false,
                            beforeLabelTextTpl: required,*/
                            width : 250,
                            displayField : "InfoDesc",
                            valueField : "InfoDesc",
                            name : "serviceType"
                        },{
                            xtype : "textarea",
                            fieldLabel : "شرح خدمت",
                            name : "otherService",
                            itemId : "otherService",
                            width : 300
                        },
                        {
                            xtype: 'radio',
                            name: 'IsInfoORService',
                            boxLabel: 'درخواست اطلاعات',
                            colspan : 2,
                            width : 100,
                            inputValue:'Info'

                        },{
                            xtype : "textarea",
                            fieldLabel : "شرح اطلاعات",
                            name : "InformationDesc",
                            itemId : "InformationDesc",
                            disabled : true,
                            width : 300
                        },

                        {
                            xtype: 'radiogroup',
                            fieldLabel: 'آیا درخواست در حوزه خدمات صندوق می باشد',
                            labelStyle: 'width:300px',
                            //arrange Radio Buttons into 2 columns
                            columns: 2,
                            /*width : 200,*/
                            /*layout:'fit',*/
                            itemId: 'IsRelated',
                            /*allowBlank : false,
                            beforeLabelTextTpl: required,*/
                            items: [
                                {
                                    xtype: 'radiofield',
                                    boxLabel: 'بلی',
                                    name: 'IsRelated',
                                    checked: true,
                                    inputValue: 'Yes',
                                    listeners: {
                                        change : function() {
                                            console.log('Yessssssssssssss');
                                            if(this.getValue())
                                            {
                                                console.log('Related');
                                                NewServiceRequestObject.planFS.down("[name=referPersonID]").enable();
                                                NewServiceRequestObject.planFS.down("[name=referDesc]").enable();
                                            }
                                            else
                                            {
                                                console.log('Not Related');
                                                NewServiceRequestObject.planFS.down("[name=referPersonID]").disable();
                                                NewServiceRequestObject.planFS.down("[name=referDesc]").disable();
                                            }
                                        }
                                    }
                                },
                                {
                                    xtype: 'radiofield',
                                    boxLabel: 'خیر',
                                    name: 'IsRelated',
                                    inputValue: 'NO'
                                }
                            ]
                        },
                        {
                            xtype : "combo",
                            name : "referPersonID",
                            store : new Ext.data.SimpleStore({
                                proxy: {
                                    type: 'jsonp',
                                    url: this.address_prefix + '../../framework/person/persons.data.php?' +
                                        "task=selectPersons&UserType=IsStaff",
                                    reader: {root: 'rows',totalProperty: 'totalCount'}
                                },
                                fields : ['PersonID','fullname']
                            }),
                            fieldLabel : "نام کارشناس ارجاعی",
                            displayField : "fullname",
                            pageSize : 20,
                            width : 250,
                            valueField : "PersonID"

                        }
                        ,{
                            xtype : "textarea",
                            fieldLabel : "شرح ارجاع",
                            name : "referDesc",
                            itemId : "referDesc",
                            width : 300
                        },
                        {
                            xtype: 'radiogroup',

                            labelStyle: 'width:200px',
                            fieldLabel: 'میزان رضایت از پاسخ دهی',
                            //arrange Radio Buttons into 4 columns
                            columns: 4,
                            itemId: 'OpinionPoll',
                            items: [
                                {
                                    xtype: 'radiofield',
                                    boxLabel: 'ضعیف',
                                    name: 'Poll',
                                    checked: true,
                                    inputValue: '1'
                                },
                                {
                                    xtype: 'radiofield',
                                    boxLabel: 'متوسط',
                                    name: 'Poll',
                                    inputValue: '2'
                                },
                                {
                                    xtype: 'radiofield',
                                    boxLabel: 'خوب',
                                    name: 'Poll',
                                    inputValue: '3'
                                },
                                {
                                    xtype: 'radiofield',
                                    boxLabel: 'عالی',
                                    name: 'Poll',
                                    inputValue: '4'
                                }
                            ]
                        }

                        ]
                }
			/*,{
				xtype : "button",
				disabled : this.AddAccess ? false : true,
				text : "ثبت درخواست",
				iconCls : "arrow_left",
				handler : function(){ NewServiceRequestObject.SaveNewServiceRequest(); }
			}*/],
            buttons:[{
                text : "ذخیره",
                iconCls : "arrow_left",
                handler : function(btn){
                    var data = this.up('form').getForm();
                    Ext.Ajax.request({
                        methos : "post",
                        url : "../request/" + "Request.data.php?task=SaveNewRequest",
                        params : data.getValues(),
                        success : function(response){
                            result = Ext.decode(response.responseText);
                            if(result.success)
                            {
                                NewServiceRequestObject.planFS.getForm().reset();
                                Ext.MessageBox.alert("Success", "عملیات مورد نظر با موفقیت شد");
                                /*framework.CloseTab(NewAlterPersonObject.TabID);*/
                                framework.OpenPage("request/RequestManage.php", "مدیریت درخواست ها", {MenuID : NewServiceRequestObject.MenuID});
                                /*framework.OpenPage("plan/plan/PlanInfo.php", "جداول اطلاعاتی طرح", {
                                    MenuID : NewAlterPersonObject.MenuID,
                                    PlanID : result.data});*/
                            }
                            else
                                Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
                        }
                    });
                }
            }]
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
			/*if(record.data.StepID == <?= STEPID_REJECT ?>)
				return "pinkRow";

			return "";*/
		}
		this.grid.render(this.get("div_grid"));
	}



}

NewServiceRequest.OperationRender = function(v,p,record){

	var str = "";

	str += "<div  title='اطلاعات طرح' class='info2' onclick='NewServiceRequestObject.ShowPlanInfo();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;height:16;float:right'></div>";

	str += "<div  title='سابقه درخواست' class='history' onclick='NewServiceRequestObject.ShowHistory();' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:16px;height:16;float:right'></div>";

	return str;
}

NewServiceRequestObject = new NewServiceRequest();

NewServiceRequest.prototype.SaveNewServiceRequest = function(){

	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();  

	Ext.Ajax.request({
		methos : "post",
		url : this.address_prefix + "Request.data.php",
		params : {
			task : "SaveNewRequest",
            PersonID : this.planFS.down("[name=PersonID]").getValue(),
            /*ServiceDesc : this.planFS.down("[name=ServiceDesc]").getValue(),*/
			/*PersonID : this.framework ? this.planFS.down("[name=PersonID]").getValue() : ""*/
		},

		success : function(response){
			mask.hide();
			result = Ext.decode(response.responseText);
			if(result.success)
			{
				if(NewServiceRequestObject.framework)
				{
					framework.CloseTab(NewServiceRequestObject.TabID);
					/*framework.OpenPage("plan/plan/PlanInfo.php", "جداول اطلاعاتی طرح", {
						MenuID : NewServiceRequestObject.MenuID,
						PlanID : result.data});*/
				}
				else{
                    /*portal.OpenPage("plan/plan/PlanInfo.php", "جداول اطلاعاتی طرح",{
                        MenuID : NewServiceRequestObject.MenuID,
                        PlanID : result.data});*/
                }

			}
			else
				Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
		}
	});
		
}

NewServiceRequest.prototype.ShowPlanInfo = function(){
	
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

NewServiceRequest.prototype.ShowHistory = function(){

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
