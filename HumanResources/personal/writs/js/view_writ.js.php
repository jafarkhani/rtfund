<script type="text/javascript">
//---------------------------
// programmer:	Jafarkhani
// create Date:	89.11
//---------------------------

setInterval(keepOnline, 600000);

function keepOnline(){Ext.Ajax.request({url: "../../HumanResources/header.inc.php", method: 'POST'});}

ViewWrit.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	staff_id : '<?= $staff_id ?>',
	AllWrits : new Array(<?= $jsArr ?>),
	WritIndex	 : <?= $currentWritIndex ?>,
        
	mainPanel : "",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

ViewWrit.prototype.moveWrit = function(elem, direction)
{
	if(elem && elem.className == "disable_moveItem")
		return false;

	    this.get("btn_previosVersion").className =
		this.get("btn_nextVersion").className =
		this.get("btn_next").className =
		this.get("btn_last").className =
		this.get("btn_first").className =
		this.get("btn_previous").className = "moveItem";

	switch(direction)
	{
		case "previous":
		case "previosVersion":
			this.WritIndex--;
			break;
		case "next":
		case "nextVersion":
			this.WritIndex++;
			break;
		case "last":
			this.WritIndex = this.AllWrits.length - 1;
			break;
		case "first":
			this.WritIndex = 0;
			break;
	}
	if(this.WritIndex != 0 && this.AllWrits[this.WritIndex - 1].writ_id != this.AllWrits[this.WritIndex].writ_id)
		this.get("btn_previosVersion").className = "disable_moveItem";

	if(this.AllWrits[this.WritIndex + 1] && this.AllWrits[this.WritIndex + 1].writ_id != this.AllWrits[this.WritIndex].writ_id)
		this.get("btn_nextVersion").className = "disable_moveItem";

	if(this.WritIndex == this.AllWrits.length -1)
	{
		this.get("btn_next").className = "disable_moveItem";
		this.get("btn_last").className = "disable_moveItem";
		this.get("btn_nextVersion").className = "disable_moveItem";
	}
	if(this.WritIndex == 0)
	{
		this.get("btn_previous").className = "disable_moveItem";
		this.get("btn_previosVersion").className = "disable_moveItem";
		this.get("btn_first").className = "disable_moveItem";
	}

	this.mainPanel.loader.load({
		params:{
			WID : this.AllWrits[this.WritIndex].writ_id,
			STID : this.staff_id,
			WVER : this.AllWrits[this.WritIndex].writ_ver,
			ExeDate  : this.AllWrits[this.WritIndex].execute_date,
			FacilID : this.FacilID
		}
	});
}

function ViewWrit()
{
	this.form = this.get("form_viewWrit");
	
	this.mainPanel = new Ext.Panel({
		applyTo: this.get("DIV_writ"),
		//title: "حکم کارگزینی",
		border : 0,
		width: 780,
		loader : {
			url : this.address_prefix + "writ_form.php",
			scripts : true
		}
	});
	
	this.addItemWin = new Ext.Window({
		applyTo : this.get("newItemWindow"),
		layout:'fit',
		modal: true,
		width:600,
		constrain : true,
		closeAction:'hide',
		loader : {
			url: this.address_prefix + "new_writ_salary_item.php",
			scripts: true
		},
		buttons :[{
			text : "ذخیره",
			iconCls : "save",
                        itemId : "save",
			handler : function(){WritFormObject.saveItem();}
		},{
			text : "انصراف",
			iconCls : "undo",                        
			handler : function(){this.up('window').hide();}
		}]
	});
 
	this.moveWrit();
}

var ViewWritObject = new ViewWrit();

//------------------------------------------------------------------------------

WritForm.prototype = {

	parent : "",	
	prevItemsGrid : "",
	curItemsGrid : "",
	writ_id : "",
	writ_ver : "",
	cost_center_id : "",
	postCombo : "",

	get : function(elementID){
		return findChild(this.parent.TabID, elementID);
	}
};

var WritFormObject ;

