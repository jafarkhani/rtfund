<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	94.06
//-------------------------
require_once('../../header.inc.php');
require_once 'request.class.php';
require_once inc_dataGrid;

$RequsetID = $_REQUEST["RequestID"];

$access = false;
$obj = new LON_requests($RequsetID);
if($_SESSION["USER"]["IsCustomer"] == "YES" && in_array($obj->StatusID, array("40","60")) )
	$access = true;
if($_SESSION["USER"]["IsStaff"] == "YES" && $obj->StatusID != LON_REQ_STATUS_CONFIRM)
	$access = true;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=GetGuarantors&FormType=1&RequestID="
		. $RequsetID, "grid_div");

$dg->addColumn("", "GuarantorID", "", true);
$dg->addColumn("", "RequestID", "", true);
$dg->addColumn("", "sex", "", true);
$dg->addColumn("", "father", "", true);
$dg->addColumn("", "ShNo", "", true);
$dg->addColumn("", "ShCity", "", true);
$dg->addColumn("", "BirthDate", "", true);
$dg->addColumn("", "phone", "", true);

$col = $dg->addColumn("نوع فرد", "PersonType", "");
$col->renderer = "function(v){return v == 'GUARANTOR' ? 'ضامن' : 'وثیقه گذار';}";
$col->width = 120;

$col = $dg->addColumn("نام و نام خانوادگی", "fullname", "");
$col->width = 140;

$col = $dg->addColumn("کدملی", "NationalCode");
$col->width = 100;

$col = $dg->addColumn("موبایل", "mobile");
$col->width = 80;

$col = $dg->addColumn("آدرس", "address", "");

if($access)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){GuarantorObject.GuarantorInfo('new',1);}";
	
	$col = $dg->addColumn("عملیات", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return Guarantor.OperationRender(v,p,r);}";
	$col->width = 50;
}
$dg->title = "لیست ضامنین و وثیقه گذاران";
$dg->height = 260;
$dg->width = 700;
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "fullname";
$dg->autoExpandColumn = "address";
$dg->emptyTextOfHiddenColumns = true;

$grid = $dg->makeGrid_returnObjects();

//new added
$dg1 = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=GetGuarantors&FormType=2&RequestID="
    . $RequsetID , "grid_div1");

$dg1->addColumn("", "GuarantorID", "", true);
$dg1->addColumn("", "RequestID", "", true);
$dg1->addColumn("", "sex", "", true);
$dg1->addColumn("", "father", "", true);
$dg1->addColumn("", "ShNo", "", true);
$dg1->addColumn("", "ShCity", "", true);
$dg1->addColumn("", "BirthDate", "", true);
$dg1->addColumn("", "phone", "", true);

$col = $dg1->addColumn("نوع فرد", "PersonType", "");
$col->renderer = "function(v){return v == 'GUARANTOR' ? 'ضامن' : 'وثیقه گذار';}";
$col->width = 60;

$col = $dg1->addColumn("نام شرکت  ", "fullname", "");
$col->width = 160;

$col = $dg1->addColumn("شناسه ملی", "NationalCode");
$col->width = 100;

$col = $dg1->addColumn("موبایل مدیرعامل", "mobile");
$col->width = 100;

$col = $dg1->addColumn("آدرس", "address", "");

if($access)
{
    $dg1->addButton = true;
    $dg1->addHandler = "function(){GuarantorObject.GuarantorInfo('new',2);}";

    $col = $dg1->addColumn("عملیات", "");
    $col->sortable = false;
    $col->renderer = "function(v,p,r){return Guarantor.OperationRender1(v,p,r);}";
    $col->width = 50;
}
$dg1->title = "لیست ضامنین و وثیقه گذاران حقوقی";
$dg1->height = 260;
$dg1->width = 700;
$dg1->EnablePaging = false;
$dg1->EnableSearch = false;
$dg1->DefaultSortField = "fullname";
$dg1->autoExpandColumn = "address";
$dg1->emptyTextOfHiddenColumns = true;

$grid1 = $dg1->makeGrid_returnObjects();
//end new added
?>
<center>
    <form id="mainForm">
		<div id="newDiv"></div>
        <div id="grid_div"></div>
    </form>
    <form id="mainForm1">
        <div id="newDiv1"></div>
        <div id="grid_div1"></div>
    </form>
</center>
<script>

Guarantor.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	RequestID : <?= $RequsetID ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function Guarantor(){
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("grid_div"));
    this.grid1 = <?= $grid1 ?>;
    this.grid1.render(this.get("grid_div1"));
}

