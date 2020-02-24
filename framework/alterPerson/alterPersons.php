<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("conf/header.inc.php");
require_once inc_dataGrid;
require_once 'alterPersons.class.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg",$js_prefix_address . "alterPersons.data.php?task=selectPersons", "div_grid_persons");

$grid = $dg->makeGrid_returnObjects();

?>

<center>
    <div id="div_plan"></div>
    <div id="div_grid"></div>
</center>

<script>
    NewAlterPerson.prototype = {
        TabID : '<?= $_REQUEST["ExtTabID"]?>',
        address_prefix : "<?= $js_prefix_address?>",
        MenuID : "<?= $_POST["MenuID"] ?>",

        get : function(elementID){
            return findChild(this.TabID, elementID);
        }
    };




    function NewAlterPerson(){


        this.planFS = new Ext.form.FormPanel({
            title : "ثبت نیروی جانشین جدید",
            name: 'myfieldform',
            ID: 'myfieldform',
            width : 700,
            layout : "vbox",
            renderTo : this.get("div_plan"),
            items : [{
                xtype : "shdatefield",
                fieldLabel: 'تاریخ تکمیل',
                allowBlank : false,
                /*beforeLabelTextTpl: required,*/
                name: 'fillDate'
            },{
                xtype : "textfield",
                fieldLabel : "نام و نام خانوادگی",
                allowBlank : false,
                name : "fullname",
                width : 600
            },{
                xtype : "textfield",
                regex: /^\d{10}$/,
                maskRe: /[\d\-]/,
                fieldLabel: 'کد ملی',
                allowBlank : false,
                name: 'NationalID',
                width : 600
            },{
                xtype : "combo",
                fieldLabel : "جنسیت",
                width : 600,
                name : "sex",
                store : new Ext.data.SimpleStore({
                    data : [
                        ["MALE" , "مرد" ],
                        ["WOMAN" , "زن" ]
                    ],
                    fields : ['id','value']
                }),
                displayField : "value",
                valueField : "id"
            },{
                xtype : "textfield",
                regex: /^\d{10}$/,
                /*maskRe: /[\d\-]/,*/
                fieldLabel: 'تلفن همراه',
                /*allowBlank : false,*/
                name: 'mobile',
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "آخرین مدرک تحصیلی",
                name : "educationDeg",
                width : 600
            },{
                xtype : "shdatefield",
                fieldLabel: 'تاریخ تولد',
                allowBlank : false,
                /*beforeLabelTextTpl: required,*/
                name: 'BirthDate'
            },{
                xtype : "textfield",
                fieldLabel : "سوابق کاری",
                name : "WorkExp",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "محل سابقه",
                name : "WorkExpPlace",
                width : 600
            },{
                xtype : "combo",
                fieldLabel : "وضعیت تاهل",
                width : 600,
                name : "marital",
                store : new Ext.data.SimpleStore({
                    data : [
                        ["Married" , "متاهل" ],
                        ["Single" , "مجرد" ]
                    ],
                    fields : ['id','value']
                }),
                displayField : "value",
                valueField : "id"
            },{
                xtype : "textfield",
                fieldLabel : "موفقیت های ویژه",
                name : "SpecAchieve",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "درخواست همکاری در قسمت",
                name : "assistPart",
                width : 600
            },{
                xtype : "shdatefield",
                fieldLabel : "تاریخ آمادگی به اشتغال",
                name : "readyDate"
            },{
                xtype : "textfield",
                fieldLabel : "میزان حقوق درخواستی",
                name : "reqWage",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "محدوده سکونت",
                name : "habitRange",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "نتیجه",
                name : "result",
                rowspan : 4,
                width : 600
            }],
            buttons:[{
                text : "ذخیره",
                iconCls : "arrow_left",
                handler : function(btn){
                    var data = this.up('form').getForm();

                    Ext.Ajax.request({
       methos : "post",
       url : "alterPerson/" + "alterPersons.data.php?task=SaveNewAlterPerson",
       params : data.getValues(),

       success : function(response){

           /*mask.hide();*/
           result = Ext.decode(response.responseText);
           if(result.success)
           {
               Ext.MessageBox.alert("Success", "عملیات مورد نظر با موفقیت شد");
                   framework.CloseTab(NewAlterPersonObject.TabID);
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


/*function NewAlterPerson(){


        this.planFS = new Ext.form.FieldSet({
            title : "ثبت نیروی جانشین جدید",
            name: 'myfieldform',
            ID: 'myfieldform',
            width : 700,
            layout : "vbox",
            renderTo : this.get("div_plan"),
            items : [{
				xtype : "textfield",
				fieldLabel : "نام و نام خانوادگی",
                allowBlank : false,
				name : "fullname",
				width : 600
			},{
                xtype : "textfield",
                regex: /^\d{10}$/,
                maskRe: /[\d\-]/,
                fieldLabel: 'کد ملی',
                allowBlank : false,
                name: 'NationalID',
                width : 600
            },{
                xtype : "combo",
                fieldLabel : "جنسیت",
                width : 600,
                name : "sex",
                store : new Ext.data.SimpleStore({
                    data : [
                        ["MALE" , "مرد" ],
                        ["WOMAN" , "زن" ]
                    ],
                    fields : ['id','value']
                }),
                displayField : "value",
                valueField : "id"
            },{
                xtype : "textfield",
                fieldLabel : "آخرین مدرک تحصیلی",
                name : "educationDeg",
                width : 600
            },{
                xtype : "shdatefield",
                fieldLabel: 'تاریخ تولد',
                allowBlank : false,
                /!*beforeLabelTextTpl: required,*!/
                name: 'BirthDate'
            },{
                xtype : "textfield",
                fieldLabel : "سوابق کاری",
                name : "WorkExp",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "موفقیت های ویژه",
                name : "SpecAchieve",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "درخواست همکاری در قسمت",
                name : "assistPart",
                width : 600
            },{
                xtype : "shdatefield",
                fieldLabel : "تاریخ آمادگی به اشتغال",
                name : "readyDate"
            },{
                xtype : "textfield",
                fieldLabel : "میزان حقوق درخواستی",
                name : "reqWage",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "محدوده سکونت",
                name : "habitRange",
                width : 600
            },{
                xtype : "textfield",
                fieldLabel : "نتیجه",
                name : "result",
                rowspan : 4,
                width : 600
            }
                ,{
                    xtype : "button",
                    text : "ذخیره",
                    iconCls : "arrow_left",
                    handler : function(){ NewAlterPersonObject.SaveNewAlterPerson(); }
                }]
        });


}*/

NewAlterPersonObject= new NewAlterPerson();

/*NewAlterPerson.prototype.SaveNewAlterPerson = function(){
    mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
    mask.show();
this.up('form')
    this.planFS.getForm().submit({
        url: this.address_prefix + "alterPersons.data.php?task=SaveNewAlterPerson'",
        params: {data: finalData},
        method: 'POST',
        success : function(response){
            mask.hide();
            result = Ext.decode(response.responseText);
            if(result.success)
            {
                Ext.MessageBox.alert("Success", "عملیات مورد نظر با موفقیت شد");
            }
            else
                Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
        }

    })

    /!*Ext.Ajax.request({
        methos : "post",
        url : this.address_prefix + "alterPersons.data.php",
        params : {
            task : "SaveNewAlterPerson",
            Adata : this.planFS.down("[name=fullname]").getValue(),
            /!*Bdata : this.planFS.up('myfieldform').getValue(),*!/
            Cdata : this.planFS.up('myfieldform'),


            /!*Cdata : this.planFS.down.getValue(),
            Ddata : this.planFS.down.up("[name=myfieldform]").getForm()*!/

            /!*PlanID : this.PlanID,
            FormType : this.FormType,
            PlanDesc : this.planFS.down("[name=PlanDesc]").getValue(),
            LoanID : this.planFS.down("[name=LoanID]").getValue(),
            PersonID : this.framework ? this.planFS.down("[name=PersonID]").getValue() : ""*!/
        },


        success : function(response){
            mask.hide();
            result = Ext.decode(response.responseText);
            if(result.success)
            {
                Ext.MessageBox.alert("Success", "عملیات مورد نظر با موفقیت شد");
                    framework.CloseTab(NewAlterPersonObject.TabID);
                    framework.OpenPage("plan/plan/PlanInfo.php", "جداول اطلاعاتی طرح", {
                        MenuID : NewAlterPersonObject.MenuID,
                        PlanID : result.data});
            }
            else
                Ext.MessageBox.alert("Error", "عملیات مورد نظر با شکست مواجه شد");
        }
    });*!/

}*/




</script>