function WritForm(parent)
{
	this.parent = parent;
	this.form = this.parent.get("form_WritForm");
        
	if(this.get('warning_date'))
		this.warning_date = new Ext.form.SHDateField({
			inputId :'warning_date',
			applyTo: this.get('warning_date'),
			format: 'Y/m/d'
		});

	if(this.get('issue_date'))
		this.issue_date = new Ext.form.SHDateField({
			applyTo: this.get('issue_date'),
			format: 'Y/m/d'
		});
	if(this.get('pay_date'))
		this.pay_date = new Ext.form.SHDateField({
                        inputId :'pay_date',
			applyTo: this.get('pay_date'),
			format: 'Y/m/d'
		});
	if(this.get('ref_letter_date'))
		this.ref_letter_date = new Ext.form.SHDateField({
			applyTo: this.get('ref_letter_date'),
			format: 'Y/m/d'
		});
	if(this.get('send_letter_date'))
		this.send_letter_date = new Ext.form.SHDateField({
			applyTo: this.get('send_letter_date'),
			format: 'Y/m/d'
		});

	if(this.get("contract_start_date"))
	{
		this.contract_start_date = new Ext.form.SHDateField({
			applyTo: this.get('contract_start_date'),
			format: 'Y/m/d'
		});
		this.contract_end_date = new Ext.form.SHDateField({
			applyTo: this.get('contract_end_date'),
			format: 'Y/m/d'
		});
	}
        
        
	new Ext.form.TriggerField({
	    triggerCls:'x-form-search-trigger',
	    onTriggerClick : function(){	    	
		WritFormObject.ActDomainLOV();
	    },
	    applyTo : this.get("UnitDesc"),
	    width : 300
	});
	
WritForm.prototype.ActDomainLOV = function(record){
		
	if(!this.DomainWin)
	{
		this.DomainWin = new Ext.window.Window({
			autoScroll : true,
			width : 480,
			height : 550,
			title : "حوزه فعالیت",
			closeAction : "hide",
			loader : {
				url : this.address_prefix + "../../../../framework/baseInfo/units.php?mode=adding",				
				scripts : true
			}
		});
		
		Ext.getCmp(this.TabID).add(this.DomainWin);
	}
	
	this.DomainWin.show();
	
	this.DomainWin.loader.load({
		params : {
			ExtTabID : this.DomainWin.getEl().dom.id,
			parent : "WritFormObject.DomainWin",
                        MenuID : this.MenuID ,
			selectHandler : function(id, name){
                                         WritFormObject.form.UnitDesc.value = name ; 
                                         WritFormObject.form.UnitID.value = id;			
			}
		}
	});	

	
}
	/*new Ext.form.TriggerField({
	    triggerCls:'x-form-search-trigger',
	    onTriggerClick : function(){
			returnVal = LOV_Post('ALL');
			if(returnVal != "")
			{aa = returnVal;
				this.setValue(returnVal.post_id);
				WritFormObject.get("post_title").innerHTML = returnVal.post_no + "-" + returnVal.post_title;
			}

	    },
	    applyTo : this.get("post_id"),
	    width : 120
	});*/
 
	this.afterLoad();

    this.InsertWSI = new Ext.form.FieldSet({
		title : "اضافه قلم حقوقی به حکم",
		renderTo : this.get("div_fs"),
		width: 400,
		layout : "hbox",
		hidden : true,
		style : "background-color: #E9EFFE" ,
		items : [{
			xtype : "combo",
			name : "salary_item_type_id",
			store : new Ext.data.Store({
				fields : ["salary_item_type_id","full_title"],
				proxy : {
					type : "jsonp",
					url : this.parent.address_prefix + "../data/writ_item.data.php?task=not_assigned_items",
					extraParams : {
						writ_id : this.writ_id,
						writ_ver : this.writ_ver,
						staff_id : this.staff_id
					},
					reader: {
						root: 'rows',
						totalProperty: 'totalCount'
					}
				}
			}),
			flex : 3,
			valueField : "salary_item_type_id",
			displayField : "full_title"
		},{
			xtype : "component",
			width : 10
		},{
			xtype : "button",
			flex : 1,
			text : "افزودن",
			iconCls : "add",
			handler : function(){WritFormObject.AddSalaryItem();}

		}]
	});
	
	
	
	if(this.person_type == "<?= HR_PROFESSOR?>" || this.person_type == "<?= HR_EMPLOYEE?>" || this.person_type == "<?= HR_CONTRACT?>")
	{
		this.postCombo = new Ext.form.ComboBox({

			store : postStore ,
			displayField:'post_id',
			valueField : "post_id",
			width : 600,
			mode: 'local',
			disabled : this.ouid != "" ? false : true,
			triggerAction: 'all',
			emptyText:'انتخاب پست سازمانی ...',
			selectOnFocus:true,
			applyTo: this.get("post"),
			itemSelector: 'tr.search-item',
			tpl: new Ext.XTemplate(
					'<table cellspacing="0" width="100%"><tr class="x-grid3-header">'
						,'<td height="23px">شناسه پست</td>'
						,'<td height="23px">شماره پست</td>'
						,'<td>عنوان پست</td>'
						,'<td>عنوان واحد محل خدمت</td></tr>',
					'<tpl for=".">',
					'<tr class="search-item" style="border-left:0;border-right:0">'
						,'<td style="border-left:0;border-right:0" class="search-item">{post_id}</td>'
						,'<td style="border-left:0;border-right:0" class="search-item">{post_no}</td>'
						,'<td style="border-left:0;border-right:0" class="search-item">{title}</td>'
						,'<td style="border-left:0;border-right:0" class="search-item">{unit_title}&nbsp;</td></tr>',
					'</tpl>'
					,'</table>'),
			listeners : {
				select : function(record)
				{
					WritFormObject.get("post").value = "[" + record.data.post_id + "] " + record.data.post_no + "-" + record.data.title;
					WritFormObject.get("post_id").value = record.data.post_id;
					this.collapse();
				},
				enable : function()
				{
					this.getStore().baseParams = {ouid : WritFormObject.get("ouid").value};
					this.getStore().load();
					return true;
				}
			}
		});
	}
	//--------------------------------------------------------------------------
    if ( this.exeDate < '2005-03-21') { 

            this.store3 = new Ext.data.Store({
				fields : ["writ_type_id","title","person_type"],
				proxy : {
					type: 'jsonp',
					url : this.address_prefix + "../../global/domain.data.php?task=searchWritTypes",
					reader: {
						root: 'rows',
						totalProperty: 'totalCount'
					}
				}
			}); 
            this.store4 = new Ext.data.Store({
                fields : ["writ_type_id","writ_subtype_id","title"],
                proxy : {
                    type: 'jsonp',
                    url : this.address_prefix + "../../global/domain.data.php?task=searchWritSubTypes",
                    reader: {
                        root: 'rows',
                        totalProperty: 'totalCount'
                    }
                }
            }); 

        this.writTypeCombo = new Ext.form.field.ComboBox({
            store : this.store3,
            width : 200,
            typeAhead: false,
            queryMode : "local",
            displayField : "title",
            valueField : "writ_type_id",
            hiddenName : "writ_type_id" ,
            applyTo : this.get("WritTypeID"),
            listeners : {
                select : function(combo, records){
                    WritFormObject.writSubTypeCombo.reset();
                    WritFormObject.store4.load({
                        params : {writ_type_id : records[0].data.writ_type_id,
                                  person_type : records[0].data.person_type }
                    })
                }
            }
        });

        this.writSubTypeCombo = new Ext.form.field.ComboBox({
            store : this.store4,
            width : 200,
            typeAhead: false,
            queryMode : "local",
            displayField : "title",
            valueField : "writ_subtype_id",
            hiddenName : "writ_subtype_id" ,
            applyTo : this.get("WritSubTypeID")
        });

        this.store3.load({ 
			params : {person_type : this.person_type } ,
            callback:function(){ 
				WritFormObject.writTypeCombo.setValue(WritFormObject.writ_type_id);
                WritFormObject.store4.load({
                params:{writ_type_id:WritFormObject.writTypeCombo.getValue(),
                        person_type: WritFormObject.person_type},
                callback:function(){
					WritFormObject.writSubTypeCombo.setValue(WritFormObject.writ_subtype_id);
                }
				});
			}
		});
    } 

 

}


