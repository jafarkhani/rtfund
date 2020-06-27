<script type="text/javascript">
/*-------------------------
 * programmer: Mahdipour
 * CreateDate: 	94.11
 *------------------------- */

Person.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	personInfoForm : "",
	staffInfoForm : "",	
	IncludeHistoryGrid : "" ,
	sid : "" ,
	pid: "" ,
	person_type : "" ,
	mainTab : "",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Person()
{    

	this.personInfoForm = this.get("personInfoForm");
	this.staffInfoForm = this.get("staffInfoForm");
   
	this.mainPanel = new Ext.Panel({
		applyTo: this.get("SummaryPersonidDIV"),
		contentEl : this.get("SummaryPNL"),
		frame:true,
		hidden : (this.personInfoForm.PersonID.value == "") ? true : false ,
		title: "مشخصات فردی",
		width: 900 
	});

	this.mainTab = new Ext.TabPanel({
	    renderTo: this.get("mainTab"),
            activeTab: 0,
            plain:true,
                    width: "100%",
            defaults:{autoHeight: true, autoWidth : true},

        items:[{
            	itemId: 'list'
				,title: 'مشخصات فردی'
				,style:'padding:5px'
				,width: 900
                ,height:1400
				,contentEl: this.get("div_PInfo")
                },{
            	itemId: 'Tab_dependency'
            	,title: 'بستگان'
            	,style: 'padding:5px'
				,height:700
				,loader:{
					url: this.address_prefix + "dependencies.php",
					scripts:true
				}
				,disabled: (this.personInfoForm.PersonID.value == "" ) ? true : false
            	,listeners: {activate: function(tab){
					if(tab.isLoaded)
						return;
					tab.loader.load({						
						params : {
							Q0 : PersonObject.personInfoForm.PersonID.value,
							FacilID : '<?= $FacilID ?>'
						}						
					});
				}
             }

            },{
				itemId: 'Tab_Isar'
            	,title: 'ایثارگری'
            	,style: 'padding:5px'
				,height:700
				,loader:{
					url: this.address_prefix + "devotions.php",
					scripts:true
				}
            	,disabled: (this.personInfoForm.PersonID.value == ""  ) ? true : false
				,listeners: {activate: function(tab){
					if(tab.isLoaded)
						return;
					tab.loader.load({
						params : {
							Q0 : PersonObject.personInfoForm.PersonID.value,
							FacilID : '<?=$FacilID ?>'
						}
					});
				}
			 } 
            },{
				itemId: 'Tab_employments'
            	,title: 'سوابق کاری'
            	,style: 'padding:5px'
				,height:700
				,loader:{
					url: this.address_prefix + "employments.php",
					scripts:true
				}
            	,disabled: (this.personInfoForm.PersonID.value == "") ? true : false
				,listeners: {activate: function(tab){
					if(tab.isLoaded)
						return;
					tab.loader.load({
						params : {
							Q0 : PersonObject.personInfoForm.PersonID.value,
							FacilID : '<?= $FacilID ?>'
						}
					});
				}
			 }

            },{
                itemId: 'educations'
            	,title: 'سوابق تحصیلی'
            	,style: 'padding:5px'
				,height:700
				,loader:{
					url: this.address_prefix + "educations.php",
					scripts:true
				}
            	,disabled: (this.personInfoForm.PersonID.value == "") ? true : false
				,listeners: {activate: function(tab){
					if(tab.isLoaded)
						return;
					tab.loader.load({
						 params : {
							Q0 : PersonObject.personInfoForm.PersonID.value,
							FacilID : '<?= $FacilID ?>'
						}
					});
				}
			 }
            },{
                 itemId: 'writs'
            	,title: 'احکام '
            	,style: 'padding:5px'
				,height:700
				,loader:{
					url: this.address_prefix + "../../writs/ui/writs.php",
					scripts:true
				}
            	,disabled: (this.personInfoForm.PersonID.value == "") ? true : false
				,listeners: {activate: function(tab){
					if(tab.isLoaded)
						return;
					tab.loader.load({
						params : {
							Q0 : PersonObject.personInfoForm.PersonID.value,
							FacilID : '<?= $FacilID ?>'
						}
					});
				}
			 }
					 
		}	
		,
		{
					 itemId: 'tax'
					,title: 'سوابق مالی'
					,style: 'padding:5px'
					,height:700
					,loader:{
						url: this.address_prefix + "staff_tax_history.php",
						scripts:true
					}
					,disabled: (this.personInfoForm.PersonID.value == "") ? true : false
					,listeners: {activate: function(tab){
						if(tab.isLoaded)
							return;
						tab.loader.load({
							 params : {
								Q0 : PersonObject.personInfoForm.PersonID.value,
								Q1 : PersonObject.personInfoForm.PersonID.value,
								Q2 : PersonObject.personInfoForm.PersonID.value
								
							}
						});
					}
					  }
						},
                                                {
					 itemId: 'CostCode'
					,title: 'کد حساب پرداختی'
					,style: 'padding:5px'
					,height:700
					,loader:{
						url: this.address_prefix + "staff_CostCode_history.php",
						scripts:true
					}
					,disabled: (this.personInfoForm.PersonID.value == "") ? true : false
					,listeners: {activate: function(tab){
						if(tab.isLoaded)
							return;
						tab.loader.load({
							 params : {
								Q0 : PersonObject.personInfoForm.PersonID.value,
								Q1 : PersonObject.personInfoForm.PersonID.value,
								Q2 : PersonObject.personInfoForm.PersonID.value
								
							}
						});
					}
					  }
						},
						{
						 itemId: 'salaryReceipt'
						,title: 'فیش حقوقی'
						,style: 'padding:5px'
						,height:700
						,loader:{
							url: this.address_prefix + "../ui/salaryReceipt.php",
							scripts:true
						}
						,disabled: (this.personInfoForm.PersonID.value == "") ? true : false
						,listeners: {activate: function(tab){
							if(tab.isLoaded)
								return;
							tab.loader.load({
								params : {
									Q0 : PersonObject.personInfoForm.PersonID.value,
									FacilID : '<?= $FacilID ?>'
								}
							});
						}
						  }
						}
						
            ]
    });
    
    this.store1 = new Ext.data.Store({
		fields : ["state_id","ptitle"],
		proxy : {
			type: 'jsonp',
			url : this.address_prefix + "../../../global/domain.data.php?task=searchStates",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		},
		autoLoad:true
	});

	
    this.store2 = new Ext.data.Store({
            fields : ["city_id","state_id","ptitle"],
            proxy : {
                    type: 'jsonp',
                    url : this.address_prefix + "../../../global/domain.data.php?task=searchCities",
                    reader: {
                            root: 'rows',
                            totalProperty: 'totalCount'
                    }
            }
    });
        
       
    this.birthStateCombo = new Ext.form.field.ComboBox({
		store : this.store1,
		width : 200,
		typeAhead: false,
		queryMode : "local",
		displayField : "ptitle",
		valueField : "state_id",
		hiddenName : "birth_state_id",
		applyTo : this.get("birth_state_id"),
		listeners : {
			select : function(combo, records){
				PersonObject.birthCityCombo.reset();
				PersonObject.store2.load({
					params : {state_id : records[0].data.state_id}
				})
			}
		}
	});

	this.birthCityCombo = new Ext.form.field.ComboBox({
		store : this.store2,
		width : 100,
		typeAhead: false,
		queryMode : "local",
		displayField : "ptitle",
		valueField : "city_id",
		hiddenName : "birth_city_id",
		applyTo : this.get("birth_city_id")
	}); 

        
        this.store9 = new Ext.data.Store({
		fields : ["state_id","ptitle"],
		proxy : {
			type: 'jsonp',
			url : this.address_prefix + "../../../global/domain.data.php?task=searchStates",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		},
		autoLoad:true
	});


	this.store10 = new Ext.data.Store({
		fields : ["city_id","state_id","ptitle"],
		proxy : {
			type: 'jsonp',
			url : this.address_prefix + "../../../global/domain.data.php?task=searchCities",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		}
	});

	this.issueStateCombo = new Ext.form.field.ComboBox({
		store : this.store9,
		width : 200,
		typeAhead: false,
		queryMode : "local",
		displayField : "ptitle",
		valueField : "state_id",
		hiddenName : "issue_state_id",
		applyTo : this.get("issue_state_id"),
		listeners : {
			select : function(combo, records){
				PersonObject.issueCityCombo.reset();
				PersonObject.store10.load({
					params : {state_id : records[0].data.state_id}
				})
			}
		}
	});
        
        this.store3 = new Ext.data.Store({
		fields : ["InfoID","InfoDesc"],
		proxy : {
			type: 'jsonp',
			url : this.address_prefix + "../../../global/domain.data.php?task=searchReligion",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		},
		autoLoad:true
	});


	this.store4 = new Ext.data.Store({
		fields : ["InfoID","param1","InfoDesc"],
		proxy : {
			type: 'jsonp',
			url : this.address_prefix + "../../../global/domain.data.php?task=searchSubreligion",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		}
	});
	this.religionCombo = new Ext.form.field.ComboBox({
		store : this.store3,
		width : 100,
		typeAhead: false,
		queryMode : "local",
		displayField : "InfoDesc",
		valueField : "InfoID",
		hiddenName : "religion",
		applyTo : this.get("religion"),
		listeners : {
			select : function(combo, records){
				PersonObject.subreligionCombo.reset();
				PersonObject.store4.load({
					params : {MasterID : records[0].data.InfoID}
				})
			}
		}
	});

	this.subreligionCombo = new Ext.form.field.ComboBox({
		store : this.store4,
		width : 100,
		typeAhead: false,
		queryMode : "local",
		displayField : "InfoDesc",
		valueField : "InfoID",
		hiddenName : "subreligion",
		applyTo : this.get("subreligion")
	});

	this.issueCityCombo = new Ext.form.field.ComboBox({
		store : this.store10,
		width : 100,
		typeAhead: false,
		queryMode : "local",
		displayField : "ptitle",
		valueField : "city_id",
		hiddenName : "issue_city_id",
		applyTo : this.get("issue_city_id")
	});
        
        this.store5 = new Ext.data.Store({
		fields : ["InfoID","InfoDesc"],
		proxy : {
			type: 'jsonp',
			url : this.address_prefix + "../../../global/domain.data.php?task=searchMilitary",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		},
		autoLoad:true
	});


	this.store6 = new Ext.data.Store({
		fields : ["InfoID","param1","InfoDesc"],
		proxy : {
			type: 'jsonp',
			url : this.address_prefix + "../../../global/domain.data.php?task=searchSubMilitary",
			reader: {
				root: 'rows',
				totalProperty: 'totalCount'
			}
		}
	});
        
        this.militaryCombo = new Ext.form.field.ComboBox({
		store : this.store5,
		width : 100,
		typeAhead: false,
		queryMode : "local",
		displayField : "InfoDesc",
		valueField : "InfoID",
		hiddenName : "military_status",
		applyTo : this.get("military_status"),
		listeners : {
			select : function(combo, records){
				PersonObject.submilitaryCombo.reset();
				PersonObject.store6.load({
					params : {MasterID : records[0].data.InfoID}
				})
			}
		}
	});

	this.submilitaryCombo = new Ext.form.field.ComboBox({
		store : this.store6,
		width : 150,
		typeAhead: false,
		queryMode : "local",
		displayField : "InfoDesc",
		valueField : "InfoID",
		hiddenName : "military_type",
		applyTo : this.get("military_type")
	});
        
        
        this.birth_date = new Ext.form.SHDateField({
		applyTo: this.get("birth_date"),
		format: 'Y/m/d'
	});

	 this.issue_date = new Ext.form.SHDateField({
		applyTo: this.get("issue_date"),
		format: 'Y/m/d'
	});

//new added
    this.employ_date = new Ext.form.SHDateField({
    applyTo: this.get("employ_date"),
    format: 'Y/m/d'
    });
//end new added

         this.military_from_date = new Ext.form.SHDateField({
		applyTo: this.get("military_from_date"),
		format: 'Y/m/d'
	});


	 this.military_to_date = new Ext.form.SHDateField({
		applyTo:  this.get("military_to_date"),
		format: 'Y/m/d'
	});
	
	this.store1.load({
	callback:function(){
		PersonObject.birthStateCombo.setValue("<?= $obj->birth_state_id ?>");
		PersonObject.store2.load({
			params:{state_id:PersonObject.birthStateCombo.getValue()},
			callback:function(){
				PersonObject.birthCityCombo.setValue("<?= $obj->birth_city_id ?>");
			}
		});
	}
});

this.store9.load({
	callback:function(){ 
		PersonObject.issueStateCombo.setValue("<?= $obj->issue_state_id ?>");
		PersonObject.store10.load({
			params:{state_id:PersonObject.issueStateCombo.getValue()},
			callback:function(){
				PersonObject.issueCityCombo.setValue("<?= $obj->issue_city_id ?>");
			}
		});
	}
});

this.store3.load({
	callback:function(){
		PersonObject.religionCombo.setValue("<?= $obj->religion ?>");
		PersonObject.store4.load({
			params:{MasterID:PersonObject.religionCombo.getValue()},
			callback:function(){
				PersonObject.subreligionCombo.setValue("<?= $obj->subreligion ?>");
			}
		});
	}
});

this.store5.load({
	callback:function(){
		PersonObject.militaryCombo.setValue("<?= $obj->military_status ?>");
		PersonObject.store6.load({
			params:{MasterID:PersonObject.militaryCombo.getValue()},
			callback:function(){
				PersonObject.submilitaryCombo.setValue("<?= $obj->military_type ?>");
			}
		});
	}
});


	this.IncludeHistoryGrid = <?= $includeHistoryGrid?>;
	
	<?if($personID > 0 ){ ?>
		this.IncludeHistoryGrid.render(this.get("includeHistoryGRID"));
		this.sid = <?= $SummeryInfo[0]["staff_id"] ?>;
		this.pid = <?= $personID ?>;
		
	<?}?>
		   
	
}

