<script>
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 1398.06
//-----------------------------
 
PersonInfo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	portal : <?= session::IsPortal() ? "true" : "false" ?>,
	PersonID : <?= $PersonID ?>,
	justInfoTab : <?= $justInfoTab ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function PersonInfo()
{
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال بارگذاري...'});
    mask.show();    
	
	this.store = new Ext.data.Store({
		proxy:{
			type: 'jsonp',
			url: this.address_prefix + "alterPersons.data.php?task=selectPersons&PersonID=" + this.PersonID ,
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		fields : ["AlterPersonID","NationalID","fullname","mobile","educationDeg","sex","BirthDate","WorkExp",
                  "SpecAchieve","assistPart","readyDate","reqWage","habitRange","result","fillDate","WorkExpPlace","marital"],
		autoLoad : true,
		listeners :{
			load : function(){
				
				record = this.getAt(0);
				
				PersonInfoObject.MakeInfoPanel(record);
				PersonInfoObject.mainPanel.loadRecord(record);
                    mask.hide();
                    if(PersonInfoObject.justInfoTab)
                    return;
                PersonInfoObject.tabPanel.down("[itemId=tab_info]").add(PersonInfoObject.mainPanel);
                PersonInfoObject.tabPanel.down("[name=BirthDate]").setValue(MiladiToShamsi(record.data.BirthDate));
                PersonInfoObject.tabPanel.down("[name=fillDate]").setValue(MiladiToShamsi(record.data.fillDate));
                PersonInfoObject.tabPanel.down("[name=readyDate]").setValue(MiladiToShamsi(record.data.readyDate));

				/*if(record.data.IsReal == "NO")
				{
					PersonInfoObject.mainPanel.down("[name=RegDate]").setValue( MiladiToShamsi(record.data.RegDate) );
					PersonInfoObject.mainPanel.down("[name=LastChangeDate]").setValue( MiladiToShamsi(record.data.LastChangeDate) );
				}
				else
					PersonInfoObject.mainPanel.down("[name=BirthDate]").setValue( MiladiToShamsi(record.data.BirthDate) );

				mask.hide();

				if(PersonInfoObject.justInfoTab)
					return;

				PersonInfoObject.tabPanel.down("[itemId=tab_info]").add(PersonInfoObject.mainPanel);

				if(record.data.IsReal == "YES")
					PersonInfoObject.tabPanel.down("[itemId=tab_signers]").destroy();*/
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
			}/*,{
				title : "مدارک",
				style : "padding:0 20px 0 20px",		
				itemId : "cmp_documents",						
				loader : {
					url : "../../office/dms/documents.php",
					scripts : true
				},
				listeners :{
					activate : function(){
						if(this.loader.isLoaded)
							return;
						this.loader.load({
							scripts : true,
							params : {
								ObjectID : PersonInfoObject.PersonID,
								ExtTabID : this.id,
								ObjectType : "person"
							}
						});
					}
				}
			},{
				title : "صاحبین امضاء",
				itemId : "tab_signers",
				style : "padding:5px",
				loader : {
					url : this.address_prefix + "OrgSigners.php",
					scripts : true
				},
				listeners :{
					activate : function(){
						if(this.loader.isLoaded)
							return;
						this.loader.load({
							scripts : true,
							params : {
								ExtTabID : this.id,
								PersonID : PersonInfoObject.PersonID
							}
						});
					}
				}
			},{
				title : "مجوز ها",
				itemId : "tab_licenses",
				style : "padding:5px",
				loader : {
					url : this.address_prefix + "licenses.php",
					scripts : true
				},
				listeners :{
					activate : function(){
						if(this.loader.isLoaded)
							return;
						this.loader.load({
							scripts : true,
							params : {
								ExtTabID : this.id,
								PersonID : PersonInfoObject.PersonID
							}
						});
					}
				}
			},{
				title : "اعتبار سنجی",
				itemId : "tab_agreement",
				style : "padding:5px",
				loader : {
					url : this.address_prefix + "Agreement.php",
					scripts : true
				},
				listeners :{
					activate : function(){
						if(this.loader.isLoaded)
							return;
						this.loader.load({
							scripts : true,
							params : {
								ExtTabID : this.id,
								PersonID : PersonInfoObject.PersonID
							}
						});
					}
				}
			}*/]
		});	
	}
	
}