WritForm.prototype.saveInfo = function()
{
	mask = new Ext.LoadMask(Ext.getCmp(this.parent.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({ 
		url: this.parent.address_prefix + '../data/writ.data.php?task=saveWritInfo',
		params:{
			staff_id: ViewWritObject.staff_id ,
			writ_id: this.writ_id ,
			writ_ver: this.writ_ver
		},
		method: 'POST',
		form: this.form,


        success: function(response,op)
		{
			mask.hide();
			
			var st = Ext.decode(response.responseText);
			if(st.success)
			{				
				alert("ویرایش حکم با موفقیت انجام گردید.");				
				WritFormObject.get("dutyBase").value =    WritFormObject.get("onduty_year").value ; 
				return;
			}
			else
			{
				ShowExceptions("ErrorDiv",st.data);
			}
		}
		/*success: function(response,option){
			mask.hide();
			if(response.responseText.indexOf("InsertError") != -1 ||
				response.responseText.indexOf("UpdateError") != -1)
			{
				alert("عملیات مورد نظر با شکست مواجه شد");
				return;
			}
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
			   alert("ویرایش حکم با موفقیت انجام گردید.");

			}
			else
			{
				alert(response.responseText);
			}
		}/*/,
		failure: function(){}
	});
}

WritForm.prototype.AddSalaryItem = function()
{  
	this.parent.addItemWin.show();
	this.parent.addItemWin.center();
	this.parent.addItemWin.loader.load({
		params: {
			salary_item_type_id: this.InsertWSI.down("combo").getValue(), //this.get("salary_item_type_id").value,
			writ_id: this.writ_id,
			writ_ver: this.writ_ver,
			staff_id: this.staff_id,
            execute_date:this.exeDate,
			mode: 'new'
		}		
	});
}

WritForm.prototype.saveItem = function()
{  
	mask = new Ext.LoadMask(Ext.getCmp(this.parent.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: this.parent.address_prefix + "../data/writ_item.data.php?task=SaveItem",
		method: "POST",
		form: ViewWritObject.get("form_newWritSalaryItem"),

		success: function(response,op)
		{
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				ViewWritObject.addItemWin.hide();

				WritFormObject.InsertWSI.down("combo").clearValue();
				WritFormObject.InsertWSI.down("combo").getStore().load();
				WritFormObject.curItemsGrid.getStore().load();
				alert("ذخیره با موفقیت انجام شد");

				return;
			}
			else
			{
				ViewWritObject.addItemWin.hide();
				alert(st.data);
			}
		}
	});
}

WritForm.prototype.DontPayItem = function()
{

	if(!confirm("آیا از عدم پرداخت کلیه قلم ها اطمینان دارید ؟"))
		return;

	mask = new Ext.LoadMask(Ext.getCmp(this.parent.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: this.parent.address_prefix + "../data/writ_item.data.php?task=DontPayItem",
		method: "POST",
		params: {
			writ_id: this.writ_id,
			writ_ver: this.writ_ver,
			staff_id: this.staff_id
		},
		success: function(response,op)
		{
			var st = Ext.decode(response.responseText);
			mask.hide();
			if(st.success)
			{
				alert("عملیات با موفقیت انجام شد .");
				return;
			}
		}
	});

}

WritForm.prototype.DeleteItem = function()
{
	var record = this.curItemsGrid.getSelectionModel().getLastSelected();
	if(!record)
	{
		alert("ابتدا یک ردیف را برای حذف انتخاب کنید");
		return;
	}

	if(!confirm("آيا از حذف اطمينان داريد؟"))
		return;

	mask = new Ext.LoadMask(Ext.getCmp(this.parent.TabID), {msg:'در حال ذخيره سازي...'});
	mask.show();

	Ext.Ajax.request({
		url: this.parent.address_prefix + "../data/writ_item.data.php?task=DeleteItem",
		method: "POST",
		params: {
			writ_id: record.data.writ_id,
			writ_ver: record.data.writ_ver,
			staff_id: record.data.staff_id,
			salary_item_type_id: record.data.salary_item_type_id
		},

		success: function(response,op)
		{
			mask.hide();
			if(response.responseText == "true")
			{
				alert("حذف آیتم با موفقیت انجام شد");

				WritFormObject.InsertWSI.down("combo").clearValue();
				WritFormObject.InsertWSI.down("combo").getStore().load();
				WritFormObject.curItemsGrid.getStore().load();
				return;
			}

		}
	});
}

WritForm.prototype.EditItem = function(Access)
{
	var record = this.curItemsGrid.getSelectionModel().getLastSelected();
	if(!record)
	{
		alert("ابتدا یک ردیف  را انتخاب کنید");
		return;
	}
	
	this.parent.addItemWin.loader.load({
		params: {
			salary_item_type_id: record.data.salary_item_type_id,
			writ_id: this.writ_id,
			writ_ver: this.writ_ver,
			execute_date : this.exeDate,
			staff_id: ViewWritObject.staff_id,
			Access : Access,
            execute_date:this.exeDate,
			mode: 'edit'
		}
	});
	this.parent.addItemWin.show();
}

WritForm.prototype.Recalculate = function()
{
	mask = new Ext.LoadMask(Ext.getCmp(this.parent.TabID), {msg:'در حال انجام عملیات ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.parent.address_prefix + "../data/writ.data.php?task=recalculate",
		method: "POST",
		params: {
			writ_id: this.writ_id,
			writ_ver: this.writ_ver,
			staff_id: ViewWritObject.staff_id
		},

		success: function(response,op)
		{
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				alert("محاسبه اقلام با موفقیت انجام شد");
				WritFormObject.curItemsGrid.getStore().load();
				return;
			}
		}
        
	});
}