var PersonObject = new Person();

Person.prototype.saveStaffAction = function()
{
 	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + '../../staff/data/staff.data.php?task=save',
		method: 'POST',
		form: this.staffInfoForm,
		success: function(response,option){
			mask.hide();				
			if(response.responseText.indexOf("UpdateError") != -1)
			{
				alert("عملیات مورد نظر با شکست مواجه شد");
				return;
			}
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
                            alert("ویرایش مشخصات فردی با موفقیت انجام شد .");

                            var arr = st.data.split(",");
				
                            PersonObject.staffInfoForm.staff_id.value = arr["0"];
                            PersonObject.staffInfoForm.PersonID.value = arr["1"];
                            PersonObject.staffInfoForm.person_type.value = arr["2"];

                            PersonObject.mainTab.items.get("Tab_dependency").enable(); 
			    PersonObject.mainTab.items.get("educations").enable();
			    PersonObject.mainTab.items.get("writs").enable();
			}
			else
			{
				alert(st.data);
			}
		},
		failure: function(){}
	});
	
		

}

Person.prototype.saveAction = function()
{
   
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + '../data/person.data.php?task=save',
		method: 'POST',
		form: this.personInfoForm,
		
		success: function(response,option){
			mask.hide();
			
			if(response.responseText.indexOf("InsertError") != -1 || 
				response.responseText.indexOf("UpdateError") != -1)
			{
			alert('dfdff');
				alert("عملیات مورد نظر با شکست مواجه شد");
				return;
			}
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				if(PersonObject.personInfoForm.PersonID.value == "")
					alert("ایجاد فرد با موفقیت انجام شد");
				else
					alert("ویرایش فرد با موفقیت انجام شد");
					
										
				PersonObject.personInfoForm.PersonID.value = st.data.PID ;
				
				PersonObject.mainTab.items.get("Tab_dependency").enable();                                
				PersonObject.mainTab.items.get("educations").enable();
				PersonObject.mainTab.items.get("writs").enable();							
	
				PersonObject.IncludeHistoryGrid.render(PersonObject.get("includeHistoryGRID"));		
				PersonObject.sid =  st.data.SID ; 	
				PersonObject.pid =  st.data.PID ; 	
			
			}
			else
			{
				if(st.data.indexOf("Duplicate_national_code") != -1)
				{ 
					var s = st.data.split('%');
					if(!confirm("فرد دیگری با کد ملی فوق با مشخصات زیر در سیستم ثبت شده است\n" + s[3] + "\n" +
						"آیا مایل به تغییر نوع فرد این شخص می باشید؟"))
						return;
					
					PersonObject.get("duplicate_PersonID").value = s[1];
					PersonObject.get("duplicate_person_type").value = s[2];
					PersonObject.get("duplicate_national_code").value = PersonObject.personInfoForm.national_code.value ; 
					PersonObject.get("personInfo").innerHTML = s[3].replace(/\n/g, "<br>");
					PersonObject.changePTWindow.show();
				}
				else if(st.data == "Duplicate_ProCode") {
					
					alert(" وارد شده تکراری می باشد ProCode"); 
					}
				else {
					
					alert(st.data);
					}
			}
		},
		failure: function(){}
	});
				
}

