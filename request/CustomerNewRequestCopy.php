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

$Portal = session::IsPortal();

if(!$Portal)
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
	Portal : <?= session::IsPortal() ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function NewServiceRequest(){

	if(this.Mode == "new" || this.Mode == "edit")
	{
		var test = this.planFS = new Ext.form.FormPanel({
			title : "ثبت درخواست جدید",
			width : 750,
			layout : "vbox",
			renderTo : this.get("div_plan"),
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

                        },
			            {
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
                            width : 350,
                            displayField : "InfoDesc",
                            valueField : "InfoDesc",
                            name : "serviceType"
                        },{
                            xtype : "textarea",
                            fieldLabel : "شرح خدمت",
                            name : "otherService",
                            itemId : "otherService",
                            width : 350
                        }
                        ,
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
                            width : 350
                        },{
                            xtype : "filefield",
                            /*xtype : "textarea",*/
                            name : "LetterPic",
                            fieldLabel : "&#1578;&#1589;&#1608;&#1740;&#1585; &#1606;&#1575;&#1605;&#1607;"
                        }
			/*,{
				xtype : "button",
				disabled : this.AddAccess ? false : true,
				text : "ثبت درخواست",
				iconCls : "arrow_left",
				handler : function(){ NewServiceRequestObject.SaveNewServiceRequest(); }
			}*/],
               /*buttons:[{
                text : "ذخیره",
                iconCls : "arrow_left",
                handler : function(btn){
                    var data = this.up('form').getForm();
                    Ext.Ajax.request({
                        methos : "post",
                        url : "../request/" + "Request.data.php?task=SaveCustomerRequest",
                        params : data.getValues(),
                        success : function(response){
                            result = Ext.decode(response.responseText);
                            if(result.success)
                            {
                                NewServiceRequestObject.planFS.getForm().reset();
                                Ext.MessageBox.alert("Success", "عملیات مورد نظر با موفقیت شد");
                                /*framework.OpenPage("plan/plan/PlanInfo.php", "جداول اطلاعاتی طرح", {
                                    MenuID : NewAlterPersonObject.MenuID,
                                    PlanID : result.data});*/
                            }
                            else
                                Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
                        }
                    });
                }
            }]*/
            
		});
		test.body.applyStyles({ 
                
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
		/*this.grid.render(this.get("div_grid"));*/
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

NewServiceRequest.prototype.SaveData = function() {

    /*var data = this.planFS('form').getForm();
    console.log(data.getValues());
    console.log(data);*/
    this.planFS.getForm().submit({
        methos : "post",
        IsUpload : true,
        url : "../request/" + "Request.data.php?task=SaveCustomerRequest",
        /*params : data.getValues(),*/
        success : function(form,result){
            resultant = Ext.decode(result.response.responseText);
            console.log(result.response);
            if(resultant.success)
            {
                NewServiceRequestObject.planFS.getForm().reset();
                Ext.MessageBox.alert("","&#1575;&#1591;&#1604;&#1575;&#1593;&#1575;&#1578; &#1576;&#1575; &#1605;&#1608;&#1601;&#1602;&#1740;&#1578; &#1584;&#1582;&#1740;&#1585;&#1607; &#1588;&#1583;");
                /*document.getElementById("img_LetterPic").src = document.getElementById("img_LetterPic").src + "&" + new Date().getTime();*/
            }
            else
                Ext.MessageBox.alert("Error", "&#1593;&#1605;&#1604;&#1740;&#1575;&#1578; &#1605;&#1608;&#1585;&#1583; &#1606;&#1592;&#1585; &#1576;&#1575; &#1588;&#1705;&#1587;&#1578; &#1605;&#1608;&#1575;&#1580;&#1607; &#1588;&#1583;");
        }

    });
}

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