WritForm.prototype.Calculate = function()
{
	mask = new Ext.LoadMask(Ext.getCmp(this.parent.TabID), {msg:'در حال انجام عملیات ...'});
	mask.show();
	Ext.Ajax.request({
		url: this.parent.address_prefix + "../data/writ.data.php?task=calculate",
		method: "POST",
		params: {
			writ_id:  this.writ_id,
			writ_ver: this.writ_ver,
			staff_id: ViewWritObject.staff_id
		},

		success: function(response,op)
		{
			mask.hide();
			var st = Ext.decode(response.responseText);
			if(st.success)
			{
				alert("محاسبه اقلام با موفقیت انجام شد");
				if(WritFormObject.InsertWSI.isHidden() == false)
					WritFormObject.InsertWSI.down("combo").getStore().load();
				
				WritFormObject.curItemsGrid.getStore().load();
				return;
			}
		}
         
	});
}

WritForm.currencyRender = function(value,p,record)
{
	//return PriceEncode(value);
	return "<span title='" + CurrencyToString(value) + "'>" + PriceEncode(value) + "</span>";
}

WritForm.prototype.Prior_Corrective_Writ = function()
{

    Ext.Ajax.request({
		url: this.parent.address_prefix + "../data/writ.data.php?task=Prior_Corrective_Writ",
		method: "POST",
		params: {
			writ_id: this.writ_id,
			writ_ver: this.writ_ver,
			staff_id: ViewWritObject.staff_id ,
            corrective_date: this.form.corrective_date.value ,
            corrective_writ_id: this.form.corrective_writ_id.value ,
            corrective_writ_ver: this.form.corrective_writ_ver.value ,
            execute_date: this.form.execute_date.value ,
            corrective: this.form.corrective.value
		},

		success : function(response)
		{
			var ret = Ext.decode(response.responseText);
			if(ret.success == true )
			{  
                framework.CloseTab(ViewWritObject.TabID);
				framework.OpenPage(ViewWritObject.address_prefix + "../ui/view_writ.php", "حکم اصلاح شده",
                            { WID : ret.data.WID,
                              WVER : ret.data.WVER,
                              STID : ret.data.STF });
			}
            else 
            {   
                framework.CloseTab(ViewWritObject.TabID);
                framework.OpenPage(ViewWritObject.address_prefix + "../ui/CorrectiveIssueWrit.php","  صدور حکم اصلاحی");
            }
			
		}
	});
    
}