Person.prototype.ValidateForm = function()
 {   
	if(this.personInfoForm.pfname.value == "")
	{
		alert(".ورود نام الزامی است");
		this.personInfoForm.pfname.focus();
		return false;
	}
	
	if(this.personInfoForm.plname.value == "")
	{
		alert(".ورود نام خانوادگی الزامی است");
		return false;
	}
	
	if(this.personInfoForm.father_name.value == "" && this.personInfoForm.person_type.value != 500 )
	{
		alert(".ورود نام پدر الزامی است");
		return false;
	}
	
	if(this.personInfoForm.idcard_no.value == "" && this.personInfoForm.person_type.value != 500 )
	{
		alert(".ورود شماره شناسنامه الزامی است");
		return false;
	}
	
	if(this.personInfoForm.birth_date.value == "" && this.personInfoForm.person_type.value != 500 )
	{
		alert(".ورود تاریخ تولد الزامی است");
		return false;
	}
	
	if(this.personInfoForm.issue_date.value == "" && this.personInfoForm.person_type.value != 500 )
	{
		alert(".ورود تاریخ صدور شناسنامه الزامی است");
		return false;
	}
	
	if(this.personInfoForm.national_code.value == "" && this.personInfoForm.person_type.value != 500 )
	{
		alert(".ورود کدملی الزامی است ");
		return false;
	}
	
	if(this.personInfoForm.birth_state_id.value == "" && this.personInfoForm.person_type.value != 500 )
	{
		alert(".ورود محل تولد الزامی است ");
		return false;
	}
	
	if(this.personInfoForm.issue_state_id.value == "" && this.personInfoForm.person_type.value != 500 )
	{
		alert(".محل صدور شناسنامه الزامی است");
		return false;
	}
	 
	if(this.personInfoForm.national_code.value.length != 10 && this.personInfoForm.person_type.value != 500 )
	{
		alert("کد ملی ده رقم می باشد لطفا کد ملی وارد شده را اصلاح نمایید.");
		return false;
	}    
	
	
	return true;
 }