/*Guarantor.prototype.GuarantorInfo = function(mode)
{
	if(!this.formPanel)
	{
		this.formPanel = new Ext.form.Panel({
			renderTo: this.get("newDiv"),
			collapsible: true,
			frame: true,
			title: 'اطلاعات فرد',
			bodyPadding: ' 10 10 12 10',
			width:600,
			layout :{
				type : "table",
				columns :2,
				width:600
			},
			defaults : {
				width : 270
			},
			items: [{
					xtype:'radiogroup',
					fieldLabel: 'نوع فرد',
					items : [{
						xtype : "radio",
						boxLabel : "ضامن",
						inputValue : "GUARANTOR",
						checked : true,
						name : "PersonType"
					},{
						xtype : "radio",
						boxLabel : "وثیقه گذار",
						inputValue : "SPONSOR",
						name : "PersonType"
					}]
				},{
					xtype:'radiogroup',
					fieldLabel: 'جنسیت',
					items : [{
						xtype : "radio",
						boxLabel : "مرد",
						inputValue : "MALE",
						checked : true,
						name : "sex"
					},{
						xtype : "radio",
						boxLabel : "زن",
						inputValue : "FEMALE",
						name : "sex"
					}]
				},{
					xtype:'textfield',
					fieldLabel: 'نام و نام خانوادگی',
					name: 'fullname',
					allowBlank : false
				},{
					xtype:'textfield',
					fieldLabel: 'نام پدر',
					name: 'father',
					allowBlank : false
				},{
					xtype:'numberfield',
					fieldLabel: 'کدملی',
					name: 'NationalCode',
					hideTrigger : true,
					allowBlank : false
				},{
					xtype:'numberfield',
					fieldLabel: 'شماره شناسنامه',
					name: 'ShNo',
					hideTrigger : true,
					allowBlank : false
				},{
					xtype:'shdatefield',
					fieldLabel: 'تاریخ تولد',
					name: 'BirthDate',
					allowBlank : false
				},{
					xtype:'textfield',
					fieldLabel: 'صادره',
					name: 'ShCity',
					allowBlank : false
				},{
					xtype:'numberfield',
					fieldLabel: 'موبایل',
					name: 'mobile',
					hideTrigger : true,
					allowBlank : false
				},{
					xtype:'numberfield',
					fieldLabel: 'تلفن',
					name: 'phone',
					hideTrigger : true,
					allowBlank : false
				},{
					xtype:'textfield',
					fieldLabel: 'آدرس',
					colspan : 2,
					width : 540,
					name: 'address',
					allowBlank : false
				},{
					xtype : "hidden",
					name : "GuarantorID"
				}],
			buttons: [{
					text : "ذخیره",
					iconCls : "save",
					handler : function(){
						mask = new Ext.LoadMask(GuarantorObject.formPanel, {msg:'در حال ذخیره ...'});
						mask.show();

						GuarantorObject.formPanel.getForm().submit({
							clientValidation: true,
							url : GuarantorObject.address_prefix + 'request.data.php?task=SaveGuarantor',
							method : "POST",
							params : {
								RequestID : GuarantorObject.RequestID
							},

							success : function(form,action){
								mask.hide();
								if(action.result.success)
									GuarantorObject.grid.getStore().load();
								else
									alert("عملیات مورد نظر با شکست مواجه شد.");

								GuarantorObject.formPanel.hide();
							},
							failure : function(){
								mask.hide();
							}
						});
					}
				},{
					text : "انصراف",
					iconCls : "undo",
					handler : function(){
						GuarantorObject.formPanel.hide();
					}
				}]
		});
	}

	if(mode == "new")
	{
		this.formPanel.getForm().reset();
	}
	else
	{
		var record = this.grid.getSelectionModel().getLastSelected();
		this.formPanel.getForm().loadRecord(record);
		this.formPanel.down("[name=BirthDate]").setValue(MiladiToShamsi(record.data.BirthDate));
	}

	this.formPanel.show();
}*/

