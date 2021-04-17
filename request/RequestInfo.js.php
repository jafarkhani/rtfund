<script>
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 1398.06
//-----------------------------
 
RequestInfo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	portal : <?= session::IsPortal() ? "true" : "false" ?>,
	PersonID : <?= $PersonID ?>,
	justInfoTab : <?= $justInfoTab ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function RequestInfo()
{
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاري...'});
    mask.show();    
	
	this.store = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + "Request.data.php?task=selectRequests&PersonID=" + this.PersonID ,
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["IDReq","IsRegister","PersonID","MobCustomer","askerName","askerMob","askerID","IsPresent","referalDate","referalTime","LetterID" ,"IsInfoORService","serviceType","otherService","InformationDesc","IsRelated","referPersonID","referDesc","Poll"],
		autoLoad : true,
		listeners :{
			load : function(){
				
				record = this.getAt(0);
				
				RequestInfoObject.MakeInfoPanel(record);
				RequestInfoObject.mainPanel.loadRecord(record);
                    mask.hide();
                    if(RequestInfoObject.justInfoTab)
                    return;
                RequestInfoObject.tabPanel.down("[itemId=tab_info]").add(RequestInfoObject.mainPanel);
                RequestInfoObject.tabPanel.down("[name=referalDate]").setValue(MiladiToShamsi(record.data.referalDate));
			}
		}
	});	
	
	if(!this.justInfoTab)
	{
		this.tabPanel = new Ext.TabPanel({
			renderTo: this.get("mainForm"),
			activeTab: 0,
			plain:true,
			autoScroll : true,
			autoHeight: true, 
			width: 750,
			defaults:{
				autoHeight: true, 
				autoWidth : true            
			},
			items:[{
				title : "اطلاعات پایه",
				itemId : "tab_info"
			}]
		});	
	}
	
}