WritForm.prototype.Next_Corrective_Writ = function()
{

    Ext.Ajax.request({
		url: this.parent.address_prefix + "../data/writ.data.php?task=Next_Corrective_Writ",
		method: "POST",
		params: {
			writ_id: this.writ_id,
			writ_ver: this.writ_ver,
			staff_id: ViewWritObject.staff_id ,
            corrective_date: this.form.corrective_date.value ,
            corrective_writ_id: this.form.corrective_writ_id.value ,
            corrective_writ_ver: this.form.corrective_writ_ver.value ,
            execute_date: this.form.execute_date.value ,
            corrective: this.form.corrective.value
		},

		success : function(response)
		{
			var ret = Ext.decode(response.responseText);
                        
			if(ret.success == true )
			{  
				framework.OpenPage(ViewWritObject.address_prefix + "../ui/view_writ.php", "حکم صادره جدید",
                            { WID : ret.data.WID,
                              WVER : ret.data.WVER,
                              STID : ret.data.STF });
			}
            else
			{
				ShowExceptions(ViewWritObject.get("ErrorDiv"), ret.data);
			}            

		}
	});

}

WritForm.prototype.print = function(version)
{ 
	window.open(this.parent.address_prefix + "print_writ.php" +
		"?writ_id=" + this.writ_id +
		"&writ_ver=" + this.writ_ver +
		"&staff_id=" + this.staff_id +
		"&transcript_no=" + version,
		"",'directories=0,location=0,menubar=1,status=0,toolbar=1,scrollbars,resizable,height=800,width=700');
}

</script>