Guarantor.prototype.GuarantorInfo = function(mode,type)
{
    if (type == 1){
        if(!this.formPanel)
        {
            this.formPanel = new Ext.form.Panel({
                renderTo: this.get("newDiv"),
                collapsible: true,
                frame: true,
                title: 'اطلاعات فرد',
                bodyPadding: ' 10 10 12 10',
                width:600,
                layout :{
                    type : "table",
                    columns :2,
                    width:600
                },
                defaults : {
                    width : 270
                },
                items: [{
                    xtype:'radiogroup',
                    fieldLabel: 'نوع فرد',
                    items : [{
                        xtype : "radio",
                        boxLabel : "ضامن",
                        inputValue : "GUARANTOR",
                        checked : true,
                        name : "PersonType"
                    },{
                        xtype : "radio",
                        boxLabel : "وثیقه گذار",
                        inputValue : "SPONSOR",
                        name : "PersonType"
                    }]
                },{
                    xtype:'radiogroup',
                    fieldLabel: 'جنسیت',
                    items : [{
                        xtype : "radio",
                        boxLabel : "مرد",
                        inputValue : "MALE",
                        checked : true,
                        name : "sex"
                    },{
                        xtype : "radio",
                        boxLabel : "زن",
                        inputValue : "FEMALE",
                        name : "sex"
                    }]
                },{
                    xtype:'textfield',
                    fieldLabel: 'نام و نام خانوادگی',
                    name: 'fullname',
                    allowBlank : false
                },{
                    xtype:'textfield',
                    fieldLabel: 'نام پدر',
                    name: 'father',
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: 'کدملی',
                    name: 'NationalCode',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: 'شماره شناسنامه',
                    name: 'ShNo',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'shdatefield',
                    fieldLabel: 'تاریخ تولد',
                    name: 'BirthDate',
                    allowBlank : false
                },{
                    xtype:'textfield',
                    fieldLabel: 'صادره',
                    name: 'ShCity',
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: 'موبایل',
                    name: 'mobile',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: 'تلفن',
                    name: 'phone',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'textfield',
                    fieldLabel: 'آدرس',
                    colspan : 2,
                    width : 540,
                    name: 'address',
                    allowBlank : false
                },{
                    xtype : "hidden",
                    name : "GuarantorID"
                }],
                buttons: [{
                    text : "ذخیره",
                    iconCls : "save",
                    handler : function(){
                        mask = new Ext.LoadMask(GuarantorObject.formPanel, {msg:'در حال ذخیره ...'});
                        mask.show();

                        GuarantorObject.formPanel.getForm().submit({
                            clientValidation: true,
                            url : GuarantorObject.address_prefix + 'request.data.php?task=SaveGuarantor',
                            method : "POST",
                            params : {
                                RequestID : GuarantorObject.RequestID,
                                FormType : 1
                            },

                            success : function(form,action){
                                mask.hide();
                                if(action.result.success)
                                    GuarantorObject.grid.getStore().load();
                                else
                                    alert("عملیات مورد نظر با شکست مواجه شد.");

                                GuarantorObject.formPanel.hide();
                            },
                            failure : function(){
                                mask.hide();
                            }
                        });
                    }
                },{
                    text : "انصراف",
                    iconCls : "undo",
                    handler : function(){
                        GuarantorObject.formPanel.hide();
                    }
                }]
            });
        }
        if(mode == "new")
        {
            this.formPanel.getForm().reset();
        }
        else
        {
            console.log(mode);console.log(type);
            var record = this.grid.getSelectionModel().getLastSelected();
            this.formPanel.getForm().loadRecord(record);
            this.formPanel.down("[name=BirthDate]").setValue(MiladiToShamsi(record.data.BirthDate));
            console.log('formpanel');
        }
        this.formPanel.show();
    }
    if (type == 2){
        if(!this.formPanel2)
        {
            this.formPanel2 = new Ext.form.Panel({
                renderTo: this.get("newDiv1"),
                collapsible: true,
                frame: true,
                title: 'اطلاعات شرکت',
                bodyPadding: ' 10 10 12 10',
                width:600,
                layout :{
                    type : "table",
                    columns :2,
                    width:600
                },
                defaults : {
                    width : 270
                },
                items: [{
                    xtype:'radiogroup',
                    fieldLabel: 'نوع فرد',
                    items : [{
                        xtype : "radio",
                        boxLabel : "ضامن",
                        inputValue : "GUARANTOR",
                        checked : true,
                        name : "PersonType"
                    },{
                        xtype : "radio",
                        boxLabel : "وثیقه گذار",
                        inputValue : "SPONSOR",
                        name : "PersonType"
                    }]
                }/*,{
                    xtype:'radiogroup',
                    fieldLabel: 'جنسیت',
                    items : [{
                        xtype : "radio",
                        boxLabel : "مرد",
                        inputValue : "MALE",
                        checked : true,
                        name : "sex"
                    },{
                        xtype : "radio",
                        boxLabel : "زن",
                        inputValue : "FEMALE",
                        name : "sex"
                    }]
                }*/,{
                    xtype:'textfield',
                    fieldLabel: 'نام شرکت',
                    name: 'fullname',
                    allowBlank : false
                }/*,{
                    xtype:'textfield',
                    fieldLabel: 'نام پدر',
                    name: 'father',
                    allowBlank : false
                }*/,{
                    xtype:'numberfield',
                    fieldLabel: 'شناسه ملی',
                    name: 'NationalCode',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: 'شماره ثبت',
                    name: 'ShNo',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'shdatefield',
                    fieldLabel: 'تاریخ روزنامه',
                    name: 'BirthDate',
                    allowBlank : false
                }/*,{
                    xtype:'textfield',
                    fieldLabel: 'صادره',
                    name: 'ShCity',
                    allowBlank : false
                }*/,{
                    xtype:'numberfield',
                    fieldLabel: 'موبایل مدیرعامل',
                    name: 'mobile',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: 'تلفن ثابت',
                    name: 'phone',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: ' کد اقتصادی',
                    name: 'EconomicID',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: ' شماره آگهی روزنامه ',
                    name: 'NewspaperAdsNum',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'numberfield',
                    fieldLabel: ' کد پستی',
                    name: 'PostalCode',
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype:'textfield',
                    fieldLabel: 'آدرس',
                    colspan : 2,
                    width : 540,
                    name: 'address',
                    allowBlank : false
                },{
                    xtype:'textfield',
                    fieldLabel: '  ایمیل',
                    name: 'email',
                    colspan : 2,
                    width : 540,
                    hideTrigger : true,
                    allowBlank : false
                },{
                    xtype : "hidden",
                    name : "GuarantorID"
                }],
                buttons: [{
                    text : "ذخیره",
                    iconCls : "save",
                    handler : function(){
                        mask = new Ext.LoadMask(GuarantorObject.formPanel2, {msg:'در حال ذخیره ...'});
                        mask.show();

                        GuarantorObject.formPanel2.getForm().submit({
                            clientValidation: true,
                            url : GuarantorObject.address_prefix + 'request.data.php?task=SaveGuarantor',
                            method : "POST",
                            params : {
                                RequestID : GuarantorObject.RequestID,
                                FormType : 2
                            },

                            success : function(form,action){
                                mask.hide();
                                if(action.result.success)
                                    GuarantorObject.grid1.getStore().load();
                                else
                                    alert("عملیات مورد نظر با شکست مواجه شد.");

                                GuarantorObject.formPanel2.hide();
                            },
                            failure : function(){
                                mask.hide();
                            }
                        });
                    }
                },{
                    text : "انصراف",
                    iconCls : "undo",
                    handler : function(){
                        GuarantorObject.formPanel2.hide();
                    }
                }]
            });
        }
        if(mode == "new")
        {
            this.formPanel2.getForm().reset();
        }
        else
        {
            console.log(mode);console.log(type);
            var record1 = this.grid1.getSelectionModel().getLastSelected();
            this.formPanel2.getForm().loadRecord(record1);
            this.formPanel2.down("[name=BirthDate]").setValue(MiladiToShamsi(record1.data.BirthDate));
            console.log('formpanel2');
        }
        this.formPanel2.show();
    }
}