RequestInfo.prototype.MakeInfoPanel = function(RequestRecord){
	
	var items;
		items = [
{
    xtype : "hidden",
    name : "IDReq"
},
{
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
    RequestInfoObject.mainPanel.down("[name=PersonID]").enable();
    /*NewServiceRequestObject.planFS.down("[name=CustomerName]").enable();*/
    RequestInfoObject.mainPanel.down("[name=CustomerMob]").enable();
    RequestInfoObject.mainPanel.down("[name=askerName]").disable();
    RequestInfoObject.mainPanel.down("[name=askerName]").setValue("");
    RequestInfoObject.mainPanel.down("[name=askerMob]").disable();
    RequestInfoObject.mainPanel.down("[name=askerMob]").setValue("");
}
    else
{
    console.log('Falsingggggggg');
    RequestInfoObject.mainPanel.down("[name=PersonID]").disable();
    RequestInfoObject.mainPanel.down("[name=PersonID]").setValue("");
    /*NewServiceRequestObject.planFS.down("[name=CustomerName]").disable();*/
    RequestInfoObject.mainPanel.down("[name=CustomerMob]").disable();
    RequestInfoObject.mainPanel.down("[name=CustomerMob]").setValue("");
    RequestInfoObject.mainPanel.down("[name=askerName]").enable();
    RequestInfoObject.mainPanel.down("[name=askerMob]").enable();
}
}
},
    inputValue:'Yes'

},{
    xtype : "combo",
    store : new Ext.data.SimpleStore({
    proxy: {
    type: 'jsonp',
    /*url: this.address_prefix + '../../framework/person/persons.data.php?' +
    "task=selectPersons&UserType=IsCustomer",*/
    url: this.address_prefix + '../../framework/person/persons.data.php?task=SearchPersons&UserTypes=IsCustomer',
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    /*fields : ['PersonID','fullname','mobile']*/
    fields : ['PersonID','name','mobile'],
    autoLoad : true
}),
    fieldLabel : "نام ذینفع",
    displayField : "name",
    pageSize : 20,
    width : 250,
    valueField : "PersonID",
    name : "PersonID",
    listeners : {
    function  (combo, records, index, eOpts ){
        console.log(record);
    Ext.getCmp('MobCustomer').setValue(records[0].get('mobile'))
}
}
    /*name : "CustomerName"*/

},{
    xtype : "textfield",
    fieldLabel : "شماره همراه ذینفع",
    name : "CustomerMob",
    id : "MobCustomer",
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
    disabled : true,
    width : 250
},{
    xtype : "textfield",
    fieldLabel : "شماره همراه متقاضی",
    name : "askerMob",
    itemId : "askerMob",
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
    inputValue: 'No'
}
    ]
}

    ,{
    xtype : "shdatefield",
    allowBlank : false,
    /*displayfield : 'referalDate',*/
    beforeLabelTextTpl: required,
    fieldLabel: 'تاریخ مراجعه',
    name: 'referalDate'
},{
    xtype : "numberfield",
    fieldLabel: 'ساعت مراجعه',
    allowBlank : false,
    beforeLabelTextTpl: required,
    name: 'referalTime'
}
/*,{
    xtype : "timefield",
    readOnly : readOnly,
    name : "StartTime",
    format : "H:i",
    hideTrigger : true,
    submitFormat : "H:i:s",
    labelWidth : 110,
    width : 240,
    fieldLabel : "از ساعت",
    allowBlank : false
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
}
    ,
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
    RequestInfoObject.mainPanel.down("[name=serviceType]").enable();
    RequestInfoObject.mainPanel.down("[name=otherService]").enable();
    RequestInfoObject.mainPanel.down("[name=InformationDesc]").disable();
}
    else
{
    console.log('Info');
    RequestInfoObject.mainPanel.down("[name=serviceType]").disable();
    RequestInfoObject.mainPanel.down("[name=otherService]").disable();
    RequestInfoObject.mainPanel.down("[name=InformationDesc]").enable();
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
    allowBlank : false,
    beforeLabelTextTpl: required,
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
    allowBlank : false,
    beforeLabelTextTpl: required,
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
    RequestInfoObject.mainPanel.down("[name=referPersonID]").enable();
    RequestInfoObject.mainPanel.down("[name=referDesc]").enable();
}
    else
{
    console.log('Not Related');
    RequestInfoObject.mainPanel.down("[name=referPersonID]").disable();
    RequestInfoObject.mainPanel.down("[name=referDesc]").disable();
}
}
}
},
{
    xtype: 'radiofield',
    boxLabel: 'خیر',
    name: 'IsRelated',
    inputValue: 'No'
}
    ]
},
{
    xtype : "combo",
    name : "referPersonID",
    store : new Ext.data.SimpleStore({
    proxy: {
    type: 'jsonp',
    /*url: this.address_prefix + '../../framework/person/persons.data.php?' +
    "task=selectPersons&UserType=IsStaff",*/
    url: this.address_prefix + '../../framework/person/persons.data.php?task=SearchPersons&UserTypes=IsCustomer',
    reader: {root: 'rows',totalProperty: 'totalCount'}
},
    /*fields : ['PersonID','fullname']*/
    fields : ['PersonID','name'],
    autoLoad : true
}),
    fieldLabel : "نام کارشناس ارجاعی",
    displayField : "name",
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

		    ,

			/*store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + 'persons.data.php?task=selectPersonInfoTypes&TypeID=93&PersonID=' + this.PersonID,
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['TypeID','InfoID','InfoDesc','checked',{
					name : "chname",convert(value,record){return "info_" + record.data.TypeID + "_" + record.data.InfoID}
				}],
				autoLoad : true					
			})
		,*/{
			xtype : "hidden",
			name : "DomainID",
			colspan : 2
		}];

	this.mainPanel = new Ext.form.FormPanel({
		width: 750,
		frame : true,
		renderTo: this.justInfoTab ? this.get("mainForm") : "",
		/*layout : {
			type : "table",
			columns : 2
		}
		,*/layout : "vbox",
		defaults : {
			width : 350
		},
		items: items,

		buttons : [{
			text : "ذخیره",
			iconCls: 'save',
			handler: function(){ RequestInfoObject.SaveData(); }

		}]
	});
}

RequestInfoObject = new RequestInfo();


RequestInfo.prototype.SaveData = function() {
				
	mask = new Ext.LoadMask(this.mainPanel, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	this.mainPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'Request.data.php?task=SaveNewRequest',
		IsUpload : true,
		params : {
        /*IDReq : this.IDReq*/
		},
		method: "POST",

		success : function(form,result){
			mask.hide();
			
			Ext.MessageBox.alert("Success", "عملیات مورد نظر با موفقیت شد");
                                framework.CloseTab(RequestInfoObject.TabID);
                            
			/*framework.OpenPage("request/RequestManage.php", "مدیریت درخواست ها,{
               });*/
			/*document.getElementById("img_PersonPic").src = document.getElementById("img_PersonPic").src + "&" + new Date().getTime();*/
		},
		failure : function(form,result){
			mask.hide();
			Ext.MessageBox.alert("",result.result.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : result.result.data);
		}
	});
}

</script>