Person.prototype.LOV_ProCode = function()
{
	
        
    var FacCode = this.unitCombo.getValue();
	var EduGrpCode =this.subunitCombo.getValue();
	
	returnVal = showLOV("/HumanResources/global/LOV/ProCodeLOV.php?FacCode=" + FacCode + "&EduGrpCode=" + EduGrpCode, 780, 430);
	return (returnVal) ? returnVal : "";
}



Person.prototype.opDelRender = function(store,record,op)
{
	return  "<div  title='حذف اطلاعات' class='remove' onclick='PersonObject.deleteIncHis();' " +
			"style='float:left;background-repeat:no-repeat;background-position:center;" +
			"cursor:pointer;width:50%;height:16'></div>";
}

 
Person.prototype.SaveHistory = function(store,record,op)
{
	mask = new Ext.LoadMask(Ext.getCmp(PersonObject.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: PersonObject.address_prefix + '../data/person.data.php?task=saveIncludeHistory',
		params:{
			record: Ext.encode(record.data) ,
			Q0 : PersonObject.pid
		},
		method: 'POST',

		success: function(response,op){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success == true )
			{				
				alert("ذخیره سازی با موفقیت انجام شد.");				
				
				PersonObject.IncludeHistoryGrid.getStore().proxy.extraParams['PID'] = PersonObject.pid ; 
				PersonObject.IncludeHistoryGrid.getStore().load();					
				return;
			}
			else
			{
				alert(st.data);
			}

		},
		failure: function(){}
	});
}



Person.prototype.AddIncludeHistory = function()
{ 

    var modelClass = this.IncludeHistoryGrid.getStore().model;
	var record = new modelClass({
		staff_id : this.sid ,
		start_date : null ,
		end_date : null ,
		insure_include : null ,
		tax_include : null	

	});
    
	this.IncludeHistoryGrid.plugins[0].cancelEdit();
	this.IncludeHistoryGrid.getStore().insert(0, record);
	this.IncludeHistoryGrid.plugins[0].startEdit(0, 0);

}


Person.prototype.deleteIncHis = function()
{
	if(!confirm("آیا مایل به حذف می باشید؟"))
		return;

	var record = this.IncludeHistoryGrid.getSelectionModel().getLastSelected();
	
	mask = new Ext.LoadMask(Ext.getCmp(PersonObject.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: PersonObject.address_prefix + '../data/person.data.php',
		params:{
			task: "removeIncHistory",
			record: Ext.encode(record.data)
		},
		method: 'POST',

		success: function(response,op){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success === "true" )
			{
				alert("حذف با موفقیت انجام شد.");
				PersonObject.IncludeHistoryGrid.getStore().load();
				return;
			}
			else
			{
				alert(st.data);
			}

		},
		
		failure: function(){}
	});
}




</script>