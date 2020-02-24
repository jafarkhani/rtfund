<script type="text/javascript">
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 98.06
//-----------------------------

Person.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Person()
{
	this.FilterObj = Ext.button.Button({

	});
	/*this.InfoPanel = new Ext.form.FormPanel({
		renderTo : this.get("div_info"),
		frame: true,
		bodyPadding : "10 10 10 10",
		hidden : true,
		title: 'اطلاعات شخصی',
		width: 700,
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {labelWidth : 80},
		items : [{
			xtype :"container",
			layout : "hbox",
			items : [{
				xtype : "radio",
				boxLabel: 'شخص حقیقی',
				name: 'IsReal',
				style : "margin-right : 20px",
				checked : true,
				inputValue: 'YES',
				listeners : {
					change : function(){
						if(this.getValue())
						{
							PersonObject.InfoPanel.getComponent("RealFS").enable();
							PersonObject.InfoPanel.getComponent("NotRealFS").disable();
						}
						else
						{
							PersonObject.InfoPanel.getComponent("RealFS").disable();
							PersonObject.InfoPanel.getComponent("NotRealFS").enable();
						}
					}
				}
			},{
				xtype : "radio",
				boxLabel: 'شخص حقوقی',
				name: 'IsReal',
				inputValue: 'NO'
			}]
		},{
			xtype : "textfield",
			labelWidth : 120,
			fieldLabel : "کد ملی/ شناسه ملی",
			//regex: /^\d{10}$/,
			maskRe: /[\d\-]/,
			name : "NationalID"
		},{
			xtype : "fieldset",
			title : "اطلاعات شخص حقیقی",
			colspan : 2,
			layout : "hbox",
			itemId : "RealFS",
			defaults : {labelWidth : 70},
			items : [{
				xtype : "textfield",
				fieldLabel : "نام",
				name : "fname",
				width : 180
			},{
				xtype : "textfield",
				fieldLabel : "نام خانوادگی",
				name : "lname",
				width : 180
			},{
				xtype : "combo",
				fieldLabel : "جنسیت",
				width : 120,
				name : "sex",
				store : new Ext.data.SimpleStore({
					data : [
						["MALE" , "مرد" ],
						["FEMALE" , "زن" ]
					],
					fields : ['id','value']
				}),
				displayField : "value",
				valueField : "id"
			}]
		},{
			xtype : "fieldset",
			disabled : true,
			defaults : {labelWidth : 70},
			title : "اطلاعات شخص حقوقی",
			colspan : 2,
			layout : "hbox",
			itemId : "NotRealFS",
			items : [{
				xtype : "textfield",
				fieldLabel : "نام شرکت",
				name : "CompanyName",
				width : 360
			}]
		},{
			xtype : "textfield",
			vtype : "email",
			fieldLabel: 'پست الکترونیک',
			name: 'email',
			width : 360,
			fieldStyle : "direction:ltr"
		},{
			xtype : "textfield",
			fieldLabel : "کلمه کاربری",
			name : "UserName"
		},{
			xtype : "textfield",
			maskRe: /[\d\-]/,
			fieldLabel: 'تلفن همراه',
			name: 'mobile'
		},{
			xtype : "numberfield",
			name : "ShareNo",
			hideTrigger : true,
			labelWidth : 100,
			width : 235,
			fieldLabel : "شماره دفتر سهام"
		},{
			xtype : "numberfield",
			name : "AttCode",
			hideTrigger : true,
			labelWidth : 150,
			width : 235,
			colspan : 2,
			fieldLabel : "کد دستگاه حضور و غیاب"
		},{
			xtype : "fieldset",
			colspan : 2,
			title : "نوع ذینفع",
			layout : "hbox",
			defaults : {style : "margin-right : 20px"},
			items :[{
				xtype : "checkbox",
                boxLabel: 'همکاران صندوق',
                name: 'IsStaff',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'مشتری',
                name: 'IsCustomer',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'سهامدار',
                name: 'IsShareholder',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'سرمایه گذار',
                name: 'IsAgent',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'حامی',
                name: 'IsSupporter',
                inputValue: 'YES'
			},{
				xtype : "checkbox",
                boxLabel: 'کارشناس خارج از صندوق',
                name: 'IsExpert',
                inputValue: 'YES'
			}]
		},{
			xtype : "checkbox",
			name : "IsSigner",
			colspan : 2,
			boxLabel : "فرد صاحب امضا است",
			inputValue : "YES"
		},{
			xtype : "container",
			colspan : 2,
			layout : "hbox",
			items : [{
				xtype : "filefield",
				name : "PersonSign",
				fieldLabel : "امضا"
			},{
				xtype : "button",
				style : "margin-right:20px",
				iconCls : "sign",
				text : "تصویر امضا",
				handler : function(){
					me = PersonObject;
					PersonID = me.InfoPanel.down("[name=PersonID]").getValue();
					if(!PersonID)
						return;
					window.open(me.address_prefix + "showImage.php?PersonSign=true&PersonID=" + PersonID);
				}
			}]
			
		},{
			xtype : "hidden",
			name : "PersonID"
		}],
		buttons :[{
			text : "ریست تلاش های ناموفق ورود به سیستم",
			disabled : true,
			itemId : "ResetAttemptBTN",
			iconCls : "refresh",
			handler : function(){ PersonObject.ResetAttempt(); }
		},{
			text : "ریست رمز عبور",
			disabled : true,
			itemId : "ResetPassBTN",
			iconCls : "lock",
			handler : function(){ PersonObject.ResetPass(); }
		},{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ PersonObject.saveData(); }
		},{
			text : "بازگشت",
			iconCls : "undo",
			handler : function(){ PersonObject.InfoPanel.hide();}
		}]
	});*/
}

    Person.deleteRender = function(v,p,r)
    {
        /*if(r.data.IsActive == "NO")
        return "";*/
        return "<div align='center' title='حذف شخص' class='remove' onclick='PersonObject.Deleting();' " +
        "style='background-repeat:no-repeat;background-position:center;" +
        "cursor:pointer;width:100%;height:16'></div>";
    }



    Person.prototype.Deleting = function()
    {
        var record = this.grid.getSelectionModel().getLastSelected();

        Ext.MessageBox.confirm("","آيا مايل به حذف مي باشيد؟", function(btn){
        if(btn == "no")
        return;

        Ext.Ajax.request({
        url : PersonObject.address_prefix + "alterPersons.data.php",
        method : "POST",
        params : {
        task : "DeletePerson",
        PersonID : record.data.AlterPersonID
    },
        success : function(response,o)
    {
        PersonObject.grid.getStore().load();
    }
    });
    });
    }















</script>