PersonInfo.prototype.MakeInfoPanel = function(PersonRecord){
	
	var items;
		items = [{
                xtype : "shdatefield",
                fieldLabel: 'تاریخ تکمیل',
                allowBlank : true,
                /*beforeLabelTextTpl: required,*/
                name: 'fillDate'
            },{
			xtype : "textfield",
			fieldLabel: 'نام و نام خانوادگی',
			allowBlank : true,
			beforeLabelTextTpl: required,
			name: 'fullname'
		},{
			xtype : "textfield",
			fieldLabel: 'کد ملی',
			allowBlank : true,
			beforeLabelTextTpl: required,
			name: 'NationalID'
		},{
			xtype : "combo",
			fieldLabel : "جنسیت",
			allowBlank : true,
			beforeLabelTextTpl: required,
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
			fieldLabel: 'تلفن همراه',
			/*allowBlank : false,
			beforeLabelTextTpl: required,*/
			name: 'mobile'
		},{
			xtype : "textfield",
			fieldLabel: 'آخرین مدرک تحصیلی',
			allowBlank : true,
			beforeLabelTextTpl: required,
			name: 'educationDeg'
		},{
             xtype : "shdatefield",
            fieldLabel: 'تاریخ تولد',
            allowBlank : true,
            beforeLabelTextTpl: required,
            name: 'BirthDate'
        },{
    xtype : "textfield",
    fieldLabel: 'سوابق کاری',
    allowBlank : true,
    readOnly : this.portal && PersonRecord.data.IsActive == "YES",
    beforeLabelTextTpl: required,
    name: 'WorkExp'
},{
                xtype : "textfield",
                fieldLabel : "محل سابقه",
                name : "WorkExpPlace"
            },{
                xtype : "combo",
                fieldLabel : "وضعیت تاهل",
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
    fieldLabel: 'موفقیت های ویژه',
    allowBlank : true,
    beforeLabelTextTpl: required,
    readOnly : this.portal && PersonRecord.data.IsActive == "YES",
    name: 'SpecAchieve'
},{
    xtype : "textfield",
    fieldLabel: 'درخواست همکاری در قسمت',
    allowBlank : true,
    beforeLabelTextTpl: required,
    name: 'assistPart'
},{
    xtype : "shdatefield",
    fieldLabel: 'تاریخ آمادگی به اشتغال',
    allowBlank : true,
    beforeLabelTextTpl: required,
    name: 'readyDate'
},{
    xtype : "textfield",
    fieldLabel: 'میزان حقوق درخواستی',
    allowBlank : true,
    beforeLabelTextTpl: required,
    name: 'reqWage'
},{
    xtype : "textfield",
    fieldLabel: 'محدوده سکونت',
    allowBlank : true,
    beforeLabelTextTpl: required,
    name: 'habitRange'
},{
    xtype : "textfield",
    fieldLabel: 'نتیجه',
    allowBlank : true,
    beforeLabelTextTpl: required,
    name: 'result'
},

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
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {
			width : 350
		},
		items: items,

		buttons : [{
			text : "ذخیره",
			iconCls: 'save',
			handler: function(){ PersonInfoObject.SaveData(); }

		}]
	});
}

PersonInfoObject = new PersonInfo();

/*PersonInfo.prototype.ActDomainLOV = function(){
		
	if(!this.DomainWin)
	{
		this.DomainWin = new Ext.window.Window({
			autoScroll : true,
			width : 420,
			height : 420,
			title : "حوزه فناوری",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "../baseInfo/ActDomain.php?mode=adding",
				scripts : true
			}
		});
		
		Ext.getCmp(this.TabID).add(this.DomainWin);
	}
	
	this.DomainWin.show();
	
	this.DomainWin.loader.load({
		params : {
			ExtTabID : this.DomainWin.getEl().dom.id,
			parent : "PersonInfoObject.DomainWin",
			selectHandler : function(id, name){
				PersonInfoObject.mainPanel.down("[name=DomainDesc]").setValue(name);
				PersonInfoObject.mainPanel.down("[name=DomainID]").setValue(id);
			}
		}
	});
}*/

PersonInfo.prototype.SaveData = function() {
				
	mask = new Ext.LoadMask(this.mainPanel, {msg:'در حال ذخيره سازي...'});
	mask.show();  
	this.mainPanel.getForm().submit({
		clientValidation: true,
		url: this.address_prefix + 'alterPersons.data.php?task=SaveNewAlterPerson',
		IsUpload : true,
		
		params : {
			AlterPersonID : this.PersonID
		},
		method: "POST",

		success : function(form,result){
			mask.hide();
			Ext.MessageBox.alert("","اطلاعات با موفقیت ذخیره شد");
			
			framework.OpenPage("framework/alterPerson/showAllpersons.php", "گزارش", {
               /*MenuID : NewAlterPersonObject.MenuID,
               PlanID : result.data*/});
			/*document.getElementById("img_PersonPic").src = document.getElementById("img_PersonPic").src + "&" + new Date().getTime();*/
		},
		failure : function(form,result){
			mask.hide();
			Ext.MessageBox.alert("",result.result.data == "" ? "عملیات مورد نظر با شکست مواجه شد" : result.result.data);
		}
	});
}

</script>