Guarantor.OperationRender = function(v,p,r)
{
	st = "<table width=100%><tr><td>";
	
		st += "<div align='center' title='ویرایش ' class='edit' "+
		"onclick='GuarantorObject.GuarantorInfo(\"edit\",1);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
	
	st += "</td><td>";
	
		st += "<div align='center' title='حذف ' class='remove' "+
		"onclick='GuarantorObject.DeleteGuarantor(1);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
	
	st += "</td></tr></table>";
	
	return st;
}
Guarantor.OperationRender1 = function(v,p,r)
{
    st = "<table width=100%><tr><td>";

    st += "<div align='center' title='ویرایش ' class='edit' "+
        "onclick='GuarantorObject.GuarantorInfo(\"edit\",2);' " +
        "style='background-repeat:no-repeat;background-position:center;" +
        "cursor:pointer;width:100%;height:16'></div>";

    st += "</td><td>";

    st += "<div align='center' title='حذف ' class='remove' "+
        "onclick='GuarantorObject.DeleteGuarantor(2);' " +
        "style='background-repeat:no-repeat;background-position:center;" +
        "cursor:pointer;width:100%;height:16'></div>";

    st += "</td></tr></table>";

    return st;
}
Guarantor.prototype.DeleteGuarantor = function(type)
{
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = GuarantorObject;
        if(type == 1)
            var record = me.grid.getSelectionModel().getLastSelected();
        if(type == 2)
            var record = me.grid1.getSelectionModel().getLastSelected();
		/*var record = me.grid.getSelectionModel().getLastSelected();*/
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DeleteGuarantor",
				GuarantorID : record.data.GuarantorID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
                if(type == 1)
                    GuarantorObject.grid.getStore().load();
                if(type == 2)
                    GuarantorObject.grid1.getStore().load();
				/*GuarantorObject.grid.getStore().load();*/
			},
			failure: function(){}
		});
	});
}

var GuarantorObject = new Guarantor();	

	
</script>
